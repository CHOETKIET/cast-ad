# แผนพัฒนาระบบฝั่ง Web Admin + PHP API + MySQL

โปรเจกต์: ichi-play  
ส่วนระบบ: Web Admin + Backend API + MySQL  
Hosting เป้าหมาย: DirectAdmin Hosting  
PHP Version เป้าหมาย: PHP 7.4.26  
Database: MySQL  
API Format: JSON REST API ผ่าน HTTPS

เอกสารนี้เป็นแผนพัฒนาเฉพาะฝั่ง Web Admin, PHP API และ MySQL ก่อนเริ่มลงมือเขียนโค้ดจริง เพื่อให้ตรวจสอบและอนุมัติแนวทางก่อน

---

## 1. ขอบเขต Webapp MVP

Webapp MVP ต้องรองรับงานหลักของผู้ดูแลระบบและ Android Player ดังนี้

1. Admin Login
2. Dashboard แสดงสถานะ Device Online, Offline และ Last Seen
3. เพิ่ม แก้ไข ลบ Device
4. เก็บข้อมูล Device ID, Device Name, Status, Last Seen, App Version และ Current Playlist
5. Upload รูปภาพ JPG และ PNG
6. Upload วิดีโอ MP4
7. จัดการ Media Library
8. สร้าง แก้ไข ลบ Playlist
9. เพิ่มรูปและวิดีโอลง Playlist
10. จัดลำดับ Content ใน Playlist
11. กำหนด Duration สำหรับรูปภาพ
12. Assign Playlist ให้ Device
13. เพิ่ม Playlist Version อัตโนมัติเมื่อ Playlist เปลี่ยน
14. เปิด API ให้ Android ตรวจ config, ดึง playlist และส่ง heartbeat
15. ให้ Android ดาวน์โหลด media ผ่าน HTTPS

สิ่งที่ยังไม่อยู่ใน MVP:

- MQTT
- WebSocket
- GPS
- Remote Screenshot
- Remote Android Control
- OTA APK Update
- Advanced Analytics
- Advanced Scheduling
- Multi-role Permission

---

## 2. แนวทางเทคโนโลยี

เนื่องจาก hosting เป้าหมายเป็น DirectAdmin และ PHP 7.4.26 จึงเสนอแนวทางที่ deploy ง่ายและไม่พึ่งพา service ภายนอกมากเกินไป

| ส่วน | แนวทาง |
|---|---|
| Backend | PHP 7.4 แบบ MVC-lite |
| Database | MySQL ผ่าน PDO |
| Admin UI | PHP templates + HTML + CSS + JavaScript |
| API | JSON REST API |
| Auth | PHP Session สำหรับ Admin |
| Password | `password_hash()` และ `password_verify()` |
| Upload | PHP file upload พร้อมตรวจ MIME/type/size |
| Security | Prepared statements, CSRF token, session hardening |
| Deployment | Upload ไปยัง DirectAdmin public_html หรือ subfolder |

เหตุผล:

- ใช้งานได้กับ PHP 7.4.26
- ไม่จำเป็นต้องมี Node.js บน server
- ลดความเสี่ยงจาก Composer/package ที่ hosting อาจจำกัด
- โครงสร้างยังแยกชั้นชัดเจนพอให้ดูแลต่อได้

หมายเหตุ:

- หาก DirectAdmin รองรับ Composer สามารถพิจารณาเพิ่ม dependency เล็ก ๆ ภายหลังได้
- MVP แรกจะใช้ PHP native + PDO เป็นหลัก

---

## 3. โครงสร้างไฟล์ที่เสนอ

เสนอให้สร้างโฟลเดอร์ webapp แยกจาก Android project เพื่อไม่ปะปนกับไฟล์ Android Studio

