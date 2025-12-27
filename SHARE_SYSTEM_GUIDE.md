# Share System Implementation Guide

## 📊 โครงสร้างระบบ Share ใหม่

### **แนวคิด:**
- ✅ **Share เป็น Model แยก** (เหมือน Post, CoursePost)
- ✅ **มี Reactions ของตัวเอง** (likes, dislikes, comments)
- ✅ **อยู่ภายใต้ Activity** (polymorphic relationship)
- ✅ **ไม่กระทบระบบเดิม**

---

## 🗄️ Database Schema

### **1. shares table**
```sql
- id
- user_id (คนแชร์)
- shareable_type (Post, CoursePost, AcademyPost)
- shareable_id
- share_comment (ข้อความที่แนบมากับการแชร์)
- privacy (public, friends, private)
- likes, dislikes, comments, views (counters)
- timestamps
```

### **2. share_likes, share_dislikes, share_comments**
```sql
-- Pivot tables เหมือนระบบ Post
```

---

## 📝 ไฟล์ที่สร้างแล้ว

### **Migrations:**
1. ✅ `2024_12_24_000001_create_shares_table.php`
2. ✅ `2024_12_24_000002_create_share_likes_table.php`
3. ✅ `2024_12_24_000003_create_share_dislikes_table.php`
4. ✅ `2024_12_24_000004_create_share_comments_table.php`

### **Models:**
1. ✅ `app/Models/Share.php` - Main share model
2. ✅ `app/Models/ShareComment.php` - Comment model
3. ✅ `app/Models/Activity.php` - อัพเดทแล้ว
4. ✅ `app/Models/Post.php` - เพิ่ม postShares() relationship

### **Controllers:**
1. ✅ `app/Http/Controllers/ShareController.php` - Create/Delete shares
2. ✅ `app/Http/Controllers/ShareReactionController.php` - Like/Dislike shares

### **Resources:**
1. ✅ `app/Http/Resources/Play/ShareResource.php`

### **Routes:**
1. ✅ `routes/api_shares.php`

---

## 🚀 การติดตั้ง

### **Step 1: Run Migrations**
```bash
cd c:\wamp64\www\nuxni\api\nuxniravel
php artisan migrate
```

### **Step 2: เพิ่ม Route ใน api.php**
```php
// ใน routes/api.php เพิ่มบรรทัดนี้
require __DIR__.'/api_shares.php';
```

### **Step 3: อัพเดท ActivityResource**
เปิดไฟล์ `app/Http/Resources/Play/ActivityResource.php`

ในฟังก์ชัน `relateResource()` เพิ่ม:
```php
elseif ($type === 'Share') {
    return new \App\Http\Resources\Play\ShareResource($model);
}
```

---

## 🔄 API Endpoints

### **สร้างการแชร์**
```http
POST /api/shares
Authorization: Bearer {token}

Body:
{
  "shareable_type": "Post",        // or "CoursePost", "AcademyPost"
  "shareable_id": 123,
  "share_comment": "เห็นด้วยมาก!",  // optional
  "privacy": "public"               // optional: public, friends, private
}

Response:
{
  "success": true,
  "message": "แชร์โพสต์สำเร็จ",
  "share": { ... },
  "user_points": 964
}
```

### **ลบการแชร์**
```http
DELETE /api/shares/{id}
Authorization: Bearer {token}
```

### **กดไลค์การแชร์**
```http
POST /api/shares/{id}/like
Authorization: Bearer {token}

Response:
{
  "success": true,
  "liked": true,
  "likes": 15,
  "dislikes": 2
}
```

### **กดดิสไลค์การแชร์**
```http
POST /api/shares/{id}/dislike
Authorization: Bearer {token}
```

### **ดูรายการคนที่แชร์**
```http
GET /api/shares/{type}/{id}
// type: post, course-post, academy-post
// id: post id

Example: GET /api/shares/post/123
```

---

## 📊 Data Flow

### **เมื่อมีการแชร์:**
```
1. สร้าง Share record
2. สร้าง Activity (activityable_type = 'App\Models\Share')
3. ตัดแต้มผู้แชร์: -36 แต้ม
4. เพิ่มแต้มเจ้าของโพสต์: +18 แต้ม
5. เพิ่ม shares count ในโพสต์ต้นฉบับ
```

### **เมื่อกดไลค์การแชร์:**
```
1. สร้าง record ใน share_likes
2. ตัดแต้มผู้กด: -24 แต้ม (like) / -12 แต้ม (unlike)
3. เพิ่มแต้มเจ้าของการแชร์: +12 แต้ม
4. เพิ่ม likes count ใน shares table
```

---

## 🎨 Frontend Integration

### **แสดงการแชร์ใน News Feed:**
```javascript
// Activity จะมี:
{
  action: 'share_post',
  action_by: { ... },           // คนแชร์
  action_to: 'Share',
  target_resource: {            // Share object
    id: 456,
    share_comment: "เห็นด้วยมาก!",
    likes: 15,
    dislikes: 2,
    shareable: {                // โพสต์ต้นฉบับ
      id: 123,
      content: "...",
      author: { ... }
    }
  }
}
```

### **Component Structure:**
```
┌────────────────────────────────────┐
│ 👤 สมชาย แชร์                     │ ← Activity header
│ "เห็นด้วยกับเรื่องนี้มาก!"        │ ← share_comment
│                                    │
│ ┌────────────────────────────────┐ │
│ │ 📝 โพสต์ต้นฉบับ (Nested)       │ │ ← shareable
│ └────────────────────────────────┘ │
│                                    │
│ ❤️ 15 👎 2 💬 8                   │ ← Share stats
│ [ถูกใจ] [ไม่ถูกใจ] [คอมเมนต์]    │ ← Share actions
└────────────────────────────────────┘
```

---

## ✨ ข้อดี

1. **ไม่กระทบระบบเดิม** - Post ยังทำงานเหมือนเดิม
2. **แยก Concerns** - Share มี reactions แยก จาก Post
3. **Scalable** - เพิ่ม share types อื่นได้ง่าย
4. **Consistent** - ใช้ pattern เดียวกับ Post
5. **Flexible** - รองรับ privacy, comments ได้

---

## 🔧 การอัพเดทระบบเดิม

### **ลบโค้ดเก่า (Optional):**
1. `PostShareController.php` - ไม่ต้องใช้แล้ว
2. `CoursePostShareController.php` - ไม่ต้องใช้แล้ว
3. Routes `/posts/{post}/share` - ย้ายไปใช้ `/api/shares`

### **Migration Data (ถ้ามีข้อมูลเก่า):**
```php
// สร้าง migration เพื่อ migrate activities เก่า
// จาก activityable_type = 'Post' + activity_type = 'share_post'
// ไปเป็น activityable_type = 'Share'
```

---

## 🎯 Next Steps

1. ✅ Run migrations
2. ✅ เพิ่ม route ใน api.php
3. ✅ อัพเดท ActivityResource
4. ✅ ทดสอบ API
5. ✅ อัพเดท Frontend components
6. ✅ Migration ข้อมูลเก่า (ถ้ามี)

---

**สร้างเสร็จแล้ว! 🎉**
