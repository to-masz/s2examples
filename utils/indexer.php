<?php

namespace tomasz\s2examples;

require '../vendor/autoload.php';

use Elasticsearch\ClientBuilder;
use S2\S2CellId;
use S2\S2LatLng;

$client = ClientBuilder::create()
    ->setHosts([[
        'host' => '127.0.0.1',
        'port' => 9200,
        'user' => 'elastic',
        'pass' => 'changeme'
    ]])
    ->build();


// delete index
$deleteParams = [
    'index' => 'example'
];
if ($client->indices()->exists($deleteParams)) {

    $response = $client->indices()->delete($deleteParams);
}


// create index
$params = [
    'index' => 'example',
    'body' => [
        'mappings' => [
            'my_type' => [
                'properties' => [
                    'title' => [
                        'type' => 'text',
                    ],
                    's2cells' => [
                        'type' => 'text',
                        'analyzer' => 'whitespace'
                    ]
                ]
            ]

        ]
    ]
];
$response = $client->indices()->create($params);


// index data
$fp = fopen('../data/data.tsv', 'r');
$params = ['body' => []];
$k = 0;
while (!feof($fp)) {
    $line = trim(fgets($fp));
    if (empty($line)) {
        continue;
    }
    $data = explode("\t", $line);

    $params['body'][] = [
        'index' => [
            '_index' => 'example',
            '_type' => 'my_type',
            '_id' => ++$k,
        ]
    ];

    $s2 = S2CellId::fromLatLng(S2LatLng::fromDegrees($data[1], $data[2]));
    $s2cell = $s2->toToken();
    $s2cells = [$s2cell];
    while (!$s2->isFace()) {
        $s2 = $s2->parent();
        $s2cells[] = $s2->toToken();
    }

    $params['body'][] = [
        'title' => $data[0],
        'lat' => $data[1],
        'lng' => $data[2],
        's2cell' => $s2cell,
        's2cells' => implode(' ', $s2cells),
    ];

    if (count($params['body']) % 1000 == 0) {
        $responses = $client->bulk($params);
        $params = ['body' => []];
        unset($responses);
        echo '.';
    }
}
if (!empty($params['body'])) {
    $responses = $client->bulk($params);
}
fclose($fp);