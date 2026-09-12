# Cast Ad - ภาพรวมโปรเจกต์ปัจจุบัน

เอกสารนี้สรุปสถานะของโปรเจกต์ในปัจจุบัน เพื่อใช้เป็นข้อมูลก่อนพัฒนาต่อ โปรเจกต์นี้เดิมมาจาก iCHI-COOL Play / ichiplay และตอนนี้เริ่มเปลี่ยนฝั่ง Android เป็น Cast Ad แล้ว แต่ฝั่งเว็บ `web-ichicoolpay` ยังไม่ได้ rename ตาม เพื่อให้ค่อย ๆ ปรับภายหลังอย่างควบคุมได้

## ภาพรวมระบบ

ระบบนี้เป็น Digital Signage สำหรับ Android Box แบ่งเป็น 2 ส่วนหลัก

1. Android Player Application
   - เล่นรูปภาพและวิดีโอแบบเต็มหน้าจอ
   - ดึง playlist จาก PHP API
   - ดาวน์โหลด media มาเก็บในเครื่องก่อนเล่น
   - เล่นต่อจาก local cache ได้เมื่อ internet มีปัญหา
   - ส่ง heartbeat กลับไปหา server
   - มี hidden settings สำหรับตั้งค่า device หน้างาน

2. Web Admin + PHP API + MySQL
   - จัดการ device
   - upload media
   - สร้างและแก้ playlist
   - assign playlist ให้ device
   - ให้ Android เรียก API สำหรับ config, playlist และ heartbeat

## สถานะการ rename

ฝั่ง Android เปลี่ยน package หลักแล้วเป็น:

```text
com.castad.player
```

ค่าที่เปลี่ยนแล้ว:

- Gradle root project: `cast-ad`
- Android `namespace`: `com.castad.player`
- Android `applicationId`: `com.castad.player`
- Kotlin package และ source path: `app/src/main/java/com/castad/player`
- Theme ภายใน: `Theme.CastAd`
- SharedPreferences name: `cast_ad_device_config`

ค่าที่ยังไม่ได้เปลี่ยนเพราะเป็น UI/branding หรือฝั่งเว็บ:

- `app_name` ยังเป็น `iCHI-COOL Play`
- ข้อความใน UI บางจุดยังมีชื่อเดิม
- launcher/logo resource ยังมีชื่อ `icon_ichiplay`
- โฟลเดอร์และ config ฝั่งเว็บยังเป็น `web-ichicoolpay`, `ICHICOOL_*`, `ichiplay.ichicool.com`

## การทำงานฝั่ง Android

### Flow ตอนเปิดแอป

ไฟล์หลัก: `app/src/main/java/com/castad/player/MainActivity.kt`

เมื่อเปิดแอป:

1. ซ่อน status bar และ navigation bar เพื่อให้แสดงเต็มจอ
2. เปิดโหมด keep screen on
3. โหลดหรือสร้าง Device ID จาก `DeviceConfigStore`
4. โหลด playlist ที่ active อยู่ใน local storage
5. เริ่มเล่น playlist ทันทีผ่าน `PlayerController`
6. เริ่ม background sync ผ่าน `SyncManager`
7. เริ่ม heartbeat ผ่าน `HeartbeatManager`
8. ตั้ง hidden hotspot สำหรับเปิด settings

แนวคิดสำคัญคือ app จะเริ่มเล่นจาก local playlist ก่อนเสมอ ไม่รอ server เพื่อกัน black screen เมื่อ internet/server มีปัญหา

### การเล่นรูปและวิดีโอ

ไฟล์หลัก: `app/src/main/java/com/castad/player/player/PlayerController.kt`

สิ่งที่ทำได้:

