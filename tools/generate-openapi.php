<?php

declare(strict_types=1);

use ChristianBrown\MetOffice\Enums\WeatherType;
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
// references on the `#[OA\Property]` attributes). `type_name` is a *string*
// carrying the `WeatherType` enum's case *names* (e.g. "HEAVY_RAIN"), which
// swagger-php cannot derive from an int-backed enum, so inject the allowed
// values here from `WeatherType::cases()` — keeping the contract in sync with
// the library instead of hand-listing. Case declaration order is stable, so the
// result is deterministic.
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

$injectEnum(OutputTransformerInterface::KEY_TYPE_NAME, $enumValues(array_column(WeatherType::cases(), 'name')));

file_put_contents(__DIR__ . '/../openapi.yaml', $openapi->toYaml());
