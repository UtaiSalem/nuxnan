import { d as defineEventHandler, r as readBody, u as useRuntimeConfig, c as createError } from '../../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'vue-router';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';

const register = defineEventHandler(async (event) => {
  var _a, _b;
  try {
    const body = await readBody(event);
    const config = useRuntimeConfig();
    const response = await $fetch(
      `${config.public.apiBase}/api/register`,
      {
        method: "POST",
        body
      }
    );
    return response;
  } catch (error) {
    console.error("Register API error:", error);
    throw createError({
      statusCode: error.statusCode || ((_a = error.response) == null ? void 0 : _a.status) || 422,
      statusMessage: ((_b = error.data) == null ? void 0 : _b.message) || error.message || "Registration failed"
    });
  }
});

export { register as default };
//# sourceMappingURL=register.mjs.map
