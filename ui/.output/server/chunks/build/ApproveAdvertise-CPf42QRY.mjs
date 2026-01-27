import { mergeProps, withCtx, createVNode, createBlock, openBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderList, ssrRenderStyle, ssrRenderAttr, ssrRenderAttrs, ssrInterpolate } from 'vue/server-renderer';
import MainLayout from './main-BqvhuwHD.mjs';
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
  __name: "ApproveAdvertCard",
  __ssrInlineRender: true,
  props: {
    advert: Object
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded mb-4 border-l-4 border-orange-500" }, _attrs))}><h4 class="font-bold">Advert Approval</h4><p>Title: ${ssrInterpolate(__props.advert.title)}</p><div class="mt-2 text-sm text-gray-500"><button class="bg-green-500 text-white px-2 py-1 rounded mr-2">Approve</button><button class="bg-red-500 text-white px-2 py-1 rounded">Reject</button></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/earn/advertise/ApproveAdvertCard.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "ApproveAdvertise",
  __ssrInlineRender: true,
  props: {
    advertises: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({ title: "\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E2A\u0E37\u0E48\u0E2D\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E2A\u0E31\u0E21\u0E1E\u0E31\u0E19\u0E18\u0E4C" }, _attrs), {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center max-w-full mx-auto mt-2 md:mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg" style="${ssrRenderStyle({ backgroundImage: "url(/storage/images/banner/banner-bg.png)" })}"${_scopeId}><img class="section-banner-icon hidden md:block"${ssrRenderAttr("src", "/storage/images/banner/badges-icon.png")} alt="forums-icon"${_scopeId}><p class="text-white font-bold text-sm md:text-xl p-2"${_scopeId}>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E37\u0E48\u0E2D\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E2A\u0E31\u0E21\u0E1E\u0E31\u0E19\u0E18\u0E4C\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32</p></div>`);
          } else {
            return [
              createVNode("div", {
                class: "flex items-center max-w-full mx-auto mt-2 md:mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg",
                style: { backgroundImage: "url(/storage/images/banner/banner-bg.png)" }
              }, [
                createVNode("img", {
                  class: "section-banner-icon hidden md:block",
                  src: "/storage/images/banner/badges-icon.png",
                  alt: "forums-icon"
                }),
                createVNode("p", { class: "text-white font-bold text-sm md:text-xl p-2" }, "\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E37\u0E48\u0E2D\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E2A\u0E31\u0E21\u0E1E\u0E31\u0E19\u0E18\u0E4C\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32")
              ])
            ];
          }
        }),
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="py-4"${_scopeId}>`);
            if (props.advertises.data.length) {
              _push2(`<div class="mx-auto max-w-full flex flex-wrap justify-center gap-2"${_scopeId}><!--[-->`);
              ssrRenderList(props.advertises.data, (advert, index) => {
                _push2(`<div class="w-full md:w-[48%] xl:w-[30%]"${_scopeId}>`);
                _push2(ssrRenderComponent(_sfc_main$1, { advert }, null, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="text-center"${_scopeId}><p class="text-gray-500"${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</p></div>`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "py-4" }, [
                props.advertises.data.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mx-auto max-w-full flex flex-wrap justify-center gap-2"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(props.advertises.data, (advert, index) => {
                    return openBlock(), createBlock("div", {
                      key: index,
                      class: "w-full md:w-[48%] xl:w-[30%]"
                    }, [
                      createVNode(_sfc_main$1, { advert }, null, 8, ["advert"])
                    ]);
                  }), 128))
                ])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center"
                }, [
                  createVNode("p", { class: "text-gray-500" }, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49")
                ]))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/PlearndAdmin/Support/ApproveAdvertise.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=ApproveAdvertise-CPf42QRY.mjs.map
