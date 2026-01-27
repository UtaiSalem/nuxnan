import { defineComponent, mergeProps, ref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderClass, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { _ as _export_sfc, i as useApi } from './server.mjs';
import { u as useToast } from './useToast-BpzfS75l.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CouponRedemption",
  __ssrInlineRender: true,
  setup(__props) {
    useApi();
    useToast();
    const method = ref("code");
    const couponCode = ref("");
    const isLoading = ref(false);
    const isScanning = ref(false);
    const result = ref(null);
    ref(null);
    ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "coupon-redemption" }, _attrs))} data-v-017f28ba><h2 class="page-title" data-v-017f28ba>\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07</h2><div class="redemption-methods" data-v-017f28ba><div class="method-selector" data-v-017f28ba><button class="${ssrRenderClass([{ active: method.value === "scan" }, "method-btn"])}" data-v-017f28ba><span class="btn-icon" data-v-017f28ba>\u{1F4F7}</span><span data-v-017f28ba>\u0E2A\u0E41\u0E01\u0E19 QR Code</span></button><button class="${ssrRenderClass([{ active: method.value === "code" }, "method-btn"])}" data-v-017f28ba><span class="btn-icon" data-v-017f28ba>\u2328\uFE0F</span><span data-v-017f28ba>\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A</span></button></div>`);
      if (method.value === "scan") {
        _push(`<div class="scan-section" data-v-017f28ba><div class="${ssrRenderClass([{ scanning: isScanning.value }, "scanner-container"])}" data-v-017f28ba><video class="scanner-video" autoplay playsinline data-v-017f28ba></video><canvas class="scanner-canvas" data-v-017f28ba></canvas>`);
        if (!isScanning.value) {
          _push(`<div class="scanner-overlay" data-v-017f28ba><div class="scan-icon" data-v-017f28ba>\u{1F4F7}</div><p class="scan-text" data-v-017f28ba>\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E41\u0E01\u0E19 QR Code</p></div>`);
        } else {
          _push(`<!---->`);
        }
        if (isScanning.value) {
          _push(`<div class="scan-line" data-v-017f28ba></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><button class="${ssrRenderClass([{ scanning: isScanning.value }, "scan-toggle-btn"])}" data-v-017f28ba>${ssrInterpolate(isScanning.value ? "\u0E2B\u0E22\u0E38\u0E14\u0E2A\u0E41\u0E01\u0E19" : "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E41\u0E01\u0E19")}</button></div>`);
      } else {
        _push(`<!---->`);
      }
      if (method.value === "code") {
        _push(`<div class="code-section" data-v-017f28ba><div class="code-input-container" data-v-017f28ba><input${ssrRenderAttr("value", couponCode.value)} type="text" placeholder="\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07 12 \u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23" maxlength="12" class="code-input"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} data-v-017f28ba><button class="paste-btn" title="\u0E27\u0E32\u0E07\u0E08\u0E32\u0E01\u0E04\u0E25\u0E34\u0E1B\u0E1A\u0E2D\u0E23\u0E4C\u0E14" data-v-017f28ba> \u{1F4CB} </button></div><button${ssrIncludeBooleanAttr(!couponCode.value || isLoading.value) ? " disabled" : ""} class="redeem-btn" data-v-017f28ba>`);
        if (isLoading.value) {
          _push(`<span class="spinner" data-v-017f28ba></span>`);
        } else {
          _push(`<span data-v-017f28ba>\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07</span>`);
        }
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (result.value) {
        _push(`<div class="result-section" data-v-017f28ba><div class="${ssrRenderClass(["result-card", result.value.success ? "success" : "error"])}" data-v-017f28ba><div class="result-icon" data-v-017f28ba>${ssrInterpolate(result.value.success ? "\u2705" : "\u274C")}</div><div class="result-content" data-v-017f28ba><h3 class="result-title" data-v-017f28ba>${ssrInterpolate(result.value.success ? "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!" : "\u0E25\u0E49\u0E21\u0E40\u0E2B\u0E25\u0E27")}</h3><p class="result-message" data-v-017f28ba>${ssrInterpolate(result.value.message)}</p>`);
        if (result.value.success) {
          _push(`<div class="result-details" data-v-017f28ba><div class="detail-item" data-v-017f28ba><span class="detail-label" data-v-017f28ba>\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17:</span><span class="detail-value" data-v-017f28ba>${ssrInterpolate(result.value.type === "points" ? "\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E15\u0E49\u0E21" : "\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E40\u0E07\u0E34\u0E19")}</span></div><div class="detail-item" data-v-017f28ba><span class="detail-label" data-v-017f28ba>\u0E08\u0E33\u0E19\u0E27\u0E19:</span><span class="detail-value" data-v-017f28ba>${ssrInterpolate(result.value.amount)}</span></div><div class="detail-item" data-v-017f28ba><span class="detail-label" data-v-017f28ba>\u0E22\u0E2D\u0E14${ssrInterpolate(result.value.type === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E43\u0E2B\u0E21\u0E48:</span><span class="detail-value" data-v-017f28ba>${ssrInterpolate(result.value.new_balance)}</span></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><button class="reset-btn" data-v-017f28ba> \u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2D\u0E35\u0E01 </button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="info-section" data-v-017f28ba><div class="info-card" data-v-017f28ba><span class="info-icon" data-v-017f28ba>\u2139\uFE0F</span><div class="info-content" data-v-017f28ba><h4 data-v-017f28ba>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</h4><ul class="info-list" data-v-017f28ba><li data-v-017f28ba>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49\u0E40\u0E1E\u0E35\u0E22\u0E07 1 \u0E04\u0E23\u0E31\u0E49\u0E07</li><li data-v-017f28ba>\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E07\u0E44\u0E14\u0E49</li><li data-v-017f28ba>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E08\u0E30\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38\u0E2B\u0E25\u0E31\u0E07\u0E08\u0E32\u0E01\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E44\u0E27\u0E49</li><li data-v-017f28ba>\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E44\u0E14\u0E49\u0E08\u0E32\u0E01\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01</li></ul></div></div></div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/coupons/CouponRedemption.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const CouponRedemption = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-017f28ba"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "redeem",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "redeem-page" }, _attrs))} data-v-9d94e175><div class="page-header" data-v-9d94e175><h1 class="page-title" data-v-9d94e175>\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07</h1><p class="page-subtitle" data-v-9d94e175> \u0E2A\u0E41\u0E01\u0E19 QR Code \u0E2B\u0E23\u0E37\u0E2D\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E07\u0E34\u0E19 </p></div>`);
      _push(ssrRenderComponent(CouponRedemption, null, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/coupons/redeem.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const redeem = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-9d94e175"]]);

export { redeem as default };
//# sourceMappingURL=redeem-BCEnVkft.mjs.map
