import { defineComponent, inject, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { p as useRoute, i as useApi } from './server.mjs';
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
  __name: "admin",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    useApi();
    route.params.id;
    inject("isCourseAdmin");
    const admins = ref([]);
    const isLoading = ref(true);
    const showInviteModal = ref(false);
    const searchQuery = ref("");
    const searchResults = ref([]);
    const isSearching = ref(false);
    const selectedRole = ref(3);
    const course = inject("course");
    const getRoleName = (admin) => {
      var _a;
      if (((_a = course == null ? void 0 : course.value) == null ? void 0 : _a.user_id) === admin.user_id) {
        return "\u0E41\u0E2D\u0E14\u0E21\u0E34\u0E19 (Admin)";
      }
      return admin.role === 4 ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25 (Admin)" : "\u0E1C\u0E39\u0E49\u0E0A\u0E48\u0E27\u0E22\u0E2A\u0E2D\u0E19 (TA)";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-8 max-w-6xl" }, _attrs))}><div class="flex justify-between items-center mb-6"><div><h1 class="text-2xl font-bold flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "eos-icons:admin-outlined",
        class: "text-blue-600"
      }, null, _parent));
      _push(` \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 </h1><p class="text-gray-500 text-sm mt-1">\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E1A\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E41\u0E25\u0E30\u0E1C\u0E39\u0E49\u0E0A\u0E48\u0E27\u0E22\u0E2A\u0E2D\u0E19</p></div><button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 shadow transition-all">`);
      _push(ssrRenderComponent(unref(Icon), { icon: "mdi:plus" }, null, _parent));
      _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25 </button></div>`);
      if (unref(isLoading) && !unref(showInviteModal)) {
        _push(`<div class="text-center py-10">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-10 h-10 mx-auto text-blue-500"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
        ssrRenderList(unref(admins), (admin) => {
          var _a, _b, _c;
          _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex items-center gap-4 relative overflow-hidden">`);
          if (admin.status === 2) {
            _push(`<div class="absolute top-0 right-0 bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-bl-lg font-medium"> \u0E23\u0E2D\u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A </div>`);
          } else {
            _push(`<div class="absolute top-0 right-0 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-bl-lg font-medium">${ssrInterpolate(getRoleName(admin))}</div>`);
          }
          _push(`<img${ssrRenderAttr("src", ((_a = admin.user) == null ? void 0 : _a.avatar) || "/images/default-avatar.png")} class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"><div class="flex-1 min-w-0"><h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">${ssrInterpolate(((_b = admin.user) == null ? void 0 : _b.name) || "Unknown")}</h3><p class="text-sm text-gray-500 truncate">${ssrInterpolate((_c = admin.user) == null ? void 0 : _c.email)}</p><div class="flex items-center gap-2 mt-2"><button class="text-red-500 hover:text-red-700 text-xs flex items-center gap-1 bg-red-50 px-2 py-1 rounded-md transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), { icon: "mdi:trash-can-outline" }, null, _parent));
          _push(` \u0E25\u0E1A/\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></div></div>`);
        });
        _push(`<!--]-->`);
        if (unref(admins).length === 0) {
          _push(`<div class="col-span-full text-center py-10 text-gray-500"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25 </div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      if (unref(showInviteModal)) {
        _push(`<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"><div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]"><div class="p-4 border-b dark:border-gray-700 flex justify-between items-center"><h3 class="font-bold text-lg">\u0E40\u0E0A\u0E34\u0E0D\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25 / TA</h3><button class="text-gray-500 hover:text-gray-700">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:close",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</button></div><div class="p-4 space-y-4"><div><label class="block text-sm font-medium mb-1">\u0E15\u0E33\u0E41\u0E2B\u0E19\u0E48\u0E07</label><select class="w-full border rounded-lg p-2 dark:bg-gray-900 dark:border-gray-700"><option${ssrRenderAttr("value", 3)}${ssrIncludeBooleanAttr(Array.isArray(unref(selectedRole)) ? ssrLooseContain(unref(selectedRole), 3) : ssrLooseEqual(unref(selectedRole), 3)) ? " selected" : ""}>\u0E1C\u0E39\u0E49\u0E0A\u0E48\u0E27\u0E22\u0E2A\u0E2D\u0E19 (TA) - \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32/\u0E01\u0E32\u0E23\u0E1A\u0E49\u0E32\u0E19\u0E44\u0E14\u0E49</option><option${ssrRenderAttr("value", 4)}${ssrIncludeBooleanAttr(Array.isArray(unref(selectedRole)) ? ssrLooseContain(unref(selectedRole), 4) : ssrLooseEqual(unref(selectedRole), 4)) ? " selected" : ""}>\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A (Admin) - \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E17\u0E38\u0E01\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E44\u0E14\u0E49</option></select></div><div><label class="block text-sm font-medium mb-1">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 (\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25, \u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07)</label><div class="flex gap-2"><input${ssrRenderAttr("value", unref(searchQuery))} type="text" class="flex-1 border rounded-lg p-2 dark:bg-gray-900 dark:border-gray-700" placeholder="\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E04\u0E49\u0E19\u0E2B\u0E32..."><button class="bg-blue-600 text-white px-4 rounded-lg hover:bg-blue-700">`);
        _push(ssrRenderComponent(unref(Icon), { icon: "heroicons:magnifying-glass" }, null, _parent));
        _push(`</button></div></div><div class="flex-1 overflow-y-auto min-h-[200px] border rounded-lg p-2 bg-gray-50 dark:bg-gray-900/50">`);
        if (unref(isSearching)) {
          _push(`<div class="text-center py-4">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-6 h-6 mx-auto text-blue-500"
          }, null, _parent));
          _push(`</div>`);
        } else if (unref(searchResults).length > 0) {
          _push(`<div class="space-y-2"><!--[-->`);
          ssrRenderList(unref(searchResults), (user) => {
            _push(`<div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded shadow-sm hover:shadow-md transition-shadow"><div class="flex items-center gap-3"><img${ssrRenderAttr("src", user.avatar || "/images/default-avatar.png")} class="w-10 h-10 rounded-full"><div><p class="font-medium text-sm">${ssrInterpolate(user.name)}</p><p class="text-xs text-gray-500">${ssrInterpolate(user.email)}</p></div></div><button class="text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"> \u0E40\u0E0A\u0E34\u0E0D </button></div>`);
          });
          _push(`<!--]--></div>`);
        } else if (unref(searchQuery).length > 1) {
          _push(`<div class="text-center py-4 text-gray-500"> \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 </div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/admin.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=admin-Dh6f5Cm2.mjs.map
