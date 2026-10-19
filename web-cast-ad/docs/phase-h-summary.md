# Phase H Summary - Integration Test and Deployment Preparation

## สิ่งที่ทำแล้ว

- เตรียมเอกสาร deployment สำหรับนำ webapp ขึ้น server จริง
- เตรียม checklist สำหรับทดสอบ flow เต็มหลัง deploy
- ปรับ README ให้ใช้ local router ที่รองรับ URL API แบบเดียวกับ Android
- ปรับ database config ให้เหมาะกับ production hosting มากขึ้น
- ปรับ `.htaccess` ของโฟลเดอร์ภายในให้เข้ากับ Apache หลายรูปแบบมากขึ้น
- ปรับ `.htaccess` ของ uploads ให้ลดความเสี่ยงการรันไฟล์ script ที่ถูก upload

## ไฟล์ที่เพิ่ม

- `docs/deployment-checklist.md`
- `docs/server-test-checklist.md`
- `docs/phase-h-summary.md`

## ไฟล์ที่แก้

- `README.md`
- `app/config/database.php`
- `app/.htaccess`
- `database/.htaccess`
- `storage/.htaccess`
- `public/uploads/.htaccess`

## การเปลี่ยนแปลงสำคัญ

- เปลี่ยน default `socket` ใน `app/config/database.php` เป็นค่าว่าง
  - เหมาะกับ DirectAdmin/hosting ทั่วไปที่ใช้ database host/port
  - ถ้าทดสอบ local ผ่าน socket ให้ตั้ง `CAST_AD_DB_SOCKET=/tmp/mysql.sock` หรือแก้ config เฉพาะเครื่อง local
- README เปลี่ยนคำสั่ง local server เป็น:

```bash
php -S localhost:8080 -t public public/router.php
```

- เพิ่ม deployment checklist สำหรับ:
  - database import
  - production config
  - file placement
  - upload permission
  - HTTPS/API URL
  - post-deploy verification
- เพิ่ม server test checklist สำหรับ:
  - admin login
  - device
  - media upload
  - playlist
  - assign playlist
  - Android API
  - heartbeat
  - Android sync test

## การตรวจสอบ

- ตรวจ PHP syntax ผ่านครบทุกไฟล์ `.php` ใน `web-cast-ad`
- ยังไม่ได้ทดสอบ flow บน server จริง เพราะต้องใช้ domain, HTTPS, database และ permission ของ hosting จริง

## สถานะก่อนขึ้น Server จริง

พร้อมนำไป deploy เพื่อทดสอบบน server จริงตาม checklist แล้ว

ก่อน Android ทดสอบจริง ต้องตรวจให้แน่ใจว่า:

- `base_url` เป็น HTTPS domain จริง
- Device ID ในเว็บตรงกับ Android
- Assign playlist ให้ device แล้ว
- Media URL ใน API เป็น HTTPS และเปิดได้จริง
