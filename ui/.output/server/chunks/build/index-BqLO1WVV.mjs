import { p as useRoute, i as useApi, q as __nuxt_component_0, n as navigateTo } from './server.mjs';
import { defineComponent, inject, ref, computed, unref, mergeProps, withCtx, createVNode, reactive, watch, resolveComponent, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderTeleport, ssrIncludeBooleanAttr, ssrRenderAttr, ssrLooseContain } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$3, a as _sfc_main$1$1 } from './QuizDuplicateModal-BCiaovrs.mjs';
import { C as ContentLoader } from './ContentLoader-D4cV05oG.mjs';
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

const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "QuizPost",
  __ssrInlineRender: true,
  props: {
    quiz: {},
    isAdmin: { type: Boolean, default: false },
    courseId: {},
    quizIndex: { default: 1 }
  },
  emits: ["edit", "delete", "start", "view", "duplicate"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const status = computed(() => {
      const isActive = props.quiz.is_active || props.quiz.status === 1;
      return isActive ? {
        text: "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48\u0E41\u0E25\u0E49\u0E27",
        color: "bg-emerald-500/90 text-white shadow-emerald-500/30",
        icon: "fluent:checkmark-circle-24-filled"
      } : {
        text: "\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07",
        color: "bg-gray-500/90 text-white",
        icon: "fluent:draft-24-regular"
      };
    });
    const questionsCount = computed(() => {
      var _a;
      return props.quiz.questions_count || ((_a = props.quiz.questions) == null ? void 0 : _a.length) || 0;
    });
    const totalScore = computed(() => props.quiz.total_score || 0);
    const timeLimit = computed(() => props.quiz.time_limit || 0);
    const passingScore = computed(() => props.quiz.passing_score || 50);
    const isAvailable = computed(() => {
      const now = /* @__PURE__ */ new Date();
      if (props.quiz.start_date && new Date(props.quiz.start_date) > now) return false;
      if (props.quiz.end_date && new Date(props.quiz.end_date) < now) return false;
      return true;
    });
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleDateString("th-TH", {
        day: "numeric",
        month: "short",
        year: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const hasAttempted = computed(() => {
      return props.quiz.current_result && props.quiz.current_result.completed_at;
    });
    const attemptScore = computed(() => {
      var _a;
      if (!hasAttempted.value) return null;
      return ((_a = props.quiz.current_result) == null ? void 0 : _a.score) || 0;
    });
    const attemptPercentage = computed(() => {
      var _a;
      if (!hasAttempted.value) return null;
      return parseFloat(((_a = props.quiz.current_result) == null ? void 0 : _a.percentage) || 0).toFixed(1);
    });
    const isPassed = computed(() => {
      var _a, _b, _c;
      return ((_a = props.quiz.current_result) == null ? void 0 : _a.status) === "Pass" || ((_b = props.quiz.current_result) == null ? void 0 : _b.status) === "passed" || ((_c = props.quiz.current_result) == null ? void 0 : _c.status) === 3;
    });
    const isBarelyPassed = computed(() => {
      var _a;
      if (!isPassed.value) return false;
      const percentage = parseFloat(((_a = props.quiz.current_result) == null ? void 0 : _a.percentage) || 0);
      const passing = props.quiz.passing_score || 50;
      return percentage < passing + 10;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<article${ssrRenderAttrs(mergeProps({ class: "group relative bg-white dark:bg-gray-800 rounded-3xl shadow-xl hover:shadow-2xl hover:shadow-indigo-500/20 transition-all duration-300 overflow-hidden mb-8 border border-gray-100 dark:border-gray-700 isolate" }, _attrs))}><div class="absolute -top-24 -right-24 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all duration-500"></div><div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-500"></div><div class="relative h-48 overflow-hidden"><div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-600 transition-transform duration-700 group-hover:scale-105"></div><div class="absolute inset-0 opacity-10 bg-[url(&#39;https://www.transparenttextures.com/patterns/cubes.png&#39;)]"></div><div class="absolute inset-0 flex items-center justify-center"><div class="relative z-10 p-6 text-center transform transition-transform duration-500 group-hover:-translate-y-2"><div class="w-16 h-16 mx-auto bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mb-3 shadow-inner ring-1 ring-white/30">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "healthicons:i-exam-qualification-outline",
        class: "w-9 h-9 text-white drop-shadow-md"
      }, null, _parent));
      _push(`</div><h3 class="text-white font-bold text-lg opacity-90 tracking-wide uppercase">\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</h3></div></div><div class="absolute top-4 left-4 z-20 flex flex-wrap gap-2"><span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-black/30 backdrop-blur-md text-white ring-1 ring-white/20 shadow-sm">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:number-symbol-24-filled",
        class: "w-3.5 h-3.5"
      }, null, _parent));
      _push(` \u0E0A\u0E38\u0E14\u0E17\u0E35\u0E48 ${ssrInterpolate(__props.quizIndex)}</span>`);
      if (timeLimit.value) {
        _push(`<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-500/20 backdrop-blur-md text-blue-50 ring-1 ring-blue-400/30 shadow-sm">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:timer-24-filled",
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(` ${ssrInterpolate(timeLimit.value)} \u0E19\u0E32\u0E17\u0E35 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="absolute top-4 right-4 z-20"><span class="${ssrRenderClass([status.value.color, "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg backdrop-blur-md ring-1 ring-white/20"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: status.value.icon,
        class: "w-3.5 h-3.5"
      }, null, _parent));
      _push(` ${ssrInterpolate(status.value.text)}</span></div>`);
      if (__props.isAdmin) {
        _push(`<div class="absolute bottom-4 right-4 z-20 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 translate-y-2 group-hover:translate-y-0"><button class="p-2.5 bg-white text-gray-700 rounded-xl hover:bg-purple-50 hover:text-purple-600 transition-colors shadow-lg" title="\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:copy-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button><button class="p-2.5 bg-white text-gray-700 rounded-xl hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-lg" title="\u0E41\u0E01\u0E49\u0E44\u0E02">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:edit-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button><button class="p-2.5 bg-white text-gray-700 rounded-xl hover:bg-red-50 hover:text-red-600 transition-colors shadow-lg" title="\u0E25\u0E1A">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:delete-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-6 md:p-8 relative z-10"><div class="flex justify-between items-start mb-6"><div class="flex-1 pr-4"><h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 dark:text-white leading-tight mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">${ssrInterpolate(__props.quiz.title || __props.quiz.name)}</h2>`);
      if (__props.quiz.description) {
        _push(`<p class="text-gray-500 dark:text-gray-400 text-sm line-clamp-2 leading-relaxed">${ssrInterpolate(__props.quiz.description)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex flex-col items-center justify-center w-16 h-16 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-700/30 text-amber-600 dark:text-amber-500 shadow-sm flex-shrink-0"><span class="text-xl font-black">${ssrInterpolate(totalScore.value)}</span><span class="text-[10px] font-bold uppercase tracking-wide">\u0E04\u0E30\u0E41\u0E19\u0E19</span></div></div><div class="grid grid-cols-2 gap-4 mb-6"><div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700"><div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center text-indigo-500 shadow-sm">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:document-text-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</div><div><p class="text-xs text-gray-400 font-medium uppercase">\u0E08\u0E33\u0E19\u0E27\u0E19\u0E02\u0E49\u0E2D</p><p class="font-bold text-gray-700 dark:text-gray-200">${ssrInterpolate(questionsCount.value)} \u0E02\u0E49\u0E2D</p></div></div><div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700"><div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center text-emerald-500 shadow-sm">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:target-arrow-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</div><div><p class="text-xs text-gray-400 font-medium uppercase">\u0E40\u0E01\u0E13\u0E11\u0E4C\u0E1C\u0E48\u0E32\u0E19</p><p class="font-bold text-gray-700 dark:text-gray-200">${ssrInterpolate(passingScore.value)}%</p></div></div></div>`);
      if (__props.quiz.start_date || __props.quiz.end_date) {
        _push(`<div class="flex flex-wrap gap-y-2 gap-x-6 text-sm text-gray-500 dark:text-gray-400 mb-6 px-1">`);
        if (__props.quiz.start_date) {
          _push(`<div class="flex items-center gap-1.5">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-ltr-24-regular",
            class: "w-4 h-4 text-gray-400"
          }, null, _parent));
          _push(`<span>\u0E40\u0E23\u0E34\u0E48\u0E21: ${ssrInterpolate(formatDate(__props.quiz.start_date))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.quiz.end_date) {
          _push(`<div class="flex items-center gap-1.5">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-end-24-regular",
            class: "w-4 h-4 text-gray-400"
          }, null, _parent));
          _push(`<span>\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14: ${ssrInterpolate(formatDate(__props.quiz.end_date))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="h-px w-full bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent mb-6"></div>`);
      if (hasAttempted.value && !__props.isAdmin) {
        _push(`<div class="mb-6"><div class="${ssrRenderClass([isPassed.value ? isBarelyPassed.value ? "bg-gradient-to-r from-orange-50 to-amber-50 border-orange-200 dark:from-orange-900/20 dark:to-amber-900/20 dark:border-orange-800" : "bg-gradient-to-r from-emerald-50 to-teal-50 border-emerald-200 dark:from-emerald-900/20 dark:to-teal-900/20 dark:border-emerald-800" : "bg-gradient-to-r from-red-50 to-orange-50 border-red-200 dark:from-red-900/20 dark:to-orange-900/20 dark:border-red-800", "relative overflow-hidden rounded-2xl border transition-all duration-300"])}"><div class="p-4 flex items-center justify-between relative z-10"><div class="flex items-center gap-4"><div class="relative">`);
        _push(ssrRenderComponent(_sfc_main$1$1, {
          percentage: parseFloat(attemptPercentage.value),
          color: isPassed.value ? isBarelyPassed.value ? "text-orange-500" : "text-emerald-500" : "text-red-500",
          size: 52,
          strokeWidth: 4,
          class: "bg-white rounded-full shadow-sm"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: isPassed.value ? "fluent:trophy-24-filled" : "fluent:dismiss-circle-24-filled",
                class: ["w-5 h-5", isPassed.value ? isBarelyPassed.value ? "text-orange-500" : "text-emerald-500" : "text-red-500"]
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: isPassed.value ? "fluent:trophy-24-filled" : "fluent:dismiss-circle-24-filled",
                  class: ["w-5 h-5", isPassed.value ? isBarelyPassed.value ? "text-orange-500" : "text-emerald-500" : "text-red-500"]
                }, null, 8, ["icon", "class"])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><div><p class="${ssrRenderClass([isPassed.value ? isBarelyPassed.value ? "text-orange-800 dark:text-orange-300" : "text-emerald-800 dark:text-emerald-300" : "text-red-800 dark:text-red-300", "text-xs font-bold uppercase opacity-70"])}">\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E17\u0E14\u0E2A\u0E2D\u0E1A</p><p class="${ssrRenderClass([isPassed.value ? isBarelyPassed.value ? "text-orange-700 dark:text-orange-400" : "text-emerald-700 dark:text-emerald-400" : "text-red-700 dark:text-red-400", "text-lg font-black tracking-tight"])}">${ssrInterpolate(isPassed.value ? isBarelyPassed.value ? "\u0E1C\u0E48\u0E32\u0E19\u0E41\u0E1A\u0E1A\u0E40\u0E09\u0E35\u0E22\u0E14\u0E09\u0E34\u0E27" : "\u0E1C\u0E48\u0E32\u0E19\u0E01\u0E32\u0E23\u0E17\u0E14\u0E2A\u0E2D\u0E1A" : "\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19\u0E40\u0E01\u0E13\u0E11\u0E4C")}</p></div></div><div class="text-right"><div class="${ssrRenderClass([isPassed.value ? "text-emerald-600" : "text-red-600", "text-2xl font-black tabular-nums"])}">${ssrInterpolate(attemptScore.value)}<span class="text-sm font-medium text-gray-500">/${ssrInterpolate(totalScore.value)}</span></div></div></div><div class="${ssrRenderClass([isPassed.value ? "bg-emerald-400" : "bg-red-400", "absolute -right-6 -bottom-6 w-24 h-24 rounded-full opacity-50 blur-2xl"])}"></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex items-center gap-3"><button class="flex-1 py-3 px-4 rounded-xl font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center justify-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:eye-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </button>`);
      if (!__props.isAdmin || __props.isAdmin && __props.quiz.questions_count > 0) {
        _push(`<button class="${ssrRenderClass([__props.isAdmin ? "bg-gray-900 dark:bg-gray-700" : hasAttempted.value ? "bg-gradient-to-r from-blue-600 to-cyan-600" : "bg-gradient-to-r from-indigo-600 to-purple-600", "flex-[2] py-3 px-6 rounded-xl font-bold text-white shadow-lg hover:shadow-indigo-500/30 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 relative overflow-hidden group/btn"])}"><div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-300"></div>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: hasAttempted.value ? "fluent:arrow-rotate-clockwise-24-filled" : "fluent:play-circle-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(__props.isAdmin ? "\u0E17\u0E14\u0E2A\u0E2D\u0E1A (Admin)" : hasAttempted.value ? "\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07" : "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A")}</span></button>`);
      } else {
        _push(`<!---->`);
      }
      if (!__props.isAdmin && !isAvailable.value) {
        _push(`<div class="flex-[2] py-3 px-6 rounded-xl font-bold bg-gray-100 text-gray-400 flex items-center justify-center gap-2 cursor-not-allowed">`);
        _push(ssrRenderComponent(unref(Icon), { icon: "fluent:lock-closed-24-filled" }, null, _parent));
        _push(`<span>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E1B\u0E34\u0E14</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></article>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/quiz/QuizPost.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "QuizCreateModal",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    show: { type: Boolean }
  },
  emits: ["close", "created"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const api = useApi();
    const isLoading = ref(false);
    const errors = ref([]);
    const form = reactive({
      title: "",
      description: "",
      start_date: /* @__PURE__ */ new Date(),
      end_date: new Date(Date.now() + 60 * 60 * 1e3),
      time_limit: 60,
      passing_score: 50,
      is_active: true,
      shuffle_questions: false
    });
    const quizSuggestions = ref([]);
    const isSearchingQuizzes = ref(false);
    const isTitleFocused = ref(false);
    const isDuplicating = ref(false);
    const showSuggestions = computed(() => isTitleFocused.value && quizSuggestions.value.length > 0);
    let searchTimeout = null;
    watch(() => form.title, (newTitle) => {
      if (searchTimeout) clearTimeout(searchTimeout);
      if (newTitle && newTitle.length >= 2) {
        searchTimeout = setTimeout(searchQuizzes, 300);
      } else {
        quizSuggestions.value = [];
      }
    });
    const searchQuizzes = async () => {
      if (!form.title || form.title.length < 2) return;
      isSearchingQuizzes.value = true;
      try {
        const res = await api.get(`/api/quizzes/search?q=${encodeURIComponent(form.title)}`);
        if (Array.isArray(res)) {
          quizSuggestions.value = res;
        } else if (res.quizzes) {
          quizSuggestions.value = res.quizzes;
        } else if (res.data) {
          quizSuggestions.value = Array.isArray(res.data) ? res.data : [];
        } else {
          quizSuggestions.value = [];
        }
      } catch (err) {
        console.error("Error searching quizzes:", err);
        quizSuggestions.value = [];
      } finally {
        isSearchingQuizzes.value = false;
      }
    };
    const isFormValid = computed(() => {
      return form.title.trim() !== "" && form.time_limit > 0 && form.passing_score >= 0 && form.passing_score <= 100 && (!form.start_date || !form.end_date || new Date(form.end_date) > new Date(form.start_date));
    });
    watch(() => props.show, (newVal) => {
      if (!newVal) {
        form.title = "";
        form.description = "";
        form.time_limit = 60;
        form.passing_score = 50;
        form.is_active = true;
        form.shuffle_questions = false;
        form.start_date = /* @__PURE__ */ new Date();
        form.end_date = new Date(Date.now() + 60 * 60 * 1e3);
        errors.value = [];
        quizSuggestions.value = [];
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_VueDatePicker = resolveComponent("VueDatePicker");
      ssrRenderTeleport(_push, (_push2) => {
        if (__props.show) {
          _push2(`<div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-8 bg-black/50 backdrop-blur-sm">`);
          if (__props.show) {
            _push2(`<div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl mx-4"><div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:quiz-new-24-filled",
              class: "w-5 h-5 text-white"
            }, null, _parent));
            _push2(`</div><div><h2 class="text-lg font-bold text-gray-900 dark:text-white">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E2B\u0E21\u0E48</h2><p class="text-sm text-gray-500">\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</p></div></div><button${ssrIncludeBooleanAttr(unref(isLoading) || unref(isDuplicating)) ? " disabled" : ""} class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(`</button></div><div class="p-5 space-y-5 max-h-[calc(100vh-240px)] overflow-y-auto"><div class="relative"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A <span class="text-red-500">*</span></label><div class="relative"><input${ssrRenderAttr("value", unref(form).title)} type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E1A\u0E17\u0E17\u0E35\u0E48 1">`);
            if (unref(isSearchingQuizzes)) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500"
              }, null, _parent));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            if (unref(showSuggestions)) {
              _push2(`<div class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden"><div class="px-4 py-2 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-gray-100 dark:border-gray-700"><p class="text-xs font-semibold text-purple-700 dark:text-purple-300 flex items-center gap-1.5">`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:copy-24-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push2(` \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E40\u0E04\u0E22\u0E2A\u0E23\u0E49\u0E32\u0E07 </p></div><div class="max-h-48 overflow-y-auto"><!--[-->`);
              ssrRenderList(unref(quizSuggestions), (quiz, index) => {
                _push2(`<div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-b-0"><button type="button" class="flex items-center gap-3 flex-1 text-left"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center flex-shrink-0">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:quiz-new-24-filled",
                  class: "w-4 h-4 text-white"
                }, null, _parent));
                _push2(`</div><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate text-sm">${ssrInterpolate(quiz.title)}</p><div class="flex items-center gap-2 text-xs text-gray-500"><span>${ssrInterpolate(quiz.questions_count || 0)} \u0E02\u0E49\u0E2D</span>`);
                if (quiz.course) {
                  _push2(`<span class="text-purple-600 dark:text-purple-400 truncate">${ssrInterpolate(quiz.course.name)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div></div></button><div class="flex items-center gap-1"><button type="button" class="p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg" title="\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:copy-24-regular",
                  class: "w-4 h-4"
                }, null, _parent));
                _push2(`</button><button type="button" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:dismiss-16-regular",
                  class: "w-4 h-4"
                }, null, _parent));
                _push2(`</button></div></div>`);
              });
              _push2(`<!--]--></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 </label><textarea rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E2B\u0E23\u0E37\u0E2D\u0E04\u0E33\u0E0A\u0E35\u0E49\u0E41\u0E08\u0E07...">${ssrInterpolate(unref(form).description)}</textarea></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E40\u0E27\u0E25\u0E32 (\u0E19\u0E32\u0E17\u0E35) <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", unref(form).time_limit)} type="number" min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E40\u0E01\u0E13\u0E11\u0E4C\u0E1C\u0E48\u0E32\u0E19 (%) <span class="text-red-500">*</span></label><div class="relative"><input${ssrRenderAttr("value", unref(form).passing_score)} type="number" min="0" max="100" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-8"><span class="absolute right-3 top-2.5 text-gray-500">%</span></div></div></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33\u0E44\u0E14\u0E49\u0E15\u0E31\u0E49\u0E07\u0E41\u0E15\u0E48 </label>`);
            _push2(ssrRenderComponent(_component_VueDatePicker, {
              modelValue: unref(form).start_date,
              "onUpdate:modelValue": ($event) => unref(form).start_date = $event,
              format: "dd/MM/yyyy HH:mm",
              "auto-apply": "",
              teleport: true
            }, null, _parent));
            _push2(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E40\u0E21\u0E37\u0E48\u0E2D </label>`);
            _push2(ssrRenderComponent(_component_VueDatePicker, {
              modelValue: unref(form).end_date,
              "onUpdate:modelValue": ($event) => unref(form).end_date = $event,
              format: "dd/MM/yyyy HH:mm",
              "auto-apply": "",
              teleport: true
            }, null, _parent));
            _push2(`</div></div><div class="flex flex-wrap gap-4"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(unref(form).shuffle_questions) ? ssrLooseContain(unref(form).shuffle_questions, null) : unref(form).shuffle_questions) ? " checked" : ""} class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500"><span class="text-sm text-gray-700 dark:text-gray-300">\u0E2A\u0E25\u0E31\u0E1A\u0E02\u0E49\u0E2D\u0E04\u0E33\u0E16\u0E32\u0E21</span></label><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(unref(form).is_active) ? ssrLooseContain(unref(form).is_active, null) : unref(form).is_active) ? " checked" : ""} class="w-4 h-4 text-green-600 rounded focus:ring-green-500"><span class="text-sm text-gray-700 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</span></label></div>`);
            if (unref(errors).length) {
              _push2(`<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3"><ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400"><!--[-->`);
              ssrRenderList(unref(errors), (error, index) => {
                _push2(`<li>${ssrInterpolate(error)}</li>`);
              });
              _push2(`<!--]--></ul></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 rounded-b-2xl"><button class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"${ssrIncludeBooleanAttr(unref(isLoading) || unref(isDuplicating)) ? " disabled" : ""}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-6 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"${ssrIncludeBooleanAttr(!unref(isFormValid) || unref(isLoading) || unref(isDuplicating)) ? " disabled" : ""}>`);
            if (unref(isLoading)) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push2(`<!---->`);
            }
            _push2(`<span>${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A")}</span></button></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (unref(isDuplicating)) {
            _push2(`<div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 flex flex-col items-center gap-4 shadow-2xl">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-14 h-14 text-purple-600"
            }, null, _parent));
            _push2(`<div class="text-center"><p class="text-lg font-bold text-gray-900 dark:text-white">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A...</p><p class="text-sm text-gray-500 mt-1">\u0E23\u0E27\u0E21\u0E16\u0E36\u0E07\u0E04\u0E33\u0E16\u0E32\u0E21\u0E41\u0E25\u0E30\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/quiz/QuizCreateModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const route = useRoute();
    const api = useApi();
    const quizzes = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    const showScrollButton = ref(false);
    const showDuplicateModal = ref(false);
    const quizToDuplicate = ref(null);
    const showCreateModal = ref(false);
    const isRoot = computed(() => {
      return /\/quizzes\/?$/.test(route.path);
    });
    const fetchQuizzes = async () => {
      var _a;
      if (!((_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id)) return;
      isLoading.value = true;
      error.value = null;
      try {
        const response = await api.get(`/api/courses/${course.value.id}/quizzes`);
        quizzes.value = response.quizzes || response.data || response || [];
      } catch (err) {
        console.error("Error fetching quizzes:", err);
        error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E44\u0E14\u0E49";
      } finally {
        isLoading.value = false;
      }
    };
    const handleQuizCreated = (newQuiz) => {
      if (newQuiz) {
        quizzes.value.unshift(newQuiz);
      }
    };
    const handleEdit = (quiz) => {
      var _a;
      navigateTo(`/courses/${(_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id}/quizzes/${quiz.id}/edit`);
    };
    const handleDelete = async (quizId) => {
      var _a;
      if (!confirm("\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?")) return;
      try {
        await api.delete(`/api/courses/${(_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id}/quizzes/${quizId}`);
        await fetchQuizzes();
      } catch (err) {
        console.error("Error deleting quiz:", err);
        alert("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E44\u0E14\u0E49");
      }
    };
    const handleStart = (quiz) => {
      var _a;
      navigateTo(`/courses/${(_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id}/quizzes/${quiz.id}/attempt`);
    };
    const handleView = (quiz) => {
      var _a;
      navigateTo(`/courses/${(_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id}/quizzes/${quiz.id}`);
    };
    const handleDuplicate = (quiz) => {
      quizToDuplicate.value = quiz;
      showDuplicateModal.value = true;
    };
    const handleDuplicated = async (newQuiz) => {
      var _a;
      if (newQuiz && newQuiz.course_id === ((_a = course == null ? void 0 : course.value) == null ? void 0 : _a.id)) {
        await fetchQuizzes();
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      const _component_NuxtPage = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_component_NuxtPage, null, null, _parent));
      if (unref(isRoot)) {
        _push(`<!--[-->`);
        if (unref(isLoading)) {
          _push(ssrRenderComponent(ContentLoader, null, null, _parent));
        } else if (unref(error)) {
          _push(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center max-w-md mx-auto">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:error-circle-24-regular",
            class: "w-16 h-16 text-red-500 mx-auto mb-4"
          }, null, _parent));
          _push(`<h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h3><p class="text-red-600 dark:text-red-400 mb-4">${ssrInterpolate(unref(error))}</p><button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
        } else {
          _push(`<!--[-->`);
          if (unref(isCourseAdmin)) {
            _push(`<div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-800 dark:via-indigo-800 dark:to-blue-800 rounded-2xl p-6 shadow-xl mb-6"><div class="flex items-center justify-between"><div class="flex items-center gap-4"><div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "healthicons:i-exam-qualification-outline",
              class: "w-7 h-7 text-white"
            }, null, _parent));
            _push(`</div><div><h2 class="text-2xl font-bold text-white mb-1">\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</h2><p class="text-white/80 text-sm">${ssrInterpolate(unref(quizzes).length)} \u0E0A\u0E38\u0E14</p></div></div><button class="flex items-center gap-2 px-5 py-3 bg-white text-purple-600 rounded-xl hover:bg-purple-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-circle-24-filled",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</span></button></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (!unref(quizzes).length) {
            _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "healthicons:i-exam-qualification-outline",
              class: "w-24 h-24 text-gray-300 dark:text-gray-600 mx-auto mb-4"
            }, null, _parent));
            _push(`<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </h3><p class="text-gray-600 dark:text-gray-400">${ssrInterpolate(unref(isCourseAdmin) ? "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E41\u0E23\u0E01\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13" : "\u0E2D\u0E32\u0E08\u0E32\u0E23\u0E22\u0E4C\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E15\u0E23\u0E35\u0E22\u0E21\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E2D\u0E22\u0E39\u0E48")}</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(unref(quizzes), (quiz, index) => {
            var _a2;
            _push(`<div>`);
            _push(ssrRenderComponent(_sfc_main$2, {
              quiz,
              "is-admin": unref(isCourseAdmin),
              "course-id": (_a2 = unref(course)) == null ? void 0 : _a2.id,
              "quiz-index": index + 1,
              onEdit: handleEdit,
              onDelete: handleDelete,
              onStart: handleStart,
              onView: handleView,
              onDuplicate: handleDuplicate
            }, null, _parent));
            _push(`</div>`);
          });
          _push(`<!--]--><!--]-->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!---->`);
      }
      if (unref(showScrollButton)) {
        _push(`<button class="fixed bottom-8 right-8 z-[999] p-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all" title="\u0E40\u0E25\u0E37\u0E48\u0E2D\u0E19\u0E02\u0E36\u0E49\u0E19\u0E14\u0E49\u0E32\u0E19\u0E1A\u0E19">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-up-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</button>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_sfc_main$3, {
        show: unref(showDuplicateModal),
        quiz: unref(quizToDuplicate),
        "current-course-id": (_a = unref(course)) == null ? void 0 : _a.id,
        onClose: ($event) => showDuplicateModal.value = false,
        onDuplicated: handleDuplicated
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, {
        show: unref(showCreateModal),
        "course-id": (_b = unref(course)) == null ? void 0 : _b.id,
        onClose: ($event) => showCreateModal.value = false,
        onCreated: handleQuizCreated
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/quizzes/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-BqLO1WVV.mjs.map
