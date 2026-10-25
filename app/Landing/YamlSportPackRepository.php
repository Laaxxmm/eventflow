<?php

namespace App\Landing;

use App\Contracts\SportPackRepository;
use App\Enums\Sport;
use App\Sports\SportPack;
use App\Support\EventSchema;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads sport packs from config('eventflow.sports_path')/<sport>.yaml.
 */
final class YamlSportPackRepository implements SportPackRepository
{
    /** @var array<string, SportPack> */
    private array $packs = [];

    public function get(Sport $sport): SportPack
    {
        return $this->packs[$sport->value] ??= $this->read($sport);
    }

    private function read(Sport $sport): SportPack
    {
        $path = config('eventflow.sports_path')."/{$sport->value}.yaml";
        $data = Yaml::parseFile($path);

        if ($errors = EventSchema::errors($data, EventSchema::SPORT_PACK)) {
            throw new RuntimeException("Invalid sport pack {$path}: ".json_encode($errors));
        }

        return SportPack::fromArray($data);
    }
}
