import { ref, withCtx, createBlock, openBlock, createVNode, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import MainLayout from './main-BqvhuwHD.mjs';
import { P as PostSkeleton } from './PostSkeleton-NAoebJWP.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
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
import './server.mjs';
import 'pinia';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';

const _sfc_main$1 = {
  __name: "EditPostForm",
  __ssrInlineRender: true,
  props: ["activity"],
  setup(__props) {
    var _a, _b;
    const props = __props;
    const content = ref(((_b = (_a = props.activity) == null ? void 0 : _a.data) == null ? void 0 : _b.content) || "");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-6 shadow rounded-lg" }, _attrs))}><h2 class="text-xl font-bold mb-4">Edit Post</h2><textarea class="w-full border rounded p-2">${ssrInterpolate(content.value)}</textarea><button class="bg-blue-600 text-white px-4 py-2 rounded mt-2">Update Post</button></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/EditPostForm.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "EditPost",
  __ssrInlineRender: true,
  props: {
    post_id: Number
  },
  setup(__props) {
    const isLoadingPost = ref(true);
    const activity = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(MainLayout, { title: "Edit Post/\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1E\u0E2A\u0E15\u0E4C" }, {
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (isLoadingPost.value && !activity.value) {
              _push2(`<div${_scopeId}>`);
              _push2(ssrRenderComponent(PostSkeleton, null, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<div class="my-4"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$1, { activity: activity.value }, null, _parent2, _scopeId));
              _push2(`</div>`);
            }
          } else {
            return [
              isLoadingPost.value && !activity.value ? (openBlock(), createBlock("div", { key: 0 }, [
                createVNode(PostSkeleton)
              ])) : (openBlock(), createBlock("div", {
                key: 1,
                class: "my-4"
              }, [
                createVNode(_sfc_main$1, { activity: activity.value }, null, 8, ["activity"])
              ]))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/EditPost.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=EditPost-DRzmpdK0.mjs.map
