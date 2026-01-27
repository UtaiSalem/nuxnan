import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, unref, withCtx, createVNode, createTextVNode, computed, watch, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrIncludeBooleanAttr, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$2 } from './AdvertiseItemCard-CBvettSq.mjs';
import Swal from 'sweetalert2';
import { f as useHead, d as useAuthStore, b as useRuntimeConfig, _ as _export_sfc } from './server.mjs';
import { s as setInterval } from './interval-BEVgA3pa.mjs';
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

const radius = 60;
const _sfc_main$1 = {
  __name: "AdViewerModal",
  __ssrInlineRender: true,
  props: {
    isOpen: Boolean,
    advert: Object
  },
  emits: ["close", "completed"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const config = useRuntimeConfig();
    config.public.apiBase;
    useAuthStore();
    const timeLeft = ref(0);
    const timer = ref(null);
    const canClose = ref(false);
    const processing = ref(false);
    const totalDuration = ref(0);
    const rewardClaimed = ref(false);
    const maxViewsReached = ref(false);
    const circumference = 2 * Math.PI * radius;
    const dashOffset = computed(() => {
      if (totalDuration.value === 0) return 0;
      const progress = timeLeft.value / totalDuration.value;
      return circumference * (1 - progress);
    });
    const isVideo = computed(() => {
      var _a;
      if (!((_a = props.advert) == null ? void 0 : _a.media_image)) return false;
      const ext = props.advert.media_image.split(".").pop().toLowerCase();
      return ["mp4", "webm", "ogg"].includes(ext);
    });
    watch(() => props.isOpen, (newVal) => {
      if (newVal && props.advert) {
        startAd();
      } else {
        resetAd();
      }
    });
    function startAd() {
      timeLeft.value = props.advert.duration;
      totalDuration.value = props.advert.duration;
      canClose.value = false;
      processing.value = false;
      rewardClaimed.value = false;
      maxViewsReached.value = false;
      if (props.advert.remaining_views <= 0) {
        maxViewsReached.value = true;
        canClose.value = true;
        timeLeft.value = 0;
        return;
      }
      if (timer.value) clearInterval(timer.value);
      timer.value = setInterval();
    }
    function resetAd() {
      if (timer.value) clearInterval(timer.value);
      timeLeft.value = 0;
    }
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f;
      if (__props.isOpen) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-md" }, _attrs))} data-v-ba17de25><div class="relative bg-white dark:bg-gray-900 w-screen h-screen flex flex-col md:flex-row overflow-hidden" data-v-ba17de25>`);
        if (canClose.value) {
          _push(`<button class="absolute top-4 right-4 z-10 text-white bg-black/50 hover:bg-black/70 rounded-full p-2 transition-colors" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:close",
            class: "w-6 h-6"
          }, null, _parent));
          _push(`</button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="w-full md:w-2/3 h-1/2 md:h-full bg-black flex items-center justify-center relative" data-v-ba17de25>`);
        if (isVideo.value) {
          _push(`<video${ssrRenderAttr("src", (_a = __props.advert) == null ? void 0 : _a.media_image)} class="w-full h-full object-contain" autoplay loop muted playsinline controls data-v-ba17de25></video>`);
        } else {
          _push(`<img${ssrRenderAttr("src", (_b = __props.advert) == null ? void 0 : _b.media_image)} class="w-full h-full object-contain" alt="Ad Content" data-v-ba17de25>`);
        }
        _push(`</div><div class="w-full md:w-1/3 h-1/2 md:h-full bg-white dark:bg-gray-800 p-8 flex flex-col items-center justify-center text-center relative" data-v-ba17de25><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2" data-v-ba17de25>${ssrInterpolate(((_c = __props.advert) == null ? void 0 : _c.title) || "Product Advertisement")}</h3>`);
        if ((_d = __props.advert) == null ? void 0 : _d.description) {
          _push(`<p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-3" data-v-ba17de25>${ssrInterpolate(__props.advert.description)}</p>`);
        } else {
          _push(`<!---->`);
        }
        if ((_e = __props.advert) == null ? void 0 : _e.media_link) {
          _push(`<a${ssrRenderAttr("href", __props.advert.media_link)} target="_blank" class="mb-6 inline-flex items-center text-teal-600 hover:text-teal-700 font-medium text-sm" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:link",
            class: "mr-1 w-4 h-4"
          }, null, _parent));
          _push(` \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E0A\u0E21\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C </a>`);
        } else {
          _push(`<div class="mb-6" data-v-ba17de25></div>`);
        }
        if (timeLeft.value > 0) {
          _push(`<div class="relative mb-8" data-v-ba17de25><svg class="w-32 h-32 transform -rotate-90" data-v-ba17de25><circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200 dark:text-gray-700" data-v-ba17de25></circle><circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="transparent"${ssrRenderAttr("stroke-dasharray", circumference)}${ssrRenderAttr("stroke-dashoffset", dashOffset.value)} class="text-teal-500 transition-all duration-1000 ease-linear" stroke-linecap="round" data-v-ba17de25></circle></svg><div class="absolute inset-0 flex items-center justify-center flex-col" data-v-ba17de25><span class="text-3xl font-bold text-gray-800 dark:text-white" data-v-ba17de25>${ssrInterpolate(timeLeft.value)}</span><span class="text-xs text-gray-500 uppercase" data-v-ba17de25>Seconds</span></div></div>`);
        } else if (rewardClaimed.value) {
          _push(`<div class="mb-8 flex flex-col items-center animate-bounce-in" data-v-ba17de25><div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:check-bold",
            class: "w-12 h-12 text-green-600 dark:text-green-400"
          }, null, _parent));
          _push(`</div><h4 class="text-2xl font-bold text-green-600 dark:text-green-400 mb-1" data-v-ba17de25>\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!</h4><p class="text-gray-600 dark:text-gray-400" data-v-ba17de25>\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27</p></div>`);
        } else if (processing.value) {
          _push(`<div class="mb-8 flex flex-col items-center" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:3-dots-fade",
            class: "w-24 h-24 text-teal-500"
          }, null, _parent));
          _push(`<h4 class="text-xl font-bold text-gray-600 dark:text-gray-300 mb-1" data-v-ba17de25>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1B\u0E23\u0E30\u0E21\u0E27\u0E25\u0E1C\u0E25...</h4></div>`);
        } else if (maxViewsReached.value) {
          _push(`<div class="mb-8 flex flex-col items-center" data-v-ba17de25><div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:eye-off-outline",
            class: "w-12 h-12 text-gray-500 dark:text-gray-400"
          }, null, _parent));
          _push(`</div><h4 class="text-2xl font-bold text-gray-600 dark:text-gray-300 mb-1" data-v-ba17de25>\u0E42\u0E06\u0E29\u0E13\u0E32\u0E19\u0E35\u0E49\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25\u0E04\u0E23\u0E1A\u0E41\u0E25\u0E49\u0E27</h4><p class="text-gray-500 dark:text-gray-400 text-sm max-w-xs" data-v-ba17de25> \u0E42\u0E06\u0E29\u0E13\u0E32\u0E19\u0E35\u0E49\u0E21\u0E35\u0E22\u0E2D\u0E14\u0E01\u0E32\u0E23\u0E23\u0E31\u0E1A\u0E0A\u0E21\u0E04\u0E23\u0E1A\u0E15\u0E32\u0E21\u0E08\u0E33\u0E19\u0E27\u0E19\u0E17\u0E35\u0E48\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E41\u0E25\u0E49\u0E27 \u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E44\u0E14\u0E49 </p></div>`);
        } else {
          _push(`<div class="mb-8 flex flex-col items-center" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:alert-circle",
            class: "w-24 h-24 text-red-500"
          }, null, _parent));
          _push(`<h4 class="text-xl font-bold text-red-500 mb-1" data-v-ba17de25>\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19</h4><p class="text-sm text-gray-500" data-v-ba17de25>\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D\u0E1C\u0E25\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48...</p></div>`);
        }
        if (timeLeft.value > 0) {
          _push(`<div class="space-y-2" data-v-ba17de25><p class="text-gray-600 dark:text-gray-300" data-v-ba17de25>\u0E23\u0E31\u0E1A\u0E0A\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</p><p class="text-2xl font-bold text-amber-500" data-v-ba17de25>${ssrInterpolate((((_f = __props.advert) == null ? void 0 : _f.duration) * 0.04).toFixed(2))} \u0E3F</p></div>`);
        } else {
          _push(`<!---->`);
        }
        if (processing.value) {
          _push(`<div class="absolute bottom-4 left-0 right-0" data-v-ba17de25><div class="flex items-center justify-center text-teal-500 gap-2" data-v-ba17de25>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:3-dots-fade",
            class: "w-6 h-6"
          }, null, _parent));
          _push(`<span class="text-sm" data-v-ba17de25>Processing Reward...</span></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/advertises/AdViewerModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AdViewerModal = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-ba17de25"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E14\u0E39\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 - Nuxni"
    });
    const adverts = ref([]);
    const isLoading = ref(true);
    const authStore = useAuthStore();
    const config = useRuntimeConfig();
    config.public.apiBase;
    const selectedAdvert = ref(null);
    const isAdModalOpen = ref(false);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const isLoadingMore = ref(false);
    function handleAdClick(advert) {
      if (!authStore.isAuthenticated) {
        Swal.fire({
          icon: "warning",
          title: "\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A",
          text: "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E0A\u0E21\u0E42\u0E06\u0E29\u0E13\u0E32"
        });
        return;
      }
      const pointsRequired = advert.duration * 20;
      if (authStore.points < pointsRequired) {
        Swal.fire({
          icon: "error",
          title: "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D",
          text: `\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 ${pointsRequired} PP \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E0A\u0E21\u0E42\u0E06\u0E29\u0E13\u0E32\u0E19\u0E35\u0E49 (\u0E04\u0E38\u0E13\u0E21\u0E35 ${authStore.points} PP)`
        });
        return;
      }
      selectedAdvert.value = advert;
      isAdModalOpen.value = true;
    }
    function handleAdClose() {
      isAdModalOpen.value = false;
      selectedAdvert.value = null;
    }
    function handleAdCompleted(advert) {
      const index = adverts.value.findIndex((a) => a.id === advert.id);
      if (index !== -1) {
        adverts.value[index].remaining_views--;
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900" }, _attrs))}><div class="max-w-7xl mx-auto"><div class="text-center mb-8"><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "eos-icons:product-subscriptions-outlined",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"> \u0E14\u0E39\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto"> \u0E0A\u0E21\u0E42\u0E06\u0E29\u0E13\u0E32\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E41\u0E25\u0E30\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E1C\u0E39\u0E49\u0E02\u0E32\u0E22 </p></div><div class="flex justify-center mb-6"><div class="flex justify-center gap-4 mb-6">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/advertise/create",
        class: "flex items-center px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 shadow-md transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:plus-circle",
              class: "mr-2 w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E25\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "mdi:plus-circle",
                class: "mr-2 w-5 h-5"
              }),
              createTextVNode(" \u0E25\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/donates/create",
        class: "flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 shadow-md transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "flat-color-icons:donate",
              class: "mr-2 w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "flat-color-icons:donate",
                class: "mr-2 w-5 h-5"
              }),
              createTextVNode(" \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
      if (isLoading.value) {
        _push(`<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse"><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700"><div class="h-48 bg-gray-200 dark:bg-gray-700 w-full"></div><div class="p-4 space-y-3"><div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="flex justify-between items-end"><div class="w-1/3"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full mb-1"></div><div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div><div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (adverts.value.length > 0) {
        _push(`<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6"><!--[-->`);
        ssrRenderList(adverts.value, (advert, index) => {
          _push(`<div class="transform hover:-translate-y-1 transition-transform duration-200">`);
          _push(ssrRenderComponent(_sfc_main$2, {
            advert,
            index,
            onClick: handleAdClick
          }, null, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-12 bg-white rounded-lg shadow">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:inbox-outline",
          class: "w-16 h-16 mx-auto text-gray-300 mb-4"
        }, null, _parent));
        _push(`<p class="text-gray-500 text-lg">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E06\u0E29\u0E13\u0E32\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p></div>`);
      }
      if (currentPage.value < lastPage.value) {
        _push(`<div class="flex justify-center mt-8"><button${ssrIncludeBooleanAttr(isLoadingMore.value) ? " disabled" : ""} class="px-6 py-2 bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center transition-colors">`);
        if (isLoadingMore.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:3-dots-fade",
            class: "w-5 h-5 mr-2"
          }, null, _parent));
        } else {
          _push(`<span>\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</span>`);
        }
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      _push(ssrRenderComponent(AdViewerModal, {
        isOpen: isAdModalOpen.value,
        advert: selectedAdvert.value,
        onClose: handleAdClose,
        onCompleted: handleAdCompleted
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Advertise/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-CIorCX2b.mjs.map
