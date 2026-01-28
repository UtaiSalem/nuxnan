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

const logout = defineEventHandler(async (event) => {
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
      `${config.public.apiBase}/api/logout`,
      {
        method: "POST",
        headers: {
          Authorization: authHeader
        }
      }
    );
    return response;
  } catch (error) {
    console.error("Logout API error:", error);
    return { message: "Logged out" };
  }
});

export { logout as default };
//# sourceMappingURL=logout.mjs.map
