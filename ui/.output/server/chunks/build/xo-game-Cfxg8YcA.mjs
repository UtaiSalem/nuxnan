import { ref, watch, resolveComponent, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrRenderComponent } from 'vue/server-renderer';
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
import 'pinia';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "xo-game",
  __ssrInlineRender: true,
  setup(__props) {
    const board = ref(Array(9).fill(""));
    const currentPlayer = ref("X");
    const scores = ref({ X: 0, O: 0 });
    const gameMode = ref("single");
    ref(false);
    ref(null);
    const winner = ref(null);
    const resetBoard = () => {
      board.value = Array(9).fill("");
      currentPlayer.value = "X";
      winner.value = null;
    };
    const resetGame = () => {
      resetBoard();
      scores.value = { X: 0, O: 0 };
    };
    watch(gameMode, () => {
      resetGame();
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Icon = resolveComponent("Icon");
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-[calc(100vh-100px)] py-6 flex flex-col justify-center sm:py-12" }, _attrs))} data-v-1e23f0f8><div class="relative py-3 sm:max-w-xl sm:mx-auto w-full px-4" data-v-1e23f0f8><div class="absolute inset-0 bg-gradient-to-r from-cyan-400 to-light-blue-500 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl opacity-75" data-v-1e23f0f8></div><div class="relative px-4 py-10 bg-white dark:bg-vikinger-dark-100 shadow-lg rounded-3xl sm:p-20 border border-gray-100 dark:border-vikinger-dark-50" data-v-1e23f0f8><h1 class="text-4xl font-bold mb-8 text-center bg-gradient-to-r from-cyan-500 to-blue-500 bg-clip-text text-transparent" data-v-1e23f0f8>\u0E40\u0E01\u0E21 XO</h1><div class="mb-8 flex justify-center" data-v-1e23f0f8><div class="relative inline-flex items-center space-x-4 bg-gray-100 dark:bg-vikinger-dark-200 p-2 rounded-xl" data-v-1e23f0f8><span class="text-gray-600 dark:text-gray-300 font-medium pl-2" data-v-1e23f0f8>\u0E42\u0E2B\u0E21\u0E14:</span><select class="bg-white dark:bg-vikinger-dark-100 border border-gray-200 dark:border-vikinger-dark-50 text-gray-700 dark:text-gray-200 py-1.5 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm" data-v-1e23f0f8><option value="single" data-v-1e23f0f8${ssrIncludeBooleanAttr(Array.isArray(gameMode.value) ? ssrLooseContain(gameMode.value, "single") : ssrLooseEqual(gameMode.value, "single")) ? " selected" : ""}>\u0E40\u0E25\u0E48\u0E19\u0E04\u0E19\u0E40\u0E14\u0E35\u0E22\u0E27 (vs AI)</option><option value="multi" data-v-1e23f0f8${ssrIncludeBooleanAttr(Array.isArray(gameMode.value) ? ssrLooseContain(gameMode.value, "multi") : ssrLooseEqual(gameMode.value, "multi")) ? " selected" : ""}>\u0E40\u0E25\u0E48\u0E19\u0E2A\u0E2D\u0E07\u0E04\u0E19</option></select></div></div><div class="flex justify-between items-center mb-8 px-4 bg-gray-50 dark:bg-vikinger-dark-200 py-4 rounded-2xl" data-v-1e23f0f8><div class="text-center" data-v-1e23f0f8><p class="text-sm text-gray-500 dark:text-gray-400" data-v-1e23f0f8>\u0E1C\u0E39\u0E49\u0E40\u0E25\u0E48\u0E19 X</p><p class="text-3xl font-bold text-cyan-600" data-v-1e23f0f8>${ssrInterpolate(scores.value.X)}</p></div><div class="text-center px-4" data-v-1e23f0f8>`);
      if (!winner.value) {
        _push(`<div class="text-xl font-bold text-gray-400" data-v-1e23f0f8>VS</div>`);
      } else if (winner.value === "draw") {
        _push(`<div class="text-lg font-bold text-orange-500 animate-bounce" data-v-1e23f0f8>\u0E40\u0E2A\u0E21\u0E2D!</div>`);
      } else {
        _push(`<div class="text-lg font-bold text-green-500 animate-bounce" data-v-1e23f0f8>\u0E1C\u0E39\u0E49\u0E0A\u0E19\u0E30: ${ssrInterpolate(winner.value)}</div>`);
      }
      _push(`</div><div class="text-center" data-v-1e23f0f8><p class="text-sm text-gray-500 dark:text-gray-400" data-v-1e23f0f8>\u0E1C\u0E39\u0E49\u0E40\u0E25\u0E48\u0E19 O</p><p class="text-3xl font-bold text-pink-500" data-v-1e23f0f8>${ssrInterpolate(scores.value.O)}</p></div></div><div class="grid grid-cols-3 gap-3 mb-8 mx-auto max-w-[300px]" data-v-1e23f0f8><!--[-->`);
      ssrRenderList(board.value, (cell, index) => {
        _push(`<button${ssrIncludeBooleanAttr(cell !== "" || !!winner.value) ? " disabled" : ""} class="${ssrRenderClass([{
          "text-cyan-500": cell === "X",
          "text-pink-500": cell === "O"
        }, "w-24 h-24 bg-gray-100 dark:bg-vikinger-dark-200 text-5xl font-black flex items-center justify-center rounded-2xl shadow-sm hover:shadow-md hover:bg-gray-200 dark:hover:bg-vikinger-dark-300 transition-all duration-200 disabled:cursor-not-allowed"])}" data-v-1e23f0f8>`);
        if (cell) {
          _push(`<span data-v-1e23f0f8>${ssrInterpolate(cell)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      });
      _push(`<!--]--></div>`);
      if (!winner.value) {
        _push(`<div class="text-center mb-6" data-v-1e23f0f8><p class="text-lg font-semibold text-gray-600 dark:text-gray-300" data-v-1e23f0f8> \u0E15\u0E32\u0E02\u0E2D\u0E07: <span class="${ssrRenderClass([currentPlayer.value === "X" ? "text-cyan-500" : "text-pink-500", "text-2xl font-bold"])}" data-v-1e23f0f8>${ssrInterpolate(currentPlayer.value)}</span></p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="w-full bg-gradient-to-r from-gray-700 to-gray-900 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200 flex items-center justify-center space-x-2" data-v-1e23f0f8>`);
      _push(ssrRenderComponent(_component_Icon, {
        name: "fluent:arrow-counterclockwise-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span data-v-1e23f0f8>\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E01\u0E21\u0E43\u0E2B\u0E21\u0E48</span></button></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/xo-game.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const xoGame = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-1e23f0f8"]]);

export { xoGame as default };
//# sourceMappingURL=xo-game-Cfxg8YcA.mjs.map
