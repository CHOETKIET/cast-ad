# Android Player Settings Phase 6 Summary

## สิ่งที่ทำ

- ตรวจ build Android ปิดท้าย
- ตรวจรายการฟังก์ชันที่เพิ่มใน Phase 1-5
- สรุป flow ที่ควรนำไปทดสอบบน Android Box
- Build debug APK ล่าสุด

## ผลการตรวจสอบ

- Build debug APK ผ่านสำเร็จ
- ไม่แก้ Webapp
- ไม่แก้ Database
- ต้องติดตั้ง APK ใหม่เพื่อทดสอบฟังก์ชัน Player Settings ชุดนี้

## ฟังก์ชันที่พร้อมทดสอบ

- Player Settings ภาษาไทยพร้อมจัดกลุ่มข้อมูล
- `Connection Test`
- `Clear Content`
- ปุ่ม `ปิดโปรแกรม`
- `Manual Playlist` จากไฟล์ในเครื่องหรือ USB
- โหมด `Web Sync`
- โหมด `Manual Playlist`
- ปุ่ม `กลับไปใช้ Web Sync`

## Checklist ทดสอบบน Android Box

1. เปิดแอปแล้วเข้า Player Settings ด้วย hidden hotspot
2. ตรวจว่า Settings แสดงภาษาไทยและข้อมูลเครื่องครบ
3. กรอก `Device ID` และ `API Base URL` แล้วกด `บันทึก`
4. เข้า Settings ใหม่แล้วกด `Connection Test`
5. กด `Sync Now` แล้วตรวจว่ายัง sync จาก webapp ได้
6. กด `Clear Content` แล้วยืนยัน ตรวจว่ากลับไป fallback image
7. กด `Manual Playlist` เลือกภาพ/วิดีโอจากเครื่องหรือ USB
8. ตรวจว่า Manual Playlist เล่นได้
9. เข้า Settings ตรวจว่าโหมดเป็น `Manual Playlist`
10. ตรวจว่า auto sync ไม่ทับ Manual Playlist
11. กด `กลับไปใช้ Web Sync` แล้วตรวจว่าสามารถ sync จาก webapp ได้อีกครั้ง
12. กด `ปิดโปรแกรม` แล้วยืนยัน ตรวจว่าแอปปิดจริง

## APK ล่าสุด

- `app/build/outputs/apk/debug/app-debug.apk`

## คำสั่งติดตั้งตัวอย่าง

```bash
adb -s 192.168.1.105:5555 install -r /Users/choetkiet.s/AndroidStudioProjects/ichiplay/app/build/outputs/apk/debug/app-debug.apk
```

