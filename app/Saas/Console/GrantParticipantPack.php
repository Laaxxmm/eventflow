<?php

namespace App\Saas\Console;

use App\Models\Event;
use App\Models\Org;
use App\Models\ParticipantPack;
use App\Models\Tenancy\OrgScope;
use App\Saas\PlanLimits;
use Illuminate\Console\Command;

/**
 * For Indefine staff: raise one race's participant cap.
 *   php artisan plans:pack demo-runners bengaluru-city-marathon-2027 250 --amount=0 --note="Invoice 123"
 */
final class GrantParticipantPack extends Command
{
    protected $signature = 'plans:pack
        {org : Org slug}
        {event : Event slug}
        {participants : Extra places for this race}
        {--amount=0 : Amount paid in INR, for the record}
        {--note= : Invoice or reference}';

    protected $description = 'Add a participant pack to one race (lifts the plan soft-block).';

    public function handle(): int
    {
        $org = Org::where('slug', $this->argument('org'))->first();
        $event = $org ? Event::withoutGlobalScope(OrgScope::class)->where('org_id', $org->id)->where('slug', $this->argument('event'))->first() : null;
        if (! $event) {
            $this->error('No such org/event.');

            return self::FAILURE;
        }

        $participants = filter_var($this->argument('participants'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
        $amount = filter_var($this->option('amount'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($participants === false || $amount === false) {
            $this->error('participants must be 1..100000 and --amount a whole number of rupees.');

            return self::FAILURE;
        }

        ParticipantPack::withoutGlobalScopes()->create([
            'org_id' => $org->id,
            'event_id' => $event->id,
            'participants' => $participants,
            'amount_paise' => $amount * 100,
            'note' => $this->option('note'),
        ]);

        $cap = PlanLimits::participantCap($event);
        $this->info(sprintf('%s: +%d places. Cap is now %s.', $event->name, $participants, $cap === null ? 'not limited by the plan' : number_format($cap)));

        return self::SUCCESS;
    }
}
