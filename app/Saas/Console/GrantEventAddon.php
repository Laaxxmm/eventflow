<?php

namespace App\Saas\Console;

use App\Models\Event;
use App\Models\EventAddon;
use App\Models\Org;
use App\Models\Tenancy\OrgScope;
use App\Saas\Plans;
use Illuminate\Console\Command;

/**
 * For Indefine staff until online billing is live:
 *   php artisan plans:event-addon demo-runners bengaluru-city-marathon-2027 race_sheet --note="Invoice 42"
 */
final class GrantEventAddon extends Command
{
    protected $signature = 'plans:event-addon
        {org : Org slug}
        {event : Event slug}
        {addon : race_sheet | certificates}
        {--amount= : Amount paid in INR, default the list price}
        {--note= : Invoice or reference}
        {--revoke : Remove the add-on instead}';

    protected $description = 'Unlock a per-event add-on (race sheet ₹599, certificates ₹799) for one event.';

    public function handle(): int
    {
        $org = Org::where('slug', $this->argument('org'))->first();
        $event = $org ? Event::withoutGlobalScope(OrgScope::class)->where('org_id', $org->id)->where('slug', $this->argument('event'))->first() : null;
        if (! $event) {
            $this->error('No such org/event.');

            return self::FAILURE;
        }

        $key = (string) $this->argument('addon');
        $addon = Plans::eventAddons()[$key] ?? null;
        if ($addon === null) {
            $this->error("Unknown add-on [{$key}]. Add-ons: ".implode(', ', array_keys(Plans::eventAddons())).'.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            EventAddon::withoutGlobalScopes()->where('event_id', $event->id)->where('addon', $key)->delete();
            $this->info("{$addon['name']} removed from {$event->name}.");

            return self::SUCCESS;
        }

        $amount = $this->option('amount') ?? $addon['price_inr'];
        if (filter_var($amount, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            $this->error('--amount must be a whole number of rupees.');

            return self::FAILURE;
        }

        EventAddon::withoutGlobalScopes()->updateOrCreate(
            ['event_id' => $event->id, 'addon' => $key],
            ['org_id' => $org->id, 'amount_paise' => (int) $amount * 100, 'note' => $this->option('note')],
        );
        $this->info("{$addon['name']} unlocked for {$event->name}.");

        return self::SUCCESS;
    }
}
