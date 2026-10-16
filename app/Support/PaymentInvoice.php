<?php

namespace App\Support;

class PaymentInvoice
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $address,
        public string $amountCrypto,
        public string $coin,
        public string $network,
        public string $qrPayload,
        public ?string $externalId = null,
        public array $meta = [],
    ) {}
}
