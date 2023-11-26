<?php

declare(strict_types=1);

namespace ChristianBrown\GetMetOfficeTemps;

use ChristianBrown\CloudFunction\FunctionConfigInterface;

interface ConfigInterface
{
    public function getApiKey(): string;

    public function getFunctionConfig(): FunctionConfigInterface;

    public function getSiteId(): int;
}
