<?php

namespace Keepcloud\Directions;

use Keepcloud\Directions\Events\DistancesReceived;

class Directions
{
    /**
     * Calcula distância e ETA por rota da origem até vários destinos.
     * Assíncrono: o resultado chega no evento {@see DistancesReceived}
     * (escute com #[OnNative(DistancesReceived::class)]).
     *
     * @param  list<array{id:int|string,lat:float,lng:float}>  $destinations
     * @param  string  $transport  automobile|walking
     * @return bool true se a chamada nativa foi disparada
     */
    public function distances(float $originLat, float $originLng, array $destinations, ?string $id = null, string $transport = 'automobile'): bool
    {
        if (! function_exists('nativephp_call')) {
            return false;
        }

        nativephp_call('Directions.Distances', json_encode([
            'id' => $id,
            'origin' => ['lat' => $originLat, 'lng' => $originLng],
            'destinations' => array_values($destinations),
            'transport' => $transport,
            'event' => DistancesReceived::class,
        ]));

        return true;
    }
}
