import { shouldHydrate } from 'pinia'

/**
 * หน้า error ของ Nuxt เคยพังเป็น 500 แทนที่จะเป็น 404
 *
 * Nuxt สร้าง object ของ error จาก `getQuery(event)` ของ h3 ซึ่งเป็น object ที่ไม่มี
 * prototype (`Object.create(null)`) พอ object นั้นเข้าไปอยู่ใน payload
 * reducer ของ @pinia/nuxt จะเรียก `shouldHydrate()` ที่ข้างในใช้
 * `obj.hasOwnProperty(...)` ตรง ๆ ⇒ TypeError: obj.hasOwnProperty is not a function
 *
 * plugin นี้ลงทะเบียน reducer ชื่อเดียวกันทับของ pinia (user plugin รันหลัง plugin
 * ของ module) โดยข้าม object ที่ไม่มี prototype แล้วปล่อยที่เหลือให้ pinia ตัดสินเหมือนเดิม
 * ถ้าวันหนึ่ง pinia แก้ `shouldHydrate` ให้ปลอดภัยแล้ว ลบไฟล์นี้ทิ้งได้เลย
 */
export default definePayloadPlugin(() => {
  definePayloadReducer('skipHydrate', (data: unknown) => {
    if (typeof data === 'object' && data !== null && Object.getPrototypeOf(data) === null) {
      return false
    }

    return !shouldHydrate(data as any) && 1
  })
})
