<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather\Tests;

use ChristianBrown\MetOffice\Enums\WeatherType;
use ChristianBrown\MetOffice\Enums\WindDirection;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\MetOffice\Transformer\WeatherTypeTransformerInterface;
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
        $step = $this->createStep(self::TIME, 18.7, 17.2, 65.5, 20, 3, 30000, 10.0, 12.0, 90, WeatherType::SUNNY_DAY);

        $weatherTypeTransformer = self::createStub(WeatherTypeTransformerInterface::class);
        $weatherTypeTransformer->method('transform')
            ->willReturn('Sunny day');
        $weatherTypeTransformer->method('transformToEmoji')
            ->willReturn('☀️');

        $transformer = new OutputTransformer($weatherTypeTransformer);

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
            OutputTransformerInterface::KEY_WIND_SPEED => 10.0 * OutputTransformerInterface::METRES_PER_SECOND_TO_MPH,
            OutputTransformerInterface::KEY_WIND_GUST => 12.0 * OutputTransformerInterface::METRES_PER_SECOND_TO_MPH,
            OutputTransformerInterface::KEY_WIND_DIRECTION => WindDirection::fromDegrees(90)->value,
            OutputTransformerInterface::KEY_WIND_DIRECTION_DEGREES => 90,
            OutputTransformerInterface::KEY_TYPE => WeatherType::SUNNY_DAY->value,
            OutputTransformerInterface::KEY_TYPE_STRING => 'Sunny day',
            OutputTransformerInterface::KEY_TYPE_EMOJI => '☀️',
        ];

        self::assertSame($expected, $transformer->transform($step));
    }

    /**
     * @throws Exception
     */
    public function testTransformsMinimalStep(): void
    {
        $step = $this->createStep(self::TIME, null, null, null, null, null, null, null, null, null, null);

        $weatherTypeTransformer = self::createStub(WeatherTypeTransformerInterface::class);

        $transformer = new OutputTransformer($weatherTypeTransformer);

        $expected = [
            OutputTransformerInterface::KEY_VALID_FROM => self::TIME,
            OutputTransformerInterface::KEY_VALID_FROM_ISO8601 => gmdate(DATE_ATOM, self::TIME),
            OutputTransformerInterface::KEY_VALID_TO => self::TIME + OutputTransformerInterface::WINDOW_SECONDS,
            OutputTransformerInterface::KEY_VALID_TO_ISO8601 => gmdate(DATE_ATOM, self::TIME + OutputTransformerInterface::WINDOW_SECONDS),
        ];

        self::assertSame($expected, $transformer->transform($step));
    }

    /**
     * @throws Exception
     */
    public function testWeatherTypeWithoutNameOrEmoji(): void
    {
        $step = $this->createStep(self::TIME, null, null, null, null, null, null, null, null, null, WeatherType::NOT_USED);

        $weatherTypeTransformer = self::createStub(WeatherTypeTransformerInterface::class);
        $weatherTypeTransformer->method('transform')
            ->willReturn(null);
        $weatherTypeTransformer->method('transformToEmoji')
            ->willReturn(null);

        $transformer = new OutputTransformer($weatherTypeTransformer);

        $expected = [
            OutputTransformerInterface::KEY_VALID_FROM => self::TIME,
            OutputTransformerInterface::KEY_VALID_FROM_ISO8601 => gmdate(DATE_ATOM, self::TIME),
            OutputTransformerInterface::KEY_VALID_TO => self::TIME + OutputTransformerInterface::WINDOW_SECONDS,
            OutputTransformerInterface::KEY_VALID_TO_ISO8601 => gmdate(DATE_ATOM, self::TIME + OutputTransformerInterface::WINDOW_SECONDS),
            OutputTransformerInterface::KEY_TYPE => WeatherType::NOT_USED->value,
        ];

        self::assertSame($expected, $transformer->transform($step));
    }

    /**
     * @throws Exception
     */
    private function createStep(int $time, ?float $temperature, ?float $feelsLike, ?float $humidity, ?int $precipitation, ?int $uvIndex, ?int $visibility, ?float $windSpeed, ?float $windGust, ?int $windDirection, ?WeatherType $weatherCode): HourlyForecastTimeStepInterface
    {
        $step = self::createStub(HourlyForecastTimeStepInterface::class);
        $step->method('getTime')
            ->willReturn($time);
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
