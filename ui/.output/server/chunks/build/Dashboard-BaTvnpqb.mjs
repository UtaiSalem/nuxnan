import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createTextVNode, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderComponent, ssrRenderStyle, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { u as usePoints } from './usePoints-DipNhVzo.mjs';
import { u as useWallet } from './useWallet-DPSZCLqI.mjs';
import { u as useRewards } from './useRewards-lE9TTj1H.mjs';
import { u as useGamification } from './useGamification-BliN7lma.mjs';
import { _ as _export_sfc, f as useHead, d as useAuthStore } from './server.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Dashboard",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Dashboard | Nuxnan"
    });
    const {
      points,
      user,
      getLevelProgress,
      formatPoints
    } = usePoints();
    const {
      wallet,
      formatMoney
    } = useWallet();
    const {
      formatPoints: formatRewardsPoints
    } = useRewards();
    useGamification();
    const authStore = useAuthStore();
    ref(true);
    const streakInfo = ref(null);
    const achievements = ref([]);
    const recentTransactions = ref([]);
    const leaderboardSummary = ref(null);
    const topUsers = ref([]);
    const featuredRewards = ref([]);
    const userName = computed(() => {
      var _a, _b;
      return ((_a = user.value) == null ? void 0 : _a.name) || ((_b = authStore.user) == null ? void 0 : _b.name) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49";
    });
    const currentLevel = computed(() => {
      var _a;
      return ((_a = user.value) == null ? void 0 : _a.level) || 1;
    });
    const currentXP = computed(() => {
      var _a;
      return ((_a = user.value) == null ? void 0 : _a.current_xp) || 0;
    });
    const xpForNextLevel = computed(() => {
      var _a;
      return ((_a = user.value) == null ? void 0 : _a.xp_for_next_level) || 100;
    });
    const levelProgress = computed(() => getLevelProgress());
    const greeting = computed(() => {
      const hour = (/* @__PURE__ */ new Date()).getHours();
      if (hour < 12) return "\u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E15\u0E2D\u0E19\u0E40\u0E0A\u0E49\u0E32";
      if (hour < 18) return "\u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E15\u0E2D\u0E19\u0E1A\u0E48\u0E32\u0E22";
      return "\u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E15\u0E2D\u0E19\u0E04\u0E48\u0E33";
    });
    const currentDate = computed(() => {
      return (/* @__PURE__ */ new Date()).toLocaleDateString("th-TH", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    });
    const getTransactionIcon = (type) => {
      const icons = {
        earn: "mdi:arrow-up-circle",
        spend: "mdi:arrow-down-circle",
        refund: "mdi:refresh-circle",
        transfer: "mdi:swap-horizontal",
        conversion: "mdi:currency-exchange"
      };
      return icons[type] || "mdi:star-circle";
    };
    const getTransactionColor = (type) => {
      const colors = {
        earn: "text-green-500",
        spend: "text-red-500",
        refund: "text-blue-500",
        transfer: "text-orange-500",
        conversion: "text-purple-500"
      };
      return colors[type] || "text-gray-500";
    };
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString("th-TH", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const getStreakIcon = (streak) => {
      if (streak >= 30) return "\u{1F525}\u{1F525}\u{1F525}";
      if (streak >= 14) return "\u{1F525}\u{1F525}";
      if (streak >= 7) return "\u{1F525}";
      return "\u26A1";
    };
    const getTypeLabel = (type) => {
      const labels = {
        earn: "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21",
        spend: "\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21",
        refund: "\u0E04\u0E37\u0E19\u0E41\u0E15\u0E49\u0E21",
        transfer: "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21",
        conversion: "\u0E41\u0E25\u0E01\u0E41\u0E15\u0E49\u0E21"
      };
      return labels[type] || type;
    };
    const getRankBadgeClass = (index) => {
      const classes = [
        "bg-gradient-to-br from-amber-400 to-amber-500 text-white",
        // 1st - ทองนุ่มนวล
        "bg-gradient-to-br from-slate-300 to-slate-400 text-white",
        // 2nd - เงินนุ่มนวล
        "bg-gradient-to-br from-orange-300 to-orange-400 text-white",
        // 3rd - ทองแดงนุ่มนวล
        "bg-sky-400 text-white",
        // 4th - ฟ้านุ่มนวล
        "bg-emerald-400 text-white"
        // 5th - เขียวนุ่มนวล
      ];
      return classes[index] || "bg-slate-300 text-gray-700";
    };
    const getRewardTypeIcon = (type) => {
      const icons = {
        wallet: "mdi:wallet",
        badge: "mdi:medal",
        feature: "mdi:star-shooting",
        discount: "mdi:percent"
      };
      return icons[type] || "mdi:gift";
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "dashboard-page min-h-screen bg-gray-50 dark:bg-vikinger-dark" }, _attrs))} data-v-6ccff022><div class="relative overflow-hidden" data-v-6ccff022><div class="absolute inset-0 bg-gradient-to-r from-vikinger-dark via-vikinger-purple to-vikinger-dark" data-v-6ccff022></div><div class="absolute inset-0 bg-[url(&#39;/images/patterns/hexagon-pattern.svg&#39;)] opacity-5" data-v-6ccff022></div><div class="absolute inset-0 overflow-hidden" data-v-6ccff022><div class="absolute top-10 left-10 w-2 h-2 bg-vikinger-cyan rounded-full animate-pulse" data-v-6ccff022></div><div class="absolute top-20 right-20 w-3 h-3 bg-vikinger-purple rounded-full animate-bounce" data-v-6ccff022></div><div class="absolute bottom-10 left-1/4 w-2 h-2 bg-pink-400 rounded-full animate-ping" data-v-6ccff022></div><div class="absolute top-1/2 right-1/3 w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse" data-v-6ccff022></div></div><div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" data-v-6ccff022><div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6" data-v-6ccff022><div class="flex items-center gap-6" data-v-6ccff022><div class="relative" data-v-6ccff022><div class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-vikinger-purple to-vikinger-cyan p-1 shadow-vikinger" data-v-6ccff022><img${ssrRenderAttr("src", ((_a = unref(authStore).user) == null ? void 0 : _a.avatar) || ((_b = unref(authStore).user) == null ? void 0 : _b.profile_photo_url) || "/images/default-avatar.png")}${ssrRenderAttr("alt", userName.value)} class="w-full h-full rounded-full object-cover border-2 border-vikinger-dark" data-v-6ccff022></div><div class="absolute -bottom-2 -right-2 w-10 h-10 bg-gradient-vikinger rounded-full flex items-center justify-center text-white font-bold text-sm border-3 border-vikinger-dark shadow-lg" data-v-6ccff022>${ssrInterpolate(currentLevel.value)}</div></div><div data-v-6ccff022><p class="text-vikinger-cyan text-sm font-medium mb-1" data-v-6ccff022>${ssrInterpolate(currentDate.value)}</p><h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2" data-v-6ccff022>${ssrInterpolate(greeting.value)}, <span class="bg-gradient-to-r from-vikinger-cyan to-vikinger-purple bg-clip-text text-transparent" data-v-6ccff022>${ssrInterpolate(userName.value)}</span>! <span class="inline-block animate-wave" data-v-6ccff022>\u{1F44B}</span></h1><p class="text-gray-400 text-sm md:text-base" data-v-6ccff022>\u0E22\u0E34\u0E19\u0E14\u0E35\u0E15\u0E49\u0E2D\u0E19\u0E23\u0E31\u0E1A\u0E2A\u0E39\u0E48\u0E01\u0E32\u0E23\u0E1C\u0E08\u0E0D\u0E20\u0E31\u0E22\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div></div><div class="flex flex-wrap items-center gap-3" data-v-6ccff022><div class="relative group" data-v-6ccff022><div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300" data-v-6ccff022></div><div class="relative bg-vikinger-dark-100 border border-vikinger-purple/30 rounded-xl px-5 py-3 flex items-center gap-3" data-v-6ccff022><div class="w-10 h-10 bg-gradient-vikinger rounded-lg flex items-center justify-center shadow-vikinger" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:shield-star",
        class: "w-5 h-5 text-white"
      }, null, _parent));
      _push(`</div><div data-v-6ccff022><p class="text-xs text-gray-400 uppercase tracking-wider" data-v-6ccff022>Level</p><p class="text-xl font-bold text-white" data-v-6ccff022>Lv. ${ssrInterpolate(currentLevel.value)}</p></div></div></div><div class="relative group" data-v-6ccff022><div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300" data-v-6ccff022></div><div class="relative bg-vikinger-dark-100 border border-vikinger-cyan/30 rounded-xl px-5 py-3" data-v-6ccff022><div class="flex items-center gap-3 mb-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:lightning-bolt",
        class: "w-5 h-5 text-vikinger-cyan"
      }, null, _parent));
      _push(`<span class="text-xs text-gray-400 uppercase tracking-wider" data-v-6ccff022>Experience</span></div><div class="flex items-center gap-2" data-v-6ccff022><div class="w-24 h-2 bg-vikinger-dark rounded-full overflow-hidden" data-v-6ccff022><div class="h-full bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${levelProgress.value}%` })}" data-v-6ccff022></div></div><span class="text-xs text-gray-400" data-v-6ccff022>${ssrInterpolate(currentXP.value)}/${ssrInterpolate(xpForNextLevel.value)}</span></div></div></div>`);
      if (streakInfo.value) {
        _push(`<div class="relative group" data-v-6ccff022><div class="absolute -inset-0.5 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300" data-v-6ccff022></div><div class="relative bg-vikinger-dark-100 border border-orange-500/30 rounded-xl px-5 py-3 flex items-center gap-3" data-v-6ccff022><div class="text-2xl" data-v-6ccff022>${ssrInterpolate(getStreakIcon(streakInfo.value.current_streak))}</div><div data-v-6ccff022><p class="text-xs text-gray-400 uppercase tracking-wider" data-v-6ccff022>Streak</p><p class="text-xl font-bold text-orange-400" data-v-6ccff022>${ssrInterpolate(streakInfo.value.current_streak)} \u0E27\u0E31\u0E19</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></div><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" data-v-6ccff022><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" data-v-6ccff022><div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow overflow-hidden" data-v-6ccff022><div class="flex items-center justify-between mb-4" data-v-6ccff022><div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:star",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Points",
        class: "text-white/80 hover:text-white text-sm whitespace-nowrap"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><p class="text-white/80 text-sm mb-1" data-v-6ccff022>\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21</p><p class="text-xl sm:text-2xl lg:text-xl xl:text-2xl font-bold mb-2 truncate"${ssrRenderAttr("title", unref(formatPoints)(unref(points)))} data-v-6ccff022>${ssrInterpolate(unref(formatPoints)(unref(points)))}</p><div class="bg-white/10 rounded-lg p-2" data-v-6ccff022><div class="flex items-center justify-between text-xs mb-1" data-v-6ccff022><span data-v-6ccff022>XP Progress</span><span data-v-6ccff022>${ssrInterpolate(currentXP.value)} / ${ssrInterpolate(xpForNextLevel.value)}</span></div><div class="h-2 bg-white/20 rounded-full overflow-hidden" data-v-6ccff022><div class="h-full bg-white rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${levelProgress.value}%` })}" data-v-6ccff022></div></div></div></div><div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow overflow-hidden" data-v-6ccff022><div class="flex items-center justify-between mb-4" data-v-6ccff022><div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:wallet",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Wallet",
        class: "text-white/80 hover:text-white text-sm whitespace-nowrap"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><p class="text-white/80 text-sm mb-1" data-v-6ccff022>\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</p><p class="text-xl sm:text-2xl lg:text-xl xl:text-2xl font-bold mb-2 truncate"${ssrRenderAttr("title", unref(formatMoney)(unref(wallet)))} data-v-6ccff022>${ssrInterpolate(unref(formatMoney)(unref(wallet)))}</p><p class="text-white/70 text-xs" data-v-6ccff022>1,080 \u0E41\u0E15\u0E49\u0E21 = 1 \u0E1A\u0E32\u0E17</p></div><div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow" data-v-6ccff022><div class="flex items-center justify-between mb-4" data-v-6ccff022><div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:trophy",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/badges",
        class: "text-white/80 hover:text-white text-sm"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><p class="text-white/80 text-sm mb-1" data-v-6ccff022>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p><p class="text-3xl font-bold mb-2" data-v-6ccff022>${ssrInterpolate(achievements.value.filter((a) => a.completed).length)} / ${ssrInterpolate(achievements.value.length)}</p><p class="text-white/70 text-xs" data-v-6ccff022>${ssrInterpolate(achievements.value.length > 0 ? Math.round(achievements.value.filter((a) => a.completed).length / achievements.value.length * 100) : 0)}% \u0E1A\u0E23\u0E23\u0E25\u0E38\u0E41\u0E25\u0E49\u0E27 </p></div><div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow" data-v-6ccff022><div class="flex items-center justify-between mb-4" data-v-6ccff022><div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:podium",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Gamification",
        class: "text-white/80 hover:text-white text-sm"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><p class="text-white/80 text-sm mb-1" data-v-6ccff022>\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p><p class="text-3xl font-bold mb-2" data-v-6ccff022> #${ssrInterpolate(((_c = leaderboardSummary.value) == null ? void 0 : _c.points_rank) || "-")}</p><p class="text-white/70 text-xs" data-v-6ccff022> \u0E08\u0E32\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(((_d = leaderboardSummary.value) == null ? void 0 : _d.total_users) || 0)} \u0E04\u0E19 </p></div></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-8" data-v-6ccff022><div class="lg:col-span-2 space-y-8" data-v-6ccff022><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-6ccff022><h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:lightning-bolt",
        class: "w-5 h-5 text-yellow-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E17\u0E33\u0E14\u0E48\u0E27\u0E19 </h2><div class="grid grid-cols-2 md:grid-cols-4 gap-4" data-v-6ccff022>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Points",
        class: "group bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-6ccff022${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:star-plus",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-6ccff022${_scopeId}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-6ccff022${_scopeId}>\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08\u0E15\u0E48\u0E32\u0E07\u0E46</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:star-plus",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08\u0E15\u0E48\u0E32\u0E07\u0E46")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Wallet",
        class: "group bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-6ccff022${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:currency-exchange",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-6ccff022${_scopeId}>\u0E41\u0E25\u0E01\u0E41\u0E15\u0E49\u0E21</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-6ccff022${_scopeId}>\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:currency-exchange",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E41\u0E25\u0E01\u0E41\u0E15\u0E49\u0E21"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Rewards",
        class: "group bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-6ccff022${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:gift",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-6ccff022${_scopeId}>\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-6ccff022${_scopeId}>\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:gift",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Gamification",
        class: "group bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" data-v-6ccff022${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:trophy",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><p class="font-semibold text-gray-900 dark:text-white text-sm" data-v-6ccff022${_scopeId}>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-v-6ccff022${_scopeId}>\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p>`);
          } else {
            return [
              createVNode("div", { class: "w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:trophy",
                  class: "w-6 h-6 text-white"
                })
              ]),
              createVNode("p", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08"),
              createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-1" }, "\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-6ccff022><div class="flex items-center justify-between mb-6" data-v-6ccff022><h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:history",
        class: "w-5 h-5 text-blue-500"
      }, null, _parent));
      _push(` \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 </h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Points",
        class: "text-primary-500 hover:text-primary-600 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (recentTransactions.value.length > 0) {
        _push(`<div class="space-y-3" data-v-6ccff022><!--[-->`);
        ssrRenderList(recentTransactions.value, (transaction) => {
          _push(`<div class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" data-v-6ccff022><div class="${ssrRenderClass([getTransactionColor(transaction.type), "w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"])}" data-v-6ccff022>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getTransactionIcon(transaction.type),
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</div><div class="flex-grow min-w-0" data-v-6ccff022><p class="font-medium text-gray-900 dark:text-white text-sm truncate" data-v-6ccff022>${ssrInterpolate(transaction.description || getTypeLabel(transaction.type))}</p><p class="text-xs text-gray-500 dark:text-gray-400" data-v-6ccff022>${ssrInterpolate(formatDate(transaction.created_at))}</p></div><div class="${ssrRenderClass([getTransactionColor(transaction.type), "text-sm font-bold flex-shrink-0"])}" data-v-6ccff022>${ssrInterpolate(["earn", "refund"].includes(transaction.type) ? "+" : "-")}${ssrInterpolate(unref(formatPoints)(transaction.amount))}</div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-8 text-gray-500 dark:text-gray-400" data-v-6ccff022>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:history",
          class: "w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"
        }, null, _parent));
        _push(`<p data-v-6ccff022>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</p><p class="text-sm mt-1" data-v-6ccff022>\u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21!</p></div>`);
      }
      _push(`</div>`);
      if (featuredRewards.value.length > 0) {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-6ccff022><div class="flex items-center justify-between mb-6" data-v-6ccff022><h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-6ccff022>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:gift-open",
          class: "w-5 h-5 text-amber-500"
        }, null, _parent));
        _push(` \u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E41\u0E19\u0E30\u0E19\u0E33 </h2>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/Earn/Rewards",
          class: "text-primary-500 hover:text-primary-600 text-sm font-medium"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 `);
            } else {
              return [
                createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-v-6ccff022><!--[-->`);
        ssrRenderList(featuredRewards.value, (reward) => {
          _push(`<div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl p-4 hover:shadow-md transition-all" data-v-6ccff022><div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center mb-3" data-v-6ccff022>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getRewardTypeIcon(reward.type),
            class: "w-6 h-6 text-white"
          }, null, _parent));
          _push(`</div><h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 line-clamp-2" data-v-6ccff022>${ssrInterpolate(reward.name)}</h3><div class="flex items-center gap-1 text-amber-600 dark:text-amber-400 text-sm font-bold" data-v-6ccff022>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:star",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(formatRewardsPoints)(reward.points_required))} \u0E41\u0E15\u0E49\u0E21 </div></div>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="space-y-8" data-v-6ccff022><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden" data-v-6ccff022><div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 text-white" data-v-6ccff022><div class="flex items-center justify-between" data-v-6ccff022><div class="flex items-center gap-2" data-v-6ccff022><div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:trophy",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</div><div data-v-6ccff022><h3 class="font-semibold text-sm" data-v-6ccff022>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h3><p class="text-xs text-white/80" data-v-6ccff022>Achievements</p></div></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/badges",
        class: "text-xs bg-white/20 px-3 py-1 rounded-full hover:bg-white/30 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="p-4" data-v-6ccff022>`);
      if (achievements.value.filter((a) => a.completed).length > 0) {
        _push(`<div class="space-y-3" data-v-6ccff022><!--[-->`);
        ssrRenderList(achievements.value.filter((a) => a.completed).slice(0, 3), (achievement) => {
          _push(`<div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" data-v-6ccff022><div class="w-10 h-10 rounded-full flex items-center justify-center bg-gradient-to-br from-yellow-400 to-amber-500" data-v-6ccff022>`);
          if (achievement.icon) {
            _push(`<img${ssrRenderAttr("src", achievement.icon)}${ssrRenderAttr("alt", achievement.name)} class="w-6 h-6" data-v-6ccff022>`);
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:trophy",
              class: "w-5 h-5 text-white"
            }, null, _parent));
          }
          _push(`</div><div class="flex-grow min-w-0" data-v-6ccff022><p class="text-sm font-medium text-gray-900 dark:text-white truncate" data-v-6ccff022>${ssrInterpolate(achievement.name)}</p><p class="text-xs text-gray-500 dark:text-gray-400 truncate" data-v-6ccff022>${ssrInterpolate(achievement.description)}</p></div>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:check-circle",
            class: "w-5 h-5 text-green-500 flex-shrink-0"
          }, null, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400" data-v-6ccff022>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:trophy-outline",
          class: "w-8 h-8 mx-auto mb-2"
        }, null, _parent));
        _push(`<p class="text-sm" data-v-6ccff022>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div>`);
      }
      if (achievements.value.filter((a) => !a.completed && a.progress > 0).length > 0) {
        _push(`<div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700" data-v-6ccff022><p class="text-xs text-gray-500 dark:text-gray-400 mb-3" data-v-6ccff022>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p><div class="space-y-2" data-v-6ccff022><!--[-->`);
        ssrRenderList(achievements.value.filter((a) => !a.completed && a.progress > 0).slice(0, 2), (achievement) => {
          _push(`<div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3" data-v-6ccff022><div class="flex items-center justify-between mb-2" data-v-6ccff022><span class="text-sm font-medium text-gray-900 dark:text-white" data-v-6ccff022>${ssrInterpolate(achievement.name)}</span><span class="text-xs text-gray-500" data-v-6ccff022>${ssrInterpolate(achievement.progress)}%</span></div><div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden" data-v-6ccff022><div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${achievement.progress}%` })}" data-v-6ccff022></div></div></div>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" data-v-6ccff022><div class="flex items-center justify-between mb-4" data-v-6ccff022><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:podium",
        class: "w-5 h-5 text-rose-500"
      }, null, _parent));
      _push(` \u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 </h2>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/Earn/Gamification",
        class: "text-primary-500 hover:text-primary-600 text-sm font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 \u2192 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (topUsers.value.length > 0) {
        _push(`<div class="space-y-3" data-v-6ccff022><!--[-->`);
        ssrRenderList(topUsers.value, (user2, index) => {
          _push(`<div class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" data-v-6ccff022><div class="${ssrRenderClass([getRankBadgeClass(index), "w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"])}" data-v-6ccff022>${ssrInterpolate(index + 1)}</div><div class="flex-grow min-w-0" data-v-6ccff022><p class="text-sm font-medium text-gray-900 dark:text-white truncate" data-v-6ccff022>${ssrInterpolate(user2.name)}</p></div><div class="text-sm font-bold text-amber-600 dark:text-amber-400 flex-shrink-0" data-v-6ccff022>${ssrInterpolate(unref(formatPoints)(user2.total_points))}</div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400" data-v-6ccff022>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:podium",
          class: "w-8 h-8 mx-auto mb-2"
        }, null, _parent));
        _push(`<p class="text-sm" data-v-6ccff022>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A...</p></div>`);
      }
      _push(`</div><div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white" data-v-6ccff022><div class="flex items-center gap-2 mb-4" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:lightbulb",
        class: "w-5 h-5 text-yellow-300"
      }, null, _parent));
      _push(`<h3 class="font-bold" data-v-6ccff022>\u0E40\u0E04\u0E25\u0E47\u0E14\u0E25\u0E31\u0E1A\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21</h3></div><ul class="space-y-2 text-sm text-white/90" data-v-6ccff022><li class="flex items-start gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-circle",
        class: "w-4 h-4 mt-0.5 flex-shrink-0"
      }, null, _parent));
      _push(`<span data-v-6ccff022>\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E17\u0E38\u0E01\u0E27\u0E31\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E41\u0E25\u0E30\u0E2A\u0E30\u0E2A\u0E21 Streak</span></li><li class="flex items-start gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-circle",
        class: "w-4 h-4 mt-0.5 flex-shrink-0"
      }, null, _parent));
      _push(`<span data-v-6ccff022>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E25\u0E30\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1E\u0E34\u0E48\u0E21</span></li><li class="flex items-start gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-circle",
        class: "w-4 h-4 mt-0.5 flex-shrink-0"
      }, null, _parent));
      _push(`<span data-v-6ccff022>\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E41\u0E25\u0E30\u0E41\u0E0A\u0E23\u0E4C\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E1E\u0E34\u0E40\u0E28\u0E29</span></li><li class="flex items-start gap-2" data-v-6ccff022>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-circle",
        class: "w-4 h-4 mt-0.5 flex-shrink-0"
      }, null, _parent));
      _push(`<span data-v-6ccff022>\u0E41\u0E19\u0E30\u0E19\u0E33\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E21\u0E32\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</span></li></ul></div></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Dashboard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-6ccff022"]]);

export { Dashboard as default };
//# sourceMappingURL=Dashboard-BaTvnpqb.mjs.map
