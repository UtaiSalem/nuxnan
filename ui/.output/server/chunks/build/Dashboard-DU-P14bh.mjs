import { H as Head$1 } from './components-DEm4dYEV.mjs';
import { resolveComponent, ref, computed, useSSRContext } from 'vue';
import { H as Head, r as router } from './inertia-vue3-CWdJjaLG.mjs';
import { c as formatDateThai, f as formatTimeAgo } from './dateUtils-DQlkT5wi.mjs';
import DashboardStats from './DashboardStats-BsvXGw85.mjs';
import _sfc_main$2 from './VisitsListSection-CKI7NKpE.mjs';
import VisitFeed from './VisitFeed-bET7Z74y.mjs';
import _sfc_main$1 from './AdminSettings-BsDKqRgC.mjs';
import VisitDetailModal from './VisitDetailModal-DqgjSC1h.mjs';
import { ssrRenderComponent, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrRenderStyle } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';
import 'unhead/utils';
import './ZoneManagement-BWwnKEHv.mjs';
import 'axios';
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

const _sfc_main = {
  components: {
    Head,
    DashboardStats,
    VisitsListSection: _sfc_main$2,
    VisitFeed,
    AdminSettings: _sfc_main$1,
    VisitDetailModal
  },
  props: {
    stats: Object,
    recentVisits: Array,
    monthlyVisits: Array,
    students: Array,
    allVisits: {
      type: Array,
      default: () => []
    },
    zones: {
      type: Array,
      default: () => []
    }
  },
  setup(props) {
    const activeTab = ref("dashboard");
    const showDetailModal = ref(false);
    const selectedVisit = ref(null);
    const tabs = [
      { id: "dashboard", name: "\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14", icon: "fas fa-home" },
      { id: "feed", name: "\u0E1F\u0E35\u0E14\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19", icon: "fas fa-stream" },
      { id: "settings", name: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E30\u0E1A\u0E1A", icon: "fas fa-cog" }
    ];
    const currentDate = computed(() => {
      return formatDateThai((/* @__PURE__ */ new Date()).toISOString(), {
        format: "full",
        weekday: true
      });
    });
    const statusDistribution = computed(() => {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i;
      const total = ((_a = props.stats) == null ? void 0 : _a.total_visits) || 1;
      return [
        {
          name: "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19\u0E41\u0E25\u0E49\u0E27",
          value: ((_b = props.stats) == null ? void 0 : _b.completed_visits) || 0,
          percentage: (((_c = props.stats) == null ? void 0 : _c.completed_visits) || 0) / total * 100,
          color: "bg-green-500",
          bgColor: "bg-gradient-to-r from-green-500 to-emerald-500",
          textColor: "text-green-700"
        },
        {
          name: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
          value: ((_d = props.stats) == null ? void 0 : _d.in_progress_visits) || 0,
          percentage: (((_e = props.stats) == null ? void 0 : _e.in_progress_visits) || 0) / total * 100,
          color: "bg-yellow-500",
          bgColor: "bg-gradient-to-r from-yellow-500 to-orange-500",
          textColor: "text-yellow-700"
        },
        {
          name: "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
          value: ((_f = props.stats) == null ? void 0 : _f.pending_visits) || 0,
          percentage: (((_g = props.stats) == null ? void 0 : _g.pending_visits) || 0) / total * 100,
          color: "bg-orange-500",
          bgColor: "bg-gradient-to-r from-orange-500 to-red-500",
          textColor: "text-orange-700"
        },
        {
          name: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
          value: ((_h = props.stats) == null ? void 0 : _h.cancelled_visits) || 0,
          percentage: (((_i = props.stats) == null ? void 0 : _i.cancelled_visits) || 0) / total * 100,
          color: "bg-red-500",
          bgColor: "bg-gradient-to-r from-red-500 to-rose-500",
          textColor: "text-red-700"
        }
      ];
    });
    const recentActivities = computed(() => {
      const activities = [];
      if (props.allVisits && props.allVisits.length > 0) {
        props.allVisits.slice(0, 5).forEach((visit) => {
          var _a, _b, _c;
          activities.push({
            title: `\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 ${((_a = visit.student) == null ? void 0 : _a.first_name) || ""} ${((_b = visit.student) == null ? void 0 : _b.last_name) || ""}`,
            description: `\u0E42\u0E0B\u0E19: ${((_c = visit.zone) == null ? void 0 : _c.name) || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38"} | \u0E04\u0E23\u0E39: ${visit.teacher_name || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38"}`,
            time: formatTimeAgo(visit.visit_date),
            status: getStatusText(visit.visit_status),
            badge: getStatusBadgeClass(visit.visit_status),
            icon: getStatusIcon(visit.visit_status),
            iconColor: getStatusIconColor(visit.visit_status),
            iconBg: getStatusIconBg(visit.visit_status)
          });
        });
      }
      if (activities.length === 0) {
        activities.push(
          {
            title: "\u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19",
            description: "\u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E1E\u0E23\u0E49\u0E2D\u0E21\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19",
            time: "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48",
            status: "\u0E43\u0E2B\u0E21\u0E48",
            badge: "bg-blue-100 text-blue-800",
            icon: "fas fa-rocket",
            iconColor: "text-blue-600",
            iconBg: "bg-blue-100"
          }
        );
      }
      return activities;
    });
    const quickActions = [
      {
        name: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19",
        description: "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E43\u0E2B\u0E21\u0E48",
        icon: "fas fa-plus-circle",
        iconColor: "text-indigo-600",
        iconBg: "bg-indigo-100",
        textColor: "text-indigo-900",
        class: "bg-indigo-50 border-indigo-200 hover:bg-indigo-100"
      },
      {
        name: "\u0E14\u0E39\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19",
        description: "\u0E2A\u0E23\u0E38\u0E1B\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21",
        icon: "fas fa-chart-line",
        iconColor: "text-green-600",
        iconBg: "bg-green-100",
        textColor: "text-green-900",
        class: "bg-green-50 border-green-200 hover:bg-green-100"
      },
      {
        name: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E42\u0E0B\u0E19",
        description: "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E1E\u0E37\u0E49\u0E19\u0E17\u0E35\u0E48\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21",
        icon: "fas fa-map-marked-alt",
        iconColor: "text-purple-600",
        iconBg: "bg-purple-100",
        textColor: "text-purple-900",
        class: "bg-purple-50 border-purple-200 hover:bg-purple-100"
      },
      {
        name: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32",
        description: "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E23\u0E30\u0E1A\u0E1A",
        icon: "fas fa-cog",
        iconColor: "text-gray-600",
        iconBg: "bg-gray-100",
        textColor: "text-gray-900",
        class: "bg-gray-50 border-gray-200 hover:bg-gray-100"
      }
    ];
    const getStatusText = (status) => {
      const statusMap = {
        "pending": "\u0E23\u0E2D\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        "in-progress": "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23",
        "completed": "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E34\u0E49\u0E19",
        "cancelled": "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01"
      };
      return statusMap[status] || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
    };
    const getStatusBadgeClass = (status) => {
      const classMap = {
        "pending": "bg-orange-100 text-orange-800",
        "in-progress": "bg-yellow-100 text-yellow-800",
        "completed": "bg-green-100 text-green-800",
        "cancelled": "bg-red-100 text-red-800"
      };
      return classMap[status] || "bg-gray-100 text-gray-800";
    };
    const getStatusIcon = (status) => {
      const iconMap = {
        "pending": "fas fa-clock",
        "in-progress": "fas fa-spinner",
        "completed": "fas fa-check-circle",
        "cancelled": "fas fa-times-circle"
      };
      return iconMap[status] || "fas fa-info-circle";
    };
    const getStatusIconColor = (status) => {
      const colorMap = {
        "pending": "text-orange-600",
        "in-progress": "text-yellow-600",
        "completed": "text-green-600",
        "cancelled": "text-red-600"
      };
      return colorMap[status] || "text-gray-600";
    };
    const getStatusIconBg = (status) => {
      const bgMap = {
        "pending": "bg-orange-100",
        "in-progress": "bg-yellow-100",
        "completed": "bg-green-100",
        "cancelled": "bg-red-100"
      };
      return bgMap[status] || "bg-gray-100";
    };
    const viewVisitDetails = (visit) => {
      selectedVisit.value = visit;
      showDetailModal.value = true;
    };
    const closeDetailModal = () => {
      showDetailModal.value = false;
      selectedVisit.value = null;
    };
    const logout = () => {
      router.post("/home-visit/admin/logout");
    };
    return {
      activeTab,
      tabs,
      currentDate,
      statusDistribution,
      recentActivities,
      quickActions,
      showDetailModal,
      selectedVisit,
      viewVisitDetails,
      closeDetailModal,
      logout
    };
  }
};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs, $props, $setup, $data, $options) {
  const _component_Head = Head$1;
  const _component_DashboardStats = resolveComponent("DashboardStats");
  const _component_VisitsListSection = resolveComponent("VisitsListSection");
  const _component_VisitFeed = resolveComponent("VisitFeed");
  const _component_AdminSettings = resolveComponent("AdminSettings");
  const _component_VisitDetailModal = resolveComponent("VisitDetailModal");
  _push(`<!--[-->`);
  _push(ssrRenderComponent(_component_Head, { title: "\u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14" }, null, _parent));
  _push(`<div class="min-h-screen bg-gradient-to-br from-gray-50 via-indigo-50/30 to-purple-50/30" data-v-80b41b34><nav class="bg-white shadow-lg border-b border-gray-100 sticky top-0 z-50 backdrop-blur-md bg-white/95" data-v-80b41b34><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" data-v-80b41b34><div class="flex justify-between h-16" data-v-80b41b34><div class="flex items-center animate-fade-in" data-v-80b41b34><div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center mr-3 shadow-lg" data-v-80b41b34><i class="fas fa-home text-white text-lg" data-v-80b41b34></i></div><h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent" data-v-80b41b34> \u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h1></div><div class="flex items-center space-x-3 animate-fade-in" data-v-80b41b34><div class="hidden md:flex items-center px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg" data-v-80b41b34><i class="fas fa-user-shield text-indigo-600 mr-2" data-v-80b41b34></i><span class="text-sm font-medium text-gray-700" data-v-80b41b34>\u0E41\u0E2D\u0E14\u0E21\u0E34\u0E19</span></div><button class="group flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded-lg transition-all duration-200 hover:shadow-md" data-v-80b41b34><i class="fas fa-sign-out-alt mr-2 group-hover:rotate-12 transition-transform" data-v-80b41b34></i> \u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A </button></div></div></div></nav><div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" data-v-80b41b34><div class="mb-8" data-v-80b41b34><div class="sm:hidden" data-v-80b41b34><select class="block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 shadow-sm" data-v-80b41b34><option value="dashboard" data-v-80b41b34${ssrIncludeBooleanAttr(Array.isArray($setup.activeTab) ? ssrLooseContain($setup.activeTab, "dashboard") : ssrLooseEqual($setup.activeTab, "dashboard")) ? " selected" : ""}>\u{1F3E0} \u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14</option><option value="feed" data-v-80b41b34${ssrIncludeBooleanAttr(Array.isArray($setup.activeTab) ? ssrLooseContain($setup.activeTab, "feed") : ssrLooseEqual($setup.activeTab, "feed")) ? " selected" : ""}>\u{1F4CA} \u0E1F\u0E35\u0E14\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</option><option value="settings" data-v-80b41b34${ssrIncludeBooleanAttr(Array.isArray($setup.activeTab) ? ssrLooseContain($setup.activeTab, "settings") : ssrLooseEqual($setup.activeTab, "settings")) ? " selected" : ""}>\u2699\uFE0F \u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E23\u0E30\u0E1A\u0E1A</option></select></div><div class="hidden sm:block" data-v-80b41b34><div class="bg-white rounded-2xl shadow-lg p-2 border border-gray-100" data-v-80b41b34><nav class="flex space-x-2" data-v-80b41b34><!--[-->`);
  ssrRenderList($setup.tabs, (tab) => {
    _push(`<button class="${ssrRenderClass([
      $setup.activeTab === tab.id ? "bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg scale-105" : "text-gray-600 hover:bg-gray-100 hover:text-gray-900",
      "flex items-center px-6 py-3 rounded-xl font-medium text-sm transition-all duration-200"
    ])}" data-v-80b41b34><i class="${ssrRenderClass([tab.icon, "mr-2"])}" data-v-80b41b34></i> ${ssrInterpolate(tab.name)}</button>`);
  });
  _push(`<!--]--></nav></div></div></div>`);
  if ($setup.activeTab === "dashboard") {
    _push(`<div class="space-y-8" data-v-80b41b34><div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-3xl p-8 md:p-10 overflow-hidden shadow-2xl animate-fade-in" data-v-80b41b34><div class="absolute inset-0 opacity-10" data-v-80b41b34><div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full animate-blob" data-v-80b41b34></div><div class="absolute -bottom-10 -left-10 w-48 h-48 bg-white rounded-full animate-blob animation-delay-2000" data-v-80b41b34></div><div class="absolute top-1/2 left-1/2 w-44 h-44 bg-white rounded-full animate-blob animation-delay-4000" data-v-80b41b34></div></div><div class="relative z-10" data-v-80b41b34><div class="flex flex-col md:flex-row items-start md:items-center justify-between" data-v-80b41b34><div data-v-80b41b34><h2 class="text-3xl md:text-4xl font-bold text-white mb-2" data-v-80b41b34> \u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35\u0E04\u0E23\u0E31\u0E1A! \u{1F44B} </h2><p class="text-white/90 text-lg" data-v-80b41b34> \u0E22\u0E34\u0E19\u0E14\u0E35\u0E15\u0E49\u0E2D\u0E19\u0E23\u0E31\u0E1A\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </p><p class="text-white/75 text-sm mt-2" data-v-80b41b34> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48 ${ssrInterpolate($setup.currentDate)}</p></div><div class="mt-4 md:mt-0" data-v-80b41b34><button class="group flex items-center px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-xl text-white font-medium transition-all duration-200 hover:scale-105 border border-white/30" data-v-80b41b34><i class="fas fa-chart-line mr-2 group-hover:scale-110 transition-transform" data-v-80b41b34></i> \u0E14\u0E39\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19 </button></div></div></div></div><div class="animate-fade-in animation-delay-200" data-v-80b41b34>`);
    _push(ssrRenderComponent(_component_DashboardStats, { stats: $props.stats }, null, _parent));
    _push(`</div><div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in animation-delay-300" data-v-80b41b34><div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300" data-v-80b41b34><div class="flex items-center justify-between mb-6" data-v-80b41b34><div data-v-80b41b34><h3 class="text-lg font-bold text-gray-900 flex items-center" data-v-80b41b34><div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mr-3" data-v-80b41b34><i class="fas fa-chart-bar text-indigo-600" data-v-80b41b34></i></div> \u0E2A\u0E16\u0E34\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E23\u0E32\u0E22\u0E40\u0E14\u0E37\u0E2D\u0E19 </h3><p class="text-sm text-gray-500 mt-1 ml-13" data-v-80b41b34>\u0E41\u0E19\u0E27\u0E42\u0E19\u0E49\u0E21\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 6 \u0E40\u0E14\u0E37\u0E2D\u0E19\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14</p></div></div><div class="h-64 flex items-center justify-center bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl" data-v-80b41b34><div class="text-center" data-v-80b41b34><i class="fas fa-chart-area text-5xl text-indigo-300 mb-4" data-v-80b41b34></i><p class="text-gray-500 text-sm" data-v-80b41b34>\u0E01\u0E23\u0E32\u0E1F\u0E41\u0E2A\u0E14\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E23\u0E32\u0E22\u0E40\u0E14\u0E37\u0E2D\u0E19</p></div></div></div><div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300" data-v-80b41b34><div class="flex items-center justify-between mb-6" data-v-80b41b34><div data-v-80b41b34><h3 class="text-lg font-bold text-gray-900 flex items-center" data-v-80b41b34><div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-3" data-v-80b41b34><i class="fas fa-chart-pie text-purple-600" data-v-80b41b34></i></div> \u0E2A\u0E31\u0E14\u0E2A\u0E48\u0E27\u0E19\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 </h3><p class="text-sm text-gray-500 mt-1 ml-13" data-v-80b41b34>\u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E08\u0E32\u0E22\u0E15\u0E32\u0E21\u0E2A\u0E16\u0E32\u0E19\u0E30</p></div></div><div class="space-y-3" data-v-80b41b34><!--[-->`);
    ssrRenderList($setup.statusDistribution, (status) => {
      _push(`<div class="group hover:scale-102 transition-transform" data-v-80b41b34><div class="flex items-center justify-between mb-1" data-v-80b41b34><span class="text-sm font-medium text-gray-700 flex items-center" data-v-80b41b34><span class="${ssrRenderClass(["w-3 h-3 rounded-full mr-2", status.color])}" data-v-80b41b34></span> ${ssrInterpolate(status.name)}</span><span class="${ssrRenderClass([status.textColor, "text-sm font-bold"])}" data-v-80b41b34>${ssrInterpolate(status.value)}</span></div><div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden" data-v-80b41b34><div class="${ssrRenderClass(["h-2.5 rounded-full transition-all duration-700 ease-out", status.bgColor])}" style="${ssrRenderStyle({ width: status.percentage + "%" })}" data-v-80b41b34></div></div></div>`);
    });
    _push(`<!--]--></div></div></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in animation-delay-400" data-v-80b41b34><div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-v-80b41b34><div class="flex items-center justify-between mb-6" data-v-80b41b34><h3 class="text-lg font-bold text-gray-900 flex items-center" data-v-80b41b34><div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3" data-v-80b41b34><i class="fas fa-clock text-green-600" data-v-80b41b34></i></div> \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 </h3><button class="text-sm text-indigo-600 hover:text-indigo-700 font-medium hover:underline" data-v-80b41b34> \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button></div><div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar" data-v-80b41b34><!--[-->`);
    ssrRenderList($setup.recentActivities, (activity, index) => {
      _push(`<div class="flex items-start p-4 bg-gradient-to-r from-gray-50 to-transparent rounded-xl hover:from-indigo-50 hover:shadow-md transition-all duration-200 group" data-v-80b41b34><div class="${ssrRenderClass(["w-10 h-10 rounded-full flex items-center justify-center mr-4 flex-shrink-0", activity.iconBg])}" data-v-80b41b34><i class="${ssrRenderClass([activity.icon, activity.iconColor])}" data-v-80b41b34></i></div><div class="flex-1 min-w-0" data-v-80b41b34><p class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors" data-v-80b41b34>${ssrInterpolate(activity.title)}</p><p class="text-xs text-gray-500 mt-1" data-v-80b41b34>${ssrInterpolate(activity.description)}</p><p class="text-xs text-gray-400 mt-1 flex items-center" data-v-80b41b34><i class="far fa-clock mr-1" data-v-80b41b34></i> ${ssrInterpolate(activity.time)}</p></div><span class="${ssrRenderClass(["px-2.5 py-1 rounded-full text-xs font-medium", activity.badge])}" data-v-80b41b34>${ssrInterpolate(activity.status)}</span></div>`);
    });
    _push(`<!--]--></div></div><div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100" data-v-80b41b34><h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center" data-v-80b41b34><div class="w-10 h-10 bg-pink-100 rounded-xl flex items-center justify-center mr-3" data-v-80b41b34><i class="fas fa-bolt text-pink-600" data-v-80b41b34></i></div> \u0E17\u0E32\u0E07\u0E25\u0E31\u0E14 </h3><div class="space-y-3" data-v-80b41b34><!--[-->`);
    ssrRenderList($setup.quickActions, (action) => {
      _push(`<button class="${ssrRenderClass(["w-full group flex items-center p-4 rounded-xl transition-all duration-200 hover:scale-105 hover:shadow-lg border-2", action.class])}" data-v-80b41b34><div class="${ssrRenderClass(["w-12 h-12 rounded-xl flex items-center justify-center mr-4 transition-transform group-hover:scale-110", action.iconBg])}" data-v-80b41b34><i class="${ssrRenderClass([action.icon, "text-xl", action.iconColor])}" data-v-80b41b34></i></div><div class="text-left" data-v-80b41b34><div class="${ssrRenderClass(["font-semibold text-sm", action.textColor])}" data-v-80b41b34>${ssrInterpolate(action.name)}</div><div class="text-xs text-gray-500" data-v-80b41b34>${ssrInterpolate(action.description)}</div></div><i class="fas fa-chevron-right ml-auto opacity-0 group-hover:opacity-100 transition-opacity" data-v-80b41b34></i></button>`);
    });
    _push(`<!--]--></div></div></div><div class="animate-fade-in animation-delay-500" data-v-80b41b34>`);
    _push(ssrRenderComponent(_component_VisitsListSection, {
      visits: $props.allVisits,
      zones: $props.zones,
      onViewDetails: $setup.viewVisitDetails
    }, null, _parent));
    _push(`</div></div>`);
  } else {
    _push(`<!---->`);
  }
  if ($setup.activeTab === "feed") {
    _push(`<div class="space-y-6" data-v-80b41b34>`);
    _push(ssrRenderComponent(_component_VisitFeed, {
      visits: $props.allVisits,
      zones: $props.zones,
      onViewDetails: $setup.viewVisitDetails
    }, null, _parent));
    _push(`</div>`);
  } else {
    _push(`<!---->`);
  }
  if ($setup.activeTab === "settings") {
    _push(`<div data-v-80b41b34>`);
    _push(ssrRenderComponent(_component_AdminSettings, { zones: $props.zones }, null, _parent));
    _push(`</div>`);
  } else {
    _push(`<!---->`);
  }
  _push(`</div>`);
  if ($setup.showDetailModal) {
    _push(ssrRenderComponent(_component_VisitDetailModal, {
      visit: $setup.selectedVisit,
      onClose: $setup.closeDetailModal
    }, null, _parent));
  } else {
    _push(`<!---->`);
  }
  _push(`</div><!--]-->`);
}
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Dashboard = /* @__PURE__ */ _export_sfc(_sfc_main, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-80b41b34"]]);

export { Dashboard as default };
//# sourceMappingURL=Dashboard-DU-P14bh.mjs.map
