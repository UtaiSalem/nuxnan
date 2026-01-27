import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { _ as _sfc_main$1 } from './CreatePostBox-OC44HEYf.mjs';
import { defineComponent, ref, computed, mergeProps, unref, withCtx, createVNode, createTextVNode, createBlock, createCommentVNode, openBlock, toDisplayString, defineAsyncComponent, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderStyle, ssrRenderAttr, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderList, ssrRenderTeleport } from 'vue/server-renderer';
import { storeToRefs } from 'pinia';
import { Icon } from '@iconify/vue';
import { p as useRoute, i as useApi, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
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
import './useAvatar-C8DTKR1c.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const __nuxt_component_2_lazy = defineAsyncComponent(() => import('./InviteMemberModal-C7O4vXG_.mjs').then((c) => c.default || c));
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[name]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const api = useApi();
    const config = useRuntimeConfig();
    const { user } = storeToRefs(useAuthStore());
    const academy = ref(null);
    const courses = ref([]);
    const members = ref([]);
    const groups = ref([]);
    const activities = ref([]);
    const isLoading = ref(true);
    const isLoadingTab = ref(false);
    const error = ref(null);
    const currentTab = ref("feed");
    ref(false);
    const isMemberActionLoading = ref(false);
    const showCreateGroupModal = ref(false);
    const newGroup = ref({ name: "", description: "", type: "classroom" });
    const isCreatingGroup = ref(false);
    const showInviteMemberModal = ref(false);
    const pendingRequests = ref([]);
    ref(false);
    computed(() => route.params.name);
    const logoUrl = computed(() => {
      var _a;
      if (!((_a = academy.value) == null ? void 0 : _a.logo)) {
        return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`;
      }
      if (academy.value.logo.startsWith("http")) {
        return academy.value.logo;
      }
      return academy.value.logo;
    });
    const coverUrl = computed(() => {
      var _a;
      if (!((_a = academy.value) == null ? void 0 : _a.cover)) {
        return `${config.public.apiBase}/storage/images/academies/covers/default_cover.png`;
      }
      if (academy.value.cover.startsWith("http")) {
        return academy.value.cover;
      }
      return academy.value.cover;
    });
    const memberStatusText = computed(() => {
      if (!academy.value) return null;
      const status = academy.value.memberStatus;
      if (status === null || status === void 0) return null;
      if (status === 0 || status === "pending") return { text: "\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", color: "bg-yellow-500" };
      if (status === 1 || status === "approved") return { text: "\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34", color: "bg-yellow-500" };
      if (status === 2 || status === "member") return { text: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", color: "bg-green-500" };
      return null;
    });
    const canJoin = computed(() => {
      return academy.value && !academy.value.memberStatus && !academy.value.authIsAcademyAdmin;
    });
    const canLeave = computed(() => {
      return academy.value && academy.value.memberStatus && !academy.value.authIsAcademyAdmin;
    });
    const tabs = [
      { id: "feed", label: "\u0E1F\u0E35\u0E14", icon: "fluent:feed-24-regular" },
      { id: "courses", label: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32", icon: "fluent:book-24-regular" },
      { id: "members", label: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", icon: "fluent:people-24-regular" },
      { id: "groups", label: "\u0E01\u0E25\u0E38\u0E48\u0E21", icon: "fluent:people-community-24-regular" }
    ];
    const fetchActivities = async () => {
      if (!academy.value) return;
      isLoadingTab.value = true;
      try {
        const response = await api.get(`/api/academies/${academy.value.id}/activities`);
        if (response.success) {
          activities.value = JSON.parse(JSON.stringify(response.activities || []));
        }
      } catch (err) {
        console.error("Failed to fetch activities:", err);
      } finally {
        isLoadingTab.value = false;
      }
    };
    const onMemberInvited = () => {
    };
    const handleAcademyPostCreated = async (post) => {
      if (post) {
        activities.value.unshift(JSON.parse(JSON.stringify(post)));
      } else {
        await fetchActivities();
      }
    };
    const getAcademyTypeInfo = (type) => {
      const typeMap = {
        "public": { label: "\u0E23\u0E31\u0E10\u0E1A\u0E32\u0E25", icon: "fluent:building-government-24-regular", color: "text-blue-500" },
        "private": { label: "\u0E40\u0E2D\u0E01\u0E0A\u0E19", icon: "fluent:building-bank-24-regular", color: "text-purple-500" },
        "foundation": { label: "\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34", icon: "fluent:heart-24-regular", color: "text-pink-500" },
        "international": { label: "\u0E19\u0E32\u0E19\u0E32\u0E0A\u0E32\u0E15\u0E34", icon: "fluent:globe-24-regular", color: "text-green-500" }
      };
      return typeMap[type || ""] || { label: "\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B", icon: "fluent:building-24-regular", color: "text-gray-500" };
    };
    const getGroupTypeInfo = (type) => {
      const types = {
        "department": { label: "\u0E41\u0E1C\u0E19\u0E01", icon: "fluent:building-24-regular", color: "from-blue-400 to-blue-600" },
        "classroom": { label: "\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19", icon: "fluent:board-24-regular", color: "from-green-400 to-green-600" },
        "club": { label: "\u0E0A\u0E21\u0E23\u0E21", icon: "fluent:star-24-regular", color: "from-orange-400 to-orange-600" }
      };
      return types[type] || types["classroom"];
    };
    const formatDate = (dateString) => {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      const _component_PlayFeedCreatePostBox = _sfc_main$1;
      const _component_LazyLearnAcademyInviteMemberModal = __nuxt_component_2_lazy;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-200 dark:bg-vikinger-dark-300" }, _attrs))}>`);
      if (isLoading.value) {
        _push(`<div class="flex items-center justify-center min-h-screen"><div class="text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-12 h-12 text-vikinger-purple mx-auto mb-4"
        }, null, _parent));
        _push(`<p class="text-gray-600 dark:text-gray-400">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19...</p></div></div>`);
      } else if (error.value) {
        _push(`<div class="flex items-center justify-center min-h-screen"><div class="text-center p-8 max-w-md">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:warning-24-regular",
          class: "w-16 h-16 text-yellow-500 mx-auto mb-4"
        }, null, _parent));
        _push(`<h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</h2><p class="text-gray-600 dark:text-gray-400 mb-6">${ssrInterpolate(error.value)}</p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/newsfeed",
          class: "inline-flex items-center gap-2 px-6 py-3 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/90 transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:arrow-left-24-regular",
                  class: "w-5 h-5"
                }),
                createTextVNode(" \u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else if (academy.value) {
        _push(`<div class="max-w-7xl mx-auto px-4 py-6"><div class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-lg overflow-hidden mb-6"><div class="h-48 md:h-64 bg-gray-300 dark:bg-gray-700 bg-cover bg-center relative" style="${ssrRenderStyle({ backgroundImage: `url(${coverUrl.value})` })}"><div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div></div><div class="relative px-4 md:px-8 pb-6"><div class="absolute -top-16 left-4 md:left-8"><div class="w-28 h-28 md:w-36 md:h-36 rounded-xl border-4 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white"><img${ssrRenderAttr("src", logoUrl.value)}${ssrRenderAttr("alt", academy.value.name)} class="w-full h-full object-cover"></div></div><div class="flex flex-col md:flex-row md:items-end md:justify-between pt-16 md:pt-6 md:pl-44"><div class="mb-4 md:mb-0"><h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">${ssrInterpolate(academy.value.name)}</h1>`);
        if (academy.value.slogan) {
          _push(`<p class="text-gray-600 dark:text-gray-400 mb-3">${ssrInterpolate(academy.value.slogan)}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400"><div class="flex items-center gap-1.5">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: getAcademyTypeInfo(academy.value.type).icon,
          class: ["w-4 h-4", getAcademyTypeInfo(academy.value.type).color]
        }, null, _parent));
        _push(`<span>${ssrInterpolate(getAcademyTypeInfo(academy.value.type).label)}</span></div><div class="flex items-center gap-1.5">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(academy.value.total_students || 0)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></div><div class="flex items-center gap-1.5">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:book-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(academy.value.courses_offered || 0)} \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span></div></div></div><div class="flex items-center gap-3">`);
        if (memberStatusText.value) {
          _push(`<span class="${ssrRenderClass(["px-3 py-1.5 rounded-full text-sm font-medium text-white", memberStatusText.value.color])}">${ssrInterpolate(memberStatusText.value.text)}</span>`);
        } else {
          _push(`<!---->`);
        }
        if (academy.value.authIsAcademyAdmin) {
          _push(`<span class="px-3 py-1.5 rounded-full text-sm font-medium bg-vikinger-purple text-white flex items-center gap-1.5">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:shield-checkmark-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25 </span>`);
        } else {
          _push(`<!---->`);
        }
        if (canJoin.value) {
          _push(`<button${ssrIncludeBooleanAttr(isMemberActionLoading.value) ? " disabled" : ""} class="px-5 py-2.5 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">`);
          if (isMemberActionLoading.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-add-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push(` \u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </button>`);
        } else {
          _push(`<!---->`);
        }
        if (canLeave.value) {
          _push(`<button${ssrIncludeBooleanAttr(isMemberActionLoading.value) ? " disabled" : ""} class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">`);
          if (isMemberActionLoading.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-subtract-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push(` \u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div><div class="border-t border-gray-200 dark:border-gray-700"><div class="flex overflow-x-auto"><!--[-->`);
        ssrRenderList(tabs, (tab) => {
          _push(`<button class="${ssrRenderClass([
            "flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors whitespace-nowrap",
            currentTab.value === tab.id ? "text-vikinger-purple border-b-2 border-vikinger-purple" : "text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
          ])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: tab.icon,
            class: "w-5 h-5"
          }, null, _parent));
          _push(` ${ssrInterpolate(tab.label)}</button>`);
        });
        _push(`<!--]--></div></div></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-6"><div class="lg:col-span-2">`);
        if (isLoadingTab.value) {
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-8 h-8 text-vikinger-purple mx-auto"
          }, null, _parent));
          _push(`</div>`);
        } else if (currentTab.value === "feed") {
          _push(`<div class="space-y-4">`);
          if (academy.value.memberStatus === 2 || academy.value.authIsAcademyAdmin) {
            _push(ssrRenderComponent(_component_PlayFeedCreatePostBox, {
              context: "academy",
              "context-id": academy.value.id,
              "context-name": academy.value.name,
              onPostCreated: handleAcademyPostCreated
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          if (activities.value.length === 0) {
            _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:feed-24-regular",
              class: "w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"
            }, null, _parent));
            _push(`<p class="text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(activities.value, (activity) => {
            var _a, _b, _c, _d;
            _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden"><div class="p-4 flex items-start gap-3"><div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">`);
            if ((_a = activity.user) == null ? void 0 : _a.avatar) {
              _push(`<img${ssrRenderAttr("src", activity.user.avatar)}${ssrRenderAttr("alt", (_b = activity.user) == null ? void 0 : _b.name)} class="w-full h-full object-cover">`);
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-24-regular",
                class: "w-full h-full p-2 text-gray-400"
              }, null, _parent));
            }
            _push(`</div><div class="flex-1"><div class="flex items-center justify-between"><div><h4 class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_c = activity.user) == null ? void 0 : _c.name) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}</h4><p class="text-xs text-gray-500 dark:text-gray-400">${ssrInterpolate(formatDate(activity.created_at))}</p></div>`);
            if (activity.type) {
              _push(`<span class="text-xs px-2 py-1 rounded-full bg-vikinger-purple/10 text-vikinger-purple">${ssrInterpolate(activity.type)}</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="mt-3"><p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">${ssrInterpolate(activity.description || activity.content)}</p></div>`);
            if ((_d = activity.images) == null ? void 0 : _d.length) {
              _push(`<div class="${ssrRenderClass([activity.images.length === 1 ? "grid-cols-1" : "grid-cols-2", "mt-3 grid gap-2"])}"><!--[-->`);
              ssrRenderList(activity.images.slice(0, 4), (img, idx) => {
                _push(`<img${ssrRenderAttr("src", img)} class="rounded-lg w-full h-48 object-cover">`);
              });
              _push(`<!--]--></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div><div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700/50 flex items-center gap-4"><button class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple transition-colors">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:heart-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span class="text-sm">${ssrInterpolate(activity.likes_count || 0)}</span></button><button class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple transition-colors">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:comment-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span class="text-sm">${ssrInterpolate(activity.comments_count || 0)}</span></button><button class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple transition-colors ml-auto">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:share-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</button></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else if (currentTab.value === "courses") {
          _push(`<div class="space-y-4">`);
          if (academy.value.authIsAcademyAdmin) {
            _push(`<div class="flex justify-end">`);
            _push(ssrRenderComponent(_component_NuxtLink, {
              to: "/courses/create",
              class: "px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:add-24-regular",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48 `);
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: "fluent:add-24-regular",
                      class: "w-5 h-5"
                    }),
                    createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48 ")
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          if (courses.value.length === 0) {
            _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:book-24-regular",
              class: "w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"
            }, null, _parent));
            _push(`<p class="text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p>`);
            if (academy.value.authIsAcademyAdmin) {
              _push(`<p class="text-sm text-gray-400 dark:text-gray-500 mt-2">\u0E04\u0E25\u0E34\u0E01 &quot;\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><!--[-->`);
            ssrRenderList(courses.value, (course) => {
              _push(ssrRenderComponent(_component_NuxtLink, {
                key: course.id,
                to: `/courses/${course.id}`,
                class: "block bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm hover:shadow-md transition-all overflow-hidden group"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(`<div class="h-32 bg-gradient-to-br from-vikinger-purple to-vikinger-cyan relative"${_scopeId}>`);
                    if (course.cover) {
                      _push2(`<img${ssrRenderAttr("src", course.cover)} class="w-full h-full object-cover"${_scopeId}>`);
                    } else {
                      _push2(`<!---->`);
                    }
                    _push2(`<div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"${_scopeId}></div></div><div class="p-4"${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover:text-vikinger-purple transition-colors"${_scopeId}>${ssrInterpolate(course.name)}</h3><p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3"${_scopeId}>${ssrInterpolate(course.description)}</p><div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"${_scopeId}><div class="flex items-center gap-3"${_scopeId}><span class="flex items-center gap-1"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "fluent:people-24-regular",
                      class: "w-4 h-4"
                    }, null, _parent2, _scopeId));
                    _push2(` ${ssrInterpolate(course.students_count || 0)}</span><span class="flex items-center gap-1"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "fluent:book-open-24-regular",
                      class: "w-4 h-4"
                    }, null, _parent2, _scopeId));
                    _push2(` ${ssrInterpolate(course.lessons_count || 0)} \u0E1A\u0E17 </span></div><span class="${ssrRenderClass([
                      "px-2 py-0.5 rounded-full text-xs",
                      course.status === "published" ? "bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400" : course.status === "draft" ? "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                    ])}"${_scopeId}>${ssrInterpolate(course.status === "published" ? "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48" : course.status === "draft" ? "\u0E41\u0E1A\u0E1A\u0E23\u0E48\u0E32\u0E07" : course.status)}</span></div></div>`);
                  } else {
                    return [
                      createVNode("div", { class: "h-32 bg-gradient-to-br from-vikinger-purple to-vikinger-cyan relative" }, [
                        course.cover ? (openBlock(), createBlock("img", {
                          key: 0,
                          src: course.cover,
                          class: "w-full h-full object-cover"
                        }, null, 8, ["src"])) : createCommentVNode("", true),
                        createVNode("div", { class: "absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" })
                      ]),
                      createVNode("div", { class: "p-4" }, [
                        createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover:text-vikinger-purple transition-colors" }, toDisplayString(course.name), 1),
                        createVNode("p", { class: "text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3" }, toDisplayString(course.description), 1),
                        createVNode("div", { class: "flex items-center justify-between text-xs text-gray-500 dark:text-gray-400" }, [
                          createVNode("div", { class: "flex items-center gap-3" }, [
                            createVNode("span", { class: "flex items-center gap-1" }, [
                              createVNode(unref(Icon), {
                                icon: "fluent:people-24-regular",
                                class: "w-4 h-4"
                              }),
                              createTextVNode(" " + toDisplayString(course.students_count || 0), 1)
                            ]),
                            createVNode("span", { class: "flex items-center gap-1" }, [
                              createVNode(unref(Icon), {
                                icon: "fluent:book-open-24-regular",
                                class: "w-4 h-4"
                              }),
                              createTextVNode(" " + toDisplayString(course.lessons_count || 0) + " \u0E1A\u0E17 ", 1)
                            ])
                          ]),
                          createVNode("span", {
                            class: [
                              "px-2 py-0.5 rounded-full text-xs",
                              course.status === "published" ? "bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400" : course.status === "draft" ? "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                            ]
                          }, toDisplayString(course.status === "published" ? "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48" : course.status === "draft" ? "\u0E41\u0E1A\u0E1A\u0E23\u0E48\u0E32\u0E07" : course.status), 3)
                        ])
                      ])
                    ];
                  }
                }),
                _: 2
              }, _parent));
            });
            _push(`<!--]--></div>`);
          }
          _push(`</div>`);
        } else if (currentTab.value === "members") {
          _push(`<div class="space-y-4">`);
          if (academy.value.authIsAcademyAdmin) {
            _push(`<div class="flex items-center justify-between"><div class="flex items-center gap-2">`);
            if (pendingRequests.value.length > 0) {
              _push(`<span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-sm font-medium">${ssrInterpolate(pendingRequests.value.length)} \u0E04\u0E33\u0E02\u0E2D\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23 </span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><button class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-add-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(` \u0E40\u0E0A\u0E34\u0E0D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </button></div>`);
          } else {
            _push(`<!---->`);
          }
          if (academy.value.authIsAcademyAdmin && pendingRequests.value.length > 0) {
            _push(`<div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800"><div class="flex items-center gap-2 mb-4">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:clock-24-regular",
              class: "w-5 h-5 text-yellow-600 dark:text-yellow-400"
            }, null, _parent));
            _push(`<h3 class="font-semibold text-yellow-800 dark:text-yellow-200">\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E17\u0E35\u0E48\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</h3></div><div class="space-y-3"><!--[-->`);
            ssrRenderList(pendingRequests.value, (request) => {
              var _a, _b, _c, _d;
              _push(`<div class="flex items-center justify-between p-3 bg-white dark:bg-vikinger-dark-200 rounded-lg"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">`);
              if ((_a = request.user) == null ? void 0 : _a.profile_photo_url) {
                _push(`<img${ssrRenderAttr("src", request.user.profile_photo_url)}${ssrRenderAttr("alt", (_b = request.user) == null ? void 0 : _b.name)} class="w-full h-full object-cover">`);
              } else {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:person-24-regular",
                  class: "w-full h-full p-2 text-gray-400"
                }, null, _parent));
              }
              _push(`</div><div><h4 class="font-medium text-gray-900 dark:text-white">${ssrInterpolate((_c = request.user) == null ? void 0 : _c.name)}</h4><p class="text-sm text-gray-500">@${ssrInterpolate((_d = request.user) == null ? void 0 : _d.reference_code)}</p></div></div><div class="flex items-center gap-2"><button class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600 transition-colors flex items-center gap-1">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push(` \u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34 </button><button class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors flex items-center gap-1">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push(` \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div></div>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm"><div class="relative">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:search-24-regular",
            class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
          }, null, _parent));
          _push(`<input type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01..." class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"></div></div>`);
          if (members.value.length === 0) {
            _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-24-regular",
              class: "w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"
            }, null, _parent));
            _push(`<p class="text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p>`);
            if (academy.value.authIsAcademyAdmin) {
              _push(`<p class="text-sm text-gray-400 dark:text-gray-500 mt-2">\u0E04\u0E25\u0E34\u0E01 &quot;\u0E40\u0E0A\u0E34\u0E0D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E0A\u0E34\u0E0D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden"><div class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
          ssrRenderList(members.value, (member) => {
            var _a, _b, _c, _d, _e;
            _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"><div class="flex items-center justify-between gap-4"><div class="flex items-center gap-3"><div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden ring-2 ring-white dark:ring-vikinger-dark-200">`);
            if (((_a = member.user) == null ? void 0 : _a.avatar) || member.avatar) {
              _push(`<img${ssrRenderAttr("src", ((_b = member.user) == null ? void 0 : _b.avatar) || member.avatar)}${ssrRenderAttr("alt", ((_c = member.user) == null ? void 0 : _c.name) || member.name)} class="w-full h-full object-cover">`);
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-24-regular",
                class: "w-full h-full p-2 text-gray-400"
              }, null, _parent));
            }
            _push(`</div><div><h4 class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(((_d = member.user) == null ? void 0 : _d.name) || member.name)}</h4><div class="flex items-center gap-2 text-sm"><span class="${ssrRenderClass([
              "px-2 py-0.5 rounded-full text-xs font-medium",
              member.role === "admin" ? "bg-vikinger-purple/10 text-vikinger-purple" : member.role === "teacher" ? "bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
            ])}">${ssrInterpolate(member.role === "admin" ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25" : member.role === "teacher" ? "\u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19" : "\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")}</span>`);
            if (member.joined_at) {
              _push(`<span class="text-gray-400">\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(formatDate(member.joined_at))}</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div></div><div class="flex items-center gap-2">`);
            _push(ssrRenderComponent(_component_NuxtLink, {
              to: `/profile/${((_e = member.user) == null ? void 0 : _e.id) || member.id}`,
              class: "p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-vikinger-purple",
              title: "\u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C"
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:person-24-regular",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: "fluent:person-24-regular",
                      class: "w-5 h-5"
                    })
                  ];
                }
              }),
              _: 2
            }, _parent));
            if (academy.value.authIsAcademyAdmin && member.role !== "admin") {
              _push(`<button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-vikinger-purple" title="\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push(`</button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div></div>`);
          });
          _push(`<!--]--></div></div>`);
          if (members.value.length >= 10) {
            _push(`<div class="text-center"><button class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium text-sm"> \u0E42\u0E2B\u0E25\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 </button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else if (currentTab.value === "groups") {
          _push(`<div class="space-y-4">`);
          if (academy.value.authIsAcademyAdmin) {
            _push(`<div class="flex justify-end"><button class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
          } else {
            _push(`<!---->`);
          }
          if (groups.value.length === 0) {
            _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:people-community-24-regular",
              class: "w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3"
            }, null, _parent));
            _push(`<p class="text-gray-500 dark:text-gray-400">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21</p>`);
            if (academy.value.authIsAcademyAdmin) {
              _push(`<p class="text-sm text-gray-400 dark:text-gray-500 mt-2">\u0E04\u0E25\u0E34\u0E01 &quot;\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><!--[-->`);
            ssrRenderList(groups.value, (group) => {
              _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer"><div class="${ssrRenderClass(["h-16 bg-gradient-to-br", getGroupTypeInfo(group.type).color])}"></div><div class="p-4"><div class="flex items-start gap-3"><div class="${ssrRenderClass(["w-12 h-12 -mt-8 rounded-lg bg-gradient-to-br flex items-center justify-center border-2 border-white dark:border-vikinger-dark-200 shadow-md", getGroupTypeInfo(group.type).color])}">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: getGroupTypeInfo(group.type).icon,
                class: "w-6 h-6 text-white"
              }, null, _parent));
              _push(`</div><div class="flex-1 pt-0"><div class="flex items-center gap-2"><h4 class="font-semibold text-gray-900 dark:text-white">${ssrInterpolate(group.name)}</h4><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">${ssrInterpolate(getGroupTypeInfo(group.type).label)}</span></div>`);
              if (group.description) {
                _push(`<p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">${ssrInterpolate(group.description)}</p>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div><div class="mt-4 flex items-center justify-between text-sm"><div class="flex items-center gap-3 text-gray-500 dark:text-gray-400"><span class="flex items-center gap-1">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:people-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push(` ${ssrInterpolate(group.members_count || 0)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </span></div><button class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium"> \u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </button></div></div></div>`);
            });
            _push(`<!--]--></div>`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="space-y-6"><div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm"><h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:info-24-regular",
          class: "w-5 h-5 text-vikinger-purple"
        }, null, _parent));
        _push(` \u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><div class="space-y-3 text-sm">`);
        if (academy.value.address) {
          _push(`<div class="flex items-start gap-3">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:location-24-regular",
            class: "w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5"
          }, null, _parent));
          _push(`<span class="text-gray-600 dark:text-gray-400">${ssrInterpolate(academy.value.address)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (academy.value.email) {
          _push(`<div class="flex items-center gap-3">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:mail-24-regular",
            class: "w-5 h-5 text-gray-400"
          }, null, _parent));
          _push(`<a${ssrRenderAttr("href", `mailto:${academy.value.email}`)} class="text-vikinger-purple hover:underline">${ssrInterpolate(academy.value.email)}</a></div>`);
        } else {
          _push(`<!---->`);
        }
        if (academy.value.phone) {
          _push(`<div class="flex items-center gap-3">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:call-24-regular",
            class: "w-5 h-5 text-gray-400"
          }, null, _parent));
          _push(`<a${ssrRenderAttr("href", `tel:${academy.value.phone}`)} class="text-vikinger-purple hover:underline">${ssrInterpolate(academy.value.phone)}</a></div>`);
        } else {
          _push(`<!---->`);
        }
        if (academy.value.established_year) {
          _push(`<div class="flex items-center gap-3">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-24-regular",
            class: "w-5 h-5 text-gray-400"
          }, null, _parent));
          _push(`<span class="text-gray-600 dark:text-gray-400">\u0E01\u0E48\u0E2D\u0E15\u0E31\u0E49\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(academy.value.established_year)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
        if (academy.value.director) {
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm"><h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-star-24-regular",
            class: "w-5 h-5 text-vikinger-purple"
          }, null, _parent));
          _push(` \u0E1C\u0E39\u0E49\u0E2D\u0E33\u0E19\u0E27\u0E22\u0E01\u0E32\u0E23 </h3><div class="flex items-center gap-3"><div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">`);
          if (academy.value.director.avatar) {
            _push(`<img${ssrRenderAttr("src", academy.value.director.avatar)}${ssrRenderAttr("alt", academy.value.director.name)} class="w-full h-full object-cover">`);
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-24-regular",
              class: "w-full h-full p-2 text-gray-400"
            }, null, _parent));
          }
          _push(`</div><div><h4 class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(academy.value.director.name)}</h4><p class="text-sm text-gray-500 dark:text-gray-400">\u0E1C\u0E39\u0E49\u0E2D\u0E33\u0E19\u0E27\u0E22\u0E01\u0E32\u0E23</p></div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm"><h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:data-bar-horizontal-24-regular",
          class: "w-5 h-5 text-vikinger-purple"
        }, null, _parent));
        _push(` \u0E2A\u0E16\u0E34\u0E15\u0E34 </h3><div class="grid grid-cols-2 gap-4"><div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg"><div class="text-2xl font-bold text-vikinger-purple">${ssrInterpolate(academy.value.total_students || 0)}</div><div class="text-xs text-gray-500 dark:text-gray-400">\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</div></div><div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg"><div class="text-2xl font-bold text-vikinger-cyan">${ssrInterpolate(academy.value.total_teachers || 0)}</div><div class="text-xs text-gray-500 dark:text-gray-400">\u0E04\u0E23\u0E39</div></div><div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg"><div class="text-2xl font-bold text-green-500">${ssrInterpolate(academy.value.courses_offered || 0)}</div><div class="text-xs text-gray-500 dark:text-gray-400">\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</div></div><div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg"><div class="text-2xl font-bold text-orange-500">${ssrInterpolate(groups.value.length || 0)}</div><div class="text-xs text-gray-500 dark:text-gray-400">\u0E01\u0E25\u0E38\u0E48\u0E21</div></div></div></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (showCreateGroupModal.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4"><div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div><div class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95"><div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"><h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-community-add-24-regular",
            class: "w-6 h-6 text-vikinger-purple"
          }, null, _parent));
          _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 </h3><button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-5 h-5 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-6 space-y-4"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E25\u0E38\u0E48\u0E21 <span class="text-red-500">*</span></label><input${ssrRenderAttr("value", newGroup.value.name)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E2B\u0E49\u0E2D\u0E07 \u0E21.1/1, \u0E41\u0E1C\u0E19\u0E01\u0E27\u0E34\u0E17\u0E22\u0E32\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C, \u0E0A\u0E21\u0E23\u0E21\u0E14\u0E19\u0E15\u0E23\u0E35" class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E01\u0E25\u0E38\u0E48\u0E21 </label><div class="grid grid-cols-3 gap-3"><!--[-->`);
          ssrRenderList([
            { value: "department", label: "\u0E41\u0E1C\u0E19\u0E01", icon: "fluent:building-24-regular" },
            { value: "classroom", label: "\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19", icon: "fluent:board-24-regular" },
            { value: "club", label: "\u0E0A\u0E21\u0E23\u0E21", icon: "fluent:star-24-regular" }
          ], (gtype) => {
            _push2(`<button class="${ssrRenderClass([
              "p-3 rounded-lg border-2 flex flex-col items-center gap-2 transition-all",
              newGroup.value.type === gtype.value ? "border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple" : "border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-vikinger-purple/50"
            ])}">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: gtype.icon,
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`<span class="text-xs font-medium">${ssrInterpolate(gtype.label)}</span></button>`);
          });
          _push2(`<!--]--></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="3" placeholder="\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49..." class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 resize-none">${ssrInterpolate(newGroup.value.description)}</textarea></div></div><div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3"><button class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(!newGroup.value.name.trim() || isCreatingGroup.value) ? " disabled" : ""} class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">`);
          if (isCreatingGroup.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-4 h-4"
            }, null, _parent));
          } else {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21 </button></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      if (academy.value) {
        _push(ssrRenderComponent(_component_LazyLearnAcademyInviteMemberModal, {
          "is-open": showInviteMemberModal.value,
          "academy-id": academy.value.id,
          onClose: ($event) => showInviteMemberModal.value = false,
          onInvited: onMemberInvited
        }, null, _parent));
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/academies/[name].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_name_-BViH_U7U.mjs.map
