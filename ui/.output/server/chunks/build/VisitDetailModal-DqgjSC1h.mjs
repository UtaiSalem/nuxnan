import { ref, computed, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
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
  __name: "VisitDetailModal",
  __ssrInlineRender: true,
  props: {
    visit: {
      type: Object,
      required: true
    }
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const imageTab = ref("all");
    const showImageModal = ref(false);
    const selectedImage = ref(null);
    const currentImageIndex = ref(0);
    const evidenceImages = computed(() => {
      var _a;
      return ((_a = props.visit.images) == null ? void 0 : _a.filter((img) => img.image_type === "evidence")) || [];
    });
    const activityImages = computed(() => {
      var _a;
      return ((_a = props.visit.images) == null ? void 0 : _a.filter((img) => img.image_type === "activity")) || [];
    });
    const filteredImages = computed(() => {
      if (imageTab.value === "evidence") return evidenceImages.value;
      if (imageTab.value === "activity") return activityImages.value;
      return props.visit.images || [];
    });
    const getImageUrl = (path) => {
      if (!path) return "";
      if (path.startsWith("http")) return path;
      return `/storage/${path}`;
    };
    const formatDate = (dateString) => {
      if (!dateString) return "-";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
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
    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return "-";
      const date = new Date(dateTimeString);
      return date.toLocaleString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
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
      const baseClass = "px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full";
      const statusClasses = {
        "pending": "bg-orange-100 text-orange-800",
        "in-progress": "bg-yellow-100 text-yellow-800",
        "completed": "bg-green-100 text-green-800",
        "cancelled": "bg-gray-100 text-gray-800"
      };
      return `${baseClass} ${statusClasses[status] || "bg-gray-100 text-gray-800"}`;
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 z-50 overflow-y-auto" }, _attrs))} data-v-f786d967><div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" data-v-f786d967></div><div class="flex min-h-screen items-center justify-center p-4" data-v-f786d967><div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden" data-v-f786d967><div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4" data-v-f786d967><div class="flex items-center justify-between" data-v-f786d967><div class="flex items-center text-white" data-v-f786d967><i class="fas fa-home text-2xl mr-3" data-v-f786d967></i><div data-v-f786d967><h3 class="text-xl font-bold" data-v-f786d967>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</h3><p class="text-indigo-100 text-sm mt-1" data-v-f786d967> \u0E23\u0E2B\u0E31\u0E2A: #${ssrInterpolate(__props.visit.id)}</p></div></div><button class="text-white hover:text-gray-200 transition-colors rounded-full hover:bg-white/20 p-2" title="\u0E1B\u0E34\u0E14\u0E2B\u0E19\u0E49\u0E32\u0E15\u0E48\u0E32\u0E07" data-v-f786d967><i class="fas fa-times text-2xl" data-v-f786d967></i></button></div></div><div class="overflow-y-auto max-h-[calc(90vh-80px)]" data-v-f786d967><div class="p-6 space-y-6" data-v-f786d967><div class="grid grid-cols-1 md:grid-cols-4 gap-4" data-v-f786d967><div class="bg-blue-50 rounded-lg p-4" data-v-f786d967><div class="text-sm font-medium text-blue-600 mb-1" data-v-f786d967>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</div><div class="text-lg font-bold text-gray-900" data-v-f786d967>${ssrInterpolate(formatDate(__props.visit.visit_date))}</div>`);
      if (__props.visit.visit_time) {
        _push(`<div class="text-sm text-gray-600 mt-1" data-v-f786d967> \u0E40\u0E27\u0E25\u0E32 ${ssrInterpolate(formatTime(__props.visit.visit_time))}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="bg-green-50 rounded-lg p-4" data-v-f786d967><div class="text-sm font-medium text-green-600 mb-1" data-v-f786d967>\u0E2A\u0E16\u0E32\u0E19\u0E30</div><div data-v-f786d967><span class="${ssrRenderClass(getStatusBadgeClass(__props.visit.visit_status))}" data-v-f786d967>${ssrInterpolate(getStatusText(__props.visit.visit_status))}</span></div></div><div class="bg-purple-50 rounded-lg p-4" data-v-f786d967><div class="text-sm font-medium text-purple-600 mb-1" data-v-f786d967>\u0E42\u0E0B\u0E19</div><div class="text-sm font-semibold text-gray-900" data-v-f786d967><i class="fas fa-map-marker-alt mr-1" data-v-f786d967></i> ${ssrInterpolate(((_a = __props.visit.zone) == null ? void 0 : _a.zone_name) || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38")}</div></div><div class="bg-orange-50 rounded-lg p-4" data-v-f786d967><div class="text-sm font-medium text-orange-600 mb-1" data-v-f786d967>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</div><div class="text-lg font-bold text-gray-900" data-v-f786d967><i class="fas fa-images mr-2" data-v-f786d967></i> ${ssrInterpolate(((_b = __props.visit.images) == null ? void 0 : _b.length) || 0)} \u0E23\u0E39\u0E1B </div></div></div><div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-user-graduate mr-2 text-indigo-600" data-v-f786d967></i> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h4>`);
      if (__props.visit.student) {
        _push(`<div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-v-f786d967><div class="flex items-center space-x-4" data-v-f786d967><div class="flex-shrink-0" data-v-f786d967><div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center" data-v-f786d967><span class="text-indigo-600 font-bold text-xl" data-v-f786d967>${ssrInterpolate(getInitials(__props.visit.student))}</span></div></div><div data-v-f786d967><div class="text-lg font-semibold text-gray-900" data-v-f786d967>${ssrInterpolate(getStudentFullName(__props.visit.student))}</div>`);
        if (__props.visit.student.nickname) {
          _push(`<div class="text-sm text-gray-500" data-v-f786d967> \u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E48\u0E19: ${ssrInterpolate(__props.visit.student.nickname)}</div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.visit.student.student_code) {
          _push(`<div class="text-sm text-gray-600 mt-1" data-v-f786d967> \u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19: ${ssrInterpolate(__props.visit.student.student_code)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="space-y-2" data-v-f786d967>`);
        if (__props.visit.student.email) {
          _push(`<div class="text-sm" data-v-f786d967><span class="text-gray-500" data-v-f786d967>\u0E2D\u0E35\u0E40\u0E21\u0E25:</span><span class="text-gray-900 ml-2" data-v-f786d967>${ssrInterpolate(__props.visit.student.email)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.visit.student.phone) {
          _push(`<div class="text-sm" data-v-f786d967><span class="text-gray-500" data-v-f786d967>\u0E40\u0E1A\u0E2D\u0E23\u0E4C\u0E42\u0E17\u0E23:</span><span class="text-gray-900 ml-2" data-v-f786d967>${ssrInterpolate(__props.visit.student.phone)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.visit.student.classroom) {
          _push(`<div class="text-sm" data-v-f786d967><span class="text-gray-500" data-v-f786d967>\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19:</span><span class="text-gray-900 ml-2" data-v-f786d967>${ssrInterpolate(__props.visit.student.classroom)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<div class="text-gray-500 italic" data-v-f786d967> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </div>`);
      }
      _push(`</div><div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-users mr-2 text-indigo-600" data-v-f786d967></i> \u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h4>`);
      if (__props.visit.participants && __props.visit.participants.length > 0) {
        _push(`<div class="space-y-3" data-v-f786d967><!--[-->`);
        ssrRenderList(__props.visit.participants, (participant) => {
          _push(`<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg" data-v-f786d967><div class="flex items-center space-x-3" data-v-f786d967><div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center" data-v-f786d967><i class="fas fa-user text-indigo-600" data-v-f786d967></i></div><div data-v-f786d967><div class="font-medium text-gray-900" data-v-f786d967>${ssrInterpolate(participant.participant_name)}</div>`);
          if (participant.participant_position) {
            _push(`<div class="text-sm text-gray-500" data-v-f786d967>${ssrInterpolate(participant.participant_position)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
          if (participant.participant_role) {
            _push(`<div class="text-sm" data-v-f786d967><span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium" data-v-f786d967>${ssrInterpolate(participant.participant_role)}</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (__props.visit.visitor_name) {
        _push(`<div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg" data-v-f786d967><div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center" data-v-f786d967><i class="fas fa-user text-indigo-600" data-v-f786d967></i></div><div data-v-f786d967><div class="font-medium text-gray-900" data-v-f786d967>${ssrInterpolate(__props.visit.visitor_name)}</div>`);
        if (__props.visit.visitor_position) {
          _push(`<div class="text-sm text-gray-500" data-v-f786d967>${ssrInterpolate(__props.visit.visitor_position)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<div class="text-gray-500 italic text-center py-4" data-v-f786d967> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 </div>`);
      }
      _push(`</div>`);
      if (__props.visit.observations) {
        _push(`<div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-clipboard-list mr-2 text-indigo-600" data-v-f786d967></i> \u0E1C\u0E25\u0E01\u0E32\u0E23\u0E2A\u0E31\u0E07\u0E40\u0E01\u0E15 </h4><div class="prose max-w-none" data-v-f786d967><p class="text-gray-700 whitespace-pre-wrap" data-v-f786d967>${ssrInterpolate(__props.visit.observations)}</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.visit.notes) {
        _push(`<div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-sticky-note mr-2 text-indigo-600" data-v-f786d967></i> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 </h4><div class="prose max-w-none" data-v-f786d967><p class="text-gray-700 whitespace-pre-wrap" data-v-f786d967>${ssrInterpolate(__props.visit.notes)}</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.visit.recommendations) {
        _push(`<div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-lightbulb mr-2 text-yellow-600" data-v-f786d967></i> \u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30 </h4><div class="prose max-w-none" data-v-f786d967><p class="text-gray-700 whitespace-pre-wrap" data-v-f786d967>${ssrInterpolate(__props.visit.recommendations)}</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.visit.follow_up) {
        _push(`<div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-tasks mr-2 text-indigo-600" data-v-f786d967></i> \u0E01\u0E32\u0E23\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E1C\u0E25 </h4><div class="prose max-w-none" data-v-f786d967><p class="text-gray-700 whitespace-pre-wrap" data-v-f786d967>${ssrInterpolate(__props.visit.follow_up)}</p></div>`);
        if (__props.visit.next_visit) {
          _push(`<div class="mt-4 p-3 bg-blue-50 rounded-lg" data-v-f786d967><div class="text-sm font-medium text-blue-900" data-v-f786d967><i class="fas fa-calendar-alt mr-2" data-v-f786d967></i> \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E04\u0E23\u0E31\u0E49\u0E07\u0E16\u0E31\u0E14\u0E44\u0E1B: ${ssrInterpolate(formatDate(__props.visit.next_visit))}</div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.visit.images && __props.visit.images.length > 0) {
        _push(`<div class="bg-white border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-images mr-2 text-indigo-600" data-v-f786d967></i> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A (${ssrInterpolate(__props.visit.images.length)} \u0E23\u0E39\u0E1B) </h4><div class="mb-4 flex space-x-2 border-b border-gray-200" data-v-f786d967><button class="${ssrRenderClass([
          "px-4 py-2 text-sm font-medium border-b-2 transition-colors",
          imageTab.value === "all" ? "border-indigo-500 text-indigo-600" : "border-transparent text-gray-500 hover:text-gray-700"
        ])}" data-v-f786d967> \u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 (${ssrInterpolate(__props.visit.images.length)}) </button><button class="${ssrRenderClass([
          "px-4 py-2 text-sm font-medium border-b-2 transition-colors",
          imageTab.value === "evidence" ? "border-indigo-500 text-indigo-600" : "border-transparent text-gray-500 hover:text-gray-700"
        ])}" data-v-f786d967> \u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19 (${ssrInterpolate(evidenceImages.value.length)}) </button><button class="${ssrRenderClass([
          "px-4 py-2 text-sm font-medium border-b-2 transition-colors",
          imageTab.value === "activity" ? "border-indigo-500 text-indigo-600" : "border-transparent text-gray-500 hover:text-gray-700"
        ])}" data-v-f786d967> \u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21 (${ssrInterpolate(activityImages.value.length)}) </button></div><div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" data-v-f786d967><!--[-->`);
        ssrRenderList(filteredImages.value, (image) => {
          _push(`<div class="relative group cursor-pointer" data-v-f786d967><div class="aspect-square rounded-lg overflow-hidden bg-gray-100" data-v-f786d967><img${ssrRenderAttr("src", getImageUrl(image.image_path))}${ssrRenderAttr("alt", image.image_description || "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19")} class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200" data-v-f786d967></div><div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-opacity rounded-lg flex items-center justify-center" data-v-f786d967><i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity" data-v-f786d967></i></div>`);
          if (image.image_type) {
            _push(`<div class="absolute top-2 right-2" data-v-f786d967><span class="${ssrRenderClass([
              "px-2 py-1 rounded-full text-xs font-medium",
              image.image_type === "evidence" ? "bg-blue-500 text-white" : "bg-green-500 text-white"
            ])}" data-v-f786d967>${ssrInterpolate(image.image_type === "evidence" ? "\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19" : "\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21")}</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="bg-gray-50 border border-gray-200 rounded-lg p-6" data-v-f786d967><h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center" data-v-f786d967><i class="fas fa-info-circle mr-2 text-gray-600" data-v-f786d967></i> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 </h4><div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" data-v-f786d967><div data-v-f786d967><span class="text-gray-500" data-v-f786d967>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D:</span><span class="text-gray-900 ml-2" data-v-f786d967>${ssrInterpolate(formatDateTime(__props.visit.created_at))}</span></div><div data-v-f786d967><span class="text-gray-500" data-v-f786d967>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14:</span><span class="text-gray-900 ml-2" data-v-f786d967>${ssrInterpolate(formatDateTime(__props.visit.updated_at))}</span></div>`);
      if (__props.visit.creator) {
        _push(`<div data-v-f786d967><span class="text-gray-500" data-v-f786d967>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E14\u0E22:</span><span class="text-gray-900 ml-2" data-v-f786d967>${ssrInterpolate(__props.visit.creator.name)}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div></div><div class="border-t border-gray-200 px-6 py-4 bg-gray-50 sticky bottom-0" data-v-f786d967><div class="flex items-center justify-between" data-v-f786d967><button class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors" data-v-f786d967><i class="fas fa-print mr-2" data-v-f786d967></i> \u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19 </button><div class="flex space-x-3" data-v-f786d967><button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors" data-v-f786d967><i class="fas fa-download mr-2" data-v-f786d967></i> \u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14 PDF </button><button class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-lg" data-v-f786d967><i class="fas fa-times mr-2" data-v-f786d967></i> \u0E1B\u0E34\u0E14 </button></div></div></div><button class="fixed bottom-6 right-6 z-10 w-14 h-14 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all hover:scale-110" title="\u0E1B\u0E34\u0E14\u0E2B\u0E19\u0E49\u0E32\u0E15\u0E48\u0E32\u0E07" data-v-f786d967><i class="fas fa-times text-xl" data-v-f786d967></i></button></div></div>`);
      if (showImageModal.value) {
        _push(`<div class="fixed inset-0 z-[60] bg-black bg-opacity-90 flex items-center justify-center p-4" data-v-f786d967><button class="absolute top-4 right-4 text-white hover:text-gray-300 text-3xl" data-v-f786d967><i class="fas fa-times" data-v-f786d967></i></button><div class="max-w-5xl max-h-full" data-v-f786d967><img${ssrRenderAttr("src", getImageUrl((_c = selectedImage.value) == null ? void 0 : _c.image_path))}${ssrRenderAttr("alt", (_d = selectedImage.value) == null ? void 0 : _d.image_description)} class="max-w-full max-h-[80vh] object-contain mx-auto" data-v-f786d967>`);
        if ((_e = selectedImage.value) == null ? void 0 : _e.image_description) {
          _push(`<div class="text-white text-center mt-4" data-v-f786d967>${ssrInterpolate(selectedImage.value.image_description)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (currentImageIndex.value > 0) {
          _push(`<button class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-4xl" data-v-f786d967><i class="fas fa-chevron-left" data-v-f786d967></i></button>`);
        } else {
          _push(`<!---->`);
        }
        if (currentImageIndex.value < filteredImages.value.length - 1) {
          _push(`<button class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-4xl" data-v-f786d967><i class="fas fa-chevron-right" data-v-f786d967></i></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Admin/Components/VisitDetailModal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const VisitDetailModal = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-f786d967"]]);

export { VisitDetailModal as default };
//# sourceMappingURL=VisitDetailModal-DqgjSC1h.mjs.map
