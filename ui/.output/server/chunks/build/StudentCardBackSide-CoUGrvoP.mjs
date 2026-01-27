import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr } from 'vue/server-renderer';
import { p as publicAssetsURL } from '../_/nitro.mjs';
import { _ as _imports_1 } from './virtual_public-Zc294sLl.mjs';
import { _ as _export_sfc } from './server.mjs';
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

const _imports_0 = publicAssetsURL("/storage/images/std_card_bg2.png");
const _imports_2 = publicAssetsURL("/storage/jsm_director_signature.png");
const _sfc_main = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "w-[640px] h-[400px] bg-white shadow-lg relative overflow-hidden font-thai" }, _attrs))} data-v-30405a10><div class="absolute inset-0" data-v-30405a10><img${ssrRenderAttr("src", _imports_0)} alt="bg" class="w-full h-full object-cover opacity-20" data-v-30405a10></div><div class="bg-blue-800 h-4 w-full absolute top-0 left-0" data-v-30405a10></div><div class="relative z-10 p-6 flex flex-col gap-3" data-v-30405a10><div class="flex items-center space-x-4" data-v-30405a10><img${ssrRenderAttr("src", _imports_1)} alt="logo" class="w-16 h-16 object-contain" data-v-30405a10><div data-v-30405a10><h1 class="text-xl font-bold text-gray-800" data-v-30405a10>\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E23\u0E34\u0E22\u0E18\u0E23\u0E23\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34</h1><p class="text-sm text-gray-700" data-v-30405a10> 148 \u0E21.8 \u0E15.\u0E2A\u0E30\u0E01\u0E2D\u0E21 \u0E2D.\u0E08\u0E30\u0E19\u0E30 \u0E08. \u0E2A\u0E07\u0E02\u0E25\u0E32 90130 \u0E42\u0E17\u0E23. 081-5412281 </p></div></div><div class="mt-2" data-v-30405a10><div class="bg-blue-600 text-white px-3 py-1 w-fit rounded-t-md text-sm" data-v-30405a10> \u0E40\u0E07\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E02\u0E01\u0E32\u0E23\u0E43\u0E0A\u0E49\u0E1A\u0E31\u0E15\u0E23 </div><ul class="list-disc pl-6 text-sm text-gray-800 bg-white py-2 rounded-b-md border border-t-0 border-blue-600" data-v-30405a10><li data-v-30405a10>\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E35\u0E49\u0E43\u0E0A\u0E49\u0E40\u0E09\u0E1E\u0E32\u0E30\u0E1C\u0E39\u0E49\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E08\u0E49\u0E32\u0E02\u0E2D\u0E07\u0E1A\u0E31\u0E15\u0E23\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19</li><li data-v-30405a10>\u0E43\u0E2B\u0E49\u0E1E\u0E01\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E35\u0E49\u0E15\u0E25\u0E2D\u0E14\u0E40\u0E27\u0E25\u0E32\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E43\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E25\u0E30\u0E19\u0E2D\u0E01\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</li><li data-v-30405a10>\u0E40\u0E1B\u0E47\u0E19\u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23\u0E02\u0E2D\u0E07\u0E17\u0E32\u0E07\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E41\u0E2A\u0E14\u0E07\u0E2A\u0E16\u0E32\u0E19\u0E20\u0E32\u0E1E \u0E01\u0E32\u0E23\u0E40\u0E1B\u0E47\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E23\u0E34\u0E22\u0E18\u0E23\u0E23\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34</li><li data-v-30405a10>\u0E1C\u0E39\u0E49\u0E43\u0E14\u0E40\u0E01\u0E47\u0E1A\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E35\u0E49\u0E44\u0E14\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E19\u0E33\u0E2A\u0E48\u0E07\u0E04\u0E37\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</li></ul></div><div class="flex justify-between items-end mt-4" data-v-30405a10><img${ssrRenderAttr("src", _imports_1)} alt="QR" class="w-20 h-20" data-v-30405a10><div class="text-right text-sm text-gray-700" data-v-30405a10><div class="h-10 mb-1" data-v-30405a10><img${ssrRenderAttr("src", _imports_2)} alt="signature" class="h-full" data-v-30405a10></div><div data-v-30405a10>(\u0E19\u0E32\u0E07\u0E0B\u0E32\u0E23\u0E35\u0E19\u0E32 \u0E25\u0E32\u0E40\u0E01\u0E4A\u0E32\u0E30)</div><div data-v-30405a10>\u0E1C\u0E39\u0E49\u0E2D\u0E33\u0E19\u0E27\u0E22\u0E01\u0E32\u0E23\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</div></div></div></div></div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/Card/Admin/StudentCardBackSide.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentCardBackSide = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-30405a10"]]);

export { StudentCardBackSide as default };
//# sourceMappingURL=StudentCardBackSide-CoUGrvoP.mjs.map
