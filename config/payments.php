<?php

return [

    'driver' => env('PAYMENT_DRIVER', 'testnet'),

    'coins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PAYMENT_COINS', 'USDT,BTC,ETH'))
    ))),

    'invoice_ttl_minutes' => (int) env('PAYMENT_INVOICE_TTL', 30),

    'underpayment_tolerance_percent' => (float) env('PAYMENT_UNDERPAY_TOLERANCE', 1),

    'required_confirmations' => [
        'USDT' => 3,
        'BTC' => 2,
        'ETH' => 3,
    ],

    'decimals' => [
        'USDT' => 6,
        'BTC' => 8,
        'ETH' => 8,
    ],

    'networks' => [
        'USDT' => 'Tron Nile (test USDT)',
        'BTC' => 'Bitcoin testnet',
        'ETH' => 'Ethereum Sepolia',
    ],

    'wallets' => [
        'USDT' => env('PAYMENT_USDT_ADDRESS', 'TNexusDemoUsdtNileAddress111111'),
        'BTC' => env('PAYMENT_BTC_ADDRESS', 'tb1qnexusdemodemocryptoshop00000000'),
        'ETH' => env('PAYMENT_ETH_ADDRESS', '0xNexusDemoSepolia0000000000000000000001'),
    ],

    'fallback_rates' => [
        'USDT' => 1.0,
        'BTC' => 97000.0,
        'ETH' => 3520.0,
    ],

    'coingecko_ids' => [
        'USDT' => 'tether',
        'BTC' => 'bitcoin',
        'ETH' => 'ethereum',
    ],

    'nowpayments' => [
        'api_key' => env('NOWPAYMENTS_API_KEY'),
        'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
        'sandbox' => filter_var(env('NOWPAYMENTS_SANDBOX', true), FILTER_VALIDATE_BOOL),
    ],

    'cryptomus' => [
        'merchant_id' => env('CRYPTOMUS_MERCHANT_ID'),
        'api_key' => env('CRYPTOMUS_API_KEY'),
    ],

];
