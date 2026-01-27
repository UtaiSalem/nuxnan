import { ref, withCtx, createVNode, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import _sfc_main$2 from './CourseLayout-9QjNmWeF.mjs';
import { _ as _sfc_main$3 } from './StaggeredFade-D8HMbryk.mjs';
import './main-CdHCodS1.mjs';
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
  __name: "NonGroupedMemberList",
  __ssrInlineRender: true,
  props: {
    members: Array,
    groups: Array
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded mt-4" }, _attrs))}><h4 class="font-bold mb-2">Non-Grouped Members</h4><ul><!--[-->`);
      ssrRenderList(__props.members, (member) => {
        _push(`<li class="border-b py-2">${ssrInterpolate(member.name)}</li>`);
      });
      _push(`<!--]--></ul></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/members/NonGroupedMemberList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "CourseMemberRequesters",
  __ssrInlineRender: true,
  props: {
    isCourseAdmin: Boolean,
    course: Object,
    lessons: Object,
    groups: Object,
    members: Object,
    courseMemberOfAuth: Object
  },
  setup(__props) {
    const props = __props;
    ref(true);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_sfc_main$2, {
        isCourseAdmin: __props.isCourseAdmin,
        course: __props.course,
        lessons: __props.lessons,
        groups: __props.groups,
        courseMemberOfAuth: __props.courseMemberOfAuth,
        activeTab: 6
      }, {
        courseContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div${_scopeId}><div class="mt-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$3, {
              duration: 50,
              tag: "ul",
              class: "flex flex-col w-full"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(_sfc_main$1, {
                    members: props.members.data,
                    groups: props.groups.data
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(_sfc_main$1, {
                      members: props.members.data,
                      groups: props.groups.data
                    }, null, 8, ["members", "groups"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", null, [
                createVNode("div", { class: "mt-4" }, [
                  createVNode(_sfc_main$3, {
                    duration: 50,
                    tag: "ul",
                    class: "flex flex-col w-full"
                  }, {
                    default: withCtx(() => [
                      createVNode(_sfc_main$1, {
                        members: props.members.data,
                        groups: props.groups.data
                      }, null, 8, ["members", "groups"])
                    ]),
                    _: 1
                  })
                ])
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/CourseMemberRequesters.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CourseMemberRequesters-BxB1T2u4.mjs.map
