# Photos System Implementation
# ระบบรูปภาพ - การนำไปใช้งาน

## ภาพรวม (Overview)

ระบบรูปภาพของ Nuxnan ได้รับการพัฒนาเรียบร้อยแล้ว ออกแบบมาเพื่อให้ผู้ใช้สามารถ:
- อัพโหลดรูปภาพ
- สร้างและจัดการอัลบั้ม
- กดไลค์และคอมเมนต์รูปภาพ
- ดูรูปภาพของผู้ใช้อื่น
- จัดการความเป็นส่วนตัว (Public/Private)

---

## ส่วนประกอบระบบ (System Components)

### 1. Database Models

#### Album Model ([`api/nuxnanravel/app/Models/Album.php`](api/nuxnanravel/app/Models/Album.php)
- **ฟิลด์หลัก:**
  - `id` - ID ของอัลบั้ม
  - `user_id` - ID ของผู้ใช้เจ้าของอัลบั้ม
  - `name` - ชื่ออัลบั้ม
  - `description` - รายละเอียดอัลบั้ม (optional)
  - `cover_photo` - URL รูปปกอัลบั้ม (optional)
  - `is_public` - สถานะการเป็นสาธารณะ (true/false)
  - `created_at`, `updated_at` - เวลาสร้างและแก้ไข
  - `deleted_at` - เวลาลบ (soft delete)

- **ความสัมพันธ์:**
  - `user()` - ผู้ใช้เจ้าของอัลบั้ม
  - `photos()` - รูปภาพในอัลบั้ม
  - `photos_count` - จำนวนรูปภาพ

#### Photo Model ([`api/nuxnanravel/app/Models/Photo.php`](api/nuxnanravel/app/Models/Photo.php))
- **ฟิลด์หลัก:**
  - `id` - ID ของรูปภาพ
  - `user_id` - ID ของผู้ใช้เจ้าของรูปภาพ
  - `album_id` - ID ของอัลบั้ม (optional)
  - `url` - URL ของรูปภาพ
  - `thumbnail_url` - URL ของรูปย่อ (optional)
  - `caption` - คำอธิบายรูปภาพ (optional)
  - `is_public` - สถานะการเป็นสาธารณะ (true/false)
  - `created_at`, `updated_at` - เวลาสร้างและแก้ไข
  - `deleted_at` - เวลาลบ (soft delete)

- **ความสัมพันธ์:**
  - `user()` - ผู้ใช้เจ้าของรูปภาพ
  - `album()` - อัลบั้มที่บรรจุรูปภาพ
  - `likes()` - การกดไลค์ทั้งหมด
  - `comments()` - คอมเมนต์ทั้งหมด
  - `likes_count` - จำนวนไลค์
  - `comments_count` - จำนวนคอมเมนต์
  - `is_liked` - สถานะการกดไลค์โดยผู้ใช้ปัจจุบัน

### 2. Controllers

#### PhotoController ([`api/nuxnanravel/app/Http/Controllers/Api/PhotoController.php`](api/nuxnanravel/app/Http/Controllers/Api/PhotoController.php)

**Methods:**
- `index(Request $request, ?string $identifier = null)` - ดูรูปภาพของผู้ใช้
- `getByAlbum(Request $request, int $albumId)` - ดูรูปภาพในอัลบั้ม
- `store(Request $request)` - อัพโหลดรูปภาพ
- `show(int $id)` - ดูรูปภาพเดี่ยว
- `update(Request $request, int $id)` - แก้ไขรูปภาพ
- `destroy(int $id)` - ลบรูปภาพ
- `like(int $id)` - กดไลค์รูปภาพ
- `unlike(int $id)` - ยกเลิกไลค์รูปภาพ

#### AlbumController ([`api/nuxnanravel/app/Http/Controllers/Api/AlbumController.php`](api/nuxnanravel/app/Http/Controllers/Api/AlbumController.php))

**Methods:**
- `index(Request $request, ?string $identifier = null)` - ดูอัลบั้มของผู้ใช้
- `show(int $id)` - ดูอัลบั้มเดี่ยว
- `store(Request $request)` - สร้างอัลบั้มใหม่
- `update(Request $request, int $id)` - แก้ไขอัลบั้ม
- `destroy(int $id)` - ลบอัลบั้ม

