import { ref, mergeProps, withCtx, createVNode, toDisplayString, createBlock, openBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { u as useForm } from './inertia-vue3-CWdJjaLG.mjs';
import Swal from 'sweetalert2';
import MainLayout from './main-BqvhuwHD.mjs';
import { _ as _sfc_main$1 } from './AcademyCoverProfile-C2_VR89F.mjs';
import { C as CourseCard } from './CourseCard-CBxRij-n.mjs';
import { _ as _sfc_main$2 } from './AcademyNavbarTab-BFhMwTIK.mjs';
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

const _sfc_main = {
  __name: "AcademyCourses",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    courses: Object,
    isAcademyAdmin: Boolean,
    app_url: String
  },
  setup(__props) {
    const props = __props;
    ref(false);
    const tempLogo = ref(props.academy.data.logo ? "/storage/images/academies/logos/" + props.academy.data.logo : "/storage/images/academies/logos/default_logo.png");
    const tempCover = ref(props.academy.data.cover ? "/storage/images/academies/covers/" + props.academy.data.cover : "/storage/images/academies/covers/default_cover.png");
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
      _push(ssrRenderComponent(MainLayout, mergeProps({
        title: "Academy",
        appUrl: props.app_url
      }, _attrs), {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class=""${_scopeId}>`);
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
              onRequestTobeMember: ($event) => onRequestToBeAMember()
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              academy: __props.academy,
              activeTab: 1
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", { class: "" }, [
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
                  onRequestTobeMember: ($event) => onRequestToBeAMember()
                }, null, 8, ["coverImage", "logoImage", "coverHeader", "onUpdate:coverHeader", "coverSubheader", "onUpdate:coverSubheader", "modelableId", "modelableRoute", "modelData", "subModelData", "isAcademyAdmin", "onCoverImageChange", "onLogoImageChange", "onHeaderChange", "onSubheaderChange", "onRequestTobeMember"])
              ]),
              createVNode(_sfc_main$2, {
                academy: __props.academy,
                activeTab: 1
              }, null, 8, ["academy"])
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
            _push2(`<div class="section-header my-4 p-4 bg-white rounded-xl shadow-lg"${_scopeId}><div class="section-header-info"${_scopeId}><h2 class="section-title font-prompt"${_scopeId}>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(" " + props.courses.data.length + " \u0E27\u0E34\u0E0A\u0E32" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")}</h2></div></div><div class="flex flex-wrap justify-between gap-4"${_scopeId}><!--[-->`);
            ssrRenderList(props.courses.data, (course, index) => {
              _push2(`<div class="w-full sm:w-[48%]"${_scopeId}>`);
              _push2(ssrRenderComponent(CourseCard, { course }, null, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "section-header my-4 p-4 bg-white rounded-xl shadow-lg" }, [
                createVNode("div", { class: "section-header-info" }, [
                  createVNode("h2", { class: "section-title font-prompt" }, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 " + toDisplayString(" " + props.courses.data.length + " \u0E27\u0E34\u0E0A\u0E32" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"), 1)
                ])
              ]),
              createVNode("div", { class: "flex flex-wrap justify-between gap-4" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(props.courses.data, (course, index) => {
                  return openBlock(), createBlock("div", {
                    key: index,
                    class: "w-full sm:w-[48%]"
                  }, [
                    createVNode(CourseCard, { course }, null, 8, ["course"])
                  ]);
                }), 128))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/AcademyCourses.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AcademyCourses-Cm46b-yR.mjs.map
