<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\MetOffice\Enums\WeatherType;
use ChristianBrown\MetOffice\Enums\WindDirection;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WeatherData',
    description: 'The current-hour weather. The valid-window fields are always present; every other field appears only when its source value is available.',
    required: [
        self::KEY_VALID_FROM,
        self::KEY_VALID_FROM_ISO8601,
        self::KEY_VALID_TO,
        self::KEY_VALID_TO_ISO8601,
    ],
    properties: [
        new OA\Property(property: self::KEY_VALID_FROM, description: 'Start of the forecast window (Unix seconds).', type: 'integer'),
        new OA\Property(property: self::KEY_VALID_FROM_ISO8601, type: 'string', format: 'date-time'),
        new OA\Property(property: self::KEY_VALID_TO, description: 'End of the forecast window (Unix seconds).', type: 'integer'),
        new OA\Property(property: self::KEY_VALID_TO_ISO8601, type: 'string', format: 'date-time'),
        new OA\Property(property: self::KEY_TEMPERATURE, description: 'Screen air temperature (degrees Celsius).', type: 'number'),
        new OA\Property(property: self::KEY_TEMPERATURE_FEELS_LIKE, description: 'Feels-like temperature (degrees Celsius).', type: 'number'),
        new OA\Property(property: self::KEY_HUMIDITY, description: 'Screen relative humidity (percent).', type: 'number'),
        new OA\Property(property: self::KEY_PRECIPITATION, description: 'Probability of precipitation (percent).', type: 'integer'),
        new OA\Property(property: self::KEY_UV_INDEX, description: 'UV index.', type: 'integer'),
        new OA\Property(property: self::KEY_VISIBILITY, description: 'Visibility (metres).', type: 'integer'),
        new OA\Property(property: self::KEY_PRESSURE, description: 'Mean sea level pressure (hectopascals).', type: 'number'),
        new OA\Property(property: self::KEY_DEW_POINT, description: 'Dew point temperature (degrees Celsius).', type: 'number'),
        new OA\Property(property: self::KEY_WIND_SPEED, description: '10m wind speed (mph).', type: 'number'),
        new OA\Property(property: self::KEY_WIND_GUST, description: 'Maximum 10m wind gust (mph).', type: 'number'),
        new OA\Property(property: self::KEY_WIND_DIRECTION, description: '10m wind direction as a compass point.', type: 'string', enum: WindDirection::class),
        new OA\Property(property: self::KEY_WIND_DIRECTION_DEGREES, description: '10m wind direction (degrees).', type: 'integer'),
        new OA\Property(property: self::KEY_TYPE, description: 'Met Office significant weather code.', type: 'integer', enum: WeatherType::class),
        new OA\Property(property: self::KEY_TYPE_NAME, description: 'Met Office weather type as a stable enum-name token (e.g. "HEAVY_RAIN"); the consumer maps it to a display name and emoji.', type: 'string'),
    ],
    type: 'object',
    additionalProperties: false,
)]
interface OutputTransformerInterface
{
    public const string KEY_DEW_POINT = 'dew_point';
    public const string KEY_HUMIDITY = 'humidity';
    public const string KEY_PRECIPITATION = 'precipitation';
    public const string KEY_PRESSURE = 'pressure';
    public const string KEY_TEMPERATURE = 'temp';
    public const string KEY_TEMPERATURE_FEELS_LIKE = 'temp_feels_like';
    public const string KEY_TYPE = 'type';
    public const string KEY_TYPE_NAME = 'type_name';
    public const string KEY_UV_INDEX = 'uv_index';
    public const string KEY_VALID_FROM = 'valid_from';
    public const string KEY_VALID_FROM_ISO8601 = 'valid_from_iso8601';
    public const string KEY_VALID_TO = 'valid_to';
    public const string KEY_VALID_TO_ISO8601 = 'valid_to_iso8601';
    public const string KEY_VISIBILITY = 'visibility';
    public const string KEY_WIND_DIRECTION = 'wind_direction';
    public const string KEY_WIND_DIRECTION_DEGREES = 'wind_direction_degrees';
    public const string KEY_WIND_GUST = 'wind_gust';
    public const string KEY_WIND_SPEED = 'wind_speed';
    public const float METRES_PER_SECOND_TO_MPH = 2.2369362920544;
    public const int PASCALS_PER_HECTOPASCAL = 100;
    public const int WINDOW_SECONDS = 3600;

    /**
     * @return mixed[]
     */
    public function transform(HourlyForecastTimeStepInterface $step): array;
}
