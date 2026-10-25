<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Denormalised from events.config (template, certificate.template, sport pack defaults applied).
            $table->string('template_id', 40)->default('aurora')->after('sport'); // config('eventflow.landing_templates')
            $table->string('certificate_template_id', 40)->nullable()->after('template_id'); // gallery key or "upload"
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'certificate_template_id']);
        });
    }
};
