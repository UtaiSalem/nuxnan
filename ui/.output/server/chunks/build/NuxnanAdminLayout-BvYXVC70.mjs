import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, mergeProps, withCtx, createVNode, createBlock, createCommentVNode, openBlock, unref, toDisplayString, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrRenderSlot } from 'vue/server-renderer';
import { _ as _imports_0 } from './virtual_public-CJ1CIvfL.mjs';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, u as useRouter, p as useRoute, d as useAuthStore } from './server.mjs';
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
  __name: "NuxnanAdminLayout",
  __ssrInlineRender: true,
  setup(__props) {
    useRouter();
    const route = useRoute();
    const authStore = useAuthStore();
    ref(true);
    const isSidebarCollapsed = ref(false);
    const isMobileSidebarOpen = ref(false);
    const isUserDropdownOpen = ref(false);
    const currentUser = computed(() => authStore.user);
    const isSuperAdmin = computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.is_super_admin) === true;
    });
    const allNavItems = [
      {
        name: "\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14",
        icon: "fluent:grid-24-regular",
        href: "/nuxnan-admin",
        exact: true
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49",
        icon: "fluent:people-24-regular",
        href: "/nuxnan-admin/users"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E04\u0E2D\u0E23\u0E4C\u0E2A",
        icon: "fluent:hat-graduation-24-regular",
        href: "/nuxnan-admin/courses"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E2D\u0E30\u0E04\u0E32\u0E40\u0E14\u0E21\u0E35",
        icon: "fluent:building-24-regular",
        href: "/nuxnan-admin/academies"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32",
        icon: "fluent:document-24-regular",
        href: "/nuxnan-admin/content"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 Points",
        icon: "fluent:coin-stack-24-regular",
        href: "/nuxnan-admin/points"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 Wallet",
        icon: "fluent:wallet-24-regular",
        href: "/nuxnan-admin/wallet"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19",
        icon: "fluent:gift-24-regular",
        href: "/nuxnan-admin/supports"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E04\u0E39\u0E1B\u0E2D\u0E07",
        icon: "fluent:ticket-diagonal-24-regular",
        href: "/nuxnan-admin/coupons"
      },
      {
        name: "\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19",
        icon: "fluent:data-pie-24-regular",
        href: "/nuxnan-admin/reports"
      },
      {
        name: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E30\u0E1A\u0E1A",
        icon: "fluent:settings-24-regular",
        href: "/nuxnan-admin/settings",
        superAdminOnly: true
      }
    ];
    const navItems = computed(() => {
      return allNavItems.filter((item) => {
        if (item.superAdminOnly && !isSuperAdmin.value) {
          return false;
        }
        return true;
      });
    });
    const isActiveRoute = (item) => {
      if (item.exact) {
        return route.path === item.href;
      }
      return route.path.startsWith(item.href);
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-100 dark:bg-gray-900" }, _attrs))} data-v-3e6bb3fc>`);
      if (isMobileSidebarOpen.value) {
        _push(`<div class="fixed inset-0 bg-black/50 z-40 lg:hidden" data-v-3e6bb3fc></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<aside class="${ssrRenderClass([
        "fixed top-0 left-0 z-50 h-full bg-gradient-to-b from-indigo-900 via-purple-900 to-indigo-900 transition-all duration-300",
        isSidebarCollapsed.value ? "w-20" : "w-64",
        isMobileSidebarOpen.value ? "translate-x-0" : "-translate-x-full lg:translate-x-0"
      ])}" data-v-3e6bb3fc><div class="flex items-center justify-between h-16 px-4 border-b border-white/10" data-v-3e6bb3fc>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin",
        class: "flex items-center gap-3"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg" data-v-3e6bb3fc${_scopeId}><img${ssrRenderAttr("src", _imports_0)} alt="Logo" class="w-6 h-6" data-v-3e6bb3fc${_scopeId}></div>`);
            if (!isSidebarCollapsed.value) {
              _push2(`<span class="text-white font-bold text-lg" data-v-3e6bb3fc${_scopeId}> Nuxnan Admin </span>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", { class: "w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg" }, [
                createVNode("img", {
                  src: _imports_0,
                  alt: "Logo",
                  class: "w-6 h-6"
                })
              ]),
              !isSidebarCollapsed.value ? (openBlock(), createBlock("span", {
                key: 0,
                class: "text-white font-bold text-lg"
              }, " Nuxnan Admin ")) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<button class="lg:hidden text-white/70 hover:text-white" data-v-3e6bb3fc>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:dismiss-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</button></div><nav class="flex-1 p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-8rem)]" data-v-3e6bb3fc><!--[-->`);
      ssrRenderList(navItems.value, (item) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          key: item.href,
          to: item.href,
          class: [
            "flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200",
            isActiveRoute(item) ? "bg-white/20 text-white shadow-lg" : "text-white/70 hover:bg-white/10 hover:text-white"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: item.icon,
                class: "w-5 h-5 flex-shrink-0"
              }, null, _parent2, _scopeId));
              if (!isSidebarCollapsed.value) {
                _push2(`<span class="font-medium" data-v-3e6bb3fc${_scopeId}>${ssrInterpolate(item.name)}</span>`);
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: item.icon,
                  class: "w-5 h-5 flex-shrink-0"
                }, null, 8, ["icon"]),
                !isSidebarCollapsed.value ? (openBlock(), createBlock("span", {
                  key: 0,
                  class: "font-medium"
                }, toDisplayString(item.name), 1)) : createCommentVNode("", true)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></nav><div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10" data-v-3e6bb3fc>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/70 hover:bg-white/10 hover:text-white transition-all"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-left-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            if (!isSidebarCollapsed.value) {
              _push2(`<span class="font-medium" data-v-3e6bb3fc${_scopeId}>\u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01</span>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-5 h-5"
              }),
              !isSidebarCollapsed.value ? (openBlock(), createBlock("span", {
                key: 0,
                class: "font-medium"
              }, "\u0E01\u0E25\u0E31\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E2B\u0E25\u0E31\u0E01")) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></aside><div class="${ssrRenderClass([
        "transition-all duration-300",
        isSidebarCollapsed.value ? "lg:ml-20" : "lg:ml-64"
      ])}" data-v-3e6bb3fc><header class="sticky top-0 z-30 bg-white dark:bg-gray-800 shadow-sm" data-v-3e6bb3fc><div class="flex items-center justify-between h-16 px-4 lg:px-6" data-v-3e6bb3fc><div class="flex items-center gap-4" data-v-3e6bb3fc><button class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" data-v-3e6bb3fc>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:navigation-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`</button><h1 class="text-lg font-semibold text-gray-800 dark:text-white hidden sm:block" data-v-3e6bb3fc> \u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23 Nuxnan </h1></div><div class="flex items-center gap-3" data-v-3e6bb3fc><button class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" data-v-3e6bb3fc>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:alert-24-regular",
        class: "w-6 h-6"
      }, null, _parent));
      _push(`<span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" data-v-3e6bb3fc></span></button><div class="relative" data-v-3e6bb3fc><button class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" data-v-3e6bb3fc><img${ssrRenderAttr("src", ((_a = currentUser.value) == null ? void 0 : _a.avatar) || `https://ui-avatars.com/api/?name=${encodeURIComponent(((_b = currentUser.value) == null ? void 0 : _b.name) || "Admin")}&background=6366f1&color=fff`)}${ssrRenderAttr("alt", (_c = currentUser.value) == null ? void 0 : _c.name)} class="w-8 h-8 rounded-full object-cover" data-v-3e6bb3fc><span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-200" data-v-3e6bb3fc>${ssrInterpolate(((_d = currentUser.value) == null ? void 0 : _d.name) || "Admin")}</span>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chevron-down-24-regular",
        class: "w-4 h-4 text-gray-500"
      }, null, _parent));
      _push(`</button>`);
      if (isUserDropdownOpen.value) {
        _push(`<div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50" data-v-3e6bb3fc><div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700" data-v-3e6bb3fc><p class="text-sm font-medium text-gray-800 dark:text-white" data-v-3e6bb3fc>${ssrInterpolate((_e = currentUser.value) == null ? void 0 : _e.name)}</p><p class="text-xs text-gray-500" data-v-3e6bb3fc>${ssrInterpolate((_f = currentUser.value) == null ? void 0 : _f.email)}</p></div>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/nuxnan-admin/profile",
          class: "flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700",
          onClick: ($event) => isUserDropdownOpen.value = false
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:person-24-regular",
                  class: "w-4 h-4"
                }),
                createTextVNode(" \u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/nuxnan-admin/settings",
          class: "flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700",
          onClick: ($event) => isUserDropdownOpen.value = false
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-4 h-4"
              }, null, _parent2, _scopeId));
              _push2(` \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:settings-24-regular",
                  class: "w-4 h-4"
                }),
                createTextVNode(" \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<hr class="my-1 border-gray-100 dark:border-gray-700" data-v-3e6bb3fc><button class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20" data-v-3e6bb3fc>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:sign-out-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A </button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></header><main class="p-4 lg:p-6" data-v-3e6bb3fc>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</main></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/NuxnanAdminLayout.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const NuxnanAdminLayout = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-3e6bb3fc"]]);

export { NuxnanAdminLayout as default };
//# sourceMappingURL=NuxnanAdminLayout-BvYXVC70.mjs.map
