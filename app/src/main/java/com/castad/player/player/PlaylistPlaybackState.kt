package com.castad.player.player

data class PlaylistPlaybackState(
    val currentIndex: Int = 0,
    val isPlaying: Boolean = false,
    val statusMessage: String? = null
)
