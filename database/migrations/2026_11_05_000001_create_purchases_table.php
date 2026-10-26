<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Session P1: organiser purchases from Indefine (participant packs, per-event add-ons such as
 * the ₹599 custom domain). Paid online through the platform PaymentGateway account, or
 * recorded as a request when the item has no online price yet. App\Billing\Fulfilment applies
 * a paid purchase exactly once (participant_packs / event_addons row).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 40); // participant_pack | key of config('plans.event_addons')
            $table->unsignedInteger('quantity')->default(1); // participants, for a participant pack
            $table->unsignedInteger('amount_paise')->nullable(); // null = to be quoted
            $table->string('status', 20)->default('pending'); // requested|pending|paid|failed
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_order_id']);
            $table->index(['org_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
