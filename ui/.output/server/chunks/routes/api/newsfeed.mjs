import { c as defineEventHandler, g as getHeader, e as createError, u as useRuntimeConfig, f as getQuery } from '../../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'vue-router';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';

const newsfeed = defineEventHandler(async (event) => {
  try {
    const authHeader = getHeader(event, "authorization");
    if (!authHeader) {
      throw createError({
        statusCode: 401,
        statusMessage: "Unauthorized - No token provided"
      });
    }
    const config = useRuntimeConfig();
    const apiBase = config.public.apiBase;
    const query = getQuery(event);
    const queryString = new URLSearchParams(query).toString();
    const url = queryString ? `${apiBase}/api/newsfeed?${queryString}` : `${apiBase}/api/newsfeed`;
    const response = await $fetch(url, {
      headers: {
        "Authorization": authHeader,
        "Accept": "application/json"
      }
    });
    return response;
  } catch (error) {
    console.error("Error in /api/newsfeed:", error);
    throw createError({
      statusCode: error.statusCode || error.status || 500,
      statusMessage: error.statusMessage || error.message || "Internal Server Error"
    });
  }
});

export { newsfeed as default };
//# sourceMappingURL=newsfeed.mjs.map
