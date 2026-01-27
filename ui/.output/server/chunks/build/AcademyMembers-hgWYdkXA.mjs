import { ref, mergeProps, withCtx, unref, createVNode, createBlock, createTextVNode, toDisplayString, openBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
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
  __name: "AcademyMembers",
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
    const members = ref([]);
    const isLoading = ref(true);
    const tempLogo = ref(props.academy.data.logo ? "/storage/images/academies/logos/" + props.academy.data.logo : "/storage/images/academies/logos/default_logo.png");
    const tempCover = ref(props.academy.data.cover ? "/storage/images/academies/covers/" + props.academy.data.cover : "/storage/images/academies/covers/default_cover.png");
    consttempHeader = ref(props.academy.data.name);
    const tempSubheader = ref(props.academy.data.slogan);
    async function fetchMembers() {
      isLoading.value = true;
      try {
        const response = await axios.get(`/api/academies/${props.academy.data.id}/members`);
        if (response.data.success) {
          if (response.data.members && response.data.members.data) {
            members.value = response.data.members.data;
          } else {
            members.value = response.data.members || [];
          }
        }
      } catch (error) {
        console.error("Error fetching members:", error);
      } finally {
        isLoading.value = false;
      }
    }
    const getMemberName = (member) => {
      if (member.user) return member.user.name;
      if (member.student) return `${member.student.first_name_th} ${member.student.last_name_th}`;
      return "Unknown Member";
    };
    const getMemberAvatar = (member) => {
      if (member.user && member.user.profile_photo_url) return member.user.profile_photo_url;
      return `https://ui-avatars.com/api/?name=${encodeURIComponent(getMemberName(member))}&color=7F9CF5&background=EBF4FF`;
    };
    const getMemberCode = (member) => {
      if (member.student) return member.student.student_id;
      if (member.member_code) return member.member_code;
      return "-";
    };
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
          fetchMembers();
          if (memberResp.data.isMember) {
            Swal.fire("\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19", "\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27", "success");
          } else {
            Swal.fire("\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19", "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27", "success");
          }
        }
      } catch (error) {
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({
        title: "Academy Members",
        appUrl: props.app_url
      }, _attrs), {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class=""${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$1, {
              coverImage: tempCover.value,
              logoImage: tempLogo.value,
              coverHeader: _ctx.tempHeader,
              "onUpdate:coverHeader": ($event) => _ctx.tempHeader = $event,
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
              academy: props.academy,
              activeTab: 3
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", { class: "" }, [
                createVNode(_sfc_main$1, {
                  coverImage: tempCover.value,
                  logoImage: tempLogo.value,
                  coverHeader: _ctx.tempHeader,
                  "onUpdate:coverHeader": ($event) => _ctx.tempHeader = $event,
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
                academy: props.academy,
                activeTab: 3
              }, null, 8, ["academy"])
            ];
          }
        }),
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="section-header my-4 p-4 bg-white rounded-xl shadow-lg flex justify-between items-center"${_scopeId}><div class="section-header-info"${_scopeId}><h2 class="section-title font-prompt text-xl font-bold"${_scopeId}>\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 <span class="text-indigo-600 ml-2"${_scopeId}>${ssrInterpolate(members.value.length)} \u0E04\u0E19</span></h2></div></div>`);
            if (isLoading.value) {
              _push2(`<div class="flex justify-center p-8"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:3-dots-scale",
                class: "w-10 h-10 text-indigo-500"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (members.value.length > 0) {
              _push2(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"${_scopeId}><!--[-->`);
              ssrRenderList(members.value, (member) => {
                _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center space-x-4 hover:shadow-md transition-shadow"${_scopeId}><img${ssrRenderAttr("src", getMemberAvatar(member))} alt="Avatar" class="w-12 h-12 rounded-full object-cover ring-2 ring-indigo-100"${_scopeId}><div class="overflow-hidden"${_scopeId}><h3 class="font-bold text-gray-800 dark:text-gray-200 truncate"${_scopeId}>${ssrInterpolate(getMemberName(member))}</h3><p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:identification",
                  class: "w-3 h-3"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(getMemberCode(member))}</p><span class="inline-block mt-1 px-2 py-0.5 text-[10px] rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300"${_scopeId}>${ssrInterpolate(member.role || "Student")}</span></div></div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl shadow text-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:users",
                class: "w-16 h-16 text-gray-300 mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h3 class="text-lg font-medium text-gray-900 dark:text-gray-100"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</h3><p class="text-gray-500 text-sm mt-1"${_scopeId}>\u0E04\u0E25\u0E34\u0E01 &quot;\u0E40\u0E0A\u0E34\u0E0D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E0A\u0E34\u0E0D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 (\u0E16\u0E49\u0E32\u0E21\u0E35\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C)</p></div>`);
            }
          } else {
            return [
              createVNode("div", { class: "section-header my-4 p-4 bg-white rounded-xl shadow-lg flex justify-between items-center" }, [
                createVNode("div", { class: "section-header-info" }, [
                  createVNode("h2", { class: "section-title font-prompt text-xl font-bold" }, [
                    createTextVNode("\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 "),
                    createVNode("span", { class: "text-indigo-600 ml-2" }, toDisplayString(members.value.length) + " \u0E04\u0E19", 1)
                  ])
                ])
              ]),
              isLoading.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "flex justify-center p-8"
              }, [
                createVNode(unref(Icon), {
                  icon: "svg-spinners:3-dots-scale",
                  class: "w-10 h-10 text-indigo-500"
                })
              ])) : members.value.length > 0 ? (openBlock(), createBlock("div", {
                key: 1,
                class: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
              }, [
                (openBlock(true), createBlock(Fragment, null, renderList(members.value, (member) => {
                  return openBlock(), createBlock("div", {
                    key: member.id,
                    class: "bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center space-x-4 hover:shadow-md transition-shadow"
                  }, [
                    createVNode("img", {
                      src: getMemberAvatar(member),
                      alt: "Avatar",
                      class: "w-12 h-12 rounded-full object-cover ring-2 ring-indigo-100"
                    }, null, 8, ["src"]),
                    createVNode("div", { class: "overflow-hidden" }, [
                      createVNode("h3", { class: "font-bold text-gray-800 dark:text-gray-200 truncate" }, toDisplayString(getMemberName(member)), 1),
                      createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1" }, [
                        createVNode(unref(Icon), {
                          icon: "heroicons:identification",
                          class: "w-3 h-3"
                        }),
                        createTextVNode(" " + toDisplayString(getMemberCode(member)), 1)
                      ]),
                      createVNode("span", { class: "inline-block mt-1 px-2 py-0.5 text-[10px] rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300" }, toDisplayString(member.role || "Student"), 1)
                    ])
                  ]);
                }), 128))
              ])) : (openBlock(), createBlock("div", {
                key: 2,
                class: "flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl shadow text-center"
              }, [
                createVNode(unref(Icon), {
                  icon: "heroicons:users",
                  class: "w-16 h-16 text-gray-300 mb-4"
                }),
                createVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-gray-100" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01"),
                createVNode("p", { class: "text-gray-500 text-sm mt-1" }, '\u0E04\u0E25\u0E34\u0E01 "\u0E40\u0E0A\u0E34\u0E0D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01" \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E0A\u0E34\u0E0D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 (\u0E16\u0E49\u0E32\u0E21\u0E35\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C)')
              ]))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/AcademyMembers.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AcademyMembers-hgWYdkXA.mjs.map
