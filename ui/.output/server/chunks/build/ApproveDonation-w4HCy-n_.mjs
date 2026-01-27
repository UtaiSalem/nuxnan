import { ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrRenderTeleport, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './ApproveDonateCard-DYw_Vvyz.mjs';
import { i as useApi, b as useRuntimeConfig } from './server.mjs';
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

const _sfc_main = {
  __name: "ApproveDonation",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const config = useRuntimeConfig();
    const donates = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    const currentPage = ref(1);
    const lastPage = ref(1);
    ref(0);
    const filterStatus = ref("all");
    const viewMode = ref("card");
    const selectedSlip = ref(null);
    const filteredDonates = computed(() => {
      if (filterStatus.value === "all") return donates.value;
      const statusMap = {
        pending: 0,
        approved: 1,
        rejected: 2
      };
      return donates.value.filter((d) => d.status === statusMap[filterStatus.value]);
    });
    const stats = computed(() => {
      const pending = donates.value.filter((d) => d.status === 0).length;
      const approved = donates.value.filter((d) => d.status === 1).length;
      const rejected = donates.value.filter((d) => d.status === 2).length;
      return { pending, approved, rejected, total: donates.value.length };
    });
    const handleApproved = (donateId) => {
      const donate = donates.value.find((d) => d.id === donateId);
      if (donate) {
        donate.status = 1;
      }
    };
    const handleRejected = (donateId) => {
      const donate = donates.value.find((d) => d.id === donateId);
      if (donate) {
        donate.status = 2;
      }
    };
    const getSlipUrl = (slip) => {
      if (!slip) return null;
      if (slip.startsWith("http")) return slip;
      return `${config.public.apiBase}${slip}`;
    };
    const getStatusInfo = (status) => {
      switch (status) {
        case 0:
          return { text: "\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", class: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400", icon: "mdi:clock-outline" };
        case 1:
          return { text: "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27", class: "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400", icon: "mdi:check-circle" };
        case 2:
          return { text: "\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18", class: "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400", icon: "mdi:close-circle" };
        default:
          return { text: "\u0E44\u0E21\u0E48\u0E17\u0E23\u0E32\u0E1A", class: "bg-gray-100 text-gray-800", icon: "mdi:help-circle" };
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "py-6" }, _attrs))}><div class="mb-6"><div class="flex items-center gap-3 mb-2"><div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:hand-coin",
        class: "w-6 h-6 text-purple-600 dark:text-purple-400"
      }, null, _parent));
      _push(`</div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E41\u0E15\u0E49\u0E21</h1></div><p class="text-gray-600 dark:text-gray-400">\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04</p></div><div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6"><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-gray-200 dark:border-gray-700"><div class="flex items-center gap-3"><div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:format-list-bulleted",
        class: "w-5 h-5 text-gray-600 dark:text-gray-400"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(stats.value.total)}</p><p class="text-xs text-gray-500 dark:text-gray-400">\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-yellow-200 dark:border-yellow-800"><div class="flex items-center gap-3"><div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:clock-outline",
        class: "w-5 h-5 text-yellow-600 dark:text-yellow-400"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">${ssrInterpolate(stats.value.pending)}</p><p class="text-xs text-gray-500 dark:text-gray-400">\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-green-200 dark:border-green-800"><div class="flex items-center gap-3"><div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-circle",
        class: "w-5 h-5 text-green-600 dark:text-green-400"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-green-600 dark:text-green-400">${ssrInterpolate(stats.value.approved)}</p><p class="text-xs text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-red-200 dark:border-red-800"><div class="flex items-center gap-3"><div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:close-circle",
        class: "w-5 h-5 text-red-600 dark:text-red-400"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-red-600 dark:text-red-400">${ssrInterpolate(stats.value.rejected)}</p><p class="text-xs text-gray-500 dark:text-gray-400">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18</p></div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-2 mb-6"><div class="flex flex-wrap items-center justify-between gap-2"><div class="flex flex-wrap gap-2"><button class="${ssrRenderClass([
        "px-4 py-2 rounded-lg font-medium text-sm transition-colors",
        filterStatus.value === "all" ? "bg-purple-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}"> \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button><button class="${ssrRenderClass([
        "px-4 py-2 rounded-lg font-medium text-sm transition-colors flex items-center gap-2",
        filterStatus.value === "pending" ? "bg-yellow-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:clock-outline",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 `);
      if (stats.value.pending > 0) {
        _push(`<span class="bg-yellow-600 text-white text-xs px-2 py-0.5 rounded-full">${ssrInterpolate(stats.value.pending)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</button><button class="${ssrRenderClass([
        "px-4 py-2 rounded-lg font-medium text-sm transition-colors flex items-center gap-2",
        filterStatus.value === "approved" ? "bg-green-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-circle",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27 </button><button class="${ssrRenderClass([
        "px-4 py-2 rounded-lg font-medium text-sm transition-colors flex items-center gap-2",
        filterStatus.value === "rejected" ? "bg-red-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:close-circle",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div><div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1"><button class="${ssrRenderClass([
        "p-2 rounded-md transition-colors",
        viewMode.value === "card" ? "bg-white dark:bg-gray-600 shadow text-purple-600 dark:text-purple-400" : "text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
      ])}" title="\u0E21\u0E38\u0E21\u0E21\u0E2D\u0E07\u0E41\u0E1A\u0E1A\u0E01\u0E32\u0E23\u0E4C\u0E14">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:view-grid",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button><button class="${ssrRenderClass([
        "p-2 rounded-md transition-colors",
        viewMode.value === "table" ? "bg-white dark:bg-gray-600 shadow text-purple-600 dark:text-purple-400" : "text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
      ])}" title="\u0E21\u0E38\u0E21\u0E21\u0E2D\u0E07\u0E41\u0E1A\u0E1A\u0E15\u0E32\u0E23\u0E32\u0E07">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:table",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button></div></div></div>`);
      if (isLoading.value) {
        _push(`<div class="flex flex-col items-center justify-center py-20">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:loading",
          class: "w-12 h-12 text-purple-500 animate-spin"
        }, null, _parent));
        _push(`<p class="mt-4 text-gray-600 dark:text-gray-400">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else if (error.value) {
        _push(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:alert-circle",
          class: "w-12 h-12 text-red-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<p class="text-red-600 dark:text-red-400 mb-4">${ssrInterpolate(error.value)}</p><button class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </button></div>`);
      } else if (filteredDonates.value.length === 0) {
        _push(`<div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:inbox-outline",
          class: "w-16 h-16 text-gray-400 mx-auto mb-4"
        }, null, _parent));
        _push(`<p class="text-lg font-medium text-gray-600 dark:text-gray-400">${ssrInterpolate(filterStatus.value === "pending" ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E17\u0E35\u0E48\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34" : "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")}</p></div>`);
      } else if (viewMode.value === "card") {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"><!--[-->`);
        ssrRenderList(filteredDonates.value, (donate) => {
          _push(ssrRenderComponent(_sfc_main$1, {
            key: donate.id,
            donate,
            onApproved: handleApproved,
            onRejected: handleRejected
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden"><div class="overflow-x-auto"><table class="w-full"><thead class="bg-gray-50 dark:bg-gray-700"><tr><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ID</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E1C\u0E39\u0E49\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E41\u0E15\u0E49\u0E21</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48/\u0E40\u0E27\u0E25\u0E32\u0E42\u0E2D\u0E19</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E2A\u0E25\u0E34\u0E1B</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(filteredDonates.value, (donate) => {
          var _a, _b;
          _push(`<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"><td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">#${ssrInterpolate(donate.id)}</td><td class="px-4 py-3"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden flex items-center justify-center">`);
          if ((_a = donate.donor) == null ? void 0 : _a.avatar) {
            _push(`<img${ssrRenderAttr("src", donate.donor.avatar)} class="w-full h-full object-cover">`);
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:account",
              class: "w-4 h-4 text-gray-400"
            }, null, _parent));
          }
          _push(`</div><span class="text-sm text-gray-900 dark:text-white">${ssrInterpolate(donate.donor_name || "\u0E44\u0E21\u0E48\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E2D\u0E2D\u0E01\u0E19\u0E32\u0E21")}</span></div></td><td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400 font-medium">${ssrInterpolate(donate.amounts)}</td><td class="px-4 py-3 text-sm text-purple-600 dark:text-purple-400 font-medium">${ssrInterpolate(((_b = donate.total_points) == null ? void 0 : _b.toLocaleString()) || 0)}</td><td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400"><div>${ssrInterpolate(donate.transfer_date || "-")}</div>`);
          if (donate.transfer_time) {
            _push(`<div class="text-xs text-gray-500">${ssrInterpolate(donate.transfer_time)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</td><td class="px-4 py-3">`);
          if (getSlipUrl(donate.slip)) {
            _push(`<button class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 hover:border-purple-500 transition-colors"><img${ssrRenderAttr("src", getSlipUrl(donate.slip))} class="w-full h-full object-cover"></button>`);
          } else {
            _push(`<span class="text-gray-400 text-sm">-</span>`);
          }
          _push(`</td><td class="px-4 py-3"><span class="${ssrRenderClass([getStatusInfo(donate.status).class, "px-2 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getStatusInfo(donate.status).icon,
            class: "w-3 h-3"
          }, null, _parent));
          _push(` ${ssrInterpolate(getStatusInfo(donate.status).text)}</span></td><td class="px-4 py-3">`);
          if (donate.status === 0) {
            _push(`<div class="flex items-center justify-center gap-2"><button class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors" title="\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:check",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button><button class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors" title="\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button></div>`);
          } else {
            _push(`<span class="text-gray-400 text-sm">-</span>`);
          }
          _push(`</td></tr>`);
        });
        _push(`<!--]--></tbody></table></div></div>`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (selectedSlip.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"><div class="relative max-w-4xl max-h-[90vh]"><button class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:close",
            class: "w-8 h-8"
          }, null, _parent));
          _push2(`</button><img${ssrRenderAttr("src", selectedSlip.value)} alt="\u0E2A\u0E25\u0E34\u0E1B\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19" class="max-w-full max-h-[85vh] object-contain rounded-lg"></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      if (lastPage.value > 1) {
        _push(`<div class="mt-8 flex items-center justify-center gap-4"><button${ssrIncludeBooleanAttr(currentPage.value <= 1) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:chevron-left",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </button><span class="text-gray-600 dark:text-gray-400"> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(currentPage.value)} \u0E08\u0E32\u0E01 ${ssrInterpolate(lastPage.value)}</span><button${ssrIncludeBooleanAttr(currentPage.value >= lastPage.value) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"> \u0E16\u0E31\u0E14\u0E44\u0E1B `);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:chevron-right",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/PlearndAdmin/Donation/ApproveDonation.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=ApproveDonation-w4HCy-n_.mjs.map
