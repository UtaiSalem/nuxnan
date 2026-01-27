import { ref, mergeProps, withCtx, unref, createVNode, createBlock, createCommentVNode, openBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderList, ssrRenderStyle, ssrRenderAttr, ssrRenderAttrs, ssrInterpolate } from 'vue/server-renderer';
import MainLayout from './main-CdHCodS1.mjs';
import InfiniteLoading from 'v3-infinite-loading';
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
  __name: "DonateCard",
  __ssrInlineRender: true,
  props: {
    donate: Object
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded mb-4 border-l-4 border-blue-500" }, _attrs))}><h4 class="font-bold">Donation Request</h4><p>Donor: ${ssrInterpolate(((_a = __props.donate.donor) == null ? void 0 : _a.name) || "Unknown")}</p><p>Points: ${ssrInterpolate(__props.donate.point || 0)}</p></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/earn/donates/DonateCard.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "DonationList",
  __ssrInlineRender: true,
  props: {
    donates: Object
  },
  setup(__props) {
    var _a;
    const props = __props;
    const isLoadingDonates = ref(false);
    const refDonatesData = ref((_a = props.donates.data) != null ? _a : []);
    const nextPageUrl = ref(
      props.donates.links.next ? "/plearnd-admin/supports/donates/more?page=2" : null
    );
    const fetchMoreDonations = async () => {
      try {
        if (!nextPageUrl.value) return;
        isLoadingDonates.value = true;
        const moreDonatesResp = await axios.get(nextPageUrl.value);
        if (moreDonatesResp.data) {
          moreDonatesResp.data.donates.data.forEach((donate) => {
            refDonatesData.value.push(donate);
          });
          nextPageUrl.value = moreDonatesResp.data.donates.next_page_url;
        }
        isLoadingDonates.value = false;
      } catch (error) {
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({ title: "Donate List" }, _attrs), {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center max-w-full mx-auto mt-2 mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg" style="${ssrRenderStyle({ backgroundImage: "url(/storage/images/banner/banner-bg.png)" })}"${_scopeId}><img class="section-banner-icon"${ssrRenderAttr("src", "/storage/images/banner/badges-icon.png")} alt="forums-icon"${_scopeId}><p class="text-white font-bold text-4xl"${_scopeId}>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</p></div>`);
          } else {
            return [
              createVNode("div", {
                class: "flex items-center max-w-full mx-auto mt-2 mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg",
                style: { backgroundImage: "url(/storage/images/banner/banner-bg.png)" }
              }, [
                createVNode("img", {
                  class: "section-banner-icon",
                  src: "/storage/images/banner/badges-icon.png",
                  alt: "forums-icon"
                }),
                createVNode("p", { class: "text-white font-bold text-4xl" }, "\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49")
              ])
            ];
          }
        }),
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="py-4"${_scopeId}>`);
            if (refDonatesData.value.length) {
              _push2(`<div class="mx-auto max-w-full flex flex-wrap justify-center gap-2"${_scopeId}><!--[-->`);
              ssrRenderList(refDonatesData.value, (donate, index) => {
                _push2(`<div class="w-full md:w-[48%] xl:w-[30%]"${_scopeId}>`);
                _push2(ssrRenderComponent(_sfc_main$1, { donate }, null, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="text-center"${_scopeId}><p class="text-gray-500"${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</p></div>`);
            }
            _push2(ssrRenderComponent(unref(InfiniteLoading), {
              onDistance: ($event) => 1,
              onInfinite: fetchMoreDonations
            }, null, _parent2, _scopeId));
            if (isLoadingDonates.value) {
              _push2(`<div class="w-full flex justify-center items-center mt-2 text-violet-600"${_scopeId}><svg width="120" height="120" fill="currentColor" class="m-2 animate-spin" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"${_scopeId}><path d="M526 1394q0 53-37.5 90.5t-90.5 37.5q-52 0-90-38t-38-90q0-53 37.5-90.5t90.5-37.5 90.5 37.5 37.5 90.5zm498 206q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-704-704q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm1202 498q0 52-38 90t-90 38q-53 0-90.5-37.5t-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-964-996q0 66-47 113t-113 47-113-47-47-113 47-113 113-47 113 47 47 113zm1170 498q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-640-704q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm530 206q0 93-66 158.5t-158 65.5q-93 0-158.5-65.5t-65.5-158.5q0-92 65.5-158t158.5-66q92 0 158 66t66 158z"${_scopeId}></path></svg></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "py-4" }, [
                refDonatesData.value.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mx-auto max-w-full flex flex-wrap justify-center gap-2"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(refDonatesData.value, (donate, index) => {
                    return openBlock(), createBlock("div", {
                      key: index,
                      class: "w-full md:w-[48%] xl:w-[30%]"
                    }, [
                      createVNode(_sfc_main$1, { donate }, null, 8, ["donate"])
                    ]);
                  }), 128))
                ])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center"
                }, [
                  createVNode("p", { class: "text-gray-500" }, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49")
                ])),
                createVNode(unref(InfiniteLoading), {
                  onDistance: ($event) => 1,
                  onInfinite: fetchMoreDonations
                }),
                isLoadingDonates.value ? (openBlock(), createBlock("div", {
                  key: 2,
                  class: "w-full flex justify-center items-center mt-2 text-violet-600"
                }, [
                  (openBlock(), createBlock("svg", {
                    width: "120",
                    height: "120",
                    fill: "currentColor",
                    class: "m-2 animate-spin",
                    viewBox: "0 0 1792 1792",
                    xmlns: "http://www.w3.org/2000/svg"
                  }, [
                    createVNode("path", { d: "M526 1394q0 53-37.5 90.5t-90.5 37.5q-52 0-90-38t-38-90q0-53 37.5-90.5t90.5-37.5 90.5 37.5 37.5 90.5zm498 206q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-704-704q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm1202 498q0 52-38 90t-90 38q-53 0-90.5-37.5t-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-964-996q0 66-47 113t-113 47-113-47-47-113 47-113 113-47 113 47 47 113zm1170 498q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-640-704q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm530 206q0 93-66 158.5t-158 65.5q-93 0-158.5-65.5t-65.5-158.5q0-92 65.5-158t158.5-66q92 0 158 66t66 158z" })
                  ]))
                ])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/PlearndAdmin/Donation/DonationList.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=DonationList-D0alTh02.mjs.map
