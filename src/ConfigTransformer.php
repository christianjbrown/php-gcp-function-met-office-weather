<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\GcpFunction\FunctionConfigTransformerInterface;
use RuntimeException;

use function is_numeric;
use function is_string;
use function sprintf;

final class ConfigTransformer implements ConfigTransformerInterface
{
    private FunctionConfigTransformerInterface $functionConfigTransformer;

    public function __construct(FunctionConfigTransformerInterface $functionConfigTransformer)
    {
        $this->functionConfigTransformer = $functionConfigTransformer;
    }

    /**
     * @param mixed[] $env
     */
    public function transform(array $env): ConfigInterface
    {
        // The string env vars are guarded by a shared helper (keeping this
        // method's cyclomatic complexity within the ChristianBrown standard's
        // limit); latitude/longitude are numeric so are checked inline.
        $apiKey = $this->extractRequiredString($env, self::ENV_API_KEY);

        // isset (not empty) so a legitimate 0 (e.g. longitude at Greenwich) survives.
        if (!isset($env[self::ENV_LATITUDE])) {
            throw new RuntimeException(sprintf('%s not set or not numeric', self::ENV_LATITUDE));
        }
        if (!is_numeric($env[self::ENV_LATITUDE])) {
            throw new RuntimeException(sprintf('%s not set or not numeric', self::ENV_LATITUDE));
        }
        $latitude = (float) $env[self::ENV_LATITUDE];

        if (!isset($env[self::ENV_LONGITUDE])) {
            throw new RuntimeException(sprintf('%s not set or not numeric', self::ENV_LONGITUDE));
        }
        if (!is_numeric($env[self::ENV_LONGITUDE])) {
            throw new RuntimeException(sprintf('%s not set or not numeric', self::ENV_LONGITUDE));
        }
        $longitude = (float) $env[self::ENV_LONGITUDE];

        $databaseDsn = $this->extractRequiredString($env, self::ENV_DATABASE_DSN);

        $requestConfig = $this->functionConfigTransformer->transform($env);

        return new Config($requestConfig, $apiKey, $latitude, $longitude, $databaseDsn);
    }

    /**
     * @param mixed[] $env
     */
    private function extractRequiredString(array $env, string $key): string
    {
        // Split into sequential guards (rather than a single `||`) so each
        // failure path is independently reachable for path coverage.
        if (empty($env[$key])) {
            throw new RuntimeException(sprintf('%s not set or not a string', $key));
        }
        if (!is_string($env[$key])) {
            throw new RuntimeException(sprintf('%s not set or not a string', $key));
        }

        return $env[$key];
    }
}
