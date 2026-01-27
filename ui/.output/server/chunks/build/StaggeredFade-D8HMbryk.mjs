import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderSlotInner } from 'vue/server-renderer';

const _sfc_main = {
  __name: "StaggeredFade",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<ul${ssrRenderAttrs(mergeProps({ css: false }, _attrs))}>`);
      ssrRenderSlotInner(_ctx.$slots, "default", {}, null, _push, _parent, null, true);
      _push(`</ul>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/StaggeredFade.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=StaggeredFade-D8HMbryk.mjs.map
