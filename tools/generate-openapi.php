<?php

declare(strict_types=1);

use ChristianBrown\MetOffice\Transformer\WeatherTypeTransformerInterface;
use ChristianBrown\MetOfficeWeather\OutputTransformerInterface;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

require __DIR__ . '/../vendor/autoload.php';

// Scan the typed `#[OA\...]` attributes under `src/` — plus the shared envelope
// schema components (`SuccessEnvelope`/`ErrorEnvelope`) that live on the
// `php-gcp-function-lib` `ResponseInterface` — and emit the OpenAPI 3.0 document.
// Pinning the version to 3.0.0 keeps it inside the broadest validator support.
// The result is written to the committed `openapi.yaml`; CI regenerates it and
// fails on any diff, so the spec cannot drift from the attributes.
$openapi = (new Generator())
    ->setVersion(OpenApi::VERSION_3_0_0)
    ->generate([
        __DIR__ . '/../src',
        __DIR__ . '/../vendor/christianjbrown/php-gcp-function-lib/src',
    ]);

if (!$openapi instanceof OpenApi) {
    fwrite(STDERR, "Failed to generate the OpenAPI document from src/.\n");

    exit(1);
}

// The `wind_direction` and `type` enums are expanded from the backed PHP enums
// by swagger-php (the `enum: WindDirection::class` / `WeatherType::class`
// references on the `#[OA\Property]` attributes). The `type_string` and
// `type_emoji` values come from const *maps* on the datahub lib's
// `WeatherTypeTransformerInterface`, which swagger-php cannot see, so inject
// their allowed values here from those same maps — keeping the contract in sync
// with the library instead of hand-listing. Duplicate emojis are collapsed and
// empties dropped; map declaration order is stable, so the result is
// deterministic.
$injectEnum = static function (string $property, array $values) use ($openapi): void {
    foreach ($openapi->components->schemas as $schema) {
        if (!$schema instanceof Schema || 'WeatherData' !== $schema->schema) {
            continue;
        }

        foreach ($schema->properties as $candidate) {
            if ($candidate instanceof Property && $property === $candidate->property) {
                $candidate->enum = $values;
            }
        }
    }
};

$enumValues = static function (array $map): array {
    return array_values(array_unique(array_filter($map, static fn (mixed $value): bool => is_string($value) && '' !== $value)));
};

$injectEnum(OutputTransformerInterface::KEY_TYPE_STRING, $enumValues(WeatherTypeTransformerInterface::WEATHER_TYPE_NAMES));
$injectEnum(OutputTransformerInterface::KEY_TYPE_EMOJI, $enumValues(WeatherTypeTransformerInterface::WEATHER_TYPE_EMOJIS));

file_put_contents(__DIR__ . '/../openapi.yaml', $openapi->toYaml());
