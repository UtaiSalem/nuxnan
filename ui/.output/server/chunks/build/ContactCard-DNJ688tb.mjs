import { ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';

const _sfc_main = {
  __name: "ContactCard",
  __ssrInlineRender: true,
  props: {
    student: {
      type: Object,
      required: true
    },
    context: {
      type: String,
      default: "student"
      // 'student' or 'teacher'
    }
  },
  emits: ["save"],
  setup(__props, { emit: __emit }) {
    const contactRecords = ref([]);
    const isSaving = ref(false);
    ref(null);
    ref(null);
    const showModal = ref(false);
    const modalMode = ref("add");
    ref(null);
    ref(-1);
    const formData = ref({
      contact_type: "phone",
      contact_value: "",
      is_primary: false
    });
    const getContactTypeText = (type) => {
      const typeMap = {
        phone: "\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C",
        email: "\u0E2D\u0E35\u0E40\u0E21\u0E25",
        line: "Line",
        facebook: "Facebook"
      };
      return typeMap[type] || type;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden" }, _attrs))}><div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-4 py-3 sm:px-6 sm:py-4"><div class="flex items-center justify-between"><div class="flex items-center"><div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg></div><div class="ml-2 sm:ml-3 min-w-0 flex-1"><h3 class="text-base sm:text-lg font-semibold text-white truncate"> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D (${ssrInterpolate(contactRecords.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </h3><p class="text-teal-100 text-xs sm:text-sm">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-2 bg-white bg-opacity-20 text-white text-sm font-medium rounded-lg hover:bg-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-teal-600 disabled:opacity-50 transition-all"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </button></div></div><div class="bg-gradient-to-br from-teal-50 to-cyan-50"><div class="hidden md:block overflow-x-auto"><table class="w-full"><thead class="bg-white border-b border-gray-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><!--[-->`);
      ssrRenderList(contactRecords.value, (record, index) => {
        _push(`<tr class="${ssrRenderClass(record.isNew ? "bg-teal-50" : "hover:bg-gray-50")}"><td class="px-4 py-4 whitespace-nowrap"><span class="${ssrRenderClass([{
          "bg-blue-100 text-blue-800": record.contact_type === "phone",
          "bg-green-100 text-green-800": record.contact_type === "email",
          "bg-yellow-100 text-yellow-800": record.contact_type === "line",
          "bg-purple-100 text-purple-800": record.contact_type === "facebook",
          "bg-gray-100 text-gray-800": !record.contact_type
        }, "inline-flex px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getContactTypeText(record.contact_type) || "-")}</span></td><td class="px-4 py-4"><div class="text-sm text-gray-900 font-medium">${ssrInterpolate(record.contact_value || "-")}</div></td><td class="px-4 py-4 whitespace-nowrap">`);
        if (record.is_primary) {
          _push(`<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> \u0E2B\u0E25\u0E31\u0E01 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</td><td class="px-4 py-4 text-right text-sm font-medium"><div class="flex items-center justify-end space-x-2">`);
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-orange-600 hover:text-orange-800 disabled:opacity-50" title="\u0E41\u0E01\u0E49\u0E44\u0E02"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.is_primary && !record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-teal-600 hover:text-teal-800 disabled:opacity-50" title="\u0E15\u0E31\u0E49\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E2B\u0E25\u0E31\u0E01"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="text-red-600 hover:text-red-800 disabled:opacity-50" title="\u0E25\u0E1A"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></td></tr>`);
      });
      _push(`<!--]-->`);
      if (contactRecords.value.length === 0) {
        _push(`<tr><td colspan="4" class="px-4 py-12 text-center"><svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg><p class="text-sm text-gray-500">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D</p><p class="text-xs text-gray-400 mt-1">\u0E04\u0E25\u0E34\u0E01\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p></td></tr>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tbody></table></div><div class="md:hidden space-y-4 p-4"><!--[-->`);
      ssrRenderList(contactRecords.value, (record, index) => {
        _push(`<div class="${ssrRenderClass(["bg-white rounded-lg border", record.isNew ? "border-teal-300 bg-teal-50" : "border-gray-200"])}"><div class="p-4 space-y-3"><div class="flex items-center justify-between"><span class="${ssrRenderClass([{
          "bg-blue-100 text-blue-800": record.contact_type === "phone",
          "bg-green-100 text-green-800": record.contact_type === "email",
          "bg-yellow-100 text-yellow-800": record.contact_type === "line",
          "bg-purple-100 text-purple-800": record.contact_type === "facebook",
          "bg-gray-100 text-gray-800": !record.contact_type
        }, "inline-flex px-2 py-1 text-xs font-semibold rounded-full"])}">${ssrInterpolate(getContactTypeText(record.contact_type) || "-")}</span>`);
        if (record.is_primary) {
          _push(`<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800"> \u0E2B\u0E25\u0E31\u0E01 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="space-y-2">`);
        if (record.contact_value) {
          _push(`<div class="text-sm"><span class="text-gray-500 font-medium">${ssrInterpolate(getContactTypeText(record.contact_type))}: </span><span class="text-gray-900">${ssrInterpolate(record.contact_value)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex items-center justify-end pt-2 border-t border-gray-200"><div class="flex space-x-2">`);
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-md hover:bg-orange-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.is_primary && !record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-teal-600 text-white text-xs font-medium rounded-md hover:bg-teal-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> \u0E15\u0E31\u0E49\u0E07\u0E2B\u0E25\u0E31\u0E01 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (!record.isNew) {
          _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 disabled:opacity-50"><svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> \u0E25\u0E1A </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div>`);
      });
      _push(`<!--]-->`);
      if (contactRecords.value.length === 0) {
        _push(`<div class="text-center py-8"><svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg><p class="text-sm text-gray-500">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D</p><p class="text-xs text-gray-400 mt-1">\u0E04\u0E25\u0E34\u0E01\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (showModal.value) {
        _push(`<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"><div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"><div class="mt-3"><div class="flex items-center justify-between pb-3"><h3 class="text-lg font-semibold text-gray-900">${ssrInterpolate(modalMode.value === "add" ? "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D" : "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D")}</h3><button class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D</label><select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-teal-500 focus:border-teal-500"><option value="phone"${ssrIncludeBooleanAttr(Array.isArray(formData.value.contact_type) ? ssrLooseContain(formData.value.contact_type, "phone") : ssrLooseEqual(formData.value.contact_type, "phone")) ? " selected" : ""}>\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C</option><option value="email"${ssrIncludeBooleanAttr(Array.isArray(formData.value.contact_type) ? ssrLooseContain(formData.value.contact_type, "email") : ssrLooseEqual(formData.value.contact_type, "email")) ? " selected" : ""}>\u0E2D\u0E35\u0E40\u0E21\u0E25</option><option value="line"${ssrIncludeBooleanAttr(Array.isArray(formData.value.contact_type) ? ssrLooseContain(formData.value.contact_type, "line") : ssrLooseEqual(formData.value.contact_type, "line")) ? " selected" : ""}>Line</option><option value="facebook"${ssrIncludeBooleanAttr(Array.isArray(formData.value.contact_type) ? ssrLooseContain(formData.value.contact_type, "facebook") : ssrLooseEqual(formData.value.contact_type, "facebook")) ? " selected" : ""}>Facebook</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D</label><input type="text"${ssrRenderAttr("value", formData.value.contact_value)} class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-teal-500 focus:border-teal-500"${ssrRenderAttr("placeholder", formData.value.contact_type === "phone" ? "0XX-XXX-XXXX" : formData.value.contact_type === "email" ? "email@example.com" : formData.value.contact_type === "line" ? "Line ID" : "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D")}></div><div class="flex items-center"><input type="checkbox" id="is_primary"${ssrIncludeBooleanAttr(Array.isArray(formData.value.is_primary) ? ssrLooseContain(formData.value.is_primary, null) : formData.value.is_primary) ? " checked" : ""} class="rounded border-gray-300 text-teal-600 shadow-sm focus:border-teal-300 focus:ring focus:ring-teal-200 focus:ring-opacity-50"><label for="is_primary" class="ml-2 text-sm text-gray-700">\u0E0A\u0E48\u0E2D\u0E07\u0E17\u0E32\u0E07\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E2B\u0E25\u0E31\u0E01</label></div></div><div class="flex items-center justify-end pt-6 space-x-2"><button type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="button"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50">${ssrInterpolate(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : modalMode.value === "add" ? "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25" : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02")}</button></div></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Components/ContactCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=ContactCard-DNJ688tb.mjs.map
