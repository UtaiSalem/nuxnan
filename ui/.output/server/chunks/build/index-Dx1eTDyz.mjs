import { _ as _export_sfc, f as useHead, i as useApi, u as useRouter, l as __nuxt_component_0$1, b as useRuntimeConfig, d as useAuthStore } from './server.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, watch, mergeProps, withCtx, unref, isRef, createTextVNode, createVNode, createBlock, openBlock, Fragment, renderList, toDisplayString, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderAttr, ssrRenderStyle, ssrRenderAttrs } from 'vue/server-renderer';
import { p as publicAssetsURL } from '../_/nitro.mjs';
import { Icon } from '@iconify/vue';
import { u as useAuth } from './useAuth-BmyK1-KK.mjs';
import { _ as _sfc_main$1$1, a as _sfc_main$4 } from './MemberedCoursesWidget-QtO1MfNH.mjs';
import { C as CourseCard } from './CourseCard-DKRWjEJn.mjs';
import 'pinia';
import 'vue-router';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';

const _imports_0 = publicAssetsURL("/images/default-avatar.png");
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "MyCoursesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    const api = useApi();
    const { user } = useAuth();
    const authStore = useAuthStore();
    const points = computed(() => Number(authStore.points));
    const createCourseThreshold = ref(100);
    const canCreateCourse = computed(() => points.value >= Number(createCourseThreshold.value));
    const myCourses = ref([]);
    const isLoading = ref(true);
    const page = ref(1);
    const lastPage = ref(1);
    const isLoadingMore = ref(false);
    const invitations = ref([]);
    const fetchInvitations = async () => {
      if (!user.value) return;
      try {
        const res = await api.get("/api/me/course-invitations");
        if (res.success) {
          invitations.value = res.invitations;
        }
      } catch (error) {
        console.error("Failed to fetch invitations", error);
      }
    };
    const fetchMyCourses = async (isLoadMore = false) => {
      if (!user.value) return;
      if (isLoadMore) {
        isLoadingMore.value = true;
      } else {
        isLoading.value = true;
      }
      if (!isLoadMore) {
        fetchInvitations();
      }
      try {
        const res = await api.get(`/api/courses/users/${user.value.id}/my-courses`, {
          params: {
            page: page.value,
            per_page: 5
          }
        });
        if (res.success) {
          if (res.create_course_threshold !== void 0) {
            createCourseThreshold.value = res.create_course_threshold;
          }
          const newCourses = res.courses.data || res.courses;
          if (isLoadMore) {
            myCourses.value.push(...newCourses);
          } else {
            myCourses.value = newCourses;
          }
          if (res.pagination && res.pagination.last_page) {
            lastPage.value = res.pagination.last_page;
          } else if (res.courses.last_page) {
            lastPage.value = res.courses.last_page;
          }
        }
      } catch (error) {
        console.error("Failed to fetch my courses", error);
      } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
      }
    };
    const getCoverUrl = (course) => {
      if (course.cover) {
        if (course.cover.startsWith("http")) {
          return course.cover;
        }
        return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`;
      }
      return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
    };
    watch(user, (newUser) => {
      if (newUser) {
        fetchMyCourses();
      }
    });
    const formatPoints = (num) => {
      return new Intl.NumberFormat().format(num);
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm mb-6" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="font-semibold text-gray-900 dark:text-white">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</h3>`);
      if (unref(canCreateCourse)) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/courses/create",
          class: "text-xs font-medium text-white bg-vikinger-purple hover:bg-vikinger-purple-dark p-1.5 rounded-lg transition-colors shadow-sm shadow-vikinger-purple/50",
          title: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:plus",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:plus",
                  class: "w-4 h-4"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<div class="group relative"><button disabled class="text-xs font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 dark:text-gray-500 p-1.5 rounded-lg cursor-not-allowed">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:lock",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><div class="absolute bottom-full right-0 mb-2 w-48 bg-gray-900 text-white text-xs rounded-lg py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center"> \u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E04\u0E30\u0E41\u0E19\u0E19\u0E2A\u0E30\u0E2A\u0E21 ${ssrInterpolate(formatPoints(createCourseThreshold.value))} \u0E04\u0E30\u0E41\u0E19\u0E19 </div></div>`);
      }
      _push(`</div>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div><div class="flex-1 space-y-2 py-1"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="space-y-4">`);
        if (invitations.value.length > 0) {
          _push(`<div class="mb-4"><h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25</h4><div class="space-y-2"><!--[-->`);
          ssrRenderList(invitations.value, (invitation) => {
            _push(`<div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30"><div class="flex items-start gap-3"><img${ssrRenderAttr("src", getCoverUrl({ cover: invitation.course.cover }))} class="w-10 h-10 object-cover rounded-md flex-shrink-0"><div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(invitation.course.name)}</p><p class="text-xs text-blue-600 dark:text-blue-400"> \u0E40\u0E0A\u0E34\u0E0D\u0E40\u0E1B\u0E47\u0E19: ${ssrInterpolate(invitation.role_name)}</p><div class="flex gap-2 mt-2"><button class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A</button><button class="text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 px-2 py-1 rounded">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18</button></div></div></div></div>`);
          });
          _push(`<!--]--></div><div class="h-px bg-gray-100 dark:bg-gray-700 my-4"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (myCourses.value.length > 0) {
          _push(`<!--[-->`);
          ssrRenderList(myCourses.value, (course) => {
            _push(ssrRenderComponent(_component_NuxtLink, {
              key: course.id,
              to: `/courses/${course.id}`,
              class: "flex items-start gap-3 group hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 p-2 -mx-2 rounded-lg transition-colors"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="relative shrink-0 w-12 h-12 mt-1"${_scopeId}><img${ssrRenderAttr("src", getCoverUrl(course))}${ssrRenderAttr("alt", course.name)} class="w-full h-full object-cover rounded-lg shadow-sm"${_scopeId}></div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1"${_scopeId}>${ssrInterpolate(course.name)}</h4><div class="flex items-center justify-between"${_scopeId}><span class="text-xs text-gray-500 dark:text-gray-400 truncate"${_scopeId}>${ssrInterpolate(course.user ? course.user.name : course.instructor ? course.instructor.name : "Unknown")}</span></div></div><div class="flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple transition-colors mt-2"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:chevron-right-24-regular",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(`</div>`);
                } else {
                  return [
                    createVNode("div", { class: "relative shrink-0 w-12 h-12 mt-1" }, [
                      createVNode("img", {
                        src: getCoverUrl(course),
                        alt: course.name,
                        class: "w-full h-full object-cover rounded-lg shadow-sm",
                        onError: ($event) => $event.target.src = `${("useRuntimeConfig" in _ctx ? _ctx.useRuntimeConfig : unref(useRuntimeConfig))().public.apiBase}/storage/images/courses/covers/default_cover.jpg`
                      }, null, 40, ["src", "alt", "onError"])
                    ]),
                    createVNode("div", { class: "flex-1 min-w-0" }, [
                      createVNode("h4", { class: "text-sm font-medium text-gray-900 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1" }, toDisplayString(course.name), 1),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-xs text-gray-500 dark:text-gray-400 truncate" }, toDisplayString(course.user ? course.user.name : course.instructor ? course.instructor.name : "Unknown"), 1)
                      ])
                    ]),
                    createVNode("div", { class: "flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple transition-colors mt-2" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:chevron-right-24-regular",
                        class: "w-5 h-5"
                      })
                    ])
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]-->`);
        } else {
          _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:hat-graduation-24-regular",
            class: "w-8 h-8 mx-auto mb-2 opacity-50"
          }, null, _parent));
          _push(`<p>\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div>`);
        }
        _push(`<div class="space-y-2 pt-2">`);
        if (page.value < lastPage.value) {
          _push(`<button${ssrIncludeBooleanAttr(isLoadingMore.value) ? " disabled" : ""} class="w-full py-2 text-xs font-medium text-vikinger-purple bg-vikinger-purple/10 hover:bg-vikinger-purple/20 rounded-lg transition-colors flex items-center justify-center gap-2">`);
          if (isLoadingMore.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push(`<span>\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21</span>`);
          }
          _push(`</button>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(points) >= createCourseThreshold.value) {
          _push(`<div>`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/courses/create",
            class: "w-full py-2 text-xs font-medium text-white bg-vikinger-purple hover:bg-vikinger-purple-dark rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm shadow-vikinger-purple/50"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:plus",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48 `);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "mdi:plus",
                    class: "w-4 h-4"
                  }),
                  createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48 ")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        } else {
          _push(`<div class="group relative"><button disabled class="w-full py-2 text-xs font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 dark:text-gray-500 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:lock",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48 </button><div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max bg-gray-900 text-white text-xs rounded-lg py-1 px-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center whitespace-nowrap"> \u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E04\u0E30\u0E41\u0E19\u0E19\u0E2A\u0E30\u0E2A\u0E21 ${ssrInterpolate(formatPoints(createCourseThreshold.value))} \u0E04\u0E30\u0E41\u0E19\u0E19 \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48 </div></div>`);
        }
        _push(`</div></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/MyCoursesWidget.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "FavoriteCoursesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const config = useRuntimeConfig();
    const favoriteCourses = ref([]);
    const isLoading = ref(true);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm mb-6" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="font-semibold text-gray-900 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E42\u0E1B\u0E23\u0E14</h3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/favourite",
        class: "text-xs text-blue-500 hover:underline"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`\u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14`);
          } else {
            return [
              createTextVNode("\u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div><div class="flex-1 space-y-2 py-1"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (favoriteCourses.value.length > 0) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(favoriteCourses.value, (course) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: course.id,
            to: `/courses/${course.id}`,
            class: "flex gap-3 group hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 -mx-2 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="relative shrink-0 w-12 h-12"${_scopeId}><img${ssrRenderAttr("src", course.cover && !course.cover.startsWith("http") ? `${unref(config).public.apiBase}/storage/images/courses/covers/${course.cover}` : course.cover || `${unref(config).public.apiBase}/storage/images/courses/covers/default_cover.jpg`)}${ssrRenderAttr("alt", course.name)} class="w-full h-full object-cover rounded-lg shadow-sm"${_scopeId}></div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-blue-500 transition-colors"${_scopeId}>${ssrInterpolate(course.name)}</h4><div class="flex items-center gap-2 mt-1"${_scopeId}><span class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(course.category || "General")}</span></div></div><div class="flex items-center justify-center text-gray-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chevron-right-20-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "relative shrink-0 w-12 h-12" }, [
                    createVNode("img", {
                      src: course.cover && !course.cover.startsWith("http") ? `${unref(config).public.apiBase}/storage/images/courses/covers/${course.cover}` : course.cover || `${unref(config).public.apiBase}/storage/images/courses/covers/default_cover.jpg`,
                      alt: course.name,
                      class: "w-full h-full object-cover rounded-lg shadow-sm"
                    }, null, 8, ["src", "alt"])
                  ]),
                  createVNode("div", { class: "flex-1 min-w-0" }, [
                    createVNode("h4", { class: "text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-blue-500 transition-colors" }, toDisplayString(course.name), 1),
                    createVNode("div", { class: "flex items-center gap-2 mt-1" }, [
                      createVNode("span", { class: "text-xs text-gray-500 dark:text-gray-400" }, toDisplayString(course.category || "General"), 1)
                    ])
                  ]),
                  createVNode("div", { class: "flex items-center justify-center text-gray-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:chevron-right-20-regular",
                      class: "w-5 h-5"
                    })
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm"><div class="inline-flex items-center justify-center w-10 h-10 bg-red-50 dark:bg-red-900/10 rounded-full mb-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:heart-24-regular",
          class: "w-5 h-5 text-red-400"
        }, null, _parent));
        _push(`</div><p>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E42\u0E1B\u0E23\u0E14</p></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/FavoriteCoursesWidget.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CourseSearchFilterWidget",
  __ssrInlineRender: true,
  props: {
    searchQuery: {},
    selectedCategory: {},
    selectedLevel: {},
    selectedSemester: {},
    selectedYear: {},
    sortBy: {},
    categories: {},
    levels: {},
    semesters: {},
    years: {},
    sortOptions: {}
  },
  emits: ["update:searchQuery", "update:selectedCategory", "update:selectedLevel", "update:selectedSemester", "update:selectedYear", "update:sortBy", "handleSearch"],
  setup(__props, { emit: __emit }) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" }, _attrs))}><div class="p-4 border-b border-gray-100 dark:border-gray-700"><h3 class="font-bold text-gray-800 dark:text-white">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E01\u0E23\u0E2D\u0E07</h3></div><div class="p-4 space-y-4"><div class="relative">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", __props.searchQuery)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg pl-10 pr-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48 </label><select${ssrRenderAttr("value", __props.selectedCategory)} class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><!--[-->`);
      ssrRenderList(__props.categories, (cat) => {
        _push(`<option${ssrRenderAttr("value", cat.value)}>${ssrInterpolate(cat.label)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E23\u0E30\u0E14\u0E31\u0E1A </label><select${ssrRenderAttr("value", __props.selectedLevel)} class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><!--[-->`);
      ssrRenderList(__props.levels, (level) => {
        _push(`<option${ssrRenderAttr("value", level.value)}>${ssrInterpolate(level.label)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19 </label><select${ssrRenderAttr("value", __props.selectedSemester)} class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><!--[-->`);
      ssrRenderList(__props.semesters, (sem) => {
        _push(`<option${ssrRenderAttr("value", sem.value)}>${ssrInterpolate(sem.label)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32 </label><select${ssrRenderAttr("value", __props.selectedYear)} class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><!--[-->`);
      ssrRenderList(__props.years, (y) => {
        _push(`<option${ssrRenderAttr("value", y.value)}>${ssrInterpolate(y.label)}</option>`);
      });
      _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E08\u0E31\u0E14\u0E40\u0E23\u0E35\u0E22\u0E07 </label><select${ssrRenderAttr("value", __props.sortBy)} class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><!--[-->`);
      ssrRenderList(__props.sortOptions, (option) => {
        _push(`<option${ssrRenderAttr("value", option.value)}>${ssrInterpolate(option.label)}</option>`);
      });
      _push(`<!--]--></select></div></div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/CourseSearchFilterWidget.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Courses - Marketplace"
    });
    const api = useApi();
    const router = useRouter();
    const courses = ref([]);
    const popularCourses = ref([]);
    const isLoading = ref(true);
    const isLoadingMore = ref(false);
    const error = ref(null);
    const searchQuery = ref("");
    const selectedCategory = ref("all");
    const selectedLevel = ref("all");
    const sortBy = ref("latest");
    const selectedSemester = ref("all");
    const selectedYear = ref("all");
    const semesters = ref([
      { value: "all", label: "\u0E17\u0E38\u0E01\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19" },
      { value: "1", label: "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 1" },
      { value: "2", label: "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 2" },
      { value: "3", label: "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 3" },
      { value: "summer", label: "Summer" }
    ]);
    const years = ref([
      { value: "all", label: "\u0E17\u0E38\u0E01\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32" }
    ]);
    const pagination = ref({
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 8
    });
    const hasMorePages = computed(() => pagination.value.currentPage < pagination.value.lastPage);
    const categories = ref([
      { value: "all", label: "\u0E17\u0E38\u0E01\u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48" }
    ]);
    const levels = ref([
      { value: "all", label: "\u0E17\u0E38\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A" }
    ]);
    const sortOptions = [
      { value: "latest", label: "\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14" },
      { value: "popular", label: "\u0E22\u0E2D\u0E14\u0E19\u0E34\u0E22\u0E21" },
      { value: "price-low", label: "\u0E23\u0E32\u0E04\u0E32\u0E15\u0E48\u0E33-\u0E2A\u0E39\u0E07" },
      { value: "price-high", label: "\u0E23\u0E32\u0E04\u0E32\u0E2A\u0E39\u0E07-\u0E15\u0E48\u0E33" },
      { value: "rating", label: "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14" }
    ];
    const fetchCourses = async (page = 1, append = false) => {
      if (page === 1) {
        isLoading.value = true;
      } else {
        isLoadingMore.value = true;
      }
      error.value = null;
      try {
        const params = new URLSearchParams({
          page: String(page),
          per_page: String(pagination.value.perPage)
        });
        if (searchQuery.value) {
          params.append("search", searchQuery.value);
        }
        if (selectedCategory.value !== "all") {
          params.append("category", selectedCategory.value);
        }
        if (selectedLevel.value !== "all") {
          params.append("level", selectedLevel.value);
        }
        if (sortBy.value) {
          params.append("sort", sortBy.value);
        }
        if (selectedSemester.value !== "all") {
          params.append("semester", selectedSemester.value);
        }
        if (selectedYear.value !== "all") {
          params.append("academic_year", selectedYear.value);
        }
        const response = await api.get(`/api/courses?${params.toString()}`);
        if (response.courses) {
          const newCourses = Array.isArray(response.courses) ? response.courses : response.courses.data || [];
          if (append) {
            courses.value = [...courses.value, ...newCourses];
          } else {
            courses.value = newCourses;
            popularCourses.value = newCourses.slice(0, 3);
          }
          if (response.courses.current_page !== void 0) {
            pagination.value = {
              currentPage: response.courses.current_page || page,
              lastPage: response.courses.last_page || 1,
              total: response.courses.total || 0,
              perPage: response.courses.per_page || 8
            };
          } else {
            pagination.value.currentPage = page;
            if (newCourses.length < pagination.value.perPage) {
              pagination.value.lastPage = page;
            } else {
              pagination.value.lastPage = page + 1;
            }
          }
        }
      } catch (err) {
        console.error("Error fetching courses:", err);
        error.value = "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E44\u0E14\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07";
      } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
      }
    };
    const loadMore = async () => {
      if (isLoadingMore.value || !hasMorePages.value) return;
      await fetchCourses(pagination.value.currentPage + 1, true);
    };
    let searchTimeout;
    const handleSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchCourses(1);
      }, 300);
    };
    const getCoverUrl = (course) => {
      if (course.cover) {
        if (course.cover.startsWith("http")) {
          return course.cover;
        }
        return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`;
      }
      return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
    };
    const goToCourse = (courseId) => {
      router.push(`/courses/${courseId}`);
    };
    watch([selectedCategory, selectedLevel, sortBy, selectedSemester, selectedYear], () => {
      fetchCourses(1);
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLayout = __nuxt_component_0$1;
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(_component_NuxtLayout, mergeProps({ name: "main" }, _attrs), {
        hero: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-400" data-v-45e6725c${_scopeId}><div class="absolute inset-0 opacity-20" data-v-45e6725c${_scopeId}><div class="absolute inset-0" style="${ssrRenderStyle({ "background-image": "url('/images/resources/animate-bg.png')", "background-size": "cover" })}" data-v-45e6725c${_scopeId}></div></div><div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/badge/gold-b.png`)} alt="badge" class="w-16 h-16 md:w-20 md:h-20 drop-shadow-lg animate-bounce" style="${ssrRenderStyle({ "animation-duration": "2s" })}" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/badge/scientist-b.png`)} alt="badge" class="w-12 h-12 md:w-16 md:h-16 drop-shadow-lg animate-bounce hidden sm:block" style="${ssrRenderStyle({ "animation-duration": "2.5s", "animation-delay": "0.3s" })}" data-v-45e6725c${_scopeId}></div><div class="relative z-10 px-6 py-8 md:py-10 ml-24 sm:ml-36 md:ml-44" data-v-45e6725c${_scopeId}><h1 class="text-2xl md:text-3xl font-bold text-white mb-2" data-v-45e6725c${_scopeId}> \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </h1><p class="text-blue-100 text-sm md:text-base" data-v-45e6725c${_scopeId}> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E2A\u0E19\u0E43\u0E08 </p></div><div class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 items-center gap-3" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/badge/globet-b.png`)} alt="badge" class="w-10 h-10 drop-shadow-lg opacity-70 animate-pulse" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/badge/collector-b.png`)} alt="badge" class="w-14 h-14 drop-shadow-lg opacity-80 animate-bounce" style="${ssrRenderStyle({ "animation-duration": "3s" })}" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/badge/platinum-b.png`)} alt="badge" class="w-12 h-12 drop-shadow-lg opacity-70 animate-pulse" style="${ssrRenderStyle({ "animation-delay": "0.5s" })}" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/badge/tycoon.png`)} alt="badge" class="w-16 h-16 drop-shadow-lg opacity-90 animate-bounce" style="${ssrRenderStyle({ "animation-duration": "2.8s", "animation-delay": "0.2s" })}" data-v-45e6725c${_scopeId}></div><div class="absolute top-4 left-1/3 w-2 h-2 bg-white rounded-full animate-ping opacity-75" data-v-45e6725c${_scopeId}></div><div class="absolute bottom-6 left-1/2 w-1.5 h-1.5 bg-yellow-200 rounded-full animate-ping opacity-60" style="${ssrRenderStyle({ "animation-delay": "0.5s" })}" data-v-45e6725c${_scopeId}></div><div class="absolute top-6 right-1/3 w-1 h-1 bg-white rounded-full animate-ping opacity-50" style="${ssrRenderStyle({ "animation-delay": "1s" })}" data-v-45e6725c${_scopeId}></div></div>`);
          } else {
            return [
              createVNode("div", { class: "relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-400" }, [
                createVNode("div", { class: "absolute inset-0 opacity-20" }, [
                  createVNode("div", {
                    class: "absolute inset-0",
                    style: { "background-image": "url('/images/resources/animate-bg.png')", "background-size": "cover" }
                  })
                ]),
                createVNode("div", { class: "absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2" }, [
                  createVNode("img", {
                    src: `${_ctx.$config.public.apiBase}/storage/images/badge/gold-b.png`,
                    alt: "badge",
                    class: "w-16 h-16 md:w-20 md:h-20 drop-shadow-lg animate-bounce",
                    style: { "animation-duration": "2s" }
                  }, null, 8, ["src"]),
                  createVNode("img", {
                    src: `${_ctx.$config.public.apiBase}/storage/images/badge/scientist-b.png`,
                    alt: "badge",
                    class: "w-12 h-12 md:w-16 md:h-16 drop-shadow-lg animate-bounce hidden sm:block",
                    style: { "animation-duration": "2.5s", "animation-delay": "0.3s" }
                  }, null, 8, ["src"])
                ]),
                createVNode("div", { class: "relative z-10 px-6 py-8 md:py-10 ml-24 sm:ml-36 md:ml-44" }, [
                  createVNode("h1", { class: "text-2xl md:text-3xl font-bold text-white mb-2" }, " \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 "),
                  createVNode("p", { class: "text-blue-100 text-sm md:text-base" }, " \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E2A\u0E19\u0E43\u0E08 ")
                ]),
                createVNode("div", { class: "hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 items-center gap-3" }, [
                  createVNode("img", {
                    src: `${_ctx.$config.public.apiBase}/storage/images/badge/globet-b.png`,
                    alt: "badge",
                    class: "w-10 h-10 drop-shadow-lg opacity-70 animate-pulse"
                  }, null, 8, ["src"]),
                  createVNode("img", {
                    src: `${_ctx.$config.public.apiBase}/storage/images/badge/collector-b.png`,
                    alt: "badge",
                    class: "w-14 h-14 drop-shadow-lg opacity-80 animate-bounce",
                    style: { "animation-duration": "3s" }
                  }, null, 8, ["src"]),
                  createVNode("img", {
                    src: `${_ctx.$config.public.apiBase}/storage/images/badge/platinum-b.png`,
                    alt: "badge",
                    class: "w-12 h-12 drop-shadow-lg opacity-70 animate-pulse",
                    style: { "animation-delay": "0.5s" }
                  }, null, 8, ["src"]),
                  createVNode("img", {
                    src: `${_ctx.$config.public.apiBase}/storage/images/badge/tycoon.png`,
                    alt: "badge",
                    class: "w-16 h-16 drop-shadow-lg opacity-90 animate-bounce",
                    style: { "animation-duration": "2.8s", "animation-delay": "0.2s" }
                  }, null, 8, ["src"])
                ]),
                createVNode("div", { class: "absolute top-4 left-1/3 w-2 h-2 bg-white rounded-full animate-ping opacity-75" }),
                createVNode("div", {
                  class: "absolute bottom-6 left-1/2 w-1.5 h-1.5 bg-yellow-200 rounded-full animate-ping opacity-60",
                  style: { "animation-delay": "0.5s" }
                }),
                createVNode("div", {
                  class: "absolute top-6 right-1/3 w-1 h-1 bg-white rounded-full animate-ping opacity-50",
                  style: { "animation-delay": "1s" }
                })
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="max-w-7xl mx-auto px-4 py-6" data-v-45e6725c${_scopeId}><div class="grid grid-cols-1 xl:grid-cols-4 gap-6" data-v-45e6725c${_scopeId}><div class="col-span-1 order-2 xl:order-1 space-y-6" data-v-45e6725c${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$1, {
              searchQuery: unref(searchQuery),
              "onUpdate:searchQuery": ($event) => isRef(searchQuery) ? searchQuery.value = $event : null,
              selectedCategory: unref(selectedCategory),
              "onUpdate:selectedCategory": ($event) => isRef(selectedCategory) ? selectedCategory.value = $event : null,
              selectedLevel: unref(selectedLevel),
              "onUpdate:selectedLevel": ($event) => isRef(selectedLevel) ? selectedLevel.value = $event : null,
              selectedSemester: unref(selectedSemester),
              "onUpdate:selectedSemester": ($event) => isRef(selectedSemester) ? selectedSemester.value = $event : null,
              selectedYear: unref(selectedYear),
              "onUpdate:selectedYear": ($event) => isRef(selectedYear) ? selectedYear.value = $event : null,
              sortBy: unref(sortBy),
              "onUpdate:sortBy": ($event) => isRef(sortBy) ? sortBy.value = $event : null,
              categories: unref(categories),
              levels: unref(levels),
              semesters: unref(semesters),
              years: unref(years),
              sortOptions,
              onHandleSearch: handleSearch
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1$1, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$2, null, null, _parent2, _scopeId));
            _push2(`</div><div class="col-span-1 xl:col-span-2 min-w-0 order-1 xl:order-2" data-v-45e6725c${_scopeId}>`);
            if (unref(isLoading)) {
              _push2(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-v-45e6725c${_scopeId}><!--[-->`);
              ssrRenderList(6, (i) => {
                _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden animate-pulse shadow-lg" data-v-45e6725c${_scopeId}><div class="h-44 bg-gray-200 dark:bg-gray-700" data-v-45e6725c${_scopeId}></div><div class="p-4 space-y-3" data-v-45e6725c${_scopeId}><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4" data-v-45e6725c${_scopeId}></div><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2" data-v-45e6725c${_scopeId}></div><div class="h-8 bg-gray-200 dark:bg-gray-700 rounded" data-v-45e6725c${_scopeId}></div></div></div>`);
              });
              _push2(`<!--]--></div>`);
            } else if (unref(error)) {
              _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg" data-v-45e6725c${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:error-circle-24-regular",
                class: "w-20 h-20 text-red-500 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2" data-v-45e6725c${_scopeId}>\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h3><p class="text-gray-500 mb-4" data-v-45e6725c${_scopeId}>${ssrInterpolate(unref(error))}</p><button class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors" data-v-45e6725c${_scopeId}> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
            } else if (unref(courses).length === 0) {
              _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg" data-v-45e6725c${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-regular",
                class: "w-20 h-20 text-gray-400 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2" data-v-45e6725c${_scopeId}>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h3><p class="text-gray-500" data-v-45e6725c${_scopeId}>\u0E25\u0E2D\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E04\u0E33\u0E04\u0E49\u0E19\u0E2D\u0E37\u0E48\u0E19 \u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07</p></div>`);
            } else {
              _push2(`<!--[--><div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-v-45e6725c${_scopeId}><!--[-->`);
              ssrRenderList(unref(courses), (course, index2) => {
                _push2(ssrRenderComponent(CourseCard, {
                  key: course.id,
                  course,
                  index: index2
                }, null, _parent2, _scopeId));
              });
              _push2(`<!--]--></div>`);
              if (unref(hasMorePages)) {
                _push2(`<div class="mt-8 text-center" data-v-45e6725c${_scopeId}><button${ssrIncludeBooleanAttr(unref(isLoadingMore)) ? " disabled" : ""} class="px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto font-medium" data-v-45e6725c${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: unref(isLoadingMore) ? "fluent:spinner-ios-20-regular" : "fluent:arrow-down-24-regular",
                  class: ["w-5 h-5", { "animate-spin": unref(isLoadingMore) }]
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(unref(isLoadingMore) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14..." : "\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</button></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<!--]-->`);
            }
            _push2(`</div><div class="col-span-1 space-y-6 order-3" data-v-45e6725c${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, null, null, _parent2, _scopeId));
            _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" data-v-45e6725c${_scopeId}><div class="p-4 border-b border-gray-100 dark:border-gray-700" data-v-45e6725c${_scopeId}><h3 class="font-bold text-gray-800 dark:text-white" data-v-45e6725c${_scopeId}>Popular Courses</h3></div><div class="divide-y divide-gray-100 dark:divide-gray-700" data-v-45e6725c${_scopeId}><!--[-->`);
            ssrRenderList(unref(popularCourses), (course) => {
              var _a;
              _push2(`<div class="p-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", getCoverUrl(course))}${ssrRenderAttr("alt", course.name)} class="w-16 h-16 rounded-lg object-cover flex-shrink-0" data-v-45e6725c${_scopeId}><div class="flex-1 min-w-0" data-v-45e6725c${_scopeId}><h4 class="text-sm font-medium text-gray-800 dark:text-white line-clamp-2 mb-1" data-v-45e6725c${_scopeId}>${ssrInterpolate(course.name)}</h4><p class="text-xs text-blue-500" data-v-45e6725c${_scopeId}>${ssrInterpolate(((_a = course.user) == null ? void 0 : _a.name) || "Unknown")}</p></div><button class="p-1 text-gray-400 hover:text-blue-500 transition-colors flex-shrink-0" data-v-45e6725c${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:bookmark-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`</button></div>`);
            });
            _push2(`<!--]-->`);
            if (unref(popularCourses).length === 0 && !unref(isLoading)) {
              _push2(`<div class="p-4 text-center text-gray-500 text-sm" data-v-45e6725c${_scopeId}> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4" data-v-45e6725c${_scopeId}><h3 class="font-bold text-gray-800 dark:text-white mb-3" data-v-45e6725c${_scopeId}>Ask Research Question?</h3><div class="flex items-start gap-3 mb-4" data-v-45e6725c${_scopeId}><div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0" data-v-45e6725c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:question-circle-24-regular",
              class: "w-5 h-5 text-blue-500"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="text-sm text-gray-600 dark:text-gray-400" data-v-45e6725c${_scopeId}> Ask questions in Q&amp;A to get help from experts in your field. </p></div><button class="w-full py-2.5 border-2 border-blue-500 text-blue-500 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition-colors" data-v-45e6725c${_scopeId}> Ask a question </button></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" data-v-45e6725c${_scopeId}><div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between" data-v-45e6725c${_scopeId}><h3 class="font-bold text-gray-800 dark:text-white" data-v-45e6725c${_scopeId}>Explore Events</h3>`);
            _push2(ssrRenderComponent(_component_NuxtLink, {
              to: "/events",
              class: "text-sm text-blue-500 hover:underline"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`See All`);
                } else {
                  return [
                    createTextVNode("See All")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><div class="p-4 space-y-3" data-v-45e6725c${_scopeId}><div class="relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-cyan-500 to-blue-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity" data-v-45e6725c${_scopeId}><div class="absolute inset-0 bg-black/20" data-v-45e6725c${_scopeId}></div><div class="relative" data-v-45e6725c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:building-24-regular",
              class: "w-6 h-6 text-white mb-1"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-white text-sm font-medium line-clamp-2" data-v-45e6725c${_scopeId}> University good night event in columbia </p></div></div><div class="relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-green-500 to-teal-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity" data-v-45e6725c${_scopeId}><div class="absolute inset-0 bg-black/20" data-v-45e6725c${_scopeId}></div><div class="relative" data-v-45e6725c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-audience-24-regular",
              class: "w-6 h-6 text-white mb-1"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-white text-sm font-medium" data-v-45e6725c${_scopeId}>The 3rd International Conference 2020</p></div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" data-v-45e6725c${_scopeId}><div class="p-4 border-b border-gray-100 dark:border-gray-700" data-v-45e6725c${_scopeId}><h3 class="font-bold text-gray-800 dark:text-white" data-v-45e6725c${_scopeId}>Who&#39;s Following</h3></div><div class="divide-y divide-gray-100 dark:divide-gray-700" data-v-45e6725c${_scopeId}><div class="p-4 flex items-center justify-between" data-v-45e6725c${_scopeId}><div class="flex items-center gap-3" data-v-45e6725c${_scopeId}><div class="relative" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", _imports_0)} alt="User" class="w-10 h-10 rounded-full object-cover" data-v-45e6725c${_scopeId}><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900" data-v-45e6725c${_scopeId}> 5 </div></div><div data-v-45e6725c${_scopeId}><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-45e6725c${_scopeId}>Kelly Bill</p><p class="text-xs text-gray-500" data-v-45e6725c${_scopeId}>Dept colleague</p></div></div><button class="text-blue-500 text-sm font-medium hover:underline" data-v-45e6725c${_scopeId}>Follow</button></div><div class="p-4 flex items-center justify-between" data-v-45e6725c${_scopeId}><div class="flex items-center gap-3" data-v-45e6725c${_scopeId}><div class="relative" data-v-45e6725c${_scopeId}><img${ssrRenderAttr("src", _imports_0)} alt="User" class="w-10 h-10 rounded-full object-cover" data-v-45e6725c${_scopeId}><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900" data-v-45e6725c${_scopeId}> 5 </div></div><div data-v-45e6725c${_scopeId}><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-45e6725c${_scopeId}>Issabel</p><p class="text-xs text-gray-500" data-v-45e6725c${_scopeId}>Dept colleague</p></div></div><button class="text-blue-500 text-sm font-medium hover:underline" data-v-45e6725c${_scopeId}>Follow</button></div></div></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "max-w-7xl mx-auto px-4 py-6" }, [
                createVNode("div", { class: "grid grid-cols-1 xl:grid-cols-4 gap-6" }, [
                  createVNode("div", { class: "col-span-1 order-2 xl:order-1 space-y-6" }, [
                    createVNode(_sfc_main$1, {
                      searchQuery: unref(searchQuery),
                      "onUpdate:searchQuery": ($event) => isRef(searchQuery) ? searchQuery.value = $event : null,
                      selectedCategory: unref(selectedCategory),
                      "onUpdate:selectedCategory": ($event) => isRef(selectedCategory) ? selectedCategory.value = $event : null,
                      selectedLevel: unref(selectedLevel),
                      "onUpdate:selectedLevel": ($event) => isRef(selectedLevel) ? selectedLevel.value = $event : null,
                      selectedSemester: unref(selectedSemester),
                      "onUpdate:selectedSemester": ($event) => isRef(selectedSemester) ? selectedSemester.value = $event : null,
                      selectedYear: unref(selectedYear),
                      "onUpdate:selectedYear": ($event) => isRef(selectedYear) ? selectedYear.value = $event : null,
                      sortBy: unref(sortBy),
                      "onUpdate:sortBy": ($event) => isRef(sortBy) ? sortBy.value = $event : null,
                      categories: unref(categories),
                      levels: unref(levels),
                      semesters: unref(semesters),
                      years: unref(years),
                      sortOptions,
                      onHandleSearch: handleSearch
                    }, null, 8, ["searchQuery", "onUpdate:searchQuery", "selectedCategory", "onUpdate:selectedCategory", "selectedLevel", "onUpdate:selectedLevel", "selectedSemester", "onUpdate:selectedSemester", "selectedYear", "onUpdate:selectedYear", "sortBy", "onUpdate:sortBy", "categories", "levels", "semesters", "years"]),
                    createVNode(_sfc_main$1$1),
                    createVNode(_sfc_main$2)
                  ]),
                  createVNode("div", { class: "col-span-1 xl:col-span-2 min-w-0 order-1 xl:order-2" }, [
                    unref(isLoading) ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "grid grid-cols-1 md:grid-cols-2 gap-6"
                    }, [
                      (openBlock(), createBlock(Fragment, null, renderList(6, (i) => {
                        return createVNode("div", {
                          key: i,
                          class: "bg-white dark:bg-gray-800 rounded-xl overflow-hidden animate-pulse shadow-lg"
                        }, [
                          createVNode("div", { class: "h-44 bg-gray-200 dark:bg-gray-700" }),
                          createVNode("div", { class: "p-4 space-y-3" }, [
                            createVNode("div", { class: "h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4" }),
                            createVNode("div", { class: "h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2" }),
                            createVNode("div", { class: "h-8 bg-gray-200 dark:bg-gray-700 rounded" })
                          ])
                        ]);
                      }), 64))
                    ])) : unref(error) ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:error-circle-24-regular",
                        class: "w-20 h-20 text-red-500 mx-auto mb-4"
                      }),
                      createVNode("h3", { class: "text-xl font-bold text-gray-800 dark:text-white mb-2" }, "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"),
                      createVNode("p", { class: "text-gray-500 mb-4" }, toDisplayString(unref(error)), 1),
                      createVNode("button", {
                        onClick: ($event) => fetchCourses(1),
                        class: "px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                      }, " \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 ", 8, ["onClick"])
                    ])) : unref(courses).length === 0 ? (openBlock(), createBlock("div", {
                      key: 2,
                      class: "bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:book-24-regular",
                        class: "w-20 h-20 text-gray-400 mx-auto mb-4"
                      }),
                      createVNode("h3", { class: "text-xl font-bold text-gray-800 dark:text-white mb-2" }, "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"),
                      createVNode("p", { class: "text-gray-500" }, "\u0E25\u0E2D\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E04\u0E33\u0E04\u0E49\u0E19\u0E2D\u0E37\u0E48\u0E19 \u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07")
                    ])) : (openBlock(), createBlock(Fragment, { key: 3 }, [
                      createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-6" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(unref(courses), (course, index2) => {
                          return openBlock(), createBlock(CourseCard, {
                            key: course.id,
                            course,
                            index: index2
                          }, null, 8, ["course", "index"]);
                        }), 128))
                      ]),
                      unref(hasMorePages) ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-8 text-center"
                      }, [
                        createVNode("button", {
                          onClick: loadMore,
                          disabled: unref(isLoadingMore),
                          class: "px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto font-medium"
                        }, [
                          createVNode(unref(Icon), {
                            icon: unref(isLoadingMore) ? "fluent:spinner-ios-20-regular" : "fluent:arrow-down-24-regular",
                            class: ["w-5 h-5", { "animate-spin": unref(isLoadingMore) }]
                          }, null, 8, ["icon", "class"]),
                          createTextVNode(" " + toDisplayString(unref(isLoadingMore) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14..." : "\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21"), 1)
                        ], 8, ["disabled"])
                      ])) : createCommentVNode("", true)
                    ], 64))
                  ]),
                  createVNode("div", { class: "col-span-1 space-y-6 order-3" }, [
                    createVNode(_sfc_main$4),
                    createVNode(_sfc_main$3),
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" }, [
                      createVNode("div", { class: "p-4 border-b border-gray-100 dark:border-gray-700" }, [
                        createVNode("h3", { class: "font-bold text-gray-800 dark:text-white" }, "Popular Courses")
                      ]),
                      createVNode("div", { class: "divide-y divide-gray-100 dark:divide-gray-700" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(unref(popularCourses), (course) => {
                          var _a;
                          return openBlock(), createBlock("div", {
                            key: course.id,
                            class: "p-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer",
                            onClick: ($event) => goToCourse(course.id)
                          }, [
                            createVNode("img", {
                              src: getCoverUrl(course),
                              alt: course.name,
                              class: "w-16 h-16 rounded-lg object-cover flex-shrink-0"
                            }, null, 8, ["src", "alt"]),
                            createVNode("div", { class: "flex-1 min-w-0" }, [
                              createVNode("h4", { class: "text-sm font-medium text-gray-800 dark:text-white line-clamp-2 mb-1" }, toDisplayString(course.name), 1),
                              createVNode("p", { class: "text-xs text-blue-500" }, toDisplayString(((_a = course.user) == null ? void 0 : _a.name) || "Unknown"), 1)
                            ]),
                            createVNode("button", { class: "p-1 text-gray-400 hover:text-blue-500 transition-colors flex-shrink-0" }, [
                              createVNode(unref(Icon), {
                                icon: "fluent:bookmark-24-regular",
                                class: "w-5 h-5"
                              })
                            ])
                          ], 8, ["onClick"]);
                        }), 128)),
                        unref(popularCourses).length === 0 && !unref(isLoading) ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "p-4 text-center text-gray-500 text-sm"
                        }, " \u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 ")) : createCommentVNode("", true)
                      ])
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4" }, [
                      createVNode("h3", { class: "font-bold text-gray-800 dark:text-white mb-3" }, "Ask Research Question?"),
                      createVNode("div", { class: "flex items-start gap-3 mb-4" }, [
                        createVNode("div", { class: "w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0" }, [
                          createVNode(unref(Icon), {
                            icon: "fluent:question-circle-24-regular",
                            class: "w-5 h-5 text-blue-500"
                          })
                        ]),
                        createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, " Ask questions in Q&A to get help from experts in your field. ")
                      ]),
                      createVNode("button", { class: "w-full py-2.5 border-2 border-blue-500 text-blue-500 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition-colors" }, " Ask a question ")
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" }, [
                      createVNode("div", { class: "p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between" }, [
                        createVNode("h3", { class: "font-bold text-gray-800 dark:text-white" }, "Explore Events"),
                        createVNode(_component_NuxtLink, {
                          to: "/events",
                          class: "text-sm text-blue-500 hover:underline"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("See All")
                          ]),
                          _: 1
                        })
                      ]),
                      createVNode("div", { class: "p-4 space-y-3" }, [
                        createVNode("div", { class: "relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-cyan-500 to-blue-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity" }, [
                          createVNode("div", { class: "absolute inset-0 bg-black/20" }),
                          createVNode("div", { class: "relative" }, [
                            createVNode(unref(Icon), {
                              icon: "fluent:building-24-regular",
                              class: "w-6 h-6 text-white mb-1"
                            }),
                            createVNode("p", { class: "text-white text-sm font-medium line-clamp-2" }, " University good night event in columbia ")
                          ])
                        ]),
                        createVNode("div", { class: "relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-green-500 to-teal-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity" }, [
                          createVNode("div", { class: "absolute inset-0 bg-black/20" }),
                          createVNode("div", { class: "relative" }, [
                            createVNode(unref(Icon), {
                              icon: "fluent:people-audience-24-regular",
                              class: "w-6 h-6 text-white mb-1"
                            }),
                            createVNode("p", { class: "text-white text-sm font-medium" }, "The 3rd International Conference 2020")
                          ])
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" }, [
                      createVNode("div", { class: "p-4 border-b border-gray-100 dark:border-gray-700" }, [
                        createVNode("h3", { class: "font-bold text-gray-800 dark:text-white" }, "Who's Following")
                      ]),
                      createVNode("div", { class: "divide-y divide-gray-100 dark:divide-gray-700" }, [
                        createVNode("div", { class: "p-4 flex items-center justify-between" }, [
                          createVNode("div", { class: "flex items-center gap-3" }, [
                            createVNode("div", { class: "relative" }, [
                              createVNode("img", {
                                src: _imports_0,
                                alt: "User",
                                class: "w-10 h-10 rounded-full object-cover"
                              }),
                              createVNode("div", { class: "absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900" }, " 5 ")
                            ]),
                            createVNode("div", null, [
                              createVNode("p", { class: "text-sm font-medium text-gray-800 dark:text-white" }, "Kelly Bill"),
                              createVNode("p", { class: "text-xs text-gray-500" }, "Dept colleague")
                            ])
                          ]),
                          createVNode("button", { class: "text-blue-500 text-sm font-medium hover:underline" }, "Follow")
                        ]),
                        createVNode("div", { class: "p-4 flex items-center justify-between" }, [
                          createVNode("div", { class: "flex items-center gap-3" }, [
                            createVNode("div", { class: "relative" }, [
                              createVNode("img", {
                                src: _imports_0,
                                alt: "User",
                                class: "w-10 h-10 rounded-full object-cover"
                              }),
                              createVNode("div", { class: "absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900" }, " 5 ")
                            ]),
                            createVNode("div", null, [
                              createVNode("p", { class: "text-sm font-medium text-gray-800 dark:text-white" }, "Issabel"),
                              createVNode("p", { class: "text-xs text-gray-500" }, "Dept colleague")
                            ])
                          ]),
                          createVNode("button", { class: "text-blue-500 text-sm font-medium hover:underline" }, "Follow")
                        ])
                      ])
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-45e6725c"]]);

export { index as default };
//# sourceMappingURL=index-Dx1eTDyz.mjs.map
