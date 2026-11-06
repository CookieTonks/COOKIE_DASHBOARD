<?php

namespace App\Services;

use App\Mail\PaymentReminderMail;
use App\Models\PaymentDue;
use App\Models\ReminderLog;
use App\Support\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ReminderService
{
    /**
     * Cobros que ya deberían haber recibido recordatorio y siguen sin él.
     * Usa "<= hoy" en vez de "= hoy" para recuperar envíos fallidos, días en que
     * el servidor estuvo caído y cobros dados de alta con poca anticipación.
     *
     * @return Collection<int, PaymentDue>
     */
    public function dueToday(): Collection
    {
        $today = CarbonImmutable::today();
        $calendar = new BusinessCalendar;

        return PaymentDue::query()
            ->pending()
            ->live()
            ->whereNull('reminder_sent_at')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->addDays(30))
            ->with('plan.client.contacts')
            ->orderBy('due_date')
            ->get()
            ->filter(fn (PaymentDue $due) => $calendar->reminderDate($due->due_date)->lte($today))
            ->values();
    }

    public function send(PaymentDue $due, string $trigger): ReminderLog
    {
        $due->loadMissing('plan.client.contacts');
        $client = $due->plan->client;
        $recipients = $client->contacts
            ->where('receives_reminders', true)
            ->pluck('email')
            ->unique()
            ->values()
            ->all();

        $error = null;

        if ($recipients === []) {
            $error = 'El cliente no tiene contactos que reciban recordatorios.';
        } else {
            try {
                $mail = Mail::to($recipients);

                if ($bcc = config('reminders.bcc')) {
                    $mail->bcc($bcc);
                }

                $mail->send(new PaymentReminderMail($due));
                $due->update(['reminder_sent_at' => now()]);
            } catch (Throwable $e) {
                Log::error('Fallo al enviar recordatorio', ['due' => $due->id, 'error' => $e->getMessage()]);
                $error = Str::limit($e->getMessage(), 500);
            }
        }

        return ReminderLog::create([
            'payment_due_id' => $due->id,
            'client_name' => $client->name,
            'concept' => $due->plan->concept,
            'due_date' => $due->due_date,
            'amount' => $due->amount,
            'currency' => $due->currency,
            'recipients' => $recipients,
            'trigger' => $trigger,
            'status' => $error ? ReminderLog::FAILED : ReminderLog::SENT,
            'error' => $error,
        ]);
    }
}
