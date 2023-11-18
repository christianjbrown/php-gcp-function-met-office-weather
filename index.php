<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\CloudFunction\CloudFunction;
use ChristianBrown\CloudFunction\FunctionConfigTransformer;
use ChristianBrown\MetOffice\DataPoint\Forecast\ThreeHourlySiteForecastApi;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();

    $functionConfigTransformer = new FunctionConfigTransformer();
    $configTransformer = new ConfigTransformer($functionConfigTransformer);
    $config = $configTransformer->transform($env);
    $dataTransformer = new DataTransformer();
    $threeHourlyForecastApi = new ThreeHourlySiteForecastApi($config->getApiKey());

    $dataProvider = new DataProvider($config, $dataTransformer, $threeHourlyForecastApi);
    $cloudFunction = new CloudFunction($dataProvider, $config->getFunctionConfig());
    $response = $cloudFunction->run($request);

    return $response;
}
