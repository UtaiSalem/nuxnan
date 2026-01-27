import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import _sfc_main$1 from './CreateTeamForm-1pSNhWAo.mjs';
import { useSSRContext } from 'vue';
import { f as useHead } from './server.mjs';
import './inertia-vue3-CWdJjaLG.mjs';
import 'unhead/utils';
import './FormSection-fwBpxzn-.mjs';
import './SectionTitle-BqtbC2dE.mjs';
import './InputError-DhKaE0Xu.mjs';
import './InputLabel-BgPfgEkm.mjs';
import './PrimaryButton-D8uKmFqJ.mjs';
import './TextInput-B3Ml7s7p.mjs';
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

const _sfc_main = {
  __name: "Create",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "Create Team"
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6"> Create Team </h2>`);
      _push(ssrRenderComponent(_sfc_main$1, null, null, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Teams/Create.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Create-BK8rVw_z.mjs.map
