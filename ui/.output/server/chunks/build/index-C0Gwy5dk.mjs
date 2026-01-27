import { defineComponent, inject, ref, withAsyncContext, computed, mergeProps, unref, withCtx, createVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { a as _sfc_main$1, _ as _sfc_main$2 } from './QuizDuplicateModal-BCiaovrs.mjs';
import { p as useRoute, i as useApi } from './server.mjs';
import { u as useAsyncData } from './asyncData-BSaJWK3Z.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const route = useRoute();
    const courseId = route.params.id;
    const quizId = route.params.quizId;
    const api = useApi();
    const isCourseAdmin = inject("isCourseAdmin");
    const showDuplicateModal = ref(false);
    const handleDuplicated = (newQuiz) => {
      if (newQuiz && newQuiz.course_id === Number(courseId)) {
        refresh();
      }
    };
    const { data: quiz, refresh, pending } = ([__temp, __restore] = withAsyncContext(async () => useAsyncData(
      `course-quiz-${quizId}`,
      async () => {
        const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`);
        return res.quiz;
      }
    )), __temp = await __temp, __restore(), __temp);
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const getStatusBadge = computed(() => {
      if (!quiz.value) return {};
      if (quiz.value.is_active) {
        return { text: "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48\u0E41\u0E25\u0E49\u0E27", class: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400" };
      }
      return { text: "\u0E09\u0E1A\u0E31\u0E1A\u0E23\u0E48\u0E32\u0E07", class: "bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400" };
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "container mx-auto px-4 py-6 max-w-4xl" }, _attrs))}>`);
      if (unref(pending)) {
        _push(`<div class="flex justify-center p-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:3-dots-fade",
          class: "w-10 h-10 text-gray-400"
        }, null, _parent));
        _push(`</div>`);
      } else if (unref(quiz)) {
        _push(`<div><div class="flex items-center justify-between mb-6"><button class="flex items-center gap-2 text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-left-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>\u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</span></button>`);
        if (unref(isCourseAdmin)) {
          _push(`<div class="flex items-center gap-2"><button class="px-4 py-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 dark:bg-purple-900/20 dark:text-purple-400 dark:hover:bg-purple-900/30 transition-colors flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:copy-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01 </button><button class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button><button class="px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E25\u0E1A </button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"><div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-8 text-white"><div class="flex items-start gap-4"><div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:quiz-new-24-filled",
          class: "w-8 h-8 text-white"
        }, null, _parent));
        _push(`</div><div><div class="flex items-center gap-2 mb-2"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-white/20 text-white border border-white/20">${ssrInterpolate(unref(getStatusBadge).text)}</span>`);
        if (unref(quiz).time_limit) {
          _push(`<span class="flex items-center gap-1 text-xs bg-black/20 px-2 py-0.5 rounded-full">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:timer-24-filled",
            class: "w-3 h-3"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(quiz).time_limit)} \u0E19\u0E32\u0E17\u0E35 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><h1 class="text-3xl font-bold mb-2">${ssrInterpolate(unref(quiz).title)}</h1><p class="text-purple-100 text-lg opacity-90">${ssrInterpolate(unref(quiz).description || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22")}</p></div></div></div><div class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700 border-b border-gray-200 dark:border-gray-700"><div class="p-6 text-center"><div class="text-sm text-gray-500 mb-1">\u0E08\u0E33\u0E19\u0E27\u0E19\u0E02\u0E49\u0E2D</div><div class="text-2xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(((_a = unref(quiz).questions) == null ? void 0 : _a.length) || 0)}</div></div><div class="p-6 text-center"><div class="text-sm text-gray-500 mb-1">\u0E04\u0E30\u0E41\u0E19\u0E19\u0E40\u0E15\u0E47\u0E21</div><div class="text-2xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(quiz).total_score || 0)}</div></div><div class="p-6 text-center"><div class="text-sm text-gray-500 mb-1">\u0E40\u0E01\u0E13\u0E11\u0E4C\u0E1C\u0E48\u0E32\u0E19</div><div class="text-2xl font-bold text-gray-900 dark:text-white">${ssrInterpolate(unref(quiz).passing_score)}% </div></div></div><div class="p-6 bg-gray-50 dark:bg-gray-700/30 border-b border-gray-200 dark:border-gray-700"><div class="grid md:grid-cols-2 gap-4 text-sm"><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-ltr-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</div><div><p class="text-gray-500">\u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33\u0E44\u0E14\u0E49\u0E15\u0E31\u0E49\u0E07\u0E41\u0E15\u0E48</p><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(formatDate(unref(quiz).start_date))}</p></div></div><div class="flex items-center gap-3"><div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:calendar-end-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</div><div><p class="text-gray-500">\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14\u0E40\u0E21\u0E37\u0E48\u0E2D</p><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(formatDate(unref(quiz).end_date))}</p></div></div></div></div><div class="p-8 text-center">`);
        if (!unref(isCourseAdmin)) {
          _push(`<div>`);
          if (unref(quiz).current_result && unref(quiz).current_result.completed_at) {
            _push(`<div class="bg-gray-100 dark:bg-gray-700/50 rounded-xl p-6 mb-6"><h3 class="text-lg font-semibold mb-4 text-center">\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13</h3><div class="flex justify-center gap-12 text-center py-4"><div class="flex flex-col items-center">`);
            _push(ssrRenderComponent(_sfc_main$1, {
              percentage: unref(quiz).current_result.score / unref(quiz).total_score * 100,
              color: unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "text-orange-500" : "text-green-600" : "text-red-500",
              trackColor: "text-gray-100 dark:text-gray-700",
              size: 100,
              strokeWidth: 8
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<div class="flex flex-col items-center mt-1"${_scopeId}><span class="${ssrRenderClass([unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "text-orange-600" : "text-green-600" : "text-red-600", "text-2xl font-bold"])}"${_scopeId}>${ssrInterpolate(unref(quiz).current_result.score)}</span><span class="text-xs text-gray-400 font-medium"${_scopeId}>/ ${ssrInterpolate(unref(quiz).total_score)}</span></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex flex-col items-center mt-1" }, [
                      createVNode("span", {
                        class: ["text-2xl font-bold", unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "text-orange-600" : "text-green-600" : "text-red-600"]
                      }, toDisplayString(unref(quiz).current_result.score), 3),
                      createVNode("span", { class: "text-xs text-gray-400 font-medium" }, "/ " + toDisplayString(unref(quiz).total_score), 1)
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(`<div class="text-sm font-bold mt-3 text-gray-500">\u0E04\u0E30\u0E41\u0E19\u0E19</div></div><div class="flex flex-col items-center">`);
            _push(ssrRenderComponent(_sfc_main$1, {
              percentage: parseFloat(unref(quiz).current_result.percentage),
              color: unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "text-orange-500" : "text-green-600" : "text-red-500",
              trackColor: "text-gray-100 dark:text-gray-700",
              size: 100,
              strokeWidth: 8
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`<span class="${ssrRenderClass([unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "text-orange-600" : "text-green-600" : "text-red-600", "text-xl font-bold"])}"${_scopeId}>${ssrInterpolate(parseFloat(unref(quiz).current_result.percentage).toFixed(0))}% </span>`);
                } else {
                  return [
                    createVNode("span", {
                      class: ["text-xl font-bold", unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "text-orange-600" : "text-green-600" : "text-red-600"]
                    }, toDisplayString(parseFloat(unref(quiz).current_result.percentage).toFixed(0)) + "% ", 3)
                  ];
                }
              }),
              _: 1
            }, _parent));
            _push(`<div class="text-sm font-bold mt-3 text-gray-500">\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C</div></div></div><div class="mt-4 text-center"><span class="${ssrRenderClass([unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "bg-orange-100 text-orange-700" : "bg-green-100 text-green-700" : "bg-red-100 text-red-700", "px-3 py-1 rounded-full text-sm font-bold"])}">${ssrInterpolate(unref(quiz).current_result.status === 3 ? parseFloat(unref(quiz).current_result.percentage) < unref(quiz).passing_score + 10 ? "\u0E1C\u0E48\u0E32\u0E19\u0E40\u0E09\u0E35\u0E22\u0E14\u0E09\u0E34\u0E27" : "\u0E1C\u0E48\u0E32\u0E19\u0E09\u0E25\u0E38\u0E22" : "\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19")}</span></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold text-lg hover:shadow-lg hover:scale-105 transition-all duration-300 shadow-purple-200 dark:shadow-none">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:play-circle-24-filled",
            class: "w-8 h-8"
          }, null, _parent));
          _push(` ${ssrInterpolate(unref(quiz).current_result && unref(quiz).current_result.completed_at ? "\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07" : "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A")}</button>`);
          if (!unref(quiz).current_result || !unref(quiz).current_result.completed_at) {
            _push(`<p class="mt-4 text-sm text-gray-500"> \u0E40\u0E21\u0E37\u0E48\u0E2D\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21\u0E40\u0E23\u0E34\u0E48\u0E21\u0E17\u0E33 \u0E40\u0E27\u0E25\u0E32\u0E08\u0E30\u0E19\u0E31\u0E1A\u0E16\u0E2D\u0E22\u0E2B\u0E25\u0E31\u0E07\u0E17\u0E31\u0E19\u0E17\u0E35 </p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<div><div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-6"><h3 class="text-lg font-semibold mb-4">\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E2A\u0E2D\u0E1A\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h3><div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400"><tr><th class="px-6 py-3 rounded-l-lg">\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</th><th class="px-6 py-3">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E2D\u0E1A</th><th class="px-6 py-3">\u0E04\u0E30\u0E41\u0E19\u0E19</th><th class="px-6 py-3">\u0E40\u0E1B\u0E2D\u0E23\u0E4C\u0E40\u0E0B\u0E47\u0E19\u0E15\u0E4C</th><th class="px-6 py-3 rounded-r-lg">\u0E2A\u0E16\u0E32\u0E19\u0E30</th></tr></thead><tbody><!--[-->`);
          ssrRenderList(unref(quiz).student_results, (result) => {
            var _a2, _b;
            _push(`<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"><td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white flex items-center gap-2"><div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden">`);
            if ((_a2 = result.user) == null ? void 0 : _a2.avatar) {
              _push(`<img${ssrRenderAttr("src", result.user.avatar)} class="w-full h-full object-cover">`);
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-24-filled",
                class: "w-full h-full p-1 text-gray-400"
              }, null, _parent));
            }
            _push(`</div> ${ssrInterpolate(((_b = result.user) == null ? void 0 : _b.name) || "Unknown")}</td><td class="px-6 py-4">${ssrInterpolate(result.completed_at ? formatDate(result.completed_at) : result.started_at ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E17\u0E33\u0E02\u0E49\u0E2D\u0E2A\u0E2D\u0E1A" : "-")}</td><td class="px-6 py-4">${ssrInterpolate(result.score)} / ${ssrInterpolate(unref(quiz).total_score)}</td><td class="px-6 py-4">${ssrInterpolate(parseFloat(result.percentage).toFixed(1))}% </td><td class="px-6 py-4">`);
            if (result.completed_at) {
              _push(`<span class="${ssrRenderClass([result.status === 3 ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700", "px-2 py-1 rounded text-xs font-bold"])}">${ssrInterpolate(result.status === 3 ? "\u0E1C\u0E48\u0E32\u0E19" : "\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19")}</span>`);
            } else {
              _push(`<span class="text-gray-500 italic">...</span>`);
            }
            _push(`</td></tr>`);
          });
          _push(`<!--]-->`);
          if (!unref(quiz).student_results || unref(quiz).student_results.length === 0) {
            _push(`<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E43\u0E04\u0E23\u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E19\u0E35\u0E49\u0E19\u0E30 </td></tr>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</tbody></table></div></div><div class="mt-4 flex justify-end gap-2"><button class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` \u0E41\u0E01\u0E49\u0E44\u0E02\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A </button></div></div>`);
        }
        _push(`</div></div></div>`);
      } else {
        _push(`<div class="text-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-16 h-16 text-gray-300 mx-auto mb-4"
        }, null, _parent));
        _push(`<h3 class="text-xl font-semibold text-gray-900 dark:text-white">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</h3><button class="mt-4 text-purple-600 hover:underline"> \u0E01\u0E25\u0E31\u0E1A\u0E44\u0E1B\u0E2B\u0E19\u0E49\u0E32\u0E23\u0E27\u0E21 </button></div>`);
      }
      _push(ssrRenderComponent(_sfc_main$2, {
        show: unref(showDuplicateModal),
        quiz: unref(quiz),
        "current-course-id": unref(courseId),
        onClose: ($event) => showDuplicateModal.value = false,
        onDuplicated: handleDuplicated
      }, null, _parent));
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/quizzes/[quizId]/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-C0Gwy5dk.mjs.map
