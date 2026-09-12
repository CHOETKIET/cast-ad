package com.castad.player.network

import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
    const val DEFAULT_SYNC_INTERVAL_SECONDS = 60
    const val DEFAULT_HEARTBEAT_INTERVAL_SECONDS = 60

    fun create(baseUrl: String): DigitalSignageApi {
        val normalizedBaseUrl = normalizeBaseUrl(baseUrl)
        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BASIC
        }
        val httpClient = OkHttpClient.Builder()
            .connectTimeout(DEFAULT_TIMEOUT_SECONDS, TimeUnit.SECONDS)
            .readTimeout(DEFAULT_TIMEOUT_SECONDS, TimeUnit.SECONDS)
            .writeTimeout(DEFAULT_TIMEOUT_SECONDS, TimeUnit.SECONDS)
            .addInterceptor(loggingInterceptor)
            .build()

        return Retrofit.Builder()
            .baseUrl(normalizedBaseUrl)
            .client(httpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(DigitalSignageApi::class.java)
    }

    private fun normalizeBaseUrl(baseUrl: String): String {
        val trimmedBaseUrl = baseUrl.trim()
        require(trimmedBaseUrl.startsWith("https://")) {
            "API Base URL must start with https://"
        }
        return if (trimmedBaseUrl.endsWith("/")) trimmedBaseUrl else "$trimmedBaseUrl/"
    }

    private const val DEFAULT_TIMEOUT_SECONDS = 20L
}
