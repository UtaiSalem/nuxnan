import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, computed, ref, watch, mergeProps, withCtx, createTextVNode, toDisplayString, unref, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './LessonForm-CnZH8av3.mjs';
import { s as useCourseStore, p as useRoute, u as useRouter, i as useApi, f as useHead } from './server.mjs';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "edit",
  __ssrInlineRender: true,
  setup(__props) {
    const courseStore = useCourseStore();
    const course = computed(() => courseStore.currentCourse);
    const isCourseAdmin = computed(() => courseStore.isCourseAdmin);
    const route = useRoute();
    const router = useRouter();
    useApi();
    const courseId = computed(() => route.params.id);
    const lessonId = computed(() => route.params.lessonId);
    const lesson = ref(null);
    const isLoading = ref(true);
    const error = ref(null);
    const handleSubmit = (response) => {
      router.push(`/courses/${courseId.value}/lessons/${lessonId.value}`);
    };
    const handleCancel = () => {
      router.back();
    };
    watch(lesson, (newLesson) => {
      var _a;
      if (newLesson == null ? void 0 : newLesson.title) {
        useHead({
          title: `\u0E41\u0E01\u0E49\u0E44\u0E02: ${newLesson.title} - ${((_a = course.value) == null ? void 0 : _a.name) || "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"}`
        });
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-4xl mx-auto py-8 px-4" }, _attrs))}><nav class="flex items-center gap-2 text-sm mb-6">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${courseId.value}`,
        class: "text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b;
          if (_push2) {
            _push2(`${ssrInterpolate(((_a = course.value) == null ? void 0 : _a.name) || "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")}`);
          } else {
            return [
              createTextVNode(toDisplayString(((_b = course.value) == null ? void 0 : _b.name) || "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chevron-right-16-regular",
        class: "w-4 h-4 text-gray-400"
      }, null, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${courseId.value}/lessons`,
        class: "text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 `);
          } else {
            return [
              createTextVNode(" \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chevron-right-16-regular",
        class: "w-4 h-4 text-gray-400"
      }, null, _parent));
      if (lesson.value) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${courseId.value}/lessons/${lessonId.value}`,
          class: "text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`${ssrInterpolate(lesson.value.title)}`);
            } else {
              return [
                createTextVNode(toDisplayString(lesson.value.title), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chevron-right-16-regular",
        class: "w-4 h-4 text-gray-400"
      }, null, _parent));
      _push(`<span class="text-gray-900 dark:text-white font-medium">\u0E41\u0E01\u0E49\u0E44\u0E02</span></nav>`);
      if (isLoading.value) {
        _push(`<div class="flex justify-center items-center py-16"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>`);
      } else if (error.value) {
        _push(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-16 h-16 text-red-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h3><p class="text-red-600 dark:text-red-400 mb-4">${ssrInterpolate(error.value)}</p><button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
      } else if (!isCourseAdmin.value) {
        _push(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:shield-error-24-regular",
          class: "w-16 h-16 text-red-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C\u0E40\u0E02\u0E49\u0E32\u0E16\u0E36\u0E07</h3><p class="text-red-600 dark:text-red-400 mb-4">\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C\u0E43\u0E19\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${courseId.value}/lessons`,
          class: "inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:arrow-left-24-regular",
                  class: "w-5 h-5"
                }),
                createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else if (lesson.value) {
        _push(ssrRenderComponent(_sfc_main$1, {
          "course-id": courseId.value,
          lesson: lesson.value,
          "is-edit": true,
          onSubmit: handleSubmit,
          onCancel: handleCancel
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/lessons/[lessonId]/edit.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=edit-Di96umzZ.mjs.map
