import { ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { i as useApi, b as useRuntimeConfig } from './server.mjs';

const _sfc_main = {
  __name: "ApproveDonateCard",
  __ssrInlineRender: true,
  props: {
    donate: {
      type: Object,
      required: true
    }
  },
  emits: ["approved", "rejected"],
  setup(__props, { emit: __emit }) {
    useApi();
    const config = useRuntimeConfig();
    const props = __props;
    const isLoading = ref(false);
    const showSlipModal = ref(false);
    const slipUrl = computed(() => {
      if (!props.donate.slip) return null;
      if (props.donate.slip.startsWith("http")) return props.donate.slip;
      return `${config.public.apiBase}${props.donate.slip}`;
    });
    const statusInfo = computed(() => {
      switch (props.donate.status) {
        case 0:
          return { text: "\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", class: "bg-yellow-100 text-yellow-800", icon: "mdi:clock-outline" };
        case 1:
          return { text: "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27", class: "bg-green-100 text-green-800", icon: "mdi:check-circle" };
        case 2:
          return { text: "\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18", class: "bg-red-100 text-red-800", icon: "mdi:close-circle" };
        default:
          return { text: "\u0E44\u0E21\u0E48\u0E17\u0E23\u0E32\u0E1A", class: "bg-gray-100 text-gray-800", icon: "mdi:help-circle" };
      }
    });
    const donorAvatar = computed(() => {
      var _a;
      return ((_a = props.donate.donor) == null ? void 0 : _a.avatar) || `${config.public.apiBase}/storage/images/plearnd-logo.png`;
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700" }, _attrs))}><div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"><div class="flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:hand-coin",
        class: "w-5 h-5 text-purple-500"
      }, null, _parent));
      _push(`<span class="font-semibold text-gray-900 dark:text-white">\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 #${ssrInterpolate(__props.donate.id)}</span></div><span class="${ssrRenderClass([statusInfo.value.class, "px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: statusInfo.value.icon,
        class: "w-4 h-4"
      }, null, _parent));
      _push(` ${ssrInterpolate(statusInfo.value.text)}</span></div><div class="p-4 space-y-4"><div class="flex items-center gap-3"><div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex items-center justify-center"><img${ssrRenderAttr("src", donorAvatar.value)}${ssrRenderAttr("alt", __props.donate.donor_name)} class="w-full h-full object-cover"></div><div><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(__props.donate.donor_name || "\u0E44\u0E21\u0E48\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E2D\u0E2D\u0E01\u0E19\u0E32\u0E21")}</p><p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(((_a = __props.donate.donor) == null ? void 0 : _a.email) || __props.donate.donor_email || "-")}</p></div></div><div class="grid grid-cols-2 gap-4"><div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3 text-center"><p class="text-xs text-blue-600 dark:text-blue-400 mb-1">\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19</p><p class="text-lg font-bold text-blue-700 dark:text-blue-300">${ssrInterpolate(__props.donate.amounts)}</p></div><div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-3 text-center"><p class="text-xs text-purple-600 dark:text-purple-400 mb-1">\u0E41\u0E15\u0E49\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p><p class="text-lg font-bold text-purple-700 dark:text-purple-300">${ssrInterpolate(((_b = __props.donate.total_points) == null ? void 0 : _b.toLocaleString()) || 0)}</p></div></div><div class="text-sm space-y-2"><div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19:</span><span class="text-gray-900 dark:text-white">${ssrInterpolate(__props.donate.transfer_date || "-")}</span></div><div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">\u0E40\u0E27\u0E25\u0E32:</span><span class="text-gray-900 dark:text-white">${ssrInterpolate(__props.donate.transfer_time || "-")}</span></div><div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D:</span><span class="text-gray-900 dark:text-white">${ssrInterpolate(__props.donate.diff_humans_created_at)}</span></div></div>`);
      if (slipUrl.value) {
        _push(`<div class="mt-3"><button class="w-full border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-2 hover:border-purple-500 transition-colors"><img${ssrRenderAttr("src", slipUrl.value)} alt="\u0E2A\u0E25\u0E34\u0E1B\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19" class="w-full h-40 object-contain rounded-lg"><p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E14\u0E39\u0E02\u0E19\u0E32\u0E14\u0E43\u0E2B\u0E0D\u0E48</p></button></div>`);
      } else {
        _push(`<div class="mt-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:image-off",
          class: "w-8 h-8 text-gray-400 mx-auto"
        }, null, _parent));
        _push(`<p class="text-sm text-gray-500 dark:text-gray-400 mt-2">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E25\u0E34\u0E1B\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</p></div>`);
      }
      if (__props.donate.notes) {
        _push(`<div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3"><p class="text-xs text-gray-500 dark:text-gray-400 mb-1">\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38:</p><p class="text-sm text-gray-900 dark:text-white">${ssrInterpolate(__props.donate.notes)}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (__props.donate.status === 0) {
        _push(`<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex gap-2"><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white font-medium rounded-lg transition-colors">`);
        if (isLoading.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:loading",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:check",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(` \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 </button><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white font-medium rounded-lg transition-colors">`);
        if (isLoading.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:loading",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:close",
            class: "w-5 h-5"
          }, null, _parent));
        }
        _push(` \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (showSlipModal.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"><div class="relative max-w-4xl max-h-[90vh]"><button class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:close",
            class: "w-8 h-8"
          }, null, _parent));
          _push2(`</button><img${ssrRenderAttr("src", slipUrl.value)} alt="\u0E2A\u0E25\u0E34\u0E1B\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19" class="max-w-full max-h-[85vh] object-contain rounded-lg"></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/earn/donates/ApproveDonateCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=ApproveDonateCard-DYw_Vvyz.mjs.map
