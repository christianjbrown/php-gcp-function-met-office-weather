<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\GcpFunction\FunctionConfigInterface;

final class Config implements ConfigInterface
{
    private string $apiKey;
    private string $databaseDsn;
    private FunctionConfigInterface $functionConfig;
    private float $latitude;
    private float $longitude;

    public function __construct(FunctionConfigInterface $functionConfig, string $apiKey, float $latitude, float $longitude, string $databaseDsn)
    {
        $this->functionConfig = $functionConfig;
        $this->apiKey = $apiKey;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->databaseDsn = $databaseDsn;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getDatabaseDsn(): string
    {
        return $this->databaseDsn;
    }

    public function getFunctionConfig(): FunctionConfigInterface
    {
        return $this->functionConfig;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }
}
