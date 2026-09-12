package com.castad.player.boot

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.castad.player.MainActivity
import com.castad.player.storage.DeviceConfigStore
import com.castad.player.util.TimeFormats

class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action !in SUPPORTED_ACTIONS) {
            return
        }

        DeviceConfigStore(context).saveLastBootLaunchTime(TimeFormats.nowIso8601())

        val launchIntent = Intent(context, MainActivity::class.java).apply {
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP)
            addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP)
        }
        context.startActivity(launchIntent)
    }

    private companion object {
        private val SUPPORTED_ACTIONS = setOf(
            Intent.ACTION_BOOT_COMPLETED,
            Intent.ACTION_MY_PACKAGE_REPLACED
        )
    }
}