- เล่นรูปภาพผ่าน `ImageView`
- เล่นวิดีโอผ่าน ExoPlayer / Media3
- เรียง playlist ตาม `sortOrder`
- เล่นวน loop เมื่อจบ playlist
- รูปภาพใช้ `durationSeconds` ถ้าไม่มีจะใช้ค่า default 10 วินาที
- ถ้า item เล่นไม่ได้ จะข้ามไป item ถัดไป
- ถ้าเล่นไม่ได้ครบทุก item จะแสดง fallback image `default_no_content`

สิ่งที่ต้องระวัง:

- `playbackSource` ที่ไม่ใช่ `android.resource://` ต้องเป็น path ไฟล์ local ที่มีอยู่จริง
- ถ้าเปลี่ยนวิธีเก็บไฟล์ ต้องให้ `PlaylistItem.localPath` หรือ `playbackSource` ยังคงชี้ไปหาไฟล์จริง
- วิดีโอใช้ ExoPlayer state `STATE_ENDED` เพื่อไป item ถัดไป ถ้าเพิ่ม feature pause/seek ต้องระวังไม่ให้ชนกับ loop logic

### การ sync playlist จาก server

ไฟล์หลัก: `app/src/main/java/com/castad/player/sync/SyncManager.kt`

Flow:

1. ถ้าอยู่ใน `MANUAL_PLAYLIST` mode จะไม่ sync web playlist
2. อ่าน `deviceId` และ `apiBaseUrl`
3. เรียก `GET /api/device/config`
4. บันทึก sync interval และ heartbeat interval จาก server
5. เช็ก `force_resync_token`
6. ถ้าไม่มี playlist active จะล้าง local content
7. ถ้า version ใหม่กว่า local หรือมี force re-sync จะเรียก `GET /api/playlist`
8. ดาวน์โหลด media ทุกไฟล์ผ่าน `MediaCacheManager`
9. save เป็น pending playlist
10. activate pending playlist เป็น active playlist
11. เริ่มเล่น playlist ใหม่
12. ถ้าเป็น force re-sync จะบันทึก token ที่ apply แล้ว เพื่อส่งกลับใน heartbeat

สิ่งที่ต้องระวัง:

- `apiBaseUrl` ต้องขึ้นต้นด้วย `https://`
- ถ้า server ส่ง version เท่าเดิม Android จะไม่โหลดใหม่ ยกเว้นมี force re-sync token
- ถ้า playlist ว่าง Android จะล้าง active playlist และแสดง fallback
- การเปลี่ยนโครงสร้าง JSON ของ API ต้องแก้ data model ฝั่ง Android ด้วย
- ห้ามลบ pending/active playlist flow แบบไม่คิด เพราะช่วยกันกรณีดาวน์โหลดไม่ครบแล้ว playlist เสีย

### การ cache media

ไฟล์หลัก: `app/src/main/java/com/castad/player/sync/MediaCacheManager.kt`

สิ่งที่ทำได้:

- ดาวน์โหลดเฉพาะ URL ที่ขึ้นต้นด้วย `https://`
- เก็บไฟล์ไว้ใน internal storage:
  - active media: `filesDir/media/active`
  - temporary download: `filesDir/media/tmp`
- ตรวจไฟล์ cache เดิมจาก file size ถ้าใช้ได้จะไม่ดาวน์โหลดซ้ำ
- ตั้งชื่อไฟล์จาก `mediaId` และ playlist item `id`

สิ่งที่ต้องระวัง:

- Media URL จาก server ต้องเป็น HTTPS ไม่เช่นนั้น Android จะ reject
- ถ้าเปลี่ยน domain/base URL ต้องมั่นใจว่า media เปิดผ่าน HTTPS ได้จริง
- ถ้า server ส่ง `file_size` ผิด Android อาจดาวน์โหลดซ้ำหรือมอง cache เดิมว่าใช้ไม่ได้
- ตอนนี้ยังไม่มี checksum จริง ค่า `checksum` ใน API เป็น `null`

### Local playlist storage

ไฟล์หลัก: `app/src/main/java/com/castad/player/storage/LocalPlaylistStore.kt`

ไฟล์ที่เก็บ:

