import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { i as useApi } from './server.mjs';

const useCourseGroupStore = defineStore("courseGroup", () => {
  const groups = ref([]);
  const currentGroup = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  const lastFetchTime = ref(null);
  const lastCourseId = ref(null);
  const cacheDuration = 5 * 60 * 1e3;
  const groupsCount = computed(() => groups.value.length);
  const getGroupById = computed(() => (groupId) => {
    return groups.value.find((g) => g.id === groupId);
  });
  const hasGroups = computed(() => groups.value.length > 0);
  const isCacheValid = computed(() => (courseId) => {
    if (!lastFetchTime.value) return false;
    if (courseId && lastCourseId.value != courseId) return false;
    return Date.now() - lastFetchTime.value < cacheDuration;
  });
  const setGroups = (groupsData, courseId) => {
    groups.value = groupsData || [];
    lastFetchTime.value = Date.now();
    if (courseId) {
      lastCourseId.value = courseId;
    }
  };
  const setCurrentGroup = (group) => {
    currentGroup.value = group;
  };
  const addGroup = (group) => {
    const exists = groups.value.find((g) => g.id === group.id);
    if (!exists) {
      groups.value.push(group);
    }
  };
  const updateGroup = (groupId, updates) => {
    var _a;
    const index = groups.value.findIndex((g) => g.id === groupId);
    if (index !== -1) {
      groups.value[index] = { ...groups.value[index], ...updates };
    }
    if (((_a = currentGroup.value) == null ? void 0 : _a.id) === groupId) {
      currentGroup.value = { ...currentGroup.value, ...updates };
    }
  };
  const removeGroup = (groupId) => {
    var _a;
    groups.value = groups.value.filter((g) => g.id !== groupId);
    if (((_a = currentGroup.value) == null ? void 0 : _a.id) === groupId) {
      currentGroup.value = null;
    }
  };
  const clearGroups = () => {
    groups.value = [];
    currentGroup.value = null;
    error.value = null;
    lastFetchTime.value = null;
    lastCourseId.value = null;
  };
  const fetchGroups = async (courseId, forceRefresh = false) => {
    var _a;
    if (!forceRefresh && isCacheValid.value(courseId)) {
      return { success: true, groups: groups.value };
    }
    isLoading.value = true;
    error.value = null;
    try {
      const api = useApi();
      const response = await api.get(`/api/courses/${courseId}/groups`);
      if (response.success) {
        setGroups(response.groups || response.data, courseId);
        return response;
      }
    } catch (err) {
      error.value = ((_a = err.data) == null ? void 0 : _a.msg) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49";
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const fetchGroupById = async (courseId, groupId) => {
    var _a;
    isLoading.value = true;
    error.value = null;
    try {
      const api = useApi();
      const response = await api.get(`/api/courses/${courseId}/groups/${groupId}`);
      if (response.success) {
        setCurrentGroup(response.group || response.data);
        return response;
      }
    } catch (err) {
      error.value = ((_a = err.data) == null ? void 0 : _a.msg) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49";
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const createGroup = async (courseId, data) => {
    var _a;
    isLoading.value = true;
    error.value = null;
    try {
      const api = useApi();
      const response = await api.post(`/api/courses/${courseId}/groups`, data);
      const group = response.group || response.data || response;
      if (group) {
        addGroup(group);
      }
      return group;
    } catch (err) {
      error.value = ((_a = err.data) == null ? void 0 : _a.msg) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49";
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const updateGroupData = async (courseId, groupId, data) => {
    var _a;
    isLoading.value = true;
    error.value = null;
    try {
      const api = useApi();
      const response = await api.patch(`/api/courses/${courseId}/groups/${groupId}`, data);
      const group = response.group || response.data || response;
      if (group) {
        updateGroup(groupId, group);
      }
      return group;
    } catch (err) {
      error.value = ((_a = err.data) == null ? void 0 : _a.msg) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49";
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const deleteGroup = async (courseId, groupId) => {
    var _a;
    isLoading.value = true;
    error.value = null;
    try {
      const api = useApi();
      const response = await api.delete(`/api/courses/${courseId}/groups/${groupId}`);
      if (response.success) {
        removeGroup(groupId);
        return response;
      }
    } catch (err) {
      error.value = ((_a = err.data) == null ? void 0 : _a.msg) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49";
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  return {
    // State
    groups,
    currentGroup,
    isLoading,
    error,
    // Getters
    groupsCount,
    getGroupById,
    hasGroups,
    isCacheValid,
    // Actions
    setGroups,
    setCurrentGroup,
    addGroup,
    updateGroup,
    removeGroup,
    clearGroups,
    fetchGroups,
    fetchGroupById,
    createGroup,
    updateGroupData,
    deleteGroup
  };
});

export { useCourseGroupStore as u };
//# sourceMappingURL=courseGroup-9VJQb76E.mjs.map
