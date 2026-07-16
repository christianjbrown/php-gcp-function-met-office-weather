<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\GcpFunction\CloudFunction;
use ChristianBrown\GcpFunction\FunctionConfigTransformer;
use ChristianBrown\MetOffice\MetOffice;
use ChristianBrown\MetOffice\Transformer\WeatherTypeTransformer;
use ChristianBrown\MetOfficeWeather\ConfigTransformer;
use ChristianBrown\MetOfficeWeather\DataProvider;
use ChristianBrown\MetOfficeWeather\OutputTransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();
    $functionConfigTransformer = new FunctionConfigTransformer();
    $configTransformer = new ConfigTransformer($functionConfigTransformer);
    $config = $configTransformer->transform($env);

    $metOffice = new MetOffice();
    $hourlyApi = $metOffice->siteSpecific($config->getApiKey())->getHourlyForecastApi();

    $outputTransformer = new OutputTransformer(new WeatherTypeTransformer());

    $dataProvider = new DataProvider($hourlyApi, $outputTransformer, $config->getLatitude(), $config->getLongitude());
    $cloudFunction = new CloudFunction($dataProvider, $config->getFunctionConfig());
    $response = $cloudFunction->run($request);

    return $response;
}
