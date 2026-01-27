import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import _sfc_main$3 from './DeleteTeamForm-l3qBXa8y.mjs';
import { S as SectionBorder } from './SectionBorder-BtYcudGu.mjs';
import _sfc_main$2 from './TeamMemberManager-DK8ybVSp.mjs';
import _sfc_main$1 from './UpdateTeamNameForm-DdwXuD-x.mjs';
import { useSSRContext } from 'vue';
import { f as useHead } from './server.mjs';
import './inertia-vue3-CWdJjaLG.mjs';
import 'unhead/utils';
import './ActionSection-DgrI_Sk1.mjs';
import './SectionTitle-BqtbC2dE.mjs';
import './ConfirmationModal-QYEdkObj.mjs';
import './Modal-DYe4d1RC.mjs';
import './DangerButton-B1fbLIbl.mjs';
import './SecondaryButton-CNmWyvs4.mjs';
import './ActionMessage-CLeRFQtH.mjs';
import './DialogModal-CfsI63S_.mjs';
import './FormSection-fwBpxzn-.mjs';
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
  __name: "Show",
  __ssrInlineRender: true,
  props: {
    team: Object,
    availableRoles: Array,
    permissions: Object
  },
  setup(__props) {
    useHead({
      title: "Team Settings"
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6"> Team Settings </h2>`);
      _push(ssrRenderComponent(_sfc_main$1, {
        team: __props.team,
        permissions: __props.permissions
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$2, {
        class: "mt-10 sm:mt-0",
        team: __props.team,
        "available-roles": __props.availableRoles,
        "user-permissions": __props.permissions
      }, null, _parent));
      if (__props.permissions.canDeleteTeam && !__props.team.personal_team) {
        _push(`<!--[-->`);
        _push(ssrRenderComponent(SectionBorder, null, null, _parent));
        _push(ssrRenderComponent(_sfc_main$3, {
          class: "mt-10 sm:mt-0",
          team: __props.team
        }, null, _parent));
        _push(`<!--]-->`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Teams/Show.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Show-DZJyYulh.mjs.map
