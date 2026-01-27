import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderStyle } from 'vue/server-renderer';
import { mergeProps, useSSRContext } from 'vue';
import { p as publicAssetsURL } from '../_/nitro.mjs';
import { _ as _export_sfc } from './server.mjs';
import { _ as _imports_2$2 } from './virtual_public-bm40i66I.mjs';
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

const _imports_0$3 = publicAssetsURL("/storage/images/badge/warrior-b.png");
const _imports_1$2 = publicAssetsURL("/storage/images/badge/mightiers-b.png");
const _imports_2$1 = publicAssetsURL("/storage/images/badge/goldc.png");
const _sfc_main$c = {
  __name: "HeroSection",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "relative bg-linear-to-br from-sky-100 via-blue-50 to-white text-gray-800 overflow-hidden" }, _attrs))} data-v-966d5d74><div class="absolute inset-0 overflow-hidden" data-v-966d5d74><div class="absolute -top-40 -right-40 w-80 h-80 bg-sky-200/40 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob" data-v-966d5d74></div><div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-100/40 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob animation-delay-2000" data-v-966d5d74></div><div class="absolute top-40 left-20 w-80 h-80 bg-cyan-100/40 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob animation-delay-4000" data-v-966d5d74></div></div><div class="absolute inset-0 grid-pattern" style="${ssrRenderStyle({ "background-size": "50px 50px" })}" data-v-966d5d74></div><div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-4" data-v-966d5d74><div class="container mx-auto text-center max-w-4xl" data-v-966d5d74><div class="text-xl font-bold mb-6 leading-tight animate-fade-in-up" data-v-966d5d74><span class="block mb-2 text-indigo-700" data-v-966d5d74>Welcome to</span><span class="font-audiowide text-5xl md:text-7xl bg-clip-text text-transparent bg-linear-to-r from-purple-600 to-blue-600" data-v-966d5d74> www.plearnd.com </span></div><p class="font-prompt text-xl md:text-2xl mb-12 text-gray-600 leading-relaxed animate-fade-in-up animation-delay-200 text-center" data-v-966d5d74> \u0E40\u0E23\u0E35\u0E22\u0E19\u0E1A\u0E49\u0E32\u0E07 \u0E40\u0E25\u0E48\u0E19\u0E1A\u0E49\u0E32\u0E07 \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49\u0E14\u0E49\u0E27\u0E22 PlearnD </p><div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 animate-fade-in-up animation-delay-600" data-v-966d5d74><div class="flex flex-col items-center" data-v-966d5d74><div class="w-16 h-16 bg-white/70 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 border border-gray-200/50" data-v-966d5d74><img class="badge-item-preview-image"${ssrRenderAttr("src", _imports_0$3)} alt="badge-warrior-b" data-v-966d5d74></div><span class="text-sm font-medium text-gray-600" data-v-966d5d74>Play</span></div><div class="flex flex-col items-center" data-v-966d5d74><div class="w-16 h-16 bg-white/70 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 border border-gray-200/50" data-v-966d5d74><img class="badge-item-preview-image"${ssrRenderAttr("src", _imports_1$2)} alt="badge-mightiers-b" data-v-966d5d74></div><span class="text-sm font-medium text-gray-600" data-v-966d5d74>Learnd</span></div><div class="flex flex-col items-center" data-v-966d5d74><div class="w-16 h-16 bg-white/70 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 border border-gray-200/50" data-v-966d5d74><img class="badge-item-preview-image"${ssrRenderAttr("src", _imports_2$1)} alt="badge-goldc-b" data-v-966d5d74></div><span class="text-sm font-medium text-gray-600" data-v-966d5d74>Earn</span></div></div></div></div><div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce" data-v-966d5d74><svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-966d5d74><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" data-v-966d5d74></path></svg></div></section>`);
    };
  }
};
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/HeroSection.vue");
  return _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const HeroSection = /* @__PURE__ */ _export_sfc(_sfc_main$c, [["__scopeId", "data-v-966d5d74"]]);
