import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
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
import './server.mjs';
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
    const contents = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref("");
    const selectedType = ref("all");
    const types = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "post", label: "\u0E42\u0E1E\u0E2A\u0E15\u0E4C" },
      { value: "article", label: "\u0E1A\u0E17\u0E04\u0E27\u0E32\u0E21" },
      { value: "video", label: "\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D" },
      { value: "announcement", label: "\u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28" }
    ];
    const getTypeBadge = (type) => {
      const badges = {
        post: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
        article: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        video: "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400",
        announcement: "bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400"
      };
      return badges[type] || "bg-gray-100 text-gray-700";
    };
    const getTypeLabel = (type) => {
      const labels = { post: "\u0E42\u0E1E\u0E2A\u0E15\u0E4C", article: "\u0E1A\u0E17\u0E04\u0E27\u0E32\u0E21", video: "\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D", announcement: "\u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28" };
      return labels[type] || type;
    };
    const getStatusBadge = (status) => {
      const badges = {
        published: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        draft: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"
      };
      return badges[status] || badges.draft;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E42\u0E1E\u0E2A\u0E15\u0E4C \u0E1A\u0E17\u0E04\u0E27\u0E32\u0E21 \u0E41\u0E25\u0E30\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E15\u0E48\u0E32\u0E07\u0E46</p></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/content/create",
        class: "inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E43\u0E2B\u0E21\u0E48 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:add-24-regular",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E43\u0E2B\u0E21\u0E48 ")
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
      _push(`<input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"></div><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><!--[-->`);
      ssrRenderList(types, (type) => {
        _push(`<option${ssrRenderAttr("value", type.value)}${ssrIncludeBooleanAttr(Array.isArray(selectedType.value) ? ssrLooseContain(selectedType.value, type.value) : ssrLooseEqual(selectedType.value, type.value)) ? " selected" : ""}>${ssrInterpolate(type.label)}</option>`);
      });
      _push(`<!--]--></select></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">`);
      if (isLoading.value) {
        _push(`<div class="p-8 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else {
        _push(`<div class="overflow-x-auto"><table class="w-full"><thead class="bg-gray-50 dark:bg-gray-700/50"><tr><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D</th><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E35\u0E22\u0E19</th><th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">\u0E22\u0E2D\u0E14\u0E14\u0E39</th><th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase">\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</th><th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(contents.value, (content) => {
          _push(`<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30"><td class="px-6 py-4"><p class="font-medium text-gray-800 dark:text-white line-clamp-1">${ssrInterpolate(content.title)}</p></td><td class="px-6 py-4"><span class="${ssrRenderClass([getTypeBadge(content.type), "px-2.5 py-1 rounded-full text-xs font-medium"])}">${ssrInterpolate(getTypeLabel(content.type))}</span></td><td class="px-6 py-4 text-gray-600 dark:text-gray-300">${ssrInterpolate(content.author)}</td><td class="px-6 py-4 text-right text-gray-600 dark:text-gray-300">${ssrInterpolate(content.views.toLocaleString())}</td><td class="px-6 py-4 text-center"><span class="${ssrRenderClass([getStatusBadge(content.status), "px-2.5 py-1 rounded-full text-xs font-medium"])}">${ssrInterpolate(content.status === "published" ? "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48" : "\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07")}</span></td><td class="px-6 py-4 text-right text-gray-500">${ssrInterpolate(content.date)}</td><td class="px-6 py-4 text-right"><div class="flex items-center justify-end gap-2"><button class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:eye-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button><button class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button><button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/content/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-CVYWXuRp.mjs.map
