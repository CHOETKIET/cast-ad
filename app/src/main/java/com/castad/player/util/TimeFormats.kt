package com.castad.player.util

import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone

object TimeFormats {
    fun nowIso8601(): String {
        val formatter = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ", Locale.US)
        formatter.timeZone = TimeZone.getDefault()
        val formatted = formatter.format(Date())
        return formatted.replace(Regex("([+-]\\d{2})(\\d{2})$"), "$1:$2")
    }
}
