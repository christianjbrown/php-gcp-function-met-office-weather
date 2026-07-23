<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\Database\ClimateMeasurementRecorderInterface;
use ChristianBrown\Database\Entity\MetOfficeWeather;
use ChristianBrown\MetOffice\CoordinatesInterface;
use ChristianBrown\MetOffice\SiteSpecific\Api\HourlyForecastApiInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\ForecastTimeStepInterface;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\UserFriendlyException\UserFriendlyException;
use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function array_filter;
use function array_first;
use function array_last;
use function array_values;
use function error_log;
use function time;
use function usort;

final class DataProvider implements DataProviderInterface
{
    private ClimateMeasurementRecorderInterface $climateMeasurementRecorder;
    private CoordinatesInterface $coordinates;
    private HourlyForecastApiInterface $hourlyForecastApi;
    private int $now;
    private OutputTransformerInterface $outputTransformer;

    public function __construct(HourlyForecastApiInterface $hourlyForecastApi, OutputTransformerInterface $outputTransformer, ClimateMeasurementRecorderInterface $climateMeasurementRecorder, CoordinatesInterface $coordinates)
    {
        $this->hourlyForecastApi = $hourlyForecastApi;
        $this->outputTransformer = $outputTransformer;
        $this->climateMeasurementRecorder = $climateMeasurementRecorder;
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

        $this->recordClimate($step);

        return $this->outputTransformer->transform($step);
    }

    /**
     * Best-effort persistence of the observed temperature/humidity. The write is
     * wrapped so a database failure is logged, never propagated — it must not
     * disturb the function's response.
     */
    private function recordClimate(HourlyForecastTimeStepInterface $step): void
    {
        $temperature = $step->getScreenTemperature();
        $humidity = $step->getScreenRelativeHumidity();

        // Nothing worth recording when the step reports neither value.
        if ([] === array_filter([$temperature, $humidity], static fn (?float $value): bool => null !== $value)) {
            return;
        }

        try {
            $this->climateMeasurementRecorder->record(
                (new MetOfficeWeather())
                    ->setRecordedAt(new DateTimeImmutable())
                    ->setTemperature($temperature)
                    ->setHumidity($humidity)
            );
        } catch (Throwable $exception) {
            error_log('Met Office weather write failed: '.$exception->getMessage());
        }
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
        $sorted = self::sortByTime($timeSteps);

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
    private static function sortByTime(array $timeSteps): array
    {
        $sorted = array_values($timeSteps);
        usort(
            $sorted,
            static fn (ForecastTimeStepInterface $a, ForecastTimeStepInterface $b): int => $a->getTime() <=> $b->getTime()
        );

        return $sorted;
    }
}
