<?php

declare(strict_types=1);

namespace ChristianBrown\MetOfficeWeather;

use ChristianBrown\GcpFunction\DataProviderInterface as BaseDataProviderInterface;

interface DataProviderInterface extends BaseDataProviderInterface
{
    public const string ERROR_NO_FORECAST = 'No weather forecast is currently available for this location.';
}