### 3. API Routes ([`api/nuxnanravel/routes/api-photos.php`](api/nuxnanravel/routes/api-photos.php)

**Profile Routes:**
```php
GET    /api/profile/photos          // ดูรูปภาพของตัวเอง
POST   /api/profile/photos          // อัพโหลดรูปภาพ
PUT    /api/profile/photos/{id}     // แก้ไขรูปภาพ
DELETE /api/profile/photos/{id}     // ลบรูปภาพ
GET    /api/profile/albums         // ดูอัลบั้มของตัวเอง
POST   /api/profile/albums         // สร้างอัลบั้ม
PUT    /api/profile/albums/{id}    // แก้ไขอัลบั้ม
DELETE /api/profile/albums/{id}    // ลบอัลบั้ม
```

**Photos Routes:**
```php
GET    /api/photos/album/{albumId}   // ดูรูปภาพในอัลบั้ม
POST   /api/photos/{id}/like         // กดไลค์
POST   /api/photos/{id}/unlike       // ยกเลิกไลค์
GET    /api/photos/{id}              // ดูรูปภาพเดี่ยว
```

**User Routes:**
```php
GET    /api/users/{identifier}/photos  // ดูรูปภาพของผู้ใช้อื่น
GET    /api/users/{identifier}/albums  // ดูอัลบั้มของผู้ใช้อื่น
```

### 4. Frontend Composable ([`ui/composables/usePhotos.ts`](ui/composables/usePhotos.ts))

**State:**
- `photos` - รายการรูปภาพ
- `albums` - รายการอัลบั้ม
- `isLoading` - สถานะการโหลด
- `isUploading` - สถานะการอัพโหลด
- `uploadProgress` - ความคืบหน้าการอัพโหลด
- `error` - ข้อความผิดพลาด
- `hasMore` - มีข้อมูลเพิ่มเติมไหม

**Methods:**
- `fetchPhotos(userId?, page)` - โหลดรูปภาพ
- `fetchAlbums(userId?)` - โหลดอัลบั้ม
- `uploadPhotos(files, albumId?)` - อัพโหลดรูปภาพ
- `deletePhoto(photoId)` - ลบรูปภาพ
- `toggleLike(photo)` - กดไลค์/ยกเลิกไลค์
- `updateCaption(photoId, caption)` - แก้ไขคำอธิบาย
- `createAlbum(name, description?)` - สร้างอัลบั้ม
- `deleteAlbum(albumId)` - ลบอัลบั้ม
- `loadMore(userId?)` - โหลดข้อมูลเพิ่มเติม
- `clearState()` - ล้าง state

---

## วิธีการใช้งาน (Usage Examples)

### 1. อัพโหลดรูปภาพ (Upload Photos)

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { uploadPhotos, isUploading, uploadProgress } = usePhotos()

// อัพโหลดรูปภาพ
const handleUpload = async (files: FileList) => {
  try {
    const newPhotos = await uploadPhotos(files)
    console.log('Uploaded:', newPhotos)
  } catch (error) {
    console.error('Upload failed:', error)
  }
}

// อัพโหลดไปยังอัลบั้ม
const handleUploadToAlbum = async (files: FileList, albumId: number) => {
  try {
    const newPhotos = await uploadPhotos(files, albumId)
    console.log('Uploaded to album:', newPhotos)
  } catch (error) {
    console.error('Upload failed:', error)
  }
}
```

### 2. ดูรูปภาพ (View Photos)

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { photos, isLoading, fetchPhotos, loadMore, hasMore } = usePhotos()

// โหลดรูปภาพของตัวเอง
onMounted(async () => {
  await fetchPhotos()
})

// โหลดรูปภาพของผู้ใช้อื่น
const loadUserPhotos = async (userId: string) => {
  await fetchPhotos(userId)
}

// โหลดข้อมูลเพิ่มเติม
const handleLoadMore = async () => {
  if (hasMore.value && !isLoading.value) {
    await loadMore()
  }
}
```

### 3. สร้างและจัดการอัลบั้ม (Manage Albums)

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { albums, fetchAlbums, createAlbum, deleteAlbum } = usePhotos()

