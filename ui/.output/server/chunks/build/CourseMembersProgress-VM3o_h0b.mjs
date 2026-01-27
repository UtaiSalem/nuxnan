import { ref, computed, withCtx, unref, createVNode, createBlock, openBlock, createTextVNode, toDisplayString, withDirectives, vModelText, createCommentVNode, Fragment, renderList, withModifiers, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderAttr, ssrRenderList, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import { i as useApi } from './server.mjs';
import _sfc_main$2 from './CourseLayout-DYixP6h3.mjs';
import { _ as _sfc_main$3 } from './StaggeredFade-D8HMbryk.mjs';
import { _ as _sfc_main$4 } from './MyProgressDetails-CmsVaAN0.mjs';
import { _ as _sfc_main$5 } from './Modal-DYe4d1RC.mjs';
import { Icon } from '@iconify/vue';
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
import './main-BqvhuwHD.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';
import './AssignmentSubmissionForm-BYC_TqBC.mjs';
import './RichTextEditor-DEEazQRP.mjs';
import './useSweetAlert-jHixiibP.mjs';
import 'sweetalert2';

const _sfc_main$1 = {
  __name: "MembersProgress",
  __ssrInlineRender: true,
  props: {
    groupName: String,
    members: Array,
    isCourseAdmin: Boolean
  },
  emits: ["view"],
  setup(__props) {
    const getAttendanceColor = (rate) => {
      if (!rate) return "text-gray-400";
      if (rate >= 80) return "text-green-600";
      if (rate >= 50) return "text-orange-600";
      return "text-red-600";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 rounded-xl shadow-lg border border-gray-100" }, _attrs))}><h3 class="font-bold mb-4 ml-4 text-gray-700">\u0E25\u0E33\u0E14\u0E31\u0E1A\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E02\u0E2D\u0E07\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 - ${ssrInterpolate(__props.groupName)}</h3><div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="bg-gray-50 text-gray-600 text-sm"><th class="p-3 font-semibold">\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25</th><th class="p-3 font-semibold text-center">\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</th><th class="p-3 font-semibold text-center">\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</th><th class="p-3 font-semibold text-center">\u0E07\u0E32\u0E19</th><th class="p-3 font-semibold text-center">\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</th><th class="p-3 font-semibold text-center">\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21</th><th class="p-3 font-semibold text-center">\u0E40\u0E01\u0E23\u0E14</th><th class="p-3 font-semibold text-center">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody><!--[-->`);
      ssrRenderList(__props.members, (member) => {
        var _a, _b;
        _push(`<tr class="border-t hover:bg-blue-50/50 transition-colors"><td class="p-3"><div class="font-medium text-gray-800">${ssrInterpolate(member.member_name)}</div><div class="text-xs text-gray-500">${ssrInterpolate(member.member_code || "-")}</div></td><td class="p-3 text-center"><div class="${ssrRenderClass([getAttendanceColor(member.attendance_rate), "text-sm font-bold"])}">${ssrInterpolate(member.attendance_rate || 0)}% </div></td><td class="p-3 text-center"><div class="w-full bg-gray-200 rounded-full h-2 mb-1"><div class="bg-blue-600 h-2 rounded-full" style="${ssrRenderStyle(`width: ${member.lessons_progress || 0}%`)}"></div></div><span class="text-xs text-gray-500">${ssrInterpolate(member.lessons_progress || 0)}%</span></td><td class="p-3 text-center"><div class="w-full bg-gray-200 rounded-full h-2 mb-1"><div class="bg-green-600 h-2 rounded-full" style="${ssrRenderStyle(`width: ${member.assignments_progress || 0}%`)}"></div></div><span class="text-xs text-gray-500">${ssrInterpolate(member.assignments_progress || 0)}%</span></td><td class="p-3 text-center"><div class="w-full bg-gray-200 rounded-full h-2 mb-1"><div class="bg-purple-600 h-2 rounded-full" style="${ssrRenderStyle(`width: ${member.quizzes_progress || 0}%`)}"></div></div><span class="text-xs text-gray-500">${ssrInterpolate(member.quizzes_progress || 0)}%</span></td><td class="p-3 text-center"><span class="font-bold text-gray-700">${ssrInterpolate(((_a = member.scores) == null ? void 0 : _a.total_score) || 0)}</span></td><td class="p-3 text-center"><span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-700">${ssrInterpolate(((_b = member.scores) == null ? void 0 : _b.grade_name) || "-")}</span></td><td class="p-3 text-center"><button class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1 rounded-lg text-sm transition-colors font-medium"> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </button></td></tr>`);
      });
      _push(`<!--]--></tbody></table></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/progress/MembersProgress.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "CourseMembersProgress",
  __ssrInlineRender: true,
  props: {
    course: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    members: Object,
    assignments: Object,
    quizzes: Object,
    courseMembersProgress: Object
  },
  setup(__props) {
    const api = useApi();
    const props = __props;
    const getInitialGroupTab = () => {
      var _a, _b;
      const lastAccessedGroupId = (_a = props.courseMemberOfAuth) == null ? void 0 : _a.last_accessed_group_tab;
      if (!lastAccessedGroupId) return 0;
      const groups = ((_b = props.groups) == null ? void 0 : _b.data) || [];
      const index = groups.findIndex((group) => group.id === lastAccessedGroupId);
      return index >= 0 ? index : 0;
    };
    const activeGroupTab = ref(getInitialGroupTab());
    async function setActiveGroupTab(tabIndex) {
      var _a;
      activeGroupTab.value = tabIndex;
      const groups = ((_a = props.groups) == null ? void 0 : _a.data) || [];
      if (tabIndex < groups.length && props.courseMemberOfAuth) {
        const groupId = groups[tabIndex].id;
        try {
          const resp = await api.post(`/api/courses/${props.course.data.id}/members/${props.courseMemberOfAuth.id}/set-active-group-tab`, { group_tab: groupId });
          if (resp.success) {
            props.courseMemberOfAuth.last_accessed_group_tab = groupId;
          }
        } catch (error) {
          console.error("Error saving group tab:", error);
        }
      }
    }
    const unGroupedMembers = computed(() => {
      var _a, _b, _c, _d;
      const members = ((_a = props.members) == null ? void 0 : _a.data) || [];
      const courseOwnerId = (_d = (_c = (_b = props.course) == null ? void 0 : _b.data) == null ? void 0 : _c.user) == null ? void 0 : _d.id;
      return members.filter((member) => !member.group).filter((member) => {
        var _a2;
        return ((_a2 = member.user) == null ? void 0 : _a2.id) !== courseOwnerId;
      });
    });
    const memberStats = computed(() => {
      var _a, _b, _c;
      const allProgress = props.courseMembersProgress || [];
      if (!allProgress.length) return { average: 0, max: 0, min: 0, passCount: 0, failCount: 0, passRate: 0, avgAttendance: 0, totalAttendSessions: 0 };
      const scores = allProgress.map((p) => {
        var _a2;
        return ((_a2 = p.scores) == null ? void 0 : _a2.total_score) || 0;
      });
      const sum = scores.reduce((a, b) => a + b, 0);
      const avg = sum / scores.length || 0;
      const max = Math.max(...scores);
      const min = Math.min(...scores);
      const attendanceRates = allProgress.map((p) => p.attendance_rate || 0);
      const avgAttendance = attendanceRates.reduce((a, b) => a + b, 0) / attendanceRates.length;
      const totalAttendSessions = ((_a = allProgress[0]) == null ? void 0 : _a.total_attendance) || 0;
      const threshold = (((_c = (_b = props.course) == null ? void 0 : _b.data) == null ? void 0 : _c.total_score) || 100) / 2;
      const passCount = scores.filter((s) => s >= threshold).length;
      return {
        average: avg.toFixed(1),
        max,
        min,
        passCount,
        failCount: scores.length - passCount,
        passRate: (passCount / scores.length * 100).toFixed(1),
        avgAttendance: avgAttendance.toFixed(1),
        totalAttendSessions
      };
    });
    const isExporting = ref(false);
    const exportToExcel = async () => {
      var _a;
      if (isExporting.value) return;
      isExporting.value = true;
      try {
        const groups = ((_a = props.groups) == null ? void 0 : _a.data) || [];
        const groupId = activeGroupTab.value < groups.length ? groups[activeGroupTab.value].id : "all";
        const response = await api.get(`/api/courses/${props.course.data.id}/export/results`, {
          params: { group_id: groupId },
          responseType: "blob"
        });
        const blob = new Blob([response], {
          type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        });
        const url = (void 0).URL.createObjectURL(blob);
        const link = (void 0).createElement("a");
        link.href = url;
        link.setAttribute("download", `results-${props.course.data.id}-${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.xlsx`);
        (void 0).body.appendChild(link);
        link.click();
        (void 0).body.removeChild(link);
        (void 0).URL.revokeObjectURL(url);
      } catch (error) {
        console.error("Export failed:", error);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07");
      } finally {
        isExporting.value = false;
      }
    };
    const searchQuery = ref("");
    const filteredActiveGroupMembers = computed(() => {
      var _a, _b;
      const groups = ((_a = props.groups) == null ? void 0 : _a.data) || [];
      let members = [];
      if (activeGroupTab.value < groups.length) {
        members = ((_b = groups[activeGroupTab.value]) == null ? void 0 : _b.members) || [];
      } else {
        members = unGroupedMembers.value;
      }
      if (props.courseMembersProgress) {
        members = members.map((member) => {
          const progressData = props.courseMembersProgress.find((p) => p.member.id === member.id);
          if (progressData) {
            return {
              ...member,
              scores: progressData.scores,
              attendance_rate: progressData.attendance_rate,
              lessons_progress: progressData.lessons_progress,
              assignments_progress: progressData.assignments_progress,
              quizzes_progress: progressData.quizzes_progress,
              overall_progress: progressData.overall_progress
            };
          }
          return member;
        });
      }
      if (!searchQuery.value) return members;
      const query = searchQuery.value.toLowerCase();
      return members.filter(
        (m) => m.member_name && m.member_name.toLowerCase().includes(query) || m.member_code && m.member_code.toLowerCase().includes(query)
      );
    });
    const groupsData = computed(() => {
      var _a;
      return ((_a = props.groups) == null ? void 0 : _a.data) || [];
    });
    const showMemberModal = ref(false);
    const selectedMember = ref(null);
    const openMemberModal = (member) => {
      selectedMember.value = member;
      showMemberModal.value = true;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_sfc_main$2, {
        course: props.course,
        lessons: props.lessons,
        groups: props.groups,
        isCourseAdmin: props.isCourseAdmin,
        courseMemberOfAuth: props.courseMemberOfAuth,
        activeTab: 10
      }, {
        courseContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (props.isCourseAdmin) {
              _push2(`<div class="md:-ml-4 md:mr-4"${_scopeId}><div class="mb-6 px-4"${_scopeId}><div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100"${_scopeId}><h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center"${_scopeId}><svg class="w-6 h-6 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"${_scopeId}></path></svg> \u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 (Dashboard) </h2><div class="flex justify-end mb-4"${_scopeId}><button${ssrIncludeBooleanAttr(isExporting.value) ? " disabled" : ""} class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors shadow-md disabled:opacity-50"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: isExporting.value ? "line-md:loading-twotone-loop" : "lucide:file-spreadsheet",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` ${ssrInterpolate(isExporting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01..." : "\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 Excel")}</button></div><div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4"${_scopeId}><div class="bg-blue-50 p-4 rounded-lg border border-blue-100"${_scopeId}><p class="text-sm text-gray-600"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22</p><p class="text-2xl font-bold text-blue-700"${_scopeId}>${ssrInterpolate(memberStats.value.average)}</p></div><div class="bg-green-50 p-4 rounded-lg border border-green-100"${_scopeId}><p class="text-sm text-gray-600"${_scopeId}>\u0E1C\u0E48\u0E32\u0E19\u0E40\u0E01\u0E13\u0E11\u0E4C</p><p class="text-2xl font-bold text-green-700"${_scopeId}>${ssrInterpolate(memberStats.value.passCount)} <span class="text-sm text-gray-500"${_scopeId}>(${ssrInterpolate(memberStats.value.passRate)}%)</span></p></div><div class="bg-purple-50 p-4 rounded-lg border border-purple-100"${_scopeId}><p class="text-sm text-gray-600"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14</p><p class="text-2xl font-bold text-purple-700"${_scopeId}>${ssrInterpolate(memberStats.value.max)}</p></div><div class="bg-red-50 p-4 rounded-lg border border-red-100"${_scopeId}><p class="text-sm text-gray-600"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E15\u0E48\u0E33\u0E2A\u0E38\u0E14</p><p class="text-2xl font-bold text-red-700"${_scopeId}>${ssrInterpolate(memberStats.value.min)}</p></div><div class="bg-orange-50 p-4 rounded-lg border border-orange-100"${_scopeId}><p class="text-sm text-gray-600"${_scopeId}>\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22</p><p class="text-2xl font-bold text-orange-700"${_scopeId}>${ssrInterpolate(memberStats.value.avgAttendance)}%</p></div><div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100"${_scopeId}><p class="text-sm text-gray-600"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E32\u0E1A\u0E40\u0E23\u0E35\u0E22\u0E19</p><p class="text-2xl font-bold text-indigo-700"${_scopeId}>${ssrInterpolate(memberStats.value.totalAttendSessions)}</p></div></div></div></div><div class="px-4 mb-4"${_scopeId}><div class="relative"${_scopeId}><span class="absolute inset-y-0 left-0 flex items-center pl-3"${_scopeId}><svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"${_scopeId}></path></svg></span><input${ssrRenderAttr("value", searchQuery.value)} type="text" class="w-full py-2 pl-10 pr-4 text-gray-700 bg-white border rounded-lg focus:border-blue-400 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-40" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E14\u0E49\u0E27\u0E22\u0E0A\u0E37\u0E48\u0E2D \u0E2B\u0E23\u0E37\u0E2D \u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E28\u0E36\u0E01\u0E29\u0E32..."${_scopeId}></div></div><section class="" aria-multiselectable="false"${_scopeId}>`);
              if (props.isCourseAdmin) {
                _push2(`<ul class="flex flex-wrap items-center border-b plearnd-card bg-gradient-to-r from-blue-50 via-green-50 to-yellow-50" role="tablist"${_scopeId}><!--[-->`);
                ssrRenderList(groupsData.value, (group, index) => {
                  var _a;
                  _push2(`<li class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation "${_scopeId}><button class="${ssrRenderClass([activeGroupTab.value === index ? "border-blue-500 bg-gradient-to-r from-blue-200 via-green-100 to-yellow-100 text-blue-900 font-bold shadow-lg" : "border-transparent bg-white text-gray-600 hover:bg-blue-100 hover:text-blue-700", "inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-200 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap font-medium shadow-sm hover:scale-105"])}" id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0" aria-controls="tab-panel-1a" aria-selected="true"${_scopeId}><span${_scopeId}>${ssrInterpolate(group.name + " (" + (((_a = group.members) == null ? void 0 : _a.length) || 0) + ")")}</span></button></li>`);
                });
                _push2(`<!--]-->`);
                if (unGroupedMembers.value.length > 0) {
                  _push2(`<li class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation "${_scopeId}><button class="${ssrRenderClass([activeGroupTab.value === groupsData.value.length ? "border-blue-500 bg-gradient-to-r from-blue-200 via-green-100 to-yellow-100 text-blue-900 font-bold shadow-lg" : "border-transparent bg-white text-gray-600 hover:bg-blue-100 hover:text-blue-700", "inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-200 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap font-medium shadow-sm hover:scale-105"])}" id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0" aria-controls="tab-panel-1a" aria-selected="true"${_scopeId}><span${_scopeId}>${ssrInterpolate("\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21 (" + unGroupedMembers.value.length + ")")}</span></button></li>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</ul>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</section><section class=""${_scopeId}><div class="bg-gradient-to-br from-blue-50 via-green-50 to-yellow-50 mt-4 p-4 rounded-xl shadow-lg border border-blue-100" id="tab-panel-1a" aria-hidden="false" role="tabpanel" aria-labelledby="tab-label-1a" tabindex="-1"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$3, {
                duration: 50,
                tag: "ul",
                class: "flex flex-col w-full"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  var _a, _b;
                  if (_push3) {
                    _push3(ssrRenderComponent(_sfc_main$1, {
                      groupName: ((_a = groupsData.value[activeGroupTab.value]) == null ? void 0 : _a.name) || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21",
                      members: filteredActiveGroupMembers.value,
                      isCourseAdmin: props.isCourseAdmin,
                      onView: openMemberModal
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(_sfc_main$1, {
                        groupName: ((_b = groupsData.value[activeGroupTab.value]) == null ? void 0 : _b.name) || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21",
                        members: filteredActiveGroupMembers.value,
                        isCourseAdmin: props.isCourseAdmin,
                        onView: openMemberModal
                      }, null, 8, ["groupName", "members", "isCourseAdmin"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div></section></div>`);
            } else {
              _push2(`<div class="p-4"${_scopeId}><h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:chart-person-24-filled",
                class: "text-blue-600"
              }, null, _parent2, _scopeId));
              _push2(` \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 (My Progress) </h2>`);
              if (props.courseMemberOfAuth) {
                _push2(ssrRenderComponent(_sfc_main$4, {
                  courseId: props.course.data.id,
                  memberId: props.courseMemberOfAuth.id
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<div class="text-center text-gray-500 py-10"${_scopeId}> \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E02\u0E2D\u0E07\u0E17\u0E48\u0E32\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </div>`);
              }
              _push2(`</div>`);
            }
          } else {
            return [
              props.isCourseAdmin ? (openBlock(), createBlock("div", {
                key: 0,
                class: "md:-ml-4 md:mr-4"
              }, [
                createVNode("div", { class: "mb-6 px-4" }, [
                  createVNode("div", { class: "bg-white rounded-xl shadow-lg p-6 border border-gray-100" }, [
                    createVNode("h2", { class: "text-2xl font-bold text-gray-800 mb-4 flex items-center" }, [
                      (openBlock(), createBlock("svg", {
                        class: "w-6 h-6 mr-2 text-blue-600",
                        fill: "none",
                        viewBox: "0 0 24 24",
                        stroke: "currentColor"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          "stroke-width": "2",
                          d: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        })
                      ])),
                      createTextVNode(" \u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 (Dashboard) ")
                    ]),
                    createVNode("div", { class: "flex justify-end mb-4" }, [
                      createVNode("button", {
                        onClick: exportToExcel,
                        disabled: isExporting.value,
                        class: "flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors shadow-md disabled:opacity-50"
                      }, [
                        createVNode(unref(Icon), {
                          icon: isExporting.value ? "line-md:loading-twotone-loop" : "lucide:file-spreadsheet",
                          class: "w-5 h-5"
                        }, null, 8, ["icon"]),
                        createTextVNode(" " + toDisplayString(isExporting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01..." : "\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 Excel"), 1)
                      ], 8, ["disabled"])
                    ]),
                    createVNode("div", { class: "grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" }, [
                      createVNode("div", { class: "bg-blue-50 p-4 rounded-lg border border-blue-100" }, [
                        createVNode("p", { class: "text-sm text-gray-600" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22"),
                        createVNode("p", { class: "text-2xl font-bold text-blue-700" }, toDisplayString(memberStats.value.average), 1)
                      ]),
                      createVNode("div", { class: "bg-green-50 p-4 rounded-lg border border-green-100" }, [
                        createVNode("p", { class: "text-sm text-gray-600" }, "\u0E1C\u0E48\u0E32\u0E19\u0E40\u0E01\u0E13\u0E11\u0E4C"),
                        createVNode("p", { class: "text-2xl font-bold text-green-700" }, [
                          createTextVNode(toDisplayString(memberStats.value.passCount) + " ", 1),
                          createVNode("span", { class: "text-sm text-gray-500" }, "(" + toDisplayString(memberStats.value.passRate) + "%)", 1)
                        ])
                      ]),
                      createVNode("div", { class: "bg-purple-50 p-4 rounded-lg border border-purple-100" }, [
                        createVNode("p", { class: "text-sm text-gray-600" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14"),
                        createVNode("p", { class: "text-2xl font-bold text-purple-700" }, toDisplayString(memberStats.value.max), 1)
                      ]),
                      createVNode("div", { class: "bg-red-50 p-4 rounded-lg border border-red-100" }, [
                        createVNode("p", { class: "text-sm text-gray-600" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E15\u0E48\u0E33\u0E2A\u0E38\u0E14"),
                        createVNode("p", { class: "text-2xl font-bold text-red-700" }, toDisplayString(memberStats.value.min), 1)
                      ]),
                      createVNode("div", { class: "bg-orange-50 p-4 rounded-lg border border-orange-100" }, [
                        createVNode("p", { class: "text-sm text-gray-600" }, "\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22"),
                        createVNode("p", { class: "text-2xl font-bold text-orange-700" }, toDisplayString(memberStats.value.avgAttendance) + "%", 1)
                      ]),
                      createVNode("div", { class: "bg-indigo-50 p-4 rounded-lg border border-indigo-100" }, [
                        createVNode("p", { class: "text-sm text-gray-600" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E32\u0E1A\u0E40\u0E23\u0E35\u0E22\u0E19"),
                        createVNode("p", { class: "text-2xl font-bold text-indigo-700" }, toDisplayString(memberStats.value.totalAttendSessions), 1)
                      ])
                    ])
                  ])
                ]),
                createVNode("div", { class: "px-4 mb-4" }, [
                  createVNode("div", { class: "relative" }, [
                    createVNode("span", { class: "absolute inset-y-0 left-0 flex items-center pl-3" }, [
                      (openBlock(), createBlock("svg", {
                        class: "w-5 h-5 text-gray-400",
                        fill: "none",
                        viewBox: "0 0 24 24",
                        stroke: "currentColor"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          "stroke-width": "2",
                          d: "M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        })
                      ]))
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => searchQuery.value = $event,
                      type: "text",
                      class: "w-full py-2 pl-10 pr-4 text-gray-700 bg-white border rounded-lg focus:border-blue-400 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-40",
                      placeholder: "\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E14\u0E49\u0E27\u0E22\u0E0A\u0E37\u0E48\u0E2D \u0E2B\u0E23\u0E37\u0E2D \u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E28\u0E36\u0E01\u0E29\u0E32..."
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, searchQuery.value]
                    ])
                  ])
                ]),
                createVNode("section", {
                  class: "",
                  "aria-multiselectable": "false"
                }, [
                  props.isCourseAdmin ? (openBlock(), createBlock("ul", {
                    key: 0,
                    class: "flex flex-wrap items-center border-b plearnd-card bg-gradient-to-r from-blue-50 via-green-50 to-yellow-50",
                    role: "tablist"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(groupsData.value, (group, index) => {
                      var _a;
                      return openBlock(), createBlock("li", {
                        key: index,
                        class: "w-1/2 md:w-1/3 lg:w-1/4",
                        role: "presentation "
                      }, [
                        createVNode("button", {
                          onClick: withModifiers(($event) => setActiveGroupTab(index), ["prevent"]),
                          class: ["inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-200 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap font-medium shadow-sm hover:scale-105", activeGroupTab.value === index ? "border-blue-500 bg-gradient-to-r from-blue-200 via-green-100 to-yellow-100 text-blue-900 font-bold shadow-lg" : "border-transparent bg-white text-gray-600 hover:bg-blue-100 hover:text-blue-700"],
                          id: "tab-label-1a",
                          role: "tab",
                          "aria-setsize": "3",
                          "aria-posinset": "1",
                          tabindex: "0",
                          "aria-controls": "tab-panel-1a",
                          "aria-selected": "true"
                        }, [
                          createVNode("span", null, toDisplayString(group.name + " (" + (((_a = group.members) == null ? void 0 : _a.length) || 0) + ")"), 1)
                        ], 10, ["onClick"])
                      ]);
                    }), 128)),
                    unGroupedMembers.value.length > 0 ? (openBlock(), createBlock("li", {
                      key: 0,
                      class: "w-1/2 md:w-1/3 lg:w-1/4",
                      role: "presentation "
                    }, [
                      createVNode("button", {
                        onClick: withModifiers(($event) => setActiveGroupTab(groupsData.value.length), ["prevent"]),
                        class: ["inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-200 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap font-medium shadow-sm hover:scale-105", activeGroupTab.value === groupsData.value.length ? "border-blue-500 bg-gradient-to-r from-blue-200 via-green-100 to-yellow-100 text-blue-900 font-bold shadow-lg" : "border-transparent bg-white text-gray-600 hover:bg-blue-100 hover:text-blue-700"],
                        id: "tab-label-1a",
                        role: "tab",
                        "aria-setsize": "3",
                        "aria-posinset": "1",
                        tabindex: "0",
                        "aria-controls": "tab-panel-1a",
                        "aria-selected": "true"
                      }, [
                        createVNode("span", null, toDisplayString("\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21 (" + unGroupedMembers.value.length + ")"), 1)
                      ], 10, ["onClick"])
                    ])) : createCommentVNode("", true)
                  ])) : createCommentVNode("", true)
                ]),
                createVNode("section", { class: "" }, [
                  createVNode("div", {
                    class: "bg-gradient-to-br from-blue-50 via-green-50 to-yellow-50 mt-4 p-4 rounded-xl shadow-lg border border-blue-100",
                    id: "tab-panel-1a",
                    "aria-hidden": "false",
                    role: "tabpanel",
                    "aria-labelledby": "tab-label-1a",
                    tabindex: "-1"
                  }, [
                    createVNode(_sfc_main$3, {
                      duration: 50,
                      tag: "ul",
                      class: "flex flex-col w-full"
                    }, {
                      default: withCtx(() => {
                        var _a;
                        return [
                          createVNode(_sfc_main$1, {
                            groupName: ((_a = groupsData.value[activeGroupTab.value]) == null ? void 0 : _a.name) || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21",
                            members: filteredActiveGroupMembers.value,
                            isCourseAdmin: props.isCourseAdmin,
                            onView: openMemberModal
                          }, null, 8, ["groupName", "members", "isCourseAdmin"])
                        ];
                      }),
                      _: 1
                    })
                  ])
                ])
              ])) : (openBlock(), createBlock("div", {
                key: 1,
                class: "p-4"
              }, [
                createVNode("h2", { class: "text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2" }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:chart-person-24-filled",
                    class: "text-blue-600"
                  }),
                  createTextVNode(" \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 (My Progress) ")
                ]),
                props.courseMemberOfAuth ? (openBlock(), createBlock(_sfc_main$4, {
                  key: 0,
                  courseId: props.course.data.id,
                  memberId: props.courseMemberOfAuth.id
                }, null, 8, ["courseId", "memberId"])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center text-gray-500 py-10"
                }, " \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E02\u0E2D\u0E07\u0E17\u0E48\u0E32\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 "))
              ]))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$5, {
        show: showMemberModal.value,
        onClose: ($event) => showMemberModal.value = false,
        maxWidth: "4xl"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d, _e, _f, _g, _h;
          if (_push2) {
            _push2(`<div class="p-6"${_scopeId}><div class="flex justify-between items-center mb-6"${_scopeId}><div class="flex items-center gap-3"${_scopeId}><div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold"${_scopeId}>${ssrInterpolate(((_b = (_a = selectedMember.value) == null ? void 0 : _a.member_name) == null ? void 0 : _b.charAt(0)) || "U")}</div><div${_scopeId}><h3 class="text-xl font-bold text-gray-800 dark:text-white"${_scopeId}> \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07 ${ssrInterpolate((_c = selectedMember.value) == null ? void 0 : _c.member_name)}</h3><p class="text-sm text-gray-500"${_scopeId}>${ssrInterpolate(((_d = selectedMember.value) == null ? void 0 : _d.member_code) || "-")}</p></div></div><button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors text-gray-500"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-6 h-6"
            }, null, _parent2, _scopeId));
            _push2(`</button></div><div class="max-h-[80vh] overflow-y-auto"${_scopeId}>`);
            if (selectedMember.value) {
              _push2(ssrRenderComponent(_sfc_main$4, {
                courseId: props.course.data.id,
                memberId: selectedMember.value.id,
                key: selectedMember.value.id
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-6" }, [
                createVNode("div", { class: "flex justify-between items-center mb-6" }, [
                  createVNode("div", { class: "flex items-center gap-3" }, [
                    createVNode("div", { class: "w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold" }, toDisplayString(((_f = (_e = selectedMember.value) == null ? void 0 : _e.member_name) == null ? void 0 : _f.charAt(0)) || "U"), 1),
                    createVNode("div", null, [
                      createVNode("h3", { class: "text-xl font-bold text-gray-800 dark:text-white" }, " \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07 " + toDisplayString((_g = selectedMember.value) == null ? void 0 : _g.member_name), 1),
                      createVNode("p", { class: "text-sm text-gray-500" }, toDisplayString(((_h = selectedMember.value) == null ? void 0 : _h.member_code) || "-"), 1)
                    ])
                  ]),
                  createVNode("button", {
                    onClick: ($event) => showMemberModal.value = false,
                    class: "p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors text-gray-500"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:dismiss-24-regular",
                      class: "w-6 h-6"
                    })
                  ], 8, ["onClick"])
                ]),
                createVNode("div", { class: "max-h-[80vh] overflow-y-auto" }, [
                  selectedMember.value ? (openBlock(), createBlock(_sfc_main$4, {
                    courseId: props.course.data.id,
                    memberId: selectedMember.value.id,
                    key: selectedMember.value.id
                  }, null, 8, ["courseId", "memberId"])) : createCommentVNode("", true)
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/Progress/CourseMembersProgress.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CourseMembersProgress-VM3o_h0b.mjs.map
