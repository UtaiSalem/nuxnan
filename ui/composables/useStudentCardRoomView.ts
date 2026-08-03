import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount, type Ref } from 'vue'

export type StudentCardSortKey = 'student_number' | 'order_no' | 'name'

export const studentCardSortOptions: { key: StudentCardSortKey; label: string }[] = [
    { key: 'student_number', label: 'เลขประจำตัว' },
    { key: 'order_no', label: 'เลขที่' },
    { key: 'name', label: 'ชื่อ-สกุล' },
]

/**
 * ตรรกะของ "หน้าดูบัตรทั้งห้อง" ที่ใช้ร่วมกันระหว่างหน้าชั่วคราว /student-card
 * กับหน้าจัดการบัตรของโรงเรียน — ค้นหา เรียงลำดับ แถบกระโดดไปบัตร และ
 * รายชื่อด้านซ้ายที่เลื่อนตามบัตรที่กำลังดู
 *
 * เคสขอบที่เคยแก้มาแล้วและห้ามหายไปตอนย้ายโค้ด:
 *  - เลขประจำตัวเก็บเป็นสตริง ต้องเรียงแบบ numeric ไม่งั้น "10" มาก่อน "9"
 *  - คนที่ยังไม่มีเลขที่ต้องไปอยู่ท้ายรายการเสมอ ไม่ใช่ถูกมองเป็น 0
 *  - เรียงตามชื่อต้องตัดคำนำหน้าออกก่อน ไม่งั้น "เด็กชาย" ทั้งหมดจะกองอยู่ด้วยกัน
 *  - บางเบราว์เซอร์ (หรือโหมดลดการเคลื่อนไหว) ไม่ทำ smooth scroll ให้ ต้องมีทางถอย
 */
export function useStudentCardRoomView(students: Ref<any[]>, options: { navOffset?: number } = {}) {
    const NAV_OFFSET = options.navOffset ?? 96

    const searchTerm = ref('')
    const sortKey = ref<StudentCardSortKey>('student_number')
    const currentIndex = ref(0)
    const railRef = ref<HTMLElement | null>(null)

    // ชื่อจริง-นามสกุลแบบตัดคำนำหน้าทิ้ง ใช้ทั้งตอนเรียงและตอนแสดงในรายชื่อด้านซ้าย
    // ซึ่งคอลัมน์แคบจนคำนำหน้ากินที่เปล่า ๆ
    const studentSortName = (s: any) => {
        const parts = [s?.first_name_thai, s?.last_name_thai].filter(Boolean).join(' ').trim()
        if (parts) return parts
        const title = s?.title_name?.trim()
        const full = (s?.full_name_thai || '').trim()

        return title && full.startsWith(title) ? full.slice(title.length).trim() : full
    }

    const filteredStudents = computed(() => {
        if (!searchTerm.value) return students.value
        const term = searchTerm.value.toLowerCase()

        return students.value.filter(s =>
            (s.full_name_thai && s.full_name_thai.toLowerCase().includes(term)) ||
            (s.first_name_thai && s.first_name_thai.toLowerCase().includes(term)) ||
            (s.student_number && s.student_number.toString().includes(term))
        )
    })

    const sortedStudents = computed(() => {
        const list = [...filteredStudents.value]

        if (sortKey.value === 'name') {
            return list.sort((a, b) => studentSortName(a).localeCompare(studentSortName(b), 'th'))
        }

        if (sortKey.value === 'order_no') {
            return list.sort((a, b) => {
                const na = Number(a.order_no)
                const nb = Number(b.order_no)
                const aMissing = !Number.isFinite(na)
                const bMissing = !Number.isFinite(nb)
                if (aMissing || bMissing) return aMissing && bMissing ? 0 : aMissing ? 1 : -1

                return na - nb
            })
        }

        return list.sort((a, b) =>
            String(a.student_number || '').localeCompare(String(b.student_number || ''), 'th', { numeric: true }))
    })

    const studentLabel = (s: any, index: number) => {
        const name = s?.full_name_thai
            || [s?.title_name, s?.first_name_thai, s?.last_name_thai].filter(Boolean).join(' ')
            || 'ไม่ระบุชื่อ'
        const orderNo = s?.order_no ? `เลขที่ ${s.order_no}` : `ลำดับ ${index + 1}`

        return `${orderNo} · ${s?.student_number || '-'} · ${name}`
    }

    const scrollToIndex = (index: number) => {
        const student = sortedStudents.value[index]
        if (!student) return
        currentIndex.value = index

        const el = document.getElementById(`card-${student.id}`)
        if (!el) return

        const before = window.scrollY
        el.scrollIntoView({ block: 'start', behavior: 'smooth' })

        // ถ้าจอไม่ขยับภายในเวลาสั้น ๆ ให้กระโดดแบบทันทีแทน ปุ่มนำทางจะได้ไม่ด้าน
        window.setTimeout(() => {
            if (Math.abs(window.scrollY - before) < 2) el.scrollIntoView({ block: 'start' })
        }, 300)
    }

    const stepCard = (delta: number) => {
        const next = currentIndex.value + delta
        if (next < 0 || next >= sortedStudents.value.length) return
        scrollToIndex(next)
    }

    // ตั้ง scrollTop เองแทน scrollIntoView เพราะ scrollIntoView จะลาก viewport หลักไปด้วย
    const syncRailScroll = () => {
        const rail = railRef.value
        const student = sortedStudents.value[currentIndex.value]
        if (!rail || !student) return

        const row = document.getElementById(`row-${student.id}`)
        if (!row) return

        const top = row.offsetTop
        const bottom = top + row.offsetHeight
        if (top < rail.scrollTop) rail.scrollTop = top - 8
        else if (bottom > rail.scrollTop + rail.clientHeight) rail.scrollTop = bottom - rail.clientHeight + 8
    }

    watch(currentIndex, syncRailScroll)

    // ใบที่กำลังดูอยู่ = ใบสุดท้ายที่ขอบบนเลื่อนพ้นแถบนำทางไปแล้ว
    // ใช้ scroll listener แทน IntersectionObserver เพื่อให้ทำงานเหมือนกันทุกเบราว์เซอร์
    const syncCurrentIndexToScroll = () => {
        const list = sortedStudents.value
        if (!list.length) return

        let index = 0
        for (let i = 0; i < list.length; i++) {
            const el = document.getElementById(`card-${list[i].id}`)
            if (!el) continue
            if (el.getBoundingClientRect().top - NAV_OFFSET > 0) break
            index = i
        }
        currentIndex.value = index
    }

    let scrollTicking = false
    const handleScroll = () => {
        if (scrollTicking) return
        scrollTicking = true
        window.requestAnimationFrame(() => {
            scrollTicking = false
            syncCurrentIndexToScroll()
        })
    }

    watch(sortedStudents, async (list) => {
        if (currentIndex.value > list.length - 1) currentIndex.value = Math.max(0, list.length - 1)
        await nextTick()
        syncCurrentIndexToScroll()
    })

    onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }))
    onBeforeUnmount(() => window.removeEventListener('scroll', handleScroll))

    return {
        searchTerm,
        sortKey,
        sortOptions: studentCardSortOptions,
        currentIndex,
        railRef,
        filteredStudents,
        sortedStudents,
        studentSortName,
        studentLabel,
        scrollToIndex,
        stepCard,
    }
}
