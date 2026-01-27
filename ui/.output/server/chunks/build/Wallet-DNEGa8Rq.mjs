import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, toDisplayString, createTextVNode, createBlock, openBlock, Fragment, renderList, withDirectives, vModelText, createCommentVNode, vModelSelect, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, d as useAuthStore, i as useApi, b as useRuntimeConfig } from './server.mjs';
import { u as useWallet } from './useWallet-DPSZCLqI.mjs';
import { u as usePoints } from './usePoints-DipNhVzo.mjs';
import { _ as _sfc_main$2 } from './BaseCard-Baxif1fS.mjs';
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

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "UnifiedTransactionCard",
  __ssrInlineRender: true,
  props: {
    id: {},
    transactionType: {},
    actionType: {},
    amount: {},
    balanceAfter: {},
    createdAt: {},
    source: {},
    destination: {},
    description: {},
    status: {}
  },
  setup(__props) {
    const props = __props;
    const formatMoney = (amount) => {
      return new Intl.NumberFormat("th-TH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(Math.abs(amount));
    };
    const formatPoints = (points) => {
      return new Intl.NumberFormat("th-TH").format(Math.abs(points));
    };
    const formatDate = (dateStr) => {
      const date = new Date(dateStr);
      return date.toLocaleDateString("th-TH", {
        day: "numeric",
        month: "short",
        year: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const isPositive = computed(() => props.amount > 0);
    const getActionLabel = computed(() => {
      const walletLabels = {
        "transfer_in": "\u0E42\u0E2D\u0E19\u0E40\u0E02\u0E49\u0E32",
        "transfer_out": "\u0E42\u0E2D\u0E19\u0E2D\u0E2D\u0E01",
        "transfer": isPositive.value ? "\u0E42\u0E2D\u0E19\u0E40\u0E02\u0E49\u0E32" : "\u0E42\u0E2D\u0E19\u0E2D\u0E2D\u0E01",
        "convert_to_points": "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E15\u0E49\u0E21",
        "convert_to_wallet": "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19",
        "deposit": "\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19",
        "withdraw": "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19",
        "purchase": "\u0E0B\u0E37\u0E49\u0E2D",
        "refund": "\u0E04\u0E37\u0E19\u0E40\u0E07\u0E34\u0E19",
        "reward": "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"
      };
      const pointsLabels = {
        "earn": "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21",
        "spend": "\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21",
        "transfer_in": "\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21",
        "transfer_out": "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21",
        "transfer": isPositive.value ? "\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21" : "\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21",
        "convert_from_wallet": "\u0E41\u0E1B\u0E25\u0E07\u0E08\u0E32\u0E01\u0E40\u0E07\u0E34\u0E19",
        "convert_to_wallet": "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19",
        "reward": "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25",
        "bonus": "\u0E42\u0E1A\u0E19\u0E31\u0E2A",
        "refund": "\u0E04\u0E37\u0E19\u0E41\u0E15\u0E49\u0E21"
      };
      const labels = props.transactionType === "wallet" ? walletLabels : pointsLabels;
      return labels[props.actionType] || props.actionType;
    });
    const getSystemIcon = (type, isSource) => {
      if (props.transactionType === "wallet") {
        const icons = {
          "convert_to_points": isSource ? "mdi:wallet" : "mdi:star-circle",
          "deposit": "mdi:bank",
          "withdraw": "mdi:bank-transfer-out",
          "purchase": "mdi:cart",
          "refund": "mdi:cash-refund",
          "reward": "mdi:gift"
        };
        return icons[props.actionType] || "mdi:wallet";
      } else {
        const icons = {
          "convert_from_wallet": isSource ? "mdi:wallet" : "mdi:star-circle",
          "convert_to_wallet": isSource ? "mdi:star-circle" : "mdi:wallet",
          "earn": "mdi:star-plus",
          "spend": "mdi:star-minus",
          "reward": "mdi:gift",
          "bonus": "mdi:party-popper"
        };
        return icons[props.actionType] || "mdi:star";
      }
    };
    const getColors = computed(() => {
      if (props.transactionType === "wallet") {
        return {
          positive: {
            text: "text-green-400",
            bg: "bg-green-500/10",
            glow: "bg-green-500/5 group-hover:bg-green-500/10",
            ring: "ring-green-500/30",
            border: "border-green-500/40"
          },
          negative: {
            text: "text-red-400",
            bg: "bg-red-500/10",
            glow: "bg-red-500/5 group-hover:bg-red-500/10",
            ring: "ring-red-500/30",
            border: "border-red-500/40"
          }
        };
      } else {
        return {
          positive: {
            text: "text-amber-400",
            bg: "bg-amber-500/10",
            glow: "bg-amber-500/5 group-hover:bg-amber-500/10",
            ring: "ring-amber-500/30",
            border: "border-amber-500/40"
          },
          negative: {
            text: "text-purple-400",
            bg: "bg-purple-500/10",
            glow: "bg-purple-500/5 group-hover:bg-purple-500/10",
            ring: "ring-purple-500/30",
            border: "border-purple-500/40"
          }
        };
      }
    });
    const currentColors = computed(() => isPositive.value ? getColors.value.positive : getColors.value.negative);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "group relative overflow-hidden bg-gray-900/90 backdrop-blur-sm border border-gray-700/50 rounded-xl sm:rounded-2xl hover:border-gray-600 transition-all duration-300" }, _attrs))}><div class="${ssrRenderClass([unref(currentColors).glow, "absolute -right-10 -top-10 w-32 h-32 blur-3xl pointer-events-none transition-colors"])}"></div><div class="relative z-10 p-3 sm:p-4"><div class="flex items-center gap-2 sm:gap-3"><div class="flex items-center gap-1.5 sm:gap-3 flex-grow min-w-0"><div class="flex flex-col items-center text-center flex-shrink-0 w-12 sm:w-16">`);
      if (__props.source.avatar && !__props.source.isSystem) {
        _push(`<img${ssrRenderAttr("src", __props.source.avatar)}${ssrRenderAttr("alt", __props.source.name)} class="w-9 h-9 sm:w-11 sm:h-11 rounded-full object-cover ring-2 ring-gray-800 border border-gray-700 shadow-lg">`);
      } else {
        _push(`<div class="${ssrRenderClass([[
          __props.source.isSystem ? unref(currentColors).bg : "bg-gray-800",
          __props.source.isSystem ? unref(currentColors).ring : "ring-gray-700",
          __props.source.isSystem ? unref(currentColors).border : "border-gray-600"
        ], "w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center ring-2 shadow-lg"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: __props.source.systemIcon || getSystemIcon(__props.actionType, true),
          class: ["w-4 h-4 sm:w-5 sm:h-5", __props.source.isSystem ? unref(currentColors).text : "text-gray-400"]
        }, null, _parent));
        _push(`</div>`);
      }
      _push(`<span class="text-[8px] sm:text-[10px] font-medium text-gray-400 mt-1 truncate w-full px-0.5">${ssrInterpolate(__props.source.name)}</span></div><div class="flex-grow flex flex-col items-center justify-center -mt-2 sm:-mt-3 min-w-0 px-0.5"><div class="w-full flex items-center justify-center"><div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"></div>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:chevron-right",
        class: ["w-4 h-4 sm:w-5 sm:h-5 mx-0.5 flex-shrink-0", unref(currentColors).text]
      }, null, _parent));
      _push(`<div class="flex-grow h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"></div></div><span class="${ssrRenderClass([unref(currentColors).text, "text-[7px] sm:text-[9px] uppercase tracking-wide font-bold mt-1 sm:mt-1.5 text-center leading-tight"])}">${ssrInterpolate(unref(getActionLabel))}</span></div><div class="flex flex-col items-center text-center flex-shrink-0 w-12 sm:w-16">`);
      if (__props.destination.avatar && !__props.destination.isSystem) {
        _push(`<img${ssrRenderAttr("src", __props.destination.avatar)}${ssrRenderAttr("alt", __props.destination.name)} class="w-9 h-9 sm:w-11 sm:h-11 rounded-full object-cover ring-2 ring-gray-800 border border-gray-700 shadow-lg">`);
      } else {
        _push(`<div class="${ssrRenderClass([[unref(currentColors).bg, unref(currentColors).ring, unref(currentColors).border], "w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center ring-2 shadow-lg"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: __props.destination.systemIcon || getSystemIcon(__props.actionType, false),
          class: ["w-4 h-4 sm:w-5 sm:h-5", unref(currentColors).text]
        }, null, _parent));
        _push(`</div>`);
      }
      _push(`<span class="text-[8px] sm:text-[10px] font-medium text-gray-400 mt-1 truncate w-full px-0.5">${ssrInterpolate(__props.destination.name)}</span></div></div><div class="flex flex-col items-end flex-shrink-0 min-w-[75px] sm:min-w-[110px]"><div class="${ssrRenderClass([unref(currentColors).text, "flex items-center font-black text-sm sm:text-lg leading-none"])}"><span class="text-xs sm:text-base">${ssrInterpolate(unref(isPositive) ? "+" : "-")}</span>`);
      if (__props.transactionType === "wallet") {
        _push(`<!--[--><span class="text-[10px] sm:text-sm">\u0E3F</span><span>${ssrInterpolate(formatMoney(__props.amount))}</span><!--]-->`);
      } else {
        _push(`<!--[--><span>${ssrInterpolate(formatPoints(__props.amount))}</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:star",
          class: "w-3.5 h-3.5 sm:w-4 sm:h-4 ml-0.5"
        }, null, _parent));
        _push(`<!--]-->`);
      }
      _push(`</div>`);
      if (__props.balanceAfter !== void 0) {
        _push(`<div class="flex items-center gap-0.5 mt-0.5 sm:mt-1 text-[8px] sm:text-[10px] text-gray-500 bg-gray-800/50 px-1.5 py-0.5 rounded"><span>\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D:</span>`);
        if (__props.transactionType === "wallet") {
          _push(`<span class="text-gray-300 font-semibold">\u0E3F${ssrInterpolate(formatMoney(__props.balanceAfter))}</span>`);
        } else {
          _push(`<!--[--><span class="text-amber-300 font-semibold">${ssrInterpolate(formatPoints(__props.balanceAfter))}</span>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:star",
            class: "w-2.5 h-2.5 text-amber-300"
          }, null, _parent));
          _push(`<!--]-->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<p class="text-[8px] sm:text-[10px] text-gray-500 mt-0.5">${ssrInterpolate(formatDate(__props.createdAt))}</p></div></div></div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Common/UnifiedTransactionCard.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Wallet",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19 - Nuxni"
    });
    const authStore = useAuthStore();
    const { get } = useApi();
    const {
      getBalance,
      withdraw,
      transfer,
      convertToPoints,
      getTransactions,
      createDepositRequest,
      getDepositRequests,
      cancelDepositRequest,
      formatMoney,
      calculateFee,
      getNetAmount
    } = useWallet();
    const { points, convertToWallet, formatPoints } = usePoints();
    const transactions = ref([]);
    const transactionsLoading = ref(false);
    const depositRequests = ref([]);
    const depositRequestsLoading = ref(false);
    const activeTab = ref("overview");
    const depositForm = ref({
      amount: 100,
      transfer_date: "",
      transfer_time: "",
      transfer_slip: null
    });
    const withdrawForm = ref({
      amount: 100,
      method: "bank_transfer",
      bank_account: {
        bank_name: "",
        account_number: "",
        account_name: ""
      }
    });
    const transferForm = ref({
      recipient_id: "",
      amount: 10,
      message: ""
    });
    const userSearchQuery = ref("");
    const userSearchResults = ref([]);
    const userSearchLoading = ref(false);
    const selectedRecipient = ref(null);
    const showUserDropdown = ref(false);
    const config = useRuntimeConfig();
    computed(() => config.public.apiBase || "");
    const searchUsers = async () => {
      var _a;
      if (userSearchQuery.value.length < 2) {
        userSearchResults.value = [];
        return;
      }
      try {
        userSearchLoading.value = true;
        const response = await get("/api/users/search", {
          params: {
            q: userSearchQuery.value,
            limit: 10
          }
        });
        if (response.success) {
          userSearchResults.value = (response.data || []).filter((u) => {
            var _a2;
            return u.id !== ((_a2 = authStore.user) == null ? void 0 : _a2.id);
          });
          showUserDropdown.value = true;
        }
      } catch (err) {
        console.error("Search users error:", err);
        userSearchResults.value = [];
        if (err.statusCode === 401 || ((_a = err.message) == null ? void 0 : _a.includes("Unauthenticated"))) {
          processMessage.value = "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49";
          processSuccess.value = false;
        }
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
    const convertForm = ref({
      points: 1200
    });
    const convertToPointsForm = ref({
      amount: 10
    });
    const isProcessing = ref(false);
    const processSuccess = ref(false);
    const processMessage = ref("");
    const quickDepositAmounts = [100, 300, 500, 1e3, 2e3, 5e3];
    const quickWithdrawAmounts = [100, 500, 1e3, 2e3, 5e3];
    const bankOptions = [
      { value: "kbank", label: "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E2A\u0E34\u0E01\u0E23\u0E44\u0E17\u0E22", icon: "\u{1F3E6}" },
      { value: "scb", label: "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E44\u0E17\u0E22\u0E1E\u0E32\u0E13\u0E34\u0E0A\u0E22\u0E4C", icon: "\u{1F3E6}" },
      { value: "bbl", label: "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E23\u0E38\u0E07\u0E40\u0E17\u0E1E", icon: "\u{1F3E6}" },
      { value: "ktb", label: "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E23\u0E38\u0E07\u0E44\u0E17\u0E22", icon: "\u{1F3E6}" },
      { value: "bay", label: "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E23\u0E38\u0E07\u0E28\u0E23\u0E35\u0E2D\u0E22\u0E38\u0E18\u0E22\u0E32", icon: "\u{1F3E6}" },
      { value: "tmb", label: "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E17\u0E2B\u0E32\u0E23\u0E44\u0E17\u0E22\u0E18\u0E19\u0E0A\u0E32\u0E15", icon: "\u{1F3E6}" }
    ];
    const walletBalance = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.wallet) || 0;
    });
    const conversionPreview = computed(() => {
      const pts = convertForm.value.points;
      const rate = 1200;
      return {
        points: pts,
        money: pts / rate,
        isValid: pts >= rate && pts <= (points.value || 0)
      };
    });
    const convertToPointsPreview = computed(() => {
      const amount = convertToPointsForm.value.amount;
      const rate = 1200;
      return {
        amount,
        points: amount * rate,
        isValid: amount >= 10 && amount <= walletBalance.value
      };
    });
    const withdrawPreview = computed(() => {
      const amount = withdrawForm.value.amount;
      const fee = calculateFee(amount);
      const net = getNetAmount(amount);
      return { amount, fee, net };
    });
    const slipPreview = ref(null);
    const handleSlipUpload = (event) => {
      var _a;
      const target = event.target;
      const file = (_a = target.files) == null ? void 0 : _a[0];
      if (file) {
        if (!file.type.startsWith("image/")) {
          processMessage.value = "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E44\u0E1F\u0E25\u0E4C\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19";
          processSuccess.value = false;
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          processMessage.value = "\u0E02\u0E19\u0E32\u0E14\u0E44\u0E1F\u0E25\u0E4C\u0E15\u0E49\u0E2D\u0E07\u0E44\u0E21\u0E48\u0E40\u0E01\u0E34\u0E19 5MB";
          processSuccess.value = false;
          return;
        }
        depositForm.value.transfer_slip = file;
        const reader = new FileReader();
        reader.onload = (e) => {
          var _a2;
          slipPreview.value = (_a2 = e.target) == null ? void 0 : _a2.result;
        };
        reader.readAsDataURL(file);
      }
    };
    const clearSlip = () => {
      depositForm.value.transfer_slip = null;
      slipPreview.value = null;
    };
    const loadTransactions = async () => {
      try {
        transactionsLoading.value = true;
        const data = await getTransactions({ per_page: 20 });
        transactions.value = data.transactions || data.data || [];
      } catch (err) {
        console.error("Failed to load transactions:", err);
      } finally {
        transactionsLoading.value = false;
      }
    };
    const loadDepositRequests = async () => {
      try {
        depositRequestsLoading.value = true;
        const data = await getDepositRequests({ per_page: 20 });
        depositRequests.value = data || [];
      } catch (err) {
        console.error("Failed to load deposit requests:", err);
      } finally {
        depositRequestsLoading.value = false;
      }
    };
    const handleCancelRequest = async (requestId) => {
      if (!confirm("\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?")) return;
      try {
        isProcessing.value = true;
        await cancelDepositRequest(requestId);
        processSuccess.value = true;
        processMessage.value = "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08";
        await loadDepositRequests();
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14";
      } finally {
        isProcessing.value = false;
      }
    };
    const getStatusBadgeClass = (status) => {
      switch (status) {
        case "pending":
          return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400";
        case "approved":
          return "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400";
        case "rejected":
          return "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400";
        default:
          return "bg-gray-100 text-gray-800";
      }
    };
    const handleDeposit = async () => {
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        if (!depositForm.value.transfer_slip) {
          throw new Error("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19");
        }
        if (!depositForm.value.transfer_date) {
          throw new Error("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E30\u0E1A\u0E38\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19");
        }
        const formData = new FormData();
        formData.append("amount", depositForm.value.amount.toString());
        formData.append("payment_method", "bank_transfer");
        formData.append("transfer_date", depositForm.value.transfer_date);
        formData.append("transfer_time", depositForm.value.transfer_time);
        formData.append("transfer_slip", depositForm.value.transfer_slip);
        await createDepositRequest(formData);
        processSuccess.value = true;
        processMessage.value = `\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 ${formatMoney(depositForm.value.amount)} \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08! \u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D Admin \u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34`;
        await loadDepositRequests();
        depositForm.value = {
          amount: 100,
          transfer_date: "",
          transfer_time: "",
          transfer_slip: null
        };
        slipPreview.value = null;
        activeTab.value = "deposit-requests";
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14";
      } finally {
        isProcessing.value = false;
      }
    };
    const handleWithdraw = async () => {
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        await withdraw({
          amount: withdrawForm.value.amount,
          method: withdrawForm.value.method,
          bank_account: withdrawForm.value.bank_account
        });
        processSuccess.value = true;
        processMessage.value = `\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 ${formatMoney(withdrawForm.value.amount)} \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!`;
        await getBalance();
        await loadTransactions();
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14";
      } finally {
        isProcessing.value = false;
      }
    };
    const handleTransfer = async () => {
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        await transfer({
          recipient_id: parseInt(transferForm.value.recipient_id),
          amount: transferForm.value.amount,
          message: transferForm.value.message
        });
        processSuccess.value = true;
        processMessage.value = `\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 ${formatMoney(transferForm.value.amount)} \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!`;
        await getBalance();
        await loadTransactions();
        transferForm.value.recipient_id = "";
        transferForm.value.amount = 10;
        transferForm.value.message = "";
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14";
      } finally {
        isProcessing.value = false;
      }
    };
    const handleConvert = async () => {
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        await convertToWallet(convertForm.value.points);
        processSuccess.value = true;
        processMessage.value = `\u0E41\u0E1B\u0E25\u0E07 ${formatPoints(convertForm.value.points)} \u0E41\u0E15\u0E49\u0E21 \u0E40\u0E1B\u0E47\u0E19 ${formatMoney(conversionPreview.value.money)} \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!`;
        await getBalance();
        await loadTransactions();
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14";
      } finally {
        isProcessing.value = false;
      }
    };
    const handleConvertToPoints = async () => {
      try {
        isProcessing.value = true;
        processSuccess.value = false;
        await convertToPoints(convertToPointsForm.value.amount);
        processSuccess.value = true;
        processMessage.value = `\u0E41\u0E1B\u0E25\u0E07 ${formatMoney(convertToPointsForm.value.amount)} \u0E40\u0E1B\u0E47\u0E19 ${formatPoints(convertToPointsPreview.value.points)} \u0E41\u0E15\u0E49\u0E21 \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!`;
        await getBalance();
        await loadTransactions();
      } catch (err) {
        processSuccess.value = false;
        processMessage.value = err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14";
      } finally {
        isProcessing.value = false;
      }
    };
    const isPositiveTransaction = (tx) => {
      var _a;
      const type = tx.transaction_type || tx.type;
      if (["deposit", "transfer_in"].includes(type)) return true;
      if (["withdraw", "transfer_out"].includes(type)) return false;
      if (type === "conversion") {
        return ((_a = tx.metadata) == null ? void 0 : _a.conversion_type) === "points_to_money";
      }
      if (type === "admin_adjust") {
        return tx.amount > 0;
      }
      return tx.amount > 0;
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
        const isPointsToMoney = ((_a = tx.metadata) == null ? void 0 : _a.conversion_type) === "points_to_money";
        return {
          name: isPointsToMoney ? "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E1E\u0E2D\u0E22\u0E17\u0E4C" : "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E1E\u0E2D\u0E22\u0E17\u0E4C",
          avatar: null,
          isSystem: true
        };
      }
      return null;
    };
    const transformTransaction = (tx) => {
      var _a, _b, _c, _d, _e;
      const type = tx.transaction_type || tx.type;
      const isPositive = isPositiveTransaction(tx);
      const owner = getOwnerInfo();
      const partner = getPartnerInfo(tx);
      let actionType = type;
      if (type === "conversion") {
        actionType = ((_a = tx.metadata) == null ? void 0 : _a.conversion_type) === "points_to_money" ? "convert_to_wallet" : "convert_to_points";
      } else if (type === "transfer_in" || type === "transfer_out") {
        actionType = "transfer";
      }
      let source, destination;
      if (isPositive) {
        source = partner ? {
          avatar: partner.avatar || void 0,
          name: ((_b = partner.name) == null ? void 0 : _b.split(" ")[0]) || "\u0E23\u0E30\u0E1A\u0E1A",
          isSystem: partner.isSystem || false,
          systemIcon: partner.isSystem ? "mdi:star-circle" : void 0
        } : {
          name: "\u0E23\u0E30\u0E1A\u0E1A",
          isSystem: true,
          systemIcon: "mdi:bank"
        };
        destination = {
          avatar: owner.avatar,
          name: ((_c = owner.name) == null ? void 0 : _c.split(" ")[0]) || "\u0E09\u0E31\u0E19",
          isSystem: false
        };
      } else {
        source = {
          avatar: owner.avatar,
          name: ((_d = owner.name) == null ? void 0 : _d.split(" ")[0]) || "\u0E09\u0E31\u0E19",
          isSystem: false
        };
        destination = partner ? {
          avatar: partner.avatar || void 0,
          name: ((_e = partner.name) == null ? void 0 : _e.split(" ")[0]) || "\u0E1B\u0E25\u0E32\u0E22\u0E17\u0E32\u0E07",
          isSystem: partner.isSystem || false,
          systemIcon: partner.isSystem ? actionType === "convert_to_points" ? "mdi:star-circle" : "mdi:wallet" : void 0
        } : {
          name: "\u0E1B\u0E25\u0E32\u0E22\u0E17\u0E32\u0E07",
          isSystem: true,
          systemIcon: "mdi:wallet"
        };
      }
      return {
        id: tx.id,
        transactionType: "wallet",
        actionType,
        amount: isPositive ? Math.abs(tx.amount) : -Math.abs(tx.amount),
        balanceAfter: tx.balance_after,
        createdAt: tx.created_at,
        source,
        destination,
        description: tx.description
      };
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-4xl mx-auto"><div class="text-center mb-8"><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-full flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:wallet",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"> \u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19 </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32 \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 \u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 \u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 \u0E41\u0E25\u0E30\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19 </p></div>`);
      _push(ssrRenderComponent(_sfc_main$2, { class: "mb-6 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 border-0" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-white p-2"${_scopeId}><div class="flex flex-col lg:flex-row items-center justify-between gap-6"${_scopeId}><div class="flex items-center gap-6"${_scopeId}><div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:wallet",
              class: "w-12 h-12"
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><p class="text-white/80 text-sm mb-1"${_scopeId}>\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D</p><p class="text-4xl lg:text-5xl font-bold"${_scopeId}>${ssrInterpolate(unref(formatMoney)(walletBalance.value))}</p></div></div><div class="flex gap-3"${_scopeId}><button class="px-4 py-2 bg-white text-teal-600 font-semibold rounded-xl hover:bg-teal-50 transition-colors flex items-center gap-2 shadow"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:plus",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 </button><button class="px-4 py-2 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 backdrop-blur"${ssrIncludeBooleanAttr(walletBalance.value < 100) ? " disabled" : ""}${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:minus",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 </button></div></div><div class="mt-6 pt-6 border-t border-white/20 flex items-center justify-between"${_scopeId}><div class="flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:star-circle",
              class: "w-5 h-5 text-yellow-300"
            }, null, _parent2, _scopeId));
            _push2(`<span${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E21\u0E35: <strong${_scopeId}>${ssrInterpolate(unref(formatPoints)(unref(points)))}</strong> \u0E41\u0E15\u0E49\u0E21</span></div><button class="text-sm underline hover:no-underline"${_scopeId}> \u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19 \u2192 </button></div></div>`);
          } else {
            return [
              createVNode("div", { class: "text-white p-2" }, [
                createVNode("div", { class: "flex flex-col lg:flex-row items-center justify-between gap-6" }, [
                  createVNode("div", { class: "flex items-center gap-6" }, [
                    createVNode("div", { class: "w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:wallet",
                        class: "w-12 h-12"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-white/80 text-sm mb-1" }, "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D"),
                      createVNode("p", { class: "text-4xl lg:text-5xl font-bold" }, toDisplayString(unref(formatMoney)(walletBalance.value)), 1)
                    ])
                  ]),
                  createVNode("div", { class: "flex gap-3" }, [
                    createVNode("button", {
                      class: "px-4 py-2 bg-white text-teal-600 font-semibold rounded-xl hover:bg-teal-50 transition-colors flex items-center gap-2 shadow",
                      onClick: ($event) => activeTab.value = "deposit"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:plus",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 ")
                    ], 8, ["onClick"]),
                    createVNode("button", {
                      class: "px-4 py-2 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 backdrop-blur",
                      disabled: walletBalance.value < 100,
                      onClick: ($event) => activeTab.value = "withdraw"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:minus",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 ")
                    ], 8, ["disabled", "onClick"])
                  ])
                ]),
                createVNode("div", { class: "mt-6 pt-6 border-t border-white/20 flex items-center justify-between" }, [
                  createVNode("div", { class: "flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:star-circle",
                      class: "w-5 h-5 text-yellow-300"
                    }),
                    createVNode("span", null, [
                      createTextVNode("\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E21\u0E35: "),
                      createVNode("strong", null, toDisplayString(unref(formatPoints)(unref(points))), 1),
                      createTextVNode(" \u0E41\u0E15\u0E49\u0E21")
                    ])
                  ]),
                  createVNode("button", {
                    class: "text-sm underline hover:no-underline",
                    onClick: ($event) => activeTab.value = "convert"
                  }, " \u0E41\u0E1B\u0E25\u0E07\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19 \u2192 ", 8, ["onClick"])
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
        { key: "deposit", label: "\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19", icon: "mdi:plus-circle" },
        { key: "deposit-requests", label: "\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19", icon: "mdi:clock-outline", badge: depositRequests.value.filter((r) => r.status === "pending").length },
        { key: "withdraw", label: "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19", icon: "mdi:minus-circle" },
        { key: "transfer", label: "\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19", icon: "mdi:send" },
        { key: "convert", label: "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19", icon: "mdi:swap-horizontal" },
        { key: "convert-to-points", label: "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E07\u0E34\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E15\u0E49\u0E21", icon: "mdi:swap-horizontal-circle" },
        { key: "history", label: "\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34", icon: "mdi:history" }
      ], (tab) => {
        _push(`<button class="${ssrRenderClass([activeTab.value === tab.key ? "bg-primary-500 text-white shadow" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700", "px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(tab.label)} `);
        if (tab.badge && tab.badge > 0) {
          _push(`<span class="ml-1 px-1.5 py-0.5 text-xs bg-yellow-500 text-white rounded-full">${ssrInterpolate(tab.badge)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
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
        _push(ssrRenderComponent(_sfc_main$2, {
          class: "cursor-pointer hover:shadow-lg transition-shadow",
          onClick: ($event) => activeTab.value = "deposit"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4 p-2"${_scopeId}><div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:plus-circle",
                class: "w-7 h-7 text-green-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:chevron-right",
                class: "w-6 h-6 text-gray-400 ml-auto"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                  createVNode("div", { class: "w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:plus-circle",
                      class: "w-7 h-7 text-green-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32")
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
        _push(ssrRenderComponent(_sfc_main$2, {
          class: "cursor-pointer hover:shadow-lg transition-shadow",
          onClick: ($event) => activeTab.value = "withdraw"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-4 p-2"${_scopeId}><div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:minus-circle",
                class: "w-7 h-7 text-red-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23</p></div>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:chevron-right",
                class: "w-6 h-6 text-gray-400 ml-auto"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-4 p-2" }, [
                  createVNode("div", { class: "w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:minus-circle",
                      class: "w-7 h-7 text-red-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23")
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
        _push(ssrRenderComponent(_sfc_main$2, {
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
              _push2(`</div><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19</p></div>`);
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
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19")
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
        _push(ssrRenderComponent(_sfc_main$2, {
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
              _push2(`</div><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21</h3><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</p></div>`);
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
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19")
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
        _push(`</div>`);
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><div class="flex items-center justify-between mb-4"${_scopeId}><h3 class="text-lg font-semibold text-gray-900 dark:text-white"${_scopeId}>\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</h3><button class="text-primary-500 text-sm hover:underline"${_scopeId}> \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button></div>`);
              if (transactionsLoading.value) {
                _push2(`<div class="py-8 text-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-8 h-8 text-primary-500 animate-spin mx-auto"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (transactions.value.length > 0) {
                _push2(`<div class="space-y-2 sm:space-y-3"${_scopeId}><!--[-->`);
                ssrRenderList(transactions.value.slice(0, 5), (tx) => {
                  _push2(ssrRenderComponent(_sfc_main$1, mergeProps({
                    key: tx.id
                  }, { ref_for: true }, transformTransaction(tx)), null, _parent2, _scopeId));
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
                      class: "text-primary-500 text-sm hover:underline",
                      onClick: ($event) => activeTab.value = "history"
                    }, " \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ", 8, ["onClick"])
                  ]),
                  transactionsLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "py-8 text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:loading",
                      class: "w-8 h-8 text-primary-500 animate-spin mx-auto"
                    })
                  ])) : transactions.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-2 sm:space-y-3"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(transactions.value.slice(0, 5), (tx) => {
                      return openBlock(), createBlock(_sfc_main$1, mergeProps({
                        key: tx.id
                      }, { ref_for: true }, transformTransaction(tx)), null, 16);
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
      if (activeTab.value === "deposit") {
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32</h3><p class="text-sm text-gray-500 dark:text-gray-400 mb-6"${_scopeId}>\u0E01\u0E23\u0E38\u0E13\u0E32\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19 \u0E23\u0E2D Admin \u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p><div class="bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-xl p-4 mb-6"${_scopeId}><h4 class="font-semibold text-gray-900 dark:text-white mb-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:bank",
                class: "w-5 h-5 inline mr-2"
              }, null, _parent2, _scopeId));
              _push2(` \u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19 </h4><div class="space-y-2 text-sm"${_scopeId}><div class="flex justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23:</span><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E2A\u0E34\u0E01\u0E23\u0E44\u0E17\u0E22</span></div><div class="flex justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E0D\u0E0A\u0E35:</span><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>xxx-x-xxxxx-x</span></div><div class="flex justify-between"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35:</span><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E1A\u0E23\u0E34\u0E29\u0E31\u0E17 \u0E40\u0E1E\u0E25\u0E34\u0E19\u0E4C\u0E14\u0E35 \u0E08\u0E33\u0E01\u0E31\u0E14</span></div></div></div><div class="space-y-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 <span class="text-red-500"${_scopeId}>*</span></label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", depositForm.value.amount)} type="number" min="10" step="10" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E1A\u0E32\u0E17</span></div><div class="flex flex-wrap gap-2 mt-3"${_scopeId}><!--[-->`);
              ssrRenderList(quickDepositAmounts, (amount) => {
                _push2(`<button type="button" class="${ssrRenderClass([depositForm.value.amount === amount ? "bg-primary-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${_scopeId}>${ssrInterpolate(amount)} \u0E1A\u0E32\u0E17 </button>`);
              });
              _push2(`<!--]--></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-4"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 <span class="text-red-500"${_scopeId}>*</span></label><input${ssrRenderAttr("value", depositForm.value.transfer_date)} type="date" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E40\u0E27\u0E25\u0E32\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19</label><input${ssrRenderAttr("value", depositForm.value.transfer_time)} type="time" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"${_scopeId}></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E2A\u0E25\u0E34\u0E1B) <span class="text-red-500"${_scopeId}>*</span></label>`);
              if (!slipPreview.value) {
                _push2(`<div class="mt-1"${_scopeId}><label class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors"${_scopeId}><div class="flex flex-col items-center justify-center py-5"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:cloud-upload",
                  class: "w-10 h-10 text-gray-400 mb-3"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</p><p class="text-xs text-gray-500 mt-1"${_scopeId}>PNG, JPG (\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 5MB)</p></div><input type="file" accept="image/*" class="hidden"${_scopeId}></label></div>`);
              } else {
                _push2(`<div class="relative mt-1"${_scopeId}><img${ssrRenderAttr("src", slipPreview.value)} alt="Transfer slip preview" class="w-full max-h-64 object-contain rounded-xl border border-gray-200 dark:border-gray-600"${_scopeId}><button type="button" class="absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:close",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(`</button></div>`);
              }
              _push2(`</div><button type="button" class="w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || depositForm.value.amount < 10 || !depositForm.value.transfer_slip || !depositForm.value.transfer_date) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 ${unref(formatMoney)(depositForm.value.amount)}`)}</button><p class="text-xs text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information",
                class: "w-4 h-4 inline"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E07\u0E34\u0E19\u0E08\u0E30\u0E16\u0E39\u0E01\u0E40\u0E15\u0E34\u0E21\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E2B\u0E25\u0E31\u0E07\u0E08\u0E32\u0E01 Admin \u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27 </p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-2" }, "\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32"),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400 mb-6" }, "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19 \u0E23\u0E2D Admin \u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34"),
                  createVNode("div", { class: "bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-xl p-4 mb-6" }, [
                    createVNode("h4", { class: "font-semibold text-gray-900 dark:text-white mb-3" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:bank",
                        class: "w-5 h-5 inline mr-2"
                      }),
                      createTextVNode(" \u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19 ")
                    ]),
                    createVNode("div", { class: "space-y-2 text-sm" }, [
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23:"),
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E2A\u0E34\u0E01\u0E23\u0E44\u0E17\u0E22")
                      ]),
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E0D\u0E0A\u0E35:"),
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, "xxx-x-xxxxx-x")
                      ]),
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35:"),
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E1A\u0E23\u0E34\u0E29\u0E31\u0E17 \u0E40\u0E1E\u0E25\u0E34\u0E19\u0E4C\u0E14\u0E35 \u0E08\u0E33\u0E01\u0E31\u0E14")
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                        createTextVNode(" \u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 "),
                        createVNode("span", { class: "text-red-500" }, "*")
                      ]),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => depositForm.value.amount = $event,
                          type: "number",
                          min: "10",
                          step: "10",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [
                            vModelText,
                            depositForm.value.amount,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("span", { class: "absolute right-4 top-1/2 -translate-y-1/2 text-gray-500" }, "\u0E1A\u0E32\u0E17")
                      ]),
                      createVNode("div", { class: "flex flex-wrap gap-2 mt-3" }, [
                        (openBlock(), createBlock(Fragment, null, renderList(quickDepositAmounts, (amount) => {
                          return createVNode("button", {
                            key: amount,
                            type: "button",
                            class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", depositForm.value.amount === amount ? "bg-primary-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"],
                            onClick: ($event) => depositForm.value.amount = amount
                          }, toDisplayString(amount) + " \u0E1A\u0E32\u0E17 ", 11, ["onClick"]);
                        }), 64))
                      ])
                    ]),
                    createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-4" }, [
                      createVNode("div", null, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                          createTextVNode(" \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 "),
                          createVNode("span", { class: "text-red-500" }, "*")
                        ]),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => depositForm.value.transfer_date = $event,
                          type: "date",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, depositForm.value.transfer_date]
                        ])
                      ]),
                      createVNode("div", null, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E40\u0E27\u0E25\u0E32\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19"),
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => depositForm.value.transfer_time = $event,
                          type: "time",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, depositForm.value.transfer_time]
                        ])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                        createTextVNode(" \u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E2A\u0E25\u0E34\u0E1B) "),
                        createVNode("span", { class: "text-red-500" }, "*")
                      ]),
                      !slipPreview.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mt-1"
                      }, [
                        createVNode("label", { class: "flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors" }, [
                          createVNode("div", { class: "flex flex-col items-center justify-center py-5" }, [
                            createVNode(unref(Icon), {
                              icon: "mdi:cloud-upload",
                              class: "w-10 h-10 text-gray-400 mb-3"
                            }),
                            createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"),
                            createVNode("p", { class: "text-xs text-gray-500 mt-1" }, "PNG, JPG (\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 5MB)")
                          ]),
                          createVNode("input", {
                            type: "file",
                            accept: "image/*",
                            class: "hidden",
                            onChange: handleSlipUpload
                          }, null, 32)
                        ])
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "relative mt-1"
                      }, [
                        createVNode("img", {
                          src: slipPreview.value,
                          alt: "Transfer slip preview",
                          class: "w-full max-h-64 object-contain rounded-xl border border-gray-200 dark:border-gray-600"
                        }, null, 8, ["src"]),
                        createVNode("button", {
                          type: "button",
                          class: "absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors",
                          onClick: clearSlip
                        }, [
                          createVNode(unref(Icon), {
                            icon: "mdi:close",
                            class: "w-4 h-4"
                          })
                        ])
                      ]))
                    ]),
                    createVNode("button", {
                      type: "button",
                      class: "w-full py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || depositForm.value.amount < 10 || !depositForm.value.transfer_slip || !depositForm.value.transfer_date,
                      onClick: handleDeposit
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 ${unref(formatMoney)(depositForm.value.amount)}`), 1)
                    ], 8, ["disabled"]),
                    createVNode("p", { class: "text-xs text-center text-gray-500 dark:text-gray-400" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:information",
                        class: "w-4 h-4 inline"
                      }),
                      createTextVNode(" \u0E40\u0E07\u0E34\u0E19\u0E08\u0E30\u0E16\u0E39\u0E01\u0E40\u0E15\u0E34\u0E21\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E2B\u0E25\u0E31\u0E07\u0E08\u0E32\u0E01 Admin \u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27 ")
                    ])
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
      if (activeTab.value === "deposit-requests") {
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</h3>`);
              if (depositRequestsLoading.value) {
                _push2(`<div class="py-8 text-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-8 h-8 text-primary-500 animate-spin mx-auto"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-500 mt-2"${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
              } else if (depositRequests.value.length > 0) {
                _push2(`<div class="space-y-4"${_scopeId}><!--[-->`);
                ssrRenderList(depositRequests.value, (req) => {
                  _push2(`<div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4"${_scopeId}><div class="flex items-start justify-between mb-3"${_scopeId}><div${_scopeId}><p class="font-semibold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(unref(formatMoney)(req.amount))}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(req.created_at)}</p></div><span class="${ssrRenderClass([getStatusBadgeClass(req.status), "px-3 py-1 text-xs font-medium rounded-full"])}"${_scopeId}>${ssrInterpolate(req.status_label)}</span></div><div class="grid grid-cols-2 gap-2 text-sm mb-3"${_scopeId}><div${_scopeId}><span class="text-gray-500 dark:text-gray-400"${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23:</span><span class="ml-1 text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(req.bank_name || "-")}</span></div><div${_scopeId}><span class="text-gray-500 dark:text-gray-400"${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19:</span><span class="ml-1 text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(req.transfer_date || "-")}</span></div></div>`);
                  if (req.transfer_slip) {
                    _push2(`<div class="mb-3"${_scopeId}><img${ssrRenderAttr("src", req.transfer_slip)} alt="Transfer slip" class="w-full max-h-32 object-contain rounded-lg cursor-pointer hover:opacity-80"${_scopeId}></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  if (req.status === "rejected" && req.rejection_reason) {
                    _push2(`<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 mb-3"${_scopeId}><p class="text-sm text-red-700 dark:text-red-400"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:alert-circle",
                      class: "w-4 h-4 inline mr-1"
                    }, null, _parent2, _scopeId));
                    _push2(` \u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E17\u0E35\u0E48\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18: ${ssrInterpolate(req.rejection_reason)}</p></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  if (req.status === "pending") {
                    _push2(`<div class="text-right"${_scopeId}><button type="button" class="px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"${ssrIncludeBooleanAttr(isProcessing.value) ? " disabled" : ""}${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:close-circle",
                      class: "w-4 h-4 inline mr-1"
                    }, null, _parent2, _scopeId));
                    _push2(` \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D </button></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="py-8 text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:inbox-outline",
                  class: "w-12 h-12 mx-auto mb-3"
                }, null, _parent2, _scopeId));
                _push2(`<p${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19</p><button type="button" class="mt-4 px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"${_scopeId}> \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E40\u0E25\u0E22 </button></div>`);
              }
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19"),
                  depositRequestsLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "py-8 text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:loading",
                      class: "w-8 h-8 text-primary-500 animate-spin mx-auto"
                    }),
                    createVNode("p", { class: "text-gray-500 mt-2" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...")
                  ])) : depositRequests.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-4"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(depositRequests.value, (req) => {
                      return openBlock(), createBlock("div", {
                        key: req.id,
                        class: "bg-gray-50 dark:bg-gray-700 rounded-xl p-4"
                      }, [
                        createVNode("div", { class: "flex items-start justify-between mb-3" }, [
                          createVNode("div", null, [
                            createVNode("p", { class: "font-semibold text-gray-900 dark:text-white" }, toDisplayString(unref(formatMoney)(req.amount)), 1),
                            createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(req.created_at), 1)
                          ]),
                          createVNode("span", {
                            class: ["px-3 py-1 text-xs font-medium rounded-full", getStatusBadgeClass(req.status)]
                          }, toDisplayString(req.status_label), 3)
                        ]),
                        createVNode("div", { class: "grid grid-cols-2 gap-2 text-sm mb-3" }, [
                          createVNode("div", null, [
                            createVNode("span", { class: "text-gray-500 dark:text-gray-400" }, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23:"),
                            createVNode("span", { class: "ml-1 text-gray-900 dark:text-white" }, toDisplayString(req.bank_name || "-"), 1)
                          ]),
                          createVNode("div", null, [
                            createVNode("span", { class: "text-gray-500 dark:text-gray-400" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19:"),
                            createVNode("span", { class: "ml-1 text-gray-900 dark:text-white" }, toDisplayString(req.transfer_date || "-"), 1)
                          ])
                        ]),
                        req.transfer_slip ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "mb-3"
                        }, [
                          createVNode("img", {
                            src: req.transfer_slip,
                            alt: "Transfer slip",
                            class: "w-full max-h-32 object-contain rounded-lg cursor-pointer hover:opacity-80",
                            onClick: ($event) => _ctx.window.open(req.transfer_slip, "_blank")
                          }, null, 8, ["src", "onClick"])
                        ])) : createCommentVNode("", true),
                        req.status === "rejected" && req.rejection_reason ? (openBlock(), createBlock("div", {
                          key: 1,
                          class: "bg-red-50 dark:bg-red-900/20 rounded-lg p-3 mb-3"
                        }, [
                          createVNode("p", { class: "text-sm text-red-700 dark:text-red-400" }, [
                            createVNode(unref(Icon), {
                              icon: "mdi:alert-circle",
                              class: "w-4 h-4 inline mr-1"
                            }),
                            createTextVNode(" \u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E17\u0E35\u0E48\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18: " + toDisplayString(req.rejection_reason), 1)
                          ])
                        ])) : createCommentVNode("", true),
                        req.status === "pending" ? (openBlock(), createBlock("div", {
                          key: 2,
                          class: "text-right"
                        }, [
                          createVNode("button", {
                            type: "button",
                            class: "px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors",
                            disabled: isProcessing.value,
                            onClick: ($event) => handleCancelRequest(req.id)
                          }, [
                            createVNode(unref(Icon), {
                              icon: "mdi:close-circle",
                              class: "w-4 h-4 inline mr-1"
                            }),
                            createTextVNode(" \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D ")
                          ], 8, ["disabled", "onClick"])
                        ])) : createCommentVNode("", true)
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
                    createVNode("p", null, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19"),
                    createVNode("button", {
                      type: "button",
                      class: "mt-4 px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors",
                      onClick: ($event) => activeTab.value = "deposit"
                    }, " \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E40\u0E25\u0E22 ", 8, ["onClick"])
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
      if (activeTab.value === "withdraw") {
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</h3><div class="space-y-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19</label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", withdrawForm.value.amount)} type="number" min="25"${ssrRenderAttr("max", walletBalance.value)} step="1" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 25 \u0E1A\u0E32\u0E17)"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E1A\u0E32\u0E17</span></div><p class="text-sm text-gray-500 mt-1"${_scopeId}>\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: ${ssrInterpolate(unref(formatMoney)(walletBalance.value))} | \u0E16\u0E2D\u0E19\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 25 \u0E1A\u0E32\u0E17</p><div class="flex flex-wrap gap-2 mt-3"${_scopeId}><!--[-->`);
              ssrRenderList(quickWithdrawAmounts, (amount) => {
                _push2(`<button class="${ssrRenderClass([withdrawForm.value.amount === amount ? "bg-primary-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${ssrIncludeBooleanAttr(amount > walletBalance.value) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(amount)} \u0E1A\u0E32\u0E17 </button>`);
              });
              _push2(`<!--]--></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23</label><select class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(withdrawForm.value.bank_account.bank_name) ? ssrLooseContain(withdrawForm.value.bank_account.bank_name, "") : ssrLooseEqual(withdrawForm.value.bank_account.bank_name, "")) ? " selected" : ""}${_scopeId}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23</option><!--[-->`);
              ssrRenderList(bankOptions, (bank) => {
                _push2(`<option${ssrRenderAttr("value", bank.value)}${ssrIncludeBooleanAttr(Array.isArray(withdrawForm.value.bank_account.bank_name) ? ssrLooseContain(withdrawForm.value.bank_account.bank_name, bank.value) : ssrLooseEqual(withdrawForm.value.bank_account.bank_name, bank.value)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(bank.icon)} ${ssrInterpolate(bank.label)}</option>`);
              });
              _push2(`<!--]--></select></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E0D\u0E0A\u0E35</label><input${ssrRenderAttr("value", withdrawForm.value.bank_account.account_number)} type="text" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E0D\u0E0A\u0E35"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35</label><input${ssrRenderAttr("value", withdrawForm.value.bank_account.account_name)} type="text" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35"${_scopeId}></div><div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4"${_scopeId}><div class="flex justify-between mb-2"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E16\u0E2D\u0E19</span><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(unref(formatMoney)(withdrawPreview.value.amount))}</span></div><div class="flex justify-between mb-2"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21 (13%)</span><span class="font-medium text-red-500"${_scopeId}>-${ssrInterpolate(unref(formatMoney)(withdrawPreview.value.fee))}</span></div><div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600"${_scopeId}><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E22\u0E2D\u0E14\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A</span><span class="font-bold text-green-500"${_scopeId}>${ssrInterpolate(unref(formatMoney)(withdrawPreview.value.net))}</span></div></div><button class="w-full py-3 bg-gradient-to-r from-red-500 to-rose-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || withdrawForm.value.amount < 100 || withdrawForm.value.amount > walletBalance.value || !withdrawForm.value.bank_account.bank_name) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19")}</button></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19"),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => withdrawForm.value.amount = $event,
                          type: "number",
                          min: "25",
                          max: walletBalance.value,
                          step: "1",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 25 \u0E1A\u0E32\u0E17)"
                        }, null, 8, ["onUpdate:modelValue", "max"]), [
                          [
                            vModelText,
                            withdrawForm.value.amount,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("span", { class: "absolute right-4 top-1/2 -translate-y-1/2 text-gray-500" }, "\u0E1A\u0E32\u0E17")
                      ]),
                      createVNode("p", { class: "text-sm text-gray-500 mt-1" }, "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: " + toDisplayString(unref(formatMoney)(walletBalance.value)) + " | \u0E16\u0E2D\u0E19\u0E02\u0E31\u0E49\u0E19\u0E15\u0E48\u0E33 25 \u0E1A\u0E32\u0E17", 1),
                      createVNode("div", { class: "flex flex-wrap gap-2 mt-3" }, [
                        (openBlock(), createBlock(Fragment, null, renderList(quickWithdrawAmounts, (amount) => {
                          return createVNode("button", {
                            key: amount,
                            class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", withdrawForm.value.amount === amount ? "bg-primary-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"],
                            disabled: amount > walletBalance.value,
                            onClick: ($event) => withdrawForm.value.amount = amount
                          }, toDisplayString(amount) + " \u0E1A\u0E32\u0E17 ", 11, ["disabled", "onClick"]);
                        }), 64))
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23"),
                      withDirectives(createVNode("select", {
                        "onUpdate:modelValue": ($event) => withdrawForm.value.bank_account.bank_name = $event,
                        class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      }, [
                        createVNode("option", { value: "" }, "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23"),
                        (openBlock(), createBlock(Fragment, null, renderList(bankOptions, (bank) => {
                          return createVNode("option", {
                            key: bank.value,
                            value: bank.value
                          }, toDisplayString(bank.icon) + " " + toDisplayString(bank.label), 9, ["value"]);
                        }), 64))
                      ], 8, ["onUpdate:modelValue"]), [
                        [vModelSelect, withdrawForm.value.bank_account.bank_name]
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E0D\u0E0A\u0E35"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => withdrawForm.value.bank_account.account_number = $event,
                        type: "text",
                        class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                        placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E0D\u0E0A\u0E35"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, withdrawForm.value.bank_account.account_number]
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35"),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => withdrawForm.value.bank_account.account_name = $event,
                        type: "text",
                        class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                        placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, withdrawForm.value.bank_account.account_name]
                      ])
                    ]),
                    createVNode("div", { class: "bg-gray-100 dark:bg-gray-700 rounded-xl p-4" }, [
                      createVNode("div", { class: "flex justify-between mb-2" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E16\u0E2D\u0E19"),
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(unref(formatMoney)(withdrawPreview.value.amount)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between mb-2" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21 (13%)"),
                        createVNode("span", { class: "font-medium text-red-500" }, "-" + toDisplayString(unref(formatMoney)(withdrawPreview.value.fee)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600" }, [
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E22\u0E2D\u0E14\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A"),
                        createVNode("span", { class: "font-bold text-green-500" }, toDisplayString(unref(formatMoney)(withdrawPreview.value.net)), 1)
                      ])
                    ]),
                    createVNode("button", {
                      class: "w-full py-3 bg-gradient-to-r from-red-500 to-rose-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || withdrawForm.value.amount < 100 || withdrawForm.value.amount > walletBalance.value || !withdrawForm.value.bank_account.bank_name,
                      onClick: handleWithdraw
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E16\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19"), 1)
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
      if (activeTab.value === "transfer") {
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19</h3><div class="space-y-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A <span class="text-red-500"${_scopeId}>*</span></label>`);
              if (selectedRecipient.value) {
                _push2(`<div class="mb-3"${_scopeId}><div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl"${_scopeId}><img${ssrRenderAttr("src", selectedRecipient.value.profile_photo_url || selectedRecipient.value.avatar || "/default-avatar.png")}${ssrRenderAttr("alt", selectedRecipient.value.name)} class="w-12 h-12 rounded-full object-cover"${_scopeId}><div class="flex-grow"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(selectedRecipient.value.name || selectedRecipient.value.username)}</p><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(selectedRecipient.value.email)}</p>`);
                if (selectedRecipient.value.personal_code) {
                  _push2(`<p class="text-xs text-primary-500"${_scopeId}>${ssrInterpolate(selectedRecipient.value.personal_code)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><button type="button" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:close",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(`</button></div></div>`);
              } else {
                _push2(`<div class="relative"${_scopeId}><div class="relative"${_scopeId}><input${ssrRenderAttr("value", userSearchQuery.value)} type="text" class="w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07..."${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: userSearchLoading.value ? "mdi:loading" : "mdi:magnify",
                  class: ["absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400", { "animate-spin": userSearchLoading.value }]
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
                if (showUserDropdown.value && userSearchResults.value.length > 0) {
                  _push2(`<div class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-64 overflow-y-auto"${_scopeId}><!--[-->`);
                  ssrRenderList(userSearchResults.value, (user) => {
                    _push2(`<div class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"${_scopeId}><img${ssrRenderAttr("src", user.profile_photo_url || user.avatar || "/default-avatar.png")}${ssrRenderAttr("alt", user.name)} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div class="flex-grow"${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(user.name || user.username)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(user.email)}</p>`);
                    if (user.personal_code) {
                      _push2(`<p class="text-xs text-primary-500"${_scopeId}>${ssrInterpolate(user.personal_code)}</p>`);
                    } else {
                      _push2(`<!---->`);
                    }
                    _push2(`</div>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:chevron-right",
                      class: "w-5 h-5 text-gray-400"
                    }, null, _parent2, _scopeId));
                    _push2(`</div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (showUserDropdown.value && userSearchQuery.value.length >= 2 && userSearchResults.value.length === 0 && !userSearchLoading.value) {
                  _push2(`<div class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:account-search",
                    class: "w-8 h-8 text-gray-400 mx-auto mb-2"
                  }, null, _parent2, _scopeId));
                  _push2(`<p class="text-gray-500 dark:text-gray-400"${_scopeId}>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</p></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              }
              _push2(`<p class="text-xs text-gray-500 mt-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information",
                class: "w-3.5 h-3.5 inline"
              }, null, _parent2, _scopeId));
              _push2(` \u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 2 \u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32 </p></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19</label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", transferForm.value.amount)} type="number" min="1"${ssrRenderAttr("max", walletBalance.value)} step="1" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E1A\u0E32\u0E17</span></div><p class="text-sm text-gray-500 mt-1"${_scopeId}>\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: ${ssrInterpolate(unref(formatMoney)(walletBalance.value))}</p></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</label><textarea rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E16\u0E36\u0E07\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A..."${_scopeId}>${ssrInterpolate(transferForm.value.message)}</textarea></div><button class="w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || !transferForm.value.recipient_id || transferForm.value.amount < 1 || transferForm.value.amount > walletBalance.value) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 ${unref(formatMoney)(transferForm.value.amount)}`)}</button></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E2D\u0E37\u0E48\u0E19"),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                        createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A "),
                        createVNode("span", { class: "text-red-500" }, "*")
                      ]),
                      selectedRecipient.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "mb-3"
                      }, [
                        createVNode("div", { class: "flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl" }, [
                          createVNode("img", {
                            src: selectedRecipient.value.profile_photo_url || selectedRecipient.value.avatar || "/default-avatar.png",
                            alt: selectedRecipient.value.name,
                            class: "w-12 h-12 rounded-full object-cover"
                          }, null, 8, ["src", "alt"]),
                          createVNode("div", { class: "flex-grow" }, [
                            createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(selectedRecipient.value.name || selectedRecipient.value.username), 1),
                            createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, toDisplayString(selectedRecipient.value.email), 1),
                            selectedRecipient.value.personal_code ? (openBlock(), createBlock("p", {
                              key: 0,
                              class: "text-xs text-primary-500"
                            }, toDisplayString(selectedRecipient.value.personal_code), 1)) : createCommentVNode("", true)
                          ]),
                          createVNode("button", {
                            type: "button",
                            class: "p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg",
                            onClick: clearRecipient
                          }, [
                            createVNode(unref(Icon), {
                              icon: "mdi:close",
                              class: "w-5 h-5"
                            })
                          ])
                        ])
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "relative"
                      }, [
                        createVNode("div", { class: "relative" }, [
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => userSearchQuery.value = $event,
                            type: "text",
                            class: "w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                            placeholder: "\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07...",
                            onInput: debouncedSearch,
                            onFocus: ($event) => showUserDropdown.value = userSearchResults.value.length > 0
                          }, null, 40, ["onUpdate:modelValue", "onFocus"]), [
                            [vModelText, userSearchQuery.value]
                          ]),
                          createVNode(unref(Icon), {
                            icon: userSearchLoading.value ? "mdi:loading" : "mdi:magnify",
                            class: ["absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400", { "animate-spin": userSearchLoading.value }]
                          }, null, 8, ["icon", "class"])
                        ]),
                        showUserDropdown.value && userSearchResults.value.length > 0 ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-64 overflow-y-auto"
                        }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(userSearchResults.value, (user) => {
                            return openBlock(), createBlock("div", {
                              key: user.id,
                              class: "flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors",
                              onClick: ($event) => selectRecipient(user)
                            }, [
                              createVNode("img", {
                                src: user.profile_photo_url || user.avatar || "/default-avatar.png",
                                alt: user.name,
                                class: "w-10 h-10 rounded-full object-cover"
                              }, null, 8, ["src", "alt"]),
                              createVNode("div", { class: "flex-grow" }, [
                                createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(user.name || user.username), 1),
                                createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(user.email), 1),
                                user.personal_code ? (openBlock(), createBlock("p", {
                                  key: 0,
                                  class: "text-xs text-primary-500"
                                }, toDisplayString(user.personal_code), 1)) : createCommentVNode("", true)
                              ]),
                              createVNode(unref(Icon), {
                                icon: "mdi:chevron-right",
                                class: "w-5 h-5 text-gray-400"
                              })
                            ], 8, ["onClick"]);
                          }), 128))
                        ])) : createCommentVNode("", true),
                        showUserDropdown.value && userSearchQuery.value.length >= 2 && userSearchResults.value.length === 0 && !userSearchLoading.value ? (openBlock(), createBlock("div", {
                          key: 1,
                          class: "absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center"
                        }, [
                          createVNode(unref(Icon), {
                            icon: "mdi:account-search",
                            class: "w-8 h-8 text-gray-400 mx-auto mb-2"
                          }),
                          createVNode("p", { class: "text-gray-500 dark:text-gray-400" }, "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")
                        ])) : createCommentVNode("", true)
                      ])),
                      createVNode("p", { class: "text-xs text-gray-500 mt-2" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:information",
                          class: "w-3.5 h-3.5 inline"
                        }),
                        createTextVNode(" \u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 2 \u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32 ")
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => transferForm.value.amount = $event,
                          type: "number",
                          min: "1",
                          max: walletBalance.value,
                          step: "1",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"
                        }, null, 8, ["onUpdate:modelValue", "max"]), [
                          [
                            vModelText,
                            transferForm.value.amount,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("span", { class: "absolute right-4 top-1/2 -translate-y-1/2 text-gray-500" }, "\u0E1A\u0E32\u0E17")
                      ]),
                      createVNode("p", { class: "text-sm text-gray-500 mt-1" }, "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: " + toDisplayString(unref(formatMoney)(walletBalance.value)), 1)
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)"),
                      withDirectives(createVNode("textarea", {
                        "onUpdate:modelValue": ($event) => transferForm.value.message = $event,
                        rows: "3",
                        class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                        placeholder: "\u0E40\u0E02\u0E35\u0E22\u0E19\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E16\u0E36\u0E07\u0E1C\u0E39\u0E49\u0E23\u0E31\u0E1A..."
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, transferForm.value.message]
                      ])
                    ]),
                    createVNode("button", {
                      class: "w-full py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || !transferForm.value.recipient_id || transferForm.value.amount < 1 || transferForm.value.amount > walletBalance.value,
                      onClick: handleTransfer
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : `\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 ${unref(formatMoney)(transferForm.value.amount)}`), 1)
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
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</h3><div class="space-y-6"${_scopeId}><div class="bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl p-4"${_scopeId}><div class="flex items-center gap-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information",
                class: "w-6 h-6 text-amber-600 dark:text-amber-400"
              }, null, _parent2, _scopeId));
              _push2(`<div${_scopeId}><p class="font-medium text-amber-800 dark:text-amber-300"${_scopeId}>\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19</p><p class="text-sm text-amber-700 dark:text-amber-400"${_scopeId}>1,200 \u0E41\u0E15\u0E49\u0E21 = 1 \u0E1A\u0E32\u0E17</p></div></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21</label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", convertForm.value.points)} type="number" min="1200"${ssrRenderAttr("max", unref(points))} step="1200" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</span></div><p class="text-sm text-gray-500 mt-1"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E21\u0E35: ${ssrInterpolate(unref(formatPoints)(unref(points)))} \u0E41\u0E15\u0E49\u0E21</p></div><div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4"${_scopeId}><div class="flex justify-between mb-2"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49</span><span class="font-medium text-amber-500"${_scopeId}>${ssrInterpolate(unref(formatPoints)(conversionPreview.value.points))} \u0E41\u0E15\u0E49\u0E21</span></div><div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600"${_scopeId}><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E40\u0E07\u0E34\u0E19</span><span class="font-bold text-green-500"${_scopeId}>${ssrInterpolate(unref(formatMoney)(conversionPreview.value.money))}</span></div></div><button class="w-full py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || !conversionPreview.value.isValid) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19")}</button></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19"),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", { class: "bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl p-4" }, [
                      createVNode("div", { class: "flex items-center gap-3" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:information",
                          class: "w-6 h-6 text-amber-600 dark:text-amber-400"
                        }),
                        createVNode("div", null, [
                          createVNode("p", { class: "font-medium text-amber-800 dark:text-amber-300" }, "\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19"),
                          createVNode("p", { class: "text-sm text-amber-700 dark:text-amber-400" }, "1,200 \u0E41\u0E15\u0E49\u0E21 = 1 \u0E1A\u0E32\u0E17")
                        ])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21"),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => convertForm.value.points = $event,
                          type: "number",
                          min: "1200",
                          max: unref(points),
                          step: "1200",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
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
                      createVNode("p", { class: "text-sm text-gray-500 mt-1" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E21\u0E35: " + toDisplayString(unref(formatPoints)(unref(points))) + " \u0E41\u0E15\u0E49\u0E21", 1)
                    ]),
                    createVNode("div", { class: "bg-gray-100 dark:bg-gray-700 rounded-xl p-4" }, [
                      createVNode("div", { class: "flex justify-between mb-2" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49"),
                        createVNode("span", { class: "font-medium text-amber-500" }, toDisplayString(unref(formatPoints)(conversionPreview.value.points)) + " \u0E41\u0E15\u0E49\u0E21", 1)
                      ]),
                      createVNode("div", { class: "flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600" }, [
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E40\u0E07\u0E34\u0E19"),
                        createVNode("span", { class: "font-bold text-green-500" }, toDisplayString(unref(formatMoney)(conversionPreview.value.money)), 1)
                      ])
                    ]),
                    createVNode("button", {
                      class: "w-full py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || !conversionPreview.value.isValid,
                      onClick: handleConvert
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E41\u0E1B\u0E25\u0E07\u0E41\u0E15\u0E49\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19"), 1)
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
      if (activeTab.value === "convert-to-points") {
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E07\u0E34\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E15\u0E49\u0E21</h3><div class="space-y-6"${_scopeId}><div class="bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl p-4"${_scopeId}><div class="flex items-center gap-3"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:information",
                class: "w-6 h-6 text-emerald-600 dark:text-emerald-400"
              }, null, _parent2, _scopeId));
              _push2(`<div${_scopeId}><p class="font-medium text-emerald-800 dark:text-emerald-300"${_scopeId}>\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19</p><p class="text-sm text-emerald-700 dark:text-emerald-400"${_scopeId}>1 \u0E1A\u0E32\u0E17 = 1,200 \u0E41\u0E15\u0E49\u0E21</p><p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1"${_scopeId}>\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E42\u0E06\u0E29\u0E13\u0E32</p></div></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19</label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", convertToPointsForm.value.amount)} type="number" min="10"${ssrRenderAttr("max", walletBalance.value)} step="10" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"${_scopeId}><span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"${_scopeId}>\u0E1A\u0E32\u0E17</span></div><p class="text-sm text-gray-500 mt-1"${_scopeId}>\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: ${ssrInterpolate(unref(formatMoney)(walletBalance.value))}</p><div class="flex flex-wrap gap-2 mt-3"${_scopeId}><!--[-->`);
              ssrRenderList([10, 50, 100, 500, 1e3], (amount) => {
                _push2(`<button class="${ssrRenderClass([convertToPointsForm.value.amount === amount ? "bg-primary-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600", "px-4 py-2 rounded-lg text-sm font-medium transition-colors"])}"${ssrIncludeBooleanAttr(amount > walletBalance.value) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(amount)} \u0E1A\u0E32\u0E17 </button>`);
              });
              _push2(`<!--]--></div></div><div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4"${_scopeId}><div class="flex justify-between mb-2"${_scopeId}><span class="text-gray-600 dark:text-gray-400"${_scopeId}>\u0E40\u0E07\u0E34\u0E19\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49</span><span class="font-medium text-emerald-500"${_scopeId}>${ssrInterpolate(unref(formatMoney)(convertToPointsPreview.value.amount))}</span></div><div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600"${_scopeId}><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21</span><span class="font-bold text-purple-500"${_scopeId}>${ssrInterpolate(unref(formatPoints)(convertToPointsPreview.value.points))} \u0E41\u0E15\u0E49\u0E21</span></div></div><button class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"${ssrIncludeBooleanAttr(isProcessing.value || !convertToPointsPreview.value.isValid) ? " disabled" : ""}${_scopeId}>`);
              if (isProcessing.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-5 h-5 animate-spin inline mr-2"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(` ${ssrInterpolate(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E07\u0E34\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E15\u0E49\u0E21")}</button></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E07\u0E34\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E15\u0E49\u0E21"),
                  createVNode("div", { class: "space-y-6" }, [
                    createVNode("div", { class: "bg-gradient-to-r from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-xl p-4" }, [
                      createVNode("div", { class: "flex items-center gap-3" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:information",
                          class: "w-6 h-6 text-emerald-600 dark:text-emerald-400"
                        }),
                        createVNode("div", null, [
                          createVNode("p", { class: "font-medium text-emerald-800 dark:text-emerald-300" }, "\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E41\u0E25\u0E01\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19"),
                          createVNode("p", { class: "text-sm text-emerald-700 dark:text-emerald-400" }, "1 \u0E1A\u0E32\u0E17 = 1,200 \u0E41\u0E15\u0E49\u0E21"),
                          createVNode("p", { class: "text-xs text-emerald-600 dark:text-emerald-500 mt-1" }, "\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E42\u0E06\u0E29\u0E13\u0E32")
                        ])
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"),
                      createVNode("div", { class: "relative" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => convertToPointsForm.value.amount = $event,
                          type: "number",
                          min: "10",
                          max: walletBalance.value,
                          step: "10",
                          class: "w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500",
                          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"
                        }, null, 8, ["onUpdate:modelValue", "max"]), [
                          [
                            vModelText,
                            convertToPointsForm.value.amount,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        createVNode("span", { class: "absolute right-4 top-1/2 -translate-y-1/2 text-gray-500" }, "\u0E1A\u0E32\u0E17")
                      ]),
                      createVNode("p", { class: "text-sm text-gray-500 mt-1" }, "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D: " + toDisplayString(unref(formatMoney)(walletBalance.value)), 1),
                      createVNode("div", { class: "flex flex-wrap gap-2 mt-3" }, [
                        (openBlock(), createBlock(Fragment, null, renderList([10, 50, 100, 500, 1e3], (amount) => {
                          return createVNode("button", {
                            key: amount,
                            class: ["px-4 py-2 rounded-lg text-sm font-medium transition-colors", convertToPointsForm.value.amount === amount ? "bg-primary-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"],
                            disabled: amount > walletBalance.value,
                            onClick: ($event) => convertToPointsForm.value.amount = amount
                          }, toDisplayString(amount) + " \u0E1A\u0E32\u0E17 ", 11, ["disabled", "onClick"]);
                        }), 64))
                      ])
                    ]),
                    createVNode("div", { class: "bg-gray-100 dark:bg-gray-700 rounded-xl p-4" }, [
                      createVNode("div", { class: "flex justify-between mb-2" }, [
                        createVNode("span", { class: "text-gray-600 dark:text-gray-400" }, "\u0E40\u0E07\u0E34\u0E19\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49"),
                        createVNode("span", { class: "font-medium text-emerald-500" }, toDisplayString(unref(formatMoney)(convertToPointsPreview.value.amount)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600" }, [
                        createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21"),
                        createVNode("span", { class: "font-bold text-purple-500" }, toDisplayString(unref(formatPoints)(convertToPointsPreview.value.points)) + " \u0E41\u0E15\u0E49\u0E21", 1)
                      ])
                    ]),
                    createVNode("button", {
                      class: "w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed",
                      disabled: isProcessing.value || !convertToPointsPreview.value.isValid,
                      onClick: handleConvertToPoints
                    }, [
                      isProcessing.value ? (openBlock(), createBlock(unref(Icon), {
                        key: 0,
                        icon: "mdi:loading",
                        class: "w-5 h-5 animate-spin inline mr-2"
                      })) : createCommentVNode("", true),
                      createTextVNode(" " + toDisplayString(isProcessing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E41\u0E1B\u0E25\u0E07\u0E40\u0E07\u0E34\u0E19\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E15\u0E49\u0E21"), 1)
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
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6"${_scopeId}>\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</h3>`);
              if (transactionsLoading.value) {
                _push2(`<div class="py-12 text-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:loading",
                  class: "w-12 h-12 text-primary-500 animate-spin mx-auto"
                }, null, _parent2, _scopeId));
                _push2(`<p class="mt-4 text-gray-500 dark:text-gray-400"${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
              } else if (transactions.value.length > 0) {
                _push2(`<div class="space-y-2 sm:space-y-3"${_scopeId}><!--[-->`);
                ssrRenderList(transactions.value, (tx) => {
                  _push2(ssrRenderComponent(_sfc_main$1, mergeProps({
                    key: tx.id
                  }, { ref_for: true }, transformTransaction(tx)), null, _parent2, _scopeId));
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="py-12 text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:inbox-outline",
                  class: "w-16 h-16 mx-auto mb-3"
                }, null, _parent2, _scopeId));
                _push2(`<p${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</p></div>`);
              }
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6" }, "\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23"),
                  transactionsLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "py-12 text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:loading",
                      class: "w-12 h-12 text-primary-500 animate-spin mx-auto"
                    }),
                    createVNode("p", { class: "mt-4 text-gray-500 dark:text-gray-400" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...")
                  ])) : transactions.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "space-y-2 sm:space-y-3"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(transactions.value, (tx) => {
                      return openBlock(), createBlock(_sfc_main$1, mergeProps({
                        key: tx.id
                      }, { ref_for: true }, transformTransaction(tx)), null, 16);
                    }), 128))
                  ])) : (openBlock(), createBlock("div", {
                    key: 2,
                    class: "py-12 text-center text-gray-500 dark:text-gray-400"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:inbox-outline",
                      class: "w-16 h-16 mx-auto mb-3"
                    }),
                    createVNode("p", null, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E17\u0E33\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23")
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
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Wallet.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Wallet-DNEGa8Rq.mjs.map
