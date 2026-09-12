# แผนแก้ปัญหา Content ค้าง + ปุ่ม Re-sync Now

โปรเจกต์: iCHI-COOL Play  
ขอบเขต: Webapp + Android Player  
สถานะ: รออนุมัติก่อนเริ่มแก้ไข

---

## 1. ปัญหาที่พบ

หลังจาก assign รูปแรกไปยัง Android แล้ว รูปแสดงผลได้ปกติ แต่เมื่อทำรายการต่อไปนี้ รูปเดิมยังค้างอยู่:

- Deactivate media
- Upload รูปใหม่แล้วต้องการแทนรูปเดิม
- Deactivate playlist
- ปิดเปิด Android Box ใหม่

สาเหตุหลัก:

- Android เก็บ playlist ล่าสุดไว้ในเครื่อง เพื่อให้ยังเล่นได้หลัง reboot หรือช่วง network หลุด
- ฝั่ง web ยังไม่ได้เพิ่ม playlist version เมื่อ media ถูก deactivate
- เมื่อไม่มี active playlist แล้ว API ส่ง `playlist_version = 0`
- Android เห็น version ใหม่ไม่มากกว่า version ที่มีในเครื่อง จึงไม่ล้าง playlist เก่า
- ยังไม่มีคำสั่งจาก web ให้ Android “ล้าง cache แล้ว sync ใหม่ทั้งหมด”

---

## 2. เป้าหมายการแก้ไข

1. เมื่อ media ถูก deactivate แล้ว Android ต้องไม่เล่น media นั้นต่อ
2. เมื่อ playlist ถูก deactivate หรือไม่มี playlist assign แล้ว Android ต้องกลับไปแสดง fallback image
3. เมื่อ playlist ว่าง Android ต้องแสดง fallback image ไม่เล่น content เก่า
4. เพิ่มปุ่ม `Re-sync Now` บน webapp เพื่อบังคับให้ Android รับข้อมูลใหม่ทั้งชุด
5. ลดโอกาสที่ Android ใช้ local cache เก่าหลัง reboot

---

## 3. ข้อจำกัดสำคัญของปุ่ม Re-sync Now

ระบบปัจจุบันเป็นแบบ Android ดึงข้อมูลจาก server เป็นรอบ ๆ ไม่ใช่ server push

ดังนั้นปุ่ม `Re-sync Now` บน webapp จะทำงานแบบ:

- Admin กดปุ่มบน web
- Web บันทึกคำสั่งว่า device นี้ต้อง force re-sync
- Android จะเห็นคำสั่งนี้ในรอบ sync/heartbeat ถัดไป
- Android ล้าง playlist/cache เดิม แล้วดึงข้อมูลใหม่ทั้งหมด
- หลังทำสำเร็จ Android ส่ง heartbeat กลับมาให้ web เห็นสถานะล่าสุด

หมายเหตุ:

- หากต้องการสั่งทันทีแบบ real-time จริง ๆ ต้องเพิ่ม MQTT, WebSocket หรือ push channel ซึ่งอยู่นอก MVP เดิม
- สำหรับ MVP นี้จะทำเป็น near real-time ผ่าน sync polling

---

## 4. แนวทางออกแบบ

### 4.1 ฝั่ง Webapp

เพิ่ม field หรือ table สำหรับคำสั่ง re-sync ต่อ device

แนวทางที่เสนอ:

- เพิ่ม column ใน table `devices`
  - `force_resync_token`
  - `force_resync_requested_at`
  - `force_resync_completed_at`
- เพิ่มปุ่ม `Re-sync Now` ในหน้า Devices
- เมื่อกดปุ่ม:
  - สร้าง token ใหม่
  - บันทึกเวลา request
  - API config ส่ง token นี้ให้ Android
- Android ส่ง token กลับมากับ heartbeat เมื่อทำ force sync สำเร็จ
- Dashboard/Device list แสดงสถานะว่า re-sync ล่าสุดถูกขอเมื่อไร และสำเร็จเมื่อไร

### 4.2 ฝั่ง API

ปรับ `GET /api/device/config`

เพิ่ม field:

```json
{
  "force_resync_token": "abc123",
  "force_resync_requested_at": "2026-08-11T16:50:00+07:00"
}
```

ปรับ `POST /api/device/heartbeat`

