<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather\Tests;

use ChristianBrown\MetOffice\SiteSpecific\Api\HourlyForecastApiInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\ForecastInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\ForecastTimeStepInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\MetOfficeWeather\DataProvider;
use ChristianBrown\MetOfficeWeather\DataProviderInterface;
use ChristianBrown\MetOfficeWeather\OutputTransformerInterface;
use ChristianBrown\UserFriendlyException\UserFriendlyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

use function time;

#[CoversClass(DataProvider::class)]
final class DataProviderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFallsBackToEarliestWhenAllStepsAreInTheFuture(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $now = time();

        $earliest = $this->createHourlyStep($now + 3600);
        $latest = $this->createHourlyStep($now + 7200);

        // Unsorted so the earliest-selection depends on the sort, not input order.
        $forecast = $this->createForecast([$latest, $earliest]);

        $hourlyForecastApi = self::createStub(HourlyForecastApiInterface::class);
        $hourlyForecastApi->method('getForecast')
            ->willReturn($forecast);

        $outputTransformer = self::createMock(OutputTransformerInterface::class);
        $outputTransformer->expects(self::once())
            ->method('transform')
            ->with($earliest)
            ->willReturn(['test-output']);

        $dataProvider = new DataProvider($hourlyForecastApi, $outputTransformer, 51.5, -0.18);

        self::assertSame(['test-output'], $dataProvider->getData($request));
    }

    /**
     * @throws Exception
     */
    public function testSelectsCurrentStep(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $now = time();

        $olderPast = $this->createHourlyStep($now - 7200);
        $currentPast = $this->createHourlyStep($now - 3600);
        $future = $this->createHourlyStep($now + 3600);

        // Deliberately unsorted so the sort step is exercised.
        $forecast = $this->createForecast([$future, $olderPast, $currentPast]);

        $hourlyForecastApi = self::createMock(HourlyForecastApiInterface::class);
        $hourlyForecastApi->expects(self::once())
            ->method('getForecast')
            ->with(51.5, -0.18)
            ->willReturn($forecast);

        $outputTransformer = self::createMock(OutputTransformerInterface::class);
        $outputTransformer->expects(self::once())
            ->method('transform')
            ->with($currentPast)
            ->willReturn(['test-output']);

        $dataProvider = new DataProvider($hourlyForecastApi, $outputTransformer, 51.5, -0.18);

        self::assertSame(['test-output'], $dataProvider->getData($request));
    }

    /**
     * @throws Exception
     */
    public function testThrowsWhenNoStepsAvailable(): void
    {
        $request = self::createStub(ServerRequestInterface::class);

        $forecast = $this->createForecast([]);

        $hourlyForecastApi = self::createStub(HourlyForecastApiInterface::class);
        $hourlyForecastApi->method('getForecast')
            ->willReturn($forecast);

        $outputTransformer = self::createStub(OutputTransformerInterface::class);

        $dataProvider = new DataProvider($hourlyForecastApi, $outputTransformer, 51.5, -0.18);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage(DataProviderInterface::ERROR_NO_FORECAST);

        $dataProvider->getData($request);
    }

    /**
     * @throws Exception
     */
    public function testThrowsWhenSelectedStepIsNotHourly(): void
    {
        $request = self::createStub(ServerRequestInterface::class);
        $now = time();

        $step = self::createStub(ForecastTimeStepInterface::class);
        $step->method('getTime')
            ->willReturn($now - 3600);

        $forecast = $this->createForecast([$step]);

        $hourlyForecastApi = self::createStub(HourlyForecastApiInterface::class);
        $hourlyForecastApi->method('getForecast')
            ->willReturn($forecast);

        $outputTransformer = self::createStub(OutputTransformerInterface::class);

        $dataProvider = new DataProvider($hourlyForecastApi, $outputTransformer, 51.5, -0.18);

        $this->expectException(UserFriendlyException::class);
        $this->expectExceptionMessage(DataProviderInterface::ERROR_NO_FORECAST);

        $dataProvider->getData($request);
    }

    /**
     * @param ForecastTimeStepInterface[] $timeSteps
     *
     * @throws Exception
     */
    private function createForecast(array $timeSteps): ForecastInterface
    {
        $forecast = self::createStub(ForecastInterface::class);
        $forecast->method('getTimeSteps')
            ->willReturn($timeSteps);

        return $forecast;
    }

    /**
     * @throws Exception
     */
    private function createHourlyStep(int $time): HourlyForecastTimeStepInterface
    {
        $step = self::createStub(HourlyForecastTimeStepInterface::class);
        $step->method('getTime')
            ->willReturn($time);

        return $step;
    }
}
