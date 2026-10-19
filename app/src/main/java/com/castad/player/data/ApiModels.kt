package com.castad.player.data

import com.google.gson.annotations.SerializedName

data class DeviceConfigResponse(
    val success: Boolean = false,
    @SerializedName("device_id")
    val deviceId: String? = null,
    @SerializedName("playlist_id")
    val playlistId: Int? = null,
    @SerializedName("playlist_version")
    val playlistVersion: Int = 0,
    val orientation: String? = null,
    @SerializedName("sync_interval_seconds")
    val syncIntervalSeconds: Int = 60,
    @SerializedName("heartbeat_interval_seconds")
    val heartbeatIntervalSeconds: Int = 60,
    @SerializedName("force_resync_token")
    val forceResyncToken: String? = null,
    @SerializedName("force_resync_requested_at")
    val forceResyncRequestedAt: String? = null,
    @SerializedName("server_time")
    val serverTime: String? = null,
    val message: String? = null
)

data class HeartbeatRequest(
    @SerializedName("device_id")
    val deviceId: String,
    @SerializedName("app_version")
    val appVersion: String,
    @SerializedName("current_playlist_version")
    val currentPlaylistVersion: Int,
    @SerializedName("last_sync_time")
    val lastSyncTime: String?,
    @SerializedName("last_applied_resync_token")
    val lastAppliedResyncToken: String? = null,
    @SerializedName("current_playlist_item_id")
    val currentPlaylistItemId: Int? = null,
    @SerializedName("current_media_id")
    val currentMediaId: Int? = null,
    @SerializedName("current_media_type")
    val currentMediaType: String? = null,
    @SerializedName("last_playback_time")
    val lastPlaybackTime: String? = null,
    @SerializedName("last_download_status")
    val lastDownloadStatus: String? = null,
    @SerializedName("last_download_message")
    val lastDownloadMessage: String? = null,
    @SerializedName("last_download_time")
    val lastDownloadTime: String? = null,
    @SerializedName("storage_available_bytes")
    val storageAvailableBytes: Long? = null
)

data class ApiStatusResponse(
    val success: Boolean = false,
    @SerializedName("server_time")
    val serverTime: String? = null,
    val message: String? = null
)
