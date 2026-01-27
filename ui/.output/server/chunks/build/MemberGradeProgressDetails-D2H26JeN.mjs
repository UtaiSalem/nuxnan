import { computed, ref, withCtx, unref, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString, createTextVNode, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrRenderStyle } from 'vue/server-renderer';
import _sfc_main$1 from './CourseLayout-DYixP6h3.mjs';
import Textarea from 'primevue/textarea';
import './main-BqvhuwHD.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
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
import './server.mjs';
import 'pinia';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';

const _sfc_main = {
  __name: "MemberGradeProgressDetails",
  __ssrInlineRender: true,
  props: {
    isCourseAdmin: Boolean,
    course: Object,
    member: Object,
    course_assignments: Object,
    course_quizzes: Object,
    member_quizes_results: Object,
    member_assignments_answers: Object
  },
  setup(__props) {
    const props = __props;
    const totalAchievedScore = computed(() => {
      let assignmentScore = 0;
      let quizScore = 0;
      props.course_assignments.forEach((assignment) => {
        const answer = props.member_assignments_answers.data.find((answer2) => answer2.assignment_id === assignment.id);
        if (answer) {
          assignmentScore += answer.points || 0;
        }
      });
      props.course_quizzes.forEach((quiz) => {
        const result = props.member_quizes_results.data.find((result2) => result2.quiz_id === quiz.id);
        if (result) {
          quizScore += result.score || 0;
        }
      });
      return assignmentScore + quizScore;
    });
    const scorePercentage = computed(() => {
      if (!props.course.data.total_score || props.course.data.total_score === 0) return 0;
      return totalAchievedScore.value / props.course.data.total_score * 100;
    });
    const allAssignmentsSubmitted = computed(() => {
      return props.course_assignments.every((assignment) => {
        return props.member_assignments_answers.data.some((answer) => answer.assignment_id === assignment.id);
      });
    });
    const calculatedGrade = computed(() => {
      if (!allAssignmentsSubmitted.value) {
        return { grade: "\u0E23.", label: "\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E15\u0E31\u0E14\u0E40\u0E01\u0E23\u0E14", status: "pending" };
      }
      const passingThreshold = props.course.data.total_score / 2;
      if (totalAchievedScore.value <= passingThreshold) {
        return { grade: "0", label: "0 (\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19\u0E40\u0E01\u0E13\u0E11\u0E4C)", status: "fail" };
      }
      const percentage = scorePercentage.value;
      if (percentage >= 90) return { grade: "4.0", label: "4.0 (\u0E14\u0E35\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21)", status: "excellent" };
      if (percentage >= 85) return { grade: "3.5", label: "3.5 (\u0E14\u0E35\u0E21\u0E32\u0E01)", status: "very-good" };
      if (percentage >= 80) return { grade: "3.0", label: "3.0 (\u0E14\u0E35)", status: "good" };
      if (percentage >= 75) return { grade: "2.5", label: "2.5 (\u0E04\u0E48\u0E2D\u0E19\u0E02\u0E49\u0E32\u0E07\u0E14\u0E35)", status: "fairly-good" };
      if (percentage >= 70) return { grade: "2.0", label: "2.0 (\u0E1E\u0E2D\u0E43\u0E0A\u0E49)", status: "fair" };
      if (percentage >= 60) return { grade: "1.5", label: "1.5 (\u0E1C\u0E48\u0E32\u0E19\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33)", status: "minimum-pass" };
      if (percentage > passingThreshold / props.course.data.total_score * 100) return { grade: "1.0", label: "1.0 (\u0E1C\u0E48\u0E32\u0E19)", status: "pass" };
      return { grade: "0", label: "0 (\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19)", status: "fail" };
    });
    const passFailStatus = computed(() => {
      const passingThreshold = props.course.data.total_score / 2;
      return totalAchievedScore.value > passingThreshold ? "\u0E1C\u0E48\u0E32\u0E19" : "\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19";
    });
    const getGradeColor = (status) => {
      switch (status) {
        case "excellent":
          return "text-emerald-700 bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-300 shadow-lg shadow-emerald-100";
        case "very-good":
          return "text-green-700 bg-gradient-to-br from-green-50 to-green-100 border-green-300 shadow-lg shadow-green-100";
        case "good":
          return "text-teal-700 bg-gradient-to-br from-teal-50 to-teal-100 border-teal-300 shadow-lg shadow-teal-100";
        case "fairly-good":
          return "text-blue-700 bg-gradient-to-br from-blue-50 to-blue-100 border-blue-300 shadow-lg shadow-blue-100";
        case "fair":
          return "text-indigo-700 bg-gradient-to-br from-indigo-50 to-indigo-100 border-indigo-300 shadow-lg shadow-indigo-100";
        case "minimum-pass":
          return "text-yellow-700 bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-300 shadow-lg shadow-yellow-100";
        case "pass":
          return "text-orange-700 bg-gradient-to-br from-orange-50 to-orange-100 border-orange-300 shadow-lg shadow-orange-100";
        case "fail":
          return "text-red-700 bg-gradient-to-br from-red-50 to-red-100 border-red-300 shadow-lg shadow-red-100";
        case "pending":
          return "text-gray-700 bg-gradient-to-br from-gray-50 to-gray-100 border-gray-300 shadow-lg shadow-gray-100";
        default:
          return "text-gray-700 bg-gradient-to-br from-gray-50 to-gray-100 border-gray-300 shadow-lg shadow-gray-100";
      }
    };
    const getProgressColor = (percentage) => {
      if (percentage >= 90) return "text-emerald-500";
      if (percentage >= 80) return "text-green-500";
      if (percentage >= 70) return "text-teal-500";
      if (percentage >= 60) return "text-blue-500";
      if (percentage >= 50) return "text-indigo-500";
      if (percentage >= 40) return "text-yellow-500";
      if (percentage >= 30) return "text-orange-500";
      return "text-red-500";
    };
    const getStrokeColor = (percentage) => {
      if (percentage >= 90) return "#10b981";
      if (percentage >= 80) return "#22c55e";
      if (percentage >= 70) return "#14b8a6";
      if (percentage >= 60) return "#3b82f6";
      if (percentage >= 50) return "#6366f1";
      if (percentage >= 40) return "#eab308";
      if (percentage >= 30) return "#f97316";
      return "#ef4444";
    };
    const getProgressGradient = (percentage) => {
      if (percentage >= 90) return "url(#gradient-emerald)";
      if (percentage >= 80) return "url(#gradient-green)";
      if (percentage >= 70) return "url(#gradient-teal)";
      if (percentage >= 60) return "url(#gradient-blue)";
      if (percentage >= 50) return "url(#gradient-indigo)";
      if (percentage >= 40) return "url(#gradient-yellow)";
      if (percentage >= 30) return "url(#gradient-orange)";
      return "url(#gradient-red)";
    };
    const getStrokeDasharray = (percentage) => {
      const radius = 60;
      const circumference = 2 * Math.PI * radius;
      const offset = circumference - percentage / 100 * circumference;
      return {
        strokeDasharray: circumference,
        strokeDashoffset: offset
      };
    };
    const getItemProgressColor = (percentage) => {
      if (percentage >= 80) return "bg-gradient-to-r from-emerald-400 to-emerald-600";
      if (percentage >= 60) return "bg-gradient-to-r from-blue-400 to-blue-600";
      if (percentage >= 40) return "bg-gradient-to-r from-yellow-400 to-yellow-600";
      return "bg-gradient-to-r from-red-400 to-red-600";
    };
    const getStatusBadgeStyle = (status, type) => {
      if (type === "assignment") {
        return status ? "bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-300" : "bg-gradient-to-r from-red-100 to-pink-100 text-red-800 border border-red-300";
      } else {
        return status ? "bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-300" : "bg-gradient-to-r from-yellow-100 to-orange-100 text-yellow-800 border border-yellow-300";
      }
    };
    const formatScore = (score, total) => {
      return `${score}/${total}`;
    };
    const truncateText = (text, maxLength = 50) => {
      if (text.length <= maxLength) return { text, isTruncated: false };
      return {
        text: text.substring(0, maxLength) + "...",
        isTruncated: true,
        fullText: text
      };
    };
    const expandedAssignments = ref({});
    const expandedQuizzes = ref({});
    const toggleExpanded = (id, type) => {
      if (type === "assignment") {
        expandedAssignments.value[id] = !expandedAssignments.value[id];
      } else {
        expandedQuizzes.value[id] = !expandedQuizzes.value[id];
      }
    };
    const handlePrint = () => {
      (void 0).print();
    };
    const isCalculatorOpen = ref(false);
    const hypotheticalScores = ref({});
    const toggleCalculator = () => {
      isCalculatorOpen.value = !isCalculatorOpen.value;
      if (isCalculatorOpen.value && Object.keys(hypotheticalScores.value).length === 0) {
        props.course_assignments.forEach((assignment) => {
          const answer = props.member_assignments_answers.data.find((a) => a.assignment_id === assignment.id);
          if (!answer) {
            hypotheticalScores.value[`assignment_${assignment.id}`] = assignment.points;
          }
        });
        props.course_quizzes.forEach((quiz) => {
          const result = props.member_quizes_results.data.find((r) => r.quiz_id === quiz.id);
          if (!result) {
            hypotheticalScores.value[`quiz_${quiz.id}`] = quiz.total_score;
          }
        });
      }
    };
    const projectedTotalScore = computed(() => {
      let score = 0;
      props.course_assignments.forEach((assignment) => {
        const key = `assignment_${assignment.id}`;
        if (hypotheticalScores.value[key] !== void 0) {
          score += Number(hypotheticalScores.value[key]) || 0;
        } else {
          const answer = props.member_assignments_answers.data.find((a) => a.assignment_id === assignment.id);
          score += answer ? answer.points || 0 : 0;
        }
      });
      props.course_quizzes.forEach((quiz) => {
        const key = `quiz_${quiz.id}`;
        if (hypotheticalScores.value[key] !== void 0) {
          score += Number(hypotheticalScores.value[key]) || 0;
        } else {
          const result = props.member_quizes_results.data.find((r) => r.quiz_id === quiz.id);
          score += result ? result.score || 0 : 0;
        }
      });
      return score;
    });
    const projectedScorePercentage = computed(() => {
      if (!props.course.data.total_score || props.course.data.total_score === 0) return 0;
      return projectedTotalScore.value / props.course.data.total_score * 100;
    });
    const projectedGrade = computed(() => {
      const score = projectedTotalScore.value;
      const passingThreshold = props.course.data.total_score / 2;
      if (score <= passingThreshold) return { grade: "0", label: "0 (\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19)", status: "fail" };
      const percentage = projectedScorePercentage.value;
      if (percentage >= 90) return { grade: "4.0", label: "4.0 (\u0E14\u0E35\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21)", status: "excellent" };
      if (percentage >= 85) return { grade: "3.5", label: "3.5 (\u0E14\u0E35\u0E21\u0E32\u0E01)", status: "very-good" };
      if (percentage >= 80) return { grade: "3.0", label: "3.0 (\u0E14\u0E35)", status: "good" };
      if (percentage >= 75) return { grade: "2.5", label: "2.5 (\u0E04\u0E48\u0E2D\u0E19\u0E02\u0E49\u0E32\u0E07\u0E14\u0E35)", status: "fairly-good" };
      if (percentage >= 70) return { grade: "2.0", label: "2.0 (\u0E1E\u0E2D\u0E43\u0E0A\u0E49)", status: "fair" };
      if (percentage >= 60) return { grade: "1.5", label: "1.5 (\u0E1C\u0E48\u0E32\u0E19\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33)", status: "minimum-pass" };
      if (percentage > passingThreshold / props.course.data.total_score * 100) return { grade: "1.0", label: "1.0 (\u0E1C\u0E48\u0E32\u0E19)", status: "pass" };
      return { grade: "0", label: "0 (\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19)", status: "fail" };
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_sfc_main$1, {
        isCourseAdmin: props.isCourseAdmin,
        course: props.course,
        lessons: props.lessons,
        groups: props.groups,
        activeTab: 4
      }, {
        courseContent: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b;
          if (_push2) {
            _push2(`<div class="bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 rounded-2xl shadow-2xl p-8 mb-8 text-white relative overflow-hidden"${_scopeId}><div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"${_scopeId}></div><div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full blur-2xl"${_scopeId}></div><div class="relative z-10"${_scopeId}><div class="flex flex-col md:flex-row justify-between items-start md:items-center"${_scopeId}><div class="mb-6 md:mb-0"${_scopeId}><div class="flex items-center gap-4 mb-3"${_scopeId}><button class="p-2 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors print:hidden"${_scopeId}><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"${_scopeId}></path></svg></button><h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-blue-100"${_scopeId}> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E04\u0E27\u0E32\u0E21\u0E01\u0E49\u0E32\u0E27\u0E2B\u0E19\u0E49\u0E32\u0E40\u0E01\u0E23\u0E14 </h1></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6"${_scopeId}><div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25"${_scopeId}><p class="text-sm opacity-90 mb-1"${_scopeId}>\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48</p><p class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(props.member.data.order_number)}</p></div><div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25"${_scopeId}><p class="text-sm opacity-90 mb-1"${_scopeId}>\u0E40\u0E25\u0E02\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27</p><p class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(props.member.data.member_code)}</p></div><div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25"${_scopeId}><p class="text-sm opacity-90 mb-1"${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p><p class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(props.member.data.member_name)}</p></div><div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25"${_scopeId}><p class="text-sm opacity-90 mb-1"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</p><p class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(props.member.data.role === 4 ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A" : "\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")}</p></div></div></div><div class="flex gap-3 print:hidden"${_scopeId}><button class="${ssrRenderClass(`flex items-center gap-2 px-4 py-2 ${isCalculatorOpen.value ? "bg-white text-indigo-600 shadow-lg" : "bg-white/20 text-white hover:bg-white/30"} rounded-xl backdrop-blur-md transition-all font-medium border border-transparent`)}"${_scopeId}><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"${_scopeId}></path></svg> ${ssrInterpolate(isCalculatorOpen.value ? "\u0E1B\u0E34\u0E14\u0E04\u0E33\u0E19\u0E27\u0E13\u0E40\u0E01\u0E23\u0E14" : "\u0E04\u0E33\u0E19\u0E27\u0E13\u0E40\u0E01\u0E23\u0E14")}</button><button class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl backdrop-blur-md transition-all font-medium"${_scopeId}><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"${_scopeId}></path></svg> \u0E1E\u0E34\u0E21\u0E1E\u0E4C / \u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 PDF </button></div></div></div></div>`);
            if (isCalculatorOpen.value) {
              _push2(`<div class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl shadow-xl p-8 mb-8 border border-indigo-100 print:hidden"${_scopeId}><div class="flex justify-between items-start mb-6"${_scopeId}><h2 class="text-2xl font-bold text-indigo-800 flex items-center"${_scopeId}><span class="p-2 bg-indigo-200 rounded-lg mr-3"${_scopeId}><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"${_scopeId}></path></svg></span> \u0E08\u0E33\u0E25\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E33\u0E19\u0E27\u0E13\u0E40\u0E01\u0E23\u0E14 (Grade Calculator) </h2><button class="text-gray-500 hover:text-gray-700"${_scopeId}><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"${_scopeId}></path></svg></button></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-8"${_scopeId}><div class="lg:col-span-2 space-y-6"${_scopeId}><div class="bg-white/50 rounded-xl p-4"${_scopeId}><h3 class="font-bold text-gray-700 mb-3 flex items-center"${_scopeId}><span class="w-2 h-2 bg-blue-500 rounded-full mr-2"${_scopeId}></span> \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E48\u0E07 / \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E15\u0E23\u0E27\u0E08 </h3><div class="grid grid-cols-1 md:grid-cols-2 gap-4"${_scopeId}>`);
              if (!props.member_assignments_answers.data.find((a) => a.assignment_id === _ctx.assign.id) || !props.member_assignments_answers.data.find((a) => a.assignment_id === _ctx.assign.id).points) {
                _push2(`<!--[-->`);
                ssrRenderList(props.course_assignments, (assign) => {
                  _push2(`<div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm"${_scopeId}><div class="flex justify-between text-sm mb-2"${_scopeId}><span class="font-medium text-gray-700 truncate pr-2"${ssrRenderAttr("title", assign.title)}${_scopeId}>${ssrInterpolate(truncateText(assign.title, 30).text)}</span><span class="text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full"${_scopeId}>Max: ${ssrInterpolate(assign.points)}</span></div><div class="relative"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Textarea), {
                    modelValue: hypotheticalScores.value[`assignment_${assign.id}`],
                    "onUpdate:modelValue": ($event) => hypotheticalScores.value[`assignment_${assign.id}`] = $event,
                    max: assign.points,
                    rows: "1",
                    class: "w-full",
                    placeholder: "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E04\u0E32\u0E14\u0E2B\u0E27\u0E31\u0E07"
                  }, null, _parent2, _scopeId));
                  _push2(`</div></div>`);
                });
                _push2(`<!--]-->`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></div><div class="bg-white/50 rounded-xl p-4"${_scopeId}><h3 class="font-bold text-gray-700 mb-3 flex items-center"${_scopeId}><span class="w-2 h-2 bg-purple-500 rounded-full mr-2"${_scopeId}></span> \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E17\u0E33 </h3><div class="grid grid-cols-1 md:grid-cols-2 gap-4"${_scopeId}>`);
              if (!props.member_quizes_results.data.find((r) => r.quiz_id === _ctx.quiz.id)) {
                _push2(`<!--[-->`);
                ssrRenderList(props.course_quizzes, (quiz) => {
                  _push2(`<div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm"${_scopeId}><div class="flex justify-between text-sm mb-2"${_scopeId}><span class="font-medium text-gray-700 truncate pr-2"${ssrRenderAttr("title", quiz.title)}${_scopeId}>${ssrInterpolate(truncateText(quiz.title, 30).text)}</span><span class="text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full"${_scopeId}>Max: ${ssrInterpolate(quiz.total_score)}</span></div>`);
                  _push2(ssrRenderComponent(unref(Textarea), {
                    modelValue: hypotheticalScores.value[`quiz_${quiz.id}`],
                    "onUpdate:modelValue": ($event) => hypotheticalScores.value[`quiz_${quiz.id}`] = $event,
                    max: quiz.total_score,
                    rows: "1",
                    class: "w-full",
                    placeholder: "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E04\u0E32\u0E14\u0E2B\u0E27\u0E31\u0E07"
                  }, null, _parent2, _scopeId));
                  _push2(`</div>`);
                });
                _push2(`<!--]-->`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></div></div><div class="relative"${_scopeId}><div class="sticky top-4 bg-white rounded-2xl p-6 shadow-lg border border-indigo-100 text-center ring-4 ring-indigo-50/50"${_scopeId}><p class="text-gray-500 font-medium mb-1 tracking-wide uppercase text-xs"${_scopeId}>\u0E40\u0E01\u0E23\u0E14\u0E17\u0E35\u0E48\u0E04\u0E32\u0E14\u0E27\u0E48\u0E32\u0E08\u0E30\u0E44\u0E14\u0E49</p><div class="my-4"${_scopeId}><div class="${ssrRenderClass(`inline-block px-8 py-6 rounded-2xl border-2 transition-all duration-300 transform hover:scale-105 ${getGradeColor(projectedGrade.value.status)}`)}"${_scopeId}><p class="text-6xl font-black mb-1 leading-none"${_scopeId}>${ssrInterpolate(projectedGrade.value.grade)}</p><p class="text-sm font-bold opacity-90"${_scopeId}>${ssrInterpolate(projectedGrade.value.label)}</p></div></div><div class="bg-gray-50 rounded-xl p-4 mb-4"${_scopeId}><div class="flex justify-between items-center mb-2"${_scopeId}><span class="text-sm text-gray-600"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</span><span class="font-bold text-gray-800"${_scopeId}>${ssrInterpolate(totalAchievedScore.value)}</span></div><div class="flex justify-between items-center mb-2"${_scopeId}><span class="text-sm text-gray-600"${_scopeId}>+ \u0E04\u0E30\u0E41\u0E19\u0E19\u0E08\u0E33\u0E25\u0E2D\u0E07</span><span class="font-bold text-indigo-600"${_scopeId}>+${ssrInterpolate((projectedTotalScore.value - totalAchievedScore.value).toFixed(0))}</span></div><div class="border-t border-gray-200 my-2 pt-2 flex justify-between items-end"${_scopeId}><span class="text-sm font-bold text-gray-700"${_scopeId}>\u0E23\u0E27\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span><div class="text-right"${_scopeId}><span class="text-lg font-black text-indigo-700 block"${_scopeId}>${ssrInterpolate(projectedTotalScore.value)} / ${ssrInterpolate(props.course.data.total_score)}</span><span class="text-xs text-gray-500 font-medium"${_scopeId}>(${ssrInterpolate(projectedScorePercentage.value.toFixed(1))}%)</span></div></div></div><button class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm font-medium transition-colors"${_scopeId}> \u0E1B\u0E34\u0E14\u0E40\u0E04\u0E23\u0E37\u0E48\u0E2D\u0E07\u0E04\u0E34\u0E14\u0E40\u0E25\u0E02 </button></div></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100"${_scopeId}><h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center"${_scopeId}><div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4"${_scopeId}><svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"${_scopeId}></path></svg></div> \u0E2A\u0E23\u0E38\u0E1B\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 </h2><div class="grid grid-cols-1 md:grid-cols-3 gap-6"${_scopeId}><div class="text-center"${_scopeId}><div class="${ssrRenderClass(`inline-block px-10 py-8 rounded-2xl border-2 transition-all duration-300 ${getGradeColor(calculatedGrade.value.status)}`)}"${_scopeId}><p class="text-sm font-bold mb-3 uppercase tracking-wider"${_scopeId}>\u0E40\u0E01\u0E23\u0E14</p><p class="text-5xl font-bold mb-2"${_scopeId}>${ssrInterpolate(calculatedGrade.value.grade)}</p><p class="text-sm font-medium"${_scopeId}>${ssrInterpolate(calculatedGrade.value.label)}</p></div></div><div class="flex flex-col justify-center items-center"${_scopeId}><div class="relative group"${_scopeId}><div class="absolute inset-0 rounded-full blur-xl opacity-20 group-hover:opacity-30 transition-opacity duration-200" style="${ssrRenderStyle(`background: ${getStrokeColor(scorePercentage.value)}`)}"${_scopeId}></div><svg class="w-40 h-40 transform -rotate-90 relative z-10"${_scopeId}><defs${_scopeId}><linearGradient id="gradient-emerald" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#10b981", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#34d399", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-green" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#22c55e", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#4ade80", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-teal" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#14b8a6", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#2dd4bf", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-blue" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#3b82f6", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#60a5fa", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-indigo" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#6366f1", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#818cf8", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-yellow" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#eab308", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#facc15", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-orange" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#f97316", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#fb923c", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient><linearGradient id="gradient-red" x1="0%" y1="0%" x2="100%" y2="100%"${_scopeId}><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#ef4444", "stop-opacity": "1" })}"${_scopeId}></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#f87171", "stop-opacity": "1" })}"${_scopeId}></stop></linearGradient></defs><circle cx="80" cy="80" r="60" stroke="#f3f4f6" stroke-width="12" fill="none"${_scopeId}></circle><circle cx="80" cy="80" r="60"${ssrRenderAttr("stroke", getProgressGradient(scorePercentage.value))} stroke-width="12" fill="none"${ssrRenderAttr("stroke-dasharray", getStrokeDasharray(scorePercentage.value).strokeDasharray)}${ssrRenderAttr("stroke-dashoffset", getStrokeDasharray(scorePercentage.value).strokeDashoffset)} stroke-linecap="round" class="transition-all duration-500 ease-out"${_scopeId}></circle></svg><div class="absolute inset-0 flex flex-col items-center justify-center"${_scopeId}><span class="${ssrRenderClass(`text-3xl font-bold ${getProgressColor(scorePercentage.value)}`)}"${_scopeId}>${ssrInterpolate(scorePercentage.value.toFixed(1))}% </span><span class="text-sm text-gray-600 mt-1 font-medium"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21</span></div></div><div class="mt-4 text-center bg-gray-50 rounded-xl px-4 py-2"${_scopeId}><p class="text-sm font-semibold text-gray-700"${_scopeId}>${ssrInterpolate(formatScore(totalAchievedScore.value, props.course.data.total_score))}</p></div></div><div class="flex flex-col justify-center items-center"${_scopeId}><div class="${ssrRenderClass(`px-8 py-6 rounded-2xl transition-all duration-300 ${passFailStatus.value === "\u0E1C\u0E48\u0E32\u0E19" ? "bg-gradient-to-br from-green-50 to-emerald-100 border-2 border-green-300 shadow-lg shadow-green-100" : "bg-gradient-to-br from-red-50 to-pink-100 border-2 border-red-300 shadow-lg shadow-red-100"}`)}"${_scopeId}><p class="text-sm font-bold mb-2 uppercase tracking-wider"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</p><p class="${ssrRenderClass(`text-3xl font-bold ${passFailStatus.value === "\u0E1C\u0E48\u0E32\u0E19" ? "text-green-600" : "text-red-600"}`)}"${_scopeId}>${ssrInterpolate(passFailStatus.value)}</p></div></div></div>`);
            if ((_a = props.member) == null ? void 0 : _a.data.notes_comments) {
              _push2(`<div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 transition-all duration-200"${_scopeId}><h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center"${_scopeId}><svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"${_scopeId}></path></svg> \u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38 </h3><p class="text-gray-700 leading-relaxed"${_scopeId}>${ssrInterpolate(props.member.data.notes_comments)}</p></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100 transition-all duration-200"${_scopeId}><h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center"${_scopeId}><div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4"${_scopeId}><svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"${_scopeId}></path></svg></div> \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 </h2><div class="overflow-x-auto"${_scopeId}><table class="w-full"${_scopeId}><thead${_scopeId}><tr class="border-b-2 border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100"${_scopeId}><th class="text-left py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E25\u0E33\u0E14\u0E31\u0E1A</th><th class="text-left py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D</th><th class="text-center py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19</th><th class="text-center py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C</th><th class="text-center py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(props.course_assignments, (assignment, index) => {
              var _a2, _b2, _c, _d;
              _push2(`<tr class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200"${_scopeId}><td class="py-4 px-4"${_scopeId}><span class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 text-white rounded-full font-bold shadow-lg"${_scopeId}>${ssrInterpolate(index + 1)}</span></td><td class="py-4 px-4"${_scopeId}><div class="max-w-md"${_scopeId}><p class="font-semibold text-gray-800 text-lg"${_scopeId}>${ssrInterpolate(expandedAssignments.value[assignment.id] ? assignment.title : truncateText(assignment.title, 50).text)}</p>`);
              if (truncateText(assignment.title, 50).isTruncated) {
                _push2(`<button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 transition-colors duration-200"${_scopeId}>${ssrInterpolate(expandedAssignments.value[assignment.id] ? "\u0E41\u0E2A\u0E14\u0E07\u0E19\u0E49\u0E2D\u0E22\u0E25\u0E07" : "\u0E2D\u0E48\u0E32\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></td><td class="py-4 px-4 text-center"${_scopeId}><span class="font-bold text-gray-700 text-lg"${_scopeId}>${ssrInterpolate(((_a2 = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _a2.points) || 0)}/${ssrInterpolate(assignment.points)}</span></td><td class="py-4 px-4 text-center"${_scopeId}><div class="flex items-center justify-center"${_scopeId}><div class="w-20 bg-gray-200 rounded-full h-3 mr-3 shadow-inner"${_scopeId}><div class="${ssrRenderClass(`h-3 rounded-full shadow-sm transition-all duration-300 ${getItemProgressColor((((_b2 = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _b2.points) || 0) / assignment.points * 100)}`)}" style="${ssrRenderStyle(`width: ${Math.min((((_c = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _c.points) || 0) / assignment.points * 100, 100)}%`)}"${_scopeId}></div></div><span class="text-sm font-semibold text-gray-700"${_scopeId}>${ssrInterpolate(((((_d = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _d.points) || 0) / assignment.points * 100).toFixed(1))}% </span></div></td><td class="py-4 px-4 text-center"${_scopeId}>`);
              if (props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) {
                _push2(`<span class="${ssrRenderClass(`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(true, "assignment")}`)}"${_scopeId}> \u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27 </span>`);
              } else {
                _push2(`<span class="${ssrRenderClass(`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(false, "assignment")}`)}"${_scopeId}> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E2A\u0E48\u0E07 </span>`);
              }
              _push2(`</td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div></div><div class="bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100 transition-all duration-200"${_scopeId}><h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center"${_scopeId}><div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4"${_scopeId}><svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"${_scopeId}></path></svg></div> \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E19\u0E2B\u0E25\u0E31\u0E01\u0E2A\u0E39\u0E15\u0E23 </h2><div class="overflow-x-auto"${_scopeId}><table class="w-full"${_scopeId}><thead${_scopeId}><tr class="border-b-2 border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100"${_scopeId}><th class="text-left py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E25\u0E33\u0E14\u0E31\u0E1A</th><th class="text-left py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</th><th class="text-center py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19</th><th class="text-center py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C</th><th class="text-center py-4 px-4 font-bold text-gray-700"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(props.course_quizzes, (quizze, index) => {
              var _a2, _b2, _c, _d;
              _push2(`<tr class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200"${_scopeId}><td class="py-4 px-4"${_scopeId}><span class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 text-white rounded-full font-bold shadow-lg"${_scopeId}>${ssrInterpolate(index + 1)}</span></td><td class="py-4 px-4"${_scopeId}><div class="max-w-md"${_scopeId}><p class="font-semibold text-gray-800 text-lg"${_scopeId}>${ssrInterpolate(expandedQuizzes.value[quizze.id] ? quizze.title : truncateText(quizze.title, 50).text)}</p>`);
              if (truncateText(quizze.title, 50).isTruncated) {
                _push2(`<button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 transition-colors duration-200"${_scopeId}>${ssrInterpolate(expandedQuizzes.value[quizze.id] ? "\u0E41\u0E2A\u0E14\u0E07\u0E19\u0E49\u0E2D\u0E22\u0E25\u0E07" : "\u0E2D\u0E48\u0E32\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></td><td class="py-4 px-4 text-center"${_scopeId}><span class="font-bold text-gray-700 text-lg"${_scopeId}>${ssrInterpolate(((_a2 = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _a2.score) || 0)}/${ssrInterpolate(quizze.total_score)}</span></td><td class="py-4 px-4 text-center"${_scopeId}><div class="flex items-center justify-center"${_scopeId}><div class="w-20 bg-gray-200 rounded-full h-3 mr-3 shadow-inner"${_scopeId}><div class="${ssrRenderClass(`h-3 rounded-full shadow-sm transition-all duration-300 ${getItemProgressColor((((_b2 = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _b2.score) || 0) / quizze.total_score * 100)}`)}" style="${ssrRenderStyle(`width: ${Math.min((((_c = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _c.score) || 0) / quizze.total_score * 100, 100)}%`)}"${_scopeId}></div></div><span class="text-sm font-semibold text-gray-700"${_scopeId}>${ssrInterpolate(((((_d = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _d.score) || 0) / quizze.total_score * 100).toFixed(1))}% </span></div></td><td class="py-4 px-4 text-center"${_scopeId}>`);
              if (props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) {
                _push2(`<span class="${ssrRenderClass(`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(true, "quiz")}`)}"${_scopeId}> \u0E17\u0E33\u0E41\u0E25\u0E49\u0E27 </span>`);
              } else {
                _push2(`<span class="${ssrRenderClass(`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(false, "quiz")}`)}"${_scopeId}> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E17\u0E33 </span>`);
              }
              _push2(`</td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div></div><div class="bg-gradient-to-br from-indigo-50 via-blue-50 to-purple-50 rounded-2xl shadow-2xl p-8 border border-indigo-200 transition-all duration-200"${_scopeId}><h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center"${_scopeId}><div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4"${_scopeId}><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"${_scopeId}></path></svg></div> \u0E21\u0E32\u0E15\u0E23\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E43\u0E2B\u0E49\u0E40\u0E01\u0E23\u0E14 </h3><div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4"${_scopeId}><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-emerald-200"${_scopeId}><p class="font-bold text-emerald-600 text-lg"${_scopeId}>4.0</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>90-100%</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-green-200"${_scopeId}><p class="font-bold text-green-600 text-lg"${_scopeId}>3.5</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>85-89%</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-teal-200"${_scopeId}><p class="font-bold text-teal-600 text-lg"${_scopeId}>3.0</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>80-84%</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-blue-200"${_scopeId}><p class="font-bold text-blue-600 text-lg"${_scopeId}>2.5</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>75-79%</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-indigo-200"${_scopeId}><p class="font-bold text-indigo-600 text-lg"${_scopeId}>2.0</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>70-74%</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-yellow-200"${_scopeId}><p class="font-bold text-yellow-600 text-lg"${_scopeId}>1.5</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>60-69%</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-red-200"${_scopeId}><p class="font-bold text-red-600 text-lg"${_scopeId}>0</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>&lt;50%&gt;</p></div><div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-200"${_scopeId}><p class="font-bold text-gray-600 text-lg"${_scopeId}>\u0E23.</p><p class="text-xs text-gray-600 mt-1"${_scopeId}>\u0E02\u0E32\u0E14\u0E07\u0E32\u0E19</p></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 rounded-2xl shadow-2xl p-8 mb-8 text-white relative overflow-hidden" }, [
                createVNode("div", { class: "absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl" }),
                createVNode("div", { class: "absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full blur-2xl" }),
                createVNode("div", { class: "relative z-10" }, [
                  createVNode("div", { class: "flex flex-col md:flex-row justify-between items-start md:items-center" }, [
                    createVNode("div", { class: "mb-6 md:mb-0" }, [
                      createVNode("div", { class: "flex items-center gap-4 mb-3" }, [
                        createVNode("button", {
                          onClick: ($event) => _ctx.$router.back(),
                          class: "p-2 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors print:hidden"
                        }, [
                          (openBlock(), createBlock("svg", {
                            xmlns: "http://www.w3.org/2000/svg",
                            class: "h-6 w-6",
                            fill: "none",
                            viewBox: "0 0 24 24",
                            stroke: "currentColor"
                          }, [
                            createVNode("path", {
                              "stroke-linecap": "round",
                              "stroke-linejoin": "round",
                              "stroke-width": "2",
                              d: "M10 19l-7-7m0 0l7-7m-7 7h18"
                            })
                          ]))
                        ], 8, ["onClick"]),
                        createVNode("h1", { class: "text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-blue-100" }, " \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E04\u0E27\u0E32\u0E21\u0E01\u0E49\u0E32\u0E27\u0E2B\u0E19\u0E49\u0E32\u0E40\u0E01\u0E23\u0E14 ")
                      ]),
                      createVNode("div", { class: "grid grid-cols-1 md:grid-cols-3 gap-4 mt-6" }, [
                        createVNode("div", { class: "bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25" }, [
                          createVNode("p", { class: "text-sm opacity-90 mb-1" }, "\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48"),
                          createVNode("p", { class: "text-2xl font-bold" }, toDisplayString(props.member.data.order_number), 1)
                        ]),
                        createVNode("div", { class: "bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25" }, [
                          createVNode("p", { class: "text-sm opacity-90 mb-1" }, "\u0E40\u0E25\u0E02\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27"),
                          createVNode("p", { class: "text-2xl font-bold" }, toDisplayString(props.member.data.member_code), 1)
                        ]),
                        createVNode("div", { class: "bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25" }, [
                          createVNode("p", { class: "text-sm opacity-90 mb-1" }, "\u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01"),
                          createVNode("p", { class: "text-2xl font-bold" }, toDisplayString(props.member.data.member_name), 1)
                        ]),
                        createVNode("div", { class: "bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25" }, [
                          createVNode("p", { class: "text-sm opacity-90 mb-1" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19"),
                          createVNode("p", { class: "text-2xl font-bold" }, toDisplayString(props.member.data.role === 4 ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A" : "\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"), 1)
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "flex gap-3 print:hidden" }, [
                      createVNode("button", {
                        onClick: toggleCalculator,
                        class: `flex items-center gap-2 px-4 py-2 ${isCalculatorOpen.value ? "bg-white text-indigo-600 shadow-lg" : "bg-white/20 text-white hover:bg-white/30"} rounded-xl backdrop-blur-md transition-all font-medium border border-transparent`
                      }, [
                        (openBlock(), createBlock("svg", {
                          xmlns: "http://www.w3.org/2000/svg",
                          class: "h-5 w-5",
                          fill: "none",
                          viewBox: "0 0 24 24",
                          stroke: "currentColor"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            "stroke-width": "2",
                            d: "M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                          })
                        ])),
                        createTextVNode(" " + toDisplayString(isCalculatorOpen.value ? "\u0E1B\u0E34\u0E14\u0E04\u0E33\u0E19\u0E27\u0E13\u0E40\u0E01\u0E23\u0E14" : "\u0E04\u0E33\u0E19\u0E27\u0E13\u0E40\u0E01\u0E23\u0E14"), 1)
                      ], 2),
                      createVNode("button", {
                        onClick: handlePrint,
                        class: "flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl backdrop-blur-md transition-all font-medium"
                      }, [
                        (openBlock(), createBlock("svg", {
                          xmlns: "http://www.w3.org/2000/svg",
                          class: "h-5 w-5",
                          fill: "none",
                          viewBox: "0 0 24 24",
                          stroke: "currentColor"
                        }, [
                          createVNode("path", {
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                            "stroke-width": "2",
                            d: "M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                          })
                        ])),
                        createTextVNode(" \u0E1E\u0E34\u0E21\u0E1E\u0E4C / \u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 PDF ")
                      ])
                    ])
                  ])
                ])
              ]),
              isCalculatorOpen.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl shadow-xl p-8 mb-8 border border-indigo-100 print:hidden"
              }, [
                createVNode("div", { class: "flex justify-between items-start mb-6" }, [
                  createVNode("h2", { class: "text-2xl font-bold text-indigo-800 flex items-center" }, [
                    createVNode("span", { class: "p-2 bg-indigo-200 rounded-lg mr-3" }, [
                      (openBlock(), createBlock("svg", {
                        xmlns: "http://www.w3.org/2000/svg",
                        class: "h-6 w-6 text-indigo-700",
                        fill: "none",
                        viewBox: "0 0 24 24",
                        stroke: "currentColor"
                      }, [
                        createVNode("path", {
                          "stroke-linecap": "round",
                          "stroke-linejoin": "round",
                          "stroke-width": "2",
                          d: "M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                        })
                      ]))
                    ]),
                    createTextVNode(" \u0E08\u0E33\u0E25\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E33\u0E19\u0E27\u0E13\u0E40\u0E01\u0E23\u0E14 (Grade Calculator) ")
                  ]),
                  createVNode("button", {
                    onClick: toggleCalculator,
                    class: "text-gray-500 hover:text-gray-700"
                  }, [
                    (openBlock(), createBlock("svg", {
                      xmlns: "http://www.w3.org/2000/svg",
                      class: "h-6 w-6",
                      fill: "none",
                      viewBox: "0 0 24 24",
                      stroke: "currentColor"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M6 18L18 6M6 6l12 12"
                      })
                    ]))
                  ])
                ]),
                createVNode("div", { class: "grid grid-cols-1 lg:grid-cols-3 gap-8" }, [
                  createVNode("div", { class: "lg:col-span-2 space-y-6" }, [
                    createVNode("div", { class: "bg-white/50 rounded-xl p-4" }, [
                      createVNode("h3", { class: "font-bold text-gray-700 mb-3 flex items-center" }, [
                        createVNode("span", { class: "w-2 h-2 bg-blue-500 rounded-full mr-2" }),
                        createTextVNode(" \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E48\u0E07 / \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E15\u0E23\u0E27\u0E08 ")
                      ]),
                      createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-4" }, [
                        !props.member_assignments_answers.data.find((a) => a.assignment_id === _ctx.assign.id) || !props.member_assignments_answers.data.find((a) => a.assignment_id === _ctx.assign.id).points ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(props.course_assignments, (assign) => {
                          return openBlock(), createBlock("div", {
                            key: assign.id,
                            class: "bg-white p-3 rounded-lg border border-gray-100 shadow-sm"
                          }, [
                            createVNode("div", { class: "flex justify-between text-sm mb-2" }, [
                              createVNode("span", {
                                class: "font-medium text-gray-700 truncate pr-2",
                                title: assign.title
                              }, toDisplayString(truncateText(assign.title, 30).text), 9, ["title"]),
                              createVNode("span", { class: "text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full" }, "Max: " + toDisplayString(assign.points), 1)
                            ]),
                            createVNode("div", { class: "relative" }, [
                              createVNode(unref(Textarea), {
                                modelValue: hypotheticalScores.value[`assignment_${assign.id}`],
                                "onUpdate:modelValue": ($event) => hypotheticalScores.value[`assignment_${assign.id}`] = $event,
                                max: assign.points,
                                rows: "1",
                                class: "w-full",
                                placeholder: "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E04\u0E32\u0E14\u0E2B\u0E27\u0E31\u0E07"
                              }, null, 8, ["modelValue", "onUpdate:modelValue", "max"])
                            ])
                          ]);
                        }), 128)) : createCommentVNode("", true)
                      ])
                    ]),
                    createVNode("div", { class: "bg-white/50 rounded-xl p-4" }, [
                      createVNode("h3", { class: "font-bold text-gray-700 mb-3 flex items-center" }, [
                        createVNode("span", { class: "w-2 h-2 bg-purple-500 rounded-full mr-2" }),
                        createTextVNode(" \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E17\u0E33 ")
                      ]),
                      createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-4" }, [
                        !props.member_quizes_results.data.find((r) => r.quiz_id === _ctx.quiz.id) ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(props.course_quizzes, (quiz) => {
                          return openBlock(), createBlock("div", {
                            key: quiz.id,
                            class: "bg-white p-3 rounded-lg border border-gray-100 shadow-sm"
                          }, [
                            createVNode("div", { class: "flex justify-between text-sm mb-2" }, [
                              createVNode("span", {
                                class: "font-medium text-gray-700 truncate pr-2",
                                title: quiz.title
                              }, toDisplayString(truncateText(quiz.title, 30).text), 9, ["title"]),
                              createVNode("span", { class: "text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full" }, "Max: " + toDisplayString(quiz.total_score), 1)
                            ]),
                            createVNode(unref(Textarea), {
                              modelValue: hypotheticalScores.value[`quiz_${quiz.id}`],
                              "onUpdate:modelValue": ($event) => hypotheticalScores.value[`quiz_${quiz.id}`] = $event,
                              max: quiz.total_score,
                              rows: "1",
                              class: "w-full",
                              placeholder: "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E04\u0E32\u0E14\u0E2B\u0E27\u0E31\u0E07"
                            }, null, 8, ["modelValue", "onUpdate:modelValue", "max"])
                          ]);
                        }), 128)) : createCommentVNode("", true)
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "relative" }, [
                    createVNode("div", { class: "sticky top-4 bg-white rounded-2xl p-6 shadow-lg border border-indigo-100 text-center ring-4 ring-indigo-50/50" }, [
                      createVNode("p", { class: "text-gray-500 font-medium mb-1 tracking-wide uppercase text-xs" }, "\u0E40\u0E01\u0E23\u0E14\u0E17\u0E35\u0E48\u0E04\u0E32\u0E14\u0E27\u0E48\u0E32\u0E08\u0E30\u0E44\u0E14\u0E49"),
                      createVNode("div", { class: "my-4" }, [
                        createVNode("div", {
                          class: `inline-block px-8 py-6 rounded-2xl border-2 transition-all duration-300 transform hover:scale-105 ${getGradeColor(projectedGrade.value.status)}`
                        }, [
                          createVNode("p", { class: "text-6xl font-black mb-1 leading-none" }, toDisplayString(projectedGrade.value.grade), 1),
                          createVNode("p", { class: "text-sm font-bold opacity-90" }, toDisplayString(projectedGrade.value.label), 1)
                        ], 2)
                      ]),
                      createVNode("div", { class: "bg-gray-50 rounded-xl p-4 mb-4" }, [
                        createVNode("div", { class: "flex justify-between items-center mb-2" }, [
                          createVNode("span", { class: "text-sm text-gray-600" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19"),
                          createVNode("span", { class: "font-bold text-gray-800" }, toDisplayString(totalAchievedScore.value), 1)
                        ]),
                        createVNode("div", { class: "flex justify-between items-center mb-2" }, [
                          createVNode("span", { class: "text-sm text-gray-600" }, "+ \u0E04\u0E30\u0E41\u0E19\u0E19\u0E08\u0E33\u0E25\u0E2D\u0E07"),
                          createVNode("span", { class: "font-bold text-indigo-600" }, "+" + toDisplayString((projectedTotalScore.value - totalAchievedScore.value).toFixed(0)), 1)
                        ]),
                        createVNode("div", { class: "border-t border-gray-200 my-2 pt-2 flex justify-between items-end" }, [
                          createVNode("span", { class: "text-sm font-bold text-gray-700" }, "\u0E23\u0E27\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                          createVNode("div", { class: "text-right" }, [
                            createVNode("span", { class: "text-lg font-black text-indigo-700 block" }, toDisplayString(projectedTotalScore.value) + " / " + toDisplayString(props.course.data.total_score), 1),
                            createVNode("span", { class: "text-xs text-gray-500 font-medium" }, "(" + toDisplayString(projectedScorePercentage.value.toFixed(1)) + "%)", 1)
                          ])
                        ])
                      ]),
                      createVNode("button", {
                        onClick: toggleCalculator,
                        class: "w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm font-medium transition-colors"
                      }, " \u0E1B\u0E34\u0E14\u0E40\u0E04\u0E23\u0E37\u0E48\u0E2D\u0E07\u0E04\u0E34\u0E14\u0E40\u0E25\u0E02 ")
                    ])
                  ])
                ])
              ])) : createCommentVNode("", true),
              createVNode("div", { class: "bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100" }, [
                createVNode("h2", { class: "text-3xl font-bold text-gray-800 mb-8 flex items-center" }, [
                  createVNode("div", { class: "p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4" }, [
                    (openBlock(), createBlock("svg", {
                      class: "w-8 h-8 text-white",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                      })
                    ]))
                  ]),
                  createTextVNode(" \u0E2A\u0E23\u0E38\u0E1B\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 ")
                ]),
                createVNode("div", { class: "grid grid-cols-1 md:grid-cols-3 gap-6" }, [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", {
                      class: `inline-block px-10 py-8 rounded-2xl border-2 transition-all duration-300 ${getGradeColor(calculatedGrade.value.status)}`
                    }, [
                      createVNode("p", { class: "text-sm font-bold mb-3 uppercase tracking-wider" }, "\u0E40\u0E01\u0E23\u0E14"),
                      createVNode("p", { class: "text-5xl font-bold mb-2" }, toDisplayString(calculatedGrade.value.grade), 1),
                      createVNode("p", { class: "text-sm font-medium" }, toDisplayString(calculatedGrade.value.label), 1)
                    ], 2)
                  ]),
                  createVNode("div", { class: "flex flex-col justify-center items-center" }, [
                    createVNode("div", { class: "relative group" }, [
                      createVNode("div", {
                        class: "absolute inset-0 rounded-full blur-xl opacity-20 group-hover:opacity-30 transition-opacity duration-200",
                        style: `background: ${getStrokeColor(scorePercentage.value)}`
                      }, null, 4),
                      (openBlock(), createBlock("svg", { class: "w-40 h-40 transform -rotate-90 relative z-10" }, [
                        createVNode("defs", null, [
                          createVNode("linearGradient", {
                            id: "gradient-emerald",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#10b981", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#34d399", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-green",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#22c55e", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#4ade80", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-teal",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#14b8a6", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#2dd4bf", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-blue",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#3b82f6", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#60a5fa", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-indigo",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#6366f1", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#818cf8", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-yellow",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#eab308", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#facc15", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-orange",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#f97316", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#fb923c", "stop-opacity": "1" }
                            })
                          ]),
                          createVNode("linearGradient", {
                            id: "gradient-red",
                            x1: "0%",
                            y1: "0%",
                            x2: "100%",
                            y2: "100%"
                          }, [
                            createVNode("stop", {
                              offset: "0%",
                              style: { "stop-color": "#ef4444", "stop-opacity": "1" }
                            }),
                            createVNode("stop", {
                              offset: "100%",
                              style: { "stop-color": "#f87171", "stop-opacity": "1" }
                            })
                          ])
                        ]),
                        createVNode("circle", {
                          cx: "80",
                          cy: "80",
                          r: "60",
                          stroke: "#f3f4f6",
                          "stroke-width": "12",
                          fill: "none"
                        }),
                        createVNode("circle", {
                          cx: "80",
                          cy: "80",
                          r: "60",
                          stroke: getProgressGradient(scorePercentage.value),
                          "stroke-width": "12",
                          fill: "none",
                          "stroke-dasharray": getStrokeDasharray(scorePercentage.value).strokeDasharray,
                          "stroke-dashoffset": getStrokeDasharray(scorePercentage.value).strokeDashoffset,
                          "stroke-linecap": "round",
                          class: "transition-all duration-500 ease-out"
                        }, null, 8, ["stroke", "stroke-dasharray", "stroke-dashoffset"])
                      ])),
                      createVNode("div", { class: "absolute inset-0 flex flex-col items-center justify-center" }, [
                        createVNode("span", {
                          class: `text-3xl font-bold ${getProgressColor(scorePercentage.value)}`
                        }, toDisplayString(scorePercentage.value.toFixed(1)) + "% ", 3),
                        createVNode("span", { class: "text-sm text-gray-600 mt-1 font-medium" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21")
                      ])
                    ]),
                    createVNode("div", { class: "mt-4 text-center bg-gray-50 rounded-xl px-4 py-2" }, [
                      createVNode("p", { class: "text-sm font-semibold text-gray-700" }, toDisplayString(formatScore(totalAchievedScore.value, props.course.data.total_score)), 1)
                    ])
                  ]),
                  createVNode("div", { class: "flex flex-col justify-center items-center" }, [
                    createVNode("div", {
                      class: `px-8 py-6 rounded-2xl transition-all duration-300 ${passFailStatus.value === "\u0E1C\u0E48\u0E32\u0E19" ? "bg-gradient-to-br from-green-50 to-emerald-100 border-2 border-green-300 shadow-lg shadow-green-100" : "bg-gradient-to-br from-red-50 to-pink-100 border-2 border-red-300 shadow-lg shadow-red-100"}`
                    }, [
                      createVNode("p", { class: "text-sm font-bold mb-2 uppercase tracking-wider" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30"),
                      createVNode("p", {
                        class: `text-3xl font-bold ${passFailStatus.value === "\u0E1C\u0E48\u0E32\u0E19" ? "text-green-600" : "text-red-600"}`
                      }, toDisplayString(passFailStatus.value), 3)
                    ], 2)
                  ])
                ]),
                ((_b = props.member) == null ? void 0 : _b.data.notes_comments) ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 transition-all duration-200"
                }, [
                  createVNode("h3", { class: "text-lg font-bold text-gray-800 mb-3 flex items-center" }, [
                    (openBlock(), createBlock("svg", {
                      class: "w-5 h-5 mr-2 text-blue-600",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                      })
                    ])),
                    createTextVNode(" \u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38 ")
                  ]),
                  createVNode("p", { class: "text-gray-700 leading-relaxed" }, toDisplayString(props.member.data.notes_comments), 1)
                ])) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100 transition-all duration-200" }, [
                createVNode("h2", { class: "text-3xl font-bold text-gray-800 mb-8 flex items-center" }, [
                  createVNode("div", { class: "p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4" }, [
                    (openBlock(), createBlock("svg", {
                      class: "w-8 h-8 text-white",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                      })
                    ]))
                  ]),
                  createTextVNode(" \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 ")
                ]),
                createVNode("div", { class: "overflow-x-auto" }, [
                  createVNode("table", { class: "w-full" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "border-b-2 border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100" }, [
                        createVNode("th", { class: "text-left py-4 px-4 font-bold text-gray-700" }, "\u0E25\u0E33\u0E14\u0E31\u0E1A"),
                        createVNode("th", { class: "text-left py-4 px-4 font-bold text-gray-700" }, "\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D"),
                        createVNode("th", { class: "text-center py-4 px-4 font-bold text-gray-700" }, "\u0E04\u0E30\u0E41\u0E19\u0E19"),
                        createVNode("th", { class: "text-center py-4 px-4 font-bold text-gray-700" }, "\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C"),
                        createVNode("th", { class: "text-center py-4 px-4 font-bold text-gray-700" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(props.course_assignments, (assignment, index) => {
                        var _a2, _b2, _c, _d;
                        return openBlock(), createBlock("tr", {
                          key: assignment.id,
                          class: "border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200"
                        }, [
                          createVNode("td", { class: "py-4 px-4" }, [
                            createVNode("span", { class: "inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 text-white rounded-full font-bold shadow-lg" }, toDisplayString(index + 1), 1)
                          ]),
                          createVNode("td", { class: "py-4 px-4" }, [
                            createVNode("div", { class: "max-w-md" }, [
                              createVNode("p", { class: "font-semibold text-gray-800 text-lg" }, toDisplayString(expandedAssignments.value[assignment.id] ? assignment.title : truncateText(assignment.title, 50).text), 1),
                              truncateText(assignment.title, 50).isTruncated ? (openBlock(), createBlock("button", {
                                key: 0,
                                onClick: ($event) => toggleExpanded(assignment.id, "assignment"),
                                class: "text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 transition-colors duration-200"
                              }, toDisplayString(expandedAssignments.value[assignment.id] ? "\u0E41\u0E2A\u0E14\u0E07\u0E19\u0E49\u0E2D\u0E22\u0E25\u0E07" : "\u0E2D\u0E48\u0E32\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21"), 9, ["onClick"])) : createCommentVNode("", true)
                            ])
                          ]),
                          createVNode("td", { class: "py-4 px-4 text-center" }, [
                            createVNode("span", { class: "font-bold text-gray-700 text-lg" }, toDisplayString(((_a2 = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _a2.points) || 0) + "/" + toDisplayString(assignment.points), 1)
                          ]),
                          createVNode("td", { class: "py-4 px-4 text-center" }, [
                            createVNode("div", { class: "flex items-center justify-center" }, [
                              createVNode("div", { class: "w-20 bg-gray-200 rounded-full h-3 mr-3 shadow-inner" }, [
                                createVNode("div", {
                                  class: `h-3 rounded-full shadow-sm transition-all duration-300 ${getItemProgressColor((((_b2 = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _b2.points) || 0) / assignment.points * 100)}`,
                                  style: `width: ${Math.min((((_c = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _c.points) || 0) / assignment.points * 100, 100)}%`
                                }, null, 6)
                              ]),
                              createVNode("span", { class: "text-sm font-semibold text-gray-700" }, toDisplayString(((((_d = props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id)) == null ? void 0 : _d.points) || 0) / assignment.points * 100).toFixed(1)) + "% ", 1)
                            ])
                          ]),
                          createVNode("td", { class: "py-4 px-4 text-center" }, [
                            props.member_assignments_answers.data.find((answer) => answer.assignment_id === assignment.id) ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: `px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(true, "assignment")}`
                            }, " \u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27 ", 2)) : (openBlock(), createBlock("span", {
                              key: 1,
                              class: `px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(false, "assignment")}`
                            }, " \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E2A\u0E48\u0E07 ", 2))
                          ])
                        ]);
                      }), 128))
                    ])
                  ])
                ])
              ]),
              createVNode("div", { class: "bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100 transition-all duration-200" }, [
                createVNode("h2", { class: "text-3xl font-bold text-gray-800 mb-8 flex items-center" }, [
                  createVNode("div", { class: "p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4" }, [
                    (openBlock(), createBlock("svg", {
                      class: "w-8 h-8 text-white",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                      })
                    ]))
                  ]),
                  createTextVNode(" \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E19\u0E2B\u0E25\u0E31\u0E01\u0E2A\u0E39\u0E15\u0E23 ")
                ]),
                createVNode("div", { class: "overflow-x-auto" }, [
                  createVNode("table", { class: "w-full" }, [
                    createVNode("thead", null, [
                      createVNode("tr", { class: "border-b-2 border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100" }, [
                        createVNode("th", { class: "text-left py-4 px-4 font-bold text-gray-700" }, "\u0E25\u0E33\u0E14\u0E31\u0E1A"),
                        createVNode("th", { class: "text-left py-4 px-4 font-bold text-gray-700" }, "\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A"),
                        createVNode("th", { class: "text-center py-4 px-4 font-bold text-gray-700" }, "\u0E04\u0E30\u0E41\u0E19\u0E19"),
                        createVNode("th", { class: "text-center py-4 px-4 font-bold text-gray-700" }, "\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C"),
                        createVNode("th", { class: "text-center py-4 px-4 font-bold text-gray-700" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30")
                      ])
                    ]),
                    createVNode("tbody", null, [
                      (openBlock(true), createBlock(Fragment, null, renderList(props.course_quizzes, (quizze, index) => {
                        var _a2, _b2, _c, _d;
                        return openBlock(), createBlock("tr", {
                          key: quizze.id,
                          class: "border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200"
                        }, [
                          createVNode("td", { class: "py-4 px-4" }, [
                            createVNode("span", { class: "inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 text-white rounded-full font-bold shadow-lg" }, toDisplayString(index + 1), 1)
                          ]),
                          createVNode("td", { class: "py-4 px-4" }, [
                            createVNode("div", { class: "max-w-md" }, [
                              createVNode("p", { class: "font-semibold text-gray-800 text-lg" }, toDisplayString(expandedQuizzes.value[quizze.id] ? quizze.title : truncateText(quizze.title, 50).text), 1),
                              truncateText(quizze.title, 50).isTruncated ? (openBlock(), createBlock("button", {
                                key: 0,
                                onClick: ($event) => toggleExpanded(quizze.id, "quiz"),
                                class: "text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 transition-colors duration-200"
                              }, toDisplayString(expandedQuizzes.value[quizze.id] ? "\u0E41\u0E2A\u0E14\u0E07\u0E19\u0E49\u0E2D\u0E22\u0E25\u0E07" : "\u0E2D\u0E48\u0E32\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21"), 9, ["onClick"])) : createCommentVNode("", true)
                            ])
                          ]),
                          createVNode("td", { class: "py-4 px-4 text-center" }, [
                            createVNode("span", { class: "font-bold text-gray-700 text-lg" }, toDisplayString(((_a2 = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _a2.score) || 0) + "/" + toDisplayString(quizze.total_score), 1)
                          ]),
                          createVNode("td", { class: "py-4 px-4 text-center" }, [
                            createVNode("div", { class: "flex items-center justify-center" }, [
                              createVNode("div", { class: "w-20 bg-gray-200 rounded-full h-3 mr-3 shadow-inner" }, [
                                createVNode("div", {
                                  class: `h-3 rounded-full shadow-sm transition-all duration-300 ${getItemProgressColor((((_b2 = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _b2.score) || 0) / quizze.total_score * 100)}`,
                                  style: `width: ${Math.min((((_c = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _c.score) || 0) / quizze.total_score * 100, 100)}%`
                                }, null, 6)
                              ]),
                              createVNode("span", { class: "text-sm font-semibold text-gray-700" }, toDisplayString(((((_d = props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id)) == null ? void 0 : _d.score) || 0) / quizze.total_score * 100).toFixed(1)) + "% ", 1)
                            ])
                          ]),
                          createVNode("td", { class: "py-4 px-4 text-center" }, [
                            props.member_quizes_results.data.find((result) => result.quiz_id === quizze.id) ? (openBlock(), createBlock("span", {
                              key: 0,
                              class: `px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(true, "quiz")}`
                            }, " \u0E17\u0E33\u0E41\u0E25\u0E49\u0E27 ", 2)) : (openBlock(), createBlock("span", {
                              key: 1,
                              class: `px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(false, "quiz")}`
                            }, " \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E17\u0E33 ", 2))
                          ])
                        ]);
                      }), 128))
                    ])
                  ])
                ])
              ]),
              createVNode("div", { class: "bg-gradient-to-br from-indigo-50 via-blue-50 to-purple-50 rounded-2xl shadow-2xl p-8 border border-indigo-200 transition-all duration-200" }, [
                createVNode("h3", { class: "text-2xl font-bold text-gray-800 mb-6 flex items-center" }, [
                  createVNode("div", { class: "p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4" }, [
                    (openBlock(), createBlock("svg", {
                      class: "w-6 h-6 text-white",
                      fill: "none",
                      stroke: "currentColor",
                      viewBox: "0 0 24 24"
                    }, [
                      createVNode("path", {
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2",
                        d: "M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      })
                    ]))
                  ]),
                  createTextVNode(" \u0E21\u0E32\u0E15\u0E23\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E43\u0E2B\u0E49\u0E40\u0E01\u0E23\u0E14 ")
                ]),
                createVNode("div", { class: "grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4" }, [
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-emerald-200" }, [
                    createVNode("p", { class: "font-bold text-emerald-600 text-lg" }, "4.0"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "90-100%")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-green-200" }, [
                    createVNode("p", { class: "font-bold text-green-600 text-lg" }, "3.5"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "85-89%")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-teal-200" }, [
                    createVNode("p", { class: "font-bold text-teal-600 text-lg" }, "3.0"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "80-84%")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-blue-200" }, [
                    createVNode("p", { class: "font-bold text-blue-600 text-lg" }, "2.5"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "75-79%")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-indigo-200" }, [
                    createVNode("p", { class: "font-bold text-indigo-600 text-lg" }, "2.0"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "70-74%")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-yellow-200" }, [
                    createVNode("p", { class: "font-bold text-yellow-600 text-lg" }, "1.5"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "60-69%")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-red-200" }, [
                    createVNode("p", { class: "font-bold text-red-600 text-lg" }, "0"),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "<50%>")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-200" }, [
                    createVNode("p", { class: "font-bold text-gray-600 text-lg" }, "\u0E23."),
                    createVNode("p", { class: "text-xs text-gray-600 mt-1" }, "\u0E02\u0E32\u0E14\u0E07\u0E32\u0E19")
                  ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/Progress/MemberGradeProgressDetails.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=MemberGradeProgressDetails-D2H26JeN.mjs.map
