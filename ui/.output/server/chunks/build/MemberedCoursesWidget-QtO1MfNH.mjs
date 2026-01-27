import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, toDisplayString, createBlock, createCommentVNode, openBlock, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderList, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { i as useApi, b as useRuntimeConfig, d as useAuthStore } from './server.mjs';
import { storeToRefs } from 'pinia';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "RecentlyViewedCoursesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const config = useRuntimeConfig();
    const recentCourses = ref([]);
    const isLoading = ref(true);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm mb-6" }, _attrs))}><h3 class="font-semibold text-gray-900 dark:text-white mb-4">\u0E40\u0E02\u0E49\u0E32\u0E0A\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h3>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div><div class="flex-1 space-y-2 py-1"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (recentCourses.value.length > 0) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(recentCourses.value, (course) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: course.id,
            to: `/courses/${course.id}`,
            class: "flex gap-3 group hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 p-2 -mx-2 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="relative shrink-0 w-12 h-12"${_scopeId}><img${ssrRenderAttr("src", course.cover ? course.cover : `${unref(config).public.apiBase}/storage/images/courses/covers/default_cover.jpg`)}${ssrRenderAttr("alt", course.name)} class="w-full h-full object-cover rounded-lg shadow-sm"${_scopeId}></div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-vikinger-purple transition-colors"${_scopeId}>${ssrInterpolate(course.name)}</h4><div class="flex items-center gap-2 mt-1"${_scopeId}><span class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(course.code ? course.code : "Course")}</span></div></div><div class="flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple group-hover:translate-x-1 transition-all"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chevron-right-20-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "relative shrink-0 w-12 h-12" }, [
                    createVNode("img", {
                      src: course.cover ? course.cover : `${unref(config).public.apiBase}/storage/images/courses/covers/default_cover.jpg`,
                      alt: course.name,
                      class: "w-full h-full object-cover rounded-lg shadow-sm"
                    }, null, 8, ["src", "alt"])
                  ]),
                  createVNode("div", { class: "flex-1 min-w-0" }, [
                    createVNode("h4", { class: "text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-vikinger-purple transition-colors" }, toDisplayString(course.name), 1),
                    createVNode("div", { class: "flex items-center gap-2 mt-1" }, [
                      createVNode("span", { class: "text-xs text-gray-500 dark:text-gray-400" }, toDisplayString(course.code ? course.code : "Course"), 1)
                    ])
                  ]),
                  createVNode("div", { class: "flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple group-hover:translate-x-1 transition-all" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:chevron-right-20-regular",
                      class: "w-5 h-5"
                    })
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:history-20-regular",
          class: "w-8 h-8 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E17\u0E35\u0E48\u0E14\u0E39\u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49</p></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/RecentlyViewedCoursesWidget.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "MemberedCoursesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const { user } = storeToRefs(useAuthStore());
    const config = useRuntimeConfig();
    const courses = ref([]);
    const isLoading = ref(true);
    ref(1);
    const hasMore = ref(false);
    const getCoverUrl = (course) => {
      if (course.cover) {
        if (course.cover.startsWith("http")) return course.cover;
        return `${config.public.apiBase}/storage/images/courses/covers/${course.cover}`;
      }
      return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
    };
    const getProgressColor = (progress) => {
      if (progress >= 80) return "text-green-500";
      if (progress >= 50) return "text-blue-500";
      return "text-orange-500";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" }, _attrs))}><div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"><h3 class="font-bold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</h3></div><div class="divide-y divide-gray-100 dark:divide-gray-700">`);
      if (unref(isLoading) && unref(courses).length === 0) {
        _push(`<!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="p-4 flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div><div class="flex-1 space-y-2"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]-->`);
      } else if (unref(courses).length > 0) {
        _push(`<!--[--><!--[-->`);
        ssrRenderList(unref(courses), (course) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: course.id,
            to: `/courses/${course.id}`,
            class: "p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="relative shrink-0 w-12 h-12"${_scopeId}><img${ssrRenderAttr("src", getCoverUrl(course))}${ssrRenderAttr("alt", course.name)} class="w-full h-full object-cover rounded-lg shadow-sm"${_scopeId}>`);
                if (course.isCourseAdmin) {
                  _push2(`<div class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:shield-checkmark-16-filled",
                    class: "w-3 h-3 text-white"
                  }, null, _parent2, _scopeId));
                  _push2(`</div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 group-hover:text-blue-500 transition-colors"${_scopeId}>${ssrInterpolate(course.name)}</h4><div class="flex items-center gap-2 mt-1"${_scopeId}>`);
                if (course.isCourseAdmin) {
                  _push2(`<span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"${_scopeId}> Admin </span>`);
                } else {
                  _push2(`<span class="${ssrRenderClass([getProgressColor(course.auth_progress || 0), "text-xs font-medium"])}"${_scopeId}>${ssrInterpolate(course.auth_progress.toFixed(0) || 0)}% Completed </span>`);
                }
                _push2(`</div></div>`);
              } else {
                return [
                  createVNode("div", { class: "relative shrink-0 w-12 h-12" }, [
                    createVNode("img", {
                      src: getCoverUrl(course),
                      alt: course.name,
                      class: "w-full h-full object-cover rounded-lg shadow-sm"
                    }, null, 8, ["src", "alt"]),
                    course.isCourseAdmin ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:shield-checkmark-16-filled",
                        class: "w-3 h-3 text-white"
                      })
                    ])) : createCommentVNode("", true)
                  ]),
                  createVNode("div", { class: "flex-1 min-w-0" }, [
                    createVNode("h4", { class: "text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 group-hover:text-blue-500 transition-colors" }, toDisplayString(course.name), 1),
                    createVNode("div", { class: "flex items-center gap-2 mt-1" }, [
                      course.isCourseAdmin ? (openBlock(), createBlock("span", {
                        key: 0,
                        class: "px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                      }, " Admin ")) : (openBlock(), createBlock("span", {
                        key: 1,
                        class: ["text-xs font-medium", getProgressColor(course.auth_progress || 0)]
                      }, toDisplayString(course.auth_progress.toFixed(0) || 0) + "% Completed ", 3))
                    ])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]-->`);
        if (unref(hasMore)) {
          _push(`<div class="p-2 text-center"><button class="text-xs text-blue-500 hover:underline py-2"> \u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 </button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<div class="p-8 text-center text-gray-500">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:hat-graduation-24-regular",
          class: "w-12 h-12 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p class="text-sm">\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E14</p></div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/MemberedCoursesWidget.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main$1 as _, _sfc_main as a };
//# sourceMappingURL=MemberedCoursesWidget-QtO1MfNH.mjs.map
