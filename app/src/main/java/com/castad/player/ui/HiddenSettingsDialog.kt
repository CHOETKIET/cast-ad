package com.castad.player.ui

import android.content.Context
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.text.InputType
import android.view.inputmethod.EditorInfo
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import com.castad.player.R
import com.castad.player.network.ApiClient
import com.castad.player.storage.DeviceConfigStore
import com.castad.player.storage.DeviceConfigStore.PlaybackMode
import com.castad.player.util.TimeFormats
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class HiddenSettingsDialog(
    private val context: Context,
    private val configStore: DeviceConfigStore,
    private val onSyncNow: () -> Unit,
    private val onClearContent: () -> Unit,
    private val manualPlaylistDraftCount: Int,
    private val onAddManualPlaylistFile: (Int) -> Unit,
    private val onSaveManualPlaylist: (Int) -> Unit,
    private val onClearManualPlaylistDraft: () -> Unit,
    private val onSwitchToWebSync: () -> Unit,
    private val onExitApp: () -> Unit,
    private val onDismiss: () -> Unit
) {
    private val dialogScope = CoroutineScope(Dispatchers.Main)

    fun show() {
        val content = LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(dp(22), dp(18), dp(22), dp(6))
        }

        val deviceIdInput = createInput(
            hint = context.getString(R.string.settings_device_id),
            value = configStore.getDeviceId(),
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_FLAG_CAP_CHARACTERS
        )
        val apiBaseUrlInput = createInput(
            hint = context.getString(R.string.settings_api_base_url),
            value = configStore.getApiBaseUrl(),
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI
        )
        val connectionStatusText = createInfoText(
            context.getString(R.string.settings_last_connection_test_status),
            configStore.getLastConnectionTestStatus() ?: "-"
        )
        val manualDurationInput = createInput(
            hint = context.getString(R.string.settings_manual_image_duration),
            value = DEFAULT_MANUAL_IMAGE_DURATION_SECONDS.toString(),
            inputType = InputType.TYPE_CLASS_NUMBER
        )
        val syncNowButton = createButton(context.getString(R.string.settings_sync_now))
        val connectionTestButton = createButton(context.getString(R.string.settings_connection_test))
        val clearContentButton = createButton(context.getString(R.string.settings_clear_content))
        val addManualFileButton = createButton(context.getString(R.string.settings_manual_pick_files))
        val saveManualPlaylistButton = createButton(context.getString(R.string.settings_manual_save_playlist))
        val clearManualDraftButton = createButton(context.getString(R.string.settings_manual_clear_draft))
        val switchWebSyncButton = createButton(context.getString(R.string.settings_switch_web_sync))
        val exitAppButton = createButton(context.getString(R.string.settings_exit_app))
        val saveSettingsButton = createButton(context.getString(R.string.settings_save_settings))

        content.addView(
            createSection(
                title = context.getString(R.string.settings_section_device),
                rows = listOf(
                    context.getString(R.string.settings_app_version) to getAppVersion(),
                    context.getString(R.string.settings_playback_mode) to playbackModeLabel(configStore.getPlaybackMode()),
                    context.getString(R.string.settings_playlist_version) to configStore.getCurrentPlaylistVersion().toString(),
                    context.getString(R.string.settings_last_boot_launch_time) to (configStore.getLastBootLaunchTime() ?: "-")
                )
            )
        )
        content.addView(
            createSection(
                title = context.getString(R.string.settings_section_status),
                rows = listOf(
                    context.getString(R.string.settings_last_sync_time) to (configStore.getLastSyncTime() ?: "-"),
                    context.getString(R.string.settings_last_sync_status) to (configStore.getLastSyncStatus() ?: "-"),
                    context.getString(R.string.settings_last_heartbeat_time) to (configStore.getLastHeartbeatTime() ?: "-"),
                    context.getString(R.string.settings_last_heartbeat_status) to (configStore.getLastHeartbeatStatus() ?: "-"),
                    context.getString(R.string.settings_last_connection_test_status) to (configStore.getLastConnectionTestStatus() ?: "-")
                )
            )
        )
        content.addView(createSectionTitle(context.getString(R.string.settings_section_connection)))
        content.addView(deviceIdInput)
        content.addView(apiBaseUrlInput)
        content.addView(saveSettingsButton)
        content.addView(createSectionTitle(context.getString(R.string.settings_section_tools)))
        content.addView(syncNowButton)
        content.addView(connectionTestButton)
        content.addView(clearContentButton)
        content.addView(createSectionTitle(context.getString(R.string.settings_manual_playlist)))
        content.addView(createInfoText(context.getString(R.string.settings_manual_draft_count), manualPlaylistDraftCount.toString()))
        content.addView(createHelpText(context.getString(R.string.settings_manual_help)))
        content.addView(manualDurationInput)
        content.addView(addManualFileButton)
        content.addView(saveManualPlaylistButton)
        content.addView(clearManualDraftButton)
        content.addView(switchWebSyncButton)
        content.addView(exitAppButton)
        content.addView(connectionStatusText)

        val scrollView = ScrollView(context).apply {
            addView(content)
        }

        AlertDialog.Builder(context)
            .setTitle(R.string.settings_title)
            .setView(scrollView)
            .setNegativeButton(R.string.settings_cancel, null)
            .setNeutralButton(R.string.settings_sync_now) { _, _ ->
                onSyncNow()
                Toast.makeText(context, R.string.settings_sync_started, Toast.LENGTH_SHORT).show()
            }
            .setPositiveButton(R.string.settings_save, null)
            .create()
            .apply {
                setOnDismissListener {
                    onDismiss()
                }
                setOnShowListener {
                    saveSettingsButton.setOnClickListener {
                        saveSettings(deviceIdInput, apiBaseUrlInput) {
                            dismiss()
                        }
                    }
                    syncNowButton.setOnClickListener {
                        onSyncNow()
                        Toast.makeText(context, R.string.settings_sync_started, Toast.LENGTH_SHORT).show()
                        dismiss()
                    }
                    connectionTestButton.setOnClickListener {
                        runConnectionTest(
                            deviceId = deviceIdInput.text.toString().trim(),
                            apiBaseUrl = apiBaseUrlInput.text.toString().trim(),
                            statusText = connectionStatusText,
                            button = connectionTestButton
                        )
                    }
                    clearContentButton.setOnClickListener {
                        confirmAction(
                            title = context.getString(R.string.settings_clear_content_confirm_title),
                            message = context.getString(R.string.settings_clear_content_confirm_message),
                            onConfirm = {
                                onClearContent()
                                Toast.makeText(
                                    context,
                                    R.string.settings_clear_content_started,
                                    Toast.LENGTH_SHORT
                                ).show()
                                dismiss()
                            }
                        )
                    }
                    addManualFileButton.setOnClickListener {
                        val durationSeconds = manualDurationInput.text.toString().toIntOrNull()
                            ?.coerceAtLeast(1)
                            ?: DEFAULT_MANUAL_IMAGE_DURATION_SECONDS
                        Toast.makeText(
                            context,
                            R.string.settings_manual_select_files,
                            Toast.LENGTH_SHORT
                        ).show()
                        dismiss()
                        onAddManualPlaylistFile(durationSeconds)
                    }
                    saveManualPlaylistButton.setOnClickListener {
                        val durationSeconds = manualDurationInput.text.toString().toIntOrNull()
                            ?.coerceAtLeast(1)
                            ?: DEFAULT_MANUAL_IMAGE_DURATION_SECONDS
                        onSaveManualPlaylist(durationSeconds)
                        dismiss()
                    }
                    clearManualDraftButton.setOnClickListener {
                        onClearManualPlaylistDraft()
                        Toast.makeText(context, R.string.settings_manual_draft_cleared, Toast.LENGTH_SHORT).show()
                        dismiss()
                    }
                    switchWebSyncButton.setOnClickListener {
                        onSwitchToWebSync()
                        Toast.makeText(context, R.string.settings_web_sync_enabled, Toast.LENGTH_SHORT).show()
                        dismiss()
                    }
                    exitAppButton.setOnClickListener {
                        confirmAction(
                            title = context.getString(R.string.settings_exit_app_confirm_title),
                            message = context.getString(R.string.settings_exit_app_confirm_message),
                            onConfirm = {
                                dismiss()
                                onExitApp()
                            }
                        )
                    }

                    getButton(AlertDialog.BUTTON_POSITIVE).setOnClickListener {
                        saveSettings(deviceIdInput, apiBaseUrlInput) {
                            dismiss()
                        }
                    }
                }
            }
            .show()
    }

    private fun saveSettings(
        deviceIdInput: EditText,
        apiBaseUrlInput: EditText,
        onSaved: () -> Unit
    ) {
        val deviceId = deviceIdInput.text.toString().trim()
        if (deviceId.isBlank()) {
            deviceIdInput.error = context.getString(R.string.settings_device_id_required)
            return
        }

        configStore.saveDeviceId(deviceId)
        configStore.saveApiBaseUrl(apiBaseUrlInput.text.toString())
        Toast.makeText(context, R.string.settings_saved, Toast.LENGTH_SHORT).show()
        onSaved()
    }

    private fun createInput(hint: String, value: String, inputType: Int): EditText {
        return EditText(context).apply {
            this.hint = hint
            setText(value)
            this.inputType = inputType
            imeOptions = EditorInfo.IME_ACTION_NEXT
            setSingleLine(true)
            textSize = 15f
            setPadding(dp(12), dp(4), dp(12), dp(4))
        }
    }

    private fun createSection(title: String, rows: List<Pair<String, String>>): LinearLayout {
        return LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            background = roundedBackground(fillColor = "#F4FAFF", strokeColor = "#CFE9FF")
            setPadding(dp(14), dp(12), dp(14), dp(10))
            addView(createSectionTitle(title))
            rows.forEach { (label, value) ->
                addView(createInfoText(label, value))
            }
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                bottomMargin = dp(12)
            }
        }
    }

    private fun createSectionTitle(text: String): TextView {
        return TextView(context).apply {
            this.text = text
            setTextColor(Color.parseColor("#0B67B2"))
            setTypeface(typeface, Typeface.BOLD)
            textSize = 15f
            setPadding(0, 0, 0, dp(8))
        }
    }

    private fun createButton(text: String): Button {
        return Button(context).apply {
            this.text = text
            isAllCaps = false
            textSize = 14f
            setPadding(dp(12), dp(8), dp(12), dp(8))
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                topMargin = dp(8)
                bottomMargin = dp(8)
            }
        }
    }

    private fun createInfoText(label: String, value: String): TextView {
        return TextView(context).apply {
            text = "$label: $value"
            setTextColor(Color.parseColor("#24384A"))
            textSize = 14f
            setPadding(0, dp(4), 0, dp(4))
        }
    }

    private fun createHelpText(text: String): TextView {
        return TextView(context).apply {
            this.text = text
            setTextColor(Color.parseColor("#53677A"))
            textSize = 13f
            setPadding(0, 0, 0, dp(8))
        }
    }

    private fun runConnectionTest(
        deviceId: String,
        apiBaseUrl: String,
        statusText: TextView,
        button: Button
    ) {
        if (deviceId.isBlank()) {
            statusText.text = statusLine(context.getString(R.string.settings_device_id_required))
            return
        }

        button.isEnabled = false
        statusText.text = statusLine(context.getString(R.string.settings_connection_testing))

        dialogScope.launch {
            val result = testConnection(deviceId, apiBaseUrl)
            configStore.saveLastConnectionTestStatus(result)
            statusText.text = statusLine(result)
            Toast.makeText(context, result, Toast.LENGTH_SHORT).show()
            button.isEnabled = true
        }
    }

    private suspend fun testConnection(deviceId: String, apiBaseUrl: String): String {
        return withContext(Dispatchers.IO) {
            try {
                val api = ApiClient.create(apiBaseUrl)
                val response = api.getDeviceConfig(deviceId)
                val body = response.body()
                val message = when {
                    !response.isSuccessful || body == null -> context.getString(R.string.settings_connection_failed)
                    !body.success -> body.message?.takeIf { it.isNotBlank() }
                        ?: context.getString(R.string.settings_connection_failed)
                    body.playlistId == null || body.playlistVersion <= 0 -> context.getString(R.string.settings_connection_no_playlist)
                    else -> context.getString(R.string.settings_connection_success)
                }
                "${TimeFormats.nowIso8601()} - $message"
            } catch (error: CancellationException) {
                throw error
            } catch (error: IllegalArgumentException) {
                "${TimeFormats.nowIso8601()} - ${context.getString(R.string.settings_connection_invalid_url)}"
            } catch (error: Exception) {
                "${TimeFormats.nowIso8601()} - ${context.getString(R.string.settings_connection_failed)}"
            }
        }
    }

    private fun statusLine(value: String): String {
        return "${context.getString(R.string.settings_last_connection_test_status)}: $value"
    }

    private fun playbackModeLabel(mode: PlaybackMode): String {
        return mode.label
    }

    private fun confirmAction(title: String, message: String, onConfirm: () -> Unit) {
        AlertDialog.Builder(context)
            .setTitle(title)
            .setMessage(message)
            .setNegativeButton(R.string.settings_cancel, null)
            .setPositiveButton(R.string.settings_confirm) { _, _ ->
                onConfirm()
            }
            .show()
    }

    private fun roundedBackground(fillColor: String, strokeColor: String): GradientDrawable {
        return GradientDrawable().apply {
            shape = GradientDrawable.RECTANGLE
            cornerRadius = dp(8).toFloat()
            setColor(Color.parseColor(fillColor))
            setStroke(dp(1), Color.parseColor(strokeColor))
        }
    }

    private fun dp(value: Int): Int {
        return (value * context.resources.displayMetrics.density).toInt()
    }

    private fun getAppVersion(): String {
        return try {
            val packageInfo = context.packageManager.getPackageInfo(context.packageName, 0)
            packageInfo.versionName ?: "-"
        } catch (_: PackageManager.NameNotFoundException) {
            "-"
        }
    }

    private companion object {
        private const val DEFAULT_MANUAL_IMAGE_DURATION_SECONDS = 10
    }
}
