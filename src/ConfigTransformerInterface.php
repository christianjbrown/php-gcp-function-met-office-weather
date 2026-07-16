<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

interface ConfigTransformerInterface
{
    public const ENV_API_KEY = 'MET_OFFICE_WEATHER_API_KEY';
    public const ENV_LATITUDE = 'MET_OFFICE_WEATHER_LATITUDE';
    public const ENV_LONGITUDE = 'MET_OFFICE_WEATHER_LONGITUDE';

    /**
     * @param mixed[] $env
     */
    public function transform(array $env): ConfigInterface;
}
