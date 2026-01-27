import { ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList } from 'vue/server-renderer';
import { H as Head } from './inertia-vue3-CWdJjaLG.mjs';
import StudentsCard from './StudentsCard-CUu90lC-.mjs';
import _sfc_main$1 from './AcademicInfoCard-DeAtPXoT.mjs';
import _sfc_main$2 from './AddressCard-C5OO_9mi.mjs';
import _sfc_main$3 from './ContactCard-DNJ688tb.mjs';
import _sfc_main$4 from './HealthInfoCard-CRvifp0i.mjs';
import _sfc_main$5 from './GuardianCard-C6LWpJ3F.mjs';
import './server.mjs';
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
import './dateUtils-DQlkT5wi.mjs';
import './useStudentRoutes-BWKsyL_y.mjs';

const _sfc_main = {
  __name: "Profile",
  __ssrInlineRender: true,
  props: {
    student: {
      type: Object,
      required: true,
      validator: (value) => value && typeof value === "object"
    },
    studentCard: {
      type: Object,
      default: () => ({})
    },
    homeVisits: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const isSubmitting = ref(false);
    const lastSaved = ref(null);
    const saveMessage = ref("");
    const studentData = computed(() => props.student || {});
    computed(() => {
      var _a;
      return ((_a = studentData.value.addresses) == null ? void 0 : _a[0]) || {};
    });
    computed(() => {
      var _a;
      return ((_a = studentData.value.contacts) == null ? void 0 : _a[0]) || {};
    });
    const primaryGuardian = computed(() => {
      var _a;
      return ((_a = studentData.value.guardians) == null ? void 0 : _a[0]) || {};
    });
    computed(() => {
      var _a;
      return ((_a = primaryGuardian.value.contacts) == null ? void 0 : _a[0]) || {};
    });
    computed(() => studentData.value.academic_info || {});
    computed(() => studentData.value.health_info || {});
    const handleCardSave = async (cardType, result) => {
      try {
        isSubmitting.value = true;
        const cardNames = {
          student: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19",
          academic: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32",
          address: "\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48",
          contact: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D",
          health: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E38\u0E02\u0E20\u0E32\u0E1E",
          guardian: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1C\u0E39\u0E49\u0E1B\u0E01\u0E04\u0E23\u0E2D\u0E07"
          // documents: 'เอกสาร' // Disabled for future development
        };
        const cardName = cardNames[cardType] || cardType;
        if (result.success) {
          lastSaved.value = /* @__PURE__ */ new Date();
          saveMessage.value = `\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01${cardName}\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08`;
          setTimeout(() => {
            saveMessage.value = "";
          }, 3e3);
        } else {
          saveMessage.value = `\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01${cardName}: ${result.message || "\u0E44\u0E21\u0E48\u0E17\u0E23\u0E32\u0E1A\u0E2A\u0E32\u0E40\u0E2B\u0E15\u0E38"}`;
        }
      } catch (error) {
        saveMessage.value = `\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E17\u0E35\u0E48\u0E44\u0E21\u0E48\u0E04\u0E32\u0E14\u0E04\u0E34\u0E14: ${error.message}`;
      } finally {
        isSubmitting.value = false;
      }
    };
    const fullName = computed(() => {
      const first = studentData.value.first_name_th || "";
      const last = studentData.value.last_name_th || "";
      return `${first} ${last}`.trim();
    });
    const userInitial = computed(() => {
      var _a;
      return ((_a = studentData.value.first_name_th) == null ? void 0 : _a.charAt(0)) || "S";
    });
    const hasHomeVisits = computed(() => {
      return props.homeVisits && props.homeVisits.length > 0;
    });
    computed(() => {
      return !isSubmitting.value;
    });
    const statusTextMap = /* @__PURE__ */ new Map([
      ["pending", "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23"],
      ["completed", "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19"],
      ["cancelled", "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"]
    ]);
    const getStatusText = (status) => {
      return statusTextMap.get(status) || status;
    };
    const formatDate = (date) => {
      if (!date) return "";
      if (!formatDate._formatter) {
        formatDate._formatter = new Intl.DateTimeFormat("th-TH", {
          year: "numeric",
          month: "long",
          day: "numeric"
        });
      }
      try {
        return formatDate._formatter.format(new Date(date));
      } catch (error) {
        console.warn("Date formatting error:", error);
        return date;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50" }, _attrs))}>`);
      _push(ssrRenderComponent(unref(Head), { title: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 - \u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19" }, null, _parent));
      _push(`<nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50"><div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8"><div class="flex justify-between items-center h-14 sm:h-16"><div class="flex items-center min-w-0 flex-1"><div class="flex items-center"><div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v0a2 2 0 002-2h10"></path></svg></div><div class="ml-2 sm:ml-3 min-w-0 flex-1"><h1 class="text-sm sm:text-base lg:text-xl font-bold text-gray-900 truncate"> \u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h1><p class="text-xs sm:text-sm text-gray-500 truncate sm:hidden"> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 </p></div></div></div><div class="flex items-center space-x-2 sm:space-x-4"><div class="flex items-center space-x-2"><div class="w-6 h-6 sm:w-8 sm:h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full flex items-center justify-center flex-shrink-0"><span class="text-white text-xs sm:text-sm font-medium">${ssrInterpolate(userInitial.value)}</span></div><div class="hidden sm:block text-right min-w-0"><p class="text-xs sm:text-sm font-medium text-gray-900 truncate max-w-24 sm:max-w-32 lg:max-w-none">${ssrInterpolate(fullName.value)}</p><p class="text-xs text-gray-500">\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><button class="inline-flex items-center px-2 py-1.5 sm:px-4 sm:py-2 border border-transparent text-xs sm:text-sm font-medium rounded-lg text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-md hover:shadow-lg"><svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg><span class="hidden sm:inline">\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A</span></button></div></div></div></nav><div class="max-w-7xl mx-auto pb-4 sm:pb-6 lg:py-8 px-3 sm:px-4 lg:px-8"><div class="mb-6 sm:mb-8"><div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden"><div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-6 sm:px-6 sm:py-8"><div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0"><div class="w-12 h-12 sm:w-16 sm:h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center flex-shrink-0"><svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg></div><div class="sm:ml-6"><h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white"> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h1><p class="text-blue-100 mt-1 text-sm sm:text-base"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E41\u0E25\u0E30\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 </p><div class="mt-3 sm:hidden"><nav class="flex" aria-label="Breadcrumb"><ol class="inline-flex items-center space-x-1"><li class="inline-flex items-center"><span class="text-xs font-medium text-blue-200">\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01</span></li><li><div class="flex items-center"><svg class="w-3 h-3 text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg><span class="ml-1 text-xs font-medium text-white">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</span></div></li></ol></nav></div></div></div></div></div></div>`);
      if (saveMessage.value) {
        _push(`<div class="${ssrRenderClass([{
          "bg-green-50 border-green-400 text-green-700": saveMessage.value.includes("\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08"),
          "bg-red-50 border-red-400 text-red-700": saveMessage.value.includes("\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"),
          "bg-blue-50 border-blue-400 text-blue-700": saveMessage.value.includes("\u0E01\u0E33\u0E25\u0E31\u0E07")
        }, "mb-6 p-4 rounded-lg border-l-4 transition-all duration-300"])}"><div class="flex items-center">`);
        if (saveMessage.value.includes("\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08")) {
          _push(`<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`);
        } else if (saveMessage.value.includes("\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14")) {
          _push(`<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>`);
        } else {
          _push(`<svg class="w-5 h-5 mr-2 animate-spin" fill="currentColor" viewBox="0 0 20 20"><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
        }
        _push(`<span class="font-medium">${ssrInterpolate(saveMessage.value)}</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="space-y-4 sm:space-y-6">`);
      _push(ssrRenderComponent(unref(StudentsCard), {
        student: __props.student,
        "student-card": __props.studentCard,
        onSave: (result) => handleCardSave("student", result),
        "is-loading": isSubmitting.value
      }, null, _parent));
      _push(ssrRenderComponent(unref(_sfc_main$1), {
        student: __props.student,
        onSave: (result) => handleCardSave("academic_info", result),
        "is-loading": isSubmitting.value
      }, null, _parent));
      _push(ssrRenderComponent(unref(_sfc_main$2), {
        student: __props.student,
        onSave: (result) => handleCardSave("address", result),
        "is-loading": isSubmitting.value
      }, null, _parent));
      _push(ssrRenderComponent(unref(_sfc_main$3), {
        student: __props.student,
        onSave: (result) => handleCardSave("contact", result),
        "is-loading": isSubmitting.value
      }, null, _parent));
      _push(ssrRenderComponent(unref(_sfc_main$4), {
        student: __props.student,
        onSave: (result) => handleCardSave("health", result),
        "is-loading": isSubmitting.value
      }, null, _parent));
      _push(ssrRenderComponent(unref(_sfc_main$5), {
        student: __props.student,
        onSave: (result) => handleCardSave("guardian", result),
        "is-loading": isSubmitting.value
      }, null, _parent));
      _push(`</div>`);
      if (hasHomeVisits.value) {
        _push(`<div class="mt-8 sm:mt-12 bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden"><div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-3 sm:px-6 sm:py-4"><div class="flex items-center"><div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg></div><div class="ml-2 sm:ml-3 min-w-0 flex-1"><h3 class="text-base sm:text-lg font-semibold text-white truncate"> \u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h3><p class="text-indigo-100 text-xs sm:text-sm truncate">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div></div><div class="p-4 sm:p-6"><div class="space-y-4"><!--[-->`);
        ssrRenderList(__props.homeVisits, (visit) => {
          _push(`<div class="bg-gray-50 rounded-lg p-4 border border-gray-200"><div class="flex items-center justify-between"><div><h4 class="font-medium text-gray-900">${ssrInterpolate(formatDate(visit.visit_date))}</h4><p class="text-sm text-gray-600">${ssrInterpolate(visit.purpose || "\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19")}</p></div><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">${ssrInterpolate(getStatusText(visit.status))}</span></div>`);
          if (visit.notes) {
            _push(`<div class="mt-2"><p class="text-sm text-gray-700">${ssrInterpolate(visit.notes)}</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Student/Profile.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Profile-CnU9MBtO.mjs.map
