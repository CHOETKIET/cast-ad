# Content Sync Fix - Phase 1 Summary

## สิ่งที่ทำแล้ว

- เพิ่มการหา playlist ที่ใช้งาน media ตัวใดตัวหนึ่งอยู่
- เมื่อ admin deactivate media ระบบจะเพิ่ม version ของ playlist ที่เกี่ยวข้องทั้งหมด
- เมื่อ admin เปลี่ยนสถานะ active/inactive ของ playlist จากหน้า edit ระบบจะเพิ่ม version
- เมื่อ admin deactivate playlist จากหน้า playlist list ระบบจะเพิ่ม version
- ปรับ API config ให้กรณี playlist ที่ assign ถูก inactive ยังส่ง `playlist_version` ล่าสุดออกไป
- ปรับ API playlist ให้กรณี playlist ที่ assign ถูก inactive ตอบ playlist ว่างพร้อม version ล่าสุด

## ผลลัพธ์ที่คาดหวัง

- ถ้า deactivate media ที่อยู่ใน playlist Android จะเห็น playlist version ใหม่ในรอบ sync ถัดไป
- ถ้า media ถูก inactive แล้ว API playlist จะไม่ส่ง media นั้นกลับไปให้ Android
- ถ้า playlist ถูก inactive แล้ว API จะตอบ playlist ว่าง เพื่อไม่ให้ Android ดึง item เดิมจาก server

## ไฟล์ที่แก้

- `app/models/PlaylistItem.php`
- `app/models/Playlist.php`
- `public/media.php`
- `public/playlist-edit.php`
- `public/api/device/config.php`
- `public/api/playlist.php`

## การตรวจสอบ

- ตรวจ PHP syntax ผ่านทุกไฟล์ที่แก้ใน Phase 1

## หมายเหตุ

- Phase 1 แก้เฉพาะฝั่ง webapp
- ยังไม่ได้แก้ Android ให้ล้าง local cache เก่าแบบสมบูรณ์
- ถ้า Android มี playlist เก่าค้างในเครื่องอยู่แล้ว การแก้ให้ล้างแน่นอนจะอยู่ใน Phase 2
