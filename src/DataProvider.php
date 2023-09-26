<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\DataProviderInterface;
use ChristianBrown\MetOffice\DataPoint\Forecast\ThreeHourlySiteForecastApi;
use Psr\Http\Message\ServerRequestInterface;

final class DataProvider implements DataProviderInterface
{
    private ConfigTransformerInterface $configTransformer;
    private DataTransformerInterface $dataTransformer;

    public function __construct()
    {
        $this->configTransformer = new ConfigTransformer();
        $this->dataTransformer = new DataTransformer();
    }

    public function getData(array $env, ServerRequestInterface $request): array
    {
        $config = $this->configTransformer->transform($env);
        $threeHourlyForecastApi = new ThreeHourlySiteForecastApi($config->getApiKey());
        $forecast = $threeHourlyForecastApi->getOnePeriod($config->getSiteId());
        $data = $this->dataTransformer->transform($forecast);

        return $data;
    }
}
