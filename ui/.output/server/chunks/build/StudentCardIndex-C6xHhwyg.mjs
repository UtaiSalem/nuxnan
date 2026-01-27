import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderStyle, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
import { _ as _imports_1 } from './virtual_public-Zc294sLl.mjs';
import { H as Head } from './inertia-vue3-CWdJjaLG.mjs';
import { _ as _export_sfc } from './server.mjs';
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
import 'unhead/utils';
import 'pinia';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';

const _sfc_main = {
  __name: "StudentCardIndex",
  __ssrInlineRender: true,
  setup(__props) {
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
    ref(mattayomLevels[0]);
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-blue-100 via-white to-blue-200 relative overflow-x-hidden" }, _attrs))} data-v-9a285a63><div class="fixed inset-0 flex items-center justify-center pointer-events-none select-none z-0" aria-hidden="true" data-v-9a285a63><img${ssrRenderAttr("src", _imports_1)} alt="Watermark" class="opacity-10 w-[60vw] max-w-[500px] h-auto" style="${ssrRenderStyle({ "filter": "blur(0.5px)" })}" data-v-9a285a63></div><div class="flex justify-center mt-6 mb-2 relative z-10" data-v-9a285a63><img${ssrRenderAttr("src", _imports_1)} alt="Logo" class="w-20 h-20 rounded-full shadow-xl border-4 border-white animate-float" data-v-9a285a63></div>`);
      if (isNavigating.value) {
        _push(`<div class="fixed inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-50" data-v-9a285a63><div class="bg-white p-6 rounded-lg shadow-lg" data-v-9a285a63><p class="text-gray-700" data-v-9a285a63>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(unref(Head), { title: "Student Card" }, null, _parent));
      _push(`<div class="container mx-auto px-4 py-10 relative z-10" data-v-9a285a63><div class="text-center mb-8" data-v-9a285a63><h1 class="text-3xl md:text-4xl font-extrabold text-blue-700 drop-shadow-lg mb-2 tracking-wide" data-v-9a285a63> \u0E23\u0E30\u0E1A\u0E1A\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h1><p class="text-lg text-blue-500 font-medium mb-2" data-v-9a285a63>\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E04\u0E23\u0E39\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E0A\u0E31\u0E49\u0E19</p><div class="flex justify-center gap-2 mt-2" data-v-9a285a63><span class="inline-block w-2 h-2 bg-blue-700 rounded-full animate-pulse" data-v-9a285a63></span><span class="inline-block w-2 h-2 bg-blue-400 rounded-full animate-pulse delay-150" data-v-9a285a63></span><span class="inline-block w-2 h-2 bg-blue-300 rounded-full animate-pulse delay-300" data-v-9a285a63></span></div></div><div class="flex flex-wrap w-full border-b-2 border-blue-200 mb-8 justify-center min-h-[64px]" data-v-9a285a63><!--[-->`);
      ssrRenderList(mattayomLevels, (level) => {
        _push(`<button class="${ssrRenderClass([
          "relative px-6 py-4 mx-1 my-2 font-semibold rounded-t-xl transition-all duration-200 whitespace-nowrap focus:outline-none",
          activeTab.value === level.id ? "bg-gradient-to-r from-blue-500 to-blue-700 text-white shadow-lg scale-105" : "bg-white text-blue-700 hover:bg-blue-100 border-2 border-blue-300 shadow-xl hover:scale-105 hover:shadow-lg"
        ])}" style="${ssrRenderStyle({ "min-width": "90px", "min-height": "56px" })}" data-v-9a285a63><span class="text-xl mr-2" data-v-9a285a63>\u{1F3EB}</span> ${ssrInterpolate(level.name)} `);
        if (activeTab.value === level.id) {
          _push(`<span class="absolute left-1/2 -bottom-2 transform -translate-x-1/2 w-8 h-1 bg-blue-600 rounded-full" data-v-9a285a63></span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      });
      _push(`<!--]--></div><div class="tab-content" data-v-9a285a63><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-8" data-v-9a285a63><!--[-->`);
      ssrRenderList(getClassrooms(activeTab.value), (room) => {
        _push(`<div class="group bg-blue-200 shadow-xl rounded-2xl p-6 cursor-pointer hover:scale-105 hover:shadow-2xl transition-all duration-200 text-center border-2 border-blue-400 hover:border-blue-400 relative overflow-hidden" data-v-9a285a63><div class="absolute top-5 left-0 opacity-10 text-5xl pointer-events-none select-none" data-v-9a285a63>\u{1F3EB}</div><div class="flex flex-col items-center justify-center z-10 relative" data-v-9a285a63><span class="text-3xl font-bold text-blue-700 drop-shadow-sm mb-2 group-hover:text-blue-900 transition" data-v-9a285a63>${ssrInterpolate(room.fullName)}</span><span class="text-xs text-blue-400 font-semibold tracking-widest uppercase" data-v-9a285a63>Classroom</span></div><div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-10 h-1 bg-gradient-to-r from-blue-300 to-blue-500 rounded-full opacity-70" data-v-9a285a63></div></div>`);
      });
      _push(`<!--]--></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/Card/StudentCardIndex.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentCardIndex = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-9a285a63"]]);

export { StudentCardIndex as default };
//# sourceMappingURL=StudentCardIndex-C6xHhwyg.mjs.map
