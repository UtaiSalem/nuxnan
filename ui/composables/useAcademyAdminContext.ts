import { inject, provide } from 'vue'
import type { ComputedRef, InjectionKey, Ref } from 'vue'
import type { UseAcademyRoleReturn } from '~/composables/useAcademyRole'

/**
 * SA-3 — context ร่วมของพื้นที่แอดมินโรงเรียน: เปลือก admin.vue provide ครั้งเดียว หน้าลูก inject ไปใช้
 * (หน้าลูกที่ยังดึงเองจะย้ายมาใช้ใน SA-5)
 */
export interface AcademyAdminContext {
  academy: Ref<any>
  academyId: Ref<number | null>
  academyName: ComputedRef<string>
  role: UseAcademyRoleReturn
  can: (permission: string) => boolean
  isAdmin: ComputedRef<boolean>
  refreshRole: () => Promise<void>
}

export const ACADEMY_ADMIN_CONTEXT: InjectionKey<AcademyAdminContext> = Symbol('academy-admin-context')

export const provideAcademyAdminContext = (ctx: AcademyAdminContext) => provide(ACADEMY_ADMIN_CONTEXT, ctx)

export const useAcademyAdminContext = (): AcademyAdminContext => {
  const ctx = inject(ACADEMY_ADMIN_CONTEXT, null)
  if (!ctx) {
    throw new Error('useAcademyAdminContext() ต้องเรียกในหน้าที่อยู่ใต้ pages/academies/[name]/admin.vue')
  }
  return ctx
}
