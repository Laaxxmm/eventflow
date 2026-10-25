<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
            $table->string('plan', 40); // plan label, e.g. "Season 2027"
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('pending'); // pending|active|expired|cancelled
            $table->json('modules'); // App\Enums\Module values included in this pass
            $table->unsignedInteger('max_events')->nullable(); // null = unlimited
            $table->unsignedInteger('max_registrations')->nullable(); // null = unlimited
            $table->unsignedInteger('amount_paise')->default(0);
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->timestamps();

            $table->index(['org_id', 'status', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_passes');
    }
};
