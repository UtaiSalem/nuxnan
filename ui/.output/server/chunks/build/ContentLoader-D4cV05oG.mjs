import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';

const _sfc_main = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex justify-center items-center h-40" }, _attrs))}><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div></div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/ContentLoader.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ContentLoader = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);

export { ContentLoader as C };
//# sourceMappingURL=ContentLoader-D4cV05oG.mjs.map
