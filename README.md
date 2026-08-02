# Directions — NativePHP Mobile plugin

Real-route **distance** and **travel time (ETA)** from one origin to multiple
destinations. On **iOS** it uses **MapKit `MKDirections`** — on-device, no API
key, no cost. On **Android** there is no free on-device routing engine, so the
bridge returns `ok: false` per destination and you should fall back to a
straight-line (haversine) estimate in your app.

- iOS: MapKit `MKDirections` (iOS 15+)
- Android: no-op (returns `ok: false`; use a haversine fallback)

## Installation

```bash
composer require keepcloud/plugin-directions
php artisan native:plugin:register keepcloud/plugin-directions
php artisan native:run   # rebuild the native project
```

## Usage (PHP / Livewire)

The call is **asynchronous**: it returns immediately and the result arrives as
a native event. Handle it with `#[OnNative(DistancesReceived::class)]`.

```php
use Keepcloud\Directions\Facades\Directions;
use Keepcloud\Directions\Events\DistancesReceived;
use Native\Mobile\Attributes\OnNative;

class NearbyStores extends \Livewire\Component
{
    public function findRoutes(): void
    {
        Directions::distances(
            originLat: -22.2171,
            originLng: -49.9501,
            destinations: [
                ['id' => 'store-1', 'lat' => -22.2200, 'lng' => -49.9600],
                ['id' => 'store-2', 'lat' => -22.2100, 'lng' => -49.9400],
            ],
            transport: 'automobile', // or 'walking'
        );
    }

    // The #[OnNative] attribute must live on the component class (not a trait).
    #[OnNative(DistancesReceived::class)]
    public function onDistances(string $results, ?string $id = null): void
    {
        // $results is a JSON string: [{ "id": "store-1", "meters": 1234, "seconds": 180, "ok": true }, ...]
        $rows = json_decode($results, true);
    }
}
```

## Usage (JavaScript / Inertia — Vue or React)

```js
import { distances, onDistances } from 'keepcloud-plugin-directions';

const unsubscribe = onDistances((rows, id) => {
    // rows: [{ id: 'store-1', meters: 1234, seconds: 180, ok: true }, ...]
    console.log(rows, id);
});

await distances(-22.2171, -49.9501, [
    { id: 'store-1', lat: -22.2200, lng: -49.9600 },
    { id: 'store-2', lat: -22.2100, lng: -49.9400 },
], { transport: 'automobile' });

// later: unsubscribe();
```

The JS library ships TypeScript definitions and works across Livewire v3/v4 and
Inertia (Vue/React).

## API

### `Directions::distances(float $originLat, float $originLng, array $destinations, ?string $id = null, string $transport = 'automobile'): bool`

Triggers the calculation. `$destinations` is a list of
`['id' => string|int, 'lat' => float, 'lng' => float]`. Returns `true` when the
native call was dispatched (`false` outside the native runtime).

### Event: `Keepcloud\Directions\Events\DistancesReceived`

| Property | Type | Description |
|---|---|---|
| `results` | `string` | JSON list of `{ id, meters?, seconds?, ok }` |
| `id` | `?string` | Correlation id passed to `distances()` |

Rows with `ok: false` had no route (Android, or an unreachable destination on
iOS) — fall back to a straight-line estimate.

## Permissions

None. MapKit routing needs no location permission (it routes between the
coordinates you pass, not the device's position).

## License

MIT