```text
webapp/
  public/
    index.php
    login.php
    logout.php
    dashboard.php
    devices.php
    device-form.php
    media.php
    media-upload.php
    playlists.php
    playlist-edit.php
    assign-playlist.php

    api/
      device/
        config.php
        heartbeat.php
      playlist.php

    assets/
      css/
        admin.css
      js/
        admin.js

    uploads/
      images/
      videos/

  app/
    config/
      config.php
      database.php

    core/
      auth.php
      csrf.php
      response.php
      upload.php
      validation.php

    models/
      User.php
      Device.php
      Media.php
      Playlist.php
      PlaylistItem.php
      DevicePlaylist.php

    services/
      DeviceStatusService.php
      PlaylistVersionService.php
      MediaUploadService.php
      ApiAuthService.php

    views/
      layout/
        header.php
        sidebar.php
        footer.php
      auth/
      dashboard/
      devices/
      media/
      playlists/

  database/
    schema.sql
    seed.sql

  storage/
    logs/

  README.md
```

หมายเหตุสำคัญ:

- `public/` เป็น document root ที่เหมาะสมที่สุด
- ถ้า DirectAdmin ตั้ง document root เป็น `public_html` และเปลี่ยน root ไม่ได้ อาจ deploy contents ของ `public/` ไว้ใน `public_html` แล้วเก็บ `app/` และ `database/` ไว้นอก public path ถ้า hosting อนุญาต
- ถ้า hosting ไม่อนุญาตให้นำ `app/` ออกนอก public path ต้องใช้ `.htaccess` ป้องกันการเข้าถึงไฟล์ภายใน

---

## 4. Database Schema ขั้นต่ำ

Requirement กำหนด table ขั้นต่ำดังนี้

- `users`
- `devices`
- `media`
- `playlists`
- `playlist_items`
- `device_playlist`

เสนอรายละเอียดเพิ่มเติมดังนี้

### 4.1 users

ใช้สำหรับ Admin Login

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(150) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 4.2 devices

ใช้เก็บ Android Box แต่ละเครื่อง

```sql
CREATE TABLE devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(100) NOT NULL UNIQUE,
  device_name VARCHAR(150) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'offline',
  last_seen DATETIME NULL,
  app_version VARCHAR(50) NULL,
  current_playlist_version INT NOT NULL DEFAULT 0,
  last_sync_time DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 4.3 media

ใช้เก็บไฟล์รูปและวิดีโอ

```sql
CREATE TABLE media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  media_type VARCHAR(20) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  public_url VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT NOT NULL DEFAULT 0,
  width INT NULL,
  height INT NULL,
  duration_seconds INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 4.4 playlists

ใช้เก็บ playlist และ version

```sql
CREATE TABLE playlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  version INT NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);
```

### 4.5 playlist_items

ใช้เก็บรายการ media ใน playlist

```sql
CREATE TABLE playlist_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT NOT NULL,
  media_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  duration_seconds INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_playlist_items_playlist_id (playlist_id),
  INDEX idx_playlist_items_media_id (media_id)
);
```

### 4.6 device_playlist

ใช้กำหนด playlist ให้ device

```sql
CREATE TABLE device_playlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id INT NOT NULL,
  playlist_id INT NOT NULL,
  assigned_at DATETIME NOT NULL,
  assigned_by INT NULL,
  UNIQUE KEY uniq_device_playlist_device (device_id),
  INDEX idx_device_playlist_playlist_id (playlist_id)
);
```

หมายเหตุ:

- MVP ใช้ soft delete ด้วย `is_active`
- Online/Offline คำนวณจาก `last_seen` เป็นหลัก เช่น online ถ้า heartbeat ภายใน 2 นาทีล่าสุด
- เมื่อแก้ไข playlist หรือ item ใน playlist ต้องเพิ่ม `playlists.version` อัตโนมัติ

---

## 5. API Contract สำหรับ Android

API ทั้งหมดตอบกลับเป็น JSON และใช้งานผ่าน HTTPS

### 5.1 GET /api/device/config

Android ใช้ตรวจสอบ device config และ playlist version ล่าสุด

Query ที่เสนอ:

```text
GET /api/device/config?device_id=SCREEN001
```

Response สำเร็จ:

```json
{
  "success": true,
  "device_id": "SCREEN001",
  "playlist_id": 1,
  "playlist_version": 3,
  "sync_interval_seconds": 60,
  "heartbeat_interval_seconds": 60,
  "server_time": "2026-08-11T10:30:00+07:00"
}
```

