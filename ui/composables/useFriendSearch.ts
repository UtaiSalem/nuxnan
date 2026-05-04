import { ref, computed } from 'vue'
import { friendService } from '~/services'

/**
 * useFriendSearch — Example composable that delegates API calls to a service.
 *
 * Pattern:
 *   service (raw API)  →  composable (state + UX)  →  component
 *
 * This is the reference pattern for Phase B of the service layer refactor.
 * Components call the composable; the composable owns debounce + loading
 * state; and the service owns the actual HTTP call. Swapping endpoints or
 * response shapes only requires editing `services/friendService.js`.
 *
 * Usage:
 *   const { results, isSearching, hasSearched, search, clear } = useFriendSearch()
 *   search('john')            // debounced; mutates `results`
 *   clear()                   // reset state
 */
export interface FriendSearchResult {
  id: number
  name: string
  username?: string
  avatar?: string
}

export interface UseFriendSearchOptions {
  /** Debounce delay in milliseconds (default 300) */
  debounceMs?: number
  /** Minimum query length before triggering a search (default 1) */
  minLength?: number
}

export const useFriendSearch = (options: UseFriendSearchOptions = {}) => {
  const debounceMs = options.debounceMs ?? 300
  const minLength = options.minLength ?? 1

  const results = ref<FriendSearchResult[]>([])
  const isSearching = ref(false)
  const hasSearched = ref(false)
  const error = ref<string | null>(null)
  const lastQuery = ref('')

  const isEmpty = computed(
    () => hasSearched.value && !isSearching.value && results.value.length === 0
  )

  let timeoutId: ReturnType<typeof setTimeout> | null = null

  /**
   * Run a search immediately (no debounce).
   */
  const searchNow = async (query: string): Promise<FriendSearchResult[]> => {
    const trimmed = query.trim()
    lastQuery.value = trimmed

    if (trimmed.length < minLength) {
      results.value = []
      hasSearched.value = false
      return []
    }

    isSearching.value = true
    error.value = null

    try {
      const response = await friendService.searchFriends(trimmed) as {
        success?: boolean
        data?: FriendSearchResult[]
        friends?: FriendSearchResult[]
      }

      const list = response?.data || response?.friends || []
      // Only apply if this is still the latest query (avoid stale overwrite)
      if (lastQuery.value === trimmed) {
        results.value = list
        hasSearched.value = true
      }
      return list
    } catch (err: any) {
      error.value = err?.message || 'ไม่สามารถค้นหาเพื่อนได้'
      results.value = []
      hasSearched.value = true
      return []
    } finally {
      if (lastQuery.value === trimmed) {
        isSearching.value = false
      }
    }
  }

  /**
   * Run a search with debounce. Call this from @input handlers.
   */
  const search = (query: string) => {
    if (timeoutId) clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      searchNow(query)
    }, debounceMs)
  }

  /**
   * Reset state.
   */
  const clear = () => {
    if (timeoutId) clearTimeout(timeoutId)
    results.value = []
    isSearching.value = false
    hasSearched.value = false
    error.value = null
    lastQuery.value = ''
  }

  return {
    // State
    results,
    isSearching,
    hasSearched,
    error,
    isEmpty,
    lastQuery,

    // Actions
    search,
    searchNow,
    clear,
  }
}
