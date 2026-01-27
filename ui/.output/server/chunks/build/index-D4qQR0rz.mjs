import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, createBlock, createTextVNode, openBlock, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
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
import '@iconify/vue';
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
      title: "\u0E23\u0E30\u0E1A\u0E1A\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"
    });
    const isNavigating = ref(false);
    const activeTab = ref(0);
    const mattayomLevels = [
      { id: 0, name: "\u0E21.1", rooms: 11, color: "blue" },
      { id: 1, name: "\u0E21.2", rooms: 9, color: "blue" },
      { id: 2, name: "\u0E21.3", rooms: 9, color: "blue" },
      { id: 3, name: "\u0E21.4", rooms: 8, color: "blue" },
      { id: 4, name: "\u0E21.5", rooms: 7, color: "blue" },
      { id: 5, name: "\u0E21.6", rooms: 7, color: "blue" }
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
          link: `/student-card/${levelId + 1}/${i}`,
          levelId: level.id
        });
      }
      return rooms;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-blue-100 via-white to-blue-200 relative overflow-x-hidden" }, _attrs))}><div class="absolute inset-0 overflow-hidden pointer-events-none"><div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-200 rounded-full opacity-30 blur-3xl"></div><div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-300 rounded-full opacity-20 blur-3xl"></div></div>`);
      if (isNavigating.value) {
        _push(`<div class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center"><div class="text-center"><div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div><p class="text-blue-600 font-medium">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="relative z-10 p-4 sm:p-6 lg:p-8"><div class="max-w-5xl mx-auto mb-8"><div class="text-center"><div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg mb-4"><svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg></div><h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">\u0E23\u0E30\u0E1A\u0E1A\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h1><p class="text-gray-600">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19\u0E41\u0E25\u0E30\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E14\u0E39\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><div class="max-w-5xl mx-auto mb-6"><div class="flex flex-wrap justify-center gap-3">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student-card/admin",
        class: "inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur rounded-lg shadow-sm hover:shadow-md transition-all text-gray-700 hover:text-blue-600"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"${_scopeId}><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"${_scopeId}></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"${_scopeId}></path></svg> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 (Admin) `);
          } else {
            return [
              (openBlock(), createBlock("svg", {
                class: "w-5 h-5",
                fill: "none",
                stroke: "currentColor",
                viewBox: "0 0 24 24"
              }, [
                createVNode("path", {
                  "stroke-linecap": "round",
                  "stroke-linejoin": "round",
                  "stroke-width": "2",
                  d: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                }),
                createVNode("path", {
                  "stroke-linecap": "round",
                  "stroke-linejoin": "round",
                  "stroke-width": "2",
                  d: "M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                })
              ])),
              createTextVNode(" \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 (Admin) ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="max-w-5xl mx-auto mb-6"><div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-2"><div class="flex flex-wrap justify-center gap-2"><!--[-->`);
      ssrRenderList(mattayomLevels, (level) => {
        _push(`<button class="${ssrRenderClass([
          "px-6 py-3 rounded-xl font-semibold transition-all duration-200",
          activeTab.value === level.id ? "bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md" : "bg-gray-100 text-gray-700 hover:bg-gray-200"
        ])}">${ssrInterpolate(level.name)}</button>`);
      });
      _push(`<!--]--></div></div></div><div class="max-w-5xl mx-auto"><div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-6"><h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2"><span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center"><svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg></span> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 - ${ssrInterpolate(currentLevel.value.name)}</h2><div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3"><!--[-->`);
      ssrRenderList(getClassrooms(activeTab.value), (room) => {
        _push(`<button class="group relative bg-gradient-to-br from-blue-50 to-white border-2 border-blue-100 rounded-xl p-4 hover:border-blue-400 hover:shadow-lg transition-all duration-200"><div class="text-center"><span class="block text-2xl font-bold text-blue-600 group-hover:text-blue-700">${ssrInterpolate(room.name)}</span><span class="text-xs text-gray-500">${ssrInterpolate(room.fullName)}</span></div><div class="absolute inset-0 bg-blue-500/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity"></div></button>`);
      });
      _push(`<!--]--></div></div></div><div class="max-w-5xl mx-auto mt-8 text-center text-gray-500 text-sm"><p>\xA9 ${ssrInterpolate((/* @__PURE__ */ new Date()).getFullYear())} \u0E23\u0E30\u0E1A\u0E1A\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E2D\u0E21\u0E2A\u0E38\u0E23\u0E32\u0E07\u0E04\u0E4C\u0E2D\u0E38\u0E1B\u0E16\u0E31\u0E21\u0E20\u0E4C</p></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student-card/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-D4qQR0rz.mjs.map
