# Phase G Summary - Android API Integration

## สิ่งที่ทำแล้ว

- เพิ่ม API สำหรับ Android Player ตาม path ที่แอปเรียกใช้งานจริง
  - `GET /api/device/config?device_id=SCREEN001`
  - `GET /api/playlist?device_id=SCREEN001`
  - `POST /api/device/heartbeat`
- เพิ่มไฟล์ช่วยงาน API ที่ `app/core/api.php`
  - ตรวจ method ของ request
  - อ่าน JSON body
  - ส่ง JSON response
  - สร้าง server time
  - แปลง media URL ให้เป็น absolute URL ตาม `base_url`
- เพิ่มการค้นหา Device แบบ active เท่านั้น
- เพิ่มการบันทึก heartbeat ลงตาราง `devices`
  - `status = online`
  - `last_seen = NOW()`
  - `app_version`
  - `current_playlist_version`
  - `last_sync_time`
- เพิ่มการค้นหา Playlist ที่ assign ให้ Device และต้องเป็น active playlist
- เพิ่ม `.htaccess` rewrite สำหรับ Apache hosting ให้ URL แบบไม่มี `.php` ทำงานได้
- เพิ่ม `public/router.php` สำหรับทดสอบด้วย PHP built-in server ใน local

## พฤติกรรม API

- ถ้า Device ยังไม่ได้ assign playlist:
  - `config` จะตอบ `playlist_id = null`, `playlist_version = 0`
  - `playlist` จะตอบ playlist ว่าง `playlist_id = 0`, `playlist_version = 0`, `items = []`
- ถ้า Device ถูกปิดใช้งานหรือไม่มีในระบบ:
  - API จะตอบ `success = false` พร้อมข้อความแจ้งปัญหา
- `playlist` จะส่งเฉพาะ media ที่ active อยู่เท่านั้น
- field ของ JSON ถูกจัดให้ตรงกับ Android Kotlin model แล้ว

## ไฟล์ที่เพิ่ม/แก้

- `app/core/api.php`
- `app/models/Device.php`
- `app/models/DevicePlaylist.php`
- `public/.htaccess`
- `public/router.php`
- `public/api/device/config.php`
- `public/api/device/heartbeat.php`
- `public/api/playlist.php`

## การตรวจสอบ

- ตรวจ PHP syntax ผ่านทุกไฟล์ที่เพิ่มและแก้ใน Phase G
- ทดสอบ validation ของ heartbeat กรณีไม่มี `device_id` แล้ว API ตอบ JSON ได้ถูกต้อง
- การทดสอบ API ที่ต้องอ่านฐานข้อมูลจริงจาก CLI ถูกจำกัดโดยสิทธิ์ sandbox ต่อ MySQL socket
- การเปิด local server ใหม่พร้อม router ถูกระบบอนุมัติปฏิเสธ จึงยังไม่ได้ทดสอบ URL `/api/...` ผ่าน browser/curl หลังเพิ่ม router

## วิธีเปิด local server สำหรับทดสอบ API

ให้เปิดจากโฟลเดอร์ `web-cast-ad` ด้วยคำสั่งนี้ เพื่อให้ URL แบบ `/api/...` ทำงานกับ PHP built-in server:

```bash
php -S localhost:8080 -t public public/router.php
```

จากนั้นลองเปิด:

- `http://localhost:8080/api/device/config?device_id=SCREEN001`
- `http://localhost:8080/api/playlist?device_id=SCREEN001`
