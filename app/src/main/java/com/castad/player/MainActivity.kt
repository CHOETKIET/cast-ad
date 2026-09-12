package com.castad.player

import android.os.Bundle
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.view.View
import android.view.WindowManager
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat
import androidx.lifecycle.lifecycleScope
import androidx.media3.ui.PlayerView
import com.castad.player.data.DeviceConfigResponse
import com.castad.player.data.Playlist
import com.castad.player.player.PlayerController
import com.castad.player.storage.DeviceConfigStore
import com.castad.player.storage.DeviceConfigStore.PlaybackMode
import com.castad.player.storage.LocalPlaylistStore
import com.castad.player.storage.ManualPlaylistStore
import com.castad.player.sync.HeartbeatManager
import com.castad.player.sync.MediaCacheManager
import com.castad.player.sync.SyncManager
import com.castad.player.ui.HiddenSettingsDialog
import com.castad.player.util.TimeFormats
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity(), SyncManager.Listener, HeartbeatManager.Listener {
    private lateinit var playerController: PlayerController
    private lateinit var deviceConfigStore: DeviceConfigStore
    private lateinit var localPlaylistStore: LocalPlaylistStore
    private lateinit var manualPlaylistStore: ManualPlaylistStore
    private lateinit var mediaCacheManager: MediaCacheManager
    private lateinit var syncManager: SyncManager
    private lateinit var heartbeatManager: HeartbeatManager
    private var settingsTapCount = 0
    private var lastSettingsTapTime = 0L
    private var pendingManualImageDurationSeconds = DEFAULT_MANUAL_IMAGE_DURATION_SECONDS
    private val manualPlaylistDraftUris = mutableListOf<Uri>()
    private val manualPlaylistPicker = registerForActivityResult(
        ActivityResultContracts.OpenDocument()
    ) { uri ->
        handleManualPlaylistFileSelection(uri)
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        WindowCompat.setDecorFitsSystemWindows(window, false)
        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        setContentView(R.layout.activity_main)
        hideSystemBars()

        deviceConfigStore = DeviceConfigStore(this)
        deviceConfigStore.getDeviceId()

        playerController = PlayerController(
            imageView = findViewById(R.id.imagePlayer),
            playerView = findViewById(R.id.videoPlayer),
            statusText = findViewById(R.id.statusText),
            scope = lifecycleScope
        )
        localPlaylistStore = LocalPlaylistStore(this)
        manualPlaylistStore = ManualPlaylistStore(this)
        mediaCacheManager = MediaCacheManager(this)
        val activePlaylist = localPlaylistStore.loadActivePlaylist()
        deviceConfigStore.saveCurrentPlaylistVersion(activePlaylist.playlistVersion)
        playerController.start(activePlaylist)
        syncManager = SyncManager(
            configStore = deviceConfigStore,
            localPlaylistStore = localPlaylistStore,
            mediaCacheManager = mediaCacheManager,
            scope = lifecycleScope,
            listener = this
        )
        syncManager.start()
        heartbeatManager = HeartbeatManager(
            configStore = deviceConfigStore,
            appVersion = getAppVersion(),
            scope = lifecycleScope,
            listener = this
        )
        heartbeatManager.start()
        setupHiddenSettingsHotspot()
    }

    override fun onResume() {
        super.onResume()
        hideSystemBars()
    }

    override fun onWindowFocusChanged(hasFocus: Boolean) {
        super.onWindowFocusChanged(hasFocus)
        if (hasFocus) {
            hideSystemBars()
        }
    }

    override fun onDestroy() {
        syncManager.stop()
        heartbeatManager.stop()
        playerController.release()
        super.onDestroy()
    }

    @Suppress("DEPRECATION")
    private fun hideSystemBars() {
        window.decorView.systemUiVisibility = (
            View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                or View.SYSTEM_UI_FLAG_FULLSCREEN
                or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                or View.SYSTEM_UI_FLAG_LAYOUT_STABLE
            )

        WindowInsetsControllerCompat(window, window.decorView).apply {
            systemBarsBehavior =
                WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
            hide(WindowInsetsCompat.Type.systemBars())
        }
    }

    private fun setupHiddenSettingsHotspot() {
        findViewById<View>(R.id.settingsHotspot).setOnClickListener {
            val now = System.currentTimeMillis()
            settingsTapCount = if (now - lastSettingsTapTime <= SETTINGS_TAP_WINDOW_MS) {
                settingsTapCount + 1
            } else {
                1
            }
            lastSettingsTapTime = now

            if (settingsTapCount >= REQUIRED_SETTINGS_TAPS) {
                settingsTapCount = 0
                HiddenSettingsDialog(
                    context = this,
                    configStore = deviceConfigStore,
                    onSyncNow = { syncManager.syncNow() },
                    onClearContent = { clearContent() },
                    manualPlaylistDraftCount = manualPlaylistDraftUris.size,
                    onAddManualPlaylistFile = { durationSeconds ->
                        openManualPlaylistPicker(durationSeconds)
                    },
                    onSaveManualPlaylist = { durationSeconds ->
                        saveManualPlaylistDraft(durationSeconds)
                    },
                    onClearManualPlaylistDraft = {
                        clearManualPlaylistDraft()
                    },
                    onSwitchToWebSync = { switchToWebSync() },
                    onExitApp = { closeApp() },
                    onDismiss = { hideSystemBars() }
                ).show()
                hideSystemBars()
            }
        }
    }

    override fun onSyncStatus(message: String) {
        deviceConfigStore.saveLastSyncStatus(message)
    }

    override fun onNewerPlaylistDetected(config: DeviceConfigResponse, playlist: Playlist) = Unit

    override fun onPlaylistActivated(playlist: Playlist) {
        playerController.start(playlist)
    }

    override fun onHeartbeatStatus(message: String) {
        deviceConfigStore.saveLastHeartbeatStatus(message)
    }

    private fun clearContent() {
        lifecycleScope.launch {
            manualPlaylistDraftUris.clear()
            mediaCacheManager.clearCachedMedia()
            val emptyPlaylist = localPlaylistStore.clearActivePlaylist()
            deviceConfigStore.saveCurrentPlaylistVersion(0)
            deviceConfigStore.saveLastSyncTime(TimeFormats.nowIso8601())
            deviceConfigStore.saveLastSyncStatus(getString(R.string.settings_clear_content_done))
            playerController.start(emptyPlaylist)
            Toast.makeText(this@MainActivity, R.string.settings_clear_content_done, Toast.LENGTH_SHORT).show()
        }
    }

    private fun closeApp() {
        finish()
    }

    private fun openManualPlaylistPicker(imageDurationSeconds: Int) {
        pendingManualImageDurationSeconds = imageDurationSeconds.coerceAtLeast(1)
        manualPlaylistPicker.launch(
            arrayOf("image/jpeg", "image/png", "video/mp4")
        )
    }

    private fun handleManualPlaylistFileSelection(uri: Uri?) {
        if (uri == null) {
            Toast.makeText(this, R.string.settings_manual_no_supported_files, Toast.LENGTH_SHORT).show()
            return
        }

        persistManualPlaylistUriPermission(uri)
        manualPlaylistDraftUris.add(uri)
        Toast.makeText(this, R.string.settings_manual_file_added, Toast.LENGTH_SHORT).show()
    }

    private fun saveManualPlaylistDraft(imageDurationSeconds: Int) {
        if (manualPlaylistDraftUris.isEmpty()) {
            Toast.makeText(this, R.string.settings_manual_draft_empty, Toast.LENGTH_SHORT).show()
            return
        }

        pendingManualImageDurationSeconds = imageDurationSeconds.coerceAtLeast(1)
        Toast.makeText(this, R.string.settings_manual_creating, Toast.LENGTH_SHORT).show()
        lifecycleScope.launch {
            val playlist = manualPlaylistStore.createPlaylistFromUris(
                uris = manualPlaylistDraftUris.toList(),
                imageDurationSeconds = pendingManualImageDurationSeconds
            )
            if (playlist.items.isEmpty()) {
                Toast.makeText(
                    this@MainActivity,
                    R.string.settings_manual_no_supported_files,
                    Toast.LENGTH_SHORT
                ).show()
                return@launch
            }

            localPlaylistStore.saveActivePlaylist(playlist)
            manualPlaylistDraftUris.clear()
            deviceConfigStore.savePlaybackMode(PlaybackMode.MANUAL_PLAYLIST)
            deviceConfigStore.saveCurrentPlaylistVersion(playlist.playlistVersion)
            deviceConfigStore.saveLastSyncStatus(getString(R.string.settings_manual_created))
            playerController.start(playlist)
            Toast.makeText(this@MainActivity, R.string.settings_manual_created, Toast.LENGTH_SHORT).show()
        }
    }

    private fun clearManualPlaylistDraft() {
        manualPlaylistDraftUris.clear()
    }

    private fun persistManualPlaylistUriPermission(uri: Uri) {
        runCatching {
            contentResolver.takePersistableUriPermission(uri, Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
    }

    private fun switchToWebSync() {
        deviceConfigStore.savePlaybackMode(PlaybackMode.WEB_SYNC)
        deviceConfigStore.saveLastSyncStatus(getString(R.string.settings_web_sync_enabled))
        syncManager.syncNow()
    }

    private fun getAppVersion(): String {
        return try {
            val packageInfo = packageManager.getPackageInfo(packageName, 0)
            packageInfo.versionName ?: "-"
        } catch (_: PackageManager.NameNotFoundException) {
            "-"
        }
    }

    private companion object {
        private const val REQUIRED_SETTINGS_TAPS = 5
        private const val SETTINGS_TAP_WINDOW_MS = 3000L
        private const val DEFAULT_MANUAL_IMAGE_DURATION_SECONDS = 10
    }
}
