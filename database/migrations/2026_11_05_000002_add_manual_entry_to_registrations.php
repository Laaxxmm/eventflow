<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Session P1: participants the organiser adds by hand (Registrations → Add participant).
 * Same registrations row as an online entry: paid / unpaid filters, passes and payment
 * reminders work the same. Email and phone become optional for hand-added entries.
 *   source          online | manual
 *   payment_method  null (online gateway) | manual (paid offline) | complimentary
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('source', 20)->default('online')->after('status');
            $table->string('payment_method', 20)->nullable()->after('gateway_payment_id');
            $table->text('notes')->nullable()->after('answers');
            $table->foreignId('added_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('added_by');
            $table->dropColumn(['source', 'payment_method', 'notes']);
        });
    }
};
