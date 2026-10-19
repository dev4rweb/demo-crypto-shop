<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url?}';

    protected $description = 'Register the Telegram webhook URL';

    public function handle(TelegramService $telegram): int
    {
        $result = $telegram->setWebhook($this->argument('url'));

        if ($result === null) {
            $this->warn('Telegram is not configured.');

            return self::SUCCESS;
        }

        $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');

        return self::SUCCESS;
    }
}
