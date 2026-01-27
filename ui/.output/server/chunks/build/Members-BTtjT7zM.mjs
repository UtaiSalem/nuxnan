import { ref, computed, withCtx, unref, createBlock, openBlock, Fragment, renderList, createVNode, createCommentVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
import { i as useApi } from './server.mjs';
import { Icon } from '@iconify/vue';
import _sfc_main$1 from './CourseLayout-9QjNmWeF.mjs';
import { _ as _sfc_main$2 } from './StaggeredFade-D8HMbryk.mjs';
import { M as MemberCard } from './MemberCard-DppXYXxE.mjs';
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
import './main-CdHCodS1.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';
import './inertia-vue3-CWdJjaLG.mjs';

const _sfc_main = {
  __name: "Members",
  __ssrInlineRender: true,
  props: {
    course: Object,
    members: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object
  },
  setup(__props) {
    const api = useApi();
    const props = __props;
    const getInitialGroupTab = () => {
      var _a, _b;
      const lastAccessedGroupId = (_a = props.courseMemberOfAuth) == null ? void 0 : _a.last_accessed_group_tab;
      if (!lastAccessedGroupId) return 0;
      const groups = ((_b = props.groups) == null ? void 0 : _b.data) || [];
      const index = groups.findIndex((group) => group.id === lastAccessedGroupId);
      return index >= 0 ? index : 0;
    };
    const activeGroupTab = ref(getInitialGroupTab());
    const groupsData = computed(() => {
      var _a;
      return ((_a = props.groups) == null ? void 0 : _a.data) || [];
    });
    const unGroupedMembers = computed(() => {
      var _a, _b, _c, _d;
      const members = ((_a = props.members) == null ? void 0 : _a.data) || [];
      const courseOwnerId = (_d = (_c = (_b = props.course) == null ? void 0 : _b.data) == null ? void 0 : _c.user) == null ? void 0 : _d.id;
      return members.filter((member) => !member.group && !member.group_id).filter((member) => {
        var _a2;
        return ((_a2 = member.user) == null ? void 0 : _a2.id) !== courseOwnerId;
      });
    });
    const activeGroupMembers = computed(() => {
      const groups = groupsData.value;
      if (activeGroupTab.value >= groups.length) {
        return unGroupedMembers.value;
      }
      if (groups[activeGroupTab.value]) {
        return groups[activeGroupTab.value].members || [];
      }
      return [];
    });
    const activeGroup = computed(() => {
      const groups = groupsData.value;
      if (activeGroupTab.value >= groups.length) {
        return { id: null, name: "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21", members_count: unGroupedMembers.value.length };
      }
      return groups[activeGroupTab.value] || null;
    });
    async function setActiveGroupTab(tabIndex) {
      if (activeGroupTab.value === tabIndex) return;
      activeGroupTab.value = tabIndex;
      const groups = groupsData.value;
      if (tabIndex < groups.length && props.courseMemberOfAuth) {
        const groupId = groups[tabIndex].id;
        try {
          await api.patch(`/api/courses/${props.course.data.id}/members/update-last-access-group`, {
            last_accessed_group_tab: groupId
          });
        } catch (error) {
          console.error("Error saving group tab:", error);
        }
      }
    }
    const handleUnmemberRequest = (data) => {
      console.log("Unmember request:", data);
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_sfc_main$1, {
        isCourseAdmin: __props.isCourseAdmin,
        courseMemberOfAuth: __props.courseMemberOfAuth,
        course: __props.course,
        activeTab: 4
      }, {
        courseContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mt-4"${_scopeId}><div class="mb-6"${_scopeId}><div class="flex items-center mb-4"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:user-group-solid",
              class: "w-6 h-6 text-indigo-600 mr-2"
            }, null, _parent2, _scopeId));
            _push2(`<h3 class="text-lg font-semibold text-gray-800 dark:text-white"${_scopeId}>\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h3></div><div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2"${_scopeId}><!--[-->`);
            ssrRenderList(groupsData.value, (group, index) => {
              var _a;
              _push2(`<button class="${ssrRenderClass([activeGroupTab.value === index ? "bg-indigo-600 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-t-lg font-medium text-sm transition-all duration-200 flex items-center gap-2"])}"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:users",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(`<span${_scopeId}>${ssrInterpolate(group.name)}</span><span class="${ssrRenderClass([activeGroupTab.value === index ? "bg-white/20 text-white" : "bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300", "px-2 py-0.5 text-xs rounded-full"])}"${_scopeId}>${ssrInterpolate(group.members_count || ((_a = group.members) == null ? void 0 : _a.length) || 0)}</span></button>`);
            });
            _push2(`<!--]-->`);
            if (unGroupedMembers.value.length > 0) {
              _push2(`<button class="${ssrRenderClass([activeGroupTab.value === groupsData.value.length ? "bg-orange-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-t-lg font-medium text-sm transition-all duration-200 flex items-center gap-2"])}"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-minus",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(`<span${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21</span><span class="${ssrRenderClass([activeGroupTab.value === groupsData.value.length ? "bg-white/20 text-white" : "bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300", "px-2 py-0.5 text-xs rounded-full"])}"${_scopeId}>${ssrInterpolate(unGroupedMembers.value.length)}</span></button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
            if (activeGroup.value) {
              _push2(`<div class="mb-4 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-indigo-100 dark:border-gray-600"${_scopeId}><div class="flex items-center justify-between"${_scopeId}><div class="flex items-center"${_scopeId}><div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg mr-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-group-solid",
                class: "w-6 h-6 text-indigo-600 dark:text-indigo-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><h4 class="font-bold text-gray-800 dark:text-white"${_scopeId}>${ssrInterpolate(activeGroup.value.name)}</h4><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(activeGroupMembers.value.length)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </p></div></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(_sfc_main$2, {
              duration: 50,
              tag: "ul",
              class: "flex flex-col w-full space-y-3"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (activeGroupMembers.value.length > 0) {
                    _push3(`<!--[-->`);
                    ssrRenderList(activeGroupMembers.value, (member, index) => {
                      _push3(ssrRenderComponent(MemberCard, {
                        key: member.id,
                        member,
                        dataIndex: index,
                        onRequestUnmemberCourse: handleUnmemberRequest
                      }, null, _parent3, _scopeId2));
                    });
                    _push3(`<!--]-->`);
                  } else {
                    _push3(`<div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "heroicons:users",
                      class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
                    }, null, _parent3, _scopeId2));
                    _push3(`<p class="text-gray-500 dark:text-gray-400 text-lg"${_scopeId2}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49</p></div>`);
                  }
                } else {
                  return [
                    activeGroupMembers.value.length > 0 ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(activeGroupMembers.value, (member, index) => {
                      return openBlock(), createBlock(MemberCard, {
                        key: member.id,
                        member,
                        dataIndex: index,
                        onRequestUnmemberCourse: handleUnmemberRequest
                      }, null, 8, ["member", "dataIndex"]);
                    }), 128)) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "heroicons:users",
                        class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
                      }),
                      createVNode("p", { class: "text-gray-500 dark:text-gray-400 text-lg" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49")
                    ]))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "mt-4" }, [
                createVNode("div", { class: "mb-6" }, [
                  createVNode("div", { class: "flex items-center mb-4" }, [
                    createVNode(unref(Icon), {
                      icon: "heroicons:user-group-solid",
                      class: "w-6 h-6 text-indigo-600 mr-2"
                    }),
                    createVNode("h3", { class: "text-lg font-semibold text-gray-800 dark:text-white" }, "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")
                  ]),
                  createVNode("div", { class: "flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(groupsData.value, (group, index) => {
                      var _a;
                      return openBlock(), createBlock("button", {
                        key: group.id,
                        onClick: ($event) => setActiveGroupTab(index),
                        class: ["px-4 py-2 rounded-t-lg font-medium text-sm transition-all duration-200 flex items-center gap-2", activeGroupTab.value === index ? "bg-indigo-600 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"]
                      }, [
                        createVNode(unref(Icon), {
                          icon: "heroicons:users",
                          class: "w-4 h-4"
                        }),
                        createVNode("span", null, toDisplayString(group.name), 1),
                        createVNode("span", {
                          class: ["px-2 py-0.5 text-xs rounded-full", activeGroupTab.value === index ? "bg-white/20 text-white" : "bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300"]
                        }, toDisplayString(group.members_count || ((_a = group.members) == null ? void 0 : _a.length) || 0), 3)
                      ], 10, ["onClick"]);
                    }), 128)),
                    unGroupedMembers.value.length > 0 ? (openBlock(), createBlock("button", {
                      key: 0,
                      onClick: ($event) => setActiveGroupTab(groupsData.value.length),
                      class: ["px-4 py-2 rounded-t-lg font-medium text-sm transition-all duration-200 flex items-center gap-2", activeGroupTab.value === groupsData.value.length ? "bg-orange-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"]
                    }, [
                      createVNode(unref(Icon), {
                        icon: "heroicons:user-minus",
                        class: "w-4 h-4"
                      }),
                      createVNode("span", null, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21"),
                      createVNode("span", {
                        class: ["px-2 py-0.5 text-xs rounded-full", activeGroupTab.value === groupsData.value.length ? "bg-white/20 text-white" : "bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300"]
                      }, toDisplayString(unGroupedMembers.value.length), 3)
                    ], 10, ["onClick"])) : createCommentVNode("", true)
                  ])
                ]),
                activeGroup.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mb-4 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-indigo-100 dark:border-gray-600"
                }, [
                  createVNode("div", { class: "flex items-center justify-between" }, [
                    createVNode("div", { class: "flex items-center" }, [
                      createVNode("div", { class: "p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg mr-3" }, [
                        createVNode(unref(Icon), {
                          icon: "heroicons:user-group-solid",
                          class: "w-6 h-6 text-indigo-600 dark:text-indigo-400"
                        })
                      ]),
                      createVNode("div", null, [
                        createVNode("h4", { class: "font-bold text-gray-800 dark:text-white" }, toDisplayString(activeGroup.value.name), 1),
                        createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, toDisplayString(activeGroupMembers.value.length) + " \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 ", 1)
                      ])
                    ])
                  ])
                ])) : createCommentVNode("", true),
                createVNode(_sfc_main$2, {
                  duration: 50,
                  tag: "ul",
                  class: "flex flex-col w-full space-y-3"
                }, {
                  default: withCtx(() => [
                    activeGroupMembers.value.length > 0 ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(activeGroupMembers.value, (member, index) => {
                      return openBlock(), createBlock(MemberCard, {
                        key: member.id,
                        member,
                        dataIndex: index,
                        onRequestUnmemberCourse: handleUnmemberRequest
                      }, null, 8, ["member", "dataIndex"]);
                    }), 128)) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "heroicons:users",
                        class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
                      }),
                      createVNode("p", { class: "text-gray-500 dark:text-gray-400 text-lg" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49")
                    ]))
                  ]),
                  _: 1
                })
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/Member/Members.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Members-BTtjT7zM.mjs.map
