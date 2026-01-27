import { defineComponent, resolveComponent, mergeProps, unref, withCtx, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead } from './server.mjs';
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
  __name: "quests",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08 - Nuxni"
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_BaseCard = resolveComponent("BaseCard");
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900" }, _attrs))}><div class="max-w-4xl mx-auto"><div class="text-center mb-8"><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        name: "mdi:flag-checkered",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"> \u0E20\u0E32\u0E23\u0E01\u0E34\u0E08 </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto"> \u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E41\u0E25\u0E30\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21 </p></div>`);
      _push(ssrRenderComponent(_component_BaseCard, { class: "text-center py-12" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              name: "mdi:clock-outline",
              class: "w-16 h-16 mx-auto text-indigo-500 mb-4"
            }, null, _parent2, _scopeId));
            _push2(`<h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}> \u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49 </h2><p class="text-gray-600 dark:text-gray-400"${_scopeId}> \u0E23\u0E30\u0E1A\u0E1A\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E22\u0E39\u0E48\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E01\u0E32\u0E23\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15 </p>`);
          } else {
            return [
              createVNode(unref(Icon), {
                name: "mdi:clock-outline",
                class: "w-16 h-16 mx-auto text-indigo-500 mb-4"
              }),
              createVNode("h2", { class: "text-xl font-semibold text-gray-900 dark:text-white mb-2" }, " \u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49 "),
              createVNode("p", { class: "text-gray-600 dark:text-gray-400" }, " \u0E23\u0E30\u0E1A\u0E1A\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E22\u0E39\u0E48\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E01\u0E32\u0E23\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/quests.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=quests-CmC6wiMe.mjs.map
