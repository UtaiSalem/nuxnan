import { defineComponent, inject, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './MyProgressDetails-CmsVaAN0.mjs';
import { p as useRoute } from './server.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
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
import './AssignmentSubmissionForm-BYC_TqBC.mjs';
import './RichTextEditor-DEEazQRP.mjs';
import './useSweetAlert-jHixiibP.mjs';
import 'sweetalert2';
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "my-progress",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const courseMemberOfAuth = inject("courseMemberOfAuth");
    const route = useRoute();
    const isCourseAdmin = inject("isCourseAdmin");
    const targetMemberId = computed(() => {
      if ((isCourseAdmin == null ? void 0 : isCourseAdmin.value) && route.query.member_id) {
        return Number(route.query.member_id);
      }
      return null;
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}><div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"><h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:graph-box-plus-outline",
        class: "w-5 h-5 text-cyan-500"
      }, null, _parent));
      _push(` \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19 </h2></div>`);
      if (((_a = unref(course)) == null ? void 0 : _a.id) && (unref(targetMemberId) || ((_b = unref(courseMemberOfAuth)) == null ? void 0 : _b.id))) {
        _push(ssrRenderComponent(_sfc_main$1, {
          courseId: unref(course).id,
          memberId: unref(targetMemberId) || ((_c = unref(courseMemberOfAuth)) == null ? void 0 : _c.id)
        }, null, _parent));
      } else {
        _push(`<div class="text-center py-10 text-gray-500"> \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E2B\u0E23\u0E37\u0E2D\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/my-progress.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=my-progress-C9cvQbsL.mjs.map
