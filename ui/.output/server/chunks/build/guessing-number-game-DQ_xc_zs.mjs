import { ref, watch, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import confetti from 'canvas-confetti';
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
  __name: "guessing-number-game",
  __ssrInlineRender: true,
  setup(__props) {
    ref(generateRandomNumber());
    const guess = ref(null);
    const message = ref("");
    const isGameOver = ref(false);
    const attempts = ref(0);
    const score = ref(0);
    function generateRandomNumber() {
      return Math.floor(Math.random() * 100) + 1;
    }
    const showConfetti = () => {
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
      });
    };
    watch(isGameOver, (newValue) => {
      if (newValue) {
        showConfetti();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full h-full flex items-center justify-center min-h-[50vh]" }, _attrs))} data-v-c705a727><div class="bg-white dark:bg-vikinger-dark-100 rounded-2xl shadow-xl max-w-md w-full border border-gray-100 dark:border-vikinger-dark-50 overflow-hidden" data-v-c705a727><div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center" data-v-c705a727><h1 class="text-3xl font-bold text-white mb-2" data-v-c705a727>\u0E40\u0E01\u0E21\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02</h1><p class="text-blue-100" data-v-c705a727>\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07 1-100</p></div><div class="px-8 py-8" data-v-c705a727><div class="text-center mb-6 p-4 bg-gray-50 dark:bg-vikinger-dark-200 rounded-xl" data-v-c705a727><p class="text-gray-600 dark:text-gray-300" data-v-c705a727> \u0E04\u0E38\u0E13\u0E17\u0E32\u0E22 <span class="font-bold text-blue-600 dark:text-blue-400 text-xl mx-1" data-v-c705a727>${ssrInterpolate(attempts.value)}</span> \u0E04\u0E23\u0E31\u0E49\u0E07 </p><p class="text-gray-500 dark:text-gray-400 text-sm mt-1" data-v-c705a727> \u0E04\u0E30\u0E41\u0E19\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19: <span class="font-bold text-green-500" data-v-c705a727>${ssrInterpolate(score.value)}</span></p></div>`);
      if (message.value) {
        _push(`<div class="${ssrRenderClass([{
          "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400": message.value.includes("\u0E22\u0E34\u0E19\u0E14\u0E35\u0E14\u0E49\u0E27\u0E22"),
          "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400": message.value.includes("\u0E19\u0E49\u0E2D\u0E22\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B") || message.value.includes("\u0E21\u0E32\u0E01\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B")
        }, "mb-6 p-4 rounded-lg text-center font-bold text-lg animate-fade-in"])}" data-v-c705a727>${ssrInterpolate(message.value)}</div>`);
      } else {
        _push(`<div class="h-[60px]" data-v-c705a727></div>`);
      }
      _push(`<div class="mb-6 relative" data-v-c705a727><input${ssrRenderAttr("value", guess.value)} type="text" inputmode="numeric" pattern="[0-9]*"${ssrIncludeBooleanAttr(isGameOver.value) ? " disabled" : ""} class="w-full px-4 py-3 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-300 dark:border-vikinger-dark-50 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-center text-2xl font-bold tracking-widest text-gray-800 dark:text-gray-100 disabled:opacity-50 transition-all" placeholder="???" data-v-c705a727></div><div class="flex space-x-3" data-v-c705a727><button${ssrIncludeBooleanAttr(isGameOver.value || !guess.value) ? " disabled" : ""} class="flex-1 bg-gradient-to-t from-blue-600 to-blue-500 text-white py-3 px-4 rounded-xl hover:from-blue-700 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/30 transition-all transform active:scale-95 font-semibold" data-v-c705a727> \u0E17\u0E32\u0E22\u0E40\u0E25\u0E22 </button><button class="flex-1 bg-gray-100 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-xl hover:bg-gray-200 dark:hover:bg-vikinger-dark-300 focus:outline-none focus:ring-2 focus:ring-gray-300 font-semibold transition-all" data-v-c705a727> \u0E40\u0E25\u0E48\u0E19\u0E43\u0E2B\u0E21\u0E48 </button></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/guessing-number-game.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const guessingNumberGame = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-c705a727"]]);

export { guessingNumberGame as default };
//# sourceMappingURL=guessing-number-game-DQ_xc_zs.mjs.map
