<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Per-event add-ons (config('plans.event_addons')): race_sheet (₹599), certificates (₹799).
 * One row per event and add-on; granted by Indefine (php artisan plans:event-addon) until
 * online billing is live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('addon', 40); // key of config('plans.event_addons')
            $table->unsignedInteger('amount_paise')->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'addon']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_addons');
    }
};