กรณียังไม่ assign playlist:

```json
{
  "success": true,
  "device_id": "SCREEN001",
  "playlist_id": null,
  "playlist_version": 0,
  "sync_interval_seconds": 60,
  "heartbeat_interval_seconds": 60,
  "server_time": "2026-08-11T10:30:00+07:00"
}
```

### 5.2 GET /api/playlist

Android ใช้ดึง playlist และรายการ media

Query ที่เสนอ:

```text
GET /api/playlist?device_id=SCREEN001
```

Response:

```json
{
  "success": true,
  "playlist_id": 1,
  "playlist_version": 3,
  "items": [
    {
      "id": 101,
      "media_id": 5,
      "type": "image",
      "url": "https://domain.com/uploads/images/image01.jpg",
      "duration_seconds": 10,
      "sort_order": 1,
      "file_size": 123456,
      "checksum": null
    },
    {
      "id": 102,
      "media_id": 8,
      "type": "video",
      "url": "https://domain.com/uploads/videos/video01.mp4",
      "duration_seconds": null,
      "sort_order": 2,
      "file_size": 9876543,
      "checksum": null
    }
  ]
}
```

หมายเหตุ:

- MVP ยังไม่บังคับ checksum
- หากเพิ่ม checksum ภายหลัง Android จะตรวจความครบถ้วนได้ดีขึ้น
- วิดีโอใช้ `duration_seconds` เป็น `null` เพราะ Android เล่นจนจบไฟล์

### 5.3 POST /api/device/heartbeat

Android ใช้ส่งสถานะเครื่อง

Request:

```json
{
  "device_id": "SCREEN001",
  "app_version": "1.0",
  "current_playlist_version": 3,
  "last_sync_time": "2026-08-11T10:30:00+07:00"
}
```

Response:

```json
{
  "success": true,
  "server_time": "2026-08-11T10:31:00+07:00"
}
```

---

## 6. Admin UI Pages

### 6.1 Login

ความสามารถ:

- กรอก username/password
- ตรวจด้วย `password_verify()`
- ใช้ PHP session
- redirect เข้า dashboard เมื่อ login สำเร็จ
- logout ได้

### 6.2 Dashboard

แสดง:

- จำนวน device ทั้งหมด
- จำนวน online
- จำนวน offline
- รายการ device ล่าสุด
- Last Seen
- Current Playlist
- App Version

เกณฑ์ online/offline:

- Online ถ้า `last_seen` อยู่ในช่วงเวลาที่กำหนด เช่น ภายใน 120 วินาที
- Offline ถ้าเกินเวลาที่กำหนด

### 6.3 Device Management

ความสามารถ:

- เพิ่ม Device
- แก้ไข Device
- ปิดใช้งาน Device
- แสดง Device ID
- แสดง Device Name
- แสดง Status
- แสดง Last Seen
- แสดง App Version
- แสดง Current Playlist
- ปุ่ม Assign Playlist

### 6.4 Media Library

ความสามารถ:

- Upload JPG
- Upload PNG
- Upload MP4
- แสดงรายการ media
- แสดงชนิดไฟล์
- แสดงขนาดไฟล์
- แสดง preview รูปภาพ
- แสดงชื่อไฟล์วิดีโอ
- ปิดใช้งานหรือลบ media

ข้อกำหนด upload:

- ตรวจนามสกุล
- ตรวจ MIME type
- จำกัดขนาดไฟล์ตาม hosting
- เปลี่ยนชื่อไฟล์ตอนเก็บจริงเพื่อกันชื่อซ้ำ
- ห้ามให้ upload PHP/script file

### 6.5 Playlist Management

ความสามารถ:

- สร้าง Playlist
- แก้ไขชื่อ Playlist
- เพิ่ม media ลง Playlist
- ลบ item ออกจาก Playlist
- จัดลำดับ item
- กำหนด duration สำหรับ image
- แสดง version ปัจจุบัน
- เพิ่ม version อัตโนมัติเมื่อแก้ไข item/order/duration

### 6.6 Assign Playlist to Device

ความสามารถ:

