# แผนพัฒนา Android Player Settings และ Manual Playlist

## วัตถุประสงค์

ปรับหน้า Player Settings ฝั่ง Android ให้ใช้งานหน้างานได้สะดวกขึ้น เพิ่มเครื่องมือแก้ปัญหาเบื้องต้น และเพิ่มโหมดสร้าง playlist แบบ manual จากไฟล์ในเครื่อง/USB สำหรับกรณีไม่ต้องการใช้ web sync ชั่วคราว

## ขอบเขตงาน

- แก้เฉพาะฝั่ง Android app
- ไม่แก้ Webapp
- ไม่แก้ Database
- ต้อง build APK ใหม่หลังทำเสร็จ
- หลังจบแต่ละเฟสให้สร้างไฟล์ `.md` สรุปสั้น ๆ

## แนวทางสำคัญ

- หน้า Player Settings จะปรับเป็นภาษาไทยเป็นหลัก
- ศัพท์เทคนิคที่ควรใช้ English จะยังคงไว้ เช่น `Device ID`, `API Base URL`, `Sync Now`, `Connection Test`, `Clear Content`
- Manual Playlist จะใช้ Android file picker เพื่อเลือกไฟล์ภาพ/วิดีโอจาก storage หรือ USB ที่ระบบ Android มองเห็น
- Manual Playlist จะถูกเก็บแยกจาก playlist ที่มาจาก Web Sync เพื่อควบคุมพฤติกรรมไม่ให้สับสน
- ค่าเริ่มต้นของ player ยังคงเล่น playlist จาก Web Sync เหมือนเดิม

---

## Phase 1: ปรับ Player Settings UI เป็นภาษาไทยและดูดีขึ้น

### งานที่จะทำ

- ปรับข้อความในหน้า Player Settings เป็นภาษาไทย
- จัดกลุ่มข้อมูลใน settings ให้ชัดเจน เช่น ข้อมูลเครื่อง, การเชื่อมต่อ, สถานะ Sync, เครื่องมือหน้างาน
- ปรับ layout จาก dialog แบบเรียบ ๆ ให้ดูเป็น panel ที่อ่านง่ายขึ้น
- ปรับปุ่มให้กดง่ายบนจอ TV/Android Box
- คงช่องแก้ `Device ID` และ `API Base URL` ไว้ แต่จัดวางให้เข้าใจง่ายขึ้น

### สิ่งที่จะยังไม่ทำในเฟสนี้

- ยังไม่เพิ่ม Connection Test
- ยังไม่เพิ่ม Clear Content
- ยังไม่เพิ่ม Manual Playlist

### ไฟล์ที่คาดว่าจะกระทบ

- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`
- `app/src/main/res/values/strings.xml`

### เกณฑ์ตรวจรับ

- หน้า settings อ่านเป็นภาษาไทยเป็นหลัก
- ข้อมูลเดิมยังแสดงครบ
- Save, Cancel และ Sync Now ยังทำงานเหมือนเดิม
- หน้าจอดูเรียบร้อยกว่าปัจจุบัน

---

## Phase 2: เพิ่ม Connection Test

### งานที่จะทำ

- เพิ่มปุ่ม `Connection Test`
- เมื่อกดแล้วให้ทดสอบเรียก API config ด้วย `Device ID` และ `API Base URL` ปัจจุบัน
- แสดงผลลัพธ์เป็นภาษาไทย เช่น
  - เชื่อมต่อ Server สำเร็จ
  - ติดต่อ Server ไม่ได้
  - API Base URL ไม่ถูกต้อง
  - Device ID ยังไม่ถูกลงทะเบียน หรือไม่มี playlist
- บันทึกผล test ล่าสุดไว้ใน settings เพื่อดูย้อนหลังได้

### ไฟล์ที่คาดว่าจะกระทบ

- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`
- `app/src/main/java/com/example/ichi_play/network/ApiClient.kt`
- `app/src/main/java/com/example/ichi_play/storage/DeviceConfigStore.kt`
- `app/src/main/res/values/strings.xml`

