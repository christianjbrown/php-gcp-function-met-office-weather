<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather\Tests;

use ChristianBrown\MetOffice\Enums\WeatherType;
use ChristianBrown\MetOffice\Enums\WindDirection;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\MetOfficeWeather\OutputTransformer;
use ChristianBrown\MetOfficeWeather\OutputTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

use function gmdate;

use const DATE_ATOM;

#[CoversClass(OutputTransformer::class)]
final class OutputTransformerTest extends TestCase
{
    private const TIME = 1752580800;

    /**
     * @throws Exception
     */
    public function testTransformsFullStep(): void
    {
        $step = $this->createStep(self::TIME, 18.7, 17.2, 65.5, 20, 3, 30000, 10.0, 12.0, 90, WeatherType::SUNNY_DAY, 101320, 12.3);

        $transformer = new OutputTransformer();

        $expected = [
            OutputTransformerInterface::KEY_VALID_FROM => self::TIME,
            OutputTransformerInterface::KEY_VALID_FROM_ISO8601 => gmdate(DATE_ATOM, self::TIME),
            OutputTransformerInterface::KEY_VALID_TO => self::TIME + OutputTransformerInterface::WINDOW_SECONDS,
            OutputTransformerInterface::KEY_VALID_TO_ISO8601 => gmdate(DATE_ATOM, self::TIME + OutputTransformerInterface::WINDOW_SECONDS),
            OutputTransformerInterface::KEY_TEMPERATURE => 18.7,
            OutputTransformerInterface::KEY_TEMPERATURE_FEELS_LIKE => 17.2,
            OutputTransformerInterface::KEY_HUMIDITY => 65.5,
            OutputTransformerInterface::KEY_PRECIPITATION => 20,
            OutputTransformerInterface::KEY_UV_INDEX => 3,
            OutputTransformerInterface::KEY_VISIBILITY => 30000,
            OutputTransformerInterface::KEY_PRESSURE => 101320 / OutputTransformerInterface::PASCALS_PER_HECTOPASCAL,
            OutputTransformerInterface::KEY_DEW_POINT => 12.3,
            OutputTransformerInterface::KEY_WIND_SPEED => 10.0 * OutputTransformerInterface::METRES_PER_SECOND_TO_MPH,
            OutputTransformerInterface::KEY_WIND_GUST => 12.0 * OutputTransformerInterface::METRES_PER_SECOND_TO_MPH,
            OutputTransformerInterface::KEY_WIND_DIRECTION => WindDirection::fromDegrees(90)->value,
            OutputTransformerInterface::KEY_WIND_DIRECTION_DEGREES => 90,
            OutputTransformerInterface::KEY_TYPE => WeatherType::SUNNY_DAY->value,
            OutputTransformerInterface::KEY_TYPE_NAME => WeatherType::SUNNY_DAY->name,
        ];

        self::assertSame($expected, $transformer->transform($step));
    }

    /**
     * @throws Exception
     */
    public function testTransformsMinimalStep(): void
    {
        $step = $this->createStep(self::TIME, null, null, null, null, null, null, null, null, null, null);

        $transformer = new OutputTransformer();

        $expected = [
            OutputTransformerInterface::KEY_VALID_FROM => self::TIME,
            OutputTransformerInterface::KEY_VALID_FROM_ISO8601 => gmdate(DATE_ATOM, self::TIME),
            OutputTransformerInterface::KEY_VALID_TO => self::TIME + OutputTransformerInterface::WINDOW_SECONDS,
            OutputTransformerInterface::KEY_VALID_TO_ISO8601 => gmdate(DATE_ATOM, self::TIME + OutputTransformerInterface::WINDOW_SECONDS),
        ];

        self::assertSame($expected, $transformer->transform($step));
    }

    /**
     * The function no longer decides display wording, so it emits the enum-name
     * token for every code — including NOT_USED, which had no display mapping
     * under the old transformer. The consumer decides whether to render it.
     *
     * @throws Exception
     */
    public function testWeatherTypeEmitsNameTokenEvenForDisplaylessCode(): void
    {
        $step = $this->createStep(self::TIME, null, null, null, null, null, null, null, null, null, WeatherType::NOT_USED);

        $transformer = new OutputTransformer();

        $expected = [
            OutputTransformerInterface::KEY_VALID_FROM => self::TIME,
            OutputTransformerInterface::KEY_VALID_FROM_ISO8601 => gmdate(DATE_ATOM, self::TIME),
            OutputTransformerInterface::KEY_VALID_TO => self::TIME + OutputTransformerInterface::WINDOW_SECONDS,
            OutputTransformerInterface::KEY_VALID_TO_ISO8601 => gmdate(DATE_ATOM, self::TIME + OutputTransformerInterface::WINDOW_SECONDS),
            OutputTransformerInterface::KEY_TYPE => WeatherType::NOT_USED->value,
            OutputTransformerInterface::KEY_TYPE_NAME => WeatherType::NOT_USED->name,
        ];

        self::assertSame($expected, $transformer->transform($step));
    }

    /**
     * @throws Exception
     */
    private function createStep(int $time, ?float $temperature, ?float $feelsLike, ?float $humidity, ?int $precipitation, ?int $uvIndex, ?int $visibility, ?float $windSpeed, ?float $windGust, ?int $windDirection, ?WeatherType $weatherCode, ?int $pressure = null, ?float $dewPoint = null): HourlyForecastTimeStepInterface
    {
        $step = self::createStub(HourlyForecastTimeStepInterface::class);
        $step->method('getTime')
            ->willReturn($time);
        $step->method('getMslp')
            ->willReturn($pressure);
        $step->method('getScreenDewPointTemperature')
            ->willReturn($dewPoint);
        $step->method('getScreenTemperature')
            ->willReturn($temperature);
        $step->method('getFeelsLikeTemperature')
            ->willReturn($feelsLike);
        $step->method('getScreenRelativeHumidity')
            ->willReturn($humidity);
        $step->method('getProbOfPrecipitation')
            ->willReturn($precipitation);
        $step->method('getUvIndex')
            ->willReturn($uvIndex);
        $step->method('getVisibility')
            ->willReturn($visibility);
        $step->method('getWindSpeed10m')
            ->willReturn($windSpeed);
        $step->method('getMax10mWindGust')
            ->willReturn($windGust);
        $step->method('getWindDirectionFrom10m')
            ->willReturn($windDirection);
        $step->method('getSignificantWeatherCode')
            ->willReturn($weatherCode);

        return $step;
    }
}
