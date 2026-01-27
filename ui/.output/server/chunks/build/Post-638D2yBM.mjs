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
  __name: "SinglePostViewer",
  __ssrInlineRender: true,
  props: {
    activity: Object
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded mb-4" }, _attrs))}><div class="flex items-center mb-2"><div class="font-bold">${ssrInterpolate(((_a = __props.activity.actor) == null ? void 0 : _a.name) || "User")}</div></div><p>${ssrInterpolate(((_b = __props.activity.data) == null ? void 0 : _b.content) || "Post content")}</p></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/posts/SinglePostViewer.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Post",
  __ssrInlineRender: true,
  props: {
    post_id: Number
  },
  setup(__props) {
    const isLoadingPost = ref(true);
    const activity = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(MainLayout, { title: "Post/\u0E42\u0E1E\u0E2A\u0E15\u0E4C" }, {
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (isLoadingPost.value && !activity.value) {
              _push2(`<div${_scopeId}>`);
              _push2(ssrRenderComponent(PostSkeleton, null, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<div${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$1, { activity: activity.value }, null, _parent2, _scopeId));
              _push2(`</div>`);
            }
          } else {
            return [
              isLoadingPost.value && !activity.value ? (openBlock(), createBlock("div", { key: 0 }, [
                createVNode(PostSkeleton)
              ])) : (openBlock(), createBlock("div", { key: 1 }, [
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Post.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Post-638D2yBM.mjs.map
