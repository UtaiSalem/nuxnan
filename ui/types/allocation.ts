/** ตัวเลือกตัวกรองรายวิชา พร้อมจำนวนคอร์สในแต่ละตัวเลือก (มาจาก available_filters ของ API) */
export interface FilterOption {
  value: string
  label: string
  count: number
}

/** ชุดตัวเลือกตัวกรองที่ API ส่งกลับมาพร้อมรายการคอร์ส */
export interface AvailableFilters {
  education_levels?: FilterOption[]
  education_years?: FilterOption[]
  semesters?: FilterOption[]
  academic_years?: FilterOption[]
  current_term?: { academic_year?: string | null; semester?: string | null } | null
}

/** สถานะตัวกรองรายวิชาในหน้าจัดสรรแต้ม */
export interface CourseFilters {
  education_level: string
  education_year: string
  semester: string
  academic_year: string
  search: string
}
