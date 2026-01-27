import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, createBlock, createCommentVNode, toDisplayString, openBlock, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrRenderStyle, ssrInterpolate } from 'vue/server-renderer';
import { storeToRefs } from 'pinia';
import { Icon } from '@iconify/vue';
import { i as useApi, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
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
    useApi();
    const config = useRuntimeConfig();
    const { user } = storeToRefs(useAuthStore());
    const allAcademies = ref([]);
    const myAcademies = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref("");
    const currentView = ref("all");
    ref(1);
    ref(false);
    const filteredAcademies = computed(() => {
      const list = currentView.value === "all" ? allAcademies.value : myAcademies.value;
      if (!searchQuery.value.trim()) return list;
      const query = searchQuery.value.toLowerCase();
      return list.filter(
        (academy) => {
          var _a, _b, _c;
          return ((_a = academy.name) == null ? void 0 : _a.toLowerCase().includes(query)) || ((_b = academy.slogan) == null ? void 0 : _b.toLowerCase().includes(query)) || ((_c = academy.address) == null ? void 0 : _c.toLowerCase().includes(query));
        }
      );
    });
    const getLogoUrl = (academy) => {
      if (!academy.logo) {
        return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`;
      }
      if (academy.logo.startsWith("http")) {
        return academy.logo;
      }
      return academy.logo;
    };
    const getAcademyTypeInfo = (type) => {
      const typeMap = {
        "public": { label: "\u0E23\u0E31\u0E10\u0E1A\u0E32\u0E25", icon: "fluent:building-government-24-regular", color: "text-blue-600", bg: "bg-blue-100 dark:bg-blue-900/30" },
        "private": { label: "\u0E40\u0E2D\u0E01\u0E0A\u0E19", icon: "fluent:building-bank-24-regular", color: "text-purple-600", bg: "bg-purple-100 dark:bg-purple-900/30" },
        "foundation": { label: "\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34", icon: "fluent:heart-24-regular", color: "text-pink-600", bg: "bg-pink-100 dark:bg-pink-900/30" },
        "international": { label: "\u0E19\u0E32\u0E19\u0E32\u0E0A\u0E32\u0E15\u0E34", icon: "fluent:globe-24-regular", color: "text-green-600", bg: "bg-green-100 dark:bg-green-900/30" }
      };
      return typeMap[type || ""] || { label: "\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B", icon: "fluent:building-24-regular", color: "text-gray-600", bg: "bg-gray-100 dark:bg-gray-700" };
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-200 dark:bg-vikinger-dark-300" }, _attrs))}><div class="max-w-7xl mx-auto px-4 py-6"><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-6 mb-6"><div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:building-multiple-24-regular",
        class: "w-5 h-5 text-white"
      }, null, _parent));
      _push(`</div> \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </h1><p class="text-gray-600 dark:text-gray-400 mt-1">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E2A\u0E19\u0E43\u0E08</p></div><div class="relative w-full md:w-80">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19..." class="w-full pl-10 pr-4 py-2.5 bg-gray-100 dark:bg-vikinger-dark-100 border-0 rounded-lg focus:ring-2 focus:ring-vikinger-purple text-gray-900 dark:text-white"></div></div><div class="flex gap-2 mt-6"><button class="${ssrRenderClass([
        "px-4 py-2 rounded-lg text-sm font-medium transition-colors",
        currentView.value === "all" ? "bg-vikinger-purple text-white" : "bg-gray-100 dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-vikinger-dark-300"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:building-multiple-24-regular",
        class: "w-4 h-4 inline mr-1.5"
      }, null, _parent));
      _push(` \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button><button class="${ssrRenderClass([
        "px-4 py-2 rounded-lg text-sm font-medium transition-colors",
        currentView.value === "my" ? "bg-vikinger-purple text-white" : "bg-gray-100 dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-vikinger-dark-300"
      ])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:building-24-regular",
        class: "w-4 h-4 inline mr-1.5"
      }, null, _parent));
      _push(` \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14 </button></div></div>`);
      if (isLoading.value) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 animate-pulse"><div class="flex items-start gap-4"><div class="w-16 h-16 rounded-xl bg-gray-200 dark:bg-gray-700"></div><div class="flex-1 space-y-3"><div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (filteredAcademies.value.length > 0) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"><!--[-->`);
        ssrRenderList(filteredAcademies.value, (academy) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: academy.id,
            to: `/academies/${encodeURIComponent(academy.name)}`,
            class: "bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm hover:shadow-lg transition-all group overflow-hidden"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="h-24 bg-gray-200 dark:bg-gray-700 bg-cover bg-center" style="${ssrRenderStyle({ backgroundImage: academy.cover ? `url(${academy.cover})` : "none" })}"${_scopeId}><div class="w-full h-full bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/30"${_scopeId}></div></div><div class="p-5 -mt-8 relative"${_scopeId}><div class="w-14 h-14 rounded-xl border-2 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white mb-3"${_scopeId}><img${ssrRenderAttr("src", getLogoUrl(academy))}${ssrRenderAttr("alt", academy.name)} class="w-full h-full object-cover"${_scopeId}></div><h3 class="font-bold text-gray-900 dark:text-white group-hover:text-vikinger-purple transition-colors line-clamp-1 mb-1"${_scopeId}>${ssrInterpolate(academy.name)}</h3>`);
                if (academy.slogan) {
                  _push2(`<p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3"${_scopeId}>${ssrInterpolate(academy.slogan)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400"${_scopeId}><span class="${ssrRenderClass(["inline-flex items-center gap-1 px-2 py-1 rounded-full", getAcademyTypeInfo(academy.type).bg, getAcademyTypeInfo(academy.type).color])}"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: getAcademyTypeInfo(academy.type).icon,
                  class: "w-3.5 h-3.5"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(getAcademyTypeInfo(academy.type).label)}</span><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:people-24-regular",
                  class: "w-3.5 h-3.5"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(academy.total_students || 0)}</span></div></div>`);
              } else {
                return [
                  createVNode("div", {
                    class: "h-24 bg-gray-200 dark:bg-gray-700 bg-cover bg-center",
                    style: { backgroundImage: academy.cover ? `url(${academy.cover})` : "none" }
                  }, [
                    createVNode("div", { class: "w-full h-full bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/30" })
                  ], 4),
                  createVNode("div", { class: "p-5 -mt-8 relative" }, [
                    createVNode("div", { class: "w-14 h-14 rounded-xl border-2 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white mb-3" }, [
                      createVNode("img", {
                        src: getLogoUrl(academy),
                        alt: academy.name,
                        class: "w-full h-full object-cover"
                      }, null, 8, ["src", "alt"])
                    ]),
                    createVNode("h3", { class: "font-bold text-gray-900 dark:text-white group-hover:text-vikinger-purple transition-colors line-clamp-1 mb-1" }, toDisplayString(academy.name), 1),
                    academy.slogan ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3"
                    }, toDisplayString(academy.slogan), 1)) : createCommentVNode("", true),
                    createVNode("div", { class: "flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400" }, [
                      createVNode("span", {
                        class: ["inline-flex items-center gap-1 px-2 py-1 rounded-full", getAcademyTypeInfo(academy.type).bg, getAcademyTypeInfo(academy.type).color]
                      }, [
                        createVNode(unref(Icon), {
                          icon: getAcademyTypeInfo(academy.type).icon,
                          class: "w-3.5 h-3.5"
                        }, null, 8, ["icon"]),
                        createTextVNode(" " + toDisplayString(getAcademyTypeInfo(academy.type).label), 1)
                      ], 2),
                      createVNode("span", { class: "flex items-center gap-1" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:people-24-regular",
                          class: "w-3.5 h-3.5"
                        }),
                        createTextVNode(" " + toDisplayString(academy.total_students || 0), 1)
                      ])
                    ])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:building-multiple-24-regular",
          class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">${ssrInterpolate(currentView.value === "my" ? "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19" : "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19")}</h3><p class="text-gray-600 dark:text-gray-400">${ssrInterpolate(currentView.value === "my" ? "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49" : "\u0E25\u0E2D\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E04\u0E33\u0E04\u0E49\u0E19\u0E2D\u0E37\u0E48\u0E19")}</p></div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/academies/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-3C5Qrglj.mjs.map