- active playlist: `filesDir/playlists/active_playlist.json`
- pending playlist: `filesDir/playlists/pending_playlist.json`

สิ่งที่ทำได้:

- โหลด active playlist ตอนเปิดแอป
- ถ้ายังไม่มี active playlist จะใช้ mock playlist ที่แสดง `default_no_content`
- save pending playlist ก่อน activate
- clear active playlist เป็น empty playlist
- save active playlist โดยตรงสำหรับ manual playlist

สิ่งที่ต้องระวัง:

- ถ้าแก้ schema ของ `Playlist` หรือ `PlaylistItem` ต้องคิดเรื่องไฟล์ JSON เก่าที่ค้างในเครื่องด้วย
- ถ้า local playlist parse ไม่ได้ ตอนนี้จะ fallback เป็น mock playlist

### Device config และ settings

ไฟล์หลัก:

- `app/src/main/java/com/castad/player/storage/DeviceConfigStore.kt`
- `app/src/main/java/com/castad/player/ui/HiddenSettingsDialog.kt`

ค่าที่เก็บใน SharedPreferences:

- Device ID
- API Base URL
- Current playlist version
- Last sync time/status
- Last heartbeat time/status
- Last boot launch time
- Last connection test status
- Sync interval
- Heartbeat interval
- Playback mode
- Last applied force re-sync token

Device ID default สร้างจาก Android ID และขึ้นต้นด้วย `SCREEN-`

Hidden settings เปิดโดยแตะ hotspot 5 ครั้งภายใน 3 วินาที

สิ่งที่ทำได้ใน settings:

- ดู app version, playback mode, playlist version
- แก้ Device ID
- แก้ API Base URL
- Sync Now
- Connection Test
- Clear Content
- สร้าง Manual Playlist จากไฟล์ในเครื่อง
- กลับไปใช้ Web Sync
- ปิดแอป

สิ่งที่ต้องระวัง:

- ถ้าเปลี่ยน SharedPreferences name อีก เครื่องเดิมจะเหมือน reset settings ทั้งหมด
- ถ้าเปลี่ยน Device ID ต้องมี device เดียวกันใน Web Admin ไม่เช่นนั้น API จะตอบ 404
- Manual Playlist mode จะหยุด web sync จนกว่าจะกดกลับไปใช้ Web Sync

### Manual Playlist

ไฟล์หลัก: `app/src/main/java/com/castad/player/storage/ManualPlaylistStore.kt`

สิ่งที่ทำได้:

- เลือกไฟล์ JPG, PNG, MP4 จาก Android document picker
- copy ไฟล์เข้า internal storage: `filesDir/manual_media`
- สร้าง playlist local id `-100`
- รูปภาพใช้ duration จาก settings
- วิดีโอเล่นตามความยาวไฟล์

สิ่งที่ต้องระวัง:

- การสร้าง manual playlist ใหม่จะลบ `manual_media` เดิม
- ถ้าเลือกไฟล์ชนิดอื่นจะถูกข้าม
- Manual Playlist ไม่เกี่ยวกับ server และไม่อัปโหลดกลับ web

### Heartbeat

ไฟล์หลัก: `app/src/main/java/com/castad/player/sync/HeartbeatManager.kt`

ส่งข้อมูลไปที่ `POST /api/device/heartbeat`:

- `device_id`
- `app_version`
- `current_playlist_version`
- `last_sync_time`
- `last_applied_resync_token`

สิ่งที่ต้องระวัง:

- ถ้าไม่มี API Base URL จะไม่ส่ง heartbeat
- ถ้า device ถูกปิดใช้งานในเว็บ heartbeat จะ fail
- force re-sync จะถือว่าสำเร็จฝั่งเว็บเมื่อ heartbeat ส่ง token ที่ apply แล้วกลับไปตรงกัน

### Auto launch ตอนเปิดเครื่อง

ไฟล์หลัก:

- `app/src/main/AndroidManifest.xml`
- `app/src/main/java/com/castad/player/boot/BootReceiver.kt`

