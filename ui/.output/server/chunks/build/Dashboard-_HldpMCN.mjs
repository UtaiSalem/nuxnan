import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderClass, ssrRenderStyle, ssrRenderList } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, f as useHead, d as useAuthStore } from './server.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Dashboard",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Learning Dashboard | Nuxnan"
    });
    const authStore = useAuthStore();
    ref(true);
    ref([]);
    const recentCourses = ref([]);
    const upcomingAssignments = ref([]);
    const recentActivities = ref([]);
    const learningStats = ref({
      totalCourses: 0,
      activeCourses: 0,
      completedCourses: 0,
      totalLessons: 0,
      completedLessons: 0,
      totalAssignments: 0,
      completedAssignments: 0,
      totalQuizzes: 0,
      completedQuizzes: 0,
      attendanceRate: 0,
      averageScore: 0
    });
    const userName = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.name) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49";
    });
    const greeting = computed(() => {
      const hour = (/* @__PURE__ */ new Date()).getHours();
      if (hour < 12) return "\u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E15\u0E2D\u0E19\u0E40\u0E0A\u0E49\u0E32";
      if (hour < 18) return "\u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E15\u0E2D\u0E19\u0E1A\u0E48\u0E32\u0E22";
      return "\u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E15\u0E2D\u0E19\u0E04\u0E48\u0E33";
    });
    const currentDate = computed(() => {
      return (/* @__PURE__ */ new Date()).toLocaleDateString("th-TH", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    });
    const overallProgress = computed(() => {
      const total = learningStats.value.totalLessons + learningStats.value.totalAssignments + learningStats.value.totalQuizzes;
      const completed = learningStats.value.completedLessons + learningStats.value.completedAssignments + learningStats.value.completedQuizzes;
      if (total === 0) return 0;
      return Math.round(completed / total * 100);
    });
    const getProgressColor = (progress) => {
      if (progress >= 80) return "from-green-400 to-green-600";
      if (progress >= 60) return "from-blue-400 to-blue-600";
      if (progress >= 40) return "from-yellow-400 to-yellow-600";
      return "from-red-400 to-red-600";
    };
    const getProgressStatus = (progress) => {
      if (progress >= 80) return { text: "\u0E22\u0E2D\u0E14\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21", color: "text-green-600" };
      if (progress >= 60) return { text: "\u0E14\u0E35\u0E21\u0E32\u0E01", color: "text-blue-600" };
      if (progress >= 40) return { text: "\u0E1B\u0E32\u0E19\u0E01\u0E25\u0E32\u0E07", color: "text-yellow-600" };
      return { text: "\u0E15\u0E49\u0E2D\u0E07\u0E1B\u0E23\u0E31\u0E1A\u0E1B\u0E23\u0E38\u0E07", color: "text-red-600" };
    };
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString("th-TH", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const getActivityIcon = (type) => {
      const icons = {
        lesson: "fluent:book-open-24-regular",
        assignment: "fluent:document-text-24-regular",
        quiz: "fluent:quiz-new-24-regular",
        attendance: "fluent:calendar-clock-24-regular"
      };
      return icons[type] || "fluent:info-24-regular";
    };
    const getActivityColor = (type) => {
      const colors = {
        lesson: "text-blue-500 bg-blue-100 dark:bg-blue-900/30",
        assignment: "text-orange-500 bg-orange-100 dark:bg-orange-900/30",
        quiz: "text-purple-500 bg-purple-100 dark:bg-purple-900/30",
        attendance: "text-green-500 bg-green-100 dark:bg-green-900/30"
      };
      return colors[type] || "text-gray-500 bg-gray-100 dark:bg-gray-900/30";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "learning-dashboard min-h-screen bg-gray-50 dark:bg-gray-900" }, _attrs))} data-v-d5740407><div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white" data-v-d5740407><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" data-v-d5740407><div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4" data-v-d5740407><div data-v-d5740407><h1 class="text-3xl md:text-4xl font-bold mb-2" data-v-d5740407>${ssrInterpolate(greeting.value)}, ${ssrInterpolate(userName.value)}! \u{1F44B} </h1><p class="text-white/80 text-lg" data-v-d5740407>${ssrInterpolate(currentDate.value)}</p></div><div class="flex items-center gap-3" data-v-d5740407><div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2" data-v-d5740407><p class="text-sm text-white/80" data-v-d5740407>\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E40\u0E23\u0E35\u0E22\u0E19</p><p class="text-2xl font-bold" data-v-d5740407>${ssrInterpolate(learningStats.value.activeCourses)}</p></div><div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2" data-v-d5740407><p class="text-sm text-white/80" data-v-d5740407>\u0E40\u0E01\u0E23\u0E14\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22</p><p class="text-2xl font-bold" data-v-d5740407>${ssrInterpolate(learningStats.value.averageScore)}%</p></div></div></div></div></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" data-v-d5740407><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" data-v-d5740407><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow" data-v-d5740407><div class="flex items-center justify-between mb-4" data-v-d5740407><div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chart-multiple-24-filled",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><span class="text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08\u0E42\u0E14\u0E22\u0E23\u0E27\u0E21</span></div><p class="text-3xl font-bold text-gray-900 dark:text-white mb-1" data-v-d5740407>${ssrInterpolate(overallProgress.value)}%</p><div class="flex items-center gap-2" data-v-d5740407><div class="flex-grow h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden" data-v-d5740407><div class="${ssrRenderClass([`bg-gradient-to-r ${getProgressColor(overallProgress.value)}`, "h-full rounded-full transition-all duration-500"])}" style="${ssrRenderStyle({ width: `${overallProgress.value}%` })}" data-v-d5740407></div></div><span class="${ssrRenderClass([getProgressStatus(overallProgress.value).color, "text-sm font-medium"])}" data-v-d5740407>${ssrInterpolate(getProgressStatus(overallProgress.value).text)}</span></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow" data-v-d5740407><div class="flex items-center justify-between mb-4" data-v-d5740407><div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-open-24-filled",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><span class="text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></div><p class="text-3xl font-bold text-gray-900 dark:text-white mb-1" data-v-d5740407>${ssrInterpolate(learningStats.value.totalCourses)}</p><p class="text-sm text-gray-500 dark:text-gray-400" data-v-d5740407>${ssrInterpolate(learningStats.value.activeCourses)} \u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </p></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow" data-v-d5740407><div class="flex items-center justify-between mb-4" data-v-d5740407><div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:learning-app-24-filled",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><span class="text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></div><p class="text-3xl font-bold text-gray-900 dark:text-white mb-1" data-v-d5740407>${ssrInterpolate(learningStats.value.totalLessons)}</p><p class="text-sm text-gray-500 dark:text-gray-400" data-v-d5740407>${ssrInterpolate(learningStats.value.completedLessons)} \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E25\u0E49\u0E27 </p></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow" data-v-d5740407><div class="flex items-center justify-between mb-4" data-v-d5740407><div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clipboard-task-24-filled",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><span class="text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></div><p class="text-3xl font-bold text-gray-900 dark:text-white mb-1" data-v-d5740407>${ssrInterpolate(learningStats.value.totalAssignments)}</p><p class="text-sm text-gray-500 dark:text-gray-400" data-v-d5740407>${ssrInterpolate(learningStats.value.completedAssignments)} \u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27 </p></div></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-v-d5740407><div class="lg:col-span-2 space-y-8" data-v-d5740407><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-d5740407><h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:lightning-24-filled",
        class: "w-5 h-5 text-yellow-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E17\u0E33\u0E14\u0E48\u0E27\u0E19 </h2><div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4" data-v-d5740407>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Learn/Courses",
        class: "group bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-d5740407${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:book-open-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-d5740407${_scopeId}>\u0E04\u0E2D\u0E23\u0E4C\u0E2A</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-d5740407${_scopeId}>\u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:book-open-24-filled",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E04\u0E2D\u0E23\u0E4C\u0E2A"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Learn/Courses/UserCourses",
        class: "group bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-d5740407${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:learning-app-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-d5740407${_scopeId}>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-d5740407${_scopeId}>\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:learning-app-24-filled",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Learn/Courses/[id]/assignments",
        class: "group bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-d5740407${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:clipboard-task-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-d5740407${_scopeId}>\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-d5740407${_scopeId}>\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:clipboard-task-24-filled",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Learn/Courses/[id]/quizzes",
        class: "group bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-d5740407${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:quiz-new-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-d5740407${_scopeId}>\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-d5740407${_scopeId}>\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:quiz-new-24-filled",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student-card",
        class: "group bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-d5740407${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-card-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-d5740407${_scopeId}>\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-d5740407${_scopeId}>\u0E14\u0E39\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:person-card-24-filled",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E14\u0E39\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/home-visit",
        class: "group bg-gradient-to-br from-cyan-50 to-sky-50 dark:from-cyan-900/20 dark:to-sky-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-sky-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-d5740407${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:home-person-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-d5740407${_scopeId}>\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-d5740407${_scopeId}>\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-cyan-500 to-sky-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:home-person-24-filled",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-d5740407><div class="flex items-center justify-between mb-6" data-v-d5740407><h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-open-24-filled",
        class: "w-5 h-5 text-blue-500"
      }, null, _parent));
      _push(` \u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 </h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Learn/Courses/UserCourses",
        class: "text-primary-500 hover:text-primary-600 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (recentCourses.value.length > 0) {
        _push(`<div class="space-y-4" data-v-d5740407><!--[-->`);
        ssrRenderList(recentCourses.value, (course) => {
          _push(`<div class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-gray-100 dark:border-gray-700" data-v-d5740407><div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0" data-v-d5740407><div class="w-full h-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:book-open-24-filled",
            class: "w-8 h-8 text-white"
          }, null, _parent));
          _push(`</div></div><div class="flex-grow min-w-0" data-v-d5740407><h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1" data-v-d5740407>${ssrInterpolate(course.name)}</h3><p class="text-xs text-gray-500 dark:text-gray-400 mb-2" data-v-d5740407>${ssrInterpolate(course.category)}</p><div class="flex items-center gap-4" data-v-d5740407><div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:book-open-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span data-v-d5740407>${ssrInterpolate(course.lessons_count)} \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</span></div><div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span data-v-d5740407>${ssrInterpolate(course.hours)} \u0E0A\u0E21.</span></div></div><div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden" data-v-d5740407><div class="h-full bg-gradient-to-r from-blue-400 to-indigo-500 rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${course.progress}%` })}" data-v-d5740407></div></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-8 text-gray-500 dark:text-gray-400" data-v-d5740407>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:book-open-24-regular",
          class: "w-12 h-12 mx-auto mb-3"
        }, null, _parent));
        _push(`<p data-v-d5740407>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E2D\u0E23\u0E4C\u0E2A</p><p class="text-sm mt-1" data-v-d5740407>\u0E40\u0E23\u0E34\u0E48\u0E21\u0E25\u0E07\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E41\u0E23\u0E01</p></div>`);
      }
      _push(`</div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-d5740407><div class="flex items-center justify-between mb-6" data-v-d5740407><h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clipboard-task-24-filled",
        class: "w-5 h-5 text-orange-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E2A\u0E48\u0E07 </h2><span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-3 py-1 rounded-full" data-v-d5740407>${ssrInterpolate(upcomingAssignments.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </span></div>`);
      if (upcomingAssignments.value.length > 0) {
        _push(`<div class="space-y-3" data-v-d5740407><!--[-->`);
        ssrRenderList(upcomingAssignments.value, (assignment) => {
          _push(`<div class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-gray-100 dark:border-gray-700" data-v-d5740407><div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0" data-v-d5740407><div class="w-full h-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:document-text-24-filled",
            class: "w-6 h-6 text-white"
          }, null, _parent));
          _push(`</div></div><div class="flex-grow min-w-0" data-v-d5740407><h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1" data-v-d5740407>${ssrInterpolate(assignment.title)}</h3><p class="text-xs text-gray-500 dark:text-gray-400 mb-2" data-v-d5740407>${ssrInterpolate(assignment.course)}</p><div class="flex items-center gap-3" data-v-d5740407><div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-clock-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span data-v-d5740407>\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E2A\u0E48\u0E07: ${ssrInterpolate(formatDate(assignment.due_date))}</span></div><div class="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 font-medium" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:star-24-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span data-v-d5740407>${ssrInterpolate(assignment.points)} \u0E04\u0E30\u0E41\u0E19\u0E19</span></div></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-8 text-gray-500 dark:text-gray-400" data-v-d5740407>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-regular",
          class: "w-12 h-12 mx-auto mb-3"
        }, null, _parent));
        _push(`<p data-v-d5740407>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E2A\u0E48\u0E07</p><p class="text-sm mt-1" data-v-d5740407>\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21! \u0E04\u0E38\u0E13\u0E17\u0E33\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19\u0E04\u0E23\u0E1A\u0E41\u0E25\u0E49\u0E27</p></div>`);
      }
      _push(`</div></div><div class="space-y-8" data-v-d5740407><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-d5740407><h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chart-multiple-24-filled",
        class: "w-5 h-5 text-violet-500"
      }, null, _parent));
      _push(` \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 </h2><div class="space-y-4" data-v-d5740407><div class="flex items-center justify-between" data-v-d5740407><span class="text-sm text-gray-600 dark:text-gray-400" data-v-d5740407>\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</span><span class="text-sm font-bold text-gray-900 dark:text-white" data-v-d5740407>${ssrInterpolate(learningStats.value.attendanceRate)}%</span></div><div class="flex items-center justify-between" data-v-d5740407><span class="text-sm text-gray-600 dark:text-gray-400" data-v-d5740407>\u0E40\u0E01\u0E23\u0E14\u0E40\u0E09\u0E25\u0E35\u0E48\u0E22</span><span class="text-sm font-bold text-gray-900 dark:text-white" data-v-d5740407>${ssrInterpolate(learningStats.value.averageScore)}%</span></div><div class="flex items-center justify-between" data-v-d5740407><span class="text-sm text-gray-600 dark:text-gray-400" data-v-d5740407>\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E40\u0E23\u0E35\u0E22\u0E19</span><span class="text-sm font-bold text-gray-900 dark:text-white" data-v-d5740407>${ssrInterpolate(learningStats.value.activeCourses)}</span></div><div class="flex items-center justify-between" data-v-d5740407><span class="text-sm text-gray-600 dark:text-gray-400" data-v-d5740407>\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E41\u0E25\u0E49\u0E27</span><span class="text-sm font-bold text-gray-900 dark:text-white" data-v-d5740407>${ssrInterpolate(learningStats.value.completedCourses)}</span></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-d5740407><h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:history-24-filled",
        class: "w-5 h-5 text-blue-500"
      }, null, _parent));
      _push(` \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 </h2>`);
      if (recentActivities.value.length > 0) {
        _push(`<div class="space-y-3" data-v-d5740407><!--[-->`);
        ssrRenderList(recentActivities.value, (activity) => {
          _push(`<div class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" data-v-d5740407><div class="${ssrRenderClass([getActivityColor(activity.type), "w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"])}" data-v-d5740407>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getActivityIcon(activity.type),
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</div><div class="flex-grow min-w-0" data-v-d5740407><p class="text-sm font-medium text-gray-900 dark:text-white truncate" data-v-d5740407>${ssrInterpolate(activity.title)}</p><p class="text-xs text-gray-500 dark:text-gray-400" data-v-d5740407>${ssrInterpolate(activity.course)} \u2022 ${ssrInterpolate(formatDate(activity.date))}</p></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400" data-v-d5740407>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:history-24-regular",
          class: "w-8 h-8 mx-auto mb-2"
        }, null, _parent));
        _push(`<p class="text-sm" data-v-d5740407>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</p></div>`);
      }
      _push(`</div><div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white" data-v-d5740407><div class="flex items-center gap-2 mb-4" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:lightbulb-24-filled",
        class: "w-5 h-5 text-yellow-300"
      }, null, _parent));
      _push(`<h3 class="font-bold" data-v-d5740407>\u0E40\u0E04\u0E25\u0E47\u0E14\u0E25\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</h3></div><ul class="space-y-2 text-sm text-white/90" data-v-d5740407><li class="flex items-start gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-filled",
        class: "w-4 h-4 flex-shrink-0 mt-0.5"
      }, null, _parent));
      _push(`<span data-v-d5740407>\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2A\u0E21\u0E48\u0E27\u0E22\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E01\u0E29\u0E32\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</span></li><li class="flex items-start gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-filled",
        class: "w-4 h-4 flex-shrink-0 mt-0.5"
      }, null, _parent));
      _push(`<span data-v-d5740407>\u0E17\u0E33\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19\u0E41\u0E25\u0E30\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E15\u0E23\u0E07\u0E40\u0E27\u0E25\u0E32</span></li><li class="flex items-start gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-filled",
        class: "w-4 h-4 flex-shrink-0 mt-0.5"
      }, null, _parent));
      _push(`<span data-v-d5740407>\u0E17\u0E1A\u0E17\u0E17\u0E27\u0E19\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E22</span></li><li class="flex items-start gap-2" data-v-d5740407>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-filled",
        class: "w-4 h-4 flex-shrink-0 mt-0.5"
      }, null, _parent));
      _push(`<span data-v-d5740407>\u0E16\u0E32\u0E21\u0E1B\u0E31\u0E0D\u0E2B\u0E32\u0E2B\u0E32\u0E01\u0E44\u0E21\u0E48\u0E40\u0E02\u0E49\u0E32\u0E43\u0E08 \u0E16\u0E32\u0E21\u0E04\u0E23\u0E39</span></li></ul></div></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Dashboard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-d5740407"]]);

export { Dashboard as default };
//# sourceMappingURL=Dashboard-_HldpMCN.mjs.map
