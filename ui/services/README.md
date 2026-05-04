# Service Layer

โฟลเดอร์นี้เก็บ **service module** ที่ห่อการเรียก API ของ backend ไว้เป็นฟังก์ชันที่นำกลับมาใช้ซ้ำได้ จุดประสงค์คือ แยกเลเยอร์ "เรียก API" ออกจาก "ตรรกะของ UI / state reactivity" เพื่อให้แก้ไข endpoint หรือรูปแบบ response ในจุดเดียว

---

## แนวทาง (Convention)

### 1. Service = ฟังก์ชัน API ล้วน (ไม่มี Vue reactivity)

ทุกเมธอดของ service ใช้ `useApi()` (จาก `composables/useApi.ts`) เพื่อให้ได้:

- แนบ `Authorization: Bearer <token>` อัตโนมัติ
- Retry + exponential backoff
- จัดการ 401 + refresh token
- รองรับ FormData โดยอัตโนมัติ

ตัวอย่าง:

```js
// services/exampleService.js
export const exampleService = {
  async getItem(id) {
    const api = useApi()
    return api.get(`/api/items/${id}`)
  },
  async createItem(data) {
    const api = useApi()
    return api.post('/api/items', data)
  },
}
```

**ต้องเรียก `useApi()` ภายในแต่ละเมธอด** เพราะ `useApi()` ต้องใช้ Nuxt setup context ไม่สามารถเรียกที่ระดับ module ได้

### 2. Composable = state + เรียก service

Composable (`composables/useXxx.ts`) ห่อ service ด้วย Vue reactivity:

- `ref` / `computed` สำหรับ state (isLoading, error, data)
- จัดการ optimistic update
- เปลี่ยน response ให้ตรงกับที่ component ใช้งานง่าย

```ts
// composables/useExample.ts
export const useExample = () => {
  const items = ref([])
  const isLoading = ref(false)
  const error = ref(null)

  const loadItems = async () => {
    isLoading.value = true
    try {
      const res = await exampleService.getItems()
      items.value = res.data || []
    } catch (err) {
      error.value = err.message
    } finally {
      isLoading.value = false
    }
  }

  return { items, isLoading, error, loadItems }
}
```

### 3. Import ผ่าน barrel

ใช้ `import { exampleService } from '@/services'` ไม่ใช่ import ตรงจากไฟล์

---

## Services ที่มีอยู่

- `courseService` — จัดการ courses, members, groups
- `friendService` — จัดการ friends + suggestions + search

## Composables ตัวอย่างที่ใช้ service

- `useFriendSearch` — ห่อ `friendService.searchFriends` ด้วย debounce + state

---

## แนวปฏิบัติเพิ่มเติม

- Service ไม่ควรเรียก `console.error` หรือ show toast — ให้ composable / component จัดการ error
- ชื่อเมธอด: `get*`, `create*`, `update*`, `delete*`, ตามรูปแบบของ REST
- ถ้า endpoint ต้อง FormData ให้ service รับ `File` หรือ object แล้วประกอบ FormData ภายใน เพื่อให้ caller เรียบง่าย
- หลีกเลี่ยงการใช้ `axios` โดยตรง — ใช้ `useApi()` เท่านั้น เพื่อให้ได้ auth + retry + refresh
