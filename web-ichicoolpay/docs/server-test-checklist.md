# Server Test Checklist - iCHI-COOL Play Webapp

ใช้ checklist นี้หลัง deploy ขึ้น server จริง

## 1. Admin Login

- เปิด `https://your-domain.com/login.php`
- Login ด้วย admin เริ่มต้น
- เข้า Dashboard ได้
- Logout แล้วกลับเข้า Dashboard โดยตรงไม่ได้

## 2. Device

- เปิดหน้า Devices
- ตรวจว่ามี Device ID ที่ต้องใช้ เช่น `SCREEN001`
- ถ้า Android ใช้ Device ID อื่น ให้เพิ่มหรือแก้ให้ตรงกัน
- Device ต้องเป็น active

## 3. Media Upload

- Upload รูป JPG หรือ PNG
- Upload วิดีโอ MP4
- ตรวจว่า preview รูปเปิดได้
- เปิด media URL โดยตรงแล้วโหลดได้ผ่าน HTTPS
- ลอง upload ไฟล์ชนิดอื่น แล้วระบบต้องปฏิเสธ

## 4. Playlist

- สร้าง playlist ใหม่
- เพิ่ม media เข้า playlist
- กำหนด duration สำหรับรูปภาพ
- เปลี่ยนลำดับ item แล้วตรวจว่า version เพิ่ม
- ลบ item แล้วตรวจว่า version เพิ่ม

## 5. Assign Playlist

- เปิดหน้า Devices
- กด Assign ของ device ที่จะทดสอบ
- เลือก playlist
- กลับมาหน้า Devices แล้ว Current Playlist ต้องแสดงถูกต้อง

## 6. API Test

แทน `your-domain.com` และ `SCREEN001` ด้วยค่าจริง

```text
https://your-domain.com/api/device/config?device_id=SCREEN001
```

ผลที่ควรเห็น:

- response เป็น JSON
- `success` เป็น `true`
- `device_id` ตรงกับที่ส่งไป
- `playlist_version` มากกว่า `0` ถ้า assign playlist แล้ว

```text
https://your-domain.com/api/playlist?device_id=SCREEN001
```

ผลที่ควรเห็น:

- response เป็น JSON
- มี `playlist_id`
- มี `playlist_version`
- มี `items`
- media แต่ละตัวมี `type`, `url`, `duration_seconds`, `sort_order`
- `url` ต้องเป็น HTTPS

ทดสอบ heartbeat ด้วยเครื่องมือ API หรือ Android:

```json
{
  "device_id": "SCREEN001",
  "app_version": "1.0",
  "current_playlist_version": 1,
  "last_sync_time": "2026-08-11T10:30:00+07:00"
}
```

ส่งไปที่:

```text
POST https://your-domain.com/api/device/heartbeat
```

ผลที่ควรเห็น:

- `success` เป็น `true`
- Dashboard แสดง Last Seen ล่าสุด
- Device เปลี่ยนเป็น online ตามช่วงเวลาที่กำหนด

## 7. Android Test

- เปิดแอป Android
- เข้า Setting มุมขวาบน
- ตั้ง Device ID ให้ตรงกับในเว็บ
- ตั้ง API Base URL เป็น `https://your-domain.com`
- กด Sync Now
- แอปควรโหลด playlist จาก server
- ถ้ายังไม่มี playlist หรือ playlist ว่าง แอปควรแสดง fallback image
- กลับไปดู Dashboard ว่า heartbeat/last seen อัปเดตหรือไม่

## 8. ปัญหาที่พบบ่อย

- ได้หน้า HTML แทน JSON: ตรวจ `.htaccess` และ rewrite rule
- media ไม่โหลดบน Android: ตรวจว่า URL เป็น HTTPS และไฟล์เปิดได้จริง
- upload ไม่ได้: ตรวจ permission, `upload_max_filesize`, `post_max_size`
- config บอก device inactive: ตรวจ Device ID และสถานะ active ในหน้า Devices
- playlist ว่าง: ตรวจว่า assign playlist แล้ว และ media ใน playlist ยัง active