สิ่งที่ทำได้:

- รับ `BOOT_COMPLETED`
- รับ `MY_PACKAGE_REPLACED`
- บันทึก last boot launch time
- เปิด `MainActivity`

สิ่งที่ต้องระวัง:

- Android บางรุ่นหรือ Android Box บาง firmware อาจต้องให้สิทธิ์ auto-start เพิ่มเอง
- ถ้าเปลี่ยน activity/package ต้องเช็ก receiver และ manifest เสมอ

## การทำงานฝั่ง Web Admin และ API

ฝั่งเว็บยังอยู่ในโฟลเดอร์ `web-ichicoolpay` และยังใช้ชื่อ/config เดิมหลายจุด

### Web Admin

ไฟล์ public หลัก:

- `web-ichicoolpay/public/login.php` - login admin
- `web-ichicoolpay/public/dashboard.php` - dashboard สถานะภาพรวม
- `web-ichicoolpay/public/devices.php` - รายการ device, ปิดใช้งาน, force re-sync
- `web-ichicoolpay/public/device-form.php` - เพิ่ม/แก้ device
- `web-ichicoolpay/public/media.php` - media library
- `web-ichicoolpay/public/media-upload.php` - upload media
- `web-ichicoolpay/public/playlists.php` - รายการ playlist
- `web-ichicoolpay/public/playlist-edit.php` - สร้าง/แก้ playlist และ item
- `web-ichicoolpay/public/assign-playlist.php` - assign playlist ให้ device

สิ่งที่ทำได้:

- Admin login
- จัดการ device
- upload JPG, PNG, MP4
- สร้าง playlist
- เพิ่ม media เข้า playlist
- กำหนด duration สำหรับรูป
- จัดลำดับ playlist item
- assign playlist ให้ device
- deactivate media/playlist/device
- กด Re-sync Now เพื่อบังคับให้ Android โหลดใหม่

### PHP API

ไฟล์ API หลัก:

- `web-ichicoolpay/public/api/device/config.php`
- `web-ichicoolpay/public/api/playlist.php`
- `web-ichicoolpay/public/api/device/heartbeat.php`
- `web-ichicoolpay/public/router.php`

Endpoints:

```text
GET /api/device/config?device_id=...
GET /api/playlist?device_id=...
POST /api/device/heartbeat
```

`/api/device/config` ส่งกลับ:

- device status
- playlist id/version
- sync interval
- heartbeat interval
- force re-sync token
- server time

`/api/playlist` ส่งกลับ:

- playlist id/version
- items
- media type
- media URL
- duration
- sort order
- file size

`/api/device/heartbeat` ทำหน้าที่:

- อัปเดต device เป็น online
- บันทึก last seen
- บันทึก app version
- บันทึก current playlist version
- บันทึก last sync time
- mark force re-sync completed ถ้า token ตรง

สิ่งที่ต้องระวัง:

- Android ต้องใช้ site root เป็น API Base URL เช่น `https://example.com`
- URL ที่ API ส่งให้ media ต้องเปิดผ่าน HTTPS ได้จริง
- ถ้า playlist ถูก inactive API จะส่ง playlist ว่าง เพื่อให้ Android ล้าง content เดิม
- ถ้า media ถูก inactive API จะไม่ส่ง media นั้นใน playlist

### Models และ services ฝั่งเว็บ

ไฟล์หลัก:

- `web-ichicoolpay/app/models/Device.php`
- `web-ichicoolpay/app/models/DevicePlaylist.php`
- `web-ichicoolpay/app/models/Media.php`
- `web-ichicoolpay/app/models/Playlist.php`
- `web-ichicoolpay/app/models/PlaylistItem.php`
- `web-ichicoolpay/app/services/MediaUploadService.php`
- `web-ichicoolpay/app/services/PlaylistVersionService.php`
- `web-ichicoolpay/app/services/DeviceStatusService.php`

