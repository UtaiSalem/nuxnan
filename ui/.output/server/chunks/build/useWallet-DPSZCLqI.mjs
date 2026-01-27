import { computed, ref } from 'vue';
import { d as useAuthStore, b as useRuntimeConfig } from './server.mjs';

const useWallet = () => {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();
  const apiBase = computed(() => config.public.apiBase || "");
  const isLoading = ref(false);
  const error = ref(null);
  const wallet = computed(() => {
    var _a;
    return ((_a = authStore.user) == null ? void 0 : _a.wallet) || 0;
  });
  const user = computed(() => authStore.user);
  const getBalance = async () => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/wallet/balance`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get wallet balance");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "Failed to get wallet balance";
      error.value = msg;
      console.error("Get wallet balance error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const deposit = async (data) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/wallet/deposit`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: data
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to deposit money");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "Failed to deposit money";
      error.value = msg;
      console.error("Deposit error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const withdraw = async (data) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/wallet/withdraw`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: data
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to withdraw money");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "Failed to withdraw money";
      error.value = msg;
      console.error("Withdraw error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const transfer = async (data) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/wallet/transfer`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: data
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to transfer money");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "Failed to transfer money";
      error.value = msg;
      console.error("Transfer error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const getTransactions = async (params = {}) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.type) queryParams.append("type", params.type);
      if (params.date_from) queryParams.append("date_from", params.date_from);
      if (params.date_to) queryParams.append("date_to", params.date_to);
      if (params.page) queryParams.append("page", params.page.toString());
      if (params.per_page) queryParams.append("per_page", params.per_page.toString());
      const response = await $fetch(`${apiBase.value}/api/wallet/transactions?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get transactions");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "Failed to get transactions";
      error.value = msg;
      console.error("Get transactions error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const formatMoney = (value) => {
    return new Intl.NumberFormat("th-TH", {
      style: "currency",
      currency: "THB"
    }).format(value);
  };
  const canWithdraw = (amount) => {
    return amount >= 25 && wallet.value >= amount;
  };
  const calculateFee = (amount) => {
    return amount * 0.13;
  };
  const createDepositRequest = async (formData) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/wallet/deposit-request`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: formData
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E44\u0E14\u0E49");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E44\u0E14\u0E49";
      error.value = msg;
      console.error("Create deposit request error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const getDepositRequests = async (params = {}) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.status) queryParams.append("status", params.status);
      if (params.page) queryParams.append("page", params.page.toString());
      if (params.per_page) queryParams.append("per_page", params.per_page.toString());
      const response = await $fetch(`${apiBase.value}/api/wallet/deposit-requests?${queryParams}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E36\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E44\u0E14\u0E49");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E36\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E44\u0E14\u0E49";
      error.value = msg;
      console.error("Get deposit requests error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const cancelDepositRequest = async (requestId) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/wallet/deposit-requests/${requestId}`, {
        method: "DELETE",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D\u0E44\u0E14\u0E49");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D\u0E44\u0E14\u0E49";
      error.value = msg;
      console.error("Cancel deposit request error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  const getNetAmount = (amount) => {
    const fee = calculateFee(amount);
    return amount - fee;
  };
  const convertToPoints = async (amount) => {
    var _a, _b, _c;
    try {
      isLoading.value = true;
      error.value = null;
      if (wallet.value < amount) {
        throw new Error(`\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23 ${formatMoney(amount)}, \u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48 ${formatMoney(wallet.value)})`);
      }
      const response = await $fetch(`${apiBase.value}/api/wallet/convert-to-points`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: {
          amount
        }
      });
      if (response.success) {
        if (response.data) {
          authStore.setWallet(response.data.new_wallet_balance || 0);
        }
        return response.data;
      } else {
        throw new Error(response.message || "Failed to convert wallet to points");
      }
    } catch (err) {
      const msg = ((_a = err.data) == null ? void 0 : _a.message) || ((_c = (_b = err.response) == null ? void 0 : _b._data) == null ? void 0 : _c.message) || err.message || "Failed to convert wallet to points";
      error.value = msg;
      console.error("Convert wallet to points error:", err);
      throw new Error(msg);
    } finally {
      isLoading.value = false;
    }
  };
  return {
    // State
    wallet,
    user,
    isLoading,
    error,
    // Methods
    getBalance,
    deposit,
    withdraw,
    transfer,
    convertToPoints,
    getTransactions,
    // Deposit Request Methods
    createDepositRequest,
    getDepositRequests,
    cancelDepositRequest,
    // Helpers
    formatMoney,
    canWithdraw,
    calculateFee,
    getNetAmount
  };
};

export { useWallet as u };
//# sourceMappingURL=useWallet-DPSZCLqI.mjs.map
