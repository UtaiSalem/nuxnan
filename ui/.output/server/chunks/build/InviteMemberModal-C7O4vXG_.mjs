import { defineComponent, ref, watch, unref, useSSRContext } from 'vue';
import { ssrRenderTeleport, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, i as useApi } from './server.mjs';
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
  __name: "InviteMemberModal",
  __ssrInlineRender: true,
  props: {
    isOpen: { type: Boolean },
    academyId: {}
  },
  emits: ["close", "invited"],
  setup(__props, { emit: __emit }) {
    const api = useApi();
    const searchQuery = ref("");
    const searchResults = ref([]);
    const isSearching = ref(false);
    const isInviting = ref(false);
    const invitingUserId = ref(null);
    const debounceTimer = ref(null);
    watch(searchQuery, (newQuery) => {
      if (debounceTimer.value) {
        clearTimeout(debounceTimer.value);
      }
      if (newQuery.length < 2) {
        searchResults.value = [];
        return;
      }
      debounceTimer.value = setTimeout(() => {
        searchUsers();
      }, 300);
    });
    const searchUsers = async () => {
      if (searchQuery.value.length < 2) return;
      isSearching.value = true;
      try {
        const response = await api.get("/api/users/search", {
          params: { q: searchQuery.value, limit: 10 }
        });
        if (response.success) {
          searchResults.value = response.data || [];
        }
      } catch (err) {
        console.error("Search users error:", err);
      } finally {
        isSearching.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        if (__props.isOpen) {
          _push2(`<div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-v-fb024ca7><div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-v-fb024ca7></div><div class="relative bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col overflow-hidden" data-v-fb024ca7><div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700" data-v-fb024ca7><div class="flex items-center gap-3" data-v-fb024ca7><div class="w-10 h-10 rounded-lg bg-vikinger-purple/10 flex items-center justify-center" data-v-fb024ca7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-add-24-regular",
            class: "w-5 h-5 text-vikinger-purple"
          }, null, _parent));
          _push2(`</div><div data-v-fb024ca7><h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-v-fb024ca7>\u0E40\u0E0A\u0E34\u0E0D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-fb024ca7>\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E40\u0E0A\u0E34\u0E0D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" data-v-fb024ca7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-5 h-5 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700" data-v-fb024ca7><div class="relative" data-v-fb024ca7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:search-24-regular",
            class: "absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
          }, null, _parent));
          _push2(`<input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E0A\u0E37\u0E48\u0E2D, \u0E2D\u0E35\u0E40\u0E21\u0E25, \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07..." class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50" data-v-fb024ca7>`);
          if (isSearching.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-vikinger-purple"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div>`);
          if (searchQuery.value.length > 0 && searchQuery.value.length < 2) {
            _push2(`<p class="mt-2 text-sm text-gray-500" data-v-fb024ca7> \u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 2 \u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23 </p>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><div class="flex-1 overflow-y-auto px-6 py-4" data-v-fb024ca7>`);
          if (!searchQuery.value || searchQuery.value.length < 2) {
            _push2(`<div class="text-center py-8" data-v-fb024ca7>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:search-24-regular",
              class: "w-16 h-16 text-gray-200 dark:text-gray-700 mx-auto mb-4"
            }, null, _parent));
            _push2(`<p class="text-gray-500 dark:text-gray-400" data-v-fb024ca7>\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E34\u0E0D</p></div>`);
          } else if (searchResults.value.length === 0 && !isSearching.value) {
            _push2(`<div class="text-center py-8" data-v-fb024ca7>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:person-search-24-regular",
              class: "w-16 h-16 text-gray-200 dark:text-gray-700 mx-auto mb-4"
            }, null, _parent));
            _push2(`<p class="text-gray-500 dark:text-gray-400" data-v-fb024ca7>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E35\u0E48\u0E04\u0E49\u0E19\u0E2B\u0E32</p></div>`);
          } else {
            _push2(`<div class="space-y-2" data-v-fb024ca7><!--[-->`);
            ssrRenderList(searchResults.value, (user) => {
              _push2(`<div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 transition-colors" data-v-fb024ca7><div class="flex items-center gap-3" data-v-fb024ca7><div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden" data-v-fb024ca7>`);
              if (user.avatar || user.profile_photo_url) {
                _push2(`<img${ssrRenderAttr("src", user.avatar || user.profile_photo_url)}${ssrRenderAttr("alt", user.name)} class="w-full h-full object-cover" data-v-fb024ca7>`);
              } else {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:person-24-regular",
                  class: "w-full h-full p-2 text-gray-400"
                }, null, _parent));
              }
              _push2(`</div><div data-v-fb024ca7><h4 class="font-medium text-gray-900 dark:text-white" data-v-fb024ca7>${ssrInterpolate(user.name)}</h4><div class="flex items-center gap-2 text-sm text-gray-500" data-v-fb024ca7>`);
              if (user.reference_code) {
                _push2(`<span data-v-fb024ca7>@${ssrInterpolate(user.reference_code)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              if (user.email) {
                _push2(`<span data-v-fb024ca7>${ssrInterpolate(user.email)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></div></div><button${ssrIncludeBooleanAttr(isInviting.value && invitingUserId.value === user.id) ? " disabled" : ""} class="px-4 py-2 bg-vikinger-purple text-white rounded-lg text-sm font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors" data-v-fb024ca7>`);
              if (isInviting.value && invitingUserId.value === user.id) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "svg-spinners:ring-resize",
                  class: "w-4 h-4"
                }, null, _parent));
              } else {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:person-add-24-regular",
                  class: "w-4 h-4"
                }, null, _parent));
              }
              _push2(`<span data-v-fb024ca7>\u0E40\u0E0A\u0E34\u0E0D</span></button></div>`);
            });
            _push2(`<!--]--></div>`);
          }
          _push2(`</div><div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-vikinger-dark-100" data-v-fb024ca7><p class="text-xs text-gray-500 dark:text-gray-400 text-center" data-v-fb024ca7>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:info-16-regular",
            class: "inline w-4 h-4 mr-1"
          }, null, _parent));
          _push2(` \u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E40\u0E0A\u0E34\u0E0D\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19\u0E41\u0E25\u0E30\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E2D\u0E21\u0E23\u0E31\u0E1A\u0E2B\u0E23\u0E37\u0E2D\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D\u0E44\u0E14\u0E49 </p></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/InviteMemberModal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const InviteMemberModal = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-fb024ca7"]]);

export { InviteMemberModal as default };
//# sourceMappingURL=InviteMemberModal-C7O4vXG_.mjs.map
