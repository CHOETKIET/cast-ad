package com.castad.player.network

import com.castad.player.data.DeviceConfigResponse
import com.castad.player.data.ApiStatusResponse
import com.castad.player.data.HeartbeatRequest
import com.castad.player.data.Playlist
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface DigitalSignageApi {
    @GET("api/device/config")
    suspend fun getDeviceConfig(
        @Query("device_id") deviceId: String,
        @Query("orientation") orientation: String
    ): Response<DeviceConfigResponse>

    @GET("api/playlist")
    suspend fun getPlaylist(
        @Query("device_id") deviceId: String,
        @Query("orientation") orientation: String
    ): Response<Playlist>

    @POST("api/device/heartbeat")
    suspend fun sendHeartbeat(
        @Body request: HeartbeatRequest
    ): Response<ApiStatusResponse>
}
