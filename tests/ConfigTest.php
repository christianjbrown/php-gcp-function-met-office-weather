<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather\Tests;

use ChristianBrown\GcpFunction\FunctionConfigInterface;
use ChristianBrown\MetOfficeWeather\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test(): void
    {
        $functionConfig = self::createStub(FunctionConfigInterface::class);
        $config = new Config($functionConfig, 'test-api-key', 51.5, -0.18);
        self::assertSame($functionConfig, $config->getFunctionConfig());
        self::assertSame('test-api-key', $config->getApiKey());
        self::assertSame(51.5, $config->getLatitude());
        self::assertSame(-0.18, $config->getLongitude());
    }
}
