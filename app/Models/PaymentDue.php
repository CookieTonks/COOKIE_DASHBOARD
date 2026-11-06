<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentDue extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['due_date', 'amount', 'currency', 'status', 'paid_at', 'reminder_sent_at'];

    protected $casts = [
        'due_date' => 'immutable_date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    /** Solo cobros de planes y clientes activos. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereHas('plan', fn (Builder $plan) => $plan
            ->where('active', true)
            ->whereHas('client', fn (Builder $client) => $client->where('active', true)));
    }

    public function isOverdue(): bool
    {
        return $this->status === self::PENDING && $this->due_date->lt(CarbonImmutable::today());
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) {
            return 'Vencido';
        }

        return match ($this->status) {
            self::PAID => 'Pagado',
            self::CANCELLED => 'Cancelado',
            default => 'Pendiente',
        };
    }

    public function statusKey(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }
}
