import { ref, resolveComponent, withCtx, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString, unref, withModifiers, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderStyle } from 'vue/server-renderer';
import { L as Link } from './inertia-vue3-CWdJjaLG.mjs';
import { Icon } from '@iconify/vue';
import MainLayout from './main-CdHCodS1.mjs';
import { _ as _sfc_main$1$1, Q as QuestionsListViewer } from './LessonQuizSection-BLUECQic.mjs';
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
import './RichTextViewer-UnGuFLT8.mjs';
import './RichTextEditor-DEEazQRP.mjs';
import './useAvatar-C8DTKR1c.mjs';
import './ImageLightbox-D9vQ7Zkj.mjs';

const _sfc_main$3 = {
  __name: "LessonCoverProfile",
  __ssrInlineRender: true,
  props: {
    logo: String,
    cover: String,
    name: String,
    code: String
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-gray-200 h-64 w-full relative" }, _attrs))}><div class="h-full w-full bg-cover bg-center" style="${ssrRenderStyle({ backgroundImage: `url(${__props.cover})` })}"></div><div class="absolute -bottom-12 left-4 flex items-end"><img${ssrRenderAttr("src", __props.logo)} class="w-24 h-24 rounded-full border-4 border-white shadow"><div class="ml-4 mb-4"><h1 class="text-2xl font-bold bg-white/80 px-2 rounded">${ssrInterpolate(__props.name)}</h1><p class="text-gray-700 bg-white/80 px-2 rounded">${ssrInterpolate(__props.code)}</p></div></div></div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonCoverProfile.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "CreateNewAssignmentCard",
  __ssrInlineRender: true,
  props: {
    assignmentableType: String,
    assignmentableId: Number,
    assignmentNameTh: String,
    assignmentApiRoute: String
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-4 border rounded shadow bg-white mt-4" }, _attrs))}><h3 class="font-bold">Create New Assignment</h3><p class="text-gray-500">Assignment creation form placeholder.</p></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/CreateNewAssignmentCard.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "LessonImagesViewer",
  __ssrInlineRender: true,
  props: {
    model_id: Number,
    images: Array,
    edit: Boolean
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      if ((_a = __props.images) == null ? void 0 : _a.length) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "grid grid-cols-2 gap-2 mt-4" }, _attrs))}><!--[-->`);
        ssrRenderList(__props.images, (img) => {
          _push(`<img${ssrRenderAttr("src", img.full_url)} class="w-full h-auto rounded">`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonImagesViewer.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Lesson",
  __ssrInlineRender: true,
  props: {
    isCourseAdmin: Boolean,
    academy: Object,
    course: Object,
    lesson: Object,
    topics: Object,
    assignments: Object,
    questions: Object,
    imagePath: String
  },
  setup(__props) {
    const props = __props;
    const activeTab = ref(2);
    ref(false);
    const tempLogo = ref(props.course.data.user.avatar);
    const tempCover = ref("/storage/images/courses/covers/default_cover.jpg");
    const tempHeader = ref(props.course.data.name);
    const tempSubheader = ref(props.course.data.code);
    const setActiveTab = (tab) => activeTab.value = tab;
    function onAddNewAssignmentHandler(newAsm) {
      props.assignments.data.push(newAsm);
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_vue_plyr = resolveComponent("vue-plyr");
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(MainLayout, {
        title: props.lesson.data.title
      }, {
        coverProfileCard: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$3, {
              logo: tempLogo.value,
              cover: tempCover.value,
              name: tempHeader.value,
              code: tempSubheader.value
            }, null, _parent2, _scopeId));
            _push2(`<div class="bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4"${_scopeId}><div class="flex flex-row justify-around"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: "/courses/" + props.course.data.id,
              class: ["tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": activeTab.value === 1 }]
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex flex-col items-center py-2 justify-center text-slate-600/80"${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "icon-park-outline:view-grid-detail",
                    class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 1 }]
                  }, null, _parent3, _scopeId2));
                  _push3(`<span class="${ssrRenderClass([{ "text-cyan-500": activeTab.value === 1 }, "hidden sm:block"])}"${_scopeId2}>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-600/80" }, [
                      createVNode(unref(Icon), {
                        icon: "icon-park-outline:view-grid-detail",
                        class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 1 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: ["hidden sm:block", { "text-cyan-500": activeTab.value === 1 }]
                      }, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32", 2)
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<button type="button" class="${ssrRenderClass([{ "border-b-4 border-cyan-500": activeTab.value === 2 }, "tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"])}"${_scopeId}><div class="flex flex-col items-center py-2 justify-center text-slate-600/80"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 2 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500": activeTab.value === 2 }, "hidden sm:block"])}"${_scopeId}>\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32</span></div></button><button type="button" class="${ssrRenderClass([{ "border-b-4 border-cyan-500": activeTab.value === 3 }, "tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"])}"${_scopeId}><div class="flex flex-col items-center py-2 justify-center text-slate-600/80"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 3 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500": activeTab.value === 3 }, "hidden sm:block"])}"${_scopeId}>\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14</span></div></button><button type="button" class="${ssrRenderClass([{ "border-b-4 border-cyan-500": activeTab.value === 4 }, "tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center"])}"${_scopeId}><div class="flex flex-col items-center py-2 justify-center text-slate-600/80"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "lucide:layout-list",
              class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 4 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500": activeTab.value === 4 }, "hidden sm:block"])}"${_scopeId}>\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</span></div></button></div></div>`);
          } else {
            return [
              createVNode(_sfc_main$3, {
                logo: tempLogo.value,
                cover: tempCover.value,
                name: tempHeader.value,
                code: tempSubheader.value
              }, null, 8, ["logo", "cover", "name", "code"]),
              createVNode("div", { class: "bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4" }, [
                createVNode("div", { class: "flex flex-row justify-around" }, [
                  createVNode(unref(Link), {
                    href: "/courses/" + props.course.data.id,
                    class: ["tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": activeTab.value === 1 }]
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-600/80" }, [
                        createVNode(unref(Icon), {
                          icon: "icon-park-outline:view-grid-detail",
                          class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 1 }]
                        }, null, 8, ["class"]),
                        createVNode("span", {
                          class: ["hidden sm:block", { "text-cyan-500": activeTab.value === 1 }]
                        }, "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32", 2)
                      ])
                    ]),
                    _: 1
                  }, 8, ["href", "class"]),
                  createVNode("button", {
                    type: "button",
                    onClick: withModifiers(($event) => setActiveTab(2), ["prevent"]),
                    class: ["tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": activeTab.value === 2 }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-600/80" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 2 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: ["hidden sm:block", { "text-cyan-500": activeTab.value === 2 }]
                      }, "\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32", 2)
                    ])
                  ], 10, ["onClick"]),
                  createVNode("button", {
                    type: "button",
                    onClick: withModifiers(($event) => setActiveTab(3), ["prevent"]),
                    class: ["tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": activeTab.value === 3 }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-600/80" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 3 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: ["hidden sm:block", { "text-cyan-500": activeTab.value === 3 }]
                      }, "\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14", 2)
                    ])
                  ], 10, ["onClick"]),
                  createVNode("button", {
                    type: "button",
                    onClick: withModifiers(($event) => setActiveTab(4), ["prevent"]),
                    class: ["tab-item border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center", { "border-b-4 border-cyan-500": activeTab.value === 4 }]
                  }, [
                    createVNode("div", { class: "flex flex-col items-center py-2 justify-center text-slate-600/80" }, [
                      createVNode(unref(Icon), {
                        icon: "lucide:layout-list",
                        class: ["w-8 h-8", { "text-cyan-500": activeTab.value === 4 }]
                      }, null, 8, ["class"]),
                      createVNode("span", {
                        class: ["hidden sm:block", { "text-cyan-500": activeTab.value === 4 }]
                      }, "\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A", 2)
                    ])
                  ], 10, ["onClick"])
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
            _push2(`<div class="w-full space-y-4"${_scopeId}>`);
            if (activeTab.value === 2) {
              _push2(`<div class="plearnd-card my-4"${_scopeId}><div class="flex flex-col"${_scopeId}>`);
              if (__props.lesson.data.youtube_url) {
                _push2(`<div${_scopeId}>`);
                _push2(ssrRenderComponent(_component_vue_plyr, null, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<div data-plyr-provider="youtube"${ssrRenderAttr("data-plyr-embed-id", __props.lesson.data.youtube_url)}${_scopeId2}></div>`);
                    } else {
                      return [
                        createVNode("div", {
                          "data-plyr-provider": "youtube",
                          "data-plyr-embed-id": __props.lesson.data.youtube_url
                        }, null, 8, ["data-plyr-embed-id"])
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="my-4 text-md font-bold"${_scopeId}>${ssrInterpolate(__props.lesson.data.title)}</div><div class="text-md font-bold"${_scopeId}>\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</div><div class="my-4 text-sm font-semibold text-gray-600"${_scopeId}>${ssrInterpolate(__props.lesson.data.description)}</div><div class="text-sm font-semibold"${_scopeId}>\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32</div><div class="my-2 text-sm font-normal"${_scopeId}>${ssrInterpolate(__props.lesson.data.content)}</div>`);
              _push2(ssrRenderComponent(_sfc_main$1, {
                model_id: props.lesson.data.id,
                images: props.lesson.data.images,
                edit: __props.isCourseAdmin
              }, null, _parent2, _scopeId));
              _push2(`</div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (activeTab.value === 3) {
              _push2(`<div class="mt-4 space-y-4"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$1$1, {
                assignments: props.assignments.data,
                lessonId: props.lesson.data.id
              }, null, _parent2, _scopeId));
              if (_ctx.$page.props.isCourseAdmin) {
                _push2(ssrRenderComponent(_sfc_main$2, {
                  assignmentableType: "lessons",
                  assignmentableId: props.lesson.data.id,
                  assignmentNameTh: "\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19",
                  assignmentApiRoute: `/lessons/${props.lesson.data.id}`,
                  onAddNewAssignment: (newAsm) => {
                    onAddNewAssignmentHandler(newAsm);
                  }
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            if (activeTab.value === 4) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              _push2(ssrRenderComponent(QuestionsListViewer, {
                questions: props.questions.data,
                lessonId: props.lesson.data.id
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "w-full space-y-4" }, [
                activeTab.value === 2 ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "plearnd-card my-4"
                }, [
                  createVNode("div", { class: "flex flex-col" }, [
                    __props.lesson.data.youtube_url ? (openBlock(), createBlock("div", { key: 0 }, [
                      createVNode(_component_vue_plyr, null, {
                        default: withCtx(() => [
                          createVNode("div", {
                            "data-plyr-provider": "youtube",
                            "data-plyr-embed-id": __props.lesson.data.youtube_url
                          }, null, 8, ["data-plyr-embed-id"])
                        ]),
                        _: 1
                      })
                    ])) : createCommentVNode("", true),
                    createVNode("div", { class: "my-4 text-md font-bold" }, toDisplayString(__props.lesson.data.title), 1),
                    createVNode("div", { class: "text-md font-bold" }, "\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19"),
                    createVNode("div", { class: "my-4 text-sm font-semibold text-gray-600" }, toDisplayString(__props.lesson.data.description), 1),
                    createVNode("div", { class: "text-sm font-semibold" }, "\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32"),
                    createVNode("div", { class: "my-2 text-sm font-normal" }, toDisplayString(__props.lesson.data.content), 1),
                    createVNode(_sfc_main$1, {
                      model_id: props.lesson.data.id,
                      images: props.lesson.data.images,
                      edit: __props.isCourseAdmin
                    }, null, 8, ["model_id", "images", "edit"])
                  ])
                ])) : createCommentVNode("", true),
                activeTab.value === 3 ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "mt-4 space-y-4"
                }, [
                  createVNode(_sfc_main$1$1, {
                    assignments: props.assignments.data,
                    lessonId: props.lesson.data.id
                  }, null, 8, ["assignments", "lessonId"]),
                  _ctx.$page.props.isCourseAdmin ? (openBlock(), createBlock(_sfc_main$2, {
                    key: 0,
                    assignmentableType: "lessons",
                    assignmentableId: props.lesson.data.id,
                    assignmentNameTh: "\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19",
                    assignmentApiRoute: `/lessons/${props.lesson.data.id}`,
                    onAddNewAssignment: (newAsm) => {
                      onAddNewAssignmentHandler(newAsm);
                    }
                  }, null, 8, ["assignmentableId", "assignmentApiRoute", "onAddNewAssignment"])) : createCommentVNode("", true)
                ])) : createCommentVNode("", true),
                activeTab.value === 4 ? (openBlock(), createBlock("div", {
                  key: 2,
                  class: "mt-4"
                }, [
                  createVNode(QuestionsListViewer, {
                    questions: props.questions.data,
                    lessonId: props.lesson.data.id
                  }, null, 8, ["questions", "lessonId"])
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
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Lesson/Lesson.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Lesson-zzUAHn8K.mjs.map
