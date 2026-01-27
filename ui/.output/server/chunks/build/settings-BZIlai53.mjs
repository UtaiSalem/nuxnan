import { defineComponent, ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrIncludeBooleanAttr, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "settings",
  __ssrInlineRender: true,
  setup(__props) {
    const sections = [
      { id: "general", name: "\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B", icon: "fluent:settings-24-regular" },
      { id: "appearance", name: "\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25", icon: "fluent:paint-brush-24-regular" },
      { id: "email", name: "\u0E2D\u0E35\u0E40\u0E21\u0E25", icon: "fluent:mail-24-regular" },
      { id: "payment", name: "\u0E01\u0E32\u0E23\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19", icon: "fluent:payment-24-regular" },
      { id: "security", name: "\u0E04\u0E27\u0E32\u0E21\u0E1B\u0E25\u0E2D\u0E14\u0E20\u0E31\u0E22", icon: "fluent:shield-24-regular" },
      { id: "api", name: "API", icon: "fluent:code-24-regular" }
    ];
    const activeSection = ref("general");
    const isSaving = ref(false);
    const generalSettings = ref({
      siteName: "Nuxnan",
      siteDescription: "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E2B\u0E49\u0E2A\u0E19\u0E38\u0E01 \u0E40\u0E25\u0E48\u0E19\u0E43\u0E2B\u0E49\u0E44\u0E14\u0E49\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49 \u0E2A\u0E39\u0E48\u0E01\u0E32\u0E23\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49",
      contactEmail: "support@nuxnan.com",
      supportPhone: "02-xxx-xxxx",
      timezone: "Asia/Bangkok",
      language: "th"
    });
    const appearanceSettings = ref({
      primaryColor: "#6366f1",
      logoUrl: "/storage/images/plearnd-logo.png",
      faviconUrl: "/favicon.ico",
      enableDarkMode: true,
      showFooter: true
    });
    ref({
      smtpHost: "smtp.example.com",
      smtpPort: "587",
      smtpUsername: "",
      smtpPassword: "",
      senderName: "Nuxnan",
      senderEmail: "noreply@nuxnan.com"
    });
    const paymentSettings = ref({
      enablePayments: true,
      currency: "THB",
      stripeEnabled: false,
      stripePublicKey: "",
      stripeSecretKey: "",
      omiseEnabled: true,
      omisePublicKey: "",
      omiseSecretKey: ""
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-800 dark:text-white">\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E30\u0E1A\u0E1A</h1><p class="text-gray-500 dark:text-gray-400 mt-1"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B\u0E02\u0E2D\u0E07\u0E23\u0E30\u0E1A\u0E1A </p></div><button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors disabled:opacity-50">`);
      if (isSaving.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:save-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
      }
      _push(` ${ssrInterpolate(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32")}</button></div><div class="flex flex-col lg:flex-row gap-6"><div class="lg:w-64 flex-shrink-0"><div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700"><nav class="space-y-1"><!--[-->`);
      ssrRenderList(sections, (section) => {
        _push(`<button class="${ssrRenderClass([
          "w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left transition-colors",
          activeSection.value === section.id ? "bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400" : "text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
        ])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: section.icon,
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="font-medium">${ssrInterpolate(section.name)}</span></button>`);
      });
      _push(`<!--]--></nav></div></div><div class="flex-1"><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">`);
      if (activeSection.value === "general") {
        _push(`<div class="space-y-6"><h2 class="text-lg font-semibold text-gray-800 dark:text-white">\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C </label><input${ssrRenderAttr("value", generalSettings.value.siteName)} type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2D\u0E35\u0E40\u0E21\u0E25\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D </label><input${ssrRenderAttr("value", generalSettings.value.contactEmail)} type="email" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div><div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E27\u0E47\u0E1A\u0E44\u0E0B\u0E15\u0E4C </label><textarea rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">${ssrInterpolate(generalSettings.value.siteDescription)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E40\u0E02\u0E15\u0E40\u0E27\u0E25\u0E32 </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><option value="Asia/Bangkok"${ssrIncludeBooleanAttr(Array.isArray(generalSettings.value.timezone) ? ssrLooseContain(generalSettings.value.timezone, "Asia/Bangkok") : ssrLooseEqual(generalSettings.value.timezone, "Asia/Bangkok")) ? " selected" : ""}>Asia/Bangkok (GMT+7)</option><option value="UTC"${ssrIncludeBooleanAttr(Array.isArray(generalSettings.value.timezone) ? ssrLooseContain(generalSettings.value.timezone, "UTC") : ssrLooseEqual(generalSettings.value.timezone, "UTC")) ? " selected" : ""}>UTC (GMT+0)</option></select></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E20\u0E32\u0E29\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><option value="th"${ssrIncludeBooleanAttr(Array.isArray(generalSettings.value.language) ? ssrLooseContain(generalSettings.value.language, "th") : ssrLooseEqual(generalSettings.value.language, "th")) ? " selected" : ""}>\u0E44\u0E17\u0E22</option><option value="en"${ssrIncludeBooleanAttr(Array.isArray(generalSettings.value.language) ? ssrLooseContain(generalSettings.value.language, "en") : ssrLooseEqual(generalSettings.value.language, "en")) ? " selected" : ""}>English</option></select></div></div></div>`);
      } else if (activeSection.value === "appearance") {
        _push(`<div class="space-y-6"><h2 class="text-lg font-semibold text-gray-800 dark:text-white">\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25</h2><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2A\u0E35\u0E2B\u0E25\u0E31\u0E01 </label><div class="flex items-center gap-3"><input${ssrRenderAttr("value", appearanceSettings.value.primaryColor)} type="color" class="w-12 h-12 rounded-xl border border-gray-200 dark:border-gray-600 cursor-pointer"><input${ssrRenderAttr("value", appearanceSettings.value.primaryColor)} type="text" class="flex-1 px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> URL \u0E42\u0E25\u0E42\u0E01\u0E49 </label><input${ssrRenderAttr("value", appearanceSettings.value.logoUrl)} type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div><div class="md:col-span-2"><label class="flex items-center gap-3 cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(appearanceSettings.value.enableDarkMode) ? ssrLooseContain(appearanceSettings.value.enableDarkMode, null) : appearanceSettings.value.enableDarkMode) ? " checked" : ""} type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="text-gray-700 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 Dark Mode</span></label></div></div></div>`);
      } else if (activeSection.value === "payment") {
        _push(`<div class="space-y-6"><h2 class="text-lg font-semibold text-gray-800 dark:text-white">\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E01\u0E32\u0E23\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19</h2><div class="space-y-4"><label class="flex items-center gap-3 cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(paymentSettings.value.enablePayments) ? ssrLooseContain(paymentSettings.value.enablePayments, null) : paymentSettings.value.enablePayments) ? " checked" : ""} type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="text-gray-700 dark:text-gray-300">\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E23\u0E30\u0E1A\u0E1A\u0E0A\u0E33\u0E23\u0E30\u0E40\u0E07\u0E34\u0E19</span></label><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> \u0E2A\u0E01\u0E38\u0E25\u0E40\u0E07\u0E34\u0E19 </label><select class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"><option value="THB"${ssrIncludeBooleanAttr(Array.isArray(paymentSettings.value.currency) ? ssrLooseContain(paymentSettings.value.currency, "THB") : ssrLooseEqual(paymentSettings.value.currency, "THB")) ? " selected" : ""}>THB - \u0E1A\u0E32\u0E17\u0E44\u0E17\u0E22</option><option value="USD"${ssrIncludeBooleanAttr(Array.isArray(paymentSettings.value.currency) ? ssrLooseContain(paymentSettings.value.currency, "USD") : ssrLooseEqual(paymentSettings.value.currency, "USD")) ? " selected" : ""}>USD - \u0E14\u0E2D\u0E25\u0E25\u0E32\u0E23\u0E4C\u0E2A\u0E2B\u0E23\u0E31\u0E10</option></select></div><div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl space-y-4"><label class="flex items-center gap-3 cursor-pointer"><input${ssrIncludeBooleanAttr(Array.isArray(paymentSettings.value.omiseEnabled) ? ssrLooseContain(paymentSettings.value.omiseEnabled, null) : paymentSettings.value.omiseEnabled) ? " checked" : ""} type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="font-medium text-gray-800 dark:text-white">Omise</span></label>`);
        if (paymentSettings.value.omiseEnabled) {
          _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div><label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Public Key</label><input${ssrRenderAttr("value", paymentSettings.value.omisePublicKey)} type="text" placeholder="pkey_xxx" class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-lg text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div><div><label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Secret Key</label><input${ssrRenderAttr("value", paymentSettings.value.omiseSecretKey)} type="password" placeholder="skey_xxx" class="w-full px-3 py-2 bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-lg text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
      } else {
        _push(`<div class="text-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:settings-24-regular",
          class: "w-12 h-12 text-gray-300 mx-auto"
        }, null, _parent));
        _push(`<p class="text-gray-500 mt-4">\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E2A\u0E48\u0E27\u0E19\u0E19\u0E35\u0E49\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32</p></div>`);
      }
      _push(`</div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/nuxnan-admin/settings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=settings-BZIlai53.mjs.map
