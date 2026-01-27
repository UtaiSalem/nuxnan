import { withCtx, createBlock, createCommentVNode, openBlock, createVNode, ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import _sfc_main$2 from './CourseLayout-9QjNmWeF.mjs';
import { j as __nuxt_component_0$2 } from './server.mjs';
import { Icon } from '@iconify/vue';
import './main-CdHCodS1.mjs';
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
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import 'pinia';
import './useGamification-BliN7lma.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main$1 = {
  __name: "CourseSettings",
  __ssrInlineRender: true,
  props: {
    course: Object,
    isCourseAdmin: Boolean
  },
  emits: ["update-course"],
  setup(__props, { emit: __emit }) {
    const form = ref({
      academy_id: "",
      code: "",
      name: "",
      description: "",
      category: "",
      level: "",
      credit_units: 0,
      hours_per_week: 0,
      start_date: null,
      end_date: null,
      auto_accept_members: false,
      tuition_fees: 0,
      saleable: false,
      price: 0,
      discount: 0,
      discount_type: "fixed",
      semester: "",
      academic_year: "",
      status: true,
      cover: null
      // For new file upload
    });
    const tempCover = ref(null);
    ref(null);
    ref([null, null]);
    const courseCategories = ref([
      { name: "\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22" },
      { name: "\u0E04\u0E13\u0E34\u0E15\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C" },
      { name: "\u0E27\u0E34\u0E17\u0E22\u0E32\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C" },
      { name: "\u0E2A\u0E31\u0E07\u0E04\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32 \u0E28\u0E32\u0E2A\u0E19\u0E32 \u0E41\u0E25\u0E30\u0E27\u0E31\u0E12\u0E19\u0E18\u0E23\u0E23\u0E21" },
      { name: "\u0E2A\u0E38\u0E02\u0E28\u0E36\u0E01\u0E29\u0E32\u0E41\u0E25\u0E30\u0E1E\u0E25\u0E28\u0E36\u0E01\u0E29\u0E32" },
      { name: "\u0E28\u0E34\u0E25\u0E1B\u0E30" },
      { name: "\u0E01\u0E32\u0E23\u0E07\u0E32\u0E19\u0E2D\u0E32\u0E0A\u0E35\u0E1E\u0E41\u0E25\u0E30\u0E40\u0E17\u0E04\u0E42\u0E19\u0E42\u0E25\u0E22\u0E35" },
      { name: "\u0E20\u0E32\u0E29\u0E32\u0E15\u0E48\u0E32\u0E07\u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28" }
    ]);
    const courseLevelOptions = ref([
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 1" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 2" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 3" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 4" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 5" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 6" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 1" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 2" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 3" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 4" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 5" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 6" }
    ]);
    const myAcademies = ref([]);
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 p-8 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700" }, _attrs))}><div class="mb-8 border-b border-gray-100 dark:border-gray-700 pb-4"><h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:cog",
        class: "text-violet-500"
      }, null, _parent));
      _push(` \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 </h3><p class="text-gray-500 dark:text-gray-400 text-sm mt-1">\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E25\u0E30\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E02\u0E2D\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div><form class="space-y-8"><div class="grid grid-cols-1 lg:grid-cols-12 gap-8"><div class="lg:col-span-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E01</label><div class="group relative aspect-video w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-violet-500 transition-colors cursor-pointer">`);
      if (tempCover.value) {
        _push(`<img${ssrRenderAttr("src", tempCover.value)} class="w-full h-full object-cover">`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="absolute inset-0 flex flex-col items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:camera",
        class: "w-8 h-8 text-white mb-2"
      }, null, _parent));
      _push(`<span class="text-white text-xs">\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E23\u0E39\u0E1B</span></div>`);
      if (!tempCover.value) {
        _push(`<div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:photo",
          class: "w-10 h-10 mb-2"
        }, null, _parent));
        _push(`<span class="text-xs">\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<input type="file" class="hidden" accept="image/*"></div></div><div class="lg:col-span-8 space-y-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 <span class="text-red-500">*</span></label><input type="text"${ssrRenderAttr("value", form.value.name)} required class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none"></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32</label><input type="text"${ssrRenderAttr("value", form.value.code)} class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E2A\u0E16\u0E32\u0E19\u0E28\u0E36\u0E01\u0E29\u0E32 (Academy)</label><select class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.value.academy_id) ? ssrLooseContain(form.value.academy_id, "") : ssrLooseEqual(form.value.academy_id, "")) ? " selected" : ""}>-- \u0E44\u0E21\u0E48\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14 (\u0E2D\u0E34\u0E2A\u0E23\u0E30) --</option><!--[-->`);
      ssrRenderList(myAcademies.value, (academy) => {
        _push(`<option${ssrRenderAttr("value", academy.id)}${ssrIncludeBooleanAttr(Array.isArray(form.value.academy_id) ? ssrLooseContain(form.value.academy_id, academy.id) : ssrLooseEqual(form.value.academy_id, academy.id)) ? " selected" : ""}>${ssrInterpolate(academy.name)}</option>`);
      });
      _push(`<!--]--></select><p class="text-xs text-gray-500 mt-1">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E2A\u0E16\u0E32\u0E19\u0E28\u0E36\u0E01\u0E29\u0E32\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14 \u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01 &quot;\u0E44\u0E21\u0E48\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E27\u0E34\u0E0A\u0E32\u0E2D\u0E34\u0E2A\u0E23\u0E30</p></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</label><textarea rows="3" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none">${ssrInterpolate(form.value.description)}</textarea></div></div></div><hr class="border-gray-100 dark:border-gray-700"><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E23\u0E30</label><select class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.value.category) ? ssrLooseContain(form.value.category, "") : ssrLooseEqual(form.value.category, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E23\u0E30</option><!--[-->`);
      ssrRenderList(courseCategories.value, (cat) => {
        _push(`<option${ssrRenderAttr("value", cat.name)}${ssrIncludeBooleanAttr(Array.isArray(form.value.category) ? ssrLooseContain(form.value.category, cat.name) : ssrLooseEqual(form.value.category, cat.name)) ? " selected" : ""}>${ssrInterpolate(cat.name)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</label><select class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.value.level) ? ssrLooseContain(form.value.level, "") : ssrLooseEqual(form.value.level, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</option><!--[-->`);
      ssrRenderList(courseLevelOptions.value, (lvl) => {
        _push(`<option${ssrRenderAttr("value", lvl.level)}${ssrIncludeBooleanAttr(Array.isArray(form.value.level) ? ssrLooseContain(form.value.level, lvl.level) : ssrLooseEqual(form.value.level, lvl.level)) ? " selected" : ""}>${ssrInterpolate(lvl.level)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E2B\u0E19\u0E48\u0E27\u0E22\u0E01\u0E34\u0E15</label><input type="number"${ssrRenderAttr("value", form.value.credit_units)} min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E0A\u0E21./\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C</label><input type="number"${ssrRenderAttr("value", form.value.hours_per_week)} min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"></div></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21</label>`);
      _push(ssrRenderComponent(_component_ClientOnly, null, {}, _parent));
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</label>`);
      _push(ssrRenderComponent(_component_ClientOnly, null, {}, _parent));
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19</label><select class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"><option value="1"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "1") : ssrLooseEqual(form.value.semester, "1")) ? " selected" : ""}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 1</option><option value="2"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "2") : ssrLooseEqual(form.value.semester, "2")) ? " selected" : ""}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 2</option><option value="summer"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "summer") : ssrLooseEqual(form.value.semester, "summer")) ? " selected" : ""}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E24\u0E14\u0E39\u0E23\u0E49\u0E2D\u0E19</option></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</label><input type="text"${ssrRenderAttr("value", form.value.academic_year)} class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"></div></div><hr class="border-gray-100 dark:border-gray-700"><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><div class="space-y-4"><h4 class="font-medium text-gray-900 dark:text-white">\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B</h4><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34</span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(form.value.auto_accept_members) ? ssrLooseContain(form.value.auto_accept_members, null) : form.value.auto_accept_members) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div></label></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><span class="text-sm font-medium text-gray-700 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 (Active)</span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(form.value.status) ? ssrLooseContain(form.value.status, null) : form.value.status) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div></label></div></div><div class="space-y-4"><h4 class="font-medium text-gray-900 dark:text-white">\u0E23\u0E32\u0E04\u0E32\u0E41\u0E25\u0E30\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21</h4><div class="flex items-center justify-between mb-2"><span class="text-sm text-gray-500">\u0E40\u0E1B\u0E34\u0E14\u0E02\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(form.value.saleable) ? ssrLooseContain(form.value.saleable, null) : form.value.saleable) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div></label></div>`);
      if (form.value.saleable) {
        _push(`<div class="space-y-3"><div class="grid grid-cols-2 gap-3"><div><label class="text-xs text-gray-500 block mb-1">\u0E23\u0E32\u0E04\u0E32\u0E1B\u0E01\u0E15\u0E34 (\u0E3F)</label><input type="number"${ssrRenderAttr("value", form.value.price)} class="w-full px-3 py-2 border rounded-lg bg-gray-50"></div><div><label class="text-xs text-gray-500 block mb-1">\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14</label><div class="flex"><input type="number"${ssrRenderAttr("value", form.value.discount)} class="w-full px-3 py-2 border rounded-l-lg bg-gray-50"><select class="border-y border-r rounded-r-lg bg-gray-100 px-2"><option value="fixed"${ssrIncludeBooleanAttr(Array.isArray(form.value.discount_type) ? ssrLooseContain(form.value.discount_type, "fixed") : ssrLooseEqual(form.value.discount_type, "fixed")) ? " selected" : ""}>\u0E3F</option><option value="percent"${ssrIncludeBooleanAttr(Array.isArray(form.value.discount_type) ? ssrLooseContain(form.value.discount_type, "percent") : ssrLooseEqual(form.value.discount_type, "percent")) ? " selected" : ""}>%</option></select></div></div></div><div class="p-3 bg-emerald-50 rounded-lg text-emerald-800 text-sm flex justify-between font-bold"><span>\u0E23\u0E32\u0E04\u0E32\u0E2A\u0E38\u0E17\u0E18\u0E34</span><span>\u0E3F${ssrInterpolate(netPrice.value.toLocaleString())}</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="pt-2"><label class="text-sm text-gray-500 block mb-1">\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 (Point)</label><input type="number"${ssrRenderAttr("value", form.value.tuition_fees)} class="w-full px-4 py-2 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white"></div></div></div><div class="pt-6 flex justify-end"><button type="submit" class="px-8 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-violet-200 dark:shadow-none hover:shadow-xl hover:-translate-y-0.5 transition-all"> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07 </button></div></form></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseSettings.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Settings",
  __ssrInlineRender: true,
  props: {
    course: Object,
    lessons: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object
  },
  setup(__props) {
    const props = __props;
    async function onUpdateCourseHandler(courseData) {
      try {
        const config = { headers: { "Content-Type": "multipart/form-data" } };
        let courseUpdateForm = new FormData();
        courseUpdateForm.append("name", courseData.name);
        courseUpdateForm.append("code", courseData.code);
        courseUpdateForm.append("description", courseData.description);
        courseUpdateForm.append("category", courseData.category);
        courseUpdateForm.append("level", courseData.level);
        courseUpdateForm.append("credit_units", courseData.credit_units);
        courseUpdateForm.append("hours_per_week", courseData.hours_per_week);
        courseUpdateForm.append("start_date", courseData.start_date);
        courseUpdateForm.append("end_date", courseData.end_date);
        courseUpdateForm.append("auto_accept_members", courseData.auto_accept_members);
        courseUpdateForm.append("tuition_fees", courseData.tuition_fees);
        courseUpdateForm.append("saleable", courseData.saleable);
        courseUpdateForm.append("price", courseData.price);
        courseUpdateForm.append("status", courseData.status);
        courseUpdateForm.append("academy_id", courseData.academy_id);
        courseData.cover ? courseUpdateForm.append("cover", courseData.cover) : null;
        courseUpdateForm.append("_method", "put");
        let resultResp = await axios.put(`/courses/${props.course.data.id}`, courseUpdateForm, config);
        if (resultResp.data && resultResp.data.success) {
          router.reload({ only: ["course"] });
        }
      } catch (error) {
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_sfc_main$2, {
        course: __props.course,
        isCourseAdmin: __props.isCourseAdmin,
        activeTab: 8,
        courseMemberOfAuth: __props.courseMemberOfAuth
      }, {
        courseContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (props.isCourseAdmin) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$1, {
                course: props.course.data,
                isCourseAdmin: props.isCourseAdmin,
                onUpdateCourse: (formData) => onUpdateCourseHandler(formData)
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              props.isCourseAdmin ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mt-4"
              }, [
                createVNode(_sfc_main$1, {
                  course: props.course.data,
                  isCourseAdmin: props.isCourseAdmin,
                  onUpdateCourse: (formData) => onUpdateCourseHandler(formData)
                }, null, 8, ["course", "isCourseAdmin", "onUpdateCourse"])
              ])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/Setting/Settings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Settings-D0x3-yJw.mjs.map
