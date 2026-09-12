# แผนพัฒนาระบบฝั่ง Android Studio

โปรเจกต์: ichi-play  
รูปแบบเริ่มต้น: Empty Views Activity  
ภาษา: Kotlin  
Minimum SDK: API 23 Android 6.0 Marshmallow  
เป้าหมาย: Android Player Application สำหรับ Digital Signage บน Android Box HIMEDIA SB-370H

เอกสารนี้เป็นแผนพัฒนาเฉพาะฝั่ง Android Studio ก่อนเริ่มลงมือเขียนโค้ดจริง เพื่อให้ตรวจสอบและอนุมัติแนวทางก่อน

---

## 1. ขอบเขต Android MVP

Android Application ต้องทำหน้าที่เป็น Player หลักสำหรับแสดงสื่อแบบเต็มหน้าจอ โดยมีความสามารถดังนี้

1. แสดงรูปภาพ JPG และ PNG แบบ Full Screen
2. เล่นวิดีโอ MP4 แบบ Full Screen
3. เล่น Playlist ที่มีทั้งรูปและวิดีโอรวมกัน
4. ตั้งเวลาแสดงรูปภาพแต่ละรายการได้
5. เล่นวนซ้ำอัตโนมัติเมื่อจบ Playlist
6. ซ่อน Status Bar และ Navigation Bar
7. เปิด App อัตโนมัติเมื่อ Android Box เปิดเครื่อง
8. มี Device ID ประจำเครื่อง เช่น `SCREEN001`
9. ติดต่อ PHP API ผ่าน HTTPS
10. ตรวจสอบ Playlist จาก Server เป็นระยะ เช่น ทุก 60 วินาที
11. ถ้า Server มี Playlist Version ใหม่กว่าเครื่อง ให้ดาวน์โหลด Playlist ใหม่
12. ดาวน์โหลด Media มาเก็บใน Local Storage ก่อนเล่น
13. เล่น Content ที่ดาวน์โหลดไว้ต่อได้แม้ Internet ขาด
14. ห้ามเกิด Black Screen เพียงเพราะ Server ติดต่อไม่ได้
15. ส่ง Heartbeat ไป Server เป็นระยะ พร้อมข้อมูล Device ID, App Version, Current Playlist Version และ Last Sync Time

---

## 2. แนวทางสถาปัตยกรรม Android

ใช้โครงสร้างแบบแบ่งหน้าที่ชัดเจน แต่ยังคงเหมาะกับ MVP ไม่ซับซ้อนเกินไป

```text
MainActivity
  |
  |-- PlayerController
  |     |-- Image Player
  |     |-- Video Player
  |     |-- Playlist Loop
  |
  |-- SyncManager
  |     |-- ตรวจ config/version จาก API
  |     |-- ดาวน์โหลด playlist/media
  |     |-- สลับไปใช้ playlist ใหม่เมื่อดาวน์โหลดสำเร็จ
  |
  |-- HeartbeatManager
  |     |-- ส่งสถานะ device ไป Server เป็นระยะ
  |
  |-- LocalPlaylistStore
  |     |-- เก็บ playlist ปัจจุบัน
  |     |-- เก็บ playlist version
  |     |-- เก็บ last sync time
  |
  |-- MediaCacheManager
        |-- ตรวจไฟล์ media ในเครื่อง
        |-- ดาวน์โหลดไฟล์ที่ยังไม่มี
        |-- คืน path ของไฟล์ local สำหรับเล่น
```

หลักสำคัญของระบบ:

- Player ต้องอ่านสื่อจาก Local Storage เป็นหลัก
- Server ใช้สำหรับ sync และ heartbeat เท่านั้น
- Playlist ใหม่จะถูกนำมาใช้ก็ต่อเมื่อดาวน์โหลด media ครบและตรวจสอบสำเร็จ
- ถ้า Server ล่มหรือ Internet ขาด ให้เล่น Playlist เดิมต่อทันที
- ถ้าเครื่องยังไม่มี Playlist ใด ๆ ให้แสดงหน้าจอสถานะที่ไม่ใช่จอดำ เช่น ข้อความรอการตั้งค่าแบบเต็มจอ

---

## 3. Library ที่คาดว่าจะเพิ่ม

ปัจจุบันโปรเจกต์มี AppCompat, ConstraintLayout, Material, Core KTX และ Activity KTX แล้ว

