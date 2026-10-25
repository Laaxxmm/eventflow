<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orgs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name');
            $table->json('colours')->nullable(); // {primary, secondary, accent, background}
            $table->string('payment_provider', 20)->nullable(); // null = config('eventflow.payment.provider')
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orgs');
    }
};
