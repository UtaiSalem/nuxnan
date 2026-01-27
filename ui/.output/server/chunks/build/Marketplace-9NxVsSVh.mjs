import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, withCtx, createTextVNode, unref, mergeProps, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString, withModifiers, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead } from './server.mjs';
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
import 'unhead/utils';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "ProductCard",
  __ssrInlineRender: true,
  props: {
    product: {}
  },
  setup(__props) {
    const addToCart = () => {
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(_component_NuxtLink, mergeProps({
        to: `/product/${__props.product.id}`,
        class: "bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all hover:-translate-y-1 duration-200"
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="relative aspect-square"${_scopeId}><img${ssrRenderAttr("src", __props.product.image)}${ssrRenderAttr("alt", __props.product.name)} class="w-full h-full object-cover"${_scopeId}>`);
            if (__props.product.condition) {
              _push2(`<span class="${ssrRenderClass([__props.product.condition === "New" ? "bg-green-500 text-white" : "bg-amber-500 text-white", "absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-full"])}"${_scopeId}>${ssrInterpolate(__props.product.condition)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="p-3"${_scopeId}><h3 class="font-semibold text-secondary-900 mb-1 line-clamp-2"${_scopeId}>${ssrInterpolate(__props.product.name)}</h3><p class="text-sm text-secondary-600 mb-2"${_scopeId}>${ssrInterpolate(__props.product.seller)}</p><div class="flex items-center justify-between"${_scopeId}><span class="text-lg font-bold text-primary-600"${_scopeId}> $${ssrInterpolate(__props.product.price.toFixed(2))}</span><button class="p-2 hover:bg-primary-50 rounded-full transition-colors"${_scopeId}><i class="pi pi-shopping-cart text-xl text-primary-600"${_scopeId}></i></button></div></div>`);
          } else {
            return [
              createVNode("div", { class: "relative aspect-square" }, [
                createVNode("img", {
                  src: __props.product.image,
                  alt: __props.product.name,
                  class: "w-full h-full object-cover"
                }, null, 8, ["src", "alt"]),
                __props.product.condition ? (openBlock(), createBlock("span", {
                  key: 0,
                  class: ["absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-full", __props.product.condition === "New" ? "bg-green-500 text-white" : "bg-amber-500 text-white"]
                }, toDisplayString(__props.product.condition), 3)) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "p-3" }, [
                createVNode("h3", { class: "font-semibold text-secondary-900 mb-1 line-clamp-2" }, toDisplayString(__props.product.name), 1),
                createVNode("p", { class: "text-sm text-secondary-600 mb-2" }, toDisplayString(__props.product.seller), 1),
                createVNode("div", { class: "flex items-center justify-between" }, [
                  createVNode("span", { class: "text-lg font-bold text-primary-600" }, " $" + toDisplayString(__props.product.price.toFixed(2)), 1),
                  createVNode("button", {
                    onClick: withModifiers(addToCart, ["prevent"]),
                    class: "p-2 hover:bg-primary-50 rounded-full transition-colors"
                  }, [
                    createVNode("i", { class: "pi pi-shopping-cart text-xl text-primary-600" })
                  ])
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/ProductCard.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Marketplace",
  __ssrInlineRender: true,
  setup(__props) {
    const activeCategory = ref("All");
    const categories = ["All", "Electronics", "Fashion", "Home", "Sports", "Books"];
    const products = ref([
      {
        id: 1,
        name: "Wireless Headphones",
        price: 129.99,
        image: "https://picsum.photos/400/400?random=60",
        seller: "Tech Store",
        condition: "New"
      },
      {
        id: 2,
        name: "Vintage Camera",
        price: 299.99,
        image: "https://picsum.photos/400/400?random=61",
        seller: "Camera Shop",
        condition: "Used"
      },
      {
        id: 3,
        name: "Running Shoes",
        price: 89.99,
        image: "https://picsum.photos/400/400?random=62",
        seller: "Sports Gear",
        condition: "New"
      },
      {
        id: 4,
        name: "Designer Watch",
        price: 499.99,
        image: "https://picsum.photos/400/400?random=63",
        seller: "Luxury Store",
        condition: "New"
      },
      {
        id: 5,
        name: "Gaming Keyboard",
        price: 159.99,
        image: "https://picsum.photos/400/400?random=64",
        seller: "Gaming Hub",
        condition: "New"
      },
      {
        id: 6,
        name: "Leather Backpack",
        price: 79.99,
        image: "https://picsum.photos/400/400?random=65",
        seller: "Fashion Store",
        condition: "Used"
      }
    ]);
    useHead({
      title: "Market Place"
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      const _component_ProductCard = _sfc_main$1;
      _push(`<div${ssrRenderAttrs(_attrs)}><section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4"><div class="container mx-auto px-4"><h1 class="text-2xl font-bold mb-1">Market Place</h1><div class="flex items-center gap-2 text-sm text-primary-100">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "hover:text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Home`);
          } else {
            return [
              createTextVNode("Home")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:chevron-right",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span>Market Place</span></div></div></section><div class="container mx-auto px-4 mb-6"><div class="flex gap-2 overflow-x-auto pb-2"><!--[-->`);
      ssrRenderList(categories, (category) => {
        _push(`<button class="${ssrRenderClass([
          unref(activeCategory) === category ? "bg-primary-600 text-white" : "bg-white text-secondary-700 hover:bg-gray-100",
          "px-4 py-2 rounded-full font-medium whitespace-nowrap transition-colors"
        ])}">${ssrInterpolate(category)}</button>`);
      });
      _push(`<!--]--></div></div><div class="container mx-auto px-4 pb-6"><div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"><!--[-->`);
      ssrRenderList(unref(products), (product) => {
        _push(ssrRenderComponent(_component_ProductCard, {
          key: product.id,
          product
        }, null, _parent));
      });
      _push(`<!--]--></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Earn/Marketplace.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Marketplace-9NxVsSVh.mjs.map