// สร้างอัลบั้มใหม่
const handleCreateAlbum = async () => {
  try {
    const newAlbum = await createAlbum('My Vacation', 'Photos from my trip')
    console.log('Album created:', newAlbum)
  } catch (error) {
    console.error('Create failed:', error)
  }
}

// ลบอัลบั้ม
const handleDeleteAlbum = async (albumId: number) => {
  if (confirm('Are you sure you want to delete this album?')) {
    try {
      await deleteAlbum(albumId)
      console.log('Album deleted')
    } catch (error) {
      console.error('Delete failed:', error)
    }
  }
}
```

### 4. กดไลค์รูปภาพ (Like Photos)

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { photos, toggleLike } = usePhotos()

const handleLike = async (photo: Photo) => {
  try {
    await toggleLike(photo)
    // is_liked และ likes_count จะถูกอัปเดตอัตโนมัติ
  } catch (error) {
    console.error('Like failed:', error)
  }
}
```

### 5. แก้ไขและลบรูปภาพ (Edit and Delete Photos)

```typescript
import { usePhotos } from '@/composables/usePhotos'

const { updateCaption, deletePhoto } = usePhotos()

// แก้ไขคำอธิบาย
const handleUpdateCaption = async (photoId: number, newCaption: string) => {
  try {
    await updateCaption(photoId, newCaption)
    console.log('Caption updated')
  } catch (error) {
    console.error('Update failed:', error)
  }
}

// ลบรูปภาพ
const handleDeletePhoto = async (photoId: number) => {
  if (confirm('Are you sure you want to delete this photo?')) {
    try {
      await deletePhoto(photoId)
      console.log('Photo deleted')
    } catch (error) {
      console.error('Delete failed:', error)
    }
  }
}
```

---

## ข้อจำกัดและเงื่อนไข (Limitations and Conditions)

### การอัพโหลดรูปภาพ (Upload Limits)
- อัพโหลดได้สูงสุด: **10 รูป/ครั้ง**
- ขนาดไฟล์สูงสุด: **10MB/ไฟล์**
- รูปแบบที่รองรับ: **JPEG, PNG, JPG, GIF**

### การเข้าถึง (Access Control)
- รูปภาพและอัลบั้มสามารถตั้งค่าเป็น **Public** หรือ **Private**
- Public: ผู้ใช้อื่นสามารถดูได้
- Private: เจ้าของเท่านั้นที่ดูได้

### การลบ (Deletion)
- ลบรูปภาพ: ลบไฟล์จาก storage และ database
- ลบอัลบั้ม: ลบอัลบั้มและรูปภาพทั้งหมดในอัลบั้ม

---

## API Response Formats

### ดูรูปภาพ (List Photos)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "album_id": null,
      "url": "https://example.com/storage/photos/photo1.jpg",
      "thumbnail_url": "https://example.com/storage/photos/thumbnails/thumb1.jpg",
      "caption": "My photo",
      "is_public": true,
      "created_at": "2026-01-14T06:00:00.000000Z",
      "updated_at": "2026-01-14T06:00:00.000000Z",
      "likes_count": 5,
      "comments_count": 2,
      "is_liked": true,
      "user": {
        "id": 123,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

### อัพโหลดรูปภาพ (Upload Photos)
```json
{
  "success": true,
  "message": "Photos uploaded successfully",
  "photos": [
    {
      "id": 1,
      "user_id": 123,
      "album_id": null,
      "url": "https://example.com/storage/photos/photo1.jpg",
      "thumbnail_url": "https://example.com/storage/photos/thumbnails/thumb1.jpg",
      "caption": null,
      "is_public": true,
      "created_at": "2026-01-14T06:00:00.000000Z",
      "updated_at": "2026-01-14T06:00:00.000000Z",
      "likes_count": 0,
      "comments_count": 0,
      "is_liked": false,
      "user": {
        "id": 123,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ]
}
```

### ดูอัลบั้ม (List Albums)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "name": "My Vacation",
      "description": "Photos from my trip",
      "cover_photo": "https://example.com/storage/photos/cover1.jpg",
      "is_public": true,
      "created_at": "2026-01-14T06:00:00.000000Z",
      "updated_at": "2026-01-14T06:00:00.000000Z",
      "photos_count": 15,
      "user": {
        "id": 123,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ]
}
```

---

## การติดตั้งและการตั้งค่า (Setup and Configuration)

### 1. Database Migrations
ได้ทำการรัน migrations แล้ว:
```bash
cd api/nuxnanravel
php artisan migrate
```

### 2. Storage Configuration
ตรวจสอบให้แน่ใจว่ามีการตั้งค่า storage:
```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### 3. Create Storage Link
```bash
php artisan storage:link
```

