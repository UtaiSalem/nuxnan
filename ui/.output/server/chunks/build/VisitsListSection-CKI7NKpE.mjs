import { ref, mergeProps, unref, computed, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList } from 'vue/server-renderer';

function useVisitReports(allVisits, zones) {
  const showFilters = ref(false);
  const showDetailModal = ref(false);
  const selectedVisit = ref(null);
  const isExporting = ref(false);
  const currentPage = ref(1);
  const perPage = ref(25);
  const filters = ref({
    startDate: "",
    endDate: "",
    status: "",
    zoneId: "",
    studentName: ""
  });
  const filteredVisits = computed(() => {
    let result = [...allVisits.value];
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
    if (filters.value.studentName) {
      const searchTerm = filters.value.studentName.toLowerCase();
      result = result.filter((visit) => {
        const student = visit.student;
        if (!student) return false;
        const fullName = `${student.first_name_th || student.first_name || ""} ${student.last_name_th || student.last_name || ""}`.toLowerCase();
        return fullName.includes(searchTerm);
      });
    }
    return result.sort((a, b) => new Date(b.visit_date) - new Date(a.visit_date));
  });
  const totalPages = computed(() => {
    return Math.ceil(filteredVisits.value.length / perPage.value);
  });
  const paginatedVisits = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredVisits.value.slice(start, end);
  });
  const toggleFilters = () => {
    showFilters.value = !showFilters.value;
  };
  const clearFilters = () => {
    filters.value = {
      startDate: "",
      endDate: "",
      status: "",
      zoneId: "",
      studentName: ""
    };
    currentPage.value = 1;
  };
  const previousPage = () => {
    if (currentPage.value > 1) {
      currentPage.value--;
    }
  };
  const nextPage = () => {
    if (currentPage.value < totalPages.value) {
      currentPage.value++;
    }
  };
  const viewVisitDetails = (visit) => {
    selectedVisit.value = visit;
    showDetailModal.value = true;
  };
  const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedVisit.value = null;
  };
  const exportToExcel = async () => {
    isExporting.value = true;
    try {
      if (!filteredVisits.value || filteredVisits.value.length === 0) {
        alert("\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E35\u0E48\u0E08\u0E30\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E48\u0E2D\u0E19");
        return;
      }
      const response = await axios.post("/api/home-visit/admin/visits/export/excel", {
        filters: filters.value,
        visits: filteredVisits.value.map((v) => v.id)
      }, {
        responseType: "blob"
      });
      if (!response.data || response.data.size === 0) {
        throw new Error("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E44\u0E1F\u0E25\u0E4C Excel \u0E44\u0E14\u0E49");
      }
      const blob = new Blob([response.data], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
      });
      const url = (void 0).URL.createObjectURL(blob);
      const link = (void 0).createElement("a");
      link.href = url;
      link.setAttribute("download", `home-visits-${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.xlsx`);
      (void 0).body.appendChild(link);
      link.click();
      (void 0).body.removeChild(link);
      (void 0).URL.revokeObjectURL(url);
      alert(`\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27 (${filteredVisits.value.length} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23)`);
    } catch (error) {
      console.error("Export failed:", error);
      if (error.response && error.response.data instanceof Blob) {
        const reader = new FileReader();
        reader.onload = () => {
          try {
            const errorData = JSON.parse(reader.result);
            alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14: " + (errorData.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E44\u0E14\u0E49"));
          } catch {
            alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07");
          }
        };
        reader.readAsText(error.response.data);
      } else {
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01: " + (error.message || "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07"));
      }
    } finally {
      isExporting.value = false;
    }
  };
  const getStudentFullName = (student) => {
    if (!student) return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
    return `${student.first_name_th || student.first_name || ""} ${student.last_name_th || student.last_name || ""}`.trim() || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D";
  };
  const getStudentInitial = (student) => {
    if (!student) return "N";
    const firstName = student.first_name_th || student.first_name || "";
    return firstName.charAt(0).toUpperCase() || "N";
  };
  return {
    showFilters,
    showDetailModal,
    selectedVisit,
    isExporting,
    currentPage,
    perPage,
    filters,
    filteredVisits,
    totalPages,
    paginatedVisits,
    toggleFilters,
    clearFilters,
    previousPage,
    nextPage,
    viewVisitDetails,
    closeDetailModal,
    exportToExcel,
    getStudentFullName,
    getStudentInitial
  };
}
const _sfc_main = {
  __name: "VisitsListSection",
  __ssrInlineRender: true,
  props: {
    visits: {
      type: Array,
      required: true
    },
    zones: {
      type: Array,
      required: true
    }
  },
  emits: ["view-details"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const visitsRef = ref(props.visits);
    ref(props.zones);
    const {
      showFilters,
      isExporting,
      currentPage,
      perPage,
      filters,
      filteredVisits,
      totalPages,
      paginatedVisits,
      getStudentFullName,
      getStudentInitial
    } = useVisitReports(visitsRef);
    const getStatusText = (status) => {
      const statusMap = {
        "pending": "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        "in-progress": "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        "completed": "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
        "cancelled": "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
      };
      return statusMap[status] || status;
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
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white shadow overflow-hidden sm:rounded-lg" }, _attrs))}><div class="px-4 py-5 sm:px-6 border-b border-gray-200"><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h3 class="text-lg leading-6 font-medium text-gray-900"><i class="fas fa-clipboard-list mr-2 text-indigo-600"></i> \u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </h3><p class="mt-1 text-sm text-gray-500"> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E41\u0E25\u0E30\u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </p></div><div class="flex gap-2"><button class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"${ssrIncludeBooleanAttr(unref(isExporting)) ? " disabled" : ""}><i class="fas fa-file-excel mr-2"></i> ${ssrInterpolate(unref(isExporting) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07\u0E2D\u0E2D\u0E01..." : "Excel")}</button><button class="${ssrRenderClass([
        unref(showFilters) ? "bg-indigo-600 text-white" : "bg-gray-100 text-gray-700",
        "inline-flex items-center px-3 py-2 hover:bg-indigo-700 text-sm font-medium rounded-lg transition-colors"
      ])}"><i class="fas fa-filter mr-2"></i> \u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 </button></div></div></div>`);
      if (unref(showFilters)) {
        _push(`<div class="px-4 py-4 bg-gray-50 border-b border-gray-200"><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</label><input${ssrRenderAttr("value", unref(filters).startDate)} type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</label><input${ssrRenderAttr("value", unref(filters).endDate)} type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E2A\u0E16\u0E32\u0E19\u0E30</label><select class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(unref(filters).status) ? ssrLooseContain(unref(filters).status, "") : ssrLooseEqual(unref(filters).status, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><option value="pending"${ssrIncludeBooleanAttr(Array.isArray(unref(filters).status) ? ssrLooseContain(unref(filters).status, "pending") : ssrLooseEqual(unref(filters).status, "pending")) ? " selected" : ""}>\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="in-progress"${ssrIncludeBooleanAttr(Array.isArray(unref(filters).status) ? ssrLooseContain(unref(filters).status, "in-progress") : ssrLooseEqual(unref(filters).status, "in-progress")) ? " selected" : ""}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23</option><option value="completed"${ssrIncludeBooleanAttr(Array.isArray(unref(filters).status) ? ssrLooseContain(unref(filters).status, "completed") : ssrLooseEqual(unref(filters).status, "completed")) ? " selected" : ""}>\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19</option><option value="cancelled"${ssrIncludeBooleanAttr(Array.isArray(unref(filters).status) ? ssrLooseContain(unref(filters).status, "cancelled") : ssrLooseEqual(unref(filters).status, "cancelled")) ? " selected" : ""}>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E42\u0E0B\u0E19</label><select class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(unref(filters).zoneId) ? ssrLooseContain(unref(filters).zoneId, "") : ssrLooseEqual(unref(filters).zoneId, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><!--[-->`);
        ssrRenderList(__props.zones, (zone) => {
          _push(`<option${ssrRenderAttr("value", zone.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(filters).zoneId) ? ssrLooseContain(unref(filters).zoneId, zone.id) : ssrLooseEqual(unref(filters).zoneId, zone.id)) ? " selected" : ""}>${ssrInterpolate(zone.zone_name)}</option>`);
        });
        _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</label><input${ssrRenderAttr("value", unref(filters).studentName)} type="text" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></div><div class="flex items-end"><button class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors"><i class="fas fa-times mr-2"></i> \u0E25\u0E49\u0E32\u0E07\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07 </button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="px-4 py-3 bg-white border-b border-gray-200"><div class="flex items-center justify-between text-sm"><span class="text-gray-700"> \u0E41\u0E2A\u0E14\u0E07 ${ssrInterpolate(unref(paginatedVisits).length)} \u0E08\u0E32\u0E01 ${ssrInterpolate(unref(filteredVisits).length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </span><div class="flex items-center gap-4"><span class="text-gray-500">\u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(unref(currentPage))}/${ssrInterpolate(unref(totalPages))}</span><select class="border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"><option${ssrRenderAttr("value", 10)}${ssrIncludeBooleanAttr(Array.isArray(unref(perPage)) ? ssrLooseContain(unref(perPage), 10) : ssrLooseEqual(unref(perPage), 10)) ? " selected" : ""}>10</option><option${ssrRenderAttr("value", 25)}${ssrIncludeBooleanAttr(Array.isArray(unref(perPage)) ? ssrLooseContain(unref(perPage), 25) : ssrLooseEqual(unref(perPage), 25)) ? " selected" : ""}>25</option><option${ssrRenderAttr("value", 50)}${ssrIncludeBooleanAttr(Array.isArray(unref(perPage)) ? ssrLooseContain(unref(perPage), 50) : ssrLooseEqual(unref(perPage), 50)) ? " selected" : ""}>50</option></select></div></div></div>`);
      if (unref(filteredVisits).length === 0) {
        _push(`<div class="p-12 text-center"><div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-inbox text-gray-400 text-2xl"></i></div><h3 class="text-lg font-medium text-gray-900 mb-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25</h3><p class="text-gray-500">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E15\u0E32\u0E21\u0E40\u0E07\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E02\u0E17\u0E35\u0E48\u0E04\u0E49\u0E19\u0E2B\u0E32</p></div>`);
      } else {
        _push(`<ul class="divide-y divide-gray-200"><!--[-->`);
        ssrRenderList(unref(paginatedVisits), (visit) => {
          _push(`<li class="px-4 py-4 hover:bg-gray-50 cursor-pointer transition-colors"><div class="flex items-center justify-between"><div class="flex items-center flex-1"><div class="flex-shrink-0"><div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center"><span class="text-white font-medium text-sm">${ssrInterpolate(unref(getStudentInitial)(visit.student))}</span></div></div><div class="ml-4 flex-1"><div class="flex items-center gap-2"><div class="text-sm font-medium text-gray-900">${ssrInterpolate(unref(getStudentFullName)(visit.student))}</div>`);
          if (visit.zone) {
            _push(`<span class="text-xs text-gray-500"><i class="fas fa-map-marker-alt mr-1"></i>${ssrInterpolate(visit.zone.zone_name)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="text-sm text-gray-500 mt-1">${ssrInterpolate(formatDate(visit.visit_date))} `);
          if (visit.visitor_name) {
            _push(`<span class="ml-2"> | <i class="fas fa-user text-xs"></i> ${ssrInterpolate(visit.visitor_name)}</span>`);
          } else if (visit.participants && visit.participants.length > 0) {
            _push(`<span class="ml-2"> | <i class="fas fa-users text-xs"></i> ${ssrInterpolate(visit.participants.length)} \u0E04\u0E19 </span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div><div class="flex items-center gap-3">`);
          if (visit.images_count) {
            _push(`<span class="text-xs text-gray-500"><i class="fas fa-images mr-1"></i>${ssrInterpolate(visit.images_count)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<span class="${ssrRenderClass([
            visit.visit_status === "completed" ? "bg-green-100 text-green-800" : visit.visit_status === "in-progress" ? "bg-yellow-100 text-yellow-800" : visit.visit_status === "pending" ? "bg-orange-100 text-orange-800" : "bg-gray-100 text-gray-800",
            "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          ])}">${ssrInterpolate(getStatusText(visit.visit_status))}</span><i class="fas fa-chevron-right text-gray-400 text-sm"></i></div></div></li>`);
        });
        _push(`<!--]--></ul>`);
      }
      if (unref(filteredVisits).length > 0) {
        _push(`<div class="px-4 py-3 border-t border-gray-200 bg-gray-50"><div class="flex items-center justify-center space-x-2"><button${ssrIncludeBooleanAttr(unref(currentPage) === 1) ? " disabled" : ""} class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"><i class="fas fa-chevron-left"></i></button><span class="text-sm text-gray-700"> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(unref(currentPage))} \u0E08\u0E32\u0E01 ${ssrInterpolate(unref(totalPages))}</span><button${ssrIncludeBooleanAttr(unref(currentPage) === unref(totalPages)) ? " disabled" : ""} class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"><i class="fas fa-chevron-right"></i></button></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/VisitsListSection.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=VisitsListSection-CKI7NKpE.mjs.map
