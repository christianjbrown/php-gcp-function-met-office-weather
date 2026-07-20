<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

interface ConfigTransformerInterface
{
    public const string ENV_API_KEY = 'MET_OFFICE_WEATHER_API_KEY';
    public const string ENV_LATITUDE = 'MET_OFFICE_WEATHER_LATITUDE';
    public const string ENV_LONGITUDE = 'MET_OFFICE_WEATHER_LONGITUDE';

    /**
     * @param mixed[] $env
     */
    public function transform(array $env): ConfigInterface;
}