---

## การทดสอบ (Testing)

### ทดสอบ API Endpoints

**1. ทดสอบการอัพโหลดรูปภาพ:**
```bash
curl -X POST http://localhost:8000/api/profile/photos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "photos[]=@/path/to/photo1.jpg" \
  -F "photos[]=@/path/to/photo2.jpg" \
  -F "caption=My photos"
```

**2. ทดสอบการดูรูปภาพ:**
```bash
curl http://localhost:8000/api/profile/photos \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**3. ทดสอบการสร้างอัลบั้ม:**
```bash
curl -X POST http://localhost:8000/api/profile/albums \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My Album","description":"My photo album"}'
```

---

## ข้อควรระวัง (Important Notes)

1. **Authentication:** ทุก API endpoints ต้องการ authentication ผ่าน JWT token
2. **Authorization:** ผู้ใช้สามารถแก้ไข/ลบเฉพาะรูปภาพและอัลบั้มของตัวเอง
3. **Privacy:** ตรวจสอบสถานะ is_public ก่อนแสดงรูปภาพ
4. **Storage:** ตรวจสอบให้แน่ใจว่ามีพื้นที่เพียงพอใน storage
5. **Validation:** ทุก requests ผ่าน validation เพื่อป้องกันข้อมูลไม่ถูกต้อง

---

## การแก้ไขปัญหา (Troubleshooting)

### ปัญหา: อัพโหลดไม่ได้
- ตรวจสอบขนาดไฟล์ (ต้องไม่เกิน 10MB)
- ตรวจสอบประเภทไฟล์ (JPEG, PNG, JPG, GIF เท่านั้น)
- ตรวจสอบ permissions ของ storage

### ปัญหา: ไม่เห็นรูปภาพ
- ตรวจสอบว่ารัน `php artisan storage:link` แล้วหรือยัง
- ตรวจสอบสถานะ is_public
- ตรวจสอบ permissions ของไฟล์

### ปัญหา: ไม่สามารถลบรูปภาพได้
- ตรวจสอบว่าเป็นเจ้าของรูปภาพหรือไม่
- ตรวจสอบ permissions ของ storage

---

## การพัฒนาต่อ (Future Enhancements)

### Features ที่อาจเพิ่มในอนาคต:
1. **Photo Tags** - เพิ่ม tags ให้รูปภาพ
2. **Photo Search** - ค้นหารูปภาพตาม tags, caption
3. **Photo Sharing** - แชร์รูปภาพไปยัง social media
4. **Photo Editing** - แก้ไขรูปภาพ (crop, filter)
5. **Photo Download** - ดาวน์โหลดรูปภาพ
6. **Photo Comments** - คอมเมนต์ในรูปภาพ
7. **Album Collaboration** - ให้ผู้ใช้อื่นมีส่วนร่วมในอัลบั้ม
8. **Photo Statistics** - สถิติการดูรูปภาพ

---

## สรุป (Summary)

ระบบรูปภาพของ Nuxnan พร้อมใช้งานแล้ว ประกอบด้วย:

✅ **Database Models:** Album และ Photo
✅ **Controllers:** PhotoController และ AlbumController
✅ **API Routes:** ทุก endpoints ที่จำเป็น
✅ **Frontend Composable:** usePhotos สำหรับ Vue.js
✅ **Documentation:** คู่มือการใช้งานครบถ้วน

### ขั้นตอนถัดไป (Next Steps):
1. ทดสอบระบบอย่างละเอียด
2. สร้าง UI components สำหรับ Photos และ Albums
3. เพิ่ม features เพิ่มเติมตามที่ต้องการ
4. ปรับปรุง performance และ user experience

---

**เอกสารนี้อัปเดตล่าสุดเมื่อ: 14 มกราคม 2026**
