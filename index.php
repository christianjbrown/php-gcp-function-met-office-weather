<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

function run(ServerRequestInterface $request): ResponseInterface
{
    $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Content-Type' => 'application/json; charset=utf-8',
    ];

    try {
        $bodyJson = [
            'version' => getenv('K_REVISION'),
            'timestamp' => time(),
        ];

        if (!empty(getenv('MET_OFFICE_API_KEY') && is_string(getenv('MET_OFFICE_API_KEY'))) && !empty(getenv('MET_OFFICE_SITE_ID') && is_string(getenv('MET_OFFICE_SITE_ID')))) {
            $apiKey = getenv('MET_OFFICE_API_KEY');
            $siteId = getenv('MET_OFFICE_SITE_ID');
            $time = time();
            $roundedDownTo3Hrs = ($time - ($time % 10800));
            $date = gmdate('Y-m-d', $roundedDownTo3Hrs).'T'.gmdate('H', $roundedDownTo3Hrs).'Z';

            $metUrl = sprintf('http://datapoint.metoffice.gov.uk/public/data/val/wxfcs/all/json/%s?res=3hourly&time=%s&key=%s', $siteId, $date, $apiKey);
            $rawMetData = file_get_contents($metUrl);
            $metData = json_decode($rawMetData, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($metData) && !empty($metData['SiteRep']['DV']['Location']['Period']['Rep']) && is_array($metData['SiteRep']['DV']['Location']['Period']['Rep'])) {
                $weatherData = $metData['SiteRep']['DV']['Location']['Period']['Rep'];
                $feelsLike = null;
                $temp = null;
                $humidity = null;
                $precipitation = null;
                if (!empty($weatherData['F']) && is_numeric($weatherData['F'])) {
                    $feelsLike = (float)$weatherData['F'];
                }
                if (!empty($weatherData['T']) && is_numeric($weatherData['T'])) {
                    $temp = (float)$weatherData['T'];
                }
                if (!empty($weatherData['H']) && is_numeric($weatherData['H'])) {
                    $humidity = (int)$weatherData['H'];
                }
                if (!empty($weatherData['H']) && is_numeric($weatherData['H'])) {
                    $humidity = (int)$weatherData['H'];
                }
                if (!empty($weatherData['Pp']) && is_numeric($weatherData['Pp'])) {
                    $precipitation = (int)$weatherData['Pp'];
                }
                $bodyJson['data'] = [
                    'feels_like' => $feelsLike,
                    'temp' => $temp,
                    'humidity' => $humidity,
                    'precipitation' => $precipitation,
                    'valid_from' => $roundedDownTo3Hrs,
                    'valid_to' => $roundedDownTo3Hrs+10800,
                ];
            }
        }

        $body = json_encode($bodyJson, JSON_THROW_ON_ERROR);
        $response = new Response(200, $headers, $body);
    } catch (Throwable $e) {
        $response = new Response(500, $headers, 'An error occurred! :(');
    }

    return $response;
}
