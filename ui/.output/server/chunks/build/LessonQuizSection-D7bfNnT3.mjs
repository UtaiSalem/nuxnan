import { defineComponent, ref, computed, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as __nuxt_component_1 } from './RichTextViewer-UnGuFLT8.mjs';
import { _ as __nuxt_component_0 } from './RichTextEditor-C7FYwlb0.mjs';
import { _ as _export_sfc, i as useApi } from './server.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import { I as ImageLightbox } from './ImageLightbox-D9vQ7Zkj.mjs';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "LessonAssignmentSection",
  __ssrInlineRender: true,
  props: {
    assignments: {},
    lessonId: {},
    courseId: {},
    isCreator: { type: Boolean }
  },
  emits: ["submit", "close", "edit", "delete", "view-submissions"],
  setup(__props, { emit: __emit }) {
    var _a;
    const props = __props;
    const api = useApi();
    const { getAvatarUrl } = useAvatar();
    const activeAssignmentId = ref(((_a = props.assignments[0]) == null ? void 0 : _a.id) || null);
    const isSubmitting = ref(false);
    const answerContent = ref("");
    const answerFiles = ref([]);
    const allAnswers = ref([]);
    const isFetchingAnswers = ref(false);
    const existingImages = ref([]);
    ref([]);
    const isEditing = ref(false);
    const userAvatar = (user) => getAvatarUrl(user);
    const activeAssignment = computed(() => {
      return props.assignments.find((a) => a.id === activeAssignmentId.value) || null;
    });
    const userAnswer = computed(() => {
      var _a2;
      if (!activeAssignment.value) return null;
      return ((_a2 = activeAssignment.value.answers) == null ? void 0 : _a2[0]) || null;
    });
    const assignmentStatus = computed(() => {
      var _a2, _b;
      if (!activeAssignment.value) return "not_started";
      if (((_a2 = userAnswer.value) == null ? void 0 : _a2.graded_score) !== void 0 && ((_b = userAnswer.value) == null ? void 0 : _b.graded_score) !== null) {
        return "graded";
      }
      if (userAnswer.value) {
        return "submitted";
      }
      return "not_started";
    });
    const statusConfig = computed(() => {
      const configs = {
        not_started: {
          icon: "fluent:circle-24-regular",
          text: "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E17\u0E33",
          color: "text-gray-700 dark:text-gray-300",
          bgColor: "bg-gray-50 dark:bg-gray-800 ring-1 ring-inset ring-gray-600/20 dark:ring-gray-500/30"
        },
        submitted: {
          icon: "fluent:checkmark-circle-24-filled",
          text: "\u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27 - \u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08",
          color: "text-blue-700 dark:text-blue-400",
          bgColor: "bg-blue-50 dark:bg-blue-900/30 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-500/30"
        },
        graded: {
          icon: "fluent:trophy-24-filled",
          text: "\u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27",
          color: "text-amber-700 dark:text-amber-400",
          bgColor: "bg-amber-50 dark:bg-amber-900/30 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/30"
        }
      };
      return configs[assignmentStatus.value] || configs.not_started;
    });
    const fetchAnswers = async () => {
      if (!activeAssignment.value || !props.isCreator) return;
      isFetchingAnswers.value = true;
      try {
        const response = await api.get(`/api/assignments/${activeAssignment.value.id}/answers`);
        allAnswers.value = (response.answers || []).map((a) => ({
          ...a,
          points: a.points,
          originalPoints: a.points,
          isUpdating: false
        }));
      } catch (error) {
        console.error("Failed to fetch answers:", error);
      } finally {
        isFetchingAnswers.value = false;
      }
    };
    watch(activeAssignmentId, () => {
      if (props.isCreator) {
        fetchAnswers();
      }
    }, { immediate: true });
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a2, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "mt-6 border-t border-gray-200 dark:border-gray-700 pt-6" }, _attrs))}><div class="flex items-center justify-between mb-6"><h3 class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clipboard-task-24-filled",
        class: "w-6 h-6 text-green-600"
      }, null, _parent));
      _push(` \u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 <span class="px-2 py-0.5 text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">${ssrInterpolate(__props.assignments.length)} \u0E02\u0E49\u0E2D </span></h3><button class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chevron-up-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button></div>`);
      if (__props.assignments.length > 1) {
        _push(`<div class="flex gap-2 mb-6 overflow-x-auto pb-2"><!--[-->`);
        ssrRenderList(__props.assignments, (assignment, index) => {
          _push(`<button class="${ssrRenderClass([[
            activeAssignmentId.value === assignment.id ? "bg-green-500 text-white shadow-lg" : "bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
          ], "flex-shrink-0 px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200"])}"> \u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48 ${ssrInterpolate(index + 1)}</button>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (activeAssignment.value) {
        _push(`<div class="space-y-6"><div class="p-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-800"><div class="flex items-start justify-between mb-4"><h4 class="text-lg font-bold text-gray-900 dark:text-white">${ssrInterpolate(activeAssignment.value.title)}</h4><div class="flex items-center gap-3">`);
        if (__props.isCreator) {
          _push(`<div class="flex items-center gap-1 bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm border border-gray-200 dark:border-gray-700"><button class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-md transition-colors" title="\u0E41\u0E01\u0E49\u0E44\u0E02">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-16-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button><div class="w-px h-4 bg-gray-200 dark:bg-gray-700"></div><button class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-md transition-colors" title="\u0E25\u0E1A">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-16-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button><div class="w-px h-4 bg-gray-200 dark:bg-gray-700"></div><button class="flex items-center gap-1 px-2 py-1 text-xs font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30 rounded-md transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clipboard-task-list-20-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`<span>\u0E15\u0E23\u0E27\u0E08\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14</span></button></div>`);
        } else {
          _push(`<span class="${ssrRenderClass([[statusConfig.value.bgColor, statusConfig.value.color], "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: statusConfig.value.icon,
            class: "w-4 h-4"
          }, null, _parent));
          _push(` ${ssrInterpolate(statusConfig.value.text)}</span>`);
        }
        _push(`</div></div>`);
        if (activeAssignment.value.description) {
          _push(`<div class="mb-4">`);
          _push(ssrRenderComponent(__nuxt_component_0, {
            "model-value": activeAssignment.value.description,
            disabled: "",
            class: "!min-h-0"
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400"><div class="flex items-center gap-1.5">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-regular",
          class: "w-4 h-4 text-amber-500"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(activeAssignment.value.points || 0)} \u0E04\u0E30\u0E41\u0E19\u0E19</span></div>`);
        if (activeAssignment.value.due_date) {
          _push(`<div class="flex items-center gap-1.5">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-24-regular",
            class: "w-4 h-4 text-blue-500"
          }, null, _parent));
          _push(`<span>\u0E01\u0E33\u0E2B\u0E19\u0E14\u0E2A\u0E48\u0E07: ${ssrInterpolate(formatDate(activeAssignment.value.due_date))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if ((_a2 = activeAssignment.value.images) == null ? void 0 : _a2.length) {
          _push(`<div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3"><!--[-->`);
          ssrRenderList(activeAssignment.value.images, (image) => {
            _push(`<img${ssrRenderAttr("src", image.full_url || image.image_url)}${ssrRenderAttr("alt", activeAssignment.value.title)} class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity">`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (__props.isCreator) {
          _push(`<div class="space-y-4"><h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-available-24-filled",
            class: "w-5 h-5 text-green-600"
          }, null, _parent));
          _push(` \u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19\u0E41\u0E25\u0E49\u0E27 (${ssrInterpolate(allAnswers.value.length)}) </h4>`);
          if (isFetchingAnswers.value) {
            _push(`<div class="py-8 flex justify-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "eos-icons:loading",
              class: "w-8 h-8 text-green-500"
            }, null, _parent));
            _push(`</div>`);
          } else if (allAnswers.value.length === 0) {
            _push(`<div class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700"><p class="text-gray-500">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E43\u0E04\u0E23\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19</p></div>`);
          } else {
            _push(`<div class="space-y-4"><!--[-->`);
            ssrRenderList(allAnswers.value, (answer) => {
              var _a3, _b2, _c, _d;
              _push(`<div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"><div class="flex items-start gap-3 mb-3"><img${ssrRenderAttr("src", userAvatar(answer.user))} class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600"><div class="flex-1 min-w-0"><div class="flex justify-between items-start"><div><div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">${ssrInterpolate((_a3 = answer.user) == null ? void 0 : _a3.firstname)} ${ssrInterpolate((_b2 = answer.user) == null ? void 0 : _b2.lastname)} `);
              if (answer.points === null || answer.points === void 0) {
                _push(`<span class="px-2 py-0.5 text-[10px] bg-red-100 text-red-600 rounded-full border border-red-200"> \u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08 </span>`);
              } else {
                _push(`<span class="px-2 py-0.5 text-[10px] bg-green-100 text-green-600 rounded-full border border-green-200"> \u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27 </span>`);
              }
              _push(`</div><div class="text-xs text-gray-500"> \u0E2A\u0E48\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(formatDate(answer.created_at))} `);
              if (answer.late_submission) {
                _push(`<span class="text-red-500 ml-1">(\u0E2A\u0E48\u0E07\u0E0A\u0E49\u0E32)</span>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div><div class="text-right flex flex-col items-end gap-1"><button class="text-gray-400 hover:text-red-500 transition-colors p-1" title="\u0E25\u0E1A\u0E04\u0E33\u0E15\u0E2D\u0E1A">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:delete-20-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push(`</button><div><span class="${ssrRenderClass([answer.points !== null ? "text-green-600 dark:text-green-400" : "text-gray-400", "text-2xl font-bold"])}">${ssrInterpolate((_c = answer.points) != null ? _c : "-")}</span><span class="text-xs text-gray-500">/ ${ssrInterpolate(activeAssignment.value.points)}</span></div></div></div><div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800">${ssrInterpolate(answer.content)} `);
              if ((_d = answer.images) == null ? void 0 : _d.length) {
                _push(`<div class="mt-2 text-xs text-blue-500 flex gap-1">`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:image-24-regular",
                  class: "w-4 h-4"
                }, null, _parent));
                _push(` \u0E21\u0E35\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E41\u0E19\u0E1A ${ssrInterpolate(answer.images.length)} \u0E23\u0E39\u0E1B </div>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div></div><div class="mt-2 pt-3 border-t border-gray-100 dark:border-gray-700"><div class="flex items-center gap-4"><span class="text-xs font-medium text-gray-500">\u0E43\u0E2B\u0E49\u0E04\u0E30\u0E41\u0E19\u0E19:</span><input type="range"${ssrRenderAttr("value", answer.points)}${ssrRenderAttr("min", 0)}${ssrRenderAttr("max", activeAssignment.value.points)} step="1" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-green-500"><button${ssrIncludeBooleanAttr(answer.isUpdating || answer.points === answer.originalPoints) ? " disabled" : ""} class="p-1 px-3 text-xs font-bold text-white bg-green-500 rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1" title="\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E04\u0E30\u0E41\u0E19\u0E19">`);
              if (answer.isUpdating) {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "eos-icons:loading",
                  class: "w-4 h-4 animate-spin"
                }, null, _parent));
              } else {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:save-24-regular",
                  class: "w-4 h-4"
                }, null, _parent));
              }
              _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button>`);
              if (answer.points !== answer.originalPoints) {
                _push(`<button${ssrIncludeBooleanAttr(answer.isUpdating) ? " disabled" : ""} class="p-1 px-3 text-xs font-bold text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors disabled:opacity-50 flex items-center gap-1" title="\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01">`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:dismiss-24-regular",
                  class: "w-4 h-4"
                }, null, _parent));
                _push(` \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div></div>`);
            });
            _push(`<!--]--></div>`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (userAnswer.value && !isEditing.value && !__props.isCreator) {
          _push(`<div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800"><div class="flex justify-between items-start mb-2"><h5 class="font-semibold text-blue-800 dark:text-blue-300 flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:document-text-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 </h5><div class="flex items-center gap-2">`);
          if (userAnswer.value.graded_score === null || userAnswer.value.graded_score === void 0) {
            _push(`<span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg flex items-center gap-1">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:clock-24-regular",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` \u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27 - \u0E23\u0E2D\u0E15\u0E23\u0E27\u0E08 </span>`);
          } else {
            _push(`<span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-lg flex items-center gap-1">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:checkmark-circle-24-filled",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(` \u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27 </span>`);
          }
          if (assignmentStatus.value !== "graded") {
            _push(`<button class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 ml-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-16-filled",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div><div class="text-gray-700 dark:text-gray-300 mt-2">`);
          _push(ssrRenderComponent(__nuxt_component_1, {
            content: userAnswer.value.content
          }, null, _parent));
          _push(`</div>`);
          if ((_b = userAnswer.value.images) == null ? void 0 : _b.length) {
            _push(`<div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2"><!--[-->`);
            ssrRenderList(userAnswer.value.images, (img) => {
              _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-24 object-cover rounded-lg border border-blue-100 dark:border-blue-800">`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          if (userAnswer.value.graded_score !== void 0 && userAnswer.value.graded_score !== null) {
            _push(`<div class="mt-4 p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg"><div class="flex items-center gap-2 text-amber-700 dark:text-amber-400 font-bold">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:trophy-24-filled",
              class: "w-5 h-5"
            }, null, _parent));
            _push(` \u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E44\u0E14\u0E49: ${ssrInterpolate(userAnswer.value.graded_score)} / ${ssrInterpolate(activeAssignment.value.points || 100)}</div>`);
            if (userAnswer.value.feedback) {
              _push(`<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">${ssrInterpolate(userAnswer.value.feedback)}</p>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (assignmentStatus.value !== "graded" && !__props.isCreator && !userAnswer.value || isEditing.value) {
          _push(`<div id="answer-form" class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg"><div class="flex justify-between items-center mb-4"><h5 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5 text-green-600"
          }, null, _parent));
          _push(` ${ssrInterpolate(isEditing.value ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E15\u0E2D\u0E1A" : "\u0E40\u0E02\u0E35\u0E22\u0E19\u0E04\u0E33\u0E15\u0E2D\u0E1A")}</h5>`);
          if (isEditing.value) {
            _push(`<button class="text-gray-500 hover:text-gray-700 text-sm font-medium"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          _push(ssrRenderComponent(__nuxt_component_0, {
            modelValue: answerContent.value,
            "onUpdate:modelValue": ($event) => answerContent.value = $event,
            placeholder: "\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48...",
            class: "min-h-[120px]"
          }, null, _parent));
          _push(`<div class="mt-4"><label class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-green-500 hover:text-green-500 cursor-pointer transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:attach-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`<span>\u0E41\u0E19\u0E1A\u0E44\u0E1F\u0E25\u0E4C / \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span><input type="file" multiple accept="image/*,.pdf,.doc,.docx" class="hidden"></label>`);
          if (answerFiles.value.length) {
            _push(`<div class="mt-3 flex flex-wrap gap-2"><!--[-->`);
            ssrRenderList(answerFiles.value, (file, index) => {
              _push(`<div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:document-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push(`<span class="truncate max-w-32">${ssrInterpolate(file.name)}</span><button class="text-red-500 hover:text-red-700">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-12-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push(`</button></div>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          if (existingImages.value.length > 0) {
            _push(`<div class="mt-3"><p class="text-xs text-gray-500 mb-2">\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E40\u0E14\u0E34\u0E21:</p><div class="flex flex-wrap gap-2"><!--[-->`);
            ssrRenderList(existingImages.value, (img) => {
              _push(`<div class="relative group w-24 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-full object-cover"><button class="absolute top-1 right-1 p-1 bg-red-500/90 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity" title="\u0E25\u0E1A\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:delete-16-regular",
                class: "w-3 h-3"
              }, null, _parent));
              _push(`</button></div>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><button${ssrIncludeBooleanAttr(isSubmitting.value || !answerContent.value.trim() && answerFiles.value.length === 0) ? " disabled" : ""} class="mt-4 w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: isSubmitting.value ? "fluent:spinner-ios-20-regular" : "fluent:send-24-filled",
            class: ["w-5 h-5", isSubmitting.value && "animate-spin"]
          }, null, _parent));
          _push(` ${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : isEditing.value ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02" : "\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E15\u0E2D\u0E1A")}</button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<div class="text-center py-8 text-gray-500 dark:text-gray-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-24-regular",
          class: "w-12 h-12 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E43\u0E19\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49</p></div>`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonAssignmentSection.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "LessonQuizSection",
  __ssrInlineRender: true,
  props: {
    questions: {},
    lessonId: {},
    isCreator: { type: Boolean }
  },
  emits: ["update:questions", "create", "edit", "delete"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    useApi();
    const hasQuestions = computed(() => props.questions && props.questions.length > 0);
    const localQuestions = ref([]);
    const selectedAnswers = ref({});
    const answerResults = ref({});
    const submitting = ref({});
    watch(() => props.questions, (newVal) => {
      if (newVal) {
        localQuestions.value = JSON.parse(JSON.stringify(newVal));
      }
    }, { immediate: true, deep: true });
    const progressPercentage = computed(() => {
      if (!props.questions || props.questions.length === 0) return 0;
      const answeredIds = new Set(Object.keys(answerResults.value).map(Number));
      if (props.questions) {
        props.questions.forEach((q) => {
          if (q.user_answer) answeredIds.add(q.id);
        });
      }
      const answeredCount = answeredIds.size;
      return Math.round(answeredCount / props.questions.length * 100);
    });
    const showLightbox = ref(false);
    const lightboxImages = ref([]);
    const lightboxIndex = ref(0);
    const closeLightbox = () => {
      showLightbox.value = false;
      lightboxImages.value = [];
      lightboxIndex.value = 0;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "mt-6 border-t border-gray-200 dark:border-gray-700 pt-6" }, _attrs))} data-v-dd10a6ba><div class="flex flex-col gap-4 mb-6" data-v-dd10a6ba><div class="flex items-center justify-between" data-v-dd10a6ba><h3 class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white" data-v-dd10a6ba>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:quiz-new-24-filled",
        class: "w-6 h-6 text-orange-600"
      }, null, _parent));
      _push(` \u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A <span class="px-2 py-0.5 text-sm font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full" data-v-dd10a6ba>${ssrInterpolate(__props.questions.length)} \u0E02\u0E49\u0E2D </span></h3>`);
      if (__props.isCreator) {
        _push(`<button class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm font-medium" data-v-dd10a6ba>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21 </button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (!__props.isCreator && hasQuestions.value) {
        _push(`<div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden" data-v-dd10a6ba><div class="bg-orange-600 h-2.5 rounded-full transition-all duration-500 ease-out" style="${ssrRenderStyle({ width: `${progressPercentage.value}%` })}" data-v-dd10a6ba></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (!__props.isCreator && hasQuestions.value) {
        _push(`<div class="text-right text-sm text-gray-500 dark:text-gray-400" data-v-dd10a6ba> \u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32: ${ssrInterpolate(progressPercentage.value)}% </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (!__props.isCreator && hasQuestions.value) {
        _push(`<div class="space-y-8" data-v-dd10a6ba><!--[-->`);
        ssrRenderList(localQuestions.value, (question, index) => {
          var _a, _b;
          _push(`<div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition-all hover:shadow-md" data-v-dd10a6ba><div class="flex items-start gap-5" data-v-dd10a6ba><div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg font-bold text-lg" data-v-dd10a6ba>${ssrInterpolate(index + 1)}</div><div class="flex-1 w-full min-w-0" data-v-dd10a6ba><p class="font-medium text-lg text-gray-900 dark:text-white mb-4 leading-relaxed whitespace-pre-wrap" data-v-dd10a6ba>${ssrInterpolate(question.text)}</p>`);
          if ((_a = question.images) == null ? void 0 : _a.length) {
            _push(`<div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide" data-v-dd10a6ba><!--[-->`);
            ssrRenderList(question.images, (img, imgIndex) => {
              _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="h-40 w-auto rounded-xl object-cover border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-95 transition-opacity shadow-sm" data-v-dd10a6ba>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="space-y-3 mt-2" data-v-dd10a6ba><div${ssrRenderAttrs({
            name: "list",
            class: "space-y-3"
          })} data-v-dd10a6ba>`);
          ssrRenderList(question.options, (option) => {
            var _a2, _b2;
            _push(`<button${ssrIncludeBooleanAttr(submitting.value[question.id] || ((_a2 = answerResults.value[question.id]) == null ? void 0 : _a2.is_correct)) ? " disabled" : ""} class="${ssrRenderClass([[
              selectedAnswers.value[question.id] === option.id ? "border-orange-500 bg-orange-50 dark:bg-orange-900/10" : "border-gray-200 dark:border-gray-700 hover:border-orange-200 dark:hover:border-orange-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
            ], "w-full flex items-center gap-4 p-4 rounded-xl border-2 transition-all text-left group relative overflow-hidden"])}" data-v-dd10a6ba><div class="${ssrRenderClass([[
              selectedAnswers.value[question.id] === option.id ? "border-orange-500 bg-orange-500 text-white" : "border-gray-300 dark:border-gray-600 group-hover:border-orange-400"
            ], "w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-colors"])}" data-v-dd10a6ba>`);
            if (selectedAnswers.value[question.id] === option.id) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-16-filled",
                class: "w-4 h-4"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="flex-1" data-v-dd10a6ba><span class="text-gray-900 dark:text-gray-200 text-base" data-v-dd10a6ba>${ssrInterpolate(option.text)}</span>`);
            if ((_b2 = option.images) == null ? void 0 : _b2.length) {
              _push(`<div class="mt-3" data-v-dd10a6ba><img${ssrRenderAttr("src", option.images[0].full_url || option.images[0].image_url)} class="h-32 w-auto rounded-lg object-cover cursor-pointer hover:opacity-95 shadow-sm border border-gray-200 dark:border-gray-700" data-v-dd10a6ba></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></button>`);
          });
          _push(`</div></div><div class="mt-6 flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700/50" data-v-dd10a6ba><div class="flex-1 min-h-[40px] flex items-center" data-v-dd10a6ba>`);
          if (answerResults.value[question.id]) {
            _push(`<div class="${ssrRenderClass([answerResults.value[question.id].is_correct ? "bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400" : "bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400", "flex items-center gap-3 font-medium px-4 py-2 rounded-lg"])}" data-v-dd10a6ba>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: answerResults.value[question.id].is_correct ? "fluent:checkmark-circle-24-filled" : "fluent:dismiss-circle-24-filled",
              class: "w-6 h-6"
            }, null, _parent));
            _push(`<span data-v-dd10a6ba>${ssrInterpolate(answerResults.value[question.id].message)}</span>`);
            if (answerResults.value[question.id].is_correct) {
              _push(`<span class="text-sm opacity-80 ml-1" data-v-dd10a6ba> (+${ssrInterpolate(answerResults.value[question.id].points)} \u0E04\u0E30\u0E41\u0E19\u0E19) </span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          if (!((_b = answerResults.value[question.id]) == null ? void 0 : _b.is_correct)) {
            _push(`<button${ssrIncludeBooleanAttr(!selectedAnswers.value[question.id] || submitting.value[question.id]) ? " disabled" : ""} class="px-8 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-medium rounded-full shadow-lg shadow-orange-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2 transform active:scale-95" data-v-dd10a6ba>`);
            if (submitting.value[question.id]) {
              _push(`<span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" data-v-dd10a6ba></span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`<span data-v-dd10a6ba>${ssrInterpolate(submitting.value[question.id] ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2A\u0E48\u0E07..." : "\u0E15\u0E23\u0E27\u0E08\u0E04\u0E33\u0E15\u0E2D\u0E1A")}</span>`);
            if (!submitting.value[question.id]) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:send-24-filled",
                class: "w-5 h-5"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`</button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.isCreator) {
        _push(`<div class="space-y-4" data-v-dd10a6ba>`);
        if (!hasQuestions.value) {
          _push(`<div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl border-dashed border-2 border-gray-200 dark:border-gray-700" data-v-dd10a6ba><div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4" data-v-dd10a6ba>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:question-circle-24-regular",
            class: "w-8 h-8 text-orange-500"
          }, null, _parent));
          _push(`</div><p class="text-gray-500 dark:text-gray-400" data-v-dd10a6ba>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E16\u0E32\u0E21</p><button class="mt-4 text-orange-600 hover:text-orange-700 font-medium" data-v-dd10a6ba> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E41\u0E23\u0E01 </button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--[-->`);
        ssrRenderList(__props.questions, (question, index) => {
          var _a;
          _push(`<div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm relative group" data-v-dd10a6ba><div class="flex items-start gap-4" data-v-dd10a6ba><div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg font-bold" data-v-dd10a6ba>${ssrInterpolate(index + 1)}</div><div class="flex-1 min-w-0" data-v-dd10a6ba><p class="font-medium text-gray-900 dark:text-white mb-2" data-v-dd10a6ba>${ssrInterpolate(question.text)}</p>`);
          if ((_a = question.images) == null ? void 0 : _a.length) {
            _push(`<div class="flex gap-2 overflow-x-auto pb-2" data-v-dd10a6ba><!--[-->`);
            ssrRenderList(question.images, (img, imgIndex) => {
              _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="h-16 w-auto rounded-lg object-cover border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-90 transition-opacity" data-v-dd10a6ba>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="mt-3 space-y-1" data-v-dd10a6ba><!--[-->`);
          ssrRenderList(question.options, (option) => {
            var _a2;
            _push(`<div class="${ssrRenderClass([option.is_correct ? "text-green-600 dark:text-green-400 font-medium" : "text-gray-500 dark:text-gray-400", "flex items-center gap-2 text-sm"])}" data-v-dd10a6ba>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: option.is_correct ? "fluent:checkmark-circle-24-filled" : "fluent:circle-24-regular",
              class: "w-4 h-4 flex-shrink-0"
            }, null, _parent));
            if ((_a2 = option.images) == null ? void 0 : _a2.length) {
              _push(`<img${ssrRenderAttr("src", option.images[0].full_url || option.images[0].image_url)} class="h-10 w-auto rounded border border-gray-200 dark:border-gray-700 object-cover cursor-pointer hover:opacity-90 transition-opacity" data-v-dd10a6ba>`);
            } else {
              _push(`<!---->`);
            }
            if (option.text) {
              _push(`<span data-v-dd10a6ba>${ssrInterpolate(option.text)}</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          });
          _push(`<!--]--></div></div><div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" data-v-dd10a6ba><button class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg" title="\u0E41\u0E01\u0E49\u0E44\u0E02" data-v-dd10a6ba>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-20-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button><button class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg" title="\u0E25\u0E1A" data-v-dd10a6ba>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-20-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(`</button></div></div><div class="absolute top-4 right-4 text-xs font-medium text-gray-400" data-v-dd10a6ba>${ssrInterpolate(question.points)} \u0E04\u0E30\u0E41\u0E19\u0E19 </div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(ImageLightbox, {
        show: showLightbox.value,
        images: lightboxImages.value,
        "initial-index": lightboxIndex.value,
        onClose: closeLightbox
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonQuizSection.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const QuestionsListViewer = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-dd10a6ba"]]);

export { QuestionsListViewer as Q, _sfc_main$1 as _ };
//# sourceMappingURL=LessonQuizSection-D7bfNnT3.mjs.map
