<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Participant packs (extra places for one race, granted by Indefine) and the move from
 * the placeholder plans to the locked ones: starter -> single_event, growth -> club_season,
 * scale -> custom. Add-on custom_smtp is dropped (own SMTP is now free on every plan).
 */
return new class extends Migration
{
    private const PLAN_MAP = ['starter' => 'single_event', 'growth' => 'club_season', 'scale' => 'custom'];

    public function up(): void
    {
        Schema::create('participant_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('participants');
            $table->unsignedInteger('amount_paise')->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['event_id']);
        });

        foreach (self::PLAN_MAP as $old => $new) {
            DB::table('orgs')->where('plan', $old)->update(['plan' => $new]);
        }

        DB::table('orgs')->whereNotNull('addons')->orderBy('id')->each(function (object $org) {
            $addons = json_decode((string) $org->addons, true);
            if (! is_array($addons)) {
                return;
            }
            $kept = array_values(array_filter($addons, fn ($a) => in_array($a, ['platform_mail', 'whatsapp'], true)));
            if ($kept !== $addons) {
                DB::table('orgs')->where('id', $org->id)->update(['addons' => json_encode($kept)]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_packs');

        foreach (self::PLAN_MAP as $old => $new) {
            DB::table('orgs')->where('plan', $new)->update(['plan' => $old]);
        }
    }
};