const _sfc_main$b = {};
function _sfc_ssrRender$7(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "bg-white py-16" }, _attrs))}><div class="container mx-auto px-4 text-center"><h2 class="text-3xl font-bold mb-4">Welcome to Our Platform</h2><p class="text-gray-600">We provide the best solutions for your learning journey.</p></div></section>`);
}
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/WelcomeSection.vue");
  return _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
const WelcomeSection = /* @__PURE__ */ _export_sfc(_sfc_main$b, [["ssrRender", _sfc_ssrRender$7]]);
const _sfc_main$a = {};
function _sfc_ssrRender$6(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "py-10 bg-gray-50" }, _attrs))}><div class="container mx-auto text-center"><h2 class="text-2xl font-bold">Services</h2><p>Services content placeholder...</p></div></section>`);
}
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/ServicesSection.vue");
  return _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const ServicesSection = /* @__PURE__ */ _export_sfc(_sfc_main$a, [["ssrRender", _sfc_ssrRender$6]]);
const _imports_0$2 = publicAssetsURL("/images/resources/album1.jpg");
const _imports_1$1 = publicAssetsURL("/images/resources/album2.jpg");
const _imports_2 = publicAssetsURL("/images/resources/blog1.jpg");
const _sfc_main$9 = {
  __name: "PortfolioSection",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "bg-white py-16" }, _attrs))}><div class="container mx-auto px-4"><h2 class="text-3xl md:text-4xl font-bold text-center mb-10 text-gray-800">Portfolio</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-8"><div class="rounded-lg overflow-hidden shadow-lg"><img${ssrRenderAttr("src", _imports_0$2)} alt="Portfolio 1" class="w-full h-48 object-cover"><div class="p-6"><h3 class="text-xl font-semibold mb-2">Project One</h3><p class="text-gray-600">A modern social platform for creative communities.</p></div></div><div class="rounded-lg overflow-hidden shadow-lg"><img${ssrRenderAttr("src", _imports_1$1)} alt="Portfolio 2" class="w-full h-48 object-cover"><div class="p-6"><h3 class="text-xl font-semibold mb-2">Project Two</h3><p class="text-gray-600">Mobile-first networking app for professionals.</p></div></div><div class="rounded-lg overflow-hidden shadow-lg"><img${ssrRenderAttr("src", _imports_2)} alt="Portfolio 3" class="w-full h-48 object-cover"><div class="p-6"><h3 class="text-xl font-semibold mb-2">Project Three</h3><p class="text-gray-600">Community-driven platform for events and groups.</p></div></div></div></div></section>`);
    };
  }
};
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/PortfolioSection.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const _sfc_main$8 = {};
function _sfc_ssrRender$5(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "py-10 bg-white" }, _attrs))}><div class="container mx-auto text-center"><h2 class="text-2xl font-bold">Latest Blog</h2><p>Blog content placeholder...</p></div></section>`);
}
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/BlogSection.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const BlogSection = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["ssrRender", _sfc_ssrRender$5]]);
const _sfc_main$7 = {};
function _sfc_ssrRender$4(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "py-10 bg-blue-600 text-white" }, _attrs))}><div class="container mx-auto text-center"><h2 class="text-2xl font-bold">Fun Facts</h2><p>Content placeholder...</p></div></section>`);
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/FunFactsSection.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const FunFactsSection = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender$4]]);
const _sfc_main$6 = {};
function _sfc_ssrRender$3(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "py-10 bg-gray-50" }, _attrs))}><div class="container mx-auto text-center"><h2 class="text-2xl font-bold">Testimonials</h2><p>Content placeholder...</p></div></section>`);
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/TabsTestimonialSection.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const TabsTestimonialSection = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender$3]]);
const _imports_0$1 = publicAssetsURL("/images/resources/user1.jpg");
const _imports_1 = publicAssetsURL("/images/resources/user2.jpg");
const _imports_3 = publicAssetsURL("/images/resources/user4.jpg");
const _sfc_main$5 = {
  __name: "TeamSection",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "bg-white py-16" }, _attrs))}><div class="container mx-auto px-4"><h2 class="text-3xl md:text-4xl font-bold text-center mb-10 text-gray-800">Meet the Team</h2><div class="grid grid-cols-1 md:grid-cols-4 gap-8"><div class="bg-gray-50 rounded-lg shadow p-6 text-center"><img${ssrRenderAttr("src", _imports_0$1)} alt="Team Member 1" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"><h3 class="text-xl font-semibold mb-2 text-blue-700">Alice Brown</h3><p class="text-gray-600">CEO &amp; Founder</p></div><div class="bg-gray-50 rounded-lg shadow p-6 text-center"><img${ssrRenderAttr("src", _imports_1)} alt="Team Member 2" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"><h3 class="text-xl font-semibold mb-2 text-indigo-700">Michael Lee</h3><p class="text-gray-600">Lead Developer</p></div><div class="bg-gray-50 rounded-lg shadow p-6 text-center"><img${ssrRenderAttr("src", _imports_2$2)} alt="Team Member 3" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"><h3 class="text-xl font-semibold mb-2 text-green-700">Sara Kim</h3><p class="text-gray-600">UI/UX Designer</p></div><div class="bg-gray-50 rounded-lg shadow p-6 text-center"><img${ssrRenderAttr("src", _imports_3)} alt="Team Member 4" class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"><h3 class="text-xl font-semibold mb-2 text-pink-700">David Chen</h3><p class="text-gray-600">Marketing Manager</p></div></div></div></section>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/TeamSection.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {};
function _sfc_ssrRender$2(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "py-10 bg-white" }, _attrs))}><div class="container mx-auto text-center"><h2 class="text-2xl font-bold">Pricing</h2><p>Content placeholder...</p></div></section>`);
}
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/PricingSection.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const PricingSection = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["ssrRender", _sfc_ssrRender$2]]);
const _imports_0 = publicAssetsURL("/images/logo.png");
const _sfc_main$3 = {
  __name: "SponsorsSection",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({ class: "bg-white py-16" }, _attrs))}><div class="container mx-auto px-4"><h2 class="text-3xl md:text-4xl font-bold text-center mb-10 text-gray-800">Our Sponsors</h2><div class="flex flex-wrap justify-center gap-8"><img${ssrRenderAttr("src", _imports_0)} alt="Sponsor 1" class="h-12"><img${ssrRenderAttr("src", _imports_0)} alt="Sponsor 2" class="h-12"><img${ssrRenderAttr("src", _imports_0)} alt="Sponsor 3" class="h-12"><img${ssrRenderAttr("src", _imports_0)} alt="Sponsor 4" class="h-12"></div></div></section>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/SponsorsSection.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {};