เสนอให้เพิ่ม library ต่อไปนี้:

| หมวด | Library | ใช้สำหรับ |
|---|---|---|
| Video Player | AndroidX Media3 ExoPlayer | เล่น MP4 เต็มจอและควบคุมสถานะ playback |
| HTTP Client | Retrofit + OkHttp | เรียก PHP JSON REST API ผ่าน HTTPS |
| JSON | Gson หรือ Moshi | แปลง JSON config/playlist/heartbeat |
| Async Task | Kotlin Coroutines | sync playlist, download media, heartbeat แบบไม่ค้าง UI |
| Lifecycle | Lifecycle Runtime KTX | ควบคุม coroutine ตาม lifecycle ของ Activity |

หมายเหตุ:

- ยังไม่ใช้ Room ใน MVP แรก เพื่อลดความซับซ้อน
- เก็บ playlist เป็น JSON file ใน app-specific storage และ metadata ใน SharedPreferences
- หากในอนาคตมี scheduling, history, analytics หรือหลาย playlist ต่อ device ค่อยพิจารณา Room

---

## 4. โครงสร้างไฟล์ที่เสนอ

```text
app/src/main/java/com/example/ichi_play/
  MainActivity.kt

  data/
    ApiModels.kt
    PlaylistModels.kt
    DeviceState.kt

  network/
    ApiClient.kt
    DigitalSignageApi.kt

  player/
    PlayerController.kt
    PlaylistPlaybackState.kt

  sync/
    SyncManager.kt
    MediaCacheManager.kt
    HeartbeatManager.kt

  storage/
    DeviceConfigStore.kt
    LocalPlaylistStore.kt

  boot/
    BootReceiver.kt

  ui/
    PlayerStatusView.kt หรือใช้ layout XML แยก
```

Resource ที่คาดว่าจะปรับ:

```text
app/src/main/res/layout/activity_main.xml
app/src/main/res/values/strings.xml
app/src/main/res/values/themes.xml
app/src/main/AndroidManifest.xml
```

---

## 5. Data Model ฝั่ง Android

รูปแบบข้อมูลฝั่ง Android จะออกแบบให้รับกับ API ขั้นต่ำใน requirement

### 5.1 Device Config

ใช้จาก `GET /api/device/config`

```json
{
  "device_id": "SCREEN001",
  "playlist_id": 1,
  "playlist_version": 3,
  "sync_interval_seconds": 60,
  "heartbeat_interval_seconds": 60
}
```

ฝั่ง Android จะใช้ข้อมูลนี้เพื่อตัดสินใจว่า Server มี Playlist ใหม่หรือไม่

### 5.2 Playlist

ใช้จาก `GET /api/playlist`

```json
{
  "playlist_id": 1,
  "playlist_version": 3,
  "items": [
    {
      "id": 101,
      "type": "image",
      "url": "https://domain.com/uploads/image01.jpg",
      "duration_seconds": 10,
      "sort_order": 1,
      "checksum": null
    },
    {
      "id": 102,
      "type": "video",
      "url": "https://domain.com/uploads/video01.mp4",
      "duration_seconds": null,
      "sort_order": 2,
      "checksum": null
    }
  ]
}
```

หมายเหตุ:

- รูปภาพใช้ `duration_seconds`
- วิดีโอเล่นจนจบไฟล์ แล้วไป item ถัดไป
- ถ้า API ยังไม่มี checksum ใน MVP แรก สามารถตรวจจากการดาวน์โหลดสำเร็จและขนาดไฟล์มากกว่า 0 ก่อน

### 5.3 Heartbeat

ใช้กับ `POST /api/device/heartbeat`

```json
{
  "device_id": "SCREEN001",
  "app_version": "1.0",
  "current_playlist_version": 3,
  "last_sync_time": "2026-08-11T10:30:00+07:00"
}
```

---

## 6. Device ID และการตั้งค่าเครื่อง

เสนอแนวทางสำหรับ MVP:

1. App มี `Device ID` เก็บใน SharedPreferences
2. ค่าเริ่มต้นอาจสร้างจาก `ANDROID_ID` เช่น `SCREEN-AB12CD34`
3. รองรับการแก้ Device ID และ API Base URL ผ่านหน้าตั้งค่าแบบซ่อน
4. หน้าตั้งค่าแบบซ่อนเปิดด้วยการแตะมุมจอหลายครั้ง หรือกดปุ่มรีโมตตาม pattern ที่กำหนด

