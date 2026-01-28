import process from 'node:process';globalThis._importMeta_=globalThis._importMeta_||{url:"file:///_entry.js",env:process.env};import * as Vue from 'vue';
import { defineComponent, ref, computed, shallowRef, getCurrentInstance as getCurrentInstance$1, provide, cloneVNode, h, createElementBlock, inject, unref, defineAsyncComponent, hasInjectionContext, Suspense, shallowReactive, Fragment, toRef, isRef, nextTick, mergeProps, useSSRContext, createApp, withCtx, createVNode, onErrorCaptured, onServerPrefetch, resolveDynamicComponent, reactive, effectScope, isReadonly, isShallow, isReactive, toRaw, getCurrentScope, Text } from 'vue';
import { e as createError$1, j as hasProtocol, m as isScriptProtocol, k as joinURL, n as withQuery, d as destr, o as klona, q as sanitizeStatusCode, v as getContext, x as getRequestHeader, y as isEqual, z as setCookie, A as getCookie, B as deleteCookie, $ as $fetch$1, C as baseURL, D as createHooks, E as executeAsync, F as toRouteMatcher, G as createRouter$1, H as defu, I as getRequestURL, J as createDefu, l as withoutTrailingSlash, K as parsePath, i as parseQuery } from '../_/nitro.mjs';
import { defineStore, createPinia, setActivePinia, storeToRefs, shouldHydrate } from 'pinia';
import { useRoute as useRoute$1, RouterView, createMemoryHistory, createRouter, START_LOCATION } from 'vue-router';
import { ssrRenderComponent, ssrRenderAttrs, ssrInterpolate, ssrRenderSuspense, ssrRenderVNode, ssrRenderList, ssrRenderClass } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { u as useHead$1, a as useSeoMeta$1, h as headSymbol } from '../routes/renderer.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

function parse$1(str, options) {
  if (typeof str !== "string") {
    throw new TypeError("argument str must be a string");
  }
  const obj = {};
  const opt = options || {};
  const dec = opt.decode || decode;
  let index = 0;
  while (index < str.length) {
    const eqIdx = str.indexOf("=", index);
    if (eqIdx === -1) {
      break;
    }
    let endIdx = str.indexOf(";", index);
    if (endIdx === -1) {
      endIdx = str.length;
    } else if (endIdx < eqIdx) {
      index = str.lastIndexOf(";", eqIdx - 1) + 1;
      continue;
    }
    const key = str.slice(index, eqIdx).trim();
    if (opt?.filter && !opt?.filter(key)) {
      index = endIdx + 1;
      continue;
    }
    if (void 0 === obj[key]) {
      let val = str.slice(eqIdx + 1, endIdx).trim();
      if (val.codePointAt(0) === 34) {
        val = val.slice(1, -1);
      }
      obj[key] = tryDecode(val, dec);
    }
    index = endIdx + 1;
  }
  return obj;
}
function decode(str) {
  return str.includes("%") ? decodeURIComponent(str) : str;
}
function tryDecode(str, decode2) {
  try {
    return decode2(str);
  } catch {
    return str;
  }
}

