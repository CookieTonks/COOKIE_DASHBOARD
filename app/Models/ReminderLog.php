<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    public const SENT = 'sent';

    public const FAILED = 'failed';

    protected $fillable = [
        'payment_due_id', 'client_name', 'concept', 'due_date', 'amount',
        'currency', 'recipients', 'trigger', 'status', 'error',
    ];

    protected $casts = [
        'due_date' => 'immutable_date',
        'amount' => 'decimal:2',
        'recipients' => 'array',
    ];

    public function due(): BelongsTo
    {
        return $this->belongsTo(PaymentDue::class, 'payment_due_id');
    }
}
