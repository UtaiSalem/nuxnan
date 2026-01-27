import { ref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderStyle, ssrRenderList } from 'vue/server-renderer';
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
  __name: "MentalMatch",
  __ssrInlineRender: true,
  setup(__props) {
    const cards = ref([]);
    ref([]);
    const matchedPairs = ref(0);
    const moves = ref(0);
    const isGameActive = ref(false);
    const gameDifficulty = ref("easy");
    const emojis = {
      easy: ["\u{1F34E}", "\u{1F3B8}", "\u{1F3BA}", "\u{1F3BB}", "\u{1F3A8}", "\u{1F3AD}"],
      medium: ["\u{1F34E}", "\u{1F3B8}", "\u{1F3BA}", "\u{1F3BB}", "\u{1F3A8}", "\u{1F3AD}", "\u{1F3C0}", "\u{1F3C8}"],
      hard: ["\u{1F34E}", "\u{1F3B8}", "\u{1F3BA}", "\u{1F3BB}", "\u{1F3A8}", "\u{1F3AD}", "\u{1F3C0}", "\u{1F3C8}", "\u26BD", "\u{1F3C0}"]
    };
    const difficultySettings = {
      easy: { pairs: 6, gridCols: 3 },
      medium: { pairs: 8, gridCols: 4 },
      hard: { pairs: 10, gridCols: 5 }
    };
    computed(() => emojis[gameDifficulty.value]);
    const currentSettings = computed(() => difficultySettings[gameDifficulty.value]);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-4" }, _attrs))} data-v-f15e69df><div class="max-w-4xl mx-auto" data-v-f15e69df><div class="text-center mb-8" data-v-f15e69df><h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg" data-v-f15e69df>Mental Match Game</h1><p class="text-white text-lg drop-shadow" data-v-f15e69df>Find all matching pairs!</p></div><div class="bg-white rounded-lg shadow-xl p-4 mb-6 border-2 border-purple-200" data-v-f15e69df><div class="flex justify-between items-center" data-v-f15e69df><div class="text-center" data-v-f15e69df><p class="text-gray-600 text-sm" data-v-f15e69df>Moves</p><p class="text-2xl font-bold text-indigo-600" data-v-f15e69df>${ssrInterpolate(moves.value)}</p></div><div class="text-center" data-v-f15e69df><p class="text-gray-600 text-sm" data-v-f15e69df>Pairs Found</p><p class="text-2xl font-bold text-purple-600" data-v-f15e69df>${ssrInterpolate(matchedPairs.value)}/${ssrInterpolate(currentSettings.value.pairs)}</p></div><div class="text-center" data-v-f15e69df><p class="text-gray-600 text-sm" data-v-f15e69df>Difficulty</p><div class="flex space-x-2 mt-1" data-v-f15e69df><button class="${ssrRenderClass([gameDifficulty.value === "easy" ? "bg-green-500 text-white" : "bg-gray-200 text-gray-700", "px-3 py-1 rounded text-sm font-medium transition-all hover:scale-105"])}" data-v-f15e69df> Easy </button><button class="${ssrRenderClass([gameDifficulty.value === "medium" ? "bg-yellow-500 text-white" : "bg-gray-200 text-gray-700", "px-3 py-1 rounded text-sm font-medium transition-all hover:scale-105"])}" data-v-f15e69df> Medium </button><button class="${ssrRenderClass([gameDifficulty.value === "hard" ? "bg-red-500 text-white" : "bg-gray-200 text-gray-700", "px-3 py-1 rounded text-sm font-medium transition-all hover:scale-105"])}" data-v-f15e69df> Hard </button></div></div></div></div><div class="bg-white rounded-lg shadow-xl p-6 border-2 border-purple-200" data-v-f15e69df><div class="grid gap-4 mx-auto" style="${ssrRenderStyle(`grid-template-columns: repeat(${currentSettings.value.gridCols}, minmax(0, 1fr)); max-width: ${currentSettings.value.gridCols * 100}px;`)}" data-v-f15e69df><!--[-->`);
      ssrRenderList(cards.value, (card) => {
        _push(`<div class="${ssrRenderClass([{
          "bg-gradient-to-br from-green-400 to-blue-400 border-2 border-green-200": card.isFlipped || card.isMatched,
          "bg-gradient-to-br from-indigo-400 to-purple-400": !card.isFlipped && !card.isMatched,
          "opacity-50 cursor-not-allowed": card.isMatched,
          "rotate-y-180": card.isFlipped || card.isMatched
        }, "aspect-square flex items-center justify-center text-4xl rounded-lg cursor-pointer transition-all duration-300 transform hover:scale-105 shadow-md"])}" data-v-f15e69df>`);
        if (card.isFlipped || card.isMatched) {
          _push(`<div class="animate-pulse" data-v-f15e69df>${ssrInterpolate(card.emoji)}</div>`);
        } else {
          _push(`<div class="text-white font-bold text-2xl" data-v-f15e69df> ? </div>`);
        }
        _push(`</div>`);
      });
      _push(`<!--]--></div></div><div class="text-center mt-6" data-v-f15e69df><button class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-lg shadow-xl hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105" data-v-f15e69df> New Game </button></div>`);
      if (!isGameActive.value && matchedPairs.value.value === currentSettings.value.pairs) {
        _push(`<div class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 backdrop-blur-sm" data-v-f15e69df><div class="bg-gradient-to-br from-purple-100 to-pink-100 rounded-xl p-8 max-w-md mx-4 text-center border-2 border-purple-300 shadow-2xl transform scale-100 animate-bounce" data-v-f15e69df><h2 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-4" data-v-f15e69df>\u{1F389} Congratulations! \u{1F389}</h2><p class="text-lg mb-2 text-gray-700" data-v-f15e69df>You found all pairs!</p><p class="text-gray-600 mb-6" data-v-f15e69df>Completed in ${ssrInterpolate(moves.value)} moves</p><button class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105 shadow-lg" data-v-f15e69df> Play Again </button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/MentalMatch.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const MentalMatch = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-f15e69df"]]);

export { MentalMatch as default };
//# sourceMappingURL=MentalMatch-G3BS2sQp.mjs.map
