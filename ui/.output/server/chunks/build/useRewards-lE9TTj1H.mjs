import { ref, computed } from 'vue';
import { d as useAuthStore, b as useRuntimeConfig } from './server.mjs';

const useRewards = () => {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase;
  const isLoading = ref(false);
  const error = ref(null);
  const rewards = ref([]);
  const userRewards = ref([]);
  const points = computed(() => authStore.points);
  const user = computed(() => authStore.user);
  const getRewards = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.type) queryParams.append("type", params.type);
      if (params.is_active !== void 0) queryParams.append("is_active", params.is_active.toString());
      if (params.page) queryParams.append("page", params.page.toString());
      if (params.per_page) queryParams.append("per_page", params.per_page.toString());
      const response = await $fetch(`${apiBase}/api/rewards?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        rewards.value = response.data.rewards || response.data;
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get rewards");
      }
    } catch (err) {
      error.value = err.message || "Failed to get rewards";
      console.error("Get rewards error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getReward = async (rewardId) => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/rewards/${rewardId}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get reward");
      }
    } catch (err) {
      error.value = err.message || "Failed to get reward";
      console.error("Get reward error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getMyRewards = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.status) queryParams.append("status", params.status);
      if (params.page) queryParams.append("page", params.page.toString());
      if (params.per_page) queryParams.append("per_page", params.per_page.toString());
      const response = await $fetch(`${apiBase}/api/rewards/my?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        userRewards.value = response.data.rewards || response.data;
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get user rewards");
      }
    } catch (err) {
      error.value = err.message || "Failed to get user rewards";
      console.error("Get user rewards error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const redeemReward = async (rewardId) => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/rewards/redeem`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        },
        body: {
          reward_id: rewardId
        }
      });
      if (response.success) {
        if (response.data.new_points_balance !== void 0) {
          authStore.setPoints(response.data.new_points_balance);
        }
        await getMyRewards();
        return response.data;
      } else {
        throw new Error(response.message || "Failed to redeem reward");
      }
    } catch (err) {
      error.value = err.message || "Failed to redeem reward";
      console.error("Redeem reward error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const claimReward = async (userRewardId) => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/rewards/${userRewardId}/claim`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        await getMyRewards();
        return response.data;
      } else {
        throw new Error(response.message || "Failed to claim reward");
      }
    } catch (err) {
      error.value = err.message || "Failed to claim reward";
      console.error("Claim reward error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const cancelReward = async (userRewardId) => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/rewards/${userRewardId}/cancel`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        if (response.data.new_points_balance !== void 0) {
          authStore.setPoints(response.data.new_points_balance);
        }
        await getMyRewards();
        return response.data;
      } else {
        throw new Error(response.message || "Failed to cancel reward");
      }
    } catch (err) {
      error.value = err.message || "Failed to cancel reward";
      console.error("Cancel reward error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getStats = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/rewards/stats`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get reward stats");
      }
    } catch (err) {
      error.value = err.message || "Failed to get reward stats";
      console.error("Get reward stats error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const canRedeem = (reward) => {
    if (points.value < reward.points_required) {
      return false;
    }
    if (!reward.is_active) {
      return false;
    }
    if (reward.is_limited && reward.quantity_redeemed >= reward.quantity_available) {
      return false;
    }
    const now = /* @__PURE__ */ new Date();
    if (reward.start_date && new Date(reward.start_date) > now) {
      return false;
    }
    if (reward.end_date && new Date(reward.end_date) < now) {
      return false;
    }
    return true;
  };
  const getTypeLabel = (type) => {
    const labels = {
      wallet: "\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32",
      badge: "\u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D\u0E15\u0E23\u0E32",
      feature: "\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E1E\u0E34\u0E40\u0E28\u0E29",
      discount: "\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14"
    };
    return labels[type] || type;
  };
  const getTypeIcon = (type) => {
    const icons = {
      wallet: "mdi:wallet",
      badge: "mdi:medal",
      feature: "mdi:star-shooting",
      discount: "mdi:percent"
    };
    return icons[type] || "mdi:gift";
  };
  const getStatusLabel = (status) => {
    const labels = {
      pending: "\u0E23\u0E2D\u0E23\u0E31\u0E1A",
      claimed: "\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27",
      used: "\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27",
      expired: "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38",
      cancelled: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
    };
    return labels[status] || status;
  };
  const getStatusColor = (status) => {
    const colors = {
      pending: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
      claimed: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
      used: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
      expired: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300",
      cancelled: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
    };
    return colors[status] || "bg-gray-100 text-gray-800";
  };
  const formatPoints = (value) => {
    return new Intl.NumberFormat("th-TH").format(value);
  };
  return {
    // State
    rewards,
    userRewards,
    points,
    user,
    isLoading,
    error,
    // Methods
    getRewards,
    getReward,
    getMyRewards,
    redeemReward,
    claimReward,
    cancelReward,
    getStats,
    // Helpers
    canRedeem,
    getTypeLabel,
    getTypeIcon,
    getStatusLabel,
    getStatusColor,
    formatPoints
  };
};

export { useRewards as u };
//# sourceMappingURL=useRewards-lE9TTj1H.mjs.map
