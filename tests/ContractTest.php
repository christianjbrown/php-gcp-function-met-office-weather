<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather\Tests;

use ChristianBrown\GcpFunction\CloudFunction;
use ChristianBrown\GcpFunction\DataProviderInterface as BaseDataProviderInterface;
use ChristianBrown\GcpFunction\FunctionConfig;
use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\MetOffice\Enums\WeatherType;
use ChristianBrown\MetOffice\SiteSpecific\Model\HourlyForecastTimeStepInterface;
use ChristianBrown\MetOfficeWeather\CloudFunctionFactoryInterface;
use ChristianBrown\MetOfficeWeather\DataProviderInterface;
use ChristianBrown\MetOfficeWeather\OutputTransformer;
use ChristianBrown\MetOfficeWeather\RequestHandler;
use ChristianBrown\UserFriendlyException\UserFriendlyException;
use GuzzleHttp\Psr7\ServerRequest;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function dirname;

/**
 * Validates the function's real PSR-7 responses against the committed
 * `openapi.yaml` (generated from the `#[OA\...]` attributes). If a response ever
 * drifts from the contract — an unexpected key, a wrong type, a missing required
 * field — `ResponseValidator::validate()` throws and the suite fails.
 */
#[CoversClass(RequestHandler::class)]
#[UsesClass(OutputTransformer::class)]
final class ContractTest extends TestCase
{
    private const string ORIGIN = 'https://example.com';
    private const string REVISION = 'contract-test-revision';
    private const int TIME = 1752580800;
    private ResponseValidator $responseValidator;

    protected function setUp(): void
    {
        $this->responseValidator = (new ValidatorBuilder())
            ->fromYamlFile(dirname(__DIR__).'/openapi.yaml')
            ->getResponseValidator();
    }

    /**
     * @throws Exception
     */
    public function testNoForecastErrorResponseMatchesContract(): void
    {
        $dataProvider = self::createStub(BaseDataProviderInterface::class);
        $dataProvider->method('getData')
            ->willThrowException(new UserFriendlyException(DataProviderInterface::ERROR_NO_FORECAST));

        $response = $this->buildResponse($this->unauthenticatedConfig(), $dataProvider, new ServerRequest('GET', '/'));

        $this->responseValidator->validate(new OperationAddress('/', 'get'), $response);
        self::assertSame(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testSuccessFullPayloadMatchesContract(): void
    {
        $step = $this->createStep(18.7, 17.2, 65.5, 20, 3, 30000, 10.0, 12.0, 90, WeatherType::SUNNY_DAY, 101320, 12.3);
        $data = (new OutputTransformer())->transform($step);

        $dataProvider = self::createStub(BaseDataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn($data);

        $request = new ServerRequest('GET', '/', ['Origin' => self::ORIGIN]);
        $response = $this->buildResponse($this->unauthenticatedConfig(), $dataProvider, $request);

        $this->responseValidator->validate(new OperationAddress('/', 'get'), $response);
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testSuccessMinimalPayloadMatchesContract(): void
    {
        $step = $this->createStep(null, null, null, null, null, null, null, null, null, null);
        $data = (new OutputTransformer())->transform($step);

        $dataProvider = self::createStub(BaseDataProviderInterface::class);
        $dataProvider->method('getData')
            ->willReturn($data);

        $response = $this->buildResponse($this->unauthenticatedConfig(), $dataProvider, new ServerRequest('GET', '/'));

        $this->responseValidator->validate(new OperationAddress('/', 'get'), $response);
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUnauthorizedResponseMatchesContract(): void
    {
        $config = (new FunctionConfig(self::REVISION))
            ->setRequiredHeaderKey('X-Request-Auth')
            ->setRequiredHeaderValue('secret');

        $dataProvider = self::createStub(BaseDataProviderInterface::class);

        $response = $this->buildResponse($config, $dataProvider, new ServerRequest('GET', '/'));

        $this->responseValidator->validate(new OperationAddress('/', 'get'), $response);
        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    private function buildResponse(FunctionConfigInterface $config, BaseDataProviderInterface $dataProvider, ServerRequestInterface $request): ResponseInterface
    {
        $cloudFunction = new CloudFunction($dataProvider, $config);

        $cloudFunctionFactory = self::createStub(CloudFunctionFactoryInterface::class);
        $cloudFunctionFactory->method('create')
            ->willReturn($cloudFunction);

        $requestHandler = new RequestHandler($cloudFunctionFactory, $config);

        return $requestHandler->handle($request);
    }

    /**
     * @throws Exception
     */
    private function createStep(?float $temperature, ?float $feelsLike, ?float $humidity, ?int $precipitation, ?int $uvIndex, ?int $visibility, ?float $windSpeed, ?float $windGust, ?int $windDirection, ?WeatherType $weatherCode, ?int $pressure = null, ?float $dewPoint = null): HourlyForecastTimeStepInterface
    {
        $step = self::createStub(HourlyForecastTimeStepInterface::class);
        $step->method('getTime')
            ->willReturn(self::TIME);
        $step->method('getMslp')
            ->willReturn($pressure);
        $step->method('getScreenDewPointTemperature')
            ->willReturn($dewPoint);
        $step->method('getScreenTemperature')
            ->willReturn($temperature);
        $step->method('getFeelsLikeTemperature')
            ->willReturn($feelsLike);
        $step->method('getScreenRelativeHumidity')
            ->willReturn($humidity);
        $step->method('getProbOfPrecipitation')
            ->willReturn($precipitation);
        $step->method('getUvIndex')
            ->willReturn($uvIndex);
        $step->method('getVisibility')
            ->willReturn($visibility);
        $step->method('getWindSpeed10m')
            ->willReturn($windSpeed);
        $step->method('getMax10mWindGust')
            ->willReturn($windGust);
        $step->method('getWindDirectionFrom10m')
            ->willReturn($windDirection);
        $step->method('getSignificantWeatherCode')
            ->willReturn($weatherCode);

        return $step;
    }

    private function unauthenticatedConfig(): FunctionConfigInterface
    {
        return (new FunctionConfig(self::REVISION))
            ->setAllowUnauthenticated(true)
            ->setRequiredOrigin(self::ORIGIN)
            ->setUseCacheTtl(900)
            ->setUseCacheButRequestTtl(600)
            ->setUseCacheIfErrorTtl(3600);
    }
}
