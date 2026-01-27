import { ref, withCtx, createVNode, unref, createTextVNode, toDisplayString, createBlock, openBlock, Fragment, renderList, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderStyle, ssrRenderAttr } from 'vue/server-renderer';
import axios from 'axios';
import { u as useForm, L as Link, H as Head } from './inertia-vue3-CWdJjaLG.mjs';
import MainLayout from './main-CdHCodS1.mjs';
import Swal from 'sweetalert2';
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
import '@iconify/vue';
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

const _sfc_main$2 = {
  __name: "Navbar",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<nav${ssrRenderAttrs(mergeProps({ class: "bg-white shadow p-4" }, _attrs))}><div class="flex justify-between items-center"><div class="font-bold">Plearnd</div><div class="flex space-x-4">`);
      _push(ssrRenderComponent(unref(Link), { href: "/" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Home`);
          } else {
            return [
              createTextVNode("Home")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></nav>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/partials/Navbar.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "ProfileCover",
  __ssrInlineRender: true,
  props: {
    coverImage: String,
    logoImage: String,
    coverHeader: String,
    coverSubheader: String,
    model: String,
    modelTable: String,
    modelableId: Number,
    modelableType: String,
    modelableRoute: String,
    modelData: Object,
    subModelData: Array,
    subModelNameTh: String
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative bg-white shadow rounded-lg overflow-hidden mb-4" }, _attrs))}><div class="h-64 bg-gray-300 w-full bg-cover bg-center" style="${ssrRenderStyle({ backgroundImage: `url(${__props.coverImage})` })}"></div><div class="absolute bottom-4 left-4 flex items-end"><img${ssrRenderAttr("src", __props.logoImage)} class="w-32 h-32 rounded-full border-4 border-white shadow-lg bg-white"><div class="ml-4 mb-2"><h1 class="text-3xl font-bold text-white shadow-black drop-shadow-md">${ssrInterpolate(__props.coverHeader)}</h1><p class="text-white drop-shadow-md">${ssrInterpolate(__props.coverSubheader)}</p></div></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/partials/ProfileCover.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Academy",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    courses: Object,
    imagePath: String
  },
  setup(__props) {
    const props = __props;
    const isDarkMode = ref(false);
    const tempLogo = ref(props.imagePath + "storage/images/" + props.academy.data.logo || props.imagePath + "storage/images/default_logo.png");
    const tempCover = ref(props.imagePath + "storage/images/" + props.academy.data.cover || props.imagePath + "storage/images/default_cover.png");
    const tempHeader = ref(props.academy.data.name);
    const tempSubheader = ref(props.academy.data.slogan);
    useForm({
      name: "",
      slogan: "",
      address: "",
      cover: null,
      logo: null
    });
    async function onCoverImageChange(coverFile) {
      const academyCoverUpdate = new FormData();
      academyCoverUpdate.append("cover", coverFile);
      academyCoverUpdate.append("_method", "patch");
      await axios.post(`/academies/${props.academy.data.id}/update`, academyCoverUpdate);
    }
    async function onLogoImageChange(logoFile) {
      const academyLogoUpdate = new FormData();
      academyLogoUpdate.append("logo", logoFile);
      academyLogoUpdate.append("_method", "patch");
      await axios.post(`/academies/${props.academy.data.id}/update`, academyLogoUpdate);
    }
    async function onHeaderChange(academyName) {
      await axios.patch(`/academies/${props.academy.data.id}/update`, { name: academyName });
    }
    async function onSubheaderChange(academySlogan) {
      await axios.patch(`/academies/${props.academy.data.id}/update`, { slogan: academySlogan });
    }
    async function onRequestToBeAMember() {
      try {
        let memberResp = await axios.post(`/academies/${props.academy.data.id}/members`);
        if (memberResp.data.success) {
          props.academy.data.isMember = memberResp.data.isMember;
          props.academy.data.total_students = memberResp.data.totalStudents;
          if (memberResp.data.isMember) {
            Swal.fire(
              "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
              "\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
              "success"
            );
          } else {
            Swal.fire(
              "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
              "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
              "success"
            );
          }
        }
      } catch (error) {
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(MainLayout, null, {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Head), { title: "Academy" }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Head), { title: "Academy" })
            ];
          }
        }),
        navbar: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$2, {
              isDarkMode: isDarkMode.value,
              onToggleDarkMode: ($event) => isDarkMode.value = !isDarkMode.value
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$2, {
                isDarkMode: isDarkMode.value,
                onToggleDarkMode: ($event) => isDarkMode.value = !isDarkMode.value
              }, null, 8, ["isDarkMode", "onToggleDarkMode"])
            ];
          }
        }),
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$1, {
              coverImage: tempCover.value,
              logoImage: tempLogo.value,
              coverHeader: tempHeader.value,
              "onUpdate:coverHeader": ($event) => tempHeader.value = $event,
              coverSubheader: tempSubheader.value,
              "onUpdate:coverSubheader": ($event) => tempSubheader.value = $event,
              model: "Academy",
              modelTable: "academies",
              modelableId: props.academy.data.id,
              modelableType: "app/models/Academy",
              modelableRoute: `/academies/${props.academy.data.id}`,
              modelData: props.academy.data,
              subModelData: props.courses.data,
              subModelNameTh: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32",
              onCoverImageChange: (data) => {
                onCoverImageChange(data);
              },
              onLogoImageChange: (data) => {
                onLogoImageChange(data);
              },
              onHeaderChange: (data) => {
                onHeaderChange(data);
              },
              onSubheaderChange: (data) => {
                onSubheaderChange(data);
              },
              onRequestTobeMember: ($event) => onRequestToBeAMember()
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$1, {
                coverImage: tempCover.value,
                logoImage: tempLogo.value,
                coverHeader: tempHeader.value,
                "onUpdate:coverHeader": ($event) => tempHeader.value = $event,
                coverSubheader: tempSubheader.value,
                "onUpdate:coverSubheader": ($event) => tempSubheader.value = $event,
                model: "Academy",
                modelTable: "academies",
                modelableId: props.academy.data.id,
                modelableType: "app/models/Academy",
                modelableRoute: `/academies/${props.academy.data.id}`,
                modelData: props.academy.data,
                subModelData: props.courses.data,
                subModelNameTh: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32",
                onCoverImageChange: (data) => {
                  onCoverImageChange(data);
                },
                onLogoImageChange: (data) => {
                  onLogoImageChange(data);
                },
                onHeaderChange: (data) => {
                  onHeaderChange(data);
                },
                onSubheaderChange: (data) => {
                  onSubheaderChange(data);
                },
                onRequestTobeMember: ($event) => onRequestToBeAMember()
              }, null, 8, ["coverImage", "logoImage", "coverHeader", "onUpdate:coverHeader", "coverSubheader", "onUpdate:coverSubheader", "modelableId", "modelableRoute", "modelData", "subModelData", "onCoverImageChange", "onLogoImageChange", "onHeaderChange", "onSubheaderChange", "onRequestTobeMember"])
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
            _push2(`<div class="bg-white rounded-lg"${_scopeId}><!--[-->`);
            ssrRenderList(props.courses.data, (course) => {
              _push2(`<div class="my-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: `/courses/${course.id}`
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(course.name)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(course.name), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-white rounded-lg" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(props.courses.data, (course) => {
                  return openBlock(), createBlock("div", {
                    key: course.id,
                    class: "my-2"
                  }, [
                    createVNode(unref(Link), {
                      href: `/courses/${course.id}`
                    }, {
                      default: withCtx(() => [
                        createTextVNode(toDisplayString(course.name), 1)
                      ]),
                      _: 2
                    }, 1032, ["href"])
                  ]);
                }), 128))
              ])
            ];
          }
        }),
        rightSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div${_scopeId}></div>`);
          } else {
            return [
              createVNode("div")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Academy.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Academy-CQ73ky80.mjs.map
