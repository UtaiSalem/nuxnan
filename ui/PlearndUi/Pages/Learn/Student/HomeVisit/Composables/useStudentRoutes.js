import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useStudentRoutes(context = null) {
  const page = usePage()
  
  // Detect context based on prop or current route
  const isTeacherContext = computed(() => {
    if (context) {
      return context === 'teacher'
    }
    return page.url.includes('/teacher/') || page.component.includes('Teacher')
  })
  
  const isStudentContext = computed(() => {
    return !isTeacherContext.value
  })
  
  // Academic Info Routes
  const academicInfoRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        index: (studentId) => route('homevisit.teacher.academic-info.index', studentId),
        store: (studentId) => route('homevisit.teacher.academic-info.store', studentId),
        update: (studentId, academicInfoId) => route('homevisit.teacher.academic-info.update', [studentId, academicInfoId]),
        destroy: (studentId, academicInfoId) => route('homevisit.teacher.academic-info.destroy', [studentId, academicInfoId]),
        setCurrent: (studentId, academicInfoId) => route('homevisit.teacher.academic-info.set-current', [studentId, academicInfoId])
      }
    } else {
      return {
        index: (studentId) => route('homevisit.student.academic-info.index', studentId),
        store: (studentId) => route('homevisit.student.academic-info.store', studentId),
        update: (studentId, academicInfoId) => route('homevisit.student.academic-info.update', [studentId, academicInfoId]),
        destroy: (studentId, academicInfoId) => route('homevisit.student.academic-info.destroy', [studentId, academicInfoId]),
        setCurrent: (studentId, academicInfoId) => route('homevisit.student.academic-info.set-current', [studentId, academicInfoId])
      }
    }
  })
  
  // Address Routes
  const addressRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        index: (studentId) => route('homevisit.teacher.addresses.index', studentId),
        store: (studentId) => route('homevisit.teacher.addresses.store', studentId),
        update: (studentId, addressId) => route('homevisit.teacher.addresses.update', [studentId, addressId]),
        destroy: (studentId, addressId) => route('homevisit.teacher.addresses.destroy', [studentId, addressId]),
        setCurrent: (studentId, addressId) => route('homevisit.teacher.addresses.set-current', [studentId, addressId])
      }
    } else {
      return {
        index: (studentId) => route('homevisit.student.addresses.index', studentId),
        store: (studentId) => route('homevisit.student.addresses.store', studentId),
        update: (studentId, addressId) => route('homevisit.student.addresses.update', [studentId, addressId]),
        destroy: (studentId, addressId) => route('homevisit.student.addresses.destroy', [studentId, addressId]),
        setCurrent: (studentId, addressId) => route('homevisit.student.addresses.set-current', [studentId, addressId])
      }
    }
  })
  
  // Contact Routes
  const contactRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        index: (studentId) => route('homevisit.teacher.contacts.index', studentId),
        store: (studentId) => route('homevisit.teacher.contacts.store', studentId),
        update: (studentId, contactId) => route('homevisit.teacher.contacts.update', [studentId, contactId]),
        destroy: (studentId, contactId) => route('homevisit.teacher.contacts.destroy', [studentId, contactId]),
        setPrimary: (studentId, contactId) => route('homevisit.teacher.contacts.set-primary', [studentId, contactId])
      }
    } else {
      return {
        index: (studentId) => route('homevisit.student.contacts.index', studentId),
        store: (studentId) => route('homevisit.student.contacts.store', studentId),
        update: (studentId, contactId) => route('homevisit.student.contacts.update', [studentId, contactId]),
        destroy: (studentId, contactId) => route('homevisit.student.contacts.destroy', [studentId, contactId]),
        setPrimary: (studentId, contactId) => route('homevisit.student.contacts.set-primary', [studentId, contactId])
      }
    }
  })
  
  // Health Info Routes
  const healthRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        show: (studentId) => route('homevisit.teacher.health.show', studentId),
        store: (studentId) => route('homevisit.teacher.health.store', studentId),
        update: (studentId, healthId) => route('homevisit.teacher.health.update', [studentId, healthId])
      }
    } else {
      return {
        show: (studentId) => route('homevisit.student.health.show', studentId),
        store: (studentId) => route('homevisit.student.health.store', studentId),
        update: (studentId, healthId) => route('homevisit.student.health.update', [studentId, healthId])
      }
    }
  })
  
  // Guardian Routes
  const guardianRoutes = computed(() => {
    if (isTeacherContext.value) {
      return {
        show: (studentId) => route('homevisit.teacher.guardian.show', studentId),
        store: (studentId) => route('homevisit.teacher.guardian.store', studentId),
        update: (studentId) => route('homevisit.teacher.guardian.update', studentId)
      }
    } else {
      return {
        show: (studentId) => route('homevisit.student.guardian.show', studentId),
        store: (studentId) => route('homevisit.student.guardian.store', studentId),
        update: (studentId) => route('homevisit.student.guardian.update', studentId)
      }
    }
  })
  
  return {
    isTeacherContext,
    isStudentContext,
    academicInfoRoutes,
    addressRoutes,
    contactRoutes,
    healthRoutes,
    guardianRoutes
  }
}