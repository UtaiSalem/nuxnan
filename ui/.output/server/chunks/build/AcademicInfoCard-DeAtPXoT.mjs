import { ref, watch, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { useStudentRoutes } from './useStudentRoutes-BWKsyL_y.mjs';
import './inertia-vue3-CWdJjaLG.mjs';
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

const _sfc_main = {
  __name: "AcademicInfoCard",
  __ssrInlineRender: true,
  props: {
    student: {
      type: Object,
      required: true
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    context: {
      type: String,
      default: "student"
      // 'student' or 'teacher'
    }
  },
  emits: ["save", "update"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const { academicInfoRoutes } = useStudentRoutes(props.context);
    ref(null);
    const academicRecords = ref([]);
    const isSaving = ref(false);
    const showModal = ref(false);
    const modalMode = ref("add");
    ref(null);
    ref(-1);
    ref(null);
    const error = ref(null);
    const formData = ref({
      academic_year: "",
      class_level: "",
      education_level: 2,
      // ค่าเริ่มต้นเป็นมัธยมศึกษา (2)
      class_section: "",
      school_name: "\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E23\u0E34\u0E22\u0E18\u0E23\u0E23\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34",
      school_address: "\u0E2B\u0E21\u0E39\u0E48 8 \u0E15\u0E33\u0E1A\u0E25\u0E2A\u0E30\u0E01\u0E2D\u0E21 \u0E2D\u0E33\u0E40\u0E20\u0E2D\u0E08\u0E30\u0E19\u0E30",
      school_province: "\u0E2A\u0E07\u0E02\u0E25\u0E32",
      enrollment_date: "",
      graduation_date: "",
      status: "studying",
      transfer_reason: "",
      notes: "",
      isCurrent: false
    });
    watch(() => formData.value.class_level, (newValue, oldValue) => {
      if (newValue && newValue !== oldValue) {
        const detectedLevel = detectEducationLevel(newValue);
        if (formData.value.education_level !== detectedLevel) {
          formData.value.education_level = detectedLevel;
        }
      }
    });
    const getStatusText = (status) => {
      const statusMap = {
        studying: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E28\u0E36\u0E01\u0E29\u0E32",
        graduated: "\u0E08\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32",
        transferred: "\u0E22\u0E49\u0E32\u0E22\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19",
        dropped: "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A"
      };
      return statusMap[status] || status;
    };
    const getEducationLevelText = (level) => {
      const levelMap = {
        0: "\u0E2D\u0E19\u0E38\u0E1A\u0E32\u0E25",
        1: "\u0E1B\u0E23\u0E30\u0E16\u0E21",
        2: "\u0E21\u0E31\u0E18\u0E22\u0E21"
      };
      return levelMap[level] || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
    };
    const detectEducationLevel = (classLevel) => {
      if (!classLevel) return 2;
      const level = classLevel.toString().toLowerCase();
      if (level.includes("\u0E2D.") || level.includes("\u0E01.")) {
        return 0;
      }
      if (level.includes("\u0E1B.")) {
        return 1;
      }
      if (level.includes("\u0E21.")) {
        return 2;
      }
      return 2;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden" }, _attrs))}><div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-4 py-3 sm:px-6 sm:py-4"><div class="flex items-center justify-between"><div class="flex items-center"><div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg></div><div class="ml-2 sm:ml-3 min-w-0 flex-1"><h3 class="text-base sm:text-lg font-semibold text-white truncate"> \u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32 (${ssrInterpolate(academicRecords.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </h3><p class="text-blue-100 text-xs sm:text-sm">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><button class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs sm:text-sm font-medium rounded-lg transition-all duration-200"><svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </button></div></div>`);
      if (error.value && !isSaving.value) {
        _push(`<div class="p-4 bg-red-50 border-l-4 border-red-400"><div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg></div><div class="ml-3"><p class="text-sm text-red-700">${ssrInterpolate(error.value)}</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="overflow-hidden"><div class="hidden md:block overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E2B\u0E49\u0E2D\u0E07</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E28\u0E36\u0E01\u0E29\u0E32</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><!--[-->`);
      ssrRenderList(academicRecords.value, (record, index) => {
        _push(`<tr class="${ssrRenderClass({
          "bg-blue-50": record.isNew,
          "bg-green-50": record.isCurrent,
          "bg-gray-50": record.isFromAPI && !record.isCurrent,
          "hover:bg-gray-50": !record.isNew && !record.isCurrent
        })}"><td class="px-4 py-3 whitespace-nowrap"><div class="flex items-center"><input${ssrRenderAttr("value", record.academic_year)} type="text"${ssrIncludeBooleanAttr(record.isFromAPI && !record.isCurrent) ? " readonly" : ""} class="${ssrRenderClass([
          "w-full border-0 bg-transparent focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded px-2 py-1 text-sm placeholder-gray-400",
          record.isFromAPI && !record.isCurrent ? "text-gray-600 cursor-default" : ""
        ])}" placeholder="2567">`);
        if (record.isCurrent) {
          _push(`<span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"> \u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(getEducationLevelText(record.education_level))}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.class_level || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.class_section || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.enrollment_date || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.school_name || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.school_province || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="${ssrRenderClass([{
          "bg-green-100 text-green-800": record.status === "studying",
          "bg-blue-100 text-blue-800": record.status === "graduated",
          "bg-yellow-100 text-yellow-800": record.status === "transferred",
          "bg-red-100 text-red-800": record.status === "dropped",
          "bg-gray-100 text-gray-800": !record.status
        }, "inline-flex px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getStatusText(record.status) || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap text-center"><div class="flex items-center justify-center space-x-2">`);
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-blue-600 hover:text-blue-800 disabled:opacity-50" title="\u0E41\u0E01\u0E49\u0E44\u0E02"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        if (record.isCurrent || record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-red-600 hover:text-red-800 disabled:opacity-50" title="\u0E25\u0E1A"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>`);
        } else {
          _push(`<!--[-->`);
          if (record.isFromAPI) {
            _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-blue-600 hover:text-blue-800 disabled:opacity-50 mr-2" title="\u0E15\u0E31\u0E49\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 713.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg></button>`);
          } else {
            _push(`<span class="text-xs text-gray-400" title="\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34 (\u0E14\u0E39\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19)"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></span>`);
          }
          _push(`<!--]-->`);
        }
        _push(`</div></td></tr>`);
      });
      _push(`<!--]-->`);
      if (academicRecords.value.length === 0) {
        _push(`<tr><td colspan="10" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg><p class="text-sm">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</p><p class="text-xs text-gray-400 mt-1">\u0E04\u0E25\u0E34\u0E01\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p></div></td></tr>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tbody></table></div><div class="md:hidden space-y-4 p-4"><!--[-->`);
      ssrRenderList(academicRecords.value, (record, index) => {
        _push(`<div class="${ssrRenderClass(["bg-white rounded-lg border", record.isNew ? "border-blue-300 bg-blue-50" : "border-gray-200"])}"><div class="p-4 space-y-3"><div class="flex items-center space-x-3"><label class="text-xs font-medium text-gray-500 w-20">\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32:</label><span class="flex-1 text-sm text-gray-900">${ssrInterpolate(record.academic_year || "-")}</span></div><div class="flex items-center space-x-3"><label class="text-xs font-medium text-gray-500 w-20">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32:</label><span class="flex-1 text-sm text-gray-900">${ssrInterpolate(getEducationLevelText(record.education_level))}</span></div><div class="grid grid-cols-2 gap-3"><div class="flex items-center space-x-2"><label class="text-xs font-medium text-gray-500">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19:</label><span class="flex-1 text-sm text-gray-900">${ssrInterpolate(record.class_level || "-")}</span></div><div class="flex items-center space-x-2"><label class="text-xs font-medium text-gray-500">\u0E2B\u0E49\u0E2D\u0E07:</label><span class="flex-1 text-sm text-gray-900">${ssrInterpolate(record.class_section || "-")}</span></div></div><div class="flex items-center space-x-3"><label class="text-xs font-medium text-gray-500 w-20">\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19:</label><span class="flex-1 text-sm text-gray-900">${ssrInterpolate(record.school_name || "-")}</span></div><div class="flex items-center space-x-3"><label class="text-xs font-medium text-gray-500 w-20">\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14:</label><span class="flex-1 text-sm text-gray-900">${ssrInterpolate(record.school_province || "-")}</span></div><div class="flex items-center space-x-3"><label class="text-xs font-medium text-gray-500 w-20">\u0E2A\u0E16\u0E32\u0E19\u0E30:</label><span class="${ssrRenderClass([{
          "bg-green-100 text-green-800": record.status === "studying",
          "bg-blue-100 text-blue-800": record.status === "graduated",
          "bg-yellow-100 text-yellow-800": record.status === "transferred",
          "bg-red-100 text-red-800": record.status === "dropped",
          "bg-gray-100 text-gray-800": !record.status
        }, "inline-flex px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getStatusText(record.status) || "-")}</span></div><div class="flex items-center justify-between pt-2 border-t border-gray-200"><div class="flex items-center space-x-2"></div><div class="flex space-x-2">`);
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.isCurrent && !record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-md hover:bg-orange-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 713.138-3.138z"></path></svg> \u0E15\u0E31\u0E49\u0E07\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (record.isCurrent) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> \u0E25\u0E1A </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div>`);
      });
      _push(`<!--]-->`);
      if (academicRecords.value.length === 0) {
        _push(`<div class="text-center py-8"><svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg><p class="text-sm text-gray-500">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</p><p class="text-xs text-gray-400 mt-1">\u0E04\u0E25\u0E34\u0E01\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (showModal.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-y-auto"><div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div><div class="relative min-h-screen flex items-center justify-center p-4"><div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between p-6 border-b border-gray-200"><h3 class="text-lg font-semibold text-gray-900">${ssrInterpolate(modalMode.value === "add" ? "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32" : "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32")}</h3><button class="text-gray-400 hover:text-gray-600 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div class="p-6 space-y-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</label><input${ssrRenderAttr("value", formData.value.academic_year)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="2567"></div><div class="flex items-center"><label class="flex items-center"><input${ssrIncludeBooleanAttr(Array.isArray(formData.value.isCurrent) ? ssrLooseContain(formData.value.isCurrent, null) : formData.value.isCurrent) ? " checked" : ""} type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"><span class="ml-2 text-sm text-gray-700">\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</span></label></div></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32 *</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required><option value=""${ssrIncludeBooleanAttr(Array.isArray(formData.value.education_level) ? ssrLooseContain(formData.value.education_level, "") : ssrLooseEqual(formData.value.education_level, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</option><option${ssrRenderAttr("value", 0)}${ssrIncludeBooleanAttr(Array.isArray(formData.value.education_level) ? ssrLooseContain(formData.value.education_level, 0) : ssrLooseEqual(formData.value.education_level, 0)) ? " selected" : ""}>\u0E2D\u0E19\u0E38\u0E1A\u0E32\u0E25</option><option${ssrRenderAttr("value", 1)}${ssrIncludeBooleanAttr(Array.isArray(formData.value.education_level) ? ssrLooseContain(formData.value.education_level, 1) : ssrLooseEqual(formData.value.education_level, 1)) ? " selected" : ""}>\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32</option><option${ssrRenderAttr("value", 2)}${ssrIncludeBooleanAttr(Array.isArray(formData.value.education_level) ? ssrLooseContain(formData.value.education_level, 2) : ssrLooseEqual(formData.value.education_level, 2)) ? " selected" : ""}>\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32</option></select></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19 *</label><input${ssrRenderAttr("value", formData.value.class_level)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="\u0E40\u0E0A\u0E48\u0E19 1, 2" required></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E2B\u0E49\u0E2D\u0E07 *</label><input${ssrRenderAttr("value", formData.value.class_section)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="1, 2, 3..." required></div></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E0A\u0E37\u0E48\u0E2D\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</label><input${ssrRenderAttr("value", formData.value.school_name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E23\u0E34\u0E22\u0E18\u0E23\u0E23\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34"></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</label><textarea rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="\u0E2B\u0E21\u0E39\u0E48 8 \u0E15\u0E33\u0E1A\u0E25\u0E2A\u0E30\u0E01\u0E2D\u0E21 \u0E2D\u0E33\u0E40\u0E20\u0E2D\u0E08\u0E30\u0E19\u0E30">${ssrInterpolate(formData.value.school_address)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14</label><input${ssrRenderAttr("value", formData.value.school_province)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="\u0E2A\u0E07\u0E02\u0E25\u0E32"></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</label><input${ssrRenderAttr("value", formData.value.enrollment_date)} type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E08\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</label><input${ssrRenderAttr("value", formData.value.graduation_date)} type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><option value="studying"${ssrIncludeBooleanAttr(Array.isArray(formData.value.status) ? ssrLooseContain(formData.value.status, "studying") : ssrLooseEqual(formData.value.status, "studying")) ? " selected" : ""}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E28\u0E36\u0E01\u0E29\u0E32</option><option value="graduated"${ssrIncludeBooleanAttr(Array.isArray(formData.value.status) ? ssrLooseContain(formData.value.status, "graduated") : ssrLooseEqual(formData.value.status, "graduated")) ? " selected" : ""}>\u0E08\u0E1A\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</option><option value="transferred"${ssrIncludeBooleanAttr(Array.isArray(formData.value.status) ? ssrLooseContain(formData.value.status, "transferred") : ssrLooseEqual(formData.value.status, "transferred")) ? " selected" : ""}>\u0E22\u0E49\u0E32\u0E22\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</option><option value="dropped"${ssrIncludeBooleanAttr(Array.isArray(formData.value.status) ? ssrLooseContain(formData.value.status, "dropped") : ssrLooseEqual(formData.value.status, "dropped")) ? " selected" : ""}>\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A</option><option value="suspended"${ssrIncludeBooleanAttr(Array.isArray(formData.value.status) ? ssrLooseContain(formData.value.status, "suspended") : ssrLooseEqual(formData.value.status, "suspended")) ? " selected" : ""}>\u0E1E\u0E31\u0E01\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</option></select></div>`);
        if (formData.value.status === "transferred") {
          _push(`<div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E22\u0E49\u0E32\u0E22</label><textarea rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E22\u0E49\u0E32\u0E22\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19">${ssrInterpolate(formData.value.transfer_reason)}</textarea></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38</label><textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400" placeholder="\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21">${ssrInterpolate(formData.value.notes)}</textarea></div></div><div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200"><button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">`);
        if (isSaving.value) {
          _push(`<span class="flex items-center"><svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> \u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01... </span>`);
        } else {
          _push(`<span>${ssrInterpolate(modalMode.value === "add" ? "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25" : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02")}</span>`);
        }
        _push(`</button></div></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Components/AcademicInfoCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AcademicInfoCard-DeAtPXoT.mjs.map
