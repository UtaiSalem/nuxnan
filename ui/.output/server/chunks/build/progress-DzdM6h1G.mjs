import { defineComponent, inject, unref, ref, watch, computed, mergeProps, withCtx, createVNode, createBlock, openBlock, createCommentVNode, createTextVNode, withDirectives, vModelText, vModelSelect, toDisplayString, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderStyle, ssrRenderAttr, ssrRenderClass, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { _ as _sfc_main$4 } from './DialogModal-CfsI63S_.mjs';
import { Icon } from '@iconify/vue';
import { i as useApi, _ as _export_sfc } from './server.mjs';
import './Modal-DYe4d1RC.mjs';
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
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "ProgressCard",
  __ssrInlineRender: true,
  props: {
    member: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  emits: ["view-details"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const overallProgress = computed(() => {
      if (props.member.overall_progress !== void 0) {
        return props.member.overall_progress;
      }
      const lessons = props.member.lessons_progress || 0;
      const assignments = props.member.assignments_progress || 0;
      const quizzes = props.member.quizzes_progress || 0;
      const totalWeight = 100;
      const lessonsWeight = 40;
      const assignmentsWeight = 30;
      const quizzesWeight = 30;
      return Math.round(
        (lessons * lessonsWeight + assignments * assignmentsWeight + quizzes * quizzesWeight) / totalWeight
      );
    });
    const getProgressColor = (progress) => {
      if (progress >= 80) return "bg-green-500";
      if (progress >= 60) return "bg-blue-500";
      if (progress >= 40) return "bg-yellow-500";
      return "bg-red-500";
    };
    const getProgressTextColor = (progress) => {
      if (progress >= 80) return "text-green-600 dark:text-green-400";
      if (progress >= 60) return "text-blue-600 dark:text-blue-400";
      if (progress >= 40) return "text-yellow-600 dark:text-yellow-400";
      return "text-red-600 dark:text-red-400";
    };
    const formatDate = (date) => {
      if (!date) return "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21";
      return new Date(date).toLocaleDateString("th-TH", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const statusBadge = computed(() => {
      const p = overallProgress.value;
      if (p >= 80) return { text: "\u0E14\u0E35\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21", color: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 border-green-200 dark:border-green-800" };
      if (p >= 50) return { text: "\u0E1B\u0E01\u0E15\u0E34", color: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800" };
      return { text: "\u0E15\u0E49\u0E2D\u0E07\u0E1B\u0E23\u0E31\u0E1A\u0E1B\u0E23\u0E38\u0E07", color: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800" };
    });
    const getAvatarUrl = (user) => {
      var _a;
      if (user == null ? void 0 : user.avatar) return user.avatar;
      const index = (user == null ? void 0 : user.id) || ((_a = user == null ? void 0 : user.name) == null ? void 0 : _a.length) || 0;
      const textColors = [
        "f59e0b",
        // amber-500
        "64748b",
        // slate-500
        "f97316",
        // orange-500
        "0ea5e9",
        // sky-500
        "10b981",
        // emerald-500
        "8b5cf6",
        // violet-500
        "f43f5e",
        // rose-500
        "14b8a6",
        // teal-500
        "6366f1",
        // indigo-500
        "06b6d4"
        // cyan-500
      ];
      const color = textColors[index % textColors.length];
      return `https://ui-avatars.com/api/?name=${encodeURIComponent((user == null ? void 0 : user.name) || "User")}&background=eff6ff&color=${color}&font-size=0.4`;
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow cursor-pointer" }, _attrs))}><div class="flex items-start gap-3"><img${ssrRenderAttr("src", getAvatarUrl(__props.member.user))}${ssrRenderAttr("alt", (_a = __props.member.user) == null ? void 0 : _a.name)} class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"><div class="flex-1 min-w-0"><h4 class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(__props.member.member_name || ((_b = __props.member.user) == null ? void 0 : _b.name))}</h4><p class="text-sm text-gray-500 dark:text-gray-400 truncate flex flex-wrap items-center gap-1">`);
      if (__props.member.order_number) {
        _push(`<span class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-xs font-mono font-bold text-blue-700 dark:text-blue-300"> No. ${ssrInterpolate(__props.member.order_number)}</span>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.member.member_code) {
        _push(`<span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs font-mono font-medium text-gray-600 dark:text-gray-300">${ssrInterpolate(__props.member.member_code)}</span>`);
      } else {
        _push(`<!---->`);
      }
      if (!__props.member.order_number && !__props.member.member_code) {
        _push(`<span>@${ssrInterpolate((_c = __props.member.user) == null ? void 0 : _c.username)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</p><div class="mt-1"><span class="${ssrRenderClass(["inline-flex items-center px-2 py-0.5 rounded textxs font-medium border", unref(statusBadge).color])}">${ssrInterpolate(unref(statusBadge).text)}</span></div><p class="text-xs text-gray-400 mt-1"> \u0E40\u0E02\u0E49\u0E32\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14: ${ssrInterpolate(formatDate(__props.member.last_activity))}</p></div><div class="relative w-16 h-16 flex-shrink-0"><svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-200 dark:text-gray-700"></circle><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="${ssrRenderClass(getProgressTextColor(unref(overallProgress)))}"${ssrRenderAttr("stroke-dasharray", `${unref(overallProgress) * 0.97} 100`)}></circle></svg><div class="absolute inset-0 flex flex-col items-center justify-center"><span class="${ssrRenderClass([getProgressTextColor(unref(overallProgress)), "text-sm font-bold"])}">${ssrInterpolate(((_d = __props.member.scores) == null ? void 0 : _d.grade_name) || "-")}</span><span class="text-[10px] text-gray-400">${ssrInterpolate(unref(overallProgress))}%</span></div></div></div><div class="mt-4 space-y-3"><div><div class="flex items-center justify-between mb-1"><span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </span><span class="${ssrRenderClass([getProgressTextColor(__props.member.lessons_progress || 0), "text-sm font-medium"])}">${ssrInterpolate(__props.member.lessons_completed || 0)}/${ssrInterpolate(__props.member.total_lessons || 0)}</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"><div class="${ssrRenderClass([getProgressColor(__props.member.lessons_progress || 0), "h-full rounded-full transition-all duration-500"])}" style="${ssrRenderStyle({ width: `${__props.member.lessons_progress || 0}%` })}"></div></div></div><div><div class="flex items-center justify-between mb-1"><span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:document-text-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 </span><span class="${ssrRenderClass([getProgressTextColor(__props.member.assignments_progress || 0), "text-sm font-medium"])}">${ssrInterpolate(__props.member.assignments_completed || 0)}/${ssrInterpolate(__props.member.total_assignments || 0)}</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"><div class="${ssrRenderClass([getProgressColor(__props.member.assignments_progress || 0), "h-full rounded-full transition-all duration-500"])}" style="${ssrRenderStyle({ width: `${__props.member.assignments_progress || 0}%` })}"></div></div></div><div><div class="flex items-center justify-between mb-1"><span class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:quiz-new-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A </span><span class="${ssrRenderClass([getProgressTextColor(__props.member.quizzes_progress || 0), "text-sm font-medium"])}">${ssrInterpolate(__props.member.quizzes_completed || 0)}/${ssrInterpolate(__props.member.total_quizzes || 0)}</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"><div class="${ssrRenderClass([getProgressColor(__props.member.quizzes_progress || 0), "h-full rounded-full transition-all duration-500"])}" style="${ssrRenderStyle({ width: `${__props.member.quizzes_progress || 0}%` })}"></div></div></div></div><div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-3 gap-2 text-center"><div><div class="${ssrRenderClass([getProgressTextColor(__props.member.attendance_rate || 0), "text-lg font-semibold"])}">${ssrInterpolate(__props.member.attendance_rate || 0)}% </div><p class="text-xs text-gray-400">\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 (${ssrInterpolate(__props.member.attendance_present || 0)}/${ssrInterpolate(__props.member.total_attendance || 0)})</p></div><div><div class="text-lg font-semibold text-blue-600 dark:text-blue-400">${ssrInterpolate(((_e = __props.member.scores) == null ? void 0 : _e.total_score) || 0)}</div><p class="text-xs text-gray-400">\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21</p></div><div><div class="${ssrRenderClass([getProgressTextColor(unref(overallProgress)), "text-lg font-semibold"])}">${ssrInterpolate(((_f = __props.member.scores) == null ? void 0 : _f.grade_name) || "-")}</div><p class="text-xs text-gray-400">\u0E40\u0E01\u0E23\u0E14</p></div></div></div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/ProgressCard.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 animate-pulse" }, _attrs))}><div class="flex items-start gap-3"><div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600"></div><div class="flex-1 min-w-0 space-y-2"><div class="h-4 w-3/4 rounded bg-gray-300 dark:bg-gray-600"></div><div class="h-3 w-1/2 rounded bg-gray-300 dark:bg-gray-600"></div><div class="h-2 w-2/3 rounded bg-gray-200 dark:bg-gray-700 mt-1"></div></div><div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div></div><div class="mt-4 space-y-3"><div><div class="flex justify-between mb-1"><div class="h-3 w-16 rounded bg-gray-200 dark:bg-gray-700"></div><div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div></div><div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div></div><div><div class="flex justify-between mb-1"><div class="h-3 w-20 rounded bg-gray-200 dark:bg-gray-700"></div><div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div></div><div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div></div><div><div class="flex justify-between mb-1"><div class="h-3 w-16 rounded bg-gray-200 dark:bg-gray-700"></div><div class="h-3 w-8 rounded bg-gray-200 dark:bg-gray-700"></div></div><div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"></div></div></div><div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 grid grid-cols-3 gap-2"><div class="text-center space-y-1"><div class="h-5 w-10 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div><div class="h-2 w-12 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div></div><div class="text-center space-y-1"><div class="h-5 w-10 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div><div class="h-2 w-14 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div></div><div class="text-center space-y-1"><div class="h-5 w-10 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div><div class="h-2 w-12 mx-auto rounded bg-gray-200 dark:bg-gray-700"></div></div></div></div>`);
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/ProgressCardSkeleton.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const ProgressCardSkeleton = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "ProgressList",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  setup(__props) {
    const props = __props;
    const api = useApi();
    const members = ref([]);
    const loading = ref(true);
    const searchQuery = ref("");
    const sortBy = ref("order_number");
    const sortOrder = ref("asc");
    const showDetailsModal = ref(false);
    const selectedMember = ref(null);
    const memberDetails = ref(null);
    const groups = ref([]);
    const activeTab = ref("all");
    const viewMode = ref("grid");
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
      from: 0,
      to: 0
    });
    const stats = ref({
      avg: 0,
      min: 0,
      max: 0,
      completed: 0,
      total: 0
    });
    const fetchProgress = async (page = 1) => {
      loading.value = true;
      try {
        const params = {
          page,
          per_page: pagination.value.per_page,
          sort: sortBy.value,
          order: sortOrder.value
        };
        if (activeTab.value !== "all") {
          params.group_id = activeTab.value;
        }
        if (searchQuery.value) {
          params.search = searchQuery.value;
        }
        const response = await api.get(`/api/courses/${props.courseId}/progress`, { params });
        if (response.courseMembersProgress) {
          members.value = response.courseMembersProgress.map((item) => {
            var _a, _b;
            return {
              // Include root-level properties (lessons_completed, overall_progress, etc.)
              lessons_completed: item.lessons_completed,
              total_lessons: item.total_lessons,
              lessons_progress: item.lessons_progress,
              assignments_completed: item.assignments_completed,
              total_assignments: item.total_assignments,
              assignments_progress: item.assignments_progress,
              quizzes_completed: item.quizzes_completed,
              total_quizzes: item.total_quizzes,
              quizzes_progress: item.quizzes_progress,
              // Attendance data
              attendance_present: item.attendance_present,
              total_attendance: item.total_attendance,
              attendance_rate: item.attendance_rate,
              overall_progress: item.overall_progress,
              // Spread existing structures
              ...item.progress,
              ...item.member,
              scores: {
                ...item.scores,
                original_bonus: ((_a = item.scores) == null ? void 0 : _a.bonus_points) || 0,
                original_edited_grade: (_b = item.scores) == null ? void 0 : _b.edited_grade
                // Store original to detect changes
              }
            };
          });
        } else {
          members.value = [];
        }
        groups.value = response.groups || [];
        if (response.pagination) {
          pagination.value = response.pagination;
        }
        if (response.stats) {
          stats.value = {
            ...stats.value,
            ...response.stats
            // Calculate pseudo-stats if backend doesn't provide all (Backen provides total/completed)
            // Backend doesn't provide avg/min/max yet. We might display 0 or hide them.
            // Wait, existing UI shows avg/min/max. Backend implementation didn't calculate them.
            // I should hide them or mock them for now until backend supports them?
            // Actually, backend controller lines 411+ added 'stats' => [ 'total', 'completed' ].
            // It did NOT add 'avg', 'min', 'max'.
            // I'll set them to 0 or '-' to avoid errors.
          };
        }
      } catch (error) {
        console.error("Error fetching progress:", error);
      } finally {
        loading.value = false;
      }
    };
    watch(activeTab, () => {
      fetchProgress(1);
    });
    let searchTimeout;
    watch(searchQuery, (newVal) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchProgress(1);
      }, 500);
    });
    const viewMemberDetails = async (member) => {
      selectedMember.value = member;
      showDetailsModal.value = true;
      try {
        const response = await api.get(`/api/courses/${props.courseId}/members/${member.id}/progress`);
        memberDetails.value = response;
      } catch (error) {
        console.error("Error fetching member details:", error);
        memberDetails.value = null;
      }
    };
    const updateMemberCode = async (member) => {
      try {
        await api.patch(`/api/courses/${props.courseId}/members/${member.id}/member-code`, {
          member_code: member.member_code
        });
      } catch (error) {
        console.error("Error updating member code:", error);
      }
    };
    const updateOrderNumber = async (member) => {
      try {
        await api.patch(`/api/courses/${props.courseId}/members/${member.id}/order-number`, {
          order_number: member.order_number
        });
      } catch (error) {
        console.error("Error updating order number:", error);
      }
    };
    const updateMemberStatus = async (member) => {
      try {
        await api.patch(`/api/courses/${props.courseId}/members/${member.id}/update`, {
          course_member_status: member.course_member_status
        });
      } catch (error) {
        console.error("Error updating member status:", error);
      }
    };
    const updateMemberProfile = async (member) => {
      try {
        await api.patch(`/api/courses/${props.courseId}/members/${member.id}/update`, {
          member_name: member.member_name,
          member_email: member.member_email
        });
      } catch (error) {
        console.error("Error updating member profile:", error);
      }
    };
    const gradeDistribution = computed(() => {
      const distribution = { A: 0, "B+": 0, B: 0, "C+": 0, C: 0, "D+": 0, D: 0, F: 0 };
      members.value.forEach((m) => {
        var _a;
        const grade = ((_a = m.scores) == null ? void 0 : _a.grade_name) || "F";
        if (distribution.hasOwnProperty(grade)) {
          distribution[grade]++;
        }
      });
      return distribution;
    });
    const topPerformers = ref([]);
    ref(false);
    const atRiskStudents = computed(() => {
      return members.value.filter((m) => (m.overall_progress || 0) < 50);
    });
    const averageProgress = computed(() => {
      if (members.value.length === 0) return 0;
      const total = members.value.reduce((sum, m) => sum + (m.overall_progress || 0), 0);
      return Math.round(total / members.value.length);
    });
    const passRate = computed(() => {
      if (members.value.length === 0) return 0;
      const passed = members.value.filter((m) => (m.overall_progress || 0) >= 50).length;
      return Math.round(passed / members.value.length * 100);
    });
    const getAvatarUrl = (user, index = 0) => {
      var _a;
      if (user == null ? void 0 : user.avatar) return user.avatar;
      const colorIndex = (user == null ? void 0 : user.id) || index || ((_a = user == null ? void 0 : user.name) == null ? void 0 : _a.length) || 0;
      const textColors = [
        "f59e0b",
        // amber-500
        "64748b",
        // slate-500
        "f97316",
        // orange-500
        "0ea5e9",
        // sky-500
        "10b981",
        // emerald-500
        "8b5cf6",
        // violet-500
        "f43f5e",
        // rose-500
        "14b8a6",
        // teal-500
        "6366f1",
        // indigo-500
        "06b6d4"
        // cyan-500
      ];
      const color = textColors[colorIndex % textColors.length];
      return `https://ui-avatars.com/api/?name=${encodeURIComponent((user == null ? void 0 : user.name) || "User")}&background=eff6ff&color=${color}&font-size=0.4`;
    };
    const getRankColor = (index) => {
      const colors = [
        "bg-amber-500",
        // 1st - ทองนุ่มนวล
        "bg-slate-400",
        // 2nd - เงินนุ่มนวล
        "bg-orange-400",
        // 3rd - ทองแดงนุ่มนวล
        "bg-sky-400",
        // 4th - ฟ้านุ่มนวล
        "bg-emerald-400",
        // 5th - เขียวนุ่มนวล
        "bg-violet-400",
        // 6th - ม่วงนุ่มนวล
        "bg-rose-400",
        // 7th - ชมพูนุ่มนวล
        "bg-teal-400",
        // 8th - เขียวน้ำทะเลนุ่มนวล
        "bg-indigo-400",
        // 9th - คราม นุ่มนวล
        "bg-cyan-400"
        // 10th - ฟ้าอมเขียวนุ่มนวล
      ];
      return colors[index] || "bg-slate-400";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_DialogModal = _sfc_main$4;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"><div><h3 class="text-lg font-semibold text-gray-900 dark:text-white"> \u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E02\u0E2D\u0E07\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><p class="text-sm text-gray-500 dark:text-gray-400"> \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(unref(pagination).total)} \u0E04\u0E19 </p></div></div>`);
      if (unref(members).length > 0) {
        _push(`<div class="space-y-4"><div class="grid grid-cols-2 md:grid-cols-4 gap-4"><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center gap-3"><div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-community-24-filled",
          class: "w-6 h-6 text-blue-600 dark:text-blue-400"
        }, null, _parent));
        _push(`</div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p><div class="text-xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(stats).total)}</div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center gap-3"><div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:data-trending-24-filled",
          class: "w-6 h-6 text-indigo-600 dark:text-indigo-400"
        }, null, _parent));
        _push(`</div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22</p><div class="text-xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(averageProgress))}%</div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center gap-3"><div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-starburst-24-filled",
          class: "w-6 h-6 text-green-600 dark:text-green-400"
        }, null, _parent));
        _push(`</div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E1C\u0E48\u0E32\u0E19\u0E40\u0E01\u0E13\u0E11\u0E4C (&gt;50%)</p><div class="text-xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(passRate))}%</div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center gap-3"><div class="p-2 bg-teal-50 dark:bg-teal-900/20 rounded-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:trophy-24-filled",
          class: "w-6 h-6 text-teal-600 dark:text-teal-400"
        }, null, _parent));
        _push(`</div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A\u0E41\u0E25\u0E49\u0E27</p><div class="text-xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(stats).completed)}</div></div></div></div></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-4"><div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-6 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chart-person-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` \u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E08\u0E32\u0E22\u0E40\u0E01\u0E23\u0E14 (Grade Distribution) </h4><div class="flex items-end justify-between h-40 gap-2 px-2"><!--[-->`);
        ssrRenderList(unref(gradeDistribution), (count, grade) => {
          _push(`<div class="flex-1 flex flex-col items-center justify-end h-full gap-2 group relative"><div class="absolute -top-8 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">${ssrInterpolate(count)} \u0E04\u0E19 </div><div class="w-full max-w-[40px] bg-blue-100 dark:bg-blue-900/30 rounded-t-md relative overflow-hidden transition-all duration-500 hover:bg-blue-200 dark:hover:bg-blue-800/50" style="${ssrRenderStyle({ height: `${Math.max(count / unref(members).length * 100, 4)}%` })}">`);
          if (count > 0) {
            _push(`<div class="absolute bottom-0 left-0 right-0 bg-blue-500/20 h-full w-full"></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="text-sm font-medium text-gray-600 dark:text-gray-400">${ssrInterpolate(grade)}</div></div>`);
        });
        _push(`<!--]--></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col"><h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:ribbon-star-24-filled",
          class: "w-5 h-5 text-yellow-500"
        }, null, _parent));
        _push(` Top Performers </h4><div class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar"><!--[-->`);
        ssrRenderList(unref(topPerformers), (student, index) => {
          var _a, _b, _c;
          _push(`<div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"><div class="relative"><img${ssrRenderAttr("src", getAvatarUrl(student.user, index))} class="w-10 h-10 rounded-full object-cover border border-gray-200">`);
          if (index < 3) {
            _push(`<div class="${ssrRenderClass([getRankColor(index), "absolute -top-1 -left-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-sm border border-white"])}">${ssrInterpolate(index + 1)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate((_a = student.user) == null ? void 0 : _a.name)}</p><p class="text-xs text-gray-500 truncate">${ssrInterpolate(((_b = student.scores) == null ? void 0 : _b.grade_name) || "-")} \u2022 ${ssrInterpolate(student.overall_progress || 0)}%</p></div><div class="text-sm font-bold text-green-600">${ssrInterpolate(((_c = student.scores) == null ? void 0 : _c.total_score) || 0)}</div></div>`);
        });
        _push(`<!--]--></div></div></div>`);
        if (unref(atRiskStudents).length > 0) {
          _push(`<div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-xl p-4 flex items-start gap-4"><div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-full shrink-0">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:alert-24-filled",
            class: "w-6 h-6 text-red-600 dark:text-red-400"
          }, null, _parent));
          _push(`</div><div class="flex-1"><h4 class="font-bold text-red-700 dark:text-red-400 text-sm">\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E27\u0E32\u0E21\u0E0A\u0E48\u0E27\u0E22\u0E40\u0E2B\u0E25\u0E37\u0E2D (${ssrInterpolate(unref(atRiskStudents).length)} \u0E04\u0E19)</h4><p class="text-xs text-red-600/80 dark:text-red-400/80 mt-1 mb-2">\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E19\u0E49\u0E2D\u0E22\u0E01\u0E27\u0E48\u0E32 50% \u0E2B\u0E23\u0E37\u0E2D\u0E04\u0E30\u0E41\u0E19\u0E19\u0E15\u0E48\u0E33\u0E01\u0E27\u0E48\u0E32\u0E40\u0E01\u0E13\u0E11\u0E4C</p><div class="flex flex-wrap gap-2"><!--[-->`);
          ssrRenderList(unref(atRiskStudents).slice(0, 5), (student) => {
            var _a;
            _push(`<div class="flex items-center gap-1.5 bg-white dark:bg-gray-800 px-2 py-1 rounded-md border border-red-100 dark:border-red-900/30 shadow-sm cursor-pointer hover:shadow-md transition-shadow"><img${ssrRenderAttr("src", getAvatarUrl(student.user, student.id))} class="w-5 h-5 rounded-full"><span class="text-xs font-medium text-gray-700 dark:text-gray-300">${ssrInterpolate((_a = student.user) == null ? void 0 : _a.name)}</span><span class="text-xs text-red-500 font-bold">(${ssrInterpolate(student.overall_progress)}%)</span></div>`);
          });
          _push(`<!--]-->`);
          if (unref(atRiskStudents).length > 5) {
            _push(`<div class="px-2 py-1 text-xs text-red-600 font-medium"> + \u0E2D\u0E35\u0E01 ${ssrInterpolate(unref(atRiskStudents).length - 5)} \u0E04\u0E19 </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.isCourseAdmin && (unref(groups).length > 0 || unref(members).some((m) => !m.group_id))) {
        _push(`<div class="mb-4"><ul class="flex flex-wrap items-center border-b bg-gray-50 dark:bg-gray-800 rounded-t-lg"><li class="mr-1"><button class="${ssrRenderClass(["px-4 py-2 text-sm font-medium rounded-t-lg", unref(activeTab) === "all" ? "bg-white dark:bg-gray-900 text-blue-600 border-t border-l border-r border-gray-200 dark:border-gray-700" : "text-gray-500 hover:text-gray-700 dark:text-gray-400"])}"> \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 (${ssrInterpolate(unref(members).length)}) </button></li><!--[-->`);
        ssrRenderList(unref(groups), (group) => {
          _push(`<li class="mr-1"><button class="${ssrRenderClass(["px-4 py-2 text-sm font-medium rounded-t-lg", unref(activeTab) === group.id ? "bg-white dark:bg-gray-900 text-blue-600 border-t border-l border-r border-gray-200 dark:border-gray-700" : "text-gray-500 hover:text-gray-700 dark:text-gray-400"])}">${ssrInterpolate(group.name)}</button></li>`);
        });
        _push(`<!--]-->`);
        if (unref(members).some((m) => !m.group_id)) {
          _push(`<li class="mr-1"><button class="${ssrRenderClass(["px-4 py-2 text-sm font-medium rounded-t-lg", unref(activeTab) === "ungrouped" ? "bg-white dark:bg-gray-900 text-blue-600 border-t border-l border-r border-gray-200 dark:border-gray-700" : "text-gray-500 hover:text-gray-700 dark:text-gray-400"])}"> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21 </button></li>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</ul></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex flex-col sm:flex-row gap-3"><div class="relative flex-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", unref(searchQuery))} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32 \u0E0A\u0E37\u0E48\u0E2D, \u0E40\u0E25\u0E02\u0E17\u0E35\u0E48, Email..." class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></div><div class="flex gap-2"><div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1 mr-2"><button class="${ssrRenderClass(["p-1.5 rounded-md transition-colors", unref(viewMode) === "grid" ? "bg-white dark:bg-gray-600 shadow text-blue-600" : "text-gray-500 hover:text-gray-700"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:grid-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button><button class="${ssrRenderClass(["p-1.5 rounded-md transition-colors", unref(viewMode) === "table" ? "bg-white dark:bg-gray-600 shadow text-blue-600" : "text-gray-500 hover:text-gray-700"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:table-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button></div><button class="${ssrRenderClass([
        "px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors",
        unref(sortBy) === "order_number" ? "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" : "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}"> \u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 `);
      if (unref(sortBy) === "order_number") {
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(sortOrder) === "asc" ? "fluent:arrow-up-24-regular" : "fluent:arrow-down-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</button><button class="${ssrRenderClass([
        "px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors",
        unref(sortBy) === "name" ? "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" : "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}"> \u0E0A\u0E37\u0E48\u0E2D `);
      if (unref(sortBy) === "name") {
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(sortOrder) === "asc" ? "fluent:arrow-up-24-regular" : "fluent:arrow-down-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</button><button class="${ssrRenderClass([
        "px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors",
        unref(sortBy) === "progress" ? "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" : "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}"> \u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32 `);
      if (unref(sortBy) === "progress") {
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(sortOrder) === "asc" ? "fluent:arrow-up-24-regular" : "fluent:arrow-down-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</button><button class="${ssrRenderClass([
        "px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors",
        unref(sortBy) === "last_activity" ? "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" : "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}"> \u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 `);
      if (unref(sortBy) === "last_activity") {
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(sortOrder) === "asc" ? "fluent:arrow-up-24-regular" : "fluent:arrow-down-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</button>`);
      if (__props.isCourseAdmin) {
        _push(`<button class="inline-flex items-center gap-2 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-download-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span>\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 Excel</span></button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (unref(loading)) {
        _push(`<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(ssrRenderComponent(ProgressCardSkeleton, { key: i }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else if (unref(members).length > 0 && unref(viewMode) === "grid") {
        _push(`<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><!--[-->`);
        ssrRenderList(unref(members), (member) => {
          _push(ssrRenderComponent(_sfc_main$3, {
            key: member.id,
            member,
            "is-course-admin": __props.isCourseAdmin,
            onViewDetails: viewMemberDetails
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else if (unref(members).length > 0 && unref(viewMode) === "table") {
        _push(`<div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"><table class="w-full text-sm text-left text-gray-500 dark:text-gray-400"><thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300"><tr><th scope="col" class="px-4 py-3 min-w-[50px]">#</th><th scope="col" class="px-4 py-3 min-w-[60px]">\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48</th><th scope="col" class="px-4 py-3 min-w-[80px]">\u0E23\u0E2B\u0E31\u0E2A</th><th scope="col" class="px-4 py-3 min-w-[200px]">\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</th><th scope="col" class="px-4 py-3 text-center">\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</th><th scope="col" class="px-4 py-3 text-center">\u0E07\u0E32\u0E19</th><th scope="col" class="px-4 py-3 text-center">\u0E17\u0E14\u0E2A\u0E2D\u0E1A</th><th scope="col" class="px-6 py-3 min-w-[120px]"> \u0E1E\u0E34\u0E40\u0E28\u0E29 </th><th scope="col" class="px-6 py-3 whitespace-nowrap"> \u0E23\u0E27\u0E21 </th><th scope="col" class="px-4 py-3 text-center">\u0E40\u0E01\u0E23\u0E14</th><th scope="col" class="px-4 py-3 text-center min-w-[120px]">\u0E40\u0E01\u0E23\u0E14\u0E41\u0E01\u0E49</th><th scope="col" class="px-4 py-3 text-center">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th scope="col" class="px-4 py-3 text-center">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody><!--[-->`);
        ssrRenderList(unref(members), (member, index) => {
          var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m;
          _push(`<tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3">${ssrInterpolate(index + 1)}</td><td class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">${ssrInterpolate(member.order_number || "-")}</td><td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">${ssrInterpolate(member.member_code || "-")}</td><td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap"><div class="flex items-center gap-3"><img${ssrRenderAttr("src", getAvatarUrl(member.user, index))} class="w-8 h-8 rounded-full" alt=""><div class="flex flex-col"><span>${ssrInterpolate(member.member_name || ((_a = member.user) == null ? void 0 : _a.name))}</span><span class="text-xs text-gray-400">${ssrInterpolate(member.member_email || ((_b = member.user) == null ? void 0 : _b.email) || "-")}</span></div></div></td><td class="px-4 py-3 text-center"><div class="flex flex-col items-center"><span class="text-xs text-gray-400">\u0E07\u0E32\u0E19: ${ssrInterpolate(((_c = member.scores) == null ? void 0 : _c.lesson_assignments) || 0)}</span><span class="text-xs text-gray-400">\u0E2A\u0E2D\u0E1A: ${ssrInterpolate(((_d = member.scores) == null ? void 0 : _d.lesson_quizzes) || 0)}</span></div></td><td class="px-4 py-3 text-center">${ssrInterpolate(((_e = member.scores) == null ? void 0 : _e.course_assignments) || 0)}</td><td class="px-4 py-3 text-center">${ssrInterpolate(((_f = member.scores) == null ? void 0 : _f.course_quizzes) || 0)}</td><td class="px-6 py-4">`);
          if (__props.isCourseAdmin) {
            _push(`<div class="flex items-center gap-1"><input type="number"${ssrRenderAttr("value", member.scores.bonus_points)} class="w-16 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">`);
            if (member.scores.bonus_points !== member.scores.original_bonus) {
              _push(`<button class="p-1 text-green-600 hover:bg-green-100 rounded dark:hover:bg-green-900/30" title="\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg></button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<span class="text-orange-600 font-medium">${ssrInterpolate(((_g = member.scores) == null ? void 0 : _g.bonus_points) || 0)}</span>`);
          }
          _push(`</td><td class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400">${ssrInterpolate(((_h = member.scores) == null ? void 0 : _h.total_score) || 0)}</td><td class="${ssrRenderClass([{ "text-green-600 dark:text-green-400": (((_i = member.scores) == null ? void 0 : _i.grade_progress) || 0) >= 2, "text-red-600 dark:text-red-400": (((_j = member.scores) == null ? void 0 : _j.grade_progress) || 0) < 1 }, "px-4 py-3 text-center font-bold text-lg"])}">${ssrInterpolate(((_k = member.scores) == null ? void 0 : _k.grade_name) || "-")} <span class="text-xs text-gray-400 font-normal">(${ssrInterpolate(((_l = member.scores) == null ? void 0 : _l.grade_progress) || 0)})</span></td><td class="px-4 py-3 text-center">`);
          if (__props.isCourseAdmin) {
            _push(`<div class="flex items-center justify-center gap-1"><select class="w-20 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><option${ssrRenderAttr("value", null)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, null) : ssrLooseEqual(member.scores.edited_grade, null)) ? " selected" : ""}>-</option><option${ssrRenderAttr("value", 1)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 1) : ssrLooseEqual(member.scores.edited_grade, 1)) ? " selected" : ""}>1 (D)</option><option${ssrRenderAttr("value", 1.5)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 1.5) : ssrLooseEqual(member.scores.edited_grade, 1.5)) ? " selected" : ""}>1.5 (D+)</option><option${ssrRenderAttr("value", 2)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 2) : ssrLooseEqual(member.scores.edited_grade, 2)) ? " selected" : ""}>2 (C)</option><option${ssrRenderAttr("value", 2.5)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 2.5) : ssrLooseEqual(member.scores.edited_grade, 2.5)) ? " selected" : ""}>2.5 (C+)</option><option${ssrRenderAttr("value", 3)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 3) : ssrLooseEqual(member.scores.edited_grade, 3)) ? " selected" : ""}>3 (B)</option><option${ssrRenderAttr("value", 3.5)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 3.5) : ssrLooseEqual(member.scores.edited_grade, 3.5)) ? " selected" : ""}>3.5 (B+)</option><option${ssrRenderAttr("value", 4)}${ssrIncludeBooleanAttr(Array.isArray(member.scores.edited_grade) ? ssrLooseContain(member.scores.edited_grade, 4) : ssrLooseEqual(member.scores.edited_grade, 4)) ? " selected" : ""}>4 (A)</option></select>`);
            if (member.scores.edited_grade !== member.scores.original_edited_grade) {
              _push(`<button class="p-1 text-blue-600 hover:bg-blue-100 rounded dark:hover:bg-blue-900/30" title="\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E40\u0E01\u0E23\u0E14\u0E41\u0E01\u0E49"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg></button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<span class="font-medium">${ssrInterpolate(((_m = member.scores) == null ? void 0 : _m.edited_grade) || "-")}</span>`);
          }
          _push(`</td><td class="px-4 py-3 text-center">`);
          if (member.course_member_status === 1) {
            _push(`<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">\u0E1B\u0E01\u0E15\u0E34</span>`);
          } else {
            _push(`<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">\u0E1E\u0E31\u0E01</span>`);
          }
          _push(`</td><td class="px-4 py-3 text-center"><button class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-xs"> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </button></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(members).length > 0) {
        _push(`<div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6"><span class="text-sm text-gray-500 dark:text-gray-400"> \u0E41\u0E2A\u0E14\u0E07 ${ssrInterpolate(unref(pagination).from)}-${ssrInterpolate(unref(pagination).to)} \u0E08\u0E32\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(unref(pagination).total)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </span><div class="flex gap-2"><button${ssrIncludeBooleanAttr(unref(pagination).current_page === 1) ? " disabled" : ""} class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700"> \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </button><div class="flex items-center gap-1"><!--[-->`);
        ssrRenderList(unref(pagination).last_page, (page) => {
          _push(`<!--[-->`);
          if (Math.abs(page - unref(pagination).current_page) <= 2 || page === 1 || page === unref(pagination).last_page) {
            _push(`<button class="${ssrRenderClass(["px-3 py-1 text-sm border rounded-lg", page === unref(pagination).current_page ? "bg-blue-600 text-white border-blue-600" : "hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"])}">${ssrInterpolate(page)}</button>`);
          } else if (Math.abs(page - unref(pagination).current_page) === 3) {
            _push(`<span class="px-2">...</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--]-->`);
        });
        _push(`<!--]--></div><button${ssrIncludeBooleanAttr(unref(pagination).current_page === unref(pagination).last_page) ? " disabled" : ""} class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700"> \u0E16\u0E31\u0E14\u0E44\u0E1B </button></div></div>`);
      } else {
        _push(`<div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:data-area-24-regular",
          class: "w-16 h-16 mx-auto text-gray-300 dark:text-gray-600"
        }, null, _parent));
        _push(`<h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">${ssrInterpolate(unref(searchQuery) ? "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32")}</h3><p class="mt-2 text-gray-500 dark:text-gray-400">${ssrInterpolate(unref(searchQuery) ? "\u0E25\u0E2D\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E04\u0E33\u0E2D\u0E37\u0E48\u0E19" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E19\u0E35\u0E49")}</p></div>`);
      }
      _push(ssrRenderComponent(_component_DialogModal, {
        show: unref(showDetailsModal),
        onClose: ($event) => showDetailsModal.value = false,
        "max-width": "2xl"
      }, {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d;
          if (_push2) {
            _push2(` \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32 - ${ssrInterpolate((_b = (_a = unref(selectedMember)) == null ? void 0 : _a.user) == null ? void 0 : _b.name)}`);
          } else {
            return [
              createTextVNode(" \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32 - " + toDisplayString((_d = (_c = unref(selectedMember)) == null ? void 0 : _c.user) == null ? void 0 : _d.name), 1)
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m, _n, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x, _y, _z, _A, _B, _C, _D, _E, _F, _G, _H, _I, _J, _K, _L, _M, _N, _O, _P, _Q, _R, _S, _T, _U, _V, _W, _X, _Y, _Z, __, _$, _aa, _ba, _ca, _da, _ea, _fa, _ga, _ha, _ia, _ja, _ka, _la, _ma, _na, _oa, _pa, _qa, _ra, _sa, _ta, _ua, _va;
          if (_push2) {
            if (unref(memberDetails)) {
              _push2(`<div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2"${_scopeId}>`);
              if (__props.isCourseAdmin) {
                _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><div class="flex items-center justify-between mb-4"${_scopeId}><h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:person-edit-24-regular",
                  class: "w-5 h-5 text-blue-500"
                }, null, _parent2, _scopeId));
                _push2(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19 </h4><a${ssrRenderAttr("href", `/Learn/Courses/${__props.courseId}/my-progress?member_id=${unref(selectedMember).id}`)} target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1"${_scopeId}> \u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:open-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(`</a></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D-\u0E2A\u0E01\u0E38\u0E25 (Name)</label><div class="flex flex-col sm:flex-row gap-2"${_scopeId}><input${ssrRenderAttr("value", unref(selectedMember).member_name)} type="text" class="w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D-\u0E2A\u0E01\u0E38\u0E25"${_scopeId}><button class="w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"${_scopeId}> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E2D\u0E35\u0E40\u0E21\u0E25 (Email)</label><div class="flex flex-col sm:flex-row gap-2"${_scopeId}><input${ssrRenderAttr("value", unref(selectedMember).member_email)} type="email" class="w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E2D\u0E35\u0E40\u0E21\u0E25"${_scopeId}><button class="w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"${_scopeId}> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 (No.)</label><div class="flex flex-col sm:flex-row gap-2"${_scopeId}><input${ssrRenderAttr("value", unref(selectedMember).order_number)} type="number" class="w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48"${_scopeId}><button class="w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"${_scopeId}> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27 (Student ID)</label><div class="flex flex-col sm:flex-row gap-2"${_scopeId}><input${ssrRenderAttr("value", unref(selectedMember).member_code)} type="text" class="w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27"${_scopeId}><button class="w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"${_scopeId}> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div><div class="md:col-span-2"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</label><select class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500"${_scopeId}><option${ssrRenderAttr("value", 1)}${ssrIncludeBooleanAttr(Array.isArray(unref(selectedMember).course_member_status) ? ssrLooseContain(unref(selectedMember).course_member_status, 1) : ssrLooseEqual(unref(selectedMember).course_member_status, 1)) ? " selected" : ""}${_scopeId}>\u0E1B\u0E01\u0E15\u0E34 (Active)</option><option${ssrRenderAttr("value", 0)}${ssrIncludeBooleanAttr(Array.isArray(unref(selectedMember).course_member_status) ? ssrLooseContain(unref(selectedMember).course_member_status, 0) : ssrLooseEqual(unref(selectedMember).course_member_status, 0)) ? " selected" : ""}${_scopeId}>\u0E1E\u0E31\u0E01\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 (Suspended)</option></select></div></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl"${_scopeId}><div class="text-center"${_scopeId}><div class="relative w-16 h-16 mx-auto"${_scopeId}><svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36"${_scopeId}><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-200 dark:text-gray-600"${_scopeId}></circle><circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-blue-500"${ssrRenderAttr("stroke-dasharray", `${((_a = unref(selectedMember)) == null ? void 0 : _a.overall_progress) || 0} 100`)}${_scopeId}></circle></svg><div class="absolute inset-0 flex items-center justify-center"${_scopeId}><span class="text-lg font-bold text-blue-600 dark:text-blue-400"${_scopeId}>${ssrInterpolate(((_b = unref(selectedMember)) == null ? void 0 : _b.overall_progress) || 0)}%</span></div></div><p class="text-xs text-gray-500 mt-2"${_scopeId}>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32</p></div><div class="text-center"${_scopeId}><div class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center"${_scopeId}><span class="text-2xl font-bold text-green-600 dark:text-green-400"${_scopeId}>${ssrInterpolate(((_d = (_c = unref(selectedMember)) == null ? void 0 : _c.scores) == null ? void 0 : _d.grade_name) || "-")}</span></div><p class="text-xs text-gray-500 mt-2"${_scopeId}>\u0E40\u0E01\u0E23\u0E14 (${ssrInterpolate(((_f = (_e = unref(selectedMember)) == null ? void 0 : _e.scores) == null ? void 0 : _f.grade_progress) || 0)})</p></div><div class="text-center"${_scopeId}><div class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center"${_scopeId}><span class="text-xl font-bold text-orange-600 dark:text-orange-400"${_scopeId}>${ssrInterpolate(((_g = unref(selectedMember)) == null ? void 0 : _g.attendance_rate) || 0)}%</span></div><p class="text-xs text-gray-500 mt-2"${_scopeId}>\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 (${ssrInterpolate(((_h = unref(selectedMember)) == null ? void 0 : _h.attendance_present) || 0)}/${ssrInterpolate(((_i = unref(selectedMember)) == null ? void 0 : _i.total_attendance) || 0)})</p></div><div class="text-center"${_scopeId}><div class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center"${_scopeId}><span class="text-xl font-bold text-purple-600 dark:text-purple-400"${_scopeId}>${ssrInterpolate(((_k = (_j = unref(selectedMember)) == null ? void 0 : _j.scores) == null ? void 0 : _k.total_score) || 0)}</span></div><p class="text-xs text-gray-500 mt-2"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21</p></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"${_scopeId}><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><div class="flex items-center justify-between mb-2"${_scopeId}><span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-regular",
                class: "w-4 h-4 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </span><span class="text-sm font-bold text-blue-600 dark:text-blue-400"${_scopeId}>${ssrInterpolate(((_l = unref(selectedMember)) == null ? void 0 : _l.lessons_completed) || 0)}/${ssrInterpolate(((_m = unref(selectedMember)) == null ? void 0 : _m.total_lessons) || 0)}</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"${_scopeId}><div class="h-full rounded-full bg-blue-500 transition-all duration-500" style="${ssrRenderStyle({ width: `${((_n = unref(selectedMember)) == null ? void 0 : _n.lessons_progress) || 0}%` })}"${_scopeId}></div></div></div><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><div class="flex items-center justify-between mb-2"${_scopeId}><span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:document-text-24-regular",
                class: "w-4 h-4 text-orange-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E07\u0E32\u0E19 </span><span class="text-sm font-bold text-orange-600 dark:text-orange-400"${_scopeId}>${ssrInterpolate(((_o = unref(selectedMember)) == null ? void 0 : _o.assignments_completed) || 0)}/${ssrInterpolate(((_p = unref(selectedMember)) == null ? void 0 : _p.total_assignments) || 0)}</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"${_scopeId}><div class="h-full rounded-full bg-orange-500 transition-all duration-500" style="${ssrRenderStyle({ width: `${((_q = unref(selectedMember)) == null ? void 0 : _q.assignments_progress) || 0}%` })}"${_scopeId}></div></div></div><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><div class="flex items-center justify-between mb-2"${_scopeId}><span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:quiz-new-24-regular",
                class: "w-4 h-4 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E17\u0E14\u0E2A\u0E2D\u0E1A </span><span class="text-sm font-bold text-purple-600 dark:text-purple-400"${_scopeId}>${ssrInterpolate(((_r = unref(selectedMember)) == null ? void 0 : _r.quizzes_completed) || 0)}/${ssrInterpolate(((_s = unref(selectedMember)) == null ? void 0 : _s.total_quizzes) || 0)}</span></div><div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden"${_scopeId}><div class="h-full rounded-full bg-purple-500 transition-all duration-500" style="${ssrRenderStyle({ width: `${((_t = unref(selectedMember)) == null ? void 0 : _t.quizzes_progress) || 0}%` })}"${_scopeId}></div></div></div></div><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:chart-multiple-24-regular",
                class: "w-5 h-5 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E04\u0E30\u0E41\u0E19\u0E19 </h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center"${_scopeId}><div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><div class="text-lg font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(((_v = (_u = unref(selectedMember)) == null ? void 0 : _u.scores) == null ? void 0 : _v.lesson_assignments) || 0)}<span class="text-sm font-normal text-gray-500"${_scopeId}>/${ssrInterpolate(((_x = (_w = unref(selectedMember)) == null ? void 0 : _w.scores) == null ? void 0 : _x.max_lesson_assignments) || 0)}</span></div><p class="text-xs text-gray-500"${_scopeId}>\u0E07\u0E32\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p></div><div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><div class="text-lg font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(((_z = (_y = unref(selectedMember)) == null ? void 0 : _y.scores) == null ? void 0 : _z.lesson_quizzes) || 0)}<span class="text-sm font-normal text-gray-500"${_scopeId}>/${ssrInterpolate(((_B = (_A = unref(selectedMember)) == null ? void 0 : _A.scores) == null ? void 0 : _B.max_lesson_quizzes) || 0)}</span></div><p class="text-xs text-gray-500"${_scopeId}>\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p></div><div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><div class="text-lg font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(((_D = (_C = unref(selectedMember)) == null ? void 0 : _C.scores) == null ? void 0 : _D.course_assignments) || 0)}<span class="text-sm font-normal text-gray-500"${_scopeId}>/${ssrInterpolate(((_F = (_E = unref(selectedMember)) == null ? void 0 : _E.scores) == null ? void 0 : _F.max_course_assignments) || 0)}</span></div><p class="text-xs text-gray-500"${_scopeId}>\u0E07\u0E32\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div><div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><div class="text-lg font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(((_H = (_G = unref(selectedMember)) == null ? void 0 : _G.scores) == null ? void 0 : _H.course_quizzes) || 0)}<span class="text-sm font-normal text-gray-500"${_scopeId}>/${ssrInterpolate(((_J = (_I = unref(selectedMember)) == null ? void 0 : _I.scores) == null ? void 0 : _J.max_course_quizzes) || 0)}</span></div><p class="text-xs text-gray-500"${_scopeId}>\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div></div><div class="mt-3 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg"${_scopeId}><span class="text-sm font-medium text-blue-700 dark:text-blue-300"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E1E\u0E34\u0E40\u0E28\u0E29 (Bonus)</span><span class="text-lg font-bold text-blue-600 dark:text-blue-400"${_scopeId}>+${ssrInterpolate(((_L = (_K = unref(selectedMember)) == null ? void 0 : _K.scores) == null ? void 0 : _L.bonus_points) || 0)}</span></div></div><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-regular",
                class: "w-5 h-5 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </h4>`);
              if (unref(memberDetails).lessons && unref(memberDetails).lessons.length > 0) {
                _push2(`<div class="space-y-2"${_scopeId}><!--[-->`);
                ssrRenderList(unref(memberDetails).lessons, (lesson) => {
                  _push2(`<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><span class="text-sm text-gray-700 dark:text-gray-300"${_scopeId}>${ssrInterpolate(lesson.title)}</span><span class="${ssrRenderClass([
                    "text-xs px-2 py-1 rounded-full",
                    lesson.completed ? "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" : "bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                  ])}"${_scopeId}>${ssrInterpolate(lesson.completed ? "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A\u0E41\u0E25\u0E49\u0E27" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21")}</span></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<p class="text-sm text-gray-500 text-center py-4"${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p>`);
              }
              _push2(`</div><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:document-text-24-regular",
                class: "w-5 h-5 text-orange-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 </h4>`);
              if (unref(memberDetails).assignments && unref(memberDetails).assignments.length > 0) {
                _push2(`<div class="space-y-2"${_scopeId}><!--[-->`);
                ssrRenderList(unref(memberDetails).assignments, (assignment) => {
                  _push2(`<div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1 min-w-0"${ssrRenderAttr("title", assignment.title)}${_scopeId}>${ssrInterpolate(assignment.title)}</span><div class="flex items-center gap-2"${_scopeId}>`);
                  if (assignment.graded) {
                    _push2(`<span class="text-sm font-bold text-green-600 dark:text-green-400"${_scopeId}>${ssrInterpolate(assignment.score)}/${ssrInterpolate(assignment.max_score)}</span>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`<span class="${ssrRenderClass([
                    "text-xs px-2 py-1 rounded-full whitespace-nowrap",
                    assignment.status === "graded" ? "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" : assignment.status === "in_review" ? "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400" : assignment.status === "submitted" ? "bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400" : "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                  ])}"${_scopeId}>${ssrInterpolate(assignment.status === "graded" ? "\u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27" : assignment.status === "in_review" ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E15\u0E23\u0E27\u0E08" : assignment.status === "submitted" ? "\u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E2A\u0E48\u0E07")}</span></div></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<p class="text-sm text-gray-500 text-center py-4"${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22</p>`);
              }
              _push2(`</div><div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:quiz-new-24-regular",
                class: "w-5 h-5 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A </h4>`);
              if (unref(memberDetails).quizzes && unref(memberDetails).quizzes.length > 0) {
                _push2(`<div class="space-y-2"${_scopeId}><!--[-->`);
                ssrRenderList(unref(memberDetails).quizzes, (quiz) => {
                  _push2(`<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"${_scopeId}><div class="flex-1"${_scopeId}><span class="text-sm text-gray-700 dark:text-gray-300"${_scopeId}>${ssrInterpolate(quiz.title)}</span>`);
                  if (quiz.attempt_count > 0) {
                    _push2(`<span class="text-xs text-gray-400 ml-2"${_scopeId}> (\u0E17\u0E33 ${ssrInterpolate(quiz.attempt_count)} \u0E04\u0E23\u0E31\u0E49\u0E07) </span>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div><div class="flex items-center gap-2"${_scopeId}>`);
                  if (quiz.completed) {
                    _push2(`<span class="${ssrRenderClass([quiz.passed === false ? "text-red-500" : "text-green-600 dark:text-green-400", "text-sm font-bold"])}"${_scopeId}>${ssrInterpolate(quiz.score)}/${ssrInterpolate(quiz.max_score)}</span>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`<span class="${ssrRenderClass([
                    "text-xs px-2 py-1 rounded-full whitespace-nowrap",
                    quiz.completed ? quiz.passed === false ? "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400" : "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" : "bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                  ])}"${_scopeId}>${ssrInterpolate(quiz.completed ? quiz.passed === false ? "\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19" : "\u0E1C\u0E48\u0E32\u0E19" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E17\u0E33")}</span></div></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<p class="text-sm text-gray-500 text-center py-4"${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</p>`);
              }
              _push2(`</div></div>`);
            } else {
              _push2(`<div class="flex flex-col items-center justify-center py-12"${_scopeId}><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"${_scopeId}></div><p class="text-gray-500"${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
            }
          } else {
            return [
              unref(memberDetails) ? (openBlock(), createBlock("div", {
                key: 0,
                class: "space-y-6 max-h-[70vh] overflow-y-auto pr-2"
              }, [
                __props.isCourseAdmin ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"
                }, [
                  createVNode("div", { class: "flex items-center justify-between mb-4" }, [
                    createVNode("h4", { class: "font-semibold text-gray-900 dark:text-white flex items-center gap-2" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:person-edit-24-regular",
                        class: "w-5 h-5 text-blue-500"
                      }),
                      createTextVNode(" \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19 ")
                    ]),
                    createVNode("a", {
                      href: `/Learn/Courses/${__props.courseId}/my-progress?member_id=${unref(selectedMember).id}`,
                      target: "_blank",
                      class: "text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1"
                    }, [
                      createTextVNode(" \u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 "),
                      createVNode(unref(Icon), {
                        icon: "fluent:open-24-regular",
                        class: "w-4 h-4"
                      })
                    ], 8, ["href"])
                  ]),
                  createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-4" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E0A\u0E37\u0E48\u0E2D-\u0E2A\u0E01\u0E38\u0E25 (Name)"),
                      createVNode("div", { class: "flex flex-col sm:flex-row gap-2" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => unref(selectedMember).member_name = $event,
                          type: "text",
                          class: "w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D-\u0E2A\u0E01\u0E38\u0E25"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, unref(selectedMember).member_name]
                        ]),
                        createVNode("button", {
                          onClick: ($event) => updateMemberProfile(unref(selectedMember)),
                          class: "w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                        }, " \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 ", 8, ["onClick"])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E2D\u0E35\u0E40\u0E21\u0E25 (Email)"),
                      createVNode("div", { class: "flex flex-col sm:flex-row gap-2" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => unref(selectedMember).member_email = $event,
                          type: "email",
                          class: "w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E2D\u0E35\u0E40\u0E21\u0E25"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, unref(selectedMember).member_email]
                        ]),
                        createVNode("button", {
                          onClick: ($event) => updateMemberProfile(unref(selectedMember)),
                          class: "w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                        }, " \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 ", 8, ["onClick"])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 (No.)"),
                      createVNode("div", { class: "flex flex-col sm:flex-row gap-2" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => unref(selectedMember).order_number = $event,
                          type: "number",
                          class: "w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, unref(selectedMember).order_number]
                        ]),
                        createVNode("button", {
                          onClick: ($event) => updateOrderNumber(unref(selectedMember)),
                          class: "w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                        }, " \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 ", 8, ["onClick"])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27 (Student ID)"),
                      createVNode("div", { class: "flex flex-col sm:flex-row gap-2" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => unref(selectedMember).member_code = $event,
                          type: "text",
                          class: "w-full sm:flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, unref(selectedMember).member_code]
                        ]),
                        createVNode("button", {
                          onClick: ($event) => updateMemberCode(unref(selectedMember)),
                          class: "w-full sm:w-auto px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                        }, " \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 ", 8, ["onClick"])
                      ])
                    ]),
                    createVNode("div", { class: "md:col-span-2" }, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => unref(selectedMember).course_member_status = $event,
                        onChange: ($event) => updateMemberStatus(unref(selectedMember)),
                        class: "w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500"
                      }, [
                        createVNode("option", { value: 1 }, "\u0E1B\u0E01\u0E15\u0E34 (Active)"),
                        createVNode("option", { value: 0 }, "\u0E1E\u0E31\u0E01\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 (Suspended)")
                      ], 40, ["onUpdate:modelValue", "onChange"]), [
                        [vModelSelect, unref(selectedMember).course_member_status]
                      ])
                    ])
                  ])
                ])) : createCommentVNode("", true),
                createVNode("div", { class: "grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl" }, [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "relative w-16 h-16 mx-auto" }, [
                      (openBlock(), createBlock("svg", {
                        class: "w-full h-full transform -rotate-90",
                        viewBox: "0 0 36 36"
                      }, [
                        createVNode("circle", {
                          cx: "18",
                          cy: "18",
                          r: "15.5",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "3",
                          class: "text-gray-200 dark:text-gray-600"
                        }),
                        createVNode("circle", {
                          cx: "18",
                          cy: "18",
                          r: "15.5",
                          fill: "none",
                          stroke: "currentColor",
                          "stroke-width": "3",
                          "stroke-linecap": "round",
                          class: "text-blue-500",
                          "stroke-dasharray": `${((_M = unref(selectedMember)) == null ? void 0 : _M.overall_progress) || 0} 100`
                        }, null, 8, ["stroke-dasharray"])
                      ])),
                      createVNode("div", { class: "absolute inset-0 flex items-center justify-center" }, [
                        createVNode("span", { class: "text-lg font-bold text-blue-600 dark:text-blue-400" }, toDisplayString(((_N = unref(selectedMember)) == null ? void 0 : _N.overall_progress) || 0) + "%", 1)
                      ])
                    ]),
                    createVNode("p", { class: "text-xs text-gray-500 mt-2" }, "\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32")
                  ]),
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center" }, [
                      createVNode("span", { class: "text-2xl font-bold text-green-600 dark:text-green-400" }, toDisplayString(((_P = (_O = unref(selectedMember)) == null ? void 0 : _O.scores) == null ? void 0 : _P.grade_name) || "-"), 1)
                    ]),
                    createVNode("p", { class: "text-xs text-gray-500 mt-2" }, "\u0E40\u0E01\u0E23\u0E14 (" + toDisplayString(((_R = (_Q = unref(selectedMember)) == null ? void 0 : _Q.scores) == null ? void 0 : _R.grade_progress) || 0) + ")", 1)
                  ]),
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center" }, [
                      createVNode("span", { class: "text-xl font-bold text-orange-600 dark:text-orange-400" }, toDisplayString(((_S = unref(selectedMember)) == null ? void 0 : _S.attendance_rate) || 0) + "%", 1)
                    ]),
                    createVNode("p", { class: "text-xs text-gray-500 mt-2" }, "\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 (" + toDisplayString(((_T = unref(selectedMember)) == null ? void 0 : _T.attendance_present) || 0) + "/" + toDisplayString(((_U = unref(selectedMember)) == null ? void 0 : _U.total_attendance) || 0) + ")", 1)
                  ]),
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center" }, [
                      createVNode("span", { class: "text-xl font-bold text-purple-600 dark:text-purple-400" }, toDisplayString(((_W = (_V = unref(selectedMember)) == null ? void 0 : _V.scores) == null ? void 0 : _W.total_score) || 0), 1)
                    ]),
                    createVNode("p", { class: "text-xs text-gray-500 mt-2" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21")
                  ])
                ]),
                createVNode("div", { class: "grid grid-cols-1 md:grid-cols-3 gap-4" }, [
                  createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                    createVNode("div", { class: "flex items-center justify-between mb-2" }, [
                      createVNode("span", { class: "text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:book-24-regular",
                          class: "w-4 h-4 text-blue-500"
                        }),
                        createTextVNode(" \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 ")
                      ]),
                      createVNode("span", { class: "text-sm font-bold text-blue-600 dark:text-blue-400" }, toDisplayString(((_X = unref(selectedMember)) == null ? void 0 : _X.lessons_completed) || 0) + "/" + toDisplayString(((_Y = unref(selectedMember)) == null ? void 0 : _Y.total_lessons) || 0), 1)
                    ]),
                    createVNode("div", { class: "h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden" }, [
                      createVNode("div", {
                        class: "h-full rounded-full bg-blue-500 transition-all duration-500",
                        style: { width: `${((_Z = unref(selectedMember)) == null ? void 0 : _Z.lessons_progress) || 0}%` }
                      }, null, 4)
                    ])
                  ]),
                  createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                    createVNode("div", { class: "flex items-center justify-between mb-2" }, [
                      createVNode("span", { class: "text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:document-text-24-regular",
                          class: "w-4 h-4 text-orange-500"
                        }),
                        createTextVNode(" \u0E07\u0E32\u0E19 ")
                      ]),
                      createVNode("span", { class: "text-sm font-bold text-orange-600 dark:text-orange-400" }, toDisplayString(((__ = unref(selectedMember)) == null ? void 0 : __.assignments_completed) || 0) + "/" + toDisplayString(((_$ = unref(selectedMember)) == null ? void 0 : _$.total_assignments) || 0), 1)
                    ]),
                    createVNode("div", { class: "h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden" }, [
                      createVNode("div", {
                        class: "h-full rounded-full bg-orange-500 transition-all duration-500",
                        style: { width: `${((_aa = unref(selectedMember)) == null ? void 0 : _aa.assignments_progress) || 0}%` }
                      }, null, 4)
                    ])
                  ]),
                  createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                    createVNode("div", { class: "flex items-center justify-between mb-2" }, [
                      createVNode("span", { class: "text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:quiz-new-24-regular",
                          class: "w-4 h-4 text-purple-500"
                        }),
                        createTextVNode(" \u0E17\u0E14\u0E2A\u0E2D\u0E1A ")
                      ]),
                      createVNode("span", { class: "text-sm font-bold text-purple-600 dark:text-purple-400" }, toDisplayString(((_ba = unref(selectedMember)) == null ? void 0 : _ba.quizzes_completed) || 0) + "/" + toDisplayString(((_ca = unref(selectedMember)) == null ? void 0 : _ca.total_quizzes) || 0), 1)
                    ]),
                    createVNode("div", { class: "h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden" }, [
                      createVNode("div", {
                        class: "h-full rounded-full bg-purple-500 transition-all duration-500",
                        style: { width: `${((_da = unref(selectedMember)) == null ? void 0 : _da.quizzes_progress) || 0}%` }
                      }, null, 4)
                    ])
                  ])
                ]),
                createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                  createVNode("h4", { class: "font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:chart-multiple-24-regular",
                      class: "w-5 h-5 text-blue-500"
                    }),
                    createTextVNode(" \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E04\u0E30\u0E41\u0E19\u0E19 ")
                  ]),
                  createVNode("div", { class: "grid grid-cols-2 md:grid-cols-4 gap-4 text-center" }, [
                    createVNode("div", { class: "p-3 bg-gray-50 dark:bg-gray-700 rounded-lg" }, [
                      createVNode("div", { class: "text-lg font-bold text-gray-900 dark:text-white" }, [
                        createTextVNode(toDisplayString(((_fa = (_ea = unref(selectedMember)) == null ? void 0 : _ea.scores) == null ? void 0 : _fa.lesson_assignments) || 0), 1),
                        createVNode("span", { class: "text-sm font-normal text-gray-500" }, "/" + toDisplayString(((_ha = (_ga = unref(selectedMember)) == null ? void 0 : _ga.scores) == null ? void 0 : _ha.max_lesson_assignments) || 0), 1)
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500" }, "\u0E07\u0E32\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")
                    ]),
                    createVNode("div", { class: "p-3 bg-gray-50 dark:bg-gray-700 rounded-lg" }, [
                      createVNode("div", { class: "text-lg font-bold text-gray-900 dark:text-white" }, [
                        createTextVNode(toDisplayString(((_ja = (_ia = unref(selectedMember)) == null ? void 0 : _ia.scores) == null ? void 0 : _ja.lesson_quizzes) || 0), 1),
                        createVNode("span", { class: "text-sm font-normal text-gray-500" }, "/" + toDisplayString(((_la = (_ka = unref(selectedMember)) == null ? void 0 : _ka.scores) == null ? void 0 : _la.max_lesson_quizzes) || 0), 1)
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500" }, "\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")
                    ]),
                    createVNode("div", { class: "p-3 bg-gray-50 dark:bg-gray-700 rounded-lg" }, [
                      createVNode("div", { class: "text-lg font-bold text-gray-900 dark:text-white" }, [
                        createTextVNode(toDisplayString(((_na = (_ma = unref(selectedMember)) == null ? void 0 : _ma.scores) == null ? void 0 : _na.course_assignments) || 0), 1),
                        createVNode("span", { class: "text-sm font-normal text-gray-500" }, "/" + toDisplayString(((_pa = (_oa = unref(selectedMember)) == null ? void 0 : _oa.scores) == null ? void 0 : _pa.max_course_assignments) || 0), 1)
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500" }, "\u0E07\u0E32\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
                    ]),
                    createVNode("div", { class: "p-3 bg-gray-50 dark:bg-gray-700 rounded-lg" }, [
                      createVNode("div", { class: "text-lg font-bold text-gray-900 dark:text-white" }, [
                        createTextVNode(toDisplayString(((_ra = (_qa = unref(selectedMember)) == null ? void 0 : _qa.scores) == null ? void 0 : _ra.course_quizzes) || 0), 1),
                        createVNode("span", { class: "text-sm font-normal text-gray-500" }, "/" + toDisplayString(((_ta = (_sa = unref(selectedMember)) == null ? void 0 : _sa.scores) == null ? void 0 : _ta.max_course_quizzes) || 0), 1)
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500" }, "\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
                    ])
                  ]),
                  createVNode("div", { class: "mt-3 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg" }, [
                    createVNode("span", { class: "text-sm font-medium text-blue-700 dark:text-blue-300" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E1E\u0E34\u0E40\u0E28\u0E29 (Bonus)"),
                    createVNode("span", { class: "text-lg font-bold text-blue-600 dark:text-blue-400" }, "+" + toDisplayString(((_va = (_ua = unref(selectedMember)) == null ? void 0 : _ua.scores) == null ? void 0 : _va.bonus_points) || 0), 1)
                  ])
                ]),
                createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                  createVNode("h4", { class: "font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:book-24-regular",
                      class: "w-5 h-5 text-blue-500"
                    }),
                    createTextVNode(" \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 ")
                  ]),
                  unref(memberDetails).lessons && unref(memberDetails).lessons.length > 0 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-2"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(memberDetails).lessons, (lesson) => {
                      return openBlock(), createBlock("div", {
                        key: lesson.id,
                        class: "flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                      }, [
                        createVNode("span", { class: "text-sm text-gray-700 dark:text-gray-300" }, toDisplayString(lesson.title), 1),
                        createVNode("span", {
                          class: [
                            "text-xs px-2 py-1 rounded-full",
                            lesson.completed ? "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" : "bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                          ]
                        }, toDisplayString(lesson.completed ? "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A\u0E41\u0E25\u0E49\u0E27" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21"), 3)
                      ]);
                    }), 128))
                  ])) : (openBlock(), createBlock("p", {
                    key: 1,
                    class: "text-sm text-gray-500 text-center py-4"
                  }, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19"))
                ]),
                createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                  createVNode("h4", { class: "font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:document-text-24-regular",
                      class: "w-5 h-5 text-orange-500"
                    }),
                    createTextVNode(" \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 ")
                  ]),
                  unref(memberDetails).assignments && unref(memberDetails).assignments.length > 0 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-2"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(memberDetails).assignments, (assignment) => {
                      return openBlock(), createBlock("div", {
                        key: assignment.id,
                        class: "flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                      }, [
                        createVNode("span", {
                          class: "text-sm text-gray-700 dark:text-gray-300 truncate flex-1 min-w-0",
                          title: assignment.title
                        }, toDisplayString(assignment.title), 9, ["title"]),
                        createVNode("div", { class: "flex items-center gap-2" }, [
                          assignment.graded ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "text-sm font-bold text-green-600 dark:text-green-400"
                          }, toDisplayString(assignment.score) + "/" + toDisplayString(assignment.max_score), 1)) : createCommentVNode("", true),
                          createVNode("span", {
                            class: [
                              "text-xs px-2 py-1 rounded-full whitespace-nowrap",
                              assignment.status === "graded" ? "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" : assignment.status === "in_review" ? "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400" : assignment.status === "submitted" ? "bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400" : "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                            ]
                          }, toDisplayString(assignment.status === "graded" ? "\u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27" : assignment.status === "in_review" ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E15\u0E23\u0E27\u0E08" : assignment.status === "submitted" ? "\u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E2A\u0E48\u0E07"), 3)
                        ])
                      ]);
                    }), 128))
                  ])) : (openBlock(), createBlock("p", {
                    key: 1,
                    class: "text-sm text-gray-500 text-center py-4"
                  }, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22"))
                ]),
                createVNode("div", { class: "p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700" }, [
                  createVNode("h4", { class: "font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:quiz-new-24-regular",
                      class: "w-5 h-5 text-purple-500"
                    }),
                    createTextVNode(" \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A ")
                  ]),
                  unref(memberDetails).quizzes && unref(memberDetails).quizzes.length > 0 ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "space-y-2"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(memberDetails).quizzes, (quiz) => {
                      return openBlock(), createBlock("div", {
                        key: quiz.id,
                        class: "flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                      }, [
                        createVNode("div", { class: "flex-1" }, [
                          createVNode("span", { class: "text-sm text-gray-700 dark:text-gray-300" }, toDisplayString(quiz.title), 1),
                          quiz.attempt_count > 0 ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "text-xs text-gray-400 ml-2"
                          }, " (\u0E17\u0E33 " + toDisplayString(quiz.attempt_count) + " \u0E04\u0E23\u0E31\u0E49\u0E07) ", 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("div", { class: "flex items-center gap-2" }, [
                          quiz.completed ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: ["text-sm font-bold", quiz.passed === false ? "text-red-500" : "text-green-600 dark:text-green-400"]
                          }, toDisplayString(quiz.score) + "/" + toDisplayString(quiz.max_score), 3)) : createCommentVNode("", true),
                          createVNode("span", {
                            class: [
                              "text-xs px-2 py-1 rounded-full whitespace-nowrap",
                              quiz.completed ? quiz.passed === false ? "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400" : "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" : "bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                            ]
                          }, toDisplayString(quiz.completed ? quiz.passed === false ? "\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19" : "\u0E1C\u0E48\u0E32\u0E19" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E17\u0E33"), 3)
                        ])
                      ]);
                    }), 128))
                  ])) : (openBlock(), createBlock("p", {
                    key: 1,
                    class: "text-sm text-gray-500 text-center py-4"
                  }, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A"))
                ])
              ])) : (openBlock(), createBlock("div", {
                key: 1,
                class: "flex flex-col items-center justify-center py-12"
              }, [
                createVNode("div", { class: "animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4" }),
                createVNode("p", { class: "text-gray-500" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...")
              ]))
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<button class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"${_scopeId}> \u0E1B\u0E34\u0E14 </button>`);
          } else {
            return [
              createVNode("button", {
                onClick: ($event) => showDetailsModal.value = false,
                class: "px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
              }, " \u0E1B\u0E34\u0E14 ", 8, ["onClick"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/ProgressList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "progress",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if ((_a = unref(course)) == null ? void 0 : _a.id) {
        _push(ssrRenderComponent(_sfc_main$1, {
          "course-id": unref(course).id,
          "is-course-admin": unref(isCourseAdmin)
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/progress.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=progress-DzdM6h1G.mjs.map
