<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\Database\ClimateMeasurementRecorder;
use ChristianBrown\Database\EntityManagerFactory;
use ChristianBrown\GcpFunction\CloudFunction;
use ChristianBrown\GcpFunction\CloudFunctionInterface;
use ChristianBrown\GcpFunction\FunctionConfigTransformer;
use ChristianBrown\MetOffice\Coordinates;
use ChristianBrown\MetOffice\MetOffice;
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

            $outputTransformer = new OutputTransformer();

            // Record each observed reading to the shared climate-history table.
            // The write is best-effort — DataProvider isolates it so a failure
            // never disturbs the response.
            $entityManager = (new EntityManagerFactory($config->getDatabaseDsn()))->getEntityManager();
            $climateMeasurementRecorder = new ClimateMeasurementRecorder($entityManager);

            $coordinates = new Coordinates($config->getLatitude(), $config->getLongitude());
            $dataProvider = new DataProvider($hourlyApi, $outputTransformer, $climateMeasurementRecorder, $coordinates);

            return new CloudFunction($dataProvider, $config->getFunctionConfig());
        }
    };

    $requestHandler = new RequestHandler($cloudFunctionFactory, $config->getFunctionConfig());

    return $requestHandler->handle($request);
}
