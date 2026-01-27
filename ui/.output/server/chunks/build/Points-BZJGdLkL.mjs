import { defineComponent, ref, mergeProps, unref, withCtx, createVNode, toDisplayString, createBlock, openBlock, Fragment, renderList, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrRenderTeleport, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
import { _ as _sfc_main$1 } from './BaseCard-Baxif1fS.mjs';
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

const useAdminPoints = () => {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase;
  const isLoading = ref(false);
  const error = ref(null);
  const getStats = async () => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/points/stats`, {
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to get stats:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const getRules = async () => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/points/rules`, {
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to get rules:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const createRule = async (ruleData) => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/points/rules`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${authStore.token}` },
        body: ruleData
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to create rule:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const updateRule = async (id, ruleData) => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/points/rules/${id}`, {
        method: "PUT",
        headers: { "Authorization": `Bearer ${authStore.token}` },
        body: ruleData
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to update rule:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const deleteRule = async (id) => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/points/rules/${id}`, {
        method: "DELETE",
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success;
    } catch (err) {
      console.error("Failed to delete rule:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const adjustPoints = async (userId, adjustmentData) => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/points/users/${userId}/adjust`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${authStore.token}` },
        body: adjustmentData
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to adjust points:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getUserTransactions = async (userId, params = {}) => {
    try {
      isLoading.value = true;
      const queryParams = new URLSearchParams(params).toString();
      const response = await $fetch(`${apiBase}/api/admin/points/users/${userId}/transactions?${queryParams}`, {
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to get user transactions:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const getLeaderboard = async (params = {}) => {
    try {
      isLoading.value = true;
      const queryParams = new URLSearchParams(params).toString();
      const response = await $fetch(`${apiBase}/api/admin/points/leaderboard?${queryParams}`, {
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to get leaderboard:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const getAnalytics = async (filters = {}) => {
    try {
      isLoading.value = true;
      const queryParams = new URLSearchParams(filters).toString();
      const response = await $fetch(`${apiBase}/api/admin/points/analytics?${queryParams}`, {
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to get analytics:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const formatPoints = (points) => {
    return new Intl.NumberFormat("th-TH").format(Math.round(points));
  };
  const formatPercentage = (value) => {
    return `${value.toFixed(2)}%`;
  };
  return {
    isLoading,
    error,
    getStats,
    getRules,
    createRule,
    updateRule,
    deleteRule,
    adjustPoints,
    getUserTransactions,
    getLeaderboard,
    getAnalytics,
    formatPoints,
    formatPercentage
  };
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Points",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E41\u0E15\u0E49\u0E21 - Admin"
    });
    useAuthStore();
    const {
      getStats,
      getRules,
      deleteRule,
      getLeaderboard,
      getAnalytics,
      isLoading
    } = useAdminPoints();
    const activeTab = ref("overview");
    const stats = ref(null);
    const rules = ref([]);
    const leaderboard = ref([]);
    const analytics = ref(null);
    const showRuleModal = ref(false);
    const editingRule = ref(null);
    const ruleForm = ref({
      name: "",
      action_type: "",
      points_earned: 0,
      points_spent: 0,
      daily_limit: 0,
      is_active: true,
      description: ""
    });
    const showAdjustModal = ref(false);
    const adjustForm = ref({
      user_id: "",
      amount: 0,
      type: "add",
      // 'add' | 'deduct' | 'set'
      reason: ""
    });
    const actionTypes = [
      { value: "view_ad", label: "\u0E14\u0E39\u0E42\u0E06\u0E29\u0E13\u0E32" },
      { value: "post", label: "\u0E42\u0E1E\u0E2A\u0E15\u0E4C" },
      { value: "comment", label: "\u0E04\u0E2D\u0E21\u0E40\u0E21\u0E19\u0E15\u0E4C" },
      { value: "like", label: "\u0E01\u0E14\u0E16\u0E39\u0E01\u0E43\u0E08" },
      { value: "share", label: "\u0E41\u0E0A\u0E23\u0E4C" },
      { value: "donation", label: "\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04" },
      { value: "referral", label: "\u0E41\u0E19\u0E30\u0E19\u0E33\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19" },
      { value: "quiz", label: "\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A" },
      { value: "course", label: "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E04\u0E2D\u0E23\u0E4C\u0E2A" },
      { value: "poll", label: "\u0E42\u0E2B\u0E27\u0E15\u0E42\u0E1E\u0E25" },
      { value: "streak", label: "\u0E40\u0E02\u0E49\u0E32\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E01\u0E31\u0E19" },
      { value: "daily_login", label: "\u0E25\u0E47\u0E2D\u0E01\u0E2D\u0E34\u0E19\u0E23\u0E32\u0E22\u0E27\u0E31\u0E19" }
    ];
    const loadData = async () => {
      try {
        const [statsData, rulesData, leaderboardData, analyticsData] = await Promise.all([
          getStats(),
          getRules(),
          getLeaderboard({ limit: 10 }),
          getAnalytics()
        ]);
        if (statsData) stats.value = statsData;
        if (rulesData) rules.value = rulesData.rules || rulesData;
        if (leaderboardData) leaderboard.value = leaderboardData.leaderboard || leaderboardData;
        if (analyticsData) analytics.value = analyticsData;
      } catch (error) {
        console.error("Failed to load admin data:", error);
      }
    };
    const openRuleModal = (rule) => {
      if (rule) {
        editingRule.value = rule;
        ruleForm.value = { ...rule };
      } else {
        editingRule.value = null;
        ruleForm.value = {
          name: "",
          action_type: "",
          points_earned: 0,
          points_spent: 0,
          daily_limit: 0,
          is_active: true,
          description: ""
        };
      }
      showRuleModal.value = true;
    };
    const handleDeleteRule = async (ruleId) => {
      if (confirm("\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E0E\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?")) {
        try {
          await deleteRule(ruleId);
          await loadData();
        } catch (error) {
          console.error("Failed to delete rule:", error);
        }
      }
    };
    const formatNumber = (value) => {
      return new Intl.NumberFormat("th-TH").format(value);
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-7xl mx-auto"><div class="flex items-center justify-between mb-8"><div><h1 class="text-3xl font-bold text-gray-900 dark:text-white">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E23\u0E30\u0E1A\u0E1A\u0E41\u0E15\u0E49\u0E21</h1><p class="text-gray-600 dark:text-gray-400">Admin Points Management</p></div><div class="flex gap-3"><button class="px-4 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:account-edit",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E1B\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </button></div></div>`);
      if (stats.value) {
        _push(`<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">`);
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:star-circle",
                class: "w-7 h-7 text-amber-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatNumber(stats.value.total_points || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E43\u0E19\u0E23\u0E30\u0E1A\u0E1A</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:star-circle",
                      class: "w-7 h-7 text-amber-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatNumber(stats.value.total_points || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E43\u0E19\u0E23\u0E30\u0E1A\u0E1A")
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:trending-up",
                class: "w-7 h-7 text-green-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatNumber(stats.value.total_points_earned || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E41\u0E08\u0E01\u0E44\u0E1B</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:trending-up",
                      class: "w-7 h-7 text-green-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatNumber(stats.value.total_points_earned || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E41\u0E08\u0E01\u0E44\u0E1B")
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:trending-down",
                class: "w-7 h-7 text-red-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatNumber(stats.value.total_points_spent || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49\u0E44\u0E1B</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:trending-down",
                      class: "w-7 h-7 text-red-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatNumber(stats.value.total_points_spent || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49\u0E44\u0E1B")
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:account-group",
                class: "w-7 h-7 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatNumber(stats.value.active_users || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E35\u0E48\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:account-group",
                      class: "w-7 h-7 text-blue-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatNumber(stats.value.active_users || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E35\u0E48\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21")
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex gap-2 mb-6 overflow-x-auto pb-2"><!--[-->`);
      ssrRenderList([
        { key: "overview", label: "\u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21", icon: "mdi:view-dashboard" },
        { key: "rules", label: "\u0E01\u0E0E\u0E01\u0E32\u0E23\u0E44\u0E14\u0E49\u0E41\u0E15\u0E49\u0E21", icon: "mdi:cog" },
        { key: "users", label: "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49", icon: "mdi:account-group" },
        { key: "analytics", label: "\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C", icon: "mdi:chart-bar" }
      ], (tab) => {
        _push(`<button class="${ssrRenderClass([activeTab.value === tab.key ? "bg-primary-500 text-white shadow" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700", "px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(tab.label)}</button>`);
      });
      _push(`<!--]--></div>`);
      if (unref(isLoading)) {
        _push(`<div class="py-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:loading",
          class: "w-12 h-12 text-primary-500 animate-spin mx-auto"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<div>`);
        if (activeTab.value === "overview") {
          _push(`<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">`);
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              var _a, _b, _c, _d, _e, _f, _g, _h;
              if (_push2) {
                _push2(`<div class="p-2"${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"${_scopeId}>\u0E2A\u0E16\u0E34\u0E15\u0E34\u0E23\u0E32\u0E22\u0E27\u0E31\u0E19</h3><div class="space-y-4"${_scopeId}><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E41\u0E08\u0E01\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49</span><span class="font-bold text-green-500"${_scopeId}>+${ssrInterpolate(formatNumber(((_a = stats.value) == null ? void 0 : _a.daily_earnings) || 0))}</span></div><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E43\u0E0A\u0E49\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49</span><span class="font-bold text-red-500"${_scopeId}>-${ssrInterpolate(formatNumber(((_b = stats.value) == null ? void 0 : _b.daily_spending) || 0))}</span></div><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E41\u0E08\u0E01\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E19\u0E35\u0E49</span><span class="font-bold text-green-500"${_scopeId}>+${ssrInterpolate(formatNumber(((_c = stats.value) == null ? void 0 : _c.weekly_earnings) || 0))}</span></div><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E41\u0E08\u0E01\u0E40\u0E14\u0E37\u0E2D\u0E19\u0E19\u0E35\u0E49</span><span class="font-bold text-green-500"${_scopeId}>+${ssrInterpolate(formatNumber(((_d = stats.value) == null ? void 0 : _d.monthly_earnings) || 0))}</span></div></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "p-2" }, [
                    createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white mb-4" }, "\u0E2A\u0E16\u0E34\u0E15\u0E34\u0E23\u0E32\u0E22\u0E27\u0E31\u0E19"),
                    createVNode("div", { class: "space-y-4" }, [
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E41\u0E08\u0E01\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49"),
                        createVNode("span", { class: "font-bold text-green-500" }, "+" + toDisplayString(formatNumber(((_e = stats.value) == null ? void 0 : _e.daily_earnings) || 0)), 1)
                      ]),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E43\u0E0A\u0E49\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49"),
                        createVNode("span", { class: "font-bold text-red-500" }, "-" + toDisplayString(formatNumber(((_f = stats.value) == null ? void 0 : _f.daily_spending) || 0)), 1)
                      ]),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E41\u0E08\u0E01\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E19\u0E35\u0E49"),
                        createVNode("span", { class: "font-bold text-green-500" }, "+" + toDisplayString(formatNumber(((_g = stats.value) == null ? void 0 : _g.weekly_earnings) || 0)), 1)
                      ]),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E41\u0E08\u0E01\u0E40\u0E14\u0E37\u0E2D\u0E19\u0E19\u0E35\u0E49"),
                        createVNode("span", { class: "font-bold text-green-500" }, "+" + toDisplayString(formatNumber(((_h = stats.value) == null ? void 0 : _h.monthly_earnings) || 0)), 1)
                      ])
                    ])
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="p-2"${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"${_scopeId}>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14</h3>`);
                if (leaderboard.value.length > 0) {
                  _push2(`<div class="space-y-3"${_scopeId}><!--[-->`);
                  ssrRenderList(leaderboard.value.slice(0, 5), (user, index) => {
                    _push2(`<div class="flex items-center gap-3"${_scopeId}><div class="${ssrRenderClass([{
                      "bg-amber-500 text-white": index === 0,
                      "bg-slate-400 text-white": index === 1,
                      "bg-orange-400 text-white": index === 2,
                      "bg-sky-400 text-white": index === 3,
                      "bg-emerald-400 text-white": index === 4,
                      "bg-slate-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300": index > 4
                    }, "w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"])}"${_scopeId}>${ssrInterpolate(index + 1)}</div><img${ssrRenderAttr("src", user.avatar || "/images/default-avatar.png")} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div class="flex-grow"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(user.name)}</p></div><span class="font-bold text-amber-500"${_scopeId}>${ssrInterpolate(formatNumber(user.points))}</span></div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<div class="text-center py-4 text-gray-500"${_scopeId}> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </div>`);
                }
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "p-2" }, [
                    createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white mb-4" }, "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14"),
                    leaderboard.value.length > 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "space-y-3"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(leaderboard.value.slice(0, 5), (user, index) => {
                        return openBlock(), createBlock("div", {
                          key: user.id,
                          class: "flex items-center gap-3"
                        }, [
                          createVNode("div", {
                            class: ["w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm", {
                              "bg-amber-500 text-white": index === 0,
                              "bg-slate-400 text-white": index === 1,
                              "bg-orange-400 text-white": index === 2,
                              "bg-sky-400 text-white": index === 3,
                              "bg-emerald-400 text-white": index === 4,
                              "bg-slate-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300": index > 4
                            }]
                          }, toDisplayString(index + 1), 3),
                          createVNode("img", {
                            src: user.avatar || "/images/default-avatar.png",
                            class: "w-10 h-10 rounded-full object-cover"
                          }, null, 8, ["src"]),
                          createVNode("div", { class: "flex-grow" }, [
                            createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(user.name), 1)
                          ]),
                          createVNode("span", { class: "font-bold text-amber-500" }, toDisplayString(formatNumber(user.points)), 1)
                        ]);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "text-center py-4 text-gray-500"
                    }, " \u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 "))
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "rules") {
          _push(`<div><div class="flex justify-end mb-4"><button class="px-4 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:plus",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E01\u0E0E\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="overflow-x-auto"${_scopeId}><table class="w-full"${_scopeId}><thead${_scopeId}><tr class="border-b border-gray-200 dark:border-gray-700"${_scopeId}><th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E0E</th><th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</th><th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49</th><th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E08\u0E33\u0E01\u0E31\u0E14/\u0E27\u0E31\u0E19</th><th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="text-right py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody${_scopeId}><!--[-->`);
                ssrRenderList(rules.value, (rule) => {
                  var _a;
                  _push2(`<tr class="border-b border-gray-100 dark:border-gray-700"${_scopeId}><td class="py-3 px-4"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(rule.name)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(rule.description)}</p></td><td class="py-3 px-4 text-gray-600 dark:text-gray-400"${_scopeId}>${ssrInterpolate(((_a = actionTypes.find((t) => t.value === rule.action_type)) == null ? void 0 : _a.label) || rule.action_type)}</td><td class="py-3 px-4 text-center"${_scopeId}><span class="font-bold text-green-500"${_scopeId}>+${ssrInterpolate(rule.points_earned)}</span></td><td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400"${_scopeId}>${ssrInterpolate(rule.daily_limit || "\u221E")}</td><td class="py-3 px-4 text-center"${_scopeId}><span class="${ssrRenderClass([rule.is_active ? "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300" : "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300", "px-2 py-1 rounded-full text-xs font-medium"])}"${_scopeId}>${ssrInterpolate(rule.is_active ? "\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49" : "\u0E1B\u0E34\u0E14")}</span></td><td class="py-3 px-4 text-right"${_scopeId}><button class="p-2 text-gray-500 hover:text-primary-500"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:pencil",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(`</button><button class="p-2 text-gray-500 hover:text-red-500"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:delete",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(`</button></td></tr>`);
                });
                _push2(`<!--]--></tbody></table></div>`);
              } else {
                return [
                  createVNode("div", { class: "overflow-x-auto" }, [
                    createVNode("table", { class: "w-full" }, [
                      createVNode("thead", null, [
                        createVNode("tr", { class: "border-b border-gray-200 dark:border-gray-700" }, [
                          createVNode("th", { class: "text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E0E"),
                          createVNode("th", { class: "text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17"),
                          createVNode("th", { class: "text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49"),
                          createVNode("th", { class: "text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E08\u0E33\u0E01\u0E31\u0E14/\u0E27\u0E31\u0E19"),
                          createVNode("th", { class: "text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30"),
                          createVNode("th", { class: "text-right py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23")
                        ])
                      ]),
                      createVNode("tbody", null, [
                        (openBlock(true), createBlock(Fragment, null, renderList(rules.value, (rule) => {
                          var _a;
                          return openBlock(), createBlock("tr", {
                            key: rule.id,
                            class: "border-b border-gray-100 dark:border-gray-700"
                          }, [
                            createVNode("td", { class: "py-3 px-4" }, [
                              createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(rule.name), 1),
                              createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(rule.description), 1)
                            ]),
                            createVNode("td", { class: "py-3 px-4 text-gray-600 dark:text-gray-400" }, toDisplayString(((_a = actionTypes.find((t) => t.value === rule.action_type)) == null ? void 0 : _a.label) || rule.action_type), 1),
                            createVNode("td", { class: "py-3 px-4 text-center" }, [
                              createVNode("span", { class: "font-bold text-green-500" }, "+" + toDisplayString(rule.points_earned), 1)
                            ]),
                            createVNode("td", { class: "py-3 px-4 text-center text-gray-600 dark:text-gray-400" }, toDisplayString(rule.daily_limit || "\u221E"), 1),
                            createVNode("td", { class: "py-3 px-4 text-center" }, [
                              createVNode("span", {
                                class: ["px-2 py-1 rounded-full text-xs font-medium", rule.is_active ? "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300" : "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300"]
                              }, toDisplayString(rule.is_active ? "\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49" : "\u0E1B\u0E34\u0E14"), 3)
                            ]),
                            createVNode("td", { class: "py-3 px-4 text-right" }, [
                              createVNode("button", {
                                class: "p-2 text-gray-500 hover:text-primary-500",
                                onClick: ($event) => openRuleModal(rule)
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "mdi:pencil",
                                  class: "w-5 h-5"
                                })
                              ], 8, ["onClick"]),
                              createVNode("button", {
                                class: "p-2 text-gray-500 hover:text-red-500",
                                onClick: ($event) => handleDeleteRule(rule.id)
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "mdi:delete",
                                  class: "w-5 h-5"
                                })
                              ], 8, ["onClick"])
                            ])
                          ]);
                        }), 128))
                      ])
                    ])
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "users") {
          _push(`<div>`);
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="p-2"${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"${_scopeId}>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</h3>`);
                if (leaderboard.value.length > 0) {
                  _push2(`<div class="space-y-3"${_scopeId}><!--[-->`);
                  ssrRenderList(leaderboard.value, (user) => {
                    _push2(`<div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl"${_scopeId}><img${ssrRenderAttr("src", user.avatar || "/images/default-avatar.png")} class="w-12 h-12 rounded-full object-cover"${_scopeId}><div class="flex-grow"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(user.name)}</p><p class="text-sm text-gray-500"${_scopeId}>ID: ${ssrInterpolate(user.id)} | \u0E40\u0E25\u0E40\u0E27\u0E25 ${ssrInterpolate(user.level)}</p></div><div class="text-right"${_scopeId}><p class="font-bold text-amber-500"${_scopeId}>${ssrInterpolate(formatNumber(user.points))} \u0E41\u0E15\u0E49\u0E21</p></div><button class="px-3 py-1 bg-primary-100 text-primary-600 rounded-lg text-sm hover:bg-primary-200"${_scopeId}> \u0E1B\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 </button></div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "p-2" }, [
                    createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white mb-4" }, "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                    leaderboard.value.length > 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "space-y-3"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(leaderboard.value, (user) => {
                        return openBlock(), createBlock("div", {
                          key: user.id,
                          class: "flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl"
                        }, [
                          createVNode("img", {
                            src: user.avatar || "/images/default-avatar.png",
                            class: "w-12 h-12 rounded-full object-cover"
                          }, null, 8, ["src"]),
                          createVNode("div", { class: "flex-grow" }, [
                            createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(user.name), 1),
                            createVNode("p", { class: "text-sm text-gray-500" }, "ID: " + toDisplayString(user.id) + " | \u0E40\u0E25\u0E40\u0E27\u0E25 " + toDisplayString(user.level), 1)
                          ]),
                          createVNode("div", { class: "text-right" }, [
                            createVNode("p", { class: "font-bold text-amber-500" }, toDisplayString(formatNumber(user.points)) + " \u0E41\u0E15\u0E49\u0E21", 1)
                          ]),
                          createVNode("button", {
                            class: "px-3 py-1 bg-primary-100 text-primary-600 rounded-lg text-sm hover:bg-primary-200",
                            onClick: ($event) => {
                              adjustForm.value.user_id = user.id.toString();
                              showAdjustModal.value = true;
                            }
                          }, " \u0E1B\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 ", 8, ["onClick"])
                        ]);
                      }), 128))
                    ])) : createCommentVNode("", true)
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "analytics") {
          _push(`<div>`);
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="p-4 text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:chart-bar",
                  class: "w-16 h-16 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E01\u0E23\u0E32\u0E1F\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C</h3><p${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32 - \u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E01\u0E23\u0E32\u0E1F\u0E41\u0E19\u0E27\u0E42\u0E19\u0E49\u0E21\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E15\u0E49\u0E21</p></div>`);
              } else {
                return [
                  createVNode("div", { class: "p-4 text-center text-gray-500 dark:text-gray-400" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:chart-bar",
                      class: "w-16 h-16 mx-auto mb-4"
                    }),
                    createVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-white mb-2" }, "\u0E01\u0E23\u0E32\u0E1F\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C"),
                    createVNode("p", null, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32 - \u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E01\u0E23\u0E32\u0E1F\u0E41\u0E19\u0E27\u0E42\u0E19\u0E49\u0E21\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E15\u0E49\u0E21")
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (showRuleModal.value) {
          _push2(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6"><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">${ssrInterpolate(editingRule.value ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E0E" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E01\u0E0E\u0E43\u0E2B\u0E21\u0E48")}</h3><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E0E</label><input${ssrRenderAttr("value", ruleForm.value.name)} type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</label><select class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"><option value=""${ssrIncludeBooleanAttr(Array.isArray(ruleForm.value.action_type) ? ssrLooseContain(ruleForm.value.action_type, "") : ssrLooseEqual(ruleForm.value.action_type, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</option><!--[-->`);
          ssrRenderList(actionTypes, (type) => {
            _push2(`<option${ssrRenderAttr("value", type.value)}${ssrIncludeBooleanAttr(Array.isArray(ruleForm.value.action_type) ? ssrLooseContain(ruleForm.value.action_type, type.value) : ssrLooseEqual(ruleForm.value.action_type, type.value)) ? " selected" : ""}>${ssrInterpolate(type.label)}</option>`);
          });
          _push2(`<!--]--></select></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49</label><input${ssrRenderAttr("value", ruleForm.value.points_earned)} type="number" min="0" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E08\u0E33\u0E01\u0E31\u0E14/\u0E27\u0E31\u0E19</label><input${ssrRenderAttr("value", ruleForm.value.daily_limit)} type="number" min="0" placeholder="0 = \u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22</label><textarea rows="2" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl">${ssrInterpolate(ruleForm.value.description)}</textarea></div><div class="flex items-center gap-2"><input type="checkbox" id="is_active"${ssrIncludeBooleanAttr(Array.isArray(ruleForm.value.is_active) ? ssrLooseContain(ruleForm.value.is_active, null) : ruleForm.value.is_active) ? " checked" : ""} class="rounded"><label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</label></div></div><div class="flex gap-3 mt-6"><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 py-2 bg-primary-500 text-white rounded-xl"> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        if (showAdjustModal.value) {
          _push2(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6"><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">\u0E1B\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</h3><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID</label><input${ssrRenderAttr("value", adjustForm.value.user_id)} type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E23\u0E30\u0E1A\u0E38 User ID"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</label><div class="flex gap-2"><!--[-->`);
          ssrRenderList([{ value: "add", label: "\u0E40\u0E1E\u0E34\u0E48\u0E21", color: "green" }, { value: "deduct", label: "\u0E2B\u0E31\u0E01", color: "red" }, { value: "set", label: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32", color: "blue" }], (t) => {
            _push2(`<button class="${ssrRenderClass([adjustForm.value.type === t.value ? `bg-${t.color}-500 text-white` : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400", "flex-1 py-2 rounded-xl font-medium transition-colors"])}">${ssrInterpolate(t.label)}</button>`);
          });
          _push2(`<!--]--></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E08\u0E33\u0E19\u0E27\u0E19</label><input${ssrRenderAttr("value", adjustForm.value.amount)} type="number" min="0" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25</label><input${ssrRenderAttr("value", adjustForm.value.reason)} type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25"></div></div><div class="flex gap-3 mt-6"><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 py-2 bg-primary-500 text-white rounded-xl"> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Admin/Points.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Points-BZJGdLkL.mjs.map
