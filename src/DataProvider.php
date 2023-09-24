<?php

declare(strict_types=1);

use ChristianBrown\CloudFunction\DataProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DataProvider implements DataProviderInterface
{
    private ConfigTransformerInterface $configTransformer;

    public function __construct()
    {
        $this->configTransformer = new ConfigTransformer();
    }

    public function getData(array $env, ServerRequestInterface $request): array
    {
        $data = [];

        $config = $this->configTransformer->transform($env);

        $time = time();
        $roundedDownTo3Hrs = ($time - ($time % 10800));
        $roundedUpTo3Hrs = ($roundedDownTo3Hrs + 10800);
        $roundedDownDiff = $time - $roundedDownTo3Hrs;
        $roundedUpDiff = $roundedUpTo3Hrs - $time;
        if ($roundedDownDiff > $roundedUpDiff) {
            $adjustedTime = $roundedUpTo3Hrs;
        } else {
            $adjustedTime = $roundedDownTo3Hrs;
        }

        $date = gmdate('Y-m-d\TH\Z', $adjustedTime);

        $metUrl = sprintf('http://datapoint.metoffice.gov.uk/public/data/val/wxfcs/all/json/%s?res=3hourly&time=%s&key=%s', $config->getSiteId(), $date, $config->getApiKey());
        $rawMetData = file_get_contents($metUrl);
        $metData = json_decode($rawMetData, true, 512, \JSON_THROW_ON_ERROR);
        if (is_array($metData) && !empty($metData['SiteRep']['DV']['Location']['Period']['Rep']) && is_array($metData['SiteRep']['DV']['Location']['Period']['Rep'])) {
            $weatherData = $metData['SiteRep']['DV']['Location']['Period']['Rep'];
            $tempFeelsLike = null;
            $temp = null;
            $humidity = null;
            $precipitation = null;
            if (isset($weatherData['F']) && is_numeric($weatherData['F'])) {
                $tempFeelsLike = (float) $weatherData['F'];
            }
            if (isset($weatherData['T']) && is_numeric($weatherData['T'])) {
                $temp = (float) $weatherData['T'];
            }
            if (isset($weatherData['H']) && is_numeric($weatherData['H'])) {
                $humidity = (int) $weatherData['H'];
            }
            if (isset($weatherData['Pp']) && is_numeric($weatherData['Pp'])) {
                $precipitation = (int) $weatherData['Pp'];
            }
            $data = [
                'temp_feels_like' => $tempFeelsLike,
                'temp' => $temp,
                'humidity' => $humidity,
                'precipitation' => $precipitation,
                'valid_from' => $adjustedTime,
                'valid_to' => $adjustedTime + 10800,
            ];
        }

        return $data;
    }
}
