import { ref, computed, mergeProps, withCtx, createVNode, createBlock, createCommentVNode, openBlock, renderSlot, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderSlot } from 'vue/server-renderer';
import Swal from 'sweetalert2';
import MainLayout from './main-CdHCodS1.mjs';
import { _ as _sfc_main$1 } from './AcademyCoverProfile-C2_VR89F.mjs';
import { _ as _sfc_main$2 } from './AcademyNavbarTab-BFhMwTIK.mjs';
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

const _sfc_main = {
  __name: "AcademyLayout",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    isAcademyAdmin: Boolean
  },
  setup(__props) {
    const props = __props;
    ref(false);
    ref([]);
    const academyData = computed(() => {
      var _a;
      return ((_a = props.academy) == null ? void 0 : _a.data) || props.academy || {};
    });
    const tempLogo = computed(() => {
      const logo = academyData.value.logo;
      return logo ? "/storage/images/academies/logos/" + logo : "/storage/images/academies/logos/default_logo.png";
    });
    const tempCover = computed(() => {
      const cover = academyData.value.cover;
      return cover ? "/storage/images/academies/covers/" + cover : "/storage/images/academies/covers/default_cover.png";
    });
    const tempHeader = ref(academyData.value.name || "");
    const tempSubheader = ref(academyData.value.slogan || "");
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
        console.log(error);
      }
    }
    async function onRequestToBeUnmember() {
      console.log("unmember");
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
        console.log(error);
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({
        title: academyData.value.name || "Academy"
      }, _attrs), {
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
              modelableId: academyData.value.id,
              modelableType: "app/models/Academy",
              modelableRoute: `/academies/${academyData.value.id}`,
              modelData: academyData.value,
              subModelDataLength: academyData.value.courses_offered,
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
            _push2(ssrRenderComponent(_sfc_main$2, {
              academy: __props.academy,
              activeTab: 0
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
                modelableId: academyData.value.id,
                modelableType: "app/models/Academy",
                modelableRoute: `/academies/${academyData.value.id}`,
                modelData: academyData.value,
                subModelDataLength: academyData.value.courses_offered,
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
              }, null, 8, ["coverImage", "logoImage", "coverHeader", "onUpdate:coverHeader", "coverSubheader", "onUpdate:coverSubheader", "modelableId", "modelableRoute", "modelData", "subModelDataLength", "isAcademyAdmin", "onCoverImageChange", "onLogoImageChange", "onHeaderChange", "onSubheaderChange"]),
              createVNode(_sfc_main$2, {
                academy: __props.academy,
                activeTab: 0
              }, null, 8, ["academy"])
            ];
          }
        }),
        leftSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4"${_scopeId}></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4" })
            ];
          }
        }),
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mt-4"${_scopeId}>`);
            if (_ctx.$slots.academyContent) {
              _push2(`<div${_scopeId}>`);
              ssrRenderSlot(_ctx.$slots, "academyContent", {}, null, _push2, _parent2, _scopeId);
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "mt-4" }, [
                _ctx.$slots.academyContent ? (openBlock(), createBlock("div", { key: 0 }, [
                  renderSlot(_ctx.$slots, "academyContent")
                ])) : createCommentVNode("", true)
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
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/AcademyLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AcademyLayout-CV3k8mo2.mjs.map
