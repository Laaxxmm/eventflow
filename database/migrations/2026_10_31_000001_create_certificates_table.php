<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One issued certificate per registration: its public EF-CERT number (printed
 * and verifiable), the stored PDF and a fingerprint of everything printed on
 * it, so a template, palette or name change re-issues the file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cert_no', 32)->unique();
            $table->string('template', 40)->nullable();
            $table->string('fingerprint', 64)->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
