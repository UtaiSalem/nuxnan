import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';

const _sfc_main = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "py-4 rounded shadow-md w-full animate-pulse bg-gray-50" }, _attrs))}><div class="flex p-4 space-x-4 sm:px-8"><div class="flex-shrink-0 w-16 h-16 rounded-full bg-gray-300"></div><div class="flex-1 py-2 space-y-4"><div class="w-full h-3 rounded bg-gray-300"></div><div class="w-5/6 h-3 rounded bg-gray-300"></div></div></div><div class="p-4 space-y-4 sm:px-8"><div class="w-full h-4 rounded bg-gray-300"></div><div class="w-full h-4 rounded bg-gray-300"></div><div class="w-3/4 h-4 rounded bg-gray-300"></div></div></div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/PostSkeleton.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const PostSkeleton = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender]]);

export { PostSkeleton as P };
//# sourceMappingURL=PostSkeleton-NAoebJWP.mjs.map
