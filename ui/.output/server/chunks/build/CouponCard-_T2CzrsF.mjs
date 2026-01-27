import { defineComponent, ref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { _ as _export_sfc, b as useRuntimeConfig, i as useApi } from './server.mjs';
import { u as useToast } from './useToast-BpzfS75l.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "CouponCard",
  __ssrInlineRender: true,
  props: {
    coupon: {}
  },
  emits: ["cancelled", "printed"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const config = useRuntimeConfig();
    const apiBase = config.public.apiBase;
    useApi();
    useToast();
    const copied = ref(false);
    const statusClass = computed(() => {
      return {
        "status-active": props.coupon.status === "active",
        "status-redeemed": props.coupon.status === "redeemed",
        "status-expired": props.coupon.status === "expired",
        "status-cancelled": props.coupon.status === "cancelled"
      };
    });
    const canCancel = computed(() => {
      return props.coupon.status === "active" && !props.coupon.is_expired;
    });
    const formatAmount = computed(() => {
      return props.coupon.coupon_type === "wallet" ? props.coupon.amount.toFixed(2) : Math.floor(props.coupon.amount).toString();
    });
    const qrCodeUrl = computed(() => {
      return props.coupon.qr_code_path ? `${apiBase}/storage/${props.coupon.qr_code_path}` : "";
    });
    function formatDateShort(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        day: "2-digit",
        month: "short",
        year: "2-digit"
      });
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["coupon-card", statusClass.value]
      }, _attrs))} data-v-4de5cec3><div class="coupon-header" data-v-4de5cec3><div class="type-badge" data-v-4de5cec3><span class="icon" data-v-4de5cec3>${ssrInterpolate(__props.coupon.coupon_type === "points" ? "\u{1F3AF}" : "\u{1F4B0}")}</span><span class="type-label" data-v-4de5cec3>${ssrInterpolate(__props.coupon.type_label)}</span></div><span class="${ssrRenderClass([__props.coupon.status, "status-badge"])}" data-v-4de5cec3>${ssrInterpolate(__props.coupon.status_label)}</span></div><div class="coupon-body" data-v-4de5cec3><div class="body-main" data-v-4de5cec3>`);
      if (__props.coupon.qr_code_path) {
        _push(`<div class="qr-section" data-v-4de5cec3><img${ssrRenderAttr("src", qrCodeUrl.value)} alt="QR Code" class="qr-image" data-v-4de5cec3></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="details-col" data-v-4de5cec3><div class="amount-section" data-v-4de5cec3><span class="amount" data-v-4de5cec3>${ssrInterpolate(formatAmount.value)}</span><span class="unit" data-v-4de5cec3>${ssrInterpolate(__props.coupon.coupon_type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E1A\u0E32\u0E17")}</span></div><div class="code-section" data-v-4de5cec3><div class="code-container" data-v-4de5cec3><code class="coupon-code" data-v-4de5cec3>${ssrInterpolate(__props.coupon.coupon_code)}</code><button class="${ssrRenderClass([{ copied: copied.value }, "copy-btn"])}"${ssrRenderAttr("title", copied.value ? "\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E41\u0E25\u0E49\u0E27" : "\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A")} data-v-4de5cec3><span class="btn-text-full" data-v-4de5cec3>${ssrInterpolate(copied.value ? "\u2713 \u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08" : "\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01")}</span><span class="btn-text-short" data-v-4de5cec3>${ssrInterpolate(copied.value ? "\u2713" : "\u0E04\u0E31\u0E14")}</span></button></div></div><div class="dates-compact" data-v-4de5cec3>`);
      if (__props.coupon.expires_at) {
        _push(`<span class="date-item" data-v-4de5cec3><span class="date-label" data-v-4de5cec3>\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38:</span> ${ssrInterpolate(formatDateShort(__props.coupon.expires_at))}</span>`);
      } else {
        _push(`<span class="date-item no-expiry" data-v-4de5cec3>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38</span>`);
      }
      _push(`</div></div></div>`);
      if (__props.coupon.description) {
        _push(`<div class="description-section" data-v-4de5cec3><p class="description" data-v-4de5cec3>${ssrInterpolate(__props.coupon.description)}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="coupon-footer" data-v-4de5cec3><button class="action-btn print-btn"${ssrIncludeBooleanAttr(!__props.coupon.qr_code_path) ? " disabled" : ""} data-v-4de5cec3><span class="btn-icon" data-v-4de5cec3>\u{1F5A8}\uFE0F</span><span data-v-4de5cec3>\u0E1E\u0E34\u0E21\u0E1E\u0E4C</span></button>`);
      if (canCancel.value) {
        _push(`<button class="action-btn cancel-btn" data-v-4de5cec3><span class="btn-icon" data-v-4de5cec3>\u274C</span><span data-v-4de5cec3>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</span></button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/coupons/CouponCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CouponCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-4de5cec3"]]);

export { CouponCard as C };
//# sourceMappingURL=CouponCard-_T2CzrsF.mjs.map