จุดสำคัญ:

- `Playlist::incrementVersion()` เป็นกลไกสำคัญที่ทำให้ Android รู้ว่าต้องโหลด content ใหม่
- `Device::requestForceResync()` สร้าง token เพื่อบังคับ sync ใหม่
- `Device::recordHeartbeat()` ใช้ mark online และ mark force re-sync completed
- `MediaUploadService` จำกัดไฟล์สูงสุด 500 MB และรองรับ JPG, PNG, MP4

## Database

ไฟล์ schema: `web-ichicoolpay/database/schema.sql`

Tables หลัก:

- `users` - admin users
- `devices` - Android devices
- `media` - media library
- `playlists` - playlist header
- `playlist_items` - media ใน playlist
- `device_playlist` - playlist ที่ assign ให้ device

Column ที่เกี่ยวกับ sync สำคัญ:

- `devices.current_playlist_version`
- `devices.last_sync_time`
- `devices.force_resync_token`
- `devices.force_resync_requested_at`
- `devices.force_resync_completed_at`
- `devices.last_seen`
- `playlists.version`
- `playlist_items.sort_order`
- `playlist_items.duration_seconds`
- `media.public_url`
- `media.file_size`

## ไฟล์หลักของ Android

```text
settings.gradle.kts
app/build.gradle.kts
app/src/main/AndroidManifest.xml
app/src/main/java/com/castad/player/MainActivity.kt
app/src/main/java/com/castad/player/boot/BootReceiver.kt
app/src/main/java/com/castad/player/player/PlayerController.kt
app/src/main/java/com/castad/player/sync/SyncManager.kt
app/src/main/java/com/castad/player/sync/HeartbeatManager.kt
app/src/main/java/com/castad/player/sync/MediaCacheManager.kt
app/src/main/java/com/castad/player/storage/DeviceConfigStore.kt
app/src/main/java/com/castad/player/storage/LocalPlaylistStore.kt
app/src/main/java/com/castad/player/storage/ManualPlaylistStore.kt
app/src/main/java/com/castad/player/network/ApiClient.kt
app/src/main/java/com/castad/player/network/DigitalSignageApi.kt
app/src/main/java/com/castad/player/data/ApiModels.kt
app/src/main/java/com/castad/player/data/PlaylistModels.kt
app/src/main/res/values/strings.xml
app/src/main/res/layout/activity_main.xml
```

## ไฟล์หลักของ Web

```text
web-ichicoolpay/app/config/config.php
web-ichicoolpay/app/config/database.php
web-ichicoolpay/app/core/bootstrap.php
web-ichicoolpay/app/core/api.php
web-ichicoolpay/app/core/auth.php
web-ichicoolpay/app/models/Device.php
web-ichicoolpay/app/models/DevicePlaylist.php
web-ichicoolpay/app/models/Media.php
web-ichicoolpay/app/models/Playlist.php
web-ichicoolpay/app/models/PlaylistItem.php
web-ichicoolpay/app/services/MediaUploadService.php
web-ichicoolpay/app/services/PlaylistVersionService.php
web-ichicoolpay/public/api/device/config.php
web-ichicoolpay/public/api/device/heartbeat.php
web-ichicoolpay/public/api/playlist.php
web-ichicoolpay/database/schema.sql
```

## จุดที่ต้องระวังมากเมื่อพัฒนาต่อ

### 1. Package / applicationId

ตอนนี้ Android app เป็น `com.castad.player` แล้ว ถ้าเปลี่ยนอีก:

- แอปเดิมบนเครื่องจะถูกมองเป็นคนละแอป
- SharedPreferences และ local files เดิมจะไม่ตามไป
- Device heartbeat อาจแสดง app version/package ใหม่ไม่ต่อเนื่อง

### 2. Device ID

Device ID คือ key สำคัญที่ Android ใช้คุยกับ server ถ้าเปลี่ยน:

