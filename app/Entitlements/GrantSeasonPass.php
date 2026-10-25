<?php

namespace App\Entitlements;

use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * For Indefine staff until passes can be bought online:
 *   php artisan season-pass:grant demo-runners --plan="Season 2027" --until=2027-12-31 --module=checkin --module=certificates
 */
final class GrantSeasonPass extends Command
{
    protected $signature = 'season-pass:grant
        {org : Org slug}
        {--plan=Season : Plan label shown in Org Settings}
        {--from= : Start date (YYYY-MM-DD), default today}
        {--until= : End date (YYYY-MM-DD), default from + config eventflow.season_pass.length_days}
        {--module=* : Optional modules to include (checkin, race_sheets, certificates); default all}
        {--amount=0 : Amount paid in INR, for the record}';

    protected $description = 'Grant an org an active Season Pass (core modules are always on).';

    public function handle(): int
    {
        $org = Org::where('slug', $this->argument('org'))->first();
        if (! $org) {
            $this->error("No org with slug [{$this->argument('org')}].");

            return self::FAILURE;
        }

        $optional = array_map(fn (Module $m) => $m->value, SeasonPassEntitlements::optional());
        $validator = Validator::make($this->options(), [
            'plan' => ['required', 'string', 'max:40'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d'],
            'module' => ['array'],
            'module.*' => ['in:'.implode(',', $optional)],
            'amount' => ['integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $from = CarbonImmutable::parse($this->option('from') ?? now('Asia/Kolkata')->toDateString());
        $until = $this->option('until')
            ? CarbonImmutable::parse($this->option('until'))
            : $from->addDays((int) config('eventflow.season_pass.length_days', 365) - 1);

        if ($until->lt($from)) {
            $this->error('--until must be on or after --from.');

            return self::FAILURE;
        }

        $chosen = $this->option('module') ?: $optional;
        $values = array_values(array_unique([...(array) config('eventflow.modules.core', []), ...$chosen]));
        $modules = array_map(fn (string $m) => Module::from($m), $values);

        $pass = SeasonPass::withoutGlobalScopes()->create([
            'org_id' => $org->id,
            'plan' => $this->option('plan'),
            'starts_on' => $from->toDateString(),
            'ends_on' => $until->toDateString(),
            'status' => 'active',
            'modules' => $modules,
            'amount_paise' => (int) $this->option('amount') * 100,
        ]);

        $this->info(sprintf(
            'Granted "%s" to %s from %s to %s with: %s.',
            $pass->plan, $org->name, $from->format('d M Y'), $until->format('d M Y'),
            implode(', ', array_map(fn (Module $m) => EnsureModuleEnabled::label($m), $pass->modules->all())),
        ));

        return self::SUCCESS;
    }
}
