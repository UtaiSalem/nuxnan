import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { mergeProps, withCtx, unref, createVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderList, ssrRenderComponent, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const games = [
      {
        title: "\u0E40\u0E01\u0E21\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02",
        description: "\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07 1-100 \u0E43\u0E19\u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E23\u0E31\u0E49\u0E07\u0E19\u0E49\u0E2D\u0E22\u0E17\u0E35\u0E48\u0E2A\u0E38\u0E14",
        icon: "fluent:number-symbol-24-regular",
        route: "/play/games/guessing-number-game",
        color: "from-blue-500 to-blue-600"
      },
      {
        title: "\u0E40\u0E01\u0E21 XO",
        description: "\u0E40\u0E01\u0E21 tic-tac-toe \u0E41\u0E1A\u0E1A\u0E04\u0E25\u0E32\u0E2A\u0E2A\u0E34\u0E01\u0E01\u0E31\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E2B\u0E23\u0E37\u0E2D AI",
        icon: "fluent:grid-24-regular",
        route: "/play/games/xo-game",
        color: "from-green-500 to-green-600"
      },
      {
        title: "\u0E40\u0E01\u0E21\u0E07\u0E39",
        description: "\u0E04\u0E27\u0E1A\u0E04\u0E38\u0E21\u0E07\u0E39\u0E43\u0E2B\u0E49\u0E01\u0E34\u0E19\u0E2D\u0E32\u0E2B\u0E32\u0E23\u0E41\u0E25\u0E30\u0E2B\u0E25\u0E35\u0E01\u0E40\u0E25\u0E35\u0E48\u0E22\u0E07\u0E01\u0E33\u0E41\u0E1E\u0E07",
        icon: "fluent:animal-turtle-24-regular",
        route: "/play/games/snake-game",
        color: "from-purple-500 to-purple-600"
      },
      {
        title: "\u0E40\u0E01\u0E21\u0E08\u0E31\u0E1A\u0E04\u0E39\u0E48",
        description: "\u0E08\u0E31\u0E1A\u0E04\u0E39\u0E48\u0E20\u0E32\u0E1E\u0E41\u0E25\u0E30\u0E04\u0E33\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1D\u0E36\u0E01\u0E04\u0E27\u0E32\u0E21\u0E08\u0E33\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E04\u0E34\u0E14",
        icon: "fluent:brain-circuit-24-regular",
        route: "/play/games/mental-match",
        color: "from-orange-500 to-orange-600"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-6xl mx-auto py-8" }, _attrs))} data-v-dab3eb27><div class="text-center mb-12" data-v-dab3eb27><h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4" data-v-dab3eb27> \u0E40\u0E01\u0E21\u0E2A\u0E4C\u0E2A\u0E19\u0E38\u0E01 \u0E46 </h1><p class="text-lg text-gray-600 dark:text-gray-300" data-v-dab3eb27> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E40\u0E01\u0E21\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E25\u0E48\u0E19\u0E41\u0E25\u0E30\u0E17\u0E49\u0E32\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E07 </p></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-8" data-v-dab3eb27><!--[-->`);
      ssrRenderList(games, (game) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          key: game.route,
          to: game.route,
          class: "group relative overflow-hidden rounded-2xl bg-white dark:bg-vikinger-dark-100 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 block border border-gray-100 dark:border-vikinger-dark-50"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="${ssrRenderClass([game.color, "absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-5 transition-opacity duration-300"])}" data-v-dab3eb27${_scopeId}></div><div class="${ssrRenderClass([game.color, "h-1.5 w-full bg-gradient-to-r"])}" data-v-dab3eb27${_scopeId}></div><div class="relative p-8" data-v-dab3eb27${_scopeId}><div class="flex items-start justify-between mb-6" data-v-dab3eb27${_scopeId}><div class="${ssrRenderClass([game.color, "w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md transform group-hover:scale-110 transition-transform duration-300"])}" data-v-dab3eb27${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: game.icon,
                class: "w-7 h-7 text-white"
              }, null, _parent2, _scopeId));
              _push2(`</div><div class="p-2 rounded-full bg-gray-50 dark:bg-vikinger-dark-200 text-gray-400 group-hover:bg-vikinger-purple group-hover:text-white transition-colors duration-300" data-v-dab3eb27${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-right-24-filled",
                class: "w-5 h-5 -rotate-45 group-hover:rotate-0 transition-transform duration-300"
              }, null, _parent2, _scopeId));
              _push2(`</div></div><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors" data-v-dab3eb27${_scopeId}>${ssrInterpolate(game.title)}</h3><p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed mb-4" data-v-dab3eb27${_scopeId}>${ssrInterpolate(game.description)}</p><div class="inline-flex items-center text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors" data-v-dab3eb27${_scopeId}> Play Now </div></div>`);
            } else {
              return [
                createVNode("div", {
                  class: ["absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-5 transition-opacity duration-300", game.color]
                }, null, 2),
                createVNode("div", {
                  class: ["h-1.5 w-full bg-gradient-to-r", game.color]
                }, null, 2),
                createVNode("div", { class: "relative p-8" }, [
                  createVNode("div", { class: "flex items-start justify-between mb-6" }, [
                    createVNode("div", {
                      class: ["w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md transform group-hover:scale-110 transition-transform duration-300", game.color]
                    }, [
                      createVNode(unref(Icon), {
                        icon: game.icon,
                        class: "w-7 h-7 text-white"
                      }, null, 8, ["icon"])
                    ], 2),
                    createVNode("div", { class: "p-2 rounded-full bg-gray-50 dark:bg-vikinger-dark-200 text-gray-400 group-hover:bg-vikinger-purple group-hover:text-white transition-colors duration-300" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:arrow-right-24-filled",
                        class: "w-5 h-5 -rotate-45 group-hover:rotate-0 transition-transform duration-300"
                      })
                    ])
                  ]),
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors" }, toDisplayString(game.title), 1),
                  createVNode("p", { class: "text-gray-600 dark:text-gray-400 text-sm leading-relaxed mb-4" }, toDisplayString(game.description), 1),
                  createVNode("div", { class: "inline-flex items-center text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors" }, " Play Now ")
                ])
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div><div class="mt-16 text-center" data-v-dab3eb27><div class="inline-block p-8 px-12 rounded-2xl bg-gray-50 dark:bg-vikinger-dark-200/50 border-2 border-dashed border-gray-200 dark:border-vikinger-dark-50" data-v-dab3eb27>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:game-24-regular",
        class: "w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4"
      }, null, _parent));
      _push(`<h3 class="text-lg font-semibold text-gray-500 dark:text-gray-400 mb-1" data-v-dab3eb27> \u0E40\u0E01\u0E21\u0E43\u0E2B\u0E21\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E21\u0E32 </h3><p class="text-sm text-gray-400 dark:text-gray-500" data-v-dab3eb27> \u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15\u0E40\u0E01\u0E21\u0E43\u0E2B\u0E21\u0E48\u0E46 \u0E44\u0E14\u0E49\u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49 </p></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Games/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-dab3eb27"]]);

export { index as default };
//# sourceMappingURL=index-DBb4r1S_.mjs.map
