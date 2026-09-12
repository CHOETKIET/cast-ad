# Android Player Settings Phase 2 Summary

## สิ่งที่ทำ

- เพิ่มปุ่ม `Connection Test` ใน Player Settings
- Connection Test ใช้ `Device ID` และ `API Base URL` ที่กรอกอยู่ในหน้า Settings
- ทดสอบโดยเรียก API `api/device/config`
- แสดงผลลัพธ์ในหน้า Settings ทันที
- บันทึกผล Connection Test ล่าสุดไว้ในเครื่อง
- แสดงผล Connection Test ล่าสุดในกลุ่มสถานะระบบ

## พฤติกรรมของ Connection Test

- ไม่เปลี่ยน playlist ปัจจุบัน
- ไม่ดาวน์โหลด media
- ไม่ trigger sync loop
- ไม่เปลี่ยนค่า `Device ID` หรือ `API Base URL` จนกว่าจะกดบันทึก

## ผลลัพธ์ที่รองรับ

- เชื่อมต่อ Server สำเร็จ
- เชื่อมต่อ Server สำเร็จ แต่ยังไม่มี Playlist
- ติดต่อ Server ไม่ได้
- API Base URL ไม่ถูกต้อง
- แสดง message จาก server หาก server ตอบกลับว่าล้มเหลว

## ไฟล์ที่แก้ไข

- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`
- `app/src/main/java/com/example/ichi_play/storage/DeviceConfigStore.kt`
- `app/src/main/res/values/strings.xml`

## การตรวจสอบ

- Build debug APK ผ่านสำเร็จ
- ยังไม่เพิ่ม Clear Content
- ยังไม่เพิ่มปุ่มปิดโปรแกรม
- ยังไม่เพิ่ม Manual Playlist
- ไม่แก้ Webapp
- ไม่แก้ Database

## APK ที่ build แล้ว

- `app/build/outputs/apk/debug/app-debug.apk`

