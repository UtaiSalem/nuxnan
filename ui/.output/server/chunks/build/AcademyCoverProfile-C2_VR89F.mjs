import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderStyle, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';

const _sfc_main = {
  __name: "AcademyCoverProfile",
  __ssrInlineRender: true,
  props: {
    coverImage: String,
    logoImage: String,
    coverHeader: String,
    coverSubheader: String,
    model: String,
    modelTable: String,
    modelableId: Number,
    modelableType: String,
    modelableRoute: String,
    modelData: Object,
    subModelData: Array,
    subModelNameTh: String,
    isAcademyAdmin: Boolean
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative bg-white shadow rounded-lg overflow-hidden mb-4" }, _attrs))}><div class="h-64 bg-gray-300 w-full bg-cover bg-center" style="${ssrRenderStyle({ backgroundImage: `url(${__props.coverImage})` })}"></div><div class="absolute bottom-4 left-4 flex items-end"><img${ssrRenderAttr("src", __props.logoImage)} class="w-32 h-32 rounded-full border-4 border-white shadow-lg bg-white"><div class="ml-4 mb-2"><h1 class="text-3xl font-bold text-white shadow-black drop-shadow-md">${ssrInterpolate(__props.coverHeader)}</h1><p class="text-white drop-shadow-md">${ssrInterpolate(__props.coverSubheader)}</p></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/AcademyCoverProfile.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=AcademyCoverProfile-C2_VR89F.mjs.map
