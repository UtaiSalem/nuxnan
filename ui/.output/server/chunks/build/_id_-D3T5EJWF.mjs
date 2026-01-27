import { p as useRoute, i as useApi, s as useCourseStore, f as useHead, l as __nuxt_component_0$1, q as __nuxt_component_0, _ as _export_sfc, n as navigateTo, b as useRuntimeConfig } from './server.mjs';
import { defineComponent, ref, computed, provide, watch, mergeProps, withCtx, unref, createBlock, createCommentVNode, openBlock, createVNode, toDisplayString, Fragment, createTextVNode, withDirectives, vModelText, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderStyle, ssrIncludeBooleanAttr, ssrRenderAttr, ssrRenderList, ssrRenderClass, ssrRenderAttrs } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { u as useCourseMemberStore } from './courseMember-jssI8x6K.mjs';
import { _ as __nuxt_component_0$2 } from './nuxt-link-Dhr1c_cd.mjs';
import { _ as _sfc_main$3 } from './DialogModal-CfsI63S_.mjs';
import { u as useCourseGroupStore } from './courseGroup-9VJQb76E.mjs';
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
import './Modal-DYe4d1RC.mjs';

const _sfc_main$2 = {
  __name: "CourseProfileCover",
  __ssrInlineRender: true,
  props: {
    courseMemberOfAuth: { type: Object, default: null }
  },
  emits: [
    "request-member",
    "request-unmember",
    "refresh"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const api = useApi();
    const config = useRuntimeConfig();
    const courseStore = useCourseStore();
    const course = computed(() => courseStore.currentCourse);
    const academy = computed(() => courseStore.academy);
    const isAdmin = computed(() => courseStore.isCourseAdmin);
    const courseGroups = computed(() => {
      var _a;
      return ((_a = course.value) == null ? void 0 : _a.groups) || [];
    });
    const courseId = computed(() => {
      var _a;
      return (_a = course.value) == null ? void 0 : _a.id;
    });
    const courseName = computed(() => {
      var _a;
      return ((_a = course.value) == null ? void 0 : _a.name) || "";
    });
    const courseCode = computed(() => {
      var _a;
      return ((_a = course.value) == null ? void 0 : _a.code) || "";
    });
    const tuitionFees = computed(() => {
      var _a;
      return (_a = course.value) == null ? void 0 : _a.tuition_fees;
    });
    const lessonsCount = computed(() => {
      var _a, _b;
      return (_b = (_a = course.value) == null ? void 0 : _a.lessons_count) != null ? _b : 0;
    });
    const enrolledStudents = computed(() => {
      var _a, _b;
      return (_b = (_a = course.value) == null ? void 0 : _a.enrolled_students) != null ? _b : 0;
    });
    const groupsCount = computed(() => {
      var _a, _b;
      return (_b = (_a = course.value) == null ? void 0 : _a.groups) != null ? _b : 0;
    });
    const memberStatus = computed(() => {
      var _a, _b;
      return ((_a = props.courseMemberOfAuth) == null ? void 0 : _a.status) || ((_b = course.value) == null ? void 0 : _b.member_status);
    });
    const hasGroups = computed(() => {
      var _a, _b, _c;
      return ((_c = (_b = (_a = course.value) == null ? void 0 : _a.groups) == null ? void 0 : _b.length) != null ? _c : 0) > 0;
    });
    ref(null);
    ref(null);
    ref(null);
    ref(null);
    const showOptionGroups = ref(false);
    const showAcceptMemberOption = ref(false);
    const showEditModal = ref(false);
    const tempName = ref("");
    const tempCode = ref("");
    const isUpdatingCover = ref(false);
    const isUpdatingLogo = ref(false);
    const isUpdatingName = ref(false);
    const isUpdatingCode = ref(false);
    const isRequestingMember = ref(false);
    const isRequestingUnmember = ref(false);
    const coverPreview = ref(null);
    const logoPreview = ref(null);
    const coverUrl = computed(() => {
      var _a;
      if (coverPreview.value) return coverPreview.value;
      if ((_a = course.value) == null ? void 0 : _a.cover) {
        if (course.value.cover.startsWith("http")) {
          return course.value.cover;
        }
        return `${config.public.apiBase}/storage/images/courses/covers/${course.value.cover}`;
      }
      return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
    });
    const logoUrl = computed(() => {
      var _a, _b, _c;
      if (logoPreview.value) return logoPreview.value;
      if ((_a = course.value) == null ? void 0 : _a.logo) {
        if (course.value.logo.startsWith("http")) {
          return course.value.logo;
        }
        return `${config.public.apiBase}/storage/images/courses/logos/${course.value.logo}`;
      }
      if ((_c = (_b = course.value) == null ? void 0 : _b.user) == null ? void 0 : _c.avatar) {
        return course.value.user.avatar;
      }
      return "/images/default-avatar.png";
    });
    function closeEditModal() {
      showEditModal.value = false;
      tempName.value = "";
      tempCode.value = "";
      isUpdatingName.value = false;
      isUpdatingCode.value = false;
    }
    async function saveCourseInfo() {
      if (!tempName.value.trim()) {
        alert("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E01\u0E23\u0E2D\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32");
        return;
      }
      isUpdatingName.value = true;
      try {
        const data = {
          name: tempName.value.trim(),
          code: tempCode.value.trim()
        };
        await api.put(`/api/courses/${courseId.value}`, data);
        courseStore.updateCourse(data);
        emit("refresh");
        closeEditModal();
      } catch (error) {
        console.error("Failed to update course info:", error);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07");
      } finally {
        isUpdatingName.value = false;
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0$2;
      const _component_DialogModal = _sfc_main$3;
      _push(`<!--[--><div class="relative w-full bg-white dark:bg-gray-900 rounded-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transition-all duration-300" data-v-f64f411c><div class="relative h-48 sm:h-64 md:h-72 lg:h-80 bg-cover bg-center bg-no-repeat transition-all duration-500" style="${ssrRenderStyle({ backgroundImage: `url(${coverUrl.value})` })}" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 via-purple-600/10 to-pink-600/20 dark:from-blue-900/30 dark:via-purple-900/20 dark:to-pink-900/30" data-v-f64f411c></div><div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" data-v-f64f411c></div><div class="absolute inset-0 opacity-10 dark:opacity-5" style="${ssrRenderStyle({ "background-image": "radial-gradient(circle at 1px 1px, white 1px, transparent 0)", "background-size": "40px 40px" })}" data-v-f64f411c></div>`);
      if (isAdmin.value) {
        _push(`<div class="absolute top-4 left-4 z-10" data-v-f64f411c><input type="file" class="hidden" accept="image/*" data-v-f64f411c><button type="button"${ssrIncludeBooleanAttr(isUpdatingCover.value) ? " disabled" : ""} class="group relative p-3 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-xl hover:bg-white dark:hover:bg-gray-700 transition-all duration-300 disabled:opacity-50 shadow-lg hover:shadow-xl hover:scale-110 border border-white/20" data-v-f64f411c>`);
        if (isUpdatingCover.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-5 h-5"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:camera-edit-20-filled",
            class: "w-5 h-5 group-hover:scale-110 transition-transform"
          }, null, _parent));
        }
        _push(`<div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap" data-v-f64f411c> \u0E41\u0E01\u0E49\u0E44\u0E02\u0E1B\u0E01 </div></button></div>`);
      } else {
        _push(`<!---->`);
      }
      if (tuitionFees.value) {
        _push(`<div class="absolute top-4 right-4 z-10 animate-pulse" data-v-f64f411c><div class="relative group" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl blur-md opacity-75 group-hover:opacity-100 transition-opacity" data-v-f64f411c></div><div class="relative flex items-center px-4 py-2.5 space-x-2 font-bold text-white rounded-xl bg-gradient-to-r from-yellow-400 to-orange-500 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 border border-yellow-300/50" data-v-f64f411c>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "ri:bit-coin-fill",
          class: "w-6 h-6 animate-spin-slow"
        }, null, _parent));
        _push(`<span class="text-lg" data-v-f64f411c>${ssrInterpolate(tuitionFees.value)}</span><span class="text-sm opacity-90" data-v-f64f411c>\u0E1A\u0E32\u0E17</span></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex flex-col sm:flex-row items-center justify-between w-full px-4 sm:px-6 -mt-[70px] sm:-mt-[80px] md:-mt-[90px] transition-all duration-300" data-v-f64f411c><div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto" data-v-f64f411c><div class="relative flex-shrink-0 group" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-full blur-xl opacity-0 group-hover:opacity-60 transition-opacity duration-500 animate-pulse pointer-events-none -z-10" data-v-f64f411c></div><div class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-36 lg:h-36 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-2xl transition-all duration-300 group-hover:scale-105 group-hover:shadow-3xl z-10" data-v-f64f411c><img${ssrRenderAttr("src", logoUrl.value)} alt="Course Logo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" data-v-f64f411c></div></div>`);
      if (isAdmin.value) {
        _push(`<input type="file" class="hidden" accept="image/*" data-v-f64f411c>`);
      } else {
        _push(`<!---->`);
      }
      if (isAdmin.value) {
        _push(`<button type="button"${ssrIncludeBooleanAttr(isUpdatingLogo.value) ? " disabled" : ""} class="absolute bottom-2 right-2 p-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full hover:from-blue-600 hover:to-purple-700 transition-all duration-300 disabled:opacity-50 shadow-lg hover:shadow-xl hover:scale-110 border-2 border-white dark:border-gray-800 z-10" data-v-f64f411c>`);
        if (isUpdatingLogo.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-4 h-4"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:camera-edit-20-filled",
            class: "w-4 h-4"
          }, null, _parent));
        }
        _push(`</button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="space-y-2 text-center sm:text-left mt-3 sm:mt-6" data-v-f64f411c><div class="flex items-center gap-2 flex-wrap justify-center sm:justify-start" data-v-f64f411c><div class="relative group" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-2xl blur-xl opacity-0 group-hover:opacity-30 transition-opacity duration-500 pointer-events-none -z-10" data-v-f64f411c></div><h1 class="relative z-10 px-5 py-3 text-lg sm:text-xl md:text-2xl font-black text-white bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-2xl shadow-2xl border border-slate-700/50 dark:border-gray-600/50 backdrop-blur-sm transition-all duration-300 group-hover:shadow-3xl group-hover:scale-105" data-v-f64f411c><span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent" data-v-f64f411c>${ssrInterpolate(courseName.value || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")}</span></h1></div>`);
      if (isAdmin.value) {
        _push(`<button class="group relative z-20 p-2.5 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-600 hover:scale-110" data-v-f64f411c>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:edit-24-filled",
          class: "w-5 h-5 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform"
        }, null, _parent));
        _push(`<div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50" data-v-f64f411c> \u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </div></button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex items-center gap-2 justify-center sm:justify-start" data-v-f64f411c>`);
      if (courseCode.value) {
        _push(`<span class="group relative px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-purple-600 rounded-full shadow-xl border-2 border-cyan-400/30 transition-all duration-300 hover:shadow-2xl hover:scale-105 cursor-default" data-v-f64f411c>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:number-symbol-square-24-filled",
          class: "w-4 h-4 inline-block mr-1.5"
        }, null, _parent));
        _push(`<span class="tracking-wider" data-v-f64f411c>${ssrInterpolate(courseCode.value)}</span><div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent transform -skew-x-12 translate-x-full group-hover:translate-x-[-200%] transition-transform duration-1000" data-v-f64f411c></div></span>`);
      } else if (isAdmin.value) {
        _push(`<span class="px-4 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-cyan-400 dark:hover:border-cyan-600 transition-colors cursor-pointer" data-v-f64f411c>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-circle-24-regular",
          class: "w-4 h-4 inline-block mr-1"
        }, null, _parent));
        _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
      if (!isAdmin.value) {
        _push(`<div class="hidden md:block mt-4 sm:mt-0" data-v-f64f411c>`);
        if (__props.courseMemberOfAuth && (memberStatus.value === "0" || memberStatus.value === "pending")) {
          _push(`<div class="relative" data-v-f64f411c><button class="flex items-center gap-2 px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition-colors shadow-md" data-v-f64f411c>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:clock",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span data-v-f64f411c>\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A</span>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:chevron-down",
            class: ["w-4 h-4 transition-transform", { "rotate-180": showAcceptMemberOption.value }]
          }, null, _parent));
          _push(`</button>`);
          if (showAcceptMemberOption.value) {
            _push(`<div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border z-50" data-v-f64f411c><button${ssrIncludeBooleanAttr(isRequestingUnmember.value) ? " disabled" : ""} class="w-full px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg font-medium flex items-center gap-2 disabled:opacity-50" data-v-f64f411c>`);
            if (isRequestingUnmember.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:x-circle",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span data-v-f64f411c>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D</span></button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else if (__props.courseMemberOfAuth && (memberStatus.value === "1" || memberStatus.value === "active")) {
          _push(`<button${ssrIncludeBooleanAttr(isRequestingUnmember.value) ? " disabled" : ""} class="flex items-center gap-2 px-5 py-2.5 bg-emerald-500 hover:bg-red-500 text-white font-semibold rounded-lg transition-colors shadow-md disabled:opacity-50" data-v-f64f411c>`);
          if (isRequestingUnmember.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-5 h-5"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:checkmark-circle-24-filled",
              class: "w-5 h-5"
            }, null, _parent));
          }
          _push(`<span data-v-f64f411c>\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></button>`);
        } else if (!__props.courseMemberOfAuth) {
          _push(`<div class="relative" data-v-f64f411c>`);
          if (!hasGroups.value) {
            _push(`<button${ssrIncludeBooleanAttr(isRequestingMember.value) ? " disabled" : ""} class="flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md disabled:opacity-50" data-v-f64f411c>`);
            if (isRequestingMember.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-plus",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span data-v-f64f411c>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</span></button>`);
          } else {
            _push(`<!--[--><button class="flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md" data-v-f64f411c>`);
            if (isRequestingMember.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-plus",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span data-v-f64f411c>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</span>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:chevron-down",
              class: ["w-4 h-4 transition-transform", { "rotate-180": showOptionGroups.value }]
            }, null, _parent));
            _push(`</button>`);
            if (showOptionGroups.value) {
              _push(`<div class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border z-50 max-h-64 overflow-y-auto" data-v-f64f411c><div class="sticky top-0 bg-gray-50 px-4 py-2 border-b" data-v-f64f411c><p class="text-xs font-medium text-gray-600" data-v-f64f411c>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21</p></div>`);
              if (!courseGroups.value || courseGroups.value.length === 0) {
                _push(`<div class="px-4 py-6 text-center text-gray-500" data-v-f64f411c>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-group",
                  class: "w-12 h-12 mx-auto mb-2 opacity-30"
                }, null, _parent));
                _push(`<p class="text-sm" data-v-f64f411c>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49</p></div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`<!--[-->`);
              ssrRenderList(courseGroups.value, (group) => {
                _push(`<button${ssrIncludeBooleanAttr(isRequestingMember.value) ? " disabled" : ""} class="w-full px-4 py-3 text-left hover:bg-cyan-50 flex items-center gap-3 disabled:opacity-50" data-v-f64f411c><div class="w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0" data-v-f64f411c>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-group",
                  class: "w-4 h-4 text-cyan-600"
                }, null, _parent));
                _push(`</div><div class="flex-1 min-w-0" data-v-f64f411c><p class="font-medium text-gray-900 truncate" data-v-f64f411c>${ssrInterpolate(group.name)}</p><p class="text-xs text-gray-500" data-v-f64f411c>${ssrInterpolate(group.members_count || 0)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p></div></button>`);
              });
              _push(`<!--]--></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<!--]-->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="py-6 px-4 sm:px-6" data-v-f64f411c><div class="flex flex-wrap justify-center gap-4 md:gap-6" data-v-f64f411c><div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border-2 border-blue-200 dark:border-blue-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/10 to-blue-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" data-v-f64f411c></div><div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300" data-v-f64f411c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:book-open-solid",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><div class="relative" data-v-f64f411c><div class="text-2xl font-black text-blue-600 dark:text-blue-400 leading-none" data-v-f64f411c>${ssrInterpolate(lessonsCount.value)}</div><div class="text-xs font-semibold text-blue-700/70 dark:text-blue-300/70 mt-0.5" data-v-f64f411c>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</div></div></div><div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 border-2 border-purple-200 dark:border-purple-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-purple-500/0 via-purple-500/10 to-purple-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" data-v-f64f411c></div><div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300" data-v-f64f411c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:users-solid",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><div class="relative" data-v-f64f411c><div class="text-2xl font-black text-purple-600 dark:text-purple-400 leading-none" data-v-f64f411c>${ssrInterpolate(enrolledStudents.value || 0)}</div><div class="text-xs font-semibold text-purple-700/70 dark:text-purple-300/70 mt-0.5" data-v-f64f411c>\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19</div></div></div><div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border-2 border-green-200 dark:border-green-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-green-500/0 via-green-500/10 to-green-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" data-v-f64f411c></div><div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300" data-v-f64f411c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:user-group-solid",
        class: "w-6 h-6 text-white"
      }, null, _parent));
      _push(`</div><div class="relative" data-v-f64f411c><div class="text-2xl font-black text-green-600 dark:text-green-400 leading-none" data-v-f64f411c>${ssrInterpolate(groupsCount.value || 0)}</div><div class="text-xs font-semibold text-green-700/70 dark:text-green-300/70 mt-0.5" data-v-f64f411c>\u0E01\u0E25\u0E38\u0E48\u0E21</div></div></div>`);
      if ((_a = course.value) == null ? void 0 : _a.rating) {
        _push(`<div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-yellow-50 to-amber-100 dark:from-yellow-900/30 dark:to-amber-800/30 border-2 border-yellow-200 dark:border-yellow-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden" data-v-f64f411c><div class="absolute inset-0 bg-gradient-to-r from-yellow-500/0 via-yellow-500/10 to-yellow-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" data-v-f64f411c></div><div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300" data-v-f64f411c>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="relative" data-v-f64f411c><div class="flex items-center gap-1" data-v-f64f411c><span class="text-2xl font-black text-yellow-600 dark:text-yellow-400 leading-none" data-v-f64f411c>${ssrInterpolate(typeof course.value.rating === "number" ? course.value.rating.toFixed(1) : course.value.rating)}</span><div class="flex items-center" data-v-f64f411c><!--[-->`);
        ssrRenderList(5, (star) => {
          _push(ssrRenderComponent(unref(Icon), {
            key: star,
            icon: star <= Math.round(course.value.rating) ? "fluent:star-12-filled" : "fluent:star-12-regular",
            class: "w-3 h-3 text-yellow-500"
          }, null, _parent));
        });
        _push(`<!--]--></div></div><div class="text-xs font-semibold text-yellow-700/70 dark:text-yellow-300/70 mt-0.5" data-v-f64f411c>${ssrInterpolate(course.value.reviews_count || 0)} \u0E23\u0E35\u0E27\u0E34\u0E27 </div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (academy.value) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/academies/${academy.value.id}`,
          class: "group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 border-2 border-orange-200 dark:border-orange-700 hover:shadow-xl transition-all duration-300 hover:scale-105 overflow-hidden"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="absolute inset-0 bg-gradient-to-r from-orange-500/0 via-orange-500/10 to-orange-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" data-v-f64f411c${_scopeId}></div><div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg overflow-hidden group-hover:shadow-xl group-hover:scale-110 transition-all duration-300" data-v-f64f411c${_scopeId}>`);
              if (academy.value.logo) {
                _push2(`<img${ssrRenderAttr("src", `${_ctx.$config.public.apiBase}/storage/images/academies/logos/${academy.value.logo}`)} class="w-full h-full object-cover" data-v-f64f411c${_scopeId}>`);
              } else {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:academic-cap-solid",
                  class: "w-6 h-6 text-white"
                }, null, _parent2, _scopeId));
              }
              _push2(`</div><div class="relative text-left" data-v-f64f411c${_scopeId}><div class="text-sm font-black text-orange-700 dark:text-orange-400 truncate max-w-[140px] leading-tight" data-v-f64f411c${_scopeId}>${ssrInterpolate(academy.value.name)}</div><div class="text-xs font-semibold text-orange-600/70 dark:text-orange-300/70" data-v-f64f411c${_scopeId}>\u0E2A\u0E16\u0E32\u0E1A\u0E31\u0E19</div></div>`);
            } else {
              return [
                createVNode("div", { class: "absolute inset-0 bg-gradient-to-r from-orange-500/0 via-orange-500/10 to-orange-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000" }),
                createVNode("div", { class: "relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg overflow-hidden group-hover:shadow-xl group-hover:scale-110 transition-all duration-300" }, [
                  academy.value.logo ? (openBlock(), createBlock("img", {
                    key: 0,
                    src: `${_ctx.$config.public.apiBase}/storage/images/academies/logos/${academy.value.logo}`,
                    class: "w-full h-full object-cover"
                  }, null, 8, ["src"])) : (openBlock(), createBlock(unref(Icon), {
                    key: 1,
                    icon: "heroicons:academic-cap-solid",
                    class: "w-6 h-6 text-white"
                  }))
                ]),
                createVNode("div", { class: "relative text-left" }, [
                  createVNode("div", { class: "text-sm font-black text-orange-700 dark:text-orange-400 truncate max-w-[140px] leading-tight" }, toDisplayString(academy.value.name), 1),
                  createVNode("div", { class: "text-xs font-semibold text-orange-600/70 dark:text-orange-300/70" }, "\u0E2A\u0E16\u0E32\u0E1A\u0E31\u0E19")
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (!isAdmin.value) {
        _push(`<div class="flex justify-center mt-4 md:hidden" data-v-f64f411c>`);
        if (__props.courseMemberOfAuth && (memberStatus.value === "0" || memberStatus.value === "pending")) {
          _push(`<div class="relative w-56" data-v-f64f411c><button class="w-full flex items-center justify-between gap-2 px-5 py-2.5 bg-yellow-500 active:bg-yellow-600 text-white font-semibold rounded-lg transition-colors shadow-md" data-v-f64f411c><span class="flex items-center gap-2" data-v-f64f411c>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:clock",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span data-v-f64f411c>\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A</span></span>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:chevron-down",
            class: ["w-4 h-4 transition-transform", { "rotate-180": showAcceptMemberOption.value }]
          }, null, _parent));
          _push(`</button>`);
          if (showAcceptMemberOption.value) {
            _push(`<div class="absolute left-0 right-0 mt-2 bg-white rounded-lg shadow-xl border z-50" data-v-f64f411c><button${ssrIncludeBooleanAttr(isRequestingUnmember.value) ? " disabled" : ""} class="w-full px-4 py-3 text-red-600 active:bg-red-50 rounded-lg font-medium flex items-center justify-center gap-2 disabled:opacity-50" data-v-f64f411c>`);
            if (isRequestingUnmember.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:x-circle",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span data-v-f64f411c>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E04\u0E33\u0E02\u0E2D</span></button></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else if (__props.courseMemberOfAuth && (memberStatus.value === "1" || memberStatus.value === "active")) {
          _push(`<button${ssrIncludeBooleanAttr(isRequestingUnmember.value) ? " disabled" : ""} class="w-56 flex items-center justify-center gap-2 px-5 py-2.5 bg-yellow-300 active:bg-red-500 active:text-white text-gray-700 font-semibold rounded-lg transition-all shadow-md disabled:opacity-50" data-v-f64f411c>`);
          if (isRequestingUnmember.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "svg-spinners:ring-resize",
              class: "w-5 h-5"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "majesticons:door-exit-line",
              class: "w-5 h-5"
            }, null, _parent));
          }
          _push(`<span data-v-f64f411c>\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></button>`);
        } else if (!__props.courseMemberOfAuth) {
          _push(`<div class="relative w-56" data-v-f64f411c>`);
          if (!hasGroups.value) {
            _push(`<button${ssrIncludeBooleanAttr(isRequestingMember.value) ? " disabled" : ""} class="w-full flex items-center justify-center gap-2 px-5 py-2.5 bg-cyan-500 active:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md disabled:opacity-50" data-v-f64f411c>`);
            if (isRequestingMember.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-plus",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span data-v-f64f411c>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</span></button>`);
          } else {
            _push(`<!--[--><button class="w-full flex items-center justify-between gap-2 px-5 py-2.5 bg-cyan-500 active:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md" data-v-f64f411c><span class="flex items-center gap-2" data-v-f64f411c>`);
            if (isRequestingMember.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-plus",
                class: "w-5 h-5"
              }, null, _parent));
            }
            _push(`<span data-v-f64f411c>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</span></span>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:chevron-down",
              class: ["w-4 h-4 transition-transform", { "rotate-180": showOptionGroups.value }]
            }, null, _parent));
            _push(`</button>`);
            if (showOptionGroups.value) {
              _push(`<div class="absolute left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border z-50 max-h-64 overflow-y-auto" data-v-f64f411c><div class="sticky top-0 bg-gray-50 px-4 py-2 border-b" data-v-f64f411c><p class="text-xs font-medium text-gray-600" data-v-f64f411c>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21</p></div>`);
              if (!courseGroups.value || courseGroups.value.length === 0) {
                _push(`<div class="px-4 py-6 text-center text-gray-500" data-v-f64f411c>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-group",
                  class: "w-12 h-12 mx-auto mb-2 opacity-30"
                }, null, _parent));
                _push(`<p class="text-sm" data-v-f64f411c>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49</p></div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`<!--[-->`);
              ssrRenderList(courseGroups.value, (group) => {
                _push(`<button${ssrIncludeBooleanAttr(isRequestingMember.value) ? " disabled" : ""} class="w-full px-4 py-3 text-left active:bg-cyan-50 flex items-center gap-3 border-b last:border-b-0 disabled:opacity-50" data-v-f64f411c><div class="w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0" data-v-f64f411c>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-group",
                  class: "w-4 h-4 text-cyan-600"
                }, null, _parent));
                _push(`</div><div class="flex-1 min-w-0" data-v-f64f411c><p class="font-medium text-gray-900 truncate" data-v-f64f411c>${ssrInterpolate(group.name)}</p><p class="text-xs text-gray-500" data-v-f64f411c>${ssrInterpolate(group.members_count || 0)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p></div></button>`);
              });
              _push(`<!--]--></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<!--]-->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      _push(ssrRenderComponent(_component_DialogModal, {
        show: showEditModal.value,
        onClose: closeEditModal,
        "max-width": "2xl"
      }, {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center gap-3" data-v-f64f411c${_scopeId}><div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl" data-v-f64f411c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-24-filled",
              class: "w-6 h-6 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><div data-v-f64f411c${_scopeId}><h3 class="text-xl font-bold text-gray-900 dark:text-white" data-v-f64f411c${_scopeId}>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h3><p class="text-sm text-gray-500 dark:text-gray-400" data-v-f64f411c${_scopeId}>\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E25\u0E30\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32</p></div></div>`);
          } else {
            return [
              createVNode("div", { class: "flex items-center gap-3" }, [
                createVNode("div", { class: "flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl" }, [
                  createVNode(unref(Icon), {
                    icon: "fluent:edit-24-filled",
                    class: "w-6 h-6 text-white"
                  })
                ]),
                createVNode("div", null, [
                  createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white" }, "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"),
                  createVNode("p", { class: "text-sm text-gray-500 dark:text-gray-400" }, "\u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E25\u0E30\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32")
                ])
              ])
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-6" data-v-f64f411c${_scopeId}><div class="space-y-2" data-v-f64f411c${_scopeId}><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-f64f411c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:book-open-solid",
              class: "w-5 h-5 text-blue-600 dark:text-blue-400"
            }, null, _parent2, _scopeId));
            _push2(`<span data-v-f64f411c${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span><span class="text-red-500" data-v-f64f411c${_scopeId}>*</span></label><textarea rows="3" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 (\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2A\u0E48\u0E0A\u0E37\u0E48\u0E2D\u0E22\u0E32\u0E27\u0E44\u0E14\u0E49)" class="${ssrRenderClass([{ "border-red-500 focus:ring-red-500/50": !tempName.value.trim() }, "w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-blue-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600 transition-all resize-none"])}" data-v-f64f411c${_scopeId}>${ssrInterpolate(tempName.value)}</textarea><p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1" data-v-f64f411c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:information-circle",
              class: "w-4 h-4"
            }, null, _parent2, _scopeId));
            _push2(`<span data-v-f64f411c${_scopeId}>\u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23: ${ssrInterpolate(tempName.value.length)} / 500</span></p></div><div class="space-y-2" data-v-f64f411c${_scopeId}><label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-f64f411c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:number-symbol-square-24-filled",
              class: "w-5 h-5 text-cyan-600 dark:text-cyan-400"
            }, null, _parent2, _scopeId));
            _push2(`<span data-v-f64f411c${_scopeId}>\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32</span><span class="text-gray-400 text-xs" data-v-f64f411c${_scopeId}>(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</span></label><input${ssrRenderAttr("value", tempCode.value)} type="text" placeholder="\u0E40\u0E0A\u0E48\u0E19 CS101, MATH201" maxlength="50" class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-cyan-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-cyan-400 dark:hover:border-cyan-600 transition-all" data-v-f64f411c${_scopeId}><p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1" data-v-f64f411c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:light-bulb",
              class: "w-4 h-4"
            }, null, _parent2, _scopeId));
            _push2(`<span data-v-f64f411c${_scopeId}>\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32\u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E40\u0E1B\u0E47\u0E19 badge \u0E17\u0E35\u0E48\u0E2A\u0E27\u0E22\u0E07\u0E32\u0E21</span></p></div><div class="p-4 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800" data-v-f64f411c${_scopeId}><p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1" data-v-f64f411c${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:eye",
              class: "w-4 h-4"
            }, null, _parent2, _scopeId));
            _push2(`<span data-v-f64f411c${_scopeId}>\u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25:</span></p><div class="flex items-center gap-2 flex-wrap" data-v-f64f411c${_scopeId}><div class="px-4 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-xl shadow-lg border border-slate-700/50" data-v-f64f411c${_scopeId}><span class="text-sm font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent" data-v-f64f411c${_scopeId}>${ssrInterpolate(tempName.value || "\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32")}</span></div>`);
            if (tempCode.value.trim()) {
              _push2(`<div class="px-3 py-1.5 bg-gradient-to-r from-cyan-500 via-blue-600 to-purple-600 rounded-full shadow-lg" data-v-f64f411c${_scopeId}><span class="text-xs font-bold text-white tracking-wider" data-v-f64f411c${_scopeId}>${ssrInterpolate(tempCode.value)}</span></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div></div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-6" }, [
                createVNode("div", { class: "space-y-2" }, [
                  createVNode("label", { class: "flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300" }, [
                    createVNode(unref(Icon), {
                      icon: "heroicons:book-open-solid",
                      class: "w-5 h-5 text-blue-600 dark:text-blue-400"
                    }),
                    createVNode("span", null, "\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"),
                    createVNode("span", { class: "text-red-500" }, "*")
                  ]),
                  withDirectives(createVNode("textarea", {
                    "onUpdate:modelValue": ($event) => tempName.value = $event,
                    rows: "3",
                    placeholder: "\u0E01\u0E23\u0E2D\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 (\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E2A\u0E48\u0E0A\u0E37\u0E48\u0E2D\u0E22\u0E32\u0E27\u0E44\u0E14\u0E49)",
                    class: ["w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-blue-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600 transition-all resize-none", { "border-red-500 focus:ring-red-500/50": !tempName.value.trim() }]
                  }, null, 10, ["onUpdate:modelValue"]), [
                    [vModelText, tempName.value]
                  ]),
                  createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1" }, [
                    createVNode(unref(Icon), {
                      icon: "heroicons:information-circle",
                      class: "w-4 h-4"
                    }),
                    createVNode("span", null, "\u0E15\u0E31\u0E27\u0E2D\u0E31\u0E01\u0E29\u0E23: " + toDisplayString(tempName.value.length) + " / 500", 1)
                  ])
                ]),
                createVNode("div", { class: "space-y-2" }, [
                  createVNode("label", { class: "flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300" }, [
                    createVNode(unref(Icon), {
                      icon: "fluent:number-symbol-square-24-filled",
                      class: "w-5 h-5 text-cyan-600 dark:text-cyan-400"
                    }),
                    createVNode("span", null, "\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32"),
                    createVNode("span", { class: "text-gray-400 text-xs" }, "(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)")
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => tempCode.value = $event,
                    type: "text",
                    placeholder: "\u0E40\u0E0A\u0E48\u0E19 CS101, MATH201",
                    maxlength: "50",
                    class: "w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-cyan-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-cyan-400 dark:hover:border-cyan-600 transition-all"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, tempCode.value]
                  ]),
                  createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1" }, [
                    createVNode(unref(Icon), {
                      icon: "heroicons:light-bulb",
                      class: "w-4 h-4"
                    }),
                    createVNode("span", null, "\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32\u0E08\u0E30\u0E41\u0E2A\u0E14\u0E07\u0E40\u0E1B\u0E47\u0E19 badge \u0E17\u0E35\u0E48\u0E2A\u0E27\u0E22\u0E07\u0E32\u0E21")
                  ])
                ]),
                createVNode("div", { class: "p-4 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800" }, [
                  createVNode("p", { class: "text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1" }, [
                    createVNode(unref(Icon), {
                      icon: "heroicons:eye",
                      class: "w-4 h-4"
                    }),
                    createVNode("span", null, "\u0E15\u0E31\u0E27\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E01\u0E32\u0E23\u0E41\u0E2A\u0E14\u0E07\u0E1C\u0E25:")
                  ]),
                  createVNode("div", { class: "flex items-center gap-2 flex-wrap" }, [
                    createVNode("div", { class: "px-4 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-xl shadow-lg border border-slate-700/50" }, [
                      createVNode("span", { class: "text-sm font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent" }, toDisplayString(tempName.value || "\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"), 1)
                    ]),
                    tempCode.value.trim() ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "px-3 py-1.5 bg-gradient-to-r from-cyan-500 via-blue-600 to-purple-600 rounded-full shadow-lg"
                    }, [
                      createVNode("span", { class: "text-xs font-bold text-white tracking-wider" }, toDisplayString(tempCode.value), 1)
                    ])) : createCommentVNode("", true)
                  ])
                ])
              ])
            ];
          }
        }),
        footer: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex items-center justify-end gap-3" data-v-f64f411c${_scopeId}><button${ssrIncludeBooleanAttr(isUpdatingName.value) ? " disabled" : ""} class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed" data-v-f64f411c${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(!tempName.value.trim() || isUpdatingName.value) ? " disabled" : ""} class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl" data-v-f64f411c${_scopeId}>`);
            if (isUpdatingName.value) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
            } else {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:save-24-filled",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
            }
            _push2(`<span data-v-f64f411c${_scopeId}>${ssrInterpolate(isUpdatingName.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02")}</span></button></div>`);
          } else {
            return [
              createVNode("div", { class: "flex items-center justify-end gap-3" }, [
                createVNode("button", {
                  onClick: closeEditModal,
                  disabled: isUpdatingName.value,
                  class: "px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["disabled"]),
                createVNode("button", {
                  onClick: saveCourseInfo,
                  disabled: !tempName.value.trim() || isUpdatingName.value,
                  class: "flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                }, [
                  isUpdatingName.value ? (openBlock(), createBlock(unref(Icon), {
                    key: 0,
                    icon: "svg-spinners:ring-resize",
                    class: "w-5 h-5"
                  })) : (openBlock(), createBlock(unref(Icon), {
                    key: 1,
                    icon: "fluent:save-24-filled",
                    class: "w-5 h-5"
                  })),
                  createVNode("span", null, toDisplayString(isUpdatingName.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02"), 1)
                ], 8, ["disabled"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseProfileCover.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const CourseProfileCover = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-f64f411c"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CourseNavbarTab",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    isCourseAdmin: { type: Boolean, default: false },
    courseMemberOfAuth: { default: null }
  },
  setup(__props) {
    const props = __props;
    const route = useRoute();
    const api = useApi();
    const courseMemberStore = useCourseMemberStore();
    const activeTab = computed(() => {
      const path = route.path;
      if (path.includes("/basic-info")) return 12;
      if (path.includes("/feeds")) return 11;
      if (path.includes("/attendances")) return 7;
      if (path.includes("/lessons")) return 1;
      if (path.includes("/assignments")) return 2;
      if (path.includes("/quizzes")) return 3;
      if (path.includes("/play/groups")) return 5;
      if (path.includes("/members")) return 4;
      if (path.includes("/settings")) return 8;
      if (path.includes("/member-settings")) return 9;
      if (path.includes("/my-progress")) return 9;
      if (path.includes("/progress")) return 10;
      if (path.includes("/admin")) return 13;
      if (path.endsWith(`/courses/${props.courseId}`) || path.endsWith(`/courses/${props.courseId}/`)) return 12;
      return 12;
    });
    let isSavingTab = false;
    watch(activeTab, async (newTab, oldTab) => {
      var _a;
      if (!((_a = props.courseMemberOfAuth) == null ? void 0 : _a.id) || newTab === oldTab || isSavingTab) return;
      isSavingTab = true;
      try {
        await api.post(`/api/courses/${props.courseId}/members/${props.courseMemberOfAuth.id}/set-active-tab`, {
          tab: newTab
        });
        if (courseMemberStore.member) {
          courseMemberStore.member.last_accessed_tab = newTab;
        }
      } catch (error) {
        console.error("Error saving last accessed tab:", error);
      } finally {
        isSavingTab = false;
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "w-full mt-4 overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-100 dark:border-gray-700" }, _attrs))} data-v-4fd2103f><div class="flex flex-row justify-around relative" data-v-4fd2103f>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${__props.courseId}`,
        class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 12, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 12 }]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:information-circle",
              class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 12, "hover:text-cyan-400": unref(activeTab) !== 12 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 12 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B</span></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                createVNode(unref(Icon), {
                  icon: "heroicons:information-circle",
                  class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 12, "hover:text-cyan-400": unref(activeTab) !== 12 }]
                }, null, 8, ["class"]),
                createVNode("span", {
                  class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 12 }]
                }, "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B", 2)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${__props.courseId}/feeds`,
        class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 11, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 11 }]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "codicon:feedback",
              class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 11, "hover:text-cyan-400": unref(activeTab) !== 11 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 11 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19</span></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                createVNode(unref(Icon), {
                  icon: "codicon:feedback",
                  class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 11, "hover:text-cyan-400": unref(activeTab) !== 11 }]
                }, null, 8, ["class"]),
                createVNode("span", {
                  class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 11 }]
                }, "\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19", 2)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (__props.isCourseAdmin || __props.courseMemberOfAuth) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/attendances`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 7, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 7 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "tabler:calendar-user",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 7, "hover:text-cyan-400": unref(activeTab) !== 7 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 7 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "tabler:calendar-user",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 7, "hover:text-cyan-400": unref(activeTab) !== 7 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 7 }]
                  }, "\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${__props.courseId}/lessons`,
        class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 1, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 1 }]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "icon-park-outline:view-grid-detail",
              class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 1, "hover:text-cyan-400": unref(activeTab) !== 1 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 1 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</span></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                createVNode(unref(Icon), {
                  icon: "icon-park-outline:view-grid-detail",
                  class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 1, "hover:text-cyan-400": unref(activeTab) !== 1 }]
                }, null, 8, ["class"]),
                createVNode("span", {
                  class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 1 }]
                }, "\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19", 2)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${__props.courseId}/assignments`,
        class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 2, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 2 }]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "material-symbols:assignment-add-outline",
              class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 2, "hover:text-cyan-400": unref(activeTab) !== 2 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 2 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19</span></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                createVNode(unref(Icon), {
                  icon: "material-symbols:assignment-add-outline",
                  class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 2, "hover:text-cyan-400": unref(activeTab) !== 2 }]
                }, null, 8, ["class"]),
                createVNode("span", {
                  class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 2 }]
                }, "\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19", 2)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (__props.courseMemberOfAuth || __props.isCourseAdmin) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/quizzes`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 3, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 3 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "healthicons:i-exam-qualification-outline",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 3, "hover:text-cyan-400": unref(activeTab) !== 3 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 3 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E17\u0E14\u0E2A\u0E2D\u0E1A</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "healthicons:i-exam-qualification-outline",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 3, "hover:text-cyan-400": unref(activeTab) !== 3 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 3 }]
                  }, "\u0E17\u0E14\u0E2A\u0E2D\u0E1A", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/courses/${__props.courseId}/groups`,
        class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 5, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 5 }]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons-outline:user-group",
              class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 5, "hover:text-cyan-400": unref(activeTab) !== 5 }]
            }, null, _parent2, _scopeId));
            _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 5 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E01\u0E25\u0E38\u0E48\u0E21</span></div>`);
          } else {
            return [
              createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                createVNode(unref(Icon), {
                  icon: "heroicons-outline:user-group",
                  class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 5, "hover:text-cyan-400": unref(activeTab) !== 5 }]
                }, null, 8, ["class"]),
                createVNode("span", {
                  class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 5 }]
                }, "\u0E01\u0E25\u0E38\u0E48\u0E21", 2)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      if (__props.courseMemberOfAuth !== null) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/members`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 4, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 4 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "ph:users-four",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 4, "hover:text-cyan-400": unref(activeTab) !== 4 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 4 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "ph:users-four",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 4, "hover:text-cyan-400": unref(activeTab) !== 4 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 4 }]
                  }, "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (__props.isCourseAdmin) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/settings`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 8, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 8 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi-light:settings",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 8, "hover:text-cyan-400": unref(activeTab) !== 8 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 8 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "mdi-light:settings",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 8, "hover:text-cyan-400": unref(activeTab) !== 8 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 8 }]
                  }, "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (!__props.isCourseAdmin && __props.courseMemberOfAuth) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/my-progress`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 9, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 9 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:graph-box-plus-outline",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 9, "hover:text-cyan-400": unref(activeTab) !== 9 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 9 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:graph-box-plus-outline",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 9, "hover:text-cyan-400": unref(activeTab) !== 9 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 9 }]
                  }, "\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (__props.isCourseAdmin) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/progress`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 10, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 10 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:graph-box-plus-outline",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 10, "hover:text-cyan-400": unref(activeTab) !== 10 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 10 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "mdi:graph-box-plus-outline",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 10, "hover:text-cyan-400": unref(activeTab) !== 10 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 10 }]
                  }, "\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      if (__props.isCourseAdmin) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: `/courses/${__props.courseId}/admin`,
          class: ["flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105", { "border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm": unref(activeTab) === 13, "hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent": unref(activeTab) !== 13 }]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" data-v-4fd2103f${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "eos-icons:admin-outlined",
                class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 13, "hover:text-cyan-400": unref(activeTab) !== 13 }]
              }, null, _parent2, _scopeId));
              _push2(`<span class="${ssrRenderClass([{ "text-cyan-500 font-semibold": unref(activeTab) === 13 }, "hidden md:block mt-1 text-sm font-medium transition-all duration-300"])}" data-v-4fd2103f${_scopeId}>\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25</span></div>`);
            } else {
              return [
                createVNode("div", { class: "flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300" }, [
                  createVNode(unref(Icon), {
                    icon: "eos-icons:admin-outlined",
                    class: ["w-6 h-6 md:w-8 md:h-8 transition-all duration-300", { "text-cyan-500 scale-110": unref(activeTab) === 13, "hover:text-cyan-400": unref(activeTab) !== 13 }]
                  }, null, 8, ["class"]),
                  createVNode("span", {
                    class: ["hidden md:block mt-1 text-sm font-medium transition-all duration-300", { "text-cyan-500 font-semibold": unref(activeTab) === 13 }]
                  }, "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25", 2)
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseNavbarTab.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const CourseNavbarTab = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-4fd2103f"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[id]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const api = useApi();
    const courseStore = useCourseStore();
    const courseGroupStore = useCourseGroupStore();
    const courseMemberStore = useCourseMemberStore();
    const course = ref(null);
    const academy = ref(null);
    const isCourseAdmin = ref(false);
    const courseMemberOfAuth = ref(null);
    const isLoading = ref(true);
    const error = ref(null);
    const courseId = computed(() => route.params.id);
    const fetchCourse = async (forceRefresh = false) => {
      var _a;
      if (!forceRefresh && courseStore.isCacheValid && ((_a = courseStore.currentCourse) == null ? void 0 : _a.id) == courseId.value) {
        course.value = courseStore.currentCourse;
        academy.value = courseStore.academy;
        isCourseAdmin.value = courseStore.isCourseAdmin;
        if (courseMemberStore.member) {
          courseMemberOfAuth.value = courseMemberStore.member;
        }
        isLoading.value = false;
        return;
      }
      isLoading.value = true;
      error.value = null;
      try {
        const response = await api.get(`/api/courses/${courseId.value}/feeds`);
        if (response.success) {
          course.value = response.course;
          academy.value = response.academy;
          isCourseAdmin.value = response.isCourseAdmin;
          courseMemberOfAuth.value = response.courseMemberOfAuth;
          courseStore.setCourse(response.course);
          courseStore.setAcademy(response.academy);
          courseStore.setIsCourseAdmin(response.isCourseAdmin);
          courseGroupStore.setGroups(response.courseGroups || [], courseId.value);
          courseMemberStore.setMember(response.courseMemberOfAuth);
        }
      } catch (err) {
        error.value = "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E44\u0E14\u0E49";
      } finally {
        isLoading.value = false;
      }
    };
    const onRequestMember = (groupId) => {
      fetchCourse(true);
    };
    const onRequestUnmember = () => {
      fetchCourse(true);
    };
    provide("course", course);
    provide("academy", academy);
    provide("isCourseAdmin", isCourseAdmin);
    provide("courseMemberOfAuth", courseMemberOfAuth);
    provide("isLoading", isLoading);
    provide("refreshCourse", fetchCourse);
    watch(course, (newCourse) => {
      if (newCourse == null ? void 0 : newCourse.name) {
        useHead({
          title: `${newCourse.name} - \u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32`
        });
      }
    });
    const acceptInvite = async () => {
      try {
        const res = await api.post(`/api/courses/${courseId.value}/admins/invitations/${courseMemberOfAuth.value.id}/accept`);
        if (res.success) {
          Swal.fire({ title: "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", text: "\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E41\u0E25\u0E49\u0E27", icon: "success" });
          fetchCourse(true);
        }
      } catch (e) {
        Swal.fire({ title: "\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14", text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E44\u0E14\u0E49", icon: "error" });
      }
    };
    const declineInvite = async () => {
      const result = await Swal.fire({
        title: "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18?",
        text: "\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D\u0E19\u0E35\u0E49\u0E43\u0E0A\u0E48\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "\u0E43\u0E0A\u0E48, \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18",
        cancelButtonText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
        confirmButtonColor: "#d33"
      });
      if (result.isConfirmed) {
        try {
          const res = await api.post(`/api/courses/${courseId.value}/admins/invitations/${courseMemberOfAuth.value.id}/decline`);
          if (res.success) {
            Swal.fire({ title: "\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E41\u0E25\u0E49\u0E27", text: "\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E04\u0E33\u0E40\u0E0A\u0E34\u0E0D\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27", icon: "success" });
            navigateTo("/dashboard");
          }
        } catch (e) {
          Swal.fire({ title: "\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14", text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18\u0E44\u0E14\u0E49", icon: "error" });
        }
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLayout = __nuxt_component_0$1;
      const _component_NuxtPage = __nuxt_component_0;
      _push(ssrRenderComponent(_component_NuxtLayout, mergeProps({ name: "main" }, _attrs), {
        hero: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (unref(isLoading)) {
              _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse"${_scopeId}><div class="h-48 bg-gray-200 dark:bg-gray-700"${_scopeId}></div><div class="p-6 space-y-4"${_scopeId}><div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-2/3"${_scopeId}></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"${_scopeId}></div></div></div>`);
            } else if (unref(error)) {
              _push2(`<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:error-circle-24-regular",
                class: "w-20 h-20 text-red-500 mx-auto mb-4"
              }, null, _parent2, _scopeId));
              _push2(`<h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h3><p class="text-gray-500 dark:text-gray-400 mb-4"${_scopeId}>${ssrInterpolate(unref(error))}</p><button class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"${_scopeId}> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
            } else if (unref(course)) {
              _push2(`<!--[-->`);
              _push2(ssrRenderComponent(CourseProfileCover, {
                "course-member-of-auth": unref(courseMemberOfAuth),
                onRequestMember,
                onRequestUnmember,
                onRefresh: fetchCourse
              }, null, _parent2, _scopeId));
              if (unref(courseMemberOfAuth) && unref(courseMemberOfAuth).status === 2) {
                _push2(`<div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 flex flex-col md:flex-row items-center justify-between shadow-lg mx-4 mt-4 gap-4"${_scopeId}><div class="flex items-center gap-3"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:email-alert",
                  class: "w-8 h-8 text-yellow-600 dark:text-yellow-500"
                }, null, _parent2, _scopeId));
                _push2(`<div${_scopeId}><h3 class="font-bold text-yellow-800 dark:text-yellow-200 text-lg"${_scopeId}>\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E40\u0E0A\u0E34\u0E0D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49</h3><p class="text-sm text-yellow-700 dark:text-yellow-300"${_scopeId}>\u0E43\u0E19\u0E10\u0E32\u0E19\u0E30 <span class="font-semibold"${_scopeId}>${ssrInterpolate(unref(courseMemberOfAuth).role === 4 ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A (Admin)" : "\u0E1C\u0E39\u0E49\u0E0A\u0E48\u0E27\u0E22\u0E2A\u0E2D\u0E19 (TA)")}</span></p></div></div><div class="flex gap-2 w-full md:w-auto"${_scopeId}><button class="flex-1 md:flex-none px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-sm transition-colors"${_scopeId}> \u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A </button><button class="flex-1 md:flex-none px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold shadow-sm transition-colors"${_scopeId}> \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 </button></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(ssrRenderComponent(CourseNavbarTab, {
                "course-id": unref(courseId),
                "is-course-admin": unref(isCourseAdmin),
                "course-member-of-auth": unref(courseMemberOfAuth)
              }, null, _parent2, _scopeId));
              _push2(`<!--]-->`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              unref(isLoading) ? (openBlock(), createBlock("div", {
                key: 0,
                class: "bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse"
              }, [
                createVNode("div", { class: "h-48 bg-gray-200 dark:bg-gray-700" }),
                createVNode("div", { class: "p-6 space-y-4" }, [
                  createVNode("div", { class: "h-8 bg-gray-200 dark:bg-gray-700 rounded w-2/3" }),
                  createVNode("div", { class: "h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3" })
                ])
              ])) : unref(error) ? (openBlock(), createBlock("div", {
                key: 1,
                class: "bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center"
              }, [
                createVNode(unref(Icon), {
                  icon: "fluent:error-circle-24-regular",
                  class: "w-20 h-20 text-red-500 mx-auto mb-4"
                }),
                createVNode("h3", { class: "text-xl font-bold text-gray-900 dark:text-white mb-2" }, "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"),
                createVNode("p", { class: "text-gray-500 dark:text-gray-400 mb-4" }, toDisplayString(unref(error)), 1),
                createVNode("button", {
                  onClick: () => fetchCourse(true),
                  class: "px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                }, " \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 ", 8, ["onClick"])
              ])) : unref(course) ? (openBlock(), createBlock(Fragment, { key: 2 }, [
                createVNode(CourseProfileCover, {
                  "course-member-of-auth": unref(courseMemberOfAuth),
                  onRequestMember,
                  onRequestUnmember,
                  onRefresh: fetchCourse
                }, null, 8, ["course-member-of-auth"]),
                unref(courseMemberOfAuth) && unref(courseMemberOfAuth).status === 2 ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 flex flex-col md:flex-row items-center justify-between shadow-lg mx-4 mt-4 gap-4"
                }, [
                  createVNode("div", { class: "flex items-center gap-3" }, [
                    createVNode(unref(Icon), {
                      icon: "mdi:email-alert",
                      class: "w-8 h-8 text-yellow-600 dark:text-yellow-500"
                    }),
                    createVNode("div", null, [
                      createVNode("h3", { class: "font-bold text-yellow-800 dark:text-yellow-200 text-lg" }, "\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E40\u0E0A\u0E34\u0E0D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49"),
                      createVNode("p", { class: "text-sm text-yellow-700 dark:text-yellow-300" }, [
                        createTextVNode("\u0E43\u0E19\u0E10\u0E32\u0E19\u0E30 "),
                        createVNode("span", { class: "font-semibold" }, toDisplayString(unref(courseMemberOfAuth).role === 4 ? "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A (Admin)" : "\u0E1C\u0E39\u0E49\u0E0A\u0E48\u0E27\u0E22\u0E2A\u0E2D\u0E19 (TA)"), 1)
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "flex gap-2 w-full md:w-auto" }, [
                    createVNode("button", {
                      onClick: acceptInvite,
                      class: "flex-1 md:flex-none px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-sm transition-colors"
                    }, " \u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A "),
                    createVNode("button", {
                      onClick: declineInvite,
                      class: "flex-1 md:flex-none px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold shadow-sm transition-colors"
                    }, " \u0E1B\u0E0F\u0E34\u0E40\u0E2A\u0E18 ")
                  ])
                ])) : createCommentVNode("", true),
                createVNode(CourseNavbarTab, {
                  "course-id": unref(courseId),
                  "is-course-admin": unref(isCourseAdmin),
                  "course-member-of-auth": unref(courseMemberOfAuth)
                }, null, 8, ["course-id", "is-course-admin", "course-member-of-auth"])
              ], 64)) : createCommentVNode("", true)
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (unref(course) && !unref(isLoading) && !unref(error)) {
              _push2(ssrRenderComponent(_component_NuxtPage, null, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              unref(course) && !unref(isLoading) && !unref(error) ? (openBlock(), createBlock(_component_NuxtPage, { key: 0 })) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_id_-D3T5EJWF.mjs.map
