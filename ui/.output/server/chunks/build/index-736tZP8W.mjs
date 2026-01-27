import { defineComponent, ref, mergeProps, computed, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr } from 'vue/server-renderer';
import { _ as _export_sfc, i as useApi } from './server.mjs';
import { C as CouponCard } from './CouponCard-_T2CzrsF.mjs';
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

const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "CouponList",
  __ssrInlineRender: true,
  setup(__props) {
    const api = useApi();
    const coupons = ref([]);
    const statistics = ref(null);
    const isLoading = ref(false);
    const filterType = ref("all");
    const filterStatus = ref("all");
    const filteredCoupons = computed(() => {
      let result = [...coupons.value];
      if (filterType.value !== "all") {
        result = result.filter((c) => c.coupon_type === filterType.value);
      }
      if (filterStatus.value !== "all") {
        result = result.filter((c) => c.status === filterStatus.value);
      }
      return result;
    });
    const hasActiveFilters = computed(() => {
      return filterType.value !== "all" || filterStatus.value !== "all";
    });
    const emptyMessage = computed(() => {
      if (hasActiveFilters.value) {
        return "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E35\u0E48\u0E15\u0E23\u0E07\u0E01\u0E31\u0E1A\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 \u0E25\u0E2D\u0E07\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07\u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07";
      }
      return "\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07 \u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E23\u0E01\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E40\u0E25\u0E22!";
    });
    function handleCancelled(coupon) {
      const index2 = coupons.value.findIndex((c) => c.id === coupon.id);
      if (index2 !== -1) {
        coupons.value[index2] = { ...coupon, status: "cancelled" };
      }
      loadStatistics();
    }
    function handlePrinted(coupon) {
      console.log("Coupon printed:", coupon.coupon_code);
    }
    async function loadStatistics() {
      try {
        const response = await api.get("/api/coupons/statistics");
        if (response.success) {
          statistics.value = response.data;
        }
      } catch (err) {
        console.error("Failed to load statistics:", err);
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "coupon-list" }, _attrs))} data-v-a53ae1c2><div class="filters" data-v-a53ae1c2><select class="filter-select" data-v-a53ae1c2><option value="all" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterType.value) ? ssrLooseContain(filterType.value, "all") : ssrLooseEqual(filterType.value, "all")) ? " selected" : ""}>\u0E17\u0E38\u0E01\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</option><option value="points" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterType.value) ? ssrLooseContain(filterType.value, "points") : ssrLooseEqual(filterType.value, "points")) ? " selected" : ""}>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E15\u0E49\u0E21</option><option value="wallet" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterType.value) ? ssrLooseContain(filterType.value, "wallet") : ssrLooseEqual(filterType.value, "wallet")) ? " selected" : ""}>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E40\u0E07\u0E34\u0E19</option></select><select class="filter-select" data-v-a53ae1c2><option value="all" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterStatus.value) ? ssrLooseContain(filterStatus.value, "all") : ssrLooseEqual(filterStatus.value, "all")) ? " selected" : ""}>\u0E17\u0E38\u0E01\u0E2A\u0E16\u0E32\u0E19\u0E30</option><option value="active" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterStatus.value) ? ssrLooseContain(filterStatus.value, "active") : ssrLooseEqual(filterStatus.value, "active")) ? " selected" : ""}>\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49</option><option value="redeemed" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterStatus.value) ? ssrLooseContain(filterStatus.value, "redeemed") : ssrLooseEqual(filterStatus.value, "redeemed")) ? " selected" : ""}>\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27</option><option value="expired" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterStatus.value) ? ssrLooseContain(filterStatus.value, "expired") : ssrLooseEqual(filterStatus.value, "expired")) ? " selected" : ""}>\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38</option><option value="cancelled" data-v-a53ae1c2${ssrIncludeBooleanAttr(Array.isArray(filterStatus.value) ? ssrLooseContain(filterStatus.value, "cancelled") : ssrLooseEqual(filterStatus.value, "cancelled")) ? " selected" : ""}>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</option></select></div>`);
      if (statistics.value) {
        _push(`<div class="stats-bar" data-v-a53ae1c2><div class="stat-item" data-v-a53ae1c2><span class="stat-value" data-v-a53ae1c2>${ssrInterpolate(statistics.value.total_coupons)}</span><span class="stat-label" data-v-a53ae1c2>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span></div><div class="stat-item" data-v-a53ae1c2><span class="stat-value" data-v-a53ae1c2>${ssrInterpolate(statistics.value.active_coupons)}</span><span class="stat-label" data-v-a53ae1c2>\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49</span></div><div class="stat-item" data-v-a53ae1c2><span class="stat-value" data-v-a53ae1c2>${ssrInterpolate(statistics.value.redeemed_coupons)}</span><span class="stat-label" data-v-a53ae1c2>\u0E43\u0E0A\u0E49\u0E41\u0E25\u0E49\u0E27</span></div><div class="stat-item" data-v-a53ae1c2><span class="stat-value" data-v-a53ae1c2>${ssrInterpolate(statistics.value.total_points_in_coupons)}</span><span class="stat-label" data-v-a53ae1c2>\u0E41\u0E15\u0E49\u0E21\u0E43\u0E19\u0E04\u0E39\u0E1B\u0E2D\u0E07</span></div><div class="stat-item" data-v-a53ae1c2><span class="stat-value" data-v-a53ae1c2>${ssrInterpolate(statistics.value.total_wallet_in_coupons.toFixed(2))}</span><span class="stat-label" data-v-a53ae1c2>\u0E1A\u0E32\u0E17\u0E43\u0E19\u0E04\u0E39\u0E1B\u0E2D\u0E07</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (filteredCoupons.value.length > 0) {
        _push(`<div class="coupons-grid" data-v-a53ae1c2><!--[-->`);
        ssrRenderList(filteredCoupons.value, (coupon) => {
          _push(ssrRenderComponent(CouponCard, {
            key: coupon.id,
            coupon,
            onCancelled: handleCancelled,
            onPrinted: handlePrinted
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="empty-state" data-v-a53ae1c2><div class="empty-icon" data-v-a53ae1c2>\u{1F39F}</div><h3 class="empty-title" data-v-a53ae1c2>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07</h3><p class="empty-message" data-v-a53ae1c2>${ssrInterpolate(emptyMessage.value)}</p>`);
        if (hasActiveFilters.value) {
          _push(`<button class="reset-btn" data-v-a53ae1c2> \u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      if (isLoading.value) {
        _push(`<div class="loading-state" data-v-a53ae1c2><div class="spinner" data-v-a53ae1c2></div><p data-v-a53ae1c2>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/coupons/CouponList.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const CouponList = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-a53ae1c2"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CouponCreationForm",
  __ssrInlineRender: true,
  emits: ["created", "cancel"],
  setup(__props, { emit: __emit }) {
    useApi();
    useToast();
    const type = ref("points");
    const amount = ref(0);
    const description = ref("");
    const expiresInDays = ref(null);
    const isLoading = ref(false);
    const error = ref("");
    const pointsBalance = ref(5e3);
    const walletBalance = ref(100.5);
    const minAmount = computed(() => type.value === "points" ? 1 : 10);
    const availableBalance = computed(() => {
      return type.value === "points" ? pointsBalance.value : walletBalance.value;
    });
    const isValid = computed(() => {
      return amount.value >= minAmount.value && amount.value <= availableBalance.value && (!expiresInDays.value || expiresInDays.value >= 1 && expiresInDays.value <= 365);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "coupon-creation-form" }, _attrs))} data-v-d28fee15><h2 class="form-title" data-v-d28fee15>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07</h2><div class="coupon-type-selector" data-v-d28fee15><button class="${ssrRenderClass([{ active: type.value === "points" }, "type-btn"])}" data-v-d28fee15><span class="icon" data-v-d28fee15>\u{1F3AF}</span><span data-v-d28fee15>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E15\u0E49\u0E21</span></button><button class="${ssrRenderClass([{ active: type.value === "wallet" }, "type-btn"])}" data-v-d28fee15><span class="icon" data-v-d28fee15>\u{1F4B0}</span><span data-v-d28fee15>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E40\u0E07\u0E34\u0E19</span></button></div><form class="coupon-form" data-v-d28fee15><div class="form-group" data-v-d28fee15><label for="amount" data-v-d28fee15>${ssrInterpolate(type.value === "points" ? "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E41\u0E15\u0E49\u0E21" : "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E1A\u0E32\u0E17)")}</label><input${ssrRenderAttr("value", amount.value)} type="number" id="amount"${ssrRenderAttr("min", minAmount.value)}${ssrRenderAttr("step", type.value === "wallet" ? "0.01" : "1")} required class="form-input" data-v-d28fee15><span class="unit" data-v-d28fee15>${ssrInterpolate(type.value === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E1A\u0E32\u0E17")}</span></div><div class="form-group" data-v-d28fee15><label for="expiresInDays" data-v-d28fee15>\u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38 (\u0E27\u0E31\u0E19) - \u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A</label><input${ssrRenderAttr("value", expiresInDays.value)} type="number" id="expiresInDays" min="1" max="365" class="form-input" placeholder="\u0E40\u0E0A\u0E48\u0E19 30, 60, 90" data-v-d28fee15></div><div class="form-group" data-v-d28fee15><label for="description" data-v-d28fee15>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</label><textarea id="description" rows="3" class="form-textarea" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E19\u0E35\u0E49..." data-v-d28fee15>${ssrInterpolate(description.value)}</textarea></div><div class="balance-info" data-v-d28fee15><div class="balance-card" data-v-d28fee15><span class="label" data-v-d28fee15>\u0E22\u0E2D\u0E14${ssrInterpolate(type.value === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E17\u0E35\u0E48\u0E21\u0E35:</span><span class="value" data-v-d28fee15>${ssrInterpolate(availableBalance.value)} ${ssrInterpolate(type.value === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E1A\u0E32\u0E17")}</span></div><div class="info-card" data-v-d28fee15><span class="info-icon" data-v-d28fee15>\u2139\uFE0F</span><span class="info-text" data-v-d28fee15> \u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E08\u0E30\u0E16\u0E39\u0E01\u0E2B\u0E31\u0E01\u0E08\u0E32\u0E01${ssrInterpolate(type.value === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19")}\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E17\u0E31\u0E19\u0E17\u0E35 \u0E41\u0E25\u0E30\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49\u0E42\u0E14\u0E22\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E1C\u0E48\u0E32\u0E19 QR Code \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E04\u0E39\u0E1B\u0E2D\u0E07 </span></div></div><button type="submit"${ssrIncludeBooleanAttr(!isValid.value || isLoading.value) ? " disabled" : ""} class="submit-btn" data-v-d28fee15>`);
      if (isLoading.value) {
        _push(`<span class="spinner" data-v-d28fee15></span>`);
      } else {
        _push(`<span data-v-d28fee15>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07</span>`);
      }
      _push(`</button></form>`);
      if (error.value) {
        _push(`<div class="error-message" data-v-d28fee15>${ssrInterpolate(error.value)}</div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/coupons/CouponCreationForm.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const CouponCreationForm = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-d28fee15"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const showCreateModal = ref(false);
    function handleCouponCreated(coupon) {
      showCreateModal.value = false;
      (void 0).reload();
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "coupons-page" }, _attrs))} data-v-b881f4f4><div class="page-header" data-v-b881f4f4><h1 class="page-title" data-v-b881f4f4>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</h1><button class="create-btn" data-v-b881f4f4><span class="btn-icon" data-v-b881f4f4>\u2795</span><span data-v-b881f4f4>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07</span></button></div>`);
      if (!showCreateModal.value) {
        _push(ssrRenderComponent(CouponList, null, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (showCreateModal.value) {
        _push(`<div class="modal-overlay" data-v-b881f4f4><div class="modal-content" data-v-b881f4f4><div class="modal-header" data-v-b881f4f4><h2 data-v-b881f4f4>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48</h2><button class="close-btn" data-v-b881f4f4> \u2715 </button></div>`);
        _push(ssrRenderComponent(CouponCreationForm, {
          onCreated: handleCouponCreated,
          onCancel: ($event) => showCreateModal.value = false
        }, null, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/coupons/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-b881f4f4"]]);

export { index as default };
//# sourceMappingURL=index-736tZP8W.mjs.map
