import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, watch, mergeProps, unref, withCtx, createVNode, createBlock, createCommentVNode, openBlock, createTextVNode, toDisplayString, Fragment, renderList, withDirectives, vShow, isRef, vModelText, resolveComponent, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderList, ssrRenderStyle, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$a } from './BaseCard-Baxif1fS.mjs';
import { _ as _sfc_main$b, F as FeedPost } from './ProfileCompletionWidget-BzF-6HBI.mjs';
import { _ as _sfc_main$c } from './CreatePostBox-OC44HEYf.mjs';
import { _ as _export_sfc, p as useRoute, d as useAuthStore, i as useApi, n as navigateTo } from './server.mjs';
import { defineStore } from 'pinia';
import { u as useToast } from './useToast-BpzfS75l.mjs';
import { f as formatTimeAgo, g as getCurrentDateThai, a as getCurrentDate, i as isValidDate, b as formatDate, c as formatDateThai, d as formatDateForInput } from './dateUtils-DQlkT5wi.mjs';
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
import './PollCard-DKn1EeyZ.mjs';
import './useAvatar-C8DTKR1c.mjs';
import './useSweetAlert-jHixiibP.mjs';
import 'sweetalert2';
import './ImageLightbox-D9vQ7Zkj.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main$9 = /* @__PURE__ */ defineComponent({
  __name: "CircleAvatar",
  __ssrInlineRender: true,
  props: {
    src: {},
    alt: { default: "Avatar" },
    fallbackIcon: { default: "mdi:account" },
    size: { default: "md" },
    showBorder: { type: Boolean, default: true },
    borderColor: { default: "#23d2e2" },
    borderWidth: { default: 3 },
    showOnlineStatus: { type: Boolean, default: false },
    isOnline: { type: Boolean, default: false }
  },
  emits: ["error"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const sizePresets = {
      "xs": 32,
      "sm": 48,
      "md": 80,
      "lg": 120,
      "xl": 160,
      "2xl": 200,
      "3xl": 260
    };
    const computedSize = computed(() => {
      if (typeof props.size === "number") return props.size;
      return sizePresets[props.size] || sizePresets["md"];
    });
    const sizeClass = computed(() => {
      if (typeof props.size === "string") return `size-${props.size}`;
      return "";
    });
    const wrapperStyle = computed(() => ({
      width: `${computedSize.value}px`,
      height: `${computedSize.value}px`
    }));
    const avatarStyle = computed(() => ({
      width: `${computedSize.value}px`,
      height: `${computedSize.value}px`,
      borderColor: props.showBorder ? props.borderColor : "transparent",
      borderWidth: props.showBorder ? `${props.borderWidth}px` : "0"
    }));
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Icon = resolveComponent("Icon");
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: "circle-avatar-wrapper",
        style: wrapperStyle.value
      }, _attrs))} data-v-a5fac8db><div class="${ssrRenderClass([[sizeClass.value, { "has-border": __props.showBorder }], "circle-avatar"])}" style="${ssrRenderStyle(avatarStyle.value)}" data-v-a5fac8db>`);
      if (__props.src) {
        _push(`<img${ssrRenderAttr("src", __props.src)}${ssrRenderAttr("alt", __props.alt)} class="avatar-image" data-v-a5fac8db>`);
      } else {
        _push(`<div class="avatar-fallback" data-v-a5fac8db>`);
        _push(ssrRenderComponent(_component_Icon, {
          name: __props.fallbackIcon,
          class: "fallback-icon"
        }, null, _parent));
        _push(`</div>`);
      }
      _push(`</div>`);
      if (__props.showOnlineStatus) {
        _push(`<div class="${ssrRenderClass([{ online: __props.isOnline }, "online-status"])}" data-v-a5fac8db></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/CircleAvatar.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const CircleAvatar = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["__scopeId", "data-v-a5fac8db"]]);