เหตุผล:

- Requirement ยกตัวอย่าง Device ID แบบ `SCREEN001` ซึ่งมักต้องตั้งให้ตรงกับ Web Admin
- Android Box อาจไม่มี keyboard ตลอดเวลา จึงควรมีหน้าตั้งค่าที่เข้าถึงได้จากรีโมตหรือการคลิก
- หากต้องการความง่ายในรอบแรก สามารถ hardcode API Base URL ไว้ใน `BuildConfig` ก่อน แล้วเพิ่มหน้าตั้งค่าทีหลัง

จุดที่ต้องยืนยันก่อนลงมือจริง:

- ต้องการให้ Device ID ตั้งเองในเครื่อง หรือให้สร้างอัตโนมัติแล้วนำไป register ใน Web Admin
- API Base URL จะเป็น domain ใด

---

## 7. Full Screen และ Kiosk Behavior

แนวทางพัฒนา:

1. ปรับ Theme ให้ไม่มี ActionBar
2. ใช้ Immersive Sticky Mode เพื่อซ่อน Status Bar และ Navigation Bar
3. เรียกซ่อน system bars ซ้ำใน `onResume`
4. ป้องกันหน้าจอดับระหว่างเล่นด้วย `FLAG_KEEP_SCREEN_ON`
5. ใช้ layout เต็มจอที่มีเฉพาะพื้นที่แสดง media และ status fallback

หมายเหตุ:

- Android Box บางรุ่นอาจมี Navigation Bar แบบ vendor-specific ที่ซ่อนยาก ต้องทดสอบบน HIMEDIA SB-370H จริง
- MVP จะไม่ทำ Lock Task Mode เต็มรูปแบบ เพราะต้องมีการตั้งค่า Device Owner เพิ่มเติม

---

## 8. Auto Start หลังเปิดเครื่อง

แนวทางพัฒนา:

1. เพิ่ม permission `RECEIVE_BOOT_COMPLETED`
2. เพิ่ม `BootReceiver`
3. เมื่อได้รับ `BOOT_COMPLETED` ให้เปิด `MainActivity`
4. เพิ่ม intent action ที่เกี่ยวข้อง เช่น `LOCKED_BOOT_COMPLETED` หากเหมาะสม

หมายเหตุ:

- Android บางรุ่นบล็อก auto start ตาม policy ของเครื่องหรือ launcher
- ควรทดสอบบน HIMEDIA SB-370H จริง
- หาก boot receiver ไม่ทำงานบน firmware นั้น อาจต้องตั้งค่า launcher หรือ auto-start permission เพิ่มในเครื่อง

---

## 9. Content Sync Flow

ลำดับการทำงานที่เสนอ:

```text
App Start
  |
  |-- Load local playlist
  |-- ถ้ามี local playlist: เริ่มเล่นทันที
  |-- ถ้าไม่มี: แสดง waiting/setup status
  |
  |-- Start sync loop ทุก 60 วินาที
        |
        |-- GET /api/device/config
        |-- เปรียบเทียบ server playlist_version กับ local playlist_version
        |-- ถ้า server version ใหม่กว่า:
              |
              |-- GET /api/playlist
              |-- ดาวน์โหลด media ทั้งหมดลง local storage
              |-- ตรวจว่าไฟล์ครบ
              |-- บันทึก playlist ใหม่
              |-- แจ้ง PlayerController ให้สลับ playlist
        |
        |-- ถ้าเรียก API ไม่ได้:
              |
              |-- log error
              |-- ไม่หยุด player
              |-- รอรอบถัดไป
```

ข้อกำหนดสำคัญ:

- ห้ามลบ Playlist เดิมก่อน Playlist ใหม่พร้อมใช้งาน
- ดาวน์โหลดไฟล์ใหม่ไปยังพื้นที่ temporary ก่อน แล้วค่อยย้ายเป็น active media
- หากดาวน์โหลดบางไฟล์ล้มเหลว ให้ยกเลิกการ switch playlist รอบนั้น
- Player ต้องเล่นต่อระหว่าง sync

---

## 10. Playback Flow

