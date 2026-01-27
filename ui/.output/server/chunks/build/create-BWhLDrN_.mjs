import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, watch, mergeProps, unref, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, t as useSeoMeta, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
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
  __name: "create",
  __ssrInlineRender: true,
  setup(__props) {
    useSeoMeta({
      title: "\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C - Plearnd"
    });
    const config = useRuntimeConfig();
    const authStore = useAuthStore();
    const isLoading = ref(false);
    const isLoadingDonor = ref(false);
    const personalCode = ref("");
    const donor = ref(null);
    const totalMoneySupport = ref(10);
    const moneyIndexSelected = ref(0);
    const slipImage = ref(null);
    const now = /* @__PURE__ */ new Date();
    const transferDate = ref(now.toISOString().split("T")[0]);
    const transferTime = ref(now.toTimeString().slice(0, 5));
    const dragging = ref(false);
    ref(null);
    ref("");
    const isManualOverride = ref(false);
    const paymentMethod = ref("slip");
    const pointsRequired = computed(() => totalMoneySupport.value * 100);
    const searchQuery = ref("");
    const searchResults = ref([]);
    const showSearchDropdown = ref(false);
    const errors = ref({
      personalCode: "",
      slip: "",
      dateTime: ""
    });
    const showSuccessModal = ref(false);
    const generateOptions = (min, max, step) => {
      const options = [];
      for (let i = min; i <= max; i += step) {
        options.push(i);
      }
      return options;
    };
    const tempDonate2Digit = generateOptions(10, 100, 10);
    const tempDonate3Digit = generateOptions(150, 500, 50);
    const tempDonate4Digit = generateOptions(600, 1e3, 100);
    const totalMoneySupportOptions = [...tempDonate2Digit, ...tempDonate3Digit, ...tempDonate4Digit];
    const quickSelectOptions = [50, 100, 300, 500, 1e3];
    const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, "0"));
    const minutes = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, "0"));
    const selectedHour = ref(now.getHours().toString().padStart(2, "0"));
    const selectedMinute = ref(now.getMinutes().toString().padStart(2, "0"));
    watch([selectedHour, selectedMinute], ([h, m]) => {
      transferTime.value = `${h}:${m}`;
    }, { immediate: true });
    const estimatedPoints = computed(() => {
      return totalMoneySupport.value * 1080;
    });
    const today = (/* @__PURE__ */ new Date()).toISOString().split("T")[0];
    watch(() => authStore.user, async (user) => {
      if (user && !donor.value && !isManualOverride.value) {
        if (user.personal_code) {
          isLoadingDonor.value = true;
          try {
            const response = await $fetch(`${config.public.apiBase}/api/supports/donates/donor/${user.personal_code}`);
            if (response.success) {
              donor.value = response.donor;
            } else {
              donor.value = user;
            }
          } catch (e) {
            donor.value = user;
          } finally {
            isLoadingDonor.value = false;
          }
        } else {
          donor.value = user;
        }
        personalCode.value = user.personal_code || "";
      }
    }, { immediate: true });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "py-6 px-4" }, _attrs))} data-v-709e55f3><div class="mb-6 bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 rounded-2xl shadow-xl overflow-hidden" data-v-709e55f3><div class="px-6 py-8 flex flex-col md:flex-row items-center justify-between" data-v-709e55f3><div class="flex items-center gap-4 mb-4 md:mb-0" data-v-709e55f3><div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center" data-v-709e55f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:hand-coin",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><div data-v-709e55f3><h1 class="text-2xl md:text-3xl font-bold text-white" data-v-709e55f3>\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 \u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C \u0E40\u0E1E\u0E25\u0E34\u0E19\u0E14\u0E4C</h1><p class="text-white/80 mt-1" data-v-709e55f3>\u0E23\u0E48\u0E27\u0E21\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E41\u0E1E\u0E25\u0E15\u0E1F\u0E2D\u0E23\u0E4C\u0E21\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</p></div></div><div class="flex gap-3" data-v-709e55f3>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/donates",
        class: "flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:arrow-left",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "mdi:arrow-left",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div><div class="max-w-2xl mx-auto" data-v-709e55f3><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" data-v-709e55f3><div class="bg-gradient-to-r from-teal-500 to-cyan-500 px-6 py-4" data-v-709e55f3><h2 class="text-xl font-bold text-white flex items-center gap-2" data-v-709e55f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:heart",
        class: "w-6 h-6"
      }, null, _parent));
      _push(` \u0E40\u0E1E\u0E25\u0E34\u0E19\u0E14\u0E4C\u0E02\u0E2D\u0E02\u0E2D\u0E1A\u0E04\u0E38\u0E13\u0E1C\u0E39\u0E49\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E01\u0E17\u0E48\u0E32\u0E19 </h2><p class="text-white/80 text-sm mt-1" data-v-709e55f3>\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E08\u0E32\u0E01\u0E17\u0E48\u0E32\u0E19\u0E08\u0E30\u0E16\u0E39\u0E01\u0E08\u0E31\u0E14\u0E2A\u0E23\u0E23\u0E43\u0E2B\u0E49\u0E01\u0E31\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</p></div><div class="p-6" data-v-709e55f3><div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4" data-v-709e55f3><h3 class="font-semibold text-amber-800 dark:text-amber-400 mb-2 flex items-center gap-2" data-v-709e55f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:information",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` \u0E02\u0E31\u0E49\u0E19\u0E15\u0E2D\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 </h3><ol class="text-amber-700 dark:text-amber-300 text-sm space-y-1 list-decimal list-inside" data-v-709e55f3><li data-v-709e55f3>\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19\u0E40\u0E02\u0E49\u0E32\u0E1A\u0E31\u0E0D\u0E0A\u0E35 <span class="font-bold" data-v-709e55f3>677-7724-60-5</span> \u0E18\u0E19\u0E32\u0E04\u0E32\u0E23\u0E01\u0E23\u0E38\u0E07\u0E44\u0E17\u0E22</li><li data-v-709e55f3>\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E31\u0E0D\u0E0A\u0E35 <span class="font-bold" data-v-709e55f3>\u0E19\u0E32\u0E22\u0E2D\u0E38\u0E17\u0E31\u0E22 \u0E2A\u0E32\u0E40\u0E2B\u0E25\u0E47\u0E21</span></li><li data-v-709e55f3>\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E43\u0E2B\u0E49\u0E04\u0E23\u0E1A\u0E16\u0E49\u0E27\u0E19</li><li data-v-709e55f3>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</li></ol></div><form class="space-y-6" data-v-709e55f3>`);
      if (!unref(donor)) {
        _push(`<div class="space-y-4" data-v-709e55f3><div class="space-y-2" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 (\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A) </label><div class="relative" data-v-709e55f3><input${ssrRenderAttr("value", unref(searchQuery))} type="text" placeholder="\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01..." class="w-full px-4 py-3 pl-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all" data-v-709e55f3>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(isLoadingDonor) ? "mdi:loading" : "mdi:magnify",
          class: ["absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400", { "animate-spin": unref(isLoadingDonor) }]
        }, null, _parent));
        if (unref(showSearchDropdown) && unref(searchResults).length > 0) {
          _push(`<div class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg max-h-64 overflow-y-auto" data-v-709e55f3><!--[-->`);
          ssrRenderList(unref(searchResults), (user) => {
            _push(`<div class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors" data-v-709e55f3><img${ssrRenderAttr("src", user.avatar || user.profile_photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random&color=fff`)}${ssrRenderAttr("alt", user.name)} class="w-10 h-10 rounded-full object-cover" data-v-709e55f3><div class="flex-grow" data-v-709e55f3><p class="font-medium text-gray-900 dark:text-white" data-v-709e55f3>${ssrInterpolate(user.name)}</p><p class="text-xs text-gray-500" data-v-709e55f3>${ssrInterpolate(user.email)}</p>`);
            if (user.personal_code) {
              _push(`<p class="text-xs text-purple-500" data-v-709e55f3>${ssrInterpolate(user.personal_code)}</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:chevron-right",
              class: "w-5 h-5 text-gray-400"
            }, null, _parent));
            _push(`</div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(showSearchDropdown) && unref(searchQuery).length >= 2 && unref(searchResults).length === 0 && !unref(isLoadingDonor)) {
          _push(`<div class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-lg p-4 text-center" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-search",
            class: "w-8 h-8 text-gray-400 mx-auto mb-2"
          }, null, _parent));
          _push(`<p class="text-gray-500 dark:text-gray-400" data-v-709e55f3>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="flex items-center gap-3" data-v-709e55f3><div class="flex-1 border-t border-gray-200 dark:border-gray-700" data-v-709e55f3></div><span class="text-xs text-gray-400" data-v-709e55f3>\u0E2B\u0E23\u0E37\u0E2D</span><div class="flex-1 border-t border-gray-200 dark:border-gray-700" data-v-709e55f3></div></div><div class="space-y-2" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 (8 \u0E2B\u0E25\u0E31\u0E01) </label><div class="relative" data-v-709e55f3><input${ssrRenderAttr("value", unref(personalCode))} type="text" maxlength="8" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A 8 \u0E2B\u0E25\u0E31\u0E01" class="${ssrRenderClass([{ "border-red-500 focus:ring-red-500": unref(errors).personalCode }, "w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"])}" data-v-709e55f3>`);
        if (unref(isLoadingDonor)) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:loading",
            class: "absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500 animate-spin"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
        if (unref(authStore).user) {
          _push(`<div class="flex justify-end" data-v-709e55f3><button type="button" class="text-xs text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-circle",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E43\u0E0A\u0E49\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 </button></div>`);
        } else {
          _push(`<!---->`);
        }
        if (unref(errors).personalCode) {
          _push(`<p class="text-xs text-red-500 flex items-center gap-1" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:alert-circle",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(errors).personalCode)}</p>`);
        } else {
          _push(`<p class="text-xs text-gray-500 flex items-center gap-1" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:information-outline",
            class: "w-3 h-3"
          }, null, _parent));
          _push(` \u0E44\u0E21\u0E48\u0E08\u0E33\u0E40\u0E1B\u0E47\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E23\u0E30\u0E1A\u0E38\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 \u0E2B\u0E32\u0E01\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38 \u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E30\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E43\u0E2B\u0E49\u0E40\u0E1B\u0E47\u0E19 &quot;\u0E44\u0E21\u0E48\u0E1B\u0E23\u0E30\u0E2A\u0E07\u0E04\u0E4C\u0E2D\u0E2D\u0E01\u0E19\u0E32\u0E21&quot; </p>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(isLoadingDonor) && !unref(donor)) {
        _push(`<div class="animate-pulse bg-gray-100 dark:bg-gray-700 rounded-xl p-4" data-v-709e55f3><div class="flex items-center gap-4" data-v-709e55f3><div class="w-16 h-16 bg-gray-300 dark:bg-gray-600 rounded-full" data-v-709e55f3></div><div class="flex-1 space-y-2" data-v-709e55f3><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4" data-v-709e55f3></div><div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-1/2" data-v-709e55f3></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(donor)) {
        _push(`<div class="relative bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4 animate-fade-in-up" data-v-709e55f3><button type="button" class="absolute top-2 right-2 p-2 bg-white dark:bg-gray-700 rounded-full shadow hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01" data-v-709e55f3>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:close",
          class: "w-4 h-4 text-red-500"
        }, null, _parent));
        _push(`</button><div class="flex items-center gap-4" data-v-709e55f3><img${ssrRenderAttr("src", unref(donor).avatar || unref(donor).profile_photo_url || `${unref(config).public.apiBase}/storage/images/plearnd-logo.png`)}${ssrRenderAttr("alt", unref(donor).name || unref(donor).username)} class="w-16 h-16 rounded-full border-4 border-purple-500 shadow-lg object-cover" data-v-709e55f3><div data-v-709e55f3><h4 class="text-lg font-bold text-gray-900 dark:text-white" data-v-709e55f3>${ssrInterpolate(unref(donor).name || unref(donor).username)}</h4><p class="text-sm text-gray-600 dark:text-gray-400" data-v-709e55f3> \u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27: <span class="font-semibold" data-v-709e55f3>${ssrInterpolate(unref(donor).personal_code)}</span></p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="space-y-3" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19 (\u0E1A\u0E32\u0E17) </label><div class="flex flex-wrap gap-2" data-v-709e55f3><!--[-->`);
      ssrRenderList(quickSelectOptions, (amount) => {
        _push(`<button type="button" class="${ssrRenderClass([
          "px-3 py-1.5 rounded-lg text-sm font-medium transition-all border",
          unref(totalMoneySupport) === amount ? "bg-purple-500 border-purple-500 text-white shadow-md" : "bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-purple-400"
        ])}" data-v-709e55f3>${ssrInterpolate(amount.toLocaleString())}</button>`);
      });
      _push(`<!--]-->`);
      if (!quickSelectOptions.includes(unref(totalMoneySupport))) {
        _push(`<button type="button" class="px-3 py-1.5 rounded-lg text-sm font-medium border bg-purple-500 border-purple-500 text-white shadow-md" data-v-709e55f3>${ssrInterpolate(unref(totalMoneySupport).toLocaleString())}</button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><select class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-lg font-semibold text-center transition-all" data-v-709e55f3><!--[-->`);
      ssrRenderList(totalMoneySupportOptions, (amount, idx) => {
        _push(`<option${ssrRenderAttr("value", idx)} data-v-709e55f3${ssrIncludeBooleanAttr(Array.isArray(unref(moneyIndexSelected)) ? ssrLooseContain(unref(moneyIndexSelected), idx) : ssrLooseEqual(unref(moneyIndexSelected), idx)) ? " selected" : ""}>${ssrInterpolate(amount.toLocaleString())} \u0E1A\u0E32\u0E17 </option>`);
      });
      _push(`<!--]--></select><div class="flex items-center justify-center gap-2 py-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800/30" data-v-709e55f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:star-circle",
        class: "w-8 h-8 text-amber-500"
      }, null, _parent));
      _push(`<span class="text-lg font-bold text-purple-600 dark:text-purple-400" data-v-709e55f3>${ssrInterpolate(unref(estimatedPoints).toLocaleString())} \u0E41\u0E15\u0E49\u0E21 </span></div></div>`);
      if (unref(authStore).user) {
        _push(`<div class="space-y-3" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E27\u0E34\u0E18\u0E35\u0E01\u0E32\u0E23\u0E0A\u0E33\u0E23\u0E30 </label><div class="grid grid-cols-1 md:grid-cols-3 gap-3" data-v-709e55f3><button type="button" class="${ssrRenderClass([
          "p-4 rounded-xl border-2 text-left transition-all",
          unref(paymentMethod) === "slip" ? "border-teal-500 bg-teal-50 dark:bg-teal-900/20" : "border-gray-200 dark:border-gray-700 hover:border-teal-300"
        ])}" data-v-709e55f3><div class="flex items-center gap-3" data-v-709e55f3><div class="${ssrRenderClass(["w-10 h-10 rounded-lg flex items-center justify-center", unref(paymentMethod) === "slip" ? "bg-teal-500" : "bg-gray-200 dark:bg-gray-700"])}" data-v-709e55f3>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:receipt",
          class: ["w-5 h-5", unref(paymentMethod) === "slip" ? "text-white" : "text-gray-500"]
        }, null, _parent));
        _push(`</div><div data-v-709e55f3><p class="font-medium text-gray-900 dark:text-white" data-v-709e55f3>\u0E2A\u0E25\u0E34\u0E1B\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19</p><p class="text-xs text-gray-500" data-v-709e55f3>\u0E23\u0E2D Admin \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p></div></div></button><button type="button"${ssrIncludeBooleanAttr((((_a = unref(authStore).user) == null ? void 0 : _a.wallet) || 0) < unref(totalMoneySupport)) ? " disabled" : ""} class="${ssrRenderClass([
          "p-4 rounded-xl border-2 text-left transition-all disabled:opacity-50 disabled:cursor-not-allowed",
          unref(paymentMethod) === "wallet" ? "border-green-500 bg-green-50 dark:bg-green-900/20" : "border-gray-200 dark:border-gray-700 hover:border-green-300"
        ])}" data-v-709e55f3><div class="flex items-center gap-3" data-v-709e55f3><div class="${ssrRenderClass(["w-10 h-10 rounded-lg flex items-center justify-center", unref(paymentMethod) === "wallet" ? "bg-green-500" : "bg-gray-200 dark:bg-gray-700"])}" data-v-709e55f3>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:wallet",
          class: ["w-5 h-5", unref(paymentMethod) === "wallet" ? "text-white" : "text-gray-500"]
        }, null, _parent));
        _push(`</div><div data-v-709e55f3><p class="font-medium text-gray-900 dark:text-white" data-v-709e55f3>\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19</p><p class="text-xs text-green-600 dark:text-green-400 font-semibold" data-v-709e55f3>${ssrInterpolate((((_b = unref(authStore).user) == null ? void 0 : _b.wallet) || 0).toLocaleString())} \u0E1A\u0E32\u0E17 </p></div></div></button><button type="button"${ssrIncludeBooleanAttr((((_c = unref(authStore).user) == null ? void 0 : _c.points) || 0) < unref(pointsRequired)) ? " disabled" : ""} class="${ssrRenderClass([
          "p-4 rounded-xl border-2 text-left transition-all disabled:opacity-50 disabled:cursor-not-allowed",
          unref(paymentMethod) === "points" ? "border-purple-500 bg-purple-50 dark:bg-purple-900/20" : "border-gray-200 dark:border-gray-700 hover:border-purple-300"
        ])}" data-v-709e55f3><div class="flex items-center gap-3" data-v-709e55f3><div class="${ssrRenderClass(["w-10 h-10 rounded-lg flex items-center justify-center", unref(paymentMethod) === "points" ? "bg-purple-500" : "bg-gray-200 dark:bg-gray-700"])}" data-v-709e55f3>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:star-circle",
          class: ["w-5 h-5", unref(paymentMethod) === "points" ? "text-white" : "text-gray-500"]
        }, null, _parent));
        _push(`</div><div data-v-709e55f3><p class="font-medium text-gray-900 dark:text-white" data-v-709e55f3>\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21</p><p class="text-xs text-purple-600 dark:text-purple-400 font-semibold" data-v-709e55f3>${ssrInterpolate((((_d = unref(authStore).user) == null ? void 0 : _d.points) || 0).toLocaleString())} \u0E41\u0E15\u0E49\u0E21 </p></div></div></button></div>`);
        if (unref(paymentMethod) === "points") {
          _push(`<div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-3" data-v-709e55f3><p class="text-sm text-purple-700 dark:text-purple-300" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:information",
            class: "w-4 h-4 inline mr-1"
          }, null, _parent));
          _push(` \u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49 <span class="font-bold" data-v-709e55f3>${ssrInterpolate(unref(pointsRequired).toLocaleString())}</span> \u0E41\u0E15\u0E49\u0E21 (\u0E2D\u0E31\u0E15\u0E23\u0E32 1 \u0E1A\u0E32\u0E17 = 100 \u0E41\u0E15\u0E49\u0E21) </p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(paymentMethod) === "slip" || !unref(authStore).user) {
        _push(`<div class="space-y-2" data-v-709e55f3><div class="flex justify-between items-center" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E2A\u0E25\u0E34\u0E1B\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 <span class="text-red-500" data-v-709e55f3>*</span></label>`);
        if (unref(errors).slip) {
          _push(`<span class="text-xs text-red-500 flex items-center gap-1" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:alert-circle",
            class: "w-3 h-3"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(errors).slip)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (unref(slipImage)) {
          _push(`<div class="relative group" data-v-709e55f3><div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors rounded-xl pointer-events-none" data-v-709e55f3></div><img${ssrRenderAttr("src", unref(slipImage).url)} alt="Slip" class="w-full max-h-80 object-contain rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900" data-v-709e55f3><button type="button" class="absolute top-2 right-2 p-2 bg-white/90 dark:bg-gray-800/90 hover:bg-red-500 hover:text-white text-gray-700 rounded-full shadow-lg transition-all backdrop-blur-sm" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:delete",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div>`);
        } else {
          _push(`<div class="${ssrRenderClass([
            "border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer relative overflow-hidden",
            unref(dragging) ? "border-purple-500 bg-purple-50 dark:bg-purple-900/20 scale-[1.01]" : "border-gray-300 dark:border-gray-600 hover:border-purple-400 hover:bg-gray-50 dark:hover:bg-gray-700/50",
            unref(errors).slip ? "border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/10" : ""
          ])}" data-v-709e55f3>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "mdi:cloud-upload",
            class: "w-12 h-12 mx-auto text-gray-400 mb-3"
          }, null, _parent));
          _push(`<p class="text-gray-600 dark:text-gray-400 mb-2" data-v-709e55f3><span class="font-semibold text-purple-600 dark:text-purple-400" data-v-709e55f3>\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E1F\u0E25\u0E4C</span> \u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E21\u0E32\u0E27\u0E32\u0E07\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48 </p><p class="text-xs text-gray-500" data-v-709e55f3>PNG, JPG, GIF (\u0E44\u0E21\u0E48\u0E40\u0E01\u0E34\u0E19 4MB)</p><input type="file" accept="image/png, image/jpeg, image/gif" class="hidden" data-v-709e55f3></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-v-709e55f3><div class="space-y-2" data-v-709e55f3><div class="flex justify-between" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 <span class="text-red-500" data-v-709e55f3>*</span></label>`);
      if (unref(errors).dateTime) {
        _push(`<span class="text-xs text-red-500 md:hidden" data-v-709e55f3>${ssrInterpolate(unref(errors).dateTime)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><input${ssrRenderAttr("value", unref(transferDate))} type="date"${ssrRenderAttr("max", unref(today))} class="${ssrRenderClass([{ "border-red-500": unref(errors).dateTime }, "w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"])}" data-v-709e55f3></div><div class="space-y-2" data-v-709e55f3><label class="block text-sm font-medium text-gray-700 dark:text-gray-300" data-v-709e55f3> \u0E40\u0E27\u0E25\u0E32\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 <span class="text-red-500" data-v-709e55f3>*</span></label><div class="flex gap-2" data-v-709e55f3><div class="relative flex-1" data-v-709e55f3><select class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white appearance-none" data-v-709e55f3><!--[-->`);
      ssrRenderList(unref(hours), (h) => {
        _push(`<option${ssrRenderAttr("value", h)} data-v-709e55f3${ssrIncludeBooleanAttr(Array.isArray(unref(selectedHour)) ? ssrLooseContain(unref(selectedHour), h) : ssrLooseEqual(unref(selectedHour), h)) ? " selected" : ""}>${ssrInterpolate(h)}</option>`);
      });
      _push(`<!--]--></select>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:chevron-down",
        class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none"
      }, null, _parent));
      _push(`<span class="absolute -top-6 left-0 text-xs text-gray-500" data-v-709e55f3>\u0E19\u0E32\u0E2C\u0E34\u0E01\u0E32</span></div><span class="flex items-center text-2xl font-bold text-gray-400" data-v-709e55f3>:</span><div class="relative flex-1" data-v-709e55f3><select class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white appearance-none" data-v-709e55f3><!--[-->`);
      ssrRenderList(unref(minutes), (m) => {
        _push(`<option${ssrRenderAttr("value", m)} data-v-709e55f3${ssrIncludeBooleanAttr(Array.isArray(unref(selectedMinute)) ? ssrLooseContain(unref(selectedMinute), m) : ssrLooseEqual(unref(selectedMinute), m)) ? " selected" : ""}>${ssrInterpolate(m)}</option>`);
      });
      _push(`<!--]--></select>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:chevron-down",
        class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none"
      }, null, _parent));
      _push(`<span class="absolute -top-6 left-0 text-xs text-gray-500" data-v-709e55f3>\u0E19\u0E32\u0E17\u0E35</span></div></div></div></div>`);
      if (unref(errors).dateTime) {
        _push(`<p class="text-xs text-red-500 -mt-2 hidden md:block" data-v-709e55f3>${ssrInterpolate(unref(errors).dateTime)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-3" data-v-709e55f3><p class="text-sm text-red-600 dark:text-red-400 flex items-start gap-2" data-v-709e55f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:alert",
        class: "w-5 h-5 flex-shrink-0 mt-0.5"
      }, null, _parent));
      _push(`<span data-v-709e55f3> \u0E01\u0E23\u0E38\u0E13\u0E32\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E30\u0E40\u0E27\u0E25\u0E32\u0E43\u0E2B\u0E49\u0E15\u0E23\u0E07\u0E01\u0E31\u0E1A\u0E43\u0E1A\u0E2A\u0E25\u0E34\u0E1B\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 <br class="hidden sm:block" data-v-709e55f3> (\u0E2B\u0E32\u0E01\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E08\u0E30\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E04\u0E37\u0E19\u0E40\u0E07\u0E34\u0E19\u0E44\u0E14\u0E49) </span></p></div><div class="flex flex-col sm:flex-row gap-3 pt-4" data-v-709e55f3><button type="submit"${ssrIncludeBooleanAttr(unref(isLoading)) ? " disabled" : ""} class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all" data-v-709e55f3>`);
      if (unref(isLoading)) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:loading",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:content-save",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/donates",
        class: "flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "mdi:close",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></form></div></div></div>`);
      ssrRenderTeleport(_push, (_push2) => {
        if (unref(showSuccessModal)) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-all" data-v-709e55f3><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center animate-bounce-in" data-v-709e55f3><div class="w-20 h-20 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center" data-v-709e55f3>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:check-circle",
            class: "w-12 h-12 text-green-500"
          }, null, _parent));
          _push2(`</div><h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2" data-v-709e55f3>\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E21\u0E1A\u0E39\u0E23\u0E13\u0E4C!</h3><p class="text-gray-600 dark:text-gray-400 mb-6" data-v-709e55f3> \u0E02\u0E2D\u0E1A\u0E04\u0E38\u0E13\u0E17\u0E35\u0E48\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C\u0E40\u0E1E\u0E25\u0E34\u0E19\u0E14\u0E4C \u0E17\u0E32\u0E07\u0E17\u0E35\u0E21\u0E07\u0E32\u0E19\u0E08\u0E30\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E25\u0E30\u0E17\u0E33\u0E01\u0E32\u0E23\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E43\u0E2B\u0E49\u0E40\u0E23\u0E47\u0E27\u0E17\u0E35\u0E48\u0E2A\u0E38\u0E14 </p><div class="flex gap-3" data-v-709e55f3><button class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors" data-v-709e55f3> \u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21 </button>`);
          _push2(ssrRenderComponent(_component_NuxtLink, {
            to: "/earn/donates",
            class: "flex-1 px-4 py-3 bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 text-white font-semibold rounded-xl transition-all text-center",
            onClick: ($event) => showSuccessModal.value = false
          }, {
            default: withCtx((_, _push3, _parent2, _scopeId) => {
              if (_push3) {
                _push3(` \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21 `);
              } else {
                return [
                  createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21 ")
                ];
              }
            }),
            _: 1
          }, _parent));
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/donates/create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const create = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-709e55f3"]]);

export { create as default };
//# sourceMappingURL=create-BWhLDrN_.mjs.map
