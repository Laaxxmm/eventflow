<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Org Settings -> Notifications. Secrets are encrypted at rest (Laravel `encrypted` cast).
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->unique()->constrained('orgs')->cascadeOnDelete();

            // Platform email (Brevo/SES via MAIL_*): from-name = brand, reply-to = organiser.
            $table->string('from_name', 120)->nullable();
            $table->string('reply_to_email')->nullable();
            $table->boolean('send_reminders')->default(true);
            $table->boolean('send_checkin_confirmation')->default(false);

            // Custom SMTP (module email_branding): used only when enabled and verified.
            $table->boolean('smtp_enabled')->default(false);
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 10)->nullable(); // tls|ssl|none
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable(); // encrypted
            $table->string('smtp_from_email')->nullable();
            $table->string('smtp_from_name', 120)->nullable();
            $table->timestamp('smtp_verified_at')->nullable();

            // WhatsApp (module sms_whatsapp).
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_provider', 20)->default('meta');
            $table->string('whatsapp_phone_number_id', 64)->nullable();
            $table->text('whatsapp_token')->nullable(); // encrypted
            $table->string('whatsapp_template_confirmed', 120)->nullable();
            $table->string('whatsapp_template_reminder', 120)->nullable();
            $table->string('whatsapp_language', 10)->default('en');
            $table->timestamp('whatsapp_verified_at')->nullable();

            $table->timestamps();
        });

        // One row per send attempt outcome, for the organiser's "Email delivery" status.
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->nullable()->constrained('orgs')->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('registrations')->cascadeOnDelete();
            $table->string('channel', 20); // mail|whatsapp
            $table->string('type', 40); // registration_confirmed|event_reminder|checkin_confirmed|test
            $table->string('recipient');
            $table->string('status', 20); // queued|sent|failed|skipped
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['registration_id', 'channel', 'type']);
            $table->index(['org_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_settings');
    }
};
