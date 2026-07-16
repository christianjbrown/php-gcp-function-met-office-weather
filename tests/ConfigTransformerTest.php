<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather\Tests;

use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\GcpFunction\FunctionConfigTransformerInterface;
use ChristianBrown\MetOfficeWeather\Config;
use ChristianBrown\MetOfficeWeather\ConfigTransformer;
use ChristianBrown\MetOfficeWeather\ConfigTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function sprintf;

#[CoversClass(Config::class)]
#[CoversClass(ConfigTransformer::class)]
final class ConfigTransformerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testTransform(): void
    {
        $env = [
            ConfigTransformerInterface::ENV_API_KEY => 'test-api-key',
            ConfigTransformerInterface::ENV_LATITUDE => '51.546111',
            ConfigTransformerInterface::ENV_LONGITUDE => '-0.183111',
        ];

        $functionConfig = self::createStub(FunctionConfigInterface::class);

        $functionConfigTransformer = self::createMock(FunctionConfigTransformerInterface::class);
        $functionConfigTransformer->expects(self::once())
            ->method('transform')
            ->with($env)
            ->willReturn($functionConfig);

        $transformer = new ConfigTransformer($functionConfigTransformer);
        $actual = $transformer->transform($env);

        self::assertSame('test-api-key', $actual->getApiKey());
        self::assertSame(51.546111, $actual->getLatitude());
        self::assertSame(-0.183111, $actual->getLongitude());
        self::assertSame($functionConfig, $actual->getFunctionConfig());
    }

    /**
     * @param mixed[] $env
     *
     * @throws Exception
     */
    #[TestWith([[]])]
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => null]])]
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 42]])]
    public function testTransformWithMissingApiKey(array $env): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('%s not set or not a string', ConfigTransformerInterface::ENV_API_KEY));

        $functionConfigTransformer = self::createStub(FunctionConfigTransformerInterface::class);

        $transformer = new ConfigTransformer($functionConfigTransformer);
        $transformer->transform($env);
    }

    /**
     * @param mixed[] $env
     *
     * @throws Exception
     */
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 'test-api-key']])]
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 'test-api-key', ConfigTransformerInterface::ENV_LATITUDE => null]])]
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 'test-api-key', ConfigTransformerInterface::ENV_LATITUDE => 'not-numeric']])]
    public function testTransformWithMissingLatitude(array $env): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('%s not set or not numeric', ConfigTransformerInterface::ENV_LATITUDE));

        $functionConfigTransformer = self::createStub(FunctionConfigTransformerInterface::class);

        $transformer = new ConfigTransformer($functionConfigTransformer);
        $transformer->transform($env);
    }

    /**
     * @param mixed[] $env
     *
     * @throws Exception
     */
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 'test-api-key', ConfigTransformerInterface::ENV_LATITUDE => '51.5']])]
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 'test-api-key', ConfigTransformerInterface::ENV_LATITUDE => '51.5', ConfigTransformerInterface::ENV_LONGITUDE => null]])]
    #[TestWith([[ConfigTransformerInterface::ENV_API_KEY => 'test-api-key', ConfigTransformerInterface::ENV_LATITUDE => '51.5', ConfigTransformerInterface::ENV_LONGITUDE => 'not-numeric']])]
    public function testTransformWithMissingLongitude(array $env): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('%s not set or not numeric', ConfigTransformerInterface::ENV_LONGITUDE));

        $functionConfigTransformer = self::createStub(FunctionConfigTransformerInterface::class);

        $transformer = new ConfigTransformer($functionConfigTransformer);
        $transformer->transform($env);
    }
}