- เลือก Device
- เลือก Playlist
- บันทึกการ assign
- Android จะเห็น version ใหม่ผ่าน `GET /api/device/config`

---

## 7. Security Plan

สิ่งที่จะทำใน MVP:

1. ใช้ prepared statements ทุก query
2. ใช้ `password_hash()` สำหรับรหัสผ่าน admin
3. ใช้ `password_verify()` ตอน login
4. ใช้ PHP session และ regenerate session ID หลัง login
5. ใช้ CSRF token กับ form สำคัญ
6. ตรวจสิทธิ์ admin ก่อนเข้า dashboard และหน้าจัดการ
7. ตรวจ MIME/type/extension ของไฟล์ upload
8. จำกัดชนิดไฟล์เฉพาะ JPG, PNG, MP4
9. ตั้งชื่อไฟล์ใหม่ตอน upload
10. ปิด directory listing ผ่าน `.htaccess`
11. API ตอบ JSON เท่านั้น
12. ไม่แสดง PHP error ต่อผู้ใช้ใน production

API Auth สำหรับ MVP:

- ใช้ `device_id` เป็นตัวระบุ device
- API จะตรวจว่า device_id มีอยู่และ active
- หากต้องการความปลอดภัยเพิ่มในเฟสถัดไป สามารถเพิ่ม `device_token` ต่อ device ได้

ข้อเสนอ:

- เพิ่ม `device_token` ตั้งแต่ MVP ได้ หากต้องการกันคนเดา Device ID แล้วเรียก API
- Android จะส่ง `device_id` + `device_token` ใน request

จุดที่ต้องยืนยัน:

- MVP ต้องการ `device_token` เลยหรือไม่

---

## 8. Playlist Version Rule

ต้องเพิ่ม version เมื่อเกิดเหตุการณ์เหล่านี้:

1. เพิ่ม media เข้า playlist
2. ลบ media ออกจาก playlist
3. เปลี่ยนลำดับ media
4. เปลี่ยน duration ของรูปภาพ
5. เปลี่ยน media item ใด ๆ ใน playlist

ไม่จำเป็นต้องเพิ่ม version เมื่อ:

- แก้ชื่อ playlist อย่างเดียว
- แก้ description อย่างเดียว
- assign playlist เดิมให้ device เดิมซ้ำ

เมื่อ assign playlist ให้ device:

- ไม่จำเป็นต้องเพิ่ม playlist version
- Android จะเห็น playlist_id/version จาก config ของ device

---

## 9. Media Storage Plan

โครงสร้างไฟล์ media:

```text
public/uploads/
  images/
    20260811_abcdef123456.jpg
    20260811_bcdefa234567.png

  videos/
    20260811_cdefab345678.mp4
```

แนวทาง:

- เก็บไฟล์จริงใน `public/uploads/images` และ `public/uploads/videos`
- เก็บ path และ public URL ใน table `media`
- Android ดาวน์โหลดจาก `public_url`
- ชื่อไฟล์จริงไม่ใช้ original filename ตรง ๆ
- original filename เก็บใน database เพื่อให้ admin ดูย้อนหลัง

จุดที่ต้องยืนยัน:

- จำกัดขนาดไฟล์รูปและวิดีโอเท่าไร
- Hosting DirectAdmin มี `upload_max_filesize` และ `post_max_size` เท่าไร

---

## 10. Error Handling และ Logging

Admin UI:

- แสดงข้อความ error ที่เข้าใจง่าย
- ไม่แสดง SQL error หรือ stack trace
- form validation ทั้งฝั่ง server และฝั่ง browser เท่าที่จำเป็น

API:

- ใช้ HTTP status code ให้เหมาะสม
- response JSON มี `success`, `message`, และข้อมูลที่เกี่ยวข้อง
- หาก device ไม่พบ ให้ตอบ error ชัดเจน
- หาก playlist ไม่พบ ให้ตอบ playlist ว่างหรือ error ตามกรณี

Logging:

- เก็บ error log ฝั่ง PHP ใน hosting log หรือ `storage/logs`
- ไม่ log password
- ไม่ log session id

---

## 11. เฟสการพัฒนา Webapp สำหรับอนุมัติ

