import { mergeProps, useSSRContext } from 'vue';
import { r as router } from './inertia-vue3-CWdJjaLG.mjs';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';
import 'unhead/utils';
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

const _sfc_main = {
  props: {
    student: Object
  },
  setup(props) {
    const goBack = () => {
      router.visit(route("homevisit.teacher.dashboard"));
    };
    const logout = () => {
      router.post(route("homevisit.logout"));
    };
    const createHomeVisit = () => {
      router.visit(route("homevisit.teacher.create.home.visit", props.student.id));
    };
    const formatDate = (dateString) => {
      if (!dateString) return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    };
    const getGenderText = (gender) => {
      const genderMap = {
        1: "\u0E0A\u0E32\u0E22",
        2: "\u0E2B\u0E0D\u0E34\u0E07",
        "male": "\u0E0A\u0E32\u0E22",
        "female": "\u0E2B\u0E0D\u0E34\u0E07"
      };
      return genderMap[gender] || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
    };
    const getEducationLevelText = (level) => {
      const levelMap = {
        1: "\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32",
        2: "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E15\u0E49\u0E19",
        3: "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E1B\u0E25\u0E32\u0E22",
        4: "\u0E2D\u0E38\u0E14\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32"
      };
      return levelMap[level] || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
    };
    const getContactTypeText = (type) => {
      const typeMap = {
        "phone": "\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C",
        "mobile": "\u0E21\u0E37\u0E2D\u0E16\u0E37\u0E2D",
        "email": "\u0E2D\u0E35\u0E40\u0E21\u0E25",
        "line": "LINE ID",
        "facebook": "Facebook"
      };
      return typeMap[type] || type;
    };
    const getContactIcon = (type) => {
      const iconMap = {
        "phone": "fas fa-phone",
        "mobile": "fas fa-mobile-alt",
        "email": "fas fa-envelope",
        "line": "fab fa-line",
        "facebook": "fab fa-facebook"
      };
      return iconMap[type] || "fas fa-address-book";
    };
    const getProfileImagePath = (student) => {
      if (!student.profile_image) return null;
      return `../../storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`;
    };
    return {
      goBack,
      logout,
      createHomeVisit,
      formatDate,
      getGenderText,
      getEducationLevelText,
      getContactTypeText,
      getContactIcon,
      getProfileImagePath
    };
  }
};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  var _a;
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50" }, _attrs))}><nav class="bg-white shadow-sm border-b"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="flex justify-between h-16"><div class="flex items-center"><div class="flex-shrink-0 flex items-center"><button class="text-gray-500 hover:text-gray-700 mr-4"><i class="fas fa-arrow-left"></i></button><span class="text-2xl">\u{1F468}\u200D\u{1F393}</span><h1 class="ml-2 text-xl font-semibold text-gray-900"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h1></div></div><div class="flex items-center space-x-4"><span class="text-sm text-gray-600"> \u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35, \u0E04\u0E23\u0E39 </span><button class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"> \u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A </button></div></div></div></nav><div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"><div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6"><div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-indigo-500 to-purple-600"><div class="flex items-center"><div class="flex-shrink-0">`);
  if ($props.student.profile_image) {
    _push(`<div class="w-20 h-20 rounded-full overflow-hidden border-4 border-white"><img${ssrRenderAttr("src", $setup.getProfileImagePath($props.student))}${ssrRenderAttr("alt", $props.student.first_name_th)} class="w-full h-full object-cover"></div>`);
  } else {
    _push(`<div class="w-20 h-20 bg-white rounded-full flex items-center justify-center border-4 border-white"><span class="text-indigo-600 font-bold text-2xl">${ssrInterpolate(((_a = $props.student.first_name_th) == null ? void 0 : _a.charAt(0)) || "N")}</span></div>`);
  }
  _push(`</div><div class="ml-6"><h3 class="text-2xl leading-8 font-bold text-white">${ssrInterpolate($props.student.title_prefix_th || "")} ${ssrInterpolate($props.student.first_name_th)} ${ssrInterpolate($props.student.last_name_th)}</h3>`);
  if ($props.student.nickname) {
    _push(`<div class="mt-1"><span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white"><i class="fas fa-star mr-1"></i> ${ssrInterpolate($props.student.nickname)}</span></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<p class="mt-2 text-indigo-100"> \u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19: ${ssrInterpolate($props.student.student_id)}</p></div></div></div></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6"><div class="bg-white shadow overflow-hidden sm:rounded-lg"><div class="px-4 py-5 sm:px-6"><h3 class="text-lg leading-6 font-medium text-gray-900"><i class="fas fa-user mr-2"></i> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </p></div><div class="border-t border-gray-200"><dl><div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E15\u0E47\u0E21</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($props.student.title_prefix_th)} ${ssrInterpolate($props.student.first_name_th)} ${ssrInterpolate($props.student.last_name_th)}</dd></div><div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E48\u0E19</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($props.student.nickname || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</dd></div><div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($props.student.student_id)}</dd></div><div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($props.student.citizen_id || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</dd></div><div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($setup.formatDate($props.student.date_of_birth))}</dd></div><div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E40\u0E1E\u0E28</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($setup.getGenderText($props.student.gender))}</dd></div></dl></div></div><div class="bg-white shadow overflow-hidden sm:rounded-lg"><div class="px-4 py-5 sm:px-6"><h3 class="text-lg leading-6 font-medium text-gray-900"><i class="fas fa-graduation-cap mr-2"></i> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32 </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E14\u0E49\u0E32\u0E19\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32\u0E41\u0E25\u0E30\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </p></div>`);
  if ($props.student.academic_info) {
    _push(`<div class="border-t border-gray-200"><dl><div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E0A\u0E31\u0E49\u0E19\u0E40\u0E23\u0E35\u0E22\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($props.student.academic_info.current_class || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</dd></div><div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($props.student.academic_info.school_name || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</dd></div><div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($setup.getEducationLevelText($props.student.academic_info.education_level))}</dd></div><div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"><dt class="text-sm font-medium text-gray-500">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</dt><dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">${ssrInterpolate($setup.formatDate($props.student.enrollment_date))}</dd></div></dl></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div></div>`);
  if ($props.student.contacts && $props.student.contacts.length > 0) {
    _push(`<div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg"><div class="px-4 py-5 sm:px-6"><h3 class="text-lg leading-6 font-medium text-gray-900"><i class="fas fa-phone mr-2"></i> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E0A\u0E48\u0E2D\u0E07\u0E17\u0E32\u0E07\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </p></div><div class="border-t border-gray-200"><ul class="divide-y divide-gray-200"><!--[-->`);
    ssrRenderList($props.student.contacts, (contact) => {
      _push(`<li class="px-4 py-4"><div class="flex items-center justify-between"><div class="flex items-center"><div class="flex-shrink-0"><i class="${ssrRenderClass([$setup.getContactIcon(contact.contact_type), "text-gray-400"])}"></i></div><div class="ml-4"><p class="text-sm font-medium text-gray-900">${ssrInterpolate($setup.getContactTypeText(contact.contact_type))}</p><p class="text-sm text-gray-500">${ssrInterpolate(contact.contact_value)}</p></div></div></div></li>`);
    });
    _push(`<!--]--></ul></div></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`<div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg"><div class="px-4 py-5 sm:px-6"><div class="flex items-center justify-between"><div><h3 class="text-lg leading-6 font-medium text-gray-900"><i class="fas fa-home mr-2"></i> \u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </p></div><button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"><i class="fas fa-plus mr-2"></i> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </button></div></div><div class="border-t border-gray-200">`);
  if ($props.student.home_visits && $props.student.home_visits.length > 0) {
    _push(`<div><ul class="divide-y divide-gray-200"><!--[-->`);
    ssrRenderList($props.student.home_visits, (visit) => {
      _push(`<li class="px-4 py-4 hover:bg-gray-50"><div class="flex items-center justify-between"><div class="flex-1"><div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-900">${ssrInterpolate(visit.visit_purpose || "\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19")}</p><p class="text-sm text-gray-500">${ssrInterpolate($setup.formatDate(visit.visit_date))}</p></div><p class="mt-1 text-sm text-gray-600">${ssrInterpolate(visit.visit_notes || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14")}</p><p class="mt-1 text-xs text-gray-400"> \u0E42\u0E14\u0E22: ${ssrInterpolate(visit.teacher_name || "\u0E04\u0E23\u0E39")}</p></div></div></li>`);
    });
    _push(`<!--]--></ul></div>`);
  } else {
    _push(`<div class="text-center py-12"><i class="fas fa-home text-4xl text-gray-400 mb-4"></i><h3 class="text-lg font-medium text-gray-900 mb-2">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</h3><p class="text-gray-500">\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E04\u0E23\u0E31\u0E49\u0E07\u0E41\u0E23\u0E01\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E04\u0E19\u0E19\u0E35\u0E49</p></div>`);
  }
  _push(`</div></div></div></div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Teacher/ManageStudent.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ManageStudent = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);

export { ManageStudent as default };
//# sourceMappingURL=ManageStudent-DUXFFCzs.mjs.map
