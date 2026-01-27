import { defineComponent, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrRenderTeleport, ssrIncludeBooleanAttr } from 'vue/server-renderer';
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
  __name: "pending",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    const activeTab = ref("withdrawals");
    const pendingWithdrawals = ref([]);
    const pendingDeposits = ref([]);
    const isLoading = ref(true);
    ref(1);
    ref(1);
    const totalPendingWithdrawals = ref(0);
    const totalPendingDeposits = ref(0);
    const showApproveModal = ref(false);
    const showRejectModal = ref(false);
    const showSlipModal = ref(false);
    const selectedRequest = ref(null);
    const selectedSlipUrl = ref("");
    const isProcessing = ref(false);
    const rejectReason = ref("");
    const adminNote = ref("");
    const formatCurrency = (amount) => {
      return new Intl.NumberFormat("th-TH", {
        style: "currency",
        currency: "THB"
      }).format(amount);
    };
    const formatDate = (dateStr) => {
      const date = new Date(dateStr);
      return date.toLocaleString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</h1><p class="text-gray-500 dark:text-gray-400 mt-1"> \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E17\u0E35\u0E48\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate((totalPendingWithdrawals.value + totalPendingDeposits.value).toLocaleString())} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p></div><button class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 transition-colors inline-flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-sync-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A </button></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-1 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex"><button class="${ssrRenderClass([
        "flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors",
        activeTab.value === "withdrawals" ? "bg-indigo-600 text-white" : "text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
      ])}"> \u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 (${ssrInterpolate(totalPendingWithdrawals.value)}) </button><button class="${ssrRenderClass([
        "flex-1 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors",
        activeTab.value === "deposits" ? "bg-indigo-600 text-white" : "text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
      ])}"> \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 (${ssrInterpolate(totalPendingDeposits.value)}) </button></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">`);
      if (isLoading.value) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else if ((activeTab.value === "withdrawals" ? pendingWithdrawals.value : pendingDeposits.value).length === 0) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-regular",
          class: "w-12 h-12 text-green-400 mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">${ssrInterpolate(activeTab.value === "withdrawals" ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23" : "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23")}</p></div>`);
      } else {
        _push(`<div class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(activeTab.value === "withdrawals" ? pendingWithdrawals.value : pendingDeposits.value, (request) => {
          var _a, _b;
          _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"><div class="flex flex-col sm:flex-row sm:items-center gap-4"><div class="flex items-center gap-3 flex-1"><div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-24-regular",
            class: "w-5 h-5 text-gray-500"
          }, null, _parent));
          _push(`</div><div class="min-w-0"><p class="font-medium text-gray-800 dark:text-white truncate">${ssrInterpolate(((_a = request.user) == null ? void 0 : _a.name) || "Unknown")}</p><p class="text-sm text-gray-500 truncate">${ssrInterpolate(((_b = request.user) == null ? void 0 : _b.email) || "-")}</p>`);
          if (activeTab.value === "deposits" && request.payment_method) {
            _push(`<p class="text-xs text-gray-400 flex items-center gap-1">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:payment-24-regular",
              class: "w-3 h-3"
            }, null, _parent));
            _push(` ${ssrInterpolate(request.payment_method_label || request.payment_method)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="text-right whitespace-nowrap"><p class="text-lg font-bold text-gray-800 dark:text-white">${ssrInterpolate(formatCurrency(request.amount))}</p><p class="text-sm text-gray-500">${ssrInterpolate(formatDate(request.created_at))}</p>`);
          if (activeTab.value === "deposits" && request.reference_number) {
            _push(`<p class="text-xs text-gray-400"> Ref: ${ssrInterpolate(request.reference_number)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          if (activeTab.value === "deposits" && (request.slip_url || request.transfer_slip)) {
            _push(`<div class="hidden sm:block relative group cursor-pointer"><div class="w-12 h-16 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 relative"><img${ssrRenderAttr("src", request.slip_url || request.transfer_slip)} class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" alt="Slip"><div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:zoom-in-24-filled",
              class: "w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity"
            }, null, _parent));
            _push(`</div></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex gap-2"><button class="w-9 h-9 sm:w-auto sm:px-4 sm:py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors inline-flex items-center justify-center gap-2" title="\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="hidden sm:inline">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</span></button><button class="w-9 h-9 sm:w-auto sm:px-4 sm:py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors inline-flex items-center justify-center gap-2" title="\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="hidden sm:inline">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18</span></button></div></div>`);
          if (activeTab.value === "deposits" && (request.slip_url || request.transfer_slip)) {
            _push(`<div class="sm:hidden mt-3"><button class="flex items-center gap-2 text-xs text-blue-600 font-medium">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:image-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` \u0E14\u0E39\u0E23\u0E39\u0E1B\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 </button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (showApproveModal.value) {
          _push2(`<div class="fixed inset-0 z-[60] flex items-center justify-center p-4"><div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl transform transition-all scale-100"><h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</h3><p class="text-gray-600 dark:text-gray-300 mb-4"> \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34${ssrInterpolate(activeTab.value === "withdrawals" ? "\u0E01\u0E32\u0E23\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19" : "\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19")} <span class="font-bold text-green-600">${ssrInterpolate(formatCurrency(((_a = selectedRequest.value) == null ? void 0 : _a.amount) || 0))}</span> \u0E43\u0E0A\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48? </p>`);
          if (activeTab.value === "deposits") {
            _push2(`<div class="mb-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="2" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none" placeholder="\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38...">${ssrInterpolate(adminNote.value)}</textarea></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="flex gap-3"><button${ssrIncludeBooleanAttr(isProcessing.value) ? " disabled" : ""} class="flex-1 px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-xl transition-colors font-medium shadow-sm shadow-green-600/30">`);
          if (isProcessing.value) {
            _push2(`<span class="flex items-center justify-center gap-2">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23 </span>`);
          } else {
            _push2(`<span>\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</span>`);
          }
          _push2(`</button><button class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-colors font-medium"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        if (showRejectModal.value) {
          _push2(`<div class="fixed inset-0 z-[60] flex items-center justify-center p-4"><div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl transform transition-all scale-100"><div class="flex items-center gap-3 mb-4 text-red-600"><div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:warning-24-filled",
            class: "w-6 h-6"
          }, null, _parent));
          _push2(`</div><h3 class="text-lg font-bold text-gray-900 dark:text-white">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18${ssrInterpolate(activeTab.value === "withdrawals" ? "\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19" : "\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19")}</h3></div><div class="mb-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25 <span class="text-red-500">*</span></label><textarea rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18..." autofocus>${ssrInterpolate(rejectReason.value)}</textarea></div><div class="mb-6"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="2" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none" placeholder="\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38...">${ssrInterpolate(adminNote.value)}</textarea></div><div class="flex gap-3"><button${ssrIncludeBooleanAttr(isProcessing.value || !rejectReason.value.trim()) ? " disabled" : ""} class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:text-gray-500 text-white rounded-xl transition-colors font-medium shadow-sm shadow-red-600/30">`);
          if (isProcessing.value) {
            _push2(`<span class="flex items-center justify-center gap-2">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23 </span>`);
          } else {
            _push2(`<span>\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18</span>`);
          }
          _push2(`</button><button class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-colors font-medium"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        if (showSlipModal.value) {
          _push2(`<div class="fixed inset-0 z-[70] flex items-center justify-center p-4"><div class="absolute inset-0 bg-gray-900/80 backdrop-blur-md transition-all duration-300"></div><div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col items-center justify-center"><button class="absolute -top-12 right-0 p-2 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-colors backdrop-blur-sm">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-filled",
            class: "w-6 h-6"
          }, null, _parent));
          _push2(`</button><div class="bg-transparent rounded-2xl overflow-hidden shadow-2xl max-w-full">`);
          if (selectedSlipUrl.value) {
            _push2(`<div class="relative group"><img${ssrRenderAttr("src", selectedSlipUrl.value)}${ssrRenderAttr("alt", "\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19")} class="max-w-full max-h-[85vh] object-contain rounded-xl"><div class="absolute bottom-0 inset-x-0 p-4 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex justify-center gap-4"><a${ssrRenderAttr("href", selectedSlipUrl.value)} target="_blank" download class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm text-sm font-medium flex items-center gap-2 transition-colors">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-download-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14 </a><a${ssrRenderAttr("href", selectedSlipUrl.value)} target="_blank" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg backdrop-blur-sm text-sm font-medium flex items-center gap-2 transition-colors">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:open-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E40\u0E1B\u0E34\u0E14\u0E41\u0E17\u0E47\u0E1A\u0E43\u0E2B\u0E21\u0E48 </a></div></div>`);
          } else {
            _push2(`<div class="bg-white dark:bg-gray-800 p-12 rounded-xl text-center min-w-[300px]"><div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:image-off-24-filled",
              class: "w-10 h-10 text-gray-400"
            }, null, _parent));
            _push2(`</div><h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-1">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</h3><p class="text-gray-500 text-sm">\u0E44\u0E1F\u0E25\u0E4C\u0E2D\u0E32\u0E08\u0E16\u0E39\u0E01\u0E25\u0E1A\u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E34\u0E07\u0E01\u0E4C\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07</p><button class="mt-6 px-6 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors"> \u0E1B\u0E34\u0E14\u0E2B\u0E19\u0E49\u0E32\u0E15\u0E48\u0E32\u0E07 </button></div>`);
          }
          _push2(`</div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/wallet/pending.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=pending-BTZDC337.mjs.map
