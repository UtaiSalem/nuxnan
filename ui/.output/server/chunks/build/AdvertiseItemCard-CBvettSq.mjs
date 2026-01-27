import { mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';

const _sfc_main = {
  __name: "AdvertiseItemCard",
  __ssrInlineRender: true,
  props: {
    advert: Object,
    index: Number
  },
  emits: ["click"],
  setup(__props) {
    const isVideo = (url) => {
      if (!url) return false;
      return /\.(mp4|webm|ogg)$/i.test(url);
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f, _g;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow cursor-pointer" }, _attrs))}><div class="p-3 flex items-center gap-3"><img${ssrRenderAttr("src", ((_a = __props.advert.advertiser) == null ? void 0 : _a.avatar) || ((_b = __props.advert.user) == null ? void 0 : _b.avatar) || "/images/default-avatar.png")} class="w-10 h-10 rounded-full object-cover bg-gray-100" alt="Profile"><div><h4 class="font-bold text-sm text-blue-600 leading-tight">${ssrInterpolate(((_c = __props.advert.advertiser) == null ? void 0 : _c.name) || ((_d = __props.advert.user) == null ? void 0 : _d.name) || "Unknown User")}</h4><p class="text-xs text-gray-800 font-semibold">${ssrInterpolate(((_e = __props.advert.advertiser) == null ? void 0 : _e.personal_code) || ((_f = __props.advert.user) == null ? void 0 : _f.personal_code) || ((_g = __props.advert.advertiser) == null ? void 0 : _g.id) || "Code")}</p></div><div class="ml-auto"><span class="text-xs text-gray-500">\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E2A\u0E31\u0E21\u0E1E\u0E31\u0E19\u0E18\u0E4C</span></div></div><div class="w-full aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">`);
      if (isVideo(__props.advert.media_image) || __props.advert.media_type && __props.advert.media_type.startsWith("video/")) {
        _push(`<video${ssrRenderAttr("src", __props.advert.media_image)} class="w-full h-full object-cover" autoplay muted loop playsinline></video>`);
      } else {
        _push(`<img${ssrRenderAttr("src", __props.advert.media_image)} class="w-full h-full object-cover" alt="Ad Image" loading="lazy">`);
      }
      _push(`</div><div class="p-3 text-center border-t border-gray-100"><div class="flex items-center justify-center gap-1 text-sm font-bold"><span class="text-amber-400 text-lg">${ssrInterpolate(__props.advert.total_views || 0)}</span><span class="text-teal-500/80 text-xs">views</span><span class="text-gray-300 mx-1">/</span><span class="text-blue-500 text-xs">\u0E43\u0E0A\u0E49\u0E44\u0E14\u0E49</span><span class="text-blue-500 text-lg">${ssrInterpolate(__props.advert.remaining_views || 0)}</span><span class="text-blue-500 text-xs">views</span></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/widgets/advertises/AdvertiseItemCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=AdvertiseItemCard-CBvettSq.mjs.map
