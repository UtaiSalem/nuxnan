import { ref, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrRenderComponent } from 'vue/server-renderer';
import ZoneManagement from './ZoneManagement-BWwnKEHv.mjs';
import 'axios';
import './server.mjs';
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

const _sfc_main = {
  __name: "AdminSettings",
  __ssrInlineRender: true,
  setup(__props) {
    const activeSettingsTab = ref("zones");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-white shadow-sm rounded-lg p-6"><h2 class="text-xl font-bold text-gray-900 mb-4"><i class="fas fa-cog mr-2 text-purple-600"></i> \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E30\u0E1A\u0E1A </h2><p class="text-gray-600 mb-6"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E41\u0E25\u0E30\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E04\u0E48\u0E32\u0E15\u0E48\u0E32\u0E07\u0E46 \u0E02\u0E2D\u0E07\u0E23\u0E30\u0E1A\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </p><div class="border-b border-gray-200 mb-6"><nav class="-mb-px flex space-x-8"><button class="${ssrRenderClass([
        activeSettingsTab.value === "zones" ? "border-indigo-500 text-indigo-600" : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300",
        "whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
      ])}"><i class="fas fa-map-marked-alt mr-2"></i> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E42\u0E0B\u0E19 </button></nav></div>`);
      if (activeSettingsTab.value === "zones") {
        _push(`<div>`);
        _push(ssrRenderComponent(ZoneManagement, null, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeSettingsTab.value === "general") {
        _push(`<div><div class="bg-blue-50 border border-blue-200 rounded-lg p-8 text-center"><div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4"><i class="fas fa-sliders-h text-blue-600 text-2xl"></i></div><h3 class="text-lg font-medium text-gray-900 mb-2">\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B</h3><p class="text-gray-600 mb-4">\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E19\u0E35\u0E49\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E22\u0E39\u0E48\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32</p><div class="text-sm text-gray-500"><p class="mb-2">\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E17\u0E35\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32:</p><ul class="inline-block text-left space-y-1"><li>\u2022 \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</li><li>\u2022 \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E40\u0E17\u0E21\u0E40\u0E1E\u0E25\u0E15\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19</li><li>\u2022 \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E2A\u0E34\u0E17\u0E18\u0E34\u0E4C\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E16\u0E36\u0E07</li><li>\u2022 \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</li></ul></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeSettingsTab.value === "notifications") {
        _push(`<div><div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center"><div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4"><i class="fas fa-bell text-yellow-600 text-2xl"></i></div><h3 class="text-lg font-medium text-gray-900 mb-2">\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19</h3><p class="text-gray-600 mb-4">\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E19\u0E35\u0E49\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E22\u0E39\u0E48\u0E23\u0E30\u0E2B\u0E27\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32</p><div class="text-sm text-gray-500"><p class="mb-2">\u0E1F\u0E35\u0E40\u0E08\u0E2D\u0E23\u0E4C\u0E17\u0E35\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1E\u0E31\u0E12\u0E19\u0E32:</p><ul class="inline-block text-left space-y-1"><li>\u2022 \u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E35\u0E48\u0E01\u0E33\u0E25\u0E31\u0E07\u0E08\u0E30\u0E16\u0E36\u0E07</li><li>\u2022 \u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E35\u0E48\u0E40\u0E01\u0E34\u0E19\u0E01\u0E33\u0E2B\u0E19\u0E14</li><li>\u2022 \u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19\u0E1C\u0E48\u0E32\u0E19 Email/LINE</li><li>\u2022 \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E04\u0E27\u0E32\u0E21\u0E16\u0E35\u0E48\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19</li></ul></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/AdminSettings.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AdminSettings-BsDKqRgC.mjs.map
