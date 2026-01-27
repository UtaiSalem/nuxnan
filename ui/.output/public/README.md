# Public Assets Directory

## โครงสร้างโฟลเดอร์

```
public/
├── favicon.ico           # Website icon
├── images/
│   ├── logo.png         # Main logo (ไม่จำเป็น - ใช้ icon แทน)
│   └── resources/       # Resource images
│       └── README.md    # คำแนะนำ
└── README.md            # This file
```

## การใช้งาน

### ใน Component

```vue
<!-- ✅ ถูกต้อง - ใช้ icon จาก @nuxt/icon -->
<Icon name="mdi:white-balance-sunny" class="w-10 h-10" />

<!-- ✅ ถูกต้อง - ใช้ online placeholder -->
<img src="https://picsum.photos/800/600" alt="Photo" />

<!-- ❌ ไม่แนะนำ - ต้องมีไฟล์จริง -->
<img src="/images/logo.png" alt="Logo" />
```

## หมายเหตุ

โปรเจคนี้ใช้ **Icons** จาก `@nuxt/icon` แทนรูปภาพส่วนใหญ่เพื่อ:
- ✅ ไม่ต้องจัดการไฟล์รูปภาพ
- ✅ ขนาดเล็กกว่า
- ✅ ปรับแต่งง่าย (สี, ขนาด)
- ✅ โหลดเร็วกว่า

## การเพิ่มรูปภาพ

1. วางไฟล์ใน `public/images/`
2. อ้างอิงด้วย `/images/filename.png`

Example:
```vue
<img src="/images/my-photo.jpg" alt="My Photo" />
```
