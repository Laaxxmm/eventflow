<?php

namespace App\Console\Commands;

use App\Landing\ConfigCompiler;
use App\Landing\InvalidEventConfig;
use App\Landing\YamlLandingSource;
use Illuminate\Console\Command;

/**
 * Validates event YAML against events/schema.json (after sport pack defaults). Exits 1 on any error, for CI.
 */
class EventsValidate extends Command
{
    protected $signature = 'events:validate {paths?* : YAML files to check (default: every event YAML under events/)}';

    protected $description = 'Validate event YAML configs against events/schema.json';

    public function handle(YamlLandingSource $source, ConfigCompiler $compiler): int
    {
        $paths = $this->argument('paths') ?: $source->files();
        $seen = [];
        $failed = 0;

        foreach ($paths as $path) {
            $label = $this->relative($path);

            if (! is_file($path)) {
                $this->components->error("{$label}: file not found");
                $failed++;

                continue;
            }

            try {
                $authored = $source->read($path);
                $config = $compiler->compile($authored, $label);
            } catch (InvalidEventConfig $e) {
                $this->components->error($label);
                foreach ($e->errors as $error) {
                    $this->line('  <fg=red>•</> '.($error['property'] !== '' ? "<comment>{$error['property']}</comment>: " : '').$error['message']);
                }
                $failed++;

                continue;
            }

            $key = "{$config->org}/{$config->slug}";
            if (isset($seen[$key])) {
                $this->components->error("{$label}: duplicate org + slug {$key} (also in {$seen[$key]})");
                $failed++;

                continue;
            }
            $seen[$key] = $label;

            $this->components->twoColumnDetail($label, "<fg=green>OK</> {$key}");
        }

        if ($paths === []) {
            $this->components->warn('No event YAML files found.');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function relative(string $path): string
    {
        return str_starts_with($path, base_path().DIRECTORY_SEPARATOR) ? substr($path, strlen(base_path()) + 1) : $path;
    }
}
