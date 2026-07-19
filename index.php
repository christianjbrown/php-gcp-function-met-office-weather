<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\GcpFunction\CloudFunction;
use ChristianBrown\GcpFunction\CloudFunctionInterface;
use ChristianBrown\GcpFunction\FunctionConfigTransformer;
use ChristianBrown\MetOffice\MetOffice;
use ChristianBrown\MetOffice\Transformer\WeatherTypeTransformer;
use ChristianBrown\MetOfficeWeather\CloudFunctionFactoryInterface;
use ChristianBrown\MetOfficeWeather\ConfigInterface;
use ChristianBrown\MetOfficeWeather\ConfigTransformer;
use ChristianBrown\MetOfficeWeather\DataProvider;
use ChristianBrown\MetOfficeWeather\OutputTransformer;
use ChristianBrown\MetOfficeWeather\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();
    $functionConfigTransformer = new FunctionConfigTransformer();
    $configTransformer = new ConfigTransformer($functionConfigTransformer);
    $config = $configTransformer->transform($env);

    // The MetOffice client construction happens inside the factory (not here), so
    // that RequestHandler::handle() wraps it in the same try/catch as
    // CloudFunction::run() and a failure there returns the framework's JSON error
    // envelope rather than escaping as a bare 500.
    $cloudFunctionFactory = new class ($config) implements CloudFunctionFactoryInterface {
        private ConfigInterface $config;

        public function __construct(ConfigInterface $config)
        {
            $this->config = $config;
        }

        public function create(): CloudFunctionInterface
        {
            $config = $this->config;

            $metOffice = new MetOffice();
            $hourlyApi = $metOffice->siteSpecific($config->getApiKey())->getHourlyForecastApi();

            $outputTransformer = new OutputTransformer(new WeatherTypeTransformer());

            $dataProvider = new DataProvider($hourlyApi, $outputTransformer, $config->getLatitude(), $config->getLongitude());

            return new CloudFunction($dataProvider, $config->getFunctionConfig());
        }
    };

    $requestHandler = new RequestHandler($cloudFunctionFactory, $config->getFunctionConfig());

    return $requestHandler->handle($request);
}
