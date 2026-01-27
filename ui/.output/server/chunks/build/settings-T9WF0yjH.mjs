import { defineComponent, computed, watch, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, f as useHead, p as useRoute, u as useRouter, d as useAuthStore, b as useRuntimeConfig } from './server.mjs';
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

const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "SettingsSidebar",
  __ssrInlineRender: true,
  props: {
    activeTab: {}
  },
  emits: ["update:activeTab"],
  setup(__props, { emit: __emit }) {
    const menuItems = [
      { id: "account", label: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1A\u0E31\u0E0D\u0E0A\u0E35", icon: "fluent:person-info-24-regular", description: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1A\u0E31\u0E0D\u0E0A\u0E35" },
      { id: "profile", label: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C", icon: "fluent:contact-card-24-regular", description: "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C" },
      { id: "privacy", label: "\u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27", icon: "fluent:shield-24-regular", description: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27" },
      { id: "socials", label: "\u0E42\u0E0B\u0E40\u0E0A\u0E35\u0E22\u0E25\u0E21\u0E35\u0E40\u0E14\u0E35\u0E22", icon: "fluent:share-24-regular", description: "\u0E40\u0E0A\u0E37\u0E48\u0E2D\u0E21\u0E15\u0E48\u0E2D\u0E42\u0E0B\u0E40\u0E0A\u0E35\u0E22\u0E25\u0E21\u0E35\u0E40\u0E14\u0E35\u0E22" },
      { id: "security", label: "\u0E04\u0E27\u0E32\u0E21\u0E1B\u0E25\u0E2D\u0E14\u0E20\u0E31\u0E22", icon: "fluent:shield-keyhole-24-regular", description: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19" }
      // { id: 'notifications', label: 'Notifications', icon: 'fluent:alert-24-regular' },
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700" }, _attrs))}><div class="p-6 border-b border-gray-100 dark:border-gray-700"><h3 class="text-lg font-bold text-gray-900 dark:text-white">\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E1A\u0E31\u0E0D\u0E0A\u0E35\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div><div class="p-2"><!--[-->`);
      ssrRenderList(menuItems, (item) => {
        _push(`<button class="${ssrRenderClass([__props.activeTab === item.id ? "bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400" : "text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50", "w-full flex items-start gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-left"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: item.icon,
          class: "w-5 h-5 mt-0.5 flex-shrink-0"
        }, null, _parent));
        _push(`<div class="flex-1 min-w-0"><div class="font-medium">${ssrInterpolate(item.label)}</div>`);
        if (item.description) {
          _push(`<div class="text-xs mt-0.5 opacity-75">${ssrInterpolate(item.description)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (__props.activeTab === item.id) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chevron-right-24-regular",
            class: "w-4 h-4 flex-shrink-0 mt-1"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      });
      _push(`<!--]--></div></div>`);
    };
  }
});
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/settings/SettingsSidebar.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "AccountInfo",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    useAuthStore();
    const isLoading = ref(false);
    const form = ref({
      name: "",
      phone_number: "",
      email: ""
      // Read-only typically
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="p-6 border-b border-gray-100 dark:border-gray-700"><h3 class="text-lg font-bold text-gray-900 dark:text-white">Account Information</h3><p class="text-sm text-gray-500 dark:text-gray-400">Manage your basic account details</p></div><div class="p-6 space-y-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Display Name</label><div class="relative">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:person-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", form.value.name)} type="text" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Your name"></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label><div class="relative opacity-75">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:mail-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", form.value.email)} type="email" readonly class="pl-10 w-full rounded-xl border-gray-300 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"></div><p class="mt-1 text-xs text-amber-500">Contact support to change email address.</p></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label><div class="relative">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:call-24-regular",
        class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
      }, null, _parent));
      _push(`<input${ssrRenderAttr("value", form.value.phone_number)} type="tel" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="08xxxxxxxx"></div></div><div class="pt-4 flex justify-end"><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(` Save Changes </button></div></div></div>`);
    };
  }
});
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/settings/AccountInfo.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "ProfileInfo",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    const authStore = useAuthStore();
    const isLoading = ref(false);
    const isUploadingAvatar = ref(false);
    const isUploadingCover = ref(false);
    const activeSection = ref("basic");
    const basicForm = ref({
      first_name: "",
      last_name: "",
      bio: "",
      location: "",
      website: "",
      birthdate: "",
      gender: "male"
    });
    const personalForm = ref({
      phone_number: "",
      address: "",
      city: "",
      country: "",
      postal_code: ""
    });
    const professionalForm = ref({
      job_title: "",
      company: "",
      industry: "",
      skills: "",
      experience_years: ""
    });
    const avatarPreview = ref("/images/default-avatar.png");
    const coverPreview = ref("");
    ref(null);
    ref(null);
    const sections = [
      { id: "basic", label: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19", icon: "fluent:person-24-regular", description: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E2B\u0E25\u0E31\u0E01" },
      { id: "personal", label: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27", icon: "fluent:contact-card-24-regular", description: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E41\u0E25\u0E30\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48" },
      { id: "professional", label: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2D\u0E32\u0E0A\u0E35\u0E1E", icon: "fluent:briefcase-24-regular", description: "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E17\u0E33\u0E07\u0E32\u0E19\u0E41\u0E25\u0E30\u0E17\u0E31\u0E01\u0E29\u0E30" }
    ];
    computed(() => sections.find((s) => s.id === activeSection.value));
    const displayName = computed(() => {
      var _a, _b;
      const firstName = basicForm.value.first_name;
      const lastName = basicForm.value.last_name;
      if (firstName || lastName) {
        return `${firstName} ${lastName}`.trim();
      }
      return ((_a = authStore.user) == null ? void 0 : _a.name) || ((_b = authStore.user) == null ? void 0 : _b.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19";
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"><div class="relative h-48 bg-gray-200 group"><img${ssrRenderAttr("src", coverPreview.value)} class="w-full h-full object-cover transition-opacity group-hover:opacity-90"><div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/30"><button class="bg-white/90 text-gray-800 px-4 py-2 rounded-full font-medium shadow-lg hover:bg-white flex items-center gap-2">`);
      if (isUploadingCover.value) {
        _push(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), { icon: "fluent:camera-24-regular" }, null, _parent));
      }
      _push(` \u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E20\u0E32\u0E1E\u0E1B\u0E01 </button></div><input type="file" class="hidden" accept="image/*"></div><div class="px-6 pb-6 relative"><div class="absolute -top-12 left-6 w-24 h-24 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden group shadow-md bg-gray-100 dark:bg-gray-700"><img${ssrRenderAttr("src", avatarPreview.value || "/images/default-avatar.png")} class="w-full h-full object-cover"><div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 cursor-pointer">`);
      if (isUploadingAvatar.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "text-white w-6 h-6"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:camera-24-regular",
          class: "text-white w-6 h-6"
        }, null, _parent));
      }
      _push(`</div><input type="file" class="hidden" accept="image/*"></div><div class="ml-28 pt-2"><h3 class="text-xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(displayName.value)}</h3><p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:mail-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` ${ssrInterpolate(((_a = unref(authStore).user) == null ? void 0 : _a.email) || "-")}</p><p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:key-24-regular",
        class: "w-3 h-3"
      }, null, _parent));
      _push(` ${ssrInterpolate(((_b = unref(authStore).user) == null ? void 0 : _b.reference_code) || "-")}</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"><div class="border-b border-gray-100 dark:border-gray-700"><nav class="flex overflow-x-auto"><!--[-->`);
      ssrRenderList(sections, (section) => {
        _push(`<button class="${ssrRenderClass([activeSection.value === section.id ? "border-blue-500 text-blue-600 dark:text-blue-400" : "border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300", "flex-1 min-w-max px-6 py-4 text-sm font-medium transition-colors border-b-2"])}"><div class="flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: section.icon,
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(section.label)}</span></div></button>`);
      });
      _push(`<!--]--></nav></div><div class="p-6">`);
      if (activeSection.value === "basic") {
        _push(`<div class="space-y-6"><div><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E35\u0E48\u0E41\u0E2A\u0E14\u0E07\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19</p></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E0A\u0E37\u0E48\u0E2D\u0E08\u0E23\u0E34\u0E07</label><input${ssrRenderAttr("value", basicForm.value.first_name)} type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E08\u0E23\u0E34\u0E07"></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25</label><input${ssrRenderAttr("value", basicForm.value.last_name)} type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25"></div><div class="md:col-span-2 space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E09\u0E31\u0E19 (Bio)</label><textarea rows="4" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" placeholder="\u0E1A\u0E2D\u0E01\u0E40\u0E25\u0E48\u0E32\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E15\u0E31\u0E27\u0E04\u0E38\u0E13...">${ssrInterpolate(basicForm.value.bio)}</textarea></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:location-24-regular",
          class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", basicForm.value.location)} type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E01\u0E23\u0E38\u0E07\u0E40\u0E17\u0E1E\u0E21\u0E2B\u0E32\u0E19\u0E04\u0E23, \u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28\u0E44\u0E17\u0E22"></div></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:globe-24-regular",
          class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", basicForm.value.website)} type="url" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="https://yourwebsite.com"></div></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</label><input${ssrRenderAttr("value", basicForm.value.birthdate)} type="date" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E40\u0E1E\u0E28</label><select class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"><option value="male"${ssrIncludeBooleanAttr(Array.isArray(basicForm.value.gender) ? ssrLooseContain(basicForm.value.gender, "male") : ssrLooseEqual(basicForm.value.gender, "male")) ? " selected" : ""}>\u0E0A\u0E32\u0E22</option><option value="female"${ssrIncludeBooleanAttr(Array.isArray(basicForm.value.gender) ? ssrLooseContain(basicForm.value.gender, "female") : ssrLooseEqual(basicForm.value.gender, "female")) ? " selected" : ""}>\u0E2B\u0E0D\u0E34\u0E07</option><option value="other"${ssrIncludeBooleanAttr(Array.isArray(basicForm.value.gender) ? ssrLooseContain(basicForm.value.gender, "other") : ssrLooseEqual(basicForm.value.gender, "other")) ? " selected" : ""}>\u0E2D\u0E37\u0E48\u0E19\u0E46</option></select></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeSection.value === "personal") {
        _push(`<div class="space-y-6"><div><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E41\u0E25\u0E30\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:call-24-regular",
          class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", personalForm.value.phone_number)} type="tel" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="08xxxxxxxx"></div></div><div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:home-24-regular",
          class: "absolute left-3 top-3 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<textarea rows="2" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="\u0E1A\u0E49\u0E32\u0E19\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48, \u0E16\u0E19\u0E19, \u0E41\u0E02\u0E27\u0E07/\u0E15\u0E33\u0E1A\u0E25">${ssrInterpolate(personalForm.value.address)}</textarea></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E40\u0E21\u0E37\u0E2D\u0E07/\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14</label><input${ssrRenderAttr("value", personalForm.value.city)} type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="\u0E40\u0E21\u0E37\u0E2D\u0E07/\u0E08\u0E31\u0E07\u0E2B\u0E27\u0E31\u0E14"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28</label><input${ssrRenderAttr("value", personalForm.value.country)} type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="\u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">\u0E23\u0E2B\u0E31\u0E2A\u0E44\u0E1B\u0E23\u0E29\u0E13\u0E35\u0E22\u0E4C</label><input${ssrRenderAttr("value", personalForm.value.postal_code)} type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="\u0E23\u0E2B\u0E31\u0E2A\u0E44\u0E1B\u0E23\u0E29\u0E13\u0E35\u0E22\u0E4C"></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeSection.value === "professional") {
        _push(`<div class="space-y-6"><div><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2D\u0E32\u0E0A\u0E35\u0E1E</h3><p class="text-sm text-gray-500 dark:text-gray-400">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E17\u0E33\u0E07\u0E32\u0E19\u0E41\u0E25\u0E30\u0E17\u0E31\u0E01\u0E29\u0E30\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E15\u0E33\u0E41\u0E2B\u0E19\u0E48\u0E07\u0E07\u0E32\u0E19</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:briefcase-24-regular",
          class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", professionalForm.value.job_title)} type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E15\u0E33\u0E41\u0E2B\u0E19\u0E48\u0E07\u0E07\u0E32\u0E19"></div></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E1A\u0E23\u0E34\u0E29\u0E31\u0E17/\u0E2D\u0E07\u0E04\u0E4C\u0E01\u0E23</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:building-24-regular",
          class: "absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<input${ssrRenderAttr("value", professionalForm.value.company)} type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E1A\u0E23\u0E34\u0E29\u0E31\u0E17/\u0E2D\u0E07\u0E04\u0E4C\u0E01\u0E23"></div></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E2D\u0E38\u0E15\u0E2A\u0E32\u0E2B\u0E01\u0E23\u0E23\u0E21</label><input${ssrRenderAttr("value", professionalForm.value.industry)} type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E2D\u0E38\u0E15\u0E2A\u0E32\u0E2B\u0E01\u0E23\u0E23\u0E21"></div><div class="space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E1B\u0E23\u0E30\u0E2A\u0E1A\u0E01\u0E32\u0E23\u0E13\u0E4C (\u0E1B\u0E35)</label><input${ssrRenderAttr("value", professionalForm.value.experience_years)} type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="\u0E08\u0E33\u0E19\u0E27\u0E19\u0E1B\u0E35"></div><div class="md:col-span-2 space-y-1"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300">\u0E17\u0E31\u0E01\u0E29\u0E30 (\u0E04\u0E31\u0E48\u0E19\u0E14\u0E49\u0E27\u0E22\u0E08\u0E38\u0E25\u0E20\u0E32\u0E04)</label><div class="relative">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:lightbulb-24-regular",
          class: "absolute left-3 top-3 text-gray-400 w-5 h-5"
        }, null, _parent));
        _push(`<textarea rows="3" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" placeholder="\u0E17\u0E31\u0E01\u0E29\u0E301, \u0E17\u0E31\u0E01\u0E29\u0E302, \u0E17\u0E31\u0E01\u0E29\u0E303">${ssrInterpolate(professionalForm.value.skills)}</textarea></div><p class="mt-1 text-xs text-gray-500">\u0E04\u0E31\u0E48\u0E19\u0E17\u0E31\u0E01\u0E29\u0E30\u0E41\u0E15\u0E48\u0E25\u0E30\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E14\u0E49\u0E27\u0E22\u0E08\u0E38\u0E25\u0E20\u0E32\u0E04 (,)</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="pt-6 border-t border-gray-100 dark:border-gray-700 flex justify-end"><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (activeSection.value === "basic") {
        _push(`<span>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19</span>`);
      } else if (activeSection.value === "personal") {
        _push(`<span>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</span>`);
      } else if (activeSection.value === "professional") {
        _push(`<span>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2D\u0E32\u0E0A\u0E35\u0E1E</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</button></div></div></div></div>`);
    };
  }
});
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/settings/ProfileInfo.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "PrivacySettings",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    useAuthStore();
    const isLoading = ref(false);
    const privacyForm = ref({
      profile_visibility: "public",
      // public, friends, private
      show_email: false,
      show_phone: false,
      show_birthdate: false,
      show_location: false,
      allow_friend_requests: true,
      allow_messages: true,
      show_online_status: true
    });
    const visibilityOptions = [
      {
        value: "public",
        label: "\u0E2A\u0E32\u0E18\u0E32\u0E23\u0E13\u0E30",
        description: "\u0E17\u0E38\u0E01\u0E04\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49",
        icon: "fluent:earth-24-regular",
        color: "green"
      },
      {
        value: "friends",
        label: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19",
        description: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49",
        icon: "fluent:people-24-regular",
        color: "blue"
      },
      {
        value: "private",
        label: "\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27",
        description: "\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E04\u0E38\u0E13\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49",
        icon: "fluent:lock-closed-24-regular",
        color: "red"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"><div class="p-6 border-b border-gray-100 dark:border-gray-700"><h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:eye-24-regular",
        class: "text-blue-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E21\u0E2D\u0E07\u0E40\u0E2B\u0E47\u0E19\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C </h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-1">\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E27\u0E48\u0E32\u0E43\u0E04\u0E23\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E1A\u0E49\u0E32\u0E07</p></div><div class="p-6"><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><!--[-->`);
      ssrRenderList(visibilityOptions, (option) => {
        _push(`<div class="${ssrRenderClass([[
          privacyForm.value.profile_visibility === option.value ? `border-${option.color}-500 bg-${option.color}-50 dark:bg-${option.color}-900/20` : "border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600"
        ], "relative p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"])}"><div class="flex items-center gap-3 mb-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: option.icon,
          class: ["w-6 h-6", `text-${option.color}-500`]
        }, null, _parent));
        _push(`<span class="font-semibold text-gray-900 dark:text-white">${ssrInterpolate(option.label)}</span></div><p class="text-sm text-gray-600 dark:text-gray-400">${ssrInterpolate(option.description)}</p>`);
        if (privacyForm.value.profile_visibility === option.value) {
          _push(`<div class="absolute top-3 right-3">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: `text-${option.color}-500`
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      });
      _push(`<!--]--></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"><div class="p-6 border-b border-gray-100 dark:border-gray-700"><h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:person-info-24-regular",
        class: "text-purple-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 </h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-1">\u0E04\u0E27\u0E1A\u0E04\u0E38\u0E21\u0E27\u0E48\u0E32\u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E43\u0E14\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19</p></div><div class="p-6 space-y-4"><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:mail-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E41\u0E2A\u0E14\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.show_email) ? ssrLooseContain(privacyForm.value.show_email, null) : privacyForm.value.show_email) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:call-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E41\u0E2A\u0E14\u0E07\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23\u0E28\u0E31\u0E1E\u0E17\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.show_phone) ? ssrLooseContain(privacyForm.value.show_phone, null) : privacyForm.value.show_phone) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:calendar-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E41\u0E2A\u0E14\u0E07\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.show_birthdate) ? ssrLooseContain(privacyForm.value.show_birthdate, null) : privacyForm.value.show_birthdate) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:location-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E41\u0E2A\u0E14\u0E07\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.show_location) ? ssrLooseContain(privacyForm.value.show_location, null) : privacyForm.value.show_location) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div></div></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"><div class="p-6 border-b border-gray-100 dark:border-gray-700"><h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chat-24-regular",
        class: "text-green-500"
      }, null, _parent));
      _push(` \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E01\u0E32\u0E23\u0E42\u0E15\u0E49\u0E15\u0E2D\u0E1A </h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-1">\u0E04\u0E27\u0E1A\u0E04\u0E38\u0E21\u0E27\u0E48\u0E32\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E15\u0E49\u0E15\u0E2D\u0E1A\u0E01\u0E31\u0E1A\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E44\u0E23</p></div><div class="p-6 space-y-4"><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:person-add-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.allow_friend_requests) ? ssrLooseContain(privacyForm.value.allow_friend_requests, null) : privacyForm.value.allow_friend_requests) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chat-bubble-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E2D\u0E19\u0E38\u0E0D\u0E32\u0E15\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E2A\u0E48\u0E07\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.allow_messages) ? ssrLooseContain(privacyForm.value.allow_messages, null) : privacyForm.value.allow_messages) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"><div class="flex items-center gap-3">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:presence-available-24-regular",
        class: "text-gray-500 w-5 h-5"
      }, null, _parent));
      _push(`<div><p class="font-medium text-gray-900 dark:text-white">\u0E41\u0E2A\u0E14\u0E07\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C</p><p class="text-sm text-gray-500 dark:text-gray-400">\u0E41\u0E2A\u0E14\u0E07\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E40\u0E2B\u0E47\u0E19</p></div></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(privacyForm.value.show_online_status) ? ssrLooseContain(privacyForm.value.show_online_status, null) : privacyForm.value.show_online_status) ? " checked" : ""} class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div></label></div></div></div><div class="flex justify-end"><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<span>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</span></button></div></div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/settings/PrivacySettings.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "Socials",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    useAuthStore();
    const isLoading = ref(false);
    const socials = ref({
      facebook: "",
      twitter: "",
      instagram: "",
      linkedin: "",
      youtube: "",
      github: "",
      tiktok: ""
    });
    const socialIcons = {
      facebook: "logos:facebook",
      twitter: "logos:twitter",
      instagram: "logos:instagram-icon",
      linkedin: "logos:linkedin-icon",
      youtube: "logos:youtube-icon",
      github: "logos:github-icon",
      tiktok: "logos:tiktok-icon"
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="p-6 border-b border-gray-100 dark:border-gray-700"><h3 class="text-lg font-bold text-gray-900 dark:text-white">Social Networks</h3><p class="text-sm text-gray-500 dark:text-gray-400">Connect your social profiles</p></div><div class="p-6 space-y-6"><!--[-->`);
      ssrRenderList(socials.value, (url, platform) => {
        _push(`<div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center"><div class="md:col-span-3 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: socialIcons[platform],
          class: "w-6 h-6"
        }, null, _parent));
        _push(`<span class="capitalize font-medium text-gray-700 dark:text-gray-300">${ssrInterpolate(platform)}</span></div><div class="md:col-span-9"><input${ssrRenderAttr("value", socials.value[platform])} type="url" placeholder="Profile URL..." class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 transition-all text-sm"></div></div>`);
      });
      _push(`<!--]--><div class="pt-4 flex justify-end"><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(` Save Social Links </button></div></div></div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/settings/Socials.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "Security",
  __ssrInlineRender: true,
  setup(__props) {
    const config = useRuntimeConfig();
    config.public.apiBase;
    useAuthStore();
    const isLoading = ref(false);
    const form = ref({
      current_password: "",
      password: "",
      password_confirmation: ""
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-red-50/50 dark:bg-red-900/10"><h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:shield-lock-24-filled",
        class: "text-red-500"
      }, null, _parent));
      _push(` Security &amp; Password </h3><p class="text-sm text-gray-500 dark:text-gray-400">Ensure your account is using a strong password</p></div><div class="p-6 space-y-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Password</label><input${ssrRenderAttr("value", form.value.current_password)} type="password" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500"></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Password</label><input${ssrRenderAttr("value", form.value.password)} type="password" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm New Password</label><input${ssrRenderAttr("value", form.value.password_confirmation)} type="password" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500"></div></div><div class="pt-4 flex justify-end"><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium shadow-lg hover:shadow-red-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), { icon: "svg-spinners:ring-resize" }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<span>Update Password</span></button></div></div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/settings/Security.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "settings",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C"
    });
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    const referenceCode = computed(() => route.params.reference_code);
    computed(() => {
      var _a;
      return ((_a = authStore.user) == null ? void 0 : _a.reference_code) === referenceCode.value;
    });
    watch(() => route.params.reference_code, (newCode) => {
      var _a;
      if (newCode && ((_a = authStore.user) == null ? void 0 : _a.reference_code) !== newCode) {
        router.replace(`/profile/${newCode}`);
      }
    });
    const activeTab = ref("profile");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-7xl mx-auto py-8 px-4" }, _attrs))} data-v-c11c0a98><div class="mb-6" data-v-c11c0a98><h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2" data-v-c11c0a98>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:settings-24-regular",
        class: "w-5 h-5 text-blue-500"
      }, null, _parent));
      _push(` \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E1A\u0E31\u0E0D\u0E0A\u0E35 </h2><p class="text-sm text-gray-500 dark:text-gray-400 mt-1" data-v-c11c0a98> \u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 \u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 \u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E04\u0E27\u0E32\u0E21\u0E1B\u0E25\u0E2D\u0E14\u0E20\u0E31\u0E22 </p></div><div class="flex flex-col lg:flex-row gap-6" data-v-c11c0a98><div class="lg:w-1/4" data-v-c11c0a98><div class="sticky top-24" data-v-c11c0a98>`);
      _push(ssrRenderComponent(_sfc_main$6, {
        "active-tab": activeTab.value,
        "onUpdate:activeTab": ($event) => activeTab.value = $event
      }, null, _parent));
      _push(`</div></div><div class="lg:w-3/4" data-v-c11c0a98>`);
      if (activeTab.value === "account") {
        _push(`<div data-v-c11c0a98>`);
        _push(ssrRenderComponent(_sfc_main$5, null, null, _parent));
        _push(`</div>`);
      } else if (activeTab.value === "profile") {
        _push(`<div data-v-c11c0a98>`);
        _push(ssrRenderComponent(_sfc_main$4, null, null, _parent));
        _push(`</div>`);
      } else if (activeTab.value === "privacy") {
        _push(`<div data-v-c11c0a98>`);
        _push(ssrRenderComponent(_sfc_main$3, null, null, _parent));
        _push(`</div>`);
      } else if (activeTab.value === "socials") {
        _push(`<div data-v-c11c0a98>`);
        _push(ssrRenderComponent(_sfc_main$2, null, null, _parent));
        _push(`</div>`);
      } else if (activeTab.value === "security") {
        _push(`<div data-v-c11c0a98>`);
        _push(ssrRenderComponent(_sfc_main$1, null, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/profile/[reference_code]/settings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const settings = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-c11c0a98"]]);

export { settings as default };
//# sourceMappingURL=settings-T9WF0yjH.mjs.map
