<?php

namespace App\Models;

use App\Enums\Cycle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPlan extends Model
{
    protected $fillable = ['concept', 'amount', 'currency', 'cycle', 'anchor_date', 'ends_on', 'active'];

    protected $casts = [
        'cycle' => Cycle::class,
        'amount' => 'decimal:2',
        'anchor_date' => 'immutable_date',
        'ends_on' => 'immutable_date',
        'active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function dues(): HasMany
    {
        return $this->hasMany(PaymentDue::class);
    }

    /** Próxima fecha del ciclo a partir de hoy, o null si el plan ya terminó. */
    public function nextDate(): ?CarbonImmutable
    {
        $today = CarbonImmutable::today();

        for ($n = 0; $n < 10000; $n++) {
            $date = $this->cycle->occurrence($this->anchor_date, $n);

            if ($this->ends_on && $date->gt($this->ends_on)) {
                return null;
            }
            if ($date->gte($today)) {
                return $date;
            }
            if ($this->cycle === Cycle::Once) {
                return null;
            }
        }

        return null;
    }
}
