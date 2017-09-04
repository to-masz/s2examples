<?php

namespace tomasz\s2examples;

use S2\S2CellId;
use S2\S2LatLng;

require '../vendor/autoload.php';

$lat = 52.4049292;
$lng = 16.9096754;

$s2CellId = S2CellId::fromLatLng(S2LatLng::fromDegrees($lat, $lng));
echo $s2CellId->id() . PHP_EOL;
echo decbin($s2CellId->id()) . PHP_EOL;
echo $s2CellId->toToken() . PHP_EOL;

echo PHP_EOL;
echo 'Parent lvl 10: ' . PHP_EOL;

$s2CellIdLvl10 = $s2CellId->parent(10);
echo $s2CellIdLvl10->id() . PHP_EOL;
echo decbin($s2CellIdLvl10->id()) . PHP_EOL;
echo $s2CellIdLvl10->toToken() . PHP_EOL;

echo PHP_EOL;
echo 'Neighbours: ' . PHP_EOL;

$neighbors = [];
$s2CellId->getAllNeighbors($s2CellId->level(), $neighbors);
foreach ($neighbors as $neighbor) {
    echo $neighbor->id() . PHP_EOL;
    echo decbin($neighbor->id()) . PHP_EOL;
    echo $neighbor->toToken() . PHP_EOL;
    echo PHP_EOL;
}