import { _ as __nuxt_component_0 } from './RichTextEditor-DEEazQRP.mjs';
import { _ as __nuxt_component_1 } from './RichTextViewer-UnGuFLT8.mjs';
import { defineComponent, ref, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './AssignmentGradingView-CCvO8j6N.mjs';
import { _ as _sfc_main$2 } from './AssignmentSubmissionForm-BYC_TqBC.mjs';
import { C as ContentLoader } from './ContentLoader-D4cV05oG.mjs';
import { p as useRoute, i as useApi } from './server.mjs';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
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
import 'sweetalert2';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[assignmentId]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const api = useApi();
    const swal = useSweetAlert();
    useAvatar();
    const courseId = route.params.id;
    const assignmentId = route.params.assignmentId;
    const isLoading = ref(true);
    const assignment = ref(null);
    const course = ref(null);
    const groups = ref([]);
    const isCourseAdmin = ref(false);
    const answerContent = ref("");
    const answerFiles = ref([]);
    const existingImages = ref([]);
    const deletedImageIds = ref([]);
    ref(false);
    const isEditing = ref(false);
    const fetchAssignment = async () => {
      isLoading.value = true;
      try {
        const response = await api.get(`/api/courses/${courseId}/assignments/${assignmentId}`);
        assignment.value = response.assignment;
        course.value = response.course;
        groups.value = response.groups || [];
        isCourseAdmin.value = response.isCourseAdmin;
      } catch (error) {
        console.error("Failed to fetch assignment:", error);
        swal.toast("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19\u0E44\u0E14\u0E49", "error");
      } finally {
        isLoading.value = false;
      }
    };
    const userAnswer = computed(() => {
      var _a, _b;
      if (isCourseAdmin.value) return null;
      return ((_b = (_a = assignment.value) == null ? void 0 : _a.answers) == null ? void 0 : _b[0]) || null;
    });
    const assignmentStatus = computed(() => {
      if (!userAnswer.value) return "not_started";
      if (userAnswer.value.graded_score !== null) return "graded";
      return "submitted";
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
    const cancelEdit = () => {
      isEditing.value = false;
      answerContent.value = "";
      answerFiles.value = [];
      existingImages.value = [];
      deletedImageIds.value = [];
    };
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleString("th-TH", {
        dateStyle: "medium",
        timeStyle: "short"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      const _component_RichTextEditor = __nuxt_component_0;
      const _component_RichTextViewer = __nuxt_component_1;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "max-w-5xl mx-auto px-4 py-8" }, _attrs))}>`);
      if (isLoading.value) {
        _push(ssrRenderComponent(ContentLoader, null, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (!isLoading.value) {
        _push(`<button class="mb-6 flex items-center gap-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-left-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>\u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19</span></button>`);
      } else {
        _push(`<!---->`);
      }
      if (!isLoading.value && assignment.value) {
        _push(`<div class="space-y-8"><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"><div class="flex items-start justify-between mb-4"><div class="flex items-center gap-3"><div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</div><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(assignment.value.title)}</h1><div class="flex items-center gap-3 text-sm text-gray-500 mt-1">`);
        if (assignment.value.points) {
          _push(`<span class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:star-20-filled",
            class: "w-4 h-4 text-amber-500"
          }, null, _parent));
          _push(` ${ssrInterpolate(assignment.value.points)} \u0E04\u0E30\u0E41\u0E19\u0E19 </span>`);
        } else {
          _push(`<!---->`);
        }
        if (assignment.value.due_date) {
          _push(`<span class="flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:calendar-ltr-20-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E2A\u0E48\u0E07: ${ssrInterpolate(formatDate(assignment.value.due_date))}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
        if (!isCourseAdmin.value) {
          _push(`<div><div class="${ssrRenderClass([statusConfig.value.bgColor + " " + statusConfig.value.color, "inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold shadow-sm"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: statusConfig.value.icon,
            class: "w-5 h-5"
          }, null, _parent));
          _push(` ${ssrInterpolate(statusConfig.value.text)}</div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (assignment.value.description) {
          _push(`<div class="mt-6">`);
          _push(ssrRenderComponent(_component_RichTextEditor, {
            "model-value": assignment.value.description,
            disabled: "",
            class: "!min-h-0"
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if ((_a = assignment.value.images) == null ? void 0 : _a.length) {
          _push(`<div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4"><!--[-->`);
          ssrRenderList(assignment.value.images, (img) => {
            _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-40 object-cover rounded-xl shadow-sm hover:opacity-95 transition-opacity cursor-pointer border border-gray-100 dark:border-gray-700">`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (isCourseAdmin.value) {
          _push(`<div class="mt-8">`);
          if (assignment.value) {
            _push(ssrRenderComponent(_sfc_main$1, {
              assignment: assignment.value,
              courseId: unref(courseId)
            }, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<div class="space-y-6">`);
          if (userAnswer.value && !isEditing.value) {
            _push(`<div class="bg-blue-50 dark:bg-blue-900/10 rounded-2xl p-6 border border-blue-100 dark:border-blue-800"><div class="flex justify-between items-start mb-4"><h3 class="font-bold text-blue-900 dark:text-blue-300 flex items-center gap-2">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:document-checkmark-24-filled",
              class: "w-6 h-6"
            }, null, _parent));
            _push(` \u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 </h3>`);
            if (assignmentStatus.value !== "graded") {
              _push(`<button class="text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:edit-16-filled",
                class: "w-4 h-4"
              }, null, _parent));
              _push(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-blue-100 dark:border-blue-800/50 shadow-sm text-gray-800 dark:text-gray-200">`);
            _push(ssrRenderComponent(_component_RichTextViewer, {
              content: userAnswer.value.content
            }, null, _parent));
            _push(`</div>`);
            if ((_b = userAnswer.value.images) == null ? void 0 : _b.length) {
              _push(`<div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3"><!--[-->`);
              ssrRenderList(userAnswer.value.images, (img) => {
                _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-32 object-cover rounded-lg border border-blue-100 dark:border-blue-800">`);
              });
              _push(`<!--]--></div>`);
            } else {
              _push(`<!---->`);
            }
            if (userAnswer.value.graded_score !== null) {
              _push(`<div class="mt-4 bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl border border-amber-100 dark:border-amber-800/50 flex items-center gap-3"><div class="p-2 bg-amber-100 dark:bg-amber-800/50 rounded-full text-amber-600 dark:text-amber-400">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:trophy-24-filled",
                class: "w-6 h-6"
              }, null, _parent));
              _push(`</div><div><div class="font-bold text-amber-900 dark:text-amber-300">\u0E40\u0E02\u0E49\u0E32\u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27</div><div class="text-amber-700 dark:text-amber-400">\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E04\u0E30\u0E41\u0E19\u0E19 ${ssrInterpolate(userAnswer.value.graded_score)} / ${ssrInterpolate(assignment.value.points)}</div></div></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          if (!userAnswer.value || isEditing.value) {
            _push(`<div>`);
            _push(ssrRenderComponent(_sfc_main$2, {
              assignment: assignment.value,
              courseId: unref(courseId),
              existingAnswer: isEditing.value ? userAnswer.value : null,
              isEditing: isEditing.value,
              onSubmitted: fetchAssignment,
              onCancel: cancelEdit
            }, null, _parent));
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        }
        _push(`</div>`);
      } else if (!isLoading.value) {
        _push(`<div class="text-center py-20 text-gray-500"><div class="text-6xl mb-4">\u{1F615}</div><h2 class="text-xl font-bold">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E20\u0E32\u0E23\u0E30\u0E07\u0E32\u0E19</h2></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/assignments/[assignmentId].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_assignmentId_-BTwpygLv.mjs.map
