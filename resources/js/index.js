// Directions plugin — JavaScript library for NativePHP Mobile.
//
// Wraps the `Directions.Distances` bridge function so the plugin can be used
// from Inertia (Vue/React) the same way the PHP facade is used from Livewire.
// The call is asynchronous: it returns immediately and the result arrives as a
// native event — subscribe with `onDistances()`.

/**
 * Low-level bridge call. Guarded so it is a no-op outside the native runtime.
 * @param {string} method
 * @param {Record<string, unknown>} params
 * @returns {Promise<any>}
 */
async function bridgeCall(method, params = {}) {
    const response = await fetch('/_native/api/call', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ method, params }),
    });

    return response.json();
}

/**
 * Trigger a real-route distance/ETA calculation from one origin to many
 * destinations. The result is delivered via the `DistancesReceived` event —
 * listen with `onDistances()`.
 *
 * @param {number} originLat
 * @param {number} originLng
 * @param {Array<{ id: string | number, lat: number, lng: number }>} destinations
 * @param {{ id?: string, transport?: 'automobile' | 'walking' }} [options]
 * @returns {Promise<{ success: boolean }>}
 */
export async function distances(originLat, originLng, destinations, options = {}) {
    await bridgeCall('Directions.Distances', {
        id: options.id ?? null,
        origin: { lat: originLat, lng: originLng },
        destinations,
        transport: options.transport ?? 'automobile',
        event: 'Keepcloud\\Directions\\Events\\DistancesReceived',
    });

    return { success: true };
}

/**
 * Subscribe to route results. The callback receives the parsed rows
 * (already JSON-decoded) and the optional correlation id.
 *
 * @param {(results: Array<{ id: string, meters?: number, seconds?: number, ok: boolean }>, id: string | null) => void} callback
 * @returns {() => void} unsubscribe
 */
export function onDistances(callback) {
    const handler = (event) => {
        const name = String(event?.detail?.event ?? '').replace(/^\\+/, '');
        if (!name.endsWith('Directions\\Events\\DistancesReceived')) {
            return;
        }

        const payload = event.detail.payload ?? {};
        let rows = [];
        try {
            rows = JSON.parse(payload.results ?? '[]');
        } catch (_) {
            rows = [];
        }

        callback(rows, payload.id ?? null);
    };

    document.addEventListener('native-event', handler);

    return () => document.removeEventListener('native-event', handler);
}

export default { distances, onDistances };
