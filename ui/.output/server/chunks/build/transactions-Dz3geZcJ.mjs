import { defineComponent, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderComponent, ssrRenderClass } from 'vue/server-renderer';
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
  __name: "transactions",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    const transactions = ref([]);
    const isLoading = ref(true);
    const currentPage = ref(1);
    const totalPages = ref(1);
    ref(20);
    const totalTransactions = ref(0);
    const selectedType = ref("all");
    const selectedStatus = ref("all");
    const transactionTypes = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "deposit", label: "\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19" },
      { value: "withdraw", label: "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19" },
      { value: "transfer", label: "\u0E42\u0E2D\u0E19" },
      { value: "payment", label: "\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19" },
      { value: "refund", label: "\u0E04\u0E37\u0E19\u0E40\u0E07\u0E34\u0E19" }
    ];
    const transactionStatuses = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "pending", label: "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23" },
      { value: "completed", label: "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08" },
      { value: "failed", label: "\u0E25\u0E49\u0E21\u0E40\u0E2B\u0E25\u0E27" },
      { value: "cancelled", label: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01" }
    ];
    const getStatusBadge = (status) => {
      const badges = {
        pending: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
        completed: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        failed: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
        cancelled: "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400"
      };
      return badges[status] || badges.pending;
    };
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</h1><p class="text-gray-500 dark:text-gray-400 mt-1"> \u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21 ${ssrInterpolate(totalTransactions.value.toLocaleString())} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex flex-col sm:flex-row gap-4"><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(transactionTypes, (type) => {
        _push(`<option${ssrRenderAttr("value", type.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedType.value) ? ssrLooseContain(selectedType.value, type.value) : ssrLooseEqual(selectedType.value, type.value)) ? " selected" : ""}>${ssrInterpolate(type.label)}</option>`);
      });
      _push(`<!--]--></select><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(transactionStatuses, (status) => {
        _push(`<option${ssrRenderAttr("value", status.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedStatus.value) ? ssrLooseContain(selectedStatus.value, status.value) : ssrLooseEqual(selectedStatus.value, status.value)) ? " selected" : ""}>${ssrInterpolate(status.label)}</option>`);
      });
      _push(`<!--]--></select><button class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 transition-colors inline-flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-sync-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A </button></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">`);
      if (isLoading.value) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else if (transactions.value.length === 0) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:wallet-24-regular",
          class: "w-12 h-12 text-gray-300 mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21</p></div>`);
      } else {
        _push(`<div class="overflow-x-auto"><table class="w-full"><thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">\u0E08\u0E33\u0E19\u0E27\u0E19</th><th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(transactions.value, (tx) => {
          var _a, _b;
          _push(`<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50"><td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">#${ssrInterpolate(tx.id)}</td><td class="px-4 py-3 text-sm text-gray-800 dark:text-white">${ssrInterpolate(((_a = tx.user) == null ? void 0 : _a.name) || "-")}</td><td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 capitalize">${ssrInterpolate(tx.type)}</td><td class="${ssrRenderClass([tx.amount >= 0 ? "text-green-600" : "text-red-600", "px-4 py-3 text-sm text-right font-medium"])}">${ssrInterpolate(formatCurrency(tx.amount))}</td><td class="px-4 py-3 text-center"><span class="${ssrRenderClass([getStatusBadge(tx.status), "px-2 py-1 text-xs rounded-lg"])}">${ssrInterpolate(((_b = transactionStatuses.find((s) => s.value === tx.status)) == null ? void 0 : _b.label) || tx.status)}</span></td><td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(formatDate(tx.created_at))}</td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      }
      if (totalPages.value > 1) {
        _push(`<div class="p-4 border-t border-gray-100 dark:border-gray-700"><div class="flex justify-center gap-1"><!--[-->`);
        ssrRenderList(Math.min(totalPages.value, 10), (page) => {
          _push(`<button class="${ssrRenderClass([currentPage.value === page ? "bg-indigo-600 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "w-10 h-10 rounded-lg text-sm font-medium transition-colors"])}">${ssrInterpolate(page)}</button>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/transactions.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=transactions-Dz3geZcJ.mjs.map
