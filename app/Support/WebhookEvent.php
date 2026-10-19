<?php

namespace App\Support;

class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $idempotencyKey,
        public string $status,
        public ?string $externalId,
        public ?string $txid,
        public ?string $amountReceived,
        public int $confirmations = 0,
        public array $payload = [],
    ) {}
}
