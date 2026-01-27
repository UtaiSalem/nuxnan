import { defineStore } from 'pinia';
import { i as useApi } from './server.mjs';

const useCourseMemberStore = defineStore("course-member", {
  state: () => ({
    member: null,
    loading: false
  }),
  getters: {
    isMember: (state) => !!state.member,
    currentGroupId: (state) => {
      var _a;
      return (_a = state.member) == null ? void 0 : _a.group_id;
    },
    lastAccessedGroupTab: (state) => {
      var _a;
      return (_a = state.member) == null ? void 0 : _a.last_accessed_group_tab;
    }
  },
  actions: {
    setMember(memberData) {
      this.member = memberData;
    },
    clearMember() {
      this.member = null;
    },
    // If needed, we can add a fetch action here later, 
    // but currently the course layout fetches it.
    async fetchMember(courseId) {
      this.loading = true;
      const api = useApi();
      try {
        const res = await api.get(`/api/courses/${courseId}/me`);
        if (res.data) {
          this.member = res.data;
          console.log(this.member);
        }
      } catch (error) {
        console.error("Failed to fetch my member info", error);
      } finally {
        this.loading = false;
      }
    }
  }
});

export { useCourseMemberStore as u };
//# sourceMappingURL=courseMember-jssI8x6K.mjs.map
