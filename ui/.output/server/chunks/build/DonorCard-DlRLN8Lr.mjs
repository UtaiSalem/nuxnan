import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, i as useApi, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "DonorCard",
  __ssrInlineRender: true,
  props: {
    donate: {}
  },
  emits: ["friendRequestSent"],
  setup(__props, { emit: __emit }) {
    useApi();
    const authStore = useAuthStore();
    const config = useRuntimeConfig();
    const props = __props;
    const isSendingRequest = ref(false);
    const requestSent = ref(false);
    const donorAvatar = computed(() => {
      var _a;
      const avatar = (_a = props.donate.donor) == null ? void 0 : _a.avatar;
      if (!avatar || avatar.includes("/storages/")) {
        return `${config.public.apiBase}/storage/images/plearnd-logo.png`;
      }
      return avatar;
    });
    const shouldShowAddFriend = () => {
      var _a;
      if (!props.donate.donor) return false;
      if (!authStore.isAuthenticated) return true;
      if (((_a = authStore.user) == null ? void 0 : _a.id) === props.donate.donor.id) return false;
      return true;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-gradient-to-br from-white to-indigo-50 dark:from-gray-800 dark:to-gray-700 border border-indigo-200 dark:border-gray-600 rounded-2xl hover:shadow-2xl hover:shadow-indigo-300 dark:hover:shadow-gray-900 transform hover:scale-105 transition-all duration-300 overflow-hidden group h-full flex flex-col" }, _attrs))} data-v-0f36fc22><div class="h-2 bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600 shrink-0" data-v-0f36fc22></div><div class="flex flex-col justify-between flex-1 p-5 pb-20 rounded-b-2xl" data-v-0f36fc22><figure class="flex items-center p-3 mb-3 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-xl shadow-sm" data-v-0f36fc22><div class="flex-shrink-0 relative" data-v-0f36fc22><img class="w-20 h-20 rounded-full border-4 border-white shadow-lg transform hover:scale-110 transition-transform duration-300"${ssrRenderAttr("src", donorAvatar.value)}${ssrRenderAttr("alt", __props.donate.donor ? __props.donate.donor.username + " photo" : "donor-image")} data-v-0f36fc22><div class="absolute -bottom-1 -right-1 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full p-1 animate-pulse-slow" data-v-0f36fc22>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:check-decagram",
        class: "w-5 h-5 text-white"
      }, null, _parent));
      _push(`</div></div><div class="w-full ps-4" data-v-0f36fc22><div class="flex flex-col mb-2 text-sm" data-v-0f36fc22><span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400" data-v-0f36fc22>${ssrInterpolate(__props.donate.donor ? __props.donate.donor_name : "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E19\u0E32\u0E21")}</span><span class="font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-1" data-v-0f36fc22>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:identifier",
        class: "w-3 h-3"
      }, null, _parent));
      _push(` ${ssrInterpolate(__props.donate.donor ? __props.donate.donor.personal_code : "")}</span></div><div class="flex flex-wrap gap-2" data-v-0f36fc22>`);
      if (__props.donate.donor && __props.donate.donor.reference_code) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/auth?tab=register&ref=${__props.donate.donor.reference_code}`,
          class: "inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-teal-400 to-emerald-500 rounded-lg hover:from-teal-500 hover:to-emerald-600 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-lg"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:account-star",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-0f36fc22${_scopeId}>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E15\u0E48\u0E2D</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:account-star",
                  class: "w-4 h-4"
                }),
                createVNode("span", null, "\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E15\u0E48\u0E2D")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (shouldShowAddFriend() && !requestSent.value) {
        _push(`<button${ssrIncludeBooleanAttr(isSendingRequest.value) ? " disabled" : ""} class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg hover:from-blue-600 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed" data-v-0f36fc22>`);
        if (isSendingRequest.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:loading",
            class: "w-4 h-4 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-plus",
            class: "w-4 h-4"
          }, null, _parent));
        }
        _push(`<span data-v-0f36fc22>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</span></button>`);
      } else {
        _push(`<!---->`);
      }
      if (requestSent.value) {
        _push(`<span class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg" data-v-0f36fc22>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:check",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span data-v-0f36fc22>\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E41\u0E25\u0E49\u0E27</span></span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></figure><div class="mt-4 p-4 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl border border-yellow-200" data-v-0f36fc22><p class="text-center text-sm font-semibold text-gray-600 mb-2 flex items-center justify-center gap-1" data-v-0f36fc22>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:gift",
        class: "w-4 h-4 text-pink-500 animate-bounce-slow"
      }, null, _parent));
      _push(`<span data-v-0f36fc22>\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</span></p><div class="flex items-center justify-center gap-2 flex-wrap" data-v-0f36fc22><div class="flex items-baseline gap-1" data-v-0f36fc22>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:star-circle",
        class: "w-6 h-6 text-yellow-500 animate-pulse-slow"
      }, null, _parent));
      _push(`<span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-yellow-500 to-orange-500" data-v-0f36fc22>${ssrInterpolate(__props.donate.total_points.toLocaleString())}</span></div><span class="text-gray-400 font-bold" data-v-0f36fc22>/</span><div class="flex flex-col items-start" data-v-0f36fc22><span class="text-xs text-blue-600 font-medium" data-v-0f36fc22>\u0E04\u0E07\u0E40\u0E2B\u0E25\u0E37\u0E2D</span><span class="text-lg font-bold text-green-600" data-v-0f36fc22>${ssrInterpolate(__props.donate.remaining_points)}</span></div><span class="text-sm text-gray-600 font-medium" data-v-0f36fc22>\u0E41\u0E15\u0E49\u0E21</span></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/DonorCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const DonorCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-0f36fc22"]]);

export { DonorCard as D };
//# sourceMappingURL=DonorCard-DlRLN8Lr.mjs.map
