import { ref, mergeProps, computed, unref, watch, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderTeleport, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderStyle, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, d as useAuthStore, a as useNuxtApp } from './server.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import { defineStore } from 'pinia';

const useFeedStore = defineStore("feed", () => {
  const posts = ref([]);
  const trendingTopics = ref([]);
  function setPosts(newPosts) {
    posts.value = newPosts;
  }
  function addPost(post) {
    posts.value.unshift(post);
  }
  function updatePost(postId, updatedPost) {
    const index = posts.value.findIndex((p) => {
      if (p.target_resource && p.target_resource.id === postId) return true;
      return p.id === postId;
    });
    if (index !== -1) {
      if (posts.value[index].target_resource) {
        posts.value[index].target_resource = { ...posts.value[index].target_resource, ...updatedPost };
      } else {
        posts.value[index] = { ...posts.value[index], ...updatedPost };
      }
    }
  }
  function removePost(postId) {
    posts.value = posts.value.filter((p) => {
      if (p.target_resource && p.target_resource.id === postId) return false;
      return p.id !== postId;
    });
  }
  function setTrending(topics) {
    trendingTopics.value = topics;
  }
  return {
    posts,
    trendingTopics,
    setPosts,
    addPost,
    updatePost,
    removePost,
    setTrending
  };
});
const usePosts = () => {
  const feedStore = useFeedStore();
  const { $apiFetch } = useNuxtApp();
  const authStore = useAuthStore();
  const feelings = ref([]);
  const activityTypes = ref([]);
  const backgrounds = ref([]);
  const isLoadingOptions = ref(false);
  const fetchPosts = async () => {
    try {
      const response = await $apiFetch("/api/newsfeed/activities");
      if (response && response.data) {
        feedStore.setPosts(response.data);
      }
    } catch (error) {
      console.error("Error fetching posts:", error);
      feedStore.setPosts([]);
    }
  };
  const fetchFeelings = async () => {
    if (feelings.value.length > 0) return feelings.value;
    try {
      const response = await $apiFetch("/api/posts-feelings");
      if (response.success) {
        const data = response.data || response.feelings || [];
        feelings.value = data;
        return data;
      }
      return [];
    } catch (error) {
      console.error("Error fetching feelings:", error);
      return [];
    }
  };
  const fetchActivityTypes = async () => {
    if (activityTypes.value.length > 0) return activityTypes.value;
    try {
      const response = await $apiFetch("/api/posts-activity-types");
      if (response.success) {
        const data = response.data || response.activity_types || [];
        activityTypes.value = data;
        return data;
      }
      return [];
    } catch (error) {
      console.error("Error fetching activity types:", error);
      return [];
    }
  };
  const fetchBackgrounds = async () => {
    if (backgrounds.value.length > 0) return backgrounds.value;
    try {
      const response = await $apiFetch("/api/posts-backgrounds");
      if (response.success) {
        const data = response.data || [];
        backgrounds.value = data;
        return data;
      }
      return [];
    } catch (error) {
      console.error("Error fetching backgrounds:", error);
      return [];
    }
  };
  const fetchPostOptions = async () => {
    if (isLoadingOptions.value) return;
    isLoadingOptions.value = true;
    try {
      const response = await $apiFetch("/api/posts-options");
      if (response.success && response.data) {
        feelings.value = response.data.feelings || [];
        activityTypes.value = response.data.activity_types || [];
        backgrounds.value = response.data.backgrounds || [];
      }
    } catch (error) {
      console.error("Error fetching post options:", error);
    } finally {
      isLoadingOptions.value = false;
    }
  };
  const createPost = async (content, options = {}) => {
    var _a;
    try {
      const formData = new FormData();
      formData.append("content", content);
      formData.append("privacy_settings", String((_a = options.privacy_settings) != null ? _a : 3));
      formData.append("point", "1");
      if (options.images && options.images.length > 0) {
        options.images.forEach((image, index) => {
          formData.append(`images[${index}]`, image);
        });
      }
      if (options.location_name) {
        formData.append("location_name", options.location_name);
      }
      if (options.location) {
        formData.append("location[name]", options.location.name);
        if (options.location.address) formData.append("location[address]", options.location.address);
        if (options.location.city) formData.append("location[city]", options.location.city);
        if (options.location.state) formData.append("location[state]", options.location.state);
        if (options.location.country) formData.append("location[country]", options.location.country);
        if (options.location.latitude) formData.append("location[latitude]", String(options.location.latitude));
        if (options.location.longitude) formData.append("location[longitude]", String(options.location.longitude));
        if (options.location.place_id) formData.append("location[place_id]", options.location.place_id);
      }
      if (options.feeling) {
        formData.append("feeling", options.feeling);
      }
      if (options.feeling_icon) {
        formData.append("feeling_icon", options.feeling_icon);
      }
      if (options.activity_type) {
        formData.append("activity_type", options.activity_type);
      }
      if (options.activity_text) {
        formData.append("activity_text", options.activity_text);
      }
      if (options.background_color) {
        formData.append("background_color", options.background_color);
      }
      if (options.background_gradient) {
        formData.append("background_gradient", options.background_gradient);
      }
      if (options.text_color) {
        formData.append("text_color", options.text_color);
      }
      if (options.tagged_users && options.tagged_users.length > 0) {
        options.tagged_users.forEach((userId, index) => {
          formData.append(`tagged_users[${index}]`, String(userId));
        });
      }
      if (options.scheduled_at) {
        formData.append("scheduled_at", options.scheduled_at);
      }
      if (options.comments_disabled !== void 0) {
        formData.append("comments_disabled", options.comments_disabled ? "1" : "0");
      }
      if (options.is_poll) {
        formData.append("is_poll", "1");
        if (options.poll_title) formData.append("poll_title", options.poll_title);
        if (options.poll_duration) formData.append("poll_duration", String(options.poll_duration));
        if (options.poll_options && options.poll_options.length > 0) {
          options.poll_options.forEach((opt, index) => {
            formData.append(`poll_options[${index}]`, opt);
          });
        }
      }
      const response = await $apiFetch("/api/posts", {
        method: "POST",
        body: formData
      });
      if (response.success && response.activity) {
        feedStore.addPost(response.activity);
        if (authStore.user) {
          authStore.user.pp -= 180;
        }
      }
      return response;
    } catch (error) {
      console.error("Error creating post:", error);
      throw error;
    }
  };
  const updatePost = async (postId, content, options = {}) => {
    try {
      const formData = new FormData();
      formData.append("_method", "PUT");
      formData.append("content", content);
      if (options.privacy_settings !== void 0) {
        formData.append("privacy_settings", String(options.privacy_settings));
      }
      if (options.images && options.images.length > 0) {
        options.images.forEach((image, index) => {
          formData.append(`images[${index}]`, image);
        });
      }
      if (options.delete_images && options.delete_images.length > 0) {
        options.delete_images.forEach((imageId, index) => {
          formData.append(`delete_images[${index}]`, String(imageId));
        });
      }
      if (options.image_order && options.image_order.length > 0) {
        options.image_order.forEach((imageId, index) => {
          formData.append(`image_order[${index}]`, String(imageId));
        });
      }
      if (options.remove_feeling) {
        formData.append("remove_feeling", "1");
      }
      if (options.remove_background) {
        formData.append("remove_background", "1");
      }
      if (options.location_name) {
        formData.append("location_name", options.location_name);
      }
      if (!options.remove_feeling) {
        if (options.feeling) {
          formData.append("feeling", options.feeling);
        }
        if (options.feeling_icon) {
          formData.append("feeling_icon", options.feeling_icon);
        }
        if (options.activity_type) {
          formData.append("activity_type", options.activity_type);
        }
        if (options.activity_text) {
          formData.append("activity_text", options.activity_text);
        }
      }
      if (!options.remove_background) {
        if (options.background_color) {
          formData.append("background_color", options.background_color);
        }
        if (options.background_gradient) {
          formData.append("background_gradient", options.background_gradient);
        }
        if (options.text_color) {
          formData.append("text_color", options.text_color);
        }
      }
      if (options.tagged_users && options.tagged_users.length > 0) {
        options.tagged_users.forEach((userId, index) => {
          formData.append(`tagged_users[${index}]`, String(userId));
        });
      }
      if (options.comments_disabled !== void 0) {
        formData.append("comments_disabled", options.comments_disabled ? "1" : "0");
      }
      const response = await $apiFetch(`/api/posts/${postId}`, {
        method: "POST",
        // Using POST with _method override for FormData
        body: formData
      });
      if (response.success && response.post) {
        feedStore.updatePost(postId, response.post);
      }
      return response;
    } catch (error) {
      console.error("Error updating post:", error);
      throw error;
    }
  };
  return {
    // Data
    feelings,
    activityTypes,
    backgrounds,
    isLoadingOptions,
    // Methods
    fetchPosts,
    fetchFeelings,
    fetchActivityTypes,
    fetchBackgrounds,
    fetchPostOptions,
    createPost,
    updatePost
  };
};
const _sfc_main$2 = {
  __name: "CreatePostTrigger",
  __ssrInlineRender: true,
  emits: ["open-modal"],
  setup(__props, { emit: __emit }) {
    const authStore = useAuthStore();
    const { getAvatarUrl } = useAvatar();
    const userAvatar = computed(() => getAvatarUrl(authStore.user));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-100 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-vikinger-dark-50/20" }, _attrs))}><div class="flex items-center gap-3 mb-3"><img${ssrRenderAttr("src", unref(userAvatar))} alt="Avatar" class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"><button class="flex-1 text-left px-4 py-2.5 bg-gray-100 dark:bg-vikinger-dark-200/50 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 transition-colors text-sm"> \u0E04\u0E38\u0E13\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E34\u0E14\u0E2D\u0E30\u0E44\u0E23\u0E2D\u0E22\u0E39\u0E48? </button></div><div class="flex items-center justify-around pt-2 border-t border-gray-100 dark:border-vikinger-dark-50/20"><button class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:image-24-regular",
        class: "w-5 h-5 text-green-500"
      }, null, _parent));
      _push(`<span class="text-xs font-medium text-gray-600 dark:text-gray-400">\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></button><button class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:poll-24-regular",
        class: "w-5 h-5 text-amber-500"
      }, null, _parent));
      _push(`<span class="text-xs font-medium text-gray-600 dark:text-gray-400">\u0E42\u0E1E\u0E25</span></button></div></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/feed/CreatePostTrigger.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "CreatePostModal",
  __ssrInlineRender: true,
  props: {
    show: {
      type: Boolean,
      default: false
    },
    initialTab: {
      type: String,
      default: "status"
    },
    context: {
      type: String,
      default: "newsfeed",
      // 'newsfeed', 'academy', 'course'
      validator: (value) => ["newsfeed", "academy", "course"].includes(value)
    },
    contextId: {
      type: Number,
      default: null
    },
    contextName: {
      type: String,
      default: ""
    }
  },
  emits: ["close", "post-created"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const { $apiFetch } = useNuxtApp();
    const authStore = useAuthStore();
    const { getAvatarUrl } = useAvatar();
    const {
      feelings,
      activityTypes,
      backgrounds,
      fetchPostOptions
    } = usePosts();
    const modalTitle = computed(() => {
      if (props.context === "academy" && props.contextName) {
        return `\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E19 ${props.contextName}`;
      }
      if (props.context === "course" && props.contextName) {
        return `\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 ${props.contextName}`;
      }
      return "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E2A\u0E15\u0E4C";
    });
    const currentUserAvatar = computed(() => getAvatarUrl(authStore.user));
    const postText = ref("");
    const isSubmitting = ref(false);
    const selectedImages = ref([]);
    ref(null);
    const imagePreviews = computed(() => {
      return [];
    });
    const selectedPrivacy = ref(3);
    const locationInput = ref("");
    const selectedFeeling = ref(null);
    const selectedActivity = ref(null);
    const activityText = ref("");
    const selectedBackground = ref(null);
    const taggedFriends = ref([]);
    const scheduledDate = ref("");
    const commentsDisabled = ref(false);
    const showFeelingPicker = ref(false);
    const showActivityPicker = ref(false);
    const showBackgroundPicker = ref(false);
    const showLocationInput = ref(false);
    const showTagFriends = ref(false);
    const showScheduler = ref(false);
    const showPrivacyOptions = ref(false);
    const friendSearchQuery = ref("");
    const friendSearchResults = ref([]);
    const isSearchingFriends = ref(false);
    const hasSearchedFriends = ref(false);
    const activeTab = ref("status");
    const tabs = [
      { id: "status", label: "\u0E2A\u0E16\u0E32\u0E19\u0E30", icon: "mdi:card-text-outline" },
      { id: "poll", label: "\u0E42\u0E1E\u0E25", icon: "mdi:poll" }
    ];
    const pollQuestion = ref("");
    const pollOptions = ref(["", ""]);
    const pollDuration = ref(24);
    const pollPointsPool = ref(12e3);
    const maxVotes = ref(100);
    computed(() => {
      const date = /* @__PURE__ */ new Date();
      date.setDate(date.getDate() + parseInt(pollDuration.value));
      return date.toISOString();
    });
    watch(() => props.show, (newVal) => {
      if (newVal) {
        activeTab.value = props.initialTab || "status";
        fetchPostOptions();
      } else {
        resetForm();
      }
    });
    const privacyOptions = [
      { value: 3, label: "\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30", icon: "mdi:earth", color: "text-blue-500" },
      { value: 2, label: "\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19", icon: "mdi:account-group", color: "text-green-500" },
      { value: 1, label: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E09\u0E31\u0E19", icon: "mdi:lock", color: "text-gray-500" }
    ];
    const currentPrivacy = computed(() => {
      return privacyOptions.find((p) => p.value === selectedPrivacy.value) || privacyOptions[0];
    });
    const backgroundStyle = computed(() => {
      if (!selectedBackground.value) return {};
      const bg = selectedBackground.value;
      const style = {};
      if (bg.background_gradient) {
        style.background = bg.background_gradient;
      } else if (bg.background_color) {
        style.backgroundColor = bg.background_color;
      }
      if (bg.text_color) {
        style.color = bg.text_color;
      }
      return style;
    });
    const feelingActivityText = computed(() => {
      const parts = [];
      if (selectedFeeling.value) {
        parts.push(`${selectedFeeling.value.icon} \u0E23\u0E39\u0E49\u0E2A\u0E36\u0E01${selectedFeeling.value.name_th || selectedFeeling.value.name}`);
      }
      if (selectedActivity.value) {
        let actText = `${selectedActivity.value.icon} \u0E01\u0E33\u0E25\u0E31\u0E07${selectedActivity.value.name_th || selectedActivity.value.name}`;
        if (activityText.value) actText += ` ${activityText.value}`;
        parts.push(actText);
      }
      return parts.join(" \u2014 ");
    });
    const feelingsByCategory = computed(() => {
      const grouped = {};
      feelings.value.forEach((f) => {
        if (!grouped[f.category]) grouped[f.category] = [];
        grouped[f.category].push(f);
      });
      return grouped;
    });
    const activitiesByCategory = computed(() => {
      const grouped = {};
      activityTypes.value.forEach((a) => {
        if (!grouped[a.category]) grouped[a.category] = [];
        grouped[a.category].push(a);
      });
      return grouped;
    });
    const closeModal = () => {
      emit("close");
    };
    const resetForm = () => {
      postText.value = "";
      selectedImages.value = [];
      locationInput.value = "";
      selectedFeeling.value = null;
      selectedActivity.value = null;
      activityText.value = "";
      selectedBackground.value = null;
      taggedFriends.value = [];
      scheduledDate.value = "";
      commentsDisabled.value = false;
      showFeelingPicker.value = false;
      showActivityPicker.value = false;
      showBackgroundPicker.value = false;
      showLocationInput.value = false;
      showTagFriends.value = false;
      showScheduler.value = false;
      showPrivacyOptions.value = false;
      activeTab.value = "status";
      pollQuestion.value = "";
      pollOptions.value = ["", ""];
      pollDuration.value = 24;
      pollPointsPool.value = 12e3;
      maxVotes.value = 100;
      closeModal();
    };
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto backdrop-blur-sm" data-v-b7df1785><div class="w-full max-w-2xl mx-4 mb-10 modal-content" data-v-b7df1785><div class="bg-white dark:bg-vikinger-dark-300 rounded-xl shadow-2xl" data-v-b7df1785><div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30" data-v-b7df1785><div class="flex items-center gap-2" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: __props.context === "academy" ? "fluent:building-24-regular" : __props.context === "course" ? "fluent:book-24-regular" : "fluent:calendar-ltr-24-regular",
            class: "w-5 h-5 text-vikinger-purple"
          }, null, _parent));
          _push2(`<h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-b7df1785>${ssrInterpolate(modalTitle.value)}</h2></div><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-6 h-6 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-4 max-h-[75vh] overflow-y-auto" data-v-b7df1785><div class="flex items-center gap-3 mb-4" data-v-b7df1785><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20" data-v-b7df1785><div class="flex-1" data-v-b7df1785><div class="font-bold text-gray-800 dark:text-white leading-tight" data-v-b7df1785>${ssrInterpolate((_a = unref(authStore).user) == null ? void 0 : _a.name)}</div><div class="flex items-center gap-1.5 mt-0.5" data-v-b7df1785><button class="flex items-center gap-1 text-[11px] font-bold text-gray-500 dark:text-gray-400 hover:text-vikinger-purple uppercase tracking-wider transition-colors" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: currentPrivacy.value.icon,
            class: "w-2.5 h-2.5"
          }, null, _parent));
          _push2(`<span data-v-b7df1785>${ssrInterpolate(currentPrivacy.value.label)}</span>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:chevron-down",
            class: "w-3 h-3"
          }, null, _parent));
          _push2(`</button>`);
          if (locationInput.value) {
            _push2(`<span class="text-gray-300 dark:text-gray-700 font-light text-xs" data-v-b7df1785>|</span>`);
          } else {
            _push2(`<!---->`);
          }
          if (locationInput.value) {
            _push2(`<div class="flex items-center gap-1 text-[11px] font-bold text-vikinger-cyan uppercase tracking-wider" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:map-marker",
              class: "w-2.5 h-2.5"
            }, null, _parent));
            _push2(`<span class="truncate max-w-[150px]" data-v-b7df1785>${ssrInterpolate(locationInput.value)}</span></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div></div><div class="flex border-b border-gray-200 dark:border-vikinger-dark-50/30 mb-4" data-v-b7df1785><!--[-->`);
          ssrRenderList(tabs, (tab) => {
            _push2(`<button class="${ssrRenderClass([
              "flex-1 pb-3 text-sm font-bold uppercase tracking-wider transition-all relative flex items-center justify-center gap-2",
              activeTab.value === tab.id ? tab.id === "status" ? "text-blue-600" : "text-amber-600" : "text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
            ])}" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: tab.id === "status" ? "fluent:edit-24-regular" : "fluent:poll-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` ${ssrInterpolate(tab.label)} `);
            if (activeTab.value === tab.id) {
              _push2(`<div class="${ssrRenderClass([
                "absolute bottom-0 left-0 w-full h-1 rounded-full",
                tab.id === "status" ? "bg-blue-600" : "bg-amber-600"
              ])}" data-v-b7df1785></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</button>`);
          });
          _push2(`<!--]--></div><div style="${ssrRenderStyle(activeTab.value === "status" ? null : { display: "none" })}" data-v-b7df1785><input type="file" class="hidden" accept="image/*" multiple data-v-b7df1785>`);
          if (showPrivacyOptions.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-b7df1785><div class="space-y-2" data-v-b7df1785><!--[-->`);
            ssrRenderList(privacyOptions, (option) => {
              _push2(`<button class="${ssrRenderClass([{ "bg-white dark:bg-vikinger-dark-100": selectedPrivacy.value === option.value }, "w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100"])}" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: option.icon,
                class: ["w-5 h-5", option.color]
              }, null, _parent));
              _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>${ssrInterpolate(option.label)}</span></button>`);
            });
            _push2(`<!--]--></div><label class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-vikinger-dark-50/30 cursor-pointer" data-v-b7df1785><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(commentsDisabled.value) ? ssrLooseContain(commentsDisabled.value, null) : commentsDisabled.value) ? " checked" : ""} class="rounded text-vikinger-purple" data-v-b7df1785><span class="text-sm text-gray-600 dark:text-gray-400" data-v-b7df1785>\u0E1B\u0E34\u0E14\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</span></label></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (selectedFeeling.value || selectedActivity.value || locationInput.value || taggedFriends.value.length > 0) {
            _push2(`<div class="mb-3 flex flex-wrap gap-2" data-v-b7df1785>`);
            if (feelingActivityText.value) {
              _push2(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full text-sm" data-v-b7df1785>${ssrInterpolate(feelingActivityText.value)} <button class="hover:text-red-500" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:close",
                class: "w-3 h-3"
              }, null, _parent));
              _push2(`</button></span>`);
            } else {
              _push2(`<!---->`);
            }
            if (locationInput.value) {
              _push2(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:map-marker",
                class: "w-3 h-3"
              }, null, _parent));
              _push2(` ${ssrInterpolate(locationInput.value)} <button class="hover:text-red-500" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:close",
                class: "w-3 h-3"
              }, null, _parent));
              _push2(`</button></span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<!--[-->`);
            ssrRenderList(taggedFriends.value, (friend) => {
              _push2(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm" data-v-b7df1785>${ssrInterpolate(friend.name)} <button class="hover:text-red-500" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:close",
                class: "w-3 h-3"
              }, null, _parent));
              _push2(`</button></span>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="${ssrRenderClass([selectedBackground.value ? "" : "bg-gray-50 dark:bg-vikinger-dark-200", "rounded-lg mb-4 min-h-[120px] p-4 transition-all"])}" style="${ssrRenderStyle(backgroundStyle.value)}" data-v-b7df1785><textarea placeholder="\u0E04\u0E38\u0E13\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E34\u0E14\u0E2D\u0E30\u0E44\u0E23\u0E2D\u0E22\u0E39\u0E48?"${ssrRenderAttr("rows", selectedBackground.value ? 5 : 3)} class="${ssrRenderClass([selectedBackground.value ? "text-center text-lg font-medium" : "text-gray-800 dark:text-white", "w-full bg-transparent border-none outline-none resize-none placeholder-gray-400"])}" style="${ssrRenderStyle(selectedBackground.value ? { color: selectedBackground.value.text_color } : {})}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>${ssrInterpolate(postText.value)}</textarea></div>`);
          if (imagePreviews.value.length > 0) {
            _push2(`<div class="mb-4" data-v-b7df1785><div class="flex flex-wrap gap-2" data-v-b7df1785><!--[-->`);
            ssrRenderList(imagePreviews.value, (preview, index) => {
              _push2(`<div class="relative group" data-v-b7df1785><img${ssrRenderAttr("src", preview.url)} class="w-32 h-32 object-cover rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" data-v-b7df1785><button class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:close",
                class: "w-4 h-4"
              }, null, _parent));
              _push2(`</button></div>`);
            });
            _push2(`<!--]-->`);
            if (imagePreviews.value.length < 20) {
              _push2(`<button class="w-32 h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center hover:border-vikinger-purple hover:bg-vikinger-purple/5 transition-all" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:plus",
                class: "w-8 h-8 text-gray-400"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><p class="text-xs text-gray-500 mt-1" data-v-b7df1785>${ssrInterpolate(imagePreviews.value.length)}/20 \u0E23\u0E39\u0E1B</p></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showBackgroundPicker.value && imagePreviews.value.length === 0) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1E\u0E37\u0E49\u0E19\u0E2B\u0E25\u0E31\u0E07</span><button data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><div class="flex flex-wrap gap-2" data-v-b7df1785><button class="${ssrRenderClass([!selectedBackground.value ? "border-vikinger-purple" : "border-gray-300", "w-10 h-10 rounded-lg border-2 flex items-center justify-center"])}" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:format-clear",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button><!--[-->`);
            ssrRenderList(unref(backgrounds), (bg) => {
              var _a2;
              _push2(`<button class="${ssrRenderClass([((_a2 = selectedBackground.value) == null ? void 0 : _a2.id) === bg.id ? "border-vikinger-purple scale-110" : "border-transparent", "w-10 h-10 rounded-lg border-2 transition-all"])}" style="${ssrRenderStyle({ background: bg.background_gradient || bg.background_color })}" data-v-b7df1785></button>`);
            });
            _push2(`<!--]--></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showFeelingPicker.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg max-h-48 overflow-y-auto" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E04\u0E38\u0E13\u0E23\u0E39\u0E49\u0E2A\u0E36\u0E01\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E44\u0E23?</span><button data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><!--[-->`);
            ssrRenderList(feelingsByCategory.value, (categoryFeelings, category) => {
              _push2(`<div class="mb-3" data-v-b7df1785><div class="text-xs text-gray-500 uppercase mb-2" data-v-b7df1785>${ssrInterpolate(category)}</div><div class="grid grid-cols-5 gap-2" data-v-b7df1785><!--[-->`);
              ssrRenderList(categoryFeelings, (feeling) => {
                var _a2;
                _push2(`<button class="${ssrRenderClass([{ "bg-vikinger-purple/10 ring-1 ring-vikinger-purple": ((_a2 = selectedFeeling.value) == null ? void 0 : _a2.id) === feeling.id }, "flex flex-col items-center p-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100"])}" data-v-b7df1785><span class="text-xl" data-v-b7df1785>${ssrInterpolate(feeling.icon)}</span><span class="text-xs text-gray-600 dark:text-gray-400 truncate w-full text-center" data-v-b7df1785>${ssrInterpolate(feeling.name_th || feeling.name)}</span></button>`);
              });
              _push2(`<!--]--></div></div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showActivityPicker.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg max-h-48 overflow-y-auto" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E04\u0E38\u0E13\u0E01\u0E33\u0E25\u0E31\u0E07\u0E17\u0E33\u0E2D\u0E30\u0E44\u0E23?</span><button data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><!--[-->`);
            ssrRenderList(activitiesByCategory.value, (categoryActivities, category) => {
              _push2(`<div class="mb-3" data-v-b7df1785><div class="text-xs text-gray-500 uppercase mb-2" data-v-b7df1785>${ssrInterpolate(category)}</div><div class="grid grid-cols-3 gap-2" data-v-b7df1785><!--[-->`);
              ssrRenderList(categoryActivities, (activity) => {
                var _a2;
                _push2(`<button class="${ssrRenderClass([{ "bg-vikinger-purple/10 ring-1 ring-vikinger-purple": ((_a2 = selectedActivity.value) == null ? void 0 : _a2.id) === activity.id }, "flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100 text-left"])}" data-v-b7df1785><span class="text-lg" data-v-b7df1785>${ssrInterpolate(activity.icon)}</span><span class="text-xs text-gray-600 dark:text-gray-400 truncate" data-v-b7df1785>${ssrInterpolate(activity.name_th || activity.name)}</span></button>`);
              });
              _push2(`<!--]--></div></div>`);
            });
            _push2(`<!--]-->`);
            if (selectedActivity.value) {
              _push2(`<div class="mt-3" data-v-b7df1785><input${ssrRenderAttr("value", activityText.value)} type="text"${ssrRenderAttr("placeholder", `${selectedActivity.value.name_th || selectedActivity.value.name}...`)} class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" data-v-b7df1785></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showLocationInput.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48</span><button data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><div class="flex items-center gap-2" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:map-marker",
              class: "w-5 h-5 text-red-500"
            }, null, _parent));
            _push2(`<input${ssrRenderAttr("value", locationInput.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2B\u0E23\u0E37\u0E2D\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48..." class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" data-v-b7df1785></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showTagFriends.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E41\u0E17\u0E47\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span><button data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div>`);
            if (taggedFriends.value.length > 0) {
              _push2(`<div class="flex flex-wrap gap-2 mb-3" data-v-b7df1785><!--[-->`);
              ssrRenderList(taggedFriends.value, (friend) => {
                _push2(`<div class="flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded-full" data-v-b7df1785><img${ssrRenderAttr("src", unref(getAvatarUrl)(friend))} class="w-5 h-5 rounded-full" data-v-b7df1785><span class="text-xs text-blue-700 dark:text-blue-300" data-v-b7df1785>${ssrInterpolate(friend.name)}</span><button class="ml-1 text-blue-500 hover:text-red-500" data-v-b7df1785>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:close",
                  class: "w-3 h-3"
                }, null, _parent));
                _push2(`</button></div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="relative" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:magnify",
              class: "absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
            }, null, _parent));
            _push2(`<input${ssrRenderAttr("value", friendSearchQuery.value)} type="text" placeholder="\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32..." class="w-full pl-9 pr-10 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-all" data-v-b7df1785>`);
            if (isSearchingFriends.value) {
              _push2(`<div class="absolute right-2 top-1/2 -translate-y-1/2" data-v-b7df1785><div class="w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" data-v-b7df1785></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="mt-4" data-v-b7df1785>`);
          if (isSearchingFriends.value) {
            _push2(`<div class="mb-2 p-4 text-center" data-v-b7df1785><div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" data-v-b7df1785><div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" data-v-b7df1785></div><span data-v-b7df1785>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32...</span></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (friendSearchResults.value.length > 0) {
            _push2(`<div class="mb-2 space-y-1 max-h-40 overflow-y-auto border border-gray-200 dark:border-vikinger-dark-50/30 rounded-lg bg-white dark:bg-vikinger-dark-100" data-v-b7df1785><!--[-->`);
            ssrRenderList(friendSearchResults.value, (friend) => {
              _push2(`<button class="${ssrRenderClass([{ "opacity-50 cursor-not-allowed": taggedFriends.value.find((f) => f.id === friend.id) }, "w-full flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"])}"${ssrIncludeBooleanAttr(!!taggedFriends.value.find((f) => f.id === friend.id)) ? " disabled" : ""} data-v-b7df1785><img${ssrRenderAttr("src", unref(getAvatarUrl)(friend))} class="w-8 h-8 rounded-full" data-v-b7df1785><div class="flex-1 text-left" data-v-b7df1785><span class="text-sm text-gray-800 dark:text-white block" data-v-b7df1785>${ssrInterpolate(friend.name)}</span>`);
              if (friend.username) {
                _push2(`<span class="text-xs text-gray-500" data-v-b7df1785>@${ssrInterpolate(friend.username)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (taggedFriends.value.find((f) => f.id === friend.id)) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:check",
                  class: "w-5 h-5 text-green-500"
                }, null, _parent));
              } else {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:plus",
                  class: "w-5 h-5 text-blue-500"
                }, null, _parent));
              }
              _push2(`</button>`);
            });
            _push2(`<!--]--></div>`);
          } else if (hasSearchedFriends.value && friendSearchQuery.value && !isSearchingFriends.value) {
            _push2(`<div class="mb-2 p-3 text-center text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-vikinger-dark-100 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:account-search",
              class: "w-8 h-8 mx-auto mb-1 text-gray-400"
            }, null, _parent));
            _push2(`<p data-v-b7df1785>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E0A\u0E37\u0E48\u0E2D &quot;${ssrInterpolate(friendSearchQuery.value)}&quot;</p></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div>`);
          if (showScheduler.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E15\u0E31\u0E49\u0E07\u0E40\u0E27\u0E25\u0E32\u0E42\u0E1E\u0E2A\u0E15\u0E4C</span><button data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><input${ssrRenderAttr("value", scheduledDate.value)} type="datetime-local" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" data-v-b7df1785></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="flex flex-wrap gap-2 border-t border-gray-200 dark:border-vikinger-dark-50/30 pt-4" data-v-b7df1785><button class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:image-multiple",
            class: "w-5 h-5 text-green-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></button><button class="${ssrRenderClass([{ "border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20": selectedFeeling.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:emoticon-happy",
            class: "w-5 h-5 text-yellow-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49\u0E2A\u0E36\u0E01</span></button><button class="${ssrRenderClass([{ "border-orange-400 bg-orange-50 dark:bg-orange-900/20": selectedActivity.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:run",
            class: "w-5 h-5 text-orange-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</span></button><button class="${ssrRenderClass([{ "border-red-400 bg-red-50 dark:bg-red-900/20": locationInput.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:map-marker",
            class: "w-5 h-5 text-red-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48</span></button><button class="${ssrRenderClass([{ "border-blue-400 bg-blue-50 dark:bg-blue-900/20": taggedFriends.value.length > 0 }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-multiple-plus",
            class: "w-5 h-5 text-blue-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E41\u0E17\u0E47\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span></button>`);
          if (imagePreviews.value.length === 0) {
            _push2(`<button class="${ssrRenderClass([{ "border-purple-400 bg-purple-50 dark:bg-purple-900/20": selectedBackground.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:palette",
              class: "w-5 h-5 text-purple-500"
            }, null, _parent));
            _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E1E\u0E37\u0E49\u0E19\u0E2B\u0E25\u0E31\u0E07</span></button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<button class="${ssrRenderClass([{ "border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20": scheduledDate.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:clock-outline",
            class: "w-5 h-5 text-indigo-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-b7df1785>\u0E15\u0E31\u0E49\u0E07\u0E40\u0E27\u0E25\u0E32</span></button></div></div><div class="mb-4" style="${ssrRenderStyle(activeTab.value === "poll" ? null : { display: "none" })}" data-v-b7df1785><div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700/30" data-v-b7df1785><div class="flex items-center justify-between mb-3" data-v-b7df1785><div class="flex items-center gap-2" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:poll-24-regular",
            class: "w-5 h-5 text-amber-600"
          }, null, _parent));
          _push2(`<span class="font-medium text-amber-700 dark:text-amber-300" data-v-b7df1785>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E42\u0E1E\u0E25</span></div></div><input${ssrRenderAttr("value", pollQuestion.value)} type="text" placeholder="\u0E04\u0E33\u0E16\u0E32\u0E21\u0E42\u0E1E\u0E25..." class="w-full px-3 py-2 mb-3 rounded-lg border border-amber-200 dark:border-amber-700/50 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-white" data-v-b7df1785><div class="space-y-3 mb-4" data-v-b7df1785><!--[-->`);
          ssrRenderList(pollOptions.value, (option, index) => {
            _push2(`<div class="flex items-center gap-3 p-3 bg-white dark:bg-vikinger-dark-100 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30" data-v-b7df1785><div class="w-7 h-7 rounded-full bg-vikinger-purple text-white flex items-center justify-center text-xs font-bold flex-shrink-0" data-v-b7df1785>${ssrInterpolate(index + 1)}</div><input${ssrRenderAttr("value", pollOptions.value[index])} type="text"${ssrRenderAttr("placeholder", `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`)} class="flex-1 bg-transparent border-none outline-none text-gray-800 dark:text-white text-sm" data-v-b7df1785>`);
            if (pollOptions.value.length > 2) {
              _push2(`<button class="p-1 text-gray-400 hover:text-red-500 transition-colors" data-v-b7df1785>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          });
          _push2(`<!--]--></div>`);
          if (pollOptions.value.length < 10) {
            _push2(`<button class="w-full h-11 border-2 border-dashed border-gray-200 dark:border-vikinger-dark-50/20 rounded-xl flex items-center justify-center gap-2 text-sm text-gray-500 hover:border-vikinger-cyan hover:text-vikinger-cyan transition-all mb-4" data-v-b7df1785>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(`<span data-v-b7df1785>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 (\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 10 \u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01)</span></button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700/30" data-v-b7df1785><label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`<span data-v-b7df1785>\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32\u0E42\u0E1E\u0E25:</span><select class="px-2 py-1 rounded border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-sm" data-v-b7df1785><option${ssrRenderAttr("value", 1)} data-v-b7df1785${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 1) : ssrLooseEqual(pollDuration.value, 1)) ? " selected" : ""}>1 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</option><option${ssrRenderAttr("value", 6)} data-v-b7df1785${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 6) : ssrLooseEqual(pollDuration.value, 6)) ? " selected" : ""}>6 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</option><option${ssrRenderAttr("value", 12)} data-v-b7df1785${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 12) : ssrLooseEqual(pollDuration.value, 12)) ? " selected" : ""}>12 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</option><option${ssrRenderAttr("value", 24)} data-v-b7df1785${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 24) : ssrLooseEqual(pollDuration.value, 24)) ? " selected" : ""}>1 \u0E27\u0E31\u0E19</option><option${ssrRenderAttr("value", 72)} data-v-b7df1785${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 72) : ssrLooseEqual(pollDuration.value, 72)) ? " selected" : ""}>3 \u0E27\u0E31\u0E19</option><option${ssrRenderAttr("value", 168)} data-v-b7df1785${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 168) : ssrLooseEqual(pollDuration.value, 168)) ? " selected" : ""}>1 \u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C</option></select></label></div></div><div class="grid grid-cols-2 gap-4 mt-6" data-v-b7df1785><div data-v-b7df1785><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider" data-v-b7df1785> \u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E23\u0E27\u0E21 </label><div class="relative flex items-center" data-v-b7df1785><div class="absolute left-3 text-vikinger-cyan" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:piggy-bank",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`</div><input${ssrRenderAttr("value", pollPointsPool.value)} type="number" min="0" step="100" class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/10 rounded-xl text-sm focus:ring-1 focus:ring-vikinger-cyan outline-none" placeholder="0" data-v-b7df1785></div></div><div data-v-b7df1785><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider" data-v-b7df1785> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E19\u0E42\u0E2B\u0E27\u0E15\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 </label><div class="relative flex items-center" data-v-b7df1785><div class="absolute left-3 text-vikinger-orange" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-group",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`</div><input${ssrRenderAttr("value", maxVotes.value)} type="number" min="1" class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/10 rounded-xl text-sm focus:ring-1 focus:ring-vikinger-cyan outline-none" placeholder="100" data-v-b7df1785></div></div></div><div class="mt-4 p-4 bg-vikinger-cyan/5 dark:bg-vikinger-cyan/10 border border-vikinger-cyan/20 rounded-xl" data-v-b7df1785><div class="flex items-start gap-3" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:info-24-regular",
            class: "w-6 h-6 text-vikinger-cyan flex-shrink-0 mt-0.5"
          }, null, _parent));
          _push2(`<div class="text-sm dark:text-gray-200 w-full" data-v-b7df1785><p class="font-bold text-vikinger-cyan mb-2" data-v-b7df1785>\u0E2A\u0E23\u0E38\u0E1B\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49:</p><ul class="space-y-1.5" data-v-b7df1785><li class="flex justify-between items-center text-gray-600 dark:text-gray-400" data-v-b7df1785><span data-v-b7df1785>\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E25</span><span class="font-semibold" data-v-b7df1785>180 \u0E41\u0E15\u0E49\u0E21</span></li><li class="flex justify-between items-center text-gray-600 dark:text-gray-400" data-v-b7df1785><span data-v-b7df1785>\u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E04\u0E19\u0E42\u0E2B\u0E27\u0E15</span><span class="font-semibold" data-v-b7df1785>${ssrInterpolate(pollPointsPool.value)} \u0E41\u0E15\u0E49\u0E21</span></li><li class="flex justify-between items-center pt-2 border-t border-vikinger-cyan/20 font-bold text-gray-800 dark:text-white mt-1" data-v-b7df1785><span data-v-b7df1785>\u0E23\u0E27\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span><span data-v-b7df1785>${ssrInterpolate(180 + pollPointsPool.value)} \u0E41\u0E15\u0E49\u0E21</span></li></ul><div class="mt-3 flex items-center gap-2 text-xs text-green-600 dark:text-green-400 font-medium" data-v-b7df1785>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:gift-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`<span data-v-b7df1785>\u0E1C\u0E39\u0E49\u0E23\u0E48\u0E27\u0E21\u0E42\u0E2B\u0E27\u0E15\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E42\u0E14\u0E22\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34!</span></div>`);
          if (unref(authStore).user && unref(authStore).user.pp < 180 + pollPointsPool.value) {
            _push2(`<p class="text-xs text-red-500 font-bold mt-2" data-v-b7df1785> ! \u0E04\u0E38\u0E13\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 ${ssrInterpolate(unref(authStore).user.pp)} \u0E41\u0E15\u0E49\u0E21) </p>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div></div></div><div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30" data-v-b7df1785><button${ssrIncludeBooleanAttr(isSubmitting.value || activeTab.value === "status" && !postText.value.trim() && imagePreviews.value.length === 0 || activeTab.value === "poll" && !pollQuestion.value.trim()) ? " disabled" : ""} class="w-full py-3 px-4 bg-gradient-vikinger text-white font-semibold rounded-lg hover:shadow-vikinger transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" data-v-b7df1785>`);
          if (isSubmitting.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else if (activeTab.value === "poll") {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:poll",
              class: "w-5 h-5"
            }, null, _parent));
          } else if (scheduledDate.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:clock-outline",
              class: "w-5 h-5"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`<span data-v-b7df1785>${ssrInterpolate(isSubmitting.value ? activeTab.value === "poll" ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E25..." : "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E1E\u0E2A\u0E15\u0E4C..." : activeTab.value === "poll" ? "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E25" : scheduledDate.value ? "\u0E15\u0E31\u0E49\u0E07\u0E40\u0E27\u0E25\u0E32\u0E42\u0E1E\u0E2A\u0E15\u0E4C" : "\u0E42\u0E1E\u0E2A\u0E15\u0E4C")}</span></button></div></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/feed/CreatePostModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const CreatePostModal = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-b7df1785"]]);
const _sfc_main = {
  __name: "CreatePostBox",
  __ssrInlineRender: true,
  props: {
    context: {
      type: String,
      default: "newsfeed",
      // 'newsfeed', 'academy', 'course'
      validator: (value) => ["newsfeed", "academy", "course"].includes(value)
    },
    contextId: {
      type: Number,
      default: null
    },
    contextName: {
      type: String,
      default: ""
    }
  },
  emits: ["post-created"],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const showModal = ref(false);
    const initialTab = ref("status");
    const openModal = (tab = "status") => {
      initialTab.value = tab;
      showModal.value = true;
    };
    const closeModal = () => {
      showModal.value = false;
    };
    const handlePostCreated = (activity) => {
      emit("post-created", activity);
      closeModal();
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "contents" }, _attrs))}>`);
      _push(ssrRenderComponent(_sfc_main$2, { onOpenModal: openModal }, null, _parent));
      _push(ssrRenderComponent(CreatePostModal, {
        show: showModal.value,
        "initial-tab": initialTab.value,
        context: __props.context,
        "context-id": __props.contextId,
        "context-name": __props.contextName,
        onClose: closeModal,
        onPostCreated: handlePostCreated
      }, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/feed/CreatePostBox.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _, usePosts as u };
//# sourceMappingURL=CreatePostBox-OC44HEYf.mjs.map
