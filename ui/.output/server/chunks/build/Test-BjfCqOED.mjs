import { ref, unref, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
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
  __name: "Test",
  __ssrInlineRender: true,
  setup(__props) {
    const form = ref({
      name: "",
      cover: null,
      img: "",
      success: ""
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="container bg-slate-200"><div class="flex flex-col justify-center items-center w-screen h-screen"><div class="max-w-6xl"><div class="bg-white shadow-lg rounded-lg m-6 p-6 space-y-4"><div class="card-header underline">`);
      _push(ssrRenderComponent(unref(Link), { href: "/play/newsfeed" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Laravel Vue JS File Upload Demo`);
          } else {
            return [
              createTextVNode("Laravel Vue JS File Upload Demo")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="card-body">`);
      if (form.value.success != "") {
        _push(`<div class="alert alert-success">${ssrInterpolate(form.value.success)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<form enctype="multipart/form-data"><input type="file" class="form-control"><button type="submit" class="bg-green-500 text-white p-2 rounded-lg">Upload</button></form></div></div><div class="bg-white shadow-lg rounded-lg m-6 p-6 space-y-4"><div class="w-40 h-40"><img${ssrRenderAttr("src", form.value.img)} alt=""></div><div>${ssrInterpolate(form.value.name)}</div></div></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Test.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Test-BjfCqOED.mjs.map
