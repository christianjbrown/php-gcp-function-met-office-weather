<?php

declare(strict_types=1);

use ChristianBrown\MetOffice\DataPoint\Forecast\ThreeHourlySiteForecastApiInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DataProvider implements DataProviderInterface
{
    private ConfigInterface $config;
    private ForecastTransformerInterface $forecastTransformer;
    private ThreeHourlySiteForecastApiInterface $threeHourlySiteForecastApi;

    public function __construct(ConfigInterface $config, ForecastTransformerInterface $forecastTransformer, ThreeHourlySiteForecastApiInterface $threeHourlyForecastApi)
    {
        $this->config = $config;
        $this->forecastTransformer = $forecastTransformer;
        $this->threeHourlySiteForecastApi = $threeHourlyForecastApi;
    }

    public function getData(ServerRequestInterface $request): array
    {
        $forecast = $this->threeHourlySiteForecastApi->getOnePeriod($this->config->getSiteId());
        $data = $this->forecastTransformer->transform($forecast);

        return $data;
    }
}
