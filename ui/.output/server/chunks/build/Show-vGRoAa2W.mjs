import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import { S as SectionBorder } from './SectionBorder-BtYcudGu.mjs';
import _sfc_main$2 from './UpdatePasswordForm-3TWXj1Hd.mjs';
import _sfc_main$1 from './UpdateProfileInformationForm-FiE2A5zw.mjs';
import { useSSRContext } from 'vue';
import { f as useHead } from './server.mjs';
import './inertia-vue3-CWdJjaLG.mjs';
import 'unhead/utils';
import './ActionMessage-CLeRFQtH.mjs';
import './FormSection-fwBpxzn-.mjs';
import './SectionTitle-BqtbC2dE.mjs';
import './InputError-DhKaE0Xu.mjs';
import './InputLabel-BgPfgEkm.mjs';
import './PrimaryButton-D8uKmFqJ.mjs';
import './TextInput-B3Ml7s7p.mjs';
import '@iconify/vue';
import './SecondaryButton-CNmWyvs4.mjs';
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

const _sfc_main = {
  __name: "Show",
  __ssrInlineRender: true,
  props: {
    sessions: Array
  },
  setup(__props) {
    useHead({
      title: "Profile"
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6"> Profile </h2>`);
      if (_ctx.$page.props.jetstream.canUpdateProfileInformation) {
        _push(`<div>`);
        _push(ssrRenderComponent(_sfc_main$1, {
          user: _ctx.$page.props.auth.user
        }, null, _parent));
        _push(ssrRenderComponent(SectionBorder, null, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (_ctx.$page.props.jetstream.canUpdatePassword) {
        _push(`<div>`);
        _push(ssrRenderComponent(_sfc_main$2, { class: "mt-10 sm:mt-0" }, null, _parent));
        _push(ssrRenderComponent(SectionBorder, null, null, _parent));
        _push(`</div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/profile/Show.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Show-vGRoAa2W.mjs.map
