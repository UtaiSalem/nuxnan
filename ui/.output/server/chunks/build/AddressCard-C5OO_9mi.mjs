import { ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
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
  __name: "AddressCard",
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
    const { addressRoutes } = useStudentRoutes(props.context);
    const addressRecords = ref([]);
    const isSaving = ref(false);
    const showModal = ref(false);
    const modalMode = ref("add");
    ref(null);
    ref(-1);
    ref(null);
    const error = ref(null);
    const formData = ref({
      address_type: "current",
      house_number: "",
      village_number: "",
      village_name: "",
      alley: "",
      road: "",
      subdistrict: "",
      district: "",
      province: "",
      postal_code: "",
      is_current: false
    });
    const getAddressTypeText = (type) => {
      const typeMap = {
        current: "\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19",
        permanent: "\u0E15\u0E32\u0E21\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19\u0E1A\u0E49\u0E32\u0E19",
        temporary: "\u0E0A\u0E31\u0E48\u0E27\u0E04\u0E23\u0E32\u0E27"
      };
      return typeMap[type] || type;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden" }, _attrs))}><div class="bg-gradient-to-r from-orange-500 to-red-600 px-4 py-3 sm:px-6 sm:py-4"><div class="flex items-center justify-between"><div class="flex items-center"><div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div><div class="ml-2 sm:ml-3 min-w-0 flex-1"><h3 class="text-base sm:text-lg font-semibold text-white truncate"> \u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48 (${ssrInterpolate(addressRecords.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </h3><p class="text-orange-100 text-xs sm:text-sm">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19\u0E41\u0E25\u0E30\u0E15\u0E32\u0E21\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19\u0E1A\u0E49\u0E32\u0E19</p></div></div><button class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs sm:text-sm font-medium rounded-lg transition-all duration-200"><svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </button></div></div>`);
      if (error.value && !isSaving.value) {
        _push(`<div class="p-4 bg-red-50 border-l-4 border-red-400"><div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg></div><div class="ml-3"><p class="text-sm text-red-700">${ssrInterpolate(error.value)}</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="overflow-hidden"><div class="hidden md:block overflow-x-auto"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E15\u0E33\u0E1A\u0E25</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E2D\u0E33\u0E40\u0E20\u0E2D</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E23\u0E2B\u0E31\u0E2A\u0E44\u0E1B\u0E23\u0E29\u0E13\u0E35\u0E22\u0E4C</th><th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><!--[-->`);
      ssrRenderList(addressRecords.value, (record, index) => {
        _push(`<tr class="${ssrRenderClass({
          "bg-blue-50": record.isNew,
          "bg-green-50": record.is_current,
          "bg-gray-50": record.isFromAPI && !record.is_current,
          "hover:bg-gray-50": !record.isNew && !record.is_current
        })}"><td class="px-4 py-3 whitespace-nowrap"><div class="flex items-center"><span class="${ssrRenderClass([{
          "bg-green-100 text-green-800": record.address_type === "current",
          "bg-blue-100 text-blue-800": record.address_type === "permanent",
          "bg-yellow-100 text-yellow-800": record.address_type === "temporary",
          "bg-gray-100 text-gray-800": !record.address_type
        }, "inline-flex px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getAddressTypeText(record.address_type) || "-")}</span>`);
        if (record.is_current) {
          _push(`<span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"> \u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></td><td class="px-4 py-3"><div class="text-sm text-gray-900 max-w-xs truncate">${ssrInterpolate([record.house_number, record.village_number ? `\u0E2B\u0E21\u0E39\u0E48 ${record.village_number}` : "", record.village_name, record.alley, record.road].filter(Boolean).join(" ") || "-")}</div></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.subdistrict || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.district || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.province || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap"><span class="text-sm text-gray-900">${ssrInterpolate(record.postal_code || "-")}</span></td><td class="px-4 py-3 whitespace-nowrap text-center"><div class="flex items-center justify-center space-x-2">`);
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-orange-600 hover:text-orange-800 disabled:opacity-50" title="\u0E41\u0E01\u0E49\u0E44\u0E02"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        if (record.is_current || record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-red-600 hover:text-red-800 disabled:opacity-50" title="\u0E25\u0E1A"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.is_current && !record.isNew && record.isFromAPI) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-orange-600 hover:text-orange-800 disabled:opacity-50" title="\u0E15\u0E31\u0E49\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></td></tr>`);
      });
      _push(`<!--]-->`);
      if (addressRecords.value.length === 0) {
        _push(`<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><p class="text-sm">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</p><p class="text-xs text-gray-400 mt-1">\u0E04\u0E25\u0E34\u0E01\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p></div></td></tr>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tbody></table></div><div class="md:hidden space-y-4 p-4"><!--[-->`);
      ssrRenderList(addressRecords.value, (record, index) => {
        _push(`<div class="${ssrRenderClass(["bg-white rounded-lg border", record.isNew ? "border-orange-300 bg-orange-50" : "border-gray-200"])}"><div class="p-4 space-y-3"><div class="flex items-center justify-between"><span class="${ssrRenderClass([{
          "bg-green-100 text-green-800": record.address_type === "current",
          "bg-blue-100 text-blue-800": record.address_type === "permanent",
          "bg-yellow-100 text-yellow-800": record.address_type === "temporary",
          "bg-gray-100 text-gray-800": !record.address_type
        }, "inline-flex px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getAddressTypeText(record.address_type) || "-")}</span>`);
        if (record.is_current) {
          _push(`<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"> \u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="space-y-2">`);
        if (record.house_number || record.village_number || record.village_name || record.alley || record.road) {
          _push(`<div class="text-sm"><span class="text-gray-500 font-medium">\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48: </span><span class="text-gray-900">${ssrInterpolate([record.house_number, record.village_number ? `\u0E2B\u0E21\u0E39\u0E48 ${record.village_number}` : "", record.village_name, record.alley, record.road].filter(Boolean).join(" ") || "-")}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="space-y-1">`);
        if (record.subdistrict) {
          _push(`<div class="text-sm"><span class="text-gray-500 font-medium">\u0E15\u0E33\u0E1A\u0E25: </span><span class="text-gray-900">${ssrInterpolate(record.subdistrict)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (record.district) {
          _push(`<div class="text-sm"><span class="text-gray-500 font-medium">\u0E2D\u0E33\u0E40\u0E20\u0E2D: </span><span class="text-gray-900">${ssrInterpolate(record.district)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (record.province) {
          _push(`<div class="text-sm"><span class="text-gray-500 font-medium">\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14: </span><span class="text-gray-900">${ssrInterpolate(record.province)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (record.postal_code) {
          _push(`<div class="text-sm"><span class="text-gray-500 font-medium">\u0E23\u0E2B\u0E31\u0E2A\u0E44\u0E1B\u0E23\u0E29\u0E13\u0E35\u0E22\u0E4C: </span><span class="text-gray-900">${ssrInterpolate(record.postal_code)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="flex items-center justify-between pt-2 border-t border-gray-200"><div class="flex items-center space-x-2"></div><div class="flex space-x-2">`);
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-md hover:bg-orange-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.is_current && !record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> \u0E15\u0E31\u0E49\u0E07\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (record.is_current) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> \u0E25\u0E1A </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div>`);
      });
      _push(`<!--]-->`);
      if (addressRecords.value.length === 0) {
        _push(`<div class="text-center py-8"><svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><p class="text-sm text-gray-500">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</p><p class="text-xs text-gray-400 mt-1">\u0E04\u0E25\u0E34\u0E01\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (showModal.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-y-auto"><div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div><div class="relative min-h-screen flex items-center justify-center p-4"><div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between p-6 border-b border-gray-200"><h3 class="text-lg font-semibold text-gray-900">${ssrInterpolate(modalMode.value === "add" ? "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48" : "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48")}</h3><button class="text-gray-400 hover:text-gray-600 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div class="p-6 space-y-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48 *</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required><option value="current"${ssrIncludeBooleanAttr(Array.isArray(formData.value.address_type) ? ssrLooseContain(formData.value.address_type, "current") : ssrLooseEqual(formData.value.address_type, "current")) ? " selected" : ""}>\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</option><option value="permanent"${ssrIncludeBooleanAttr(Array.isArray(formData.value.address_type) ? ssrLooseContain(formData.value.address_type, "permanent") : ssrLooseEqual(formData.value.address_type, "permanent")) ? " selected" : ""}>\u0E15\u0E32\u0E21\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19\u0E1A\u0E49\u0E32\u0E19</option><option value="temporary"${ssrIncludeBooleanAttr(Array.isArray(formData.value.address_type) ? ssrLooseContain(formData.value.address_type, "temporary") : ssrLooseEqual(formData.value.address_type, "temporary")) ? " selected" : ""}>\u0E0A\u0E31\u0E48\u0E27\u0E04\u0E23\u0E32\u0E27</option></select></div><div class="flex items-center"><label class="flex items-center"><input${ssrIncludeBooleanAttr(Array.isArray(formData.value.is_current) ? ssrLooseContain(formData.value.is_current, null) : formData.value.is_current) ? " checked" : ""} type="checkbox" class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"><span class="ml-2 text-sm text-gray-700">\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</span></label></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E1A\u0E49\u0E32\u0E19\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 *</label><input${ssrRenderAttr("value", formData.value.house_number)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E1A\u0E49\u0E32\u0E19" required></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E2B\u0E21\u0E39\u0E48\u0E17\u0E35\u0E48</label><input${ssrRenderAttr("value", formData.value.village_number)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E2B\u0E21\u0E39\u0E48\u0E17\u0E35\u0E48"></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E0A\u0E37\u0E48\u0E2D\u0E2B\u0E21\u0E39\u0E48\u0E1A\u0E49\u0E32\u0E19</label><input${ssrRenderAttr("value", formData.value.village_name)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E2B\u0E21\u0E39\u0E48\u0E1A\u0E49\u0E32\u0E19"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E0B\u0E2D\u0E22/\u0E15\u0E23\u0E2D\u0E01</label><input${ssrRenderAttr("value", formData.value.alley)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E0B\u0E2D\u0E22/\u0E15\u0E23\u0E2D\u0E01"></div></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E16\u0E19\u0E19</label><input${ssrRenderAttr("value", formData.value.road)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E16\u0E19\u0E19"></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E15\u0E33\u0E1A\u0E25/\u0E41\u0E02\u0E27\u0E07 *</label><input${ssrRenderAttr("value", formData.value.subdistrict)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E15\u0E33\u0E1A\u0E25/\u0E41\u0E02\u0E27\u0E07" required></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E2D\u0E33\u0E40\u0E20\u0E2D/\u0E40\u0E02\u0E15 *</label><input${ssrRenderAttr("value", formData.value.district)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E2D\u0E33\u0E40\u0E20\u0E2D/\u0E40\u0E02\u0E15" required></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14 *</label><input${ssrRenderAttr("value", formData.value.province)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14" required></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E23\u0E2B\u0E31\u0E2A\u0E44\u0E1B\u0E23\u0E29\u0E13\u0E35\u0E22\u0E4C</label><input${ssrRenderAttr("value", formData.value.postal_code)} type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400" placeholder="\u0E23\u0E2B\u0E31\u0E2A\u0E44\u0E1B\u0E23\u0E29\u0E13\u0E35\u0E22\u0E4C"></div></div></div><div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200"><button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} type="button" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed">`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Components/AddressCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AddressCard-C5OO_9mi.mjs.map
