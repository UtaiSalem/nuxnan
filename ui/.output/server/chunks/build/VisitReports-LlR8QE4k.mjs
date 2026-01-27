import { ref, computed, watch, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrRenderComponent } from 'vue/server-renderer';
import VisitDetailModal from './VisitDetailModal-DqgjSC1h.mjs';
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
  __name: "VisitReports",
  __ssrInlineRender: true,
  props: {
    visits: {
      type: Array,
      default: () => []
    },
    zones: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const isLoading = ref(false);
    const isExporting = ref(false);
    const currentPage = ref(1);
    const showDetailModal = ref(false);
    const selectedVisit = ref(null);
    const filters = ref({
      startDate: "",
      endDate: "",
      status: "",
      zoneId: "",
      teacherName: "",
      studentName: "",
      sortBy: "visit_date_desc",
      perPage: 25
    });
    const filteredVisits = computed(() => {
      let result = [...props.visits];
      if (filters.value.startDate) {
        result = result.filter(
          (visit) => new Date(visit.visit_date) >= new Date(filters.value.startDate)
        );
      }
      if (filters.value.endDate) {
        result = result.filter(
          (visit) => new Date(visit.visit_date) <= new Date(filters.value.endDate)
        );
      }
      if (filters.value.status) {
        result = result.filter((visit) => visit.visit_status === filters.value.status);
      }
      if (filters.value.zoneId) {
        result = result.filter((visit) => visit.zone_id == filters.value.zoneId);
      }
      if (filters.value.teacherName) {
        const searchTerm = filters.value.teacherName.toLowerCase();
        result = result.filter((visit) => {
          if (visit.visitor_name && visit.visitor_name.toLowerCase().includes(searchTerm)) {
            return true;
          }
          if (visit.participants) {
            return visit.participants.some(
              (p) => p.participant_name.toLowerCase().includes(searchTerm)
            );
          }
          return false;
        });
      }
      if (filters.value.studentName) {
        const searchTerm = filters.value.studentName.toLowerCase();
        result = result.filter((visit) => {
          const fullName = getStudentFullName(visit.student).toLowerCase();
          return fullName.includes(searchTerm);
        });
      }
      switch (filters.value.sortBy) {
        case "visit_date_desc":
          result.sort((a, b) => new Date(b.visit_date) - new Date(a.visit_date));
          break;
        case "visit_date_asc":
          result.sort((a, b) => new Date(a.visit_date) - new Date(b.visit_date));
          break;
        case "student_name":
          result.sort(
            (a, b) => getStudentFullName(a.student).localeCompare(getStudentFullName(b.student), "th")
          );
          break;
        case "status":
          result.sort((a, b) => a.visit_status.localeCompare(b.visit_status));
          break;
      }
      return result;
    });
    const filteredStats = computed(() => {
      const visits = filteredVisits.value;
      const total = visits.length;
      const completed = visits.filter((v) => v.visit_status === "completed").length;
      const inProgress = visits.filter((v) => v.visit_status === "in-progress").length;
      const pending = visits.filter((v) => v.visit_status === "pending").length;
      const completedRate = total > 0 ? Math.round(completed / total * 100) : 0;
      return {
        total,
        completed,
        inProgress,
        pending,
        completedRate
      };
    });
    const totalPages = computed(() => {
      return Math.ceil(filteredVisits.value.length / filters.value.perPage);
    });
    const paginatedVisits = computed(() => {
      const start = (currentPage.value - 1) * filters.value.perPage;
      const end = start + filters.value.perPage;
      return filteredVisits.value.slice(start, end);
    });
    const visiblePages = computed(() => {
      const pages = [];
      const total = totalPages.value;
      const current = currentPage.value;
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i);
          pages.push("...");
          pages.push(total);
        } else if (current >= total - 3) {
          pages.push(1);
          pages.push("...");
          for (let i = total - 4; i <= total; i++) pages.push(i);
        } else {
          pages.push(1);
          pages.push("...");
          for (let i = current - 1; i <= current + 1; i++) pages.push(i);
          pages.push("...");
          pages.push(total);
        }
      }
      return pages;
    });
    const closeDetailModal = () => {
      showDetailModal.value = false;
      selectedVisit.value = null;
    };
    const formatDate = (dateString) => {
      if (!dateString) return "-";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric"
      });
    };
    const formatTime = (timeString) => {
      if (!timeString) return "-";
      try {
        return (/* @__PURE__ */ new Date(`2000-01-01 ${timeString}`)).toLocaleTimeString("th-TH", {
          hour: "2-digit",
          minute: "2-digit"
        });
      } catch {
        return timeString;
      }
    };
    const getStudentFullName = (student) => {
      if (!student) return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
      return `${student.first_name || ""} ${student.last_name || ""}`.trim() || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D";
    };
    const getInitials = (student) => {
      if (!student || !student.first_name) return "N";
      return student.first_name.charAt(0).toUpperCase();
    };
    const getStatusText = (status) => {
      const statusMap = {
        "pending": "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        "in-progress": "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        "completed": "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
        "cancelled": "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
      };
      return statusMap[status] || status;
    };
    const getStatusBadgeClass = (status) => {
      const baseClass = "px-2 inline-flex text-xs leading-5 font-semibold rounded-full";
      const statusClasses = {
        "pending": "bg-orange-100 text-orange-800",
        "in-progress": "bg-yellow-100 text-yellow-800",
        "completed": "bg-green-100 text-green-800",
        "cancelled": "bg-gray-100 text-gray-800"
      };
      return `${baseClass} ${statusClasses[status] || "bg-gray-100 text-gray-800"}`;
    };
    watch(() => filters.value.perPage, () => {
      currentPage.value = 1;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-e28c8703><div class="bg-white shadow-sm rounded-lg p-6" data-v-e28c8703><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-v-e28c8703><div data-v-e28c8703><h2 class="text-2xl font-bold text-gray-900" data-v-e28c8703><i class="fas fa-chart-line mr-2 text-indigo-600" data-v-e28c8703></i> \u0E23\u0E32\u0E22\u0E07\u0E32\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h2><p class="mt-1 text-sm text-gray-500" data-v-e28c8703> \u0E41\u0E2A\u0E14\u0E07\u0E41\u0E25\u0E30\u0E27\u0E34\u0E40\u0E04\u0E23\u0E32\u0E30\u0E2B\u0E4C\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </p></div><div class="flex gap-2" data-v-e28c8703><button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"${ssrIncludeBooleanAttr(isExporting.value) ? " disabled" : ""} data-v-e28c8703><i class="fas fa-file-excel mr-2" data-v-e28c8703></i> ${ssrInterpolate(isExporting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01..." : "\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 Excel")}</button><button class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"${ssrIncludeBooleanAttr(isExporting.value) ? " disabled" : ""} data-v-e28c8703><i class="fas fa-file-pdf mr-2" data-v-e28c8703></i> \u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 PDF </button><button class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors" data-v-e28c8703><i class="fas fa-sync-alt mr-2" data-v-e28c8703></i> \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A </button></div></div></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" data-v-e28c8703><div class="bg-white rounded-lg shadow-sm p-6" data-v-e28c8703><div class="flex items-center justify-between" data-v-e28c8703><div data-v-e28c8703><p class="text-sm font-medium text-gray-500" data-v-e28c8703>\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</p><p class="text-2xl font-bold text-gray-900 mt-1" data-v-e28c8703>${ssrInterpolate(filteredStats.value.total)}</p></div><div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center" data-v-e28c8703><i class="fas fa-home text-blue-600 text-xl" data-v-e28c8703></i></div></div></div><div class="bg-white rounded-lg shadow-sm p-6" data-v-e28c8703><div class="flex items-center justify-between" data-v-e28c8703><div data-v-e28c8703><p class="text-sm font-medium text-gray-500" data-v-e28c8703>\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19\u0E41\u0E25\u0E49\u0E27</p><p class="text-2xl font-bold text-green-600 mt-1" data-v-e28c8703>${ssrInterpolate(filteredStats.value.completed)}</p><p class="text-xs text-gray-500 mt-1" data-v-e28c8703>${ssrInterpolate(filteredStats.value.completedRate)}%</p></div><div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center" data-v-e28c8703><i class="fas fa-check-circle text-green-600 text-xl" data-v-e28c8703></i></div></div></div><div class="bg-white rounded-lg shadow-sm p-6" data-v-e28c8703><div class="flex items-center justify-between" data-v-e28c8703><div data-v-e28c8703><p class="text-sm font-medium text-gray-500" data-v-e28c8703>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p><p class="text-2xl font-bold text-yellow-600 mt-1" data-v-e28c8703>${ssrInterpolate(filteredStats.value.inProgress)}</p></div><div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center" data-v-e28c8703><i class="fas fa-spinner text-yellow-600 text-xl" data-v-e28c8703></i></div></div></div><div class="bg-white rounded-lg shadow-sm p-6" data-v-e28c8703><div class="flex items-center justify-between" data-v-e28c8703><div data-v-e28c8703><p class="text-sm font-medium text-gray-500" data-v-e28c8703>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</p><p class="text-2xl font-bold text-orange-600 mt-1" data-v-e28c8703>${ssrInterpolate(filteredStats.value.pending)}</p></div><div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center" data-v-e28c8703><i class="fas fa-clock text-orange-600 text-xl" data-v-e28c8703></i></div></div></div></div><div class="bg-white shadow-sm rounded-lg p-6" data-v-e28c8703><div class="mb-4 flex items-center justify-between" data-v-e28c8703><h3 class="text-lg font-medium text-gray-900" data-v-e28c8703><i class="fas fa-filter mr-2 text-indigo-600" data-v-e28c8703></i> \u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07\u0E41\u0E25\u0E30\u0E04\u0E49\u0E19\u0E2B\u0E32 </h3><button class="text-sm text-indigo-600 hover:text-indigo-800 font-medium" data-v-e28c8703><i class="fas fa-times mr-1" data-v-e28c8703></i> \u0E25\u0E49\u0E32\u0E07\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 </button></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" data-v-e28c8703><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</label><input${ssrRenderAttr("value", filters.value.startDate)} type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</label><input${ssrRenderAttr("value", filters.value.endDate)} type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E2A\u0E16\u0E32\u0E19\u0E30</label><select class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703><option value="" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "") : ssrLooseEqual(filters.value.status, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><option value="pending" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "pending") : ssrLooseEqual(filters.value.status, "pending")) ? " selected" : ""}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="in-progress" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "in-progress") : ssrLooseEqual(filters.value.status, "in-progress")) ? " selected" : ""}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="completed" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "completed") : ssrLooseEqual(filters.value.status, "completed")) ? " selected" : ""}>\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19</option><option value="cancelled" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.status) ? ssrLooseContain(filters.value.status, "cancelled") : ssrLooseEqual(filters.value.status, "cancelled")) ? " selected" : ""}>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</option></select></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E42\u0E0B\u0E19</label><select class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703><option value="" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.zoneId) ? ssrLooseContain(filters.value.zoneId, "") : ssrLooseEqual(filters.value.zoneId, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><!--[-->`);
      ssrRenderList(__props.zones, (zone) => {
        _push(`<option${ssrRenderAttr("value", zone.id)} data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.zoneId) ? ssrLooseContain(filters.value.zoneId, zone.id) : ssrLooseEqual(filters.value.zoneId, zone.id)) ? " selected" : ""}>${ssrInterpolate(zone.zone_name)}</option>`);
      });
      _push(`<!--]--></select></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</label><input${ssrRenderAttr("value", filters.value.teacherName)} type="text" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E04\u0E23\u0E39..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</label><input${ssrRenderAttr("value", filters.value.studentName)} type="text" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E40\u0E23\u0E35\u0E22\u0E07\u0E15\u0E32\u0E21</label><select class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703><option value="visit_date_desc" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.sortBy) ? ssrLooseContain(filters.value.sortBy, "visit_date_desc") : ssrLooseEqual(filters.value.sortBy, "visit_date_desc")) ? " selected" : ""}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</option><option value="visit_date_asc" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.sortBy) ? ssrLooseContain(filters.value.sortBy, "visit_date_asc") : ssrLooseEqual(filters.value.sortBy, "visit_date_asc")) ? " selected" : ""}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E01\u0E48\u0E32\u0E2A\u0E38\u0E14</option><option value="student_name" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.sortBy) ? ssrLooseContain(filters.value.sortBy, "student_name") : ssrLooseEqual(filters.value.sortBy, "student_name")) ? " selected" : ""}>\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</option><option value="status" data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.sortBy) ? ssrLooseContain(filters.value.sortBy, "status") : ssrLooseEqual(filters.value.sortBy, "status")) ? " selected" : ""}>\u0E2A\u0E16\u0E32\u0E19\u0E30</option></select></div><div data-v-e28c8703><label class="block text-sm font-medium text-gray-700 mb-1" data-v-e28c8703>\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25</label><select class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" data-v-e28c8703><option${ssrRenderAttr("value", 10)} data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.perPage) ? ssrLooseContain(filters.value.perPage, 10) : ssrLooseEqual(filters.value.perPage, 10)) ? " selected" : ""}>10 \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</option><option${ssrRenderAttr("value", 25)} data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.perPage) ? ssrLooseContain(filters.value.perPage, 25) : ssrLooseEqual(filters.value.perPage, 25)) ? " selected" : ""}>25 \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</option><option${ssrRenderAttr("value", 50)} data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.perPage) ? ssrLooseContain(filters.value.perPage, 50) : ssrLooseEqual(filters.value.perPage, 50)) ? " selected" : ""}>50 \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</option><option${ssrRenderAttr("value", 100)} data-v-e28c8703${ssrIncludeBooleanAttr(Array.isArray(filters.value.perPage) ? ssrLooseContain(filters.value.perPage, 100) : ssrLooseEqual(filters.value.perPage, 100)) ? " selected" : ""}>100 \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23</option></select></div></div><div class="mt-4" data-v-e28c8703><button class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors" data-v-e28c8703><i class="fas fa-search mr-2" data-v-e28c8703></i> \u0E04\u0E49\u0E19\u0E2B\u0E32 </button></div></div><div class="bg-white shadow-sm rounded-lg overflow-hidden" data-v-e28c8703><div class="px-6 py-4 border-b border-gray-200" data-v-e28c8703><div class="flex items-center justify-between" data-v-e28c8703><h3 class="text-lg font-medium text-gray-900" data-v-e28c8703> \u0E1C\u0E25\u0E25\u0E31\u0E1E\u0E18\u0E4C\u0E01\u0E32\u0E23\u0E04\u0E49\u0E19\u0E2B\u0E32 (${ssrInterpolate(filteredVisits.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23) </h3><div class="text-sm text-gray-500" data-v-e28c8703> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(currentPage.value)} \u0E08\u0E32\u0E01 ${ssrInterpolate(totalPages.value)}</div></div></div>`);
      if (isLoading.value) {
        _push(`<div class="p-12 text-center" data-v-e28c8703><div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" data-v-e28c8703></div><p class="mt-4 text-gray-500" data-v-e28c8703>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
      } else if (filteredVisits.value.length === 0) {
        _push(`<div class="p-12 text-center" data-v-e28c8703><div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4" data-v-e28c8703><i class="fas fa-inbox text-gray-400 text-2xl" data-v-e28c8703></i></div><h3 class="text-lg font-medium text-gray-900 mb-2" data-v-e28c8703>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</h3><p class="text-gray-500" data-v-e28c8703>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E15\u0E32\u0E21\u0E40\u0E07\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E02\u0E17\u0E35\u0E48\u0E04\u0E49\u0E19\u0E2B\u0E32</p></div>`);
      } else {
        _push(`<div class="overflow-x-auto" data-v-e28c8703><table class="min-w-full divide-y divide-gray-200" data-v-e28c8703><thead class="bg-gray-50" data-v-e28c8703><tr data-v-e28c8703><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 </th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E42\u0E0B\u0E19 </th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 </th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E2A\u0E16\u0E32\u0E19\u0E30 </th><th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E </th><th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider" data-v-e28c8703> \u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23 </th></tr></thead><tbody class="bg-white divide-y divide-gray-200" data-v-e28c8703><!--[-->`);
        ssrRenderList(paginatedVisits.value, (visit) => {
          var _a;
          _push(`<tr class="hover:bg-gray-50 transition-colors" data-v-e28c8703><td class="px-6 py-4 whitespace-nowrap" data-v-e28c8703><div class="text-sm font-medium text-gray-900" data-v-e28c8703>${ssrInterpolate(formatDate(visit.visit_date))}</div>`);
          if (visit.visit_time) {
            _push(`<div class="text-sm text-gray-500" data-v-e28c8703>${ssrInterpolate(formatTime(visit.visit_time))}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</td><td class="px-6 py-4" data-v-e28c8703><div class="flex items-center" data-v-e28c8703><div class="flex-shrink-0 h-10 w-10" data-v-e28c8703><div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center" data-v-e28c8703><span class="text-indigo-600 font-medium text-sm" data-v-e28c8703>${ssrInterpolate(getInitials(visit.student))}</span></div></div><div class="ml-4" data-v-e28c8703><div class="text-sm font-medium text-gray-900" data-v-e28c8703>${ssrInterpolate(getStudentFullName(visit.student))}</div>`);
          if ((_a = visit.student) == null ? void 0 : _a.student_code) {
            _push(`<div class="text-sm text-gray-500" data-v-e28c8703> \u0E23\u0E2B\u0E31\u0E2A: ${ssrInterpolate(visit.student.student_code)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></td><td class="px-6 py-4 whitespace-nowrap" data-v-e28c8703>`);
          if (visit.zone) {
            _push(`<div class="text-sm text-gray-900" data-v-e28c8703><i class="fas fa-map-marker-alt mr-1 text-indigo-600" data-v-e28c8703></i> ${ssrInterpolate(visit.zone.zone_name)}</div>`);
          } else {
            _push(`<div class="text-sm text-gray-400 italic" data-v-e28c8703> \u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38 </div>`);
          }
          _push(`</td><td class="px-6 py-4" data-v-e28c8703>`);
          if (visit.participants && visit.participants.length > 0) {
            _push(`<div class="space-y-1" data-v-e28c8703><!--[-->`);
            ssrRenderList(visit.participants.slice(0, 2), (participant) => {
              _push(`<div class="text-sm text-gray-900" data-v-e28c8703><i class="fas fa-user mr-1 text-gray-400" data-v-e28c8703></i> ${ssrInterpolate(participant.participant_name)}</div>`);
            });
            _push(`<!--]-->`);
            if (visit.participants.length > 2) {
              _push(`<div class="text-xs text-gray-500" data-v-e28c8703> +${ssrInterpolate(visit.participants.length - 2)} \u0E04\u0E19\u0E2D\u0E37\u0E48\u0E19\u0E46 </div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else if (visit.visitor_name) {
            _push(`<div class="text-sm text-gray-900" data-v-e28c8703><i class="fas fa-user mr-1 text-gray-400" data-v-e28c8703></i> ${ssrInterpolate(visit.visitor_name)}</div>`);
          } else {
            _push(`<div class="text-sm text-gray-400 italic" data-v-e28c8703> \u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38 </div>`);
          }
          _push(`</td><td class="px-6 py-4 whitespace-nowrap" data-v-e28c8703><span class="${ssrRenderClass(getStatusBadgeClass(visit.visit_status))}" data-v-e28c8703>${ssrInterpolate(getStatusText(visit.visit_status))}</span></td><td class="px-6 py-4 whitespace-nowrap" data-v-e28c8703><div class="flex items-center" data-v-e28c8703><i class="fas fa-image mr-2 text-gray-400" data-v-e28c8703></i><span class="text-sm text-gray-900" data-v-e28c8703>${ssrInterpolate(visit.images_count || 0)} \u0E23\u0E39\u0E1B </span></div></td><td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" data-v-e28c8703><button class="text-indigo-600 hover:text-indigo-900 mr-3" title="\u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14" data-v-e28c8703><i class="fas fa-eye" data-v-e28c8703></i></button><button class="text-green-600 hover:text-green-900 mr-3" title="\u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19" data-v-e28c8703><i class="fas fa-download" data-v-e28c8703></i></button><button class="text-yellow-600 hover:text-yellow-900" title="\u0E41\u0E01\u0E49\u0E44\u0E02" data-v-e28c8703><i class="fas fa-edit" data-v-e28c8703></i></button></td></tr>`);
        });
        _push(`<!--]--></tbody></table></div>`);
      }
      if (filteredVisits.value.length > 0) {
        _push(`<div class="px-6 py-4 border-t border-gray-200 bg-gray-50" data-v-e28c8703><div class="flex items-center justify-between" data-v-e28c8703><div class="text-sm text-gray-500" data-v-e28c8703> \u0E41\u0E2A\u0E14\u0E07 ${ssrInterpolate((currentPage.value - 1) * filters.value.perPage + 1)} \u0E16\u0E36\u0E07 ${ssrInterpolate(Math.min(currentPage.value * filters.value.perPage, filteredVisits.value.length))} \u0E08\u0E32\u0E01 ${ssrInterpolate(filteredVisits.value.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </div><div class="flex items-center space-x-2" data-v-e28c8703><button${ssrIncludeBooleanAttr(currentPage.value === 1) ? " disabled" : ""} class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" data-v-e28c8703><i class="fas fa-chevron-left" data-v-e28c8703></i></button><!--[-->`);
        ssrRenderList(visiblePages.value, (page) => {
          _push(`<button class="${ssrRenderClass([
            "px-3 py-1 border rounded-md text-sm font-medium",
            page === currentPage.value ? "bg-indigo-600 text-white border-indigo-600" : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
          ])}" data-v-e28c8703>${ssrInterpolate(page)}</button>`);
        });
        _push(`<!--]--><button${ssrIncludeBooleanAttr(currentPage.value === totalPages.value) ? " disabled" : ""} class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" data-v-e28c8703><i class="fas fa-chevron-right" data-v-e28c8703></i></button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (showDetailModal.value) {
        _push(ssrRenderComponent(VisitDetailModal, {
          visit: selectedVisit.value,
          onClose: closeDetailModal
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/VisitReports.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const VisitReports = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-e28c8703"]]);

export { VisitReports as default };
//# sourceMappingURL=VisitReports-LlR8QE4k.mjs.map
