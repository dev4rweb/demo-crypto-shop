<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('delivery_type', 16)->default('digital');
            $table->string('status', 32)->default('awaiting_payment');
            $table->string('fulfillment_status', 32)->default('pending');
            $table->unsignedInteger('subtotal_usd_cents');
            $table->unsignedInteger('discount_usd_cents')->default(0);
            $table->unsignedInteger('total_usd_cents');
            $table->string('locale', 8)->default('en');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_usd_cents');
            $table->json('delivery_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('driver', 32);
            $table->string('coin', 16);
            $table->string('network')->nullable();
            $table->string('address');
            $table->string('amount_crypto', 48);
            $table->string('amount_received', 48)->nullable();
            $table->unsignedInteger('amount_usd_cents');
            $table->decimal('rate_used', 18, 8);
            $table->string('status', 32)->default('waiting');
            $table->string('txid')->nullable();
            $table->unsignedInteger('confirmations')->default(0);
            $table->unsignedInteger('required_confirmations')->default(1);
            $table->string('qr_payload');
            $table->string('external_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('simulation_started_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('driver', 32)->nullable();
            $table->string('type', 64);
            $table->string('idempotency_key')->unique();
            $table->boolean('signature_ok')->default(false);
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
