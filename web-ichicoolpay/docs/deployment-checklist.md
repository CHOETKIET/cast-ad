# Deployment Checklist - iCHI-COOL Play Webapp

ใช้ checklist นี้ก่อนนำ webapp ขึ้น DirectAdmin หรือ hosting จริง

## 1. เตรียม Database

- สร้าง MySQL database บน hosting
- สร้าง MySQL user และผูกสิทธิ์กับ database
- Import `database/schema.sql`
- Import `database/seed.sql`
- จดค่าเหล่านี้จาก hosting:
  - database host
  - database name
  - database username
  - database password

## 2. ตั้งค่า Config

แก้ `app/config/config.php`

```php
'base_url' => 'https://your-domain.com',
'environment' => 'production',
```

แก้ `app/config/database.php`

```php
'host' => 'localhost',
'port' => '3306',
'socket' => '',
'database' => 'your_database_name',
'username' => 'your_database_user',
'password' => 'your_database_password',
```

หมายเหตุ:

- DirectAdmin ส่วนใหญ่ให้ใช้ `host` เป็น `localhost`
- ถ้า hosting ระบุ database host เป็นชื่ออื่น ให้ใช้ค่าที่ hosting ให้มา
- ใช้ `socket` เฉพาะกรณี hosting แจ้งชัดเจนว่าต้องใช้ socket

## 3. วางไฟล์บน Server

แนวทางที่แนะนำ:

- ให้ `public/` เป็น document root ของ domain หรือ subdomain
- เก็บ `app/`, `database/`, `docs/`, `storage/` ไว้นอก public path หาก hosting รองรับ

ถ้า DirectAdmin ไม่ให้เปลี่ยน document root:

- นำไฟล์ใน `public/` ไปไว้ใน `public_html`
- นำโฟลเดอร์ `app/`, `database/`, `docs/`, `storage/` ไปไว้ระดับเดียวกับ `public_html` ถ้าทำได้
- ถ้าจำเป็นต้องวางทั้งหมดไว้ใน `public_html` ให้คงไฟล์ `.htaccess` ใน `app/`, `database/`, `storage/` ไว้เสมอ

## 4. ตรวจ Permission

โฟลเดอร์ที่ต้องเขียนไฟล์ได้:

- `public/uploads/images`
- `public/uploads/videos`

ค่าที่มักใช้ได้บน hosting:

- folder: `755`
- file: `644`

ถ้า upload ไม่ได้ ให้ตรวจ permission และค่า `upload_max_filesize`, `post_max_size`

## 5. ตรวจ HTTPS และ URL

- เปิด `https://your-domain.com/login.php` ได้
- เปิด `https://your-domain.com/api/device/config?device_id=SCREEN001` แล้วได้ JSON
- URL ของ media ใน API ต้องขึ้นต้นด้วย `https://`
- ใน Android Setting ให้ใส่ API Base URL เป็น root domain เช่น `https://your-domain.com`

## 6. หลัง Deploy

- Login ด้วย admin seed
- เปลี่ยนรหัสผ่าน admin ทันที
- เพิ่มหรือแก้ Device ID ให้ตรงกับ Android
- Upload media ตัวอย่าง
- สร้าง playlist
- Assign playlist ให้ device
- ทดสอบ API ตาม `docs/server-test-checklist.md`
