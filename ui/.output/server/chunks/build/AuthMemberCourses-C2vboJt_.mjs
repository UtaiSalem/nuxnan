import { ref, mergeProps, withCtx, unref, createBlock, createCommentVNode, createVNode, openBlock, toDisplayString, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { a as usePage, r as router } from './inertia-vue3-CWdJjaLG.mjs';
import _sfc_main$1 from './CoursesLayout-Bw6-53KN.mjs';
import { C as CourseCard } from './CourseCard-CBxRij-n.mjs';
import InfiniteLoading from 'v3-infinite-loading';
import { Icon } from '@iconify/vue';
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
import './main-BqvhuwHD.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';

const _sfc_main = {
  __name: "AuthMemberCourses",
  __ssrInlineRender: true,
  props: {
    courses: Object
  },
  setup(__props) {
    const props = __props;
    const authMemberedCourses = ref(props.courses.data.filter((course) => course.user.id != usePage().props.auth.user.id));
    const isLoading = ref(false);
    const isLoadingPage = ref(false);
    const currentPage = ref(1);
    const lastPage = ref(props.courses.meta.last_page);
    async function fetchMoreCourses() {
      try {
        currentPage.value++;
        if (currentPage.value <= lastPage.value) {
          isLoading.value = true;
          let coursesResp = await axios.get(`/api/courses/users/${usePage().props.auth.user.id}/membered?page=${currentPage.value}`);
          if (coursesResp.data.success) {
            coursesResp.data.courses.forEach((course) => {
              if (course.user.id != usePage().props.auth.user.id) {
                authMemberedCourses.value.push(course);
              }
            });
          }
        }
        isLoading.value = false;
      } catch (error) {
        isLoading.value = false;
      }
    }
    const handleLoadingPage = (courseId) => {
      isLoadingPage.value = true;
      router.visit(`/courses/${courseId}`);
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({ title: "Courses" }, _attrs), {
        coursesMainContent: withCtx((_, _push2, _parent2, _scopeId) => {
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
            _push2(`<div class="p-4 my-4 bg-white shadow-lg section-header rounded-xl"${_scopeId}><div class="section-header-info"${_scopeId}><h2 class="section-title font-prompt"${_scopeId}> \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 ${ssrInterpolate(" " + props.courses.meta.total + " \u0E27\u0E34\u0E0A\u0E32" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")}</h2></div></div><div class="flex flex-wrap justify-between gap-4"${_scopeId}><!--[-->`);
            ssrRenderList(authMemberedCourses.value, (course, index) => {
              _push2(`<div class="w-full sm:w-[48%]"${_scopeId}>`);
              _push2(ssrRenderComponent(CourseCard, {
                course,
                onLoadingPage: ($event) => handleLoadingPage(course.id)
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
            if (isLoading.value) {
              _push2(`<div class="flex flex-wrap justify-between gap-4 mt-4"${_scopeId}><!--[-->`);
              ssrRenderList(2, (index) => {
                _push2(`<div class="w-full sm:w-[48%]"${_scopeId}><div class="w-full overflow-hidden rounded-lg shadow-xl"${_scopeId}><div class="object-cover object-center w-full bg-gray-400/40 h-36"${_scopeId}></div><div class="p-4"${_scopeId}><div class="flex items-center justify-between mb-2"${_scopeId}><div class="flex-shrink-0 w-10 h-10 bg-gray-400 rounded-full"${_scopeId}></div><div class="flex-1 py-2 mx-2 space-y-4"${_scopeId}><div class="w-full h-3 leading-relaxed bg-gray-400 rounded animate-pulse"${_scopeId}></div><div class="w-5/6 h-3 leading-relaxed bg-gray-400 rounded animate-pulse"${_scopeId}></div></div></div><p class="w-full h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse"${_scopeId}></p><p class="w-2/3 h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse"${_scopeId}></p><p class="w-1/2 h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse"${_scopeId}></p></div></div></div>`);
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
              createVNode("div", { class: "p-4 my-4 bg-white shadow-lg section-header rounded-xl" }, [
                createVNode("div", { class: "section-header-info" }, [
                  createVNode("h2", { class: "section-title font-prompt" }, " \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E09\u0E31\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 " + toDisplayString(" " + props.courses.meta.total + " \u0E27\u0E34\u0E0A\u0E32" || "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"), 1)
                ])
              ]),
              createVNode("div", { class: "flex flex-wrap justify-between gap-4" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(authMemberedCourses.value, (course, index) => {
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
              isLoading.value ? (openBlock(), createBlock("div", {
                key: 1,
                class: "flex flex-wrap justify-between gap-4 mt-4"
              }, [
                (openBlock(), createBlock(Fragment, null, renderList(2, (index) => {
                  return createVNode("div", {
                    key: index,
                    class: "w-full sm:w-[48%]"
                  }, [
                    createVNode("div", { class: "w-full overflow-hidden rounded-lg shadow-xl" }, [
                      createVNode("div", { class: "object-cover object-center w-full bg-gray-400/40 h-36" }),
                      createVNode("div", { class: "p-4" }, [
                        createVNode("div", { class: "flex items-center justify-between mb-2" }, [
                          createVNode("div", { class: "flex-shrink-0 w-10 h-10 bg-gray-400 rounded-full" }),
                          createVNode("div", { class: "flex-1 py-2 mx-2 space-y-4" }, [
                            createVNode("div", { class: "w-full h-3 leading-relaxed bg-gray-400 rounded animate-pulse" }),
                            createVNode("div", { class: "w-5/6 h-3 leading-relaxed bg-gray-400 rounded animate-pulse" })
                          ])
                        ]),
                        createVNode("p", { class: "w-full h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse" }),
                        createVNode("p", { class: "w-2/3 h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse" }),
                        createVNode("p", { class: "w-1/2 h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse" })
                      ])
                    ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/AuthMemberCourses.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AuthMemberCourses-C2vboJt_.mjs.map
