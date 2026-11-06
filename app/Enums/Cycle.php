<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum Cycle: string
{
    case Once = 'once';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Bimonthly = 'bimonthly';
    case Quarterly = 'quarterly';
    case Semiannual = 'semiannual';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Pago único',
            self::Weekly => 'Semanal',
            self::Biweekly => 'Cada 2 semanas',
            self::Monthly => 'Mensual',
            self::Bimonthly => 'Bimestral',
            self::Quarterly => 'Trimestral',
            self::Semiannual => 'Semestral',
            self::Annual => 'Anual',
        };
    }

    /** Fecha de la ocurrencia número $n (0 = la fecha ancla). */
    public function occurrence(CarbonImmutable $anchor, int $n): CarbonImmutable
    {
        return match ($this) {
            self::Once => $anchor,
            self::Weekly => $anchor->addWeeks($n),
            self::Biweekly => $anchor->addWeeks($n * 2),
            self::Monthly => $anchor->addMonthsNoOverflow($n),
            self::Bimonthly => $anchor->addMonthsNoOverflow($n * 2),
            self::Quarterly => $anchor->addMonthsNoOverflow($n * 3),
            self::Semiannual => $anchor->addMonthsNoOverflow($n * 6),
            self::Annual => $anchor->addMonthsNoOverflow($n * 12),
        };
    }
}