- ต้องมี device record ใน Web Admin ตรงกัน
- playlist assignment เดิมจะไม่ตามไป
- heartbeat จะ fail ถ้า device ไม่ active

### 3. Playlist version

Android ตัดสินใจโหลดใหม่จาก `playlist_version`

หากแก้ playlist/media แล้วไม่เพิ่ม version:

- Android จะคิดว่า playlist ล่าสุดอยู่แล้ว
- content ใหม่จะไม่ถูกโหลด

หากเพิ่ม version บ่อยเกิน:

- Android จะดาวน์โหลดซ้ำโดยไม่จำเป็น

### 4. Media URL และ HTTPS

Android บังคับทั้ง API base URL และ media URL เป็น HTTPS

ถ้าใช้ HTTP:

- API client จะ reject base URL
- Media downloader จะ throw error และไม่ activate playlist ใหม่

### 5. Local-first playback

พฤติกรรมสำคัญของ app คือเล่น local content ก่อน แล้ว sync ทีหลัง

อย่าเปลี่ยนให้ app รอ server ก่อนเล่น เว้นแต่ตั้งใจรับความเสี่ยงเรื่อง black screen

### 6. Pending playlist

Pending playlist มีไว้เพื่อกันกรณีดาวน์โหลด media ไม่ครบ

ถ้าเปลี่ยน flow นี้ ต้องมั่นใจว่า:

- ดาวน์โหลด media ครบก่อน activate
- active playlist เดิมยังอยู่ถ้า sync ใหม่ fail
- ไม่เขียน active playlist ทับด้วย playlist ที่ไฟล์ไม่ครบ

### 7. Manual Playlist mode

ถ้าอยู่ใน manual mode:

- Web sync จะถูกข้าม
- heartbeat ยังทำงานได้
- ต้องกดกลับ Web Sync จึงจะโหลด playlist จากเว็บอีกครั้ง

### 8. Force re-sync

Force re-sync พึ่ง token จาก web และ token ที่ Android ส่งกลับใน heartbeat

หากเปลี่ยนชื่อ field เหล่านี้ต้องแก้พร้อมกันทั้งสองฝั่ง:

- `force_resync_token`
- `force_resync_requested_at`
- `last_applied_resync_token`

### 9. PHP 7.4 compatibility

Requirement เดิมระบุว่า hosting ใช้ PHP 7.4.26 ดังนั้นฝั่งเว็บควรหลีกเลี่ยง syntax ที่ต้องใช้ PHP ใหม่กว่า 7.4

### 10. Production config

ก่อน deploy ต้องตรวจ:

- `web-ichicoolpay/app/config/config.php`
- `web-ichicoolpay/app/config/database.php`
- writable folders สำหรับ upload
- database schema/migration
- default admin password
- HTTPS domain

## สิ่งที่ยังควรทำต่อ

1. Rename ฝั่งเว็บจาก `web-ichicoolpay` / `ICHICOOL_*` / `ichiplay` เป็น Cast Ad อย่างเป็นระบบ
2. เปลี่ยน `app_name`, logo, launcher icon และข้อความ UI ให้เป็น Cast Ad
3. ตรวจ flow บน Android Box จริง โดยเฉพาะ auto launch หลัง boot
4. เพิ่ม checksum สำหรับ media เพื่อให้ cache validation แข็งแรงขึ้น
5. เพิ่ม test สำหรับ API payload และ Android sync cases สำคัญ
6. ทำเอกสาร deployment แยกสำหรับ production domain ใหม่
7. พิจารณา migration สำหรับเครื่องที่เคยติดตั้ง package/app เก่า หากต้องการรักษาข้อมูลเดิม

## คำสั่งตรวจสอบที่ใช้ล่าสุด

รัน unit test ฝั่ง Android ผ่านแล้ว:

```text
./gradlew testDebugUnitTest
```

หมายเหตุ: เครื่องนี้ต้องใช้ JDK ที่มากับ Android Studio และ Gradle cache ชั่วคราวตอนรันจาก shell
