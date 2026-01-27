import { defineComponent, withCtx, createVNode, useSSRContext } from 'vue';
import { ssrRenderComponent } from 'vue/server-renderer';
import { _ as _sfc_main$1 } from './BaseCard-Baxif1fS.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Messages",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-center py-12"${_scopeId}><h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>Messages</h2><p class="text-gray-500 dark:text-gray-400"${_scopeId}>This feature is coming soon!</p></div>`);
          } else {
            return [
              createVNode("div", { class: "text-center py-12" }, [
                createVNode("h2", { class: "text-2xl font-bold text-gray-900 dark:text-white mb-2" }, "Messages"),
                createVNode("p", { class: "text-gray-500 dark:text-gray-400" }, "This feature is coming soon!")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Messages.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Messages-BG3Q6TUh.mjs.map
