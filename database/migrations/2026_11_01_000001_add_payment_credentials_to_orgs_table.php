<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organisers connect their own Cashfree / Razorpay account from the dashboard.
 * Keys are stored encrypted (APP_KEY) as {provider: {...keys, mode, verified_at}}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->text('payment_credentials')->nullable()->after('payment_provider');
        });
    }

    public function down(): void
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->dropColumn('payment_credentials');
        });
    }
};
