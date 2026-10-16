<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'driver',
        'coin',
        'network',
        'address',
        'amount_crypto',
        'amount_received',
        'amount_usd_cents',
        'rate_used',
        'status',
        'txid',
        'confirmations',
        'required_confirmations',
        'qr_payload',
        'external_id',
        'expires_at',
        'detected_at',
        'confirmed_at',
        'simulation_started_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'detected_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'simulation_started_at' => 'datetime',
            'meta' => 'array',
            'rate_used' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['waiting', 'detected', 'confirming', 'underpaid'], true);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->isOpen() && $this->expires_at?->isPast());
    }
}
