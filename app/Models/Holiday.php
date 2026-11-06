<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name'];

    protected $casts = ['date' => 'immutable_date'];

    /** Días de descanso obligatorio en México (LFT, art. 74). */
    public static function mexicanHolidays(int $year): array
    {
        $days = [
            "$year-01-01" => 'Año Nuevo',
            CarbonImmutable::parse("first monday of february $year")->toDateString() => 'Día de la Constitución',
            CarbonImmutable::parse("third monday of march $year")->toDateString() => 'Natalicio de Benito Juárez',
            "$year-05-01" => 'Día del Trabajo',
            "$year-09-16" => 'Día de la Independencia',
            CarbonImmutable::parse("third monday of november $year")->toDateString() => 'Día de la Revolución',
            "$year-12-25" => 'Navidad',
        ];

        if (($year - 2024) % 6 === 0) {
            $days["$year-10-01"] = 'Transmisión del Poder Ejecutivo';
        }

        return $days;
    }

    /**
     * Carga los feriados de un año solo si ese año no tiene ninguno,
     * para no revivir los que hayas borrado a mano.
     */
    public static function ensureYear(int $year): void
    {
        if (self::whereYear('date', $year)->exists()) {
            return;
        }

        foreach (self::mexicanHolidays($year) as $date => $name) {
            self::create(['date' => $date, 'name' => $name]);
        }
    }
}
