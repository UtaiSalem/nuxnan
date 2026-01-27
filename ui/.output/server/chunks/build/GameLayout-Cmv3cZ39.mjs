import { mergeProps, unref, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderSlot } from 'vue/server-renderer';
import { L as Link } from './inertia-vue3-CWdJjaLG.mjs';
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
  __name: "GameLayout",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex h-screen bg-gray-200" }, _attrs))}><div class="w-64 bg-blue-400 text-white p-6"><h2 class="text-2xl font-bold mb-6">\u0E40\u0E21\u0E19\u0E39\u0E40\u0E01\u0E21</h2><nav><ul class="space-y-2"><li>`);
      _push(ssrRenderComponent(unref(Link), {
        href: "/",
        class: [{ "bg-blue-700": _ctx.$page.url === "/game/guessing-number" }, "block py-2 px-4 hover:bg-blue-700 rounded"]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E2B\u0E19\u0E49\u0E32\u0E41\u0E23\u0E01 `);
          } else {
            return [
              createTextVNode(" \u0E2B\u0E19\u0E49\u0E32\u0E41\u0E23\u0E01 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(unref(Link), {
        href: "/game/guessing-number",
        class: [{ "bg-blue-700": _ctx.$page.url === "/game/guessing-number" }, "block py-2 px-4 hover:bg-blue-700 rounded"]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E40\u0E01\u0E21\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02 `);
          } else {
            return [
              createTextVNode(" \u0E40\u0E01\u0E21\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(unref(Link), {
        href: "/game/xo",
        class: [{ "bg-blue-700": _ctx.$page.url === "/game/xo" }, "block py-2 px-4 hover:bg-blue-700 rounded"]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E40\u0E01\u0E21 XO `);
          } else {
            return [
              createTextVNode(" \u0E40\u0E01\u0E21 XO ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(unref(Link), {
        href: "/game/snake",
        class: [{ "bg-blue-700": _ctx.$page.url === "/game/snake" }, "block py-2 px-4 hover:bg-blue-700 rounded"]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E40\u0E01\u0E21\u0E07\u0E39 `);
          } else {
            return [
              createTextVNode(" \u0E40\u0E01\u0E21\u0E07\u0E39 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li></ul></nav></div><div class="w-full h-full">`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/GameLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=GameLayout-Cmv3cZ39.mjs.map
