import { defineComponent, reactive, ref, watch, unref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderTeleport, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrRenderAttrs, ssrRenderSlot } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { i as useApi } from './server.mjs';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "RadialProgress",
  __ssrInlineRender: true,
  props: {
    percentage: {
      type: Number,
      required: true,
      default: 0
    },
    size: {
      type: Number,
      default: 60
    },
    strokeWidth: {
      type: Number,
      default: 6
    },
    color: {
      type: String,
      default: "text-purple-600"
    },
    trackColor: {
      type: String,
      default: "text-gray-200 dark:text-gray-700"
    }
  },
  setup(__props) {
    const props = __props;
    const radius = computed(() => props.size / 2 - props.strokeWidth / 2);
    const circumference = computed(() => 2 * Math.PI * radius.value);
    const offset = computed(() => circumference.value - props.percentage / 100 * circumference.value);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: "relative flex items-center justify-center",
        style: { width: __props.size + "px", height: __props.size + "px" }
      }, _attrs))}><svg class="transform -rotate-90 w-full h-full"><circle${ssrRenderAttr("cx", __props.size / 2)}${ssrRenderAttr("cy", __props.size / 2)}${ssrRenderAttr("r", radius.value)}${ssrRenderAttr("stroke-width", __props.strokeWidth)} fill="transparent" class="${ssrRenderClass(__props.trackColor)}" stroke="currentColor"></circle><circle${ssrRenderAttr("cx", __props.size / 2)}${ssrRenderAttr("cy", __props.size / 2)}${ssrRenderAttr("r", radius.value)}${ssrRenderAttr("stroke-width", __props.strokeWidth)} fill="transparent" class="${ssrRenderClass([__props.color, "transition-all duration-1000 ease-out"])}" stroke="currentColor" stroke-linecap="round"${ssrRenderAttr("stroke-dasharray", circumference.value)}${ssrRenderAttr("stroke-dashoffset", offset.value)}></circle></svg><div class="absolute inset-0 flex items-center justify-center">`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Common/RadialProgress.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "QuizDuplicateModal",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    quiz: {},
    currentCourseId: {}
  },
  emits: ["close", "duplicated"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const api = useApi();
    const form = reactive({
      title: "",
      copyMode: "select_course",
      targetCourseId: null
    });
    const isLoading = ref(false);
    const isSearching = ref(false);
    const searchQuery = ref("");
    const searchResults = ref([]);
    const selectedCourse = ref(null);
    watch(() => props.show, (show) => {
      if (show && props.quiz) {
        form.title = `${props.quiz.title} (\u0E2A\u0E33\u0E40\u0E19\u0E32)`;
        form.copyMode = "select_course";
        form.targetCourseId = null;
        selectedCourse.value = null;
        searchQuery.value = "";
        searchResults.value = [];
      }
    });
    const searchCourses = async () => {
      if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
      }
      isSearching.value = true;
      try {
        const res = await api.get(`/api/courses/search?q=${encodeURIComponent(searchQuery.value)}`);
        searchResults.value = (res.courses || res.data || []).filter(
          (c) => c.id !== Number(props.currentCourseId)
        );
      } catch (err) {
        console.error("Error searching courses:", err);
        searchResults.value = [];
      } finally {
        isSearching.value = false;
      }
    };
    let searchTimeout;
    watch(searchQuery, (val) => {
      clearTimeout(searchTimeout);
      if (val.length >= 2) {
        searchTimeout = setTimeout(searchCourses, 300);
      } else {
        searchResults.value = [];
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">`);
          if (__props.show) {
            _push2(`<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"><div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6"><div class="flex items-center justify-between"><div class="flex items-center gap-3"><div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:copy-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent));
            _push2(`</div><div><h3 class="text-xl font-bold text-white">\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</h3><p class="text-purple-100 text-sm">${ssrInterpolate((_a = __props.quiz) == null ? void 0 : _a.title)}</p></div></div><button${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""} class="p-2 text-white/80 hover:text-white hover:bg-white/20 rounded-lg transition-colors">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`</button></div></div><div class="p-6 space-y-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E2B\u0E21\u0E48 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", unref(form).title)} type="text" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A..."></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"> \u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E44\u0E1B\u0E22\u0E31\u0E07 </label><div class="grid grid-cols-2 gap-3"><button type="button" class="${ssrRenderClass([
              "p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all",
              unref(form).copyMode === "select_course" ? "border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-400"
            ])}">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-export-24-regular",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`<span class="text-sm font-medium">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E2D\u0E23\u0E4C\u0E2A</span></button><button type="button" class="${ssrRenderClass([
              "p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all",
              unref(form).copyMode === "same_course" ? "border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300" : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-400"
            ])}">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-sync-24-regular",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`<span class="text-sm font-medium">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E14\u0E34\u0E21</span></button></div></div>`);
            if (unref(form).copyMode === "select_course") {
              _push2(`<div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E1B\u0E25\u0E32\u0E22\u0E17\u0E32\u0E07 <span class="text-red-500">*</span></label>`);
              if (unref(selectedCourse)) {
                _push2(`<div class="flex items-center gap-3 p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 mb-3"><div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-800 flex items-center justify-center">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:book-24-filled",
                  class: "w-5 h-5 text-purple-600 dark:text-purple-400"
                }, null, _parent));
                _push2(`</div><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(unref(selectedCourse).name || unref(selectedCourse).title)}</p><p class="text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(unref(selectedCourse).code || "")}</p></div><button type="button" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:dismiss-16-regular",
                  class: "w-4 h-4"
                }, null, _parent));
                _push2(`</button></div>`);
              } else {
                _push2(`<div class="relative">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:search-24-regular",
                  class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                }, null, _parent));
                _push2(`<input${ssrRenderAttr("value", unref(searchQuery))} type="text" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E14\u0E49\u0E27\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A...">`);
                if (unref(isSearching)) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "svg-spinners:ring-resize",
                    class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500"
                  }, null, _parent));
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              }
              if (unref(searchResults).length > 0 && !unref(selectedCourse)) {
                _push2(`<div class="mt-2 max-h-48 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"><!--[-->`);
                ssrRenderList(unref(searchResults), (course) => {
                  _push2(`<button type="button" class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left border-b border-gray-100 dark:border-gray-700 last:border-b-0"><div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">`);
                  if (course.cover_image || course.image) {
                    _push2(`<img${ssrRenderAttr("src", course.cover_image || course.image)}${ssrRenderAttr("alt", course.name || course.title)} class="w-full h-full object-cover">`);
                  } else {
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "fluent:book-24-regular",
                      class: "w-5 h-5 text-gray-400"
                    }, null, _parent));
                  }
                  _push2(`</div><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(course.name || course.title)}</p><p class="text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(course.code || "")}</p></div></button>`);
                });
                _push2(`<!--]--></div>`);
              } else if (unref(searchQuery).length >= 2 && !unref(isSearching) && unref(searchResults).length === 0 && !unref(selectedCourse)) {
                _push2(`<div class="mt-2 p-4 text-center text-gray-500 dark:text-gray-400 rounded-xl border border-gray-200 dark:border-gray-700">`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:search-24-regular",
                  class: "w-8 h-8 mx-auto mb-2 opacity-50"
                }, null, _parent));
                _push2(`<p class="text-sm">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E04\u0E49\u0E19\u0E2B\u0E32</p></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (!unref(selectedCourse) && unref(searchQuery).length < 2) {
                _push2(`<p class="mt-2 text-xs text-gray-500 dark:text-gray-400"> \u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 2 \u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32 </p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800"><div class="flex items-start gap-3">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:info-24-regular",
              class: "w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5"
            }, null, _parent));
            _push2(`<div class="text-sm text-blue-700 dark:text-blue-300"><p class="font-medium mb-1">\u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E08\u0E30\u0E16\u0E39\u0E01\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01:</p><ul class="list-disc list-inside space-y-0.5 text-xs opacity-90"><li>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 (\u0E0A\u0E37\u0E48\u0E2D, \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22, \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32)</li><li>\u0E04\u0E33\u0E16\u0E32\u0E21\u0E41\u0E25\u0E30\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</li><li>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</li></ul></div></div></div></div><div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3"><button type="button"${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""} class="px-6 py-2.5 rounded-xl font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="button"${ssrIncludeBooleanAttr(unref(isLoading) || !unref(form).title.trim() || unref(form).copyMode === "select_course" && !unref(form).targetCourseId) ? " disabled" : ""} class="px-6 py-2.5 rounded-xl font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: unref(isLoading) ? "svg-spinners:ring-resize" : "fluent:copy-24-filled",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` ${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01..." : "\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A")}</button></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/quiz/QuizDuplicateModal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _, _sfc_main$1 as a };
//# sourceMappingURL=QuizDuplicateModal-BCiaovrs.mjs.map
