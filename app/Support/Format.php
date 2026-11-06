<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class Format
{
    public static function money(float|string $amount, string $currency): string
    {
        return '$'.number_format((float) $amount, 2).' '.$currency;
    }

    public static function date(?CarbonInterface $date): string
    {
        return $date ? $date->translatedFormat('D j M Y') : '—';
    }

    /** Total de cobros por moneda; nunca se suman pesos con dólares. */
    public static function totals(Collection $dues): string
    {
        if ($dues->isEmpty()) {
            return '$0.00';
        }

        return $dues->groupBy('currency')
            ->map(fn (Collection $group, string $currency) => self::money($group->sum('amount'), $currency))
            ->sortKeysDesc()
            ->implode(' + ');
    }
}
