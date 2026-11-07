<?php

namespace Tests\Feature;

use App\Enums\Cycle;
use App\Models\Client;
use App\Models\Holiday;
use App\Models\PaymentDue;
use App\Models\PaymentPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-06 10:00:00'));
        Holiday::ensureYear(2026);
        Mail::fake();
        $this->user = User::create(['name' => 'Admin', 'email' => 'admin@test.mx', 'password' => 'secreto-largo-123']);
    }

    private function client(): Client
    {
        $client = Client::create(['name' => 'Acme', 'active' => true]);
        $client->contacts()->create(['name' => 'Ana', 'email' => 'ana@acme.test', 'receives_reminders' => true]);

        return $client;
    }

    private function planData(array $overrides = []): array
    {
        return $overrides + [
            'concept' => 'Hosting', 'amount' => '1500.50', 'currency' => 'MXN',
            'cycle' => 'monthly', 'next_date' => '2026-11-20', 'active' => '1',
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_guests_are_sent_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public static function protectedRoutes(): array
    {
        return [['/'], ['/clients'], ['/cobros'], ['/historial'], ['/dias-inhabiles']];
    }

    public function test_login_works_and_wrong_password_is_rejected(): void
    {
        $this->post('/login', ['email' => 'admin@test.mx', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'admin@test.mx', 'password' => 'secreto-largo-123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_login_is_rate_limited(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->post('/login', ['email' => 'admin@test.mx', 'password' => 'mala']);
        }

        $this->post('/login', ['email' => 'admin@test.mx', 'password' => 'mala'])->assertStatus(429);
    }

    public function test_admin_command_creates_user_and_rejects_short_password(): void
    {
        $this->artisan('app:admin', ['email' => 'nueva@test.mx'])
            ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'corta')
            ->assertFailed();

        $this->artisan('app:admin', ['email' => 'nueva@test.mx'])
            ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'una-contrasena-larga')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'nueva@test.mx']);
    }

    public function test_admin_can_be_created_from_env_without_a_prompt(): void
    {
        putenv('ADMIN_PASSWORD=corta');
        $this->artisan('app:admin', ['email' => 'cron@test.mx', '--from-env' => true])->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'cron@test.mx']);

        putenv('ADMIN_PASSWORD=una-contrasena-larga');
        $this->artisan('app:admin', ['email' => 'cron@test.mx', '--from-env' => true])->assertSuccessful();
        putenv('ADMIN_PASSWORD');

        $this->assertTrue(Hash::check('una-contrasena-larga', User::firstWhere('email', 'cron@test.mx')->password));
    }

    public function test_full_flow_client_contact_plan_and_every_page_renders(): void
    {
        $this->actingAs($this->user);

        $this->post('/clients', ['name' => 'Tacos <b>Don Pepe</b>', 'active' => '1'])->assertRedirect();
        $client = Client::first();

        $this->post("/clients/{$client->id}/contacts", ['name' => 'Ana', 'email' => 'ana@pepe.test', 'receives_reminders' => '1'])->assertRedirect();
        $this->post("/clients/{$client->id}/plans", $this->planData())->assertRedirect();

        $plan = PaymentPlan::first();
        $this->assertSame(Cycle::Monthly, $plan->cycle);
        $due = PaymentDue::first();
        $this->assertSame('2026-11-20', $due->due_date->toDateString());

        $this->get('/')->assertOk()->assertSee('Hosting')->assertDontSee('<b>Don Pepe</b>', false);
        $this->get('/clients')->assertOk();
        $this->get('/clients?q=pepe')->assertOk()->assertSee('Don Pepe');
        $this->get('/clients/create')->assertOk();
        $this->get("/clients/{$client->id}")->assertOk()->assertSee('ana@pepe.test');
        $this->get("/clients/{$client->id}/edit")->assertOk();
        $this->get("/clients/{$client->id}/plans/create")->assertOk();
        $this->get("/clients/{$client->id}/plans/{$plan->id}/edit")->assertOk();
        $this->get('/cobros')->assertOk()->assertSee('$1,500.50 MXN');
        foreach (['vencidos', 'pagados', 'cancelados'] as $status) {
            $this->get("/cobros?estado=$status")->assertOk();
        }
        $this->get('/historial')->assertOk();
        $this->get('/dias-inhabiles')->assertOk()->assertSee('Día de la Revolución');
        $this->get("/cobros/{$due->id}/vista-previa")->assertOk()->assertSee('Hosting')->assertSee('viernes 20 de noviembre de 2026');
    }

    public function test_plan_cannot_start_in_the_past_or_with_bad_data(): void
    {
        $this->actingAs($this->user);
        $client = $this->client();

        $this->post("/clients/{$client->id}/plans", $this->planData(['next_date' => '2026-11-05']))->assertSessionHasErrors('next_date');
        $this->post("/clients/{$client->id}/plans", $this->planData(['amount' => '0']))->assertSessionHasErrors('amount');
        $this->post("/clients/{$client->id}/plans", $this->planData(['cycle' => 'nope']))->assertSessionHasErrors('cycle');
        $this->post("/clients/{$client->id}/contacts", ['name' => 'X', 'email' => 'no-es-correo'])->assertSessionHasErrors('email');
        $this->post("/clients/{$client->id}/contacts", ['name' => 'X', 'email' => 'dueña@acme.test'])->assertSessionHasErrors('email');

        $this->assertSame(0, PaymentPlan::count());
    }

    public function test_editing_amount_keeps_anchor_day_31_and_updates_pending_dues(): void
    {
        $this->actingAs($this->user);
        $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00:00'));
        $client = $this->client();
        $plan = $client->plans()->create([
            'concept' => 'Renta', 'amount' => 100, 'currency' => 'MXN', 'cycle' => Cycle::Monthly,
            'anchor_date' => '2027-01-31', 'active' => true,
        ]);

        $this->put("/clients/{$client->id}/plans/{$plan->id}", $this->planData([
            'concept' => 'Renta', 'amount' => '250', 'next_date' => '2027-02-28',
        ]))->assertRedirect();

        $plan->refresh();
        $this->assertSame('2027-01-31', $plan->anchor_date->toDateString());
        $this->assertTrue($plan->dues()->whereDate('due_date', '2027-03-31')->exists());
        $this->assertEquals(250, $plan->dues()->whereDate('due_date', '2027-02-28')->first()->amount);
    }

    public function test_rescheduling_replaces_future_dues_but_keeps_sent_mark_and_paid_history(): void
    {
        $this->actingAs($this->user);
        $client = $this->client();
        $this->post("/clients/{$client->id}/plans", $this->planData(['next_date' => '2026-11-10']));
        $plan = PaymentPlan::first();
        $plan->dues()->whereDate('due_date', '2026-11-10')->update(['reminder_sent_at' => now()]);

        // Cambio solo de monto: el recordatorio ya enviado se conserva.
        $this->put("/clients/{$client->id}/plans/{$plan->id}", $this->planData(['next_date' => '2026-11-10', 'amount' => '999']));
        $this->assertNotNull($plan->dues()->whereDate('due_date', '2026-11-10')->first()->reminder_sent_at);

        // Cambio de fecha: desaparece la fecha vieja y entra la nueva, sin recordatorio.
        $this->put("/clients/{$client->id}/plans/{$plan->id}", $this->planData(['next_date' => '2026-11-15']));
        $this->assertFalse($plan->dues()->whereDate('due_date', '2026-11-10')->exists());
        $this->assertNull($plan->dues()->whereDate('due_date', '2026-11-15')->first()->reminder_sent_at);
    }

    public function test_mark_paid_cancel_reopen_and_manual_send(): void
    {
        $this->actingAs($this->user);
        $client = $this->client();
        $this->post("/clients/{$client->id}/plans", $this->planData());
        $due = PaymentDue::first();

        $this->post("/cobros/{$due->id}/pagado")->assertRedirect();
        $this->assertSame(PaymentDue::PAID, $due->fresh()->status);
        $this->assertNotNull($due->fresh()->paid_at);

        $this->post("/cobros/{$due->id}/enviar")->assertSessionHas('error');
        Mail::assertNothingSent();

        $this->post("/cobros/{$due->id}/reabrir");
        $this->assertSame(PaymentDue::PENDING, $due->fresh()->status);
        $this->assertNull($due->fresh()->paid_at);

        $this->post("/cobros/{$due->id}/enviar")->assertSessionHas('success');
        Mail::assertSentCount(1);

        $this->post("/cobros/{$due->id}/cancelar");
        $this->assertSame(PaymentDue::CANCELLED, $due->fresh()->status);
    }

    public function test_contacts_and_plans_cannot_be_reached_through_another_client(): void
    {
        $this->actingAs($this->user);
        $mine = $this->client();
        $other = Client::create(['name' => 'Otro', 'active' => true]);
        $contact = $mine->contacts()->first();

        $this->delete("/clients/{$other->id}/contacts/{$contact->id}")->assertNotFound();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_deleting_client_keeps_email_history(): void
    {
        $this->actingAs($this->user);
        $client = $this->client();
        $this->post("/clients/{$client->id}/plans", $this->planData());
        $due = PaymentDue::first();
        $this->post("/cobros/{$due->id}/enviar");

        $this->delete("/clients/{$client->id}")->assertRedirect('/clients');

        $this->assertSame(0, PaymentDue::count());
        $this->get('/historial')->assertOk()->assertSee('Acme');
    }

    public function test_dashboard_warns_about_clients_without_contacts_and_failures(): void
    {
        $this->actingAs($this->user);
        $client = Client::create(['name' => 'Sin Contacto SA', 'active' => true]);
        $this->post("/clients/{$client->id}/plans", $this->planData(['next_date' => '2026-11-09']));

        $this->artisan('reminders:send');

        $this->get('/')->assertOk()
            ->assertSee('no tienen un contacto activo')
            ->assertSee('no se pudo enviar');
    }

    public function test_holiday_add_duplicate_and_remove(): void
    {
        $this->actingAs($this->user);

        $this->post('/dias-inhabiles', ['date' => '2026-12-24', 'name' => 'Cierre'])->assertSessionHasNoErrors();
        $this->post('/dias-inhabiles', ['date' => '2026-12-24', 'name' => 'Otra vez'])->assertSessionHasErrors('date');

        $holiday = Holiday::whereDate('date', '2026-12-24')->first();
        $this->delete("/dias-inhabiles/{$holiday->id}")->assertRedirect();
        $this->assertFalse(Holiday::whereDate('date', '2026-12-24')->exists());
    }
}
