// Type definitions for the Directions plugin JS library.

export interface Destination {
    id: string | number;
    lat: number;
    lng: number;
}

export interface DistanceResult {
    id: string;
    meters?: number;
    seconds?: number;
    ok: boolean;
}

export interface DistancesOptions {
    /** Correlation id echoed back on the DistancesReceived event. */
    id?: string;
    /** Travel mode. Defaults to "automobile". */
    transport?: 'automobile' | 'walking';
}

/**
 * Trigger a real-route distance/ETA calculation. Result arrives via
 * `onDistances`.
 */
export function distances(
    originLat: number,
    originLng: number,
    destinations: Destination[],
    options?: DistancesOptions,
): Promise<{ success: boolean }>;

/** Subscribe to route results. Returns an unsubscribe function. */
export function onDistances(
    callback: (results: DistanceResult[], id: string | null) => void,
): () => void;

declare const _default: {
    distances: typeof distances;
    onDistances: typeof onDistances;
};

export default _default;
