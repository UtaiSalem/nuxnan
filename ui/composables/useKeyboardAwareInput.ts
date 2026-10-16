import { onBeforeUnmount, watch, type Ref } from 'vue'

/**
 * useKeyboardAwareInput
 * ทำให้ <input> ที่กำลังโฟกัสพ้นจากแป้นพิมพ์มือถือเสมอ โดย **เลื่อนน้อยที่สุดเท่าที่จำเป็น**
 *
 * ปัญหาที่แก้: ในเกม/ฟอร์มที่ input ถูกจัดกลางกล่องสูง ๆ พอแป้นพิมพ์มือถือเด้งขึ้นมา
 * (iOS Safari / Android Chrome ค่า default) layout viewport ไม่หด ช่องกรอกกับปุ่มส่ง
 * จึงจมอยู่ใต้แป้นพิมพ์ ผู้เล่นมองไม่เห็นสิ่งที่พิมพ์
 *
 * จงใจ "ไม่จัดกึ่งกลาง" (ไม่ใช้ scrollIntoView block:'center') เพราะโจทย์/คำถามมักอยู่
 * เหนือช่องกรอก — ถ้าเลื่อนขึ้นมากเกินไปโจทย์จะหลุดขอบบนจอ วิธีนี้เลื่อนแค่พอให้ขอบล่าง
 * ของ input พ้นแป้นพิมพ์ (เว้นระยะ margin) จึงเก็บพื้นที่เหนือ input ไว้ให้โจทย์มากที่สุด
 *
 * ใช้กับ input ที่อยู่ใน document flow ปกติ (หน้าเลื่อน window ได้ เช่น layout main) —
 * ต่างจาก useVisualViewport ที่คืน overlayStyle สำหรับกล่อง position:fixed
 *
 * การใช้งาน:
 *   const inputRef = ref<HTMLInputElement | null>(null)
 *   useKeyboardAwareInput(inputRef)
 *   // <input ref="inputRef" ... />
 */
export function useKeyboardAwareInput(
    target: Ref<HTMLElement | null>,
    options: { margin?: number } = {}
) {
    const margin = options.margin ?? 16
    let bound: HTMLElement | null = null
    let vv: VisualViewport | null = null
    let timer: ReturnType<typeof setTimeout> | null = null

    const clearTimer = () => {
        if (timer) {
            clearTimeout(timer)
            timer = null
        }
    }

    /**
     * เลื่อนน้อยที่สุดให้ช่องกรอกอยู่ในพื้นที่มองเห็น (เหนือแป้นพิมพ์)
     * - ถ้า input จมใต้แป้น → เลื่อนขึ้นแค่พอให้ขอบล่าง input พ้นแป้น + margin
     * - ถ้า input หลุดขอบบน (กรณีหายาก) → เลื่อนลงแค่พอให้ขอบบน input โผล่
     * ไม่ทำอะไรถ้า input อยู่ในพื้นที่มองเห็นอยู่แล้ว (กันไม่ให้โจทย์ด้านบนขยับเล่น ๆ)
     */
    const ensureVisible = () => {
        if (!bound || !import.meta.client) return
        const rect = bound.getBoundingClientRect()
        const vp = window.visualViewport
        // กรอบพื้นที่มองเห็นในพิกัดเดียวกับ getBoundingClientRect (อิง layout viewport)
        const visibleTop = vp ? vp.offsetTop : 0
        const visibleBottom = vp ? vp.offsetTop + vp.height : window.innerHeight

        let delta = 0
        if (rect.bottom > visibleBottom - margin) {
            // จมใต้แป้นพิมพ์ — เลื่อนขึ้นเท่าที่จำเป็นเท่านั้น
            delta = rect.bottom - (visibleBottom - margin)
            // แต่ห้ามเลื่อนจนขอบบนของ input หลุดพ้นขอบบนพื้นที่มองเห็น
            const maxDelta = rect.top - (visibleTop + margin)
            if (maxDelta > 0) delta = Math.min(delta, maxDelta)
            else delta = 0
        } else if (rect.top < visibleTop + margin) {
            // หลุดขอบบน — เลื่อนลงเล็กน้อย (ค่าติดลบ = เลื่อนขึ้นของหน้า)
            delta = rect.top - (visibleTop + margin)
        }

        if (Math.abs(delta) > 1) {
            window.scrollBy({ top: delta, behavior: 'smooth' })
        }
    }

    // แป้นพิมพ์ใช้เวลาเด้ง/หด และยิง visualViewport.resize หลายครั้ง
    // รวบให้เลื่อนครั้งเดียวหลังค่าเริ่มนิ่ง
    const onViewportChange = () => {
        clearTimer()
        timer = setTimeout(ensureVisible, 80)
    }

    const attachViewport = () => {
        if (vv || !import.meta.client) return
        vv = window.visualViewport ?? null
        vv?.addEventListener('resize', onViewportChange)
    }

    const detachViewport = () => {
        vv?.removeEventListener('resize', onViewportChange)
        vv = null
        clearTimer()
    }

    const onFocus = () => {
        attachViewport()
        // เผื่อ element ที่ autofocus หรือแป้นเด้งทันที (รอแป้นเด้งจนสุดก่อน)
        clearTimer()
        timer = setTimeout(ensureVisible, 300)
    }

    const onBlur = () => {
        detachViewport()
    }

    const bind = (node: HTMLElement | null) => {
        if (bound === node) return
        if (bound) {
            bound.removeEventListener('focus', onFocus)
            bound.removeEventListener('blur', onBlur)
        }
        bound = node
        if (bound) {
            bound.addEventListener('focus', onFocus)
            bound.addEventListener('blur', onBlur)
            // ถ้าโฟกัสอยู่แล้วตอนผูก (กรณี autofocus) ให้เริ่มเลยเลย
            if (import.meta.client && document.activeElement === bound) onFocus()
        }
    }

    watch(target, (el) => bind(el), { immediate: true, flush: 'post' })

    onBeforeUnmount(() => {
        detachViewport()
        bind(null)
    })
}
