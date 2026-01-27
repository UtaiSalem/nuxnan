import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, computed, mergeProps, unref, withCtx, createTextVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { storeToRefs } from 'pinia';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './LessonForm-CnZH8av3.mjs';
import { s as useCourseStore, p as useRoute, u as useRouter, f as useHead } from './server.mjs';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "create",
  __ssrInlineRender: true,
  setup(__props) {
    const courseStore = useCourseStore();
    const { currentCourse: course, isCourseAdmin } = storeToRefs(courseStore);
    const route = useRoute();
    const router = useRouter();
    const courseId = route.params.id;
    console.log("Create page - course:", course == null ? void 0 : course.value, "isCourseAdmin:", isCourseAdmin == null ? void 0 : isCourseAdmin.value);
    const handleSubmit = (response) => {
      router.push(`/courses/${courseId}/lessons`);
    };
    const handleCancel = () => {
      router.back();
    };
    const courseName = computed(() => {
      var _a;
      return ((_a = course == null ? void 0 : course.value) == null ? void 0 : _a.name) || "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32";
    });
    useHead({
      title: `\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E2B\u0E21\u0E48 - ${courseName.value}`
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-4xl mx-auto py-8 px-4" }, _attrs))}><nav class="flex items-center gap-2 text-sm mb-6">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${unref(courseId)}`,
        class: "text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(courseName.value)}`);
          } else {
            return [
              createTextVNode(toDisplayString(courseName.value), 1)
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
        to: `/courses/${unref(courseId)}/lessons`,
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
      _push(`<span class="text-gray-900 dark:text-white font-medium">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E2B\u0E21\u0E48</span></nav>`);
      _push(ssrRenderComponent(_sfc_main$1, {
        "course-id": unref(courseId),
        "is-edit": false,
        onSubmit: handleSubmit,
        onCancel: handleCancel
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/lessons/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=create-Du7vY2UV.mjs.map
