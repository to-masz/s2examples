<?php

namespace tomasz\s2examples;

use Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SearchAction implements Action
{
    private $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([[
                'host' => '127.0.0.1',
                'port' => 9200,
                'user' => 'elastic',
                'pass' => 'changeme'
            ]])
            ->build();
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $criteria = $request->getParsedBody();
        $title = $criteria['q'] ?? '';
        $s2cells = $criteria['s2'] ?? '';

        $must = [];
        if (!empty($title)) {
            $must[] = [ 'match' => [ 'title' => $title ] ];
        }
        if (!empty($s2cells)) {
            $must[] = [ 'match' => [ 's2cells' => ['query' => $s2cells, 'operator' => 'OR'] ] ];
        }

        $params = [
            'index' => 'example',
            'type' => 'my_type',
            'body' => [
                'size' => 1000,
                'query' => [
                    'bool' => [
                        'must' => $must
                    ]
                ]
            ]
        ];

        $response = $this->client->search($params);
        $results = [];
        foreach ($response['hits']['hits'] as $data) {
            $results[] = [
                'title' => $data['_source']['title'],
                'lat' => $data['_source']['lat'],
                'lng' => $data['_source']['lng'],
            ];
        }


        return new Response(200, ['Content-type' => 'text/json'], json_encode(['results' => $results]));
    }
}