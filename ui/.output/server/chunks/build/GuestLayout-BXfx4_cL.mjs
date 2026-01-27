import { unref, withCtx, createVNode, renderSlot, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderSlot } from 'vue/server-renderer';
import { H as Head } from './inertia-vue3-CWdJjaLG.mjs';
import { A as AuthenticationCard } from './AuthenticationCard-Cc5GEVqI.mjs';
import './server.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "GuestLayout",
  __ssrInlineRender: true,
  props: {
    pageName: String
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: __props.pageName }, null, _parent));
      _push(ssrRenderComponent(AuthenticationCard, { class: "page-bg" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="rounded-t mb-0 p-4"${_scopeId}><div class="text-center mb-2"${_scopeId}><h6 class="text-gray-600 text-2xl font-bold font-prompt"${_scopeId}> \u0E22\u0E34\u0E19\u0E14\u0E35\u0E15\u0E49\u0E2D\u0E19\u0E23\u0E31\u0E1A\u0E2A\u0E39\u0E48 PLEARND! </h6></div></div><hr${_scopeId}><div class="text-gray-500 text-center text-2xl my-4 font-bold"${_scopeId}><span class="font-prompt"${_scopeId}>${ssrInterpolate(__props.pageName)}</span></div>`);
            ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
          } else {
            return [
              createVNode("div", { class: "rounded-t mb-0 p-4" }, [
                createVNode("div", { class: "text-center mb-2" }, [
                  createVNode("h6", { class: "text-gray-600 text-2xl font-bold font-prompt" }, " \u0E22\u0E34\u0E19\u0E14\u0E35\u0E15\u0E49\u0E2D\u0E19\u0E23\u0E31\u0E1A\u0E2A\u0E39\u0E48 PLEARND! ")
                ])
              ]),
              createVNode("hr"),
              createVNode("div", { class: "text-gray-500 text-center text-2xl my-4 font-bold" }, [
                createVNode("span", { class: "font-prompt" }, toDisplayString(__props.pageName), 1)
              ]),
              renderSlot(_ctx.$slots, "default")
            ];
          }
        }),
        _: 3
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/GuestLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=GuestLayout-BXfx4_cL.mjs.map