```text
Start Playlist
  |
  |-- item เป็น image:
  |     |-- แสดง ImageView แบบ centerCrop หรือ fitCenter ตามที่กำหนด
  |     |-- รอตาม duration_seconds
  |     |-- ไป item ถัดไป
  |
  |-- item เป็น video:
  |     |-- ส่ง local file path ให้ ExoPlayer
  |     |-- เล่นจนจบ
  |     |-- ไป item ถัดไป
  |
  |-- ถ้าจบรายการสุดท้าย:
        |-- กลับไป item แรก
```

แนวทางป้องกัน Black Screen:

- ถ้า media file หายหรือเล่นไม่ได้ ให้ข้าม item นั้นและไป item ถัดไป
- ถ้าทุก item เล่นไม่ได้ ให้แสดง status fallback เช่น `No playable content`
- เมื่อ playlist ใหม่มีปัญหา ให้กลับไปใช้ playlist เดิม
- ระหว่าง video loading สามารถคง frame/player view เดิมไว้ และไม่ clear screen เป็นสีดำโดยไม่จำเป็น

---

## 11. Local Storage

เสนอใช้ app-specific external files หรือ internal files ตามความเหมาะสม:

```text
filesDir/
  playlists/
    active_playlist.json
    pending_playlist.json

  media/
    active/
      image_101.jpg
      video_102.mp4

    tmp/
      download_xxx.tmp
```

แนวทาง:

- ใช้ชื่อไฟล์จาก media id และนามสกุลจาก URL
- ตรวจสอบไฟล์ว่ามีอยู่และขนาดมากกว่า 0
- ไม่ต้องขอ permission storage เพิ่ม หากใช้ app-specific storage
- API 23 รองรับแนวทางนี้

---

## 12. Network และ Error Handling

แนวทาง:

1. OkHttp ตั้ง timeout ชัดเจน เช่น connect/read/write 15-30 วินาที
2. API ทุกตัวต้อง handle exception
3. Sync error ต้องไม่ทำให้ Activity crash
4. Heartbeat error ต้องไม่รบกวน playback
5. Response JSON ผิดรูปแบบให้ log และรอรอบ sync ถัดไป
6. HTTPS certificate ใช้ certificate ปกติจาก hosting เท่านั้น ไม่ปิด SSL verification

---

## 13. Heartbeat Flow

```text
App Start
  |
  |-- Start heartbeat loop
        |
        |-- POST /api/device/heartbeat
        |-- แนบ device_id
        |-- แนบ app_version
        |-- แนบ current_playlist_version
        |-- แนบ last_sync_time
        |-- ถ้าส่งไม่สำเร็จ ให้รอรอบถัดไป
```

ช่วงเวลาส่ง:

- ค่าเริ่มต้น 60 วินาที
- หาก API config ส่งค่า interval มา ให้ใช้ค่าจาก server

---

## 14. หน้าจอและ UI

MVP จะมี UI น้อยที่สุด เพราะเป็น Digital Signage Player

### 14.1 Player Screen

- Full screen
- พื้นหลังสีดำ
- แสดง ImageView หรือ PlayerView เต็มจอ
- ไม่มีปุ่มควบคุมวิดีโอ
- ไม่มี toolbar

### 14.2 Status Fallback

ใช้เมื่อยังไม่มี content หรือเกิดปัญหาที่ทำให้ไม่มี item เล่นได้

ตัวอย่างสถานะ:

- Waiting for playlist
- No downloaded content
- Device ID not configured
- Cannot play current playlist

หน้าจอนี้ต้องไม่ใช่ black screen เฉย ๆ เพื่อให้รู้ว่า App ยังทำงานอยู่

### 14.3 Hidden Settings

สำหรับ MVP อาจทำในเฟสหลังของ Android หากต้องการตั้งค่าบนเครื่อง

ข้อมูลที่ควรตั้งได้:

- Device ID
- API Base URL
- ปุ่ม Sync Now
- แสดง App Version
- แสดง Current Playlist Version
- แสดง Last Sync Time

---

## 15. Manifest และ Permission ที่ต้องเพิ่ม

