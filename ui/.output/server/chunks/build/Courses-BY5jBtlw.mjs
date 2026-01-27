import { ref, mergeProps, withCtx, unref, createBlock, createCommentVNode, createVNode, openBlock, toDisplayString, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttrs } from 'vue/server-renderer';
import { r as router } from './inertia-vue3-CWdJjaLG.mjs';
import _sfc_main$2 from './CoursesLayout-Bw6-53KN.mjs';
import { C as CourseCard } from './CourseCard-CBxRij-n.mjs';
import InfiniteLoading from 'v3-infinite-loading';
import { C as CoursesLoading } from './CoursesLoadingSkeleton-D-nJ_WcL.mjs';
import { _ as _export_sfc } from './server.mjs';
import 'unhead/utils';
import '@iconify/vue';
import './main-BqvhuwHD.mjs';
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

const _sfc_main$1 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 bg-white/80 z-50 flex items-center justify-center" }, _attrs))}><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div></div>`);
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/LoadingPage.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const LoadingPage = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main = {
  __name: "Courses",
  __ssrInlineRender: true,
  props: {
    courses: Object
  },
  setup(__props) {
    const props = __props;
    const loading = ref(false);
    const currentPage = ref(1);
    const lastPage = ref(props.courses.meta.last_page);
    const allCourses = ref(props.courses.data);
    const isLoadingPage = ref(false);
    async function fetchMoreCourses() {
      try {
        currentPage.value++;
        if (currentPage.value <= lastPage.value) {
          loading.value = true;
          let coursesResp = await axios.get("/api/courses?page=" + currentPage.value);
          if (coursesResp.data.success) {
            coursesResp.data.courses.forEach((course) => {
              allCourses.value.push(course);
            });
          }
          loading.value = false;
        }
      } catch (error) {
        loading.value = false;
      }
    }
    const handleLoadingPage = (courseId) => {
      isLoadingPage.value = true;
      router.visit(`/courses/${courseId}`);
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$2, mergeProps({ coursePageTitle: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" }, _attrs), {
        coursesMainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (isLoadingPage.value) {
              _push2(ssrRenderComponent(LoadingPage, null, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="p-4 my-4 bg-white shadow-lg section-header rounded-xl"${_scopeId}><div class="section-header-info"${_scopeId}><h2 class="section-title font-prompt"${_scopeId}> \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 ${ssrInterpolate(" " + props.courses.meta.total + " \u0E27\u0E34\u0E0A\u0E32" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")}</h2></div></div><div class="flex flex-wrap justify-between gap-4"${_scopeId}><!--[-->`);
            ssrRenderList(allCourses.value, (course, index) => {
              _push2(`<div class="w-full sm:w-[48%]"${_scopeId}>`);
              _push2(ssrRenderComponent(CourseCard, {
                course,
                onLoadingPage: ($event) => handleLoadingPage(course.id)
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
            if (loading.value) {
              _push2(`<div class="flex flex-wrap justify-between gap-4"${_scopeId}><!--[-->`);
              ssrRenderList(2, (index) => {
                _push2(`<div class="mt-4 w-full sm:w-[48%]"${_scopeId}>`);
                _push2(ssrRenderComponent(CoursesLoading, null, null, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(unref(InfiniteLoading), {
              onDistance: ($event) => 1,
              onInfinite: fetchMoreCourses
            }, null, _parent2, _scopeId));
          } else {
            return [
              isLoadingPage.value ? (openBlock(), createBlock(LoadingPage, { key: 0 })) : createCommentVNode("", true),
              createVNode("div", { class: "p-4 my-4 bg-white shadow-lg section-header rounded-xl" }, [
                createVNode("div", { class: "section-header-info" }, [
                  createVNode("h2", { class: "section-title font-prompt" }, " \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 " + toDisplayString(" " + props.courses.meta.total + " \u0E27\u0E34\u0E0A\u0E32" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"), 1)
                ])
              ]),
              createVNode("div", { class: "flex flex-wrap justify-between gap-4" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(allCourses.value, (course, index) => {
                  return openBlock(), createBlock("div", {
                    key: index,
                    class: "w-full sm:w-[48%]"
                  }, [
                    createVNode(CourseCard, {
                      course,
                      onLoadingPage: ($event) => handleLoadingPage(course.id)
                    }, null, 8, ["course", "onLoadingPage"])
                  ]);
                }), 128))
              ]),
              loading.value ? (openBlock(), createBlock("div", {
                key: 1,
                class: "flex flex-wrap justify-between gap-4"
              }, [
                (openBlock(), createBlock(Fragment, null, renderList(2, (index) => {
                  return createVNode("div", {
                    key: index,
                    class: "mt-4 w-full sm:w-[48%]"
                  }, [
                    createVNode(CoursesLoading)
                  ]);
                }), 64))
              ])) : createCommentVNode("", true),
              createVNode(unref(InfiniteLoading), {
                onDistance: ($event) => 1,
                onInfinite: fetchMoreCourses
              })
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/Courses.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Courses-BY5jBtlw.mjs.map
