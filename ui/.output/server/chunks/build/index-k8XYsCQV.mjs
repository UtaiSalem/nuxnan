import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderClass, ssrRenderTeleport } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, b as useRuntimeConfig } from './server.mjs';
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
    const config = useRuntimeConfig();
    config.public.apiBase;
    const users = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref("");
    const selectedRole = ref("all");
    const currentPage = ref(1);
    const totalPages = ref(1);
    const perPage = ref(10);
    const totalUsers = ref(0);
    const showDeleteModal = ref(false);
    const userToDelete = ref(null);
    const roles = [
      { value: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14" },
      { value: "user", label: "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B" },
      { value: "instructor", label: "\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19" },
      { value: "admin", label: "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25" }
    ];
    const getStatusBadge = (status) => {
      const badges = {
        active: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
        inactive: "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300",
        suspended: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
      };
      return badges[status] || badges.inactive;
    };
    const getRoleBadge = (role) => {
      const badges = {
        admin: "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400",
        instructor: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
        user: "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
      };
      return badges[role] || badges.user;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-ad6b6d18><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-v-ad6b6d18><div data-v-ad6b6d18><h1 class="text-2xl font-bold text-gray-800 dark:text-white" data-v-ad6b6d18>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</h1><p class="text-gray-500 dark:text-gray-400 mt-1" data-v-ad6b6d18> \u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ${ssrInterpolate(totalUsers.value.toLocaleString())} \u0E04\u0E19 </p></div>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/users/create",
        class: "inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-add-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E43\u0E2B\u0E21\u0E48 `);
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:person-add-24-regular",
                class: "w-5 h-5"
              }),
              createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E43\u0E2B\u0E21\u0E48 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700" data-v-ad6b6d18><div class="flex flex-col sm:flex-row gap-4" data-v-ad6b6d18><div class="flex-1 relative" data-v-ad6b6d18>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:search-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" data-v-ad6b6d18></div><select class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" data-v-ad6b6d18><!--[-->`);
      ssrRenderList(roles, (role) => {
        _push(`<option${ssrRenderAttr("value", role.value)} data-v-ad6b6d18${ssrIncludeBooleanAttr(Array.isArray(selectedRole.value) ? ssrLooseContain(selectedRole.value, role.value) : ssrLooseEqual(selectedRole.value, role.value)) ? " selected" : ""}>${ssrInterpolate(role.label)}</option>`);
      });
      _push(`<!--]--></select><button class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors" data-v-ad6b6d18> \u0E04\u0E49\u0E19\u0E2B\u0E32 </button></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" data-v-ad6b6d18>`);
      if (isLoading.value) {
        _push(`<div class="p-8 text-center" data-v-ad6b6d18>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2" data-v-ad6b6d18>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else {
        _push(`<div class="overflow-x-auto" data-v-ad6b6d18><table class="w-full" data-v-ad6b6d18><thead class="bg-gray-50 dark:bg-gray-700/50" data-v-ad6b6d18><tr data-v-ad6b6d18><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-ad6b6d18> \u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </th><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-ad6b6d18> \u0E2D\u0E35\u0E40\u0E21\u0E25 </th><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-ad6b6d18> \u0E1A\u0E17\u0E1A\u0E32\u0E17 </th><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-ad6b6d18> \u0E2A\u0E16\u0E32\u0E19\u0E30 </th><th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-ad6b6d18> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E21\u0E31\u0E04\u0E23 </th><th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" data-v-ad6b6d18> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 </th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700" data-v-ad6b6d18><!--[-->`);
        ssrRenderList(users.value, (user) => {
          _push(`<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" data-v-ad6b6d18><td class="px-6 py-4 whitespace-nowrap" data-v-ad6b6d18><div class="flex items-center gap-3" data-v-ad6b6d18><img${ssrRenderAttr("src", user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`)}${ssrRenderAttr("alt", user.name)} class="w-10 h-10 rounded-full object-cover" data-v-ad6b6d18><div data-v-ad6b6d18><p class="font-medium text-gray-800 dark:text-white" data-v-ad6b6d18>${ssrInterpolate(user.name)}</p><p class="text-sm text-gray-500" data-v-ad6b6d18>ID: ${ssrInterpolate(user.id)}</p></div></div></td><td class="px-6 py-4 whitespace-nowrap" data-v-ad6b6d18><span class="text-gray-600 dark:text-gray-300" data-v-ad6b6d18>${ssrInterpolate(user.email)}</span></td><td class="px-6 py-4 whitespace-nowrap" data-v-ad6b6d18><span class="${ssrRenderClass([getRoleBadge(user.role), "px-2.5 py-1 rounded-full text-xs font-medium"])}" data-v-ad6b6d18>${ssrInterpolate(user.role || "user")}</span></td><td class="px-6 py-4 whitespace-nowrap" data-v-ad6b6d18><span class="${ssrRenderClass([getStatusBadge(user.status || "active"), "px-2.5 py-1 rounded-full text-xs font-medium"])}" data-v-ad6b6d18>${ssrInterpolate(user.status || "active")}</span></td><td class="px-6 py-4 whitespace-nowrap" data-v-ad6b6d18><span class="text-gray-600 dark:text-gray-300" data-v-ad6b6d18>${ssrInterpolate(new Date(user.created_at).toLocaleDateString("th-TH"))}</span></td><td class="px-6 py-4 whitespace-nowrap text-right" data-v-ad6b6d18><div class="flex items-center justify-end gap-2" data-v-ad6b6d18>`);
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/nuxnan-admin/users/${user.id}`,
            class: "p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:eye-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:eye-24-regular",
                    class: "w-5 h-5"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/nuxnan-admin/users/${user.id}/edit`,
            class: "p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:edit-24-regular",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-regular",
                    class: "w-5 h-5"
                  })
                ];
              }
            }),
            _: 2
          }, _parent));
          _push(`<button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" data-v-ad6b6d18>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></td></tr>`);
        });
        _push(`<!--]--></tbody></table>`);
        if (users.value.length === 0) {
          _push(`<div class="p-8 text-center" data-v-ad6b6d18>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-24-regular",
            class: "w-12 h-12 text-gray-300 mx-auto"
          }, null, _parent));
          _push(`<p class="text-gray-500 mt-2" data-v-ad6b6d18>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      if (totalPages.value > 1) {
        _push(`<div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between" data-v-ad6b6d18><p class="text-sm text-gray-500" data-v-ad6b6d18> \u0E41\u0E2A\u0E14\u0E07 ${ssrInterpolate((currentPage.value - 1) * perPage.value + 1)} - ${ssrInterpolate(Math.min(currentPage.value * perPage.value, totalUsers.value))} \u0E08\u0E32\u0E01 ${ssrInterpolate(totalUsers.value)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p><div class="flex items-center gap-2" data-v-ad6b6d18><button${ssrIncludeBooleanAttr(currentPage.value === 1) ? " disabled" : ""} class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-v-ad6b6d18>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-left-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button><span class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300" data-v-ad6b6d18> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(currentPage.value)} / ${ssrInterpolate(totalPages.value)}</span><button${ssrIncludeBooleanAttr(currentPage.value === totalPages.value) ? " disabled" : ""} class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-v-ad6b6d18>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-right-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      ssrRenderTeleport(_push, (_push2) => {
        var _a;
        if (showDeleteModal.value) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-v-ad6b6d18><div class="absolute inset-0 bg-black/50" data-v-ad6b6d18></div><div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl" data-v-ad6b6d18><div class="text-center" data-v-ad6b6d18><div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4" data-v-ad6b6d18>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:warning-24-regular",
            class: "w-6 h-6 text-red-600 dark:text-red-400"
          }, null, _parent));
          _push2(`</div><h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2" data-v-ad6b6d18> \u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </h3><p class="text-gray-500 dark:text-gray-400" data-v-ad6b6d18> \u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 &quot;${ssrInterpolate((_a = userToDelete.value) == null ? void 0 : _a.name)}&quot; \u0E43\u0E0A\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48? \u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E17\u0E33\u0E19\u0E35\u0E49\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E49\u0E2D\u0E19\u0E01\u0E25\u0E31\u0E1A\u0E44\u0E14\u0E49 </p></div><div class="flex gap-3 mt-6" data-v-ad6b6d18><button class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" data-v-ad6b6d18> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors" data-v-ad6b6d18> \u0E25\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </button></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/users/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-ad6b6d18"]]);

export { index as default };
//# sourceMappingURL=index-k8XYsCQV.mjs.map
