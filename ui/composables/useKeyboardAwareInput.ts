import { onBeforeUnmount, watch, type Ref } from 'vue'

/**
 * useKeyboardAwareInput
 * เลื่อน <input> ที่กำลังโฟกัสให้ลอยขึ้นเหนือแป้นพิมพ์มือถือเสมอ
 *
 * ปัญหาที่แก้: ในเกม/ฟอร์มที่ input ถูกจัดกลางกล่องสูง ๆ พอแป้นพิมพ์มือถือเด้งขึ้นมา
 * (iOS Safari / Android Chrome ค่า default) layout viewport ไม่หด ช่องกรอกกับปุ่มส่ง
 * จึงจมอยู่ใต้แป้นพิมพ์ ผู้เล่นมองไม่เห็นสิ่งที่พิมพ์
 *
 * ใช้กับ input ที่อยู่ใน document flow ปกติ (หน้าเลื่อนได้) — ต่างจาก useVisualViewport
 * ที่คืน overlayStyle สำหรับกล่อง position:fixed
 *
 * การใช้งาน:
 *   const inputRef = ref<HTMLInputElement | null>(null)
 *   useKeyboardAwareInput(inputRef)
 *   // <input ref="inputRef" ... />
 */
export function useKeyboardAwareInput(
    target: Ref<HTMLElement | null>,
    options: { block?: ScrollLogicalPosition } = {}
) {
    const block = options.block ?? 'center'
    let bound: HTMLElement | null = null
    let vv: VisualViewport | null = null
    let timer: ReturnType<typeof setTimeout> | null = null

    const clearTimer = () => {
        if (timer) {
            clearTimeout(timer)
            timer = null
        }
    }

    const scrollIntoView = () => {
        if (!bound) return
        try {
            bound.scrollIntoView({ block, behavior: 'smooth' })
        } catch {
            bound.scrollIntoView()
        }
    }

    // แป้นพิมพ์ใช้เวลาเด้ง/หด และยิง visualViewport.resize หลายครั้ง
    // รวบให้เลื่อนครั้งเดียวหลังค่าเริ่มนิ่ง
    const onViewportChange = () => {
        clearTimer()
        timer = setTimeout(scrollIntoView, 60)
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
        // เผื่อ element ที่ autofocus หรือแป้นเด้งทันที
        clearTimer()
        timer = setTimeout(scrollIntoView, 300)
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
            // ถ้าโฟกัสอยู่แล้วตอนผูก (กรณี autofocus) ให้เริ่มเลื่อนเลย
            if (import.meta.client && document.activeElement === bound) onFocus()
        }
    }

    watch(target, (el) => bind(el), { immediate: true, flush: 'post' })

    onBeforeUnmount(() => {
        detachViewport()
        bind(null)
    })
}
