<?php

declare(strict_types=1);

namespace ChristianBrown\GetMetOfficeTemps;

use ChristianBrown\MetOffice\DataPoint\Forecast\Model\Forecast;

interface ForecastTransformerInterface
{
    public const RESPONSE_KEY_FEELS_LIKE = 'temp_feels_like';
    public const RESPONSE_KEY_MAX_UV_INDEX = 'uv_index';
    public const RESPONSE_KEY_PRECIPITATION_PROBABILITY = 'precipitation';
    public const RESPONSE_KEY_SCREEN_RELATIVE_HUMIDITY = 'humidity';
    public const RESPONSE_KEY_TEMPERATURE = 'temp';
    public const RESPONSE_KEY_VALID_FROM = 'valid_from';
    public const RESPONSE_KEY_VALID_FROM_ISO8601 = 'valid_from_iso8601';
    public const RESPONSE_KEY_VALID_TO = 'valid_to';
    public const RESPONSE_KEY_VALID_TO_ISO8601 = 'valid_to_iso8601';
    public const RESPONSE_KEY_VISIBILITY = 'visibility';
    public const RESPONSE_KEY_WEATHER_TYPE = 'type';
    public const RESPONSE_KEY_WEATHER_TYPE_EMOJI = 'type_emoji';
    public const RESPONSE_KEY_WEATHER_TYPE_STRING = 'type_string';
    public const RESPONSE_KEY_WIND_DIRECTION = 'wind_direction';
    public const RESPONSE_KEY_WIND_GUST = 'wind_gust';
    public const RESPONSE_KEY_WIND_SPEED = 'wind_speed';

    public function transform(Forecast $forecast): array;
}
