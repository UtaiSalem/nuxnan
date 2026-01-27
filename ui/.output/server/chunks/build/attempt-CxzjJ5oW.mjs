import { defineComponent, ref, computed, mergeProps, unref, isRef, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderStyle, ssrRenderList, ssrRenderAttr, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { onBeforeRouteLeave } from 'vue-router';
import Swal from 'sweetalert2';
import { defineStore } from 'pinia';
import { p as useRoute, i as useApi, u as useRouter } from './server.mjs';
import { u as useAuth } from './useAuth-BmyK1-KK.mjs';
import { C as ContentLoader } from './ContentLoader-D4cV05oG.mjs';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
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

const useQuestionAnswersStore = defineStore("questionAnswers", {
  state: () => ({
    // Stores CONFIRMED answers: { quizId: { questionId: answerId } }
    answeredQuestions: {},
    // User's confirmed answers (persistent/saved)
    // Stores SUBMITTING status: { quizId: { questionId: boolean } }
    submittingQuestions: {},
    // Prevent double submission
    // Stores TEMPORARY answers (selected but not confirmed): { quizId: { questionId: answerId } }
    temporaryAnswers: {},
    // UI state before saving
    // Stores ERRORS: { quizId: { questionId: errorMsg } }
    questionErrors: {}
  }),
  actions: {
    // Check if a question is CONFIRMED
    isQuestionAnswered(quizId, questionId) {
      var _a;
      return ((_a = this.answeredQuestions[quizId]) == null ? void 0 : _a.hasOwnProperty(questionId)) || false;
    },
    // Save a CONFIRMED answer
    setAnsweredQuestion(quizId, questionId, answerId) {
      if (!this.answeredQuestions[quizId]) {
        this.answeredQuestions[quizId] = {};
      }
      this.answeredQuestions[quizId][questionId] = answerId;
      if (this.temporaryAnswers[quizId]) {
        delete this.temporaryAnswers[quizId][questionId];
      }
      if (this.questionErrors[quizId]) {
        delete this.questionErrors[quizId][questionId];
      }
    },
    // Check submission status
    isQuestionSubmitting(quizId, questionId) {
      var _a;
      return ((_a = this.submittingQuestions[quizId]) == null ? void 0 : _a.hasOwnProperty(questionId)) || false;
    },
    // Set submission status
    setQuestionSubmitting(quizId, questionId, isSubmitting) {
      if (!this.submittingQuestions[quizId]) {
        this.submittingQuestions[quizId] = {};
      }
      if (isSubmitting) {
        this.submittingQuestions[quizId][questionId] = true;
      } else {
        delete this.submittingQuestions[quizId][questionId];
      }
    },
    // Set TEMPORARY answer (Selection)
    setTemporaryAnswer(quizId, questionId, answerId) {
      if (!this.temporaryAnswers[quizId]) {
        this.temporaryAnswers[quizId] = {};
      }
      this.temporaryAnswers[quizId][questionId] = answerId;
    },
    // Get TEMPORARY answer
    getTemporaryAnswer(quizId, questionId) {
      var _a;
      return ((_a = this.temporaryAnswers[quizId]) == null ? void 0 : _a[questionId]) || null;
    },
    setQuestionError(quizId, questionId, error) {
      if (!this.questionErrors[quizId]) {
        this.questionErrors[quizId] = {};
      }
      this.questionErrors[quizId][questionId] = error;
    },
    clearQuestionError(quizId, questionId) {
      if (this.questionErrors[quizId]) {
        delete this.questionErrors[quizId][questionId];
      }
    },
    getQuestionError(quizId, questionId) {
      var _a;
      return ((_a = this.questionErrors[quizId]) == null ? void 0 : _a[questionId]) || null;
    },
    // Clear specific question state (e.g. for re-attempt or edit)
    clearAnsweredQuestion(quizId, questionId) {
      if (this.answeredQuestions[quizId]) {
        delete this.answeredQuestions[quizId][questionId];
      }
      if (this.submittingQuestions[quizId]) {
        delete this.submittingQuestions[quizId][questionId];
      }
      if (this.temporaryAnswers[quizId]) {
        delete this.temporaryAnswers[quizId][questionId];
      }
      if (this.questionErrors[quizId]) {
        delete this.questionErrors[quizId][questionId];
      }
    },
    clearQuizAnswers(quizId) {
      delete this.answeredQuestions[quizId];
      delete this.submittingQuestions[quizId];
      delete this.temporaryAnswers[quizId];
      delete this.questionErrors[quizId];
    },
    clearAllAnswers() {
      this.answeredQuestions = {};
      this.submittingQuestions = {};
      this.temporaryAnswers = {};
      this.questionErrors = {};
    },
    // For editing/updating an answer
    updateAnsweredQuestion(quizId, questionId, newAnswerId) {
      var _a;
      if ((_a = this.answeredQuestions[quizId]) == null ? void 0 : _a.hasOwnProperty(questionId)) {
        this.answeredQuestions[quizId][questionId] = newAnswerId;
      }
    }
  },
  getters: {
    getAnswerForQuestion: (state) => (quizId, questionId) => {
      var _a;
      return ((_a = state.answeredQuestions[quizId]) == null ? void 0 : _a[questionId]) || null;
    },
    answeredQuestionsCount: (state) => (quizId) => {
      const answeredCount = state.answeredQuestions[quizId] ? Object.keys(state.answeredQuestions[quizId]).length : 0;
      const uniqueTemporaryCount = state.temporaryAnswers[quizId] ? Object.keys(state.temporaryAnswers[quizId]).filter(
        (questionId) => {
          var _a;
          return !((_a = state.answeredQuestions[quizId]) == null ? void 0 : _a.hasOwnProperty(questionId));
        }
      ).length : 0;
      return answeredCount + uniqueTemporaryCount;
    },
    submittingQuestionsCount: (state) => (quizId) => {
      return state.submittingQuestions[quizId] ? Object.keys(state.submittingQuestions[quizId]).length : 0;
    }
  }
});
const _sfc_main$1 = {
  __name: "QuestionsListViewer",
  __ssrInlineRender: true,
  props: {
    courseId: { type: [Number, String], required: false },
    questions: { type: Array, required: true },
    quizId: { type: Number, required: true },
    quiz: { type: Object, required: true },
    quizResult: { type: Object, required: false }
  },
  setup(__props) {
    const props = __props;
    useApi();
    useAuth();
    const store = useQuestionAnswersStore();
    const editingQuestions = ref({});
    const isAnswered = (questionId) => store.isQuestionAnswered(props.quizId, questionId);
    const isEditing = (questionId) => !!editingQuestions.value[questionId];
    const isLocked = (questionId) => isAnswered(questionId) && !isEditing(questionId);
    ref({});
    ref({});
    const isSelected = (questionId, optionId) => {
      return store.getTemporaryAnswer(props.quizId, questionId) === optionId;
    };
    const getOptionClass = (questionId, optionId) => {
      return isSelected(questionId, optionId) ? "border-blue-500 bg-blue-50 dark:bg-blue-900/10 ring-1 ring-blue-500" : "border-gray-200 dark:border-gray-700";
    };
    const hasUnconfirmedChanges = (questionId) => {
      const temp = store.getTemporaryAnswer(props.quizId, questionId);
      const confirmed = store.getAnswerForQuestion(props.quizId, questionId);
      if (!store.isQuestionAnswered(props.quizId, questionId)) {
        return !!temp;
      }
      return temp && temp !== confirmed;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><!--[-->`);
      ssrRenderList(__props.questions, (q, index) => {
        var _a;
        _push(`<div class="p-6 bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-xl"><div class="flex gap-4"><span class="font-bold text-lg text-blue-600 dark:text-blue-400 min-w-[24px]">${ssrInterpolate(index + 1)}.</span><div class="flex-grow"><div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4"><div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100 flex-grow">${(_a = q.text) != null ? _a : ""}</div><div class="flex items-center gap-2 flex-shrink-0"><div class="text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-1 rounded-md border border-blue-100 dark:border-blue-800">${ssrInterpolate(q.points)} \u0E04\u0E30\u0E41\u0E19\u0E19 </div><div class="text-xs font-medium bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 px-2 py-1 rounded-md border border-orange-100 dark:border-orange-800 flex items-center gap-1"><span>${ssrInterpolate(q.pp_fine || 0)} \u0E41\u0E15\u0E49\u0E21</span></div></div></div>`);
        if (q.images && q.images.length > 0) {
          _push(`<div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4"><!--[-->`);
          ssrRenderList(q.images, (img) => {
            _push(`<div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"><img${ssrRenderAttr("src", img.full_url)} class="w-full h-auto object-cover" loading="lazy"></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(q.options, (opt) => {
          var _a2;
          _push(`<div class="${ssrRenderClass([[
            getOptionClass(q.id, opt.id),
            isLocked(q.id) ? "opacity-70 cursor-not-allowed bg-gray-50 dark:bg-gray-800" : "cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
          ], "relative flex items-start p-3 rounded-lg border transition-all group"])}"><div class="flex items-center h-5 mt-1"><input${ssrRenderAttr("name", `question_${q.id}`)} type="radio"${ssrIncludeBooleanAttr(isSelected(q.id, opt.id)) ? " checked" : ""}${ssrIncludeBooleanAttr(isLocked(q.id)) ? " disabled" : ""} class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600 disabled:text-gray-400"></div><div class="ml-3 text-sm flex-grow"><span class="font-medium text-gray-900 dark:text-gray-100">${(_a2 = opt.text) != null ? _a2 : ""}</span>`);
          if (opt.images && opt.images.length > 0) {
            _push(`<div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2"><!--[-->`);
            ssrRenderList(opt.images, (optImg) => {
              _push(`<div class="rounded overflow-hidden border border-gray-200 dark:border-gray-700"><img${ssrRenderAttr("src", optImg.full_url)} class="w-full h-24 object-cover" loading="lazy"></div>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
        });
        _push(`<!--]--><div class="mt-4 flex justify-end gap-2">`);
        if (isAnswered(q.id) && !isEditing(q.id)) {
          _push(`<button class="flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), { icon: "heroicons:pencil-square" }, null, _parent));
          _push(` \u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E15\u0E2D\u0E1A </button>`);
        } else if (hasUnconfirmedChanges(q.id) || isEditing(q.id)) {
          _push(`<button${ssrIncludeBooleanAttr(unref(store).isQuestionSubmitting(__props.quizId, q.id)) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">`);
          if (unref(store).isQuestionSubmitting(__props.quizId, q.id)) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "eos-icons:loading",
              class: "animate-spin"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), { icon: "heroicons:check-circle" }, null, _parent));
          }
          _push(` ${ssrInterpolate(unref(store).isQuestionSubmitting(__props.quizId, q.id) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E04\u0E33\u0E15\u0E2D\u0E1A")}</button>`);
        } else {
          _push(`<!---->`);
        }
        if (isEditing(q.id)) {
          _push(`<button class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg text-sm font-medium transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div></div>`);
      });
      _push(`<!--]--></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/questions/QuestionsListViewer.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "attempt",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const courseId = route.params.id;
    const quizId = route.params.quizId;
    useApi();
    useRouter();
    const quiz = ref(null);
    const quizResult = ref(null);
    const isLoading = ref(true);
    const isSubmitting = ref(false);
    ref([]);
    const timeElapsed = ref(0);
    ref(null);
    ref(null);
    const formattedTime = computed(() => {
      const minutes = Math.floor(timeElapsed.value / 60);
      const seconds = timeElapsed.value % 60;
      return `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
    });
    const showBackToTop = ref(false);
    onBeforeRouteLeave((to, from, next) => {
      if (isSubmitting.value) {
        next();
        return;
      }
      Swal.fire({
        title: "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E2B\u0E19\u0E49\u0E32\u0E2A\u0E2D\u0E1A?",
        text: "\u0E01\u0E32\u0E23\u0E17\u0E33\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A\u0E08\u0E30\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E2A\u0E48\u0E07 \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E2D\u0E2D\u0E01\u0E08\u0E23\u0E34\u0E07\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "\u0E43\u0E0A\u0E48, \u0E2D\u0E2D\u0E01\u0E40\u0E25\u0E22",
        cancelButtonText: "\u0E17\u0E33\u0E15\u0E48\u0E2D"
      }).then((result) => {
        if (result.isConfirmed) {
          next();
        } else {
          next(false);
        }
      });
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-4xl" }, _attrs))}>`);
      if (unref(isLoading)) {
        _push(ssrRenderComponent(ContentLoader, null, null, _parent));
      } else if (unref(quiz)) {
        _push(`<div class="relative"><div class="sticky top-4 z-20 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-100 dark:border-gray-700 p-4 mb-6 flex items-center justify-between transition-all duration-300"><div class="flex items-center gap-4"><h1 class="text-lg font-bold text-gray-800 dark:text-white truncate max-w-[200px] sm:max-w-md hidden sm:block">${ssrInterpolate(unref(quiz).title)}</h1><span class="sm:hidden font-bold text-gray-800 dark:text-white">\u0E40\u0E27\u0E25\u0E32\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49</span></div><div class="flex items-center gap-4"><div class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg font-mono font-bold text-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:timer-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(formattedTime))}</div><button${ssrIncludeBooleanAttr(unref(isSubmitting)) ? " disabled" : ""} class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium shadow-md transition-colors flex items-center gap-2"><span class="hidden sm:inline">\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E01\u0E32\u0E23\u0E2A\u0E2D\u0E1A (\u0E2B\u0E22\u0E38\u0E14\u0E40\u0E27\u0E25\u0E32)</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:stop-24-filled",
          class: "w-5 h-5 sm:hidden"
        }, null, _parent));
        _push(`</button></div></div>`);
        if (unref(quiz).questions && unref(quiz).questions.length > 0) {
          _push(`<div>`);
          _push(ssrRenderComponent(_sfc_main$1, {
            modelValue: unref(quizResult),
            "onUpdate:modelValue": ($event) => isRef(quizResult) ? quizResult.value = $event : null,
            questions: unref(quiz).questions,
            quizId: parseInt(unref(quizId)),
            courseId: parseInt(unref(courseId)),
            quiz: unref(quiz),
            quizResult: unref(quizResult),
            questionApiRoute: `/api/quizs/${unref(quizId)}`
          }, null, _parent));
          _push(`<div class="mt-8 flex justify-center pb-10"><button class="px-8 py-3 bg-red-600 text-white rounded-full font-bold shadow-lg hover:bg-red-700 transition flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:stop-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E01\u0E32\u0E23\u0E17\u0E33\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A (\u0E2B\u0E22\u0E38\u0E14\u0E40\u0E27\u0E25\u0E32) </button></div></div>`);
        } else {
          _push(`<div class="flex flex-col items-center justify-center p-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:document-error-24-regular",
            class: "w-16 h-16 text-gray-300 mb-4"
          }, null, _parent));
          _push(`<h3 class="text-xl font-medium text-gray-600 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E19\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E19\u0E35\u0E49</h3><p class="text-gray-400 mt-2">\u0E42\u0E1B\u0E23\u0E14\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19</p></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="fixed bottom-6 right-6 p-3 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all z-50" title="Back to Top" style="${ssrRenderStyle(unref(showBackToTop) ? null : { display: "none" })}">`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/quizzes/[quizId]/attempt.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=attempt-CxzjJ5oW.mjs.map
