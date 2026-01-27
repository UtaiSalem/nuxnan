import { i as useApi, j as __nuxt_component_0$2 } from './server.mjs';
import { defineComponent, ref, watch, computed, mergeProps, unref, withCtx, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "LessonForm",
  __ssrInlineRender: true,
  props: {
    lesson: {},
    courseId: {},
    isEdit: { type: Boolean, default: false }
  },
  emits: ["submit", "cancel"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    useApi();
    const form = ref({
      title: "",
      description: "",
      content: "",
      youtube_url: "",
      min_read: 0,
      point_tuition_fee: 0,
      order: 0,
      status: 1
    });
    const tempImages = ref([]);
    const existingImages = ref([]);
    const isDragActive = ref(false);
    const isSubmitting = ref(false);
    const errors = ref({});
    watch(
      () => props.lesson,
      (lesson) => {
        var _a;
        if (lesson) {
          form.value = {
            title: lesson.title || "",
            description: lesson.description || "",
            content: lesson.content || "",
            youtube_url: lesson.youtube_url || "",
            min_read: lesson.min_read || 0,
            point_tuition_fee: lesson.point_tuition_fee || 0,
            order: lesson.order || 0,
            status: (_a = lesson.status) != null ? _a : 1
          };
          existingImages.value = lesson.images || [];
        }
      },
      { immediate: true }
    );
    const formTitle = computed(() => props.isEdit ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E2B\u0E21\u0E48");
    return (_ctx, _push, _parent, _attrs) => {
      const _component_ClientOnly = __nuxt_component_0$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" }, _attrs))}><div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 p-6"><h2 class="text-2xl font-bold text-white flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: __props.isEdit ? "fluent:edit-24-filled" : "fluent:add-circle-24-filled",
        class: "w-7 h-7"
      }, null, _parent));
      _push(` ${ssrInterpolate(formTitle.value)}</h2></div><form class="p-6 space-y-6"><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", form.value.title)} type="text" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19..." class="${ssrRenderClass([{ "border-red-500": errors.value.title }, "w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"])}">`);
      if (errors.value.title) {
        _push(`<p class="mt-1 text-sm text-red-500">${ssrInterpolate(errors.value.title)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </label><textarea rows="3" placeholder="\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49..." class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all resize-none">${ssrInterpolate(form.value.description)}</textarea></div><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </label>`);
      _push(ssrRenderComponent(_component_ClientOnly, null, {
        fallback: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full h-[300px] bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center"${_scopeId}><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"${_scopeId}></div></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full h-[300px] bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center" }, [
                createVNode("div", { class: "animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600" })
              ])
            ];
          }
        })
      }, _parent));
      _push(`</div><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A </label>`);
      if (existingImages.value.length > 0) {
        _push(`<div class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-4"><!--[-->`);
        ssrRenderList(existingImages.value, (image, index) => {
          _push(`<div class="relative group"><img${ssrRenderAttr("src", image.full_url)}${ssrRenderAttr("alt", `Image ${index + 1}`)} class="w-full h-32 object-cover rounded-lg"><button type="button" class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (tempImages.value.length > 0) {
        _push(`<div class="mb-4 grid grid-cols-2 md:grid-cols-4 gap-4"><!--[-->`);
        ssrRenderList(tempImages.value, (image, index) => {
          _push(`<div class="relative group"><img${ssrRenderAttr("src", image.url)}${ssrRenderAttr("alt", `New Image ${index + 1}`)} class="w-full h-32 object-cover rounded-lg"><button type="button" class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button><div class="absolute bottom-2 left-2 px-2 py-0.5 bg-green-500 text-white text-xs rounded"> \u0E43\u0E2B\u0E21\u0E48 </div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="${ssrRenderClass([
        isDragActive.value ? "bg-blue-50 border-blue-400 dark:bg-blue-900/20" : "border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700",
        "relative w-full p-8 border-2 border-dashed rounded-xl transition-all cursor-pointer"
      ])}"><input type="file" multiple accept="image/*" class="hidden"><div class="text-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:cloud-arrow-up-24-regular",
        class: "w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-3"
      }, null, _parent));
      _push(`<p class="text-gray-600 dark:text-gray-400 font-medium"> \u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E21\u0E32\u0E27\u0E32\u0E07\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48 \u0E2B\u0E23\u0E37\u0E2D <span class="text-blue-600 dark:text-blue-400">\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01</span></p><p class="text-sm text-gray-500 dark:text-gray-500 mt-1">PNG, JPG, GIF \u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 4MB</p></div></div></div><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E25\u0E34\u0E07\u0E01\u0E4C YouTube (\u0E16\u0E49\u0E32\u0E21\u0E35) </label><div class="relative">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "logos:youtube-icon",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", form.value.youtube_url)} type="text" placeholder="https://www.youtube.com/watch?v=..." class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-6"><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E41\u0E15\u0E49\u0E21\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21 </label><input${ssrRenderAttr("value", form.value.point_tuition_fee)} type="number" min="0" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"></div><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E25\u0E33\u0E14\u0E31\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </label><input${ssrRenderAttr("value", form.value.order)} type="number" min="0" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"></div><div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E2A\u0E16\u0E32\u0E19\u0E30 </label><select class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"><option${ssrRenderAttr("value", 1)}${ssrIncludeBooleanAttr(Array.isArray(form.value.status) ? ssrLooseContain(form.value.status, 1) : ssrLooseEqual(form.value.status, 1)) ? " selected" : ""}>\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48</option><option${ssrRenderAttr("value", 0)}${ssrIncludeBooleanAttr(Array.isArray(form.value.status) ? ssrLooseContain(form.value.status, 0) : ssrLooseEqual(form.value.status, 0)) ? " selected" : ""}>\u0E41\u0E1A\u0E1A\u0E23\u0E48\u0E32\u0E07</option></select></div></div><div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700"><button type="button" class="px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="submit"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition-all shadow-lg hover:shadow-xl font-bold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">`);
      if (isSubmitting.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: __props.isEdit ? "fluent:save-24-filled" : "fluent:add-circle-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` ${ssrInterpolate(__props.isEdit ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")}</button></div></form></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonForm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=LessonForm-CnZH8av3.mjs.map
