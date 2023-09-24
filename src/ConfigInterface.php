<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\FunctionConfigInterface;

interface ConfigInterface
{
    public function getApiKey(): string;

    public function getFunctionConfig(): FunctionConfigInterface;

    public function getSiteId(): int;
}
