# Content Sync Fix - Phase 2 Summary

## สิ่งที่ทำแล้ว

- เพิ่มความสามารถให้ Android ล้าง playlist ที่ cache ไว้ในเครื่อง
- เพิ่มความสามารถให้ Android ล้าง media cache ที่ดาวน์โหลดไว้
- ปรับ sync logic ให้รองรับกรณี server แจ้งว่าไม่มี active playlist
- ปรับ sync logic ให้รองรับกรณี playlist ใหม่มี `items = []`
- เมื่อ content ถูก clear แล้ว Android จะกลับไปแสดง fallback image
- เมื่อ reboot หลัง clear แล้ว Android จะไม่กลับไปเล่น content เก่าที่ถูกถอดออกแล้ว

## พฤติกรรมหลังแก้

- ถ้า API config ส่ง `playlist_id = null` หรือ `playlist_version = 0`
  - Android จะล้าง active playlist
  - Android จะล้าง pending playlist
  - Android จะล้าง media cache
  - Android จะแสดง fallback image
- ถ้า API playlist ส่ง item ว่าง
  - Android จะล้าง content เก่า
  - Android จะแสดง fallback image
- ถ้า playlist version ใหม่มี media ปกติ
  - Android จะดาวน์โหลด media และเล่น playlist ใหม่เหมือนเดิม

## ไฟล์ที่แก้

- `app/src/main/java/com/example/cast_ad/storage/LocalPlaylistStore.kt`
- `app/src/main/java/com/example/cast_ad/sync/MediaCacheManager.kt`
- `app/src/main/java/com/example/cast_ad/sync/SyncManager.kt`

## การตรวจสอบ

- Build APK debug สำเร็จ
- APK ใหม่อยู่ที่ `app/build/outputs/apk/debug/app-debug.apk`

## หมายเหตุ

- Phase 2 มีการแก้ Android จึงต้องติดตั้ง APK ใหม่บน Android Box
- Phase นี้ยังไม่ได้เพิ่มปุ่ม `Re-sync Now` บน web ปุ่มจะอยู่ใน Phase 3 และ Android command token จะอยู่ใน Phase 4
