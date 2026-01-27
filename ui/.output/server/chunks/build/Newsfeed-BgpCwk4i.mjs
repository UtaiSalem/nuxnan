import { _ as _export_sfc, f as useHead, i as useApi, d as useAuthStore, l as __nuxt_component_0$1, b as useRuntimeConfig } from './server.mjs';
import { ref, computed, mergeProps, withCtx, unref, createVNode, createBlock, openBlock, toDisplayString, createTextVNode, Fragment, renderList, createCommentVNode, defineComponent, watch, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderClass, ssrRenderAttrs, ssrIncludeBooleanAttr, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$9 } from './CreatePostBox-OC44HEYf.mjs';
import { F as FeedPost, _ as _sfc_main$b } from './ProfileCompletionWidget-BzF-6HBI.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { _ as _sfc_main$1$1, a as _sfc_main$a } from './MemberedCoursesWidget-QtO1MfNH.mjs';
import { storeToRefs } from 'pinia';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import './useAvatar-C8DTKR1c.mjs';
import './PollCard-DKn1EeyZ.mjs';
import './useSweetAlert-jHixiibP.mjs';
import 'sweetalert2';
import './useToast-BpzfS75l.mjs';
import './ImageLightbox-D9vQ7Zkj.mjs';

const _sfc_main$8 = {
  __name: "StatsBoxWidget",
  __ssrInlineRender: true,
  props: {
    postsCreated: {
      type: Number,
      default: 294
    },
    growth: {
      type: Number,
      default: 0.4
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "vikinger-card bg-gradient-to-br from-vikinger-cyan to-vikinger-purple text-white relative overflow-hidden" }, _attrs))}><div class="absolute top-4 right-4 w-16 h-16 bg-white/10 rounded-full"></div><div class="absolute bottom-0 right-0 w-24 h-24 bg-white/10 rounded-full translate-x-8 translate-y-8"></div><div class="relative z-10"><div class="flex items-center justify-between mb-4"><h3 class="text-sm font-bold">Stats Box</h3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:more-horizontal-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</div><div class="flex items-end gap-4"><div class="flex-1"><div class="text-4xl font-bold mb-1">${ssrInterpolate(__props.postsCreated)}</div><div class="flex items-center gap-1 text-xs">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-trending-up-24-filled",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span>${ssrInterpolate(__props.growth)}%</span></div><div class="text-sm font-semibold mt-2">Posts Created</div><div class="text-xs opacity-80">in the last month</div></div><div class="flex items-end gap-1 h-16 pb-2"><div class="w-2 bg-white/40 rounded-t" style="${ssrRenderStyle({ "height": "40%" })}"></div><div class="w-2 bg-white/50 rounded-t" style="${ssrRenderStyle({ "height": "60%" })}"></div><div class="w-2 bg-white/60 rounded-t" style="${ssrRenderStyle({ "height": "45%" })}"></div><div class="w-2 bg-white/70 rounded-t" style="${ssrRenderStyle({ "height": "75%" })}"></div><div class="w-2 bg-white/90 rounded-t" style="${ssrRenderStyle({ "height": "90%" })}"></div><div class="w-2 bg-white rounded-t" style="${ssrRenderStyle({ "height": "100%" })}"></div></div></div></div></div>`);
    };
  }
};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/organisms/StatsBoxWidget.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const _sfc_main$7 = {
  __name: "PeopleMayKnowWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const users = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="font-semibold text-gray-900 dark:text-white">\u0E04\u0E19\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E2D\u0E32\u0E08\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01</h3><button class="text-vikinger-purple hover:text-vikinger-purple/80 transition-colors"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""}>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-sync-24-regular",
        class: ["w-4 h-4", { "animate-spin": isLoading.value }]
      }, null, _parent));
      _push(`</button></div>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="flex items-center gap-3 animate-pulse"><div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div><div class="flex-1 space-y-2"><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (error.value) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-8 h-8 mx-auto mb-2 text-red-400"
        }, null, _parent));
        _push(`<p class="text-sm">${ssrInterpolate(error.value)}</p></div>`);
      } else if (users.value.length === 0) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-regular",
          class: "w-8 h-8 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p class="text-sm">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E41\u0E19\u0E30\u0E19\u0E33\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p></div>`);
      } else {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(users.value, (user) => {
          _push(`<div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors">`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/profile/${user.reference_code}`
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<img${ssrRenderAttr("src", user.avatar || "/storage/images/default-avatar.png")}${ssrRenderAttr("alt", user.name)} class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"${_scopeId}>`);
              } else {
                return [
                  createVNode("img", {
                    src: user.avatar || "/storage/images/default-avatar.png",
                    alt: user.name,
                    class: "w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"
                  }, null, 8, ["src", "alt"])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`<div class="flex-1 min-w-0">`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/profile/${user.reference_code}`,
            class: "block font-medium text-gray-900 dark:text-white truncate hover:text-vikinger-purple transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`${ssrInterpolate(user.name)}`);
              } else {
                return [
                  createTextVNode(toDisplayString(user.name), 1)
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`<p class="text-xs text-gray-500 dark:text-gray-400 truncate">${ssrInterpolate(user.email)}</p></div><button class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full hover:opacity-90 transition-opacity">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-add-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span class="hidden sm:inline">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span></button></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/PeopleMayKnowWidget.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const _sfc_main$6 = {
  __name: "PendingFriendsWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const requests = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="font-semibold text-gray-900 dark:text-white">\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</h3>`);
      if (requests.value.length > 0) {
        _push(`<span class="px-2 py-0.5 text-xs font-medium text-white bg-vikinger-purple rounded-full">${ssrInterpolate(requests.value.length)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(2, (i) => {
          _push(`<div class="flex items-center gap-3 animate-pulse"><div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div><div class="flex-1 space-y-2"><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (error.value) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-8 h-8 mx-auto mb-2 text-red-400"
        }, null, _parent));
        _push(`<p class="text-sm">${ssrInterpolate(error.value)}</p></div>`);
      } else if (requests.value.length === 0) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-checkmark-24-regular",
          class: "w-8 h-8 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p class="text-sm">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E43\u0E2B\u0E21\u0E48</p></div>`);
      } else {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(requests.value, (request) => {
          var _a, _b, _c;
          _push(`<div class="p-3 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100"><div class="flex items-center gap-3 mb-3"><img${ssrRenderAttr("src", ((_a = request.sender) == null ? void 0 : _a.avatar) || "/storage/images/default-avatar.png")}${ssrRenderAttr("alt", (_b = request.sender) == null ? void 0 : _b.name)} class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"><div class="flex-1 min-w-0"><p class="font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate((_c = request.sender) == null ? void 0 : _c.name)}</p><p class="text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(request.diff_humans_created_at || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</p></div></div><div class="flex gap-2"><button class="flex-1 py-2 text-sm font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-lg hover:opacity-90 transition-opacity"> \u0E22\u0E2D\u0E21\u0E23\u0E31\u0E1A </button><button class="flex-1 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-vikinger-dark-200 rounded-lg hover:bg-gray-300 dark:hover:bg-vikinger-dark-50 transition-colors"> \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/PendingFriendsWidget.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const _sfc_main$5 = {
  __name: "DonatesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    useAuthStore();
    const donates = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    const processingId = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="font-semibold text-gray-900 dark:text-white">\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21</h3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/donates",
        class: "text-xs text-vikinger-purple hover:text-vikinger-purple/80 transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="flex items-center gap-3 animate-pulse"><div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-lg"></div><div class="flex-1 space-y-2"><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (error.value) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-8 h-8 mx-auto mb-2 text-red-400"
        }, null, _parent));
        _push(`<p class="text-sm">${ssrInterpolate(error.value)}</p></div>`);
      } else if (donates.value.length === 0) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:hand-coin-outline",
          class: "w-8 h-8 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p class="text-sm">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p></div>`);
      } else {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(donates.value, (donate) => {
          var _a;
          _push(`<div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"><div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center relative">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:hand-coin",
            class: "w-5 h-5 text-white"
          }, null, _parent));
          if (donate.status === 0) {
            _push(`<div class="absolute -top-1 -right-1 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center" title="\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:clock-outline",
              class: "w-2.5 h-2.5 text-white"
            }, null, _parent));
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="flex-1 min-w-0"><div class="flex items-center gap-1.5"><p class="font-medium text-gray-900 dark:text-white text-sm truncate">${ssrInterpolate(donate.donor_name || "\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")}</p>`);
          if (donate.status === 0) {
            _push(`<span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-medium rounded"> \u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 </span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><p class="text-xs text-gray-500 dark:text-gray-400"> \u0E40\u0E2B\u0E25\u0E37\u0E2D ${ssrInterpolate(typeof donate.remaining_points === "string" ? donate.remaining_points : ((_a = donate.remaining_points) == null ? void 0 : _a.toLocaleString()) || 0)} \u0E41\u0E15\u0E49\u0E21 </p></div><button${ssrIncludeBooleanAttr(processingId.value === donate.id || donate.remaining_points < 270 || donate.status === 0) ? " disabled" : ""} class="${ssrRenderClass([
            "px-3 py-1.5 text-xs font-medium rounded-full transition-opacity flex items-center gap-1",
            donate.status === 0 ? "bg-gray-400 dark:bg-gray-600 text-white cursor-not-allowed" : "text-white bg-gradient-to-r from-yellow-500 to-orange-500 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
          ])}">`);
          if (processingId.value === donate.id) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-3 h-3 animate-spin"
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          if (processingId.value === donate.id) {
            _push(`<span>\u0E01\u0E33\u0E25\u0E31\u0E07...</span>`);
          } else if (donate.status === 0) {
            _push(`<span>\u0E23\u0E2D</span>`);
          } else {
            _push(`<span>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21</span>`);
          }
          _push(`</button></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/DonatesWidget.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "AdvertisesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const advertises = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4" }, _attrs))}><div class="flex items-center justify-between mb-4"><h3 class="font-bold text-lg dark:text-white">\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32\u0E41\u0E19\u0E30\u0E19\u0E33</h3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/advertise",
        class: "text-sm text-blue-500 hover:underline"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(2, (i) => {
          _push(`<div class="animate-pulse"><div class="h-24 bg-gray-200 dark:bg-vikinger-dark-100 rounded-lg mb-2"></div><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (error.value) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-8 h-8 mx-auto mb-2 text-red-400"
        }, null, _parent));
        _push(`<p class="text-sm">${ssrInterpolate(error.value)}</p></div>`);
      } else if (advertises.value.length === 0) {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "eos-icons:product-subscriptions-outlined",
          class: "w-8 h-8 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p class="text-sm">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E06\u0E29\u0E13\u0E32\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p></div>`);
      } else {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(advertises.value, (advert) => {
          var _a;
          _push(`<div class="rounded-lg overflow-hidden bg-gray-50 dark:bg-vikinger-dark-100 hover:shadow-md transition-shadow cursor-pointer">`);
          if (advert.media_image) {
            _push(`<img${ssrRenderAttr("src", advert.media_image)}${ssrRenderAttr("alt", advert.supporter)} class="w-full h-24 object-cover">`);
          } else {
            _push(`<div class="w-full h-24 bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:megaphone-24-regular",
              class: "w-8 h-8 text-white/60"
            }, null, _parent));
            _push(`</div>`);
          }
          _push(`<div class="p-2"><p class="text-sm font-medium text-gray-900 dark:text-white truncate">${ssrInterpolate(advert.supporter || "\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")}</p><div class="flex items-center justify-between mt-1"><span class="text-xs text-gray-500 dark:text-gray-400"> \u0E40\u0E2B\u0E25\u0E37\u0E2D ${ssrInterpolate(((_a = advert.remaining_views) == null ? void 0 : _a.toLocaleString()) || 0)} \u0E04\u0E23\u0E31\u0E49\u0E07 </span><span class="text-xs font-medium text-green-600 dark:text-green-400"> +${ssrInterpolate((advert.duration * 0.04).toFixed(2))} \u0E1A\u0E32\u0E17 </span></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/AdvertisesWidget.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "PopularCoursesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const popularCourses = ref([]);
    const isLoading = ref(true);
    const config = useRuntimeConfig();
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm mb-6" }, _attrs))}><h3 class="font-semibold text-gray-900 dark:text-white mb-4">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E22\u0E2D\u0E14\u0E19\u0E34\u0E22\u0E21</h3>`);
      if (isLoading.value) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div><div class="flex-1 space-y-2 py-1"><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (popularCourses.value.length > 0) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(popularCourses.value, (course) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: course.id,
            to: `/courses/${course.id}`,
            class: "flex items-start gap-3 group hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 p-2 -mx-2 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="relative shrink-0 w-12 h-12 mt-1"${_scopeId}><img${ssrRenderAttr("src", course.cover ? course.cover : `${unref(config).public.apiBase}/storage/images/courses/covers/default_cover.jpg`)}${ssrRenderAttr("alt", course.name)} class="w-full h-full object-cover rounded-lg shadow-sm"${_scopeId}></div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1"${_scopeId}>${ssrInterpolate(course.name)}</h4><div class="flex items-center justify-between"${_scopeId}><span class="text-xs text-gray-500 dark:text-gray-400 truncate"${_scopeId}>${ssrInterpolate(course.user ? course.user.name : course.instructor ? course.instructor.name : "Unknown")}</span></div></div><div class="flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple transition-colors mt-2"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:bookmark-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "relative shrink-0 w-12 h-12 mt-1" }, [
                    createVNode("img", {
                      src: course.cover ? course.cover : `${unref(config).public.apiBase}/storage/images/courses/covers/default_cover.jpg`,
                      alt: course.name,
                      class: "w-full h-full object-cover rounded-lg shadow-sm"
                    }, null, 8, ["src", "alt"])
                  ]),
                  createVNode("div", { class: "flex-1 min-w-0" }, [
                    createVNode("h4", { class: "text-sm font-medium text-gray-900 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1" }, toDisplayString(course.name), 1),
                    createVNode("div", { class: "flex items-center justify-between" }, [
                      createVNode("span", { class: "text-xs text-gray-500 dark:text-gray-400 truncate" }, toDisplayString(course.user ? course.user.name : course.instructor ? course.instructor.name : "Unknown"), 1)
                    ])
                  ]),
                  createVNode("div", { class: "flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple transition-colors mt-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:bookmark-24-regular",
                      class: "w-5 h-5"
                    })
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:book-off-20-regular",
          class: "w-8 h-8 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E22\u0E2D\u0E14\u0E19\u0E34\u0E22\u0E21</p></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/PopularCoursesWidget.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "MemberedAcademiesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const { user } = storeToRefs(useAuthStore());
    const config = useRuntimeConfig();
    const academies = ref([]);
    const isLoading = ref(true);
    ref(1);
    const hasMore = ref(false);
    const getLogoUrl = (academy) => {
      if (academy.logo) {
        if (academy.logo.startsWith("http")) return academy.logo;
        return academy.logo;
      }
      return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`;
    };
    const getMemberStatusLabel = (status) => {
      const statusMap = {
        1: { label: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", color: "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" },
        0: { label: "\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", color: "bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400" },
        "pending": { label: "\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", color: "bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400" },
        "approved": { label: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", color: "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400" }
      };
      return statusMap[status] || { label: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", color: "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400" };
    };
    watch(user, (newUser) => {
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden mb-6" }, _attrs))}><div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:building-24-regular",
        class: "w-4 h-4 text-white"
      }, null, _parent));
      _push(`</div><h3 class="font-bold text-gray-800 dark:text-white">\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14</h3></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/academies",
        class: "text-xs text-vikinger-purple hover:text-vikinger-cyan transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="divide-y divide-gray-100 dark:divide-gray-700">`);
      if (isLoading.value && academies.value.length === 0) {
        _push(`<!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="p-4 flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div><div class="flex-1 space-y-2"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]-->`);
      } else if (academies.value.length > 0) {
        _push(`<!--[-->`);
        ssrRenderList(academies.value, (academy) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: academy.id,
            to: `/academies/${encodeURIComponent(academy.name)}`,
            class: "p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="relative w-12 h-12 flex-shrink-0"${_scopeId}><div class="absolute inset-0 rounded-full border-2 border-vikinger-purple"${_scopeId}></div><div class="absolute inset-[2px] rounded-full overflow-hidden bg-gray-100"${_scopeId}><img${ssrRenderAttr("src", getLogoUrl(academy))}${ssrRenderAttr("alt", academy.name)} class="w-full h-full object-cover"${_scopeId}></div></div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 group-hover:text-vikinger-purple transition-colors"${_scopeId}>${ssrInterpolate(academy.name)}</h4><div class="flex items-center gap-2 mt-1"${_scopeId}><span class="${ssrRenderClass([getMemberStatusLabel(academy.memberStatus).color, "px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"])}"${_scopeId}>${ssrInterpolate(getMemberStatusLabel(academy.memberStatus).label)}</span>`);
                if (academy.address) {
                  _push2(`<span class="text-xs text-gray-500 dark:text-gray-400 truncate"${_scopeId}>${ssrInterpolate(academy.address)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div></div>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chevron-right-24-regular",
                  class: "w-4 h-4 text-gray-400 group-hover:text-vikinger-purple transition-colors flex-shrink-0"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode("div", { class: "relative w-12 h-12 flex-shrink-0" }, [
                    createVNode("div", { class: "absolute inset-0 rounded-full border-2 border-vikinger-purple" }),
                    createVNode("div", { class: "absolute inset-[2px] rounded-full overflow-hidden bg-gray-100" }, [
                      createVNode("img", {
                        src: getLogoUrl(academy),
                        alt: academy.name,
                        class: "w-full h-full object-cover"
                      }, null, 8, ["src", "alt"])
                    ])
                  ]),
                  createVNode("div", { class: "flex-1 min-w-0" }, [
                    createVNode("h4", { class: "text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 group-hover:text-vikinger-purple transition-colors" }, toDisplayString(academy.name), 1),
                    createVNode("div", { class: "flex items-center gap-2 mt-1" }, [
                      createVNode("span", {
                        class: ["px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full", getMemberStatusLabel(academy.memberStatus).color]
                      }, toDisplayString(getMemberStatusLabel(academy.memberStatus).label), 3),
                      academy.address ? (openBlock(), createBlock("span", {
                        key: 0,
                        class: "text-xs text-gray-500 dark:text-gray-400 truncate"
                      }, toDisplayString(academy.address), 1)) : createCommentVNode("", true)
                    ])
                  ]),
                  createVNode(unref(Icon), {
                    icon: "fluent:chevron-right-24-regular",
                    class: "w-4 h-4 text-gray-400 group-hover:text-vikinger-purple transition-colors flex-shrink-0"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]-->`);
      } else {
        _push(`<div class="p-6 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:building-multiple-24-regular",
          class: "w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"
        }, null, _parent));
        _push(`<p class="text-sm text-gray-500 dark:text-gray-400 mb-3">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E31\u0E07\u0E01\u0E31\u0E14\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E14</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/academies",
          class: "inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full hover:opacity-90 transition-opacity"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:search-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:search-24-regular",
                  class: "w-4 h-4"
                }),
                createTextVNode(" \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      }
      _push(`</div>`);
      if (hasMore.value && academies.value.length > 0) {
        _push(`<div class="p-3 border-t border-gray-100 dark:border-gray-700"><button class="w-full py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-lg transition-colors"> \u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 </button></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/MemberedAcademiesWidget.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "AllAcademiesWidget",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    const config = useRuntimeConfig();
    const { user } = storeToRefs(useAuthStore());
    const academies = ref([]);
    const isLoading = ref(true);
    const getLogoUrl = (academy) => {
      if (academy.logo) {
        if (academy.logo.startsWith("http")) return academy.logo;
        return academy.logo;
      }
      return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`;
    };
    const getAcademyType = (type) => {
      const typeMap = {
        "public": { label: "\u0E23\u0E31\u0E10\u0E1A\u0E32\u0E25", icon: "fluent:building-government-24-regular", color: "text-blue-500" },
        "private": { label: "\u0E40\u0E2D\u0E01\u0E0A\u0E19", icon: "fluent:building-bank-24-regular", color: "text-purple-500" },
        "foundation": { label: "\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34", icon: "fluent:heart-24-regular", color: "text-pink-500" },
        "international": { label: "\u0E19\u0E32\u0E19\u0E32\u0E0A\u0E32\u0E15\u0E34", icon: "fluent:globe-24-regular", color: "text-green-500" }
      };
      return typeMap[type || ""] || { label: "\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B", icon: "fluent:building-24-regular", color: "text-gray-500" };
    };
    watch(user, (newUser) => {
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden mb-6" }, _attrs))}><div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-pink-500 flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:building-multiple-24-regular",
        class: "w-4 h-4 text-white"
      }, null, _parent));
      _push(`</div><h3 class="font-bold text-gray-800 dark:text-white">\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</h3></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/academies",
        class: "text-xs text-vikinger-purple hover:text-vikinger-cyan transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="divide-y divide-gray-100 dark:divide-gray-700">`);
      if (isLoading.value) {
        _push(`<!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="p-4 flex gap-3 animate-pulse"><div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div><div class="flex-1 space-y-2"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div></div>`);
        });
        _push(`<!--]-->`);
      } else if (academies.value.length > 0) {
        _push(`<!--[-->`);
        ssrRenderList(academies.value, (academy) => {
          _push(ssrRenderComponent(_component_NuxtLink, {
            key: academy.id,
            to: `/academies/${encodeURIComponent(academy.name)}`,
            class: "p-4 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="relative w-12 h-12 flex-shrink-0 mt-1"${_scopeId}><img${ssrRenderAttr("src", getLogoUrl(academy))}${ssrRenderAttr("alt", academy.name)} class="w-full h-full object-cover rounded-lg shadow-sm"${_scopeId}></div><div class="flex-1 min-w-0"${_scopeId}><h4 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1"${_scopeId}>${ssrInterpolate(academy.name)}</h4><div class="flex items-center gap-2 flex-wrap"${_scopeId}>`);
                if (academy.type) {
                  _push2(`<span class="${ssrRenderClass([getAcademyType(academy.type).color, "inline-flex items-center gap-1 text-xs"])}"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: getAcademyType(academy.type).icon,
                    class: "w-3 h-3"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(getAcademyType(academy.type).label)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (academy.address) {
                  _push2(`<span class="text-xs text-gray-500 dark:text-gray-400 truncate"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:location-24-regular",
                    class: "w-3 h-3 inline"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(academy.address)}</span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div><div class="flex items-center gap-3 mt-2 text-xs text-gray-400 dark:text-gray-500"${_scopeId}>`);
                if (academy.total_students) {
                  _push2(`<span class="flex items-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:people-24-regular",
                    class: "w-3 h-3"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(academy.total_students)} \u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </span>`);
                } else {
                  _push2(`<!---->`);
                }
                if (academy.total_teachers) {
                  _push2(`<span class="flex items-center gap-1"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:person-24-regular",
                    class: "w-3 h-3"
                  }, null, _parent2, _scopeId));
                  _push2(` ${ssrInterpolate(academy.total_teachers)} \u0E04\u0E23\u0E39 </span>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div></div>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chevron-right-24-regular",
                  class: "w-4 h-4 text-gray-400 group-hover:text-vikinger-purple transition-colors flex-shrink-0 mt-2"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode("div", { class: "relative w-12 h-12 flex-shrink-0 mt-1" }, [
                    createVNode("img", {
                      src: getLogoUrl(academy),
                      alt: academy.name,
                      class: "w-full h-full object-cover rounded-lg shadow-sm"
                    }, null, 8, ["src", "alt"])
                  ]),
                  createVNode("div", { class: "flex-1 min-w-0" }, [
                    createVNode("h4", { class: "text-sm font-semibold text-gray-800 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1" }, toDisplayString(academy.name), 1),
                    createVNode("div", { class: "flex items-center gap-2 flex-wrap" }, [
                      academy.type ? (openBlock(), createBlock("span", {
                        key: 0,
                        class: ["inline-flex items-center gap-1 text-xs", getAcademyType(academy.type).color]
                      }, [
                        createVNode(unref(Icon), {
                          icon: getAcademyType(academy.type).icon,
                          class: "w-3 h-3"
                        }, null, 8, ["icon"]),
                        createTextVNode(" " + toDisplayString(getAcademyType(academy.type).label), 1)
                      ], 2)) : createCommentVNode("", true),
                      academy.address ? (openBlock(), createBlock("span", {
                        key: 1,
                        class: "text-xs text-gray-500 dark:text-gray-400 truncate"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:location-24-regular",
                          class: "w-3 h-3 inline"
                        }),
                        createTextVNode(" " + toDisplayString(academy.address), 1)
                      ])) : createCommentVNode("", true)
                    ]),
                    createVNode("div", { class: "flex items-center gap-3 mt-2 text-xs text-gray-400 dark:text-gray-500" }, [
                      academy.total_students ? (openBlock(), createBlock("span", {
                        key: 0,
                        class: "flex items-center gap-1"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:people-24-regular",
                          class: "w-3 h-3"
                        }),
                        createTextVNode(" " + toDisplayString(academy.total_students) + " \u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 ", 1)
                      ])) : createCommentVNode("", true),
                      academy.total_teachers ? (openBlock(), createBlock("span", {
                        key: 1,
                        class: "flex items-center gap-1"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:person-24-regular",
                          class: "w-3 h-3"
                        }),
                        createTextVNode(" " + toDisplayString(academy.total_teachers) + " \u0E04\u0E23\u0E39 ", 1)
                      ])) : createCommentVNode("", true)
                    ])
                  ]),
                  createVNode(unref(Icon), {
                    icon: "fluent:chevron-right-24-regular",
                    class: "w-4 h-4 text-gray-400 group-hover:text-vikinger-purple transition-colors flex-shrink-0 mt-2"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
        });
        _push(`<!--]-->`);
      } else {
        _push(`<div class="p-6 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:building-multiple-24-regular",
          class: "w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"
        }, null, _parent));
        _push(`<p class="text-sm text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E23\u0E30\u0E1A\u0E1A</p></div>`);
      }
      _push(`</div>`);
      if (academies.value.length > 0) {
        _push(`<div class="p-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-vikinger-dark-100">`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/academies",
          class: "w-full flex items-center justify-center gap-2 py-2 text-sm font-medium text-vikinger-purple hover:text-vikinger-cyan transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:grid-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E14\u0E39\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:grid-24-regular",
                  class: "w-4 h-4"
                }),
                createTextVNode(" \u0E14\u0E39\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/AllAcademiesWidget.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "Newsfeed",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Newsfeed - Plearnd"
    });
    const api = useApi();
    useAuthStore();
    const activities = ref([]);
    const isLoading = ref(true);
    const isLoadingMore = ref(false);
    const error = ref(null);
    const pagination = ref({
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 15
    });
    const hasMorePages = computed(() => pagination.value.currentPage < pagination.value.lastPage);
    const loadMoreTrigger = ref(null);
    const userStats = computed(() => ({
      posts: 294,
      postsGrowth: 0.4,
      likes: 12642,
      loves: 8913,
      dislikes: 945,
      happy: 7034
    }));
    const profileCompletion = computed(() => 59);
    const quests = ref({ completed: 11, total: 30 });
    const badges = ref({ unlocked: 22, total: 46 });
    const recentStories = ref([
      {
        id: 1,
        user: "Sarah Diamond",
        avatar: "https://i.pravatar.cc/150?img=1",
        preview: "https://picsum.photos/200/300?random=1"
      },
      {
        id: 2,
        user: "James Murdock",
        avatar: "https://i.pravatar.cc/150?img=2",
        preview: "https://picsum.photos/200/300?random=2"
      }
    ]);
    const featuredBadges = ref([
      { id: 1, name: "Gold Star", icon: "\u2B50", color: "from-yellow-400 to-yellow-600" },
      { id: 2, name: "Shield", icon: "\u{1F6E1}\uFE0F", color: "from-blue-400 to-blue-600" }
    ]);
    const fetchActivities = async (page = 1, append = false) => {
      var _a, _b, _c;
      if (page === 1) {
        isLoading.value = true;
      } else {
        isLoadingMore.value = true;
      }
      error.value = null;
      try {
        const data = await api.get(`/api/newsfeed/activities?page=${page}&per_page=${pagination.value.perPage}`);
        if (data && data.activities) {
          const newActivities = Array.isArray(data.activities) ? data.activities : data.activities.data || [];
          if (append) {
            activities.value = [...activities.value, ...newActivities];
          } else {
            activities.value = newActivities;
          }
          if (data.activities.current_page !== void 0) {
            pagination.value = {
              currentPage: data.activities.current_page || page,
              lastPage: data.activities.last_page || 1,
              total: data.activities.total || 0,
              perPage: data.activities.per_page || 15
            };
          } else {
            pagination.value.currentPage = page;
            if (newActivities.length < pagination.value.perPage) {
              pagination.value.lastPage = page;
            } else {
              pagination.value.lastPage = page + 1;
            }
          }
        }
      } catch (err) {
        console.error("Error fetching activities:", err);
        console.error("Error details:", {
          message: err == null ? void 0 : err.message,
          status: (err == null ? void 0 : err.statusCode) || ((_a = err == null ? void 0 : err.response) == null ? void 0 : _a.status),
          data: err == null ? void 0 : err.data
        });
        if ((err == null ? void 0 : err.statusCode) === 401 || ((_b = err == null ? void 0 : err.message) == null ? void 0 : _b.includes("401")) || ((_c = err == null ? void 0 : err.message) == null ? void 0 : _c.includes("authentication"))) {
          error.value = "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07";
        } else {
          error.value = "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E44\u0E14\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07";
        }
      } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
      }
    };
    const refreshFeed = async () => {
      pagination.value.currentPage = 1;
      await fetchActivities(1, false);
    };
    const handlePostCreated = (activity) => {
      if (activity && activity.id) {
        activities.value.unshift(activity);
      }
    };
    const handleDeleteActivity = (activityId) => {
      activities.value = activities.value.filter((a) => a.id !== activityId);
      if (pagination.value.total > 0) {
        pagination.value.total--;
      }
    };
    const handlePostUpdated = (updatedPost) => {
      const index = activities.value.findIndex((a) => {
        if (a.target_resource && a.target_resource.id === updatedPost.id) return true;
        return a.id === updatedPost.id;
      });
      if (index !== -1) {
        if (activities.value[index].target_resource) {
          activities.value[index] = {
            ...activities.value[index],
            target_resource: { ...activities.value[index].target_resource, ...updatedPost }
          };
        } else {
          activities.value[index] = { ...activities.value[index], ...updatedPost };
        }
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLayout = __nuxt_component_0$1;
      _push(ssrRenderComponent(_component_NuxtLayout, mergeProps({ name: "main" }, _attrs), {
        leftWidgets: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$1$1, { class: "mb-6" }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$a, { class: "mb-6" }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, { class: "mb-6" }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$b, {
              completion: profileCompletion.value,
              quests: quests.value,
              badges: badges.value
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$8, { stats: userStats.value }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$1$1, { class: "mb-6" }),
              createVNode(_sfc_main$a, { class: "mb-6" }),
              createVNode(_sfc_main$3, { class: "mb-6" }),
              createVNode(_sfc_main$b, {
                completion: profileCompletion.value,
                quests: quests.value,
                badges: badges.value
              }, null, 8, ["completion", "quests", "badges"]),
              createVNode(_sfc_main$8, { stats: userStats.value }, null, 8, ["stats"])
            ];
          }
        }),
        rightWidgets: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$2, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$7, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$6, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$5, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$4, null, null, _parent2, _scopeId));
            _push2(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" data-v-73c5e4e8${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white mb-4" data-v-73c5e4e8${_scopeId}>Recent Stories</h3><div class="flex gap-2 overflow-x-auto" data-v-73c5e4e8${_scopeId}><!--[-->`);
            ssrRenderList(recentStories.value, (story) => {
              _push2(`<div class="flex-shrink-0 w-16 cursor-pointer group" data-v-73c5e4e8${_scopeId}><div class="relative" data-v-73c5e4e8${_scopeId}><img${ssrRenderAttr("src", story.preview)}${ssrRenderAttr("alt", story.user)} class="w-16 h-24 object-cover rounded-lg ring-2 ring-vikinger-purple" data-v-73c5e4e8${_scopeId}><img${ssrRenderAttr("src", story.avatar)}${ssrRenderAttr("alt", story.user)} class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full border-2 border-white dark:border-vikinger-dark-200" data-v-73c5e4e8${_scopeId}></div></div>`);
            });
            _push2(`<!--]--></div></div><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" data-v-73c5e4e8${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white mb-4" data-v-73c5e4e8${_scopeId}>Featured Badges</h3><div class="flex gap-3" data-v-73c5e4e8${_scopeId}><!--[-->`);
            ssrRenderList(featuredBadges.value, (badge) => {
              _push2(`<div class="${ssrRenderClass([badge.color, "w-12 h-12 rounded-lg flex items-center justify-center text-2xl bg-gradient-to-br shadow-lg"])}" data-v-73c5e4e8${_scopeId}>${ssrInterpolate(badge.icon)}</div>`);
            });
            _push2(`<!--]--></div></div>`);
          } else {
            return [
              createVNode(_sfc_main$2),
              createVNode(_sfc_main$1),
              createVNode(_sfc_main$7),
              createVNode(_sfc_main$6),
              createVNode(_sfc_main$5),
              createVNode(_sfc_main$4),
              createVNode("div", { class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" }, [
                createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-4" }, "Recent Stories"),
                createVNode("div", { class: "flex gap-2 overflow-x-auto" }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(recentStories.value, (story) => {
                    return openBlock(), createBlock("div", {
                      key: story.id,
                      class: "flex-shrink-0 w-16 cursor-pointer group"
                    }, [
                      createVNode("div", { class: "relative" }, [
                        createVNode("img", {
                          src: story.preview,
                          alt: story.user,
                          class: "w-16 h-24 object-cover rounded-lg ring-2 ring-vikinger-purple"
                        }, null, 8, ["src", "alt"]),
                        createVNode("img", {
                          src: story.avatar,
                          alt: story.user,
                          class: "absolute -bottom-2 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full border-2 border-white dark:border-vikinger-dark-200"
                        }, null, 8, ["src", "alt"])
                      ])
                    ]);
                  }), 128))
                ])
              ]),
              createVNode("div", { class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm" }, [
                createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-4" }, "Featured Badges"),
                createVNode("div", { class: "flex gap-3" }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(featuredBadges.value, (badge) => {
                    return openBlock(), createBlock("div", {
                      key: badge.id,
                      class: ["w-12 h-12 rounded-lg flex items-center justify-center text-2xl bg-gradient-to-br shadow-lg", badge.color]
                    }, toDisplayString(badge.icon), 3);
                  }), 128))
                ])
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-6" data-v-73c5e4e8${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$9, { onPostCreated: handlePostCreated }, null, _parent2, _scopeId));
            if (error.value && !isLoading.value) {
              _push2(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-6 text-center" data-v-73c5e4e8${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:error-circle-24-regular",
                class: "w-12 h-12 mx-auto mb-3 text-red-500"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-red-600 dark:text-red-400 mb-4" data-v-73c5e4e8${_scopeId}>${ssrInterpolate(error.value)}</p><button class="px-6 py-2 bg-vikinger-purple text-white rounded-full hover:bg-vikinger-purple/90 transition-colors" data-v-73c5e4e8${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-sync-24-regular",
                class: "w-4 h-4 inline mr-2"
              }, null, _parent2, _scopeId));
              _push2(` \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </button></div>`);
            } else if (isLoading.value) {
              _push2(`<div class="space-y-4" data-v-73c5e4e8${_scopeId}><!--[-->`);
              ssrRenderList(3, (i) => {
                _push2(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-6 animate-pulse" data-v-73c5e4e8${_scopeId}><div class="flex items-center gap-4 mb-4" data-v-73c5e4e8${_scopeId}><div class="w-12 h-12 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full" data-v-73c5e4e8${_scopeId}></div><div class="flex-1 space-y-2" data-v-73c5e4e8${_scopeId}><div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/4" data-v-73c5e4e8${_scopeId}></div><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/6" data-v-73c5e4e8${_scopeId}></div></div></div><div class="space-y-2" data-v-73c5e4e8${_scopeId}><div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-full" data-v-73c5e4e8${_scopeId}></div><div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4" data-v-73c5e4e8${_scopeId}></div></div></div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="space-y-4" data-v-73c5e4e8${_scopeId}><div class="flex justify-center" data-v-73c5e4e8${_scopeId}><button class="flex items-center gap-2 px-4 py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-full transition-colors" data-v-73c5e4e8${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-sync-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A\u0E1F\u0E35\u0E14 </button></div><!--[-->`);
              ssrRenderList(activities.value, (activity) => {
                _push2(ssrRenderComponent(FeedPost, {
                  key: activity.id,
                  post: activity,
                  onDeleteSuccess: handleDeleteActivity,
                  onPostUpdated: handlePostUpdated
                }, null, _parent2, _scopeId));
              });
              _push2(`<!--]-->`);
              if (hasMorePages.value) {
                _push2(`<div class="flex justify-center py-6" data-v-73c5e4e8${_scopeId}>`);
                if (isLoadingMore.value) {
                  _push2(`<div class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-vikinger-dark-200 text-vikinger-purple rounded-full shadow-sm" data-v-73c5e4e8${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:spinner-ios-20-regular",
                    class: "w-5 h-5 animate-spin"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14... </div>`);
                } else {
                  _push2(`<div class="h-4 w-full" data-v-73c5e4e8${_scopeId}></div>`);
                }
                _push2(`</div>`);
              } else if (activities.value.length > 0) {
                _push2(`<div class="text-center py-6 text-gray-500 dark:text-gray-400" data-v-73c5e4e8${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:checkmark-circle-24-regular",
                  class: "w-8 h-8 mx-auto mb-2 text-green-500"
                }, null, _parent2, _scopeId));
                _push2(`<p data-v-73c5e4e8${_scopeId}>\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E14\u0E39\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E41\u0E25\u0E49\u0E27!</p></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (activities.value.length === 0 && !error.value) {
                _push2(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center" data-v-73c5e4e8${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:document-text-24-regular",
                  class: "w-16 h-16 mx-auto mb-4 text-gray-400"
                }, null, _parent2, _scopeId));
                _push2(`<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2" data-v-73c5e4e8${_scopeId}> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E1E\u0E2A\u0E15\u0E4C </h3><p class="text-gray-500 dark:text-gray-400" data-v-73c5e4e8${_scopeId}> \u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E41\u0E0A\u0E23\u0E4C\u0E2A\u0E34\u0E48\u0E07\u0E14\u0E35\u0E46 \u0E01\u0E31\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E46 \u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49! </p></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-6" }, [
                createVNode(_sfc_main$9, { onPostCreated: handlePostCreated }),
                error.value && !isLoading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "bg-red-50 dark:bg-red-900/20 rounded-xl p-6 text-center"
                }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:error-circle-24-regular",
                    class: "w-12 h-12 mx-auto mb-3 text-red-500"
                  }),
                  createVNode("p", { class: "text-red-600 dark:text-red-400 mb-4" }, toDisplayString(error.value), 1),
                  createVNode("button", {
                    onClick: refreshFeed,
                    class: "px-6 py-2 bg-vikinger-purple text-white rounded-full hover:bg-vikinger-purple/90 transition-colors"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:arrow-sync-24-regular",
                      class: "w-4 h-4 inline mr-2"
                    }),
                    createTextVNode(" \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 ")
                  ])
                ])) : isLoading.value ? (openBlock(), createBlock("div", {
                  key: 1,
                  class: "space-y-4"
                }, [
                  (openBlock(), createBlock(Fragment, null, renderList(3, (i) => {
                    return createVNode("div", {
                      key: i,
                      class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-6 animate-pulse"
                    }, [
                      createVNode("div", { class: "flex items-center gap-4 mb-4" }, [
                        createVNode("div", { class: "w-12 h-12 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full" }),
                        createVNode("div", { class: "flex-1 space-y-2" }, [
                          createVNode("div", { class: "h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/4" }),
                          createVNode("div", { class: "h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/6" })
                        ])
                      ]),
                      createVNode("div", { class: "space-y-2" }, [
                        createVNode("div", { class: "h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-full" }),
                        createVNode("div", { class: "h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4" })
                      ])
                    ]);
                  }), 64))
                ])) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "space-y-4"
                }, [
                  createVNode("div", { class: "flex justify-center" }, [
                    createVNode("button", {
                      onClick: refreshFeed,
                      class: "flex items-center gap-2 px-4 py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-full transition-colors"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:arrow-sync-24-regular",
                        class: "w-4 h-4"
                      }),
                      createTextVNode(" \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A\u0E1F\u0E35\u0E14 ")
                    ])
                  ]),
                  (openBlock(true), createBlock(Fragment, null, renderList(activities.value, (activity) => {
                    return openBlock(), createBlock(FeedPost, {
                      key: activity.id,
                      post: activity,
                      onDeleteSuccess: handleDeleteActivity,
                      onPostUpdated: handlePostUpdated
                    }, null, 8, ["post"]);
                  }), 128)),
                  hasMorePages.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    ref_key: "loadMoreTrigger",
                    ref: loadMoreTrigger,
                    class: "flex justify-center py-6"
                  }, [
                    isLoadingMore.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "flex items-center gap-2 px-6 py-3 bg-white dark:bg-vikinger-dark-200 text-vikinger-purple rounded-full shadow-sm"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:spinner-ios-20-regular",
                        class: "w-5 h-5 animate-spin"
                      }),
                      createTextVNode(" \u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14... ")
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "h-4 w-full"
                    }))
                  ], 512)) : activities.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "text-center py-6 text-gray-500 dark:text-gray-400"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:checkmark-circle-24-regular",
                      class: "w-8 h-8 mx-auto mb-2 text-green-500"
                    }),
                    createVNode("p", null, "\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E14\u0E39\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E41\u0E25\u0E49\u0E27!")
                  ])) : createCommentVNode("", true),
                  activities.value.length === 0 && !error.value ? (openBlock(), createBlock("div", {
                    key: 2,
                    class: "bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:document-text-24-regular",
                      class: "w-16 h-16 mx-auto mb-4 text-gray-400"
                    }),
                    createVNode("h3", { class: "text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2" }, " \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E1E\u0E2A\u0E15\u0E4C "),
                    createVNode("p", { class: "text-gray-500 dark:text-gray-400" }, " \u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E41\u0E0A\u0E23\u0E4C\u0E2A\u0E34\u0E48\u0E07\u0E14\u0E35\u0E46 \u0E01\u0E31\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E46 \u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49! ")
                  ])) : createCommentVNode("", true)
                ]))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Newsfeed.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Newsfeed = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-73c5e4e8"]]);

export { Newsfeed as default };
//# sourceMappingURL=Newsfeed-BgpCwk4i.mjs.map
