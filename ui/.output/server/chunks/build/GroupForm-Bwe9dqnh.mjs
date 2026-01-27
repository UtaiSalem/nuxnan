import { defineComponent, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrRenderStyle, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { i as useApi } from './server.mjs';
import { u as useCourseGroupStore } from './courseGroup-9VJQb76E.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "GroupForm",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    group: { default: null }
  },
  emits: ["saved", "cancel"],
  setup(__props, { emit: __emit }) {
    useApi();
    useCourseGroupStore();
    const formData = ref({
      name: "",
      description: "",
      privacy: "public",
      color: "#3B82F6",
      // Default blue
      max_members: null
    });
    const isSaving = ref(false);
    const errors = ref({});
    const colorOptions = [
      { name: "\u0E19\u0E49\u0E33\u0E40\u0E07\u0E34\u0E19", value: "#3B82F6" },
      { name: "\u0E21\u0E48\u0E27\u0E07", value: "#8B5CF6" },
      { name: "\u0E0A\u0E21\u0E1E\u0E39", value: "#EC4899" },
      { name: "\u0E41\u0E14\u0E07", value: "#EF4444" },
      { name: "\u0E2A\u0E49\u0E21", value: "#F97316" },
      { name: "\u0E40\u0E2B\u0E25\u0E37\u0E2D\u0E07", value: "#EAB308" },
      { name: "\u0E40\u0E02\u0E35\u0E22\u0E27", value: "#10B981" },
      { name: "\u0E1F\u0E49\u0E32", value: "#06B6D4" },
      { name: "\u0E40\u0E17\u0E32", value: "#6B7280" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      if (unref(errors).general) {
        _push(`<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl"><p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:exclamation-circle",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(errors).general)}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="space-y-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:user-group-solid",
        class: "w-5 h-5 text-teal-600 dark:text-teal-400"
      }, null, _parent));
      _push(`<span>\u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E25\u0E38\u0E48\u0E21</span><span class="text-red-500">*</span></label><input${ssrRenderAttr("value", unref(formData).name)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E01\u0E25\u0E38\u0E48\u0E21 A, \u0E01\u0E25\u0E38\u0E48\u0E21 1, \u0E40\u0E0A\u0E49\u0E32" class="${ssrRenderClass([unref(errors).name ? "border-red-500" : "border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-600", "w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-teal-500/50 border-2 transition-all"])}">`);
      if (unref(errors).name) {
        _push(`<p class="text-xs text-red-500 flex items-center gap-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:exclamation-circle",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(errors).name)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="space-y-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:document-text",
        class: "w-5 h-5 text-gray-600 dark:text-gray-400"
      }, null, _parent));
      _push(`<span>\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22</span><span class="text-gray-400 text-xs">(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</span></label><textarea rows="3" placeholder="\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21" class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-teal-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-600 transition-all resize-none">${ssrInterpolate(unref(formData).description)}</textarea></div><div class="space-y-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:swatch",
        class: "w-5 h-5 text-purple-600 dark:text-purple-400"
      }, null, _parent));
      _push(`<span>\u0E2A\u0E35\u0E02\u0E2D\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21</span></label><div class="grid grid-cols-9 gap-2"><!--[-->`);
      ssrRenderList(colorOptions, (color) => {
        _push(`<button type="button"${ssrRenderAttr("title", color.name)} class="${ssrRenderClass([unref(formData).color === color.value ? "ring-4 ring-offset-2 ring-gray-400 dark:ring-gray-600 scale-110" : "", "w-10 h-10 rounded-lg transition-all hover:scale-110"])}" style="${ssrRenderStyle({ backgroundColor: color.value })}"></button>`);
      });
      _push(`<!--]--></div></div><div class="space-y-3"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:lock-closed",
        class: "w-5 h-5 text-gray-600 dark:text-gray-400"
      }, null, _parent));
      _push(`<span>\u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</span></label><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div class="${ssrRenderClass([unref(formData).privacy === "public" ? "border-teal-500 bg-teal-50 dark:bg-teal-900/20" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600", "cursor-pointer p-4 rounded-xl border-2 transition-all flex items-start gap-3"])}"><div class="mt-1"><div class="${ssrRenderClass([unref(formData).privacy === "public" ? "border-teal-500" : "border-gray-400", "w-5 h-5 rounded-full border-2 flex items-center justify-center"])}">`);
      if (unref(formData).privacy === "public") {
        _push(`<div class="w-2.5 h-2.5 rounded-full bg-teal-500"></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div><h4 class="font-bold text-gray-900 dark:text-white mb-1">\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30</h4><p class="text-xs text-gray-500 dark:text-gray-400">\u0E17\u0E38\u0E01\u0E04\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49\u0E42\u0E14\u0E22\u0E44\u0E21\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p></div></div><div class="${ssrRenderClass([unref(formData).privacy === "private" ? "border-teal-500 bg-teal-50 dark:bg-teal-900/20" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600", "cursor-pointer p-4 rounded-xl border-2 transition-all flex items-start gap-3"])}"><div class="mt-1"><div class="${ssrRenderClass([unref(formData).privacy === "private" ? "border-teal-500" : "border-gray-400", "w-5 h-5 rounded-full border-2 flex items-center justify-center"])}">`);
      if (unref(formData).privacy === "private") {
        _push(`<div class="w-2.5 h-2.5 rounded-full bg-teal-500"></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div><h4 class="font-bold text-gray-900 dark:text-white mb-1">\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</h4><p class="text-xs text-gray-500 dark:text-gray-400">\u0E15\u0E49\u0E2D\u0E07\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E01\u0E25\u0E38\u0E48\u0E21\u0E01\u0E48\u0E2D\u0E19\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21</p></div></div></div></div><div class="space-y-2"><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:users",
        class: "w-5 h-5 text-blue-600 dark:text-blue-400"
      }, null, _parent));
      _push(`<span>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14</span><span class="text-gray-400 text-xs">(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A - \u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14)</span></label><input${ssrRenderAttr("value", unref(formData).max_members)} type="number" min="1" placeholder="\u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14\u0E08\u0E33\u0E19\u0E27\u0E19" class="${ssrRenderClass([unref(errors).max_members ? "border-red-500" : "border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600", "w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-blue-500/50 border-2 transition-all"])}">`);
      if (unref(errors).max_members) {
        _push(`<p class="text-xs text-red-500 flex items-center gap-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:exclamation-circle",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(errors).max_members)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-700"><p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:eye",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25: </p><div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700"><div class="w-12 h-12 rounded-lg flex items-center justify-center" style="${ssrRenderStyle({ backgroundColor: unref(formData).color })}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:user-group",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><div class="flex-1"><h4 class="font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(formData).name || "\u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E25\u0E38\u0E48\u0E21")}</h4><p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(unref(formData).max_members ? `\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 ${unref(formData).max_members} \u0E04\u0E19` : "\u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14\u0E08\u0E33\u0E19\u0E27\u0E19")} <span class="mx-2">\u2022</span><span>${ssrInterpolate(unref(formData).privacy === "public" ? "\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30" : "\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27")}</span></p></div></div></div><div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"><button${ssrIncludeBooleanAttr(unref(isSaving)) ? " disabled" : ""} class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(unref(isSaving)) ? " disabled" : ""} class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-teal-500 to-cyan-600 hover:from-teal-600 hover:to-cyan-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl">`);
      if (unref(isSaving)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:save-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(`<span>${ssrInterpolate(unref(isSaving) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : __props.group ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21")}</span></button></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/GroupForm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=GroupForm-Bwe9dqnh.mjs.map
