<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PaymentDue;
use App\Models\ReminderLog;
use App\Support\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = CarbonImmutable::today();

        $overdue = PaymentDue::query()->pending()->live()
            ->whereDate('due_date', '<', $today)
            ->with('plan.client')
            ->orderBy('due_date')
            ->get();

        $upcoming = PaymentDue::query()->pending()->live()
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->addDays(30))
            ->with('plan.client')
            ->orderBy('due_date')
            ->get();

        $clientsWithoutContacts = Client::query()->active()
            ->whereHas('plans', fn ($plan) => $plan->where('active', true))
            ->whereDoesntHave('contacts', fn ($contact) => $contact->where('receives_reminders', true))
            ->orderBy('name')
            ->get();

        // Cobros cuyo último intento de envío falló y siguen sin recordatorio.
        $failed = $upcoming->whereNull('reminder_sent_at')
            ->load(['logs' => fn ($logs) => $logs->latest('id')])
            ->filter(fn (PaymentDue $due) => $due->logs->first()?->status === ReminderLog::FAILED);

        return view('dashboard', [
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'failed' => $failed,
            'clientsWithoutContacts' => $clientsWithoutContacts,
            'activeClients' => Client::query()->active()->count(),
            'sentThisMonth' => ReminderLog::query()
                ->where('status', ReminderLog::SENT)
                ->where('created_at', '>=', $today->startOfMonth())
                ->count(),
            'calendar' => new BusinessCalendar,
        ]);
    }
}
