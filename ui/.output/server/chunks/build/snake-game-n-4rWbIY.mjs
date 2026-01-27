import { mergeProps, ref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { b as useRuntimeConfig, d as useAuthStore } from './server.mjs';
import { s as setInterval } from './interval-BEVgA3pa.mjs';
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

const _sfc_main$6 = {
  __name: "GameBoard",
  __ssrInlineRender: true,
  props: ["snake", "food", "gameOver", "gridSize"],
  emits: ["keydown"],
  setup(__props) {
    const props = __props;
    const isSnakeSegment = (x, y) => {
      return props.snake.some((segment) => segment.x === x && segment.y === y);
    };
    const isFood = (x, y) => {
      return props.food.x === x && props.food.y === y;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: "bg-gray-100 dark:bg-gray-700 grid gap-0 border-4 border-gray-300 dark:border-gray-600 mx-auto rounded-xl shadow-lg",
        style: {
          width: `min(90vw, 90vh, 400px)`,
          height: `min(90vw, 90vh, 400px)`,
          gridTemplateColumns: `repeat(${__props.gridSize}, 1fr)`
        },
        tabindex: "0"
      }, _attrs))}><!--[-->`);
      ssrRenderList(__props.gridSize, (y) => {
        _push(`<!--[--><!--[-->`);
        ssrRenderList(__props.gridSize, (x) => {
          _push(`<div class="${ssrRenderClass([{
            "bg-green-500 shadow-lg": isSnakeSegment(x - 1, y - 1),
            "bg-red-500 shadow-lg animate-pulse": isFood(x - 1, y - 1),
            "bg-gray-50 dark:bg-gray-800": !isSnakeSegment(x - 1, y - 1) && !isFood(x - 1, y - 1)
          }, "aspect-square border border-gray-200 dark:border-gray-600"])}"></div>`);
        });
        _push(`<!--]--><!--]-->`);
      });
      _push(`<!--]--></div>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/games/snake/GameBoard.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const _sfc_main$5 = {
  __name: "ScoreBoard",
  __ssrInlineRender: true,
  props: ["score", "level"],
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "mb-6 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4" }, _attrs))}><div class="text-center"><div class="text-2xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(__props.score)}</div><div class="text-sm text-gray-500 dark:text-gray-400">Score</div></div><div class="text-center"><div class="text-2xl font-bold text-blue-600 dark:text-blue-400">${ssrInterpolate(__props.level)}</div><div class="text-sm text-gray-500 dark:text-gray-400">Level</div></div><div class="text-center"><div class="text-2xl font-bold text-green-600 dark:text-green-400">${ssrInterpolate(Math.floor(__props.score / 2))}</div><div class="text-sm text-gray-500 dark:text-gray-400">Points</div></div></div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/games/snake/ScoreBoard.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "LevelSelector",
  __ssrInlineRender: true,
  emits: ["select-level"],
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "mt-6" }, _attrs))}><h2 class="text-xl font-semibold mb-4 text-center text-gray-900 dark:text-white">Select Difficulty Level:</h2><div class="grid grid-cols-5 gap-3"><!--[-->`);
      ssrRenderList(5, (l) => {
        _push(`<button class="px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all transform hover:scale-105"> Level ${ssrInterpolate(l)}</button>`);
      });
      _push(`<!--]--></div><div class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center"> Higher levels are faster and more challenging! </div></div>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/games/snake/LevelSelector.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "CongratulationsModal",
  __ssrInlineRender: true,
  props: ["level"],
  emits: ["next-level", "quit-game"],
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" }, _attrs))}><div class="bg-white dark:bg-gray-800 px-8 py-6 rounded-xl shadow-lg text-center max-w-md mx-4"><div class="text-6xl mb-4">\u{1F389}</div><h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Congratulations!</h2><p class="mb-6 text-gray-600 dark:text-gray-400">You&#39;ve completed level ${ssrInterpolate(__props.level)}!</p><p class="mb-6 text-sm text-green-600 dark:text-green-400 font-medium">+${ssrInterpolate(__props.level * 20)} Points Earned!</p><div class="flex justify-center space-x-3">`);
      if (__props.level < 5) {
        _push(`<button class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium shadow-lg hover:shadow-green-500/30 transition-all"> Next Level </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium shadow-lg hover:shadow-gray-500/30 transition-all"> Quit Game </button></div></div></div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/games/snake/CongratulationsModal.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "GameOverModal",
  __ssrInlineRender: true,
  props: ["score"],
  emits: ["restart", "quit"],
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" }, _attrs))}><div class="bg-white dark:bg-gray-800 px-8 py-6 rounded-xl shadow-lg text-center max-w-md mx-4"><div class="text-6xl mb-4">\u{1F480}</div><h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Game Over!</h2><p class="mb-6 text-gray-600 dark:text-gray-400">Your final score: <span class="font-bold text-gray-900 dark:text-white">${ssrInterpolate(__props.score)}</span></p><div class="flex justify-center space-x-3"><button class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all"> Restart </button><button class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium shadow-lg hover:shadow-gray-500/30 transition-all"> Quit </button></div></div></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/games/snake/GameOverModal.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const gridSize = 20;
const _sfc_main$1 = {
  __name: "SnakeGameComponent",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    const apiBase = config.public.apiBase;
    const authStore = useAuthStore();
    const snake = ref([{ x: 10, y: 10 }]);
    const food = ref({ x: 5, y: 5 });
    const direction = ref("right");
    const gameOver = ref(false);
    const score = ref(0);
    const level = ref(1);
    const gameStarted = ref(false);
    const showCongrats = ref(false);
    let gameLoop;
    const speeds = {
      1: 300,
      2: 200,
      3: 150,
      4: 120,
      5: 90
    };
    const earnPoints = async (amount, description) => {
      if (!authStore.isAuthenticated) return;
      try {
        await $fetch(`${apiBase}/api/points/earn`, {
          method: "POST",
          body: {
            source_type: "game_snake",
            amount,
            description,
            metadata: {
              game_level: level.value,
              game_score: score.value
            }
          },
          headers: { Authorization: `Bearer ${authStore.token}` }
        });
      } catch (error) {
        console.error("Failed to earn points:", error);
      }
    };
    const startGame = (selectedLevel) => {
      level.value = selectedLevel;
      gameStarted.value = true;
      resetGame();
      gameLoop = setInterval(updateGame, speeds[level.value]);
    };
    const resetGame = () => {
      snake.value = [{ x: 10, y: 10 }];
      food.value = getRandomFood();
      direction.value = "right";
      gameOver.value = false;
      score.value = 0;
    };
    const restartGame = () => {
      clearInterval(gameLoop);
      resetGame();
      gameLoop = setInterval(updateGame, speeds[level.value]);
    };
    const quitGame = () => {
      gameStarted.value = false;
      showCongrats.value = false;
      resetGame();
      clearInterval(gameLoop);
    };
    const updateGame = () => {
      if (gameOver.value) {
        clearInterval(gameLoop);
        return;
      }
      const head = { ...snake.value[0] };
      switch (direction.value) {
        case "up":
          head.y--;
          break;
        case "down":
          head.y++;
          break;
        case "left":
          head.x--;
          break;
        case "right":
          head.x++;
          break;
      }
      if (checkCollision(head)) {
        gameOver.value = true;
        return;
      }
      snake.value.unshift(head);
      if (head.x === food.value.x && head.y === food.value.y) {
        score.value += 10;
        earnPoints(5, `Snake Game - Ate food at level ${level.value}`);
        food.value = getRandomFood();
        if (score.value >= level.value * 100) {
          showCongrats.value = true;
          clearInterval(gameLoop);
        }
      } else {
        snake.value.pop();
      }
    };
    const checkCollision = (head) => {
      return head.x < 0 || head.x >= gridSize || head.y < 0 || head.y >= gridSize || snake.value.some((segment) => segment.x === head.x && segment.y === head.y);
    };
    const getRandomFood = () => {
      return {
        x: Math.floor(Math.random() * gridSize),
        y: Math.floor(Math.random() * gridSize)
      };
    };
    const handleKeyDown = (e) => {
      switch (e.key) {
        case "ArrowUp":
          if (direction.value !== "down") direction.value = "up";
          break;
        case "ArrowDown":
          if (direction.value !== "up") direction.value = "down";
          break;
        case "ArrowLeft":
          if (direction.value !== "right") direction.value = "left";
          break;
        case "ArrowRight":
          if (direction.value !== "left") direction.value = "right";
          break;
      }
    };
    const nextLevel = () => {
      if (level.value < 5) {
        level.value++;
        earnPoints(level.value * 20, `Snake Game - Completed level ${level.value - 1}`);
        showCongrats.value = false;
        resetGame();
        gameLoop = setInterval(updateGame, speeds[level.value]);
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 p-4" }, _attrs))}><div class="max-w-4xl mx-auto w-full"><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6"><h1 class="text-3xl font-bold mb-4 text-center text-gray-900 dark:text-white">\u{1F40D} Snake Game</h1><p class="text-center text-gray-600 dark:text-gray-400 mb-4">Play the classic snake game and earn points!</p><div class="flex-col items-center justify-center">`);
      if (gameStarted.value) {
        _push(`<div class="flex justify-center max-w-full items-center mb-4"><div class="flex w-[400px] justify-between items-center">`);
        _push(ssrRenderComponent(_sfc_main$5, {
          score: score.value,
          level: level.value
        }, null, _parent));
        _push(`<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition duration-300"> Restart Game </button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (gameStarted.value) {
        _push(ssrRenderComponent(_sfc_main$6, {
          snake: snake.value,
          food: food.value,
          gameOver: gameOver.value,
          gridSize,
          onKeydown: handleKeyDown
        }, null, _parent));
      } else {
        _push(`<div class="flex items-center justify-center mb-4"><div><p class="text-xl mb-4">Welcome to Snake Game!</p>`);
        _push(ssrRenderComponent(_sfc_main$4, { onSelectLevel: startGame }, null, _parent));
        _push(`</div></div>`);
      }
      _push(`</div>`);
      if (showCongrats.value) {
        _push(ssrRenderComponent(_sfc_main$3, {
          level: level.value,
          onNextLevel: nextLevel,
          onQuitGame: quitGame
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (gameOver.value) {
        _push(ssrRenderComponent(_sfc_main$2, {
          score: score.value,
          onRestart: restartGame,
          onQuit: quitGame
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/games/snake/SnakeGameComponent.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "snake-game",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full h-full flex flex-col items-center justify-center min-h-[calc(100vh-100px)]" }, _attrs))}><div class="w-full max-w-4xl">`);
      _push(ssrRenderComponent(_sfc_main$1, null, null, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/snake-game.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=snake-game-n-4rWbIY.mjs.map
