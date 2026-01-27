import { mergeProps, ref, computed, useSSRContext } from 'vue';
import axios from 'axios';
import { ssrRenderAttrs, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrRenderStyle, ssrInterpolate } from 'vue/server-renderer';
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
  name: "ZoneManagement",
  setup() {
    const zones = ref({ data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });
    const loading = ref(false);
    const searchQuery = ref("");
    const statusFilter = ref("");
    const showModal = ref(false);
    const editingZone = ref(null);
    const saving = ref(false);
    const errors = ref({});
    const formData = ref({
      zone_name: "",
      description: "",
      color: "#3B82F6",
      is_active: true,
      display_order: 0
    });
    const presetColors = [
      "#3B82F6",
      // Blue
      "#EF4444",
      // Red
      "#10B981",
      // Green
      "#F59E0B",
      // Orange
      "#8B5CF6",
      // Purple
      "#EC4899",
      // Pink
      "#06B6D4",
      // Cyan
      "#84CC16"
      // Lime
    ];
    const visiblePages = computed(() => {
      const pages = [];
      const current = zones.value.current_page;
      const last = zones.value.last_page;
      const delta = 2;
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        pages.push(i);
      }
      if (current - delta > 2) {
        pages.unshift("...");
      }
      if (current + delta < last - 1) {
        pages.push("...");
      }
      pages.unshift(1);
      if (last > 1) {
        pages.push(last);
      }
      return pages.filter((v, i, a) => a.indexOf(v) === i);
    });
    let searchTimeout = null;
    const debouncedSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        loadZones(1);
      }, 500);
    };
    const loadZones = async (page = 1) => {
      loading.value = true;
      try {
        const params = {
          page,
          per_page: 10
        };
        if (searchQuery.value) {
          params.search = searchQuery.value;
        }
        if (statusFilter.value !== "") {
          params.is_active = statusFilter.value;
        }
        const response = await axios.get("/home-visit/admin/zones", { params });
        zones.value = response.data.zones;
      } catch (error) {
        console.error("Error loading zones:", error);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25");
      } finally {
        loading.value = false;
      }
    };
    const openCreateModal = () => {
      editingZone.value = null;
      formData.value = {
        zone_name: "",
        description: "",
        color: "#3B82F6",
        is_active: true,
        display_order: 0
      };
      errors.value = {};
      showModal.value = true;
    };
    const editZone = (zone) => {
      editingZone.value = zone;
      formData.value = {
        zone_name: zone.zone_name,
        description: zone.description || "",
        color: zone.color || "#3B82F6",
        is_active: zone.is_active,
        display_order: zone.display_order || 0
      };
      errors.value = {};
      showModal.value = true;
    };
    const closeModal = () => {
      showModal.value = false;
      editingZone.value = null;
      errors.value = {};
    };
    const saveZone = async () => {
      saving.value = true;
      errors.value = {};
      try {
        if (editingZone.value) {
          await axios.put(`/home-visit/admin/zones/${editingZone.value.id}`, formData.value);
          alert("\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E42\u0E0B\u0E19\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27");
        } else {
          await axios.post("/home-visit/admin/zones", formData.value);
          alert("\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E42\u0E0B\u0E19\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27");
        }
        closeModal();
        loadZones(zones.value.current_page);
      } catch (error) {
        console.error("Error saving zone:", error);
        if (error.response && error.response.data.errors) {
          errors.value = error.response.data.errors;
        } else {
          alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25");
        }
      } finally {
        saving.value = false;
      }
    };
    const toggleZoneStatus = async (zone) => {
      try {
        const response = await axios.put(`/home-visit/admin/zones/${zone.id}/toggle-status`);
        alert(response.data.message);
        loadZones(zones.value.current_page);
      } catch (error) {
        console.error("Error toggling zone status:", error);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E2A\u0E16\u0E32\u0E19\u0E30");
      }
    };
    const deleteZone = async (zone) => {
      var _a, _b;
      if (zone.home_visits_count > 0) {
        alert("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E42\u0E0B\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E2D\u0E22\u0E39\u0E48\u0E44\u0E14\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E22\u0E49\u0E32\u0E22\u0E2B\u0E23\u0E37\u0E2D\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E01\u0E48\u0E2D\u0E19");
        return;
      }
      if (!confirm(`\u0E04\u0E38\u0E13\u0E41\u0E19\u0E48\u0E43\u0E08\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48\u0E17\u0E35\u0E48\u0E08\u0E30\u0E25\u0E1A\u0E42\u0E0B\u0E19 "${zone.zone_name}"?`)) {
        return;
      }
      try {
        await axios.delete(`/home-visit/admin/zones/${zone.id}`);
        alert("\u0E25\u0E1A\u0E42\u0E0B\u0E19\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27");
        loadZones(zones.value.current_page);
      } catch (error) {
        console.error("Error deleting zone:", error);
        alert(((_b = (_a = error.response) == null ? void 0 : _a.data) == null ? void 0 : _b.message) || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E42\u0E0B\u0E19");
      }
    };
    return {
      zones,
      loading,
      searchQuery,
      statusFilter,
      showModal,
      editingZone,
      saving,
      errors,
      formData,
      presetColors,
      visiblePages,
      debouncedSearch,
      loadZones,
      openCreateModal,
      editZone,
      closeModal,
      saveZone,
      toggleZoneStatus,
      deleteZone
    };
  }
};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-23a55ad0><div class="flex justify-between items-center" data-v-23a55ad0><div data-v-23a55ad0><h2 class="text-2xl font-bold text-gray-900" data-v-23a55ad0>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E42\u0E0B\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</h2><p class="mt-1 text-sm text-gray-600" data-v-23a55ad0> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E42\u0E0B\u0E19\u0E1E\u0E37\u0E49\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </p></div><button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-v-23a55ad0><i class="fas fa-plus mr-2" data-v-23a55ad0></i> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E42\u0E0B\u0E19\u0E43\u0E2B\u0E21\u0E48 </button></div><div class="bg-white shadow rounded-lg p-4" data-v-23a55ad0><div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-v-23a55ad0><div class="md:col-span-2" data-v-23a55ad0><label for="search" class="sr-only" data-v-23a55ad0>\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E42\u0E0B\u0E19</label><div class="relative" data-v-23a55ad0><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" data-v-23a55ad0><i class="fas fa-search text-gray-400" data-v-23a55ad0></i></div><input${ssrRenderAttr("value", $setup.searchQuery)} type="text" id="search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E08\u0E32\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E42\u0E0B\u0E19 \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E42\u0E0B\u0E19..." data-v-23a55ad0></div></div><div data-v-23a55ad0><label for="status-filter" class="sr-only" data-v-23a55ad0>\u0E01\u0E23\u0E2D\u0E07\u0E15\u0E32\u0E21\u0E2A\u0E16\u0E32\u0E19\u0E30</label><select id="status-filter" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" data-v-23a55ad0><option value="" data-v-23a55ad0${ssrIncludeBooleanAttr(Array.isArray($setup.statusFilter) ? ssrLooseContain($setup.statusFilter, "") : ssrLooseEqual($setup.statusFilter, "")) ? " selected" : ""}>\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</option><option value="1" data-v-23a55ad0${ssrIncludeBooleanAttr(Array.isArray($setup.statusFilter) ? ssrLooseContain($setup.statusFilter, "1") : ssrLooseEqual($setup.statusFilter, "1")) ? " selected" : ""}>\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</option><option value="0" data-v-23a55ad0${ssrIncludeBooleanAttr(Array.isArray($setup.statusFilter) ? ssrLooseContain($setup.statusFilter, "0") : ssrLooseEqual($setup.statusFilter, "0")) ? " selected" : ""}>\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</option></select></div></div></div>`);
  if ($setup.loading) {
    _push(`<div class="text-center py-12" data-v-23a55ad0><i class="fas fa-spinner fa-spin text-4xl text-indigo-500" data-v-23a55ad0></i><p class="mt-2 text-gray-600" data-v-23a55ad0>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div>`);
  } else if ($setup.zones.data && $setup.zones.data.length > 0) {
    _push(`<div class="space-y-4" data-v-23a55ad0><!--[-->`);
    ssrRenderList($setup.zones.data, (zone) => {
      _push(`<div class="${ssrRenderClass([{ "opacity-60": !zone.is_active }, "bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200"])}" data-v-23a55ad0><div class="p-6" data-v-23a55ad0><div class="flex items-start justify-between" data-v-23a55ad0><div class="flex items-start space-x-4 flex-1" data-v-23a55ad0><div class="w-16 h-16 rounded-lg flex-shrink-0" style="${ssrRenderStyle({ backgroundColor: zone.color || "#6B7280" })}" data-v-23a55ad0></div><div class="flex-1 min-w-0" data-v-23a55ad0><div class="flex items-center space-x-3" data-v-23a55ad0><h3 class="text-lg font-semibold text-gray-900" data-v-23a55ad0>${ssrInterpolate(zone.zone_name)}</h3><span class="${ssrRenderClass([zone.is_active ? "bg-green-100 text-green-800" : "bg-gray-100 text-gray-800", "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"])}" data-v-23a55ad0>${ssrInterpolate(zone.is_active ? "\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19" : "\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19")}</span></div>`);
      if (zone.description) {
        _push(`<p class="mt-2 text-sm text-gray-600" data-v-23a55ad0>${ssrInterpolate(zone.description)}</p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="mt-3 flex items-center text-sm text-gray-500" data-v-23a55ad0><i class="fas fa-home mr-2" data-v-23a55ad0></i><span data-v-23a55ad0>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19: <strong data-v-23a55ad0>${ssrInterpolate(zone.home_visits_count || 0)}</strong> \u0E04\u0E23\u0E31\u0E49\u0E07</span></div></div></div><div class="flex items-center space-x-2 ml-4" data-v-23a55ad0><button class="${ssrRenderClass([zone.is_active ? "hover:bg-yellow-50" : "hover:bg-green-50", "inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"])}" data-v-23a55ad0><i class="${ssrRenderClass([zone.is_active ? "fas fa-toggle-on text-green-600" : "fas fa-toggle-off text-gray-400", "mr-2"])}" data-v-23a55ad0></i> ${ssrInterpolate(zone.is_active ? "\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19" : "\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19")}</button><button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-v-23a55ad0><i class="fas fa-edit mr-2" data-v-23a55ad0></i> \u0E41\u0E01\u0E49\u0E44\u0E02 </button><button${ssrIncludeBooleanAttr(zone.home_visits_count > 0) ? " disabled" : ""} class="${ssrRenderClass([{ "opacity-50 cursor-not-allowed": zone.home_visits_count > 0 }, "inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"])}" data-v-23a55ad0><i class="fas fa-trash mr-2" data-v-23a55ad0></i> \u0E25\u0E1A </button></div></div></div></div>`);
    });
    _push(`<!--]--></div>`);
  } else {
    _push(`<div class="bg-white shadow rounded-lg p-12 text-center" data-v-23a55ad0><i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4" data-v-23a55ad0></i><h3 class="text-lg font-medium text-gray-900 mb-2" data-v-23a55ad0>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E0B\u0E19</h3><p class="text-gray-500 mb-6" data-v-23a55ad0>\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E0B\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1E\u0E37\u0E49\u0E19\u0E17\u0E35\u0E48</p><button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700" data-v-23a55ad0><i class="fas fa-plus mr-2" data-v-23a55ad0></i> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E42\u0E0B\u0E19\u0E41\u0E23\u0E01 </button></div>`);
  }
  if ($setup.zones.data && $setup.zones.data.length > 0) {
    _push(`<div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-lg shadow" data-v-23a55ad0><div class="flex-1 flex justify-between sm:hidden" data-v-23a55ad0><button${ssrIncludeBooleanAttr($setup.zones.current_page === 1) ? " disabled" : ""} class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" data-v-23a55ad0> \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </button><button${ssrIncludeBooleanAttr($setup.zones.current_page === $setup.zones.last_page) ? " disabled" : ""} class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" data-v-23a55ad0> \u0E16\u0E31\u0E14\u0E44\u0E1B </button></div><div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between" data-v-23a55ad0><div data-v-23a55ad0><p class="text-sm text-gray-700" data-v-23a55ad0> \u0E41\u0E2A\u0E14\u0E07 <span class="font-medium" data-v-23a55ad0>${ssrInterpolate($setup.zones.from)}</span> \u0E16\u0E36\u0E07 <span class="font-medium" data-v-23a55ad0>${ssrInterpolate($setup.zones.to)}</span> \u0E08\u0E32\u0E01 <span class="font-medium" data-v-23a55ad0>${ssrInterpolate($setup.zones.total)}</span> \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p></div><div data-v-23a55ad0><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination" data-v-23a55ad0><button${ssrIncludeBooleanAttr($setup.zones.current_page === 1) ? " disabled" : ""} class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" data-v-23a55ad0><i class="fas fa-chevron-left" data-v-23a55ad0></i></button><!--[-->`);
    ssrRenderList($setup.visiblePages, (page) => {
      _push(`<button class="${ssrRenderClass([
        page === $setup.zones.current_page ? "z-10 bg-indigo-50 border-indigo-500 text-indigo-600" : "bg-white border-gray-300 text-gray-500 hover:bg-gray-50",
        "relative inline-flex items-center px-4 py-2 border text-sm font-medium"
      ])}" data-v-23a55ad0>${ssrInterpolate(page)}</button>`);
    });
    _push(`<!--]--><button${ssrIncludeBooleanAttr($setup.zones.current_page === $setup.zones.last_page) ? " disabled" : ""} class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" data-v-23a55ad0><i class="fas fa-chevron-right" data-v-23a55ad0></i></button></nav></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  if ($setup.showModal) {
    _push(`<div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" data-v-23a55ad0><div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0" data-v-23a55ad0><div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" data-v-23a55ad0></div><span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true" data-v-23a55ad0>\u200B</span><div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" data-v-23a55ad0><form data-v-23a55ad0><div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4" data-v-23a55ad0><div class="sm:flex sm:items-start" data-v-23a55ad0><div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10" data-v-23a55ad0><i class="fas fa-map-marked-alt text-indigo-600" data-v-23a55ad0></i></div><div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1" data-v-23a55ad0><h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title" data-v-23a55ad0>${ssrInterpolate($setup.editingZone ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E0B\u0E19" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E42\u0E0B\u0E19\u0E43\u0E2B\u0E21\u0E48")}</h3><div class="mt-4 space-y-4" data-v-23a55ad0><div data-v-23a55ad0><label for="zone_name" class="block text-sm font-medium text-gray-700" data-v-23a55ad0> \u0E0A\u0E37\u0E48\u0E2D\u0E42\u0E0B\u0E19 <span class="text-red-500" data-v-23a55ad0>*</span></label><input${ssrRenderAttr("value", $setup.formData.zone_name)} type="text" id="zone_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E42\u0E0B\u0E19\u0E40\u0E2B\u0E19\u0E37\u0E2D, \u0E42\u0E0B\u0E19\u0E01\u0E25\u0E32\u0E07" data-v-23a55ad0>`);
    if ($setup.errors.zone_name) {
      _push(`<p class="mt-1 text-sm text-red-600" data-v-23a55ad0>${ssrInterpolate($setup.errors.zone_name[0])}</p>`);
    } else {
      _push(`<!---->`);
    }
    _push(`</div><div data-v-23a55ad0><label for="description" class="block text-sm font-medium text-gray-700" data-v-23a55ad0> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </label><textarea id="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E42\u0E0B\u0E19\u0E19\u0E35\u0E49" data-v-23a55ad0>${ssrInterpolate($setup.formData.description)}</textarea></div><div data-v-23a55ad0><label for="color" class="block text-sm font-medium text-gray-700" data-v-23a55ad0> \u0E2A\u0E35\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E42\u0E0B\u0E19 </label><div class="mt-1 flex items-center space-x-3" data-v-23a55ad0><input${ssrRenderAttr("value", $setup.formData.color)} type="color" id="color" class="h-10 w-20 border-gray-300 rounded-md cursor-pointer" data-v-23a55ad0><input${ssrRenderAttr("value", $setup.formData.color)} type="text" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="#3B82F6" data-v-23a55ad0></div><div class="mt-2 flex space-x-2" data-v-23a55ad0><!--[-->`);
    ssrRenderList($setup.presetColors, (presetColor) => {
      _push(`<button type="button" class="w-8 h-8 rounded-full border-2 border-gray-300 hover:border-gray-400" style="${ssrRenderStyle({ backgroundColor: presetColor })}" data-v-23a55ad0></button>`);
    });
    _push(`<!--]--></div></div><div data-v-23a55ad0><label for="display_order" class="block text-sm font-medium text-gray-700" data-v-23a55ad0> \u0E25\u0E33\u0E14\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07 </label><input${ssrRenderAttr("value", $setup.formData.display_order)} type="number" id="display_order" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="0" data-v-23a55ad0><p class="mt-1 text-xs text-gray-500" data-v-23a55ad0>\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02\u0E19\u0E49\u0E2D\u0E22\u0E41\u0E2A\u0E14\u0E07\u0E01\u0E48\u0E2D\u0E19</p></div><div class="flex items-center" data-v-23a55ad0><input${ssrIncludeBooleanAttr(Array.isArray($setup.formData.is_active) ? ssrLooseContain($setup.formData.is_active, null) : $setup.formData.is_active) ? " checked" : ""} type="checkbox" id="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" data-v-23a55ad0><label for="is_active" class="ml-2 block text-sm text-gray-900" data-v-23a55ad0> \u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19 </label></div></div></div></div></div><div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse" data-v-23a55ad0><button type="submit"${ssrIncludeBooleanAttr($setup.saving) ? " disabled" : ""} class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50" data-v-23a55ad0>`);
    if ($setup.saving) {
      _push(`<i class="fas fa-spinner fa-spin mr-2" data-v-23a55ad0></i>`);
    } else {
      _push(`<!---->`);
    }
    _push(` ${ssrInterpolate($setup.saving ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")}</button><button type="button"${ssrIncludeBooleanAttr($setup.saving) ? " disabled" : ""} class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" data-v-23a55ad0> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></form></div></div></div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div>`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/ZoneManagement.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ZoneManagement = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-23a55ad0"]]);

export { ZoneManagement as default };
//# sourceMappingURL=ZoneManagement-BWwnKEHv.mjs.map
