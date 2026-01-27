import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, unref, withCtx, createTextVNode, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderComponent, ssrInterpolate, ssrRenderDynamicModel, ssrIncludeBooleanAttr, ssrLooseContain } from 'vue/server-renderer';
import { _ as _imports_0 } from './virtual_public-CJ1CIvfL.mjs';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, u as useRouter, p as useRoute, d as useAuthStore } from './server.mjs';
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
  __name: "login",
  __ssrInlineRender: true,
  setup(__props) {
    useRouter();
    const route = useRoute();
    useAuthStore();
    const form = ref({
      email: "",
      password: "",
      remember: false
    });
    const isLoading = ref(false);
    const errorMessage = ref("");
    const showPassword = ref(false);
    if (route.query.error === "unauthorized") {
      errorMessage.value = "\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C\u0E40\u0E02\u0E49\u0E32\u0E16\u0E36\u0E07\u0E2B\u0E19\u0E49\u0E32\u0E19\u0E35\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E14\u0E49\u0E27\u0E22\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A";
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-900 via-purple-900 to-indigo-800 p-4" }, _attrs))} data-v-88b82498><div class="absolute inset-0 overflow-hidden" data-v-88b82498><div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-500/30 rounded-full blur-3xl" data-v-88b82498></div><div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-500/30 rounded-full blur-3xl" data-v-88b82498></div></div><div class="relative w-full max-w-md" data-v-88b82498><div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 p-8" data-v-88b82498><div class="text-center mb-8" data-v-88b82498><div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-xl mb-4" data-v-88b82498><img${ssrRenderAttr("src", _imports_0)} alt="Logo" class="w-10 h-10" data-v-88b82498></div><h1 class="text-2xl font-bold text-white mb-2" data-v-88b82498>Nuxnan Admin</h1><p class="text-gray-300 text-sm" data-v-88b82498>\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</p></div>`);
      if (errorMessage.value) {
        _push(`<div class="mb-6 p-4 bg-red-500/20 border border-red-500/30 rounded-xl flex items-start gap-3" data-v-88b82498>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:warning-24-regular",
          class: "w-5 h-5 text-red-400 flex-shrink-0 mt-0.5"
        }, null, _parent));
        _push(`<p class="text-red-200 text-sm" data-v-88b82498>${ssrInterpolate(errorMessage.value)}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<form class="space-y-5" data-v-88b82498><div data-v-88b82498><label for="email" class="block text-sm font-medium text-gray-200 mb-2" data-v-88b82498> \u0E2D\u0E35\u0E40\u0E21\u0E25 </label><div class="relative" data-v-88b82498>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:mail-24-regular",
        class: "absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input id="email"${ssrRenderAttr("value", form.value.email)} type="email" placeholder="admin@nuxnan.com" class="w-full pl-12 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" data-v-88b82498></div></div><div data-v-88b82498><label for="password" class="block text-sm font-medium text-gray-200 mb-2" data-v-88b82498> \u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19 </label><div class="relative" data-v-88b82498>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:lock-closed-24-regular",
        class: "absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input id="password"${ssrRenderDynamicModel(showPassword.value ? "text" : "password", form.value.password, null)}${ssrRenderAttr("type", showPassword.value ? "text" : "password")} placeholder="\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022" class="w-full pl-12 pr-12 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" data-v-88b82498><button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors" data-v-88b82498>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: showPassword.value ? "fluent:eye-off-24-regular" : "fluent:eye-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button></div></div><div class="flex items-center justify-between" data-v-88b82498><label class="flex items-center gap-2 cursor-pointer" data-v-88b82498><input${ssrIncludeBooleanAttr(Array.isArray(form.value.remember) ? ssrLooseContain(form.value.remember, null) : form.value.remember) ? " checked" : ""} type="checkbox" class="w-4 h-4 rounded border-white/20 bg-white/10 text-purple-500 focus:ring-purple-500 focus:ring-offset-0" data-v-88b82498><span class="text-sm text-gray-300" data-v-88b82498>\u0E08\u0E14\u0E08\u0E33\u0E09\u0E31\u0E19</span></label>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/auth/forgot-password",
        class: "text-sm text-purple-400 hover:text-purple-300 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E25\u0E37\u0E21\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19? `);
          } else {
            return [
              createTextVNode(" \u0E25\u0E37\u0E21\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19? ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><button type="submit"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" data-v-88b82498>`);
      if (isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<span data-v-88b82498>${ssrInterpolate(isLoading.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A..." : "\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A")}</span></button></form><div class="mt-6 text-center" data-v-88b82498>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-left-24-regular",
              class: "w-4 h-4"
            }, null, _parent2, _scopeId));
            _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-4 h-4"
              }),
              createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><p class="text-center text-gray-400 text-xs mt-6" data-v-88b82498> \xA9 ${ssrInterpolate((/* @__PURE__ */ new Date()).getFullYear())} Nuxnan. All rights reserved. </p></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const login = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-88b82498"]]);

export { login as default };
//# sourceMappingURL=login-Cb5l9CUL.mjs.map
