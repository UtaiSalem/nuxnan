# Academy Routes Audit

This file lists the academy destinations mapping and verifies if the corresponding Nuxt page file exists in the frontend.

| ID | Title | Intended Route | Nuxt Page File Path | Verified Exists | Notes |
|---|---|---|---|---|---|
| `my-dashboard` | ไปแดชบอร์ดของฉัน | `/academies/{n}/dashboard` | `ui/pages/academies/[name]/dashboard/index.vue` | Yes | Routes internally via middleware/role |
| `continue-learning` | เรียนต่อ | `/academies/{n}#courses` | `ui/pages/academies/[name].vue#courses` | Yes | Uses hash to switch tab |
| `my-assignments` | งาน/แบบทดสอบของฉัน | `/academies/{n}/dashboard/student` | `ui/pages/academies/[name]/dashboard/student.vue` | Yes | Temporary target |
| `my-attendance` | เช็กชื่อ/ประวัติเข้าเรียน | `/academies/{n}/attendance/check-in` | `ui/pages/academies/[name]/attendance/check-in.vue` | Yes | Student check-in page |
| `my-transcript` | ผลการเรียน | `/academies/{n}/my-transcript` | `ui/pages/academies/[name]/my-transcript.vue` | Yes | Student transcript overview |
| `my-profile` | โปรไฟล์ของฉัน | `/academies/{n}/my-profile` | `ui/pages/academies/[name]/my-profile.vue` | Yes | Reuses profile view components |
| `my-card` | บัตรนักเรียน | `/academies/{n}/my-card` | `ui/pages/academies/[name]/my-card.vue` | Yes | Renders student card preview |
| `members-and-classrooms` | ห้องเรียน/สมาชิก | `/academies/{n}#classrooms` | `ui/pages/academies/[name].vue#classrooms` | Yes | Uses hash to switch tab |
| `announcements` | ประกาศและกิจกรรม | `/academies/{n}#events` | `ui/pages/academies/[name].vue#events` | Yes | Uses hash to switch tab |
| `school-store` | ร้านค้าโรงเรียน | `/academies/{n}/store` | `ui/pages/academies/[name]/store.vue` | Yes | School marketplace store |
| `manage-school` | จัดการโรงเรียน | `/academies/{n}/admin` | `ui/pages/academies/[name]/admin/index.vue` | Yes | Admin entry layout and index |
| `pending-status` | สถานะการสมัคร | `/academies/{n}` | `ui/pages/academies/[name].vue` | Yes | In-page banner status |
