# Content Sync Fix - Phase 3 Summary

## สิ่งที่ทำแล้ว

- เพิ่ม column สำหรับคำสั่ง force re-sync ใน table `devices`
- เพิ่ม migration SQL สำหรับ server จริง
- เพิ่มปุ่ม `Re-sync Now` ในหน้า Devices
- เพิ่มการแสดงเวลา re-sync request และ completed ในหน้า Devices
- เพิ่ม model method สำหรับบันทึกคำสั่ง force re-sync
- ปรับ API config ให้ส่ง `force_resync_token` และ `force_resync_requested_at`
- ปรับ API heartbeat ให้รองรับ `last_applied_resync_token` เพื่อ mark completed ใน Phase 4

## Migration SQL

รันคำสั่งนี้บน server จริงก่อนอัปโหลด/ใช้งานไฟล์ Phase 3:

```sql
ALTER TABLE devices
  ADD COLUMN force_resync_token VARCHAR(64) NULL AFTER last_sync_time,
  ADD COLUMN force_resync_requested_at DATETIME NULL AFTER force_resync_token,
  ADD COLUMN force_resync_completed_at DATETIME NULL AFTER force_resync_requested_at;
```

ไฟล์ migration:

- `database/migrations/20260811_add_device_force_resync.sql`

## ไฟล์ที่แก้

- `database/schema.sql`
- `database/migrations/20260811_add_device_force_resync.sql`
- `app/models/Device.php`
- `public/devices.php`
- `public/api/device/config.php`
- `public/api/device/heartbeat.php`

## พฤติกรรมหลัง Phase 3

- Admin เห็นปุ่ม `Re-sync Now` บนหน้า Devices
- เมื่อกดปุ่ม ระบบจะสร้าง token ใหม่และบันทึกเวลา request
- API config จะส่ง token นี้ไปหา Android
- API heartbeat พร้อมรับ token กลับจาก Android เพื่อ mark completed

## หมายเหตุ

- Phase 3 แก้เฉพาะฝั่ง webapp
- ปุ่ม `Re-sync Now` จะยังไม่ทำให้ Android re-sync จริงจนกว่า Phase 4 จะเพิ่ม logic ฝั่ง Android ให้อ่าน token
- ตรวจ PHP syntax ผ่านทุกไฟล์ที่แก้ใน Phase 3
