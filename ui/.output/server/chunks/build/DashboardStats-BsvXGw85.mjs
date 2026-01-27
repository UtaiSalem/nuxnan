import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "DashboardStats",
  __ssrInlineRender: true,
  props: {
    stats: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))} data-v-1b89f081><div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5" data-v-1b89f081><div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2" data-v-1b89f081><div class="p-6" data-v-1b89f081><div class="flex items-center justify-between" data-v-1b89f081><div class="flex-1" data-v-1b89f081><div class="flex items-center mb-4" data-v-1b89f081><div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300" data-v-1b89f081><i class="fas fa-users text-white text-2xl" data-v-1b89f081></i></div></div><dt class="text-sm font-medium text-gray-500 mb-2" data-v-1b89f081> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </dt><dd class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent" data-v-1b89f081>${ssrInterpolate(((_a = __props.stats.total_students) == null ? void 0 : _a.toLocaleString()) || 0)}</dd><div class="mt-2 flex items-center text-xs text-green-600" data-v-1b89f081><i class="fas fa-arrow-up mr-1" data-v-1b89f081></i><span data-v-1b89f081>\u0E04\u0E19</span></div></div><div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full -mr-10 -mt-10 opacity-20" data-v-1b89f081></div></div></div><div class="h-1 bg-gradient-to-r from-blue-500 to-cyan-500" data-v-1b89f081></div></div><div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2" data-v-1b89f081><div class="p-6" data-v-1b89f081><div class="flex items-center justify-between" data-v-1b89f081><div class="flex-1" data-v-1b89f081><div class="flex items-center mb-4" data-v-1b89f081><div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300" data-v-1b89f081><i class="fas fa-home text-white text-2xl" data-v-1b89f081></i></div></div><dt class="text-sm font-medium text-gray-500 mb-2" data-v-1b89f081> \u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </dt><dd class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent" data-v-1b89f081>${ssrInterpolate(((_b = __props.stats.total_visits) == null ? void 0 : _b.toLocaleString()) || 0)}</dd><div class="mt-2 flex items-center text-xs text-green-600" data-v-1b89f081><i class="fas fa-check-circle mr-1" data-v-1b89f081></i><span data-v-1b89f081>\u0E04\u0E23\u0E31\u0E49\u0E07</span></div></div><div class="absolute top-0 right-0 w-20 h-20 bg-green-100 rounded-full -mr-10 -mt-10 opacity-20" data-v-1b89f081></div></div></div><div class="h-1 bg-gradient-to-r from-green-500 to-emerald-500" data-v-1b89f081></div></div><div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2" data-v-1b89f081><div class="p-6" data-v-1b89f081><div class="flex items-center justify-between" data-v-1b89f081><div class="flex-1" data-v-1b89f081><div class="flex items-center mb-4" data-v-1b89f081><div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300" data-v-1b89f081><i class="fas fa-calendar text-white text-2xl" data-v-1b89f081></i></div></div><dt class="text-sm font-medium text-gray-500 mb-2" data-v-1b89f081> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E40\u0E14\u0E37\u0E2D\u0E19\u0E19\u0E35\u0E49 </dt><dd class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent" data-v-1b89f081>${ssrInterpolate(((_c = __props.stats.visits_this_month) == null ? void 0 : _c.toLocaleString()) || 0)}</dd><div class="mt-2 flex items-center text-xs text-yellow-600" data-v-1b89f081><i class="fas fa-chart-line mr-1" data-v-1b89f081></i><span data-v-1b89f081>\u0E04\u0E23\u0E31\u0E49\u0E07</span></div></div><div class="absolute top-0 right-0 w-20 h-20 bg-yellow-100 rounded-full -mr-10 -mt-10 opacity-20" data-v-1b89f081></div></div></div><div class="h-1 bg-gradient-to-r from-yellow-500 to-orange-500" data-v-1b89f081></div></div><div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2" data-v-1b89f081><div class="p-6" data-v-1b89f081><div class="flex items-center justify-between" data-v-1b89f081><div class="flex-1" data-v-1b89f081><div class="flex items-center mb-4" data-v-1b89f081><div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300" data-v-1b89f081><i class="fas fa-clock text-white text-2xl" data-v-1b89f081></i></div></div><dt class="text-sm font-medium text-gray-500 mb-2" data-v-1b89f081> \u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23 </dt><dd class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent" data-v-1b89f081>${ssrInterpolate(((_d = __props.stats.pending_visits) == null ? void 0 : _d.toLocaleString()) || 0)}</dd><div class="mt-2 flex items-center text-xs text-orange-600" data-v-1b89f081><i class="fas fa-hourglass-half mr-1" data-v-1b89f081></i><span data-v-1b89f081>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</span></div></div><div class="absolute top-0 right-0 w-20 h-20 bg-orange-100 rounded-full -mr-10 -mt-10 opacity-20" data-v-1b89f081></div></div></div><div class="h-1 bg-gradient-to-r from-orange-500 to-red-500" data-v-1b89f081></div></div><div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2" data-v-1b89f081><div class="p-6" data-v-1b89f081><div class="flex items-center justify-between" data-v-1b89f081><div class="flex-1" data-v-1b89f081><div class="flex items-center mb-4" data-v-1b89f081><div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300" data-v-1b89f081><i class="fas fa-check text-white text-2xl" data-v-1b89f081></i></div></div><dt class="text-sm font-medium text-gray-500 mb-2" data-v-1b89f081> \u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19\u0E41\u0E25\u0E49\u0E27 </dt><dd class="text-3xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent" data-v-1b89f081>${ssrInterpolate(((_e = __props.stats.completed_visits) == null ? void 0 : _e.toLocaleString()) || 0)}</dd><div class="mt-2 flex items-center text-xs text-emerald-600" data-v-1b89f081><i class="fas fa-trophy mr-1" data-v-1b89f081></i><span data-v-1b89f081>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</span></div></div><div class="absolute top-0 right-0 w-20 h-20 bg-emerald-100 rounded-full -mr-10 -mt-10 opacity-20" data-v-1b89f081></div></div></div><div class="h-1 bg-gradient-to-r from-emerald-500 to-teal-500" data-v-1b89f081></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/DashboardStats.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const DashboardStats = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-1b89f081"]]);

export { DashboardStats as default };
//# sourceMappingURL=DashboardStats-BsvXGw85.mjs.map