if (!globalThis.$fetch) {
  globalThis.$fetch = $fetch$1.create({
    baseURL: baseURL()
  });
}
if (!("global" in globalThis)) {
  globalThis.global = globalThis;
}
const appLayoutTransition = false;
const nuxtLinkDefaults = { "componentName": "NuxtLink" };
const asyncDataDefaults = { "value": null, "errorValue": null, "deep": true };
const fetchDefaults = {};
const appId = "nuxt-app";
function getNuxtAppCtx(id = appId) {
  return getContext(id, {
    asyncContext: false
  });
}
const NuxtPluginIndicator = "__nuxt_plugin";
function createNuxtApp(options) {
  let hydratingCount = 0;
  const nuxtApp = {
    _id: options.id || appId || "nuxt-app",
    _scope: effectScope(),
    provide: void 0,
    globalName: "nuxt",
    versions: {
      get nuxt() {
        return "3.20.2";
      },
      get vue() {
        return nuxtApp.vueApp.version;
      }
    },
    payload: shallowReactive({
      ...options.ssrContext?.payload || {},
      data: shallowReactive({}),
      state: reactive({}),
      once: /* @__PURE__ */ new Set(),
      _errors: shallowReactive({})
    }),
    static: {
      data: {}
    },
    runWithContext(fn) {
      if (nuxtApp._scope.active && !getCurrentScope()) {
        return nuxtApp._scope.run(() => callWithNuxt(nuxtApp, fn));
      }
      return callWithNuxt(nuxtApp, fn);
    },
    isHydrating: false,
    deferHydration() {
      if (!nuxtApp.isHydrating) {
        return () => {
        };
      }
      hydratingCount++;
      let called = false;
      return () => {
        if (called) {
          return;
        }
        called = true;
        hydratingCount--;
        if (hydratingCount === 0) {
          nuxtApp.isHydrating = false;
          return nuxtApp.callHook("app:suspense:resolve");
        }
      };
    },
    _asyncDataPromises: {},
    _asyncData: shallowReactive({}),
    _payloadRevivers: {},
    ...options
  };
  {
    nuxtApp.payload.serverRendered = true;
  }
  if (nuxtApp.ssrContext) {
    nuxtApp.payload.path = nuxtApp.ssrContext.url;
    nuxtApp.ssrContext.nuxt = nuxtApp;
    nuxtApp.ssrContext.payload = nuxtApp.payload;
    nuxtApp.ssrContext.config = {
      public: nuxtApp.ssrContext.runtimeConfig.public,
      app: nuxtApp.ssrContext.runtimeConfig.app
    };
  }
  nuxtApp.hooks = createHooks();
  nuxtApp.hook = nuxtApp.hooks.hook;
  {
    const contextCaller = async function(hooks, args) {
      for (const hook of hooks) {
        await nuxtApp.runWithContext(() => hook(...args));
      }
    };
    nuxtApp.hooks.callHook = (name, ...args) => nuxtApp.hooks.callHookWith(contextCaller, name, ...args);
  }
  nuxtApp.callHook = nuxtApp.hooks.callHook;
  nuxtApp.provide = (name, value) => {
    const $name = "$" + name;
    defineGetter(nuxtApp, $name, value);
    defineGetter(nuxtApp.vueApp.config.globalProperties, $name, value);
  };
  defineGetter(nuxtApp.vueApp, "$nuxt", nuxtApp);
  defineGetter(nuxtApp.vueApp.config.globalProperties, "$nuxt", nuxtApp);
  const runtimeConfig = options.ssrContext.runtimeConfig;
  nuxtApp.provide("config", runtimeConfig);
  return nuxtApp;
}
function registerPluginHooks(nuxtApp, plugin2) {
  if (plugin2.hooks) {
    nuxtApp.hooks.addHooks(plugin2.hooks);
  }
}
async function applyPlugin(nuxtApp, plugin2) {
  if (typeof plugin2 === "function") {
    const { provide: provide2 } = await nuxtApp.runWithContext(() => plugin2(nuxtApp)) || {};
    if (provide2 && typeof provide2 === "object") {
      for (const key in provide2) {
        nuxtApp.provide(key, provide2[key]);
      }
    }
  }
}
async function applyPlugins(nuxtApp, plugins2) {
  const resolvedPlugins = /* @__PURE__ */ new Set();
  const unresolvedPlugins = [];
  const parallels = [];
  let error = void 0;
  let promiseDepth = 0;
  async function executePlugin(plugin2) {
    const unresolvedPluginsForThisPlugin = plugin2.dependsOn?.filter((name) => plugins2.some((p) => p._name === name) && !resolvedPlugins.has(name)) ?? [];
    if (unresolvedPluginsForThisPlugin.length > 0) {
      unresolvedPlugins.push([new Set(unresolvedPluginsForThisPlugin), plugin2]);
    } else {
      const promise = applyPlugin(nuxtApp, plugin2).then(async () => {
        if (plugin2._name) {
          resolvedPlugins.add(plugin2._name);
          await Promise.all(unresolvedPlugins.map(async ([dependsOn, unexecutedPlugin]) => {
            if (dependsOn.has(plugin2._name)) {
              dependsOn.delete(plugin2._name);
              if (dependsOn.size === 0) {
                promiseDepth++;
                await executePlugin(unexecutedPlugin);
              }
            }
          }));
        }
      }).catch((e) => {
        if (!plugin2.parallel && !nuxtApp.payload.error) {
          throw e;
        }
        error ||= e;
      });
      if (plugin2.parallel) {
        parallels.push(promise);
      } else {
        await promise;
      }
    }
  }
  for (const plugin2 of plugins2) {
    if (nuxtApp.ssrContext?.islandContext && plugin2.env?.islands === false) {
      continue;
    }
    registerPluginHooks(nuxtApp, plugin2);
  }
  for (const plugin2 of plugins2) {
    if (nuxtApp.ssrContext?.islandContext && plugin2.env?.islands === false) {
      continue;
    }
    await executePlugin(plugin2);
  }
  await Promise.all(parallels);
  if (promiseDepth) {
    for (let i = 0; i < promiseDepth; i++) {
      await Promise.all(parallels);
    }
  }
  if (error) {
    throw nuxtApp.payload.error || error;
  }
}
// @__NO_SIDE_EFFECTS__
function defineNuxtPlugin(plugin2) {
  if (typeof plugin2 === "function") {
    return plugin2;
  }
  const _name = plugin2._name || plugin2.name;
  delete plugin2.name;
  return Object.assign(plugin2.setup || (() => {
  }), plugin2, { [NuxtPluginIndicator]: true, _name });
}
const definePayloadPlugin = defineNuxtPlugin;
function callWithNuxt(nuxt, setup, args) {
  const fn = () => setup();
  const nuxtAppCtx = getNuxtAppCtx(nuxt._id);
  {
    return nuxt.vueApp.runWithContext(() => nuxtAppCtx.callAsync(nuxt, fn));
  }
}
function tryUseNuxtApp(id) {
  let nuxtAppInstance;
  if (hasInjectionContext()) {
    nuxtAppInstance = getCurrentInstance$1()?.appContext.app.$nuxt;
  }
  nuxtAppInstance ||= getNuxtAppCtx(id).tryUse();
  return nuxtAppInstance || null;
}
function useNuxtApp(id) {
  const nuxtAppInstance = tryUseNuxtApp(id);
  if (!nuxtAppInstance) {
    {
      throw new Error("[nuxt] instance unavailable");
    }
  }
  return nuxtAppInstance;
}
// @__NO_SIDE_EFFECTS__
function useRuntimeConfig(_event) {
  return useNuxtApp().$config;
}
function defineGetter(obj, key, val) {
  Object.defineProperty(obj, key, { get: () => val });
}
const LayoutMetaSymbol = /* @__PURE__ */ Symbol("layout-meta");
const PageRouteSymbol = /* @__PURE__ */ Symbol("route");
globalThis._importMeta_.url.replace(/\/app\/.*$/, "/");
const useRouter = () => {
  return useNuxtApp()?.$router;
};
const useRoute = () => {
  if (hasInjectionContext()) {
    return inject(PageRouteSymbol, useNuxtApp()._route);
  }
  return useNuxtApp()._route;
};
// @__NO_SIDE_EFFECTS__
function defineNuxtRouteMiddleware(middleware) {
  return middleware;
}
const isProcessingMiddleware = () => {
  try {
    if (useNuxtApp()._processingMiddleware) {
      return true;
    }
  } catch {
    return false;
  }
  return false;
};
const URL_QUOTE_RE = /"/g;
const navigateTo = (to, options) => {
  to ||= "/";
  const toPath = typeof to === "string" ? to : "path" in to ? resolveRouteObject(to) : useRouter().resolve(to).href;
  const isExternalHost = hasProtocol(toPath, { acceptRelative: true });
  const isExternal = options?.external || isExternalHost;
  if (isExternal) {
    if (!options?.external) {
      throw new Error("Navigating to an external URL is not allowed by default. Use `navigateTo(url, { external: true })`.");
    }
    const { protocol } = new URL(toPath, "http://localhost");
    if (protocol && isScriptProtocol(protocol)) {
      throw new Error(`Cannot navigate to a URL with '${protocol}' protocol.`);
    }
  }
  const inMiddleware = isProcessingMiddleware();
  const router = useRouter();
  const nuxtApp = useNuxtApp();
  {
    if (nuxtApp.ssrContext) {
      const fullPath = typeof to === "string" || isExternal ? toPath : router.resolve(to).fullPath || "/";
      const location2 = isExternal ? toPath : joinURL((/* @__PURE__ */ useRuntimeConfig()).app.baseURL, fullPath);
      const redirect = async function(response) {
        await nuxtApp.callHook("app:redirected");
        const encodedLoc = location2.replace(URL_QUOTE_RE, "%22");
        const encodedHeader = encodeURL(location2, isExternalHost);
        nuxtApp.ssrContext._renderResponse = {
          statusCode: sanitizeStatusCode(options?.redirectCode || 302, 302),
          body: `<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=${encodedLoc}"></head></html>`,
          headers: { location: encodedHeader }
        };
        return response;
      };
      if (!isExternal && inMiddleware) {
        router.afterEach((final) => final.fullPath === fullPath ? redirect(false) : void 0);
        return to;
      }
      return redirect(!inMiddleware ? void 0 : (
        /* abort route navigation */
        false
      ));
    }
  }
  if (isExternal) {
    nuxtApp._scope.stop();
    if (options?.replace) {
      (void 0).replace(toPath);
    } else {
      (void 0).href = toPath;
    }
    if (inMiddleware) {
      if (!nuxtApp.isHydrating) {
        return false;
      }
      return new Promise(() => {
      });
    }
    return Promise.resolve();
  }
  return options?.replace ? router.replace(to) : router.push(to);
};
const abortNavigation = (err) => {
  if (!err) {
    return false;
  }
  err = createError(err);
  if (err.fatal) {
    useNuxtApp().runWithContext(() => showError(err));
  }
  throw err;
};
function resolveRouteObject(to) {
  return withQuery(to.path || "", to.query || {}) + (to.hash || "");
}
function encodeURL(location2, isExternalHost = false) {
  const url = new URL(location2, "http://localhost");
  if (!isExternalHost) {
    return url.pathname + url.search + url.hash;
  }
  if (location2.startsWith("//")) {
    return url.toString().replace(url.protocol, "");
  }
  return url.toString();
}
const NUXT_ERROR_SIGNATURE = "__nuxt_error";
const useError = /* @__NO_SIDE_EFFECTS__ */ () => toRef(useNuxtApp().payload, "error");
const showError = (error) => {
  const nuxtError = createError(error);
  try {
    const error2 = /* @__PURE__ */ useError();
    if (false) ;
    error2.value ||= nuxtError;
  } catch {
    throw nuxtError;
  }
  return nuxtError;
};
const isNuxtError = (error) => !!error && typeof error === "object" && NUXT_ERROR_SIGNATURE in error;
const createError = (error) => {
  const nuxtError = createError$1(error);
  Object.defineProperty(nuxtError, NUXT_ERROR_SIGNATURE, {
    value: true,
    configurable: false,
    writable: false
  });
  return nuxtError;
};
function injectHead(nuxtApp) {
  const nuxt = nuxtApp || tryUseNuxtApp();
  return nuxt?.ssrContext?.head || nuxt?.runWithContext(() => {
    if (hasInjectionContext()) {
      return inject(headSymbol);
    }
  });
}
function useHead(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return useHead$1(input, { head, ...options });
  }
}
function useSeoMeta(input, options = {}) {
  const head = injectHead(options.nuxt);
  if (head) {
    return useSeoMeta$1(input, { head, ...options });
  }
}
async function getRouteRules(arg) {
  const path = typeof arg === "string" ? arg : arg.path;
  {
    useNuxtApp().ssrContext._preloadManifest = true;
    const _routeRulesMatcher = toRouteMatcher(
      createRouter$1({ routes: (/* @__PURE__ */ useRuntimeConfig()).nitro.routeRules })
    );
    return defu({}, ..._routeRulesMatcher.matchAll(path).reverse());
  }
}
function definePayloadReducer(name, reduce) {
  {
    useNuxtApp().ssrContext._payloadReducers[name] = reduce;
  }
}
const payloadPlugin = definePayloadPlugin(() => {
  definePayloadReducer(
    "skipHydrate",
    // We need to return something truthy to be treated as a match
    (data) => !shouldHydrate(data) && 1
  );
});
const unhead_k2P3m_ZDyjlr2mMYnoDPwavjsDN8hBlk9cFai0bbopU = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:head",
  enforce: "pre",
  setup(nuxtApp) {
    const head = nuxtApp.ssrContext.head;
    nuxtApp.vueApp.use(head);
  }
});
function toArray$2(value) {
  return Array.isArray(value) ? value : [value];
}
const __nuxt_page_meta$1u = {
  layout: "main"
};
const __nuxt_page_meta$1t = {
  layout: "main"
};
const __nuxt_page_meta$1s = {
  layout: false
};
const __nuxt_page_meta$1r = {
  layout: "main"
};
const __nuxt_page_meta$1q = {
  layout: "main"
};
const __nuxt_page_meta$1p = {
  layout: "main"
};
const __nuxt_page_meta$1o = {
  layout: "main"
};
const __nuxt_page_meta$1n = {
  layout: "main"
};
const __nuxt_page_meta$1m = {
  layout: "main"
};
const __nuxt_page_meta$1l = {
  layout: "main"
};
const __nuxt_page_meta$1k = {
  layout: "main"
};
const __nuxt_page_meta$1j = {
  layout: false
};
const __nuxt_page_meta$1i = {
  layout: "main"
};
const __nuxt_page_meta$1h = {
  middleware: [
    function(to, from) {
      return navigateTo("/play/newsfeed");
    }
  ]
};
const __nuxt_page_meta$1g = {
  layout: "main"
};
const __nuxt_page_meta$1f = {
  layout: "main"
};
const __nuxt_page_meta$1e = {
  layout: "main"
};
const __nuxt_page_meta$1d = {
  layout: "main"
};
const __nuxt_page_meta$1c = {
  layout: "main"
};
const __nuxt_page_meta$1b = {
  title: "My Favorites"
};
const __nuxt_page_meta$1a = {
  layout: "main"
};
const __nuxt_page_meta$19 = {
  layout: "auth"
};
const __nuxt_page_meta$18 = {
  layout: "main"
};
const __nuxt_page_meta$17 = {
  layout: "main"
};
const __nuxt_page_meta$16 = {
  layout: "main"
};
const __nuxt_page_meta$15 = {
  layout: "main"
};
const __nuxt_page_meta$14 = {
  layout: "main"
};
const __nuxt_page_meta$13 = {
  layout: "main"
};
const __nuxt_page_meta$12 = {
  layout: "main"
};
const __nuxt_page_meta$11 = {
  layout: "main"
};
const __nuxt_page_meta$10 = {
  layout: "main"
};
const __nuxt_page_meta$$ = {
  layout: "main"
};
const __nuxt_page_meta$_ = {
  layout: "main"
};
const __nuxt_page_meta$Z = {
  layout: "main"
};
const __nuxt_page_meta$Y = {
  layout: false
};
const __nuxt_page_meta$X = {
  layout: "main"
};
const __nuxt_page_meta$W = {
  layout: false
};
const __nuxt_page_meta$V = {
  layout: "main"
};
const __nuxt_page_meta$U = {
  layout: "main"
};
const __nuxt_page_meta$T = {
  layout: "none"
};
const __nuxt_page_meta$S = {
  layout: "main"
};
const __nuxt_page_meta$R = {
  layout: "main"
};
const __nuxt_page_meta$Q = {
  layout: "game-layout"
};
const __nuxt_page_meta$P = {
  middleware: [
    function(to, from) {
      const slug = to.params.slug;
      const targetPath = Array.isArray(slug) ? slug.join("/") : slug || "";
      return navigateTo(`/Learn/Courses/${targetPath}`);
    }
  ]
};
const __nuxt_page_meta$O = {
  layout: "main"
};
const __nuxt_page_meta$N = {
  layout: "main"
};
const __nuxt_page_meta$M = {
  layout: "main"
};
const __nuxt_page_meta$L = {
  layout: false
};
const __nuxt_page_meta$K = null;
function useRequestEvent(nuxtApp) {
  nuxtApp ||= useNuxtApp();
  return nuxtApp.ssrContext?.event;
}
function useRequestFetch() {
  return useRequestEvent()?.$fetch || globalThis.$fetch;
}
function prerenderRoutes(path) {
  {
    return;
  }
}
const CookieDefaults = {
  path: "/",
  watch: true,
  decode: (val) => destr(decodeURIComponent(val)),
  encode: (val) => encodeURIComponent(typeof val === "string" ? val : JSON.stringify(val))
};
function useCookie(name, _opts) {
  const opts = { ...CookieDefaults, ..._opts };
  opts.filter ??= (key) => key === name;
  const cookies = readRawCookies(opts) || {};
  let delay;
  if (opts.maxAge !== void 0) {
    delay = opts.maxAge * 1e3;
  } else if (opts.expires) {
    delay = opts.expires.getTime() - Date.now();
  }
  const hasExpired = delay !== void 0 && delay <= 0;
  const cookieValue = klona(hasExpired ? void 0 : cookies[name] ?? opts.default?.());
  const cookie = ref(cookieValue);
  {
    const nuxtApp = useNuxtApp();
    const writeFinalCookieValue = () => {
      if (opts.readonly || isEqual(cookie.value, cookies[name])) {
        return;
      }
      nuxtApp._cookies ||= {};
      if (name in nuxtApp._cookies) {
        if (isEqual(cookie.value, nuxtApp._cookies[name])) {
          return;
        }
      }
      nuxtApp._cookies[name] = cookie.value;
      writeServerCookie(useRequestEvent(nuxtApp), name, cookie.value, opts);
    };
    const unhook = nuxtApp.hooks.hookOnce("app:rendered", writeFinalCookieValue);
    nuxtApp.hooks.hookOnce("app:error", () => {
      unhook();
      return writeFinalCookieValue();
    });
  }
  return cookie;
}
function readRawCookies(opts = {}) {
  {
    return parse$1(getRequestHeader(useRequestEvent(), "cookie") || "", opts);
  }
}
function writeServerCookie(event, name, value, opts = {}) {
  if (event) {
    if (value !== null && value !== void 0) {
      return setCookie(event, name, value, opts);
    }
    if (getCookie(event, name) !== void 0) {
      return deleteCookie(event, name, opts);
    }
  }
}
const useAuthStore = defineStore("auth", () => {
  const config = /* @__PURE__ */ useRuntimeConfig();
  const apiBase = config.public.apiBase;
  const user = ref(null);
  const token = useCookie("token", {
    maxAge: 60 * 60 * 24 * 7,
    // 7 days
    secure: "production" === "production",
    // Only secure in production (HTTPS)
    sameSite: "lax"
  });
  const isAuthenticated = computed(() => !!token.value);
  const isRefreshing = ref(false);
  const isLoading = ref(false);
  async function login(credentials) {
    isLoading.value = true;
    try {
      const response = await $fetch(`${apiBase}/api/login`, {
        method: "POST",
        credentials: "include",
        body: {
          login: credentials.email || credentials.login,
          password: credentials.password
        }
      });
      if (response.success) {
        const accessToken = response.access_token || response.data?.accessToken;
        const userData = response.user || response.data?.user;
        if (accessToken) {
          token.value = accessToken;
          user.value = userData;
        } else {
          throw new Error("Invalid response from server");
        }
      } else {
        throw new Error(response.message || "Invalid response from server");
      }
      return response;
    } catch (e) {
      console.error("Login error:", e);
      const errorMessage = e.data?.message || e.statusMessage || e.message || "Login failed";
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  }
  async function register(userData) {
    isLoading.value = true;
    try {
      const response = await $fetch(`${apiBase}/api/register`, {
        method: "POST",
        credentials: "include",
        body: userData
      });
      if (response.success && response.data) {
        const { accessToken, user: responseUser } = response.data;
        if (accessToken) {
          token.value = accessToken;
          user.value = responseUser;
        } else {
          throw new Error("Invalid response from server");
        }
      } else {
        throw new Error("Invalid response from server");
      }
      return response;
    } catch (e) {
      console.error("Registration error:", e);
      const errorMessage = e.data?.message || e.statusMessage || e.message || "Registration failed";
      throw new Error(errorMessage);
    } finally {
      isLoading.value = false;
    }
  }
  async function fetchUser() {
    if (!token.value) {
      throw new Error("No authentication token");
    }
    try {
      const response = await $fetch(`${apiBase}/api/me`, {
        credentials: "include",
        headers: {
          Authorization: `Bearer ${token.value}`
        }
      });
      if (response.success && response.data) {
        user.value = response.data;
      } else if (response.user) {
        user.value = response.user;
      } else if (response.data) {
        user.value = response.data;
      } else {
        user.value = response;
      }
      if (!user.value) {
        throw new Error("No user data received from server");
      }
    } catch (e) {
      if (e.statusCode !== 401) {
        console.error("Fetch user error:", e);
      }
      if (e.statusCode === 401) {
        token.value = null;
        user.value = null;
      }
      throw e;
    }
  }
  async function refreshToken() {
    if (isRefreshing.value || !token.value) return false;
    isRefreshing.value = true;
    try {
      const response = await $fetch(`${apiBase}/api/refresh`, {
        method: "POST",
        credentials: "include",
        headers: {
          Authorization: `Bearer ${token.value}`
        }
      });
      if (response.success && response.data?.accessToken) {
        token.value = response.data.accessToken;
        if (response.data.user) {
          user.value = response.data.user;
        }
        return true;
      }
      return false;
    } catch (e) {
      console.error("Token refresh error:", e);
      token.value = null;
      user.value = null;
      return false;
    } finally {
      isRefreshing.value = false;
    }
  }
  async function logout() {
    try {
      if (token.value) {
        await $fetch(`${apiBase}/api/logout`, {
          method: "POST",
          credentials: "include",
          headers: {
            Authorization: `Bearer ${token.value}`
          }
        });
      }
    } catch (e) {
      console.error("Logout error:", e);
    } finally {
      token.value = null;
      user.value = null;
      navigateTo("/auth");
    }
  }
  async function handleOAuthCallback(oauthToken) {
    isLoading.value = true;
    try {
      if (!oauthToken || oauthToken.split(".").length !== 3) {
        throw new Error("Invalid token format");
      }
      token.value = oauthToken;
      await fetchUser();
      return true;
    } catch (error) {
      token.value = null;
      user.value = null;
      throw new Error(error.message || "Failed to process authentication");
    } finally {
      isLoading.value = false;
    }
  }
  const points = computed(() => Number(user.value?.points) || 0);
  function deductPoints(amount) {
    if (!user.value) {
      console.error("❌ deductPoints: No user");
      return false;
    }
    const currentPoints = Number(user.value.points) || 0;
    if (currentPoints < amount) {
      console.error("❌ deductPoints: Not enough points", { currentPoints, amount });
      return false;
    }
    user.value.points = currentPoints - amount;
    return true;
  }
  function addPoints(amount) {
    if (!user.value) return;
    const currentPoints = Number(user.value.points) || 0;
    user.value.points = currentPoints + amount;
  }
  function setPoints(amount) {
    if (!user.value) return;
    user.value.points = amount;
  }
  function rollback(amount) {
    addPoints(amount);
  }
  const hasEnoughPoints = computed(() => (amount) => {
    return points.value >= amount;
  });
  const canLike = computed(() => points.value >= 24);
  const canDislike = computed(() => points.value >= 12);
  function deductForLike() {
    return deductPoints(24);
  }
  function deductForDislike() {
    return deductPoints(12);
  }
  function deductForUnlike() {
    return deductPoints(12);
  }
  function deductForUndislike() {
    return deductPoints(12);
  }
  const wallet = computed(() => Number(user.value?.wallet) || 0);
  function setWallet(amount) {
    if (!user.value) return;
    user.value.wallet = amount;
  }
  function addWallet(amount) {
    if (!user.value) return;
    const currentWallet = Number(user.value.wallet) || 0;
    user.value.wallet = currentWallet + amount;
  }
  function deductWallet(amount) {
    if (!user.value) {
      console.error("❌ deductWallet: No user");
      return false;
    }
    const currentWallet = Number(user.value.wallet) || 0;
    if (currentWallet < amount) {
      console.error("❌ deductWallet: Not enough wallet balance", { currentWallet, amount });
      return false;
    }
    user.value.wallet = currentWallet - amount;
    return true;
  }
  const hasEnoughWallet = computed(() => (amount) => {
    return wallet.value >= amount;
  });
  return {
    user,
    token,
    isAuthenticated,
    isRefreshing,
    isLoading,
    login,
    register,
    fetchUser,
    refreshToken,
    logout,
    handleOAuthCallback,
    // Points management
    points,
    deductPoints,
    addPoints,
    setPoints,
    rollback,
    hasEnoughPoints,
    canLike,
    canDislike,
    deductForLike,
    deductForDislike,
    deductForUnlike,
    deductForUndislike,
    // Wallet management
    wallet,
    setWallet,
    addWallet,
    deductWallet,
    hasEnoughWallet
  };
});
const useApi = () => {
  const authStore = useAuthStore();
  const config = /* @__PURE__ */ useRuntimeConfig();
  const call = async (endpoint, options = {}) => {
    const token = authStore.token;
    const apiBase = config.public.apiBase;
    if (!token) {
      throw new Error("No authentication token available");
    }
    const url = endpoint.startsWith("http") ? endpoint : `${apiBase}${endpoint}`;
    try {
      const response = await $fetch(url, {
        ...options,
        headers: {
          ...options.headers,
          Authorization: `Bearer ${token}`,
          Accept: "application/json"
        }
      });
      return response;
    } catch (error) {
      console.error("API Error:", {
        endpoint,
        url,
        status: error.statusCode || error.response?.status,
        data: error.data,
        message: error.message
      });
      if (error.statusCode === 401 || error.response?.status === 401) {
        const refreshed = await authStore.refreshToken();
        if (refreshed) {
          return await $fetch(url, {
            ...options,
            headers: {
              ...options.headers,
              Authorization: `Bearer ${authStore.token}`
            }
          });
        } else {
          authStore.logout();
          throw new Error("Session expired. Please login again.");
        }
      }
      throw error;
    }
  };
  const get = (endpoint, options = {}) => {
    return call(endpoint, { ...options, method: "GET" });
  };
  const post = (endpoint, body, options = {}) => {
    return call(endpoint, { ...options, method: "POST", body });
  };
  const put = (endpoint, body, options = {}) => {
    return call(endpoint, { ...options, method: "PUT", body });
  };
  const patch = (endpoint, body, options = {}) => {
    return call(endpoint, { ...options, method: "PATCH", body });
  };
  const del = (endpoint, options = {}) => {
    return call(endpoint, { ...options, method: "DELETE" });
  };
  return {
    call,
    get,
    post,
    put,
    patch,
    delete: del
  };
};
const useCourseStore = defineStore("course", () => {
  const currentCourse = ref(null);
  const academy = ref(null);
  const isCourseAdmin = ref(false);
  const isLoading = ref(false);
  const error = ref(null);
  const lastFetchTime = ref(null);
  const cacheDuration = 5 * 60 * 1e3;
  const isCacheValid = computed(() => {
    if (!lastFetchTime.value) return false;
    return Date.now() - lastFetchTime.value < cacheDuration;
  });
  const setCourse = (course) => {
    currentCourse.value = course;
  };
  const setAcademy = (academyData) => {
    academy.value = academyData;
  };
  const setIsCourseAdmin = (isAdmin) => {
    isCourseAdmin.value = isAdmin;
  };
  const updateCourse = (updates) => {
    if (currentCourse.value) {
      currentCourse.value = { ...currentCourse.value, ...updates };
    }
  };
  const clearCourse = () => {
    currentCourse.value = null;
    academy.value = null;
    isCourseAdmin.value = false;
    error.value = null;
    lastFetchTime.value = null;
  };
  const fetchCourse = async (courseId, forceRefresh = false) => {
    if (!forceRefresh && isCacheValid.value && currentCourse.value?.id == courseId) {
      return { success: true, course: currentCourse.value, academy: academy.value };
    }
    isLoading.value = true;
    error.value = null;
    try {
      const api = useApi();
      const response = await api.get(`/api/courses/${courseId}/feeds`);
      if (response.success) {
        setCourse(response.course);
        setAcademy(response.academy);
        setIsCourseAdmin(response.isCourseAdmin || false);
        lastFetchTime.value = Date.now();
        return response;
      }
    } catch (err) {
      error.value = err.data?.msg || "ไม่สามารถโหลดข้อมูลรายวิชาได้";
      throw err;
    } finally {
      isLoading.value = false;
    }
  };
  return {
    // State
    currentCourse,
    academy,
    isCourseAdmin,
    isLoading,
    error,
    // Getters
    isCacheValid,
    // Actions
    setCourse,
    setAcademy,
    setIsCourseAdmin,
    updateCourse,
    clearCourse,
    fetchCourse
  };
});
const __nuxt_page_meta$J = {
  middleware: ["auth", async (to) => {
    const courseStore2 = useCourseStore();
    if (!courseStore2.currentCourse || courseStore2.currentCourse.id != to.params.id) {
      try {
        await courseStore2.fetchCourse(to.params.id);
      } catch (e) {
        console.error("Middleware fetch course error", e);
        return abortNavigation("Course not found");
      }
    }
    if (!courseStore2.isCourseAdmin) {
      return navigateTo(`/courses/${to.params.id}`);
    }
  }]
};
const __nuxt_page_meta$I = {
  middleware: ["auth", async (to) => {
    const courseStore = useCourseStore();
    if (!courseStore.currentCourse || courseStore.currentCourse.id != to.params.id) {
      try {
        await courseStore.fetchCourse(to.params.id);
      } catch (e) {
        console.error("Middleware fetch course error", e);
        return abortNavigation("Course not found");
      }
    }
    if (!courseStore.isCourseAdmin) {
      return navigateTo(`/courses/${to.params.id}`);
    }
  }]
};
const __nuxt_page_meta$H = {
  middleware: ["auth", async (to) => {
    const courseStore = useCourseStore();
    if (!courseStore.currentCourse || courseStore.currentCourse.id != to.params.id) {
      try {
        await courseStore.fetchCourse(to.params.id);
      } catch (e) {
        console.error("Middleware fetch course error", e);
        return abortNavigation("Course not found");
      }
    }
    if (!courseStore.isCourseAdmin) {
      return navigateTo(`/courses/${to.params.id}`);
    }
  }]
};
const __nuxt_page_meta$G = {
  layout: false
  // We'll use NuxtLayout manually to pass slots
};
const __nuxt_page_meta$F = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$E = {
  layout: "auth"
};
const __nuxt_page_meta$D = {
  layout: "game-layout"
};
const __nuxt_page_meta$C = {
  layout: false
};
const __nuxt_page_meta$B = {
  layout: "admin"
};
const __nuxt_page_meta$A = {
  layout: "main"
};
const __nuxt_page_meta$z = {
  layout: "main"
};
const __nuxt_page_meta$y = {
  layout: "main"
};
const __nuxt_page_meta$x = {
  layout: false
};
const __nuxt_page_meta$w = {
  layout: "main"
};
const __nuxt_page_meta$v = {
  layout: false
};
const __nuxt_page_meta$u = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$t = {
  layout: false
};
const __nuxt_page_meta$s = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$r = {
  layout: "game-layout"
};
const __nuxt_page_meta$q = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$p = {
  layout: "game-layout"
};
const __nuxt_page_meta$o = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$n = {
  layout: false
};
const __nuxt_page_meta$m = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$l = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$k = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$j = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$i = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$h = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$g = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$f = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$e = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$d = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$c = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$b = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$a = {
  layout: false
};
const __nuxt_page_meta$9 = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$8 = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$7 = {
  layout: "main"
};
const __nuxt_page_meta$6 = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$5 = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$4 = {
  layout: "nuxnan-admin-layout"
};
const __nuxt_page_meta$3 = {
  layout: "game-layout"
};
const __nuxt_page_meta$2 = {
  layout: "main"
};
const __nuxt_page_meta$1 = {
  layout: "main"
};
const __nuxt_page_meta = {
  layout: false
};
const _routes = [
  {
    name: "Post",
    path: "/Post",
    component: () => import('./Post-638D2yBM.mjs')
  },
  {
    name: "Test",
    path: "/Test",
    component: () => import('./Test-BjfCqOED.mjs')
  },
  {
    name: "about",
    path: "/about",
    meta: __nuxt_page_meta$1u || {},
    component: () => import('./about-DU-c7DTr.mjs')
  },
  {
    name: "forum",
    path: "/forum",
    meta: __nuxt_page_meta$1t || {},
    component: () => import('./forum-MaZPs0bY.mjs')
  },
  {
    name: "index",
    path: "/",
    meta: __nuxt_page_meta$1s || {},
    component: () => import('./index-DppIAB-k.mjs')
  },
  {
    name: "tools",
    path: "/tools",
    meta: __nuxt_page_meta$1r || {},
    component: () => import('./tools-_4A_Sm_6.mjs')
  },
  {
    name: "badges",
    path: "/badges",
    meta: __nuxt_page_meta$1q || {},
    component: () => import('./badges-CS-k2Aho.mjs')
  },
  {
    name: "events",
    path: "/events",
    meta: __nuxt_page_meta$1p || {},
    component: () => import('./events-JBF5qTPT.mjs')
  },
  {
    name: "photos",
    path: "/photos",
    meta: __nuxt_page_meta$1o || {},
    component: () => import('./photos-D7QqP1ML.mjs')
  },
  {
    name: "quests",
    path: "/quests",
    meta: __nuxt_page_meta$1n || {},
    component: () => import('./quests-CmC6wiMe.mjs')
  },
  {
    name: "videos",
    path: "/videos",
    meta: __nuxt_page_meta$1m || {},
    component: () => import('./videos-CU_hxCMa.mjs')
  },
  {
    name: "Academy",
    path: "/Academy",
    component: () => import('./Academy-2araEXkJ.mjs')
  },
  {
    name: "account",
    path: "/account",
    meta: __nuxt_page_meta$1l || {},
    component: () => import('./account-BUJmGjlH.mjs')
  },
  {
    name: "members",
    path: "/members",
    meta: __nuxt_page_meta$1k || {},
    component: () => import('./members-DfOuMJVP.mjs')
  },
  {
    name: "welcome",
    path: "/welcome",
    meta: __nuxt_page_meta$1j || {},
    component: () => import('./welcome-47xV7A8D.mjs')
  },
  {
    name: "EditPost",
    path: "/EditPost",
    component: () => import('./EditPost-DRzmpdK0.mjs')
  },
  {
    name: "featured",
    path: "/featured",
    meta: __nuxt_page_meta$1i || {},
    component: () => import('./featured-Cor_b9tK.mjs')
  },
  {
    name: "newsfeed",
    path: "/newsfeed",
    meta: __nuxt_page_meta$1h || {},
    component: () => import('./newsfeed-BnE4c_Yu.mjs')
  },
  {
    name: "overview",
    path: "/overview",
    meta: __nuxt_page_meta$1g || {},
    component: () => import('./overview-Q5jtTssh.mjs')
  },
  {
    name: "settings",
    path: "/settings",
    meta: { ...__nuxt_page_meta$1f || {}, ...{ "middleware": "auth" } },
    component: () => import('./settings-Dj4wCJbH.mjs')
  },
  {
    name: "timeline",
    path: "/timeline",
    component: () => import('./timeline-CBqWu2T6.mjs')
  },
  {
    name: "API-Index",
    path: "/API/Index",
    meta: { ...__nuxt_page_meta$1e || {}, ...{ "middleware": "auth" } },
    component: () => import('./Index-KzscMTYl.mjs')
  },
  {
    name: "Dashboard",
    path: "/Dashboard",
    meta: { ...__nuxt_page_meta$1d || {}, ...{ "middleware": "auth" } },
    component: () => import('./Dashboard-BaTvnpqb.mjs')
  },
  {
    name: "Earn-Jobs",
    path: "/Earn/Jobs",
    meta: __nuxt_page_meta$1c || {},
    component: () => import('./Jobs-CDkaP71q.mjs')
  },
  {
    name: "favourite",
    path: "/favourite",
    meta: { ...__nuxt_page_meta$1b || {}, ...{ "middleware": "auth" } },
    component: () => import('./favourite-Dtuzgm2J.mjs')
  },
  {
    name: "Play-Tube",
    path: "/Play/Tube",
    meta: __nuxt_page_meta$1a || {},
    component: () => import('./Tube-W2DTHxTc.mjs')
  },
  {
    name: "auth",
    path: "/auth",
    meta: { ...__nuxt_page_meta$19 || {}, ...{ "middleware": "guest" } },
    component: () => import('./index-rWAAgq8z.mjs')
  },
  {
    name: "MentalMath",
    path: "/MentalMath",
    component: () => import('./MentalMath-CH0kP0lH.mjs')
  },
  {
    name: "Teams-Show",
    path: "/Teams/Show",
    meta: { ...__nuxt_page_meta$18 || {}, ...{ "middleware": "auth" } },
    component: () => import('./Show-DZJyYulh.mjs')
  },
  {
    name: "Earn-Points",
    path: "/Earn/Points",
    meta: { ...__nuxt_page_meta$17 || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./Points-BzEOF14-.mjs')
  },
  {
    name: "Earn-Wallet",
    path: "/Earn/Wallet",
    meta: { ...__nuxt_page_meta$16 || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./Wallet-DNEGa8Rq.mjs')
  },
  {
    name: "Play-Groups",
    path: "/Play/Groups",
    component: () => import('./Groups-BprYz3We.mjs')
  },
  {
    name: "Admin-Points",
    path: "/Admin/Points",
    meta: { ...__nuxt_page_meta$15 || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import('./Points-BZJGdLkL.mjs')
  },
  {
    name: "Admin-Wallet",
    path: "/Admin/Wallet",
    meta: { ...__nuxt_page_meta$14 || {}, ...{ "middleware": ["auth", "admin"] } },
    component: () => import('./Wallet-ha6Sj0vx.mjs')
  },
  {
    name: "Earn-Rewards",
    path: "/Earn/Rewards",
    meta: { ...__nuxt_page_meta$13 || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./Rewards-CdC7CfCK.mjs')
  },
  {
    name: "Play-Friends",
    path: "/Play/Friends",
    meta: __nuxt_page_meta$12 || {},
    component: () => import('./Friends-DzVnPr8u.mjs')
  },
  {
    name: "Play-Streams",
    path: "/Play/Streams",
    meta: __nuxt_page_meta$11 || {},
    component: () => import('./Streams-B7crLKBU.mjs')
  },
  {
    name: "profile-id",
    path: "/profile/:id()",
    meta: { ...__nuxt_page_meta$10 || {}, ...{ "middleware": "auth" } },
    component: () => import('./_id_-DUUhed5w.mjs')
  },
  {
    name: "profile-edit",
    path: "/profile/edit",
    meta: { ...__nuxt_page_meta$$ || {}, ...{ "middleware": "auth" } },
    component: () => import('./edit-D2-aqdUS.mjs')
  },
  {
    name: "profile-Show",
    path: "/profile/Show",
    meta: { ...__nuxt_page_meta$_ || {}, ...{ "middleware": "auth" } },
    component: () => import('./Show-vGRoAa2W.mjs')
  },
  {
    name: "Teams-Create",
    path: "/Teams/Create",
    meta: { ...__nuxt_page_meta$Z || {}, ...{ "middleware": "auth" } },
    component: () => import('./Create-BK8rVw_z.mjs')
  },
  {
    name: "auth-callback",
    path: "/auth/callback",
    meta: { ...__nuxt_page_meta$Y || {}, ...{ "middleware": [] } },
    component: () => import('./callback-OpSIcDEp.mjs')
  },
  {
    name: "coupons",
    path: "/coupons",
    component: () => import('./index-736tZP8W.mjs')
  },
  {
    name: "notifications",
    path: "/notifications",
    component: () => import('./notifications-CP50jt-G.mjs')
  },
  {
    name: "Play-Messages",
    path: "/Play/Messages",
    meta: __nuxt_page_meta$X || {},
    component: () => import('./Messages-BG3Q6TUh.mjs')
  },
  {
    name: "Play-Newsfeed",
    path: "/Play/Newsfeed",
    meta: { ...__nuxt_page_meta$W || {}, ...{ "middleware": "auth" } },
    component: () => import('./Newsfeed-BgpCwk4i.mjs')
  },
  {
    name: "PrivacyPolicy",
    path: "/PrivacyPolicy",
    component: () => import('./PrivacyPolicy-BL6iPH7K.mjs')
  },
  {
    name: "profile",
    path: "/profile",
    meta: { ...__nuxt_page_meta$V || {}, ...{ "middleware": "auth" } },
    component: () => import('./index-BeGktFYU.mjs')
  },
  {
    name: "CompanyLanding",
    path: "/CompanyLanding",
    component: () => import('./CompanyLanding-qhEpAIok.mjs')
  },
  {
    name: "coupons-redeem",
    path: "/coupons/redeem",
    component: () => import('./redeem-BCEnVkft.mjs')
  },
  {
    name: "TermsOfService",
    path: "/TermsOfService",
    component: () => import('./TermsOfService-BUMnizq4.mjs')
  },
  {
    name: "academies",
    path: "/academies",
    meta: { ...__nuxt_page_meta$U || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./index-3C5Qrglj.mjs')
  },
  {
    name: "button-showcase",
    path: "/button-showcase",
    meta: __nuxt_page_meta$T || {},
    component: () => import('./button-showcase-DIUwa1gv.mjs')
  },
  {
    name: "Learn-Dashboard",
    path: "/Learn/Dashboard",
    meta: { ...__nuxt_page_meta$S || {}, ...{ "middleware": "auth" } },
    component: () => import('./Dashboard-_HldpMCN.mjs')
  },
  {
    name: "academies-name",
    path: "/academies/:name()",
    meta: { ...__nuxt_page_meta$R || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./_name_-BViH_U7U.mjs')
  },
  {
    name: "auth-VerifyEmail",
    path: "/auth/VerifyEmail",
    component: () => import('./VerifyEmail-CrzPpxOz.mjs')
  },
  {
    name: "Earn-Marketplace",
    path: "/Earn/Marketplace",
    component: () => import('./Marketplace-9NxVsSVh.mjs')
  },
  {
    name: "home-visit",
    path: "/home-visit",
    component: () => import('./index-CU6Skp6a.mjs')
  },
  {
    name: "Play-Games",
    path: "/Play/Games",
    meta: __nuxt_page_meta$Q || {},
    component: () => import('./index-DBb4r1S_.mjs')
  },
  {
    name: "courses-slug",
    path: "/courses/:slug(.*)*",
    meta: __nuxt_page_meta$P || {},
    component: () => import('./_...slug_-DjXFGcNF.mjs')
  },
  {
    name: "Earn-Gamification",
    path: "/Earn/Gamification",
    meta: { ...__nuxt_page_meta$O || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./Gamification-D_ZhB0WB.mjs')
  },
  {
    name: "auth-ResetPassword",
    path: "/auth/ResetPassword",
    component: () => import('./ResetPassword-BuP6Ur8c.mjs')
  },
  {
    name: "Earn-coupons",
    path: "/Earn/coupons",
    meta: { ...__nuxt_page_meta$N || {}, ...{ "middleware": "auth" } },
    component: () => import('./index-DXE_Qgf1.mjs')
  },
  {
    name: "Earn-donates",
    path: "/Earn/donates",
    meta: __nuxt_page_meta$M || {},
    component: () => import('./index-Bk8FxRGL.mjs')
  },
  {
    name: __nuxt_page_meta$G?.name,
    path: "/Learn/Courses/:id()",
    meta: { ...__nuxt_page_meta$G || {}, ...{ "middleware": "auth" } },
    component: () => import('./_id_-D3T5EJWF.mjs'),
    children: [
      {
        name: "Learn-Courses-id-admin",
        path: "admin",
        component: () => import('./admin-CCGNkT-3.mjs')
      },
      {
        name: "Learn-Courses-id-feeds",
        path: "feeds",
        meta: __nuxt_page_meta$L || {},
        component: () => import('./feeds-CZYvZ45G.mjs')
      },
      {
        name: "Learn-Courses-id",
        path: "",
        component: () => import('./index-BimSm09e.mjs')
      },
      {
        name: "Learn-Courses-id-lessons",
        path: "lessons",
        component: () => import('./lessons-D_HchPEf.mjs'),
        children: [
          {
            name: __nuxt_page_meta$K?.name,
            path: "create",
            component: () => import('./create-Du7vY2UV.mjs'),
            children: [
              {
                name: "Learn-Courses-id-lessons-create",
                path: "",
                component: () => import('./index-CcDLIloM.mjs')
              }
            ]
          },
          {
            name: "Learn-Courses-id-lessons-lessonId",
            path: ":lessonId()",
            component: () => import('./_lessonId_-tjxTaLfB.mjs'),
            children: [
              {
                name: "Learn-Courses-id-lessons-lessonId-edit",
                path: "edit",
                meta: __nuxt_page_meta$J || {},
                component: () => import('./edit-Di96umzZ.mjs')
              }
            ]
          }
        ]
      },
      {
        name: "Learn-Courses-id-progress",
        path: "progress",
        component: () => import('./progress-DzdM6h1G.mjs')
      },
      {
        name: "Learn-Courses-id-settings",
        path: "settings",
        component: () => import('./settings-DWATzg8d.mjs')
      },
      {
        name: "Learn-Courses-id-attendances",
        path: "attendances",
        component: () => import('./attendances-orEnfGrT.mjs')
      },
      {
        name: "Learn-Courses-id-my-progress",
        path: "my-progress",
        component: () => import('./my-progress-B1z5qxgS.mjs')
      },
      {
        name: "Learn-Courses-id-groups",
        path: "groups",
        component: () => import('./index-ToNFJsGK.mjs')
      },
      {
        name: "Learn-Courses-id-members",
        path: "members",
        component: () => import('./index-Dk-Vc5fg.mjs')
      },
      {
        name: "Learn-Courses-id-quizzes",
        path: "quizzes",
        component: () => import('./index-BqLO1WVV.mjs')
      },
      {
        name: "Learn-Courses-id-quizzes-create",
        path: "quizzes/create",
        meta: __nuxt_page_meta$I || {},
        component: () => import('./create-B3RtKq2E.mjs')
      },
      {
        name: "Learn-Courses-id-groups-groupId",
        path: "groups/:groupId()",
        component: () => import('./_groupId_-DppC6PlZ.mjs'),
        children: [
          {
            name: "Learn-Courses-id-groups-groupId-edit",
            path: "edit",
            component: () => import('./edit-DgRTBGLi.mjs')
          }
        ]
      },
      {
        name: "Learn-Courses-id-assignments",
        path: "assignments",
        component: () => import('./index-BtxvEWuu.mjs')
      },
      {
        name: "Learn-Courses-id-quizzes-quizId-edit",
        path: "quizzes/:quizId()/edit",
        meta: __nuxt_page_meta$H || {},
        component: () => import('./edit-DsuRWs51.mjs')
      },
      {
        name: "Learn-Courses-id-quizzes-quizId",
        path: "quizzes/:quizId()",
        component: () => import('./index-C0Gwy5dk.mjs')
      },
      {
        name: "Learn-Courses-id-quizzes-quizId-attempt",
        path: "quizzes/:quizId()/attempt",
        component: () => import('./attempt-CxzjJ5oW.mjs')
      },
      {
        name: "Learn-Courses-id-assignments-assignmentId",
        path: "assignments/:assignmentId()",
        component: () => import('./_assignmentId_-u7K6WklX.mjs')
      }
    ]
  },
  {
    name: "nuxnan-admin",
    path: "/nuxnan-admin",
    meta: { ...__nuxt_page_meta$F || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-CKB9WHKe.mjs')
  },
  {
    name: "nuxnan-admin-login",
    path: "/nuxnan-admin/login",
    meta: { ...__nuxt_page_meta$E || {}, ...{ "middleware": "admin-guest" } },
    component: () => import('./login-Cb5l9CUL.mjs')
  },
  {
    name: "Play-Games-xo-game",
    path: "/Play/Games/xo-game",
    meta: __nuxt_page_meta$D || {},
    component: () => import('./xo-game-Cfxg8YcA.mjs')
  },
  {
    name: "student-card",
    path: "/student-card",
    meta: __nuxt_page_meta$C || {},
    component: () => import('./index-D4qQR0rz.mjs')
  },
  {
    name: "Admin-Resetpassword",
    path: "/Admin/Resetpassword",
    meta: { ...__nuxt_page_meta$B || {}, ...{ "middleware": ["auth", "plearnd-admin"] } },
    component: () => import('./Resetpassword-Bu_fnpHN.mjs')
  },
  {
    name: "auth-ForgotPassword",
    path: "/auth/ForgotPassword",
    component: () => import('./ForgotPassword-YlAGPNDg.mjs')
  },
  {
    name: "Earn-coupons-create",
    path: "/Earn/coupons/create",
    meta: { ...__nuxt_page_meta$A || {}, ...{ "middleware": "auth" } },
    component: () => import('./create-BKkqDuxP.mjs')
  },
  {
    name: "Earn-coupons-redeem",
    path: "/Earn/coupons/redeem",
    meta: { ...__nuxt_page_meta$z || {}, ...{ "middleware": "auth" } },
    component: () => import('./redeem-sCKIEkmK.mjs')
  },
  {
    name: "Earn-donates-create",
    path: "/Earn/donates/create",
    meta: __nuxt_page_meta$y || {},
    component: () => import('./create-BWhLDrN_.mjs')
  },
  {
    name: "Learn-Course-Course",
    path: "/Learn/Course/Course",
    component: () => import('./Course-D2rqPqDC.mjs')
  },
  {
    name: "Learn-Courses",
    path: "/Learn/Courses",
    meta: { ...__nuxt_page_meta$x || {}, ...{ "middleware": "auth" } },
    component: () => import('./index-Dx1eTDyz.mjs')
  },
  {
    name: "Learn-Lesson-Lesson",
    path: "/Learn/Lesson/Lesson",
    component: () => import('./Lesson-C-_4_zQG.mjs')
  },
  {
    name: "auth-ConfirmPassword",
    path: "/auth/ConfirmPassword",
    component: () => import('./ConfirmPassword-CROY_Zlj.mjs')
  },
  {
    name: "Earn-Advertise",
    path: "/Earn/Advertise",
    meta: __nuxt_page_meta$w || {},
    component: () => import('./index-CIorCX2b.mjs')
  },
  {
    name: "Learn-Course-Courses",
    path: "/Learn/Course/Courses",
    component: () => import('./Courses-BY5jBtlw.mjs')
  },
  {
    name: "Learn-Courses-create",
    path: "/Learn/Courses/create",
    meta: { ...__nuxt_page_meta$v || {}, ...{ "middleware": "auth" } },
    component: () => import('./create-BEBn0li6.mjs')
  },
  {
    name: "nuxnan-admin-reports",
    path: "/nuxnan-admin/reports",
    meta: { ...__nuxt_page_meta$u || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./reports-CYoj4DA4.mjs')
  },
  {
    name: "Earn-Advertise-create",
    path: "/Earn/Advertise/create",
    meta: __nuxt_page_meta$t || {},
    component: () => import('./create-DdltlZjk.mjs')
  },
  {
    name: "nuxnan-admin-settings",
    path: "/nuxnan-admin/settings",
    meta: { ...__nuxt_page_meta$s || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./settings-BZIlai53.mjs')
  },
  {
    name: "Play-Games-snake-game",
    path: "/Play/Games/snake-game",
    meta: __nuxt_page_meta$r || {},
    component: () => import('./snake-game-n-4rWbIY.mjs')
  },
  {
    name: "Play-Games-MentalMatch",
    path: "/Play/Games/MentalMatch",
    component: () => import('./MentalMatch-G3BS2sQp.mjs')
  },
  {
    name: "auth-TwoFactorChallenge",
    path: "/auth/TwoFactorChallenge",
    component: () => import('./TwoFactorChallenge-Vwx7M1oM.mjs')
  },
  {
    name: "Learn-Academy-Academies",
    path: "/Learn/Academy/Academies",
    component: () => import('./Academies-DMrNCJQS.mjs')
  },
  {
    name: "nuxnan-admin-activities",
    path: "/nuxnan-admin/activities",
    meta: { ...__nuxt_page_meta$q || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./activities-BCXm7-DH.mjs')
  },
  {
    name: "Play-Games-mental-match",
    path: "/Play/Games/mental-match",
    meta: __nuxt_page_meta$p || {},
    component: () => import('./mental-match-CNBaZlkl.mjs')
  },
  {
    name: "Learn-Course-UserCourses",
    path: "/Learn/Course/UserCourses",
    component: () => import('./UserCourses-C2Imb9LF.mjs')
  },
  {
    name: "nuxnan-admin-users",
    path: "/nuxnan-admin/users",
    meta: { ...__nuxt_page_meta$o || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-k8XYsCQV.mjs')
  },
  {
    name: "student-card-admin",
    path: "/student-card/admin",
    meta: __nuxt_page_meta$n || {},
    component: () => import('./index-Bjz0G4gS.mjs')
  },
  {
    name: "Learn-Academy-GroupDetail",
    path: "/Learn/Academy/GroupDetail",
    component: () => import('./GroupDetail-BnpkLdqF.mjs')
  },
  {
    name: "Learn-Academy-ShowAcademy",
    path: "/Learn/Academy/ShowAcademy",
    component: () => import('./ShowAcademy-Dbz2Lmr3.mjs')
  },
  {
    name: "nuxnan-admin-points",
    path: "/nuxnan-admin/points",
    meta: { ...__nuxt_page_meta$m || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-CJTIglLP.mjs')
  },
  {
    name: "nuxnan-admin-transactions",
    path: "/nuxnan-admin/transactions",
    meta: { ...__nuxt_page_meta$l || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./transactions-Dz3geZcJ.mjs')
  },
  {
    name: "nuxnan-admin-users-create",
    path: "/nuxnan-admin/users/create",
    meta: { ...__nuxt_page_meta$k || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./create-S2SBBDGU.mjs')
  },
  {
    name: "nuxnan-admin-wallet",
    path: "/nuxnan-admin/wallet",
    meta: { ...__nuxt_page_meta$j || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index--Mo9XrCZ.mjs')
  },
  {
    name: "Learn-Course-Member-Member",
    path: "/Learn/Course/Member/Member",
    component: () => import('./Member-aKNJuPpO.mjs')
  },
  {
    name: "nuxnan-admin-content",
    path: "/nuxnan-admin/content",
    meta: { ...__nuxt_page_meta$i || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-CVYWXuRp.mjs')
  },
  {
    name: "nuxnan-admin-coupons",
    path: "/nuxnan-admin/coupons",
    meta: { ...__nuxt_page_meta$h || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-BbQ-_0k-.mjs')
  },
  {
    name: "nuxnan-admin-courses",
    path: "/nuxnan-admin/courses",
    meta: { ...__nuxt_page_meta$g || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-DWIeWT_X.mjs')
  },
  {
    name: "Learn-Academy-AcademyGroups",
    path: "/Learn/Academy/AcademyGroups",
    component: () => import('./AcademyGroups-D9yGJNUe.mjs')
  },
  {
    name: "Learn-Course-Member-Members",
    path: "/Learn/Course/Member/Members",
    component: () => import('./Members-CG-NawOF.mjs')
  },
  {
    name: "nuxnan-admin-content-create",
    path: "/nuxnan-admin/content/create",
    meta: { ...__nuxt_page_meta$f || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./create-DUQvmJbj.mjs')
  },
  {
    name: "nuxnan-admin-coupons-create",
    path: "/nuxnan-admin/coupons/create",
    meta: { ...__nuxt_page_meta$e || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./create-BmiiXI32.mjs')
  },
  {
    name: "nuxnan-admin-courses-create",
    path: "/nuxnan-admin/courses/create",
    meta: { ...__nuxt_page_meta$d || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./create-BJsW4Nuu.mjs')
  },
  {
    name: "nuxnan-admin-supports",
    path: "/nuxnan-admin/supports",
    meta: { ...__nuxt_page_meta$c || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-DRPz8wvm.mjs')
  },
  {
    name: "nuxnan-admin-wallet-pending",
    path: "/nuxnan-admin/wallet/pending",
    meta: { ...__nuxt_page_meta$b || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./pending-BTZDC337.mjs')
  },
  {
    name: "student-card-level-room",
    path: "/student-card/:level()/:room()",
    meta: __nuxt_page_meta$a || {},
    component: () => import('./_room_-C-rTm_Iy.mjs')
  },
  {
    name: "API-Partials-ApiTokenManager",
    path: "/API/Partials/ApiTokenManager",
    component: () => import('./ApiTokenManager-CQzweVn9.mjs')
  },
  {
    name: "Learn-Academy-AcademyCourses",
    path: "/Learn/Academy/AcademyCourses",
    component: () => import('./AcademyCourses-Cm46b-yR.mjs')
  },
  {
    name: "Learn-Academy-AcademyMembers",
    path: "/Learn/Academy/AcademyMembers",
    component: () => import('./AcademyMembers-BhnG6f4n.mjs')
  },
  {
    name: "Learn-Course-Basic-BasicInfo",
    path: "/Learn/Course/Basic/BasicInfo",
    component: () => import('./BasicInfo-rp2X8r93.mjs')
  },
  {
    name: "Learn-Course-CreateNewCourse",
    path: "/Learn/Course/CreateNewCourse",
    component: () => import('./CreateNewCourse-DrWmcRAU.mjs')
  },
  {
    name: "nuxnan-admin-academies",
    path: "/nuxnan-admin/academies",
    meta: { ...__nuxt_page_meta$9 || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-NBiz1gbp.mjs')
  },
  {
    name: "nuxnan-admin-users-id-edit",
    path: "/nuxnan-admin/users/:id()/edit",
    meta: { ...__nuxt_page_meta$8 || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./edit-B5_ybkIU.mjs')
  },
  {
    name: "PlearndAdmin-DepositApproval",
    path: "/PlearndAdmin/DepositApproval",
    meta: { ...__nuxt_page_meta$7 || {}, ...{ "middleware": ["auth"] } },
    component: () => import('./DepositApproval-WrtHv4uR.mjs')
  },
  {
    name: "Learn-Course-Setting-Settings",
    path: "/Learn/Course/Setting/Settings",
    component: () => import('./Settings-oNKdmMIk.mjs')
  },
  {
    name: "nuxnan-admin-academies-create",
    path: "/nuxnan-admin/academies/create",
    meta: { ...__nuxt_page_meta$6 || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./create-CdD1ie44.mjs')
  },
  {
    name: "nuxnan-admin-users-id",
    path: "/nuxnan-admin/users/:id()",
    meta: { ...__nuxt_page_meta$5 || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./index-D0Q5J42T.mjs')
  },
  {
    name: "Teams-Partials-CreateTeamForm",
    path: "/Teams/Partials/CreateTeamForm",
    component: () => import('./CreateTeamForm-1pSNhWAo.mjs')
  },
  {
    name: "Teams-Partials-DeleteTeamForm",
    path: "/Teams/Partials/DeleteTeamForm",
    component: () => import('./DeleteTeamForm-l3qBXa8y.mjs')
  },
  {
    name: "Learn-Academy-CreateNewAcademy",
    path: "/Learn/Academy/CreateNewAcademy",
    component: () => import('./CreateNewAcademy-CacKe2BR.mjs')
  },
  {
    name: "Learn-Course-AuthMemberCourses",
    path: "/Learn/Course/AuthMemberCourses",
    component: () => import('./AuthMemberCourses-C2vboJt_.mjs')
  },
  {
    name: "Learn-Student-Card-StudentCard",
    path: "/Learn/Student/Card/StudentCard",
    component: () => import('./StudentCard-DqUbauQQ.mjs')
  },
  {
    name: "nuxnan-admin-coupons-id-edit",
    path: "/nuxnan-admin/coupons/:id()/edit",
    meta: { ...__nuxt_page_meta$4 || {}, ...{ "middleware": "nuxnan-admin" } },
    component: () => import('./edit-BZ0PVRVI.mjs')
  },
  {
    name: "Play-Games-guessing-number-game",
    path: "/Play/Games/guessing-number-game",
    meta: __nuxt_page_meta$3 || {},
    component: () => import('./guessing-number-game-DQ_xc_zs.mjs')
  },
  {
    name: "profile-Partials-DeleteUserForm",
    path: "/profile/Partials/DeleteUserForm",
    component: () => import('./DeleteUserForm-CBAguVl3.mjs')
  },
  {
    name: "Teams-Partials-TeamMemberManager",
    path: "/Teams/Partials/TeamMemberManager",
    component: () => import('./TeamMemberManager-DK8ybVSp.mjs')
  },
  {
    name: "profile-reference_code-settings",
    path: "/profile/:reference_code()/settings",
    meta: { ...__nuxt_page_meta$2 || {}, ...{ "middleware": "auth" } },
    component: () => import('./settings-T9WF0yjH.mjs')
  },
  {
    name: "Teams-Partials-UpdateTeamNameForm",
    path: "/Teams/Partials/UpdateTeamNameForm",
    component: () => import('./UpdateTeamNameForm-DdwXuD-x.mjs')
  },
  {
    name: "Learn-Course-Member-MemberSettings",
    path: "/Learn/Course/Member/MemberSettings",
    component: () => import('./MemberSettings-CTqHADwn.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Auth-Login",
    path: "/Learn/Student/HomeVisit/Auth/Login",
    component: () => import('./Login-CMm0AA1b.mjs')
  },
  {
    name: "PlearndAdmin-Donation-DonationList",
    path: "/PlearndAdmin/Donation/DonationList",
    meta: { "middleware": ["plearnd-admin"] },
    component: () => import('./DonationList-Amqc_hRJ.mjs')
  },
  {
    name: "Learn-Course-CourseMemberRequesters",
    path: "/Learn/Course/CourseMemberRequesters",
    component: () => import('./CourseMemberRequesters-cO9aKHW5.mjs')
  },
  {
    name: "Learn-Student-Card-StudentCardIndex",
    path: "/Learn/Student/Card/StudentCardIndex",
    component: () => import('./StudentCardIndex-C6xHhwyg.mjs')
  },
  {
    name: "profile-Partials-UpdatePasswordForm",
    path: "/profile/Partials/UpdatePasswordForm",
    component: () => import('./UpdatePasswordForm-3TWXj1Hd.mjs')
  },
  {
    name: "Learn-Academy-CreateNewAcademyCourse",
    path: "/Learn/Academy/CreateNewAcademyCourse",
    component: () => import('./CreateNewAcademyCourse-D_XeOaon.mjs')
  },
  {
    name: "Learn-Student-Card-Admin-StudentCard",
    path: "/Learn/Student/Card/Admin/StudentCard",
    component: () => import('./StudentCard-B_uiHXki.mjs')
  },
  {
    name: "Learn-Student-Card-StudentCardByRoom",
    path: "/Learn/Student/Card/StudentCardByRoom",
    component: () => import('./StudentCardByRoom-DLGkRfv8.mjs')
  },
  {
    name: "PlearndAdmin-Donation-ApproveDonation",
    path: "/PlearndAdmin/Donation/ApproveDonation",
    meta: { ...__nuxt_page_meta$1 || {}, ...{ "middleware": ["plearnd-admin"] } },
    component: () => import('./ApproveDonation-w4HCy-n_.mjs')
  },
  {
    name: "PlearndAdmin-Support-ApproveAdvertise",
    path: "/PlearndAdmin/Support/ApproveAdvertise",
    meta: { "middleware": ["plearnd-admin"] },
    component: () => import('./ApproveAdvertise-CPf42QRY.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Dashboard",
    path: "/Learn/Student/HomeVisit/Admin/Dashboard",
    component: () => import('./Dashboard-DU-P14bh.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components",
    path: "/Learn/Student/HomeVisit/Components",
    component: () => import('./index-UbG9Looq.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Student-Profile",
    path: "/Learn/Student/HomeVisit/Student/Profile",
    component: () => import('./Profile-CnU9MBtO.mjs')
  },
  {
    name: "Learn-Student-Card-Admin-StudentCardIndex",
    path: "/Learn/Student/Card/Admin/StudentCardIndex",
    component: () => import('./StudentCardIndex-qbL2lU2b.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Teacher-Dashboard",
    path: "/Learn/Student/HomeVisit/Teacher/Dashboard",
    component: () => import('./Dashboard-CnB-D6ip.mjs')
  },
  {
    name: "Learn-Student-Card-Admin-StudentCardByRoom",
    path: "/Learn/Student/Card/Admin/StudentCardByRoom",
    component: () => import('./StudentCardByRoom-DeujdpbK.mjs')
  },
  {
    name: "student-card-admin-students-level-room",
    path: "/student-card/admin/students/:level()/:room()",
    meta: __nuxt_page_meta || {},
    component: () => import('./_room_-CVXJhjQE.mjs')
  },
  {
    name: "Learn-Course-Progress-CourseMembersProgress",
    path: "/Learn/Course/Progress/CourseMembersProgress",
    component: () => import('./CourseMembersProgress-Dl4TmeCx.mjs')
  },
  {
    name: "Learn-Student-Card-Admin-StudentCardBackSide",
    path: "/Learn/Student/Card/Admin/StudentCardBackSide",
    component: () => import('./StudentCardBackSide-CoUGrvoP.mjs')
  },
  {
    name: "profile-Partials-TwoFactorAuthenticationForm",
    path: "/profile/Partials/TwoFactorAuthenticationForm",
    component: () => import('./TwoFactorAuthenticationForm-DiHg0cJ4.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Teacher-ManageStudent",
    path: "/Learn/Student/HomeVisit/Teacher/ManageStudent",
    component: () => import('./ManageStudent-DUXFFCzs.mjs')
  },
  {
    name: "profile-Partials-UpdateProfileInformationForm",
    path: "/profile/Partials/UpdateProfileInformationForm",
    component: () => import('./UpdateProfileInformationForm-FiE2A5zw.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-AddressCard",
    path: "/Learn/Student/HomeVisit/Components/AddressCard",
    component: () => import('./AddressCard-C5OO_9mi.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-ContactCard",
    path: "/Learn/Student/HomeVisit/Components/ContactCard",
    component: () => import('./ContactCard-DNJ688tb.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-GuardianCard",
    path: "/Learn/Student/HomeVisit/Components/GuardianCard",
    component: () => import('./GuardianCard-C6LWpJ3F.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-StudentsCard",
    path: "/Learn/Student/HomeVisit/Components/StudentsCard",
    component: () => import('./StudentsCard-CUu90lC-.mjs')
  },
  {
    name: "profile-Partials-LogoutOtherBrowserSessionsForm",
    path: "/profile/Partials/LogoutOtherBrowserSessionsForm",
    component: () => import('./LogoutOtherBrowserSessionsForm-CjIurKU7.mjs')
  },
  {
    name: "Learn-Course-Progress-MemberGradeProgressDetails",
    path: "/Learn/Course/Progress/MemberGradeProgressDetails",
    component: () => import('./MemberGradeProgressDetails-D2H26JeN.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-DocumentsCard",
    path: "/Learn/Student/HomeVisit/Components/DocumentsCard",
    component: () => import('./DocumentsCard-CBzY7Fw7.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-HealthInfoCard",
    path: "/Learn/Student/HomeVisit/Components/HealthInfoCard",
    component: () => import('./HealthInfoCard-CRvifp0i.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-VisitFeed",
    path: "/Learn/Student/HomeVisit/Admin/Components/VisitFeed",
    component: () => import('./VisitFeed-bET7Z74y.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-MockData-visitFeedData",
    path: "/Learn/Student/HomeVisit/Admin/MockData/visitFeedData",
    component: () => import('./visitFeedData-uFwSmikd.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Components-AcademicInfoCard",
    path: "/Learn/Student/HomeVisit/Components/AcademicInfoCard",
    component: () => import('./AcademicInfoCard-DeAtPXoT.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Composables-useStudentRoutes",
    path: "/Learn/Student/HomeVisit/Composables/useStudentRoutes",
    component: () => import('./useStudentRoutes-BWKsyL_y.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-VisitReports",
    path: "/Learn/Student/HomeVisit/Admin/Components/VisitReports",
    component: () => import('./VisitReports-LlR8QE4k.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-AdminSettings",
    path: "/Learn/Student/HomeVisit/Admin/Components/AdminSettings",
    component: () => import('./AdminSettings-BsDKqRgC.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Teacher-Components-StudentCard",
    path: "/Learn/Student/HomeVisit/Teacher/Components/StudentCard",
    component: () => import('./StudentCard-BOGCzgEZ.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-DashboardStats",
    path: "/Learn/Student/HomeVisit/Admin/Components/DashboardStats",
    component: () => import('./DashboardStats-BsvXGw85.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-ZoneManagement",
    path: "/Learn/Student/HomeVisit/Admin/Components/ZoneManagement",
    component: () => import('./ZoneManagement-BWwnKEHv.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Teacher-Components-HomeVisitCard",
    path: "/Learn/Student/HomeVisit/Teacher/Components/HomeVisitCard",
    component: () => import('./HomeVisitCard-BFPeJSf0.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-VisitDetailModal",
    path: "/Learn/Student/HomeVisit/Admin/Components/VisitDetailModal",
    component: () => import('./VisitDetailModal-DqgjSC1h.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-StudentManagement",
    path: "/Learn/Student/HomeVisit/Admin/Components/StudentManagement",
    component: () => import('./StudentManagement-BQ1M95q4.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Admin-Components-VisitsListSection",
    path: "/Learn/Student/HomeVisit/Admin/Components/VisitsListSection",
    component: () => import('./VisitsListSection-CKI7NKpE.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Teacher-Components-StudentInfoGrid",
    path: "/Learn/Student/HomeVisit/Teacher/Components/StudentInfoGrid",
    component: () => import('./StudentInfoGrid-VBS3PfCt.mjs')
  },
  {
    name: "Learn-Student-HomeVisit-Teacher-Components-ImageGalleryModal",
    path: "/Learn/Student/HomeVisit/Teacher/Components/ImageGalleryModal",
    component: () => import('./ImageGalleryModal-DpY9Yyol.mjs')
  }
];
const _wrapInTransition = (props, children) => {
  return { default: () => children.default?.() };
};
const ROUTE_KEY_PARENTHESES_RE = /(:\w+)\([^)]+\)/g;
const ROUTE_KEY_SYMBOLS_RE = /(:\w+)[?+*]/g;
const ROUTE_KEY_NORMAL_RE = /:\w+/g;
function generateRouteKey(route) {
  const source = route?.meta.key ?? route.path.replace(ROUTE_KEY_PARENTHESES_RE, "$1").replace(ROUTE_KEY_SYMBOLS_RE, "$1").replace(ROUTE_KEY_NORMAL_RE, (r) => route.params[r.slice(1)]?.toString() || "");
  return typeof source === "function" ? source(route) : source;
}
function isChangingPage(to, from) {
  if (to === from || from === START_LOCATION) {
    return false;
  }
  if (generateRouteKey(to) !== generateRouteKey(from)) {
    return true;
  }
  const areComponentsSame = to.matched.every(
    (comp, index) => comp.components && comp.components.default === from.matched[index]?.components?.default
  );
  if (areComponentsSame) {
    return false;
  }
  return true;
}
const routerOptions0 = {
  scrollBehavior(to, from, savedPosition) {
    const nuxtApp = useNuxtApp();
    const hashScrollBehaviour = useRouter().options?.scrollBehaviorType ?? "auto";
    if (to.path.replace(/\/$/, "") === from.path.replace(/\/$/, "")) {
      if (from.hash && !to.hash) {
        return { left: 0, top: 0 };
      }
      if (to.hash) {
        return { el: to.hash, top: _getHashElementScrollMarginTop(to.hash), behavior: hashScrollBehaviour };
      }
      return false;
    }
    const routeAllowsScrollToTop = typeof to.meta.scrollToTop === "function" ? to.meta.scrollToTop(to, from) : to.meta.scrollToTop;
    if (routeAllowsScrollToTop === false) {
      return false;
    }
    const hookToWait = nuxtApp._runningTransition ? "page:transition:finish" : "page:loading:end";
    return new Promise((resolve) => {
      if (from === START_LOCATION) {
        resolve(_calculatePosition(to, from, savedPosition, hashScrollBehaviour));
        return;
      }
      nuxtApp.hooks.hookOnce(hookToWait, () => {
        requestAnimationFrame(() => resolve(_calculatePosition(to, from, savedPosition, hashScrollBehaviour)));
      });
    });
  }
};
function _getHashElementScrollMarginTop(selector) {
  try {
    const elem = (void 0).querySelector(selector);
    if (elem) {
      return (Number.parseFloat(getComputedStyle(elem).scrollMarginTop) || 0) + (Number.parseFloat(getComputedStyle((void 0).documentElement).scrollPaddingTop) || 0);
    }
  } catch {
  }
  return 0;
}
function _calculatePosition(to, from, savedPosition, defaultHashScrollBehaviour) {
  if (savedPosition) {
    return savedPosition;
  }
  const isPageNavigation = isChangingPage(to, from);
  if (to.hash) {
    return {
      el: to.hash,
      top: _getHashElementScrollMarginTop(to.hash),
      behavior: isPageNavigation ? defaultHashScrollBehaviour : "instant"
    };
  }
  return {
    left: 0,
    top: 0
  };
}
const configRouterOptions = {
  hashMode: false,
  scrollBehaviorType: "auto"
};
const routerOptions = {
  ...configRouterOptions,
  ...routerOptions0
};
const validate = /* @__PURE__ */ defineNuxtRouteMiddleware(async (to, from) => {
  let __temp, __restore;
  if (!to.meta?.validate) {
    return;
  }
  const result = ([__temp, __restore] = executeAsync(() => Promise.resolve(to.meta.validate(to))), __temp = await __temp, __restore(), __temp);
  if (result === true) {
    return;
  }
  const error = createError({
    fatal: false,
    statusCode: result && result.statusCode || 404,
    statusMessage: result && result.statusMessage || `Page Not Found: ${to.fullPath}`,
    data: {
      path: to.fullPath
    }
  });
  return error;
});
const manifest_45route_45rule = /* @__PURE__ */ defineNuxtRouteMiddleware(async (to) => {
  {
    return;
  }
});
const globalMiddleware = [
  validate,
  manifest_45route_45rule
];
const namedMiddleware = {
  "admin-guest": () => import('./admin-guest-DwzyjMdM.mjs'),
  auth: () => import('./auth-AJkvoRQ1.mjs'),
  guest: () => import('./guest-BxyDnGjt.mjs'),
  "nuxnan-admin": () => import('./nuxnan-admin-DXeJnF6M.mjs'),
  "plearnd-admin": () => import('./plearnd-admin-CVf213Xb.mjs')
};
const plugin$1 = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:router",
  enforce: "pre",
  async setup(nuxtApp) {
    let __temp, __restore;
    let routerBase = (/* @__PURE__ */ useRuntimeConfig()).app.baseURL;
    const history = routerOptions.history?.(routerBase) ?? createMemoryHistory(routerBase);
    const routes = routerOptions.routes ? ([__temp, __restore] = executeAsync(() => routerOptions.routes(_routes)), __temp = await __temp, __restore(), __temp) ?? _routes : _routes;
    let startPosition;
    const router = createRouter({
      ...routerOptions,
      scrollBehavior: (to, from, savedPosition) => {
        if (from === START_LOCATION) {
          startPosition = savedPosition;
          return;
        }
        if (routerOptions.scrollBehavior) {
          router.options.scrollBehavior = routerOptions.scrollBehavior;
          if ("scrollRestoration" in (void 0).history) {
            const unsub = router.beforeEach(() => {
              unsub();
              (void 0).history.scrollRestoration = "manual";
            });
          }
          return routerOptions.scrollBehavior(to, START_LOCATION, startPosition || savedPosition);
        }
      },
      history,
      routes
    });
    nuxtApp.vueApp.use(router);
    const previousRoute = shallowRef(router.currentRoute.value);
    router.afterEach((_to, from) => {
      previousRoute.value = from;
    });
    Object.defineProperty(nuxtApp.vueApp.config.globalProperties, "previousRoute", {
      get: () => previousRoute.value
    });
    const initialURL = nuxtApp.ssrContext.url;
    const _route = shallowRef(router.currentRoute.value);
    const syncCurrentRoute = () => {
      _route.value = router.currentRoute.value;
    };
    router.afterEach((to, from) => {
      if (to.matched.at(-1)?.components?.default === from.matched.at(-1)?.components?.default) {
        syncCurrentRoute();
      }
    });
    const route = { sync: syncCurrentRoute };
    for (const key in _route.value) {
      Object.defineProperty(route, key, {
        get: () => _route.value[key],
        enumerable: true
      });
    }
    nuxtApp._route = shallowReactive(route);
    nuxtApp._middleware ||= {
      global: [],
      named: {}
    };
    if (!nuxtApp.ssrContext?.islandContext) {
      router.afterEach(async (to, _from, failure) => {
        delete nuxtApp._processingMiddleware;
        if (failure) {
          await nuxtApp.callHook("page:loading:end");
        }
        if (failure?.type === 4) {
          return;
        }
        if (to.redirectedFrom && to.fullPath !== initialURL) {
          await nuxtApp.runWithContext(() => navigateTo(to.fullPath || "/"));
        }
      });
    }
    try {
      if (true) {
        ;
        [__temp, __restore] = executeAsync(() => router.push(initialURL)), await __temp, __restore();
        ;
      }
      ;
      [__temp, __restore] = executeAsync(() => router.isReady()), await __temp, __restore();
      ;
    } catch (error2) {
      [__temp, __restore] = executeAsync(() => nuxtApp.runWithContext(() => showError(error2))), await __temp, __restore();
    }
    const resolvedInitialRoute = router.currentRoute.value;
    syncCurrentRoute();
    if (nuxtApp.ssrContext?.islandContext) {
      return { provide: { router } };
    }
    const initialLayout = nuxtApp.payload.state._layout;
    router.beforeEach(async (to, from) => {
      await nuxtApp.callHook("page:loading:start");
      to.meta = reactive(to.meta);
      if (nuxtApp.isHydrating && initialLayout && !isReadonly(to.meta.layout)) {
        to.meta.layout = initialLayout;
      }
      nuxtApp._processingMiddleware = true;
      if (!nuxtApp.ssrContext?.islandContext) {
        const middlewareEntries = /* @__PURE__ */ new Set([...globalMiddleware, ...nuxtApp._middleware.global]);
        for (const component of to.matched) {
          const componentMiddleware = component.meta.middleware;
          if (!componentMiddleware) {
            continue;
          }
          for (const entry2 of toArray$2(componentMiddleware)) {
            middlewareEntries.add(entry2);
          }
        }
        {
          const routeRules = await nuxtApp.runWithContext(() => getRouteRules({ path: to.path }));
          if (routeRules.appMiddleware) {
            for (const key in routeRules.appMiddleware) {
              if (routeRules.appMiddleware[key]) {
                middlewareEntries.add(key);
              } else {
                middlewareEntries.delete(key);
              }
            }
          }
        }
        for (const entry2 of middlewareEntries) {
          const middleware = typeof entry2 === "string" ? nuxtApp._middleware.named[entry2] || await namedMiddleware[entry2]?.().then((r) => r.default || r) : entry2;
          if (!middleware) {
            throw new Error(`Unknown route middleware: '${entry2}'.`);
          }
          try {
            if (false) ;
            const result = await nuxtApp.runWithContext(() => middleware(to, from));
            if (true) {
              if (result === false || result instanceof Error) {
                const error2 = result || createError({
                  statusCode: 404,
                  statusMessage: `Page Not Found: ${initialURL}`
                });
                await nuxtApp.runWithContext(() => showError(error2));
                return false;
              }
            }
            if (result === true) {
              continue;
            }
            if (result === false) {
              return result;
            }
            if (result) {
              if (isNuxtError(result) && result.fatal) {
                await nuxtApp.runWithContext(() => showError(result));
              }
              return result;
            }
          } catch (err) {
            const error2 = createError(err);
            if (error2.fatal) {
              await nuxtApp.runWithContext(() => showError(error2));
            }
            return error2;
          }
        }
      }
    });
    router.onError(async () => {
      delete nuxtApp._processingMiddleware;
      await nuxtApp.callHook("page:loading:end");
    });
    router.afterEach((to) => {
      if (to.matched.length === 0) {
        return nuxtApp.runWithContext(() => showError(createError({
          statusCode: 404,
          fatal: false,
          statusMessage: `Page not found: ${to.fullPath}`,
          data: {
            path: to.fullPath
          }
        })));
      }
    });
    nuxtApp.hooks.hookOnce("app:created", async () => {
      try {
        if ("name" in resolvedInitialRoute) {
          resolvedInitialRoute.name = void 0;
        }
        await router.replace({
          ...resolvedInitialRoute,
          force: true
        });
        router.options.scrollBehavior = routerOptions.scrollBehavior;
      } catch (error2) {
        await nuxtApp.runWithContext(() => showError(error2));
      }
    });
    return { provide: { router } };
  }
});
const reducers = [
  ["NuxtError", (data) => isNuxtError(data) && data.toJSON()],
  ["EmptyShallowRef", (data) => isRef(data) && isShallow(data) && !data.value && (typeof data.value === "bigint" ? "0n" : JSON.stringify(data.value) || "_")],
  ["EmptyRef", (data) => isRef(data) && !data.value && (typeof data.value === "bigint" ? "0n" : JSON.stringify(data.value) || "_")],
  ["ShallowRef", (data) => isRef(data) && isShallow(data) && data.value],
  ["ShallowReactive", (data) => isReactive(data) && isShallow(data) && toRaw(data)],
  ["Ref", (data) => isRef(data) && data.value],
  ["Reactive", (data) => isReactive(data) && toRaw(data)]
];
const revive_payload_server_MVtmlZaQpj6ApFmshWfUWl5PehCebzaBf2NuRMiIbms = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:revive-payload:server",
  setup() {
    for (const [reducer, fn] of reducers) {
      definePayloadReducer(reducer, fn);
    }
  }
});
defineComponent({
  name: "ServerPlaceholder",
  render() {
    return createElementBlock("div");
  }
});
const clientOnlySymbol = /* @__PURE__ */ Symbol.for("nuxt:client-only");
const __nuxt_component_0$2 = defineComponent({
  name: "ClientOnly",
  inheritAttrs: false,
  props: ["fallback", "placeholder", "placeholderTag", "fallbackTag"],
  ...false,
  setup(props, { slots, attrs }) {
    const mounted = shallowRef(false);
    const vm = getCurrentInstance$1();
    if (vm) {
      vm._nuxtClientOnly = true;
    }
    provide(clientOnlySymbol, true);
    return () => {
      if (mounted.value) {
        const vnodes = slots.default?.();
        if (vnodes && vnodes.length === 1) {
          return [cloneVNode(vnodes[0], attrs)];
        }
        return vnodes;
      }
      const slot = slots.fallback || slots.placeholder;
      if (slot) {
        return h(slot);
      }
      const fallbackStr = props.fallback || props.placeholder || "";
      const fallbackTag = props.fallbackTag || props.placeholderTag || "span";
      return createElementBlock(fallbackTag, attrs, fallbackStr);
    };
  }
});
const useStateKeyPrefix = "$s";
function useState(...args) {
  const autoKey = typeof args[args.length - 1] === "string" ? args.pop() : void 0;
  if (typeof args[0] !== "string") {
    args.unshift(autoKey);
  }
  const [_key, init] = args;
  if (!_key || typeof _key !== "string") {
    throw new TypeError("[nuxt] [useState] key must be a string: " + _key);
  }
  if (init !== void 0 && typeof init !== "function") {
    throw new Error("[nuxt] [useState] init must be a function: " + init);
  }
  const key = useStateKeyPrefix + _key;
  const nuxtApp = useNuxtApp();
  const state = toRef(nuxtApp.payload.state, key);
  if (state.value === void 0 && init) {
    const initialValue = init();
    if (isRef(initialValue)) {
      nuxtApp.payload.state[key] = initialValue;
      return initialValue;
    }
    state.value = initialValue;
  }
  return state;
}
function useRequestURL(opts) {
  {
    return getRequestURL(useRequestEvent(), opts);
  }
}
const plugin = /* @__PURE__ */ defineNuxtPlugin({
  name: "pinia",
  setup(nuxtApp) {
    const pinia = createPinia();
    nuxtApp.vueApp.use(pinia);
    setActivePinia(pinia);
    {
      nuxtApp.payload.pinia = toRaw(pinia.state.value);
    }
    return {
      provide: {
        pinia
      }
    };
  }
});
const components_plugin_z4hgvsiddfKkfXTP6M8M4zG5Cb7sGnDhcryKVM45Di4 = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:global-components"
});
function warn(msg, err) {
  if (typeof console !== "undefined") {
    console.warn(`[intlify] ` + msg);
    if (err) {
      console.warn(err.stack);
    }
  }
}
const makeSymbol = (name, shareable = false) => !shareable ? Symbol(name) : Symbol.for(name);
const generateFormatCacheKey = (locale, key, source) => friendlyJSONstringify({ l: locale, k: key, s: source });
const friendlyJSONstringify = (json) => JSON.stringify(json).replace(/\u2028/g, "\\u2028").replace(/\u2029/g, "\\u2029").replace(/\u0027/g, "\\u0027");
const isNumber = (val) => typeof val === "number" && isFinite(val);
const isDate = (val) => toTypeString(val) === "[object Date]";
const isRegExp = (val) => toTypeString(val) === "[object RegExp]";
const isEmptyObject = (val) => isPlainObject(val) && Object.keys(val).length === 0;
const assign = Object.assign;
const _create = Object.create;
const create = (obj = null) => _create(obj);
function escapeHtml(rawText) {
  return rawText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;").replace(/\//g, "&#x2F;").replace(/=/g, "&#x3D;");
}
function escapeAttributeValue(value) {
  return value.replace(/&(?![a-zA-Z0-9#]{2,6};)/g, "&amp;").replace(/"/g, "&quot;").replace(/'/g, "&apos;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
function sanitizeTranslatedHtml(html) {
  html = html.replace(/(\w+)\s*=\s*"([^"]*)"/g, (_, attrName, attrValue) => `${attrName}="${escapeAttributeValue(attrValue)}"`);
  html = html.replace(/(\w+)\s*=\s*'([^']*)'/g, (_, attrName, attrValue) => `${attrName}='${escapeAttributeValue(attrValue)}'`);
  const eventHandlerPattern = /\s*on\w+\s*=\s*["']?[^"'>]+["']?/gi;
  if (eventHandlerPattern.test(html)) {
    html = html.replace(/(\s+)(on)(\w+\s*=)/gi, "$1&#111;n$3");
  }
  const javascriptUrlPattern = [
    // In href, src, action, formaction attributes
    /(\s+(?:href|src|action|formaction)\s*=\s*["']?)\s*javascript:/gi,
    // In style attributes within url()
    /(style\s*=\s*["'][^"']*url\s*\(\s*)javascript:/gi
  ];
  javascriptUrlPattern.forEach((pattern) => {
    html = html.replace(pattern, "$1javascript&#58;");
  });
  return html;
}
const hasOwnProperty = Object.prototype.hasOwnProperty;
function hasOwn(obj, key) {
  return hasOwnProperty.call(obj, key);
}
const isArray = Array.isArray;
const isFunction = (val) => typeof val === "function";
const isString = (val) => typeof val === "string";
const isBoolean = (val) => typeof val === "boolean";
const isObject = (val) => val !== null && typeof val === "object";
const isPromise = (val) => {
  return isObject(val) && isFunction(val.then) && isFunction(val.catch);
};
const objectToString = Object.prototype.toString;
const toTypeString = (value) => objectToString.call(value);
const isPlainObject = (val) => toTypeString(val) === "[object Object]";
const toDisplayString = (val) => {
  return val == null ? "" : isArray(val) || isPlainObject(val) && val.toString === objectToString ? JSON.stringify(val, null, 2) : String(val);
};
function join(items, separator2 = "") {
  return items.reduce((str, item, index) => index === 0 ? str + item : str + separator2 + item, "");
}
const isNotObjectOrIsArray = (val) => !isObject(val) || isArray(val);
function deepCopy(src, des) {
  if (isNotObjectOrIsArray(src) || isNotObjectOrIsArray(des)) {
    throw new Error("Invalid value");
  }
  const stack = [{ src, des }];
  while (stack.length) {
    const { src: src2, des: des2 } = stack.pop();
    Object.keys(src2).forEach((key) => {
      if (key === "__proto__") {
        return;
      }
      if (isObject(src2[key]) && !isObject(des2[key])) {
        des2[key] = Array.isArray(src2[key]) ? [] : create();
      }
      if (isNotObjectOrIsArray(des2[key]) || isNotObjectOrIsArray(src2[key])) {
        des2[key] = src2[key];
      } else {
        stack.push({ src: src2[key], des: des2[key] });
      }
    });
  }
}
function localeHead$1(options, currentLanguage = options.getCurrentLanguage(), currentDirection = options.getCurrentDirection()) {
  const metaObject = {
    htmlAttrs: {},
    link: [],
    meta: []
  };
  if (options.dir) {
    metaObject.htmlAttrs.dir = currentDirection;
  }
  if (options.lang && currentLanguage) {
    metaObject.htmlAttrs.lang = currentLanguage;
  }
  if (options.seo) {
    const alternateLinks = getHreflangLinks(options);
    metaObject.link = metaObject.link.concat(
      alternateLinks,
      getCanonicalLink(options)
    );
    metaObject.meta = metaObject.meta.concat(
      getOgUrl(options),
      getCurrentOgLocale(options),
      getAlternateOgLocales(
        options,
        options.locales.map((x) => x.language || x.code)
      )
    );
  }
  return metaObject;
}
function createLocaleMap(locales) {
  const localeMap = /* @__PURE__ */ new Map();
  for (const locale of locales) {
    if (!locale.language) {
      console.warn("Locale `language` ISO code is required to generate alternate link");
      continue;
    }
    const [language, region] = locale.language.split("-");
    if (language && region && (locale.isCatchallLocale || !localeMap.has(language))) {
      localeMap.set(language, locale);
    }
    localeMap.set(locale.language, locale);
  }
  return localeMap;
}
function getHreflangLinks(options) {
  if (!options.hreflangLinks) {
    return [];
  }
  const links = [];
  const localeMap = createLocaleMap(options.locales);
  for (const [language, locale] of localeMap.entries()) {
    const link = getHreflangLink(language, locale, options);
    if (!link) {
      continue;
    }
    links.push(link);
    if (options.defaultLocale && options.defaultLocale === locale.code && links[0].hreflang !== "x-default") {
      links.unshift(
        { [options.key]: "i18n-xd", rel: "alternate", href: link.href, hreflang: "x-default" }
      );
    }
  }
  return links;
}
function getHreflangLink(language, locale, options, routeWithoutQuery = options.strictCanonicals ? options.getRouteWithoutQuery() : void 0) {
  const localePath2 = options.getLocalizedRoute(locale.code, routeWithoutQuery);
  if (!localePath2) {
    return void 0;
  }
  const href = withQuery(
    hasProtocol(localePath2) ? localePath2 : joinURL(options.baseUrl, localePath2),
    options.strictCanonicals ? getCanonicalQueryParams(options) : {}
  );
  return { [options.key]: `i18n-alt-${language}`, rel: "alternate", href, hreflang: language };
}
function getCanonicalUrl(options, route = options.getCurrentRoute()) {
  const currentRoute = options.getLocaleRoute(
    Object.assign({}, route, { path: void 0, name: options.getRouteBaseName(route) })
  );
  if (!currentRoute) {
    return "";
  }
  return withQuery(joinURL(options.baseUrl, currentRoute.path), getCanonicalQueryParams(options));
}
function getCanonicalLink(options, href = getCanonicalUrl(options)) {
  if (!href) {
    return [];
  }
  return [{ [options.key]: "i18n-can", rel: "canonical", href }];
}
function getCanonicalQueryParams(options, route = options.getCurrentRoute()) {
  const currentRoute = options.getLocaleRoute(
    Object.assign({}, route, { path: void 0, name: options.getRouteBaseName(route) })
  );
  const currentRouteQuery = currentRoute?.query ?? {};
  const params = {};
  for (const param of options.canonicalQueries.filter((x) => x in currentRouteQuery)) {
    params[param] ??= [];
    for (const val of toArray$1(currentRouteQuery[param])) {
      params[param].push(val || "");
    }
  }
  return params;
}
function getOgUrl(options, href = getCanonicalUrl(options)) {
  if (!href) {
    return [];
  }
  return [
    { [options.key]: "i18n-og-url", property: "og:url", content: href }
  ];
}
function getCurrentOgLocale(options, currentLanguage = options.getCurrentLanguage()) {
  if (!currentLanguage) {
    return [];
  }
  return [
    { [options.key]: "i18n-og", property: "og:locale", content: formatOgLanguage(currentLanguage) }
  ];
}
function getAlternateOgLocales(options, languages, currentLanguage = options.getCurrentLanguage()) {
  const alternateLocales = languages.filter((locale) => locale && locale !== currentLanguage);
  return alternateLocales.map(
    (locale) => ({
      [options.key]: `i18n-og-alt-${locale}`,
      property: "og:locale:alternate",
      content: formatOgLanguage(locale)
    })
  );
}
function formatOgLanguage(val = "") {
  return val.replace(/-/g, "_");
}
function toArray$1(value) {
  return Array.isArray(value) ? value : [value];
}
function localePath(ctx, route, locale = ctx.getLocale()) {
  if (isString(route) && hasProtocol(route, { acceptRelative: true })) {
    return route;
  }
  try {
    return resolveRoute(ctx, route, locale).fullPath;
  } catch {
    return "";
  }
}
function localeRoute(ctx, route, locale = ctx.getLocale()) {
  try {
    return resolveRoute(ctx, route, locale);
  } catch {
    return;
  }
}
function normalizeRawLocation(route) {
  if (!isString(route)) {
    return assign({}, route);
  }
  if (route[0] === "/") {
    const { pathname: path, search, hash } = parsePath(route);
    return { path, query: parseQuery(search), hash };
  }
  return { name: route };
}
function resolveRoute(ctx, route, locale) {
  const normalized = normalizeRawLocation(route);
  const resolved = ctx.router.resolve(ctx.resolveLocalizedRouteObject(normalized, locale));
  if (resolved.name) {
    return resolved;
  }
  return ctx.router.resolve(route);
}
function switchLocalePath(ctx, locale, route = ctx.router.currentRoute.value) {
  const name = ctx.getRouteBaseName(route);
  if (!name) {
    return "";
  }
  const routeCopy = {
    name,
    params: assign({}, route.params, ctx.getLocalizedDynamicParams(locale)),
    fullPath: route.fullPath,
    query: route.query,
    hash: route.hash,
    path: route.path,
    meta: route.meta
  };
  const path = localePath(ctx, routeCopy, locale);
  return ctx.afterSwitchLocalePath(path, locale);
}
function createHeadContext(ctx, config, locale = ctx.getLocale(), locales = ctx.getLocales(), baseUrl = ctx.getBaseUrl()) {
  const currentLocale = locales.find((l) => l.code === locale) || {};
  const canonicalQueries = typeof config.seo === "object" && config.seo?.canonicalQueries || [];
  if (!baseUrl && true && true) {
    console.warn("I18n `baseUrl` is required to generate valid SEO tag links.");
  }
  return {
    ...config,
    key: "id",
    locales,
    baseUrl,
    canonicalQueries,
    hreflangLinks: ctx.routingOptions.hreflangLinks,
    defaultLocale: ctx.routingOptions.defaultLocale,
    strictCanonicals: ctx.routingOptions.strictCanonicals,
    getRouteBaseName: ctx.getRouteBaseName,
    getCurrentRoute: () => ctx.router.currentRoute.value,
    getCurrentLanguage: () => currentLocale.language,
    getCurrentDirection: () => currentLocale.dir || "ltr",
    getLocaleRoute: (route) => localeRoute(ctx, route),
    getLocalizedRoute: (locale2, route) => switchLocalePath(ctx, locale2, route),
    getRouteWithoutQuery: () => {
      try {
        return assign({}, ctx.router.resolve({ query: {} }), { meta: ctx.router.currentRoute.value.meta });
      } catch {
        return void 0;
      }
    }
  };
}
function localeHead(ctx, { dir = true, lang = true, seo = true }) {
  return localeHead$1(createHeadContext(ctx, { dir, lang, seo }));
}
function parseAcceptLanguage(value) {
  return value.split(",").map((tag) => tag.split(";")[0]).filter(
    (tag) => !(tag === "*" || tag === "")
  );
}
function createPathIndexLanguageParser(index = 0) {
  return (path) => {
    const rawPath = typeof path === "string" ? path : path.pathname;
    const normalizedPath = rawPath.split("?")[0];
    const parts = normalizedPath.split("/");
    if (parts[0] === "") {
      parts.shift();
    }
    return parts.length > index ? parts[index] || "" : "";
  };
}
const separator = "___";
function normalizeRouteName(routeName) {
  if (typeof routeName === "string") {
    return routeName;
  }
  if (routeName != null) {
    return routeName.toString();
  }
  return "";
}
function getRouteBaseName(route) {
  return normalizeRouteName(typeof route === "object" ? route?.name : route).split(separator)[0];
}
const pathLanguageParser = createPathIndexLanguageParser(0);
const getLocaleFromRoutePath = (path) => pathLanguageParser(path);
const getLocaleFromRouteName = (name) => name.split(separator).at(1) ?? "";
function normalizeInput(input) {
  return typeof input !== "object" ? String(input) : String(input?.name || input?.path || "");
}
function getLocaleFromRoute(route) {
  const input = normalizeInput(route);
  return input[0] === "/" ? getLocaleFromRoutePath(input) : getLocaleFromRouteName(input);
}
function createLocaleRouteNameGetter(defaultLocale) {
  {
    return (routeName) => normalizeRouteName(routeName);
  }
}
function createLocalizedRouteByPathResolver(router) {
  {
    return (route) => route;
  }
}
const localeCodes = [
  "th",
  "en"
];
const localeLoaders = {
  th: [
    {
      key: "locale_th_46json_8dbaabae",
      load: () => import(
        './th-WSwILUUL.mjs'
        /* webpackChunkName: "locale_th_46json_8dbaabae" */
      ),
      cache: true
    }
  ],
  en: [
    {
      key: "locale_en_46json_d959d062",
      load: () => import(
        './en-Ctnc4Ale.mjs'
        /* webpackChunkName: "locale_en_46json_d959d062" */
      ),
      cache: true
    }
  ]
};
const vueI18nConfigs = [];
const normalizedLocales = [
  {
    code: "th",
    name: "ภาษาไทย",
    language: void 0
  },
  {
    code: "en",
    name: "English",
    language: void 0
  }
];
const cacheMessages = /* @__PURE__ */ new Map();
const merger = createDefu((obj, key, value) => {
  if (key === "messages" || key === "datetimeFormats" || key === "numberFormats") {
    obj[key] ??= create(null);
    deepCopy(value, obj[key]);
    return true;
  }
});
async function loadVueI18nOptions(vueI18nConfigs2) {
  const nuxtApp = useNuxtApp();
  let vueI18nOptions = { messages: create(null) };
  for (const configFile of vueI18nConfigs2) {
    const resolver = await configFile().then((x) => x.default);
    const resolved = isFunction(resolver) ? await nuxtApp.runWithContext(() => resolver()) : resolver;
    vueI18nOptions = merger(create(null), resolved, vueI18nOptions);
  }
  vueI18nOptions.fallbackLocale ??= false;
  return vueI18nOptions;
}
const isModule = (val) => toTypeString(val) === "[object Module]";
const isResolvedModule = (val) => isModule(val) || true;
async function getLocaleMessages$1(locale, loader) {
  const nuxtApp = useNuxtApp();
  try {
    const getter = await nuxtApp.runWithContext(loader.load).then((x) => isResolvedModule(x) ? x.default : x);
    return isFunction(getter) ? await nuxtApp.runWithContext(() => getter(locale)) : getter;
  } catch (e) {
    throw new Error(`Failed loading locale (${locale}): ` + e.message);
  }
}
async function getLocaleMessagesMergedCached(locale, loaders = []) {
  const nuxtApp = useNuxtApp();
  const messages = await Promise.all(loaders.map(async (loader) => {
    const cached = getCachedMessages(loader);
    const messages2 = cached || await nuxtApp.runWithContext(() => getLocaleMessages$1(locale, loader));
    if (!cached && loader.cache !== false) {
      cacheMessages.set(loader.key, { ttl: Date.now() + 86400 * 1e3, value: messages2 });
    }
    return messages2;
  }));
  const merged = {};
  for (const message of messages) {
    deepCopy(message, merged);
  }
  return merged;
}
function getCachedMessages(loader) {
  if (loader.cache === false) {
    return;
  }
  const cache2 = cacheMessages.get(loader.key);
  if (cache2 == null) {
    return;
  }
  return cache2.ttl > Date.now() ? cache2.value : void 0;
}
function getI18nTarget(i18n) {
  return i18n != null && "global" in i18n && "mode" in i18n ? i18n.global : i18n;
}
function getComposer$3(i18n) {
  const target = getI18nTarget(i18n);
  return "__composer" in target ? target.__composer : target;
}
function useRuntimeI18n(nuxtApp, event) {
  if (!nuxtApp) {
    return (/* @__PURE__ */ useRuntimeConfig()).public.i18n;
  }
  return nuxtApp.$config.public.i18n;
}
function useI18nDetection(nuxtApp) {
  const detectBrowserLanguage = useRuntimeI18n(nuxtApp).detectBrowserLanguage;
  const detect = detectBrowserLanguage || {};
  return {
    ...detect,
    enabled: !!detectBrowserLanguage,
    cookieKey: detect.cookieKey || "i18n_redirected"
  };
}
function resolveRootRedirect(config) {
  if (!config) {
    return void 0;
  }
  return {
    path: "/" + (isString(config) ? config : config.path).replace(/^\//, ""),
    code: !isString(config) && config.statusCode || 302
  };
}
function toArray(value) {
  return Array.isArray(value) ? value : [value];
}
function matchDomainLocale(locales, host, pathLocale) {
  const normalizeDomain = (domain = "") => domain.replace(/https?:\/\//, "");
  const matches = locales.filter(
    (locale) => normalizeDomain(locale.domain) === host || toArray(locale.domains).includes(host)
  );
  if (matches.length <= 1) {
    return matches[0]?.code;
  }
  return (
    // match by current path locale
    matches.find((l) => l.code === pathLocale)?.code || matches.find((l) => l.defaultForDomains?.includes(host) ?? l.domainDefault)?.code
  );
}
function domainFromLocale(domainLocales, url, locale) {
  const lang = normalizedLocales.find((x) => x.code === locale);
  const domain = domainLocales?.[locale]?.domain || lang?.domain || lang?.domains?.find((v) => v === url.host);
  if (!domain) {
    return;
  }
  if (hasProtocol(domain, { strict: true })) {
    return domain;
  }
  return url.protocol + "//" + domain;
}
function getDefaultLocaleForDomain(host) {
  return normalizedLocales.find((l) => !!l.defaultForDomains?.includes(host))?.code;
}
const isSupportedLocale = (locale) => localeCodes.includes(locale || "");
const resolveSupportedLocale = (locale) => isSupportedLocale(locale) ? locale : void 0;
const useLocaleConfigs = () => useState(
  "i18n:cached-locale-configs",
  () => void 0
);
const useResolvedLocale = () => useState("i18n:resolved-locale", () => "");
function useI18nCookie({ cookieCrossOrigin, cookieDomain, cookieSecure, cookieKey }) {
  const date = /* @__PURE__ */ new Date();
  return useCookie(cookieKey, {
    path: "/",
    readonly: false,
    expires: new Date(date.setDate(date.getDate() + 365)),
    sameSite: cookieCrossOrigin ? "none" : "lax",
    domain: cookieDomain || void 0,
    secure: cookieCrossOrigin || cookieSecure
  });
}
function createNuxtI18nContext(nuxt, vueI18n, defaultLocale) {
  const i18n = getI18nTarget(vueI18n);
  const runtimeI18n = useRuntimeI18n(nuxt);
  const detectConfig = useI18nDetection(nuxt);
  const serverLocaleConfigs = useLocaleConfigs();
  const localeCookie = useI18nCookie(detectConfig);
  const loadMap = /* @__PURE__ */ new Set();
  const getLocaleConfig = (locale) => serverLocaleConfigs.value[locale];
  const getDomainFromLocale = (locale) => domainFromLocale(runtimeI18n.domainLocales, useRequestURL({ xForwardedHost: true }), locale);
  const baseUrl = createBaseUrlGetter(nuxt, runtimeI18n.baseUrl);
  const resolvedLocale = useResolvedLocale();
  if (nuxt.ssrContext?.event?.context?.nuxtI18n?.detectLocale) {
    resolvedLocale.value = nuxt.ssrContext.event.context.nuxtI18n.detectLocale;
  }
  const loadMessagesFromClient = async (locale) => {
    const locales = getLocaleConfig(locale)?.fallbacks ?? [];
    if (!locales.includes(locale)) {
      locales.push(locale);
    }
    for (const k of locales) {
      const msg = await nuxt.runWithContext(() => getLocaleMessagesMergedCached(k, localeLoaders[k]));
      i18n.mergeLocaleMessage(k, msg);
    }
  };
  const loadMessagesFromServer = async (locale) => {
    if (locale in localeLoaders === false) {
      return;
    }
    const headers = getLocaleConfig(locale)?.cacheable ? {} : { "Cache-Control": "no-cache" };
    const messages = await $fetch(`${"/_i18n/SeXYkDvK"}/${locale}/messages.json`, { headers });
    for (const k of Object.keys(messages)) {
      i18n.mergeLocaleMessage(k, messages[k]);
    }
  };
  const ctx = {
    vueI18n,
    initial: true,
    preloaded: false,
    config: runtimeI18n,
    rootRedirect: resolveRootRedirect(runtimeI18n.rootRedirect),
    redirectStatusCode: runtimeI18n.redirectStatusCode ?? 302,
    dynamicResourcesSSG: false,
    getDefaultLocale: () => defaultLocale,
    getLocale: () => unref(i18n.locale),
    setLocale: async (locale) => {
      const oldLocale = ctx.getLocale();
      if (locale === oldLocale || !isSupportedLocale(locale)) {
        return;
      }
      if (isRef(i18n.locale)) {
        i18n.locale.value = locale;
      } else {
        i18n.locale = locale;
      }
      await nuxt.callHook("i18n:localeSwitched", { newLocale: locale, oldLocale });
      resolvedLocale.value = locale;
    },
    setLocaleSuspend: async (locale) => {
      if (!isSupportedLocale(locale)) {
        return;
      }
      ctx.vueI18n.__pendingLocale = locale;
      ctx.vueI18n.__pendingLocalePromise = new Promise((resolve) => {
        ctx.vueI18n.__resolvePendingLocalePromise = async () => {
          ctx.setCookieLocale(locale);
          await ctx.setLocale(locale);
          ctx.vueI18n.__pendingLocale = void 0;
          resolve();
        };
      });
      {
        await ctx.vueI18n.__resolvePendingLocalePromise?.();
      }
    },
    getLocales: () => unref(i18n.locales).map((x) => isString(x) ? { code: x } : x),
    setCookieLocale: (locale) => {
      if (detectConfig.useCookie && isSupportedLocale(locale)) {
        localeCookie.value = locale;
      }
    },
    getBaseUrl: (locale) => {
      if (locale) {
        return joinURL(getDomainFromLocale(locale) || baseUrl(), nuxt.$config.app.baseURL);
      }
      return joinURL(baseUrl(), nuxt.$config.app.baseURL);
    },
    loadMessages: async (locale) => {
      if (nuxt.isHydrating && loadMap.has(locale)) {
        return;
      }
      try {
        return ctx.dynamicResourcesSSG || false ? await loadMessagesFromClient(locale) : await loadMessagesFromServer(locale);
      } catch (e) {
        console.warn(`Failed to load messages for locale "${locale}"`, e);
      } finally {
        loadMap.add(locale);
      }
    },
    composableCtx: void 0
  };
  ctx.composableCtx = createComposableContext(ctx, nuxt);
  return ctx;
}
function useNuxtI18nContext(nuxt) {
  if (nuxt._nuxtI18n == null) {
    throw new Error("Nuxt I18n context has not been set up yet.");
  }
  return nuxt._nuxtI18n;
}
function matchBrowserLocale(locales, browserLocales) {
  const matchedLocales = [];
  for (const [index, browserCode] of browserLocales.entries()) {
    const matchedLocale = locales.find((l) => l.language?.toLowerCase() === browserCode.toLowerCase());
    if (matchedLocale) {
      matchedLocales.push({ code: matchedLocale.code, score: 1 - index / browserLocales.length });
      break;
    }
  }
  for (const [index, browserCode] of browserLocales.entries()) {
    const languageCode = browserCode.split("-")[0].toLowerCase();
    const matchedLocale = locales.find((l) => l.language?.split("-")[0].toLowerCase() === languageCode);
    if (matchedLocale) {
      matchedLocales.push({ code: matchedLocale.code, score: 0.999 - index / browserLocales.length });
      break;
    }
  }
  return matchedLocales;
}
function compareBrowserLocale(a, b) {
  if (a.score === b.score) {
    return b.code.length - a.code.length;
  }
  return b.score - a.score;
}
function findBrowserLocale(locales, browserLocales) {
  const matchedLocales = matchBrowserLocale(
    locales.map((l) => ({ code: l.code, language: l.language || l.code })),
    browserLocales
  );
  return matchedLocales.sort(compareBrowserLocale).at(0)?.code ?? "";
}
const getCookieLocale = (event, cookieName) => getCookie(event, cookieName) || void 0;
const getRouteLocale = (event, route) => getLocaleFromRoute(route);
const getHeaderLocale = (event) => findBrowserLocale(normalizedLocales, parseAcceptLanguage(getRequestHeader(event, "accept-language") || ""));
const getHostLocale = (event, path, domainLocales) => {
  const host = getRequestURL(event, { xForwardedHost: true }).host;
  const locales = normalizedLocales.map((l) => ({
    ...l,
    domain: domainLocales[l.code]?.domain ?? l.domain
  }));
  return matchDomainLocale(locales, host, getLocaleFromRoutePath(path));
};
const useDetectors = (event, config, nuxtApp) => {
  if (!event) {
    throw new Error("H3Event is required for server-side locale detection");
  }
  const runtimeI18n = useRuntimeI18n(nuxtApp);
  return {
    cookie: () => getCookieLocale(event, config.cookieKey),
    header: () => getHeaderLocale(event),
    navigator: () => void 0,
    host: (path) => getHostLocale(event, path, runtimeI18n.domainLocales),
    route: (path) => getRouteLocale(event, path)
  };
};
const isRouteLocationPathRaw = (val) => !!val.path && !val.name;
function useComposableContext(nuxtApp) {
  const context = nuxtApp?._nuxtI18n?.composableCtx;
  if (!context) {
    throw new Error(
      "i18n context is not initialized. Ensure the i18n plugin is installed and the composable is used within a Vue component or setup function."
    );
  }
  return context;
}
const formatTrailingSlash = withoutTrailingSlash;
function createComposableContext(ctx, nuxtApp = useNuxtApp()) {
  const router = useRouter();
  useDetectors(useRequestEvent(), useI18nDetection(nuxtApp), nuxtApp);
  const defaultLocale = ctx.getDefaultLocale();
  const getLocalizedRouteName = createLocaleRouteNameGetter();
  function resolveLocalizedRouteByName(route, locale) {
    route.name = getRouteBaseName(route.name || router.currentRoute.value);
    const localizedName = getLocalizedRouteName(route.name, locale);
    if (router.hasRoute(localizedName)) {
      route.name = localizedName;
    }
    return route;
  }
  const routeByPathResolver = createLocalizedRouteByPathResolver();
  function resolveLocalizedRouteByPath(input, locale) {
    const route = routeByPathResolver(input, locale);
    const baseName = getRouteBaseName(route);
    if (baseName) {
      route.name = getLocalizedRouteName(baseName, locale);
      return route;
    }
    route.path = formatTrailingSlash(route.path, true);
    return route;
  }
  const composableCtx = {
    router,
    _head: void 0,
    get head() {
      this._head ??= useHead({});
      return this._head;
    },
    metaState: { htmlAttrs: {}, meta: [], link: [] },
    seoSettings: {
      dir: false,
      lang: false,
      seo: false
    },
    localePathPayload: getLocalePathPayload(),
    routingOptions: {
      defaultLocale,
      strictCanonicals: ctx.config.experimental.alternateLinkCanonicalQueries ?? true,
      hreflangLinks: false
    },
    getLocale: ctx.getLocale,
    getLocales: ctx.getLocales,
    getBaseUrl: ctx.getBaseUrl,
    getRouteBaseName,
    getRouteLocalizedParams: () => router.currentRoute.value.meta["nuxtI18nInternal"] ?? {},
    getLocalizedDynamicParams: (locale) => {
      return composableCtx.getRouteLocalizedParams()?.[locale];
    },
    afterSwitchLocalePath: (path, locale) => {
      composableCtx.getRouteLocalizedParams();
      return path;
    },
    resolveLocalizedRouteObject: (route, locale) => {
      return isRouteLocationPathRaw(route) ? resolveLocalizedRouteByPath(route, locale) : resolveLocalizedRouteByName(route, locale);
    }
  };
  return composableCtx;
}
function getLocalePathPayload(nuxtApp = useNuxtApp()) {
  return JSON.parse("{}");
}
async function loadAndSetLocale(nuxtApp, locale) {
  const ctx = useNuxtI18nContext(nuxtApp);
  const oldLocale = ctx.getLocale();
  if (locale === oldLocale && !ctx.initial) {
    return locale;
  }
  const data = { oldLocale, newLocale: locale, initialSetup: ctx.initial, context: nuxtApp };
  let override = await nuxtApp.callHook("i18n:beforeLocaleSwitch", data);
  if (override != null && false) {
    console.warn("[nuxt-i18n] Do not return in `i18n:beforeLocaleSwitch`, mutate `data.newLocale` instead.");
  }
  override ??= data.newLocale;
  if (isSupportedLocale(override)) {
    locale = override;
  }
  await ctx.loadMessages(locale);
  await ctx.setLocaleSuspend(locale);
  return locale;
}
function skipDetect(detect, path, pathLocale) {
  {
    return false;
  }
}
function detectLocale(nuxtApp, route) {
  const detectConfig = useI18nDetection(nuxtApp);
  const detectors = useDetectors(useRequestEvent(nuxtApp), detectConfig, nuxtApp);
  const ctx = useNuxtI18nContext(nuxtApp);
  const path = isString(route) ? route : route.path;
  function* detect() {
    if (ctx.initial && detectConfig.enabled && !skipDetect(detectConfig, path, detectors.route(path))) {
      yield detectors.cookie();
      yield detectors.header();
      yield detectors.navigator();
      yield detectConfig.fallbackLocale;
    }
  }
  for (const detected of detect()) {
    if (detected && isSupportedLocale(detected)) {
      return detected;
    }
  }
  return ctx.getLocale() || ctx.getDefaultLocale() || "";
}
function navigate(nuxtApp, to, locale) {
  {
    return;
  }
}
function createBaseUrlGetter(nuxt, baseUrl, defaultLocale, getDomainFromLocale) {
  if (isFunction(baseUrl)) {
    return () => baseUrl(nuxt);
  }
  return () => {
    return baseUrl ?? "";
  };
}
function createPosition(line, column, offset) {
  return { line, column, offset };
}
function createLocation(start, end, source) {
  const loc = { start, end };
  return loc;
}
const CompileErrorCodes = {
  // tokenizer error codes
  EXPECTED_TOKEN: 1,
  INVALID_TOKEN_IN_PLACEHOLDER: 2,
  UNTERMINATED_SINGLE_QUOTE_IN_PLACEHOLDER: 3,
  UNKNOWN_ESCAPE_SEQUENCE: 4,
  INVALID_UNICODE_ESCAPE_SEQUENCE: 5,
  UNBALANCED_CLOSING_BRACE: 6,
  UNTERMINATED_CLOSING_BRACE: 7,
  EMPTY_PLACEHOLDER: 8,
  NOT_ALLOW_NEST_PLACEHOLDER: 9,
  INVALID_LINKED_FORMAT: 10,
  // parser error codes
  MUST_HAVE_MESSAGES_IN_PLURAL: 11,
  UNEXPECTED_EMPTY_LINKED_MODIFIER: 12,
  UNEXPECTED_EMPTY_LINKED_KEY: 13,
  UNEXPECTED_LEXICAL_ANALYSIS: 14};
const COMPILE_ERROR_CODES_EXTEND_POINT = 17;
function createCompileError(code, loc, options = {}) {
  const { domain, messages, args } = options;
  const msg = code;
  const error = new SyntaxError(String(msg));
  error.code = code;
  if (loc) {
    error.location = loc;
  }
  error.domain = domain;
  return error;
}
function defaultOnError(error) {
  throw error;
}
const CHAR_SP = " ";
const CHAR_CR = "\r";
const CHAR_LF = "\n";
const CHAR_LS = String.fromCharCode(8232);
const CHAR_PS = String.fromCharCode(8233);
function createScanner(str) {
  const _buf = str;
  let _index = 0;
  let _line = 1;
  let _column = 1;
  let _peekOffset = 0;
  const isCRLF = (index2) => _buf[index2] === CHAR_CR && _buf[index2 + 1] === CHAR_LF;
  const isLF = (index2) => _buf[index2] === CHAR_LF;
  const isPS = (index2) => _buf[index2] === CHAR_PS;
  const isLS = (index2) => _buf[index2] === CHAR_LS;
  const isLineEnd = (index2) => isCRLF(index2) || isLF(index2) || isPS(index2) || isLS(index2);
  const index = () => _index;
  const line = () => _line;
  const column = () => _column;
  const peekOffset = () => _peekOffset;
  const charAt = (offset) => isCRLF(offset) || isPS(offset) || isLS(offset) ? CHAR_LF : _buf[offset];
  const currentChar = () => charAt(_index);
  const currentPeek = () => charAt(_index + _peekOffset);
  function next() {
    _peekOffset = 0;
    if (isLineEnd(_index)) {
      _line++;
      _column = 0;
    }
    if (isCRLF(_index)) {
      _index++;
    }
    _index++;
    _column++;
    return _buf[_index];
  }
  function peek() {
    if (isCRLF(_index + _peekOffset)) {
      _peekOffset++;
    }
    _peekOffset++;
    return _buf[_index + _peekOffset];
  }
  function reset() {
    _index = 0;
    _line = 1;
    _column = 1;
    _peekOffset = 0;
  }
  function resetPeek(offset = 0) {
    _peekOffset = offset;
  }
  function skipToPeek() {
    const target = _index + _peekOffset;
    while (target !== _index) {
      next();
    }
    _peekOffset = 0;
  }
  return {
    index,
    line,
    column,
    peekOffset,
    charAt,
    currentChar,
    currentPeek,
    next,
    peek,
    reset,
    resetPeek,
    skipToPeek
  };
}
const EOF = void 0;
const DOT = ".";
const LITERAL_DELIMITER = "'";
const ERROR_DOMAIN$3 = "tokenizer";
function createTokenizer(source, options = {}) {
  const location = options.location !== false;
  const _scnr = createScanner(source);
  const currentOffset = () => _scnr.index();
  const currentPosition = () => createPosition(_scnr.line(), _scnr.column(), _scnr.index());
  const _initLoc = currentPosition();
  const _initOffset = currentOffset();
  const _context = {
    currentType: 13,
    offset: _initOffset,
    startLoc: _initLoc,
    endLoc: _initLoc,
    lastType: 13,
    lastOffset: _initOffset,
    lastStartLoc: _initLoc,
    lastEndLoc: _initLoc,
    braceNest: 0,
    inLinked: false,
    text: ""
  };
  const context = () => _context;
  const { onError } = options;
  function emitError(code, pos, offset, ...args) {
    const ctx = context();
    pos.column += offset;
    pos.offset += offset;
    if (onError) {
      const loc = location ? createLocation(ctx.startLoc, pos) : null;
      const err = createCompileError(code, loc, {
        domain: ERROR_DOMAIN$3,
        args
      });
      onError(err);
    }
  }
  function getToken(context2, type, value) {
    context2.endLoc = currentPosition();
    context2.currentType = type;
    const token = { type };
    if (location) {
      token.loc = createLocation(context2.startLoc, context2.endLoc);
    }
    if (value != null) {
      token.value = value;
    }
    return token;
  }
  const getEndToken = (context2) => getToken(
    context2,
    13
    /* TokenTypes.EOF */
  );
  function eat(scnr, ch) {
    if (scnr.currentChar() === ch) {
      scnr.next();
      return ch;
    } else {
      emitError(CompileErrorCodes.EXPECTED_TOKEN, currentPosition(), 0, ch);
      return "";
    }
  }
  function peekSpaces(scnr) {
    let buf = "";
    while (scnr.currentPeek() === CHAR_SP || scnr.currentPeek() === CHAR_LF) {
      buf += scnr.currentPeek();
      scnr.peek();
    }
    return buf;
  }
  function skipSpaces(scnr) {
    const buf = peekSpaces(scnr);
    scnr.skipToPeek();
    return buf;
  }
  function isIdentifierStart(ch) {
    if (ch === EOF) {
      return false;
    }
    const cc = ch.charCodeAt(0);
    return cc >= 97 && cc <= 122 || // a-z
    cc >= 65 && cc <= 90 || // A-Z
    cc === 95;
  }
  function isNumberStart(ch) {
    if (ch === EOF) {
      return false;
    }
    const cc = ch.charCodeAt(0);
    return cc >= 48 && cc <= 57;
  }
  function isNamedIdentifierStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 2) {
      return false;
    }
    peekSpaces(scnr);
    const ret = isIdentifierStart(scnr.currentPeek());
    scnr.resetPeek();
    return ret;
  }
  function isListIdentifierStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 2) {
      return false;
    }
    peekSpaces(scnr);
    const ch = scnr.currentPeek() === "-" ? scnr.peek() : scnr.currentPeek();
    const ret = isNumberStart(ch);
    scnr.resetPeek();
    return ret;
  }
  function isLiteralStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 2) {
      return false;
    }
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === LITERAL_DELIMITER;
    scnr.resetPeek();
    return ret;
  }
  function isLinkedDotStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 7) {
      return false;
    }
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === ".";
    scnr.resetPeek();
    return ret;
  }
  function isLinkedModifierStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 8) {
      return false;
    }
    peekSpaces(scnr);
    const ret = isIdentifierStart(scnr.currentPeek());
    scnr.resetPeek();
    return ret;
  }
  function isLinkedDelimiterStart(scnr, context2) {
    const { currentType } = context2;
    if (!(currentType === 7 || currentType === 11)) {
      return false;
    }
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === ":";
    scnr.resetPeek();
    return ret;
  }
  function isLinkedReferStart(scnr, context2) {
    const { currentType } = context2;
    if (currentType !== 9) {
      return false;
    }
    const fn = () => {
      const ch = scnr.currentPeek();
      if (ch === "{") {
        return isIdentifierStart(scnr.peek());
      } else if (ch === "@" || ch === "|" || ch === ":" || ch === "." || ch === CHAR_SP || !ch) {
        return false;
      } else if (ch === CHAR_LF) {
        scnr.peek();
        return fn();
      } else {
        return isTextStart(scnr, false);
      }
    };
    const ret = fn();
    scnr.resetPeek();
    return ret;
  }
  function isPluralStart(scnr) {
    peekSpaces(scnr);
    const ret = scnr.currentPeek() === "|";
    scnr.resetPeek();
    return ret;
  }
  function isTextStart(scnr, reset = true) {
    const fn = (hasSpace = false, prev = "") => {
      const ch = scnr.currentPeek();
      if (ch === "{") {
        return hasSpace;
      } else if (ch === "@" || !ch) {
        return hasSpace;
      } else if (ch === "|") {
        return !(prev === CHAR_SP || prev === CHAR_LF);
      } else if (ch === CHAR_SP) {
        scnr.peek();
        return fn(true, CHAR_SP);
      } else if (ch === CHAR_LF) {
        scnr.peek();
        return fn(true, CHAR_LF);
      } else {
        return true;
      }
    };
    const ret = fn();
    reset && scnr.resetPeek();
    return ret;
  }
  function takeChar(scnr, fn) {
    const ch = scnr.currentChar();
    if (ch === EOF) {
      return EOF;
    }
    if (fn(ch)) {
      scnr.next();
      return ch;
    }
    return null;
  }
  function isIdentifier(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 97 && cc <= 122 || // a-z
    cc >= 65 && cc <= 90 || // A-Z
    cc >= 48 && cc <= 57 || // 0-9
    cc === 95 || // _
    cc === 36;
  }
  function takeIdentifierChar(scnr) {
    return takeChar(scnr, isIdentifier);
  }
  function isNamedIdentifier(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 97 && cc <= 122 || // a-z
    cc >= 65 && cc <= 90 || // A-Z
    cc >= 48 && cc <= 57 || // 0-9
    cc === 95 || // _
    cc === 36 || // $
    cc === 45;
  }
  function takeNamedIdentifierChar(scnr) {
    return takeChar(scnr, isNamedIdentifier);
  }
  function isDigit(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 48 && cc <= 57;
  }
  function takeDigit(scnr) {
    return takeChar(scnr, isDigit);
  }
  function isHexDigit(ch) {
    const cc = ch.charCodeAt(0);
    return cc >= 48 && cc <= 57 || // 0-9
    cc >= 65 && cc <= 70 || // A-F
    cc >= 97 && cc <= 102;
  }
  function takeHexDigit(scnr) {
    return takeChar(scnr, isHexDigit);
  }
  function getDigits(scnr) {
    let ch = "";
    let num = "";
    while (ch = takeDigit(scnr)) {
      num += ch;
    }
    return num;
  }
  function readText(scnr) {
    let buf = "";
    while (true) {
      const ch = scnr.currentChar();
      if (ch === "{" || ch === "}" || ch === "@" || ch === "|" || !ch) {
        break;
      } else if (ch === CHAR_SP || ch === CHAR_LF) {
        if (isTextStart(scnr)) {
          buf += ch;
          scnr.next();
        } else if (isPluralStart(scnr)) {
          break;
        } else {
          buf += ch;
          scnr.next();
        }
      } else {
        buf += ch;
        scnr.next();
      }
    }
    return buf;
  }
  function readNamedIdentifier(scnr) {
    skipSpaces(scnr);
    let ch = "";
    let name = "";
    while (ch = takeNamedIdentifierChar(scnr)) {
      name += ch;
    }
    const currentChar = scnr.currentChar();
    if (currentChar && currentChar !== "}" && currentChar !== EOF && currentChar !== CHAR_SP && currentChar !== CHAR_LF && currentChar !== "　") {
      const invalidPart = readInvalidIdentifier(scnr);
      emitError(CompileErrorCodes.INVALID_TOKEN_IN_PLACEHOLDER, currentPosition(), 0, name + invalidPart);
      return name + invalidPart;
    }
    if (scnr.currentChar() === EOF) {
      emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
    }
    return name;
  }
  function readListIdentifier(scnr) {
    skipSpaces(scnr);
    let value = "";
    if (scnr.currentChar() === "-") {
      scnr.next();
      value += `-${getDigits(scnr)}`;
    } else {
      value += getDigits(scnr);
    }
    if (scnr.currentChar() === EOF) {
      emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
    }
    return value;
  }
  function isLiteral2(ch) {
    return ch !== LITERAL_DELIMITER && ch !== CHAR_LF;
  }
  function readLiteral(scnr) {
    skipSpaces(scnr);
    eat(scnr, `'`);
    let ch = "";
    let literal = "";
    while (ch = takeChar(scnr, isLiteral2)) {
      if (ch === "\\") {
        literal += readEscapeSequence(scnr);
      } else {
        literal += ch;
      }
    }
    const current = scnr.currentChar();
    if (current === CHAR_LF || current === EOF) {
      emitError(CompileErrorCodes.UNTERMINATED_SINGLE_QUOTE_IN_PLACEHOLDER, currentPosition(), 0);
      if (current === CHAR_LF) {
        scnr.next();
        eat(scnr, `'`);
      }
      return literal;
    }
    eat(scnr, `'`);
    return literal;
  }
  function readEscapeSequence(scnr) {
    const ch = scnr.currentChar();
    switch (ch) {
      case "\\":
      case `'`:
        scnr.next();
        return `\\${ch}`;
      case "u":
        return readUnicodeEscapeSequence(scnr, ch, 4);
      case "U":
        return readUnicodeEscapeSequence(scnr, ch, 6);
      default:
        emitError(CompileErrorCodes.UNKNOWN_ESCAPE_SEQUENCE, currentPosition(), 0, ch);
        return "";
    }
  }
  function readUnicodeEscapeSequence(scnr, unicode, digits) {
    eat(scnr, unicode);
    let sequence = "";
    for (let i = 0; i < digits; i++) {
      const ch = takeHexDigit(scnr);
      if (!ch) {
        emitError(CompileErrorCodes.INVALID_UNICODE_ESCAPE_SEQUENCE, currentPosition(), 0, `\\${unicode}${sequence}${scnr.currentChar()}`);
        break;
      }
      sequence += ch;
    }
    return `\\${unicode}${sequence}`;
  }
  function isInvalidIdentifier(ch) {
    return ch !== "{" && ch !== "}" && ch !== CHAR_SP && ch !== CHAR_LF;
  }
  function readInvalidIdentifier(scnr) {
    skipSpaces(scnr);
    let ch = "";
    let identifiers = "";
    while (ch = takeChar(scnr, isInvalidIdentifier)) {
      identifiers += ch;
    }
    return identifiers;
  }
  function readLinkedModifier(scnr) {
    let ch = "";
    let name = "";
    while (ch = takeIdentifierChar(scnr)) {
      name += ch;
    }
    return name;
  }
  function readLinkedRefer(scnr) {
    const fn = (buf) => {
      const ch = scnr.currentChar();
      if (ch === "{" || ch === "@" || ch === "|" || ch === "(" || ch === ")" || !ch) {
        return buf;
      } else if (ch === CHAR_SP) {
        return buf;
      } else if (ch === CHAR_LF || ch === DOT) {
        buf += ch;
        scnr.next();
        return fn(buf);
      } else {
        buf += ch;
        scnr.next();
        return fn(buf);
      }
    };
    return fn("");
  }
  function readPlural(scnr) {
    skipSpaces(scnr);
    const plural = eat(
      scnr,
      "|"
      /* TokenChars.Pipe */
    );
    skipSpaces(scnr);
    return plural;
  }
  function readTokenInPlaceholder(scnr, context2) {
    let token = null;
    const ch = scnr.currentChar();
    switch (ch) {
      case "{":
        if (context2.braceNest >= 1) {
          emitError(CompileErrorCodes.NOT_ALLOW_NEST_PLACEHOLDER, currentPosition(), 0);
        }
        scnr.next();
        token = getToken(
          context2,
          2,
          "{"
          /* TokenChars.BraceLeft */
        );
        skipSpaces(scnr);
        context2.braceNest++;
        return token;
      case "}":
        if (context2.braceNest > 0 && context2.currentType === 2) {
          emitError(CompileErrorCodes.EMPTY_PLACEHOLDER, currentPosition(), 0);
        }
        scnr.next();
        token = getToken(
          context2,
          3,
          "}"
          /* TokenChars.BraceRight */
        );
        context2.braceNest--;
        context2.braceNest > 0 && skipSpaces(scnr);
        if (context2.inLinked && context2.braceNest === 0) {
          context2.inLinked = false;
        }
        return token;
      case "@":
        if (context2.braceNest > 0) {
          emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
        }
        token = readTokenInLinked(scnr, context2) || getEndToken(context2);
        context2.braceNest = 0;
        return token;
      default: {
        let validNamedIdentifier = true;
        let validListIdentifier = true;
        let validLiteral = true;
        if (isPluralStart(scnr)) {
          if (context2.braceNest > 0) {
            emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
          }
          token = getToken(context2, 1, readPlural(scnr));
          context2.braceNest = 0;
          context2.inLinked = false;
          return token;
        }
        if (context2.braceNest > 0 && (context2.currentType === 4 || context2.currentType === 5 || context2.currentType === 6)) {
          emitError(CompileErrorCodes.UNTERMINATED_CLOSING_BRACE, currentPosition(), 0);
          context2.braceNest = 0;
          return readToken(scnr, context2);
        }
        if (validNamedIdentifier = isNamedIdentifierStart(scnr, context2)) {
          token = getToken(context2, 4, readNamedIdentifier(scnr));
          skipSpaces(scnr);
          return token;
        }
        if (validListIdentifier = isListIdentifierStart(scnr, context2)) {
          token = getToken(context2, 5, readListIdentifier(scnr));
          skipSpaces(scnr);
          return token;
        }
        if (validLiteral = isLiteralStart(scnr, context2)) {
          token = getToken(context2, 6, readLiteral(scnr));
          skipSpaces(scnr);
          return token;
        }
        if (!validNamedIdentifier && !validListIdentifier && !validLiteral) {
          token = getToken(context2, 12, readInvalidIdentifier(scnr));
          emitError(CompileErrorCodes.INVALID_TOKEN_IN_PLACEHOLDER, currentPosition(), 0, token.value);
          skipSpaces(scnr);
          return token;
        }
        break;
      }
    }
    return token;
  }
  function readTokenInLinked(scnr, context2) {
    const { currentType } = context2;
    let token = null;
    const ch = scnr.currentChar();
    if ((currentType === 7 || currentType === 8 || currentType === 11 || currentType === 9) && (ch === CHAR_LF || ch === CHAR_SP)) {
      emitError(CompileErrorCodes.INVALID_LINKED_FORMAT, currentPosition(), 0);
    }
    switch (ch) {
      case "@":
        scnr.next();
        token = getToken(
          context2,
          7,
          "@"
          /* TokenChars.LinkedAlias */
        );
        context2.inLinked = true;
        return token;
      case ".":
        skipSpaces(scnr);
        scnr.next();
        return getToken(
          context2,
          8,
          "."
          /* TokenChars.LinkedDot */
        );
      case ":":
        skipSpaces(scnr);
        scnr.next();
        return getToken(
          context2,
          9,
          ":"
          /* TokenChars.LinkedDelimiter */
        );
      default:
        if (isPluralStart(scnr)) {
          token = getToken(context2, 1, readPlural(scnr));
          context2.braceNest = 0;
          context2.inLinked = false;
          return token;
        }
        if (isLinkedDotStart(scnr, context2) || isLinkedDelimiterStart(scnr, context2)) {
          skipSpaces(scnr);
          return readTokenInLinked(scnr, context2);
        }
        if (isLinkedModifierStart(scnr, context2)) {
          skipSpaces(scnr);
          return getToken(context2, 11, readLinkedModifier(scnr));
        }
        if (isLinkedReferStart(scnr, context2)) {
          skipSpaces(scnr);
          if (ch === "{") {
            return readTokenInPlaceholder(scnr, context2) || token;
          } else {
            return getToken(context2, 10, readLinkedRefer(scnr));
          }
        }
        if (currentType === 7) {
          emitError(CompileErrorCodes.INVALID_LINKED_FORMAT, currentPosition(), 0);
        }
        context2.braceNest = 0;
        context2.inLinked = false;
        return readToken(scnr, context2);
    }
  }
  function readToken(scnr, context2) {
    let token = {
      type: 13
      /* TokenTypes.EOF */
    };
    if (context2.braceNest > 0) {
      return readTokenInPlaceholder(scnr, context2) || getEndToken(context2);
    }
    if (context2.inLinked) {
      return readTokenInLinked(scnr, context2) || getEndToken(context2);
    }
    const ch = scnr.currentChar();
    switch (ch) {
      case "{":
        return readTokenInPlaceholder(scnr, context2) || getEndToken(context2);
      case "}":
        emitError(CompileErrorCodes.UNBALANCED_CLOSING_BRACE, currentPosition(), 0);
        scnr.next();
        return getToken(
          context2,
          3,
          "}"
          /* TokenChars.BraceRight */
        );
      case "@":
        return readTokenInLinked(scnr, context2) || getEndToken(context2);
      default: {
        if (isPluralStart(scnr)) {
          token = getToken(context2, 1, readPlural(scnr));
          context2.braceNest = 0;
          context2.inLinked = false;
          return token;
        }
        if (isTextStart(scnr)) {
          return getToken(context2, 0, readText(scnr));
        }
        break;
      }
    }
    return token;
  }
  function nextToken() {
    const { currentType, offset, startLoc, endLoc } = _context;
    _context.lastType = currentType;
    _context.lastOffset = offset;
    _context.lastStartLoc = startLoc;
    _context.lastEndLoc = endLoc;
    _context.offset = currentOffset();
    _context.startLoc = currentPosition();
    if (_scnr.currentChar() === EOF) {
      return getToken(
        _context,
        13
        /* TokenTypes.EOF */
      );
    }
    return readToken(_scnr, _context);
  }
  return {
    nextToken,
    currentOffset,
    currentPosition,
    context
  };
}
const ERROR_DOMAIN$2 = "parser";
const KNOWN_ESCAPES = /(?:\\\\|\\'|\\u([0-9a-fA-F]{4})|\\U([0-9a-fA-F]{6}))/g;
function fromEscapeSequence(match, codePoint4, codePoint6) {
  switch (match) {
    case `\\\\`:
      return `\\`;
    // eslint-disable-next-line no-useless-escape
    case `\\'`:
      return `'`;
    default: {
      const codePoint = parseInt(codePoint4 || codePoint6, 16);
      if (codePoint <= 55295 || codePoint >= 57344) {
        return String.fromCodePoint(codePoint);
      }
      return "�";
    }
  }
}
function createParser(options = {}) {
  const location = options.location !== false;
  const { onError } = options;
  function emitError(tokenzer, code, start, offset, ...args) {
    const end = tokenzer.currentPosition();
    end.offset += offset;
    end.column += offset;
    if (onError) {
      const loc = location ? createLocation(start, end) : null;
      const err = createCompileError(code, loc, {
        domain: ERROR_DOMAIN$2,
        args
      });
      onError(err);
    }
  }
  function startNode(type, offset, loc) {
    const node = { type };
    if (location) {
      node.start = offset;
      node.end = offset;
      node.loc = { start: loc, end: loc };
    }
    return node;
  }
  function endNode(node, offset, pos, type) {
    if (location) {
      node.end = offset;
      if (node.loc) {
        node.loc.end = pos;
      }
    }
  }
  function parseText(tokenizer, value) {
    const context = tokenizer.context();
    const node = startNode(3, context.offset, context.startLoc);
    node.value = value;
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseList(tokenizer, index) {
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(5, offset, loc);
    node.index = parseInt(index, 10);
    tokenizer.nextToken();
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseNamed(tokenizer, key) {
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(4, offset, loc);
    node.key = key;
    tokenizer.nextToken();
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseLiteral(tokenizer, value) {
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(9, offset, loc);
    node.value = value.replace(KNOWN_ESCAPES, fromEscapeSequence);
    tokenizer.nextToken();
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseLinkedModifier(tokenizer) {
    const token = tokenizer.nextToken();
    const context = tokenizer.context();
    const { lastOffset: offset, lastStartLoc: loc } = context;
    const node = startNode(8, offset, loc);
    if (token.type !== 11) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_EMPTY_LINKED_MODIFIER, context.lastStartLoc, 0);
      node.value = "";
      endNode(node, offset, loc);
      return {
        nextConsumeToken: token,
        node
      };
    }
    if (token.value == null) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
    }
    node.value = token.value || "";
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return {
      node
    };
  }
  function parseLinkedKey(tokenizer, value) {
    const context = tokenizer.context();
    const node = startNode(7, context.offset, context.startLoc);
    node.value = value;
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseLinked(tokenizer) {
    const context = tokenizer.context();
    const linkedNode = startNode(6, context.offset, context.startLoc);
    let token = tokenizer.nextToken();
    if (token.type === 8) {
      const parsed = parseLinkedModifier(tokenizer);
      linkedNode.modifier = parsed.node;
      token = parsed.nextConsumeToken || tokenizer.nextToken();
    }
    if (token.type !== 9) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
    }
    token = tokenizer.nextToken();
    if (token.type === 2) {
      token = tokenizer.nextToken();
    }
    switch (token.type) {
      case 10:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseLinkedKey(tokenizer, token.value || "");
        break;
      case 4:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseNamed(tokenizer, token.value || "");
        break;
      case 5:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseList(tokenizer, token.value || "");
        break;
      case 6:
        if (token.value == null) {
          emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
        }
        linkedNode.key = parseLiteral(tokenizer, token.value || "");
        break;
      default: {
        emitError(tokenizer, CompileErrorCodes.UNEXPECTED_EMPTY_LINKED_KEY, context.lastStartLoc, 0);
        const nextContext = tokenizer.context();
        const emptyLinkedKeyNode = startNode(7, nextContext.offset, nextContext.startLoc);
        emptyLinkedKeyNode.value = "";
        endNode(emptyLinkedKeyNode, nextContext.offset, nextContext.startLoc);
        linkedNode.key = emptyLinkedKeyNode;
        endNode(linkedNode, nextContext.offset, nextContext.startLoc);
        return {
          nextConsumeToken: token,
          node: linkedNode
        };
      }
    }
    endNode(linkedNode, tokenizer.currentOffset(), tokenizer.currentPosition());
    return {
      node: linkedNode
    };
  }
  function parseMessage(tokenizer) {
    const context = tokenizer.context();
    const startOffset = context.currentType === 1 ? tokenizer.currentOffset() : context.offset;
    const startLoc = context.currentType === 1 ? context.endLoc : context.startLoc;
    const node = startNode(2, startOffset, startLoc);
    node.items = [];
    let nextToken = null;
    do {
      const token = nextToken || tokenizer.nextToken();
      nextToken = null;
      switch (token.type) {
        case 0:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseText(tokenizer, token.value || ""));
          break;
        case 5:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseList(tokenizer, token.value || ""));
          break;
        case 4:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseNamed(tokenizer, token.value || ""));
          break;
        case 6:
          if (token.value == null) {
            emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, getTokenCaption(token));
          }
          node.items.push(parseLiteral(tokenizer, token.value || ""));
          break;
        case 7: {
          const parsed = parseLinked(tokenizer);
          node.items.push(parsed.node);
          nextToken = parsed.nextConsumeToken || null;
          break;
        }
      }
    } while (context.currentType !== 13 && context.currentType !== 1);
    const endOffset = context.currentType === 1 ? context.lastOffset : tokenizer.currentOffset();
    const endLoc = context.currentType === 1 ? context.lastEndLoc : tokenizer.currentPosition();
    endNode(node, endOffset, endLoc);
    return node;
  }
  function parsePlural(tokenizer, offset, loc, msgNode) {
    const context = tokenizer.context();
    let hasEmptyMessage = msgNode.items.length === 0;
    const node = startNode(1, offset, loc);
    node.cases = [];
    node.cases.push(msgNode);
    do {
      const msg = parseMessage(tokenizer);
      if (!hasEmptyMessage) {
        hasEmptyMessage = msg.items.length === 0;
      }
      node.cases.push(msg);
    } while (context.currentType !== 13);
    if (hasEmptyMessage) {
      emitError(tokenizer, CompileErrorCodes.MUST_HAVE_MESSAGES_IN_PLURAL, loc, 0);
    }
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  function parseResource(tokenizer) {
    const context = tokenizer.context();
    const { offset, startLoc } = context;
    const msgNode = parseMessage(tokenizer);
    if (context.currentType === 13) {
      return msgNode;
    } else {
      return parsePlural(tokenizer, offset, startLoc, msgNode);
    }
  }
  function parse2(source) {
    const tokenizer = createTokenizer(source, assign({}, options));
    const context = tokenizer.context();
    const node = startNode(0, context.offset, context.startLoc);
    if (location && node.loc) {
      node.loc.source = source;
    }
    node.body = parseResource(tokenizer);
    if (options.onCacheKey) {
      node.cacheKey = options.onCacheKey(source);
    }
    if (context.currentType !== 13) {
      emitError(tokenizer, CompileErrorCodes.UNEXPECTED_LEXICAL_ANALYSIS, context.lastStartLoc, 0, source[context.offset] || "");
    }
    endNode(node, tokenizer.currentOffset(), tokenizer.currentPosition());
    return node;
  }
  return { parse: parse2 };
}
function getTokenCaption(token) {
  if (token.type === 13) {
    return "EOF";
  }
  const name = (token.value || "").replace(/\r?\n/gu, "\\n");
  return name.length > 10 ? name.slice(0, 9) + "…" : name;
}
function createTransformer(ast, options = {}) {
  const _context = {
    ast,
    helpers: /* @__PURE__ */ new Set()
  };
  const context = () => _context;
  const helper = (name) => {
    _context.helpers.add(name);
    return name;
  };
  return { context, helper };
}
function traverseNodes(nodes, transformer) {
  for (let i = 0; i < nodes.length; i++) {
    traverseNode(nodes[i], transformer);
  }
}
function traverseNode(node, transformer) {
  switch (node.type) {
    case 1:
      traverseNodes(node.cases, transformer);
      transformer.helper(
        "plural"
        /* HelperNameMap.PLURAL */
      );
      break;
    case 2:
      traverseNodes(node.items, transformer);
      break;
    case 6: {
      const linked = node;
      traverseNode(linked.key, transformer);
      transformer.helper(
        "linked"
        /* HelperNameMap.LINKED */
      );
      transformer.helper(
        "type"
        /* HelperNameMap.TYPE */
      );
      break;
    }
    case 5:
      transformer.helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      );
      transformer.helper(
        "list"
        /* HelperNameMap.LIST */
      );
      break;
    case 4:
      transformer.helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      );
      transformer.helper(
        "named"
        /* HelperNameMap.NAMED */
      );
      break;
  }
}
function transform(ast, options = {}) {
  const transformer = createTransformer(ast);
  transformer.helper(
    "normalize"
    /* HelperNameMap.NORMALIZE */
  );
  ast.body && traverseNode(ast.body, transformer);
  const context = transformer.context();
  ast.helpers = Array.from(context.helpers);
}
function optimize(ast) {
  const body = ast.body;
  if (body.type === 2) {
    optimizeMessageNode(body);
  } else {
    body.cases.forEach((c) => optimizeMessageNode(c));
  }
  return ast;
}
function optimizeMessageNode(message) {
  if (message.items.length === 1) {
    const item = message.items[0];
    if (item.type === 3 || item.type === 9) {
      message.static = item.value;
      delete item.value;
    }
  } else {
    const values = [];
    for (let i = 0; i < message.items.length; i++) {
      const item = message.items[i];
      if (!(item.type === 3 || item.type === 9)) {
        break;
      }
      if (item.value == null) {
        break;
      }
      values.push(item.value);
    }
    if (values.length === message.items.length) {
      message.static = join(values);
      for (let i = 0; i < message.items.length; i++) {
        const item = message.items[i];
        if (item.type === 3 || item.type === 9) {
          delete item.value;
        }
      }
    }
  }
}
function minify(node) {
  node.t = node.type;
  switch (node.type) {
    case 0: {
      const resource = node;
      minify(resource.body);
      resource.b = resource.body;
      delete resource.body;
      break;
    }
    case 1: {
      const plural = node;
      const cases = plural.cases;
      for (let i = 0; i < cases.length; i++) {
        minify(cases[i]);
      }
      plural.c = cases;
      delete plural.cases;
      break;
    }
    case 2: {
      const message = node;
      const items = message.items;
      for (let i = 0; i < items.length; i++) {
        minify(items[i]);
      }
      message.i = items;
      delete message.items;
      if (message.static) {
        message.s = message.static;
        delete message.static;
      }
      break;
    }
    case 3:
    case 9:
    case 8:
    case 7: {
      const valueNode = node;
      if (valueNode.value) {
        valueNode.v = valueNode.value;
        delete valueNode.value;
      }
      break;
    }
    case 6: {
      const linked = node;
      minify(linked.key);
      linked.k = linked.key;
      delete linked.key;
      if (linked.modifier) {
        minify(linked.modifier);
        linked.m = linked.modifier;
        delete linked.modifier;
      }
      break;
    }
    case 5: {
      const list = node;
      list.i = list.index;
      delete list.index;
      break;
    }
    case 4: {
      const named = node;
      named.k = named.key;
      delete named.key;
      break;
    }
  }
  delete node.type;
}
function createCodeGenerator(ast, options) {
  const { filename, breakLineCode, needIndent: _needIndent } = options;
  const location = options.location !== false;
  const _context = {
    filename,
    code: "",
    column: 1,
    line: 1,
    offset: 0,
    map: void 0,
    breakLineCode,
    needIndent: _needIndent,
    indentLevel: 0
  };
  if (location && ast.loc) {
    _context.source = ast.loc.source;
  }
  const context = () => _context;
  function push(code, node) {
    _context.code += code;
  }
  function _newline(n, withBreakLine = true) {
    const _breakLineCode = withBreakLine ? breakLineCode : "";
    push(_needIndent ? _breakLineCode + `  `.repeat(n) : _breakLineCode);
  }
  function indent(withNewLine = true) {
    const level = ++_context.indentLevel;
    withNewLine && _newline(level);
  }
  function deindent(withNewLine = true) {
    const level = --_context.indentLevel;
    withNewLine && _newline(level);
  }
  function newline() {
    _newline(_context.indentLevel);
  }
  const helper = (key) => `_${key}`;
  const needIndent = () => _context.needIndent;
  return {
    context,
    push,
    indent,
    deindent,
    newline,
    helper,
    needIndent
  };
}
function generateLinkedNode(generator, node) {
  const { helper } = generator;
  generator.push(`${helper(
    "linked"
    /* HelperNameMap.LINKED */
  )}(`);
  generateNode(generator, node.key);
  if (node.modifier) {
    generator.push(`, `);
    generateNode(generator, node.modifier);
    generator.push(`, _type`);
  } else {
    generator.push(`, undefined, _type`);
  }
  generator.push(`)`);
}
function generateMessageNode(generator, node) {
  const { helper, needIndent } = generator;
  generator.push(`${helper(
    "normalize"
    /* HelperNameMap.NORMALIZE */
  )}([`);
  generator.indent(needIndent());
  const length = node.items.length;
  for (let i = 0; i < length; i++) {
    generateNode(generator, node.items[i]);
    if (i === length - 1) {
      break;
    }
    generator.push(", ");
  }
  generator.deindent(needIndent());
  generator.push("])");
}
function generatePluralNode(generator, node) {
  const { helper, needIndent } = generator;
  if (node.cases.length > 1) {
    generator.push(`${helper(
      "plural"
      /* HelperNameMap.PLURAL */
    )}([`);
    generator.indent(needIndent());
    const length = node.cases.length;
    for (let i = 0; i < length; i++) {
      generateNode(generator, node.cases[i]);
      if (i === length - 1) {
        break;
      }
      generator.push(", ");
    }
    generator.deindent(needIndent());
    generator.push(`])`);
  }
}
function generateResource(generator, node) {
  if (node.body) {
    generateNode(generator, node.body);
  } else {
    generator.push("null");
  }
}
function generateNode(generator, node) {
  const { helper } = generator;
  switch (node.type) {
    case 0:
      generateResource(generator, node);
      break;
    case 1:
      generatePluralNode(generator, node);
      break;
    case 2:
      generateMessageNode(generator, node);
      break;
    case 6:
      generateLinkedNode(generator, node);
      break;
    case 8:
      generator.push(JSON.stringify(node.value), node);
      break;
    case 7:
      generator.push(JSON.stringify(node.value), node);
      break;
    case 5:
      generator.push(`${helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      )}(${helper(
        "list"
        /* HelperNameMap.LIST */
      )}(${node.index}))`, node);
      break;
    case 4:
      generator.push(`${helper(
        "interpolate"
        /* HelperNameMap.INTERPOLATE */
      )}(${helper(
        "named"
        /* HelperNameMap.NAMED */
      )}(${JSON.stringify(node.key)}))`, node);
      break;
    case 9:
      generator.push(JSON.stringify(node.value), node);
      break;
    case 3:
      generator.push(JSON.stringify(node.value), node);
      break;
  }
}
const generate = (ast, options = {}) => {
  const mode = isString(options.mode) ? options.mode : "normal";
  const filename = isString(options.filename) ? options.filename : "message.intl";
  !!options.sourceMap;
  const breakLineCode = options.breakLineCode != null ? options.breakLineCode : mode === "arrow" ? ";" : "\n";
  const needIndent = options.needIndent ? options.needIndent : mode !== "arrow";
  const helpers = ast.helpers || [];
  const generator = createCodeGenerator(ast, {
    filename,
    breakLineCode,
    needIndent
  });
  generator.push(mode === "normal" ? `function __msg__ (ctx) {` : `(ctx) => {`);
  generator.indent(needIndent);
  if (helpers.length > 0) {
    generator.push(`const { ${join(helpers.map((s) => `${s}: _${s}`), ", ")} } = ctx`);
    generator.newline();
  }
  generator.push(`return `);
  generateNode(generator, ast);
  generator.deindent(needIndent);
  generator.push(`}`);
  delete ast.helpers;
  const { code, map } = generator.context();
  return {
    ast,
    code,
    map: map ? map.toJSON() : void 0
    // eslint-disable-line @typescript-eslint/no-explicit-any
  };
};
function baseCompile$1(source, options = {}) {
  const assignedOptions = assign({}, options);
  const jit = !!assignedOptions.jit;
  const enalbeMinify = !!assignedOptions.minify;
  const enambeOptimize = assignedOptions.optimize == null ? true : assignedOptions.optimize;
  const parser = createParser(assignedOptions);
  const ast = parser.parse(source);
  if (!jit) {
    transform(ast, assignedOptions);
    return generate(ast, assignedOptions);
  } else {
    enambeOptimize && optimize(ast);
    enalbeMinify && minify(ast);
    return { ast, code: "" };
  }
}
function isMessageAST(val) {
  return isObject(val) && resolveType(val) === 0 && (hasOwn(val, "b") || hasOwn(val, "body"));
}
const PROPS_BODY = ["b", "body"];
function resolveBody(node) {
  return resolveProps(node, PROPS_BODY);
}
const PROPS_CASES = ["c", "cases"];
function resolveCases(node) {
  return resolveProps(node, PROPS_CASES, []);
}
const PROPS_STATIC = ["s", "static"];
function resolveStatic(node) {
  return resolveProps(node, PROPS_STATIC);
}
const PROPS_ITEMS = ["i", "items"];
function resolveItems(node) {
  return resolveProps(node, PROPS_ITEMS, []);
}
const PROPS_TYPE = ["t", "type"];
function resolveType(node) {
  return resolveProps(node, PROPS_TYPE);
}
const PROPS_VALUE = ["v", "value"];
function resolveValue$1(node, type) {
  const resolved = resolveProps(node, PROPS_VALUE);
  if (resolved != null) {
    return resolved;
  } else {
    throw createUnhandleNodeError(type);
  }
}
const PROPS_MODIFIER = ["m", "modifier"];
function resolveLinkedModifier(node) {
  return resolveProps(node, PROPS_MODIFIER);
}
const PROPS_KEY = ["k", "key"];
function resolveLinkedKey(node) {
  const resolved = resolveProps(node, PROPS_KEY);
  if (resolved) {
    return resolved;
  } else {
    throw createUnhandleNodeError(
      6
      /* NodeTypes.Linked */
    );
  }
}
function resolveProps(node, props, defaultValue) {
  for (let i = 0; i < props.length; i++) {
    const prop = props[i];
    if (hasOwn(node, prop) && node[prop] != null) {
      return node[prop];
    }
  }
  return defaultValue;
}
const AST_NODE_PROPS_KEYS = [
  ...PROPS_BODY,
  ...PROPS_CASES,
  ...PROPS_STATIC,
  ...PROPS_ITEMS,
  ...PROPS_KEY,
  ...PROPS_MODIFIER,
  ...PROPS_VALUE,
  ...PROPS_TYPE
];
function createUnhandleNodeError(type) {
  return new Error(`unhandled node type: ${type}`);
}
function format(ast) {
  const msg = (ctx) => formatParts(ctx, ast);
  return msg;
}
function formatParts(ctx, ast) {
  const body = resolveBody(ast);
  if (body == null) {
    throw createUnhandleNodeError(
      0
      /* NodeTypes.Resource */
    );
  }
  const type = resolveType(body);
  if (type === 1) {
    const plural = body;
    const cases = resolveCases(plural);
    return ctx.plural(cases.reduce((messages, c) => [
      ...messages,
      formatMessageParts(ctx, c)
    ], []));
  } else {
    return formatMessageParts(ctx, body);
  }
}
function formatMessageParts(ctx, node) {
  const static_ = resolveStatic(node);
  if (static_ != null) {
    return ctx.type === "text" ? static_ : ctx.normalize([static_]);
  } else {
    const messages = resolveItems(node).reduce((acm, c) => [...acm, formatMessagePart(ctx, c)], []);
    return ctx.normalize(messages);
  }
}
function formatMessagePart(ctx, node) {
  const type = resolveType(node);
  switch (type) {
    case 3: {
      return resolveValue$1(node, type);
    }
    case 9: {
      return resolveValue$1(node, type);
    }
    case 4: {
      const named = node;
      if (hasOwn(named, "k") && named.k) {
        return ctx.interpolate(ctx.named(named.k));
      }
      if (hasOwn(named, "key") && named.key) {
        return ctx.interpolate(ctx.named(named.key));
      }
      throw createUnhandleNodeError(type);
    }
    case 5: {
      const list = node;
      if (hasOwn(list, "i") && isNumber(list.i)) {
        return ctx.interpolate(ctx.list(list.i));
      }
      if (hasOwn(list, "index") && isNumber(list.index)) {
        return ctx.interpolate(ctx.list(list.index));
      }
      throw createUnhandleNodeError(type);
    }
    case 6: {
      const linked = node;
      const modifier = resolveLinkedModifier(linked);
      const key = resolveLinkedKey(linked);
      return ctx.linked(formatMessagePart(ctx, key), modifier ? formatMessagePart(ctx, modifier) : void 0, ctx.type);
    }
    case 7: {
      return resolveValue$1(node, type);
    }
    case 8: {
      return resolveValue$1(node, type);
    }
    default:
      throw new Error(`unhandled node on format message part: ${type}`);
  }
}
const defaultOnCacheKey = (message) => message;
let compileCache = create();
function baseCompile(message, options = {}) {
  let detectError = false;
  const onError = options.onError || defaultOnError;
  options.onError = (err) => {
    detectError = true;
    onError(err);
  };
  return { ...baseCompile$1(message, options), detectError };
}
// @__NO_SIDE_EFFECTS__
function compile(message, context) {
  if (isString(message)) {
    isBoolean(context.warnHtmlMessage) ? context.warnHtmlMessage : true;
    const onCacheKey = context.onCacheKey || defaultOnCacheKey;
    const cacheKey = onCacheKey(message);
    const cached = compileCache[cacheKey];
    if (cached) {
      return cached;
    }
    const { ast, detectError } = baseCompile(message, {
      ...context,
      location: "production" !== "production",
      jit: true
    });
    const msg = format(ast);
    return !detectError ? compileCache[cacheKey] = msg : msg;
  } else {
    const cacheKey = message.cacheKey;
    if (cacheKey) {
      const cached = compileCache[cacheKey];
      if (cached) {
        return cached;
      }
      return compileCache[cacheKey] = format(message);
    } else {
      return format(message);
    }
  }
}
const CoreErrorCodes = {
  INVALID_ARGUMENT: COMPILE_ERROR_CODES_EXTEND_POINT,
  // 17
  INVALID_DATE_ARGUMENT: 18,
  INVALID_ISO_DATE_ARGUMENT: 19,
  NOT_SUPPORT_LOCALE_PROMISE_VALUE: 21,
  NOT_SUPPORT_LOCALE_ASYNC_FUNCTION: 22,
  NOT_SUPPORT_LOCALE_TYPE: 23
};
const CORE_ERROR_CODES_EXTEND_POINT = 24;
function createCoreError(code) {
  return createCompileError(code, null, void 0);
}
function getLocale(context, options) {
  return options.locale != null ? resolveLocale(options.locale) : resolveLocale(context.locale);
}
let _resolveLocale;
function resolveLocale(locale) {
  if (isString(locale)) {
    return locale;
  } else {
    if (isFunction(locale)) {
      if (locale.resolvedOnce && _resolveLocale != null) {
        return _resolveLocale;
      } else if (locale.constructor.name === "Function") {
        const resolve = locale();
        if (isPromise(resolve)) {
          throw createCoreError(CoreErrorCodes.NOT_SUPPORT_LOCALE_PROMISE_VALUE);
        }
        return _resolveLocale = resolve;
      } else {
        throw createCoreError(CoreErrorCodes.NOT_SUPPORT_LOCALE_ASYNC_FUNCTION);
      }
    } else {
      throw createCoreError(CoreErrorCodes.NOT_SUPPORT_LOCALE_TYPE);
    }
  }
}
function fallbackWithSimple(ctx, fallback, start) {
  return [.../* @__PURE__ */ new Set([
    start,
    ...isArray(fallback) ? fallback : isObject(fallback) ? Object.keys(fallback) : isString(fallback) ? [fallback] : [start]
  ])];
}
function fallbackWithLocaleChain(ctx, fallback, start) {
  const startLocale = isString(start) ? start : DEFAULT_LOCALE;
  const context = ctx;
  if (!context.__localeChainCache) {
    context.__localeChainCache = /* @__PURE__ */ new Map();
  }
  let chain = context.__localeChainCache.get(startLocale);
  if (!chain) {
    chain = [];
    let block = [start];
    while (isArray(block)) {
      block = appendBlockToChain(chain, block, fallback);
    }
    const defaults = isArray(fallback) || !isPlainObject(fallback) ? fallback : fallback["default"] ? fallback["default"] : null;
    block = isString(defaults) ? [defaults] : defaults;
    if (isArray(block)) {
      appendBlockToChain(chain, block, false);
    }
    context.__localeChainCache.set(startLocale, chain);
  }
  return chain;
}
function appendBlockToChain(chain, block, blocks) {
  let follow = true;
  for (let i = 0; i < block.length && isBoolean(follow); i++) {
    const locale = block[i];
    if (isString(locale)) {
      follow = appendLocaleToChain(chain, block[i], blocks);
    }
  }
  return follow;
}
function appendLocaleToChain(chain, locale, blocks) {
  let follow;
  const tokens = locale.split("-");
  do {
    const target = tokens.join("-");
    follow = appendItemToChain(chain, target, blocks);
    tokens.splice(-1, 1);
  } while (tokens.length && follow === true);
  return follow;
}
function appendItemToChain(chain, target, blocks) {
  let follow = false;
  if (!chain.includes(target)) {
    follow = true;
    if (target) {
      follow = target[target.length - 1] !== "!";
      const locale = target.replace(/!/g, "");
      chain.push(locale);
      if ((isArray(blocks) || isPlainObject(blocks)) && blocks[locale]) {
        follow = blocks[locale];
      }
    }
  }
  return follow;
}
const pathStateMachine = [];
pathStateMachine[
  0
  /* States.BEFORE_PATH */
] = {
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    0
    /* States.BEFORE_PATH */
  ],
  [
    "i"
    /* PathCharTypes.IDENT */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4
    /* States.IN_SUB_PATH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: [
    7
    /* States.AFTER_PATH */
  ]
};
pathStateMachine[
  1
  /* States.IN_PATH */
] = {
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    1
    /* States.IN_PATH */
  ],
  [
    "."
    /* PathCharTypes.DOT */
  ]: [
    2
    /* States.BEFORE_IDENT */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4
    /* States.IN_SUB_PATH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: [
    7
    /* States.AFTER_PATH */
  ]
};
pathStateMachine[
  2
  /* States.BEFORE_IDENT */
] = {
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    2
    /* States.BEFORE_IDENT */
  ],
  [
    "i"
    /* PathCharTypes.IDENT */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "0"
    /* PathCharTypes.ZERO */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ]
};
pathStateMachine[
  3
  /* States.IN_IDENT */
] = {
  [
    "i"
    /* PathCharTypes.IDENT */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "0"
    /* PathCharTypes.ZERO */
  ]: [
    3,
    0
    /* Actions.APPEND */
  ],
  [
    "w"
    /* PathCharTypes.WORKSPACE */
  ]: [
    1,
    1
    /* Actions.PUSH */
  ],
  [
    "."
    /* PathCharTypes.DOT */
  ]: [
    2,
    1
    /* Actions.PUSH */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4,
    1
    /* Actions.PUSH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: [
    7,
    1
    /* Actions.PUSH */
  ]
};
pathStateMachine[
  4
  /* States.IN_SUB_PATH */
] = {
  [
    "'"
    /* PathCharTypes.SINGLE_QUOTE */
  ]: [
    5,
    0
    /* Actions.APPEND */
  ],
  [
    '"'
    /* PathCharTypes.DOUBLE_QUOTE */
  ]: [
    6,
    0
    /* Actions.APPEND */
  ],
  [
    "["
    /* PathCharTypes.LEFT_BRACKET */
  ]: [
    4,
    2
    /* Actions.INC_SUB_PATH_DEPTH */
  ],
  [
    "]"
    /* PathCharTypes.RIGHT_BRACKET */
  ]: [
    1,
    3
    /* Actions.PUSH_SUB_PATH */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: 8,
  [
    "l"
    /* PathCharTypes.ELSE */
  ]: [
    4,
    0
    /* Actions.APPEND */
  ]
};
pathStateMachine[
  5
  /* States.IN_SINGLE_QUOTE */
] = {
  [
    "'"
    /* PathCharTypes.SINGLE_QUOTE */
  ]: [
    4,
    0
    /* Actions.APPEND */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: 8,
  [
    "l"
    /* PathCharTypes.ELSE */
  ]: [
    5,
    0
    /* Actions.APPEND */
  ]
};
pathStateMachine[
  6
  /* States.IN_DOUBLE_QUOTE */
] = {
  [
    '"'
    /* PathCharTypes.DOUBLE_QUOTE */
  ]: [
    4,
    0
    /* Actions.APPEND */
  ],
  [
    "o"
    /* PathCharTypes.END_OF_FAIL */
  ]: 8,
  [
    "l"
    /* PathCharTypes.ELSE */
  ]: [
    6,
    0
    /* Actions.APPEND */
  ]
};
const literalValueRE = /^\s?(?:true|false|-?[\d.]+|'[^']*'|"[^"]*")\s?$/;
function isLiteral(exp) {
  return literalValueRE.test(exp);
}
function stripQuotes(str) {
  const a = str.charCodeAt(0);
  const b = str.charCodeAt(str.length - 1);
  return a === b && (a === 34 || a === 39) ? str.slice(1, -1) : str;
}
function getPathCharType(ch) {
  if (ch === void 0 || ch === null) {
    return "o";
  }
  const code = ch.charCodeAt(0);
  switch (code) {
    case 91:
    // [
    case 93:
    // ]
    case 46:
    // .
    case 34:
    // "
    case 39:
      return ch;
    case 95:
    // _
    case 36:
    // $
    case 45:
      return "i";
    case 9:
    // Tab (HT)
    case 10:
    // Newline (LF)
    case 13:
    // Return (CR)
    case 160:
    // No-break space (NBSP)
    case 65279:
    // Byte Order Mark (BOM)
    case 8232:
    // Line Separator (LS)
    case 8233:
      return "w";
  }
  return "i";
}
function formatSubPath(path) {
  const trimmed = path.trim();
  if (path.charAt(0) === "0" && isNaN(parseInt(path))) {
    return false;
  }
  return isLiteral(trimmed) ? stripQuotes(trimmed) : "*" + trimmed;
}
function parse(path) {
  const keys = [];
  let index = -1;
  let mode = 0;
  let subPathDepth = 0;
  let c;
  let key;
  let newChar;
  let type;
  let transition;
  let action;
  let typeMap;
  const actions = [];
  actions[
    0
    /* Actions.APPEND */
  ] = () => {
    if (key === void 0) {
      key = newChar;
    } else {
      key += newChar;
    }
  };
  actions[
    1
    /* Actions.PUSH */
  ] = () => {
    if (key !== void 0) {
      keys.push(key);
      key = void 0;
    }
  };
  actions[
    2
    /* Actions.INC_SUB_PATH_DEPTH */
  ] = () => {
    actions[
      0
      /* Actions.APPEND */
    ]();
    subPathDepth++;
  };
  actions[
    3
    /* Actions.PUSH_SUB_PATH */
  ] = () => {
    if (subPathDepth > 0) {
      subPathDepth--;
      mode = 4;
      actions[
        0
        /* Actions.APPEND */
      ]();
    } else {
      subPathDepth = 0;
      if (key === void 0) {
        return false;
      }
      key = formatSubPath(key);
      if (key === false) {
        return false;
      } else {
        actions[
          1
          /* Actions.PUSH */
        ]();
      }
    }
  };
  function maybeUnescapeQuote() {
    const nextChar = path[index + 1];
    if (mode === 5 && nextChar === "'" || mode === 6 && nextChar === '"') {
      index++;
      newChar = "\\" + nextChar;
      actions[
        0
        /* Actions.APPEND */
      ]();
      return true;
    }
  }
  while (mode !== null) {
    index++;
    c = path[index];
    if (c === "\\" && maybeUnescapeQuote()) {
      continue;
    }
    type = getPathCharType(c);
    typeMap = pathStateMachine[mode];
    transition = typeMap[type] || typeMap[
      "l"
      /* PathCharTypes.ELSE */
    ] || 8;
    if (transition === 8) {
      return;
    }
    mode = transition[0];
    if (transition[1] !== void 0) {
      action = actions[transition[1]];
      if (action) {
        newChar = c;
        if (action() === false) {
          return;
        }
      }
    }
    if (mode === 7) {
      return keys;
    }
  }
}
const cache = /* @__PURE__ */ new Map();
function resolveWithKeyValue(obj, path) {
  return isObject(obj) ? obj[path] : null;
}
function resolveValue(obj, path) {
  if (!isObject(obj)) {
    return null;
  }
  let hit = cache.get(path);
  if (!hit) {
    hit = parse(path);
    if (hit) {
      cache.set(path, hit);
    }
  }
  if (!hit) {
    return null;
  }
  const len = hit.length;
  let last = obj;
  let i = 0;
  while (i < len) {
    const key = hit[i];
    if (AST_NODE_PROPS_KEYS.includes(key) && isMessageAST(last)) {
      return null;
    }
    const val = last[key];
    if (val === void 0) {
      return null;
    }
    if (isFunction(last)) {
      return null;
    }
    last = val;
    i++;
  }
  return last;
}
const VERSION$1 = "11.2.8";
const NOT_REOSLVED = -1;
const DEFAULT_LOCALE = "en-US";
const MISSING_RESOLVE_VALUE = "";
const capitalize = (str) => `${str.charAt(0).toLocaleUpperCase()}${str.substr(1)}`;
function getDefaultLinkedModifiers() {
  return {
    upper: (val, type) => {
      return type === "text" && isString(val) ? val.toUpperCase() : type === "vnode" && isObject(val) && "__v_isVNode" in val ? val.children.toUpperCase() : val;
    },
    lower: (val, type) => {
      return type === "text" && isString(val) ? val.toLowerCase() : type === "vnode" && isObject(val) && "__v_isVNode" in val ? val.children.toLowerCase() : val;
    },
    capitalize: (val, type) => {
      return type === "text" && isString(val) ? capitalize(val) : type === "vnode" && isObject(val) && "__v_isVNode" in val ? capitalize(val.children) : val;
    }
  };
}
let _compiler;
function registerMessageCompiler(compiler) {
  _compiler = compiler;
}
let _resolver;
function registerMessageResolver(resolver) {
  _resolver = resolver;
}
let _fallbacker;
function registerLocaleFallbacker(fallbacker) {
  _fallbacker = fallbacker;
}
const setAdditionalMeta = /* @__NO_SIDE_EFFECTS__ */ (meta) => {
};
let _fallbackContext = null;
const setFallbackContext = (context) => {
  _fallbackContext = context;
};
const getFallbackContext = () => _fallbackContext;
let _cid = 0;
function createCoreContext(options = {}) {
  const onWarn = isFunction(options.onWarn) ? options.onWarn : warn;
  const version = isString(options.version) ? options.version : VERSION$1;
  const locale = isString(options.locale) || isFunction(options.locale) ? options.locale : DEFAULT_LOCALE;
  const _locale = isFunction(locale) ? DEFAULT_LOCALE : locale;
  const fallbackLocale = isArray(options.fallbackLocale) || isPlainObject(options.fallbackLocale) || isString(options.fallbackLocale) || options.fallbackLocale === false ? options.fallbackLocale : _locale;
  const messages = isPlainObject(options.messages) ? options.messages : createResources(_locale);
  const datetimeFormats = isPlainObject(options.datetimeFormats) ? options.datetimeFormats : createResources(_locale);
  const numberFormats = isPlainObject(options.numberFormats) ? options.numberFormats : createResources(_locale);
  const modifiers = assign(create(), options.modifiers, getDefaultLinkedModifiers());
  const pluralRules = options.pluralRules || create();
  const missing = isFunction(options.missing) ? options.missing : null;
  const missingWarn = isBoolean(options.missingWarn) || isRegExp(options.missingWarn) ? options.missingWarn : true;
  const fallbackWarn = isBoolean(options.fallbackWarn) || isRegExp(options.fallbackWarn) ? options.fallbackWarn : true;
  const fallbackFormat = !!options.fallbackFormat;
  const unresolving = !!options.unresolving;
  const postTranslation = isFunction(options.postTranslation) ? options.postTranslation : null;
  const processor = isPlainObject(options.processor) ? options.processor : null;
  const warnHtmlMessage = isBoolean(options.warnHtmlMessage) ? options.warnHtmlMessage : true;
  const escapeParameter = !!options.escapeParameter;
  const messageCompiler = isFunction(options.messageCompiler) ? options.messageCompiler : _compiler;
  const messageResolver = isFunction(options.messageResolver) ? options.messageResolver : _resolver || resolveWithKeyValue;
  const localeFallbacker = isFunction(options.localeFallbacker) ? options.localeFallbacker : _fallbacker || fallbackWithSimple;
  const fallbackContext = isObject(options.fallbackContext) ? options.fallbackContext : void 0;
  const internalOptions = options;
  const __datetimeFormatters = isObject(internalOptions.__datetimeFormatters) ? internalOptions.__datetimeFormatters : /* @__PURE__ */ new Map();
  const __numberFormatters = isObject(internalOptions.__numberFormatters) ? internalOptions.__numberFormatters : /* @__PURE__ */ new Map();
  const __meta = isObject(internalOptions.__meta) ? internalOptions.__meta : {};
  _cid++;
  const context = {
    version,
    cid: _cid,
    locale,
    fallbackLocale,
    messages,
    modifiers,
    pluralRules,
    missing,
    missingWarn,
    fallbackWarn,
    fallbackFormat,
    unresolving,
    postTranslation,
    processor,
    warnHtmlMessage,
    escapeParameter,
    messageCompiler,
    messageResolver,
    localeFallbacker,
    fallbackContext,
    onWarn,
    __meta
  };
  {
    context.datetimeFormats = datetimeFormats;
    context.numberFormats = numberFormats;
    context.__datetimeFormatters = __datetimeFormatters;
    context.__numberFormatters = __numberFormatters;
  }
  return context;
}
const createResources = (locale) => ({ [locale]: create() });
function handleMissing(context, key, locale, missingWarn, type) {
  const { missing, onWarn } = context;
  if (missing !== null) {
    const ret = missing(context, locale, key, type);
    return isString(ret) ? ret : key;
  } else {
    return key;
  }
}
function updateFallbackLocale(ctx, locale, fallback) {
  const context = ctx;
  context.__localeChainCache = /* @__PURE__ */ new Map();
  ctx.localeFallbacker(ctx, fallback, locale);
}
function isAlmostSameLocale(locale, compareLocale) {
  if (locale === compareLocale)
    return false;
  return locale.split("-")[0] === compareLocale.split("-")[0];
}
function isImplicitFallback(targetLocale, locales) {
  const index = locales.indexOf(targetLocale);
  if (index === -1) {
    return false;
  }
  for (let i = index + 1; i < locales.length; i++) {
    if (isAlmostSameLocale(targetLocale, locales[i])) {
      return true;
    }
  }
  return false;
}
function datetime(context, ...args) {
  const { datetimeFormats, unresolving, fallbackLocale, onWarn, localeFallbacker } = context;
  const { __datetimeFormatters } = context;
  const [key, value, options, overrides] = parseDateTimeArgs(...args);
  const missingWarn = isBoolean(options.missingWarn) ? options.missingWarn : context.missingWarn;
  isBoolean(options.fallbackWarn) ? options.fallbackWarn : context.fallbackWarn;
  const part = !!options.part;
  const locale = getLocale(context, options);
  const locales = localeFallbacker(
    context,
    // eslint-disable-line @typescript-eslint/no-explicit-any
    fallbackLocale,
    locale
  );
  if (!isString(key) || key === "") {
    return new Intl.DateTimeFormat(locale, overrides).format(value);
  }
  let datetimeFormat = {};
  let targetLocale;
  let format2 = null;
  const type = "datetime format";
  for (let i = 0; i < locales.length; i++) {
    targetLocale = locales[i];
    datetimeFormat = datetimeFormats[targetLocale] || {};
    format2 = datetimeFormat[key];
    if (isPlainObject(format2))
      break;
    handleMissing(context, key, targetLocale, missingWarn, type);
  }
  if (!isPlainObject(format2) || !isString(targetLocale)) {
    return unresolving ? NOT_REOSLVED : key;
  }
  let id = `${targetLocale}__${key}`;
  if (!isEmptyObject(overrides)) {
    id = `${id}__${JSON.stringify(overrides)}`;
  }
  let formatter = __datetimeFormatters.get(id);
  if (!formatter) {
    formatter = new Intl.DateTimeFormat(targetLocale, assign({}, format2, overrides));
    __datetimeFormatters.set(id, formatter);
  }
  return !part ? formatter.format(value) : formatter.formatToParts(value);
}
const DATETIME_FORMAT_OPTIONS_KEYS = [
  "localeMatcher",
  "weekday",
  "era",
  "year",
  "month",
  "day",
  "hour",
  "minute",
  "second",
  "timeZoneName",
  "formatMatcher",
  "hour12",
  "timeZone",
  "dateStyle",
  "timeStyle",
  "calendar",
  "dayPeriod",
  "numberingSystem",
  "hourCycle",
  "fractionalSecondDigits"
];
function parseDateTimeArgs(...args) {
  const [arg1, arg2, arg3, arg4] = args;
  const options = create();
  let overrides = create();
  let value;
  if (isString(arg1)) {
    const matches = arg1.match(/(\d{4}-\d{2}-\d{2})(T|\s)?(.*)/);
    if (!matches) {
      throw createCoreError(CoreErrorCodes.INVALID_ISO_DATE_ARGUMENT);
    }
    const dateTime = matches[3] ? matches[3].trim().startsWith("T") ? `${matches[1].trim()}${matches[3].trim()}` : `${matches[1].trim()}T${matches[3].trim()}` : matches[1].trim();
    value = new Date(dateTime);
    try {
      value.toISOString();
    } catch {
      throw createCoreError(CoreErrorCodes.INVALID_ISO_DATE_ARGUMENT);
    }
  } else if (isDate(arg1)) {
    if (isNaN(arg1.getTime())) {
      throw createCoreError(CoreErrorCodes.INVALID_DATE_ARGUMENT);
    }
    value = arg1;
  } else if (isNumber(arg1)) {
    value = arg1;
  } else {
    throw createCoreError(CoreErrorCodes.INVALID_ARGUMENT);
  }
  if (isString(arg2)) {
    options.key = arg2;
  } else if (isPlainObject(arg2)) {
    Object.keys(arg2).forEach((key) => {
      if (DATETIME_FORMAT_OPTIONS_KEYS.includes(key)) {
        overrides[key] = arg2[key];
      } else {
        options[key] = arg2[key];
      }
    });
  }
  if (isString(arg3)) {
    options.locale = arg3;
  } else if (isPlainObject(arg3)) {
    overrides = arg3;
  }
  if (isPlainObject(arg4)) {
    overrides = arg4;
  }
  return [options.key || "", value, options, overrides];
}
function clearDateTimeFormat(ctx, locale, format2) {
  const context = ctx;
  for (const key in format2) {
    const id = `${locale}__${key}`;
    if (!context.__datetimeFormatters.has(id)) {
      continue;
    }
    context.__datetimeFormatters.delete(id);
  }
}
function number(context, ...args) {
  const { numberFormats, unresolving, fallbackLocale, onWarn, localeFallbacker } = context;
  const { __numberFormatters } = context;
  const [key, value, options, overrides] = parseNumberArgs(...args);
  const missingWarn = isBoolean(options.missingWarn) ? options.missingWarn : context.missingWarn;
  isBoolean(options.fallbackWarn) ? options.fallbackWarn : context.fallbackWarn;
  const part = !!options.part;
  const locale = getLocale(context, options);
  const locales = localeFallbacker(
    context,
    // eslint-disable-line @typescript-eslint/no-explicit-any
    fallbackLocale,
    locale
  );
  if (!isString(key) || key === "") {
    return new Intl.NumberFormat(locale, overrides).format(value);
  }
  let numberFormat = {};
  let targetLocale;
  let format2 = null;
  const type = "number format";
  for (let i = 0; i < locales.length; i++) {
    targetLocale = locales[i];
    numberFormat = numberFormats[targetLocale] || {};
    format2 = numberFormat[key];
    if (isPlainObject(format2))
      break;
    handleMissing(context, key, targetLocale, missingWarn, type);
  }
  if (!isPlainObject(format2) || !isString(targetLocale)) {
    return unresolving ? NOT_REOSLVED : key;
  }
  let id = `${targetLocale}__${key}`;
  if (!isEmptyObject(overrides)) {
    id = `${id}__${JSON.stringify(overrides)}`;
  }
  let formatter = __numberFormatters.get(id);
  if (!formatter) {
    formatter = new Intl.NumberFormat(targetLocale, assign({}, format2, overrides));
    __numberFormatters.set(id, formatter);
  }
  return !part ? formatter.format(value) : formatter.formatToParts(value);
}
const NUMBER_FORMAT_OPTIONS_KEYS = [
  "localeMatcher",
  "style",
  "currency",
  "currencyDisplay",
  "currencySign",
  "useGrouping",
  "minimumIntegerDigits",
  "minimumFractionDigits",
  "maximumFractionDigits",
  "minimumSignificantDigits",
  "maximumSignificantDigits",
  "compactDisplay",
  "notation",
  "signDisplay",
  "unit",
  "unitDisplay",
  "roundingMode",
  "roundingPriority",
  "roundingIncrement",
  "trailingZeroDisplay"
];
function parseNumberArgs(...args) {
  const [arg1, arg2, arg3, arg4] = args;
  const options = create();
  let overrides = create();
  if (!isNumber(arg1)) {
    throw createCoreError(CoreErrorCodes.INVALID_ARGUMENT);
  }
  const value = arg1;
  if (isString(arg2)) {
    options.key = arg2;
  } else if (isPlainObject(arg2)) {
    Object.keys(arg2).forEach((key) => {
      if (NUMBER_FORMAT_OPTIONS_KEYS.includes(key)) {
        overrides[key] = arg2[key];
      } else {
        options[key] = arg2[key];
      }
    });
  }
  if (isString(arg3)) {
    options.locale = arg3;
  } else if (isPlainObject(arg3)) {
    overrides = arg3;
  }
  if (isPlainObject(arg4)) {
    overrides = arg4;
  }
  return [options.key || "", value, options, overrides];
}
function clearNumberFormat(ctx, locale, format2) {
  const context = ctx;
  for (const key in format2) {
    const id = `${locale}__${key}`;
    if (!context.__numberFormatters.has(id)) {
      continue;
    }
    context.__numberFormatters.delete(id);
  }
}
const DEFAULT_MODIFIER = (str) => str;
const DEFAULT_MESSAGE = (ctx) => "";
const DEFAULT_MESSAGE_DATA_TYPE = "text";
const DEFAULT_NORMALIZE = (values) => values.length === 0 ? "" : join(values);
const DEFAULT_INTERPOLATE = toDisplayString;
function pluralDefault(choice, choicesLength) {
  choice = Math.abs(choice);
  if (choicesLength === 2) {
    return choice ? choice > 1 ? 1 : 0 : 1;
  }
  return choice ? Math.min(choice, 2) : 0;
}
function getPluralIndex(options) {
  const index = isNumber(options.pluralIndex) ? options.pluralIndex : -1;
  return options.named && (isNumber(options.named.count) || isNumber(options.named.n)) ? isNumber(options.named.count) ? options.named.count : isNumber(options.named.n) ? options.named.n : index : index;
}
function normalizeNamed(pluralIndex, props) {
  if (!props.count) {
    props.count = pluralIndex;
  }
  if (!props.n) {
    props.n = pluralIndex;
  }
}
function createMessageContext(options = {}) {
  const locale = options.locale;
  const pluralIndex = getPluralIndex(options);
  const pluralRule = isObject(options.pluralRules) && isString(locale) && isFunction(options.pluralRules[locale]) ? options.pluralRules[locale] : pluralDefault;
  const orgPluralRule = isObject(options.pluralRules) && isString(locale) && isFunction(options.pluralRules[locale]) ? pluralDefault : void 0;
  const plural = (messages) => {
    return messages[pluralRule(pluralIndex, messages.length, orgPluralRule)];
  };
  const _list = options.list || [];
  const list = (index) => _list[index];
  const _named = options.named || create();
  isNumber(options.pluralIndex) && normalizeNamed(pluralIndex, _named);
  const named = (key) => _named[key];
  function message(key, useLinked) {
    const msg = isFunction(options.messages) ? options.messages(key, !!useLinked) : isObject(options.messages) ? options.messages[key] : false;
    return !msg ? options.parent ? options.parent.message(key) : DEFAULT_MESSAGE : msg;
  }
  const _modifier = (name) => options.modifiers ? options.modifiers[name] : DEFAULT_MODIFIER;
  const normalize = isPlainObject(options.processor) && isFunction(options.processor.normalize) ? options.processor.normalize : DEFAULT_NORMALIZE;
  const interpolate = isPlainObject(options.processor) && isFunction(options.processor.interpolate) ? options.processor.interpolate : DEFAULT_INTERPOLATE;
  const type = isPlainObject(options.processor) && isString(options.processor.type) ? options.processor.type : DEFAULT_MESSAGE_DATA_TYPE;
  const linked = (key, ...args) => {
    const [arg1, arg2] = args;
    let type2 = "text";
    let modifier = "";
    if (args.length === 1) {
      if (isObject(arg1)) {
        modifier = arg1.modifier || modifier;
        type2 = arg1.type || type2;
      } else if (isString(arg1)) {
        modifier = arg1 || modifier;
      }
    } else if (args.length === 2) {
      if (isString(arg1)) {
        modifier = arg1 || modifier;
      }
      if (isString(arg2)) {
        type2 = arg2 || type2;
      }
    }
    const ret = message(key, true)(ctx);
    const msg = (
      // The message in vnode resolved with linked are returned as an array by processor.nomalize
      type2 === "vnode" && isArray(ret) && modifier ? ret[0] : ret
    );
    return modifier ? _modifier(modifier)(msg, type2) : msg;
  };
  const ctx = {
    [
      "list"
      /* HelperNameMap.LIST */
    ]: list,
    [
      "named"
      /* HelperNameMap.NAMED */
    ]: named,
    [
      "plural"
      /* HelperNameMap.PLURAL */
    ]: plural,
    [
      "linked"
      /* HelperNameMap.LINKED */
    ]: linked,
    [
      "message"
      /* HelperNameMap.MESSAGE */
    ]: message,
    [
      "type"
      /* HelperNameMap.TYPE */
    ]: type,
    [
      "interpolate"
      /* HelperNameMap.INTERPOLATE */
    ]: interpolate,
    [
      "normalize"
      /* HelperNameMap.NORMALIZE */
    ]: normalize,
    [
      "values"
      /* HelperNameMap.VALUES */
    ]: assign(create(), _list, _named)
  };
  return ctx;
}
const NOOP_MESSAGE_FUNCTION = () => "";
const isMessageFunction = (val) => isFunction(val);
function translate(context, ...args) {
  const { fallbackFormat, postTranslation, unresolving, messageCompiler, fallbackLocale, messages } = context;
  const [key, options] = parseTranslateArgs(...args);
  const missingWarn = isBoolean(options.missingWarn) ? options.missingWarn : context.missingWarn;
  const fallbackWarn = isBoolean(options.fallbackWarn) ? options.fallbackWarn : context.fallbackWarn;
  const escapeParameter = isBoolean(options.escapeParameter) ? options.escapeParameter : context.escapeParameter;
  const resolvedMessage = !!options.resolvedMessage;
  const defaultMsgOrKey = isString(options.default) || isBoolean(options.default) ? !isBoolean(options.default) ? options.default : !messageCompiler ? () => key : key : fallbackFormat ? !messageCompiler ? () => key : key : null;
  const enableDefaultMsg = fallbackFormat || defaultMsgOrKey != null && (isString(defaultMsgOrKey) || isFunction(defaultMsgOrKey));
  const locale = getLocale(context, options);
  escapeParameter && escapeParams(options);
  let [formatScope, targetLocale, message] = !resolvedMessage ? resolveMessageFormat(context, key, locale, fallbackLocale, fallbackWarn, missingWarn) : [
    key,
    locale,
    messages[locale] || create()
  ];
  let format2 = formatScope;
  let cacheBaseKey = key;
  if (!resolvedMessage && !(isString(format2) || isMessageAST(format2) || isMessageFunction(format2))) {
    if (enableDefaultMsg) {
      format2 = defaultMsgOrKey;
      cacheBaseKey = format2;
    }
  }
  if (!resolvedMessage && (!(isString(format2) || isMessageAST(format2) || isMessageFunction(format2)) || !isString(targetLocale))) {
    return unresolving ? NOT_REOSLVED : key;
  }
  let occurred = false;
  const onError = () => {
    occurred = true;
  };
  const msg = !isMessageFunction(format2) ? compileMessageFormat(context, key, targetLocale, format2, cacheBaseKey, onError) : format2;
  if (occurred) {
    return format2;
  }
  const ctxOptions = getMessageContextOptions(context, targetLocale, message, options);
  const msgContext = createMessageContext(ctxOptions);
  const messaged = evaluateMessage(context, msg, msgContext);
  let ret = postTranslation ? postTranslation(messaged, key) : messaged;
  if (escapeParameter && isString(ret)) {
    ret = sanitizeTranslatedHtml(ret);
  }
  return ret;
}
function escapeParams(options) {
  if (isArray(options.list)) {
    options.list = options.list.map((item) => isString(item) ? escapeHtml(item) : item);
  } else if (isObject(options.named)) {
    Object.keys(options.named).forEach((key) => {
      if (isString(options.named[key])) {
        options.named[key] = escapeHtml(options.named[key]);
      }
    });
  }
}
function resolveMessageFormat(context, key, locale, fallbackLocale, fallbackWarn, missingWarn) {
  const { messages, onWarn, messageResolver: resolveValue2, localeFallbacker } = context;
  const locales = localeFallbacker(context, fallbackLocale, locale);
  let message = create();
  let targetLocale;
  let format2 = null;
  const type = "translate";
  for (let i = 0; i < locales.length; i++) {
    targetLocale = locales[i];
    message = messages[targetLocale] || create();
    if ((format2 = resolveValue2(message, key)) === null) {
      format2 = message[key];
    }
    if (isString(format2) || isMessageAST(format2) || isMessageFunction(format2)) {
      break;
    }
    if (!isImplicitFallback(targetLocale, locales)) {
      const missingRet = handleMissing(
        context,
        // eslint-disable-line @typescript-eslint/no-explicit-any
        key,
        targetLocale,
        missingWarn,
        type
      );
      if (missingRet !== key) {
        format2 = missingRet;
      }
    }
  }
  return [format2, targetLocale, message];
}
function compileMessageFormat(context, key, targetLocale, format2, cacheBaseKey, onError) {
  const { messageCompiler, warnHtmlMessage } = context;
  if (isMessageFunction(format2)) {
    const msg2 = format2;
    msg2.locale = msg2.locale || targetLocale;
    msg2.key = msg2.key || key;
    return msg2;
  }
  if (messageCompiler == null) {
    const msg2 = (() => format2);
    msg2.locale = targetLocale;
    msg2.key = key;
    return msg2;
  }
  const msg = messageCompiler(format2, getCompileContext(context, targetLocale, cacheBaseKey, format2, warnHtmlMessage, onError));
  msg.locale = targetLocale;
  msg.key = key;
  msg.source = format2;
  return msg;
}
function evaluateMessage(context, msg, msgCtx) {
  const messaged = msg(msgCtx);
  return messaged;
}
function parseTranslateArgs(...args) {
  const [arg1, arg2, arg3] = args;
  const options = create();
  if (!isString(arg1) && !isNumber(arg1) && !isMessageFunction(arg1) && !isMessageAST(arg1)) {
    throw createCoreError(CoreErrorCodes.INVALID_ARGUMENT);
  }
  const key = isNumber(arg1) ? String(arg1) : isMessageFunction(arg1) ? arg1 : arg1;
  if (isNumber(arg2)) {
    options.plural = arg2;
  } else if (isString(arg2)) {
    options.default = arg2;
  } else if (isPlainObject(arg2) && !isEmptyObject(arg2)) {
    options.named = arg2;
  } else if (isArray(arg2)) {
    options.list = arg2;
  }
  if (isNumber(arg3)) {
    options.plural = arg3;
  } else if (isString(arg3)) {
    options.default = arg3;
  } else if (isPlainObject(arg3)) {
    assign(options, arg3);
  }
  return [key, options];
}
function getCompileContext(context, locale, key, source, warnHtmlMessage, onError) {
  return {
    locale,
    key,
    warnHtmlMessage,
    onError: (err) => {
      onError && onError(err);
      {
        throw err;
      }
    },
    onCacheKey: (source2) => generateFormatCacheKey(locale, key, source2)
  };
}
function getMessageContextOptions(context, locale, message, options) {
  const { modifiers, pluralRules, messageResolver: resolveValue2, fallbackLocale, fallbackWarn, missingWarn, fallbackContext } = context;
  const resolveMessage = (key, useLinked) => {
    let val = resolveValue2(message, key);
    if (val == null && (fallbackContext || useLinked)) {
      const [, , message2] = resolveMessageFormat(
        fallbackContext || context,
        // NOTE: if has fallbackContext, fallback to root, else if use linked, fallback to local context
        key,
        locale,
        fallbackLocale,
        fallbackWarn,
        missingWarn
      );
      val = resolveValue2(message2, key);
    }
    if (isString(val) || isMessageAST(val)) {
      let occurred = false;
      const onError = () => {
        occurred = true;
      };
      const msg = compileMessageFormat(context, key, locale, val, key, onError);
      return !occurred ? msg : NOOP_MESSAGE_FUNCTION;
    } else if (isMessageFunction(val)) {
      return val;
    } else {
      return NOOP_MESSAGE_FUNCTION;
    }
  };
  const ctxOptions = {
    locale,
    modifiers,
    pluralRules,
    messages: resolveMessage
  };
  if (context.processor) {
    ctxOptions.processor = context.processor;
  }
  if (options.list) {
    ctxOptions.list = options.list;
  }
  if (options.named) {
    ctxOptions.named = options.named;
  }
  if (isNumber(options.plural)) {
    ctxOptions.pluralIndex = options.plural;
  }
  return ctxOptions;
}
const VERSION = "11.2.8";
const I18nErrorCodes = {
  // composer module errors
  UNEXPECTED_RETURN_TYPE: CORE_ERROR_CODES_EXTEND_POINT,
  // 24
  // legacy module errors
  INVALID_ARGUMENT: 25,
  // i18n module errors
  MUST_BE_CALL_SETUP_TOP: 26,
  NOT_INSTALLED: 27,
  // directive module errors
  REQUIRED_VALUE: 28,
  INVALID_VALUE: 29,
  NOT_INSTALLED_WITH_PROVIDE: 31,
  // unexpected error
  UNEXPECTED_ERROR: 32};
function createI18nError(code, ...args) {
  return createCompileError(code, null, void 0);
}
const TranslateVNodeSymbol = /* @__PURE__ */ makeSymbol("__translateVNode");
const DatetimePartsSymbol = /* @__PURE__ */ makeSymbol("__datetimeParts");
const NumberPartsSymbol = /* @__PURE__ */ makeSymbol("__numberParts");
const SetPluralRulesSymbol = makeSymbol("__setPluralRules");
const InejctWithOptionSymbol = /* @__PURE__ */ makeSymbol("__injectWithOption");
const DisposeSymbol = /* @__PURE__ */ makeSymbol("__dispose");
function handleFlatJson(obj) {
  if (!isObject(obj)) {
    return obj;
  }
  if (isMessageAST(obj)) {
    return obj;
  }
  for (const key in obj) {
    if (!hasOwn(obj, key)) {
      continue;
    }
    if (!key.includes(".")) {
      if (isObject(obj[key])) {
        handleFlatJson(obj[key]);
      }
    } else {
      const subKeys = key.split(".");
      const lastIndex = subKeys.length - 1;
      let currentObj = obj;
      let hasStringValue = false;
      for (let i = 0; i < lastIndex; i++) {
        if (subKeys[i] === "__proto__") {
          throw new Error(`unsafe key: ${subKeys[i]}`);
        }
        if (!(subKeys[i] in currentObj)) {
          currentObj[subKeys[i]] = create();
        }
        if (!isObject(currentObj[subKeys[i]])) {
          hasStringValue = true;
          break;
        }
        currentObj = currentObj[subKeys[i]];
      }
      if (!hasStringValue) {
        if (!isMessageAST(currentObj)) {
          currentObj[subKeys[lastIndex]] = obj[key];
          delete obj[key];
        } else {
          if (!AST_NODE_PROPS_KEYS.includes(subKeys[lastIndex])) {
            delete obj[key];
          }
        }
      }
      if (!isMessageAST(currentObj)) {
        const target = currentObj[subKeys[lastIndex]];
        if (isObject(target)) {
          handleFlatJson(target);
        }
      }
    }
  }
  return obj;
}
function getLocaleMessages(locale, options) {
  const { messages, __i18n, messageResolver, flatJson } = options;
  const ret = isPlainObject(messages) ? messages : isArray(__i18n) ? create() : { [locale]: create() };
  if (isArray(__i18n)) {
    __i18n.forEach((custom) => {
      if ("locale" in custom && "resource" in custom) {
        const { locale: locale2, resource } = custom;
        if (locale2) {
          ret[locale2] = ret[locale2] || create();
          deepCopy(resource, ret[locale2]);
        } else {
          deepCopy(resource, ret);
        }
      } else {
        isString(custom) && deepCopy(JSON.parse(custom), ret);
      }
    });
  }
  if (messageResolver == null && flatJson) {
    for (const key in ret) {
      if (hasOwn(ret, key)) {
        handleFlatJson(ret[key]);
      }
    }
  }
  return ret;
}
function getComponentOptions(instance) {
  return instance.type;
}
function adjustI18nResources(gl, options, componentOptions) {
  let messages = isObject(options.messages) ? options.messages : create();
  if ("__i18nGlobal" in componentOptions) {
    messages = getLocaleMessages(gl.locale.value, {
      messages,
      __i18n: componentOptions.__i18nGlobal
    });
  }
  const locales = Object.keys(messages);
  if (locales.length) {
    locales.forEach((locale) => {
      gl.mergeLocaleMessage(locale, messages[locale]);
    });
  }
  {
    if (isObject(options.datetimeFormats)) {
      const locales2 = Object.keys(options.datetimeFormats);
      if (locales2.length) {
        locales2.forEach((locale) => {
          gl.mergeDateTimeFormat(locale, options.datetimeFormats[locale]);
        });
      }
    }
    if (isObject(options.numberFormats)) {
      const locales2 = Object.keys(options.numberFormats);
      if (locales2.length) {
        locales2.forEach((locale) => {
          gl.mergeNumberFormat(locale, options.numberFormats[locale]);
        });
      }
    }
  }
}
function createTextNode(key) {
  return createVNode(Text, null, key, 0);
}
function getCurrentInstance() {
  const key = "currentInstance";
  if (key in Vue) {
    return Vue[key];
  } else {
    return Vue.getCurrentInstance();
  }
}
const DEVTOOLS_META = "__INTLIFY_META__";
const NOOP_RETURN_ARRAY = () => [];
const NOOP_RETURN_FALSE = () => false;
let composerID = 0;
function defineCoreMissingHandler(missing) {
  return ((ctx, locale, key, type) => {
    return missing(locale, key, getCurrentInstance() || void 0, type);
  });
}
const getMetaInfo = /* @__NO_SIDE_EFFECTS__ */ () => {
  const instance = getCurrentInstance();
  let meta = null;
  return instance && (meta = getComponentOptions(instance)[DEVTOOLS_META]) ? { [DEVTOOLS_META]: meta } : null;
};
function createComposer(options = {}) {
  const { __root, __injectWithOption } = options;
  const _isGlobal = __root === void 0;
  const flatJson = options.flatJson;
  const _ref = shallowRef;
  let _inheritLocale = isBoolean(options.inheritLocale) ? options.inheritLocale : true;
  const _locale = _ref(
    // prettier-ignore
    __root && _inheritLocale ? __root.locale.value : isString(options.locale) ? options.locale : DEFAULT_LOCALE
  );
  const _fallbackLocale = _ref(
    // prettier-ignore
    __root && _inheritLocale ? __root.fallbackLocale.value : isString(options.fallbackLocale) || isArray(options.fallbackLocale) || isPlainObject(options.fallbackLocale) || options.fallbackLocale === false ? options.fallbackLocale : _locale.value
  );
  const _messages = _ref(getLocaleMessages(_locale.value, options));
  const _datetimeFormats = _ref(isPlainObject(options.datetimeFormats) ? options.datetimeFormats : { [_locale.value]: {} });
  const _numberFormats = _ref(isPlainObject(options.numberFormats) ? options.numberFormats : { [_locale.value]: {} });
  let _missingWarn = __root ? __root.missingWarn : isBoolean(options.missingWarn) || isRegExp(options.missingWarn) ? options.missingWarn : true;
  let _fallbackWarn = __root ? __root.fallbackWarn : isBoolean(options.fallbackWarn) || isRegExp(options.fallbackWarn) ? options.fallbackWarn : true;
  let _fallbackRoot = __root ? __root.fallbackRoot : isBoolean(options.fallbackRoot) ? options.fallbackRoot : true;
  let _fallbackFormat = !!options.fallbackFormat;
  let _missing = isFunction(options.missing) ? options.missing : null;
  let _runtimeMissing = isFunction(options.missing) ? defineCoreMissingHandler(options.missing) : null;
  let _postTranslation = isFunction(options.postTranslation) ? options.postTranslation : null;
  let _warnHtmlMessage = __root ? __root.warnHtmlMessage : isBoolean(options.warnHtmlMessage) ? options.warnHtmlMessage : true;
  let _escapeParameter = !!options.escapeParameter;
  const _modifiers = __root ? __root.modifiers : isPlainObject(options.modifiers) ? options.modifiers : {};
  let _pluralRules = options.pluralRules || __root && __root.pluralRules;
  let _context;
  const getCoreContext = () => {
    _isGlobal && setFallbackContext(null);
    const ctxOptions = {
      version: VERSION,
      locale: _locale.value,
      fallbackLocale: _fallbackLocale.value,
      messages: _messages.value,
      modifiers: _modifiers,
      pluralRules: _pluralRules,
      missing: _runtimeMissing === null ? void 0 : _runtimeMissing,
      missingWarn: _missingWarn,
      fallbackWarn: _fallbackWarn,
      fallbackFormat: _fallbackFormat,
      unresolving: true,
      postTranslation: _postTranslation === null ? void 0 : _postTranslation,
      warnHtmlMessage: _warnHtmlMessage,
      escapeParameter: _escapeParameter,
      messageResolver: options.messageResolver,
      messageCompiler: options.messageCompiler,
      __meta: { framework: "vue" }
    };
    {
      ctxOptions.datetimeFormats = _datetimeFormats.value;
      ctxOptions.numberFormats = _numberFormats.value;
      ctxOptions.__datetimeFormatters = isPlainObject(_context) ? _context.__datetimeFormatters : void 0;
      ctxOptions.__numberFormatters = isPlainObject(_context) ? _context.__numberFormatters : void 0;
    }
    const ctx = createCoreContext(ctxOptions);
    _isGlobal && setFallbackContext(ctx);
    return ctx;
  };
  _context = getCoreContext();
  updateFallbackLocale(_context, _locale.value, _fallbackLocale.value);
  function trackReactivityValues() {
    return [
      _locale.value,
      _fallbackLocale.value,
      _messages.value,
      _datetimeFormats.value,
      _numberFormats.value
    ];
  }
  const locale = computed({
    get: () => _locale.value,
    set: (val) => {
      _context.locale = val;
      _locale.value = val;
    }
  });
  const fallbackLocale = computed({
    get: () => _fallbackLocale.value,
    set: (val) => {
      _context.fallbackLocale = val;
      _fallbackLocale.value = val;
      updateFallbackLocale(_context, _locale.value, val);
    }
  });
  const messages = computed(() => _messages.value);
  const datetimeFormats = /* @__PURE__ */ computed(() => _datetimeFormats.value);
  const numberFormats = /* @__PURE__ */ computed(() => _numberFormats.value);
  function getPostTranslationHandler() {
    return isFunction(_postTranslation) ? _postTranslation : null;
  }
  function setPostTranslationHandler(handler) {
    _postTranslation = handler;
    _context.postTranslation = handler;
  }
  function getMissingHandler() {
    return _missing;
  }
  function setMissingHandler(handler) {
    if (handler !== null) {
      _runtimeMissing = defineCoreMissingHandler(handler);
    }
    _missing = handler;
    _context.missing = _runtimeMissing;
  }
  const wrapWithDeps = (fn, argumentParser, warnType, fallbackSuccess, fallbackFail, successCondition) => {
    trackReactivityValues();
    let ret;
    try {
      if ("production" !== "production" || false) ;
      if (!_isGlobal) {
        _context.fallbackContext = __root ? getFallbackContext() : void 0;
      }
      ret = fn(_context);
    } finally {
      if (!_isGlobal) {
        _context.fallbackContext = void 0;
      }
    }
    if (warnType !== "translate exists" && // for not `te` (e.g `t`)
    isNumber(ret) && ret === NOT_REOSLVED || warnType === "translate exists" && !ret) {
      const [key, arg2] = argumentParser();
      return __root && _fallbackRoot ? fallbackSuccess(__root) : fallbackFail(key);
    } else if (successCondition(ret)) {
      return ret;
    } else {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_RETURN_TYPE);
    }
  };
  function t(...args) {
    return wrapWithDeps((context) => Reflect.apply(translate, null, [context, ...args]), () => parseTranslateArgs(...args), "translate", (root) => Reflect.apply(root.t, root, [...args]), (key) => key, (val) => isString(val));
  }
  function rt(...args) {
    const [arg1, arg2, arg3] = args;
    if (arg3 && !isObject(arg3)) {
      throw createI18nError(I18nErrorCodes.INVALID_ARGUMENT);
    }
    return t(...[arg1, arg2, assign({ resolvedMessage: true }, arg3 || {})]);
  }
  function d(...args) {
    return wrapWithDeps((context) => Reflect.apply(datetime, null, [context, ...args]), () => parseDateTimeArgs(...args), "datetime format", (root) => Reflect.apply(root.d, root, [...args]), () => MISSING_RESOLVE_VALUE, (val) => isString(val) || isArray(val));
  }
  function n(...args) {
    return wrapWithDeps((context) => Reflect.apply(number, null, [context, ...args]), () => parseNumberArgs(...args), "number format", (root) => Reflect.apply(root.n, root, [...args]), () => MISSING_RESOLVE_VALUE, (val) => isString(val) || isArray(val));
  }
  function normalize(values) {
    return values.map((val) => isString(val) || isNumber(val) || isBoolean(val) ? createTextNode(String(val)) : val);
  }
  const interpolate = (val) => val;
  const processor = {
    normalize,
    interpolate,
    type: "vnode"
  };
  function translateVNode(...args) {
    return wrapWithDeps((context) => {
      let ret;
      const _context2 = context;
      try {
        _context2.processor = processor;
        ret = Reflect.apply(translate, null, [_context2, ...args]);
      } finally {
        _context2.processor = null;
      }
      return ret;
    }, () => parseTranslateArgs(...args), "translate", (root) => root[TranslateVNodeSymbol](...args), (key) => [createTextNode(key)], (val) => isArray(val));
  }
  function numberParts(...args) {
    return wrapWithDeps((context) => Reflect.apply(number, null, [context, ...args]), () => parseNumberArgs(...args), "number format", (root) => root[NumberPartsSymbol](...args), NOOP_RETURN_ARRAY, (val) => isString(val) || isArray(val));
  }
  function datetimeParts(...args) {
    return wrapWithDeps((context) => Reflect.apply(datetime, null, [context, ...args]), () => parseDateTimeArgs(...args), "datetime format", (root) => root[DatetimePartsSymbol](...args), NOOP_RETURN_ARRAY, (val) => isString(val) || isArray(val));
  }
  function setPluralRules(rules) {
    _pluralRules = rules;
    _context.pluralRules = _pluralRules;
  }
  function te(key, locale2) {
    return wrapWithDeps(() => {
      if (!key) {
        return false;
      }
      const targetLocale = isString(locale2) ? locale2 : _locale.value;
      const message = getLocaleMessage(targetLocale);
      const resolved = _context.messageResolver(message, key);
      return isMessageAST(resolved) || isMessageFunction(resolved) || isString(resolved);
    }, () => [key], "translate exists", (root) => {
      return Reflect.apply(root.te, root, [key, locale2]);
    }, NOOP_RETURN_FALSE, (val) => isBoolean(val));
  }
  function resolveMessages(key) {
    let messages2 = null;
    const locales = fallbackWithLocaleChain(_context, _fallbackLocale.value, _locale.value);
    for (let i = 0; i < locales.length; i++) {
      const targetLocaleMessages = _messages.value[locales[i]] || {};
      const messageValue = _context.messageResolver(targetLocaleMessages, key);
      if (messageValue != null) {
        messages2 = messageValue;
        break;
      }
    }
    return messages2;
  }
  function tm(key) {
    const messages2 = resolveMessages(key);
    return messages2 != null ? messages2 : __root ? __root.tm(key) || {} : {};
  }
  function getLocaleMessage(locale2) {
    return _messages.value[locale2] || {};
  }
  function setLocaleMessage(locale2, message) {
    if (flatJson) {
      const _message = { [locale2]: message };
      for (const key in _message) {
        if (hasOwn(_message, key)) {
          handleFlatJson(_message[key]);
        }
      }
      message = _message[locale2];
    }
    _messages.value[locale2] = message;
    _context.messages = _messages.value;
  }
  function mergeLocaleMessage(locale2, message) {
    _messages.value[locale2] = _messages.value[locale2] || {};
    const _message = { [locale2]: message };
    if (flatJson) {
      for (const key in _message) {
        if (hasOwn(_message, key)) {
          handleFlatJson(_message[key]);
        }
      }
    }
    message = _message[locale2];
    deepCopy(message, _messages.value[locale2]);
    _context.messages = _messages.value;
  }
  function getDateTimeFormat(locale2) {
    return _datetimeFormats.value[locale2] || {};
  }
  function setDateTimeFormat(locale2, format2) {
    _datetimeFormats.value[locale2] = format2;
    _context.datetimeFormats = _datetimeFormats.value;
    clearDateTimeFormat(_context, locale2, format2);
  }
  function mergeDateTimeFormat(locale2, format2) {
    _datetimeFormats.value[locale2] = assign(_datetimeFormats.value[locale2] || {}, format2);
    _context.datetimeFormats = _datetimeFormats.value;
    clearDateTimeFormat(_context, locale2, format2);
  }
  function getNumberFormat(locale2) {
    return _numberFormats.value[locale2] || {};
  }
  function setNumberFormat(locale2, format2) {
    _numberFormats.value[locale2] = format2;
    _context.numberFormats = _numberFormats.value;
    clearNumberFormat(_context, locale2, format2);
  }
  function mergeNumberFormat(locale2, format2) {
    _numberFormats.value[locale2] = assign(_numberFormats.value[locale2] || {}, format2);
    _context.numberFormats = _numberFormats.value;
    clearNumberFormat(_context, locale2, format2);
  }
  composerID++;
  const composer = {
    id: composerID,
    locale,
    fallbackLocale,
    get inheritLocale() {
      return _inheritLocale;
    },
    set inheritLocale(val) {
      _inheritLocale = val;
      if (val && __root) {
        _locale.value = __root.locale.value;
        _fallbackLocale.value = __root.fallbackLocale.value;
        updateFallbackLocale(_context, _locale.value, _fallbackLocale.value);
      }
    },
    get availableLocales() {
      return Object.keys(_messages.value).sort();
    },
    messages,
    get modifiers() {
      return _modifiers;
    },
    get pluralRules() {
      return _pluralRules || {};
    },
    get isGlobal() {
      return _isGlobal;
    },
    get missingWarn() {
      return _missingWarn;
    },
    set missingWarn(val) {
      _missingWarn = val;
      _context.missingWarn = _missingWarn;
    },
    get fallbackWarn() {
      return _fallbackWarn;
    },
    set fallbackWarn(val) {
      _fallbackWarn = val;
      _context.fallbackWarn = _fallbackWarn;
    },
    get fallbackRoot() {
      return _fallbackRoot;
    },
    set fallbackRoot(val) {
      _fallbackRoot = val;
    },
    get fallbackFormat() {
      return _fallbackFormat;
    },
    set fallbackFormat(val) {
      _fallbackFormat = val;
      _context.fallbackFormat = _fallbackFormat;
    },
    get warnHtmlMessage() {
      return _warnHtmlMessage;
    },
    set warnHtmlMessage(val) {
      _warnHtmlMessage = val;
      _context.warnHtmlMessage = val;
    },
    get escapeParameter() {
      return _escapeParameter;
    },
    set escapeParameter(val) {
      _escapeParameter = val;
      _context.escapeParameter = val;
    },
    t,
    getLocaleMessage,
    setLocaleMessage,
    mergeLocaleMessage,
    getPostTranslationHandler,
    setPostTranslationHandler,
    getMissingHandler,
    setMissingHandler,
    [SetPluralRulesSymbol]: setPluralRules
  };
  {
    composer.datetimeFormats = datetimeFormats;
    composer.numberFormats = numberFormats;
    composer.rt = rt;
    composer.te = te;
    composer.tm = tm;
    composer.d = d;
    composer.n = n;
    composer.getDateTimeFormat = getDateTimeFormat;
    composer.setDateTimeFormat = setDateTimeFormat;
    composer.mergeDateTimeFormat = mergeDateTimeFormat;
    composer.getNumberFormat = getNumberFormat;
    composer.setNumberFormat = setNumberFormat;
    composer.mergeNumberFormat = mergeNumberFormat;
    composer[InejctWithOptionSymbol] = __injectWithOption;
    composer[TranslateVNodeSymbol] = translateVNode;
    composer[DatetimePartsSymbol] = datetimeParts;
    composer[NumberPartsSymbol] = numberParts;
  }
  return composer;
}
const baseFormatProps = {
  tag: {
    type: [String, Object]
  },
  locale: {
    type: String
  },
  scope: {
    type: String,
    // NOTE: avoid https://github.com/microsoft/rushstack/issues/1050
    validator: (val) => val === "parent" || val === "global",
    default: "parent"
    /* ComponentI18nScope */
  },
  i18n: {
    type: Object
  }
};
function getInterpolateArg({ slots }, keys) {
  if (keys.length === 1 && keys[0] === "default") {
    const ret = slots.default ? slots.default() : [];
    return ret.reduce((slot, current) => {
      return [
        ...slot,
        // prettier-ignore
        ...current.type === Fragment ? current.children : [current]
      ];
    }, []);
  } else {
    return keys.reduce((arg, key) => {
      const slot = slots[key];
      if (slot) {
        arg[key] = slot();
      }
      return arg;
    }, create());
  }
}
function getFragmentableTag() {
  return Fragment;
}
const TranslationImpl = /* @__PURE__ */ defineComponent({
  /* eslint-disable */
  name: "i18n-t",
  props: assign({
    keypath: {
      type: String,
      required: true
    },
    plural: {
      type: [Number, String],
      validator: (val) => isNumber(val) || !isNaN(val)
    }
  }, baseFormatProps),
  /* eslint-enable */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  setup(props, context) {
    const { slots, attrs } = context;
    const i18n = props.i18n || useI18n({
      useScope: props.scope,
      __useComponent: true
    });
    return () => {
      const keys = Object.keys(slots).filter((key) => key[0] !== "_");
      const options = create();
      if (props.locale) {
        options.locale = props.locale;
      }
      if (props.plural !== void 0) {
        options.plural = isString(props.plural) ? +props.plural : props.plural;
      }
      const arg = getInterpolateArg(context, keys);
      const children = i18n[TranslateVNodeSymbol](props.keypath, arg, options);
      const assignedAttrs = assign(create(), attrs);
      const tag = isString(props.tag) || isObject(props.tag) ? props.tag : getFragmentableTag();
      return h(tag, assignedAttrs, children);
    };
  }
});
const Translation = TranslationImpl;
function isVNode(target) {
  return isArray(target) && !isString(target[0]);
}
function renderFormatter(props, context, slotKeys, partFormatter) {
  const { slots, attrs } = context;
  return () => {
    const options = { part: true };
    let overrides = create();
    if (props.locale) {
      options.locale = props.locale;
    }
    if (isString(props.format)) {
      options.key = props.format;
    } else if (isObject(props.format)) {
      if (isString(props.format.key)) {
        options.key = props.format.key;
      }
      overrides = Object.keys(props.format).reduce((options2, prop) => {
        return slotKeys.includes(prop) ? assign(create(), options2, { [prop]: props.format[prop] }) : options2;
      }, create());
    }
    const parts = partFormatter(...[props.value, options, overrides]);
    let children = [options.key];
    if (isArray(parts)) {
      children = parts.map((part, index) => {
        const slot = slots[part.type];
        const node = slot ? slot({ [part.type]: part.value, index, parts }) : [part.value];
        if (isVNode(node)) {
          node[0].key = `${part.type}-${index}`;
        }
        return node;
      });
    } else if (isString(parts)) {
      children = [parts];
    }
    const assignedAttrs = assign(create(), attrs);
    const tag = isString(props.tag) || isObject(props.tag) ? props.tag : getFragmentableTag();
    return h(tag, assignedAttrs, children);
  };
}
const NumberFormatImpl = /* @__PURE__ */ defineComponent({
  /* eslint-disable */
  name: "i18n-n",
  props: assign({
    value: {
      type: Number,
      required: true
    },
    format: {
      type: [String, Object]
    }
  }, baseFormatProps),
  /* eslint-enable */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  setup(props, context) {
    const i18n = props.i18n || useI18n({
      useScope: props.scope,
      __useComponent: true
    });
    return renderFormatter(props, context, NUMBER_FORMAT_OPTIONS_KEYS, (...args) => (
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      i18n[NumberPartsSymbol](...args)
    ));
  }
});
const NumberFormat = NumberFormatImpl;
function getComposer$1(i18n, instance) {
  const i18nInternal = i18n;
  if (i18n.mode === "composition") {
    return i18nInternal.__getInstance(instance) || i18n.global;
  } else {
    const vueI18n = i18nInternal.__getInstance(instance);
    return vueI18n != null ? vueI18n.__composer : i18n.global.__composer;
  }
}
function vTDirective(i18n) {
  const _process = (binding) => {
    const { instance, value } = binding;
    if (!instance || !instance.$) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    const composer = getComposer$1(i18n, instance.$);
    const parsedValue = parseValue(value);
    return [
      Reflect.apply(composer.t, composer, [...makeParams(parsedValue)]),
      composer
    ];
  };
  const register = (el, binding) => {
    const [textContent, composer] = _process(binding);
    el.__composer = composer;
    el.textContent = textContent;
  };
  const unregister = (el) => {
    if (el.__composer) {
      el.__composer = void 0;
      delete el.__composer;
    }
  };
  const update = (el, { value }) => {
    if (el.__composer) {
      const composer = el.__composer;
      const parsedValue = parseValue(value);
      el.textContent = Reflect.apply(composer.t, composer, [
        ...makeParams(parsedValue)
      ]);
    }
  };
  const getSSRProps = (binding) => {
    const [textContent] = _process(binding);
    return { textContent };
  };
  return {
    created: register,
    unmounted: unregister,
    beforeUpdate: update,
    getSSRProps
  };
}
function parseValue(value) {
  if (isString(value)) {
    return { path: value };
  } else if (isPlainObject(value)) {
    if (!("path" in value)) {
      throw createI18nError(I18nErrorCodes.REQUIRED_VALUE, "path");
    }
    return value;
  } else {
    throw createI18nError(I18nErrorCodes.INVALID_VALUE);
  }
}
function makeParams(value) {
  const { path, locale, args, choice, plural } = value;
  const options = {};
  const named = args || {};
  if (isString(locale)) {
    options.locale = locale;
  }
  if (isNumber(choice)) {
    options.plural = choice;
  }
  if (isNumber(plural)) {
    options.plural = plural;
  }
  return [path, named, options];
}
function apply(app, i18n, ...options) {
  const pluginOptions = isPlainObject(options[0]) ? options[0] : {};
  const globalInstall = isBoolean(pluginOptions.globalInstall) ? pluginOptions.globalInstall : true;
  if (globalInstall) {
    [Translation.name, "I18nT"].forEach((name) => app.component(name, Translation));
    [NumberFormat.name, "I18nN"].forEach((name) => app.component(name, NumberFormat));
    [DatetimeFormat.name, "I18nD"].forEach((name) => app.component(name, DatetimeFormat));
  }
  {
    app.directive("t", vTDirective(i18n));
  }
}
const I18nInjectionKey = /* @__PURE__ */ makeSymbol("global-vue-i18n");
function createI18n(options = {}) {
  const __globalInjection = isBoolean(options.globalInjection) ? options.globalInjection : true;
  const __instances = /* @__PURE__ */ new Map();
  const [globalScope, __global] = createGlobal(options);
  const symbol = /* @__PURE__ */ makeSymbol("");
  function __getInstance(component) {
    return __instances.get(component) || null;
  }
  function __setInstance(component, instance) {
    __instances.set(component, instance);
  }
  function __deleteInstance(component) {
    __instances.delete(component);
  }
  const i18n = {
    // mode
    get mode() {
      return "composition";
    },
    // install plugin
    async install(app, ...options2) {
      app.__VUE_I18N_SYMBOL__ = symbol;
      app.provide(app.__VUE_I18N_SYMBOL__, i18n);
      if (isPlainObject(options2[0])) {
        const opts = options2[0];
        i18n.__composerExtend = opts.__composerExtend;
        i18n.__vueI18nExtend = opts.__vueI18nExtend;
      }
      let globalReleaseHandler = null;
      if (__globalInjection) {
        globalReleaseHandler = injectGlobalFields(app, i18n.global);
      }
      {
        apply(app, i18n, ...options2);
      }
      const unmountApp = app.unmount;
      app.unmount = () => {
        globalReleaseHandler && globalReleaseHandler();
        i18n.dispose();
        unmountApp();
      };
    },
    // global accessor
    get global() {
      return __global;
    },
    dispose() {
      globalScope.stop();
    },
    // @internal
    __instances,
    // @internal
    __getInstance,
    // @internal
    __setInstance,
    // @internal
    __deleteInstance
  };
  return i18n;
}
function useI18n(options = {}) {
  const instance = getCurrentInstance();
  if (instance == null) {
    throw createI18nError(I18nErrorCodes.MUST_BE_CALL_SETUP_TOP);
  }
  if (!instance.isCE && instance.appContext.app != null && !instance.appContext.app.__VUE_I18N_SYMBOL__) {
    throw createI18nError(I18nErrorCodes.NOT_INSTALLED);
  }
  const i18n = getI18nInstance(instance);
  const gl = getGlobalComposer(i18n);
  const componentOptions = getComponentOptions(instance);
  const scope = getScope(options, componentOptions);
  if (scope === "global") {
    adjustI18nResources(gl, options, componentOptions);
    return gl;
  }
  if (scope === "parent") {
    let composer2 = getComposer(i18n, instance, options.__useComponent);
    if (composer2 == null) {
      composer2 = gl;
    }
    return composer2;
  }
  const i18nInternal = i18n;
  let composer = i18nInternal.__getInstance(instance);
  if (composer == null) {
    const composerOptions = assign({}, options);
    if ("__i18n" in componentOptions) {
      composerOptions.__i18n = componentOptions.__i18n;
    }
    if (gl) {
      composerOptions.__root = gl;
    }
    composer = createComposer(composerOptions);
    if (i18nInternal.__composerExtend) {
      composer[DisposeSymbol] = i18nInternal.__composerExtend(composer);
    }
    i18nInternal.__setInstance(instance, composer);
  }
  return composer;
}
function createGlobal(options, legacyMode) {
  const scope = effectScope();
  const obj = scope.run(() => createComposer(options));
  if (obj == null) {
    throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
  }
  return [scope, obj];
}
function getI18nInstance(instance) {
  const i18n = inject(!instance.isCE ? instance.appContext.app.__VUE_I18N_SYMBOL__ : I18nInjectionKey);
  if (!i18n) {
    throw createI18nError(!instance.isCE ? I18nErrorCodes.UNEXPECTED_ERROR : I18nErrorCodes.NOT_INSTALLED_WITH_PROVIDE);
  }
  return i18n;
}
function getScope(options, componentOptions) {
  return isEmptyObject(options) ? "__i18n" in componentOptions ? "local" : "global" : !options.useScope ? "local" : options.useScope;
}
function getGlobalComposer(i18n) {
  return i18n.mode === "composition" ? i18n.global : i18n.global.__composer;
}
function getComposer(i18n, target, useComponent = false) {
  let composer = null;
  const root = target.root;
  let current = getParentComponentInstance(target, useComponent);
  while (current != null) {
    const i18nInternal = i18n;
    if (i18n.mode === "composition") {
      composer = i18nInternal.__getInstance(current);
    }
    if (composer != null) {
      break;
    }
    if (root === current) {
      break;
    }
    current = current.parent;
  }
  return composer;
}
function getParentComponentInstance(target, useComponent = false) {
  if (target == null) {
    return null;
  }
  return !useComponent ? target.parent : target.vnode.ctx || target.parent;
}
const globalExportProps = [
  "locale",
  "fallbackLocale",
  "availableLocales"
];
const globalExportMethods = ["t", "rt", "d", "n", "tm", "te"];
function injectGlobalFields(app, composer) {
  const i18n = /* @__PURE__ */ Object.create(null);
  globalExportProps.forEach((prop) => {
    const desc = Object.getOwnPropertyDescriptor(composer, prop);
    if (!desc) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    const wrap = isRef(desc.value) ? {
      get() {
        return desc.value.value;
      },
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      set(val) {
        desc.value.value = val;
      }
    } : {
      get() {
        return desc.get && desc.get();
      }
    };
    Object.defineProperty(i18n, prop, wrap);
  });
  app.config.globalProperties.$i18n = i18n;
  globalExportMethods.forEach((method) => {
    const desc = Object.getOwnPropertyDescriptor(composer, method);
    if (!desc || !desc.value) {
      throw createI18nError(I18nErrorCodes.UNEXPECTED_ERROR);
    }
    Object.defineProperty(app.config.globalProperties, `$${method}`, desc);
  });
  const dispose = () => {
    delete app.config.globalProperties.$i18n;
    globalExportMethods.forEach((method) => {
      delete app.config.globalProperties[`$${method}`];
    });
  };
  return dispose;
}
const DatetimeFormatImpl = /* @__PURE__ */ defineComponent({
  /* eslint-disable */
  name: "i18n-d",
  props: assign({
    value: {
      type: [Number, Date],
      required: true
    },
    format: {
      type: [String, Object]
    }
  }, baseFormatProps),
  /* eslint-enable */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  setup(props, context) {
    const i18n = props.i18n || useI18n({
      useScope: props.scope,
      __useComponent: true
    });
    return renderFormatter(props, context, DATETIME_FORMAT_OPTIONS_KEYS, (...args) => (
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      i18n[DatetimePartsSymbol](...args)
    ));
  }
});
const DatetimeFormat = DatetimeFormatImpl;
registerMessageCompiler(compile);
registerMessageResolver(resolveValue);
registerLocaleFallbacker(fallbackWithLocaleChain);
function useRouteBaseName(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (route) => {
    if (route == null) {
      return;
    }
    return common.getRouteBaseName(route) || void 0;
  };
}
function useLocalePath(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (route, locale) => localePath(common, route, locale);
}
function useLocaleRoute(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (route, locale) => localeRoute(common, route, locale);
}
function useSwitchLocalePath(nuxtApp = useNuxtApp()) {
  const common = useComposableContext(nuxtApp);
  return (locale) => switchLocalePath(common, locale);
}
const identifier = "nuxt-i18n-slp";
const switchLocalePathLinkWrapperExpr = new RegExp(
  [`<!--${identifier}-\\[(\\w+)\\]-->`, `.+?`, `<!--/${identifier}-->`].join(""),
  "g"
);
const switch_locale_path_ssr_NflG9_QeVcJ1jVig0vCfxB_cZhpEMQ9U2ujRUiYbbVw = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin:switch-locale-path-ssr",
  dependsOn: ["i18n:plugin"],
  setup(_nuxt) {
    const nuxt = useNuxtApp(_nuxt._id);
    const switchLocalePath2 = useSwitchLocalePath(nuxt);
    nuxt.hook("app:rendered", (ctx) => {
      if (ctx.renderResult?.html == null) {
        return;
      }
      ctx.renderResult.html = ctx.renderResult.html.replaceAll(
        switchLocalePathLinkWrapperExpr,
        (match, p1) => {
          const encoded = encodeURI(switchLocalePath2(p1 ?? ""));
          return match.replace(
            /href="([^"]+)"/,
            `href="${encoded || "#"}" ${""}`
          );
        }
      );
    });
  }
});
const route_locale_detect__HPHJq3Jg7gwhwgKEI8tQavopSAjmrCSPXl9HgL2h9U = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin:route-locale-detect",
  dependsOn: ["i18n:plugin"],
  async setup(_nuxt) {
    let __temp, __restore;
    const nuxt = useNuxtApp(_nuxt._id);
    const ctx = useNuxtI18nContext(nuxt);
    const resolvedLocale = useResolvedLocale();
    [__temp, __restore] = executeAsync(() => nuxt.runWithContext(
      () => loadAndSetLocale(
        nuxt,
        ctx.initial && resolvedLocale.value || detectLocale(nuxt, nuxt.$router.currentRoute.value)
      )
    )), await __temp, __restore();
    {
      return;
    }
  }
});
const preload_30FByJAs5vQa4mNNQLX15KPGCCVjIGrzdTjh6ve5W24 = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin:preload",
  dependsOn: ["i18n:plugin"],
  async setup(_nuxt) {
    {
      return;
    }
  }
});
function extendI18n(i18n, { extendComposer, extendComposerInstance }) {
  const scope = effectScope();
  const installI18n = i18n.install.bind(i18n);
  i18n.install = (app, ...options) => {
    const pluginOptions = assign({}, options[0]);
    pluginOptions.__composerExtend = (c) => {
      extendComposerInstance(c, getComposer$3(i18n));
      return () => {
      };
    };
    if (i18n.mode === "legacy") {
      pluginOptions.__vueI18nExtend = (vueI18n) => {
        extendComposerInstance(vueI18n, getComposer$3(vueI18n));
        return () => {
        };
      };
    }
    Reflect.apply(installI18n, i18n, [app, pluginOptions]);
    const globalComposer = getComposer$3(i18n);
    scope.run(() => {
      extendComposer(globalComposer);
      if (i18n.mode === "legacy" && "__composer" in i18n.global) {
        extendComposerInstance(i18n.global, getComposer$3(i18n.global));
      }
    });
    if (i18n.mode === "composition" && app.config.globalProperties.$i18n != null) {
      extendComposerInstance(app.config.globalProperties.$i18n, globalComposer);
    }
    if (app.unmount) {
      const unmountApp = app.unmount.bind(app);
      app.unmount = () => {
        scope.stop();
        unmountApp();
      };
    }
  };
}
const setupVueI18nOptions = async (defaultLocale) => {
  const options = await loadVueI18nOptions(vueI18nConfigs);
  options.locale = defaultLocale || options.locale || "en-US";
  options.defaultLocale = defaultLocale;
  options.fallbackLocale ??= false;
  options.messages ??= {};
  for (const locale of localeCodes) {
    options.messages[locale] ??= {};
  }
  return options;
};
const i18n_EI7LsD1KYQADczz5hrChviGQCdVM8yUkvFEZLJpmnvM = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin",
  parallel: false,
  async setup(_nuxt) {
    let __temp, __restore;
    Object.defineProperty(_nuxt.versions, "nuxtI18n", { get: () => "10.2.1" });
    const nuxt = useNuxtApp(_nuxt._id);
    const runtimeI18n = useRuntimeI18n(nuxt);
    const preloadedOptions = nuxt.ssrContext?.event?.context?.nuxtI18n?.vueI18nOptions;
    const _defaultLocale = getDefaultLocaleForDomain(useRequestURL({ xForwardedHost: true }).host) || runtimeI18n.defaultLocale || "";
    const optionsI18n = preloadedOptions || ([__temp, __restore] = executeAsync(() => setupVueI18nOptions(_defaultLocale)), __temp = await __temp, __restore(), __temp);
    const localeConfigs = useLocaleConfigs();
    {
      localeConfigs.value = useRequestEvent().context.nuxtI18n?.localeConfigs || {};
    }
    prerenderRoutes(localeCodes.map((locale) => `${"/_i18n/SeXYkDvK"}/${locale}/messages.json`));
    const i18n = createI18n(optionsI18n);
    const detectors = useDetectors(useRequestEvent(nuxt), useI18nDetection(nuxt), nuxt);
    const ctx = createNuxtI18nContext(nuxt, i18n, optionsI18n.defaultLocale);
    nuxt._nuxtI18n = ctx;
    extendI18n(i18n, {
      extendComposer(composer) {
        composer.locales = computed(() => runtimeI18n.locales);
        composer.localeCodes = computed(() => localeCodes);
        const _baseUrl = ref(ctx.getBaseUrl());
        composer.baseUrl = computed(() => _baseUrl.value);
        composer.strategy = "no_prefix";
        composer.localeProperties = computed(
          () => normalizedLocales.find((l) => l.code === composer.locale.value) || { code: composer.locale.value }
        );
        composer.setLocale = async (locale) => {
          await loadAndSetLocale(nuxt, locale);
          await nuxt.runWithContext(() => navigate(nuxt, nuxt.$router.currentRoute.value));
        };
        composer.loadLocaleMessages = ctx.loadMessages;
        composer.differentDomains = false;
        composer.defaultLocale = optionsI18n.defaultLocale;
        composer.getBrowserLocale = () => resolveSupportedLocale(detectors.header());
        composer.getLocaleCookie = () => resolveSupportedLocale(detectors.cookie());
        composer.setLocaleCookie = ctx.setCookieLocale;
        composer.finalizePendingLocaleChange = async () => {
          if (!i18n.__pendingLocale) {
            return;
          }
          await i18n.__resolvePendingLocalePromise?.();
        };
        composer.waitForPendingLocaleChange = async () => {
          await i18n?.__pendingLocalePromise;
        };
      },
      extendComposerInstance(instance, c) {
        const props = [
          ["locales", () => c.locales],
          ["localeCodes", () => c.localeCodes],
          ["baseUrl", () => c.baseUrl],
          ["strategy", () => "no_prefix"],
          ["localeProperties", () => c.localeProperties],
          ["setLocale", () => (locale) => Reflect.apply(c.setLocale, c, [locale])],
          ["loadLocaleMessages", () => (locale) => Reflect.apply(c.loadLocaleMessages, c, [locale])],
          ["differentDomains", () => false],
          ["defaultLocale", () => c.defaultLocale],
          ["getBrowserLocale", () => () => Reflect.apply(c.getBrowserLocale, c, [])],
          ["getLocaleCookie", () => () => Reflect.apply(c.getLocaleCookie, c, [])],
          ["setLocaleCookie", () => (locale) => Reflect.apply(c.setLocaleCookie, c, [locale])],
          ["finalizePendingLocaleChange", () => () => Reflect.apply(c.finalizePendingLocaleChange, c, [])],
          ["waitForPendingLocaleChange", () => () => Reflect.apply(c.waitForPendingLocaleChange, c, [])]
        ];
        for (const [key, get] of props) {
          Object.defineProperty(instance, key, { get });
        }
      }
    });
    nuxt.vueApp.use(i18n);
    Object.defineProperty(nuxt, "$i18n", { get: () => getI18nTarget(i18n) });
    nuxt.provide("localeHead", (options) => localeHead(nuxt._nuxtI18n.composableCtx, options));
    nuxt.provide("localePath", useLocalePath(nuxt));
    nuxt.provide("localeRoute", useLocaleRoute(nuxt));
    nuxt.provide("routeBaseName", useRouteBaseName(nuxt));
    nuxt.provide("getRouteBaseName", useRouteBaseName(nuxt));
    nuxt.provide("switchLocalePath", useSwitchLocalePath(nuxt));
  }
});
const preference = "system";
const plugin_server_9Ca9_HhnjAGwBWpwAydRauMHxWoxTDY60BrArRnXN_A = /* @__PURE__ */ defineNuxtPlugin((nuxtApp) => {
  const colorMode = nuxtApp.ssrContext?.islandContext ? ref({}).value : useState("color-mode", () => reactive({
    preference,
    value: preference,
    unknown: true,
    forced: false
  })).value;
  const htmlAttrs = {};
  useHead({ htmlAttrs });
  useRouter().afterEach((to) => {
    const forcedColorMode = to.meta.colorMode;
    if (forcedColorMode && forcedColorMode !== "system") {
      htmlAttrs["data-color-mode-forced"] = forcedColorMode;
      colorMode.value = forcedColorMode;
      colorMode.forced = true;
    } else if (forcedColorMode === "system") {
      console.warn("You cannot force the colorMode to system at the page level.");
    }
  });
  nuxtApp.provide("colorMode", colorMode);
});
const api_jy9dy79pM_nYQuRKPrcfNg56p_gKkzw9SekUTZgAenc = /* @__PURE__ */ defineNuxtPlugin(() => {
  const apiFetch = $fetch.create({
    baseURL: (/* @__PURE__ */ useRuntimeConfig()).public.apiBase,
    credentials: "include",
    // Include cookies for CORS
    async onRequest({ options }) {
      const tokenCookie = useCookie("token");
      const token = tokenCookie.value;
      if (token) {
        options.headers = {
          ...options.headers || {},
          Authorization: `Bearer ${token}`,
          Accept: "application/json"
          // Ensure Laravel returns JSON instead of redirects
        };
      } else {
        options.headers = {
          ...options.headers || {},
          Accept: "application/json"
        };
      }
    },
    async onResponseError({ response }) {
      if (response.status === 401) {
        const authStore = useAuthStore();
        const token = authStore.token?.value;
        if (token && !authStore.isRefreshing) {
          const refreshed = await authStore.refreshToken();
          if (!refreshed) {
            authStore.logout();
          }
        } else if (!token) {
          navigateTo("/auth");
        }
      }
    }
  });
  return {
    provide: {
      apiFetch
    }
  };
});
const auth_ydS_uAQ5qS4E2Ez9vBX0d0JPoo00WYZNOgh9_nraCUo = /* @__PURE__ */ defineNuxtPlugin(async (nuxtApp) => {
  let __temp, __restore;
  const authStore = useAuthStore();
  const token = useCookie("token");
  if (token.value && !authStore.user) {
    try {
      ;
      [__temp, __restore] = executeAsync(() => authStore.fetchUser()), await __temp, __restore();
      ;
    } catch (error) {
      if (error?.statusCode !== 401 && !error?.message?.includes("401")) {
        console.error("Failed to fetch user on init", error);
      }
      token.value = null;
      authStore.user = null;
    }
  }
});
const click_outside_I4ttNMqHV6xHGjmJUbCn_KeHhF4cThlycOt7HfJGaS4 = /* @__PURE__ */ defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive("click-outside", {
    mounted(el, binding) {
      el._clickOutsideHandler = (event) => {
        if (!(el === event.target || el.contains(event.target))) {
          binding.value(event);
        }
      };
      (void 0).addEventListener("click", el._clickOutsideHandler);
    },
    beforeUnmount(el) {
      (void 0).removeEventListener("click", el._clickOutsideHandler);
      delete el._clickOutsideHandler;
    }
  });
});
function createRouteHelper(getCurrentPath) {
  const resolveName = (name) => {
    const map = {
      welcome: "/",
      newsfeed: "/play/newsfeed",
      dashboard: "/dashboard",
      "profile.show": "/profile",
      logout: "/logout"
    };
    if (name in map) return map[name];
    if (name.startsWith("/")) return name;
    return `/${name.replace(/\./g, "/")}`;
  };
  const routeFn = (name) => {
    if (!name) {
      return {
        current: (target) => {
          const currentPath = getCurrentPath();
          const expected = resolveName(target);
          return currentPath === expected || currentPath.startsWith(expected + "/");
        }
      };
    }
    return resolveName(name);
  };
  return routeFn;
}
const inertia_shim_ShDNVavl4jvGlYW_AaLJHEpWG0ixri6_LzxOuY72QhI = /* @__PURE__ */ defineNuxtPlugin((nuxtApp) => {
  const authStore = useAuthStore();
  const page = reactive({
    props: {
      auth: {
        user: authStore.user || null
      },
      jetstream: {
        managesProfilePhotos: true
      },
      // Provide safe nested shapes used by some legacy stores/components.
      course: { data: {} },
      courseMemberOfAuth: null
    },
    url: "/"
  });
  const updateUrl = () => {
    const fullPath = nuxtApp.$router?.currentRoute?.value?.fullPath;
    page.url = typeof fullPath === "string" ? fullPath : "/";
    if (!authStore.user && page.props.auth.user) {
      authStore.user = page.props.auth.user;
    } else if (authStore.user && !page.props.auth.user) {
      page.props.auth.user = authStore.user;
    }
    if (authStore.user) {
      page.props.auth.user = authStore.user;
    }
  };
  updateUrl();
  nuxtApp.hook("page:finish", updateUrl);
  const route = createRouteHelper(() => page.url);
  nuxtApp.provide("inertiaPage", page);
  nuxtApp.vueApp.config.globalProperties.$page = page;
  nuxtApp.vueApp.config.globalProperties.route = route;
});
const ssg_detect_IpHCGcQQ_IR5Rl99qyukWoMA9fJGfuTYyoksTzy81cs = /* @__PURE__ */ defineNuxtPlugin({
  name: "i18n:plugin:ssg-detect",
  dependsOn: ["i18n:plugin", "i18n:plugin:route-locale-detect"],
  enforce: "post",
  setup(_nuxt) {
    {
      return;
    }
  }
});
const plugins = [
  payloadPlugin,
  unhead_k2P3m_ZDyjlr2mMYnoDPwavjsDN8hBlk9cFai0bbopU,
  plugin$1,
  revive_payload_server_MVtmlZaQpj6ApFmshWfUWl5PehCebzaBf2NuRMiIbms,
  plugin,
  components_plugin_z4hgvsiddfKkfXTP6M8M4zG5Cb7sGnDhcryKVM45Di4,
  switch_locale_path_ssr_NflG9_QeVcJ1jVig0vCfxB_cZhpEMQ9U2ujRUiYbbVw,
  route_locale_detect__HPHJq3Jg7gwhwgKEI8tQavopSAjmrCSPXl9HgL2h9U,
  preload_30FByJAs5vQa4mNNQLX15KPGCCVjIGrzdTjh6ve5W24,
  i18n_EI7LsD1KYQADczz5hrChviGQCdVM8yUkvFEZLJpmnvM,
  plugin_server_9Ca9_HhnjAGwBWpwAydRauMHxWoxTDY60BrArRnXN_A,
  api_jy9dy79pM_nYQuRKPrcfNg56p_gKkzw9SekUTZgAenc,
  auth_ydS_uAQ5qS4E2Ez9vBX0d0JPoo00WYZNOgh9_nraCUo,
  click_outside_I4ttNMqHV6xHGjmJUbCn_KeHhF4cThlycOt7HfJGaS4,
  inertia_shim_ShDNVavl4jvGlYW_AaLJHEpWG0ixri6_LzxOuY72QhI,
  ssg_detect_IpHCGcQQ_IR5Rl99qyukWoMA9fJGfuTYyoksTzy81cs
];
const layouts = {
  "academy-layout": defineAsyncComponent(() => import('./AcademyLayout-CHtGHy8-.mjs').then((m) => m.default || m)),
  "course-layout": defineAsyncComponent(() => import('./CourseLayout-DYixP6h3.mjs').then((m) => m.default || m)),
  "courses-layout": defineAsyncComponent(() => import('./CoursesLayout-Bw6-53KN.mjs').then((m) => m.default || m)),
  "game-layout": defineAsyncComponent(() => import('./GameLayout-DW3Yp05e.mjs').then((m) => m.default || m)),
  "guest-layout": defineAsyncComponent(() => import('./GuestLayout-BXfx4_cL.mjs').then((m) => m.default || m)),
  "nuxnan-admin-layout": defineAsyncComponent(() => import('./NuxnanAdminLayout-BvYXVC70.mjs').then((m) => m.default || m)),
  auth: defineAsyncComponent(() => import('./auth-D-xhbOgu.mjs').then((m) => m.default || m)),
  course: defineAsyncComponent(() => import('./course-CPGvgq5p.mjs').then((m) => m.default || m)),
  main: defineAsyncComponent(() => import('./main-BqvhuwHD.mjs').then((m) => m.default || m))
};
const LayoutLoader = defineComponent({
  name: "LayoutLoader",
  inheritAttrs: false,
  props: {
    name: String,
    layoutProps: Object
  },
  setup(props, context) {
    return () => h(layouts[props.name], props.layoutProps, context.slots);
  }
});
const nuxtLayoutProps = {
  name: {
    type: [String, Boolean, Object],
    default: null
  },
  fallback: {
    type: [String, Object],
    default: null
  }
};
const __nuxt_component_0$1 = defineComponent({
  name: "NuxtLayout",
  inheritAttrs: false,
  props: nuxtLayoutProps,
  setup(props, context) {
    const nuxtApp = useNuxtApp();
    const injectedRoute = inject(PageRouteSymbol);
    const shouldUseEagerRoute = !injectedRoute || injectedRoute === useRoute();
    const route = shouldUseEagerRoute ? useRoute$1() : injectedRoute;
    const layout = computed(() => {
      let layout2 = unref(props.name) ?? route?.meta.layout ?? "default";
      if (layout2 && !(layout2 in layouts)) {
        if (props.fallback) {
          layout2 = unref(props.fallback);
        }
      }
      return layout2;
    });
    const layoutRef = shallowRef();
    context.expose({ layoutRef });
    const done = nuxtApp.deferHydration();
    let lastLayout;
    return () => {
      const hasLayout = layout.value && layout.value in layouts;
      const transitionProps = route?.meta.layoutTransition ?? appLayoutTransition;
      const previouslyRenderedLayout = lastLayout;
      lastLayout = layout.value;
      return _wrapInTransition(hasLayout && transitionProps, {
        default: () => h(Suspense, { suspensible: true, onResolve: () => {
          nextTick(done);
        } }, {
          default: () => h(
            LayoutProvider,
            {
              layoutProps: mergeProps(context.attrs, { ref: layoutRef }),
              key: layout.value || void 0,
              name: layout.value,
              shouldProvide: !props.name,
              isRenderingNewLayout: (name) => {
                return name !== previouslyRenderedLayout && name === layout.value;
              },
              hasTransition: !!transitionProps
            },
            context.slots
          )
        })
      }).default();
    };
  }
});
const LayoutProvider = defineComponent({
  name: "NuxtLayoutProvider",
  inheritAttrs: false,
  props: {
    name: {
      type: [String, Boolean]
    },
    layoutProps: {
      type: Object
    },
    hasTransition: {
      type: Boolean
    },
    shouldProvide: {
      type: Boolean
    },
    isRenderingNewLayout: {
      type: Function,
      required: true
    }
  },
  setup(props, context) {
    const name = props.name;
    if (props.shouldProvide) {
      provide(LayoutMetaSymbol, {
        isCurrent: (route) => name === (route.meta.layout ?? "default")
      });
    }
    const injectedRoute = inject(PageRouteSymbol);
    const isNotWithinNuxtPage = injectedRoute && injectedRoute === useRoute();
    if (isNotWithinNuxtPage) {
      const vueRouterRoute = useRoute$1();
      const reactiveChildRoute = {};
      for (const _key in vueRouterRoute) {
        const key = _key;
        Object.defineProperty(reactiveChildRoute, key, {
          enumerable: true,
          get: () => {
            return props.isRenderingNewLayout(props.name) ? vueRouterRoute[key] : injectedRoute[key];
          }
        });
      }
      provide(PageRouteSymbol, shallowReactive(reactiveChildRoute));
    }
    return () => {
      if (!name || typeof name === "string" && !(name in layouts)) {
        return context.slots.default?.();
      }
      return h(
        LayoutLoader,
        { key: name, layoutProps: props.layoutProps, name },
        context.slots
      );
    };
  }
});
const defineRouteProvider = (name = "RouteProvider") => defineComponent({
  name,
  props: {
    route: {
      type: Object,
      required: true
    },
    vnode: Object,
    vnodeRef: Object,
    renderKey: String,
    trackRootNodes: Boolean
  },
  setup(props) {
    const previousKey = props.renderKey;
    const previousRoute = props.route;
    const route = {};
    for (const key in props.route) {
      Object.defineProperty(route, key, {
        get: () => previousKey === props.renderKey ? props.route[key] : previousRoute[key],
        enumerable: true
      });
    }
    provide(PageRouteSymbol, shallowReactive(route));
    return () => {
      if (!props.vnode) {
        return props.vnode;
      }
      return h(props.vnode, { ref: props.vnodeRef });
    };
  }
});
const RouteProvider = defineRouteProvider();
const __nuxt_component_0 = defineComponent({
  name: "NuxtPage",
  inheritAttrs: false,
  props: {
    name: {
      type: String
    },
    transition: {
      type: [Boolean, Object],
      default: void 0
    },
    keepalive: {
      type: [Boolean, Object],
      default: void 0
    },
    route: {
      type: Object
    },
    pageKey: {
      type: [Function, String],
      default: null
    }
  },
  setup(props, { attrs, slots, expose }) {
    const nuxtApp = useNuxtApp();
    const pageRef = ref();
    inject(PageRouteSymbol, null);
    expose({ pageRef });
    inject(LayoutMetaSymbol, null);
    nuxtApp.deferHydration();
    return () => {
      return h(RouterView, { name: props.name, route: props.route, ...attrs }, {
        default: (routeProps) => {
          return h(Suspense, { suspensible: true }, {
            default() {
              return h(RouteProvider, {
                vnode: slots.default ? normalizeSlot(slots.default, routeProps) : routeProps.Component,
                route: routeProps.route,
                vnodeRef: pageRef
              });
            }
          });
        }
      });
    };
  }
});
function normalizeSlot(slot, data) {
  const slotContent = slot(data);
  return slotContent.length === 1 ? h(slotContent[0]) : h(Fragment, void 0, slotContent);
}
const useNotificationStore = defineStore("notification", {
  state: () => ({
    notifications: []
  }),
  actions: {
    add(notification) {
      const id = `${Date.now()}-${Math.random()}`;
      const duration = notification.duration ?? 3e3;
      const defaultIcons = {
        success: "fluent:checkmark-circle-24-filled",
        error: "fluent:dismiss-circle-24-filled",
        warning: "fluent:warning-24-filled",
        info: "fluent:info-24-filled"
      };
      const newNotification = {
        id,
        icon: notification.icon || defaultIcons[notification.type],
        ...notification,
        duration
      };
      this.notifications.push(newNotification);
      if (duration > 0) {
        setTimeout(() => {
          this.remove(id);
        }, duration);
      }
      return id;
    },
    remove(id) {
      const index = this.notifications.findIndex((n) => n.id === id);
      if (index > -1) {
        this.notifications.splice(index, 1);
      }
    },
    clear() {
      this.notifications = [];
    },
    // Convenience methods
    success(message, title, duration) {
      return this.add({ type: "success", message, title, duration });
    },
    error(message, title, duration) {
      return this.add({ type: "error", message, title, duration });
    },
    warning(message, title, duration) {
      return this.add({ type: "warning", message, title, duration });
    },
    info(message, title, duration) {
      return this.add({ type: "info", message, title, duration });
    }
  }
});
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main$3 = {
  __name: "ToastNotification",
  __ssrInlineRender: true,
  setup(__props) {
    const notificationStore = useNotificationStore();
    const { notifications } = storeToRefs(notificationStore);
    const toastClasses = {
      success: "bg-green-50/95 dark:bg-green-900/30 border-green-200 dark:border-green-800",
      error: "bg-red-50/95 dark:bg-red-900/30 border-red-200 dark:border-red-800",
      warning: "bg-yellow-50/95 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800",
      info: "bg-blue-50/95 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800"
    };
    const iconColors = {
      success: "text-green-500",
      error: "text-red-500",
      warning: "text-yellow-500",
      info: "text-blue-500"
    };
    const defaultIcons = {
      success: "fluent:checkmark-circle-24-filled",
      error: "fluent:dismiss-circle-24-filled",
      warning: "fluent:warning-24-filled",
      info: "fluent:info-24-filled"
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed top-4 right-4 z-[9999] space-y-2 pointer-events-none" }, _attrs))} data-v-6b793501><!--[-->`);
      ssrRenderList(unref(notifications), (notification) => {
        _push(`<div class="pointer-events-auto w-96 max-w-full" data-v-6b793501><div class="${ssrRenderClass([
          "flex items-start gap-3 p-4 rounded-xl shadow-lg backdrop-blur-sm border transition-all duration-300 hover:scale-105",
          toastClasses[notification.type]
        ])}" data-v-6b793501><div class="${ssrRenderClass(["flex-shrink-0 w-6 h-6", iconColors[notification.type]])}" data-v-6b793501>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: notification.icon || defaultIcons[notification.type],
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</div><div class="flex-1 min-w-0" data-v-6b793501>`);
        if (notification.title) {
          _push(`<h4 class="font-semibold text-gray-900 dark:text-white mb-0.5" data-v-6b793501>${ssrInterpolate(notification.title)}</h4>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<p class="text-sm text-gray-700 dark:text-gray-300" data-v-6b793501>${ssrInterpolate(notification.message)}</p></div><button class="flex-shrink-0 p-1 hover:bg-black/5 dark:hover:bg-white/5 rounded-lg transition-colors" data-v-6b793501>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-4 h-4 text-gray-500 dark:text-gray-400"
        }, null, _parent));
        _push(`</button></div></div>`);
      });
      _push(`<!--]--></div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/notifications/ToastNotification.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const __nuxt_component_2 = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-6b793501"]]);
const useColorMode = () => {
  return useState("color-mode").value;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "app",
  __ssrInlineRender: true,
  setup(__props) {
    useColorMode();
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLayout = __nuxt_component_0$1;
      const _component_NuxtPage = __nuxt_component_0;
      const _component_NotificationsToastNotification = __nuxt_component_2;
      _push(ssrRenderComponent(_component_NuxtLayout, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_NuxtPage, null, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_component_NotificationsToastNotification, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_NuxtPage),
              createVNode(_component_NotificationsToastNotification)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("app.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "error",
  __ssrInlineRender: true,
  props: {
    error: {}
  },
  setup(__props) {
    const props = __props;
    useHead({
      title: `${props.error?.statusCode || 404} - Page Not Found`
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 flex items-center justify-center p-4" }, _attrs))}><div class="text-center"><div class="mb-6">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:alert-circle",
        class: "w-24 h-24 text-primary-500 mx-auto mb-4"
      }, null, _parent));
      _push(`</div><h1 class="text-6xl font-bold text-secondary-900 mb-4">${ssrInterpolate(__props.error?.statusCode || 404)}</h1><h2 class="text-2xl font-semibold text-secondary-700 mb-4">Page Not Found</h2><p class="text-secondary-600 mb-8"> Sorry, the page you are looking for doesn&#39;t exist or has been moved. </p><button class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors cursor-pointer">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:home",
        class: "w-5 h-5"
      }, null, _parent));
      _push(` Back to Home </button></div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("error.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "nuxt-root",
  __ssrInlineRender: true,
  setup(__props) {
    const IslandRenderer = () => null;
    const nuxtApp = useNuxtApp();
    nuxtApp.deferHydration();
    nuxtApp.ssrContext.url;
    const SingleRenderer = false;
    provide(PageRouteSymbol, useRoute());
    nuxtApp.hooks.callHookWith((hooks) => hooks.map((hook) => hook()), "vue:setup");
    const error = /* @__PURE__ */ useError();
    const abortRender = error.value && !nuxtApp.ssrContext.error;
    onErrorCaptured((err, target, info) => {
      nuxtApp.hooks.callHook("vue:error", err, target, info).catch((hookError) => console.error("[nuxt] Error in `vue:error` hook", hookError));
      {
        const p = nuxtApp.runWithContext(() => showError(err));
        onServerPrefetch(() => p);
        return false;
      }
    });
    const islandContext = nuxtApp.ssrContext.islandContext;
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderSuspense(_push, {
        default: () => {
          if (unref(abortRender)) {
            _push(`<div></div>`);
          } else if (unref(error)) {
            _push(ssrRenderComponent(unref(_sfc_main$1), { error: unref(error) }, null, _parent));
          } else if (unref(islandContext)) {
            _push(ssrRenderComponent(unref(IslandRenderer), { context: unref(islandContext) }, null, _parent));
          } else if (unref(SingleRenderer)) {
            ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(SingleRenderer)), null, null), _parent);
          } else {
            _push(ssrRenderComponent(unref(_sfc_main$2), null, null, _parent));
          }
        },
        _: 1
      });
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/nuxt/dist/app/components/nuxt-root.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
let entry;
{
  entry = async function createNuxtAppServer(ssrContext) {
    const vueApp = createApp(_sfc_main);
    const nuxt = createNuxtApp({ vueApp, ssrContext });
    try {
      await applyPlugins(nuxt, plugins);
      await nuxt.hooks.callHook("app:created", vueApp);
    } catch (error) {
      await nuxt.hooks.callHook("app:error", error);
      nuxt.payload.error ||= createError(error);
    }
    if (ssrContext?._renderResponse) {
      throw new Error("skipping render");
    }
    return vueApp;
  };
}
const entry_default = (ssrContext) => entry(ssrContext);

export { _export_sfc as _, useNuxtApp as a, useRuntimeConfig as b, nuxtLinkDefaults as c, useAuthStore as d, entry_default as default, useNotificationStore as e, useHead as f, asyncDataDefaults as g, createError as h, useApi as i, __nuxt_component_0$2 as j, defineNuxtRouteMiddleware as k, __nuxt_component_0$1 as l, fetchDefaults as m, navigateTo as n, useRequestFetch as o, useRoute as p, __nuxt_component_0 as q, resolveRouteObject as r, useCourseStore as s, useSeoMeta as t, useRouter as u, useColorMode as v };
//# sourceMappingURL=server.mjs.map
