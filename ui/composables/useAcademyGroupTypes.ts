import { ACADEMY_GROUP_TYPES, type AcademyGroupTypeMeta } from '~/constants/academyGroupTypes'

export const useAcademyGroupTypes = () => {
  const api = useApi()
  const cache = useState<AcademyGroupTypeMeta[] | null>('academy-group-types-cache', () => null)
  const isLoaded = useState<boolean>('academy-group-types-loaded', () => false)

  const types = computed(() => cache.value ?? ACADEMY_GROUP_TYPES)

  const fetch = async () => {
    if (isLoaded.value) return types.value

    try {
      const response: any = await api.get('/api/academy-group-types')
      if (response?.success && Array.isArray(response.data) && response.data.length > 0) {
        cache.value = response.data.map((item: any) => ({
          key: item.key,
          label: item.label,
          labelEn: item.label_en,
          icon: item.icon,
          color: item.color,
          order: item.order,
        }))
      }
    } catch {
      cache.value = cache.value ?? ACADEMY_GROUP_TYPES
    } finally {
      isLoaded.value = true
    }

    return types.value
  }

  return {
    types,
    fetch,
  }
}
