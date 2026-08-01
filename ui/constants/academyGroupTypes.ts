/**
 * Academy group type metadata — single source of truth (frontend side).
 * Mirror of backend constant (to be added in Phase G.1).
 *
 * Used to render group cards, sections, badges, and dropdowns
 * in the school homepage "ส่วนงาน" tab and group profile pages.
 */

export interface AcademyGroupTypeMeta {
  /** Stored value in academy_groups.type */
  key: string
  /** Thai display label */
  label: string
  /** English label (fallback / a11y) */
  labelEn: string
  /** Iconify icon name */
  icon: string
  /** Tailwind color tone — used for badge bg/text + gradient header */
  color: 'purple' | 'cyan' | 'green' | 'orange' | 'pink' | 'amber' | 'teal' | 'gray'
  /** Display sort order within "ส่วนงาน" tab */
  order: number
}

export const ACADEMY_GROUP_TYPES: AcademyGroupTypeMeta[] = [
  { key: 'office',         label: 'สำนัก',         labelEn: 'Office',         icon: 'heroicons:building-office',           color: 'purple', order: 1 },
  { key: 'department',     label: 'ฝ่าย',          labelEn: 'Department',     icon: 'heroicons:briefcase',                 color: 'cyan',   order: 2 },
  { key: 'section',        label: 'งาน',           labelEn: 'Section',        icon: 'heroicons:clipboard-document-list',   color: 'green',  order: 3 },
  { key: 'academic_group', label: 'กลุ่มสาระ',     labelEn: 'Academic Group', icon: 'heroicons:book-open',                 color: 'orange', order: 4 },
  { key: 'classroom',      label: 'ห้องเรียน',     labelEn: 'Classroom',      icon: 'heroicons:academic-cap',              color: 'cyan',   order: 5 },
  { key: 'club',           label: 'ชมรม',          labelEn: 'Club',           icon: 'heroicons:trophy',                    color: 'pink',   order: 6 },
  { key: 'committee',      label: 'คณะกรรมการ',    labelEn: 'Committee',      icon: 'heroicons:user-group',                color: 'amber',  order: 7 },
  { key: 'dormitory',      label: 'หอพัก',         labelEn: 'Dormitory',      icon: 'heroicons:home-modern',               color: 'teal',   order: 8 },
  { key: 'house',          label: 'คณะสี',                 labelEn: 'House',          icon: 'heroicons:flag',                       color: 'purple', order: 9 },
]

const UNKNOWN_TYPE: AcademyGroupTypeMeta = {
  key: 'unknown',
  label: 'ไม่ระบุประเภท',
  labelEn: 'Other',
  icon: 'heroicons:squares-2x2',
  color: 'gray',
  order: 999,
}

const TYPE_MAP: Record<string, AcademyGroupTypeMeta> = Object.fromEntries(
  ACADEMY_GROUP_TYPES.map((t) => [t.key, t]),
)

/**
 * Lookup type metadata by key. Returns a safe fallback for unknown types.
 */
export const getAcademyGroupTypeMeta = (type: string | null | undefined): AcademyGroupTypeMeta => {
  if (!type) return UNKNOWN_TYPE
  return TYPE_MAP[type] || UNKNOWN_TYPE
}

/**
 * Tailwind class fragments per color — used for badges, icon containers, gradients.
 * Kept here so Tailwind JIT can detect all classes at build time
 * (don't construct class strings dynamically elsewhere).
 */
export const GROUP_TYPE_COLOR_CLASSES: Record<AcademyGroupTypeMeta['color'], {
  badge: string
  iconBg: string
  gradient: string
  text: string
}> = {
  purple: {
    badge:    'bg-vikinger-purple/15 text-vikinger-purple',
    iconBg:   'bg-vikinger-purple/15 text-vikinger-purple',
    gradient: 'from-vikinger-purple to-purple-400',
    text:     'text-vikinger-purple',
  },
  cyan: {
    badge:    'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300',
    iconBg:   'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300',
    gradient: 'from-vikinger-cyan to-cyan-400',
    text:     'text-cyan-700 dark:text-cyan-300',
  },
  green: {
    badge:    'bg-green-500/15 text-green-700 dark:text-green-300',
    iconBg:   'bg-green-500/15 text-green-700 dark:text-green-300',
    gradient: 'from-green-500 to-emerald-400',
    text:     'text-green-700 dark:text-green-300',
  },
  orange: {
    badge:    'bg-orange-500/15 text-orange-700 dark:text-orange-300',
    iconBg:   'bg-orange-500/15 text-orange-700 dark:text-orange-300',
    gradient: 'from-orange-500 to-amber-400',
    text:     'text-orange-700 dark:text-orange-300',
  },
  pink: {
    badge:    'bg-pink-500/15 text-pink-700 dark:text-pink-300',
    iconBg:   'bg-pink-500/15 text-pink-700 dark:text-pink-300',
    gradient: 'from-pink-500 to-rose-400',
    text:     'text-pink-700 dark:text-pink-300',
  },
  amber: {
    badge:    'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    iconBg:   'bg-amber-500/15 text-amber-700 dark:text-amber-300',
    gradient: 'from-amber-500 to-yellow-400',
    text:     'text-amber-700 dark:text-amber-300',
  },
  teal: {
    badge:    'bg-teal-500/15 text-teal-700 dark:text-teal-300',
    iconBg:   'bg-teal-500/15 text-teal-700 dark:text-teal-300',
    gradient: 'from-teal-500 to-cyan-400',
    text:     'text-teal-700 dark:text-teal-300',
  },
  gray: {
    badge:    'bg-gray-200/70 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    iconBg:   'bg-gray-200/70 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    gradient: 'from-gray-400 to-gray-500',
    text:     'text-gray-700 dark:text-gray-300',
  },
}

/**
 * Group an array of groups by their type and return ordered sections.
 * Unknown types fall into a single trailing "อื่นๆ" section.
 */
export const groupGroupsByType = <T extends { type?: string | null }>(
  groups: T[],
): Array<{ meta: AcademyGroupTypeMeta; items: T[] }> => {
  const buckets = new Map<string, T[]>()
  for (const g of groups) {
    const key = g.type && TYPE_MAP[g.type] ? g.type : UNKNOWN_TYPE.key
    if (!buckets.has(key)) buckets.set(key, [])
    buckets.get(key)!.push(g)
  }
  return Array.from(buckets.entries())
    .map(([key, items]) => ({
      meta: key === UNKNOWN_TYPE.key ? UNKNOWN_TYPE : TYPE_MAP[key],
      items,
    }))
    .sort((a, b) => a.meta.order - b.meta.order)
}
