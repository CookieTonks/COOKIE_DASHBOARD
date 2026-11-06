<?php

namespace App\Services;

use App\Enums\Cycle;
use App\Models\PaymentPlan;
use Carbon\CarbonImmutable;

class DueGenerator
{
    /** Días hacia adelante para los que se crean cobros. */
    private const HORIZON_DAYS = 60;

    public function syncAll(): void
    {
        PaymentPlan::query()
            ->where('active', true)
            ->whereHas('client', fn ($client) => $client->where('active', true))
            ->each(fn (PaymentPlan $plan) => $this->syncPlan($plan));
    }

    /**
     * Crea los cobros futuros del plan. Nunca genera fechas pasadas, así reactivar
     * un plan pausado no llena la lista de vencidos. Siempre deja al menos el
     * siguiente cobro, aunque quede más allá del horizonte (planes anuales).
     */
    public function syncPlan(PaymentPlan $plan): void
    {
        $today = CarbonImmutable::today();
        $horizon = $today->addDays(self::HORIZON_DAYS);

        $existing = $plan->dues()->get(['due_date'])
            ->mapWithKeys(fn ($due) => [$due->due_date->toDateString() => true]);
        $hasUpcoming = false;

        for ($n = 0; $n < 10000; $n++) {
            $date = $plan->cycle->occurrence($plan->anchor_date, $n);

            if ($plan->ends_on && $date->gt($plan->ends_on)) {
                break;
            }
            if ($date->gte($today)) {
                if ($hasUpcoming && $date->gt($horizon)) {
                    break;
                }
                $hasUpcoming = true;

                if (! $existing->has($date->toDateString())) {
                    $plan->dues()->create([
                        'due_date' => $date,
                        'amount' => $plan->amount,
                        'currency' => $plan->currency,
                    ]);
                }
            }
            if ($plan->cycle === Cycle::Once) {
                break;
            }
        }
    }
}
