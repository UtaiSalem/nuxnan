import { defineComponent, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "RichTextViewer",
  __ssrInlineRender: true,
  props: {
    content: {},
    class: { default: "" }
  },
  setup(__props) {
    const props = __props;
    const sanitizedContent = computed(() => {
      if (!props.content) return "";
      let clean = props.content;
      if (!clean.includes("<p>") && !clean.includes("<div>")) {
        clean = clean.replace(/\n/g, "<br>");
      }
      return clean;
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      if (__props.content) {
        _push(`<div${ssrRenderAttrs(mergeProps({
          class: ["prose prose-lg dark:prose-invert max-w-none", props.class]
        }, _attrs))} data-v-9e957e82>${(_a = sanitizedContent.value) != null ? _a : ""}</div>`);
      } else {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "text-gray-500 dark:text-gray-400 italic" }, _attrs))} data-v-9e957e82> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32 </div>`);
      }
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/RichTextViewer.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const __nuxt_component_1 = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-9e957e82"]]);

export { __nuxt_component_1 as _ };
//# sourceMappingURL=RichTextViewer-UnGuFLT8.mjs.map