การพัฒนาฝั่ง Webapp จะแบ่งเป็นเฟสย่อยเพื่อให้ตรวจสอบและอนุมัติทีละช่วง ก่อนเริ่มลงมือทำจริง

แนวทางอนุมัติ:

- อนุมัติทีละเฟสก่อนเริ่มทำเฟสนั้น
- เมื่อจบแต่ละเฟส จะสรุปไฟล์ที่สร้าง/แก้ ผลลัพธ์ และวิธีทดสอบ
- API contract ต้องสอดคล้องกับแผน Android
- ถ้ารายละเอียด hosting ยังไม่ครบ จะใช้ค่า config placeholder ก่อน

---

### Phase A: Webapp Foundation และ Database Setup

เป้าหมาย:

- เตรียมโครงสร้าง webapp, config, database connection และ schema เริ่มต้น

งานที่จะทำ:

1. สร้างโฟลเดอร์ `webapp/`
2. สร้างโครงสร้าง `public/`, `app/`, `database/`, `storage/`
3. สร้าง config สำหรับ database และ base URL
4. สร้าง PDO database connection
5. สร้าง helper สำหรับ response, redirect, validation และ session
6. สร้าง `database/schema.sql`
7. สร้าง `database/seed.sql` สำหรับ admin user เริ่มต้น
8. สร้างหน้าเริ่มต้นหรือ redirect ไป login

ผลลัพธ์เมื่อจบเฟส:

- มีโครงสร้าง webapp พร้อมพัฒนา
- มี schema database ครบ table ขั้นต่ำ
- เชื่อมต่อ MySQL ได้ผ่าน config

เกณฑ์อนุมัติเฟส:

- import schema ได้
- เปิดหน้า webapp แล้วไม่ error
- database connection สำเร็จ
- มี admin seed สำหรับ login รอบแรก

ยังไม่ทำในเฟสนี้:

- ยังไม่มี dashboard จริง
- ยังไม่มี upload
- ยังไม่มี API สำหรับ Android

---

### Phase B: Admin Login และ Layout หลัก

เป้าหมาย:

- ทำระบบ login/logout และ layout พื้นฐานของ Admin

งานที่จะทำ:

1. สร้างหน้า login
2. ตรวจ username/password จาก table `users`
3. ใช้ `password_verify()`
4. ใช้ PHP session
5. regenerate session ID หลัง login
6. สร้าง logout
7. สร้าง middleware/helper ตรวจว่า login แล้ว
8. สร้าง layout หลัก เช่น header, sidebar, footer
9. เพิ่ม CSRF token สำหรับ form สำคัญ

ผลลัพธ์เมื่อจบเฟส:

- Admin login/logout ได้
- หน้าที่ต้อง login จะถูกป้องกัน
- มี layout admin พื้นฐานพร้อมใช้ในเฟสถัดไป

เกณฑ์อนุมัติเฟส:

- login ด้วย admin seed ได้
- logout แล้วกลับเข้า protected page ไม่ได้
- session ทำงานถูกต้อง
- layout แสดงเมนูหลักได้

ยังไม่ทำในเฟสนี้:

- ยังไม่จัดการ device
- ยังไม่จัดการ media
- ยังไม่จัดการ playlist

---

### Phase C: Device Management และ Dashboard

เป้าหมาย:

- ให้ admin จัดการ device และดูสถานะ online/offline ได้

งานที่จะทำ:

1. สร้าง model/service สำหรับ `devices`
2. สร้างหน้า dashboard
3. คำนวณ online/offline จาก `last_seen`
4. แสดงจำนวน device ทั้งหมด
5. แสดงจำนวน online/offline
6. สร้างหน้า device list
7. สร้างหน้า add/edit device
8. รองรับ soft delete หรือปิดใช้งาน device
9. แสดง Device ID, Device Name, Status, Last Seen, App Version และ Current Playlist

ผลลัพธ์เมื่อจบเฟส:

- Admin เพิ่ม แก้ไข ปิดใช้งาน device ได้
- Dashboard เห็นภาพรวม device ได้
- สถานะ online/offline มี logic รองรับ heartbeat ในเฟส API

