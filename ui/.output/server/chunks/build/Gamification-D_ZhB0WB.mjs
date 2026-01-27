import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, toDisplayString, watch, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderStyle, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, d as useAuthStore, _ as _export_sfc } from './server.mjs';
import { u as useGamification } from './useGamification-BliN7lma.mjs';
import { _ as _sfc_main$4 } from './BaseCard-Baxif1fS.mjs';
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

const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "AchievementsDisplay",
  __ssrInlineRender: true,
  setup(__props) {
    useGamification();
    const activeTab = ref("unlocked");
    const isLoading = ref(false);
    const unlockedAchievements = ref([]);
    const availableAchievements = ref([]);
    const stats = ref({
      total_achievements: 0,
      unlocked_achievements: 0,
      locked_achievements: 0,
      completion_percentage: 0,
      total_points_from_achievements: 0
    });
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric"
      });
    };
    const formatCriteria = (key, value) => {
      const labels = {
        count: "\u0E17\u0E33\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
        points: "\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21",
        streak: "Streak",
        social_type: "\u0E2A\u0E31\u0E07\u0E04\u0E21",
        learning_type: "\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49",
        action_type: "\u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E17\u0E33"
      };
      if (key === "count") {
        return `${labels[key]} ${value} \u0E04\u0E23\u0E31\u0E49\u0E07`;
      }
      return `${labels[key]}: ${value}`;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "achievements-display" }, _attrs))} data-v-b1592733><div class="stats-overview" data-v-b1592733><div class="stat-card" data-v-b1592733><div class="stat-icon" data-v-b1592733>\u{1F3C6}</div><div class="stat-info" data-v-b1592733><p class="stat-value" data-v-b1592733>${ssrInterpolate(stats.value.unlocked_achievements)}/${ssrInterpolate(stats.value.total_achievements)}</p><p class="stat-label" data-v-b1592733>\u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21</p></div></div><div class="stat-card" data-v-b1592733><div class="stat-icon" data-v-b1592733>\u{1F512}</div><div class="stat-info" data-v-b1592733><p class="stat-value" data-v-b1592733>${ssrInterpolate(stats.value.locked_achievements)}</p><p class="stat-label" data-v-b1592733>\u0E04\u0E49\u0E32\u0E07\u0E41\u0E25\u0E01</p></div></div><div class="stat-card" data-v-b1592733><div class="stat-icon" data-v-b1592733>\u{1F4CA}</div><div class="stat-info" data-v-b1592733><p class="stat-value" data-v-b1592733>${ssrInterpolate(stats.value.completion_percentage)}%</p><p class="stat-label" data-v-b1592733>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div></div><div class="stat-card" data-v-b1592733><div class="stat-icon" data-v-b1592733>\u2B50</div><div class="stat-info" data-v-b1592733><p class="stat-value" data-v-b1592733>${ssrInterpolate(stats.value.total_points_from_achievements)}</p><p class="stat-label" data-v-b1592733>\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div></div></div><div class="tabs" data-v-b1592733><button class="${ssrRenderClass([{ active: activeTab.value === "unlocked" }, "tab-btn"])}" data-v-b1592733> \u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21 </button><button class="${ssrRenderClass([{ active: activeTab.value === "available" }, "tab-btn"])}" data-v-b1592733> \u0E04\u0E49\u0E32\u0E07\u0E41\u0E25\u0E01 </button></div>`);
      if (!isLoading.value) {
        _push(`<div class="achievements-list" data-v-b1592733>`);
        if (activeTab.value === "unlocked") {
          _push(`<div class="achievements-grid" data-v-b1592733><!--[-->`);
          ssrRenderList(unlockedAchievements.value, (achievement) => {
            _push(`<div class="achievement-card unlocked" data-v-b1592733><div class="achievement-badge" data-v-b1592733><img${ssrRenderAttr("src", achievement.icon || "/icons/achievement.png")}${ssrRenderAttr("alt", achievement.name)} data-v-b1592733></div><div class="achievement-details" data-v-b1592733><h4 class="achievement-name" data-v-b1592733>${ssrInterpolate(achievement.name)}</h4><p class="achievement-description" data-v-b1592733>${ssrInterpolate(achievement.description)}</p><div class="achievement-meta" data-v-b1592733><span class="achievement-type" data-v-b1592733>${ssrInterpolate(achievement.type_label)}</span>`);
            if (achievement.points_reward) {
              _push(`<span class="achievement-points" data-v-b1592733>+${ssrInterpolate(achievement.points_reward)} \u0E41\u0E15\u0E49\u0E21</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
            if (achievement.completed_at) {
              _push(`<p class="achievement-date" data-v-b1592733> \u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(formatDate(achievement.completed_at))}</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]-->`);
          if (unlockedAchievements.value.length === 0) {
            _push(`<div class="empty-state" data-v-b1592733><i class="lni lni-empty-file" data-v-b1592733></i><p data-v-b1592733>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "available") {
          _push(`<div class="achievements-grid" data-v-b1592733><!--[-->`);
          ssrRenderList(availableAchievements.value, (achievement) => {
            _push(`<div class="achievement-card locked" data-v-b1592733><div class="achievement-badge" data-v-b1592733><img${ssrRenderAttr("src", achievement.icon || "/icons/achievement-locked.png")}${ssrRenderAttr("alt", achievement.name)} data-v-b1592733></div><div class="achievement-details" data-v-b1592733><h4 class="achievement-name" data-v-b1592733>${ssrInterpolate(achievement.name)}</h4><p class="achievement-description" data-v-b1592733>${ssrInterpolate(achievement.description)}</p><div class="achievement-meta" data-v-b1592733><span class="achievement-type" data-v-b1592733>${ssrInterpolate(achievement.type_label)}</span>`);
            if (achievement.points_reward) {
              _push(`<span class="achievement-points" data-v-b1592733>+${ssrInterpolate(achievement.points_reward)} \u0E41\u0E15\u0E49\u0E21</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="achievement-criteria" data-v-b1592733><p class="criteria-label" data-v-b1592733>\u0E40\u0E07\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E1A\u0E23\u0E31\u0E1A:</p><ul class="criteria-list" data-v-b1592733><!--[-->`);
            ssrRenderList(achievement.criteria, (value, key) => {
              _push(`<li data-v-b1592733>${ssrInterpolate(formatCriteria(key, value))}</li>`);
            });
            _push(`<!--]--></ul></div></div></div>`);
          });
          _push(`<!--]-->`);
          if (availableAchievements.value.length === 0) {
            _push(`<div class="empty-state" data-v-b1592733><i class="lni lni-checkmark-circle" data-v-b1592733></i><p data-v-b1592733>\u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14!</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (isLoading.value) {
        _push(`<div class="loading-state" data-v-b1592733><i class="lni lni-spinner lni-spin" data-v-b1592733></i><p data-v-b1592733>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/gamification/AchievementsDisplay.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const AchievementsDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-b1592733"]]);
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "LeaderboardDisplay",
  __ssrInlineRender: true,
  setup(__props) {
    const {
      getPointsLeaderboard,
      getStreakLeaderboard,
      getAchievementLeaderboard,
      getLevelLeaderboard
    } = useGamification();
    const authStore = useAuthStore();
    const activeTab = ref("points");
    const isLoading = ref(false);
    const leaderboardData = ref({
      leaderboard: [],
      current_page: 1,
      total_pages: 1,
      total_users: 0,
      per_page: 10
    });
    const currentUserId = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.id) || 0;
    });
    const formatNumber = (num) => {
      return new Intl.NumberFormat("th-TH").format(num);
    };
    const getAvatarUrl = (item, index = 0) => {
      if (item.avatar) return item.avatar;
      const bgColors = [
        "94a3b8",
        "64748b",
        "78716c",
        "6b7280",
        "71717a",
        "737373",
        "a3a3a3",
        "9ca3af",
        "a1a1aa",
        "a8a29e"
      ];
      const bgColor = bgColors[index % bgColors.length];
      return `https://ui-avatars.com/api/?name=${encodeURIComponent(item.user_name)}&background=${bgColor}&color=fff`;
    };
    const tabs = [
      { id: "points", label: "\u0E41\u0E15\u0E49\u0E21", icon: "lni-star" },
      { id: "streak", label: "Streak", icon: "lni-fire" },
      { id: "achievements", label: "\u0E04\u0E27\u0E32\u0E21", icon: "lni-trophy" },
      { id: "level", label: "\u0E40\u0E25\u0E40\u0E27\u0E25", icon: "lni-medal" }
    ];
    const loadLeaderboard = async () => {
      try {
        isLoading.value = true;
        switch (activeTab.value) {
          case "points":
            leaderboardData.value = await getPointsLeaderboard({ limit: 10, page: 1 });
            break;
          case "streak":
            leaderboardData.value = await getStreakLeaderboard({ limit: 10, page: 1 });
            break;
          case "achievements":
            leaderboardData.value = await getAchievementLeaderboard({ limit: 10, page: 1 });
            break;
          case "level":
            leaderboardData.value = await getLevelLeaderboard({ limit: 10, page: 1 });
            break;
        }
      } catch (error) {
        console.error("Failed to load leaderboard:", error);
      } finally {
        isLoading.value = false;
      }
    };
    const { stop: stopWatchingTab } = watch(activeTab, () => {
      loadLeaderboard();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "leaderboard-display" }, _attrs))} data-v-fd78d6e9><div class="tabs" data-v-fd78d6e9><!--[-->`);
      ssrRenderList(tabs, (tab) => {
        _push(`<button class="${ssrRenderClass([{ active: activeTab.value === tab.id }, "tab-btn"])}" data-v-fd78d6e9><i class="${ssrRenderClass(tab.icon)}" data-v-fd78d6e9></i> ${ssrInterpolate(tab.label)}</button>`);
      });
      _push(`<!--]--></div>`);
      if (!isLoading.value) {
        _push(`<div class="leaderboard-content" data-v-fd78d6e9>`);
        if (activeTab.value === "points") {
          _push(`<div class="leaderboard-list" data-v-fd78d6e9><div class="leaderboard-header" data-v-fd78d6e9><span class="header-rank" data-v-fd78d6e9>\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A</span><span class="header-user" data-v-fd78d6e9>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</span><span class="header-points" data-v-fd78d6e9>\u0E41\u0E15\u0E49\u0E21</span><span class="header-level" data-v-fd78d6e9>\u0E40\u0E25\u0E40\u0E27\u0E25</span></div><!--[-->`);
          ssrRenderList(leaderboardData.value.leaderboard, (item, index) => {
            _push(`<div class="${ssrRenderClass([{ "top-3": index < 3, "current-user": item.user_id === currentUserId.value }, "leaderboard-item"])}" data-v-fd78d6e9><div class="item-rank" data-v-fd78d6e9>`);
            if (index === 0) {
              _push(`<span class="rank-badge gold" data-v-fd78d6e9>\u{1F947}</span>`);
            } else if (index === 1) {
              _push(`<span class="rank-badge silver" data-v-fd78d6e9>\u{1F948}</span>`);
            } else if (index === 2) {
              _push(`<span class="rank-badge bronze" data-v-fd78d6e9>\u{1F949}</span>`);
            } else {
              _push(`<span class="rank-number" data-v-fd78d6e9>#${ssrInterpolate(item.rank)}</span>`);
            }
            _push(`</div><div class="item-user" data-v-fd78d6e9><img${ssrRenderAttr("src", getAvatarUrl(item, index))}${ssrRenderAttr("alt", item.user_name)} class="user-avatar" data-v-fd78d6e9><span class="user-name" data-v-fd78d6e9>${ssrInterpolate(item.user_name)}</span></div><div class="item-points" data-v-fd78d6e9>${ssrInterpolate(formatNumber(item.total_points))} \u0E41\u0E15\u0E49\u0E21</div><div class="item-level" data-v-fd78d6e9><div class="level-info" data-v-fd78d6e9>Lv. ${ssrInterpolate(item.level)}</div>`);
            if (item.followers_count !== void 0) {
              _push(`<div class="follower-info" data-v-fd78d6e9><i class="lni lni-users" data-v-fd78d6e9></i> ${ssrInterpolate(formatNumber(item.followers_count))}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]-->`);
          if (leaderboardData.value.leaderboard.length === 0) {
            _push(`<div class="empty-state" data-v-fd78d6e9><i class="lni lni-empty-file" data-v-fd78d6e9></i><p data-v-fd78d6e9>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "streak") {
          _push(`<div class="leaderboard-list" data-v-fd78d6e9><div class="leaderboard-header" data-v-fd78d6e9><span class="header-rank" data-v-fd78d6e9>\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A</span><span class="header-user" data-v-fd78d6e9>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</span><span class="header-streak" data-v-fd78d6e9>Streak</span><span class="header-level" data-v-fd78d6e9>\u0E23\u0E30\u0E14\u0E31\u0E1A</span></div><!--[-->`);
          ssrRenderList(leaderboardData.value.leaderboard, (item, index) => {
            _push(`<div class="${ssrRenderClass([{ "top-3": index < 3, "current-user": item.user_id === currentUserId.value }, "leaderboard-item"])}" data-v-fd78d6e9><div class="item-rank" data-v-fd78d6e9>`);
            if (index === 0) {
              _push(`<span class="rank-badge gold" data-v-fd78d6e9>\u{1F947}</span>`);
            } else if (index === 1) {
              _push(`<span class="rank-badge silver" data-v-fd78d6e9>\u{1F948}</span>`);
            } else if (index === 2) {
              _push(`<span class="rank-badge bronze" data-v-fd78d6e9>\u{1F949}</span>`);
            } else {
              _push(`<span class="rank-number" data-v-fd78d6e9>#${ssrInterpolate(item.rank)}</span>`);
            }
            _push(`</div><div class="item-user" data-v-fd78d6e9><img${ssrRenderAttr("src", getAvatarUrl(item, index))}${ssrRenderAttr("alt", item.user_name)} class="user-avatar" data-v-fd78d6e9><span class="user-name" data-v-fd78d6e9>${ssrInterpolate(item.user_name)}</span></div><div class="item-streak" data-v-fd78d6e9><span class="streak-icon" data-v-fd78d6e9>${ssrInterpolate(item.streak_icon)}</span><span class="streak-days" data-v-fd78d6e9>${ssrInterpolate(item.current_streak)} \u0E27\u0E31\u0E19</span></div><div class="item-level" data-v-fd78d6e9><div class="level-info" style="${ssrRenderStyle({ color: item.streak_level_color })}" data-v-fd78d6e9>${ssrInterpolate(item.streak_level)}</div>`);
            if (item.followers_count !== void 0) {
              _push(`<div class="follower-info" data-v-fd78d6e9><i class="lni lni-users" data-v-fd78d6e9></i> ${ssrInterpolate(formatNumber(item.followers_count))}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]-->`);
          if (leaderboardData.value.leaderboard.length === 0) {
            _push(`<div class="empty-state" data-v-fd78d6e9><i class="lni lni-empty-file" data-v-fd78d6e9></i><p data-v-fd78d6e9>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "achievements") {
          _push(`<div class="leaderboard-list" data-v-fd78d6e9><div class="leaderboard-header" data-v-fd78d6e9><span class="header-rank" data-v-fd78d6e9>\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A</span><span class="header-user" data-v-fd78d6e9>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</span><span class="header-achievements" data-v-fd78d6e9>\u0E04\u0E27\u0E32\u0E21</span><span class="header-level" data-v-fd78d6e9>\u0E40\u0E25\u0E40\u0E27\u0E25</span></div><!--[-->`);
          ssrRenderList(leaderboardData.value.leaderboard, (item, index) => {
            _push(`<div class="${ssrRenderClass([{ "top-3": index < 3, "current-user": item.user_id === currentUserId.value }, "leaderboard-item"])}" data-v-fd78d6e9><div class="item-rank" data-v-fd78d6e9>`);
            if (index === 0) {
              _push(`<span class="rank-badge gold" data-v-fd78d6e9>\u{1F947}</span>`);
            } else if (index === 1) {
              _push(`<span class="rank-badge silver" data-v-fd78d6e9>\u{1F948}</span>`);
            } else if (index === 2) {
              _push(`<span class="rank-badge bronze" data-v-fd78d6e9>\u{1F949}</span>`);
            } else {
              _push(`<span class="rank-number" data-v-fd78d6e9>#${ssrInterpolate(item.rank)}</span>`);
            }
            _push(`</div><div class="item-user" data-v-fd78d6e9><img${ssrRenderAttr("src", getAvatarUrl(item, index))}${ssrRenderAttr("alt", item.user_name)} class="user-avatar" data-v-fd78d6e9><span class="user-name" data-v-fd78d6e9>${ssrInterpolate(item.user_name)}</span></div><div class="item-achievements" data-v-fd78d6e9>${ssrInterpolate(formatNumber(item.achievement_count))} \u0E04\u0E27\u0E32\u0E21</div><div class="item-level" data-v-fd78d6e9><div class="level-info" data-v-fd78d6e9>Lv. ${ssrInterpolate(item.level)}</div>`);
            if (item.followers_count !== void 0) {
              _push(`<div class="follower-info" data-v-fd78d6e9><i class="lni lni-users" data-v-fd78d6e9></i> ${ssrInterpolate(formatNumber(item.followers_count))}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]-->`);
          if (leaderboardData.value.leaderboard.length === 0) {
            _push(`<div class="empty-state" data-v-fd78d6e9><i class="lni lni-empty-file" data-v-fd78d6e9></i><p data-v-fd78d6e9>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "level") {
          _push(`<div class="leaderboard-list" data-v-fd78d6e9><div class="leaderboard-header" data-v-fd78d6e9><span class="header-rank" data-v-fd78d6e9>\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A</span><span class="header-user" data-v-fd78d6e9>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</span><span class="header-level" data-v-fd78d6e9>\u0E40\u0E25\u0E40\u0E27\u0E25</span><span class="header-points" data-v-fd78d6e9>\u0E41\u0E15\u0E49\u0E21</span></div><!--[-->`);
          ssrRenderList(leaderboardData.value.leaderboard, (item, index) => {
            _push(`<div class="${ssrRenderClass([{ "top-3": index < 3, "current-user": item.user_id === currentUserId.value }, "leaderboard-item"])}" data-v-fd78d6e9><div class="item-rank" data-v-fd78d6e9>`);
            if (index === 0) {
              _push(`<span class="rank-badge gold" data-v-fd78d6e9>\u{1F947}</span>`);
            } else if (index === 1) {
              _push(`<span class="rank-badge silver" data-v-fd78d6e9>\u{1F948}</span>`);
            } else if (index === 2) {
              _push(`<span class="rank-badge bronze" data-v-fd78d6e9>\u{1F949}</span>`);
            } else {
              _push(`<span class="rank-number" data-v-fd78d6e9>#${ssrInterpolate(item.rank)}</span>`);
            }
            _push(`</div><div class="item-user" data-v-fd78d6e9><img${ssrRenderAttr("src", item.avatar || "/images/default-avatar.png")}${ssrRenderAttr("alt", item.user_name)} class="user-avatar" data-v-fd78d6e9><span class="user-name" data-v-fd78d6e9>${ssrInterpolate(item.user_name)}</span></div><div class="item-level" data-v-fd78d6e9><div class="level-info" data-v-fd78d6e9>Lv. ${ssrInterpolate(item.level)}</div>`);
            if (item.followers_count !== void 0) {
              _push(`<div class="follower-info" data-v-fd78d6e9><i class="lni lni-users" data-v-fd78d6e9></i> ${ssrInterpolate(formatNumber(item.followers_count))}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="item-points" data-v-fd78d6e9>${ssrInterpolate(formatNumber(item.total_points))} \u0E41\u0E15\u0E49\u0E21</div></div>`);
          });
          _push(`<!--]-->`);
          if (leaderboardData.value.leaderboard.length === 0) {
            _push(`<div class="empty-state" data-v-fd78d6e9><i class="lni lni-empty-file" data-v-fd78d6e9></i><p data-v-fd78d6e9>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (leaderboardData.value.total_pages > 1) {
        _push(`<div class="pagination" data-v-fd78d6e9><button class="btn-pagination"${ssrIncludeBooleanAttr(leaderboardData.value.current_page <= 1) ? " disabled" : ""} data-v-fd78d6e9><i class="lni lni-chevron-left" data-v-fd78d6e9></i></button><span class="page-info" data-v-fd78d6e9> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(leaderboardData.value.current_page)} / ${ssrInterpolate(leaderboardData.value.total_pages)}</span><button class="btn-pagination"${ssrIncludeBooleanAttr(leaderboardData.value.current_page >= leaderboardData.value.total_pages) ? " disabled" : ""} data-v-fd78d6e9><i class="lni lni-chevron-right" data-v-fd78d6e9></i></button></div>`);
      } else {
        _push(`<!---->`);
      }
      if (isLoading.value) {
        _push(`<div class="loading-state" data-v-fd78d6e9><i class="lni lni-spinner lni-spin" data-v-fd78d6e9></i><p data-v-fd78d6e9>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/gamification/LeaderboardDisplay.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const LeaderboardDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-fd78d6e9"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "StreakDisplay",
  __ssrInlineRender: true,
  setup(__props) {
    useGamification();
    const streakData = ref({
      current_streak: 0,
      longest_streak: 0,
      bonus_points_earned: 0,
      streak_level: "Newbie",
      streak_icon: "\u{1F525}",
      streak_level_color: "#9ca3af",
      is_active_today: false,
      next_milestone: 5,
      days_until_next_bonus: 5,
      potential_bonus: 0
    });
    const showHistory = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "streak-display" }, _attrs))} data-v-798028c4><div class="streak-card" data-v-798028c4><div class="streak-icon" style="${ssrRenderStyle({ background: streakData.value.streak_level_color })}" data-v-798028c4><span class="icon-emoji" data-v-798028c4>${ssrInterpolate(streakData.value.streak_icon)}</span></div><div class="streak-info" data-v-798028c4><h3 class="streak-title" data-v-798028c4>Streak \u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</h3><p class="streak-count" data-v-798028c4>${ssrInterpolate(streakData.value.current_streak)} \u0E27\u0E31\u0E19</p><p class="streak-level" data-v-798028c4>${ssrInterpolate(streakData.value.streak_level)} Level</p></div><div class="streak-bonus" data-v-798028c4><p class="bonus-label" data-v-798028c4>\u0E42\u0E1A\u0E19\u0E31\u0E2A\u0E16\u0E31\u0E14\u0E44\u0E1B</p><p class="bonus-amount" data-v-798028c4>+${ssrInterpolate(streakData.value.potential_bonus)} \u0E41\u0E15\u0E49\u0E21</p><p class="bonus-milestone" data-v-798028c4>\u0E17\u0E35\u0E48 ${ssrInterpolate(streakData.value.next_milestone)} \u0E27\u0E31\u0E19</p><p class="bonus-days" data-v-798028c4>(${ssrInterpolate(streakData.value.days_until_next_bonus)} \u0E27\u0E31\u0E19)</p></div></div>`);
      if (showHistory.value) {
        _push(`<div class="streak-history" data-v-798028c4><h4 data-v-798028c4>\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34 Streak</h4><div class="history-item" data-v-798028c4><span class="history-label" data-v-798028c4>Streak \u0E22\u0E32\u0E27\u0E19\u0E32\u0E22:</span><span class="history-value" data-v-798028c4>${ssrInterpolate(streakData.value.longest_streak)} \u0E27\u0E31\u0E19</span></div><div class="history-item" data-v-798028c4><span class="history-label" data-v-798028c4>\u0E41\u0E15\u0E49\u0E21\u0E42\u0E1A\u0E19\u0E31\u0E2A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14:</span><span class="history-value" data-v-798028c4>${ssrInterpolate(streakData.value.bonus_points_earned)} \u0E41\u0E15\u0E49\u0E21</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="btn-toggle-history" data-v-798028c4>${ssrInterpolate(showHistory.value ? "\u0E0B\u0E48\u0E2D\u0E19" : "\u0E41\u0E2A\u0E14\u0E07")} \u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34 </button></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/gamification/StreakDisplay.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const StreakDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-798028c4"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Gamification",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A - Nuxni"
    });
    const authStore = useAuthStore();
    const {
      isLoading
    } = useGamification();
    const activeTab = ref("achievements");
    ref([]);
    ref([]);
    const streak = ref(null);
    const level = ref(null);
    const stats = ref({
      total_achievements: 0,
      unlocked_achievements: 0,
      locked_achievements: 0,
      completion_percentage: 0,
      total_points_from_achievements: 0
    });
    const user = computed(() => authStore.user);
    const points = computed(() => authStore.points || 0);
    const formatPoints = (value) => {
      return new Intl.NumberFormat("th-TH").format(value);
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-6xl mx-auto"><div class="text-center mb-8"><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-indigo-400 to-purple-600 rounded-full flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:trophy",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"> \u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto"> \u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E04\u0E27\u0E32\u0E21\u0E01\u0E49\u0E32\u0E27\u0E2B\u0E19\u0E49\u0E32\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 \u0E1A\u0E23\u0E23\u0E25\u0E38\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08 \u0E41\u0E25\u0E30\u0E41\u0E02\u0E48\u0E07\u0E02\u0E31\u0E19\u0E01\u0E31\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19 </p></div><div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">`);
      _push(ssrRenderComponent(_sfc_main$4, { class: "bg-gradient-to-br from-indigo-500 to-purple-600 border-0" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d;
          if (_push2) {
            _push2(`<div class="text-white text-center"${_scopeId}><div class="w-12 h-12 mx-auto mb-2 bg-white/20 rounded-full flex items-center justify-center backdrop-blur"${_scopeId}><span class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(((_a = level.value) == null ? void 0 : _a.level) || ((_b = user.value) == null ? void 0 : _b.level) || 1)}</span></div><p class="text-sm font-medium opacity-80"${_scopeId}>\u0E40\u0E25\u0E40\u0E27\u0E25</p></div>`);
          } else {
            return [
              createVNode("div", { class: "text-white text-center" }, [
                createVNode("div", { class: "w-12 h-12 mx-auto mb-2 bg-white/20 rounded-full flex items-center justify-center backdrop-blur" }, [
                  createVNode("span", { class: "text-2xl font-bold" }, toDisplayString(((_c = level.value) == null ? void 0 : _c.level) || ((_d = user.value) == null ? void 0 : _d.level) || 1), 1)
                ]),
                createVNode("p", { class: "text-sm font-medium opacity-80" }, "\u0E40\u0E25\u0E40\u0E27\u0E25")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$4, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:star-circle",
              class: "w-8 h-8 text-amber-500 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatPoints(points.value))}</p><p class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21</p></div>`);
          } else {
            return [
              createVNode("div", { class: "text-center" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:star-circle",
                  class: "w-8 h-8 text-amber-500 mx-auto mb-2"
                }),
                createVNode("p", { class: "text-xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatPoints(points.value)), 1),
                createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$4, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:trophy",
              class: "w-8 h-8 text-green-500 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.unlocked_achievements)}/${ssrInterpolate(stats.value.total_achievements)}</p><p class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div>`);
          } else {
            return [
              createVNode("div", { class: "text-center" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:trophy",
                  class: "w-8 h-8 text-green-500 mx-auto mb-2"
                }),
                createVNode("p", { class: "text-xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.unlocked_achievements) + "/" + toDisplayString(stats.value.total_achievements), 1),
                createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$4, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b;
          if (_push2) {
            _push2(`<div class="text-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:fire",
              class: "w-8 h-8 text-orange-500 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(((_a = streak.value) == null ? void 0 : _a.current_streak) || 0)}</p><p class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>\u0E27\u0E31\u0E19\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E01\u0E31\u0E19</p></div>`);
          } else {
            return [
              createVNode("div", { class: "text-center" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:fire",
                  class: "w-8 h-8 text-orange-500 mx-auto mb-2"
                }),
                createVNode("p", { class: "text-xl font-bold text-gray-900 dark:text-white" }, toDisplayString(((_b = streak.value) == null ? void 0 : _b.current_streak) || 0), 1),
                createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E27\u0E31\u0E19\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E01\u0E31\u0E19")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$4, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="text-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:percent",
              class: "w-8 h-8 text-blue-500 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.completion_percentage)}% </p><p class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08</p></div>`);
          } else {
            return [
              createVNode("div", { class: "text-center" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:percent",
                  class: "w-8 h-8 text-blue-500 mx-auto mb-2"
                }),
                createVNode("p", { class: "text-xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.completion_percentage) + "% ", 1),
                createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_sfc_main$4, { class: "mb-8" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m, _n, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x, _y, _z;
          if (_push2) {
            _push2(`<div class="p-2"${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"${_scopeId}>\u0E04\u0E27\u0E32\u0E21\u0E01\u0E49\u0E32\u0E27\u0E2B\u0E19\u0E49\u0E32\u0E40\u0E25\u0E40\u0E27\u0E25</h3><div class="flex items-center gap-6"${_scopeId}><div class="flex-shrink-0"${_scopeId}><div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white"${_scopeId}><span class="text-2xl font-bold"${_scopeId}>${ssrInterpolate(((_a = level.value) == null ? void 0 : _a.level) || ((_b = user.value) == null ? void 0 : _b.level) || 1)}</span></div></div><div class="flex-grow"${_scopeId}><div class="flex items-center justify-between mb-2"${_scopeId}><span class="text-sm font-medium text-gray-900 dark:text-white"${_scopeId}> \u0E40\u0E25\u0E40\u0E27\u0E25 ${ssrInterpolate(((_c = level.value) == null ? void 0 : _c.level) || ((_d = user.value) == null ? void 0 : _d.level) || 1)}</span><span class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(((_e = level.value) == null ? void 0 : _e.current_xp) || ((_f = user.value) == null ? void 0 : _f.current_xp) || 0)} / ${ssrInterpolate(((_g = level.value) == null ? void 0 : _g.xp_for_next_level) || ((_h = user.value) == null ? void 0 : _h.xp_for_next_level) || 100)} XP </span></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"${_scopeId}><div class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${((_i = level.value) == null ? void 0 : _i.progress_percentage) || 0}%` })}"${_scopeId}></div></div><p class="text-xs text-gray-500 dark:text-gray-400 mt-2"${_scopeId}> \u0E2D\u0E35\u0E01 ${ssrInterpolate((((_j = level.value) == null ? void 0 : _j.xp_for_next_level) || 100) - (((_k = level.value) == null ? void 0 : _k.current_xp) || 0))} XP \u0E08\u0E30\u0E02\u0E36\u0E49\u0E19\u0E40\u0E25\u0E40\u0E27\u0E25\u0E16\u0E31\u0E14\u0E44\u0E1B </p></div><div class="flex-shrink-0"${_scopeId}><div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500"${_scopeId}><span class="text-2xl font-bold"${_scopeId}>${ssrInterpolate((((_l = level.value) == null ? void 0 : _l.level) || ((_m = user.value) == null ? void 0 : _m.level) || 1) + 1)}</span></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-2" }, [
                createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white mb-4" }, "\u0E04\u0E27\u0E32\u0E21\u0E01\u0E49\u0E32\u0E27\u0E2B\u0E19\u0E49\u0E32\u0E40\u0E25\u0E40\u0E27\u0E25"),
                createVNode("div", { class: "flex items-center gap-6" }, [
                  createVNode("div", { class: "flex-shrink-0" }, [
                    createVNode("div", { class: "w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white" }, [
                      createVNode("span", { class: "text-2xl font-bold" }, toDisplayString(((_n = level.value) == null ? void 0 : _n.level) || ((_o = user.value) == null ? void 0 : _o.level) || 1), 1)
                    ])
                  ]),
                  createVNode("div", { class: "flex-grow" }, [
                    createVNode("div", { class: "flex items-center justify-between mb-2" }, [
                      createVNode("span", { class: "text-sm font-medium text-gray-900 dark:text-white" }, " \u0E40\u0E25\u0E40\u0E27\u0E25 " + toDisplayString(((_p = level.value) == null ? void 0 : _p.level) || ((_q = user.value) == null ? void 0 : _q.level) || 1), 1),
                      createVNode("span", { class: "text-sm text-gray-500 dark:text-gray-400" }, toDisplayString(((_r = level.value) == null ? void 0 : _r.current_xp) || ((_s = user.value) == null ? void 0 : _s.current_xp) || 0) + " / " + toDisplayString(((_t = level.value) == null ? void 0 : _t.xp_for_next_level) || ((_u = user.value) == null ? void 0 : _u.xp_for_next_level) || 100) + " XP ", 1)
                    ]),
                    createVNode("div", { class: "h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden" }, [
                      createVNode("div", {
                        class: "h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-500",
                        style: { width: `${((_v = level.value) == null ? void 0 : _v.progress_percentage) || 0}%` }
                      }, null, 4)
                    ]),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-2" }, " \u0E2D\u0E35\u0E01 " + toDisplayString((((_w = level.value) == null ? void 0 : _w.xp_for_next_level) || 100) - (((_x = level.value) == null ? void 0 : _x.current_xp) || 0)) + " XP \u0E08\u0E30\u0E02\u0E36\u0E49\u0E19\u0E40\u0E25\u0E40\u0E27\u0E25\u0E16\u0E31\u0E14\u0E44\u0E1B ", 1)
                  ]),
                  createVNode("div", { class: "flex-shrink-0" }, [
                    createVNode("div", { class: "w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500" }, [
                      createVNode("span", { class: "text-2xl font-bold" }, toDisplayString((((_y = level.value) == null ? void 0 : _y.level) || ((_z = user.value) == null ? void 0 : _z.level) || 1) + 1), 1)
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="flex gap-4 mb-6"><button class="${ssrRenderClass([activeTab.value === "achievements" ? "bg-primary-500 text-white shadow-md" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700", "px-6 py-2 rounded-xl font-medium transition-all flex items-center gap-2"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:trophy",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08 </button><button class="${ssrRenderClass([activeTab.value === "leaderboard" ? "bg-primary-500 text-white shadow-md" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700", "px-6 py-2 rounded-xl font-medium transition-all flex items-center gap-2"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:chart-line",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E2D\u0E31\u0E19\u0E14\u0E31\u0E1A </button><button class="${ssrRenderClass([activeTab.value === "streak" ? "bg-primary-500 text-white shadow-md" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700", "px-6 py-2 rounded-xl font-medium transition-all flex items-center gap-2"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:fire",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E15\u0E48\u0E2D\u0E40\u0E19\u0E37\u0E48\u0E2D\u0E07 </button></div>`);
      if (unref(isLoading)) {
        _push(`<div class="py-12 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:loading",
          class: "w-12 h-12 text-primary-500 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="mt-4 text-gray-500 dark:text-gray-400">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
      } else {
        _push(`<div>`);
        if (activeTab.value === "achievements") {
          _push(`<div>`);
          _push(ssrRenderComponent(AchievementsDisplay, null, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "leaderboard") {
          _push(`<div>`);
          _push(ssrRenderComponent(LeaderboardDisplay, null, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "streak") {
          _push(`<div>`);
          _push(ssrRenderComponent(StreakDisplay, null, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Gamification.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Gamification-D_ZhB0WB.mjs.map
