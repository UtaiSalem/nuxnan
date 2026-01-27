import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, unref, withCtx, createVNode, toDisplayString, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderTeleport, ssrRenderAttr, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { _ as _export_sfc, f as useHead, d as useAuthStore, i as useApi, n as navigateTo, b as useRuntimeConfig } from './server.mjs';
import { D as DonorCard } from './DonorCard-DlRLN8Lr.mjs';
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
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21 - Nuxni"
    });
    const authStore = useAuthStore();
    useApi();
    const donates = ref([]);
    const isLoading = ref(true);
    const isLoadingMore = ref(false);
    const processingDonateId = ref(null);
    const error = ref(null);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const totalDonates = ref(0);
    const dailyLimitReachedDonates = ref(/* @__PURE__ */ new Set());
    const selectedDonate = ref(null);
    ref(-1);
    const isShowingDonorModal = ref(false);
    const countdownSeconds = ref(10);
    ref(null);
    const isProcessingDonate = ref(false);
    ref(0);
    const animatedPoints = ref(0);
    ref(null);
    const parsePoints = (points) => {
      if (points === void 0 || points === null) return 0;
      if (typeof points === "string") {
        return parseInt(points.replace(/,/g, "")) || 0;
      }
      return points;
    };
    computed(() => {
      return donates.value.reduce((sum, d) => {
        const points = typeof d.remaining_points === "string" ? parseInt(d.remaining_points.replace(/,/g, "")) : d.remaining_points || 0;
        return sum + points;
      }, 0);
    });
    computed(() => {
      return donates.value.filter((d) => {
        const points = typeof d.remaining_points === "string" ? parseInt(d.remaining_points.replace(/,/g, "")) : d.remaining_points || 0;
        return points > 0;
      }).length;
    });
    const fetchDonates = async (page = 1) => {
      var _a, _b, _c;
      if (page === 1) {
        isLoading.value = true;
      } else {
        isLoadingMore.value = true;
      }
      error.value = null;
      try {
        const response = await $fetch(`/api/donates/available?page=${page}`, {
          baseURL: useRuntimeConfig().public.apiBase
        });
        if (response == null ? void 0 : response.donates) {
          if (page === 1) {
            donates.value = response.donates.data || [];
          } else {
            donates.value = [...donates.value, ...response.donates.data || []];
          }
          currentPage.value = ((_a = response.donates.meta) == null ? void 0 : _a.current_page) || page;
          lastPage.value = ((_b = response.donates.meta) == null ? void 0 : _b.last_page) || 1;
          totalDonates.value = ((_c = response.donates.meta) == null ? void 0 : _c.total) || 0;
        }
      } catch (err) {
        console.error("Error fetching donates:", err);
        error.value = "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E44\u0E14\u0E49";
      } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
      }
    };
    const goToCreateDonate = () => {
      if (!authStore.isAuthenticated) {
        Swal.fire({
          icon: "warning",
          title: "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A",
          text: "\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E01\u0E48\u0E2D\u0E19\u0E08\u0E36\u0E07\u0E08\u0E30\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E44\u0E14\u0E49",
          confirmButtonText: "\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A",
          confirmButtonColor: "#3085d6",
          showCancelButton: true,
          cancelButtonText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
        }).then((result) => {
          if (result.isConfirmed) {
            navigateTo("/auth");
          }
        });
        return;
      }
      navigateTo("/earn/donates/create");
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<!--[--><div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900" data-v-d10b181d><div class="max-w-6xl mx-auto" data-v-d10b181d><div class="text-center mb-8" data-v-d10b181d><div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center shadow-lg" data-v-d10b181d>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:hand-coin-outline",
        class: "w-10 h-10 text-white"
      }, null, _parent));
      _push(`</div><h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" data-v-d10b181d> \u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21 </h1><p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto" data-v-d10b181d> \u0E23\u0E27\u0E1A\u0E23\u0E27\u0E21\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E25\u0E01\u0E23\u0E31\u0E1A\u0E02\u0E2D\u0E07\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E1E\u0E34\u0E40\u0E28\u0E29 </p></div>`);
      if (unref(authStore).isAuthenticated) {
        _push(`<div class="mb-8" data-v-d10b181d>`);
        _push(ssrRenderComponent(_sfc_main$1, { class: "bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 border-0" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-white" data-v-d10b181d${_scopeId}><div class="flex items-center gap-4" data-v-d10b181d${_scopeId}><div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur" data-v-d10b181d${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:star-circle",
                class: "w-10 h-10"
              }, null, _parent2, _scopeId));
              _push2(`</div><div data-v-d10b181d${_scopeId}><p class="text-white/80 text-sm" data-v-d10b181d${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p><p class="text-3xl font-bold" data-v-d10b181d${_scopeId}>${ssrInterpolate(unref(authStore).points.toLocaleString())}</p></div></div><button class="px-6 py-3 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow-lg" data-v-d10b181d${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:gift-outline",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 </button></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col sm:flex-row items-center justify-between gap-4 text-white" }, [
                  createVNode("div", { class: "flex items-center gap-4" }, [
                    createVNode("div", { class: "w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:star-circle",
                        class: "w-10 h-10"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-white/80 text-sm" }, "\u0E41\u0E15\u0E49\u0E21\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13"),
                      createVNode("p", { class: "text-3xl font-bold" }, toDisplayString(unref(authStore).points.toLocaleString()), 1)
                    ])
                  ]),
                  createVNode("button", {
                    onClick: goToCreateDonate,
                    class: "px-6 py-3 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow-lg"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:gift-outline",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 ")
                  ])
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_sfc_main$1, { class: "mb-8" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2" data-v-d10b181d${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:information-outline",
              class: "w-6 h-6 text-blue-500"
            }, null, _parent2, _scopeId));
            _push2(` \u0E27\u0E34\u0E18\u0E35\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21 </h2><div class="grid grid-cols-1 md:grid-cols-3 gap-6" data-v-d10b181d${_scopeId}><div class="flex items-start gap-3" data-v-d10b181d${_scopeId}><div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0" data-v-d10b181d${_scopeId}><span class="text-amber-600 font-bold" data-v-d10b181d${_scopeId}>1</span></div><div data-v-d10b181d${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white" data-v-d10b181d${_scopeId}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21\u0E40\u0E2B\u0E25\u0E37\u0E2D\u0E2D\u0E22\u0E39\u0E48</p></div></div><div class="flex items-start gap-3" data-v-d10b181d${_scopeId}><div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0" data-v-d10b181d${_scopeId}><span class="text-amber-600 font-bold" data-v-d10b181d${_scopeId}>2</span></div><div data-v-d10b181d${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white" data-v-d10b181d${_scopeId}>\u0E01\u0E14\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId}>\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A 240 \u0E41\u0E15\u0E49\u0E21</p></div></div><div class="flex items-start gap-3" data-v-d10b181d${_scopeId}><div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0" data-v-d10b181d${_scopeId}><span class="text-amber-600 font-bold" data-v-d10b181d${_scopeId}>3</span></div><div data-v-d10b181d${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white" data-v-d10b181d${_scopeId}>\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId}>\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E43\u0E19\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E1A\u0E19\u0E41\u0E1E\u0E25\u0E15\u0E1F\u0E2D\u0E23\u0E4C\u0E21</p></div></div></div>`);
          } else {
            return [
              createVNode("h2", { class: "text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:information-outline",
                  class: "w-6 h-6 text-blue-500"
                }),
                createTextVNode(" \u0E27\u0E34\u0E18\u0E35\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21 ")
              ]),
              createVNode("div", { class: "grid grid-cols-1 md:grid-cols-3 gap-6" }, [
                createVNode("div", { class: "flex items-start gap-3" }, [
                  createVNode("div", { class: "w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0" }, [
                    createVNode("span", { class: "text-amber-600 font-bold" }, "1")
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21\u0E40\u0E2B\u0E25\u0E37\u0E2D\u0E2D\u0E22\u0E39\u0E48")
                  ])
                ]),
                createVNode("div", { class: "flex items-start gap-3" }, [
                  createVNode("div", { class: "w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0" }, [
                    createVNode("span", { class: "text-amber-600 font-bold" }, "2")
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E01\u0E14\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, '\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21 "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21" \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E23\u0E31\u0E1A 240 \u0E41\u0E15\u0E49\u0E21')
                  ])
                ]),
                createVNode("div", { class: "flex items-start gap-3" }, [
                  createVNode("div", { class: "w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0" }, [
                    createVNode("span", { class: "text-amber-600 font-bold" }, "3")
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, "\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21"),
                    createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E43\u0E0A\u0E49\u0E41\u0E15\u0E49\u0E21\u0E43\u0E19\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E1A\u0E19\u0E41\u0E1E\u0E25\u0E15\u0E1F\u0E2D\u0E23\u0E4C\u0E21")
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (isLoading.value) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-v-d10b181d><!--[-->`);
        ssrRenderList(6, (i) => {
          _push(`<div class="animate-pulse" data-v-d10b181d>`);
          _push(ssrRenderComponent(_sfc_main$1, null, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="space-y-4" data-v-d10b181d${_scopeId}><div class="flex items-center gap-4" data-v-d10b181d${_scopeId}><div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full" data-v-d10b181d${_scopeId}></div><div class="flex-1 space-y-2" data-v-d10b181d${_scopeId}><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4" data-v-d10b181d${_scopeId}></div><div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2" data-v-d10b181d${_scopeId}></div></div></div><div class="h-20 bg-gray-200 dark:bg-gray-700 rounded" data-v-d10b181d${_scopeId}></div></div>`);
              } else {
                return [
                  createVNode("div", { class: "space-y-4" }, [
                    createVNode("div", { class: "flex items-center gap-4" }, [
                      createVNode("div", { class: "w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full" }),
                      createVNode("div", { class: "flex-1 space-y-2" }, [
                        createVNode("div", { class: "h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4" }),
                        createVNode("div", { class: "h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2" })
                      ])
                    ]),
                    createVNode("div", { class: "h-20 bg-gray-200 dark:bg-gray-700 rounded" })
                  ])
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (error.value) {
        _push(`<div class="text-center py-12" data-v-d10b181d>`);
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:alert-circle-outline",
                class: "w-16 h-16 mx-auto text-red-500 mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2" data-v-d10b181d${_scopeId}>\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h2><p class="text-gray-500 dark:text-gray-400 mb-4" data-v-d10b181d${_scopeId}>${ssrInterpolate(error.value)}</p><button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" data-v-d10b181d${_scopeId}> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </button>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:alert-circle-outline",
                  class: "w-16 h-16 mx-auto text-red-500 mb-4"
                }),
                createVNode("h2", { class: "text-xl font-semibold text-gray-900 dark:text-white mb-2" }, "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"),
                createVNode("p", { class: "text-gray-500 dark:text-gray-400 mb-4" }, toDisplayString(error.value), 1),
                createVNode("button", {
                  onClick: ($event) => fetchDonates(1),
                  class: "px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                }, " \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 ", 8, ["onClick"])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else if (donates.value.length === 0) {
        _push(`<div class="text-center py-12" data-v-d10b181d>`);
        _push(ssrRenderComponent(_sfc_main$1, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:hand-coin-outline",
                class: "w-16 h-16 mx-auto text-gray-400 mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2" data-v-d10b181d${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</h2><p class="text-gray-500 dark:text-gray-400 mb-4" data-v-d10b181d${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49 \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19!</p><button class="px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 mx-auto" data-v-d10b181d${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:gift-outline",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 </button>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:hand-coin-outline",
                  class: "w-16 h-16 mx-auto text-gray-400 mb-4"
                }),
                createVNode("h2", { class: "text-xl font-semibold text-gray-900 dark:text-white mb-2" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19"),
                createVNode("p", { class: "text-gray-500 dark:text-gray-400 mb-4" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1C\u0E39\u0E49\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49 \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19!"),
                createVNode("button", {
                  onClick: goToCreateDonate,
                  class: "px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 mx-auto"
                }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:gift-outline",
                    class: "w-5 h-5"
                  }),
                  createTextVNode(" \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 ")
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<div data-v-d10b181d><div class="flex items-center justify-between mb-6" data-v-d10b181d><h2 class="text-xl font-bold text-gray-900 dark:text-white" data-v-d10b181d> \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 (${ssrInterpolate(totalDonates.value)}) </h2><button class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 text-sm" data-v-d10b181d>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:plus",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19 </button></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-v-d10b181d><!--[-->`);
        ssrRenderList(donates.value, (donate, index2) => {
          _push(`<div class="relative" data-v-d10b181d>`);
          if (donate.status === 0) {
            _push(`<div class="absolute top-4 right-4 z-10 px-3 py-1.5 bg-amber-500/90 backdrop-blur-sm text-white text-xs font-semibold rounded-full flex items-center gap-1.5 shadow-lg" data-v-d10b181d>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:clock-outline",
              class: "w-4 h-4"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E23\u0E2D\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(ssrRenderComponent(DonorCard, { donate }, null, _parent));
          _push(`<div class="absolute bottom-4 left-4 right-4" data-v-d10b181d><button${ssrIncludeBooleanAttr(processingDonateId.value === donate.id || parsePoints(donate.remaining_points) < 270 || donate.status === 0 || dailyLimitReachedDonates.value.has(donate.id)) ? " disabled" : ""} class="${ssrRenderClass([
            "w-full py-3 font-semibold rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg",
            donate.status === 0 || dailyLimitReachedDonates.value.has(donate.id) ? "bg-gray-400 dark:bg-gray-600 text-white cursor-not-allowed" : "bg-gradient-to-r from-yellow-500 to-orange-500 text-white hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
          ])}" data-v-d10b181d>`);
          if (processingDonateId.value === donate.id) {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23...</span><!--]-->`);
          } else if (donate.status === 0) {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:clock-outline",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</span><!--]-->`);
          } else if (dailyLimitReachedDonates.value.has(donate.id)) {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:clock-check-outline",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E04\u0E23\u0E1A 10 \u0E04\u0E23\u0E31\u0E49\u0E07\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49</span><!--]-->`);
          } else if (parsePoints(donate.remaining_points) < 270) {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:close-circle",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E41\u0E15\u0E49\u0E21\u0E2B\u0E21\u0E14\u0E41\u0E25\u0E49\u0E27</span><!--]-->`);
          } else {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:hand-coin",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E23\u0E31\u0E1A 240 \u0E41\u0E15\u0E49\u0E21</span><!--]-->`);
          }
          _push(`</button></div></div>`);
        });
        _push(`<!--]--></div>`);
        if (currentPage.value < lastPage.value) {
          _push(`<div class="text-center mt-8" data-v-d10b181d><button${ssrIncludeBooleanAttr(isLoadingMore.value) ? " disabled" : ""} class="px-8 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 mx-auto" data-v-d10b181d>`);
          if (isLoadingMore.value) {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:loading",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14...</span><!--]-->`);
          } else {
            _push(`<!--[-->`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "mdi:chevron-down",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span data-v-d10b181d>\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</span><!--]-->`);
          }
          _push(`</button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(`<div class="mt-12" data-v-d10b181d><h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center" data-v-d10b181d> \u0E27\u0E34\u0E18\u0E35\u0E2D\u0E37\u0E48\u0E19\u0E43\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21 </h2><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" data-v-d10b181d>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/newsfeed",
        class: "block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="text-center" data-v-d10b181d${_scopeId2}><div class="w-14 h-14 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" data-v-d10b181d${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:thumb-up",
                    class: "w-7 h-7 text-blue-600 dark:text-blue-400"
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white mb-1" data-v-d10b181d${_scopeId2}>\u0E16\u0E39\u0E01\u0E43\u0E08\u0E42\u0E1E\u0E2A\u0E15\u0E4C</h3><p class="text-xs text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E16\u0E39\u0E01\u0E01\u0E14\u0E16\u0E39\u0E01\u0E43\u0E08</p></div>`);
                } else {
                  return [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:thumb-up",
                          class: "w-7 h-7 text-blue-600 dark:text-blue-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E42\u0E1E\u0E2A\u0E15\u0E4C"),
                      createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E16\u0E39\u0E01\u0E01\u0E14\u0E16\u0E39\u0E01\u0E43\u0E08")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:thumb-up",
                        class: "w-7 h-7 text-blue-600 dark:text-blue-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E42\u0E1E\u0E2A\u0E15\u0E4C"),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E16\u0E39\u0E01\u0E01\u0E14\u0E16\u0E39\u0E01\u0E43\u0E08")
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/newsfeed",
        class: "block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="text-center" data-v-d10b181d${_scopeId2}><div class="w-14 h-14 mx-auto mb-3 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" data-v-d10b181d${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:comment-text",
                    class: "w-7 h-7 text-green-600 dark:text-green-400"
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white mb-1" data-v-d10b181d${_scopeId2}>\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</h3><p class="text-xs text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E40\u0E2B\u0E47\u0E19</p></div>`);
                } else {
                  return [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:comment-text",
                          class: "w-7 h-7 text-green-600 dark:text-green-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19"),
                      createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E40\u0E2B\u0E47\u0E19")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:comment-text",
                        class: "w-7 h-7 text-green-600 dark:text-green-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19"),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E40\u0E2B\u0E47\u0E19")
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/courses",
        class: "block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="text-center" data-v-d10b181d${_scopeId2}><div class="w-14 h-14 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" data-v-d10b181d${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:school",
                    class: "w-7 h-7 text-purple-600 dark:text-purple-400"
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white mb-1" data-v-d10b181d${_scopeId2}>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</h3><p class="text-xs text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
                } else {
                  return [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:school",
                          class: "w-7 h-7 text-purple-600 dark:text-purple-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"),
                      createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:school",
                        class: "w-7 h-7 text-purple-600 dark:text-purple-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19")
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/quests",
        class: "block"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="text-center" data-v-d10b181d${_scopeId2}><div class="w-14 h-14 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" data-v-d10b181d${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "mdi:trophy",
                    class: "w-7 h-7 text-amber-600 dark:text-amber-400"
                  }, null, _parent3, _scopeId2));
                  _push3(`</div><h3 class="font-semibold text-gray-900 dark:text-white mb-1" data-v-d10b181d${_scopeId2}>\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08</h3><p class="text-xs text-gray-500 dark:text-gray-400" data-v-d10b181d${_scopeId2}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08</p></div>`);
                } else {
                  return [
                    createVNode("div", { class: "text-center" }, [
                      createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                        createVNode(unref(Icon), {
                          icon: "mdi:trophy",
                          class: "w-7 h-7 text-amber-600 dark:text-amber-400"
                        })
                      ]),
                      createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08"),
                      createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$1, { class: "h-full hover:shadow-lg transition-shadow cursor-pointer group" }, {
                default: withCtx(() => [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "w-14 h-14 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" }, [
                      createVNode(unref(Icon), {
                        icon: "mdi:trophy",
                        class: "w-7 h-7 text-amber-600 dark:text-amber-400"
                      })
                    ]),
                    createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1" }, "\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08"),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E08\u0E32\u0E01\u0E01\u0E32\u0E23\u0E17\u0E33\u0E20\u0E32\u0E23\u0E01\u0E34\u0E08")
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="mt-12" data-v-d10b181d>`);
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2" data-v-d10b181d${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:help-circle-outline",
              class: "w-6 h-6 text-blue-500"
            }, null, _parent2, _scopeId));
            _push2(` \u0E04\u0E33\u0E16\u0E32\u0E21\u0E17\u0E35\u0E48\u0E1E\u0E1A\u0E1A\u0E48\u0E2D\u0E22 </h2><div class="space-y-4" data-v-d10b181d${_scopeId}><div class="border-b border-gray-200 dark:border-gray-700 pb-4" data-v-d10b181d${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white mb-2" data-v-d10b181d${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E17\u0E33\u0E2D\u0E30\u0E44\u0E23\u0E44\u0E14\u0E49\u0E1A\u0E49\u0E32\u0E07?</h3><p class="text-sm text-gray-600 dark:text-gray-400" data-v-d10b181d${_scopeId}>\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E43\u0E19\u0E01\u0E32\u0E23\u0E01\u0E14\u0E16\u0E39\u0E01\u0E43\u0E08, \u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19, \u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C \u0E41\u0E25\u0E30\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E2D\u0E37\u0E48\u0E19\u0E46 \u0E1A\u0E19\u0E41\u0E1E\u0E25\u0E15\u0E1F\u0E2D\u0E23\u0E4C\u0E21</p></div><div class="border-b border-gray-200 dark:border-gray-700 pb-4" data-v-d10b181d${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white mb-2" data-v-d10b181d${_scopeId}>\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E44\u0E14\u0E49\u0E01\u0E35\u0E48\u0E04\u0E23\u0E31\u0E49\u0E07\u0E15\u0E48\u0E2D\u0E27\u0E31\u0E19?</h3><p class="text-sm text-gray-600 dark:text-gray-400" data-v-d10b181d${_scopeId}>\u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14\u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E23\u0E31\u0E49\u0E07\u0E43\u0E19\u0E01\u0E32\u0E23\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 \u0E02\u0E36\u0E49\u0E19\u0E2D\u0E22\u0E39\u0E48\u0E01\u0E31\u0E1A\u0E08\u0E33\u0E19\u0E27\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48</p></div><div data-v-d10b181d${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white mb-2" data-v-d10b181d${_scopeId}>\u0E09\u0E31\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E44\u0E14\u0E49\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E44\u0E23?</h3><p class="text-sm text-gray-600 dark:text-gray-400" data-v-d10b181d${_scopeId}>\u0E04\u0E38\u0E13\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E44\u0E14\u0E49\u0E42\u0E14\u0E22\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19&quot; \u0E41\u0E25\u0E30\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 \u0E41\u0E15\u0E49\u0E21\u0E08\u0E30\u0E16\u0E39\u0E01\u0E04\u0E33\u0E19\u0E27\u0E13\u0E08\u0E32\u0E01\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 (1 \u0E1A\u0E32\u0E17 = 1,080 \u0E41\u0E15\u0E49\u0E21)</p></div></div>`);
          } else {
            return [
              createVNode("h2", { class: "text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2" }, [
                createVNode(unref(Icon), {
                  icon: "mdi:help-circle-outline",
                  class: "w-6 h-6 text-blue-500"
                }),
                createTextVNode(" \u0E04\u0E33\u0E16\u0E32\u0E21\u0E17\u0E35\u0E48\u0E1E\u0E1A\u0E1A\u0E48\u0E2D\u0E22 ")
              ]),
              createVNode("div", { class: "space-y-4" }, [
                createVNode("div", { class: "border-b border-gray-200 dark:border-gray-700 pb-4" }, [
                  createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-2" }, "\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E17\u0E33\u0E2D\u0E30\u0E44\u0E23\u0E44\u0E14\u0E49\u0E1A\u0E49\u0E32\u0E07?"),
                  createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E43\u0E19\u0E01\u0E32\u0E23\u0E01\u0E14\u0E16\u0E39\u0E01\u0E43\u0E08, \u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19, \u0E41\u0E0A\u0E23\u0E4C\u0E42\u0E1E\u0E2A\u0E15\u0E4C \u0E41\u0E25\u0E30\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E2D\u0E37\u0E48\u0E19\u0E46 \u0E1A\u0E19\u0E41\u0E1E\u0E25\u0E15\u0E1F\u0E2D\u0E23\u0E4C\u0E21")
                ]),
                createVNode("div", { class: "border-b border-gray-200 dark:border-gray-700 pb-4" }, [
                  createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-2" }, "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E44\u0E14\u0E49\u0E01\u0E35\u0E48\u0E04\u0E23\u0E31\u0E49\u0E07\u0E15\u0E48\u0E2D\u0E27\u0E31\u0E19?"),
                  createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, "\u0E44\u0E21\u0E48\u0E08\u0E33\u0E01\u0E31\u0E14\u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E23\u0E31\u0E49\u0E07\u0E43\u0E19\u0E01\u0E32\u0E23\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21 \u0E02\u0E36\u0E49\u0E19\u0E2D\u0E22\u0E39\u0E48\u0E01\u0E31\u0E1A\u0E08\u0E33\u0E19\u0E27\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48")
                ]),
                createVNode("div", null, [
                  createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-2" }, "\u0E09\u0E31\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E44\u0E14\u0E49\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E44\u0E23?"),
                  createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400" }, '\u0E04\u0E38\u0E13\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E44\u0E14\u0E49\u0E42\u0E14\u0E22\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21 "\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19" \u0E41\u0E25\u0E30\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E42\u0E2D\u0E19\u0E40\u0E07\u0E34\u0E19 \u0E41\u0E15\u0E49\u0E21\u0E08\u0E30\u0E16\u0E39\u0E01\u0E04\u0E33\u0E19\u0E27\u0E13\u0E08\u0E32\u0E01\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E07\u0E34\u0E19\u0E17\u0E35\u0E48\u0E42\u0E2D\u0E19 (1 \u0E1A\u0E32\u0E17 = 1,080 \u0E41\u0E15\u0E49\u0E21)')
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div>`);
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (isShowingDonorModal.value && selectedDonate.value) {
          _push2(`<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" data-v-d10b181d><div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden animate-scale-in" data-v-d10b181d><div class="bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 p-6 text-white text-center relative overflow-hidden" data-v-d10b181d><div class="absolute inset-0 bg-white/10 backdrop-blur-sm" data-v-d10b181d></div><div class="relative z-10" data-v-d10b181d><div class="w-24 h-24 mx-auto mb-4 relative" data-v-d10b181d><svg class="w-24 h-24 transform -rotate-90" data-v-d10b181d><circle cx="48" cy="48" r="44" stroke="rgba(255,255,255,0.3)" stroke-width="8" fill="transparent" data-v-d10b181d></circle><circle cx="48" cy="48" r="44" stroke="white" stroke-width="8" fill="transparent" stroke-linecap="round"${ssrRenderAttr("stroke-dasharray", 276.46)}${ssrRenderAttr("stroke-dashoffset", 276.46 * (1 - countdownSeconds.value / 10))} class="transition-all duration-1000 ease-linear" data-v-d10b181d></circle></svg><div class="absolute inset-0 flex items-center justify-center" data-v-d10b181d>`);
          if ((_a = selectedDonate.value.donor) == null ? void 0 : _a.avatar) {
            _push2(`<img${ssrRenderAttr("src", selectedDonate.value.donor.avatar)} class="w-16 h-16 rounded-full border-3 border-white shadow-lg object-cover"${ssrRenderAttr("alt", selectedDonate.value.donor_name)} data-v-d10b181d>`);
          } else {
            _push2(`<div class="w-16 h-16 rounded-full bg-white/30 flex items-center justify-center" data-v-d10b181d>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "mdi:account",
              class: "w-8 h-8 text-white"
            }, null, _parent));
            _push2(`</div>`);
          }
          _push2(`</div></div><h2 class="text-2xl font-bold mb-1" data-v-d10b181d>${ssrInterpolate(selectedDonate.value.donor_name)}</h2><p class="text-sm text-white/80" data-v-d10b181d>\u0E1C\u0E39\u0E49\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</p></div></div><div class="p-6 text-center" data-v-d10b181d><div class="mb-6" data-v-d10b181d><div class="text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-2" data-v-d10b181d>${ssrInterpolate(countdownSeconds.value)}</div><p class="text-gray-600 dark:text-gray-400" data-v-d10b181d>\u0E27\u0E34\u0E19\u0E32\u0E17\u0E35</p></div><div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-6" data-v-d10b181d><div class="h-full bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 transition-all duration-1000 ease-linear rounded-full" style="${ssrRenderStyle({ width: `${(10 - countdownSeconds.value) / 10 * 100}%` })}" data-v-d10b181d></div></div><div class="flex justify-center items-center gap-4 mb-6" data-v-d10b181d><div class="flex items-center gap-3 bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 px-6 py-4 rounded-2xl shadow-inner" data-v-d10b181d>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:star",
            class: "w-8 h-8 text-yellow-500 animate-pulse"
          }, null, _parent));
          _push2(`<div class="text-center" data-v-d10b181d><div class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-500 to-orange-500 tabular-nums" data-v-d10b181d> +${ssrInterpolate(animatedPoints.value)}</div><p class="text-xs text-yellow-700 dark:text-yellow-400 font-medium" data-v-d10b181d>\u0E41\u0E15\u0E49\u0E21</p></div></div></div><p class="text-sm text-gray-500 dark:text-gray-400 mb-6" data-v-d10b181d>${ssrInterpolate(isProcessingDonate.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48 \u0E23\u0E30\u0E1A\u0E1A\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E15\u0E23\u0E35\u0E22\u0E21\u0E41\u0E15\u0E49\u0E21\u0E43\u0E2B\u0E49\u0E04\u0E38\u0E13")}</p><div class="flex justify-center" data-v-d10b181d><button${ssrIncludeBooleanAttr(isProcessingDonate.value) ? " disabled" : ""} class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" data-v-d10b181d>${ssrInterpolate(isProcessingDonate.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23..." : "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01")}</button></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`<!--]-->`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/donates/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-d10b181d"]]);

export { index as default };
//# sourceMappingURL=index-Bk8FxRGL.mjs.map
