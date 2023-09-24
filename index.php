<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

use ChristianBrown\CloudFunction\CloudFunction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function run(ServerRequestInterface $request): ResponseInterface
{
    $env = getenv();
    $dataProvider = new DataProvider();
    $cloudFunction = new CloudFunction($dataProvider, $env);
    $response = $cloudFunction->run($request);

    return $response;
}
