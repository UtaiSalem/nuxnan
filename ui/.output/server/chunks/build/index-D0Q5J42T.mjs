import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, mergeProps, withCtx, unref, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { b as useRuntimeConfig, p as useRoute } from './server.mjs';
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
    const route = useRoute();
    const userId = route.params.id;
    const user = ref(null);
    const isLoading = ref(true);
    const error = ref("");
    const isVerifying = ref(false);
    const verifyMessage = ref("");
    const verifyError = ref("");
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
    const formatDate = (dateStr) => {
      if (!dateStr) return "-";
      const date = new Date(dateStr);
      return date.toLocaleString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6 max-w-4xl mx-auto" }, _attrs))}><div class="flex items-center gap-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/nuxnan-admin/users",
        class: "p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:arrow-left-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "fluent:arrow-left-24-regular",
                class: "w-5 h-5"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="flex-1"><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49</h1><p class="text-gray-500 dark:text-gray-400 mt-1">\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 #${ssrInterpolate(unref(userId))}</p></div>`);
      if (user.value) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/nuxnan-admin/users/${unref(userId)}/edit`,
          class: "inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:edit-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(` \u0E41\u0E01\u0E49\u0E44\u0E02 `);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:edit-24-regular",
                  class: "w-5 h-5"
                }),
                createTextVNode(" \u0E41\u0E01\u0E49\u0E44\u0E02 ")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (isLoading.value) {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700"><div class="text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-8 h-8 text-indigo-600 animate-spin mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div></div>`);
      } else if (error.value) {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700"><div class="text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-12 h-12 text-red-400 mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-2">${ssrInterpolate(error.value)}</p><button class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </button></div></div>`);
      } else if (user.value) {
        _push(`<!--[--><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"><div class="flex flex-col sm:flex-row items-center sm:items-start gap-6"><div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold">${ssrInterpolate(((_b = (_a = user.value.name) == null ? void 0 : _a.charAt(0)) == null ? void 0 : _b.toUpperCase()) || "U")}</div><div class="flex-1 text-center sm:text-left"><h2 class="text-2xl font-bold text-gray-800 dark:text-white">${ssrInterpolate(user.value.name)}</h2><p class="text-gray-500 dark:text-gray-400">@${ssrInterpolate(user.value.username || "no-username")}</p><div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-3"><span class="${ssrRenderClass([getStatusBadge(user.value.status || "active"), "px-3 py-1 text-sm rounded-full"])}">${ssrInterpolate(user.value.status === "active" ? "\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19" : user.value.status === "suspended" ? "\u0E23\u0E30\u0E07\u0E31\u0E1A" : "\u0E44\u0E21\u0E48\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19")}</span><span class="${ssrRenderClass([getRoleBadge(user.value.role || "user"), "px-3 py-1 text-sm rounded-full"])}">${ssrInterpolate(user.value.role === "admin" ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25" : user.value.role === "instructor" ? "\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19" : "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}</span>`);
        if (user.value.is_super_admin) {
          _push(`<span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"> Super Admin </span>`);
        } else {
          _push(`<!---->`);
        }
        if (user.value.is_plearnd_admin) {
          _push(`<span class="px-3 py-1 text-sm rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400"> Plearnd Admin </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-24-regular",
          class: "w-5 h-5 text-indigo-600"
        }, null, _parent));
        _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D </h3><div class="space-y-4"><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E35\u0E40\u0E21\u0E25</p><p class="text-gray-800 dark:text-white">${ssrInterpolate(user.value.email || "-")}</p></div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C</p><p class="text-gray-800 dark:text-white">${ssrInterpolate(user.value.phone || "-")}</p></div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07</p><p class="text-gray-800 dark:text-white font-mono">${ssrInterpolate(user.value.reference_code || "-")}</p></div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E23\u0E2B\u0E31\u0E2A\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</p><p class="text-gray-800 dark:text-white font-mono">${ssrInterpolate(user.value.personal_code || "-")}</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-24-regular",
          class: "w-5 h-5 text-indigo-600"
        }, null, _parent));
        _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1A\u0E31\u0E0D\u0E0A\u0E35 </h3><div class="space-y-4"><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D</p><p class="text-gray-800 dark:text-white">${ssrInterpolate(formatDate(user.value.created_at))}</p></div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</p><p class="text-gray-800 dark:text-white">${ssrInterpolate(formatDate(user.value.updated_at))}</p></div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E40\u0E21\u0E37\u0E48\u0E2D</p><div class="flex items-center gap-3"><p class="text-gray-800 dark:text-white">${ssrInterpolate(user.value.email_verified_at ? formatDate(user.value.email_verified_at) : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19")}</p>`);
        if (!user.value.email_verified_at) {
          _push(`<button${ssrIncludeBooleanAttr(isVerifying.value) ? " disabled" : ""} class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-lg transition-colors">`);
          if (isVerifying.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-4 h-4 animate-spin"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:checkmark-circle-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push(` \u0E22\u0E37\u0E19\u0E22\u0E31\u0E19 </button>`);
        } else {
          _push(`<button${ssrIncludeBooleanAttr(isVerifying.value) ? " disabled" : ""} class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white rounded-lg transition-colors">`);
          if (isVerifying.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-4 h-4 animate-spin"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-circle-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push(` \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button>`);
        }
        _push(`</div>`);
        if (verifyMessage.value) {
          _push(`<p class="text-sm text-green-600 dark:text-green-400 mt-1">${ssrInterpolate(verifyMessage.value)}</p>`);
        } else {
          _push(`<!---->`);
        }
        if (verifyError.value) {
          _push(`<p class="text-sm text-red-600 dark:text-red-400 mt-1">${ssrInterpolate(verifyError.value)}</p>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div><p class="text-sm text-gray-500 dark:text-gray-400">\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</p><p class="text-gray-800 dark:text-white">${ssrInterpolate(user.value.last_login_at ? formatDate(user.value.last_login_at) : "-")}</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:wallet-24-regular",
          class: "w-5 h-5 text-indigo-600"
        }, null, _parent));
        _push(` \u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19 &amp; \u0E04\u0E30\u0E41\u0E19\u0E19 </h3><div class="space-y-4"><div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl"><span class="text-gray-600 dark:text-gray-300">\u0E22\u0E2D\u0E14\u0E40\u0E07\u0E34\u0E19</span><span class="text-lg font-bold text-green-600 dark:text-green-400"> \u0E3F${ssrInterpolate((user.value.wallet_balance || 0).toLocaleString())}</span></div><div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl"><span class="text-gray-600 dark:text-gray-300">\u0E04\u0E30\u0E41\u0E19\u0E19</span><span class="text-lg font-bold text-purple-600 dark:text-purple-400">${ssrInterpolate((user.value.points || 0).toLocaleString())} pts </span></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"><h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:data-trending-24-regular",
          class: "w-5 h-5 text-indigo-600"
        }, null, _parent));
        _push(` \u0E2A\u0E16\u0E34\u0E15\u0E34 </h3><div class="grid grid-cols-2 gap-4"><div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><p class="text-2xl font-bold text-gray-800 dark:text-white">${ssrInterpolate(user.value.courses_count || 0)}</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E25\u0E07\u0E17\u0E30\u0E40\u0E1A\u0E35\u0E22\u0E19</p></div><div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><p class="text-2xl font-bold text-gray-800 dark:text-white">${ssrInterpolate(user.value.completed_courses || 0)}</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E1A</p></div><div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><p class="text-2xl font-bold text-gray-800 dark:text-white">${ssrInterpolate(user.value.referrals_count || 0)}</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E1C\u0E39\u0E49\u0E17\u0E35\u0E48\u0E41\u0E19\u0E30\u0E19\u0E33</p></div><div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><p class="text-2xl font-bold text-gray-800 dark:text-white">${ssrInterpolate(user.value.login_count || 0)}</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E08\u0E33\u0E19\u0E27\u0E19\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A</p></div></div></div></div><!--]-->`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/users/[id]/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-D0Q5J42T.mjs.map
