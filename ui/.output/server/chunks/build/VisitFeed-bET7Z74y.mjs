import { ref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderAttr, ssrInterpolate, ssrRenderStyle, ssrRenderClass } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "VisitFeed",
  __ssrInlineRender: true,
  props: {
    visits: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] }
  },
  emits: ["view-details"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const filters = ref({
      status: "",
      zone: "",
      startDate: "",
      endDate: ""
    });
    const expanded = ref(/* @__PURE__ */ new Set());
    const currentPage = ref(1);
    const perPage = ref(10);
    const formatDate = (d) => {
      if (!d) return "-";
      const dt = new Date(d);
      return dt.toLocaleString("th-TH", { dateStyle: "medium", timeStyle: "short" });
    };
    const statusText = (s) => {
      switch (s) {
        case "pending":
          return "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23";
        case "in-progress":
          return "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23";
        case "completed":
          return "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19";
        case "cancelled":
          return "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01";
        default:
          return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
      }
    };
    const statusColor = (s) => {
      switch (s) {
        case "pending":
          return "bg-gray-400";
        case "in-progress":
          return "bg-yellow-500";
        case "completed":
          return "bg-green-600";
        case "cancelled":
          return "bg-red-500";
        default:
          return "bg-indigo-500";
      }
    };
    const statusIcon = (s) => {
      switch (s) {
        case "pending":
          return "fas fa-clock";
        case "in-progress":
          return "fas fa-spinner";
        case "completed":
          return "fas fa-check";
        case "cancelled":
          return "fas fa-times";
        default:
          return "fas fa-info";
      }
    };
    const statusBadge = (s) => {
      switch (s) {
        case "pending":
          return "bg-gray-100 text-gray-800";
        case "in-progress":
          return "bg-yellow-100 text-yellow-800";
        case "completed":
          return "bg-green-100 text-green-800";
        case "cancelled":
          return "bg-red-100 text-red-800";
        default:
          return "bg-indigo-100 text-indigo-800";
      }
    };
    const statusDot = (s) => {
      switch (s) {
        case "pending":
          return "bg-gray-500";
        case "in-progress":
          return "bg-yellow-500";
        case "completed":
          return "bg-green-500";
        case "cancelled":
          return "bg-red-500";
        default:
          return "bg-indigo-500";
      }
    };
    const filteredVisits = computed(() => {
      return props.visits.filter((v) => {
        if (filters.value.status && v.visit_status !== filters.value.status) return false;
        if (filters.value.zone && String(v.zone_id) !== String(filters.value.zone)) return false;
        if (filters.value.startDate) {
          const vs = new Date(v.visit_date);
          const st = new Date(filters.value.startDate);
          if (vs < st) return false;
        }
        if (filters.value.endDate) {
          const ve = new Date(v.visit_date);
          const en = new Date(filters.value.endDate);
          if (ve > en) return false;
        }
        return true;
      }).sort((a, b) => new Date(b.visit_date) - new Date(a.visit_date));
    });
    const totalPages = computed(() => Math.max(1, Math.ceil(filteredVisits.value.length / perPage.value)));
    const paginatedVisits = computed(() => {
      const start = (currentPage.value - 1) * perPage.value;
      return filteredVisits.value.slice(start, start + perPage.value);
    });
    const getImageUrl = (img) => {
      if (typeof img === "string") {
        return img;
      }
      if (img && typeof img === "object") {
        return img.url || img.path || img.src || "";
      }
      return "";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-cbb856c5><div class="header-card bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 shadow-xl rounded-2xl p-8 text-white relative overflow-hidden" data-v-cbb856c5><div class="absolute inset-0 opacity-10" data-v-cbb856c5><div class="absolute -top-4 -right-4 w-32 h-32 bg-white rounded-full animate-blob" data-v-cbb856c5></div><div class="absolute -bottom-8 -left-4 w-40 h-40 bg-white rounded-full animate-blob animation-delay-2000" data-v-cbb856c5></div><div class="absolute top-1/2 left-1/2 w-36 h-36 bg-white rounded-full animate-blob animation-delay-4000" data-v-cbb856c5></div></div><div class="relative z-10 flex items-center justify-between" data-v-cbb856c5><div class="animate-fade-in-up" data-v-cbb856c5><h2 class="text-3xl font-bold flex items-center" data-v-cbb856c5><div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-3 animate-pulse-soft" data-v-cbb856c5><i class="fas fa-stream text-2xl" data-v-cbb856c5></i></div> \u0E1F\u0E35\u0E14\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h2><p class="mt-2 text-white/90 text-sm ml-15" data-v-cbb856c5>\u0E44\u0E17\u0E21\u0E4C\u0E44\u0E25\u0E19\u0E4C\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E40\u0E23\u0E35\u0E22\u0E07\u0E15\u0E32\u0E21\u0E40\u0E27\u0E25\u0E32\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</p></div><div class="flex space-x-3 animate-fade-in" data-v-cbb856c5><button class="group px-4 py-2.5 text-sm rounded-xl bg-white/20 backdrop-blur-md hover:bg-white/30 transition-all duration-300 border border-white/30 hover:scale-105 hover:shadow-lg" data-v-cbb856c5><i class="fas fa-sync-alt mr-2 group-hover:rotate-180 transition-transform duration-500" data-v-cbb856c5></i> \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A </button></div></div></div><div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100 animate-fade-in" data-v-cbb856c5><div class="flex items-center mb-4" data-v-cbb856c5><i class="fas fa-filter text-indigo-600 mr-2" data-v-cbb856c5></i><h3 class="text-sm font-semibold text-gray-700" data-v-cbb856c5>\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</h3></div><div class="grid md:grid-cols-4 gap-4" data-v-cbb856c5><div class="filter-item" data-v-cbb856c5><label class="text-xs font-medium text-gray-700 mb-2 flex items-center" data-v-cbb856c5><i class="fas fa-tasks text-indigo-500 mr-1.5 text-xs" data-v-cbb856c5></i> \u0E2A\u0E16\u0E32\u0E19\u0E30 </label><select class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400" data-v-cbb856c5><option value="" data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "") : ssrLooseEqual(filters.value.status, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><option value="pending" data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "pending") : ssrLooseEqual(filters.value.status, "pending")) ? " selected" : ""}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="in-progress" data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "in-progress") : ssrLooseEqual(filters.value.status, "in-progress")) ? " selected" : ""}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="completed" data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "completed") : ssrLooseEqual(filters.value.status, "completed")) ? " selected" : ""}>\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19</option><option value="cancelled" data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "cancelled") : ssrLooseEqual(filters.value.status, "cancelled")) ? " selected" : ""}>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</option></select></div><div class="filter-item" data-v-cbb856c5><label class="text-xs font-medium text-gray-700 mb-2 flex items-center" data-v-cbb856c5><i class="fas fa-map-marker-alt text-indigo-500 mr-1.5 text-xs" data-v-cbb856c5></i> \u0E42\u0E0B\u0E19 </label><select class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400" data-v-cbb856c5><option value="" data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.zone) ? ssrLooseContain(filters.value.zone, "") : ssrLooseEqual(filters.value.zone, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><!--[-->`);
      ssrRenderList(__props.zones, (z) => {
        _push(`<option${ssrRenderAttr("value", z.id)} data-v-cbb856c5${ssrIncludeBooleanAttr(Array.isArray(filters.value.zone) ? ssrLooseContain(filters.value.zone, z.id) : ssrLooseEqual(filters.value.zone, z.id)) ? " selected" : ""}>${ssrInterpolate(z.name)}</option>`);
      });
      _push(`<!--]--></select></div><div class="filter-item" data-v-cbb856c5><label class="text-xs font-medium text-gray-700 mb-2 flex items-center" data-v-cbb856c5><i class="fas fa-calendar-alt text-indigo-500 mr-1.5 text-xs" data-v-cbb856c5></i> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21 </label><input type="date"${ssrRenderAttr("value", filters.value.startDate)} class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400" data-v-cbb856c5></div><div class="filter-item" data-v-cbb856c5><label class="text-xs font-medium text-gray-700 mb-2 flex items-center" data-v-cbb856c5><i class="fas fa-calendar-check text-indigo-500 mr-1.5 text-xs" data-v-cbb856c5></i> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 </label><input type="date"${ssrRenderAttr("value", filters.value.endDate)} class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400" data-v-cbb856c5></div></div><div class="mt-5 flex items-center justify-between pt-4 border-t border-gray-100" data-v-cbb856c5><button class="group text-xs px-4 py-2 rounded-lg bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 hover:from-gray-200 hover:to-gray-300 transition-all duration-200 font-medium hover:shadow-md" data-v-cbb856c5><i class="fas fa-redo-alt mr-1.5 group-hover:rotate-180 transition-transform duration-300" data-v-cbb856c5></i> \u0E25\u0E49\u0E32\u0E07\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 </button><div class="flex items-center space-x-2" data-v-cbb856c5><div class="px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200" data-v-cbb856c5><span class="text-xs font-semibold text-indigo-700" data-v-cbb856c5><i class="fas fa-chart-bar mr-1" data-v-cbb856c5></i> ${ssrInterpolate(filteredVisits.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </span></div></div></div></div><div class="space-y-8" data-v-cbb856c5>`);
      if (!filteredVisits.value.length) {
        _push(`<div class="bg-white rounded-2xl shadow-lg p-16 text-center animate-fade-in" data-v-cbb856c5><div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6 animate-bounce-slow" data-v-cbb856c5><i class="fas fa-inbox text-3xl text-gray-400" data-v-cbb856c5></i></div><h3 class="text-lg font-semibold text-gray-700 mb-2" data-v-cbb856c5>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</h3><p class="text-sm text-gray-500" data-v-cbb856c5>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E15\u0E32\u0E21\u0E40\u0E07\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E02\u0E17\u0E35\u0E48\u0E40\u0E25\u0E37\u0E2D\u0E01</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(paginatedVisits.value, (visit, index) => {
        var _a, _b, _c, _d;
        _push(`<div class="relative timeline-item" style="${ssrRenderStyle({ animationDelay: `${index * 100}ms` })}" data-v-cbb856c5>`);
        if (index !== paginatedVisits.value.length - 1) {
          _push(`<div class="absolute left-6 top-16 w-0.5 h-full bg-gradient-to-b from-indigo-400 via-purple-400 to-transparent" data-v-cbb856c5></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex items-start" data-v-cbb856c5><div class="flex flex-col items-center" data-v-cbb856c5><div class="${ssrRenderClass(["w-12 h-12 rounded-full flex items-center justify-center shadow-lg text-white relative transition-all duration-300 hover:scale-110", statusColor(visit.visit_status)])}" data-v-cbb856c5><div class="${ssrRenderClass([statusColor(visit.visit_status), "absolute inset-0 rounded-full animate-ping opacity-20"])}" data-v-cbb856c5></div><i class="${ssrRenderClass([statusIcon(visit.visit_status), "text-lg relative z-10"])}" data-v-cbb856c5></i></div></div><div class="ml-8 flex-1" data-v-cbb856c5><div class="visit-card bg-white rounded-2xl shadow-md hover:shadow-2xl border border-gray-100 overflow-hidden transition-all duration-300 hover:-translate-y-1 group" data-v-cbb856c5><div class="${ssrRenderClass(["h-1.5", statusColor(visit.visit_status)])}" data-v-cbb856c5></div><div class="p-6" data-v-cbb856c5><div class="flex justify-between items-start" data-v-cbb856c5><div class="flex-1" data-v-cbb856c5><h3 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 flex items-center" data-v-cbb856c5><i class="fas fa-user-graduate text-indigo-500 mr-2" data-v-cbb856c5></i> ${ssrInterpolate((_a = visit.student) == null ? void 0 : _a.first_name)} ${ssrInterpolate((_b = visit.student) == null ? void 0 : _b.last_name)}</h3><p class="text-xs text-gray-500 mt-1.5 flex items-center" data-v-cbb856c5><i class="fas fa-school text-gray-400 mr-1.5" data-v-cbb856c5></i> \u0E0A\u0E31\u0E49\u0E19 ${ssrInterpolate(((_c = visit.student) == null ? void 0 : _c.classroom) || "-")} <span class="mx-2" data-v-cbb856c5>\u2022</span><i class="fas fa-chalkboard-teacher text-gray-400 mr-1.5" data-v-cbb856c5></i> \u0E04\u0E23\u0E39: ${ssrInterpolate(visit.teacher_name || "-")}</p></div><div class="text-right ml-4" data-v-cbb856c5><div class="text-xs text-gray-400 flex items-center justify-end mb-2" data-v-cbb856c5><i class="far fa-clock mr-1.5" data-v-cbb856c5></i> ${ssrInterpolate(formatDate(visit.visit_date))}</div><span class="${ssrRenderClass(["inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm", statusBadge(visit.visit_status)])}" data-v-cbb856c5><span class="${ssrRenderClass(["w-2 h-2 rounded-full mr-2 animate-pulse", statusDot(visit.visit_status)])}" data-v-cbb856c5></span> ${ssrInterpolate(statusText(visit.visit_status))}</span></div></div>`);
        if (visit.notes || visit.summary) {
          _push(`<div class="mt-4 p-4 bg-gradient-to-br from-gray-50 to-indigo-50/30 rounded-xl border border-gray-100" data-v-cbb856c5><p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line line-clamp-3" data-v-cbb856c5>${ssrInterpolate(visit.summary || visit.notes)}</p></div>`);
        } else {
          _push(`<!---->`);
        }
        if (visit.images && visit.images.length) {
          _push(`<div class="mt-5 grid grid-cols-3 gap-3" data-v-cbb856c5><!--[-->`);
          ssrRenderList(visit.images.slice(0, 3), (img, i) => {
            _push(`<div class="aspect-square overflow-hidden rounded-xl cursor-pointer group/img relative shadow-md hover:shadow-xl transition-all duration-300" data-v-cbb856c5><img${ssrRenderAttr("src", getImageUrl(img))} class="object-cover w-full h-full group-hover/img:scale-110 transition-transform duration-500" data-v-cbb856c5><div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover/img:opacity-100 transition-opacity duration-300" data-v-cbb856c5></div>`);
            if (i === 2 && visit.images.length > 3) {
              _push(`<div class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center text-white font-bold text-lg" data-v-cbb856c5><div class="text-center" data-v-cbb856c5><i class="fas fa-images text-2xl mb-1" data-v-cbb856c5></i><div data-v-cbb856c5>+${ssrInterpolate(visit.images.length - 3)}</div></div></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="mt-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs" data-v-cbb856c5><div class="flex items-center space-x-2 p-2.5 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors duration-200" data-v-cbb856c5><div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center" data-v-cbb856c5><i class="fas fa-map-marker-alt text-indigo-600" data-v-cbb856c5></i></div><div data-v-cbb856c5><div class="text-[10px] text-indigo-600 font-medium" data-v-cbb856c5>\u0E42\u0E0B\u0E19</div><div class="text-gray-700 font-semibold" data-v-cbb856c5>${ssrInterpolate(((_d = visit.zone) == null ? void 0 : _d.name) || "-")}</div></div></div><div class="flex items-center space-x-2 p-2.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors duration-200" data-v-cbb856c5><div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center" data-v-cbb856c5><i class="fas fa-user-friends text-purple-600" data-v-cbb856c5></i></div><div data-v-cbb856c5><div class="text-[10px] text-purple-600 font-medium" data-v-cbb856c5>\u0E1C\u0E39\u0E49\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</div><div class="text-gray-700 font-semibold truncate" data-v-cbb856c5>${ssrInterpolate(visit.visitor_name || visit.teacher_name || "N/A")}</div></div></div>`);
        if (visit.duration) {
          _push(`<div class="flex items-center space-x-2 p-2.5 bg-pink-50 rounded-lg hover:bg-pink-100 transition-colors duration-200" data-v-cbb856c5><div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center" data-v-cbb856c5><i class="fas fa-hourglass-half text-pink-600" data-v-cbb856c5></i></div><div data-v-cbb856c5><div class="text-[10px] text-pink-600 font-medium" data-v-cbb856c5>\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32</div><div class="text-gray-700 font-semibold" data-v-cbb856c5>${ssrInterpolate(visit.duration)} \u0E19\u0E32\u0E17\u0E35</div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (visit.next_schedule) {
          _push(`<div class="flex items-center space-x-2 p-2.5 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200" data-v-cbb856c5><div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center" data-v-cbb856c5><i class="fas fa-calendar-plus text-green-600" data-v-cbb856c5></i></div><div data-v-cbb856c5><div class="text-[10px] text-green-600 font-medium" data-v-cbb856c5>\u0E04\u0E23\u0E31\u0E49\u0E07\u0E15\u0E48\u0E2D\u0E44\u0E1B</div><div class="text-gray-700 font-semibold text-[11px]" data-v-cbb856c5>${ssrInterpolate(formatDate(visit.next_schedule))}</div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between" data-v-cbb856c5><div class="flex space-x-3" data-v-cbb856c5><button class="group/btn flex items-center px-4 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-600 hover:text-white transition-all duration-200 hover:shadow-md" data-v-cbb856c5><i class="fas fa-eye mr-2 group-hover/btn:scale-110 transition-transform" data-v-cbb856c5></i> \u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </button><button class="group/btn flex items-center px-4 py-2 text-xs font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-600 hover:text-white transition-all duration-200 hover:shadow-md" data-v-cbb856c5><i class="${ssrRenderClass(["mr-2 transition-transform", expanded.value.has(visit.id) ? "fas fa-chevron-up rotate-180" : "fas fa-chevron-down"])}" data-v-cbb856c5></i> ${ssrInterpolate(expanded.value.has(visit.id) ? "\u0E22\u0E48\u0E2D" : "\u0E02\u0E22\u0E32\u0E22")}</button></div><div class="flex items-center space-x-2" data-v-cbb856c5><span class="text-[10px] text-gray-400 bg-gray-50 px-2.5 py-1 rounded-full font-mono" data-v-cbb856c5> ID: ${ssrInterpolate(visit.id)}</span></div></div>`);
        if (expanded.value.has(visit.id)) {
          _push(`<div class="mt-5 pt-5 border-t border-gray-100 space-y-4" data-v-cbb856c5>`);
          if (visit.risks) {
            _push(`<div class="animate-slide-in" data-v-cbb856c5><div class="flex items-center p-3 bg-red-50 rounded-xl mb-3" data-v-cbb856c5><div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3" data-v-cbb856c5><i class="fas fa-exclamation-triangle text-red-600 text-lg" data-v-cbb856c5></i></div><div class="font-semibold text-red-800 text-sm" data-v-cbb856c5>\u0E1B\u0E23\u0E30\u0E40\u0E14\u0E47\u0E19\u0E17\u0E35\u0E48\u0E1E\u0E1A</div></div><ul class="space-y-2 ml-2" data-v-cbb856c5><!--[-->`);
            ssrRenderList(Array.isArray(visit.risks) ? visit.risks : [], (r, i) => {
              _push(`<li class="flex items-start text-sm text-gray-700 p-2.5 bg-white rounded-lg border border-red-100 hover:shadow-sm transition-shadow" data-v-cbb856c5><i class="fas fa-circle text-red-400 text-[6px] mt-1.5 mr-3" data-v-cbb856c5></i><span data-v-cbb856c5>${ssrInterpolate(r)}</span></li>`);
            });
            _push(`<!--]--></ul></div>`);
          } else {
            _push(`<!---->`);
          }
          if (visit.recommendations) {
            _push(`<div class="animate-slide-in animation-delay-100" data-v-cbb856c5><div class="flex items-center p-3 bg-yellow-50 rounded-xl mb-3" data-v-cbb856c5><div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3" data-v-cbb856c5><i class="fas fa-lightbulb text-yellow-600 text-lg" data-v-cbb856c5></i></div><div class="font-semibold text-yellow-800 text-sm" data-v-cbb856c5>\u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30</div></div><ul class="space-y-2 ml-2" data-v-cbb856c5><!--[-->`);
            ssrRenderList(Array.isArray(visit.recommendations) ? visit.recommendations : [], (r, i) => {
              _push(`<li class="flex items-start text-sm text-gray-700 p-2.5 bg-white rounded-lg border border-yellow-100 hover:shadow-sm transition-shadow" data-v-cbb856c5><i class="fas fa-circle text-yellow-400 text-[6px] mt-1.5 mr-3" data-v-cbb856c5></i><span data-v-cbb856c5>${ssrInterpolate(r)}</span></li>`);
            });
            _push(`<!--]--></ul></div>`);
          } else {
            _push(`<!---->`);
          }
          if (visit.follow_up_actions) {
            _push(`<div class="animate-slide-in animation-delay-200" data-v-cbb856c5><div class="flex items-center p-3 bg-indigo-50 rounded-xl mb-3" data-v-cbb856c5><div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3" data-v-cbb856c5><i class="fas fa-tasks text-indigo-600 text-lg" data-v-cbb856c5></i></div><div class="font-semibold text-indigo-800 text-sm" data-v-cbb856c5>\u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E1C\u0E25</div></div><ul class="space-y-2 ml-2" data-v-cbb856c5><!--[-->`);
            ssrRenderList(Array.isArray(visit.follow_up_actions) ? visit.follow_up_actions : [], (a, i) => {
              _push(`<li class="flex items-start text-sm text-gray-700 p-2.5 bg-white rounded-lg border border-indigo-100 hover:shadow-sm transition-shadow" data-v-cbb856c5><i class="fas fa-circle text-indigo-400 text-[6px] mt-1.5 mr-3" data-v-cbb856c5></i><span data-v-cbb856c5>${ssrInterpolate(a)}</span></li>`);
            });
            _push(`<!--]--></ul></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></div></div>`);
      });
      _push(`<!--]-->`);
      if (filteredVisits.value.length) {
        _push(`<div class="flex justify-center pt-8 animate-fade-in" data-v-cbb856c5><nav class="inline-flex items-center bg-white rounded-xl shadow-lg p-2 space-x-1 border border-gray-100" aria-label="Pagination" data-v-cbb856c5><button${ssrIncludeBooleanAttr(currentPage.value === 1) ? " disabled" : ""} class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-indigo-50 hover:text-indigo-600 text-gray-600" data-v-cbb856c5><i class="fas fa-chevron-left mr-2" data-v-cbb856c5></i> \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </button><div class="px-6 py-2.5 text-sm" data-v-cbb856c5><span class="font-semibold text-indigo-600" data-v-cbb856c5>${ssrInterpolate(currentPage.value)}</span><span class="text-gray-400 mx-1" data-v-cbb856c5>/</span><span class="text-gray-600" data-v-cbb856c5>${ssrInterpolate(totalPages.value)}</span></div><button${ssrIncludeBooleanAttr(currentPage.value === totalPages.value) ? " disabled" : ""} class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-indigo-50 hover:text-indigo-600 text-gray-600" data-v-cbb856c5> \u0E16\u0E31\u0E14\u0E44\u0E1B <i class="fas fa-chevron-right ml-2" data-v-cbb856c5></i></button></nav></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/VisitFeed.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const VisitFeed = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-cbb856c5"]]);

export { VisitFeed as default };
//# sourceMappingURL=VisitFeed-bET7Z74y.mjs.map
