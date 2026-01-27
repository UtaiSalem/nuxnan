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
  name: "StudentInfoGrid",
  props: {
    student: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric"
      });
    }
  }
};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  var _a, _b;
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 space-y-3 border border-gray-200" }, _attrs))}><div class="grid grid-cols-2 gap-3"><div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-id-card text-blue-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p><p class="text-sm font-bold text-gray-900 break-all">${ssrInterpolate($props.student.student_id)}</p></div></div></div>`);
  if ($props.student.class_level) {
    _push(`<div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-layer-group text-emerald-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</p><p class="text-sm font-bold text-gray-900">\u0E21.${ssrInterpolate($props.student.class_level)}</p></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div><div class="grid grid-cols-2 gap-3">`);
  if ($props.student.class_section) {
    _push(`<div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-graduation-cap text-green-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E2B\u0E49\u0E2D\u0E07</p><p class="text-sm font-bold text-gray-900">${ssrInterpolate($props.student.class_section)}</p></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  if ((_a = $props.student.academic_info) == null ? void 0 : _a.current_class) {
    _push(`<div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-chalkboard-teacher text-teal-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</p><p class="text-sm font-bold text-gray-900 break-words">${ssrInterpolate($props.student.academic_info.current_class)}</p></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div>`);
  if ($props.student.citizen_id || $props.student.contacts && $props.student.contacts.length > 0) {
    _push(`<div class="grid grid-cols-2 gap-3">`);
    if ($props.student.citizen_id) {
      _push(`<div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-credit-card text-purple-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23</p><p class="text-xs font-bold text-gray-900 break-all">${ssrInterpolate($props.student.citizen_id)}</p></div></div></div>`);
    } else {
      _push(`<!---->`);
    }
    if ($props.student.contacts && $props.student.contacts.length > 0) {
      _push(`<div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-phone text-yellow-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23</p><p class="text-xs font-bold text-gray-900 break-all">${ssrInterpolate($props.student.contacts[0].contact_value || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</p></div></div></div>`);
    } else {
      _push(`<!---->`);
    }
    _push(`</div>`);
  } else {
    _push(`<!---->`);
  }
  if (((_b = $props.student.home_visits) == null ? void 0 : _b.length) > 0) {
    _push(`<div class="bg-white rounded-lg p-3 shadow-sm"><div class="flex items-center space-x-2"><div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-calendar text-indigo-600 text-sm"></i></div><div class="min-w-0 flex-1"><p class="text-xs text-gray-500 font-medium">\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</p><p class="text-sm font-bold text-gray-900 break-words">${ssrInterpolate($options.formatDate($props.student.home_visits[0].visit_date))}</p></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Teacher/Components/StudentInfoGrid.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentInfoGrid = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);

export { StudentInfoGrid as default };
//# sourceMappingURL=StudentInfoGrid-VBS3PfCt.mjs.map
