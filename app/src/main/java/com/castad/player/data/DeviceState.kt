package com.castad.player.data

data class DeviceState(
    val deviceId: String,
    val appVersion: String,
    val currentPlaylistVersion: Int = 0,
    val lastSyncTime: String? = null
)
