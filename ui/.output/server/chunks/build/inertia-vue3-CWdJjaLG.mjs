import { defineComponent, ref, watchEffect, resolveComponent, h, hasInjectionContext, inject, getCurrentInstance, toValue, isRef, reactive, computed } from 'vue';
import { a as useNuxtApp, n as navigateTo } from './server.mjs';
import { walkResolver } from 'unhead/utils';

const VueResolver = (_, value) => {
  return isRef(value) ? toValue(value) : value;
};
const headSymbol = "usehead";
// @__NO_SIDE_EFFECTS__
function injectHead() {
  if (hasInjectionContext()) {
    const instance = inject(headSymbol);
    if (!instance) {
      throw new Error("useHead() was called without provide context, ensure you call it through the setup() function.");
    }
    return instance;
  }
  throw new Error("useHead() was called without provide context, ensure you call it through the setup() function.");
}
function useHead(input, options = {}) {
  const head = options.head || /* @__PURE__ */ injectHead();
  return head.ssr ? head.push(input || {}, options) : clientUseHead(head, input, options);
}
function clientUseHead(head, input, options = {}) {
  const deactivated = ref(false);
  let entry;
  watchEffect(() => {
    const i = deactivated.value ? {} : walkResolver(input, VueResolver);
    if (entry) {
      entry.patch(i);
    } else {
      entry = head.push(i, options);
    }
  });
  getCurrentInstance();
  return entry;
}
function addVNodeToHeadObj(node, obj) {
  const nodeType = node.type;
  const type = nodeType === "html" ? "htmlAttrs" : nodeType === "body" ? "bodyAttrs" : nodeType;
  if (typeof type !== "string" || !(type in obj))
    return;
  const props = node.props || {};
  if (node.children) {
    const childrenAttr = "children";
    props.children = Array.isArray(node.children) ? node.children[0][childrenAttr] : node[childrenAttr];
  }
  if (Array.isArray(obj[type]))
    obj[type].push(props);
  else if (type === "title")
    obj.title = props.children;
  else
    obj[type] = props;
}
function vnodesToHeadObj(nodes) {
  const obj = {
    title: void 0,
    htmlAttrs: void 0,
    bodyAttrs: void 0,
    base: void 0,
    meta: [],
    link: [],
    style: [],
    script: [],
    noscript: []
  };
  for (const node of nodes) {
    if (typeof node.type === "symbol" && Array.isArray(node.children)) {
      for (const childNode of node.children)
        addVNodeToHeadObj(childNode, obj);
    } else {
      addVNodeToHeadObj(node, obj);
    }
  }
  return obj;
}
const Head$1 = /* @__PURE__ */ defineComponent({
  name: "Head",
  setup(_, { slots }) {
    const obj = ref({});
    const entry = useHead(obj);
    return () => {
      watchEffect(() => {
        if (!slots.default)
          return;
        entry.patch(vnodesToHeadObj(slots.default()));
      });
      return null;
    };
  }
});
const Head = Head$1;
const Link = defineComponent({
  name: "InertiaLinkShim",
  inheritAttrs: false,
  props: {
    href: { type: String, default: "" },
    as: { type: String, default: void 0 },
    method: { type: String, default: void 0 },
    data: { type: Object, default: void 0 },
    replace: { type: Boolean, default: false },
    preserveScroll: { type: Boolean, default: false },
    preserveState: { type: Boolean, default: false }
  },
  setup(props, { slots, attrs }) {
    const NuxtLink = resolveComponent("NuxtLink");
    if (props.as === "button") {
      return () => {
        var _a, _b;
        return h(
          "button",
          {
            type: (_a = attrs.type) != null ? _a : "button",
            ...attrs
          },
          (_b = slots.default) == null ? void 0 : _b.call(slots)
        );
      };
    }
    return () => {
      var _a;
      return h(
        NuxtLink,
        {
          to: props.href,
          ...attrs
        },
        (_a = slots.default) == null ? void 0 : _a.call(slots)
      );
    };
  }
});
function usePage() {
  const nuxtApp = useNuxtApp();
  return nuxtApp.$inertiaPage || { props: {} };
}
function asPath(input) {
  if (typeof input !== "string") return "/";
  return input.startsWith("/") ? input : `/${input}`;
}
const router = {
  async visit(url) {
    await navigateTo(url);
  },
  async get(url) {
    await navigateTo(url);
  },
  async post(url, _data) {
    try {
      const nuxtApp = useNuxtApp();
      const $fetch = nuxtApp.$fetch || globalThis.$fetch;
      if (typeof $fetch === "function") {
        await $fetch(url, { method: "POST", body: _data });
      }
    } catch {
    }
    await navigateTo("/");
  },
  async put(url, data) {
    return this.post(url, data);
  },
  async patch(url, data) {
    return this.post(url, data);
  },
  async delete(url, data) {
    return this.post(url, data);
  }
};
function useForm(initial = {}) {
  const form = reactive({
    ...initial,
    processing: false,
    errors: {},
    hasErrors: computed(() => Object.keys(form.errors || {}).length > 0),
    recentlySuccessful: false,
    reset: (...keys) => {
      if (!keys.length) {
        Object.keys(initial).forEach((k) => form[k] = initial[k]);
        return;
      }
      keys.forEach((k) => form[k] = initial[k]);
    },
    clearErrors: () => {
      form.errors = {};
    },
    setError: (key, value) => {
      form.errors = { ...form.errors || {}, [key]: value };
    },
    async post(url, options = {}) {
      var _a, _b;
      form.processing = true;
      try {
        const nuxtApp = useNuxtApp();
        const $fetch = nuxtApp.$fetch || globalThis.$fetch;
        if (typeof $fetch === "function") {
          await $fetch(asPath(url), { method: "POST", body: form });
        }
        form.recentlySuccessful = true;
        (_a = options == null ? void 0 : options.onSuccess) == null ? void 0 : _a.call(options);
      } catch (e) {
        (_b = options == null ? void 0 : options.onError) == null ? void 0 : _b.call(options, e);
      } finally {
        form.processing = false;
      }
    }
  });
  return form;
}

export { Head as H, Link as L, usePage as a, router as r, useForm as u };
//# sourceMappingURL=inertia-vue3-CWdJjaLG.mjs.map
