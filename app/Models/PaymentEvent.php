<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    protected $fillable = [
        'payment_id',
        'driver',
        'type',
        'idempotency_key',
        'signature_ok',
        'payload',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'signature_ok' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
