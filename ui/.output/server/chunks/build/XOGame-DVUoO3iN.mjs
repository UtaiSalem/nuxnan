import { ref, watch, withCtx, createVNode, createBlock, createCommentVNode, withDirectives, vModelSelect, openBlock, Fragment, renderList, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
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
  __name: "XOGame",
  __ssrInlineRender: true,
  setup(__props) {
    const board = ref(Array(9).fill(""));
    const currentPlayer = ref("X");
    const scores = ref({ X: 0, O: 0 });
    const gameMode = ref("single");
    const showFireworks = ref(false);
    ref(null);
    const winningCombos = [
      [0, 1, 2],
      [3, 4, 5],
      [6, 7, 8],
      // แนวนอน
      [0, 3, 6],
      [1, 4, 7],
      [2, 5, 8],
      // แนวตั้ง
      [0, 4, 8],
      [2, 4, 6]
      // แนวทแยง
    ];
    const makeMove = (index) => {
      if (board.value[index] === "") {
        board.value[index] = currentPlayer.value;
        checkWinner();
        currentPlayer.value = currentPlayer.value === "X" ? "O" : "X";
        if (gameMode.value === "single" && currentPlayer.value === "O") {
          setTimeout(makeComputerMove, 500);
        }
      }
    };
    const makeComputerMove = () => {
      const emptySpots = board.value.reduce((acc, cell, index) => {
        if (cell === "") acc.push(index);
        return acc;
      }, []);
      if (emptySpots.length > 0) {
        const randomSpot = emptySpots[Math.floor(Math.random() * emptySpots.length)];
        makeMove(randomSpot);
      }
    };
    const checkWinner = () => {
      for (let combo of winningCombos) {
        if (combo.every((index) => board.value[index] === currentPlayer.value)) {
          scores.value[currentPlayer.value]++;
          showFireworks.value = true;
          showConfetti();
          setTimeout(() => {
            showFireworks.value = false;
            resetBoard();
          }, 3e3);
          return;
        }
      }
      if (board.value.every((cell) => cell !== "")) {
        resetBoard();
      }
    };
    const resetBoard = () => {
      board.value = Array(9).fill("");
      currentPlayer.value = "X";
    };
    const resetGame = () => {
      resetBoard();
      scores.value = { X: 0, O: 0 };
    };
    const showConfetti = () => {
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 }
      });
    };
    watch(gameMode, () => {
      resetGame();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12" data-v-383528fb${_scopeId}><div class="relative py-3 sm:max-w-xl sm:mx-auto" data-v-383528fb${_scopeId}><div class="absolute inset-0 bg-gradient-to-r from-cyan-400 to-light-blue-500 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl" data-v-383528fb${_scopeId}></div><div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20" data-v-383528fb${_scopeId}><h1 class="text-4xl font-bold mb-5 text-center text-gray-800" data-v-383528fb${_scopeId}>\u0E40\u0E01\u0E21 XO</h1><div class="mb-5" data-v-383528fb${_scopeId}><label class="mr-3" data-v-383528fb${_scopeId}>\u0E42\u0E2B\u0E21\u0E14\u0E40\u0E01\u0E21:</label><select class="border p-2 rounded" data-v-383528fb${_scopeId}><option value="single" data-v-383528fb${ssrIncludeBooleanAttr(Array.isArray(gameMode.value) ? ssrLooseContain(gameMode.value, "single") : ssrLooseEqual(gameMode.value, "single")) ? " selected" : ""}${_scopeId}>\u0E40\u0E25\u0E48\u0E19\u0E04\u0E19\u0E40\u0E14\u0E35\u0E22\u0E27</option><option value="multi" data-v-383528fb${ssrIncludeBooleanAttr(Array.isArray(gameMode.value) ? ssrLooseContain(gameMode.value, "multi") : ssrLooseEqual(gameMode.value, "multi")) ? " selected" : ""}${_scopeId}>\u0E40\u0E25\u0E48\u0E19\u0E2A\u0E2D\u0E07\u0E04\u0E19</option></select></div><div class="grid grid-cols-3 gap-3 mb-5" data-v-383528fb${_scopeId}><!--[-->`);
            ssrRenderList(board.value, (cell, index) => {
              _push2(`<button class="w-20 h-20 bg-blue-200 text-4xl font-bold flex items-center justify-center rounded" data-v-383528fb${_scopeId}>${ssrInterpolate(cell)}</button>`);
            });
            _push2(`<!--]--></div><div class="text-center mb-5" data-v-383528fb${_scopeId}><p class="text-xl font-semibold" data-v-383528fb${_scopeId}>\u0E1C\u0E39\u0E49\u0E40\u0E25\u0E48\u0E19 ${ssrInterpolate(currentPlayer.value)} \u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E25\u0E48\u0E19</p><p class="text-lg" data-v-383528fb${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19: X - ${ssrInterpolate(scores.value.X)}, O - ${ssrInterpolate(scores.value.O)}</p></div><button class="bg-green-500 text-white px-4 py-2 rounded w-full" data-v-383528fb${_scopeId}>\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E01\u0E21\u0E43\u0E2B\u0E21\u0E48</button></div></div></div>`);
            if (showFireworks.value) {
              _push2(`<div class="xfireworks" data-v-383528fb${_scopeId}></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", { class: "min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12" }, [
                createVNode("div", { class: "relative py-3 sm:max-w-xl sm:mx-auto" }, [
                  createVNode("div", { class: "absolute inset-0 bg-gradient-to-r from-cyan-400 to-light-blue-500 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl" }),
                  createVNode("div", { class: "relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20" }, [
                    createVNode("h1", { class: "text-4xl font-bold mb-5 text-center text-gray-800" }, "\u0E40\u0E01\u0E21 XO"),
                    createVNode("div", { class: "mb-5" }, [
                      createVNode("label", { class: "mr-3" }, "\u0E42\u0E2B\u0E21\u0E14\u0E40\u0E01\u0E21:"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => gameMode.value = $event,
                        class: "border p-2 rounded"
                      }, [
                        createVNode("option", { value: "single" }, "\u0E40\u0E25\u0E48\u0E19\u0E04\u0E19\u0E40\u0E14\u0E35\u0E22\u0E27"),
                        createVNode("option", { value: "multi" }, "\u0E40\u0E25\u0E48\u0E19\u0E2A\u0E2D\u0E07\u0E04\u0E19")
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, gameMode.value]
                      ])
                    ]),
                    createVNode("div", { class: "grid grid-cols-3 gap-3 mb-5" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(board.value, (cell, index) => {
                        return openBlock(), createBlock("button", {
                          key: index,
                          onClick: ($event) => makeMove(index),
                          class: "w-20 h-20 bg-blue-200 text-4xl font-bold flex items-center justify-center rounded"
                        }, toDisplayString(cell), 9, ["onClick"]);
                      }), 128))
                    ]),
                    createVNode("div", { class: "text-center mb-5" }, [
                      createVNode("p", { class: "text-xl font-semibold" }, "\u0E1C\u0E39\u0E49\u0E40\u0E25\u0E48\u0E19 " + toDisplayString(currentPlayer.value) + " \u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E25\u0E48\u0E19", 1),
                      createVNode("p", { class: "text-lg" }, "\u0E04\u0E30\u0E41\u0E19\u0E19: X - " + toDisplayString(scores.value.X) + ", O - " + toDisplayString(scores.value.O), 1)
                    ]),
                    createVNode("button", {
                      onClick: resetGame,
                      class: "bg-green-500 text-white px-4 py-2 rounded w-full"
                    }, "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E01\u0E21\u0E43\u0E2B\u0E21\u0E48")
                  ])
                ])
              ]),
              showFireworks.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "xfireworks"
              })) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/XOGame.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const XOGame = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-383528fb"]]);

export { XOGame as default };
//# sourceMappingURL=XOGame-DVUoO3iN.mjs.map
