<?php

namespace App\Http\Controllers;

use App\Enums\Cycle;
use App\Models\Client;
use App\Models\PaymentPlan;
use App\Services\DueGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function create(Client $client): View
    {
        $plan = new PaymentPlan(['currency' => 'MXN', 'cycle' => Cycle::Monthly, 'active' => true]);

        return view('plans.form', ['client' => $client, 'plan' => $plan, 'nextDate' => null]);
    }

    public function store(Request $request, Client $client, DueGenerator $generator): RedirectResponse
    {
        $data = $this->validated($request);
        $nextDate = CarbonImmutable::parse($data['next_date']);
        $this->assertNotPast($nextDate);

        $plan = $client->plans()->create($this->attributes($data, $request) + ['anchor_date' => $nextDate]);

        if ($plan->active && $client->active) {
            $generator->syncPlan($plan);
        }

        return redirect()->route('clients.show', $client)->with('success', 'Plan de pago creado.');
    }

    public function edit(Client $client, PaymentPlan $plan): View
    {
        return view('plans.form', ['client' => $client, 'plan' => $plan, 'nextDate' => $plan->nextDate()]);
    }

    public function update(Request $request, Client $client, PaymentPlan $plan, DueGenerator $generator): RedirectResponse
    {
        $data = $this->validated($request);
        $submitted = CarbonImmutable::parse($data['next_date']);
        $currentNext = $plan->nextDate();

        // La fecha ancla solo se mueve si cambió el calendario. Así, editar el monto de
        // un plan del día 31 cuando su próxima fecha es 28 de febrero conserva el 31.
        $rescheduled = $data['cycle'] !== $plan->cycle->value
            || ! $currentNext
            || ! $submitted->eq($currentNext);

        if ($rescheduled) {
            $this->assertNotPast($submitted);
        }

        DB::transaction(function () use ($plan, $client, $data, $request, $submitted, $rescheduled, $generator) {
            $plan->fill($this->attributes($data, $request));
            if ($rescheduled) {
                $plan->anchor_date = $submitted;
            }
            $plan->save();

            // Se rehacen los cobros futuros; los recordatorios ya enviados se conservan
            // para no volver a escribirle al cliente por una fecha que no cambió.
            $today = CarbonImmutable::today();
            $future = $plan->dues()->pending()->whereDate('due_date', '>=', $today);
            $alreadySent = (clone $future)->whereNotNull('reminder_sent_at')->get(['due_date', 'reminder_sent_at']);
            (clone $future)->delete();

            if ($plan->active && $client->active) {
                $generator->syncPlan($plan);
            }

            foreach ($alreadySent as $due) {
                $plan->dues()->pending()->whereDate('due_date', $due->due_date)
                    ->update(['reminder_sent_at' => $due->reminder_sent_at]);
            }
        });

        return redirect()->route('clients.show', $client)->with('success', 'Plan de pago actualizado.');
    }

    public function destroy(Client $client, PaymentPlan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('clients.show', $client)->with('success', 'Plan de pago eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'currency' => ['required', Rule::in(['MXN', 'USD'])],
            'cycle' => ['required', Rule::enum(Cycle::class)],
            'next_date' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:next_date'],
        ]);
    }

    private function attributes(array $data, Request $request): array
    {
        return [
            'concept' => $data['concept'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'cycle' => $data['cycle'],
            'ends_on' => $data['ends_on'] ?? null,
            'active' => $request->boolean('active'),
        ];
    }

    private function assertNotPast(CarbonImmutable $date): void
    {
        if ($date->lt(CarbonImmutable::today())) {
            throw ValidationException::withMessages([
                'next_date' => 'La próxima fecha de pago no puede estar en el pasado.',
            ]);
        }
    }
}
