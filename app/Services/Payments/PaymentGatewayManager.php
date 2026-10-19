<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\ShopSetting;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(?string $name = null): PaymentGateway
    {
        $name = $name ?: (string) ShopSetting::get('payment_driver', config('payments.driver', 'testnet'));

        return match ($name) {
            'nowpayments' => app(NowPaymentsGateway::class),
            'cryptomus' => app(CryptomusGateway::class),
            'testnet' => app(TestnetGateway::class),
            default => throw new InvalidArgumentException('Unknown payment driver: '.$name),
        };
    }
}
