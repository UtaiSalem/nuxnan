import { defineComponent, inject, ref, unref, mergeProps, computed, watch, resolveComponent, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrRenderStyle, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as __nuxt_component_1 } from './RichTextViewer-UnGuFLT8.mjs';
import { _ as __nuxt_component_0 } from './RichTextEditor-DEEazQRP.mjs';
import { _ as _sfc_main$5 } from './AssignmentGradingView-CCvO8j6N.mjs';
import { _ as _sfc_main$4 } from './AssignmentSubmissionForm-BYC_TqBC.mjs';
import { i as useApi, _ as _export_sfc, n as navigateTo } from './server.mjs';
import { C as ContentLoader } from './ContentLoader-D4cV05oG.mjs';
import './useAvatar-C8DTKR1c.mjs';
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

const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "AssignmentCard",
  __ssrInlineRender: true,
  props: {
    assignment: {},
    isCourseAdmin: { type: Boolean, default: false },
    courseId: {}
  },
  emits: ["edit", "delete", "click", "refresh"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    ref(false);
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleString("th-TH", {
        dateStyle: "medium",
        timeStyle: "short"
      });
    };
    const getStatusBadge = computed(() => {
      if (props.assignment.status === 1 || props.assignment.is_published) {
        return {
          text: "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48",
          color: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
          icon: "fluent:checkmark-circle-16-filled"
        };
      }
      return {
        text: "\u0E23\u0E48\u0E32\u0E07",
        color: "bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400",
        icon: "fluent:drafts-16-regular"
      };
    });
    const isOverdue = computed(() => {
      if (!props.assignment.due_date) return false;
      return new Date(props.assignment.due_date) < /* @__PURE__ */ new Date();
    });
    const currentAnswer = computed(() => {
      if (props.assignment.answers && props.assignment.answers.length > 0) {
        return props.assignment.answers[0];
      }
      return null;
    });
    const hasAnswer = computed(() => !!currentAnswer.value);
    const answerStatus = computed(() => {
      if (currentAnswer.value) return currentAnswer.value.status;
      return props.assignment.answer_status;
    });
    const answerContent = computed(() => {
      var _a;
      return (_a = currentAnswer.value) == null ? void 0 : _a.content;
    });
    const answerImages = computed(() => {
      var _a;
      return ((_a = currentAnswer.value) == null ? void 0 : _a.images) || [];
    });
    computed(() => {
      return answerStatus.value === "submitted" || answerStatus.value === "graded";
    });
    ref(false);
    const isEditingGraded = ref(false);
    const handleGradedEditSubmit = () => {
      isEditingGraded.value = false;
      emit("refresh");
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e;
      _push(`<article${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden mb-6 border border-gray-200 dark:border-gray-700 group" }, _attrs))}><div class="relative"><div class="relative h-32 bg-gradient-to-br from-violet-600 via-indigo-600 to-cyan-600 dark:from-violet-900 dark:via-indigo-900 dark:to-cyan-900 overflow-hidden rounded-t-2xl"><div class="absolute inset-0 bg-black/10"></div><div class="w-full h-full flex items-center justify-center">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clipboard-task-24-filled",
        class: "w-16 h-16 text-white/20 animate-pulse"
      }, null, _parent));
      _push(`</div></div><div class="absolute top-4 left-4 flex flex-wrap gap-2"><span class="${ssrRenderClass([getStatusBadge.value.color, "inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg backdrop-blur-md bg-white/90 dark:bg-gray-900/80 ring-1 ring-white/20 transition-transform hover:scale-105"])}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: getStatusBadge.value.icon,
        class: "w-3.5 h-3.5"
      }, null, _parent));
      _push(` ${ssrInterpolate(getStatusBadge.value.text)}</span>`);
      if (__props.assignment.points) {
        _push(`<span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/90 backdrop-blur-md text-white text-xs font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-20-filled",
          class: "w-3.5 h-3.5"
        }, null, _parent));
        _push(` ${ssrInterpolate(__props.assignment.points)} \u0E04\u0E30\u0E41\u0E19\u0E19 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (__props.isCourseAdmin) {
        _push(`<div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200"><button class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 text-blue-600 shadow-lg backdrop-blur hover:scale-105 transition-all" title="\u0E41\u0E01\u0E49\u0E44\u0E02">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:edit-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button><button class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 text-red-600 shadow-lg backdrop-blur hover:scale-105 transition-all" title="\u0E25\u0E1A">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:delete-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-6"><div class="mb-4"><button class="text-left w-full group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 leading-tight">${ssrInterpolate(__props.assignment.title || __props.assignment.name)}</h2></button><div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mt-2">`);
      if (__props.assignment.due_date) {
        _push(`<div class="${ssrRenderClass([isOverdue.value ? "text-red-500 font-medium" : "", "flex items-center gap-1.5"])}">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-clock-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span>\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E2A\u0E48\u0E07: ${ssrInterpolate(formatDate(__props.assignment.due_date))}</span>`);
        if (isOverdue.value) {
          _push(`<span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full ml-1">\u0E40\u0E01\u0E34\u0E19\u0E01\u0E33\u0E2B\u0E19\u0E14</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (__props.assignment.description) {
        _push(`<div class="mb-6">`);
        _push(ssrRenderComponent(__nuxt_component_0, {
          "model-value": __props.assignment.description,
          disabled: "",
          class: "!min-h-0"
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (!__props.isCourseAdmin && answerStatus.value === "graded" && hasAnswer.value) {
        _push(`<div class="mb-6 p-5 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-700/30"><div class="flex justify-between items-start mb-3"><h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-100 flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-feedback-24-regular",
          class: "w-5 h-5 text-emerald-600"
        }, null, _parent));
        _push(` \u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 (\u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27): </h3><button class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-emerald-100 transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: isEditingGraded.value ? "fluent:dismiss-16-regular" : "fluent:edit-16-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(isEditingGraded.value ? "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01" : "\u0E41\u0E01\u0E49\u0E44\u0E02")}</button></div>`);
        if (answerContent.value && !isEditingGraded.value) {
          _push(`<div class="text-sm text-gray-700 dark:text-gray-300 mb-4 leading-relaxed font-sans bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700">`);
          _push(ssrRenderComponent(__nuxt_component_1, { content: answerContent.value }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (answerImages.value.length && !isEditingGraded.value) {
          _push(`<div class="flex flex-wrap gap-3"><!--[-->`);
          ssrRenderList(answerImages.value, (img) => {
            _push(`<div class="group relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 shadow-sm hover:shadow-md transition-all"><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-full object-cover cursor-zoom-in group-hover:scale-110 transition-transform duration-500"></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        if (isEditingGraded.value) {
          _push(`<div class="mt-4">`);
          _push(ssrRenderComponent(_sfc_main$4, {
            assignment: __props.assignment,
            courseId: __props.courseId,
            "is-editing": true,
            "existing-answer": currentAnswer.value,
            "show-cancel": true,
            onSubmitted: handleGradedEditSubmit,
            onCancel: ($event) => isEditingGraded.value = false
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="pt-4 border-t border-gray-100 dark:border-gray-700">`);
      if (answerStatus.value || !__props.isCourseAdmin && hasAnswer.value) {
        _push(`<div class="flex items-center justify-between mb-4">`);
        if (answerStatus.value) {
          _push(`<div class="flex items-center gap-2"><span class="text-sm font-medium text-gray-500">\u0E2A\u0E16\u0E32\u0E19\u0E30:</span><span class="${ssrRenderClass([answerStatus.value === "submitted" || answerStatus.value === "graded" ? "bg-emerald-50 text-emerald-600 border-emerald-100" : "bg-orange-50 text-orange-500 border-orange-100", "px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-1.5"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: answerStatus.value === "submitted" || answerStatus.value === "graded" ? "fluent:checkmark-circle-16-filled" : "fluent:circle-16-regular"
          }, null, _parent));
          _push(` ${ssrInterpolate(answerStatus.value === "submitted" || answerStatus.value === "graded" ? "\u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E2A\u0E48\u0E07")}</span></div>`);
        } else {
          _push(`<div></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (!__props.isCourseAdmin && answerStatus.value === "graded" && hasAnswer.value) {
        _push(`<div class="mt-3"><div class="flex justify-between items-center mb-1.5"><span class="text-xs font-medium text-gray-500 dark:text-gray-400">\u0E04\u0E30\u0E41\u0E19\u0E19</span><span class="${ssrRenderClass([(((_a = currentAnswer.value) == null ? void 0 : _a.points) || 0) >= (__props.assignment.passing_score || 0) ? "text-emerald-600" : "text-red-500", "text-sm font-bold"])}">${ssrInterpolate(((_b = currentAnswer.value) == null ? void 0 : _b.points) || 0)} / ${ssrInterpolate(__props.assignment.points)} `);
        if (__props.assignment.passing_score) {
          _push(`<span class="ml-1 text-xs font-normal">`);
          if ((((_c = currentAnswer.value) == null ? void 0 : _c.points) || 0) >= __props.assignment.passing_score) {
            _push(`<span class="text-emerald-500">\u2713 \u0E1C\u0E48\u0E32\u0E19</span>`);
          } else {
            _push(`<span class="text-red-400">\u2717 \u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19</span>`);
          }
          _push(`</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</span></div><div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"><div class="${ssrRenderClass([(((_d = currentAnswer.value) == null ? void 0 : _d.points) || 0) >= (__props.assignment.passing_score || 0) ? "bg-gradient-to-r from-emerald-400 to-emerald-600" : "bg-gradient-to-r from-red-400 to-red-600", "h-full rounded-full transition-all duration-500"])}" style="${ssrRenderStyle({ width: `${(((_e = currentAnswer.value) == null ? void 0 : _e.points) || 0) / (__props.assignment.points || 1) * 100}%` })}"></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.isCourseAdmin) {
        _push(`<div class="mt-4">`);
        _push(ssrRenderComponent(_sfc_main$5, {
          assignment: __props.assignment,
          courseId: __props.courseId
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (!__props.isCourseAdmin && answerStatus.value !== "graded") {
        _push(`<div class="mt-4">`);
        _push(ssrRenderComponent(_sfc_main$4, {
          assignment: __props.assignment,
          courseId: __props.courseId,
          "is-editing": hasAnswer.value,
          "existing-answer": currentAnswer.value,
          "show-cancel": false,
          onSubmitted: ($event) => emit("refresh")
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></article>`);
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AssignmentCard.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "CourseAssignmentFormModal",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    courseId: {},
    assignment: {},
    availableGroups: {}
  },
  emits: ["close", "submit"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    useApi();
    const form = ref({
      title: "",
      description: "",
      points: 10,
      passing_score: 0,
      start_date: "",
      due_date: "",
      status: "1",
      // 1=Published, 0=Draft
      target_groups: [],
      images: [],
      existingImages: []
    });
    const isSubmitting = ref(false);
    const imagePreviews = ref([]);
    const getCurrentDateTime = () => {
      const now = /* @__PURE__ */ new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, "0");
      const day = String(now.getDate()).padStart(2, "0");
      const hours = String(now.getHours()).padStart(2, "0");
      const minutes = String(now.getMinutes()).padStart(2, "0");
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    const getTomorrowDateTime = () => {
      const tomorrow = /* @__PURE__ */ new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      const year = tomorrow.getFullYear();
      const month = String(tomorrow.getMonth() + 1).padStart(2, "0");
      const day = String(tomorrow.getDate()).padStart(2, "0");
      const hours = String(tomorrow.getHours()).padStart(2, "0");
      const minutes = String(tomorrow.getMinutes()).padStart(2, "0");
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    const resetForm = () => {
      form.value = {
        title: "",
        description: "",
        points: 10,
        passing_score: 5,
        start_date: getCurrentDateTime(),
        due_date: getTomorrowDateTime(),
        status: "1",
        target_groups: [],
        images: [],
        existingImages: []
      };
      imagePreviews.value = [];
    };
    watch(() => props.assignment, (newVal) => {
      var _a;
      if (newVal) {
        form.value = {
          title: newVal.title,
          description: newVal.description || "",
          points: newVal.points || 10,
          passing_score: newVal.passing_score || 0,
          start_date: newVal.start_date ? new Date(newVal.start_date) : "",
          due_date: newVal.due_date ? new Date(newVal.due_date) : "",
          status: String((_a = newVal.status) != null ? _a : "1"),
          target_groups: newVal.target_groups || [],
          images: [],
          existingImages: newVal.images || []
        };
      } else {
        resetForm();
      }
    }, { immediate: true });
    watch(() => form.value.points, (val) => {
      if (!props.assignment) {
        form.value.passing_score = Math.floor(val / 2);
      }
    });
    watch(() => props.show, (val) => {
      if (val && !props.assignment) {
        resetForm();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_VueDatePicker = resolveComponent("VueDatePicker");
      if (__props.show) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" }, _attrs))} data-v-a24a25fe><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" data-v-a24a25fe><div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 z-10" data-v-a24a25fe><h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-a24a25fe>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-edit-24-filled",
          class: "text-green-600"
        }, null, _parent));
        _push(` ${ssrInterpolate(__props.assignment ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E43\u0E2B\u0E21\u0E48")}</h3><button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors" data-v-a24a25fe>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-6 h-6 text-gray-500"
        }, null, _parent));
        _push(`</button></div><div class="p-6 space-y-6" data-v-a24a25fe><div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-v-a24a25fe><div class="col-span-1 md:col-span-2" data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14 <span class="text-red-500" data-v-a24a25fe>*</span></label><input${ssrRenderAttr("value", form.value.title)} type="text" class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all" placeholder="Ex. \u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E17\u0E1A\u0E17\u0E27\u0E19\u0E1A\u0E17\u0E17\u0E35\u0E48 1" data-v-a24a25fe></div><div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E2A\u0E16\u0E32\u0E19\u0E30 </label><select class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all appearance-none" data-v-a24a25fe><option value="1" data-v-a24a25fe${ssrIncludeBooleanAttr(Array.isArray(form.value.status) ? ssrLooseContain(form.value.status, "1") : ssrLooseEqual(form.value.status, "1")) ? " selected" : ""}>\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48 (Published)</option><option value="0" data-v-a24a25fe${ssrIncludeBooleanAttr(Array.isArray(form.value.status) ? ssrLooseContain(form.value.status, "0") : ssrLooseEqual(form.value.status, "0")) ? " selected" : ""}>\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07 (Draft)</option></select></div><div class="grid grid-cols-2 gap-4" data-v-a24a25fe><div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E04\u0E30\u0E41\u0E19\u0E19\u0E40\u0E15\u0E47\u0E21 </label><div class="relative" data-v-a24a25fe><input${ssrRenderAttr("value", form.value.points)} type="number" min="0" class="w-full px-4 py-2 pl-10 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all" data-v-a24a25fe>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-regular",
          class: "absolute left-3 top-2.5 w-5 h-5 text-gray-400"
        }, null, _parent));
        _push(`</div></div><div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E40\u0E01\u0E13\u0E11\u0E4C\u0E1C\u0E48\u0E32\u0E19 </label><div class="relative" data-v-a24a25fe><input${ssrRenderAttr("value", form.value.passing_score)} type="number" min="0"${ssrRenderAttr("max", form.value.points)} class="w-full px-4 py-2 pl-10 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all" data-v-a24a25fe>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-regular",
          class: "absolute left-3 top-2.5 w-5 h-5 text-gray-400"
        }, null, _parent));
        _push(`</div></div></div><div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 (Start Date) </label><div class="relative" data-v-a24a25fe>`);
        _push(ssrRenderComponent(_component_VueDatePicker, {
          modelValue: form.value.start_date,
          "onUpdate:modelValue": ($event) => form.value.start_date = $event,
          "auto-apply": "",
          "enable-time-picker": true,
          dark: true
        }, null, _parent));
        _push(`</div></div><div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E2A\u0E48\u0E07 (Due Date) </label>`);
        _push(ssrRenderComponent(_component_VueDatePicker, {
          modelValue: form.value.due_date,
          "onUpdate:modelValue": ($event) => form.value.due_date = $event,
          "auto-apply": "",
          "enable-time-picker": true,
          dark: true
        }, null, _parent));
        _push(`</div></div><div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 / \u0E42\u0E08\u0E17\u0E22\u0E4C </label>`);
        _push(ssrRenderComponent(__nuxt_component_0, {
          modelValue: form.value.description,
          "onUpdate:modelValue": ($event) => form.value.description = $event,
          placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E02\u0E2D\u0E07\u0E07\u0E32\u0E19...",
          class: "min-h-[200px]"
        }, null, _parent));
        _push(`</div>`);
        if (__props.availableGroups && __props.availableGroups.length > 0) {
          _push(`<div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3" data-v-a24a25fe> \u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22\u0E43\u0E2B\u0E49\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19 (Target Groups) </label><div class="flex flex-wrap gap-3" data-v-a24a25fe><!--[-->`);
          ssrRenderList(__props.availableGroups, (group) => {
            _push(`<button type="button" class="${ssrRenderClass([[
              form.value.target_groups.includes(group.id) ? "bg-green-50 border-green-500 text-green-700 dark:bg-green-900/30 dark:border-green-500 dark:text-green-400" : "bg-gray-50 border-gray-200 text-gray-600 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-500"
            ], "flex items-center gap-2 px-4 py-2 rounded-lg border transition-all duration-200"])}" data-v-a24a25fe><div class="${ssrRenderClass([[
              form.value.target_groups.includes(group.id) ? "bg-green-500 border-green-500" : "bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-500"
            ], "w-5 h-5 rounded border flex items-center justify-center transition-colors"])}" data-v-a24a25fe>`);
            if (form.value.target_groups.includes(group.id)) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-12-filled",
                class: "w-3 h-3 text-white"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`</div><span data-v-a24a25fe>${ssrInterpolate(group.name)}</span></button>`);
          });
          _push(`<!--]--></div><p class="text-xs text-gray-500 mt-2" data-v-a24a25fe>* \u0E2B\u0E32\u0E01\u0E44\u0E21\u0E48\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E14\u0E46 \u0E07\u0E32\u0E19\u0E08\u0E30\u0E16\u0E39\u0E01\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22\u0E43\u0E2B\u0E49\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E38\u0E01\u0E04\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div data-v-a24a25fe><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-a24a25fe> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A </label><div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4" data-v-a24a25fe><!--[-->`);
        ssrRenderList(form.value.existingImages, (img) => {
          _push(`<div class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700" data-v-a24a25fe><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-full object-cover" data-v-a24a25fe><button class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" data-v-a24a25fe>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-16-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button></div>`);
        });
        _push(`<!--]--><!--[-->`);
        ssrRenderList(imagePreviews.value, (preview, idx) => {
          _push(`<div class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700" data-v-a24a25fe><img${ssrRenderAttr("src", preview)} class="w-full h-full object-cover" data-v-a24a25fe><div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center" data-v-a24a25fe><button class="p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors" data-v-a24a25fe>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-20-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></div>`);
        });
        _push(`<!--]--><label class="aspect-video flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-green-500 hover:text-green-500 transition-colors bg-gray-50 dark:bg-gray-700/50 hover:bg-green-50 dark:hover:bg-green-900/10" data-v-a24a25fe>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:image-add-24-regular",
          class: "w-8 h-8 mb-2"
        }, null, _parent));
        _push(`<span class="text-xs font-medium" data-v-a24a25fe>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span><input type="file" multiple accept="image/*" class="hidden" data-v-a24a25fe></label></div></div></div><div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl sticky bottom-0 z-10" data-v-a24a25fe><button class="px-5 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors" data-v-a24a25fe> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 hover:shadow-green-500/40 hover:from-green-600 hover:to-emerald-700 active:scale-[0.98] transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" data-v-a24a25fe>`);
        if (isSubmitting.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:loading",
            class: "w-5 h-5"
          }, null, _parent));
        } else {
          _push(`<span data-v-a24a25fe>${ssrInterpolate(__props.assignment ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14")}</span>`);
        }
        _push(`</button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseAssignmentFormModal.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const CourseAssignmentFormModal = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-a24a25fe"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "AssignmentsList",
  __ssrInlineRender: true,
  props: {
    assignments: { default: () => [] },
    isCourseAdmin: { type: Boolean, default: false },
    courseId: {},
    availableGroups: { default: () => [] }
  },
  emits: ["refresh"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const api = useApi();
    const isDeleting = ref(false);
    const showModal = ref(false);
    const editingAssignment = ref(null);
    const openModal = (assignment = null) => {
      editingAssignment.value = assignment;
      showModal.value = true;
    };
    const handleModalSubmit = () => {
      emit("refresh");
      showModal.value = false;
    };
    const navigateToAssignment = (assignment) => {
      navigateTo(`/courses/${props.courseId}/assignments/${assignment.id}`);
    };
    const editAssignment = (assignment) => {
      openModal(assignment);
    };
    const deleteAssignment = async (assignmentId) => {
      var _a;
      if (!confirm("\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?")) return;
      isDeleting.value = true;
      try {
        const response = await api.delete(`/api/courses/${props.courseId}/assignments/${assignmentId}`);
        if (response) {
          emit("refresh");
        }
      } catch (err) {
        alert(((_a = err.data) == null ? void 0 : _a.msg) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49");
      } finally {
        isDeleting.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))}>`);
      if (__props.isCourseAdmin) {
        _push(`<div class="bg-gradient-to-r from-violet-600 via-indigo-600 to-cyan-600 dark:from-violet-900 dark:via-indigo-900 dark:to-cyan-900 rounded-2xl p-6 shadow-xl mb-6 text-white"><div class="flex items-center justify-between"><div class="flex items-center gap-4"><div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "material-symbols:assignment-outline",
          class: "w-7 h-7 text-white"
        }, null, _parent));
        _push(`</div><div><h2 class="text-2xl font-bold mb-1">\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</h2><p class="text-white/80 text-sm">${ssrInterpolate(__props.assignments.length)} \u0E07\u0E32\u0E19</p></div></div>`);
        if (__props.isCourseAdmin) {
          _push(`<button class="flex items-center gap-2 px-5 py-3 bg-white/10 text-white rounded-xl hover:bg-white/20 border border-white/20 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold backdrop-blur-sm">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:add-circle-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E07\u0E32\u0E19\u0E43\u0E2B\u0E21\u0E48</span></button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.assignments.length > 0) {
        _push(`<div class="space-y-3"><!--[-->`);
        ssrRenderList(__props.assignments, (assignment) => {
          _push(ssrRenderComponent(_sfc_main$3, {
            key: assignment.id,
            assignment,
            "course-id": __props.courseId,
            "is-course-admin": __props.isCourseAdmin,
            onClick: navigateToAssignment,
            onEdit: editAssignment,
            onDelete: deleteAssignment,
            onRefresh: ($event) => emit("refresh")
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center border border-gray-100 dark:border-gray-700"><div class="w-24 h-24 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-6">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-24-regular",
          class: "w-12 h-12 text-gray-400"
        }, null, _parent));
        _push(`</div><h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19</h3><p class="text-gray-500 dark:text-gray-400 mb-8 max-w-xs mx-auto">${ssrInterpolate(__props.isCourseAdmin ? "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19\u0E43\u0E2B\u0E49\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13" : "\u0E2D\u0E32\u0E08\u0E32\u0E23\u0E22\u0E4C\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22\u0E07\u0E32\u0E19\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49")}</p>`);
        if (__props.isCourseAdmin) {
          _push(`<button class="inline-flex items-center gap-2 px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition-all shadow-lg hover:shadow-orange-500/30 font-semibold">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:add-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19\u0E41\u0E23\u0E01 </button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      }
      _push(ssrRenderComponent(CourseAssignmentFormModal, {
        show: showModal.value,
        assignment: editingAssignment.value,
        "course-id": __props.courseId,
        "available-groups": __props.availableGroups,
        onClose: ($event) => showModal.value = false,
        onSubmit: handleModalSubmit
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/AssignmentsList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const api = useApi();
    const assignments = ref([]);
    const groups = ref([]);
    const isLoading = ref(true);
    const fetchAssignments = async () => {
      var _a;
      if (!((_a = course.value) == null ? void 0 : _a.id)) return;
      isLoading.value = true;
      try {
        const courseId = course.value.id;
        const response = await api.get(`/api/courses/${courseId}/assignments`);
        const data = response.assignments || [];
        assignments.value = Array.isArray(data) ? data : data.data || [];
        groups.value = response.groups || [];
      } catch (error) {
        console.error("Failed to load assignments:", error);
      } finally {
        isLoading.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if (unref(isLoading)) {
        _push(ssrRenderComponent(ContentLoader, null, null, _parent));
      } else {
        _push(ssrRenderComponent(_sfc_main$1, {
          assignments: unref(assignments),
          "available-groups": unref(groups),
          "course-id": (_a = unref(course)) == null ? void 0 : _a.id,
          "is-course-admin": unref(isCourseAdmin),
          onRefresh: fetchAssignments
        }, null, _parent));
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/assignments/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-Du-rFlyJ.mjs.map
