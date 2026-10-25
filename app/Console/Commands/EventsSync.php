<?php

namespace App\Console\Commands;

use App\Landing\ConfigCompiler;
use App\Landing\Distance;
use App\Landing\EventColumns;
use App\Landing\EventStatus;
use App\Landing\InvalidEventConfig;
use App\Landing\YamlLandingSource;
use App\Models\Event;
use App\Models\Org;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Upserts event YAML into events (by org + slug), storing the authored YAML in events.config.
 *
 * Never deletes an event that has registrations, and never drops a distance that
 * registrations point at. --prune removes events of YAML-managed orgs that no file
 * declares any more; events with registrations are closed instead.
 */
class EventsSync extends Command
{
    protected $signature = 'events:sync
        {paths?* : YAML files to sync (default: every event YAML under events/)}
        {--prune : Remove events of synced orgs that no YAML file declares (events with registrations are closed, never deleted)}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Upsert event YAML configs into the events table';

    public function handle(YamlLandingSource $source, ConfigCompiler $compiler): int
    {
        $paths = $this->argument('paths') ?: $source->files();
        $dry = (bool) $this->option('dry-run');
        $failed = 0;
        $synced = []; // org slug => list of event slugs

        foreach ($paths as $path) {
            try {
                $authored = $source->read($path);
                $config = $compiler->compile($authored, $path);
            } catch (InvalidEventConfig $e) {
                $this->components->error($e->getMessage());
                $failed++;

                continue;
            }

            $synced[$config->org][] = $config->slug;
            $org = Org::firstWhere('slug', $config->org);
            $event = $org?->events()->where('slug', $config->slug)->first();

            if ($event && ($missing = $this->droppedDistances($event, $config->distances))) {
                $this->components->error("{$config->org}/{$config->slug}: distances ".implode(', ', $missing).' have registrations and cannot be removed');
                $failed++;

                continue;
            }

            $action = $event ? 'updated' : 'created';
            if (! $dry) {
                DB::transaction(function () use ($org, $event, $config, $authored) {
                    $org ??= Org::create(['slug' => $config->org, 'name' => Str::headline($config->org)]);
                    $attributes = EventColumns::from($authored, $config);

                    if ($event) {
                        $event->update($attributes);
                    } else {
                        $org->events()->create($attributes + ['status' => EventStatus::Published]);
                    }
                });
            }

            $this->components->twoColumnDetail("{$config->org}/{$config->slug}", $dry ? "would be {$action}" : "<fg=green>{$action}</>");
        }

        if ($this->option('prune')) {
            $failed === 0
                ? $this->prune($synced, $dry)
                : $this->components->warn('Skipped --prune because some files failed.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<Distance>  $distances
     * @return list<string> Distance codes that registrations use but the new config drops
     */
    private function droppedDistances(Event $event, array $distances): array
    {
        $kept = array_map(fn ($d) => $d->code, $distances);

        return $event->registrations()
            ->whereNotNull('distance_code')
            ->whereNotIn('distance_code', $kept)
            ->distinct()
            ->pluck('distance_code')
            ->all();
    }

    /** @param  array<string, list<string>>  $synced */
    private function prune(array $synced, bool $dry): void
    {
        foreach ($synced as $orgSlug => $slugs) {
            $stale = Event::query()
                ->whereHas('org', fn ($q) => $q->where('slug', $orgSlug))
                ->whereNotIn('slug', $slugs)
                ->withCount('registrations')
                ->get();

            foreach ($stale as $event) {
                $key = "{$orgSlug}/{$event->slug}";

                if ($event->registrations_count > 0) {
                    if (! $dry && $event->status !== EventStatus::Closed) {
                        $event->update(['status' => EventStatus::Closed]);
                    }
                    $this->components->twoColumnDetail($key, "<fg=yellow>kept ({$event->registrations_count} registrations), closed</>");

                    continue;
                }

                if (! $dry) {
                    $event->delete();
                }
                $this->components->twoColumnDetail($key, $dry ? 'would be deleted' : '<fg=red>deleted</>');
            }
        }
    }
}
