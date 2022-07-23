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
        $body = json_encode($bodyJson, JSON_THROW_ON_ERROR);
        $response = new Response(200, $headers, $body);
    } catch (Throwable $e) {
        $response = new Response(500, $headers, 'An error occurred! :(');
    }

    return $response;
}
