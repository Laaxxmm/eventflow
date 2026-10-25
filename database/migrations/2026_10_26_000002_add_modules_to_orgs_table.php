<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->json('modules')->nullable()->after('payment_provider'); // Org Settings toggles: {module: bool}
        });
    }

    public function down(): void
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->dropColumn('modules');
        });
    }
};
