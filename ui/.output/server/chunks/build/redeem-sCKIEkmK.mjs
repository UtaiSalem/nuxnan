import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderClass, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, i as useApi, d as useAuthStore } from './server.mjs';
import { u as useToast } from './useToast-BpzfS75l.mjs';
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
  __name: "redeem",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    useToast();
    useAuthStore();
    const method = ref("code");
    const couponCode = ref("");
    const isLoading = ref(false);
    const isScanning = ref(false);
    const result = ref(null);
    ref(null);
    ref(null);
    const formatNumber = (num) => {
      return num.toLocaleString();
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-lg" }, _attrs))} data-v-0c6bd89b>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/coupons",
        class: "inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple dark:hover:text-vikinger-cyan mb-6 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-left-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E04\u0E39\u0E1B\u0E2D\u0E07 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E04\u0E39\u0E1B\u0E2D\u0E07 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(result)) {
        _push(`<div class="vikinger-card !p-0 overflow-hidden" data-v-0c6bd89b><div class="${ssrRenderClass([
          "p-6 text-center",
          unref(result).success ? "bg-gradient-to-r from-green-500 to-emerald-500" : "bg-gradient-to-r from-red-500 to-rose-500"
        ])}" data-v-0c6bd89b><div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center" data-v-0c6bd89b>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(result).success ? "fluent:checkmark-circle-24-filled" : "fluent:dismiss-circle-24-filled",
          class: "w-10 h-10 text-white"
        }, null, _parent));
        _push(`</div><h2 class="text-2xl font-black text-white" data-v-0c6bd89b>${ssrInterpolate(unref(result).success ? "\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!" : "\u0E44\u0E21\u0E48\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08")}</h2><p class="text-white/80 mt-1" data-v-0c6bd89b>${ssrInterpolate(unref(result).message)}</p></div>`);
        if (unref(result).success) {
          _push(`<div class="p-6" data-v-0c6bd89b><div class="grid grid-cols-2 gap-4 mb-6" data-v-0c6bd89b><div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 text-center" data-v-0c6bd89b><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-0c6bd89b>\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</p><p class="text-lg font-bold text-gray-900 dark:text-white mt-1" data-v-0c6bd89b>${ssrInterpolate(unref(result).type === "points" ? "\u{1F3AF} \u0E41\u0E15\u0E49\u0E21" : "\u{1F4B0} \u0E40\u0E07\u0E34\u0E19")}</p></div><div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 text-center" data-v-0c6bd89b><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-0c6bd89b>\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A</p><p class="text-lg font-bold text-green-600 dark:text-green-400 mt-1" data-v-0c6bd89b> +${ssrInterpolate(unref(result).type === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(unref(result).amount))} ${ssrInterpolate(unref(result).type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "")}</p></div></div><div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl p-4 text-center mb-6" data-v-0c6bd89b><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-0c6bd89b> \u0E22\u0E2D\u0E14${ssrInterpolate(unref(result).type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E43\u0E2B\u0E21\u0E48 </p><p class="text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan mt-1" data-v-0c6bd89b>${ssrInterpolate(unref(result).type === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(unref(result).new_balance))} ${ssrInterpolate(unref(result).type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "")}</p></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="p-6 pt-0" data-v-0c6bd89b><button class="w-full py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold hover:opacity-90 transition-all flex items-center justify-center gap-2" data-v-0c6bd89b>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:qr-code-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` \u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2D\u0E35\u0E01 </button>`);
        if (unref(result) && unref(result).success) {
          _push(`<button class="w-full mt-3 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all flex items-center justify-center gap-2" data-v-0c6bd89b>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:arrow-left-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E04\u0E39\u0E1B\u0E2D\u0E07 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<div class="vikinger-card !p-0 overflow-hidden" data-v-0c6bd89b><div class="bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-6" data-v-0c6bd89b><h1 class="text-2xl font-black text-white flex items-center gap-3" data-v-0c6bd89b>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:qr-code-24-filled",
          class: "w-8 h-8"
        }, null, _parent));
        _push(` \u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07 </h1><p class="text-white/80 mt-1" data-v-0c6bd89b>\u0E2A\u0E41\u0E01\u0E19 QR Code \u0E2B\u0E23\u0E37\u0E2D\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07</p></div><div class="p-6" data-v-0c6bd89b><div class="grid grid-cols-2 gap-3 mb-6" data-v-0c6bd89b><button class="${ssrRenderClass([
          "p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2",
          unref(method) === "scan" ? "border-vikinger-purple bg-vikinger-purple/10" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600"
        ])}" data-v-0c6bd89b>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:camera-24-regular",
          class: "w-8 h-8 text-vikinger-purple"
        }, null, _parent));
        _push(`<span class="font-bold text-gray-900 dark:text-white text-sm" data-v-0c6bd89b>\u0E2A\u0E41\u0E01\u0E19 QR Code</span></button><button class="${ssrRenderClass([
          "p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2",
          unref(method) === "code" ? "border-vikinger-cyan bg-vikinger-cyan/10" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600"
        ])}" data-v-0c6bd89b>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:keyboard-24-regular",
          class: "w-8 h-8 text-vikinger-cyan"
        }, null, _parent));
        _push(`<span class="font-bold text-gray-900 dark:text-white text-sm" data-v-0c6bd89b>\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A</span></button></div>`);
        if (unref(method) === "scan") {
          _push(`<div class="mb-6" data-v-0c6bd89b><div class="relative aspect-square bg-gray-900 rounded-xl overflow-hidden" data-v-0c6bd89b><video class="w-full h-full object-cover" autoplay playsinline data-v-0c6bd89b></video><canvas class="hidden" data-v-0c6bd89b></canvas>`);
          if (!unref(isScanning)) {
            _push(`<div class="absolute inset-0 bg-gray-900/80 flex flex-col items-center justify-center cursor-pointer" data-v-0c6bd89b>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:camera-24-regular",
              class: "w-16 h-16 text-gray-400 mb-4"
            }, null, _parent));
            _push(`<p class="text-gray-400" data-v-0c6bd89b>\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E41\u0E01\u0E19</p></div>`);
          } else {
            _push(`<!---->`);
          }
          if (unref(isScanning)) {
            _push(`<div class="absolute inset-x-0 top-0 h-1 bg-vikinger-cyan animate-scan" data-v-0c6bd89b></div>`);
          } else {
            _push(`<!---->`);
          }
          if (unref(isScanning)) {
            _push(`<div class="absolute inset-8 border-2 border-vikinger-cyan rounded-xl pointer-events-none" data-v-0c6bd89b><div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-vikinger-cyan rounded-tl-lg" data-v-0c6bd89b></div><div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-vikinger-cyan rounded-tr-lg" data-v-0c6bd89b></div><div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-vikinger-cyan rounded-bl-lg" data-v-0c6bd89b></div><div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-vikinger-cyan rounded-br-lg" data-v-0c6bd89b></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><button class="${ssrRenderClass([
            "w-full mt-4 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2",
            unref(isScanning) ? "bg-red-500 text-white hover:bg-red-600" : "bg-vikinger-purple text-white hover:opacity-90"
          ])}" data-v-0c6bd89b>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: unref(isScanning) ? "fluent:stop-24-regular" : "fluent:camera-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(isScanning) ? "\u0E2B\u0E22\u0E38\u0E14\u0E2A\u0E41\u0E01\u0E19" : "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E41\u0E01\u0E19")}</button></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(method) === "code") {
          _push(`<div class="mb-6" data-v-0c6bd89b><label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block" data-v-0c6bd89b> \u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07 (8 \u0E2B\u0E25\u0E31\u0E01) </label><div class="flex gap-2" data-v-0c6bd89b><input${ssrRenderAttr("value", unref(couponCode))} type="text" inputmode="numeric" pattern="[0-9]*" maxlength="8" placeholder="00000000" class="flex-1 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-lg font-mono font-bold tracking-widest focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""} data-v-0c6bd89b><button class="px-4 py-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all" title="\u0E27\u0E32\u0E07\u0E08\u0E32\u0E01\u0E04\u0E25\u0E34\u0E1B\u0E1A\u0E2D\u0E23\u0E4C\u0E14" data-v-0c6bd89b>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clipboard-paste-24-regular",
            class: "w-6 h-6"
          }, null, _parent));
          _push(`</button></div><p class="text-xs text-gray-500 dark:text-gray-400 mt-2" data-v-0c6bd89b> \u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02 8 \u0E2B\u0E25\u0E31\u0E01\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E08\u0E32\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07 </p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button${ssrIncludeBooleanAttr(!unref(couponCode) || unref(isLoading)) ? " disabled" : ""} class="${ssrRenderClass([
          "w-full py-4 rounded-xl font-bold text-lg transition-all flex items-center justify-center gap-2",
          unref(couponCode) && !unref(isLoading) ? "bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white shadow-lg hover:opacity-90" : "bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed"
        ])}" data-v-0c6bd89b>`);
        if (unref(isLoading)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:spinner-ios-20-regular",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:gift-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(` ${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A..." : "\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07")}</button><div class="mt-6 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4" data-v-0c6bd89b><div class="flex gap-3" data-v-0c6bd89b>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-24-regular",
          class: "w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5"
        }, null, _parent));
        _push(`<div class="text-xs text-gray-500 dark:text-gray-400 space-y-1" data-v-0c6bd89b><p data-v-0c6bd89b>\u2022 \u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49\u0E40\u0E1E\u0E35\u0E22\u0E07 1 \u0E04\u0E23\u0E31\u0E49\u0E07</p><p data-v-0c6bd89b>\u2022 \u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E07\u0E44\u0E14\u0E49</p><p data-v-0c6bd89b>\u2022 \u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38\u0E01\u0E48\u0E2D\u0E19\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</p></div></div></div></div></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/coupons/redeem.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const redeem = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-0c6bd89b"]]);

export { redeem as default };
//# sourceMappingURL=redeem-sCKIEkmK.mjs.map
