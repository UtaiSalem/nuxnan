import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { mergeProps, withCtx, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent } from 'vue/server-renderer';

const _sfc_main = {
  __name: "AcademyNavbarTab",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    activeTab: {
      type: Number,
      default: 0
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 shadow rounded-lg p-2 mb-4 flex gap-2 overflow-x-auto" }, _attrs))}>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/Learn/Academy/${((_b = (_a = __props.academy) == null ? void 0 : _a.data) == null ? void 0 : _b.name) || ((_c = __props.academy) == null ? void 0 : _c.name)}`,
        class: [
          "px-4 py-2 rounded font-medium transition-colors",
          __props.activeTab === 0 ? "bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
        ]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Home `);
          } else {
            return [
              createTextVNode(" Home ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/Learn/Academy/${((_e = (_d = __props.academy) == null ? void 0 : _d.data) == null ? void 0 : _e.name) || ((_f = __props.academy) == null ? void 0 : _f.name)}/groups`,
        class: [
          "px-4 py-2 rounded font-medium transition-colors",
          __props.activeTab === 1 ? "bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
        ]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Groups `);
          } else {
            return [
              createTextVNode(" Groups ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/Learn/Academy/${((_h = (_g = __props.academy) == null ? void 0 : _g.data) == null ? void 0 : _h.name) || ((_i = __props.academy) == null ? void 0 : _i.name)}/courses`,
        class: [
          "px-4 py-2 rounded font-medium transition-colors",
          __props.activeTab === 2 ? "bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
        ]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Courses `);
          } else {
            return [
              createTextVNode(" Courses ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/Learn/Academy/${((_k = (_j = __props.academy) == null ? void 0 : _j.data) == null ? void 0 : _k.name) || ((_l = __props.academy) == null ? void 0 : _l.name)}/members`,
        class: [
          "px-4 py-2 rounded font-medium transition-colors",
          __props.activeTab === 3 ? "bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
        ]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Members `);
          } else {
            return [
              createTextVNode(" Members ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/AcademyNavbarTab.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=AcademyNavbarTab-BFhMwTIK.mjs.map
