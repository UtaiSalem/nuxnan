import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, inject, computed, ref, unref, withCtx, createVNode, createTextVNode, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderStyle, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderList, ssrRenderClass, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { C as CourseFeedsList } from './CourseFeedsList-Ef74fyl-.mjs';
import { p as useRoute, i as useApi, b as useRuntimeConfig } from './server.mjs';
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
import './useAuth-BmyK1-KK.mjs';
import './useAvatar-C8DTKR1c.mjs';
import './PollCard-DKn1EeyZ.mjs';
import 'pinia';
import './useSweetAlert-jHixiibP.mjs';
import 'sweetalert2';
import './useToast-BpzfS75l.mjs';
import './ImageLightbox-D9vQ7Zkj.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "CourseGroupResources",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    groupId: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 min-h-[400px]" }, _attrs))}><div class="mb-6 flex items-center justify-between"><h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:document-duplicate",
        class: "w-6 h-6 text-blue-500"
      }, null, _parent));
      _push(` \u0E44\u0E1F\u0E25\u0E4C\u0E41\u0E25\u0E30\u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 </h2></div>`);
      _push(ssrRenderComponent(CourseFeedsList, {
        "course-id": props.courseId,
        "group-id": props.groupId,
        "is-course-admin": props.isCourseAdmin,
        "initial-tab": "materials"
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/groups/CourseGroupResources.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "CourseGroupAttendance",
  __ssrInlineRender: true,
  props: {
    groups: Array
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded-lg" }, _attrs))}><h3 class="font-bold mb-4">Course Group Attendance</h3><p>Attendance records will be displayed here.</p></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/attendances/CourseGroupAttendance.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[groupId]",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const route = useRoute();
    useApi();
    const config = useRuntimeConfig();
    const groupId = computed(() => Array.isArray(route.params.groupId) ? route.params.groupId[0] : route.params.groupId);
    const group = ref(null);
    const members = ref([]);
    const requesters = ref([]);
    const isLoading = ref(true);
    const isJoining = ref(false);
    const isLeaving = ref(false);
    ref(false);
    const activeTab = ref("about");
    const tabs = computed(() => {
      const baseTabs = [
        { id: "attendance", label: "\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19", icon: "heroicons:calendar-days" },
        { id: "resources", label: "\u0E44\u0E1F\u0E25\u0E4C/\u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23", icon: "heroicons:document-duplicate" },
        { id: "members", label: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", icon: "heroicons:users" },
        { id: "about", label: "\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A", icon: "heroicons:information-circle" }
      ];
      return baseTabs;
    });
    const isMember = computed(() => {
      var _a;
      return !!((_a = group.value) == null ? void 0 : _a.groupMemberOfAuth);
    });
    const getMemberAvatar = (member) => {
      const user = member.user || member.member;
      return (user == null ? void 0 : user.avatar) || "/images/default-avatar.png";
    };
    const getMemberName = (member) => {
      const user = member.user || member.member;
      return (user == null ? void 0 : user.name) || "Unknown User";
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if (unref(isLoading)) {
        _push(`<div class="flex items-center justify-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-8 h-8 text-blue-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (unref(group)) {
        _push(`<div class="space-y-6"><div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg"><div class="h-32 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500" style="${ssrRenderStyle(unref(group).cover ? { backgroundImage: `url(${unref(config).public.apiBase}/storage/images/courses/groups/covers/${unref(group).cover})`, backgroundSize: "cover" } : {})}"></div><div class="p-6"><div class="flex items-start justify-between gap-4"><div class="flex items-start gap-4 flex-1"><div class="w-20 h-20 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg" style="${ssrRenderStyle({ backgroundColor: unref(group).color || "#3B82F6" })}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:user-group",
          class: "w-10 h-10 text-white"
        }, null, _parent));
        _push(`</div><div class="flex-1"><h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">${ssrInterpolate(unref(group).name)}</h1>`);
        if (unref(group).description) {
          _push(`<p class="text-gray-600 dark:text-gray-400 mb-3">${ssrInterpolate(unref(group).description)}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex items-center gap-4 text-sm"><span class="flex items-center gap-1 text-gray-600 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:users",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(group).members_count || 0)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </span>`);
        if (unref(group).max_members) {
          _push(`<span class="flex items-center gap-1 text-gray-600 dark:text-gray-400">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:user-plus",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 ${ssrInterpolate(unref(group).max_members)} \u0E04\u0E19 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div><div class="flex items-center gap-2">`);
        if (unref(isCourseAdmin)) {
          _push(`<!--[-->`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/courses/${unref(course).id}/groups/${unref(groupId)}/edit`,
            class: "p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors",
            title: "\u0E41\u0E01\u0E49\u0E44\u0E02"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:edit-24-filled",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-filled",
                    class: "w-5 h-5"
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`<button class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="\u0E25\u0E1A">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button><!--]-->`);
        } else {
          _push(`<!--[-->`);
          if (!unref(isMember)) {
            _push(`<button${ssrIncludeBooleanAttr(unref(isJoining)) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50">`);
            if (unref(isJoining)) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-plus",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span>${ssrInterpolate(unref(isJoining) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21..." : "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21")}</span></button>`);
          } else {
            _push(`<button${ssrIncludeBooleanAttr(unref(isLeaving)) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50">`);
            if (unref(isLeaving)) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:arrow-left-on-rectangle",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span>${ssrInterpolate(unref(isLeaving) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E2D\u0E01..." : "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21")}</span></button>`);
          }
          _push(`<!--]-->`);
        }
        _push(`</div></div></div></div><div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto"><!--[-->`);
        ssrRenderList(unref(tabs), (tab) => {
          _push(`<button class="${ssrRenderClass([unref(activeTab) === tab.id ? "border-blue-500 text-blue-600 dark:text-blue-400" : "border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300", "flex items-center gap-2 px-4 py-3 border-b-2 font-medium transition-colors whitespace-nowrap"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: tab.icon,
            class: "w-5 h-5"
          }, null, _parent));
          _push(` ${ssrInterpolate(tab.label)}</button>`);
        });
        _push(`<!--]--></div>`);
        if (unref(activeTab) === "attendance") {
          _push(`<div>`);
          if (unref(group)) {
            _push(ssrRenderComponent(_sfc_main$1, {
              groups: [unref(group)]
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "resources") {
          _push(`<div>`);
          _push(ssrRenderComponent(_sfc_main$2, {
            "course-id": unref(course).id,
            "group-id": unref(groupId),
            "is-course-admin": unref(isCourseAdmin)
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "about") {
          _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6"><h2 class="text-xl font-bold mb-4">\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21</h2><p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">${ssrInterpolate(unref(group).description || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22")}</p><div class="mt-6 grid grid-cols-2 gap-4"><div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl"><div class="text-sm text-gray-500">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E23\u0E49\u0E32\u0E07</div><div class="font-medium">${ssrInterpolate(unref(group).created_at ? new Date(unref(group).created_at).toLocaleDateString("th-TH", { year: "numeric", month: "long", day: "numeric" }) : "-")}</div></div><div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl"><div class="text-sm text-gray-500">\u0E2A\u0E16\u0E32\u0E19\u0E30</div><div class="font-medium">${ssrInterpolate(unref(group).privacy === "private" ? "\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27" : "\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30")}</div></div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "members") {
          _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6"><h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:users",
            class: "w-6 h-6"
          }, null, _parent));
          _push(` \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21 (${ssrInterpolate(unref(members).length)}) </h2>`);
          if (unref(requesters).length > 0) {
            _push(`<div class="mb-8"><h3 class="text-lg font-semibold text-orange-500 mb-3 flex items-center gap-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:user-plus",
              class: "w-5 h-5"
            }, null, _parent));
            _push(` \u0E04\u0E33\u0E02\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 (${ssrInterpolate(unref(requesters).length)}) </h3><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
            ssrRenderList(unref(requesters), (req) => {
              _push(`<div class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 rounded-lg"><img${ssrRenderAttr("src", getMemberAvatar(req))}${ssrRenderAttr("alt", getMemberName(req))} class="w-10 h-10 rounded-full object-cover"><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(getMemberName(req))}</p><p class="text-xs text-orange-500">\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p></div><div class="flex items-center gap-1"><button class="p-1 text-green-600 hover:bg-green-100 rounded" title="\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-24-filled",
                class: "w-5 h-5"
              }, null, _parent));
              _push(`</button><button class="p-1 text-red-600 hover:bg-red-100 rounded" title="\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-24-filled",
                class: "w-5 h-5"
              }, null, _parent));
              _push(`</button></div></div>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (unref(members).length > 0) {
            _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
            ssrRenderList(unref(members), (member) => {
              _push(`<div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors"><img${ssrRenderAttr("src", getMemberAvatar(member))}${ssrRenderAttr("alt", getMemberName(member))} class="w-10 h-10 rounded-full object-cover"><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(getMemberName(member))}</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p></div></div>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<div class="text-center py-8">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:user-group",
              class: "w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2"
            }, null, _parent));
            _push(`<p class="text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49</p></div>`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<div class="text-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:exclamation-circle",
          class: "w-16 h-16 text-red-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E25\u0E38\u0E48\u0E21</h3>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${(_a = unref(course)) == null ? void 0 : _a.id}/groups`,
          class: "inline-flex items-center gap-2 text-blue-600 hover:text-blue-700"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:arrow-left",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "heroicons:arrow-left",
                  class: "w-5 h-5"
                }),
                createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/groups/[groupId].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_groupId_-DppC6PlZ.mjs.map
