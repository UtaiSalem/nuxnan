import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
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
    useHead({
      title: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - Admin"
    });
    const isNavigating = ref(false);
    const activeTab = ref(0);
    const mattayomLevels = [
      { id: 0, name: "\u0E21.1", rooms: 11, color: "purple" },
      { id: 1, name: "\u0E21.2", rooms: 9, color: "purple" },
      { id: 2, name: "\u0E21.3", rooms: 9, color: "purple" },
      { id: 3, name: "\u0E21.4", rooms: 8, color: "purple" },
      { id: 4, name: "\u0E21.5", rooms: 7, color: "purple" },
      { id: 5, name: "\u0E21.6", rooms: 7, color: "purple" }
    ];
    const currentLevel = ref(mattayomLevels[0]);
    const getClassrooms = (levelId) => {
      const level = mattayomLevels[levelId];
      const rooms = [];
      for (let i = 1; i <= level.rooms; i++) {
        rooms.push({
          id: i,
          name: `${i}`,
          fullName: `${level.name}/${i}`,
          link: `/student-card/admin/students/${levelId + 1}/${i}`,
          levelId: level.id
        });
      }
      return rooms;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-purple-100 via-white to-purple-200 relative overflow-x-hidden" }, _attrs))}><div class="absolute inset-0 overflow-hidden pointer-events-none"><div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-200 rounded-full opacity-30 blur-3xl"></div><div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-300 rounded-full opacity-20 blur-3xl"></div></div>`);
      if (isNavigating.value) {
        _push(`<div class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center"><div class="text-center"><div class="w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div><p class="text-purple-600 font-medium">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="relative z-10 p-4 sm:p-6 lg:p-8"><div class="max-w-5xl mx-auto mb-8"><div class="flex items-center gap-4 mb-6">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student-card",
        class: "p-2 bg-white/80 backdrop-blur rounded-lg shadow-sm hover:shadow-md transition-all"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:arrow-left",
              class: "w-6 h-6 text-gray-600"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "heroicons:arrow-left",
                class: "w-6 h-6 text-gray-600"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div><span class="inline-flex items-center gap-2 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:shield-check",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` Admin Mode </span></div></div><div class="text-center"><div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg mb-4">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:cog-6-tooth",
        class: "w-8 h-8 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h1><p class="text-gray-600">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19\u0E41\u0E25\u0E30\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><div class="max-w-5xl mx-auto mb-8"><div class="grid grid-cols-2 sm:grid-cols-4 gap-4"><div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:camera",
        class: "w-8 h-8 text-purple-500 mx-auto mb-2"
      }, null, _parent));
      _push(`<p class="text-sm text-gray-600">\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B</p></div><div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:pencil-square",
        class: "w-8 h-8 text-purple-500 mx-auto mb-2"
      }, null, _parent));
      _push(`<p class="text-sm text-gray-600">\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</p></div><div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:qr-code",
        class: "w-8 h-8 text-purple-500 mx-auto mb-2"
      }, null, _parent));
      _push(`<p class="text-sm text-gray-600">QR Code</p></div><div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:printer",
        class: "w-8 h-8 text-purple-500 mx-auto mb-2"
      }, null, _parent));
      _push(`<p class="text-sm text-gray-600">\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E1A\u0E31\u0E15\u0E23</p></div></div></div><div class="max-w-5xl mx-auto mb-6"><div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-2"><div class="flex flex-wrap justify-center gap-2"><!--[-->`);
      ssrRenderList(mattayomLevels, (level) => {
        _push(`<button class="${ssrRenderClass([
          "px-6 py-3 rounded-xl font-semibold transition-all duration-200",
          activeTab.value === level.id ? "bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-md" : "bg-gray-100 text-gray-700 hover:bg-gray-200"
        ])}">${ssrInterpolate(level.name)}</button>`);
      });
      _push(`<!--]--></div></div></div><div class="max-w-5xl mx-auto"><div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-6"><h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2"><span class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:building-office-2",
        class: "w-5 h-5 text-purple-600"
      }, null, _parent));
      _push(`</span> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 - ${ssrInterpolate(currentLevel.value.name)}</h2><div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3"><!--[-->`);
      ssrRenderList(getClassrooms(activeTab.value), (room) => {
        _push(`<button class="group relative bg-gradient-to-br from-purple-50 to-white border-2 border-purple-100 rounded-xl p-4 hover:border-purple-400 hover:shadow-lg transition-all duration-200"><div class="text-center"><span class="block text-2xl font-bold text-purple-600 group-hover:text-purple-700">${ssrInterpolate(room.name)}</span><span class="text-xs text-gray-500">${ssrInterpolate(room.fullName)}</span></div><div class="absolute top-2 right-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:cog-6-tooth",
          class: "w-4 h-4 text-purple-300 group-hover:text-purple-500"
        }, null, _parent));
        _push(`</div></button>`);
      });
      _push(`<!--]--></div></div></div><div class="max-w-5xl mx-auto mt-8 text-center text-gray-500 text-sm"><p>\xA9 ${ssrInterpolate((/* @__PURE__ */ new Date()).getFullYear())} \u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E2D\u0E21\u0E2A\u0E38\u0E23\u0E32\u0E07\u0E04\u0E4C\u0E2D\u0E38\u0E1B\u0E16\u0E31\u0E21\u0E20\u0E4C</p></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student-card/admin/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-Bjz0G4gS.mjs.map
