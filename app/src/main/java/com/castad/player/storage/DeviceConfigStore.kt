package com.castad.player.storage

import android.content.Context
import android.provider.Settings
import java.util.Locale

class DeviceConfigStore(context: Context) {
    private val appContext = context.applicationContext
    private val preferences = appContext.getSharedPreferences(PREFERENCES_NAME, Context.MODE_PRIVATE)

    fun getDeviceId(): String {
        val savedDeviceId = preferences.getString(KEY_DEVICE_ID, null)
        if (!savedDeviceId.isNullOrBlank()) {
            return savedDeviceId
        }

        val generatedDeviceId = generateDefaultDeviceId()
        saveDeviceId(generatedDeviceId)
        return generatedDeviceId
    }

    fun saveDeviceId(deviceId: String) {
        preferences.edit()
            .putString(KEY_DEVICE_ID, deviceId.trim())
            .apply()
    }

    fun getApiBaseUrl(): String {
        return preferences.getString(KEY_API_BASE_URL, DEFAULT_API_BASE_URL).orEmpty()
    }

    fun saveApiBaseUrl(apiBaseUrl: String) {
        preferences.edit()
            .putString(KEY_API_BASE_URL, apiBaseUrl.trim().trimEnd('/'))
            .apply()
    }

    fun getCurrentPlaylistVersion(): Int {
        return preferences.getInt(KEY_CURRENT_PLAYLIST_VERSION, 0)
    }

    fun saveCurrentPlaylistVersion(version: Int) {
        preferences.edit()
            .putInt(KEY_CURRENT_PLAYLIST_VERSION, version)
            .apply()
    }

    fun getLastSyncTime(): String? {
        return preferences.getString(KEY_LAST_SYNC_TIME, null)
    }

    fun saveLastSyncTime(lastSyncTime: String?) {
        preferences.edit()
            .putString(KEY_LAST_SYNC_TIME, lastSyncTime)
            .apply()
    }

    fun getLastSyncStatus(): String? {
        return preferences.getString(KEY_LAST_SYNC_STATUS, null)
    }

    fun saveLastSyncStatus(status: String) {
        preferences.edit()
            .putString(KEY_LAST_SYNC_STATUS, status)
            .apply()
    }

    fun getLastAppliedResyncToken(): String? {
        return preferences.getString(KEY_LAST_APPLIED_RESYNC_TOKEN, null)
    }

    fun saveLastAppliedResyncToken(token: String?) {
        preferences.edit()
            .putString(KEY_LAST_APPLIED_RESYNC_TOKEN, token)
            .apply()
    }

    fun getHeartbeatIntervalSeconds(): Int {
        return preferences.getInt(KEY_HEARTBEAT_INTERVAL_SECONDS, DEFAULT_HEARTBEAT_INTERVAL_SECONDS)
    }

    fun saveHeartbeatIntervalSeconds(seconds: Int) {
        preferences.edit()
            .putInt(KEY_HEARTBEAT_INTERVAL_SECONDS, seconds.coerceAtLeast(MINIMUM_INTERVAL_SECONDS))
            .apply()
    }

    fun getSyncIntervalSeconds(): Int {
        return preferences.getInt(KEY_SYNC_INTERVAL_SECONDS, DEFAULT_SYNC_INTERVAL_SECONDS)
    }

    fun saveSyncIntervalSeconds(seconds: Int) {
        preferences.edit()
            .putInt(KEY_SYNC_INTERVAL_SECONDS, seconds.coerceAtLeast(MINIMUM_INTERVAL_SECONDS))
            .apply()
    }

    fun getLastHeartbeatTime(): String? {
        return preferences.getString(KEY_LAST_HEARTBEAT_TIME, null)
    }

    fun saveLastHeartbeatTime(lastHeartbeatTime: String?) {
        preferences.edit()
            .putString(KEY_LAST_HEARTBEAT_TIME, lastHeartbeatTime)
            .apply()
    }

    fun getLastHeartbeatStatus(): String? {
        return preferences.getString(KEY_LAST_HEARTBEAT_STATUS, null)
    }

    fun saveLastHeartbeatStatus(status: String) {
        preferences.edit()
            .putString(KEY_LAST_HEARTBEAT_STATUS, status)
            .apply()
    }

    fun getLastBootLaunchTime(): String? {
        return preferences.getString(KEY_LAST_BOOT_LAUNCH_TIME, null)
    }

    fun saveLastBootLaunchTime(lastBootLaunchTime: String) {
        preferences.edit()
            .putString(KEY_LAST_BOOT_LAUNCH_TIME, lastBootLaunchTime)
            .apply()
    }

    fun getLastConnectionTestStatus(): String? {
        return preferences.getString(KEY_LAST_CONNECTION_TEST_STATUS, null)
    }

    fun saveLastConnectionTestStatus(status: String) {
        preferences.edit()
            .putString(KEY_LAST_CONNECTION_TEST_STATUS, status)
            .apply()
    }

    fun getPlaybackMode(): PlaybackMode {
        val savedMode = preferences.getString(KEY_PLAYBACK_MODE, PlaybackMode.WEB_SYNC.value)
        return PlaybackMode.entries.firstOrNull { it.value == savedMode } ?: PlaybackMode.WEB_SYNC
    }

    fun savePlaybackMode(mode: PlaybackMode) {
        preferences.edit()
            .putString(KEY_PLAYBACK_MODE, mode.value)
            .apply()
    }

    private fun generateDefaultDeviceId(): String {
        val androidId = Settings.Secure.getString(
            appContext.contentResolver,
            Settings.Secure.ANDROID_ID
        ).orEmpty()
        val suffix = androidId
            .takeLast(DEFAULT_DEVICE_ID_SUFFIX_LENGTH)
            .uppercase(Locale.US)
            .ifBlank { "LOCAL001" }
        return "SCREEN-$suffix"
    }

    private companion object {
        private const val PREFERENCES_NAME = "cast_ad_device_config"
        private const val KEY_DEVICE_ID = "device_id"
        private const val KEY_API_BASE_URL = "api_base_url"
        private const val KEY_CURRENT_PLAYLIST_VERSION = "current_playlist_version"
        private const val KEY_LAST_SYNC_TIME = "last_sync_time"
        private const val KEY_LAST_SYNC_STATUS = "last_sync_status"
        private const val KEY_LAST_APPLIED_RESYNC_TOKEN = "last_applied_resync_token"
        private const val KEY_SYNC_INTERVAL_SECONDS = "sync_interval_seconds"
        private const val KEY_HEARTBEAT_INTERVAL_SECONDS = "heartbeat_interval_seconds"
        private const val KEY_LAST_HEARTBEAT_TIME = "last_heartbeat_time"
        private const val KEY_LAST_HEARTBEAT_STATUS = "last_heartbeat_status"
        private const val KEY_LAST_BOOT_LAUNCH_TIME = "last_boot_launch_time"
        private const val KEY_LAST_CONNECTION_TEST_STATUS = "last_connection_test_status"
        private const val KEY_PLAYBACK_MODE = "playback_mode"
        private const val DEFAULT_API_BASE_URL = ""
        private const val DEFAULT_SYNC_INTERVAL_SECONDS = 60
        private const val DEFAULT_HEARTBEAT_INTERVAL_SECONDS = 60
        private const val MINIMUM_INTERVAL_SECONDS = 15
        private const val DEFAULT_DEVICE_ID_SUFFIX_LENGTH = 8
    }

    enum class PlaybackMode(val value: String, val label: String) {
        WEB_SYNC("web_sync", "Web Sync"),
        MANUAL_PLAYLIST("manual_playlist", "Manual Playlist")
    }
}