คาดว่าจะเพิ่ม:

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED" />
```

Activity:

- `MainActivity` เป็น launcher activity
- ตั้งค่า theme ให้ fullscreen/no actionbar
- อาจกำหนด orientation เป็น landscape หากจอ signage ใช้แนวนอนเป็นหลัก

Receiver:

- `BootReceiver` สำหรับเปิด app หลังเครื่อง boot

จุดที่ต้องยืนยัน:

- หน้าจอจะใช้แนวนอนเท่านั้น หรือรองรับทั้งแนวนอนและแนวตั้ง

---

## 16. เฟสการพัฒนา Android สำหรับอนุมัติ

การพัฒนาฝั่ง Android จะแบ่งเป็นเฟสย่อยเพื่อให้ตรวจสอบและอนุมัติทีละช่วง ก่อนเดินหน้าสู่เฟสถัดไป

แนวทางอนุมัติ:

- อนุมัติทีละเฟสก่อนเริ่มลงมือทำเฟสนั้น
- เมื่อจบแต่ละเฟส จะสรุปไฟล์ที่แก้ สิ่งที่ทำสำเร็จ และผลทดสอบ
- หากเฟสใดต้องรอ API จริงจากฝั่ง Web Admin จะใช้ mock JSON ชั่วคราวเพื่อทดสอบ Android ก่อน
- งานฝั่ง Web Admin/API จะยังไม่เริ่มจนกว่าจะมีการอนุมัติแผนฝั่ง Web Admin แยกต่างหาก

---

### Phase A: Project Foundation และ Fullscreen Shell

เป้าหมาย:

- เตรียมโปรเจกต์ Android ให้พร้อมเป็น Digital Signage Player แบบเต็มจอ

งานที่จะทำ:

1. เพิ่ม dependency พื้นฐานที่จำเป็น เช่น Media3 ExoPlayer, Retrofit, OkHttp, Coroutines และ Lifecycle
2. ปรับ `AndroidManifest.xml` เพื่อเพิ่ม permission พื้นฐาน
3. ปรับ theme ให้ไม่มี ActionBar และเหมาะกับ full screen
4. ปรับ `activity_main.xml` จากหน้า Hello World เป็น player shell เต็มจอ
5. ปรับ `MainActivity.kt` ให้ซ่อน Status Bar และ Navigation Bar
6. เปิด `FLAG_KEEP_SCREEN_ON` เพื่อป้องกันจอดับระหว่างใช้งาน
7. สร้าง package และไฟล์ skeleton ตามโครงสร้างที่วางไว้

ผลลัพธ์เมื่อจบเฟส:

- App เปิดขึ้นมาเป็นหน้าจอเต็ม
- ไม่มี toolbar/actionbar
- ซ่อน system bars ได้
- มีโครงสร้างไฟล์พร้อมสำหรับ player, sync, storage และ network

เกณฑ์อนุมัติเฟส:

- Build ผ่าน
- เปิด app แล้วเห็นหน้าจอ player shell เต็มจอ
- ไม่มีจอ Hello World เดิม

ยังไม่ทำในเฟสนี้:

- ยังไม่เล่นรูป/วิดีโอจริง
- ยังไม่เรียก API
- ยังไม่ดาวน์โหลด media

---

### Phase B: Local Playlist Playback

เป้าหมาย:

- ทำให้ app เล่น playlist จากไฟล์ local/mock ได้ก่อน โดยยังไม่ต้องพึ่ง server

งานที่จะทำ:

1. สร้าง data model สำหรับ playlist และ playlist item
2. สร้าง `LocalPlaylistStore`
3. สร้าง mock playlist สำหรับทดสอบในเครื่อง
4. สร้าง `PlayerController`
5. รองรับการแสดงรูปภาพจาก local file หรือ app asset
6. รองรับ duration สำหรับรูปภาพ
7. รองรับวิดีโอ MP4 ด้วย ExoPlayer
8. เล่น item ถัดไปอัตโนมัติเมื่อรูปครบเวลา หรือวิดีโอจบ
9. วนกลับ item แรกเมื่อจบ playlist
10. แสดง fallback status หากไม่มี item ที่เล่นได้

ผลลัพธ์เมื่อจบเฟส:

- App เล่น playlist แบบ image/video mixed ได้จากข้อมูล local/mock
- Playlist เล่นวนซ้ำอัตโนมัติ
- หากไม่มี content จะมี status fallback ไม่ใช่จอดำ

เกณฑ์อนุมัติเฟส:

- ทดสอบ playlist รูปอย่างเดียวได้
- ทดสอบ playlist วิดีโออย่างเดียวได้
- ทดสอบ playlist รูปและวิดีโอผสมกันได้
- เมื่อจบ playlist แล้ว loop กลับไปเริ่มใหม่

ยังไม่ทำในเฟสนี้:

- ยังไม่เชื่อม PHP API
- ยังไม่ดาวน์โหลด media จาก server
- ยังไม่ส่ง heartbeat

---

### Phase C: Device Config และ Hidden Settings

เป้าหมาย:

- ให้เครื่องมี Device ID และตั้งค่า API Base URL ได้ เพื่อเตรียมเชื่อมกับ Web Admin/API

งานที่จะทำ:

1. สร้าง `DeviceConfigStore`
2. เก็บ Device ID ใน SharedPreferences
3. เก็บ API Base URL ใน SharedPreferences หรือ BuildConfig ตามที่อนุมัติ
4. สร้างค่า Device ID เริ่มต้นจากเครื่อง หากยังไม่ถูกตั้งค่า
5. เพิ่ม hidden settings screen แบบง่าย
6. แสดงข้อมูล App Version, Device ID, API Base URL, Playlist Version และ Last Sync Time
7. เพิ่มปุ่มบันทึกค่าและปุ่ม Sync Now สำหรับเฟสถัดไป

ผลลัพธ์เมื่อจบเฟส:

- App มี Device ID ประจำเครื่อง
- สามารถแก้ Device ID และ API Base URL ได้จาก hidden settings
- ข้อมูลสำคัญของเครื่องถูกเก็บข้ามการเปิดปิด app

เกณฑ์อนุมัติเฟส:

- ตั้ง Device ID เป็นรูปแบบเช่น `SCREEN001` ได้
- ปิดแล้วเปิด app ใหม่ ค่ายังอยู่
- หน้าตั้งค่าไม่รบกวนการแสดง signage ตามปกติ

ยังไม่ทำในเฟสนี้:

- ยังไม่เรียก API จริง
- ยังไม่ sync playlist จริง

---

### Phase D: API Client และ Playlist Version Sync

เป้าหมาย:

- ให้ app ติดต่อ PHP API เพื่อเช็ค config และ playlist version ได้

งานที่จะทำ:

1. สร้าง `ApiClient` ด้วย Retrofit + OkHttp
2. สร้าง `DigitalSignageApi`
3. รองรับ `GET /api/device/config`
4. รองรับ `GET /api/playlist`
5. ส่ง Device ID ไปกับ request ตามรูปแบบ API ที่ตกลง
6. เปรียบเทียบ playlist version จาก server กับ local version
7. สร้าง `SyncManager`
8. ทำ sync loop ค่าเริ่มต้นทุก 60 วินาที
9. เพิ่ม manual Sync Now จาก hidden settings
10. จัดการ network error โดยไม่ทำให้ player หยุด

ผลลัพธ์เมื่อจบเฟส:

- App เรียก API เพื่อตรวจ version ได้
- App รู้ว่า server มี playlist ใหม่กว่า local หรือไม่
- ถ้า server ติดต่อไม่ได้ player ยังทำงานต่อ

เกณฑ์อนุมัติเฟส:

- เรียก config endpoint ได้
- เรียก playlist endpoint ได้
- เปรียบเทียบ version ได้ถูกต้อง
- ปิด internet แล้ว app ไม่ crash และไม่กลายเป็น black screen

ยังไม่ทำในเฟสนี้:

- ยังไม่ดาวน์โหลด media ทั้งชุดแบบ production-ready
- ยังไม่ switch playlist ใหม่แบบสมบูรณ์
- ยังไม่ส่ง heartbeat

---

### Phase E: Media Download, Cache และ Offline Playback

เป้าหมาย:

- ดาวน์โหลด media จาก server มาเก็บในเครื่อง และเล่นจาก local storage เป็นหลัก

งานที่จะทำ:

1. สร้าง `MediaCacheManager`
2. ดาวน์โหลดไฟล์ JPG, PNG และ MP4 จาก HTTPS
3. เก็บไฟล์ media ใน app-specific storage
4. ดาวน์โหลดลง temporary file ก่อน
5. ตรวจสอบว่าไฟล์ดาวน์โหลดสำเร็จและขนาดมากกว่า 0
6. บันทึก `pending_playlist.json` ก่อนเปลี่ยนเป็น active
7. เปลี่ยนไปใช้ playlist ใหม่เฉพาะเมื่อ media ครบทุกไฟล์
8. เก็บ `active_playlist.json` และ current playlist version
9. ให้ player โหลดจาก local file เท่านั้น
10. หากดาวน์โหลดล้มเหลว ให้คง playlist เดิมไว้

ผลลัพธ์เมื่อจบเฟส:

- App ดาวน์โหลด content ใหม่ได้เมื่อ playlist version เปลี่ยน
- App เล่น media จาก local storage
- App เล่น content เดิมต่อได้เมื่อ internet หลุด
- ไม่เกิด black screen จาก server หรือ download error

เกณฑ์อนุมัติเฟส:

- มี playlist เดิมแล้วปิด internet ยังเล่นต่อได้
- server มี version ใหม่แล้ว app ดาวน์โหลดและเปลี่ยน playlist ได้
- หากดาวน์โหลดบางไฟล์ล้มเหลว app ยังใช้ playlist เดิม
- media ที่เล่นอยู่มาจาก local storage ไม่ใช่ stream ตรงจาก URL

ยังไม่ทำในเฟสนี้:

- ยังไม่ทำ heartbeat
- ยังไม่ทำ auto start หลังเปิดเครื่อง

---

### Phase F: Heartbeat และ Device Online Status

เป้าหมาย:

- ส่งสถานะเครื่องให้ Web Admin รู้ว่า device online และใช้งาน playlist version ใดอยู่

งานที่จะทำ:

1. สร้าง `HeartbeatManager`
2. รองรับ `POST /api/device/heartbeat`
3. ส่ง Device ID
4. ส่ง App Version
5. ส่ง Current Playlist Version
6. ส่ง Last Sync Time
7. ส่งเป็นรอบ ค่าเริ่มต้นทุก 60 วินาที
8. หาก server กำหนด heartbeat interval มา ให้ใช้ค่าจาก config
9. จัดการ heartbeat error โดยไม่กระทบ playback

ผลลัพธ์เมื่อจบเฟส:

- Web Admin สามารถนำ heartbeat ไปคำนวณ Online/Offline ได้
- App ยังคงเล่น content แม้ heartbeat ส่งไม่สำเร็จ

เกณฑ์อนุมัติเฟส:

- ส่ง heartbeat payload ครบตาม requirement
- ส่งซ้ำตาม interval ได้
- network error ไม่ทำให้ app crash หรือหยุดเล่น

ยังไม่ทำในเฟสนี้:

- ยังไม่ทำ auto start หลังเปิดเครื่อง
- ยังไม่ทดสอบบน Android Box จริงครบทุกกรณี

---

### Phase G: Boot Auto Start และ Stabilization

เป้าหมาย:

- ทำให้ app พร้อมใช้งานบน Android Box จริงในรูปแบบ signage player

งานที่จะทำ:

1. เพิ่ม permission `RECEIVE_BOOT_COMPLETED`
2. เพิ่ม `BootReceiver`
3. เปิด `MainActivity` หลังเครื่อง boot
4. ตรวจ fullscreen ซ้ำหลัง resume
5. ทดสอบการเปิด app หลัง reboot
6. ทดสอบ long-running playback
7. ทดสอบกรณี API ล่ม
8. ทดสอบกรณี internet หลุด
9. ทดสอบ playlist version ใหม่
10. ปรับ error/fallback status ตามผลทดสอบ

ผลลัพธ์เมื่อจบเฟส:

- Android Box เปิดเครื่องแล้ว app เริ่มทำงานเอง
- App เล่น content แบบ full screen ต่อเนื่อง
- App ทนต่อปัญหา network และ server ตาม requirement MVP

เกณฑ์อนุมัติเฟส:

- Reboot แล้ว app เปิดอัตโนมัติ หรือมีข้อสรุปชัดเจนหาก firmware ของเครื่องบล็อก
- เล่น content ต่อเนื่องได้
- offline playback ผ่าน
- heartbeat และ sync ไม่รบกวน player

ยังไม่ทำในเฟสนี้:

- ยังไม่ทำ MQTT
- ยังไม่ทำ WebSocket
- ยังไม่ทำ OTA update
- ยังไม่ทำ remote screenshot
- ยังไม่ทำ remote Android control

---

## 16.1 ตารางสรุปเฟสเพื่ออนุมัติ

| เฟส | ชื่อเฟส | สิ่งที่จะอนุมัติ | ผลลัพธ์หลัก |
|---|---|---|---|
| Phase A | Project Foundation และ Fullscreen Shell | โครงสร้าง app และหน้าจอเต็มจอ | App พร้อมเป็น player shell |
| Phase B | Local Playlist Playback | การเล่นรูป/วิดีโอจาก local/mock | Playlist เล่นวนได้ |
| Phase C | Device Config และ Hidden Settings | วิธีตั้ง Device ID และ API URL | เครื่องมี identity พร้อมผูก Web Admin |
| Phase D | API Client และ Version Sync | รูปแบบเรียก API และเช็ค version | App รู้ว่ามี playlist ใหม่ |
| Phase E | Media Download และ Offline Cache | วิธี cache media และ switch playlist | เล่น offline ได้จริง |
| Phase F | Heartbeat | payload และรอบส่งสถานะ | Web Admin เห็น device online/offline |
| Phase G | Boot Auto Start และ Stabilization | พฤติกรรมตอน boot และความเสถียร | พร้อมทดสอบกับ Android Box จริง |

---

## 17. รายการทดสอบสำคัญก่อนส่งมอบ Android

| กรณีทดสอบ | ผลที่คาดหวัง |
|---|---|
| เปิด app ครั้งแรกไม่มี playlist | แสดง status fallback ไม่ใช่จอดำ |
| มี local playlist | เล่น content ทันทีโดยไม่รอ server |
| Internet ขาด | เล่น content เดิมต่อ |
| Server ส่ง version ใหม่ | ดาวน์โหลด content ใหม่และเปลี่ยน playlist |
| ดาวน์โหลด media ล้มเหลว | ไม่เปลี่ยน playlist และเล่นของเดิมต่อ |
| วิดีโอเล่นจบ | ไป item ถัดไป |
| รูปภาพครบ duration | ไป item ถัดไป |
| Playlist จบ | วนกลับ item แรก |
| App resume | กลับสู่ fullscreen |
| Reboot เครื่อง | App เปิดอัตโนมัติ |
| Heartbeat ส่งสำเร็จ | Server เห็น last seen ล่าสุด |

---

## 18. ข้อจำกัดและความเสี่ยง

1. Auto start หลัง boot อาจขึ้นกับ firmware ของ Android Box
2. การซ่อน Navigation Bar อาจขึ้นกับ Android Box และ launcher
3. MP4 บางไฟล์อาจใช้ codec ที่เครื่องไม่รองรับ ต้องกำหนดมาตรฐานไฟล์จากฝั่ง Web Admin
4. Hosting PHP 7.4.26 ไม่มีผลโดยตรงกับ Android แต่ API ต้องส่ง JSON แบบเสถียร
5. ถ้าไฟล์วิดีโอใหญ่มาก ต้องคุม timeout และพื้นที่เก็บข้อมูล
6. ถ้า API ยังไม่พร้อม ต้องสร้าง mock JSON เพื่อทดสอบ Android ก่อน

---

## 19. สิ่งที่ต้องยืนยันก่อนเริ่มเขียนโค้ด

1. API Base URL จริง เช่น `https://yourdomain.com`
2. Device ID ต้องตั้งเองเป็น `SCREEN001` หรือให้ app สร้างอัตโนมัติ
3. จอ signage ใช้แนวนอนเท่านั้นหรือไม่
4. รูปภาพควรแสดงแบบ `centerCrop` เต็มจอ หรือ `fitCenter` เห็นครบทั้งภาพ
5. Sync interval และ heartbeat interval ใช้ 60 วินาทีเป็นค่าเริ่มต้นได้หรือไม่
6. ต้องการ hidden settings screen ใน MVP แรกหรือให้ hardcode ค่าก่อน
7. API response สุดท้ายจะใช้ field name ตามตัวอย่างในเอกสารนี้ได้หรือไม่

---

## 20. ข้อเสนอสำหรับ MVP รอบแรก

เพื่อให้ Android เดินหน้าได้เร็วและเชื่อมกับ Web Admin ภายหลังได้ง่าย เสนอให้ทำ MVP Android ตามนี้ก่อน:

1. Fullscreen player
2. Local playlist playback
3. API client
4. Playlist sync by version
5. Media download/cache
6. Offline playback
7. Heartbeat
8. Boot auto start
9. Hidden settings แบบง่ายสำหรับ Device ID และ API Base URL

หลังจากอนุมัติแผนนี้แล้ว จึงค่อยเริ่มเขียนโค้ดฝั่ง Android หรือไปจัดทำแผนฝั่ง Web Admin + API + MySQL ตามลำดับที่กำหนด
