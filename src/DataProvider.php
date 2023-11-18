<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\DataProviderInterface;
use ChristianBrown\MetOffice\DataPoint\Forecast\ThreeHourlySiteForecastApiInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DataProvider implements DataProviderInterface
{
    private ConfigInterface $config;
    private DataTransformerInterface $dataTransformer;
    private ThreeHourlySiteForecastApiInterface $threeHourlySiteForecastApi;

    public function __construct(ConfigInterface $config, DataTransformerInterface $dataTransformer, ThreeHourlySiteForecastApiInterface $threeHourlyForecastApi)
    {
        $this->config = $config;
        $this->dataTransformer = $dataTransformer;
        $this->threeHourlySiteForecastApi = $threeHourlyForecastApi;
    }

    public function getData(ServerRequestInterface $request): array
    {
        $forecast = $this->threeHourlySiteForecastApi->getOnePeriod($this->config->getSiteId());
        $data = $this->dataTransformer->transform($forecast);

        return $data;
    }
}
