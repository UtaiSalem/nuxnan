import { d as defineEventHandler, g as getHeader, u as useRuntimeConfig, c as createError } from '../../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'vue-router';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';

const me = defineEventHandler(async (event) => {
  var _a, _b;
  try {
    const authHeader = getHeader(event, "authorization");
    const config = useRuntimeConfig();
    if (!authHeader) {
      throw createError({
        statusCode: 401,
        statusMessage: "No authorization token provided"
      });
    }
    const response = await $fetch(
      `${config.public.apiBase}/api/auth/me`,
      {
        method: "GET",
        headers: {
          Authorization: authHeader
        }
      }
    );
    return response;
  } catch (error) {
    console.error("Get user API error:", error);
    throw createError({
      statusCode: error.statusCode || ((_a = error.response) == null ? void 0 : _a.status) || 401,
      statusMessage: ((_b = error.data) == null ? void 0 : _b.message) || error.message || "Failed to fetch user"
    });
  }
});

export { me as default };
//# sourceMappingURL=me.mjs.map
