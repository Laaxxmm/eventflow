<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Yaml\Yaml;

const MARATHON = 'events/demo/example-marathon.yaml';

/** Syncs the demo marathon YAML and returns its event. */
function syncMarathon(): Event
{
    Artisan::call('events:sync', ['paths' => [base_path(MARATHON)]]);

    return Event::where('slug', 'bengaluru-city-marathon-2027')->firstOrFail();
}

/** Writes a YAML file into the test's scratch dir and returns its path. */
function yamlFile(array $data, string $name = 'event.yaml'): string
{
    $dir = storage_path('framework/testing/events-'.getmypid());
    is_dir($dir) || mkdir($dir, 0777, true);
    file_put_contents($path = "{$dir}/{$name}", Yaml::dump($data, 4));

    return $path;
}

function marathonData(): array
{
    return Yaml::parseFile(base_path(MARATHON));
}

/** A valid registration post for the demo marathon. */
function registrationInput(array $overrides = []): array
{
    return array_replace_recursive([
        'distance' => '5K',
        'participant_name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'phone' => '9876543210',
        'dob' => '1990-05-01',
        'answers' => [
            'gender' => 'Female',
            'emergency_contact_name' => 'Ravi Rao',
            'emergency_contact_phone' => '9876500000',
            'tshirt_size' => 'M',
        ],
    ], $overrides);
}
