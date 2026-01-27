import { ref, mergeProps, withCtx, renderSlot, createVNode, unref, createBlock, createCommentVNode, openBlock, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderSlot, ssrRenderStyle, ssrRenderAttr, ssrRenderClass } from 'vue/server-renderer';
import { a as usePage, r as router } from './inertia-vue3-CWdJjaLG.mjs';
import { Icon } from '@iconify/vue';
import MainLayout from './main-BqvhuwHD.mjs';
import './server.mjs';
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
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import './nuxt-link-Dhr1c_cd.mjs';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';

const _sfc_main = {
  __name: "CoursesLayout",
  __ssrInlineRender: true,
  props: {
    coursePageTitle: String
  },
  setup(__props) {
    const authUser = usePage().props.auth.user;
    const isLoadingPage = ref(false);
    const handleLoadingPage = (option) => {
      isLoadingPage.value = true;
      switch (option) {
        case 1:
          router.visit(`/courses/users/${usePage().props.auth.user.id}`);
          break;
        case 2:
          router.visit(`/courses/users/${usePage().props.auth.user.id}/member`);
          break;
        case 3:
          router.visit(`/courses`);
          break;
        case 4:
          router.visit(`/courses/create`);
          break;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({ title: __props.coursePageTitle }, _attrs), {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (isLoadingPage.value) {
              _push2(`<div class="fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal"${_scopeId}><div class="absolute w-full h-full bg-gray-900 opacity-75 modal-overlay"${_scopeId}></div><div class="flex items-center justify-center h-64"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:bars-rotate-fade",
                class: "z-30 w-32 h-32 text-white"
              }, null, _parent2, _scopeId));
              _push2(`</div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="hidden md:flex items-center max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg" style="${ssrRenderStyle({ backgroundImage: "url(/storage/images/banner/banner-bg.png)" })}"${_scopeId}><img class="section-banner-icon"${ssrRenderAttr("src", "/storage/images/banner/forums-icon.png")} alt="forums-icon"${_scopeId}><p class="text-xl lg:text-4xl font-bold text-white"${_scopeId}>\u0E23\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div><div class="w-full mx-auto mt-4 overflow-hidden bg-white rounded-lg shadow-xl max-w-7xl"${_scopeId}><div class="flex flex-row justify-around"${_scopeId}><button type="button" class="${ssrRenderClass([{
              "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}`
            }, "flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400"])}"${_scopeId}><div class="flex flex-col items-center justify-center py-2 text-slate-700"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-8 h-8", {
                "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}`
              }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass({
              "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}`
            })}"${_scopeId}>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</span></div></button><button type="button" class="${ssrRenderClass([{
              "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}/member`
            }, "flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400"])}"${_scopeId}><div class="flex flex-col items-center justify-center py-2 text-slate-700"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-8 h-8", {
                "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}/member`
              }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass({
              "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}/member`
            })}"${_scopeId}>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></div></button><button type="button" class="${ssrRenderClass([{ "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses` }, "flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400"])}"${_scopeId}><div class="flex flex-col items-center justify-center py-2 text-slate-700"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-8 h-8", { "text-cyan-500": _ctx.$page.url === `/courses` }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass({ "text-cyan-500": _ctx.$page.url === `/courses` })}"${_scopeId}>\u0E23\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></div></button>`);
            if (unref(authUser).pp > 12e4) {
              _push2(`<button type="button" class="${ssrRenderClass([{ "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses/create` }, "flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400"])}"${_scopeId}><div class="flex flex-col items-center justify-center py-2 text-slate-700"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "lucide:layout-list",
                class: ["w-8 h-8", { "text-cyan-500": _ctx.$page.url === `/courses/create` }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass({ "text-cyan-500": _ctx.$page.url === `/courses/create` })}"${_scopeId}>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48</span></div></button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            return [
              isLoadingPage.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal"
              }, [
                createVNode("div", { class: "absolute w-full h-full bg-gray-900 opacity-75 modal-overlay" }),
                createVNode("div", { class: "flex items-center justify-center h-64" }, [
                  createVNode(unref(Icon), {
                    icon: "svg-spinners:bars-rotate-fade",
                    class: "z-30 w-32 h-32 text-white"
                  })
                ])
              ])) : createCommentVNode("", true),
              createVNode("div", {
                class: "hidden md:flex items-center max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg",
                style: { backgroundImage: "url(/storage/images/banner/banner-bg.png)" }
              }, [
                createVNode("img", {
                  class: "section-banner-icon",
                  src: "/storage/images/banner/forums-icon.png",
                  alt: "forums-icon"
                }),
                createVNode("p", { class: "text-xl lg:text-4xl font-bold text-white" }, "\u0E23\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
              ]),
              createVNode("div", { class: "w-full mx-auto mt-4 overflow-hidden bg-white rounded-lg shadow-xl max-w-7xl" }, [
                createVNode("div", { class: "flex flex-row justify-around" }, [
                  createVNode("button", {
                    type: "button",
                    onClick: ($event) => handleLoadingPage(1),
                    class: ["flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400", {
                      "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}`
                    }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center justify-center py-2 text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", {
                          "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}`
                        }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: {
                          "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}`
                        }
                      }, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19", 2)
                    ])
                  ], 10, ["onClick"]),
                  createVNode("button", {
                    type: "button",
                    onClick: ($event) => handleLoadingPage(2),
                    class: ["flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400", {
                      "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}/member`
                    }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center justify-center py-2 text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", {
                          "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}/member`
                        }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: {
                          "text-cyan-500": _ctx.$page.url === `/courses/users/${_ctx.$page.props.auth.user.id}/member`
                        }
                      }, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", 2)
                    ])
                  ], 10, ["onClick"]),
                  createVNode("button", {
                    type: "button",
                    onClick: ($event) => handleLoadingPage(3),
                    class: ["flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400", { "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses` }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center justify-center py-2 text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", { "text-cyan-500": _ctx.$page.url === `/courses` }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: { "text-cyan-500": _ctx.$page.url === `/courses` }
                      }, "\u0E23\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", 2)
                    ])
                  ], 10, ["onClick"]),
                  unref(authUser).pp > 12e4 ? (openBlock(), createBlock("button", {
                    key: 0,
                    type: "button",
                    onClick: ($event) => handleLoadingPage(4),
                    class: ["flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400", { "border-b-4 border-cyan-500 bg-cyan-100": _ctx.$page.url === `/courses/create` }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center justify-center py-2 text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", { "text-cyan-500": _ctx.$page.url === `/courses/create` }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: { "text-cyan-500": _ctx.$page.url === `/courses/create` }
                      }, "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48", 2)
                    ])
                  ], 10, ["onClick"])) : createCommentVNode("", true)
                ])
              ])
            ];
          }
        }),
        leftSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div${_scopeId}></div>`);
          } else {
            return [
              createVNode("div")
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            ssrRenderSlot(_ctx.$slots, "coursesMainContent", {}, null, _push2, _parent2, _scopeId);
          } else {
            return [
              renderSlot(_ctx.$slots, "coursesMainContent")
            ];
          }
        }),
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/CoursesLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CoursesLayout-Bw6-53KN.mjs.map
