import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, watch, mergeProps, unref, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderClass, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { C as CouponCard } from './CouponCard-_T2CzrsF.mjs';
import QRCodeVue3 from 'qrcode-vue3';
import { i as useApi, d as useAuthStore } from './server.mjs';
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
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const api = useApi();
    const toast = useToast();
    useAuthStore();
    const coupons = ref([]);
    const statistics = ref(null);
    const isLoading = ref(true);
    const isDownloading = ref(false);
    const activeFilter = ref("all");
    const activeType = ref("all");
    const activeView = ref("card");
    const showRedeemModal = ref(false);
    const redeemCode = ref("");
    const isRedeeming = ref(false);
    const showQrModal = ref(false);
    const selectedCouponForQr = ref(null);
    const fetchCoupons = async () => {
      isLoading.value = true;
      try {
        const params = {};
        if (activeFilter.value !== "all") params.status = activeFilter.value;
        if (activeType.value !== "all") params.type = activeType.value;
        const response = await api.get("/api/coupons", { params });
        if (response.success) {
          coupons.value = response.data.coupons;
        }
      } catch (error) {
        console.error("Error fetching coupons:", error);
        toast.error("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E44\u0E14\u0E49");
      } finally {
        isLoading.value = false;
      }
    };
    const fetchStatistics = async () => {
      try {
        const response = await api.get("/api/coupons/statistics");
        if (response.success) {
          statistics.value = {
            total: response.data.total_coupons || 0,
            active: response.data.active_coupons || 0,
            total_points: response.data.total_points_in_coupons || 0,
            total_wallet: response.data.total_wallet_in_coupons || 0
          };
        }
      } catch (error) {
        console.error("Error fetching statistics:", error);
      }
    };
    const handleCouponCancelled = (couponId) => {
      const index = coupons.value.findIndex((c) => c.id === couponId);
      if (index !== -1) {
        coupons.value[index].status = "cancelled";
      }
      fetchStatistics();
    };
    watch([activeFilter, activeType], () => {
      fetchCoupons();
    });
    const formatNumber = (num) => {
      if (num >= 1e6) return (num / 1e6).toFixed(1).replace(/\.0$/, "") + "M";
      if (num >= 1e3) return (num / 1e3).toFixed(1).replace(/\.0$/, "") + "K";
      return num.toLocaleString();
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-6xl" }, _attrs))}><div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6"><div><h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3"><div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:ticket-diagonal-24-filled",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div> \u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 </h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E15\u0E49\u0E21\u0E41\u0E25\u0E30\u0E40\u0E07\u0E34\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div><div class="flex flex-wrap gap-2 md:gap-3"><button class="p-2.5 md:px-4 md:py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center gap-2 font-bold">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:qr-code-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span class="hidden md:inline">\u0E41\u0E25\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07</span></button><button${ssrIncludeBooleanAttr(unref(isDownloading) || unref(coupons).length === 0) ? " disabled" : ""} class="${ssrRenderClass([
        "p-2.5 md:px-4 md:py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition-all flex items-center gap-2 font-bold",
        unref(isDownloading) || unref(coupons).length === 0 ? "opacity-50 cursor-not-allowed" : ""
      ])}">`);
      if (unref(isDownloading)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:document-pdf-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(`<span class="hidden md:inline">${ssrInterpolate(unref(isDownloading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14..." : "\u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14 PDF")}</span></button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/coupons/create",
        class: "p-2.5 md:px-4 md:py-2.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:opacity-90 transition-all flex items-center gap-2 font-bold shadow-lg"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(`<span class="hidden md:inline"${_scopeId}>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07</span>`);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:add-24-regular",
                class: "w-5 h-5"
              }),
              createVNode("span", { class: "hidden md:inline" }, "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
      if (unref(statistics)) {
        _push(`<div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-4 mb-6"><div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3"><div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-md flex-shrink-0">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:ticket-diagonal-24-filled",
          class: "w-5 h-5 sm:w-6 sm:h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="min-w-0"><p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">${ssrInterpolate(unref(statistics).total || 0)}</p><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div><div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3"><div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-md flex-shrink-0">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-5 h-5 sm:w-6 sm:h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="min-w-0"><p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">${ssrInterpolate(unref(statistics).active || 0)}</p><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49</p></div></div><div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3"><div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-md flex-shrink-0">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: "w-5 h-5 sm:w-6 sm:h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="min-w-0"><p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">${ssrInterpolate(formatNumber(unref(statistics).total_points || 0))}</p><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">\u0E41\u0E15\u0E49\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div><div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3"><div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-md flex-shrink-0">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:money-24-filled",
          class: "w-5 h-5 sm:w-6 sm:h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="min-w-0"><p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">\u0E3F${ssrInterpolate(formatNumber(unref(statistics).total_wallet || 0))}</p><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="vikinger-card !p-3 sm:!p-4 mb-6"><div class="flex flex-col gap-3 sm:gap-4"><div><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">\u0E2A\u0E16\u0E32\u0E19\u0E30</p><div class="flex gap-1.5 sm:gap-2 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-hide"><!--[-->`);
      ssrRenderList([
        { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "fluent:apps-24-regular" },
        { key: "active", label: "\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49", icon: "fluent:checkmark-circle-24-regular" },
        { key: "redeemed", label: "\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27", icon: "fluent:gift-24-regular" },
        { key: "expired", label: "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38", icon: "fluent:clock-24-regular" },
        { key: "cancelled", label: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01", icon: "fluent:dismiss-circle-24-regular" }
      ], (status) => {
        _push(`<button class="${ssrRenderClass([
          "px-2.5 py-1.5 sm:px-3 rounded-lg text-[10px] sm:text-xs font-bold flex items-center gap-1 sm:gap-1.5 transition-all whitespace-nowrap flex-shrink-0",
          unref(activeFilter) === status.key ? "bg-vikinger-purple text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
        ])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: status.icon,
          class: "w-3.5 h-3.5 sm:w-4 sm:h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(status.label)}</button>`);
      });
      _push(`<!--]--></div></div><div class="flex flex-wrap items-end gap-3 sm:gap-4"><div class="flex-1 min-w-[120px]"><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</p><div class="flex gap-1.5 sm:gap-2"><!--[-->`);
      ssrRenderList([
        { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
        { key: "points", label: "\u{1F3AF} \u0E41\u0E15\u0E49\u0E21" },
        { key: "wallet", label: "\u{1F4B0} \u0E40\u0E07\u0E34\u0E19" }
      ], (type) => {
        _push(`<button class="${ssrRenderClass([
          "px-2.5 py-1.5 sm:px-3 rounded-lg text-[10px] sm:text-xs font-bold transition-all whitespace-nowrap",
          unref(activeType) === type.key ? "bg-vikinger-cyan text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
        ])}">${ssrInterpolate(type.label)}</button>`);
      });
      _push(`<!--]--></div></div><div><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">\u0E21\u0E38\u0E21\u0E21\u0E2D\u0E07</p><div class="flex gap-1.5 sm:gap-2"><button class="${ssrRenderClass([
        "p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-[10px] sm:text-xs font-bold flex items-center gap-1 sm:gap-1.5 transition-all",
        unref(activeView) === "card" ? "bg-vikinger-purple text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:grid-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span class="hidden sm:inline">\u0E01\u0E32\u0E23\u0E4C\u0E14</span></button><button class="${ssrRenderClass([
        "p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-[10px] sm:text-xs font-bold flex items-center gap-1 sm:gap-1.5 transition-all",
        unref(activeView) === "table" ? "bg-vikinger-purple text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:table-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span class="hidden sm:inline">\u0E15\u0E32\u0E23\u0E32\u0E07</span></button></div></div></div></div></div>`);
      if (unref(isLoading)) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="vikinger-card animate-pulse"><div class="h-40 bg-gray-200 dark:bg-gray-700 rounded-xl mb-4"></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(coupons).length > 0 && unref(activeView) === "card") {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(unref(coupons), (coupon) => {
          _push(ssrRenderComponent(CouponCard, {
            key: coupon.id,
            coupon,
            onCancelled: handleCouponCancelled
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else if (unref(coupons).length > 0 && unref(activeView) === "table") {
        _push(`<div class="vikinger-card overflow-hidden"><div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-gray-200 dark:border-gray-700"><th class="text-center px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">\u0E25\u0E33\u0E14\u0E31\u0E1A</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E08\u0E33\u0E19\u0E27\u0E19</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">QR Code</th><th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</th></tr></thead><tbody><!--[-->`);
        ssrRenderList(unref(coupons), (coupon, index) => {
          _push(`<tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"><td class="px-4 py-3 text-center"><span class="text-sm font-bold text-gray-900 dark:text-white">${ssrInterpolate(index + 1)}</span></td><td class="px-4 py-3"><span class="font-mono text-sm font-bold text-gray-900 dark:text-white">${ssrInterpolate(coupon.coupon_code)}</span></td><td class="px-4 py-3"><span class="${ssrRenderClass([
            "inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold",
            coupon.coupon_type === "points" ? "bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400" : "bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400"
          ])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: coupon.coupon_type === "points" ? "fluent:star-24-filled" : "fluent:money-24-filled",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(` ${ssrInterpolate(coupon.coupon_type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}</span></td><td class="px-4 py-3"><span class="text-sm font-bold text-gray-900 dark:text-white">${ssrInterpolate(coupon.coupon_type === "wallet" ? "\u0E3F" : "")}${ssrInterpolate(coupon.amount.toLocaleString())}</span></td><td class="px-4 py-3"><span class="${ssrRenderClass([
            "inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold",
            coupon.status === "active" ? "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400" : coupon.status === "redeemed" ? "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400" : coupon.status === "expired" ? "bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400" : coupon.status === "cancelled" ? "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400"
          ])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: coupon.status === "active" ? "fluent:checkmark-circle-24-filled" : coupon.status === "redeemed" ? "fluent:gift-24-filled" : coupon.status === "expired" ? "fluent:clock-24-filled" : coupon.status === "cancelled" ? "fluent:dismiss-circle-24-filled" : "fluent:question-circle-24-filled",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(` ${ssrInterpolate(coupon.status === "active" ? "\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49" : coupon.status === "redeemed" ? "\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27" : coupon.status === "expired" ? "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38" : coupon.status === "cancelled" ? "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01" : coupon.status)}</span></td><td class="px-4 py-3"><span class="text-sm text-gray-600 dark:text-gray-400">${ssrInterpolate(coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString("th-TH", { year: "numeric", month: "short", day: "numeric" }) : "-")}</span></td><td class="px-4 py-3">`);
          if (coupon.qr_code_path) {
            _push(`<button class="text-vikinger-purple hover:text-vikinger-cyan transition-colors" title="\u0E41\u0E2A\u0E14\u0E07 QR Code">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:qr-code-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button>`);
          } else {
            _push(`<span class="text-gray-400 dark:text-gray-600">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:qr-code-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</span>`);
          }
          _push(`</td><td class="px-4 py-3"><div class="flex gap-2">`);
          if (coupon.status === "active") {
            _push(`<button class="text-red-500 hover:text-red-600 transition-colors" title="\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-circle-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 transition-colors" title="\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:copy-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div></div>`);
      } else {
        _push(`<div class="vikinger-card !py-16 text-center"><div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:ticket-diagonal-24-regular",
          class: "w-10 h-10 text-gray-400 dark:text-gray-500"
        }, null, _parent));
        _push(`</div><h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-2">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E39\u0E1B\u0E2D\u0E07</h3><p class="text-gray-500 dark:text-gray-400 mb-6">\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/earn/coupons/create",
          class: "inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold shadow-lg hover:opacity-90 transition-all"
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
        _push(`</div>`);
      }
      if (unref(showRedeemModal)) {
        _push(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto"><div class="flex justify-between items-center mb-4 sm:mb-6"><h3 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2"><div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center shadow-md flex-shrink-0">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:qr-code-24-filled",
          class: "w-4 h-4 sm:w-5 sm:h-5 text-white"
        }, null, _parent));
        _push(`</div> \u0E41\u0E25\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 </h3><button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors p-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-5 h-5 sm:w-6 sm:h-6"
        }, null, _parent));
        _push(`</button></div><div class="space-y-3 sm:space-y-4"><div><label class="block text-xs sm:text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07</label><div class="flex flex-col sm:flex-row gap-2"><input${ssrRenderAttr("value", unref(redeemCode))} type="text" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E41\u0E25\u0E01" class="flex-1 px-3 sm:px-4 py-2.5 sm:py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-vikinger-purple focus:border-transparent transition-all"><button${ssrIncludeBooleanAttr(unref(isRedeeming) || !unref(redeemCode).trim()) ? " disabled" : ""} class="${ssrRenderClass([
          "px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full sm:w-auto",
          unref(isRedeeming) || !unref(redeemCode).trim() ? "bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed" : "bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:opacity-90 shadow-lg"
        ])}">`);
        if (unref(isRedeeming)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:spinner-ios-20-regular",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(`<span class="text-sm">${ssrInterpolate(unref(isRedeeming) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E41\u0E25\u0E01..." : "\u0E41\u0E25\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07")}</span></button></div></div><p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-24-regular",
          class: "w-3.5 h-3.5 sm:w-4 sm:h-4 inline mr-1"
        }, null, _parent));
        _push(` \u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E41\u0E25\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 \u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E30\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49\u0E42\u0E14\u0E22\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E35 </p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(showQrModal) && unref(selectedCouponForQr)) {
        _push(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4"><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 max-w-[280px] sm:max-w-sm w-full shadow-2xl"><div class="flex justify-between items-center mb-3 sm:mb-4"><h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"><div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:qr-code-24-filled",
          class: "w-3.5 h-3.5 sm:w-4 sm:h-4 text-white"
        }, null, _parent));
        _push(`</div> QR Code </h3><button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors p-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-5 h-5 sm:w-6 sm:h-6"
        }, null, _parent));
        _push(`</button></div><div class="flex flex-col items-center"><div class="p-3 sm:p-4 bg-white rounded-xl shadow-inner mb-3 sm:mb-4">`);
        _push(ssrRenderComponent(unref(QRCodeVue3), {
          value: unref(selectedCouponForQr).coupon_code,
          width: 160,
          height: 160,
          class: "sm:!w-[200px] sm:!h-[200px]",
          "qr-options": { errorCorrectionLevel: "H" },
          "dots-options": {
            type: "rounded",
            color: "#581c87"
          },
          "corners-square-options": {
            type: "extra-rounded",
            color: "#0891b2"
          },
          "corners-dot-options": {
            type: "dot",
            color: "#581c87"
          },
          "background-options": {
            color: "#ffffff"
          },
          "image-options": "{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
        }, null, _parent));
        _push(`</div><p class="text-xs sm:text-sm font-mono font-bold text-gray-900 dark:text-white mb-1 sm:mb-2">${ssrInterpolate(unref(selectedCouponForQr).coupon_code)}</p><p class="text-base sm:text-lg font-bold text-vikinger-purple dark:text-vikinger-cyan">${ssrInterpolate(unref(selectedCouponForQr).coupon_type === "wallet" ? "\u0E3F" : "")}${ssrInterpolate((_a = unref(selectedCouponForQr).amount) == null ? void 0 : _a.toLocaleString())} <span class="text-[10px] sm:text-xs text-gray-500">${ssrInterpolate(unref(selectedCouponForQr).coupon_type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E1A\u0E32\u0E17")}</span></p></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/coupons/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-DXE_Qgf1.mjs.map
