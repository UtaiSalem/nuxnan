import { defineComponent, ref, reactive, computed, resolveComponent, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderStyle, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrRenderList } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { p as useRoute, i as useApi } from './server.mjs';
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
  __name: "edit",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    route.params.id;
    route.params.quizId;
    useApi();
    const activeTab = ref("settings");
    const isLoading = ref(false);
    const isSaving = ref(false);
    ref([]);
    const form = reactive({
      title: "",
      description: "",
      start_date: /* @__PURE__ */ new Date(),
      end_date: /* @__PURE__ */ new Date(),
      time_limit: 60,
      passing_score: 50,
      is_active: true,
      shuffle_questions: false
    });
    const quiz = ref(null);
    const questions = ref([]);
    const isFormValid = computed(() => {
      return form.title.trim() !== "" && form.time_limit > 0 && form.passing_score >= 0 && form.passing_score <= 100 && (!form.start_date || !form.end_date || new Date(form.end_date) > new Date(form.start_date));
    });
    const questionModal = ref(false);
    const editingQuestion = ref(null);
    const isSavingQuestion = ref(false);
    ref(null);
    const questionMediaPreview = ref(null);
    ref({});
    const optionMediaPreviews = ref({});
    const questionForm = reactive({
      text: "",
      points: 1,
      pp_fine: 0,
      media_url: null,
      options: [
        { text: "", is_correct: false, media_url: null },
        { text: "", is_correct: false, media_url: null }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_VueDatePicker = resolveComponent("VueDatePicker");
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-4xl" }, _attrs))}><div class="flex items-center gap-4 mb-6"><button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-left-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</button><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">\u0E41\u0E01\u0E49\u0E44\u0E02\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</h1><p class="text-sm text-gray-500">${ssrInterpolate((_a = unref(quiz)) == null ? void 0 : _a.title)}</p></div></div>`);
      if (unref(isLoading)) {
        _push(`<div class="flex justify-center p-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:3-dots-fade",
          class: "w-10 h-10 text-gray-400"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"><div class="flex border-b border-gray-200 dark:border-gray-700"><button class="${ssrRenderClass(["px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative", unref(activeTab) === "settings" ? "text-purple-600 dark:text-purple-400" : "text-gray-500 hover:text-gray-900 dark:hover:text-gray-300"])}"> \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B `);
        if (unref(activeTab) === "settings") {
          _push(`<div class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button><button class="${ssrRenderClass(["px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative", unref(activeTab) === "questions" ? "text-purple-600 dark:text-purple-400" : "text-gray-500 hover:text-gray-900 dark:hover:text-gray-300"])}"> \u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A (${ssrInterpolate(unref(questions).length)}) `);
        if (unref(activeTab) === "questions") {
          _push(`<div class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button></div><div class="p-6 space-y-6" style="${ssrRenderStyle(unref(activeTab) === "settings" ? null : { display: "none" })}"><div class="grid gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", unref(form).title)} type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22</label><textarea rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">${ssrInterpolate(unref(form).description)}</textarea></div></div><hr class="border-gray-200 dark:border-gray-700"><div class="grid md:grid-cols-2 gap-6"><div class="space-y-4"><h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:timer-24-regular",
          class: "w-5 h-5 text-gray-400"
        }, null, _parent));
        _push(` \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E40\u0E27\u0E25\u0E32\u0E41\u0E25\u0E30\u0E04\u0E30\u0E41\u0E19\u0E19 </h3><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E27\u0E25\u0E32 (\u0E19\u0E32\u0E17\u0E35) <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", unref(form).time_limit)} type="number" min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E01\u0E13\u0E11\u0E4C\u0E1C\u0E48\u0E32\u0E19 (%) <span class="text-red-500">*</span></label><div class="relative"><input${ssrRenderAttr("value", unref(form).passing_score)} type="number" min="0" max="100" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-8"><span class="absolute right-3 top-2.5 text-gray-500">%</span></div></div></div><div class="space-y-4"><h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-ltr-24-regular",
          class: "w-5 h-5 text-gray-400"
        }, null, _parent));
        _push(` \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E01\u0E32\u0E23 </h3><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E23\u0E34\u0E48\u0E21</label>`);
        _push(ssrRenderComponent(_component_VueDatePicker, {
          modelValue: unref(form).start_date,
          "onUpdate:modelValue": ($event) => unref(form).start_date = $event,
          format: "dd/MM/yyyy HH:mm",
          "auto-apply": "",
          teleport: true,
          "input-class-name": "bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white"
        }, null, _parent));
        _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</label>`);
        _push(ssrRenderComponent(_component_VueDatePicker, {
          modelValue: unref(form).end_date,
          "onUpdate:modelValue": ($event) => unref(form).end_date = $event,
          format: "dd/MM/yyyy HH:mm",
          "auto-apply": "",
          teleport: true
        }, null, _parent));
        _push(`</div></div></div><hr class="border-gray-200 dark:border-gray-700"><div class="flex flex-col sm:flex-row gap-6"><label class="flex items-center gap-3 cursor-pointer group"><div class="relative flex items-center"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(unref(form).shuffle_questions) ? ssrLooseContain(unref(form).shuffle_questions, null) : unref(form).shuffle_questions) ? " checked" : ""} class="peer sr-only"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div></div><span class="text-sm font-medium text-gray-900 dark:text-gray-300">\u0E2A\u0E25\u0E31\u0E1A\u0E02\u0E49\u0E2D\u0E04\u0E33\u0E16\u0E32\u0E21</span></label><label class="flex items-center gap-3 cursor-pointer group"><div class="relative flex items-center"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(unref(form).is_active) ? ssrLooseContain(unref(form).is_active, null) : unref(form).is_active) ? " checked" : ""} class="peer sr-only"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div></div><span class="text-sm font-medium text-gray-900 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</span></label></div><div class="flex justify-end gap-3 pt-4"><button class="px-6 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"${ssrIncludeBooleanAttr(unref(isSaving)) ? " disabled" : ""}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-6 py-2 rounded-lg bg-purple-600 text-white font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors"${ssrIncludeBooleanAttr(!unref(isFormValid) || unref(isSaving)) ? " disabled" : ""}>`);
        if (unref(isSaving)) {
          _push(`<span>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01...</span>`);
        } else {
          _push(`<span>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07</span>`);
        }
        _push(`</button></div></div><div class="p-6" style="${ssrRenderStyle(unref(activeTab) === "questions" ? null : { display: "none" })}"><div class="flex justify-between items-center mb-6"><h3 class="font-bold text-gray-900 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E04\u0E33\u0E16\u0E32\u0E21</h3><button class="px-4 py-2 rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-900/50 transition-colors flex items-center gap-2 font-medium">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-circle-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A </button></div>`);
        if (unref(questions).length === 0) {
          _push(`<div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:quiz-new-24-regular",
            class: "w-12 h-12 text-gray-300 mx-auto mb-3"
          }, null, _parent));
          _push(`<p class="text-gray-500">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A\u0E43\u0E19\u0E0A\u0E38\u0E14\u0E19\u0E35\u0E49</p><button class="text-purple-600 hover:underline mt-2">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A\u0E41\u0E23\u0E01</button></div>`);
        } else {
          _push(`<div class="space-y-4"><!--[-->`);
          ssrRenderList(unref(questions), (q, index) => {
            _push(`<div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-100 dark:border-gray-700"><div class="flex items-start justify-between gap-4"><div class="flex gap-3"><div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center font-bold text-gray-500 text-sm shadow-sm">${ssrInterpolate(index + 1)}</div><div><h4 class="font-medium text-gray-900 dark:text-white mb-2">${ssrInterpolate(q.text)}</h4><div class="space-y-1"><!--[-->`);
            ssrRenderList(q.options, (opt) => {
              _push(`<div class="flex items-center gap-2 text-sm">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: opt.is_correct ? "fluent:checkmark-circle-24-filled" : "fluent:circle-24-regular",
                class: opt.is_correct ? "text-green-500" : "text-gray-400"
              }, null, _parent));
              _push(`<span class="${ssrRenderClass(opt.is_correct ? "text-green-700 dark:text-green-400 font-medium" : "text-gray-600 dark:text-gray-400")}">${ssrInterpolate(opt.text)}</span></div>`);
            });
            _push(`<!--]--></div></div></div><div class="flex items-center gap-1"><span class="text-xs font-bold px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded text-blue-600 dark:text-blue-300 mr-1">${ssrInterpolate(q.points)} \u0E04\u0E30\u0E41\u0E19\u0E19</span><span class="text-xs font-bold px-2 py-1 bg-orange-100 dark:bg-orange-900/30 rounded text-orange-600 dark:text-orange-300 mr-2">${ssrInterpolate(q.pp_fine || 0)} \u0E41\u0E15\u0E49\u0E21</span><button class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full transition-colors" title="\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-20-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button><button class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors" title="\u0E25\u0E1A\u0E04\u0E33\u0E16\u0E32\u0E21">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:delete-20-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button></div></div></div>`);
          });
          _push(`<!--]--></div>`);
        }
        _push(`</div></div>`);
      }
      if (unref(questionModal)) {
        _push(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"><div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col"><div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center"><h3 class="font-bold text-lg text-gray-900 dark:text-white">${ssrInterpolate(unref(editingQuestion) ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A\u0E43\u0E2B\u0E21\u0E48")}</h3><button class="text-gray-400 hover:text-gray-500">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</button></div><div class="p-6 overflow-y-auto flex-1 space-y-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E04\u0E33\u0E16\u0E32\u0E21</label><textarea rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">${ssrInterpolate(unref(questionForm).text)}</textarea><div class="mt-3"><label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">\u0E41\u0E19\u0E1A\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E/\u0E44\u0E1F\u0E25\u0E4C\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A\u0E04\u0E33\u0E16\u0E32\u0E21</label>`);
        if (unref(questionMediaPreview) || unref(questionForm).media_url) {
          _push(`<div class="relative inline-block mb-2"><img${ssrRenderAttr("src", unref(questionMediaPreview) || unref(questionForm).media_url)} class="h-24 w-auto rounded-lg object-cover" alt="Question media"><button type="button" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-12-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button></div>`);
        } else {
          _push(`<label class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors w-fit">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:image-add-20-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="text-sm">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E1F\u0E25\u0E4C</span><input type="file" accept="image/*,video/*,audio/*" class="hidden"></label>`);
        }
        _push(`</div></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E04\u0E30\u0E41\u0E19\u0E19</label><input${ssrRenderAttr("value", unref(questionForm).points)} type="number" min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"> \u0E41\u0E15\u0E49\u0E21\u0E04\u0E48\u0E32\u0E1B\u0E23\u0E31\u0E1A (PP Fine) `);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-16-regular",
          class: "inline w-4 h-4 text-gray-400 ml-1",
          title: "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E2B\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07 (0 = \u0E1F\u0E23\u0E35)"
        }, null, _parent));
        _push(`</label><input${ssrRenderAttr("value", unref(questionForm).pp_fine)} type="number" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"><p class="text-xs text-gray-500 mt-1">0 = \u0E41\u0E01\u0E49\u0E44\u0E02\u0E44\u0E14\u0E49\u0E1F\u0E23\u0E35</p></div></div><div><div class="flex items-center justify-between mb-2"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 (\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01 - \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D)</label><button type="button" class="text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400 flex items-center gap-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-circle-16-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 </button></div><div class="space-y-3"><!--[-->`);
        ssrRenderList(unref(questionForm).options, (opt, i) => {
          _push(`<div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"><div class="flex items-start gap-3"><button class="focus:outline-none mt-2.5" type="button" title="\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01/\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07"><div class="${ssrRenderClass([opt.is_correct ? "bg-green-500 border-green-500 text-white" : "border-gray-300 dark:border-gray-600 hover:border-green-400", "w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200"])}">`);
          if (opt.is_correct) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:checkmark-16-filled",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</div></button><div class="flex-1 space-y-2"><input${ssrRenderAttr("value", opt.text)} type="text"${ssrRenderAttr("placeholder", `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${i + 1}`)} class="${ssrRenderClass([{ "border-green-500 ring-1 ring-green-500": opt.is_correct }, "w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"])}"><div class="flex items-center gap-2">`);
          if (unref(optionMediaPreviews)[i] || opt.media_url) {
            _push(`<div class="relative inline-block"><img${ssrRenderAttr("src", unref(optionMediaPreviews)[i] || opt.media_url)} class="h-16 w-auto rounded object-cover"${ssrRenderAttr("alt", `Option ${i + 1} media`)}><button type="button" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-12-regular",
              class: "w-3 h-3"
            }, null, _parent));
            _push(`</button></div>`);
          } else {
            _push(`<label class="flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors text-xs">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:image-add-20-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(`<span>\u0E41\u0E19\u0E1A\u0E23\u0E39\u0E1B</span><input type="file" accept="image/*" class="hidden"></label>`);
          }
          _push(`</div></div>`);
          if (unref(questionForm).options.length > 2) {
            _push(`<button type="button" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors mt-1" title="\u0E25\u0E1A\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E19\u0E35\u0E49">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:delete-20-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
        });
        _push(`<!--]--></div><p class="mt-2 text-xs text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-16-regular",
          class: "inline w-3.5 h-3.5 mr-1"
        }, null, _parent));
        _push(` \u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D </p></div></div><div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3"><button class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"${ssrIncludeBooleanAttr(unref(isSavingQuestion)) ? " disabled" : ""}>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</button><button class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium disabled:opacity-50 flex items-center gap-2"${ssrIncludeBooleanAttr(unref(isSavingQuestion)) ? " disabled" : ""}>`);
        if (unref(isSavingQuestion)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-5 h-5"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<span>${ssrInterpolate(unref(isSavingQuestion) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")}</span></button></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/quizzes/[quizId]/edit.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=edit-DsuRWs51.mjs.map
