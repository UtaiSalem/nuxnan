import { defineComponent, inject, unref, ref, watch, computed, mergeProps, withCtx, createVNode, createBlock, createCommentVNode, openBlock, toDisplayString, withDirectives, createTextVNode, vModelText, nextTick, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrIncludeBooleanAttr, ssrRenderAttr } from 'vue/server-renderer';
import { _ as _sfc_main$5 } from './DialogModal-CfsI63S_.mjs';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, i as useApi, a as useNuxtApp } from './server.mjs';
import { C as ContentLoader } from './ContentLoader-D4cV05oG.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import Swal from 'sweetalert2';
import { s as setInterval } from './interval-BEVgA3pa.mjs';
import './Modal-DYe4d1RC.mjs';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "AttendanceStatusBadge",
  __ssrInlineRender: true,
  props: {
    status: {},
    attendanceId: {},
    memberId: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  emits: ["update-status"],
  setup(__props, { emit: __emit }) {
    const { $apiFetch } = useNuxtApp();
    const props = __props;
    const currentStatus = ref(props.status);
    const showMenu = ref(false);
    const isLoading = ref(false);
    const menuRef = ref(null);
    watch(() => props.status, (newVal) => {
      currentStatus.value = newVal;
    });
    const statusOptions = [
      { value: 1, label: "\u0E21\u0E32", icon: "heroicons:check-circle", color: "green" },
      { value: 2, label: "\u0E2A\u0E32\u0E22", icon: "heroicons:clock", color: "amber" },
      { value: 3, label: "\u0E25\u0E32", icon: "heroicons:document-text", color: "blue" },
      { value: null, label: "\u0E02\u0E32\u0E14", icon: "heroicons:x-circle", color: "red" }
    ];
    const currentStatusConfig = computed(() => {
      const status = currentStatus.value;
      if (status === 1) {
        return {
          label: "\u0E21\u0E32",
          icon: "heroicons:check-circle-20-solid",
          color: "green",
          bgClass: "bg-green-50 dark:bg-green-900/40",
          textClass: "text-green-800 dark:text-green-200",
          iconClass: "text-green-600 dark:text-green-400"
        };
      }
      if (status === 2) {
        return {
          label: "\u0E2A\u0E32\u0E22",
          icon: "heroicons:clock-20-solid",
          color: "amber",
          bgClass: "bg-amber-50 dark:bg-amber-900/40",
          textClass: "text-amber-800 dark:text-amber-200",
          iconClass: "text-amber-600 dark:text-amber-400"
        };
      }
      if (status === 3) {
        return {
          label: "\u0E25\u0E32",
          icon: "heroicons:document-text-20-solid",
          color: "blue",
          bgClass: "bg-blue-50 dark:bg-blue-900/40",
          textClass: "text-blue-800 dark:text-blue-200",
          iconClass: "text-blue-600 dark:text-blue-400"
        };
      }
      return {
        label: "\u0E02\u0E32\u0E14",
        icon: "heroicons:x-circle-20-solid",
        color: "red",
        bgClass: "bg-red-50 dark:bg-red-900/40",
        textClass: "text-red-800 dark:text-red-200",
        iconClass: "text-red-600 dark:text-red-400"
      };
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        ref_key: "menuRef",
        ref: menuRef,
        class: "relative inline-flex items-center gap-1"
      }, _attrs))} data-v-def1b4d7><div class="${ssrRenderClass([
        "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all duration-200 border",
        currentStatusConfig.value.bgClass,
        currentStatusConfig.value.textClass,
        currentStatusConfig.value.color === "green" ? "border-green-200 dark:border-green-800" : "",
        currentStatusConfig.value.color === "amber" ? "border-amber-200 dark:border-amber-800" : "",
        currentStatusConfig.value.color === "blue" ? "border-blue-200 dark:border-blue-800" : "",
        currentStatusConfig.value.color === "red" ? "border-red-200 dark:border-red-800" : "",
        __props.isCourseAdmin ? "cursor-pointer hover:shadow-sm active:scale-95" : ""
      ])}"${ssrRenderAttr("title", __props.isCourseAdmin ? "\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E2A\u0E16\u0E32\u0E19\u0E30" : currentStatusConfig.value.label)} data-v-def1b4d7>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: isLoading.value ? "svg-spinners:ring-resize" : currentStatusConfig.value.icon,
        class: ["w-4 h-4", currentStatusConfig.value.iconClass]
      }, null, _parent));
      _push(`<span data-v-def1b4d7>${ssrInterpolate(currentStatusConfig.value.label)}</span>`);
      if (__props.isCourseAdmin && !isLoading.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:chevron-down",
          class: ["w-3 h-3 ml-0.5 transition-transform duration-200", [{ "rotate-180": showMenu.value }, currentStatusConfig.value.iconClass]]
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (showMenu.value && __props.isCourseAdmin) {
        _push(`<div class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-36 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50" data-v-def1b4d7><div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600" data-v-def1b4d7><p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide" data-v-def1b4d7>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E2A\u0E16\u0E32\u0E19\u0E30</p></div><div class="py-1" data-v-def1b4d7><!--[-->`);
        ssrRenderList(statusOptions, (option) => {
          _push(`<button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="${ssrRenderClass([[
            currentStatus.value === option.value || currentStatus.value === null && option.value === null || currentStatus.value === void 0 && option.value === null || currentStatus.value === 0 && option.value === null ? `bg-${option.color}-50 dark:bg-${option.color}-900/20 text-${option.color}-700 dark:text-${option.color}-300` : "text-gray-700 dark:text-gray-300"
          ], "w-full flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium transition-all duration-150 hover:bg-gray-100 dark:hover:bg-gray-700/50"])}" data-v-def1b4d7><div class="${ssrRenderClass([
            "w-7 h-7 rounded-full flex items-center justify-center",
            option.color === "green" ? "bg-green-100 dark:bg-green-900/30" : "",
            option.color === "amber" ? "bg-amber-100 dark:bg-amber-900/30" : "",
            option.color === "blue" ? "bg-blue-100 dark:bg-blue-900/30" : "",
            option.color === "red" ? "bg-red-100 dark:bg-red-900/30" : ""
          ])}" data-v-def1b4d7>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: option.icon,
            class: ["w-4 h-4", [
              option.color === "green" ? "text-green-600 dark:text-green-400" : "",
              option.color === "amber" ? "text-amber-600 dark:text-amber-400" : "",
              option.color === "blue" ? "text-blue-600 dark:text-blue-400" : "",
              option.color === "red" ? "text-red-600 dark:text-red-400" : ""
            ]]
          }, null, _parent));
          _push(`</div><span class="flex-1 text-left" data-v-def1b4d7>${ssrInterpolate(option.label)}</span>`);
          if (currentStatus.value === option.value || currentStatus.value === null && option.value === null || currentStatus.value === void 0 && option.value === null || currentStatus.value === 0 && option.value === null) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:check",
              class: ["w-4 h-4", [
                option.color === "green" ? "text-green-600" : "",
                option.color === "amber" ? "text-amber-600" : "",
                option.color === "blue" ? "text-blue-600" : "",
                option.color === "red" ? "text-red-600" : ""
              ]]
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</button>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AttendanceStatusBadge.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const AttendanceStatusBadge = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-def1b4d7"]]);
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "AttendancesTable",
  __ssrInlineRender: true,
  props: {
    attendances: {},
    groupMembers: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  emits: ["create", "view-details", "edit", "delete", "update-status"],
  setup(__props, { emit: __emit }) {
    const { getAvatarUrl } = useAvatar();
    const props = __props;
    const emit = __emit;
    const formatDate = (dateString) => {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", { day: "numeric", month: "short" });
    };
    const sortedAttendances = computed(() => {
      return [...props.attendances].sort((a, b) => {
        const dateA = new Date(a.date || a.start_at || a.created_at).getTime();
        const dateB = new Date(b.date || b.start_at || b.created_at).getTime();
        return dateA - dateB;
      });
    });
    const getMemberStatus = (attendance, memberId) => {
      var _a;
      const detail = (_a = attendance.details) == null ? void 0 : _a.find((d) => d.course_member_id === memberId);
      return detail == null ? void 0 : detail.status;
    };
    const getMemberAvatar = (member) => {
      return getAvatarUrl(member.user || member);
    };
    const tableContainer = ref(null);
    const scrollToEnd = async () => {
      await nextTick();
      if (tableContainer.value) {
        tableContainer.value.scrollLeft = tableContainer.value.scrollWidth;
      }
    };
    watch(() => props.attendances, () => {
      scrollToEnd();
    }, { deep: true });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="relative overflow-x-auto"><table class="w-full"><thead class="bg-gradient-to-r from-gray-50 via-gray-100 to-slate-100 dark:from-gray-700 dark:via-gray-800 dark:to-gray-700 border-b-2 border-gray-200 dark:border-gray-600"><tr class="text-center"><th scope="col" class="px-6 py-4 border border-slate-300 dark:border-gray-600 font-black text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 min-w-[280px] sticky left-0 z-20"><div class="flex items-center justify-center gap-3"><div class="w-10 h-10 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900 dark:to-purple-900 rounded-xl flex items-center justify-center shadow-sm">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:people-24-filled",
        class: "w-5 h-5 text-violet-600 dark:text-violet-400"
      }, null, _parent));
      _push(`</div><span class="uppercase tracking-wide">\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></div></th><!--[-->`);
      ssrRenderList(unref(sortedAttendances), (attendance, index) => {
        _push(`<th scope="col" class="px-3 py-4 border border-slate-300 dark:border-gray-600 min-w-[100px]"><button class="flex flex-col justify-center items-center mx-auto text-sm font-bold text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-xl px-3 py-2 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"><span class="text-xs text-gray-500 dark:text-gray-400">#${ssrInterpolate(index + 1)}</span><span class="mt-1">${ssrInterpolate(formatDate(attendance.date))}</span>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:eye-24-regular",
          width: "16",
          height: "16",
          class: "mt-1"
        }, null, _parent));
        _push(`</button></th>`);
      });
      _push(`<!--]-->`);
      if (__props.isCourseAdmin) {
        _push(`<th scope="col" class="px-3 py-4 border bg-white dark:bg-gray-800 sticky right-0 z-20"><button class="flex justify-center items-center mx-auto text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-xl p-3 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md" title="\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E43\u0E2B\u0E21\u0E48">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-circle-24-filled",
          width: "24",
          height: "24"
        }, null, _parent));
        _push(`</button></th>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tr></thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
      ssrRenderList(__props.groupMembers, (member) => {
        var _a, _b;
        _push(`<tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 transition-all duration-200"><td class="p-2 border border-slate-300 dark:border-gray-600 whitespace-nowrap sticky left-0 z-10 bg-white dark:bg-gray-800"><div class="flex items-center min-w-fit group"><div class="relative"><img class="w-14 h-14 rounded-full border-2 border-white dark:border-gray-700 shadow-md transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg object-cover"${ssrRenderAttr("src", getMemberAvatar(member))}${ssrRenderAttr("alt", ((_a = member.user) == null ? void 0 : _a.name) || member.name || "Avatar")}><div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div></div><div class="ml-3 flex-1"><p class="text-gray-900 dark:text-gray-100 font-bold text-md tracking-wide group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors duration-200">${ssrInterpolate(member.name || member.member_name || ((_b = member.user) == null ? void 0 : _b.name) || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E0A\u0E37\u0E48\u0E2D")}</p><div class="flex items-center mt-1 space-x-2 text-xs">`);
        if (member.order_number) {
          _push(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-violet-100 to-purple-100 dark:from-violet-900/50 dark:to-purple-900/50 text-violet-700 dark:text-violet-300 rounded-full font-medium border border-violet-200 dark:border-violet-700">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:number-symbol-square-20-regular",
            width: "14",
            height: "14"
          }, null, _parent));
          _push(`<span>${ssrInterpolate(member.order_number)}</span></span>`);
        } else {
          _push(`<!---->`);
        }
        if (member.group) {
          _push(`<span class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50 text-blue-700 dark:text-blue-300 rounded-full font-medium border border-blue-200 dark:border-blue-700">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-team-20-regular",
            width: "14",
            height: "14"
          }, null, _parent));
          _push(`<span>${ssrInterpolate(member.group.name)}</span></span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div></td><!--[-->`);
        ssrRenderList(unref(sortedAttendances), (attendance) => {
          _push(`<td class="p-3 whitespace-nowrap border border-slate-300 dark:border-gray-600 text-center"><div class="flex justify-center items-center">`);
          _push(ssrRenderComponent(AttendanceStatusBadge, {
            status: getMemberStatus(attendance, member.id),
            attendanceId: attendance.id,
            memberId: member.id,
            isCourseAdmin: __props.isCourseAdmin,
            onUpdateStatus: ($event) => emit("update-status", $event)
          }, null, _parent));
          _push(`</div></td>`);
        });
        _push(`<!--]--></tr>`);
      });
      _push(`<!--]-->`);
      if (__props.groupMembers.length === 0) {
        _push(`<tr><td${ssrRenderAttr("colspan", __props.attendances.length + (__props.isCourseAdmin ? 2 : 1))} class="p-12 text-center"><div class="flex flex-col items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-24-regular",
          class: "w-16 h-16 text-gray-400 dark:text-gray-600 mb-4"
        }, null, _parent));
        _push(`<p class="text-gray-500 dark:text-gray-400 font-medium">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49</p></div></td></tr>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</tbody></table></div></div>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AttendancesTable.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "StudentAttendanceTable",
  __ssrInlineRender: true,
  props: {
    attendances: {},
    loading: { type: Boolean, default: false }
  },
  emits: ["reload", "check-in-success"],
  setup(__props, { emit: __emit }) {
    useApi();
    const checkingInId = ref(null);
    const isAttendanceActive = (attendance) => {
      const now = /* @__PURE__ */ new Date();
      const startAt = new Date(attendance.start_at);
      const finishAt = new Date(attendance.finish_at);
      return now >= startAt && now <= finishAt;
    };
    const isAttendanceNotStarted = (attendance) => {
      const now = /* @__PURE__ */ new Date();
      const startAt = new Date(attendance.start_at);
      return now < startAt;
    };
    const hasCheckedIn = (attendance) => {
      return attendance.status === 1 || attendance.status === 2;
    };
    const wouldBeLate = (attendance) => {
      const now = /* @__PURE__ */ new Date();
      const startAt = new Date(attendance.start_at);
      const lateTime = attendance.late_time || 15;
      const lateThreshold = new Date(startAt.getTime() + lateTime * 6e4);
      return now > lateThreshold;
    };
    const formatDate = (dateString) => {
      if (!dateString) return "-";
      const date = new Date(dateString);
      const day = date.getDate();
      const month = date.getMonth() + 1;
      const year = date.getFullYear() + 543;
      return `${String(day).padStart(2, "0")}/${String(month).padStart(2, "0")}/${year}`;
    };
    const formatDayOfWeek = (dateString) => {
      if (!dateString) return "";
      const date = new Date(dateString);
      const days = ["\u0E27\u0E31\u0E19\u0E2D\u0E32\u0E17\u0E34\u0E15\u0E22\u0E4C", "\u0E27\u0E31\u0E19\u0E08\u0E31\u0E19\u0E17\u0E23\u0E4C", "\u0E27\u0E31\u0E19\u0E2D\u0E31\u0E07\u0E04\u0E32\u0E23", "\u0E27\u0E31\u0E19\u0E1E\u0E38\u0E18", "\u0E27\u0E31\u0E19\u0E1E\u0E24\u0E2B\u0E31\u0E2A\u0E1A\u0E14\u0E35", "\u0E27\u0E31\u0E19\u0E28\u0E38\u0E01\u0E23\u0E4C", "\u0E27\u0E31\u0E19\u0E40\u0E2A\u0E32\u0E23\u0E4C"];
      return days[date.getDay()];
    };
    const formatTime = (dateTimeString) => {
      if (!dateTimeString) return "-";
      const date = new Date(dateTimeString);
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      return `${hours}:${minutes}`;
    };
    const getStatusConfig = (status) => {
      if (status === 1) {
        return {
          label: "\u0E21\u0E32",
          icon: "heroicons:check-circle-20-solid",
          bgClass: "bg-green-100 dark:bg-green-900/40",
          textClass: "text-green-700 dark:text-green-300",
          iconClass: "text-green-600 dark:text-green-400",
          dotClass: "bg-green-500"
        };
      }
      if (status === 2) {
        return {
          label: "\u0E2A\u0E32\u0E22",
          icon: "heroicons:clock-20-solid",
          bgClass: "bg-amber-100 dark:bg-amber-900/40",
          textClass: "text-amber-700 dark:text-amber-300",
          iconClass: "text-amber-600 dark:text-amber-400",
          dotClass: "bg-amber-500"
        };
      }
      if (status === 3) {
        return {
          label: "\u0E25\u0E32",
          icon: "heroicons:document-text-20-solid",
          bgClass: "bg-blue-100 dark:bg-blue-900/40",
          textClass: "text-blue-700 dark:text-blue-300",
          iconClass: "text-blue-600 dark:text-blue-400",
          dotClass: "bg-blue-500"
        };
      }
      return {
        label: "\u0E02\u0E32\u0E14",
        icon: "heroicons:x-circle-20-solid",
        bgClass: "bg-red-100 dark:bg-red-900/40",
        textClass: "text-red-700 dark:text-red-300",
        iconClass: "text-red-600 dark:text-red-400",
        dotClass: "bg-red-500"
      };
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="bg-gradient-to-r from-violet-500 via-purple-500 to-fuchsia-500 px-6 py-4"><div class="flex items-center justify-between"><div class="flex items-center gap-3"><div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:calendar-checkmark-24-filled",
        class: "w-7 h-7 text-white"
      }, null, _parent));
      _push(`</div><div><h3 class="text-white/80 text-sm font-medium">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</h3><p class="text-white text-lg font-bold"> \u0E41\u0E2A\u0E14\u0E07 ${ssrInterpolate(__props.attendances.length)} \u0E08\u0E32\u0E01 ${ssrInterpolate(__props.attendances.length)} \u0E23\u0E32\u0E22\u0E01\u0E32\u0E23 </p></div></div><button${ssrIncludeBooleanAttr(__props.loading) ? " disabled" : ""} class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all duration-200 font-medium disabled:opacity-50">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:arrow-sync-24-filled",
        class: ["w-5 h-5", { "animate-spin": __props.loading }]
      }, null, _parent));
      _push(`<span>\u0E23\u0E35\u0E42\u0E2B\u0E25\u0E14</span></button></div></div><div class="overflow-x-auto"><table class="w-full"><thead class="bg-gradient-to-r from-violet-50 via-purple-50 to-fuchsia-50 dark:from-violet-900/30 dark:via-purple-900/30 dark:to-fuchsia-900/30"><tr><th class="px-4 py-4 text-center"><div class="flex items-center justify-center gap-2"><div class="w-8 h-8 bg-violet-100 dark:bg-violet-800 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:number-symbol-24-regular",
        class: "w-5 h-5 text-violet-600 dark:text-violet-400"
      }, null, _parent));
      _push(`</div><span class="text-sm font-bold text-violet-700 dark:text-violet-300">\u0E25\u0E33\u0E14\u0E31\u0E1A</span></div></th><th class="px-4 py-4 text-center"><div class="flex items-center justify-center gap-2"><div class="w-8 h-8 bg-purple-100 dark:bg-purple-800 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:calendar-24-regular",
        class: "w-5 h-5 text-purple-600 dark:text-purple-400"
      }, null, _parent));
      _push(`</div><span class="text-sm font-bold text-purple-700 dark:text-purple-300">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48</span></div></th><th class="px-4 py-4 text-center"><div class="flex items-center justify-center gap-2"><div class="w-8 h-8 bg-cyan-100 dark:bg-cyan-800 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:play-circle-24-regular",
        class: "w-5 h-5 text-cyan-600 dark:text-cyan-400"
      }, null, _parent));
      _push(`</div><span class="text-sm font-bold text-cyan-700 dark:text-cyan-300">\u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21</span></div></th><th class="px-4 py-4 text-center"><div class="flex items-center justify-center gap-2"><div class="w-8 h-8 bg-pink-100 dark:bg-pink-800 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:stop-24-regular",
        class: "w-5 h-5 text-pink-600 dark:text-pink-400"
      }, null, _parent));
      _push(`</div><span class="text-sm font-bold text-pink-700 dark:text-pink-300">\u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</span></div></th><th class="px-4 py-4 text-center"><div class="flex items-center justify-center gap-2"><div class="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:document-text-24-regular",
        class: "w-5 h-5 text-slate-600 dark:text-slate-400"
      }, null, _parent));
      _push(`</div><span class="text-sm font-bold text-slate-700 dark:text-slate-300">\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22</span></div></th><th class="px-4 py-4 text-center"><div class="flex items-center justify-center gap-2"><div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-800 rounded-lg flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:checkmark-circle-24-regular",
        class: "w-5 h-5 text-emerald-600 dark:text-emerald-400"
      }, null, _parent));
      _push(`</div><span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</span></div></th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
      ssrRenderList(__props.attendances, (attendance, index) => {
        _push(`<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"><td class="px-4 py-4 text-center"><span class="inline-flex items-center justify-center w-8 h-8 bg-violet-100 dark:bg-violet-800 text-violet-700 dark:text-violet-300 rounded-lg font-bold text-sm">${ssrInterpolate(index + 1)}</span></td><td class="px-4 py-4 text-center"><div class="flex flex-col items-center"><span class="font-semibold text-gray-900 dark:text-white">${ssrInterpolate(formatDate(attendance.date || attendance.start_at))}</span><span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${ssrInterpolate(formatDayOfWeek(attendance.date || attendance.start_at))}</span></div></td><td class="px-4 py-4 text-center"><div class="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:play-circle-24-filled",
          class: "w-4 h-4 text-cyan-600 dark:text-cyan-400"
        }, null, _parent));
        _push(`<span class="font-semibold text-cyan-700 dark:text-cyan-300">${ssrInterpolate(formatTime(attendance.start_at))}</span></div></td><td class="px-4 py-4 text-center"><div class="inline-flex items-center gap-2 px-3 py-1.5 bg-pink-50 dark:bg-pink-900/30 rounded-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:stop-24-filled",
          class: "w-4 h-4 text-pink-600 dark:text-pink-400"
        }, null, _parent));
        _push(`<span class="font-semibold text-pink-700 dark:text-pink-300">${ssrInterpolate(formatTime(attendance.finish_at))}</span></div></td><td class="px-4 py-4 text-center"><span class="text-gray-600 dark:text-gray-400">${ssrInterpolate(attendance.description || "-")}</span></td><td class="px-4 py-4 text-center">`);
        if (hasCheckedIn(attendance)) {
          _push(`<div class="${ssrRenderClass([getStatusConfig(attendance.status).bgClass, "inline-flex items-center gap-2 px-4 py-2 rounded-full"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getStatusConfig(attendance.status).icon,
            class: ["w-5 h-5", getStatusConfig(attendance.status).iconClass]
          }, null, _parent));
          _push(`<span class="${ssrRenderClass([getStatusConfig(attendance.status).textClass, "font-semibold"])}">${ssrInterpolate(getStatusConfig(attendance.status).label)}</span><span class="${ssrRenderClass([getStatusConfig(attendance.status).dotClass, "w-2 h-2 rounded-full animate-pulse"])}"></span></div>`);
        } else if (isAttendanceActive(attendance)) {
          _push(`<button${ssrIncludeBooleanAttr(checkingInId.value !== null) ? " disabled" : ""} class="${ssrRenderClass([wouldBeLate(attendance) ? "bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800/50" : "bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800/50", "inline-flex items-center gap-2 px-4 py-2 rounded-full font-semibold transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none"])}">`);
          if (checkingInId.value === attendance.id) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-filled",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: wouldBeLate(attendance) ? "heroicons:clock-20-solid" : "heroicons:hand-raised-20-solid",
              class: "w-5 h-5"
            }, null, _parent));
          }
          _push(`<span>${ssrInterpolate(checkingInId.value === attendance.id ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19\u0E15\u0E31\u0E27..." : wouldBeLate(attendance) ? "\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19\u0E15\u0E31\u0E27 (\u0E2A\u0E32\u0E22)" : "\u0E23\u0E32\u0E22\u0E07\u0E32\u0E19\u0E15\u0E31\u0E27")}</span></button>`);
        } else if (isAttendanceNotStarted(attendance)) {
          _push(`<div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-700">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-5 h-5 text-gray-500 dark:text-gray-400"
          }, null, _parent));
          _push(`<span class="font-semibold text-gray-500 dark:text-gray-400">\u0E23\u0E2D\u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21</span></div>`);
        } else {
          _push(`<div class="${ssrRenderClass([getStatusConfig(attendance.status).bgClass, "inline-flex items-center gap-2 px-4 py-2 rounded-full"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: getStatusConfig(attendance.status).icon,
            class: ["w-5 h-5", getStatusConfig(attendance.status).iconClass]
          }, null, _parent));
          _push(`<span class="${ssrRenderClass([getStatusConfig(attendance.status).textClass, "font-semibold"])}">${ssrInterpolate(getStatusConfig(attendance.status).label)}</span><span class="${ssrRenderClass([getStatusConfig(attendance.status).dotClass, "w-2 h-2 rounded-full animate-pulse"])}"></span></div>`);
        }
        _push(`</td></tr>`);
      });
      _push(`<!--]--></tbody></table></div>`);
      if (__props.attendances.length === 0 && !__props.loading) {
        _push(`<div class="py-12 text-center"><div class="w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-empty-24-regular",
          class: "w-12 h-12 text-gray-400 dark:text-gray-500"
        }, null, _parent));
        _push(`</div><h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><p class="text-gray-500 dark:text-gray-400"> \u0E23\u0E2D\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E43\u0E2B\u0E21\u0E48 </p></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.loading) {
        _push(`<div class="divide-y divide-gray-100 dark:divide-gray-700"><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="px-4 py-4 flex items-center gap-4 animate-pulse"><div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div><div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div><div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div><div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div><div class="w-24 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div><div class="w-20 h-8 bg-gray-200 dark:bg-gray-700 rounded-full"></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/StudentAttendanceTable.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "AttendancesList",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  setup(__props) {
    const props = __props;
    const api = useApi();
    const attendances = ref([]);
    const groups = ref([]);
    const selectedGroupId = ref(null);
    const selectedGroupMembers = ref([]);
    const courseMemberOfAuth = ref(null);
    const loading = ref(true);
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDetailsModal = ref(false);
    const editingAttendance = ref(null);
    const selectedAttendance = ref(null);
    ref([]);
    ref(false);
    const autoRefreshEnabled = ref(true);
    const autoRefreshInterval = ref(15);
    const refreshTimer = ref(null);
    const lastRefreshed = ref(null);
    const isAutoRefreshing = ref(false);
    const getDateTimeLocal = (date) => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    const toDateTimeLocalFormat = (dateTimeString) => {
      if (!dateTimeString) return "";
      const date = new Date(dateTimeString);
      if (isNaN(date.getTime())) return "";
      return getDateTimeLocal(date);
    };
    const addMinutesToDateTime = (dateTimeLocal, minutes = 45) => {
      if (!dateTimeLocal) return "";
      const date = new Date(dateTimeLocal);
      if (isNaN(date.getTime())) return "";
      date.setMinutes(date.getMinutes() + minutes);
      return getDateTimeLocal(date);
    };
    const now = /* @__PURE__ */ new Date();
    const endTime = new Date(now.getTime() + 45 * 6e4);
    const newAttendance = ref({
      title: "",
      date: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
      start_time: getDateTimeLocal(now),
      end_time: getDateTimeLocal(endTime),
      description: "",
      late_time: 15
    });
    watch(() => newAttendance.value.start_time, (newStartTime, oldStartTime) => {
      if (newStartTime && newStartTime !== oldStartTime) {
        newAttendance.value.end_time = addMinutesToDateTime(newStartTime, 45);
      }
    });
    const fetchAttendances = async (groupId, silent = false) => {
      var _a;
      if (!silent) {
        loading.value = true;
      }
      try {
        const params = new URLSearchParams();
        if (groupId) {
          params.append("group_id", groupId.toString());
        }
        const queryString = params.toString();
        const url = `/api/courses/${props.courseId}/attendances${queryString ? `?${queryString}` : ""}`;
        const response = await api.get(url);
        if (response.courseMemberOfAuth) {
          courseMemberOfAuth.value = response.courseMemberOfAuth;
        }
        if (response.groups && groups.value.length === 0) {
          groups.value = response.groups;
          if (props.isCourseAdmin && groups.value.length > 0 && !selectedGroupId.value) {
            const lastAccessedGroupId = (_a = courseMemberOfAuth.value) == null ? void 0 : _a.last_accessed_group_tab;
            if (lastAccessedGroupId && groups.value.some((g) => g.id === lastAccessedGroupId)) {
              selectedGroupId.value = lastAccessedGroupId;
            } else {
              selectedGroupId.value = groups.value[0].id;
            }
            const selectedGroup = groups.value.find((g) => g.id === selectedGroupId.value);
            if (selectedGroup == null ? void 0 : selectedGroup.members) {
              selectedGroupMembers.value = selectedGroup.members.sort((a, b) => (a.order_number || 0) - (b.order_number || 0));
            }
            return;
          }
        }
        if (groupId) {
          const selectedGroup = groups.value.find((g) => g.id === groupId);
          if (selectedGroup == null ? void 0 : selectedGroup.members) {
            selectedGroupMembers.value = selectedGroup.members.sort((a, b) => (a.order_number || 0) - (b.order_number || 0));
          }
        }
        attendances.value = response.data || response;
      } catch (error) {
        console.error("Error fetching attendances:", error);
        attendances.value = [];
      } finally {
        loading.value = false;
      }
    };
    const updateLastAccessGroupTab = async (groupId) => {
      if (!props.isCourseAdmin || !courseMemberOfAuth.value) return;
      try {
        await api.patch(`/api/courses/${props.courseId}/members/update-last-access-group`, {
          last_accessed_group_tab: groupId
        });
      } catch (error) {
        console.error("Error updating last access group tab:", error);
      }
    };
    const createAttendance = async () => {
      if (!selectedGroupId.value) {
        alert("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19");
        return;
      }
      try {
        const payload = {
          description: newAttendance.value.description,
          start_at: newAttendance.value.start_time,
          finish_at: newAttendance.value.end_time,
          late_time: newAttendance.value.late_time || 15
        };
        await api.post(`/api/courses/${props.courseId}/groups/${selectedGroupId.value}/attendances`, payload);
        showCreateModal.value = false;
        resetForm();
        await fetchAttendances(selectedGroupId.value);
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
          showConfirmButton: false,
          timer: 2e3,
          timerProgressBar: true,
          customClass: {
            popup: "colored-toast"
          }
        });
      } catch (error) {
        console.error("Error creating attendance:", error);
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E44\u0E14\u0E49",
          showConfirmButton: false,
          timer: 3e3,
          timerProgressBar: true
        });
      }
    };
    const updateAttendance = async () => {
      if (!editingAttendance.value) return;
      try {
        const payload = {
          description: editingAttendance.value.description,
          start_at: editingAttendance.value.start_at,
          finish_at: editingAttendance.value.finish_at,
          late_time: editingAttendance.value.late_time || 0
        };
        await api.patch(`/api/attendances/${editingAttendance.value.id}`, payload);
        showEditModal.value = false;
        await fetchAttendances(selectedGroupId.value);
      } catch (error) {
        console.error("Error updating attendance:", error);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D");
      }
    };
    const deleteAttendance = async (attendanceId) => {
      if (!confirm("\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?")) return;
      try {
        await api.delete(`/api/attendances/${attendanceId}`);
        fetchAttendances();
      } catch (error) {
        console.error("Error deleting attendance:", error);
      }
    };
    const viewDetails = (attendance) => {
      selectedAttendance.value = {
        ...attendance,
        start_at: toDateTimeLocalFormat(attendance.start_at),
        finish_at: toDateTimeLocalFormat(attendance.finish_at)
      };
      showDetailsModal.value = true;
    };
    const openEditModal = (attendance) => {
      editingAttendance.value = {
        ...attendance,
        start_at: toDateTimeLocalFormat(attendance.start_at),
        finish_at: toDateTimeLocalFormat(attendance.finish_at)
      };
      showEditModal.value = true;
    };
    watch(() => {
      var _a;
      return (_a = selectedAttendance.value) == null ? void 0 : _a.start_at;
    }, (newStartAt, oldStartAt) => {
      if (newStartAt && newStartAt !== oldStartAt && selectedAttendance.value) {
        selectedAttendance.value.finish_at = addMinutesToDateTime(newStartAt, 45);
      }
    });
    watch(() => {
      var _a;
      return (_a = editingAttendance.value) == null ? void 0 : _a.start_at;
    }, (newStartAt, oldStartAt) => {
      if (newStartAt && newStartAt !== oldStartAt && editingAttendance.value) {
        editingAttendance.value.finish_at = addMinutesToDateTime(newStartAt, 45);
      }
    });
    const saveAttendanceChanges = async () => {
      if (!selectedAttendance.value) return;
      try {
        const payload = {
          description: selectedAttendance.value.description,
          start_at: selectedAttendance.value.start_at,
          finish_at: selectedAttendance.value.finish_at,
          late_time: selectedAttendance.value.late_time || 0
        };
        await api.patch(`/api/attendances/${selectedAttendance.value.id}`, payload);
        showDetailsModal.value = false;
        await fetchAttendances(selectedGroupId.value);
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
          showConfirmButton: false,
          timer: 2e3,
          timerProgressBar: true,
          customClass: {
            popup: "colored-toast"
          }
        });
      } catch (error) {
        console.error("Error updating attendance:", error);
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E44\u0E14\u0E49",
          showConfirmButton: false,
          timer: 3e3,
          timerProgressBar: true
        });
      }
    };
    const confirmDeleteAttendance = async () => {
      if (!selectedAttendance.value) return;
      const result = await Swal.fire({
        title: "\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D?",
        html: `<p class="text-gray-600">\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D<br/><strong>${selectedAttendance.value.description || "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48 " + new Date(selectedAttendance.value.date).toLocaleDateString("th-TH")}</strong></p><p class="text-red-600 text-sm mt-2">\u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E19\u0E49\u0E35\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E49\u0E2D\u0E19\u0E01\u0E25\u0E31\u0E1A\u0E44\u0E14\u0E49</p>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "\u0E25\u0E1A",
        cancelButtonText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
        reverseButtons: true,
        customClass: {
          popup: "rounded-xl",
          confirmButton: "px-5 py-2 rounded-lg font-medium",
          cancelButton: "px-5 py-2 rounded-lg font-medium"
        }
      });
      if (!result.isConfirmed) return;
      try {
        await api.delete(`/api/attendances/${selectedAttendance.value.id}`);
        showDetailsModal.value = false;
        await fetchAttendances(selectedGroupId.value);
      } catch (error) {
        console.error("Error deleting attendance:", error);
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E44\u0E14\u0E49",
          showConfirmButton: false,
          timer: 3e3,
          timerProgressBar: true
        });
      }
    };
    const resetForm = () => {
      const now2 = /* @__PURE__ */ new Date();
      const endTime2 = new Date(now2.getTime() + 45 * 6e4);
      newAttendance.value = {
        title: "",
        date: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
        start_time: getDateTimeLocal(now2),
        end_time: getDateTimeLocal(endTime2),
        description: "",
        late_time: 15
      };
    };
    const handleUpdateMemberStatus = async ({ attendanceId, memberId, status }) => {
      var _a;
      const attendance = attendances.value.find((a) => a.id === attendanceId);
      if (!attendance) return;
      const detail = (_a = attendance.details) == null ? void 0 : _a.find((d) => d.course_member_id === memberId);
      const oldStatus = detail ? detail.status : null;
      if (detail) {
        detail.status = status;
      } else if (attendance.details) {
        attendance.details.push({
          course_member_id: memberId,
          status
        });
      }
      try {
        const apiStatus = status === null ? 0 : status;
        await api.post(`/api/attendances/${attendanceId}/member/${memberId}/update-status`, {
          status: apiStatus
        });
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true
        });
      } catch (error) {
        if (detail && oldStatus !== null) {
          detail.status = oldStatus;
        } else if (detail && oldStatus === null) {
          const index = attendance.details.indexOf(detail);
          if (index > -1) {
            attendance.details.splice(index, 1);
          }
        }
        console.error("Error updating member status:", error);
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E44\u0E14\u0E49",
          showConfirmButton: false,
          timer: 2e3
        });
      }
    };
    const formatDate = (date) => {
      if (!date) return "";
      return new Date(date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    };
    const filteredAttendances = computed(() => {
      if (!props.isCourseAdmin || !selectedGroupId.value) {
        return attendances.value;
      }
      return attendances.value.filter((attendance) => {
        return attendance.group_id === selectedGroupId.value;
      });
    });
    const studentAttendances = computed(() => {
      if (props.isCourseAdmin || !courseMemberOfAuth.value) {
        return [];
      }
      const studentGroupId = courseMemberOfAuth.value.group_id;
      return attendances.value.filter((attendance) => {
        return !studentGroupId || attendance.group_id === studentGroupId;
      }).map((attendance) => {
        var _a, _b;
        const memberDetail = (_a = attendance.details) == null ? void 0 : _a.find(
          (d) => {
            var _a2;
            return d.course_member_id === ((_a2 = courseMemberOfAuth.value) == null ? void 0 : _a2.id);
          }
        );
        return {
          id: attendance.id,
          date: attendance.date || attendance.start_at,
          start_at: attendance.start_at,
          finish_at: attendance.finish_at,
          late_time: attendance.late_time || 15,
          // Default 15 minutes
          description: attendance.description,
          status: (_b = memberDetail == null ? void 0 : memberDetail.status) != null ? _b : null,
          // null = ขาด
          time_in: memberDetail == null ? void 0 : memberDetail.time_in
        };
      }).sort((a, b) => {
        return new Date(b.date).getTime() - new Date(a.date).getTime();
      });
    });
    watch(selectedGroupId, (newGroupId, oldGroupId) => {
      if (newGroupId && props.isCourseAdmin) {
        if (oldGroupId !== void 0 && oldGroupId !== null) {
          updateLastAccessGroupTab(newGroupId);
        }
        fetchAttendances(newGroupId);
      }
    });
    const startAutoRefresh = () => {
      if (refreshTimer.value) {
        clearInterval(refreshTimer.value);
      }
      if (autoRefreshEnabled.value && props.isCourseAdmin) {
        refreshTimer.value = setInterval(async () => {
          if (!loading.value && !showCreateModal.value && !showEditModal.value && !showDetailsModal.value) {
            isAutoRefreshing.value = true;
            await fetchAttendances(selectedGroupId.value, true);
            lastRefreshed.value = /* @__PURE__ */ new Date();
            isAutoRefreshing.value = false;
          }
        }, autoRefreshInterval.value * 1e3);
      }
    };
    const stopAutoRefresh = () => {
      if (refreshTimer.value) {
        clearInterval(refreshTimer.value);
        refreshTimer.value = null;
      }
    };
    const formatLastRefreshed = computed(() => {
      if (!lastRefreshed.value) return "";
      const now2 = /* @__PURE__ */ new Date();
      const diff = Math.floor((now2.getTime() - lastRefreshed.value.getTime()) / 1e3);
      if (diff < 60) return `${diff} \u0E27\u0E34\u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diff < 3600) return `${Math.floor(diff / 60)} \u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      return lastRefreshed.value.toLocaleTimeString("th-TH");
    });
    watch(autoRefreshEnabled, (enabled) => {
      if (enabled) {
        startAutoRefresh();
      } else {
        stopAutoRefresh();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_DialogModal = _sfc_main$5;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))} data-v-3cbbea61>`);
      if (__props.isCourseAdmin && unref(groups).length > 0) {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4" data-v-3cbbea61><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3" data-v-3cbbea61>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:people-team-24-regular",
          class: "w-5 h-5 inline-block mr-2"
        }, null, _parent));
        _push(` \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19 </label><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3" data-v-3cbbea61><!--[-->`);
        ssrRenderList(unref(groups), (group) => {
          var _a;
          _push(`<button class="${ssrRenderClass([
            "relative p-4 rounded-xl border-2 transition-all duration-300 text-left",
            unref(selectedGroupId) === group.id ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md scale-105" : "border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-sm"
          ])}" data-v-3cbbea61><div class="flex items-start justify-between gap-2" data-v-3cbbea61><div class="flex-1 min-w-0" data-v-3cbbea61><div class="flex items-center gap-2 mb-1" data-v-3cbbea61><div class="${ssrRenderClass([
            "w-3 h-3 rounded-full transition-all",
            unref(selectedGroupId) === group.id ? "bg-blue-500 animate-pulse" : "bg-gray-400"
          ])}" data-v-3cbbea61></div><h4 class="font-semibold text-gray-900 dark:text-white truncate" data-v-3cbbea61>${ssrInterpolate(group.name || `\u0E01\u0E25\u0E38\u0E48\u0E21 ${group.id}`)}</h4></div><p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1" data-v-3cbbea61>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-20-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span data-v-3cbbea61>${ssrInterpolate(((_a = group.members) == null ? void 0 : _a.length) || group.member_count || 0)} \u0E04\u0E19</span></p></div>`);
          if (unref(selectedGroupId) === group.id) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:checkmark-circle-24-filled",
              class: "w-6 h-6 text-blue-500 flex-shrink-0"
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</div></button>`);
        });
        _push(`<!--]--></div>`);
        if (unref(loading)) {
          _push(`<div class="mt-3 flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400" data-v-3cbbea61><div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin" data-v-3cbbea61></div><span data-v-3cbbea61>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D...</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.isCourseAdmin && unref(groups).length > 0) {
        _push(`<div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-sm p-4 border border-blue-200 dark:border-gray-600" data-v-3cbbea61><div class="flex flex-wrap items-center justify-between gap-4" data-v-3cbbea61><div class="flex items-center gap-3" data-v-3cbbea61><div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center" data-v-3cbbea61>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-sync-24-filled",
          class: ["w-5 h-5 text-white", { "animate-spin": unref(isAutoRefreshing) }]
        }, null, _parent));
        _push(`</div><div data-v-3cbbea61><h3 class="font-semibold text-gray-900 dark:text-white" data-v-3cbbea61>\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34</h3><p class="text-xs text-gray-500 dark:text-gray-400" data-v-3cbbea61>`);
        if (unref(autoRefreshEnabled)) {
          _push(`<!--[--> \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A\u0E17\u0E38\u0E01 ${ssrInterpolate(unref(autoRefreshInterval))} \u0E27\u0E34\u0E19\u0E32\u0E17\u0E35 `);
          if (unref(lastRefreshed)) {
            _push(`<span class="ml-2" data-v-3cbbea61>\u2022 \u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14: ${ssrInterpolate(unref(formatLastRefreshed))}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--]-->`);
        } else {
          _push(`<!--[-->\u0E1B\u0E34\u0E14\u0E2D\u0E22\u0E39\u0E48 - \u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21\u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25<!--]-->`);
        }
        _push(`</p></div></div><div class="flex items-center gap-3" data-v-3cbbea61><button${ssrIncludeBooleanAttr(unref(isAutoRefreshing)) ? " disabled" : ""} class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-lg border border-blue-300 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-600 transition-all disabled:opacity-50" data-v-3cbbea61>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-clockwise-24-regular",
          class: ["w-5 h-5", { "animate-spin": unref(isAutoRefreshing) }]
        }, null, _parent));
        _push(`<span class="hidden sm:inline" data-v-3cbbea61>\u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A</span></button><button class="${ssrRenderClass([
          "relative inline-flex h-8 w-14 items-center rounded-full transition-colors duration-300",
          unref(autoRefreshEnabled) ? "bg-blue-600" : "bg-gray-300 dark:bg-gray-600"
        ])}" data-v-3cbbea61><span class="${ssrRenderClass([
          "inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition-transform duration-300",
          unref(autoRefreshEnabled) ? "translate-x-7" : "translate-x-1"
        ])}" data-v-3cbbea61></span></button><span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-3cbbea61>${ssrInterpolate(unref(autoRefreshEnabled) ? "\u0E40\u0E1B\u0E34\u0E14" : "\u0E1B\u0E34\u0E14")}</span></div></div>`);
        if (unref(autoRefreshEnabled)) {
          _push(`<div class="mt-3 flex items-center gap-2 text-sm text-green-600 dark:text-green-400" data-v-3cbbea61><span class="relative flex h-3 w-3" data-v-3cbbea61><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" data-v-3cbbea61></span><span class="relative inline-flex rounded-full h-3 w-3 bg-green-500" data-v-3cbbea61></span></span><span data-v-3cbbea61>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E2A\u0E16\u0E32\u0E19\u0E30\u0E01\u0E32\u0E23\u0E21\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E1A\u0E1A Live</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (unref(loading)) {
        _push(ssrRenderComponent(ContentLoader, null, null, _parent));
      } else if (__props.isCourseAdmin && unref(filteredAttendances).length > 0 && unref(selectedGroupMembers).length > 0) {
        _push(ssrRenderComponent(_sfc_main$3, {
          attendances: unref(filteredAttendances),
          "group-members": unref(selectedGroupMembers),
          "is-course-admin": __props.isCourseAdmin,
          onCreate: ($event) => showCreateModal.value = true,
          onViewDetails: viewDetails,
          onEdit: openEditModal,
          onDelete: deleteAttendance,
          onUpdateStatus: handleUpdateMemberStatus
        }, null, _parent));
      } else if (!__props.isCourseAdmin && unref(studentAttendances).length > 0) {
        _push(ssrRenderComponent(_sfc_main$2, {
          attendances: unref(studentAttendances),
          loading: unref(loading),
          onReload: ($event) => fetchAttendances()
        }, null, _parent));
      } else {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-12 text-center" data-v-3cbbea61><div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-3xl flex items-center justify-center" data-v-3cbbea61>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-empty-24-regular",
          class: "w-20 h-20 text-blue-500 dark:text-blue-400"
        }, null, _parent));
        _push(`</div><h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2" data-v-3cbbea61> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D </h3><p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto" data-v-3cbbea61>${ssrInterpolate(__props.isCourseAdmin ? "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19" : "\u0E23\u0E2D\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D \u0E04\u0E38\u0E13\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E21\u0E35\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E43\u0E2B\u0E21\u0E48")}</p>`);
        if (__props.isCourseAdmin) {
          _push(`<button class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl hover:scale-105" data-v-3cbbea61>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:add-circle-24-filled",
            class: "w-6 h-6"
          }, null, _parent));
          _push(`<span data-v-3cbbea61>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E23\u0E01</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(ssrRenderComponent(_component_DialogModal, {
        show: unref(showCreateModal),
        onClose: ($event) => showCreateModal.value = false
      }, {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D`);
          } else {
            return [
              createTextVNode("\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D")
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b;
          if (_push2) {
            _push2(`<div class="space-y-4" data-v-3cbbea61${_scopeId}>`);
            if (unref(selectedGroupId)) {
              _push2(`<div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg" data-v-3cbbea61${_scopeId}><div class="flex items-center gap-3" data-v-3cbbea61${_scopeId}><div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:people-team-24-filled",
                class: "w-6 h-6 text-white"
              }, null, _parent2, _scopeId));
              _push2(`</div><div data-v-3cbbea61${_scopeId}><p class="text-xs text-gray-500 dark:text-gray-400" data-v-3cbbea61${_scopeId}>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A</p><p class="font-semibold text-gray-900 dark:text-white" data-v-3cbbea61${_scopeId}>${ssrInterpolate(((_a = unref(groups).find((g) => g.id === unref(selectedGroupId))) == null ? void 0 : _a.name) || `\u0E01\u0E25\u0E38\u0E48\u0E21 ${unref(selectedGroupId)}`)}</p></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:text-24-regular",
              class: "inline-block w-4 h-4 mr-1"
            }, null, _parent2, _scopeId));
            _push2(` \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D/\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 </label><input${ssrRenderAttr("value", unref(newAttendance).description)} type="text" placeholder="\u0E04\u0E23\u0E31\u0E49\u0E07\u0E17\u0E35\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E17\u0E35\u0E48" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white" data-v-3cbbea61${_scopeId}></div><div class="grid grid-cols-2 gap-4" data-v-3cbbea61${_scopeId}><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:calendar-24-regular",
              class: "inline-block w-4 h-4 mr-1"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 </label><input${ssrRenderAttr("value", unref(newAttendance).start_time)} type="datetime-local" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white" data-v-3cbbea61${_scopeId}></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:calendar-checkmark-24-regular",
              class: "inline-block w-4 h-4 mr-1"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 </label><input${ssrRenderAttr("value", unref(newAttendance).end_time)} type="datetime-local" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white" data-v-3cbbea61${_scopeId}></div></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:clock-24-regular",
              class: "inline-block w-4 h-4 mr-1"
            }, null, _parent2, _scopeId));
            _push2(` \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E32\u0E22 (\u0E19\u0E32\u0E17\u0E35) </label><input${ssrRenderAttr("value", unref(newAttendance).late_time)} type="number" min="0" placeholder="15" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white" data-v-3cbbea61${_scopeId}></div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4" }, [
                unref(selectedGroupId) ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"
                }, [
                  createVNode("div", { class: "flex items-center gap-3" }, [
                    createVNode("div", { class: "w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:people-team-24-filled",
                        class: "w-6 h-6 text-white"
                      })
                    ]),
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A"),
                      createVNode("p", { class: "font-semibold text-gray-900 dark:text-white" }, toDisplayString(((_b = unref(groups).find((g) => g.id === unref(selectedGroupId))) == null ? void 0 : _b.name) || `\u0E01\u0E25\u0E38\u0E48\u0E21 ${unref(selectedGroupId)}`), 1)
                    ])
                  ])
                ])) : createCommentVNode("", true),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:text-24-regular",
                      class: "inline-block w-4 h-4 mr-1"
                    }),
                    createTextVNode(" \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D/\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 ")
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(newAttendance).description = $event,
                    type: "text",
                    placeholder: "\u0E04\u0E23\u0E31\u0E49\u0E07\u0E17\u0E35\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E17\u0E35\u0E48",
                    class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(newAttendance).description]
                  ])
                ]),
                createVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:calendar-24-regular",
                        class: "inline-block w-4 h-4 mr-1"
                      }),
                      createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 ")
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(newAttendance).start_time = $event,
                      type: "datetime-local",
                      class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(newAttendance).start_time]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:calendar-checkmark-24-regular",
                        class: "inline-block w-4 h-4 mr-1"
                      }),
                      createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 ")
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(newAttendance).end_time = $event,
                      type: "datetime-local",
                      class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(newAttendance).end_time]
                    ])
                  ])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:clock-24-regular",
                      class: "inline-block w-4 h-4 mr-1"
                    }),
                    createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E32\u0E22 (\u0E19\u0E32\u0E17\u0E35) ")
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(newAttendance).late_time = $event,
                    type: "number",
                    min: "0",
                    placeholder: "15",
                    class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [
                      vModelText,
                      unref(newAttendance).late_time,
                      void 0,
                      { number: true }
                    ]
                  ])
                ])
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<button class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" data-v-3cbbea61${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" data-v-3cbbea61${_scopeId}> \u0E2A\u0E23\u0E49\u0E32\u0E07 </button>`);
          } else {
            return [
              createVNode("button", {
                onClick: ($event) => showCreateModal.value = false,
                class: "px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
              }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
              createVNode("button", {
                onClick: createAttendance,
                class: "px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              }, " \u0E2A\u0E23\u0E49\u0E32\u0E07 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_DialogModal, {
        show: unref(showEditModal),
        onClose: ($event) => showEditModal.value = false
      }, {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center gap-3" data-v-3cbbea61${_scopeId}><div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-24-filled",
              class: "w-6 h-6 text-blue-600 dark:text-blue-400"
            }, null, _parent2, _scopeId));
            _push2(`</div><div data-v-3cbbea61${_scopeId}><h3 class="text-lg font-bold" data-v-3cbbea61${_scopeId}>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-3cbbea61${_scopeId}>\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D</p></div></div>`);
          } else {
            return [
              createVNode("div", { class: "flex items-center gap-3" }, [
                createVNode("div", { class: "w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center" }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-filled",
                    class: "w-6 h-6 text-blue-600 dark:text-blue-400"
                  })
                ]),
                createVNode("div", null, [
                  createVNode("h3", { class: "text-lg font-bold" }, "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D"),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D")
                ])
              ])
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (unref(editingAttendance)) {
              _push2(`<div class="space-y-4" data-v-3cbbea61${_scopeId}><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:text-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D/\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 </label><input${ssrRenderAttr("value", unref(editingAttendance).description)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E1A\u0E17\u0E17\u0E35\u0E48 1, \u0E2A\u0E2D\u0E1A\u0E01\u0E25\u0E32\u0E07\u0E20\u0E32\u0E04" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" data-v-3cbbea61${_scopeId}></div><div class="grid grid-cols-2 gap-4" data-v-3cbbea61${_scopeId}><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 </label><input${ssrRenderAttr("value", unref(editingAttendance).start_at)} type="datetime-local" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" data-v-3cbbea61${_scopeId}></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-checkmark-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 </label><input${ssrRenderAttr("value", unref(editingAttendance).finish_at)} type="datetime-local" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" data-v-3cbbea61${_scopeId}></div></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:clock-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E32\u0E22 (\u0E19\u0E32\u0E17\u0E35) </label><input${ssrRenderAttr("value", unref(editingAttendance).late_time)} type="number" min="0" placeholder="\u0E08\u0E33\u0E19\u0E27\u0E19\u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E16\u0E37\u0E2D\u0E27\u0E48\u0E32\u0E2A\u0E32\u0E22" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" data-v-3cbbea61${_scopeId}></div></div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              unref(editingAttendance) ? (openBlock(), createBlock("div", {
                key: 0,
                class: "space-y-4"
              }, [
                createVNode("div", null, [
                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:text-24-regular",
                      class: "inline-block w-4 h-4 mr-1"
                    }),
                    createTextVNode(" \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D/\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 ")
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(editingAttendance).description = $event,
                    type: "text",
                    placeholder: "\u0E40\u0E0A\u0E48\u0E19 \u0E1A\u0E17\u0E17\u0E35\u0E48 1, \u0E2A\u0E2D\u0E1A\u0E01\u0E25\u0E32\u0E07\u0E20\u0E32\u0E04",
                    class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(editingAttendance).description]
                  ])
                ]),
                createVNode("div", { class: "grid grid-cols-2 gap-4" }, [
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:calendar-24-regular",
                        class: "inline-block w-4 h-4 mr-1"
                      }),
                      createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 ")
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(editingAttendance).start_at = $event,
                      type: "datetime-local",
                      class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(editingAttendance).start_at]
                    ])
                  ]),
                  createVNode("div", null, [
                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                      createVNode(unref(Icon), {
                        icon: "fluent:calendar-checkmark-24-regular",
                        class: "inline-block w-4 h-4 mr-1"
                      }),
                      createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 ")
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(editingAttendance).finish_at = $event,
                      type: "datetime-local",
                      class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(editingAttendance).finish_at]
                    ])
                  ])
                ]),
                createVNode("div", null, [
                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:clock-24-regular",
                      class: "inline-block w-4 h-4 mr-1"
                    }),
                    createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E32\u0E22 (\u0E19\u0E32\u0E17\u0E35) ")
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(editingAttendance).late_time = $event,
                    type: "number",
                    min: "0",
                    placeholder: "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E16\u0E37\u0E2D\u0E27\u0E48\u0E32\u0E2A\u0E32\u0E22",
                    class: "w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [
                      vModelText,
                      unref(editingAttendance).late_time,
                      void 0,
                      { number: true }
                    ]
                  ])
                ])
              ])) : createCommentVNode("", true)
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<button class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" data-v-3cbbea61${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:save-24-regular",
              class: "inline-block w-4 h-4 mr-1"
            }, null, _parent2, _scopeId));
            _push2(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07 </button>`);
          } else {
            return [
              createVNode("button", {
                onClick: ($event) => showEditModal.value = false,
                class: "px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
              createVNode("button", {
                onClick: updateAttendance,
                class: "px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              }, [
                createVNode(unref(Icon), {
                  icon: "fluent:save-24-regular",
                  class: "inline-block w-4 h-4 mr-1"
                }),
                createTextVNode(" \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07 ")
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_DialogModal, {
        show: unref(showDetailsModal),
        onClose: ($event) => showDetailsModal.value = false,
        "max-width": "2xl"
      }, {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d;
          if (_push2) {
            _push2(`<div class="flex items-center justify-between" data-v-3cbbea61${_scopeId}><div class="flex items-center gap-3" data-v-3cbbea61${_scopeId}><div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:calendar-checkmark-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><div data-v-3cbbea61${_scopeId}><h3 class="text-lg font-bold text-gray-900 dark:text-white" data-v-3cbbea61${_scopeId}>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D</h3><p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" data-v-3cbbea61${_scopeId}>${ssrInterpolate(formatDate(((_a = unref(selectedAttendance)) == null ? void 0 : _a.date) || ((_b = unref(selectedAttendance)) == null ? void 0 : _b.start_at)))}</p></div></div><button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" title="\u0E1B\u0E34\u0E14" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-5 h-5 text-gray-500 dark:text-gray-400"
            }, null, _parent2, _scopeId));
            _push2(`</button></div>`);
          } else {
            return [
              createVNode("div", { class: "flex items-center justify-between" }, [
                createVNode("div", { class: "flex items-center gap-3" }, [
                  createVNode("div", { class: "w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:calendar-checkmark-24-filled",
                      class: "w-6 h-6 text-white"
                    })
                  ]),
                  createVNode("div", null, [
                    createVNode("h3", { class: "text-lg font-bold text-gray-900 dark:text-white" }, "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D"),
                    createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 mt-0.5" }, toDisplayString(formatDate(((_c = unref(selectedAttendance)) == null ? void 0 : _c.date) || ((_d = unref(selectedAttendance)) == null ? void 0 : _d.start_at))), 1)
                  ])
                ]),
                createVNode("button", {
                  onClick: ($event) => showDetailsModal.value = false,
                  class: "p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors",
                  title: "\u0E1B\u0E34\u0E14"
                }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:dismiss-24-regular",
                    class: "w-5 h-5 text-gray-500 dark:text-gray-400"
                  })
                ], 8, ["onClick"])
              ])
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4" data-v-3cbbea61${_scopeId}>`);
            if (unref(selectedAttendance)) {
              _push2(`<div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-blue-200 dark:border-gray-600" data-v-3cbbea61${_scopeId}><div class="grid grid-cols-1 md:grid-cols-2 gap-3" data-v-3cbbea61${_scopeId}><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:text-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D/\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 </label><input${ssrRenderAttr("value", unref(selectedAttendance).description)}${ssrIncludeBooleanAttr(!__props.isCourseAdmin) ? " disabled" : ""} type="text" placeholder="\u0E04\u0E23\u0E31\u0E49\u0E07\u0E17\u0E35\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E17\u0E35\u0E48" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed" data-v-3cbbea61${_scopeId}></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:clock-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E32\u0E22 (\u0E19\u0E32\u0E17\u0E35) </label><input${ssrRenderAttr("value", unref(selectedAttendance).late_time)}${ssrIncludeBooleanAttr(!__props.isCourseAdmin) ? " disabled" : ""} type="number" min="0" placeholder="\u0E08\u0E33\u0E19\u0E27\u0E19\u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E16\u0E37\u0E2D\u0E27\u0E48\u0E32\u0E2A\u0E32\u0E22" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed" data-v-3cbbea61${_scopeId}></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 </label><input${ssrRenderAttr("value", unref(selectedAttendance).start_at)}${ssrIncludeBooleanAttr(!__props.isCourseAdmin) ? " disabled" : ""} type="datetime-local" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed" data-v-3cbbea61${_scopeId}></div><div data-v-3cbbea61${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:calendar-checkmark-24-regular",
                class: "inline-block w-4 h-4 mr-1"
              }, null, _parent2, _scopeId));
              _push2(` \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 </label><input${ssrRenderAttr("value", unref(selectedAttendance).finish_at)}${ssrIncludeBooleanAttr(!__props.isCourseAdmin) ? " disabled" : ""} type="datetime-local" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed" data-v-3cbbea61${_scopeId}></div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4" }, [
                unref(selectedAttendance) ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-blue-200 dark:border-gray-600"
                }, [
                  createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-3" }, [
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:text-24-regular",
                          class: "inline-block w-4 h-4 mr-1"
                        }),
                        createTextVNode(" \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D/\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 ")
                      ]),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(selectedAttendance).description = $event,
                        disabled: !__props.isCourseAdmin,
                        type: "text",
                        placeholder: "\u0E04\u0E23\u0E31\u0E49\u0E07\u0E17\u0E35\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E17\u0E35\u0E48",
                        class: "w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                      }, null, 8, ["onUpdate:modelValue", "disabled"]), [
                        [vModelText, unref(selectedAttendance).description]
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:clock-24-regular",
                          class: "inline-block w-4 h-4 mr-1"
                        }),
                        createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E32\u0E22 (\u0E19\u0E32\u0E17\u0E35) ")
                      ]),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(selectedAttendance).late_time = $event,
                        disabled: !__props.isCourseAdmin,
                        type: "number",
                        min: "0",
                        placeholder: "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E16\u0E37\u0E2D\u0E27\u0E48\u0E32\u0E2A\u0E32\u0E22",
                        class: "w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                      }, null, 8, ["onUpdate:modelValue", "disabled"]), [
                        [
                          vModelText,
                          unref(selectedAttendance).late_time,
                          void 0,
                          { number: true }
                        ]
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:calendar-24-regular",
                          class: "inline-block w-4 h-4 mr-1"
                        }),
                        createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 ")
                      ]),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(selectedAttendance).start_at = $event,
                        disabled: !__props.isCourseAdmin,
                        type: "datetime-local",
                        class: "w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                      }, null, 8, ["onUpdate:modelValue", "disabled"]), [
                        [vModelText, unref(selectedAttendance).start_at]
                      ])
                    ]),
                    createVNode("div", null, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" }, [
                        createVNode(unref(Icon), {
                          icon: "fluent:calendar-checkmark-24-regular",
                          class: "inline-block w-4 h-4 mr-1"
                        }),
                        createTextVNode(" \u0E40\u0E27\u0E25\u0E32\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14 ")
                      ]),
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(selectedAttendance).finish_at = $event,
                        disabled: !__props.isCourseAdmin,
                        type: "datetime-local",
                        class: "w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                      }, null, 8, ["onUpdate:modelValue", "disabled"]), [
                        [vModelText, unref(selectedAttendance).finish_at]
                      ])
                    ])
                  ])
                ])) : createCommentVNode("", true)
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center justify-between w-full" data-v-3cbbea61${_scopeId}><button class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium" data-v-3cbbea61${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-5 h-5"
            }, null, _parent2, _scopeId));
            _push2(`<span data-v-3cbbea61${_scopeId}>\u0E1B\u0E34\u0E14</span></button>`);
            if (__props.isCourseAdmin) {
              _push2(`<div class="flex items-center gap-3" data-v-3cbbea61${_scopeId}><button class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium shadow-lg hover:shadow-xl" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:delete-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-3cbbea61${_scopeId}>\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D</span></button><button class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all font-medium shadow-lg hover:shadow-xl" data-v-3cbbea61${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:save-24-filled",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-3cbbea61${_scopeId}>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07</span></button></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "flex items-center justify-between w-full" }, [
                createVNode("button", {
                  onClick: ($event) => showDetailsModal.value = false,
                  class: "inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
                }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:dismiss-24-regular",
                    class: "w-5 h-5"
                  }),
                  createVNode("span", null, "\u0E1B\u0E34\u0E14")
                ], 8, ["onClick"]),
                __props.isCourseAdmin ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex items-center gap-3"
                }, [
                  createVNode("button", {
                    onClick: confirmDeleteAttendance,
                    class: "inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium shadow-lg hover:shadow-xl"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:delete-24-regular",
                      class: "w-5 h-5"
                    }),
                    createVNode("span", null, "\u0E25\u0E1A\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D")
                  ]),
                  createVNode("button", {
                    onClick: saveAttendanceChanges,
                    class: "inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all font-medium shadow-lg hover:shadow-xl"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:save-24-filled",
                      class: "w-5 h-5"
                    }),
                    createVNode("span", null, "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07")
                  ])
                ])) : createCommentVNode("", true)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AttendancesList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AttendancesList = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-3cbbea61"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "attendances",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if ((_a = unref(course)) == null ? void 0 : _a.id) {
        _push(ssrRenderComponent(AttendancesList, {
          "course-id": unref(course).id,
          "is-course-admin": unref(isCourseAdmin)
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/attendances.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=attendances-orEnfGrT.mjs.map
