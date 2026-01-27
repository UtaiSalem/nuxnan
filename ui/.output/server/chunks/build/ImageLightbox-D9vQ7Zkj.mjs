import { ref, computed, watch, unref, useSSRContext } from 'vue';
import { ssrRenderTeleport, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderList } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, a as useNuxtApp, d as useAuthStore } from './server.mjs';

const _sfc_main = {
  __name: "ImageLightbox",
  __ssrInlineRender: true,
  props: {
    show: {
      type: Boolean,
      default: false
    },
    images: {
      type: Array,
      default: () => []
    },
    initialIndex: {
      type: Number,
      default: 0
    },
    postId: {
      type: [Number, String],
      default: null
    }
  },
  emits: ["close", "image-updated"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const { $apiFetch } = useNuxtApp();
    const authStore = useAuthStore();
    const currentIndex = ref(0);
    const currentImage = computed(() => {
      return props.images[currentIndex.value] || null;
    });
    const localIsLiked = ref(false);
    const localIsDisliked = ref(false);
    const localLikes = ref(0);
    const localDislikes = ref(0);
    const localComments = ref(0);
    const localViews = ref(0);
    const isLiking = ref(false);
    const isDisliking = ref(false);
    const isLoadingComments = ref(false);
    const isSubmittingComment = ref(false);
    const showComments = ref(false);
    const comments = ref([]);
    const newComment = ref("");
    const hasMoreComments = ref(true);
    const commentsPage = ref(1);
    watch(() => props.show, (newVal) => {
      if (newVal) {
        currentIndex.value = props.initialIndex;
        loadImageData();
      } else {
        resetState();
      }
    });
    watch(currentIndex, () => {
      loadImageData();
    });
    const loadImageData = () => {
      if (!currentImage.value) return;
      const img = currentImage.value;
      localIsLiked.value = img.isLikedByAuth || false;
      localIsDisliked.value = img.isDislikedByAuth || false;
      localLikes.value = img.likes || 0;
      localDislikes.value = img.dislikes || 0;
      localComments.value = img.comments || 0;
      localViews.value = img.views || 0;
      comments.value = img.image_comments || [];
      commentsPage.value = 1;
      hasMoreComments.value = localComments.value > comments.value.length;
    };
    const resetState = () => {
      showComments.value = false;
      comments.value = [];
      newComment.value = "";
      commentsPage.value = 1;
    };
    const formatTime = (dateString) => {
      if (!dateString) return "";
      const date = new Date(dateString);
      const now = /* @__PURE__ */ new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 6e4);
      const diffHours = Math.floor(diffMs / 36e5);
      const diffDays = Math.floor(diffMs / 864e5);
      if (diffMins < 1) return "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48";
      if (diffMins < 60) return `${diffMins} \u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diffHours < 24) return `${diffHours} \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diffDays < 7) return `${diffDays} \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      return date.toLocaleDateString("th-TH");
    };
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (__props.show && currentImage.value) {
          _push2(`<div class="fixed inset-0 z-[100] bg-black/95 flex" data-v-8813098c><button class="absolute top-4 right-4 z-10 p-2 bg-black/50 hover:bg-black/70 rounded-full text-white/80 hover:text-white transition-all" data-v-8813098c>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-6 h-6"
          }, null, _parent));
          _push2(`</button><div class="absolute top-4 left-4 z-10 px-3 py-1.5 bg-black/50 rounded-full text-white text-sm" data-v-8813098c>${ssrInterpolate(currentIndex.value + 1)} / ${ssrInterpolate(__props.images.length)}</div><div class="flex flex-1 h-full" data-v-8813098c><div class="${ssrRenderClass([showComments.value ? "w-2/3" : "w-full", "flex-1 flex items-center justify-center relative"])}" data-v-8813098c>`);
          if (currentIndex.value > 0) {
            _push2(`<button class="absolute left-4 z-10 p-3 bg-black/50 hover:bg-black/70 rounded-full text-white/80 hover:text-white transition-all" data-v-8813098c>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:chevron-left-24-regular",
              class: "w-8 h-8"
            }, null, _parent));
            _push2(`</button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<img${ssrRenderAttr("src", currentImage.value.url || currentImage.value.full_url)} class="max-w-full max-h-full object-contain" data-v-8813098c>`);
          if (currentIndex.value < __props.images.length - 1) {
            _push2(`<button class="absolute right-4 z-10 p-3 bg-black/50 hover:bg-black/70 rounded-full text-white/80 hover:text-white transition-all" data-v-8813098c>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:chevron-right-24-regular",
              class: "w-8 h-8"
            }, null, _parent));
            _push2(`</button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent" data-v-8813098c><div class="flex items-center justify-between" data-v-8813098c><div class="flex items-center gap-4 text-white/80 text-sm" data-v-8813098c>`);
          if (localViews.value > 0) {
            _push2(`<span class="flex items-center gap-1.5" data-v-8813098c>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:eye-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push2(` ${ssrInterpolate(localViews.value.toLocaleString())}</span>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><div class="flex items-center gap-2" data-v-8813098c><button${ssrIncludeBooleanAttr(isLiking.value) ? " disabled" : ""} class="${ssrRenderClass([
            "flex items-center gap-2 px-4 py-2 rounded-full transition-all",
            localIsLiked.value ? "bg-vikinger-purple text-white" : "bg-white/10 hover:bg-white/20 text-white"
          ])}" data-v-8813098c>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: localIsLiked.value ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`<span class="text-sm font-medium" data-v-8813098c>${ssrInterpolate(localLikes.value || "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(isDisliking.value) ? " disabled" : ""} class="${ssrRenderClass([
            "flex items-center gap-2 px-4 py-2 rounded-full transition-all",
            localIsDisliked.value ? "bg-red-500 text-white" : "bg-white/10 hover:bg-white/20 text-white"
          ])}" data-v-8813098c>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: localIsDisliked.value ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`<span class="text-sm font-medium" data-v-8813098c>${ssrInterpolate(localDislikes.value || "")}</span></button><button class="${ssrRenderClass([
            "flex items-center gap-2 px-4 py-2 rounded-full transition-all",
            showComments.value ? "bg-vikinger-cyan text-white" : "bg-white/10 hover:bg-white/20 text-white"
          ])}" data-v-8813098c>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:comment-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`<span class="text-sm font-medium" data-v-8813098c>${ssrInterpolate(localComments.value || "\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19")}</span></button></div></div></div></div>`);
          if (showComments.value) {
            _push2(`<div class="w-96 bg-white dark:bg-vikinger-dark-300 h-full flex flex-col border-l border-gray-200 dark:border-vikinger-dark-50/30" data-v-8813098c><div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30" data-v-8813098c><h3 class="font-bold text-gray-800 dark:text-white" data-v-8813098c>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</h3><button class="p-1 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full" data-v-8813098c>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><div class="flex-1 overflow-y-auto p-4 space-y-4" data-v-8813098c>`);
            if (isLoadingComments.value && comments.value.length === 0) {
              _push2(`<div class="flex justify-center py-8" data-v-8813098c>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-6 h-6 animate-spin text-vikinger-purple"
              }, null, _parent));
              _push2(`</div>`);
            } else if (comments.value.length === 0) {
              _push2(`<div class="text-center py-8 text-gray-500 dark:text-gray-400" data-v-8813098c>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:comment-24-regular",
                class: "w-12 h-12 mx-auto mb-2 opacity-50"
              }, null, _parent));
              _push2(`<p data-v-8813098c>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</p><p class="text-sm" data-v-8813098c>\u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19!</p></div>`);
            } else {
              _push2(`<!--[--><!--[-->`);
              ssrRenderList(comments.value, (comment) => {
                var _a2, _b, _c, _d, _e;
                _push2(`<div class="flex gap-3" data-v-8813098c><img${ssrRenderAttr("src", ((_a2 = comment.user) == null ? void 0 : _a2.avatar) || "https://i.pravatar.cc/40")} class="w-8 h-8 rounded-full object-cover flex-shrink-0" data-v-8813098c><div class="flex-1 min-w-0" data-v-8813098c><div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-xl px-3 py-2" data-v-8813098c><div class="font-medium text-sm text-gray-800 dark:text-white" data-v-8813098c>${ssrInterpolate(((_b = comment.user) == null ? void 0 : _b.name) || ((_c = comment.user) == null ? void 0 : _c.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}</div><p class="text-sm text-gray-700 dark:text-gray-300 break-words" data-v-8813098c>${ssrInterpolate(comment.content)}</p></div><div class="flex items-center gap-3 mt-1 px-1 text-xs text-gray-500" data-v-8813098c><span data-v-8813098c>${ssrInterpolate(formatTime(comment.created_at))}</span><button class="${ssrRenderClass(comment.isLikedByAuth ? "text-vikinger-purple font-medium" : "hover:text-vikinger-purple")}" data-v-8813098c> \u0E16\u0E39\u0E01\u0E43\u0E08 ${ssrInterpolate(comment.likes > 0 ? `(${comment.likes})` : "")}</button>`);
                if (((_d = comment.user) == null ? void 0 : _d.id) === ((_e = unref(authStore).user) == null ? void 0 : _e.id)) {
                  _push2(`<button class="text-red-500 hover:text-red-600" data-v-8813098c> \u0E25\u0E1A </button>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div></div></div>`);
              });
              _push2(`<!--]-->`);
              if (hasMoreComments.value && !isLoadingComments.value) {
                _push2(`<button class="w-full py-2 text-sm text-vikinger-purple hover:text-vikinger-purple/80 transition-colors" data-v-8813098c> \u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21... </button>`);
              } else {
                _push2(`<!---->`);
              }
              if (isLoadingComments.value) {
                _push2(`<div class="flex justify-center py-2" data-v-8813098c>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:spinner-ios-20-regular",
                  class: "w-5 h-5 animate-spin text-vikinger-purple"
                }, null, _parent));
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<!--]-->`);
            }
            _push2(`</div><div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30" data-v-8813098c><div class="flex gap-2" data-v-8813098c><img${ssrRenderAttr("src", ((_a = unref(authStore).user) == null ? void 0 : _a.avatar) || "https://i.pravatar.cc/40")} class="w-8 h-8 rounded-full object-cover flex-shrink-0" data-v-8813098c><div class="flex-1 relative" data-v-8813098c><input${ssrRenderAttr("value", newComment.value)} type="text" placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19..." class="w-full px-4 py-2 pr-10 bg-gray-100 dark:bg-vikinger-dark-200 rounded-full text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"${ssrIncludeBooleanAttr(isSubmittingComment.value) ? " disabled" : ""} data-v-8813098c><button${ssrIncludeBooleanAttr(!newComment.value.trim() || isSubmittingComment.value) ? " disabled" : ""} class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-vikinger-purple disabled:opacity-50" data-v-8813098c>`);
            if (isSubmittingComment.value) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-5 h-5 animate-spin"
              }, null, _parent));
            } else {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:send-24-filled",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push2(`</button></div></div></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/feed/ImageLightbox.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ImageLightbox = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-8813098c"]]);

export { ImageLightbox as I };
//# sourceMappingURL=ImageLightbox-D9vQ7Zkj.mjs.map
