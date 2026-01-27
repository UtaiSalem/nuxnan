import { ref, computed } from 'vue';
import { d as useAuthStore, b as useRuntimeConfig } from './server.mjs';

const useGamification = () => {
  const authStore = useAuthStore();
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase;
  const isLoading = ref(false);
  const error = ref(null);
  const user = computed(() => authStore.user);
  const recordLogin = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/gamification/login`, {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to record login");
      }
    } catch (err) {
      error.value = err.message || "Failed to record login";
      console.error("Record login error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getStreakInfo = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/gamification/streak`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get streak info");
      }
    } catch (err) {
      error.value = err.message || "Failed to get streak info";
      console.error("Get streak info error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getAchievements = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/gamification/achievements`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data.achievements;
      } else {
        throw new Error(response.message || "Failed to get achievements");
      }
    } catch (err) {
      error.value = err.message || "Failed to get achievements";
      console.error("Get achievements error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getAvailableAchievements = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/gamification/achievements/available`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data.achievements;
      } else {
        throw new Error(response.message || "Failed to get available achievements");
      }
    } catch (err) {
      error.value = err.message || "Failed to get available achievements";
      console.error("Get available achievements error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getAchievementStats = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/gamification/achievements/stats`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get achievement stats");
      }
    } catch (err) {
      error.value = err.message || "Failed to get achievement stats";
      console.error("Get achievement stats error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getPointsLeaderboard = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.limit) queryParams.append("limit", params.limit.toString());
      if (params.page) queryParams.append("page", params.page.toString());
      const response = await $fetch(`${apiBase}/api/gamification/leaderboard/points?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get points leaderboard");
      }
    } catch (err) {
      error.value = err.message || "Failed to get points leaderboard";
      console.error("Get points leaderboard error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getStreakLeaderboard = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.limit) queryParams.append("limit", params.limit.toString());
      if (params.page) queryParams.append("page", params.page.toString());
      const response = await $fetch(`${apiBase}/api/gamification/leaderboard/streak?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get streak leaderboard");
      }
    } catch (err) {
      error.value = err.message || "Failed to get streak leaderboard";
      console.error("Get streak leaderboard error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getAchievementLeaderboard = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.limit) queryParams.append("limit", params.limit.toString());
      if (params.page) queryParams.append("page", params.page.toString());
      const response = await $fetch(`${apiBase}/api/gamification/leaderboard/achievements?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get achievement leaderboard");
      }
    } catch (err) {
      error.value = err.message || "Failed to get achievement leaderboard";
      console.error("Get achievement leaderboard error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getLevelLeaderboard = async (params = {}) => {
    try {
      isLoading.value = true;
      error.value = null;
      const queryParams = new URLSearchParams();
      if (params.limit) queryParams.append("limit", params.limit.toString());
      if (params.page) queryParams.append("page", params.page.toString());
      const response = await $fetch(`${apiBase}/api/gamification/leaderboard/level?${queryParams.toString()}`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get level leaderboard");
      }
    } catch (err) {
      error.value = err.message || "Failed to get level leaderboard";
      console.error("Get level leaderboard error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getLeaderboard = async (params = {}) => {
    const type = params.type || "points";
    const { limit, page } = params;
    switch (type) {
      case "streak":
        return getStreakLeaderboard({ limit, page });
      case "achievements":
        return getAchievementLeaderboard({ limit, page });
      case "level":
        return getLevelLeaderboard({ limit, page });
      case "points":
      default:
        return getPointsLeaderboard({ limit, page });
    }
  };
  const getUserLevel = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const user2 = authStore.user;
      if (user2) {
        return {
          level: user2.level || 1,
          current_xp: user2.current_xp || 0,
          xp_for_next_level: user2.xp_for_next_level || 100,
          progress_percentage: user2.level_progress || 0
        };
      }
      return null;
    } catch (err) {
      error.value = err.message || "Failed to get user level";
      console.error("Get user level error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const getLeaderboardSummary = async () => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await $fetch(`${apiBase}/api/gamification/leaderboard/summary`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${authStore.token}`
        }
      });
      if (response.success) {
        return response.data;
      } else {
        throw new Error(response.message || "Failed to get leaderboard summary");
      }
    } catch (err) {
      error.value = err.message || "Failed to get leaderboard summary";
      console.error("Get leaderboard summary error:", err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  const showStreakNotification = (streakData) => {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: streakData.bonus_milestone_reached ? "success" : "info",
        title: streakData.bonus_milestone_reached ? "\u{1F525} Streak Bonus!" : "\u{1F525} Streak Updated",
        html: `
          <div style="text-align: center;">
            <p style="font-size: 48px; margin: 0;">${streakData.streak_icon}</p>
            <h3 style="margin: 10px 0;">Streak: ${streakData.current_streak} \u0E27\u0E31\u0E19</h3>
            <p style="margin: 0;">${streakData.streak_level} Level</p>
            ${streakData.bonus_points_earned > 0 ? `<p style="margin: 10px 0; font-weight: bold; color: #10b981;">+${streakData.bonus_points_earned} \u0E41\u0E15\u0E49\u0E21</p>` : ""}
            ${streakData.next_milestone ? `<p style="margin: 10px 0; color: #6b7280;">Bonus \u0E16\u0E31\u0E14\u0E44\u0E1B\u0E17\u0E35\u0E48 ${streakData.next_milestone} \u0E27\u0E31\u0E19 (${streakData.days_until_next_bonus} \u0E27\u0E31\u0E19)</p>` : ""}
          </div>
        `,
        confirmButtonText: "\u0E23\u0E31\u0E1A\u0E17\u0E23\u0E32\u0E1A",
        confirmButtonColor: "#10b981"
      });
    }
  };
  const showAchievementNotification = (achievement) => {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        icon: "success",
        title: "\u{1F3C6} \u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
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
  return {
    // State
    user,
    isLoading,
    error,
    // Methods
    recordLogin,
    getStreakInfo,
    getAchievements,
    getAvailableAchievements,
    getAchievementStats,
    getPointsLeaderboard,
    getStreakLeaderboard,
    getAchievementLeaderboard,
    getLevelLeaderboard,
    getLeaderboard,
    getUserLevel,
    getLeaderboardSummary,
    // Notifications
    showStreakNotification,
    showAchievementNotification
  };
};

export { useGamification as u };
//# sourceMappingURL=useGamification-BliN7lma.mjs.map
