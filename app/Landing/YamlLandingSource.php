<?php

namespace App\Landing;

use App\Contracts\LandingSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Event configs authored as YAML under config('eventflow.events_path'), e.g. events/demo/*.yaml.
 * Sport packs (events/sports) are not event configs and are skipped.
 */
final class YamlLandingSource implements LandingSource
{
    public function __construct(private ConfigCompiler $compiler) {}

    public function load(string $org, string $slug): EventConfig
    {
        foreach ($this->files() as $path) {
            $data = $this->read($path);

            if (($data['org'] ?? null) === $org && ($data['slug'] ?? null) === $slug) {
                return $this->compiler->compile($data, $path);
            }
        }

        throw (new ModelNotFoundException)->setModel('event', ["{$org}/{$slug}"]);
    }

    /**
     * Every event YAML file, sorted by path.
     *
     * @return list<string>
     */
    public function files(): array
    {
        $root = config('eventflow.events_path');
        $sports = realpath(config('eventflow.sports_path')) ?: config('eventflow.sports_path');

        $finder = Finder::create()->files()->in($root)->name(['*.yaml', '*.yml'])->sortByName();
        $files = [];

        foreach ($finder as $file) {
            if (! str_starts_with($file->getRealPath(), $sports.DIRECTORY_SEPARATOR)) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    /**
     * The config exactly as authored (no sport pack defaults).
     *
     * @return array<string, mixed>
     *
     * @throws InvalidEventConfig when the file is not a YAML mapping
     */
    public function read(string $path): array
    {
        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new InvalidEventConfig($path, [['property' => '', 'message' => $e->getMessage()]]);
        }

        if (! is_array($data) || array_is_list($data)) {
            throw new InvalidEventConfig($path, [['property' => '', 'message' => 'expected a YAML mapping']]);
        }

        return $data;
    }
}
