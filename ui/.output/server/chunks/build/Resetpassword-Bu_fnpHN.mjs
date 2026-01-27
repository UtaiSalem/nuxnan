import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { ref, shallowRef, computed, mergeProps, withCtx, unref, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr } from 'vue/server-renderer';
import { useDebounceFn } from '@vueuse/core';
import { Icon } from '@iconify/vue';
import { a as useNuxtApp } from './server.mjs';
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

const REQUIRED_POINTS = 4800;
const POINTS_PER_BAHT = 1080;
const _sfc_main = {
  __name: "Resetpassword",
  __ssrInlineRender: true,
  setup(__props) {
    const { $apiFetch } = useNuxtApp();
    const searchQuery = ref("");
    const usersResult = shallowRef([]);
    const isSearching = ref(false);
    const searchType = ref("all");
    const hasSearched = ref(false);
    const searchTypes = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "mdi:magnify" },
      { value: "email", label: "\u0E2D\u0E35\u0E40\u0E21\u0E25", icon: "mdi:email" },
      { value: "name", label: "\u0E0A\u0E37\u0E48\u0E2D", icon: "mdi:account" },
      { value: "phone", label: "\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23", icon: "mdi:phone" },
      { value: "code", label: "\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27", icon: "mdi:card-account-details" }
    ];
    const currentSearchType = computed(
      () => searchTypes.find((t) => t.value === searchType.value) || searchTypes[0]
    );
    const placeholderText = computed(() => {
      const typeLabels = {
        all: "\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22 \u0E2D\u0E35\u0E40\u0E21\u0E25, \u0E0A\u0E37\u0E48\u0E2D, \u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27",
        email: "\u0E01\u0E23\u0E2D\u0E01\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32",
        name: "\u0E01\u0E23\u0E2D\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32",
        phone: "\u0E01\u0E23\u0E2D\u0E01\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32",
        code: "\u0E01\u0E23\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32"
      };
      return typeLabels[searchType.value] || typeLabels.all;
    });
    function getUserPoints(user) {
      var _a;
      return (_a = user.points) != null ? _a : 0;
    }
    function getUserWallet(user) {
      var _a;
      return (_a = user.wallet) != null ? _a : 0;
    }
    function getTotalAvailablePoints(user) {
      const points = getUserPoints(user);
      const walletAsPoints = getUserWallet(user) * POINTS_PER_BAHT;
      return points + walletAsPoints;
    }
    function canResetPassword(user) {
      return getTotalAvailablePoints(user) >= REQUIRED_POINTS;
    }
    function getPointsToDeduct(user) {
      const userPoints = getUserPoints(user);
      return Math.min(userPoints, REQUIRED_POINTS);
    }
    function getWalletToDeduct(user) {
      const userPoints = getUserPoints(user);
      const remainingPoints = Math.max(0, REQUIRED_POINTS - userPoints);
      return Math.ceil(remainingPoints / POINTS_PER_BAHT);
    }
    useDebounceFn(async () => {
      if (!searchQuery.value.trim()) {
        usersResult.value = [];
        hasSearched.value = false;
        return;
      }
      try {
        isSearching.value = true;
        hasSearched.value = true;
        const response = await $apiFetch("/api/forgot-password/getuser", {
          method: "POST",
          body: {
            email: searchQuery.value.trim(),
            search_type: searchType.value
          }
        });
        if (response == null ? void 0 : response.users) {
          usersResult.value = structuredClone(response.users);
        } else {
          usersResult.value = [];
        }
      } catch (error) {
        console.error("Search error:", error);
        usersResult.value = [];
      } finally {
        isSearching.value = false;
      }
    }, 400);
    function highlightMatch(text, query) {
      if (!text || !query) return text;
      const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`, "gi");
      return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800 px-0.5 rounded">$1</mark>');
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "p-6 space-y-6" }, _attrs))}><div class="flex items-center justify-between"><div><h1 class="text-3xl font-bold text-gray-900 dark:text-white">\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19</h1><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E41\u0E25\u0E30\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19 (\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01)</p></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:arrow-left",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(`<span${_scopeId}>\u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01</span>`);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "mdi:arrow-left",
                class: "w-5 h-5"
              }),
              createVNode("span", null, "\u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4"><div class="flex items-start gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:information",
        class: "w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5"
      }, null, _parent));
      _push(`<div class="text-sm text-blue-700 dark:text-blue-300"><p class="font-medium mb-1">\u0E01\u0E32\u0E23\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19\u0E08\u0E30\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p><ul class="list-disc list-inside space-y-1 text-blue-600 dark:text-blue-400"><li>\u0E04\u0E48\u0E32\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23: <strong>${ssrInterpolate(REQUIRED_POINTS.toLocaleString())} \u0E41\u0E15\u0E49\u0E21</strong> \u0E15\u0E48\u0E2D\u0E04\u0E23\u0E31\u0E49\u0E07</li><li>\u0E2B\u0E31\u0E01\u0E08\u0E32\u0E01\u0E41\u0E15\u0E49\u0E21\u0E01\u0E48\u0E2D\u0E19 \u0E2A\u0E48\u0E27\u0E19\u0E17\u0E35\u0E48\u0E02\u0E32\u0E14\u0E08\u0E30\u0E2B\u0E31\u0E01\u0E08\u0E32\u0E01 Wallet \u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34</li><li>\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19: 1 \u0E1A\u0E32\u0E17 = ${ssrInterpolate(POINTS_PER_BAHT.toLocaleString())} \u0E41\u0E15\u0E49\u0E21</li></ul></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4"><div class="flex flex-wrap gap-2"><!--[-->`);
      ssrRenderList(searchTypes, (type) => {
        _push(`<button class="${ssrRenderClass([
          "flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all",
          searchType.value === type.value ? "bg-teal-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
        ])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: type.icon,
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(type.label)}</button>`);
      });
      _push(`<!--]--></div><div class="flex gap-3"><div class="flex-1 relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: isSearching.value ? "svg-spinners:ring-resize" : currentSearchType.value.icon,
        class: "w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`</div><input${ssrRenderAttr("value", searchQuery.value)} type="search" class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all"${ssrRenderAttr("placeholder", placeholderText.value)}></div>`);
      if (searchQuery.value) {
        _push(`<button class="px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:close",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="hidden sm:inline">\u0E25\u0E49\u0E32\u0E07</span></button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (hasSearched.value && usersResult.value.length > 0) {
        _push(`<div class="space-y-4"><div class="flex items-center justify-between"><h2 class="text-lg font-semibold text-gray-900 dark:text-white"> \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32 (${ssrInterpolate(usersResult.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </h2></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-4"><!--[-->`);
        ssrRenderList(usersResult.value, (user) => {
          var _a, _b, _c, _d;
          _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"><div class="h-16 bg-gradient-to-r from-teal-400 via-teal-500 to-blue-500 relative"><div class="absolute -bottom-8 left-4"><div class="w-16 h-16 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden bg-white shadow-lg"><img${ssrRenderAttr("src", user.avatar || "https://cdn.pixabay.com/photo/2016/08/08/09/17/avatar-1577909_1280.png")}${ssrRenderAttr("alt", user.name)} class="w-full h-full object-cover"></div></div><div class="absolute top-2 right-2 px-2 py-1 bg-white/20 backdrop-blur rounded text-xs text-white font-medium"> ID: ${ssrInterpolate(user.id)}</div></div><div class="pt-10 px-4 pb-4 space-y-4"><div><h3 class="text-base font-semibold text-gray-900 dark:text-white">${(_a = highlightMatch(user.name, searchQuery.value)) != null ? _a : ""}</h3><p class="text-sm text-gray-500 dark:text-gray-400">${(_b = highlightMatch(user.email, searchQuery.value)) != null ? _b : ""}</p></div><div class="flex flex-wrap gap-2">`);
          if (user.personal_code) {
            _push(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:card-account-details",
              class: "w-3 h-3"
            }, null, _parent));
            _push(`<span>${(_c = highlightMatch(user.personal_code, searchQuery.value)) != null ? _c : ""}</span></span>`);
          } else {
            _push(`<!---->`);
          }
          if (user.phone) {
            _push(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-400">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:phone",
              class: "w-3 h-3"
            }, null, _parent));
            _push(`<span>${(_d = highlightMatch(user.phone, searchQuery.value)) != null ? _d : ""}</span></span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="p-3 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-lg border border-amber-200 dark:border-amber-800"><div class="flex items-center justify-between"><div class="flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:star-circle",
            class: "w-5 h-5 text-amber-500"
          }, null, _parent));
          _push(`<div><p class="text-xs text-gray-500 dark:text-gray-400">\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p><p class="${ssrRenderClass([canResetPassword(user) ? "text-green-600 dark:text-green-400" : "text-amber-600 dark:text-amber-400", "font-bold"])}">${ssrInterpolate(getUserPoints(user).toLocaleString())}</p></div></div><div class="w-px h-10 bg-amber-200 dark:bg-amber-700"></div><div class="flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:wallet",
            class: "w-5 h-5 text-green-500"
          }, null, _parent));
          _push(`<div><p class="text-xs text-gray-500 dark:text-gray-400">Wallet</p><p class="font-bold text-green-600 dark:text-green-400">\u0E3F${ssrInterpolate(getUserWallet(user).toLocaleString())}</p></div></div><div class="w-px h-10 bg-amber-200 dark:bg-amber-700"></div><div><p class="text-xs text-gray-500 dark:text-gray-400">\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49</p><p class="font-bold text-gray-600 dark:text-gray-400">${ssrInterpolate(REQUIRED_POINTS.toLocaleString())}</p></div></div></div><div class="space-y-2">`);
          if (canResetPassword(user)) {
            _push(`<div>`);
            if (getWalletToDeduct(user) === 0) {
              _push(`<!--[--><div class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm mb-2">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "mdi:check-circle",
                class: "w-4 h-4"
              }, null, _parent));
              _push(`<span>\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D \u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19</span></div><button class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg transition-colors font-medium">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "mdi:lock-reset",
                class: "w-5 h-5"
              }, null, _parent));
              _push(` \u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19 (-${ssrInterpolate(REQUIRED_POINTS.toLocaleString())} \u0E41\u0E15\u0E49\u0E21) </button><!--]-->`);
            } else {
              _push(`<!--[--><div class="flex items-center gap-2 text-blue-600 dark:text-blue-400 text-sm mb-2">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information",
                class: "w-4 h-4"
              }, null, _parent));
              _push(`<span>\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E23\u0E48\u0E27\u0E21\u0E01\u0E31\u0E1A Wallet</span></div><button class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "mdi:wallet",
                class: "w-5 h-5"
              }, null, _parent));
              _push(` \u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19 (-${ssrInterpolate(getPointsToDeduct(user).toLocaleString())} \u0E41\u0E15\u0E49\u0E21, -\u0E3F${ssrInterpolate(getWalletToDeduct(user).toLocaleString())}) </button><!--]-->`);
            }
            _push(`</div>`);
          } else {
            _push(`<div><div class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm mb-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:alert-circle",
              class: "w-4 h-4"
            }, null, _parent));
            _push(`<span>\u0E41\u0E15\u0E49\u0E21\u0E41\u0E25\u0E30\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19 Wallet \u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E02\u0E32\u0E14\u0E2D\u0E35\u0E01 ${ssrInterpolate((REQUIRED_POINTS - getTotalAvailablePoints(user)).toLocaleString())} \u0E41\u0E15\u0E49\u0E21)</span></div><button class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors font-medium">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:plus-circle",
              class: "w-5 h-5"
            }, null, _parent));
            _push(` \u0E40\u0E15\u0E34\u0E21\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </button></div>`);
          }
          _push(`</div></div></div>`);
        });
        _push(`<!--]--></div></div>`);
      } else if (isSearching.value) {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:blocks-shuffle-3",
          class: "w-16 h-16 mx-auto text-teal-500 mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32...</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48</p></div>`);
      } else if (hasSearched.value && usersResult.value.length === 0) {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:account-search",
          class: "w-16 h-16 mx-auto text-gray-400 mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E35\u0E48\u0E15\u0E23\u0E07\u0E01\u0E31\u0E1A &quot;${ssrInterpolate(searchQuery.value)}&quot;</p></div>`);
      } else {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:account-key",
          class: "w-16 h-16 mx-auto text-teal-500 mb-4"
        }, null, _parent));
        _push(`<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E43\u0E19\u0E0A\u0E48\u0E2D\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19</p></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Admin/Resetpassword.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Resetpassword-Bu_fnpHN.mjs.map
