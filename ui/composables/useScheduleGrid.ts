import { computed, unref } from 'vue'
import type { Ref } from 'vue'

type MaybeRef<T> = T | Ref<T>

export const SCHEDULE_DAY_LABELS: Record<number, { label: string; short: string }> = {
  1: { label: 'จันทร์', short: 'จ' },
  2: { label: 'อังคาร', short: 'อ' },
  3: { label: 'พุธ', short: 'พ' },
  4: { label: 'พฤหัสบดี', short: 'พฤ' },
  5: { label: 'ศุกร์', short: 'ศ' },
  6: { label: 'เสาร์', short: 'ส' },
  7: { label: 'อาทิตย์', short: 'อา' },
}

/** ใช้กับ dropdown "วัน" ในฟอร์ม — API รับ 1–7 เสมอ ไม่ผูกกับวันที่โรงเรียนเปิดสอน */
export const ALL_SCHEDULE_DAYS = Object.keys(SCHEDULE_DAY_LABELS).map((key) => ({
  value: Number(key),
  ...SCHEDULE_DAY_LABELS[Number(key)],
}))

export const toMinutes = (time: string) => {
  const [hour, minute] = String(time || '').split(':')

  return (Number(hour) || 0) * 60 + (Number(minute) || 0)
}

export const overlapMinutes = (aStart: string, aEnd: string, bStart: string, bEnd: string) =>
  Math.min(toMinutes(aEnd), toMinutes(bEnd)) - Math.max(toMinutes(aStart), toMinutes(bStart))

/**
 * กริดตารางเรียนที่วาดจาก "คาบจริง" ของโรงเรียน (SC-S6)
 *
 * - แถว = ช่วงเวลาที่ไม่ซ้ำกันของคาบในชุดที่ใช้ได้ + ช่วงของคาบสอนที่ไม่ตรงชุดไหนเลย ("นอกโครงคาบ")
 * - คาบสอนหนึ่งคาบอยู่ได้แถวเดียว: เลือกแถวที่ซ้อนทับมากที่สุด และแถวของชุดที่ใช้กับ "วันนั้น" ชนะเสมอ
 * - วัน = วันที่ชุดกำหนด + วันที่มีคาบอยู่จริง (กันคาบวันเสาร์หายจากจอ)
 */
export function useScheduleGrid(options: {
  periodSets: MaybeRef<any[]>
  timetable: MaybeRef<any[]>
  gradeLevel?: MaybeRef<string | null>
}) {
  const periodSets = computed<any[]>(() => unref(options.periodSets) || [])
  const timetable = computed<any[]>(() => unref(options.timetable) || [])
  const gradeLevel = computed<string | null>(() => (options.gradeLevel ? unref(options.gradeLevel) ?? null : null))

  const applicableSets = computed(() => {
    const grade = gradeLevel.value
    if (!grade) return periodSets.value

    return periodSets.value.filter((set: any) => !set.grade_levels?.length || set.grade_levels.includes(grade))
  })

  const gridDays = computed(() => {
    const days = new Set<number>()
    let hasUnrestricted = false

    for (const set of applicableSets.value) {
      if (!set.days?.length) hasUnrestricted = true
      else set.days.forEach((day: any) => days.add(Number(day)))
    }

    if (hasUnrestricted || applicableSets.value.length === 0) {
      [1, 2, 3, 4, 5].forEach((day) => days.add(day))
    }

    timetable.value.forEach((day: any) => {
      if ((day.schedules || []).length > 0) days.add(Number(day.day))
    })

    return [...days].sort((a, b) => a - b).map((value) => ({ value, ...SCHEDULE_DAY_LABELS[value] }))
  })

  const gridRows = computed(() => {
    const rows: any[] = []
    const seen = new Set<string>()

    for (const set of applicableSets.value) {
      for (const period of set.periods || []) {
        const key = `${period.start_time}-${period.end_time}`
        if (seen.has(key)) continue
        seen.add(key)
        rows.push({
          key,
          start: period.start_time,
          end: period.end_time,
          label: period.name,
          type: period.period_type || 'class',
          periodId: period.id,
          days: set.days?.length ? set.days.map((day: any) => Number(day)) : null,
        })
      }
    }

    for (const day of timetable.value) {
      for (const schedule of day.schedules || []) {
        const fits = rows.some((row) => overlapMinutes(row.start, row.end, schedule.start_time, schedule.end_time) > 0)
        if (fits) continue
        const key = `${schedule.start_time}-${schedule.end_time}`
        if (seen.has(key)) continue
        seen.add(key)
        rows.push({
          key,
          start: schedule.start_time,
          end: schedule.end_time,
          label: 'นอกโครงคาบ',
          type: 'outside',
          periodId: null,
          days: null,
        })
      }
    }

    return rows.sort((a, b) => toMinutes(a.start) - toMinutes(b.start) || toMinutes(a.end) - toMinutes(b.end))
  })

  const scheduleRowMap = computed(() => {
    const map: Record<string, any[]> = {}

    for (const day of timetable.value) {
      for (const schedule of day.schedules || []) {
        let best: any = null
        let bestScore = 0

        for (const row of gridRows.value) {
          const overlap = overlapMinutes(row.start, row.end, schedule.start_time, schedule.end_time)
          if (overlap <= 0) continue
          const appliesToDay = !row.days || row.days.includes(Number(day.day))
          const score = overlap + (appliesToDay ? 10000 : 0)
          if (score > bestScore) {
            bestScore = score
            best = row
          }
        }

        if (!best) continue
        const cellKey = `${day.day}-${best.key}`
        ;(map[cellKey] ||= []).push(schedule)
      }
    }

    return map
  })

  const getScheduleCell = (dayValue: number, row: any) => scheduleRowMap.value[`${dayValue}-${row.key}`] || []

  /** id ของคาบในชุดที่เวลาตรงกันเป๊ะ (ไว้ผูก period_id ตอนบันทึก) */
  const matchedPeriodId = (start: string, end: string) => {
    for (const set of applicableSets.value) {
      for (const period of set.periods || []) {
        if (period.start_time === start && period.end_time === end) return period.id
      }
    }

    return null
  }

  return { applicableSets, gridDays, gridRows, getScheduleCell, matchedPeriodId }
}
