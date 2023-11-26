<?php

declare(strict_types=1);

namespace ChristianBrown\GetMetOfficeTemps;

use ChristianBrown\CloudFunction\FunctionConfigTransformerInterface;

final class ConfigTransformer implements ConfigTransformerInterface
{
    private FunctionConfigTransformerInterface $functionConfigTransformer;

    public function __construct(FunctionConfigTransformerInterface $functionConfigTransformer)
    {
        $this->functionConfigTransformer = $functionConfigTransformer;
    }

    public function transform(array $env): ConfigInterface
    {
        if (empty($env[self::ENV_SITE_ID]) || !is_numeric($env[self::ENV_SITE_ID])) {
            throw new RuntimeException(sprintf('%s not set or not a number', self::ENV_SITE_ID));
        }
        $siteId = (int) $env[self::ENV_SITE_ID];

        if (empty($env[self::ENV_API_KEY]) || !is_string($env[self::ENV_API_KEY])) {
            throw new RuntimeException(sprintf('%s not set or not a string', self::ENV_API_KEY));
        }
        $apiKey = $env[self::ENV_API_KEY];

        $requestConfig = $this->functionConfigTransformer->transform($env);

        return new Config($requestConfig, $siteId, $apiKey);
    }
}
