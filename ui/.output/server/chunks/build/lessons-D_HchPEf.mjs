import { i as useApi, q as __nuxt_component_0 } from './server.mjs';
import { defineComponent, inject, ref, computed, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { C as ContentLoader } from './ContentLoader-D4cV05oG.mjs';
import { _ as _sfc_main$1 } from './LessonPost-BeiNptMJ.mjs';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
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
import './nuxt-link-Dhr1c_cd.mjs';
import './RichTextViewer-UnGuFLT8.mjs';
import './LessonQuizSection-D7bfNnT3.mjs';
import './RichTextEditor-C7FYwlb0.mjs';
import './useAvatar-C8DTKR1c.mjs';
import './ImageLightbox-D9vQ7Zkj.mjs';
import './useSweetAlert-jHixiibP.mjs';
import 'sweetalert2';
import '@headlessui/vue';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "lessons",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const router = useRouter();
    const api = useApi();
    const course = inject("course");
    const isCourseAdmin = inject("isCourseAdmin");
    const lessons = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    const showScrollButton = ref(false);
    const isRoot = computed(() => {
      return /\/lessons\/?$/.test(route.path);
    });
    const fetchLessons = async () => {
      var _a;
      if (!((_a = course.value) == null ? void 0 : _a.id)) return;
      isLoading.value = true;
      error.value = null;
      try {
        const response = await api.get(`/api/courses/${course.value.id}/lessons`);
        lessons.value = response.lessons || response.data || response || [];
      } catch (err) {
        console.error("Error fetching lessons:", err);
        error.value = err.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49";
      } finally {
        isLoading.value = false;
      }
    };
    const handleEditLesson = (lesson) => {
      router.push(`/courses/${course.value.id}/lessons/${lesson.id}/edit`);
    };
    const handleDeleteLesson = async (id) => {
      if (!confirm("\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19?")) return;
      try {
        await api.delete(`/api/courses/${course.value.id}/lessons/${id}`);
        await fetchLessons();
      } catch (err) {
        console.error("Error deleting lesson", err);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A");
      }
    };
    const handleLikeLesson = async (id) => {
      await fetchLessons();
    };
    const handleDislikeLesson = async (id) => {
      await fetchLessons();
    };
    const handleBookmarkLesson = async (id) => {
    };
    const handleShareLesson = (lesson) => {
    };
    const handleCommentLesson = (lesson) => {
      router.push(`/courses/${course.value.id}/lessons/${lesson.id}#comments`);
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtPage = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(_component_NuxtPage, null, null, _parent));
      if (isRoot.value) {
        _push(`<!--[-->`);
        if (isLoading.value) {
          _push(ssrRenderComponent(ContentLoader, null, null, _parent));
        } else if (error.value) {
          _push(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center max-w-md mx-auto">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:error-circle-24-regular",
            class: "w-16 h-16 text-red-500 mx-auto mb-4"
          }, null, _parent));
          _push(`<h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h3><p class="text-red-600 dark:text-red-400 mb-4">${ssrInterpolate(error.value)}</p><button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div>`);
        } else {
          _push(`<!--[-->`);
          if (unref(isCourseAdmin)) {
            _push(`<div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 dark:from-blue-800 dark:via-cyan-800 dark:to-purple-800 rounded-2xl p-6 shadow-xl mb-6"><div class="flex items-center justify-between"><div class="flex items-center gap-4"><div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:book-24-filled",
              class: "w-7 h-7 text-white"
            }, null, _parent));
            _push(`</div><div><h2 class="text-2xl font-bold text-white mb-1">\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</h2><p class="text-white/80 text-sm">${ssrInterpolate(lessons.value.length)} \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><button class="flex items-center gap-2 px-5 py-3 bg-white text-blue-600 rounded-xl hover:bg-blue-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-circle-24-filled",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`<span>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</span></button></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (!lessons.value.length) {
            _push(`<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:book-24-regular",
              class: "w-24 h-24 text-gray-300 dark:text-gray-600 mx-auto mb-4"
            }, null, _parent));
            _push(`<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </h3><p class="text-gray-600 dark:text-gray-400">${ssrInterpolate(unref(isCourseAdmin) ? "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E23\u0E01\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13" : "\u0E2D\u0E32\u0E08\u0E32\u0E23\u0E22\u0E4C\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E15\u0E23\u0E35\u0E22\u0E21\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2D\u0E22\u0E39\u0E48")}</p></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(lessons.value, (lesson) => {
            _push(`<div>`);
            _push(ssrRenderComponent(_sfc_main$1, {
              lesson,
              "is-admin": unref(isCourseAdmin),
              onEdit: handleEditLesson,
              onDelete: handleDeleteLesson,
              onLike: handleLikeLesson,
              onDislike: handleDislikeLesson,
              onBookmark: handleBookmarkLesson,
              onShare: handleShareLesson,
              onComment: handleCommentLesson
            }, null, _parent));
            _push(`</div>`);
          });
          _push(`<!--]--><!--]-->`);
        }
        _push(`<!--]-->`);
      } else {
        _push(`<!---->`);
      }
      if (showScrollButton.value) {
        _push(`<button class="fixed bottom-8 right-8 z-[999] p-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all" title="\u0E40\u0E25\u0E37\u0E48\u0E2D\u0E19\u0E02\u0E36\u0E49\u0E19\u0E14\u0E49\u0E32\u0E19\u0E1A\u0E19">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-up-24-filled",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`</button>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/lessons.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=lessons-D_HchPEf.mjs.map
