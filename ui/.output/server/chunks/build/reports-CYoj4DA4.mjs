import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderList, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderComponent, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
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
import './server.mjs';
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "reports",
  __ssrInlineRender: true,
  setup(__props) {
    const selectedPeriod = ref("month");
    ref(false);
    const stats = ref({
      totalRevenue: 485250,
      totalUsers: 12458,
      totalCourses: 245,
      totalEnrollments: 8945,
      revenueGrowth: 18.5,
      usersGrowth: 12.3,
      coursesGrowth: 8.7,
      enrollmentsGrowth: 15.2
    });
    const revenueByCategory = ref([
      { name: "\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19", value: 285e3, percentage: 58.7, color: "bg-blue-500" },
      { name: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E1E\u0E23\u0E35\u0E40\u0E21\u0E35\u0E22\u0E21", value: 95e3, percentage: 19.6, color: "bg-purple-500" },
      { name: "\u0E2D\u0E30\u0E04\u0E32\u0E40\u0E14\u0E21\u0E35", value: 65250, percentage: 13.4, color: "bg-green-500" },
      { name: "\u0E2D\u0E37\u0E48\u0E19\u0E46", value: 4e4, percentage: 8.3, color: "bg-orange-500" }
    ]);
    const topCourses = ref([
      { name: "Python \u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19", sales: 245, revenue: 61250 },
      { name: "JavaScript Advanced", sales: 189, revenue: 56700 },
      { name: "React.js Complete Guide", sales: 156, revenue: 46800 },
      { name: "Digital Marketing 101", sales: 134, revenue: 40200 },
      { name: "UI/UX Design Basics", sales: 121, revenue: 36300 }
    ]);
    const recentTransactions = ref([
      { id: 1, user: "\u0E2A\u0E21\u0E0A\u0E32\u0E22 \u0E43\u0E08\u0E14\u0E35", type: "\u0E0B\u0E37\u0E49\u0E2D\u0E04\u0E2D\u0E23\u0E4C\u0E2A", amount: 500, status: "success", date: "2026-01-18" },
      { id: 2, user: "\u0E2A\u0E38\u0E14\u0E32 \u0E2A\u0E27\u0E22\u0E07\u0E32\u0E21", type: "\u0E40\u0E15\u0E34\u0E21 Wallet", amount: 1e3, status: "success", date: "2026-01-18" },
      { id: 3, user: "\u0E27\u0E34\u0E0A\u0E31\u0E22 \u0E21\u0E31\u0E48\u0E19\u0E04\u0E07", type: "\u0E0B\u0E37\u0E49\u0E2D\u0E04\u0E2D\u0E23\u0E4C\u0E2A", amount: 350, status: "pending", date: "2026-01-17" },
      { id: 4, user: "\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E43\u0E08 \u0E23\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19", type: "\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E1E\u0E23\u0E35\u0E40\u0E21\u0E35\u0E22\u0E21", amount: 299, status: "success", date: "2026-01-17" },
      { id: 5, user: "\u0E0A\u0E32\u0E15\u0E34\u0E0A\u0E32\u0E22 \u0E40\u0E01\u0E48\u0E07\u0E21\u0E32\u0E01", type: "\u0E0B\u0E37\u0E49\u0E2D\u0E04\u0E2D\u0E23\u0E4C\u0E2A", amount: 450, status: "success", date: "2026-01-17" }
    ]);
    const periods = [
      { value: "week", label: "\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E19\u0E35\u0E49" },
      { value: "month", label: "\u0E40\u0E14\u0E37\u0E2D\u0E19\u0E19\u0E35\u0E49" },
      { value: "quarter", label: "\u0E44\u0E15\u0E23\u0E21\u0E32\u0E2A\u0E19\u0E35\u0E49" },
      { value: "year", label: "\u0E1B\u0E35\u0E19\u0E35\u0E49" }
    ];
    const getStatusBadge = (status) => {
      const badges = {
        success: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        pending: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
        failed: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
      };
      return badges[status] || badges.pending;
    };
    const getStatusLabel = (status) => {
      const labels = { success: "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", pending: "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23", failed: "\u0E25\u0E49\u0E21\u0E40\u0E2B\u0E25\u0E27" };
      return labels[status] || "\u0E44\u0E21\u0E48\u0E17\u0E23\u0E32\u0E1A";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E14\u0E39\u0E2A\u0E16\u0E34\u0E15\u0E34\u0E41\u0E25\u0E30\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E02\u0E2D\u0E07\u0E23\u0E30\u0E1A\u0E1A</p></div><div class="flex items-center gap-3"><select class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(periods, (period) => {
        _push(`<option${ssrRenderAttr("value", period.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedPeriod.value) ? ssrLooseContain(selectedPeriod.value, period.value) : ssrLooseEqual(selectedPeriod.value, period.value)) ? " selected" : ""}>${ssrInterpolate(period.label)}</option>`);
      });
      _push(`<!--]--></select><button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-download-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14 </button></div></div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between"><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49\u0E23\u0E27\u0E21</p><p class="text-2xl font-bold text-gray-800 dark:text-white mt-1"> \u0E3F${ssrInterpolate(stats.value.totalRevenue.toLocaleString())}</p><div class="flex items-center gap-1 mt-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-trending-24-regular",
        class: "w-4 h-4 text-green-500"
      }, null, _parent));
      _push(`<span class="text-green-500 text-sm font-medium">+${ssrInterpolate(stats.value.revenueGrowth)}%</span></div></div><div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:money-24-regular",
        class: "w-6 h-6 text-green-600 dark:text-green-400"
      }, null, _parent));
      _push(`</div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between"><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</p><p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">${ssrInterpolate(stats.value.totalUsers.toLocaleString())}</p><div class="flex items-center gap-1 mt-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-trending-24-regular",
        class: "w-4 h-4 text-green-500"
      }, null, _parent));
      _push(`<span class="text-green-500 text-sm font-medium">+${ssrInterpolate(stats.value.usersGrowth)}%</span></div></div><div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:people-24-regular",
        class: "w-6 h-6 text-blue-600 dark:text-blue-400"
      }, null, _parent));
      _push(`</div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between"><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p><p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">${ssrInterpolate(stats.value.totalCourses.toLocaleString())}</p><div class="flex items-center gap-1 mt-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-trending-24-regular",
        class: "w-4 h-4 text-green-500"
      }, null, _parent));
      _push(`<span class="text-green-500 text-sm font-medium">+${ssrInterpolate(stats.value.coursesGrowth)}%</span></div></div><div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:hat-graduation-24-regular",
        class: "w-6 h-6 text-purple-600 dark:text-purple-400"
      }, null, _parent));
      _push(`</div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between"><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E01\u0E32\u0E23\u0E25\u0E07\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19</p><p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">${ssrInterpolate(stats.value.totalEnrollments.toLocaleString())}</p><div class="flex items-center gap-1 mt-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-trending-24-regular",
        class: "w-4 h-4 text-green-500"
      }, null, _parent));
      _push(`<span class="text-green-500 text-sm font-medium">+${ssrInterpolate(stats.value.enrollmentsGrowth)}%</span></div></div><div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-xl">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:person-add-24-regular",
        class: "w-6 h-6 text-orange-600 dark:text-orange-400"
      }, null, _parent));
      _push(`</div></div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49\u0E15\u0E32\u0E21\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</h2><div class="space-y-4"><!--[-->`);
      ssrRenderList(revenueByCategory.value, (category) => {
        _push(`<div><div class="flex items-center justify-between mb-1"><span class="text-sm text-gray-600 dark:text-gray-300">${ssrInterpolate(category.name)}</span><span class="text-sm font-medium text-gray-800 dark:text-white"> \u0E3F${ssrInterpolate(category.value.toLocaleString())} (${ssrInterpolate(category.percentage)}%) </span></div><div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden"><div class="${ssrRenderClass([category.color, "h-full rounded-full transition-all duration-500"])}" style="${ssrRenderStyle({ width: `${category.percentage}%` })}"></div></div></div>`);
      });
      _push(`<!--]--></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E02\u0E32\u0E22\u0E14\u0E35</h2><div class="space-y-4"><!--[-->`);
      ssrRenderList(topCourses.value, (course, index) => {
        _push(`<div class="flex items-center gap-4"><span class="${ssrRenderClass([
          "flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold",
          index === 0 ? "bg-yellow-100 text-yellow-700" : index === 1 ? "bg-gray-100 text-gray-700" : index === 2 ? "bg-orange-100 text-orange-700" : "bg-gray-50 text-gray-500"
        ])}">${ssrInterpolate(index + 1)}</span><div class="flex-1 min-w-0"><p class="font-medium text-gray-800 dark:text-white truncate">${ssrInterpolate(course.name)}</p><p class="text-sm text-gray-500">${ssrInterpolate(course.sales)} \u0E22\u0E2D\u0E14\u0E02\u0E32\u0E22</p></div><span class="font-semibold text-green-600">\u0E3F${ssrInterpolate(course.revenue.toLocaleString())}</span></div>`);
      });
      _push(`<!--]--></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold text-gray-800 dark:text-white">\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/transactions",
        class: "text-sm text-indigo-600 hover:text-indigo-700 font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-gray-100 dark:border-gray-700"><th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase">\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</th><th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="pb-3 text-right text-xs font-medium text-gray-500 uppercase">\u0E08\u0E33\u0E19\u0E27\u0E19</th><th class="pb-3 text-center text-xs font-medium text-gray-500 uppercase">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="pb-3 text-right text-xs font-medium text-gray-500 uppercase">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</th></tr></thead><tbody class="divide-y divide-gray-50 dark:divide-gray-700/50"><!--[-->`);
      ssrRenderList(recentTransactions.value, (tx) => {
        _push(`<tr><td class="py-3 text-gray-800 dark:text-white font-medium">${ssrInterpolate(tx.user)}</td><td class="py-3 text-gray-600 dark:text-gray-300">${ssrInterpolate(tx.type)}</td><td class="py-3 text-right font-medium text-green-600">\u0E3F${ssrInterpolate(tx.amount.toLocaleString())}</td><td class="py-3 text-center"><span class="${ssrRenderClass([getStatusBadge(tx.status), "px-2 py-1 rounded-full text-xs font-medium"])}">${ssrInterpolate(getStatusLabel(tx.status))}</span></td><td class="py-3 text-right text-gray-500">${ssrInterpolate(tx.date)}</td></tr>`);
      });
      _push(`<!--]--></tbody></table></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/reports.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=reports-CYoj4DA4.mjs.map
