import { ref, watch, withCtx, createVNode, toDisplayString, withDirectives, withKeys, vModelText, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import confetti from 'canvas-confetti';
import _sfc_main$1 from './GameLayout-Cmv3cZ39.mjs';
import { _ as _export_sfc } from './server.mjs';
import './inertia-vue3-CWdJjaLG.mjs';
import 'unhead/utils';
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

const _sfc_main = {
  __name: "GuessingNumberGame",
  __ssrInlineRender: true,
  setup(__props) {
    const secretNumber = ref(generateRandomNumber());
    const guess = ref(null);
    const message = ref("");
    const isGameOver = ref(false);
    const attempts = ref(0);
    const score = ref(0);
    function generateRandomNumber() {
      return Math.floor(Math.random() * 100) + 1;
    }
    const checkGuess = () => {
      if (!guess.value) return;
      const guessNumber = parseInt(guess.value, 10);
      if (isNaN(guessNumber)) return;
      attempts.value++;
      message.value = "";
      calculateScore();
      setTimeout(() => {
        if (guessNumber === secretNumber.value) {
          message.value = "\u0E22\u0E34\u0E19\u0E14\u0E35\u0E14\u0E49\u0E27\u0E22! \u0E04\u0E38\u0E13\u0E17\u0E32\u0E22\u0E16\u0E39\u0E01!";
          isGameOver.value = true;
          showConfetti();
        } else if (guessNumber < secretNumber.value) {
          message.value = "\u0E19\u0E49\u0E2D\u0E22\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07";
        } else {
          message.value = "\u0E21\u0E32\u0E01\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07";
        }
      }, 10);
      guess.value = null;
    };
    const resetGame = () => {
      secretNumber.value = generateRandomNumber();
      guess.value = null;
      message.value = "";
      isGameOver.value = false;
      attempts.value = 0;
      score.value = 0;
    };
    const showConfetti = () => {
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
      });
    };
    const calculateScore = () => {
      score.value = Math.max(100 - (attempts.value - 1) * 10, 10);
    };
    watch(isGameOver, (newValue) => {
      if (newValue) {
        showConfetti();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-full h-full flex items-center justify-center" data-v-2200668e${_scopeId}><div class="bg-white rounded-lg shadow-md max-w-md w-full" data-v-2200668e${_scopeId}><div class="px-8 py-8" data-v-2200668e${_scopeId}><h1 class="text-3xl font-bold text-center mb-6 text-blue-600" data-v-2200668e${_scopeId}>\u0E40\u0E01\u0E21\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02</h1><p class="text-gray-600 text-center mb-4" data-v-2200668e${_scopeId}>\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07 1-100</p><p class="text-center text-gray-600 m-4" data-v-2200668e${_scopeId}> \u0E04\u0E38\u0E13\u0E17\u0E32\u0E22 ${ssrInterpolate(attempts.value)} \u0E04\u0E23\u0E31\u0E49\u0E07 \u0E41\u0E25\u0E30\u0E44\u0E14\u0E49\u0E04\u0E30\u0E41\u0E19\u0E19 ${ssrInterpolate(score.value)} \u0E04\u0E30\u0E41\u0E19\u0E19 </p><p class="${ssrRenderClass([{
              "text-green-600": message.value.includes("\u0E22\u0E34\u0E19\u0E14\u0E35\u0E14\u0E49\u0E27\u0E22"),
              "text-red-600": message.value.includes("\u0E19\u0E49\u0E2D\u0E22\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B") || message.value.includes("\u0E21\u0E32\u0E01\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B")
            }, "text-center text-lg font-semibold"])}" data-v-2200668e${_scopeId}>${ssrInterpolate(message.value)}</p><div class="mb-4" data-v-2200668e${_scopeId}><input${ssrRenderAttr("value", guess.value)} type="text" inputmode="numeric" pattern="[0-9]*"${ssrIncludeBooleanAttr(isGameOver.value) ? " disabled" : ""} class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="\u0E43\u0E2A\u0E48\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E17\u0E32\u0E22" data-v-2200668e${_scopeId}></div><div class="flex space-x-2 mb-4" data-v-2200668e${_scopeId}><button${ssrIncludeBooleanAttr(isGameOver.value || !guess.value) ? " disabled" : ""} class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50" data-v-2200668e${_scopeId}> \u0E17\u0E32\u0E22 </button><button class="flex-1 bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500" data-v-2200668e${_scopeId}> \u0E40\u0E25\u0E48\u0E19\u0E43\u0E2B\u0E21\u0E48 </button></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "w-full h-full flex items-center justify-center" }, [
                createVNode("div", { class: "bg-white rounded-lg shadow-md max-w-md w-full" }, [
                  createVNode("div", { class: "px-8 py-8" }, [
                    createVNode("h1", { class: "text-3xl font-bold text-center mb-6 text-blue-600" }, "\u0E40\u0E01\u0E21\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02"),
                    createVNode("p", { class: "text-gray-600 text-center mb-4" }, "\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07 1-100"),
                    createVNode("p", { class: "text-center text-gray-600 m-4" }, " \u0E04\u0E38\u0E13\u0E17\u0E32\u0E22 " + toDisplayString(attempts.value) + " \u0E04\u0E23\u0E31\u0E49\u0E07 \u0E41\u0E25\u0E30\u0E44\u0E14\u0E49\u0E04\u0E30\u0E41\u0E19\u0E19 " + toDisplayString(score.value) + " \u0E04\u0E30\u0E41\u0E19\u0E19 ", 1),
                    createVNode("p", {
                      class: ["text-center text-lg font-semibold", {
                        "text-green-600": message.value.includes("\u0E22\u0E34\u0E19\u0E14\u0E35\u0E14\u0E49\u0E27\u0E22"),
                        "text-red-600": message.value.includes("\u0E19\u0E49\u0E2D\u0E22\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B") || message.value.includes("\u0E21\u0E32\u0E01\u0E40\u0E01\u0E34\u0E19\u0E44\u0E1B")
                      }]
                    }, toDisplayString(message.value), 3),
                    createVNode("div", { class: "mb-4" }, [
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => guess.value = $event,
                        type: "text",
                        inputmode: "numeric",
                        pattern: "[0-9]*",
                        disabled: isGameOver.value,
                        onKeyup: withKeys(checkGuess, ["enter"]),
                        class: "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500",
                        placeholder: "\u0E43\u0E2A\u0E48\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E17\u0E32\u0E22"
                      }, null, 40, ["onUpdate:modelValue", "disabled"]), [
                        [vModelText, guess.value]
                      ])
                    ]),
                    createVNode("div", { class: "flex space-x-2 mb-4" }, [
                      createVNode("button", {
                        onClick: checkGuess,
                        disabled: isGameOver.value || !guess.value,
                        class: "flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                      }, " \u0E17\u0E32\u0E22 ", 8, ["disabled"]),
                      createVNode("button", {
                        onClick: resetGame,
                        class: "flex-1 bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                      }, " \u0E40\u0E25\u0E48\u0E19\u0E43\u0E2B\u0E21\u0E48 ")
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/GuessingNumberGame.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const GuessingNumberGame = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-2200668e"]]);

export { GuessingNumberGame as default };
//# sourceMappingURL=GuessingNumberGame-BcmP-hl2.mjs.map
