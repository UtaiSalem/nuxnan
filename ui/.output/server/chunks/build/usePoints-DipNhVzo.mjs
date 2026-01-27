import { computed, ref } from 'vue';
import { d as useAuthStore, b as useRuntimeConfig } from './server.mjs';

const usePoints = () => {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();
  const apiBase = computed(() => config.public.apiBase || "");
  const isLoading = ref(false);
  const error = ref(null);
  const points = computed(() => authStore.points);
  const user = computed(() => authStore.user);
  const getBalance = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/points/balance`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        if (response.data) {
          authStore.setPoints(response.data.current_points || 0);
        }
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get points balance");
      }
    } catch (err) {
      error.value = err.message || "Failed to get points balance";
      console.error("Get points balance error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const earn = async (data) => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase.value}/api/points/earn`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: data
      });
      if (response.success) {
        if (response.data) {
          authStore.addPoints(response.data.points_earned || 0);
        }
        if (response.data.achievements_unlocked && response.data.achievements_unlocked.length > 0) {
          response.data.achievements_unlocked.forEach((achievement) => {
            showAchievementNotification(achievement);
          });
        }
        return response.data;
      } else {
        throw new Error(response.message || "Failed to earn points");
      }
    } catch (err) {
      error.value = err.message || "Failed to earn points";
      console.error("Earn points error:", err);
      if (data.amount) {
        authStore.rollback(data.amount);
      }
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const spend = async (data) => {
    try {
      isLoading.value = true;
      error.value = null;
      if (points.value < data.amount) {
        throw new Error(`\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23 ${data.amount} \u0E41\u0E15\u0E49\u0E21, \u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48 ${points.value} \u0E41\u0E15\u0E49\u0E21)`);
      }
      const hasEnough = authStore.deductPoints(data.amount);
      if (!hasEnough) {
        throw new Error("\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D");
      }
      const response = await $fetch(`${apiBase.value}/api/points/spend`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: data
      });
      if (response.success) {
        return response.data;
      } else {
        authStore.rollback(data.amount);
        throw new Error(response.message || "Failed to spend points");
      }
    } catch (err) {
      error.value = err.message || "Failed to spend points";
      console.error("Spend points error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const convertToWallet = async (pointsAmount) => {
    try {
      isLoading.value = true;
      error.value = null;
      if (points.value < pointsAmount) {
        throw new Error(`\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23 ${pointsAmount} \u0E41\u0E15\u0E49\u0E21, \u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48 ${points.value} \u0E41\u0E15\u0E49\u0E21)`);
      }
      const exchangeRate = 1200;
      const walletAmount = pointsAmount / exchangeRate;
      const hasEnough = authStore.deductPoints(pointsAmount);
      if (!hasEnough) {
        throw new Error("\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D");
      }
      const response = await $fetch(`${apiBase.value}/api/points/convert`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: {
          points: pointsAmount,
          target: "wallet"
        }
      });
      if (response.success) {
        if (response.data) {
          authStore.setPoints(response.data.new_points_balance || 0);
        }
        return response.data;
      } else {
        authStore.rollback(pointsAmount);
        throw new Error(response.message || "Failed to convert points");
      }
    } catch (err) {
      error.value = err.message || "Failed to convert points";
      console.error("Convert points error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const transfer = async (data) => {
    var _a;
    try {
      isLoading.value = true;
      error.value = null;
      if (data.amount <= 0) {
        throw new Error("\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21\u0E15\u0E49\u0E2D\u0E07\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 0");
      }
      if (points.value < data.amount) {
        throw new Error(`\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23 ${data.amount} \u0E41\u0E15\u0E49\u0E21, \u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48 ${points.value} \u0E41\u0E15\u0E49\u0E21)`);
      }
      if (data.recipient_id === ((_a = authStore.user) == null ? void 0 : _a.id)) {
        throw new Error("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E07\u0E44\u0E14\u0E49");
      }
      const hasEnough = authStore.deductPoints(data.amount);
      if (!hasEnough) {
        throw new Error("\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D");
      }
      const response = await $fetch(`${apiBase.value}/api/points/transfer`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: {
          recipient_id: data.recipient_id,
          amount: data.amount,
          message: data.message || ""
        }
      });
      if (response.success) {
        if (response.data) {
          authStore.setPoints(response.data.new_balance || points.value - data.amount);
        }
        return response.data;
      } else {
        authStore.rollback(data.amount);
        throw new Error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E44\u0E14\u0E49");
      }
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2D\u0E19\u0E41\u0E15\u0E49\u0E21\u0E44\u0E14\u0E49";
      console.error("Transfer points error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getTransactions = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.type) queryParams.append("type", params.type);
      if (params.source_type) queryParams.append("source_type", params.source_type);
      if (params.date_from) queryParams.append("date_from", params.date_from);
      if (params.date_to) queryParams.append("date_to", params.date_to);
      if (params.page) queryParams.append("page", params.page.toString());
      if (params.per_page) queryParams.append("per_page", params.per_page.toString());
      const response = await $fetch(`${apiBase.value}/api/points/transactions?${queryParams.toString()}`, {
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
      error.value = err.message || "Failed to get transactions";
      console.error("Get transactions error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const formatPoints = (value) => {
    return new Intl.NumberFormat("th-TH").format(value);
  };
  const showAchievementNotification = (achievement) => {
    const SwalLib = (void 0).Swal;
    if (typeof SwalLib !== "undefined") {
      SwalLib.fire({
        icon: "success",
        title: "\u{1F389} \u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
        html: `
          <div style="text-align: center;">
            <img src="${achievement.icon || "/icons/achievement.png"}" style="width: 64px; height: 64px; margin-bottom: 10px;">
            <h3 style="margin: 0 0 10px 0;">${achievement.name}</h3>
            <p style="margin: 0 0 10px 0; color: #666;">${achievement.description || ""}</p>
            ${achievement.points_reward ? `<p style="margin: 0; font-weight: bold; color: #10b981;">+${achievement.points_reward} \u0E41\u0E15\u0E49\u0E21</p>` : ""}
          </div>
        `,
        confirmButtonText: "\u0E23\u0E31\u0E1A\u0E17\u0E23\u0E32\u0E1A",
        confirmButtonColor: "#10b981"
      });
    }
  };
  const canSpend = (amount) => {
    return points.value >= amount;
  };
  const getPointsForNextLevel = () => {
    if (!user.value) return 0;
    return (user.value.xp_for_next_level || 0) - (user.value.current_xp || 0);
  };
  const getLevelProgress = () => {
    if (!user.value) return 0;
    const xpForNextLevel = user.value.xp_for_next_level || 100;
    const currentXp = user.value.current_xp || 0;
    return Math.round(currentXp / xpForNextLevel * 100);
  };
  return {
    // State
    points,
    user,
    isLoading,
    error,
    // Methods
    getBalance,
    earn,
    spend,
    transfer,
    convertToWallet,
    getTransactions,
    // Helpers
    formatPoints,
    canSpend,
    getPointsForNextLevel,
    getLevelProgress
  };
};

export { usePoints as u };
//# sourceMappingURL=usePoints-DipNhVzo.mjs.map
