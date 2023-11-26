<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\CloudFunction\CloudFunction;
use ChristianBrown\CloudFunction\FunctionConfigTransformer;
use ChristianBrown\GetMetOfficeTemps\ConfigTransformer;
use ChristianBrown\GetMetOfficeTemps\DataProvider;
use ChristianBrown\GetMetOfficeTemps\ForecastTransformer;
use ChristianBrown\MetOffice\DataPoint\Forecast\ThreeHourlySiteForecastApi;
use ChristianBrown\MetOffice\DataPoint\Forecast\Transformer\WeatherTypeTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();
    $functionConfigTransformer = new FunctionConfigTransformer();
    $configTransformer = new ConfigTransformer($functionConfigTransformer);
    $config = $configTransformer->transform($env);

    $weatherTypeTransformer = new WeatherTypeTransformer();
    $forecastTransformer = new ForecastTransformer($weatherTypeTransformer);
    $threeHourlyForecastApi = new ThreeHourlySiteForecastApi($config->getApiKey());

    $dataProvider = new DataProvider($config, $forecastTransformer, $threeHourlyForecastApi);
    $cloudFunction = new CloudFunction($dataProvider, $config->getFunctionConfig());
    $response = $cloudFunction->run($request);

    return $response;
}
