import Foundation
import MapKit

// MARK: - Directions Function Namespace

/// Distância e tempo de viagem por rota real usando MapKit (MKDirections).
/// On-device, sem chave de API e sem custo. Namespace: "Directions.*"
enum DirectionsFunctions {

    // MARK: - Directions.Distances

    /// Calcula a distância e o ETA por rota da origem até cada destino.
    /// Parâmetros:
    ///   - origin: { lat: number, lng: number }
    ///   - destinations: [ { id: string, lat: number, lng: number } ]
    ///   - transport: (opcional) "automobile" | "walking". Padrão: automobile
    ///   - id: (opcional) string de correlação, devolvida no evento
    ///   - event: (opcional) classe de evento a despachar
    /// Evento:
    ///   - Dispara "Nativephp\\Directions\\Events\\DistancesReceived" com
    ///     { id, results } — results é um JSON string de
    ///     [ { id, meters, seconds, ok } ].
    final class Distances: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let reqId = parameters["id"] as? String
            let event = parameters["event"] as? String
                ?? "Nativephp\\Directions\\Events\\DistancesReceived"
            let transport = (parameters["transport"] as? String ?? "automobile").lowercased()

            guard let origin = parameters["origin"] as? [String: Any],
                  let oLat = (origin["lat"] as? NSNumber)?.doubleValue,
                  let oLng = (origin["lng"] as? NSNumber)?.doubleValue else {
                return BridgeResponse.error(code: "invalid_origin", message: "origin { lat, lng } é obrigatório")
            }

            // Destinos: [{ id, lat, lng }]. Ignora entradas malformadas.
            var dests: [(id: String, lat: Double, lng: Double)] = []
            if let arr = parameters["destinations"] as? [[String: Any]] {
                for d in arr {
                    guard let idAny = d["id"],
                          let lat = (d["lat"] as? NSNumber)?.doubleValue,
                          let lng = (d["lng"] as? NSNumber)?.doubleValue else { continue }
                    let id = (idAny as? String) ?? String(describing: idAny)
                    dests.append((id: id, lat: lat, lng: lng))
                }
            }

            let transportType: MKDirectionsTransportType = (transport == "walking") ? .walking : .automobile
            let source = MKMapItem(placemark: MKPlacemark(
                coordinate: CLLocationCoordinate2D(latitude: oLat, longitude: oLng)))

            // MKDirections é rate-limited: processa UM destino por vez (semáforo)
            // para evitar erro de throttling. O resultado volta num único evento.
            DispatchQueue.global(qos: .userInitiated).async {
                let gate = DispatchSemaphore(value: 1)
                let lock = NSLock()
                var results: [[String: Any]] = []

                func append(_ row: [String: Any]) {
                    lock.lock(); results.append(row); lock.unlock()
                }

                // Calcula uma rota; se falhar (MKDirections é rate-limited e às
                // vezes retorna "throttled"), tenta mais 1x após um respiro. Só
                // libera o slot (gate.signal) no resultado final — mantém o
                // processamento sequencial, inclusive durante o retry.
                func calculate(_ dest: (id: String, lat: Double, lng: Double), attempt: Int) {
                    let request = MKDirections.Request()
                    request.source = source
                    request.destination = MKMapItem(placemark: MKPlacemark(
                        coordinate: CLLocationCoordinate2D(latitude: dest.lat, longitude: dest.lng)))
                    request.transportType = transportType
                    request.requestsAlternateRoutes = false

                    MKDirections(request: request).calculate { response, _ in
                        if let route = response?.routes.first {
                            append([
                                "id": dest.id,
                                "meters": Int(route.distance.rounded()),
                                "seconds": Int(route.expectedTravelTime.rounded()),
                                "ok": true,
                            ])
                            gate.signal()
                        } else if attempt < 1 {
                            DispatchQueue.global(qos: .userInitiated).asyncAfter(deadline: .now() + 0.9) {
                                calculate(dest, attempt: attempt + 1)
                            }
                        } else {
                            append(["id": dest.id, "ok": false])
                            gate.signal()
                        }
                    }
                }

                for dest in dests {
                    gate.wait()
                    calculate(dest, attempt: 0)
                }

                // Aguarda a última rota terminar antes de despachar.
                gate.wait(); gate.signal()

                lock.lock(); let payload = results; lock.unlock()
                let json = (try? JSONSerialization.data(withJSONObject: payload, options: []))
                    .flatMap { String(data: $0, encoding: .utf8) } ?? "[]"

                DispatchQueue.main.async {
                    LaravelBridge.shared.send?(event, ["id": reqId, "results": json])
                }
            }

            return BridgeResponse.success(data: [:])
        }
    }
}
