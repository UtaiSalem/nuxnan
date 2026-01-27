import StudentInfoGrid from './StudentInfoGrid-VBS3PfCt.mjs';
import { resolveComponent, useSSRContext } from 'vue';
import { ssrRenderAttr, ssrInterpolate, ssrRenderComponent } from 'vue/server-renderer';
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
  name: "StudentCard",
  components: {
    StudentInfoGrid
  },
  props: {
    student: {
      type: Object,
      required: true
    }
  },
  emits: ["view-student", "create-visit"],
  data() {
    return {
      showPhotoModal: false
    };
  },
  methods: {
    getProfileImagePath(student) {
      if (!student.profile_image) return null;
      return `../../storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`;
    },
    formatDate(dateString) {
      if (!dateString) return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric"
      });
    },
    getEducationLevelText(level) {
      const levelMap = {
        1: "\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32",
        2: "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E15\u0E49\u0E19",
        3: "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E1B\u0E25\u0E32\u0E22",
        4: "\u0E2D\u0E38\u0E14\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32"
      };
      return levelMap[level] || `\u0E23\u0E30\u0E14\u0E31\u0E1A ${level}`;
    },
    openPhotoModal() {
      if (this.student.profile_image) {
        this.showPhotoModal = true;
      }
    },
    closePhotoModal() {
      this.showPhotoModal = false;
    }
  }
};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  var _a, _b, _c, _d, _e, _f, _g, _h, _i;
  const _component_StudentInfoGrid = resolveComponent("StudentInfoGrid");
  _push(`<!--[--><li class="px-3 py-4 hover:bg-gray-50 border-l-4 border-transparent hover:border-indigo-400 transition-all duration-200"><div class="block sm:hidden"><div class="space-y-3"><div class="flex items-start space-x-4"><div class="flex-shrink-0">`);
  if ($props.student.profile_image) {
    _push(`<div class="w-24 h-32 rounded-md overflow-hidden border-2 border-gray-200 shadow-lg cursor-pointer hover:shadow-xl transition-shadow"><img${ssrRenderAttr("src", $options.getProfileImagePath($props.student))}${ssrRenderAttr("alt", $props.student.first_name_th)} class="w-full h-full object-cover"></div>`);
  } else {
    _push(`<div class="w-24 h-32 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-md flex items-center justify-center border-2 border-gray-200 shadow-lg"><span class="text-white font-bold text-xl">${ssrInterpolate(((_a = $props.student.first_name_th) == null ? void 0 : _a.charAt(0)) || "N")}</span></div>`);
  }
  _push(`</div><div class="flex-1 min-w-0 flex flex-col justify-start pt-2"><div class="flex flex-col space-y-3"><div class="space-y-2">`);
  if ($props.student.title_prefix_th) {
    _push(`<div class="text-sm font-medium text-gray-600">${ssrInterpolate($props.student.title_prefix_th)}</div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<div class="grid grid-cols-1 gap-2 text-sm"><div class="flex"><span class="text-gray-500 font-medium w-12 flex-shrink-0">\u0E0A\u0E37\u0E48\u0E2D:</span><span class="font-bold text-gray-900 break-words flex-1">${ssrInterpolate($props.student.first_name_th)}</span></div><div class="flex"><span class="text-gray-500 font-medium w-16 flex-shrink-0">\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25:</span><span class="font-bold text-gray-900 break-words flex-1">${ssrInterpolate($props.student.last_name_th)}</span></div></div></div><div class="flex flex-wrap items-center gap-2">`);
  if ($props.student.nickname) {
    _push(`<div class="flex-shrink-0"><span class="inline-flex items-center px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full"><i class="fas fa-star mr-1"></i><span class="truncate max-w-20">${ssrInterpolate($props.student.nickname)}</span></span></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<div class="flex-shrink-0">`);
  if (((_b = $props.student.home_visits) == null ? void 0 : _b.length) > 0) {
    _push(`<div class="inline-flex items-center px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full"><i class="fas fa-check-circle mr-1"></i><span class="whitespace-nowrap">\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 ${ssrInterpolate($props.student.home_visits.length)} \u0E04\u0E23\u0E31\u0E49\u0E07</span></div>`);
  } else {
    _push(`<div class="inline-flex items-center px-2.5 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full"><i class="fas fa-clock mr-1"></i><span class="whitespace-nowrap">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E04\u0E22\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</span></div>`);
  }
  _push(`</div></div></div></div></div>`);
  _push(ssrRenderComponent(_component_StudentInfoGrid, { student: $props.student }, null, _parent));
  _push(`<div class="flex space-x-2"><button class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-indigo-300 text-sm font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"><i class="fas fa-user-graduate mr-2"></i> \u0E14\u0E39\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </button><button class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"><i class="fas fa-home mr-2"></i> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </button></div></div></div><div class="hidden sm:flex items-center justify-between"><div class="flex items-center flex-1"><div class="flex-shrink-0">`);
  if ($props.student.profile_image) {
    _push(`<div class="w-20 h-24 rounded-md overflow-hidden border border-gray-200 shadow-lg cursor-pointer hover:shadow-xl transition-shadow"><img${ssrRenderAttr("src", $options.getProfileImagePath($props.student))}${ssrRenderAttr("alt", $props.student.first_name_th)} class="w-full h-full object-cover"></div>`);
  } else {
    _push(`<div class="w-20 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-md flex items-center justify-center border border-gray-200 shadow-lg"><span class="text-white font-semibold text-lg">${ssrInterpolate(((_c = $props.student.first_name_th) == null ? void 0 : _c.charAt(0)) || "N")}</span></div>`);
  }
  _push(`</div><div class="ml-4 sm:ml-5 flex-1 min-w-0"><div class="flex flex-col gap-2">`);
  if ($props.student.title_prefix_th) {
    _push(`<div class="text-xs font-medium text-gray-600">${ssrInterpolate($props.student.title_prefix_th)}</div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<div class="flex flex-col gap-1"><div class="flex items-center"><span class="text-gray-500 font-medium text-xs w-12 flex-shrink-0">\u0E0A\u0E37\u0E48\u0E2D:</span><span class="font-semibold text-gray-900 text-sm break-words flex-1">${ssrInterpolate($props.student.first_name_th)}</span>`);
  if ($props.student.nickname) {
    _push(`<div class="ml-2 flex-shrink-0"><span class="inline-flex items-center text-xs text-purple-700 bg-purple-100 px-2 py-1 rounded-full font-medium"><i class="fas fa-star mr-1"></i><span class="truncate max-w-16">${ssrInterpolate($props.student.nickname)}</span></span></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div><div class="flex items-center"><span class="text-gray-500 font-medium text-xs w-12 flex-shrink-0">\u0E2A\u0E01\u0E38\u0E25:</span><span class="font-semibold text-gray-900 text-sm break-words flex-1">${ssrInterpolate($props.student.last_name_th)}</span></div></div></div><div class="mt-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-1 text-sm text-gray-500"><div class="flex items-center"><i class="fas fa-id-card w-4 mr-2"></i> \u0E23\u0E2B\u0E31\u0E2A: ${ssrInterpolate($props.student.student_id)}</div>`);
  if ($props.student.class_level) {
    _push(`<div class="flex items-center"><i class="fas fa-layer-group w-4 mr-2"></i> \u0E23\u0E30\u0E14\u0E31\u0E1A: \u0E21.${ssrInterpolate($props.student.class_level)}</div>`);
  } else {
    _push(`<!---->`);
  }
  if ($props.student.class_section) {
    _push(`<div class="flex items-center"><i class="fas fa-door-open w-4 mr-2"></i> \u0E2B\u0E49\u0E2D\u0E07: ${ssrInterpolate($props.student.class_section)}</div>`);
  } else {
    _push(`<!---->`);
  }
  if ((_d = $props.student.academic_info) == null ? void 0 : _d.current_class) {
    _push(`<div class="flex items-center"><i class="fas fa-graduation-cap w-4 mr-2"></i> \u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19: ${ssrInterpolate($props.student.academic_info.current_class)}</div>`);
  } else {
    _push(`<!---->`);
  }
  if ($props.student.citizen_id) {
    _push(`<div class="flex items-center"><i class="fas fa-credit-card w-4 mr-2"></i> \u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23: ${ssrInterpolate($props.student.citizen_id)}</div>`);
  } else {
    _push(`<!---->`);
  }
  if (((_e = $props.student.contacts) == null ? void 0 : _e.length) > 0) {
    _push(`<div class="flex items-center"><i class="fas fa-phone w-4 mr-2"></i> \u0E40\u0E1A\u0E2D\u0E23\u0E4C: ${ssrInterpolate($props.student.contacts[0].contact_value || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div><div class="mt-2 text-xs text-gray-400 space-x-3">`);
  if ((_f = $props.student.academic_info) == null ? void 0 : _f.school_name) {
    _push(`<span><i class="fas fa-school mr-1"></i> ${ssrInterpolate($props.student.academic_info.school_name)}</span>`);
  } else {
    _push(`<!---->`);
  }
  if ((_g = $props.student.academic_info) == null ? void 0 : _g.education_level) {
    _push(`<span><i class="fas fa-graduation-cap mr-1"></i> \u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32: ${ssrInterpolate($options.getEducationLevelText($props.student.academic_info.education_level))}</span>`);
  } else {
    _push(`<!---->`);
  }
  if ($props.student.class_level && $props.student.class_section) {
    _push(`<span><i class="fas fa-users mr-1"></i> \u0E21.${ssrInterpolate($props.student.class_level)}/${ssrInterpolate($props.student.class_section)}</span>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div>`);
  if (((_h = $props.student.home_visits) == null ? void 0 : _h.length) > 0) {
    _push(`<div class="mt-2 text-xs"><div class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 rounded"><i class="fas fa-home mr-1"></i> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14: ${ssrInterpolate($options.formatDate($props.student.home_visits[0].visit_date))}</div></div>`);
  } else {
    _push(`<div class="mt-2 text-xs"><div class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-700 rounded"><i class="fas fa-exclamation-circle mr-1"></i> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E04\u0E22\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </div></div>`);
  }
  _push(`</div></div><div class="flex flex-col items-end space-y-2 ml-4">`);
  if (((_i = $props.student.home_visits) == null ? void 0 : _i.length) > 0) {
    _push(`<div class="text-xs text-center"><span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-full"><i class="fas fa-chart-line mr-1"></i> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E41\u0E25\u0E49\u0E27 ${ssrInterpolate($props.student.home_visits.length)} \u0E04\u0E23\u0E31\u0E49\u0E07 </span></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<div class="flex flex-col space-y-1"><button class="inline-flex items-center px-3 py-2 border border-indigo-300 shadow-sm text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"><i class="fas fa-user-graduate mr-2"></i> \u0E14\u0E39\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </button><button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"><i class="fas fa-home mr-2"></i> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </button></div></div></div></li>`);
  if ($data.showPhotoModal) {
    _push(`<div class="fixed inset-0 z-50 overflow-y-auto"><div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"><div class="fixed inset-0 transition-opacity bg-black bg-opacity-75"></div><div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"><div class="bg-gray-50 px-4 py-3 sm:px-6 flex items-center justify-between border-b"><h3 class="text-lg leading-6 font-medium text-gray-900"> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E ${ssrInterpolate($props.student.title_prefix_th)} ${ssrInterpolate($props.student.first_name_th)} ${ssrInterpolate($props.student.last_name_th)}</h3><button class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div class="bg-white px-4 py-6 sm:px-6"><div class="flex justify-center"><img${ssrRenderAttr("src", $options.getProfileImagePath($props.student))}${ssrRenderAttr("alt", `${$props.student.first_name_th} ${$props.student.last_name_th}`)} class="max-w-full max-h-96 object-contain rounded-lg shadow-lg"></div></div><div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t"><button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"> \u0E1B\u0E34\u0E14 </button></div></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<!--]-->`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Teacher/Components/StudentCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);

export { StudentCard as default };
//# sourceMappingURL=StudentCard-BOGCzgEZ.mjs.map
