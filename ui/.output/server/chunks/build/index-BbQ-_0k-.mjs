import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { b as useRuntimeConfig } from './server.mjs';
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
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    const coupons = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref("");
    const selectedStatus = ref("all");
    const statuses = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "active", label: "\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49" },
      { value: "expired", label: "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38" },
      { value: "disabled", label: "\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19" }
    ];
    const getStatusBadge = (status) => {
      const badges = {
        active: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        expired: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
        disabled: "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
      };
      return badges[status] || badges.disabled;
    };
    const getStatusLabel = (status) => {
      const labels = { active: "\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49", expired: "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38", disabled: "\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19" };
      return labels[status] || "\u0E44\u0E21\u0E48\u0E17\u0E23\u0E32\u0E1A";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E04\u0E39\u0E1B\u0E2D\u0E07</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E25\u0E30\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E42\u0E04\u0E49\u0E14\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14</p></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/coupons/create",
        class: "inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:add-24-regular",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex flex-col sm:flex-row gap-4"><div class="flex-1 relative">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E42\u0E04\u0E49\u0E14\u0E04\u0E39\u0E1B\u0E2D\u0E07..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"></div><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(statuses, (status) => {
        _push(`<option${ssrRenderAttr("value", status.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedStatus.value) ? ssrLooseContain(selectedStatus.value, status.value) : ssrLooseEqual(selectedStatus.value, status.value)) ? " selected" : ""}>${ssrInterpolate(status.label)}</option>`);
      });
      _push(`<!--]--></select></div></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">`);
      if (isLoading.value) {
        _push(`<div class="col-span-full p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else {
        _push(`<!--[--><!--[-->`);
        ssrRenderList(coupons.value, (coupon) => {
          _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"><div class="flex items-start justify-between mb-4"><div><div class="flex items-center gap-2"><span class="font-mono font-bold text-lg text-indigo-600 dark:text-indigo-400">${ssrInterpolate(coupon.code)}</span><span class="${ssrRenderClass([getStatusBadge(coupon.status), "px-2 py-0.5 rounded-full text-xs font-medium"])}">${ssrInterpolate(getStatusLabel(coupon.status))}</span></div><p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">`);
          if (coupon.type === "percentage") {
            _push(`<!--[-->${ssrInterpolate(coupon.discount)}%<!--]-->`);
          } else {
            _push(`<!--[-->\u0E3F${ssrInterpolate(coupon.discount)}<!--]-->`);
          }
          _push(`<span class="text-sm font-normal text-gray-500 ml-1">\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14</span></p></div><div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:ticket-diagonal-24-regular",
            class: "w-6 h-6 text-indigo-600"
          }, null, _parent));
          _push(`</div></div><div class="space-y-2 text-sm"><div class="flex items-center justify-between"><span class="text-gray-500">\u0E01\u0E32\u0E23\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</span><span class="text-gray-800 dark:text-white font-medium">${ssrInterpolate(coupon.usedCount)} / ${ssrInterpolate(coupon.usageLimit || "\u221E")}</span></div><div class="flex items-center justify-between"><span class="text-gray-500">\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38</span><span class="text-gray-800 dark:text-white font-medium">${ssrInterpolate(coupon.expiresAt)}</span></div><div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mt-2"><div class="bg-indigo-600 h-2 rounded-full" style="${ssrRenderStyle({ width: coupon.usageLimit ? `${coupon.usedCount / coupon.usageLimit * 100}%` : "50%" })}"></div></div></div><div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/nuxnan-admin/coupons/${coupon.id}/edit`,
            class: "p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:edit-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-regular",
                    class: "w-5 h-5"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`<button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></div>`);
        });
        _push(`<!--]-->`);
        if (coupons.value.length === 0) {
          _push(`<div class="col-span-full p-8 text-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:ticket-diagonal-24-regular",
            class: "w-12 h-12 text-gray-300 mx-auto"
          }, null, _parent));
          _push(`<p class="text-gray-500 mt-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/coupons/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-BbQ-_0k-.mjs.map
