import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, inject, computed, ref, unref, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './GroupForm-Bwe9dqnh.mjs';
import { p as useRoute, u as useRouter, i as useApi, n as navigateTo } from './server.mjs';
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
import './courseGroup-9VJQb76E.mjs';
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
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const route = useRoute();
    const router = useRouter();
    useApi();
    const groupId = computed(() => route.params.groupId);
    const group = ref(null);
    const isLoading = ref(true);
    if (!(isCourseAdmin == null ? void 0 : isCourseAdmin.value)) {
      navigateTo(`/courses/${route.params.id}/groups`);
    }
    const handleSaved = () => {
      navigateTo(`/courses/${course.value.id}/groups/${groupId.value}`);
    };
    const handleCancel = () => {
      router.back();
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if (unref(isLoading)) {
        _push(`<div class="flex items-center justify-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-8 h-8 text-blue-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (unref(group)) {
        _push(`<div class="max-w-3xl mx-auto"><div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-lg"><div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700"><div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:pencil-square",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E25\u0E38\u0E48\u0E21</h1><p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(unref(group).name)}</p></div></div>`);
        _push(ssrRenderComponent(_sfc_main$1, {
          "course-id": (_a = unref(course)) == null ? void 0 : _a.id,
          group: unref(group),
          onSaved: handleSaved,
          onCancel: handleCancel
        }, null, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<div class="text-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:exclamation-circle",
          class: "w-16 h-16 text-red-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E25\u0E38\u0E48\u0E21</h3>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${(_b = unref(course)) == null ? void 0 : _b.id}/groups`,
          class: "inline-flex items-center gap-2 text-blue-600 hover:text-blue-700"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:arrow-left",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "heroicons:arrow-left",
                  class: "w-5 h-5"
                }),
                createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/groups/[groupId]/edit.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=edit-DgRTBGLi.mjs.map
