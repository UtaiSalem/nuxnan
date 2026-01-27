import { defineComponent, ref, reactive, computed, watch, resolveComponent, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { p as useRoute, i as useApi, u as useRouter } from './server.mjs';
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
  __name: "create",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    route.params.id;
    const api = useApi();
    useRouter();
    const isLoading = ref(false);
    const errors = ref([]);
    const form = reactive({
      title: "",
      description: "",
      start_date: /* @__PURE__ */ new Date(),
      end_date: new Date(Date.now() + 60 * 60 * 1e3),
      // Default 1 hour later
      time_limit: 60,
      // Minutes
      passing_score: 50,
      // Percent
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
      console.log("Searching quizzes for:", form.title);
      isSearchingQuizzes.value = true;
      try {
        const res = await api.get(`/api/quizzes/search?q=${encodeURIComponent(form.title)}`);
        console.log("API Response:", res);
        if (Array.isArray(res)) {
          quizSuggestions.value = res;
        } else if (res.quizzes) {
          quizSuggestions.value = res.quizzes;
        } else if (res.data) {
          quizSuggestions.value = Array.isArray(res.data) ? res.data : [];
        } else {
          quizSuggestions.value = [];
        }
        console.log("Quiz suggestions loaded:", quizSuggestions.value.length, "items");
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
    return (_ctx, _push, _parent, _attrs) => {
      const _component_VueDatePicker = resolveComponent("VueDatePicker");
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-4xl" }, _attrs))}><div class="flex items-center gap-4 mb-6"><button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-left-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</button><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E2B\u0E21\u0E48</h1><p class="text-sm text-gray-500">\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49</p></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"><div class="p-6 space-y-6"><div class="grid gap-6"><div class="relative"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A <span class="text-red-500">*</span></label><div class="relative"><input${ssrRenderAttr("value", unref(form).title)} type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E1A\u0E17\u0E17\u0E35\u0E48 1">`);
      if (unref(isSearchingQuizzes)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (unref(showSuggestions) && unref(quizSuggestions).length > 0) {
        _push(`<div class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden"><div class="px-4 py-2 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-gray-100 dark:border-gray-700"><p class="text-xs font-semibold text-purple-700 dark:text-purple-300 flex items-center gap-1.5">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:copy-24-regular",
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(` \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E40\u0E04\u0E22\u0E2A\u0E23\u0E49\u0E32\u0E07 </p></div><div class="max-h-64 overflow-y-auto"><!--[-->`);
        ssrRenderList(unref(quizSuggestions), (quiz, index) => {
          _push(`<div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-b-0"><button type="button" class="flex items-center gap-3 flex-1 text-left"><div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center flex-shrink-0">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:quiz-new-24-filled",
            class: "w-5 h-5 text-white"
          }, null, _parent));
          _push(`</div><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(quiz.title)}</p><div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-0.5"><span class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:document-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(` ${ssrInterpolate(quiz.questions_count || 0)} \u0E02\u0E49\u0E2D </span><span class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:star-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(` ${ssrInterpolate(quiz.total_score || 0)} \u0E04\u0E30\u0E41\u0E19\u0E19 </span>`);
          if (quiz.course) {
            _push(`<span class="text-purple-600 dark:text-purple-400 truncate"> \u0E08\u0E32\u0E01: ${ssrInterpolate(quiz.course.name || quiz.course.title)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></button><div class="flex items-center gap-1 flex-shrink-0"><button type="button" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A (\u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21)">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:copy-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button><button type="button" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="\u0E0B\u0E48\u0E2D\u0E19">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-16-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button></div></div>`);
        });
        _push(`<!--]--></div><div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700"><p class="text-xs text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-16-regular",
          class: "w-3.5 h-3.5 inline mr-1"
        }, null, _parent));
        _push(` \u0E04\u0E25\u0E34\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E33\u0E04\u0E48\u0E32\u0E21\u0E32\u0E43\u0E0A\u0E49 \u0E2B\u0E23\u0E37\u0E2D\u0E04\u0E25\u0E34\u0E01 `);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:copy-24-regular",
          class: "w-3 h-3 inline text-green-600"
        }, null, _parent));
        _push(` \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21 </p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 </label><textarea rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow" placeholder="\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E22\u0E48\u0E2D \u0E2B\u0E23\u0E37\u0E2D\u0E04\u0E33\u0E0A\u0E35\u0E49\u0E41\u0E08\u0E07...">${ssrInterpolate(unref(form).description)}</textarea></div></div><hr class="border-gray-200 dark:border-gray-700"><div class="grid md:grid-cols-2 gap-6"><div class="space-y-4"><h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:timer-24-regular",
        class: "w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E40\u0E27\u0E25\u0E32\u0E41\u0E25\u0E30\u0E04\u0E30\u0E41\u0E19\u0E19 </h3><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E40\u0E27\u0E25\u0E32\u0E43\u0E19\u0E01\u0E32\u0E23\u0E17\u0E33\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A (\u0E19\u0E32\u0E17\u0E35) <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", unref(form).time_limit)} type="number" min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E40\u0E01\u0E13\u0E11\u0E4C\u0E1C\u0E48\u0E32\u0E19 (%) <span class="text-red-500">*</span></label><div class="relative"><input${ssrRenderAttr("value", unref(form).passing_score)} type="number" min="0" max="100" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-8"><span class="absolute right-3 top-2.5 text-gray-500">%</span></div></div></div><div class="space-y-4"><h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:calendar-ltr-24-regular",
        class: "w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(` \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E01\u0E32\u0E23 </h3><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33\u0E44\u0E14\u0E49\u0E15\u0E31\u0E49\u0E07\u0E41\u0E15\u0E48 </label>`);
      _push(ssrRenderComponent(_component_VueDatePicker, {
        modelValue: unref(form).start_date,
        "onUpdate:modelValue": ($event) => unref(form).start_date = $event,
        format: "dd/MM/yyyy HH:mm",
        "auto-apply": "",
        teleport: true,
        "input-class-name": "bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white"
      }, null, _parent));
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E40\u0E21\u0E37\u0E48\u0E2D </label>`);
      _push(ssrRenderComponent(_component_VueDatePicker, {
        modelValue: unref(form).end_date,
        "onUpdate:modelValue": ($event) => unref(form).end_date = $event,
        format: "dd/MM/yyyy HH:mm",
        "auto-apply": "",
        teleport: true
      }, null, _parent));
      _push(`</div></div></div><hr class="border-gray-200 dark:border-gray-700"><div class="flex flex-col sm:flex-row gap-6"><label class="flex items-center gap-3 cursor-pointer group"><div class="relative flex items-center"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(unref(form).shuffle_questions) ? ssrLooseContain(unref(form).shuffle_questions, null) : unref(form).shuffle_questions) ? " checked" : ""} class="peer sr-only"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div></div><span class="text-sm font-medium text-gray-900 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">\u0E2A\u0E25\u0E31\u0E1A\u0E02\u0E49\u0E2D\u0E04\u0E33\u0E16\u0E32\u0E21</span></label><label class="flex items-center gap-3 cursor-pointer group"><div class="relative flex items-center"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(unref(form).is_active) ? ssrLooseContain(unref(form).is_active, null) : unref(form).is_active) ? " checked" : ""} class="peer sr-only"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div></div><span class="text-sm font-medium text-gray-900 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 (\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48)</span></label></div>`);
      if (unref(errors).length) {
        _push(`<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4"><div class="flex items-start gap-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-filled",
          class: "w-5 h-5 text-red-500 mt-0.5"
        }, null, _parent));
        _push(`<ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400"><!--[-->`);
        ssrRenderList(unref(errors), (error, index) => {
          _push(`<li>${ssrInterpolate(error)}</li>`);
        });
        _push(`<!--]--></ul></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3"><button class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-6 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"${ssrIncludeBooleanAttr(!unref(isFormValid) || unref(isLoading)) ? " disabled" : ""}>`);
      if (unref(isLoading)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<span>${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A")}</span></button></div></div>`);
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(isDuplicating)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 flex flex-col items-center gap-4 shadow-2xl"><div class="relative">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-14 h-14 text-purple-600"
          }, null, _parent));
          _push2(`</div><div class="text-center"><p class="text-lg font-bold text-gray-900 dark:text-white">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A...</p><p class="text-sm text-gray-500 dark:text-gray-400 mt-1">\u0E23\u0E27\u0E21\u0E16\u0E36\u0E07\u0E04\u0E33\u0E16\u0E32\u0E21\u0E41\u0E25\u0E30\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/quizzes/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=create-B3RtKq2E.mjs.map
