import { defineComponent, ref, computed, mergeProps, withCtx, unref, createVNode, toDisplayString, withDirectives, vModelSelect, withKeys, vModelText, createBlock, createCommentVNode, openBlock, Fragment, renderList, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
import { _ as _sfc_main$1 } from './BaseCard-Baxif1fS.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "DepositApproval",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19 - Admin"
    });
    const authStore = useAuthStore();
    const config = useRuntimeConfig();
    const depositRequests = ref([]);
    const isLoading = ref(true);
    const isProcessing = ref(false);
    const selectedRequest = ref(null);
    const showModal = ref(false);
    const modalAction = ref("approve");
    const statusFilter = ref("pending");
    const searchQuery = ref("");
    const dateFrom = ref("");
    const dateTo = ref("");
    const stats = ref({
      pending: 0,
      approved_today: 0,
      total_pending_amount: 0
    });
    const currentPage = ref(1);
    const lastPage = ref(1);
    const total = ref(0);
    const rejectForm = ref({
      rejection_reason: "",
      admin_note: ""
    });
    const approveForm = ref({
      admin_note: ""
    });
    computed(() => {
      var _a;
      return (_a = authStore.user) == null ? void 0 : _a.is_plearnd_admin;
    });
    const apiBase = computed(() => config.public.apiBase || "");
    const loadRequests = async () => {
      try {
        isLoading.value = true;
        const params = new URLSearchParams();
        if (statusFilter.value) params.append("status", statusFilter.value);
        if (searchQuery.value) params.append("search", searchQuery.value);
        if (dateFrom.value) params.append("from_date", dateFrom.value);
        if (dateTo.value) params.append("to_date", dateTo.value);
        params.append("page", currentPage.value.toString());
        params.append("per_page", "20");
        const response = await $fetch(`${apiBase.value}/api/admin/deposit-requests?${params}`, {
          method: "GET",
          headers: {
            "Authorization": `Bearer ${authStore.token}`
          }
        });
        if (response.success) {
          depositRequests.value = response.data;
          stats.value = response.stats;
          currentPage.value = response.pagination.current_page;
          lastPage.value = response.pagination.last_page;
          total.value = response.pagination.total;
        }
      } catch (err) {
        console.error("Failed to load deposit requests:", err);
      } finally {
        isLoading.value = false;
      }
    };
    const openApproveModal = (request) => {
      selectedRequest.value = request;
      modalAction.value = "approve";
      approveForm.value.admin_note = "";
      showModal.value = true;
    };
    const openRejectModal = (request) => {
      selectedRequest.value = request;
      modalAction.value = "reject";
      rejectForm.value.rejection_reason = "";
      rejectForm.value.admin_note = "";
      showModal.value = true;
    };
    const formatMoney = (value) => {
      return new Intl.NumberFormat("th-TH", {
        style: "currency",
        currency: "THB"
      }).format(value);
    };
    const getStatusBadgeClass = (status) => {
      switch (status) {
        case "pending":
          return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400";
        case "approved":
          return "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400";
        case "rejected":
          return "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400";
        default:
          return "bg-gray-100 text-gray-800";
      }
    };
    const viewSlip = (url) => {
      (void 0).open(url, "_blank");
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8" }, _attrs))}><div class="max-w-7xl mx-auto"><div class="mb-8"><h1 class="text-3xl font-bold text-gray-900 dark:text-white">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19</h1><p class="text-gray-600 dark:text-gray-400 mt-2">\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E08\u0E32\u0E01\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">`);
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="p-4 flex items-center gap-4"${_scopeId}><div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:clock-outline",
              class: "w-6 h-6 text-yellow-600 dark:text-yellow-400"
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.pending)}</p></div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-4 flex items-center gap-4" }, [
                createVNode("div", { class: "w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center" }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:clock-outline",
                    class: "w-6 h-6 text-yellow-600 dark:text-yellow-400"
                  })
                ]),
                createVNode("div", null, [
                  createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23"),
                  createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.pending), 1)
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="p-4 flex items-center gap-4"${_scopeId}><div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:check-circle",
              class: "w-6 h-6 text-green-600 dark:text-green-400"
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49</p><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.approved_today)}</p></div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-4 flex items-center gap-4" }, [
                createVNode("div", { class: "w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center" }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:check-circle",
                    class: "w-6 h-6 text-green-600 dark:text-green-400"
                  })
                ]),
                createVNode("div", null, [
                  createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49"),
                  createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.approved_today), 1)
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="p-4 flex items-center gap-4"${_scopeId}><div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:cash-multiple",
              class: "w-6 h-6 text-blue-600 dark:text-blue-400"
            }, null, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}>\u0E22\u0E2D\u0E14\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p><p class="text-2xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatMoney(stats.value.total_pending_amount))}</p></div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-4 flex items-center gap-4" }, [
                createVNode("div", { class: "w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center" }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:cash-multiple",
                    class: "w-6 h-6 text-blue-600 dark:text-blue-400"
                  })
                ]),
                createVNode("div", null, [
                  createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E22\u0E2D\u0E14\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23"),
                  createVNode("p", { class: "text-2xl font-bold text-gray-900 dark:text-white" }, toDisplayString(formatMoney(stats.value.total_pending_amount)), 1)
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      _push(ssrRenderComponent(_sfc_main$1, { class: "mb-6" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="p-4"${_scopeId}><div class="grid grid-cols-1 md:grid-cols-4 gap-4"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</label><select class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "") : ssrLooseEqual(statusFilter.value, "")) ? " selected" : ""}${_scopeId}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><option value="pending"${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "pending") : ssrLooseEqual(statusFilter.value, "pending")) ? " selected" : ""}${_scopeId}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="approved"${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "approved") : ssrLooseEqual(statusFilter.value, "approved")) ? " selected" : ""}${_scopeId}>\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27</option><option value="rejected"${ssrIncludeBooleanAttr(Array.isArray(statusFilter.value) ? ssrLooseContain(statusFilter.value, "rejected") : ssrLooseEqual(statusFilter.value, "rejected")) ? " selected" : ""}${_scopeId}>\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E41\u0E25\u0E49\u0E27</option></select></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E04\u0E49\u0E19\u0E2B\u0E32</label><input${ssrRenderAttr("value", searchQuery.value)} type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg" placeholder="\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25, \u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E08\u0E32\u0E01\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</label><input${ssrRenderAttr("value", dateFrom.value)} type="date" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId}>\u0E16\u0E36\u0E07\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</label><input${ssrRenderAttr("value", dateTo.value)} type="date" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"${_scopeId}></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "p-4" }, [
                createVNode("div", { class: "grid grid-cols-1 md:grid-cols-4 gap-4" }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30"),
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => statusFilter.value = $event,
                      class: "w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg",
                      onChange: loadRequests
                    }, [
                      createVNode("option", { value: "" }, "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14"),
                      createVNode("option", { value: "pending" }, "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23"),
                      createVNode("option", { value: "approved" }, "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E41\u0E25\u0E49\u0E27"),
                      createVNode("option", { value: "rejected" }, "\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E41\u0E25\u0E49\u0E27")
                    ], 40, ["onUpdate:modelValue"]), [
                      [vModelSelect, statusFilter.value]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E49\u0E19\u0E2B\u0E32"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => searchQuery.value = $event,
                      type: "text",
                      class: "w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg",
                      placeholder: "\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25, \u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07",
                      onKeyup: withKeys(loadRequests, ["enter"])
                    }, null, 40, ["onUpdate:modelValue"]), [
                      [vModelText, searchQuery.value]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E08\u0E32\u0E01\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => dateFrom.value = $event,
                      type: "date",
                      class: "w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg",
                      onChange: loadRequests
                    }, null, 40, ["onUpdate:modelValue"]), [
                      [vModelText, dateFrom.value]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E16\u0E36\u0E07\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48"),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => dateTo.value = $event,
                      type: "date",
                      class: "w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg",
                      onChange: loadRequests
                    }, null, 40, ["onUpdate:modelValue"]), [
                      [vModelText, dateTo.value]
                    ])
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="overflow-x-auto"${_scopeId}>`);
            if (isLoading.value) {
              _push2(`<div class="py-12 text-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:loading",
                class: "w-8 h-8 text-primary-500 animate-spin mx-auto"
              }, null, _parent2, _scopeId));
              _push2(`<p class="text-gray-500 mt-2"${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</p></div>`);
            } else if (depositRequests.value.length > 0) {
              _push2(`<table class="w-full"${_scopeId}><thead class="bg-gray-50 dark:bg-gray-800"${_scopeId}><tr${_scopeId}><th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E2A\u0E25\u0E34\u0E1B</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</th><th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E2A\u0E16\u0E32\u0E19\u0E30</th><th class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300"${_scopeId}>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700"${_scopeId}><!--[-->`);
              ssrRenderList(depositRequests.value, (req) => {
                _push2(`<tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50"${_scopeId}><td class="px-4 py-4"${_scopeId}><div class="flex items-center gap-3"${_scopeId}><img${ssrRenderAttr("src", req.user.avatar || "/default-avatar.png")}${ssrRenderAttr("alt", req.user.name)} class="w-10 h-10 rounded-full object-cover"${_scopeId}><div${_scopeId}><p class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(req.user.name)}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(req.user.email)}</p><p class="text-xs text-primary-500"${_scopeId}>${ssrInterpolate(req.user.reference_code)}</p></div></div></td><td class="px-4 py-4"${_scopeId}><p class="font-semibold text-lg text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(formatMoney(req.amount))}</p><p class="text-xs text-gray-500"${_scopeId}>${ssrInterpolate(req.payment_method_label)}</p></td><td class="px-4 py-4"${_scopeId}><div class="text-sm"${_scopeId}>`);
                if (req.bank_name) {
                  _push2(`<p${_scopeId}><span class="text-gray-500"${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23:</span> ${ssrInterpolate(req.bank_name)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                if (req.transfer_date) {
                  _push2(`<p${_scopeId}><span class="text-gray-500"${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19:</span> ${ssrInterpolate(req.transfer_date)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                if (req.reference_number) {
                  _push2(`<p${_scopeId}><span class="text-gray-500"${_scopeId}>\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07:</span> ${ssrInterpolate(req.reference_number)}</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div></td><td class="px-4 py-4"${_scopeId}>`);
                if (req.transfer_slip) {
                  _push2(`<img${ssrRenderAttr("src", req.transfer_slip)} alt="Transfer slip" class="w-16 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 border"${_scopeId}>`);
                } else {
                  _push2(`<span class="text-gray-400"${_scopeId}>-</span>`);
                }
                _push2(`</td><td class="px-4 py-4"${_scopeId}><p class="text-sm text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(req.created_at)}</p></td><td class="px-4 py-4"${_scopeId}><span class="${ssrRenderClass([getStatusBadgeClass(req.status), "px-3 py-1 text-xs font-medium rounded-full"])}"${_scopeId}>${ssrInterpolate(req.status_label)}</span></td><td class="px-4 py-4 text-right"${_scopeId}>`);
                if (req.status === "pending") {
                  _push2(`<div class="flex justify-end gap-2"${_scopeId}><button class="px-3 py-1.5 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition-colors"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:check",
                    class: "w-4 h-4 inline mr-1"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 </button><button class="px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors"${_scopeId}>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:close",
                    class: "w-4 h-4 inline mr-1"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div>`);
                } else {
                  _push2(`<div class="text-sm text-gray-500"${_scopeId}>`);
                  if (req.reviewed_at) {
                    _push2(`<p${_scopeId}>${ssrInterpolate(req.reviewed_at)}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  if (req.rejection_reason) {
                    _push2(`<p class="text-red-500 text-xs mt-1"${_scopeId}>${ssrInterpolate(req.rejection_reason)}</p>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div>`);
                }
                _push2(`</td></tr>`);
              });
              _push2(`<!--]--></tbody></table>`);
            } else {
              _push2(`<div class="py-12 text-center text-gray-500 dark:text-gray-400"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:inbox-outline",
                class: "w-16 h-16 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<p${_scopeId}>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19</p></div>`);
            }
            _push2(`</div>`);
            if (lastPage.value > 1) {
              _push2(`<div class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"${_scopeId}><p class="text-sm text-gray-600 dark:text-gray-400"${_scopeId}> \u0E41\u0E2A\u0E14\u0E07 ${ssrInterpolate(depositRequests.value.length)} \u0E08\u0E32\u0E01 ${ssrInterpolate(total.value)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p><div class="flex gap-2"${_scopeId}><button${ssrIncludeBooleanAttr(currentPage.value <= 1) ? " disabled" : ""} class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:chevron-left",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`</button><span class="px-3 py-1 bg-primary-500 text-white rounded-lg"${_scopeId}>${ssrInterpolate(currentPage.value)}</span><button${ssrIncludeBooleanAttr(currentPage.value >= lastPage.value) ? " disabled" : ""} class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:chevron-right",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`</button></div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", { class: "overflow-x-auto" }, [
                isLoading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "py-12 text-center"
                }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:loading",
                    class: "w-8 h-8 text-primary-500 animate-spin mx-auto"
                  }),
                  createVNode("p", { class: "text-gray-500 mt-2" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...")
                ])) : depositRequests.value.length > 0 ? (openBlock(), createBlock("table", {
                  key: 1,
                  class: "w-full"
                }, [
                  createVNode("thead", { class: "bg-gray-50 dark:bg-gray-800" }, [
                    createVNode("tr", null, [
                      createVNode("th", { class: "px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49"),
                      createVNode("th", { class: "px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19"),
                      createVNode("th", { class: "px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19"),
                      createVNode("th", { class: "px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E2A\u0E25\u0E34\u0E1B"),
                      createVNode("th", { class: "px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48"),
                      createVNode("th", { class: "px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E2A\u0E16\u0E32\u0E19\u0E30"),
                      createVNode("th", { class: "px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300" }, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23")
                    ])
                  ]),
                  createVNode("tbody", { class: "divide-y divide-gray-200 dark:divide-gray-700" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(depositRequests.value, (req) => {
                      return openBlock(), createBlock("tr", {
                        key: req.id,
                        class: "hover:bg-gray-50 dark:hover:bg-gray-800/50"
                      }, [
                        createVNode("td", { class: "px-4 py-4" }, [
                          createVNode("div", { class: "flex items-center gap-3" }, [
                            createVNode("img", {
                              src: req.user.avatar || "/default-avatar.png",
                              alt: req.user.name,
                              class: "w-10 h-10 rounded-full object-cover"
                            }, null, 8, ["src", "alt"]),
                            createVNode("div", null, [
                              createVNode("p", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(req.user.name), 1),
                              createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(req.user.email), 1),
                              createVNode("p", { class: "text-xs text-primary-500" }, toDisplayString(req.user.reference_code), 1)
                            ])
                          ])
                        ]),
                        createVNode("td", { class: "px-4 py-4" }, [
                          createVNode("p", { class: "font-semibold text-lg text-gray-900 dark:text-white" }, toDisplayString(formatMoney(req.amount)), 1),
                          createVNode("p", { class: "text-xs text-gray-500" }, toDisplayString(req.payment_method_label), 1)
                        ]),
                        createVNode("td", { class: "px-4 py-4" }, [
                          createVNode("div", { class: "text-sm" }, [
                            req.bank_name ? (openBlock(), createBlock("p", { key: 0 }, [
                              createVNode("span", { class: "text-gray-500" }, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23:"),
                              createTextVNode(" " + toDisplayString(req.bank_name), 1)
                            ])) : createCommentVNode("", true),
                            req.transfer_date ? (openBlock(), createBlock("p", { key: 1 }, [
                              createVNode("span", { class: "text-gray-500" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19:"),
                              createTextVNode(" " + toDisplayString(req.transfer_date), 1)
                            ])) : createCommentVNode("", true),
                            req.reference_number ? (openBlock(), createBlock("p", { key: 2 }, [
                              createVNode("span", { class: "text-gray-500" }, "\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07:"),
                              createTextVNode(" " + toDisplayString(req.reference_number), 1)
                            ])) : createCommentVNode("", true)
                          ])
                        ]),
                        createVNode("td", { class: "px-4 py-4" }, [
                          req.transfer_slip ? (openBlock(), createBlock("img", {
                            key: 0,
                            src: req.transfer_slip,
                            alt: "Transfer slip",
                            class: "w-16 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 border",
                            onClick: ($event) => viewSlip(req.transfer_slip)
                          }, null, 8, ["src", "onClick"])) : (openBlock(), createBlock("span", {
                            key: 1,
                            class: "text-gray-400"
                          }, "-"))
                        ]),
                        createVNode("td", { class: "px-4 py-4" }, [
                          createVNode("p", { class: "text-sm text-gray-900 dark:text-white" }, toDisplayString(req.created_at), 1)
                        ]),
                        createVNode("td", { class: "px-4 py-4" }, [
                          createVNode("span", {
                            class: ["px-3 py-1 text-xs font-medium rounded-full", getStatusBadgeClass(req.status)]
                          }, toDisplayString(req.status_label), 3)
                        ]),
                        createVNode("td", { class: "px-4 py-4 text-right" }, [
                          req.status === "pending" ? (openBlock(), createBlock("div", {
                            key: 0,
                            class: "flex justify-end gap-2"
                          }, [
                            createVNode("button", {
                              class: "px-3 py-1.5 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition-colors",
                              onClick: ($event) => openApproveModal(req)
                            }, [
                              createVNode(unref(Icon), {
                                icon: "mdi:check",
                                class: "w-4 h-4 inline mr-1"
                              }),
                              createTextVNode(" \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 ")
                            ], 8, ["onClick"]),
                            createVNode("button", {
                              class: "px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors",
                              onClick: ($event) => openRejectModal(req)
                            }, [
                              createVNode(unref(Icon), {
                                icon: "mdi:close",
                                class: "w-4 h-4 inline mr-1"
                              }),
                              createTextVNode(" \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 ")
                            ], 8, ["onClick"])
                          ])) : (openBlock(), createBlock("div", {
                            key: 1,
                            class: "text-sm text-gray-500"
                          }, [
                            req.reviewed_at ? (openBlock(), createBlock("p", { key: 0 }, toDisplayString(req.reviewed_at), 1)) : createCommentVNode("", true),
                            req.rejection_reason ? (openBlock(), createBlock("p", {
                              key: 1,
                              class: "text-red-500 text-xs mt-1"
                            }, toDisplayString(req.rejection_reason), 1)) : createCommentVNode("", true)
                          ]))
                        ])
                      ]);
                    }), 128))
                  ])
                ])) : (openBlock(), createBlock("div", {
                  key: 2,
                  class: "py-12 text-center text-gray-500 dark:text-gray-400"
                }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:inbox-outline",
                    class: "w-16 h-16 mx-auto mb-4"
                  }),
                  createVNode("p", null, "\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19")
                ]))
              ]),
              lastPage.value > 1 ? (openBlock(), createBlock("div", {
                key: 0,
                class: "p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
              }, [
                createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, " \u0E41\u0E2A\u0E14\u0E07 " + toDisplayString(depositRequests.value.length) + " \u0E08\u0E32\u0E01 " + toDisplayString(total.value) + " \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 ", 1),
                createVNode("div", { class: "flex gap-2" }, [
                  createVNode("button", {
                    disabled: currentPage.value <= 1,
                    class: "px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50",
                    onClick: ($event) => {
                      currentPage.value--;
                      loadRequests();
                    }
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:chevron-left",
                      class: "w-5 h-5"
                    })
                  ], 8, ["disabled", "onClick"]),
                  createVNode("span", { class: "px-3 py-1 bg-primary-500 text-white rounded-lg" }, toDisplayString(currentPage.value), 1),
                  createVNode("button", {
                    disabled: currentPage.value >= lastPage.value,
                    class: "px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50",
                    onClick: ($event) => {
                      currentPage.value++;
                      loadRequests();
                    }
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:chevron-right",
                      class: "w-5 h-5"
                    })
                  ], 8, ["disabled", "onClick"])
                ])
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      ssrRenderTeleport(_push, (_push2) => {
        if (showModal.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"><div class="p-6">`);
          if (modalAction.value === "approve") {
            _push2(`<!--[--><div class="text-center mb-6"><div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:check-circle",
              class: "w-8 h-8 text-green-600"
            }, null, _parent));
            _push2(`</div><h3 class="text-xl font-bold text-gray-900 dark:text-white">\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19</h3><p class="text-gray-600 dark:text-gray-400 mt-2">\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E19\u0E35\u0E49?</p></div>`);
            if (selectedRequest.value) {
              _push2(`<div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6"><div class="flex items-center gap-3 mb-3"><img${ssrRenderAttr("src", selectedRequest.value.user.avatar || "/default-avatar.png")} class="w-12 h-12 rounded-full"><div><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(selectedRequest.value.user.name)}</p><p class="text-sm text-gray-500">${ssrInterpolate(selectedRequest.value.user.email)}</p></div></div><p class="text-2xl font-bold text-green-600 text-center">${ssrInterpolate(formatMoney(selectedRequest.value.amount))}</p></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mb-6"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38 Admin (\u0E16\u0E49\u0E32\u0E21\u0E35)</label><textarea rows="2" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21...">${ssrInterpolate(approveForm.value.admin_note)}</textarea></div><div class="flex gap-3"><button class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 px-4 py-3 bg-green-500 text-white font-medium rounded-xl hover:bg-green-600 transition-colors disabled:opacity-50"${ssrIncludeBooleanAttr(isProcessing.value) ? " disabled" : ""}>`);
            if (isProcessing.value) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:loading",
                class: "w-5 h-5 animate-spin inline mr-2"
              }, null, _parent));
            } else {
              _push2(`<!---->`);
            }
            _push2(` \u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 </button></div><!--]-->`);
          } else if (modalAction.value === "reject") {
            _push2(`<!--[--><div class="text-center mb-6"><div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close-circle",
              class: "w-8 h-8 text-red-600"
            }, null, _parent));
            _push2(`</div><h3 class="text-xl font-bold text-gray-900 dark:text-white">\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E01\u0E32\u0E23\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19</h3><p class="text-gray-600 dark:text-gray-400 mt-2">\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E15\u0E34\u0E21\u0E40\u0E07\u0E34\u0E19\u0E19\u0E35\u0E49?</p></div>`);
            if (selectedRequest.value) {
              _push2(`<div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6"><div class="flex items-center gap-3 mb-3"><img${ssrRenderAttr("src", selectedRequest.value.user.avatar || "/default-avatar.png")} class="w-12 h-12 rounded-full"><div><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(selectedRequest.value.user.name)}</p><p class="text-sm text-gray-500">${ssrInterpolate(selectedRequest.value.user.email)}</p></div></div><p class="text-2xl font-bold text-gray-900 dark:text-white text-center">${ssrInterpolate(formatMoney(selectedRequest.value.amount))}</p></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mb-4"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25\u0E17\u0E35\u0E48\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 <span class="text-red-500">*</span></label><textarea rows="3" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E2B\u0E15\u0E38\u0E1C\u0E25...">${ssrInterpolate(rejectForm.value.rejection_reason)}</textarea></div><div class="mb-6"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38 Admin (\u0E16\u0E49\u0E32\u0E21\u0E35)</label><textarea rows="2" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl" placeholder="\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21...">${ssrInterpolate(rejectForm.value.admin_note)}</textarea></div><div class="flex gap-3"><button class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 px-4 py-3 bg-red-500 text-white font-medium rounded-xl hover:bg-red-600 transition-colors disabled:opacity-50"${ssrIncludeBooleanAttr(isProcessing.value || !rejectForm.value.rejection_reason.trim()) ? " disabled" : ""}>`);
            if (isProcessing.value) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:loading",
                class: "w-5 h-5 animate-spin inline mr-2"
              }, null, _parent));
            } else {
              _push2(`<!---->`);
            }
            _push2(` \u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div><!--]-->`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/PlearndAdmin/DepositApproval.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=DepositApproval-WrtHv4uR.mjs.map
