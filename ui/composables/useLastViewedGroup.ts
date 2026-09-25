import { toValue, type MaybeRefOrGetter } from 'vue'

/**
 * Shared "remember the group the admin last viewed" behaviour for course pages.
 *
 * Persistence:
 *  - Enrolled admins  → server-side (course_members.last_viewed_group_id), so the
 *    choice follows them across devices/browsers.
 *  - Non-member admins (owner / super-admin without a course_members row) → the
 *    server endpoint is a no-op for them, so we keep it in localStorage instead.
 *
 * localStorage is best-effort only (SSR / private mode / quota safe).
 */
export function useLastViewedGroup(courseId: MaybeRefOrGetter<number | string | undefined | null>) {
    const api = useApi()
    const authStore = useAuthStore()

    const storageKey = (cid: number | string) =>
        `course-${cid}-last-group-tab-${authStore.user?.id ?? 'anon'}`

    const readLocal = (): number | null => {
        const cid = toValue(courseId)
        if (!import.meta.client || !cid) return null
        try {
            const raw = localStorage.getItem(storageKey(cid))
            const id = raw ? Number(raw) : NaN
            return Number.isFinite(id) && id > 0 ? id : null
        } catch {
            return null
        }
    }

    const writeLocal = (groupId: number) => {
        const cid = toValue(courseId)
        if (!import.meta.client || !cid) return
        try {
            localStorage.setItem(storageKey(cid), String(groupId))
        } catch {
            // ignore quota / private-mode errors — persistence is best-effort
        }
    }

    /**
     * Resolve the last viewed group id: prefer the server value from
     * courseMemberOfAuth, otherwise fall back to localStorage. Returns null when
     * there is nothing remembered.
     */
    const resolveLastViewedGroupId = (courseMemberOfAuth?: any): number | null => {
        const serverId = Number(courseMemberOfAuth?.last_viewed_group_id)
        if (Number.isFinite(serverId) && serverId > 0) return serverId
        return readLocal()
    }

    /**
     * Persist the selected group. Always caches locally; only hits the API when
     * the admin is an enrolled member (has a course_members row).
     *
     * Returns true when the preference is safely stored (server save succeeded, or
     * there was nothing to persist server-side), false only when an enrolled
     * member's API call failed — so callers can surface an error if they want.
     */
    const saveLastViewedGroup = async (groupId: number, courseMemberOfAuth?: any): Promise<boolean> => {
        const cid = toValue(courseId)
        if (!cid || !groupId || Number(groupId) <= 0) return false

        writeLocal(Number(groupId))

        // No member row → nothing to persist server-side (endpoint would no-op).
        if (!courseMemberOfAuth?.id) return true

        try {
            await api.patch(`/api/courses/${cid}/members/update-last-viewed-group`, {
                last_viewed_group_id: Number(groupId),
            })
            return true
        } catch (e) {
            console.error('Failed to save last viewed group:', e)
            return false
        }
    }

    return { resolveLastViewedGroupId, saveLastViewedGroup }
}
