# Nexus — crypto-payment shop demo

Portfolio demo by **Vladislav**, full-stack Laravel + Vue developer.

Live: https://demo-crypto-shop.dev4rweb.com  
Repo: https://github.com/dev4rweb/demo-crypto-shop

Digital storefront for templates, licenses, courses and gift cards. Checkout in USDT, BTC or ETH with a live invoice, QR, countdown and instant file delivery. Operator terminal for orders, revenue and catalog.

## Stack

Laravel 12 (PHP 8.3), Vue 3, Inertia, Tailwind CSS, Pinia, MySQL/MariaDB (SQLite for tests), Pest. Payment drivers: testnet monitor, NOWPayments sandbox, Cryptomus sandbox.

## Demo logins

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@example.com | demo123456 |
| Manager | manager@example.com | demo123456 |
| Buyer | buyer@example.com | demo123456 |

Promo codes: `NEXUS10` (10%), `WELCOME5` ($5). On the payment screen use **Simulate payment** (marked as demo) to walk the full status path.

## Local setup

```bash
cp .env.example .env
# sqlite is fine locally:
# DB_CONNECTION=sqlite
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=DemoSeeder --force
php artisan storage:link
npm ci --ignore-scripts
npm run build
php artisan serve
```

`DemoSeeder` is idempotent and is **not** called from `DatabaseSeeder`.

## Hostinger deploy

Document root is the **project root**. Root `.htaccess` routes into `public/`. `public/build` is committed (the host cannot run Vite).

PHP 8.3 binary: `/opt/alt/php83/usr/bin/php`

Never put the database password in the repo. Fill `DB_USERNAME`, `DB_PASSWORD`, `MAIL_PASSWORD` and optional payment/Telegram keys in `.env` on the server.

```bash
cd /home/USER/domains/demo-crypto-shop.dev4rweb.com
/opt/alt/php83/usr/bin/php artisan key:generate --force
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan db:seed --class=DemoSeeder --force
/opt/alt/php83/usr/bin/php artisan storage:link
/opt/alt/php83/usr/bin/php artisan config:cache
/opt/alt/php83/usr/bin/php artisan route:cache
/opt/alt/php83/usr/bin/php artisan view:cache
```

Cron (once per minute):

```
* * * * * /opt/alt/php83/usr/bin/php /home/USER/domains/demo-crypto-shop.dev4rweb.com/artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs `payments:monitor` every minute and, when `QUEUE_CONNECTION=database`, `queue:work --stop-when-empty`.

Mail uses Hostinger SMTP (`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.hostinger.com`, `MAIL_PORT=465`, `MAIL_SCHEME=smtps`, from `noreply@dev4rweb.com`).

Telegram is one platform bot. Empty `TELEGRAM_BOT_TOKEN` / `TELEGRAM_BOT_USERNAME` hides Connect Telegram. After deploy:

```bash
/opt/alt/php83/usr/bin/php artisan telegram:set-webhook
```

Webhook: `https://demo-crypto-shop.dev4rweb.com/telegram/webhook` (header `X-Telegram-Bot-Api-Secret-Token`).

Payment driver: `PAYMENT_DRIVER=testnet|nowpayments|cryptomus`. Provider keys stay in `.env`.

## Tests

```bash
php artisan test
```
