package com.castad.player.sync

import android.content.Context
import com.castad.player.data.Playlist
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File
import java.io.IOException
import java.security.MessageDigest
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
            if (!mediaFile.isUsableFor(item.fileSize, item.checksum)) {
                downloadMedia(item.url, mediaFile, item.fileSize, item.checksum)
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

    private fun downloadMedia(
        url: String,
        destination: File,
        expectedFileSize: Long?,
        expectedChecksum: String?
    ) {
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

        if (!temporaryFile.isUsableFor(expectedFileSize, expectedChecksum)) {
            temporaryFile.delete()
            throw IOException("Media download failed: checksum or file size mismatch")
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

    private fun File.isUsableFor(expectedFileSize: Long?, expectedChecksum: String?): Boolean {
        if (!exists() || length() <= 0L) {
            return false
        }
        if (expectedFileSize != null && expectedFileSize > 0L && length() != expectedFileSize) {
            return false
        }

        val normalizedChecksum = expectedChecksum
            ?.trim()
            ?.lowercase(Locale.US)
            ?.takeIf { it.isNotEmpty() }
            ?: return true

        if (!SHA_256_PATTERN.matches(normalizedChecksum)) {
            return false
        }

        return sha256() == normalizedChecksum
    }

    private fun File.sha256(): String {
        val digest = MessageDigest.getInstance("SHA-256")
        inputStream().use { inputStream ->
            val buffer = ByteArray(DEFAULT_BUFFER_SIZE)
            while (true) {
                val bytesRead = inputStream.read(buffer)
                if (bytesRead == -1) {
                    break
                }
                digest.update(buffer, 0, bytesRead)
            }
        }
        return digest.digest().joinToString(separator = "") { byte ->
            "%02x".format(byte.toInt() and 0xff)
        }
    }

    private companion object {
        private const val MEDIA_DIRECTORY = "media"
        private const val ACTIVE_DIRECTORY = "active"
        private const val TEMPORARY_DIRECTORY = "tmp"
        private const val DEFAULT_TIMEOUT_SECONDS = 30L
        private const val DEFAULT_IMAGE_EXTENSION = "jpg"
        private const val DEFAULT_VIDEO_EXTENSION = "mp4"
        private val SUPPORTED_EXTENSIONS = setOf("jpg", "jpeg", "png", "mp4")
        private val SHA_256_PATTERN = Regex("^[a-f0-9]{64}$")
    }
}
