package com.castad.player.storage

import android.content.Context
import android.database.Cursor
import android.net.Uri
import android.provider.OpenableColumns
import com.castad.player.data.MediaType
import com.castad.player.data.Playlist
import com.castad.player.data.PlaylistItem
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.util.Locale

class ManualPlaylistStore(context: Context) {
    private val appContext = context.applicationContext
    private val mediaDir = File(appContext.filesDir, MANUAL_MEDIA_DIRECTORY)

    suspend fun createPlaylistFromUris(
        uris: List<Uri>,
        imageDurationSeconds: Int
    ): Playlist = withContext(Dispatchers.IO) {
        mediaDir.deleteRecursively()
        mediaDir.mkdirs()

        val items = uris.mapIndexedNotNull { index, uri ->
            val mediaType = detectMediaType(uri) ?: return@mapIndexedNotNull null
            val extension = extensionFor(uri, mediaType)
            val destination = File(mediaDir, "manual_${index + 1}.$extension")

            appContext.contentResolver.openInputStream(uri)?.use { inputStream ->
                destination.outputStream().use { outputStream ->
                    inputStream.copyTo(outputStream)
                }
            } ?: return@mapIndexedNotNull null

            if (!destination.exists() || destination.length() <= 0L) {
                destination.delete()
                return@mapIndexedNotNull null
            }

            PlaylistItem(
                id = index + 1,
                mediaId = MANUAL_MEDIA_ID_START + index,
                type = mediaType,
                url = destination.absolutePath,
                durationSeconds = if (mediaType == MediaType.IMAGE) imageDurationSeconds else null,
                sortOrder = index + 1,
                fileSize = destination.length(),
                localPath = destination.absolutePath
            )
        }

        Playlist(
            playlistId = MANUAL_PLAYLIST_ID,
            playlistVersion = MANUAL_PLAYLIST_VERSION,
            items = items
        )
    }

    private fun detectMediaType(uri: Uri): MediaType? {
        val mimeType = appContext.contentResolver.getType(uri)?.lowercase(Locale.US)
        if (mimeType?.startsWith("image/") == true) {
            return MediaType.IMAGE
        }
        if (mimeType?.startsWith("video/") == true) {
            return MediaType.VIDEO
        }

        val name = displayName(uri).lowercase(Locale.US)
        return when {
            name.endsWith(".jpg") || name.endsWith(".jpeg") || name.endsWith(".png") -> MediaType.IMAGE
            name.endsWith(".mp4") -> MediaType.VIDEO
            else -> null
        }
    }

    private fun extensionFor(uri: Uri, mediaType: MediaType): String {
        val nameExtension = displayName(uri)
            .substringAfterLast('.', "")
            .lowercase(Locale.US)
            .takeIf { it in SUPPORTED_EXTENSIONS }
        if (nameExtension != null) {
            return nameExtension
        }

        return when (mediaType) {
            MediaType.IMAGE -> DEFAULT_IMAGE_EXTENSION
            MediaType.VIDEO -> DEFAULT_VIDEO_EXTENSION
        }
    }

    private fun displayName(uri: Uri): String {
        var cursor: Cursor? = null
        return try {
            cursor = appContext.contentResolver.query(uri, null, null, null, null)
            val nameIndex = cursor?.getColumnIndex(OpenableColumns.DISPLAY_NAME) ?: -1
            if (cursor?.moveToFirst() == true && nameIndex >= 0) {
                cursor.getString(nameIndex).orEmpty()
            } else {
                uri.lastPathSegment.orEmpty()
            }
        } finally {
            cursor?.close()
        }
    }

    private companion object {
        private const val MANUAL_MEDIA_DIRECTORY = "manual_media"
        private const val MANUAL_PLAYLIST_ID = -100
        private const val MANUAL_PLAYLIST_VERSION = 1
        private const val MANUAL_MEDIA_ID_START = 100000
        private const val DEFAULT_IMAGE_EXTENSION = "jpg"
        private const val DEFAULT_VIDEO_EXTENSION = "mp4"
        private val SUPPORTED_EXTENSIONS = setOf("jpg", "jpeg", "png", "mp4")
    }
}
