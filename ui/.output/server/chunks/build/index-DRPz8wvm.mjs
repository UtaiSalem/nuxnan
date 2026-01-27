import { ref, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './ApproveDonateCard-DYw_Vvyz.mjs';
import { i as useApi, d as useAuthStore } from './server.mjs';
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
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const api = useApi();
    const donations = ref([]);
    const stats = ref({
      total: 0,
      pending: 0,
      approved: 0,
      rejected: 0
    });
    const isLoading = ref(true);
    const currentPage = ref(1);
    const totalPages = ref(1);
    const perPage = ref(12);
    const selectedStatus = ref("all");
    const statusFilters = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", color: "bg-indigo-500" },
      { value: "pending", label: "\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", color: "bg-yellow-500" },
      { value: "approved", label: "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27", color: "bg-green-500" },
      { value: "rejected", label: "\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18", color: "bg-red-500" }
    ];
    const fetchDonations = async () => {
      isLoading.value = true;
      try {
        const params = {
          page: currentPage.value,
          per_page: perPage.value
        };
        if (selectedStatus.value !== "all") {
          const statusMap = {
            "pending": 0,
            "approved": 1,
            "rejected": 2
          };
          if (statusMap[selectedStatus.value] !== void 0) {
            params.status = statusMap[selectedStatus.value];
          }
        }
        const response = await api.get("/api/plearnd-admin/supports/donates", { params });
        if (response) {
          donations.value = response.donates.data || response.donates;
          if (response.stats) {
            stats.value = response.stats;
          }
          const meta = response.donates.meta || {};
          currentPage.value = meta.current_page || 1;
          totalPages.value = meta.last_page || 1;
        }
      } catch (error) {
        console.error("Failed to fetch donations:", error);
      } finally {
        isLoading.value = false;
      }
    };
    watch(selectedStatus, () => {
      currentPage.value = 1;
      fetchDonations();
    });
    const onApproved = (id) => {
      var _a;
      const index = donations.value.findIndex((d) => d.id === id);
      if (index !== -1) {
        donations.value[index].status = 1;
        donations.value[index].approved_by = (_a = useAuthStore().user) == null ? void 0 : _a.id;
        stats.value.pending--;
        stats.value.approved++;
      }
    };
    const onRejected = (id) => {
      var _a;
      const index = donations.value.findIndex((d) => d.id === id);
      if (index !== -1) {
        donations.value[index].status = 2;
        donations.value[index].approved_by = (_a = useAuthStore().user) == null ? void 0 : _a.id;
        stats.value.pending--;
        stats.value.rejected++;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E41\u0E15\u0E49\u0E21</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04</p></div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4"><div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:list-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-gray-800 dark:text-white">${ssrInterpolate(stats.value.total)}</p><p class="text-sm text-gray-500">\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-yellow-500/20 dark:border-yellow-500/20 flex items-center gap-4"><div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center text-yellow-600 dark:text-yellow-400">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clock-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">${ssrInterpolate(stats.value.pending)}</p><p class="text-sm text-gray-500">\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-green-500/20 dark:border-green-500/20 flex items-center gap-4"><div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-green-600 dark:text-green-400">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-green-600 dark:text-green-400">${ssrInterpolate(stats.value.approved)}</p><p class="text-sm text-gray-500">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27</p></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-red-500/20 dark:border-red-500/20 flex items-center gap-4"><div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center text-red-600 dark:text-red-400">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:dismiss-circle-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div><div><p class="text-2xl font-bold text-red-600 dark:text-red-400">${ssrInterpolate(stats.value.rejected)}</p><p class="text-sm text-gray-500">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18</p></div></div></div><div class="flex flex-wrap gap-2"><!--[-->`);
      ssrRenderList(statusFilters, (filter) => {
        _push(`<button class="${ssrRenderClass([
          "px-4 py-2 rounded-xl text-sm font-medium transition-all",
          selectedStatus.value === filter.value ? `${filter.color} text-white shadow-lg` : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
        ])}">${ssrInterpolate(filter.label)} `);
        if (filter.value === "pending" && stats.value.pending > 0) {
          _push(`<span class="ml-1 px-1.5 py-0.5 bg-white/20 rounded-full text-xs">${ssrInterpolate(stats.value.pending)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      });
      _push(`<!--]--></div>`);
      if (isLoading.value) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="bg-white dark:bg-gray-800 h-80 rounded-xl animate-pulse"></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (donations.value.length > 0) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"><!--[-->`);
        ssrRenderList(donations.value, (donate) => {
          _push(ssrRenderComponent(_sfc_main$1, {
            key: donate.id,
            donate,
            onApproved,
            onRejected,
            class: "h-full"
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:box-search-24-regular",
          class: "w-16 h-16 text-gray-300 mx-auto mb-4"
        }, null, _parent));
        _push(`<p class="text-gray-500">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</p></div>`);
      }
      if (!isLoading.value && totalPages.value > 1) {
        _push(`<div class="flex justify-center mt-8"><div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"><button${ssrIncludeBooleanAttr(currentPage.value === 1) ? " disabled" : ""} class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50 transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-left-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button><span class="px-4 text-sm font-medium text-gray-600 dark:text-gray-300"> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(currentPage.value)} / ${ssrInterpolate(totalPages.value)}</span><button${ssrIncludeBooleanAttr(currentPage.value === totalPages.value) ? " disabled" : ""} class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50 transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-right-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/supports/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-DRPz8wvm.mjs.map
