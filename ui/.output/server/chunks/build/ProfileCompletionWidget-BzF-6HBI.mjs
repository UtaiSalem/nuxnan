import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { ref, computed, mergeProps, unref, watch, resolveComponent, resolveDirective, withCtx, createTextVNode, toDisplayString, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderStyle, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrGetDirectiveProps, ssrIncludeBooleanAttr, ssrRenderTeleport, ssrLooseContain } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, d as useAuthStore, i as useApi, a as useNuxtApp, b as useRuntimeConfig } from './server.mjs';
import { P as PollCard, S as ShareModal } from './PollCard-DKn1EeyZ.mjs';
import { u as usePosts } from './CreatePostBox-OC44HEYf.mjs';
import { I as ImageLightbox } from './ImageLightbox-D9vQ7Zkj.mjs';
import { u as useToast } from './useToast-BpzfS75l.mjs';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';

const _sfc_main$2 = {
  __name: "EditPostModal",
  __ssrInlineRender: true,
  props: {
    show: {
      type: Boolean,
      default: false
    },
    post: {
      type: Object,
      default: null
    }
  },
  emits: ["close", "post-updated"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const { $apiFetch } = useNuxtApp();
    const authStore = useAuthStore();
    const {
      feelings,
      activityTypes,
      backgrounds,
      fetchPostOptions
    } = usePosts();
    const postText = ref("");
    const isSubmitting = ref(false);
    const selectedImages = ref([]);
    const existingImages = ref([]);
    const deletedImageIds = ref([]);
    ref(null);
    const imagePreviews = computed(() => {
      return [];
    });
    const allImages = computed(() => {
      const existing = existingImages.value.map((img) => ({
        id: img.id,
        url: img.url || img.full_url,
        isNew: false
      }));
      return [...existing, ...imagePreviews.value];
    });
    const selectedPrivacy = ref(3);
    const locationInput = ref("");
    const selectedFeeling = ref(null);
    const selectedActivity = ref(null);
    const activityText = ref("");
    const selectedBackground = ref(null);
    const taggedFriends = ref([]);
    const commentsDisabled = ref(false);
    const showFeelingPicker = ref(false);
    const showActivityPicker = ref(false);
    const showBackgroundPicker = ref(false);
    const showLocationInput = ref(false);
    const showTagFriends = ref(false);
    const showPrivacyOptions = ref(false);
    const friendSearchQuery = ref("");
    const friendSearchResults = ref([]);
    const isSearchingFriends = ref(false);
    const hasSearchedFriends = ref(false);
    watch(() => props.show, (newVal) => {
      if (newVal && props.post) {
        fetchPostOptions();
        loadPostData();
      }
    });
    watch(() => props.post, (newVal) => {
      if (props.show && newVal) {
        loadPostData();
      }
    }, { deep: true });
    const loadPostData = () => {
      var _a;
      const post = props.post;
      if (!post) return;
      postText.value = post.content || "";
      selectedPrivacy.value = (_a = post.privacy_settings) != null ? _a : 3;
      locationInput.value = post.location_name || "";
      commentsDisabled.value = post.comments_disabled || false;
      const postImgs = post.postImages || post.post_images || post.images || [];
      existingImages.value = postImgs.map((img) => {
        let imageUrl = img.url || img.full_url || img.thumbnail || "";
        if (imageUrl && !imageUrl.startsWith("http")) {
          const config = useRuntimeConfig();
          imageUrl = `${config.public.apiBase}/storage/${imageUrl}`;
        }
        return {
          id: img.id,
          url: imageUrl
        };
      }).filter((img) => img.url);
      deletedImageIds.value = [];
      selectedImages.value = [];
      if (post.feeling) {
        selectedFeeling.value = {
          name: post.feeling,
          icon: post.feeling_icon || "\u{1F60A}",
          name_th: post.feeling
        };
      } else {
        selectedFeeling.value = null;
      }
      if (post.activity_type) {
        selectedActivity.value = {
          name: post.activity_type,
          icon: "\u{1F3AF}",
          name_th: post.activity_type
        };
        activityText.value = post.activity_text || "";
      } else {
        selectedActivity.value = null;
        activityText.value = "";
      }
      if (post.background_color || post.background_gradient) {
        selectedBackground.value = {
          background_color: post.background_color,
          background_gradient: post.background_gradient,
          text_color: post.text_color || "#ffffff"
        };
      } else {
        selectedBackground.value = null;
      }
      taggedFriends.value = post.tagged_users || [];
      showFeelingPicker.value = false;
      showActivityPicker.value = false;
      showBackgroundPicker.value = false;
      showLocationInput.value = !!locationInput.value;
      showTagFriends.value = false;
      showPrivacyOptions.value = false;
    };
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
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a, _b;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto backdrop-blur-sm" data-v-d29e5bb2><div class="w-full max-w-2xl mx-4 mb-10 modal-content" data-v-d29e5bb2><div class="bg-white dark:bg-vikinger-dark-300 rounded-xl shadow-2xl" data-v-d29e5bb2><div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30" data-v-d29e5bb2><h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-d29e5bb2>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1E\u0E2A\u0E15\u0E4C</h2><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full" data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:close",
            class: "w-6 h-6 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-4 max-h-[70vh] overflow-y-auto" data-v-d29e5bb2><input type="file" class="hidden" accept="image/*" multiple data-v-d29e5bb2><div class="flex items-center gap-3 mb-4" data-v-d29e5bb2><img${ssrRenderAttr("src", ((_a = unref(authStore).user) == null ? void 0 : _a.avatar) || "https://i.pravatar.cc/40")} class="w-10 h-10 rounded-full object-cover" data-v-d29e5bb2><div class="flex-1" data-v-d29e5bb2><div class="font-medium text-gray-800 dark:text-white" data-v-d29e5bb2>${ssrInterpolate((_b = unref(authStore).user) == null ? void 0 : _b.name)}</div><button class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700" data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: currentPrivacy.value.icon,
            class: "w-3 h-3"
          }, null, _parent));
          _push2(`<span data-v-d29e5bb2>${ssrInterpolate(currentPrivacy.value.label)}</span>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:chevron-down",
            class: "w-3 h-3"
          }, null, _parent));
          _push2(`</button></div></div>`);
          if (showPrivacyOptions.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-d29e5bb2><div class="space-y-2" data-v-d29e5bb2><!--[-->`);
            ssrRenderList(privacyOptions, (option) => {
              _push2(`<button class="${ssrRenderClass([{ "bg-white dark:bg-vikinger-dark-100": selectedPrivacy.value === option.value }, "w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100"])}" data-v-d29e5bb2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: option.icon,
                class: ["w-5 h-5", option.color]
              }, null, _parent));
              _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>${ssrInterpolate(option.label)}</span></button>`);
            });
            _push2(`<!--]--></div><label class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-vikinger-dark-50/30 cursor-pointer" data-v-d29e5bb2><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(commentsDisabled.value) ? ssrLooseContain(commentsDisabled.value, null) : commentsDisabled.value) ? " checked" : ""} class="rounded text-vikinger-purple" data-v-d29e5bb2><span class="text-sm text-gray-600 dark:text-gray-400" data-v-d29e5bb2>\u0E1B\u0E34\u0E14\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</span></label></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (selectedFeeling.value || selectedActivity.value || locationInput.value || taggedFriends.value.length > 0) {
            _push2(`<div class="mb-3 flex flex-wrap gap-2" data-v-d29e5bb2>`);
            if (feelingActivityText.value) {
              _push2(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full text-sm" data-v-d29e5bb2>${ssrInterpolate(feelingActivityText.value)} <button class="hover:text-red-500" data-v-d29e5bb2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:close",
                class: "w-3 h-3"
              }, null, _parent));
              _push2(`</button></span>`);
            } else {
              _push2(`<!---->`);
            }
            if (locationInput.value) {
              _push2(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm" data-v-d29e5bb2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:map-marker",
                class: "w-3 h-3"
              }, null, _parent));
              _push2(` ${ssrInterpolate(locationInput.value)} <button class="hover:text-red-500" data-v-d29e5bb2>`);
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
              _push2(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm" data-v-d29e5bb2>${ssrInterpolate(friend.name)} <button class="hover:text-red-500" data-v-d29e5bb2>`);
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
          _push2(`<div class="${ssrRenderClass([selectedBackground.value ? "" : "bg-gray-50 dark:bg-vikinger-dark-200", "rounded-lg mb-4 min-h-[120px] p-4 transition-all"])}" style="${ssrRenderStyle(backgroundStyle.value)}" data-v-d29e5bb2><textarea placeholder="\u0E04\u0E38\u0E13\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E34\u0E14\u0E2D\u0E30\u0E44\u0E23\u0E2D\u0E22\u0E39\u0E48?"${ssrRenderAttr("rows", selectedBackground.value ? 5 : 3)} class="${ssrRenderClass([selectedBackground.value ? "text-center text-lg font-medium" : "text-gray-800 dark:text-white", "w-full bg-transparent border-none outline-none resize-none placeholder-gray-400"])}" style="${ssrRenderStyle(selectedBackground.value ? { color: selectedBackground.value.text_color } : {})}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>${ssrInterpolate(postText.value)}</textarea></div>`);
          if (allImages.value.length > 0) {
            _push2(`<div class="mb-4" data-v-d29e5bb2><div class="flex flex-wrap gap-2" data-v-d29e5bb2><!--[-->`);
            ssrRenderList(allImages.value, (image, index) => {
              _push2(`<div class="relative group" data-v-d29e5bb2><img${ssrRenderAttr("src", image.url)} class="w-32 h-32 object-cover rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" data-v-d29e5bb2>`);
              if (image.isNew) {
                _push2(`<div class="absolute top-1 left-1 px-1.5 py-0.5 bg-green-500 text-white text-xs rounded" data-v-d29e5bb2>\u0E43\u0E2B\u0E21\u0E48</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<button class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity" data-v-d29e5bb2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:close",
                class: "w-4 h-4"
              }, null, _parent));
              _push2(`</button></div>`);
            });
            _push2(`<!--]-->`);
            if (allImages.value.length < 20) {
              _push2(`<button class="w-32 h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center hover:border-vikinger-purple hover:bg-vikinger-purple/5 transition-all" data-v-d29e5bb2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:plus",
                class: "w-8 h-8 text-gray-400"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><p class="text-xs text-gray-500 mt-1" data-v-d29e5bb2>${ssrInterpolate(allImages.value.length)}/20 \u0E23\u0E39\u0E1B</p></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showBackgroundPicker.value && allImages.value.length === 0) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-d29e5bb2><div class="flex items-center justify-between mb-3" data-v-d29e5bb2><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1E\u0E37\u0E49\u0E19\u0E2B\u0E25\u0E31\u0E07</span><button data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><div class="flex flex-wrap gap-2" data-v-d29e5bb2><button class="${ssrRenderClass([!selectedBackground.value ? "border-vikinger-purple" : "border-gray-300", "w-10 h-10 rounded-lg border-2 flex items-center justify-center"])}" data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:format-clear",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button><!--[-->`);
            ssrRenderList(unref(backgrounds), (bg) => {
              var _a2;
              _push2(`<button class="${ssrRenderClass([((_a2 = selectedBackground.value) == null ? void 0 : _a2.id) === bg.id ? "border-vikinger-purple scale-110" : "border-transparent", "w-10 h-10 rounded-lg border-2 transition-all"])}" style="${ssrRenderStyle({ background: bg.background_gradient || bg.background_color })}" data-v-d29e5bb2></button>`);
            });
            _push2(`<!--]--></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showFeelingPicker.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg max-h-48 overflow-y-auto" data-v-d29e5bb2><div class="flex items-center justify-between mb-3" data-v-d29e5bb2><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E04\u0E38\u0E13\u0E23\u0E39\u0E49\u0E2A\u0E36\u0E01\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E44\u0E23?</span><button data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><!--[-->`);
            ssrRenderList(feelingsByCategory.value, (categoryFeelings, category) => {
              _push2(`<div class="mb-3" data-v-d29e5bb2><div class="text-xs text-gray-500 uppercase mb-2" data-v-d29e5bb2>${ssrInterpolate(category)}</div><div class="grid grid-cols-5 gap-2" data-v-d29e5bb2><!--[-->`);
              ssrRenderList(categoryFeelings, (feeling) => {
                var _a2;
                _push2(`<button class="${ssrRenderClass([{ "bg-vikinger-purple/10 ring-1 ring-vikinger-purple": ((_a2 = selectedFeeling.value) == null ? void 0 : _a2.id) === feeling.id }, "flex flex-col items-center p-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100"])}" data-v-d29e5bb2><span class="text-xl" data-v-d29e5bb2>${ssrInterpolate(feeling.icon)}</span><span class="text-xs text-gray-600 dark:text-gray-400 truncate w-full text-center" data-v-d29e5bb2>${ssrInterpolate(feeling.name_th || feeling.name)}</span></button>`);
              });
              _push2(`<!--]--></div></div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showActivityPicker.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg max-h-48 overflow-y-auto" data-v-d29e5bb2><div class="flex items-center justify-between mb-3" data-v-d29e5bb2><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E04\u0E38\u0E13\u0E01\u0E33\u0E25\u0E31\u0E07\u0E17\u0E33\u0E2D\u0E30\u0E44\u0E23?</span><button data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><!--[-->`);
            ssrRenderList(activitiesByCategory.value, (categoryActivities, category) => {
              _push2(`<div class="mb-3" data-v-d29e5bb2><div class="text-xs text-gray-500 uppercase mb-2" data-v-d29e5bb2>${ssrInterpolate(category)}</div><div class="grid grid-cols-3 gap-2" data-v-d29e5bb2><!--[-->`);
              ssrRenderList(categoryActivities, (activity) => {
                var _a2;
                _push2(`<button class="${ssrRenderClass([{ "bg-vikinger-purple/10 ring-1 ring-vikinger-purple": ((_a2 = selectedActivity.value) == null ? void 0 : _a2.id) === activity.id }, "flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100 text-left"])}" data-v-d29e5bb2><span class="text-lg" data-v-d29e5bb2>${ssrInterpolate(activity.icon)}</span><span class="text-xs text-gray-600 dark:text-gray-400 truncate" data-v-d29e5bb2>${ssrInterpolate(activity.name_th || activity.name)}</span></button>`);
              });
              _push2(`<!--]--></div></div>`);
            });
            _push2(`<!--]-->`);
            if (selectedActivity.value) {
              _push2(`<div class="mt-3" data-v-d29e5bb2><input${ssrRenderAttr("value", activityText.value)} type="text"${ssrRenderAttr("placeholder", `${selectedActivity.value.name_th || selectedActivity.value.name}...`)} class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" data-v-d29e5bb2></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showLocationInput.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-d29e5bb2><div class="flex items-center justify-between mb-3" data-v-d29e5bb2><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48</span><button data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div><div class="flex items-center gap-2" data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:map-marker",
              class: "w-5 h-5 text-red-500"
            }, null, _parent));
            _push2(`<input${ssrRenderAttr("value", locationInput.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2B\u0E23\u0E37\u0E2D\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48..." class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" data-v-d29e5bb2></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (showTagFriends.value) {
            _push2(`<div class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-d29e5bb2><div class="flex items-center justify-between mb-3" data-v-d29e5bb2><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E41\u0E17\u0E47\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span><button data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push2(`</button></div>`);
            if (taggedFriends.value.length > 0) {
              _push2(`<div class="flex flex-wrap gap-2 mb-3" data-v-d29e5bb2><!--[-->`);
              ssrRenderList(taggedFriends.value, (friend) => {
                _push2(`<div class="flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded-full" data-v-d29e5bb2><img${ssrRenderAttr("src", friend.avatar || "https://i.pravatar.cc/40")} class="w-5 h-5 rounded-full" data-v-d29e5bb2><span class="text-xs text-blue-700 dark:text-blue-300" data-v-d29e5bb2>${ssrInterpolate(friend.name)}</span><button class="ml-1 text-blue-500 hover:text-red-500" data-v-d29e5bb2>`);
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
            _push2(`<div class="relative" data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:magnify",
              class: "absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
            }, null, _parent));
            _push2(`<input${ssrRenderAttr("value", friendSearchQuery.value)} type="text" placeholder="\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32..." class="w-full pl-9 pr-10 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-all" data-v-d29e5bb2>`);
            if (isSearchingFriends.value) {
              _push2(`<div class="absolute right-2 top-1/2 -translate-y-1/2" data-v-d29e5bb2><div class="w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" data-v-d29e5bb2></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            if (isSearchingFriends.value) {
              _push2(`<div class="mt-2 p-4 text-center" data-v-d29e5bb2><div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" data-v-d29e5bb2><div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" data-v-d29e5bb2></div><span data-v-d29e5bb2>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32...</span></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (friendSearchResults.value.length > 0) {
              _push2(`<div class="mt-2 space-y-1 max-h-40 overflow-y-auto border border-gray-200 dark:border-vikinger-dark-50/30 rounded-lg bg-white dark:bg-vikinger-dark-100" data-v-d29e5bb2><!--[-->`);
              ssrRenderList(friendSearchResults.value, (friend) => {
                _push2(`<button class="${ssrRenderClass([{ "opacity-50 cursor-not-allowed": taggedFriends.value.find((f) => f.id === friend.id) }, "w-full flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"])}"${ssrIncludeBooleanAttr(!!taggedFriends.value.find((f) => f.id === friend.id)) ? " disabled" : ""} data-v-d29e5bb2><img${ssrRenderAttr("src", friend.avatar || "https://i.pravatar.cc/40")} class="w-8 h-8 rounded-full" data-v-d29e5bb2><div class="flex-1 text-left" data-v-d29e5bb2><span class="text-sm text-gray-800 dark:text-white block" data-v-d29e5bb2>${ssrInterpolate(friend.name)}</span>`);
                if (friend.username) {
                  _push2(`<span class="text-xs text-gray-500" data-v-d29e5bb2>@${ssrInterpolate(friend.username)}</span>`);
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
              _push2(`<div class="mt-2 p-3 text-center text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-vikinger-dark-100 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" data-v-d29e5bb2>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:account-search",
                class: "w-8 h-8 mx-auto mb-1 text-gray-400"
              }, null, _parent));
              _push2(`<p data-v-d29e5bb2>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E0A\u0E37\u0E48\u0E2D &quot;${ssrInterpolate(friendSearchQuery.value)}&quot;</p></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (!friendSearchQuery.value) {
              _push2(`<p class="mt-2 text-xs text-gray-500 dark:text-gray-400" data-v-d29e5bb2>\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 1 \u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="flex flex-wrap gap-2 border-t border-gray-200 dark:border-vikinger-dark-50/30 pt-4" data-v-d29e5bb2><button class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:image-multiple",
            class: "w-5 h-5 text-green-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></button><button class="${ssrRenderClass([{ "border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20": selectedFeeling.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:emoticon-happy",
            class: "w-5 h-5 text-yellow-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49\u0E2A\u0E36\u0E01</span></button><button class="${ssrRenderClass([{ "border-orange-400 bg-orange-50 dark:bg-orange-900/20": selectedActivity.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:run",
            class: "w-5 h-5 text-orange-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</span></button><button class="${ssrRenderClass([{ "border-red-400 bg-red-50 dark:bg-red-900/20": locationInput.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:map-marker",
            class: "w-5 h-5 text-red-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48</span></button><button class="${ssrRenderClass([{ "border-blue-400 bg-blue-50 dark:bg-blue-900/20": taggedFriends.value.length > 0 }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-multiple-plus",
            class: "w-5 h-5 text-blue-500"
          }, null, _parent));
          _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E41\u0E17\u0E47\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span></button>`);
          if (allImages.value.length === 0) {
            _push2(`<button class="${ssrRenderClass([{ "border-purple-400 bg-purple-50 dark:bg-purple-900/20": selectedBackground.value }, "flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all"])}"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-d29e5bb2>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:palette",
              class: "w-5 h-5 text-purple-500"
            }, null, _parent));
            _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-d29e5bb2>\u0E1E\u0E37\u0E49\u0E19\u0E2B\u0E25\u0E31\u0E07</span></button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div><div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30" data-v-d29e5bb2><button${ssrIncludeBooleanAttr(isSubmitting.value || !postText.value.trim() && allImages.value.length === 0) ? " disabled" : ""} class="w-full py-3 px-4 bg-gradient-vikinger text-white font-semibold rounded-lg hover:shadow-vikinger transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" data-v-d29e5bb2>`);
          if (isSubmitting.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`<span data-v-d29e5bb2>${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02")}</span></button></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/feed/EditPostModal.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const EditPostModal = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-d29e5bb2"]]);
const contentLimit = 300;
const _sfc_main$1 = {
  __name: "FeedPost",
  __ssrInlineRender: true,
  props: {
    post: {
      type: Object,
      required: true
    },
    // For nested posts (shared posts)
    isNested: {
      type: Boolean,
      default: false
    }
  },
  emits: ["delete-success", "post-updated"],
  setup(__props, { emit: __emit }) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k;
    const authStore = useAuthStore();
    useToast();
    const api = useApi();
    const swal = useSweetAlert();
    const { getAvatarUrl } = useAvatar();
    const props = __props;
    const emit = __emit;
    const isActivity = computed(() => !!props.post.target_resource);
    const postData = computed(() => {
      if (props.post.target_resource) {
        return props.post.target_resource;
      }
      return props.post;
    });
    const activityAction = computed(() => props.post.action || null);
    const actionTo = computed(() => props.post.action_to || null);
    const shareComment = computed(() => {
      var _a2;
      if (isShareActivity.value && ((_a2 = props.post.target_resource) == null ? void 0 : _a2.share_comment)) {
        return props.post.target_resource.share_comment;
      }
      if (props.post.share_comment) {
        return props.post.share_comment;
      }
      if (props.post.activity_details) {
        try {
          const details = typeof props.post.activity_details === "string" ? JSON.parse(props.post.activity_details) : props.post.activity_details;
          return details.share_comment || null;
        } catch (e) {
          console.error("Failed to parse activity_details:", e);
          return null;
        }
      }
      return null;
    });
    const isShareActivity = computed(() => {
      const shareActions = ["share_post", "share", "repost"];
      return shareActions.includes(activityAction.value);
    });
    const isSameActor = computed(() => {
      var _a2, _b2;
      if (!isActivity.value) return true;
      const actorId = (_a2 = props.post.action_by) == null ? void 0 : _a2.id;
      const authorId = (_b2 = postAuthor.value) == null ? void 0 : _b2.id;
      if (isShareActivity.value) return false;
      if (!actorId || !authorId) return true;
      return actorId === authorId;
    });
    computed(() => {
      if (!activityAction.value) return null;
      const actionMap = {
        "create_post": "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E2B\u0E21\u0E48",
        "share_post": "\u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C",
        "share": "\u0E41\u0E0A\u0E23\u0E4C",
        "comment": "\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19",
        "like": "\u0E16\u0E39\u0E01\u0E43\u0E08",
        "donate": "\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04",
        "receive_donation": "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19",
        "create": "\u0E2A\u0E23\u0E49\u0E32\u0E07",
        "update": "\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15",
        "join": "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21",
        "enroll": "\u0E25\u0E07\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19\u0E40\u0E23\u0E35\u0E22\u0E19",
        "complete": "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A"
      };
      return actionMap[activityAction.value] || activityAction.value;
    });
    const actionTextShort = computed(() => {
      if (!activityAction.value) return null;
      const actionMap = {
        "create_post": "\u0E42\u0E1E\u0E2A\u0E15\u0E4C",
        "share_post": "\u0E41\u0E0A\u0E23\u0E4C",
        "share": "\u0E41\u0E0A\u0E23\u0E4C",
        "comment": "\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19",
        "like": "\u0E16\u0E39\u0E01\u0E43\u0E08",
        "donate": "\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04",
        "receive_donation": "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19",
        "create": "\u0E2A\u0E23\u0E49\u0E32\u0E07",
        "update": "\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15",
        "join": "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21",
        "enroll": "\u0E25\u0E07\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19",
        "complete": "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A"
      };
      return actionMap[activityAction.value] || null;
    });
    computed(() => {
      if (!activityAction.value) return null;
      const iconMap = {
        "create_post": "fluent:add-circle-24-regular",
        "share_post": "fluent:share-24-regular",
        "share": "fluent:share-24-regular",
        "comment": "fluent:comment-24-regular",
        "like": "fluent:thumb-like-24-regular",
        "donate": "fluent:heart-24-regular",
        "receive_donation": "fluent:gift-24-regular",
        "create": "fluent:add-24-regular",
        "update": "fluent:edit-24-regular",
        "join": "fluent:people-add-24-regular",
        "enroll": "fluent:book-add-24-regular",
        "complete": "fluent:checkmark-circle-24-regular"
      };
      return iconMap[activityAction.value] || "fluent:flash-24-regular";
    });
    const modelTypeText = computed(() => {
      if (!actionTo.value) return null;
      const modelMap = {
        "Post": "",
        // No suffix needed for regular posts
        "CoursePost": "\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32",
        // Changed from 'โพสต์ในรายวิชา' to avoid duplication
        "AcademyPost": "\u0E43\u0E19\u0E2A\u0E16\u0E32\u0E1A\u0E31\u0E19",
        // Changed from 'โพสต์ในสถาบัน' to avoid duplication
        "Donate": "",
        "DonateRecipient": "",
        "Poll": "\u0E42\u0E1E\u0E25",
        "Support": "\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19",
        "SupportViewer": "",
        "Course": "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32",
        "Academy": "\u0E2A\u0E16\u0E32\u0E1A\u0E31\u0E19"
      };
      return modelMap[actionTo.value] || "";
    });
    const contextInfo = computed(() => {
      var _a2, _b2, _c2, _d2, _e2, _f2, _g2, _h2;
      const data = postData.value;
      if (data.course) {
        return {
          type: "course",
          icon: "fluent:book-24-regular",
          name: data.course.name || data.course.title,
          link: `/courses/${data.course_id || data.course.id}`,
          academy: ((_a2 = data.academy) == null ? void 0 : _a2.name) || null,
          academyLink: data.academy ? `/academies/${data.academy.id}` : null,
          color: "text-blue-500"
        };
      }
      if (data.academy && !data.course) {
        return {
          type: "academy",
          icon: "fluent:building-24-regular",
          name: data.academy.name,
          link: `/academies/${data.academy.id}`,
          color: "text-purple-500"
        };
      }
      if (actionTo.value === "Donate") {
        return {
          type: "donate",
          icon: "fluent:heart-24-regular",
          name: "\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04\u0E43\u0E2B\u0E49 " + (((_b2 = data.user) == null ? void 0 : _b2.username) || "Nuxni"),
          link: ((_c2 = data.user) == null ? void 0 : _c2.id) ? `/profile/${data.user.id}` : null,
          amount: data.amounts,
          color: "text-pink-500"
        };
      }
      if (actionTo.value === "DonateRecipient") {
        return {
          type: "donate_recipient",
          icon: "fluent:gift-24-regular",
          name: "\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E08\u0E32\u0E01 " + (((_d2 = data.donation) == null ? void 0 : _d2.donor_name) || "\u0E44\u0E21\u0E48\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E2D\u0E2D\u0E01\u0E19\u0E32\u0E21"),
          link: ((_f2 = (_e2 = data.donation) == null ? void 0 : _e2.donor) == null ? void 0 : _f2.id) ? `/profile/${data.donation.donor.id}` : null,
          points: data.points_received || 240,
          // แสดงแต้มที่ได้รับแทนเงิน
          color: "text-green-500"
        };
      }
      if (actionTo.value === "Poll" || data.poll) {
        const pollId = ((_g2 = data.poll) == null ? void 0 : _g2.id) || data.id;
        return {
          type: "poll",
          icon: "fluent:poll-24-regular",
          name: ((_h2 = data.poll) == null ? void 0 : _h2.title) || "\u0E42\u0E1E\u0E25",
          link: pollId ? `/polls/${pollId}` : null,
          color: "text-yellow-500"
        };
      }
      return null;
    });
    const postAuthor = computed(() => {
      var _a2;
      if (isShareActivity.value && ((_a2 = props.post.target_resource) == null ? void 0 : _a2.shareable)) {
        return props.post.target_resource.shareable.author || props.post.target_resource.shareable.user || {};
      }
      return postData.value.author || postData.value.user || {};
    });
    const actionBy = computed(() => {
      return props.post.action_by || postAuthor.value;
    });
    const postAuthorAvatar = computed(() => getAvatarUrl(postAuthor.value));
    const actionByAvatar = computed(() => getAvatarUrl(actionBy.value));
    const currentUserAvatar = computed(() => getAvatarUrl(authStore.user));
    const getCommentAvatar = (comment) => getAvatarUrl((comment == null ? void 0 : comment.user) || (comment == null ? void 0 : comment.author));
    const createdTime = computed(() => {
      return postData.value.diff_humans_created_at || props.post.diff_humans_created_at || postData.value.createdAt || props.post.createdAt || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48";
    });
    const hashtags = computed(() => postData.value.hashtags || []);
    const location = computed(() => postData.value.location);
    const privacySetting = computed(() => postData.value.privacy_settings || postData.value.privacy_setting || "public");
    const postType = computed(() => postData.value.post_type || "text");
    computed(() => postData.value.isLikedByAuth || false);
    computed(() => postData.value.isDislikedByAuth || false);
    computed(() => postData.value.likes || 0);
    computed(() => postData.value.dislikes || 0);
    const views = computed(() => postData.value.views || 0);
    computed(() => postData.value.comments_count || 0);
    computed(() => postData.value.shares || 0);
    const hasPoll = computed(() => {
      return actionTo.value === "Poll" || postData.value.poll !== void 0;
    });
    const pollData = computed(() => {
      if (actionTo.value === "Poll") {
        return postData.value;
      }
      return postData.value.poll || null;
    });
    const isPollOwner = computed(() => {
      var _a2, _b2, _c2, _d2;
      return ((_a2 = authStore.user) == null ? void 0 : _a2.id) === ((_b2 = pollData.value) == null ? void 0 : _b2.user_id) || ((_c2 = authStore.user) == null ? void 0 : _c2.id) === ((_d2 = postAuthor.value) == null ? void 0 : _d2.id);
    });
    const isPollOnlyActivity = computed(() => {
      if (actionTo.value === "Poll") return true;
      if (postData.value.post_type === "poll" && postData.value.poll) return true;
      return false;
    });
    const feeling = computed(() => postData.value.feeling || null);
    const feelingIcon = computed(() => postData.value.feeling_icon || null);
    const activityType = computed(() => postData.value.activity_type || null);
    const activityText = computed(() => postData.value.activity_text || null);
    const feelingDisplay = computed(() => {
      const parts = [];
      if (feeling.value) {
        const icon = feelingIcon.value || "\u{1F60A}";
        parts.push(`${icon} \u0E23\u0E39\u0E49\u0E2A\u0E36\u0E01${feeling.value}`);
      }
      if (activityType.value) {
        const text = activityText.value ? ` ${activityText.value}` : "";
        parts.push(`\u0E01\u0E33\u0E25\u0E31\u0E07${activityType.value}${text}`);
      }
      return parts.length > 0 ? parts.join(" \u2014 ") : null;
    });
    const postContent = computed(() => {
      if (actionTo.value === "Donate") {
        return postData.value.notes ? `\u{1F49D} ${postData.value.notes}` : `\u{1F49D} \u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04 ${postData.value.amounts}`;
      }
      if (actionTo.value === "DonateRecipient") {
        const donation = postData.value.donation;
        return (donation == null ? void 0 : donation.notes) || "";
      }
      return postData.value.content || postData.value.description || "";
    });
    const isContentExpanded = ref(false);
    const shouldTruncate = computed(() => {
      return postContent.value.length > contentLimit;
    });
    const displayContent = computed(() => {
      if (!shouldTruncate.value || isContentExpanded.value) {
        return postContent.value;
      }
      return postContent.value.substring(0, contentLimit) + "...";
    });
    const images = computed(() => {
      if (postData.value.imagesResources && postData.value.imagesResources.length) {
        return postData.value.imagesResources;
      }
      if (postData.value.images && postData.value.images.length) {
        return postData.value.images;
      }
      if (postData.value.slip) {
        return [{ url: postData.value.slip }];
      }
      return [];
    });
    const privacyIcon = computed(() => {
      switch (privacySetting.value) {
        case "friends":
          return "fluent:people-24-regular";
        case "private":
          return "fluent:lock-closed-24-regular";
        default:
          return "fluent:globe-24-regular";
      }
    });
    const postTypeBadge = computed(() => {
      const configs = {
        "CoursePost": { icon: "fluent:book-24-regular", color: "bg-blue-500", label: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32" },
        "Donate": { icon: "fluent:heart-24-regular", color: "bg-pink-500", label: "\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04" },
        "DonateRecipient": { icon: "fluent:gift-24-regular", color: "bg-green-500", label: "\u0E23\u0E31\u0E1A\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04" },
        "Poll": { icon: "fluent:poll-24-regular", color: "bg-yellow-500", label: "\u0E42\u0E1E\u0E25" },
        "Post": { icon: "fluent:document-text-24-regular", color: "bg-purple-500", label: "\u0E42\u0E1E\u0E2A\u0E15\u0E4C" }
      };
      return configs[actionTo.value] || configs[postType.value] || null;
    });
    const showComments = ref(true);
    const newComment = ref("");
    ref(false);
    const selectedImageIndex = ref(null);
    const replyingTo = ref(null);
    const replyContent = ref("");
    const isSubmittingReply = ref(false);
    const expandedReplies = ref({});
    const commentReplies = ref({});
    const loadingReplies = ref({});
    const repliesPagination = ref({});
    const newlyAddedComments = ref([]);
    const olderComments = ref([]);
    const isLoadingComments = ref(false);
    ref(1);
    const hasMorePages = ref(true);
    const preLoadedComments = computed(() => {
      var _a2, _b2;
      if (isShareActivity.value && ((_a2 = shareData.value) == null ? void 0 : _a2.share_comments)) {
        return shareData.value.share_comments;
      }
      return ((_b2 = postData.value) == null ? void 0 : _b2.post_comments) || [];
    });
    const displayedComments = computed(() => {
      return [...newlyAddedComments.value, ...preLoadedComments.value, ...olderComments.value];
    });
    const hasMoreComments = computed(() => {
      const totalCount = localCommentsCount.value;
      const displayedCount = displayedComments.value.length;
      return hasMorePages.value && totalCount > displayedCount;
    });
    const remainingCommentsCount = computed(() => {
      return Math.max(0, localCommentsCount.value - displayedComments.value.length);
    });
    const localIsLiked = ref(((_a = postData.value) == null ? void 0 : _a.isLikedByAuth) || false);
    const localIsDisliked = ref(((_b = postData.value) == null ? void 0 : _b.isDislikedByAuth) || false);
    const localLikes = ref(((_c = postData.value) == null ? void 0 : _c.likes) || 0);
    const localDislikes = ref(((_d = postData.value) == null ? void 0 : _d.dislikes) || 0);
    const localCommentsCount = ref(((_e = postData.value) == null ? void 0 : _e.comments_count) || 0);
    const isLiking = ref(false);
    const isDisliking = ref(false);
    const isCommenting = ref(false);
    const isSharing = ref(false);
    const showShareModal = ref(false);
    const showShareMenu = ref(false);
    const showOptionsMenu = ref(false);
    const showPostOptionsMenu = ref(false);
    const showEditModal = ref(false);
    const localShares = ref(((_f = postData.value) == null ? void 0 : _f.shares) || 0);
    const isDeletingPost = ref(false);
    const isOwnPost = computed(() => {
      var _a2, _b2;
      return ((_a2 = authStore.user) == null ? void 0 : _a2.id) === ((_b2 = postAuthor.value) == null ? void 0 : _b2.id);
    });
    const isOwnShare = computed(() => {
      var _a2, _b2;
      if (!isShareActivity.value) return false;
      return ((_a2 = authStore.user) == null ? void 0 : _a2.id) === ((_b2 = actionBy.value) == null ? void 0 : _b2.id);
    });
    watch(() => postData.value, (newData) => {
      if (newData) {
        localIsLiked.value = newData.isLikedByAuth || false;
        localIsDisliked.value = newData.isDislikedByAuth || false;
        localLikes.value = newData.likes || 0;
        localDislikes.value = newData.dislikes || 0;
        localCommentsCount.value = newData.comments_count || 0;
        localShares.value = newData.shares || 0;
      }
    }, { immediate: true });
    const reactions = [
      { id: "like", icon: "\u{1F44D}", label: "Like", color: "hover:bg-blue-100 dark:hover:bg-blue-900/30" },
      { id: "love", icon: "\u2764\uFE0F", label: "Love", color: "hover:bg-red-100 dark:hover:bg-red-900/30" },
      { id: "haha", icon: "\u{1F604}", label: "Haha", color: "hover:bg-yellow-100 dark:hover:bg-yellow-900/30" },
      { id: "wow", icon: "\u{1F62E}", label: "Wow", color: "hover:bg-orange-100 dark:hover:bg-orange-900/30" },
      { id: "sad", icon: "\u{1F622}", label: "Sad", color: "hover:bg-gray-100 dark:hover:bg-gray-900/30" },
      { id: "angry", icon: "\u{1F620}", label: "Angry", color: "hover:bg-red-100 dark:hover:bg-red-900/30" }
    ];
    const closeImageModal = () => {
      selectedImageIndex.value = null;
    };
    const handleShareSubmit = async (shareData2) => {
      var _a2, _b2, _c2, _d2, _e2;
      if (isSharing.value || !((_a2 = postData.value) == null ? void 0 : _a2.id)) return;
      const pointsRequired = 36;
      const currentPoints = authStore.points || 0;
      const hasEnough = authStore.deductPoints(pointsRequired);
      if (!hasEnough) {
        swal.warning(`\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D\u0E43\u0E19\u0E01\u0E32\u0E23\u0E41\u0E0A\u0E23\u0E4C

\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23: ${pointsRequired} \u0E41\u0E15\u0E49\u0E21
\u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48: ${currentPoints} \u0E41\u0E15\u0E49\u0E21
\u0E02\u0E32\u0E14\u0E2D\u0E35\u0E01: ${pointsRequired - currentPoints} \u0E41\u0E15\u0E49\u0E21`, "\u0E41\u0E15\u0E49\u0E21\u0E44\u0E21\u0E48\u0E1E\u0E2D");
        return;
      }
      isSharing.value = true;
      localShares.value++;
      if (((_b2 = postAuthor.value) == null ? void 0 : _b2.points) !== void 0) {
        postAuthor.value.points = (postAuthor.value.points || 0) + 18;
      }
      try {
        let shareableType = "Post";
        if (actionTo.value === "CoursePost") {
          shareableType = "CoursePost";
        } else if (actionTo.value === "AcademyPost") {
          shareableType = "AcademyPost";
        }
        const response = await api.call("/api/shares", {
          method: "POST",
          body: {
            shareable_type: shareableType,
            shareable_id: postData.value.id,
            ...shareData2
          }
        });
        if (!response.success) {
          localShares.value--;
          authStore.rollback(pointsRequired);
          if (((_c2 = postAuthor.value) == null ? void 0 : _c2.points) !== void 0) {
            postAuthor.value.points = (postAuthor.value.points || 0) - 18;
          }
          swal.error(response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E41\u0E0A\u0E23\u0E4C\u0E44\u0E14\u0E49");
        } else {
          swal.toast("\u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08! \u{1F389}", "success");
        }
      } catch (error) {
        localShares.value--;
        authStore.rollback(pointsRequired);
        if (((_d2 = postAuthor.value) == null ? void 0 : _d2.points) !== void 0) {
          postAuthor.value.points = (postAuthor.value.points || 0) - 18;
        }
        console.error("Failed to share:", error);
        const errorMsg = ((_e2 = error == null ? void 0 : error.data) == null ? void 0 : _e2.message) || (error == null ? void 0 : error.message) || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E41\u0E0A\u0E23\u0E4C";
        swal.error(errorMsg);
      } finally {
        isSharing.value = false;
      }
    };
    const shareData = computed(() => {
      if (isShareActivity.value && props.post.target_resource) {
        return props.post.target_resource;
      }
      return null;
    });
    const localShareIsLiked = ref(((_g = shareData.value) == null ? void 0 : _g.isLikedByAuth) || false);
    const localShareIsDisliked = ref(((_h = shareData.value) == null ? void 0 : _h.isDislikedByAuth) || false);
    const localShareLikes = ref(((_i = shareData.value) == null ? void 0 : _i.likes) || 0);
    const localShareDislikes = ref(((_j = shareData.value) == null ? void 0 : _j.dislikes) || 0);
    const localShareComments = ref(((_k = shareData.value) == null ? void 0 : _k.comments) || 0);
    const isShareLiking = ref(false);
    const isShareDisliking = ref(false);
    watch(() => shareData.value, (newData) => {
      if (newData) {
        localShareIsLiked.value = newData.isLikedByAuth || false;
        localShareIsDisliked.value = newData.isDislikedByAuth || false;
        localShareLikes.value = newData.likes || 0;
        localShareDislikes.value = newData.dislikes || 0;
        localShareComments.value = newData.comments || 0;
      }
    }, { immediate: true });
    const showShareComments = ref(true);
    const newShareComment = ref("");
    const isSubmittingShareComment = ref(false);
    ref(false);
    ref([]);
    ref({
      currentPage: 1,
      lastPage: 1,
      perPage: 10,
      total: 0
    });
    const isDeletingShare = ref(false);
    const handlePostUpdated = (updatedPost) => {
      showEditModal.value = false;
      emit("post-updated", updatedPost);
    };
    const handlePollDelete = () => {
      emit("delete-success", props.post.id);
    };
    const handlePollUpdate = (updatedPoll) => {
      if (pollData.value) {
        Object.assign(pollData.value, updatedPoll);
      }
      emit("post-updated", props.post);
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a2, _b2, _c2, _d2, _e2, _f2, _g2, _h2, _i2, _j2;
      const _component_NuxtLink = __nuxt_component_0;
      const _component_FeedPost = resolveComponent("FeedPost", true);
      const _directive_click_outside = resolveDirective("click-outside");
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: [
          __props.isNested ? "border border-gray-200 dark:border-vikinger-dark-50/30 rounded-xl p-4 bg-gray-50 dark:bg-vikinger-dark-200/50" : isPollOnlyActivity.value ? "" : "vikinger-card group hover:shadow-lg transition-shadow duration-300"
        ]
      }, _attrs))} data-v-5cd06945>`);
      if (isPollOnlyActivity.value && !__props.isNested) {
        _push(ssrRenderComponent(PollCard, {
          poll: pollData.value,
          "show-actions": isPollOwner.value,
          "is-nested": false,
          onDelete: handlePollDelete,
          onUpdate: handlePollUpdate
        }, null, _parent));
      } else if (isShareActivity.value && !__props.isNested) {
        _push(`<!--[--><div class="flex items-center gap-3 mb-4" data-v-5cd06945><img${ssrRenderAttr("src", actionByAvatar.value)} class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-cyan/30"${ssrRenderAttr("alt", (_a2 = actionBy.value) == null ? void 0 : _a2.username)} data-v-5cd06945><div class="flex-1" data-v-5cd06945><div class="flex items-center gap-2 flex-wrap" data-v-5cd06945>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/profile/${(_b2 = actionBy.value) == null ? void 0 : _b2.id}`,
          class: "font-bold text-gray-800 dark:text-white hover:text-vikinger-purple transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            var _a3, _b3;
            if (_push2) {
              _push2(`${ssrInterpolate(((_a3 = actionBy.value) == null ? void 0 : _a3.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}`);
            } else {
              return [
                createTextVNode(toDisplayString(((_b3 = actionBy.value) == null ? void 0 : _b3.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49"), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<span class="text-gray-600 dark:text-gray-400" data-v-5cd06945>\u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E02\u0E2D\u0E07</span>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/profile/${(_c2 = postAuthor.value) == null ? void 0 : _c2.id}`,
          class: "font-semibold text-vikinger-cyan hover:underline"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            var _a3, _b3;
            if (_push2) {
              _push2(`${ssrInterpolate(((_a3 = postAuthor.value) == null ? void 0 : _a3.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}`);
            } else {
              return [
                createTextVNode(toDisplayString(((_b3 = postAuthor.value) == null ? void 0 : _b3.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49"), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:share-24-regular",
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(`<span data-v-5cd06945>${ssrInterpolate(props.post.diff_humans_created_at || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</span></div></div><div class="relative" data-v-5cd06945><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors opacity-0 group-hover:opacity-100" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5 text-gray-600 dark:text-gray-300"
        }, null, _parent));
        _push(`</button>`);
        if (showOptionsMenu.value) {
          _push(`<div${ssrRenderAttrs(mergeProps({ class: "absolute right-0 top-full mt-2 w-48 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-50" }, ssrGetDirectiveProps(_ctx, _directive_click_outside, () => showOptionsMenu.value = false)))} data-v-5cd06945>`);
          if (isOwnShare.value) {
            _push(`<button${ssrIncludeBooleanAttr(isDeletingShare.value) ? " disabled" : ""} class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left text-red-500" data-v-5cd06945>`);
            if (!isDeletingShare.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:delete-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-5 h-5 animate-spin"
              }, null, _parent));
            }
            _push(`<span class="text-sm font-medium" data-v-5cd06945>\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E41\u0E0A\u0E23\u0E4C</span></button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:flag-24-regular",
            class: "w-5 h-5 text-gray-500"
          }, null, _parent));
          _push(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-5cd06945>\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19</span></button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
        if (shareComment.value) {
          _push(`<p class="text-gray-700 dark:text-gray-300 mb-4 whitespace-pre-wrap" data-v-5cd06945>${ssrInterpolate(shareComment.value)}</p>`);
        } else {
          _push(`<!---->`);
        }
        if ((_d2 = shareData.value) == null ? void 0 : _d2.shareable) {
          _push(ssrRenderComponent(_component_FeedPost, {
            post: shareData.value.shareable,
            "is-nested": true
          }, null, _parent));
        } else {
          _push(`<div class="p-4 bg-gray-100 dark:bg-vikinger-dark-100 rounded-lg text-gray-500" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:warning-24-regular",
            class: "w-5 h-5 inline-block mr-2"
          }, null, _parent));
          _push(` \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E15\u0E49\u0E19\u0E09\u0E1A\u0E31\u0E1A </div>`);
        }
        _push(`<div class="mt-4 pt-3 border-t border-gray-200 dark:border-vikinger-dark-50/30" data-v-5cd06945><div class="flex items-center gap-4 mb-3 text-sm text-gray-600 dark:text-gray-400" data-v-5cd06945>`);
        if (localShareLikes.value > 0) {
          _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:thumb-like-24-filled",
            class: "w-4 h-4 text-vikinger-purple"
          }, null, _parent));
          _push(` ${ssrInterpolate(localShareLikes.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        if (localShareDislikes.value > 0) {
          _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:thumb-dislike-24-filled",
            class: "w-4 h-4 text-red-500"
          }, null, _parent));
          _push(` ${ssrInterpolate(localShareDislikes.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        if (localShareComments.value > 0) {
          _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:comment-24-filled",
            class: "w-4 h-4 text-vikinger-cyan"
          }, null, _parent));
          _push(` ${ssrInterpolate(localShareComments.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex items-center gap-4" data-v-5cd06945><button${ssrIncludeBooleanAttr(isShareLiking.value) ? " disabled" : ""} class="${ssrRenderClass([
          "flex items-center gap-2 transition-colors",
          localShareIsLiked.value ? "text-vikinger-purple dark:text-vikinger-purple" : "text-gray-500 dark:text-gray-400 hover:text-vikinger-purple"
        ])}" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: localShareIsLiked.value ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="text-sm" data-v-5cd06945>${ssrInterpolate(localShareIsLiked.value ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(isShareDisliking.value) ? " disabled" : ""} class="${ssrRenderClass([
          "flex items-center gap-2 transition-colors",
          localShareIsDisliked.value ? "text-red-500 dark:text-red-500" : "text-gray-500 dark:text-gray-400 hover:text-red-500"
        ])}" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: localShareIsDisliked.value ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="text-sm" data-v-5cd06945>${ssrInterpolate(localShareIsDisliked.value ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button class="flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-vikinger-cyan transition-colors" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:comment-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="text-sm" data-v-5cd06945>\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</span></button></div>`);
        if (showShareComments.value) {
          _push(`<div class="mt-4 space-y-3" data-v-5cd06945><div class="flex gap-2" data-v-5cd06945><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="Your avatar" data-v-5cd06945><div class="flex-1 relative" data-v-5cd06945><textarea placeholder="\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19... (Ctrl+Enter \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E48\u0E07)" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple resize-none" rows="2" data-v-5cd06945>${ssrInterpolate(newShareComment.value)}</textarea><button${ssrIncludeBooleanAttr(!newShareComment.value.trim() || isSubmittingShareComment.value) ? " disabled" : ""} class="absolute bottom-2 right-2 p-1.5 rounded-lg bg-vikinger-purple text-white hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" data-v-5cd06945>`);
          if (!isSubmittingShareComment.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:send-24-filled",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-4 h-4 animate-spin"
            }, null, _parent));
          }
          _push(`</button></div></div>`);
          if (displayedComments.value.length > 0) {
            _push(`<div class="space-y-3" data-v-5cd06945><!--[-->`);
            ssrRenderList(displayedComments.value, (comment) => {
              var _a3, _b3, _c3, _d3, _e3, _f3, _g3, _h3, _i3, _j3, _k2, _l;
              _push(`<div class="flex gap-3 group" data-v-5cd06945><img${ssrRenderAttr("src", getCommentAvatar(comment))} class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover"${ssrRenderAttr("alt", (_a3 = comment.user) == null ? void 0 : _a3.username)} data-v-5cd06945><div class="flex-1" data-v-5cd06945><div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-2xl p-3" data-v-5cd06945><h6 class="font-semibold text-sm text-gray-800 dark:text-white" data-v-5cd06945>${ssrInterpolate(((_b3 = comment.user) == null ? void 0 : _b3.username) || "Unknown")}</h6><p class="text-sm text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-wrap" data-v-5cd06945>${ssrInterpolate(comment.content)}</p></div>`);
              if (comment.likes || comment.dislikes) {
                _push(`<div class="flex items-center gap-3 mt-1 px-2 text-[11px] text-gray-500 dark:text-gray-400" data-v-5cd06945>`);
                if (comment.likes) {
                  _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:thumb-like-16-filled",
                    class: "w-3 h-3 text-vikinger-purple"
                  }, null, _parent));
                  _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(comment.likes)}</span></span>`);
                } else {
                  _push(`<!---->`);
                }
                if (comment.dislikes) {
                  _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:thumb-dislike-16-filled",
                    class: "w-3 h-3 text-red-500"
                  }, null, _parent));
                  _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(comment.dislikes)}</span></span>`);
                } else {
                  _push(`<!---->`);
                }
                _push(`</div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`<div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400 px-2" data-v-5cd06945><span data-v-5cd06945>${ssrInterpolate(comment.diff_humans_created_at || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</span><button${ssrIncludeBooleanAttr(comment.isLiking || ((_c3 = unref(authStore).user) == null ? void 0 : _c3.id) === ((_d3 = comment.user) == null ? void 0 : _d3.id)) ? " disabled" : ""} class="${ssrRenderClass([
                "flex items-center gap-1 font-medium transition-colors",
                comment.is_liked_by_auth ? "text-vikinger-purple" : "hover:text-vikinger-purple",
                ((_e3 = unref(authStore).user) == null ? void 0 : _e3.id) === ((_f3 = comment.user) == null ? void 0 : _f3.id) ? "opacity-50 cursor-not-allowed" : ""
              ])}" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: comment.is_liked_by_auth ? "fluent:thumb-like-20-filled" : "fluent:thumb-like-20-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(`<span data-v-5cd06945>${ssrInterpolate(comment.is_liked_by_auth ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(comment.isDisliking || ((_g3 = unref(authStore).user) == null ? void 0 : _g3.id) === ((_h3 = comment.user) == null ? void 0 : _h3.id)) ? " disabled" : ""} class="${ssrRenderClass([
                "flex items-center gap-1 font-medium transition-colors",
                comment.is_disliked_by_auth ? "text-red-500" : "hover:text-red-500",
                ((_i3 = unref(authStore).user) == null ? void 0 : _i3.id) === ((_j3 = comment.user) == null ? void 0 : _j3.id) ? "opacity-50 cursor-not-allowed" : ""
              ])}" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: comment.is_disliked_by_auth ? "fluent:thumb-dislike-20-filled" : "fluent:thumb-dislike-20-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(`<span data-v-5cd06945>${ssrInterpolate(comment.is_disliked_by_auth ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button>`);
              if (((_k2 = comment.user) == null ? void 0 : _k2.id) === ((_l = unref(authStore).user) == null ? void 0 : _l.id)) {
                _push(`<button class="flex items-center gap-1 hover:text-red-500 font-medium transition-colors" data-v-5cd06945>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:delete-24-regular",
                  class: "w-3.5 h-3.5"
                }, null, _parent));
                _push(`<span data-v-5cd06945>\u0E25\u0E1A</span></button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div></div>`);
            });
            _push(`<!--]--></div>`);
          } else if (displayedComments.value.length === 0) {
            _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400" data-v-5cd06945> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19 \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19! </div>`);
          } else {
            _push(`<!---->`);
          }
          if (hasMoreComments.value && !isLoadingComments.value) {
            _push(`<button class="w-full py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-lg transition-colors flex items-center justify-center gap-2" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-down-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` \u0E14\u0E39\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 (${ssrInterpolate(remainingCommentsCount.value)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </button>`);
          } else {
            _push(`<!---->`);
          }
          if (isLoadingComments.value) {
            _push(`<div class="flex justify-center py-3" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin text-vikinger-purple"
            }, null, _parent));
            _push(`<span class="ml-2 text-sm text-gray-500" data-v-5cd06945>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><!--]-->`);
      } else {
        _push(`<!--[--><div class="flex items-center justify-between mb-4" data-v-5cd06945><div class="flex items-center gap-3" data-v-5cd06945><div class="relative flex-shrink-0" data-v-5cd06945><img${ssrRenderAttr("src", postAuthorAvatar.value)} class="w-12 h-12 aspect-square rounded-full object-cover ring-2 ring-vikinger-purple/30 group-hover:ring-vikinger-purple transition-all duration-300"${ssrRenderAttr("alt", (_e2 = postAuthor.value) == null ? void 0 : _e2.username)} data-v-5cd06945>`);
        if (postTypeBadge.value && !__props.isNested) {
          _push(`<div class="${ssrRenderClass([postTypeBadge.value.color, "absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center shadow-sm"])}" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: postTypeBadge.value.icon,
            class: "w-3 h-3 text-white"
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div data-v-5cd06945><div class="flex items-center gap-2 flex-wrap" data-v-5cd06945>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/profile/${(_f2 = postAuthor.value) == null ? void 0 : _f2.id}`,
          class: "font-bold text-gray-800 dark:text-white hover:text-vikinger-purple cursor-pointer transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            var _a3, _b3;
            if (_push2) {
              _push2(`${ssrInterpolate(((_a3 = postAuthor.value) == null ? void 0 : _a3.username) || "Unknown User")}`);
            } else {
              return [
                createTextVNode(toDisplayString(((_b3 = postAuthor.value) == null ? void 0 : _b3.username) || "Unknown User"), 1)
              ];
            }
          }),
          _: 1
        }, _parent));
        if ((_g2 = postAuthor.value) == null ? void 0 : _g2.verified) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: "w-4 h-4 text-green-500"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (feelingDisplay.value) {
          _push(`<span class="text-gray-600 dark:text-gray-400 text-sm" data-v-5cd06945> \u2014 ${ssrInterpolate(feelingDisplay.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        if (isActivity.value && isSameActor.value && actionTextShort.value) {
          _push(`<!--[--><span class="text-gray-500 dark:text-gray-400" data-v-5cd06945>${ssrInterpolate(actionTextShort.value)}</span>`);
          if (modelTypeText.value) {
            _push(`<span class="text-vikinger-cyan font-medium" data-v-5cd06945>${ssrInterpolate(modelTypeText.value)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        if (contextInfo.value) {
          _push(`<div class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-vikinger-dark-200 text-xs" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: contextInfo.value.icon,
            class: ["w-3.5 h-3.5", contextInfo.value.color]
          }, null, _parent));
          if (contextInfo.value.link) {
            _push(ssrRenderComponent(_component_NuxtLink, {
              to: contextInfo.value.link,
              class: ["hover:underline font-medium transition-colors", contextInfo.value.color]
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`${ssrInterpolate(contextInfo.value.name)}`);
                } else {
                  return [
                    createTextVNode(toDisplayString(contextInfo.value.name), 1)
                  ];
                }
              }),
              _: 1
            }, _parent));
          } else {
            _push(`<span class="text-gray-600 dark:text-gray-300" data-v-5cd06945>${ssrInterpolate(contextInfo.value.name)}</span>`);
          }
          if (contextInfo.value.academy) {
            _push(`<!--[--><span class="text-gray-400" data-v-5cd06945>\u2022</span>`);
            if (contextInfo.value.academyLink) {
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: contextInfo.value.academyLink,
                class: "text-purple-500 hover:underline font-medium transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`${ssrInterpolate(contextInfo.value.academy)}`);
                  } else {
                    return [
                      createTextVNode(toDisplayString(contextInfo.value.academy), 1)
                    ];
                  }
                }),
                _: 1
              }, _parent));
            } else {
              _push(`<span class="text-gray-400" data-v-5cd06945>${ssrInterpolate(contextInfo.value.academy)}</span>`);
            }
            _push(`<!--]-->`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" data-v-5cd06945><span class="flex items-center gap-1" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: privacyIcon.value,
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(` ${ssrInterpolate(createdTime.value)}</span>`);
        if (location.value) {
          _push(`<span class="flex items-center gap-1 text-vikinger-cyan" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:location-24-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(` ${ssrInterpolate(location.value)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
        if (!__props.isNested) {
          _push(`<div class="relative" data-v-5cd06945><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors opacity-0 group-hover:opacity-100" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:more-horizontal-24-regular",
            class: "w-5 h-5 text-gray-600 dark:text-gray-300"
          }, null, _parent));
          _push(`</button>`);
          if (showPostOptionsMenu.value) {
            _push(`<div${ssrRenderAttrs(mergeProps({ class: "absolute right-0 top-full mt-2 w-48 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-50" }, ssrGetDirectiveProps(_ctx, _directive_click_outside, () => showPostOptionsMenu.value = false)))} data-v-5cd06945>`);
            if (hasPoll.value && isPollOwner.value) {
              _push(`<!--[--><button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-blue-600" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:edit-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push(`<span class="text-sm font-medium" data-v-5cd06945>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1E\u0E25</span></button><button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors text-left text-yellow-600" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-circle-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push(`<span class="text-sm font-medium" data-v-5cd06945>\u0E1B\u0E34\u0E14\u0E42\u0E1E\u0E25</span></button><button${ssrIncludeBooleanAttr(isDeletingPost.value) ? " disabled" : ""} class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left text-red-500" data-v-5cd06945>`);
              if (!isDeletingPost.value) {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:delete-24-regular",
                  class: "w-5 h-5"
                }, null, _parent));
              } else {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:spinner-ios-20-regular",
                  class: "w-5 h-5 animate-spin"
                }, null, _parent));
              }
              _push(`<span class="text-sm font-medium" data-v-5cd06945>\u0E25\u0E1A\u0E42\u0E1E\u0E25</span></button><!--]-->`);
            } else {
              _push(`<!--[-->`);
              if (isOwnPost.value) {
                _push(`<button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-blue-600" data-v-5cd06945>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:edit-24-regular",
                  class: "w-5 h-5"
                }, null, _parent));
                _push(`<span class="text-sm font-medium" data-v-5cd06945>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1E\u0E2A\u0E15\u0E4C</span></button>`);
              } else {
                _push(`<!---->`);
              }
              if (isOwnPost.value) {
                _push(`<button${ssrIncludeBooleanAttr(isDeletingPost.value) ? " disabled" : ""} class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left text-red-500" data-v-5cd06945>`);
                if (!isDeletingPost.value) {
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:delete-24-regular",
                    class: "w-5 h-5"
                  }, null, _parent));
                } else {
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:spinner-ios-20-regular",
                    class: "w-5 h-5 animate-spin"
                  }, null, _parent));
                }
                _push(`<span class="text-sm font-medium" data-v-5cd06945>\u0E25\u0E1A\u0E42\u0E1E\u0E2A\u0E15\u0E4C</span></button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`<!--]-->`);
            }
            _push(`<button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:flag-24-regular",
              class: "w-5 h-5 text-gray-500"
            }, null, _parent));
            _push(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-5cd06945>\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19</span></button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if ((((_h2 = contextInfo.value) == null ? void 0 : _h2.amount) || ((_i2 = contextInfo.value) == null ? void 0 : _i2.points)) && !__props.isNested) {
          _push(`<div class="mb-4 p-4 bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-xl" data-v-5cd06945><div class="flex items-center justify-between" data-v-5cd06945><div class="flex items-center gap-3" data-v-5cd06945><div class="w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 flex items-center justify-center" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: contextInfo.value.type === "donate_recipient" ? "fluent:gift-24-filled" : "fluent:heart-24-filled",
            class: "w-6 h-6 text-white"
          }, null, _parent));
          _push(`</div><div data-v-5cd06945><p class="text-sm text-gray-600 dark:text-gray-400" data-v-5cd06945>${ssrInterpolate(contextInfo.value.type === "donate" ? "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E1A\u0E23\u0E34\u0E08\u0E32\u0E04" : "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A")}</p><p class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-purple-500" data-v-5cd06945>${ssrInterpolate(contextInfo.value.points ? `${contextInfo.value.points} \u0E41\u0E15\u0E49\u0E21` : contextInfo.value.amount)}</p></div></div>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: contextInfo.value.icon,
            class: "w-16 h-16 text-pink-200 dark:text-pink-900/50"
          }, null, _parent));
          _push(`</div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="${ssrRenderClass(__props.isNested ? "mb-2" : "mb-4")}" data-v-5cd06945>`);
        if (postData.value.title) {
          _push(`<h4 class="text-lg font-bold mb-2 text-gray-800 dark:text-white" data-v-5cd06945>${ssrInterpolate(postData.value.title)}</h4>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed" data-v-5cd06945>${ssrInterpolate(displayContent.value)}</p>`);
        if (shouldTruncate.value) {
          _push(`<button class="text-vikinger-purple hover:underline text-sm font-medium mt-1" data-v-5cd06945>${ssrInterpolate(isContentExpanded.value ? "\u0E41\u0E2A\u0E14\u0E07\u0E19\u0E49\u0E2D\u0E22\u0E25\u0E07" : "\u0E2D\u0E48\u0E32\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</button>`);
        } else {
          _push(`<!---->`);
        }
        if (hashtags.value.length) {
          _push(`<div class="flex flex-wrap gap-2 mt-3" data-v-5cd06945><!--[-->`);
          ssrRenderList(hashtags.value, (tag) => {
            _push(`<span class="text-sm text-vikinger-purple hover:text-vikinger-cyan hover:underline cursor-pointer transition-colors" data-v-5cd06945> #${ssrInterpolate(tag)}</span>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        if (images.value.length) {
          _push(`<div class="${ssrRenderClass(["mt-4 rounded-xl overflow-hidden", __props.isNested ? "max-h-64" : ""])}" data-v-5cd06945>`);
          if (images.value.length === 1) {
            _push(`<div class="cursor-pointer" data-v-5cd06945><img${ssrRenderAttr("src", images.value[0].url || images.value[0])} class="${ssrRenderClass(["w-full object-cover hover:scale-[1.02] transition-transform duration-300", __props.isNested ? "max-h-64" : "max-h-[500px]"])}" alt="Post image" data-v-5cd06945></div>`);
          } else if (images.value.length === 2) {
            _push(`<div class="grid grid-cols-2 gap-1" data-v-5cd06945><!--[-->`);
            ssrRenderList(images.value, (image, index) => {
              _push(`<img${ssrRenderAttr("src", image.url || image)} class="${ssrRenderClass(["w-full object-cover cursor-pointer hover:opacity-90 transition-opacity", __props.isNested ? "h-32" : "h-64"])}" alt="Post image" data-v-5cd06945>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<div class="grid grid-cols-2 gap-1 relative" data-v-5cd06945><!--[-->`);
            ssrRenderList(images.value.slice(0, 4), (image, index) => {
              _push(`<img${ssrRenderAttr("src", image.url || image)} class="${ssrRenderClass(["w-full object-cover cursor-pointer hover:opacity-90 transition-opacity", __props.isNested ? "h-24" : "h-40", { "brightness-50": index === 3 && images.value.length > 4 }])}" alt="Post image" data-v-5cd06945>`);
            });
            _push(`<!--]-->`);
            if (images.value.length > 4) {
              _push(`<div class="absolute bottom-2 right-2 bg-black/60 text-white px-3 py-1 rounded-full text-sm font-medium" data-v-5cd06945> +${ssrInterpolate(images.value.length - 4)}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (postData.value.media) {
          _push(`<div class="mt-4 rounded-xl overflow-hidden" data-v-5cd06945>`);
          if (postData.value.media.type === "video") {
            _push(`<video controls class="w-full rounded-xl" data-v-5cd06945><source${ssrRenderAttr("src", postData.value.media.url)} data-v-5cd06945></video>`);
          } else if (postData.value.media.type === "audio") {
            _push(`<audio controls class="w-full" data-v-5cd06945><source${ssrRenderAttr("src", postData.value.media.url)} data-v-5cd06945></audio>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (hasPoll.value && pollData.value) {
          _push(`<div class="mt-4" data-v-5cd06945>`);
          _push(ssrRenderComponent(PollCard, {
            poll: pollData.value,
            "show-actions": isPollOwner.value && !__props.isNested,
            "is-nested": __props.isNested,
            onDelete: handlePollDelete,
            onUpdate: handlePollUpdate
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="${ssrRenderClass([
          "flex items-center justify-between border-t border-gray-200 dark:border-vikinger-dark-50/30 text-gray-500 dark:text-gray-400",
          __props.isNested ? "py-2 text-xs border-b-0" : "py-3 text-sm border-b"
        ])}" data-v-5cd06945><div class="flex items-center gap-4" data-v-5cd06945>`);
        if (!__props.isNested) {
          _push(`<span class="flex items-center gap-1.5 hover:text-vikinger-purple cursor-pointer transition-colors" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:eye-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(views.value)}</span></span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<span class="${ssrRenderClass([
          "flex items-center gap-1.5 transition-colors",
          __props.isNested ? "" : "hover:text-vikinger-cyan cursor-pointer"
        ])}" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:comment-24-regular",
          class: __props.isNested ? "w-3.5 h-3.5" : "w-4 h-4"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(localCommentsCount.value)}</span></span><span class="${ssrRenderClass([
          "flex items-center gap-1.5 transition-colors",
          __props.isNested ? "" : "hover:text-vikinger-purple cursor-pointer",
          { "text-vikinger-purple": localIsLiked.value && !__props.isNested }
        ])}" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: localIsLiked.value ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
          class: __props.isNested ? "w-3.5 h-3.5" : "w-4 h-4"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(localLikes.value)}</span></span><span class="${ssrRenderClass([
          "flex items-center gap-1.5 transition-colors",
          __props.isNested ? "" : "hover:text-red-500 cursor-pointer",
          { "text-red-500": localIsDisliked.value && !__props.isNested }
        ])}" data-v-5cd06945>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: localIsDisliked.value ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
          class: __props.isNested ? "w-3.5 h-3.5" : "w-4 h-4"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(localDislikes.value)}</span></span>`);
        if (!__props.isNested) {
          _push(`<span class="flex items-center gap-1.5 hover:text-vikinger-green cursor-pointer transition-colors" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:share-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(localShares.value)}</span></span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (__props.isNested && postData.value.post_url) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: postData.value.post_url,
            class: "flex items-center gap-1.5 text-vikinger-purple hover:text-vikinger-cyan transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<span class="text-xs font-medium" data-v-5cd06945${_scopeId}>\u0E14\u0E39\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E15\u0E49\u0E19\u0E09\u0E1A\u0E31\u0E1A</span>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:arrow-right-24-regular",
                  class: "w-3.5 h-3.5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode("span", { class: "text-xs font-medium" }, "\u0E14\u0E39\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E15\u0E49\u0E19\u0E09\u0E1A\u0E31\u0E1A"),
                  createVNode(unref(Icon), {
                    icon: "fluent:arrow-right-24-regular",
                    class: "w-3.5 h-3.5"
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (!__props.isNested) {
          _push(`<div class="${ssrRenderClass([
            "flex items-center gap-2",
            __props.isNested ? "mt-2" : "mt-4"
          ])}" data-v-5cd06945><button${ssrIncludeBooleanAttr(isLiking.value || isOwnPost.value) ? " disabled" : ""} class="${ssrRenderClass([localIsLiked.value ? "bg-vikinger-purple/10 text-vikinger-purple" : isOwnPost.value ? "opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600" : "hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300", "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"])}" data-v-5cd06945>`);
          if (!isLiking.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: localIsLiked.value ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
              class: "w-5 h-5 transition-transform hover:scale-110"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          }
          _push(`<span class="text-sm font-medium" data-v-5cd06945>${ssrInterpolate(localIsLiked.value ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(isDisliking.value || isOwnPost.value) ? " disabled" : ""} class="${ssrRenderClass([localIsDisliked.value ? "bg-red-500/10 text-red-500" : isOwnPost.value ? "opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600" : "hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300", "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"])}" data-v-5cd06945>`);
          if (!isDisliking.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: localIsDisliked.value ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
              class: "w-5 h-5 transition-transform hover:scale-110"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          }
          _push(`<span class="text-sm font-medium" data-v-5cd06945>${ssrInterpolate(localIsDisliked.value ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 transition-colors group" data-v-5cd06945>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:comment-24-regular",
            class: "w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-cyan transition-colors"
          }, null, _parent));
          _push(`<span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-5cd06945>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</span></button><div class="flex-1 relative" data-v-5cd06945><button${ssrIncludeBooleanAttr(isSharing.value || isOwnPost.value) ? " disabled" : ""} class="${ssrRenderClass([isOwnPost.value ? "opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600" : "hover:bg-gray-100 dark:hover:bg-vikinger-dark-200", "w-full flex items-center justify-center gap-2 py-2.5 rounded-lg transition-colors group"])}" data-v-5cd06945>`);
          if (!isSharing.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:share-24-regular",
              class: "w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-green transition-colors"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          }
          _push(`<span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-5cd06945>\u0E41\u0E0A\u0E23\u0E4C</span></button>`);
          if (showShareMenu.value && !isOwnPost.value) {
            _push(`<div class="absolute bottom-full left-0 right-0 mb-2 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-20" data-v-5cd06945><button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:flash-24-regular",
              class: "w-5 h-5 text-vikinger-green"
            }, null, _parent));
            _push(`<div data-v-5cd06945><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-5cd06945>\u0E41\u0E0A\u0E23\u0E4C\u0E40\u0E25\u0E22</p><p class="text-xs text-gray-500 dark:text-gray-400" data-v-5cd06945>\u0E41\u0E0A\u0E23\u0E4C\u0E17\u0E31\u0E19\u0E17\u0E35 - 36 \u0E41\u0E15\u0E49\u0E21</p></div></button><button class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-24-regular",
              class: "w-5 h-5 text-vikinger-purple"
            }, null, _parent));
            _push(`<div data-v-5cd06945><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-5cd06945>\u0E41\u0E0A\u0E23\u0E4C\u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</p><p class="text-xs text-gray-500 dark:text-gray-400" data-v-5cd06945>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E41\u0E25\u0E30\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32</p></div></button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.post.reactions && !__props.isNested) {
          _push(`<div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-vikinger-dark-50/30" data-v-5cd06945><div class="flex -space-x-1" data-v-5cd06945><!--[-->`);
          ssrRenderList(reactions.slice(0, 3), (reaction) => {
            _push(`<div class="w-7 h-7 rounded-full bg-white dark:bg-vikinger-dark-200 flex items-center justify-center text-sm border-2 border-white dark:border-vikinger-dark-100 shadow-sm" data-v-5cd06945>${ssrInterpolate(reaction.icon)}</div>`);
          });
          _push(`<!--]--></div><span class="text-sm text-gray-500 dark:text-gray-400 font-medium" data-v-5cd06945>${ssrInterpolate(__props.post.reactions.total || "10+")} \u0E04\u0E19\u0E16\u0E39\u0E01\u0E43\u0E08</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (showComments.value && !__props.isNested) {
          _push(`<div class="mt-4 pt-4 border-t border-gray-200 dark:border-vikinger-dark-50/30 space-y-4" data-v-5cd06945><div class="flex gap-3" data-v-5cd06945><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover" alt="You" data-v-5cd06945><div class="flex-1 flex gap-2" data-v-5cd06945><input${ssrRenderAttr("value", newComment.value)} type="text" placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19..." class="flex-1 px-4 py-2.5 rounded-full bg-gray-100 dark:bg-vikinger-dark-200 border-none outline-none text-gray-800 dark:text-white focus:ring-2 focus:ring-vikinger-purple/30 transition-all"${ssrIncludeBooleanAttr(isCommenting.value) ? " disabled" : ""} data-v-5cd06945><button${ssrIncludeBooleanAttr(isCommenting.value || !newComment.value.trim()) ? " disabled" : ""} class="p-2.5 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed" data-v-5cd06945>`);
          if (!isCommenting.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:send-24-filled",
              class: "w-5 h-5"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          }
          _push(`</button></div></div>`);
          if (displayedComments.value.length) {
            _push(`<div class="space-y-3" data-v-5cd06945><!--[-->`);
            ssrRenderList(displayedComments.value, (comment) => {
              var _a3, _b3, _c3, _d3, _e3, _f3, _g3, _h3, _i3, _j3, _k2, _l, _m, _n, _o, _p, _q, _r, _s, _t;
              _push(`<div class="flex gap-3 group" data-v-5cd06945><img${ssrRenderAttr("src", getCommentAvatar(comment))} class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover"${ssrRenderAttr("alt", ((_a3 = comment.user) == null ? void 0 : _a3.username) || ((_b3 = comment.author) == null ? void 0 : _b3.username))} data-v-5cd06945><div class="flex-1" data-v-5cd06945><div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-2xl p-3" data-v-5cd06945><h6 class="font-semibold text-sm text-gray-800 dark:text-white" data-v-5cd06945>${ssrInterpolate(((_c3 = comment.user) == null ? void 0 : _c3.username) || ((_d3 = comment.author) == null ? void 0 : _d3.username))}</h6><p class="text-sm text-gray-700 dark:text-gray-300 mt-1" data-v-5cd06945>${ssrInterpolate(comment.content)}</p></div>`);
              if (comment.likes || comment.dislikes) {
                _push(`<div class="flex items-center gap-3 mt-1 px-2 text-[11px] text-gray-500 dark:text-gray-400" data-v-5cd06945>`);
                if (comment.likes) {
                  _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:thumb-like-16-filled",
                    class: "w-3 h-3 text-vikinger-purple"
                  }, null, _parent));
                  _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(comment.likes)}</span></span>`);
                } else {
                  _push(`<!---->`);
                }
                if (comment.dislikes) {
                  _push(`<span class="flex items-center gap-1" data-v-5cd06945>`);
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:thumb-dislike-16-filled",
                    class: "w-3 h-3 text-red-500"
                  }, null, _parent));
                  _push(`<span class="font-medium" data-v-5cd06945>${ssrInterpolate(comment.dislikes)}</span></span>`);
                } else {
                  _push(`<!---->`);
                }
                _push(`</div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`<div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-xs text-gray-500 dark:text-gray-400 px-2" data-v-5cd06945><span class="text-[11px]" data-v-5cd06945>${ssrInterpolate(comment.create_at || comment.diff_humans_created_at || comment.createdAt || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</span><span class="text-gray-300 dark:text-gray-600" data-v-5cd06945>\u2022</span><button${ssrIncludeBooleanAttr(comment.isLiking || ((_e3 = unref(authStore).user) == null ? void 0 : _e3.id) === (((_f3 = comment.user) == null ? void 0 : _f3.id) || ((_g3 = comment.author) == null ? void 0 : _g3.id))) ? " disabled" : ""} class="${ssrRenderClass([
                "flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300",
                comment.isLikedByAuth ? "text-vikinger-purple bg-vikinger-purple/10" : "hover:text-vikinger-purple",
                ((_h3 = unref(authStore).user) == null ? void 0 : _h3.id) === (((_i3 = comment.user) == null ? void 0 : _i3.id) || ((_j3 = comment.author) == null ? void 0 : _j3.id)) ? "opacity-50 cursor-not-allowed" : ""
              ])}" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: comment.isLikedByAuth ? "fluent:thumb-like-20-filled" : "fluent:thumb-like-20-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(`<span class="hidden sm:inline" data-v-5cd06945>${ssrInterpolate(comment.isLikedByAuth ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(comment.isDisliking || ((_k2 = unref(authStore).user) == null ? void 0 : _k2.id) === (((_l = comment.user) == null ? void 0 : _l.id) || ((_m = comment.author) == null ? void 0 : _m.id))) ? " disabled" : ""} class="${ssrRenderClass([
                "flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300",
                comment.isDislikedByAuth ? "text-red-500 bg-red-500/10" : "hover:text-red-500",
                ((_n = unref(authStore).user) == null ? void 0 : _n.id) === (((_o = comment.user) == null ? void 0 : _o.id) || ((_p = comment.author) == null ? void 0 : _p.id)) ? "opacity-50 cursor-not-allowed" : ""
              ])}" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: comment.isDislikedByAuth ? "fluent:thumb-dislike-20-filled" : "fluent:thumb-dislike-20-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(`<span class="hidden sm:inline" data-v-5cd06945>${ssrInterpolate(comment.isDislikedByAuth ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button class="flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 hover:text-vikinger-purple" data-v-5cd06945>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-reply-20-regular",
                class: "w-3.5 h-3.5"
              }, null, _parent));
              _push(`<span class="hidden sm:inline" data-v-5cd06945>\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A</span></button>`);
              if (comment.replies_count > 0) {
                _push(`<button class="flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-vikinger-cyan/10 text-vikinger-cyan" data-v-5cd06945>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: expandedReplies.value[comment.id] ? "fluent:chevron-up-20-regular" : "fluent:chevron-down-20-regular",
                  class: "w-3.5 h-3.5"
                }, null, _parent));
                _push(`<span class="hidden xs:inline" data-v-5cd06945>${ssrInterpolate(expandedReplies.value[comment.id] ? "\u0E0B\u0E48\u0E2D\u0E19" : "")}</span><span data-v-5cd06945>${ssrInterpolate(comment.replies_count)}</span><span class="hidden sm:inline" data-v-5cd06945>\u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A</span></button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div>`);
              if (((_q = replyingTo.value) == null ? void 0 : _q.id) === comment.id) {
                _push(`<div class="mt-2 ml-2 flex gap-2" data-v-5cd06945><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="You" data-v-5cd06945><div class="flex-1 flex gap-2" data-v-5cd06945><input${ssrRenderAttr("id", `reply-input-${comment.id}`)}${ssrRenderAttr("value", replyContent.value)} type="text"${ssrRenderAttr("placeholder", `\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A ${((_r = comment.user) == null ? void 0 : _r.username) || ((_s = comment.author) == null ? void 0 : _s.username)}...`)} class="flex-1 px-3 py-2 text-sm rounded-full bg-gray-100 dark:bg-vikinger-dark-300 border-none focus:ring-2 focus:ring-vikinger-purple/50 placeholder-gray-400 dark:text-white" data-v-5cd06945><button${ssrIncludeBooleanAttr(isSubmittingReply.value || !replyContent.value.trim()) ? " disabled" : ""} class="p-2 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" data-v-5cd06945>`);
                if (!isSubmittingReply.value) {
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:send-20-filled",
                    class: "w-4 h-4"
                  }, null, _parent));
                } else {
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:spinner-ios-20-regular",
                    class: "w-4 h-4 animate-spin"
                  }, null, _parent));
                }
                _push(`</button><button class="p-2 text-gray-500 hover:text-red-500 transition-colors" data-v-5cd06945>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:dismiss-20-regular",
                  class: "w-4 h-4"
                }, null, _parent));
                _push(`</button></div></div>`);
              } else {
                _push(`<!---->`);
              }
              if (expandedReplies.value[comment.id]) {
                _push(`<div class="mt-3 ml-4 pl-4 border-l-2 border-gray-200 dark:border-vikinger-dark-300 space-y-3" data-v-5cd06945>`);
                if (loadingReplies.value[comment.id]) {
                  _push(`<div class="flex items-center gap-2 py-2" data-v-5cd06945>`);
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:spinner-ios-20-regular",
                    class: "w-4 h-4 animate-spin text-vikinger-purple"
                  }, null, _parent));
                  _push(`<span class="text-xs text-gray-500" data-v-5cd06945>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A...</span></div>`);
                } else {
                  _push(`<!--[--><!--[-->`);
                  ssrRenderList(commentReplies.value[comment.id] || [], (reply) => {
                    var _a4, _b4, _c4, _d4, _e4, _f4;
                    _push(`<div class="flex gap-2" data-v-5cd06945><img${ssrRenderAttr("src", getCommentAvatar(reply))} class="w-8 h-8 flex-shrink-0 rounded-full object-cover"${ssrRenderAttr("alt", (_a4 = reply.user) == null ? void 0 : _a4.username)} data-v-5cd06945><div class="flex-1" data-v-5cd06945><div class="bg-gray-100 dark:bg-vikinger-dark-300 rounded-xl p-2.5" data-v-5cd06945><h6 class="font-semibold text-xs text-gray-800 dark:text-white" data-v-5cd06945>${ssrInterpolate((_b4 = reply.user) == null ? void 0 : _b4.username)}</h6><p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5" data-v-5cd06945>${ssrInterpolate(reply.content)}</p></div>`);
                    if (reply.likes || reply.dislikes) {
                      _push(`<div class="flex items-center gap-2 mt-1 px-2 text-[10px] text-gray-500" data-v-5cd06945>`);
                      if (reply.likes) {
                        _push(`<span class="flex items-center gap-0.5" data-v-5cd06945>`);
                        _push(ssrRenderComponent(unref(Icon), {
                          icon: "fluent:thumb-like-16-filled",
                          class: "w-2.5 h-2.5 text-vikinger-purple"
                        }, null, _parent));
                        _push(` ${ssrInterpolate(reply.likes)}</span>`);
                      } else {
                        _push(`<!---->`);
                      }
                      if (reply.dislikes) {
                        _push(`<span class="flex items-center gap-0.5" data-v-5cd06945>`);
                        _push(ssrRenderComponent(unref(Icon), {
                          icon: "fluent:thumb-dislike-16-filled",
                          class: "w-2.5 h-2.5 text-red-500"
                        }, null, _parent));
                        _push(` ${ssrInterpolate(reply.dislikes)}</span>`);
                      } else {
                        _push(`<!---->`);
                      }
                      _push(`</div>`);
                    } else {
                      _push(`<!---->`);
                    }
                    _push(`<div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-1 text-[10px] text-gray-500 px-2" data-v-5cd06945><span data-v-5cd06945>${ssrInterpolate(reply.create_at || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</span><span class="text-gray-300 dark:text-gray-600" data-v-5cd06945>\u2022</span><button${ssrIncludeBooleanAttr(reply.isLiking || ((_c4 = unref(authStore).user) == null ? void 0 : _c4.id) === ((_d4 = reply.user) == null ? void 0 : _d4.id)) ? " disabled" : ""} class="${ssrRenderClass([
                      "flex items-center gap-0.5 font-medium transition-colors px-1 py-0.5 rounded hover:bg-gray-100 dark:hover:bg-vikinger-dark-400",
                      reply.isLikedByAuth ? "text-vikinger-purple" : "hover:text-vikinger-purple"
                    ])}" data-v-5cd06945>`);
                    _push(ssrRenderComponent(unref(Icon), {
                      icon: reply.isLikedByAuth ? "fluent:thumb-like-16-filled" : "fluent:thumb-like-16-regular",
                      class: "w-3 h-3"
                    }, null, _parent));
                    _push(`<span class="hidden sm:inline" data-v-5cd06945>${ssrInterpolate(reply.isLikedByAuth ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(reply.isDisliking || ((_e4 = unref(authStore).user) == null ? void 0 : _e4.id) === ((_f4 = reply.user) == null ? void 0 : _f4.id)) ? " disabled" : ""} class="${ssrRenderClass([
                      "flex items-center gap-0.5 font-medium transition-colors px-1 py-0.5 rounded hover:bg-gray-100 dark:hover:bg-vikinger-dark-400",
                      reply.isDislikedByAuth ? "text-red-500" : "hover:text-red-500"
                    ])}" data-v-5cd06945>`);
                    _push(ssrRenderComponent(unref(Icon), {
                      icon: reply.isDislikedByAuth ? "fluent:thumb-dislike-16-filled" : "fluent:thumb-dislike-16-regular",
                      class: "w-3 h-3"
                    }, null, _parent));
                    _push(`<span class="hidden sm:inline" data-v-5cd06945>${ssrInterpolate(reply.isDislikedByAuth ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button></div></div></div>`);
                  });
                  _push(`<!--]-->`);
                  if ((_t = repliesPagination.value[comment.id]) == null ? void 0 : _t.hasMore) {
                    _push(`<button class="text-xs text-vikinger-purple hover:text-vikinger-cyan font-medium transition-colors flex items-center gap-1" data-v-5cd06945>`);
                    _push(ssrRenderComponent(unref(Icon), {
                      icon: "fluent:arrow-down-20-regular",
                      class: "w-3 h-3"
                    }, null, _parent));
                    _push(` \u0E42\u0E2B\u0E25\u0E14\u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 </button>`);
                  } else {
                    _push(`<!---->`);
                  }
                  _push(`<!--]-->`);
                }
                _push(`</div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div>`);
            });
            _push(`<!--]--></div>`);
          } else if (displayedComments.value.length === 0) {
            _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400" data-v-5cd06945> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19 \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19! </div>`);
          } else {
            _push(`<!---->`);
          }
          if (hasMoreComments.value && !isLoadingComments.value) {
            _push(`<button class="w-full py-2 text-sm text-vikinger-purple hover:text-vikinger-cyan font-medium transition-colors flex items-center justify-center gap-2" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-down-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` \u0E14\u0E39\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 (${ssrInterpolate(remainingCommentsCount.value)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </button>`);
          } else {
            _push(`<!---->`);
          }
          if (isLoadingComments.value) {
            _push(`<div class="flex justify-center py-3" data-v-5cd06945>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin text-vikinger-purple"
            }, null, _parent));
            _push(`<span class="ml-2 text-sm text-gray-500" data-v-5cd06945>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      }
      if (!__props.isNested) {
        _push(ssrRenderComponent(ShareModal, {
          show: showShareModal.value,
          post: postData.value,
          onClose: ($event) => showShareModal.value = false,
          onShare: handleShareSubmit
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (!__props.isNested && isOwnPost.value) {
        _push(ssrRenderComponent(EditPostModal, {
          show: showEditModal.value,
          post: postData.value,
          onClose: ($event) => showEditModal.value = false,
          onPostUpdated: handlePostUpdated
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (!__props.isNested) {
        _push(ssrRenderComponent(ImageLightbox, {
          show: selectedImageIndex.value !== null,
          images: images.value,
          "initial-index": selectedImageIndex.value || 0,
          "post-id": (_j2 = postData.value) == null ? void 0 : _j2.id,
          onClose: closeImageModal
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/play/feed/FeedPost.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const FeedPost = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-5cd06945"]]);
const _sfc_main = {
  __name: "ProfileCompletionWidget",
  __ssrInlineRender: true,
  props: {
    percentage: {
      type: Number,
      default: 0
    },
    quests: {
      type: Object,
      default: () => ({ completed: 0, total: 30 })
    },
    badges: {
      type: Object,
      default: () => ({ unlocked: 0, total: 46 })
    },
    missingFields: {
      type: Array,
      default: () => []
    },
    showActions: {
      type: Boolean,
      default: true
    }
  },
  emits: ["edit-profile"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const activeTab = ref("status");
    const tabs = ["Status", "Mentions", "Friends", "Groups", "Blog Posts"];
    const circumference = 2 * Math.PI * 58;
    const offset = computed(() => circumference - props.percentage / 100 * circumference);
    const progressColor = computed(() => {
      if (props.percentage >= 80) return { from: "#10B981", to: "#059669" };
      if (props.percentage >= 50) return { from: "#00D9FF", to: "#615DFA" };
      if (props.percentage >= 25) return { from: "#F59E0B", to: "#D97706" };
      return { from: "#EF4444", to: "#DC2626" };
    });
    const getFieldIcon = (field) => {
      const icons = {
        avatar: "fluent:camera-24-regular",
        cover: "fluent:image-24-regular",
        first_name: "fluent:person-24-regular",
        last_name: "fluent:person-24-regular",
        bio: "fluent:text-description-24-regular",
        birthdate: "fluent:calendar-24-regular",
        gender: "fluent:people-24-regular",
        location: "fluent:location-24-regular",
        website: "fluent:globe-24-regular",
        interests: "fluent:heart-24-regular",
        social_media: "fluent:share-24-regular"
      };
      return icons[field] || "fluent:add-circle-24-regular";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0" }, _attrs))}><div class="relative bg-gradient-to-r from-vikinger-purple via-indigo-500 to-vikinger-cyan p-4"><div class="absolute inset-0 bg-[url(&#39;/images/patterns/circuit.svg&#39;)] bg-repeat opacity-10"></div><h3 class="relative text-lg font-black text-white flex items-center gap-2 drop-shadow-lg"><div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:person-circle-24-filled",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</div> Profile Completion </h3></div><div class="p-5 space-y-5"><div class="text-center"><div class="relative inline-flex items-center justify-center"><div class="absolute inset-0 m-4 rounded-full blur-xl opacity-30" style="${ssrRenderStyle({ background: `linear-gradient(135deg, ${progressColor.value.from}, ${progressColor.value.to})` })}"></div><svg class="w-36 h-36 transform -rotate-90 relative"><circle cx="72" cy="72" r="58" stroke="currentColor" stroke-width="10" fill="none" class="text-gray-200 dark:text-gray-700"></circle><circle cx="72" cy="72" r="58"${ssrRenderAttr("stroke", `url(#progressGradient-${__props.percentage})`)} stroke-width="10" fill="none" stroke-linecap="round"${ssrRenderAttr("stroke-dasharray", circumference)}${ssrRenderAttr("stroke-dashoffset", offset.value)} class="transition-all duration-1000 ease-out drop-shadow-lg"></circle><defs><linearGradient${ssrRenderAttr("id", `progressGradient-${__props.percentage}`)} x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%"${ssrRenderAttr("stop-color", progressColor.value.from)}></stop><stop offset="100%"${ssrRenderAttr("stop-color", progressColor.value.to)}></stop></linearGradient></defs></svg><div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-4xl font-black text-gray-800 dark:text-white">${ssrInterpolate(__props.percentage)}%</span><span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Complete</span></div></div></div>`);
      if (__props.missingFields && __props.missingFields.length > 0) {
        _push(`<div class="space-y-2"><p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Complete these to boost:</p><ul class="space-y-2"><!--[-->`);
        ssrRenderList(__props.missingFields.slice(0, 4), (field) => {
          _push(`<li class="flex items-center gap-3 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors cursor-pointer group"><div class="w-8 h-8 rounded-lg bg-vikinger-purple/20 flex items-center justify-center group-hover:bg-vikinger-purple/30 transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getFieldIcon(field.field),
            class: "w-4 h-4 text-vikinger-purple"
          }, null, _parent));
          _push(`</div><span class="text-sm text-gray-700 dark:text-gray-300 flex-1">${ssrInterpolate(field.label)}</span><span class="text-xs font-bold text-vikinger-cyan bg-vikinger-cyan/10 px-2 py-1 rounded-full">+${ssrInterpolate(field.weight)}%</span></li>`);
        });
        _push(`<!--]--></ul>`);
        if (__props.showActions) {
          _push(`<button class="mt-3 w-full py-2.5 px-4 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 shadow-lg hover:shadow-xl hover:scale-[1.02]">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` Complete Profile </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<div class="text-center py-4"><div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-10 h-10 text-green-500"
        }, null, _parent));
        _push(`</div><p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Profile Complete!</p><p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Great job completing your profile</p></div>`);
      }
      _push(`<div class="flex items-center justify-center gap-3 py-3 border-t border-b border-gray-200 dark:border-gray-700">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:feed-24-regular",
        class: "w-5 h-5 text-vikinger-purple"
      }, null, _parent));
      _push(`<span class="text-sm font-semibold text-gray-700 dark:text-gray-300">All Updates</span>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chevron-down-24-regular",
        class: "w-4 h-4 text-gray-500"
      }, null, _parent));
      _push(`</div><div class="flex gap-2 justify-center flex-wrap"><!--[-->`);
      ssrRenderList(tabs, (tab) => {
        _push(`<button class="${ssrRenderClass([
          "px-3 py-1.5 text-xs font-medium rounded-xl transition-all",
          activeTab.value === tab.toLowerCase() ? "bg-vikinger-purple text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-vikinger-purple/20 hover:text-vikinger-purple"
        ])}">${ssrInterpolate(tab)}</button>`);
      });
      _push(`<!--]--></div><div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700"><div class="text-center p-3 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200/50 dark:border-amber-800/30"><div class="text-2xl font-black text-gray-800 dark:text-white">${ssrInterpolate(__props.quests.completed)}/${ssrInterpolate(__props.quests.total)}</div><div class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1 uppercase tracking-wider">Quests</div><div class="text-[10px] text-gray-500 dark:text-gray-500">COMPLETED</div><div class="mt-2 flex justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:trophy-24-filled",
        class: "w-8 h-8 text-amber-500 drop-shadow-lg"
      }, null, _parent));
      _push(`</div></div><div class="text-center p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200/50 dark:border-blue-800/30"><div class="text-2xl font-black text-gray-800 dark:text-white">${ssrInterpolate(__props.badges.unlocked)}/${ssrInterpolate(__props.badges.total)}</div><div class="text-xs font-semibold text-blue-600 dark:text-blue-400 mt-1 uppercase tracking-wider">Badges</div><div class="text-[10px] text-gray-500 dark:text-gray-500">UNLOCKED</div><div class="mt-2 flex justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:shield-checkmark-24-filled",
        class: "w-8 h-8 text-blue-500 drop-shadow-lg"
      }, null, _parent));
      _push(`</div></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/organisms/ProfileCompletionWidget.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { FeedPost as F, _sfc_main as _ };
//# sourceMappingURL=ProfileCompletionWidget-BzF-6HBI.mjs.map
