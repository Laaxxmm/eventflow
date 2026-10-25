<?php

namespace App\Support;

use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

/**
 * Validates event configs (events/schema.json) and sport packs (events/sports/schema.json).
 * Use this instead of wiring a second validator.
 */
final class EventSchema
{
    public const EVENT = 'https://eventflow.app/schemas/event.json';

    public const SPORT_PACK = 'https://eventflow.app/schemas/sport-pack.json';

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{property: string, message: string}> Empty when valid
     */
    public static function errors(array $data, string $schemaId = self::EVENT): array
    {
        $storage = new SchemaStorage;
        $storage->addSchema(self::EVENT, json_decode(file_get_contents(config('eventflow.schema_path'))));
        $storage->addSchema(self::SPORT_PACK, json_decode(file_get_contents(config('eventflow.sport_pack_schema_path'))));

        $payload = json_decode(json_encode($data));
        $validator = new Validator(new Factory($storage));
        $validator->validate($payload, $storage->getSchema($schemaId));

        return array_map(
            fn (array $e) => ['property' => $e['property'], 'message' => $e['message']],
            $validator->getErrors(),
        );
    }
}
