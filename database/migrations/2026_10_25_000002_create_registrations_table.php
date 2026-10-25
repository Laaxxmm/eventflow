<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('pass_no', 15)->unique(); // EF- + 12 uppercase hex
            $table->string('status', 20)->default('pending'); // App\Payments\PaymentStatus
            $table->string('participant_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('distance_code', 20)->nullable();
            $table->unsignedInteger('amount_paise')->default(0);
            $table->string('gateway', 20)->nullable(); // razorpay|cashfree
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->json('answers')->nullable(); // {field_key: value} for show/hide + custom fields
            $table->string('pass_png_path')->nullable();
            $table->string('pass_pdf_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_order_id']);
            $table->index(['event_id', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
