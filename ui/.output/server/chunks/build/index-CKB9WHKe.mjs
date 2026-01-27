import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, unref, withCtx, createVNode, toDisplayString, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { d as useAuthStore } from './server.mjs';
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
    const authStore = useAuthStore();
    const stats = ref([
      {
        title: "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14",
        value: "...",
        change: "0%",
        isPositive: true,
        icon: "fluent:people-24-regular",
        color: "bg-blue-500"
      },
      {
        title: "\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14",
        value: "...",
        change: "0%",
        isPositive: true,
        icon: "fluent:hat-graduation-24-regular",
        color: "bg-green-500"
      },
      {
        title: "\u0E2D\u0E30\u0E04\u0E32\u0E40\u0E14\u0E21\u0E35",
        value: "...",
        change: "0%",
        isPositive: true,
        icon: "fluent:building-24-regular",
        color: "bg-purple-500"
      },
      {
        title: "\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49\u0E23\u0E27\u0E21",
        value: "...",
        change: "0%",
        isPositive: true,
        icon: "fluent:money-24-regular",
        color: "bg-yellow-500"
      }
    ]);
    const recentActivities = ref([]);
    const topCourses = ref([]);
    const quickActions = [
      {
        title: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E43\u0E2B\u0E21\u0E48",
        description: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E43\u0E2B\u0E21\u0E48",
        icon: "fluent:person-add-24-regular",
        href: "/nuxnan-admin/users/create",
        color: "from-blue-500 to-blue-600"
      },
      {
        title: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E2B\u0E21\u0E48",
        description: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E2B\u0E21\u0E48",
        icon: "fluent:add-circle-24-regular",
        href: "/nuxnan-admin/courses/create",
        color: "from-green-500 to-green-600"
      },
      {
        title: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07",
        description: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E04\u0E49\u0E14\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14\u0E43\u0E2B\u0E21\u0E48",
        icon: "fluent:ticket-diagonal-24-regular",
        href: "/nuxnan-admin/coupons/create",
        color: "from-purple-500 to-purple-600"
      },
      {
        title: "\u0E14\u0E39\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19",
        description: "\u0E14\u0E39\u0E2A\u0E16\u0E34\u0E15\u0E34\u0E41\u0E25\u0E30\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19\u0E15\u0E48\u0E32\u0E07\u0E46",
        icon: "fluent:data-pie-24-regular",
        href: "/nuxnan-admin/reports",
        color: "from-orange-500 to-orange-600"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white"> \u0E22\u0E34\u0E19\u0E14\u0E35\u0E15\u0E49\u0E2D\u0E19\u0E23\u0E31\u0E1A, ${ssrInterpolate(((_a = unref(authStore).user) == null ? void 0 : _a.name) || "Admin")}! \u{1F44B} </h1><p class="text-gray-500 dark:text-gray-400 mt-1"> \u0E19\u0E35\u0E48\u0E04\u0E37\u0E2D\u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21\u0E02\u0E2D\u0E07\u0E23\u0E30\u0E1A\u0E1A Nuxnan \u0E43\u0E19\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49 </p></div><div class="flex gap-3"><button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-download-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19 </button><button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:add-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div></div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6"><!--[-->`);
      ssrRenderList(stats.value, (stat) => {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-start justify-between"><div><p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(stat.title)}</p><p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">${ssrInterpolate(stat.value)}</p><div class="flex items-center gap-1 mt-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: stat.isPositive ? "fluent:arrow-trending-24-regular" : "fluent:arrow-trending-down-24-regular",
          class: [stat.isPositive ? "text-green-500" : "text-red-500", "w-4 h-4"]
        }, null, _parent));
        _push(`<span class="${ssrRenderClass([stat.isPositive ? "text-green-500" : "text-red-500", "text-sm font-medium"])}">${ssrInterpolate(stat.change)}</span><span class="text-gray-400 text-sm">\u0E08\u0E32\u0E01\u0E40\u0E14\u0E37\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27</span></div></div><div class="${ssrRenderClass([stat.color, "p-3 rounded-xl text-white"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: stat.icon,
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</div></div></div>`);
      });
      _push(`<!--]--></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-6"><div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">\u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E14\u0E48\u0E27\u0E19</h2><div class="space-y-3"><!--[-->`);
      ssrRenderList(quickActions, (action) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          key: action.title,
          to: action.href,
          class: "flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="${ssrRenderClass(["bg-gradient-to-r", action.color, "p-2.5 rounded-xl text-white"])}"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: action.icon,
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="flex-1"${_scopeId}><p class="font-medium text-gray-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"${_scopeId}>${ssrInterpolate(action.title)}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(action.description)}</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:chevron-right-24-regular",
                class: "w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition-colors"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode("div", {
                  class: ["bg-gradient-to-r", action.color, "p-2.5 rounded-xl text-white"]
                }, [
                  createVNode(unref(Icon), {
                    icon: action.icon,
                    class: "w-5 h-5"
                  }, null, 8, ["icon"])
                ], 2),
                createVNode("div", { class: "flex-1" }, [
                  createVNode("p", { class: "font-medium text-gray-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" }, toDisplayString(action.title), 1),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, toDisplayString(action.description), 1)
                ]),
                createVNode(unref(Icon), {
                  icon: "fluent:chevron-right-24-regular",
                  class: "w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition-colors"
                })
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div></div><div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold text-gray-800 dark:text-white">\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/activities",
        class: "text-sm text-indigo-600 hover:text-indigo-700 font-medium"
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
      _push(`</div><div class="space-y-4"><!--[-->`);
      ssrRenderList(recentActivities.value, (activity) => {
        _push(`<div class="flex items-start gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"><div class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: activity.icon,
          class: [activity.color, "w-5 h-5"]
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0"><p class="text-sm text-gray-800 dark:text-gray-200"><span class="font-medium">${ssrInterpolate(activity.user)}</span><span class="text-gray-500">${ssrInterpolate(activity.action)}</span><span class="font-medium text-indigo-600 dark:text-indigo-400">${ssrInterpolate(activity.target)}</span></p><p class="text-xs text-gray-400 mt-1">${ssrInterpolate(activity.time)}</p></div></div>`);
      });
      _push(`<!--]--></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold text-gray-800 dark:text-white">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E22\u0E2D\u0E14\u0E19\u0E34\u0E22\u0E21</h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/courses",
        class: "text-sm text-indigo-600 hover:text-indigo-700 font-medium"
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
      _push(`</div><div class="overflow-x-auto"><table class="w-full"><thead><tr class="text-left border-b border-gray-100 dark:border-gray-700"><th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400">\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A</th><th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400">\u0E0A\u0E37\u0E48\u0E2D\u0E04\u0E2D\u0E23\u0E4C\u0E2A</th><th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400 text-right">\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19</th><th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400 text-right">\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49</th></tr></thead><tbody><!--[-->`);
      ssrRenderList(topCourses.value, (course, index) => {
        _push(`<tr class="border-b border-gray-50 dark:border-gray-700/50 last:border-0"><td class="py-3"><span class="${ssrRenderClass([
          "inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold",
          index === 0 ? "bg-yellow-100 text-yellow-700" : index === 1 ? "bg-gray-100 text-gray-700" : index === 2 ? "bg-orange-100 text-orange-700" : "bg-gray-50 text-gray-500"
        ])}">${ssrInterpolate(index + 1)}</span></td><td class="py-3"><p class="font-medium text-gray-800 dark:text-white">${ssrInterpolate(course.name)}</p></td><td class="py-3 text-right"><span class="text-gray-600 dark:text-gray-300">${ssrInterpolate(course.enrollments.toLocaleString())}</span></td><td class="py-3 text-right"><span class="font-medium text-green-600">\u0E3F${ssrInterpolate(course.revenue.toLocaleString())}</span></td></tr>`);
      });
      _push(`<!--]--></tbody></table></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-CKB9WHKe.mjs.map