const useChatStore = defineStore("chat", () => {
  const messages = ref([]);
  const friends = ref([]);
  function setFriends(friendList) {
    friends.value = friendList;
  }
  function addMessage(message) {
    messages.value.push(message);
  }
  return {
    messages,
    friends,
    setFriends,
    addMessage
  };
});
const useFriends = () => {
  const api = useApi();
  const chatStore = useChatStore();
  const friends = ref([]);
  const friendRequests = ref([]);
  const suggestions = ref([]);
  const isLoading = ref(false);
  const isLoadingRequests = ref(false);
  const isLoadingSuggestions = ref(false);
  const error = ref(null);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const hasMore = computed(() => currentPage.value < lastPage.value);
  const fetchFriends = async (userId, page = 1) => {
    isLoading.value = true;
    error.value = null;
    try {
      const endpoint = userId ? `/api/users/${userId}/friends?page=${page}` : `/api/friends?page=${page}`;
      const response = await api.get(endpoint);
      if (response.success) {
        const friendsList = response.data || response.friends || [];
        if (page === 1) {
          friends.value = friendsList;
        } else {
          friends.value = [...friends.value, ...friendsList];
        }
        if (response.meta) {
          currentPage.value = response.meta.current_page;
          lastPage.value = response.meta.last_page;
        }
        chatStore.setFriends(friends.value);
        return friendsList;
      }
      return [];
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E32\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E14\u0E49";
      console.error("Error fetching friends:", err);
      return [];
    } finally {
      isLoading.value = false;
    }
  };
  const fetchPendingRequests = async () => {
    isLoadingRequests.value = true;
    error.value = null;
    try {
      const response = await api.get("/api/friends/pending");
      if (response.success) {
        friendRequests.value = response.requests || response.data || [];
        return friendRequests.value;
      }
      return [];
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E14\u0E49";
      console.error("Error fetching friend requests:", err);
      return [];
    } finally {
      isLoadingRequests.value = false;
    }
  };
  const fetchSuggestions = async () => {
    isLoadingSuggestions.value = true;
    error.value = null;
    try {
      const response = await api.get("/api/friends/suggestions");
      if (response.success) {
        suggestions.value = response.users || response.data || [];
        return suggestions.value;
      }
      return [];
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E04\u0E33\u0E41\u0E19\u0E30\u0E19\u0E33\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E14\u0E49";
      console.error("Error fetching suggestions:", err);
      return [];
    } finally {
      isLoadingSuggestions.value = false;
    }
  };
  const sendFriendRequest = async (userId) => {
    try {
      const response = await api.post(`/api/friends/${userId}`, {});
      if (response.success) {
        suggestions.value = suggestions.value.filter((s) => s.id !== userId);
        return true;
      }
      return false;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E14\u0E49";
      console.error("Error sending friend request:", err);
      return false;
    }
  };
  const acceptFriendRequest = async (userId) => {
    try {
      const response = await api.patch(`/api/friends/${userId}/accept`, {});
      if (response.success) {
        const request = friendRequests.value.find((r) => r.sender.id === userId);
        friendRequests.value = friendRequests.value.filter((r) => r.sender.id !== userId);
        if (request) {
          friends.value.unshift(request.sender);
        }
        return true;
      }
      return false;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E2D\u0E21\u0E23\u0E31\u0E1A\u0E04\u0E33\u0E02\u0E2D\u0E44\u0E14\u0E49";
      console.error("Error accepting friend request:", err);
      return false;
    }
  };
  const denyFriendRequest = async (userId) => {
    try {
      const response = await api.post(`/api/friends/${userId}/deny`, {});
      if (response.success) {
        friendRequests.value = friendRequests.value.filter((r) => r.sender.id !== userId);
        return true;
      }
      return false;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E04\u0E33\u0E02\u0E2D\u0E44\u0E14\u0E49";
      console.error("Error denying friend request:", err);
      return false;
    }
  };
  const cancelFriendRequest = async (userId) => {
    try {
      const response = await api.delete(`/api/friends/${userId}`);
      if (response.success) {
        return true;
      }
      return false;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D\u0E44\u0E14\u0E49";
      console.error("Error canceling friend request:", err);
      return false;
    }
  };
  const unfriend = async (userId) => {
    try {
      const response = await api.post(`/api/friends/${userId}/unfriend`, {});
      if (response.success) {
        friends.value = friends.value.filter((f) => f.id !== userId);
        return true;
      }
      return false;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E14\u0E49";
      console.error("Error unfriending:", err);
      return false;
    }
  };
  const searchFriends = async (query) => {
    if (!query.trim()) {
      return friends.value;
    }
    try {
      const response = await api.get(`/api/friends/search?q=${encodeURIComponent(query)}`);
      if (response.success) {
        return response.data || response.friends || [];
      }
      return [];
    } catch (err) {
      console.error("Error searching friends:", err);
      return friends.value.filter(
        (f) => {
          var _a;
          return f.name.toLowerCase().includes(query.toLowerCase()) || ((_a = f.username) == null ? void 0 : _a.toLowerCase().includes(query.toLowerCase()));
        }
      );
    }
  };
  const getMutualFriends = async (userId) => {
    var _a, _b;
    try {
      const response = await api.get(`/api/users/${userId}/mutual-friends`);
      if (response.success) {
        return {
          count: response.count || ((_a = response.data) == null ? void 0 : _a.count) || 0,
          friends: response.friends || ((_b = response.data) == null ? void 0 : _b.friends) || []
        };
      }
      return { count: 0, friends: [] };
    } catch (err) {
      console.error("Error fetching mutual friends:", err);
      return { count: 0, friends: [] };
    }
  };
  const loadMore = async (userId) => {
    if (!hasMore.value || isLoading.value) return;
    await fetchFriends(userId, currentPage.value + 1);
  };
  const clearState = () => {
    friends.value = [];
    friendRequests.value = [];
    suggestions.value = [];
    currentPage.value = 1;
    lastPage.value = 1;
    error.value = null;
  };
  return {
    // State
    friends,
    friendRequests,
    suggestions,
    isLoading,
    isLoadingRequests,
    isLoadingSuggestions,
    error,
    hasMore,
    // Methods
    fetchFriends,
    fetchPendingRequests,
    fetchSuggestions,
    sendFriendRequest,
    acceptFriendRequest,
    denyFriendRequest,
    cancelFriendRequest,
    unfriend,
    searchFriends,
    getMutualFriends,
    loadMore,
    clearState
  };
};
const _sfc_main$8 = /* @__PURE__ */ defineComponent({
  __name: "FriendsList",
  __ssrInlineRender: true,
  props: {
    userId: {},
    isOwnProfile: { type: Boolean }
  },
  emits: ["friend-action"],
  setup(__props, { emit: __emit }) {
    const {
      friends,
      isLoading,
      hasMore,
      searchFriends
    } = useFriends();
    useToast();
    const searchQuery = ref("");
    const searchResults = ref([]);
    const isSearching = ref(false);
    const selectedFilter = ref("all");
    const showConfirmModal = ref(false);
    const friendToRemove = ref(null);
    const displayedFriends = computed(() => {
      if (searchQuery.value) {
        return searchResults.value;
      }
      let filtered = [...friends.value];
      if (selectedFilter.value === "online") {
        filtered = filtered.filter((f) => f.is_online);
      } else if (selectedFilter.value === "recent") {
        filtered = filtered.slice(0, 12);
      }
      return filtered;
    });
    const onlineFriendsCount = computed(() => friends.value.filter((f) => f.is_online).length);
    const handleSearch = async () => {
      if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
      }
      isSearching.value = true;
      try {
        searchResults.value = await searchFriends(searchQuery.value);
      } finally {
        isSearching.value = false;
      }
    };
    const debouncedSearch = useDebounceFn(handleSearch, 300);
    const confirmUnfriend = (friend) => {
      friendToRemove.value = friend;
      showConfirmModal.value = true;
    };
    const goToProfile = (friend) => {
      navigateTo(`/profile/${friend.reference_code || friend.id}`);
    };
    const startChat = (friend) => {
      navigateTo(`/messages/${friend.id}`);
    };
    watch(searchQuery, () => {
      debouncedSearch();
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-459cae71>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 p-5" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4" data-v-459cae71${_scopeId}><div data-v-459cae71${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2" data-v-459cae71${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-24-filled",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 <span class="text-vikinger-cyan font-normal ml-1" data-v-459cae71${_scopeId}>(${ssrInterpolate(unref(friends).length)})</span></h3><p class="text-sm text-gray-400 mt-1" data-v-459cae71${_scopeId}><span class="text-green-400" data-v-459cae71${_scopeId}>${ssrInterpolate(unref(onlineFriendsCount))}</span> \u0E04\u0E19\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C </p></div><div class="relative w-full md:w-80" data-v-459cae71${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:search-24-regular",
              class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
            }, null, _parent2, _scopeId));
            _push2(`<input${ssrRenderAttr("value", unref(searchQuery))} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19..." class="w-full pl-10 pr-4 py-2.5 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple focus:border-transparent" data-v-459cae71${_scopeId}>`);
            if (unref(isSearching)) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 animate-spin"
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div><div class="flex gap-2 border-b border-gray-700 pb-3" data-v-459cae71${_scopeId}><!--[-->`);
            ssrRenderList([
              { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "fluent:people-24-regular" },
              { key: "online", label: "\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C", icon: "fluent:presence-available-24-filled" },
              { key: "recent", label: "\u0E40\u0E1E\u0E34\u0E48\u0E07\u0E40\u0E1E\u0E34\u0E48\u0E21", icon: "fluent:clock-24-regular" }
            ], (filter) => {
              _push2(`<button class="${ssrRenderClass([
                "flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all",
                unref(selectedFilter) === filter.key ? "bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30" : "text-gray-400 hover:text-white hover:bg-gray-700"
              ])}" data-v-459cae71${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: filter.icon,
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` ${ssrInterpolate(filter.label)}</button>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4" }, [
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:people-24-filled",
                      class: "w-6 h-6 text-vikinger-cyan"
                    }),
                    createTextVNode(" \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 "),
                    createVNode("span", { class: "text-vikinger-cyan font-normal ml-1" }, "(" + toDisplayString(unref(friends).length) + ")", 1)
                  ]),
                  createVNode("p", { class: "text-sm text-gray-400 mt-1" }, [
                    createVNode("span", { class: "text-green-400" }, toDisplayString(unref(onlineFriendsCount)), 1),
                    createTextVNode(" \u0E04\u0E19\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C ")
                  ])
                ]),
                createVNode("div", { class: "relative w-full md:w-80" }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:search-24-regular",
                    class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                  }),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => isRef(searchQuery) ? searchQuery.value = $event : null,
                    type: "text",
                    placeholder: "\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19...",
                    class: "w-full pl-10 pr-4 py-2.5 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(searchQuery)]
                  ]),
                  unref(isSearching) ? (openBlock(), createBlock(unref(Icon), {
                    key: 0,
                    icon: "fluent:spinner-ios-20-regular",
                    class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 animate-spin"
                  })) : createCommentVNode("", true)
                ])
              ]),
              createVNode("div", { class: "flex gap-2 border-b border-gray-700 pb-3" }, [
                (openBlock(), createBlock(Fragment, null, renderList([
                  { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "fluent:people-24-regular" },
                  { key: "online", label: "\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C", icon: "fluent:presence-available-24-filled" },
                  { key: "recent", label: "\u0E40\u0E1E\u0E34\u0E48\u0E07\u0E40\u0E1E\u0E34\u0E48\u0E21", icon: "fluent:clock-24-regular" }
                ], (filter) => {
                  return createVNode("button", {
                    key: filter.key,
                    onClick: ($event) => selectedFilter.value = filter.key,
                    class: [
                      "flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all",
                      unref(selectedFilter) === filter.key ? "bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30" : "text-gray-400 hover:text-white hover:bg-gray-700"
                    ]
                  }, [
                    createVNode(unref(Icon), {
                      icon: filter.icon,
                      class: "w-4 h-4"
                    }, null, 8, ["icon"]),
                    createTextVNode(" " + toDisplayString(filter.label), 1)
                  ], 10, ["onClick"]);
                }), 64))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(isLoading)) {
        _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" data-v-459cae71><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="animate-pulse" data-v-459cae71>`);
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 p-4" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex items-center gap-4" data-v-459cae71${_scopeId}><div class="w-16 h-16 rounded-full bg-gray-700" data-v-459cae71${_scopeId}></div><div class="flex-1 space-y-2" data-v-459cae71${_scopeId}><div class="h-4 bg-gray-700 rounded w-3/4" data-v-459cae71${_scopeId}></div><div class="h-3 bg-gray-700 rounded w-1/2" data-v-459cae71${_scopeId}></div></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "flex items-center gap-4" }, [
                    createVNode("div", { class: "w-16 h-16 rounded-full bg-gray-700" }),
                    createVNode("div", { class: "flex-1 space-y-2" }, [
                      createVNode("div", { class: "h-4 bg-gray-700 rounded w-3/4" }),
                      createVNode("div", { class: "h-3 bg-gray-700 rounded w-1/2" })
                    ])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(displayedFriends).length > 0) {
        _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" data-v-459cae71><!--[-->`);
        ssrRenderList(unref(displayedFriends), (friend) => {
          _push(ssrRenderComponent(_sfc_main$a, {
            key: friend.id,
            class: "bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all group"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="p-4" data-v-459cae71${_scopeId}><div class="flex items-start gap-4" data-v-459cae71${_scopeId}><div class="relative cursor-pointer" data-v-459cae71${_scopeId}>`);
                _push2(ssrRenderComponent(CircleAvatar, {
                  src: friend.avatar || "/images/default-avatar.png",
                  alt: friend.name,
                  size: "lg",
                  "border-width": 2,
                  "border-color": friend.is_online ? "#22c55e" : "#6b7280",
                  "show-online-status": true,
                  "is-online": friend.is_online
                }, null, _parent2, _scopeId));
                _push2(`<div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-xs font-bold text-white shadow-lg" data-v-459cae71${_scopeId}>${ssrInterpolate(friend.level || 1)}</div></div><div class="flex-1 min-w-0" data-v-459cae71${_scopeId}><h4 class="font-semibold text-white truncate hover:text-vikinger-cyan cursor-pointer transition-colors" data-v-459cae71${_scopeId}>${ssrInterpolate(friend.name || friend.full_name)}</h4><p class="text-sm text-gray-400 truncate" data-v-459cae71${_scopeId}>@${ssrInterpolate(friend.username)}</p>`);
                if (friend.mutual_friends_count) {
                  _push2(`<p class="text-xs text-gray-500 mt-1" data-v-459cae71${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:people-16-regular",
                    class: "w-3 h-3 inline mr-1"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(friend.mutual_friends_count)} \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E23\u0E48\u0E27\u0E21 </p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="flex items-center gap-1 mt-2" data-v-459cae71${_scopeId}><span class="${ssrRenderClass([
                  "w-2 h-2 rounded-full",
                  friend.is_online ? "bg-green-500" : "bg-gray-500"
                ])}" data-v-459cae71${_scopeId}></span><span class="${ssrRenderClass([friend.is_online ? "text-green-400" : "text-gray-500", "text-xs"])}" data-v-459cae71${_scopeId}>${ssrInterpolate(friend.is_online ? "\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C" : "\u0E2D\u0E2D\u0E1F\u0E44\u0E25\u0E19\u0E4C")}</span></div></div><div class="relative" data-v-459cae71${_scopeId}><div class="dropdown dropdown-end" data-v-459cae71${_scopeId}><label tabindex="0" class="btn btn-ghost btn-sm btn-circle" data-v-459cae71${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:more-vertical-24-regular",
                  class: "w-5 h-5 text-gray-400"
                }, null, _parent2, _scopeId));
                _push2(`</label><ul tabindex="0" class="dropdown-content z-20 menu p-2 shadow-lg bg-gray-900 border border-gray-700 rounded-xl w-48" data-v-459cae71${_scopeId}><li data-v-459cae71${_scopeId}><a class="flex items-center gap-2 text-gray-300 hover:text-white hover:bg-gray-700" data-v-459cae71${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:person-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` \u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C </a></li><li data-v-459cae71${_scopeId}><a class="flex items-center gap-2 text-gray-300 hover:text-white hover:bg-gray-700" data-v-459cae71${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chat-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` \u0E2A\u0E48\u0E07\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21 </a></li>`);
                if (__props.isOwnProfile) {
                  _push2(`<li data-v-459cae71${_scopeId}><a class="flex items-center gap-2 text-red-400 hover:text-red-300 hover:bg-red-900/30" data-v-459cae71${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:person-delete-24-regular",
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E40\u0E25\u0E34\u0E01\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 </a></li>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</ul></div></div></div></div><div class="flex border-t border-gray-700" data-v-459cae71${_scopeId}><button class="flex-1 py-3 flex items-center justify-center gap-2 text-gray-400 hover:text-vikinger-cyan hover:bg-gray-700/50 transition-colors" data-v-459cae71${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chat-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(`<span class="text-sm" data-v-459cae71${_scopeId}>\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21</span></button><button class="flex-1 py-3 flex items-center justify-center gap-2 text-gray-400 hover:text-vikinger-purple hover:bg-gray-700/50 transition-colors border-l border-gray-700" data-v-459cae71${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:arrow-right-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(`<span class="text-sm" data-v-459cae71${_scopeId}>\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C</span></button></div>`);
              } else {
                return [
                  createVNode("div", { class: "p-4" }, [
                    createVNode("div", { class: "flex items-start gap-4" }, [
                      createVNode("div", {
                        class: "relative cursor-pointer",
                        onClick: ($event) => goToProfile(friend)
                      }, [
                        createVNode(CircleAvatar, {
                          src: friend.avatar || "/images/default-avatar.png",
                          alt: friend.name,
                          size: "lg",
                          "border-width": 2,
                          "border-color": friend.is_online ? "#22c55e" : "#6b7280",
                          "show-online-status": true,
                          "is-online": friend.is_online
                        }, null, 8, ["src", "alt", "border-color", "is-online"]),
                        createVNode("div", { class: "absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-xs font-bold text-white shadow-lg" }, toDisplayString(friend.level || 1), 1)
                      ], 8, ["onClick"]),
                      createVNode("div", { class: "flex-1 min-w-0" }, [
                        createVNode("h4", {
                          class: "font-semibold text-white truncate hover:text-vikinger-cyan cursor-pointer transition-colors",
                          onClick: ($event) => goToProfile(friend)
                        }, toDisplayString(friend.name || friend.full_name), 9, ["onClick"]),
                        createVNode("p", { class: "text-sm text-gray-400 truncate" }, "@" + toDisplayString(friend.username), 1),
                        friend.mutual_friends_count ? (openBlock(), createBlock("p", {
                          key: 0,
                          class: "text-xs text-gray-500 mt-1"
                        }, [
                          createVNode(unref(Icon), {
                            icon: "fluent:people-16-regular",
                            class: "w-3 h-3 inline mr-1"
                          }),
                          createTextVNode(" " + toDisplayString(friend.mutual_friends_count) + " \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E23\u0E48\u0E27\u0E21 ", 1)
                        ])) : createCommentVNode("", true),
                        createVNode("div", { class: "flex items-center gap-1 mt-2" }, [
                          createVNode("span", {
                            class: [
                              "w-2 h-2 rounded-full",
                              friend.is_online ? "bg-green-500" : "bg-gray-500"
                            ]
                          }, null, 2),
                          createVNode("span", {
                            class: ["text-xs", friend.is_online ? "text-green-400" : "text-gray-500"]
                          }, toDisplayString(friend.is_online ? "\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C" : "\u0E2D\u0E2D\u0E1F\u0E44\u0E25\u0E19\u0E4C"), 3)
                        ])
                      ]),
                      createVNode("div", { class: "relative" }, [
                        createVNode("div", { class: "dropdown dropdown-end" }, [
                          createVNode("label", {
                            tabindex: "0",
                            class: "btn btn-ghost btn-sm btn-circle"
                          }, [
                            createVNode(unref(Icon), {
                              icon: "fluent:more-vertical-24-regular",
                              class: "w-5 h-5 text-gray-400"
                            })
                          ]),
                          createVNode("ul", {
                            tabindex: "0",
                            class: "dropdown-content z-20 menu p-2 shadow-lg bg-gray-900 border border-gray-700 rounded-xl w-48"
                          }, [
                            createVNode("li", null, [
                              createVNode("a", {
                                onClick: ($event) => goToProfile(friend),
                                class: "flex items-center gap-2 text-gray-300 hover:text-white hover:bg-gray-700"
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "fluent:person-24-regular",
                                  class: "w-4 h-4"
                                }),
                                createTextVNode(" \u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
                              ], 8, ["onClick"])
                            ]),
                            createVNode("li", null, [
                              createVNode("a", {
                                onClick: ($event) => startChat(friend),
                                class: "flex items-center gap-2 text-gray-300 hover:text-white hover:bg-gray-700"
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "fluent:chat-24-regular",
                                  class: "w-4 h-4"
                                }),
                                createTextVNode(" \u0E2A\u0E48\u0E07\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21 ")
                              ], 8, ["onClick"])
                            ]),
                            __props.isOwnProfile ? (openBlock(), createBlock("li", { key: 0 }, [
                              createVNode("a", {
                                onClick: ($event) => confirmUnfriend(friend),
                                class: "flex items-center gap-2 text-red-400 hover:text-red-300 hover:bg-red-900/30"
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "fluent:person-delete-24-regular",
                                  class: "w-4 h-4"
                                }),
                                createTextVNode(" \u0E40\u0E25\u0E34\u0E01\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 ")
                              ], 8, ["onClick"])
                            ])) : createCommentVNode("", true)
                          ])
                        ])
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "flex border-t border-gray-700" }, [
                    createVNode("button", {
                      onClick: ($event) => startChat(friend),
                      class: "flex-1 py-3 flex items-center justify-center gap-2 text-gray-400 hover:text-vikinger-cyan hover:bg-gray-700/50 transition-colors"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:chat-24-regular",
                        class: "w-4 h-4"
                      }),
                      createVNode("span", { class: "text-sm" }, "\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21")
                    ], 8, ["onClick"]),
                    createVNode("button", {
                      onClick: ($event) => goToProfile(friend),
                      class: "flex-1 py-3 flex items-center justify-center gap-2 text-gray-400 hover:text-vikinger-purple hover:bg-gray-700/50 transition-colors border-l border-gray-700"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:arrow-right-24-regular",
                        class: "w-4 h-4"
                      }),
                      createVNode("span", { class: "text-sm" }, "\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C")
                    ], 8, ["onClick"])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: unref(searchQuery) ? "fluent:search-24-regular" : "fluent:people-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400" data-v-459cae71${_scopeId}>${ssrInterpolate(unref(searchQuery) ? "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E04\u0E49\u0E19\u0E2B\u0E32" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19")}</p>`);
              if (!unref(searchQuery) && __props.isOwnProfile) {
                _push2(`<p class="text-sm text-gray-500 mt-2" data-v-459cae71${_scopeId}> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E0A\u0E37\u0E48\u0E2D\u0E21\u0E15\u0E48\u0E2D\u0E01\u0E31\u0E1A\u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19\u0E46! </p>`);
              } else {
                _push2(`<!---->`);
              }
              if (!unref(searchQuery) && __props.isOwnProfile) {
                _push2(ssrRenderComponent(_component_NuxtLink, {
                  to: "/members",
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(ssrRenderComponent(unref(Icon), {
                        icon: "fluent:person-add-24-regular",
                        class: "w-5 h-5"
                      }, null, _parent3, _scopeId2));
                      _push3(` \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 `);
                    } else {
                      return [
                        createVNode(unref(Icon), {
                          icon: "fluent:person-add-24-regular",
                          class: "w-5 h-5"
                        }),
                        createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 ")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: unref(searchQuery) ? "fluent:search-24-regular" : "fluent:people-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }, null, 8, ["icon"]),
                createVNode("p", { class: "text-gray-400" }, toDisplayString(unref(searchQuery) ? "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E04\u0E49\u0E19\u0E2B\u0E32" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19"), 1),
                !unref(searchQuery) && __props.isOwnProfile ? (openBlock(), createBlock("p", {
                  key: 0,
                  class: "text-sm text-gray-500 mt-2"
                }, " \u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E0A\u0E37\u0E48\u0E2D\u0E21\u0E15\u0E48\u0E2D\u0E01\u0E31\u0E1A\u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19\u0E46! ")) : createCommentVNode("", true),
                !unref(searchQuery) && __props.isOwnProfile ? (openBlock(), createBlock(_component_NuxtLink, {
                  key: 1,
                  to: "/members",
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, {
                  default: withCtx(() => [
                    createVNode(unref(Icon), {
                      icon: "fluent:person-add-24-regular",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true)
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      if (unref(hasMore) && !unref(searchQuery)) {
        _push(`<div class="text-center" data-v-459cae71><button${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""} class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto" data-v-459cae71>`);
        if (unref(isLoading)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:spinner-ios-20-regular",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<span data-v-459cae71>${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14..." : "\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</span></button></div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (unref(showConfirmModal)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-v-459cae71><div class="absolute inset-0 bg-black/70" data-v-459cae71></div><div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-md border border-gray-700" data-v-459cae71><div class="p-6 text-center" data-v-459cae71><div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-500/20 flex items-center justify-center" data-v-459cae71>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-delete-24-filled",
            class: "w-8 h-8 text-red-500"
          }, null, _parent));
          _push2(`</div><h3 class="text-xl font-bold text-white mb-2" data-v-459cae71>\u0E40\u0E25\u0E34\u0E01\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19?</h3><p class="text-gray-400 mb-6" data-v-459cae71> \u0E04\u0E38\u0E13\u0E41\u0E19\u0E48\u0E43\u0E08\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48\u0E27\u0E48\u0E32\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E25\u0E34\u0E01\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E01\u0E31\u0E1A <span class="text-white font-medium" data-v-459cae71>${ssrInterpolate((_a = unref(friendToRemove)) == null ? void 0 : _a.name)}</span>? </p><div class="flex gap-3 justify-center" data-v-459cae71><button class="px-6 py-2.5 text-gray-400 hover:text-white bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors" data-v-459cae71> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-6 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors" data-v-459cae71> \u0E40\u0E25\u0E34\u0E01\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 </button></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/FriendsList.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const FriendsList = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["__scopeId", "data-v-459cae71"]]);
const _sfc_main$7 = /* @__PURE__ */ defineComponent({
  __name: "PhotosGallery",
  __ssrInlineRender: true,
  props: {
    userId: {},
    isOwnProfile: { type: Boolean }
  },
  setup(__props) {
    const api = useApi();
    const toast = useToast();
    const photos = ref([]);
    const isLoading = ref(false);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const hasMore = computed(() => currentPage.value < lastPage.value);
    const selectedPhoto = ref(null);
    const showLightbox = ref(false);
    const isUploading = ref(false);
    const uploadProgress = ref(0);
    const fileInput = ref(null);
    const handleFileSelect = async (event) => {
      const input = event.target;
      const files = input.files;
      if (!files || files.length === 0) return;
      isUploading.value = true;
      uploadProgress.value = 0;
      const formData = new FormData();
      Array.from(files).forEach((file, index) => {
        formData.append(`photos[${index}]`, file);
      });
      try {
        const response = await api.post("/api/profile/photos", formData, {
          onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round(progressEvent.loaded * 100 / progressEvent.total);
          }
        });
        if (response.success && response.photos) {
          photos.value = [...response.photos, ...photos.value];
          toast.success("\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08");
        }
      } catch (error) {
        toast.error(error.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E44\u0E14\u0E49");
      } finally {
        isUploading.value = false;
        uploadProgress.value = 0;
        if (fileInput.value) fileInput.value.value = "";
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-3a95aed5>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 p-5" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center justify-between" data-v-3a95aed5${_scopeId}><div data-v-3a95aed5${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2" data-v-3a95aed5${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:image-24-filled",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E <span class="text-vikinger-cyan font-normal ml-1" data-v-3a95aed5${_scopeId}>(${ssrInterpolate(unref(photos).length)})</span></h3><p class="text-sm text-gray-400 mt-1" data-v-3a95aed5${_scopeId}>\u0E04\u0E25\u0E31\u0E07\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p></div>`);
            if (__props.isOwnProfile) {
              _push2(`<button${ssrIncludeBooleanAttr(unref(isUploading)) ? " disabled" : ""} class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2 disabled:opacity-50" data-v-3a95aed5${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: unref(isUploading) ? "fluent:spinner-ios-20-regular" : "fluent:arrow-upload-24-regular",
                class: ["w-5 h-5", unref(isUploading) && "animate-spin"]
              }, null, _parent2, _scopeId));
              _push2(` ${ssrInterpolate(unref(isUploading) ? `\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14 ${unref(uploadProgress)}%` : "\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B")}</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<input type="file" accept="image/*" multiple class="hidden" data-v-3a95aed5${_scopeId}></div>`);
            if (unref(isUploading)) {
              _push2(`<div class="mt-4" data-v-3a95aed5${_scopeId}><div class="h-2 bg-gray-700 rounded-full overflow-hidden" data-v-3a95aed5${_scopeId}><div class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all duration-300" style="${ssrRenderStyle({ width: unref(uploadProgress) + "%" })}" data-v-3a95aed5${_scopeId}></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", { class: "flex items-center justify-between" }, [
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:image-24-filled",
                      class: "w-6 h-6 text-vikinger-cyan"
                    }),
                    createTextVNode(" \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E "),
                    createVNode("span", { class: "text-vikinger-cyan font-normal ml-1" }, "(" + toDisplayString(unref(photos).length) + ")", 1)
                  ]),
                  createVNode("p", { class: "text-sm text-gray-400 mt-1" }, "\u0E04\u0E25\u0E31\u0E07\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14")
                ]),
                __props.isOwnProfile ? (openBlock(), createBlock("button", {
                  key: 0,
                  onClick: ($event) => {
                    var _a;
                    return (_a = unref(fileInput)) == null ? void 0 : _a.click();
                  },
                  disabled: unref(isUploading),
                  class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2 disabled:opacity-50"
                }, [
                  createVNode(unref(Icon), {
                    icon: unref(isUploading) ? "fluent:spinner-ios-20-regular" : "fluent:arrow-upload-24-regular",
                    class: ["w-5 h-5", unref(isUploading) && "animate-spin"]
                  }, null, 8, ["icon", "class"]),
                  createTextVNode(" " + toDisplayString(unref(isUploading) ? `\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14 ${unref(uploadProgress)}%` : "\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B"), 1)
                ], 8, ["onClick", "disabled"])) : createCommentVNode("", true),
                createVNode("input", {
                  ref_key: "fileInput",
                  ref: fileInput,
                  type: "file",
                  accept: "image/*",
                  multiple: "",
                  class: "hidden",
                  onChange: handleFileSelect
                }, null, 544)
              ]),
              unref(isUploading) ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mt-4"
              }, [
                createVNode("div", { class: "h-2 bg-gray-700 rounded-full overflow-hidden" }, [
                  createVNode("div", {
                    class: "h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all duration-300",
                    style: { width: unref(uploadProgress) + "%" }
                  }, null, 4)
                ])
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(isLoading) && unref(photos).length === 0) {
        _push(`<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" data-v-3a95aed5><!--[-->`);
        ssrRenderList(8, (i) => {
          _push(`<div class="aspect-square bg-gray-800 rounded-lg animate-pulse" data-v-3a95aed5></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(photos).length > 0) {
        _push(`<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" data-v-3a95aed5><!--[-->`);
        ssrRenderList(unref(photos), (photo) => {
          _push(`<div class="aspect-square bg-gray-800 rounded-lg overflow-hidden relative group cursor-pointer" data-v-3a95aed5><img${ssrRenderAttr("src", photo.thumbnail_url || photo.url)}${ssrRenderAttr("alt", photo.caption || "Photo")} class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" loading="lazy" data-v-3a95aed5><div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4" data-v-3a95aed5><div class="flex items-center gap-1 text-white" data-v-3a95aed5>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:heart-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="font-medium" data-v-3a95aed5>${ssrInterpolate(photo.likes_count)}</span></div><div class="flex items-center gap-1 text-white" data-v-3a95aed5>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chat-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="font-medium" data-v-3a95aed5>${ssrInterpolate(photo.comments_count)}</span></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:image-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400" data-v-3a95aed5${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</p>`);
              if (__props.isOwnProfile) {
                _push2(`<p class="text-sm text-gray-500 mt-2" data-v-3a95aed5${_scopeId}> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E0A\u0E23\u0E4C\u0E0A\u0E48\u0E27\u0E07\u0E40\u0E27\u0E25\u0E32\u0E14\u0E35\u0E46! </p>`);
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:image-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }),
                createVNode("p", { class: "text-gray-400" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"),
                __props.isOwnProfile ? (openBlock(), createBlock("p", {
                  key: 0,
                  class: "text-sm text-gray-500 mt-2"
                }, " \u0E40\u0E23\u0E34\u0E48\u0E21\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E0A\u0E23\u0E4C\u0E0A\u0E48\u0E27\u0E07\u0E40\u0E27\u0E25\u0E32\u0E14\u0E35\u0E46! ")) : createCommentVNode("", true)
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      if (unref(hasMore)) {
        _push(`<div class="text-center" data-v-3a95aed5><button${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""} class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto" data-v-3a95aed5>`);
        if (unref(isLoading)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:spinner-ios-20-regular",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<span data-v-3a95aed5>${ssrInterpolate(unref(isLoading) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14..." : "\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</span></button></div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(showLightbox) && unref(selectedPhoto)) {
          _push2(`<div class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center" data-v-3a95aed5><button class="absolute top-4 right-4 z-10 p-3 bg-gray-800/50 rounded-full text-white hover:bg-gray-700 transition-colors" data-v-3a95aed5>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-6 h-6"
          }, null, _parent));
          _push2(`</button>`);
          if (unref(photos).length > 1) {
            _push2(`<button class="absolute left-4 z-10 p-3 bg-gray-800/50 rounded-full text-white hover:bg-gray-700 transition-colors" data-v-3a95aed5>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:chevron-left-24-regular",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`</button>`);
          } else {
            _push2(`<!---->`);
          }
          if (unref(photos).length > 1) {
            _push2(`<button class="absolute right-4 z-10 p-3 bg-gray-800/50 rounded-full text-white hover:bg-gray-700 transition-colors" data-v-3a95aed5>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:chevron-right-24-regular",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`</button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="max-w-5xl max-h-[85vh] mx-4" data-v-3a95aed5><img${ssrRenderAttr("src", unref(selectedPhoto).url)}${ssrRenderAttr("alt", unref(selectedPhoto).caption || "Photo")} class="max-w-full max-h-[85vh] object-contain rounded-lg" data-v-3a95aed5></div><div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent" data-v-3a95aed5><div class="max-w-5xl mx-auto flex items-center justify-between" data-v-3a95aed5><div data-v-3a95aed5>`);
          if (unref(selectedPhoto).caption) {
            _push2(`<p class="text-white mb-2" data-v-3a95aed5>${ssrInterpolate(unref(selectedPhoto).caption)}</p>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<p class="text-gray-400 text-sm" data-v-3a95aed5>${ssrInterpolate(new Date(unref(selectedPhoto).created_at).toLocaleDateString("th-TH", { year: "numeric", month: "long", day: "numeric" }))}</p></div><div class="flex items-center gap-4" data-v-3a95aed5><button class="${ssrRenderClass([
            "flex items-center gap-2 px-4 py-2 rounded-lg transition-colors",
            unref(selectedPhoto).is_liked ? "bg-pink-500/20 text-pink-400" : "bg-gray-800 text-white hover:bg-gray-700"
          ])}" data-v-3a95aed5>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: unref(selectedPhoto).is_liked ? "fluent:heart-24-filled" : "fluent:heart-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(` ${ssrInterpolate(unref(selectedPhoto).likes_count)}</button>`);
          if (__props.isOwnProfile) {
            _push2(`<button class="flex items-center gap-2 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors" data-v-3a95aed5>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:delete-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E25\u0E1A </button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/PhotosGallery.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const PhotosGallery = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["__scopeId", "data-v-3a95aed5"]]);
const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "BadgesDisplay",
  __ssrInlineRender: true,
  props: {
    userId: {},
    isOwnProfile: { type: Boolean }
  },
  setup(__props) {
    useApi();
    const badges = ref([]);
    const isLoading = ref(false);
    const selectedCategory = ref("all");
    const selectedBadge = ref(null);
    const showBadgeModal = ref(false);
    const categories = computed(() => {
      const counts = {
        all: badges.value.filter((b) => b.is_earned).length,
        achievement: 0,
        social: 0,
        activity: 0,
        special: 0,
        milestone: 0
      };
      badges.value.forEach((badge) => {
        if (badge.is_earned) {
          counts[badge.category]++;
        }
      });
      return [
        { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "fluent:grid-24-regular", count: counts.all },
        { key: "achievement", label: "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", icon: "fluent:trophy-24-regular", count: counts.achievement },
        { key: "social", label: "\u0E2A\u0E31\u0E07\u0E04\u0E21", icon: "fluent:people-24-regular", count: counts.social },
        { key: "activity", label: "\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21", icon: "fluent:calendar-24-regular", count: counts.activity },
        { key: "milestone", label: "\u0E2B\u0E25\u0E31\u0E01\u0E44\u0E21\u0E25\u0E4C", icon: "fluent:flag-24-regular", count: counts.milestone },
        { key: "special", label: "\u0E1E\u0E34\u0E40\u0E28\u0E29", icon: "fluent:star-24-regular", count: counts.special }
      ];
    });
    const filteredBadges = computed(() => {
      if (selectedCategory.value === "all") {
        return badges.value;
      }
      return badges.value.filter((b) => b.category === selectedCategory.value);
    });
    const earnedBadges = computed(() => filteredBadges.value.filter((b) => b.is_earned));
    const lockedBadges = computed(() => filteredBadges.value.filter((b) => !b.is_earned));
    const totalPoints = computed(() => {
      return badges.value.filter((b) => b.is_earned).reduce((sum, b) => sum + b.points, 0);
    });
    const rarityColors = {
      common: { bg: "bg-gray-600", border: "border-gray-500", text: "text-gray-400", glow: "shadow-gray-500/20" },
      uncommon: { bg: "bg-green-600", border: "border-green-500", text: "text-green-400", glow: "shadow-green-500/30" },
      rare: { bg: "bg-blue-600", border: "border-blue-500", text: "text-blue-400", glow: "shadow-blue-500/30" },
      epic: { bg: "bg-purple-600", border: "border-purple-500", text: "text-purple-400", glow: "shadow-purple-500/40" },
      legendary: { bg: "bg-amber-600", border: "border-amber-500", text: "text-amber-400", glow: "shadow-amber-500/50" }
    };
    const rarityLabels = {
      common: "\u0E18\u0E23\u0E23\u0E21\u0E14\u0E32",
      uncommon: "\u0E44\u0E21\u0E48\u0E18\u0E23\u0E23\u0E21\u0E14\u0E32",
      rare: "\u0E2B\u0E32\u0E22\u0E32\u0E01",
      epic: "\u0E21\u0E2B\u0E32\u0E01\u0E32\u0E1E\u0E22\u0E4C",
      legendary: "\u0E15\u0E33\u0E19\u0E32\u0E19"
    };
    const formatDate2 = (date) => {
      return new Date(date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-8b6431c7>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 overflow-hidden" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6" data-v-8b6431c7${_scopeId}><div class="flex flex-col md:flex-row md:items-center justify-between gap-4" data-v-8b6431c7${_scopeId}><div data-v-8b6431c7${_scopeId}><h3 class="text-2xl font-bold text-white flex items-center gap-3" data-v-8b6431c7${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:ribbon-star-24-filled",
              class: "w-8 h-8"
            }, null, _parent2, _scopeId));
            _push2(` \u0E15\u0E23\u0E32\u0E2A\u0E31\u0E0D\u0E25\u0E31\u0E01\u0E29\u0E13\u0E4C &amp; \u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08 </h3><p class="text-white/80 mt-1" data-v-8b6431c7${_scopeId}>\u0E23\u0E27\u0E1A\u0E23\u0E27\u0E21\u0E15\u0E23\u0E32\u0E2A\u0E31\u0E0D\u0E25\u0E31\u0E01\u0E29\u0E13\u0E4C\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1B\u0E25\u0E14\u0E25\u0E47\u0E2D\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E1E\u0E34\u0E40\u0E28\u0E29!</p></div><div class="flex items-center gap-6" data-v-8b6431c7${_scopeId}><div class="text-center" data-v-8b6431c7${_scopeId}><p class="text-3xl font-black text-white" data-v-8b6431c7${_scopeId}>${ssrInterpolate(unref(earnedBadges).length)}</p><p class="text-white/70 text-sm" data-v-8b6431c7${_scopeId}>\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27</p></div><div class="w-px h-12 bg-white/20" data-v-8b6431c7${_scopeId}></div><div class="text-center" data-v-8b6431c7${_scopeId}><p class="text-3xl font-black text-white" data-v-8b6431c7${_scopeId}>${ssrInterpolate(unref(totalPoints))}</p><p class="text-white/70 text-sm" data-v-8b6431c7${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E23\u0E27\u0E21</p></div></div></div></div><div class="p-4 flex gap-2 overflow-x-auto scrollbar-hide" data-v-8b6431c7${_scopeId}><!--[-->`);
            ssrRenderList(unref(categories), (category) => {
              _push2(`<button class="${ssrRenderClass([
                "flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap",
                unref(selectedCategory) === category.key ? "bg-vikinger-purple text-white" : "text-gray-400 hover:text-white hover:bg-gray-700"
              ])}" data-v-8b6431c7${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: category.icon,
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` ${ssrInterpolate(category.label)} `);
              if (category.count > 0) {
                _push2(`<span class="${ssrRenderClass([
                  "px-1.5 py-0.5 text-xs rounded-full",
                  unref(selectedCategory) === category.key ? "bg-white/20 text-white" : "bg-gray-700 text-gray-300"
                ])}" data-v-8b6431c7${_scopeId}>${ssrInterpolate(category.count)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</button>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-6" }, [
                createVNode("div", { class: "flex flex-col md:flex-row md:items-center justify-between gap-4" }, [
                  createVNode("div", null, [
                    createVNode("h3", { class: "text-2xl font-bold text-white flex items-center gap-3" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:ribbon-star-24-filled",
                        class: "w-8 h-8"
                      }),
                      createTextVNode(" \u0E15\u0E23\u0E32\u0E2A\u0E31\u0E0D\u0E25\u0E31\u0E01\u0E29\u0E13\u0E4C & \u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08 ")
                    ]),
                    createVNode("p", { class: "text-white/80 mt-1" }, "\u0E23\u0E27\u0E1A\u0E23\u0E27\u0E21\u0E15\u0E23\u0E32\u0E2A\u0E31\u0E0D\u0E25\u0E31\u0E01\u0E29\u0E13\u0E4C\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1B\u0E25\u0E14\u0E25\u0E47\u0E2D\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E1E\u0E34\u0E40\u0E28\u0E29!")
                  ]),
                  createVNode("div", { class: "flex items-center gap-6" }, [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("p", { class: "text-3xl font-black text-white" }, toDisplayString(unref(earnedBadges).length), 1),
                      createVNode("p", { class: "text-white/70 text-sm" }, "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27")
                    ]),
                    createVNode("div", { class: "w-px h-12 bg-white/20" }),
                    createVNode("div", { class: "text-center" }, [
                      createVNode("p", { class: "text-3xl font-black text-white" }, toDisplayString(unref(totalPoints)), 1),
                      createVNode("p", { class: "text-white/70 text-sm" }, "\u0E41\u0E15\u0E49\u0E21\u0E23\u0E27\u0E21")
                    ])
                  ])
                ])
              ]),
              createVNode("div", { class: "p-4 flex gap-2 overflow-x-auto scrollbar-hide" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(categories), (category) => {
                  return openBlock(), createBlock("button", {
                    key: category.key,
                    onClick: ($event) => selectedCategory.value = category.key,
                    class: [
                      "flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap",
                      unref(selectedCategory) === category.key ? "bg-vikinger-purple text-white" : "text-gray-400 hover:text-white hover:bg-gray-700"
                    ]
                  }, [
                    createVNode(unref(Icon), {
                      icon: category.icon,
                      class: "w-4 h-4"
                    }, null, 8, ["icon"]),
                    createTextVNode(" " + toDisplayString(category.label) + " ", 1),
                    category.count > 0 ? (openBlock(), createBlock("span", {
                      key: 0,
                      class: [
                        "px-1.5 py-0.5 text-xs rounded-full",
                        unref(selectedCategory) === category.key ? "bg-white/20 text-white" : "bg-gray-700 text-gray-300"
                      ]
                    }, toDisplayString(category.count), 3)) : createCommentVNode("", true)
                  ], 10, ["onClick"]);
                }), 128))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(isLoading)) {
        _push(`<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" data-v-8b6431c7><!--[-->`);
        ssrRenderList(10, (i) => {
          _push(`<div class="animate-pulse" data-v-8b6431c7><div class="aspect-square bg-gray-800 rounded-xl" data-v-8b6431c7></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(filteredBadges).length > 0) {
        _push(`<div data-v-8b6431c7>`);
        if (unref(earnedBadges).length > 0) {
          _push(`<div data-v-8b6431c7><h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2" data-v-8b6431c7>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: "w-5 h-5 text-green-500"
          }, null, _parent));
          _push(` \u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27 (${ssrInterpolate(unref(earnedBadges).length)}) </h4><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-8" data-v-8b6431c7><!--[-->`);
          ssrRenderList(unref(earnedBadges), (badge) => {
            _push(`<div class="${ssrRenderClass([
              "relative aspect-square rounded-xl cursor-pointer transition-all duration-300 hover:scale-105 overflow-hidden",
              "border-2 shadow-lg",
              rarityColors[badge.rarity].border,
              rarityColors[badge.rarity].glow
            ])}" style="${ssrRenderStyle({ backgroundColor: badge.background_color })}" data-v-8b6431c7><div class="${ssrRenderClass([
              "absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-medium",
              rarityColors[badge.rarity].bg,
              "text-white"
            ])}" data-v-8b6431c7>${ssrInterpolate(rarityLabels[badge.rarity])}</div><div class="absolute inset-0 flex items-center justify-center" data-v-8b6431c7>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: badge.icon,
              class: "w-16 h-16",
              style: { color: badge.icon_color }
            }, null, _parent));
            _push(`</div><div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent" data-v-8b6431c7><p class="text-white text-sm font-medium text-center truncate" data-v-8b6431c7>${ssrInterpolate(badge.name)}</p><p class="text-center text-xs text-amber-400 flex items-center justify-center gap-1" data-v-8b6431c7>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:star-12-filled",
              class: "w-3 h-3"
            }, null, _parent));
            _push(` +${ssrInterpolate(badge.points)}</p></div><div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-transparent opacity-0 hover:opacity-100 transition-opacity" data-v-8b6431c7></div></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(lockedBadges).length > 0) {
          _push(`<div data-v-8b6431c7><h4 class="text-lg font-semibold text-white mb-4 flex items-center gap-2" data-v-8b6431c7>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:lock-closed-24-filled",
            class: "w-5 h-5 text-gray-500"
          }, null, _parent));
          _push(` \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A (${ssrInterpolate(unref(lockedBadges).length)}) </h4><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" data-v-8b6431c7><!--[-->`);
          ssrRenderList(unref(lockedBadges), (badge) => {
            _push(`<div class="relative aspect-square bg-gray-800/50 rounded-xl cursor-pointer transition-all duration-300 hover:bg-gray-800 border-2 border-gray-700 border-dashed overflow-hidden" data-v-8b6431c7><div class="absolute inset-0 flex items-center justify-center opacity-30" data-v-8b6431c7>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: badge.icon,
              class: "w-16 h-16 text-gray-500"
            }, null, _parent));
            _push(`</div><div class="absolute inset-0 flex items-center justify-center" data-v-8b6431c7><div class="w-12 h-12 rounded-full bg-gray-700 flex items-center justify-center" data-v-8b6431c7>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:lock-closed-24-filled",
              class: "w-6 h-6 text-gray-500"
            }, null, _parent));
            _push(`</div></div>`);
            if (badge.progress !== void 0 && badge.max_progress) {
              _push(`<div class="absolute bottom-0 left-0 right-0 p-3" data-v-8b6431c7><div class="h-1.5 bg-gray-700 rounded-full overflow-hidden" data-v-8b6431c7><div class="h-full bg-vikinger-purple rounded-full" style="${ssrRenderStyle({ width: badge.progress / badge.max_progress * 100 + "%" })}" data-v-8b6431c7></div></div><p class="text-xs text-gray-500 text-center mt-1" data-v-8b6431c7>${ssrInterpolate(badge.progress)}/${ssrInterpolate(badge.max_progress)}</p></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<p class="absolute top-3 left-0 right-0 text-gray-500 text-xs text-center truncate px-2" data-v-8b6431c7>${ssrInterpolate(badge.name)}</p></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:ribbon-star-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400" data-v-8b6431c7${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E15\u0E23\u0E32\u0E2A\u0E31\u0E0D\u0E25\u0E31\u0E01\u0E29\u0E13\u0E4C\u0E43\u0E19\u0E2B\u0E21\u0E27\u0E14\u0E19\u0E35\u0E49</p>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:ribbon-star-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }),
                createVNode("p", { class: "text-gray-400" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E15\u0E23\u0E32\u0E2A\u0E31\u0E0D\u0E25\u0E31\u0E01\u0E29\u0E13\u0E4C\u0E43\u0E19\u0E2B\u0E21\u0E27\u0E14\u0E19\u0E35\u0E49")
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(showBadgeModal) && unref(selectedBadge)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-v-8b6431c7><div class="absolute inset-0 bg-black/80 backdrop-blur-sm" data-v-8b6431c7></div><div class="${ssrRenderClass([rarityColors[unref(selectedBadge).rarity].border, "relative bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border-2"])}" data-v-8b6431c7><div class="relative h-48 flex items-center justify-center" style="${ssrRenderStyle({ backgroundColor: unref(selectedBadge).background_color })}" data-v-8b6431c7><div class="absolute inset-0 overflow-hidden" data-v-8b6431c7><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full border border-white/10" data-v-8b6431c7></div><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 rounded-full border border-white/10" data-v-8b6431c7></div><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 rounded-full border border-white/10" data-v-8b6431c7></div></div><div class="relative z-10" data-v-8b6431c7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: unref(selectedBadge).icon,
            class: "w-24 h-24",
            style: { color: unref(selectedBadge).icon_color }
          }, null, _parent));
          if (unref(selectedBadge).is_earned) {
            _push2(`<div class="absolute inset-0 blur-xl opacity-50" style="${ssrRenderStyle({ backgroundColor: unref(selectedBadge).icon_color })}" data-v-8b6431c7></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div>`);
          if (!unref(selectedBadge).is_earned) {
            _push2(`<div class="absolute inset-0 bg-black/60 flex items-center justify-center" data-v-8b6431c7><div class="w-20 h-20 rounded-full bg-gray-800 flex items-center justify-center" data-v-8b6431c7>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:lock-closed-24-filled",
              class: "w-10 h-10 text-gray-500"
            }, null, _parent));
            _push2(`</div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<button class="absolute top-4 right-4 p-2 bg-black/30 rounded-full text-white hover:bg-black/50 transition-colors" data-v-8b6431c7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`</button><div class="${ssrRenderClass([
            "absolute top-4 left-4 px-3 py-1 rounded-full text-sm font-medium",
            rarityColors[unref(selectedBadge).rarity].bg,
            "text-white"
          ])}" data-v-8b6431c7>${ssrInterpolate(rarityLabels[unref(selectedBadge).rarity])}</div></div><div class="p-6" data-v-8b6431c7><h3 class="text-2xl font-bold text-white text-center mb-2" data-v-8b6431c7>${ssrInterpolate(unref(selectedBadge).name)}</h3><p class="text-gray-400 text-center mb-6" data-v-8b6431c7>${ssrInterpolate(unref(selectedBadge).description)}</p><div class="grid grid-cols-2 gap-4 mb-6" data-v-8b6431c7><div class="bg-gray-800 rounded-xl p-4 text-center" data-v-8b6431c7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:star-24-filled",
            class: "w-6 h-6 text-amber-400 mx-auto mb-1"
          }, null, _parent));
          _push2(`<p class="text-xl font-bold text-white" data-v-8b6431c7>+${ssrInterpolate(unref(selectedBadge).points)}</p><p class="text-xs text-gray-500" data-v-8b6431c7>\u0E41\u0E15\u0E49\u0E21</p></div><div class="bg-gray-800 rounded-xl p-4 text-center" data-v-8b6431c7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: unref(selectedBadge).is_earned ? "fluent:checkmark-circle-24-filled" : "fluent:hourglass-24-regular",
            class: ["w-6 h-6 mx-auto mb-1", unref(selectedBadge).is_earned ? "text-green-500" : "text-gray-500"]
          }, null, _parent));
          _push2(`<p class="text-xl font-bold text-white" data-v-8b6431c7>${ssrInterpolate(unref(selectedBadge).is_earned ? "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27" : "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23")}</p>`);
          if (unref(selectedBadge).earned_at) {
            _push2(`<p class="text-xs text-gray-500" data-v-8b6431c7>${ssrInterpolate(formatDate2(unref(selectedBadge).earned_at))}</p>`);
          } else if (unref(selectedBadge).progress !== void 0) {
            _push2(`<p class="text-xs text-gray-500" data-v-8b6431c7>${ssrInterpolate(unref(selectedBadge).progress)}/${ssrInterpolate(unref(selectedBadge).max_progress)}</p>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div>`);
          if (!unref(selectedBadge).is_earned && unref(selectedBadge).progress !== void 0) {
            _push2(`<div class="mb-6" data-v-8b6431c7><div class="flex justify-between text-sm mb-2" data-v-8b6431c7><span class="text-gray-400" data-v-8b6431c7>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32</span><span class="text-vikinger-cyan font-medium" data-v-8b6431c7>${ssrInterpolate(Math.round(unref(selectedBadge).progress / unref(selectedBadge).max_progress * 100))}% </span></div><div class="h-3 bg-gray-800 rounded-full overflow-hidden" data-v-8b6431c7><div class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: unref(selectedBadge).progress / unref(selectedBadge).max_progress * 100 + "%" })}" data-v-8b6431c7></div></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<button class="w-full py-3 bg-vikinger-purple text-white rounded-xl font-medium hover:bg-vikinger-purple/80 transition-colors" data-v-8b6431c7>${ssrInterpolate(unref(selectedBadge).is_earned ? "\u0E22\u0E2D\u0E14\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21! \u{1F389}" : "\u0E2A\u0E39\u0E49\u0E15\u0E48\u0E2D\u0E44\u0E1B! \u{1F4AA}")}</button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/BadgesDisplay.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const BadgesDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["__scopeId", "data-v-8b6431c7"]]);
const useDateFormatter = () => {
  return {
    toInput: formatDateForInput,
    toThai: formatDateThai,
    toTimeAgo: formatTimeAgo,
    format: formatDate,
    validate: isValidDate,
    getToday: getCurrentDate,
    getTodayThai: getCurrentDateThai,
    formatRelative: formatTimeAgo
  };
};
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "FriendRequestsWidget",
  __ssrInlineRender: true,
  setup(__props) {
    const {
      friendRequests,
      suggestions,
      isLoadingRequests,
      isLoadingSuggestions
    } = useFriends();
    useToast();
    const activeTab = ref("requests");
    const processingIds = ref(/* @__PURE__ */ new Set());
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "vikinger-card vikinger-card-hover overflow-hidden !p-0" }, _attrs))}><div class="relative bg-gradient-to-r from-vikinger-purple to-vikinger-cyan px-3 py-2.5"><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-10"></div><h3 class="relative text-sm font-bold text-white flex items-center gap-2"><div class="w-6 h-6 rounded-md bg-white/20 flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:people-add-24-filled",
        class: "w-3.5 h-3.5"
      }, null, _parent));
      _push(`</div> \u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 `);
      if (unref(friendRequests).length > 0) {
        _push(`<span class="ml-auto px-2 py-0.5 text-[10px] bg-white/20 text-white rounded-full font-bold">${ssrInterpolate(unref(friendRequests).length)} \u0E43\u0E2B\u0E21\u0E48 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</h3></div><div class="flex border-b border-gray-200 dark:border-gray-700/50"><button class="${ssrRenderClass([
        "flex-1 py-2 text-xs font-bold transition-all relative flex items-center justify-center gap-1.5",
        unref(activeTab) === "requests" ? "text-vikinger-purple dark:text-vikinger-cyan bg-vikinger-purple/5 dark:bg-vikinger-cyan/10" : "text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50"
      ])}"> \u0E04\u0E33\u0E02\u0E2D\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A `);
      if (unref(friendRequests).length > 0) {
        _push(`<span class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded-full font-bold">${ssrInterpolate(unref(friendRequests).length)}</span>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(activeTab) === "requests") {
        _push(`<div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan"></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</button><button class="${ssrRenderClass([
        "flex-1 py-2 text-xs font-bold transition-all relative",
        unref(activeTab) === "suggestions" ? "text-vikinger-purple dark:text-vikinger-cyan bg-vikinger-purple/5 dark:bg-vikinger-cyan/10" : "text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50"
      ])}"> \u0E41\u0E19\u0E30\u0E19\u0E33 `);
      if (unref(activeTab) === "suggestions") {
        _push(`<div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan"></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</button></div><div class="max-h-72 overflow-y-auto">`);
      if (unref(activeTab) === "requests") {
        _push(`<!--[-->`);
        if (unref(isLoadingRequests)) {
          _push(`<div class="p-3 space-y-2"><!--[-->`);
          ssrRenderList(3, (i) => {
            _push(`<div class="flex items-center gap-2 animate-pulse"><div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700"></div><div class="flex-1 space-y-1"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else if (unref(friendRequests).length > 0) {
          _push(`<div class="divide-y divide-gray-100 dark:divide-gray-700/50"><!--[-->`);
          ssrRenderList(unref(friendRequests), (request) => {
            _push(`<div class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"><div class="flex items-center gap-2.5"><div class="cursor-pointer flex-shrink-0">`);
            _push(ssrRenderComponent(CircleAvatar, {
              src: request.sender.avatar || "/images/default-avatar.png",
              alt: request.sender.name,
              size: "sm"
            }, null, _parent));
            _push(`</div><div class="flex-1 min-w-0"><h4 class="font-bold text-xs text-gray-900 dark:text-white truncate hover:text-vikinger-purple dark:hover:text-vikinger-cyan cursor-pointer">${ssrInterpolate(request.sender.name || request.sender.full_name)}</h4><p class="text-[10px] text-gray-400 dark:text-gray-500">${ssrInterpolate(("useDateFormatter" in _ctx ? _ctx.useDateFormatter : unref(useDateFormatter))().formatRelative(request.created_at))}</p></div></div><div class="flex gap-1.5 mt-2"><button${ssrIncludeBooleanAttr(unref(processingIds).has(request.sender.id)) ? " disabled" : ""} class="flex-1 py-1.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-lg text-[10px] font-bold disabled:opacity-50 flex items-center justify-center gap-1 hover:opacity-90 transition-all">`);
            if (unref(processingIds).has(request.sender.id)) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-3 h-3 animate-spin"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-24-regular",
                class: "w-3 h-3"
              }, null, _parent));
            }
            _push(` \u0E22\u0E2D\u0E21\u0E23\u0E31\u0E1A </button><button${ssrIncludeBooleanAttr(unref(processingIds).has(request.sender.id)) ? " disabled" : ""} class="flex-1 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg text-[10px] font-bold disabled:opacity-50 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all"> \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="p-6 text-center"><div class="w-12 h-12 mx-auto mb-2 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-24-regular",
            class: "w-6 h-6 text-gray-400 dark:text-gray-500"
          }, null, _parent));
          _push(`</div><p class="text-xs text-gray-500 dark:text-gray-400 font-medium">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</p></div>`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!---->`);
      }
      if (unref(activeTab) === "suggestions") {
        _push(`<!--[-->`);
        if (unref(isLoadingSuggestions)) {
          _push(`<div class="p-3 space-y-2"><!--[-->`);
          ssrRenderList(3, (i) => {
            _push(`<div class="flex items-center gap-2 animate-pulse"><div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700"></div><div class="flex-1 space-y-1"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else if (unref(suggestions).length > 0) {
          _push(`<div class="divide-y divide-gray-100 dark:divide-gray-700/50"><!--[-->`);
          ssrRenderList(unref(suggestions), (user) => {
            _push(`<div class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"><div class="flex items-center gap-2.5"><div class="cursor-pointer flex-shrink-0">`);
            _push(ssrRenderComponent(CircleAvatar, {
              src: user.avatar || "/images/default-avatar.png",
              alt: user.name,
              size: "sm"
            }, null, _parent));
            _push(`</div><div class="flex-1 min-w-0"><h4 class="font-bold text-xs text-gray-900 dark:text-white truncate hover:text-vikinger-purple dark:hover:text-vikinger-cyan cursor-pointer">${ssrInterpolate(user.name || user.full_name)}</h4>`);
            if (user.mutual_friends_count) {
              _push(`<p class="text-[10px] text-vikinger-purple dark:text-vikinger-cyan">${ssrInterpolate(user.mutual_friends_count)} \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E23\u0E48\u0E27\u0E21 </p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><button${ssrIncludeBooleanAttr(unref(processingIds).has(user.id)) ? " disabled" : ""} class="p-1.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-lg hover:opacity-90 hover:scale-105 transition-all disabled:opacity-50">`);
            if (unref(processingIds).has(user.id)) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-4 h-4 animate-spin"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-add-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
            }
            _push(`</button></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="p-6 text-center"><div class="w-12 h-12 mx-auto mb-2 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:search-24-regular",
            class: "w-6 h-6 text-gray-400 dark:text-gray-500"
          }, null, _parent));
          _push(`</div><p class="text-xs text-gray-500 dark:text-gray-400 font-medium">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E41\u0E19\u0E30\u0E19\u0E33</p></div>`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-2.5 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/50">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/members",
        class: "flex items-center justify-center gap-1.5 py-1.5 px-3 bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 hover:from-vikinger-purple/20 hover:to-vikinger-cyan/20 text-vikinger-purple dark:text-vikinger-cyan rounded-lg text-xs font-bold transition-all"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:search-24-regular",
              class: "w-3.5 h-3.5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 `);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-right-24-regular",
              class: "w-3.5 h-3.5"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:search-24-regular",
                class: "w-3.5 h-3.5"
              }),
              createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 "),
              createVNode(unref(Icon), {
                icon: "fluent:arrow-right-24-regular",
                class: "w-3.5 h-3.5"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/FriendRequestsWidget.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "ProfileAboutSection",
  __ssrInlineRender: true,
  props: {
    profile: {},
    isOwnProfile: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const socialIcons = {
      facebook: { icon: "ri:facebook-fill", color: "#1877f2", label: "Facebook" },
      twitter: { icon: "ri:twitter-x-fill", color: "#000000", label: "Twitter / X" },
      instagram: { icon: "ri:instagram-fill", color: "#e4405f", label: "Instagram" },
      linkedin: { icon: "ri:linkedin-fill", color: "#0077b5", label: "LinkedIn" },
      youtube: { icon: "ri:youtube-fill", color: "#ff0000", label: "YouTube" },
      tiktok: { icon: "ri:tiktok-fill", color: "#000000", label: "TikTok" }
    };
    const age = computed(() => {
      if (!props.profile.birthdate) return null;
      const birth = new Date(props.profile.birthdate);
      const today = /* @__PURE__ */ new Date();
      let age2 = today.getFullYear() - birth.getFullYear();
      const monthDiff = today.getMonth() - birth.getMonth();
      if (monthDiff < 0 || monthDiff === 0 && today.getDate() < birth.getDate()) {
        age2--;
      }
      return age2;
    });
    const joinDate = computed(() => {
      if (!props.profile.join_date) return null;
      return new Date(props.profile.join_date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long"
      });
    });
    const genderDisplay = computed(() => {
      const genderMap = {
        male: "\u0E0A\u0E32\u0E22",
        female: "\u0E2B\u0E0D\u0E34\u0E07",
        other: "\u0E2D\u0E37\u0E48\u0E19\u0E46",
        prefer_not_to_say: "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38"
      };
      return genderMap[props.profile.gender || ""] || props.profile.gender;
    });
    const hasSocialLinks = computed(() => {
      if (!props.profile.social_media_links) return false;
      return Object.values(props.profile.social_media_links).some((v) => v);
    });
    const gradeDisplay = computed(() => {
      if (!props.profile.grade) return null;
      return `\u0E21.${props.profile.grade}`;
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 overflow-hidden" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-gradient-to-r from-vikinger-purple/20 to-vikinger-cyan/20 p-6 border-b border-gray-700"${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-info-24-filled",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A ${ssrInterpolate(__props.profile.username || __props.profile.full_name)}</h3></div><div class="p-6 space-y-6"${_scopeId}>`);
            if (__props.profile.bio) {
              _push2(`<div${_scopeId}><h4 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-3"${_scopeId}>\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E22\u0E48\u0E2D</h4><p class="text-white leading-relaxed"${_scopeId}>${ssrInterpolate(__props.profile.bio)}</p></div>`);
            } else if (__props.isOwnProfile) {
              _push2(`<div class="text-center py-4 bg-gray-700/30 rounded-xl"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:edit-24-regular",
                class: "w-8 h-8 text-gray-500 mx-auto mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400 text-sm"${_scopeId}>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E22\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E43\u0E2B\u0E49\u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01\u0E04\u0E38\u0E13\u0E21\u0E32\u0E01\u0E02\u0E36\u0E49\u0E19</p>`);
              _push2(ssrRenderComponent(_component_NuxtLink, {
                to: "/profile/edit",
                class: "text-vikinger-cyan text-sm hover:underline mt-1 inline-block"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C `);
                  } else {
                    return [
                      createTextVNode(" \u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4"${_scopeId}>`);
            if (__props.profile.gender) {
              _push2(`<div class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"${_scopeId}><div class="w-10 h-10 rounded-lg bg-pink-500/20 flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: __props.profile.gender === "male" ? "fluent:person-24-filled" : __props.profile.gender === "female" ? "fluent:person-24-filled" : "fluent:people-24-filled",
                class: "w-5 h-5 text-pink-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-xs text-gray-400"${_scopeId}>\u0E40\u0E1E\u0E28</p><p class="text-white font-medium"${_scopeId}>${ssrInterpolate(unref(genderDisplay))}</p></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (__props.profile.birthdate) {
              _push2(`<div class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"${_scopeId}><div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-24-filled",
                class: "w-5 h-5 text-orange-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-xs text-gray-400"${_scopeId}>\u0E2D\u0E32\u0E22\u0E38</p><p class="text-white font-medium"${_scopeId}>${ssrInterpolate(unref(age))} \u0E1B\u0E35</p></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (__props.profile.grade) {
              _push2(`<div class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"${_scopeId}><div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-filled",
                class: "w-5 h-5 text-blue-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-xs text-gray-400"${_scopeId}>\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</p><p class="text-white font-medium"${_scopeId}>${ssrInterpolate(unref(gradeDisplay))}</p></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (__props.profile.location) {
              _push2(`<div class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"${_scopeId}><div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:location-24-filled",
                class: "w-5 h-5 text-green-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-xs text-gray-400"${_scopeId}>\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</p><p class="text-white font-medium"${_scopeId}>${ssrInterpolate(__props.profile.location)}</p></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (__props.profile.website) {
              _push2(`<div class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"${_scopeId}><div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:globe-24-filled",
                class: "w-5 h-5 text-cyan-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-xs text-gray-400"${_scopeId}>\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C</p><a${ssrRenderAttr("href", __props.profile.website)} target="_blank" rel="noopener noreferrer" class="text-vikinger-cyan font-medium hover:underline"${_scopeId}>${ssrInterpolate(__props.profile.website.replace(/^https?:\/\//, ""))}</a></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (unref(joinDate)) {
              _push2(`<div class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"${_scopeId}><div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-add-24-filled",
                class: "w-5 h-5 text-purple-400"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-xs text-gray-400"${_scopeId}>\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D</p><p class="text-white font-medium"${_scopeId}>${ssrInterpolate(unref(joinDate))}</p></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            if (__props.profile.interests) {
              _push2(`<div${_scopeId}><h4 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-3"${_scopeId}>\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E19\u0E43\u0E08</h4><div class="flex flex-wrap gap-2"${_scopeId}><!--[-->`);
              ssrRenderList(__props.profile.interests.split(",").map((i) => i.trim()).filter(Boolean), (interest, index) => {
                _push2(`<span class="px-3 py-1.5 bg-vikinger-purple/20 text-vikinger-purple rounded-full text-sm font-medium"${_scopeId}>${ssrInterpolate(interest)}</span>`);
              });
              _push2(`<!--]--></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "bg-gradient-to-r from-vikinger-purple/20 to-vikinger-cyan/20 p-6 border-b border-gray-700" }, [
                createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:person-info-24-filled",
                    class: "w-6 h-6 text-vikinger-cyan"
                  }),
                  createTextVNode(" \u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A " + toDisplayString(__props.profile.username || __props.profile.full_name), 1)
                ])
              ]),
              createVNode("div", { class: "p-6 space-y-6" }, [
                __props.profile.bio ? (openBlock(), createBlock("div", { key: 0 }, [
                  createVNode("h4", { class: "text-sm font-medium text-gray-400 uppercase tracking-wider mb-3" }, "\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E22\u0E48\u0E2D"),
                  createVNode("p", { class: "text-white leading-relaxed" }, toDisplayString(__props.profile.bio), 1)
                ])) : __props.isOwnProfile ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "text-center py-4 bg-gray-700/30 rounded-xl"
                }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-regular",
                    class: "w-8 h-8 text-gray-500 mx-auto mb-2"
                  }),
                  createVNode("p", { class: "text-gray-400 text-sm" }, "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E22\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E43\u0E2B\u0E49\u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01\u0E04\u0E38\u0E13\u0E21\u0E32\u0E01\u0E02\u0E36\u0E49\u0E19"),
                  createVNode(_component_NuxtLink, {
                    to: "/profile/edit",
                    class: "text-vikinger-cyan text-sm hover:underline mt-1 inline-block"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
                    ]),
                    _: 1
                  })
                ])) : createCommentVNode("", true),
                createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-4" }, [
                  __props.profile.gender ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"
                  }, [
                    createVNode("div", { class: "w-10 h-10 rounded-lg bg-pink-500/20 flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: __props.profile.gender === "male" ? "fluent:person-24-filled" : __props.profile.gender === "female" ? "fluent:person-24-filled" : "fluent:people-24-filled",
                        class: "w-5 h-5 text-pink-400"
                      }, null, 8, ["icon"])
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-400" }, "\u0E40\u0E1E\u0E28"),
                      createVNode("p", { class: "text-white font-medium" }, toDisplayString(unref(genderDisplay)), 1)
                    ])
                  ])) : createCommentVNode("", true),
                  __props.profile.birthdate ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"
                  }, [
                    createVNode("div", { class: "w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:calendar-24-filled",
                        class: "w-5 h-5 text-orange-400"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-400" }, "\u0E2D\u0E32\u0E22\u0E38"),
                      createVNode("p", { class: "text-white font-medium" }, toDisplayString(unref(age)) + " \u0E1B\u0E35", 1)
                    ])
                  ])) : createCommentVNode("", true),
                  __props.profile.grade ? (openBlock(), createBlock("div", {
                    key: 2,
                    class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"
                  }, [
                    createVNode("div", { class: "w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:book-24-filled",
                        class: "w-5 h-5 text-blue-400"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-400" }, "\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19"),
                      createVNode("p", { class: "text-white font-medium" }, toDisplayString(unref(gradeDisplay)), 1)
                    ])
                  ])) : createCommentVNode("", true),
                  __props.profile.location ? (openBlock(), createBlock("div", {
                    key: 3,
                    class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"
                  }, [
                    createVNode("div", { class: "w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:location-24-filled",
                        class: "w-5 h-5 text-green-400"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-400" }, "\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48"),
                      createVNode("p", { class: "text-white font-medium" }, toDisplayString(__props.profile.location), 1)
                    ])
                  ])) : createCommentVNode("", true),
                  __props.profile.website ? (openBlock(), createBlock("div", {
                    key: 4,
                    class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"
                  }, [
                    createVNode("div", { class: "w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:globe-24-filled",
                        class: "w-5 h-5 text-cyan-400"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-400" }, "\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C"),
                      createVNode("a", {
                        href: __props.profile.website,
                        target: "_blank",
                        rel: "noopener noreferrer",
                        class: "text-vikinger-cyan font-medium hover:underline"
                      }, toDisplayString(__props.profile.website.replace(/^https?:\/\//, "")), 9, ["href"])
                    ])
                  ])) : createCommentVNode("", true),
                  unref(joinDate) ? (openBlock(), createBlock("div", {
                    key: 5,
                    class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl"
                  }, [
                    createVNode("div", { class: "w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:calendar-add-24-filled",
                        class: "w-5 h-5 text-purple-400"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-400" }, "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D"),
                      createVNode("p", { class: "text-white font-medium" }, toDisplayString(unref(joinDate)), 1)
                    ])
                  ])) : createCommentVNode("", true)
                ]),
                __props.profile.interests ? (openBlock(), createBlock("div", { key: 2 }, [
                  createVNode("h4", { class: "text-sm font-medium text-gray-400 uppercase tracking-wider mb-3" }, "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E19\u0E43\u0E08"),
                  createVNode("div", { class: "flex flex-wrap gap-2" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.profile.interests.split(",").map((i) => i.trim()).filter(Boolean), (interest, index) => {
                      return openBlock(), createBlock("span", {
                        key: index,
                        class: "px-3 py-1.5 bg-vikinger-purple/20 text-vikinger-purple rounded-full text-sm font-medium"
                      }, toDisplayString(interest), 1);
                    }), 128))
                  ])
                ])) : createCommentVNode("", true)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(hasSocialLinks)) {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 overflow-hidden" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-6 border-b border-gray-700"${_scopeId}><h3 class="text-lg font-bold text-white flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:share-24-filled",
                class: "w-5 h-5 text-vikinger-cyan"
              }, null, _parent2, _scopeId));
              _push2(` \u0E42\u0E0B\u0E40\u0E0A\u0E35\u0E22\u0E25\u0E21\u0E35\u0E40\u0E14\u0E35\u0E22 </h3></div><div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-4"${_scopeId}><!--[-->`);
              ssrRenderList(__props.profile.social_media_links, (url, platform) => {
                var _a, _b, _c, _d;
                _push2(`<a${ssrRenderAttr("href", url)} target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl hover:bg-gray-700/50 transition-colors group" style="${ssrRenderStyle(url ? null : { display: "none" })}"${_scopeId}><div class="w-10 h-10 rounded-lg flex items-center justify-center transition-transform group-hover:scale-110" style="${ssrRenderStyle({ backgroundColor: ((_a = socialIcons[platform]) == null ? void 0 : _a.color) + "20" })}"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: ((_b = socialIcons[platform]) == null ? void 0 : _b.icon) || "fluent:link-24-regular",
                  class: "w-5 h-5",
                  style: { color: (_c = socialIcons[platform]) == null ? void 0 : _c.color }
                }, null, _parent2, _scopeId));
                _push2(`</div><span class="text-gray-300 text-sm font-medium group-hover:text-white"${_scopeId}>${ssrInterpolate(((_d = socialIcons[platform]) == null ? void 0 : _d.label) || platform)}</span></a>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              return [
                createVNode("div", { class: "p-6 border-b border-gray-700" }, [
                  createVNode("h3", { class: "text-lg font-bold text-white flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:share-24-filled",
                      class: "w-5 h-5 text-vikinger-cyan"
                    }),
                    createTextVNode(" \u0E42\u0E0B\u0E40\u0E0A\u0E35\u0E22\u0E25\u0E21\u0E35\u0E40\u0E14\u0E35\u0E22 ")
                  ])
                ]),
                createVNode("div", { class: "p-6 grid grid-cols-2 sm:grid-cols-3 gap-4" }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.profile.social_media_links, (url, platform) => {
                    var _a, _b, _c, _d;
                    return withDirectives((openBlock(), createBlock("a", {
                      key: platform,
                      href: url,
                      target: "_blank",
                      rel: "noopener noreferrer",
                      class: "flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl hover:bg-gray-700/50 transition-colors group"
                    }, [
                      createVNode("div", {
                        class: "w-10 h-10 rounded-lg flex items-center justify-center transition-transform group-hover:scale-110",
                        style: { backgroundColor: ((_a = socialIcons[platform]) == null ? void 0 : _a.color) + "20" }
                      }, [
                        createVNode(unref(Icon), {
                          icon: ((_b = socialIcons[platform]) == null ? void 0 : _b.icon) || "fluent:link-24-regular",
                          class: "w-5 h-5",
                          style: { color: (_c = socialIcons[platform]) == null ? void 0 : _c.color }
                        }, null, 8, ["icon", "style"])
                      ], 4),
                      createVNode("span", { class: "text-gray-300 text-sm font-medium group-hover:text-white" }, toDisplayString(((_d = socialIcons[platform]) == null ? void 0 : _d.label) || platform), 1)
                    ], 8, ["href"])), [
                      [vShow, url]
                    ]);
                  }), 128))
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 overflow-hidden" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="p-6 border-b border-gray-700"${_scopeId}><h3 class="text-lg font-bold text-white flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:data-trending-24-filled",
              class: "w-5 h-5 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21 </h3></div><div class="p-6"${_scopeId}><div class="grid grid-cols-2 sm:grid-cols-4 gap-4"${_scopeId}><div class="text-center p-4 bg-gradient-to-br from-violet-500/20 to-purple-500/20 rounded-xl"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:document-24-filled",
              class: "w-8 h-8 text-violet-400 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-2xl font-bold text-white"${_scopeId}>${ssrInterpolate(__props.profile.posts_count || 0)}</p><p class="text-xs text-gray-400"${_scopeId}>\u0E42\u0E1E\u0E2A\u0E15\u0E4C</p></div><div class="text-center p-4 bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-24-filled",
              class: "w-8 h-8 text-blue-400 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-2xl font-bold text-white"${_scopeId}>${ssrInterpolate(__props.profile.friends_count || __props.profile.friends || 0)}</p><p class="text-xs text-gray-400"${_scopeId}>\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</p></div><div class="text-center p-4 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-xl"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:star-24-filled",
              class: "w-8 h-8 text-amber-400 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-2xl font-bold text-white"${_scopeId}>${ssrInterpolate((__props.profile.points || __props.profile.pp || 0).toLocaleString())}</p><p class="text-xs text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</p></div><div class="text-center p-4 bg-gradient-to-br from-emerald-500/20 to-green-500/20 rounded-xl"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:eye-24-filled",
              class: "w-8 h-8 text-emerald-400 mx-auto mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<p class="text-2xl font-bold text-white"${_scopeId}>${ssrInterpolate(__props.profile.visits_count || 0)}</p><p class="text-xs text-gray-400"${_scopeId}>\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E0A\u0E21</p></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-6 border-b border-gray-700" }, [
                createVNode("h3", { class: "text-lg font-bold text-white flex items-center gap-2" }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:data-trending-24-filled",
                    class: "w-5 h-5 text-vikinger-cyan"
                  }),
                  createTextVNode(" \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E20\u0E32\u0E1E\u0E23\u0E27\u0E21 ")
                ])
              ]),
              createVNode("div", { class: "p-6" }, [
                createVNode("div", { class: "grid grid-cols-2 sm:grid-cols-4 gap-4" }, [
                  createVNode("div", { class: "text-center p-4 bg-gradient-to-br from-violet-500/20 to-purple-500/20 rounded-xl" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:document-24-filled",
                      class: "w-8 h-8 text-violet-400 mx-auto mb-2"
                    }),
                    createVNode("p", { class: "text-2xl font-bold text-white" }, toDisplayString(__props.profile.posts_count || 0), 1),
                    createVNode("p", { class: "text-xs text-gray-400" }, "\u0E42\u0E1E\u0E2A\u0E15\u0E4C")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:people-24-filled",
                      class: "w-8 h-8 text-blue-400 mx-auto mb-2"
                    }),
                    createVNode("p", { class: "text-2xl font-bold text-white" }, toDisplayString(__props.profile.friends_count || __props.profile.friends || 0), 1),
                    createVNode("p", { class: "text-xs text-gray-400" }, "\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-xl" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:star-24-filled",
                      class: "w-8 h-8 text-amber-400 mx-auto mb-2"
                    }),
                    createVNode("p", { class: "text-2xl font-bold text-white" }, toDisplayString((__props.profile.points || __props.profile.pp || 0).toLocaleString()), 1),
                    createVNode("p", { class: "text-xs text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21")
                  ]),
                  createVNode("div", { class: "text-center p-4 bg-gradient-to-br from-emerald-500/20 to-green-500/20 rounded-xl" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:eye-24-filled",
                      class: "w-8 h-8 text-emerald-400 mx-auto mb-2"
                    }),
                    createVNode("p", { class: "text-2xl font-bold text-white" }, toDisplayString(__props.profile.visits_count || 0), 1),
                    createVNode("p", { class: "text-xs text-gray-400" }, "\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E0A\u0E21")
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/ProfileAboutSection.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "GroupsList",
  __ssrInlineRender: true,
  props: {
    userId: {},
    isOwnProfile: { type: Boolean }
  },
  setup(__props) {
    useApi();
    const groups = ref([]);
    const isLoading = ref(false);
    const selectedFilter = ref("all");
    const filteredGroups = computed(() => {
      if (selectedFilter.value === "all") return groups.value;
      if (selectedFilter.value === "owned") return groups.value.filter((g) => g.is_admin);
      return groups.value.filter((g) => g.is_member && !g.is_admin);
    });
    const privacyConfig = {
      public: { icon: "fluent:globe-24-regular", label: "\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30", color: "text-green-400" },
      private: { icon: "fluent:lock-closed-24-regular", label: "\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27", color: "text-amber-400" },
      secret: { icon: "fluent:eye-off-24-regular", label: "\u0E25\u0E31\u0E1A", color: "text-red-400" }
    };
    const goToGroup = (group) => {
      navigateTo(`/groups/${group.slug}`);
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 p-5" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4"${_scopeId}><div${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-community-24-filled",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E01\u0E25\u0E38\u0E48\u0E21 <span class="text-vikinger-cyan font-normal ml-1"${_scopeId}>(${ssrInterpolate(unref(groups).length)})</span></h3><p class="text-sm text-gray-400 mt-1"${_scopeId}>\u0E01\u0E25\u0E38\u0E48\u0E21\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21</p></div>`);
            if (__props.isOwnProfile) {
              _push2(ssrRenderComponent(_component_NuxtLink, {
                to: "/groups/create",
                class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "fluent:add-24-regular",
                      class: "w-5 h-5"
                    }, null, _parent3, _scopeId2));
                    _push3(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 `);
                  } else {
                    return [
                      createVNode(unref(Icon), {
                        icon: "fluent:add-24-regular",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="flex gap-2 border-b border-gray-700 pb-3"${_scopeId}><!--[-->`);
            ssrRenderList([
              { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
              { key: "owned", label: "\u0E17\u0E35\u0E48\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E2D\u0E14\u0E21\u0E34\u0E19" },
              { key: "joined", label: "\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21" }
            ], (filter) => {
              _push2(`<button class="${ssrRenderClass([
                "px-4 py-2 rounded-lg text-sm font-medium transition-all",
                unref(selectedFilter) === filter.key ? "bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30" : "text-gray-400 hover:text-white hover:bg-gray-700"
              ])}"${_scopeId}>${ssrInterpolate(filter.label)}</button>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4" }, [
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:people-community-24-filled",
                      class: "w-6 h-6 text-vikinger-cyan"
                    }),
                    createTextVNode(" \u0E01\u0E25\u0E38\u0E48\u0E21 "),
                    createVNode("span", { class: "text-vikinger-cyan font-normal ml-1" }, "(" + toDisplayString(unref(groups).length) + ")", 1)
                  ]),
                  createVNode("p", { class: "text-sm text-gray-400 mt-1" }, "\u0E01\u0E25\u0E38\u0E48\u0E21\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21")
                ]),
                __props.isOwnProfile ? (openBlock(), createBlock(_component_NuxtLink, {
                  key: 0,
                  to: "/groups/create",
                  class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
                }, {
                  default: withCtx(() => [
                    createVNode(unref(Icon), {
                      icon: "fluent:add-24-regular",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "flex gap-2 border-b border-gray-700 pb-3" }, [
                (openBlock(), createBlock(Fragment, null, renderList([
                  { key: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
                  { key: "owned", label: "\u0E17\u0E35\u0E48\u0E40\u0E1B\u0E47\u0E19\u0E41\u0E2D\u0E14\u0E21\u0E34\u0E19" },
                  { key: "joined", label: "\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21" }
                ], (filter) => {
                  return createVNode("button", {
                    key: filter.key,
                    onClick: ($event) => selectedFilter.value = filter.key,
                    class: [
                      "px-4 py-2 rounded-lg text-sm font-medium transition-all",
                      unref(selectedFilter) === filter.key ? "bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30" : "text-gray-400 hover:text-white hover:bg-gray-700"
                    ]
                  }, toDisplayString(filter.label), 11, ["onClick"]);
                }), 64))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(isLoading)) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><!--[-->`);
        ssrRenderList(4, (i) => {
          _push(`<div class="animate-pulse">`);
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 overflow-hidden" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="h-24 bg-gray-700"${_scopeId}></div><div class="p-4 space-y-3"${_scopeId}><div class="h-5 bg-gray-700 rounded w-3/4"${_scopeId}></div><div class="h-3 bg-gray-700 rounded w-1/2"${_scopeId}></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "h-24 bg-gray-700" }),
                  createVNode("div", { class: "p-4 space-y-3" }, [
                    createVNode("div", { class: "h-5 bg-gray-700 rounded w-3/4" }),
                    createVNode("div", { class: "h-3 bg-gray-700 rounded w-1/2" })
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(filteredGroups).length > 0) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><!--[-->`);
        ssrRenderList(unref(filteredGroups), (group) => {
          _push(ssrRenderComponent(_sfc_main$a, {
            key: group.id,
            class: "bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group",
            onClick: ($event) => goToGroup(group)
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="h-24 bg-gradient-to-r from-vikinger-purple/30 to-vikinger-cyan/30 relative overflow-hidden"${_scopeId}>`);
                if (group.cover_image) {
                  _push2(`<img${ssrRenderAttr("src", group.cover_image)}${ssrRenderAttr("alt", group.name)} class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"${_scopeId}>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="${ssrRenderClass([privacyConfig[group.privacy].color, "absolute top-2 right-2 px-2 py-1 bg-black/50 rounded-full flex items-center gap-1 text-xs"])}"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: privacyConfig[group.privacy].icon,
                  class: "w-3 h-3"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(privacyConfig[group.privacy].label)}</div>`);
                if (group.is_admin) {
                  _push2(`<div class="absolute top-2 left-2 px-2 py-1 bg-vikinger-purple text-white rounded-full text-xs font-medium"${_scopeId}> \u0E41\u0E2D\u0E14\u0E21\u0E34\u0E19 </div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="p-4"${_scopeId}><h4 class="font-semibold text-white truncate group-hover:text-vikinger-cyan transition-colors"${_scopeId}>${ssrInterpolate(group.name)}</h4>`);
                if (group.description) {
                  _push2(`<p class="text-sm text-gray-400 line-clamp-2 mt-1"${_scopeId}>${ssrInterpolate(group.description)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="flex items-center gap-4 mt-3 text-sm text-gray-500"${_scopeId}><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:people-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(group.members_count.toLocaleString())} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </span><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:tag-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(group.category)}</span></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "h-24 bg-gradient-to-r from-vikinger-purple/30 to-vikinger-cyan/30 relative overflow-hidden" }, [
                    group.cover_image ? (openBlock(), createBlock("img", {
                      key: 0,
                      src: group.cover_image,
                      alt: group.name,
                      class: "w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    }, null, 8, ["src", "alt"])) : createCommentVNode("", true),
                    createVNode("div", {
                      class: ["absolute top-2 right-2 px-2 py-1 bg-black/50 rounded-full flex items-center gap-1 text-xs", privacyConfig[group.privacy].color]
                    }, [
                      createVNode(unref(Icon), {
                        icon: privacyConfig[group.privacy].icon,
                        class: "w-3 h-3"
                      }, null, 8, ["icon"]),
                      createTextVNode(" " + toDisplayString(privacyConfig[group.privacy].label), 1)
                    ], 2),
                    group.is_admin ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "absolute top-2 left-2 px-2 py-1 bg-vikinger-purple text-white rounded-full text-xs font-medium"
                    }, " \u0E41\u0E2D\u0E14\u0E21\u0E34\u0E19 ")) : createCommentVNode("", true)
                  ]),
                  createVNode("div", { class: "p-4" }, [
                    createVNode("h4", { class: "font-semibold text-white truncate group-hover:text-vikinger-cyan transition-colors" }, toDisplayString(group.name), 1),
                    group.description ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-sm text-gray-400 line-clamp-2 mt-1"
                    }, toDisplayString(group.description), 1)) : createCommentVNode("", true),
                    createVNode("div", { class: "flex items-center gap-4 mt-3 text-sm text-gray-500" }, [
                      createVNode("span", { class: "flex items-center gap-1" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:people-24-regular",
                          class: "w-4 h-4"
                        }),
                        createTextVNode(" " + toDisplayString(group.members_count.toLocaleString()) + " \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 ", 1)
                      ]),
                      createVNode("span", { class: "flex items-center gap-1" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:tag-24-regular",
                          class: "w-4 h-4"
                        }),
                        createTextVNode(" " + toDisplayString(group.category), 1)
                      ])
                    ])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:people-community-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E14\u0E46</p>`);
              if (__props.isOwnProfile) {
                _push2(`<p class="text-sm text-gray-500 mt-2"${_scopeId}> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1E\u0E1A\u0E1B\u0E30\u0E1C\u0E39\u0E49\u0E04\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E19\u0E43\u0E08\u0E40\u0E2B\u0E21\u0E37\u0E2D\u0E19\u0E01\u0E31\u0E19! </p>`);
              } else {
                _push2(`<!---->`);
              }
              if (__props.isOwnProfile) {
                _push2(ssrRenderComponent(_component_NuxtLink, {
                  to: "/groups",
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(ssrRenderComponent(unref(Icon), {
                        icon: "fluent:search-24-regular",
                        class: "w-5 h-5"
                      }, null, _parent3, _scopeId2));
                      _push3(` \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 `);
                    } else {
                      return [
                        createVNode(unref(Icon), {
                          icon: "fluent:search-24-regular",
                          class: "w-5 h-5"
                        }),
                        createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 ")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:people-community-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }),
                createVNode("p", { class: "text-gray-400" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E14\u0E46"),
                __props.isOwnProfile ? (openBlock(), createBlock("p", {
                  key: 0,
                  class: "text-sm text-gray-500 mt-2"
                }, " \u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1E\u0E1A\u0E1B\u0E30\u0E1C\u0E39\u0E49\u0E04\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E19\u0E43\u0E08\u0E40\u0E2B\u0E21\u0E37\u0E2D\u0E19\u0E01\u0E31\u0E19! ")) : createCommentVNode("", true),
                __props.isOwnProfile ? (openBlock(), createBlock(_component_NuxtLink, {
                  key: 1,
                  to: "/groups",
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, {
                  default: withCtx(() => [
                    createVNode(unref(Icon), {
                      icon: "fluent:search-24-regular",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E01\u0E25\u0E38\u0E48\u0E21 ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true)
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/GroupsList.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "EventsList",
  __ssrInlineRender: true,
  props: {
    userId: {},
    isOwnProfile: { type: Boolean }
  },
  setup(__props) {
    useApi();
    const events = ref([]);
    const isLoading = ref(false);
    const selectedFilter = ref("upcoming");
    const filteredEvents = computed(() => {
      const now = /* @__PURE__ */ new Date();
      if (selectedFilter.value === "upcoming") {
        return events.value.filter((e) => new Date(e.start_date) >= now);
      }
      if (selectedFilter.value === "past") {
        return events.value.filter((e) => new Date(e.start_date) < now);
      }
      return events.value.filter((e) => e.status === "hosting");
    });
    const formatEventDate = (dateStr) => {
      const date = new Date(dateStr);
      const options = {
        weekday: "short",
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      };
      return date.toLocaleDateString("th-TH", options);
    };
    const statusConfig = {
      going: { label: "\u0E08\u0E30\u0E44\u0E1B", color: "bg-green-500/20 text-green-400 border-green-500/30" },
      interested: { label: "\u0E2A\u0E19\u0E43\u0E08", color: "bg-amber-500/20 text-amber-400 border-amber-500/30" },
      not_going: { label: "\u0E44\u0E21\u0E48\u0E44\u0E1B", color: "bg-gray-500/20 text-gray-400 border-gray-500/30" },
      hosting: { label: "\u0E40\u0E1B\u0E47\u0E19\u0E1C\u0E39\u0E49\u0E08\u0E31\u0E14", color: "bg-vikinger-purple/20 text-vikinger-purple border-vikinger-purple/30" }
    };
    const isPastEvent = (dateStr) => {
      return new Date(dateStr) < /* @__PURE__ */ new Date();
    };
    const goToEvent = (event) => {
      navigateTo(`/events/${event.slug}`);
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 p-5" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4"${_scopeId}><div${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:calendar-24-filled",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 <span class="text-vikinger-cyan font-normal ml-1"${_scopeId}>(${ssrInterpolate(unref(events).length)})</span></h3><p class="text-sm text-gray-400 mt-1"${_scopeId}>\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E41\u0E25\u0E30\u0E2A\u0E19\u0E43\u0E08</p></div>`);
            if (__props.isOwnProfile) {
              _push2(ssrRenderComponent(_component_NuxtLink, {
                to: "/events/create",
                class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "fluent:add-24-regular",
                      class: "w-5 h-5"
                    }, null, _parent3, _scopeId2));
                    _push3(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 `);
                  } else {
                    return [
                      createVNode(unref(Icon), {
                        icon: "fluent:add-24-regular",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="flex gap-2 border-b border-gray-700 pb-3"${_scopeId}><!--[-->`);
            ssrRenderList([
              { key: "upcoming", label: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E08\u0E30\u0E21\u0E32", icon: "fluent:arrow-trending-24-regular" },
              { key: "past", label: "\u0E17\u0E35\u0E48\u0E1C\u0E48\u0E32\u0E19\u0E21\u0E32", icon: "fluent:history-24-regular" },
              { key: "hosting", label: "\u0E17\u0E35\u0E48\u0E08\u0E31\u0E14", icon: "fluent:star-24-regular" }
            ], (filter) => {
              _push2(`<button class="${ssrRenderClass([
                "px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2",
                unref(selectedFilter) === filter.key ? "bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30" : "text-gray-400 hover:text-white hover:bg-gray-700"
              ])}"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: filter.icon,
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` ${ssrInterpolate(filter.label)}</button>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4" }, [
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:calendar-24-filled",
                      class: "w-6 h-6 text-vikinger-cyan"
                    }),
                    createTextVNode(" \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 "),
                    createVNode("span", { class: "text-vikinger-cyan font-normal ml-1" }, "(" + toDisplayString(unref(events).length) + ")", 1)
                  ]),
                  createVNode("p", { class: "text-sm text-gray-400 mt-1" }, "\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E35\u0E48\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E41\u0E25\u0E30\u0E2A\u0E19\u0E43\u0E08")
                ]),
                __props.isOwnProfile ? (openBlock(), createBlock(_component_NuxtLink, {
                  key: 0,
                  to: "/events/create",
                  class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
                }, {
                  default: withCtx(() => [
                    createVNode(unref(Icon), {
                      icon: "fluent:add-24-regular",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "flex gap-2 border-b border-gray-700 pb-3" }, [
                (openBlock(), createBlock(Fragment, null, renderList([
                  { key: "upcoming", label: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E08\u0E30\u0E21\u0E32", icon: "fluent:arrow-trending-24-regular" },
                  { key: "past", label: "\u0E17\u0E35\u0E48\u0E1C\u0E48\u0E32\u0E19\u0E21\u0E32", icon: "fluent:history-24-regular" },
                  { key: "hosting", label: "\u0E17\u0E35\u0E48\u0E08\u0E31\u0E14", icon: "fluent:star-24-regular" }
                ], (filter) => {
                  return createVNode("button", {
                    key: filter.key,
                    onClick: ($event) => selectedFilter.value = filter.key,
                    class: [
                      "px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2",
                      unref(selectedFilter) === filter.key ? "bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30" : "text-gray-400 hover:text-white hover:bg-gray-700"
                    ]
                  }, [
                    createVNode(unref(Icon), {
                      icon: filter.icon,
                      class: "w-4 h-4"
                    }, null, 8, ["icon"]),
                    createTextVNode(" " + toDisplayString(filter.label), 1)
                  ], 10, ["onClick"]);
                }), 64))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(isLoading)) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="animate-pulse">`);
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex gap-4 p-4"${_scopeId}><div class="w-32 h-24 bg-gray-700 rounded-lg shrink-0"${_scopeId}></div><div class="flex-1 space-y-3"${_scopeId}><div class="h-5 bg-gray-700 rounded w-3/4"${_scopeId}></div><div class="h-3 bg-gray-700 rounded w-1/2"${_scopeId}></div><div class="h-3 bg-gray-700 rounded w-1/3"${_scopeId}></div></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "flex gap-4 p-4" }, [
                    createVNode("div", { class: "w-32 h-24 bg-gray-700 rounded-lg shrink-0" }),
                    createVNode("div", { class: "flex-1 space-y-3" }, [
                      createVNode("div", { class: "h-5 bg-gray-700 rounded w-3/4" }),
                      createVNode("div", { class: "h-3 bg-gray-700 rounded w-1/2" }),
                      createVNode("div", { class: "h-3 bg-gray-700 rounded w-1/3" })
                    ])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(filteredEvents).length > 0) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(unref(filteredEvents), (event) => {
          _push(ssrRenderComponent(_sfc_main$a, {
            key: event.id,
            class: "bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group",
            onClick: ($event) => goToEvent(event)
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="flex flex-col sm:flex-row gap-4 p-4"${_scopeId}><div class="w-full sm:w-40 h-32 sm:h-28 bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/30 rounded-lg overflow-hidden shrink-0 relative"${_scopeId}>`);
                if (event.cover_image) {
                  _push2(`<img${ssrRenderAttr("src", event.cover_image)}${ssrRenderAttr("alt", event.title)} class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"${_scopeId}>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="absolute top-2 left-2 bg-black/80 rounded-lg p-2 text-center min-w-[50px]"${_scopeId}><div class="text-xs text-vikinger-cyan uppercase"${_scopeId}>${ssrInterpolate(new Date(event.start_date).toLocaleDateString("th-TH", { month: "short" }))}</div><div class="text-xl font-bold text-white"${_scopeId}>${ssrInterpolate(new Date(event.start_date).getDate())}</div></div>`);
                if (isPastEvent(event.start_date)) {
                  _push2(`<div class="absolute inset-0 bg-black/60 flex items-center justify-center"${_scopeId}><span class="text-gray-300 text-sm font-medium"${_scopeId}>\u0E08\u0E1A\u0E41\u0E25\u0E49\u0E27</span></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="flex-1 min-w-0"${_scopeId}><div class="flex items-start justify-between gap-2"${_scopeId}><h4 class="font-semibold text-white truncate group-hover:text-vikinger-cyan transition-colors"${_scopeId}>${ssrInterpolate(event.title)}</h4><span class="${ssrRenderClass([
                  "px-2 py-1 text-xs rounded-full border shrink-0",
                  statusConfig[event.status].color
                ])}"${_scopeId}>${ssrInterpolate(statusConfig[event.status].label)}</span></div>`);
                if (event.description) {
                  _push2(`<p class="text-sm text-gray-400 line-clamp-2 mt-1"${_scopeId}>${ssrInterpolate(event.description)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-sm text-gray-500"${_scopeId}><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:clock-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(formatEventDate(event.start_date))}</span><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: event.is_online ? "fluent:video-24-regular" : "fluent:location-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(`<span class="${ssrRenderClass(event.is_online ? "text-vikinger-cyan" : "")}"${_scopeId}>${ssrInterpolate(event.location || (event.is_online ? "Online" : "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38"))}</span></span><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:people-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(event.attendees_count)} \u0E04\u0E19\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 </span>`);
                if (event.price !== void 0) {
                  _push2(`<span class="flex items-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:ticket-24-regular",
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(`<span class="${ssrRenderClass(event.price === 0 ? "text-green-400" : "text-vikinger-cyan")}"${_scopeId}>${ssrInterpolate(event.price === 0 ? "\u0E1F\u0E23\u0E35" : `\u0E3F${event.price.toLocaleString()}`)}</span></span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="mt-3"${_scopeId}><span class="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded-full"${_scopeId}>${ssrInterpolate(event.category)}</span></div></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "flex flex-col sm:flex-row gap-4 p-4" }, [
                    createVNode("div", { class: "w-full sm:w-40 h-32 sm:h-28 bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/30 rounded-lg overflow-hidden shrink-0 relative" }, [
                      event.cover_image ? (openBlock(), createBlock("img", {
                        key: 0,
                        src: event.cover_image,
                        alt: event.title,
                        class: "w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      }, null, 8, ["src", "alt"])) : createCommentVNode("", true),
                      createVNode("div", { class: "absolute top-2 left-2 bg-black/80 rounded-lg p-2 text-center min-w-[50px]" }, [
                        createVNode("div", { class: "text-xs text-vikinger-cyan uppercase" }, toDisplayString(new Date(event.start_date).toLocaleDateString("th-TH", { month: "short" })), 1),
                        createVNode("div", { class: "text-xl font-bold text-white" }, toDisplayString(new Date(event.start_date).getDate()), 1)
                      ]),
                      isPastEvent(event.start_date) ? (openBlock(), createBlock("div", {
                        key: 1,
                        class: "absolute inset-0 bg-black/60 flex items-center justify-center"
                      }, [
                        createVNode("span", { class: "text-gray-300 text-sm font-medium" }, "\u0E08\u0E1A\u0E41\u0E25\u0E49\u0E27")
                      ])) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "flex-1 min-w-0" }, [
                      createVNode("div", { class: "flex items-start justify-between gap-2" }, [
                        createVNode("h4", { class: "font-semibold text-white truncate group-hover:text-vikinger-cyan transition-colors" }, toDisplayString(event.title), 1),
                        createVNode("span", {
                          class: [
                            "px-2 py-1 text-xs rounded-full border shrink-0",
                            statusConfig[event.status].color
                          ]
                        }, toDisplayString(statusConfig[event.status].label), 3)
                      ]),
                      event.description ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "text-sm text-gray-400 line-clamp-2 mt-1"
                      }, toDisplayString(event.description), 1)) : createCommentVNode("", true),
                      createVNode("div", { class: "flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-sm text-gray-500" }, [
                        createVNode("span", { class: "flex items-center gap-1" }, [
                          createVNode(unref(Icon), {
                            icon: "fluent:clock-24-regular",
                            class: "w-4 h-4"
                          }),
                          createTextVNode(" " + toDisplayString(formatEventDate(event.start_date)), 1)
                        ]),
                        createVNode("span", { class: "flex items-center gap-1" }, [
                          createVNode(unref(Icon), {
                            icon: event.is_online ? "fluent:video-24-regular" : "fluent:location-24-regular",
                            class: "w-4 h-4"
                          }, null, 8, ["icon"]),
                          createVNode("span", {
                            class: event.is_online ? "text-vikinger-cyan" : ""
                          }, toDisplayString(event.location || (event.is_online ? "Online" : "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")), 3)
                        ]),
                        createVNode("span", { class: "flex items-center gap-1" }, [
                          createVNode(unref(Icon), {
                            icon: "fluent:people-24-regular",
                            class: "w-4 h-4"
                          }),
                          createTextVNode(" " + toDisplayString(event.attendees_count) + " \u0E04\u0E19\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 ", 1)
                        ]),
                        event.price !== void 0 ? (openBlock(), createBlock("span", {
                          key: 0,
                          class: "flex items-center gap-1"
                        }, [
                          createVNode(unref(Icon), {
                            icon: "fluent:ticket-24-regular",
                            class: "w-4 h-4"
                          }),
                          createVNode("span", {
                            class: event.price === 0 ? "text-green-400" : "text-vikinger-cyan"
                          }, toDisplayString(event.price === 0 ? "\u0E1F\u0E23\u0E35" : `\u0E3F${event.price.toLocaleString()}`), 3)
                        ])) : createCommentVNode("", true)
                      ]),
                      createVNode("div", { class: "mt-3" }, [
                        createVNode("span", { class: "px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded-full" }, toDisplayString(event.category), 1)
                      ])
                    ])
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400"${_scopeId}>${ssrInterpolate(unref(selectedFilter) === "upcoming" ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E35\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E08\u0E30\u0E21\u0E32" : unref(selectedFilter) === "past" ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E35\u0E48\u0E1C\u0E48\u0E32\u0E19\u0E21\u0E32" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E04\u0E22\u0E08\u0E31\u0E14\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21")}</p>`);
              if (__props.isOwnProfile) {
                _push2(`<p class="text-sm text-gray-500 mt-2"${_scopeId}> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1E\u0E1A\u0E1B\u0E30\u0E1C\u0E39\u0E49\u0E04\u0E19\u0E43\u0E2B\u0E21\u0E48\u0E46! </p>`);
              } else {
                _push2(`<!---->`);
              }
              if (__props.isOwnProfile) {
                _push2(ssrRenderComponent(_component_NuxtLink, {
                  to: "/events",
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(ssrRenderComponent(unref(Icon), {
                        icon: "fluent:search-24-regular",
                        class: "w-5 h-5"
                      }, null, _parent3, _scopeId2));
                      _push3(` \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 `);
                    } else {
                      return [
                        createVNode(unref(Icon), {
                          icon: "fluent:search-24-regular",
                          class: "w-5 h-5"
                        }),
                        createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 ")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:calendar-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }),
                createVNode("p", { class: "text-gray-400" }, toDisplayString(unref(selectedFilter) === "upcoming" ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E35\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E08\u0E30\u0E21\u0E32" : unref(selectedFilter) === "past" ? "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E17\u0E35\u0E48\u0E1C\u0E48\u0E32\u0E19\u0E21\u0E32" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E04\u0E22\u0E08\u0E31\u0E14\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21"), 1),
                __props.isOwnProfile ? (openBlock(), createBlock("p", {
                  key: 0,
                  class: "text-sm text-gray-500 mt-2"
                }, " \u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1E\u0E1A\u0E1B\u0E30\u0E1C\u0E39\u0E49\u0E04\u0E19\u0E43\u0E2B\u0E21\u0E48\u0E46! ")) : createCommentVNode("", true),
                __props.isOwnProfile ? (openBlock(), createBlock(_component_NuxtLink, {
                  key: 1,
                  to: "/events",
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, {
                  default: withCtx(() => [
                    createVNode(unref(Icon), {
                      icon: "fluent:search-24-regular",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true)
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/EventsList.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "VideosList",
  __ssrInlineRender: true,
  props: {
    userId: {},
    isOwnProfile: { type: Boolean }
  },
  setup(__props) {
    const api = useApi();
    const videos = ref([]);
    const isLoading = ref(false);
    const selectedVideo = ref(null);
    const showUploadModal = ref(false);
    const sortBy = ref("recent");
    const sortedVideos = computed(() => {
      const sorted = [...videos.value];
      switch (sortBy.value) {
        case "recent":
          return sorted.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
        case "popular":
          return sorted.sort((a, b) => b.views_count - a.views_count);
        case "oldest":
          return sorted.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
        default:
          return sorted;
      }
    });
    const formatDuration = (seconds) => {
      const hrs = Math.floor(seconds / 3600);
      const mins = Math.floor(seconds % 3600 / 60);
      const secs = seconds % 60;
      if (hrs > 0) {
        return `${hrs}:${mins.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
      }
      return `${mins}:${secs.toString().padStart(2, "0")}`;
    };
    const formatViews = (count) => {
      if (count >= 1e6) {
        return `${(count / 1e6).toFixed(1)}M`;
      }
      if (count >= 1e3) {
        return `${(count / 1e3).toFixed(1)}K`;
      }
      return count.toString();
    };
    const formatRelativeTime = (dateStr) => {
      const date = new Date(dateStr);
      const now = /* @__PURE__ */ new Date();
      const diffMs = now.getTime() - date.getTime();
      const diffDays = Math.floor(diffMs / (1e3 * 60 * 60 * 24));
      if (diffDays === 0) return "\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49";
      if (diffDays === 1) return "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E27\u0E32\u0E19";
      if (diffDays < 7) return `${diffDays} \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diffDays < 30) return `${Math.floor(diffDays / 7)} \u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} \u0E40\u0E14\u0E37\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      return `${Math.floor(diffDays / 365)} \u0E1B\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
    };
    const privacyConfig = {
      public: { icon: "fluent:globe-24-regular", label: "\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30" },
      friends: { icon: "fluent:people-24-regular", label: "\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19" },
      private: { icon: "fluent:lock-closed-24-regular", label: "\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27" }
    };
    const playVideo = (video) => {
      selectedVideo.value = video;
    };
    const deleteVideo = async (video) => {
      if (!confirm(`\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D "${video.title}" \u0E43\u0E0A\u0E48\u0E44\u0E2B\u0E21?`)) return;
      try {
        await api.delete(`/api/profile/videos/${video.id}`);
        videos.value = videos.value.filter((v) => v.id !== video.id);
      } catch (error) {
        console.error("Error deleting video:", error);
        alert("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E44\u0E14\u0E49");
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 p-5" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4"${_scopeId}><div${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:video-24-filled",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent2, _scopeId));
            _push2(` \u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D <span class="text-vikinger-cyan font-normal ml-1"${_scopeId}>(${ssrInterpolate(unref(videos).length)})</span></h3><p class="text-sm text-gray-400 mt-1"${_scopeId}>\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E17\u0E35\u0E48\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14</p></div>`);
            if (__props.isOwnProfile) {
              _push2(`<button class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-upload-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D </button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="flex items-center gap-4"${_scopeId}><span class="text-sm text-gray-400"${_scopeId}>\u0E40\u0E23\u0E35\u0E22\u0E07\u0E15\u0E32\u0E21:</span><div class="flex gap-2"${_scopeId}><!--[-->`);
            ssrRenderList([
              { key: "recent", label: "\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14" },
              { key: "popular", label: "\u0E22\u0E2D\u0E14\u0E19\u0E34\u0E22\u0E21" },
              { key: "oldest", label: "\u0E40\u0E01\u0E48\u0E32\u0E2A\u0E38\u0E14" }
            ], (sort) => {
              _push2(`<button class="${ssrRenderClass([
                "px-3 py-1.5 rounded-full text-sm transition-all",
                unref(sortBy) === sort.key ? "bg-vikinger-purple text-white" : "bg-gray-700 text-gray-400 hover:text-white"
              ])}"${_scopeId}>${ssrInterpolate(sort.label)}</button>`);
            });
            _push2(`<!--]--></div></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4" }, [
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:video-24-filled",
                      class: "w-6 h-6 text-vikinger-cyan"
                    }),
                    createTextVNode(" \u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D "),
                    createVNode("span", { class: "text-vikinger-cyan font-normal ml-1" }, "(" + toDisplayString(unref(videos).length) + ")", 1)
                  ]),
                  createVNode("p", { class: "text-sm text-gray-400 mt-1" }, "\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E17\u0E35\u0E48\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14")
                ]),
                __props.isOwnProfile ? (openBlock(), createBlock("button", {
                  key: 0,
                  onClick: ($event) => showUploadModal.value = true,
                  class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
                }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:arrow-upload-24-regular",
                    class: "w-5 h-5"
                  }),
                  createTextVNode(" \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D ")
                ], 8, ["onClick"])) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "flex items-center gap-4" }, [
                createVNode("span", { class: "text-sm text-gray-400" }, "\u0E40\u0E23\u0E35\u0E22\u0E07\u0E15\u0E32\u0E21:"),
                createVNode("div", { class: "flex gap-2" }, [
                  (openBlock(), createBlock(Fragment, null, renderList([
                    { key: "recent", label: "\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14" },
                    { key: "popular", label: "\u0E22\u0E2D\u0E14\u0E19\u0E34\u0E22\u0E21" },
                    { key: "oldest", label: "\u0E40\u0E01\u0E48\u0E32\u0E2A\u0E38\u0E14" }
                  ], (sort) => {
                    return createVNode("button", {
                      key: sort.key,
                      onClick: ($event) => sortBy.value = sort.key,
                      class: [
                        "px-3 py-1.5 rounded-full text-sm transition-all",
                        unref(sortBy) === sort.key ? "bg-vikinger-purple text-white" : "bg-gray-700 text-gray-400 hover:text-white"
                      ]
                    }, toDisplayString(sort.label), 11, ["onClick"]);
                  }), 64))
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(isLoading)) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="animate-pulse">`);
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 overflow-hidden" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="aspect-video bg-gray-700"${_scopeId}></div><div class="p-4 space-y-3"${_scopeId}><div class="h-4 bg-gray-700 rounded w-3/4"${_scopeId}></div><div class="h-3 bg-gray-700 rounded w-1/2"${_scopeId}></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "aspect-video bg-gray-700" }),
                  createVNode("div", { class: "p-4 space-y-3" }, [
                    createVNode("div", { class: "h-4 bg-gray-700 rounded w-3/4" }),
                    createVNode("div", { class: "h-3 bg-gray-700 rounded w-1/2" })
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(sortedVideos).length > 0) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(unref(sortedVideos), (video) => {
          _push(ssrRenderComponent(_sfc_main$a, {
            key: video.id,
            class: "bg-gray-800 border-gray-700 overflow-hidden group"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="aspect-video bg-gray-900 relative cursor-pointer"${_scopeId}><img${ssrRenderAttr("src", video.thumbnail)}${ssrRenderAttr("alt", video.title)} class="w-full h-full object-cover group-hover:opacity-80 transition-opacity"${_scopeId}><div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"${_scopeId}><div class="w-16 h-16 bg-vikinger-purple/90 rounded-full flex items-center justify-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:play-24-filled",
                  class: "w-8 h-8 text-white ml-1"
                }, null, _parent2, _scopeId));
                _push2(`</div></div><div class="absolute bottom-2 right-2 bg-black/80 text-white text-xs px-2 py-1 rounded"${_scopeId}>${ssrInterpolate(formatDuration(video.duration))}</div>`);
                if (video.privacy !== "public") {
                  _push2(`<div class="absolute top-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full flex items-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: privacyConfig[video.privacy].icon,
                    class: "w-3 h-3"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(privacyConfig[video.privacy].label)}</div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="p-4"${_scopeId}><h4 class="font-semibold text-white line-clamp-2 group-hover:text-vikinger-cyan transition-colors"${_scopeId}>${ssrInterpolate(video.title)}</h4><div class="flex items-center gap-3 mt-2 text-sm text-gray-500"${_scopeId}><span class="flex items-center gap-1"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:eye-24-regular",
                  class: "w-4 h-4"
                }, null, _parent2, _scopeId));
                _push2(` ${ssrInterpolate(formatViews(video.views_count))} views </span><span${_scopeId}>\u2022</span><span${_scopeId}>${ssrInterpolate(formatRelativeTime(video.created_at))}</span></div>`);
                if (__props.isOwnProfile) {
                  _push2(`<div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-700"${_scopeId}><button class="flex-1 px-3 py-1.5 bg-gray-700 text-gray-300 text-sm rounded-lg hover:bg-gray-600 transition-colors flex items-center justify-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:edit-24-regular",
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button><button class="px-3 py-1.5 bg-red-500/20 text-red-400 text-sm rounded-lg hover:bg-red-500/30 transition-colors flex items-center justify-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:delete-24-regular",
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(`</button></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", {
                    class: "aspect-video bg-gray-900 relative cursor-pointer",
                    onClick: ($event) => playVideo(video)
                  }, [
                    createVNode("img", {
                      src: video.thumbnail,
                      alt: video.title,
                      class: "w-full h-full object-cover group-hover:opacity-80 transition-opacity"
                    }, null, 8, ["src", "alt"]),
                    createVNode("div", { class: "absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" }, [
                      createVNode("div", { class: "w-16 h-16 bg-vikinger-purple/90 rounded-full flex items-center justify-center" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:play-24-filled",
                          class: "w-8 h-8 text-white ml-1"
                        })
                      ])
                    ]),
                    createVNode("div", { class: "absolute bottom-2 right-2 bg-black/80 text-white text-xs px-2 py-1 rounded" }, toDisplayString(formatDuration(video.duration)), 1),
                    video.privacy !== "public" ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "absolute top-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full flex items-center gap-1"
                    }, [
                      createVNode(unref(Icon), {
                        icon: privacyConfig[video.privacy].icon,
                        class: "w-3 h-3"
                      }, null, 8, ["icon"]),
                      createTextVNode(" " + toDisplayString(privacyConfig[video.privacy].label), 1)
                    ])) : createCommentVNode("", true)
                  ], 8, ["onClick"]),
                  createVNode("div", { class: "p-4" }, [
                    createVNode("h4", { class: "font-semibold text-white line-clamp-2 group-hover:text-vikinger-cyan transition-colors" }, toDisplayString(video.title), 1),
                    createVNode("div", { class: "flex items-center gap-3 mt-2 text-sm text-gray-500" }, [
                      createVNode("span", { class: "flex items-center gap-1" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:eye-24-regular",
                          class: "w-4 h-4"
                        }),
                        createTextVNode(" " + toDisplayString(formatViews(video.views_count)) + " views ", 1)
                      ]),
                      createVNode("span", null, "\u2022"),
                      createVNode("span", null, toDisplayString(formatRelativeTime(video.created_at)), 1)
                    ]),
                    __props.isOwnProfile ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "flex items-center gap-2 mt-3 pt-3 border-t border-gray-700"
                    }, [
                      createVNode("button", { class: "flex-1 px-3 py-1.5 bg-gray-700 text-gray-300 text-sm rounded-lg hover:bg-gray-600 transition-colors flex items-center justify-center gap-1" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:edit-24-regular",
                          class: "w-4 h-4"
                        }),
                        createTextVNode(" \u0E41\u0E01\u0E49\u0E44\u0E02 ")
                      ]),
                      createVNode("button", {
                        onClick: ($event) => deleteVideo(video),
                        class: "px-3 py-1.5 bg-red-500/20 text-red-400 text-sm rounded-lg hover:bg-red-500/30 transition-colors flex items-center justify-center gap-1"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:delete-24-regular",
                          class: "w-4 h-4"
                        })
                      ], 8, ["onClick"])
                    ])) : createCommentVNode("", true)
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:video-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D</p>`);
              if (__props.isOwnProfile) {
                _push2(`<p class="text-sm text-gray-500 mt-2"${_scopeId}> \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E0A\u0E23\u0E4C\u0E01\u0E31\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13! </p>`);
              } else {
                _push2(`<!---->`);
              }
              if (__props.isOwnProfile) {
                _push2(`<button class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:arrow-upload-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(` \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E41\u0E23\u0E01 </button>`);
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:video-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }),
                createVNode("p", { class: "text-gray-400" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D"),
                __props.isOwnProfile ? (openBlock(), createBlock("p", {
                  key: 0,
                  class: "text-sm text-gray-500 mt-2"
                }, " \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E0A\u0E23\u0E4C\u0E01\u0E31\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13! ")) : createCommentVNode("", true),
                __props.isOwnProfile ? (openBlock(), createBlock("button", {
                  key: 1,
                  onClick: ($event) => showUploadModal.value = true,
                  class: "inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
                }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:arrow-upload-24-regular",
                    class: "w-5 h-5"
                  }),
                  createTextVNode(" \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E41\u0E23\u0E01 ")
                ], 8, ["onClick"])) : createCommentVNode("", true)
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(selectedVideo)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4"><div class="absolute inset-0 bg-black/90"></div><div class="relative w-full max-w-5xl z-10"><button class="absolute -top-12 right-0 text-white hover:text-vikinger-cyan transition-colors">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-8 h-8"
          }, null, _parent));
          _push2(`</button><div class="aspect-video bg-black rounded-lg overflow-hidden shadow-2xl"><video${ssrRenderAttr("src", unref(selectedVideo).video_url)} controls autoplay class="w-full h-full"> Your browser does not support the video tag. </video></div><div class="mt-4 p-4 bg-gray-800 rounded-lg"><h3 class="text-xl font-bold text-white">${ssrInterpolate(unref(selectedVideo).title)}</h3>`);
          if (unref(selectedVideo).description) {
            _push2(`<p class="text-gray-400 mt-2">${ssrInterpolate(unref(selectedVideo).description)}</p>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="flex items-center gap-4 mt-3 text-sm text-gray-500"><span class="flex items-center gap-1">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:eye-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(` ${ssrInterpolate(formatViews(unref(selectedVideo).views_count))} views </span><span class="flex items-center gap-1">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:thumb-like-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(` ${ssrInterpolate(formatViews(unref(selectedVideo).likes_count))} likes </span><span>${ssrInterpolate(formatRelativeTime(unref(selectedVideo).created_at))}</span></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(showUploadModal)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4"><div class="absolute inset-0 bg-black/80"></div><div class="relative bg-gray-800 rounded-xl p-6 max-w-lg w-full z-10"><h3 class="text-xl font-bold text-white mb-4">\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D</h3><div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-vikinger-purple transition-colors cursor-pointer">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:cloud-arrow-up-24-regular",
            class: "w-12 h-12 text-gray-500 mx-auto mb-3"
          }, null, _parent));
          _push2(`<p class="text-gray-400">\u0E04\u0E25\u0E34\u0E01\u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E21\u0E32\u0E27\u0E32\u0E07</p><p class="text-sm text-gray-500 mt-1">MP4, WebM, MOV (\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 500MB)</p></div><div class="flex justify-end gap-3 mt-6"><button class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors" disabled> \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/profile/VideosList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const useProfile = () => {
  const api = useApi();
  const profile = ref(null);
  const isLoading = ref(false);
  const error = ref(null);
  const isSaving = ref(false);
  const isProfileLoaded = computed(() => !!profile.value);
  const profileCompletion = computed(() => {
    var _a, _b;
    return (_b = (_a = profile.value) == null ? void 0 : _a.profile_completion) != null ? _b : null;
  });
  const missingFields = computed(() => {
    var _a, _b, _c;
    return (_c = (_b = (_a = profile.value) == null ? void 0 : _a.profile_completion) == null ? void 0 : _b.missing_fields) != null ? _c : [];
  });
  const fetchMyProfile = async () => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await api.get("/api/profile/me");
      if (response.success) {
        profile.value = response.data;
        return response.data;
      }
      return null;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E44\u0E14\u0E49";
      console.error("Error fetching profile:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const fetchUserProfile = async (referenceCode) => {
    isLoading.value = true;
    error.value = null;
    try {
      const response = await api.get(`/api/users/${referenceCode}/show`);
      if (response.success) {
        return {
          profile: response.data,
          friendshipStatus: response.friendship_status,
          canViewFullProfile: response.can_view_full_profile,
          isOwnProfile: response.is_own_profile
        };
      }
      return null;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E44\u0E14\u0E49";
      console.error("Error fetching user profile:", err);
      return null;
    } finally {
      isLoading.value = false;
    }
  };
  const updateProfile = async (data) => {
    isSaving.value = true;
    error.value = null;
    try {
      const response = await api.put("/api/profile/update", data);
      if (response.success) {
        profile.value = response.data;
        return response.data;
      }
      return null;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E44\u0E14\u0E49";
      console.error("Error updating profile:", err);
      throw err;
    } finally {
      isSaving.value = false;
    }
  };
  const updateAvatar = async (file) => {
    isSaving.value = true;
    error.value = null;
    try {
      const formData = new FormData();
      formData.append("avatar", file);
      const response = await api.call("/api/profile/avatar", {
        method: "POST",
        body: formData
      });
      if (response.success && profile.value) {
        profile.value.avatar = response.avatar;
        return response.avatar;
      }
      return null;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E44\u0E14\u0E49";
      console.error("Error updating avatar:", err);
      throw err;
    } finally {
      isSaving.value = false;
    }
  };
  const updateCover = async (file) => {
    isSaving.value = true;
    error.value = null;
    try {
      const formData = new FormData();
      formData.append("cover", file);
      const response = await api.call("/api/profile/cover", {
        method: "POST",
        body: formData
      });
      if (response.success && profile.value) {
        profile.value.cover_image = response.cover_image;
        return response.cover_image;
      }
      return null;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E1B\u0E01\u0E44\u0E14\u0E49";
      console.error("Error updating cover:", err);
      throw err;
    } finally {
      isSaving.value = false;
    }
  };
  const fetchProfileCompletion = async () => {
    try {
      const response = await api.get("/api/profile/completion");
      if (response.success) {
        if (profile.value) {
          profile.value.profile_completion = response.data;
        }
        return response.data;
      }
      return null;
    } catch (err) {
      console.error("Error fetching profile completion:", err);
      return null;
    }
  };
  const updatePrivacy = async (setting) => {
    isSaving.value = true;
    error.value = null;
    try {
      const response = await api.put("/api/profile/privacy", {
        privacy_settings: setting
      });
      if (response.success && profile.value) {
        profile.value.privacy_settings = setting;
        return true;
      }
      return false;
    } catch (err) {
      error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E44\u0E14\u0E49";
      console.error("Error updating privacy:", err);
      return false;
    } finally {
      isSaving.value = false;
    }
  };
  const fetchStats = async (referenceCode) => {
    try {
      const endpoint = referenceCode ? `/api/users/${referenceCode}/stats` : "/api/profile/stats";
      const response = await api.get(endpoint);
      return response.success ? response.data : null;
    } catch (err) {
      console.error("Error fetching stats:", err);
      return null;
    }
  };
  const clearProfile = () => {
    profile.value = null;
    error.value = null;
  };
  return {
    // State
    profile,
    isLoading,
    error,
    isSaving,
    // Computed
    isProfileLoaded,
    profileCompletion,
    missingFields,
    // Methods
    fetchMyProfile,
    fetchUserProfile,
    updateProfile,
    updateAvatar,
    updateCover,
    fetchProfileCompletion,
    updatePrivacy,
    fetchStats,
    clearProfile
  };
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[id]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const { fetchUserProfile, fetchMyProfile } = useProfile();
    useFriends();
    const authStore = useAuthStore();
    useToast();
    const profile = ref(null);
    const friendshipStatus = ref(null);
    const canViewFullProfile = ref(true);
    const isOwnProfile = ref(false);
    const isLoading = ref(true);
    const activeTab = ref("timeline");
    const activities = ref([]);
    const activitiesPage = ref(1);
    const activitiesLastPage = ref(1);
    const isLoadingMore = ref(false);
    const hasMoreActivities = computed(() => activitiesPage.value < activitiesLastPage.value);
    ref(null);
    const canScrollLeft = ref(false);
    const canScrollRight = ref(true);
    const showCoverModal = ref(false);
    const showAvatarModal = ref(false);
    ref(null);
    ref(null);
    const coverPreview = ref(null);
    const avatarPreview = ref(null);
    const isUploadingCover = ref(false);
    const isUploadingAvatar = ref(false);
    const referenceCode = computed(() => route.params.id);
    const isViewingOwnProfile = computed(() => {
      if (!authStore.user) return false;
      return referenceCode.value === "me" || referenceCode.value === authStore.user.reference_code || referenceCode.value === String(authStore.user.id);
    });
    const loadProfile = async () => {
      isLoading.value = true;
      try {
        if (isViewingOwnProfile.value) {
          const data = await fetchMyProfile();
          if (data) {
            profile.value = data;
            isOwnProfile.value = true;
          }
        } else {
          const result = await fetchUserProfile(referenceCode.value);
          if (result) {
            profile.value = result.profile;
            friendshipStatus.value = result.friendshipStatus;
            canViewFullProfile.value = result.canViewFullProfile;
            isOwnProfile.value = result.isOwnProfile;
          }
        }
        if (profile.value) {
          await loadActivities();
        }
      } catch (error) {
        console.error("Error loading profile:", error);
      } finally {
        isLoading.value = false;
      }
    };
    const loadActivities = async (page = 1) => {
      var _a;
      console.log("[loadActivities] Starting...", {
        page,
        referenceCode: referenceCode.value,
        isViewingOwnProfile: isViewingOwnProfile.value,
        profileLoaded: !!profile.value
      });
      try {
        const api = useApi();
        let userIdentifier = referenceCode.value;
        if (isViewingOwnProfile.value && authStore.user) {
          userIdentifier = authStore.user.reference_code || authStore.user.id;
        } else if (profile.value) {
          userIdentifier = profile.value.reference_code || profile.value.user_id || referenceCode.value;
        }
        console.log("[loadActivities] Fetching activities for:", userIdentifier, "URL:", `/api/users/${userIdentifier}/activities?page=${page}`);
        const response = await api.get(`/api/users/${userIdentifier}/activities?page=${page}`);
        console.log("[loadActivities] Response:", response);
        if (response.success && response.activities) {
          if (page === 1) {
            activities.value = response.activities;
          } else {
            activities.value = [...activities.value, ...response.activities];
          }
          if (response.meta) {
            activitiesPage.value = response.meta.current_page;
            activitiesLastPage.value = response.meta.last_page;
          }
        } else if ((_a = response.data) == null ? void 0 : _a.activities) {
          if (page === 1) {
            activities.value = response.data.activities;
          } else {
            activities.value = [...activities.value, ...response.data.activities];
          }
          if (response.data.meta) {
            activitiesPage.value = response.data.meta.current_page;
            activitiesLastPage.value = response.data.meta.last_page;
          }
        } else {
          if (page === 1) {
            activities.value = [];
          }
        }
      } catch (error) {
        console.error("Error loading activities:", error);
        if (page === 1) {
          activities.value = [];
        }
      }
    };
    const goToEditProfile = () => {
      navigateTo("/profile/edit");
    };
    watch(() => route.params.id, async () => {
      await loadProfile();
    });
    const displayName = computed(() => {
      if (!profile.value) return "";
      return profile.value.username || `${profile.value.first_name || ""} ${profile.value.last_name || ""}`.trim() || "User";
    });
    const memberSince = computed(() => {
      var _a;
      if (!((_a = profile.value) == null ? void 0 : _a.join_date)) return "";
      return new Date(profile.value.join_date).toLocaleDateString("th-TH", { year: "numeric", month: "long" });
    });
    const profileCompletionPercentage = computed(() => {
      var _a, _b, _c;
      return (_c = (_b = (_a = profile.value) == null ? void 0 : _a.profile_completion) == null ? void 0 : _b.percentage) != null ? _c : 0;
    });
    const profileMissingFields = computed(() => {
      var _a, _b, _c;
      return (_c = (_b = (_a = profile.value) == null ? void 0 : _a.profile_completion) == null ? void 0 : _b.missing_fields) != null ? _c : [];
    });
    const userQuests = computed(() => {
      var _a, _b, _c, _d;
      return {
        completed: (_b = (_a = profile.value) == null ? void 0 : _a.quests_completed) != null ? _b : 0,
        total: (_d = (_c = profile.value) == null ? void 0 : _c.quests_total) != null ? _d : 30
      };
    });
    const userBadges = computed(() => {
      var _a, _b, _c, _d;
      return {
        unlocked: (_b = (_a = profile.value) == null ? void 0 : _a.badges_unlocked) != null ? _b : 0,
        total: (_d = (_c = profile.value) == null ? void 0 : _c.badges_total) != null ? _d : 46
      };
    });
    const formatNumber = (num) => {
      if (num >= 1e6) {
        return (num / 1e6).toFixed(1).replace(/\.0$/, "") + "M";
      }
      if (num >= 1e3) {
        return (num / 1e3).toFixed(1).replace(/\.0$/, "") + "K";
      }
      return num.toLocaleString();
    };
    const friendButtonConfig = computed(() => {
      if (!friendshipStatus.value) {
        return { text: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19", icon: "fluent:person-add-24-regular", class: "bg-vikinger-purple", action: "add" };
      }
      switch (friendshipStatus.value.status) {
        case "pending_sent":
          return { text: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D", icon: "fluent:clock-24-regular", class: "bg-gray-500", action: "cancel" };
        case "pending_received":
          return { text: "\u0E22\u0E2D\u0E21\u0E23\u0E31\u0E1A\u0E04\u0E33\u0E02\u0E2D", icon: "fluent:checkmark-24-regular", class: "bg-green-500", action: "accept" };
        case "friends":
          return { text: "\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E41\u0E25\u0E49\u0E27", icon: "fluent:people-24-filled", class: "bg-vikinger-cyan", action: "unfriend" };
        default:
          return { text: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19", icon: "fluent:person-add-24-regular", class: "bg-vikinger-purple", action: "add" };
      }
    });
    const isProcessingFriend = ref(false);
    const handlePostCreated = (activity) => {
      if (activity) {
        activities.value.unshift(activity);
      }
    };
    const handleDeletePost = (postId) => {
      activities.value = activities.value.filter((a) => {
        var _a;
        const activityId = a.id;
        const nestedPostId = ((_a = a.target_resource) == null ? void 0 : _a.id) || a.action_to_id;
        return activityId !== postId && nestedPostId !== postId;
      });
    };
    const handlePostUpdated = (updatedPost) => {
      const index = activities.value.findIndex((a) => {
        var _a;
        const activityId = a.id;
        const nestedPostId = (_a = a.target_resource) == null ? void 0 : _a.id;
        return activityId === updatedPost.id || nestedPostId === updatedPost.id;
      });
      if (index !== -1) {
        if (activities.value[index].target_resource) {
          activities.value[index].target_resource = updatedPost;
        } else {
          activities.value[index] = { ...activities.value[index], ...updatedPost };
        }
      }
    };
    const countryFlag = computed(() => {
      var _a;
      if (!((_a = profile.value) == null ? void 0 : _a.location)) return "";
      const country = profile.value.location.toLowerCase();
      if (country.includes("thai") || country.includes("\u0E44\u0E17\u0E22")) return "\u{1F1F9}\u{1F1ED}";
      if (country.includes("usa") || country.includes("america")) return "\u{1F1FA}\u{1F1F8}";
      return "\u{1F30D}";
    });
    const calculateAge = (birthdate) => {
      const birth = new Date(birthdate);
      const today = /* @__PURE__ */ new Date();
      let age = today.getFullYear() - birth.getFullYear();
      const monthDiff = today.getMonth() - birth.getMonth();
      if (monthDiff < 0 || monthDiff === 0 && today.getDate() < birth.getDate()) {
        age--;
      }
      return age;
    };
    const tabs = [
      { key: "about", label: "About", icon: "fluent:person-info-24-regular" },
      { key: "timeline", label: "Timeline", icon: "fluent:timeline-24-regular" },
      { key: "friends", label: "Friends", icon: "fluent:people-24-regular" },
      { key: "photos", label: "Photos", icon: "fluent:image-24-regular" },
      { key: "videos", label: "Videos", icon: "fluent:video-24-regular" },
      { key: "badges", label: "Badges", icon: "fluent:trophy-24-regular" },
      { key: "groups", label: "Groups", icon: "fluent:people-community-24-regular" },
      { key: "events", label: "Events", icon: "fluent:calendar-24-regular" },
      { key: "blog", label: "Blog", icon: "fluent:document-text-24-regular" },
      { key: "forum", label: "Forum", icon: "fluent:chat-multiple-24-regular" },
      { key: "marketplace", label: "Marketplace", icon: "fluent:building-shop-24-regular" },
      { key: "cart", label: "Cart", icon: "fluent:cart-24-regular" }
    ];
    const mobileBottomTabs = [
      { key: "timeline", label: "Timeline", icon: "fluent:timeline-24-filled" },
      { key: "about", label: "About", icon: "fluent:person-info-24-filled" },
      { key: "friends", label: "Friends", icon: "fluent:people-24-filled" },
      { key: "photos", label: "Photos", icon: "fluent:image-24-filled" },
      { key: "badges", label: "Badges", icon: "fluent:trophy-24-filled" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-6xl mx-auto pb-20 md:pb-0" }, _attrs))} data-v-3b650ee9>`);
      if (unref(isLoading)) {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 animate-pulse" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="h-48 md:h-64 bg-gray-700 rounded-t-xl" data-v-3b650ee9${_scopeId}></div><div class="pt-20 pb-6 px-6" data-v-3b650ee9${_scopeId}><div class="h-8 bg-gray-700 rounded w-1/3 mx-auto mb-4" data-v-3b650ee9${_scopeId}></div><div class="h-4 bg-gray-700 rounded w-1/4 mx-auto" data-v-3b650ee9${_scopeId}></div></div>`);
            } else {
              return [
                createVNode("div", { class: "h-48 md:h-64 bg-gray-700 rounded-t-xl" }),
                createVNode("div", { class: "pt-20 pb-6 px-6" }, [
                  createVNode("div", { class: "h-8 bg-gray-700 rounded w-1/3 mx-auto mb-4" }),
                  createVNode("div", { class: "h-4 bg-gray-700 rounded w-1/4 mx-auto" })
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else if (unref(profile)) {
        _push(`<!--[--><input type="file" accept="image/*" class="hidden" data-v-3b650ee9><input type="file" accept="image/*" class="hidden" data-v-3b650ee9><div class="vikinger-card relative overflow-hidden mb-6 !rounded-2xl !p-0 dark:!bg-gray-900 !shadow-2xl" data-v-3b650ee9><div class="absolute inset-0 opacity-10 pointer-events-none" data-v-3b650ee9><div class="absolute inset-0 bg-[url(&#39;/images/patterns/hexagon.svg&#39;)] bg-repeat opacity-20" data-v-3b650ee9></div><div class="absolute top-0 right-0 w-96 h-96 bg-vikinger-purple/20 rounded-full blur-3xl animate-pulse" data-v-3b650ee9></div><div class="absolute bottom-0 left-0 w-96 h-96 bg-vikinger-cyan/20 rounded-full blur-3xl animate-pulse delay-1000" data-v-3b650ee9></div></div><div class="relative h-48 md:h-64 overflow-hidden" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-br from-vikinger-purple via-purple-800 to-vikinger-cyan" data-v-3b650ee9>`);
        if (unref(profile).cover_image) {
          _push(`<img${ssrRenderAttr("src", unref(profile).cover_image)}${ssrRenderAttr("alt", `${unref(displayName)}'s cover`)} class="w-full h-full object-cover opacity-90" data-v-3b650ee9>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/30 to-transparent" data-v-3b650ee9></div>`);
        if (unref(isOwnProfile)) {
          _push(`<button class="absolute top-4 right-4 px-4 py-2 bg-black/40 backdrop-blur-sm text-white rounded-xl hover:bg-black/60 transition-all flex items-center gap-2 border border-white/10" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:camera-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="hidden sm:inline text-sm font-medium" data-v-3b650ee9>Edit Cover</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="relative px-6 pb-6" data-v-3b650ee9><div class="flex flex-col md:flex-row items-center md:items-end gap-4 -mt-16 md:-mt-20" data-v-3b650ee9><div class="relative flex-shrink-0" data-v-3b650ee9><div class="absolute inset-0 -m-1 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan opacity-60 blur-sm animate-pulse" data-v-3b650ee9></div><div class="relative w-28 h-28 md:w-36 md:h-36 rounded-full overflow-hidden ring-4 ring-vikinger-cyan ring-offset-4 ring-offset-gray-900 dark:ring-offset-gray-900 shadow-2xl" data-v-3b650ee9><img${ssrRenderAttr("src", unref(profile).avatar || "/images/default-avatar.png")}${ssrRenderAttr("alt", unref(displayName))} class="w-full h-full object-cover" data-v-3b650ee9></div>`);
        if (unref(isOwnProfile)) {
          _push(`<button class="absolute top-0 right-0 p-2 bg-vikinger-purple text-white rounded-full shadow-lg hover:bg-vikinger-purple/80 transition-all hover:scale-110 border-2 border-white/30" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:camera-24-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex-1 flex flex-col md:flex-row items-center md:items-center justify-between gap-4 mt-2 md:mt-0" data-v-3b650ee9><div class="text-center md:text-left" data-v-3b650ee9><h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white flex items-center justify-center md:justify-start gap-2" data-v-3b650ee9>${ssrInterpolate(unref(displayName))} `);
        if (unref(profile).is_verified) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: "w-6 h-6 text-vikinger-cyan"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</h1>`);
        if (unref(profile).title || unref(profile).bio) {
          _push(`<p class="text-gray-500 dark:text-gray-400 text-sm mt-1 max-w-md" data-v-3b650ee9>${ssrInterpolate(unref(profile).title || ((_a = unref(profile).bio) == null ? void 0 : _a.substring(0, 60)) + (((_b = unref(profile).bio) == null ? void 0 : _b.length) > 60 ? "..." : ""))}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex gap-3 flex-shrink-0" data-v-3b650ee9>`);
        if (!unref(isOwnProfile)) {
          _push(`<!--[--><button${ssrIncludeBooleanAttr(unref(isProcessingFriend)) ? " disabled" : ""} class="${ssrRenderClass([
            "px-5 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2 shadow-lg disabled:opacity-70 hover:scale-105",
            ((_c = unref(friendshipStatus)) == null ? void 0 : _c.status) === "friends" ? "bg-vikinger-cyan/20 text-vikinger-cyan border border-vikinger-cyan/50" : "bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white"
          ])}" data-v-3b650ee9>`);
          if (unref(isProcessingFriend)) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: unref(friendButtonConfig).icon,
              class: "w-5 h-5"
            }, null, _parent));
          }
          _push(`<span class="hidden sm:inline" data-v-3b650ee9>${ssrInterpolate(unref(friendButtonConfig).text)}</span></button><button class="px-5 py-2.5 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-white rounded-xl hover:bg-gray-300 dark:hover:bg-gray-700 transition-all flex items-center gap-2 font-bold" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chat-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span class="hidden sm:inline" data-v-3b650ee9>\u0E2A\u0E48\u0E07\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21</span></button><!--]-->`);
        } else {
          _push(`<button class="px-5 py-2.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:opacity-90 transition-all flex items-center gap-2 font-bold shadow-lg hover:scale-105" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span data-v-3b650ee9>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C</span></button>`);
        }
        _push(`</div></div></div></div><div class="border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50" data-v-3b650ee9><div class="flex items-center justify-center gap-4 md:gap-8 px-6 py-4" data-v-3b650ee9><div class="stat-card group flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all cursor-pointer" data-v-3b650ee9><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:document-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><div class="text-left" data-v-3b650ee9><span class="block text-lg md:text-xl font-black text-gray-900 dark:text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).posts_count || 0)}</span><span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E42\u0E1E\u0E2A\u0E15\u0E4C</span></div></div><div class="stat-card group flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all cursor-pointer" data-v-3b650ee9><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><div class="text-left" data-v-3b650ee9><span class="block text-lg md:text-xl font-black text-gray-900 dark:text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).friends_count || 0)}</span><span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span></div></div><div class="stat-card group flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all cursor-pointer" data-v-3b650ee9><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:eye-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><div class="text-left" data-v-3b650ee9><span class="block text-lg md:text-xl font-black text-gray-900 dark:text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).visits_count || 0)}</span><span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E40\u0E02\u0E49\u0E32\u0E0A\u0E21</span></div></div></div></div></div><div class="hidden md:block vikinger-card relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900 mb-6" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none opacity-50" data-v-3b650ee9><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-5" data-v-3b650ee9></div></div><div class="relative flex items-center" data-v-3b650ee9><button class="${ssrRenderClass([
          "p-4 transition-all duration-300 flex-shrink-0 relative z-10",
          unref(canScrollLeft) ? "text-white hover:text-vikinger-cyan hover:bg-gradient-to-r hover:from-vikinger-purple/20 hover:to-transparent cursor-pointer" : "text-gray-700 cursor-not-allowed"
        ])}"${ssrIncludeBooleanAttr(!unref(canScrollLeft)) ? " disabled" : ""} data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-left-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</button><div class="flex-1 flex overflow-x-auto scrollbar-hide scroll-smooth py-2" data-v-3b650ee9><!--[-->`);
        ssrRenderList(tabs, (tab) => {
          _push(`<button class="${ssrRenderClass([
            "relative flex-shrink-0 min-w-[80px] lg:min-w-[100px] flex flex-col items-center justify-center gap-2 py-4 px-5 mx-1 transition-all duration-300 group rounded-xl",
            unref(activeTab) === tab.key ? "bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/20 border border-vikinger-cyan/30" : "hover:bg-gray-800/50 border border-transparent"
          ])}" data-v-3b650ee9><div class="relative" data-v-3b650ee9>`);
          if (unref(activeTab) === tab.key) {
            _push(`<div class="absolute inset-0 -m-2 bg-vikinger-cyan/20 blur-xl rounded-full animate-pulse" data-v-3b650ee9></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="${ssrRenderClass([
            "relative w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300",
            unref(activeTab) === tab.key ? "bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-lg scale-110" : "bg-gray-800 group-hover:bg-gray-700 group-hover:scale-105"
          ])}" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: tab.icon,
            class: [
              "w-5 h-5 transition-all duration-300",
              unref(activeTab) === tab.key ? "text-white" : "text-gray-400 group-hover:text-white"
            ]
          }, null, _parent));
          _push(`</div></div><span class="${ssrRenderClass([
            "text-xs font-bold transition-all duration-300 whitespace-nowrap",
            unref(activeTab) === tab.key ? "text-vikinger-cyan" : "text-gray-500 group-hover:text-white"
          ])}" data-v-3b650ee9>${ssrInterpolate(tab.label)}</span>`);
          if (unref(activeTab) === tab.key) {
            _push(`<div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-vikinger-cyan rounded-full shadow-lg shadow-vikinger-cyan/50" data-v-3b650ee9></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</button>`);
        });
        _push(`<!--]--></div><button class="${ssrRenderClass([
          "p-4 transition-all duration-300 flex-shrink-0 relative z-10",
          unref(canScrollRight) ? "text-white hover:text-vikinger-cyan hover:bg-gradient-to-l hover:from-vikinger-purple/20 hover:to-transparent cursor-pointer" : "text-gray-700 cursor-not-allowed"
        ])}"${ssrIncludeBooleanAttr(!unref(canScrollRight)) ? " disabled" : ""} data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-right-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</button></div></div><div class="grid grid-cols-1 lg:grid-cols-12 gap-6" data-v-3b650ee9><div class="lg:col-span-3 space-y-6" data-v-3b650ee9>`);
        if (unref(isOwnProfile)) {
          _push(ssrRenderComponent(_sfc_main$b, {
            percentage: unref(profileCompletionPercentage),
            "missing-fields": unref(profileMissingFields),
            quests: unref(userQuests),
            badges: unref(userBadges),
            onEditProfile: goToEditProfile
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(isOwnProfile)) {
          _push(ssrRenderComponent(_sfc_main$5, null, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="vikinger-card vikinger-card-hover overflow-hidden !rounded-2xl !p-0" data-v-3b650ee9><div class="relative bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 px-3 py-2.5" data-v-3b650ee9><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-10" data-v-3b650ee9></div><h3 class="relative text-sm font-bold text-white flex items-center gap-2" data-v-3b650ee9><div class="w-6 h-6 rounded-md bg-white/20 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:wallet-24-filled",
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(`</div> \u0E41\u0E15\u0E49\u0E21 &amp; \u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19 </h3></div><div class="p-3 space-y-2" data-v-3b650ee9><div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl" data-v-3b650ee9><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-md flex-shrink-0" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-3b650ee9><p class="text-[10px] text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold" data-v-3b650ee9>\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21</p><p class="text-xl font-black text-gray-900 dark:text-white" data-v-3b650ee9>${ssrInterpolate(formatNumber(unref(profile).points || unref(profile).pp || 0))}</p></div></div><div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl" data-v-3b650ee9><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-md flex-shrink-0" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:money-24-filled",
          class: "w-5 h-5 text-white"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-3b650ee9><p class="text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold" data-v-3b650ee9>Wallet</p><p class="text-xl font-black text-gray-900 dark:text-white" data-v-3b650ee9>\u0E3F${ssrInterpolate(formatNumber(unref(profile).wallet || 0))}</p></div></div><div class="flex gap-2 pt-1" data-v-3b650ee9><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-upload-24-regular",
          class: "w-3.5 h-3.5 text-amber-500"
        }, null, _parent));
        _push(` \u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 </button><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:history-24-regular",
          class: "w-3.5 h-3.5 text-emerald-500"
        }, null, _parent));
        _push(` \u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34 </button></div></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none overflow-hidden" data-v-3b650ee9><div class="absolute top-0 right-0 w-60 h-60 bg-vikinger-purple/10 rounded-full blur-3xl animate-pulse" data-v-3b650ee9></div><div class="absolute bottom-0 left-0 w-60 h-60 bg-vikinger-cyan/10 rounded-full blur-3xl animate-pulse delay-500" data-v-3b650ee9></div></div><div class="relative bg-gradient-to-r from-vikinger-purple via-purple-600 to-vikinger-cyan p-4" data-v-3b650ee9><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-10" data-v-3b650ee9></div><div class="relative flex items-center justify-between" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2 drop-shadow-lg" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:trophy-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</div> Level &amp; Experience </h3><div class="flex items-center gap-2 px-4 py-1.5 bg-white/20 rounded-full backdrop-blur-sm border border-white/30" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: "w-4 h-4 text-yellow-300"
        }, null, _parent));
        _push(`<span class="text-white font-black text-sm" data-v-3b650ee9>LV.${ssrInterpolate(unref(profile).level || 1)}</span></div></div></div><div class="relative p-5 space-y-5" data-v-3b650ee9><div class="flex items-center justify-center py-2" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="absolute inset-0 -m-4 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full blur-xl opacity-30 animate-pulse" data-v-3b650ee9></div><svg class="relative w-36 h-36 transform -rotate-90" viewBox="0 0 100 100" data-v-3b650ee9><circle cx="50" cy="50" r="42" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-700" data-v-3b650ee9></circle><circle cx="50" cy="50" r="42" stroke="url(#levelGradient2)" stroke-width="6" fill="none" stroke-linecap="round"${ssrRenderAttr("stroke-dasharray", `${(unref(profile).level_progress || 65) * 2.64} 264`)} class="transition-all duration-1000 drop-shadow-[0_0_10px_rgba(97,93,250,0.5)]" data-v-3b650ee9></circle><circle cx="50" cy="50" r="35" stroke="currentColor" stroke-width="1" fill="none" class="text-gray-700" data-v-3b650ee9></circle><defs data-v-3b650ee9><linearGradient id="levelGradient2" x1="0%" y1="0%" x2="100%" y2="0%" data-v-3b650ee9><stop offset="0%" style="${ssrRenderStyle({ "stop-color": "#9333ea" })}" data-v-3b650ee9></stop><stop offset="50%" style="${ssrRenderStyle({ "stop-color": "#615dfa" })}" data-v-3b650ee9></stop><stop offset="100%" style="${ssrRenderStyle({ "stop-color": "#23d2e2" })}" data-v-3b650ee9></stop></linearGradient></defs></svg><div class="absolute inset-0 flex items-center justify-center" data-v-3b650ee9><div class="text-center" data-v-3b650ee9><span class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-vikinger-purple to-vikinger-cyan" data-v-3b650ee9>${ssrInterpolate(unref(profile).level || 1)}</span><p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest" data-v-3b650ee9>LEVEL</p></div></div></div></div><div class="space-y-3" data-v-3b650ee9><div class="flex justify-between text-sm" data-v-3b650ee9><span class="text-gray-400 font-medium" data-v-3b650ee9>Experience Points</span><span class="text-vikinger-cyan font-bold" data-v-3b650ee9>${ssrInterpolate(unref(profile).level_progress || 65)}%</span></div><div class="relative h-4 bg-gray-800 rounded-full overflow-hidden border border-gray-700" data-v-3b650ee9><div class="absolute inset-y-0 left-0 bg-gradient-to-r from-vikinger-purple via-purple-500 to-vikinger-cyan rounded-full transition-all duration-1000 shadow-lg shadow-vikinger-purple/30" style="${ssrRenderStyle({ width: (unref(profile).level_progress || 65) + "%" })}" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" data-v-3b650ee9></div></div><div class="absolute inset-0 flex items-center justify-center" data-v-3b650ee9><span class="text-[10px] font-bold text-white drop-shadow-lg" data-v-3b650ee9>${ssrInterpolate(((unref(profile).points || unref(profile).pp || 0) * 0.65).toLocaleString())} / ${ssrInterpolate(((unref(profile).points || unref(profile).pp || 0) * 0.65 + 1e3).toLocaleString())} XP </span></div></div></div><div class="relative overflow-hidden p-4 bg-gradient-to-r from-yellow-900/30 via-amber-900/20 to-yellow-900/30 rounded-xl border border-yellow-600/30" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-400/5 to-transparent animate-shimmer" data-v-3b650ee9></div><div class="relative flex items-center gap-4" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="absolute inset-0 bg-yellow-400 rounded-xl blur-md opacity-40 animate-pulse" data-v-3b650ee9></div><div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500 flex items-center justify-center shadow-lg border border-yellow-300/30" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:gift-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div></div><div class="flex-1" data-v-3b650ee9><p class="text-sm font-bold text-white" data-v-3b650ee9>Next Level Reward</p><p class="text-xs text-yellow-400/80" data-v-3b650ee9>Unlock: +500 points &amp; special badge</p></div>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-right-24-regular",
          class: "w-5 h-5 text-yellow-400"
        }, null, _parent));
        _push(`</div></div></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none" data-v-3b650ee9><div class="absolute top-0 right-0 w-32 h-32 bg-vikinger-cyan/5 rounded-full blur-2xl" data-v-3b650ee9></div></div><div class="relative" data-v-3b650ee9><div class="flex items-center justify-between mb-5" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-info-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`</div> About Me </h3><button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5 text-gray-400 hover:text-white"
        }, null, _parent));
        _push(`</button></div>`);
        if (unref(profile).bio) {
          _push(`<div class="mb-6" data-v-3b650ee9><p class="text-gray-300 text-sm leading-relaxed" data-v-3b650ee9>${ssrInterpolate(unref(profile).bio)}</p></div>`);
        } else {
          _push(`<div class="mb-6 p-4 bg-gray-800/50 rounded-xl border border-dashed border-gray-600" data-v-3b650ee9><p class="text-gray-500 text-sm text-center italic flex items-center justify-center gap-2" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:text-description-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` No bio yet... </p></div>`);
        }
        _push(`<div class="space-y-3" data-v-3b650ee9>`);
        if (unref(memberSince)) {
          _push(`<div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors" data-v-3b650ee9><div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-24-regular",
            class: "w-5 h-5 text-blue-400"
          }, null, _parent));
          _push(`</div><div class="flex-1" data-v-3b650ee9><span class="text-xs text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D</span><p class="text-white font-medium" data-v-3b650ee9>${ssrInterpolate(unref(memberSince))}</p></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(profile).location) {
          _push(`<div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors" data-v-3b650ee9><div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:location-24-regular",
            class: "w-5 h-5 text-green-400"
          }, null, _parent));
          _push(`</div><div class="flex-1" data-v-3b650ee9><span class="text-xs text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</span><p class="text-white font-medium" data-v-3b650ee9>${ssrInterpolate(unref(profile).location)}</p></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors" data-v-3b650ee9><div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center text-xl" data-v-3b650ee9>${ssrInterpolate(unref(countryFlag) || "\u{1F30D}")}</div><div class="flex-1" data-v-3b650ee9><span class="text-xs text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28</span><p class="text-white font-medium" data-v-3b650ee9>Thailand</p></div></div>`);
        if (unref(profile).birthdate) {
          _push(`<div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors" data-v-3b650ee9><div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-24-regular",
            class: "w-5 h-5 text-purple-400"
          }, null, _parent));
          _push(`</div><div class="flex-1" data-v-3b650ee9><span class="text-xs text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E2D\u0E32\u0E22\u0E38</span><p class="text-white font-medium" data-v-3b650ee9>${ssrInterpolate(calculateAge(unref(profile).birthdate))} \u0E1B\u0E35</p></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(profile).website) {
          _push(`<div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors group" data-v-3b650ee9><div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:globe-24-regular",
            class: "w-5 h-5 text-cyan-400"
          }, null, _parent));
          _push(`</div><div class="flex-1" data-v-3b650ee9><span class="text-xs text-gray-400 uppercase tracking-wider" data-v-3b650ee9>\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C</span><a${ssrRenderAttr("href", unref(profile).website)} target="_blank" class="text-vikinger-cyan font-medium hover:underline block truncate" data-v-3b650ee9>${ssrInterpolate(unref(profile).website.replace(/^https?:\/\//, ""))}</a></div>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:open-24-regular",
            class: "w-4 h-4 text-gray-400 group-hover:text-vikinger-cyan transition-colors"
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none" data-v-3b650ee9><div class="absolute bottom-0 left-0 w-32 h-32 bg-yellow-500/5 rounded-full blur-2xl" data-v-3b650ee9></div></div><div class="relative" data-v-3b650ee9><div class="flex items-center justify-between mb-5" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:trophy-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`</div> Badges <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full" data-v-3b650ee9>${ssrInterpolate(unref(profile).badges_count || 0)}</span></h3><button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5 text-gray-400 hover:text-white"
        }, null, _parent));
        _push(`</button></div><div class="grid grid-cols-5 gap-2 mb-4" data-v-3b650ee9><!--[-->`);
        ssrRenderList(10, (i) => {
          _push(`<div class="${ssrRenderClass([
            "group relative aspect-square rounded-xl flex items-center justify-center transition-all cursor-pointer hover:scale-110",
            i <= 3 ? "bg-gradient-to-br from-yellow-500/20 to-amber-600/20 border border-yellow-500/30" : "bg-gray-800/50 border border-gray-700"
          ])}" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: i <= 3 ? "fluent:trophy-24-filled" : "fluent:trophy-24-regular",
            class: [
              "w-6 h-6 transition-colors",
              i <= 3 ? "text-yellow-400" : "text-gray-600 group-hover:text-gray-400"
            ]
          }, null, _parent));
          _push(`<div class="absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10 border border-gray-700" data-v-3b650ee9>${ssrInterpolate(i <= 3 ? "Badge Unlocked!" : "Locked")}</div></div>`);
        });
        _push(`<!--]--></div><button class="w-full py-2.5 px-4 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2 transition-all" data-v-3b650ee9><span data-v-3b650ee9>View All Badges</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-right-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none" data-v-3b650ee9><div class="absolute top-0 left-0 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl" data-v-3b650ee9></div></div><div class="relative" data-v-3b650ee9><div class="flex items-center justify-between mb-5" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`</div> Friends <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full" data-v-3b650ee9>${ssrInterpolate(unref(profile).friends_count || 0)}</span></h3><button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5 text-gray-400 hover:text-white"
        }, null, _parent));
        _push(`</button></div><div class="flex items-center -space-x-3 mb-4" data-v-3b650ee9><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="relative group" data-v-3b650ee9><div class="w-12 h-12 rounded-full bg-gradient-to-br from-gray-600 to-gray-700 border-3 border-gray-900 flex items-center justify-center overflow-hidden hover:scale-110 hover:z-10 transition-transform cursor-pointer shadow-lg" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-24-regular",
            class: "w-6 h-6 text-gray-400"
          }, null, _parent));
          _push(`</div></div>`);
        });
        _push(`<!--]-->`);
        if ((unref(profile).friends_count || 0) > 6) {
          _push(`<div class="w-12 h-12 rounded-full bg-gray-800 border-3 border-gray-900 flex items-center justify-center text-xs font-bold text-gray-400 hover:bg-gray-700 cursor-pointer transition-colors" data-v-3b650ee9> +${ssrInterpolate((unref(profile).friends_count || 0) - 6)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="grid grid-cols-3 gap-2 mb-4" data-v-3b650ee9><div class="text-center p-2 bg-gray-800/50 rounded-lg" data-v-3b650ee9><p class="text-lg font-bold text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).mutual_friends || 0)}</p><p class="text-xs text-gray-400" data-v-3b650ee9>Mutual</p></div><div class="text-center p-2 bg-gray-800/50 rounded-lg" data-v-3b650ee9><p class="text-lg font-bold text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).online_friends || 0)}</p><p class="text-xs text-gray-400" data-v-3b650ee9>Online</p></div><div class="text-center p-2 bg-gray-800/50 rounded-lg" data-v-3b650ee9><p class="text-lg font-bold text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).new_friends || 0)}</p><p class="text-xs text-gray-400" data-v-3b650ee9>New</p></div></div><button class="w-full py-2.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2 transition-all" data-v-3b650ee9><span data-v-3b650ee9>View All Friends</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-right-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div></div></div><div class="lg:col-span-6 space-y-6" data-v-3b650ee9>`);
        if (unref(activeTab) === "timeline") {
          _push(`<!--[-->`);
          if (unref(isOwnProfile)) {
            _push(ssrRenderComponent(_sfc_main$c, { onPostCreated: handlePostCreated }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          if (unref(activities).length > 0) {
            _push(`<!--[--><!--[-->`);
            ssrRenderList(unref(activities), (activity) => {
              _push(ssrRenderComponent(FeedPost, {
                key: activity.id,
                post: activity,
                onDeleteSuccess: handleDeletePost,
                onPostUpdated: handlePostUpdated
              }, null, _parent));
            });
            _push(`<!--]-->`);
            if (unref(hasMoreActivities)) {
              _push(`<div class="text-center py-4" data-v-3b650ee9><button${ssrIncludeBooleanAttr(unref(isLoadingMore)) ? " disabled" : ""} class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto" data-v-3b650ee9>`);
              if (unref(isLoadingMore)) {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:spinner-ios-20-regular",
                  class: "w-5 h-5 animate-spin"
                }, null, _parent));
              } else {
                _push(`<!---->`);
              }
              _push(`<span data-v-3b650ee9>${ssrInterpolate(unref(isLoadingMore) ? "Loading..." : "Load More")}</span></button></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<!--]-->`);
          } else {
            _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:document-24-regular",
                    class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                  }, null, _parent2, _scopeId));
                  _push2(`<p class="text-gray-400" data-v-3b650ee9${_scopeId}>No posts yet</p>`);
                  if (unref(isOwnProfile)) {
                    _push2(`<p class="text-sm text-gray-500 mt-2" data-v-3b650ee9${_scopeId}> Share your first post with friends! </p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: "fluent:document-24-regular",
                      class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                    }),
                    createVNode("p", { class: "text-gray-400" }, "No posts yet"),
                    unref(isOwnProfile) ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-sm text-gray-500 mt-2"
                    }, " Share your first post with friends! ")) : createCommentVNode("", true)
                  ];
                }
              }),
              _: 1
            }, _parent));
          }
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "about") {
          _push(ssrRenderComponent(_sfc_main$4, {
            profile: unref(profile),
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "friends") {
          _push(ssrRenderComponent(FriendsList, {
            "user-id": unref(profile).reference_code || unref(profile).user_id,
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "photos") {
          _push(ssrRenderComponent(PhotosGallery, {
            "user-id": unref(profile).reference_code || unref(profile).user_id,
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "videos") {
          _push(ssrRenderComponent(_sfc_main$1, {
            "user-id": unref(profile).reference_code || unref(profile).user_id,
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "badges") {
          _push(ssrRenderComponent(BadgesDisplay, {
            "user-id": unref(profile).reference_code || unref(profile).user_id,
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "groups") {
          _push(ssrRenderComponent(_sfc_main$3, {
            "user-id": unref(profile).reference_code || unref(profile).user_id,
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "events") {
          _push(ssrRenderComponent(_sfc_main$2, {
            "user-id": unref(profile).reference_code || unref(profile).user_id,
            "is-own-profile": unref(isOwnProfile)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "blog") {
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:document-text-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-400" data-v-3b650ee9${_scopeId}>Blog posts coming soon</p>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:document-text-24-regular",
                    class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                  }),
                  createVNode("p", { class: "text-gray-400" }, "Blog posts coming soon")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "forum") {
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chat-multiple-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-400" data-v-3b650ee9${_scopeId}>Forum discussions coming soon</p>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:chat-multiple-24-regular",
                    class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                  }),
                  createVNode("p", { class: "text-gray-400" }, "Forum discussions coming soon")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "marketplace") {
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:building-shop-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-400" data-v-3b650ee9${_scopeId}>Marketplace coming soon</p><p class="text-gray-500 text-sm mt-2" data-v-3b650ee9${_scopeId}>Buy and sell items with other users</p>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:building-shop-24-regular",
                    class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                  }),
                  createVNode("p", { class: "text-gray-400" }, "Marketplace coming soon"),
                  createVNode("p", { class: "text-gray-500 text-sm mt-2" }, "Buy and sell items with other users")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        if (unref(activeTab) === "cart") {
          _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:cart-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-400" data-v-3b650ee9${_scopeId}>Your cart is empty</p><p class="text-gray-500 text-sm mt-2" data-v-3b650ee9${_scopeId}>Items you add to cart will appear here</p>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:cart-24-regular",
                    class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                  }),
                  createVNode("p", { class: "text-gray-400" }, "Your cart is empty"),
                  createVNode("p", { class: "text-gray-500 text-sm mt-2" }, "Items you add to cart will appear here")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="lg:col-span-3 space-y-6" data-v-3b650ee9><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none overflow-hidden" data-v-3b650ee9><div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl animate-pulse" data-v-3b650ee9></div><div class="absolute -bottom-20 -left-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl animate-pulse delay-500" data-v-3b650ee9></div></div><div class="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 p-4" data-v-3b650ee9><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-10" data-v-3b650ee9></div><h3 class="relative text-lg font-black text-white flex items-center gap-2 drop-shadow-lg" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:data-trending-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</div> \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 </h3></div><div class="relative p-5 space-y-4" data-v-3b650ee9><div class="grid grid-cols-2 gap-3" data-v-3b650ee9><div class="group relative overflow-hidden p-4 bg-gradient-to-br from-violet-900/40 to-purple-900/30 rounded-xl border border-violet-500/30 hover:border-violet-400/50 transition-all cursor-pointer hover:scale-[1.02]" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-violet-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="relative text-center" data-v-3b650ee9><div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:document-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><p class="text-2xl font-black text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).posts_count || 0)}</p><p class="text-xs text-gray-400 font-medium uppercase tracking-wider" data-v-3b650ee9>\u0E42\u0E1E\u0E2A\u0E15\u0E4C</p></div></div><div class="group relative overflow-hidden p-4 bg-gradient-to-br from-pink-900/40 to-rose-900/30 rounded-xl border border-pink-500/30 hover:border-pink-400/50 transition-all cursor-pointer hover:scale-[1.02]" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-pink-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="relative text-center" data-v-3b650ee9><div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:heart-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><p class="text-2xl font-black text-white" data-v-3b650ee9>${ssrInterpolate((unref(profile).likes_received || 0).toLocaleString())}</p><p class="text-xs text-gray-400 font-medium uppercase tracking-wider" data-v-3b650ee9>\u0E16\u0E39\u0E01\u0E43\u0E08</p></div></div><div class="group relative overflow-hidden p-4 bg-gradient-to-br from-blue-900/40 to-indigo-900/30 rounded-xl border border-blue-500/30 hover:border-blue-400/50 transition-all cursor-pointer hover:scale-[1.02]" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="relative text-center" data-v-3b650ee9><div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chat-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><p class="text-2xl font-black text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).comments_count || 0)}</p><p class="text-xs text-gray-400 font-medium uppercase tracking-wider" data-v-3b650ee9>\u0E04\u0E2D\u0E21\u0E40\u0E21\u0E19\u0E15\u0E4C</p></div></div><div class="group relative overflow-hidden p-4 bg-gradient-to-br from-teal-900/40 to-emerald-900/30 rounded-xl border border-teal-500/30 hover:border-teal-400/50 transition-all cursor-pointer hover:scale-[1.02]" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-teal-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="relative text-center" data-v-3b650ee9><div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:share-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><p class="text-2xl font-black text-white" data-v-3b650ee9>${ssrInterpolate(unref(profile).shares_count || 0)}</p><p class="text-xs text-gray-400 font-medium uppercase tracking-wider" data-v-3b650ee9>\u0E41\u0E0A\u0E23\u0E4C</p></div></div></div><div class="relative overflow-hidden p-4 bg-gradient-to-r from-orange-900/40 via-red-900/30 to-orange-900/40 rounded-xl border border-orange-500/30" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-t from-orange-600/10 to-transparent animate-pulse" data-v-3b650ee9></div><div class="relative flex items-center justify-between" data-v-3b650ee9><div class="flex items-center gap-4" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="absolute inset-0 bg-orange-500 rounded-xl blur-md opacity-50 animate-pulse" data-v-3b650ee9></div><div class="relative w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 via-red-500 to-orange-600 flex items-center justify-center shadow-lg border border-orange-300/30" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:fire-24-filled",
          class: "w-7 h-7 text-white animate-bounce"
        }, null, _parent));
        _push(`</div></div><div data-v-3b650ee9><p class="text-sm font-bold text-white" data-v-3b650ee9>Activity Streak</p><p class="text-xs text-orange-400/80" data-v-3b650ee9>\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E15\u0E48\u0E2D\u0E40\u0E19\u0E37\u0E48\u0E2D\u0E07</p></div></div><div class="text-right" data-v-3b650ee9><p class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-400" data-v-3b650ee9>${ssrInterpolate(unref(profile).streak || 7)}</p><p class="text-xs text-gray-400 font-medium uppercase" data-v-3b650ee9>\u0E27\u0E31\u0E19</p></div></div></div></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="absolute inset-0 pointer-events-none overflow-hidden" data-v-3b650ee9><div class="absolute top-0 left-0 w-40 h-40 bg-yellow-500/10 rounded-full blur-3xl animate-pulse" data-v-3b650ee9></div></div><div class="relative bg-gradient-to-r from-yellow-500 via-amber-500 to-orange-500 p-4" data-v-3b650ee9><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-10" data-v-3b650ee9></div><h3 class="relative text-lg font-black text-white flex items-center gap-2 drop-shadow-lg" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:ribbon-star-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</div> \u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08 <span class="ml-auto px-3 py-1 bg-white/20 rounded-full text-sm font-bold" data-v-3b650ee9>3/10</span></h3></div><div class="relative p-5 space-y-3" data-v-3b650ee9><div class="group relative overflow-hidden flex items-center gap-4 p-3 bg-gradient-to-r from-green-900/30 to-emerald-900/20 rounded-xl border border-green-500/30 hover:border-green-400/50 transition-all cursor-pointer" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-green-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:document-checkmark-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-3b650ee9><p class="text-sm font-bold text-white" data-v-3b650ee9>\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E41\u0E23\u0E01</p><p class="text-xs text-gray-400 truncate" data-v-3b650ee9>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E41\u0E23\u0E01\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div><div class="flex items-center gap-2" data-v-3b650ee9><span class="text-xs text-green-400 font-bold" data-v-3b650ee9>+50 XP</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-6 h-6 text-green-500"
        }, null, _parent));
        _push(`</div></div><div class="group relative overflow-hidden flex items-center gap-4 p-3 bg-gradient-to-r from-blue-900/30 to-indigo-900/20 rounded-xl border border-blue-500/30 hover:border-blue-400/50 transition-all cursor-pointer" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-3b650ee9><p class="text-sm font-bold text-white" data-v-3b650ee9>\u0E2A\u0E31\u0E07\u0E04\u0E21\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C</p><p class="text-xs text-gray-400 truncate" data-v-3b650ee9>\u0E21\u0E35\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 10 \u0E04\u0E19</p><div class="mt-1.5 h-1.5 bg-gray-700 rounded-full overflow-hidden" data-v-3b650ee9><div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full" style="${ssrRenderStyle({ width: Math.min((unref(profile).friends_count || 0) * 10, 100) + "%" })}" data-v-3b650ee9></div></div></div><div class="text-right" data-v-3b650ee9><span class="text-xs text-blue-400 font-bold" data-v-3b650ee9>${ssrInterpolate(Math.min(unref(profile).friends_count || 0, 10))}/10</span></div></div><div class="${ssrRenderClass([(unref(profile).points || unref(profile).pp || 0) >= 100 ? "bg-gradient-to-r from-amber-900/30 to-orange-900/20 border-amber-500/30 hover:border-amber-400/50" : "bg-gray-800/30 border-gray-700 hover:border-gray-600", "group relative overflow-hidden flex items-center gap-4 p-3 rounded-xl border transition-all cursor-pointer"])}" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-amber-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="${ssrRenderClass([(unref(profile).points || unref(profile).pp || 0) >= 100 ? "bg-gradient-to-br from-amber-400 to-orange-500" : "bg-gray-700", "relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"])}" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: ["w-6 h-6", (unref(profile).points || unref(profile).pp || 0) >= 100 ? "text-white" : "text-gray-500"]
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-3b650ee9><p class="text-sm font-bold text-white" data-v-3b650ee9>\u0E19\u0E31\u0E01\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21</p><p class="text-xs text-gray-400 truncate" data-v-3b650ee9>\u0E2A\u0E30\u0E2A\u0E21 100 \u0E41\u0E15\u0E49\u0E21</p></div><div class="flex items-center gap-2" data-v-3b650ee9><span class="${ssrRenderClass([(unref(profile).points || unref(profile).pp || 0) >= 100 ? "text-amber-400" : "text-gray-500", "text-xs font-bold"])}" data-v-3b650ee9>+100 XP</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: (unref(profile).points || unref(profile).pp || 0) >= 100 ? "fluent:checkmark-circle-24-filled" : "fluent:lock-closed-24-regular",
          class: (unref(profile).points || unref(profile).pp || 0) >= 100 ? "w-6 h-6 text-amber-500" : "w-5 h-5 text-gray-500"
        }, null, _parent));
        _push(`</div></div><div class="${ssrRenderClass([(unref(profile).level || 1) >= 5 ? "bg-gradient-to-r from-purple-900/30 to-violet-900/20 border-purple-500/30 hover:border-purple-400/50" : "bg-gray-800/30 border-gray-700 hover:border-gray-600", "group relative overflow-hidden flex items-center gap-4 p-3 rounded-xl border transition-all cursor-pointer"])}" data-v-3b650ee9><div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-3b650ee9></div><div class="${ssrRenderClass([(unref(profile).level || 1) >= 5 ? "bg-gradient-to-br from-purple-400 to-violet-500" : "bg-gray-700", "relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"])}" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:trophy-24-filled",
          class: ["w-6 h-6", (unref(profile).level || 1) >= 5 ? "text-white" : "text-gray-500"]
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-3b650ee9><p class="text-sm font-bold text-white" data-v-3b650ee9>\u0E1C\u0E39\u0E49\u0E0A\u0E33\u0E19\u0E32\u0E0D</p><p class="text-xs text-gray-400 truncate" data-v-3b650ee9>\u0E16\u0E36\u0E07 Level 5</p></div><div class="flex items-center gap-2" data-v-3b650ee9><span class="${ssrRenderClass([(unref(profile).level || 1) >= 5 ? "text-purple-400" : "text-gray-500", "text-xs font-bold"])}" data-v-3b650ee9>+200 XP</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: (unref(profile).level || 1) >= 5 ? "fluent:checkmark-circle-24-filled" : "fluent:lock-closed-24-regular",
          class: (unref(profile).level || 1) >= 5 ? "w-6 h-6 text-purple-500" : "w-5 h-5 text-gray-500"
        }, null, _parent));
        _push(`</div></div><button class="w-full py-3 px-4 bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all mt-2" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:trophy-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span data-v-3b650ee9>\u0E14\u0E39\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></button></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="flex items-center justify-between mb-5" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:video-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`</div> Stream Box </h3><div class="flex items-center gap-2" data-v-3b650ee9><span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded-full flex items-center gap-1" data-v-3b650ee9><span class="w-2 h-2 bg-red-500 rounded-full animate-pulse" data-v-3b650ee9></span> OFFLINE </span></div></div><div class="relative aspect-video bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl overflow-hidden border border-gray-700" data-v-3b650ee9><div class="absolute inset-0 flex flex-col items-center justify-center" data-v-3b650ee9><div class="relative mb-4" data-v-3b650ee9><div class="absolute inset-0 bg-vikinger-purple/30 rounded-full blur-xl animate-pulse" data-v-3b650ee9></div><div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center border border-gray-600" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:video-off-24-regular",
          class: "w-10 h-10 text-gray-500"
        }, null, _parent));
        _push(`</div></div><p class="text-gray-400 text-sm font-medium" data-v-3b650ee9>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E16\u0E48\u0E32\u0E22\u0E17\u0E2D\u0E14\u0E2A\u0E14</p><p class="text-gray-500 text-xs mt-1" data-v-3b650ee9>No active stream</p></div>`);
        if (unref(isOwnProfile)) {
          _push(`<button class="absolute bottom-4 left-1/2 -translate-x-1/2 px-6 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl font-bold text-sm hover:from-red-600 hover:to-pink-700 transition-all flex items-center gap-2 shadow-lg" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:video-24-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` Go Live </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="flex items-center justify-between mb-5" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:image-multiple-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`</div> Photos <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full" data-v-3b650ee9>${ssrInterpolate(unref(profile).photos_count || 0)}</span></h3><button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5 text-gray-400 hover:text-white"
        }, null, _parent));
        _push(`</button></div><div class="grid grid-cols-4 gap-1.5" data-v-3b650ee9><!--[-->`);
        ssrRenderList(12, (i) => {
          _push(`<div class="group relative aspect-square bg-gradient-to-br from-gray-800 to-gray-700 rounded-lg overflow-hidden cursor-pointer hover:scale-105 transition-transform" data-v-3b650ee9><div class="absolute inset-0 flex items-center justify-center" data-v-3b650ee9>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:image-24-regular",
            class: "w-5 h-5 text-gray-600 group-hover:text-gray-500 transition-colors"
          }, null, _parent));
          _push(`</div><div class="absolute inset-0 bg-vikinger-purple/0 group-hover:bg-vikinger-purple/20 transition-colors" data-v-3b650ee9></div></div>`);
        });
        _push(`<!--]--></div><button class="w-full mt-4 py-2.5 px-4 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2 transition-all" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:image-multiple-24-regular",
          class: "w-4 h-4 text-vikinger-cyan"
        }, null, _parent));
        _push(`<span data-v-3b650ee9>\u0E14\u0E39\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></button></div></div><div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="flex items-center justify-between mb-5" data-v-3b650ee9><h3 class="text-lg font-black text-white flex items-center gap-2" data-v-3b650ee9><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-community-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`</div> Groups <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full" data-v-3b650ee9>${ssrInterpolate(unref(profile).groups_count || 0)}</span></h3><button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5 text-gray-400 hover:text-white"
        }, null, _parent));
        _push(`</button></div><div class="text-center py-8 px-4" data-v-3b650ee9><div class="relative w-20 h-20 mx-auto mb-4" data-v-3b650ee9><div class="absolute inset-0 bg-green-500/20 rounded-full blur-xl animate-pulse" data-v-3b650ee9></div><div class="relative w-full h-full rounded-full bg-gradient-to-br from-gray-800 to-gray-700 flex items-center justify-center border border-gray-600" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-community-24-regular",
          class: "w-10 h-10 text-gray-500"
        }, null, _parent));
        _push(`</div></div><p class="text-gray-400 text-sm font-medium" data-v-3b650ee9>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E25\u0E38\u0E48\u0E21</p><p class="text-gray-500 text-xs mt-1" data-v-3b650ee9>No groups joined yet</p><button class="mt-4 px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-medium text-sm hover:from-green-600 hover:to-emerald-700 transition-all flex items-center gap-2 mx-auto" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:search-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E2A\u0E33\u0E23\u0E27\u0E08\u0E01\u0E25\u0E38\u0E48\u0E21 </button></div></div></div></div></div><!--]-->`);
      } else {
        _push(ssrRenderComponent(_sfc_main$a, { class: "bg-gray-800 border-gray-700 text-center py-12" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-warning-24-regular",
                class: "w-16 h-16 text-gray-600 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-400" data-v-3b650ee9${_scopeId}>Profile not found</p>`);
              _push2(ssrRenderComponent(_component_NuxtLink, {
                to: "/play/newsfeed",
                class: "mt-4 inline-block text-vikinger-purple hover:underline"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Go back to newsfeed `);
                  } else {
                    return [
                      createTextVNode(" Go back to newsfeed ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:person-warning-24-regular",
                  class: "w-16 h-16 text-gray-600 mx-auto mb-4"
                }),
                createVNode("p", { class: "text-gray-400" }, "Profile not found"),
                createVNode(_component_NuxtLink, {
                  to: "/play/newsfeed",
                  class: "mt-4 inline-block text-vikinger-purple hover:underline"
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Go back to newsfeed ")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      ssrRenderTeleport(_push, (_push2) => {
        var _a2, _b2;
        if (unref(showCoverModal)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-v-3b650ee9><div class="absolute inset-0 bg-black/70 backdrop-blur-sm" data-v-3b650ee9></div><div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-gray-700" data-v-3b650ee9><div class="flex items-center justify-between p-4 border-b border-gray-700" data-v-3b650ee9><h3 class="text-xl font-bold text-white flex items-center gap-2" data-v-3b650ee9>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:image-edit-24-regular",
            class: "w-6 h-6 text-vikinger-cyan"
          }, null, _parent));
          _push2(` Edit Cover Photo </h3><button class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors" data-v-3b650ee9>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`</button></div><div class="p-6" data-v-3b650ee9><div class="relative aspect-[3/1] bg-gray-700 rounded-lg overflow-hidden mb-6" data-v-3b650ee9>`);
          if (unref(coverPreview) || ((_a2 = unref(profile)) == null ? void 0 : _a2.cover_photo)) {
            _push2(`<img${ssrRenderAttr("src", unref(coverPreview) || ((_b2 = unref(profile)) == null ? void 0 : _b2.cover_photo))} alt="Cover Preview" class="w-full h-full object-cover" data-v-3b650ee9>`);
          } else {
            _push2(`<div class="w-full h-full flex items-center justify-center" data-v-3b650ee9><div class="text-center" data-v-3b650ee9>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:image-24-regular",
              class: "w-16 h-16 text-gray-500 mx-auto mb-2"
            }, null, _parent));
            _push2(`<p class="text-gray-500" data-v-3b650ee9>No cover photo</p></div></div>`);
          }
          if (unref(coverPreview)) {
            _push2(`<div class="absolute top-2 left-2 px-2 py-1 bg-green-500/80 text-white text-xs rounded-lg" data-v-3b650ee9> New Photo Selected </div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><div class="space-y-4" data-v-3b650ee9><button class="w-full py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center justify-center gap-2" data-v-3b650ee9>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:arrow-upload-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(` Choose Image from Device </button><p class="text-center text-gray-500 text-sm" data-v-3b650ee9> Recommended size: 1200 x 400 pixels. Max file size: 5MB </p></div></div><div class="flex items-center justify-end gap-3 p-4 border-t border-gray-700" data-v-3b650ee9><button class="px-5 py-2.5 text-gray-400 hover:text-white transition-colors" data-v-3b650ee9> Cancel </button><button${ssrIncludeBooleanAttr(!unref(coverPreview) || unref(isUploadingCover)) ? " disabled" : ""} class="${ssrRenderClass([
            "px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2",
            unref(coverPreview) && !unref(isUploadingCover) ? "bg-vikinger-purple hover:bg-vikinger-purple/80 text-white" : "bg-gray-700 text-gray-500 cursor-not-allowed"
          ])}" data-v-3b650ee9>`);
          if (unref(isUploadingCover)) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:save-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
          }
          _push2(` ${ssrInterpolate(unref(isUploadingCover) ? "Saving..." : "Save Cover")}</button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        var _a2, _b2;
        if (unref(showAvatarModal)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-v-3b650ee9><div class="absolute inset-0 bg-black/70 backdrop-blur-sm" data-v-3b650ee9></div><div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-hidden border border-gray-700" data-v-3b650ee9><div class="flex items-center justify-between p-4 border-b border-gray-700" data-v-3b650ee9><h3 class="text-xl font-bold text-white flex items-center gap-2" data-v-3b650ee9>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-edit-24-regular",
            class: "w-6 h-6 text-vikinger-cyan"
          }, null, _parent));
          _push2(` Edit Profile Photo </h3><button class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors" data-v-3b650ee9>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`</button></div><div class="p-6" data-v-3b650ee9><div class="flex justify-center mb-6" data-v-3b650ee9><div class="relative" data-v-3b650ee9><div class="w-40 h-40 rounded-full overflow-hidden border-4 border-vikinger-cyan bg-gray-700" data-v-3b650ee9>`);
          if (unref(avatarPreview) || ((_a2 = unref(profile)) == null ? void 0 : _a2.avatar)) {
            _push2(`<img${ssrRenderAttr("src", unref(avatarPreview) || ((_b2 = unref(profile)) == null ? void 0 : _b2.avatar))} alt="Avatar Preview" class="w-full h-full object-cover" data-v-3b650ee9>`);
          } else {
            _push2(`<div class="w-full h-full flex items-center justify-center" data-v-3b650ee9>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-24-regular",
              class: "w-16 h-16 text-gray-500"
            }, null, _parent));
            _push2(`</div>`);
          }
          _push2(`</div>`);
          if (unref(avatarPreview)) {
            _push2(`<div class="absolute -top-2 -right-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full" data-v-3b650ee9> New </div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div><div class="space-y-4" data-v-3b650ee9><button class="w-full py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center justify-center gap-2" data-v-3b650ee9>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:arrow-upload-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(` Choose Image from Device </button><p class="text-center text-gray-500 text-sm" data-v-3b650ee9> Recommended: Square image, at least 200x200 pixels. Max: 2MB </p></div></div><div class="flex items-center justify-end gap-3 p-4 border-t border-gray-700" data-v-3b650ee9><button class="px-5 py-2.5 text-gray-400 hover:text-white transition-colors" data-v-3b650ee9> Cancel </button><button${ssrIncludeBooleanAttr(!unref(avatarPreview) || unref(isUploadingAvatar)) ? " disabled" : ""} class="${ssrRenderClass([
            "px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2",
            unref(avatarPreview) && !unref(isUploadingAvatar) ? "bg-vikinger-purple hover:bg-vikinger-purple/80 text-white" : "bg-gray-700 text-gray-500 cursor-not-allowed"
          ])}" data-v-3b650ee9>`);
          if (unref(isUploadingAvatar)) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:save-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
          }
          _push2(` ${ssrInterpolate(unref(isUploadingAvatar) ? "Saving..." : "Save Avatar")}</button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`<div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 safe-area-bottom" data-v-3b650ee9><div class="flex items-center justify-around px-2 py-2" data-v-3b650ee9><!--[-->`);
      ssrRenderList(mobileBottomTabs, (tab) => {
        _push(`<button class="${ssrRenderClass([
          "flex flex-col items-center justify-center py-1.5 px-3 rounded-xl transition-all flex-1 max-w-[72px]",
          unref(activeTab) === tab.key ? "text-vikinger-purple dark:text-vikinger-cyan bg-vikinger-purple/10 dark:bg-vikinger-cyan/10" : "text-gray-500 dark:text-gray-400"
        ])}" data-v-3b650ee9><div class="${ssrRenderClass([
          "w-8 h-8 rounded-lg flex items-center justify-center mb-0.5 transition-all",
          unref(activeTab) === tab.key ? "bg-gradient-to-br from-vikinger-purple to-vikinger-cyan text-white scale-110" : "bg-transparent"
        ])}" data-v-3b650ee9>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</div><span class="${ssrRenderClass([
          "text-[10px] font-bold truncate max-w-full",
          unref(activeTab) === tab.key ? "text-vikinger-purple dark:text-vikinger-cyan" : ""
        ])}" data-v-3b650ee9>${ssrInterpolate(tab.label)}</span></button>`);
      });
      _push(`<!--]--></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/profile/[id].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const _id_ = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-3b650ee9"]]);

export { _id_ as default };
//# sourceMappingURL=_id_-DUUhed5w.mjs.map
