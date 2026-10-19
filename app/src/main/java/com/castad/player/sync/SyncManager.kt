package com.castad.player.sync

import com.castad.player.data.DeviceConfigResponse
import com.castad.player.data.Playlist
import com.castad.player.network.ApiClient
import com.castad.player.storage.DeviceConfigStore
import com.castad.player.storage.DeviceConfigStore.PlaybackMode
import com.castad.player.storage.LocalPlaylistStore
import com.castad.player.util.TimeFormats
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Job
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

class SyncManager(
    private val configStore: DeviceConfigStore,
    private val localPlaylistStore: LocalPlaylistStore,
    private val mediaCacheManager: MediaCacheManager,
    private val scope: CoroutineScope,
    private val orientationProvider: () -> String = { ORIENTATION_LANDSCAPE },
    private val listener: Listener? = null
) {
    private var syncJob: Job? = null
    private var manualSyncJob: Job? = null

    fun start() {
        if (syncJob?.isActive == true) {
            return
        }

        syncJob = scope.launch {
            while (isActive) {
                syncOnce()
                delay(configStore.getSyncIntervalSeconds() * 1000L)
            }
        }
    }

    fun stop() {
        syncJob?.cancel()
        manualSyncJob?.cancel()
    }

    fun syncNow() {
        manualSyncJob?.cancel()
        manualSyncJob = scope.launch {
            syncOnce()
        }
    }

    private suspend fun syncOnce() {
        if (configStore.getPlaybackMode() == PlaybackMode.MANUAL_PLAYLIST) {
            listener?.onSyncStatus("Manual Playlist mode is active")
            return
        }

        val deviceId = configStore.getDeviceId()
        val apiBaseUrl = configStore.getApiBaseUrl()
        if (apiBaseUrl.isBlank()) {
            listener?.onSyncStatus("API Base URL is not configured")
            return
        }

        val api = try {
            ApiClient.create(apiBaseUrl)
        } catch (error: IllegalArgumentException) {
            listener?.onSyncStatus(error.message ?: "Invalid API Base URL")
            return
        }

        val orientation = orientationProvider().takeIf { it == ORIENTATION_PORTRAIT } ?: ORIENTATION_LANDSCAPE
        val config = fetchDeviceConfig(api, deviceId, orientation) ?: return
        configStore.saveLastSyncTime(TimeFormats.nowIso8601())
        configStore.saveSyncIntervalSeconds(config.syncIntervalSeconds)
        configStore.saveHeartbeatIntervalSeconds(config.heartbeatIntervalSeconds)
        val forceResyncToken = config.forceResyncToken?.trim().orEmpty()
        val shouldForceResync = forceResyncToken.isNotBlank()
            && forceResyncToken != configStore.getLastAppliedResyncToken()

        val localVersion = configStore.getCurrentPlaylistVersion()
        val localPlaylistId = configStore.getCurrentPlaylistId()
        if (config.playlistId == null || config.playlistVersion <= 0) {
            clearLocalContent(
                message = if (shouldForceResync) "Force re-sync cleared content" else "No active playlist assigned",
                playlistVersion = config.playlistVersion,
                appliedResyncToken = forceResyncToken.takeIf { shouldForceResync }
            )
            return
        }

        if (shouldForceResync) {
            mediaCacheManager.clearCachedMedia()
            localPlaylistStore.clearActivePlaylist(config.playlistVersion)
            listener?.onSyncStatus("Force re-sync requested")
        }

        val isSamePlaylist = config.playlistId == localPlaylistId
        if (!shouldForceResync && isSamePlaylist && config.playlistVersion <= localVersion) {
            listener?.onSyncStatus("Playlist is up to date")
            return
        }

        val playlist = fetchPlaylist(api, deviceId, orientation) ?: return
        listener?.onNewerPlaylistDetected(config, playlist)
        if (playlist.items.isEmpty()) {
            clearLocalContent(
                message = if (shouldForceResync) "Force re-sync completed with empty playlist" else "Playlist is empty",
                playlistVersion = playlist.playlistVersion,
                appliedResyncToken = forceResyncToken.takeIf { shouldForceResync }
            )
            return
        }

        listener?.onSyncStatus(
            if (shouldForceResync) {
                "Force downloading playlist version ${config.playlistVersion}"
            } else {
                "Downloading playlist version ${config.playlistVersion}"
            }
        )

        val cachedPlaylist = cachePlaylist(playlist) ?: return
        configStore.saveLastDownloadStatus(
            status = DOWNLOAD_STATUS_SUCCESS,
            message = "Playlist version ${cachedPlaylist.playlistVersion} downloaded",
            downloadTime = TimeFormats.nowIso8601()
        )
        localPlaylistStore.savePendingPlaylist(cachedPlaylist)
        val activatedPlaylist = localPlaylistStore.activatePendingPlaylist()
        if (activatedPlaylist == null) {
            listener?.onSyncStatus("Cannot activate downloaded playlist")
            return
        }

        configStore.saveCurrentPlaylistVersion(activatedPlaylist.playlistVersion)
        configStore.saveCurrentPlaylistId(activatedPlaylist.playlistId)
        configStore.saveLastSyncTime(TimeFormats.nowIso8601())
        if (shouldForceResync) {
            configStore.saveLastAppliedResyncToken(forceResyncToken)
        }
        listener?.onPlaylistActivated(activatedPlaylist)
        listener?.onSyncStatus(
            if (shouldForceResync) {
                "Force re-sync applied version ${activatedPlaylist.playlistVersion}"
            } else {
                "Playlist version ${activatedPlaylist.playlistVersion} activated"
            }
        )
    }

    private suspend fun fetchDeviceConfig(
        api: com.castad.player.network.DigitalSignageApi,
        deviceId: String,
        orientation: String
    ): DeviceConfigResponse? {
        return try {
            val response = api.getDeviceConfig(deviceId, orientation)
            val body = response.body()
            if (!response.isSuccessful || body == null || !body.success) {
                listener?.onSyncStatus(body?.message ?: "Device config request failed")
                null
            } else {
                body
            }
        } catch (error: CancellationException) {
            throw error
        } catch (error: Exception) {
            listener?.onSyncStatus("Cannot contact server")
            null
        }
    }

    private suspend fun fetchPlaylist(
        api: com.castad.player.network.DigitalSignageApi,
        deviceId: String,
        orientation: String
    ): Playlist? {
        return try {
            val response = api.getPlaylist(deviceId, orientation)
            val body = response.body()
            if (!response.isSuccessful || body == null) {
                listener?.onSyncStatus("Playlist request failed")
                null
            } else {
                body
            }
        } catch (error: CancellationException) {
            throw error
        } catch (error: Exception) {
            listener?.onSyncStatus("Cannot download playlist")
            null
        }
    }

    private suspend fun cachePlaylist(playlist: Playlist): Playlist? {
        return try {
            mediaCacheManager.cachePlaylistMedia(playlist)
        } catch (error: CancellationException) {
            throw error
        } catch (error: Exception) {
            configStore.saveLastDownloadStatus(
                status = DOWNLOAD_STATUS_FAILED,
                message = error.message ?: "Media download failed",
                downloadTime = TimeFormats.nowIso8601()
            )
            listener?.onSyncStatus("Media download failed")
            null
        }
    }

    private suspend fun clearLocalContent(
        message: String,
        playlistVersion: Int = 0,
        appliedResyncToken: String? = null
    ) {
        mediaCacheManager.clearCachedMedia()
        val emptyPlaylist = localPlaylistStore.clearActivePlaylist(playlistVersion)
        configStore.saveCurrentPlaylistId(emptyPlaylist.playlistId)
        configStore.saveCurrentPlaylistVersion(playlistVersion)
        configStore.saveLastSyncTime(TimeFormats.nowIso8601())
        if (!appliedResyncToken.isNullOrBlank()) {
            configStore.saveLastAppliedResyncToken(appliedResyncToken)
        }
        listener?.onPlaylistActivated(emptyPlaylist)
        listener?.onSyncStatus(message)
    }

    interface Listener {
        fun onSyncStatus(message: String)

        fun onNewerPlaylistDetected(config: DeviceConfigResponse, playlist: Playlist)

        fun onPlaylistActivated(playlist: Playlist)
    }

    private companion object {
        private const val ORIENTATION_LANDSCAPE = "landscape"
        private const val ORIENTATION_PORTRAIT = "portrait"
        private const val DOWNLOAD_STATUS_SUCCESS = "success"
        private const val DOWNLOAD_STATUS_FAILED = "failed"
    }
}
