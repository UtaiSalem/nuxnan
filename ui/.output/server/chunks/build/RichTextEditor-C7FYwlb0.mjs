import { defineComponent, ref, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "RichTextEditor",
  __ssrInlineRender: true,
  props: {
    modelValue: {},
    placeholder: { default: "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E02\u0E35\u0E22\u0E19\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32..." },
    disabled: { type: Boolean, default: false },
    class: { default: "" }
  },
  emits: ["update:modelValue"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const editorRef = ref();
    const content = ref(props.modelValue || "");
    const isBold = ref(false);
    const isItalic = ref(false);
    const isUnderline = ref(false);
    watch(() => props.modelValue, (newValue) => {
      if (newValue !== content.value && editorRef.value) {
        content.value = newValue || "";
        editorRef.value.innerHTML = content.value;
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["rich-text-editor", props.class]
      }, _attrs))} data-v-76b3cd14>`);
      if (!__props.disabled) {
        _push(`<div class="toolbar bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-t-xl p-2 flex flex-wrap gap-1" data-v-76b3cd14><div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2" data-v-76b3cd14><button type="button" class="${ssrRenderClass(["toolbar-btn", isBold.value && "active"])}" title="Bold (Ctrl+B)" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-bold-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass(["toolbar-btn", isItalic.value && "active"])}" title="Italic (Ctrl+I)" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-italic-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="${ssrRenderClass(["toolbar-btn", isUnderline.value && "active"])}" title="Underline (Ctrl+U)" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-underline-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="toolbar-btn" title="Strikethrough" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-strikethrough-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div><div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2" data-v-76b3cd14><button type="button" class="toolbar-btn" title="Heading 1" data-v-76b3cd14> H1 </button><button type="button" class="toolbar-btn" title="Heading 2" data-v-76b3cd14> H2 </button><button type="button" class="toolbar-btn" title="Heading 3" data-v-76b3cd14> H3 </button><button type="button" class="toolbar-btn" title="Paragraph" data-v-76b3cd14> P </button></div><div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2" data-v-76b3cd14><button type="button" class="toolbar-btn" title="Bullet List" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-bullet-list-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="toolbar-btn" title="Numbered List" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-number-list-ltr-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div><div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2" data-v-76b3cd14><button type="button" class="toolbar-btn" title="Align Left" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-align-left-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="toolbar-btn" title="Align Center" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-align-center-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="toolbar-btn" title="Align Right" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-align-right-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div><div class="flex gap-1" data-v-76b3cd14><button type="button" class="toolbar-btn" title="Insert Link" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:link-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button type="button" class="toolbar-btn" title="Remove Link" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:link-dismiss-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div><div class="flex gap-1 ml-auto" data-v-76b3cd14><button type="button" class="toolbar-btn" title="Clear Formatting" data-v-76b3cd14>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-clear-formatting-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div${ssrRenderAttr("contenteditable", !__props.disabled)} class="${ssrRenderClass([
        "editor-content",
        __props.disabled ? "min-h-[50px]" : "min-h-[300px] max-h-[600px] overflow-y-auto",
        "p-4 bg-white dark:bg-gray-900",
        "border border-gray-300 dark:border-gray-600",
        __props.disabled ? "rounded-xl cursor-default" : "rounded-b-xl border-t-0",
        !__props.disabled && "focus:outline-none focus:ring-2 focus:ring-blue-500",
        "prose prose-lg dark:prose-invert max-w-none whitespace-pre-wrap"
      ])}"${ssrRenderAttr("data-placeholder", __props.disabled ? "" : __props.placeholder)} data-v-76b3cd14></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/RichTextEditor.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const __nuxt_component_0 = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-76b3cd14"]]);

export { __nuxt_component_0 as _ };
//# sourceMappingURL=RichTextEditor-C7FYwlb0.mjs.map
