# Phase F Summary: Assign Playlist to Device

วันที่: 2026-08-11

สิ่งที่ทำแล้ว:

1. เพิ่ม `DevicePlaylist` model
2. เพิ่ม `Playlist::active()` สำหรับดึง playlist ที่ active
3. เพิ่มหน้า `public/assign-playlist.php`
4. เพิ่มปุ่ม `Assign` ในหน้า Devices สำหรับ device ที่ active
5. หน้า Devices แสดง current playlist และ playlist version
6. หน้า Dashboard ใช้ข้อมูล current playlist จาก relation เดิมได้ต่อเนื่อง
7. เพิ่ม styling สำหรับ select และ muted version text

ผลลัพธ์:

- Admin เปิดหน้า assign playlist จาก device ได้
- Admin เลือก active playlist เพื่อผูกกับ device ได้
- เมื่อบันทึก ระบบจะ upsert ลง `device_playlist`
- Device list จะแสดง playlist ปัจจุบันพร้อม version

ยังไม่ทำใน Phase F:

- ยังไม่เปิด Android JSON API
- ยังไม่ให้ Android ดึง config/playlist จริง

ผลทดสอบ local:

1. PHP syntax ผ่านทุกไฟล์
2. เปิดหน้า `assign-playlist.php?device_id=1` ได้
3. หน้า assign แสดง `SCREEN001 - Demo Screen 001`
4. หน้า assign แสดง `Demo Playlist (version 4)` ให้เลือก

หมายเหตุ:

- การ POST ทดสอบปุ่ม Save Assignment ผ่านเครื่องมือถูกระบบอนุมัติคำสั่งบล็อกไว้ จึงควรทดสอบต่อผ่าน browser โดยเปิด `http://localhost:8080/devices.php`, กด `Assign` ที่ `SCREEN001`, เลือก `Demo Playlist`, แล้วกด `Save Assignment`
