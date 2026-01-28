import { _ as _sfc_main$5 } from './RichTextEditor-J-3c3zks.mjs';
import { defineComponent, inject, watch, computed, ref, unref, mergeProps, isRef, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderAttr, ssrRenderList, ssrRenderClass, ssrRenderStyle, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { s as useCourseStore, i as useApi, b as useRuntimeConfig } from './server.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import '@tiptap/vue-3';
import '@tiptap/starter-kit';
import '@tiptap/extension-link';
import '@tiptap/extension-text-align';
import '@tiptap/extension-underline';
import '@tiptap/extension-placeholder';
import '@tiptap/extension-image';
import '@tiptap/extension-youtube';
import '@tiptap/extension-text-style';
import '@tiptap/extension-color';
import '@tiptap/extension-highlight';
import '@tiptap/extension-subscript';
import '@tiptap/extension-superscript';
import '@tiptap/extension-task-list';
import '@tiptap/extension-task-item';
import '@tiptap/extension-table';
import '@tiptap/extension-table-row';
import '@tiptap/extension-table-cell';
import '@tiptap/extension-table-header';
import '@tiptap/extension-code-block-lowlight';
import 'lowlight';
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

const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "CourseRatingStars",
  __ssrInlineRender: true,
  props: {
    rating: {},
    size: { default: "md" },
    interactive: { type: Boolean, default: false },
    showValue: { type: Boolean, default: false },
    reviewsCount: { default: 0 }
  },
  emits: ["rate"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const hoverRating = ref(0);
    const sizeClasses = computed(() => {
      switch (props.size) {
        case "sm":
          return "w-4 h-4";
        case "lg":
          return "w-7 h-7";
        default:
          return "w-5 h-5";
      }
    });
    const textSizeClasses = computed(() => {
      switch (props.size) {
        case "sm":
          return "text-xs";
        case "lg":
          return "text-lg";
        default:
          return "text-sm";
      }
    });
    const displayRating = computed(() => {
      if (props.interactive && hoverRating.value > 0) {
        return hoverRating.value;
      }
      return props.rating;
    });
    const getStarType = (index) => {
      const currentRating = displayRating.value;
      if (index <= Math.floor(currentRating)) {
        return "full";
      }
      if (index === Math.ceil(currentRating) && currentRating % 1 >= 0.5) {
        return "half";
      }
      return "empty";
    };
    const getStarIcon = (type) => {
      switch (type) {
        case "full":
          return "fluent:star-24-filled";
        case "half":
          return "fluent:star-half-24-regular";
        default:
          return "fluent:star-24-regular";
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex items-center gap-1" }, _attrs))}><div class="${ssrRenderClass([{ "cursor-pointer": __props.interactive }, "flex items-center"])}"><!--[-->`);
      ssrRenderList(5, (index) => {
        _push(`<button type="button"${ssrIncludeBooleanAttr(!__props.interactive) ? " disabled" : ""} class="${ssrRenderClass([{
          "hover:scale-110": __props.interactive,
          "cursor-default": !__props.interactive
        }, "p-0.5 transition-transform"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: getStarIcon(getStarType(index)),
          class: [
            unref(sizeClasses),
            getStarType(index) === "empty" ? "text-gray-300 dark:text-gray-600" : "text-yellow-400"
          ]
        }, null, _parent));
        _push(`</button>`);
      });
      _push(`<!--]--></div>`);
      if (__props.showValue) {
        _push(`<span class="${ssrRenderClass([unref(textSizeClasses), "font-semibold text-gray-700 dark:text-gray-300 ml-1"])}">${ssrInterpolate(__props.rating > 0 ? __props.rating.toFixed(1) : "-")}</span>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.reviewsCount > 0) {
        _push(`<span class="${ssrRenderClass([unref(textSizeClasses), "text-gray-500 dark:text-gray-400"])}"> (${ssrInterpolate(__props.reviewsCount)}) </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/rating/CourseRatingStars.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "CourseReviewForm",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    existingReview: { default: null }
  },
  emits: ["submitted", "cancel"],
  setup(__props, { emit: __emit }) {
    var _a, _b, _c;
    const props = __props;
    useApi();
    const rating = ref(((_a = props.existingReview) == null ? void 0 : _a.rating) || 0);
    const title = ref(((_b = props.existingReview) == null ? void 0 : _b.title) || "");
    const content = ref(((_c = props.existingReview) == null ? void 0 : _c.content) || "");
    const isSubmitting = ref(false);
    const errorMessage = ref("");
    const isEditing = computed(() => !!props.existingReview);
    const canSubmit = computed(() => rating.value >= 1 && rating.value <= 5);
    const handleRating = (value) => {
      rating.value = value;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6" }, _attrs))}><h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">${ssrInterpolate(unref(isEditing) ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E23\u0E35\u0E27\u0E34\u0E27" : "\u0E40\u0E02\u0E35\u0E22\u0E19\u0E23\u0E35\u0E27\u0E34\u0E27")}</h4><div class="mb-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E43\u0E2B\u0E49\u0E04\u0E30\u0E41\u0E19\u0E19 <span class="text-red-500">*</span></label><div class="flex items-center gap-2">`);
      _push(ssrRenderComponent(_sfc_main$4, {
        rating: unref(rating),
        size: "lg",
        interactive: true,
        onRate: handleRating
      }, null, _parent));
      if (unref(rating) > 0) {
        _push(`<span class="text-lg font-semibold text-gray-700 dark:text-gray-300 ml-2">${ssrInterpolate(unref(rating))}/5 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="mb-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E23\u0E35\u0E27\u0E34\u0E27 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><input${ssrRenderAttr("value", unref(title))} type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="\u0E2A\u0E23\u0E38\u0E1B\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13..." maxlength="255"></div><div class="mb-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="4" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" placeholder="\u0E41\u0E0A\u0E23\u0E4C\u0E1B\u0E23\u0E30\u0E2A\u0E1A\u0E01\u0E32\u0E23\u0E13\u0E4C\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13..." maxlength="2000">${ssrInterpolate(unref(content))}</textarea><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(unref(content).length)}/2000 </p></div>`);
      if (unref(errorMessage)) {
        _push(`<div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-sm text-red-600 dark:text-red-400">${ssrInterpolate(unref(errorMessage))}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex items-center justify-end gap-3"><button type="button"${ssrIncludeBooleanAttr(unref(isSubmitting)) ? " disabled" : ""} class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="button"${ssrIncludeBooleanAttr(!unref(canSubmit) || unref(isSubmitting)) ? " disabled" : ""} class="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">`);
      if (unref(isSubmitting)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-4 h-4"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:send-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
      }
      _push(` ${ssrInterpolate(unref(isEditing) ? "\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15\u0E23\u0E35\u0E27\u0E34\u0E27" : "\u0E2A\u0E48\u0E07\u0E23\u0E35\u0E27\u0E34\u0E27")}</button></div></div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/rating/CourseReviewForm.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "CourseReviewCard",
  __ssrInlineRender: true,
  props: {
    review: {}
  },
  emits: ["edit", "delete"],
  setup(__props, { emit: __emit }) {
    const isDeleting = ref(false);
    const showDeleteConfirm = ref(false);
    const getAvatarUrl = (avatar) => {
      if (!avatar) return "/images/default-avatar.png";
      if (avatar.startsWith("http")) return avatar;
      return `${useRuntimeConfig().public.apiBase}/storage/${avatar}`;
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5" }, _attrs))}><div class="flex items-start justify-between mb-3"><div class="flex items-center gap-3"><img${ssrRenderAttr("src", getAvatarUrl((_a = __props.review.user) == null ? void 0 : _a.avatar))}${ssrRenderAttr("alt", (_b = __props.review.user) == null ? void 0 : _b.name)} class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"><div><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_c = __props.review.user) == null ? void 0 : _c.name) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19")}</p><p class="text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(__props.review.created_at_formatted)}</p></div></div>`);
      if (__props.review.is_own) {
        _push(`<div class="flex items-center gap-1"><button class="p-2 text-gray-500 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="\u0E41\u0E01\u0E49\u0E44\u0E02">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:edit-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="\u0E25\u0E1A">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:delete-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="mb-3">`);
      _push(ssrRenderComponent(_sfc_main$4, {
        rating: __props.review.rating,
        size: "sm"
      }, null, _parent));
      _push(`</div>`);
      if (__props.review.title) {
        _push(`<h4 class="font-semibold text-gray-900 dark:text-white mb-2">${ssrInterpolate(__props.review.title)}</h4>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.review.content) {
        _push(`<p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">${ssrInterpolate(__props.review.content)}</p>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(showDeleteConfirm)) {
          _push2(`<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"><div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full shadow-xl"><div class="text-center mb-4">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:warning-24-regular",
            class: "w-12 h-12 text-yellow-500 mx-auto mb-3"
          }, null, _parent));
          _push2(`<h3 class="text-lg font-semibold text-gray-900 dark:text-white"> \u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E23\u0E35\u0E27\u0E34\u0E27? </h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-2"> \u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E19\u0E35\u0E49\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E44\u0E14\u0E49 </p></div><div class="flex gap-3"><button${ssrIncludeBooleanAttr(unref(isDeleting)) ? " disabled" : ""} class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(unref(isDeleting)) ? " disabled" : ""} class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50 flex items-center justify-center gap-2">`);
          if (unref(isDeleting)) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(` \u0E25\u0E1A\u0E23\u0E35\u0E27\u0E34\u0E27 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/rating/CourseReviewCard.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CourseReviewsSection",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    isMember: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const api = useApi();
    const reviews = ref([]);
    const summary = ref({
      average_rating: 0,
      total_reviews: 0,
      distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
    });
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0
    });
    const myReview = ref(null);
    const canReview = ref(false);
    const isLoading = ref(true);
    const isLoadingMore = ref(false);
    const showReviewForm = ref(false);
    const editingReview = ref(null);
    const fetchReviews = async (page = 1, append = false) => {
      if (page === 1) {
        isLoading.value = true;
      } else {
        isLoadingMore.value = true;
      }
      try {
        const response = await api.get(`/api/courses/${props.courseId}/reviews`, {
          params: { page, per_page: 10 }
        });
        if (response.success) {
          if (append) {
            reviews.value = [...reviews.value, ...response.reviews || []];
          } else {
            reviews.value = response.reviews || [];
          }
          summary.value = response.summary || summary.value;
          pagination.value = response.pagination || pagination.value;
        }
      } catch (err) {
        console.error("Failed to fetch reviews:", err);
      } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
      }
    };
    const fetchMyReview = async () => {
      if (!props.isMember) return;
      try {
        const response = await api.get(`/api/courses/${props.courseId}/reviews/my-review`);
        if (response.success) {
          myReview.value = response.review || null;
          canReview.value = response.can_review || false;
        }
      } catch (err) {
        console.error("Failed to fetch my review:", err);
      }
    };
    const deleteReview = async (reviewId) => {
      var _a;
      try {
        const response = await api.delete(`/api/courses/${props.courseId}/reviews/${reviewId}`);
        if (response.success) {
          reviews.value = reviews.value.filter((r) => r.id !== reviewId);
          if (((_a = myReview.value) == null ? void 0 : _a.id) === reviewId) {
            myReview.value = null;
          }
          fetchReviews(1);
        }
      } catch (err) {
        console.error("Failed to delete review:", err);
      }
    };
    const handleReviewSubmitted = (review) => {
      myReview.value = review;
      showReviewForm.value = false;
      editingReview.value = null;
      fetchReviews(1);
    };
    const handleEditReview = (review) => {
      editingReview.value = review;
      showReviewForm.value = true;
    };
    const handleCancelForm = () => {
      showReviewForm.value = false;
      editingReview.value = null;
    };
    const getDistributionPercent = (rating) => {
      if (summary.value.total_reviews === 0) return 0;
      return (summary.value.distribution[rating] || 0) / summary.value.total_reviews * 100;
    };
    watch(() => props.courseId, () => {
      fetchReviews();
      fetchMyReview();
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_LearnCourseRatingCourseReviewForm = _sfc_main$3;
      const _component_LearnCourseRatingCourseReviewCard = _sfc_main$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6" }, _attrs))}><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:star-24-regular",
        class: "w-5 h-5 text-yellow-500"
      }, null, _parent));
      _push(` \u0E23\u0E35\u0E27\u0E34\u0E27\u0E41\u0E25\u0E30\u0E04\u0E30\u0E41\u0E19\u0E19 </h3>`);
      if (unref(isLoading)) {
        _push(`<div class="flex items-center justify-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-8 h-8 text-blue-500"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!--[--><div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6"><div class="text-center md:text-left"><div class="flex items-center justify-center md:justify-start gap-3 mb-2"><span class="text-5xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(summary).average_rating > 0 ? unref(summary).average_rating.toFixed(1) : "-")}</span><div>`);
        _push(ssrRenderComponent(_sfc_main$4, {
          rating: unref(summary).average_rating,
          size: "md"
        }, null, _parent));
        _push(`<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${ssrInterpolate(unref(summary).total_reviews)} \u0E23\u0E35\u0E27\u0E34\u0E27 </p></div></div></div><div class="space-y-2"><!--[-->`);
        ssrRenderList([5, 4, 3, 2, 1], (rating) => {
          _push(`<div class="flex items-center gap-2"><span class="text-sm text-gray-600 dark:text-gray-400 w-3">${ssrInterpolate(rating)}</span>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:star-24-filled",
            class: "w-4 h-4 text-yellow-400"
          }, null, _parent));
          _push(`<div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"><div class="h-full bg-yellow-400 rounded-full transition-all duration-300" style="${ssrRenderStyle({ width: `${getDistributionPercent(rating)}%` })}"></div></div><span class="text-xs text-gray-500 dark:text-gray-400 w-8 text-right">${ssrInterpolate(unref(summary).distribution[rating] || 0)}</span></div>`);
        });
        _push(`<!--]--></div></div>`);
        if (__props.isMember && unref(canReview) && !unref(myReview)) {
          _push(`<div class="mb-6">`);
          if (!unref(showReviewForm)) {
            _push(`<button class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 transition-colors flex items-center justify-center gap-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(` \u0E40\u0E02\u0E35\u0E22\u0E19\u0E23\u0E35\u0E27\u0E34\u0E27 </button>`);
          } else {
            _push(ssrRenderComponent(_component_LearnCourseRatingCourseReviewForm, {
              "course-id": __props.courseId,
              "existing-review": unref(editingReview),
              onSubmitted: handleReviewSubmitted,
              onCancel: handleCancelForm
            }, null, _parent));
          }
          _push(`</div>`);
        } else if (__props.isMember && unref(myReview) && unref(showReviewForm)) {
          _push(`<div class="mb-6">`);
          _push(ssrRenderComponent(_component_LearnCourseRatingCourseReviewForm, {
            "course-id": __props.courseId,
            "existing-review": { id: unref(myReview).id, rating: unref(myReview).rating, title: unref(myReview).title, content: unref(myReview).content },
            onSubmitted: handleReviewSubmitted,
            onCancel: handleCancelForm
          }, null, _parent));
          _push(`</div>`);
        } else if (!__props.isMember) {
          _push(`<div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center"><p class="text-sm text-gray-600 dark:text-gray-400">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:info-24-regular",
            class: "w-4 h-4 inline mr-1"
          }, null, _parent));
          _push(` \u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E02\u0E35\u0E22\u0E19\u0E23\u0E35\u0E27\u0E34\u0E27 </p></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(reviews).length > 0) {
          _push(`<div class="space-y-4"><!--[-->`);
          ssrRenderList(unref(reviews), (review) => {
            _push(ssrRenderComponent(_component_LearnCourseRatingCourseReviewCard, {
              key: review.id,
              review,
              onEdit: handleEditReview,
              onDelete: deleteReview
            }, null, _parent));
          });
          _push(`<!--]-->`);
          if (unref(pagination).current_page < unref(pagination).last_page) {
            _push(`<div class="text-center pt-4"><button${ssrIncludeBooleanAttr(unref(isLoadingMore)) ? " disabled" : ""} class="px-6 py-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors flex items-center gap-2 mx-auto">`);
            if (unref(isLoadingMore)) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-4 h-4"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`<span>\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</span></button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<div class="text-center py-8">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chat-empty-24-regular",
            class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-3"
          }, null, _parent));
          _push(`<p class="text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E35\u0E27\u0E34\u0E27</p>`);
          if (__props.isMember && unref(canReview)) {
            _push(`<p class="text-sm text-gray-400 dark:text-gray-500 mt-1"> \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E23\u0E35\u0E27\u0E34\u0E27\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49! </p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        }
        _push(`<!--]-->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/rating/CourseReviewsSection.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  props: {
    course: {},
    academy: {},
    isCourseAdmin: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const injectedCourse = inject("course");
    const injectedAcademy = inject("academy");
    const injectedIsCourseAdmin = inject("isCourseAdmin");
    inject("refreshCourse");
    const courseStore = useCourseStore();
    watch([() => props.course, injectedCourse], ([propsCourse, injected]) => {
      const courseData = propsCourse || injected;
      if (courseData) {
        courseStore.setCourse(courseData);
      }
    }, { immediate: true });
    watch([() => props.academy, injectedAcademy], ([propsAcademy, injected]) => {
      const academyData = propsAcademy || injected;
      if (academyData) {
        courseStore.setAcademy(academyData);
      }
    }, { immediate: true });
    watch([() => props.isCourseAdmin, injectedIsCourseAdmin], ([propsAdmin, injected]) => {
      const isAdmin = propsAdmin || injected || false;
      courseStore.setIsCourseAdmin(isAdmin);
    }, { immediate: true });
    const course = computed(() => props.course || (injectedCourse == null ? void 0 : injectedCourse.value) || courseStore.currentCourse);
    computed(() => props.academy || (injectedAcademy == null ? void 0 : injectedAcademy.value) || courseStore.academy);
    const isCourseAdmin = computed(() => props.isCourseAdmin || (injectedIsCourseAdmin == null ? void 0 : injectedIsCourseAdmin.value) || courseStore.isCourseAdmin);
    useApi();
    const isEnrolling = ref(false);
    const isWishlisted = ref(false);
    const isTogglingFavorite = ref(false);
    const expandedSections = ref([0]);
    const isEditingDescription = ref(false);
    const descriptionContent = ref("");
    const isSavingDescription = ref(false);
    watch(() => {
      var _a;
      return (_a = course.value) == null ? void 0 : _a.description;
    }, (newVal) => {
      if (newVal && !isEditingDescription.value) {
        descriptionContent.value = newVal;
      }
    }, { immediate: true });
    const curriculum = computed(() => {
      var _a, _b;
      if (!((_b = (_a = course.value) == null ? void 0 : _a.lessons) == null ? void 0 : _b.length)) {
        return [];
      }
      return course.value.lessons.map((lesson, index) => {
        var _a2;
        return {
          id: lesson.id,
          title: `${index + 1}. ${lesson.name}`,
          videos: lesson.topics_count || 0,
          items: ((_a2 = lesson.topics) == null ? void 0 : _a2.map((topic) => ({
            id: topic.id,
            title: topic.name,
            duration: topic.duration || "15min",
            type: topic.is_preview ? "video" : "locked"
          }))) || []
        };
      });
    });
    watch(() => {
      var _a;
      return (_a = course.value) == null ? void 0 : _a.is_favorited;
    }, (newVal) => {
      if (newVal !== void 0) {
        isWishlisted.value = newVal;
      }
    }, { immediate: true });
    const getCoverUrl = (coverPath) => {
      if (!coverPath) return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
      if (coverPath.startsWith("http")) return coverPath;
      return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${coverPath}`;
    };
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric"
      });
    };
    const formatPrice = (price) => {
      return new Intl.NumberFormat("th-TH", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(price || 0);
    };
    const pendingInvitation = computed(() => {
      var _a;
      return (_a = course.value) == null ? void 0 : _a.pending_invitation;
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e;
      const _component_CommonRichTextEditor = _sfc_main$5;
      const _component_LearnCourseRatingCourseReviewsSection = _sfc_main$1;
      const _component_NuxtLink = __nuxt_component_0;
      if (unref(course)) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="grid grid-cols-1 lg:grid-cols-3 gap-6"><div class="lg:col-span-2 space-y-6">`);
        if (unref(pendingInvitation)) {
          _push(`<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 relative overflow-hidden"><div class="absolute top-0 right-0 p-4 opacity-10">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:mail-read-24-filled",
            class: "w-24 h-24 text-blue-600"
          }, null, _parent));
          _push(`</div><div class="relative z-10"><div class="flex items-center gap-3 mb-2"><div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-full">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-key-20-filled",
            class: "w-6 h-6 text-blue-600 dark:text-blue-300"
          }, null, _parent));
          _push(`</div><div><h3 class="font-bold text-lg text-gray-900 dark:text-white">\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D\u0E40\u0E1B\u0E47\u0E19\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h3><p class="text-blue-700 dark:text-blue-300"> \u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D\u0E43\u0E2B\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E1B\u0E47\u0E19 <span class="font-semibold underline">${ssrInterpolate(unref(pendingInvitation).role === 4 ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A (Admin)" : "\u0E1C\u0E39\u0E49\u0E0A\u0E48\u0E27\u0E22\u0E2A\u0E2D\u0E19 (TA)")}</span></p></div></div><p class="text-gray-600 dark:text-gray-400 mt-2 mb-4 max-w-xl"> \u0E1C\u0E39\u0E49\u0E40\u0E0A\u0E34\u0E0D: ${ssrInterpolate(unref(pendingInvitation).inviter_id)} (\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E42\u0E14\u0E22\u0E23\u0E30\u0E1A\u0E1A) <br> \u0E40\u0E21\u0E37\u0E48\u0E2D\u0E04\u0E38\u0E13\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A \u0E04\u0E38\u0E13\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C\u0E43\u0E19\u0E01\u0E32\u0E23\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49\u0E15\u0E32\u0E21\u0E1A\u0E17\u0E1A\u0E32\u0E17\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22\u0E17\u0E31\u0E19\u0E17\u0E35 </p><div class="flex gap-3"><button class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5"> \u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D </button><button class="px-6 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"> \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"><div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:text-description-24-regular",
          class: "w-5 h-5 text-blue-500"
        }, null, _parent));
        _push(` \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 </h3>`);
        if (unref(isCourseAdmin) && !unref(isEditingDescription)) {
          _push(`<button class="flex items-center gap-1 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (!unref(isEditingDescription)) {
          _push(`<div class="text-gray-600 dark:text-gray-400 leading-relaxed prose dark:prose-invert max-w-none whitespace-pre-wrap">`);
          if (unref(course).description) {
            _push(`<div>${(_a = unref(course).description) != null ? _a : ""}</div>`);
          } else {
            _push(`<p class="text-gray-400 italic">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14</p>`);
          }
          _push(`</div>`);
        } else {
          _push(`<div class="space-y-4">`);
          _push(ssrRenderComponent(_component_CommonRichTextEditor, {
            modelValue: unref(descriptionContent),
            "onUpdate:modelValue": ($event) => isRef(descriptionContent) ? descriptionContent.value = $event : null,
            placeholder: "\u0E40\u0E02\u0E35\u0E22\u0E19\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48...",
            "min-height": "250px"
          }, null, _parent));
          _push(`<div class="flex items-center justify-end gap-3"><button${ssrIncludeBooleanAttr(unref(isSavingDescription)) ? " disabled" : ""} class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(unref(isSavingDescription)) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50">`);
          if (unref(isSavingDescription)) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:save-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div>`);
        }
        _push(`</div><div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-24-regular",
          class: "w-5 h-5 text-blue-500"
        }, null, _parent));
        _push(` \u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19 </h3><div class="flex items-center gap-4"><img${ssrRenderAttr("src", ((_b = unref(course).user) == null ? void 0 : _b.avatar) || "/images/default-avatar.png")}${ssrRenderAttr("alt", (_c = unref(course).user) == null ? void 0 : _c.name)} class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"><div class="flex-1"><p class="font-semibold text-gray-900 dark:text-white text-lg">${ssrInterpolate(((_d = unref(course).user) == null ? void 0 : _d.name) || "\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19")}</p><p class="text-gray-500 dark:text-gray-400 text-sm">${ssrInterpolate(((_e = unref(course).user) == null ? void 0 : _e.bio) || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25")}</p></div><button class="px-4 py-2 border border-blue-500 text-blue-500 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-add-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div></div>`);
        if (unref(curriculum).length > 0) {
          _push(`<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:book-24-regular",
            class: "w-5 h-5 text-blue-500"
          }, null, _parent));
          _push(` \u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><div class="space-y-2"><!--[-->`);
          ssrRenderList(unref(curriculum), (section, index) => {
            _push(`<div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"><button class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><span class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(section.title)}</span><div class="flex items-center gap-3 text-gray-500 text-sm"><span>${ssrInterpolate(section.videos)} \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D</span>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: unref(expandedSections).includes(index) ? "fluent:chevron-up-24-regular" : "fluent:chevron-down-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</div></button>`);
            if (unref(expandedSections).includes(index) && section.items.length > 0) {
              _push(`<div class="divide-y divide-gray-200 dark:divide-gray-600"><!--[-->`);
              ssrRenderList(section.items, (item) => {
                _push(`<div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><div class="flex items-center gap-3">`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: item.type === "locked" ? "fluent:lock-closed-24-regular" : "fluent:play-circle-24-regular",
                  class: [
                    "w-5 h-5",
                    item.type === "locked" ? "text-gray-400" : "text-blue-500"
                  ]
                }, null, _parent));
                _push(`<span class="text-gray-700 dark:text-gray-300 text-sm">${ssrInterpolate(item.title)}</span></div><span class="text-gray-500 text-sm">${ssrInterpolate(item.duration)}</span></div>`);
              });
              _push(`<!--]--></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(course)) {
          _push(ssrRenderComponent(_component_LearnCourseRatingCourseReviewsSection, {
            "course-id": unref(course).id,
            "is-member": unref(course).isMember
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="space-y-6"><div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 sticky top-24"><div class="relative aspect-video bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden mb-4"><img${ssrRenderAttr("src", getCoverUrl(unref(course).cover))}${ssrRenderAttr("alt", unref(course).name)} class="w-full h-full object-cover"><div class="absolute inset-0 flex items-center justify-center bg-black/30"><button class="w-14 h-14 bg-blue-500 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors shadow-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:play-24-filled",
          class: "w-7 h-7 text-white ml-1"
        }, null, _parent));
        _push(`</button></div></div>`);
        if (unref(course).price) {
          _push(`<div class="text-center mb-4"><span class="text-3xl font-bold text-gray-900 dark:text-white"> \u0E3F${ssrInterpolate(formatPrice(unref(course).price))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="space-y-3 mb-6">`);
        if (!unref(course).isMember) {
          _push(`<button${ssrIncludeBooleanAttr(unref(isEnrolling)) ? " disabled" : ""} class="w-full py-3 bg-green-500 text-white rounded-lg font-bold hover:bg-green-600 transition-colors flex items-center justify-center gap-2 disabled:opacity-50">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:add-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(isEnrolling) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E21\u0E31\u0E04\u0E23..." : "\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19")}</button>`);
        } else {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/courses/${unref(course).id}/lessons`,
            class: "w-full py-3 bg-green-500 text-white rounded-lg font-bold hover:bg-green-600 transition-colors flex items-center justify-center gap-2"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:play-24-filled",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(` \u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 `);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:play-24-filled",
                    class: "w-5 h-5"
                  }),
                  createTextVNode(" \u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 ")
                ];
              }
            }),
            _: 1
          }, _parent));
        }
        _push(`<button${ssrIncludeBooleanAttr(unref(isTogglingFavorite)) ? " disabled" : ""} class="${ssrRenderClass([
          "w-full py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors disabled:opacity-50",
          unref(isWishlisted) ? "bg-red-500 text-white" : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
        ])}">`);
        if (unref(isTogglingFavorite)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-5 h-5"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: unref(isWishlisted) ? "fluent:heart-24-filled" : "fluent:heart-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(` ${ssrInterpolate(unref(isWishlisted) ? "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E43\u0E19\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E42\u0E1B\u0E23\u0E14\u0E41\u0E25\u0E49\u0E27" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E43\u0E19\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E42\u0E1B\u0E23\u0E14")}</button></div><div class="space-y-3 text-sm"><div class="flex items-center justify-between text-gray-600 dark:text-gray-400"><div class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19</span></div><span class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(unref(course).enrolled_students || 0)} \u0E04\u0E19</span></div><div class="flex items-center justify-between text-gray-600 dark:text-gray-400"><div class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:book-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</span></div><span class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(unref(course).lessons_count || unref(curriculum).length)} \u0E1A\u0E17</span></div>`);
        if (unref(course).duration) {
          _push(`<div class="flex items-center justify-between text-gray-600 dark:text-gray-400"><div class="flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span>\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32</span></div><span class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(unref(course).duration)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex items-center justify-between text-gray-600 dark:text-gray-400"><div class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19</span></div><span class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(formatDate(unref(course).start_date))}</span></div><div class="flex items-center justify-between text-gray-600 dark:text-gray-400"><div class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-checkmark-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</span></div><span class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(formatDate(unref(course).end_date))}</span></div></div><div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"><h4 class="font-medium text-gray-900 dark:text-white mb-3">\u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A</h4><ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400"><li class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-5 h-5 text-green-500"
        }, null, _parent));
        _push(`<span>\u0E40\u0E02\u0E49\u0E32\u0E16\u0E36\u0E07\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E44\u0E14\u0E49\u0E15\u0E25\u0E2D\u0E14\u0E0A\u0E35\u0E1E</span></li><li class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-5 h-5 text-green-500"
        }, null, _parent));
        _push(`<span>\u0E43\u0E1A\u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28\u0E19\u0E35\u0E22\u0E1A\u0E31\u0E15\u0E23</span></li><li class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-5 h-5 text-green-500"
        }, null, _parent));
        _push(`<span>\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A\u0E17\u0E38\u0E01\u0E2D\u0E38\u0E1B\u0E01\u0E23\u0E13\u0E4C</span></li></ul></div></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-BimSm09e.mjs.map
