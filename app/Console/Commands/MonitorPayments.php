<?php

namespace App\Console\Commands;

use App\Services\PaymentMonitorService;
use Illuminate\Console\Command;

class MonitorPayments extends Command
{
    protected $signature = 'payments:monitor';

    protected $description = 'Check open crypto invoices and expire stale ones';

    public function handle(PaymentMonitorService $monitor): int
    {
        $updated = $monitor->tick();
        $this->info("Updated {$updated} payment(s).");

        return self::SUCCESS;
    }
}
