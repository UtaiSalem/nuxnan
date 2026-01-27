import { k as defineNuxtRouteMiddleware, d as useAuthStore, n as navigateTo } from './server.mjs';
import 'vue';
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
import 'vue/server-renderer';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const nuxnanAdmin = defineNuxtRouteMiddleware((to, from) => {
  var _a, _b;
  const authStore = useAuthStore();
  if (!authStore.isAuthenticated) {
    return navigateTo("/nuxnan-admin/login");
  }
  if (!((_a = authStore.user) == null ? void 0 : _a.is_plearnd_admin) && !((_b = authStore.user) == null ? void 0 : _b.is_super_admin)) {
    return navigateTo("/nuxnan-admin/login?error=unauthorized");
  }
});

export { nuxnanAdmin as default };
//# sourceMappingURL=nuxnan-admin-DXeJnF6M.mjs.map
