<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
            $table->string('slug', 80); // auto-generated from name, unique per org
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->string('venue');
            $table->unsignedInteger('price_paise')->default(0); // base price; per-distance prices live in distances
            $table->unsignedInteger('capacity')->nullable(); // null = unlimited
            $table->json('distances')->nullable(); // [{code, label, price_inr, capacity}]
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->json('colours')->nullable(); // {primary, secondary, accent, background}
            $table->json('form_fields')->nullable(); // {show: {age, gender, city, ...: bool}, custom: [{key, label, type, required, options}]}
            $table->string('status', 20)->default('draft'); // draft|published|closed
            $table->json('config')->nullable(); // full events/schema.json document
            $table->timestamps();

            $table->unique(['org_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
