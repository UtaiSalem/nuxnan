import { _ as _sfc_main$2 } from './DialogModal-CfsI63S_.mjs';
import { defineComponent, inject, computed, ref, watch, unref, withCtx, createVNode, toDisplayString, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$4 } from './GroupCard-DSuoJKLI.mjs';
import { d as useAuthStore, s as useCourseStore, p as useRoute, i as useApi, n as navigateTo } from './server.mjs';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';
import { _ as _sfc_main$3 } from './GroupForm-Bwe9dqnh.mjs';
import { a as usePage } from './inertia-vue3-CWdJjaLG.mjs';
import { u as useCourseMemberStore } from './courseMember-jssI8x6K.mjs';
import { u as useCourseGroupStore } from './courseGroup-9VJQb76E.mjs';
import './Modal-DYe4d1RC.mjs';
import 'pinia';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'vue-router';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import 'sweetalert2';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "GroupsList",
  __ssrInlineRender: true,
  props: {
    groups: { default: () => [] },
    isCourseAdmin: { type: Boolean, default: false },
    courseId: {},
    joiningGroupId: { default: null }
  },
  emits: ["create", "edit", "join", "refresh", "deleted"],
  setup(__props, { expose: __expose, emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const api = useApi();
    const swal = useSweetAlert();
    const isDeleting = ref(false);
    ref(false);
    const localGroups = ref([]);
    watch(() => props.groups, (newGroups) => {
      localGroups.value = [...newGroups];
    }, { immediate: true, deep: true });
    const navigateToGroup = (group) => {
      navigateTo(`/courses/${props.courseId}/groups/${group.id}`);
    };
    const deleteGroup = async (groupId) => {
      var _a;
      const confirmed = await swal.confirm(
        "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E08\u0E30\u0E16\u0E39\u0E01\u0E22\u0E49\u0E32\u0E22\u0E44\u0E1B\u0E22\u0E31\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2B\u0E25\u0E31\u0E01 \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?",
        "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21",
        {
          confirmText: "\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21",
          cancelText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
          icon: "warning",
          isDanger: true
        }
      );
      if (!confirmed) return;
      isDeleting.value = true;
      try {
        const response = await api.delete(`/api/courses/${props.courseId}/groups/${groupId}`);
        if (response.success) {
          localGroups.value = localGroups.value.filter((g) => g.id !== groupId);
          swal.success("\u0E01\u0E25\u0E38\u0E48\u0E21\u0E16\u0E39\u0E01\u0E25\u0E1A\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27", "\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08");
          emit("deleted", groupId);
          emit("refresh");
        }
      } catch (err) {
        swal.error(((_a = err.data) == null ? void 0 : _a.msg) || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21", "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49");
      } finally {
        isDeleting.value = false;
      }
    };
    const addGroup = (group) => {
      localGroups.value.push(group);
    };
    const updateGroup = (updatedGroup) => {
      const index = localGroups.value.findIndex((g) => g.id === updatedGroup.id);
      if (index !== -1) {
        localGroups.value[index] = { ...localGroups.value[index], ...updatedGroup };
      }
    };
    __expose({
      addGroup,
      updateGroup,
      localGroups
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_GroupCard = _sfc_main$4;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))}><div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"><div class="flex items-center justify-between"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons-outline:user-group",
        class: "w-5 h-5 text-white"
      }, null, _parent));
      _push(`</div><div><h2 class="text-lg font-bold text-gray-900 dark:text-white">\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19</h2><p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(unref(localGroups).length)} \u0E01\u0E25\u0E38\u0E48\u0E21</p></div></div>`);
      if (__props.isCourseAdmin) {
        _push(`<button class="flex items-center gap-2 px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span class="hidden sm:inline">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21</span></button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (unref(localGroups).length > 0) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(unref(localGroups), (group) => {
          _push(ssrRenderComponent(_component_GroupCard, {
            key: group.id,
            group,
            "course-id": __props.courseId,
            "is-course-admin": __props.isCourseAdmin,
            loading: group.id === __props.joiningGroupId,
            onClick: navigateToGroup,
            onEdit: ($event) => emit("edit", group),
            onJoin: ($event) => emit("join", group.id),
            onDelete: deleteGroup
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons-outline:user-group",
          class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19</h3><p class="text-gray-500 dark:text-gray-400 mb-4">\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19</p>`);
        if (__props.isCourseAdmin) {
          _push(`<button class="inline-flex items-center gap-2 px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:add-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E41\u0E23\u0E01 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/GroupsList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const refreshCourse = inject("refreshCourse");
    const courseGroupStore = useCourseGroupStore();
    const courseMemberStore = useCourseMemberStore();
    const authStore = useAuthStore();
    const courseStore = useCourseStore();
    useRoute();
    usePage();
    const groups = computed(() => courseGroupStore.groups);
    const isLoading = ref(false);
    const showCreateModal = ref(false);
    const editingGroup = ref(null);
    const api = useApi();
    const swal = useSweetAlert();
    const groupsListRef = ref(null);
    const loadGroups = async () => {
      var _a;
      if (!((_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id)) return;
      isLoading.value = true;
      try {
        await courseGroupStore.fetchGroups(course.value.id);
      } catch (error) {
        console.error("Failed to load groups:", error);
      } finally {
        isLoading.value = false;
      }
    };
    const handleCreate = () => {
      editingGroup.value = null;
      showCreateModal.value = true;
    };
    const handleEdit = (group) => {
      editingGroup.value = group;
      showCreateModal.value = true;
    };
    const joiningGroupId = ref(null);
    const handleJoin = async (groupId) => {
      var _a, _b, _c, _d, _e;
      const userPP = ((_a = authStore.user) == null ? void 0 : _a.pp) || 0;
      const tuitionFees = ((_b = courseStore.course) == null ? void 0 : _b.tuition_fees) || 0;
      if (userPP < tuitionFees) {
        await swal.fire({
          icon: "warning",
          title: "\u0E02\u0E2D\u0E2D\u0E20\u0E31\u0E22",
          text: "\u0E04\u0E38\u0E13\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D\u0E17\u0E35\u0E48\u0E08\u0E30\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E15\u0E34\u0E21\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E01\u0E48\u0E2D\u0E19",
          confirmButtonText: "\u0E15\u0E01\u0E25\u0E07"
        });
        return;
      }
      const oldGroupId = (_c = courseMemberStore.member) == null ? void 0 : _c.group_id;
      const userId = (_d = authStore.user) == null ? void 0 : _d.id;
      joiningGroupId.value = groupId;
      try {
        const res = await api.post(`/api/courses/${course.value.id}/groups/${groupId}/members`);
        let memberData = res.courseMemberOfAuth;
        if (memberData && memberData.data) {
          memberData = memberData.data;
        }
        if (oldGroupId && oldGroupId != groupId) {
          const oldGroup = courseGroupStore.getGroupById(oldGroupId);
          if (oldGroup) {
            courseGroupStore.updateGroup(oldGroupId, {
              members_count: Math.max(0, (oldGroup.members_count || 0) - 1),
              groupMemberOfAuth: null
            });
          }
        }
        const newGroup = courseGroupStore.getGroupById(groupId);
        if (newGroup) {
          const newCount = newGroup.groupMemberOfAuth ? newGroup.members_count || 0 : (newGroup.members_count || 0) + 1;
          courseGroupStore.updateGroup(groupId, {
            members_count: newCount,
            groupMemberOfAuth: {
              user_id: userId,
              group_id: groupId,
              status: 1,
              ...memberData
              // usage of memberData properties if needed
            }
          });
        }
        courseMemberStore.setMember(memberData);
        await courseGroupStore.fetchGroups(course.value.id, true);
        await swal.success(
          res.message || "\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
          "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08 (Success)"
        );
      } catch (error) {
        console.error("Join Error:", error);
        swal.error(((_e = error.data) == null ? void 0 : _e.message) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49", "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14");
      } finally {
        joiningGroupId.value = null;
      }
    };
    const handleRefresh = async () => {
      var _a;
      if ((_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id) {
        await courseGroupStore.fetchGroups(course.value.id, true);
      }
    };
    const handleGroupDeleted = async (groupId) => {
      if (refreshCourse) {
        await refreshCourse();
      }
    };
    const handleGroupSaved = async (savedGroup) => {
      var _a, _b;
      showCreateModal.value = false;
      if (editingGroup.value) {
        (_a = groupsListRef.value) == null ? void 0 : _a.updateGroup(savedGroup);
        swal.success("\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08");
      } else {
        (_b = groupsListRef.value) == null ? void 0 : _b.addGroup(savedGroup);
        swal.success("\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08");
        if (refreshCourse) {
          await refreshCourse();
        }
      }
      await handleRefresh();
    };
    watch(() => {
      var _a;
      return (_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id;
    }, async (newId) => {
      if (newId) {
        await loadGroups();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_DialogModal = _sfc_main$2;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if (unref(isLoading)) {
        _push(`<div class="flex items-center justify-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-8 h-8 text-blue-500"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$1, {
          ref_key: "groupsListRef",
          ref: groupsListRef,
          groups: unref(groups),
          "course-id": (_a = unref(course)) == null ? void 0 : _a.id,
          "is-course-admin": unref(isCourseAdmin),
          "joining-group-id": unref(joiningGroupId),
          onCreate: handleCreate,
          onEdit: handleEdit,
          onJoin: handleJoin,
          onRefresh: handleRefresh,
          onDeleted: handleGroupDeleted
        }, null, _parent));
      }
      _push(ssrRenderComponent(_component_DialogModal, {
        show: unref(showCreateModal),
        onClose: ($event) => showCreateModal.value = false,
        "max-width": "2xl"
      }, {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center gap-3"${_scopeId}><div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:user-group-solid",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(unref(editingGroup) ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E25\u0E38\u0E48\u0E21" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48")}</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div></div>`);
          } else {
            return [
              createVNode("div", { class: "flex items-center gap-3" }, [
                createVNode("div", { class: "flex items-center justify-center w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl" }, [
                  createVNode(unref(Icon), {
                    icon: "heroicons:user-group-solid",
                    class: "w-6 h-6 text-white"
                  })
                ]),
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white" }, toDisplayString(unref(editingGroup) ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E25\u0E38\u0E48\u0E21" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48"), 1),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
                ])
              ])
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a2, _b;
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$3, {
              "course-id": (_a2 = unref(course)) == null ? void 0 : _a2.id,
              group: unref(editingGroup),
              onSaved: handleGroupSaved,
              onCancel: ($event) => showCreateModal.value = false
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$3, {
                "course-id": (_b = unref(course)) == null ? void 0 : _b.id,
                group: unref(editingGroup),
                onSaved: handleGroupSaved,
                onCancel: ($event) => showCreateModal.value = false
              }, null, 8, ["course-id", "group", "onCancel"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/groups/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-ToNFJsGK.mjs.map