### เกณฑ์ตรวจรับ

- กด Connection Test แล้วเห็นผลทันที
- ไม่ทำให้ playlist ปัจจุบันเปลี่ยน
- ไม่ trigger download media
- ไม่กระทบ sync loop ปกติ

---

## Phase 3: เพิ่ม Clear Content และปุ่มปิดโปรแกรม

### งานที่จะทำ

- เพิ่มปุ่ม `Clear Content`
- เมื่อกดแล้วให้ confirm ก่อนล้างข้อมูล
- Clear Content จะทำงานดังนี้
  - ล้าง media cache ที่ดาวน์โหลดไว้
  - ล้าง playlist ปัจจุบัน
  - กลับไปแสดง fallback image
  - ตั้ง playlist version กลับเป็น `0`
- เพิ่มปุ่ม `ปิดโปรแกรม`
- ปุ่มปิดโปรแกรมต้องมี confirm ก่อนออก
- หลังปิดโปรแกรมให้เรียก `finish()` เพื่อออกจาก Activity

### สิ่งที่ต้องระวัง

- Clear Content ไม่ควรลบ `Device ID`
- Clear Content ไม่ควรลบ `API Base URL`
- Clear Content ไม่ควรลบค่า last status ที่ใช้วิเคราะห์ปัญหา ยกเว้นจำเป็น

### ไฟล์ที่คาดว่าจะกระทบ

- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`
- `app/src/main/java/com/example/ichi_play/MainActivity.kt`
- `app/src/main/java/com/example/ichi_play/sync/MediaCacheManager.kt`
- `app/src/main/java/com/example/ichi_play/storage/LocalPlaylistStore.kt`
- `app/src/main/java/com/example/ichi_play/storage/DeviceConfigStore.kt`
- `app/src/main/res/values/strings.xml`

### เกณฑ์ตรวจรับ

- Clear Content แล้วจอกลับไป fallback image
- รูป/วิดีโอเก่าที่ cache ไว้ไม่ค้าง
- Sync Now หลัง Clear Content ยังใช้งานได้
- ปุ่มปิดโปรแกรมออกจากแอปได้จริงและมี confirm

---

## Phase 4: เพิ่ม Manual Playlist แบบเลือกไฟล์จากเครื่อง/USB

### งานที่จะทำ

- เพิ่มเมนู `Manual Playlist`
- เปิด Android file picker เพื่อเลือกภาพหรือวิดีโอ
- รองรับไฟล์เบื้องต้น:
  - รูปภาพ: `.jpg`, `.jpeg`, `.png`
  - วิดีโอ: `.mp4`
- ให้ผู้ใช้กำหนดระยะเวลาแสดงผลสำหรับรูปภาพเป็นวินาที
- วิดีโอให้เล่นจนจบไฟล์ ไม่ต้องกำหนดวินาที
- สร้าง playlist local จากไฟล์ที่เลือก
- บันทึกไฟล์ที่เลือกไว้ใน app storage เพื่อให้เล่นต่อได้หลัง reboot หากไฟล์ต้นทาง USB ถูกถอดออก
- ให้ player เริ่มเล่น Manual Playlist หลังสร้างเสร็จ

### ข้อจำกัดของเฟสแรก

- ยังไม่ทำระบบ drag/drop reorder
- ยังไม่ทำ thumbnail preview เต็มรูปแบบ
- ยังไม่ทำแก้ไขรายการหลายหน้าซับซ้อน
- ยังไม่ sync manual playlist ขึ้น web

### ไฟล์ที่คาดว่าจะกระทบ

- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`
- `app/src/main/java/com/example/ichi_play/MainActivity.kt`
- `app/src/main/java/com/example/ichi_play/storage/LocalPlaylistStore.kt`
- `app/src/main/java/com/example/ichi_play/player/PlayerController.kt`
- อาจเพิ่มไฟล์ใหม่ เช่น
  - `app/src/main/java/com/example/ichi_play/storage/ManualPlaylistStore.kt`
  - `app/src/main/java/com/example/ichi_play/ui/ManualPlaylistDialog.kt`

