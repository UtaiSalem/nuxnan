import { defineComponent, ref, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as __nuxt_component_0 } from './RichTextEditor-C7FYwlb0.mjs';
import { i as useApi } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "AssignmentSubmissionForm",
  __ssrInlineRender: true,
  props: {
    assignment: {},
    courseId: {},
    existingAnswer: {},
    isEditing: { type: Boolean },
    showCancel: { type: Boolean }
  },
  emits: ["submitted", "cancel"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    useApi();
    const answerContent = ref("");
    const answerFiles = ref([]);
    const imagePreviews = ref([]);
    const existingImages = ref([]);
    ref([]);
    const isSubmitting = ref(false);
    watch(() => props.existingAnswer, (newVal) => {
      if (newVal) {
        answerContent.value = newVal.content || "";
        existingImages.value = [...newVal.images || []];
      } else {
        answerContent.value = "";
        existingImages.value = [];
      }
    }, { immediate: true, deep: true });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg" }, _attrs))}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:form-new-24-regular",
        class: "w-6 h-6 text-orange-500"
      }, null, _parent));
      _push(` ${ssrInterpolate(__props.isEditing ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E32\u0E23\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19" : "\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19")}</h3><div class="space-y-4">`);
      _push(ssrRenderComponent(__nuxt_component_0, {
        modelValue: answerContent.value,
        "onUpdate:modelValue": ($event) => answerContent.value = $event,
        placeholder: "\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13...",
        class: "min-h-[120px]"
      }, null, _parent));
      _push(`<div><label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">\u0E41\u0E19\u0E1A\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1C\u0E25\u0E07\u0E32\u0E19</label><div class="flex items-center justify-center w-full"><label class="flex flex-col items-center justify-center w-full h-24 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:border-gray-700 dark:hover:border-gray-500 dark:hover:bg-gray-800 transition-colors"><div class="flex flex-col items-center justify-center pt-5 pb-6">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:image-add-24-regular",
        class: "w-8 h-8 text-gray-400 mb-1"
      }, null, _parent));
      _push(`<p class="text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</p></div><input type="file" multiple accept="image/*" class="hidden"></label></div></div>`);
      if (imagePreviews.value.length) {
        _push(`<div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3"><!--[-->`);
        ssrRenderList(imagePreviews.value, (preview, i) => {
          var _a, _b;
          _push(`<div class="relative group aspect-square rounded-xl overflow-hidden border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900"><img${ssrRenderAttr("src", preview)} class="w-full h-full object-cover"${ssrRenderAttr("alt", ((_a = answerFiles.value[i]) == null ? void 0 : _a.name) || "preview")}><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><button class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors shadow-lg">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-16-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div><div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2"><p class="text-white text-xs truncate">${ssrInterpolate((_b = answerFiles.value[i]) == null ? void 0 : _b.name)}</p></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (existingImages.value.length) {
        _push(`<div class="space-y-2"><p class="text-sm font-medium text-gray-600 dark:text-gray-400">\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E35\u0E48\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E41\u0E25\u0E49\u0E27:</p><div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3"><!--[-->`);
        ssrRenderList(existingImages.value, (img) => {
          _push(`<div class="relative group aspect-square rounded-xl overflow-hidden border-2 border-green-200 dark:border-green-700 bg-gray-100 dark:bg-gray-900"><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-full object-cover"><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><button class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors shadow-lg">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-16-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div><div class="absolute top-2 left-2"><span class="px-2 py-0.5 bg-green-500 text-white text-[10px] font-bold rounded-full">\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E41\u0E25\u0E49\u0E27</span></div></div>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="mt-6 flex gap-3 justify-end">`);
      if (__props.isEditing && __props.showCancel !== false) {
        _push(`<button class="px-6 py-2.5 rounded-xl font-bold text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="px-8 py-2.5 rounded-xl font-bold text-white bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 shadow-lg hover:shadow-orange-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2">`);
      if (isSubmitting.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "eos-icons:loading",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(` ${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07..." : __props.isEditing ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02" : "\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19")}</button></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AssignmentSubmissionForm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=AssignmentSubmissionForm-DdGy9hN6.mjs.map
