export interface GroupedCourseSection {
  key: string
  label: string
  courses: any[]
}

const EDUCATION_LEVEL_ORDER: Record<string, number> = {
  'ประถมศึกษา': 1,
  'มัธยมศึกษา': 2,
  'ปวช.': 3,
  'ปวส.': 4,
  'อุดมศึกษา': 5,
  'อื่นๆ': 6,
  unspecified: 99,
}

const buildGroupLabel = (educationLevel: string, educationYear: string) => {
  if (educationLevel === 'unspecified') {
    return 'ยังไม่ระบุกลุ่มนักเรียน'
  }

  if (!educationYear || educationYear === 'unspecified') {
    return educationLevel
  }

  return `${educationLevel} ปี ${educationYear}`
}

const compareGroupedSections = (a: GroupedCourseSection, b: GroupedCourseSection) => {
  const [levelA = 'unspecified', yearA = 'unspecified'] = a.key.split('|')
  const [levelB = 'unspecified', yearB = 'unspecified'] = b.key.split('|')

  const levelOrderA = EDUCATION_LEVEL_ORDER[levelA] ?? 90
  const levelOrderB = EDUCATION_LEVEL_ORDER[levelB] ?? 90

  if (levelOrderA !== levelOrderB) {
    return levelOrderA - levelOrderB
  }

  if (yearA === 'unspecified') return 1
  if (yearB === 'unspecified') return -1

  return Number(yearA) - Number(yearB)
}

export function useCourseGrouping() {
  const groupCourses = (courses: any[]): GroupedCourseSection[] => {
    const groups = new Map<string, GroupedCourseSection>()

    for (const course of courses) {
      const educationLevel = course?.education_level || 'unspecified'
      const educationYear = course?.education_year != null ? String(course.education_year) : 'unspecified'
      const key = `${educationLevel}|${educationYear}`

      if (!groups.has(key)) {
        groups.set(key, {
          key,
          label: buildGroupLabel(educationLevel, educationYear),
          courses: [],
        })
      }

      groups.get(key)?.courses.push(course)
    }

    return [...groups.values()].sort(compareGroupedSections)
  }

  return { groupCourses }
}
