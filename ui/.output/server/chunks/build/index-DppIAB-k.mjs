import { H as Head, T as Title } from './components-DEm4dYEV.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, withAsyncContext, ref, unref, mergeProps, withCtx, createTextVNode, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { _ as _export_sfc, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
import { u as useFetch, I as IconWrapper } from './IconWrapper-BfOz4r-N.mjs';
import { D as DonorCard } from './DonorCard-DlRLN8Lr.mjs';
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
import '@vue/shared';
import './asyncData-BSaJWK3Z.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const authStore = useAuthStore();
    const { data: welcomeData, pending, error } = ([__temp, __restore] = withAsyncContext(() => useFetch("/api/welcome", {
      baseURL: useRuntimeConfig().public.apiBase
    }, "$77hSeXQoip")), __temp = await __temp, __restore(), __temp);
    const getAvatarUrl = (avatar) => {
      if (avatar.startsWith("http")) {
        return avatar;
      }
      return `http://localhost:8000${avatar}`;
    };
    const currentTimes = ref("");
    ref(0);
    ref(0);
    ref(0);
    const daysNameTh = ref("");
    const todayDate = ref(0);
    const months = ref("");
    const years = ref(0);
    ref(false);
    const isGetDonateLoading = ref(false);
    const isCreateDonatePageLoading = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f, _g;
      const _component_Head = Head;
      const _component_Title = Title;
      const _component_NuxtLink = __nuxt_component_0;
      if (unref(pending)) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex justify-center items-center min-h-screen" }, _attrs))} data-v-ca2edc45><div class="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-vikinger-blue" data-v-ca2edc45></div></div>`);
      } else if (unref(error)) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex justify-center items-center min-h-screen" }, _attrs))} data-v-ca2edc45><p class="text-red-500" data-v-ca2edc45>Error loading data: ${ssrInterpolate(unref(error).message)}</p></div>`);
      } else {
        _push(`<div${ssrRenderAttrs(_attrs)} data-v-ca2edc45>`);
        _push(ssrRenderComponent(_component_Head, null, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(_component_Title, null, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Welcome`);
                  } else {
                    return [
                      createTextVNode("Welcome")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(_component_Title, null, {
                  default: withCtx(() => [
                    createTextVNode("Welcome")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        if (unref(isGetDonateLoading)) {
          _push(`<div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center" data-v-ca2edc45><div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white" data-v-ca2edc45></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="relative flex justify-center items-center min-h-screen bg-[url(&#39;/storage/landing/joanna-kosinska-education-unsplash.png&#39;)] bg-cover bg-no-repeat dark:bg-gray-900 selection:bg-red-500 selection:text-white" data-v-ca2edc45><div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60" data-v-ca2edc45></div><div class="flex flex-col items-center justify-center w-full h-full mt-8 relative z-10" data-v-ca2edc45><div class="z-20 p-6 text-center sm:fixed sm:top-0 sm:right-0 md:text-right animate-fade-in" data-v-ca2edc45>`);
        if (unref(authStore).isAuthenticated) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/play/newsfeed",
            class: "text-md font-semibold text-white md:text-lg bg-gradient-to-r from-blue-500 to-indigo-600 p-4 rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transform transition-all duration-300 inline-flex items-center gap-2 backdrop-blur-sm"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(IconWrapper, {
                  icon: "mdi:newspaper-variant-outline",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(`<span data-v-ca2edc45${_scopeId}>\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E02\u0E48\u0E32\u0E27</span>`);
              } else {
                return [
                  createVNode(IconWrapper, {
                    icon: "mdi:newspaper-variant-outline",
                    class: "w-5 h-5"
                  }),
                  createVNode("span", null, "\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E02\u0E48\u0E32\u0E27")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<div class="flex flex-wrap items-center justify-center gap-4 sm:justify-end" data-v-ca2edc45>`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/auth",
            class: "p-3 px-6 text-base font-semibold text-white bg-white/20 backdrop-blur-md rounded-xl md:text-lg font-prompt hover:bg-white/30 hover:scale-105 transform transition-all duration-300 inline-flex items-center gap-2 shadow-lg hover:shadow-xl active:scale-95"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(IconWrapper, {
                  icon: "mdi:login",
                  class: "w-6 h-6"
                }, null, _parent2, _scopeId));
                _push2(`<span data-v-ca2edc45${_scopeId}>\u0E40\u0E02\u0E49\u0E32\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</span>`);
              } else {
                return [
                  createVNode(IconWrapper, {
                    icon: "mdi:login",
                    class: "w-6 h-6"
                  }),
                  createVNode("span", null, "\u0E40\u0E02\u0E49\u0E32\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/auth?tab=register",
            class: "p-3 px-6 text-base font-semibold text-white bg-gradient-to-r from-teal-500 to-emerald-600 rounded-xl md:text-lg font-prompt hover:from-teal-600 hover:to-emerald-700 hover:scale-105 transform transition-all duration-300 inline-flex items-center gap-2 shadow-lg hover:shadow-xl active:scale-95"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(IconWrapper, {
                  icon: "mdi:account-plus",
                  class: "w-6 h-6"
                }, null, _parent2, _scopeId));
                _push2(`<span data-v-ca2edc45${_scopeId}>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span>`);
              } else {
                return [
                  createVNode(IconWrapper, {
                    icon: "mdi:account-plus",
                    class: "w-6 h-6"
                  }),
                  createVNode("span", null, "\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        }
        _push(`</div><div class="animate-fade-in-up text-center space-y-6" data-v-ca2edc45><div class="relative inline-block" data-v-ca2edc45><div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 blur-3xl opacity-30 animate-pulse-slow" data-v-ca2edc45></div><p class="relative text-lg text-white sm:text-3xl font-prompt drop-shadow-2xl flex items-center justify-center gap-3 animate-bounce-slow" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:book-open-globe-20-filled  ",
          class: "w-10 h-10 sm:w-12 sm:h-12 text-yellow-300 animate-spin-slow"
        }, null, _parent));
        _push(`<span class="bg-white/10 backdrop-blur-sm px-6 py-3 rounded-2xl border border-white/20 shadow-xl audiowide-font" data-v-ca2edc45>\u0E2B\u0E19\u0E38\u0E01\u0E2B\u0E19\u0E32\u0E19\u0E46 \u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E49\u0E32\u0E07 \u0E40\u0E25\u0E48\u0E19\u0E1A\u0E49\u0E32\u0E07 \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49\u0E14\u0E49\u0E27\u0E22 </span>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:emoji:money-bag",
          class: "w-10 h-10 sm:w-12 sm:h-12 animate-bounce-slow"
        }, null, _parent));
        _push(`</p></div><h3 class="text-2xl text-white md:text-6xl drop-shadow-2xl animate-pulse-slow" data-v-ca2edc45><span class="text-3xl md:text-5xl font-light" data-v-ca2edc45>www.</span><b class="audiowide-font text-4xl md:text-8xl bg-clip-text text-transparent bg-gradient-to-r from-cyan-300 via-purple-300 to-pink-300 drop-shadow-[0_0_30px_rgba(168,85,247,0.5)]" data-v-ca2edc45>NUXNAN</b><span class="text-3xl md:text-5xl font-light" data-v-ca2edc45>.com</span></h3><div class="flex flex-wrap items-center justify-center gap-3 px-4 text-white/90 text-sm sm:text-base animate-fade-in-up delay-200" data-v-ca2edc45><div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/20 transform hover:scale-110 transition-all duration-300 shadow-lg" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:graduation-hat-bold",
          class: "w-5 h-5 text-green-300"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</span></div><div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/20 transform hover:scale-110 transition-all duration-300 shadow-lg" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent-emoji-flat:party-popper",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E2A\u0E19\u0E38\u0E01\u0E2A\u0E19\u0E32\u0E19</span></div><div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/20 transform hover:scale-110 transition-all duration-300 shadow-lg" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "noto:money-with-wings",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49</span></div></div></div><div class="p-3 mb-4 font-medium min-w-40 min-h-48 animate-fade-in-up delay-200 mt-6 sm:mt-0" data-v-ca2edc45><div class="flex-none w-40 text-center rounded-t shadow-2xl lg:rounded-t-none lg:rounded-l transform hover:scale-110 transition-all duration-300" data-v-ca2edc45><div class="block overflow-hidden text-center rounded-lg font-prompt shadow-xl hover:shadow-2xl" data-v-ca2edc45><div class="py-1 bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center gap-1" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "mdi:calendar-month",
          class: "w-4 h-4 text-white"
        }, null, _parent));
        _push(`<p class="text-white font-prompt" data-v-ca2edc45>${ssrInterpolate(unref(months))}</p></div><div class="py-1 bg-gradient-to-r from-blue-500 to-indigo-600" data-v-ca2edc45><p class="text-white font-prompt flex items-center justify-center gap-1" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "mdi:calendar",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(years))}</p></div><div class="pt-3 bg-white border-l border-r border-white" data-v-ca2edc45><span class="text-6xl font-bold leading-tight bg-clip-text text-transparent bg-gradient-to-br from-blue-500 to-purple-600" data-v-ca2edc45>${ssrInterpolate(unref(todayDate))}</span></div><div class="pb-3 -mb-1 text-center bg-white border-b border-l border-r border-white rounded-b-lg" data-v-ca2edc45><span class="font-extrabold text-blue-600 text-lg flex items-center justify-center gap-1" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "mdi:weather-sunny",
          class: "w-5 h-5"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(daysNameTh))}</span></div><div class="py-2 mt-2 text-center bg-gradient-to-br from-gray-50 to-gray-100 border-b border-l border-r border-white rounded-lg shadow-inner" data-v-ca2edc45><span class="text-xl leading-normal text-gray-800 font-semibold flex items-center justify-center gap-1" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "mdi:clock-outline",
          class: "w-5 h-5 text-blue-500"
        }, null, _parent));
        _push(` ${ssrInterpolate(unref(currentTimes))}</span></div></div></div></div><section class="text-gray-700 body-font animate-fade-in-up delay-300" data-v-ca2edc45><div class="container px-5 py-12 mx-auto" data-v-ca2edc45><div class="flex flex-wrap -m-4 text-center text-blue-500" data-v-ca2edc45><div class="w-full px-4 py-2 lg:w-1/4 md:w-1/2 sm:w-1/2 transform hover:scale-110 transition-all duration-500" data-v-ca2edc45><div class="relative w-full h-full p-6 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-blue-500 overflow-hidden group" data-v-ca2edc45><div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500" data-v-ca2edc45></div><div class="absolute -top-2 -left-2 w-4 h-4 bg-blue-400 rounded-full animate-pulse-slow" data-v-ca2edc45></div><div class="absolute -bottom-2 -right-2 w-4 h-4 bg-blue-400 rounded-full animate-pulse-slow delay-100" data-v-ca2edc45></div><div class="relative z-10" data-v-ca2edc45><div class="flex justify-center mb-4" data-v-ca2edc45><div class="p-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:people-community-20-filled",
          class: "w-12 h-12 text-white"
        }, null, _parent));
        _push(`</div></div><div class="my-4" data-v-ca2edc45><h2 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-700 drop-shadow-sm" data-v-ca2edc45><span data-v-ca2edc45>${ssrInterpolate((_a = unref(welcomeData)) == null ? void 0 : _a.usersCount)}</span></h2></div><div class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:users-group-two-rounded-bold-duotone",
          class: "w-6 h-6 text-blue-600"
        }, null, _parent));
        _push(`<span class="whitespace-nowrap" data-v-ca2edc45>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</span></div></div></div></div><div class="w-full px-4 py-2 lg:w-1/4 md:w-1/2 sm:w-1/2 transform hover:scale-110 transition-all duration-500" data-v-ca2edc45><div class="relative w-full h-full p-6 bg-gradient-to-br from-purple-50 to-pink-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-purple-500 overflow-hidden group" data-v-ca2edc45><div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500" data-v-ca2edc45></div><div class="absolute -top-2 -left-2 w-4 h-4 bg-purple-400 rounded-full animate-pulse-slow" data-v-ca2edc45></div><div class="absolute -bottom-2 -right-2 w-4 h-4 bg-purple-400 rounded-full animate-pulse-slow delay-100" data-v-ca2edc45></div><div class="relative z-10" data-v-ca2edc45><div class="flex justify-center mb-4" data-v-ca2edc45><div class="p-4 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:chat-bubbles-question-20-filled",
          class: "w-12 h-12 text-white"
        }, null, _parent));
        _push(`</div></div><div class="my-4" data-v-ca2edc45><h2 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-pink-700 drop-shadow-sm" data-v-ca2edc45><span data-v-ca2edc45>${ssrInterpolate((_b = unref(welcomeData)) == null ? void 0 : _b.postsCount)}</span></h2></div><div class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:chat-round-bold-duotone",
          class: "w-6 h-6 text-purple-600"
        }, null, _parent));
        _push(`<span class="whitespace-nowrap" data-v-ca2edc45>\u0E42\u0E1E\u0E2A\u0E15\u0E4C</span></div></div></div></div><div class="w-full px-4 py-2 lg:w-1/4 md:w-1/2 sm:w-1/2 transform hover:scale-110 transition-all duration-500" data-v-ca2edc45><div class="relative w-full h-full p-6 bg-gradient-to-br from-green-50 to-emerald-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-green-500 overflow-hidden group" data-v-ca2edc45><div class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500" data-v-ca2edc45></div><div class="absolute -top-2 -left-2 w-4 h-4 bg-green-400 rounded-full animate-pulse-slow" data-v-ca2edc45></div><div class="absolute -bottom-2 -right-2 w-4 h-4 bg-green-400 rounded-full animate-pulse-slow delay-100" data-v-ca2edc45></div><div class="relative z-10" data-v-ca2edc45><div class="flex justify-center mb-4" data-v-ca2edc45><div class="p-4 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:book-open-globe-20-filled",
          class: "w-12 h-12 text-white"
        }, null, _parent));
        _push(`</div></div><div class="my-4" data-v-ca2edc45><h2 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-emerald-700 drop-shadow-sm" data-v-ca2edc45><span data-v-ca2edc45>${ssrInterpolate((_c = unref(welcomeData)) == null ? void 0 : _c.coursesCount)}</span></h2></div><div class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:notebook-bold-duotone",
          class: "w-6 h-6 text-green-600"
        }, null, _parent));
        _push(`<span class="whitespace-nowrap" data-v-ca2edc45>\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span></div></div></div></div><div class="w-full px-4 py-2 lg:w-1/4 md:w-1/2 sm:w-1/2 transform hover:scale-110 transition-all duration-500" data-v-ca2edc45><div class="relative w-full h-full p-6 bg-gradient-to-br from-orange-50 to-red-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-orange-500 overflow-hidden group" data-v-ca2edc45><div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500" data-v-ca2edc45></div><div class="absolute -top-2 -left-2 w-4 h-4 bg-orange-400 rounded-full animate-pulse-slow" data-v-ca2edc45></div><div class="absolute -bottom-2 -right-2 w-4 h-4 bg-orange-400 rounded-full animate-pulse-slow delay-100" data-v-ca2edc45></div><div class="relative z-10" data-v-ca2edc45><div class="flex justify-center mb-4" data-v-ca2edc45><div class="p-4 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:book-template-20-filled",
          class: "w-12 h-12 text-white"
        }, null, _parent));
        _push(`</div></div><div class="my-4" data-v-ca2edc45><h2 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-red-700 drop-shadow-sm" data-v-ca2edc45><span data-v-ca2edc45>${ssrInterpolate((_d = unref(welcomeData)) == null ? void 0 : _d.lessonsCount)}</span></h2></div><div class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:document-text-bold-duotone",
          class: "w-6 h-6 text-orange-600"
        }, null, _parent));
        _push(`<span class="whitespace-nowrap" data-v-ca2edc45>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</span></div></div></div></div></div></div></section><section class="container flex justify-center w-full animate-fade-in-up delay-400" data-v-ca2edc45><div class="relative flex items-center justify-center w-full m-5 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-3xl sm:w-64 shadow-2xl hover:shadow-3xl transform hover:scale-110 hover:rotate-2 transition-all duration-500 overflow-hidden group" data-v-ca2edc45><div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" data-v-ca2edc45></div><div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500" data-v-ca2edc45></div><div class="absolute top-4 left-4 w-2 h-2 bg-white/60 rounded-full animate-float-slow" data-v-ca2edc45></div><div class="absolute top-8 right-8 w-3 h-3 bg-white/40 rounded-full animate-float-slow delay-200" data-v-ca2edc45></div><div class="absolute bottom-6 left-6 w-2 h-2 bg-white/50 rounded-full animate-float-slow delay-400" data-v-ca2edc45></div><div class="absolute bottom-4 right-4 w-4 h-4 bg-white/30 rounded-full animate-float-slow delay-600" data-v-ca2edc45></div><div class="relative z-10 p-6 space-y-4" data-v-ca2edc45><div class="text-center" data-v-ca2edc45><div class="inline-block p-4 bg-white/20 backdrop-blur-sm rounded-2xl animate-pulse-slow" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent:people-community-20-filled",
          class: "w-16 h-16 mx-auto text-white animate-bounce-slow"
        }, null, _parent));
        _push(`</div></div><div class="my-3 text-center" data-v-ca2edc45><h2 class="text-5xl font-bold text-white drop-shadow-2xl animate-pulse-slow" data-v-ca2edc45><span data-v-ca2edc45>${ssrInterpolate((_e = unref(welcomeData)) == null ? void 0 : _e.visitorCounter)}</span></h2></div><div class="text-center text-white font-bold text-xl flex items-center justify-center gap-2 bg-white/10 backdrop-blur-sm rounded-full py-2 px-4 hover:bg-white/20 transition-all duration-300" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:eye-bold-duotone",
          class: "w-6 h-6 animate-pulse-slow"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E0A\u0E21</span></div><div class="text-sm text-center text-yellow-200 font-medium flex items-center justify-center gap-2 bg-yellow-500/20 rounded-full py-2 px-3 hover:bg-yellow-500/30 transition-all duration-300" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:calendar-bold-duotone",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E15\u0E31\u0E49\u0E07\u0E41\u0E15\u0E48 ${ssrInterpolate((/* @__PURE__ */ new Date("1/1/2024")).toLocaleDateString("th-TH"))}</span></div></div></div></section><div class="max-w-6xl mt-10 md:mt-20 mb-4 text-center animate-fade-in-up delay-500" data-v-ca2edc45><div class="relative inline-block" data-v-ca2edc45><div class="absolute inset-0 bg-yellow-500/30 blur-2xl rounded-full animate-pulse-slow" data-v-ca2edc45></div><p class="relative text-white text-md sm:text-lg font-prompt bg-gradient-to-r from-yellow-500/30 to-orange-500/30 backdrop-blur-md px-8 py-4 rounded-full inline-flex items-center gap-3 shadow-2xl border border-yellow-500/50" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "svg-spinners:blocks-shuffle-3",
          class: "w-6 h-6 text-yellow-300"
        }, null, _parent));
        _push(`<span class="font-bold" data-v-ca2edc45>***\u0E2D\u0E22\u0E39\u0E48\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E41\u0E25\u0E30\u0E17\u0E14\u0E25\u0E2D\u0E07\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19***</span>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent-emoji:construction",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</p></div></div></div></div><section class="text-gray-700 border-t border-gray-200 body-font bg-gradient-to-b from-white to-blue-50" data-v-ca2edc45><div class="container px-5 py-16 mx-auto" data-v-ca2edc45><div class="flex flex-col w-full mb-12 text-center animate-fade-in-up" data-v-ca2edc45><div class="flex items-center justify-center gap-3 mb-4" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "noto:sparkling-heart",
          class: "w-12 h-12 animate-bounce-slow"
        }, null, _parent));
        _push(`<h1 class="text-3xl m-6 font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 sm:text-5xl title-font drop-shadow-lg" data-v-ca2edc45> \u0E1C\u0E39\u0E49\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49 </h1>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "noto:sparkling-heart",
          class: "w-12 h-12 animate-bounce-slow delay-100"
        }, null, _parent));
        _push(`</div><p class="text-gray-600 text-base font-medium flex items-center justify-center gap-2 bg-pink-50 py-2 px-6 rounded-full mx-auto" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "twemoji:red-heart",
          class: "w-5 h-5 animate-pulse-slow"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E02\u0E2D\u0E1A\u0E04\u0E38\u0E13\u0E17\u0E38\u0E01\u0E17\u0E48\u0E32\u0E19\u0E17\u0E35\u0E48\u0E43\u0E2B\u0E49\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</span>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "twemoji:folded-hands",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</p></div><div class="flex flex-wrap" data-v-ca2edc45><div class="grid grid-cols-1 gap-6 p-4 mx-auto sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-v-ca2edc45><!--[-->`);
        ssrRenderList((_f = unref(welcomeData)) == null ? void 0 : _f.donates, (donate, index2) => {
          _push(ssrRenderComponent(DonorCard, {
            key: donate.id || index2,
            donate
          }, null, _parent));
        });
        _push(`<!--]--></div></div></div><div class="container mx-auto mb-20 text-center rounded-lg animate-fade-in-up delay-300" data-v-ca2edc45><button class="relative px-20 py-8 text-2xl font-bold text-white bg-gradient-to-r from-teal-500 via-emerald-500 to-green-500 rounded-3xl hover:from-teal-600 hover:via-emerald-600 hover:to-green-600 transform hover:scale-110 transition-all duration-500 shadow-2xl hover:shadow-3xl inline-flex items-center gap-4 overflow-hidden group" data-v-ca2edc45><div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000" data-v-ca2edc45></div><div class="absolute -top-2 -left-2 w-3 h-3 bg-white/60 rounded-full animate-float-slow" data-v-ca2edc45></div><div class="absolute -bottom-2 -right-2 w-4 h-4 bg-white/40 rounded-full animate-float-slow delay-200" data-v-ca2edc45></div><div class="absolute top-4 right-8 w-2 h-2 bg-white/50 rounded-full animate-float-slow delay-400" data-v-ca2edc45></div>`);
        if (unref(isCreateDonatePageLoading)) {
          _push(ssrRenderComponent(IconWrapper, {
            icon: "svg-spinners:pulse-3",
            class: "w-10 h-10 relative z-10"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(IconWrapper, {
            icon: "noto:money-bag",
            class: "w-10 h-10 relative z-10 animate-bounce-slow"
          }, null, _parent));
        }
        _push(`<span class="relative z-10" data-v-ca2edc45>\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</span>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent-emoji:sparkles",
          class: "w-8 h-8 relative z-10 animate-pulse-slow"
        }, null, _parent));
        _push(`</button></div></section><section class="text-gray-700 border-t bg-slate-200 body-font sm:px-4" data-v-ca2edc45><div class="container px-5 py-10 mx-auto" data-v-ca2edc45><div class="flex flex-col w-full mb-4 text-center" data-v-ca2edc45><h1 class="text-2xl font-semibold text-gray-700 sm:text-3xl title-font" data-v-ca2edc45>\u0E1C\u0E39\u0E49\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19\u0E17\u0E38\u0E19\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</h1></div></div><div class="pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 px-4" data-v-ca2edc45><!--[-->`);
        ssrRenderList((_g = unref(welcomeData)) == null ? void 0 : _g.donateRecipients, (recipient, index2) => {
          _push(`<div data-v-ca2edc45><div class="p-2 max-w-md mx-auto bg-white rounded-lg shadow-lg space-y-2 sm:py-4 flex items-center justify-between" data-v-ca2edc45><div class="flex items-center space-x-2" data-v-ca2edc45><img class="h-16 w-16 rounded-full sm:mx-0 sm:shrink-0"${ssrRenderAttr("src", getAvatarUrl(recipient.avatar))} alt="recipient-image" data-v-ca2edc45><div class="" data-v-ca2edc45><div class="mb-2" data-v-ca2edc45><p class="text-sm text-gray-700 font-semibold" data-v-ca2edc45>${ssrInterpolate(recipient.username)}</p><p class="text-xs text-gray-500" data-v-ca2edc45>${ssrInterpolate(recipient.reference_code)}</p></div></div></div><span class="text-green-500 font-semibold" data-v-ca2edc45>${ssrInterpolate(recipient.points)} \u0E41\u0E15\u0E49\u0E21</span></div></div>`);
        });
        _push(`<!--]--></div></section><footer class="w-full px-4 mx-auto bg-gradient-to-b from-gray-100 via-gray-200 to-gray-300 max-w-container sm:px-6 lg:px-8" data-v-ca2edc45><div class="py-16 text-center border-t-4 border-gradient-to-r from-indigo-500 via-purple-500 to-pink-500" data-v-ca2edc45><h3 class="text-gray-700 mb-4" data-v-ca2edc45><span class="text-2xl font-light" data-v-ca2edc45>www.</span><b class="audiowide-font text-5xl md:text-6xl bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 drop-shadow-lg" data-v-ca2edc45>nuxnan</b><span class="text-2xl font-light" data-v-ca2edc45>.com</span></h3><p class="mt-6 text-lg leading-6 text-center text-gray-800 font-prompt font-bold flex items-center justify-center gap-3 bg-blue-50 py-3 px-8 rounded-full mx-auto" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "fluent-emoji:books",
          class: "w-6 h-6 animate-bounce-slow"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E40\u0E25\u0E48\u0E19\u0E1A\u0E49\u0E32\u0E07 \u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E49\u0E32\u0E07 \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49\u0E14\u0E49\u0E27\u0E22 \u0E2B\u0E19\u0E38\u0E01\u0E2B\u0E19\u0E32\u0E19!!</span>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "noto:smiling-face-with-hearts",
          class: "w-6 h-6 animate-pulse-slow"
        }, null, _parent));
        _push(`</p><div class="mt-10 flex justify-center" data-v-ca2edc45><div class="relative group" data-v-ca2edc45><div class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full blur-xl opacity-50 group-hover:opacity-75 transition-opacity duration-500" data-v-ca2edc45></div><div class="absolute -top-2 -left-2 w-3 h-3 bg-purple-400 rounded-full animate-float-slow" data-v-ca2edc45></div><div class="absolute -bottom-2 -right-2 w-4 h-4 bg-pink-400 rounded-full animate-float-slow delay-200" data-v-ca2edc45></div><div class="absolute top-4 right-4 w-2 h-2 bg-indigo-400 rounded-full animate-float-slow delay-400" data-v-ca2edc45></div><img${ssrRenderAttr("src", "/storage/landing/ceo.jpg")} alt="CEO" class="relative w-32 h-32 rounded-full border-4 border-white shadow-2xl transform group-hover:scale-110 transition-transform duration-500" data-v-ca2edc45></div></div><div class="mt-10 max-w-md mx-auto bg-gradient-to-br from-white to-blue-50 rounded-3xl p-8 shadow-2xl border-2 border-blue-200" data-v-ca2edc45><h4 class="text-xl font-bold text-gray-800 mb-6 flex items-center justify-center gap-2" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:letter-bold-duotone",
          class: "w-7 h-7 text-blue-600 animate-pulse-slow"
        }, null, _parent));
        _push(`<span data-v-ca2edc45>\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E40\u0E23\u0E32</span></h4><div class="space-y-4" data-v-ca2edc45><div class="flex items-center gap-4 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "logos:facebook",
          class: "w-8 h-8 group-hover:scale-110 transition-transform duration-300"
        }, null, _parent));
        _push(`<span class="text-base font-bold text-slate-700" data-v-ca2edc45>Utai Salem</span></div><div class="flex items-center gap-4 p-3 bg-gradient-to-r from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "logos:messenger",
          class: "w-8 h-8 group-hover:scale-110 transition-transform duration-300"
        }, null, _parent));
        _push(`<span class="text-base font-bold text-slate-700" data-v-ca2edc45>Bhupha MustaFa</span></div><div class="flex items-center gap-4 p-3 bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "simple-icons:line",
          class: "w-8 h-8 text-green-600 group-hover:scale-110 transition-transform duration-300"
        }, null, _parent));
        _push(`<span class="text-base font-bold text-slate-700" data-v-ca2edc45>babobhupha</span></div><div class="flex items-center gap-4 p-3 bg-gradient-to-r from-purple-50 to-pink-50 hover:from-purple-100 hover:to-pink-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:phone-bold-duotone",
          class: "w-8 h-8 text-purple-600 group-hover:scale-110 transition-transform duration-300"
        }, null, _parent));
        _push(`<span class="text-base font-bold text-slate-700" data-v-ca2edc45>093-840-3000</span></div></div></div><div class="mt-10 text-sm text-gray-600 items-center justify-center gap-2 bg-gray-100 py-3 px-6 rounded-full inline-flex mx-auto" data-v-ca2edc45>`);
        _push(ssrRenderComponent(IconWrapper, {
          icon: "solar:copyright-bold-duotone",
          class: "w-5 h-5 text-gray-500 animate-pulse-slow"
        }, null, _parent));
        _push(`<span class="font-semibold" data-v-ca2edc45>2024 Plearnd. All rights reserved.</span></div></div></footer></div>`);
      }
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-ca2edc45"]]);

export { index as default };
//# sourceMappingURL=index-DppIAB-k.mjs.map
