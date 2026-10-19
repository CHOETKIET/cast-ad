# Content Sync Fix - Phase 4 Summary

## สิ่งที่ทำแล้ว

- เพิ่ม field `force_resync_token` และ `force_resync_requested_at` ใน Android config response model
- เพิ่ม field `last_applied_resync_token` ใน heartbeat request
- เพิ่มการเก็บ token ล่าสุดที่ Android apply แล้วใน `DeviceConfigStore`
- ปรับ `SyncManager` ให้ตรวจ token ใหม่จาก server
- ถ้าเจอ token ใหม่:
  - ล้าง media cache
  - ล้าง active playlist
  - ดึง playlist ใหม่จาก API แม้ version จะเท่าเดิม
  - ถ้า playlist ว่างหรือไม่มี active playlist ให้แสดง fallback image
  - บันทึก token ที่ apply แล้ว
- ปรับ heartbeat ให้ส่ง token ที่ apply แล้วกลับไปหา web
- ปรับ sync loop ให้ใช้ `sync_interval_seconds` จาก server แทนการ fix 60 วินาทีตลอด

## พฤติกรรมหลัง Phase 4

- Admin กด `Re-sync Now` บน web
- Android จะรับ token ในรอบ sync ถัดไป
- Android จะ clear cache และดึงข้อมูลใหม่ทั้งชุด
- Android ส่ง `last_applied_resync_token` กลับใน heartbeat
- Web สามารถ mark `force_resync_completed_at` ได้เมื่อ token ตรงกัน

## ไฟล์ที่แก้

- `app/src/main/java/com/example/cast_ad/data/ApiModels.kt`
- `app/src/main/java/com/example/cast_ad/storage/DeviceConfigStore.kt`
- `app/src/main/java/com/example/cast_ad/sync/SyncManager.kt`
- `app/src/main/java/com/example/cast_ad/sync/HeartbeatManager.kt`

## การตรวจสอบ

- Build APK debug สำเร็จ
- APK ใหม่อยู่ที่ `app/build/outputs/apk/debug/app-debug.apk`

## หมายเหตุ

- Phase 4 มีการแก้ Android จึงต้องติดตั้ง APK ใหม่บน Android Box
- ก่อนทดสอบปุ่ม `Re-sync Now` ต้อง deploy ไฟล์ webapp ของ Phase 3 และรัน migration SQL แล้ว
