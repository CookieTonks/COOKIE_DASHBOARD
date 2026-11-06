<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReminderMail;
use App\Models\PaymentDue;
use App\Models\ReminderLog;
use App\Services\ReminderService;
use App\Support\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DueController extends Controller
{
    public function index(Request $request): View
    {
        $today = CarbonImmutable::today();
        $status = (string) $request->query('estado', 'pendientes');

        $query = PaymentDue::query()->with('plan.client');

        match ($status) {
            'vencidos' => $query->pending()->whereDate('due_date', '<', $today)->orderBy('due_date'),
            'pagados' => $query->where('status', PaymentDue::PAID)->orderByDesc('paid_at'),
            'cancelados' => $query->where('status', PaymentDue::CANCELLED)->orderByDesc('due_date'),
            default => $query->pending()->whereDate('due_date', '>=', $today)->orderBy('due_date'),
        };

        return view('dues.index', [
            'dues' => $query->paginate(50)->withQueryString(),
            'status' => $status,
            'calendar' => new BusinessCalendar,
        ]);
    }

    public function preview(PaymentDue $due): PaymentReminderMail
    {
        return new PaymentReminderMail($due->load('plan.client'));
    }

    public function markPaid(PaymentDue $due): RedirectResponse
    {
        $due->update(['status' => PaymentDue::PAID, 'paid_at' => now()]);

        return back()->with('success', 'Cobro marcado como pagado.');
    }

    public function cancel(PaymentDue $due): RedirectResponse
    {
        $due->update(['status' => PaymentDue::CANCELLED, 'paid_at' => null]);

        return back()->with('success', 'Cobro cancelado: no se enviará recordatorio.');
    }

    public function reopen(PaymentDue $due): RedirectResponse
    {
        $due->update(['status' => PaymentDue::PENDING, 'paid_at' => null]);

        return back()->with('success', 'Cobro reabierto.');
    }

    public function send(PaymentDue $due, ReminderService $reminders): RedirectResponse
    {
        if (config('mail.default') === 'log') {
            return back()->with('error', 'El SMTP no está configurado: no se envió nada.');
        }

        if ($due->status !== PaymentDue::PENDING) {
            return back()->with('error', 'Solo se puede enviar recordatorio de cobros pendientes.');
        }

        $log = $reminders->send($due, 'manual');

        return $log->status === ReminderLog::SENT
            ? back()->with('success', 'Recordatorio enviado a '.implode(', ', $log->recipients).'.')
            : back()->with('error', 'No se pudo enviar: '.$log->error);
    }
}