### เกณฑ์ตรวจรับ

- เลือกไฟล์จาก file picker ได้
- สร้าง playlist local ได้
- กำหนดวินาทีสำหรับรูปภาพได้
- วิดีโอเล่นได้
- ถอด USB หลังสร้าง playlist แล้วไฟล์ยังเล่นได้ เพราะถูก copy เข้า app storage
- Manual Playlist ไม่ทำให้ค่า `Device ID` หรือ server config หาย

---

## Phase 5: จัดการโหมด Web Sync กับ Manual Playlist

### งานที่จะทำ

- เพิ่มสถานะว่า player กำลังใช้โหมดใด
  - `Web Sync`
  - `Manual Playlist`
- ใน settings แสดงโหมดปัจจุบัน
- เพิ่มปุ่มกลับไปใช้ `Web Sync`
- เมื่อกลับไปใช้ Web Sync ให้กด Sync Now เพื่อโหลด playlist จาก server ได้
- ป้องกันไม่ให้ sync loop เขียนทับ manual playlist โดยไม่ตั้งใจ หากผู้ใช้เลือก Manual Playlist อยู่

### แนวทางที่เสนอ

- ถ้าอยู่โหมด `Web Sync`: ทำงานเหมือนปัจจุบัน
- ถ้าอยู่โหมด `Manual Playlist`: เล่น local playlist และหยุด auto-apply playlist จาก server ชั่วคราว
- ผู้ใช้ต้องกดกลับไป `Web Sync` เองเมื่อต้องการรับข้อมูลจาก webapp

### ไฟล์ที่คาดว่าจะกระทบ

- `app/src/main/java/com/example/ichi_play/storage/DeviceConfigStore.kt`
- `app/src/main/java/com/example/ichi_play/sync/SyncManager.kt`
- `app/src/main/java/com/example/ichi_play/MainActivity.kt`
- `app/src/main/java/com/example/ichi_play/ui/HiddenSettingsDialog.kt`

### เกณฑ์ตรวจรับ

- Manual Playlist ไม่ถูก web sync ทับทันที
- กลับไป Web Sync ได้ชัดเจน
- สถานะโหมดปัจจุบันแสดงใน settings
- Heartbeat ยังส่งสถานะไป server ได้ตามปกติ

---

## Phase 6: ตรวจสอบและ Build APK

### งานที่จะทำ

- ตรวจ build Android
- ทดสอบ flow หลัก:
  - เปิด settings
  - Save config
  - Sync Now
  - Connection Test
  - Clear Content
  - ปิดโปรแกรม
  - Manual Playlist
  - กลับไป Web Sync
- Build debug APK ใหม่
- สรุปไฟล์ที่แก้และ APK path

### เกณฑ์ตรวจรับ

- Build APK สำเร็จ
- ไม่ crash ใน flow settings หลัก
- ฟังก์ชันเดิมยังใช้งานได้
- ได้ APK สำหรับติดตั้งทดสอบบน Android Box

---

## คำถาม/ข้อควรยืนยันก่อนเริ่มพัฒนา

1. Manual Playlist ต้องการให้เลือกได้หลายไฟล์ในครั้งเดียวหรือเลือกทีละไฟล์ก็พอสำหรับรอบแรก?
2. เมื่ออยู่ Manual Playlist ต้องการให้หยุด Web Sync อัตโนมัติทั้งหมด หรือให้ sync ได้แต่ยังไม่ apply จนกดกลับ Web Sync?
3. ปุ่มปิดโปรแกรมต้องการแค่ปิดแอป หรืออยากให้มีปุ่ม shutdown เครื่อง Android ด้วยในอนาคต?

