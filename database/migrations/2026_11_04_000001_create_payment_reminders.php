<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Unpaid payment reminders (Session N2):
 *  - events.payment_reminders: per-event opt-in, cadence and copy (JSON, null = defaults, off);
 *  - registrations.reminders_opted_out_at: the participant's "stop reminders" click;
 *  - registration_reminder_logs: every reminder (automatic or manual, per channel), which is
 *    also the idempotency record (one row per registration, channel and dispatch key);
 *  - notification_settings.whatsapp_template_payment: approved WhatsApp template for reminders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('payment_reminders')->nullable();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->timestamp('reminders_opted_out_at')->nullable();
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->string('whatsapp_template_payment', 120)->nullable();
        });

        Schema::create('registration_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10); // auto | manual
            $table->unsignedTinyInteger('sequence')->nullable(); // 1..5 for automatic reminders
            $table->string('dispatch_key', 64); // auto-<n> or manual-<uuid>
            $table->string('channel', 10); // mail | whatsapp
            $table->string('template', 60)->default('payment_reminder');
            $table->string('status', 12)->default('queued'); // queued | sent | skipped | failed
            $table->string('detail', 255)->nullable();
            $table->string('provider_id', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['registration_id', 'channel', 'dispatch_key'], 'reminder_logs_once');
            $table->index(['registration_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_reminder_logs');
        Schema::table('notification_settings', fn (Blueprint $table) => $table->dropColumn('whatsapp_template_payment'));
        Schema::table('registrations', fn (Blueprint $table) => $table->dropColumn('reminders_opted_out_at'));
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('payment_reminders'));
    }
};
