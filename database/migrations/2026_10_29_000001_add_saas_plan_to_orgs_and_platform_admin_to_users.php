<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->string('plan', 40)->nullable()->after('modules'); // key of config('plans.plans')
            $table->json('addons')->nullable()->after('plan'); // keys of config('plans.addons')
            // trialing|active|past_due|cancelled (billing connects when SaaS subscriptions go live)
            $table->string('subscription_status', 20)->default('trialing')->after('addons');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('suspended_at')->nullable()->after('trial_ends_at'); // set by Indefine platform admins
            $table->index(['subscription_status', 'trial_ends_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Indefine staff: platform console at /platform. Independent of org roles.
            $table->boolean('is_platform_admin')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
        });

        Schema::table('orgs', function (Blueprint $table) {
            $table->dropIndex(['subscription_status', 'trial_ends_at']);
            $table->dropColumn(['plan', 'addons', 'subscription_status', 'trial_ends_at', 'suspended_at']);
        });
    }
};
