import { computed } from 'vue'
import type { Ref, ComputedRef } from 'vue'
import { useAcademyRole } from '~/composables/useAcademyRole'

export interface AcademyDestination {
  id: string
  title: string
  outcome: string
  icon: string
  to: string
  destinationLabel: string
  intent: 'learn' | 'evaluate' | 'identity' | 'community' | 'commerce' | 'manage'
  visibleWhen: (ctx: NavContext) => boolean
  badge?: { type: 'count' | 'dot' | 'text'; value?: string | number }
  color?: 'sky' | 'amber' | 'emerald' | 'rose' | 'violet' | 'slate'
  order: number
}

export interface NavContext {
  academyName: string
  role: ReturnType<typeof useAcademyRole>
  hasStudentLink: boolean
  hasParentLink: boolean
  isMember: boolean
  isPending: boolean
}

export const useAcademyNavigation = (
  academyName: ComputedRef<string> | Ref<string> | string,
  role: ReturnType<typeof useAcademyRole>,
  extraCtx?: { isPending?: () => boolean }
) => {
  const nameStr = computed(() => typeof academyName === 'string' ? academyName : academyName.value)

  // Context resolved for visibility checks
  const ctx = computed<NavContext>(() => {
    const isMem = role.isMember.value
    const isPen = extraCtx?.isPending ? extraCtx.isPending() : false
    
    return {
      academyName: nameStr.value,
      role,
      hasStudentLink: role.isStudent.value || role.isAdmin.value, // staff/admin can view profiles
      hasParentLink: role.isParent.value,
      isMember: isMem,
      isPending: isPen
    }
  })

  const allDestinations: AcademyDestination[] = [
    {
      id: 'my-dashboard',
      title: 'แดชบอร์ดของฉัน',
      outcome: 'เปิดหน้าแดชบอร์ดตามบทบาทของคุณ',
      icon: 'lucide:layout-dashboard',
      to: `/academies/{n}/dashboard`,
      destinationLabel: 'หน้าแดชบอร์ด',
      intent: 'community',
      color: 'sky',
      order: 1,
      visibleWhen: (c) => c.isMember && c.role.roleName.value !== 'staff'
    },
    {
      id: 'continue-learning',
      title: 'เรียนต่อ',
      outcome: 'ดูรายวิชาที่กำลังเรียนอยู่',
      icon: 'fluent:book-open-page-variant-24-regular',
      to: `/academies/{n}#courses`,
      destinationLabel: 'แท็บรายวิชา',
      intent: 'learn',
      color: 'emerald',
      order: 2,
      visibleWhen: (c) => c.isMember || !c.isPending // guests can view courses
    },
    {
      id: 'my-assignments',
      title: 'งาน/แบบทดสอบของฉัน',
      outcome: 'ดูงานค้างและกำหนดส่ง',
      icon: 'fluent:clipboard-task-list-ltr-24-regular',
      to: `/academies/{n}/dashboard/student`,
      destinationLabel: 'งานแดชบอร์ด',
      intent: 'evaluate',
      color: 'rose',
      order: 3,
      visibleWhen: (c) => c.isMember && c.role.isStudent.value
    },
    {
      id: 'my-attendance',
      title: 'เช็กชื่อ/ประวัติเข้าเรียน',
      outcome: 'ดูสถิติการเข้าเรียนของคุณ',
      icon: 'fluent:person-clock-24-regular',
      to: `/academies/{n}/attendance/check-in`,
      destinationLabel: 'หน้าเช็กชื่อ',
      intent: 'evaluate',
      color: 'violet',
      order: 4,
      visibleWhen: (c) => c.isMember && (c.role.isStudent.value || c.role.isParent.value)
    },
    {
      id: 'my-transcript',
      title: 'ผลการเรียน',
      outcome: 'ดูคะแนนและเกรดของคุณ',
      icon: 'fluent:document-ribbon-24-regular',
      to: `/academies/{n}/my-transcript`,
      destinationLabel: 'หน้าผลการเรียน',
      intent: 'evaluate',
      color: 'amber',
      order: 5,
      visibleWhen: (c) => c.isMember && c.role.isStudent.value
    },
    {
      id: 'my-profile',
      title: 'โปรไฟล์ของฉัน',
      outcome: 'ดู/แก้ไขข้อมูลนักเรียน',
      icon: 'fluent:person-card-profile-phone-24-regular',
      to: `/academies/{n}/my-profile`,
      destinationLabel: 'หน้าโปรไฟล์นักเรียน',
      intent: 'identity',
      color: 'sky',
      order: 6,
      visibleWhen: (c) => c.isMember && c.hasStudentLink
    },
    {
      id: 'my-card',
      title: 'บัตรนักเรียน',
      outcome: 'ดูบัตรนักเรียนของคุณ',
      icon: 'fluent:contact-card-24-regular',
      to: `/academies/{n}/my-profile?tab=card`,
      destinationLabel: 'หน้าบัตรนักเรียน',
      intent: 'identity',
      color: 'slate',
      order: 7,
      visibleWhen: (c) => c.isMember && c.hasStudentLink
    },
    {
      id: 'members-and-classrooms',
      title: 'ห้องเรียน/สมาชิก',
      outcome: 'ค้นหาเพื่อนร่วมโรงเรียน',
      icon: 'fluent:people-community-24-regular',
      to: `/academies/{n}#classrooms`,
      destinationLabel: 'แท็บห้องเรียน',
      intent: 'community',
      color: 'violet',
      order: 8,
      visibleWhen: (c) => c.isMember
    },
    {
      id: 'announcements',
      title: 'ประกาศและกิจกรรม',
      outcome: 'อ่านประกาศและกำหนดการ',
      icon: 'fluent:megaphone-24-regular',
      to: `/academies/{n}#events`,
      destinationLabel: 'แท็บกิจกรรม',
      intent: 'community',
      color: 'amber',
      order: 9,
      visibleWhen: (c) => c.isMember
    },
    {
      id: 'school-store',
      title: 'ร้านค้าโรงเรียน',
      outcome: 'ซื้อสินค้า/แต้มของโรงเรียน',
      icon: 'fluent:store-24-regular',
      to: `/academies/{n}/store`,
      destinationLabel: 'หน้าโรงเรียนสโตร์',
      intent: 'commerce',
      color: 'emerald',
      order: 10,
      visibleWhen: (c) => c.isMember
    },
    {
      id: 'manage-school',
      title: 'จัดการโรงเรียน',
      outcome: 'เข้าหน้าจัดการสำหรับผู้ดูแล',
      icon: 'fluent:settings-gear-24-regular',
      to: `/academies/{n}/admin`,
      destinationLabel: 'หน้าระบบควบคุม',
      intent: 'manage',
      color: 'rose',
      order: 11,
      visibleWhen: (c) => c.role.isAdmin.value || c.role.can('academy.settings.view')
    }
  ]

  const visibleDestinations = computed(() => {
    return allDestinations
      .filter(dest => dest.visibleWhen(ctx.value) && dest.id !== 'my-dashboard') // my-dashboard is primary CTA
      .sort((a, b) => a.order - b.order)
      .slice(0, 10)
      .map(dest => ({
        ...dest,
        to: dest.to.replace('{n}', encodeURIComponent(nameStr.value))
      }))
  })

  const primaryCta = computed(() => {
    const dashboardDest = allDestinations.find(d => d.id === 'my-dashboard')
    if (dashboardDest && dashboardDest.visibleWhen(ctx.value)) {
      return {
        ...dashboardDest,
        to: dashboardDest.to.replace('{n}', encodeURIComponent(nameStr.value))
      }
    }
    // Fallback if not member / guest
    if (!ctx.value.isMember && !ctx.value.isPending) {
      return {
        id: 'join-academy',
        title: 'เข้าร่วมโรงเรียน',
        outcome: 'สมัครสมาชิกโรงเรียนนี้',
        icon: 'fluent:person-add-24-regular',
        to: '', // Handled by join flow click in UI
        destinationLabel: 'ปุ่มเข้าร่วม',
        intent: 'community',
        color: 'sky',
        order: 0
      }
    }
    return null
  })

  const pendingHint = computed(() => {
    if (ctx.value.isPending) {
      return 'คำขอเข้าร่วมโรงเรียนของคุณกำลังอยู่ระหว่างการรออนุมัติจากผู้ดูแลระบบ'
    }
    return null
  })

  return {
    allDestinations,
    visibleDestinations,
    primaryCta,
    pendingHint
  }
}
