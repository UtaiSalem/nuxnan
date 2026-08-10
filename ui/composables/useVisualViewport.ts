import { computed, onBeforeUnmount, onMounted, ref, toValue, watch, type MaybeRefOrGetter } from 'vue'

/**
 * ติดตาม "พื้นที่ที่ผู้ใช้มองเห็นจริง" (visual viewport) บนมือถือ
 *
 * ปัญหาที่แก้: `position: fixed; inset: 0` อิงกับ layout viewport ซึ่ง **ไม่หด**
 * เมื่อแป้นพิมพ์มือถือเด้งขึ้นมา (ทั้ง iOS Safari และ Android Chrome ค่า default)
 * ผลคือ modal ที่จัดกึ่งกลางแนวตั้งจะถูกคำนวณจากความสูงเต็มจอ แล้วครึ่งล่าง
 * ของฟอร์มไปจมอยู่ใต้แป้นพิมพ์ — ผู้ใช้พิมพ์ต่อไม่ได้
 *
 * `window.visualViewport` รายงานความสูงที่เหลือหลังหักแป้นพิมพ์ และ `offsetTop`
 * บอกว่าพื้นที่นั้นเลื่อนลงจากขอบบนของ layout viewport เท่าไร นำสองค่านี้ไป
 * กำหนดกรอบของ overlay แทน `inset-0` จะได้กรอบที่พอดีกับพื้นที่เหนือแป้นพิมพ์เสมอ
 *
 * @param enabled ผูก listener เฉพาะตอนที่ต้องใช้ (เช่นตอน modal เปิด) เพื่อไม่ให้
 *                ทุก card ในลิสต์เสียบ listener ค้างไว้พร้อมกัน
 */
export function useVisualViewport(enabled: MaybeRefOrGetter<boolean> = true) {
    const height = ref<number | null>(null)
    const offsetTop = ref(0)
    const isActive = computed(() => toValue(enabled))

    let vv: VisualViewport | null = null

    const sync = () => {
        if (!vv) return
        height.value = vv.height
        // ค่า offsetTop ของบางเบราว์เซอร์ติดลบเล็กน้อยระหว่าง rubber-band scroll
        offsetTop.value = Math.max(0, vv.offsetTop)
    }

    const attach = () => {
        if (vv || !import.meta.client) return
        vv = window.visualViewport ?? null
        if (!vv) return
        vv.addEventListener('resize', sync)
        vv.addEventListener('scroll', sync)
        // เผื่อกรณีที่ visualViewport ไม่ยิง resize เอง เช่นเบราว์เซอร์ที่ตั้ง
        // `interactive-widget=resizes-content` (แป้นพิมพ์ไปหด layout viewport แทน)
        // หรือตอนหมุนจอ — สองตัวนี้ยิง window resize เสมอ
        window.addEventListener('resize', sync)
        window.addEventListener('orientationchange', sync)
        sync()
    }

    const detach = () => {
        if (!vv) return
        vv.removeEventListener('resize', sync)
        vv.removeEventListener('scroll', sync)
        window.removeEventListener('resize', sync)
        window.removeEventListener('orientationchange', sync)
        vv = null
        height.value = null
        offsetTop.value = 0
    }

    watch(isActive, (on) => (on ? attach() : detach()), { immediate: true })

    // ค่าตอน setup อาจได้มาก่อน layout นิ่ง — วัดซ้ำอีกครั้งหลัง mount
    onMounted(sync)
    onBeforeUnmount(detach)

    /**
     * สไตล์สำหรับกล่อง `position: fixed` ที่ต้องอยู่ในพื้นที่มองเห็นเท่านั้น
     * เบราว์เซอร์ที่ไม่มี VisualViewport API จะ fallback ไป 100dvh ซึ่งยังดีกว่า 100vh
     */
    const overlayStyle = computed(() => ({
        top: `${offsetTop.value}px`,
        height: height.value !== null ? `${height.value}px` : '100dvh',
    }))

    return { height, offsetTop, overlayStyle }
}
