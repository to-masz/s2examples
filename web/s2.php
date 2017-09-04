<?php

declare(strict_types=1);

namespace tomasz\s2examples;

require_once '../vendor/autoload.php';

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;


function output(ResponseInterface $response): void {
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
    echo $response->getBody();
}

$request = ServerRequest::fromGlobals();
$requestParams = $request->getQueryParams();
$action = $requestParams['action'] ?? 'search';

//try {
    switch ($action) {
        case 'region-coverer':
            $action = new RegionCovererAction();
            $response = $action->run($request);
            break;
        case 'search':
            $action = new SearchAction();
            $response = $action->run($request);
            break;

        default:
            $response = new Response(400);
    }
//}
//catch (\Throwable $e) {
////    $response = new Response(500, [], $e->getMessage());
//}

output($response);