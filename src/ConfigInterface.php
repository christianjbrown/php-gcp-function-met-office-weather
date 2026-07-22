<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\GcpFunction\FunctionConfigInterface;

interface ConfigInterface
{
    public function getApiKey(): string;

    public function getDatabaseDsn(): string;

    public function getFunctionConfig(): FunctionConfigInterface;

    public function getLatitude(): float;

    public function getLongitude(): float;
}
