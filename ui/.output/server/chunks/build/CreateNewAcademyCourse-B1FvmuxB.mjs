import { ref, mergeProps, withCtx, createVNode, unref, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderAttrs } from 'vue/server-renderer';
import { L as Link } from './inertia-vue3-CWdJjaLG.mjs';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import MainLayout from './main-CdHCodS1.mjs';
import { _ as _sfc_main$2 } from './AcademyCoverProfile-C2_VR89F.mjs';
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

const _sfc_main$1 = {
  __name: "CreateNewAcademyCourse",
  __ssrInlineRender: true,
  props: {
    academy: Object
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-6 shadow rounded-lg" }, _attrs))}><h2 class="text-xl font-bold mb-4">Create New Course</h2><form class="space-y-4"><input type="text" placeholder="Course Name" class="w-full border rounded p-2"><textarea placeholder="Description" class="w-full border rounded p-2"></textarea><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Create</button></form></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/CreateNewAcademyCourse.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "CreateNewAcademyCourse",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    courses: Object,
    isAcademyAdmin: Boolean
  },
  setup(__props) {
    const props = __props;
    ref(false);
    const tempLogo = ref(props.academy.data.logo ? "/storage/images/academies/logos/" + props.academy.data.logo : "/storage/images/academies/logos/default_logo.png");
    const tempCover = ref(props.academy.data.cover ? "/storage/images/academies/covers/" + props.academy.data.cover : "/storage/images/academies/covers/default_cover.png");
    const tempHeader = ref(props.academy.data.name);
    const tempSubheader = ref(props.academy.data.slogan);
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
        if (memberResp.data && memberResp.data.success) {
          props.academy.data.memberStatus = memberResp.data.memberStatus;
          props.academy.data.total_students = memberResp.data.totalStudents;
          if (memberResp.data && memberResp.data.success) {
            props.academy.data.memberStatus = memberResp.data.memberStatus;
            Swal.fire(
              "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
              "\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
              "success"
            );
          }
        } else if (memberResp.data && !memberResp.data.success) {
          Swal.fire(
            "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
            memberResp.data.msg,
            "error"
          );
        }
      } catch (error) {
      }
    }
    async function onRequestToBeUnmember() {
      try {
        let memberResp = await axios.post(`/academies/${props.academy.data.id}/unmembers`);
        if (memberResp.data && memberResp.data.success) {
          if (props.academy.data.memberStatus == 2) {
            props.academy.data.total_students--;
          }
          props.academy.data.memberStatus = null;
          Swal.fire(
            "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
            "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
            "success"
          );
        }
      } catch (error) {
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({
        title: "Academy",
        appUrl: props.app_url
      }, _attrs), {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class=""${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
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
              isAcademyAdmin: props.isAcademyAdmin,
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
              onRequestTobeMember: onRequestToBeAMember,
              onRequestTobeUnmember: onRequestToBeUnmember
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4"${_scopeId}><div class="flex flex-row justify-around"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: `/academies/${props.academy.data.id}`,
              class: ["border-b-4 hover:border-cyan-500 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": _ctx.$page.url === `/academies/${props.academy.data.id}` }]
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex flex-col items-center py-2 justify-center text-slate-700"${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "bi:view-list",
                    class: "w-8 h-8"
                  }, null, _parent3, _scopeId2));
                  _push3(`<span${_scopeId2}>\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E02\u0E48\u0E32\u0E27</span></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "bi:view-list",
                        class: "w-8 h-8"
                      }),
                      createVNode("span", null, "\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E02\u0E48\u0E32\u0E27")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: `/academies/${props.academy.data.id}/courses`,
              class: "border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex flex-col items-center py-2 justify-center text-slate-700"${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "lucide:layout-list",
                    class: "w-8 h-8"
                  }, null, _parent3, _scopeId2));
                  _push3(`<span${_scopeId2}>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: "w-8 h-8"
                      }),
                      createVNode("span", null, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: `/academies/${props.academy.data.id}/courses/create`,
              class: ["border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": _ctx.$page.url === `/academies/${props.academy.data.id}/courses/create` }]
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex flex-col items-center py-2 justify-center text-slate-700"${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "lucide:layout-list",
                    class: "w-8 h-8"
                  }, null, _parent3, _scopeId2));
                  _push3(`<span${_scopeId2}>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48</span></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: "w-8 h-8"
                      }),
                      createVNode("span", null, "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "" }, [
                createVNode(_sfc_main$2, {
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
                  isAcademyAdmin: props.isAcademyAdmin,
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
                  onRequestTobeMember: onRequestToBeAMember,
                  onRequestTobeUnmember: onRequestToBeUnmember
                }, null, 8, ["coverImage", "logoImage", "coverHeader", "onUpdate:coverHeader", "coverSubheader", "onUpdate:coverSubheader", "modelableId", "modelableRoute", "modelData", "subModelData", "isAcademyAdmin", "onCoverImageChange", "onLogoImageChange", "onHeaderChange", "onSubheaderChange"])
              ]),
              createVNode("div", { class: "bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4" }, [
                createVNode("div", { class: "flex flex-row justify-around" }, [
                  createVNode(unref(Link), {
                    href: `/academies/${props.academy.data.id}`,
                    class: ["border-b-4 hover:border-cyan-500 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": _ctx.$page.url === `/academies/${props.academy.data.id}` }]
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                        createVNode(unref(Icon), {
                          icon: "bi:view-list",
                          class: "w-8 h-8"
                        }),
                        createVNode("span", null, "\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E02\u0E48\u0E32\u0E27")
                      ])
                    ]),
                    _: 1
                  }, 8, ["href", "class"]),
                  createVNode(unref(Link), {
                    href: `/academies/${props.academy.data.id}/courses`,
                    class: "border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                        createVNode(unref(Icon), {
                          icon: "lucide:layout-list",
                          class: "w-8 h-8"
                        }),
                        createVNode("span", null, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
                      ])
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: `/academies/${props.academy.data.id}/courses/create`,
                    class: ["border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": _ctx.$page.url === `/academies/${props.academy.data.id}/courses/create` }]
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-700" }, [
                        createVNode(unref(Icon), {
                          icon: "lucide:layout-list",
                          class: "w-8 h-8"
                        }),
                        createVNode("span", null, "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48")
                      ])
                    ]),
                    _: 1
                  }, 8, ["href", "class"])
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
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="my-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$1, {
              academy: props.academy
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "my-4" }, [
                createVNode(_sfc_main$1, {
                  academy: props.academy
                }, null, 8, ["academy"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/CreateNewAcademyCourse.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CreateNewAcademyCourse-B1FvmuxB.mjs.map
