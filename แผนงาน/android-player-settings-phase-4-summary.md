# Android Player Settings Phase 4 Summary

## สิ่งที่ทำ

- เพิ่มส่วน `Manual Playlist` ใน Player Settings
- เพิ่มช่องกำหนด `เวลารูปภาพ (วินาที)`
- เพิ่มปุ่มเลือกภาพ/วิดีโอจากเครื่องหรือ USB
- ใช้ Android file picker แบบเลือกหลายไฟล์ได้
- รองรับไฟล์เบื้องต้น:
  - รูปภาพ `.jpg`, `.jpeg`, `.png`
  - วิดีโอ `.mp4`
- เพิ่ม `ManualPlaylistStore` สำหรับ copy ไฟล์ที่เลือกเข้า app storage
- สร้าง local playlist จากไฟล์ที่เลือก
- สั่ง Player เล่น Manual Playlist ทันทีหลังสร้างสำเร็จ
- บันทึก Manual Playlist เป็น active playlist ในเครื่อง

## พฤติกรรม Manual Playlist

- รูปภาพใช้เวลาตามค่าที่กรอกใน Settings
- วิดีโอเล่นจนจบไฟล์
- ไฟล์ที่เลือกจะถูก copy เข้า storage ของแอป
- หลัง copy แล้วสามารถถอด USB ได้ โดยไฟล์ที่ copy สำเร็จยังเล่นได้
- ถ้าไฟล์ไม่รองรับ จะข้ามไฟล์นั้น
- ถ้าไม่มีไฟล์ที่รองรับ จะแสดงข้อความว่าไม่พบไฟล์ที่รองรับ

## ข้อจำกัดที่ยังเหลือสำหรับ Phase 5

- ตอนนี้ Manual Playlist ยังไม่ได้มีโหมดแยกจาก Web Sync แบบสมบูรณ์
- ถ้า Web Sync พบ playlist version ใหม่ อาจมีโอกาส apply playlist จาก server ทับ Manual Playlist ได้
- Phase 5 จะเพิ่มโหมด `Web Sync` / `Manual Playlist` เพื่อควบคุมเรื่องนี้ให้ชัดเจน

## ไฟล์ที่เพิ่ม

- `app/src/main/java/com/example/ichi_play/storage/ManualPlaylistStore.kt`

## ไฟล์ที่แก้ไข

- `app/src/main/java/com/example/ichi_play/MainActivity.kt`
- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`
- `app/src/main/res/values/strings.xml`

## การตรวจสอบ

- Build debug APK ผ่านสำเร็จ
- ยังไม่เพิ่มโหมด Web Sync / Manual Playlist แบบเต็ม
- ไม่แก้ Webapp
- ไม่แก้ Database

## APK ที่ build แล้ว

- `app/build/outputs/apk/debug/app-debug.apk`

