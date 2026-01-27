import { _ as _export_sfc, p as useRoute, n as navigateTo, i as useApi, j as __nuxt_component_0$2 } from './server.mjs';
import { defineComponent, inject, watch, ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrIncludeBooleanAttr, ssrRenderAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './RichTextEditor-J-3c3zks.mjs';
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
import '@tiptap/vue-3';
import '@tiptap/starter-kit';
import '@tiptap/extension-link';
import '@tiptap/extension-text-align';
import '@tiptap/extension-underline';
import '@tiptap/extension-placeholder';
import '@tiptap/extension-image';
import '@tiptap/extension-youtube';
import '@tiptap/extension-text-style';
import '@tiptap/extension-color';
import '@tiptap/extension-highlight';
import '@tiptap/extension-subscript';
import '@tiptap/extension-superscript';
import '@tiptap/extension-task-list';
import '@tiptap/extension-task-item';
import '@tiptap/extension-table';
import '@tiptap/extension-table-row';
import '@tiptap/extension-table-cell';
import '@tiptap/extension-table-header';
import '@tiptap/extension-code-block-lowlight';
import 'lowlight';

const DEFAULT_DESCRIPTION_TEMPLATE = `<h2>\u{1F4CB} \u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h2>
<p>\u0E43\u0E2A\u0E48\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48...</p>

<h2>\u{1F3AF} \u0E27\u0E31\u0E15\u0E16\u0E38\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C</h2>
<ul>
  <li>\u0E27\u0E31\u0E15\u0E16\u0E38\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 1</li>
  <li>\u0E27\u0E31\u0E15\u0E16\u0E38\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 2</li>
</ul>

<h2>\u{1F4D6} \u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19</h2>
<ul>
  <li>\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 1</li>
  <li>\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 2</li>
</ul>`;
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "settings",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    inject("refreshCourse");
    const isLoadingParent = inject("isLoading");
    const route = useRoute();
    const courseId = route.params.id;
    watch(() => isLoadingParent == null ? void 0 : isLoadingParent.value, (loading) => {
      if (!loading && (isCourseAdmin == null ? void 0 : isCourseAdmin.value) === false) {
        navigateTo(`/courses/${courseId}`);
      }
    });
    const isSaving = ref(false);
    useApi();
    const form = ref({
      code: "",
      name: "",
      description: "",
      category: "",
      level: "",
      credit_units: 0,
      hours_per_week: 0,
      start_date: "",
      end_date: "",
      auto_accept_members: false,
      tuition_fees: 0,
      saleable: false,
      price: 0,
      discount: 0,
      discount_type: "fixed",
      semester: "",
      academic_year: "",
      status: "draft"
    });
    const courseCategories = [
      "\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22",
      "\u0E04\u0E13\u0E34\u0E15\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C",
      "\u0E27\u0E34\u0E17\u0E22\u0E32\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C",
      "\u0E2A\u0E31\u0E07\u0E04\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32 \u0E28\u0E32\u0E2A\u0E19\u0E32 \u0E41\u0E25\u0E30\u0E27\u0E31\u0E12\u0E19\u0E18\u0E23\u0E23\u0E21",
      "\u0E2A\u0E38\u0E02\u0E28\u0E36\u0E01\u0E29\u0E32\u0E41\u0E25\u0E30\u0E1E\u0E25\u0E28\u0E36\u0E01\u0E29\u0E32",
      "\u0E28\u0E34\u0E25\u0E1B\u0E30",
      "\u0E01\u0E32\u0E23\u0E07\u0E32\u0E19\u0E2D\u0E32\u0E0A\u0E35\u0E1E\u0E41\u0E25\u0E30\u0E40\u0E17\u0E04\u0E42\u0E19\u0E42\u0E25\u0E22\u0E35",
      "\u0E20\u0E32\u0E29\u0E32\u0E15\u0E48\u0E32\u0E07\u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28",
      "\u0E2D\u0E37\u0E48\u0E19\u0E46"
    ];
    const courseLevels = [
      "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 1",
      "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 2",
      "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 3",
      "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 4",
      "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 5",
      "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 6",
      "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 1",
      "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 2",
      "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 3",
      "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 4",
      "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 5",
      "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 6",
      "\u0E2D\u0E38\u0E14\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32",
      "\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B"
    ];
    watch(() => course == null ? void 0 : course.value, (newCourse) => {
      var _a;
      if (newCourse) {
        form.value = {
          code: newCourse.code || "",
          name: newCourse.name || "",
          description: newCourse.description || DEFAULT_DESCRIPTION_TEMPLATE,
          category: newCourse.category || "",
          level: newCourse.level || "",
          credit_units: newCourse.credit_units || 0,
          hours_per_week: newCourse.hours_per_week || 0,
          start_date: newCourse.start_date ? newCourse.start_date.split(/[T ]/)[0] : "",
          end_date: newCourse.end_date ? newCourse.end_date.split(/[T ]/)[0] : "",
          auto_accept_members: Boolean((_a = newCourse.setting) == null ? void 0 : _a.auto_accept_members),
          tuition_fees: newCourse.tuition_fees || 0,
          saleable: newCourse.saleable || false,
          price: newCourse.price || 0,
          discount: newCourse.discount || 0,
          discount_type: newCourse.discount_type || "fixed",
          semester: newCourse.semester || "",
          academic_year: newCourse.academic_year || "",
          status: newCourse.status || "draft"
        };
      }
    }, { immediate: true });
    const netPrice = computed(() => {
      if (!form.value.saleable) return 0;
      const price = Number(form.value.price) || 0;
      const discount = Number(form.value.discount) || 0;
      if (form.value.discount_type === "percent") {
        const discountAmount = price * discount / 100;
        return Math.max(0, price - discountAmount);
      }
      return Math.max(0, price - discount);
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_ClientOnly = __nuxt_component_0$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-8 max-w-7xl mx-auto pb-20" }, _attrs))} data-v-02a6f9a8><div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 p-8 text-white shadow-lg" data-v-02a6f9a8><div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-3xl" data-v-02a6f9a8></div><div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-black/10 rounded-full blur-3xl" data-v-02a6f9a8></div><div class="relative z-10 flex items-center justify-between" data-v-02a6f9a8><div class="flex items-center gap-4" data-v-02a6f9a8><div class="p-3 bg-white/20 backdrop-blur-md rounded-xl" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi-light:settings",
        class: "w-8 h-8 text-white"
      }, null, _parent));
      _push(`</div><div data-v-02a6f9a8><h1 class="text-2xl font-bold" data-v-02a6f9a8>\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h1><p class="text-blue-100 opacity-90" data-v-02a6f9a8>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E25\u0E30\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E02\u0E2D\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div></div><button${ssrIncludeBooleanAttr(unref(isSaving)) ? " disabled" : ""} class="hidden md:flex items-center gap-2 px-6 py-2.5 bg-white text-blue-600 font-bold rounded-xl shadow-lg hover:bg-blue-50 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed" data-v-02a6f9a8>`);
      if (unref(isSaving)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:save-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07 </button></div></div><form class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-v-02a6f9a8><div class="lg:col-span-2 space-y-8" data-v-02a6f9a8><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" data-v-02a6f9a8><div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50" data-v-02a6f9a8><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:information-circle",
        class: "w-5 h-5 text-cyan-500"
      }, null, _parent));
      _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B </h2></div><div class="p-6 space-y-6" data-v-02a6f9a8><div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-v-02a6f9a8><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32</label><div class="relative" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:number-symbol-square-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span><input${ssrRenderAttr("value", unref(form).code)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 CS101" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white" data-v-02a6f9a8></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 <span class="text-red-500" data-v-02a6f9a8>*</span></label><div class="relative" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:book-open",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span><input${ssrRenderAttr("value", unref(form).name)} type="text" required placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white" data-v-02a6f9a8></div></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</label>`);
      _push(ssrRenderComponent(_sfc_main$1, {
        modelValue: unref(form).description,
        "onUpdate:modelValue": ($event) => unref(form).description = $event,
        placeholder: "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32...",
        class: "w-full",
        "min-height": "200px"
      }, null, _parent));
      _push(`</div><div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-v-02a6f9a8><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48</label><div class="relative" data-v-02a6f9a8><select class="w-full pl-4 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white appearance-none" data-v-02a6f9a8><option value="" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).category) ? ssrLooseContain(unref(form).category, "") : ssrLooseEqual(unref(form).category, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48</option><!--[-->`);
      ssrRenderList(courseCategories, (cat) => {
        _push(`<option${ssrRenderAttr("value", cat)} data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).category) ? ssrLooseContain(unref(form).category, cat) : ssrLooseEqual(unref(form).category, cat)) ? " selected" : ""}>${ssrInterpolate(cat)}</option>`);
      });
      _push(`<!--]--></select><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:chevron-down",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</label><div class="relative" data-v-02a6f9a8><select class="w-full pl-4 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white appearance-none" data-v-02a6f9a8><option value="" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).level) ? ssrLooseContain(unref(form).level, "") : ssrLooseEqual(unref(form).level, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</option><!--[-->`);
      ssrRenderList(courseLevels, (level) => {
        _push(`<option${ssrRenderAttr("value", level)} data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).level) ? ssrLooseContain(unref(form).level, level) : ssrLooseEqual(unref(form).level, level)) ? " selected" : ""}>${ssrInterpolate(level)}</option>`);
      });
      _push(`<!--]--></select><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:chevron-down",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span></div></div></div></div></section><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" data-v-02a6f9a8><div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50" data-v-02a6f9a8><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:academic-cap",
        class: "w-5 h-5 text-purple-500"
      }, null, _parent));
      _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E0A\u0E34\u0E07\u0E27\u0E34\u0E0A\u0E32\u0E01\u0E32\u0E23 </h2></div><div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6" data-v-02a6f9a8><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48</label><div class="relative" data-v-02a6f9a8><select class="w-full pl-4 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white appearance-none" data-v-02a6f9a8><option value="" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).semester) ? ssrLooseContain(unref(form).semester, "") : ssrLooseEqual(unref(form).semester, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19</option><option value="1" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).semester) ? ssrLooseContain(unref(form).semester, "1") : ssrLooseEqual(unref(form).semester, "1")) ? " selected" : ""}>1</option><option value="2" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).semester) ? ssrLooseContain(unref(form).semester, "2") : ssrLooseEqual(unref(form).semester, "2")) ? " selected" : ""}>2</option><option value="3" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).semester) ? ssrLooseContain(unref(form).semester, "3") : ssrLooseEqual(unref(form).semester, "3")) ? " selected" : ""}>3</option><option value="summer" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).semester) ? ssrLooseContain(unref(form).semester, "summer") : ssrLooseEqual(unref(form).semester, "summer")) ? " selected" : ""}>\u0E24\u0E14\u0E39\u0E23\u0E49\u0E2D\u0E19</option></select><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:chevron-down",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</label><div class="relative" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:calendar",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span><input${ssrRenderAttr("value", unref(form).academic_year)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 2567" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white" data-v-02a6f9a8></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E2B\u0E19\u0E48\u0E27\u0E22\u0E01\u0E34\u0E15</label><div class="relative" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:star",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span><input${ssrRenderAttr("value", unref(form).credit_units)} type="number" min="0" step="0.5" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white" data-v-02a6f9a8></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07/\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C</label><div class="relative" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:clock",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</span><input${ssrRenderAttr("value", unref(form).hours_per_week)} type="number" min="0" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white" data-v-02a6f9a8></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E27\u0E31\u0E19\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</label>`);
      _push(ssrRenderComponent(_component_ClientOnly, null, {}, _parent));
      _push(`</div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E27\u0E31\u0E19\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</label>`);
      _push(ssrRenderComponent(_component_ClientOnly, null, {}, _parent));
      _push(`</div></div><div class="px-6 pb-6 pt-2 hidden lg:flex justify-end border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50" data-v-02a6f9a8><button type="submit"${ssrIncludeBooleanAttr(unref(isSaving)) ? " disabled" : ""} class="mt-4 flex items-center gap-2 px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50" data-v-02a6f9a8>`);
      if (unref(isSaving)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:save-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07 </button></div></section></div><div class="space-y-8" data-v-02a6f9a8><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" data-v-02a6f9a8><div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50" data-v-02a6f9a8><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:globe-alt",
        class: "w-5 h-5 text-green-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48 </h2></div><div class="p-6 space-y-4" data-v-02a6f9a8><div class="space-y-3" data-v-02a6f9a8><label class="${ssrRenderClass([{ "ring-2 ring-green-500 border-transparent": unref(form).status === "published" }, "flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"])}" data-v-02a6f9a8><div class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600" data-v-02a6f9a8>`);
      if (unref(form).status === "published") {
        _push(`<div class="w-3 h-3 rounded-full bg-green-500" data-v-02a6f9a8></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><input type="radio"${ssrIncludeBooleanAttr(ssrLooseEqual(unref(form).status, "published")) ? " checked" : ""} value="published" class="hidden" data-v-02a6f9a8><div class="flex-1" data-v-02a6f9a8><div class="font-semibold text-gray-900 dark:text-white" data-v-02a6f9a8>\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48</div><div class="text-xs text-gray-500" data-v-02a6f9a8>\u0E17\u0E38\u0E01\u0E04\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E40\u0E2B\u0E47\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49</div></div></label><label class="${ssrRenderClass([{ "ring-2 ring-gray-400 border-transparent": unref(form).status === "draft" }, "flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"])}" data-v-02a6f9a8><div class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600" data-v-02a6f9a8>`);
      if (unref(form).status === "draft") {
        _push(`<div class="w-3 h-3 rounded-full bg-gray-400" data-v-02a6f9a8></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><input type="radio"${ssrIncludeBooleanAttr(ssrLooseEqual(unref(form).status, "draft")) ? " checked" : ""} value="draft" class="hidden" data-v-02a6f9a8><div class="flex-1" data-v-02a6f9a8><div class="font-semibold text-gray-900 dark:text-white" data-v-02a6f9a8>\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07</div><div class="text-xs text-gray-500" data-v-02a6f9a8>\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19\u0E17\u0E35\u0E48\u0E40\u0E2B\u0E47\u0E19</div></div></label><label class="${ssrRenderClass([{ "ring-2 ring-orange-500 border-transparent": unref(form).status === "archived" }, "flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"])}" data-v-02a6f9a8><div class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600" data-v-02a6f9a8>`);
      if (unref(form).status === "archived") {
        _push(`<div class="w-3 h-3 rounded-full bg-orange-500" data-v-02a6f9a8></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><input type="radio"${ssrIncludeBooleanAttr(ssrLooseEqual(unref(form).status, "archived")) ? " checked" : ""} value="archived" class="hidden" data-v-02a6f9a8><div class="flex-1" data-v-02a6f9a8><div class="font-semibold text-gray-900 dark:text-white" data-v-02a6f9a8>\u0E40\u0E01\u0E47\u0E1A\u0E16\u0E32\u0E27\u0E23</div><div class="text-xs text-gray-500" data-v-02a6f9a8>\u0E1B\u0E34\u0E14\u0E23\u0E31\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E41\u0E25\u0E30\u0E0B\u0E48\u0E2D\u0E19\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32</div></div></label></div></div></section><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" data-v-02a6f9a8><div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50" data-v-02a6f9a8><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:cog-6-tooth",
        class: "w-5 h-5 text-orange-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 </h2></div><div class="p-6 space-y-6" data-v-02a6f9a8><div class="flex items-center justify-between" data-v-02a6f9a8><div data-v-02a6f9a8><div class="font-semibold text-gray-900 dark:text-white" data-v-02a6f9a8>\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34</div><div class="text-xs text-gray-500" data-v-02a6f9a8>\u0E44\u0E21\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E14\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E04\u0E33\u0E02\u0E2D</div></div><label class="relative inline-flex items-center cursor-pointer" data-v-02a6f9a8><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).auto_accept_members) ? ssrLooseContain(unref(form).auto_accept_members, null) : unref(form).auto_accept_members) ? " checked" : ""} type="checkbox" class="sr-only peer" data-v-02a6f9a8><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500" data-v-02a6f9a8></div></label></div><hr class="border-gray-100 dark:border-gray-700" data-v-02a6f9a8><div class="flex items-center justify-between" data-v-02a6f9a8><div data-v-02a6f9a8><div class="font-semibold text-gray-900 dark:text-white" data-v-02a6f9a8>\u0E40\u0E1B\u0E34\u0E14\u0E02\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</div><div class="text-xs text-gray-500" data-v-02a6f9a8>\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E23\u0E32\u0E04\u0E32\u0E41\u0E25\u0E30\u0E02\u0E32\u0E22</div></div><label class="relative inline-flex items-center cursor-pointer" data-v-02a6f9a8><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).saleable) ? ssrLooseContain(unref(form).saleable, null) : unref(form).saleable) ? " checked" : ""} type="checkbox" class="sr-only peer" data-v-02a6f9a8><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500" data-v-02a6f9a8></div></label></div>`);
      if (unref(form).saleable) {
        _push(`<div class="pt-2 animate-fade-in-down grid grid-cols-1 gap-4" data-v-02a6f9a8><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E23\u0E32\u0E04\u0E32 (\u0E1A\u0E32\u0E17)</label><div class="relative" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:currency-dollar",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</span><input${ssrRenderAttr("value", unref(form).price)} type="number" min="0" placeholder="0.00" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all dark:text-white" data-v-02a6f9a8></div></div><div class="space-y-2" data-v-02a6f9a8><label class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14</label><div class="flex gap-2" data-v-02a6f9a8><div class="relative flex-1" data-v-02a6f9a8><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-v-02a6f9a8>`);
        if (unref(form).discount_type === "fixed") {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:currency-dollar",
            class: "w-5 h-5"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:receipt-percent",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(`</span><input${ssrRenderAttr("value", unref(form).discount)} type="number" min="0"${ssrRenderAttr("max", unref(form).discount_type === "percent" ? 100 : unref(form).price)} placeholder="0" class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all dark:text-white" data-v-02a6f9a8></div><select class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 text-sm font-medium dark:text-white appearance-none cursor-pointer" data-v-02a6f9a8><option value="fixed" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).discount_type) ? ssrLooseContain(unref(form).discount_type, "fixed") : ssrLooseEqual(unref(form).discount_type, "fixed")) ? " selected" : ""}>\u0E1A\u0E32\u0E17</option><option value="percent" data-v-02a6f9a8${ssrIncludeBooleanAttr(Array.isArray(unref(form).discount_type) ? ssrLooseContain(unref(form).discount_type, "percent") : ssrLooseEqual(unref(form).discount_type, "percent")) ? " selected" : ""}>%</option></select></div></div>`);
        if (unref(form).saleable) {
          _push(`<div class="col-span-1 border-t pt-4 mt-2" data-v-02a6f9a8><div class="flex justify-between items-center text-lg font-bold" data-v-02a6f9a8><span class="text-gray-700 dark:text-gray-300" data-v-02a6f9a8>\u0E23\u0E32\u0E04\u0E32\u0E02\u0E32\u0E22\u0E08\u0E23\u0E34\u0E07 (\u0E2A\u0E38\u0E17\u0E18\u0E34):</span><span class="text-green-600 dark:text-green-400" data-v-02a6f9a8>${ssrInterpolate(unref(netPrice).toLocaleString())} \u0E1A\u0E32\u0E17 </span></div>`);
          if (unref(form).discount > 0) {
            _push(`<p class="text-xs text-gray-500 text-right mt-1" data-v-02a6f9a8> (\u0E08\u0E32\u0E01\u0E23\u0E32\u0E04\u0E32\u0E1B\u0E01\u0E15\u0E34 ${ssrInterpolate(unref(form).price.toLocaleString())} \u0E1A\u0E32\u0E17 \u0E25\u0E14 ${ssrInterpolate(unref(form).discount_type === "percent" ? unref(form).discount + "%" : unref(form).discount.toLocaleString() + " \u0E1A\u0E32\u0E17")}) </p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></section><section class="bg-red-50 dark:bg-red-900/10 rounded-2xl shadow-sm border border-red-100 dark:border-red-900/30 overflow-hidden" data-v-02a6f9a8><div class="p-6 border-b border-red-100 dark:border-red-900/30 bg-red-100/50 dark:bg-red-900/20" data-v-02a6f9a8><h2 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:exclamation-triangle",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E42\u0E0B\u0E19\u0E2D\u0E31\u0E19\u0E15\u0E23\u0E32\u0E22 </h2></div><div class="p-6 text-center" data-v-02a6f9a8><p class="text-sm text-red-600/80 dark:text-red-400/80 mb-4" data-v-02a6f9a8> \u0E01\u0E32\u0E23\u0E25\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E08\u0E30\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E01\u0E39\u0E49\u0E04\u0E37\u0E19\u0E44\u0E14\u0E49 \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E08\u0E30\u0E2B\u0E32\u0E22\u0E44\u0E1B\u0E16\u0E32\u0E27\u0E23 </p><button type="button" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-red-500/20" data-v-02a6f9a8>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:delete-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E25\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E16\u0E32\u0E27\u0E23 </button></div></section></div><div class="fixed bottom-0 left-0 right-0 p-4 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-t border-gray-200 dark:border-gray-800 lg:hidden z-50" data-v-02a6f9a8><button type="submit"${ssrIncludeBooleanAttr(unref(isSaving)) ? " disabled" : ""} class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg active:scale-95 transition-all disabled:opacity-50" data-v-02a6f9a8>`);
      if (unref(isSaving)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:save-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07 </button></div></form></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/settings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const settings = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-02a6f9a8"]]);

export { settings as default };
//# sourceMappingURL=settings-DWATzg8d.mjs.map
