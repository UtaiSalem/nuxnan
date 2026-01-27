import { unref, mergeProps, withCtx, createVNode, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderAttr } from 'vue/server-renderer';
import { _ as _imports_0 } from './virtual_public-CJ1CIvfL.mjs';
import { L as Link } from './inertia-vue3-CWdJjaLG.mjs';

const _sfc_main = {
  __name: "AuthenticationCardLogo",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(Link), mergeProps({ href: "/" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<figure${_scopeId}><img${ssrRenderAttr("src", _imports_0)} alt="Plearnd Logo" class="w-20 h-20"${_scopeId}></figure>`);
          } else {
            return [
              createVNode("figure", null, [
                createVNode("img", {
                  src: _imports_0,
                  alt: "Plearnd Logo",
                  class: "w-20 h-20"
                })
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AuthenticationCardLogo.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=AuthenticationCardLogo-DVECfwqv.mjs.map