เกณฑ์อนุมัติเฟส:

- เพิ่ม device เช่น `SCREEN001` ได้
- แก้ชื่อ device ได้
- ปิดใช้งาน device ได้
- dashboard แสดงจำนวน device และสถานะถูกต้องตามข้อมูลใน database

ยังไม่ทำในเฟสนี้:

- ยังไม่ upload media
- ยังไม่ assign playlist
- ยังไม่รับ heartbeat จริงจาก Android

---

### Phase D: Media Library และ Upload

เป้าหมาย:

- ให้ admin upload และจัดการไฟล์ JPG, PNG, MP4 ได้

งานที่จะทำ:

1. สร้าง model/service สำหรับ `media`
2. สร้างหน้า media list
3. สร้างหน้า upload media
4. ตรวจ extension เฉพาะ jpg, jpeg, png, mp4
5. ตรวจ MIME type
6. ตรวจ file size
7. ตั้งชื่อไฟล์ใหม่ก่อนจัดเก็บ
8. เก็บไฟล์ใน `public/uploads/images` หรือ `public/uploads/videos`
9. บันทึก metadata ลง table `media`
10. แสดง preview รูปภาพ
11. แสดงข้อมูลไฟล์วิดีโอ
12. รองรับปิดใช้งานหรือลบ media ตามที่อนุมัติ

ผลลัพธ์เมื่อจบเฟส:

- Admin upload รูปและวิดีโอได้
- Media ถูกเก็บในตำแหน่งที่ Android ดาวน์โหลดได้ผ่าน HTTPS
- Media Library พร้อมให้ playlist เลือกใช้งาน

เกณฑ์อนุมัติเฟส:

- upload JPG ได้
- upload PNG ได้
- upload MP4 ได้
- upload ไฟล์ชนิดอื่นแล้วถูกปฏิเสธ
- media list แสดงข้อมูลไฟล์และ public URL ได้

ยังไม่ทำในเฟสนี้:

- ยังไม่สร้าง playlist
- ยังไม่ assign playlist ให้ device

---

### Phase E: Playlist Management และ Versioning

เป้าหมาย:

- ให้ admin สร้าง playlist, เพิ่ม media, จัดลำดับ และกำหนด duration รูปภาพได้

งานที่จะทำ:

1. สร้าง model/service สำหรับ `playlists`
2. สร้าง model/service สำหรับ `playlist_items`
3. สร้างหน้า playlist list
4. สร้างหน้า create/edit playlist
5. เพิ่ม media ลง playlist
6. ลบ media ออกจาก playlist
7. จัดลำดับ item
8. กำหนด duration สำหรับ image
9. แสดง version ปัจจุบัน
10. เพิ่ม playlist version อัตโนมัติเมื่อ item/order/duration เปลี่ยน
11. ป้องกันการใส่ duration ผิดรูปแบบ

ผลลัพธ์เมื่อจบเฟส:

- Admin สร้าง playlist ได้
- Playlist มี media หลายชนิดรวมกันได้
- Playlist version เพิ่มตาม rule ที่กำหนด

เกณฑ์อนุมัติเฟส:

- สร้าง playlist ได้
- เพิ่มรูปและวิดีโอลง playlist ได้
- เปลี่ยนลำดับแล้ว version เพิ่ม
- แก้ duration รูปแล้ว version เพิ่ม
- ลบ item แล้ว version เพิ่ม

ยังไม่ทำในเฟสนี้:

- ยังไม่ assign playlist ให้ device
- Android ยังไม่ดึง playlist ผ่าน API จริง

---

### Phase F: Assign Playlist to Device

เป้าหมาย:

- ให้ admin เลือก playlist ให้ Android Box แต่ละเครื่องได้

งานที่จะทำ:

1. สร้าง model/service สำหรับ `device_playlist`
2. เพิ่มปุ่ม assign playlist ในหน้า device
3. สร้างหน้า assign playlist
4. บันทึก playlist ที่เลือกให้ device
5. แสดง current playlist ใน device list และ dashboard
6. ตรวจว่า device และ playlist active ก่อน assign
7. รองรับการเปลี่ยน playlist ของ device

