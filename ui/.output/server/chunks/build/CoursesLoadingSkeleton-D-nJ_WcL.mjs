import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';

const _sfc_main = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "animate-pulse bg-white rounded-lg p-4 h-64" }, _attrs))}><div class="h-40 bg-gray-200 rounded mb-4"></div><div class="h-6 bg-gray-200 rounded w-3/4 mb-2"></div><div class="h-4 bg-gray-200 rounded w-1/2"></div></div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/CoursesLoadingSkeleton.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CoursesLoading = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);

export { CoursesLoading as C };
//# sourceMappingURL=CoursesLoadingSkeleton-D-nJ_WcL.mjs.map
