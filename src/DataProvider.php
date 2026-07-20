<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\MetOffice\CoordinatesInterface;
use ChristianBrown\MetOffice\SiteSpecific\Api\HourlyForecastApiInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\ForecastTimeStepInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\UserFriendlyException\UserFriendlyException;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function array_first;
use function array_last;
use function array_values;
use function time;
use function usort;

final class DataProvider implements DataProviderInterface
{
    private CoordinatesInterface $coordinates;
    private HourlyForecastApiInterface $hourlyForecastApi;
    private int $now;
    private OutputTransformerInterface $outputTransformer;

    public function __construct(HourlyForecastApiInterface $hourlyForecastApi, OutputTransformerInterface $outputTransformer, CoordinatesInterface $coordinates)
    {
        $this->hourlyForecastApi = $hourlyForecastApi;
        $this->outputTransformer = $outputTransformer;
        $this->coordinates = $coordinates;
        $this->now = time();
    }

    /**
     * @return mixed[]
     */
    public function getData(ServerRequestInterface $request): array
    {
        $forecast = $this->hourlyForecastApi->getForecast($this->coordinates);
        $timeSteps = $forecast->getTimeSteps();

        // Split into sequential guards so each failure path is independently
        // reachable for path coverage.
        if ([] === $timeSteps) {
            throw new UserFriendlyException(self::ERROR_NO_FORECAST);
        }

        $step = $this->selectCurrentStep($timeSteps);
        if (!$step instanceof HourlyForecastTimeStepInterface) {
            throw new UserFriendlyException(self::ERROR_NO_FORECAST);
        }

        return $this->outputTransformer->transform($step);
    }

    /**
     * Selects the step whose time is the greatest that is still at or before now
     * (the "current" hour); when every step is in the future, falls back to the
     * earliest step.
     *
     * @param non-empty-array<ForecastTimeStepInterface> $timeSteps
     */
    private function selectCurrentStep(array $timeSteps): ForecastTimeStepInterface
    {
        $sorted = $this->sortByTime($timeSteps);

        $past = array_values(
            array_filter(
                $sorted,
                fn (ForecastTimeStepInterface $step): bool => $step->getTime() <= $this->now
            )
        );

        if ([] === $past) {
            return array_first($sorted);
        }

        return array_last($past);
    }

    /**
     * @param non-empty-array<ForecastTimeStepInterface> $timeSteps
     *
     * @return non-empty-list<ForecastTimeStepInterface>
     */
    private function sortByTime(array $timeSteps): array
    {
        $sorted = array_values($timeSteps);
        usort(
            $sorted,
            static fn (ForecastTimeStepInterface $a, ForecastTimeStepInterface $b): int => $a->getTime() <=> $b->getTime()
        );

        return $sorted;
    }
}
