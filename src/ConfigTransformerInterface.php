<?php

declare(strict_types=1);

namespace ChristianBrown\GetMetOfficeTemps;

interface ConfigTransformerInterface
{
    public const ENV_API_KEY = 'API_KEY';
    public const ENV_SITE_ID = 'SITE_ID';

    public function transform(array $env): ConfigInterface;
}
