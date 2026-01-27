import { defineComponent, inject, ref, computed, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrRenderList } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { u as useCourseGroupStore } from './courseGroup-9VJQb76E.mjs';
import { u as useCourseMemberStore } from './courseMember-jssI8x6K.mjs';
import { M as MemberCard } from './MemberCard-DppXYXxE.mjs';
import Swal from 'sweetalert2';
import { i as useApi, p as useRoute } from './server.mjs';
import 'pinia';
import './inertia-vue3-CWdJjaLG.mjs';
import 'unhead/utils';
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

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "TopPerformers",
  __ssrInlineRender: true,
  props: {
    members: {
      type: Array,
      required: true,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const topPerformers = computed(() => {
      const sorted = [...props.members].sort((a, b) => {
        const scoreA = Number(a.achieved_score || 0);
        const scoreB = Number(b.achieved_score || 0);
        return scoreB - scoreA;
      });
      return sorted.slice(0, 5);
    });
    const getRankColor = (index) => {
      switch (index) {
        case 0:
          return "bg-yellow-400 text-yellow-900 ring-yellow-400";
        // Gold
        case 1:
          return "bg-gray-300 text-gray-800 ring-gray-300";
        // Silver
        case 2:
          return "bg-orange-400 text-orange-900 ring-orange-400";
        // Bronze
        default:
          return "bg-gray-700 text-white ring-gray-600";
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-slate-900 rounded-xl p-5 shadow-lg border border-slate-800 text-white" }, _attrs))}><div class="flex items-center gap-2 mb-6">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "solar:cup-star-bold",
        class: "w-6 h-6 text-yellow-400"
      }, null, _parent));
      _push(`<h2 class="text-lg font-bold">Top Performers</h2></div><div class="flex flex-col gap-4"><!--[-->`);
      ssrRenderList(topPerformers.value, (member, index) => {
        var _a, _b, _c, _d;
        _push(`<div class="flex items-center justify-between group"><div class="flex items-center gap-3"><div class="relative"><div class="${ssrRenderClass([getRankColor(index), "absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold shadow-md z-10 border-2 border-slate-900"])}">${ssrInterpolate(index + 1)}</div><img${ssrRenderAttr("src", member.avatar || ((_a = member.user) == null ? void 0 : _a.avatar) || ((_b = member.user) == null ? void 0 : _b.profile_photo_url) || "/images/default-avatar.png")}${ssrRenderAttr("alt", member.name)} class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-800 group-hover:ring-slate-700 transition-all"></div><div class="flex flex-col"><span class="text-sm font-semibold truncate max-w-[120px] md:max-w-[150px]">${ssrInterpolate(member.member_name || ((_c = member.user) == null ? void 0 : _c.name) || ((_d = member.student) == null ? void 0 : _d.name) || "Unknown")}</span><span class="text-xs text-slate-400"> F \u2022 ${ssrInterpolate(member.percentage || 0)}% </span></div></div><div class="text-green-500 font-bold font-mono text-lg">${ssrInterpolate(member.achieved_score || 0)}</div></div>`);
      });
      _push(`<!--]-->`);
      if (topPerformers.value.length === 0) {
        _push(`<div class="text-center py-4 text-slate-500 text-sm"> No ranking data yet </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/TopPerformers.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const api = useApi();
    useRoute();
    const courseGroupStore = useCourseGroupStore();
    const courseMemberStore = useCourseMemberStore();
    const searchQuery = ref("");
    const activeGroupTab = ref(0);
    ref("grid");
    ref(false);
    const sortBy = ref("number");
    const getInitialGroupTab = () => {
      var _a, _b;
      if (!isCourseAdmin.value) {
        const userGroupId = (_a = courseMemberStore.member) == null ? void 0 : _a.group_id;
        if (userGroupId) {
          const index2 = courseGroupStore.groups.findIndex((g) => g.id === userGroupId);
          return index2 >= 0 ? index2 + 1 : 0;
        }
        return 0;
      }
      const lastAccessedGroupId = (_b = courseMemberStore.member) == null ? void 0 : _b.last_accessed_group_tab;
      if (!lastAccessedGroupId) return 0;
      const index = courseGroupStore.groups.findIndex((g) => g.id === lastAccessedGroupId);
      return index >= 0 ? index + 1 : 0;
    };
    const members = computed(() => {
      var _a;
      let list = [];
      if (activeGroupTab.value === 0) {
        const allMembers = courseGroupStore.groups.flatMap((g) => g.members || []);
        const seen = /* @__PURE__ */ new Set();
        list = allMembers.filter((m) => {
          const duplicate = seen.has(m.id);
          seen.add(m.id);
          return !duplicate;
        });
      } else {
        const group = courseGroupStore.groups[activeGroupTab.value - 1];
        list = (group == null ? void 0 : group.members) || [];
      }
      if (!isCourseAdmin.value && ((_a = courseMemberStore.member) == null ? void 0 : _a.group_id)) {
        const userGroupId = Number(courseMemberStore.member.group_id);
        list = list.filter((m) => m.group_id === userGroupId);
      }
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        list = list.filter(
          (m) => {
            var _a2, _b, _c;
            return (m.member_name || ((_a2 = m.user) == null ? void 0 : _a2.name) || ((_b = m.student) == null ? void 0 : _b.name) || m.name || "").toLowerCase().includes(query) || (((_c = m.user) == null ? void 0 : _c.email) || "").toLowerCase().includes(query) || (m.student_id || "").toLowerCase().includes(query);
          }
        );
      }
      list.sort((a, b) => {
        if (sortBy.value === "score") {
          const scoreA = Number(a.achieved_score || 0);
          const scoreB = Number(b.achieved_score || 0);
          if (scoreA !== scoreB) {
            return scoreB - scoreA;
          }
        }
        const orderA = a.order_number != null ? Number(a.order_number) : Infinity;
        const orderB = b.order_number != null ? Number(b.order_number) : Infinity;
        return orderA - orderB;
      });
      return list;
    });
    const isLoading = computed(() => courseGroupStore.isLoading);
    const totalmembers = computed(() => members.value.length);
    watch(() => {
      var _a;
      return (_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id;
    }, async (newId) => {
      if (newId) {
        await courseGroupStore.fetchGroups(newId);
        activeGroupTab.value = getInitialGroupTab();
      }
    });
    const handleRequestUnmember = async ({ memberId, memberName }) => {
      const result = await Swal.fire({
        title: "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01?",
        text: `\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A "${memberName}" \u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49\u0E43\u0E0A\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "\u0E43\u0E0A\u0E48, \u0E25\u0E1A\u0E40\u0E25\u0E22!",
        cancelButtonText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
      });
      if (result.isConfirmed) {
        try {
          await api.delete(`/api/courses/${course.value.id}/members/${memberId}`);
          Swal.fire(
            "\u0E25\u0E1A\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
            "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E18\u0E16\u0E39\u0E01\u0E25\u0E1A\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27.",
            "success"
          );
          await courseGroupStore.fetchGroups(course.value.id, true);
        } catch (error) {
          console.error("Failed to remove member:", error);
          Swal.fire(
            "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14!",
            "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E44\u0E14\u0E49.",
            "error"
          );
        }
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-8 max-w-7xl" }, _attrs))}><div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6"><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "ph:users-three-duotone",
        class: "w-8 h-8 text-blue-500"
      }, null, _parent));
      _push(` \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 `);
      if (!unref(isLoading)) {
        _push(`<span class="text-sm font-normal text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full">${ssrInterpolate(unref(totalmembers))} \u0E04\u0E19 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</h1><p class="mt-1 text-gray-500 dark:text-gray-400 text-sm"> \u0E23\u0E32\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E25\u0E30\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </p></div><div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto"><div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-lg"><button class="${ssrRenderClass([unref(sortBy) === "number" ? "bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm" : "text-gray-500 hover:text-gray-700 dark:hover:text-gray-300", "px-3 py-1.5 text-sm font-medium rounded-md transition-all"])}"> \u0E40\u0E23\u0E35\u0E22\u0E07\u0E15\u0E32\u0E21\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 </button><button class="${ssrRenderClass([unref(sortBy) === "score" ? "bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm" : "text-gray-500 hover:text-gray-700 dark:hover:text-gray-300", "px-3 py-1.5 text-sm font-medium rounded-md transition-all"])}"> \u0E40\u0E23\u0E35\u0E22\u0E07\u0E15\u0E32\u0E21\u0E04\u0E30\u0E41\u0E19\u0E19 </button></div><div class="relative w-full sm:w-64">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:magnifying-glass",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", unref(searchQuery))} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E0A\u0E37\u0E48\u0E2D, \u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E28\u0E36\u0E01\u0E29\u0E32..." class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-500 dark:text-white"></div></div></div><div class="grid grid-cols-1 lg:grid-cols-4 gap-6"><div class="lg:col-span-3 order-2 lg:order-1">`);
      if (unref(isCourseAdmin) && unref(courseGroupStore).groups.length > 0) {
        _push(`<div class="mb-6"><div class="flex flex-wrap items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"><button class="${ssrRenderClass([unref(activeGroupTab) === 0 ? "bg-blue-500 text-white shadow-md" : "text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700", "inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:users",
          class: "w-4 h-4 mr-2"
        }, null, _parent));
        _push(` \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 (${ssrInterpolate(unref(courseGroupStore).groups.reduce((sum, g) => {
          var _a2;
          return sum + (((_a2 = g.members) == null ? void 0 : _a2.length) || 0);
        }, 0))}) </button><!--[-->`);
        ssrRenderList(unref(courseGroupStore).groups, (group, index) => {
          var _a2;
          _push(`<button class="${ssrRenderClass([unref(activeGroupTab) === index + 1 ? "bg-blue-500 text-white shadow-md" : "text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700", "inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"])}">${ssrInterpolate(group.name)} (${ssrInterpolate(((_a2 = group.members) == null ? void 0 : _a2.length) || 0)}) </button>`);
        });
        _push(`<!--]--></div></div>`);
      } else if (!unref(isCourseAdmin) && ((_a = unref(courseMemberStore).member) == null ? void 0 : _a.group_id)) {
        _push(`<div class="mb-6"><div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-100 dark:border-blue-800">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:user-group-solid",
          class: "w-5 h-5 text-blue-500"
        }, null, _parent));
        _push(`<span class="text-sm font-medium text-blue-700 dark:text-blue-300"> \u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19: ${ssrInterpolate(((_b = unref(courseGroupStore).groups.find((g) => {
          var _a2;
          return g.id === ((_a2 = unref(courseMemberStore).member) == null ? void 0 : _a2.group_id);
        })) == null ? void 0 : _b.name) || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(isLoading)) {
        _push(`<div class="flex justify-center py-20"><div class="flex flex-col items-center gap-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-10 h-10 text-blue-500"
        }, null, _parent));
        _push(`<span class="text-gray-500 animate-pulse">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01...</span></div></div>`);
      } else if (unref(members).length > 0) {
        _push(`<div><ul class="flex flex-col gap-3"><!--[-->`);
        ssrRenderList(unref(members), (member, index) => {
          _push(ssrRenderComponent(MemberCard, {
            key: member.id,
            member,
            "data-index": index,
            onRequestUnmemberCourse: handleRequestUnmember
          }, null, _parent));
        });
        _push(`<!--]--></ul></div>`);
      } else {
        _push(`<div class="text-center py-20 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700"><div class="inline-flex p-4 rounded-full bg-gray-50 dark:bg-gray-900 mb-4">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "ph:users-three-duotone",
          class: "w-12 h-12 text-gray-400"
        }, null, _parent));
        _push(`</div><h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</h3><p class="text-gray-500">\u0E25\u0E2D\u0E07\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E04\u0E33\u0E04\u0E49\u0E19\u0E2B\u0E32 \u0E2B\u0E23\u0E37\u0E2D\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
      }
      _push(`</div><div class="lg:col-span-1 order-1 lg:order-2">`);
      if (unref(members).length > 0) {
        _push(ssrRenderComponent(_sfc_main$1, { members: unref(members) }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/members/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-Dk-Vc5fg.mjs.map
