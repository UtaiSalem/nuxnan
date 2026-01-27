import { defineComponent, ref, computed, watch, mergeProps, unref, withCtx, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrIncludeBooleanAttr, ssrRenderClass, ssrRenderTeleport, ssrRenderStyle, ssrRenderSlot } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import { _ as _export_sfc, d as useAuthStore, a as useNuxtApp } from './server.mjs';
import { defineStore } from 'pinia';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';
import { u as useToast } from './useToast-BpzfS75l.mjs';

const _sfc_main$4 = {
  __name: "ShareModal",
  __ssrInlineRender: true,
  props: {
    show: {
      type: Boolean,
      required: true
    },
    post: {
      type: Object,
      required: true
    }
  },
  emits: ["close", "share"],
  setup(__props, { emit: __emit }) {
    const { getAvatarUrl } = useAvatar();
    const props = __props;
    const shareComment = ref("");
    const privacy = ref("public");
    ref([]);
    ref("");
    const showPrivacyMenu = ref(false);
    ref(false);
    ref(false);
    const privacyOptions = [
      { value: "public", label: "\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30", icon: "fluent:globe-24-regular", desc: "\u0E17\u0E38\u0E01\u0E04\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E2B\u0E47\u0E19\u0E44\u0E14\u0E49" },
      { value: "friends", label: "\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19", icon: "fluent:people-24-regular", desc: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19" },
      { value: "private", label: "\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27", icon: "fluent:lock-closed-24-regular", desc: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E04\u0E38\u0E13\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19" }
    ];
    const selectedPrivacy = computed(() => {
      return privacyOptions.find((opt) => opt.value === privacy.value) || privacyOptions[0];
    });
    const previewContent = computed(() => {
      const content = props.post.content || props.post.description || "";
      return content.length > 200 ? content.substring(0, 200) + "..." : content;
    });
    const previewImage = computed(() => {
      if (props.post.imagesResources && props.post.imagesResources.length) {
        return props.post.imagesResources[0].url;
      }
      if (props.post.images && props.post.images.length) {
        return props.post.images[0].url || props.post.images[0];
      }
      return null;
    });
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a, _b;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 z-[100] flex items-center justify-center p-4" data-v-ef2092e2><div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-v-ef2092e2></div><div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-vikinger-dark-100 rounded-2xl shadow-2xl" data-v-ef2092e2><div class="sticky top-0 z-10 flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100" data-v-ef2092e2><h3 class="text-xl font-bold text-gray-800 dark:text-white" data-v-ef2092e2>\u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C</h3><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors" data-v-ef2092e2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-6 h-6 text-gray-600 dark:text-gray-300"
          }, null, _parent));
          _push2(`</button></div><div class="p-4 space-y-4" data-v-ef2092e2><div data-v-ef2092e2><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-ef2092e2> \u0E40\u0E02\u0E35\u0E22\u0E19\u0E1A\u0E32\u0E07\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E19\u0E35\u0E49... (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="3" placeholder="\u0E1A\u0E2D\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E46 \u0E27\u0E48\u0E32\u0E17\u0E33\u0E44\u0E21\u0E04\u0E38\u0E13\u0E16\u0E36\u0E07\u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E19\u0E35\u0E49..." class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-vikinger-purple/30 focus:border-vikinger-purple transition-all resize-none" data-v-ef2092e2>${ssrInterpolate(shareComment.value)}</textarea></div><div class="space-y-2" data-v-ef2092e2><div class="relative" data-v-ef2092e2><button class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors" data-v-ef2092e2><div class="flex items-center gap-3" data-v-ef2092e2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: selectedPrivacy.value.icon,
            class: "w-5 h-5 text-vikinger-purple"
          }, null, _parent));
          _push2(`<div class="text-left" data-v-ef2092e2><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-ef2092e2>${ssrInterpolate(selectedPrivacy.value.label)}</p><p class="text-xs text-gray-500 dark:text-gray-400" data-v-ef2092e2>${ssrInterpolate(selectedPrivacy.value.desc)}</p></div></div>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chevron-down-24-regular",
            class: "w-5 h-5 text-gray-400"
          }, null, _parent));
          _push2(`</button>`);
          if (showPrivacyMenu.value) {
            _push2(`<div class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-20" data-v-ef2092e2><!--[-->`);
            ssrRenderList(privacyOptions, (option) => {
              _push2(`<button class="${ssrRenderClass([{ "bg-vikinger-purple/10": privacy.value === option.value }, "w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"])}" data-v-ef2092e2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: option.icon,
                class: ["w-5 h-5", privacy.value === option.value ? "text-vikinger-purple" : "text-gray-500"]
              }, null, _parent));
              _push2(`<div class="text-left" data-v-ef2092e2><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-ef2092e2>${ssrInterpolate(option.label)}</p><p class="text-xs text-gray-500 dark:text-gray-400" data-v-ef2092e2>${ssrInterpolate(option.desc)}</p></div>`);
              if (privacy.value === option.value) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:checkmark-24-filled",
                  class: "w-5 h-5 text-vikinger-purple ml-auto"
                }, null, _parent));
              } else {
                _push2(`<!---->`);
              }
              _push2(`</button>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><button class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors opacity-50 cursor-not-allowed" data-v-ef2092e2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-add-24-regular",
            class: "w-5 h-5 text-gray-400"
          }, null, _parent));
          _push2(`<div class="text-left" data-v-ef2092e2><p class="text-sm font-medium text-gray-600 dark:text-gray-400" data-v-ef2092e2>\u0E41\u0E17\u0E47\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</p><p class="text-xs text-gray-500 dark:text-gray-500" data-v-ef2092e2>\u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49</p></div></button><button class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors opacity-50 cursor-not-allowed" data-v-ef2092e2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:location-24-regular",
            class: "w-5 h-5 text-gray-400"
          }, null, _parent));
          _push2(`<div class="text-left" data-v-ef2092e2><p class="text-sm font-medium text-gray-600 dark:text-gray-400" data-v-ef2092e2>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48</p><p class="text-xs text-gray-500 dark:text-gray-500" data-v-ef2092e2>\u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49</p></div></button></div><div class="border border-gray-200 dark:border-vikinger-dark-50/30 rounded-xl p-3 bg-gray-50 dark:bg-vikinger-dark-200/50" data-v-ef2092e2><div class="flex gap-3" data-v-ef2092e2><img${ssrRenderAttr("src", unref(getAvatarUrl)(__props.post.author || __props.post.user))} class="w-10 h-10 rounded-full object-cover flex-shrink-0" alt="" data-v-ef2092e2><div class="flex-1 min-w-0" data-v-ef2092e2><p class="font-semibold text-sm text-gray-800 dark:text-white" data-v-ef2092e2>${ssrInterpolate(((_a = __props.post.author) == null ? void 0 : _a.username) || ((_b = __props.post.user) == null ? void 0 : _b.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-3" data-v-ef2092e2>${ssrInterpolate(previewContent.value)}</p></div>`);
          if (previewImage.value) {
            _push2(`<img${ssrRenderAttr("src", previewImage.value)} class="w-20 h-20 rounded-lg object-cover flex-shrink-0" alt="" data-v-ef2092e2>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div><div class="flex items-center gap-2 p-3 bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl" data-v-ef2092e2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:info-24-regular",
            class: "w-5 h-5 text-vikinger-purple flex-shrink-0"
          }, null, _parent));
          _push2(`<p class="text-sm text-gray-700 dark:text-gray-300" data-v-ef2092e2> \u0E01\u0E32\u0E23\u0E41\u0E0A\u0E23\u0E4C\u0E08\u0E30\u0E43\u0E0A\u0E49 <span class="font-bold text-vikinger-purple" data-v-ef2092e2>36 \u0E41\u0E15\u0E49\u0E21</span> \u0E41\u0E25\u0E30\u0E40\u0E08\u0E49\u0E32\u0E02\u0E2D\u0E07\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A <span class="font-bold text-vikinger-cyan" data-v-ef2092e2>18 \u0E41\u0E15\u0E49\u0E21</span></p></div></div><div class="sticky bottom-0 flex items-center gap-3 p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100" data-v-ef2092e2><button class="flex-1 px-6 py-3 rounded-xl border border-gray-300 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-all" data-v-ef2092e2> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white font-medium hover:shadow-lg transition-all duration-300 hover:scale-105" data-v-ef2092e2><span class="flex items-center justify-center gap-2" data-v-ef2092e2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:share-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(` \u0E41\u0E0A\u0E23\u0E4C\u0E40\u0E25\u0E22 - 36 \u0E41\u0E15\u0E49\u0E21 </span></button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/share/ShareModal.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const ShareModal = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-ef2092e2"]]);
const usePollStore = defineStore("poll", () => {
  const polls = ref([]);
  const currentPoll = ref(null);
  const isLoading = ref(false);
  const getPollById = computed(() => (id) => {
    return polls.value.find((p) => p.id === id);
  });
  const getActivePolls = computed(() => {
    return polls.value.filter((p) => !p.is_ended);
  });
  const getEndedPolls = computed(() => {
    return polls.value.filter((p) => p.is_ended);
  });
  const addPoll = (poll) => {
    polls.value.unshift(poll);
  };
  const updatePoll = (id, updates) => {
    var _a;
    const index = polls.value.findIndex((p) => p.id === id);
    if (index !== -1) {
      polls.value[index] = { ...polls.value[index], ...updates };
    }
    if (((_a = currentPoll.value) == null ? void 0 : _a.id) === id) {
      currentPoll.value = { ...currentPoll.value, ...updates };
    }
  };
  const removePoll = (id) => {
    var _a;
    polls.value = polls.value.filter((p) => p.id !== id);
    if (((_a = currentPoll.value) == null ? void 0 : _a.id) === id) {
      currentPoll.value = null;
    }
  };
  const setCurrentPoll = (poll) => {
    currentPoll.value = poll;
  };
  const setPolls = (newPolls) => {
    polls.value = newPolls;
  };
  const updatePollVote = (pollId, optionIds) => {
    const poll = polls.value.find((p) => p.id === pollId);
    if (!poll) return;
    poll.user_voted = true;
    poll.user_votes = optionIds;
    poll.options = poll.options.map((option) => ({
      ...option,
      votes: optionIds.includes(option.id) ? option.votes + 1 : option.votes,
      is_user_vote: optionIds.includes(option.id)
    }));
    const totalVotes = poll.options.reduce((sum, opt) => sum + opt.votes, 0);
    poll.total_votes = totalVotes;
    poll.options = poll.options.map((option) => ({
      ...option,
      percentage: totalVotes > 0 ? Math.round(option.votes / totalVotes * 100) : 0
    }));
  };
  const closePollState = (pollId) => {
    updatePoll(pollId, { is_ended: true });
  };
  return {
    // State
    polls,
    currentPoll,
    isLoading,
    // Getters
    getPollById,
    getActivePolls,
    getEndedPolls,
    // Actions
    addPoll,
    updatePoll,
    removePoll,
    setCurrentPoll,
    setPolls,
    updatePollVote,
    closePollState
  };
});
const usePolls = () => {
  const { $apiFetch } = useNuxtApp();
  useAuthStore();
  const createPoll = async (options) => {
    var _a;
    try {
      const formData = new FormData();
      formData.append("question", options.question);
      formData.append("duration", String(options.duration));
      formData.append("is_multiple", options.is_multiple ? "1" : "0");
      formData.append("privacy_settings", String((_a = options.privacy_settings) != null ? _a : 3));
      if (options.points_pool !== void 0) {
        formData.append("points_pool", String(options.points_pool));
      }
      if (options.max_votes !== void 0) {
        formData.append("max_votes", String(options.max_votes));
      }
      options.options.forEach((option, index) => {
        formData.append(`options[${index}]`, option);
      });
      if (options.location_name) {
        formData.append("location_name", options.location_name);
      }
      if (options.tagged_users && options.tagged_users.length > 0) {
        options.tagged_users.forEach((userId, index) => {
          formData.append(`tagged_users[${index}]`, String(userId));
        });
      }
      const response = await $apiFetch("/api/polls", {
        method: "POST",
        body: formData
      });
      return response;
    } catch (error) {
      console.error("Error creating poll:", error);
      throw error;
    }
  };
  const getPoll = async (pollId) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}`);
      if (response.success && response.data) {
        return response.data;
      }
      return null;
    } catch (error) {
      console.error("Error fetching poll:", error);
      return null;
    }
  };
  const votePoll = async (pollId, optionId) => {
    try {
      const response = await $apiFetch("/api/polls/vote", {
        method: "POST",
        body: {
          poll_id: pollId,
          option_id: optionId
        }
      });
      return response;
    } catch (error) {
      console.error("Error voting on poll:", error);
      throw error;
    }
  };
  const updatePoll = async (pollId, options) => {
    try {
      const formData = new FormData();
      formData.append("_method", "PUT");
      if (options.question) {
        formData.append("question", options.question);
      }
      if (options.duration !== void 0) {
        formData.append("duration", String(options.duration));
      }
      if (options.is_multiple !== void 0) {
        formData.append("is_multiple", options.is_multiple ? "1" : "0");
      }
      if (options.options) {
        options.options.forEach((option, index) => {
          formData.append(`options[${index}]`, option);
        });
      }
      const response = await $apiFetch(`/api/polls/${pollId}`, {
        method: "POST",
        // Using POST with _method override for FormData
        body: formData
      });
      return response;
    } catch (error) {
      console.error("Error updating poll:", error);
      throw error;
    }
  };
  const closePoll = async (pollId) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/close`, {
        method: "POST"
      });
      return response;
    } catch (error) {
      console.error("Error closing poll:", error);
      throw error;
    }
  };
  const deletePoll = async (pollId) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}`, {
        method: "DELETE"
      });
      return response;
    } catch (error) {
      console.error("Error deleting poll:", error);
      throw error;
    }
  };
  const getPollResults = async (pollId) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/results`);
      if (response.success && response.data) {
        return response.data;
      }
      return null;
    } catch (error) {
      console.error("Error fetching poll results:", error);
      return null;
    }
  };
  const calculateTimeRemaining = (endsAt) => {
    const now = /* @__PURE__ */ new Date();
    const endsAtDate = new Date(endsAt);
    const diffMs = endsAtDate.getTime() - now.getTime();
    if (diffMs <= 0) {
      return "\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E41\u0E25\u0E49\u0E27";
    }
    const diffHours = Math.floor(diffMs / (1e3 * 60 * 60));
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays > 0) {
      return `${diffDays} \u0E27\u0E31\u0E19`;
    }
    if (diffHours > 0) {
      return `${diffHours} \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07`;
    }
    const diffMinutes = Math.floor(diffMs / (1e3 * 60));
    return `${diffMinutes} \u0E19\u0E32\u0E17\u0E35`;
  };
  const calculatePercentages = (options) => {
    const totalVotes = options.reduce((sum, opt) => sum + opt.votes, 0);
    return options.map((option) => ({
      ...option,
      percentage: totalVotes > 0 ? Math.round(option.votes / totalVotes * 100) : 0
    }));
  };
  const likePoll = async (pollId) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/like`, {
        method: "POST"
      });
      return response;
    } catch (error) {
      console.error("Error liking poll:", error);
      throw error;
    }
  };
  const dislikePoll = async (pollId) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/dislike`, {
        method: "POST"
      });
      return response;
    } catch (error) {
      console.error("Error disliking poll:", error);
      throw error;
    }
  };
  const commentOnPoll = async (pollId, content) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/comment`, {
        method: "POST",
        body: { content }
      });
      return response;
    } catch (error) {
      console.error("Error commenting on poll:", error);
      throw error;
    }
  };
  const deletePollComment = async (commentId) => {
    try {
      const response = await $apiFetch(`/api/poll_comments/${commentId}`, {
        method: "DELETE"
      });
      return response;
    } catch (error) {
      console.error("Error deleting poll comment:", error);
      throw error;
    }
  };
  return {
    createPoll,
    getPoll,
    votePoll,
    updatePoll,
    closePoll,
    deletePoll,
    getPollResults,
    calculateTimeRemaining,
    calculatePercentages,
    likePoll,
    dislikePoll,
    commentOnPoll,
    deletePollComment
  };
};
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "PollProgressBar",
  __ssrInlineRender: true,
  props: {
    percentage: {},
    isVoted: { type: Boolean, default: false },
    isLeading: { type: Boolean, default: false },
    color: { default: "purple" }
  },
  emits: ["click"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const barColor = computed(() => {
      const colors = {
        purple: "from-vikinger-purple to-vikinger-cyan",
        cyan: "from-vikinger-cyan to-vikinger-purple",
        green: "from-vikinger-green to-vikinger-cyan",
        orange: "from-vikinger-orange to-vikinger-yellow",
        pink: "from-vikinger-pink to-vikinger-purple"
      };
      return colors[props.color];
    });
    const barOpacity = computed(() => {
      return props.isVoted ? "opacity-20" : "opacity-10";
    });
    const barGlow = computed(() => {
      return props.isLeading ? "shadow-cyan-glow" : "";
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "poll-progress-bar-container" }, _attrs))} data-v-65ab9b50><div class="${ssrRenderClass([barOpacity.value, "poll-progress-background"])}" data-v-65ab9b50></div><div class="${ssrRenderClass([[barColor.value, barGlow.value], "poll-progress-fill"])}" style="${ssrRenderStyle({
        width: `${__props.percentage}%`,
        "--progress-width": `${__props.percentage}%`
      })}" role="progressbar"${ssrRenderAttr("aria-valuenow", __props.percentage)}${ssrRenderAttr("aria-valuemin", 0)}${ssrRenderAttr("aria-valuemax", 100)}${ssrRenderAttr("aria-label", `${__props.percentage}% of votes`)} data-v-65ab9b50></div><div class="poll-progress-content" data-v-65ab9b50>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/poll/PollProgressBar.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const PollProgressBar = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-65ab9b50"]]);
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "PollOption",
  __ssrInlineRender: true,
  props: {
    option: {},
    isVotingMode: { type: Boolean },
    isSelected: { type: Boolean, default: false },
    isMultiple: { type: Boolean, default: false },
    maxPercentage: {},
    index: {}
  },
  emits: ["click"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    computed(() => {
      return `${props.index + 1}.`;
    });
    const rankBadge = computed(() => {
      if (props.option.percentage === 100) return "\u{1F3C6}";
      if (props.option.percentage >= 50) return "\u{1F948}";
      if (props.option.percentage >= 33) return "\u{1F949}";
      return null;
    });
    const isLeading = computed(() => {
      return props.option.percentage > 0 && props.option.percentage === props.maxPercentage;
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (__props.isVotingMode) {
        _push(`<button${ssrRenderAttrs(mergeProps({
          class: ["poll-option-voting", { selected: __props.isSelected }],
          role: "radio",
          "aria-checked": __props.isSelected,
          "aria-label": `Vote for option ${__props.index + 1}: ${__props.option.text}`
        }, _attrs))} data-v-cd2fcf82><div class="poll-option-checkbox" data-v-cd2fcf82>`);
        if (__props.isSelected) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: "w-6 h-6 text-vikinger-cyan"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkbox-circle-24-regular",
            class: "w-6 h-6 text-gray-400"
          }, null, _parent));
        }
        _push(`</div><span class="poll-option-text" data-v-cd2fcf82>${ssrInterpolate(__props.option.text)}</span></button>`);
      } else {
        _push(ssrRenderComponent(PollProgressBar, mergeProps({
          percentage: __props.option.percentage,
          "is-voted": __props.option.is_user_vote,
          "is-leading": isLeading.value,
          color: __props.option.is_user_vote ? "cyan" : "purple",
          onClick: ($event) => emit("click")
        }, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="poll-option-results-content" data-v-cd2fcf82${_scopeId}><div class="flex-1" data-v-cd2fcf82${_scopeId}><div class="flex items-center gap-2" data-v-cd2fcf82${_scopeId}>`);
              if (rankBadge.value) {
                _push2(`<span class="poll-rank-badge" data-v-cd2fcf82${_scopeId}>${ssrInterpolate(rankBadge.value)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<span class="${ssrRenderClass([{ voted: __props.option.is_user_vote }, "poll-option-result-text"])}" data-v-cd2fcf82${_scopeId}>${ssrInterpolate(__props.option.text)} `);
              if (__props.option.is_user_vote) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:checkmark-24-filled",
                  class: "w-4 h-4 ml-1"
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`</span></div><div class="poll-option-votes" data-v-cd2fcf82${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:thumb-like-24-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-cd2fcf82${_scopeId}>${ssrInterpolate(__props.option.votes)} \u0E04\u0E19</span></div></div><div class="poll-option-percentage" data-v-cd2fcf82${_scopeId}>${ssrInterpolate(__props.option.percentage)}% </div></div>`);
            } else {
              return [
                createVNode("div", { class: "poll-option-results-content" }, [
                  createVNode("div", { class: "flex-1" }, [
                    createVNode("div", { class: "flex items-center gap-2" }, [
                      rankBadge.value ? (openBlock(), createBlock("span", {
                        key: 0,
                        class: "poll-rank-badge"
                      }, toDisplayString(rankBadge.value), 1)) : createCommentVNode("", true),
                      createVNode("span", {
                        class: ["poll-option-result-text", { voted: __props.option.is_user_vote }]
                      }, [
                        createTextVNode(toDisplayString(__props.option.text) + " ", 1),
                        __props.option.is_user_vote ? (openBlock(), createBlock(unref(Icon), {
                          key: 0,
                          icon: "fluent:checkmark-24-filled",
                          class: "w-4 h-4 ml-1"
                        })) : createCommentVNode("", true)
                      ], 2)
                    ]),
                    createVNode("div", { class: "poll-option-votes" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:thumb-like-24-regular",
                        class: "w-3.5 h-3.5"
                      }),
                      createVNode("span", null, toDisplayString(__props.option.votes) + " \u0E04\u0E19", 1)
                    ])
                  ]),
                  createVNode("div", { class: "poll-option-percentage" }, toDisplayString(__props.option.percentage) + "% ", 1)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      }
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/poll/PollOption.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const PollOption = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-cd2fcf82"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "PollMenu",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    pollId: {},
    isOwner: { type: Boolean },
    isEnded: { type: Boolean }
  },
  emits: ["close", "edit", "closePoll", "delete", "share"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const showDeleteConfirm = ref(false);
    const showCloseConfirm = ref(false);
    const isDeleting = ref(false);
    const isClosing = ref(false);
    const menuRef = ref(null);
    const handleClickOutside = (event) => {
      if (menuRef.value && !menuRef.value.contains(event.target)) {
        const target = event.target;
        const clickedOnTrigger = target.closest(".poll-menu-trigger");
        if (!clickedOnTrigger) {
          emit("close");
        }
      }
    };
    watch(() => props.show, (newValue) => {
      if (newValue) {
        setTimeout(() => {
          (void 0).addEventListener("click", handleClickOutside);
        }, 0);
      } else {
        (void 0).removeEventListener("click", handleClickOutside);
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      if (__props.show) {
        _push(`<div class="poll-menu" data-v-cd45284f>`);
        if (__props.isOwner) {
          _push(`<button class="poll-menu-item" data-v-cd45284f>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5 text-vikinger-purple"
          }, null, _parent));
          _push(`<span data-v-cd45284f>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1E\u0E25</span></button>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.isOwner && !__props.isEnded) {
          _push(`<button class="poll-menu-item" data-v-cd45284f>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:pause-circle-24-regular",
            class: "w-5 h-5 text-vikinger-orange"
          }, null, _parent));
          _push(`<span data-v-cd45284f>\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15</span></button>`);
        } else {
          _push(`<!---->`);
        }
        if (!__props.isEnded) {
          _push(`<button class="poll-menu-item" data-v-cd45284f>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chart-multiple-24-regular",
            class: "w-5 h-5 text-vikinger-cyan"
          }, null, _parent));
          _push(`<span data-v-cd45284f>\u0E14\u0E39\u0E1C\u0E25\u0E25\u0E31\u0E1E\u0E18\u0E4C</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button class="poll-menu-item" data-v-cd45284f>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:share-24-regular",
          class: "w-5 h-5 text-vikinger-green"
        }, null, _parent));
        _push(`<span data-v-cd45284f>\u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E25</span></button>`);
        if (__props.isOwner) {
          _push(`<div class="poll-menu-divider" data-v-cd45284f></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.isOwner) {
          _push(`<button${ssrIncludeBooleanAttr(isDeleting.value) ? " disabled" : ""} class="poll-menu-item danger" data-v-cd45284f>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: isDeleting.value ? "fluent:spinner-ios-20-regular" : "fluent:delete-24-regular",
            class: ["w-5 h-5", { "animate-spin": isDeleting.value }]
          }, null, _parent));
          _push(`<span data-v-cd45284f>${ssrInterpolate(isDeleting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E25\u0E1A..." : "\u0E25\u0E1A\u0E42\u0E1E\u0E25")}</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (showCloseConfirm.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" data-v-cd45284f><div class="bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-vikinger-lg p-6 max-w-md mx-4" data-v-cd45284f><div class="flex items-center gap-3 mb-4" data-v-cd45284f><div class="w-12 h-12 rounded-full bg-vikinger-orange/10 flex items-center justify-center" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:pause-circle-24-regular",
            class: "w-6 h-6 text-vikinger-orange"
          }, null, _parent));
          _push2(`</div><h3 class="text-xl font-bold text-gray-800 dark:text-white" data-v-cd45284f>\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15</h3></div><p class="text-gray-600 dark:text-gray-400 mb-4" data-v-cd45284f> \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15\u0E19\u0E35\u0E49\u0E43\u0E0A\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48? </p><div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mb-6 border border-yellow-200 dark:border-yellow-700/30" data-v-cd45284f><p class="text-sm text-yellow-700 dark:text-yellow-300 mb-2 font-medium" data-v-cd45284f>\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15\u0E41\u0E25\u0E49\u0E27:</p><ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1" data-v-cd45284f><li class="flex items-start gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-4 h-4 mt-0.5 flex-shrink-0"
          }, null, _parent));
          _push2(`<span data-v-cd45284f>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E08\u0E30\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E27\u0E15\u0E44\u0E14\u0E49\u0E2D\u0E35\u0E01</span></li><li class="flex items-start gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-4 h-4 mt-0.5 flex-shrink-0"
          }, null, _parent));
          _push2(`<span data-v-cd45284f>\u0E1C\u0E25\u0E25\u0E31\u0E1E\u0E18\u0E4C\u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E17\u0E31\u0E19\u0E17\u0E35</span></li><li class="flex items-start gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-4 h-4 mt-0.5 flex-shrink-0"
          }, null, _parent));
          _push2(`<span data-v-cd45284f>\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15\u0E43\u0E2B\u0E21\u0E48\u0E44\u0E14\u0E49</span></li></ul></div><div class="flex gap-3" data-v-cd45284f><button class="flex-1 py-3 px-4 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors" data-v-cd45284f> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isClosing.value) ? " disabled" : ""} class="flex-1 py-3 px-4 rounded-xl bg-vikinger-orange text-white font-semibold hover:bg-vikinger-orange/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2" data-v-cd45284f>`);
          if (isClosing.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`<span data-v-cd45284f>${ssrInterpolate(isClosing.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1B\u0E34\u0E14..." : "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15")}</span></button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        if (showDeleteConfirm.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" data-v-cd45284f><div class="bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-vikinger-lg p-6 max-w-md mx-4" data-v-cd45284f><div class="flex items-center gap-3 mb-4" data-v-cd45284f><div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-6 h-6 text-red-500"
          }, null, _parent));
          _push2(`</div><h3 class="text-xl font-bold text-gray-800 dark:text-white" data-v-cd45284f>\u0E25\u0E1A\u0E42\u0E1E\u0E25</h3></div><p class="text-gray-600 dark:text-gray-400 mb-4" data-v-cd45284f> \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E42\u0E1E\u0E25\u0E19\u0E35\u0E49\u0E43\u0E0A\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48? </p><div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 mb-6 border border-red-200 dark:border-red-700/30" data-v-cd45284f><p class="text-sm text-red-700 dark:text-red-300 mb-3 flex items-center gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:warning-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`<span class="font-medium" data-v-cd45284f>\u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E19\u0E35\u0E49\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E49\u0E2D\u0E19\u0E01\u0E25\u0E31\u0E1A\u0E44\u0E14\u0E49</span></p><p class="text-sm text-red-700 dark:text-red-300 font-medium" data-v-cd45284f>\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E25\u0E1A\u0E42\u0E1E\u0E25\u0E41\u0E25\u0E49\u0E27:</p><ul class="text-sm text-red-700 dark:text-red-300 space-y-1 mt-2" data-v-cd45284f><li class="flex items-start gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-4 h-4 mt-0.5 flex-shrink-0"
          }, null, _parent));
          _push2(`<span data-v-cd45284f>\u0E42\u0E2B\u0E27\u0E15\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E08\u0E30\u0E2B\u0E32\u0E22\u0E44\u0E1B</span></li><li class="flex items-start gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-4 h-4 mt-0.5 flex-shrink-0"
          }, null, _parent));
          _push2(`<span data-v-cd45284f>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E08\u0E30\u0E2B\u0E32\u0E22\u0E44\u0E1B</span></li><li class="flex items-start gap-2" data-v-cd45284f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-24-regular",
            class: "w-4 h-4 mt-0.5 flex-shrink-0"
          }, null, _parent));
          _push2(`<span data-v-cd45284f>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E08\u0E30\u0E16\u0E39\u0E01\u0E25\u0E1A\u0E16\u0E32\u0E27\u0E23</span></li></ul></div><div class="flex gap-3" data-v-cd45284f><button class="flex-1 py-3 px-4 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors" data-v-cd45284f> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isDeleting.value) ? " disabled" : ""} class="flex-1 py-3 px-4 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2" data-v-cd45284f>`);
          if (isDeleting.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`<span data-v-cd45284f>${ssrInterpolate(isDeleting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E25\u0E1A..." : "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A")}</span></button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`<!--]-->`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/poll/PollMenu.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const PollMenu = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-cd45284f"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "PollCard",
  __ssrInlineRender: true,
  props: {
    poll: {},
    showActions: { type: Boolean, default: true },
    isNested: { type: Boolean, default: false }
  },
  emits: ["delete", "update"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const authStore = useAuthStore();
    const pollStore = usePollStore();
    const { calculateTimeRemaining } = usePolls();
    const { getAvatarUrl } = useAvatar();
    const swal = useSweetAlert();
    const toast = useToast();
    const isVoting = ref(false);
    const selectedOptions = ref([]);
    const showResults = ref(false);
    const showMenu = ref(false);
    const localPoll = ref({ ...props.poll });
    usePolls();
    ref(false);
    ref(false);
    const showComments = ref(false);
    const newComment = ref("");
    const isCommenting = ref(false);
    usePolls();
    const isOwner = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.id) === localPoll.value.author.id;
    });
    const isEnded = computed(() => {
      return localPoll.value.is_ended;
    });
    const hasVoted = computed(() => {
      return localPoll.value.user_voted;
    });
    const currentUserAvatar = computed(() => getAvatarUrl(authStore.user));
    const canVote = computed(() => {
      return !isEnded.value && !hasVoted.value;
    });
    const timeRemaining = computed(() => {
      if (isEnded.value) {
        return "\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E41\u0E25\u0E49\u0E27";
      }
      return localPoll.value.time_remaining || calculateTimeRemaining(localPoll.value.ends_at);
    });
    const showVotingMode = computed(() => {
      return canVote.value && !showResults.value;
    });
    computed(() => {
      return !canVote.value || showResults.value || hasVoted.value;
    });
    const sortedOptions = computed(() => {
      return [...localPoll.value.options].sort((a, b) => b.votes - a.votes);
    });
    const maxPercentage = computed(() => {
      if (localPoll.value.options.length === 0) return 0;
      return Math.max(...localPoll.value.options.map((o) => o.percentage));
    });
    watch(() => props.poll, (newPoll) => {
      localPoll.value = { ...newPoll };
      if (newPoll.is_ended) {
        showResults.value = true;
      }
    }, { deep: true });
    const selectOption = (optionId) => {
      if (!canVote.value) return;
      if (localPoll.value.is_multiple) {
        const index = selectedOptions.value.indexOf(optionId);
        if (index === -1) {
          selectedOptions.value.push(optionId);
        } else {
          selectedOptions.value.splice(index, 1);
        }
      } else {
        selectedOptions.value = [optionId];
      }
    };
    const viewResults = () => {
      showResults.value = true;
    };
    const handleEdit = () => {
      emit("update");
    };
    const handleClosePoll = async () => {
      try {
        const { closePoll } = usePolls();
        const response = await closePoll(localPoll.value.id);
        if (response.success) {
          localPoll.value.is_ended = true;
          showResults.value = true;
          pollStore.closePollState(localPoll.value.id);
          toast.success("\u0E1B\u0E34\u0E14\u0E42\u0E2B\u0E27\u0E15\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!");
        } else {
          swal.error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E1B\u0E34\u0E14\u0E42\u0E1E\u0E25\u0E44\u0E14\u0E49");
        }
      } catch (error) {
        console.error("Error closing poll:", error);
        swal.error("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48");
      }
    };
    const handleDelete = async () => {
      try {
        const { deletePoll } = usePolls();
        const response = await deletePoll(localPoll.value.id);
        if (response.success) {
          pollStore.removePoll(localPoll.value.id);
          emit("delete");
          toast.success("\u0E25\u0E1A\u0E42\u0E1E\u0E25\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!");
        } else {
          swal.error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E42\u0E1E\u0E25\u0E44\u0E14\u0E49");
        }
      } catch (error) {
        console.error("Error deleting poll:", error);
        swal.error("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48");
      }
    };
    const handleShare = () => {
      toast.info("\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E41\u0E0A\u0E23\u0E4C\u0E08\u0E30\u0E40\u0E02\u0E49\u0E32\u0E21\u0E32\u0E40\u0E23\u0E47\u0E27\u0E46 \u0E19\u0E35\u0E49!");
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: [
          "poll-card",
          __props.isNested ? "poll-card-nested" : ""
        ]
      }, _attrs))} data-v-f0d0a236>`);
      if (!__props.isNested || localPoll.value.points_pool > 0 || !isEnded.value) {
        _push(`<div class="poll-header" data-v-f0d0a236>`);
        if (!__props.isNested) {
          _push(`<div class="flex items-center gap-3" data-v-f0d0a236><img${ssrRenderAttr("src", unref(getAvatarUrl)(localPoll.value.author))} class="poll-author-avatar" data-v-f0d0a236><div class="flex-1" data-v-f0d0a236><div class="flex items-center gap-2 flex-wrap" data-v-f0d0a236><span class="poll-author-name" data-v-f0d0a236>${ssrInterpolate(localPoll.value.author.name)}</span><span class="poll-badge" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:poll-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(`<span data-v-f0d0a236>\u0E42\u0E1E\u0E25</span></span></div><div class="poll-meta" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(`<span data-v-f0d0a236>${ssrInterpolate(timeRemaining.value)}</span></div></div></div>`);
        } else {
          _push(`<div class="flex items-center gap-3" data-v-f0d0a236><span class="poll-badge" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:poll-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(`<span data-v-f0d0a236>\u0E42\u0E1E\u0E25</span></span><div class="poll-meta !mt-0" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(`<span data-v-f0d0a236>${ssrInterpolate(timeRemaining.value)}</span></div></div>`);
        }
        if (localPoll.value.points_pool > 0) {
          _push(`<div class="flex flex-col items-end" data-v-f0d0a236><div class="flex items-center gap-1.5 px-3 py-1 bg-vikinger-cyan/10 rounded-lg text-vikinger-cyan" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:piggy-bank",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span class="text-sm font-bold" data-v-f0d0a236>${ssrInterpolate(localPoll.value.points_pool - localPoll.value.points_distributed)} \u0E41\u0E15\u0E49\u0E21\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D</span></div><span class="text-[10px] text-gray-500 mt-1" data-v-f0d0a236>\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E42\u0E2B\u0E27\u0E15\u0E25\u0E30 ${ssrInterpolate(localPoll.value.points_per_vote)} \u0E41\u0E15\u0E49\u0E21</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.showActions && isOwner.value) {
          _push(`<div class="relative" data-v-f0d0a236><button class="poll-menu-trigger" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:more-horizontal-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button>`);
          _push(ssrRenderComponent(PollMenu, {
            show: showMenu.value,
            "poll-id": localPoll.value.id,
            "is-owner": isOwner.value,
            "is-ended": isEnded.value,
            onClose: ($event) => showMenu.value = false,
            onEdit: handleEdit,
            onClosePoll: handleClosePoll,
            onDelete: handleDelete,
            onShare: handleShare
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<h3 class="poll-question" data-v-f0d0a236>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chart-multiple-24-regular",
        class: "w-5 h-5 text-vikinger-cyan"
      }, null, _parent));
      _push(` ${ssrInterpolate(localPoll.value.question)}</h3>`);
      if (showVotingMode.value) {
        _push(`<div class="poll-options" data-v-f0d0a236><div class="space-y-3" data-v-f0d0a236><!--[-->`);
        ssrRenderList(localPoll.value.options, (option, index) => {
          _push(ssrRenderComponent(PollOption, {
            key: option.id,
            option,
            "is-voting-mode": true,
            "is-selected": selectedOptions.value.includes(option.id),
            "is-multiple": !!localPoll.value.is_multiple,
            "max-percentage": maxPercentage.value,
            index,
            onClick: ($event) => selectOption(option.id)
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
        if (localPoll.value.points_pool > 0 && localPoll.value.points_pool - localPoll.value.points_distributed > 0) {
          _push(`<div class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700/30" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:gift-24-filled",
            class: "w-5 h-5 text-green-500 flex-shrink-0"
          }, null, _parent));
          _push(`<span class="text-sm text-green-700 dark:text-green-300" data-v-f0d0a236> \u{1F381} \u0E23\u0E48\u0E27\u0E21\u0E42\u0E2B\u0E27\u0E15\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A <strong data-v-f0d0a236>${ssrInterpolate(localPoll.value.points_per_vote)}</strong> \u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25! </span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (selectedOptions.value.length > 0) {
          _push(`<button${ssrIncludeBooleanAttr(isVoting.value) ? " disabled" : ""} class="poll-submit-btn" data-v-f0d0a236>`);
          if (isVoting.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`<span data-v-f0d0a236>${ssrInterpolate(isVoting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07\u0E42\u0E2B\u0E27\u0E15..." : "\u0E2A\u0E48\u0E07\u0E42\u0E2B\u0E27\u0E15")}</span></button>`);
        } else {
          _push(`<button class="poll-view-results-btn" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chart-multiple-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span data-v-f0d0a236>\u0E14\u0E39\u0E1C\u0E25\u0E25\u0E31\u0E1E\u0E18\u0E4C</span></button>`);
        }
        _push(`</div>`);
      } else {
        _push(`<div class="poll-options" data-v-f0d0a236><div class="space-y-3" data-v-f0d0a236><!--[-->`);
        ssrRenderList(sortedOptions.value, (option, index) => {
          _push(ssrRenderComponent(PollOption, {
            key: option.id,
            option,
            "is-voting-mode": false,
            "is-selected": false,
            "is-multiple": false,
            "max-percentage": maxPercentage.value,
            index,
            onClick: viewResults
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
        if (hasVoted.value && !isEnded.value) {
          _push(`<button class="poll-change-vote-btn" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:arrow-clockwise-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span data-v-f0d0a236>\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E42\u0E2B\u0E27\u0E15</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      if (!__props.isNested) {
        _push(`<div class="poll-footer" data-v-f0d0a236><div class="flex items-center gap-3" data-v-f0d0a236><button class="${ssrRenderClass([{ "active text-vikinger-purple": localPoll.value.user_reactions.is_liked }, "poll-action-btn"])}" data-v-f0d0a236>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: localPoll.value.user_reactions.is_liked ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-f0d0a236>${ssrInterpolate(localPoll.value.reaction_counts.likes)}</span></button><button class="${ssrRenderClass([{ "active text-red-500": localPoll.value.user_reactions.is_disliked }, "poll-action-btn"])}" data-v-f0d0a236>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: localPoll.value.user_reactions.is_disliked ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-f0d0a236>${ssrInterpolate(localPoll.value.reaction_counts.dislikes)}</span></button><button class="${ssrRenderClass([{ "active text-vikinger-cyan": showComments.value }, "poll-action-btn"])}" data-v-f0d0a236>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:comment-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-f0d0a236>${ssrInterpolate(localPoll.value.reaction_counts.comments)}</span></button></div><div class="flex items-center gap-4 text-xs text-gray-500" data-v-f0d0a236><span data-v-f0d0a236>${ssrInterpolate(localPoll.value.total_votes)} \u0E04\u0E19\u0E42\u0E2B\u0E27\u0E15</span>`);
        if (hasVoted.value) {
          _push(`<span class="text-green-500 flex items-center gap-1" data-v-f0d0a236>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(` \u0E42\u0E2B\u0E27\u0E15\u0E41\u0E25\u0E49\u0E27 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (showComments.value && !__props.isNested) {
        _push(`<div class="poll-comment-section border-t border-gray-100 dark:border-vikinger-dark-50/10" data-v-f0d0a236>`);
        if (localPoll.value.comments && localPoll.value.comments.length > 0) {
          _push(`<div class="poll-comments-list p-4 space-y-4 max-h-[300px] overflow-y-auto" data-v-f0d0a236><!--[-->`);
          ssrRenderList(localPoll.value.comments, (comment) => {
            var _a;
            _push(`<div class="flex gap-3 group" data-v-f0d0a236><img${ssrRenderAttr("src", unref(getAvatarUrl)(comment.user))} class="w-8 h-8 rounded-full object-cover flex-shrink-0" data-v-f0d0a236><div class="flex-1" data-v-f0d0a236><div class="bg-gray-50 dark:bg-vikinger-dark-100 p-3 rounded-2xl rounded-tl-none relative" data-v-f0d0a236><div class="flex items-center justify-between mb-1" data-v-f0d0a236><span class="text-sm font-bold text-vikinger-dark-50 dark:text-white" data-v-f0d0a236>${ssrInterpolate(comment.user.name)}</span><span class="text-[10px] text-gray-500" data-v-f0d0a236>${ssrInterpolate(comment.diff_humans_created_at)}</span></div><p class="text-sm text-gray-700 dark:text-gray-300" data-v-f0d0a236>${ssrInterpolate(comment.content)}</p>`);
            if (comment.user.id === ((_a = unref(authStore).user) == null ? void 0 : _a.id) || isOwner.value) {
              _push(`<button class="absolute -right-2 -top-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-sm" data-v-f0d0a236>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:delete-24-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(`</button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="p-4 border-t border-gray-50 dark:border-vikinger-dark-50/5" data-v-f0d0a236><div class="flex gap-3" data-v-f0d0a236><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-8 h-8 rounded-full object-cover" data-v-f0d0a236><div class="flex-1 relative" data-v-f0d0a236><input${ssrRenderAttr("value", newComment.value)} placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E04\u0E27\u0E32\u0E21\u0E40\u0E2B\u0E47\u0E19..." class="w-full pl-4 pr-10 py-2 bg-gray-50 dark:bg-vikinger-dark-100 border-none rounded-lg text-sm focus:ring-1 focus:ring-vikinger-purple outline-none" data-v-f0d0a236><button${ssrIncludeBooleanAttr(!newComment.value.trim() || isCommenting.value) ? " disabled" : ""} class="absolute right-2 top-1/2 -translate-y-1/2 text-vikinger-purple disabled:opacity-30" data-v-f0d0a236>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:send",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/poll/PollCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const PollCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-f0d0a236"]]);

export { PollCard as P, ShareModal as S };
//# sourceMappingURL=PollCard-DKn1EeyZ.mjs.map
