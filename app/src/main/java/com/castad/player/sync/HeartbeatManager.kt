package com.castad.player.sync

import com.castad.player.data.HeartbeatRequest
import com.castad.player.network.ApiClient
import com.castad.player.storage.DeviceConfigStore
import com.castad.player.util.TimeFormats
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

class HeartbeatManager(
    private val configStore: DeviceConfigStore,
    private val appVersion: String,
    private val scope: CoroutineScope,
    private val listener: Listener? = null
) {
    private var heartbeatJob: Job? = null

    fun start() {
        if (heartbeatJob?.isActive == true) {
            return
        }

        heartbeatJob = scope.launch {
            while (isActive) {
                sendHeartbeatOnce()
                delay(configStore.getHeartbeatIntervalSeconds() * 1000L)
            }
        }
    }

    fun stop() {
        heartbeatJob?.cancel()
    }

    fun sendNow() {
        scope.launch {
            sendHeartbeatOnce()
        }
    }

    private suspend fun sendHeartbeatOnce() {
        val apiBaseUrl = configStore.getApiBaseUrl()
        if (apiBaseUrl.isBlank()) {
            listener?.onHeartbeatStatus("API Base URL is not configured")
            return
        }

        val api = try {
            ApiClient.create(apiBaseUrl)
        } catch (error: IllegalArgumentException) {
            listener?.onHeartbeatStatus(error.message ?: "Invalid API Base URL")
            return
        }

        val request = HeartbeatRequest(
            deviceId = configStore.getDeviceId(),
            appVersion = appVersion,
            currentPlaylistVersion = configStore.getCurrentPlaylistVersion(),
            lastSyncTime = configStore.getLastSyncTime(),
            lastAppliedResyncToken = configStore.getLastAppliedResyncToken(),
            currentPlaylistItemId = configStore.getCurrentPlaylistItemId() ?: 0,
            currentMediaId = configStore.getCurrentMediaId() ?: 0,
            currentMediaType = configStore.getCurrentMediaType().orEmpty(),
            lastPlaybackTime = configStore.getLastPlaybackTime().orEmpty(),
            lastDownloadStatus = configStore.getLastDownloadStatus().orEmpty(),
            lastDownloadMessage = configStore.getLastDownloadMessage().orEmpty(),
            lastDownloadTime = configStore.getLastDownloadTime().orEmpty(),
            storageAvailableBytes = configStore.getStorageAvailableBytes()
        )

        try {
            val response = api.sendHeartbeat(request)
            val body = response.body()
            if (!response.isSuccessful || body == null || !body.success) {
                listener?.onHeartbeatStatus(body?.message ?: "Heartbeat failed")
                return
            }

            val heartbeatTime = body.serverTime ?: TimeFormats.nowIso8601()
            configStore.saveLastHeartbeatTime(heartbeatTime)
            listener?.onHeartbeatStatus("Heartbeat sent")
        } catch (error: CancellationException) {
            throw error
        } catch (error: Exception) {
            listener?.onHeartbeatStatus("Cannot send heartbeat")
        }
    }

    interface Listener {
        fun onHeartbeatStatus(message: String)
    }
}
