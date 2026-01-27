import { ref, computed, resolveComponent, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderStyle, ssrRenderList, ssrRenderComponent } from 'vue/server-renderer';
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
  __name: "mental-match",
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
      const _component_Icon = resolveComponent("Icon");
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex flex-col p-4 bg-gray-50 dark:bg-gray-900" }, _attrs))} data-v-93d1297b><div class="max-w-4xl mx-auto w-full" data-v-93d1297b><div class="text-center mb-8" data-v-93d1297b><h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-pink-500 mb-2 drop-shadow-sm" data-v-93d1297b>Mental Match Game</h1><p class="text-gray-600 dark:text-gray-400 text-lg" data-v-93d1297b>Find all matching pairs!</p></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700" data-v-93d1297b><div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center" data-v-93d1297b><div class="text-center" data-v-93d1297b><p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wider font-semibold" data-v-93d1297b>Moves</p><p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400" data-v-93d1297b>${ssrInterpolate(moves.value)}</p></div><div class="text-center" data-v-93d1297b><p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wider font-semibold" data-v-93d1297b>Pairs Found</p><p class="text-3xl font-bold text-purple-600 dark:text-purple-400" data-v-93d1297b>${ssrInterpolate(matchedPairs.value)}/${ssrInterpolate(currentSettings.value.pairs)}</p></div><div class="text-center" data-v-93d1297b><p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wider font-semibold mb-2" data-v-93d1297b>Difficulty</p><div class="flex justify-center space-x-2" data-v-93d1297b><button class="${ssrRenderClass([gameDifficulty.value === "easy" ? "bg-green-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-all transform active:scale-95"])}" data-v-93d1297b> Easy </button><button class="${ssrRenderClass([gameDifficulty.value === "medium" ? "bg-yellow-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-all transform active:scale-95"])}" data-v-93d1297b> Medium </button><button class="${ssrRenderClass([gameDifficulty.value === "hard" ? "bg-red-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-all transform active:scale-95"])}" data-v-93d1297b> Hard </button></div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 border border-gray-100 dark:border-gray-700 mb-8" data-v-93d1297b><div class="grid gap-4 mx-auto" style="${ssrRenderStyle(`grid-template-columns: repeat(${currentSettings.value.gridCols}, minmax(0, 1fr)); max-width: ${currentSettings.value.gridCols * 100}px;`)}" data-v-93d1297b><!--[-->`);
      ssrRenderList(cards.value, (card) => {
        _push(`<div class="${ssrRenderClass([{
          "bg-gradient-to-br from-green-400 to-blue-500 border-2 border-green-300 dark:border-green-600": card.isFlipped || card.isMatched,
          "bg-gradient-to-br from-indigo-400 to-purple-500": !card.isFlipped && !card.isMatched,
          "opacity-60 cursor-default ring-4 ring-green-400/30": card.isMatched,
          "rotate-y-180": card.isFlipped || card.isMatched
        }, "aspect-square flex items-center justify-center text-4xl rounded-xl cursor-pointer transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"])}" data-v-93d1297b>`);
        if (card.isFlipped || card.isMatched) {
          _push(`<div class="animate-appear" data-v-93d1297b>${ssrInterpolate(card.emoji)}</div>`);
        } else {
          _push(`<div class="text-white/80 font-bold text-3xl" data-v-93d1297b> ? </div>`);
        }
        _push(`</div>`);
      });
      _push(`<!--]--></div></div><div class="text-center" data-v-93d1297b><button class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-purple-500/30 hover:from-purple-700 hover:to-pink-700 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center mx-auto space-x-2" data-v-93d1297b>`);
      _push(ssrRenderComponent(_component_Icon, {
        name: "fluent:arrow-counterclockwise-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span data-v-93d1297b>New Game</span></button></div>`);
      if (!isGameActive.value && matchedPairs.value === currentSettings.value.pairs) {
        _push(`<div class="fixed inset-0 bg-black/60 dark:bg-black/80 flex items-center justify-center z-50 backdrop-blur-sm p-4" data-v-93d1297b><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl border border-gray-200 dark:border-gray-700 transform scale-100 animate-bounce-in" data-v-93d1297b><div class="text-6xl mb-4" data-v-93d1297b>\u{1F389}</div><h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" data-v-93d1297b>Congratulations!</h2><p class="text-lg text-gray-600 dark:text-gray-300 mb-2" data-v-93d1297b>You found all pairs!</p><div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 mb-6" data-v-93d1297b><p class="text-gray-700 dark:text-gray-200 font-medium" data-v-93d1297b>Completed in <span class="text-indigo-600 dark:text-indigo-400 font-bold" data-v-93d1297b>${ssrInterpolate(moves.value)}</span> moves</p></div><button class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg hover:shadow-purple-500/25" data-v-93d1297b> Play Again </button></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/mental-match.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const mentalMatch = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-93d1297b"]]);

export { mentalMatch as default };
//# sourceMappingURL=mental-match-CNBaZlkl.mjs.map
