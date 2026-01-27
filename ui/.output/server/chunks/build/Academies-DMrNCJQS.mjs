import { ref, withCtx, unref, createVNode, createBlock, withModifiers, toDisplayString, openBlock, Fragment, renderList, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderClass, ssrInterpolate, ssrRenderList, ssrRenderStyle, ssrRenderAttr } from 'vue/server-renderer';
import { a as usePage, L as Link } from './inertia-vue3-CWdJjaLG.mjs';
import MainLayout from './main-BqvhuwHD.mjs';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { _ as _export_sfc } from './server.mjs';
import 'unhead/utils';
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
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import 'pinia';
import './useGamification-BliN7lma.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';

const _sfc_main$2 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "animate-pulse space-y-4" }, _attrs))}><div class="h-40 bg-gray-200 rounded"></div><div class="h-4 bg-gray-200 rounded w-3/4"></div></div>`);
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/AcademiesLoadingSkeleton.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const AcademiesLoadingSkeleton = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main$1 = {
  __name: "AcademyCard",
  __ssrInlineRender: true,
  props: {
    academy: Object
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white shadow rounded-lg overflow-hidden" }, _attrs))}><div class="h-40 bg-gray-300 w-full bg-cover bg-center" style="${ssrRenderStyle({ backgroundImage: `url(${__props.academy.cover || "/storage/images/academies/covers/default_cover.png"})` })}"></div><div class="p-4"><div class="flex items-center -mt-12"><img${ssrRenderAttr("src", __props.academy.logo || "/storage/images/academies/logos/default_logo.png")} class="w-16 h-16 rounded-full border-4 border-white bg-white"><h3 class="ml-2 font-bold text-lg mt-8">${ssrInterpolate(__props.academy.name)}</h3></div><p class="text-gray-600 mt-2 text-sm">${ssrInterpolate(__props.academy.slogan)}</p></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/AcademyCard.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Academies",
  __ssrInlineRender: true,
  setup(__props) {
    const isLoading = ref(false);
    const authUser = usePage().props.auth.user;
    const academies = ref([]);
    const academiesType = ref([
      { title: "\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19", errorMessages: "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E08\u0E49\u0E32\u0E02\u0E2D\u0E07" },
      {
        title: "\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01",
        errorMessages: "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01"
      },
      { title: "\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", errorMessages: "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49" }
    ]);
    const academiesTypeIndex = ref(1);
    const handleGetAcademies = async (index) => {
      academiesTypeIndex.value = index;
      academies.value = [];
      switch (index) {
        case 0:
          getAuthOwnerAcademies();
          break;
        case 1:
          getAuthMemberedAcademies();
          break;
        case 2:
          getAllAcademies();
          break;
      }
    };
    const getAuthOwnerAcademies = async () => {
      try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/users/${authUser.id}/my-academies`);
        if (response.data.success) {
          academies.value = response.data.academies;
          isLoading.value = false;
        } else {
          isLoading.value = false;
          Swal.fire({
            icon: "error",
            title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14! \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07",
            text: response.data.message
          });
        }
      } catch (error) {
        isLoading.value = false;
      }
    };
    const getAuthMemberedAcademies = async () => {
      try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/users/${authUser.id}/membered-academies`);
        if (response.data.success) {
          academies.value = response.data.academies;
          isLoading.value = false;
        } else {
          isLoading.value = false;
          Swal.fire({
            icon: "error",
            title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14! \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07",
            text: response.data.message
          });
        }
      } catch (error) {
        isLoading.value = false;
      }
    };
    const getAllAcademies = async () => {
      try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/all-academies`);
        if (response.data.success) {
          academies.value = response.data.academies;
          isLoading.value = false;
        } else {
          isLoading.value = false;
          Swal.fire({
            icon: "error",
            title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14! \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07",
            text: response.data.message
          });
        }
      } catch (error) {
        isLoading.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(MainLayout, { title: "Academies" }, {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div style="${ssrRenderStyle({ backgroundImage: "url(/storage/images/banner/banner-bg.png)" })}" class="flex items-center max-w-7xl mx-auto jusb mt-2 mb-4 shadow-lg bg-image bg-cover bg-no-repeat"${_scopeId}><img class="section-banner-icon"${ssrRenderAttr("src", "/storage/images/banner/forums-icon.png")} alt="forums-icon"${_scopeId}><p class="text-white font-bold text-4xl"${_scopeId}>\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</p></div>`);
          } else {
            return [
              createVNode("div", {
                style: { backgroundImage: "url(/storage/images/banner/banner-bg.png)" },
                class: "flex items-center max-w-7xl mx-auto jusb mt-2 mb-4 shadow-lg bg-image bg-cover bg-no-repeat"
              }, [
                createVNode("img", {
                  class: "section-banner-icon",
                  src: "/storage/images/banner/forums-icon.png",
                  alt: "forums-icon"
                }),
                createVNode("p", { class: "text-white font-bold text-4xl" }, "\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49")
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
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-white shadow-xl w-full max-w-7xl mx-auto rounded-lg overflow-hidden mt-4"${_scopeId}><div class="flex flex-row justify-around"${_scopeId}><button class="${ssrRenderClass([{ "border-b-4 border-cyan-500 bg-cyan-100": academiesTypeIndex.value === 0 }, "border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"])}"${_scopeId}><div class="flex flex-col items-center py-2 justify-center text-slate-700"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-6 h-6", { "text-cyan-500": academiesTypeIndex.value === 0 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass({ "text-cyan-500": academiesTypeIndex.value === 0 })}"${_scopeId}>${ssrInterpolate(academiesType.value[0].title)}</span></div></button><button class="${ssrRenderClass([{ "border-b-4 border-cyan-500 bg-cyan-100": academiesTypeIndex.value === 1 }, "border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"])}"${_scopeId}><div class="flex flex-col items-center py-2 justify-center text-slate-700"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-6 h-6", { "text-cyan-500": academiesTypeIndex.value === 1 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass({ "text-cyan-500": academiesTypeIndex.value === 1 })}"${_scopeId}>${ssrInterpolate(academiesType.value[1].title)}</span></div></button><button class="${ssrRenderClass([{ "border-b-4 border-cyan-500 bg-cyan-100": academiesTypeIndex.value === 2 }, "border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"])}"${_scopeId}><div class="flex flex-col items-center py-2 justify-center text-slate-700"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-6 h-6", { "text-cyan-500": academiesTypeIndex.value === 2 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass({ "text-cyan-500": academiesTypeIndex.value === 2 })}"${_scopeId}>${ssrInterpolate(academiesType.value[2].title)}</span></div></button></div></div><div class="section-header my-4 p-4 bg-white rounded-xl shadow-lg"${_scopeId}><div class="flex justify-between"${_scopeId}><h2 class="font-bold text-2xl text-gray-700 font-prompt"${_scopeId}>${ssrInterpolate(academiesType.value[academiesTypeIndex.value].title)} ${ssrInterpolate(" (" + academies.value.length + ")" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49")}</h2></div></div>`);
            if (isLoading.value) {
              _push2(`<div class="grid grid-cols-1 mx-auto w-1/2"${_scopeId}>`);
              _push2(ssrRenderComponent(AcademiesLoadingSkeleton, null, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (academies.value.length) {
              _push2(`<div class="flex flex-wrap justify-between gap-4"${_scopeId}><!--[-->`);
              ssrRenderList(academies.value, (academy, index) => {
                _push2(`<div class="w-full sm:w-[48%]"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Link), {
                  href: `/academies/${academy.name}`
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(ssrRenderComponent(_sfc_main$1, { academy }, null, _parent3, _scopeId2));
                    } else {
                      return [
                        createVNode(_sfc_main$1, { academy }, null, 8, ["academy"])
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="flex items-center justify-center h-48 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg"${_scopeId}><p class="text-gray-800 dark:text-gray-200"${_scopeId}>${ssrInterpolate(academiesType.value[academiesTypeIndex.value].errorMessages)}</p></div>`);
            }
          } else {
            return [
              createVNode("div", { class: "bg-white shadow-xl w-full max-w-7xl mx-auto rounded-lg overflow-hidden mt-4" }, [
                createVNode("div", { class: "flex flex-row justify-around" }, [
                  createVNode("button", {
                    onClick: withModifiers(($event) => handleGetAcademies(0), ["prevent"]),
                    class: ["border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500 bg-cyan-100": academiesTypeIndex.value === 0 }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-6 h-6", { "text-cyan-500": academiesTypeIndex.value === 0 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: { "text-cyan-500": academiesTypeIndex.value === 0 }
                      }, toDisplayString(academiesType.value[0].title), 3)
                    ])
                  ], 10, ["onClick"]),
                  createVNode("button", {
                    onClick: withModifiers(($event) => handleGetAcademies(1), ["prevent"]),
                    class: ["border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500 bg-cyan-100": academiesTypeIndex.value === 1 }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-6 h-6", { "text-cyan-500": academiesTypeIndex.value === 1 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: { "text-cyan-500": academiesTypeIndex.value === 1 }
                      }, toDisplayString(academiesType.value[1].title), 3)
                    ])
                  ], 10, ["onClick"]),
                  createVNode("button", {
                    onClick: withModifiers(($event) => handleGetAcademies(2), ["prevent"]),
                    class: ["border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500 bg-cyan-100": academiesTypeIndex.value === 2 }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-6 h-6", { "text-cyan-500": academiesTypeIndex.value === 2 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: { "text-cyan-500": academiesTypeIndex.value === 2 }
                      }, toDisplayString(academiesType.value[2].title), 3)
                    ])
                  ], 10, ["onClick"])
                ])
              ]),
              createVNode("div", { class: "section-header my-4 p-4 bg-white rounded-xl shadow-lg" }, [
                createVNode("div", { class: "flex justify-between" }, [
                  createVNode("h2", { class: "font-bold text-2xl text-gray-700 font-prompt" }, toDisplayString(academiesType.value[academiesTypeIndex.value].title) + " " + toDisplayString(" (" + academies.value.length + ")" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"), 1)
                ])
              ]),
              isLoading.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "grid grid-cols-1 mx-auto w-1/2"
              }, [
                createVNode(AcademiesLoadingSkeleton)
              ])) : academies.value.length ? (openBlock(), createBlock("div", {
                key: 1,
                class: "flex flex-wrap justify-between gap-4"
              }, [
                (openBlock(true), createBlock(Fragment, null, renderList(academies.value, (academy, index) => {
                  return openBlock(), createBlock("div", {
                    key: index,
                    class: "w-full sm:w-[48%]"
                  }, [
                    createVNode(unref(Link), {
                      href: `/academies/${academy.name}`
                    }, {
                      default: withCtx(() => [
                        createVNode(_sfc_main$1, { academy }, null, 8, ["academy"])
                      ]),
                      _: 2
                    }, 1032, ["href"])
                  ]);
                }), 128))
              ])) : (openBlock(), createBlock("div", {
                key: 2,
                class: "flex items-center justify-center h-48 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg"
              }, [
                createVNode("p", { class: "text-gray-800 dark:text-gray-200" }, toDisplayString(academiesType.value[academiesTypeIndex.value].errorMessages), 1)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/Academies.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Academies-DMrNCJQS.mjs.map
