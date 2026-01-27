import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, unref, withCtx, createVNode, createTextVNode, toDisplayString, createBlock, openBlock, Fragment, renderList, computed, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, d as useAuthStore, _ as _export_sfc } from './server.mjs';
import { u as useRewards } from './useRewards-lE9TTj1H.mjs';
import { _ as _sfc_main$2 } from './BaseCard-Baxif1fS.mjs';
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

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "RewardsDisplay",
  __ssrInlineRender: true,
  props: {
    showMyRewards: {
      type: Boolean,
      default: true
    }
  },
  setup(__props) {
    const {
      rewards,
      userRewards,
      points,
      isLoading,
      canRedeem,
      getTypeLabel,
      getTypeIcon,
      getStatusLabel,
      getStatusColor,
      formatPoints
    } = useRewards();
    const activeTab = ref("all");
    const tabs = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "mdi:view-grid" },
      { value: "wallet", label: "\u0E40\u0E07\u0E34\u0E19", icon: "mdi:wallet" },
      { value: "badge", label: "\u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D\u0E15\u0E23\u0E32", icon: "mdi:medal" },
      { value: "feature", label: "\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C", icon: "mdi:star-shooting" },
      { value: "discount", label: "\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14", icon: "mdi:percent" }
    ];
    const showConfirmModal = ref(false);
    const showSuccessModal = ref(false);
    const selectedReward = ref(null);
    const isRedeeming = ref(false);
    const redeemingId = ref(null);
    const filteredRewards = computed(() => {
      if (activeTab.value === "all") {
        return rewards.value;
      }
      return rewards.value.filter((r) => r.type === activeTab.value);
    });
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "rewards-display" }, _attrs))} data-v-ca90b7b8><div class="rewards-header mb-6" data-v-ca90b7b8><div class="flex items-center justify-between" data-v-ca90b7b8><div data-v-ca90b7b8><h2 class="text-2xl font-bold text-gray-900 dark:text-white" data-v-ca90b7b8>\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h2><p class="text-gray-500 dark:text-gray-400" data-v-ca90b7b8>\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23</p></div><div class="points-badge bg-gradient-to-r from-amber-400 to-orange-500 text-white px-4 py-2 rounded-xl shadow-lg" data-v-ca90b7b8><span class="text-sm" data-v-ca90b7b8>\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</span><p class="text-2xl font-bold" data-v-ca90b7b8>${ssrInterpolate(unref(formatPoints)(unref(points)))}</p></div></div></div><div class="filter-tabs mb-6" data-v-ca90b7b8><div class="flex gap-2 overflow-x-auto pb-2" data-v-ca90b7b8><!--[-->`);
      ssrRenderList(tabs, (tab) => {
        _push(`<button class="${ssrRenderClass([activeTab.value === tab.value ? "bg-primary-500 text-white shadow-md" : "bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700", "tab-btn px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap"])}" data-v-ca90b7b8>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-4 h-4 inline mr-1"
        }, null, _parent));
        _push(` ${ssrInterpolate(tab.label)}</button>`);
      });
      _push(`<!--]--></div></div>`);
      if (unref(isLoading)) {
        _push(`<div class="loading-state py-12" data-v-ca90b7b8><div class="flex flex-col items-center" data-v-ca90b7b8>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:loading",
          class: "w-12 h-12 text-primary-500 animate-spin"
        }, null, _parent));
        _push(`<p class="mt-4 text-gray-500 dark:text-gray-400" data-v-ca90b7b8>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25...</p></div></div>`);
      } else if (filteredRewards.value.length > 0) {
        _push(`<div class="rewards-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-v-ca90b7b8><!--[-->`);
        ssrRenderList(filteredRewards.value, (reward) => {
          _push(`<div class="${ssrRenderClass([{ "opacity-60": !unref(canRedeem)(reward) }, "reward-card bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden transition-all hover:shadow-lg hover:-translate-y-1"])}" data-v-ca90b7b8><div class="reward-image h-32 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center relative" data-v-ca90b7b8>`);
          if (reward.image) {
            _push(`<img${ssrRenderAttr("src", reward.image)}${ssrRenderAttr("alt", reward.name)} class="w-full h-full object-cover" data-v-ca90b7b8>`);
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: unref(getTypeIcon)(reward.type),
              class: "w-16 h-16 text-white"
            }, null, _parent));
          }
          if (reward.is_limited) {
            _push(`<div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full" data-v-ca90b7b8> \u0E08\u0E33\u0E01\u0E31\u0E14\u0E08\u0E33\u0E19\u0E27\u0E19 </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="absolute top-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded-full" data-v-ca90b7b8>${ssrInterpolate(unref(getTypeLabel)(reward.type))}</div></div><div class="reward-info p-4" data-v-ca90b7b8><h3 class="reward-name text-lg font-semibold text-gray-900 dark:text-white mb-1" data-v-ca90b7b8>${ssrInterpolate(reward.name)}</h3><p class="reward-description text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2" data-v-ca90b7b8>${ssrInterpolate(reward.description)}</p><div class="flex items-center justify-between mb-3" data-v-ca90b7b8><div class="points-required flex items-center gap-1" data-v-ca90b7b8>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:star-circle",
            class: "w-5 h-5 text-amber-500"
          }, null, _parent));
          _push(`<span class="text-lg font-bold text-gray-900 dark:text-white" data-v-ca90b7b8>${ssrInterpolate(unref(formatPoints)(reward.points_required))}</span><span class="text-sm text-gray-500" data-v-ca90b7b8>\u0E41\u0E15\u0E49\u0E21</span></div>`);
          if (reward.is_limited) {
            _push(`<div class="text-sm text-gray-500 dark:text-gray-400" data-v-ca90b7b8> \u0E40\u0E2B\u0E25\u0E37\u0E2D ${ssrInterpolate(reward.quantity_available - reward.quantity_redeemed)} \u0E0A\u0E34\u0E49\u0E19 </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          if (reward.type === "wallet") {
            _push(`<div class="value-badge bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300 text-sm px-3 py-1 rounded-full inline-block mb-3" data-v-ca90b7b8> \u0E23\u0E31\u0E1A \u0E3F${ssrInterpolate(reward.value)} \u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32 </div>`);
          } else if (reward.type === "discount") {
            _push(`<div class="value-badge bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300 text-sm px-3 py-1 rounded-full inline-block mb-3" data-v-ca90b7b8> \u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14 ${ssrInterpolate(reward.value)}% </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="${ssrRenderClass([unref(canRedeem)(reward) ? "bg-gradient-to-r from-primary-500 to-primary-600 text-white hover:shadow-md" : "bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed", "redeem-btn w-full py-2 rounded-xl font-medium transition-all"])}"${ssrIncludeBooleanAttr(!unref(canRedeem)(reward) || isRedeeming.value) ? " disabled" : ""} data-v-ca90b7b8>`);
          if (isRedeeming.value && redeemingId.value === reward.id) {
            _push(`<span data-v-ca90b7b8>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-5 h-5 animate-spin inline"
            }, null, _parent));
            _push(` \u0E01\u0E33\u0E25\u0E31\u0E07\u0E41\u0E25\u0E01... </span>`);
          } else if (unref(points) < reward.points_required) {
            _push(`<span data-v-ca90b7b8> \u0E41\u0E15\u0E49\u0E21\u0E44\u0E21\u0E48\u0E1E\u0E2D (\u0E02\u0E32\u0E14 ${ssrInterpolate(unref(formatPoints)(reward.points_required - unref(points)))}) </span>`);
          } else if (!reward.is_active) {
            _push(`<span data-v-ca90b7b8> \u0E44\u0E21\u0E48\u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E43\u0E2B\u0E49\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23 </span>`);
          } else if (reward.is_limited && reward.quantity_redeemed >= reward.quantity_available) {
            _push(`<span data-v-ca90b7b8> \u0E2B\u0E21\u0E14\u0E41\u0E25\u0E49\u0E27 </span>`);
          } else {
            _push(`<span data-v-ca90b7b8>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:gift",
              class: "w-5 h-5 inline mr-1"
            }, null, _parent));
            _push(` \u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 </span>`);
          }
          _push(`</button></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="empty-state py-12 text-center" data-v-ca90b7b8>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:gift-off",
          class: "w-16 h-16 mx-auto text-gray-300 dark:text-gray-600"
        }, null, _parent));
        _push(`<h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white" data-v-ca90b7b8>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h3><p class="mt-2 text-gray-500 dark:text-gray-400" data-v-ca90b7b8>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E43\u0E19\u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48\u0E19\u0E35\u0E49</p></div>`);
      }
      if (__props.showMyRewards) {
        _push(`<div class="my-rewards-section mt-8" data-v-ca90b7b8><div class="section-header mb-4" data-v-ca90b7b8><h3 class="text-xl font-bold text-gray-900 dark:text-white" data-v-ca90b7b8>\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-ca90b7b8>\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E41\u0E25\u0E01\u0E44\u0E27\u0E49</p></div>`);
        if (unref(userRewards).length > 0) {
          _push(`<div class="user-rewards-list space-y-3" data-v-ca90b7b8><!--[-->`);
          ssrRenderList(unref(userRewards), (userReward) => {
            _push(`<div class="user-reward-card bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm flex items-center gap-4" data-v-ca90b7b8><div class="reward-icon w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center flex-shrink-0" data-v-ca90b7b8>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: unref(getTypeIcon)(userReward.reward.type),
              class: "w-6 h-6 text-white"
            }, null, _parent));
            _push(`</div><div class="reward-info flex-grow" data-v-ca90b7b8><h4 class="font-semibold text-gray-900 dark:text-white" data-v-ca90b7b8>${ssrInterpolate(userReward.reward.name)}</h4><p class="text-sm text-gray-500 dark:text-gray-400" data-v-ca90b7b8> \u0E41\u0E25\u0E01\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(formatDate(userReward.redeemed_at))}</p></div><div class="status-badge" data-v-ca90b7b8><span class="${ssrRenderClass([unref(getStatusColor)(userReward.status), "px-3 py-1 rounded-full text-xs font-medium"])}" data-v-ca90b7b8>${ssrInterpolate(unref(getStatusLabel)(userReward.status))}</span></div><div class="actions" data-v-ca90b7b8>`);
            if (userReward.status === "pending") {
              _push(`<button class="text-primary-500 hover:text-primary-600 font-medium text-sm" data-v-ca90b7b8> \u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 </button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="text-center py-8 text-gray-500 dark:text-gray-400" data-v-ca90b7b8>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:inbox-outline",
            class: "w-12 h-12 mx-auto mb-3"
          }, null, _parent));
          _push(`<p data-v-ca90b7b8>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E41\u0E25\u0E01\u0E44\u0E27\u0E49</p></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        var _a, _b, _c;
        if (showConfirmModal.value) {
          _push2(`<div class="modal-overlay fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" data-v-ca90b7b8><div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" data-v-ca90b7b8><div class="text-center" data-v-ca90b7b8><div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center" data-v-ca90b7b8>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:gift",
            class: "w-8 h-8 text-white"
          }, null, _parent));
          _push2(`</div><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" data-v-ca90b7b8>\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h3><p class="text-gray-500 dark:text-gray-400 mb-4" data-v-ca90b7b8> \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E41\u0E25\u0E01 <strong class="text-gray-900 dark:text-white" data-v-ca90b7b8>${ssrInterpolate((_a = selectedReward.value) == null ? void 0 : _a.name)}</strong> \u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48? </p><div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 mb-6" data-v-ca90b7b8><div class="flex justify-between items-center" data-v-ca90b7b8><span class="text-gray-600 dark:text-gray-400" data-v-ca90b7b8>\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21</span><span class="text-lg font-bold text-amber-500" data-v-ca90b7b8>${ssrInterpolate(unref(formatPoints)(((_b = selectedReward.value) == null ? void 0 : _b.points_required) || 0))}</span></div><div class="flex justify-between items-center mt-2" data-v-ca90b7b8><span class="text-gray-600 dark:text-gray-400" data-v-ca90b7b8>\u0E41\u0E15\u0E49\u0E21\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D</span><span class="text-lg font-bold text-gray-900 dark:text-white" data-v-ca90b7b8>${ssrInterpolate(unref(formatPoints)(unref(points) - (((_c = selectedReward.value) == null ? void 0 : _c.points_required) || 0)))}</span></div></div><div class="flex gap-3" data-v-ca90b7b8><button class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" data-v-ca90b7b8> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 py-2 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium hover:shadow-md transition-all"${ssrIncludeBooleanAttr(isRedeeming.value) ? " disabled" : ""} data-v-ca90b7b8>`);
          if (isRedeeming.value) {
            _push2(`<span data-v-ca90b7b8>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-5 h-5 animate-spin inline"
            }, null, _parent));
            _push2(`</span>`);
          } else {
            _push2(`<span data-v-ca90b7b8>\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19</span>`);
          }
          _push2(`</button></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (showSuccessModal.value) {
          _push2(`<div class="modal-overlay fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" data-v-ca90b7b8><div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" data-v-ca90b7b8><div class="text-center" data-v-ca90b7b8><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center animate-bounce" data-v-ca90b7b8>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:check",
            class: "w-10 h-10 text-white"
          }, null, _parent));
          _push2(`</div><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" data-v-ca90b7b8>\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!</h3><p class="text-gray-500 dark:text-gray-400 mb-6" data-v-ca90b7b8> \u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A <strong class="text-gray-900 dark:text-white" data-v-ca90b7b8>${ssrInterpolate((_a = selectedReward.value) == null ? void 0 : _a.name)}</strong> \u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27 </p><button class="w-full py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium hover:shadow-md transition-all" data-v-ca90b7b8> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E40\u0E25\u0E22! </button></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/rewards/RewardsDisplay.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const RewardsDisplay = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-ca90b7b8"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Rewards",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 - Nuxni"
    });
    const authStore = useAuthStore();
    const {
      userRewards,
      points,
      isLoading,
      formatPoints
    } = useRewards();
    const stats = ref({
      total_rewards: 0,
      total_redeemed: 0,
      total_points_spent: 0,
      rewards_by_type: {}
    });
    const activeView = ref("rewards");
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-6xl mx-auto"><div class="text-center mb-8"><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-purple-400 to-pink-600 rounded-full flex items-center justify-center shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:gift",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"> \u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto"> \u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E21\u0E32\u0E01\u0E21\u0E32\u0E22 \u0E17\u0E31\u0E49\u0E07\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32 \u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D\u0E15\u0E23\u0E32 \u0E41\u0E25\u0E30\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E1E\u0E34\u0E40\u0E28\u0E29 </p></div>`);
      if (!unref(authStore).isAuthenticated) {
        _push(`<div class="text-center py-12">`);
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:account-lock",
                class: "w-16 h-16 mx-auto text-gray-400 mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A</h2><p class="text-gray-500 dark:text-gray-400 mb-4"${_scopeId}>\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E14\u0E39\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E41\u0E25\u0E30\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</p>`);
              _push2(ssrRenderComponent(_component_NuxtLink, {
                to: "/auth",
                class: "inline-flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:opacity-90 transition-opacity"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:login",
                      class: "w-5 h-5"
                    }, null, _parent3, _scopeId2));
                    _push3(` \u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A `);
                  } else {
                    return [
                      createVNode(unref(Icon), {
                        icon: "mdi:login",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:account-lock",
                  class: "w-16 h-16 mx-auto text-gray-400 mb-4"
                }),
                createVNode("h2", { class: "text-xl font-semibold text-gray-900 dark:text-white mb-2" }, "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A"),
                createVNode("p", { class: "text-gray-500 dark:text-gray-400 mb-4" }, "\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E14\u0E39\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E41\u0E25\u0E30\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"),
                createVNode(_component_NuxtLink, {
                  to: "/auth",
                  class: "inline-flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:opacity-90 transition-opacity"
                }, {
                  default: withCtx(() => [
                    createVNode(unref(Icon), {
                      icon: "mdi:login",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A ")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<div><div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">`);
        _push(ssrRenderComponent(_sfc_main$2, { class: "bg-gradient-to-br from-amber-400 to-orange-500 border-0" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="text-white"${_scopeId}><div class="flex items-center gap-2 mb-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:star-circle",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
              _push2(`<span class="text-sm font-medium opacity-80"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</span></div><p class="text-2xl lg:text-3xl font-bold"${_scopeId}>${ssrInterpolate(unref(formatPoints)(unref(points)))}</p></div>`);
            } else {
              return [
                createVNode("div", { class: "text-white" }, [
                  createVNode("div", { class: "flex items-center gap-2 mb-2" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:star-circle",
                      class: "w-6 h-6"
                    }),
                    createVNode("span", { class: "text-sm font-medium opacity-80" }, "\u0E41\u0E15\u0E49\u0E21\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19")
                  ]),
                  createVNode("p", { class: "text-2xl lg:text-3xl font-bold" }, toDisplayString(unref(formatPoints)(unref(points))), 1)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-3"${_scopeId}><div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:gift-outline",
                class: "w-6 h-6 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.total_rewards)}</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-3" }, [
                  createVNode("div", { class: "w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:gift-outline",
                      class: "w-6 h-6 text-purple-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.total_rewards), 1)
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-3"${_scopeId}><div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:check-circle",
                class: "w-6 h-6 text-green-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E25\u0E01\u0E44\u0E1B\u0E41\u0E25\u0E49\u0E27</p><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(unref(userRewards).length)}</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-3" }, [
                  createVNode("div", { class: "w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:check-circle",
                      class: "w-6 h-6 text-green-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E25\u0E01\u0E44\u0E1B\u0E41\u0E25\u0E49\u0E27"),
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(unref(userRewards).length), 1)
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_sfc_main$2, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex items-center gap-3"${_scopeId}><div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:trending-down",
                class: "w-6 h-6 text-blue-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><div${_scopeId}><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49\u0E44\u0E1B</p><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(unref(formatPoints)(stats.value.total_points_spent))}</p></div></div>`);
            } else {
              return [
                createVNode("div", { class: "flex items-center gap-3" }, [
                  createVNode("div", { class: "w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:trending-down",
                      class: "w-6 h-6 text-blue-500"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E43\u0E0A\u0E49\u0E44\u0E1B"),
                    createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(unref(formatPoints)(stats.value.total_points_spent)), 1)
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div><div class="flex gap-4 mb-6"><button class="${ssrRenderClass([activeView.value === "rewards" ? "bg-primary-500 text-white shadow-md" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700", "px-6 py-2 rounded-xl font-medium transition-all"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:gift",
          class: "w-5 h-5 inline mr-2"
        }, null, _parent));
        _push(` \u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25 </button><button class="${ssrRenderClass([activeView.value === "my-rewards" ? "bg-primary-500 text-white shadow-md" : "bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700", "px-6 py-2 rounded-xl font-medium transition-all relative"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:account-check",
          class: "w-5 h-5 inline mr-2"
        }, null, _parent));
        _push(` \u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 `);
        if (unref(userRewards).length > 0) {
          _push(`<span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">${ssrInterpolate(unref(userRewards).length)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button></div>`);
        if (activeView.value === "rewards") {
          _push(`<div>`);
          _push(ssrRenderComponent(RewardsDisplay, { "show-my-rewards": false }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<div>`);
          _push(ssrRenderComponent(_sfc_main$2, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4"${_scopeId}>\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</h3>`);
                if (unref(isLoading)) {
                  _push2(`<div class="py-12 text-center"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:loading",
                    class: "w-12 h-12 text-primary-500 animate-spin mx-auto"
                  }, null, _parent2, _scopeId));
                  _push2(`<p class="mt-4 text-gray-500 dark:text-gray-400"${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
                } else if (unref(userRewards).length > 0) {
                  _push2(`<div class="space-y-4"${_scopeId}><!--[-->`);
                  ssrRenderList(unref(userRewards), (userReward) => {
                    _push2(`<div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl"${_scopeId}><div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center flex-shrink-0"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "mdi:gift",
                      class: "w-7 h-7 text-white"
                    }, null, _parent2, _scopeId));
                    _push2(`</div><div class="flex-grow"${_scopeId}><h4 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(userReward.reward.name)}</h4><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(userReward.reward.description)}</p><p class="text-xs text-gray-400 dark:text-gray-500 mt-1"${_scopeId}> \u0E41\u0E25\u0E01\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(new Date(userReward.redeemed_at).toLocaleDateString("th-TH"))}</p></div><div class="text-right"${_scopeId}><p class="text-lg font-bold text-amber-500"${_scopeId}>-${ssrInterpolate(unref(formatPoints)(userReward.points_spent))}</p><p class="text-xs text-gray-500"${_scopeId}>\u0E41\u0E15\u0E49\u0E21</p></div><div${_scopeId}><span class="${ssrRenderClass([{
                      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300": userReward.status === "pending",
                      "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300": userReward.status === "claimed",
                      "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300": userReward.status === "used",
                      "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300": userReward.status === "expired",
                      "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300": userReward.status === "cancelled"
                    }, "px-3 py-1 rounded-full text-xs font-medium"])}"${_scopeId}>${ssrInterpolate(userReward.status === "pending" ? "\u0E23\u0E2D\u0E23\u0E31\u0E1A" : userReward.status === "claimed" ? "\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27" : userReward.status === "used" ? "\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27" : userReward.status === "expired" ? "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38" : userReward.status === "cancelled" ? "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01" : userReward.status)}</span></div></div>`);
                  });
                  _push2(`<!--]--></div>`);
                } else {
                  _push2(`<div class="py-12 text-center"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:gift-off",
                    class: "w-16 h-16 mx-auto text-gray-300 dark:text-gray-600"
                  }, null, _parent2, _scopeId));
                  _push2(`<h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h3><p class="mt-2 text-gray-500 dark:text-gray-400"${_scopeId}>\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E43\u0E14\u0E46</p><button class="mt-4 px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:shadow-md transition-all"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:gift",
                    class: "w-5 h-5 inline mr-2"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E14\u0E39\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E41\u0E25\u0E01\u0E44\u0E14\u0E49 </button></div>`);
                }
                _push2(`</div>`);
              } else {
                return [
                  createVNode("div", { class: "p-2" }, [
                    createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-4" }, "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19"),
                    unref(isLoading) ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "py-12 text-center"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:loading",
                        class: "w-12 h-12 text-primary-500 animate-spin mx-auto"
                      }),
                      createVNode("p", { class: "mt-4 text-gray-500 dark:text-gray-400" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...")
                    ])) : unref(userRewards).length > 0 ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "space-y-4"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(unref(userRewards), (userReward) => {
                        return openBlock(), createBlock("div", {
                          key: userReward.id,
                          class: "flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl"
                        }, [
                          createVNode("div", { class: "w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-500 rounded-xl flex items-center justify-center flex-shrink-0" }, [
                            createVNode(unref(Icon), {
                              icon: "mdi:gift",
                              class: "w-7 h-7 text-white"
                            })
                          ]),
                          createVNode("div", { class: "flex-grow" }, [
                            createVNode("h4", { class: "font-semibold text-gray-900 dark:text-white" }, toDisplayString(userReward.reward.name), 1),
                            createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, toDisplayString(userReward.reward.description), 1),
                            createVNode("p", { class: "text-xs text-gray-400 dark:text-gray-500 mt-1" }, " \u0E41\u0E25\u0E01\u0E40\u0E21\u0E37\u0E48\u0E2D " + toDisplayString(new Date(userReward.redeemed_at).toLocaleDateString("th-TH")), 1)
                          ]),
                          createVNode("div", { class: "text-right" }, [
                            createVNode("p", { class: "text-lg font-bold text-amber-500" }, "-" + toDisplayString(unref(formatPoints)(userReward.points_spent)), 1),
                            createVNode("p", { class: "text-xs text-gray-500" }, "\u0E41\u0E15\u0E49\u0E21")
                          ]),
                          createVNode("div", null, [
                            createVNode("span", {
                              class: ["px-3 py-1 rounded-full text-xs font-medium", {
                                "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300": userReward.status === "pending",
                                "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300": userReward.status === "claimed",
                                "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300": userReward.status === "used",
                                "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300": userReward.status === "expired",
                                "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300": userReward.status === "cancelled"
                              }]
                            }, toDisplayString(userReward.status === "pending" ? "\u0E23\u0E2D\u0E23\u0E31\u0E1A" : userReward.status === "claimed" ? "\u0E23\u0E31\u0E1A\u0E41\u0E25\u0E49\u0E27" : userReward.status === "used" ? "\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27" : userReward.status === "expired" ? "\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38" : userReward.status === "cancelled" ? "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01" : userReward.status), 3)
                          ])
                        ]);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 2,
                      class: "py-12 text-center"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:gift-off",
                        class: "w-16 h-16 mx-auto text-gray-300 dark:text-gray-600"
                      }),
                      createVNode("h3", { class: "mt-4 text-lg font-medium text-gray-900 dark:text-white" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"),
                      createVNode("p", { class: "mt-2 text-gray-500 dark:text-gray-400" }, "\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E43\u0E14\u0E46"),
                      createVNode("button", {
                        class: "mt-4 px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:shadow-md transition-all",
                        onClick: ($event) => activeView.value = "rewards"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:gift",
                          class: "w-5 h-5 inline mr-2"
                        }),
                        createTextVNode(" \u0E14\u0E39\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E41\u0E25\u0E01\u0E44\u0E14\u0E49 ")
                      ], 8, ["onClick"])
                    ]))
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        }
        _push(ssrRenderComponent(_sfc_main$2, { class: "mt-8" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="p-2"${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 text-center"${_scopeId}>\u0E27\u0E34\u0E18\u0E35\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h3><div class="grid grid-cols-1 md:grid-cols-3 gap-6"${_scopeId}><div class="text-center"${_scopeId}><div class="w-16 h-16 mx-auto mb-4 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:star-circle",
                class: "w-8 h-8 text-amber-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><h4 class="font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}>1. \u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21</h4><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}> \u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E42\u0E15\u0E49\u0E15\u0E2D\u0E1A\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E40\u0E0A\u0E48\u0E19 \u0E23\u0E31\u0E1A\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 \u0E16\u0E39\u0E01\u0E01\u0E14\u0E44\u0E25\u0E04\u0E4C \u0E41\u0E25\u0E30\u0E2D\u0E37\u0E48\u0E19\u0E46 </p></div><div class="text-center"${_scopeId}><div class="w-16 h-16 mx-auto mb-4 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:gift-open",
                class: "w-8 h-8 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><h4 class="font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}>2. \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h4><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E08\u0E32\u0E01\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E21\u0E32\u0E01\u0E21\u0E32\u0E22 \u0E17\u0E31\u0E49\u0E07\u0E40\u0E07\u0E34\u0E19 \u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D \u0E41\u0E25\u0E30\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E1E\u0E34\u0E40\u0E28\u0E29 </p></div><div class="text-center"${_scopeId}><div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:check-decagram",
                class: "w-8 h-8 text-green-500"
              }, null, _parent2, _scopeId));
              _push2(`</div><h4 class="font-semibold text-gray-900 dark:text-white mb-2"${_scopeId}>3. \u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25</h4><p class="text-sm text-gray-500 dark:text-gray-400"${_scopeId}> \u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E31\u0E19\u0E17\u0E35! \u0E40\u0E07\u0E34\u0E19\u0E08\u0E30\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E31\u0E1A\u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D\u0E15\u0E23\u0E32\u0E41\u0E25\u0E30\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E1E\u0E34\u0E40\u0E28\u0E29 </p></div></div></div>`);
            } else {
              return [
                createVNode("div", { class: "p-2" }, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6 text-center" }, "\u0E27\u0E34\u0E18\u0E35\u0E41\u0E25\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"),
                  createVNode("div", { class: "grid grid-cols-1 md:grid-cols-3 gap-6" }, [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-16 h-16 mx-auto mb-4 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:star-circle",
                          class: "w-8 h-8 text-amber-500"
                        })
                      ]),
                      createVNode("h4", { class: "font-semibold text-gray-900 dark:text-white mb-2" }, "1. \u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21"),
                      createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, " \u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E42\u0E15\u0E49\u0E15\u0E2D\u0E1A\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E40\u0E0A\u0E48\u0E19 \u0E23\u0E31\u0E1A\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 \u0E16\u0E39\u0E01\u0E01\u0E14\u0E44\u0E25\u0E04\u0E4C \u0E41\u0E25\u0E30\u0E2D\u0E37\u0E48\u0E19\u0E46 ")
                    ]),
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-16 h-16 mx-auto mb-4 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:gift-open",
                          class: "w-8 h-8 text-purple-500"
                        })
                      ]),
                      createVNode("h4", { class: "font-semibold text-gray-900 dark:text-white mb-2" }, "2. \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"),
                      createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, " \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E08\u0E32\u0E01\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E21\u0E32\u0E01\u0E21\u0E32\u0E22 \u0E17\u0E31\u0E49\u0E07\u0E40\u0E07\u0E34\u0E19 \u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D \u0E41\u0E25\u0E30\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E1E\u0E34\u0E40\u0E28\u0E29 ")
                    ]),
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:check-decagram",
                          class: "w-8 h-8 text-green-500"
                        })
                      ]),
                      createVNode("h4", { class: "font-semibold text-gray-900 dark:text-white mb-2" }, "3. \u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25"),
                      createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, " \u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E17\u0E31\u0E19\u0E17\u0E35! \u0E40\u0E07\u0E34\u0E19\u0E08\u0E30\u0E40\u0E02\u0E49\u0E32\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E31\u0E1A\u0E40\u0E2B\u0E23\u0E35\u0E22\u0E0D\u0E15\u0E23\u0E32\u0E41\u0E25\u0E30\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E1E\u0E34\u0E40\u0E28\u0E29 ")
                    ])
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Rewards.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Rewards-CdC7CfCK.mjs.map
