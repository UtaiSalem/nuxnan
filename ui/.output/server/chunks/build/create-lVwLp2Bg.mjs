import { ref, computed, resolveComponent, withCtx, unref, createVNode, withDirectives, createTextVNode, vModelText, createBlock, openBlock, withModifiers, createCommentVNode, Fragment, renderList, toDisplayString, vModelSelect, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { useObjectUrl } from '@vueuse/core';
import MainLayout from './main-CdHCodS1.mjs';
import Swal from 'sweetalert2';
import { _ as _export_sfc, d as useAuthStore, b as useRuntimeConfig, u as useRouter, n as navigateTo } from './server.mjs';
import { _ as _sfc_main$1 } from './AdvertiseItemCard-CBvettSq.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
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
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import 'pinia';
import './useGamification-BliN7lma.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "create",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const config = useRuntimeConfig();
    const apiBase = config.public.apiBase;
    const router = useRouter();
    const isLoading = ref(false);
    const title = ref("");
    const description = ref("");
    const mediaLink = ref("");
    const mediaImage = ref(null);
    const inputMediaImage = ref(null);
    const dragingMedia = ref(false);
    const quantityToShowProductMedia = ref(1e3);
    const timeToShowProductMedia = ref(5);
    const transferDate = ref(/* @__PURE__ */ new Date());
    const transferTime = ref({ hours: (/* @__PURE__ */ new Date()).getHours(), minutes: (/* @__PURE__ */ new Date()).getMinutes() });
    const payWithWallet = ref(false);
    const inputSlip = ref(null);
    const slipImage = ref(null);
    const dragingSlip = ref(false);
    const totalMoneyAdvert = ref(0);
    const quantityOptions = [100, 500, 1e3, 2e3, 5e3, 1e4];
    const timeOptions = [1, 3, 5, 10, 15, 30];
    const previewAdvert = computed(() => {
      var _a, _b;
      return {
        advertiser: authStore.user,
        media_image: ((_a = mediaImage.value) == null ? void 0 : _a.url) || "https://via.placeholder.com/300?text=Ad+Image+Preview",
        media_type: (_b = mediaImage.value) == null ? void 0 : _b.type,
        total_views: quantityToShowProductMedia.value,
        remaining_views: quantityToShowProductMedia.value
      };
    });
    const walletBalance = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.wallet) || 0;
    });
    function computeTotalCost() {
      if (quantityToShowProductMedia.value < 0) quantityToShowProductMedia.value = 0;
      totalMoneyAdvert.value = quantityToShowProductMedia.value * timeToShowProductMedia.value * 64 / 684;
    }
    const browseInputMedia = () => inputMediaImage.value.click();
    const onInputMediaChange = (e) => {
      if (e.target.files && e.target.files[0]) {
        mediaImage.value = {
          file: e.target.files[0],
          url: URL.createObjectURL(e.target.files[0]),
          type: e.target.files[0].type
        };
      }
    };
    const onDropMediaFile = (e) => {
      dragingMedia.value = false;
      let files = [...e.dataTransfer.items].filter((item) => item.kind === "file").map((item) => item.getAsFile());
      if (files.length > 0) {
        mediaImage.value = {
          file: files[0],
          url: useObjectUrl(files[0]),
          type: files[0].type
        };
      }
    };
    const deleteMediaImage = () => mediaImage.value = null;
    const browseInputSlip = () => inputSlip.value.click();
    const onInputSlipChange = (e) => {
      if (e.target.files && e.target.files[0]) {
        slipImage.value = {
          file: e.target.files[0],
          url: URL.createObjectURL(e.target.files[0])
        };
      }
    };
    const onDropSlipFile = (e) => {
      dragingSlip.value = false;
      let files = [...e.dataTransfer.items].filter((item) => item.kind === "file").map((item) => item.getAsFile());
      if (files.length > 0) {
        slipImage.value = {
          file: files[0],
          url: useObjectUrl(files[0])
        };
      }
    };
    const deleteSlipImage = () => slipImage.value = null;
    async function submitForm() {
      var _a, _b, _c;
      if (!authStore.isAuthenticated) {
        Swal.fire("Error", "Please login first", "error");
        return;
      }
      if (!mediaImage.value) {
        Swal.fire("\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19", "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E42\u0E06\u0E29\u0E13\u0E32", "warning");
        return;
      }
      if (!payWithWallet.value && !slipImage.value) {
        Swal.fire("\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19", "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E2A\u0E25\u0E34\u0E1B\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 \u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E0A\u0E33\u0E23\u0E30\u0E14\u0E49\u0E27\u0E22 Wallet", "warning");
        return;
      }
      if (payWithWallet.value && walletBalance.value < totalMoneyAdvert.value) {
        Swal.fire("\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19", "\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19\u0E43\u0E19 Wallet \u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D", "error");
        return;
      }
      isLoading.value = true;
      const advertData = new FormData();
      advertData.append("advertiser_id", (_a = authStore.user) == null ? void 0 : _a.id);
      advertData.append("title", title.value);
      if (description.value) advertData.append("description", description.value);
      if (mediaLink.value) advertData.append("media_link", mediaLink.value);
      advertData.append("amounts", totalMoneyAdvert.value);
      advertData.append("duration", timeToShowProductMedia.value);
      advertData.append("total_views", quantityToShowProductMedia.value);
      const year = transferDate.value.getFullYear();
      const month = String(transferDate.value.getMonth() + 1).padStart(2, "0");
      const day = String(transferDate.value.getDate()).padStart(2, "0");
      advertData.append("transfer_date", `${year}-${month}-${day}`);
      let hours = 0, minutes = 0;
      if (transferTime.value) {
        if (typeof transferTime.value === "string") {
          const parts = transferTime.value.split(":");
          hours = parts[0];
          minutes = parts[1];
        } else {
          hours = (_b = transferTime.value.hours) != null ? _b : 0;
          minutes = (_c = transferTime.value.minutes) != null ? _c : 0;
        }
      }
      advertData.append("transfer_time", `${hours}:${minutes}`);
      if (slipImage.value) advertData.append("slip", slipImage.value.file);
      if (mediaImage.value) advertData.append("media_image", mediaImage.value.file);
      try {
        const advertResp = await $fetch(`${apiBase}/api/advertises`, {
          method: "POST",
          body: advertData,
          headers: { Authorization: `Bearer ${authStore.token}` }
        });
        if (advertResp.success) {
          Swal.fire({
            title: "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
            text: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
            icon: "success",
            confirmButtonText: "\u0E15\u0E01\u0E25\u0E07",
            confirmButtonColor: "#10B981"
          }).then(() => {
            navigateTo("/earn/advertise");
          });
        } else {
          throw new Error(advertResp.message || "Unknown Error");
        }
      } catch (error) {
        Swal.fire("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14", error.message || "Error occurred", "error");
      } finally {
        isLoading.value = false;
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_VueDatePicker = resolveComponent("VueDatePicker");
      _push(ssrRenderComponent(MainLayout, _attrs, {
        hero: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="relative rounded-2xl overflow-hidden shadow-lg mb-8 bg-gradient-to-r from-blue-600 to-indigo-700 h-48 flex items-center px-8" data-v-c73ec6f1${_scopeId}><div class="absolute inset-0 opacity-20 bg-[url(&#39;/storage/images/banner/banner-bg.png&#39;)] bg-cover bg-center" data-v-c73ec6f1${_scopeId}></div><div class="relative z-10 flex items-center gap-6" data-v-c73ec6f1${_scopeId}><div class="p-4 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20" data-v-c73ec6f1${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "solar:megaphone-bold-duotone",
              class: "w-12 h-12 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><div data-v-c73ec6f1${_scopeId}><h1 class="text-3xl md:text-4xl font-bold text-white mb-2" data-v-c73ec6f1${_scopeId}>\u0E25\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32</h1><p class="text-blue-100 text-lg" data-v-c73ec6f1${_scopeId}>\u0E42\u0E1B\u0E23\u0E42\u0E21\u0E17\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32\u0E41\u0E25\u0E30\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E19\u0E31\u0E1A\u0E2B\u0E21\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19\u0E44\u0E14\u0E49\u0E07\u0E48\u0E32\u0E22\u0E46</p></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "relative rounded-2xl overflow-hidden shadow-lg mb-8 bg-gradient-to-r from-blue-600 to-indigo-700 h-48 flex items-center px-8" }, [
                createVNode("div", { class: "absolute inset-0 opacity-20 bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-center" }),
                createVNode("div", { class: "relative z-10 flex items-center gap-6" }, [
                  createVNode("div", { class: "p-4 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20" }, [
                    createVNode(unref(Icon), {
                      icon: "solar:megaphone-bold-duotone",
                      class: "w-12 h-12 text-white"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h1", { class: "text-3xl md:text-4xl font-bold text-white mb-2" }, "\u0E25\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32"),
                    createVNode("p", { class: "text-blue-100 text-lg" }, "\u0E42\u0E1B\u0E23\u0E42\u0E21\u0E17\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32\u0E41\u0E25\u0E30\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E19\u0E31\u0E1A\u0E2B\u0E21\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19\u0E44\u0E14\u0E49\u0E07\u0E48\u0E32\u0E22\u0E46")
                  ])
                ])
              ])
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="max-w-7xl mx-auto pb-20" data-v-c73ec6f1${_scopeId}><div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" data-v-c73ec6f1${_scopeId}><div class="lg:col-span-2 space-y-8" data-v-c73ec6f1${_scopeId}><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8" data-v-c73ec6f1${_scopeId}><div class="flex items-center gap-3 mb-6" data-v-c73ec6f1${_scopeId}><span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold" data-v-c73ec6f1${_scopeId}>1</span><h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-c73ec6f1${_scopeId}>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E42\u0E06\u0E29\u0E13\u0E32</h2></div><div class="space-y-6" data-v-c73ec6f1${_scopeId}><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E42\u0E06\u0E29\u0E13\u0E32 / \u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 <span class="text-red-500" data-v-c73ec6f1${_scopeId}>*</span></label><input${ssrRenderAttr("value", title.value)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E42\u0E1B\u0E23\u0E42\u0E21\u0E0A\u0E31\u0E48\u0E19\u0E1E\u0E34\u0E40\u0E28\u0E29\u0E25\u0E14 50%..." class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all shadow-sm" required data-v-c73ec6f1${_scopeId}></div><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</label><textarea rows="3" placeholder="\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E08\u0E38\u0E14\u0E40\u0E14\u0E48\u0E19\u0E02\u0E2D\u0E07\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32\u0E2B\u0E23\u0E37\u0E2D\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23..." class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all shadow-sm" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(description.value)}</textarea></div><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2B\u0E23\u0E37\u0E2D\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E42\u0E06\u0E29\u0E13\u0E32 <span class="text-red-500" data-v-c73ec6f1${_scopeId}>*</span></label>`);
            if (!mediaImage.value) {
              _push2(`<div class="${ssrRenderClass([{ "border-blue-500 bg-blue-50": dragingMedia.value }, "border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-8 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"])}" data-v-c73ec6f1${_scopeId}><div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform" data-v-c73ec6f1${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "solar:cloud-upload-bold-duotone",
                class: "w-8 h-8"
              }, null, _parent2, _scopeId));
              _push2(`</div><p class="text-gray-900 dark:text-white font-medium mb-1" data-v-c73ec6f1${_scopeId}>\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14 \u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E21\u0E32\u0E27\u0E32\u0E07\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48</p><p class="text-sm text-gray-500 dark:text-gray-400" data-v-c73ec6f1${_scopeId}>\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A JPG, PNG, MP4, WEBM (Max 20MB)</p><input type="file" class="hidden" accept="image/*,video/*" data-v-c73ec6f1${_scopeId}></div>`);
            } else {
              _push2(`<div class="relative rounded-2xl overflow-hidden shadow-md group" data-v-c73ec6f1${_scopeId}><button class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg z-20 transition-transform hover:scale-110" data-v-c73ec6f1${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), { icon: "solar:trash-bin-bold" }, null, _parent2, _scopeId));
              _push2(`</button>`);
              if (mediaImage.value.type && mediaImage.value.type.startsWith("video/")) {
                _push2(`<div class="aspect-video bg-black flex items-center justify-center" data-v-c73ec6f1${_scopeId}><video${ssrRenderAttr("src", mediaImage.value.url)} controls class="max-h-[400px] w-full" data-v-c73ec6f1${_scopeId}></video></div>`);
              } else {
                _push2(`<img${ssrRenderAttr("src", mediaImage.value.url)} class="w-full object-contain max-h-[400px] bg-gray-100 dark:bg-gray-900" data-v-c73ec6f1${_scopeId}>`);
              }
              _push2(`</div>`);
            }
            _push2(`</div><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E25\u0E34\u0E07\u0E01\u0E4C\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C / \u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</label><div class="relative" data-v-c73ec6f1${_scopeId}><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500" data-v-c73ec6f1${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), { icon: "solar:link-circle-bold" }, null, _parent2, _scopeId));
            _push2(`</div><input${ssrRenderAttr("value", mediaLink.value)} type="url" placeholder="https://..." class="pl-10 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm" data-v-c73ec6f1${_scopeId}></div></div></div></section><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8" data-v-c73ec6f1${_scopeId}><div class="flex items-center gap-3 mb-6" data-v-c73ec6f1${_scopeId}><span class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold" data-v-c73ec6f1${_scopeId}>2</span><h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-c73ec6f1${_scopeId}>\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E41\u0E04\u0E21\u0E40\u0E1B\u0E0D</h2></div><div class="grid md:grid-cols-2 gap-6" data-v-c73ec6f1${_scopeId}><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25 (Views)</label><select class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11" data-v-c73ec6f1${_scopeId}><!--[-->`);
            ssrRenderList(quantityOptions, (opt) => {
              _push2(`<option${ssrRenderAttr("value", opt)} data-v-c73ec6f1${ssrIncludeBooleanAttr(Array.isArray(quantityToShowProductMedia.value) ? ssrLooseContain(quantityToShowProductMedia.value, opt) : ssrLooseEqual(quantityToShowProductMedia.value, opt)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(opt.toLocaleString())} \u0E04\u0E23\u0E31\u0E49\u0E07</option>`);
            });
            _push2(`<!--]-->`);
            if (!quantityOptions.includes(quantityToShowProductMedia.value)) {
              _push2(`<option${ssrRenderAttr("value", quantityToShowProductMedia.value)} data-v-c73ec6f1${ssrIncludeBooleanAttr(Array.isArray(quantityToShowProductMedia.value) ? ssrLooseContain(quantityToShowProductMedia.value, quantityToShowProductMedia.value) : ssrLooseEqual(quantityToShowProductMedia.value, quantityToShowProductMedia.value)) ? " selected" : ""}${_scopeId}>\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E40\u0E2D\u0E07: ${ssrInterpolate(quantityToShowProductMedia.value)}</option>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</select>`);
            if (!quantityOptions.includes(quantityToShowProductMedia.value)) {
              _push2(`<input type="number"${ssrRenderAttr("value", quantityToShowProductMedia.value)} class="mt-2 w-full rounded-xl border-gray-300 text-sm" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19" data-v-c73ec6f1${_scopeId}>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32\u0E15\u0E48\u0E2D\u0E27\u0E34\u0E27 (\u0E27\u0E34\u0E19\u0E32\u0E17\u0E35)</label><select class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11" data-v-c73ec6f1${_scopeId}><!--[-->`);
            ssrRenderList(timeOptions, (opt) => {
              _push2(`<option${ssrRenderAttr("value", opt)} data-v-c73ec6f1${ssrIncludeBooleanAttr(Array.isArray(timeToShowProductMedia.value) ? ssrLooseContain(timeToShowProductMedia.value, opt) : ssrLooseEqual(timeToShowProductMedia.value, opt)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(opt)} \u0E27\u0E34\u0E19\u0E32\u0E17\u0E35</option>`);
            });
            _push2(`<!--]--></select></div></div></section><section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8" data-v-c73ec6f1${_scopeId}><div class="flex items-center gap-3 mb-6" data-v-c73ec6f1${_scopeId}><span class="w-8 h-8 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold" data-v-c73ec6f1${_scopeId}>3</span><h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-c73ec6f1${_scopeId}>\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19</h2></div><div class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-xl mb-6" data-v-c73ec6f1${_scopeId}><button class="${ssrRenderClass([!payWithWallet.value ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700", "flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"])}" data-v-c73ec6f1${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), { icon: "solar:card-transfer-bold" }, null, _parent2, _scopeId));
            _push2(` \u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23 </button><button class="${ssrRenderClass([payWithWallet.value ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700", "flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"])}" data-v-c73ec6f1${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), { icon: "solar:wallet-money-bold" }, null, _parent2, _scopeId));
            _push2(` Wallet (${ssrInterpolate(walletBalance.value.toFixed(2))} \u0E3F) </button></div>`);
            if (!payWithWallet.value) {
              _push2(`<div class="space-y-6" data-v-c73ec6f1${_scopeId}><div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-200 dark:border-gray-600 flex items-start gap-4" data-v-c73ec6f1${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "solar:info-circle-bold",
                class: "w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5"
              }, null, _parent2, _scopeId));
              _push2(`<div class="text-sm text-gray-600 dark:text-gray-300" data-v-c73ec6f1${_scopeId}><p class="font-bold text-gray-900 dark:text-white mb-1" data-v-c73ec6f1${_scopeId}>\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</p><p data-v-c73ec6f1${_scopeId}>\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23: \u0E01\u0E2A\u0E34\u0E01\u0E23\u0E44\u0E17\u0E22 (K-Bank)</p><p data-v-c73ec6f1${_scopeId}>\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E1A\u0E31\u0E0D\u0E0A\u0E35: <span class="font-mono font-bold select-all" data-v-c73ec6f1${_scopeId}>XXX-X-XXXXX-X</span></p><p data-v-c73ec6f1${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35: \u0E1A\u0E08\u0E01. \u0E40\u0E1E\u0E25\u0E34\u0E19\u0E14\u0E4C</p></div></div><div class="grid md:grid-cols-2 gap-6" data-v-c73ec6f1${_scopeId}><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19</label>`);
              _push2(ssrRenderComponent(_component_VueDatePicker, {
                modelValue: transferDate.value,
                "onUpdate:modelValue": ($event) => transferDate.value = $event,
                format: "dd/MM/yyyy",
                "enable-time-picker": false,
                "auto-apply": "",
                class: "date-picker-custom"
              }, null, _parent2, _scopeId));
              _push2(`</div><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E40\u0E27\u0E25\u0E32\u0E42\u0E2D\u0E19</label>`);
              _push2(ssrRenderComponent(_component_VueDatePicker, {
                modelValue: transferTime.value,
                "onUpdate:modelValue": ($event) => transferTime.value = $event,
                "time-picker": "",
                format: "HH:mm",
                "auto-apply": ""
              }, {
                "input-icon": withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(unref(Icon), {
                      icon: "solar:clock-circle-bold",
                      class: "w-5 h-5 text-gray-400"
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(unref(Icon), {
                        icon: "solar:clock-circle-bold",
                        class: "w-5 h-5 text-gray-400"
                      })
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div></div><div data-v-c73ec6f1${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-c73ec6f1${_scopeId}>\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 (Slip) <span class="text-red-500" data-v-c73ec6f1${_scopeId}>*</span></label>`);
              if (!slipImage.value) {
                _push2(`<div class="${ssrRenderClass([{ "border-teal-500 bg-teal-50": dragingSlip.value }, "border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"])}" data-v-c73ec6f1${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "solar:document-add-bold",
                  class: "w-8 h-8 text-gray-400 group-hover:text-teal-500 mx-auto mb-2 transition-colors"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-sm text-gray-600 dark:text-gray-400" data-v-c73ec6f1${_scopeId}>\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E2A\u0E25\u0E34\u0E1B</p><input type="file" class="hidden" accept="image/*" data-v-c73ec6f1${_scopeId}></div>`);
              } else {
                _push2(`<div class="relative rounded-xl overflow-hidden shadow-sm border border-gray-200 inline-block group" data-v-c73ec6f1${_scopeId}><button class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity" data-v-c73ec6f1${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), { icon: "solar:close-circle-bold" }, null, _parent2, _scopeId));
                _push2(`</button><img${ssrRenderAttr("src", slipImage.value.url)} class="h-32 w-auto object-contain" data-v-c73ec6f1${_scopeId}><div class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[10px] px-2 py-0.5 truncate" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(slipImage.value.file.name)}</div></div>`);
              }
              _push2(`</div></div>`);
            } else {
              _push2(`<div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-xl border border-teal-200 dark:border-teal-700 text-center" data-v-c73ec6f1${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "solar:check-circle-bold",
                class: "w-10 h-10 text-teal-500 mx-auto mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<p class="font-medium text-teal-900 dark:text-teal-100" data-v-c73ec6f1${_scopeId}>\u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E30\u0E15\u0E31\u0E14\u0E40\u0E07\u0E34\u0E19\u0E08\u0E32\u0E01 Wallet \u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E17\u0E31\u0E19\u0E17\u0E35</p><p class="text-sm text-teal-600 dark:text-teal-300" data-v-c73ec6f1${_scopeId}>\u0E2A\u0E30\u0E14\u0E27\u0E01\u0E23\u0E27\u0E14\u0E40\u0E23\u0E47\u0E27 \u0E44\u0E21\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19</p></div>`);
            }
            _push2(`</section></div><div class="lg:col-span-1" data-v-c73ec6f1${_scopeId}><div class="sticky top-24 space-y-6" data-v-c73ec6f1${_scopeId}><div class="bg-gray-100 dark:bg-gray-800/50 rounded-2xl p-4 border border-dashed border-gray-300 dark:border-gray-600" data-v-c73ec6f1${_scopeId}><p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 text-center" data-v-c73ec6f1${_scopeId}>\u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32</p><div class="transform scale-90 sm:scale-100 origin-top bg-white rounded-xl shadow-lg pointer-events-none" data-v-c73ec6f1${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$1, { advert: previewAdvert.value }, null, _parent2, _scopeId));
            _push2(`</div><div class="mt-2 text-center" data-v-c73ec6f1${_scopeId}><p class="text-sm font-bold text-gray-800 dark:text-white truncate px-2" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(title.value || "\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E42\u0E06\u0E29\u0E13\u0E32")}</p><p class="text-xs text-gray-500 dark:text-gray-400 truncate px-4" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(description.value || "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14...")}</p></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" data-v-c73ec6f1${_scopeId}><div class="p-6" data-v-c73ec6f1${_scopeId}><h3 class="font-bold text-gray-900 dark:text-white mb-4" data-v-c73ec6f1${_scopeId}>\u0E2A\u0E23\u0E38\u0E1B\u0E22\u0E2D\u0E14\u0E0A\u0E33\u0E23\u0E30</h3><div class="space-y-3 text-sm" data-v-c73ec6f1${_scopeId}><div class="flex justify-between text-gray-600 dark:text-gray-300" data-v-c73ec6f1${_scopeId}><span data-v-c73ec6f1${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19 Views</span><span class="font-medium" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(quantityToShowProductMedia.value.toLocaleString())}</span></div><div class="flex justify-between text-gray-600 dark:text-gray-300" data-v-c73ec6f1${_scopeId}><span data-v-c73ec6f1${_scopeId}>\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32/View</span><span class="font-medium" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(timeToShowProductMedia.value)} \u0E27\u0E34\u0E19\u0E32\u0E17\u0E35</span></div><div class="h-px bg-gray-100 dark:bg-gray-700 my-2" data-v-c73ec6f1${_scopeId}></div><div class="flex justify-between items-end" data-v-c73ec6f1${_scopeId}><span class="font-bold text-gray-900 dark:text-white" data-v-c73ec6f1${_scopeId}>\u0E23\u0E27\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19</span><div class="text-right" data-v-c73ec6f1${_scopeId}><div class="text-3xl font-bold text-blue-600" data-v-c73ec6f1${_scopeId}>${ssrInterpolate(totalMoneyAdvert.value.toLocaleString(void 0, { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}</div><div class="text-xs text-gray-400" data-v-c73ec6f1${_scopeId}>\u0E1A\u0E32\u0E17</div></div></div></div></div><div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700" data-v-c73ec6f1${_scopeId}><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-blue-500/30 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" data-v-c73ec6f1${_scopeId}>`);
            if (isLoading.value) {
              _push2(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent2, _scopeId));
            } else {
              _push2(`<span data-v-c73ec6f1${_scopeId}>\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E41\u0E25\u0E30\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19</span>`);
            }
            _push2(`</button><button class="w-full mt-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white text-sm font-medium py-2" data-v-c73ec6f1${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></div></div></div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "max-w-7xl mx-auto pb-20" }, [
                createVNode("div", { class: "grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" }, [
                  createVNode("div", { class: "lg:col-span-2 space-y-8" }, [
                    createVNode("section", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8" }, [
                      createVNode("div", { class: "flex items-center gap-3 mb-6" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold" }, "1"),
                        createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-white" }, "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E42\u0E06\u0E29\u0E13\u0E32")
                      ]),
                      createVNode("div", { class: "space-y-6" }, [
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                            createTextVNode("\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E42\u0E06\u0E29\u0E13\u0E32 / \u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 "),
                            createVNode("span", { class: "text-red-500" }, "*")
                          ]),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => title.value = $event,
                            type: "text",
                            placeholder: "\u0E40\u0E0A\u0E48\u0E19 \u0E42\u0E1B\u0E23\u0E42\u0E21\u0E0A\u0E31\u0E48\u0E19\u0E1E\u0E34\u0E40\u0E28\u0E29\u0E25\u0E14 50%...",
                            class: "w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all shadow-sm",
                            required: ""
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, title.value]
                          ])
                        ]),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)"),
                          withDirectives(createVNode("textarea", {
                            "onUpdate:modelValue": ($event) => description.value = $event,
                            rows: "3",
                            placeholder: "\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E08\u0E38\u0E14\u0E40\u0E14\u0E48\u0E19\u0E02\u0E2D\u0E07\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32\u0E2B\u0E23\u0E37\u0E2D\u0E1A\u0E23\u0E34\u0E01\u0E32\u0E23...",
                            class: "w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all shadow-sm"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, description.value]
                          ])
                        ]),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                            createTextVNode("\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2B\u0E23\u0E37\u0E2D\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E42\u0E06\u0E29\u0E13\u0E32 "),
                            createVNode("span", { class: "text-red-500" }, "*")
                          ]),
                          !mediaImage.value ? (openBlock(), createBlock("div", {
                            key: 0,
                            onClick: browseInputMedia,
                            onDragover: withModifiers(($event) => dragingMedia.value = true, ["prevent"]),
                            onDragleave: withModifiers(($event) => dragingMedia.value = false, ["prevent"]),
                            onDrop: withModifiers(onDropMediaFile, ["prevent"]),
                            class: [{ "border-blue-500 bg-blue-50": dragingMedia.value }, "border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-8 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"]
                          }, [
                            createVNode("div", { class: "w-16 h-16 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform" }, [
                              createVNode(unref(Icon), {
                                icon: "solar:cloud-upload-bold-duotone",
                                class: "w-8 h-8"
                              })
                            ]),
                            createVNode("p", { class: "text-gray-900 dark:text-white font-medium mb-1" }, "\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14 \u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E21\u0E32\u0E27\u0E32\u0E07\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48"),
                            createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A JPG, PNG, MP4, WEBM (Max 20MB)"),
                            createVNode("input", {
                              type: "file",
                              ref_key: "inputMediaImage",
                              ref: inputMediaImage,
                              class: "hidden",
                              accept: "image/*,video/*",
                              onChange: onInputMediaChange
                            }, null, 544)
                          ], 42, ["onDragover", "onDragleave"])) : (openBlock(), createBlock("div", {
                            key: 1,
                            class: "relative rounded-2xl overflow-hidden shadow-md group"
                          }, [
                            createVNode("button", {
                              onClick: withModifiers(deleteMediaImage, ["stop"]),
                              class: "absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-lg z-20 transition-transform hover:scale-110"
                            }, [
                              createVNode(unref(Icon), { icon: "solar:trash-bin-bold" })
                            ]),
                            mediaImage.value.type && mediaImage.value.type.startsWith("video/") ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "aspect-video bg-black flex items-center justify-center"
                            }, [
                              createVNode("video", {
                                src: mediaImage.value.url,
                                controls: "",
                                class: "max-h-[400px] w-full"
                              }, null, 8, ["src"])
                            ])) : (openBlock(), createBlock("img", {
                              key: 1,
                              src: mediaImage.value.url,
                              class: "w-full object-contain max-h-[400px] bg-gray-100 dark:bg-gray-900"
                            }, null, 8, ["src"]))
                          ]))
                        ]),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E25\u0E34\u0E07\u0E01\u0E4C\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C / \u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)"),
                          createVNode("div", { class: "relative" }, [
                            createVNode("div", { class: "absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500" }, [
                              createVNode(unref(Icon), { icon: "solar:link-circle-bold" })
                            ]),
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => mediaLink.value = $event,
                              type: "url",
                              placeholder: "https://...",
                              class: "pl-10 w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, mediaLink.value]
                            ])
                          ])
                        ])
                      ])
                    ]),
                    createVNode("section", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8" }, [
                      createVNode("div", { class: "flex items-center gap-3 mb-6" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold" }, "2"),
                        createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-white" }, "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E41\u0E04\u0E21\u0E40\u0E1B\u0E0D")
                      ]),
                      createVNode("div", { class: "grid md:grid-cols-2 gap-6" }, [
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25 (Views)"),
                          withDirectives(createVNode("select", {
                            "onUpdate:modelValue": ($event) => quantityToShowProductMedia.value = $event,
                            onChange: computeTotalCost,
                            class: "w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11"
                          }, [
                            (openBlock(), createBlock(Fragment, null, renderList(quantityOptions, (opt) => {
                              return createVNode("option", {
                                key: opt,
                                value: opt
                              }, toDisplayString(opt.toLocaleString()) + " \u0E04\u0E23\u0E31\u0E49\u0E07", 9, ["value"]);
                            }), 64)),
                            !quantityOptions.includes(quantityToShowProductMedia.value) ? (openBlock(), createBlock("option", {
                              key: 0,
                              value: quantityToShowProductMedia.value
                            }, "\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E40\u0E2D\u0E07: " + toDisplayString(quantityToShowProductMedia.value), 9, ["value"])) : createCommentVNode("", true)
                          ], 40, ["onUpdate:modelValue"]), [
                            [
                              vModelSelect,
                              quantityToShowProductMedia.value,
                              void 0,
                              { number: true }
                            ]
                          ]),
                          !quantityOptions.includes(quantityToShowProductMedia.value) ? withDirectives((openBlock(), createBlock("input", {
                            key: 0,
                            type: "number",
                            "onUpdate:modelValue": ($event) => quantityToShowProductMedia.value = $event,
                            onInput: computeTotalCost,
                            class: "mt-2 w-full rounded-xl border-gray-300 text-sm",
                            placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E08\u0E33\u0E19\u0E27\u0E19"
                          }, null, 40, ["onUpdate:modelValue"])), [
                            [
                              vModelText,
                              quantityToShowProductMedia.value,
                              void 0,
                              { number: true }
                            ]
                          ]) : createCommentVNode("", true)
                        ]),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32\u0E15\u0E48\u0E2D\u0E27\u0E34\u0E27 (\u0E27\u0E34\u0E19\u0E32\u0E17\u0E35)"),
                          withDirectives(createVNode("select", {
                            "onUpdate:modelValue": ($event) => timeToShowProductMedia.value = $event,
                            onChange: computeTotalCost,
                            class: "w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm h-11"
                          }, [
                            (openBlock(), createBlock(Fragment, null, renderList(timeOptions, (opt) => {
                              return createVNode("option", {
                                key: opt,
                                value: opt
                              }, toDisplayString(opt) + " \u0E27\u0E34\u0E19\u0E32\u0E17\u0E35", 9, ["value"]);
                            }), 64))
                          ], 40, ["onUpdate:modelValue"]), [
                            [
                              vModelSelect,
                              timeToShowProductMedia.value,
                              void 0,
                              { number: true }
                            ]
                          ])
                        ])
                      ])
                    ]),
                    createVNode("section", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8" }, [
                      createVNode("div", { class: "flex items-center gap-3 mb-6" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center font-bold" }, "3"),
                        createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-white" }, "\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19")
                      ]),
                      createVNode("div", { class: "flex p-1 bg-gray-100 dark:bg-gray-900 rounded-xl mb-6" }, [
                        createVNode("button", {
                          onClick: ($event) => payWithWallet.value = false,
                          class: [!payWithWallet.value ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700", "flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"]
                        }, [
                          createVNode(unref(Icon), { icon: "solar:card-transfer-bold" }),
                          createTextVNode(" \u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23 ")
                        ], 10, ["onClick"]),
                        createVNode("button", {
                          onClick: ($event) => payWithWallet.value = true,
                          class: [payWithWallet.value ? "bg-white text-gray-900 shadow-sm" : "text-gray-500 hover:text-gray-700", "flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2"]
                        }, [
                          createVNode(unref(Icon), { icon: "solar:wallet-money-bold" }),
                          createTextVNode(" Wallet (" + toDisplayString(walletBalance.value.toFixed(2)) + " \u0E3F) ", 1)
                        ], 10, ["onClick"])
                      ]),
                      !payWithWallet.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "space-y-6"
                      }, [
                        createVNode("div", { class: "bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-200 dark:border-gray-600 flex items-start gap-4" }, [
                          createVNode(unref(Icon), {
                            icon: "solar:info-circle-bold",
                            class: "w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5"
                          }),
                          createVNode("div", { class: "text-sm text-gray-600 dark:text-gray-300" }, [
                            createVNode("p", { class: "font-bold text-gray-900 dark:text-white mb-1" }, "\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19"),
                            createVNode("p", null, "\u0E18\u0E19\u0E32\u0E04\u0E32\u0E23: \u0E01\u0E2A\u0E34\u0E01\u0E23\u0E44\u0E17\u0E22 (K-Bank)"),
                            createVNode("p", null, [
                              createTextVNode("\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E1A\u0E31\u0E0D\u0E0A\u0E35: "),
                              createVNode("span", { class: "font-mono font-bold select-all" }, "XXX-X-XXXXX-X")
                            ]),
                            createVNode("p", null, "\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35: \u0E1A\u0E08\u0E01. \u0E40\u0E1E\u0E25\u0E34\u0E19\u0E14\u0E4C")
                          ])
                        ]),
                        createVNode("div", { class: "grid md:grid-cols-2 gap-6" }, [
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19"),
                            createVNode(_component_VueDatePicker, {
                              modelValue: transferDate.value,
                              "onUpdate:modelValue": ($event) => transferDate.value = $event,
                              format: "dd/MM/yyyy",
                              "enable-time-picker": false,
                              "auto-apply": "",
                              class: "date-picker-custom"
                            }, null, 8, ["modelValue", "onUpdate:modelValue"])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E40\u0E27\u0E25\u0E32\u0E42\u0E2D\u0E19"),
                            createVNode(_component_VueDatePicker, {
                              modelValue: transferTime.value,
                              "onUpdate:modelValue": ($event) => transferTime.value = $event,
                              "time-picker": "",
                              format: "HH:mm",
                              "auto-apply": ""
                            }, {
                              "input-icon": withCtx(() => [
                                createVNode(unref(Icon), {
                                  icon: "solar:clock-circle-bold",
                                  class: "w-5 h-5 text-gray-400"
                                })
                              ]),
                              _: 1
                            }, 8, ["modelValue", "onUpdate:modelValue"])
                          ])
                        ]),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                            createTextVNode("\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 (Slip) "),
                            createVNode("span", { class: "text-red-500" }, "*")
                          ]),
                          !slipImage.value ? (openBlock(), createBlock("div", {
                            key: 0,
                            onClick: browseInputSlip,
                            onDragover: withModifiers(($event) => dragingSlip.value = true, ["prevent"]),
                            onDragleave: withModifiers(($event) => dragingSlip.value = false, ["prevent"]),
                            onDrop: withModifiers(onDropSlipFile, ["prevent"]),
                            class: [{ "border-teal-500 bg-teal-50": dragingSlip.value }, "border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"]
                          }, [
                            createVNode(unref(Icon), {
                              icon: "solar:document-add-bold",
                              class: "w-8 h-8 text-gray-400 group-hover:text-teal-500 mx-auto mb-2 transition-colors"
                            }),
                            createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E2A\u0E25\u0E34\u0E1B"),
                            createVNode("input", {
                              type: "file",
                              ref_key: "inputSlip",
                              ref: inputSlip,
                              class: "hidden",
                              accept: "image/*",
                              onChange: onInputSlipChange
                            }, null, 544)
                          ], 42, ["onDragover", "onDragleave"])) : (openBlock(), createBlock("div", {
                            key: 1,
                            class: "relative rounded-xl overflow-hidden shadow-sm border border-gray-200 inline-block group"
                          }, [
                            createVNode("button", {
                              onClick: withModifiers(deleteSlipImage, ["stop"]),
                              class: "absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity"
                            }, [
                              createVNode(unref(Icon), { icon: "solar:close-circle-bold" })
                            ]),
                            createVNode("img", {
                              src: slipImage.value.url,
                              class: "h-32 w-auto object-contain"
                            }, null, 8, ["src"]),
                            createVNode("div", { class: "absolute bottom-0 inset-x-0 bg-black/50 text-white text-[10px] px-2 py-0.5 truncate" }, toDisplayString(slipImage.value.file.name), 1)
                          ]))
                        ])
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "bg-teal-50 dark:bg-teal-900/20 p-4 rounded-xl border border-teal-200 dark:border-teal-700 text-center"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "solar:check-circle-bold",
                          class: "w-10 h-10 text-teal-500 mx-auto mb-2"
                        }),
                        createVNode("p", { class: "font-medium text-teal-900 dark:text-teal-100" }, "\u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E30\u0E15\u0E31\u0E14\u0E40\u0E07\u0E34\u0E19\u0E08\u0E32\u0E01 Wallet \u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E17\u0E31\u0E19\u0E17\u0E35"),
                        createVNode("p", { class: "text-sm text-teal-600 dark:text-teal-300" }, "\u0E2A\u0E30\u0E14\u0E27\u0E01\u0E23\u0E27\u0E14\u0E40\u0E23\u0E47\u0E27 \u0E44\u0E21\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19")
                      ]))
                    ])
                  ]),
                  createVNode("div", { class: "lg:col-span-1" }, [
                    createVNode("div", { class: "sticky top-24 space-y-6" }, [
                      createVNode("div", { class: "bg-gray-100 dark:bg-gray-800/50 rounded-2xl p-4 border border-dashed border-gray-300 dark:border-gray-600" }, [
                        createVNode("p", { class: "text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 text-center" }, "\u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E42\u0E06\u0E29\u0E13\u0E32"),
                        createVNode("div", { class: "transform scale-90 sm:scale-100 origin-top bg-white rounded-xl shadow-lg pointer-events-none" }, [
                          createVNode(_sfc_main$1, { advert: previewAdvert.value }, null, 8, ["advert"])
                        ]),
                        createVNode("div", { class: "mt-2 text-center" }, [
                          createVNode("p", { class: "text-sm font-bold text-gray-800 dark:text-white truncate px-2" }, toDisplayString(title.value || "\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E42\u0E06\u0E29\u0E13\u0E32"), 1),
                          createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 truncate px-4" }, toDisplayString(description.value || "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14..."), 1)
                        ])
                      ]),
                      createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" }, [
                        createVNode("div", { class: "p-6" }, [
                          createVNode("h3", { class: "font-bold text-gray-900 dark:text-white mb-4" }, "\u0E2A\u0E23\u0E38\u0E1B\u0E22\u0E2D\u0E14\u0E0A\u0E33\u0E23\u0E30"),
                          createVNode("div", { class: "space-y-3 text-sm" }, [
                            createVNode("div", { class: "flex justify-between text-gray-600 dark:text-gray-300" }, [
                              createVNode("span", null, "\u0E08\u0E33\u0E19\u0E27\u0E19 Views"),
                              createVNode("span", { class: "font-medium" }, toDisplayString(quantityToShowProductMedia.value.toLocaleString()), 1)
                            ]),
                            createVNode("div", { class: "flex justify-between text-gray-600 dark:text-gray-300" }, [
                              createVNode("span", null, "\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32/View"),
                              createVNode("span", { class: "font-medium" }, toDisplayString(timeToShowProductMedia.value) + " \u0E27\u0E34\u0E19\u0E32\u0E17\u0E35", 1)
                            ]),
                            createVNode("div", { class: "h-px bg-gray-100 dark:bg-gray-700 my-2" }),
                            createVNode("div", { class: "flex justify-between items-end" }, [
                              createVNode("span", { class: "font-bold text-gray-900 dark:text-white" }, "\u0E23\u0E27\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E07\u0E34\u0E19"),
                              createVNode("div", { class: "text-right" }, [
                                createVNode("div", { class: "text-3xl font-bold text-blue-600" }, toDisplayString(totalMoneyAdvert.value.toLocaleString(void 0, { minimumFractionDigits: 2, maximumFractionDigits: 2 })), 1),
                                createVNode("div", { class: "text-xs text-gray-400" }, "\u0E1A\u0E32\u0E17")
                              ])
                            ])
                          ])
                        ]),
                        createVNode("div", { class: "p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700" }, [
                          createVNode("button", {
                            onClick: submitForm,
                            disabled: isLoading.value,
                            class: "w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-blue-500/30 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                          }, [
                            isLoading.value ? (openBlock(), createBlock(unref(Icon), {
                              key: 0,
                              icon: "svg-spinners:ring-resize"
                            })) : (openBlock(), createBlock("span", { key: 1 }, "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E41\u0E25\u0E30\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19"))
                          ], 8, ["disabled"]),
                          createVNode("button", {
                            onClick: ($event) => unref(router).back(),
                            class: "w-full mt-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white text-sm font-medium py-2"
                          }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"])
                        ])
                      ])
                    ])
                  ])
                ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Advertise/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const create = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-c73ec6f1"]]);

export { create as default };
//# sourceMappingURL=create-lVwLp2Bg.mjs.map
