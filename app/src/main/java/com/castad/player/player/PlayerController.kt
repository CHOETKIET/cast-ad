package com.castad.player.player

import android.net.Uri
import android.util.Log
import android.view.View
import android.widget.ImageView
import android.widget.TextView
import androidx.media3.common.MediaItem
import androidx.media3.common.Player
import androidx.media3.exoplayer.ExoPlayer
import androidx.media3.ui.PlayerView
import com.castad.player.R
import com.castad.player.data.MediaType
import com.castad.player.data.Playlist
import com.castad.player.data.PlaylistItem
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import java.io.File

class PlayerController(
    private val imageView: ImageView,
    private val playerView: PlayerView,
    private val statusText: TextView,
    private val scope: CoroutineScope
) {
    private val exoPlayer = ExoPlayer.Builder(playerView.context).build()
    private var playbackJob: Job? = null
    private var playlist: Playlist? = null
    private var currentIndex = 0
    private var consecutiveFailures = 0

    init {
        playerView.player = exoPlayer
        exoPlayer.addListener(
            object : Player.Listener {
                override fun onPlaybackStateChanged(playbackState: Int) {
                    if (playbackState == Player.STATE_ENDED) {
                        playNext()
                    }
                }

                override fun onPlayerError(error: androidx.media3.common.PlaybackException) {
                    handlePlaybackFailure()
                }
            }
        )
    }

    fun start(playlist: Playlist) {
        Log.i(TAG, "Starting playlist id=${playlist.playlistId} version=${playlist.playlistVersion} items=${playlist.items.size}")
        this.playlist = playlist.copy(items = playlist.items.sortedBy { it.sortOrder })
        currentIndex = 0
        consecutiveFailures = 0
        if (this.playlist?.items.isNullOrEmpty()) {
            showStatus("No playable content")
            return
        }
        playCurrent()
    }

    fun stop() {
        playbackJob?.cancel()
        exoPlayer.stop()
    }

    fun release() {
        stop()
        exoPlayer.release()
    }

    private fun playCurrent() {
        val activePlaylist = playlist
        if (activePlaylist == null || activePlaylist.items.isEmpty()) {
            showStatus("No playable content")
            return
        }

        val item = activePlaylist.items[currentIndex % activePlaylist.items.size]
        when (item.type) {
            MediaType.IMAGE -> playImage(item)
            MediaType.VIDEO -> playVideo(item)
        }
    }

    private fun playImage(item: PlaylistItem) {
        Log.i(TAG, "Playing image item=${item.id} source=${item.playbackSource}")
        playbackJob?.cancel()
        exoPlayer.stop()
        playerView.visibility = View.GONE
        imageView.visibility = View.VISIBLE
        statusText.visibility = View.GONE

        val uri = item.playbackUriOrNull()
        if (uri == null) {
            handlePlaybackFailure()
            return
        }

        consecutiveFailures = 0
        if (!imageView.displayImage(item.playbackSource, uri)) {
            Log.w(TAG, "Image display failed for item=${item.id}")
            handlePlaybackFailure()
            return
        }
        playbackJob = scope.launch {
            delay((item.durationSeconds ?: DEFAULT_IMAGE_DURATION_SECONDS) * 1000L)
            playNext()
        }
    }

    private fun playVideo(item: PlaylistItem) {
        Log.i(TAG, "Playing video item=${item.id} source=${item.playbackSource}")
        playbackJob?.cancel()
        imageView.visibility = View.GONE
        playerView.visibility = View.VISIBLE
        statusText.visibility = View.GONE

        val uri = item.playbackUriOrNull()
        if (uri == null) {
            handlePlaybackFailure()
            return
        }

        consecutiveFailures = 0
        exoPlayer.setMediaItem(MediaItem.fromUri(uri))
        exoPlayer.prepare()
        exoPlayer.playWhenReady = true
    }

    private fun playNext() {
        val itemCount = playlist?.items?.size ?: 0
        if (itemCount == 0) {
            showStatus("No playable content")
            return
        }
        currentIndex = (currentIndex + 1) % itemCount
        playCurrent()
    }

    private fun handlePlaybackFailure() {
        val itemCount = playlist?.items?.size ?: 0
        consecutiveFailures += 1
        if (itemCount == 0 || consecutiveFailures >= itemCount) {
            showStatus("No playable content")
            return
        }
        currentIndex = (currentIndex + 1) % itemCount
        playCurrent()
    }

    private fun showStatus(message: String) {
        Log.w(TAG, "Showing player status: $message")
        playbackJob?.cancel()
        exoPlayer.stop()
        consecutiveFailures = 0
        imageView.setImageResource(R.drawable.default_no_content)
        imageView.visibility = View.VISIBLE
        playerView.visibility = View.GONE
        statusText.text = message
        statusText.visibility = View.GONE
    }

    private fun ImageView.displayImage(source: String, uri: Uri): Boolean {
        return runCatching {
            if (source.startsWith("android.resource://")) {
                val resourceId = uri.lastPathSegment?.toIntOrNull()
                if (resourceId == null) {
                    setImageURI(uri)
                } else {
                    setImageResource(resourceId)
                }
            } else {
                setImageURI(uri)
            }
        }.isSuccess
    }

    private fun PlaylistItem.playbackUriOrNull(): Uri? {
        val source = playbackSource.takeIf { it.isNotBlank() } ?: return null
        return if (source.startsWith("android.resource://")) {
            Uri.parse(source)
        } else {
            val file = File(source)
            if (!file.exists() || file.length() <= 0L) {
                null
            } else {
                Uri.fromFile(file)
            }
        }
    }

    private companion object {
        private const val TAG = "PlayerController"
        private const val DEFAULT_IMAGE_DURATION_SECONDS = 10
    }
}
