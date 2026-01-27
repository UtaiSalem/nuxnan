import { mergeProps, ref, computed, useSSRContext } from 'vue';
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
  name: "StudentManagement",
  props: {
    students: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const studentsData = ref(props.students || []);
    const activeStudentsCount = computed(() => {
      return studentsData.value.filter((student) => student.status === "active").length;
    });
    const pendingVisitCount = computed(() => {
      return studentsData.value.filter((student) => student.visit_status === "pending").length;
    });
    const visitCompletionRate = computed(() => {
      const totalActive = studentsData.value.filter((student) => student.status === "active").length;
      const completedVisits = studentsData.value.filter((student) => student.visit_status === "completed").length;
      return totalActive > 0 ? Math.round(completedVisits / totalActive * 100) : 0;
    });
    return {
      studentsData,
      activeStudentsCount,
      pendingVisitCount,
      visitCompletionRate
    };
  }
};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  var _a;
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "student-management" }, _attrs))} data-v-92a8049c><div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6" data-v-92a8049c><div class="bg-white overflow-hidden shadow rounded-lg" data-v-92a8049c><div class="p-5" data-v-92a8049c><div class="flex items-center" data-v-92a8049c><div class="flex-shrink-0" data-v-92a8049c><div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center" data-v-92a8049c><i class="fas fa-users text-white text-sm" data-v-92a8049c></i></div></div><div class="ml-5 w-0 flex-1" data-v-92a8049c><dl data-v-92a8049c><dt class="text-sm font-medium text-gray-500 truncate" data-v-92a8049c> \u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </dt><dd class="text-lg font-medium text-gray-900" data-v-92a8049c>${ssrInterpolate(((_a = $setup.studentsData) == null ? void 0 : _a.length) || 0)}</dd></dl></div></div></div></div><div class="bg-white overflow-hidden shadow rounded-lg" data-v-92a8049c><div class="p-5" data-v-92a8049c><div class="flex items-center" data-v-92a8049c><div class="flex-shrink-0" data-v-92a8049c><div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center" data-v-92a8049c><i class="fas fa-check text-white text-sm" data-v-92a8049c></i></div></div><div class="ml-5 w-0 flex-1" data-v-92a8049c><dl data-v-92a8049c><dt class="text-sm font-medium text-gray-500 truncate" data-v-92a8049c> \u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </dt><dd class="text-lg font-medium text-gray-900" data-v-92a8049c>${ssrInterpolate($setup.activeStudentsCount)}</dd></dl></div></div></div></div><div class="bg-white overflow-hidden shadow rounded-lg" data-v-92a8049c><div class="p-5" data-v-92a8049c><div class="flex items-center" data-v-92a8049c><div class="flex-shrink-0" data-v-92a8049c><div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center" data-v-92a8049c><i class="fas fa-clock text-white text-sm" data-v-92a8049c></i></div></div><div class="ml-5 w-0 flex-1" data-v-92a8049c><dl data-v-92a8049c><dt class="text-sm font-medium text-gray-500 truncate" data-v-92a8049c> \u0E23\u0E2D\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </dt><dd class="text-lg font-medium text-gray-900" data-v-92a8049c>${ssrInterpolate($setup.pendingVisitCount)}</dd></dl></div></div></div></div><div class="bg-white overflow-hidden shadow rounded-lg" data-v-92a8049c><div class="p-5" data-v-92a8049c><div class="flex items-center" data-v-92a8049c><div class="flex-shrink-0" data-v-92a8049c><div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center" data-v-92a8049c><i class="fas fa-percentage text-white text-sm" data-v-92a8049c></i></div></div><div class="ml-5 w-0 flex-1" data-v-92a8049c><dl data-v-92a8049c><dt class="text-sm font-medium text-gray-500 truncate" data-v-92a8049c> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E41\u0E25\u0E49\u0E27 </dt><dd class="text-lg font-medium text-gray-900" data-v-92a8049c>${ssrInterpolate($setup.visitCompletionRate)}% </dd></dl></div></div></div></div></div><div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center" data-v-92a8049c><div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4" data-v-92a8049c><i class="fas fa-tools text-yellow-600 text-xl" data-v-92a8049c></i></div><h3 class="text-lg font-medium text-gray-900 mb-2" data-v-92a8049c> \u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32 </h3><p class="text-sm text-gray-600 mb-4" data-v-92a8049c> \u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E01\u0E32\u0E23\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E22\u0E31\u0E07\u0E2D\u0E22\u0E39\u0E48\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32<br data-v-92a8049c> \u0E08\u0E30\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E2B\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E43\u0E19\u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49 </p><div class="text-xs text-gray-500" data-v-92a8049c><p data-v-92a8049c>\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E17\u0E35\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32:</p><ul class="mt-2 space-y-1" data-v-92a8049c><li data-v-92a8049c>\u2713 \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E01\u0E23\u0E2D\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</li><li data-v-92a8049c>\u2713 \u0E40\u0E1E\u0E34\u0E48\u0E21/\u0E41\u0E01\u0E49\u0E44\u0E02/\u0E25\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</li><li data-v-92a8049c>\u2713 \u0E19\u0E33\u0E40\u0E02\u0E49\u0E32\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E08\u0E32\u0E01 Excel/CSV</li><li data-v-92a8049c>\u2713 \u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</li><li data-v-92a8049c>\u2713 \u0E14\u0E39\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</li><li data-v-92a8049c>\u2713 \u0E01\u0E32\u0E23\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E41\u0E1A\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21 (Bulk Operations)</li></ul></div></div></div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/StudentManagement.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentManagement = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-92a8049c"]]);

export { StudentManagement as default };
//# sourceMappingURL=StudentManagement-BQ1M95q4.mjs.map
