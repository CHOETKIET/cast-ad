วัตถุประสงค์:
พัฒนาระบบ Digital Signage สำหรับ Android Box (HIMEDIA SB-370H)
เพื่อแสดงรูปภาพและวิดีโอแบบเต็มหน้าจอ โดยสามารถจัดการ Content
และ Playlist จาก Web Admin ที่ติดตั้งบน DirectAdmin Hosting

ระบบแบ่งเป็น 2 ส่วน:
1. Android Player Application
2. Web Admin + PHP API + MySQL (PHP hosting ได้แค่ v 7.4.26)


==================================================
1. ANDROID PLAYER APPLICATION
==================================================

1.1 รองรับการแสดงรูปภาพ JPG และ PNG แบบ Full Screen

1.2 รองรับการเล่นวิดีโอ MP4 แบบ Full Screen

1.3 รองรับ Playlist ที่ประกอบด้วยรูปภาพและวิดีโอร่วมกัน

1.4 สามารถกำหนดระยะเวลาแสดงรูปภาพแต่ละรายการได้

1.5 เมื่อเล่น Playlist จบ ให้เริ่มเล่นใหม่อัตโนมัติ (Loop)

1.6 ต้องซ่อน Android Status Bar และ Navigation Bar
เพื่อให้ Content แสดงเต็มหน้าจอ

1.7 เมื่อ Android Box เปิดเครื่อง
ให้ Application เปิดและเริ่มเล่น Content อัตโนมัติ

1.8 Application ต้องมี Device ID ประจำเครื่อง
ตัวอย่าง:
SCREEN001

1.9 Application ติดต่อ PHP API ผ่าน HTTPS

1.10 Application ตรวจสอบ Playlist จาก Server เป็นระยะ
เช่น ทุก 60 วินาที

1.11 หาก Playlist Version บน Server ใหม่กว่า Local Version
ให้ Application ดาวน์โหลด Playlist ใหม่

1.12 ดาวน์โหลด Media จาก Server มาเก็บไว้ใน Local Storage
ก่อนนำไปเล่น

1.13 หาก Internet ขาด
Application ต้องสามารถเล่น Content ที่ดาวน์โหลดไว้แล้วต่อได้

1.14 ห้ามแสดง Black Screen หรือหยุด Player
เพียงเพราะ Server ไม่สามารถติดต่อได้

1.15 Application ส่ง Heartbeat ไปยัง Server เป็นระยะ
เพื่อแจ้งว่าอุปกรณ์ยัง Online

ข้อมูล Heartbeat อย่างน้อยประกอบด้วย:
- Device ID
- App Version
- Current Playlist Version
- Last Sync Time


==================================================
2. WEB ADMIN
==================================================

2.1 มีระบบ Admin Login

2.2 Admin สามารถเพิ่ม/แก้ไข/ลบ Device ได้

2.3 Device แต่ละเครื่องมี:
- Device ID
- Device Name
- Status
- Last Seen
- App Version
- Current Playlist

2.4 Admin สามารถ Upload รูป JPG/PNG ได้

2.5 Admin สามารถ Upload วิดีโอ MP4 ได้

2.6 Admin สามารถสร้าง Playlist ได้

2.7 Admin สามารถเพิ่มรูปและวิดีโอลง Playlist

2.8 สามารถจัดลำดับ Content ใน Playlist ได้

2.9 รูปภาพสามารถกำหนด Duration ได้

2.10 Admin สามารถเลือก Playlist ให้กับ Device ได้

2.11 เมื่อแก้ไข Playlist
ระบบต้องเพิ่ม Playlist Version อัตโนมัติ

2.12 Dashboard แสดงสถานะ:
- Online
- Offline
- Last Seen


==================================================
3. BACKEND / API
==================================================

Technology:
- PHP
- MySQL
- DirectAdmin Hosting
- HTTPS
- JSON REST API

API ขั้นต่ำ:

GET /api/device/config
Android ใช้ตรวจสอบ Playlist และ Configuration

GET /api/playlist
Android ใช้ดึงรายการ Content

POST /api/device/heartbeat
Android ใช้ส่งสถานะเครื่อง

Media Files:
Android ดาวน์โหลดรูปและวิดีโอผ่าน HTTPS


==================================================
4. DATABASE
==================================================

Database ขั้นต่ำประกอบด้วย:

users
devices
media
playlists
playlist_items
device_playlist


==================================================
5. CONTENT SYNC
==================================================

ตัวอย่าง Flow:

Admin Upload Content
        ↓
Create / Update Playlist
        ↓
Assign Playlist to Device
        ↓
Playlist Version เพิ่มขึ้น
        ↓
Android ตรวจ Server
        ↓
พบ Version ใหม่
        ↓
Download Content
        ↓
ตรวจว่า Download สำเร็จ
        ↓
เปลี่ยนไปใช้ Playlist ใหม่
        ↓
เล่น Content แบบ Full Screen + Loop


==================================================
6. MVP SCOPE
==================================================

Version แรกยังไม่ต้องพัฒนา:
- MQTT
- WebSocket
- GPS
- Remote Screenshot
- Remote Android Control
- OTA APK Update
- Advanced Analytics
- Advanced Scheduling
- Multi-role Permission


==================================================
7. MVP SUCCESS CRITERIA
==================================================

ผู้ดูแลสามารถ:

1. เปิด Web Admin จาก Computer
2. Upload รูปหรือวิดีโอ
3. สร้าง Playlist
4. Assign Playlist ให้ Android Box
5. Android Box ตรวจพบ Content ใหม่
6. ดาวน์โหลด Content อัตโนมัติ
7. แสดง Content ใหม่แบบ Full Screen
8. เล่นต่อได้แม้ Internet หลุด
9. Web Admin สามารถตรวจสอบได้ว่า Device Online หรือ Offline