ให้ Android ส่งกลับ:

```json
{
  "device_id": "SCREEN-1B480359",
  "app_version": "1.0",
  "current_playlist_version": 2,
  "last_sync_time": "2026-08-11T16:51:00+07:00",
  "last_applied_resync_token": "abc123"
}
```

เมื่อ token ตรงกับคำสั่งล่าสุด ให้ web update `force_resync_completed_at`

### 4.3 ฝั่ง Android

เพิ่ม logic:

- จำ token ล่าสุดที่เคย apply แล้ว
- ถ้า API config ส่ง `force_resync_token` ใหม่:
  - ล้าง active playlist
  - ล้าง pending playlist
  - ล้าง media cache ที่ download ไว้
  - ดึง playlist ใหม่จาก API แม้ version จะเท่าหรือต่ำกว่าเดิม
  - ถ้าไม่มี playlist หรือ playlist ว่าง ให้แสดง fallback image
  - บันทึก token ที่ apply แล้ว
  - ส่ง token กลับใน heartbeat

---

## 5. เฟสการพัฒนา

### Phase 1: Web Version Invalidation

เป้าหมาย:

- ทำให้ web แจ้ง Android ได้ถูกต้องเมื่อ media/playlist ถูกเปลี่ยนจน content เดิมใช้ต่อไม่ได้

งานที่จะทำ:

1. เพิ่ม model method หา playlist ที่ใช้ media หนึ่ง ๆ
2. เมื่อ deactivate media ให้เพิ่ม version ของ playlist ที่ได้รับผลกระทบ
3. เมื่อ deactivate playlist ให้ทำให้ device ที่ assign อยู่เห็นสถานะไม่มี active playlist ชัดเจน
4. ตรวจ API playlist ให้ส่ง `items = []` เมื่อไม่มี media active
5. ตรวจ API config ให้ยังตอบ JSON ชัดเจนทุกกรณี

ผลลัพธ์:

- Deactivate media แล้ว playlist version เพิ่ม
- Android รอบ sync ถัดไปจะรู้ว่ามีข้อมูลเปลี่ยน

ไฟล์ที่คาดว่าจะแก้:

- `web-ichicoolpay/app/models/Media.php`
- `web-ichicoolpay/app/models/PlaylistItem.php`
- `web-ichicoolpay/public/media.php`
- `web-ichicoolpay/public/playlists.php`
- `web-ichicoolpay/public/api/device/config.php`
- `web-ichicoolpay/public/api/playlist.php`

---

### Phase 2: Android Clear Stale Content

เป้าหมาย:

- ทำให้ Android ล้าง content เก่าเมื่อ server บอกว่าไม่มี playlist หรือ playlist ว่าง

งานที่จะทำ:

1. เพิ่ม `clearActivePlaylist()` ใน `LocalPlaylistStore`
2. เพิ่มการล้าง pending playlist
3. เพิ่มการล้าง media cache ที่ download แล้ว
4. ปรับ `SyncManager`
   - ถ้า `playlist_id = null` หรือ `playlist_version = 0` ให้ล้าง playlist เก่าและแสดง fallback
   - ถ้า playlist ใหม่มี `items = []` ให้บันทึก playlist ว่างและแสดง fallback
   - ไม่ใช้ rule “version ต้องมากกว่าเท่านั้น” ในกรณี clear content
5. ปรับ UI status ให้เห็นว่า content ถูก clear แล้ว

ผลลัพธ์:

- Deactivate playlist แล้ว Android ไม่เล่นรูปเก่าต่อ
- Playlist ว่างแล้ว Android แสดง fallback image
- Reboot แล้วไม่กลับไปเล่น content ที่ถูกถอดออกแล้ว

ไฟล์ที่คาดว่าจะแก้:

- `app/src/main/java/com/example/ichi_play/storage/LocalPlaylistStore.kt`
- `app/src/main/java/com/example/ichi_play/sync/MediaCacheManager.kt`
- `app/src/main/java/com/example/ichi_play/sync/SyncManager.kt`
- `app/src/main/java/com/example/ichi_play/MainActivity.kt`

ต้อง rebuild APK:

- ใช่

---

### Phase 3: Web Re-sync Now Button

เป้าหมาย:

- ให้ admin กดบังคับ re-sync จากหน้า web ได้

