import { mergeProps, withCtx, createVNode, createBlock, createCommentVNode, renderSlot, openBlock, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderSlot } from 'vue/server-renderer';
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

const _sfc_main = {
  __name: "CourseLayout",
  __ssrInlineRender: true,
  props: {
    course: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    activeTab: Number
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(MainLayout, mergeProps({
        title: __props.course ? __props.course.name : "Course"
      }, _attrs), {
        header: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-white shadow"${_scopeId}><div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"${_scopeId}><h2 class="font-semibold text-xl text-gray-800 leading-tight"${_scopeId}>${ssrInterpolate(__props.course ? __props.course.name : "Course")}</h2></div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-white shadow" }, [
                createVNode("div", { class: "max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" }, [
                  createVNode("h2", { class: "font-semibold text-xl text-gray-800 leading-tight" }, toDisplayString(__props.course ? __props.course.name : "Course"), 1)
                ])
              ])
            ];
          }
        }),
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="py-12"${_scopeId}><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"${_scopeId}>`);
            if (__props.course) {
              _push2(`<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6"${_scopeId}><div class="flex items-center"${_scopeId}><div class="ml-4"${_scopeId}><div class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(__props.course.name)}</div><div class="text-gray-500"${_scopeId}>${ssrInterpolate(__props.course.code)}</div></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            ssrRenderSlot(_ctx.$slots, "courseContent", {}, null, _push2, _parent2, _scopeId);
            _push2(`</div></div>`);
          } else {
            return [
              createVNode("div", { class: "py-12" }, [
                createVNode("div", { class: "max-w-7xl mx-auto sm:px-6 lg:px-8" }, [
                  __props.course ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6"
                  }, [
                    createVNode("div", { class: "flex items-center" }, [
                      createVNode("div", { class: "ml-4" }, [
                        createVNode("div", { class: "text-2xl font-bold" }, toDisplayString(__props.course.name), 1),
                        createVNode("div", { class: "text-gray-500" }, toDisplayString(__props.course.code), 1)
                      ])
                    ])
                  ])) : createCommentVNode("", true),
                  renderSlot(_ctx.$slots, "courseContent")
                ])
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/CourseLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CourseLayout-DYixP6h3.mjs.map
