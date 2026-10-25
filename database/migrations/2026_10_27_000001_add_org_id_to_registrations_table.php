<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Denormalised from events.org_id so the org scope filters registrations directly.
            $table->foreignId('org_id')->nullable()->after('id')->constrained('orgs')->cascadeOnDelete();
            $table->index(['org_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['org_id', 'status']);
            $table->dropConstrainedForeignId('org_id');
        });
    }
};
