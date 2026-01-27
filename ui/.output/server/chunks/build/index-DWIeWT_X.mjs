import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { b as useRuntimeConfig } from './server.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    const courses = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref("");
    const selectedStatus = ref("all");
    ref(1);
    ref(1);
    ref(10);
    const totalCourses = ref(0);
    const statuses = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "published", label: "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48\u0E41\u0E25\u0E49\u0E27" },
      { value: "draft", label: "\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07" },
      { value: "archived", label: "\u0E40\u0E01\u0E47\u0E1A\u0E16\u0E32\u0E27\u0E23" }
    ];
    const getStatusBadge = (status) => {
      const badges = {
        published: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        draft: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
        archived: "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
      };
      return badges[status] || badges.draft;
    };
    const getStatusLabel = (status) => {
      const labels = {
        published: "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48",
        draft: "\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07",
        archived: "\u0E40\u0E01\u0E47\u0E1A\u0E16\u0E32\u0E27\u0E23"
      };
      return labels[status] || "\u0E44\u0E21\u0E48\u0E17\u0E23\u0E32\u0E1A";
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19</h1><p class="text-gray-500 dark:text-gray-400 mt-1"> \u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(totalCourses.value.toLocaleString())} \u0E04\u0E2D\u0E23\u0E4C\u0E2A </p></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/courses/create",
        class: "inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:add-24-regular",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex flex-col sm:flex-row gap-4"><div class="flex-1 relative">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E04\u0E2D\u0E23\u0E4C\u0E2A..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></div><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(statuses, (status) => {
        _push(`<option${ssrRenderAttr("value", status.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedStatus.value) ? ssrLooseContain(selectedStatus.value, status.value) : ssrLooseEqual(selectedStatus.value, status.value)) ? " selected" : ""}>${ssrInterpolate(status.label)}</option>`);
      });
      _push(`<!--]--></select><button class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"> \u0E04\u0E49\u0E19\u0E2B\u0E32 </button></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">`);
      if (isLoading.value) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else {
        _push(`<div class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(courses.value, (course) => {
          var _a;
          _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><div class="flex flex-col sm:flex-row gap-4"><div class="w-full sm:w-40 h-24 flex-shrink-0"><img${ssrRenderAttr("src", course.thumbnail || "/storage/images/default-course.jpg")}${ssrRenderAttr("alt", course.title)} class="w-full h-full object-cover rounded-xl"></div><div class="flex-1 min-w-0"><div class="flex items-start justify-between gap-4"><div><h3 class="font-semibold text-gray-800 dark:text-white line-clamp-1">${ssrInterpolate(course.title)}</h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">${ssrInterpolate(course.description)}</p></div><span class="${ssrRenderClass([getStatusBadge(course.status), "px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap"])}">${ssrInterpolate(getStatusLabel(course.status))}</span></div><div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500 dark:text-gray-400"><div class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span>${ssrInterpolate(((_a = course.instructor) == null ? void 0 : _a.name) || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</span></div><div class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span>${ssrInterpolate(course.enrollments_count || 0)} \u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19</span></div><div class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:money-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span>\u0E3F${ssrInterpolate((course.price || 0).toLocaleString())}</span></div></div></div><div class="flex items-center gap-2 sm:flex-col sm:justify-center">`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/nuxnan-admin/courses/${course.id}`,
            class: "p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:eye-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:eye-24-regular",
                    class: "w-5 h-5"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/nuxnan-admin/courses/${course.id}/edit`,
            class: "p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:edit-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-regular",
                    class: "w-5 h-5"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`<button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></div></div>`);
        });
        _push(`<!--]-->`);
        if (courses.value.length === 0) {
          _push(`<div class="p-8 text-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:hat-graduation-24-regular",
            class: "w-12 h-12 text-gray-300 mx-auto"
          }, null, _parent));
          _push(`<p class="text-gray-500 mt-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/courses/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-DWIeWT_X.mjs.map