งานที่จะทำ:

1. เพิ่ม migration SQL สำหรับ column re-sync ใน `devices`
2. เพิ่ม model method:
   - request force resync
   - mark force resync completed
3. เพิ่มปุ่ม `Re-sync Now` ในหน้า Devices
4. เพิ่มข้อมูลสถานะ re-sync ใน Device list
5. ปรับ API config ให้ส่ง `force_resync_token`
6. ปรับ API heartbeat ให้รับ `last_applied_resync_token`

ผลลัพธ์:

- Admin กด `Re-sync Now` ให้ device ใด device หนึ่งได้
- Android เห็นคำสั่งในรอบ sync ถัดไป
- Web เห็นเวลาที่ request และเวลาที่ Android apply สำเร็จ

ไฟล์ที่คาดว่าจะแก้:

- `web-ichicoolpay/database/schema.sql`
- `web-ichicoolpay/database/migrations/`
- `web-ichicoolpay/app/models/Device.php`
- `web-ichicoolpay/public/devices.php`
- `web-ichicoolpay/public/api/device/config.php`
- `web-ichicoolpay/public/api/device/heartbeat.php`

---

### Phase 4: Android Force Re-sync Command

เป้าหมาย:

- ทำให้ Android รับคำสั่ง force re-sync จาก web และดึงข้อมูลใหม่ทั้งหมด

งานที่จะทำ:

1. เพิ่ม field ใน `DeviceConfigResponse`
   - `force_resync_token`
   - `force_resync_requested_at`
2. เพิ่ม field ใน `HeartbeatRequest`
   - `last_applied_resync_token`
3. เก็บ token ล่าสุดที่ apply แล้วใน `DeviceConfigStore`
4. เมื่อ token ใหม่มา:
   - clear playlist/cache
   - fetch playlist ใหม่
   - activate playlist ใหม่ หรือ fallback ถ้าว่าง
   - ส่ง token กลับใน heartbeat
5. ปรับ sync loop ให้ใช้ interval จาก config ได้จริง ไม่ fix ที่ 60 วินาทีตลอด

ผลลัพธ์:

- กดปุ่มจาก web แล้ว Android จะ re-sync ทั้งชุดในรอบ sync ถัดไป
- ถ้าตั้ง interval ต่ำ เช่น 15 วินาที จะใกล้เคียง real-time มากขึ้น

ไฟล์ที่คาดว่าจะแก้:

- `app/src/main/java/com/example/ichi_play/data/ApiModels.kt`
- `app/src/main/java/com/example/ichi_play/storage/DeviceConfigStore.kt`
- `app/src/main/java/com/example/ichi_play/sync/SyncManager.kt`
- `app/src/main/java/com/example/ichi_play/sync/HeartbeatManager.kt`

ต้อง rebuild APK:

- ใช่

---

### Phase 5: Verification

เป้าหมาย:

- ทดสอบ flow จริงให้ครบก่อนนำไปใช้งานต่อ

รายการทดสอบ:

1. Assign รูปแรกแล้ว Android แสดงผล
2. Deactivate media แล้ว Android ไม่เล่นรูปนั้นต่อ
3. Deactivate playlist แล้ว Android แสดง fallback image
4. สร้าง playlist ใหม่และ assign ใหม่ แล้ว Android ดึง content ใหม่
5. กด `Re-sync Now` จาก web แล้ว Android clear cache + sync ใหม่
6. Reboot Android แล้วไม่เล่น content ที่ถูกลบ/deactivate
7. Dashboard แสดง heartbeat และ re-sync completed time

ผลลัพธ์:

- ระบบจัดการ stale content ได้
- Admin มีปุ่มบังคับ sync จาก web
- Android ไม่ค้างรูปเก่าหลังเปลี่ยนข้อมูลบน web

---

## 6. สิ่งที่ต้องอนุมัติ

กรุณาอนุมัติว่าให้เริ่มแก้ตามแผนนี้หรือไม่

ข้อเสนอ:

- เริ่มจาก Phase 1 และ Phase 2 ก่อน เพื่อแก้ปัญหารูปค้างทันที
- จากนั้นทำ Phase 3 และ Phase 4 เพื่อเพิ่มปุ่ม `Re-sync Now`
- ปิดท้ายด้วย Phase 5 เพื่อทดสอบครบ flow
