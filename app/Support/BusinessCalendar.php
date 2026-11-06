<?php

namespace App\Support;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class BusinessCalendar
{
    /** @var array<string, true>|null */
    private ?array $holidays = null;

    public function isBusinessDay(CarbonInterface $date): bool
    {
        return ! $date->isWeekend() && ! isset($this->holidays()[$date->toDateString()]);
    }

    /** Retrocede $days días hábiles: el vencimiento no cuenta como uno de ellos. */
    public function subBusinessDays(CarbonInterface $date, int $days): CarbonImmutable
    {
        $current = CarbonImmutable::parse($date->toDateString());

        while ($days > 0) {
            $current = $current->subDay();
            if ($this->isBusinessDay($current)) {
                $days--;
            }
        }

        return $current;
    }

    public function reminderDate(CarbonInterface $dueDate): CarbonImmutable
    {
        return $this->subBusinessDays($dueDate, (int) config('reminders.business_days_before'));
    }

    /** @return array<string, true> */
    private function holidays(): array
    {
        return $this->holidays ??= Holiday::query()
            ->get(['date'])
            ->mapWithKeys(fn (Holiday $holiday) => [$holiday->date->toDateString() => true])
            ->all();
    }
}
