<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\MetOffice\Enums\WindDirection;
use ChristianBrown\MetOffice\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\MetOffice\Transformer\WeatherTypeTransformerInterface;

use function gmdate;

use const DATE_ATOM;

final class OutputTransformer implements OutputTransformerInterface
{
    private WeatherTypeTransformerInterface $weatherTypeTransformer;

    public function __construct(WeatherTypeTransformerInterface $weatherTypeTransformer)
    {
        $this->weatherTypeTransformer = $weatherTypeTransformer;
    }

    /**
     * @return mixed[]
     */
    public function transform(HourlyForecastTimeStepInterface $step): array
    {
        $validFrom = $step->getTime();
        $validTo = $validFrom + self::WINDOW_SECONDS;

        // The valid-window fields are always emitted; each optional field is a
        // self-contained helper unioned onto the base, so its presence/absence
        // is an independent code path.
        $data = [
            self::KEY_VALID_FROM => $validFrom,
            self::KEY_VALID_FROM_ISO8601 => gmdate(DATE_ATOM, $validFrom),
            self::KEY_VALID_TO => $validTo,
            self::KEY_VALID_TO_ISO8601 => gmdate(DATE_ATOM, $validTo),
        ];
        $data += $this->temperature($step);
        $data += $this->feelsLike($step);
        $data += $this->humidity($step);
        $data += $this->precipitation($step);
        $data += $this->uvIndex($step);
        $data += $this->visibility($step);
        $data += $this->windSpeed($step);
        $data += $this->windGust($step);
        $data += $this->windDirection($step);
        $data += $this->type($step);
        $data += $this->typeString($step);
        $data += $this->typeEmoji($step);

        return $data;
    }

    /**
     * @return mixed[]
     */
    private function feelsLike(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getFeelsLikeTemperature();
        if (null === $value) {
            return [];
        }

        return [self::KEY_TEMPERATURE_FEELS_LIKE => $value];
    }

    /**
     * @return mixed[]
     */
    private function humidity(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getScreenRelativeHumidity();
        if (null === $value) {
            return [];
        }

        return [self::KEY_HUMIDITY => $value];
    }

    /**
     * @return mixed[]
     */
    private function precipitation(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getProbOfPrecipitation();
        if (null === $value) {
            return [];
        }

        return [self::KEY_PRECIPITATION => $value];
    }

    /**
     * @return mixed[]
     */
    private function temperature(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getScreenTemperature();
        if (null === $value) {
            return [];
        }

        return [self::KEY_TEMPERATURE => $value];
    }

    /**
     * @return mixed[]
     */
    private function type(HourlyForecastTimeStepInterface $step): array
    {
        $code = $step->getSignificantWeatherCode();
        if (null === $code) {
            return [];
        }

        return [self::KEY_TYPE => $code->value];
    }

    /**
     * @return mixed[]
     */
    private function typeEmoji(HourlyForecastTimeStepInterface $step): array
    {
        $code = $step->getSignificantWeatherCode();
        if (null === $code) {
            return [];
        }
        $emoji = $this->weatherTypeTransformer->transformToEmoji($code);
        if (null === $emoji) {
            return [];
        }

        return [self::KEY_TYPE_EMOJI => $emoji];
    }

    /**
     * @return mixed[]
     */
    private function typeString(HourlyForecastTimeStepInterface $step): array
    {
        $code = $step->getSignificantWeatherCode();
        if (null === $code) {
            return [];
        }
        $name = $this->weatherTypeTransformer->transform($code);
        if (null === $name) {
            return [];
        }

        return [self::KEY_TYPE_STRING => $name];
    }

    /**
     * @return mixed[]
     */
    private function uvIndex(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getUvIndex();
        if (null === $value) {
            return [];
        }

        return [self::KEY_UV_INDEX => $value];
    }

    /**
     * @return mixed[]
     */
    private function visibility(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getVisibility();
        if (null === $value) {
            return [];
        }

        return [self::KEY_VISIBILITY => $value];
    }

    /**
     * @return mixed[]
     */
    private function windDirection(HourlyForecastTimeStepInterface $step): array
    {
        $degrees = $step->getWindDirectionFrom10m();
        if (null === $degrees) {
            return [];
        }

        return [self::KEY_WIND_DIRECTION => WindDirection::fromDegrees($degrees)->value];
    }

    /**
     * @return mixed[]
     */
    private function windGust(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getMax10mWindGust();
        if (null === $value) {
            return [];
        }

        return [self::KEY_WIND_GUST => $value * self::METRES_PER_SECOND_TO_MPH];
    }

    /**
     * @return mixed[]
     */
    private function windSpeed(HourlyForecastTimeStepInterface $step): array
    {
        $value = $step->getWindSpeed10m();
        if (null === $value) {
            return [];
        }

        return [self::KEY_WIND_SPEED => $value * self::METRES_PER_SECOND_TO_MPH];
    }
}