function _sfc_ssrRender$1(_ctx, _push, _parent, _attrs) {
  _push(`<section${ssrRenderAttrs(mergeProps({ class: "py-10 bg-gray-900 text-white" }, _attrs))}><div class="container mx-auto text-center"><h2 class="text-2xl font-bold">Subscribe to our Newsletter</h2><p>Content placeholder...</p></div></section>`);
}
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/NewsletterSection.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const NewsletterSection = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["ssrRender", _sfc_ssrRender$1]]);
const _sfc_main$1 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<footer${ssrRenderAttrs(mergeProps({ class: "bg-gray-800 text-white py-8" }, _attrs))}><div class="container mx-auto text-center"><p>\xA9 2026 PlearnD. All rights reserved.</p></div></footer>`);
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/landing/FooterSection.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const FooterSection = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main = {
  __name: "CompanyLanding",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(HeroSection, null, null, _parent));
      _push(ssrRenderComponent(WelcomeSection, null, null, _parent));
      _push(ssrRenderComponent(ServicesSection, null, null, _parent));
      _push(ssrRenderComponent(_sfc_main$9, null, null, _parent));
      _push(ssrRenderComponent(BlogSection, null, null, _parent));
      _push(ssrRenderComponent(FunFactsSection, null, null, _parent));
      _push(ssrRenderComponent(TabsTestimonialSection, null, null, _parent));
      _push(ssrRenderComponent(_sfc_main$5, null, null, _parent));
      _push(ssrRenderComponent(PricingSection, null, null, _parent));
      _push(ssrRenderComponent(_sfc_main$3, null, null, _parent));
      _push(ssrRenderComponent(NewsletterSection, null, null, _parent));
      _push(ssrRenderComponent(FooterSection, null, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/CompanyLanding.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CompanyLanding-qhEpAIok.mjs.map
