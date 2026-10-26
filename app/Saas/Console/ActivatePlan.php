<?php

namespace App\Saas\Console;

use App\Models\Org;
use App\Saas\PlanAssigner;
use App\Saas\Plans;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * For Indefine staff until online plan billing is live:
 *   php artisan plans:activate demo-runners club_season --addon=whatsapp
 */
final class ActivatePlan extends Command
{
    protected $signature = 'plans:activate
        {org : Org slug}
        {plan : single_event | club_season | circuit_season | custom}
        {--from= : Start date (YYYY-MM-DD), default today (IST)}
        {--addon=* : Paid add-ons to include (platform_mail, whatsapp); default keeps the current ones}
        {--no-addons : Remove every add-on}';

    protected $description = 'Start a paid plan term: Single Event 90 days, Club / Circuit Season 12 months.';

    public function handle(): int
    {
        $org = Org::where('slug', $this->argument('org'))->first();
        if (! $org) {
            $this->error("No org with slug [{$this->argument('org')}].");

            return self::FAILURE;
        }

        $plan = (string) $this->argument('plan');
        if (! array_key_exists($plan, Plans::all())) {
            $this->error("Unknown plan [{$plan}]. Plans: ".implode(', ', array_keys(Plans::all())).'.');

            return self::FAILURE;
        }

        $unknown = array_diff($this->option('addon'), array_keys(Plans::addons()));
        if ($unknown !== []) {
            $this->error('Unknown add-on ['.implode(', ', $unknown).']. Add-ons: '.implode(', ', array_keys(Plans::addons())).'.');

            return self::FAILURE;
        }

        $from = $this->option('from');
        if ($from !== null && CarbonImmutable::hasFormat($from, 'Y-m-d') === false) {
            $this->error('--from must be YYYY-MM-DD.');

            return self::FAILURE;
        }

        $addons = match (true) {
            $this->option('no-addons') => [],
            $this->option('addon') !== [] => $this->option('addon'),
            default => null,
        };

        $pass = PlanAssigner::activate($org, $plan, CarbonImmutable::parse($from ?? now('Asia/Kolkata')->toDateString()), $addons);
        $definition = Plans::get($plan);

        $this->info(sprintf(
            '%s is on %s from %s to %s (%s%s). %s.',
            $org->name, $definition['name'], $pass->starts_on->format('d M Y'), $pass->ends_on->format('d M Y'),
            $definition['term_label'] ?? 'custom term', $definition['renews'] === false ? ', does not renew' : '',
            Plans::limitsLabel($definition),
        ));

        return self::SUCCESS;
    }
}
