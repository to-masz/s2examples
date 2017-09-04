<?php

namespace tomasz\s2examples;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use S2\S2CellId;
use S2\S2LatLng;
use S2\S2LatLngRect;
use S2\S2Point;
use S2\S2RegionCoverer;

class RegionCovererAction implements Action
{
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        $requestBody = $request->getParsedBody();
        foreach (['type', 'maxCells', 'minLevel', 'maxLevel'] as $name) {
            if (empty($requestBody[$name])) {
                throw new \InvalidArgumentException('Missing request parameter '.$name);
            }
        }

        switch ($requestBody['type']) {
            case 'rect':
                $ne = explode(",", $requestBody['ne']);
                $sw = explode(",", $requestBody['sw']);
                $region = new S2LatLngRect(S2LatLng::fromDegrees($ne[0], $sw[1]), S2LatLng::fromDegrees($sw[0], $ne[1]));
                break;

            default:
                throw new \UnexpectedValueException('Type not implemented yet :)');
                break;
        }
        $covering = [];
        $regionCoverer = new S2RegionCoverer();
        $regionCoverer->setMaxCells($requestBody['maxCells']);
        $regionCoverer->setMinLevel($requestBody['minLevel']);
        $regionCoverer->setMaxLevel($requestBody['maxLevel']);
        $regionCoverer->getCovering($region, $covering);

        $cells = array_map(function(S2CellId $cellId) {
            return new S2CellDto($cellId);
        }, $covering);

        return new Response(200, ['Content-type' => 'text/json'], json_encode(['cells' => $cells]));
    }
}