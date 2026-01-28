import process from 'node:process';globalThis._importMeta_=globalThis._importMeta_||{url:"file:///_entry.js",env:process.env};import http from 'node:http';
import https from 'node:https';
import { EventEmitter } from 'node:events';
import { Buffer as Buffer$1 } from 'node:buffer';
import { createRouterMatcher } from 'vue-router';
import { promises, existsSync } from 'node:fs';
import { resolve as resolve$1, dirname as dirname$1, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';

const suspectProtoRx = /"(?:_|\\u0{2}5[Ff]){2}(?:p|\\u0{2}70)(?:r|\\u0{2}72)(?:o|\\u0{2}6[Ff])(?:t|\\u0{2}74)(?:o|\\u0{2}6[Ff])(?:_|\\u0{2}5[Ff]){2}"\s*:/;
const suspectConstructorRx = /"(?:c|\\u0063)(?:o|\\u006[Ff])(?:n|\\u006[Ee])(?:s|\\u0073)(?:t|\\u0074)(?:r|\\u0072)(?:u|\\u0075)(?:c|\\u0063)(?:t|\\u0074)(?:o|\\u006[Ff])(?:r|\\u0072)"\s*:/;
const JsonSigRx = /^\s*["[{]|^\s*-?\d{1,16}(\.\d{1,17})?([Ee][+-]?\d+)?\s*$/;
function jsonParseTransform(key, value) {
  if (key === "__proto__" || key === "constructor" && value && typeof value === "object" && "prototype" in value) {
    warnKeyDropped(key);
    return;
  }
  return value;
}
function warnKeyDropped(key) {
  console.warn(`[destr] Dropping "${key}" key to prevent prototype pollution.`);
}
function destr(value, options = {}) {
  if (typeof value !== "string") {
    return value;
  }
  if (value[0] === '"' && value[value.length - 1] === '"' && value.indexOf("\\") === -1) {
    return value.slice(1, -1);
  }
  const _value = value.trim();
  if (_value.length <= 9) {
    switch (_value.toLowerCase()) {
      case "true": {
        return true;
      }
      case "false": {
        return false;
      }
      case "undefined": {
        return void 0;
      }
      case "null": {
        return null;
      }
      case "nan": {
        return Number.NaN;
      }
      case "infinity": {
        return Number.POSITIVE_INFINITY;
      }
      case "-infinity": {
        return Number.NEGATIVE_INFINITY;
      }
    }
  }
  if (!JsonSigRx.test(value)) {
    if (options.strict) {
      throw new SyntaxError("[destr] Invalid JSON");
    }
    return value;
  }
  try {
    if (suspectProtoRx.test(value) || suspectConstructorRx.test(value)) {
      if (options.strict) {
        throw new Error("[destr] Possible prototype pollution");
      }
      return JSON.parse(value, jsonParseTransform);
    }
    return JSON.parse(value);
  } catch (error) {
    if (options.strict) {
      throw error;
    }
    return value;
  }
}

const HASH_RE = /#/g;
const AMPERSAND_RE = /&/g;
const SLASH_RE = /\//g;
const EQUAL_RE = /=/g;
const PLUS_RE = /\+/g;
const ENC_CARET_RE = /%5e/gi;
const ENC_BACKTICK_RE = /%60/gi;
const ENC_PIPE_RE = /%7c/gi;
const ENC_SPACE_RE = /%20/gi;
const ENC_SLASH_RE = /%2f/gi;
function encode(text) {
  return encodeURI("" + text).replace(ENC_PIPE_RE, "|");
}
function encodeQueryValue(input) {
  return encode(typeof input === "string" ? input : JSON.stringify(input)).replace(PLUS_RE, "%2B").replace(ENC_SPACE_RE, "+").replace(HASH_RE, "%23").replace(AMPERSAND_RE, "%26").replace(ENC_BACKTICK_RE, "`").replace(ENC_CARET_RE, "^").replace(SLASH_RE, "%2F");
}
function encodeQueryKey(text) {
  return encodeQueryValue(text).replace(EQUAL_RE, "%3D");
}
function decode$1(text = "") {
  try {
    return decodeURIComponent("" + text);
  } catch {
    return "" + text;
  }
}
function decodePath(text) {
  return decode$1(text.replace(ENC_SLASH_RE, "%252F"));
}
function decodeQueryKey(text) {
  return decode$1(text.replace(PLUS_RE, " "));
}
function decodeQueryValue(text) {
  return decode$1(text.replace(PLUS_RE, " "));
}

function parseQuery(parametersString = "") {
  const object = /* @__PURE__ */ Object.create(null);
  if (parametersString[0] === "?") {
    parametersString = parametersString.slice(1);
  }
  for (const parameter of parametersString.split("&")) {
    const s = parameter.match(/([^=]+)=?(.*)/) || [];
    if (s.length < 2) {
      continue;
    }
    const key = decodeQueryKey(s[1]);
    if (key === "__proto__" || key === "constructor") {
      continue;
    }
    const value = decodeQueryValue(s[2] || "");
    if (object[key] === void 0) {
      object[key] = value;
    } else if (Array.isArray(object[key])) {
      object[key].push(value);
    } else {
      object[key] = [object[key], value];
    }
  }
  return object;
}
function encodeQueryItem(key, value) {
  if (typeof value === "number" || typeof value === "boolean") {
    value = String(value);
  }
  if (!value) {
    return encodeQueryKey(key);
  }
  if (Array.isArray(value)) {
    return value.map(
      (_value) => `${encodeQueryKey(key)}=${encodeQueryValue(_value)}`
    ).join("&");
  }
  return `${encodeQueryKey(key)}=${encodeQueryValue(value)}`;
}
function stringifyQuery(query) {
  return Object.keys(query).filter((k) => query[k] !== void 0).map((k) => encodeQueryItem(k, query[k])).filter(Boolean).join("&");
}

const PROTOCOL_STRICT_REGEX = /^[\s\w\0+.-]{2,}:([/\\]{1,2})/;
const PROTOCOL_REGEX = /^[\s\w\0+.-]{2,}:([/\\]{2})?/;
const PROTOCOL_RELATIVE_REGEX = /^([/\\]\s*){2,}[^/\\]/;
const JOIN_LEADING_SLASH_RE = /^\.?\//;
function hasProtocol(inputString, opts = {}) {
  if (typeof opts === "boolean") {
    opts = { acceptRelative: opts };
  }
  if (opts.strict) {
    return PROTOCOL_STRICT_REGEX.test(inputString);
  }
  return PROTOCOL_REGEX.test(inputString) || (opts.acceptRelative ? PROTOCOL_RELATIVE_REGEX.test(inputString) : false);
}
function hasTrailingSlash(input = "", respectQueryAndFragment) {
  {
    return input.endsWith("/");
  }
}
function withoutTrailingSlash(input = "", respectQueryAndFragment) {
  {
    return (hasTrailingSlash(input) ? input.slice(0, -1) : input) || "/";
  }
}
function withTrailingSlash(input = "", respectQueryAndFragment) {
  {
    return input.endsWith("/") ? input : input + "/";
  }
}
function hasLeadingSlash(input = "") {
  return input.startsWith("/");
}
function withLeadingSlash(input = "") {
  return hasLeadingSlash(input) ? input : "/" + input;
}
function withBase(input, base) {
  if (isEmptyURL(base) || hasProtocol(input)) {
    return input;
  }
  const _base = withoutTrailingSlash(base);
  if (input.startsWith(_base)) {
    const nextChar = input[_base.length];
    if (!nextChar || nextChar === "/" || nextChar === "?") {
      return input;
    }
  }
  return joinURL(_base, input);
}
function withoutBase(input, base) {
  if (isEmptyURL(base)) {
    return input;
  }
  const _base = withoutTrailingSlash(base);
  if (!input.startsWith(_base)) {
    return input;
  }
  const nextChar = input[_base.length];
  if (nextChar && nextChar !== "/" && nextChar !== "?") {
    return input;
  }
  const trimmed = input.slice(_base.length);
  return trimmed[0] === "/" ? trimmed : "/" + trimmed;
}
function withQuery(input, query) {
  const parsed = parseURL(input);
  const mergedQuery = { ...parseQuery(parsed.search), ...query };
  parsed.search = stringifyQuery(mergedQuery);
  return stringifyParsedURL(parsed);
}
function getQuery$1(input) {
  return parseQuery(parseURL(input).search);
}
function isEmptyURL(url) {
  return !url || url === "/";
}
function isNonEmptyURL(url) {
  return url && url !== "/";
}
function joinURL(base, ...input) {
  let url = base || "";
  for (const segment of input.filter((url2) => isNonEmptyURL(url2))) {
    if (url) {
      const _segment = segment.replace(JOIN_LEADING_SLASH_RE, "");
      url = withTrailingSlash(url) + _segment;
    } else {
      url = segment;
    }
  }
  return url;
}
function joinRelativeURL(..._input) {
  const JOIN_SEGMENT_SPLIT_RE = /\/(?!\/)/;
  const input = _input.filter(Boolean);
  const segments = [];
  let segmentsDepth = 0;
  for (const i of input) {
    if (!i || i === "/") {
      continue;
    }
    for (const [sindex, s] of i.split(JOIN_SEGMENT_SPLIT_RE).entries()) {
      if (!s || s === ".") {
        continue;
      }
      if (s === "..") {
        if (segments.length === 1 && hasProtocol(segments[0])) {
          continue;
        }
        segments.pop();
        segmentsDepth--;
        continue;
      }
      if (sindex === 1 && segments[segments.length - 1]?.endsWith(":/")) {
        segments[segments.length - 1] += "/" + s;
        continue;
      }
      segments.push(s);
      segmentsDepth++;
    }
  }
  let url = segments.join("/");
  if (segmentsDepth >= 0) {
    if (input[0]?.startsWith("/") && !url.startsWith("/")) {
      url = "/" + url;
    } else if (input[0]?.startsWith("./") && !url.startsWith("./")) {
      url = "./" + url;
    }
  } else {
    url = "../".repeat(-1 * segmentsDepth) + url;
  }
  if (input[input.length - 1]?.endsWith("/") && !url.endsWith("/")) {
    url += "/";
  }
  return url;
}

const protocolRelative = Symbol.for("ufo:protocolRelative");
function parseURL(input = "", defaultProto) {
  const _specialProtoMatch = input.match(
    /^[\s\0]*(blob:|data:|javascript:|vbscript:)(.*)/i
  );
  if (_specialProtoMatch) {
    const [, _proto, _pathname = ""] = _specialProtoMatch;
    return {
      protocol: _proto.toLowerCase(),
      pathname: _pathname,
      href: _proto + _pathname,
      auth: "",
      host: "",
      search: "",
      hash: ""
    };
  }
  if (!hasProtocol(input, { acceptRelative: true })) {
    return parsePath(input);
  }
  const [, protocol = "", auth, hostAndPath = ""] = input.replace(/\\/g, "/").match(/^[\s\0]*([\w+.-]{2,}:)?\/\/([^/@]+@)?(.*)/) || [];
  let [, host = "", path = ""] = hostAndPath.match(/([^#/?]*)(.*)?/) || [];
  if (protocol === "file:") {
    path = path.replace(/\/(?=[A-Za-z]:)/, "");
  }
  const { pathname, search, hash } = parsePath(path);
  return {
    protocol: protocol.toLowerCase(),
    auth: auth ? auth.slice(0, Math.max(0, auth.length - 1)) : "",
    host,
    pathname,
    search,
    hash,
    [protocolRelative]: !protocol
  };
}
function parsePath(input = "") {
  const [pathname = "", search = "", hash = ""] = (input.match(/([^#?]*)(\?[^#]*)?(#.*)?/) || []).splice(1);
  return {
    pathname,
    search,
    hash
  };
}
function stringifyParsedURL(parsed) {
  const pathname = parsed.pathname || "";
  const search = parsed.search ? (parsed.search.startsWith("?") ? "" : "?") + parsed.search : "";
  const hash = parsed.hash || "";
  const auth = parsed.auth ? parsed.auth + "@" : "";
  const host = parsed.host || "";
  const proto = parsed.protocol || parsed[protocolRelative] ? (parsed.protocol || "") + "//" : "";
  return proto + auth + host + pathname + search + hash;
}

function parse(str, options) {
  if (typeof str !== "string") {
    throw new TypeError("argument str must be a string");
  }
  const obj = {};
  const opt = {};
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

const fieldContentRegExp = /^[\u0009\u0020-\u007E\u0080-\u00FF]+$/;
function serialize$1(name, value, options) {
  const opt = options || {};
  const enc = opt.encode || encodeURIComponent;
  if (typeof enc !== "function") {
    throw new TypeError("option encode is invalid");
  }
  if (!fieldContentRegExp.test(name)) {
    throw new TypeError("argument name is invalid");
  }
  const encodedValue = enc(value);
  if (encodedValue && !fieldContentRegExp.test(encodedValue)) {
    throw new TypeError("argument val is invalid");
  }
  let str = name + "=" + encodedValue;
  if (void 0 !== opt.maxAge && opt.maxAge !== null) {
    const maxAge = opt.maxAge - 0;
    if (Number.isNaN(maxAge) || !Number.isFinite(maxAge)) {
      throw new TypeError("option maxAge is invalid");
    }
    str += "; Max-Age=" + Math.floor(maxAge);
  }
  if (opt.domain) {
    if (!fieldContentRegExp.test(opt.domain)) {
      throw new TypeError("option domain is invalid");
    }
    str += "; Domain=" + opt.domain;
  }
  if (opt.path) {
    if (!fieldContentRegExp.test(opt.path)) {
      throw new TypeError("option path is invalid");
    }
    str += "; Path=" + opt.path;
  }
  if (opt.expires) {
    if (!isDate(opt.expires) || Number.isNaN(opt.expires.valueOf())) {
      throw new TypeError("option expires is invalid");
    }
    str += "; Expires=" + opt.expires.toUTCString();
  }
  if (opt.httpOnly) {
    str += "; HttpOnly";
  }
  if (opt.secure) {
    str += "; Secure";
  }
  if (opt.priority) {
    const priority = typeof opt.priority === "string" ? opt.priority.toLowerCase() : opt.priority;
    switch (priority) {
      case "low": {
        str += "; Priority=Low";
        break;
      }
      case "medium": {
        str += "; Priority=Medium";
        break;
      }
      case "high": {
        str += "; Priority=High";
        break;
      }
      default: {
        throw new TypeError("option priority is invalid");
      }
    }
  }
  if (opt.sameSite) {
    const sameSite = typeof opt.sameSite === "string" ? opt.sameSite.toLowerCase() : opt.sameSite;
    switch (sameSite) {
      case true: {
        str += "; SameSite=Strict";
        break;
      }
      case "lax": {
        str += "; SameSite=Lax";
        break;
      }
      case "strict": {
        str += "; SameSite=Strict";
        break;
      }
      case "none": {
        str += "; SameSite=None";
        break;
      }
      default: {
        throw new TypeError("option sameSite is invalid");
      }
    }
  }
  if (opt.partitioned) {
    str += "; Partitioned";
  }
  return str;
}
function isDate(val) {
  return Object.prototype.toString.call(val) === "[object Date]" || val instanceof Date;
}

function parseSetCookie(setCookieValue, options) {
  const parts = (setCookieValue || "").split(";").filter((str) => typeof str === "string" && !!str.trim());
  const nameValuePairStr = parts.shift() || "";
  const parsed = _parseNameValuePair(nameValuePairStr);
  const name = parsed.name;
  let value = parsed.value;
  try {
    value = options?.decode === false ? value : (options?.decode || decodeURIComponent)(value);
  } catch {
  }
  const cookie = {
    name,
    value
  };
  for (const part of parts) {
    const sides = part.split("=");
    const partKey = (sides.shift() || "").trimStart().toLowerCase();
    const partValue = sides.join("=");
    switch (partKey) {
      case "expires": {
        cookie.expires = new Date(partValue);
        break;
      }
      case "max-age": {
        cookie.maxAge = Number.parseInt(partValue, 10);
        break;
      }
      case "secure": {
        cookie.secure = true;
        break;
      }
      case "httponly": {
        cookie.httpOnly = true;
        break;
      }
      case "samesite": {
        cookie.sameSite = partValue;
        break;
      }
      default: {
        cookie[partKey] = partValue;
      }
    }
  }
  return cookie;
}
function _parseNameValuePair(nameValuePairStr) {
  let name = "";
  let value = "";
  const nameValueArr = nameValuePairStr.split("=");
  if (nameValueArr.length > 1) {
    name = nameValueArr.shift();
    value = nameValueArr.join("=");
  } else {
    value = nameValuePairStr;
  }
  return { name, value };
}

const NODE_TYPES = {
  NORMAL: 0,
  WILDCARD: 1,
  PLACEHOLDER: 2
};

function createRouter$1(options = {}) {
  const ctx = {
    options,
    rootNode: createRadixNode(),
    staticRoutesMap: {}
  };
  const normalizeTrailingSlash = (p) => options.strictTrailingSlash ? p : p.replace(/\/$/, "") || "/";
  if (options.routes) {
    for (const path in options.routes) {
      insert(ctx, normalizeTrailingSlash(path), options.routes[path]);
    }
  }
  return {
    ctx,
    lookup: (path) => lookup(ctx, normalizeTrailingSlash(path)),
    insert: (path, data) => insert(ctx, normalizeTrailingSlash(path), data),
    remove: (path) => remove(ctx, normalizeTrailingSlash(path))
  };
}
function lookup(ctx, path) {
  const staticPathNode = ctx.staticRoutesMap[path];
  if (staticPathNode) {
    return staticPathNode.data;
  }
  const sections = path.split("/");
  const params = {};
  let paramsFound = false;
  let wildcardNode = null;
  let node = ctx.rootNode;
  let wildCardParam = null;
  for (let i = 0; i < sections.length; i++) {
    const section = sections[i];
    if (node.wildcardChildNode !== null) {
      wildcardNode = node.wildcardChildNode;
      wildCardParam = sections.slice(i).join("/");
    }
    const nextNode = node.children.get(section);
    if (nextNode === void 0) {
      if (node && node.placeholderChildren.length > 1) {
        const remaining = sections.length - i;
        node = node.placeholderChildren.find((c) => c.maxDepth === remaining) || null;
      } else {
        node = node.placeholderChildren[0] || null;
      }
      if (!node) {
        break;
      }
      if (node.paramName) {
        params[node.paramName] = section;
      }
      paramsFound = true;
    } else {
      node = nextNode;
    }
  }
  if ((node === null || node.data === null) && wildcardNode !== null) {
    node = wildcardNode;
    params[node.paramName || "_"] = wildCardParam;
    paramsFound = true;
  }
  if (!node) {
    return null;
  }
  if (paramsFound) {
    return {
      ...node.data,
      params: paramsFound ? params : void 0
    };
  }
  return node.data;
}
function insert(ctx, path, data) {
  let isStaticRoute = true;
  const sections = path.split("/");
  let node = ctx.rootNode;
  let _unnamedPlaceholderCtr = 0;
  const matchedNodes = [node];
  for (const section of sections) {
    let childNode;
    if (childNode = node.children.get(section)) {
      node = childNode;
    } else {
      const type = getNodeType(section);
      childNode = createRadixNode({ type, parent: node });
      node.children.set(section, childNode);
      if (type === NODE_TYPES.PLACEHOLDER) {
        childNode.paramName = section === "*" ? `_${_unnamedPlaceholderCtr++}` : section.slice(1);
        node.placeholderChildren.push(childNode);
        isStaticRoute = false;
      } else if (type === NODE_TYPES.WILDCARD) {
        node.wildcardChildNode = childNode;
        childNode.paramName = section.slice(
          3
          /* "**:" */
        ) || "_";
        isStaticRoute = false;
      }
      matchedNodes.push(childNode);
      node = childNode;
    }
  }
  for (const [depth, node2] of matchedNodes.entries()) {
    node2.maxDepth = Math.max(matchedNodes.length - depth, node2.maxDepth || 0);
  }
  node.data = data;
  if (isStaticRoute === true) {
    ctx.staticRoutesMap[path] = node;
  }
  return node;
}
function remove(ctx, path) {
  let success = false;
  const sections = path.split("/");
  let node = ctx.rootNode;
  for (const section of sections) {
    node = node.children.get(section);
    if (!node) {
      return success;
    }
  }
  if (node.data) {
    const lastSection = sections.at(-1) || "";
    node.data = null;
    if (Object.keys(node.children).length === 0 && node.parent) {
      node.parent.children.delete(lastSection);
      node.parent.wildcardChildNode = null;
      node.parent.placeholderChildren = [];
    }
    success = true;
  }
  return success;
}
function createRadixNode(options = {}) {
  return {
    type: options.type || NODE_TYPES.NORMAL,
    maxDepth: 0,
    parent: options.parent || null,
    children: /* @__PURE__ */ new Map(),
    data: options.data || null,
    paramName: options.paramName || null,
    wildcardChildNode: null,
    placeholderChildren: []
  };
}
function getNodeType(str) {
  if (str.startsWith("**")) {
    return NODE_TYPES.WILDCARD;
  }
  if (str[0] === ":" || str === "*") {
    return NODE_TYPES.PLACEHOLDER;
  }
  return NODE_TYPES.NORMAL;
}

function toRouteMatcher(router) {
  const table = _routerNodeToTable("", router.ctx.rootNode);
  return _createMatcher(table, router.ctx.options.strictTrailingSlash);
}
function _createMatcher(table, strictTrailingSlash) {
  return {
    ctx: { table },
    matchAll: (path) => _matchRoutes(path, table, strictTrailingSlash)
  };
}
function _createRouteTable() {
  return {
    static: /* @__PURE__ */ new Map(),
    wildcard: /* @__PURE__ */ new Map(),
    dynamic: /* @__PURE__ */ new Map()
  };
}
function _matchRoutes(path, table, strictTrailingSlash) {
  if (strictTrailingSlash !== true && path.endsWith("/")) {
    path = path.slice(0, -1) || "/";
  }
  const matches = [];
  for (const [key, value] of _sortRoutesMap(table.wildcard)) {
    if (path === key || path.startsWith(key + "/")) {
      matches.push(value);
    }
  }
  for (const [key, value] of _sortRoutesMap(table.dynamic)) {
    if (path.startsWith(key + "/")) {
      const subPath = "/" + path.slice(key.length).split("/").splice(2).join("/");
      matches.push(..._matchRoutes(subPath, value));
    }
  }
  const staticMatch = table.static.get(path);
  if (staticMatch) {
    matches.push(staticMatch);
  }
  return matches.filter(Boolean);
}
function _sortRoutesMap(m) {
  return [...m.entries()].sort((a, b) => a[0].length - b[0].length);
}
function _routerNodeToTable(initialPath, initialNode) {
  const table = _createRouteTable();
  function _addNode(path, node) {
    if (path) {
      if (node.type === NODE_TYPES.NORMAL && !(path.includes("*") || path.includes(":"))) {
        if (node.data) {
          table.static.set(path, node.data);
        }
      } else if (node.type === NODE_TYPES.WILDCARD) {
        table.wildcard.set(path.replace("/**", ""), node.data);
      } else if (node.type === NODE_TYPES.PLACEHOLDER) {
        const subTable = _routerNodeToTable("", node);
        if (node.data) {
          subTable.static.set("/", node.data);
        }
        table.dynamic.set(path.replace(/\/\*|\/:\w+/, ""), subTable);
        return;
      }
    }
    for (const [childPath, child] of node.children.entries()) {
      _addNode(`${path}/${childPath}`.replace("//", "/"), child);
    }
  }
  _addNode(initialPath, initialNode);
  return table;
}

function isPlainObject(value) {
  if (value === null || typeof value !== "object") {
    return false;
  }
  const prototype = Object.getPrototypeOf(value);
  if (prototype !== null && prototype !== Object.prototype && Object.getPrototypeOf(prototype) !== null) {
    return false;
  }
  if (Symbol.iterator in value) {
    return false;
  }
  if (Symbol.toStringTag in value) {
    return Object.prototype.toString.call(value) === "[object Module]";
  }
  return true;
}

function _defu(baseObject, defaults, namespace = ".", merger) {
  if (!isPlainObject(defaults)) {
    return _defu(baseObject, {}, namespace, merger);
  }
  const object = Object.assign({}, defaults);
  for (const key in baseObject) {
    if (key === "__proto__" || key === "constructor") {
      continue;
    }
    const value = baseObject[key];
    if (value === null || value === void 0) {
      continue;
    }
    if (merger && merger(object, key, value, namespace)) {
      continue;
    }
    if (Array.isArray(value) && Array.isArray(object[key])) {
      object[key] = [...value, ...object[key]];
    } else if (isPlainObject(value) && isPlainObject(object[key])) {
      object[key] = _defu(
        value,
        object[key],
        (namespace ? `${namespace}.` : "") + key.toString(),
        merger
      );
    } else {
      object[key] = value;
    }
  }
  return object;
}
function createDefu(merger) {
  return (...arguments_) => (
    // eslint-disable-next-line unicorn/no-array-reduce
    arguments_.reduce((p, c) => _defu(p, c, "", merger), {})
  );
}
const defu = createDefu();
const defuFn = createDefu((object, key, currentValue) => {
  if (object[key] !== void 0 && typeof currentValue === "function") {
    object[key] = currentValue(object[key]);
    return true;
  }
});

function o(n){throw new Error(`${n} is not implemented yet!`)}let i$1 = class i extends EventEmitter{__unenv__={};readableEncoding=null;readableEnded=true;readableFlowing=false;readableHighWaterMark=0;readableLength=0;readableObjectMode=false;readableAborted=false;readableDidRead=false;closed=false;errored=null;readable=false;destroyed=false;static from(e,t){return new i(t)}constructor(e){super();}_read(e){}read(e){}setEncoding(e){return this}pause(){return this}resume(){return this}isPaused(){return  true}unpipe(e){return this}unshift(e,t){}wrap(e){return this}push(e,t){return  false}_destroy(e,t){this.removeAllListeners();}destroy(e){return this.destroyed=true,this._destroy(e),this}pipe(e,t){return {}}compose(e,t){throw new Error("Method not implemented.")}[Symbol.asyncDispose](){return this.destroy(),Promise.resolve()}async*[Symbol.asyncIterator](){throw o("Readable.asyncIterator")}iterator(e){throw o("Readable.iterator")}map(e,t){throw o("Readable.map")}filter(e,t){throw o("Readable.filter")}forEach(e,t){throw o("Readable.forEach")}reduce(e,t,r){throw o("Readable.reduce")}find(e,t){throw o("Readable.find")}findIndex(e,t){throw o("Readable.findIndex")}some(e,t){throw o("Readable.some")}toArray(e){throw o("Readable.toArray")}every(e,t){throw o("Readable.every")}flatMap(e,t){throw o("Readable.flatMap")}drop(e,t){throw o("Readable.drop")}take(e,t){throw o("Readable.take")}asIndexedPairs(e){throw o("Readable.asIndexedPairs")}};let l$1 = class l extends EventEmitter{__unenv__={};writable=true;writableEnded=false;writableFinished=false;writableHighWaterMark=0;writableLength=0;writableObjectMode=false;writableCorked=0;closed=false;errored=null;writableNeedDrain=false;writableAborted=false;destroyed=false;_data;_encoding="utf8";constructor(e){super();}pipe(e,t){return {}}_write(e,t,r){if(this.writableEnded){r&&r();return}if(this._data===void 0)this._data=e;else {const s=typeof this._data=="string"?Buffer$1.from(this._data,this._encoding||t||"utf8"):this._data,a=typeof e=="string"?Buffer$1.from(e,t||this._encoding||"utf8"):e;this._data=Buffer$1.concat([s,a]);}this._encoding=t,r&&r();}_writev(e,t){}_destroy(e,t){}_final(e){}write(e,t,r){const s=typeof t=="string"?this._encoding:"utf8",a=typeof t=="function"?t:typeof r=="function"?r:void 0;return this._write(e,s,a),true}setDefaultEncoding(e){return this}end(e,t,r){const s=typeof e=="function"?e:typeof t=="function"?t:typeof r=="function"?r:void 0;if(this.writableEnded)return s&&s(),this;const a=e===s?void 0:e;if(a){const u=t===s?void 0:t;this.write(a,u,s);}return this.writableEnded=true,this.writableFinished=true,this.emit("close"),this.emit("finish"),this}cork(){}uncork(){}destroy(e){return this.destroyed=true,delete this._data,this.removeAllListeners(),this}compose(e,t){throw new Error("Method not implemented.")}[Symbol.asyncDispose](){return Promise.resolve()}};const c=class{allowHalfOpen=true;_destroy;constructor(e=new i$1,t=new l$1){Object.assign(this,e),Object.assign(this,t),this._destroy=m(e._destroy,t._destroy);}};function _(){return Object.assign(c.prototype,i$1.prototype),Object.assign(c.prototype,l$1.prototype),c}function m(...n){return function(...e){for(const t of n)t(...e);}}const g=_();class A extends g{__unenv__={};bufferSize=0;bytesRead=0;bytesWritten=0;connecting=false;destroyed=false;pending=false;localAddress="";localPort=0;remoteAddress="";remoteFamily="";remotePort=0;autoSelectFamilyAttemptedAddresses=[];readyState="readOnly";constructor(e){super();}write(e,t,r){return  false}connect(e,t,r){return this}end(e,t,r){return this}setEncoding(e){return this}pause(){return this}resume(){return this}setTimeout(e,t){return this}setNoDelay(e){return this}setKeepAlive(e,t){return this}address(){return {}}unref(){return this}ref(){return this}destroySoon(){this.destroy();}resetAndDestroy(){const e=new Error("ERR_SOCKET_CLOSED");return e.code="ERR_SOCKET_CLOSED",this.destroy(e),this}}class y extends i$1{aborted=false;httpVersion="1.1";httpVersionMajor=1;httpVersionMinor=1;complete=true;connection;socket;headers={};trailers={};method="GET";url="/";statusCode=200;statusMessage="";closed=false;errored=null;readable=false;constructor(e){super(),this.socket=this.connection=e||new A;}get rawHeaders(){const e=this.headers,t=[];for(const r in e)if(Array.isArray(e[r]))for(const s of e[r])t.push(r,s);else t.push(r,e[r]);return t}get rawTrailers(){return []}setTimeout(e,t){return this}get headersDistinct(){return p(this.headers)}get trailersDistinct(){return p(this.trailers)}}function p(n){const e={};for(const[t,r]of Object.entries(n))t&&(e[t]=(Array.isArray(r)?r:[r]).filter(Boolean));return e}class w extends l$1{statusCode=200;statusMessage="";upgrading=false;chunkedEncoding=false;shouldKeepAlive=false;useChunkedEncodingByDefault=false;sendDate=false;finished=false;headersSent=false;strictContentLength=false;connection=null;socket=null;req;_headers={};constructor(e){super(),this.req=e;}assignSocket(e){e._httpMessage=this,this.socket=e,this.connection=e,this.emit("socket",e),this._flush();}_flush(){this.flushHeaders();}detachSocket(e){}writeContinue(e){}writeHead(e,t,r){e&&(this.statusCode=e),typeof t=="string"&&(this.statusMessage=t,t=void 0);const s=r||t;if(s&&!Array.isArray(s))for(const a in s)this.setHeader(a,s[a]);return this.headersSent=true,this}writeProcessing(){}setTimeout(e,t){return this}appendHeader(e,t){e=e.toLowerCase();const r=this._headers[e],s=[...Array.isArray(r)?r:[r],...Array.isArray(t)?t:[t]].filter(Boolean);return this._headers[e]=s.length>1?s:s[0],this}setHeader(e,t){return this._headers[e.toLowerCase()]=t,this}setHeaders(e){for(const[t,r]of Object.entries(e))this.setHeader(t,r);return this}getHeader(e){return this._headers[e.toLowerCase()]}getHeaders(){return this._headers}getHeaderNames(){return Object.keys(this._headers)}hasHeader(e){return e.toLowerCase()in this._headers}removeHeader(e){delete this._headers[e.toLowerCase()];}addTrailers(e){}flushHeaders(){}writeEarlyHints(e,t){typeof t=="function"&&t();}}const E=(()=>{const n=function(){};return n.prototype=Object.create(null),n})();function R(n={}){const e=new E,t=Array.isArray(n)||H(n)?n:Object.entries(n);for(const[r,s]of t)if(s){if(e[r]===void 0){e[r]=s;continue}e[r]=[...Array.isArray(e[r])?e[r]:[e[r]],...Array.isArray(s)?s:[s]];}return e}function H(n){return typeof n?.entries=="function"}function v(n={}){if(n instanceof Headers)return n;const e=new Headers;for(const[t,r]of Object.entries(n))if(r!==void 0){if(Array.isArray(r)){for(const s of r)e.append(t,String(s));continue}e.set(t,String(r));}return e}const S=new Set([101,204,205,304]);async function b(n,e){const t=new y,r=new w(t);t.url=e.url?.toString()||"/";let s;if(!t.url.startsWith("/")){const d=new URL(t.url);s=d.host,t.url=d.pathname+d.search+d.hash;}t.method=e.method||"GET",t.headers=R(e.headers||{}),t.headers.host||(t.headers.host=e.host||s||"localhost"),t.connection.encrypted=t.connection.encrypted||e.protocol==="https",t.body=e.body||null,t.__unenv__=e.context,await n(t,r);let a=r._data;(S.has(r.statusCode)||t.method.toUpperCase()==="HEAD")&&(a=null,delete r._headers["content-length"]);const u={status:r.statusCode,statusText:r.statusMessage,headers:r._headers,body:a};return t.destroy(),r.destroy(),u}async function C(n,e,t={}){try{const r=await b(n,{url:e,...t});return new Response(r.body,{status:r.status,statusText:r.statusText,headers:v(r.headers)})}catch(r){return new Response(r.toString(),{status:Number.parseInt(r.statusCode||r.code)||500,statusText:r.statusText})}}

function hasProp(obj, prop) {
  try {
    return prop in obj;
  } catch {
    return false;
  }
}

class H3Error extends Error {
  static __h3_error__ = true;
  statusCode = 500;
  fatal = false;
  unhandled = false;
  statusMessage;
  data;
  cause;
  constructor(message, opts = {}) {
    super(message, opts);
    if (opts.cause && !this.cause) {
      this.cause = opts.cause;
    }
  }
  toJSON() {
    const obj = {
      message: this.message,
      statusCode: sanitizeStatusCode(this.statusCode, 500)
    };
    if (this.statusMessage) {
      obj.statusMessage = sanitizeStatusMessage(this.statusMessage);
    }
    if (this.data !== void 0) {
      obj.data = this.data;
    }
    return obj;
  }
}
function createError$1(input) {
  if (typeof input === "string") {
    return new H3Error(input);
  }
  if (isError(input)) {
    return input;
  }
  const err = new H3Error(input.message ?? input.statusMessage ?? "", {
    cause: input.cause || input
  });
  if (hasProp(input, "stack")) {
    try {
      Object.defineProperty(err, "stack", {
        get() {
          return input.stack;
        }
      });
    } catch {
      try {
        err.stack = input.stack;
      } catch {
      }
    }
  }
  if (input.data) {
    err.data = input.data;
  }
  if (input.statusCode) {
    err.statusCode = sanitizeStatusCode(input.statusCode, err.statusCode);
  } else if (input.status) {
    err.statusCode = sanitizeStatusCode(input.status, err.statusCode);
  }
  if (input.statusMessage) {
    err.statusMessage = input.statusMessage;
  } else if (input.statusText) {
    err.statusMessage = input.statusText;
  }
  if (err.statusMessage) {
    const originalMessage = err.statusMessage;
    const sanitizedMessage = sanitizeStatusMessage(err.statusMessage);
    if (sanitizedMessage !== originalMessage) {
      console.warn(
        "[h3] Please prefer using `message` for longer error messages instead of `statusMessage`. In the future, `statusMessage` will be sanitized by default."
      );
    }
  }
  if (input.fatal !== void 0) {
    err.fatal = input.fatal;
  }
  if (input.unhandled !== void 0) {
    err.unhandled = input.unhandled;
  }
  return err;
}
function sendError(event, error, debug) {
  if (event.handled) {
    return;
  }
  const h3Error = isError(error) ? error : createError$1(error);
  const responseBody = {
    statusCode: h3Error.statusCode,
    statusMessage: h3Error.statusMessage,
    stack: [],
    data: h3Error.data
  };
  if (debug) {
    responseBody.stack = (h3Error.stack || "").split("\n").map((l) => l.trim());
  }
  if (event.handled) {
    return;
  }
  const _code = Number.parseInt(h3Error.statusCode);
  setResponseStatus(event, _code, h3Error.statusMessage);
  event.node.res.setHeader("content-type", MIMES.json);
  event.node.res.end(JSON.stringify(responseBody, void 0, 2));
}
function isError(input) {
  return input?.constructor?.__h3_error__ === true;
}

function getQuery(event) {
  return getQuery$1(event.path || "");
}
function getRouterParams(event, opts = {}) {
  let params = event.context.params || {};
  if (opts.decode) {
    params = { ...params };
    for (const key in params) {
      params[key] = decode$1(params[key]);
    }
  }
  return params;
}
function getRouterParam(event, name, opts = {}) {
  const params = getRouterParams(event, opts);
  return params[name];
}
function isMethod(event, expected, allowHead) {
  if (typeof expected === "string") {
    if (event.method === expected) {
      return true;
    }
  } else if (expected.includes(event.method)) {
    return true;
  }
  return false;
}
function assertMethod(event, expected, allowHead) {
  if (!isMethod(event, expected)) {
    throw createError$1({
      statusCode: 405,
      statusMessage: "HTTP method is not allowed."
    });
  }
}
function getRequestHeaders(event) {
  const _headers = {};
  for (const key in event.node.req.headers) {
    const val = event.node.req.headers[key];
    _headers[key] = Array.isArray(val) ? val.filter(Boolean).join(", ") : val;
  }
  return _headers;
}
function getRequestHeader(event, name) {
  const headers = getRequestHeaders(event);
  const value = headers[name.toLowerCase()];
  return value;
}
const getHeader = getRequestHeader;
function getRequestHost(event, opts = {}) {
  if (opts.xForwardedHost) {
    const _header = event.node.req.headers["x-forwarded-host"];
    const xForwardedHost = (_header || "").split(",").shift()?.trim();
    if (xForwardedHost) {
      return xForwardedHost;
    }
  }
  return event.node.req.headers.host || "localhost";
}
function getRequestProtocol(event, opts = {}) {
  if (opts.xForwardedProto !== false && event.node.req.headers["x-forwarded-proto"] === "https") {
    return "https";
  }
  return event.node.req.connection?.encrypted ? "https" : "http";
}
function getRequestURL(event, opts = {}) {
  const host = getRequestHost(event, opts);
  const protocol = getRequestProtocol(event, opts);
  const path = (event.node.req.originalUrl || event.path).replace(
    /^[/\\]+/g,
    "/"
  );
  return new URL(path, `${protocol}://${host}`);
}

const RawBodySymbol = Symbol.for("h3RawBody");
const ParsedBodySymbol = Symbol.for("h3ParsedBody");
const PayloadMethods$1 = ["PATCH", "POST", "PUT", "DELETE"];
function readRawBody(event, encoding = "utf8") {
  assertMethod(event, PayloadMethods$1);
  const _rawBody = event._requestBody || event.web?.request?.body || event.node.req[RawBodySymbol] || event.node.req.rawBody || event.node.req.body;
  if (_rawBody) {
    const promise2 = Promise.resolve(_rawBody).then((_resolved) => {
      if (Buffer.isBuffer(_resolved)) {
        return _resolved;
      }
      if (typeof _resolved.pipeTo === "function") {
        return new Promise((resolve, reject) => {
          const chunks = [];
          _resolved.pipeTo(
            new WritableStream({
              write(chunk) {
                chunks.push(chunk);
              },
              close() {
                resolve(Buffer.concat(chunks));
              },
              abort(reason) {
                reject(reason);
              }
            })
          ).catch(reject);
        });
      } else if (typeof _resolved.pipe === "function") {
        return new Promise((resolve, reject) => {
          const chunks = [];
          _resolved.on("data", (chunk) => {
            chunks.push(chunk);
          }).on("end", () => {
            resolve(Buffer.concat(chunks));
          }).on("error", reject);
        });
      }
      if (_resolved.constructor === Object) {
        return Buffer.from(JSON.stringify(_resolved));
      }
      if (_resolved instanceof URLSearchParams) {
        return Buffer.from(_resolved.toString());
      }
      if (_resolved instanceof FormData) {
        return new Response(_resolved).bytes().then((uint8arr) => Buffer.from(uint8arr));
      }
      return Buffer.from(_resolved);
    });
    return encoding ? promise2.then((buff) => buff.toString(encoding)) : promise2;
  }
  if (!Number.parseInt(event.node.req.headers["content-length"] || "") && !/\bchunked\b/i.test(
    String(event.node.req.headers["transfer-encoding"] ?? "")
  )) {
    return Promise.resolve(void 0);
  }
  const promise = event.node.req[RawBodySymbol] = new Promise(
    (resolve, reject) => {
      const bodyData = [];
      event.node.req.on("error", (err) => {
        reject(err);
      }).on("data", (chunk) => {
        bodyData.push(chunk);
      }).on("end", () => {
        resolve(Buffer.concat(bodyData));
      });
    }
  );
  const result = encoding ? promise.then((buff) => buff.toString(encoding)) : promise;
  return result;
}
async function readBody(event, options = {}) {
  const request = event.node.req;
  if (hasProp(request, ParsedBodySymbol)) {
    return request[ParsedBodySymbol];
  }
  const contentType = request.headers["content-type"] || "";
  const body = await readRawBody(event);
  let parsed;
  if (contentType === "application/json") {
    parsed = _parseJSON(body, options.strict ?? true);
  } else if (contentType.startsWith("application/x-www-form-urlencoded")) {
    parsed = _parseURLEncodedBody(body);
  } else if (contentType.startsWith("text/")) {
    parsed = body;
  } else {
    parsed = _parseJSON(body, options.strict ?? false);
  }
  request[ParsedBodySymbol] = parsed;
  return parsed;
}
function getRequestWebStream(event) {
  if (!PayloadMethods$1.includes(event.method)) {
    return;
  }
  const bodyStream = event.web?.request?.body || event._requestBody;
  if (bodyStream) {
    return bodyStream;
  }
  const _hasRawBody = RawBodySymbol in event.node.req || "rawBody" in event.node.req || "body" in event.node.req || "__unenv__" in event.node.req;
  if (_hasRawBody) {
    return new ReadableStream({
      async start(controller) {
        const _rawBody = await readRawBody(event, false);
        if (_rawBody) {
          controller.enqueue(_rawBody);
        }
        controller.close();
      }
    });
  }
  return new ReadableStream({
    start: (controller) => {
      event.node.req.on("data", (chunk) => {
        controller.enqueue(chunk);
      });
      event.node.req.on("end", () => {
        controller.close();
      });
      event.node.req.on("error", (err) => {
        controller.error(err);
      });
    }
  });
}
function _parseJSON(body = "", strict) {
  if (!body) {
    return void 0;
  }
  try {
    return destr(body, { strict });
  } catch {
    throw createError$1({
      statusCode: 400,
      statusMessage: "Bad Request",
      message: "Invalid JSON body"
    });
  }
}
function _parseURLEncodedBody(body) {
  const form = new URLSearchParams(body);
  const parsedForm = /* @__PURE__ */ Object.create(null);
  for (const [key, value] of form.entries()) {
    if (hasProp(parsedForm, key)) {
      if (!Array.isArray(parsedForm[key])) {
        parsedForm[key] = [parsedForm[key]];
      }
      parsedForm[key].push(value);
    } else {
      parsedForm[key] = value;
    }
  }
  return parsedForm;
}

function handleCacheHeaders(event, opts) {
  const cacheControls = ["public", ...opts.cacheControls || []];
  let cacheMatched = false;
  if (opts.maxAge !== void 0) {
    cacheControls.push(`max-age=${+opts.maxAge}`, `s-maxage=${+opts.maxAge}`);
  }
  if (opts.modifiedTime) {
    const modifiedTime = new Date(opts.modifiedTime);
    const ifModifiedSince = event.node.req.headers["if-modified-since"];
    event.node.res.setHeader("last-modified", modifiedTime.toUTCString());
    if (ifModifiedSince && new Date(ifModifiedSince) >= modifiedTime) {
      cacheMatched = true;
    }
  }
  if (opts.etag) {
    event.node.res.setHeader("etag", opts.etag);
    const ifNonMatch = event.node.req.headers["if-none-match"];
    if (ifNonMatch === opts.etag) {
      cacheMatched = true;
    }
  }
  event.node.res.setHeader("cache-control", cacheControls.join(", "));
  if (cacheMatched) {
    event.node.res.statusCode = 304;
    if (!event.handled) {
      event.node.res.end();
    }
    return true;
  }
  return false;
}

const MIMES = {
  html: "text/html",
  json: "application/json"
};

const DISALLOWED_STATUS_CHARS = /[^\u0009\u0020-\u007E]/g;
function sanitizeStatusMessage(statusMessage = "") {
  return statusMessage.replace(DISALLOWED_STATUS_CHARS, "");
}
function sanitizeStatusCode(statusCode, defaultStatusCode = 200) {
  if (!statusCode) {
    return defaultStatusCode;
  }
  if (typeof statusCode === "string") {
    statusCode = Number.parseInt(statusCode, 10);
  }
  if (statusCode < 100 || statusCode > 999) {
    return defaultStatusCode;
  }
  return statusCode;
}

function getDistinctCookieKey(name, opts) {
  return [name, opts.domain || "", opts.path || "/"].join(";");
}

function parseCookies(event) {
  return parse(event.node.req.headers.cookie || "");
}
function getCookie(event, name) {
  return parseCookies(event)[name];
}
function setCookie(event, name, value, serializeOptions = {}) {
  if (!serializeOptions.path) {
    serializeOptions = { path: "/", ...serializeOptions };
  }
  const newCookie = serialize$1(name, value, serializeOptions);
  const currentCookies = splitCookiesString(
    event.node.res.getHeader("set-cookie")
  );
  if (currentCookies.length === 0) {
    event.node.res.setHeader("set-cookie", newCookie);
    return;
  }
  const newCookieKey = getDistinctCookieKey(name, serializeOptions);
  event.node.res.removeHeader("set-cookie");
  for (const cookie of currentCookies) {
    const parsed = parseSetCookie(cookie);
    const key = getDistinctCookieKey(parsed.name, parsed);
    if (key === newCookieKey) {
      continue;
    }
    event.node.res.appendHeader("set-cookie", cookie);
  }
  event.node.res.appendHeader("set-cookie", newCookie);
}
function splitCookiesString(cookiesString) {
  if (Array.isArray(cookiesString)) {
    return cookiesString.flatMap((c) => splitCookiesString(c));
  }
  if (typeof cookiesString !== "string") {
    return [];
  }
  const cookiesStrings = [];
  let pos = 0;
  let start;
  let ch;
  let lastComma;
  let nextStart;
  let cookiesSeparatorFound;
  const skipWhitespace = () => {
    while (pos < cookiesString.length && /\s/.test(cookiesString.charAt(pos))) {
      pos += 1;
    }
    return pos < cookiesString.length;
  };
  const notSpecialChar = () => {
    ch = cookiesString.charAt(pos);
    return ch !== "=" && ch !== ";" && ch !== ",";
  };
  while (pos < cookiesString.length) {
    start = pos;
    cookiesSeparatorFound = false;
    while (skipWhitespace()) {
      ch = cookiesString.charAt(pos);
      if (ch === ",") {
        lastComma = pos;
        pos += 1;
        skipWhitespace();
        nextStart = pos;
        while (pos < cookiesString.length && notSpecialChar()) {
          pos += 1;
        }
        if (pos < cookiesString.length && cookiesString.charAt(pos) === "=") {
          cookiesSeparatorFound = true;
          pos = nextStart;
          cookiesStrings.push(cookiesString.slice(start, lastComma));
          start = pos;
        } else {
          pos = lastComma + 1;
        }
      } else {
        pos += 1;
      }
    }
    if (!cookiesSeparatorFound || pos >= cookiesString.length) {
      cookiesStrings.push(cookiesString.slice(start));
    }
  }
  return cookiesStrings;
}

const defer = typeof setImmediate === "undefined" ? (fn) => fn() : setImmediate;
function send(event, data, type) {
  if (type) {
    defaultContentType(event, type);
  }
  return new Promise((resolve) => {
    defer(() => {
      if (!event.handled) {
        event.node.res.end(data);
      }
      resolve();
    });
  });
}
function sendNoContent(event, code) {
  if (event.handled) {
    return;
  }
  if (!code && event.node.res.statusCode !== 200) {
    code = event.node.res.statusCode;
  }
  const _code = sanitizeStatusCode(code, 204);
  if (_code === 204) {
    event.node.res.removeHeader("content-length");
  }
  event.node.res.writeHead(_code);
  event.node.res.end();
}
function setResponseStatus(event, code, text) {
  if (code) {
    event.node.res.statusCode = sanitizeStatusCode(
      code,
      event.node.res.statusCode
    );
  }
  if (text) {
    event.node.res.statusMessage = sanitizeStatusMessage(text);
  }
}
function getResponseStatus(event) {
  return event.node.res.statusCode;
}
function getResponseStatusText(event) {
  return event.node.res.statusMessage;
}
function defaultContentType(event, type) {
  if (type && event.node.res.statusCode !== 304 && !event.node.res.getHeader("content-type")) {
    event.node.res.setHeader("content-type", type);
  }
}
function sendRedirect(event, location, code = 302) {
  event.node.res.statusCode = sanitizeStatusCode(
    code,
    event.node.res.statusCode
  );
  event.node.res.setHeader("location", location);
  const encodedLoc = location.replace(/"/g, "%22");
  const html = `<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=${encodedLoc}"></head></html>`;
  return send(event, html, MIMES.html);
}
function getResponseHeader(event, name) {
  return event.node.res.getHeader(name);
}
function setResponseHeaders(event, headers) {
  for (const [name, value] of Object.entries(headers)) {
    event.node.res.setHeader(
      name,
      value
    );
  }
}
const setHeaders = setResponseHeaders;
function setResponseHeader(event, name, value) {
  event.node.res.setHeader(name, value);
}
function appendResponseHeader(event, name, value) {
  let current = event.node.res.getHeader(name);
  if (!current) {
    event.node.res.setHeader(name, value);
    return;
  }
  if (!Array.isArray(current)) {
    current = [current.toString()];
  }
  event.node.res.setHeader(name, [...current, value]);
}
function removeResponseHeader(event, name) {
  return event.node.res.removeHeader(name);
}
function isStream(data) {
  if (!data || typeof data !== "object") {
    return false;
  }
  if (typeof data.pipe === "function") {
    if (typeof data._read === "function") {
      return true;
    }
    if (typeof data.abort === "function") {
      return true;
    }
  }
  if (typeof data.pipeTo === "function") {
    return true;
  }
  return false;
}
function isWebResponse(data) {
  return typeof Response !== "undefined" && data instanceof Response;
}
function sendStream(event, stream) {
  if (!stream || typeof stream !== "object") {
    throw new Error("[h3] Invalid stream provided.");
  }
  event.node.res._data = stream;
  if (!event.node.res.socket) {
    event._handled = true;
    return Promise.resolve();
  }
  if (hasProp(stream, "pipeTo") && typeof stream.pipeTo === "function") {
    return stream.pipeTo(
      new WritableStream({
        write(chunk) {
          event.node.res.write(chunk);
        }
      })
    ).then(() => {
      event.node.res.end();
    });
  }
  if (hasProp(stream, "pipe") && typeof stream.pipe === "function") {
    return new Promise((resolve, reject) => {
      stream.pipe(event.node.res);
      if (stream.on) {
        stream.on("end", () => {
          event.node.res.end();
          resolve();
        });
        stream.on("error", (error) => {
          reject(error);
        });
      }
      event.node.res.on("close", () => {
        if (stream.abort) {
          stream.abort();
        }
      });
    });
  }
  throw new Error("[h3] Invalid or incompatible stream provided.");
}
function sendWebResponse(event, response) {
  for (const [key, value] of response.headers) {
    if (key === "set-cookie") {
      event.node.res.appendHeader(key, splitCookiesString(value));
    } else {
      event.node.res.setHeader(key, value);
    }
  }
  if (response.status) {
    event.node.res.statusCode = sanitizeStatusCode(
      response.status,
      event.node.res.statusCode
    );
  }
  if (response.statusText) {
    event.node.res.statusMessage = sanitizeStatusMessage(response.statusText);
  }
  if (response.redirected) {
    event.node.res.setHeader("location", response.url);
  }
  if (!response.body) {
    event.node.res.end();
    return;
  }
  return sendStream(event, response.body);
}

const PayloadMethods = /* @__PURE__ */ new Set(["PATCH", "POST", "PUT", "DELETE"]);
const ignoredHeaders = /* @__PURE__ */ new Set([
  "transfer-encoding",
  "accept-encoding",
  "connection",
  "keep-alive",
  "upgrade",
  "expect",
  "host",
  "accept"
]);
async function proxyRequest(event, target, opts = {}) {
  let body;
  let duplex;
  if (PayloadMethods.has(event.method)) {
    if (opts.streamRequest) {
      body = getRequestWebStream(event);
      duplex = "half";
    } else {
      body = await readRawBody(event, false).catch(() => void 0);
    }
  }
  const method = opts.fetchOptions?.method || event.method;
  const fetchHeaders = mergeHeaders$1(
    getProxyRequestHeaders(event, { host: target.startsWith("/") }),
    opts.fetchOptions?.headers,
    opts.headers
  );
  return sendProxy(event, target, {
    ...opts,
    fetchOptions: {
      method,
      body,
      duplex,
      ...opts.fetchOptions,
      headers: fetchHeaders
    }
  });
}
async function sendProxy(event, target, opts = {}) {
  let response;
  try {
    response = await _getFetch(opts.fetch)(target, {
      headers: opts.headers,
      ignoreResponseError: true,
      // make $ofetch.raw transparent
      ...opts.fetchOptions
    });
  } catch (error) {
    throw createError$1({
      status: 502,
      statusMessage: "Bad Gateway",
      cause: error
    });
  }
  event.node.res.statusCode = sanitizeStatusCode(
    response.status,
    event.node.res.statusCode
  );
  event.node.res.statusMessage = sanitizeStatusMessage(response.statusText);
  const cookies = [];
  for (const [key, value] of response.headers.entries()) {
    if (key === "content-encoding") {
      continue;
    }
    if (key === "content-length") {
      continue;
    }
    if (key === "set-cookie") {
      cookies.push(...splitCookiesString(value));
      continue;
    }
    event.node.res.setHeader(key, value);
  }
  if (cookies.length > 0) {
    event.node.res.setHeader(
      "set-cookie",
      cookies.map((cookie) => {
        if (opts.cookieDomainRewrite) {
          cookie = rewriteCookieProperty(
            cookie,
            opts.cookieDomainRewrite,
            "domain"
          );
        }
        if (opts.cookiePathRewrite) {
          cookie = rewriteCookieProperty(
            cookie,
            opts.cookiePathRewrite,
            "path"
          );
        }
        return cookie;
      })
    );
  }
  if (opts.onResponse) {
    await opts.onResponse(event, response);
  }
  if (response._data !== void 0) {
    return response._data;
  }
  if (event.handled) {
    return;
  }
  if (opts.sendStream === false) {
    const data = new Uint8Array(await response.arrayBuffer());
    return event.node.res.end(data);
  }
  if (response.body) {
    for await (const chunk of response.body) {
      event.node.res.write(chunk);
    }
  }
  return event.node.res.end();
}
function getProxyRequestHeaders(event, opts) {
  const headers = /* @__PURE__ */ Object.create(null);
  const reqHeaders = getRequestHeaders(event);
  for (const name in reqHeaders) {
    if (!ignoredHeaders.has(name) || name === "host" && opts?.host) {
      headers[name] = reqHeaders[name];
    }
  }
  return headers;
}
function fetchWithEvent(event, req, init, options) {
  return _getFetch(options?.fetch)(req, {
    ...init,
    context: init?.context || event.context,
    headers: {
      ...getProxyRequestHeaders(event, {
        host: typeof req === "string" && req.startsWith("/")
      }),
      ...init?.headers
    }
  });
}
function _getFetch(_fetch) {
  if (_fetch) {
    return _fetch;
  }
  if (globalThis.fetch) {
    return globalThis.fetch;
  }
  throw new Error(
    "fetch is not available. Try importing `node-fetch-native/polyfill` for Node.js."
  );
}
function rewriteCookieProperty(header, map, property) {
  const _map = typeof map === "string" ? { "*": map } : map;
  return header.replace(
    new RegExp(`(;\\s*${property}=)([^;]+)`, "gi"),
    (match, prefix, previousValue) => {
      let newValue;
      if (previousValue in _map) {
        newValue = _map[previousValue];
      } else if ("*" in _map) {
        newValue = _map["*"];
      } else {
        return match;
      }
      return newValue ? prefix + newValue : "";
    }
  );
}
function mergeHeaders$1(defaults, ...inputs) {
  const _inputs = inputs.filter(Boolean);
  if (_inputs.length === 0) {
    return defaults;
  }
  const merged = new Headers(defaults);
  for (const input of _inputs) {
    const entries = Array.isArray(input) ? input : typeof input.entries === "function" ? input.entries() : Object.entries(input);
    for (const [key, value] of entries) {
      if (value !== void 0) {
        merged.set(key, value);
      }
    }
  }
  return merged;
}

class H3Event {
  "__is_event__" = true;
  // Context
  node;
  // Node
  web;
  // Web
  context = {};
  // Shared
  // Request
  _method;
  _path;
  _headers;
  _requestBody;
  // Response
  _handled = false;
  // Hooks
  _onBeforeResponseCalled;
  _onAfterResponseCalled;
  constructor(req, res) {
    this.node = { req, res };
  }
  // --- Request ---
  get method() {
    if (!this._method) {
      this._method = (this.node.req.method || "GET").toUpperCase();
    }
    return this._method;
  }
  get path() {
    return this._path || this.node.req.url || "/";
  }
  get headers() {
    if (!this._headers) {
      this._headers = _normalizeNodeHeaders(this.node.req.headers);
    }
    return this._headers;
  }
  // --- Respoonse ---
  get handled() {
    return this._handled || this.node.res.writableEnded || this.node.res.headersSent;
  }
  respondWith(response) {
    return Promise.resolve(response).then(
      (_response) => sendWebResponse(this, _response)
    );
  }
  // --- Utils ---
  toString() {
    return `[${this.method}] ${this.path}`;
  }
  toJSON() {
    return this.toString();
  }
  // --- Deprecated ---
  /** @deprecated Please use `event.node.req` instead. */
  get req() {
    return this.node.req;
  }
  /** @deprecated Please use `event.node.res` instead. */
  get res() {
    return this.node.res;
  }
}
function isEvent(input) {
  return hasProp(input, "__is_event__");
}
function createEvent(req, res) {
  return new H3Event(req, res);
}
function _normalizeNodeHeaders(nodeHeaders) {
  const headers = new Headers();
  for (const [name, value] of Object.entries(nodeHeaders)) {
    if (Array.isArray(value)) {
      for (const item of value) {
        headers.append(name, item);
      }
    } else if (value) {
      headers.set(name, value);
    }
  }
  return headers;
}

function defineEventHandler(handler) {
  if (typeof handler === "function") {
    handler.__is_handler__ = true;
    return handler;
  }
  const _hooks = {
    onRequest: _normalizeArray(handler.onRequest),
    onBeforeResponse: _normalizeArray(handler.onBeforeResponse)
  };
  const _handler = (event) => {
    return _callHandler(event, handler.handler, _hooks);
  };
  _handler.__is_handler__ = true;
  _handler.__resolve__ = handler.handler.__resolve__;
  _handler.__websocket__ = handler.websocket;
  return _handler;
}
function _normalizeArray(input) {
  return input ? Array.isArray(input) ? input : [input] : void 0;
}
async function _callHandler(event, handler, hooks) {
  if (hooks.onRequest) {
    for (const hook of hooks.onRequest) {
      await hook(event);
      if (event.handled) {
        return;
      }
    }
  }
  const body = await handler(event);
  const response = { body };
  if (hooks.onBeforeResponse) {
    for (const hook of hooks.onBeforeResponse) {
      await hook(event, response);
    }
  }
  return response.body;
}
const eventHandler = defineEventHandler;
function isEventHandler(input) {
  return hasProp(input, "__is_handler__");
}
function toEventHandler(input, _, _route) {
  if (!isEventHandler(input)) {
    console.warn(
      "[h3] Implicit event handler conversion is deprecated. Use `eventHandler()` or `fromNodeMiddleware()` to define event handlers.",
      _route && _route !== "/" ? `
     Route: ${_route}` : "",
      `
     Handler: ${input}`
    );
  }
  return input;
}
function defineLazyEventHandler(factory) {
  let _promise;
  let _resolved;
  const resolveHandler = () => {
    if (_resolved) {
      return Promise.resolve(_resolved);
    }
    if (!_promise) {
      _promise = Promise.resolve(factory()).then((r) => {
        const handler2 = r.default || r;
        if (typeof handler2 !== "function") {
          throw new TypeError(
            "Invalid lazy handler result. It should be a function:",
            handler2
          );
        }
        _resolved = { handler: toEventHandler(r.default || r) };
        return _resolved;
      });
    }
    return _promise;
  };
  const handler = eventHandler((event) => {
    if (_resolved) {
      return _resolved.handler(event);
    }
    return resolveHandler().then((r) => r.handler(event));
  });
  handler.__resolve__ = resolveHandler;
  return handler;
}
const lazyEventHandler = defineLazyEventHandler;

function createApp(options = {}) {
  const stack = [];
  const handler = createAppEventHandler(stack, options);
  const resolve = createResolver(stack);
  handler.__resolve__ = resolve;
  const getWebsocket = cachedFn(() => websocketOptions(resolve, options));
  const app = {
    // @ts-expect-error
    use: (arg1, arg2, arg3) => use(app, arg1, arg2, arg3),
    resolve,
    handler,
    stack,
    options,
    get websocket() {
      return getWebsocket();
    }
  };
  return app;
}
function use(app, arg1, arg2, arg3) {
  if (Array.isArray(arg1)) {
    for (const i of arg1) {
      use(app, i, arg2, arg3);
    }
  } else if (Array.isArray(arg2)) {
    for (const i of arg2) {
      use(app, arg1, i, arg3);
    }
  } else if (typeof arg1 === "string") {
    app.stack.push(
      normalizeLayer({ ...arg3, route: arg1, handler: arg2 })
    );
  } else if (typeof arg1 === "function") {
    app.stack.push(normalizeLayer({ ...arg2, handler: arg1 }));
  } else {
    app.stack.push(normalizeLayer({ ...arg1 }));
  }
  return app;
}
function createAppEventHandler(stack, options) {
  const spacing = options.debug ? 2 : void 0;
  return eventHandler(async (event) => {
    event.node.req.originalUrl = event.node.req.originalUrl || event.node.req.url || "/";
    const _reqPath = event._path || event.node.req.url || "/";
    let _layerPath;
    if (options.onRequest) {
      await options.onRequest(event);
    }
    for (const layer of stack) {
      if (layer.route.length > 1) {
        if (!_reqPath.startsWith(layer.route)) {
          continue;
        }
        _layerPath = _reqPath.slice(layer.route.length) || "/";
      } else {
        _layerPath = _reqPath;
      }
      if (layer.match && !layer.match(_layerPath, event)) {
        continue;
      }
      event._path = _layerPath;
      event.node.req.url = _layerPath;
      const val = await layer.handler(event);
      const _body = val === void 0 ? void 0 : await val;
      if (_body !== void 0) {
        const _response = { body: _body };
        if (options.onBeforeResponse) {
          event._onBeforeResponseCalled = true;
          await options.onBeforeResponse(event, _response);
        }
        await handleHandlerResponse(event, _response.body, spacing);
        if (options.onAfterResponse) {
          event._onAfterResponseCalled = true;
          await options.onAfterResponse(event, _response);
        }
        return;
      }
      if (event.handled) {
        if (options.onAfterResponse) {
          event._onAfterResponseCalled = true;
          await options.onAfterResponse(event, void 0);
        }
        return;
      }
    }
    if (!event.handled) {
      throw createError$1({
        statusCode: 404,
        statusMessage: `Cannot find any path matching ${event.path || "/"}.`
      });
    }
    if (options.onAfterResponse) {
      event._onAfterResponseCalled = true;
      await options.onAfterResponse(event, void 0);
    }
  });
}
function createResolver(stack) {
  return async (path) => {
    let _layerPath;
    for (const layer of stack) {
      if (layer.route === "/" && !layer.handler.__resolve__) {
        continue;
      }
      if (!path.startsWith(layer.route)) {
        continue;
      }
      _layerPath = path.slice(layer.route.length) || "/";
      if (layer.match && !layer.match(_layerPath, void 0)) {
        continue;
      }
      let res = { route: layer.route, handler: layer.handler };
      if (res.handler.__resolve__) {
        const _res = await res.handler.__resolve__(_layerPath);
        if (!_res) {
          continue;
        }
        res = {
          ...res,
          ..._res,
          route: joinURL(res.route || "/", _res.route || "/")
        };
      }
      return res;
    }
  };
}
function normalizeLayer(input) {
  let handler = input.handler;
  if (handler.handler) {
    handler = handler.handler;
  }
  if (input.lazy) {
    handler = lazyEventHandler(handler);
  } else if (!isEventHandler(handler)) {
    handler = toEventHandler(handler, void 0, input.route);
  }
  return {
    route: withoutTrailingSlash(input.route),
    match: input.match,
    handler
  };
}
function handleHandlerResponse(event, val, jsonSpace) {
  if (val === null) {
    return sendNoContent(event);
  }
  if (val) {
    if (isWebResponse(val)) {
      return sendWebResponse(event, val);
    }
    if (isStream(val)) {
      return sendStream(event, val);
    }
    if (val.buffer) {
      return send(event, val);
    }
    if (val.arrayBuffer && typeof val.arrayBuffer === "function") {
      return val.arrayBuffer().then((arrayBuffer) => {
        return send(event, Buffer.from(arrayBuffer), val.type);
      });
    }
    if (val instanceof Error) {
      throw createError$1(val);
    }
    if (typeof val.end === "function") {
      return true;
    }
  }
  const valType = typeof val;
  if (valType === "string") {
    return send(event, val, MIMES.html);
  }
  if (valType === "object" || valType === "boolean" || valType === "number") {
    return send(event, JSON.stringify(val, void 0, jsonSpace), MIMES.json);
  }
  if (valType === "bigint") {
    return send(event, val.toString(), MIMES.json);
  }
  throw createError$1({
    statusCode: 500,
    statusMessage: `[h3] Cannot send ${valType} as response.`
  });
}
function cachedFn(fn) {
  let cache;
  return () => {
    if (!cache) {
      cache = fn();
    }
    return cache;
  };
}
function websocketOptions(evResolver, appOptions) {
  return {
    ...appOptions.websocket,
    async resolve(info) {
      const url = info.request?.url || info.url || "/";
      const { pathname } = typeof url === "string" ? parseURL(url) : url;
      const resolved = await evResolver(pathname);
      return resolved?.handler?.__websocket__ || {};
    }
  };
}

const RouterMethods = [
  "connect",
  "delete",
  "get",
  "head",
  "options",
  "post",
  "put",
  "trace",
  "patch"
];
function createRouter(opts = {}) {
  const _router = createRouter$1({});
  const routes = {};
  let _matcher;
  const router = {};
  const addRoute = (path, handler, method) => {
    let route = routes[path];
    if (!route) {
      routes[path] = route = { path, handlers: {} };
      _router.insert(path, route);
    }
    if (Array.isArray(method)) {
      for (const m of method) {
        addRoute(path, handler, m);
      }
    } else {
      route.handlers[method] = toEventHandler(handler, void 0, path);
    }
    return router;
  };
  router.use = router.add = (path, handler, method) => addRoute(path, handler, method || "all");
  for (const method of RouterMethods) {
    router[method] = (path, handle) => router.add(path, handle, method);
  }
  const matchHandler = (path = "/", method = "get") => {
    const qIndex = path.indexOf("?");
    if (qIndex !== -1) {
      path = path.slice(0, Math.max(0, qIndex));
    }
    const matched = _router.lookup(path);
    if (!matched || !matched.handlers) {
      return {
        error: createError$1({
          statusCode: 404,
          name: "Not Found",
          statusMessage: `Cannot find any route matching ${path || "/"}.`
        })
      };
    }
    let handler = matched.handlers[method] || matched.handlers.all;
    if (!handler) {
      if (!_matcher) {
        _matcher = toRouteMatcher(_router);
      }
      const _matches = _matcher.matchAll(path).reverse();
      for (const _match of _matches) {
        if (_match.handlers[method]) {
          handler = _match.handlers[method];
          matched.handlers[method] = matched.handlers[method] || handler;
          break;
        }
        if (_match.handlers.all) {
          handler = _match.handlers.all;
          matched.handlers.all = matched.handlers.all || handler;
          break;
        }
      }
    }
    if (!handler) {
      return {
        error: createError$1({
          statusCode: 405,
          name: "Method Not Allowed",
          statusMessage: `Method ${method} is not allowed on this route.`
        })
      };
    }
    return { matched, handler };
  };
  const isPreemptive = opts.preemptive || opts.preemtive;
  router.handler = eventHandler((event) => {
    const match = matchHandler(
      event.path,
      event.method.toLowerCase()
    );
    if ("error" in match) {
      if (isPreemptive) {
        throw match.error;
      } else {
        return;
      }
    }
    event.context.matchedRoute = match.matched;
    const params = match.matched.params || {};
    event.context.params = params;
    return Promise.resolve(match.handler(event)).then((res) => {
      if (res === void 0 && isPreemptive) {
        return null;
      }
      return res;
    });
  });
  router.handler.__resolve__ = async (path) => {
    path = withLeadingSlash(path);
    const match = matchHandler(path);
    if ("error" in match) {
      return;
    }
    let res = {
      route: match.matched.path,
      handler: match.handler
    };
    if (match.handler.__resolve__) {
      const _res = await match.handler.__resolve__(path);
      if (!_res) {
        return;
      }
      res = { ...res, ..._res };
    }
    return res;
  };
  return router;
}
function toNodeListener(app) {
  const toNodeHandle = async function(req, res) {
    const event = createEvent(req, res);
    try {
      await app.handler(event);
    } catch (_error) {
      const error = createError$1(_error);
      if (!isError(_error)) {
        error.unhandled = true;
      }
      setResponseStatus(event, error.statusCode, error.statusMessage);
      if (app.options.onError) {
        await app.options.onError(error, event);
      }
      if (event.handled) {
        return;
      }
      if (error.unhandled || error.fatal) {
        console.error("[h3]", error.fatal ? "[fatal]" : "[unhandled]", error);
      }
      if (app.options.onBeforeResponse && !event._onBeforeResponseCalled) {
        await app.options.onBeforeResponse(event, { body: error });
      }
      await sendError(event, error, !!app.options.debug);
      if (app.options.onAfterResponse && !event._onAfterResponseCalled) {
        await app.options.onAfterResponse(event, { body: error });
      }
    }
  };
  return toNodeHandle;
}

function flatHooks(configHooks, hooks = {}, parentName) {
  for (const key in configHooks) {
    const subHook = configHooks[key];
    const name = parentName ? `${parentName}:${key}` : key;
    if (typeof subHook === "object" && subHook !== null) {
      flatHooks(subHook, hooks, name);
    } else if (typeof subHook === "function") {
      hooks[name] = subHook;
    }
  }
  return hooks;
}
const defaultTask = { run: (function_) => function_() };
const _createTask = () => defaultTask;
const createTask = typeof console.createTask !== "undefined" ? console.createTask : _createTask;
function serialTaskCaller(hooks, args) {
  const name = args.shift();
  const task = createTask(name);
  return hooks.reduce(
    (promise, hookFunction) => promise.then(() => task.run(() => hookFunction(...args))),
    Promise.resolve()
  );
}
function parallelTaskCaller(hooks, args) {
  const name = args.shift();
  const task = createTask(name);
  return Promise.all(hooks.map((hook) => task.run(() => hook(...args))));
}
function callEachWith(callbacks, arg0) {
  for (const callback of [...callbacks]) {
    callback(arg0);
  }
}

class Hookable {
  constructor() {
    this._hooks = {};
    this._before = void 0;
    this._after = void 0;
    this._deprecatedMessages = void 0;
    this._deprecatedHooks = {};
    this.hook = this.hook.bind(this);
    this.callHook = this.callHook.bind(this);
    this.callHookWith = this.callHookWith.bind(this);
  }
  hook(name, function_, options = {}) {
    if (!name || typeof function_ !== "function") {
      return () => {
      };
    }
    const originalName = name;
    let dep;
    while (this._deprecatedHooks[name]) {
      dep = this._deprecatedHooks[name];
      name = dep.to;
    }
    if (dep && !options.allowDeprecated) {
      let message = dep.message;
      if (!message) {
        message = `${originalName} hook has been deprecated` + (dep.to ? `, please use ${dep.to}` : "");
      }
      if (!this._deprecatedMessages) {
        this._deprecatedMessages = /* @__PURE__ */ new Set();
      }
      if (!this._deprecatedMessages.has(message)) {
        console.warn(message);
        this._deprecatedMessages.add(message);
      }
    }
    if (!function_.name) {
      try {
        Object.defineProperty(function_, "name", {
          get: () => "_" + name.replace(/\W+/g, "_") + "_hook_cb",
          configurable: true
        });
      } catch {
      }
    }
    this._hooks[name] = this._hooks[name] || [];
    this._hooks[name].push(function_);
    return () => {
      if (function_) {
        this.removeHook(name, function_);
        function_ = void 0;
      }
    };
  }
  hookOnce(name, function_) {
    let _unreg;
    let _function = (...arguments_) => {
      if (typeof _unreg === "function") {
        _unreg();
      }
      _unreg = void 0;
      _function = void 0;
      return function_(...arguments_);
    };
    _unreg = this.hook(name, _function);
    return _unreg;
  }
  removeHook(name, function_) {
    if (this._hooks[name]) {
      const index = this._hooks[name].indexOf(function_);
      if (index !== -1) {
        this._hooks[name].splice(index, 1);
      }
      if (this._hooks[name].length === 0) {
        delete this._hooks[name];
      }
    }
  }
  deprecateHook(name, deprecated) {
    this._deprecatedHooks[name] = typeof deprecated === "string" ? { to: deprecated } : deprecated;
    const _hooks = this._hooks[name] || [];
    delete this._hooks[name];
    for (const hook of _hooks) {
      this.hook(name, hook);
    }
  }
  deprecateHooks(deprecatedHooks) {
    Object.assign(this._deprecatedHooks, deprecatedHooks);
    for (const name in deprecatedHooks) {
      this.deprecateHook(name, deprecatedHooks[name]);
    }
  }
  addHooks(configHooks) {
    const hooks = flatHooks(configHooks);
    const removeFns = Object.keys(hooks).map(
      (key) => this.hook(key, hooks[key])
    );
    return () => {
      for (const unreg of removeFns.splice(0, removeFns.length)) {
        unreg();
      }
    };
  }
  removeHooks(configHooks) {
    const hooks = flatHooks(configHooks);
    for (const key in hooks) {
      this.removeHook(key, hooks[key]);
    }
  }
  removeAllHooks() {
    for (const key in this._hooks) {
      delete this._hooks[key];
    }
  }
  callHook(name, ...arguments_) {
    arguments_.unshift(name);
    return this.callHookWith(serialTaskCaller, name, ...arguments_);
  }
  callHookParallel(name, ...arguments_) {
    arguments_.unshift(name);
    return this.callHookWith(parallelTaskCaller, name, ...arguments_);
  }
  callHookWith(caller, name, ...arguments_) {
    const event = this._before || this._after ? { name, args: arguments_, context: {} } : void 0;
    if (this._before) {
      callEachWith(this._before, event);
    }
    const result = caller(
      name in this._hooks ? [...this._hooks[name]] : [],
      arguments_
    );
    if (result instanceof Promise) {
      return result.finally(() => {
        if (this._after && event) {
          callEachWith(this._after, event);
        }
      });
    }
    if (this._after && event) {
      callEachWith(this._after, event);
    }
    return result;
  }
  beforeEach(function_) {
    this._before = this._before || [];
    this._before.push(function_);
    return () => {
      if (this._before !== void 0) {
        const index = this._before.indexOf(function_);
        if (index !== -1) {
          this._before.splice(index, 1);
        }
      }
    };
  }
  afterEach(function_) {
    this._after = this._after || [];
    this._after.push(function_);
    return () => {
      if (this._after !== void 0) {
        const index = this._after.indexOf(function_);
        if (index !== -1) {
          this._after.splice(index, 1);
        }
      }
    };
  }
}
function createHooks() {
  return new Hookable();
}

const s$1=globalThis.Headers,i=globalThis.AbortController,l=globalThis.fetch||(()=>{throw new Error("[node-fetch-native] Failed to fetch: `globalThis.fetch` is not available!")});

class FetchError extends Error {
  constructor(message, opts) {
    super(message, opts);
    this.name = "FetchError";
    if (opts?.cause && !this.cause) {
      this.cause = opts.cause;
    }
  }
}
function createFetchError(ctx) {
  const errorMessage = ctx.error?.message || ctx.error?.toString() || "";
  const method = ctx.request?.method || ctx.options?.method || "GET";
  const url = ctx.request?.url || String(ctx.request) || "/";
  const requestStr = `[${method}] ${JSON.stringify(url)}`;
  const statusStr = ctx.response ? `${ctx.response.status} ${ctx.response.statusText}` : "<no response>";
  const message = `${requestStr}: ${statusStr}${errorMessage ? ` ${errorMessage}` : ""}`;
  const fetchError = new FetchError(
    message,
    ctx.error ? { cause: ctx.error } : void 0
  );
  for (const key of ["request", "options", "response"]) {
    Object.defineProperty(fetchError, key, {
      get() {
        return ctx[key];
      }
    });
  }
  for (const [key, refKey] of [
    ["data", "_data"],
    ["status", "status"],
    ["statusCode", "status"],
    ["statusText", "statusText"],
    ["statusMessage", "statusText"]
  ]) {
    Object.defineProperty(fetchError, key, {
      get() {
        return ctx.response && ctx.response[refKey];
      }
    });
  }
  return fetchError;
}

const payloadMethods = new Set(
  Object.freeze(["PATCH", "POST", "PUT", "DELETE"])
);
function isPayloadMethod(method = "GET") {
  return payloadMethods.has(method.toUpperCase());
}
function isJSONSerializable(value) {
  if (value === void 0) {
    return false;
  }
  const t = typeof value;
  if (t === "string" || t === "number" || t === "boolean" || t === null) {
    return true;
  }
  if (t !== "object") {
    return false;
  }
  if (Array.isArray(value)) {
    return true;
  }
  if (value.buffer) {
    return false;
  }
  if (value instanceof FormData || value instanceof URLSearchParams) {
    return false;
  }
  return value.constructor && value.constructor.name === "Object" || typeof value.toJSON === "function";
}
const textTypes = /* @__PURE__ */ new Set([
  "image/svg",
  "application/xml",
  "application/xhtml",
  "application/html"
]);
const JSON_RE = /^application\/(?:[\w!#$%&*.^`~-]*\+)?json(;.+)?$/i;
function detectResponseType(_contentType = "") {
  if (!_contentType) {
    return "json";
  }
  const contentType = _contentType.split(";").shift() || "";
  if (JSON_RE.test(contentType)) {
    return "json";
  }
  if (contentType === "text/event-stream") {
    return "stream";
  }
  if (textTypes.has(contentType) || contentType.startsWith("text/")) {
    return "text";
  }
  return "blob";
}
function resolveFetchOptions(request, input, defaults, Headers) {
  const headers = mergeHeaders(
    input?.headers ?? request?.headers,
    defaults?.headers,
    Headers
  );
  let query;
  if (defaults?.query || defaults?.params || input?.params || input?.query) {
    query = {
      ...defaults?.params,
      ...defaults?.query,
      ...input?.params,
      ...input?.query
    };
  }
  return {
    ...defaults,
    ...input,
    query,
    params: query,
    headers
  };
}
function mergeHeaders(input, defaults, Headers) {
  if (!defaults) {
    return new Headers(input);
  }
  const headers = new Headers(defaults);
  if (input) {
    for (const [key, value] of Symbol.iterator in input || Array.isArray(input) ? input : new Headers(input)) {
      headers.set(key, value);
    }
  }
  return headers;
}
async function callHooks(context, hooks) {
  if (hooks) {
    if (Array.isArray(hooks)) {
      for (const hook of hooks) {
        await hook(context);
      }
    } else {
      await hooks(context);
    }
  }
}

const retryStatusCodes = /* @__PURE__ */ new Set([
  408,
  // Request Timeout
  409,
  // Conflict
  425,
  // Too Early (Experimental)
  429,
  // Too Many Requests
  500,
  // Internal Server Error
  502,
  // Bad Gateway
  503,
  // Service Unavailable
  504
  // Gateway Timeout
]);
const nullBodyResponses = /* @__PURE__ */ new Set([101, 204, 205, 304]);
function createFetch(globalOptions = {}) {
  const {
    fetch = globalThis.fetch,
    Headers = globalThis.Headers,
    AbortController = globalThis.AbortController
  } = globalOptions;
  async function onError(context) {
    const isAbort = context.error && context.error.name === "AbortError" && !context.options.timeout || false;
    if (context.options.retry !== false && !isAbort) {
      let retries;
      if (typeof context.options.retry === "number") {
        retries = context.options.retry;
      } else {
        retries = isPayloadMethod(context.options.method) ? 0 : 1;
      }
      const responseCode = context.response && context.response.status || 500;
      if (retries > 0 && (Array.isArray(context.options.retryStatusCodes) ? context.options.retryStatusCodes.includes(responseCode) : retryStatusCodes.has(responseCode))) {
        const retryDelay = typeof context.options.retryDelay === "function" ? context.options.retryDelay(context) : context.options.retryDelay || 0;
        if (retryDelay > 0) {
          await new Promise((resolve) => setTimeout(resolve, retryDelay));
        }
        return $fetchRaw(context.request, {
          ...context.options,
          retry: retries - 1
        });
      }
    }
    const error = createFetchError(context);
    if (Error.captureStackTrace) {
      Error.captureStackTrace(error, $fetchRaw);
    }
    throw error;
  }
  const $fetchRaw = async function $fetchRaw2(_request, _options = {}) {
    const context = {
      request: _request,
      options: resolveFetchOptions(
        _request,
        _options,
        globalOptions.defaults,
        Headers
      ),
      response: void 0,
      error: void 0
    };
    if (context.options.method) {
      context.options.method = context.options.method.toUpperCase();
    }
    if (context.options.onRequest) {
      await callHooks(context, context.options.onRequest);
      if (!(context.options.headers instanceof Headers)) {
        context.options.headers = new Headers(
          context.options.headers || {}
          /* compat */
        );
      }
    }
    if (typeof context.request === "string") {
      if (context.options.baseURL) {
        context.request = withBase(context.request, context.options.baseURL);
      }
      if (context.options.query) {
        context.request = withQuery(context.request, context.options.query);
        delete context.options.query;
      }
      if ("query" in context.options) {
        delete context.options.query;
      }
      if ("params" in context.options) {
        delete context.options.params;
      }
    }
    if (context.options.body && isPayloadMethod(context.options.method)) {
      if (isJSONSerializable(context.options.body)) {
        const contentType = context.options.headers.get("content-type");
        if (typeof context.options.body !== "string") {
          context.options.body = contentType === "application/x-www-form-urlencoded" ? new URLSearchParams(
            context.options.body
          ).toString() : JSON.stringify(context.options.body);
        }
        if (!contentType) {
          context.options.headers.set("content-type", "application/json");
        }
        if (!context.options.headers.has("accept")) {
          context.options.headers.set("accept", "application/json");
        }
      } else if (
        // ReadableStream Body
        "pipeTo" in context.options.body && typeof context.options.body.pipeTo === "function" || // Node.js Stream Body
        typeof context.options.body.pipe === "function"
      ) {
        if (!("duplex" in context.options)) {
          context.options.duplex = "half";
        }
      }
    }
    let abortTimeout;
    if (!context.options.signal && context.options.timeout) {
      const controller = new AbortController();
      abortTimeout = setTimeout(() => {
        const error = new Error(
          "[TimeoutError]: The operation was aborted due to timeout"
        );
        error.name = "TimeoutError";
        error.code = 23;
        controller.abort(error);
      }, context.options.timeout);
      context.options.signal = controller.signal;
    }
    try {
      context.response = await fetch(
        context.request,
        context.options
      );
    } catch (error) {
      context.error = error;
      if (context.options.onRequestError) {
        await callHooks(
          context,
          context.options.onRequestError
        );
      }
      return await onError(context);
    } finally {
      if (abortTimeout) {
        clearTimeout(abortTimeout);
      }
    }
    const hasBody = (context.response.body || // https://github.com/unjs/ofetch/issues/324
    // https://github.com/unjs/ofetch/issues/294
    // https://github.com/JakeChampion/fetch/issues/1454
    context.response._bodyInit) && !nullBodyResponses.has(context.response.status) && context.options.method !== "HEAD";
    if (hasBody) {
      const responseType = (context.options.parseResponse ? "json" : context.options.responseType) || detectResponseType(context.response.headers.get("content-type") || "");
      switch (responseType) {
        case "json": {
          const data = await context.response.text();
          const parseFunction = context.options.parseResponse || destr;
          context.response._data = parseFunction(data);
          break;
        }
        case "stream": {
          context.response._data = context.response.body || context.response._bodyInit;
          break;
        }
        default: {
          context.response._data = await context.response[responseType]();
        }
      }
    }
    if (context.options.onResponse) {
      await callHooks(
        context,
        context.options.onResponse
      );
    }
    if (!context.options.ignoreResponseError && context.response.status >= 400 && context.response.status < 600) {
      if (context.options.onResponseError) {
        await callHooks(
          context,
          context.options.onResponseError
        );
      }
      return await onError(context);
    }
    return context.response;
  };
  const $fetch = async function $fetch2(request, options) {
    const r = await $fetchRaw(request, options);
    return r._data;
  };
  $fetch.raw = $fetchRaw;
  $fetch.native = (...args) => fetch(...args);
  $fetch.create = (defaultOptions = {}, customGlobalOptions = {}) => createFetch({
    ...globalOptions,
    ...customGlobalOptions,
    defaults: {
      ...globalOptions.defaults,
      ...customGlobalOptions.defaults,
      ...defaultOptions
    }
  });
  return $fetch;
}

function createNodeFetch() {
  const useKeepAlive = JSON.parse(process.env.FETCH_KEEP_ALIVE || "false");
  if (!useKeepAlive) {
    return l;
  }
  const agentOptions = { keepAlive: true };
  const httpAgent = new http.Agent(agentOptions);
  const httpsAgent = new https.Agent(agentOptions);
  const nodeFetchOptions = {
    agent(parsedURL) {
      return parsedURL.protocol === "http:" ? httpAgent : httpsAgent;
    }
  };
  return function nodeFetchWithKeepAlive(input, init) {
    return l(input, { ...nodeFetchOptions, ...init });
  };
}
const fetch = globalThis.fetch ? (...args) => globalThis.fetch(...args) : createNodeFetch();
const Headers$1 = globalThis.Headers || s$1;
const AbortController = globalThis.AbortController || i;
createFetch({ fetch, Headers: Headers$1, AbortController });

function wrapToPromise(value) {
  if (!value || typeof value.then !== "function") {
    return Promise.resolve(value);
  }
  return value;
}
function asyncCall(function_, ...arguments_) {
  try {
    return wrapToPromise(function_(...arguments_));
  } catch (error) {
    return Promise.reject(error);
  }
}
function isPrimitive(value) {
  const type = typeof value;
  return value === null || type !== "object" && type !== "function";
}
function isPureObject(value) {
  const proto = Object.getPrototypeOf(value);
  return !proto || proto.isPrototypeOf(Object);
}
function stringify(value) {
  if (isPrimitive(value)) {
    return String(value);
  }
  if (isPureObject(value) || Array.isArray(value)) {
    return JSON.stringify(value);
  }
  if (typeof value.toJSON === "function") {
    return stringify(value.toJSON());
  }
  throw new Error("[unstorage] Cannot stringify value!");
}
const BASE64_PREFIX = "base64:";
function serializeRaw(value) {
  if (typeof value === "string") {
    return value;
  }
  return BASE64_PREFIX + base64Encode(value);
}
function deserializeRaw(value) {
  if (typeof value !== "string") {
    return value;
  }
  if (!value.startsWith(BASE64_PREFIX)) {
    return value;
  }
  return base64Decode(value.slice(BASE64_PREFIX.length));
}
function base64Decode(input) {
  if (globalThis.Buffer) {
    return Buffer.from(input, "base64");
  }
  return Uint8Array.from(
    globalThis.atob(input),
    (c) => c.codePointAt(0)
  );
}
function base64Encode(input) {
  if (globalThis.Buffer) {
    return Buffer.from(input).toString("base64");
  }
  return globalThis.btoa(String.fromCodePoint(...input));
}

const storageKeyProperties = [
  "has",
  "hasItem",
  "get",
  "getItem",
  "getItemRaw",
  "set",
  "setItem",
  "setItemRaw",
  "del",
  "remove",
  "removeItem",
  "getMeta",
  "setMeta",
  "removeMeta",
  "getKeys",
  "clear",
  "mount",
  "unmount"
];
function prefixStorage(storage, base) {
  base = normalizeBaseKey(base);
  if (!base) {
    return storage;
  }
  const nsStorage = { ...storage };
  for (const property of storageKeyProperties) {
    nsStorage[property] = (key = "", ...args) => (
      // @ts-ignore
      storage[property](base + key, ...args)
    );
  }
  nsStorage.getKeys = (key = "", ...arguments_) => storage.getKeys(base + key, ...arguments_).then((keys) => keys.map((key2) => key2.slice(base.length)));
  nsStorage.keys = nsStorage.getKeys;
  nsStorage.getItems = async (items, commonOptions) => {
    const prefixedItems = items.map(
      (item) => typeof item === "string" ? base + item : { ...item, key: base + item.key }
    );
    const results = await storage.getItems(prefixedItems, commonOptions);
    return results.map((entry) => ({
      key: entry.key.slice(base.length),
      value: entry.value
    }));
  };
  nsStorage.setItems = async (items, commonOptions) => {
    const prefixedItems = items.map((item) => ({
      key: base + item.key,
      value: item.value,
      options: item.options
    }));
    return storage.setItems(prefixedItems, commonOptions);
  };
  return nsStorage;
}
function normalizeKey$1(key) {
  if (!key) {
    return "";
  }
  return key.split("?")[0]?.replace(/[/\\]/g, ":").replace(/:+/g, ":").replace(/^:|:$/g, "") || "";
}
function joinKeys(...keys) {
  return normalizeKey$1(keys.join(":"));
}
function normalizeBaseKey(base) {
  base = normalizeKey$1(base);
  return base ? base + ":" : "";
}
function filterKeyByDepth(key, depth) {
  if (depth === void 0) {
    return true;
  }
  let substrCount = 0;
  let index = key.indexOf(":");
  while (index > -1) {
    substrCount++;
    index = key.indexOf(":", index + 1);
  }
  return substrCount <= depth;
}
function filterKeyByBase(key, base) {
  if (base) {
    return key.startsWith(base) && key[key.length - 1] !== "$";
  }
  return key[key.length - 1] !== "$";
}

function defineDriver$1(factory) {
  return factory;
}

const DRIVER_NAME$1 = "memory";
const memory = defineDriver$1(() => {
  const data = /* @__PURE__ */ new Map();
  return {
    name: DRIVER_NAME$1,
    getInstance: () => data,
    hasItem(key) {
      return data.has(key);
    },
    getItem(key) {
      return data.get(key) ?? null;
    },
    getItemRaw(key) {
      return data.get(key) ?? null;
    },
    setItem(key, value) {
      data.set(key, value);
    },
    setItemRaw(key, value) {
      data.set(key, value);
    },
    removeItem(key) {
      data.delete(key);
    },
    getKeys() {
      return [...data.keys()];
    },
    clear() {
      data.clear();
    },
    dispose() {
      data.clear();
    }
  };
});

function createStorage(options = {}) {
  const context = {
    mounts: { "": options.driver || memory() },
    mountpoints: [""],
    watching: false,
    watchListeners: [],
    unwatch: {}
  };
  const getMount = (key) => {
    for (const base of context.mountpoints) {
      if (key.startsWith(base)) {
        return {
          base,
          relativeKey: key.slice(base.length),
          driver: context.mounts[base]
        };
      }
    }
    return {
      base: "",
      relativeKey: key,
      driver: context.mounts[""]
    };
  };
  const getMounts = (base, includeParent) => {
    return context.mountpoints.filter(
      (mountpoint) => mountpoint.startsWith(base) || includeParent && base.startsWith(mountpoint)
    ).map((mountpoint) => ({
      relativeBase: base.length > mountpoint.length ? base.slice(mountpoint.length) : void 0,
      mountpoint,
      driver: context.mounts[mountpoint]
    }));
  };
  const onChange = (event, key) => {
    if (!context.watching) {
      return;
    }
    key = normalizeKey$1(key);
    for (const listener of context.watchListeners) {
      listener(event, key);
    }
  };
  const startWatch = async () => {
    if (context.watching) {
      return;
    }
    context.watching = true;
    for (const mountpoint in context.mounts) {
      context.unwatch[mountpoint] = await watch(
        context.mounts[mountpoint],
        onChange,
        mountpoint
      );
    }
  };
  const stopWatch = async () => {
    if (!context.watching) {
      return;
    }
    for (const mountpoint in context.unwatch) {
      await context.unwatch[mountpoint]();
    }
    context.unwatch = {};
    context.watching = false;
  };
  const runBatch = (items, commonOptions, cb) => {
    const batches = /* @__PURE__ */ new Map();
    const getBatch = (mount) => {
      let batch = batches.get(mount.base);
      if (!batch) {
        batch = {
          driver: mount.driver,
          base: mount.base,
          items: []
        };
        batches.set(mount.base, batch);
      }
      return batch;
    };
    for (const item of items) {
      const isStringItem = typeof item === "string";
      const key = normalizeKey$1(isStringItem ? item : item.key);
      const value = isStringItem ? void 0 : item.value;
      const options2 = isStringItem || !item.options ? commonOptions : { ...commonOptions, ...item.options };
      const mount = getMount(key);
      getBatch(mount).items.push({
        key,
        value,
        relativeKey: mount.relativeKey,
        options: options2
      });
    }
    return Promise.all([...batches.values()].map((batch) => cb(batch))).then(
      (r) => r.flat()
    );
  };
  const storage = {
    // Item
    hasItem(key, opts = {}) {
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      return asyncCall(driver.hasItem, relativeKey, opts);
    },
    getItem(key, opts = {}) {
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      return asyncCall(driver.getItem, relativeKey, opts).then(
        (value) => destr(value)
      );
    },
    getItems(items, commonOptions = {}) {
      return runBatch(items, commonOptions, (batch) => {
        if (batch.driver.getItems) {
          return asyncCall(
            batch.driver.getItems,
            batch.items.map((item) => ({
              key: item.relativeKey,
              options: item.options
            })),
            commonOptions
          ).then(
            (r) => r.map((item) => ({
              key: joinKeys(batch.base, item.key),
              value: destr(item.value)
            }))
          );
        }
        return Promise.all(
          batch.items.map((item) => {
            return asyncCall(
              batch.driver.getItem,
              item.relativeKey,
              item.options
            ).then((value) => ({
              key: item.key,
              value: destr(value)
            }));
          })
        );
      });
    },
    getItemRaw(key, opts = {}) {
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      if (driver.getItemRaw) {
        return asyncCall(driver.getItemRaw, relativeKey, opts);
      }
      return asyncCall(driver.getItem, relativeKey, opts).then(
        (value) => deserializeRaw(value)
      );
    },
    async setItem(key, value, opts = {}) {
      if (value === void 0) {
        return storage.removeItem(key);
      }
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      if (!driver.setItem) {
        return;
      }
      await asyncCall(driver.setItem, relativeKey, stringify(value), opts);
      if (!driver.watch) {
        onChange("update", key);
      }
    },
    async setItems(items, commonOptions) {
      await runBatch(items, commonOptions, async (batch) => {
        if (batch.driver.setItems) {
          return asyncCall(
            batch.driver.setItems,
            batch.items.map((item) => ({
              key: item.relativeKey,
              value: stringify(item.value),
              options: item.options
            })),
            commonOptions
          );
        }
        if (!batch.driver.setItem) {
          return;
        }
        await Promise.all(
          batch.items.map((item) => {
            return asyncCall(
              batch.driver.setItem,
              item.relativeKey,
              stringify(item.value),
              item.options
            );
          })
        );
      });
    },
    async setItemRaw(key, value, opts = {}) {
      if (value === void 0) {
        return storage.removeItem(key, opts);
      }
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      if (driver.setItemRaw) {
        await asyncCall(driver.setItemRaw, relativeKey, value, opts);
      } else if (driver.setItem) {
        await asyncCall(driver.setItem, relativeKey, serializeRaw(value), opts);
      } else {
        return;
      }
      if (!driver.watch) {
        onChange("update", key);
      }
    },
    async removeItem(key, opts = {}) {
      if (typeof opts === "boolean") {
        opts = { removeMeta: opts };
      }
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      if (!driver.removeItem) {
        return;
      }
      await asyncCall(driver.removeItem, relativeKey, opts);
      if (opts.removeMeta || opts.removeMata) {
        await asyncCall(driver.removeItem, relativeKey + "$", opts);
      }
      if (!driver.watch) {
        onChange("remove", key);
      }
    },
    // Meta
    async getMeta(key, opts = {}) {
      if (typeof opts === "boolean") {
        opts = { nativeOnly: opts };
      }
      key = normalizeKey$1(key);
      const { relativeKey, driver } = getMount(key);
      const meta = /* @__PURE__ */ Object.create(null);
      if (driver.getMeta) {
        Object.assign(meta, await asyncCall(driver.getMeta, relativeKey, opts));
      }
      if (!opts.nativeOnly) {
        const value = await asyncCall(
          driver.getItem,
          relativeKey + "$",
          opts
        ).then((value_) => destr(value_));
        if (value && typeof value === "object") {
          if (typeof value.atime === "string") {
            value.atime = new Date(value.atime);
          }
          if (typeof value.mtime === "string") {
            value.mtime = new Date(value.mtime);
          }
          Object.assign(meta, value);
        }
      }
      return meta;
    },
    setMeta(key, value, opts = {}) {
      return this.setItem(key + "$", value, opts);
    },
    removeMeta(key, opts = {}) {
      return this.removeItem(key + "$", opts);
    },
    // Keys
    async getKeys(base, opts = {}) {
      base = normalizeBaseKey(base);
      const mounts = getMounts(base, true);
      let maskedMounts = [];
      const allKeys = [];
      let allMountsSupportMaxDepth = true;
      for (const mount of mounts) {
        if (!mount.driver.flags?.maxDepth) {
          allMountsSupportMaxDepth = false;
        }
        const rawKeys = await asyncCall(
          mount.driver.getKeys,
          mount.relativeBase,
          opts
        );
        for (const key of rawKeys) {
          const fullKey = mount.mountpoint + normalizeKey$1(key);
          if (!maskedMounts.some((p) => fullKey.startsWith(p))) {
            allKeys.push(fullKey);
          }
        }
        maskedMounts = [
          mount.mountpoint,
          ...maskedMounts.filter((p) => !p.startsWith(mount.mountpoint))
        ];
      }
      const shouldFilterByDepth = opts.maxDepth !== void 0 && !allMountsSupportMaxDepth;
      return allKeys.filter(
        (key) => (!shouldFilterByDepth || filterKeyByDepth(key, opts.maxDepth)) && filterKeyByBase(key, base)
      );
    },
    // Utils
    async clear(base, opts = {}) {
      base = normalizeBaseKey(base);
      await Promise.all(
        getMounts(base, false).map(async (m) => {
          if (m.driver.clear) {
            return asyncCall(m.driver.clear, m.relativeBase, opts);
          }
          if (m.driver.removeItem) {
            const keys = await m.driver.getKeys(m.relativeBase || "", opts);
            return Promise.all(
              keys.map((key) => m.driver.removeItem(key, opts))
            );
          }
        })
      );
    },
    async dispose() {
      await Promise.all(
        Object.values(context.mounts).map((driver) => dispose(driver))
      );
    },
    async watch(callback) {
      await startWatch();
      context.watchListeners.push(callback);
      return async () => {
        context.watchListeners = context.watchListeners.filter(
          (listener) => listener !== callback
        );
        if (context.watchListeners.length === 0) {
          await stopWatch();
        }
      };
    },
    async unwatch() {
      context.watchListeners = [];
      await stopWatch();
    },
    // Mount
    mount(base, driver) {
      base = normalizeBaseKey(base);
      if (base && context.mounts[base]) {
        throw new Error(`already mounted at ${base}`);
      }
      if (base) {
        context.mountpoints.push(base);
        context.mountpoints.sort((a, b) => b.length - a.length);
      }
      context.mounts[base] = driver;
      if (context.watching) {
        Promise.resolve(watch(driver, onChange, base)).then((unwatcher) => {
          context.unwatch[base] = unwatcher;
        }).catch(console.error);
      }
      return storage;
    },
    async unmount(base, _dispose = true) {
      base = normalizeBaseKey(base);
      if (!base || !context.mounts[base]) {
        return;
      }
      if (context.watching && base in context.unwatch) {
        context.unwatch[base]?.();
        delete context.unwatch[base];
      }
      if (_dispose) {
        await dispose(context.mounts[base]);
      }
      context.mountpoints = context.mountpoints.filter((key) => key !== base);
      delete context.mounts[base];
    },
    getMount(key = "") {
      key = normalizeKey$1(key) + ":";
      const m = getMount(key);
      return {
        driver: m.driver,
        base: m.base
      };
    },
    getMounts(base = "", opts = {}) {
      base = normalizeKey$1(base);
      const mounts = getMounts(base, opts.parents);
      return mounts.map((m) => ({
        driver: m.driver,
        base: m.mountpoint
      }));
    },
    // Aliases
    keys: (base, opts = {}) => storage.getKeys(base, opts),
    get: (key, opts = {}) => storage.getItem(key, opts),
    set: (key, value, opts = {}) => storage.setItem(key, value, opts),
    has: (key, opts = {}) => storage.hasItem(key, opts),
    del: (key, opts = {}) => storage.removeItem(key, opts),
    remove: (key, opts = {}) => storage.removeItem(key, opts)
  };
  return storage;
}
function watch(driver, onChange, base) {
  return driver.watch ? driver.watch((event, key) => onChange(event, base + key)) : () => {
  };
}
async function dispose(driver) {
  if (typeof driver.dispose === "function") {
    await asyncCall(driver.dispose);
  }
}

const _assets = {

};

const normalizeKey = function normalizeKey(key) {
  if (!key) {
    return "";
  }
  return key.split("?")[0]?.replace(/[/\\]/g, ":").replace(/:+/g, ":").replace(/^:|:$/g, "") || "";
};

const assets$1 = {
  getKeys() {
    return Promise.resolve(Object.keys(_assets))
  },
  hasItem (id) {
    id = normalizeKey(id);
    return Promise.resolve(id in _assets)
  },
  getItem (id) {
    id = normalizeKey(id);
    return Promise.resolve(_assets[id] ? _assets[id].import() : null)
  },
  getMeta (id) {
    id = normalizeKey(id);
    return Promise.resolve(_assets[id] ? _assets[id].meta : {})
  }
};

function defineDriver(factory) {
  return factory;
}
function createError(driver, message, opts) {
  const err = new Error(`[unstorage] [${driver}] ${message}`, opts);
  if (Error.captureStackTrace) {
    Error.captureStackTrace(err, createError);
  }
  return err;
}
function createRequiredError(driver, name) {
  if (Array.isArray(name)) {
    return createError(
      driver,
      `Missing some of the required options ${name.map((n) => "`" + n + "`").join(", ")}`
    );
  }
  return createError(driver, `Missing required option \`${name}\`.`);
}

function ignoreNotfound(err) {
  return err.code === "ENOENT" || err.code === "EISDIR" ? null : err;
}
function ignoreExists(err) {
  return err.code === "EEXIST" ? null : err;
}
async function writeFile(path, data, encoding) {
  await ensuredir(dirname$1(path));
  return promises.writeFile(path, data, encoding);
}
function readFile(path, encoding) {
  return promises.readFile(path, encoding).catch(ignoreNotfound);
}
function unlink(path) {
  return promises.unlink(path).catch(ignoreNotfound);
}
function readdir(dir) {
  return promises.readdir(dir, { withFileTypes: true }).catch(ignoreNotfound).then((r) => r || []);
}
async function ensuredir(dir) {
  if (existsSync(dir)) {
    return;
  }
  await ensuredir(dirname$1(dir)).catch(ignoreExists);
  await promises.mkdir(dir).catch(ignoreExists);
}
async function readdirRecursive(dir, ignore, maxDepth) {
  if (ignore && ignore(dir)) {
    return [];
  }
  const entries = await readdir(dir);
  const files = [];
  await Promise.all(
    entries.map(async (entry) => {
      const entryPath = resolve$1(dir, entry.name);
      if (entry.isDirectory()) {
        if (maxDepth === void 0 || maxDepth > 0) {
          const dirFiles = await readdirRecursive(
            entryPath,
            ignore,
            maxDepth === void 0 ? void 0 : maxDepth - 1
          );
          files.push(...dirFiles.map((f) => entry.name + "/" + f));
        }
      } else {
        if (!(ignore && ignore(entry.name))) {
          files.push(entry.name);
        }
      }
    })
  );
  return files;
}
async function rmRecursive(dir) {
  const entries = await readdir(dir);
  await Promise.all(
    entries.map((entry) => {
      const entryPath = resolve$1(dir, entry.name);
      if (entry.isDirectory()) {
        return rmRecursive(entryPath).then(() => promises.rmdir(entryPath));
      } else {
        return promises.unlink(entryPath);
      }
    })
  );
}

const PATH_TRAVERSE_RE = /\.\.:|\.\.$/;
const DRIVER_NAME = "fs-lite";
const unstorage_47drivers_47fs_45lite = defineDriver((opts = {}) => {
  if (!opts.base) {
    throw createRequiredError(DRIVER_NAME, "base");
  }
  opts.base = resolve$1(opts.base);
  const r = (key) => {
    if (PATH_TRAVERSE_RE.test(key)) {
      throw createError(
        DRIVER_NAME,
        `Invalid key: ${JSON.stringify(key)}. It should not contain .. segments`
      );
    }
    const resolved = join(opts.base, key.replace(/:/g, "/"));
    return resolved;
  };
  return {
    name: DRIVER_NAME,
    options: opts,
    flags: {
      maxDepth: true
    },
    hasItem(key) {
      return existsSync(r(key));
    },
    getItem(key) {
      return readFile(r(key), "utf8");
    },
    getItemRaw(key) {
      return readFile(r(key));
    },
    async getMeta(key) {
      const { atime, mtime, size, birthtime, ctime } = await promises.stat(r(key)).catch(() => ({}));
      return { atime, mtime, size, birthtime, ctime };
    },
    setItem(key, value) {
      if (opts.readOnly) {
        return;
      }
      return writeFile(r(key), value, "utf8");
    },
    setItemRaw(key, value) {
      if (opts.readOnly) {
        return;
      }
      return writeFile(r(key), value);
    },
    removeItem(key) {
      if (opts.readOnly) {
        return;
      }
      return unlink(r(key));
    },
    getKeys(_base, topts) {
      return readdirRecursive(r("."), opts.ignore, topts?.maxDepth);
    },
    async clear() {
      if (opts.readOnly || opts.noClear) {
        return;
      }
      await rmRecursive(r("."));
    }
  };
});

const storage$1 = createStorage({});

storage$1.mount('/assets', assets$1);

storage$1.mount('data', unstorage_47drivers_47fs_45lite({"driver":"fsLite","base":"./.data/kv"}));

function useStorage(base = "") {
  return base ? prefixStorage(storage$1, base) : storage$1;
}

const e=globalThis.process?.getBuiltinModule?.("crypto")?.hash,r="sha256",s="base64url";function digest(t){if(e)return e(r,t,s);const o=createHash(r).update(t);return globalThis.process?.versions?.webcontainer?o.digest().toString(s):o.digest(s)}

const Hasher = /* @__PURE__ */ (() => {
  class Hasher2 {
    buff = "";
    #context = /* @__PURE__ */ new Map();
    write(str) {
      this.buff += str;
    }
    dispatch(value) {
      const type = value === null ? "null" : typeof value;
      return this[type](value);
    }
    object(object) {
      if (object && typeof object.toJSON === "function") {
        return this.object(object.toJSON());
      }
      const objString = Object.prototype.toString.call(object);
      let objType = "";
      const objectLength = objString.length;
      objType = objectLength < 10 ? "unknown:[" + objString + "]" : objString.slice(8, objectLength - 1);
      objType = objType.toLowerCase();
      let objectNumber = null;
      if ((objectNumber = this.#context.get(object)) === void 0) {
        this.#context.set(object, this.#context.size);
      } else {
        return this.dispatch("[CIRCULAR:" + objectNumber + "]");
      }
      if (typeof Buffer !== "undefined" && Buffer.isBuffer && Buffer.isBuffer(object)) {
        this.write("buffer:");
        return this.write(object.toString("utf8"));
      }
      if (objType !== "object" && objType !== "function" && objType !== "asyncfunction") {
        if (this[objType]) {
          this[objType](object);
        } else {
          this.unknown(object, objType);
        }
      } else {
        const keys = Object.keys(object).sort();
        const extraKeys = [];
        this.write("object:" + (keys.length + extraKeys.length) + ":");
        const dispatchForKey = (key) => {
          this.dispatch(key);
          this.write(":");
          this.dispatch(object[key]);
          this.write(",");
        };
        for (const key of keys) {
          dispatchForKey(key);
        }
        for (const key of extraKeys) {
          dispatchForKey(key);
        }
      }
    }
    array(arr, unordered) {
      unordered = unordered === void 0 ? false : unordered;
      this.write("array:" + arr.length + ":");
      if (!unordered || arr.length <= 1) {
        for (const entry of arr) {
          this.dispatch(entry);
        }
        return;
      }
      const contextAdditions = /* @__PURE__ */ new Map();
      const entries = arr.map((entry) => {
        const hasher = new Hasher2();
        hasher.dispatch(entry);
        for (const [key, value] of hasher.#context) {
          contextAdditions.set(key, value);
        }
        return hasher.toString();
      });
      this.#context = contextAdditions;
      entries.sort();
      return this.array(entries, false);
    }
    date(date) {
      return this.write("date:" + date.toJSON());
    }
    symbol(sym) {
      return this.write("symbol:" + sym.toString());
    }
    unknown(value, type) {
      this.write(type);
      if (!value) {
        return;
      }
      this.write(":");
      if (value && typeof value.entries === "function") {
        return this.array(
          [...value.entries()],
          true
          /* ordered */
        );
      }
    }
    error(err) {
      return this.write("error:" + err.toString());
    }
    boolean(bool) {
      return this.write("bool:" + bool);
    }
    string(string) {
      this.write("string:" + string.length + ":");
      this.write(string);
    }
    function(fn) {
      this.write("fn:");
      if (isNativeFunction(fn)) {
        this.dispatch("[native]");
      } else {
        this.dispatch(fn.toString());
      }
    }
    number(number) {
      return this.write("number:" + number);
    }
    null() {
      return this.write("Null");
    }
    undefined() {
      return this.write("Undefined");
    }
    regexp(regex) {
      return this.write("regex:" + regex.toString());
    }
    arraybuffer(arr) {
      this.write("arraybuffer:");
      return this.dispatch(new Uint8Array(arr));
    }
    url(url) {
      return this.write("url:" + url.toString());
    }
    map(map) {
      this.write("map:");
      const arr = [...map];
      return this.array(arr, false);
    }
    set(set) {
      this.write("set:");
      const arr = [...set];
      return this.array(arr, false);
    }
    bigint(number) {
      return this.write("bigint:" + number.toString());
    }
  }
  for (const type of [
    "uint8array",
    "uint8clampedarray",
    "unt8array",
    "uint16array",
    "unt16array",
    "uint32array",
    "unt32array",
    "float32array",
    "float64array"
  ]) {
    Hasher2.prototype[type] = function(arr) {
      this.write(type + ":");
      return this.array([...arr], false);
    };
  }
  function isNativeFunction(f) {
    if (typeof f !== "function") {
      return false;
    }
    return Function.prototype.toString.call(f).slice(
      -15
      /* "[native code] }".length */
    ) === "[native code] }";
  }
  return Hasher2;
})();
function serialize(object) {
  const hasher = new Hasher();
  hasher.dispatch(object);
  return hasher.buff;
}
function hash(value) {
  return digest(typeof value === "string" ? value : serialize(value)).replace(/[-_]/g, "").slice(0, 10);
}

function defaultCacheOptions() {
  return {
    name: "_",
    base: "/cache",
    swr: true,
    maxAge: 1
  };
}
function defineCachedFunction(fn, opts = {}) {
  opts = { ...defaultCacheOptions(), ...opts };
  const pending = {};
  const group = opts.group || "nitro/functions";
  const name = opts.name || fn.name || "_";
  const integrity = opts.integrity || hash([fn, opts]);
  const validate = opts.validate || ((entry) => entry.value !== void 0);
  async function get(key, resolver, shouldInvalidateCache, event) {
    const cacheKey = [opts.base, group, name, key + ".json"].filter(Boolean).join(":").replace(/:\/$/, ":index");
    let entry = await useStorage().getItem(cacheKey).catch((error) => {
      console.error(`[cache] Cache read error.`, error);
      useNitroApp().captureError(error, { event, tags: ["cache"] });
    }) || {};
    if (typeof entry !== "object") {
      entry = {};
      const error = new Error("Malformed data read from cache.");
      console.error("[cache]", error);
      useNitroApp().captureError(error, { event, tags: ["cache"] });
    }
    const ttl = (opts.maxAge ?? 0) * 1e3;
    if (ttl) {
      entry.expires = Date.now() + ttl;
    }
    const expired = shouldInvalidateCache || entry.integrity !== integrity || ttl && Date.now() - (entry.mtime || 0) > ttl || validate(entry) === false;
    const _resolve = async () => {
      const isPending = pending[key];
      if (!isPending) {
        if (entry.value !== void 0 && (opts.staleMaxAge || 0) >= 0 && opts.swr === false) {
          entry.value = void 0;
          entry.integrity = void 0;
          entry.mtime = void 0;
          entry.expires = void 0;
        }
        pending[key] = Promise.resolve(resolver());
      }
      try {
        entry.value = await pending[key];
      } catch (error) {
        if (!isPending) {
          delete pending[key];
        }
        throw error;
      }
      if (!isPending) {
        entry.mtime = Date.now();
        entry.integrity = integrity;
        delete pending[key];
        if (validate(entry) !== false) {
          let setOpts;
          if (opts.maxAge && !opts.swr) {
            setOpts = { ttl: opts.maxAge };
          }
          const promise = useStorage().setItem(cacheKey, entry, setOpts).catch((error) => {
            console.error(`[cache] Cache write error.`, error);
            useNitroApp().captureError(error, { event, tags: ["cache"] });
          });
          if (event?.waitUntil) {
            event.waitUntil(promise);
          }
        }
      }
    };
    const _resolvePromise = expired ? _resolve() : Promise.resolve();
    if (entry.value === void 0) {
      await _resolvePromise;
    } else if (expired && event && event.waitUntil) {
      event.waitUntil(_resolvePromise);
    }
    if (opts.swr && validate(entry) !== false) {
      _resolvePromise.catch((error) => {
        console.error(`[cache] SWR handler error.`, error);
        useNitroApp().captureError(error, { event, tags: ["cache"] });
      });
      return entry;
    }
    return _resolvePromise.then(() => entry);
  }
  return async (...args) => {
    const shouldBypassCache = await opts.shouldBypassCache?.(...args);
    if (shouldBypassCache) {
      return fn(...args);
    }
    const key = await (opts.getKey || getKey)(...args);
    const shouldInvalidateCache = await opts.shouldInvalidateCache?.(...args);
    const entry = await get(
      key,
      () => fn(...args),
      shouldInvalidateCache,
      args[0] && isEvent(args[0]) ? args[0] : void 0
    );
    let value = entry.value;
    if (opts.transform) {
      value = await opts.transform(entry, ...args) || value;
    }
    return value;
  };
}
function cachedFunction(fn, opts = {}) {
  return defineCachedFunction(fn, opts);
}
function getKey(...args) {
  return args.length > 0 ? hash(args) : "";
}
function escapeKey(key) {
  return String(key).replace(/\W/g, "");
}
function defineCachedEventHandler(handler, opts = defaultCacheOptions()) {
  const variableHeaderNames = (opts.varies || []).filter(Boolean).map((h) => h.toLowerCase()).sort();
  const _opts = {
    ...opts,
    getKey: async (event) => {
      const customKey = await opts.getKey?.(event);
      if (customKey) {
        return escapeKey(customKey);
      }
      const _path = event.node.req.originalUrl || event.node.req.url || event.path;
      let _pathname;
      try {
        _pathname = escapeKey(decodeURI(parseURL(_path).pathname)).slice(0, 16) || "index";
      } catch {
        _pathname = "-";
      }
      const _hashedPath = `${_pathname}.${hash(_path)}`;
      const _headers = variableHeaderNames.map((header) => [header, event.node.req.headers[header]]).map(([name, value]) => `${escapeKey(name)}.${hash(value)}`);
      return [_hashedPath, ..._headers].join(":");
    },
    validate: (entry) => {
      if (!entry.value) {
        return false;
      }
      if (entry.value.code >= 400) {
        return false;
      }
      if (entry.value.body === void 0) {
        return false;
      }
      if (entry.value.headers.etag === "undefined" || entry.value.headers["last-modified"] === "undefined") {
        return false;
      }
      return true;
    },
    group: opts.group || "nitro/handlers",
    integrity: opts.integrity || hash([handler, opts])
  };
  const _cachedHandler = cachedFunction(
    async (incomingEvent) => {
      const variableHeaders = {};
      for (const header of variableHeaderNames) {
        const value = incomingEvent.node.req.headers[header];
        if (value !== void 0) {
          variableHeaders[header] = value;
        }
      }
      const reqProxy = cloneWithProxy(incomingEvent.node.req, {
        headers: variableHeaders
      });
      const resHeaders = {};
      let _resSendBody;
      const resProxy = cloneWithProxy(incomingEvent.node.res, {
        statusCode: 200,
        writableEnded: false,
        writableFinished: false,
        headersSent: false,
        closed: false,
        getHeader(name) {
          return resHeaders[name];
        },
        setHeader(name, value) {
          resHeaders[name] = value;
          return this;
        },
        getHeaderNames() {
          return Object.keys(resHeaders);
        },
        hasHeader(name) {
          return name in resHeaders;
        },
        removeHeader(name) {
          delete resHeaders[name];
        },
        getHeaders() {
          return resHeaders;
        },
        end(chunk, arg2, arg3) {
          if (typeof chunk === "string") {
            _resSendBody = chunk;
          }
          if (typeof arg2 === "function") {
            arg2();
          }
          if (typeof arg3 === "function") {
            arg3();
          }
          return this;
        },
        write(chunk, arg2, arg3) {
          if (typeof chunk === "string") {
            _resSendBody = chunk;
          }
          if (typeof arg2 === "function") {
            arg2(void 0);
          }
          if (typeof arg3 === "function") {
            arg3();
          }
          return true;
        },
        writeHead(statusCode, headers2) {
          this.statusCode = statusCode;
          if (headers2) {
            if (Array.isArray(headers2) || typeof headers2 === "string") {
              throw new TypeError("Raw headers  is not supported.");
            }
            for (const header in headers2) {
              const value = headers2[header];
              if (value !== void 0) {
                this.setHeader(
                  header,
                  value
                );
              }
            }
          }
          return this;
        }
      });
      const event = createEvent(reqProxy, resProxy);
      event.fetch = (url, fetchOptions) => fetchWithEvent(event, url, fetchOptions, {
        fetch: useNitroApp().localFetch
      });
      event.$fetch = (url, fetchOptions) => fetchWithEvent(event, url, fetchOptions, {
        fetch: globalThis.$fetch
      });
      event.waitUntil = incomingEvent.waitUntil;
      event.context = incomingEvent.context;
      event.context.cache = {
        options: _opts
      };
      const body = await handler(event) || _resSendBody;
      const headers = event.node.res.getHeaders();
      headers.etag = String(
        headers.Etag || headers.etag || `W/"${hash(body)}"`
      );
      headers["last-modified"] = String(
        headers["Last-Modified"] || headers["last-modified"] || (/* @__PURE__ */ new Date()).toUTCString()
      );
      const cacheControl = [];
      if (opts.swr) {
        if (opts.maxAge) {
          cacheControl.push(`s-maxage=${opts.maxAge}`);
        }
        if (opts.staleMaxAge) {
          cacheControl.push(`stale-while-revalidate=${opts.staleMaxAge}`);
        } else {
          cacheControl.push("stale-while-revalidate");
        }
      } else if (opts.maxAge) {
        cacheControl.push(`max-age=${opts.maxAge}`);
      }
      if (cacheControl.length > 0) {
        headers["cache-control"] = cacheControl.join(", ");
      }
      const cacheEntry = {
        code: event.node.res.statusCode,
        headers,
        body
      };
      return cacheEntry;
    },
    _opts
  );
  return defineEventHandler(async (event) => {
    if (opts.headersOnly) {
      if (handleCacheHeaders(event, { maxAge: opts.maxAge })) {
        return;
      }
      return handler(event);
    }
    const response = await _cachedHandler(
      event
    );
    if (event.node.res.headersSent || event.node.res.writableEnded) {
      return response.body;
    }
    if (handleCacheHeaders(event, {
      modifiedTime: new Date(response.headers["last-modified"]),
      etag: response.headers.etag,
      maxAge: opts.maxAge
    })) {
      return;
    }
    event.node.res.statusCode = response.code;
    for (const name in response.headers) {
      const value = response.headers[name];
      if (name === "set-cookie") {
        event.node.res.appendHeader(
          name,
          splitCookiesString(value)
        );
      } else {
        if (value !== void 0) {
          event.node.res.setHeader(name, value);
        }
      }
    }
    return response.body;
  });
}
function cloneWithProxy(obj, overrides) {
  return new Proxy(obj, {
    get(target, property, receiver) {
      if (property in overrides) {
        return overrides[property];
      }
      return Reflect.get(target, property, receiver);
    },
    set(target, property, value, receiver) {
      if (property in overrides) {
        overrides[property] = value;
        return true;
      }
      return Reflect.set(target, property, value, receiver);
    }
  });
}
const cachedEventHandler = defineCachedEventHandler;

function klona(x) {
	if (typeof x !== 'object') return x;

	var k, tmp, str=Object.prototype.toString.call(x);

	if (str === '[object Object]') {
		if (x.constructor !== Object && typeof x.constructor === 'function') {
			tmp = new x.constructor();
			for (k in x) {
				if (x.hasOwnProperty(k) && tmp[k] !== x[k]) {
					tmp[k] = klona(x[k]);
				}
			}
		} else {
			tmp = {}; // null
			for (k in x) {
				if (k === '__proto__') {
					Object.defineProperty(tmp, k, {
						value: klona(x[k]),
						configurable: true,
						enumerable: true,
						writable: true,
					});
				} else {
					tmp[k] = klona(x[k]);
				}
			}
		}
		return tmp;
	}

	if (str === '[object Array]') {
		k = x.length;
		for (tmp=Array(k); k--;) {
			tmp[k] = klona(x[k]);
		}
		return tmp;
	}

	if (str === '[object Set]') {
		tmp = new Set;
		x.forEach(function (val) {
			tmp.add(klona(val));
		});
		return tmp;
	}

	if (str === '[object Map]') {
		tmp = new Map;
		x.forEach(function (val, key) {
			tmp.set(klona(key), klona(val));
		});
		return tmp;
	}

	if (str === '[object Date]') {
		return new Date(+x);
	}

	if (str === '[object RegExp]') {
		tmp = new RegExp(x.source, x.flags);
		tmp.lastIndex = x.lastIndex;
		return tmp;
	}

	if (str === '[object DataView]') {
		return new x.constructor( klona(x.buffer) );
	}

	if (str === '[object ArrayBuffer]') {
		return x.slice(0);
	}

	// ArrayBuffer.isView(x)
	// ~> `new` bcuz `Buffer.slice` => ref
	if (str.slice(-6) === 'Array]') {
		return new x.constructor(x);
	}

	return x;
}

const inlineAppConfig = {
  "nuxt": {}
};



const appConfig = defuFn(inlineAppConfig);

const NUMBER_CHAR_RE = /\d/;
const STR_SPLITTERS = ["-", "_", "/", "."];
function isUppercase(char = "") {
  if (NUMBER_CHAR_RE.test(char)) {
    return void 0;
  }
  return char !== char.toLowerCase();
}
function splitByCase(str, separators) {
  const splitters = STR_SPLITTERS;
  const parts = [];
  if (!str || typeof str !== "string") {
    return parts;
  }
  let buff = "";
  let previousUpper;
  let previousSplitter;
  for (const char of str) {
    const isSplitter = splitters.includes(char);
    if (isSplitter === true) {
      parts.push(buff);
      buff = "";
      previousUpper = void 0;
      continue;
    }
    const isUpper = isUppercase(char);
    if (previousSplitter === false) {
      if (previousUpper === false && isUpper === true) {
        parts.push(buff);
        buff = char;
        previousUpper = isUpper;
        continue;
      }
      if (previousUpper === true && isUpper === false && buff.length > 1) {
        const lastChar = buff.at(-1);
        parts.push(buff.slice(0, Math.max(0, buff.length - 1)));
        buff = lastChar + char;
        previousUpper = isUpper;
        continue;
      }
    }
    buff += char;
    previousUpper = isUpper;
    previousSplitter = isSplitter;
  }
  parts.push(buff);
  return parts;
}
function kebabCase(str, joiner) {
  return str ? (Array.isArray(str) ? str : splitByCase(str)).map((p) => p.toLowerCase()).join(joiner) : "";
}
function snakeCase(str) {
  return kebabCase(str || "", "_");
}

function getEnv(key, opts) {
  const envKey = snakeCase(key).toUpperCase();
  return destr(
    process.env[opts.prefix + envKey] ?? process.env[opts.altPrefix + envKey]
  );
}
function _isObject(input) {
  return typeof input === "object" && !Array.isArray(input);
}
function applyEnv(obj, opts, parentKey = "") {
  for (const key in obj) {
    const subKey = parentKey ? `${parentKey}_${key}` : key;
    const envValue = getEnv(subKey, opts);
    if (_isObject(obj[key])) {
      if (_isObject(envValue)) {
        obj[key] = { ...obj[key], ...envValue };
        applyEnv(obj[key], opts, subKey);
      } else if (envValue === void 0) {
        applyEnv(obj[key], opts, subKey);
      } else {
        obj[key] = envValue ?? obj[key];
      }
    } else {
      obj[key] = envValue ?? obj[key];
    }
    if (opts.envExpansion && typeof obj[key] === "string") {
      obj[key] = _expandFromEnv(obj[key]);
    }
  }
  return obj;
}
const envExpandRx = /\{\{([^{}]*)\}\}/g;
function _expandFromEnv(value) {
  return value.replace(envExpandRx, (match, key) => {
    return process.env[key] || match;
  });
}

const _inlineRuntimeConfig = {
  "app": {
    "baseURL": "/",
    "buildId": "93d3b9bc-a83f-4c27-992c-9a162ba1c1c8",
    "buildAssetsDir": "/_nuxt/",
    "cdnURL": ""
  },
  "nitro": {
    "envPrefix": "NUXT_",
    "routeRules": {
      "/__nuxt_error": {
        "cache": false
      },
      "/_nuxt/builds/meta/**": {
        "headers": {
          "cache-control": "public, max-age=31536000, immutable"
        }
      },
      "/_nuxt/builds/**": {
        "headers": {
          "cache-control": "public, max-age=1, immutable"
        }
      },
      "/_nuxt/**": {
        "headers": {
          "cache-control": "public, max-age=31536000, immutable"
        }
      }
    }
  },
  "public": {
    "apiBase": "http://localhost:8000",
    "siteUrl": "http://localhost:3000",
    "i18n": {
      "baseUrl": "",
      "defaultLocale": "th",
      "rootRedirect": "",
      "redirectStatusCode": 302,
      "skipSettingLocaleOnNavigate": false,
      "locales": [
        {
          "code": "th",
          "name": "ภาษาไทย",
          "language": ""
        },
        {
          "code": "en",
          "name": "English",
          "language": ""
        }
      ],
      "detectBrowserLanguage": {
        "alwaysRedirect": false,
        "cookieCrossOrigin": false,
        "cookieDomain": "",
        "cookieKey": "i18n_redirected",
        "cookieSecure": false,
        "fallbackLocale": "th",
        "redirectOn": "root",
        "useCookie": true
      },
      "experimental": {
        "localeDetector": "",
        "typedPages": true,
        "typedOptionsAndMessages": false,
        "alternateLinkCanonicalQueries": true,
        "devCache": false,
        "cacheLifetime": "",
        "stripMessagesPayload": false,
        "preload": false,
        "strictSeo": false,
        "nitroContextDetection": true,
        "httpCacheDuration": 10
      },
      "domainLocales": {
        "th": {
          "domain": ""
        },
        "en": {
          "domain": ""
        }
      }
    }
  }
};
const envOptions = {
  prefix: "NITRO_",
  altPrefix: _inlineRuntimeConfig.nitro.envPrefix ?? process.env.NITRO_ENV_PREFIX ?? "_",
  envExpansion: _inlineRuntimeConfig.nitro.envExpansion ?? process.env.NITRO_ENV_EXPANSION ?? false
};
const _sharedRuntimeConfig = _deepFreeze(
  applyEnv(klona(_inlineRuntimeConfig), envOptions)
);
function useRuntimeConfig(event) {
  if (!event) {
    return _sharedRuntimeConfig;
  }
  if (event.context.nitro.runtimeConfig) {
    return event.context.nitro.runtimeConfig;
  }
  const runtimeConfig = klona(_inlineRuntimeConfig);
  applyEnv(runtimeConfig, envOptions);
  event.context.nitro.runtimeConfig = runtimeConfig;
  return runtimeConfig;
}
_deepFreeze(klona(appConfig));
function _deepFreeze(object) {
  const propNames = Object.getOwnPropertyNames(object);
  for (const name of propNames) {
    const value = object[name];
    if (value && typeof value === "object") {
      _deepFreeze(value);
    }
  }
  return Object.freeze(object);
}
new Proxy(/* @__PURE__ */ Object.create(null), {
  get: (_, prop) => {
    console.warn(
      "Please use `useRuntimeConfig()` instead of accessing config directly."
    );
    const runtimeConfig = useRuntimeConfig();
    if (prop in runtimeConfig) {
      return runtimeConfig[prop];
    }
    return void 0;
  }
});

const config = useRuntimeConfig();
const _routeRulesMatcher = toRouteMatcher(
  createRouter$1({ routes: config.nitro.routeRules })
);
function createRouteRulesHandler(ctx) {
  return eventHandler((event) => {
    const routeRules = getRouteRules(event);
    if (routeRules.headers) {
      setHeaders(event, routeRules.headers);
    }
    if (routeRules.redirect) {
      let target = routeRules.redirect.to;
      if (target.endsWith("/**")) {
        let targetPath = event.path;
        const strpBase = routeRules.redirect._redirectStripBase;
        if (strpBase) {
          targetPath = withoutBase(targetPath, strpBase);
        }
        target = joinURL(target.slice(0, -3), targetPath);
      } else if (event.path.includes("?")) {
        const query = getQuery$1(event.path);
        target = withQuery(target, query);
      }
      return sendRedirect(event, target, routeRules.redirect.statusCode);
    }
    if (routeRules.proxy) {
      let target = routeRules.proxy.to;
      if (target.endsWith("/**")) {
        let targetPath = event.path;
        const strpBase = routeRules.proxy._proxyStripBase;
        if (strpBase) {
          targetPath = withoutBase(targetPath, strpBase);
        }
        target = joinURL(target.slice(0, -3), targetPath);
      } else if (event.path.includes("?")) {
        const query = getQuery$1(event.path);
        target = withQuery(target, query);
      }
      return proxyRequest(event, target, {
        fetch: ctx.localFetch,
        ...routeRules.proxy
      });
    }
  });
}
function getRouteRules(event) {
  event.context._nitro = event.context._nitro || {};
  if (!event.context._nitro.routeRules) {
    event.context._nitro.routeRules = getRouteRulesForPath(
      withoutBase(event.path.split("?")[0], useRuntimeConfig().app.baseURL)
    );
  }
  return event.context._nitro.routeRules;
}
function getRouteRulesForPath(path) {
  return defu({}, ..._routeRulesMatcher.matchAll(path).reverse());
}

function _captureError(error, type) {
  console.error(`[${type}]`, error);
  useNitroApp().captureError(error, { tags: [type] });
}
function trapUnhandledNodeErrors() {
  process.on(
    "unhandledRejection",
    (error) => _captureError(error, "unhandledRejection")
  );
  process.on(
    "uncaughtException",
    (error) => _captureError(error, "uncaughtException")
  );
}
function joinHeaders(value) {
  return Array.isArray(value) ? value.join(", ") : String(value);
}
function normalizeFetchResponse(response) {
  if (!response.headers.has("set-cookie")) {
    return response;
  }
  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers: normalizeCookieHeaders(response.headers)
  });
}
function normalizeCookieHeader(header = "") {
  return splitCookiesString(joinHeaders(header));
}
function normalizeCookieHeaders(headers) {
  const outgoingHeaders = new Headers();
  for (const [name, header] of headers) {
    if (name === "set-cookie") {
      for (const cookie of normalizeCookieHeader(header)) {
        outgoingHeaders.append("set-cookie", cookie);
      }
    } else {
      outgoingHeaders.set(name, joinHeaders(header));
    }
  }
  return outgoingHeaders;
}

/**
* Nitro internal functions extracted from https://github.com/nitrojs/nitro/blob/v2/src/runtime/internal/utils.ts
*/
function isJsonRequest(event) {
	// If the client specifically requests HTML, then avoid classifying as JSON.
	if (hasReqHeader(event, "accept", "text/html")) {
		return false;
	}
	return hasReqHeader(event, "accept", "application/json") || hasReqHeader(event, "user-agent", "curl/") || hasReqHeader(event, "user-agent", "httpie/") || hasReqHeader(event, "sec-fetch-mode", "cors") || event.path.startsWith("/api/") || event.path.endsWith(".json");
}
function hasReqHeader(event, name, includes) {
	const value = getRequestHeader(event, name);
	return value && typeof value === "string" && value.toLowerCase().includes(includes);
}

const errorHandler$0 = (async function errorhandler(error, event, { defaultHandler }) {
	if (event.handled || isJsonRequest(event)) {
		// let Nitro handle JSON errors
		return;
	}
	// invoke default Nitro error handler (which will log appropriately if required)
	const defaultRes = await defaultHandler(error, event, { json: true });
	// let Nitro handle redirect if appropriate
	const status = error.status || error.statusCode || 500;
	if (status === 404 && defaultRes.status === 302) {
		setResponseHeaders(event, defaultRes.headers);
		setResponseStatus(event, defaultRes.status, defaultRes.statusText);
		return send(event, JSON.stringify(defaultRes.body, null, 2));
	}
	const errorObject = defaultRes.body;
	// remove proto/hostname/port from URL
	const url = new URL(errorObject.url);
	errorObject.url = withoutBase(url.pathname, useRuntimeConfig(event).app.baseURL) + url.search + url.hash;
	// add default server message
	errorObject.message ||= "Server Error";
	// we will be rendering this error internally so we can pass along the error.data safely
	errorObject.data ||= error.data;
	errorObject.statusText ||= error.statusText || error.statusMessage;
	delete defaultRes.headers["content-type"];
	delete defaultRes.headers["content-security-policy"];
	setResponseHeaders(event, defaultRes.headers);
	// Access request headers
	const reqHeaders = getRequestHeaders(event);
	// Detect to avoid recursion in SSR rendering of errors
	const isRenderingError = event.path.startsWith("/__nuxt_error") || !!reqHeaders["x-nuxt-error"];
	// HTML response (via SSR)
	const res = isRenderingError ? null : await useNitroApp().localFetch(withQuery(joinURL(useRuntimeConfig(event).app.baseURL, "/__nuxt_error"), errorObject), {
		headers: {
			...reqHeaders,
			"x-nuxt-error": "true"
		},
		redirect: "manual"
	}).catch(() => null);
	if (event.handled) {
		return;
	}
	// Fallback to static rendered error page
	if (!res) {
		const { template } = await import('./error-500.mjs');
		setResponseHeader(event, "Content-Type", "text/html;charset=UTF-8");
		return send(event, template(errorObject));
	}
	const html = await res.text();
	for (const [header, value] of res.headers.entries()) {
		if (header === "set-cookie") {
			appendResponseHeader(event, header, value);
			continue;
		}
		setResponseHeader(event, header, value);
	}
	setResponseStatus(event, res.status && res.status !== 200 ? res.status : defaultRes.status, res.statusText || defaultRes.statusText);
	return send(event, html);
});

function defineNitroErrorHandler(handler) {
  return handler;
}

const errorHandler$1 = defineNitroErrorHandler(
  function defaultNitroErrorHandler(error, event) {
    const res = defaultHandler(error, event);
    setResponseHeaders(event, res.headers);
    setResponseStatus(event, res.status, res.statusText);
    return send(event, JSON.stringify(res.body, null, 2));
  }
);
function defaultHandler(error, event, opts) {
  const isSensitive = error.unhandled || error.fatal;
  const statusCode = error.statusCode || 500;
  const statusMessage = error.statusMessage || "Server Error";
  const url = getRequestURL(event, { xForwardedHost: true, xForwardedProto: true });
  if (statusCode === 404) {
    const baseURL = "/";
    if (/^\/[^/]/.test(baseURL) && !url.pathname.startsWith(baseURL)) {
      const redirectTo = `${baseURL}${url.pathname.slice(1)}${url.search}`;
      return {
        status: 302,
        statusText: "Found",
        headers: { location: redirectTo },
        body: `Redirecting...`
      };
    }
  }
  if (isSensitive && !opts?.silent) {
    const tags = [error.unhandled && "[unhandled]", error.fatal && "[fatal]"].filter(Boolean).join(" ");
    console.error(`[request error] ${tags} [${event.method}] ${url}
`, error);
  }
  const headers = {
    "content-type": "application/json",
    // Prevent browser from guessing the MIME types of resources.
    "x-content-type-options": "nosniff",
    // Prevent error page from being embedded in an iframe
    "x-frame-options": "DENY",
    // Prevent browsers from sending the Referer header
    "referrer-policy": "no-referrer",
    // Disable the execution of any js
    "content-security-policy": "script-src 'none'; frame-ancestors 'none';"
  };
  setResponseStatus(event, statusCode, statusMessage);
  if (statusCode === 404 || !getResponseHeader(event, "cache-control")) {
    headers["cache-control"] = "no-cache";
  }
  const body = {
    error: true,
    url: url.href,
    statusCode,
    statusMessage,
    message: isSensitive ? "Server Error" : error.message,
    data: isSensitive ? void 0 : error.data
  };
  return {
    status: statusCode,
    statusText: statusMessage,
    headers,
    body
  };
}

const errorHandlers = [errorHandler$0, errorHandler$1];

async function errorHandler(error, event) {
  for (const handler of errorHandlers) {
    try {
      await handler(error, event, { defaultHandler });
      if (event.handled) {
        return; // Response handled
      }
    } catch(error) {
      // Handler itself thrown, log and continue
      console.error(error);
    }
  }
  // H3 will handle fallback
}

/*!
  * shared v11.2.8
  * (c) 2025 kazuya kawaguchi
  * Released under the MIT License.
  */
const _create = Object.create;
const create = (obj = null) => _create(obj);
/* eslint-enable */
/**
 * Useful Utilities By Evan you
 * Modified by kazuya kawaguchi
 * MIT License
 * https://github.com/vuejs/vue-next/blob/master/packages/shared/src/index.ts
 * https://github.com/vuejs/vue-next/blob/master/packages/shared/src/codeframe.ts
 */
const isArray = Array.isArray;
const isFunction = (val) => typeof val === 'function';
const isString = (val) => typeof val === 'string';
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const isObject = (val) => val !== null && typeof val === 'object';
const objectToString = Object.prototype.toString;
const toTypeString = (value) => objectToString.call(value);

const isNotObjectOrIsArray = (val) => !isObject(val) || isArray(val);
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function deepCopy(src, des) {
    // src and des should both be objects, and none of them can be a array
    if (isNotObjectOrIsArray(src) || isNotObjectOrIsArray(des)) {
        throw new Error('Invalid value');
    }
    const stack = [{ src, des }];
    while (stack.length) {
        const { src, des } = stack.pop();
        // using `Object.keys` which skips prototype properties
        Object.keys(src).forEach(key => {
            if (key === '__proto__') {
                return;
            }
            // if src[key] is an object/array, set des[key]
            // to empty object/array to prevent setting by reference
            if (isObject(src[key]) && !isObject(des[key])) {
                des[key] = Array.isArray(src[key]) ? [] : create();
            }
            if (isNotObjectOrIsArray(des[key]) || isNotObjectOrIsArray(src[key])) {
                // replace with src[key] when:
                // src[key] or des[key] is not an object, or
                // src[key] or des[key] is an array
                des[key] = src[key];
            }
            else {
                // src[key] and des[key] are both objects, merge them
                stack.push({ src: src[key], des: des[key] });
            }
        });
    }
}

const __nuxtMock = { runWithContext: async (fn) => await fn() };
const merger = createDefu((obj, key, value) => {
  if (key === "messages" || key === "datetimeFormats" || key === "numberFormats") {
    obj[key] ??= create(null);
    deepCopy(value, obj[key]);
    return true;
  }
});
async function loadVueI18nOptions(vueI18nConfigs) {
  const nuxtApp = __nuxtMock;
  let vueI18nOptions = { messages: create(null) };
  for (const configFile of vueI18nConfigs) {
    const resolver = await configFile().then((x) => x.default);
    const resolved = isFunction(resolver) ? await nuxtApp.runWithContext(() => resolver()) : resolver;
    vueI18nOptions = merger(create(null), resolved, vueI18nOptions);
  }
  vueI18nOptions.fallbackLocale ??= false;
  return vueI18nOptions;
}
const isModule = (val) => toTypeString(val) === "[object Module]";
const isResolvedModule = (val) => isModule(val) || true;
async function getLocaleMessages(locale, loader) {
  const nuxtApp = __nuxtMock;
  try {
    const getter = await nuxtApp.runWithContext(loader.load).then((x) => isResolvedModule(x) ? x.default : x);
    return isFunction(getter) ? await nuxtApp.runWithContext(() => getter(locale)) : getter;
  } catch (e) {
    throw new Error(`Failed loading locale (${locale}): ` + e.message);
  }
}
async function getLocaleMessagesMerged(locale, loaders = []) {
  const nuxtApp = __nuxtMock;
  const messages = await Promise.all(
    loaders.map((loader) => nuxtApp.runWithContext(() => getLocaleMessages(locale, loader)))
  );
  const merged = {};
  for (const message of messages) {
    deepCopy(message, merged);
  }
  return merged;
}

// @ts-nocheck
const localeCodes =  [
  "th",
  "en"
];
const localeLoaders = {
  th: [
    {
      key: "locale_th_46json_8dbaabae",
      load: () => import('./th.mjs' /* webpackChunkName: "locale_th_46json_8dbaabae" */),
      cache: true
    }
  ],
  en: [
    {
      key: "locale_en_46json_d959d062",
      load: () => import('./en.mjs' /* webpackChunkName: "locale_en_46json_d959d062" */),
      cache: true
    }
  ]
};
const vueI18nConfigs = [];
const normalizedLocales = [
  {
    code: "th",
    name: "ภาษาไทย",
    language: undefined
  },
  {
    code: "en",
    name: "English",
    language: undefined
  }
];

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

function defineNitroPlugin(def) {
  return def;
}

function defineRenderHandler(render) {
  const runtimeConfig = useRuntimeConfig();
  return eventHandler(async (event) => {
    const nitroApp = useNitroApp();
    const ctx = { event, render, response: void 0 };
    await nitroApp.hooks.callHook("render:before", ctx);
    if (!ctx.response) {
      if (event.path === `${runtimeConfig.app.baseURL}favicon.ico`) {
        setResponseHeader(event, "Content-Type", "image/x-icon");
        return send(
          event,
          "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
        );
      }
      ctx.response = await ctx.render(event);
      if (!ctx.response) {
        const _currentStatus = getResponseStatus(event);
        setResponseStatus(event, _currentStatus === 200 ? 500 : _currentStatus);
        return send(
          event,
          "No response returned from render handler: " + event.path
        );
      }
    }
    await nitroApp.hooks.callHook("render:response", ctx.response, ctx);
    if (ctx.response.headers) {
      setResponseHeaders(event, ctx.response.headers);
    }
    if (ctx.response.statusCode || ctx.response.statusMessage) {
      setResponseStatus(
        event,
        ctx.response.statusCode,
        ctx.response.statusMessage
      );
    }
    return ctx.response.body;
  });
}

function buildAssetsDir() {
	// TODO: support passing event to `useRuntimeConfig`
	return useRuntimeConfig().app.buildAssetsDir;
}
function buildAssetsURL(...path) {
	return joinRelativeURL(publicAssetsURL(), buildAssetsDir(), ...path);
}
function publicAssetsURL(...path) {
	// TODO: support passing event to `useRuntimeConfig`
	const app = useRuntimeConfig().app;
	const publicBase = app.cdnURL || app.baseURL;
	return path.length ? joinRelativeURL(publicBase, ...path) : publicBase;
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

function useRuntimeI18n(nuxtApp, event) {
  {
    return useRuntimeConfig(event).public.i18n;
  }
}
function useI18nDetection(nuxtApp) {
  const detectBrowserLanguage = useRuntimeI18n().detectBrowserLanguage;
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

function createLocaleConfigs(fallbackLocale) {
  const localeConfigs = {};
  for (const locale of localeCodes) {
    const fallbacks = getFallbackLocaleCodes(fallbackLocale, [locale]);
    const cacheable = isLocaleWithFallbacksCacheable(locale, fallbacks);
    localeConfigs[locale] = { fallbacks, cacheable };
  }
  return localeConfigs;
}
function getFallbackLocaleCodes(fallback, locales) {
  if (fallback === false) {
    return [];
  }
  if (isArray(fallback)) {
    return fallback;
  }
  let fallbackLocales = [];
  if (isString(fallback)) {
    if (locales.every((locale) => locale !== fallback)) {
      fallbackLocales.push(fallback);
    }
    return fallbackLocales;
  }
  const targets = [...locales, "default"];
  for (const locale of targets) {
    if (locale in fallback == false) {
      continue;
    }
    fallbackLocales = [...fallbackLocales, ...fallback[locale].filter(Boolean)];
  }
  return fallbackLocales;
}
function isLocaleCacheable(locale) {
  return localeLoaders[locale] != null && localeLoaders[locale].every((loader) => loader.cache !== false);
}
function isLocaleWithFallbacksCacheable(locale, fallbackLocales) {
  return isLocaleCacheable(locale) && fallbackLocales.every((fallbackLocale) => isLocaleCacheable(fallbackLocale));
}
function getDefaultLocaleForDomain(host) {
  return normalizedLocales.find((l) => !!l.defaultForDomains?.includes(host))?.code;
}
const isSupportedLocale = (locale) => localeCodes.includes(locale || "");

function useI18nContext(event) {
  if (event.context.nuxtI18n == null) {
    throw new Error("Nuxt I18n server context has not been set up yet.");
  }
  return event.context.nuxtI18n;
}
function tryUseI18nContext(event) {
  return event.context.nuxtI18n;
}
const getHost = (event) => getRequestURL(event, { xForwardedHost: true }).host;
async function initializeI18nContext(event) {
  const runtimeI18n = useRuntimeI18n(void 0, event);
  const defaultLocale = runtimeI18n.defaultLocale || "";
  const options = await setupVueI18nOptions(getDefaultLocaleForDomain(getHost(event)) || defaultLocale);
  const localeConfigs = createLocaleConfigs(options.fallbackLocale);
  const ctx = createI18nContext();
  ctx.vueI18nOptions = options;
  ctx.localeConfigs = localeConfigs;
  event.context.nuxtI18n = ctx;
  return ctx;
}
function createI18nContext() {
  return {
    messages: {},
    slp: {},
    localeConfigs: {},
    trackMap: {},
    vueI18nOptions: void 0,
    trackKey(key, locale) {
      this.trackMap[locale] ??= /* @__PURE__ */ new Set();
      this.trackMap[locale].add(key);
    }
  };
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

const appHead = {"meta":[{"charset":"utf-8"},{"name":"viewport","content":"width=device-width, initial-scale=1"},{"name":"description","content":"Nuxnan Social Learning E-commerce"},{"name":"format-detection","content":"telephone=no"}],"link":[{"rel":"icon","type":"image/png","href":"/favicon.png"}],"style":[],"script":[],"noscript":[],"title":"Nuxnan - Online Learning Community App","bodyAttrs":{"class":"bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans"}};

const appRootTag = "div";

const appRootAttrs = {"id":"__nuxt"};

const appTeleportTag = "div";

const appTeleportAttrs = {"id":"teleports"};

const appId = "nuxt-app";

const separator = "___";
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

const getCookieLocale = (event, cookieName) => (getCookie(event, cookieName)) || void 0;
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
  const runtimeI18n = useRuntimeI18n();
  return {
    cookie: () => getCookieLocale(event, config.cookieKey),
    header: () => getHeaderLocale(event) ,
    navigator: () => void 0,
    host: (path) => getHostLocale(event, path, runtimeI18n.domainLocales),
    route: (path) => getRouteLocale(event, path)
  };
};

// Generated by @nuxtjs/i18n
const pathToI18nConfig = {};
const i18nPathToPath = {};

const matcher = createRouterMatcher([], {});
for (const path of Object.keys(i18nPathToPath)) {
  matcher.addRoute({ path, component: () => "", meta: {} });
}
const getI18nPathToI18nPath = (path, locale) => {
  if (!path || !locale) {
    return;
  }
  const plainPath = i18nPathToPath[path];
  const i18nConfig = pathToI18nConfig[plainPath];
  if (i18nConfig && i18nConfig[locale]) {
    return i18nConfig[locale] === true ? plainPath : i18nConfig[locale];
  }
};
function isExistingNuxtRoute(path) {
  if (path === "") {
    return;
  }
  if (path.endsWith("/__nuxt_error")) {
    return;
  }
  const resolvedMatch = matcher.resolve({ path }, { path: "/", name: "", matched: [], params: {}, meta: {} });
  return resolvedMatch.matched.length > 0 ? resolvedMatch : void 0;
}
function matchLocalized(path, locale, defaultLocale) {
  if (path === "") {
    return;
  }
  const parsed = parsePath(path);
  const resolvedMatch = matcher.resolve(
    { path: parsed.pathname || "/" },
    { path: "/", name: "", matched: [], params: {}, meta: {} }
  );
  if (resolvedMatch.matched.length > 0) {
    const alternate = getI18nPathToI18nPath(resolvedMatch.matched[0].path, locale);
    const match = matcher.resolve(
      { params: resolvedMatch.params },
      { path: alternate || "/", name: "", matched: [], params: {}, meta: {} }
    );
    return withLeadingSlash(joinURL("", match.path));
  }
}

function* detect(detectors, detection, path) {
  if (detection.enabled) {
    yield { locale: detectors.cookie(), source: "cookie" };
    yield { locale: detectors.header(), source: "header" };
  }
  yield { locale: detection.fallbackLocale, source: "fallback" };
}
const _N_NVCscj13lDqMff1o27k4m48JNC1tnAosJ8ZHm3A = defineNitroPlugin(async (nitro) => {
  const runtimeI18n = useRuntimeI18n();
  const rootRedirect = resolveRootRedirect(runtimeI18n.rootRedirect);
  runtimeI18n.defaultLocale || "";
  try {
    const cacheStorage = useStorage("cache");
    const cachedKeys = await cacheStorage.getKeys("nitro:handlers:i18n");
    await Promise.all(cachedKeys.map((key) => cacheStorage.removeItem(key)));
  } catch {
  }
  const detection = useI18nDetection();
  const cookieOptions = {
    path: "/",
    domain: detection.cookieDomain || void 0,
    maxAge: 60 * 60 * 24 * 365,
    sameSite: "lax",
    secure: detection.cookieSecure
  };
  const createBaseUrlGetter = () => {
    isFunction(runtimeI18n.baseUrl) ? "" : runtimeI18n.baseUrl || "";
    if (isFunction(runtimeI18n.baseUrl)) {
      return () => "";
    }
    return (event, defaultLocale) => {
      return "";
    };
  };
  function resolveRedirectPath(event, path, pathLocale, defaultLocale, detector) {
    let locale = "";
    for (const detected of detect(detector, detection, event.path)) {
      if (detected.locale && isSupportedLocale(detected.locale)) {
        locale = detected.locale;
        break;
      }
    }
    locale ||= defaultLocale;
    function getLocalizedMatch(locale2) {
      const res = matchLocalized(path || "/", locale2);
      if (res && res !== event.path) {
        return res;
      }
    }
    let resolvedPath = void 0;
    let redirectCode = 302;
    const requestURL = getRequestURL(event);
    if (rootRedirect && requestURL.pathname === "/") {
      locale = detection.enabled && locale || defaultLocale;
      resolvedPath = isSupportedLocale(detector.route(rootRedirect.path)) && rootRedirect.path || matchLocalized(rootRedirect.path, locale);
      redirectCode = rootRedirect.code;
    } else if (runtimeI18n.redirectStatusCode) {
      redirectCode = runtimeI18n.redirectStatusCode;
    }
    switch (detection.redirectOn) {
      case "root":
        if (requestURL.pathname !== "/") {
          break;
        }
      // fallthrough (root has no prefix)
      case "no prefix":
        if (pathLocale) {
          break;
        }
      // fallthrough to resolve
      case "all":
        resolvedPath ??= getLocalizedMatch(locale);
        break;
    }
    if (requestURL.pathname === "/" && "no_prefix" === "prefix") ;
    return { path: resolvedPath, code: redirectCode, locale };
  }
  const baseUrlGetter = createBaseUrlGetter();
  nitro.hooks.hook("request", async (event) => {
    await initializeI18nContext(event);
  });
  nitro.hooks.hook("render:before", async ({ event }) => {
    const ctx = useI18nContext(event);
    const url = getRequestURL(event);
    const detector = useDetectors(event, detection);
    const localeSegment = detector.route(event.path);
    const pathLocale = isSupportedLocale(localeSegment) && localeSegment || void 0;
    const path = (pathLocale && url.pathname.slice(pathLocale.length + 1)) ?? url.pathname;
    if (!url.pathname.includes("/_i18n/G6E2kPIA") && !isExistingNuxtRoute(path)) {
      return;
    }
    const resolved = resolveRedirectPath(event, path, pathLocale, ctx.vueI18nOptions.defaultLocale, detector);
    if (resolved.path && resolved.path !== url.pathname) {
      ctx.detectLocale = resolved.locale;
      detection.useCookie && setCookie(event, detection.cookieKey, resolved.locale, cookieOptions);
      await sendRedirect(
        event,
        joinURL(baseUrlGetter(event, ctx.vueI18nOptions.defaultLocale), resolved.path + url.search),
        resolved.code
      );
      return;
    }
  });
  nitro.hooks.hook("render:html", (htmlContext, { event }) => {
    tryUseI18nContext(event);
  });
});

const script = "\"use strict\";(()=>{const t=window,e=document.documentElement,c=[\"dark\",\"light\"],n=getStorageValue(\"localStorage\",\"nuxt-color-mode\")||\"system\";let i=n===\"system\"?u():n;const r=e.getAttribute(\"data-color-mode-forced\");r&&(i=r),l(i),t[\"__NUXT_COLOR_MODE__\"]={preference:n,value:i,getColorScheme:u,addColorScheme:l,removeColorScheme:d};function l(o){const s=\"\"+o+\"\",a=\"\";e.classList?e.classList.add(s):e.className+=\" \"+s,a&&e.setAttribute(\"data-\"+a,o)}function d(o){const s=\"\"+o+\"\",a=\"\";e.classList?e.classList.remove(s):e.className=e.className.replace(new RegExp(s,\"g\"),\"\"),a&&e.removeAttribute(\"data-\"+a)}function f(o){return t.matchMedia(\"(prefers-color-scheme\"+o+\")\")}function u(){if(t.matchMedia&&f(\"\").media!==\"not all\"){for(const o of c)if(f(\":\"+o).matches)return o}return\"light\"}})();function getStorageValue(t,e){switch(t){case\"localStorage\":return window.localStorage.getItem(e);case\"sessionStorage\":return window.sessionStorage.getItem(e);case\"cookie\":return getCookie(e);default:return null}}function getCookie(t){const c=(\"; \"+window.document.cookie).split(\"; \"+t+\"=\");if(c.length===2)return c.pop()?.split(\";\").shift()}";

const _ncszT14oLwy6iuFtZ3mKTTise7Qj8u1IY1MQLreUlc = (function(nitro) {
  nitro.hooks.hook("render:html", (htmlContext) => {
    htmlContext.head.push(`<script>${script}<\/script>`);
  });
});

const plugins = [
  _N_NVCscj13lDqMff1o27k4m48JNC1tnAosJ8ZHm3A,
_ncszT14oLwy6iuFtZ3mKTTise7Qj8u1IY1MQLreUlc
];

const assets = {
  "/favicon.png": {
    "type": "image/png",
    "etag": "\"6c1-LvI7u6jwTgDVkYF7bxj+lK1dEVM\"",
    "mtime": "2026-01-27T02:33:46.654Z",
    "size": 1729,
    "path": "../public/favicon.png"
  },
  "/favicon.svg": {
    "type": "image/svg+xml",
    "etag": "\"e5-gK2Rj3GIXf6Sb5iILW3Y9cuMw5Q\"",
    "mtime": "2026-01-27T02:33:46.654Z",
    "size": 229,
    "path": "../public/favicon.svg"
  },
  "/README.md": {
    "type": "text/markdown; charset=utf-8",
    "etag": "\"5fc-vcX8zCUzxpazucXkFtrCunClje8\"",
    "mtime": "2026-01-27T08:41:04.054Z",
    "size": 1532,
    "path": "../public/README.md"
  },
  "/css/nuxt-google-fonts.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"59a6-a+yTWIDd0rFYV2Q2R+qvSWlc1ag\"",
    "mtime": "2026-01-28T07:33:47.466Z",
    "size": 22950,
    "path": "../public/css/nuxt-google-fonts.css"
  },
  "/fonts/Audiowide-normal-400-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"1c08-rA687VDUZIPPcvyLDoooVt0jaP8\"",
    "mtime": "2026-01-28T07:33:46.134Z",
    "size": 7176,
    "path": "../public/fonts/Audiowide-normal-400-latin-ext.woff2"
  },
  "/fonts/Audiowide-normal-400-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"3734-Rv5igDbEVm1P3Z1EAZURZxiuAg4\"",
    "mtime": "2026-01-28T07:33:46.267Z",
    "size": 14132,
    "path": "../public/fonts/Audiowide-normal-400-latin.woff2"
  },
  "/fonts/Inter-normal-300-cyrillic-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"6568-cF1iUGbboMFZ8TfnP5HiMgl9II0\"",
    "mtime": "2026-01-28T07:33:46.315Z",
    "size": 25960,
    "path": "../public/fonts/Inter-normal-300-cyrillic-ext.woff2"
  },
  "/fonts/Inter-normal-300-cyrillic.woff2": {
    "type": "font/woff2",
    "etag": "\"493c-n3Oy9D6jvzfMjpClqox+Zo7ERQQ\"",
    "mtime": "2026-01-28T07:33:46.344Z",
    "size": 18748,
    "path": "../public/fonts/Inter-normal-300-cyrillic.woff2"
  },
  "/fonts/Inter-normal-300-greek-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"2be0-BP5iTzJeB8nLqYAgKpWNi5o1Zm8\"",
    "mtime": "2026-01-28T07:33:46.374Z",
    "size": 11232,
    "path": "../public/fonts/Inter-normal-300-greek-ext.woff2"
  },
  "/fonts/Inter-normal-300-greek.woff2": {
    "type": "font/woff2",
    "etag": "\"4a34-xor/hj4YNqI52zFecXnUbzQ4Xs4\"",
    "mtime": "2026-01-28T07:33:46.404Z",
    "size": 18996,
    "path": "../public/fonts/Inter-normal-300-greek.woff2"
  },
  "/fonts/Inter-normal-300-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"bc80-8R1ym7Ck2DUNLqPQ/AYs9u8tUpg\"",
    "mtime": "2026-01-28T07:33:46.528Z",
    "size": 48256,
    "path": "../public/fonts/Inter-normal-300-latin.woff2"
  },
  "/fonts/Inter-normal-300-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"14c4c-zz61D7IQFMB9QxHvTAOk/Vh4ibQ\"",
    "mtime": "2026-01-28T07:33:46.486Z",
    "size": 85068,
    "path": "../public/fonts/Inter-normal-300-latin-ext.woff2"
  },
  "/fonts/Inter-normal-300-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"280c-nBythjoDQ0+5wVAendJ6wU7Xz2M\"",
    "mtime": "2026-01-28T07:33:46.428Z",
    "size": 10252,
    "path": "../public/fonts/Inter-normal-300-vietnamese.woff2"
  },
  "/fonts/Inter-normal-400-cyrillic.woff2": {
    "type": "font/woff2",
    "etag": "\"493c-n3Oy9D6jvzfMjpClqox+Zo7ERQQ\"",
    "mtime": "2026-01-28T07:33:46.344Z",
    "size": 18748,
    "path": "../public/fonts/Inter-normal-400-cyrillic.woff2"
  },
  "/fonts/Inter-normal-400-cyrillic-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"6568-cF1iUGbboMFZ8TfnP5HiMgl9II0\"",
    "mtime": "2026-01-28T07:33:46.315Z",
    "size": 25960,
    "path": "../public/fonts/Inter-normal-400-cyrillic-ext.woff2"
  },
  "/fonts/Inter-normal-400-greek-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"2be0-BP5iTzJeB8nLqYAgKpWNi5o1Zm8\"",
    "mtime": "2026-01-28T07:33:46.374Z",
    "size": 11232,
    "path": "../public/fonts/Inter-normal-400-greek-ext.woff2"
  },
  "/fonts/Inter-normal-400-greek.woff2": {
    "type": "font/woff2",
    "etag": "\"4a34-xor/hj4YNqI52zFecXnUbzQ4Xs4\"",
    "mtime": "2026-01-28T07:33:46.404Z",
    "size": 18996,
    "path": "../public/fonts/Inter-normal-400-greek.woff2"
  },
  "/fonts/Inter-normal-400-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"14c4c-zz61D7IQFMB9QxHvTAOk/Vh4ibQ\"",
    "mtime": "2026-01-28T07:33:46.486Z",
    "size": 85068,
    "path": "../public/fonts/Inter-normal-400-latin-ext.woff2"
  },
  "/fonts/Inter-normal-400-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"bc80-8R1ym7Ck2DUNLqPQ/AYs9u8tUpg\"",
    "mtime": "2026-01-28T07:33:46.528Z",
    "size": 48256,
    "path": "../public/fonts/Inter-normal-400-latin.woff2"
  },
  "/fonts/Inter-normal-400-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"280c-nBythjoDQ0+5wVAendJ6wU7Xz2M\"",
    "mtime": "2026-01-28T07:33:46.428Z",
    "size": 10252,
    "path": "../public/fonts/Inter-normal-400-vietnamese.woff2"
  },
  "/fonts/Inter-normal-500-cyrillic.woff2": {
    "type": "font/woff2",
    "etag": "\"493c-n3Oy9D6jvzfMjpClqox+Zo7ERQQ\"",
    "mtime": "2026-01-28T07:33:46.344Z",
    "size": 18748,
    "path": "../public/fonts/Inter-normal-500-cyrillic.woff2"
  },
  "/fonts/Inter-normal-500-cyrillic-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"6568-cF1iUGbboMFZ8TfnP5HiMgl9II0\"",
    "mtime": "2026-01-28T07:33:46.315Z",
    "size": 25960,
    "path": "../public/fonts/Inter-normal-500-cyrillic-ext.woff2"
  },
  "/fonts/Inter-normal-500-greek-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"2be0-BP5iTzJeB8nLqYAgKpWNi5o1Zm8\"",
    "mtime": "2026-01-28T07:33:46.374Z",
    "size": 11232,
    "path": "../public/fonts/Inter-normal-500-greek-ext.woff2"
  },
  "/fonts/Inter-normal-500-greek.woff2": {
    "type": "font/woff2",
    "etag": "\"4a34-xor/hj4YNqI52zFecXnUbzQ4Xs4\"",
    "mtime": "2026-01-28T07:33:46.404Z",
    "size": 18996,
    "path": "../public/fonts/Inter-normal-500-greek.woff2"
  },
  "/fonts/Inter-normal-500-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"14c4c-zz61D7IQFMB9QxHvTAOk/Vh4ibQ\"",
    "mtime": "2026-01-28T07:33:46.486Z",
    "size": 85068,
    "path": "../public/fonts/Inter-normal-500-latin-ext.woff2"
  },
  "/fonts/Inter-normal-500-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"bc80-8R1ym7Ck2DUNLqPQ/AYs9u8tUpg\"",
    "mtime": "2026-01-28T07:33:46.528Z",
    "size": 48256,
    "path": "../public/fonts/Inter-normal-500-latin.woff2"
  },
  "/fonts/Inter-normal-500-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"280c-nBythjoDQ0+5wVAendJ6wU7Xz2M\"",
    "mtime": "2026-01-28T07:33:46.428Z",
    "size": 10252,
    "path": "../public/fonts/Inter-normal-500-vietnamese.woff2"
  },
  "/fonts/Inter-normal-600-cyrillic.woff2": {
    "type": "font/woff2",
    "etag": "\"493c-n3Oy9D6jvzfMjpClqox+Zo7ERQQ\"",
    "mtime": "2026-01-28T07:33:46.344Z",
    "size": 18748,
    "path": "../public/fonts/Inter-normal-600-cyrillic.woff2"
  },
  "/fonts/Inter-normal-600-greek-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"2be0-BP5iTzJeB8nLqYAgKpWNi5o1Zm8\"",
    "mtime": "2026-01-28T07:33:46.374Z",
    "size": 11232,
    "path": "../public/fonts/Inter-normal-600-greek-ext.woff2"
  },
  "/fonts/Inter-normal-600-greek.woff2": {
    "type": "font/woff2",
    "etag": "\"4a34-xor/hj4YNqI52zFecXnUbzQ4Xs4\"",
    "mtime": "2026-01-28T07:33:46.404Z",
    "size": 18996,
    "path": "../public/fonts/Inter-normal-600-greek.woff2"
  },
  "/fonts/Inter-normal-600-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"14c4c-zz61D7IQFMB9QxHvTAOk/Vh4ibQ\"",
    "mtime": "2026-01-28T07:33:46.486Z",
    "size": 85068,
    "path": "../public/fonts/Inter-normal-600-latin-ext.woff2"
  },
  "/fonts/Inter-normal-600-cyrillic-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"6568-cF1iUGbboMFZ8TfnP5HiMgl9II0\"",
    "mtime": "2026-01-28T07:33:46.315Z",
    "size": 25960,
    "path": "../public/fonts/Inter-normal-600-cyrillic-ext.woff2"
  },
  "/fonts/Inter-normal-600-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"280c-nBythjoDQ0+5wVAendJ6wU7Xz2M\"",
    "mtime": "2026-01-28T07:33:46.428Z",
    "size": 10252,
    "path": "../public/fonts/Inter-normal-600-vietnamese.woff2"
  },
  "/fonts/Inter-normal-600-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"bc80-8R1ym7Ck2DUNLqPQ/AYs9u8tUpg\"",
    "mtime": "2026-01-28T07:33:46.528Z",
    "size": 48256,
    "path": "../public/fonts/Inter-normal-600-latin.woff2"
  },
  "/fonts/Inter-normal-700-cyrillic-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"6568-cF1iUGbboMFZ8TfnP5HiMgl9II0\"",
    "mtime": "2026-01-28T07:33:46.315Z",
    "size": 25960,
    "path": "../public/fonts/Inter-normal-700-cyrillic-ext.woff2"
  },
  "/fonts/Inter-normal-700-cyrillic.woff2": {
    "type": "font/woff2",
    "etag": "\"493c-n3Oy9D6jvzfMjpClqox+Zo7ERQQ\"",
    "mtime": "2026-01-28T07:33:46.344Z",
    "size": 18748,
    "path": "../public/fonts/Inter-normal-700-cyrillic.woff2"
  },
  "/fonts/Inter-normal-700-greek-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"2be0-BP5iTzJeB8nLqYAgKpWNi5o1Zm8\"",
    "mtime": "2026-01-28T07:33:46.374Z",
    "size": 11232,
    "path": "../public/fonts/Inter-normal-700-greek-ext.woff2"
  },
  "/fonts/Inter-normal-700-greek.woff2": {
    "type": "font/woff2",
    "etag": "\"4a34-xor/hj4YNqI52zFecXnUbzQ4Xs4\"",
    "mtime": "2026-01-28T07:33:46.404Z",
    "size": 18996,
    "path": "../public/fonts/Inter-normal-700-greek.woff2"
  },
  "/fonts/Inter-normal-700-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"14c4c-zz61D7IQFMB9QxHvTAOk/Vh4ibQ\"",
    "mtime": "2026-01-28T07:33:46.486Z",
    "size": 85068,
    "path": "../public/fonts/Inter-normal-700-latin-ext.woff2"
  },
  "/fonts/Inter-normal-700-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"280c-nBythjoDQ0+5wVAendJ6wU7Xz2M\"",
    "mtime": "2026-01-28T07:33:46.428Z",
    "size": 10252,
    "path": "../public/fonts/Inter-normal-700-vietnamese.woff2"
  },
  "/fonts/Inter-normal-700-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"bc80-8R1ym7Ck2DUNLqPQ/AYs9u8tUpg\"",
    "mtime": "2026-01-28T07:33:46.528Z",
    "size": 48256,
    "path": "../public/fonts/Inter-normal-700-latin.woff2"
  },
  "/fonts/LineIcons.eot": {
    "type": "application/vnd.ms-fontobject",
    "etag": "\"1e720-Brb1T0ZfvKRHGy2/+2hJhv0INBo\"",
    "mtime": "2026-01-27T02:33:46.656Z",
    "size": 124704,
    "path": "../public/fonts/LineIcons.eot"
  },
  "/fonts/LineIcons.ttf": {
    "type": "font/ttf",
    "etag": "\"1e674-ILvG5IDfYhAHeMZsdCtnFZQBfzE\"",
    "mtime": "2026-01-27T02:33:46.663Z",
    "size": 124532,
    "path": "../public/fonts/LineIcons.ttf"
  },
  "/fonts/LineIcons.woff": {
    "type": "font/woff",
    "etag": "\"fcdc-Ir+U2Xeba5iDwQARUNGH2hwz/fU\"",
    "mtime": "2026-01-27T02:33:46.664Z",
    "size": 64732,
    "path": "../public/fonts/LineIcons.woff"
  },
  "/fonts/LineIcons.woff2": {
    "type": "font/woff2",
    "etag": "\"c9dc-CZyFUt3Cz7BRWTM1d8GCHSxtSRk\"",
    "mtime": "2026-01-27T02:33:46.664Z",
    "size": 51676,
    "path": "../public/fonts/LineIcons.woff2"
  },
  "/fonts/Outfit-normal-300-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"39d8-sqVel30br8w1wJ7fkoMuV0KPYjs\"",
    "mtime": "2026-01-28T07:33:46.613Z",
    "size": 14808,
    "path": "../public/fonts/Outfit-normal-300-latin-ext.woff2"
  },
  "/fonts/Outfit-normal-300-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"7e24-2KMW98v6RuaYdskl5Y+/e3wavw0\"",
    "mtime": "2026-01-28T07:33:46.736Z",
    "size": 32292,
    "path": "../public/fonts/Outfit-normal-300-latin.woff2"
  },
  "/fonts/Outfit-normal-400-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"39d8-sqVel30br8w1wJ7fkoMuV0KPYjs\"",
    "mtime": "2026-01-28T07:33:46.613Z",
    "size": 14808,
    "path": "../public/fonts/Outfit-normal-400-latin-ext.woff2"
  },
  "/fonts/Outfit-normal-400-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"7e24-2KMW98v6RuaYdskl5Y+/e3wavw0\"",
    "mtime": "2026-01-28T07:33:46.736Z",
    "size": 32292,
    "path": "../public/fonts/Outfit-normal-400-latin.woff2"
  },
  "/fonts/LineIcons.svg": {
    "type": "image/svg+xml",
    "etag": "\"95511-EGNsuNrb6mE+x072/A24G+bbra4\"",
    "mtime": "2026-01-27T02:33:46.661Z",
    "size": 611601,
    "path": "../public/fonts/LineIcons.svg"
  },
  "/fonts/Outfit-normal-500-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"39d8-sqVel30br8w1wJ7fkoMuV0KPYjs\"",
    "mtime": "2026-01-28T07:33:46.613Z",
    "size": 14808,
    "path": "../public/fonts/Outfit-normal-500-latin-ext.woff2"
  },
  "/fonts/Outfit-normal-500-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"7e24-2KMW98v6RuaYdskl5Y+/e3wavw0\"",
    "mtime": "2026-01-28T07:33:46.736Z",
    "size": 32292,
    "path": "../public/fonts/Outfit-normal-500-latin.woff2"
  },
  "/fonts/Outfit-normal-600-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"39d8-sqVel30br8w1wJ7fkoMuV0KPYjs\"",
    "mtime": "2026-01-28T07:33:46.613Z",
    "size": 14808,
    "path": "../public/fonts/Outfit-normal-600-latin-ext.woff2"
  },
  "/fonts/Outfit-normal-600-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"7e24-2KMW98v6RuaYdskl5Y+/e3wavw0\"",
    "mtime": "2026-01-28T07:33:46.736Z",
    "size": 32292,
    "path": "../public/fonts/Outfit-normal-600-latin.woff2"
  },
  "/fonts/Outfit-normal-700-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"39d8-sqVel30br8w1wJ7fkoMuV0KPYjs\"",
    "mtime": "2026-01-28T07:33:46.613Z",
    "size": 14808,
    "path": "../public/fonts/Outfit-normal-700-latin-ext.woff2"
  },
  "/fonts/Outfit-normal-700-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"7e24-2KMW98v6RuaYdskl5Y+/e3wavw0\"",
    "mtime": "2026-01-28T07:33:46.736Z",
    "size": 32292,
    "path": "../public/fonts/Outfit-normal-700-latin.woff2"
  },
  "/fonts/Prompt-normal-300-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"4418-MAW35WaQOcMXdtvxQTxOeHv5was\"",
    "mtime": "2026-01-28T07:33:46.942Z",
    "size": 17432,
    "path": "../public/fonts/Prompt-normal-300-latin-ext.woff2"
  },
  "/fonts/Prompt-normal-300-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"4478-TQDzdaFU3NuDcHWrDk7VkSnsZaU\"",
    "mtime": "2026-01-28T07:33:46.965Z",
    "size": 17528,
    "path": "../public/fonts/Prompt-normal-300-latin.woff2"
  },
  "/fonts/Prompt-normal-300-thai.woff2": {
    "type": "font/woff2",
    "etag": "\"30b4-wVpTGBtAd5UGRMkpJJ/o94CG1FY\"",
    "mtime": "2026-01-28T07:33:46.797Z",
    "size": 12468,
    "path": "../public/fonts/Prompt-normal-300-thai.woff2"
  },
  "/fonts/Prompt-normal-300-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"2430-pNMG750r3MUeYuSMBG9jOzDox7I\"",
    "mtime": "2026-01-28T07:33:46.883Z",
    "size": 9264,
    "path": "../public/fonts/Prompt-normal-300-vietnamese.woff2"
  },
  "/fonts/Prompt-normal-400-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"46a0-lSmnVyKAey2OaS3fkKORM6mcE1A\"",
    "mtime": "2026-01-28T07:33:47.051Z",
    "size": 18080,
    "path": "../public/fonts/Prompt-normal-400-latin-ext.woff2"
  },
  "/fonts/Prompt-normal-400-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"4614-E9rcfFsUDeh0i8kgNXO5OTFFESY\"",
    "mtime": "2026-01-28T07:33:47.078Z",
    "size": 17940,
    "path": "../public/fonts/Prompt-normal-400-latin.woff2"
  },
  "/fonts/Prompt-normal-400-thai.woff2": {
    "type": "font/woff2",
    "etag": "\"3388-SBrYECIzaCoyC2Bq2RrMAW++a+k\"",
    "mtime": "2026-01-28T07:33:46.996Z",
    "size": 13192,
    "path": "../public/fonts/Prompt-normal-400-thai.woff2"
  },
  "/fonts/Prompt-normal-400-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"2514-03DjppxLQwFpaYKB+p3dzv0CLPo\"",
    "mtime": "2026-01-28T07:33:47.029Z",
    "size": 9492,
    "path": "../public/fonts/Prompt-normal-400-vietnamese.woff2"
  },
  "/fonts/Prompt-normal-500-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"4868-0PoKNiiyBD82nSUkdmQITih74dQ\"",
    "mtime": "2026-01-28T07:33:47.166Z",
    "size": 18536,
    "path": "../public/fonts/Prompt-normal-500-latin-ext.woff2"
  },
  "/fonts/Prompt-normal-500-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"46b0-uWMZeBARYcmpJb0U5tzuQNTlxpk\"",
    "mtime": "2026-01-28T07:33:47.201Z",
    "size": 18096,
    "path": "../public/fonts/Prompt-normal-500-latin.woff2"
  },
  "/fonts/Prompt-normal-500-thai.woff2": {
    "type": "font/woff2",
    "etag": "\"327c-NOQpl5/4wrhwr4Nv6ydnu8QYUyQ\"",
    "mtime": "2026-01-28T07:33:47.103Z",
    "size": 12924,
    "path": "../public/fonts/Prompt-normal-500-thai.woff2"
  },
  "/fonts/Prompt-normal-500-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"27cc-pOpsaCYxuALmOtvDJ5nEH7Z7DxQ\"",
    "mtime": "2026-01-28T07:33:47.128Z",
    "size": 10188,
    "path": "../public/fonts/Prompt-normal-500-vietnamese.woff2"
  },
  "/fonts/Prompt-normal-600-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"46a8-pR+Q+bJkU6KybmrcIi8SzBUeBes\"",
    "mtime": "2026-01-28T07:33:47.308Z",
    "size": 18088,
    "path": "../public/fonts/Prompt-normal-600-latin-ext.woff2"
  },
  "/fonts/Prompt-normal-600-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"46b8-HLsRB3NMlfBTLzPzTwNtDRqHlnw\"",
    "mtime": "2026-01-28T07:33:47.336Z",
    "size": 18104,
    "path": "../public/fonts/Prompt-normal-600-latin.woff2"
  },
  "/fonts/Prompt-normal-600-thai.woff2": {
    "type": "font/woff2",
    "etag": "\"32f4-opD0DXjdm7MF9OB1J+/OtUUQ+5Q\"",
    "mtime": "2026-01-28T07:33:47.257Z",
    "size": 13044,
    "path": "../public/fonts/Prompt-normal-600-thai.woff2"
  },
  "/fonts/Prompt-normal-600-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"263c-yYCn34QmC4vRd0jSbrp3QysRyWo\"",
    "mtime": "2026-01-28T07:33:47.280Z",
    "size": 9788,
    "path": "../public/fonts/Prompt-normal-600-vietnamese.woff2"
  },
  "/fonts/Prompt-normal-700-latin-ext.woff2": {
    "type": "font/woff2",
    "etag": "\"48f8-/Zp/zWi4cK2z6MMu2eIViT9Yex0\"",
    "mtime": "2026-01-28T07:33:47.434Z",
    "size": 18680,
    "path": "../public/fonts/Prompt-normal-700-latin-ext.woff2"
  },
  "/fonts/Prompt-normal-700-latin.woff2": {
    "type": "font/woff2",
    "etag": "\"46f0-BcH+cwKZUkufvV16Lk5d20OX+hI\"",
    "mtime": "2026-01-28T07:33:47.464Z",
    "size": 18160,
    "path": "../public/fonts/Prompt-normal-700-latin.woff2"
  },
  "/fonts/Prompt-normal-700-thai.woff2": {
    "type": "font/woff2",
    "etag": "\"3398-9MZ7y374tSlND6pgQm7trn/6n4g\"",
    "mtime": "2026-01-28T07:33:47.368Z",
    "size": 13208,
    "path": "../public/fonts/Prompt-normal-700-thai.woff2"
  },
  "/fonts/Prompt-normal-700-vietnamese.woff2": {
    "type": "font/woff2",
    "etag": "\"28d8-V6KBm/TFleVl1rXprJAhboNPI9Q\"",
    "mtime": "2026-01-28T07:33:47.401Z",
    "size": 10456,
    "path": "../public/fonts/Prompt-normal-700-vietnamese.woff2"
  },
  "/images/default-avatar.png": {
    "type": "image/png",
    "etag": "\"f400-RI2ce6+mFhK+v9Vcf2LgeUUOmpg\"",
    "mtime": "2026-01-27T02:33:46.678Z",
    "size": 62464,
    "path": "../public/images/default-avatar.png"
  },
  "/images/logo-white.png": {
    "type": "image/png",
    "etag": "\"109c-oP9sFBaRLMa9ARCH5aQU/+pva0c\"",
    "mtime": "2026-01-27T02:33:46.765Z",
    "size": 4252,
    "path": "../public/images/logo-white.png"
  },
  "/images/logo.png": {
    "type": "image/png",
    "etag": "\"74f-0qVuZXwFO3fKHUAjXz4cB20UI9c\"",
    "mtime": "2026-01-27T02:33:46.766Z",
    "size": 1871,
    "path": "../public/images/logo.png"
  },
  "/images/map-marker.png": {
    "type": "image/png",
    "etag": "\"87f-YRrqkSDr/Y9nfLEIqqcxBV6JnnQ\"",
    "mtime": "2026-01-27T02:33:46.766Z",
    "size": 2175,
    "path": "../public/images/map-marker.png"
  },
  "/images/plearnd-logo.png": {
    "type": "image/png",
    "etag": "\"3e8c-6KqMBohIw/9YNwzHczpUWPiCgRQ\"",
    "mtime": "2026-01-27T08:41:04.055Z",
    "size": 16012,
    "path": "../public/images/plearnd-logo.png"
  },
  "/js/jquery-stories.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"e1d-yNYI97p9B62yfcB8n//y9x32ax0\"",
    "mtime": "2026-01-27T02:33:46.843Z",
    "size": 3613,
    "path": "../public/js/jquery-stories.js"
  },
  "/js/html5lightbox.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"15ed9-D2DaIiUDtHwy7MhAOFjJ8h4IazY\"",
    "mtime": "2026-01-27T02:33:46.842Z",
    "size": 89817,
    "path": "../public/js/html5lightbox.js"
  },
  "/js/jquery.mCustomScrollbar.min.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"b1ab-5qwgfNtsRZHy058qZF9tv0JTT4k\"",
    "mtime": "2026-01-27T02:33:46.843Z",
    "size": 45483,
    "path": "../public/js/jquery.mCustomScrollbar.min.js"
  },
  "/js/jquery.mousewheel.min.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"ada-DjRpi6yL2cFmgRifWkDrVa1Mnfg\"",
    "mtime": "2026-01-27T02:33:46.845Z",
    "size": 2778,
    "path": "../public/js/jquery.mousewheel.min.js"
  },
  "/js/jquery.min.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1497d-Eyf3VP+H0mvO1GVoVDIH6d8ZCqo\"",
    "mtime": "2026-01-27T02:33:46.844Z",
    "size": 84349,
    "path": "../public/js/jquery.min.js"
  },
  "/js/map-init.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"768-HZlrQRJ+CONK2BCPfjEfM0J2P+s\"",
    "mtime": "2026-01-27T02:33:46.848Z",
    "size": 1896,
    "path": "../public/js/map-init.js"
  },
  "/js/owl.carousel.min.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"9dd2-Nq8sC8RURiNVWEpTWJfKXsY3Hzo\"",
    "mtime": "2026-01-27T02:33:46.850Z",
    "size": 40402,
    "path": "../public/js/owl.carousel.min.js"
  },
  "/js/script.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"270a-7FNZ9PJxsImi+URlvn/SbzwKHh4\"",
    "mtime": "2026-01-27T02:33:46.851Z",
    "size": 9994,
    "path": "../public/js/script.js"
  },
  "/js/stickit-header.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3ced-eokmjvGQXHrgZ6JTp5N2B1hQYJE\"",
    "mtime": "2026-01-27T02:33:46.859Z",
    "size": 15597,
    "path": "../public/js/stickit-header.js"
  },
  "/js/main.min.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5a26e-TJes1rna1jbOIDghr/AkWnRiyPQ\"",
    "mtime": "2026-01-27T02:33:46.848Z",
    "size": 369262,
    "path": "../public/js/main.min.js"
  },
  "/js/userincr.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"b1b-NJe/UgiR7XBrHWeslNOrze/VmrY\"",
    "mtime": "2026-01-27T02:33:46.859Z",
    "size": 2843,
    "path": "../public/js/userincr.js"
  },
  "/js/TimelineMax.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1a79a-kwOkf5DfbAbIpSaMX2pOwUPvgX0\"",
    "mtime": "2026-01-27T02:33:46.841Z",
    "size": 108442,
    "path": "../public/js/TimelineMax.js"
  },
  "/js/wow.min.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"20e0-4pcgdAT+qZhjrqYKHc03cPjs3e4\"",
    "mtime": "2026-01-27T02:33:46.860Z",
    "size": 8416,
    "path": "../public/js/wow.min.js"
  },
  "/storage/jsm_director_signature.png": {
    "type": "image/png",
    "etag": "\"3f363-MaCd63PFTvztpTT5OCVfL3s2Xm0\"",
    "mtime": "2026-01-27T02:33:46.909Z",
    "size": 258915,
    "path": "../public/storage/jsm_director_signature.png"
  },
  "/_nuxt/-BMJAI59.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4b64-0vCdAWufVuYuK9hmbRoriWJT9T0\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 19300,
    "path": "../public/_nuxt/-BMJAI59.js"
  },
  "/_nuxt/-I88kfMw.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1133-zlHSKvjxSdtpjApDCO1hszc40QY\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 4403,
    "path": "../public/_nuxt/-I88kfMw.js"
  },
  "/images/chat-bg.png": {
    "type": "image/png",
    "etag": "\"aa91d-HrQ3KPIspK36yrdgCeSLy0KOIYI\"",
    "mtime": "2026-01-27T02:33:46.677Z",
    "size": 698653,
    "path": "../public/images/chat-bg.png"
  },
  "/_nuxt/-RkxEpYC.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"18a-h6kMZIGJrTdUEDRseRxZ4XAhwQU\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 394,
    "path": "../public/_nuxt/-RkxEpYC.js"
  },
  "/storage/jsm_logo.png": {
    "type": "image/png",
    "etag": "\"14f4b5-wxrkuBvm50dhI/42eKXApAlikr0\"",
    "mtime": "2026-01-27T02:33:46.921Z",
    "size": 1373365,
    "path": "../public/storage/jsm_logo.png"
  },
  "/_nuxt/1K8ByBlk.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"228-w+SJYYdO1FXvjJt/lh98y/0S5VE\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 552,
    "path": "../public/_nuxt/1K8ByBlk.js"
  },
  "/_nuxt/1w6Pg7Bx.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"793-/uJ8CPyWFjMHLwd9EPesP79N9L0\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 1939,
    "path": "../public/_nuxt/1w6Pg7Bx.js"
  },
  "/_nuxt/27kSx7cw.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1244-4NdfvGLaiTj83E1Fcc+exZqVYrY\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 4676,
    "path": "../public/_nuxt/27kSx7cw.js"
  },
  "/_nuxt/46Vpi4R2.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"7e0-oFIqk3sIeuuB8E8D4cgDc0IFKQg\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 2016,
    "path": "../public/_nuxt/46Vpi4R2.js"
  },
  "/_nuxt/6Mg_RWud.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5763-nholGTx4V8mcMW98Ksz0VoTjA2Y\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 22371,
    "path": "../public/_nuxt/6Mg_RWud.js"
  },
  "/_nuxt/8hgaS2im.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1272-kxDQ6iWgiDkkJmISvpgsjNchSUE\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 4722,
    "path": "../public/_nuxt/8hgaS2im.js"
  },
  "/_nuxt/8hvwFV7E.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"11d-XvzEQGm7j2CdY23ev39Uu8sWnFw\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 285,
    "path": "../public/_nuxt/8hvwFV7E.js"
  },
  "/_nuxt/8uzMIACM.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3c64-t7gLvXCgV9T9AdWghfGutPJblDw\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 15460,
    "path": "../public/_nuxt/8uzMIACM.js"
  },
  "/_nuxt/9jGPXP-T.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"7d2-6A0BMJmMcKNeOWT0dCdnI2h26Qw\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 2002,
    "path": "../public/_nuxt/9jGPXP-T.js"
  },
  "/_nuxt/attendances.DV9jtR_m.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"2e0-aDcOkvBVy4yGWs/yr8KqqLAJ2M0\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 736,
    "path": "../public/_nuxt/attendances.DV9jtR_m.css"
  },
  "/_nuxt/Audiowide-normal-400-latin-ext.DBgo3hnO.woff2": {
    "type": "font/woff2",
    "etag": "\"1c08-rA687VDUZIPPcvyLDoooVt0jaP8\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 7176,
    "path": "../public/_nuxt/Audiowide-normal-400-latin-ext.DBgo3hnO.woff2"
  },
  "/_nuxt/Audiowide-normal-400-latin.6GFCX7ni.woff2": {
    "type": "font/woff2",
    "etag": "\"3734-Rv5igDbEVm1P3Z1EAZURZxiuAg4\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 14132,
    "path": "../public/_nuxt/Audiowide-normal-400-latin.6GFCX7ni.woff2"
  },
  "/_nuxt/auth.B5e7d-SS.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"d75-EZV7zmoe/FliMXv2UPza/ULdGy0\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 3445,
    "path": "../public/_nuxt/auth.B5e7d-SS.css"
  },
  "/_nuxt/AuthenticationCard.B1QoVXkc.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"ad-U2P0MimBZksvpiVneyWqjPbH28w\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 173,
    "path": "../public/_nuxt/AuthenticationCard.B1QoVXkc.css"
  },
  "/_nuxt/B-iK1jFZ.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"62a23-sHeQxQhQjs02Ycmc0KQ4poam1SA\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 404003,
    "path": "../public/_nuxt/B-iK1jFZ.js"
  },
  "/_nuxt/B0W1tBZb.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4e55-lOpATVUUKUlCfgQ9qHeil80DrMM\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 20053,
    "path": "../public/_nuxt/B0W1tBZb.js"
  },
  "/_nuxt/B1WyFRw4.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"aad-SkEi9+s0Y4wbdrPyRY+qhjmem6g\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 2733,
    "path": "../public/_nuxt/B1WyFRw4.js"
  },
  "/_nuxt/B1FP7OUh.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"7b9e-qZ73Lex9BcnNC64rbh4PO7jpfok\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 31646,
    "path": "../public/_nuxt/B1FP7OUh.js"
  },
  "/_nuxt/B2fxA5qo.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"177e-XASXSJ/JTEpzFeE6W5HWycanldg\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 6014,
    "path": "../public/_nuxt/B2fxA5qo.js"
  },
  "/_nuxt/B2kPl_O1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"330-SBgz4oXdUO6AxtStJkzmGNV/8i8\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 816,
    "path": "../public/_nuxt/B2kPl_O1.js"
  },
  "/_nuxt/B3Z5dCpP.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1ee-C8gv19wDANjDSOlRGlVYzwbDd3o\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 494,
    "path": "../public/_nuxt/B3Z5dCpP.js"
  },
  "/_nuxt/B4iR6N3_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2ccf-WgxEtdfD9l8z9SZ5/ZmMpwjt56o\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 11471,
    "path": "../public/_nuxt/B4iR6N3_.js"
  },
  "/_nuxt/B4SLziZa.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"13a7-rVlVW6SjUb3l0RDL0PLzZ2xUAdQ\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 5031,
    "path": "../public/_nuxt/B4SLziZa.js"
  },
  "/_nuxt/B56lO0ps.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"184-K7ZnfPbIp2plN4rjwEpw+qsbgAU\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 388,
    "path": "../public/_nuxt/B56lO0ps.js"
  },
  "/_nuxt/B5seet4k.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"c9db-PAewH7TWKcyk65AYBiv4ayCow7U\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 51675,
    "path": "../public/_nuxt/B5seet4k.js"
  },
  "/_nuxt/b5tjc3US.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"262a-gopw/95juTy5BojIBEPYTLU+z7E\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 9770,
    "path": "../public/_nuxt/b5tjc3US.js"
  },
  "/_nuxt/B6CBqj40.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"c070-LtOFFguRY3B0nPYBYIEmpzgifb8\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 49264,
    "path": "../public/_nuxt/B6CBqj40.js"
  },
  "/_nuxt/B7FJ5Grr.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2493-r1GpY2PJ+WNc1e9sLrROIEv8R6s\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 9363,
    "path": "../public/_nuxt/B7FJ5Grr.js"
  },
  "/_nuxt/B83qCLZV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"663a-1RHJToFmtpWq7799jbWXsnZJSzc\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 26170,
    "path": "../public/_nuxt/B83qCLZV.js"
  },
  "/_nuxt/B8AYfPOH.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"45aa-MpEseg55f5lsG0C2/PWVaoE63MQ\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 17834,
    "path": "../public/_nuxt/B8AYfPOH.js"
  },
  "/_nuxt/B8kPI-4W.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"a72-aujsKVDs8C12GmSRBe4RZskAm88\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 2674,
    "path": "../public/_nuxt/B8kPI-4W.js"
  },
  "/_nuxt/B93dvV-w.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"45b-5Kye4xIDDzP1WaZ/FW8FcRdrqyU\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 1115,
    "path": "../public/_nuxt/B93dvV-w.js"
  },
  "/_nuxt/B9x4r14s.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5b1-tvgnvOxRqkk4N0SC1pyQx15bUBs\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 1457,
    "path": "../public/_nuxt/B9x4r14s.js"
  },
  "/_nuxt/B9zzEBIg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1a0b-K6kZtlFTAHIC3DwlU1f3+W/EfHo\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 6667,
    "path": "../public/_nuxt/B9zzEBIg.js"
  },
  "/_nuxt/B9_CmdZt.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3d0-Fry/tyT1uWzdZdxAiNN+LxDTycs\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 976,
    "path": "../public/_nuxt/B9_CmdZt.js"
  },
  "/_nuxt/BB7BZ0Dd.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"912-d4w78KKEQrnLUQ5CAt1qYmv3pRc\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 2322,
    "path": "../public/_nuxt/BB7BZ0Dd.js"
  },
  "/_nuxt/BbAmCUVS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"30c1-SAuuFfZHFphSiMIvtW9bIsrli/0\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 12481,
    "path": "../public/_nuxt/BbAmCUVS.js"
  },
  "/_nuxt/BBdiBh7f.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"12e-YeU3Vh2KSYqKG8vn4DWRezewerE\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 302,
    "path": "../public/_nuxt/BBdiBh7f.js"
  },
  "/_nuxt/BbF5vgKY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"47d-IibAfT/36TD8i3TJzpGRScnPLEg\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 1149,
    "path": "../public/_nuxt/BbF5vgKY.js"
  },
  "/_nuxt/BbkqGeT2.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"666-rreAGmXCEuVCD7zK+iVhmMZhiso\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 1638,
    "path": "../public/_nuxt/BbkqGeT2.js"
  },
  "/_nuxt/BclPMwtR.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"db2-xNlNaPtnce4D0CJWXmQ5ZZPXr2c\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 3506,
    "path": "../public/_nuxt/BclPMwtR.js"
  },
  "/_nuxt/BcRB5V7Z.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1b55-IhtYRp7WeFWUOS9TzLDrPNmUSlc\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6997,
    "path": "../public/_nuxt/BcRB5V7Z.js"
  },
  "/_nuxt/BcWnSN5u.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"150-PQqCYlZjfB2u3+wmGa61+YVk9OA\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 336,
    "path": "../public/_nuxt/BcWnSN5u.js"
  },
  "/_nuxt/Bd5VnGqy.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"300-t1KYX2I+ZX5R4sq0VjUNXHSLCyU\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 768,
    "path": "../public/_nuxt/Bd5VnGqy.js"
  },
  "/_nuxt/BDzbO1Tk.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"28a-E090cA5H1Uc+9HMmng2LJOcYCWI\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 650,
    "path": "../public/_nuxt/BDzbO1Tk.js"
  },
  "/_nuxt/BEa2otGy.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"17d6-BGVjBsmdNNHA6BFlBpO2ChMjL4k\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 6102,
    "path": "../public/_nuxt/BEa2otGy.js"
  },
  "/_nuxt/Bd_Xxayn.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"18e3-yn4Y798NWZJPBF2d3DySr4STkHM\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 6371,
    "path": "../public/_nuxt/Bd_Xxayn.js"
  },
  "/_nuxt/Berb6lB7.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"bb9-FsPcDkx4zfXg2PfETexKzmcc9lo\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 3001,
    "path": "../public/_nuxt/Berb6lB7.js"
  },
  "/_nuxt/BerWVpxm.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"75a-mltmq3pegcJA5XmFE/STTcQTUew\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 1882,
    "path": "../public/_nuxt/BerWVpxm.js"
  },
  "/_nuxt/BF4yyfxD.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"60-cpuzEy4iwbnTaTzurxlK9a9XACY\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 96,
    "path": "../public/_nuxt/BF4yyfxD.js"
  },
  "/_nuxt/BFdqXSml.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2bc-TFkcHH9GPwsbQSf5uh+iFQqLJ88\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 700,
    "path": "../public/_nuxt/BFdqXSml.js"
  },
  "/_nuxt/BFKNXbSd.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"127e-huwgDpC52QUMf1YWkpm0psIOJWc\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 4734,
    "path": "../public/_nuxt/BFKNXbSd.js"
  },
  "/_nuxt/BFxKm3oi.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5a93-OupYiR2zW0LcNKJarC53kw8Wx78\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 23187,
    "path": "../public/_nuxt/BFxKm3oi.js"
  },
  "/_nuxt/BFzh45mr.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1af-CGhzGL3MxDUQXaQ9EXn/0e3dJ94\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 431,
    "path": "../public/_nuxt/BFzh45mr.js"
  },
  "/_nuxt/BgI3r82t.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3bc-67AO0CSq/dkEFJzCF7L7bqQ9Xfs\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 956,
    "path": "../public/_nuxt/BgI3r82t.js"
  },
  "/_nuxt/BgwSZpiY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1566-9oiTPyd4n2IMQFCl4K9N2ltJEHA\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 5478,
    "path": "../public/_nuxt/BgwSZpiY.js"
  },
  "/_nuxt/BGznML1T.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"23d-7O8PTUKNe1M9g6Fms9oFCMo0qzE\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 573,
    "path": "../public/_nuxt/BGznML1T.js"
  },
  "/_nuxt/BhHIHON8.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"172d-iZ1ekzkt8XrfnRPnJ1anA9pCjow\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 5933,
    "path": "../public/_nuxt/BhHIHON8.js"
  },
  "/_nuxt/Bhuosc0H.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4621-HDGEaV6BD+R0M7sy0OsssqhRKL4\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 17953,
    "path": "../public/_nuxt/Bhuosc0H.js"
  },
  "/_nuxt/BhWFnbXy.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"16e5-Sltbvim0ZEZLjDYDAUg8mZYpvOg\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 5861,
    "path": "../public/_nuxt/BhWFnbXy.js"
  },
  "/_nuxt/BIDQUSs3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"20f0-bR3n0f7VkpBi2HIF7B2PLmhDZf4\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 8432,
    "path": "../public/_nuxt/BIDQUSs3.js"
  },
  "/_nuxt/BIgcU2rD.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"11a-rvfeex/UefWoU2oJklIarnFdI78\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 282,
    "path": "../public/_nuxt/BIgcU2rD.js"
  },
  "/_nuxt/Bj1dvMWn.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1e7-5rM0s6RHBWF4GulgtyTdtULTQmE\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 487,
    "path": "../public/_nuxt/Bj1dvMWn.js"
  },
  "/_nuxt/Bji49_ub.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"14c2-H8XeiKYY8Voc0Yf5PL0ZXWt+apI\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 5314,
    "path": "../public/_nuxt/Bji49_ub.js"
  },
  "/_nuxt/BjP7i5jk.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"832-17Jf2eEL/Ya1E/a8IZ4sGgDmCwU\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2098,
    "path": "../public/_nuxt/BjP7i5jk.js"
  },
  "/_nuxt/BJrUSZNs.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"23ef-U13Oa6AP0hMrVOUyJz4V4+DwhvQ\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 9199,
    "path": "../public/_nuxt/BJrUSZNs.js"
  },
  "/_nuxt/BkaNaBaq.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8f2b-BPqcsEFCb4BXE/zw83rkDsSIzsY\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 36651,
    "path": "../public/_nuxt/BkaNaBaq.js"
  },
  "/_nuxt/BKEDL3NF.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5cc-g7D8A3HncJynoXPBx8bbyAgHB9w\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1484,
    "path": "../public/_nuxt/BKEDL3NF.js"
  },
  "/_nuxt/BKMeEsAV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2370-Qey9vw5RIZ5+blfHCx1X/Yj2m1A\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 9072,
    "path": "../public/_nuxt/BKMeEsAV.js"
  },
  "/_nuxt/BkVDiW_O.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3498-dtZpn45qE6nJBnfiRwVdgLdeQes\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 13464,
    "path": "../public/_nuxt/BkVDiW_O.js"
  },
  "/_nuxt/BL9BVHK8.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"10ee-DZu+2wHYJ3MsFCw1djFB2oH/Ucs\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 4334,
    "path": "../public/_nuxt/BL9BVHK8.js"
  },
  "/_nuxt/BlESdFMh.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2ca7-T46hNFjDzkVKCOi5NRsUAuAIuTI\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 11431,
    "path": "../public/_nuxt/BlESdFMh.js"
  },
  "/_nuxt/BlH2YGUd.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"6fa-14HrzOqdWjla5rDqHDuhUtP23lc\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1786,
    "path": "../public/_nuxt/BlH2YGUd.js"
  },
  "/_nuxt/BO5zG-EZ.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4107-BrDkBfGaip2zaWVVm3elZ7HfZgI\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 16647,
    "path": "../public/_nuxt/BO5zG-EZ.js"
  },
  "/_nuxt/BOAnWFLb.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"9d8e-ItUo1QSKWLAOLhiEZBPdW9B7GyA\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 40334,
    "path": "../public/_nuxt/BOAnWFLb.js"
  },
  "/_nuxt/BoNfY5cR.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1ba-BpllyUO0UA8+YR4M+qxBsyB6GYA\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 442,
    "path": "../public/_nuxt/BoNfY5cR.js"
  },
  "/_nuxt/BoPHOpiz.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"88d-eYCs6yQF7m0D/H1DoMcE9D78mJ8\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 2189,
    "path": "../public/_nuxt/BoPHOpiz.js"
  },
  "/_nuxt/BOZz5nQY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5d37-wu+KnLz7H2JUD/QVv+yHRxd/gyQ\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 23863,
    "path": "../public/_nuxt/BOZz5nQY.js"
  },
  "/_nuxt/Bp1sEnmr.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"267-5laBDXYEsPHSO6qOJZPmjwAiFHg\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 615,
    "path": "../public/_nuxt/Bp1sEnmr.js"
  },
  "/_nuxt/BP2gnDQ3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"13f2-5x89Jb4DYQnD2YB3MiZ/uqPFIC8\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 5106,
    "path": "../public/_nuxt/BP2gnDQ3.js"
  },
  "/_nuxt/BPC6I8e1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c6-+KmOZnnlY6nt36Y18HtTyPZxA+o\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 454,
    "path": "../public/_nuxt/BPC6I8e1.js"
  },
  "/_nuxt/BpE3h1gE.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"123f-Fno2n5XtsCe/yM+1MEl39yAV7cQ\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 4671,
    "path": "../public/_nuxt/BpE3h1gE.js"
  },
  "/_nuxt/BppaJtZ5.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"343b-xsm43P1zXUK556XEApVhh7HBWUo\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 13371,
    "path": "../public/_nuxt/BppaJtZ5.js"
  },
  "/_nuxt/BpVZ_uCt.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"551c-g7tHj47GjdBr9uK/eC+abhAlexs\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 21788,
    "path": "../public/_nuxt/BpVZ_uCt.js"
  },
  "/_nuxt/BqOf_2nr.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1993-q0y3eoA80XdR+adaE1nzjhRc+m0\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 6547,
    "path": "../public/_nuxt/BqOf_2nr.js"
  },
  "/_nuxt/BqvgoNy9.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c0c-94BSZJciDoo0l3jmHQD1RiNCY6g\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 7180,
    "path": "../public/_nuxt/BqvgoNy9.js"
  },
  "/_nuxt/BQxta0Da.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1374b-4rfdwv0MSp1l4cICLLQamQcnb9E\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 79691,
    "path": "../public/_nuxt/BQxta0Da.js"
  },
  "/_nuxt/BrMAvBk1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1b3-ot6SDm/6jJbqQkukMJLCh5mtNvk\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 435,
    "path": "../public/_nuxt/BrMAvBk1.js"
  },
  "/_nuxt/BRWW8gyr.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"146b-p5/IRkIwaq6RN5GZyOeYN01KRrs\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 5227,
    "path": "../public/_nuxt/BRWW8gyr.js"
  },
  "/_nuxt/Bs49R7Jx.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2536-ntxew83+ku8yDjmJjwvG3OoF3iw\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 9526,
    "path": "../public/_nuxt/Bs49R7Jx.js"
  },
  "/_nuxt/BsyxlmHi.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"a1-Ncz9ga56f1uj7GGAvvzOv8tYzyk\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 161,
    "path": "../public/_nuxt/BsyxlmHi.js"
  },
  "/_nuxt/BVe-JEV8.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1b38-YIwffY/gf2c93AKiJ053rEljwnQ\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 6968,
    "path": "../public/_nuxt/BVe-JEV8.js"
  },
  "/_nuxt/BvLjxVVB.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"70f4-cxtPs0OGbMQz0V9W4t/i/IfzC/M\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 28916,
    "path": "../public/_nuxt/BvLjxVVB.js"
  },
  "/_nuxt/BVtI5ipX.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4a18-QgKIFBZ1qC4qncJy/lOEKpPcpoY\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 18968,
    "path": "../public/_nuxt/BVtI5ipX.js"
  },
  "/_nuxt/BWrxNrT_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"55-f61kKXJztmD9SYHhonj8W0myyhs\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 85,
    "path": "../public/_nuxt/BWrxNrT_.js"
  },
  "/_nuxt/BwZCZUNv.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"15e-CODeJER3KoDVgu1ketW2ht6AsQg\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 350,
    "path": "../public/_nuxt/BwZCZUNv.js"
  },
  "/_nuxt/Bw_wEDt3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"f00f-FfS4ZRHR0HUcrSUqgczjNAMirgs\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 61455,
    "path": "../public/_nuxt/Bw_wEDt3.js"
  },
  "/_nuxt/Brb_7992.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"85db4-fxOB/vBq7L0HOIk2DJSm7SzORSM\"",
    "mtime": "2026-01-28T08:24:20.653Z",
    "size": 548276,
    "path": "../public/_nuxt/Brb_7992.js"
  },
  "/_nuxt/Bxe5E6H5.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"26bf4-O0vHc5TaHc/sz061K9gBTOn5U9I\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 158708,
    "path": "../public/_nuxt/Bxe5E6H5.js"
  },
  "/_nuxt/BxjnrTC5.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8ef7-2/mcs5m9GclYqoYjMmv5Se5xOMg\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 36599,
    "path": "../public/_nuxt/BxjnrTC5.js"
  },
  "/_nuxt/BYxx02sH.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1416-ipjA0JAge5dhArTSHbTqavBVW2o\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 5142,
    "path": "../public/_nuxt/BYxx02sH.js"
  },
  "/_nuxt/BZ-amxjN.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"27c1-m8X4q3eN+ytUfvcxPRZvHRT/tPU\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 10177,
    "path": "../public/_nuxt/BZ-amxjN.js"
  },
  "/_nuxt/C-4-ven1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1947-Yf3EHpFsaEpvEOpJdvMM7rBJf4s\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 6471,
    "path": "../public/_nuxt/C-4-ven1.js"
  },
  "/_nuxt/C02LlKe_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"90d5-nqdhrgxrKlySmNNc/KYmqXLWTWA\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 37077,
    "path": "../public/_nuxt/C02LlKe_.js"
  },
  "/_nuxt/C02pXPrI.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"52b1-i4JWJbizeGtHDPgaiHPuSAYaZG8\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 21169,
    "path": "../public/_nuxt/C02pXPrI.js"
  },
  "/_nuxt/C0Dv8eco.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"6250-niaC/3c//tQtvn/RONIXSRSOMgg\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 25168,
    "path": "../public/_nuxt/C0Dv8eco.js"
  },
  "/_nuxt/C1ecaOpY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"72d8-YoxJU8NRbhW+7kEzyULXhxfcHz8\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 29400,
    "path": "../public/_nuxt/C1ecaOpY.js"
  },
  "/_nuxt/C1Mq3dhS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3845-TEOIILci2VYQfN7REHcfa3cfJT0\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 14405,
    "path": "../public/_nuxt/C1Mq3dhS.js"
  },
  "/_nuxt/C2sfaxS1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5af6-fkeaXZ10rL7gLPfFqoBCiMQnH8g\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 23286,
    "path": "../public/_nuxt/C2sfaxS1.js"
  },
  "/_nuxt/C2Y7X8I6.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"30db-lWXoplqMEArToEbUJ0VK7zQBjhQ\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 12507,
    "path": "../public/_nuxt/C2Y7X8I6.js"
  },
  "/_nuxt/C3YKpzsl.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c07-m11ZNAHKn4gHBMEtCQ2zgkdLxF0\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 7175,
    "path": "../public/_nuxt/C3YKpzsl.js"
  },
  "/_nuxt/C4uQ4N82.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4dd-ecSjL4KUQMAPqJ1hpdpW2lYftVo\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1245,
    "path": "../public/_nuxt/C4uQ4N82.js"
  },
  "/_nuxt/C5bYFe-o.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4070-mQLdLRcIZ0pH/H5rE8f1esbuEWc\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 16496,
    "path": "../public/_nuxt/C5bYFe-o.js"
  },
  "/_nuxt/C6HMx8Pp.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"cd13-c94UN6DRTPUr/m2xxe8rAdS4Iv4\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 52499,
    "path": "../public/_nuxt/C6HMx8Pp.js"
  },
  "/_nuxt/C6R5l0wU.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1725-IRiSCK2vxHthgv616iL7Awdebro\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 5925,
    "path": "../public/_nuxt/C6R5l0wU.js"
  },
  "/_nuxt/C7clVqvh.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5af-mqSEhxYa/d1NFJluey62SK/5+5A\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1455,
    "path": "../public/_nuxt/C7clVqvh.js"
  },
  "/_nuxt/C7Jmz_GV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2313-HReP573dlcdF9a4Fd+I5fKV6zuI\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 8979,
    "path": "../public/_nuxt/C7Jmz_GV.js"
  },
  "/_nuxt/C8T5wX-j.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1874-0XQe53dUZW7lm3NsZYcCOpTv4JE\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 6260,
    "path": "../public/_nuxt/C8T5wX-j.js"
  },
  "/_nuxt/C9k5g-R6.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1989-tHXvL+7fh5RX7lO3GQH5ip5yvlE\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6537,
    "path": "../public/_nuxt/C9k5g-R6.js"
  },
  "/_nuxt/Ca8MJRM6.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"b9-Muq0uSvkDmqTkycnF5lZKowPJXU\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 185,
    "path": "../public/_nuxt/Ca8MJRM6.js"
  },
  "/_nuxt/CAEwjUeS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"194-ySXcOpPhpGqRgxxBu2JX+v/ZhbA\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 404,
    "path": "../public/_nuxt/CAEwjUeS.js"
  },
  "/_nuxt/CAJQ9Mn7.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3060-bDQPUNJAqz2kBASKZ1VfJfVb7vQ\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 12384,
    "path": "../public/_nuxt/CAJQ9Mn7.js"
  },
  "/_nuxt/CaKfV-y_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1a8-lsHzplThlgDzfXMarDPbcfcxKbk\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 424,
    "path": "../public/_nuxt/CaKfV-y_.js"
  },
  "/_nuxt/CaSR6SuD.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"9cb-/Tl4AM8CvJ348Y8v9HTlj5TG1Zw\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2507,
    "path": "../public/_nuxt/CaSR6SuD.js"
  },
  "/_nuxt/CBDHv4S3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"c27-Y0OajJpsz35OeUdQeMUIM0Nbdxo\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 3111,
    "path": "../public/_nuxt/CBDHv4S3.js"
  },
  "/_nuxt/Cbl2BWlS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"daf-xtGjDzSeTumvsSv/o995R2GNcSs\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 3503,
    "path": "../public/_nuxt/Cbl2BWlS.js"
  },
  "/_nuxt/CBsXM9Ye.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"b3a-cCkt5MSw+7Ukrwy+/8xMafPs2fQ\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2874,
    "path": "../public/_nuxt/CBsXM9Ye.js"
  },
  "/_nuxt/CbX5zC3K.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"80f9-9YhPxm9Ps/+JT7bbiIHVDVElB0s\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 33017,
    "path": "../public/_nuxt/CbX5zC3K.js"
  },
  "/_nuxt/Cc11XItg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1bf7-OYzYQ+fFPN/R03+ezt9NmwhvB6o\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 7159,
    "path": "../public/_nuxt/Cc11XItg.js"
  },
  "/_nuxt/Ccdp8IHV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2b88-RvZd50HYCwH0IJpB+Ffro26Kv88\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 11144,
    "path": "../public/_nuxt/Ccdp8IHV.js"
  },
  "/_nuxt/Cdgibr2A.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"106-NtMIh8M0hjQU917InoLdxwHFDFE\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 262,
    "path": "../public/_nuxt/Cdgibr2A.js"
  },
  "/_nuxt/Cdic6OTU.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"ba1d-NZQKQWaTrqGcIO3Fk6SEluS8oAk\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 47645,
    "path": "../public/_nuxt/Cdic6OTU.js"
  },
  "/_nuxt/Cdp2er0J.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"6b0-tPzCUlyh7DivcpxFSF8shgg6nFg\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1712,
    "path": "../public/_nuxt/Cdp2er0J.js"
  },
  "/_nuxt/CdQnvyki.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"612b-ahJyUUW3Lz2PGE4MYSUC1ALUluM\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 24875,
    "path": "../public/_nuxt/CdQnvyki.js"
  },
  "/_nuxt/Cds6B6h-.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"142a6-/S9aWVTH0HvL8UpGqCc2CShNgrU\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 82598,
    "path": "../public/_nuxt/Cds6B6h-.js"
  },
  "/_nuxt/CeZUl16W.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1040-PA8qQZf3Y63CYkLpAeSBvTMk6Zs\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 4160,
    "path": "../public/_nuxt/CeZUl16W.js"
  },
  "/_nuxt/CFVt-3IM.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"7502-xQR3RWk57wV2v6sTTn+/DzR5RPI\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 29954,
    "path": "../public/_nuxt/CFVt-3IM.js"
  },
  "/_nuxt/CfyVN7zA.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5ec9-LkT6q9NZtScgO1UzZunJtGWhtNs\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 24265,
    "path": "../public/_nuxt/CfyVN7zA.js"
  },
  "/_nuxt/CGAwYWOU.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"58c-63ewKtzPVjjg6y0cdLK98S7ds9A\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1420,
    "path": "../public/_nuxt/CGAwYWOU.js"
  },
  "/_nuxt/CGjXum3Q.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"27e9-36oH1kGkEY7gcxWumezxte4xJxQ\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 10217,
    "path": "../public/_nuxt/CGjXum3Q.js"
  },
  "/_nuxt/CIrSG0Cx.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c3-+uHM1QU+/jPY3XYWa7WvtA/NKW8\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 451,
    "path": "../public/_nuxt/CIrSG0Cx.js"
  },
  "/_nuxt/CJp6I_A2.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"94d-8nDOOJo10m7fwpzfKA/TAv3sURY\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2381,
    "path": "../public/_nuxt/CJp6I_A2.js"
  },
  "/_nuxt/CjSe0VM9.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3648-A5BhErKvxl2nn5JFj6e/iBoFBac\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 13896,
    "path": "../public/_nuxt/CjSe0VM9.js"
  },
  "/_nuxt/CJym0gpn.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"296d-xVJRxOQCWdO5IZ/aGyTjI9M01Io\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 10605,
    "path": "../public/_nuxt/CJym0gpn.js"
  },
  "/_nuxt/CjZOrx9Z.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"c3cb-+qFmBXYXbZAPuX2b4MK7+cEfwgE\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 50123,
    "path": "../public/_nuxt/CjZOrx9Z.js"
  },
  "/_nuxt/CjzyWchB.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"6681-Wu+hc0HDTNKqpQeqZQ0vxxhaSeg\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 26241,
    "path": "../public/_nuxt/CjzyWchB.js"
  },
  "/_nuxt/CKGmmoJI.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"aca-XBs95f0k+GoEO1i70FpQmNEcHPA\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2762,
    "path": "../public/_nuxt/CKGmmoJI.js"
  },
  "/_nuxt/CkZtUynl.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5b-JgQNoM5siHWKSf7r5gJn0YW5v0k\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 91,
    "path": "../public/_nuxt/CkZtUynl.js"
  },
  "/_nuxt/CLekOcNf.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1a65-rnlPCNFDrHxZIdGlj65AMwgvzds\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 6757,
    "path": "../public/_nuxt/CLekOcNf.js"
  },
  "/_nuxt/ClIC8E97.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1cc-XTDo8DX41lwmoqxaubekk7GFZwg\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 460,
    "path": "../public/_nuxt/ClIC8E97.js"
  },
  "/_nuxt/cNWBHc-2.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5495-yroOPyLt8HlobIGuXpl/fHmlzLQ\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 21653,
    "path": "../public/_nuxt/cNWBHc-2.js"
  },
  "/_nuxt/Cn2CHBaW.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"112cd-QAvQSWAOiLmUGS/ZeXZ7+sXPHZE\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 70349,
    "path": "../public/_nuxt/Cn2CHBaW.js"
  },
  "/_nuxt/CNWKdaqh.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8d-mCKIWkrH1uFCXCJzJwAChMLMYuY\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 141,
    "path": "../public/_nuxt/CNWKdaqh.js"
  },
  "/_nuxt/Cog6Dzzq.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"336c-zndESg9NuvH5yCi/9hMo3oHBqUk\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 13164,
    "path": "../public/_nuxt/Cog6Dzzq.js"
  },
  "/_nuxt/CompanyLanding.BzQkekRW.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"549-iUZBERpTjv4JyleDfggylMNl738\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1353,
    "path": "../public/_nuxt/CompanyLanding.BzQkekRW.css"
  },
  "/_nuxt/COtzpAEY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5cd7-uHNugKBJ8gyIY1yThDGAHsMxM0o\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 23767,
    "path": "../public/_nuxt/COtzpAEY.js"
  },
  "/_nuxt/CouponCard.ClNb95a0.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"12e5-F0817vd2VaWWjeRAlZLCamLb2+g\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 4837,
    "path": "../public/_nuxt/CouponCard.ClNb95a0.css"
  },
  "/_nuxt/CourseCard.DCbVgZNR.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"75-toJAn4effk01dDMbcRH86rZkHVI\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 117,
    "path": "../public/_nuxt/CourseCard.DCbVgZNR.css"
  },
  "/_nuxt/CourseCard.DR761scx.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"75-KR+RvAcECKt5VB75VhbD7sI1Rqs\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 117,
    "path": "../public/_nuxt/CourseCard.DR761scx.css"
  },
  "/_nuxt/CourseFeedsList.W8Ld-9SE.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"8ae-vDrJDTeiHbbdIxuTjtodg2NIxVk\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 2222,
    "path": "../public/_nuxt/CourseFeedsList.W8Ld-9SE.css"
  },
  "/_nuxt/Cpj98o6Y.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"ec-QtY1KaLA8vnMK3l2IvajpxyuPmY\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 236,
    "path": "../public/_nuxt/Cpj98o6Y.js"
  },
  "/_nuxt/CpMehWEh.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"85-KucSdI/WlwmYodHdkYWbKmKU1aU\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 133,
    "path": "../public/_nuxt/CpMehWEh.js"
  },
  "/_nuxt/CpqDDrvi.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"6873-swsxvge7NgvHiYM7ar+6VS6lYSQ\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 26739,
    "path": "../public/_nuxt/CpqDDrvi.js"
  },
  "/_nuxt/CPV3B8FV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c07-u+PqLTt+OJGZUWV5bmwGk5htAJ4\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 7175,
    "path": "../public/_nuxt/CPV3B8FV.js"
  },
  "/_nuxt/CQtViq-t.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"67b-3BeBkDbHRMDRnVZ//w7kTT7wFRY\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 1659,
    "path": "../public/_nuxt/CQtViq-t.js"
  },
  "/_nuxt/Cqef47Oy.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"db04-DZLE3Dg1+re/Hon5/NfpXoW7N9Y\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 56068,
    "path": "../public/_nuxt/Cqef47Oy.js"
  },
  "/_nuxt/CQzGFJI3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"12cb-d2UZALD4/fDM20FWe8M9RPTCjL0\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 4811,
    "path": "../public/_nuxt/CQzGFJI3.js"
  },
  "/_nuxt/create.DR_nMbC4.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1bb-KVjdJVf/bbSYMA2/E7KdoFAjXIg\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 443,
    "path": "../public/_nuxt/create.DR_nMbC4.css"
  },
  "/_nuxt/create.RP47jiOW.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"e1-5N0Omz75yC+Qhqm4F7KT6wxsXQg\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 225,
    "path": "../public/_nuxt/create.RP47jiOW.css"
  },
  "/_nuxt/CreatePostBox.D0FBGBAR.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"2a8-hyMd1Nn7rKVfX944EdBAWTyFNlM\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 680,
    "path": "../public/_nuxt/CreatePostBox.D0FBGBAR.css"
  },
  "/_nuxt/CrIF8-i-.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8d8-2zAdtJ2+UVJMm9SpfTTr9bWg84M\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 2264,
    "path": "../public/_nuxt/CrIF8-i-.js"
  },
  "/_nuxt/Csv3cgM4.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5c8-6/WVhJ8f/tUeYL1jjWTOG4Z4BTQ\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1480,
    "path": "../public/_nuxt/Csv3cgM4.js"
  },
  "/_nuxt/CuOp0z0b.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"edd-miPOV5PRt4bMKCn/teCl4+txQWg\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 3805,
    "path": "../public/_nuxt/CuOp0z0b.js"
  },
  "/_nuxt/Cv9xWlTX.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"161-W2oG3vJVE8b+rZsNfo8EecWdW5o\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 353,
    "path": "../public/_nuxt/Cv9xWlTX.js"
  },
  "/_nuxt/CvfCsUnE.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"74f3-AzqJ0n50z+2ZfDN8d3E3GwvRB+k\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 29939,
    "path": "../public/_nuxt/CvfCsUnE.js"
  },
  "/_nuxt/CW0HEJiO.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"992-TqR7heLBsLpH6DOW8t0GiNQEctg\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 2450,
    "path": "../public/_nuxt/CW0HEJiO.js"
  },
  "/_nuxt/CwkPr7df.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1af-Ypd/Btw3vNzBtfUlyDqUSjPmDCc\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 431,
    "path": "../public/_nuxt/CwkPr7df.js"
  },
  "/_nuxt/CWQki3u3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"297-LF/VeqKGYHiu3snma33BnoqQsrM\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 663,
    "path": "../public/_nuxt/CWQki3u3.js"
  },
  "/_nuxt/CWz3pxiP.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1390-VTeMoNTZCBPgtLq+2aB5qQIV5mc\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 5008,
    "path": "../public/_nuxt/CWz3pxiP.js"
  },
  "/_nuxt/CwzBsPHJ.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2a0-gI19tby22K0cpaM5boFm8JUrQ8o\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 672,
    "path": "../public/_nuxt/CwzBsPHJ.js"
  },
  "/_nuxt/CxX-C7VL.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4ca-LZ18tz/q7Ze55xh7khRe0SaiMIw\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 1226,
    "path": "../public/_nuxt/CxX-C7VL.js"
  },
  "/_nuxt/CYh1PtFM.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"60a-3tlqWZNJroATQFDfS0l4yf5ll6s\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1546,
    "path": "../public/_nuxt/CYh1PtFM.js"
  },
  "/_nuxt/CZYWTWzM.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"111-mtzmy8Z6mIGYR9Aj6jz1K9QD+GE\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 273,
    "path": "../public/_nuxt/CZYWTWzM.js"
  },
  "/_nuxt/C_bEWirS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2811-zEWTaPPmiMmmd8Sr/rXecdruDHU\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 10257,
    "path": "../public/_nuxt/C_bEWirS.js"
  },
  "/_nuxt/D-aAgd9X.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"22f-/RyDpDeEk9Mezv5nEiRjQU7v7e8\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 559,
    "path": "../public/_nuxt/D-aAgd9X.js"
  },
  "/_nuxt/D-C0GVeH.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3f84-kTIHEiyHt9+XtIZSBq7uB5DH5Zs\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 16260,
    "path": "../public/_nuxt/D-C0GVeH.js"
  },
  "/_nuxt/D-YIuzue.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"b8d-elsYp9y6Lgwg0XM524pY3pI6DkA\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 2957,
    "path": "../public/_nuxt/D-YIuzue.js"
  },
  "/_nuxt/D0PbhFXl.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3de0-pwxjYK60KJjIPZd9kIOduThffq8\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 15840,
    "path": "../public/_nuxt/D0PbhFXl.js"
  },
  "/_nuxt/D1e3wYap.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2dff-y8DyUEpXTuC3rkAEjMScyrWxQjE\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 11775,
    "path": "../public/_nuxt/D1e3wYap.js"
  },
  "/_nuxt/D1kKPk82.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"35a9-8B5Pkgo6znUn8yp3HzrNCRph/XY\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 13737,
    "path": "../public/_nuxt/D1kKPk82.js"
  },
  "/_nuxt/D1PV-R3i.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"531f-ithWX97AxANMKvkXLkZ/gLGh4oo\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 21279,
    "path": "../public/_nuxt/D1PV-R3i.js"
  },
  "/_nuxt/D2G07s1f.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"42d5-TOjAW2gcckMx5T/1DkqK5YG8xSo\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 17109,
    "path": "../public/_nuxt/D2G07s1f.js"
  },
  "/_nuxt/D2hSEuy5.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"10b7-V1ICzNSIBJJWe+C/1+PFei1g+Ng\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 4279,
    "path": "../public/_nuxt/D2hSEuy5.js"
  },
  "/_nuxt/D2pzP2Cg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2a16-4DF26K0uE8QjyzF5wFY1Pii1Y1U\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 10774,
    "path": "../public/_nuxt/D2pzP2Cg.js"
  },
  "/_nuxt/D30YhwZs.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4e5-/ERjUd5KaKjERpdQKE7qQFR2pRA\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1253,
    "path": "../public/_nuxt/D30YhwZs.js"
  },
  "/_nuxt/D4EKfMrB.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"68c3-05ZHzoZHLmhEP+5gwYzDapWaZf4\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 26819,
    "path": "../public/_nuxt/D4EKfMrB.js"
  },
  "/_nuxt/D7GoV_hN.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"38fc-q0ixllT46FIG2hbA50r7xH/tdmI\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 14588,
    "path": "../public/_nuxt/D7GoV_hN.js"
  },
  "/_nuxt/D7myDRc1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"202b-+xZdddWHsQiEX2c9yzUBcJwWoVU\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 8235,
    "path": "../public/_nuxt/D7myDRc1.js"
  },
  "/_nuxt/D89CNwUG.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"15c1-xfqoprpw06+OgsrW1DvoAquBFMk\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 5569,
    "path": "../public/_nuxt/D89CNwUG.js"
  },
  "/_nuxt/D8_ABUVM.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"d63-vkhBB40ixKFDlNC37xOoGWqaES8\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 3427,
    "path": "../public/_nuxt/D8_ABUVM.js"
  },
  "/_nuxt/D92EQahg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"5b41-2zrvHNF6QYUhcKyKxkWGY13iaFs\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 23361,
    "path": "../public/_nuxt/D92EQahg.js"
  },
  "/_nuxt/D9tYhH4o.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"695c-kzSs8UkkcWpcGgKoouWPU5DTXgo\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 26972,
    "path": "../public/_nuxt/D9tYhH4o.js"
  },
  "/_nuxt/Dar3R77M.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"29ed-rw4g7LmWILhSVFof9+2hvtOrUxI\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 10733,
    "path": "../public/_nuxt/Dar3R77M.js"
  },
  "/_nuxt/DaBEmCtK.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"248b2-zfNEc5DvQ6iE+LGC12pgjuN0u5c\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 149682,
    "path": "../public/_nuxt/DaBEmCtK.js"
  },
  "/_nuxt/Dashboard.C8L_Kgy4.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"21a-qkgcSilZzoUfr5Qg571zZExbv+w\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 538,
    "path": "../public/_nuxt/Dashboard.C8L_Kgy4.css"
  },
  "/_nuxt/Dashboard.DVfa5nxp.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"5e3-4rqGw63QlSrHKiydVvAjrz/hgi8\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 1507,
    "path": "../public/_nuxt/Dashboard.DVfa5nxp.css"
  },
  "/_nuxt/Dashboard.KjIf7OF4.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"be-n1qqDt7D3s2/+8NdJctWMPyxf9Y\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 190,
    "path": "../public/_nuxt/Dashboard.KjIf7OF4.css"
  },
  "/_nuxt/DashboardStats.D15HYVOE.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1f3-gn9tBRzlDjY0YcaoI69xQ0toPiU\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 499,
    "path": "../public/_nuxt/DashboardStats.D15HYVOE.css"
  },
  "/_nuxt/DbTywl6L.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"17a3-WrLNKvYBPFEW4LpZIGNww2ZMVmA\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 6051,
    "path": "../public/_nuxt/DbTywl6L.js"
  },
  "/_nuxt/DBUot0b-.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2ab-0dEUppaoRz9sl+mq1mTCL5oqh6g\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 683,
    "path": "../public/_nuxt/DBUot0b-.js"
  },
  "/_nuxt/DBxlUi--.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"e6-yTShiSQy5IprH4Ekuj6tv1xjbPc\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 230,
    "path": "../public/_nuxt/DBxlUi--.js"
  },
  "/_nuxt/DCspkFOg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c7-k3FYeU21+PHQnt6qtBWCbMpojtU\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 455,
    "path": "../public/_nuxt/DCspkFOg.js"
  },
  "/_nuxt/DD0cxwcP.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1024-59DTwufx8KK5Mzrh00Rdw9CgWSA\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 4132,
    "path": "../public/_nuxt/DD0cxwcP.js"
  },
  "/_nuxt/ddpOXc7a.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"128-mKTggs0ZqbpgW7WTChKKqDv+0TM\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 296,
    "path": "../public/_nuxt/ddpOXc7a.js"
  },
  "/_nuxt/DE7Mu2x9.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"17ce-PA9OlzYZtdaf9Am05q0GJFZSufs\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 6094,
    "path": "../public/_nuxt/DE7Mu2x9.js"
  },
  "/_nuxt/Dea_FCw0.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"61f-kV/jNEwNJjXJ/NyXU7s/eIsFhIM\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1567,
    "path": "../public/_nuxt/Dea_FCw0.js"
  },
  "/_nuxt/DETaaNkC.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8e9-1IOTMWtoKUzAxcSEbcl4DdWW67A\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 2281,
    "path": "../public/_nuxt/DETaaNkC.js"
  },
  "/_nuxt/DE_S8Yvd.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3af-FdobpxpSzEit1KpVCoketMPkB28\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 943,
    "path": "../public/_nuxt/DE_S8Yvd.js"
  },
  "/_nuxt/DFFEPGum.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"296-b8I85D4a/gldOmIcW1Iziw5Ztmo\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 662,
    "path": "../public/_nuxt/DFFEPGum.js"
  },
  "/_nuxt/DFfRa2rP.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"190a-BXJTExzI22oW0IIoBhnR65zWgA8\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 6410,
    "path": "../public/_nuxt/DFfRa2rP.js"
  },
  "/_nuxt/DfImB6CM.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1bd-cK9Fz3KkJp+CrN4c1731+ONUJGg\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 445,
    "path": "../public/_nuxt/DfImB6CM.js"
  },
  "/_nuxt/DGbIM9IW.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2b25-XVdDtliBp3O+nFuVyKG33I6hBaM\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 11045,
    "path": "../public/_nuxt/DGbIM9IW.js"
  },
  "/_nuxt/DgC1ZzLb.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2f4a-2N+80yZuQBibhN2Uwo5h1wY0dKw\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 12106,
    "path": "../public/_nuxt/DgC1ZzLb.js"
  },
  "/_nuxt/DGR51Bcb.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2905-mx515st5coWBMBLQx9PhhuVFhyA\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 10501,
    "path": "../public/_nuxt/DGR51Bcb.js"
  },
  "/_nuxt/DGSq2Y25.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"134-Z++hjm+d/FEPsa/A10i0FipBaW0\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 308,
    "path": "../public/_nuxt/DGSq2Y25.js"
  },
  "/_nuxt/DguwAG5y.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1243-iNIB1pmDjTTjGhTGmQLmhrZcHlQ\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 4675,
    "path": "../public/_nuxt/DguwAG5y.js"
  },
  "/_nuxt/Dh977vsH.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"32b1-BLJcvfQF+LG48WLeDahXrNi1vNs\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 12977,
    "path": "../public/_nuxt/Dh977vsH.js"
  },
  "/_nuxt/DhJ7kgbc.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"658-7UXXDcmOrnXNAU2aFRjPXO/4kgE\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1624,
    "path": "../public/_nuxt/DhJ7kgbc.js"
  },
  "/_nuxt/DHkB0Tpx.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"151d-3lRJ9e+dhLY9yeXjPBXXqhKPI4Q\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 5405,
    "path": "../public/_nuxt/DHkB0Tpx.js"
  },
  "/_nuxt/DhShSXTV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2af-IlUluu5BCa7mUq8CyosrcjHQ2Iw\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 687,
    "path": "../public/_nuxt/DhShSXTV.js"
  },
  "/_nuxt/DIlZx-x4.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"13a4-hos1DAtGZw3DCL8/xf4o0dFWxLc\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 5028,
    "path": "../public/_nuxt/DIlZx-x4.js"
  },
  "/_nuxt/DIv8PkmC.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2b06-a7MwLgKLVF2+2Beie2Cqn2Kqg3k\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 11014,
    "path": "../public/_nuxt/DIv8PkmC.js"
  },
  "/_nuxt/Dixv2AQd.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"22f0-vJzv6h6NsaEIOGZZIaBoiEhdDKM\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 8944,
    "path": "../public/_nuxt/Dixv2AQd.js"
  },
  "/_nuxt/Djdl_NNj.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1cb-T2aJ6z5HRft8fRI0WjUQHAQrn50\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 459,
    "path": "../public/_nuxt/Djdl_NNj.js"
  },
  "/_nuxt/DjMo2Yo9.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8871-nrBUbUavS3fXTX7DH5bDlKwgUUk\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 34929,
    "path": "../public/_nuxt/DjMo2Yo9.js"
  },
  "/_nuxt/DKIziql_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1af-6+wahLm+VasEFuyLff355sgJ2FM\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 431,
    "path": "../public/_nuxt/DKIziql_.js"
  },
  "/_nuxt/DKokvB7S.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4934-iq3dh7SOoiUsiO1PaJjPrIQZiic\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 18740,
    "path": "../public/_nuxt/DKokvB7S.js"
  },
  "/_nuxt/DkZumruS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"4e0e-LaGbhxej88Pzs/2tMLfFtaEn0/w\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 19982,
    "path": "../public/_nuxt/DkZumruS.js"
  },
  "/_nuxt/DlaJKhfn.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2286-UZllPRnNCpRZ0VVAdT3jR5wN37A\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 8838,
    "path": "../public/_nuxt/DlaJKhfn.js"
  },
  "/_nuxt/DJsVOgax.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1fd12-5lvc/zSqri37LCrvhUOIrMyAFts\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 130322,
    "path": "../public/_nuxt/DJsVOgax.js"
  },
  "/_nuxt/Dm6qcHYp.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"761-fHq6N74U9OnWUYo1c/dDqwfK5Qc\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 1889,
    "path": "../public/_nuxt/Dm6qcHYp.js"
  },
  "/_nuxt/Dm9wGZWo.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"19fa-9/1G2PxKwkFLQkqcxZ1LxKaKBK8\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6650,
    "path": "../public/_nuxt/Dm9wGZWo.js"
  },
  "/_nuxt/Dmf6ZtBZ.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"563-/S0t8AJ1t1mLLS+TMDJyyc9Ydok\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 1379,
    "path": "../public/_nuxt/Dmf6ZtBZ.js"
  },
  "/_nuxt/DnEM1uHq.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2568-MfjQJcg48j/A6pglM9jW+yPzTwI\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 9576,
    "path": "../public/_nuxt/DnEM1uHq.js"
  },
  "/_nuxt/Dnk_-Q5K.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"8dc3-+39qIT5l48HEWitNxFKdWVFGXhc\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 36291,
    "path": "../public/_nuxt/Dnk_-Q5K.js"
  },
  "/_nuxt/DO8l-7zV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"38d5-Q6q+FfmL8skDrxqZM5gAd+MTxH0\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 14549,
    "path": "../public/_nuxt/DO8l-7zV.js"
  },
  "/_nuxt/DOkYBloH.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3b11-sfiPjH+OB7bjxH/fQNVvtOekETM\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 15121,
    "path": "../public/_nuxt/DOkYBloH.js"
  },
  "/_nuxt/DOMrm-Is.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"bbe-UvRbTHpKgJWZ71XRMtwAJmWG7nU\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 3006,
    "path": "../public/_nuxt/DOMrm-Is.js"
  },
  "/_nuxt/DonorCard.SAMCX_iD.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"157-RMOJBP/KPIH69SlAB9nhOMZmf/A\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 343,
    "path": "../public/_nuxt/DonorCard.SAMCX_iD.css"
  },
  "/_nuxt/DoSCPXZx.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"593-UKKZZLl6tgZJebYZsCrSwd7K1Js\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1427,
    "path": "../public/_nuxt/DoSCPXZx.js"
  },
  "/_nuxt/DpMpeMrT.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c4f-H87OLiYVExCYktSLS79rF8pVZcg\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 7247,
    "path": "../public/_nuxt/DpMpeMrT.js"
  },
  "/_nuxt/DppWo0Ig.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"e6d-oBNIuA/YEhTTNpjcBG4PQd+ooiA\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 3693,
    "path": "../public/_nuxt/DppWo0Ig.js"
  },
  "/_nuxt/DpQVW6-q.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"28e-hnL2IwZT2Ya0LswtAdnzRnJx4/0\"",
    "mtime": "2026-01-28T08:24:20.735Z",
    "size": 654,
    "path": "../public/_nuxt/DpQVW6-q.js"
  },
  "/_nuxt/DPrFP-Up.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"66b3-t5MtBicsmppcckXiGknMdlGg1yU\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 26291,
    "path": "../public/_nuxt/DPrFP-Up.js"
  },
  "/_nuxt/Dr3NYXtk.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"6d72-wsc4t9Zz+dxdG90WFYH1jrm4N+g\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 28018,
    "path": "../public/_nuxt/Dr3NYXtk.js"
  },
  "/_nuxt/DR81pFEE.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"317f-DD0/u2kV+W4EY+q1v49YwjJ/OD4\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 12671,
    "path": "../public/_nuxt/DR81pFEE.js"
  },
  "/_nuxt/Drg8hKby.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c5-j+pK6uZNX9XvBejw63YMgYfhgjM\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 453,
    "path": "../public/_nuxt/Drg8hKby.js"
  },
  "/_nuxt/DS3dKDlQ.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"184e-H4cNhOoFAHtmVaot8B27cdfeJHk\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 6222,
    "path": "../public/_nuxt/DS3dKDlQ.js"
  },
  "/_nuxt/DT0SCyrs.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1fa9-ZyOyh9XeRKgM411/YzXxNdQAvYI\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 8105,
    "path": "../public/_nuxt/DT0SCyrs.js"
  },
  "/_nuxt/DtDV7Hys.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"903-wn8KTqcUHB1r3Lc25uF5JOsKLuQ\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 2307,
    "path": "../public/_nuxt/DtDV7Hys.js"
  },
  "/_nuxt/DtSsA4cg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"49c8-+91kBe4VSN/Rkts6hVVwd1PzzVM\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 18888,
    "path": "../public/_nuxt/DtSsA4cg.js"
  },
  "/_nuxt/DU-SlX3x.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"477-aW+FQTVMGyOkiAWoMWOCBQJgb5Y\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1143,
    "path": "../public/_nuxt/DU-SlX3x.js"
  },
  "/_nuxt/DUBHjpz_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"156a-xVjwUhcaAuiq6bSoRhBU996xFJo\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 5482,
    "path": "../public/_nuxt/DUBHjpz_.js"
  },
  "/_nuxt/DuC8YIlZ.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"10f8-MtK/uDVHSb4ORjV2kVceLzZ8teQ\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 4344,
    "path": "../public/_nuxt/DuC8YIlZ.js"
  },
  "/_nuxt/DvNSSaO3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"431f-RlJ+i0/x42UabWlzMvIluGDzhsw\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 17183,
    "path": "../public/_nuxt/DvNSSaO3.js"
  },
  "/_nuxt/dw-ZgbPA.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3ab-X4Q+8FALCukEIJ7SL1VwhdphzNI\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 939,
    "path": "../public/_nuxt/dw-ZgbPA.js"
  },
  "/_nuxt/DXEQVQnt.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"31151-TyUyRNm9rR2JDwpyAxcruTmmr6A\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 201041,
    "path": "../public/_nuxt/DXEQVQnt.js"
  },
  "/_nuxt/DXLIP-Kg.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"12cb-iv7EUfZMRGIzQbRtl5FCBUhl+fw\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 4811,
    "path": "../public/_nuxt/DXLIP-Kg.js"
  },
  "/_nuxt/DY7ZLZSI.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2c9c-Oh6JtFyChZ0gnc6MbYa4PexxQWM\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 11420,
    "path": "../public/_nuxt/DY7ZLZSI.js"
  },
  "/_nuxt/Dy8TKyRj.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"293c-74NVqw1bb9CuuBgQjlYAkwNNLmU\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 10556,
    "path": "../public/_nuxt/Dy8TKyRj.js"
  },
  "/_nuxt/DzNqoPZo.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"951-U5HWmv8usgDpIDeU9bbCVPrVvnA\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 2385,
    "path": "../public/_nuxt/DzNqoPZo.js"
  },
  "/_nuxt/DzyfKrU3.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2ab-SgJFlRsmwMjaxI+3pmE1fnPtm7c\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 683,
    "path": "../public/_nuxt/DzyfKrU3.js"
  },
  "/_nuxt/D_Bfgw45.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3ad-pc7dbxYyQYDUdRsnhueabTFwxkY\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 941,
    "path": "../public/_nuxt/D_Bfgw45.js"
  },
  "/_nuxt/entry.BC3MrJn9.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"5d0b-omH0vSAkHMltXai2gqAkWaZw01Y\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 23819,
    "path": "../public/_nuxt/entry.BC3MrJn9.css"
  },
  "/_nuxt/GameLayout.DNxqj3u6.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"ba-qwv3I6nCu0Rj2hgWTh4e5ob2Y2c\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 186,
    "path": "../public/_nuxt/GameLayout.DNxqj3u6.css"
  },
  "/_nuxt/Gamification.COO6M-h5.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"2592-v2xX2pmOLJAeCPQ1MNeXFyiHJIg\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 9618,
    "path": "../public/_nuxt/Gamification.COO6M-h5.css"
  },
  "/_nuxt/guessing-number-game.CWQgHjog.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"246-gCNJyda6CRo15qE/Q9gL3pi3sus\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 582,
    "path": "../public/_nuxt/guessing-number-game.CWQgHjog.css"
  },
  "/_nuxt/GuestLayout.B8zhhT-I.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"7c-5poqCWEv3fBbpWUgeSlVVhap2vI\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 124,
    "path": "../public/_nuxt/GuestLayout.B8zhhT-I.css"
  },
  "/_nuxt/HD0R1ekh.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1124-CZajCz1gXxmqsAubHgzyy9bgT/4\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 4388,
    "path": "../public/_nuxt/HD0R1ekh.js"
  },
  "/_nuxt/I9g1nx1X.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"9fe2-gZS8REUHETsZCuKf/UO0wSfTiCM\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 40930,
    "path": "../public/_nuxt/I9g1nx1X.js"
  },
  "/_nuxt/IkDlMNP1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"14078-bJKpHOSsoX6pL+oJVQ5wxuPkaMk\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 82040,
    "path": "../public/_nuxt/IkDlMNP1.js"
  },
  "/_nuxt/ImageGalleryModal.C8Dqj8pQ.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"b5-appN8R+dSm2nqXocbAdVO6x5Sd8\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 181,
    "path": "../public/_nuxt/ImageGalleryModal.C8Dqj8pQ.css"
  },
  "/_nuxt/ImageLightbox.D3kMwvlf.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1a6-7g51wvgpQRt3xeG9prHS/bxLmLQ\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 422,
    "path": "../public/_nuxt/ImageLightbox.D3kMwvlf.css"
  },
  "/_nuxt/index.B9BdeNLg.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"473-gijYE6mmoNrp+54Y3orxOOYinQM\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1139,
    "path": "../public/_nuxt/index.B9BdeNLg.css"
  },
  "/_nuxt/index.BdjEmTnb.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"18ef-mTLkEG1IE2svXNnUIIesEdCrbCw\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 6383,
    "path": "../public/_nuxt/index.BdjEmTnb.css"
  },
  "/_nuxt/index.BeP14aju.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"188-9aFIZ8b5ifGzZch2JJ9FK/lpz44\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 392,
    "path": "../public/_nuxt/index.BeP14aju.css"
  },
  "/_nuxt/index.BOF-fRD5.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"75-/wC6unyKpY4mjIVMx64BWyh8V7k\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 117,
    "path": "../public/_nuxt/index.BOF-fRD5.css"
  },
  "/_nuxt/index.Bt5urCZ5.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"649-+5fkKaVZloCXiqxHvQuv6zSmYyU\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1609,
    "path": "../public/_nuxt/index.Bt5urCZ5.css"
  },
  "/_nuxt/index.BveR-j8O.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"b1-Y32dYe6QsF1c9J+R86KWlE+pbh0\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 177,
    "path": "../public/_nuxt/index.BveR-j8O.css"
  },
  "/_nuxt/index.Ca31uqM6.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"174-grRI+/0fMeWfWjOTsgRNj1qEAng\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 372,
    "path": "../public/_nuxt/index.Ca31uqM6.css"
  },
  "/_nuxt/index.COdcyeru.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"11e-EHJhTJYXsN0kzcQSAc59tHAhFIs\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 286,
    "path": "../public/_nuxt/index.COdcyeru.css"
  },
  "/_nuxt/index.tn0RQdqM.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"0-2jmj7l5rSw0yVb/vlWAYkK/YBwk\"",
    "mtime": "2026-01-28T08:24:20.669Z",
    "size": 0,
    "path": "../public/_nuxt/index.tn0RQdqM.css"
  },
  "/_nuxt/Inter-normal-300-cyrillic-ext.BOeWTOD4.woff2": {
    "type": "font/woff2",
    "etag": "\"6568-cF1iUGbboMFZ8TfnP5HiMgl9II0\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 25960,
    "path": "../public/_nuxt/Inter-normal-300-cyrillic-ext.BOeWTOD4.woff2"
  },
  "/_nuxt/Inter-normal-300-cyrillic.DqGufNeO.woff2": {
    "type": "font/woff2",
    "etag": "\"493c-n3Oy9D6jvzfMjpClqox+Zo7ERQQ\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 18748,
    "path": "../public/_nuxt/Inter-normal-300-cyrillic.DqGufNeO.woff2"
  },
  "/_nuxt/Inter-normal-300-greek-ext.DlzME5K_.woff2": {
    "type": "font/woff2",
    "etag": "\"2be0-BP5iTzJeB8nLqYAgKpWNi5o1Zm8\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 11232,
    "path": "../public/_nuxt/Inter-normal-300-greek-ext.DlzME5K_.woff2"
  },
  "/_nuxt/Inter-normal-300-greek.CkhJZR-_.woff2": {
    "type": "font/woff2",
    "etag": "\"4a34-xor/hj4YNqI52zFecXnUbzQ4Xs4\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 18996,
    "path": "../public/_nuxt/Inter-normal-300-greek.CkhJZR-_.woff2"
  },
  "/_nuxt/Inter-normal-300-latin-ext.DO1Apj_S.woff2": {
    "type": "font/woff2",
    "etag": "\"14c4c-zz61D7IQFMB9QxHvTAOk/Vh4ibQ\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 85068,
    "path": "../public/_nuxt/Inter-normal-300-latin-ext.DO1Apj_S.woff2"
  },
  "/_nuxt/Inter-normal-300-latin.Dx4kXJAl.woff2": {
    "type": "font/woff2",
    "etag": "\"bc80-8R1ym7Ck2DUNLqPQ/AYs9u8tUpg\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 48256,
    "path": "../public/_nuxt/Inter-normal-300-latin.Dx4kXJAl.woff2"
  },
  "/_nuxt/Inter-normal-300-vietnamese.CBcvBZtf.woff2": {
    "type": "font/woff2",
    "etag": "\"280c-nBythjoDQ0+5wVAendJ6wU7Xz2M\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 10252,
    "path": "../public/_nuxt/Inter-normal-300-vietnamese.CBcvBZtf.woff2"
  },
  "/_nuxt/InviteMemberModal.S7ZNxyhp.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"139-LNVc3O76ODwBlOqrK6sBf/jxmcM\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 313,
    "path": "../public/_nuxt/InviteMemberModal.S7ZNxyhp.css"
  },
  "/_nuxt/jlBfxK8h.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"a92-FHrlyvP/Q1BUr1bUsJvNEhR2AV4\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 2706,
    "path": "../public/_nuxt/jlBfxK8h.js"
  },
  "/_nuxt/JqF_PGee.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"c2d-tgj2+1sNdyRyhD5WBbXwL5DaHT0\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 3117,
    "path": "../public/_nuxt/JqF_PGee.js"
  },
  "/_nuxt/JTH4ny3o.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2e41-nupAVpDTHKCKFQ+fOsEAEf7SZ3g\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 11841,
    "path": "../public/_nuxt/JTH4ny3o.js"
  },
  "/_nuxt/JtlKdMTY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"24d-UDKXYrgLle6UrAl2sbuKnykPqwY\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 589,
    "path": "../public/_nuxt/JtlKdMTY.js"
  },
  "/_nuxt/Jz8KpF6N.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1270-duiZ6ECo89RtqBZbiRUTtKhTI7w\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 4720,
    "path": "../public/_nuxt/Jz8KpF6N.js"
  },
  "/_nuxt/KqkJvF9s.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1b4f-mEmHE+Lh7hBJEkiDXqJq5dLSTtk\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 6991,
    "path": "../public/_nuxt/KqkJvF9s.js"
  },
  "/_nuxt/lDMvVpyB.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1b14-DFqr3bIDjMGzQnEcxWPERI/AWHo\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6932,
    "path": "../public/_nuxt/lDMvVpyB.js"
  },
  "/_nuxt/LessonPost.AfVj-uFv.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"304-6zACK8//C3YKro5TOvyeFqGcKCo\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 772,
    "path": "../public/_nuxt/LessonPost.AfVj-uFv.css"
  },
  "/_nuxt/LessonQuizSection.lvcFXooe.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"119-+ArY/MoYG+oHEzkJWmMb6YAGHow\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 281,
    "path": "../public/_nuxt/LessonQuizSection.lvcFXooe.css"
  },
  "/_nuxt/LineIcons.BJUrqD8W.eot": {
    "type": "application/vnd.ms-fontobject",
    "etag": "\"1e720-Brb1T0ZfvKRHGy2/+2hJhv0INBo\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 124704,
    "path": "../public/_nuxt/LineIcons.BJUrqD8W.eot"
  },
  "/_nuxt/LineIcons.BZgAh_1U.woff2": {
    "type": "font/woff2",
    "etag": "\"c9dc-CZyFUt3Cz7BRWTM1d8GCHSxtSRk\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 51676,
    "path": "../public/_nuxt/LineIcons.BZgAh_1U.woff2"
  },
  "/_nuxt/LineIcons.Cqb3QtOe.ttf": {
    "type": "font/ttf",
    "etag": "\"1e674-ILvG5IDfYhAHeMZsdCtnFZQBfzE\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 124532,
    "path": "../public/_nuxt/LineIcons.Cqb3QtOe.ttf"
  },
  "/_nuxt/LineIcons.DrihXZZM.woff": {
    "type": "font/woff",
    "etag": "\"fcdc-Ir+U2Xeba5iDwQARUNGH2hwz/fU\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 64732,
    "path": "../public/_nuxt/LineIcons.DrihXZZM.woff"
  },
  "/_nuxt/login.Be9WgTkN.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"b1-qGl8D6KKF55Jy0NG24QvTKGlvcM\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 177,
    "path": "../public/_nuxt/login.Be9WgTkN.css"
  },
  "/_nuxt/main.DdymOwhr.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"3a3-8/gs9j4VDbPxNJq5a//364IPIBo\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 931,
    "path": "../public/_nuxt/main.DdymOwhr.css"
  },
  "/_nuxt/mdemBZQK.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"22e-W+8zWbrmKKu8vBBWHm7jCP3EceQ\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 558,
    "path": "../public/_nuxt/mdemBZQK.js"
  },
  "/_nuxt/MemberCard.HQaf3AET.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"a2-kiQdgmaRbrU9+o64TtcDgXgJgtY\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 162,
    "path": "../public/_nuxt/MemberCard.HQaf3AET.css"
  },
  "/_nuxt/mental-match.D0Dbwn6G.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1bd-oqVJyFZ3MNzfZq9+7pjkvnX+YNg\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 445,
    "path": "../public/_nuxt/mental-match.D0Dbwn6G.css"
  },
  "/_nuxt/MentalMatch.BnzOQl-j.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1c5-XWChpEbzneCJ5WwIb7aCzrk99bA\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 453,
    "path": "../public/_nuxt/MentalMatch.BnzOQl-j.css"
  },
  "/_nuxt/MN0_0vO9.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1cd0-yqtFbL4TrN5Kng/6vTvSLrQ9lI4\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 7376,
    "path": "../public/_nuxt/MN0_0vO9.js"
  },
  "/_nuxt/MnX8pgWV.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"419-RiKOYxjQ+i+83OnCOsHlHae780M\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 1049,
    "path": "../public/_nuxt/MnX8pgWV.js"
  },
  "/_nuxt/LineIcons.BMXH4wJW.svg": {
    "type": "image/svg+xml",
    "etag": "\"95511-EGNsuNrb6mE+x072/A24G+bbra4\"",
    "mtime": "2026-01-28T08:24:20.721Z",
    "size": 611601,
    "path": "../public/_nuxt/LineIcons.BMXH4wJW.svg"
  },
  "/_nuxt/N9VaLa7C.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3f79-NK4kilp2fHOtpD3Wc7VRdge7dFc\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 16249,
    "path": "../public/_nuxt/N9VaLa7C.js"
  },
  "/_nuxt/Newsfeed.uYWt3LRm.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"4e6-gPDW52X4s0gOsPPGQZ4dPzqqHKo\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 1254,
    "path": "../public/_nuxt/Newsfeed.uYWt3LRm.css"
  },
  "/_nuxt/NuxnanAdminLayout.DHdswl2V.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"189-l3jjKZr65pi6ugy5Divc28iL+cU\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 393,
    "path": "../public/_nuxt/NuxnanAdminLayout.DHdswl2V.css"
  },
  "/_nuxt/oarP3__N.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1e5d-OyQNFPcncqVQPXkz4f7LFRsUfT8\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 7773,
    "path": "../public/_nuxt/oarP3__N.js"
  },
  "/_nuxt/oQXWb4Lk.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"29ae-OgOAnCEIWVt7r6IvfRGodaCnM8k\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 10670,
    "path": "../public/_nuxt/oQXWb4Lk.js"
  },
  "/_nuxt/Outfit-normal-300-latin-ext.DdQaqQDo.woff2": {
    "type": "font/woff2",
    "etag": "\"39d8-sqVel30br8w1wJ7fkoMuV0KPYjs\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 14808,
    "path": "../public/_nuxt/Outfit-normal-300-latin-ext.DdQaqQDo.woff2"
  },
  "/_nuxt/PollCard.CxcSlegH.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"2694-naIExW5wRnuPzUUrFbeWz/ttdDg\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 9876,
    "path": "../public/_nuxt/PollCard.CxcSlegH.css"
  },
  "/_nuxt/Outfit-normal-300-latin.Bc-8i84L.woff2": {
    "type": "font/woff2",
    "etag": "\"7e24-2KMW98v6RuaYdskl5Y+/e3wavw0\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 32292,
    "path": "../public/_nuxt/Outfit-normal-300-latin.Bc-8i84L.woff2"
  },
  "/_nuxt/pQly3-lN.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"867-COh81x2KlRWtXLTINQJ6pdQhfcc\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2151,
    "path": "../public/_nuxt/pQly3-lN.js"
  },
  "/_nuxt/ProfileCompletionWidget.Dud5x806.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"52d-+F7Gt2toaQAKvp1NhzDOq+Pjdpg\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 1325,
    "path": "../public/_nuxt/ProfileCompletionWidget.Dud5x806.css"
  },
  "/_nuxt/Prompt-normal-300-latin-ext.BveSF7za.woff2": {
    "type": "font/woff2",
    "etag": "\"4418-MAW35WaQOcMXdtvxQTxOeHv5was\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 17432,
    "path": "../public/_nuxt/Prompt-normal-300-latin-ext.BveSF7za.woff2"
  },
  "/_nuxt/Prompt-normal-300-latin.CW7rmI5T.woff2": {
    "type": "font/woff2",
    "etag": "\"4478-TQDzdaFU3NuDcHWrDk7VkSnsZaU\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 17528,
    "path": "../public/_nuxt/Prompt-normal-300-latin.CW7rmI5T.woff2"
  },
  "/_nuxt/Prompt-normal-300-thai.qs9oCq2b.woff2": {
    "type": "font/woff2",
    "etag": "\"30b4-wVpTGBtAd5UGRMkpJJ/o94CG1FY\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 12468,
    "path": "../public/_nuxt/Prompt-normal-300-thai.qs9oCq2b.woff2"
  },
  "/_nuxt/Prompt-normal-300-vietnamese.CzHbdZ_C.woff2": {
    "type": "font/woff2",
    "etag": "\"2430-pNMG750r3MUeYuSMBG9jOzDox7I\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 9264,
    "path": "../public/_nuxt/Prompt-normal-300-vietnamese.CzHbdZ_C.woff2"
  },
  "/_nuxt/Prompt-normal-400-latin-ext.DdSafGZ9.woff2": {
    "type": "font/woff2",
    "etag": "\"46a0-lSmnVyKAey2OaS3fkKORM6mcE1A\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 18080,
    "path": "../public/_nuxt/Prompt-normal-400-latin-ext.DdSafGZ9.woff2"
  },
  "/_nuxt/Prompt-normal-400-latin.BQ9zjSN8.woff2": {
    "type": "font/woff2",
    "etag": "\"4614-E9rcfFsUDeh0i8kgNXO5OTFFESY\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 17940,
    "path": "../public/_nuxt/Prompt-normal-400-latin.BQ9zjSN8.woff2"
  },
  "/_nuxt/Prompt-normal-400-thai.BrkKv8cO.woff2": {
    "type": "font/woff2",
    "etag": "\"3388-SBrYECIzaCoyC2Bq2RrMAW++a+k\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 13192,
    "path": "../public/_nuxt/Prompt-normal-400-thai.BrkKv8cO.woff2"
  },
  "/_nuxt/Prompt-normal-400-vietnamese.BCPzsgPT.woff2": {
    "type": "font/woff2",
    "etag": "\"2514-03DjppxLQwFpaYKB+p3dzv0CLPo\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 9492,
    "path": "../public/_nuxt/Prompt-normal-400-vietnamese.BCPzsgPT.woff2"
  },
  "/_nuxt/Prompt-normal-500-latin-ext.-EZ1um7s.woff2": {
    "type": "font/woff2",
    "etag": "\"4868-0PoKNiiyBD82nSUkdmQITih74dQ\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 18536,
    "path": "../public/_nuxt/Prompt-normal-500-latin-ext.-EZ1um7s.woff2"
  },
  "/_nuxt/Prompt-normal-500-latin.CxzxEHZc.woff2": {
    "type": "font/woff2",
    "etag": "\"46b0-uWMZeBARYcmpJb0U5tzuQNTlxpk\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 18096,
    "path": "../public/_nuxt/Prompt-normal-500-latin.CxzxEHZc.woff2"
  },
  "/_nuxt/Prompt-normal-500-thai.C18pDUoL.woff2": {
    "type": "font/woff2",
    "etag": "\"327c-NOQpl5/4wrhwr4Nv6ydnu8QYUyQ\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 12924,
    "path": "../public/_nuxt/Prompt-normal-500-thai.C18pDUoL.woff2"
  },
  "/_nuxt/Prompt-normal-500-vietnamese.DmzxmPwa.woff2": {
    "type": "font/woff2",
    "etag": "\"27cc-pOpsaCYxuALmOtvDJ5nEH7Z7DxQ\"",
    "mtime": "2026-01-28T08:24:20.707Z",
    "size": 10188,
    "path": "../public/_nuxt/Prompt-normal-500-vietnamese.DmzxmPwa.woff2"
  },
  "/_nuxt/Prompt-normal-600-latin-ext.Cg9L7iJU.woff2": {
    "type": "font/woff2",
    "etag": "\"46a8-pR+Q+bJkU6KybmrcIi8SzBUeBes\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 18088,
    "path": "../public/_nuxt/Prompt-normal-600-latin-ext.Cg9L7iJU.woff2"
  },
  "/_nuxt/Prompt-normal-600-latin.hKZWXsc1.woff2": {
    "type": "font/woff2",
    "etag": "\"46b8-HLsRB3NMlfBTLzPzTwNtDRqHlnw\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 18104,
    "path": "../public/_nuxt/Prompt-normal-600-latin.hKZWXsc1.woff2"
  },
  "/_nuxt/Prompt-normal-600-thai.MrdfU7zR.woff2": {
    "type": "font/woff2",
    "etag": "\"32f4-opD0DXjdm7MF9OB1J+/OtUUQ+5Q\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 13044,
    "path": "../public/_nuxt/Prompt-normal-600-thai.MrdfU7zR.woff2"
  },
  "/_nuxt/Prompt-normal-600-vietnamese.7QWjJBsF.woff2": {
    "type": "font/woff2",
    "etag": "\"263c-yYCn34QmC4vRd0jSbrp3QysRyWo\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 9788,
    "path": "../public/_nuxt/Prompt-normal-600-vietnamese.7QWjJBsF.woff2"
  },
  "/_nuxt/Prompt-normal-700-latin-ext.BkJrvM1L.woff2": {
    "type": "font/woff2",
    "etag": "\"48f8-/Zp/zWi4cK2z6MMu2eIViT9Yex0\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 18680,
    "path": "../public/_nuxt/Prompt-normal-700-latin-ext.BkJrvM1L.woff2"
  },
  "/_nuxt/Prompt-normal-700-latin.I2gc831J.woff2": {
    "type": "font/woff2",
    "etag": "\"46f0-BcH+cwKZUkufvV16Lk5d20OX+hI\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 18160,
    "path": "../public/_nuxt/Prompt-normal-700-latin.I2gc831J.woff2"
  },
  "/_nuxt/Prompt-normal-700-thai.Cg4aQ0Nn.woff2": {
    "type": "font/woff2",
    "etag": "\"3398-9MZ7y374tSlND6pgQm7trn/6n4g\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 13208,
    "path": "../public/_nuxt/Prompt-normal-700-thai.Cg4aQ0Nn.woff2"
  },
  "/_nuxt/Prompt-normal-700-vietnamese.CGnCqMm1.woff2": {
    "type": "font/woff2",
    "etag": "\"28d8-V6KBm/TFleVl1rXprJAhboNPI9Q\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 10456,
    "path": "../public/_nuxt/Prompt-normal-700-vietnamese.CGnCqMm1.woff2"
  },
  "/_nuxt/QFAnqdeY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1972-5qPI3SZQT7uh2p3nLs3DWbTjixU\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6514,
    "path": "../public/_nuxt/QFAnqdeY.js"
  },
  "/_nuxt/qsESkgMS.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"16d4-G5/ts4tpjp4J6pqc8gXUMMJ8V+M\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 5844,
    "path": "../public/_nuxt/qsESkgMS.js"
  },
  "/_nuxt/rBtuVj2O.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"db-i945wrZenbetCugGELwAoxERjEM\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 219,
    "path": "../public/_nuxt/rBtuVj2O.js"
  },
  "/_nuxt/redeem.CoC2ZMgO.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"cb-dqs7YMSZFVQFASAQggCBGmlKqEM\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 203,
    "path": "../public/_nuxt/redeem.CoC2ZMgO.css"
  },
  "/_nuxt/redeem.CQjqiUcq.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1713-gkQl/acainP584fL3vbPDW5XGkk\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 5907,
    "path": "../public/_nuxt/redeem.CQjqiUcq.css"
  },
  "/_nuxt/Rewards.BBxa6-Hm.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"14b-BYS+xpDj3YKyxZGC8kP+3MpbsgM\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 331,
    "path": "../public/_nuxt/Rewards.BBxa6-Hm.css"
  },
  "/_nuxt/RichTextEditor.D1oS4L28.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"ef2-yDLw5LYvAL2Gk5S2WB64j1M7rx0\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 3826,
    "path": "../public/_nuxt/RichTextEditor.D1oS4L28.css"
  },
  "/_nuxt/RichTextEditor.Dc0jKbuw.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"d20-guh/yRaGLRDIRhVAurRAiN3CVVg\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 3360,
    "path": "../public/_nuxt/RichTextEditor.Dc0jKbuw.css"
  },
  "/_nuxt/RichTextViewer.BjqO0CiT.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"10b5-9i0enOBgML+RrHrKBYyG29Nx5vU\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 4277,
    "path": "../public/_nuxt/RichTextViewer.BjqO0CiT.css"
  },
  "/_nuxt/rPLS4Pv1.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3db-0ORB9tzG/8jJ8jk5AtHupdcVvVI\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 987,
    "path": "../public/_nuxt/rPLS4Pv1.js"
  },
  "/_nuxt/rqDEXRq_.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c39-hfz9WZasS6ZopjMyT12MhrAP+kU\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 7225,
    "path": "../public/_nuxt/rqDEXRq_.js"
  },
  "/_nuxt/S8b4GVHC.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"23ec-qvXBy0s1YmNZltQAea4FaX0pI4w\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 9196,
    "path": "../public/_nuxt/S8b4GVHC.js"
  },
  "/_nuxt/settings.1cN4HZeo.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"c1-KaT/hBONnuFZCYCnOUpOZJHxe98\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 193,
    "path": "../public/_nuxt/settings.1cN4HZeo.css"
  },
  "/_nuxt/settings.26iA_fQ8.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"b1-RB8LEpjOrj4OgRQQWuLa9U78uaM\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 177,
    "path": "../public/_nuxt/settings.26iA_fQ8.css"
  },
  "/_nuxt/SKyabeFL.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1888-luH4entm0hbCC6EYpUYyHF6lSaE\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6280,
    "path": "../public/_nuxt/SKyabeFL.js"
  },
  "/_nuxt/SQSOXrpR.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"fa1-dYbvgFVQ59CoZo9DhQiD0UOl6Vw\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 4001,
    "path": "../public/_nuxt/SQSOXrpR.js"
  },
  "/_nuxt/StudentCard.CYXswqba.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"ff-0KMClpWyq18JBZUQWqsuTGlpSkU\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 255,
    "path": "../public/_nuxt/StudentCard.CYXswqba.css"
  },
  "/_nuxt/StudentCard.XByKYVBC.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"120-gg2D/5FM5J2SSEG+oahS7woksho\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 288,
    "path": "../public/_nuxt/StudentCard.XByKYVBC.css"
  },
  "/_nuxt/StudentCardBackSide.CN_k3CZN.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"9f-EIhwGrmQC/3RwFzUaKeNLIm3eqc\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 159,
    "path": "../public/_nuxt/StudentCardBackSide.CN_k3CZN.css"
  },
  "/_nuxt/StudentCardByRoom.pp6IEKnx.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"106-07nUQZtFXyIJCzSuQ8CzK4TTSGo\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 262,
    "path": "../public/_nuxt/StudentCardByRoom.pp6IEKnx.css"
  },
  "/_nuxt/StudentCardIndex.D3yBDqSC.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"ab-jpjnuHhlwYkiwu2JPt6cobGN2zw\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 171,
    "path": "../public/_nuxt/StudentCardIndex.D3yBDqSC.css"
  },
  "/_nuxt/StudentCardIndex.Dl0QIawZ.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"ab-0qDd8DnV4t3t3O+2NCAkczkPycc\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 171,
    "path": "../public/_nuxt/StudentCardIndex.Dl0QIawZ.css"
  },
  "/_nuxt/StudentsCard.CXYAlG0R.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"12b4-WE2/cCtHVZPAeABmYkKcOijA3/A\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 4788,
    "path": "../public/_nuxt/StudentsCard.CXYAlG0R.css"
  },
  "/_nuxt/SXA2SCtA.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1307-Vd2MgqlC5tJpEfg/yfIpUfqrg9Y\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 4871,
    "path": "../public/_nuxt/SXA2SCtA.js"
  },
  "/_nuxt/T6jXon-w.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1b1-/WA8A4HsTM0YcLYx6z5Dz8jc590\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 433,
    "path": "../public/_nuxt/T6jXon-w.js"
  },
  "/_nuxt/Tp35NlG9.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3497-xU939RCH3n1IMaQgphhkATQ0bcA\"",
    "mtime": "2026-01-28T08:24:20.718Z",
    "size": 13463,
    "path": "../public/_nuxt/Tp35NlG9.js"
  },
  "/_nuxt/VisitDetailModal.Dor45vhp.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"5e-Htwjk8Gc667TR+sLZFO5zBwXCFQ\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 94,
    "path": "../public/_nuxt/VisitDetailModal.Dor45vhp.css"
  },
  "/_nuxt/unovbEJl.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"122c7-UjOZuRwhucU6hnOiZqcOH4yGqds\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 74439,
    "path": "../public/_nuxt/unovbEJl.js"
  },
  "/_nuxt/v5NuDMBR.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"65ae-3+Iqm0j25ZyQa4VIDEvZP/8Si9U\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 26030,
    "path": "../public/_nuxt/v5NuDMBR.js"
  },
  "/_nuxt/VisitFeed._DK63QR0.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"a67-JezfkG2pGxVR3yG5f21YXBUkbCc\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 2663,
    "path": "../public/_nuxt/VisitFeed._DK63QR0.css"
  },
  "/_nuxt/VisitReports.Cjogy_t-.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"80-TMZidlWhNenTWnfqr6fb6rlN2GA\"",
    "mtime": "2026-01-28T08:24:20.709Z",
    "size": 128,
    "path": "../public/_nuxt/VisitReports.Cjogy_t-.css"
  },
  "/_nuxt/welcome.BxSARH-6.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"649-v8VgAMBviRwU9SYWXGQBn1R8aTI\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 1609,
    "path": "../public/_nuxt/welcome.BxSARH-6.css"
  },
  "/_nuxt/wsGRNYl8.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1a80-pDgDL3KMTAIvz5zoKCc+CHbNthc\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6784,
    "path": "../public/_nuxt/wsGRNYl8.js"
  },
  "/_nuxt/wY2F8avY.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"2b10-cjU1jb4IYkS0kjwklATrqBSfpx0\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 11024,
    "path": "../public/_nuxt/wY2F8avY.js"
  },
  "/_nuxt/XKbuI7Cp.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"10dd-PnX7z77rfQ38m1oTDI7kJ5xlIRM\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 4317,
    "path": "../public/_nuxt/XKbuI7Cp.js"
  },
  "/_nuxt/XMgg9z7A.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1c3-kl7+LNRe3jgcVPN4OqnPIh2KOqg\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 451,
    "path": "../public/_nuxt/XMgg9z7A.js"
  },
  "/_nuxt/xo-game.C7-c0anC.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"c0-C86f/6W47Vlqxx6Yqyt/sloe95o\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 192,
    "path": "../public/_nuxt/xo-game.C7-c0anC.css"
  },
  "/_nuxt/XQ8mRelX.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"3118-DPyFN0V+wlH4SZVpiWj1F+Yxkl0\"",
    "mtime": "2026-01-28T08:24:20.717Z",
    "size": 12568,
    "path": "../public/_nuxt/XQ8mRelX.js"
  },
  "/_nuxt/Y1_acXss.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"1972-UxCNacnc/EfviX5TT1ZQxhOKf7k\"",
    "mtime": "2026-01-28T08:24:20.716Z",
    "size": 6514,
    "path": "../public/_nuxt/Y1_acXss.js"
  },
  "/_nuxt/Z-f9zonx.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"179ed-6v8SYP1K1dr0bSE1LlpBv0ObULU\"",
    "mtime": "2026-01-28T08:24:20.719Z",
    "size": 96749,
    "path": "../public/_nuxt/Z-f9zonx.js"
  },
  "/_nuxt/zceechAA.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"db5-wxNZTMulYH3HOnR+pYGNB5EtcUU\"",
    "mtime": "2026-01-28T08:24:20.714Z",
    "size": 3509,
    "path": "../public/_nuxt/zceechAA.js"
  },
  "/_nuxt/ZkgpDsk4.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"23f4-uq/AjDNjNdBqV0SHuAZ0DSKQHrk\"",
    "mtime": "2026-01-28T08:24:20.720Z",
    "size": 9204,
    "path": "../public/_nuxt/ZkgpDsk4.js"
  },
  "/_nuxt/zMSPCIxn.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"fba-lFGSWoBLglOsdOJyonWl2CbnCiA\"",
    "mtime": "2026-01-28T08:24:20.715Z",
    "size": 4026,
    "path": "../public/_nuxt/zMSPCIxn.js"
  },
  "/_nuxt/ZoneManagement.DMxZ9I-W.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"b5-+/VUO8f3CVWHMXkqwjwrXT7oBNU\"",
    "mtime": "2026-01-28T08:24:20.708Z",
    "size": 181,
    "path": "../public/_nuxt/ZoneManagement.DMxZ9I-W.css"
  },
  "/_nuxt/zWxPcX7x.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"7e5-6u0PYUl34yhLJO9V3ymnH36kApw\"",
    "mtime": "2026-01-28T08:24:20.713Z",
    "size": 2021,
    "path": "../public/_nuxt/zWxPcX7x.js"
  },
  "/_nuxt/_id_.9Tdb1n5e.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"12f1-1CHui9c+/+odxy87SRqvc9O3viw\"",
    "mtime": "2026-01-28T08:24:20.712Z",
    "size": 4849,
    "path": "../public/_nuxt/_id_.9Tdb1n5e.css"
  },
  "/_nuxt/_id_.Vrt9qx_p.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"1c5-tG7xNhD92C+H9t96ZptZH5oRcos\"",
    "mtime": "2026-01-28T08:24:20.711Z",
    "size": 453,
    "path": "../public/_nuxt/_id_.Vrt9qx_p.css"
  },
  "/_nuxt/_Rdlmmur.js": {
    "type": "text/javascript; charset=utf-8",
    "etag": "\"97704-tqnGDRNN9VPG3LUg4DG0AH4WX6M\"",
    "mtime": "2026-01-28T08:24:20.750Z",
    "size": 620292,
    "path": "../public/_nuxt/_Rdlmmur.js"
  },
  "/fonts/fonts/LineIcons.eot": {
    "type": "application/vnd.ms-fontobject",
    "etag": "\"1e720-Brb1T0ZfvKRHGy2/+2hJhv0INBo\"",
    "mtime": "2026-01-27T02:33:46.666Z",
    "size": 124704,
    "path": "../public/fonts/fonts/LineIcons.eot"
  },
  "/fonts/fonts/LineIcons.ttf": {
    "type": "font/ttf",
    "etag": "\"1e674-ILvG5IDfYhAHeMZsdCtnFZQBfzE\"",
    "mtime": "2026-01-27T02:33:46.672Z",
    "size": 124532,
    "path": "../public/fonts/fonts/LineIcons.ttf"
  },
  "/fonts/fonts/LineIcons.woff": {
    "type": "font/woff",
    "etag": "\"fcdc-Ir+U2Xeba5iDwQARUNGH2hwz/fU\"",
    "mtime": "2026-01-27T02:33:46.673Z",
    "size": 64732,
    "path": "../public/fonts/fonts/LineIcons.woff"
  },
  "/fonts/fonts/LineIcons.woff2": {
    "type": "font/woff2",
    "etag": "\"c9dc-CZyFUt3Cz7BRWTM1d8GCHSxtSRk\"",
    "mtime": "2026-01-27T02:33:46.673Z",
    "size": 51676,
    "path": "../public/fonts/fonts/LineIcons.woff2"
  },
  "/images/flag/argentina.png": {
    "type": "image/png",
    "etag": "\"a74-CGKLY74yfF59fpYs/JoLuEzrbCs\"",
    "mtime": "2026-01-27T02:33:46.680Z",
    "size": 2676,
    "path": "../public/images/flag/argentina.png"
  },
  "/images/flag/brazil.png": {
    "type": "image/png",
    "etag": "\"1c0a-XrlXmZrKhzQkN2IjwQKCSPyza8k\"",
    "mtime": "2026-01-27T02:33:46.680Z",
    "size": 7178,
    "path": "../public/images/flag/brazil.png"
  },
  "/images/flag/canada.png": {
    "type": "image/png",
    "etag": "\"d45-8jhgyPS1y8VXvfvPCj5qVc2FBto\"",
    "mtime": "2026-01-27T02:33:46.680Z",
    "size": 3397,
    "path": "../public/images/flag/canada.png"
  },
  "/images/flag/france.png": {
    "type": "image/png",
    "etag": "\"7bc-hKeMDGtBrdW3hye/4C/Kc5jIxXU\"",
    "mtime": "2026-01-27T02:33:46.680Z",
    "size": 1980,
    "path": "../public/images/flag/france.png"
  },
  "/images/flag/germany.png": {
    "type": "image/png",
    "etag": "\"767-oPDExvnAEc/U/ImstoAQAR52i8w\"",
    "mtime": "2026-01-27T02:33:46.681Z",
    "size": 1895,
    "path": "../public/images/flag/germany.png"
  },
  "/images/flag/india.png": {
    "type": "image/png",
    "etag": "\"bb7-aTVfF06XbN20fpcpGQaMkc7q92M\"",
    "mtime": "2026-01-27T02:33:46.681Z",
    "size": 2999,
    "path": "../public/images/flag/india.png"
  },
  "/images/flag/map.png": {
    "type": "image/png",
    "etag": "\"19898-bVO//si03Xy/2Ie/aXS8fGsh/cQ\"",
    "mtime": "2026-01-27T02:33:46.682Z",
    "size": 104600,
    "path": "../public/images/flag/map.png"
  },
  "/images/flag/russia.png": {
    "type": "image/png",
    "etag": "\"6f6-Rrr8w9mRUxWSNd+QYWKVRETPvH0\"",
    "mtime": "2026-01-27T02:33:46.683Z",
    "size": 1782,
    "path": "../public/images/flag/russia.png"
  },
  "/images/flag/thai.png": {
    "type": "image/png",
    "etag": "\"a29-sxYNyKJg09LxdKC3x0TiZLf7gLE\"",
    "mtime": "2026-01-27T02:33:46.683Z",
    "size": 2601,
    "path": "../public/images/flag/thai.png"
  },
  "/images/flag/Thumbs.db": {
    "type": "text/plain; charset=utf-8",
    "etag": "\"8a00-ab561Y/mVX3+UcepQh7E0hmp8cY\"",
    "mtime": "2026-01-27T02:33:46.679Z",
    "size": 35328,
    "path": "../public/images/flag/Thumbs.db"
  },
  "/images/flag/turkey.png": {
    "type": "image/png",
    "etag": "\"1190-cwpa4T5rdrWjObsfY8dFzddcUWI\"",
    "mtime": "2026-01-27T02:33:46.684Z",
    "size": 4496,
    "path": "../public/images/flag/turkey.png"
  },
  "/images/flag/usa.png": {
    "type": "image/png",
    "etag": "\"1917-OJ6gAf7663jCuX41iEs/va73O4k\"",
    "mtime": "2026-01-27T02:33:46.684Z",
    "size": 6423,
    "path": "../public/images/flag/usa.png"
  },
  "/images/images/logo-white.png": {
    "type": "image/png",
    "etag": "\"109c-oP9sFBaRLMa9ARCH5aQU/+pva0c\"",
    "mtime": "2026-01-27T02:33:46.688Z",
    "size": 4252,
    "path": "../public/images/images/logo-white.png"
  },
  "/images/images/logo.png": {
    "type": "image/png",
    "etag": "\"74f-0qVuZXwFO3fKHUAjXz4cB20UI9c\"",
    "mtime": "2026-01-27T02:33:46.688Z",
    "size": 1871,
    "path": "../public/images/images/logo.png"
  },
  "/fonts/fonts/LineIcons.svg": {
    "type": "image/svg+xml",
    "etag": "\"95511-EGNsuNrb6mE+x072/A24G+bbra4\"",
    "mtime": "2026-01-27T02:33:46.670Z",
    "size": 611601,
    "path": "../public/fonts/fonts/LineIcons.svg"
  },
  "/images/images/map-marker.png": {
    "type": "image/png",
    "etag": "\"87f-YRrqkSDr/Y9nfLEIqqcxBV6JnnQ\"",
    "mtime": "2026-01-27T02:33:46.688Z",
    "size": 2175,
    "path": "../public/images/images/map-marker.png"
  },
  "/images/patterns/circuit.svg": {
    "type": "image/svg+xml",
    "etag": "\"51c-5sVvf6DJcYkE72mB+tfs8qL5UyI\"",
    "mtime": "2026-01-27T02:33:46.766Z",
    "size": 1308,
    "path": "../public/images/patterns/circuit.svg"
  },
  "/images/patterns/hexagon-pattern.svg": {
    "type": "image/svg+xml",
    "etag": "\"366-xiR0NLrMh8L2qc/tNezbhkS9EQ4\"",
    "mtime": "2026-01-27T02:33:46.767Z",
    "size": 870,
    "path": "../public/images/patterns/hexagon-pattern.svg"
  },
  "/images/images/chat-bg.png": {
    "type": "image/png",
    "etag": "\"aa91d-HrQ3KPIspK36yrdgCeSLy0KOIYI\"",
    "mtime": "2026-01-27T02:33:46.687Z",
    "size": 698653,
    "path": "../public/images/images/chat-bg.png"
  },
  "/images/patterns/hexagon.svg": {
    "type": "image/svg+xml",
    "etag": "\"431-RTtfBcQMIQKLq9CJIyh4wicm+4s\"",
    "mtime": "2026-01-27T02:33:46.767Z",
    "size": 1073,
    "path": "../public/images/patterns/hexagon.svg"
  },
  "/images/resources/admin.jpg": {
    "type": "image/jpeg",
    "etag": "\"1f4-6No1N2mwTsQqiBTY2G+LkVzZ9gY\"",
    "mtime": "2026-01-27T02:33:46.768Z",
    "size": 500,
    "path": "../public/images/resources/admin.jpg"
  },
  "/images/resources/album1.jpg": {
    "type": "image/jpeg",
    "etag": "\"64e-q/p4vfk6DXHs64xC92mawpdms6w\"",
    "mtime": "2026-01-27T02:33:46.768Z",
    "size": 1614,
    "path": "../public/images/resources/album1.jpg"
  },
  "/images/resources/album2.jpg": {
    "type": "image/jpeg",
    "etag": "\"64e-q/p4vfk6DXHs64xC92mawpdms6w\"",
    "mtime": "2026-01-27T02:33:46.768Z",
    "size": 1614,
    "path": "../public/images/resources/album2.jpg"
  },
  "/images/resources/animate-bg.png": {
    "type": "image/png",
    "etag": "\"159c-t0IrwsYPeLOUE7wA1NKFFDowzbw\"",
    "mtime": "2026-01-27T02:33:46.768Z",
    "size": 5532,
    "path": "../public/images/resources/animate-bg.png"
  },
  "/images/resources/bg-image.jpg": {
    "type": "image/jpeg",
    "etag": "\"791d-VBL7OmF32KT5XOOxSnMkDYI14Hw\"",
    "mtime": "2026-01-27T02:33:46.769Z",
    "size": 31005,
    "path": "../public/images/resources/bg-image.jpg"
  },
  "/images/resources/black-thsirt.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.769Z",
    "size": 7605,
    "path": "../public/images/resources/black-thsirt.jpg"
  },
  "/images/resources/blog1.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.769Z",
    "size": 1391,
    "path": "../public/images/resources/blog1.jpg"
  },
  "/images/resources/blog2.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.769Z",
    "size": 1391,
    "path": "../public/images/resources/blog2.jpg"
  },
  "/images/resources/blog3.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.770Z",
    "size": 1391,
    "path": "../public/images/resources/blog3.jpg"
  },
  "/images/resources/blog4.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.770Z",
    "size": 1391,
    "path": "../public/images/resources/blog4.jpg"
  },
  "/images/resources/blog5.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.770Z",
    "size": 1391,
    "path": "../public/images/resources/blog5.jpg"
  },
  "/images/resources/blog6.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.770Z",
    "size": 1391,
    "path": "../public/images/resources/blog6.jpg"
  },
  "/images/resources/blue-thsirt.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.771Z",
    "size": 7605,
    "path": "../public/images/resources/blue-thsirt.jpg"
  },
  "/images/resources/camera.png": {
    "type": "image/png",
    "etag": "\"1426-0homP71MkXK+dBxpjrvnZu8UcG8\"",
    "mtime": "2026-01-27T02:33:46.771Z",
    "size": 5158,
    "path": "../public/images/resources/camera.png"
  },
  "/images/resources/degree-holder.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.771Z",
    "size": 807,
    "path": "../public/images/resources/degree-holder.jpg"
  },
  "/images/resources/event-small.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.772Z",
    "size": 11372,
    "path": "../public/images/resources/event-small.jpg"
  },
  "/images/resources/event-small2.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.772Z",
    "size": 11372,
    "path": "../public/images/resources/event-small2.jpg"
  },
  "/images/resources/event.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.772Z",
    "size": 11372,
    "path": "../public/images/resources/event.jpg"
  },
  "/images/resources/feature-product1.jpg": {
    "type": "image/jpeg",
    "etag": "\"6f36-xIMYYFD0yYcxuernsNcJqo+aSx8\"",
    "mtime": "2026-01-27T02:33:46.773Z",
    "size": 28470,
    "path": "../public/images/resources/feature-product1.jpg"
  },
  "/images/resources/feature-product2.jpg": {
    "type": "image/jpeg",
    "etag": "\"6f36-xIMYYFD0yYcxuernsNcJqo+aSx8\"",
    "mtime": "2026-01-27T02:33:46.773Z",
    "size": 28470,
    "path": "../public/images/resources/feature-product2.jpg"
  },
  "/images/resources/feature-product3.jpg": {
    "type": "image/jpeg",
    "etag": "\"6f36-xIMYYFD0yYcxuernsNcJqo+aSx8\"",
    "mtime": "2026-01-27T02:33:46.774Z",
    "size": 28470,
    "path": "../public/images/resources/feature-product3.jpg"
  },
  "/images/resources/good-noon.png": {
    "type": "image/png",
    "etag": "\"19d-uNWqbtxlEsoKziogoQ0ug3thHhs\"",
    "mtime": "2026-01-27T02:33:46.774Z",
    "size": 413,
    "path": "../public/images/resources/good-noon.png"
  },
  "/images/resources/group1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.774Z",
    "size": 5249,
    "path": "../public/images/resources/group1.jpg"
  },
  "/images/resources/group10.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.775Z",
    "size": 525,
    "path": "../public/images/resources/group10.jpg"
  },
  "/images/resources/group11.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.775Z",
    "size": 525,
    "path": "../public/images/resources/group11.jpg"
  },
  "/images/resources/group12.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.775Z",
    "size": 525,
    "path": "../public/images/resources/group12.jpg"
  },
  "/images/resources/group2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.776Z",
    "size": 5249,
    "path": "../public/images/resources/group2.jpg"
  },
  "/images/resources/group3.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.776Z",
    "size": 5249,
    "path": "../public/images/resources/group3.jpg"
  },
  "/images/resources/group4.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.776Z",
    "size": 5249,
    "path": "../public/images/resources/group4.jpg"
  },
  "/images/resources/group5.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.777Z",
    "size": 5249,
    "path": "../public/images/resources/group5.jpg"
  },
  "/images/resources/group6.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.777Z",
    "size": 5249,
    "path": "../public/images/resources/group6.jpg"
  },
  "/images/resources/group7.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.777Z",
    "size": 525,
    "path": "../public/images/resources/group7.jpg"
  },
  "/images/resources/group8.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.778Z",
    "size": 525,
    "path": "../public/images/resources/group8.jpg"
  },
  "/images/resources/group9.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.778Z",
    "size": 525,
    "path": "../public/images/resources/group9.jpg"
  },
  "/images/resources/img-1.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.778Z",
    "size": 11372,
    "path": "../public/images/resources/img-1.jpg"
  },
  "/images/resources/img-2.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.779Z",
    "size": 11372,
    "path": "../public/images/resources/img-2.jpg"
  },
  "/images/resources/location.jpg": {
    "type": "image/jpeg",
    "etag": "\"39d7-2n491HDTGYoJiquidlAZRgfqNEM\"",
    "mtime": "2026-01-27T02:33:46.779Z",
    "size": 14807,
    "path": "../public/images/resources/location.jpg"
  },
  "/images/resources/location.png": {
    "type": "image/png",
    "etag": "\"1426-tGzxJK1i/cdQNvZ2Lc2upoii7Xc\"",
    "mtime": "2026-01-27T02:33:46.780Z",
    "size": 5158,
    "path": "../public/images/resources/location.png"
  },
  "/images/resources/money-card.png": {
    "type": "image/png",
    "etag": "\"2c6e-4CF1/OuYWjp6LAKUXpH7wWAnZqQ\"",
    "mtime": "2026-01-27T02:33:46.801Z",
    "size": 11374,
    "path": "../public/images/resources/money-card.png"
  },
  "/images/resources/order-success.jpg": {
    "type": "image/jpeg",
    "etag": "\"845-Ece2U0pPLUmNtVVbEbTCRavqh0o\"",
    "mtime": "2026-01-27T02:33:46.801Z",
    "size": 2117,
    "path": "../public/images/resources/order-success.jpg"
  },
  "/images/resources/page=profile.jpg": {
    "type": "image/jpeg",
    "etag": "\"28f4-eedhEzmIDzAAK/GXN8SfxDsDU7Q\"",
    "mtime": "2026-01-27T02:33:46.802Z",
    "size": 10484,
    "path": "../public/images/resources/page=profile.jpg"
  },
  "/images/resources/party1.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.802Z",
    "size": 2106,
    "path": "../public/images/resources/party1.jpg"
  },
  "/images/resources/party2.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.802Z",
    "size": 2106,
    "path": "../public/images/resources/party2.jpg"
  },
  "/images/resources/party3.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.803Z",
    "size": 2106,
    "path": "../public/images/resources/party3.jpg"
  },
  "/images/resources/party4.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.803Z",
    "size": 2106,
    "path": "../public/images/resources/party4.jpg"
  },
  "/images/resources/party5.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.803Z",
    "size": 2106,
    "path": "../public/images/resources/party5.jpg"
  },
  "/images/resources/party6.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.804Z",
    "size": 2106,
    "path": "../public/images/resources/party6.jpg"
  },
  "/images/resources/photo-big.jpg": {
    "type": "image/jpeg",
    "etag": "\"d13-ACGfsKIxT42grgr7WBCK9aY7Ue0\"",
    "mtime": "2026-01-27T02:33:46.804Z",
    "size": 3347,
    "path": "../public/images/resources/photo-big.jpg"
  },
  "/images/resources/picture-pair1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1fd2-KJAWQybIUdRilQKi/jKzcXuwh0g\"",
    "mtime": "2026-01-27T02:33:46.804Z",
    "size": 8146,
    "path": "../public/images/resources/picture-pair1.jpg"
  },
  "/images/resources/picture-pair2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.805Z",
    "size": 7605,
    "path": "../public/images/resources/picture-pair2.jpg"
  },
  "/images/resources/picture-pair3.jpg": {
    "type": "image/jpeg",
    "etag": "\"e0d-XI3SKWM2DDPJbC40/W5b9ZKLQnQ\"",
    "mtime": "2026-01-27T02:33:46.805Z",
    "size": 3597,
    "path": "../public/images/resources/picture-pair3.jpg"
  },
  "/images/resources/picture-pair4.jpg": {
    "type": "image/jpeg",
    "etag": "\"216e-N3TabsYAKDBtSrTIlEWTO9BjUQY\"",
    "mtime": "2026-01-27T02:33:46.805Z",
    "size": 8558,
    "path": "../public/images/resources/picture-pair4.jpg"
  },
  "/images/resources/picture-pair5.jpg": {
    "type": "image/jpeg",
    "etag": "\"b36-VAlojEwTimeGa9n8fpCWSc4vrLc\"",
    "mtime": "2026-01-27T02:33:46.806Z",
    "size": 2870,
    "path": "../public/images/resources/picture-pair5.jpg"
  },
  "/images/resources/picture-pair6.jpg": {
    "type": "image/jpeg",
    "etag": "\"a25-VDfVmhqQMI4zCVPI6mT7onw4usQ\"",
    "mtime": "2026-01-27T02:33:46.806Z",
    "size": 2597,
    "path": "../public/images/resources/picture-pair6.jpg"
  },
  "/images/resources/picture-pair7.jpg": {
    "type": "image/jpeg",
    "etag": "\"def-v6+nNIgRumVQL/ElISPzHinR2CQ\"",
    "mtime": "2026-01-27T02:33:46.807Z",
    "size": 3567,
    "path": "../public/images/resources/picture-pair7.jpg"
  },
  "/images/resources/picture-pair8.jpg": {
    "type": "image/jpeg",
    "etag": "\"237a-+lkqov+pA4ymOKv9L80m03QE1kM\"",
    "mtime": "2026-01-27T02:33:46.807Z",
    "size": 9082,
    "path": "../public/images/resources/picture-pair8.jpg"
  },
  "/images/resources/picture1.jpg": {
    "type": "image/jpeg",
    "etag": "\"451-b6C76cnH0YtHffT8C6ka1qNdSCs\"",
    "mtime": "2026-01-27T02:33:46.807Z",
    "size": 1105,
    "path": "../public/images/resources/picture1.jpg"
  },
  "/images/resources/picture2.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.808Z",
    "size": 807,
    "path": "../public/images/resources/picture2.jpg"
  },
  "/images/resources/picture3.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.808Z",
    "size": 807,
    "path": "../public/images/resources/picture3.jpg"
  },
  "/images/resources/picture4.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.808Z",
    "size": 1591,
    "path": "../public/images/resources/picture4.jpg"
  },
  "/images/resources/picture5.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.808Z",
    "size": 1591,
    "path": "../public/images/resources/picture5.jpg"
  },
  "/images/resources/picture6.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.809Z",
    "size": 807,
    "path": "../public/images/resources/picture6.jpg"
  },
  "/images/resources/picture7.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.809Z",
    "size": 807,
    "path": "../public/images/resources/picture7.jpg"
  },
  "/images/resources/picture8.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.809Z",
    "size": 807,
    "path": "../public/images/resources/picture8.jpg"
  },
  "/images/resources/place1.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.809Z",
    "size": 3788,
    "path": "../public/images/resources/place1.jpg"
  },
  "/images/resources/place2.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.810Z",
    "size": 3788,
    "path": "../public/images/resources/place2.jpg"
  },
  "/images/resources/place3.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.810Z",
    "size": 3788,
    "path": "../public/images/resources/place3.jpg"
  },
  "/images/resources/place4.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.810Z",
    "size": 3788,
    "path": "../public/images/resources/place4.jpg"
  },
  "/images/resources/place5.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.811Z",
    "size": 3788,
    "path": "../public/images/resources/place5.jpg"
  },
  "/images/resources/place6.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.811Z",
    "size": 3788,
    "path": "../public/images/resources/place6.jpg"
  },
  "/images/resources/prod-cat1.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.812Z",
    "size": 367,
    "path": "../public/images/resources/prod-cat1.jpg"
  },
  "/images/resources/prod-cat2.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.812Z",
    "size": 367,
    "path": "../public/images/resources/prod-cat2.jpg"
  },
  "/images/resources/prod-cat3.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.812Z",
    "size": 367,
    "path": "../public/images/resources/prod-cat3.jpg"
  },
  "/images/resources/login-bg2.jpg": {
    "type": "image/jpeg",
    "etag": "\"32f40b-oScTeJ+D+a01HVgpHL35THPzo4E\"",
    "mtime": "2026-01-27T02:33:46.801Z",
    "size": 3339275,
    "path": "../public/images/resources/login-bg2.jpg"
  },
  "/images/resources/prod-cat4.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.813Z",
    "size": 367,
    "path": "../public/images/resources/prod-cat4.jpg"
  },
  "/images/resources/prod-cat5.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.813Z",
    "size": 367,
    "path": "../public/images/resources/prod-cat5.jpg"
  },
  "/images/resources/prod-cat6.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.813Z",
    "size": 367,
    "path": "../public/images/resources/prod-cat6.jpg"
  },
  "/images/resources/product2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.814Z",
    "size": 6257,
    "path": "../public/images/resources/product2.jpg"
  },
  "/images/resources/prodcut1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.813Z",
    "size": 6257,
    "path": "../public/images/resources/prodcut1.jpg"
  },
  "/images/resources/product3.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.814Z",
    "size": 6257,
    "path": "../public/images/resources/product3.jpg"
  },
  "/images/resources/product4.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.814Z",
    "size": 6257,
    "path": "../public/images/resources/product4.jpg"
  },
  "/images/resources/product5.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.815Z",
    "size": 6257,
    "path": "../public/images/resources/product5.jpg"
  },
  "/images/resources/product6.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.815Z",
    "size": 6257,
    "path": "../public/images/resources/product6.jpg"
  },
  "/images/resources/product7.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.815Z",
    "size": 6257,
    "path": "../public/images/resources/product7.jpg"
  },
  "/images/resources/profile-baner.jpg": {
    "type": "image/jpeg",
    "etag": "\"50d1-JPXKY92sw1Jo8yO2KkPDwP04IYc\"",
    "mtime": "2026-01-27T02:33:46.816Z",
    "size": 20689,
    "path": "../public/images/resources/profile-baner.jpg"
  },
  "/images/resources/red-thsirt.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.816Z",
    "size": 7605,
    "path": "../public/images/resources/red-thsirt.jpg"
  },
  "/images/resources/shop1.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.816Z",
    "size": 10901,
    "path": "../public/images/resources/shop1.jpg"
  },
  "/images/resources/shop2.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.817Z",
    "size": 10901,
    "path": "../public/images/resources/shop2.jpg"
  },
  "/images/resources/shop3.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.817Z",
    "size": 10901,
    "path": "../public/images/resources/shop3.jpg"
  },
  "/images/resources/shop4.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.817Z",
    "size": 10901,
    "path": "../public/images/resources/shop4.jpg"
  },
  "/images/resources/shop5.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.818Z",
    "size": 10901,
    "path": "../public/images/resources/shop5.jpg"
  },
  "/images/resources/shop6.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.818Z",
    "size": 10901,
    "path": "../public/images/resources/shop6.jpg"
  },
  "/images/resources/shop7.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.818Z",
    "size": 10901,
    "path": "../public/images/resources/shop7.jpg"
  },
  "/images/resources/shop8.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.819Z",
    "size": 10901,
    "path": "../public/images/resources/shop8.jpg"
  },
  "/images/resources/travel.jpg": {
    "type": "image/jpeg",
    "etag": "\"8374-WKP8EilXwmAoeWUI1RnojdpgSoM\"",
    "mtime": "2026-01-27T02:33:46.819Z",
    "size": 33652,
    "path": "../public/images/resources/travel.jpg"
  },
  "/images/resources/user1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.820Z",
    "size": 421,
    "path": "../public/images/resources/user1.jpg"
  },
  "/images/resources/user10.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.820Z",
    "size": 421,
    "path": "../public/images/resources/user10.jpg"
  },
  "/images/resources/user11.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.820Z",
    "size": 421,
    "path": "../public/images/resources/user11.jpg"
  },
  "/images/resources/user12.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.820Z",
    "size": 421,
    "path": "../public/images/resources/user12.jpg"
  },
  "/images/resources/user13.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.821Z",
    "size": 421,
    "path": "../public/images/resources/user13.jpg"
  },
  "/images/resources/user14.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.821Z",
    "size": 421,
    "path": "../public/images/resources/user14.jpg"
  },
  "/images/resources/user15.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.821Z",
    "size": 421,
    "path": "../public/images/resources/user15.jpg"
  },
  "/images/resources/user16.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.821Z",
    "size": 421,
    "path": "../public/images/resources/user16.jpg"
  },
  "/images/resources/user17.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.821Z",
    "size": 421,
    "path": "../public/images/resources/user17.jpg"
  },
  "/images/resources/user18.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.822Z",
    "size": 421,
    "path": "../public/images/resources/user18.jpg"
  },
  "/images/resources/user19.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.822Z",
    "size": 421,
    "path": "../public/images/resources/user19.jpg"
  },
  "/images/resources/user2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.822Z",
    "size": 421,
    "path": "../public/images/resources/user2.jpg"
  },
  "/images/resources/user20.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.822Z",
    "size": 421,
    "path": "../public/images/resources/user20.jpg"
  },
  "/images/resources/user21.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.823Z",
    "size": 421,
    "path": "../public/images/resources/user21.jpg"
  },
  "/images/resources/user22.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.823Z",
    "size": 421,
    "path": "../public/images/resources/user22.jpg"
  },
  "/images/resources/user23.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.823Z",
    "size": 421,
    "path": "../public/images/resources/user23.jpg"
  },
  "/images/resources/user24.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.823Z",
    "size": 421,
    "path": "../public/images/resources/user24.jpg"
  },
  "/images/resources/user25.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.823Z",
    "size": 421,
    "path": "../public/images/resources/user25.jpg"
  },
  "/images/resources/user3.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.824Z",
    "size": 421,
    "path": "../public/images/resources/user3.jpg"
  },
  "/images/resources/user4.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.824Z",
    "size": 421,
    "path": "../public/images/resources/user4.jpg"
  },
  "/images/resources/user5.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.824Z",
    "size": 421,
    "path": "../public/images/resources/user5.jpg"
  },
  "/images/resources/user6.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.824Z",
    "size": 421,
    "path": "../public/images/resources/user6.jpg"
  },
  "/images/resources/user7.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.825Z",
    "size": 421,
    "path": "../public/images/resources/user7.jpg"
  },
  "/images/resources/user8.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.826Z",
    "size": 421,
    "path": "../public/images/resources/user8.jpg"
  },
  "/images/resources/user9.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.826Z",
    "size": 421,
    "path": "../public/images/resources/user9.jpg"
  },
  "/images/resources/video-big.jpg": {
    "type": "image/jpeg",
    "etag": "\"d13-ACGfsKIxT42grgr7WBCK9aY7Ue0\"",
    "mtime": "2026-01-27T02:33:46.826Z",
    "size": 3347,
    "path": "../public/images/resources/video-big.jpg"
  },
  "/images/resources/video1.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.826Z",
    "size": 807,
    "path": "../public/images/resources/video1.jpg"
  },
  "/images/resources/video10.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.827Z",
    "size": 807,
    "path": "../public/images/resources/video10.jpg"
  },
  "/images/resources/video11.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.827Z",
    "size": 807,
    "path": "../public/images/resources/video11.jpg"
  },
  "/images/resources/video2.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.827Z",
    "size": 807,
    "path": "../public/images/resources/video2.jpg"
  },
  "/images/resources/video3.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.827Z",
    "size": 807,
    "path": "../public/images/resources/video3.jpg"
  },
  "/images/resources/video4.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.828Z",
    "size": 1591,
    "path": "../public/images/resources/video4.jpg"
  },
  "/images/resources/video5.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.828Z",
    "size": 1591,
    "path": "../public/images/resources/video5.jpg"
  },
  "/images/resources/video6.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.828Z",
    "size": 807,
    "path": "../public/images/resources/video6.jpg"
  },
  "/images/resources/video7.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.829Z",
    "size": 807,
    "path": "../public/images/resources/video7.jpg"
  },
  "/images/resources/video8.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.829Z",
    "size": 807,
    "path": "../public/images/resources/video8.jpg"
  },
  "/images/resources/video9.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.829Z",
    "size": 807,
    "path": "../public/images/resources/video9.jpg"
  },
  "/images/resources/welcome.png": {
    "type": "image/png",
    "etag": "\"1426-luWMkNvukl8gd22O73KUzSBAOQs\"",
    "mtime": "2026-01-27T02:33:46.830Z",
    "size": 5158,
    "path": "../public/images/resources/welcome.png"
  },
  "/images/svg/aaa.png": {
    "type": "image/png",
    "etag": "\"13aa-eUfeM07zT6yleN9xiZwK6B636ks\"",
    "mtime": "2026-01-27T02:33:46.830Z",
    "size": 5034,
    "path": "../public/images/svg/aaa.png"
  },
  "/images/svg/about.png": {
    "type": "image/png",
    "etag": "\"1f4-weWBckNoVMMYwduqprn1+w3PB6M\"",
    "mtime": "2026-01-27T02:33:46.831Z",
    "size": 500,
    "path": "../public/images/svg/about.png"
  },
  "/images/svg/angry.png": {
    "type": "image/png",
    "etag": "\"12db-Db61jQIlGEh64LHz6ipAvLT9HxM\"",
    "mtime": "2026-01-27T02:33:46.831Z",
    "size": 4827,
    "path": "../public/images/svg/angry.png"
  },
  "/images/svg/arrow-left.svg": {
    "type": "image/svg+xml",
    "etag": "\"3ab-lm3gSbBf5oNztiSnWx+h87bgMto\"",
    "mtime": "2026-01-27T02:33:46.831Z",
    "size": 939,
    "path": "../public/images/svg/arrow-left.svg"
  },
  "/images/svg/arrow-right.svg": {
    "type": "image/svg+xml",
    "etag": "\"3b1-eqshx/nnsCHs+qe4Jg8SaFA0BN4\"",
    "mtime": "2026-01-27T02:33:46.832Z",
    "size": 945,
    "path": "../public/images/svg/arrow-right.svg"
  },
  "/images/svg/comon-things.png": {
    "type": "image/png",
    "etag": "\"7a9-7bSkPLmOzUFuU8qiBhAujsG/n0A\"",
    "mtime": "2026-01-27T02:33:46.832Z",
    "size": 1961,
    "path": "../public/images/svg/comon-things.png"
  },
  "/images/svg/event.png": {
    "type": "image/png",
    "etag": "\"22f-RGz8F0uhLwBQ8Uas4UDb4APOiLE\"",
    "mtime": "2026-01-27T02:33:46.832Z",
    "size": 559,
    "path": "../public/images/svg/event.png"
  },
  "/images/svg/fund.png": {
    "type": "image/png",
    "etag": "\"6c8-sZzk4kAaS35b5XAq6/Xs4/U2D8k\"",
    "mtime": "2026-01-27T02:33:46.832Z",
    "size": 1736,
    "path": "../public/images/svg/fund.png"
  },
  "/images/svg/gif.png": {
    "type": "image/png",
    "etag": "\"1295-ugssMLdASHJuFunSq5tw2L8IZlU\"",
    "mtime": "2026-01-27T02:33:46.833Z",
    "size": 4757,
    "path": "../public/images/svg/gif.png"
  },
  "/images/svg/groups.png": {
    "type": "image/png",
    "etag": "\"421-BhZJHtflxC6RbiFAA/IZUrTAMXY\"",
    "mtime": "2026-01-27T02:33:46.833Z",
    "size": 1057,
    "path": "../public/images/svg/groups.png"
  },
  "/images/svg/heart.png": {
    "type": "image/png",
    "etag": "\"ff8-EY5ZPT3jZuOirh7/CtrF6SLVSAc\"",
    "mtime": "2026-01-27T02:33:46.833Z",
    "size": 4088,
    "path": "../public/images/svg/heart.png"
  },
  "/images/svg/history.png": {
    "type": "image/png",
    "etag": "\"3df-y3yK4BTHs+7OBnVFm2jijIX7UYk\"",
    "mtime": "2026-01-27T02:33:46.834Z",
    "size": 991,
    "path": "../public/images/svg/history.png"
  },
  "/images/svg/home.png": {
    "type": "image/png",
    "etag": "\"465-MKeG3KLLT31f7HiKlZMRx11oYCQ\"",
    "mtime": "2026-01-27T02:33:46.834Z",
    "size": 1125,
    "path": "../public/images/svg/home.png"
  },
  "/images/svg/inbox.png": {
    "type": "image/png",
    "etag": "\"430-rkH8QNQjY/ixfAHP29yoh2u8Hcc\"",
    "mtime": "2026-01-27T02:33:46.834Z",
    "size": 1072,
    "path": "../public/images/svg/inbox.png"
  },
  "/images/svg/job.png": {
    "type": "image/png",
    "etag": "\"5e1-33JUGGVf/j4lKmUA9S/xXyIznUE\"",
    "mtime": "2026-01-27T02:33:46.834Z",
    "size": 1505,
    "path": "../public/images/svg/job.png"
  },
  "/images/svg/live.png": {
    "type": "image/png",
    "etag": "\"f82-jyUDWDZ9wH0hnPkKBXIaC+k7mJ8\"",
    "mtime": "2026-01-27T02:33:46.835Z",
    "size": 3970,
    "path": "../public/images/svg/live.png"
  },
  "/images/svg/map-marker.png": {
    "type": "image/png",
    "etag": "\"eda-YK3t/ZiPbXAoTcrW+aWEOQ/yfuQ\"",
    "mtime": "2026-01-27T02:33:46.835Z",
    "size": 3802,
    "path": "../public/images/svg/map-marker.png"
  },
  "/images/svg/market.png": {
    "type": "image/png",
    "etag": "\"472-cIph4/wE1PAzuFP9W6qjY99u+wM\"",
    "mtime": "2026-01-27T02:33:46.835Z",
    "size": 1138,
    "path": "../public/images/svg/market.png"
  },
  "/images/svg/messages.png": {
    "type": "image/png",
    "etag": "\"2e3-8sltB2uxeFBrF7ia1597uy08W0c\"",
    "mtime": "2026-01-27T02:33:46.836Z",
    "size": 739,
    "path": "../public/images/svg/messages.png"
  },
  "/images/svg/news.png": {
    "type": "image/png",
    "etag": "\"15f-fHk9fJS1n1W1ZQLS81egKHpQd2E\"",
    "mtime": "2026-01-27T02:33:46.836Z",
    "size": 351,
    "path": "../public/images/svg/news.png"
  },
  "/images/svg/pages.png": {
    "type": "image/png",
    "etag": "\"2ff-KSEyRNLJ5lsDIk1pKl5GRj9mhZA\"",
    "mtime": "2026-01-27T02:33:46.836Z",
    "size": 767,
    "path": "../public/images/svg/pages.png"
  },
  "/images/svg/photos.png": {
    "type": "image/png",
    "etag": "\"5c1-vB7UkXwm8PYJaEcma2DCHS8jzrg\"",
    "mtime": "2026-01-27T02:33:46.836Z",
    "size": 1473,
    "path": "../public/images/svg/photos.png"
  },
  "/images/svg/prices.png": {
    "type": "image/png",
    "etag": "\"6e8-UcPTUgmZ2SGgNjjCHZHiZM8TlbA\"",
    "mtime": "2026-01-27T02:33:46.837Z",
    "size": 1768,
    "path": "../public/images/svg/prices.png"
  },
  "/images/svg/QA.png": {
    "type": "image/png",
    "etag": "\"452-Tv56+LuEXqisVG8M+Hs+b3+aXt0\"",
    "mtime": "2026-01-27T02:33:46.830Z",
    "size": 1106,
    "path": "../public/images/svg/QA.png"
  },
  "/images/svg/recommend.png": {
    "type": "image/png",
    "etag": "\"df2-MIgF3S9ttF1WlTZ8GyoqbDCCq+8\"",
    "mtime": "2026-01-27T02:33:46.837Z",
    "size": 3570,
    "path": "../public/images/svg/recommend.png"
  },
  "/images/svg/save.png": {
    "type": "image/png",
    "etag": "\"144-4SdgmT+AbaHFH3MbmkxwAghw6tc\"",
    "mtime": "2026-01-27T02:33:46.837Z",
    "size": 324,
    "path": "../public/images/svg/save.png"
  },
  "/images/svg/setting.png": {
    "type": "image/png",
    "etag": "\"1fd-tJoSSZUIAuu7DwgGwRQ0/zqKN7Q\"",
    "mtime": "2026-01-27T02:33:46.837Z",
    "size": 509,
    "path": "../public/images/svg/setting.png"
  },
  "/images/svg/smile.png": {
    "type": "image/png",
    "etag": "\"1251-EisdWUVVDhOAGyjK22PjTF2BOsI\"",
    "mtime": "2026-01-27T02:33:46.838Z",
    "size": 4689,
    "path": "../public/images/svg/smile.png"
  },
  "/images/svg/thumb.png": {
    "type": "image/png",
    "etag": "\"ef1-ucHJoP8277v3E6UJ8wwkFGdn67g\"",
    "mtime": "2026-01-27T02:33:46.838Z",
    "size": 3825,
    "path": "../public/images/svg/thumb.png"
  },
  "/images/svg/trending.png": {
    "type": "image/png",
    "etag": "\"659-tjuXFaKI6SCnSjXOENjljeVKJHc\"",
    "mtime": "2026-01-27T02:33:46.838Z",
    "size": 1625,
    "path": "../public/images/svg/trending.png"
  },
  "/images/svg/user.png": {
    "type": "image/png",
    "etag": "\"70f-Oz1cIeLnEUUfBp5aZVhpzt3/nBw\"",
    "mtime": "2026-01-27T02:33:46.838Z",
    "size": 1807,
    "path": "../public/images/svg/user.png"
  },
  "/images/svg/users.png": {
    "type": "image/png",
    "etag": "\"54b-CRYYXaHpVCCqis+Ej80QczQtDGw\"",
    "mtime": "2026-01-27T02:33:46.839Z",
    "size": 1355,
    "path": "../public/images/svg/users.png"
  },
  "/images/svg/video.png": {
    "type": "image/png",
    "etag": "\"5b0-L9b3YW2H+6JvXpErUeaLsncqsLI\"",
    "mtime": "2026-01-27T02:33:46.839Z",
    "size": 1456,
    "path": "../public/images/svg/video.png"
  },
  "/images/svg/weep.png": {
    "type": "image/png",
    "etag": "\"1268-mZs6XyrQ+SPe6iWurxvYyMBjkx8\"",
    "mtime": "2026-01-27T02:33:46.839Z",
    "size": 4712,
    "path": "../public/images/svg/weep.png"
  },
  "/storage/images/plearnd-logo.png": {
    "type": "image/png",
    "etag": "\"3e8c-6KqMBohIw/9YNwzHczpUWPiCgRQ\"",
    "mtime": "2026-01-27T02:33:46.903Z",
    "size": 16012,
    "path": "../public/storage/images/plearnd-logo.png"
  },
  "/storage/images/std_card_bg2.png": {
    "type": "image/png",
    "etag": "\"3be00-0hQ92WRSGZQ+sC7+jjFZjBC87YU\"",
    "mtime": "2026-01-27T02:33:46.906Z",
    "size": 245248,
    "path": "../public/storage/images/std_card_bg2.png"
  },
  "/storage/landing/board-597238_1280.jpg": {
    "type": "image/jpeg",
    "etag": "\"419e1-UmlQh7ulx61Ruaa7sb5HOXre2dA\"",
    "mtime": "2026-01-27T02:33:46.927Z",
    "size": 268769,
    "path": "../public/storage/landing/board-597238_1280.jpg"
  },
  "/storage/landing/ceo.jpg": {
    "type": "image/jpeg",
    "etag": "\"5aee-SvDEM0SNZtMw9HbkJoU2Rnxq7WU\"",
    "mtime": "2026-01-27T02:33:46.927Z",
    "size": 23278,
    "path": "../public/storage/landing/ceo.jpg"
  },
  "/storage/landing/chalkboard-draw-black.jpg": {
    "type": "image/jpeg",
    "etag": "\"1335d-N7UMa8cxq4lAiIKUSMtxcOsVKRs\"",
    "mtime": "2026-01-27T02:33:46.929Z",
    "size": 78685,
    "path": "../public/storage/landing/chalkboard-draw-black.jpg"
  },
  "/storage/landing/404-bg.png": {
    "type": "image/png",
    "etag": "\"8c9e7-R0wH+rZwTABHd68EEUs4vZ8fTxI\"",
    "mtime": "2026-01-27T02:33:46.925Z",
    "size": 575975,
    "path": "../public/storage/landing/404-bg.png"
  },
  "/storage/landing/chalkboard-draw-green.png": {
    "type": "image/png",
    "etag": "\"5bb8d-x99tUeIovXNdTmSp80lwukK4lbY\"",
    "mtime": "2026-01-27T02:33:46.932Z",
    "size": 375693,
    "path": "../public/storage/landing/chalkboard-draw-green.png"
  },
  "/storage/landing/dot-texture.png": {
    "type": "image/png",
    "etag": "\"3c5-SLzy2p5P0SIkXuPvZgpzyxL41Z4\"",
    "mtime": "2026-01-27T02:33:46.932Z",
    "size": 965,
    "path": "../public/storage/landing/dot-texture.png"
  },
  "/storage/landing/geometry.jpg": {
    "type": "image/jpeg",
    "etag": "\"2ce7b-dFaciMy2wzRYKoWr6oWpicwIGGM\"",
    "mtime": "2026-01-27T02:33:46.934Z",
    "size": 183931,
    "path": "../public/storage/landing/geometry.jpg"
  },
  "/storage/landing/joanna-kosinska-education-unsplash.png": {
    "type": "image/png",
    "etag": "\"2855d-Pd9PaIqYq0RcBS8YDXFki3K1WGU\"",
    "mtime": "2026-01-27T02:33:46.935Z",
    "size": 165213,
    "path": "../public/storage/landing/joanna-kosinska-education-unsplash.png"
  },
  "/storage/landing/rocket.png": {
    "type": "image/png",
    "etag": "\"2470-Wkgd/mCrrnjyrpC9SD5+S3W0oW8\"",
    "mtime": "2026-01-27T02:33:46.943Z",
    "size": 9328,
    "path": "../public/storage/landing/rocket.png"
  },
  "/storage/landing/school-1325091_1280.png": {
    "type": "image/png",
    "etag": "\"3a68-Zp7e3NShzHU6ka/94qDxDtTFeqg\"",
    "mtime": "2026-01-27T02:33:46.944Z",
    "size": 14952,
    "path": "../public/storage/landing/school-1325091_1280.png"
  },
  "/storage/landing/vikinger-logo.png": {
    "type": "image/png",
    "etag": "\"e3a-WuO4XyIBF9ksPrpF14yIKRl+hnA\"",
    "mtime": "2026-01-27T02:33:46.944Z",
    "size": 3642,
    "path": "../public/storage/landing/vikinger-logo.png"
  },
  "/_nuxt/builds/latest.json": {
    "type": "application/json",
    "etag": "\"47-L6iyl9oFcEqT9e59LY61Yfg8/BQ\"",
    "mtime": "2026-01-28T08:25:37.549Z",
    "size": 71,
    "path": "../public/_nuxt/builds/latest.json"
  },
  "/images/images/resources/admin.jpg": {
    "type": "image/jpeg",
    "etag": "\"1f4-6No1N2mwTsQqiBTY2G+LkVzZ9gY\"",
    "mtime": "2026-01-27T02:33:46.689Z",
    "size": 500,
    "path": "../public/images/images/resources/admin.jpg"
  },
  "/images/images/resources/album1.jpg": {
    "type": "image/jpeg",
    "etag": "\"64e-q/p4vfk6DXHs64xC92mawpdms6w\"",
    "mtime": "2026-01-27T02:33:46.689Z",
    "size": 1614,
    "path": "../public/images/images/resources/album1.jpg"
  },
  "/images/images/resources/album2.jpg": {
    "type": "image/jpeg",
    "etag": "\"64e-q/p4vfk6DXHs64xC92mawpdms6w\"",
    "mtime": "2026-01-27T02:33:46.690Z",
    "size": 1614,
    "path": "../public/images/images/resources/album2.jpg"
  },
  "/images/images/resources/animate-bg.png": {
    "type": "image/png",
    "etag": "\"159c-t0IrwsYPeLOUE7wA1NKFFDowzbw\"",
    "mtime": "2026-01-27T02:33:46.690Z",
    "size": 5532,
    "path": "../public/images/images/resources/animate-bg.png"
  },
  "/images/images/resources/bg-image.jpg": {
    "type": "image/jpeg",
    "etag": "\"791d-VBL7OmF32KT5XOOxSnMkDYI14Hw\"",
    "mtime": "2026-01-27T02:33:46.691Z",
    "size": 31005,
    "path": "../public/images/images/resources/bg-image.jpg"
  },
  "/images/images/resources/black-thsirt.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.691Z",
    "size": 7605,
    "path": "../public/images/images/resources/black-thsirt.jpg"
  },
  "/images/images/resources/blog1.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.691Z",
    "size": 1391,
    "path": "../public/images/images/resources/blog1.jpg"
  },
  "/images/images/resources/blog2.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.691Z",
    "size": 1391,
    "path": "../public/images/images/resources/blog2.jpg"
  },
  "/images/images/resources/blog3.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.692Z",
    "size": 1391,
    "path": "../public/images/images/resources/blog3.jpg"
  },
  "/images/images/resources/blog4.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.692Z",
    "size": 1391,
    "path": "../public/images/images/resources/blog4.jpg"
  },
  "/images/images/resources/blog5.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.692Z",
    "size": 1391,
    "path": "../public/images/images/resources/blog5.jpg"
  },
  "/images/images/resources/blog6.jpg": {
    "type": "image/jpeg",
    "etag": "\"56f-oPGIPIPmSeuU7uwRJ8jzR3s3Xjo\"",
    "mtime": "2026-01-27T02:33:46.693Z",
    "size": 1391,
    "path": "../public/images/images/resources/blog6.jpg"
  },
  "/storage/landing/landing-background.jpg": {
    "type": "image/jpeg",
    "etag": "\"e072a-7YZ3CWxwjkdCoKq/Y0k25zvPHTs\"",
    "mtime": "2026-01-27T02:33:46.943Z",
    "size": 919338,
    "path": "../public/storage/landing/landing-background.jpg"
  },
  "/images/images/resources/blue-thsirt.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.693Z",
    "size": 7605,
    "path": "../public/images/images/resources/blue-thsirt.jpg"
  },
  "/images/images/resources/camera.png": {
    "type": "image/png",
    "etag": "\"1426-0homP71MkXK+dBxpjrvnZu8UcG8\"",
    "mtime": "2026-01-27T02:33:46.693Z",
    "size": 5158,
    "path": "../public/images/images/resources/camera.png"
  },
  "/images/images/resources/degree-holder.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.693Z",
    "size": 807,
    "path": "../public/images/images/resources/degree-holder.jpg"
  },
  "/images/images/resources/event-small.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.694Z",
    "size": 11372,
    "path": "../public/images/images/resources/event-small.jpg"
  },
  "/images/images/resources/event-small2.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.694Z",
    "size": 11372,
    "path": "../public/images/images/resources/event-small2.jpg"
  },
  "/images/images/resources/event.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.694Z",
    "size": 11372,
    "path": "../public/images/images/resources/event.jpg"
  },
  "/images/images/resources/feature-product1.jpg": {
    "type": "image/jpeg",
    "etag": "\"6f36-xIMYYFD0yYcxuernsNcJqo+aSx8\"",
    "mtime": "2026-01-27T02:33:46.694Z",
    "size": 28470,
    "path": "../public/images/images/resources/feature-product1.jpg"
  },
  "/images/images/resources/feature-product2.jpg": {
    "type": "image/jpeg",
    "etag": "\"6f36-xIMYYFD0yYcxuernsNcJqo+aSx8\"",
    "mtime": "2026-01-27T02:33:46.696Z",
    "size": 28470,
    "path": "../public/images/images/resources/feature-product2.jpg"
  },
  "/images/images/resources/feature-product3.jpg": {
    "type": "image/jpeg",
    "etag": "\"6f36-xIMYYFD0yYcxuernsNcJqo+aSx8\"",
    "mtime": "2026-01-27T02:33:46.696Z",
    "size": 28470,
    "path": "../public/images/images/resources/feature-product3.jpg"
  },
  "/images/images/resources/good-noon.png": {
    "type": "image/png",
    "etag": "\"19d-uNWqbtxlEsoKziogoQ0ug3thHhs\"",
    "mtime": "2026-01-27T02:33:46.696Z",
    "size": 413,
    "path": "../public/images/images/resources/good-noon.png"
  },
  "/images/images/resources/group1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.697Z",
    "size": 5249,
    "path": "../public/images/images/resources/group1.jpg"
  },
  "/images/images/resources/group10.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.697Z",
    "size": 525,
    "path": "../public/images/images/resources/group10.jpg"
  },
  "/images/images/resources/group11.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.698Z",
    "size": 525,
    "path": "../public/images/images/resources/group11.jpg"
  },
  "/images/images/resources/group12.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.698Z",
    "size": 525,
    "path": "../public/images/images/resources/group12.jpg"
  },
  "/images/images/resources/group2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.698Z",
    "size": 5249,
    "path": "../public/images/images/resources/group2.jpg"
  },
  "/images/images/resources/group3.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.698Z",
    "size": 5249,
    "path": "../public/images/images/resources/group3.jpg"
  },
  "/images/images/resources/group4.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.698Z",
    "size": 5249,
    "path": "../public/images/images/resources/group4.jpg"
  },
  "/images/images/resources/group5.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.698Z",
    "size": 5249,
    "path": "../public/images/images/resources/group5.jpg"
  },
  "/images/images/resources/group6.jpg": {
    "type": "image/jpeg",
    "etag": "\"1481-UHBGNzlFPLXKN3FfSrUQPAwfWgA\"",
    "mtime": "2026-01-27T02:33:46.700Z",
    "size": 5249,
    "path": "../public/images/images/resources/group6.jpg"
  },
  "/images/images/resources/group7.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.700Z",
    "size": 525,
    "path": "../public/images/images/resources/group7.jpg"
  },
  "/images/images/resources/group8.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.700Z",
    "size": 525,
    "path": "../public/images/images/resources/group8.jpg"
  },
  "/images/images/resources/group9.jpg": {
    "type": "image/jpeg",
    "etag": "\"20d-MujADxo8ME9HONEaL3hWNg1wlzY\"",
    "mtime": "2026-01-27T02:33:46.701Z",
    "size": 525,
    "path": "../public/images/images/resources/group9.jpg"
  },
  "/images/images/resources/img-1.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.701Z",
    "size": 11372,
    "path": "../public/images/images/resources/img-1.jpg"
  },
  "/images/images/resources/img-2.jpg": {
    "type": "image/jpeg",
    "etag": "\"2c6c-p5lFY/O6sd8BeBNatDs7WpIb8W0\"",
    "mtime": "2026-01-27T02:33:46.701Z",
    "size": 11372,
    "path": "../public/images/images/resources/img-2.jpg"
  },
  "/images/images/resources/location.jpg": {
    "type": "image/jpeg",
    "etag": "\"39d7-2n491HDTGYoJiquidlAZRgfqNEM\"",
    "mtime": "2026-01-27T02:33:46.702Z",
    "size": 14807,
    "path": "../public/images/images/resources/location.jpg"
  },
  "/images/images/resources/location.png": {
    "type": "image/png",
    "etag": "\"1426-tGzxJK1i/cdQNvZ2Lc2upoii7Xc\"",
    "mtime": "2026-01-27T02:33:46.702Z",
    "size": 5158,
    "path": "../public/images/images/resources/location.png"
  },
  "/images/images/resources/money-card.png": {
    "type": "image/png",
    "etag": "\"2c6e-4CF1/OuYWjp6LAKUXpH7wWAnZqQ\"",
    "mtime": "2026-01-27T02:33:46.724Z",
    "size": 11374,
    "path": "../public/images/images/resources/money-card.png"
  },
  "/images/images/resources/order-success.jpg": {
    "type": "image/jpeg",
    "etag": "\"845-Ece2U0pPLUmNtVVbEbTCRavqh0o\"",
    "mtime": "2026-01-27T02:33:46.724Z",
    "size": 2117,
    "path": "../public/images/images/resources/order-success.jpg"
  },
  "/images/images/resources/page=profile.jpg": {
    "type": "image/jpeg",
    "etag": "\"28f4-eedhEzmIDzAAK/GXN8SfxDsDU7Q\"",
    "mtime": "2026-01-27T02:33:46.726Z",
    "size": 10484,
    "path": "../public/images/images/resources/page=profile.jpg"
  },
  "/images/images/resources/party1.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.726Z",
    "size": 2106,
    "path": "../public/images/images/resources/party1.jpg"
  },
  "/images/images/resources/party2.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.726Z",
    "size": 2106,
    "path": "../public/images/images/resources/party2.jpg"
  },
  "/images/images/resources/party3.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.726Z",
    "size": 2106,
    "path": "../public/images/images/resources/party3.jpg"
  },
  "/images/images/resources/party4.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.727Z",
    "size": 2106,
    "path": "../public/images/images/resources/party4.jpg"
  },
  "/images/images/resources/party5.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.727Z",
    "size": 2106,
    "path": "../public/images/images/resources/party5.jpg"
  },
  "/images/images/resources/party6.jpg": {
    "type": "image/jpeg",
    "etag": "\"83a-q8GSgyAUDbjX66F8ox0qdoaNKc4\"",
    "mtime": "2026-01-27T02:33:46.727Z",
    "size": 2106,
    "path": "../public/images/images/resources/party6.jpg"
  },
  "/images/images/resources/photo-big.jpg": {
    "type": "image/jpeg",
    "etag": "\"d13-ACGfsKIxT42grgr7WBCK9aY7Ue0\"",
    "mtime": "2026-01-27T02:33:46.728Z",
    "size": 3347,
    "path": "../public/images/images/resources/photo-big.jpg"
  },
  "/images/images/resources/picture-pair1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1fd2-KJAWQybIUdRilQKi/jKzcXuwh0g\"",
    "mtime": "2026-01-27T02:33:46.728Z",
    "size": 8146,
    "path": "../public/images/images/resources/picture-pair1.jpg"
  },
  "/images/images/resources/picture-pair2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.728Z",
    "size": 7605,
    "path": "../public/images/images/resources/picture-pair2.jpg"
  },
  "/images/images/resources/picture-pair3.jpg": {
    "type": "image/jpeg",
    "etag": "\"e0d-XI3SKWM2DDPJbC40/W5b9ZKLQnQ\"",
    "mtime": "2026-01-27T02:33:46.729Z",
    "size": 3597,
    "path": "../public/images/images/resources/picture-pair3.jpg"
  },
  "/images/images/resources/picture-pair4.jpg": {
    "type": "image/jpeg",
    "etag": "\"216e-N3TabsYAKDBtSrTIlEWTO9BjUQY\"",
    "mtime": "2026-01-27T02:33:46.729Z",
    "size": 8558,
    "path": "../public/images/images/resources/picture-pair4.jpg"
  },
  "/images/images/resources/picture-pair5.jpg": {
    "type": "image/jpeg",
    "etag": "\"b36-VAlojEwTimeGa9n8fpCWSc4vrLc\"",
    "mtime": "2026-01-27T02:33:46.730Z",
    "size": 2870,
    "path": "../public/images/images/resources/picture-pair5.jpg"
  },
  "/images/images/resources/picture-pair6.jpg": {
    "type": "image/jpeg",
    "etag": "\"a25-VDfVmhqQMI4zCVPI6mT7onw4usQ\"",
    "mtime": "2026-01-27T02:33:46.730Z",
    "size": 2597,
    "path": "../public/images/images/resources/picture-pair6.jpg"
  },
  "/images/images/resources/picture-pair7.jpg": {
    "type": "image/jpeg",
    "etag": "\"def-v6+nNIgRumVQL/ElISPzHinR2CQ\"",
    "mtime": "2026-01-27T02:33:46.731Z",
    "size": 3567,
    "path": "../public/images/images/resources/picture-pair7.jpg"
  },
  "/images/images/resources/picture-pair8.jpg": {
    "type": "image/jpeg",
    "etag": "\"237a-+lkqov+pA4ymOKv9L80m03QE1kM\"",
    "mtime": "2026-01-27T02:33:46.731Z",
    "size": 9082,
    "path": "../public/images/images/resources/picture-pair8.jpg"
  },
  "/images/images/resources/picture1.jpg": {
    "type": "image/jpeg",
    "etag": "\"451-b6C76cnH0YtHffT8C6ka1qNdSCs\"",
    "mtime": "2026-01-27T02:33:46.732Z",
    "size": 1105,
    "path": "../public/images/images/resources/picture1.jpg"
  },
  "/images/images/resources/picture2.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.732Z",
    "size": 807,
    "path": "../public/images/images/resources/picture2.jpg"
  },
  "/images/images/resources/picture3.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.732Z",
    "size": 807,
    "path": "../public/images/images/resources/picture3.jpg"
  },
  "/images/images/resources/picture4.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.732Z",
    "size": 1591,
    "path": "../public/images/images/resources/picture4.jpg"
  },
  "/images/images/resources/picture5.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.733Z",
    "size": 1591,
    "path": "../public/images/images/resources/picture5.jpg"
  },
  "/images/images/resources/picture6.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.733Z",
    "size": 807,
    "path": "../public/images/images/resources/picture6.jpg"
  },
  "/images/images/resources/picture7.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.733Z",
    "size": 807,
    "path": "../public/images/images/resources/picture7.jpg"
  },
  "/images/images/resources/picture8.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.733Z",
    "size": 807,
    "path": "../public/images/images/resources/picture8.jpg"
  },
  "/images/images/resources/place1.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.734Z",
    "size": 3788,
    "path": "../public/images/images/resources/place1.jpg"
  },
  "/images/images/resources/place2.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.734Z",
    "size": 3788,
    "path": "../public/images/images/resources/place2.jpg"
  },
  "/images/images/resources/login-bg2.jpg": {
    "type": "image/jpeg",
    "etag": "\"32f40b-oScTeJ+D+a01HVgpHL35THPzo4E\"",
    "mtime": "2026-01-27T02:33:46.723Z",
    "size": 3339275,
    "path": "../public/images/images/resources/login-bg2.jpg"
  },
  "/images/images/resources/place3.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.734Z",
    "size": 3788,
    "path": "../public/images/images/resources/place3.jpg"
  },
  "/images/images/resources/place4.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.735Z",
    "size": 3788,
    "path": "../public/images/images/resources/place4.jpg"
  },
  "/images/images/resources/place5.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.735Z",
    "size": 3788,
    "path": "../public/images/images/resources/place5.jpg"
  },
  "/images/images/resources/place6.jpg": {
    "type": "image/jpeg",
    "etag": "\"ecc-Jj500uQfWTBGOvqBIy6XcX2YiuA\"",
    "mtime": "2026-01-27T02:33:46.735Z",
    "size": 3788,
    "path": "../public/images/images/resources/place6.jpg"
  },
  "/images/images/resources/prod-cat1.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.736Z",
    "size": 367,
    "path": "../public/images/images/resources/prod-cat1.jpg"
  },
  "/images/images/resources/prod-cat2.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.736Z",
    "size": 367,
    "path": "../public/images/images/resources/prod-cat2.jpg"
  },
  "/images/images/resources/prod-cat3.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.736Z",
    "size": 367,
    "path": "../public/images/images/resources/prod-cat3.jpg"
  },
  "/images/images/resources/prod-cat4.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.737Z",
    "size": 367,
    "path": "../public/images/images/resources/prod-cat4.jpg"
  },
  "/images/images/resources/prod-cat5.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.737Z",
    "size": 367,
    "path": "../public/images/images/resources/prod-cat5.jpg"
  },
  "/images/images/resources/prod-cat6.jpg": {
    "type": "image/jpeg",
    "etag": "\"16f-d8anQTQbecqwwxYppySFuRdv6Ko\"",
    "mtime": "2026-01-27T02:33:46.737Z",
    "size": 367,
    "path": "../public/images/images/resources/prod-cat6.jpg"
  },
  "/images/images/resources/prodcut1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.738Z",
    "size": 6257,
    "path": "../public/images/images/resources/prodcut1.jpg"
  },
  "/images/images/resources/product2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.738Z",
    "size": 6257,
    "path": "../public/images/images/resources/product2.jpg"
  },
  "/images/images/resources/product3.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.738Z",
    "size": 6257,
    "path": "../public/images/images/resources/product3.jpg"
  },
  "/images/images/resources/product4.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.739Z",
    "size": 6257,
    "path": "../public/images/images/resources/product4.jpg"
  },
  "/images/images/resources/product5.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.739Z",
    "size": 6257,
    "path": "../public/images/images/resources/product5.jpg"
  },
  "/images/images/resources/product6.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.739Z",
    "size": 6257,
    "path": "../public/images/images/resources/product6.jpg"
  },
  "/images/images/resources/product7.jpg": {
    "type": "image/jpeg",
    "etag": "\"1871-ENTuNbVD1atvVgDVMRBg0rtkD6M\"",
    "mtime": "2026-01-27T02:33:46.739Z",
    "size": 6257,
    "path": "../public/images/images/resources/product7.jpg"
  },
  "/images/images/resources/profile-baner.jpg": {
    "type": "image/jpeg",
    "etag": "\"50d1-JPXKY92sw1Jo8yO2KkPDwP04IYc\"",
    "mtime": "2026-01-27T02:33:46.740Z",
    "size": 20689,
    "path": "../public/images/images/resources/profile-baner.jpg"
  },
  "/images/images/resources/red-thsirt.jpg": {
    "type": "image/jpeg",
    "etag": "\"1db5-mQN9VOpQXqnD8t97OiIsbxl0B1E\"",
    "mtime": "2026-01-27T02:33:46.740Z",
    "size": 7605,
    "path": "../public/images/images/resources/red-thsirt.jpg"
  },
  "/images/images/resources/shop1.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.740Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop1.jpg"
  },
  "/images/images/resources/shop2.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.741Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop2.jpg"
  },
  "/images/images/resources/shop3.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.741Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop3.jpg"
  },
  "/images/images/resources/shop4.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.741Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop4.jpg"
  },
  "/images/images/resources/shop5.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.742Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop5.jpg"
  },
  "/images/images/resources/shop6.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.742Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop6.jpg"
  },
  "/images/images/resources/shop7.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.742Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop7.jpg"
  },
  "/images/images/resources/shop8.jpg": {
    "type": "image/jpeg",
    "etag": "\"2a95-kvk+tNWfjD8dYSmnoRC6uoCu7W4\"",
    "mtime": "2026-01-27T02:33:46.743Z",
    "size": 10901,
    "path": "../public/images/images/resources/shop8.jpg"
  },
  "/images/images/resources/travel.jpg": {
    "type": "image/jpeg",
    "etag": "\"8374-WKP8EilXwmAoeWUI1RnojdpgSoM\"",
    "mtime": "2026-01-27T02:33:46.743Z",
    "size": 33652,
    "path": "../public/images/images/resources/travel.jpg"
  },
  "/images/images/resources/user1.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.744Z",
    "size": 421,
    "path": "../public/images/images/resources/user1.jpg"
  },
  "/images/images/resources/user10.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.744Z",
    "size": 421,
    "path": "../public/images/images/resources/user10.jpg"
  },
  "/images/images/resources/user11.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.744Z",
    "size": 421,
    "path": "../public/images/images/resources/user11.jpg"
  },
  "/images/images/resources/user12.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.744Z",
    "size": 421,
    "path": "../public/images/images/resources/user12.jpg"
  },
  "/images/images/resources/user13.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.745Z",
    "size": 421,
    "path": "../public/images/images/resources/user13.jpg"
  },
  "/images/images/resources/user14.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.745Z",
    "size": 421,
    "path": "../public/images/images/resources/user14.jpg"
  },
  "/images/images/resources/user15.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.745Z",
    "size": 421,
    "path": "../public/images/images/resources/user15.jpg"
  },
  "/images/images/resources/user16.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.745Z",
    "size": 421,
    "path": "../public/images/images/resources/user16.jpg"
  },
  "/images/images/resources/user17.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.746Z",
    "size": 421,
    "path": "../public/images/images/resources/user17.jpg"
  },
  "/images/images/resources/user18.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.746Z",
    "size": 421,
    "path": "../public/images/images/resources/user18.jpg"
  },
  "/images/images/resources/user19.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.746Z",
    "size": 421,
    "path": "../public/images/images/resources/user19.jpg"
  },
  "/images/images/resources/user2.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.746Z",
    "size": 421,
    "path": "../public/images/images/resources/user2.jpg"
  },
  "/images/images/resources/user20.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.747Z",
    "size": 421,
    "path": "../public/images/images/resources/user20.jpg"
  },
  "/images/images/resources/user21.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.747Z",
    "size": 421,
    "path": "../public/images/images/resources/user21.jpg"
  },
  "/images/images/resources/user22.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.747Z",
    "size": 421,
    "path": "../public/images/images/resources/user22.jpg"
  },
  "/images/images/resources/user23.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.747Z",
    "size": 421,
    "path": "../public/images/images/resources/user23.jpg"
  },
  "/images/images/resources/user24.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.748Z",
    "size": 421,
    "path": "../public/images/images/resources/user24.jpg"
  },
  "/images/images/resources/user25.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.748Z",
    "size": 421,
    "path": "../public/images/images/resources/user25.jpg"
  },
  "/images/images/resources/user3.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.748Z",
    "size": 421,
    "path": "../public/images/images/resources/user3.jpg"
  },
  "/images/images/resources/user4.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.748Z",
    "size": 421,
    "path": "../public/images/images/resources/user4.jpg"
  },
  "/images/images/resources/user5.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.748Z",
    "size": 421,
    "path": "../public/images/images/resources/user5.jpg"
  },
  "/images/images/resources/user6.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.749Z",
    "size": 421,
    "path": "../public/images/images/resources/user6.jpg"
  },
  "/images/images/resources/user7.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.749Z",
    "size": 421,
    "path": "../public/images/images/resources/user7.jpg"
  },
  "/images/images/resources/user8.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.749Z",
    "size": 421,
    "path": "../public/images/images/resources/user8.jpg"
  },
  "/images/images/resources/user9.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5-j/e6ZHz2F5/jpDs/MS0ABmWPsBY\"",
    "mtime": "2026-01-27T02:33:46.749Z",
    "size": 421,
    "path": "../public/images/images/resources/user9.jpg"
  },
  "/images/images/resources/video-big.jpg": {
    "type": "image/jpeg",
    "etag": "\"d13-ACGfsKIxT42grgr7WBCK9aY7Ue0\"",
    "mtime": "2026-01-27T02:33:46.750Z",
    "size": 3347,
    "path": "../public/images/images/resources/video-big.jpg"
  },
  "/images/images/resources/video1.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.750Z",
    "size": 807,
    "path": "../public/images/images/resources/video1.jpg"
  },
  "/images/images/resources/video10.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.750Z",
    "size": 807,
    "path": "../public/images/images/resources/video10.jpg"
  },
  "/images/images/resources/video11.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.750Z",
    "size": 807,
    "path": "../public/images/images/resources/video11.jpg"
  },
  "/images/images/resources/video2.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.751Z",
    "size": 807,
    "path": "../public/images/images/resources/video2.jpg"
  },
  "/images/images/resources/video3.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.751Z",
    "size": 807,
    "path": "../public/images/images/resources/video3.jpg"
  },
  "/images/images/resources/video4.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.752Z",
    "size": 1591,
    "path": "../public/images/images/resources/video4.jpg"
  },
  "/images/images/resources/video5.jpg": {
    "type": "image/jpeg",
    "etag": "\"637-eMCT964tFqwwCDO+HC0CAf46CV4\"",
    "mtime": "2026-01-27T02:33:46.752Z",
    "size": 1591,
    "path": "../public/images/images/resources/video5.jpg"
  },
  "/images/images/resources/video6.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.752Z",
    "size": 807,
    "path": "../public/images/images/resources/video6.jpg"
  },
  "/images/images/resources/video7.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.752Z",
    "size": 807,
    "path": "../public/images/images/resources/video7.jpg"
  },
  "/images/images/resources/video8.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.753Z",
    "size": 807,
    "path": "../public/images/images/resources/video8.jpg"
  },
  "/images/images/resources/video9.jpg": {
    "type": "image/jpeg",
    "etag": "\"327-9/JbpcTCdYkGtQH4A7F0aigq6NI\"",
    "mtime": "2026-01-27T02:33:46.753Z",
    "size": 807,
    "path": "../public/images/images/resources/video9.jpg"
  },
  "/images/images/resources/welcome.png": {
    "type": "image/png",
    "etag": "\"1426-luWMkNvukl8gd22O73KUzSBAOQs\"",
    "mtime": "2026-01-27T02:33:46.753Z",
    "size": 5158,
    "path": "../public/images/images/resources/welcome.png"
  },
  "/images/images/svg/aaa.png": {
    "type": "image/png",
    "etag": "\"13aa-eUfeM07zT6yleN9xiZwK6B636ks\"",
    "mtime": "2026-01-27T02:33:46.754Z",
    "size": 5034,
    "path": "../public/images/images/svg/aaa.png"
  },
  "/images/images/svg/about.png": {
    "type": "image/png",
    "etag": "\"1f4-weWBckNoVMMYwduqprn1+w3PB6M\"",
    "mtime": "2026-01-27T02:33:46.755Z",
    "size": 500,
    "path": "../public/images/images/svg/about.png"
  },
  "/images/images/svg/angry.png": {
    "type": "image/png",
    "etag": "\"12db-Db61jQIlGEh64LHz6ipAvLT9HxM\"",
    "mtime": "2026-01-27T02:33:46.755Z",
    "size": 4827,
    "path": "../public/images/images/svg/angry.png"
  },
  "/images/images/svg/arrow-left.svg": {
    "type": "image/svg+xml",
    "etag": "\"3ab-lm3gSbBf5oNztiSnWx+h87bgMto\"",
    "mtime": "2026-01-27T02:33:46.756Z",
    "size": 939,
    "path": "../public/images/images/svg/arrow-left.svg"
  },
  "/images/images/svg/arrow-right.svg": {
    "type": "image/svg+xml",
    "etag": "\"3b1-eqshx/nnsCHs+qe4Jg8SaFA0BN4\"",
    "mtime": "2026-01-27T02:33:46.756Z",
    "size": 945,
    "path": "../public/images/images/svg/arrow-right.svg"
  },
  "/images/images/svg/comon-things.png": {
    "type": "image/png",
    "etag": "\"7a9-7bSkPLmOzUFuU8qiBhAujsG/n0A\"",
    "mtime": "2026-01-27T02:33:46.756Z",
    "size": 1961,
    "path": "../public/images/images/svg/comon-things.png"
  },
  "/images/images/svg/event.png": {
    "type": "image/png",
    "etag": "\"22f-RGz8F0uhLwBQ8Uas4UDb4APOiLE\"",
    "mtime": "2026-01-27T02:33:46.756Z",
    "size": 559,
    "path": "../public/images/images/svg/event.png"
  },
  "/images/images/svg/fund.png": {
    "type": "image/png",
    "etag": "\"6c8-sZzk4kAaS35b5XAq6/Xs4/U2D8k\"",
    "mtime": "2026-01-27T02:33:46.757Z",
    "size": 1736,
    "path": "../public/images/images/svg/fund.png"
  },
  "/images/images/svg/gif.png": {
    "type": "image/png",
    "etag": "\"1295-ugssMLdASHJuFunSq5tw2L8IZlU\"",
    "mtime": "2026-01-27T02:33:46.757Z",
    "size": 4757,
    "path": "../public/images/images/svg/gif.png"
  },
  "/images/images/svg/groups.png": {
    "type": "image/png",
    "etag": "\"421-BhZJHtflxC6RbiFAA/IZUrTAMXY\"",
    "mtime": "2026-01-27T02:33:46.757Z",
    "size": 1057,
    "path": "../public/images/images/svg/groups.png"
  },
  "/images/images/svg/heart.png": {
    "type": "image/png",
    "etag": "\"ff8-EY5ZPT3jZuOirh7/CtrF6SLVSAc\"",
    "mtime": "2026-01-27T02:33:46.757Z",
    "size": 4088,
    "path": "../public/images/images/svg/heart.png"
  },
  "/images/images/svg/history.png": {
    "type": "image/png",
    "etag": "\"3df-y3yK4BTHs+7OBnVFm2jijIX7UYk\"",
    "mtime": "2026-01-27T02:33:46.758Z",
    "size": 991,
    "path": "../public/images/images/svg/history.png"
  },
  "/images/images/svg/home.png": {
    "type": "image/png",
    "etag": "\"465-MKeG3KLLT31f7HiKlZMRx11oYCQ\"",
    "mtime": "2026-01-27T02:33:46.758Z",
    "size": 1125,
    "path": "../public/images/images/svg/home.png"
  },
  "/images/images/svg/inbox.png": {
    "type": "image/png",
    "etag": "\"430-rkH8QNQjY/ixfAHP29yoh2u8Hcc\"",
    "mtime": "2026-01-27T02:33:46.759Z",
    "size": 1072,
    "path": "../public/images/images/svg/inbox.png"
  },
  "/images/images/svg/job.png": {
    "type": "image/png",
    "etag": "\"5e1-33JUGGVf/j4lKmUA9S/xXyIznUE\"",
    "mtime": "2026-01-27T02:33:46.759Z",
    "size": 1505,
    "path": "../public/images/images/svg/job.png"
  },
  "/images/images/svg/live.png": {
    "type": "image/png",
    "etag": "\"f82-jyUDWDZ9wH0hnPkKBXIaC+k7mJ8\"",
    "mtime": "2026-01-27T02:33:46.759Z",
    "size": 3970,
    "path": "../public/images/images/svg/live.png"
  },
  "/images/images/svg/map-marker.png": {
    "type": "image/png",
    "etag": "\"eda-YK3t/ZiPbXAoTcrW+aWEOQ/yfuQ\"",
    "mtime": "2026-01-27T02:33:46.760Z",
    "size": 3802,
    "path": "../public/images/images/svg/map-marker.png"
  },
  "/images/images/svg/market.png": {
    "type": "image/png",
    "etag": "\"472-cIph4/wE1PAzuFP9W6qjY99u+wM\"",
    "mtime": "2026-01-27T02:33:46.760Z",
    "size": 1138,
    "path": "../public/images/images/svg/market.png"
  },
  "/images/images/svg/messages.png": {
    "type": "image/png",
    "etag": "\"2e3-8sltB2uxeFBrF7ia1597uy08W0c\"",
    "mtime": "2026-01-27T02:33:46.760Z",
    "size": 739,
    "path": "../public/images/images/svg/messages.png"
  },
  "/images/images/svg/news.png": {
    "type": "image/png",
    "etag": "\"15f-fHk9fJS1n1W1ZQLS81egKHpQd2E\"",
    "mtime": "2026-01-27T02:33:46.760Z",
    "size": 351,
    "path": "../public/images/images/svg/news.png"
  },
  "/images/images/svg/pages.png": {
    "type": "image/png",
    "etag": "\"2ff-KSEyRNLJ5lsDIk1pKl5GRj9mhZA\"",
    "mtime": "2026-01-27T02:33:46.761Z",
    "size": 767,
    "path": "../public/images/images/svg/pages.png"
  },
  "/images/images/svg/photos.png": {
    "type": "image/png",
    "etag": "\"5c1-vB7UkXwm8PYJaEcma2DCHS8jzrg\"",
    "mtime": "2026-01-27T02:33:46.761Z",
    "size": 1473,
    "path": "../public/images/images/svg/photos.png"
  },
  "/images/images/svg/prices.png": {
    "type": "image/png",
    "etag": "\"6e8-UcPTUgmZ2SGgNjjCHZHiZM8TlbA\"",
    "mtime": "2026-01-27T02:33:46.761Z",
    "size": 1768,
    "path": "../public/images/images/svg/prices.png"
  },
  "/images/images/svg/QA.png": {
    "type": "image/png",
    "etag": "\"452-Tv56+LuEXqisVG8M+Hs+b3+aXt0\"",
    "mtime": "2026-01-27T02:33:46.754Z",
    "size": 1106,
    "path": "../public/images/images/svg/QA.png"
  },
  "/images/images/svg/recommend.png": {
    "type": "image/png",
    "etag": "\"df2-MIgF3S9ttF1WlTZ8GyoqbDCCq+8\"",
    "mtime": "2026-01-27T02:33:46.762Z",
    "size": 3570,
    "path": "../public/images/images/svg/recommend.png"
  },
  "/images/images/svg/save.png": {
    "type": "image/png",
    "etag": "\"144-4SdgmT+AbaHFH3MbmkxwAghw6tc\"",
    "mtime": "2026-01-27T02:33:46.762Z",
    "size": 324,
    "path": "../public/images/images/svg/save.png"
  },
  "/images/images/svg/setting.png": {
    "type": "image/png",
    "etag": "\"1fd-tJoSSZUIAuu7DwgGwRQ0/zqKN7Q\"",
    "mtime": "2026-01-27T02:33:46.762Z",
    "size": 509,
    "path": "../public/images/images/svg/setting.png"
  },
  "/images/images/svg/smile.png": {
    "type": "image/png",
    "etag": "\"1251-EisdWUVVDhOAGyjK22PjTF2BOsI\"",
    "mtime": "2026-01-27T02:33:46.763Z",
    "size": 4689,
    "path": "../public/images/images/svg/smile.png"
  },
  "/images/images/svg/thumb.png": {
    "type": "image/png",
    "etag": "\"ef1-ucHJoP8277v3E6UJ8wwkFGdn67g\"",
    "mtime": "2026-01-27T02:33:46.763Z",
    "size": 3825,
    "path": "../public/images/images/svg/thumb.png"
  },
  "/images/images/svg/trending.png": {
    "type": "image/png",
    "etag": "\"659-tjuXFaKI6SCnSjXOENjljeVKJHc\"",
    "mtime": "2026-01-27T02:33:46.763Z",
    "size": 1625,
    "path": "../public/images/images/svg/trending.png"
  },
  "/images/images/svg/user.png": {
    "type": "image/png",
    "etag": "\"70f-Oz1cIeLnEUUfBp5aZVhpzt3/nBw\"",
    "mtime": "2026-01-27T02:33:46.763Z",
    "size": 1807,
    "path": "../public/images/images/svg/user.png"
  },
  "/images/images/svg/users.png": {
    "type": "image/png",
    "etag": "\"54b-CRYYXaHpVCCqis+Ej80QczQtDGw\"",
    "mtime": "2026-01-27T02:33:46.764Z",
    "size": 1355,
    "path": "../public/images/images/svg/users.png"
  },
  "/images/images/svg/video.png": {
    "type": "image/png",
    "etag": "\"5b0-L9b3YW2H+6JvXpErUeaLsncqsLI\"",
    "mtime": "2026-01-27T02:33:46.765Z",
    "size": 1456,
    "path": "../public/images/images/svg/video.png"
  },
  "/images/images/svg/weep.png": {
    "type": "image/png",
    "etag": "\"1268-mZs6XyrQ+SPe6iWurxvYyMBjkx8\"",
    "mtime": "2026-01-27T02:33:46.765Z",
    "size": 4712,
    "path": "../public/images/images/svg/weep.png"
  },
  "/js/icons/css/fontello.css": {
    "type": "text/css; charset=utf-8",
    "etag": "\"16-MUHJnnUCBBRhOW2mt3RDavGy6RQ\"",
    "mtime": "2026-01-27T02:33:46.842Z",
    "size": 22,
    "path": "../public/js/icons/css/fontello.css"
  },
  "/js/skins/default/html5boxplayer_fullscreen.png": {
    "type": "image/png",
    "etag": "\"761-64fq7ttOxffz6jQnPCNV5qEeARU\"",
    "mtime": "2026-01-27T02:33:46.851Z",
    "size": 1889,
    "path": "../public/js/skins/default/html5boxplayer_fullscreen.png"
  },
  "/js/skins/default/html5boxplayer_hd.png": {
    "type": "image/png",
    "etag": "\"5dc-Yq1PNJGRhEr0m2DfTeMSkjk1c2Y\"",
    "mtime": "2026-01-27T02:33:46.852Z",
    "size": 1500,
    "path": "../public/js/skins/default/html5boxplayer_hd.png"
  },
  "/js/skins/default/html5boxplayer_playpause.png": {
    "type": "image/png",
    "etag": "\"4bc-cF2dn/NwcBVYYUWQEn1Az6turk4\"",
    "mtime": "2026-01-27T02:33:46.852Z",
    "size": 1212,
    "path": "../public/js/skins/default/html5boxplayer_playpause.png"
  },
  "/js/skins/default/html5boxplayer_playvideo.png": {
    "type": "image/png",
    "etag": "\"6da-r9xHfQiqz2jBdSle78hZkyFYrhE\"",
    "mtime": "2026-01-27T02:33:46.852Z",
    "size": 1754,
    "path": "../public/js/skins/default/html5boxplayer_playvideo.png"
  },
  "/js/skins/default/html5boxplayer_volume.png": {
    "type": "image/png",
    "etag": "\"571-L2UHpXAqhVzVjOkdoubMZ2NvxZM\"",
    "mtime": "2026-01-27T02:33:46.852Z",
    "size": 1393,
    "path": "../public/js/skins/default/html5boxplayer_volume.png"
  },
  "/js/skins/default/lightbox-close-fullscreen.png": {
    "type": "image/png",
    "etag": "\"16a-D3ElU+IoHNv54V/xRIrS341HGBU\"",
    "mtime": "2026-01-27T02:33:46.853Z",
    "size": 362,
    "path": "../public/js/skins/default/lightbox-close-fullscreen.png"
  },
  "/js/skins/default/lightbox-close.png": {
    "type": "image/png",
    "etag": "\"5f4-auAT0p4bl6zk/dY3uMMkYyA9KW8\"",
    "mtime": "2026-01-27T02:33:46.853Z",
    "size": 1524,
    "path": "../public/js/skins/default/lightbox-close.png"
  },
  "/js/skins/default/lightbox-fullscreen-close.png": {
    "type": "image/png",
    "etag": "\"149-dhokEbLfSZexcS5nH6Ur4stVkic\"",
    "mtime": "2026-01-27T02:33:46.853Z",
    "size": 329,
    "path": "../public/js/skins/default/lightbox-fullscreen-close.png"
  },
  "/js/skins/default/lightbox-loading.gif": {
    "type": "image/gif",
    "etag": "\"ddb-B8F2eVMjxNUoR2GRQGiCCoZ8ink\"",
    "mtime": "2026-01-27T02:33:46.854Z",
    "size": 3547,
    "path": "../public/js/skins/default/lightbox-loading.gif"
  },
  "/js/skins/default/lightbox-navnext.png": {
    "type": "image/png",
    "etag": "\"1ca-jnNXg99R1vG/PQDiTMyIhUMMb+U\"",
    "mtime": "2026-01-27T02:33:46.854Z",
    "size": 458,
    "path": "../public/js/skins/default/lightbox-navnext.png"
  },
  "/js/skins/default/lightbox-navprev.png": {
    "type": "image/png",
    "etag": "\"1d0-xLt1nhadrLFyrfJ3lj7d/tkGT5o\"",
    "mtime": "2026-01-27T02:33:46.854Z",
    "size": 464,
    "path": "../public/js/skins/default/lightbox-navprev.png"
  },
  "/js/skins/default/lightbox-next-2.png": {
    "type": "image/png",
    "etag": "\"56a-jNRiEAMT9elpxil5YO+bN7UNYTU\"",
    "mtime": "2026-01-27T02:33:46.854Z",
    "size": 1386,
    "path": "../public/js/skins/default/lightbox-next-2.png"
  },
  "/js/skins/default/lightbox-next-fullscreen.png": {
    "type": "image/png",
    "etag": "\"148-ZZ4IjaQj7dCBrZyahDaJAjMDDBw\"",
    "mtime": "2026-01-27T02:33:46.855Z",
    "size": 328,
    "path": "../public/js/skins/default/lightbox-next-fullscreen.png"
  },
  "/js/skins/default/lightbox-next.png": {
    "type": "image/png",
    "etag": "\"15e-D9kOi24yV+ONl3XmQVqH5hrQnKk\"",
    "mtime": "2026-01-27T02:33:46.855Z",
    "size": 350,
    "path": "../public/js/skins/default/lightbox-next.png"
  },
  "/js/skins/default/lightbox-pause-2.png": {
    "type": "image/png",
    "etag": "\"538-XsvaojvCuG3C54ZfXRwEeD02BHs\"",
    "mtime": "2026-01-27T02:33:46.856Z",
    "size": 1336,
    "path": "../public/js/skins/default/lightbox-pause-2.png"
  },
  "/js/skins/default/lightbox-pause.png": {
    "type": "image/png",
    "etag": "\"443-c81jHk0NlyeC7avvTmkQFSsRB1k\"",
    "mtime": "2026-01-27T02:33:46.856Z",
    "size": 1091,
    "path": "../public/js/skins/default/lightbox-pause.png"
  },
  "/js/skins/default/lightbox-play-2.png": {
    "type": "image/png",
    "etag": "\"584-TQiUII1jNefEAwZqKiDvlaOIBC4\"",
    "mtime": "2026-01-27T02:33:46.856Z",
    "size": 1412,
    "path": "../public/js/skins/default/lightbox-play-2.png"
  },
  "/js/skins/default/lightbox-play.png": {
    "type": "image/png",
    "etag": "\"4c6-dKZCiF+A4G5P603QghSXeh0HZ/w\"",
    "mtime": "2026-01-27T02:33:46.857Z",
    "size": 1222,
    "path": "../public/js/skins/default/lightbox-play.png"
  },
  "/js/skins/default/lightbox-playvideo.png": {
    "type": "image/png",
    "etag": "\"6da-r9xHfQiqz2jBdSle78hZkyFYrhE\"",
    "mtime": "2026-01-27T02:33:46.857Z",
    "size": 1754,
    "path": "../public/js/skins/default/lightbox-playvideo.png"
  },
  "/js/skins/default/lightbox-prev-2.png": {
    "type": "image/png",
    "etag": "\"562-YmyCXDLqGVgeQFk05x2dw8lGw+w\"",
    "mtime": "2026-01-27T02:33:46.857Z",
    "size": 1378,
    "path": "../public/js/skins/default/lightbox-prev-2.png"
  },
  "/js/skins/default/lightbox-prev-fullscreen.png": {
    "type": "image/png",
    "etag": "\"143-yy+cZBsW1z0ih7f7dFYtldRbIzc\"",
    "mtime": "2026-01-27T02:33:46.857Z",
    "size": 323,
    "path": "../public/js/skins/default/lightbox-prev-fullscreen.png"
  },
  "/js/skins/default/lightbox-prev.png": {
    "type": "image/png",
    "etag": "\"160-jM34oWnHHO7tgl00ZZcaQNpY+Yo\"",
    "mtime": "2026-01-27T02:33:46.858Z",
    "size": 352,
    "path": "../public/js/skins/default/lightbox-prev.png"
  },
  "/js/skins/default/nav-arrows-next.png": {
    "type": "image/png",
    "etag": "\"1ca-jnNXg99R1vG/PQDiTMyIhUMMb+U\"",
    "mtime": "2026-01-27T02:33:46.858Z",
    "size": 458,
    "path": "../public/js/skins/default/nav-arrows-next.png"
  },
  "/js/skins/default/nav-arrows-prev.png": {
    "type": "image/png",
    "etag": "\"1d0-xLt1nhadrLFyrfJ3lj7d/tkGT5o\"",
    "mtime": "2026-01-27T02:33:46.858Z",
    "size": 464,
    "path": "../public/js/skins/default/nav-arrows-prev.png"
  },
  "/storage/images/badge/age-b.png": {
    "type": "image/png",
    "etag": "\"1b5b-bJea1+9RQ/ItLUsy77Dl9Ir46Hc\"",
    "mtime": "2026-01-27T02:33:46.863Z",
    "size": 7003,
    "path": "../public/storage/images/badge/age-b.png"
  },
  "/storage/images/badge/age-s.png": {
    "type": "image/png",
    "etag": "\"b71-Ck/ijxQGxlfcKFaYgy6saMG6ml0\"",
    "mtime": "2026-01-27T02:33:46.863Z",
    "size": 2929,
    "path": "../public/storage/images/badge/age-s.png"
  },
  "/storage/images/badge/badge-empty-02.png": {
    "type": "image/png",
    "etag": "\"f9f-dRCLJblCnUZ4NgRcKaW77YdI6V4\"",
    "mtime": "2026-01-27T02:33:46.863Z",
    "size": 3999,
    "path": "../public/storage/images/badge/badge-empty-02.png"
  },
  "/storage/images/badge/badge-empty.png": {
    "type": "image/png",
    "etag": "\"c66-CdExU9haXbNkrPfW/MBqZroHPl0\"",
    "mtime": "2026-01-27T02:33:46.864Z",
    "size": 3174,
    "path": "../public/storage/images/badge/badge-empty.png"
  },
  "/storage/images/badge/blank-s.png": {
    "type": "image/png",
    "etag": "\"804-VcXpcUb1xK0EvXLjNvLRAQA5YtM\"",
    "mtime": "2026-01-27T02:33:46.864Z",
    "size": 2052,
    "path": "../public/storage/images/badge/blank-s.png"
  },
  "/storage/images/badge/bronze-b.png": {
    "type": "image/png",
    "etag": "\"171a-vxwGCEnh/GtpA199StWlDmBmFL8\"",
    "mtime": "2026-01-27T02:33:46.864Z",
    "size": 5914,
    "path": "../public/storage/images/badge/bronze-b.png"
  },
  "/storage/images/badge/bronze-s.png": {
    "type": "image/png",
    "etag": "\"a68-BePX56M7VYayXJAfTrkCZgqM3MY\"",
    "mtime": "2026-01-27T02:33:46.865Z",
    "size": 2664,
    "path": "../public/storage/images/badge/bronze-s.png"
  },
  "/storage/images/badge/bronzec-b.png": {
    "type": "image/png",
    "etag": "\"17ed-DpDw+FS5URSVpXnkm+sw277Spv4\"",
    "mtime": "2026-01-27T02:33:46.865Z",
    "size": 6125,
    "path": "../public/storage/images/badge/bronzec-b.png"
  },
  "/storage/images/badge/bronzec-s.png": {
    "type": "image/png",
    "etag": "\"adb-5kkWxH3Ei8mkfALfUkmHh+Lrj9s\"",
    "mtime": "2026-01-27T02:33:46.865Z",
    "size": 2779,
    "path": "../public/storage/images/badge/bronzec-s.png"
  },
  "/storage/images/badge/caffeinated-b.png": {
    "type": "image/png",
    "etag": "\"16c0-EUy7vf9KXpDIg1aR7dKW9SNnIhY\"",
    "mtime": "2026-01-27T02:33:46.865Z",
    "size": 5824,
    "path": "../public/storage/images/badge/caffeinated-b.png"
  },
  "/storage/images/badge/caffeinated-s.png": {
    "type": "image/png",
    "etag": "\"a2c-O8EcLjHQhWTFxu1thslE6xxVdWQ\"",
    "mtime": "2026-01-27T02:33:46.866Z",
    "size": 2604,
    "path": "../public/storage/images/badge/caffeinated-s.png"
  },
  "/storage/images/badge/collector-b.png": {
    "type": "image/png",
    "etag": "\"1763-GLQxTcvH9QnvsCpMi5lo1RRRRoI\"",
    "mtime": "2026-01-27T02:33:46.866Z",
    "size": 5987,
    "path": "../public/storage/images/badge/collector-b.png"
  },
  "/storage/images/badge/collector-s.png": {
    "type": "image/png",
    "etag": "\"ab8-JL3lYNrcYQjqirOI7osyaGk4neE\"",
    "mtime": "2026-01-27T02:33:46.866Z",
    "size": 2744,
    "path": "../public/storage/images/badge/collector-s.png"
  },
  "/storage/images/badge/completedq.png": {
    "type": "image/png",
    "etag": "\"8ef-ubEJw2I/12qUXAkN1Dp6R5pir7M\"",
    "mtime": "2026-01-27T02:33:46.867Z",
    "size": 2287,
    "path": "../public/storage/images/badge/completedq.png"
  },
  "/storage/images/badge/fcultivator-b.png": {
    "type": "image/png",
    "etag": "\"174d-lr/Mug+SbBBWdzAeeUEonLMK7bQ\"",
    "mtime": "2026-01-27T02:33:46.867Z",
    "size": 5965,
    "path": "../public/storage/images/badge/fcultivator-b.png"
  },
  "/storage/images/badge/fcultivator-s.png": {
    "type": "image/png",
    "etag": "\"a9d-O8gw78I6SC75fb8U72qKVo5bxkg\"",
    "mtime": "2026-01-27T02:33:46.867Z",
    "size": 2717,
    "path": "../public/storage/images/badge/fcultivator-s.png"
  },
  "/storage/images/badge/forumsf-b.png": {
    "type": "image/png",
    "etag": "\"1702-7X3+fjhUQiHGcLmJifq733lFU8Y\"",
    "mtime": "2026-01-27T02:33:46.868Z",
    "size": 5890,
    "path": "../public/storage/images/badge/forumsf-b.png"
  },
  "/storage/images/badge/forumsf-s.png": {
    "type": "image/png",
    "etag": "\"a50-1uO9SX4mHyz7zJSXZKcD95PXDlM\"",
    "mtime": "2026-01-27T02:33:46.868Z",
    "size": 2640,
    "path": "../public/storage/images/badge/forumsf-s.png"
  },
  "/storage/images/badge/gempost-b.png": {
    "type": "image/png",
    "etag": "\"1696-iZXK1xM7gR1nFSeM5WhsTIGQ6rA\"",
    "mtime": "2026-01-27T02:33:46.868Z",
    "size": 5782,
    "path": "../public/storage/images/badge/gempost-b.png"
  },
  "/storage/images/badge/gempost-s.png": {
    "type": "image/png",
    "etag": "\"a62-gn9MBqQ2fAnUgLsBMFTog0077x8\"",
    "mtime": "2026-01-27T02:33:46.869Z",
    "size": 2658,
    "path": "../public/storage/images/badge/gempost-s.png"
  },
  "/storage/images/badge/globet-b.png": {
    "type": "image/png",
    "etag": "\"1fe3-psoSJ2JOYCp5F20648VfvLnqhY8\"",
    "mtime": "2026-01-27T02:33:46.869Z",
    "size": 8163,
    "path": "../public/storage/images/badge/globet-b.png"
  },
  "/storage/images/badge/globet-s.png": {
    "type": "image/png",
    "etag": "\"c20-Lj4+MiJzZl/TMIfM725l+4sMD1o\"",
    "mtime": "2026-01-27T02:33:46.869Z",
    "size": 3104,
    "path": "../public/storage/images/badge/globet-s.png"
  },
  "/storage/images/badge/gold-b.png": {
    "type": "image/png",
    "etag": "\"171f-5P2jrhIN+/YV85wsU9h/1tT82Zg\"",
    "mtime": "2026-01-27T02:33:46.870Z",
    "size": 5919,
    "path": "../public/storage/images/badge/gold-b.png"
  },
  "/storage/images/badge/gold-s.png": {
    "type": "image/png",
    "etag": "\"a73-iuXeuuVljdwLqSGEnHZScVVtQ6s\"",
    "mtime": "2026-01-27T02:33:46.870Z",
    "size": 2675,
    "path": "../public/storage/images/badge/gold-s.png"
  },
  "/storage/images/badge/goldc-s.png": {
    "type": "image/png",
    "etag": "\"aba-aTMCxx+fCLZuCp4lQ6qp5+Tw2oo\"",
    "mtime": "2026-01-27T02:33:46.870Z",
    "size": 2746,
    "path": "../public/storage/images/badge/goldc-s.png"
  },
  "/storage/images/badge/goldc.png": {
    "type": "image/png",
    "etag": "\"17e7-Nkeu30D6EdIGJReoc8YIBXIV2Oo\"",
    "mtime": "2026-01-27T02:33:46.871Z",
    "size": 6119,
    "path": "../public/storage/images/badge/goldc.png"
  },
  "/storage/images/badge/level-badge.png": {
    "type": "image/png",
    "etag": "\"236e-/BzUisLVOdz1YFq64NMOZQm3wvY\"",
    "mtime": "2026-01-27T02:33:46.871Z",
    "size": 9070,
    "path": "../public/storage/images/badge/level-badge.png"
  },
  "/storage/images/badge/liked-b.png": {
    "type": "image/png",
    "etag": "\"1532-jsFPSSFsUmgNfykhwdDaAaaQhsQ\"",
    "mtime": "2026-01-27T02:33:46.871Z",
    "size": 5426,
    "path": "../public/storage/images/badge/liked-b.png"
  },
  "/storage/images/badge/liked-s.png": {
    "type": "image/png",
    "etag": "\"9cb-BFFddmgRSpYI32zxSa7UJk+7T/4\"",
    "mtime": "2026-01-27T02:33:46.872Z",
    "size": 2507,
    "path": "../public/storage/images/badge/liked-s.png"
  },
  "/storage/images/badge/marketeer-b.png": {
    "type": "image/png",
    "etag": "\"1bd0-wPC9KgG4+yKxUCOZqJmZ7owhfG8\"",
    "mtime": "2026-01-27T02:33:46.872Z",
    "size": 7120,
    "path": "../public/storage/images/badge/marketeer-b.png"
  },
  "/storage/images/badge/marketeer-s.png": {
    "type": "image/png",
    "etag": "\"bb3-+2+F3sGJbnj4MELIs54dpsBQQ/A\"",
    "mtime": "2026-01-27T02:33:46.872Z",
    "size": 2995,
    "path": "../public/storage/images/badge/marketeer-s.png"
  },
  "/storage/images/badge/mightiers-b.png": {
    "type": "image/png",
    "etag": "\"15bc-2j/f7UTURHgP/ta0LiJhTR4PXLg\"",
    "mtime": "2026-01-27T02:33:46.872Z",
    "size": 5564,
    "path": "../public/storage/images/badge/mightiers-b.png"
  },
  "/storage/images/badge/mightiers-s.png": {
    "type": "image/png",
    "etag": "\"a7f-/FYj1UAuCcajjiYGFeRHq+gp3Fk\"",
    "mtime": "2026-01-27T02:33:46.873Z",
    "size": 2687,
    "path": "../public/storage/images/badge/mightiers-s.png"
  },
  "/storage/images/badge/ncreature-b.png": {
    "type": "image/png",
    "etag": "\"1892-ZMgXqUG4XJwest05B7BM84KaC4E\"",
    "mtime": "2026-01-27T02:33:46.873Z",
    "size": 6290,
    "path": "../public/storage/images/badge/ncreature-b.png"
  },
  "/storage/images/badge/ncreature-s.png": {
    "type": "image/png",
    "etag": "\"ab9-XCfajmJLafMzEq0SL4A7EHYtIkU\"",
    "mtime": "2026-01-27T02:33:46.874Z",
    "size": 2745,
    "path": "../public/storage/images/badge/ncreature-s.png"
  },
  "/storage/images/badge/peoplesp-b.png": {
    "type": "image/png",
    "etag": "\"1c29-OKrzpfjGAEpk6oY4WtFzA5pNf4A\"",
    "mtime": "2026-01-27T02:33:46.874Z",
    "size": 7209,
    "path": "../public/storage/images/badge/peoplesp-b.png"
  },
  "/storage/images/badge/peoplesp-s.png": {
    "type": "image/png",
    "etag": "\"bb3-ecFoYfBmRJgjEuoF7TAAH7UcZmw\"",
    "mtime": "2026-01-27T02:33:46.874Z",
    "size": 2995,
    "path": "../public/storage/images/badge/peoplesp-s.png"
  },
  "/storage/images/badge/phantom-b.png": {
    "type": "image/png",
    "etag": "\"17b8-GiBRGWeNOnaqZoIfw9YMhXFGPF4\"",
    "mtime": "2026-01-27T02:33:46.875Z",
    "size": 6072,
    "path": "../public/storage/images/badge/phantom-b.png"
  },
  "/storage/images/badge/phantom-s.png": {
    "type": "image/png",
    "etag": "\"aa5-CUp1PQUbrR2qL0emvH4xzjYT+iU\"",
    "mtime": "2026-01-27T02:33:46.875Z",
    "size": 2725,
    "path": "../public/storage/images/badge/phantom-s.png"
  },
  "/storage/images/badge/platinum-b.png": {
    "type": "image/png",
    "etag": "\"196a-uk6rUH0Sc9cQrwHLnHwYq8iG/kc\"",
    "mtime": "2026-01-27T02:33:46.875Z",
    "size": 6506,
    "path": "../public/storage/images/badge/platinum-b.png"
  },
  "/storage/images/badge/platinum-s.png": {
    "type": "image/png",
    "etag": "\"b49-qU5tXAcvQcPmHzo4tz3gASMYYo8\"",
    "mtime": "2026-01-27T02:33:46.876Z",
    "size": 2889,
    "path": "../public/storage/images/badge/platinum-s.png"
  },
  "/storage/images/badge/platinumc-b.png": {
    "type": "image/png",
    "etag": "\"19b0-efGDcpV2zLUbbCPj+Z83MLYFM2M\"",
    "mtime": "2026-01-27T02:33:46.876Z",
    "size": 6576,
    "path": "../public/storage/images/badge/platinumc-b.png"
  },
  "/storage/images/badge/platinumc-s.png": {
    "type": "image/png",
    "etag": "\"b6c-rEwUS/FHSGGsWW3Nv1h/hLvhVH4\"",
    "mtime": "2026-01-27T02:33:46.877Z",
    "size": 2924,
    "path": "../public/storage/images/badge/platinumc-s.png"
  },
  "/storage/images/badge/prophoto-b.png": {
    "type": "image/png",
    "etag": "\"1808-AwkZxpqPVjTF4/BPim1nhyD3nVE\"",
    "mtime": "2026-01-27T02:33:46.877Z",
    "size": 6152,
    "path": "../public/storage/images/badge/prophoto-b.png"
  },
  "/storage/images/badge/prophoto-s.png": {
    "type": "image/png",
    "etag": "\"a5c-qaEkUtH/H1KL7C70mNQ80Cy3SnI\"",
    "mtime": "2026-01-27T02:33:46.877Z",
    "size": 2652,
    "path": "../public/storage/images/badge/prophoto-s.png"
  },
  "/storage/images/badge/qconq-b.png": {
    "type": "image/png",
    "etag": "\"1d67-+RAyAHqg29fYc6Rhfr5GV+Pobvk\"",
    "mtime": "2026-01-27T02:33:46.878Z",
    "size": 7527,
    "path": "../public/storage/images/badge/qconq-b.png"
  },
  "/storage/images/badge/qconq-s.png": {
    "type": "image/png",
    "etag": "\"bf5-EH2EBzzZkrYqnUHKx5+MoL3b4LI\"",
    "mtime": "2026-01-27T02:33:46.879Z",
    "size": 3061,
    "path": "../public/storage/images/badge/qconq-s.png"
  },
  "/storage/images/badge/rmachine-b.png": {
    "type": "image/png",
    "etag": "\"1caa-PQfKLqClERRHSsfituIDE7ZYpIs\"",
    "mtime": "2026-01-27T02:33:46.879Z",
    "size": 7338,
    "path": "../public/storage/images/badge/rmachine-b.png"
  },
  "/storage/images/badge/rmachine-s.png": {
    "type": "image/png",
    "etag": "\"bce-Tkyh+duaKRm35I2Ola6/i7ywm3Q\"",
    "mtime": "2026-01-27T02:33:46.879Z",
    "size": 3022,
    "path": "../public/storage/images/badge/rmachine-s.png"
  },
  "/storage/images/badge/rulerm-b.png": {
    "type": "image/png",
    "etag": "\"197c-6LsmUbgJDH4aDSX3Yf9JzRaz9cU\"",
    "mtime": "2026-01-27T02:33:46.879Z",
    "size": 6524,
    "path": "../public/storage/images/badge/rulerm-b.png"
  },
  "/storage/images/badge/rulerm-s.png": {
    "type": "image/png",
    "etag": "\"b38-h+mOiMB9LnvueT//h7uxEgX2jt8\"",
    "mtime": "2026-01-27T02:33:46.880Z",
    "size": 2872,
    "path": "../public/storage/images/badge/rulerm-s.png"
  },
  "/storage/images/badge/scientist-b.png": {
    "type": "image/png",
    "etag": "\"175a-NJ8Bzv3Y35pSs2mJPDNPNq5CJtE\"",
    "mtime": "2026-01-27T02:33:46.880Z",
    "size": 5978,
    "path": "../public/storage/images/badge/scientist-b.png"
  },
  "/storage/images/badge/scientist-s.png": {
    "type": "image/png",
    "etag": "\"a8d-n0p0J3XgKCh30pf4vcdUZDpuyYQ\"",
    "mtime": "2026-01-27T02:33:46.880Z",
    "size": 2701,
    "path": "../public/storage/images/badge/scientist-s.png"
  },
  "/storage/images/badge/silver-b.png": {
    "type": "image/png",
    "etag": "\"1783-swMuTcSba20JUOQ/GNgNX1dihDg\"",
    "mtime": "2026-01-27T02:33:46.881Z",
    "size": 6019,
    "path": "../public/storage/images/badge/silver-b.png"
  },
  "/storage/images/badge/silver-s.png": {
    "type": "image/png",
    "etag": "\"a85-GiLDNtS/YA4D/NoeH2ADN84+5QA\"",
    "mtime": "2026-01-27T02:33:46.881Z",
    "size": 2693,
    "path": "../public/storage/images/badge/silver-s.png"
  },
  "/storage/images/badge/silverc-b.png": {
    "type": "image/png",
    "etag": "\"184e-kryGn9eH+0zAbfgznGbymDYONzM\"",
    "mtime": "2026-01-27T02:33:46.882Z",
    "size": 6222,
    "path": "../public/storage/images/badge/silverc-b.png"
  },
  "/storage/images/badge/silverc-s.png": {
    "type": "image/png",
    "etag": "\"aea-aSUppUttCXbsMIYj90U1lvCu/ow\"",
    "mtime": "2026-01-27T02:33:46.882Z",
    "size": 2794,
    "path": "../public/storage/images/badge/silverc-s.png"
  },
  "/storage/images/badge/sloved-b.png": {
    "type": "image/png",
    "etag": "\"187e-7bmNXL3llylM//jhBGz+QtkWcqY\"",
    "mtime": "2026-01-27T02:33:46.882Z",
    "size": 6270,
    "path": "../public/storage/images/badge/sloved-b.png"
  },
  "/storage/images/badge/sloved-s.png": {
    "type": "image/png",
    "etag": "\"aee-aSXv8Uyaj0Xmkbbl5bMDmwL0EA8\"",
    "mtime": "2026-01-27T02:33:46.883Z",
    "size": 2798,
    "path": "../public/storage/images/badge/sloved-s.png"
  },
  "/storage/images/badge/splanner-b.png": {
    "type": "image/png",
    "etag": "\"1160-elYX2XEK+pdCTj9T3G4palvlxyA\"",
    "mtime": "2026-01-27T02:33:46.883Z",
    "size": 4448,
    "path": "../public/storage/images/badge/splanner-b.png"
  },
  "/storage/images/badge/splanner-s.png": {
    "type": "image/png",
    "etag": "\"91a-0pmmBkaIm6Y9/yc+6VGSZvyVKno\"",
    "mtime": "2026-01-27T02:33:46.883Z",
    "size": 2330,
    "path": "../public/storage/images/badge/splanner-s.png"
  },
  "/storage/images/badge/Thumbs.db": {
    "type": "text/plain; charset=utf-8",
    "etag": "\"31800-kZl8SBzmMp3qekSHSfDa8s8sc70\"",
    "mtime": "2026-01-27T02:33:46.862Z",
    "size": 202752,
    "path": "../public/storage/images/badge/Thumbs.db"
  },
  "/storage/images/badge/traveller-b.png": {
    "type": "image/png",
    "etag": "\"146c-FYYloG/8gC5w30ZinfL6gviwUQc\"",
    "mtime": "2026-01-27T02:33:46.884Z",
    "size": 5228,
    "path": "../public/storage/images/badge/traveller-b.png"
  },
  "/storage/images/badge/traveller-s.png": {
    "type": "image/png",
    "etag": "\"9ee-etu7VTcCmvohe5r6Dhi32h7CdOY\"",
    "mtime": "2026-01-27T02:33:46.884Z",
    "size": 2542,
    "path": "../public/storage/images/badge/traveller-s.png"
  },
  "/storage/images/badge/tstruck-b.png": {
    "type": "image/png",
    "etag": "\"145e-+TS57okmKRdPUjBsEaCOZBhUV90\"",
    "mtime": "2026-01-27T02:33:46.884Z",
    "size": 5214,
    "path": "../public/storage/images/badge/tstruck-b.png"
  },
  "/storage/images/badge/tstruck-s.png": {
    "type": "image/png",
    "etag": "\"a02-16A1ojfn/o0+g+Ttgb3T1JbfU18\"",
    "mtime": "2026-01-27T02:33:46.885Z",
    "size": 2562,
    "path": "../public/storage/images/badge/tstruck-s.png"
  },
  "/storage/images/badge/tycoon-s.png": {
    "type": "image/png",
    "etag": "\"b01-AITzcHvzqUlaqYhZYYQKHeT+7g0\"",
    "mtime": "2026-01-27T02:33:46.885Z",
    "size": 2817,
    "path": "../public/storage/images/badge/tycoon-s.png"
  },
  "/storage/images/badge/tycoon.png": {
    "type": "image/png",
    "etag": "\"18b4-lFKPx6Vqf/xwlRnM3pVAVxjFBu4\"",
    "mtime": "2026-01-27T02:33:46.885Z",
    "size": 6324,
    "path": "../public/storage/images/badge/tycoon.png"
  },
  "/storage/images/badge/uexp-b.png": {
    "type": "image/png",
    "etag": "\"19f1-AJ9ce8O7BjP/nTibAs4YhCsO7bg\"",
    "mtime": "2026-01-27T02:33:46.886Z",
    "size": 6641,
    "path": "../public/storage/images/badge/uexp-b.png"
  },
  "/storage/images/badge/uexp-s.png": {
    "type": "image/png",
    "etag": "\"b4c-IHzoCJIUcNNjqPXC5rUxpzX5dIk\"",
    "mtime": "2026-01-27T02:33:46.886Z",
    "size": 2892,
    "path": "../public/storage/images/badge/uexp-s.png"
  },
  "/storage/images/badge/unlocked-badge.png": {
    "type": "image/png",
    "etag": "\"87a-2dIjIOfKAkR5MDdpZPt/liItQvI\"",
    "mtime": "2026-01-27T02:33:46.886Z",
    "size": 2170,
    "path": "../public/storage/images/badge/unlocked-badge.png"
  },
  "/storage/images/badge/upowered-b.png": {
    "type": "image/png",
    "etag": "\"1936-/bvB1h68wZ/4bIrzgvfP6ZsvnFc\"",
    "mtime": "2026-01-27T02:33:46.886Z",
    "size": 6454,
    "path": "../public/storage/images/badge/upowered-b.png"
  },
  "/storage/images/badge/upowered-s.png": {
    "type": "image/png",
    "etag": "\"aa4-YWc7U2h4buYAG9k7Dy/yI73m5dc\"",
    "mtime": "2026-01-27T02:33:46.887Z",
    "size": 2724,
    "path": "../public/storage/images/badge/upowered-s.png"
  },
  "/storage/images/badge/verifieds-b.png": {
    "type": "image/png",
    "etag": "\"1736-o2TzqtDvtpVYCT68Mm7d2okCuMQ\"",
    "mtime": "2026-01-27T02:33:46.887Z",
    "size": 5942,
    "path": "../public/storage/images/badge/verifieds-b.png"
  },
  "/storage/images/badge/verifieds-s.png": {
    "type": "image/png",
    "etag": "\"a9a-arkvia/ZvYnGiKLjZ2KnCGnyMGI\"",
    "mtime": "2026-01-27T02:33:46.887Z",
    "size": 2714,
    "path": "../public/storage/images/badge/verifieds-s.png"
  },
  "/storage/images/badge/villain-b.png": {
    "type": "image/png",
    "etag": "\"1a64-BnL58o8kZR9Ms3OhS6iCGrUB0bw\"",
    "mtime": "2026-01-27T02:33:46.888Z",
    "size": 6756,
    "path": "../public/storage/images/badge/villain-b.png"
  },
  "/storage/images/badge/villain-s.png": {
    "type": "image/png",
    "etag": "\"ae7-Kihx+mM/GY8/to21tSYNjynpGlM\"",
    "mtime": "2026-01-27T02:33:46.888Z",
    "size": 2791,
    "path": "../public/storage/images/badge/villain-s.png"
  },
  "/storage/images/badge/warrior-b.png": {
    "type": "image/png",
    "etag": "\"1520-DHrDgvDertKWCVT2TPbIwjXP0PU\"",
    "mtime": "2026-01-27T02:33:46.889Z",
    "size": 5408,
    "path": "../public/storage/images/badge/warrior-b.png"
  },
  "/storage/images/badge/warrior-s.png": {
    "type": "image/png",
    "etag": "\"98f-tOMW5pMDSv4NHMTRSAfbPMVUCyg\"",
    "mtime": "2026-01-27T02:33:46.889Z",
    "size": 2447,
    "path": "../public/storage/images/badge/warrior-s.png"
  },
  "/storage/images/banner/accounthub-icon.png": {
    "type": "image/png",
    "etag": "\"3bbb-Ba64qxEsgSSyxLNRZrS88obxdV8\"",
    "mtime": "2026-01-27T02:33:46.891Z",
    "size": 15291,
    "path": "../public/storage/images/banner/accounthub-icon.png"
  },
  "/storage/images/banner/badges-icon.png": {
    "type": "image/png",
    "etag": "\"41a9-BVZ3jKxz3/pD0Ac+e+7iVajAXBw\"",
    "mtime": "2026-01-27T02:33:46.891Z",
    "size": 16809,
    "path": "../public/storage/images/banner/badges-icon.png"
  },
  "/storage/images/banner/banner-bg.png": {
    "type": "image/png",
    "etag": "\"12d9c-hJc+kqxtqeXkOokHEpi1YOSHfOM\"",
    "mtime": "2026-01-27T02:33:46.897Z",
    "size": 77212,
    "path": "../public/storage/images/banner/banner-bg.png"
  },
  "/storage/images/banner/banner-commenter.jpg": {
    "type": "image/jpeg",
    "etag": "\"1a5f-yQEbsF/OXlA+muiWFeZfS5Oetp0\"",
    "mtime": "2026-01-27T02:33:46.897Z",
    "size": 6751,
    "path": "../public/storage/images/banner/banner-commenter.jpg"
  },
  "/storage/images/banner/banner-promo.jpg": {
    "type": "image/jpeg",
    "etag": "\"a9d2-E/rxkwero/v2Mq78GV8T7hxA8F0\"",
    "mtime": "2026-01-27T02:33:46.898Z",
    "size": 43474,
    "path": "../public/storage/images/banner/banner-promo.jpg"
  },
  "/storage/images/banner/banner-profile-stats.jpg": {
    "type": "image/jpeg",
    "etag": "\"690e-qkClbbDg7SFpDX+KsNWgnmagILI\"",
    "mtime": "2026-01-27T02:33:46.898Z",
    "size": 26894,
    "path": "../public/storage/images/banner/banner-profile-stats.jpg"
  },
  "/storage/images/banner/banner-reaction.jpg": {
    "type": "image/jpeg",
    "etag": "\"3504-QWhINYW99tLpKVFdQs/WVvtfIUk\"",
    "mtime": "2026-01-27T02:33:46.899Z",
    "size": 13572,
    "path": "../public/storage/images/banner/banner-reaction.jpg"
  },
  "/storage/images/banner/events-icon.png": {
    "type": "image/png",
    "etag": "\"196b-RzcOERddMTLslqpT4FYKfLtN2a4\"",
    "mtime": "2026-01-27T02:33:46.899Z",
    "size": 6507,
    "path": "../public/storage/images/banner/events-icon.png"
  },
  "/storage/images/banner/forums-icon.png": {
    "type": "image/png",
    "etag": "\"21e0-lv7REk0zznyLAK1qz/357g3XGtM\"",
    "mtime": "2026-01-27T02:33:46.900Z",
    "size": 8672,
    "path": "../public/storage/images/banner/forums-icon.png"
  },
  "/storage/images/banner/groups-icon.png": {
    "type": "image/png",
    "etag": "\"4b49-30tXYMhoT4zjDQ1jHHNUwjRjr8U\"",
    "mtime": "2026-01-27T02:33:46.900Z",
    "size": 19273,
    "path": "../public/storage/images/banner/groups-icon.png"
  },
  "/storage/images/banner/members-icon.png": {
    "type": "image/png",
    "etag": "\"364a-R5KgPAjskejIibfJR89PAvU1ulQ\"",
    "mtime": "2026-01-27T02:33:46.901Z",
    "size": 13898,
    "path": "../public/storage/images/banner/members-icon.png"
  },
  "/storage/images/banner/marketplace-icon.png": {
    "type": "image/png",
    "etag": "\"22cf-RRxpMbpJLkSPpr/5kRATJ+zUjNU\"",
    "mtime": "2026-01-27T02:33:46.901Z",
    "size": 8911,
    "path": "../public/storage/images/banner/marketplace-icon.png"
  },
  "/storage/images/banner/newsfeed-icon.png": {
    "type": "image/png",
    "etag": "\"184b-tnRwoW5V0bl4GCdJf2LE/ySWYlw\"",
    "mtime": "2026-01-27T02:33:46.901Z",
    "size": 6219,
    "path": "../public/storage/images/banner/newsfeed-icon.png"
  },
  "/storage/images/banner/overview-icon.png": {
    "type": "image/png",
    "etag": "\"3b3c-9GtgIzwdBGL63FKr5x/wiF+IKD8\"",
    "mtime": "2026-01-27T02:33:46.902Z",
    "size": 15164,
    "path": "../public/storage/images/banner/overview-icon.png"
  },
  "/storage/images/banner/streams-icon.png": {
    "type": "image/png",
    "etag": "\"1011-r6uL6bnXikozPCLDLlzZ9QftfMQ\"",
    "mtime": "2026-01-27T02:33:46.903Z",
    "size": 4113,
    "path": "../public/storage/images/banner/streams-icon.png"
  },
  "/storage/images/banner/quests-icon.png": {
    "type": "image/png",
    "etag": "\"4514-i24++Kakb/LoGY75VtfQvW4BLg0\"",
    "mtime": "2026-01-27T02:33:46.902Z",
    "size": 17684,
    "path": "../public/storage/images/banner/quests-icon.png"
  },
  "/storage/images/banner/Thumbs.db": {
    "type": "text/plain; charset=utf-8",
    "etag": "\"de00-kQ8BM2sWwhPusdczBbmp1s2yT78\"",
    "mtime": "2026-01-27T02:33:46.890Z",
    "size": 56832,
    "path": "../public/storage/images/banner/Thumbs.db"
  },
  "/_nuxt/builds/meta/93d3b9bc-a83f-4c27-992c-9a162ba1c1c8.json": {
    "type": "application/json",
    "etag": "\"58-dG0v6V1vaQQV2oYZ7EkujwPzsjQ\"",
    "mtime": "2026-01-28T08:25:37.552Z",
    "size": 88,
    "path": "../public/_nuxt/builds/meta/93d3b9bc-a83f-4c27-992c-9a162ba1c1c8.json"
  },
  "/_nuxt/builds/meta/dev.json": {
    "type": "application/json",
    "etag": "\"37-2XmA+UoJc2EIZAwSg42pxLDOvnk\"",
    "mtime": "2026-01-28T08:24:46.907Z",
    "size": 55,
    "path": "../public/_nuxt/builds/meta/dev.json"
  }
};

const _DRIVE_LETTER_START_RE = /^[A-Za-z]:\//;
function normalizeWindowsPath(input = "") {
  if (!input) {
    return input;
  }
  return input.replace(/\\/g, "/").replace(_DRIVE_LETTER_START_RE, (r) => r.toUpperCase());
}
const _IS_ABSOLUTE_RE = /^[/\\](?![/\\])|^[/\\]{2}(?!\.)|^[A-Za-z]:[/\\]/;
const _DRIVE_LETTER_RE = /^[A-Za-z]:$/;
function cwd() {
  if (typeof process !== "undefined" && typeof process.cwd === "function") {
    return process.cwd().replace(/\\/g, "/");
  }
  return "/";
}
const resolve = function(...arguments_) {
  arguments_ = arguments_.map((argument) => normalizeWindowsPath(argument));
  let resolvedPath = "";
  let resolvedAbsolute = false;
  for (let index = arguments_.length - 1; index >= -1 && !resolvedAbsolute; index--) {
    const path = index >= 0 ? arguments_[index] : cwd();
    if (!path || path.length === 0) {
      continue;
    }
    resolvedPath = `${path}/${resolvedPath}`;
    resolvedAbsolute = isAbsolute(path);
  }
  resolvedPath = normalizeString(resolvedPath, !resolvedAbsolute);
  if (resolvedAbsolute && !isAbsolute(resolvedPath)) {
    return `/${resolvedPath}`;
  }
  return resolvedPath.length > 0 ? resolvedPath : ".";
};
function normalizeString(path, allowAboveRoot) {
  let res = "";
  let lastSegmentLength = 0;
  let lastSlash = -1;
  let dots = 0;
  let char = null;
  for (let index = 0; index <= path.length; ++index) {
    if (index < path.length) {
      char = path[index];
    } else if (char === "/") {
      break;
    } else {
      char = "/";
    }
    if (char === "/") {
      if (lastSlash === index - 1 || dots === 1) ; else if (dots === 2) {
        if (res.length < 2 || lastSegmentLength !== 2 || res[res.length - 1] !== "." || res[res.length - 2] !== ".") {
          if (res.length > 2) {
            const lastSlashIndex = res.lastIndexOf("/");
            if (lastSlashIndex === -1) {
              res = "";
              lastSegmentLength = 0;
            } else {
              res = res.slice(0, lastSlashIndex);
              lastSegmentLength = res.length - 1 - res.lastIndexOf("/");
            }
            lastSlash = index;
            dots = 0;
            continue;
          } else if (res.length > 0) {
            res = "";
            lastSegmentLength = 0;
            lastSlash = index;
            dots = 0;
            continue;
          }
        }
        if (allowAboveRoot) {
          res += res.length > 0 ? "/.." : "..";
          lastSegmentLength = 2;
        }
      } else {
        if (res.length > 0) {
          res += `/${path.slice(lastSlash + 1, index)}`;
        } else {
          res = path.slice(lastSlash + 1, index);
        }
        lastSegmentLength = index - lastSlash - 1;
      }
      lastSlash = index;
      dots = 0;
    } else if (char === "." && dots !== -1) {
      ++dots;
    } else {
      dots = -1;
    }
  }
  return res;
}
const isAbsolute = function(p) {
  return _IS_ABSOLUTE_RE.test(p);
};
const dirname = function(p) {
  const segments = normalizeWindowsPath(p).replace(/\/$/, "").split("/").slice(0, -1);
  if (segments.length === 1 && _DRIVE_LETTER_RE.test(segments[0])) {
    segments[0] += "/";
  }
  return segments.join("/") || (isAbsolute(p) ? "/" : ".");
};

function readAsset (id) {
  const serverDir = dirname(fileURLToPath(globalThis._importMeta_.url));
  return promises.readFile(resolve(serverDir, assets[id].path))
}

const publicAssetBases = {"/_nuxt/builds/meta/":{"maxAge":31536000},"/_nuxt/builds/":{"maxAge":1},"/_nuxt/":{"maxAge":31536000}};

function isPublicAssetURL(id = '') {
  if (assets[id]) {
    return true
  }
  for (const base in publicAssetBases) {
    if (id.startsWith(base)) { return true }
  }
  return false
}

function getAsset (id) {
  return assets[id]
}

const METHODS = /* @__PURE__ */ new Set(["HEAD", "GET"]);
const EncodingMap = { gzip: ".gz", br: ".br" };
const _fw6XNg = eventHandler((event) => {
  if (event.method && !METHODS.has(event.method)) {
    return;
  }
  let id = decodePath(
    withLeadingSlash(withoutTrailingSlash(parseURL(event.path).pathname))
  );
  let asset;
  const encodingHeader = String(
    getRequestHeader(event, "accept-encoding") || ""
  );
  const encodings = [
    ...encodingHeader.split(",").map((e) => EncodingMap[e.trim()]).filter(Boolean).sort(),
    ""
  ];
  for (const encoding of encodings) {
    for (const _id of [id + encoding, joinURL(id, "index.html" + encoding)]) {
      const _asset = getAsset(_id);
      if (_asset) {
        asset = _asset;
        id = _id;
        break;
      }
    }
  }
  if (!asset) {
    if (isPublicAssetURL(id)) {
      removeResponseHeader(event, "Cache-Control");
      throw createError$1({ statusCode: 404 });
    }
    return;
  }
  if (asset.encoding !== void 0) {
    appendResponseHeader(event, "Vary", "Accept-Encoding");
  }
  const ifNotMatch = getRequestHeader(event, "if-none-match") === asset.etag;
  if (ifNotMatch) {
    setResponseStatus(event, 304, "Not Modified");
    return "";
  }
  const ifModifiedSinceH = getRequestHeader(event, "if-modified-since");
  const mtimeDate = new Date(asset.mtime);
  if (ifModifiedSinceH && asset.mtime && new Date(ifModifiedSinceH) >= mtimeDate) {
    setResponseStatus(event, 304, "Not Modified");
    return "";
  }
  if (asset.type && !getResponseHeader(event, "Content-Type")) {
    setResponseHeader(event, "Content-Type", asset.type);
  }
  if (asset.etag && !getResponseHeader(event, "ETag")) {
    setResponseHeader(event, "ETag", asset.etag);
  }
  if (asset.mtime && !getResponseHeader(event, "Last-Modified")) {
    setResponseHeader(event, "Last-Modified", mtimeDate.toUTCString());
  }
  if (asset.encoding && !getResponseHeader(event, "Content-Encoding")) {
    setResponseHeader(event, "Content-Encoding", asset.encoding);
  }
  if (asset.size > 0 && !getResponseHeader(event, "Content-Length")) {
    setResponseHeader(event, "Content-Length", asset.size);
  }
  return readAsset(id);
});

const _zmbS_G = defineEventHandler(async (event) => {
  var _a;
  const path = event.path;
  if (!path.startsWith("/storage/")) {
    return;
  }
  const backendUrl = `http://localhost:8000${path}`;
  try {
    const response = await $fetch(backendUrl, {
      method: "GET",
      responseType: "arrayBuffer"
    });
    const ext = (_a = path.split(".").pop()) == null ? void 0 : _a.toLowerCase();
    const contentTypes = {
      "jpg": "image/jpeg",
      "jpeg": "image/jpeg",
      "png": "image/png",
      "gif": "image/gif",
      "webp": "image/webp",
      "svg": "image/svg+xml"
    };
    const contentType = contentTypes[ext || ""] || "application/octet-stream";
    event.node.res.setHeader("Content-Type", contentType);
    event.node.res.setHeader("Cache-Control", "public, max-age=31536000");
    return response;
  } catch (error) {
    console.error("Error proxying storage request:", error);
    throw createError$1({
      statusCode: 404,
      statusMessage: "File not found"
    });
  }
});

const _SxA8c9 = defineEventHandler(() => {});

const storage = prefixStorage(useStorage(), "i18n");
function cachedFunctionI18n(fn, opts) {
  opts = { maxAge: 1, ...opts };
  const pending = {};
  async function get(key, resolver) {
    const isPending = pending[key];
    if (!isPending) {
      pending[key] = Promise.resolve(resolver());
    }
    try {
      return await pending[key];
    } finally {
      delete pending[key];
    }
  }
  return async (...args) => {
    const key = [opts.name, opts.getKey(...args)].join(":").replace(/:\/$/, ":index");
    const maxAge = opts.maxAge ?? 1;
    const isCacheable = !opts.shouldBypassCache(...args) && maxAge >= 0;
    const cache = isCacheable && await storage.getItemRaw(key);
    if (!cache || cache.ttl < Date.now()) {
      pending[key] = Promise.resolve(fn(...args));
      const value = await get(key, () => fn(...args));
      if (isCacheable) {
        await storage.setItemRaw(key, { ttl: Date.now() + maxAge * 1e3, value, mtime: Date.now() });
      }
      return value;
    }
    return cache.value;
  };
}

const _getMessages = async (locale) => {
  return { [locale]: await getLocaleMessagesMerged(locale, localeLoaders[locale]) };
};
const _getMessagesCached = cachedFunctionI18n(_getMessages, {
  name: "messages",
  maxAge: 60 * 60 * 24,
  getKey: (locale) => locale,
  shouldBypassCache: (locale) => !isLocaleCacheable(locale)
});
const getMessages = _getMessagesCached;
const _getMergedMessages = async (locale, fallbackLocales) => {
  const merged = {};
  try {
    if (fallbackLocales.length > 0) {
      const messages = await Promise.all(fallbackLocales.map(getMessages));
      for (const message2 of messages) {
        deepCopy(message2, merged);
      }
    }
    const message = await getMessages(locale);
    deepCopy(message, merged);
    return merged;
  } catch (e) {
    throw new Error("Failed to merge messages: " + e.message);
  }
};
const getMergedMessages = cachedFunctionI18n(_getMergedMessages, {
  name: "merged-single",
  maxAge: 60 * 60 * 24,
  getKey: (locale, fallbackLocales) => `${locale}-[${[...new Set(fallbackLocales)].sort().join("-")}]`,
  shouldBypassCache: (locale, fallbackLocales) => !isLocaleWithFallbacksCacheable(locale, fallbackLocales)
});
const _getAllMergedMessages = async (locales) => {
  const merged = {};
  try {
    const messages = await Promise.all(locales.map(getMessages));
    for (const message of messages) {
      deepCopy(message, merged);
    }
    return merged;
  } catch (e) {
    throw new Error("Failed to merge messages: " + e.message);
  }
};
cachedFunctionI18n(_getAllMergedMessages, {
  name: "merged-all",
  maxAge: 60 * 60 * 24,
  getKey: (locales) => locales.join("-"),
  shouldBypassCache: (locales) => !locales.every((locale) => isLocaleCacheable(locale))
});

const _messagesHandler = defineEventHandler(async (event) => {
  const locale = getRouterParam(event, "locale");
  if (!locale) {
    throw createError$1({ status: 400, message: "Locale not specified." });
  }
  const ctx = useI18nContext(event);
  if (ctx.localeConfigs && locale in ctx.localeConfigs === false) {
    throw createError$1({ status: 404, message: `Locale '${locale}' not found.` });
  }
  const messages = await getMergedMessages(locale, ctx.localeConfigs?.[locale]?.fallbacks ?? []);
  deepCopy(messages, ctx.messages);
  return ctx.messages;
});
const _cachedMessageLoader = defineCachedFunction(_messagesHandler, {
  name: "i18n:messages-internal",
  maxAge: 60 * 60 * 24,
  getKey: (event) => [getRouterParam(event, "locale") ?? "null", getRouterParam(event, "hash") ?? "null"].join("-"),
  async shouldBypassCache(event) {
    const locale = getRouterParam(event, "locale");
    if (locale == null) {
      return false;
    }
    const ctx = tryUseI18nContext(event) || await initializeI18nContext(event);
    return !ctx.localeConfigs?.[locale]?.cacheable;
  }
});
const _messagesHandlerCached = defineCachedEventHandler(_cachedMessageLoader, {
  name: "i18n:messages",
  maxAge: 10,
  swr: false,
  getKey: (event) => [getRouterParam(event, "locale") ?? "null", getRouterParam(event, "hash") ?? "null"].join("-")
});
const _QbpUAv = _messagesHandlerCached;

const _lazy_LiabGc = () => import('../routes/api/login.mjs');
const _lazy_Ez9s2t = () => import('../routes/api/logout.mjs');
const _lazy_WDCUWI = () => import('../routes/api/me.mjs');
const _lazy_kL2GFG = () => import('../routes/api/newsfeed.mjs');
const _lazy_UQ32dN = () => import('../routes/api/refresh.mjs');
const _lazy_4P7JCh = () => import('../routes/api/register.mjs');
const _lazy_MvvTD5 = () => import('../routes/renderer.mjs');

const handlers = [
  { route: '', handler: _fw6XNg, lazy: false, middleware: true, method: undefined },
  { route: '', handler: _zmbS_G, lazy: false, middleware: true, method: undefined },
  { route: '/api/login', handler: _lazy_LiabGc, lazy: true, middleware: false, method: undefined },
  { route: '/api/logout', handler: _lazy_Ez9s2t, lazy: true, middleware: false, method: undefined },
  { route: '/api/me', handler: _lazy_WDCUWI, lazy: true, middleware: false, method: undefined },
  { route: '/api/newsfeed', handler: _lazy_kL2GFG, lazy: true, middleware: false, method: undefined },
  { route: '/api/refresh', handler: _lazy_UQ32dN, lazy: true, middleware: false, method: undefined },
  { route: '/api/register', handler: _lazy_4P7JCh, lazy: true, middleware: false, method: undefined },
  { route: '/__nuxt_error', handler: _lazy_MvvTD5, lazy: true, middleware: false, method: undefined },
  { route: '/__nuxt_island/**', handler: _SxA8c9, lazy: false, middleware: false, method: undefined },
  { route: '/_i18n/:hash/:locale/messages.json', handler: _QbpUAv, lazy: false, middleware: false, method: undefined },
  { route: '/**', handler: _lazy_MvvTD5, lazy: true, middleware: false, method: undefined }
];

function createNitroApp() {
  const config = useRuntimeConfig();
  const hooks = createHooks();
  const captureError = (error, context = {}) => {
    const promise = hooks.callHookParallel("error", error, context).catch((error_) => {
      console.error("Error while capturing another error", error_);
    });
    if (context.event && isEvent(context.event)) {
      const errors = context.event.context.nitro?.errors;
      if (errors) {
        errors.push({ error, context });
      }
      if (context.event.waitUntil) {
        context.event.waitUntil(promise);
      }
    }
  };
  const h3App = createApp({
    debug: destr(false),
    onError: (error, event) => {
      captureError(error, { event, tags: ["request"] });
      return errorHandler(error, event);
    },
    onRequest: async (event) => {
      event.context.nitro = event.context.nitro || { errors: [] };
      const fetchContext = event.node.req?.__unenv__;
      if (fetchContext?._platform) {
        event.context = {
          _platform: fetchContext?._platform,
          // #3335
          ...fetchContext._platform,
          ...event.context
        };
      }
      if (!event.context.waitUntil && fetchContext?.waitUntil) {
        event.context.waitUntil = fetchContext.waitUntil;
      }
      event.fetch = (req, init) => fetchWithEvent(event, req, init, { fetch: localFetch });
      event.$fetch = (req, init) => fetchWithEvent(event, req, init, {
        fetch: $fetch
      });
      event.waitUntil = (promise) => {
        if (!event.context.nitro._waitUntilPromises) {
          event.context.nitro._waitUntilPromises = [];
        }
        event.context.nitro._waitUntilPromises.push(promise);
        if (event.context.waitUntil) {
          event.context.waitUntil(promise);
        }
      };
      event.captureError = (error, context) => {
        captureError(error, { event, ...context });
      };
      await nitroApp.hooks.callHook("request", event).catch((error) => {
        captureError(error, { event, tags: ["request"] });
      });
    },
    onBeforeResponse: async (event, response) => {
      await nitroApp.hooks.callHook("beforeResponse", event, response).catch((error) => {
        captureError(error, { event, tags: ["request", "response"] });
      });
    },
    onAfterResponse: async (event, response) => {
      await nitroApp.hooks.callHook("afterResponse", event, response).catch((error) => {
        captureError(error, { event, tags: ["request", "response"] });
      });
    }
  });
  const router = createRouter({
    preemptive: true
  });
  const nodeHandler = toNodeListener(h3App);
  const localCall = (aRequest) => b(
    nodeHandler,
    aRequest
  );
  const localFetch = (input, init) => {
    if (!input.toString().startsWith("/")) {
      return globalThis.fetch(input, init);
    }
    return C(
      nodeHandler,
      input,
      init
    ).then((response) => normalizeFetchResponse(response));
  };
  const $fetch = createFetch({
    fetch: localFetch,
    Headers: Headers$1,
    defaults: { baseURL: config.app.baseURL }
  });
  globalThis.$fetch = $fetch;
  h3App.use(createRouteRulesHandler({ localFetch }));
  for (const h of handlers) {
    let handler = h.lazy ? lazyEventHandler(h.handler) : h.handler;
    if (h.middleware || !h.route) {
      const middlewareBase = (config.app.baseURL + (h.route || "/")).replace(
        /\/+/g,
        "/"
      );
      h3App.use(middlewareBase, handler);
    } else {
      const routeRules = getRouteRulesForPath(
        h.route.replace(/:\w+|\*\*/g, "_")
      );
      if (routeRules.cache) {
        handler = cachedEventHandler(handler, {
          group: "nitro/routes",
          ...routeRules.cache
        });
      }
      router.use(h.route, handler, h.method);
    }
  }
  h3App.use(config.app.baseURL, router.handler);
  const app = {
    hooks,
    h3App,
    router,
    localCall,
    localFetch,
    captureError
  };
  return app;
}
function runNitroPlugins(nitroApp2) {
  for (const plugin of plugins) {
    try {
      plugin(nitroApp2);
    } catch (error) {
      nitroApp2.captureError(error, { tags: ["plugin"] });
      throw error;
    }
  }
}
const nitroApp = createNitroApp();
function useNitroApp() {
  return nitroApp;
}
runNitroPlugins(nitroApp);

const debug = (...args) => {
};
function GracefulShutdown(server, opts) {
  opts = opts || {};
  const options = Object.assign(
    {
      signals: "SIGINT SIGTERM",
      timeout: 3e4,
      development: false,
      forceExit: true,
      onShutdown: (signal) => Promise.resolve(signal),
      preShutdown: (signal) => Promise.resolve(signal)
    },
    opts
  );
  let isShuttingDown = false;
  const connections = {};
  let connectionCounter = 0;
  const secureConnections = {};
  let secureConnectionCounter = 0;
  let failed = false;
  let finalRun = false;
  function onceFactory() {
    let called = false;
    return (emitter, events, callback) => {
      function call() {
        if (!called) {
          called = true;
          return Reflect.apply(callback, this, arguments);
        }
      }
      for (const e of events) {
        emitter.on(e, call);
      }
    };
  }
  const signals = options.signals.split(" ").map((s) => s.trim()).filter((s) => s.length > 0);
  const once = onceFactory();
  once(process, signals, (signal) => {
    debug("received shut down signal", signal);
    shutdown(signal).then(() => {
      if (options.forceExit) {
        process.exit(failed ? 1 : 0);
      }
    }).catch((error) => {
      debug("server shut down error occurred", error);
      process.exit(1);
    });
  });
  function isFunction(functionToCheck) {
    const getType = Object.prototype.toString.call(functionToCheck);
    return /^\[object\s([A-Za-z]+)?Function]$/.test(getType);
  }
  function destroy(socket, force = false) {
    if (socket._isIdle && isShuttingDown || force) {
      socket.destroy();
      if (socket.server instanceof http.Server) {
        delete connections[socket._connectionId];
      } else {
        delete secureConnections[socket._connectionId];
      }
    }
  }
  function destroyAllConnections(force = false) {
    debug("Destroy Connections : " + (force ? "forced close" : "close"));
    let counter = 0;
    let secureCounter = 0;
    for (const key of Object.keys(connections)) {
      const socket = connections[key];
      const serverResponse = socket._httpMessage;
      if (serverResponse && !force) {
        if (!serverResponse.headersSent) {
          serverResponse.setHeader("connection", "close");
        }
      } else {
        counter++;
        destroy(socket);
      }
    }
    debug("Connections destroyed : " + counter);
    debug("Connection Counter    : " + connectionCounter);
    for (const key of Object.keys(secureConnections)) {
      const socket = secureConnections[key];
      const serverResponse = socket._httpMessage;
      if (serverResponse && !force) {
        if (!serverResponse.headersSent) {
          serverResponse.setHeader("connection", "close");
        }
      } else {
        secureCounter++;
        destroy(socket);
      }
    }
    debug("Secure Connections destroyed : " + secureCounter);
    debug("Secure Connection Counter    : " + secureConnectionCounter);
  }
  server.on("request", (req, res) => {
    req.socket._isIdle = false;
    if (isShuttingDown && !res.headersSent) {
      res.setHeader("connection", "close");
    }
    res.on("finish", () => {
      req.socket._isIdle = true;
      destroy(req.socket);
    });
  });
  server.on("connection", (socket) => {
    if (isShuttingDown) {
      socket.destroy();
    } else {
      const id = connectionCounter++;
      socket._isIdle = true;
      socket._connectionId = id;
      connections[id] = socket;
      socket.once("close", () => {
        delete connections[socket._connectionId];
      });
    }
  });
  server.on("secureConnection", (socket) => {
    if (isShuttingDown) {
      socket.destroy();
    } else {
      const id = secureConnectionCounter++;
      socket._isIdle = true;
      socket._connectionId = id;
      secureConnections[id] = socket;
      socket.once("close", () => {
        delete secureConnections[socket._connectionId];
      });
    }
  });
  process.on("close", () => {
    debug("closed");
  });
  function shutdown(sig) {
    function cleanupHttp() {
      destroyAllConnections();
      debug("Close http server");
      return new Promise((resolve, reject) => {
        server.close((err) => {
          if (err) {
            return reject(err);
          }
          return resolve(true);
        });
      });
    }
    debug("shutdown signal - " + sig);
    if (options.development) {
      debug("DEV-Mode - immediate forceful shutdown");
      return process.exit(0);
    }
    function finalHandler() {
      if (!finalRun) {
        finalRun = true;
        if (options.finally && isFunction(options.finally)) {
          debug("executing finally()");
          options.finally();
        }
      }
      return Promise.resolve();
    }
    function waitForReadyToShutDown(totalNumInterval) {
      debug(`waitForReadyToShutDown... ${totalNumInterval}`);
      if (totalNumInterval === 0) {
        debug(
          `Could not close connections in time (${options.timeout}ms), will forcefully shut down`
        );
        return Promise.resolve(true);
      }
      const allConnectionsClosed = Object.keys(connections).length === 0 && Object.keys(secureConnections).length === 0;
      if (allConnectionsClosed) {
        debug("All connections closed. Continue to shutting down");
        return Promise.resolve(false);
      }
      debug("Schedule the next waitForReadyToShutdown");
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve(waitForReadyToShutDown(totalNumInterval - 1));
        }, 250);
      });
    }
    if (isShuttingDown) {
      return Promise.resolve();
    }
    debug("shutting down");
    return options.preShutdown(sig).then(() => {
      isShuttingDown = true;
      cleanupHttp();
    }).then(() => {
      const pollIterations = options.timeout ? Math.round(options.timeout / 250) : 0;
      return waitForReadyToShutDown(pollIterations);
    }).then((force) => {
      debug("Do onShutdown now");
      if (force) {
        destroyAllConnections(force);
      }
      return options.onShutdown(sig);
    }).then(finalHandler).catch((error) => {
      const errString = typeof error === "string" ? error : JSON.stringify(error);
      debug(errString);
      failed = true;
      throw errString;
    });
  }
  function shutdownManual() {
    return shutdown("manual");
  }
  return shutdownManual;
}

function getGracefulShutdownConfig() {
  return {
    disabled: !!process.env.NITRO_SHUTDOWN_DISABLED,
    signals: (process.env.NITRO_SHUTDOWN_SIGNALS || "SIGTERM SIGINT").split(" ").map((s) => s.trim()),
    timeout: Number.parseInt(process.env.NITRO_SHUTDOWN_TIMEOUT || "", 10) || 3e4,
    forceExit: !process.env.NITRO_SHUTDOWN_NO_FORCE_EXIT
  };
}
function setupGracefulShutdown(listener, nitroApp) {
  const shutdownConfig = getGracefulShutdownConfig();
  if (shutdownConfig.disabled) {
    return;
  }
  GracefulShutdown(listener, {
    signals: shutdownConfig.signals.join(" "),
    timeout: shutdownConfig.timeout,
    forceExit: shutdownConfig.forceExit,
    onShutdown: async () => {
      await new Promise((resolve) => {
        const timeout = setTimeout(() => {
          console.warn("Graceful shutdown timeout, force exiting...");
          resolve();
        }, shutdownConfig.timeout);
        nitroApp.hooks.callHook("close").catch((error) => {
          console.error(error);
        }).finally(() => {
          clearTimeout(timeout);
          resolve();
        });
      });
    }
  });
}

export { getQuery as a, buildAssetsURL as b, createError$1 as c, defineEventHandler as d, appRootTag as e, appRootAttrs as f, getHeader as g, getResponseStatusText as h, getResponseStatus as i, appId as j, decodePath as k, defineRenderHandler as l, appTeleportTag as m, appTeleportAttrs as n, appHead as o, publicAssetsURL as p, getRouteRules as q, readBody as r, joinURL as s, useNitroApp as t, useRuntimeConfig as u, toNodeListener as v, destr as w, trapUnhandledNodeErrors as x, setupGracefulShutdown as y };
//# sourceMappingURL=nitro.mjs.map
