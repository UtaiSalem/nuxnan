import { defineStore } from 'pinia'

export const useCourseMemberStore = defineStore('course-member', {
    state: () => ({
        member: null as any | null,
        loading: false,
    }),
    
    getters: {
        isMember: (state) => !!state.member,
        currentGroupId: (state) => state.member?.group_id,
        lastAccessedGroupTab: (state) => state.member?.last_accessed_group_tab
    },

    actions: {
        setMember(memberData: any) {
            this.member = memberData
        },
        
        clearMember() {
            this.member = null
        },

        // If needed, we can add a fetch action here later, 
        // but currently the course layout fetches it.
        async fetchMember(courseId: number | string) {
            this.loading = true
            const api = useApi()
            try {
                // Assuming an endpoint exists or we use the feed endpoint
                // For now, this is a placeholder if we need independent fetching
                const res = await api.get(`/api/courses/${courseId}/me`) as any
                if (res.data) {
                    this.member = res.data
                    console.log(this.member)
                }
            } catch (error) {
                console.error('Failed to fetch my member info', error)
            } finally {
                this.loading = false
            }
        }
    }
})
