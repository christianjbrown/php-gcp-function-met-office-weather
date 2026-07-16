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
        // Split into sequential guards (rather than a single `||`) so each
        // failure path is independently reachable for path coverage.
        if (empty($env[self::ENV_API_KEY])) {
            throw new RuntimeException(sprintf('%s not set or not a string', self::ENV_API_KEY));
        }
        if (!is_string($env[self::ENV_API_KEY])) {
            throw new RuntimeException(sprintf('%s not set or not a string', self::ENV_API_KEY));
        }
        $apiKey = $env[self::ENV_API_KEY];

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

        $requestConfig = $this->functionConfigTransformer->transform($env);

        return new Config($requestConfig, $apiKey, $latitude, $longitude);
    }
}
