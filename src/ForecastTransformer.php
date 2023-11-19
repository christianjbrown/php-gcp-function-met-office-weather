<?php

declare(strict_types=1);

use ChristianBrown\MetOffice\DataPoint\Forecast\Model\Forecast;
use ChristianBrown\MetOffice\DataPoint\Forecast\Model\ForecastLocationPeriod;
use ChristianBrown\MetOffice\DataPoint\Forecast\Model\ForecastLocationShortPeriodRepresentation;

final class ForecastTransformer implements ForecastTransformerInterface
{
    public function transform(Forecast $forecast): array
    {
        $data = [];
        $periods = $forecast->location->periods;
        if (isset($periods[0]) && $periods[0] instanceof ForecastLocationPeriod) {
            $period = $periods[0];
            $reps = $period->representations;
            if (isset($reps[0]) && $reps[0] instanceof ForecastLocationShortPeriodRepresentation) {
                $rep = $reps[0];

                $day = strtotime($period->value);
                $validFrom = $day + ($rep->timePeriod * 60);
                $validTo = $day + ($rep->timePeriod * 60) + (3 * 60 * 60);

                $data = [
                    self::RESPONSE_KEY_FEELS_LIKE => $rep->feelsLike,
                    self::RESPONSE_KEY_MAX_UV_INDEX => $rep->maxUvIndex,
                    self::RESPONSE_KEY_PRECIPITATION_PROBABILITY => $rep->precipitationProbability,
                    self::RESPONSE_KEY_SCREEN_RELATIVE_HUMIDITY => $rep->screenRelativeHumidity,
                    self::RESPONSE_KEY_TEMPERATURE => $rep->temperature,
                    self::RESPONSE_KEY_VALID_FROM => $validFrom,
                    self::RESPONSE_KEY_VALID_FROM_ISO8601 => date('c', $validFrom),
                    self::RESPONSE_KEY_VALID_TO => $validTo,
                    self::RESPONSE_KEY_VALID_TO_ISO8601 => date('c', $validTo),
                    self::RESPONSE_KEY_VISIBILITY => $rep->visibility,
                    self::RESPONSE_KEY_WEATHER_TYPE => $rep->weatherType,
                    self::RESPONSE_KEY_WIND_DIRECTION => $rep->windDirection,
                    self::RESPONSE_KEY_WIND_GUST => $rep->windGust,
                    self::RESPONSE_KEY_WIND_SPEED => $rep->windSpeed,
                ];
            }
        }

        return $data;
    }
}
