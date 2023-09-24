<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\FunctionConfigInterface;

final class Config implements ConfigInterface
{
    private string $apiKey;
    private FunctionConfigInterface $functionConfig;
    private int $siteId;

    public function __construct(FunctionConfigInterface $functionConfig, int $siteId, string $apiKey)
    {
        $this->functionConfig = $functionConfig;
        $this->siteId = $siteId;
        $this->apiKey = $apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getFunctionConfig(): FunctionConfigInterface
    {
        return $this->functionConfig;
    }

    public function getSiteId(): int
    {
        return $this->siteId;
    }
}
