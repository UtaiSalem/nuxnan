import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, inject, computed, mergeProps, unref, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { C as CourseFeedsList } from './CourseFeedsList-Ef74fyl-.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import { f as useHead } from './server.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "feeds",
  __ssrInlineRender: true,
  props: {
    course: {},
    academy: {},
    isCourseAdmin: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const { getAvatarUrl } = useAvatar();
    const injectedCourse = inject("course");
    const injectedAcademy = inject("academy");
    const injectedIsCourseAdmin = inject("isCourseAdmin");
    inject("courseMemberOfAuth");
    const course = computed(() => props.course || (injectedCourse == null ? void 0 : injectedCourse.value));
    computed(() => props.academy || (injectedAcademy == null ? void 0 : injectedAcademy.value));
    const isCourseAdmin = computed(() => props.isCourseAdmin || (injectedIsCourseAdmin == null ? void 0 : injectedIsCourseAdmin.value));
    const teacherAvatar = computed(() => {
      var _a;
      return ((_a = course.value) == null ? void 0 : _a.user) ? getAvatarUrl(course.value.user) : null;
    });
    const courseStats = computed(() => {
      if (!course.value) return null;
      return {
        members: course.value.members_count || course.value.course_members_count || 0,
        posts: course.value.posts_count || 0,
        materials: course.value.materials_count || 0,
        assignments: course.value.assignments_count || 0
      };
    });
    useHead({
      title: computed(() => {
        var _a;
        return ((_a = course.value) == null ? void 0 : _a.name) ? `\u0E1F\u0E35\u0E14 - ${course.value.name}` : "\u0E1F\u0E35\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32";
      })
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "grid grid-cols-1 lg:grid-cols-12 gap-6" }, _attrs))}><div class="hidden lg:block lg:col-span-3 space-y-4"><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:info-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 </h3><div class="space-y-3"><div class="flex items-center justify-between"><span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:people-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </span><span class="text-sm font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_a = unref(courseStats)) == null ? void 0 : _a.members) || 0)} \u0E04\u0E19 </span></div><div class="flex items-center justify-between"><span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chat-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E42\u0E1E\u0E2A\u0E15\u0E4C </span><span class="text-sm font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_b = unref(courseStats)) == null ? void 0 : _b.posts) || 0)} \u0E42\u0E1E\u0E2A\u0E15\u0E4C </span></div><div class="flex items-center justify-between"><span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:document-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23 </span><span class="text-sm font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_c = unref(courseStats)) == null ? void 0 : _c.materials) || 0)} \u0E44\u0E1F\u0E25\u0E4C </span></div><div class="flex items-center justify-between"><span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:task-list-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E20\u0E32\u0E23\u0E01\u0E34\u0E08 </span><span class="text-sm font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_d = unref(courseStats)) == null ? void 0 : _d.assignments) || 0)} \u0E07\u0E32\u0E19 </span></div></div></div><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:link-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E25\u0E34\u0E07\u0E01\u0E4C\u0E14\u0E48\u0E27\u0E19 </h3><div class="space-y-1">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${(_e = unref(course)) == null ? void 0 : _e.id}/materials`,
        class: "flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:document-24-regular",
              class: "w-4 h-4 text-blue-500"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:document-24-regular",
                class: "w-4 h-4 text-blue-500"
              }),
              createTextVNode(" \u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${(_f = unref(course)) == null ? void 0 : _f.id}/assignments`,
        class: "flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:task-list-24-regular",
              class: "w-4 h-4 text-green-500"
            }, null, _parent2, _scopeId));
            _push2(` \u0E20\u0E32\u0E23\u0E01\u0E34\u0E08 / \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:task-list-24-regular",
                class: "w-4 h-4 text-green-500"
              }),
              createTextVNode(" \u0E20\u0E32\u0E23\u0E01\u0E34\u0E08 / \u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${(_g = unref(course)) == null ? void 0 : _g.id}/members`,
        class: "flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-24-regular",
              class: "w-4 h-4 text-purple-500"
            }, null, _parent2, _scopeId));
            _push2(` \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:people-24-regular",
                class: "w-4 h-4 text-purple-500"
              }),
              createTextVNode(" \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${(_h = unref(course)) == null ? void 0 : _h.id}/groups`,
        class: "flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-team-24-regular",
              class: "w-4 h-4 text-orange-500"
            }, null, _parent2, _scopeId));
            _push2(` \u0E01\u0E25\u0E38\u0E48\u0E21\u0E22\u0E48\u0E2D\u0E22 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:people-team-24-regular",
                class: "w-4 h-4 text-orange-500"
              }),
              createTextVNode(" \u0E01\u0E25\u0E38\u0E48\u0E21\u0E22\u0E48\u0E2D\u0E22 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
      if (unref(isCourseAdmin)) {
        _push(`<div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-sm text-white"><h3 class="text-sm font-semibold mb-3 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:shield-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 </h3><div class="space-y-1">`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${(_i = unref(course)) == null ? void 0 : _i.id}/settings`,
          class: "flex items-center gap-2 px-3 py-2 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:settings-24-regular",
                  class: "w-4 h-4"
                }),
                createTextVNode(" \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${(_j = unref(course)) == null ? void 0 : _j.id}/analytics`,
          class: "flex items-center gap-2 px-3 py-2 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:data-trending-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E41\u0E25\u0E30\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:data-trending-24-regular",
                  class: "w-4 h-4"
                }),
                createTextVNode(" \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E41\u0E25\u0E30\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="lg:col-span-6 space-y-4">`);
      if ((_k = unref(course)) == null ? void 0 : _k.id) {
        _push(ssrRenderComponent(CourseFeedsList, {
          "course-id": unref(course).id,
          "is-course-admin": unref(isCourseAdmin)
        }, null, _parent));
      } else {
        _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 animate-spin mx-auto text-blue-500 mb-4"
        }, null, _parent));
        _push(`<p class="text-gray-500 dark:text-gray-400">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32...</p></div>`);
      }
      _push(`</div><div class="hidden lg:block lg:col-span-3 space-y-4">`);
      if ((_l = unref(course)) == null ? void 0 : _l.user) {
        _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-24-regular",
          class: "w-4 h-4 text-blue-500"
        }, null, _parent));
        _push(` \u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19 </h3><div class="flex items-center gap-3"><img${ssrRenderAttr("src", unref(teacherAvatar))}${ssrRenderAttr("alt", unref(course).user.name)} class="w-12 h-12 rounded-full object-cover border-2 border-blue-500"><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(unref(course).user.name)}</p><p class="text-xs text-gray-500 dark:text-gray-400">\u0E1C\u0E39\u0E49\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:flash-24-regular",
        class: "w-4 h-4 text-yellow-500"
      }, null, _parent));
      _push(` \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 </h3><div class="space-y-3"><div class="flex items-start gap-3 text-sm"><div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:document-add-24-regular",
        class: "w-4 h-4 text-blue-500"
      }, null, _parent));
      _push(`</div><div class="flex-1 min-w-0"><p class="text-gray-600 dark:text-gray-300 text-xs">\u0E21\u0E35\u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23\u0E43\u0E2B\u0E21\u0E48\u0E16\u0E39\u0E01\u0E40\u0E1E\u0E34\u0E48\u0E21</p><p class="text-xs text-gray-400">2 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27</p></div></div><div class="flex items-start gap-3 text-sm"><div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:person-add-24-regular",
        class: "w-4 h-4 text-green-500"
      }, null, _parent));
      _push(`</div><div class="flex-1 min-w-0"><p class="text-gray-600 dark:text-gray-300 text-xs">\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E2B\u0E21\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21</p><p class="text-xs text-gray-400">5 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27</p></div></div><div class="flex items-start gap-3 text-sm"><div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chat-24-regular",
        class: "w-4 h-4 text-purple-500"
      }, null, _parent));
      _push(`</div><div class="flex-1 min-w-0"><p class="text-gray-600 dark:text-gray-300 text-xs">\u0E21\u0E35\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E2B\u0E21\u0E48\u0E43\u0E19\u0E1F\u0E35\u0E14</p><p class="text-xs text-gray-400">\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E27\u0E32\u0E19</p></div></div></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${(_m = unref(course)) == null ? void 0 : _m.id}/activities`,
        class: "mt-3 block text-center text-xs text-blue-500 hover:text-blue-600"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:megaphone-24-regular",
        class: "w-4 h-4 text-red-500"
      }, null, _parent));
      _push(` \u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28\u0E2A\u0E33\u0E04\u0E31\u0E0D </h3><div class="space-y-2"><div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800"><p class="text-xs text-yellow-800 dark:text-yellow-200 font-medium flex items-center gap-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:alert-24-regular",
        class: "w-3 h-3"
      }, null, _parent));
      _push(` \u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19 </p><p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1"> \u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19\u0E20\u0E32\u0E22\u0E43\u0E19\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E01\u0E33\u0E2B\u0E19\u0E14 </p></div></div></div><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:calendar-24-regular",
        class: "w-4 h-4 text-indigo-500"
      }, null, _parent));
      _push(` \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E01\u0E32\u0E23 </h3><div class="space-y-2"><div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg"><div class="text-center bg-indigo-500 text-white rounded-lg px-2 py-1 min-w-[40px]"><p class="text-xs font-bold">31</p><p class="text-[10px]">\u0E18.\u0E04.</p></div><div class="flex-1 min-w-0"><p class="text-xs font-medium text-gray-900 dark:text-white truncate"> \u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19\u0E0A\u0E34\u0E49\u0E19\u0E17\u0E35\u0E48 1 </p><p class="text-[10px] text-gray-500 dark:text-gray-400"> 23:59 \u0E19. </p></div></div></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/feeds.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=feeds-CZYvZ45G.mjs.map
