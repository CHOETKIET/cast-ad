package com.castad.player.sync

import android.content.Context
import com.castad.player.data.Playlist
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File
import java.io.IOException
import java.util.Locale
import java.util.concurrent.TimeUnit

class MediaCacheManager(context: Context) {
    private val appContext = context.applicationContext
    private val activeMediaDir = File(File(appContext.filesDir, MEDIA_DIRECTORY), ACTIVE_DIRECTORY)
    private val temporaryMediaDir = File(File(appContext.filesDir, MEDIA_DIRECTORY), TEMPORARY_DIRECTORY)
    private val httpClient = OkHttpClient.Builder()
        .connectTimeout(DEFAULT_TIMEOUT_SECONDS, TimeUnit.SECONDS)
        .readTimeout(DEFAULT_TIMEOUT_SECONDS, TimeUnit.SECONDS)
        .writeTimeout(DEFAULT_TIMEOUT_SECONDS, TimeUnit.SECONDS)
        .build()

    suspend fun cachePlaylistMedia(playlist: Playlist): Playlist = withContext(Dispatchers.IO) {
        activeMediaDir.mkdirs()
        temporaryMediaDir.mkdirs()

        val cachedItems = playlist.items.map { item ->
            val mediaFile = File(activeMediaDir, item.localFileName())
            if (!mediaFile.isUsableFor(item.fileSize)) {
                downloadMedia(item.url, mediaFile)
            }
            item.copy(localPath = mediaFile.absolutePath)
        }

        playlist.copy(items = cachedItems)
    }

    suspend fun clearCachedMedia() = withContext(Dispatchers.IO) {
        if (activeMediaDir.exists()) {
            activeMediaDir.deleteRecursively()
        }
        if (temporaryMediaDir.exists()) {
            temporaryMediaDir.deleteRecursively()
        }
    }

    private fun downloadMedia(url: String, destination: File) {
        if (!url.startsWith("https://")) {
            throw IOException("Media URL must start with https://")
        }

        val temporaryFile = File(temporaryMediaDir, "${destination.name}.download")
        if (temporaryFile.exists()) {
            temporaryFile.delete()
        }

        val request = Request.Builder()
            .url(url)
            .get()
            .build()

        httpClient.newCall(request).execute().use { response ->
            if (!response.isSuccessful) {
                throw IOException("Media download failed: HTTP ${response.code}")
            }

            val body = response.body ?: throw IOException("Media download failed: empty body")
            temporaryFile.outputStream().use { outputStream ->
                body.byteStream().use { inputStream ->
                    inputStream.copyTo(outputStream)
                }
            }
        }

        if (!temporaryFile.exists() || temporaryFile.length() <= 0L) {
            temporaryFile.delete()
            throw IOException("Media download failed: empty file")
        }

        destination.parentFile?.mkdirs()
        temporaryFile.copyTo(destination, overwrite = true)
        temporaryFile.delete()
    }

    private fun com.castad.player.data.PlaylistItem.localFileName(): String {
        val extension = url.substringBefore('?')
            .substringAfterLast('.', "")
            .lowercase(Locale.US)
            .takeIf { it in SUPPORTED_EXTENSIONS }
            ?: when (type) {
                com.castad.player.data.MediaType.IMAGE -> DEFAULT_IMAGE_EXTENSION
                com.castad.player.data.MediaType.VIDEO -> DEFAULT_VIDEO_EXTENSION
            }
        return "media_${mediaId}_${id}.$extension"
    }

    private fun File.isUsableFor(expectedFileSize: Long?): Boolean {
        if (!exists() || length() <= 0L) {
            return false
        }
        return expectedFileSize == null || expectedFileSize <= 0L || length() == expectedFileSize
    }

    private companion object {
        private const val MEDIA_DIRECTORY = "media"
        private const val ACTIVE_DIRECTORY = "active"
        private const val TEMPORARY_DIRECTORY = "tmp"
        private const val DEFAULT_TIMEOUT_SECONDS = 30L
        private const val DEFAULT_IMAGE_EXTENSION = "jpg"
        private const val DEFAULT_VIDEO_EXTENSION = "mp4"
        private val SUPPORTED_EXTENSIONS = setOf("jpg", "jpeg", "png", "mp4")
    }
}
