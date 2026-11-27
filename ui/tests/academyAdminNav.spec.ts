import { readdirSync, statSync } from 'node:fs'
import path from 'node:path'
import { describe, expect, it } from 'vitest'
import {
  ADMIN_SECTIONS,
  adminRelativePath,
  canEnterAcademyAdmin,
  canViewSection,
  findAdminSection,
  hasPermission,
  type PermissionContext,
} from '../utils/academyAdminNav'
import fixtureJson from './fixtures/academyRolePermissions.json'

const fixture = fixtureJson as Record<string, string[]>

type Role = keyof typeof fixtureJson

function ctxFor(role: Role): PermissionContext {
  return {
    isAdmin: role === 'owner',
    can: (k: string) => role === 'owner' || hasPermission(fixture[role], k),
  }
}

function visibleIds(role: Role): string[] {
  const ctx = ctxFor(role)
  return ADMIN_SECTIONS.filter(s => canViewSection(s, ctx)).map(s => s.id)
}

const ids = (list: string) => list.split(',').map(s => s.trim())

describe('academy admin gate', () => {
  it.each(['owner', 'director', 'admin', 'teacher', 'registrar', 'staff', 'finance_staff', 'card_admin'] as Role[])(
    'lets %s enter the admin area',
    role => expect(canEnterAcademyAdmin(ctxFor(role))).toBe(true),
  )

  it.each(['student', 'parent', 'guest'] as Role[])(
    'keeps %s out of the admin area',
    role => expect(canEnterAcademyAdmin(ctxFor(role))).toBe(false),
  )
})

describe('visible sections per role', () => {
  it('teacher', () => {
    expect(visibleIds('teacher')).toEqual(ids(
      'dashboard, members, courses, schedule, schedule-workload, schedule-exceptions, students, student-cards, student-card-requests, school-attendance, home-visits, at-risk, staff, announcements, elections',
    ))
  })

  it('staff', () => {
    expect(visibleIds('staff')).toEqual(ids(
      'dashboard, members, schedule, schedule-workload, schedule-exceptions, students, student-cards, at-risk, elections',
    ))
  })

  it('card_admin', () => {
    expect(visibleIds('card_admin')).toEqual(ids(
      'dashboard, members, students, student-cards, student-card-requests, at-risk',
    ))
  })

  it('finance_staff', () => {
    expect(visibleIds('finance_staff')).toEqual(ids(
      'dashboard, members, students, student-cards, at-risk, elections, revenue, settings',
    ))
  })

  it('registrar', () => {
    expect(visibleIds('registrar')).toEqual(ids(
      'dashboard, members, guardians, schedule, schedule-workload, schedule-exceptions, students, student-cards, at-risk, staff, elections, reports, activity-log',
    ))
  })

  it('director sees everything except allocations, house-assignments, sports-scores, store', () => {
    const excluded = ['allocations', 'house-assignments', 'sports-scores', 'store']
    const expected = ADMIN_SECTIONS.map(s => s.id).filter(id => !excluded.includes(id))
    expect(visibleIds('director')).toEqual(expected)
    expect(expected).toHaveLength(31)
  })

  it('owner sees all 35 sections', () => {
    expect(visibleIds('owner')).toEqual(ADMIN_SECTIONS.map(s => s.id))
    expect(ADMIN_SECTIONS).toHaveLength(35)
  })
})

describe('every page under admin/ maps to a section', () => {
  const adminDir = path.resolve(process.cwd(), 'pages/academies/[name]/admin')

  function walk(dir: string): string[] {
    return readdirSync(dir).flatMap((entry) => {
      const full = path.join(dir, entry)
      if (statSync(full).isDirectory()) return walk(full)
      return entry.endsWith('.vue') ? [full] : []
    })
  }

  function toRelativeRoute(file: string): string {
    return path.relative(adminDir, file)
      .split(path.sep)
      .join('/')
      .replace(/\.vue$/, '')
      .replace(/(^|\/)index$/, '')
      .replace(/\[[^\]]+\]/g, 'x')
  }

  it('finds a section for every admin page', () => {
    const files = walk(adminDir)
    expect(files.length).toBeGreaterThan(0)
    const unmatched = files
      .map(toRelativeRoute)
      .filter(rel => findAdminSection(rel) === null)
    expect(unmatched).toEqual([])
  })
})

describe('findAdminSection', () => {
  it.each([
    ['student-cards/requests/5', 'student-card-requests'],
    ['student-cards/5/edit', 'student-cards'],
    ['schedule-periods', 'schedule-periods'],
    ['schedule', 'schedule'],
    ['', 'dashboard'],
  ])('maps %j to %s', (rel, expected) => {
    expect(findAdminSection(rel)?.id).toBe(expected)
  })

  it('returns null for unknown paths', () => {
    expect(findAdminSection('nope')).toBeNull()
  })
})

describe('adminRelativePath', () => {
  const name = 'โรงเรียนทดสอบ'

  it('decodes percent-encoded academy names', () => {
    expect(adminRelativePath('/academies/' + encodeURIComponent(name) + '/admin/members', name)).toBe('members')
  })

  it('returns empty string for the admin root', () => {
    expect(adminRelativePath('/academies/' + encodeURIComponent(name) + '/admin', name)).toBe('')
  })

  it('returns null outside the admin area', () => {
    expect(adminRelativePath('/academies/x/feed', 'x')).toBeNull()
  })

  it('strips trailing slashes', () => {
    expect(adminRelativePath('/academies/x/admin/members/', 'x')).toBe('members')
    expect(adminRelativePath('/academies/x/admin/', 'x')).toBe('')
  })
})

describe('hasPermission', () => {
  it('treats a parent key as covering child keys', () => {
    expect(hasPermission(['courses.edit'], 'courses.edit.own')).toBe(true)
  })

  it('does not let a child key cover its parent', () => {
    expect(hasPermission(['academy.view.public'], 'academy.view')).toBe(false)
  })

  it('wildcard grants everything', () => {
    expect(hasPermission(['*'], 'anything.at.all')).toBe(true)
    expect(hasPermission(['*'], 'admin.access')).toBe(true)
  })
})

describe('registry integrity', () => {
  it('every non-dashboard, non-adminOnly section has at least one view key', () => {
    const missing = ADMIN_SECTIONS
      .filter(s => s.id !== 'dashboard' && !s.adminOnly && s.view.length === 0)
      .map(s => s.id)
    expect(missing).toEqual([])
  })

  it('no section uses academy.view', () => {
    const offenders = ADMIN_SECTIONS
      .filter(s => s.view.includes('academy.view') || (s.manage ?? []).includes('academy.view'))
      .map(s => s.id)
    expect(offenders).toEqual([])
  })
})
