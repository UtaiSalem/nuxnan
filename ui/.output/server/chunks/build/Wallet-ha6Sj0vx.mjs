import { defineComponent, ref, mergeProps, unref, withCtx, createVNode, toDisplayString, createBlock, openBlock, Fragment, renderList, createCommentVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrRenderTeleport } from 'vue/server-renderer';
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

const useAdminWallet = () => {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase;
  const isLoading = ref(false);
  const error = ref(null);
  const getStats = async () => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/wallet/stats`, {
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
  const getPendingWithdrawals = async (params = {}) => {
    try {
      isLoading.value = true;
      const queryParams = new URLSearchParams(params).toString();
      const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/pending?${queryParams}`, {
        headers: { "Authorization": `Bearer ${authStore.token}` }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to get pending withdrawals:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const approveWithdrawal = async (transactionId, adminNotes = "") => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${transactionId}/approve`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${authStore.token}` },
        body: { admin_notes: adminNotes }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to approve withdrawal:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const rejectWithdrawal = async (transactionId, reason) => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${transactionId}/reject`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${authStore.token}` },
        body: { reason }
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to reject withdrawal:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const adjustWallet = async (userId, adjustmentData) => {
    try {
      isLoading.value = true;
      const response = await $fetch(`${apiBase}/api/admin/wallet/users/${userId}/adjust`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${authStore.token}` },
        body: adjustmentData
      });
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Failed to adjust wallet:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getUserTransactions = async (userId, params = {}) => {
    try {
      isLoading.value = true;
      const queryParams = new URLSearchParams(params).toString();
      const response = await $fetch(`${apiBase}/api/admin/wallet/users/${userId}/transactions?${queryParams}`, {
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
  const getAnalytics = async (filters = {}) => {
    try {
      isLoading.value = true;
      const queryParams = new URLSearchParams(filters).toString();
      const response = await $fetch(`${apiBase}/api/admin/wallet/analytics?${queryParams}`, {
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
  const formatMoney = (amount) => {
    return new Intl.NumberFormat("th-TH", {
      style: "currency",
      currency: "THB",
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  };
  const formatPercentage = (value) => {
    return `${value.toFixed(2)}%`;
  };
  const calculateFee = (amount) => {
    const feePercentage = 0.13;
    const calculatedFee = amount * feePercentage;
    return calculatedFee;
  };
  const calculateNetAmount = (amount) => {
    const fee = calculateFee(amount);
    return amount - fee;
  };
  return {
    isLoading,
    error,
    getStats,
    getPendingWithdrawals,
    approveWithdrawal,
    rejectWithdrawal,
    adjustWallet,
    getUserTransactions,
    getAnalytics,
    formatMoney,
    formatPercentage,
    calculateFee,
    calculateNetAmount
  };
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Wallet",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 Wallet - Admin"
    });
    useAuthStore();
    const {
      getStats,
      getPendingWithdrawals,
      approveWithdrawal,
      getAnalytics,
      isLoading
    } = useAdminWallet();
    const activeTab = ref("overview");
    const stats = ref(null);
    const pendingWithdrawals = ref([]);
    const analytics = ref(null);
    const showAdjustModal = ref(false);
    const adjustForm = ref({
      user_id: "",
      amount: 0,
      type: "add",
      // 'add' | 'deduct' | 'set'
      reason: ""
    });
    const showRejectModal = ref(false);
    const rejectingId = ref(null);
    const rejectReason = ref("");
    const loadData = async () => {
      try {
        const [statsData, withdrawalsData, analyticsData] = await Promise.all([
          getStats(),
          getPendingWithdrawals(),
          getAnalytics()
        ]);
        if (statsData) stats.value = statsData;
        if (withdrawalsData) pendingWithdrawals.value = withdrawalsData.withdrawals || withdrawalsData;
        if (analyticsData) analytics.value = analyticsData;
      } catch (error) {
        console.error("Failed to load admin wallet data:", error);
      }
    };
    const handleApprove = async (withdrawalId) => {
      if (confirm("\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?")) {
        try {
          await approveWithdrawal(withdrawalId);
          await loadData();
        } catch (error) {
          console.error("Failed to approve withdrawal:", error);
        }
      }
    };
    const openRejectModal = (withdrawalId) => {
      rejectingId.value = withdrawalId;
      rejectReason.value = "";
      showRejectModal.value = true;
    };
    const formatMoney = (value) => {
      return new Intl.NumberFormat("th-TH", {
        style: "currency",
        currency: "THB"
      }).format(value);
    };
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const getStatusLabel = (status) => {
      const labels = {
        pending: "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        approved: "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27",
        rejected: "\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18",
        completed: "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
        cancelled: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
      };
      return labels[status] || status;
    };
    const getStatusColor = (status) => {
      const colors = {
        pending: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
        approved: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        rejected: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
        completed: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        cancelled: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300"
      };
      return colors[status] || "bg-gray-100 text-gray-800";
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-7xl mx-auto"><div class="flex items-center justify-between mb-8"><div><h1 class="text-3xl font-bold text-gray-900 dark:text-white">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 Wallet</h1><p class="text-gray-600 dark:text-gray-400">Admin Wallet Management</p></div><div class="flex gap-3"><button class="px-4 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:wallet-plus",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E1B\u0E23\u0E31\u0E1A\u0E22\u0E2D\u0E14\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </button></div></div>`);
      if (stats.value) {
        _push(`<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">`);
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:wallet",
                class: "w-7 h-7 text-emerald-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatMoney(stats.value.total_wallet || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:wallet",
                      class: "w-7 h-7 text-emerald-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatMoney(stats.value.total_wallet || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14")
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
              _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:clock-outline",
                class: "w-7 h-7 text-yellow-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.pending_withdrawals || 0)}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:clock-outline",
                      class: "w-7 h-7 text-yellow-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.pending_withdrawals || 0), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23")
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
                icon: "mdi:check-circle",
                class: "w-7 h-7 text-green-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatMoney(stats.value.completed_withdrawals || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E16\u0E2D\u0E19\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:check-circle",
                      class: "w-7 h-7 text-green-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatMoney(stats.value.completed_withdrawals || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E16\u0E2D\u0E19\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08")
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
                icon: "mdi:plus-circle",
                class: "w-7 h-7 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatMoney(stats.value.total_deposits || 0))}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E40\u0E07\u0E34\u0E19\u0E1D\u0E32\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4" }, [
                  createVNode("div", { class: "w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:plus-circle",
                      class: "w-7 h-7 text-blue-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatMoney(stats.value.total_deposits || 0)), 1),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E40\u0E07\u0E34\u0E19\u0E1D\u0E32\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14")
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
        { key: "withdrawals", label: "\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19", icon: "mdi:bank-transfer-out", badge: (_a = stats.value) == null ? void 0 : _a.pending_withdrawals },
        { key: "analytics", label: "\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C", icon: "mdi:chart-bar" }
      ], (tab) => {
        _push(`<button class="${ssrRenderClass([activeTab.value === tab.key ? "bg-primary-500 text-white shadow" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700", "px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2 relative"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(tab.label)} `);
        if (tab.badge && tab.badge > 0) {
          _push(`<span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">${ssrInterpolate(tab.badge)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
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
              var _a2, _b, _c, _d, _e, _f, _g, _h;
              if (_push2) {
                _push2(`<div class="p-2"${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"${_scopeId}>\u0E2A\u0E16\u0E34\u0E15\u0E34\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21</h3><div class="space-y-4"${_scopeId}><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E40\u0E07\u0E34\u0E19\u0E1D\u0E32\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span><span class="font-bold text-green-500"${_scopeId}>${ssrInterpolate(formatMoney(((_a2 = stats.value) == null ? void 0 : _a2.total_deposits) || 0))}</span></div><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span><span class="font-bold text-red-500"${_scopeId}>${ssrInterpolate(formatMoney(((_b = stats.value) == null ? void 0 : _b.total_withdrawals) || 0))}</span></div><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span><span class="font-bold text-blue-500"${_scopeId}>${ssrInterpolate(formatMoney(((_c = stats.value) == null ? void 0 : _c.total_transfers) || 0))}</span></div><div class="flex items-center justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E08\u0E32\u0E01\u0E41\u0E15\u0E49\u0E21</span><span class="font-bold text-purple-500"${_scopeId}>${ssrInterpolate(formatMoney(((_d = stats.value) == null ? void 0 : _d.total_conversions) || 0))}</span></div></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "p-2" }, [
                    createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white mb-4" }, "\u0E2A\u0E16\u0E34\u0E15\u0E34\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21"),
                    createVNode("div", { class: "space-y-4" }, [
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E40\u0E07\u0E34\u0E19\u0E1D\u0E32\u0E01\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                        createVNode("span", { class: "font-bold text-green-500" }, toDisplayString(formatMoney(((_e = stats.value) == null ? void 0 : _e.total_deposits) || 0)), 1)
                      ]),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                        createVNode("span", { class: "font-bold text-red-500" }, toDisplayString(formatMoney(((_f = stats.value) == null ? void 0 : _f.total_withdrawals) || 0)), 1)
                      ]),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                        createVNode("span", { class: "font-bold text-blue-500" }, toDisplayString(formatMoney(((_g = stats.value) == null ? void 0 : _g.total_transfers) || 0)), 1)
                      ]),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E41\u0E1B\u0E25\u0E07\u0E08\u0E32\u0E01\u0E41\u0E15\u0E49\u0E21"),
                        createVNode("span", { class: "font-bold text-purple-500" }, toDisplayString(formatMoney(((_h = stats.value) == null ? void 0 : _h.total_conversions) || 0)), 1)
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
                _push2(`<div class="p-2"${_scopeId}><div class="flex items-center justify-between mb-4"${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white"${_scopeId}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</h3><button class="text-primary-500 text-sm hover:underline"${_scopeId}> \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button></div>`);
                if (pendingWithdrawals.value.length > 0) {
                  _push2(`<div class="space-y-3"${_scopeId}><!--[-->`);
                  ssrRenderList(pendingWithdrawals.value.slice(0, 3), (withdrawal) => {
                    var _a2, _b;
                    _push2(`<div class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl"${_scopeId}><img${ssrRenderAttr("src", ((_a2 = withdrawal.user) == null ? void 0 : _a2.avatar) || "/images/default-avatar.png")} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div class="flex-grow"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate((_b = withdrawal.user) == null ? void 0 : _b.name)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(formatDate(withdrawal.created_at))}</p></div><p class="font-bold text-red-500"${_scopeId}>${ssrInterpolate(formatMoney(withdrawal.amount))}</p></div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<div class="text-center py-4 text-gray-500"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:check-all",
                    class: "w-8 h-8 mx-auto mb-2 text-green-500"
                  }, null, _parent2, _scopeId));
                  _push2(`<p${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p></div>`);
                }
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "p-2" }, [
                    createVNode("div", { class: "flex items-center justify-between mb-4" }, [
                      createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white" }, "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23"),
                      createVNode("button", {
                        class: "text-primary-500 text-sm hover:underline",
                        onClick: ($event) => activeTab.value = "withdrawals"
                      }, " \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ", 8, ["onClick"])
                    ]),
                    pendingWithdrawals.value.length > 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "space-y-3"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(pendingWithdrawals.value.slice(0, 3), (withdrawal) => {
                        var _a2, _b;
                        return openBlock(), createBlock("div", {
                          key: withdrawal.id,
                          class: "flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl"
                        }, [
                          createVNode("img", {
                            src: ((_a2 = withdrawal.user) == null ? void 0 : _a2.avatar) || "/images/default-avatar.png",
                            class: "w-10 h-10 rounded-full object-cover"
                          }, null, 8, ["src"]),
                          createVNode("div", { class: "flex-grow" }, [
                            createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString((_b = withdrawal.user) == null ? void 0 : _b.name), 1),
                            createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(formatDate(withdrawal.created_at)), 1)
                          ]),
                          createVNode("p", { class: "font-bold text-red-500" }, toDisplayString(formatMoney(withdrawal.amount)), 1)
                        ]);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "text-center py-4 text-gray-500"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:check-all",
                        class: "w-8 h-8 mx-auto mb-2 text-green-500"
                      }),
                      createVNode("p", null, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23")
                    ]))
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
        if (activeTab.value === "withdrawals") {
          _push(`<div>`);
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="overflow-x-auto"${_scopeId}><table class="w-full"${_scopeId}><thead${_scopeId}><tr class="border-b border-gray-200 dark:border-gray-700"${_scopeId}><th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</th><th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19</th><th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23</th><th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</th><th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="text-right py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400"${_scopeId}>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody${_scopeId}><!--[-->`);
                ssrRenderList(pendingWithdrawals.value, (withdrawal) => {
                  var _a2, _b, _c, _d;
                  _push2(`<tr class="border-b border-gray-100 dark:border-gray-700"${_scopeId}><td class="py-3 px-4"${_scopeId}><div class="flex items-center gap-3"${_scopeId}><img${ssrRenderAttr("src", ((_a2 = withdrawal.user) == null ? void 0 : _a2.avatar) || "/images/default-avatar.png")} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate((_b = withdrawal.user) == null ? void 0 : _b.name)}</p><p class="text-xs text-gray-500"${_scopeId}>ID: ${ssrInterpolate(withdrawal.user_id)}</p></div></div></td><td class="py-3 px-4"${_scopeId}><p class="font-bold text-red-500"${_scopeId}>${ssrInterpolate(formatMoney(withdrawal.amount))}</p><p class="text-xs text-gray-500"${_scopeId}>\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21: ${ssrInterpolate(formatMoney(withdrawal.fee || 0))}</p></td><td class="py-3 px-4"${_scopeId}><p class="text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate((_c = withdrawal.bank_account) == null ? void 0 : _c.bank_name)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate((_d = withdrawal.bank_account) == null ? void 0 : _d.account_number)}</p></td><td class="py-3 px-4 text-gray-600 dark:text-gray-400 text-sm"${_scopeId}>${ssrInterpolate(formatDate(withdrawal.created_at))}</td><td class="py-3 px-4 text-center"${_scopeId}><span class="${ssrRenderClass([getStatusColor(withdrawal.status), "px-2 py-1 rounded-full text-xs font-medium"])}"${_scopeId}>${ssrInterpolate(getStatusLabel(withdrawal.status))}</span></td><td class="py-3 px-4 text-right"${_scopeId}>`);
                  if (withdrawal.status === "pending") {
                    _push2(`<div class="flex gap-2 justify-end"${_scopeId}><button class="px-3 py-1 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600"${_scopeId}> \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 </button><button class="px-3 py-1 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600"${_scopeId}> \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</td></tr>`);
                });
                _push2(`<!--]--></tbody></table>`);
                if (pendingWithdrawals.value.length === 0) {
                  _push2(`<div class="text-center py-8 text-gray-500"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:inbox-outline",
                    class: "w-12 h-12 mx-auto mb-3"
                  }, null, _parent2, _scopeId));
                  _push2(`<p${_scopeId}>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</p></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "overflow-x-auto" }, [
                    createVNode("table", { class: "w-full" }, [
                      createVNode("thead", null, [
                        createVNode("tr", { class: "border-b border-gray-200 dark:border-gray-700" }, [
                          createVNode("th", { class: "text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49"),
                          createVNode("th", { class: "text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E08\u0E33\u0E19\u0E27\u0E19"),
                          createVNode("th", { class: "text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23"),
                          createVNode("th", { class: "text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48"),
                          createVNode("th", { class: "text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30"),
                          createVNode("th", { class: "text-right py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400" }, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23")
                        ])
                      ]),
                      createVNode("tbody", null, [
                        (openBlock(true), createBlock(Fragment, null, renderList(pendingWithdrawals.value, (withdrawal) => {
                          var _a2, _b, _c, _d;
                          return openBlock(), createBlock("tr", {
                            key: withdrawal.id,
                            class: "border-b border-gray-100 dark:border-gray-700"
                          }, [
                            createVNode("td", { class: "py-3 px-4" }, [
                              createVNode("div", { class: "flex items-center gap-3" }, [
                                createVNode("img", {
                                  src: ((_a2 = withdrawal.user) == null ? void 0 : _a2.avatar) || "/images/default-avatar.png",
                                  class: "w-10 h-10 rounded-full object-cover"
                                }, null, 8, ["src"]),
                                createVNode("div", null, [
                                  createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString((_b = withdrawal.user) == null ? void 0 : _b.name), 1),
                                  createVNode("p", { class: "text-xs text-gray-500" }, "ID: " + toDisplayString(withdrawal.user_id), 1)
                                ])
                              ])
                            ]),
                            createVNode("td", { class: "py-3 px-4" }, [
                              createVNode("p", { class: "font-bold text-red-500" }, toDisplayString(formatMoney(withdrawal.amount)), 1),
                              createVNode("p", { class: "text-xs text-gray-500" }, "\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21: " + toDisplayString(formatMoney(withdrawal.fee || 0)), 1)
                            ]),
                            createVNode("td", { class: "py-3 px-4" }, [
                              createVNode("p", { class: "text-gray-900 dark:text-white" }, toDisplayString((_c = withdrawal.bank_account) == null ? void 0 : _c.bank_name), 1),
                              createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString((_d = withdrawal.bank_account) == null ? void 0 : _d.account_number), 1)
                            ]),
                            createVNode("td", { class: "py-3 px-4 text-gray-600 dark:text-gray-400 text-sm" }, toDisplayString(formatDate(withdrawal.created_at)), 1),
                            createVNode("td", { class: "py-3 px-4 text-center" }, [
                              createVNode("span", {
                                class: ["px-2 py-1 rounded-full text-xs font-medium", getStatusColor(withdrawal.status)]
                              }, toDisplayString(getStatusLabel(withdrawal.status)), 3)
                            ]),
                            createVNode("td", { class: "py-3 px-4 text-right" }, [
                              withdrawal.status === "pending" ? (openBlock(), createBlock("div", {
                                key: 0,
                                class: "flex gap-2 justify-end"
                              }, [
                                createVNode("button", {
                                  class: "px-3 py-1 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600",
                                  onClick: ($event) => handleApprove(withdrawal.id)
                                }, " \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 ", 8, ["onClick"]),
                                createVNode("button", {
                                  class: "px-3 py-1 bg-red-500 text-white rounded-lg text-sm hover:bg-red-600",
                                  onClick: ($event) => openRejectModal(withdrawal.id)
                                }, " \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 ", 8, ["onClick"])
                              ])) : createCommentVNode("", true)
                            ])
                          ]);
                        }), 128))
                      ])
                    ]),
                    pendingWithdrawals.value.length === 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "text-center py-8 text-gray-500"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:inbox-outline",
                        class: "w-12 h-12 mx-auto mb-3"
                      }),
                      createVNode("p", null, "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19")
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
                _push2(`<h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E01\u0E23\u0E32\u0E1F\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C</h3><p${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32 - \u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E01\u0E23\u0E32\u0E1F\u0E41\u0E19\u0E27\u0E42\u0E19\u0E49\u0E21\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 Wallet</p></div>`);
              } else {
                return [
                  createVNode("div", { class: "p-4 text-center text-gray-500 dark:text-gray-400" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:chart-bar",
                      class: "w-16 h-16 mx-auto mb-4"
                    }),
                    createVNode("h3", { class: "text-lg font-medium text-gray-900 dark:text-white mb-2" }, "\u0E01\u0E23\u0E32\u0E1F\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C"),
                    createVNode("p", null, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32 - \u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E01\u0E23\u0E32\u0E1F\u0E41\u0E19\u0E27\u0E42\u0E19\u0E49\u0E21\u0E18\u0E38\u0E23\u0E01\u0E23\u0E23\u0E21\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 Wallet")
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
        if (showAdjustModal.value) {
          _push2(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6"><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">\u0E1B\u0E23\u0E31\u0E1A\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</h3><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID</label><input${ssrRenderAttr("value", adjustForm.value.user_id)} type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E23\u0E30\u0E1A\u0E38 User ID"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</label><div class="flex gap-2"><button class="${ssrRenderClass([adjustForm.value.type === "add" ? "bg-green-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400", "flex-1 py-2 rounded-xl font-medium transition-colors"])}"> \u0E40\u0E1E\u0E34\u0E48\u0E21 </button><button class="${ssrRenderClass([adjustForm.value.type === "deduct" ? "bg-red-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400", "flex-1 py-2 rounded-xl font-medium transition-colors"])}"> \u0E2B\u0E31\u0E01 </button><button class="${ssrRenderClass([adjustForm.value.type === "set" ? "bg-blue-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400", "flex-1 py-2 rounded-xl font-medium transition-colors"])}"> \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32 </button></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E08\u0E33\u0E19\u0E27\u0E19 (\u0E1A\u0E32\u0E17)</label><input${ssrRenderAttr("value", adjustForm.value.amount)} type="number" min="0" step="0.01" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25</label><input${ssrRenderAttr("value", adjustForm.value.reason)} type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25"></div></div><div class="flex gap-3 mt-6"><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 py-2 bg-primary-500 text-white rounded-xl"> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        if (showRejectModal.value) {
          _push2(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6"><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</h3><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18</label><textarea rows="3" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25">${ssrInterpolate(rejectReason.value)}</textarea></div><div class="flex gap-3 mt-6"><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 py-2 bg-red-500 text-white rounded-xl"> \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Admin/Wallet.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Wallet-ha6Sj0vx.mjs.map
