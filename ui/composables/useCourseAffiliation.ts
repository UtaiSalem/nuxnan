export interface HostAcademy {
  id: number
  name: string
  name_en: string | null
  logo_url: string | null
}

export const useCourseAffiliation = () => {
  const api = useApi()
  const hostAcademies = ref<HostAcademy[]>([])
  const isLoadingHosts = ref(false)

  const fetchHostAcademies = async () => {
    isLoadingHosts.value = true
    try {
      const res: any = await api.get('/api/me/course-host-academies')
      hostAcademies.value = res?.academies ?? []
    } catch {
      hostAcademies.value = []
    } finally {
      isLoadingHosts.value = false
    }
  }

  const attach = (courseId: number | string, academyId: number) =>
    api.patch(`/api/courses/${courseId}/academy`, { academy_id: academyId })

  const detach = (courseId: number | string) =>
    api.delete(`/api/courses/${courseId}/academy`)

  return { hostAcademies, isLoadingHosts, fetchHostAcademies, attach, detach }
}
