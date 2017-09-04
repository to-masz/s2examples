<?php


namespace tomasz\s2examples;


use S2\S2Cell;
use S2\S2CellId;
use S2\S2LatLng;

class S2CellDto implements \JsonSerializable
{
    private $cellId;

    public function __construct(S2CellId $cellId, ?int $level = null)
    {
        $this->cellId = $cellId;
        if ($level !== null) {
            $this->cellId = $this->cellId->parent($level);
        }
    }

    function jsonSerialize()
    {
        $cell = new S2Cell($this->cellId);
        $coordinates = [];
        for ($i = 0; $i < 4; $i++) {
            $p = $cell->getVertex($i);
            $coordinates[] = [S2LatLng::latitude($p)->degrees(), S2LatLng::longitude($p)->degrees()];
        }

        return [
            'id' => $this->cellId->toToken(),
            'level' => $this->cellId->level(),
            'coordinates' => $coordinates,
        ];
    }
}