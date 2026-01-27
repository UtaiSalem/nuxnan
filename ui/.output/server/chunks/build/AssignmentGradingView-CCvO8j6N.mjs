import { defineComponent, ref, computed, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrRenderStyle, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { i as useApi } from './server.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AssignmentGradingView",
  __ssrInlineRender: true,
  props: {
    assignment: {},
    courseId: {}
  },
  setup(__props) {
    const props = __props;
    const api = useApi();
    const { getAvatarUrl } = useAvatar();
    const allAnswers = ref([]);
    const groups = ref([]);
    const selectedGroup = ref(null);
    const courseMemberId = ref(null);
    const isFetchingAnswers = ref(false);
    ref(false);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const hasLoadedAnswers = ref(false);
    const totalAnswers = ref(0);
    ref(false);
    const searchQuery = ref("");
    const statusFilter = ref("all");
    const fetchAllAnswers = async (page = 1, reset = false) => {
      isFetchingAnswers.value = true;
      try {
        const response = await api.get(`/api/assignments/${props.assignment.id}/answers`, {
          params: {
            page,
            group_id: selectedGroup.value || "all"
          }
        });
        const halfPoints = (props.assignment.points || 0) / 2;
        const newAnswers = (response.data || []).map((a) => {
          const isGraded = a.points !== null && a.points !== void 0;
          const hasGoodScore = isGraded && a.points >= halfPoints;
          return {
            ...a,
            points: a.points,
            originalPoints: a.points,
            isUpdating: false,
            isExpanded: !hasGoodScore
            // Expand if NOT graded or score < half, collapse if score >= half
          };
        });
        if (reset) {
          allAnswers.value = newAnswers;
        } else {
          allAnswers.value = [...allAnswers.value, ...newAnswers];
        }
        currentPage.value = response.meta.current_page;
        lastPage.value = response.meta.last_page;
        totalAnswers.value = response.meta.total;
        hasLoadedAnswers.value = true;
      } catch (error) {
        console.error("Failed to fetch answers:", error);
      } finally {
        isFetchingAnswers.value = false;
      }
    };
    const filteredAnswers = computed(() => {
      let result = allAnswers.value;
      const halfPoints = (props.assignment.points || 0) / 2;
      if (statusFilter.value === "ungraded") {
        result = result.filter((a) => a.points === null || a.points === void 0);
      } else if (statusFilter.value === "graded") {
        result = result.filter((a) => a.points !== null && a.points !== void 0 && a.points >= halfPoints);
      } else if (statusFilter.value === "failed") {
        result = result.filter((a) => a.points !== null && a.points !== void 0 && a.points < halfPoints);
      }
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.trim().toLowerCase();
        result = result.filter((answer) => {
          var _a, _b, _c, _d;
          const name = (answer.member_name || ((_a = answer.student) == null ? void 0 : _a.username) || "").toLowerCase();
          const memberNumber = String(answer.member_number || ((_b = answer.student) == null ? void 0 : _b.member_number) || "");
          const studentId = String(answer.student_id || ((_c = answer.student) == null ? void 0 : _c.student_id) || ((_d = answer.student) == null ? void 0 : _d.id) || "");
          return name.includes(query) || memberNumber.includes(query) || studentId.includes(query);
        });
      }
      return result;
    });
    const statusCounts = computed(() => {
      const halfPoints = (props.assignment.points || 0) / 2;
      return {
        all: allAnswers.value.length,
        ungraded: allAnswers.value.filter((a) => a.points === null || a.points === void 0).length,
        graded: allAnswers.value.filter((a) => a.points !== null && a.points !== void 0 && a.points >= halfPoints).length,
        failed: allAnswers.value.filter((a) => a.points !== null && a.points !== void 0 && a.points < halfPoints).length
      };
    });
    computed(() => filteredAnswers.value.filter((a) => a.points === null || a.points === void 0));
    computed(() => filteredAnswers.value.filter((a) => a.points !== null && a.points !== void 0));
    watch(selectedGroup, async (newGroupId, oldGroupId) => {
      if (hasLoadedAnswers.value) {
        fetchAllAnswers(1, true);
      }
      if (newGroupId && oldGroupId !== void 0 && courseMemberId.value) {
        try {
          await api.post(`/api/courses/${props.courseId}/members/${courseMemberId.value}/set-active-group-tab`, {
            group_tab: newGroupId
          });
        } catch (e) {
          console.error("Failed to save group selection:", e);
        }
      }
    });
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleString("th-TH", {
        dateStyle: "medium",
        timeStyle: "short"
      });
    };
    const isLate = (answer) => {
      if (!props.assignment.due_date) return false;
      return new Date(answer.created_at) > new Date(props.assignment.due_date);
    };
    const userAvatar = (user) => getAvatarUrl(user);
    const getAnswerCardClass = (answer) => {
      if (answer.points === null || answer.points === void 0) {
        return "bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-600";
      }
      const halfPoints = (props.assignment.points || 0) / 2;
      return answer.points >= halfPoints ? "bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700" : "bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700";
    };
    const showBackToTop = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))}><div class="flex items-center justify-between mb-4"><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:people-community-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19 (${ssrInterpolate(hasLoadedAnswers.value ? totalAnswers.value : props.assignment.answer_count || 0)}) </h2></div><div class="relative mb-4"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`</div><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22 \u0E0A\u0E37\u0E48\u0E2D, \u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19..." class="w-full pl-10 pr-10 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all">`);
      if (searchQuery.value) {
        _push(`<button class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-circle-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (groups.value && groups.value.length > 0) {
        _push(`<div class="flex flex-wrap gap-2 mb-4 border-b border-gray-200 dark:border-gray-700 pb-3"><button class="${ssrRenderClass([selectedGroup.value === null ? "bg-indigo-600 text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700", "px-4 py-2 rounded-lg text-sm font-medium transition-all"])}"> \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button><!--[-->`);
        ssrRenderList(groups.value, (group) => {
          _push(`<button class="${ssrRenderClass([selectedGroup.value === group.id ? "bg-indigo-600 text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700", "px-4 py-2 rounded-lg text-sm font-medium transition-all"])}">${ssrInterpolate(group.name)}</button>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex flex-wrap gap-2 mb-4"><button class="${ssrRenderClass([statusFilter.value === "all" ? "bg-gray-700 text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700", "px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:list-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20">${ssrInterpolate(statusCounts.value.all)}</span></button><button class="${ssrRenderClass([statusFilter.value === "ungraded" ? "bg-gray-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700", "px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clock-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08 <span class="${ssrRenderClass([statusFilter.value === "ungraded" ? "bg-white/20" : "bg-gray-200 dark:bg-gray-700", "px-1.5 py-0.5 rounded-full text-[10px]"])}">${ssrInterpolate(statusCounts.value.ungraded)}</span></button><button class="${ssrRenderClass([statusFilter.value === "graded" ? "bg-green-600 text-white shadow-md" : "bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/40", "px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E1C\u0E48\u0E32\u0E19 <span class="${ssrRenderClass([statusFilter.value === "graded" ? "bg-white/20" : "bg-green-100 dark:bg-green-900/40", "px-1.5 py-0.5 rounded-full text-[10px]"])}">${ssrInterpolate(statusCounts.value.graded)}</span></button><button class="${ssrRenderClass([statusFilter.value === "failed" ? "bg-red-600 text-white shadow-md" : "bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40", "px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-1.5"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:dismiss-circle-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19 <span class="${ssrRenderClass([statusFilter.value === "failed" ? "bg-white/20" : "bg-red-100 dark:bg-red-900/40", "px-1.5 py-0.5 rounded-full text-[10px]"])}">${ssrInterpolate(statusCounts.value.failed)}</span></button></div>`);
      if (isFetchingAnswers.value && allAnswers.value.length === 0) {
        _push(`<div class="flex justify-center py-8">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "eos-icons:loading",
          class: "w-8 h-8 text-orange-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (allAnswers.value.length === 0) {
        _push(`<div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center text-gray-500 border border-dashed border-gray-300 dark:border-gray-700 text-sm">${ssrInterpolate(selectedGroup.value ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E48\u0E07\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19")}</div>`);
      } else if (filteredAnswers.value.length === 0 && searchQuery.value) {
        _push(`<div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center text-gray-500 border border-dashed border-gray-300 dark:border-gray-700 text-sm">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:search-24-regular",
          class: "w-8 h-8 mx-auto mb-2 text-gray-400"
        }, null, _parent));
        _push(`<p>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E25\u0E25\u0E31\u0E1E\u0E18\u0E4C\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A &quot;${ssrInterpolate(searchQuery.value)}&quot;</p></div>`);
      } else {
        _push(`<div class="space-y-3">`);
        if (searchQuery.value) {
          _push(`<p class="text-sm text-gray-500 dark:text-gray-400"> \u0E1E\u0E1A ${ssrInterpolate(filteredAnswers.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="grid gap-3"><!--[-->`);
        ssrRenderList(filteredAnswers.value, (answer) => {
          var _a, _b, _c;
          _push(`<div class="${ssrRenderClass([getAnswerCardClass(answer), "rounded-xl p-4 border shadow-sm transition-shadow hover:shadow-md"])}"><div class="flex items-start gap-3"><img${ssrRenderAttr("src", userAvatar(answer.student))} class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700"><div class="flex-1 min-w-0"><div class="flex justify-between"><div><h3 class="font-bold text-gray-900 dark:text-white text-sm">${ssrInterpolate(answer.member_name || ((_a = answer.student) == null ? void 0 : _a.username) || "Unknown Student")}</h3><p class="${ssrRenderClass([isLate(answer) ? "text-red-500 font-bold" : "text-green-600", "text-xs flex items-center gap-1"])}">${ssrInterpolate(formatDate(answer.created_at))} `);
          if (isLate(answer)) {
            _push(`<span>(Late)</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</p></div><div class="text-right flex items-center gap-2"><button class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-400">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: answer.isExpanded ? "fluent:chevron-up-24-regular" : "fluent:chevron-down-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button><div class="${ssrRenderClass([answer.points !== null ? answer.points >= (__props.assignment.passing_score || 0) ? "text-green-600" : "text-red-500" : "text-gray-300", "text-xl font-bold"])}">${ssrInterpolate((_b = answer.points) != null ? _b : "-")} <span class="text-xs font-normal text-gray-400">/ ${ssrInterpolate(__props.assignment.points)}</span></div></div></div><div style="${ssrRenderStyle(answer.isExpanded ? null : { display: "none" })}"><div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap border border-gray-100 dark:border-gray-800">${ssrInterpolate(answer.content)} `);
          if ((_c = answer.images) == null ? void 0 : _c.length) {
            _push(`<div class="mt-3 flex flex-wrap gap-2"><!--[-->`);
            ssrRenderList(answer.images, (img) => {
              _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3"><input type="number"${ssrRenderAttr("value", answer.points)}${ssrRenderAttr("min", 0)}${ssrRenderAttr("max", __props.assignment.points)} class="w-16 px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center font-bold text-sm focus:ring-2 focus:ring-orange-500 outline-none"><input type="range"${ssrRenderAttr("value", answer.points)}${ssrRenderAttr("min", 0)}${ssrRenderAttr("max", __props.assignment.points)} class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"><button${ssrIncludeBooleanAttr(answer.isUpdating || answer.points === null && answer.originalPoints === null || answer.points === answer.originalPoints) ? " disabled" : ""} class="px-3 py-1 text-xs font-bold text-white bg-orange-500 rounded-lg hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">${ssrInterpolate(answer.isUpdating ? "..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")}</button></div></div></div></div>`);
        });
        _push(`<!--]--></div>`);
        if (currentPage.value < lastPage.value) {
          _push(`<div class="flex justify-center mt-4"><button${ssrIncludeBooleanAttr(isFetchingAnswers.value) ? " disabled" : ""} class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 font-medium disabled:opacity-50 flex items-center gap-2">${ssrInterpolate(isFetchingAnswers.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14..." : "\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21")}</button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`<button class="fixed bottom-6 right-6 p-3 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition-all z-50 animate-bounce-slow" title="Back to Top" style="${ssrRenderStyle(showBackToTop.value ? null : { display: "none" })}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-up-24-filled",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</button></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AssignmentGradingView.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=AssignmentGradingView-CCvO8j6N.mjs.map
