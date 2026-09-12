package com.castad.player.data

import com.google.gson.annotations.SerializedName

data class Playlist(
    @SerializedName("playlist_id")
    val playlistId: Int,
    @SerializedName("playlist_version")
    val playlistVersion: Int,
    val items: List<PlaylistItem>
)

data class PlaylistItem(
    val id: Int,
    @SerializedName("media_id")
    val mediaId: Int,
    val type: MediaType,
    val url: String,
    @SerializedName("duration_seconds")
    val durationSeconds: Int?,
    @SerializedName("sort_order")
    val sortOrder: Int,
    @SerializedName("file_size")
    val fileSize: Long? = null,
    val checksum: String? = null,
    @SerializedName("local_path")
    val localPath: String? = null
) {
    val playbackSource: String
        get() = localPath?.takeIf { it.isNotBlank() } ?: url
}

enum class MediaType {
    @SerializedName("image")
    IMAGE,

    @SerializedName("video")
    VIDEO
}
