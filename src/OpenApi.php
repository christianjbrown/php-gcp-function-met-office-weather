<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\GcpFunction\ResponseInterface;
use OpenApi\Attributes as OA;

/**
 * Inert OpenAPI spec-holder.
 *
 * This class carries no runtime behaviour and is never instantiated: it exists
 * only so `zircote/swagger-php` can scan its `#[OA\...]` attributes and emit the
 * committed `openapi.yaml`. Keeping the top-level `#[OA\Info]`/`#[OA\Server]` and
 * the `#[OA\Get]` operation here means the HTTP contract is generated from the same
 * typed code that produces the responses, so it cannot silently drift. The success
 * response composes the shared `SuccessEnvelope` component (from `php-gcp-function-lib`)
 * with this function's local `WeatherData` schema via `allOf` — re-narrowing only the
 * envelope's generic `data` placeholder — and the error responses reference the shared
 * `ErrorEnvelope` component directly, so the reusable envelope is declared once in the
 * library rather than inline here. It has no executable lines and is excluded from
 * coverage in `phpunit.xml`, like a config file.
 */
#[OA\Info(
    version: '1.0.0',
    description: 'Reads the current outdoor weather for a fixed latitude/longitude from the Met Office Weather DataHub Site-Specific hourly forecast API and returns it as a single JSON envelope.',
    title: 'Met Office Weather Cloud Function',
)]
#[OA\Server(url: '/')]
#[OA\Get(
    path: '/',
    operationId: 'getCurrentWeather',
    summary: 'Get the current weather for the configured location.',
    description: 'Returns the current-hour weather for the function\'s configured location.',
    responses: [
        new OA\Response(
            response: ResponseInterface::STATUS_OK,
            description: 'The current-hour weather for the configured location.',
            headers: [
                new OA\Header(header: ResponseInterface::HEADER_KEY_ALLOW_METHODS, description: 'Allowed CORS methods.', schema: new OA\Schema(type: 'string')),
                new OA\Header(header: ResponseInterface::HEADER_KEY_ALLOW_ORIGIN, description: 'Configured allowed origin (present when a required origin is configured).', schema: new OA\Schema(type: 'string')),
                new OA\Header(header: ResponseInterface::HEADER_KEY_CACHE_CONTROL, description: 'CDN/browser cache directives (present when cache TTLs are configured).', schema: new OA\Schema(type: 'string')),
                new OA\Header(header: ResponseInterface::HEADER_KEY_SURROGATE_CONTROL, description: 'Surrogate cache directives (present when cache TTLs are configured).', schema: new OA\Schema(type: 'string')),
                new OA\Header(header: ResponseInterface::HEADER_KEY_VARY, description: 'Vary list (present when a required origin is configured).', schema: new OA\Schema(type: 'string')),
            ],
            content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
                    new OA\Schema(
                        properties: [
                            new OA\Property(property: ResponseInterface::RESPONSE_API_KEY_DATA, ref: '#/components/schemas/WeatherData'),
                        ],
                    ),
                ],
            ),
        ),
        new OA\Response(
            response: ResponseInterface::STATUS_UNAUTHORIZED,
            description: 'The request failed header authorization.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
        new OA\Response(
            response: ResponseInterface::STATUS_INTERNAL_SERVER_ERROR,
            description: 'No forecast was available, or an unhandled error occurred.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
    ],
)]
final class OpenApi
{
}
