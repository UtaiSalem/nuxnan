import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, computed, ref, watch, mergeProps, unref, withCtx, createVNode, createTextVNode, toDisplayString, createBlock, openBlock, Fragment, renderList, createCommentVNode, withDirectives, vModelText, vModelSelect, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
import { u as usePoints } from './usePoints-DipNhVzo.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Points",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 - Nuxni"
    });
    const authStore = useAuthStore();
    const config = useRuntimeConfig();
    const apiBase = computed(() => config.public.apiBase || "");
    const {
      getBalance,
      transfer,
      convertToWallet,
      getTransactions,
      formatPoints
    } = usePoints();
    const activeTab = ref("overview");
    const transactions = ref([]);
    const transactionsLoading = ref(false);
    const transactionFilters = ref({
      type: "",
      // earn | spend | transfer_in | transfer_out | conversion
      date_from: "",
      date_to: "",
      page: 1,
      per_page: 20
    });
    const transactionsPagination = ref({
      current_page: 1,
      total_pages: 1,
      total: 0
    });
    const transferForm = ref({
      recipient_id: "",
      amount: 100,
      message: ""
    });
    const convertForm = ref({
      points: 1200
      // Minimum 1200 points = 1 THB
    });
    const userSearchQuery = ref("");
    const userSearchResults = ref([]);
    const userSearchLoading = ref(false);
    const selectedRecipient = ref(null);
    const showUserDropdown = ref(false);
    const isProcessing = ref(false);
    const processSuccess = ref(false);
    const processMessage = ref("");
    const quickTransferAmounts = [100, 240, 500, 1e3, 2e3];
    const quickConvertAmounts = [1200, 2400, 4800, 6e3, 9600, 12e3, 14400, 18e3, 24e3, 3e4, 48e3, 12e4];
    const pointsBalance = computed(() => authStore.points || 0);
    const conversionPreview = computed(() => {
      const pts = convertForm.value.points;
      const rate = 1200;
      return {
        points: pts,
        money: pts / rate,
        isValid: pts >= rate && pts <= pointsBalance.value
      };
    });
    const searchUsers = async () => {
      if (userSearchQuery.value.length < 2) {
        userSearchResults.value = [];
        showUserDropdown.value = false;
        return;
      }
      try {
        userSearchLoading.value = true;
        const response = await $fetch(`${apiBase.value}/api/users/search`, {
          method: "GET",
          headers: {
            "Authorization": `Bearer ${authStore.token}`
          },
          params: {
            q: userSearchQuery.value,
            limit: 10
          }
        });
        if (response.success) {
          userSearchResults.value = (response.data || []).filter((u) => {
            var _a;
            return u.id !== ((_a = authStore.user) == null ? void 0 : _a.id);
          });
          showUserDropdown.value = true;
        }
      } catch (err) {
        console.error("Search users error:", err);
        userSearchResults.value = [];
      } finally {
        userSearchLoading.value = false;
      }
    };
    let searchTimeout = null;
    const debouncedSearch = () => {
      if (searchTimeout) clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        searchUsers();
      }, 300);
    };
    const selectRecipient = (user) => {
      selectedRecipient.value = user;
      transferForm.value.recipient_id = user.id.toString();
      userSearchQuery.value = user.name || user.username;
      showUserDropdown.value = false;
    };
    const clearRecipient = () => {
      selectedRecipient.value = null;
      transferForm.value.recipient_id = "";
      userSearchQuery.value = "";
      userSearchResults.value = [];
    };
    const loadTransactions = async () => {
      try {
        transactionsLoading.value = true;
        const data = await getTransactions({
          type: transactionFilters.value.type || void 0,
          date_from: transactionFilters.value.date_from || void 0,
          date_to: transactionFilters.value.date_to || void 0,
          page: transactionFilters.value.page,
          per_page: transactionFilters.value.per_page
        });
        transactions.value = data.transactions || [];
        transactionsPagination.value = {
          current_page: data.current_page || 1,
          total_pages: data.total_pages || 1,
          total: data.total || 0
        };
      } catch (err) {
        console.error("Failed to load transactions:", err);
      } finally {
        transactionsLoading.value = false;
      }
    };
    const handleTransfer = async () => {
      if (!selectedRecipient.value) {
        processSuccess.value = false;
        processMessage.value = "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A";
        return;
      }
      if (transferForm.value.amount <= 0) {
        processSuccess.value = false;
        processMessage.value = "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07";
        return;
      }
      if (transferForm.value.amount > pointsBalance.value) {
        processSuccess.value = false;
        processMessage.value = `\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23 ${formatPoints(transferForm.value.amount)} \u0E41\u0E15\u0E49\u0E21, \u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48 ${formatPoints(pointsBalance.value)} \u0E41\u0E15\u0E49\u0E21)`;
        return;
      }
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        await transfer({
          recipient_id: parseInt(transferForm.value.recipient_id),
          amount: transferForm.value.amount,
          message: transferForm.value.message
        });
        processSuccess.value = true;
        processMessage.value = `\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21 ${formatPoints(transferForm.value.amount)} \u0E43\u0E2B\u0E49 ${selectedRecipient.value.name} \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!`;
        await getBalance();
        await loadTransactions();
        clearRecipient();
        transferForm.value.amount = 100;
        transferForm.value.message = "";
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21";
      } finally {
        isProcessing.value = false;
      }
    };
    const handleConvert = async () => {
      if (convertForm.value.points < 1200) {
        processSuccess.value = false;
        processMessage.value = "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33\u0E04\u0E37\u0E2D 1,200 \u0E41\u0E15\u0E49\u0E21 (1 \u0E1A\u0E32\u0E17)";
        return;
      }
      if (convertForm.value.points > pointsBalance.value) {
        processSuccess.value = false;
        processMessage.value = `\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23 ${formatPoints(convertForm.value.points)} \u0E41\u0E15\u0E49\u0E21, \u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48 ${formatPoints(pointsBalance.value)} \u0E41\u0E15\u0E49\u0E21)`;
        return;
      }
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        await convertToWallet(convertForm.value.points);
        processSuccess.value = true;
        processMessage.value = `\u0E41\u0E1B\u0E25\u0E07 ${formatPoints(convertForm.value.points)} \u0E41\u0E15\u0E49\u0E21 \u0E40\u0E1B\u0E47\u0E19 ${(convertForm.value.points / 1200).toFixed(2)} \u0E1A\u0E32\u0E17 \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!`;
        await getBalance();
        await loadTransactions();
        convertForm.value.points = 1200;
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21";
      } finally {
        isProcessing.value = false;
      }
    };
    const getTransactionTypeLabel = (type) => {
      const labels = {
        earn: "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21",
        spend: "\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21",
        transfer_in: "\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21",
        transfer_out: "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E2D\u0E2D\u0E01",
        conversion: "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19",
        refund: "\u0E04\u0E37\u0E19\u0E41\u0E15\u0E49\u0E21",
        bonus: "\u0E42\u0E1A\u0E19\u0E31\u0E2A",
        reward: "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25",
        purchase: "\u0E0B\u0E37\u0E49\u0E2D",
        donation: "\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"
      };
      return labels[type] || type;
    };
    const getTransactionIcon = (type) => {
      const icons = {
        earn: "mdi:arrow-down-circle",
        spend: "mdi:arrow-up-circle",
        transfer_in: "mdi:account-arrow-left",
        transfer_out: "mdi:account-arrow-right",
        conversion: "mdi:swap-horizontal-circle",
        refund: "mdi:undo",
        bonus: "mdi:gift",
        reward: "mdi:trophy",
        purchase: "mdi:cart",
        donation: "mdi:hand-coin"
      };
      return icons[type] || "mdi:circle";
    };
    const isPositiveTransaction = (tx) => {
      var _a;
      const type = tx.transaction_type || tx.type;
      if (["earn", "transfer_in", "refund", "bonus", "reward"].includes(type)) return true;
      if (["spend", "transfer_out", "payment", "purchase", "donation"].includes(type)) return false;
      if (type === "conversion") {
        return tx.source_type === "wallet_to_points" || ((_a = tx.metadata) == null ? void 0 : _a.conversion_type) === "money_to_points";
      }
      return tx.amount > 0;
    };
    const getTransactionDisplayTitle = (tx) => {
      const type = tx.transaction_type || tx.type;
      if ((type === "transfer_in" || type === "transfer_out") && tx.description) {
        return tx.description;
      }
      return getTransactionTypeLabel(type);
    };
    const getTransactionDisplaySubtitle = (tx) => {
      const type = tx.transaction_type || tx.type;
      if (type === "transfer_in" || type === "transfer_out") {
        return null;
      }
      return tx.description || null;
    };
    const isThreeColumnTransaction = (tx) => {
      const type = tx.transaction_type || tx.type;
      return type === "transfer_in" || type === "transfer_out" || type === "conversion";
    };
    const getDefaultAvatar = (name) => {
      return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=e2e8f0&color=64748b`;
    };
    const getOwnerInfo = () => {
      var _a, _b, _c;
      return {
        name: ((_a = authStore.user) == null ? void 0 : _a.name) || "\u0E09\u0E31\u0E19",
        avatar: ((_b = authStore.user) == null ? void 0 : _b.profile_photo_url) || getDefaultAvatar(((_c = authStore.user) == null ? void 0 : _c.name) || "User")
      };
    };
    const getPartnerInfo = (tx) => {
      var _a;
      const type = tx.transaction_type || tx.type;
      if (type === "transfer_in") {
        return tx.sender || null;
      } else if (type === "transfer_out") {
        return tx.receiver || null;
      } else if (type === "conversion") {
        const isPointsToWallet = tx.source_type === "points_to_wallet" || ((_a = tx.metadata) == null ? void 0 : _a.conversion_type) === "points_to_money";
        return {
          name: isPointsToWallet ? "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" : "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E1E\u0E2D\u0E22\u0E17\u0E4C",
          avatar: null,
          // Will use icon fallback
          isSystem: true
        };
      }
      return null;
    };
    const getThreeColumnLabel = (tx) => {
      var _a;
      const type = tx.transaction_type || tx.type;
      if (type === "transfer_in") return "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01";
      if (type === "transfer_out") return "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49";
      if (type === "conversion") {
        const isPointsToWallet = tx.source_type === "points_to_wallet" || ((_a = tx.metadata) == null ? void 0 : _a.conversion_type) === "points_to_money";
        return isPointsToWallet ? "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19" : "\u0E41\u0E1B\u0E25\u0E07\u0E08\u0E32\u0E01\u0E40\u0E07\u0E34\u0E19";
      }
      return "";
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
    const formatMoney = (value) => {
      return new Intl.NumberFormat("th-TH", {
        style: "currency",
        currency: "THB",
        minimumFractionDigits: 2
      }).format(value);
    };
    watch(transactionFilters, () => {
      loadTransactions();
    }, { deep: true });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-4xl mx-auto"><div class="text-center mb-8"><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-full flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:star-circle",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"> \u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E41\u0E15\u0E49\u0E21 \u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21 \u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19 \u0E41\u0E25\u0E30\u0E14\u0E39\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 </p></div>`);
      _push(ssrRenderComponent(_sfc_main$1, { class: "mb-6 bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 border-0" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b;
          if (_push2) {
            _push2(`<div class="text-white p-2"${_scopeId}><div class="flex flex-col lg:flex-row items-center justify-between gap-6"${_scopeId}><div class="flex items-center gap-6"${_scopeId}><div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:star-circle",
              class: "w-12 h-12"
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><p class="text-white/80 text-sm mb-1"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</p><p class="text-4xl lg:text-5xl font-bold"${_scopeId}>${ssrInterpolate(unref(formatPoints)(pointsBalance.value))}</p><p class="text-white/80 text-sm mt-1"${_scopeId}>Plearnd Points</p></div></div><div class="flex gap-3"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_NuxtLink, {
              to: "/earn/donates",
              class: "px-4 py-2 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:plus-circle",
                    class: "w-5 h-5"
                  }, null, _parent3, _scopeId2));
                  _push3(` \u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1E\u0E34\u0E48\u0E21 `);
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: "mdi:plus-circle",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1E\u0E34\u0E48\u0E21 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<button class="px-4 py-2 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 backdrop-blur"${ssrIncludeBooleanAttr(pointsBalance.value < 100) ? " disabled" : ""}${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:send",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21 </button></div></div><div class="mt-6 pt-6 border-t border-white/20 flex items-center justify-between"${_scopeId}><div class="flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:wallet",
              class: "w-5 h-5 text-green-300"
            }, null, _parent2, _scopeId));
            _push2(`<span${_scopeId}>\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19: <strong${_scopeId}>${ssrInterpolate(formatMoney(((_a = unref(authStore).user) == null ? void 0 : _a.wallet) || 0))}</strong></span></div><button class="text-sm underline hover:no-underline"${ssrIncludeBooleanAttr(pointsBalance.value < 1200) ? " disabled" : ""}${_scopeId}> \u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19 \u2192 </button></div></div>`);
          } else {
            return [
              createVNode("div", { class: "text-white p-2" }, [
                createVNode("div", { class: "flex flex-col lg:flex-row items-center justify-between gap-6" }, [
                  createVNode("div", { class: "flex items-center gap-6" }, [
                    createVNode("div", { class: "w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:star-circle",
                        class: "w-12 h-12"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-white/80 text-sm mb-1" }, "\u0E41\u0E15\u0E49\u0E21\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19"),
                      createVNode("p", { class: "text-4xl lg:text-5xl font-bold" }, toDisplayString(unref(formatPoints)(pointsBalance.value)), 1),
                      createVNode("p", { class: "text-white/80 text-sm mt-1" }, "Plearnd Points")
                    ])
                  ]),
                  createVNode("div", { class: "flex gap-3" }, [
                    createVNode(_component_NuxtLink, {
                      to: "/earn/donates",
                      class: "px-4 py-2 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow"
                    }, {
                      default: withCtx(() => [
                        createVNode(unref(Icon), {
                          icon: "mdi:plus-circle",
                          class: "w-5 h-5"
                        }),
                        createTextVNode(" \u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1E\u0E34\u0E48\u0E21 ")
                      ]),
                      _: 1
                    }),
                    createVNode("button", {
                      class: "px-4 py-2 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 backdrop-blur",
                      disabled: pointsBalance.value < 100,
                      onClick: ($event) => activeTab.value = "transfer"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:send",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21 ")
                    ], 8, ["disabled", "onClick"])
                  ])
                ]),
                createVNode("div", { class: "mt-6 pt-6 border-t border-white/20 flex items-center justify-between" }, [
                  createVNode("div", { class: "flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:wallet",
                      class: "w-5 h-5 text-green-300"
                    }),
                    createVNode("span", null, [
                      createTextVNode("\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19: "),
                      createVNode("strong", null, toDisplayString(formatMoney(((_b = unref(authStore).user) == null ? void 0 : _b.wallet) || 0)), 1)
                    ])
                  ]),
                  createVNode("button", {
                    class: "text-sm underline hover:no-underline",
                    disabled: pointsBalance.value < 1200,
                    onClick: ($event) => activeTab.value = "convert"
                  }, " \u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19 \u2192 ", 8, ["disabled", "onClick"])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="flex gap-2 mb-6 overflow-x-auto pb-2"><!--[-->`);
      ssrRenderList([
        { key: "overview", label: "\u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21", icon: "mdi:view-dashboard" },
        { key: "transfer", label: "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21", icon: "mdi:send" },
        { key: "convert", label: "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19", icon: "mdi:swap-horizontal" },
        { key: "history", label: "\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34", icon: "mdi:history" }
      ], (tab) => {
        _push(`<button class="${ssrRenderClass([activeTab.value === tab.key ? "bg-amber-500 text-white shadow" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700", "px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(tab.label)}</button>`);
      });
      _push(`<!--]--></div>`);
      if (processMessage.value) {
        _push(`<div class="${ssrRenderClass([processSuccess.value ? "bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300" : "bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300", "mb-6 p-4 rounded-xl"])}"><div class="flex items-center gap-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: processSuccess.value ? "mdi:check-circle" : "mdi:alert-circle",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(processMessage.value)}</span><button class="ml-auto">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:close",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "overview") {
        _push(`<div><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">`);
        _push(ssrRenderComponent(_sfc_main$1, {
          class: "cursor-pointer hover:shadow-lg transition-shadow",
          onClick: ($event) => activeTab.value = "transfer"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4 p-2"${_scopeId}><div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:send",
                class: "w-7 h-7 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:chevron-right",
                class: "w-6 h-6 text-gray-400 ml-auto"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                  createVNode("div", { class: "w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:send",
                      class: "w-7 h-7 text-blue-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19")
                  ]),
                  createVNode(unref(Icon), {
                    icon: "mdi:chevron-right",
                    class: "w-6 h-6 text-gray-400 ml-auto"
                  })
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$1, {
          class: "cursor-pointer hover:shadow-lg transition-shadow",
          onClick: ($event) => activeTab.value = "convert"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4 p-2"${_scopeId}><div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:swap-horizontal",
                class: "w-7 h-7 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:chevron-right",
                class: "w-6 h-6 text-gray-400 ml-auto"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                  createVNode("div", { class: "w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:swap-horizontal",
                      class: "w-7 h-7 text-purple-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32")
                  ]),
                  createVNode(unref(Icon), {
                    icon: "mdi:chevron-right",
                    class: "w-6 h-6 text-gray-400 ml-auto"
                  })
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/earn/donates",
          class: "block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_sfc_main$1, { class: "cursor-pointer hover:shadow-lg transition-shadow h-full" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="flex items-center gap-4 p-2"${_scopeId2}><div class="w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:hand-coin",
                      class: "w-7 h-7 text-yellow-500"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div><div${_scopeId2}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId2}>\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</p></div>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:chevron-right",
                      class: "w-6 h-6 text-gray-400 ml-auto"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                        createVNode("div", { class: "w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center" }, [
                          createVNode(unref(Icon), {
                            icon: "mdi:hand-coin",
                            class: "w-7 h-7 text-yellow-500"
                          })
                        ]),
                        createVNode("div", null, [
                          createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"),
                          createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")
                        ]),
                        createVNode(unref(Icon), {
                          icon: "mdi:chevron-right",
                          class: "w-6 h-6 text-gray-400 ml-auto"
                        })
                      ])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_sfc_main$1, { class: "cursor-pointer hover:shadow-lg transition-shadow h-full" }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                      createVNode("div", { class: "w-14 h-14 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:hand-coin",
                          class: "w-7 h-7 text-yellow-500"
                        })
                      ]),
                      createVNode("div", null, [
                        createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"),
                        createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")
                      ]),
                      createVNode(unref(Icon), {
                        icon: "mdi:chevron-right",
                        class: "w-6 h-6 text-gray-400 ml-auto"
                      })
                    ])
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/earn/wallet",
          class: "block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_sfc_main$1, { class: "cursor-pointer hover:shadow-lg transition-shadow h-full" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="flex items-center gap-4 p-2"${_scopeId2}><div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:wallet",
                      class: "w-7 h-7 text-green-500"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div><div${_scopeId2}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId2}>\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId2}>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</p></div>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:chevron-right",
                      class: "w-6 h-6 text-gray-400 ml-auto"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                        createVNode("div", { class: "w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center" }, [
                          createVNode(unref(Icon), {
                            icon: "mdi:wallet",
                            class: "w-7 h-7 text-green-500"
                          })
                        ]),
                        createVNode("div", null, [
                          createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19"),
                          createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32")
                        ]),
                        createVNode(unref(Icon), {
                          icon: "mdi:chevron-right",
                          class: "w-6 h-6 text-gray-400 ml-auto"
                        })
                      ])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_sfc_main$1, { class: "cursor-pointer hover:shadow-lg transition-shadow h-full" }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                      createVNode("div", { class: "w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:wallet",
                          class: "w-7 h-7 text-green-500"
                        })
                      ]),
                      createVNode("div", null, [
                        createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19"),
                        createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32")
                      ]),
                      createVNode(unref(Icon), {
                        icon: "mdi:chevron-right",
                        class: "w-6 h-6 text-gray-400 ml-auto"
                      })
                    ])
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
        _push(ssrRenderComponent(_sfc_main$1, { class: "mb-6" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information-outline",
                class: "w-5 h-5 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E2D\u0E31\u0E15\u0E23\u0E32\u0E01\u0E32\u0E23\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19 </h3><div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4"${_scopeId}><div class="flex items-center justify-center gap-4"${_scopeId}><div class="text-center"${_scopeId}><p class="text-2xl font-bold text-amber-600"${_scopeId}>1,200</p><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:equal",
                class: "w-6 h-6 text-gray-400"
              }, null, _parent2, _scopeId));
              _push2(`<div class="text-center"${_scopeId}><p class="text-2xl font-bold text-green-600"${_scopeId}>1.00</p><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>\u0E1A\u0E32\u0E17</p></div></div></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:information-outline",
                      class: "w-5 h-5 text-blue-500"
                    }),
                    createTextVNode(" \u0E2D\u0E31\u0E15\u0E23\u0E32\u0E01\u0E32\u0E23\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19 ")
                  ]),
                  createVNode("div", { class: "bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4" }, [
                    createVNode("div", { class: "flex items-center justify-center gap-4" }, [
                      createVNode("div", { class: "text-center" }, [
                        createVNode("p", { class: "text-2xl font-bold text-amber-600" }, "1,200"),
                        createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21")
                      ]),
                      createVNode(unref(Icon), {
                        icon: "mdi:equal",
                        class: "w-6 h-6 text-gray-400"
                      }),
                      createVNode("div", { class: "text-center" }, [
                        createVNode("p", { class: "text-2xl font-bold text-green-600" }, "1.00"),
                        createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E1A\u0E32\u0E17")
                      ])
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
              _push2(`<div class="p-2"${_scopeId}><div class="flex items-center justify-between mb-4"${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h3><button class="text-amber-500 text-sm hover:underline"${_scopeId}> \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button></div>`);
              if (transactionsLoading.value) {
                _push2(`<div class="py-8 text-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-8 h-8 text-amber-500 animate-spin mx-auto"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (transactions.value.length > 0) {
                _push2(`<div class="space-y-4"${_scopeId}><!--[-->`);
                ssrRenderList(transactions.value.slice(0, 5), (tx) => {
                  var _a, _b, _c, _d, _e, _f, _g;
                  _push2(`<div class="group relative overflow-hidden p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-900/50 transition-all duration-300"${_scopeId}>`);
                  if (isPositiveTransaction(tx)) {
                    _push2(`<div class="absolute -right-10 -top-10 w-32 h-32 bg-green-500/5 blur-3xl pointer-events-none group-hover:bg-green-500/10 transition-colors"${_scopeId}></div>`);
                  } else {
                    _push2(`<div class="absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl pointer-events-none group-hover:bg-red-500/10 transition-colors"${_scopeId}></div>`);
                  }
                  _push2(`<div class="flex items-center justify-between gap-4 relative z-10"${_scopeId}><div class="flex items-center gap-4 flex-grow"${_scopeId}>`);
                  if (isThreeColumnTransaction(tx)) {
                    _push2(`<div class="flex items-center gap-2 sm:gap-4 flex-grow max-w-[80%]"${_scopeId}><div class="flex flex-col items-center text-center min-w-[64px]"${_scopeId}><img${ssrRenderAttr("src", getOwnerInfo().avatar)}${ssrRenderAttr("alt", getOwnerInfo().name)} class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"${_scopeId}><span class="text-[10px] sm:text-xs font-medium text-gray-500 mt-1 truncate max-w-[64px]"${_scopeId}>${ssrInterpolate(getOwnerInfo().name.split(" ")[0])}</span></div><div class="flex-grow flex flex-col items-center justify-center -mt-4 px-1"${_scopeId}><div class="w-full h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent relative"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: isPositiveTransaction(tx) ? "mdi:chevron-left" : "mdi:chevron-right",
                      class: ["absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-5 h-5", isPositiveTransaction(tx) ? "text-green-500 animate-pulse" : "text-amber-500"]
                    }, null, _parent2, _scopeId));
                    _push2(`</div><span class="${ssrRenderClass([isPositiveTransaction(tx) ? "text-green-600" : "text-amber-600", "text-[9px] uppercase tracking-wider font-bold mt-2"])}"${_scopeId}>${ssrInterpolate(getThreeColumnLabel(tx))}</span></div><div class="flex flex-col items-center text-center min-w-[64px]"${_scopeId}>`);
                    if ((_a = getPartnerInfo(tx)) == null ? void 0 : _a.isSystem) {
                      _push2(`<div class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover:scale-105"${_scopeId}>`);
                      _push2(ssrRenderComponent(unref(Icon), {
                        icon: ((_b = getPartnerInfo(tx)) == null ? void 0 : _b.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "mdi:wallet-outline" : "mdi:star-circle-outline",
                        class: ["w-7 h-7", ((_c = getPartnerInfo(tx)) == null ? void 0 : _c.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "text-indigo-500" : "text-amber-500"]
                      }, null, _parent2, _scopeId));
                      _push2(`</div>`);
                    } else if (getPartnerInfo(tx)) {
                      _push2(`<img${ssrRenderAttr("src", ((_d = getPartnerInfo(tx)) == null ? void 0 : _d.avatar) || getDefaultAvatar(((_e = getPartnerInfo(tx)) == null ? void 0 : _e.name) || "User"))}${ssrRenderAttr("alt", (_f = getPartnerInfo(tx)) == null ? void 0 : _f.name)} class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover:scale-105"${_scopeId}>`);
                    } else {
                      _push2(`<div class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700"${_scopeId}>`);
                      _push2(ssrRenderComponent(unref(Icon), {
                        icon: "mdi:account-question-outline",
                        class: "w-7 h-7 text-gray-400"
                      }, null, _parent2, _scopeId));
                      _push2(`</div>`);
                    }
                    _push2(`<span class="text-[10px] sm:text-xs font-medium text-gray-500 mt-1 truncate max-w-[64px]"${_scopeId}>${ssrInterpolate((((_g = getPartnerInfo(tx)) == null ? void 0 : _g.name) || "?").split(" ")[0])}</span></div></div>`);
                  } else {
                    _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="${ssrRenderClass([isPositiveTransaction(tx) ? "bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800" : "bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800", "w-12 h-12 rounded-2xl flex items-center justify-center transform transition-all group-hover:scale-110 group-hover:rotate-3 shadow-sm"])}"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: getTransactionIcon(tx.transaction_type || tx.type),
                      class: "w-6 h-6"
                    }, null, _parent2, _scopeId));
                    _push2(`</div><div class="max-w-[180px] sm:max-w-none"${_scopeId}><p class="font-bold text-gray-900 dark:text-white leading-tight"${_scopeId}>${ssrInterpolate(getTransactionDisplayTitle(tx))}</p>`);
                    if (getTransactionDisplaySubtitle(tx)) {
                      _push2(`<p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1"${_scopeId}>${ssrInterpolate(getTransactionDisplaySubtitle(tx))}</p>`);
                    } else {
                      _push2(`<!---->`);
                    }
                    _push2(`<div class="flex items-center gap-2 mt-1.5"${_scopeId}><span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase"${_scopeId}>${ssrInterpolate(getTransactionTypeLabel(tx.transaction_type || tx.type))}</span><span class="text-[10px] text-gray-400"${_scopeId}>${ssrInterpolate(formatDate(tx.created_at))}</span></div></div></div>`);
                  }
                  _push2(`</div><div class="text-right flex flex-col items-end min-w-[100px]"${_scopeId}><div class="${ssrRenderClass([isPositiveTransaction(tx) ? "text-green-500" : "text-red-500", "flex items-center font-black text-lg sm:text-xl"])}"${_scopeId}><span class="text-base mr-0.5"${_scopeId}>${ssrInterpolate(isPositiveTransaction(tx) ? "+" : "-")}</span> ${ssrInterpolate(unref(formatPoints)(Math.abs(tx.amount)))}</div><div class="flex flex-col items-end mt-1"${_scopeId}><div class="text-[10px] font-medium text-gray-400 dark:text-gray-500 flex items-center gap-1 bg-gray-50 dark:bg-gray-900/50 px-2 py-0.5 rounded-lg border border-gray-100 dark:border-gray-800"${_scopeId}><span${_scopeId}>\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D:</span><span class="text-gray-600 dark:text-gray-300"${_scopeId}>${ssrInterpolate(unref(formatPoints)(tx.balance_after || 0))}</span></div>`);
                  if (isThreeColumnTransaction(tx)) {
                    _push2(`<p class="text-[9px] text-gray-400 mt-1"${_scopeId}>${ssrInterpolate(formatDate(tx.created_at))}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div></div></div></div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="py-8 text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:inbox-outline",
                  class: "w-12 h-12 mx-auto mb-3"
                }, null, _parent2, _scopeId));
                _push2(`<p${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</p></div>`);
              }
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("div", { class: "flex items-center justify-between mb-4" }, [
                    createVNode("h3", { class: "text-lg font-semibold text-gray-900 dark:text-white" }, "\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14"),
                    createVNode("button", {
                      class: "text-amber-500 text-sm hover:underline",
                      onClick: ($event) => activeTab.value = "history"
                    }, " \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ", 8, ["onClick"])
                  ]),
                  transactionsLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "py-8 text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:loading",
                      class: "w-8 h-8 text-amber-500 animate-spin mx-auto"
                    })
                  ])) : transactions.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-4"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(transactions.value.slice(0, 5), (tx) => {
                      var _a, _b, _c, _d, _e, _f, _g;
                      return openBlock(), createBlock("div", {
                        key: tx.id,
                        class: "group relative overflow-hidden p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-900/50 transition-all duration-300"
                      }, [
                        isPositiveTransaction(tx) ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "absolute -right-10 -top-10 w-32 h-32 bg-green-500/5 blur-3xl pointer-events-none group-hover:bg-green-500/10 transition-colors"
                        })) : (openBlock(), createBlock("div", {
                          key: 1,
                          class: "absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl pointer-events-none group-hover:bg-red-500/10 transition-colors"
                        })),
                        createVNode("div", { class: "flex items-center justify-between gap-4 relative z-10" }, [
                          createVNode("div", { class: "flex items-center gap-4 flex-grow" }, [
                            isThreeColumnTransaction(tx) ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "flex items-center gap-2 sm:gap-4 flex-grow max-w-[80%]"
                            }, [
                              createVNode("div", { class: "flex flex-col items-center text-center min-w-[64px]" }, [
                                createVNode("img", {
                                  src: getOwnerInfo().avatar,
                                  alt: getOwnerInfo().name,
                                  class: "w-12 h-12 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                                }, null, 8, ["src", "alt"]),
                                createVNode("span", { class: "text-[10px] sm:text-xs font-medium text-gray-500 mt-1 truncate max-w-[64px]" }, toDisplayString(getOwnerInfo().name.split(" ")[0]), 1)
                              ]),
                              createVNode("div", { class: "flex-grow flex flex-col items-center justify-center -mt-4 px-1" }, [
                                createVNode("div", { class: "w-full h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent relative" }, [
                                  createVNode(unref(Icon), {
                                    icon: isPositiveTransaction(tx) ? "mdi:chevron-left" : "mdi:chevron-right",
                                    class: ["absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-5 h-5", isPositiveTransaction(tx) ? "text-green-500 animate-pulse" : "text-amber-500"]
                                  }, null, 8, ["icon", "class"])
                                ]),
                                createVNode("span", {
                                  class: ["text-[9px] uppercase tracking-wider font-bold mt-2", isPositiveTransaction(tx) ? "text-green-600" : "text-amber-600"]
                                }, toDisplayString(getThreeColumnLabel(tx)), 3)
                              ]),
                              createVNode("div", { class: "flex flex-col items-center text-center min-w-[64px]" }, [
                                ((_a = getPartnerInfo(tx)) == null ? void 0 : _a.isSystem) ? (openBlock(), createBlock("div", {
                                  key: 0,
                                  class: "w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover:scale-105"
                                }, [
                                  createVNode(unref(Icon), {
                                    icon: ((_b = getPartnerInfo(tx)) == null ? void 0 : _b.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "mdi:wallet-outline" : "mdi:star-circle-outline",
                                    class: ["w-7 h-7", ((_c = getPartnerInfo(tx)) == null ? void 0 : _c.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "text-indigo-500" : "text-amber-500"]
                                  }, null, 8, ["icon", "class"])
                                ])) : getPartnerInfo(tx) ? (openBlock(), createBlock("img", {
                                  key: 1,
                                  src: ((_d = getPartnerInfo(tx)) == null ? void 0 : _d.avatar) || getDefaultAvatar(((_e = getPartnerInfo(tx)) == null ? void 0 : _e.name) || "User"),
                                  alt: (_f = getPartnerInfo(tx)) == null ? void 0 : _f.name,
                                  class: "w-12 h-12 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover:scale-105"
                                }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("div", {
                                  key: 2,
                                  class: "w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700"
                                }, [
                                  createVNode(unref(Icon), {
                                    icon: "mdi:account-question-outline",
                                    class: "w-7 h-7 text-gray-400"
                                  })
                                ])),
                                createVNode("span", { class: "text-[10px] sm:text-xs font-medium text-gray-500 mt-1 truncate max-w-[64px]" }, toDisplayString((((_g = getPartnerInfo(tx)) == null ? void 0 : _g.name) || "?").split(" ")[0]), 1)
                              ])
                            ])) : (openBlock(), createBlock("div", {
                              key: 1,
                              class: "flex items-center gap-4"
                            }, [
                              createVNode("div", {
                                class: ["w-12 h-12 rounded-2xl flex items-center justify-center transform transition-all group-hover:scale-110 group-hover:rotate-3 shadow-sm", isPositiveTransaction(tx) ? "bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800" : "bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800"]
                              }, [
                                createVNode(unref(Icon), {
                                  icon: getTransactionIcon(tx.transaction_type || tx.type),
                                  class: "w-6 h-6"
                                }, null, 8, ["icon"])
                              ], 2),
                              createVNode("div", { class: "max-w-[180px] sm:max-w-none" }, [
                                createVNode("p", { class: "font-bold text-gray-900 dark:text-white leading-tight" }, toDisplayString(getTransactionDisplayTitle(tx)), 1),
                                getTransactionDisplaySubtitle(tx) ? (openBlock(), createBlock("p", {
                                  key: 0,
                                  class: "text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1"
                                }, toDisplayString(getTransactionDisplaySubtitle(tx)), 1)) : createCommentVNode("", true),
                                createVNode("div", { class: "flex items-center gap-2 mt-1.5" }, [
                                  createVNode("span", { class: "text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase" }, toDisplayString(getTransactionTypeLabel(tx.transaction_type || tx.type)), 1),
                                  createVNode("span", { class: "text-[10px] text-gray-400" }, toDisplayString(formatDate(tx.created_at)), 1)
                                ])
                              ])
                            ]))
                          ]),
                          createVNode("div", { class: "text-right flex flex-col items-end min-w-[100px]" }, [
                            createVNode("div", {
                              class: ["flex items-center font-black text-lg sm:text-xl", isPositiveTransaction(tx) ? "text-green-500" : "text-red-500"]
                            }, [
                              createVNode("span", { class: "text-base mr-0.5" }, toDisplayString(isPositiveTransaction(tx) ? "+" : "-"), 1),
                              createTextVNode(" " + toDisplayString(unref(formatPoints)(Math.abs(tx.amount))), 1)
                            ], 2),
                            createVNode("div", { class: "flex flex-col items-end mt-1" }, [
                              createVNode("div", { class: "text-[10px] font-medium text-gray-400 dark:text-gray-500 flex items-center gap-1 bg-gray-50 dark:bg-gray-900/50 px-2 py-0.5 rounded-lg border border-gray-100 dark:border-gray-800" }, [
                                createVNode("span", null, "\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D:"),
                                createVNode("span", { class: "text-gray-600 dark:text-gray-300" }, toDisplayString(unref(formatPoints)(tx.balance_after || 0)), 1)
                              ]),
                              isThreeColumnTransaction(tx) ? (openBlock(), createBlock("p", {
                                key: 0,
                                class: "text-[9px] text-gray-400 mt-1"
                              }, toDisplayString(formatDate(tx.created_at)), 1)) : createCommentVNode("", true)
                            ])
                          ])
                        ])
                      ]);
                    }), 128))
                  ])) : (openBlock(), createBlock("div", {
                    key: 2,
                    class: "py-8 text-center text-gray-500 dark:text-gray-400"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:inbox-outline",
                      class: "w-12 h-12 mx-auto mb-3"
                    }),
                    createVNode("p", null, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23")
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
      if (activeTab.value === "transfer") {
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21</h3><p class="text-sm text-gray-500 dark:text-gray-400 mb-6"${_scopeId}>\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19</p><div class="space-y-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A <span class="text-red-500"${_scopeId}>*</span></label>`);
              if (selectedRecipient.value) {
                _push2(`<div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl mb-2"${_scopeId}><img${ssrRenderAttr("src", selectedRecipient.value.avatar || "/default-avatar.png")}${ssrRenderAttr("alt", selectedRecipient.value.name)} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div class="flex-grow"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(selectedRecipient.value.name)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(selectedRecipient.value.email_masked || selectedRecipient.value.email)}</p></div><button type="button" class="p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:close",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(`</button></div>`);
              } else {
                _push2(`<div class="relative"${_scopeId}><input${ssrRenderAttr("value", userSearchQuery.value)} type="text" class="w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01..."${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:magnify",
                  class: "w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                }, null, _parent2, _scopeId));
                if (userSearchLoading.value) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:loading",
                    class: "w-5 h-5 text-amber-500 animate-spin absolute right-3 top-1/2 -translate-y-1/2"
                  }, null, _parent2, _scopeId));
                } else {
                  _push2(`<!---->`);
                }
                if (showUserDropdown.value && userSearchResults.value.length > 0) {
                  _push2(`<div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-60 overflow-y-auto"${_scopeId}><!--[-->`);
                  ssrRenderList(userSearchResults.value, (user) => {
                    _push2(`<button type="button" class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left"${_scopeId}><img${ssrRenderAttr("src", user.avatar || "/default-avatar.png")}${ssrRenderAttr("alt", user.name)} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(user.name)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(user.email_masked || user.email)}</p></div></button>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (showUserDropdown.value && userSearchQuery.value.length >= 2 && !userSearchLoading.value && userSearchResults.value.length === 0) {
                  _push2(`<div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center text-gray-500"${_scopeId}> \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              }
              _push2(`</div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21 <span class="text-red-500"${_scopeId}>*</span></label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", transferForm.value.amount)} type="number" min="1"${ssrRenderAttr("max", pointsBalance.value)} class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</span></div><p class="text-xs text-gray-500 mt-1"${_scopeId}> \u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13: ${ssrInterpolate(unref(formatPoints)(pointsBalance.value))} \u0E41\u0E15\u0E49\u0E21 </p><div class="flex flex-wrap gap-2 mt-3"${_scopeId}><!--[-->`);
              ssrRenderList(quickTransferAmounts, (amount) => {
                _push2(`<button type="button" class="${ssrRenderClass([transferForm.value.amount === amount ? "bg-amber-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${ssrIncludeBooleanAttr(amount > pointsBalance.value) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(formatPoints)(amount))}</button>`);
              });
              _push2(`<!--]--></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21</label><textarea rows="2" maxlength="255" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E16\u0E36\u0E07\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)"${_scopeId}>${ssrInterpolate(transferForm.value.message)}</textarea></div><button type="button" class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || !selectedRecipient.value || transferForm.value.amount <= 0 || transferForm.value.amount > pointsBalance.value) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E42\u0E2D\u0E19 ${unref(formatPoints)(transferForm.value.amount)} \u0E41\u0E15\u0E49\u0E21`)}</button></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-2" }, "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21"),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400 mb-6" }, "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19"),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                        createTextVNode(" \u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A "),
                        createVNode("span", { class: "text-red-500" }, "*")
                      ]),
                      selectedRecipient.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl mb-2"
                      }, [
                        createVNode("img", {
                          src: selectedRecipient.value.avatar || "/default-avatar.png",
                          alt: selectedRecipient.value.name,
                          class: "w-10 h-10 rounded-full object-cover"
                        }, null, 8, ["src", "alt"]),
                        createVNode("div", { class: "flex-grow" }, [
                          createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(selectedRecipient.value.name), 1),
                          createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(selectedRecipient.value.email_masked || selectedRecipient.value.email), 1)
                        ]),
                        createVNode("button", {
                          type: "button",
                          class: "p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded",
                          onClick: clearRecipient
                        }, [
                          createVNode(unref(Icon), {
                            icon: "mdi:close",
                            class: "w-5 h-5"
                          })
                        ])
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "relative"
                      }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => userSearchQuery.value = $event,
                          type: "text",
                          class: "w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500",
                          placeholder: "\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01...",
                          onInput: debouncedSearch,
                          onFocus: ($event) => showUserDropdown.value = userSearchResults.value.length > 0
                        }, null, 40, ["onUpdate:modelValue", "onFocus"]), [
                          [vModelText, userSearchQuery.value]
                        ]),
                        createVNode(unref(Icon), {
                          icon: "mdi:magnify",
                          class: "w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                        }),
                        userSearchLoading.value ? (openBlock(), createBlock(unref(Icon), {
                          key: 0,
                          icon: "mdi:loading",
                          class: "w-5 h-5 text-amber-500 animate-spin absolute right-3 top-1/2 -translate-y-1/2"
                        })) : createCommentVNode("", true),
                        showUserDropdown.value && userSearchResults.value.length > 0 ? (openBlock(), createBlock("div", {
                          key: 1,
                          class: "absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-60 overflow-y-auto"
                        }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(userSearchResults.value, (user) => {
                            return openBlock(), createBlock("button", {
                              key: user.id,
                              type: "button",
                              class: "w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left",
                              onClick: ($event) => selectRecipient(user)
                            }, [
                              createVNode("img", {
                                src: user.avatar || "/default-avatar.png",
                                alt: user.name,
                                class: "w-10 h-10 rounded-full object-cover"
                              }, null, 8, ["src", "alt"]),
                              createVNode("div", null, [
                                createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(user.name), 1),
                                createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(user.email_masked || user.email), 1)
                              ])
                            ], 8, ["onClick"]);
                          }), 128))
                        ])) : createCommentVNode("", true),
                        showUserDropdown.value && userSearchQuery.value.length >= 2 && !userSearchLoading.value && userSearchResults.value.length === 0 ? (openBlock(), createBlock("div", {
                          key: 2,
                          class: "absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center text-gray-500"
                        }, " \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 ")) : createCommentVNode("", true)
                      ]))
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                        createTextVNode(" \u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21 "),
                        createVNode("span", { class: "text-red-500" }, "*")
                      ]),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => transferForm.value.amount = $event,
                          type: "number",
                          min: "1",
                          max: pointsBalance.value,
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21"
                        }, null, 8, ["onUpdate:modelValue", "max"]), [
                          [
                            vModelText,
                            transferForm.value.amount,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("span", { class: "absolute right-4 top-1/2 -translate-y-1/2 text-gray-500" }, "\u0E41\u0E15\u0E49\u0E21")
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500 mt-1" }, " \u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13: " + toDisplayString(unref(formatPoints)(pointsBalance.value)) + " \u0E41\u0E15\u0E49\u0E21 ", 1),
                      createVNode("div", { class: "flex flex-wrap gap-2 mt-3" }, [
                        (openBlock(), createBlock(Fragment, null, renderList(quickTransferAmounts, (amount) => {
                          return createVNode("button", {
                            key: amount,
                            type: "button",
                            class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", transferForm.value.amount === amount ? "bg-amber-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"],
                            disabled: amount > pointsBalance.value,
                            onClick: ($event) => transferForm.value.amount = amount
                          }, toDisplayString(unref(formatPoints)(amount)), 11, ["disabled", "onClick"]);
                        }), 64))
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21"),
                      withDirectives(createVNode("textarea", {
                        "onUpdate:modelValue": ($event) => transferForm.value.message = $event,
                        rows: "2",
                        maxlength: "255",
                        class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500",
                        placeholder: "\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E16\u0E36\u0E07\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, transferForm.value.message]
                      ])
                    ]),
                    createVNode("button", {
                      type: "button",
                      class: "w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || !selectedRecipient.value || transferForm.value.amount <= 0 || transferForm.value.amount > pointsBalance.value,
                      onClick: handleTransfer
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E42\u0E2D\u0E19 ${unref(formatPoints)(transferForm.value.amount)} \u0E41\u0E15\u0E49\u0E21`), 1)
                    ], 8, ["disabled"])
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "convert") {
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400 mb-6"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</p><div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6"${_scopeId}><div class="flex items-center gap-2 text-blue-700 dark:text-blue-300"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="text-sm"${_scopeId}>\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19: <strong${_scopeId}>1,200 \u0E41\u0E15\u0E49\u0E21 = 1 \u0E1A\u0E32\u0E17</strong></span></div></div><div class="space-y-6"${_scopeId}><div class="flex flex-wrap gap-2 mb-4"${_scopeId}><!--[-->`);
              ssrRenderList(quickConvertAmounts, (amount) => {
                _push2(`<button type="button" class="${ssrRenderClass([convertForm.value.points === amount ? "bg-amber-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${ssrIncludeBooleanAttr(amount > pointsBalance.value) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(formatPoints)(amount))} (${ssrInterpolate((amount / 1200).toFixed(0))}\u0E3F) </button>`);
              });
              _push2(`<!--]--></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E41\u0E1B\u0E25\u0E07 <span class="text-red-500"${_scopeId}>*</span></label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", convertForm.value.points)} type="number" min="1200" step="1200"${ssrRenderAttr("max", pointsBalance.value)} class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</span></div><p class="text-xs text-gray-500 mt-1"${_scopeId}> \u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13: ${ssrInterpolate(unref(formatPoints)(pointsBalance.value))} \u0E41\u0E15\u0E49\u0E21 | \u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33: 1,200 \u0E41\u0E15\u0E49\u0E21 </p></div><div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4"${_scopeId}><h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"${_scopeId}>\u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E41\u0E1B\u0E25\u0E07</h4><div class="flex items-center justify-between"${_scopeId}><div class="text-center"${_scopeId}><p class="text-2xl font-bold text-amber-600"${_scopeId}>${ssrInterpolate(unref(formatPoints)(conversionPreview.value.points))}</p><p class="text-sm text-gray-500"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:arrow-right",
                class: "w-8 h-8 text-gray-400"
              }, null, _parent2, _scopeId));
              _push2(`<div class="text-center"${_scopeId}><p class="text-2xl font-bold text-green-600"${_scopeId}>${ssrInterpolate(formatMoney(conversionPreview.value.money))}</p><p class="text-sm text-gray-500"${_scopeId}>\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</p></div></div>`);
              if (!conversionPreview.value.isValid && convertForm.value.points > 0) {
                _push2(`<div class="mt-3 text-center text-sm text-red-500"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:alert-circle",
                  class: "w-4 h-4 inline mr-1"
                }, null, _parent2, _scopeId));
                if (convertForm.value.points < 1200) {
                  _push2(`<span${_scopeId}>\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 1,200 \u0E41\u0E15\u0E49\u0E21</span>`);
                } else {
                  _push2(`<span${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D</span>`);
                }
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><button type="button" class="w-full py-3 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || !conversionPreview.value.isValid) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E41\u0E1B\u0E25\u0E07 ${unref(formatPoints)(convertForm.value.points)} \u0E41\u0E15\u0E49\u0E21 \u0E40\u0E1B\u0E47\u0E19 ${formatMoney(conversionPreview.value.money)}`)}</button></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-2" }, "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19"),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400 mb-6" }, "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32"),
                  createVNode("div", { class: "bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6" }, [
                    createVNode("div", { class: "flex items-center gap-2 text-blue-700 dark:text-blue-300" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:information",
                        class: "w-5 h-5"
                      }),
                      createVNode("span", { class: "text-sm" }, [
                        createTextVNode("\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19: "),
                        createVNode("strong", null, "1,200 \u0E41\u0E15\u0E49\u0E21 = 1 \u0E1A\u0E32\u0E17")
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", { class: "flex flex-wrap gap-2 mb-4" }, [
                      (openBlock(), createBlock(Fragment, null, renderList(quickConvertAmounts, (amount) => {
                        return createVNode("button", {
                          key: amount,
                          type: "button",
                          class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", convertForm.value.points === amount ? "bg-amber-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"],
                          disabled: amount > pointsBalance.value,
                          onClick: ($event) => convertForm.value.points = amount
                        }, toDisplayString(unref(formatPoints)(amount)) + " (" + toDisplayString((amount / 1200).toFixed(0)) + "\u0E3F) ", 11, ["disabled", "onClick"]);
                      }), 64))
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                        createTextVNode(" \u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E41\u0E1B\u0E25\u0E07 "),
                        createVNode("span", { class: "text-red-500" }, "*")
                      ]),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => convertForm.value.points = $event,
                          type: "number",
                          min: "1200",
                          step: "1200",
                          max: pointsBalance.value,
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21"
                        }, null, 8, ["onUpdate:modelValue", "max"]), [
                          [
                            vModelText,
                            convertForm.value.points,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("span", { class: "absolute right-4 top-1/2 -translate-y-1/2 text-gray-500" }, "\u0E41\u0E15\u0E49\u0E21")
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500 mt-1" }, " \u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13: " + toDisplayString(unref(formatPoints)(pointsBalance.value)) + " \u0E41\u0E15\u0E49\u0E21 | \u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33: 1,200 \u0E41\u0E15\u0E49\u0E21 ", 1)
                    ]),
                    createVNode("div", { class: "bg-gray-50 dark:bg-gray-700 rounded-xl p-4" }, [
                      createVNode("h4", { class: "text-sm font-medium text-gray-700 dark:text-gray-300 mb-3" }, "\u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E41\u0E1B\u0E25\u0E07"),
                      createVNode("div", { class: "flex items-center justify-between" }, [
                        createVNode("div", { class: "text-center" }, [
                          createVNode("p", { class: "text-2xl font-bold text-amber-600" }, toDisplayString(unref(formatPoints)(conversionPreview.value.points)), 1),
                          createVNode("p", { class: "text-sm text-gray-500" }, "\u0E41\u0E15\u0E49\u0E21")
                        ]),
                        createVNode(unref(Icon), {
                          icon: "mdi:arrow-right",
                          class: "w-8 h-8 text-gray-400"
                        }),
                        createVNode("div", { class: "text-center" }, [
                          createVNode("p", { class: "text-2xl font-bold text-green-600" }, toDisplayString(formatMoney(conversionPreview.value.money)), 1),
                          createVNode("p", { class: "text-sm text-gray-500" }, "\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32")
                        ])
                      ]),
                      !conversionPreview.value.isValid && convertForm.value.points > 0 ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-3 text-center text-sm text-red-500"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:alert-circle",
                          class: "w-4 h-4 inline mr-1"
                        }),
                        convertForm.value.points < 1200 ? (openBlock(), createBlock("span", { key: 0 }, "\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 1,200 \u0E41\u0E15\u0E49\u0E21")) : (openBlock(), createBlock("span", { key: 1 }, "\u0E41\u0E15\u0E49\u0E21\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D"))
                      ])) : createCommentVNode("", true)
                    ]),
                    createVNode("button", {
                      type: "button",
                      class: "w-full py-3 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || !conversionPreview.value.isValid,
                      onClick: handleConvert
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E41\u0E1B\u0E25\u0E07 ${unref(formatPoints)(convertForm.value.points)} \u0E41\u0E15\u0E49\u0E21 \u0E40\u0E1B\u0E47\u0E19 ${formatMoney(conversionPreview.value.money)}`), 1)
                    ], 8, ["disabled"])
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "history") {
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</h3><div class="flex flex-wrap gap-3 mb-6"${_scopeId}><select class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(transactionFilters.value.type) ? ssrLooseContain(transactionFilters.value.type, "") : ssrLooseEqual(transactionFilters.value.type, "")) ? " selected" : ""}${_scopeId}>\u0E17\u0E38\u0E01\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</option><option value="earn"${ssrIncludeBooleanAttr(Array.isArray(transactionFilters.value.type) ? ssrLooseContain(transactionFilters.value.type, "earn") : ssrLooseEqual(transactionFilters.value.type, "earn")) ? " selected" : ""}${_scopeId}>\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21</option><option value="spend"${ssrIncludeBooleanAttr(Array.isArray(transactionFilters.value.type) ? ssrLooseContain(transactionFilters.value.type, "spend") : ssrLooseEqual(transactionFilters.value.type, "spend")) ? " selected" : ""}${_scopeId}>\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21</option><option value="transfer_in"${ssrIncludeBooleanAttr(Array.isArray(transactionFilters.value.type) ? ssrLooseContain(transactionFilters.value.type, "transfer_in") : ssrLooseEqual(transactionFilters.value.type, "transfer_in")) ? " selected" : ""}${_scopeId}>\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21</option><option value="transfer_out"${ssrIncludeBooleanAttr(Array.isArray(transactionFilters.value.type) ? ssrLooseContain(transactionFilters.value.type, "transfer_out") : ssrLooseEqual(transactionFilters.value.type, "transfer_out")) ? " selected" : ""}${_scopeId}>\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E2D\u0E2D\u0E01</option><option value="conversion"${ssrIncludeBooleanAttr(Array.isArray(transactionFilters.value.type) ? ssrLooseContain(transactionFilters.value.type, "conversion") : ssrLooseEqual(transactionFilters.value.type, "conversion")) ? " selected" : ""}${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</option></select><input${ssrRenderAttr("value", transactionFilters.value.date_from)} type="date" class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"${_scopeId}><input${ssrRenderAttr("value", transactionFilters.value.date_to)} type="date" class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"${_scopeId}><button type="button" class="px-4 py-2 text-amber-600 dark:text-amber-400 text-sm hover:underline"${_scopeId}> \u0E25\u0E49\u0E32\u0E07\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 </button></div>`);
              if (transactionsLoading.value) {
                _push2(`<div class="py-8 text-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-8 h-8 text-amber-500 animate-spin mx-auto"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-500 mt-2"${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
              } else if (transactions.value.length > 0) {
                _push2(`<div class="space-y-4"${_scopeId}><!--[-->`);
                ssrRenderList(transactions.value, (tx) => {
                  var _a, _b, _c, _d, _e, _f, _g;
                  _push2(`<div class="group relative overflow-hidden p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-900/50 transition-all duration-300"${_scopeId}>`);
                  if (isPositiveTransaction(tx)) {
                    _push2(`<div class="absolute -right-10 -top-10 w-32 h-32 bg-green-500/5 blur-3xl pointer-events-none group-hover:bg-green-500/10 transition-colors"${_scopeId}></div>`);
                  } else {
                    _push2(`<div class="absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl pointer-events-none group-hover:bg-red-500/10 transition-colors"${_scopeId}></div>`);
                  }
                  _push2(`<div class="flex items-center justify-between gap-4 relative z-10"${_scopeId}><div class="flex items-center gap-4 flex-grow"${_scopeId}>`);
                  if (isThreeColumnTransaction(tx)) {
                    _push2(`<div class="flex items-center gap-4 flex-grow max-w-[80%]"${_scopeId}><div class="flex flex-col items-center text-center min-w-[72px]"${_scopeId}><img${ssrRenderAttr("src", getOwnerInfo().avatar)}${ssrRenderAttr("alt", getOwnerInfo().name)} class="w-14 h-14 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"${_scopeId}><span class="text-xs font-medium text-gray-500 mt-1 truncate max-w-[72px]"${_scopeId}>${ssrInterpolate(getOwnerInfo().name)}</span></div><div class="flex-grow flex flex-col items-center justify-center -mt-4 px-2"${_scopeId}><div class="w-full h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent relative"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: isPositiveTransaction(tx) ? "mdi:chevron-left" : "mdi:chevron-right",
                      class: ["absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-6 h-6", isPositiveTransaction(tx) ? "text-green-500 animate-pulse" : "text-amber-500"]
                    }, null, _parent2, _scopeId));
                    _push2(`</div><span class="${ssrRenderClass([isPositiveTransaction(tx) ? "text-green-600" : "text-amber-600", "text-[10px] uppercase tracking-wider font-bold mt-2"])}"${_scopeId}>${ssrInterpolate(getThreeColumnLabel(tx))}</span></div><div class="flex flex-col items-center text-center min-w-[72px]"${_scopeId}>`);
                    if ((_a = getPartnerInfo(tx)) == null ? void 0 : _a.isSystem) {
                      _push2(`<div class="w-14 h-14 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"${_scopeId}>`);
                      _push2(ssrRenderComponent(unref(Icon), {
                        icon: ((_b = getPartnerInfo(tx)) == null ? void 0 : _b.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "mdi:wallet-outline" : "mdi:star-circle-outline",
                        class: ["w-8 h-8", ((_c = getPartnerInfo(tx)) == null ? void 0 : _c.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "text-indigo-500" : "text-amber-500"]
                      }, null, _parent2, _scopeId));
                      _push2(`</div>`);
                    } else if (getPartnerInfo(tx)) {
                      _push2(`<img${ssrRenderAttr("src", ((_d = getPartnerInfo(tx)) == null ? void 0 : _d.avatar) || getDefaultAvatar(((_e = getPartnerInfo(tx)) == null ? void 0 : _e.name) || "User"))}${ssrRenderAttr("alt", (_f = getPartnerInfo(tx)) == null ? void 0 : _f.name)} class="w-14 h-14 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"${_scopeId}>`);
                    } else {
                      _push2(`<div class="w-14 h-14 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700"${_scopeId}>`);
                      _push2(ssrRenderComponent(unref(Icon), {
                        icon: "mdi:account-question-outline",
                        class: "w-8 h-8 text-gray-400"
                      }, null, _parent2, _scopeId));
                      _push2(`</div>`);
                    }
                    _push2(`<span class="text-xs font-medium text-gray-500 mt-1 truncate max-w-[72px]"${_scopeId}>${ssrInterpolate(((_g = getPartnerInfo(tx)) == null ? void 0 : _g.name) || "?")}</span></div></div>`);
                  } else {
                    _push2(`<div class="flex items-center gap-4"${_scopeId}><div class="${ssrRenderClass([isPositiveTransaction(tx) ? "bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800" : "bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800", "w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm"])}"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: getTransactionIcon(tx.transaction_type || tx.type),
                      class: "w-6 h-6"
                    }, null, _parent2, _scopeId));
                    _push2(`</div><div${_scopeId}><p class="font-bold text-gray-900 dark:text-white leading-tight"${_scopeId}>${ssrInterpolate(getTransactionDisplayTitle(tx))}</p>`);
                    if (getTransactionDisplaySubtitle(tx)) {
                      _push2(`<p class="text-xs text-gray-500 dark:text-gray-400 mt-1"${_scopeId}>${ssrInterpolate(getTransactionDisplaySubtitle(tx))}</p>`);
                    } else {
                      _push2(`<!---->`);
                    }
                    _push2(`<div class="flex items-center gap-2 mt-1.5"${_scopeId}><span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase"${_scopeId}>${ssrInterpolate(getTransactionTypeLabel(tx.transaction_type || tx.type))}</span><span class="text-[10px] text-gray-400"${_scopeId}>${ssrInterpolate(formatDate(tx.created_at))}</span></div></div></div>`);
                  }
                  _push2(`</div><div class="text-right flex flex-col items-end min-w-[120px]"${_scopeId}><div class="${ssrRenderClass([isPositiveTransaction(tx) ? "text-green-500" : "text-red-500", "flex items-center font-black text-xl"])}"${_scopeId}><span class="text-base mr-0.5"${_scopeId}>${ssrInterpolate(isPositiveTransaction(tx) ? "+" : "-")}</span> ${ssrInterpolate(unref(formatPoints)(Math.abs(tx.amount)))}</div><div class="flex flex-col items-end mt-1"${_scopeId}><div class="text-xs font-medium text-gray-400 dark:text-gray-500 flex items-center gap-1 bg-gray-50 dark:bg-gray-900/50 px-3 py-1 rounded-lg border border-gray-100 dark:border-gray-800 mt-1"${_scopeId}><span${_scopeId}>\u0E22\u0E2D\u0E14\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D:</span><span class="text-gray-700 dark:text-gray-200 font-bold"${_scopeId}>${ssrInterpolate(unref(formatPoints)(tx.balance_after || 0))}</span></div>`);
                  if (isThreeColumnTransaction(tx)) {
                    _push2(`<p class="text-[10px] text-gray-400 mt-1.5"${_scopeId}>${ssrInterpolate(formatDate(tx.created_at))}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div></div></div></div>`);
                });
                _push2(`<!--]-->`);
                if (transactionsPagination.value.total_pages > 1) {
                  _push2(`<div class="flex justify-center gap-2 mt-6"${_scopeId}><button type="button" class="${ssrRenderClass([transactionFilters.value.page === 1 ? "bg-gray-100 text-gray-400 cursor-not-allowed" : "bg-gray-100 text-gray-600 hover:bg-gray-200", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${ssrIncludeBooleanAttr(transactionFilters.value.page === 1) ? " disabled" : ""}${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:chevron-left",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(`</button><span class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400"${_scopeId}> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(transactionsPagination.value.current_page)} / ${ssrInterpolate(transactionsPagination.value.total_pages)}</span><button type="button" class="${ssrRenderClass([transactionFilters.value.page === transactionsPagination.value.total_pages ? "bg-gray-100 text-gray-400 cursor-not-allowed" : "bg-gray-100 text-gray-600 hover:bg-gray-200", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${ssrIncludeBooleanAttr(transactionFilters.value.page === transactionsPagination.value.total_pages) ? " disabled" : ""}${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:chevron-right",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(`</button></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                _push2(`<div class="py-12 text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:inbox-outline",
                  class: "w-16 h-16 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-lg"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</p><p class="text-sm mt-1"${_scopeId}>\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E04\u0E38\u0E13\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 \u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48</p></div>`);
              }
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23"),
                  createVNode("div", { class: "flex flex-wrap gap-3 mb-6" }, [
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => transactionFilters.value.type = $event,
                      class: "px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    }, [
                      createVNode("option", { value: "" }, "\u0E17\u0E38\u0E01\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17"),
                      createVNode("option", { value: "earn" }, "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21"),
                      createVNode("option", { value: "spend" }, "\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21"),
                      createVNode("option", { value: "transfer_in" }, "\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21"),
                      createVNode("option", { value: "transfer_out" }, "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E2D\u0E2D\u0E01"),
                      createVNode("option", { value: "conversion" }, "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19")
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, transactionFilters.value.type]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => transactionFilters.value.date_from = $event,
                      type: "date",
                      class: "px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, transactionFilters.value.date_from]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => transactionFilters.value.date_to = $event,
                      type: "date",
                      class: "px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, transactionFilters.value.date_to]
                    ]),
                    createVNode("button", {
                      type: "button",
                      class: "px-4 py-2 text-amber-600 dark:text-amber-400 text-sm hover:underline",
                      onClick: ($event) => transactionFilters.value = { type: "", date_from: "", date_to: "", page: 1, per_page: 20 }
                    }, " \u0E25\u0E49\u0E32\u0E07\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 ", 8, ["onClick"])
                  ]),
                  transactionsLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "py-8 text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:loading",
                      class: "w-8 h-8 text-amber-500 animate-spin mx-auto"
                    }),
                    createVNode("p", { class: "text-gray-500 mt-2" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...")
                  ])) : transactions.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-4"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(transactions.value, (tx) => {
                      var _a, _b, _c, _d, _e, _f, _g;
                      return openBlock(), createBlock("div", {
                        key: tx.id,
                        class: "group relative overflow-hidden p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 dark:hover:border-amber-900/50 transition-all duration-300"
                      }, [
                        isPositiveTransaction(tx) ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "absolute -right-10 -top-10 w-32 h-32 bg-green-500/5 blur-3xl pointer-events-none group-hover:bg-green-500/10 transition-colors"
                        })) : (openBlock(), createBlock("div", {
                          key: 1,
                          class: "absolute -right-10 -top-10 w-32 h-32 bg-red-500/5 blur-3xl pointer-events-none group-hover:bg-red-500/10 transition-colors"
                        })),
                        createVNode("div", { class: "flex items-center justify-between gap-4 relative z-10" }, [
                          createVNode("div", { class: "flex items-center gap-4 flex-grow" }, [
                            isThreeColumnTransaction(tx) ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "flex items-center gap-4 flex-grow max-w-[80%]"
                            }, [
                              createVNode("div", { class: "flex flex-col items-center text-center min-w-[72px]" }, [
                                createVNode("img", {
                                  src: getOwnerInfo().avatar,
                                  alt: getOwnerInfo().name,
                                  class: "w-14 h-14 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                                }, null, 8, ["src", "alt"]),
                                createVNode("span", { class: "text-xs font-medium text-gray-500 mt-1 truncate max-w-[72px]" }, toDisplayString(getOwnerInfo().name), 1)
                              ]),
                              createVNode("div", { class: "flex-grow flex flex-col items-center justify-center -mt-4 px-2" }, [
                                createVNode("div", { class: "w-full h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent relative" }, [
                                  createVNode(unref(Icon), {
                                    icon: isPositiveTransaction(tx) ? "mdi:chevron-left" : "mdi:chevron-right",
                                    class: ["absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-6 h-6", isPositiveTransaction(tx) ? "text-green-500 animate-pulse" : "text-amber-500"]
                                  }, null, 8, ["icon", "class"])
                                ]),
                                createVNode("span", {
                                  class: ["text-[10px] uppercase tracking-wider font-bold mt-2", isPositiveTransaction(tx) ? "text-green-600" : "text-amber-600"]
                                }, toDisplayString(getThreeColumnLabel(tx)), 3)
                              ]),
                              createVNode("div", { class: "flex flex-col items-center text-center min-w-[72px]" }, [
                                ((_a = getPartnerInfo(tx)) == null ? void 0 : _a.isSystem) ? (openBlock(), createBlock("div", {
                                  key: 0,
                                  class: "w-14 h-14 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                                }, [
                                  createVNode(unref(Icon), {
                                    icon: ((_b = getPartnerInfo(tx)) == null ? void 0 : _b.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "mdi:wallet-outline" : "mdi:star-circle-outline",
                                    class: ["w-8 h-8", ((_c = getPartnerInfo(tx)) == null ? void 0 : _c.name) === "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19" ? "text-indigo-500" : "text-amber-500"]
                                  }, null, 8, ["icon", "class"])
                                ])) : getPartnerInfo(tx) ? (openBlock(), createBlock("img", {
                                  key: 1,
                                  src: ((_d = getPartnerInfo(tx)) == null ? void 0 : _d.avatar) || getDefaultAvatar(((_e = getPartnerInfo(tx)) == null ? void 0 : _e.name) || "User"),
                                  alt: (_f = getPartnerInfo(tx)) == null ? void 0 : _f.name,
                                  class: "w-14 h-14 rounded-full object-cover ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700 shadow-sm"
                                }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("div", {
                                  key: 2,
                                  class: "w-14 h-14 rounded-full flex items-center justify-center bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-50 dark:ring-gray-900 border border-gray-100 dark:border-gray-700"
                                }, [
                                  createVNode(unref(Icon), {
                                    icon: "mdi:account-question-outline",
                                    class: "w-8 h-8 text-gray-400"
                                  })
                                ])),
                                createVNode("span", { class: "text-xs font-medium text-gray-500 mt-1 truncate max-w-[72px]" }, toDisplayString(((_g = getPartnerInfo(tx)) == null ? void 0 : _g.name) || "?"), 1)
                              ])
                            ])) : (openBlock(), createBlock("div", {
                              key: 1,
                              class: "flex items-center gap-4"
                            }, [
                              createVNode("div", {
                                class: ["w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm", isPositiveTransaction(tx) ? "bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-800" : "bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-800"]
                              }, [
                                createVNode(unref(Icon), {
                                  icon: getTransactionIcon(tx.transaction_type || tx.type),
                                  class: "w-6 h-6"
                                }, null, 8, ["icon"])
                              ], 2),
                              createVNode("div", null, [
                                createVNode("p", { class: "font-bold text-gray-900 dark:text-white leading-tight" }, toDisplayString(getTransactionDisplayTitle(tx)), 1),
                                getTransactionDisplaySubtitle(tx) ? (openBlock(), createBlock("p", {
                                  key: 0,
                                  class: "text-xs text-gray-500 dark:text-gray-400 mt-1"
                                }, toDisplayString(getTransactionDisplaySubtitle(tx)), 1)) : createCommentVNode("", true),
                                createVNode("div", { class: "flex items-center gap-2 mt-1.5" }, [
                                  createVNode("span", { class: "text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 uppercase" }, toDisplayString(getTransactionTypeLabel(tx.transaction_type || tx.type)), 1),
                                  createVNode("span", { class: "text-[10px] text-gray-400" }, toDisplayString(formatDate(tx.created_at)), 1)
                                ])
                              ])
                            ]))
                          ]),
                          createVNode("div", { class: "text-right flex flex-col items-end min-w-[120px]" }, [
                            createVNode("div", {
                              class: ["flex items-center font-black text-xl", isPositiveTransaction(tx) ? "text-green-500" : "text-red-500"]
                            }, [
                              createVNode("span", { class: "text-base mr-0.5" }, toDisplayString(isPositiveTransaction(tx) ? "+" : "-"), 1),
                              createTextVNode(" " + toDisplayString(unref(formatPoints)(Math.abs(tx.amount))), 1)
                            ], 2),
                            createVNode("div", { class: "flex flex-col items-end mt-1" }, [
                              createVNode("div", { class: "text-xs font-medium text-gray-400 dark:text-gray-500 flex items-center gap-1 bg-gray-50 dark:bg-gray-900/50 px-3 py-1 rounded-lg border border-gray-100 dark:border-gray-800 mt-1" }, [
                                createVNode("span", null, "\u0E22\u0E2D\u0E14\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D:"),
                                createVNode("span", { class: "text-gray-700 dark:text-gray-200 font-bold" }, toDisplayString(unref(formatPoints)(tx.balance_after || 0)), 1)
                              ]),
                              isThreeColumnTransaction(tx) ? (openBlock(), createBlock("p", {
                                key: 0,
                                class: "text-[10px] text-gray-400 mt-1.5"
                              }, toDisplayString(formatDate(tx.created_at)), 1)) : createCommentVNode("", true)
                            ])
                          ])
                        ])
                      ]);
                    }), 128)),
                    transactionsPagination.value.total_pages > 1 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "flex justify-center gap-2 mt-6"
                    }, [
                      createVNode("button", {
                        type: "button",
                        class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", transactionFilters.value.page === 1 ? "bg-gray-100 text-gray-400 cursor-not-allowed" : "bg-gray-100 text-gray-600 hover:bg-gray-200"],
                        disabled: transactionFilters.value.page === 1,
                        onClick: ($event) => transactionFilters.value.page--
                      }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:chevron-left",
                          class: "w-5 h-5"
                        })
                      ], 10, ["disabled", "onClick"]),
                      createVNode("span", { class: "px-4 py-2 text-sm text-gray-600 dark:text-gray-400" }, " \u0E2B\u0E19\u0E49\u0E32 " + toDisplayString(transactionsPagination.value.current_page) + " / " + toDisplayString(transactionsPagination.value.total_pages), 1),
                      createVNode("button", {
                        type: "button",
                        class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", transactionFilters.value.page === transactionsPagination.value.total_pages ? "bg-gray-100 text-gray-400 cursor-not-allowed" : "bg-gray-100 text-gray-600 hover:bg-gray-200"],
                        disabled: transactionFilters.value.page === transactionsPagination.value.total_pages,
                        onClick: ($event) => transactionFilters.value.page++
                      }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:chevron-right",
                          class: "w-5 h-5"
                        })
                      ], 10, ["disabled", "onClick"])
                    ])) : createCommentVNode("", true)
                  ])) : (openBlock(), createBlock("div", {
                    key: 2,
                    class: "py-12 text-center text-gray-500 dark:text-gray-400"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:inbox-outline",
                      class: "w-16 h-16 mx-auto mb-4"
                    }),
                    createVNode("p", { class: "text-lg" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23"),
                    createVNode("p", { class: "text-sm mt-1" }, "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E04\u0E38\u0E13\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 \u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48")
                  ]))
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (activeTab.value === "overview") {
        _push(`<div class="mt-8"><h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center"> \u0E17\u0E33\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21 </h2><div class="grid grid-cols-2 md:grid-cols-4 gap-4">`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/earn/donates",
          class: "block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="w-12 h-12 mx-auto mb-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:hand-coin",
                      class: "w-6 h-6 text-yellow-600 dark:text-yellow-400"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white text-sm"${_scopeId2}>\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</h3><p class="text-xs text-green-600 dark:text-green-400 mt-1"${_scopeId2}>+240 \u0E41\u0E15\u0E49\u0E21</p>`);
                  } else {
                    return [
                      createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:hand-coin",
                          class: "w-6 h-6 text-yellow-600 dark:text-yellow-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"),
                      createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "+240 \u0E41\u0E15\u0E49\u0E21")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:hand-coin",
                        class: "w-6 h-6 text-yellow-600 dark:text-yellow-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"),
                    createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "+240 \u0E41\u0E15\u0E49\u0E21")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/newsfeed",
          class: "block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="w-12 h-12 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:post-outline",
                      class: "w-6 h-6 text-blue-600 dark:text-blue-400"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white text-sm"${_scopeId2}>\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32</h3><p class="text-xs text-green-600 dark:text-green-400 mt-1"${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E16\u0E39\u0E01\u0E43\u0E08</p>`);
                  } else {
                    return [
                      createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:post-outline",
                          class: "w-6 h-6 text-blue-600 dark:text-blue-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32"),
                      createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E16\u0E39\u0E01\u0E43\u0E08")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:post-outline",
                        class: "w-6 h-6 text-blue-600 dark:text-blue-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32"),
                    createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E16\u0E39\u0E01\u0E43\u0E08")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/courses",
          class: "block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="w-12 h-12 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:school",
                      class: "w-6 h-6 text-purple-600 dark:text-purple-400"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white text-sm"${_scopeId2}>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</h3><p class="text-xs text-green-600 dark:text-green-400 mt-1"${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p>`);
                  } else {
                    return [
                      createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:school",
                          class: "w-6 h-6 text-purple-600 dark:text-purple-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"),
                      createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:school",
                        class: "w-6 h-6 text-purple-600 dark:text-purple-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"),
                    createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/quests",
          class: "block"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="w-12 h-12 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"${_scopeId2}>`);
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:trophy",
                      class: "w-6 h-6 text-amber-600 dark:text-amber-400"
                    }, null, _parent3, _scopeId2));
                    _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white text-sm"${_scopeId2}>\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08</h3><p class="text-xs text-green-600 dark:text-green-400 mt-1"${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E42\u0E1A\u0E19\u0E31\u0E2A</p>`);
                  } else {
                    return [
                      createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:trophy",
                          class: "w-6 h-6 text-amber-600 dark:text-amber-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08"),
                      createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E42\u0E1A\u0E19\u0E31\u0E2A")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group text-center" }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "w-12 h-12 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:trophy",
                        class: "w-6 h-6 text-amber-600 dark:text-amber-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white text-sm" }, "\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08"),
                    createVNode("p", { class: "text-xs text-green-600 dark:text-green-400 mt-1" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E42\u0E1A\u0E19\u0E31\u0E2A")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Points.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Points-BzEOF14-.mjs.map
