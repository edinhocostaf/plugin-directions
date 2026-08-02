package com.keepcloud.plugins.directions

import android.content.Context
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

object DirectionsFunctions {

    /**
     * Android não tem um motor de rotas on-device gratuito equivalente ao
     * MKDirections do iOS. Por decisão de produto, o Android NÃO despacha o
     * evento DistancesReceived — o app mantém a ordenação por distância em
     * linha reta (haversine) como fallback. Trocar por Google Directions API
     * ou OSRM quando houver build Android de produção.
     */
    class Distances(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            // No-op proposital: sem evento de volta, o app usa o fallback reto.
            return BridgeResponse.success(mapOf("supported" to false))
        }
    }
}
