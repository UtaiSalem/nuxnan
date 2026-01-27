import { defineComponent, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderComponent, ssrRenderClass } from 'vue/server-renderer';
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
  __name: "activities",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    const activities = ref([]);
    const isLoading = ref(true);
    const currentPage = ref(1);
    const totalPages = ref(1);
    ref(20);
    const totalActivities = ref(0);
    const selectedType = ref("all");
    const activityTypes = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "login", label: "\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A" },
      { value: "logout", label: "\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A" },
      { value: "register", label: "\u0E25\u0E07\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19" },
      { value: "purchase", label: "\u0E0B\u0E37\u0E49\u0E2D\u0E04\u0E2D\u0E23\u0E4C\u0E2A" },
      { value: "complete", label: "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A" },
      { value: "review", label: "\u0E23\u0E35\u0E27\u0E34\u0E27" },
      { value: "profile_update", label: "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C" }
    ];
    const getActivityIcon = (type) => {
      const icons = {
        login: "fluent:sign-out-24-regular",
        logout: "fluent:door-arrow-right-24-regular",
        register: "fluent:person-add-24-regular",
        purchase: "fluent:cart-24-regular",
        complete: "fluent:checkmark-circle-24-regular",
        review: "fluent:star-24-regular",
        profile_update: "fluent:person-edit-24-regular"
      };
      return icons[type] || "fluent:activity-24-regular";
    };
    const getActivityColor = (type) => {
      const colors = {
        login: "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400",
        logout: "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400",
        register: "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400",
        purchase: "bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400",
        complete: "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400",
        review: "bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400",
        profile_update: "bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
      };
      return colors[type] || "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400";
    };
    const formatDate = (dateStr) => {
      const date = new Date(dateStr);
      return date.toLocaleString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h1><p class="text-gray-500 dark:text-gray-400 mt-1"> \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(totalActivities.value.toLocaleString())} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex flex-col sm:flex-row gap-4"><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(activityTypes, (type) => {
        _push(`<option${ssrRenderAttr("value", type.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedType.value) ? ssrLooseContain(selectedType.value, type.value) : ssrLooseEqual(selectedType.value, type.value)) ? " selected" : ""}>${ssrInterpolate(type.label)}</option>`);
      });
      _push(`<!--]--></select><button class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 transition-colors inline-flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-sync-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A </button></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">`);
      if (isLoading.value) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else if (activities.value.length === 0) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:activity-24-regular",
          class: "w-12 h-12 text-gray-300 mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</p></div>`);
      } else {
        _push(`<div class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(activities.value, (activity) => {
          var _a, _b;
          _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"><div class="flex items-start gap-4"><div class="${ssrRenderClass([getActivityColor(activity.type), "flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getActivityIcon(activity.type),
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</div><div class="flex-1 min-w-0"><div class="flex items-center gap-2"><span class="font-medium text-gray-800 dark:text-white">${ssrInterpolate(((_a = activity.user) == null ? void 0 : _a.name) || "Unknown User")}</span><span class="text-gray-500 dark:text-gray-400">-</span><span class="text-gray-600 dark:text-gray-300">${ssrInterpolate(activity.description)}</span></div><div class="flex items-center gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400"><span class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` ${ssrInterpolate(formatDate(activity.created_at))}</span>`);
          if (activity.ip_address) {
            _push(`<span class="flex items-center gap-1">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:globe-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` ${ssrInterpolate(activity.ip_address)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="flex-shrink-0"><span class="${ssrRenderClass([getActivityColor(activity.type), "px-2 py-1 text-xs rounded-lg"])}">${ssrInterpolate(((_b = activityTypes.find((t) => t.value === activity.type)) == null ? void 0 : _b.label) || activity.type)}</span></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      if (totalPages.value > 1) {
        _push(`<div class="p-4 border-t border-gray-100 dark:border-gray-700"><div class="flex justify-center gap-1"><!--[-->`);
        ssrRenderList(totalPages.value, (page) => {
          _push(`<button class="${ssrRenderClass([currentPage.value === page ? "bg-indigo-600 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "w-10 h-10 rounded-lg text-sm font-medium transition-colors"])}">${ssrInterpolate(page)}</button>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/activities.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=activities-BCXm7-DH.mjs.map
