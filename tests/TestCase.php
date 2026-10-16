<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.coingecko.com/*' => Http::response([
                'tether' => ['usd' => 1],
                'bitcoin' => ['usd' => 97000],
                'ethereum' => ['usd' => 3520],
            ], 200),
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }
}
