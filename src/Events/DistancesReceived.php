<?php

namespace Keepcloud\Directions\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Resultado do cálculo de rotas (Directions.Distances).
 *
 * @property string $results JSON de list<array{id:string,meters?:int,seconds?:int,ok:bool}>
 */
class DistancesReceived
{
    use Dispatchable;

    public function __construct(
        public readonly string $results = '[]',
        public readonly ?string $id = null,
    ) {}
}
