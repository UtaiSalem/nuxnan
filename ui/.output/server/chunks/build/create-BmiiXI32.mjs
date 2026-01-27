import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, reactive, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { b as useRuntimeConfig, u as useRouter } from './server.mjs';
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
    const config = useRuntimeConfig();
    config.public.apiBase;
    useRouter();
    const isSubmitting = ref(false);
    const errors = ref({});
    const successMessage = ref("");
    const form = reactive({
      code: "",
      name: "",
      description: "",
      discount_type: "percentage",
      discount_value: 0,
      min_purchase: 0,
      max_discount: 0,
      usage_limit: null,
      per_user_limit: 1,
      start_date: "",
      end_date: "",
      is_active: true,
      applicable_to: "all"
    });
    const discountTypes = [
      { value: "percentage", label: "\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C (%)" },
      { value: "fixed", label: "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E1A\u0E32\u0E17)" }
    ];
    const applicableOptions = [
      { value: "all", label: "\u0E17\u0E38\u0E01\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32/\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23" },
      { value: "courses", label: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19" },
      { value: "products", label: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6 max-w-2xl mx-auto" }, _attrs))}><div class="flex items-center gap-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/coupons",
        class: "p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-left-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-5 h-5"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14</p></div></div>`);
      if (successMessage.value) {
        _push(`<div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl"><div class="flex items-center gap-2 text-green-700 dark:text-green-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(successMessage.value)}</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (errors.value.general) {
        _push(`<div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl"><div class="flex items-center gap-2 text-red-700 dark:text-red-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(errors.value.general)}</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<form class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07 <span class="text-red-500">*</span></label><div class="flex gap-2"><input${ssrRenderAttr("value", form.code)} type="text" class="${ssrRenderClass([{ "border-red-500": errors.value.code }, "flex-1 px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent uppercase"])}" placeholder="SAVE20"><button type="button" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-sync-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button></div>`);
      if (errors.value.code) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.code)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E0A\u0E37\u0E48\u0E2D\u0E04\u0E39\u0E1B\u0E2D\u0E07 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.name)} type="text" class="${ssrRenderClass([{ "border-red-500": errors.value.name }, "w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"])}" placeholder="\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14 20% \u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E2B\u0E21\u0E48">`);
      if (errors.value.name) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.name)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 </label><textarea rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none" placeholder="\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07">${ssrInterpolate(form.description)}</textarea></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14 </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(discountTypes, (type) => {
        _push(`<option${ssrRenderAttr("value", type.value)}${ssrIncludeBooleanAttr(Array.isArray(form.discount_type) ? ssrLooseContain(form.discount_type, type.value) : ssrLooseEqual(form.discount_type, type.value)) ? " selected" : ""}>${ssrInterpolate(type.label)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E21\u0E39\u0E25\u0E04\u0E48\u0E32\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14 <span class="text-red-500">*</span></label><div class="relative"><input${ssrRenderAttr("value", form.discount_value)} type="number" min="0" class="${ssrRenderClass([{ "border-red-500": errors.value.discount_value }, "w-full px-4 py-2.5 pr-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"])}"><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">${ssrInterpolate(form.discount_type === "percentage" ? "%" : "\u0E3F")}</span></div>`);
      if (errors.value.discount_value) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.discount_value)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E22\u0E2D\u0E14\u0E2A\u0E31\u0E48\u0E07\u0E0B\u0E37\u0E49\u0E2D\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 (\u0E1A\u0E32\u0E17) </label><input${ssrRenderAttr("value", form.min_purchase)} type="number" min="0" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="0"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 (\u0E1A\u0E32\u0E17) </label><input${ssrRenderAttr("value", form.max_discount)} type="number" min="0" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="0 = \u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </label><input${ssrRenderAttr("value", form.usage_limit)} type="number" min="0" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="\u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E08\u0E33\u0E01\u0E31\u0E14\u0E15\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </label><input${ssrRenderAttr("value", form.per_user_limit)} type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></div></div><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.start_date)} type="datetime-local" class="${ssrRenderClass([{ "border-red-500": errors.value.start_date }, "w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"])}">`);
      if (errors.value.start_date) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.start_date)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.end_date)} type="datetime-local" class="${ssrRenderClass([{ "border-red-500": errors.value.end_date }, "w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"])}">`);
      if (errors.value.end_date) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.end_date)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E43\u0E0A\u0E49\u0E44\u0E14\u0E49\u0E01\u0E31\u0E1A </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(applicableOptions, (option) => {
        _push(`<option${ssrRenderAttr("value", option.value)}${ssrIncludeBooleanAttr(Array.isArray(form.applicable_to) ? ssrLooseContain(form.applicable_to, option.value) : ssrLooseEqual(form.applicable_to, option.value)) ? " selected" : ""}>${ssrInterpolate(option.label)}</option>`);
      });
      _push(`<!--]--></select></div><div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><label class="flex items-center gap-3 cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(form.is_active) ? ssrLooseContain(form.is_active, null) : form.is_active) ? " checked" : ""} type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="text-gray-700 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E17\u0E31\u0E19\u0E17\u0E35</span></label></div><div class="flex flex-col sm:flex-row gap-3 pt-4"><button type="submit"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 rounded-xl text-white font-medium transition-colors">`);
      if (isSubmitting.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:save-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` ${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07")}</button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/coupons",
        class: "flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 font-medium transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:dismiss-24-regular",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></form></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/coupons/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=create-BmiiXI32.mjs.map
