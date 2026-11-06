<?php

namespace Tests\Feature;

use App\Enums\Cycle;
use App\Mail\PaymentReminderMail;
use App\Models\Client;
use App\Models\Holiday;
use App\Models\PaymentDue;
use App\Models\PaymentPlan;
use App\Models\ReminderLog;
use App\Services\DueGenerator;
use App\Support\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Holiday::ensureYear(2026);
        Holiday::ensureYear(2027);
        Mail::fake();
    }

    private function plan(string $firstDue, Cycle $cycle = Cycle::Once, bool $withContact = true): PaymentPlan
    {
        $client = Client::create(['name' => 'Acme', 'active' => true]);
        if ($withContact) {
            $client->contacts()->create(['name' => 'Ana', 'email' => 'ana@acme.test', 'receives_reminders' => true]);
        }
        $plan = $client->plans()->create([
            'concept' => 'Mantenimiento', 'amount' => 1000, 'currency' => 'MXN',
            'cycle' => $cycle, 'anchor_date' => $firstDue, 'active' => true,
        ]);
        app(DueGenerator::class)->syncPlan($plan);

        return $plan;
    }

    private function at(string $date): void
    {
        $this->travelTo(CarbonImmutable::parse("$date 08:00:00"));
    }

    #[DataProvider('reminderDates')]
    public function test_reminder_date_counts_two_business_days_back(string $due, string $expected): void
    {
        $date = (new BusinessCalendar)->reminderDate(CarbonImmutable::parse($due));

        $this->assertSame($expected, $date->toDateString());
    }

    public static function reminderDates(): array
    {
        return [
            'miércoles normal' => ['2026-11-11', '2026-11-09'],
            'lunes salta el fin de semana' => ['2026-11-09', '2026-11-05'],
            'vence en sábado' => ['2026-11-07', '2026-11-05'],
            'vence en domingo' => ['2026-11-08', '2026-11-05'],
            'salta el feriado del 16 de noviembre' => ['2026-11-18', '2026-11-13'],
            'cruza Navidad y fin de semana' => ['2026-12-28', '2026-12-23'],
        ];
    }

    public function test_sends_exactly_on_the_reminder_day_and_only_once(): void
    {
        $this->at('2026-11-06');
        $plan = $this->plan('2026-11-18');

        $this->at('2026-11-12');
        $this->artisan('reminders:send')->assertSuccessful();
        Mail::assertNothingSent();

        $this->at('2026-11-13');
        $this->artisan('reminders:send')->assertSuccessful();
        Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->hasTo('ana@acme.test'));

        $this->artisan('reminders:send')->assertSuccessful();
        Mail::assertSentCount(1);

        $this->assertNotNull($plan->dues()->first()->reminder_sent_at);
        $this->assertSame(1, ReminderLog::where('status', ReminderLog::SENT)->count());
    }

    public function test_catches_up_after_missed_days_but_not_after_due_date(): void
    {
        $this->at('2026-11-02');
        $this->plan('2026-11-11'); // el recordatorio tocaba el lunes 9

        $this->at('2026-11-10');
        $this->artisan('reminders:send');
        Mail::assertSentCount(1);

        $this->plan('2026-11-11')->dues()->update(['reminder_sent_at' => null]);
        $this->at('2026-11-12');
        $this->artisan('reminders:send');
        Mail::assertSentCount(1);
    }

    public function test_does_not_send_on_weekends_or_holidays(): void
    {
        $this->at('2026-11-06');
        $this->plan('2026-11-09');

        $this->at('2026-11-07');
        $this->artisan('reminders:send');
        Mail::assertNothingSent();

        $this->at('2026-11-09');
        $this->artisan('reminders:send');
        Mail::assertSentCount(1);
    }

    public function test_paid_and_cancelled_dues_are_not_reminded(): void
    {
        $this->at('2026-11-02');
        $this->plan('2026-11-11')->dues()->update(['status' => PaymentDue::PAID]);
        $this->plan('2026-11-11')->dues()->update(['status' => PaymentDue::CANCELLED]);

        $this->at('2026-11-09');
        $this->artisan('reminders:send');

        Mail::assertNothingSent();
    }

    public function test_paused_client_and_plan_are_not_reminded(): void
    {
        $this->at('2026-11-02');
        $this->plan('2026-11-11')->client->update(['active' => false]);
        $this->plan('2026-11-11')->update(['active' => false]);

        $this->at('2026-11-09');
        $this->artisan('reminders:send');

        Mail::assertNothingSent();
    }

    public function test_only_contacts_opted_in_receive_it(): void
    {
        $this->at('2026-11-02');
        $plan = $this->plan('2026-11-11');
        $plan->client->contacts()->create(['name' => 'Beto', 'email' => 'beto@acme.test', 'receives_reminders' => true]);
        $plan->client->contacts()->create(['name' => 'Caro', 'email' => 'caro@acme.test', 'receives_reminders' => false]);

        $this->at('2026-11-09');
        $this->artisan('reminders:send');

        Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->hasTo('ana@acme.test')
            && $mail->hasTo('beto@acme.test')
            && ! $mail->hasTo('caro@acme.test'));
    }

    public function test_reminder_includes_hidden_copy_when_configured(): void
    {
        config(['reminders.bcc' => 'yo@empresa.test']);
        $this->at('2026-11-02');
        $this->plan('2026-11-11');

        $this->at('2026-11-09');
        $this->artisan('reminders:send');

        Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->hasTo('ana@acme.test')
            && $mail->hasBcc('yo@empresa.test')
            && ! $mail->hasTo('yo@empresa.test'));
        $this->assertSame(['ana@acme.test'], ReminderLog::first()->recipients);
    }

    public function test_reminder_has_no_hidden_copy_by_default(): void
    {
        config(['reminders.bcc' => null]);
        $this->at('2026-11-02');
        $this->plan('2026-11-11');

        $this->at('2026-11-09');
        $this->artisan('reminders:send');

        Mail::assertSent(PaymentReminderMail::class, fn ($mail) => $mail->bcc === []);
    }

    public function test_reminder_shows_the_embedded_logo(): void
    {
        $this->at('2026-11-02');
        $due = $this->plan('2026-11-11')->dues()->first();

        $html = (new PaymentReminderMail($due))->render();

        $this->assertStringContainsString('src="data:image/png;base64,', $html);
    }

    public function test_client_without_contacts_is_logged_as_failed_and_retried(): void
    {
        $this->at('2026-11-02');
        $plan = $this->plan('2026-11-11', withContact: false);

        $this->at('2026-11-09');
        $this->artisan('reminders:send')->assertFailed();
        Mail::assertNothingSent();
        $this->assertSame(ReminderLog::FAILED, ReminderLog::first()->status);

        $plan->client->contacts()->create(['name' => 'Ana', 'email' => 'ana@acme.test', 'receives_reminders' => true]);
        $this->artisan('reminders:send')->assertSuccessful();
        Mail::assertSentCount(1);
    }

    public function test_smtp_failure_keeps_due_unsent_and_other_clients_still_get_theirs(): void
    {
        $this->at('2026-11-02');
        $broken = $this->plan('2026-11-11');
        $broken->client->contacts()->delete();
        $broken->client->contacts()->create(['name' => 'X', 'email' => 'x@acme.test', 'receives_reminders' => true]);
        $this->plan('2026-11-12');

        $real = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->andReturnUsing(function ($to) use ($real) {
            if (in_array('x@acme.test', (array) $to, true)) {
                throw new RuntimeException('SMTP caído');
            }

            return $real->to($to);
        });

        $this->at('2026-11-10');
        $this->artisan('reminders:send')->assertFailed();

        $this->assertNull($broken->dues()->first()->reminder_sent_at);
        $this->assertSame('SMTP caído', ReminderLog::where('status', ReminderLog::FAILED)->first()->error);
        $this->assertSame(1, ReminderLog::where('status', ReminderLog::SENT)->count());
    }

    public function test_log_mailer_sends_nothing_and_marks_nothing(): void
    {
        config(['mail.default' => 'log']);
        $this->at('2026-11-02');
        $plan = $this->plan('2026-11-11');

        $this->at('2026-11-09');
        $this->artisan('reminders:send')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($plan->dues()->first()->reminder_sent_at);
    }

    public function test_dry_run_does_not_send(): void
    {
        $this->at('2026-11-02');
        $plan = $this->plan('2026-11-11');

        $this->at('2026-11-09');
        $this->artisan('reminders:send --dry-run')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($plan->dues()->first()->reminder_sent_at);
    }

    public function test_monthly_plan_on_31st_keeps_its_day_after_february(): void
    {
        $this->at('2027-01-01');
        $plan = $this->plan('2027-01-31', Cycle::Monthly);

        $this->at('2027-03-01');
        app(DueGenerator::class)->syncPlan($plan);

        $dates = $plan->dues()->orderBy('due_date')->get()->map(fn ($due) => $due->due_date->toDateString())->all();

        $this->assertContains('2027-02-28', $dates);
        $this->assertContains('2027-03-31', $dates);
    }

    public function test_generating_twice_creates_no_duplicates_and_keeps_paid_dues(): void
    {
        $this->at('2026-11-06');
        $plan = $this->plan('2026-11-10', Cycle::Monthly);
        $count = $plan->dues()->count();
        $plan->dues()->first()->update(['status' => PaymentDue::PAID]);

        app(DueGenerator::class)->syncPlan($plan);

        $this->assertSame($count, $plan->dues()->count());
        $this->assertSame(1, $plan->dues()->where('status', PaymentDue::PAID)->count());
    }

    public function test_annual_plan_always_has_its_next_due_even_past_the_horizon(): void
    {
        $this->at('2026-11-06');
        $plan = $this->plan('2027-10-01', Cycle::Annual);

        $this->assertSame(1, $plan->dues()->count());
    }

    public function test_plan_stops_generating_after_end_date(): void
    {
        $this->at('2026-11-06');
        $plan = $this->plan('2026-11-10', Cycle::Weekly);
        $plan->dues()->delete();
        $plan->update(['ends_on' => '2026-11-20']);

        app(DueGenerator::class)->syncPlan($plan);

        $this->assertSame(2, $plan->dues()->count());
    }

    public function test_generates_mexican_holidays_including_floating_ones(): void
    {
        $days = Holiday::mexicanHolidays(2027);

        $this->assertArrayHasKey('2027-02-01', $days);
        $this->assertArrayHasKey('2027-03-15', $days);
        $this->assertArrayHasKey('2027-11-15', $days);
        $this->assertArrayHasKey('2030-10-01', Holiday::mexicanHolidays(2030));
        $this->assertArrayNotHasKey('2027-10-01', $days);
    }

    public function test_deleted_holiday_does_not_come_back(): void
    {
        Holiday::whereDate('date', '2026-11-16')->delete();

        Holiday::ensureYear(2026);

        $this->assertFalse(Holiday::whereDate('date', '2026-11-16')->exists());
    }
}