ผลลัพธ์เมื่อจบเฟส:

- Device แต่ละเครื่องมี playlist ที่ถูก assign
- Dashboard และ device list แสดง current playlist
- ข้อมูลพร้อมให้ API ส่งต่อไป Android

เกณฑ์อนุมัติเฟส:

- assign playlist ให้ `SCREEN001` ได้
- เปลี่ยน playlist ของ device ได้
- current playlist แสดงถูกต้อง
- device ที่ inactive ไม่ควรนำไปใช้งานต่อ

ยังไม่ทำในเฟสนี้:

- ยังไม่เปิด API ให้ Android ใช้งาน
- ยังไม่รับ heartbeat จริง

---

### Phase G: Android JSON API

เป้าหมาย:

- เปิด API ขั้นต่ำให้ Android ใช้ sync playlist และส่ง heartbeat

งานที่จะทำ:

1. สร้าง `GET /api/device/config`
2. สร้าง `GET /api/playlist`
3. สร้าง `POST /api/device/heartbeat`
4. ตรวจ device_id ในทุก endpoint
5. ตรวจ device active
6. ส่ง playlist_id และ playlist_version ให้ Android
7. ส่ง playlist items พร้อม media URL, type, duration และ sort_order
8. รับ heartbeat แล้ว update `last_seen`, `app_version`, `current_playlist_version`, `last_sync_time`
9. response เป็น JSON ทุกกรณี
10. จัดการ error เช่น device not found, playlist not assigned

ผลลัพธ์เมื่อจบเฟส:

- Android สามารถตรวจ config ได้
- Android สามารถดึง playlist ได้
- Android สามารถส่ง heartbeat ได้
- Web Admin เห็น Last Seen และ App Version จาก Android

เกณฑ์อนุมัติเฟส:

- `GET /api/device/config?device_id=SCREEN001` ตอบข้อมูลถูกต้อง
- `GET /api/playlist?device_id=SCREEN001` ตอบ playlist items ถูกต้อง
- `POST /api/device/heartbeat` update device status ได้
- API error ตอบ JSON ไม่ใช่หน้า HTML error

ยังไม่ทำในเฟสนี้:

- ยังไม่ทำ device token หากไม่ได้อนุมัติ
- ยังไม่ทำ analytics ขั้นสูง

---

### Phase H: Integration Test และ Deployment Preparation

เป้าหมาย:

- เตรียม webapp ให้พร้อม deploy และทดสอบร่วมกับ Android

งานที่จะทำ:

1. ทดสอบ flow เต็มจาก admin upload ถึง Android sync
2. ทดสอบ upload media แล้ว public URL เปิดได้
3. ทดสอบ playlist version เพิ่มถูกต้อง
4. ทดสอบ assign playlist แล้ว API config เปลี่ยนตาม
5. ทดสอบ heartbeat แล้ว dashboard เปลี่ยนสถานะ
6. ตรวจ permission ของ upload folder
7. เตรียม config สำหรับ production
8. เตรียมขั้นตอน deploy ไป DirectAdmin
9. เตรียม checklist หลัง deploy
10. ปิด display error ใน production

ผลลัพธ์เมื่อจบเฟส:

- Webapp พร้อม deploy ขึ้น DirectAdmin
- API พร้อมให้ Android ใช้งานผ่าน HTTPS
- Flow MVP ครบตาม requirement

เกณฑ์อนุมัติเฟส:

- Admin login ได้
- Upload media ได้
- สร้าง playlist ได้
- Assign playlist ได้
- Android API ตอบถูกต้อง
- Heartbeat update dashboard ได้
- มีเอกสาร deploy/checklist

ยังไม่ทำในเฟสนี้:

- ยังไม่ทำฟีเจอร์นอก MVP เช่น OTA, screenshot, websocket, advanced scheduling

---

## 11.1 ตารางสรุปเฟสเพื่ออนุมัติ

