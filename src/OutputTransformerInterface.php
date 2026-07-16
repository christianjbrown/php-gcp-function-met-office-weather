<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;

interface OutputTransformerInterface
{
    public const KEY_HUMIDITY = 'humidity';
    public const KEY_PRECIPITATION = 'precipitation';
    public const KEY_TEMPERATURE = 'temp';
    public const KEY_TEMPERATURE_FEELS_LIKE = 'temp_feels_like';
    public const KEY_TYPE = 'type';
    public const KEY_TYPE_EMOJI = 'type_emoji';
    public const KEY_TYPE_STRING = 'type_string';
    public const KEY_UV_INDEX = 'uv_index';
    public const KEY_VALID_FROM = 'valid_from';
    public const KEY_VALID_FROM_ISO8601 = 'valid_from_iso8601';
    public const KEY_VALID_TO = 'valid_to';
    public const KEY_VALID_TO_ISO8601 = 'valid_to_iso8601';
    public const KEY_VISIBILITY = 'visibility';
    public const KEY_WIND_DIRECTION = 'wind_direction';
    public const KEY_WIND_DIRECTION_DEGREES = 'wind_direction_degrees';
    public const KEY_WIND_GUST = 'wind_gust';
    public const KEY_WIND_SPEED = 'wind_speed';
    public const METRES_PER_SECOND_TO_MPH = 2.2369362920544;
    public const WINDOW_SECONDS = 3600;

    /**
     * @return mixed[]
     */
    public function transform(HourlyForecastTimeStepInterface $step): array;
}
