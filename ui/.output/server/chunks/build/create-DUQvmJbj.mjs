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
      title: "",
      content: "",
      type: "article",
      status: "draft",
      is_featured: false
    });
    const contentTypes = [
      { value: "article", label: "\u0E1A\u0E17\u0E04\u0E27\u0E32\u0E21" },
      { value: "announcement", label: "\u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28" },
      { value: "faq", label: "\u0E04\u0E33\u0E16\u0E32\u0E21\u0E17\u0E35\u0E48\u0E1E\u0E1A\u0E1A\u0E48\u0E2D\u0E22" },
      { value: "page", label: "\u0E2B\u0E19\u0E49\u0E32\u0E40\u0E1E\u0E08" }
    ];
    const statuses = [
      { value: "draft", label: "\u0E41\u0E1A\u0E1A\u0E23\u0E48\u0E32\u0E07" },
      { value: "published", label: "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48" },
      { value: "archived", label: "\u0E40\u0E01\u0E47\u0E1A\u0E16\u0E32\u0E27\u0E23" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6 max-w-3xl mx-auto" }, _attrs))}><div class="flex items-center gap-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/content",
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
      _push(`<div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E43\u0E2B\u0E21\u0E48</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E17\u0E04\u0E27\u0E32\u0E21 \u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28 \u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E2D\u0E37\u0E48\u0E19\u0E46</p></div></div>`);
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
      _push(`<form class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.title)} type="text" class="${ssrRenderClass([{ "border-red-500": errors.value.title }, "w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"])}" placeholder="\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32">`);
      if (errors.value.title) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.title)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17 </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(contentTypes, (type) => {
        _push(`<option${ssrRenderAttr("value", type.value)}${ssrIncludeBooleanAttr(Array.isArray(form.type) ? ssrLooseContain(form.type, type.value) : ssrLooseEqual(form.type, type.value)) ? " selected" : ""}>${ssrInterpolate(type.label)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2A\u0E16\u0E32\u0E19\u0E30 </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(statuses, (status) => {
        _push(`<option${ssrRenderAttr("value", status.value)}${ssrIncludeBooleanAttr(Array.isArray(form.status) ? ssrLooseContain(form.status, status.value) : ssrLooseEqual(form.status, status.value)) ? " selected" : ""}>${ssrInterpolate(status.label)}</option>`);
      });
      _push(`<!--]--></select></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32 <span class="text-red-500">*</span></label><textarea rows="10" class="${ssrRenderClass([{ "border-red-500": errors.value.content }, "w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"])}" placeholder="\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32...">${ssrInterpolate(form.content)}</textarea>`);
      if (errors.value.content) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.content)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><label class="flex items-center gap-3 cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(form.is_featured) ? ssrLooseContain(form.is_featured, null) : form.is_featured) ? " checked" : ""} type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="text-gray-700 dark:text-gray-300">\u0E41\u0E2A\u0E14\u0E07\u0E43\u0E19\u0E2B\u0E19\u0E49\u0E32\u0E41\u0E23\u0E01 (Featured)</span></label></div><div class="flex flex-col sm:flex-row gap-3 pt-4"><button type="submit"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 rounded-xl text-white font-medium transition-colors">`);
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
      _push(` ${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32")}</button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/content",
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/content/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=create-DUQvmJbj.mjs.map