| เฟส | ชื่อเฟส | สิ่งที่จะอนุมัติ | ผลลัพธ์หลัก |
|---|---|---|---|
| Phase A | Foundation และ Database Setup | โครงสร้าง webapp และ schema | ระบบพร้อมเชื่อม DB |
| Phase B | Admin Login และ Layout | ระบบ login/session | Admin เข้าใช้งานได้ |
| Phase C | Device Management และ Dashboard | จัดการ device และสถานะ | เห็น device online/offline |
| Phase D | Media Library และ Upload | upload รูป/วิดีโอ | มี media ให้ playlist ใช้ |
| Phase E | Playlist Management และ Versioning | สร้าง playlist และเพิ่ม version | Playlist พร้อม sync |
| Phase F | Assign Playlist to Device | ผูก playlist กับ device | Device มี playlist ปัจจุบัน |
| Phase G | Android JSON API | API สำหรับ Android | Android sync/heartbeat ได้ |
| Phase H | Integration และ Deployment | ทดสอบรวมและเตรียม deploy | MVP พร้อมขึ้น hosting |

---

## 12. รายการทดสอบสำคัญก่อนส่งมอบ Webapp

| กรณีทดสอบ | ผลที่คาดหวัง |
|---|---|
| Admin login ถูกต้อง | เข้าสู่ dashboard ได้ |
| Admin login ผิด | เข้าไม่ได้และแสดง error |
| เพิ่ม device | device แสดงใน list |
| upload JPG/PNG | ไฟล์ถูกบันทึกและ preview ได้ |
| upload MP4 | ไฟล์ถูกบันทึกและมี public URL |
| upload ไฟล์ผิดชนิด | ระบบปฏิเสธ |
| สร้าง playlist | playlist แสดงใน list |
| เพิ่ม media ลง playlist | item แสดงตามลำดับ |
| แก้ duration รูป | playlist version เพิ่ม |
| จัดลำดับ item | playlist version เพิ่ม |
| assign playlist | device แสดง current playlist |
| GET config | ได้ playlist version ล่าสุด |
| GET playlist | ได้ item พร้อม media URL |
| POST heartbeat | last_seen และ app_version update |
| Android offline | dashboard เปลี่ยน offline เมื่อเลย threshold |

---

## 13. สิ่งที่ต้องยืนยันก่อนเริ่มเขียนโค้ด

1. Domain หรือ path ที่จะติดตั้งจริง เช่น `https://domain.com/signage`
2. MySQL database name, user และ password จะกำหนดภายหลังหรือให้ใช้ placeholder ก่อน
3. ต้องการใช้ `device_token` เพิ่มความปลอดภัยให้ Android API ตั้งแต่ MVP หรือไม่
4. ขนาดไฟล์ upload สูงสุดสำหรับรูปและวิดีโอ
5. Online threshold ต้องการกี่วินาที เช่น 120 วินาที หรือ 180 วินาที
6. ต้องการให้ลบ media จริงออกจาก server หรือใช้ soft delete เท่านั้น
7. ต้องการให้ playlist ที่ถูกใช้งานอยู่ลบได้หรือไม่
8. ต้องการ UI ภาษาไทยทั้งหมดหรือภาษาอังกฤษ
9. Document root บน DirectAdmin สามารถตั้งเป็น `public/` ได้หรือไม่

---

## 14. ข้อเสนอสำหรับ MVP รอบแรก

เสนอให้เริ่ม Webapp MVP ตามลำดับนี้:

1. Phase A: Foundation และ Database Setup
2. Phase B: Admin Login และ Layout
3. Phase C: Device Management และ Dashboard
4. Phase D: Media Library และ Upload
5. Phase E: Playlist Management และ Versioning
6. Phase F: Assign Playlist to Device
7. Phase G: Android JSON API
8. Phase H: Integration และ Deployment Preparation

ลำดับนี้จะทำให้ระบบค่อย ๆ สร้างจากฐานข้อมูลและ Admin UI ก่อน แล้วค่อยเปิด API ให้ Android ใช้งานจริง ซึ่งช่วยลดความเสี่ยงเรื่อง API ยังไม่มีข้อมูลให้ทดสอบ

หลังจากอนุมัติแผนนี้แล้ว จึงค่อยเริ่มพัฒนา Webapp หรือกลับไปเริ่ม Phase A ของ Android ตามลำดับที่ต้องการ
