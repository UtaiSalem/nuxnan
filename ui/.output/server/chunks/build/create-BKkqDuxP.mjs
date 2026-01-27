import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderClass, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { i as useApi, d as useAuthStore, u as useRouter } from './server.mjs';
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
  __name: "create",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    useToast();
    const authStore = useAuthStore();
    useRouter();
    const type = ref("points");
    const amount = ref(100);
    const quantity = ref(1);
    const description = ref("");
    const expiresInDays = ref(30);
    const isLoading = ref(false);
    const createdCoupons = ref([]);
    const userPoints = computed(() => authStore.points || 0);
    const userWallet = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.wallet) || 0;
    });
    const totalCost = computed(() => amount.value * quantity.value);
    const remainingPoints = computed(() => userPoints.value - totalCost.value);
    const remainingWallet = computed(() => userWallet.value - totalCost.value);
    const minAmount = computed(() => type.value === "points" ? 10 : 10);
    const maxAmount = computed(() => type.value === "points" ? userPoints.value : userWallet.value);
    const maxQuantity = computed(() => {
      const max = Math.floor((type.value === "points" ? userPoints.value : userWallet.value) / amount.value);
      return max > 0 ? max : 0;
    });
    const isValid = computed(() => {
      return amount.value >= minAmount.value && amount.value <= maxAmount.value && quantity.value >= 1 && quantity.value <= maxQuantity.value && totalCost.value <= (type.value === "points" ? userPoints.value : userWallet.value) && (!expiresInDays.value || expiresInDays.value >= 1 && expiresInDays.value <= 365);
    });
    const quickAmounts = computed(() => {
      if (type.value === "points") {
        return [100, 500, 1e3, 5e3, 1e4];
      }
      return [10, 50, 100, 500, 1e3];
    });
    ref(false);
    const formatNumber = (num) => {
      return num.toLocaleString();
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-2xl" }, _attrs))}>`);
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
      if (unref(createdCoupons).length > 0) {
        _push(`<div class="vikinger-card !p-0 overflow-hidden"><div class="bg-gradient-to-r from-green-500 to-emerald-500 p-6 text-center"><div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-10 h-10 text-white"
        }, null, _parent));
        _push(`</div><h2 class="text-2xl font-black text-white">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!</h2><p class="text-white/80 mt-1">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07 ${ssrInterpolate(unref(createdCoupons).length)} \u0E43\u0E1A\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27</p></div><div class="p-6"><div class="space-y-4 mb-6 max-h-96 overflow-y-auto"><!--[-->`);
        ssrRenderList(unref(createdCoupons), (coupon, index) => {
          _push(`<div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4"><div class="flex items-start gap-4">`);
          if (coupon.qr_code_url) {
            _push(`<div class="flex-shrink-0"><img${ssrRenderAttr("src", coupon.qr_code_url)} alt="QR Code" class="w-20 h-20 rounded-lg border-2 border-gray-200 dark:border-gray-700"></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex-1 min-w-0"><div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold px-2 py-1 rounded-full bg-vikinger-purple/10 text-vikinger-purple dark:bg-vikinger-purple/20 dark:text-vikinger-cyan"> \u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E35\u0E48 ${ssrInterpolate(index + 1)}</span><span class="text-xs font-bold px-2 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">${ssrInterpolate(coupon.coupon_type === "points" ? "\u{1F3AF} \u0E41\u0E15\u0E49\u0E21" : "\u{1F4B0} \u0E40\u0E07\u0E34\u0E19")}</span></div><div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2 mb-2"><p class="text-xs text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07</p><div class="flex items-center gap-2"><code class="flex-1 text-lg font-mono font-bold text-gray-900 dark:text-white tracking-wider truncate">${ssrInterpolate(coupon.coupon_code)}</code><button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-vikinger-purple text-white hover:opacity-90 transition-all flex-shrink-0">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:copy-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button></div></div><p class="text-sm font-bold text-gray-900 dark:text-white"> \u0E21\u0E39\u0E25\u0E04\u0E48\u0E32: ${ssrInterpolate(coupon.coupon_type === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(coupon.amount))} ${ssrInterpolate(coupon.coupon_type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "")}</p></div></div></div>`);
        });
        _push(`<!--]--></div><div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl p-4 mb-6"><div class="grid grid-cols-2 gap-4 text-center"><div><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E39\u0E1B\u0E2D\u0E07</p><p class="text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan">${ssrInterpolate(unref(createdCoupons).length)} \u0E43\u0E1A</p></div><div><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E21\u0E39\u0E25\u0E04\u0E48\u0E32\u0E23\u0E27\u0E21</p><p class="text-2xl font-black text-vikinger-purple dark:text-vikinger-cyan">${ssrInterpolate(unref(createdCoupons)[0].coupon_type === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(unref(createdCoupons).length * unref(createdCoupons)[0].amount))} ${ssrInterpolate(unref(createdCoupons)[0].coupon_type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "")}</p></div></div></div><div class="flex gap-3"><button class="flex-1 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-white rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center justify-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/earn/coupons",
          class: "flex-1 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold hover:opacity-90 transition-all flex items-center justify-center gap-2"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:list-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E14\u0E39\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:list-24-regular",
                  class: "w-5 h-5"
                }),
                createTextVNode(" \u0E14\u0E39\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div></div>`);
      } else {
        _push(`<div class="vikinger-card !p-0 overflow-hidden"><div class="bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-6"><h1 class="text-2xl font-black text-white flex items-center gap-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:ticket-diagonal-24-filled",
          class: "w-8 h-8"
        }, null, _parent));
        _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07 </h1><p class="text-white/80 mt-1">\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E07\u0E34\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E08\u0E01\u0E43\u0E2B\u0E49\u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19</p></div><div class="p-6"><div class="mb-6"><p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E04\u0E39\u0E1B\u0E2D\u0E07</p><div class="grid grid-cols-2 gap-3"><button class="${ssrRenderClass([
          "p-4 rounded-xl border-2 transition-all text-left",
          unref(type) === "points" ? "border-amber-500 bg-amber-50 dark:bg-amber-900/20" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600"
        ])}"><div class="flex items-center gap-3 mb-2"><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><span class="font-bold text-gray-900 dark:text-white">\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E15\u0E49\u0E21</span></div><p class="text-xs text-gray-500 dark:text-gray-400"> \u0E22\u0E2D\u0E14\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: <span class="font-bold text-amber-600 dark:text-amber-400">${ssrInterpolate(formatNumber(unref(userPoints)))} \u0E41\u0E15\u0E49\u0E21</span></p></button><button class="${ssrRenderClass([
          "p-4 rounded-xl border-2 transition-all text-left",
          unref(type) === "wallet" ? "border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600"
        ])}"><div class="flex items-center gap-3 mb-2"><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:money-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><span class="font-bold text-gray-900 dark:text-white">\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E40\u0E07\u0E34\u0E19</span></div><p class="text-xs text-gray-500 dark:text-gray-400"> \u0E22\u0E2D\u0E14\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: <span class="font-bold text-emerald-600 dark:text-emerald-400">\u0E3F${ssrInterpolate(formatNumber(unref(userWallet)))}</span></p></button></div></div><div class="mb-6"><label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block"> \u0E08\u0E33\u0E19\u0E27\u0E19${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19 (\u0E1A\u0E32\u0E17)")}</label><div class="relative"><input${ssrRenderAttr("value", unref(amount))} type="number"${ssrRenderAttr("min", unref(minAmount))}${ssrRenderAttr("max", unref(maxAmount))}${ssrRenderAttr("step", unref(type) === "wallet" ? "0.01" : "1")} class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xl font-bold focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E1A\u0E32\u0E17")}</span></div><div class="flex flex-wrap gap-2 mt-3"><!--[-->`);
        ssrRenderList(unref(quickAmounts), (quickAmount) => {
          _push(`<button${ssrIncludeBooleanAttr(quickAmount > unref(maxAmount)) ? " disabled" : ""} class="${ssrRenderClass([
            "px-3 py-1.5 rounded-lg text-xs font-bold transition-all",
            unref(amount) === quickAmount ? "bg-vikinger-purple text-white" : quickAmount > unref(maxAmount) ? "bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
          ])}">${ssrInterpolate(unref(type) === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(quickAmount))}</button>`);
        });
        _push(`<!--]--></div>`);
        if (unref(amount) > unref(maxAmount)) {
          _push(`<p class="text-red-500 text-xs mt-2"> \u0E22\u0E2D\u0E14${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D </p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="mb-6"><label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block"> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E39\u0E1B\u0E2D\u0E07 </label><div class="relative"><input${ssrRenderAttr("value", unref(quantity))} type="number"${ssrRenderAttr("min", 1)}${ssrRenderAttr("max", unref(maxQuantity))} class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xl font-bold focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium"> \u0E43\u0E1A </span></div><div class="flex flex-wrap gap-2 mt-3"><!--[-->`);
        ssrRenderList([1, 5, 10, 20, 50], (q) => {
          _push(`<button${ssrIncludeBooleanAttr(q > unref(maxQuantity)) ? " disabled" : ""} class="${ssrRenderClass([
            "px-3 py-1.5 rounded-lg text-xs font-bold transition-all",
            unref(quantity) === q ? "bg-vikinger-cyan text-white" : q > unref(maxQuantity) ? "bg-gray-100 dark:bg-gray-800 text-gray-400 cursor-not-allowed" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
          ])}">${ssrInterpolate(q)} \u0E43\u0E1A </button>`);
        });
        _push(`<!--]--></div>`);
        if (unref(quantity) > unref(maxQuantity)) {
          _push(`<p class="text-red-500 text-xs mt-2"> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E40\u0E01\u0E34\u0E19\u0E01\u0E27\u0E48\u0E32\u0E17\u0E35\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E44\u0E14\u0E49 (\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 ${ssrInterpolate(unref(maxQuantity))} \u0E43\u0E1A) </p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 border border-vikinger-purple/20 dark:border-vikinger-cyan/20 rounded-xl p-4 mb-6"><h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calculator-24-regular",
          class: "w-5 h-5 text-vikinger-purple dark:text-vikinger-cyan"
        }, null, _parent));
        _push(` \u0E04\u0E33\u0E19\u0E27\u0E13\u0E22\u0E2D\u0E14\u0E04\u0E48\u0E32\u0E43\u0E0A\u0E49\u0E08\u0E48\u0E32\u0E22 </h3><div class="grid grid-cols-2 gap-4"><div class="bg-white dark:bg-gray-800 rounded-lg p-3"><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">\u0E21\u0E39\u0E25\u0E04\u0E48\u0E32\u0E23\u0E27\u0E21</p><p class="text-xl font-black text-vikinger-purple dark:text-vikinger-cyan">${ssrInterpolate(unref(type) === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(unref(totalCost)))} ${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "")}</p></div><div class="bg-white dark:bg-gray-800 rounded-lg p-3"><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">\u0E22\u0E2D\u0E14\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D\u0E2B\u0E25\u0E31\u0E07\u0E2A\u0E23\u0E49\u0E32\u0E07</p><p class="${ssrRenderClass([[
          unref(type) === "points" ? "text-amber-600 dark:text-amber-400" : "text-emerald-600 dark:text-emerald-400"
        ], "text-xl font-black"])}">${ssrInterpolate(unref(type) === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(unref(type) === "points" ? unref(remainingPoints) : unref(remainingWallet)))} ${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "")}</p></div></div></div><div class="mb-6"><label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block"> \u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38 (\u0E27\u0E31\u0E19) - \u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A </label><div class="flex gap-2"><!--[-->`);
        ssrRenderList([null, 7, 30, 90, 365], (days) => {
          _push(`<button class="${ssrRenderClass([
            "px-4 py-2 rounded-lg text-sm font-bold transition-all",
            unref(expiresInDays) === days ? "bg-vikinger-cyan text-white" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
          ])}">${ssrInterpolate(days ? `${days} \u0E27\u0E31\u0E19` : "\u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14")}</button>`);
        });
        _push(`<!--]--></div></div><div class="mb-6"><label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block"> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="3" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 \u0E40\u0E0A\u0E48\u0E19 \u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E43\u0E04\u0E23 \u0E2B\u0E23\u0E37\u0E2D\u0E42\u0E2D\u0E01\u0E32\u0E2A\u0E1E\u0E34\u0E40\u0E28\u0E29..." class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent resize-none">${ssrInterpolate(unref(description))}</textarea></div><div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6"><div class="flex gap-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-24-regular",
          class: "w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5"
        }, null, _parent));
        _push(`<div class="text-sm text-blue-700 dark:text-blue-300"><p class="font-semibold mb-1">\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38</p><ul class="list-disc list-inside text-xs space-y-1 text-blue-600 dark:text-blue-400"><li>${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E08\u0E30\u0E16\u0E39\u0E01\u0E2B\u0E31\u0E01\u0E08\u0E32\u0E01\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E17\u0E31\u0E19\u0E17\u0E35 (\u0E21\u0E39\u0E25\u0E04\u0E48\u0E32\u0E23\u0E27\u0E21 ${ssrInterpolate(unref(type) === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(formatNumber(unref(totalCost)))}${ssrInterpolate(unref(type) === "points" ? " \u0E41\u0E15\u0E49\u0E21" : "")})</li><li>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49\u0E42\u0E14\u0E22\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E1C\u0E48\u0E32\u0E19 QR Code \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A</li><li>\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E07\u0E44\u0E14\u0E49</li><li>\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E44\u0E14\u0E49\u0E01\u0E48\u0E2D\u0E19\u0E16\u0E39\u0E01\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 (${ssrInterpolate(unref(type) === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E08\u0E30\u0E16\u0E39\u0E01\u0E04\u0E37\u0E19)</li></ul></div></div></div><button${ssrIncludeBooleanAttr(!unref(isValid) || unref(isLoading)) ? " disabled" : ""} class="${ssrRenderClass([
          "w-full py-4 rounded-xl font-bold text-lg transition-all flex items-center justify-center gap-2",
          unref(isValid) && !unref(isLoading) ? "bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white shadow-lg hover:opacity-90" : "bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed"
        ])}">`);
        if (unref(isLoading)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:spinner-ios-20-regular",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:ticket-diagonal-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(` ${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E23\u0E49\u0E32\u0E07..." : `\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07 ${unref(quantity)} \u0E43\u0E1A`)}</button></div></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/coupons/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=create-BKkqDuxP.mjs.map
