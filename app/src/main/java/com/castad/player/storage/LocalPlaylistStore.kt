package com.castad.player.storage

import android.content.Context
import android.content.res.Configuration
import com.castad.player.R
import com.castad.player.data.MediaType
import com.castad.player.data.Playlist
import com.castad.player.data.PlaylistItem
import com.google.gson.Gson
import java.io.File

class LocalPlaylistStore(private val context: Context) {
    private val gson = Gson()

    fun loadActivePlaylist(): Playlist {
        val activePlaylistFile = activePlaylistFile()
        if (activePlaylistFile.exists()) {
            return runCatching {
                gson.fromJson(activePlaylistFile.readText(), Playlist::class.java)
            }.getOrNull() ?: createMockPlaylist()
        }
        return createMockPlaylist()
    }

    fun savePendingPlaylist(playlist: Playlist) {
        val pendingPlaylistFile = pendingPlaylistFile()
        pendingPlaylistFile.parentFile?.mkdirs()
        pendingPlaylistFile.writeText(gson.toJson(playlist))
    }

    fun activatePendingPlaylist(): Playlist? {
        val pendingPlaylistFile = pendingPlaylistFile()
        if (!pendingPlaylistFile.exists()) {
            return null
        }

        val playlist = runCatching {
            gson.fromJson(pendingPlaylistFile.readText(), Playlist::class.java)
        }.getOrNull() ?: return null

        val activePlaylistFile = activePlaylistFile()
        activePlaylistFile.parentFile?.mkdirs()
        pendingPlaylistFile.copyTo(activePlaylistFile, overwrite = true)
        pendingPlaylistFile.delete()
        return playlist
    }

    fun clearActivePlaylist(playlistVersion: Int = EMPTY_PLAYLIST_VERSION): Playlist {
        val pendingPlaylistFile = pendingPlaylistFile()
        if (pendingPlaylistFile.exists()) {
            pendingPlaylistFile.delete()
        }

        val emptyPlaylist = createEmptyPlaylist(playlistVersion)
        val activePlaylistFile = activePlaylistFile()
        activePlaylistFile.parentFile?.mkdirs()
        activePlaylistFile.writeText(gson.toJson(emptyPlaylist))
        return emptyPlaylist
    }

    fun saveActivePlaylist(playlist: Playlist): Playlist {
        val activePlaylistFile = activePlaylistFile()
        activePlaylistFile.parentFile?.mkdirs()
        activePlaylistFile.writeText(gson.toJson(playlist))
        pendingPlaylistFile().delete()
        return playlist
    }

    private fun createMockPlaylist(): Playlist {
        val imageUri = "android.resource://${context.packageName}/${fallbackImageResourceId()}"

        return Playlist(
            playlistId = MOCK_PLAYLIST_ID,
            playlistVersion = MOCK_PLAYLIST_VERSION,
            items = listOf(
                PlaylistItem(
                    id = 1,
                    mediaId = 1,
                    type = MediaType.IMAGE,
                    url = imageUri,
                    durationSeconds = 8,
                    sortOrder = 1
                )
            )
        )
    }

    private fun createEmptyPlaylist(playlistVersion: Int = EMPTY_PLAYLIST_VERSION): Playlist {
        return Playlist(
            playlistId = EMPTY_PLAYLIST_ID,
            playlistVersion = playlistVersion,
            items = emptyList()
        )
    }

    private fun fallbackImageResourceId(): Int {
        return if (context.resources.configuration.orientation == Configuration.ORIENTATION_PORTRAIT) {
            R.drawable.default_no_content_portrait
        } else {
            R.drawable.default_no_content
        }
    }

    companion object {
        private const val EMPTY_PLAYLIST_ID = 0
        private const val EMPTY_PLAYLIST_VERSION = 0
        private const val MOCK_PLAYLIST_ID = 1
        private const val MOCK_PLAYLIST_VERSION = 0
        private const val PLAYLIST_DIRECTORY = "playlists"
        private const val ACTIVE_PLAYLIST_FILE = "active_playlist.json"
        private const val PENDING_PLAYLIST_FILE = "pending_playlist.json"
    }

    private fun activePlaylistFile(): File {
        return File(File(context.filesDir, PLAYLIST_DIRECTORY), ACTIVE_PLAYLIST_FILE)
    }

    private fun pendingPlaylistFile(): File {
        return File(File(context.filesDir, PLAYLIST_DIRECTORY), PENDING_PLAYLIST_FILE)
    }
}
