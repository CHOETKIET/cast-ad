# Content Sync Fix - Phase 5 Summary

## สิ่งที่ตรวจแล้ว

- ตรวจ API contract ระหว่าง webapp และ Android
  - `force_resync_token`
  - `force_resync_requested_at`
  - `last_applied_resync_token`
  - `playlist_id`
  - `playlist_version`
  - `sync_interval_seconds`
  - `heartbeat_interval_seconds`
- ตรวจ PHP syntax ผ่านครบทุกไฟล์ `.php` ใน `web-ichicoolpay`
- Build Android debug APK สำเร็จ

## APK สำหรับทดสอบ

```text
app/build/outputs/apk/debug/app-debug.apk
```

ติดตั้งทับ:

```bash
adb -s 192.168.1.105:5555 install -r /Users/choetkiet.s/AndroidStudioProjects/ichiplay/app/build/outputs/apk/debug/app-debug.apk
```

## ไฟล์ Webapp ที่ต้อง Deploy ให้ครบ

### Phase 1

- `app/models/PlaylistItem.php`
- `app/models/Playlist.php`
- `public/media.php`
- `public/playlist-edit.php`
- `public/api/device/config.php`
- `public/api/playlist.php`

### Phase 3

- `app/models/Device.php`
- `public/devices.php`
- `public/api/device/config.php`
- `public/api/device/heartbeat.php`

หมายเหตุ:

- `public/api/device/config.php` ถูกแก้ทั้ง Phase 1 และ Phase 3 ให้อัปโหลดเวอร์ชันล่าสุดเท่านั้น

## SQL ที่ต้องรันบน Server จริง

รันครั้งเดียว:

```sql
ALTER TABLE devices
  ADD COLUMN force_resync_token VARCHAR(64) NULL AFTER last_sync_time,
  ADD COLUMN force_resync_requested_at DATETIME NULL AFTER force_resync_token,
  ADD COLUMN force_resync_completed_at DATETIME NULL AFTER force_resync_requested_at;
```

ถ้ารันซ้ำแล้วขึ้นว่า column มีอยู่แล้ว ไม่ต้องรันซ้ำ

## Checklist ทดสอบจริง

### 1. ตรวจ Android Online

1. ติดตั้ง APK ใหม่
2. เปิด Android app
3. ตั้ง `Device ID` ให้ตรงกับ webapp
4. ตั้ง `API Base URL` เป็น `https://ichiplay.ichicool.com`
5. กด `Sync Now`
6. ดูหน้า Devices บน webapp

ผลที่ควรได้:

- `Last Seen` เปลี่ยนเป็นเวลาล่าสุด
- `App Version` ขึ้น
- Device เป็น Online

### 2. ทดสอบ Assign Content

1. Upload รูป
2. สร้าง Playlist
3. เพิ่มรูปเข้า Playlist
4. Assign Playlist ให้ Device
5. กด `Sync Now` ที่ Android

ผลที่ควรได้:

- Android ดาวน์โหลดและแสดงรูปใหม่
- Last Sync Status แสดงว่า playlist version ถูก activate

### 3. ทดสอบ Deactivate Media

1. Deactivate media ที่กำลังแสดงอยู่
2. ตรวจว่า playlist version เพิ่ม
3. กด `Sync Now` ที่ Android หรือรอรอบ sync ถัดไป

ผลที่ควรได้:

- Android ไม่เล่น media ที่ inactive ต่อ
- ถ้า playlist ไม่มี item active เหลือ จะกลับไป fallback image

### 4. ทดสอบ Deactivate Playlist

1. Deactivate playlist ที่ assign อยู่
2. กด `Sync Now` ที่ Android หรือรอรอบ sync ถัดไป

ผลที่ควรได้:

- Android ล้าง content เก่า
- Android แสดง fallback image
- Reboot แล้วไม่กลับไปเล่นรูปเก่า

### 5. ทดสอบ Re-sync Now จาก Web

1. เปิดหน้า Devices
2. กด `Re-sync Now` ของ device ที่ต้องการ
3. รอ Android sync รอบถัดไป หรือกด `Sync Now` ที่ Android เพื่อเร่งทดสอบ
4. ดูค่า `Completed` ใน column Re-sync

ผลที่ควรได้:

- `Requested` มีเวลาใหม่
- Android ล้าง cache และ sync ใหม่
- หลัง heartbeat รอบถัดไป `Completed` มีเวลาใหม่

### 6. ตรวจ Log หากมีปัญหา

ดู log Android:

```bash
adb -s 192.168.1.105:5555 logcat -c
adb -s 192.168.1.105:5555 logcat -v time | grep -i "ichi_play\|OkHttp\|AndroidRuntime\|PlayerController\|SyncManager\|Heartbeat"
```

## สถานะ Phase 5

- Code verification ในเครื่องผ่าน
- เหลือการทดสอบ end-to-end บน server จริงและ Android Box จริงตาม checklist ด้านบน
