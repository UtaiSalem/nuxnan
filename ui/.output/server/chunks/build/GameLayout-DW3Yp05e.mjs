import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { mergeProps, withCtx, createVNode, renderSlot, unref, toDisplayString, createBlock, openBlock, Fragment, renderList, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderSlot, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import MainLayout from './main-BqvhuwHD.mjs';
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
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import 'pinia';
import './useGamification-BliN7lma.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "GameLayout",
  __ssrInlineRender: true,
  setup(__props) {
    const games = [
      { name: "\u0E17\u0E32\u0E22\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02", path: "/play/games/guessing-number-game", icon: "fluent:number-symbol-24-regular" },
      { name: "XO", path: "/play/games/xo-game", icon: "fluent:grid-24-regular" },
      { name: "\u0E07\u0E39", path: "/play/games/snake-game", icon: "fluent:animal-turtle-24-regular" },
      { name: "\u0E08\u0E31\u0E1A\u0E04\u0E39\u0E48", path: "/play/games/mental-match", icon: "fluent:brain-circuit-24-regular" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(MainLayout, mergeProps({ title: "Games Zone" }, _attrs), {
        leftWidgets: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-vikinger-dark-50 sticky top-24" data-v-85d79d1a${_scopeId}><div class="flex items-center gap-3 mb-6" data-v-85d79d1a${_scopeId}><div class="w-10 h-10 rounded-lg bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger" data-v-85d79d1a${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:games-24-filled",
              class: "w-6 h-6"
            }, null, _parent2, _scopeId));
            _push2(`</div><div data-v-85d79d1a${_scopeId}><h2 class="font-bold text-gray-900 dark:text-white text-lg" data-v-85d79d1a${_scopeId}>Game Center</h2><p class="text-xs text-gray-500 dark:text-gray-400" data-v-85d79d1a${_scopeId}>\u0E28\u0E39\u0E19\u0E22\u0E4C\u0E23\u0E27\u0E21\u0E04\u0E27\u0E32\u0E21\u0E1A\u0E31\u0E19\u0E40\u0E17\u0E34\u0E07</p></div></div><nav class="space-y-1" data-v-85d79d1a${_scopeId}>`);
            _push2(ssrRenderComponent(_component_NuxtLink, {
              to: "/play/games",
              class: ["flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group", _ctx.$route.path === "/play/games" ? "" : "text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 hover:text-gray-900 dark:hover:text-gray-200"],
              "active-class": "bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan font-semibold ring-1 ring-vikinger-purple/20"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:home-24-regular",
                    class: "w-5 h-5 group-hover:scale-110 transition-transform"
                  }, null, _parent3, _scopeId2));
                  _push3(`<span data-v-85d79d1a${_scopeId2}>\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01\u0E40\u0E01\u0E21</span>`);
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: "fluent:home-24-regular",
                      class: "w-5 h-5 group-hover:scale-110 transition-transform"
                    }),
                    createVNode("span", null, "\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01\u0E40\u0E01\u0E21")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="my-4 border-t border-gray-100 dark:border-vikinger-dark-50/50" data-v-85d79d1a${_scopeId}></div><div class="px-3 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider" data-v-85d79d1a${_scopeId}>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E40\u0E01\u0E21</div><!--[-->`);
            ssrRenderList(games, (game) => {
              _push2(ssrRenderComponent(_component_NuxtLink, {
                key: game.path,
                to: game.path,
                class: ["flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group", _ctx.$route.path === game.path ? "" : "text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 hover:text-gray-900 dark:hover:text-gray-200 border-l-4 border-transparent"],
                "active-class": "bg-gradient-to-r from-vikinger-purple/10 to-transparent text-vikinger-purple dark:text-vikinger-cyan font-semibold border-l-4 border-vikinger-purple"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: game.icon,
                      class: "w-5 h-5 group-hover:scale-110 transition-transform"
                    }, null, _parent3, _scopeId2));
                    _push3(`<span data-v-85d79d1a${_scopeId2}>${ssrInterpolate(game.name)}</span>`);
                  } else {
                    return [
                      createVNode(unref(Icon), {
                        icon: game.icon,
                        class: "w-5 h-5 group-hover:scale-110 transition-transform"
                      }, null, 8, ["icon"]),
                      createVNode("span", null, toDisplayString(game.name), 1)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
            });
            _push2(`<!--]--></nav><div class="mt-6 p-4 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-center shadow-lg relative overflow-hidden group hover:scale-[1.02] transition-transform cursor-pointer" data-v-85d79d1a${_scopeId}><div class="absolute top-0 right-0 p-2 opacity-20" data-v-85d79d1a${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:trophy-24-filled",
              class: "w-16 h-16 transform rotate-12 group-hover:rotate-[20deg] transition-transform duration-500"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="text-xs font-medium text-white/80 mb-1" data-v-85d79d1a${_scopeId}>\u0E40\u0E25\u0E48\u0E19\u0E40\u0E01\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21</p><h3 class="text-xl font-bold mb-2" data-v-85d79d1a${_scopeId}>\u0E41\u0E25\u0E01\u0E02\u0E2D\u0E07\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25!</h3><div class="text-xs bg-white/20 hover:bg-white/30 backdrop-blur-sm px-3 py-1.5 rounded-lg transition-colors w-full inline-block" data-v-85d79d1a${_scopeId}> \u0E14\u0E39\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 </div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-vikinger-dark-50 sticky top-24" }, [
                createVNode("div", { class: "flex items-center gap-3 mb-6" }, [
                  createVNode("div", { class: "w-10 h-10 rounded-lg bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:games-24-filled",
                      class: "w-6 h-6"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h2", { class: "font-bold text-gray-900 dark:text-white text-lg" }, "Game Center"),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E28\u0E39\u0E19\u0E22\u0E4C\u0E23\u0E27\u0E21\u0E04\u0E27\u0E32\u0E21\u0E1A\u0E31\u0E19\u0E40\u0E17\u0E34\u0E07")
                  ])
                ]),
                createVNode("nav", { class: "space-y-1" }, [
                  createVNode(_component_NuxtLink, {
                    to: "/play/games",
                    class: ["flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group", _ctx.$route.path === "/play/games" ? "" : "text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 hover:text-gray-900 dark:hover:text-gray-200"],
                    "active-class": "bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan font-semibold ring-1 ring-vikinger-purple/20"
                  }, {
                    default: withCtx(() => [
                      createVNode(unref(Icon), {
                        icon: "fluent:home-24-regular",
                        class: "w-5 h-5 group-hover:scale-110 transition-transform"
                      }),
                      createVNode("span", null, "\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01\u0E40\u0E01\u0E21")
                    ]),
                    _: 1
                  }, 8, ["class"]),
                  createVNode("div", { class: "my-4 border-t border-gray-100 dark:border-vikinger-dark-50/50" }),
                  createVNode("div", { class: "px-3 mb-2 text-xs font-bold uppercase text-gray-400 tracking-wider" }, "\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E40\u0E01\u0E21"),
                  (openBlock(), createBlock(Fragment, null, renderList(games, (game) => {
                    return createVNode(_component_NuxtLink, {
                      key: game.path,
                      to: game.path,
                      class: ["flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group", _ctx.$route.path === game.path ? "" : "text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 hover:text-gray-900 dark:hover:text-gray-200 border-l-4 border-transparent"],
                      "active-class": "bg-gradient-to-r from-vikinger-purple/10 to-transparent text-vikinger-purple dark:text-vikinger-cyan font-semibold border-l-4 border-vikinger-purple"
                    }, {
                      default: withCtx(() => [
                        createVNode(unref(Icon), {
                          icon: game.icon,
                          class: "w-5 h-5 group-hover:scale-110 transition-transform"
                        }, null, 8, ["icon"]),
                        createVNode("span", null, toDisplayString(game.name), 1)
                      ]),
                      _: 2
                    }, 1032, ["to", "class"]);
                  }), 64))
                ]),
                createVNode("div", { class: "mt-6 p-4 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-center shadow-lg relative overflow-hidden group hover:scale-[1.02] transition-transform cursor-pointer" }, [
                  createVNode("div", { class: "absolute top-0 right-0 p-2 opacity-20" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:trophy-24-filled",
                      class: "w-16 h-16 transform rotate-12 group-hover:rotate-[20deg] transition-transform duration-500"
                    })
                  ]),
                  createVNode("p", { class: "text-xs font-medium text-white/80 mb-1" }, "\u0E40\u0E25\u0E48\u0E19\u0E40\u0E01\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21"),
                  createVNode("h3", { class: "text-xl font-bold mb-2" }, "\u0E41\u0E25\u0E01\u0E02\u0E2D\u0E07\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25!"),
                  createVNode("div", { class: "text-xs bg-white/20 hover:bg-white/30 backdrop-blur-sm px-3 py-1.5 rounded-lg transition-colors w-full inline-block" }, " \u0E14\u0E39\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 ")
                ])
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="min-h-[500px] animate-fade-in-up" data-v-85d79d1a${_scopeId}>`);
            ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "min-h-[500px] animate-fade-in-up" }, [
                renderSlot(_ctx.$slots, "default", {}, void 0, true)
              ])
            ];
          }
        }),
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/GameLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const GameLayout = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-85d79d1a"]]);

export { GameLayout as default };
//# sourceMappingURL=GameLayout-DW3Yp05e.mjs.map
