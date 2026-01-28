import { s as useCourseStore, p as useRoute, u as useRouter, i as useApi, f as useHead, q as __nuxt_component_0 } from './server.mjs';
import { defineComponent, computed, ref, watch, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './LessonPost-BeiNptMJ.mjs';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';
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
import './nuxt-link-Dhr1c_cd.mjs';
import './RichTextViewer-UnGuFLT8.mjs';
import './LessonQuizSection-D7bfNnT3.mjs';
import './RichTextEditor-C7FYwlb0.mjs';
import './useAvatar-C8DTKR1c.mjs';
import './ImageLightbox-D9vQ7Zkj.mjs';
import '@headlessui/vue';
import 'sweetalert2';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "[lessonId]",
  __ssrInlineRender: true,
  setup(__props) {
    const courseStore = useCourseStore();
    const course = computed(() => courseStore.currentCourse);
    const isCourseAdmin = computed(() => courseStore.isCourseAdmin);
    const route = useRoute();
    const router = useRouter();
    const api = useApi();
    const swal = useSweetAlert();
    const courseId = computed(() => route.params.id);
    const lessonId = computed(() => route.params.lessonId);
    const isChildRoute = computed(() => {
      const path = route.path;
      const lessonPath = `/courses/${courseId.value}/lessons/${lessonId.value}`;
      return path !== lessonPath && path.startsWith(lessonPath + "/");
    });
    const lesson = ref(null);
    const allLessons = ref([]);
    const isLoading = ref(true);
    const error = ref(null);
    const currentLessonIndex = computed(() => {
      if (!lesson.value || allLessons.value.length === 0) return -1;
      return allLessons.value.findIndex((l) => l.id === lesson.value.id);
    });
    const prevLesson = computed(() => {
      if (currentLessonIndex.value <= 0) return null;
      return allLessons.value[currentLessonIndex.value - 1];
    });
    const nextLesson = computed(() => {
      if (currentLessonIndex.value < 0 || currentLessonIndex.value >= allLessons.value.length - 1) return null;
      return allLessons.value[currentLessonIndex.value + 1];
    });
    const totalLessons = computed(() => allLessons.value.length);
    const fetchLesson = async () => {
      var _a;
      if (!courseId.value || !lessonId.value) return;
      isLoading.value = true;
      error.value = null;
      try {
        const response = await api.get(`/api/courses/${courseId.value}/lessons/${lessonId.value}`);
        if (response.success !== false) {
          lesson.value = response.lesson || response.data || response;
        } else {
          error.value = "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49";
        }
      } catch (err) {
        console.error("Failed to fetch lesson:", err);
        error.value = ((_a = err.data) == null ? void 0 : _a.message) || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E42\u0E2B\u0E25\u0E14\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19";
      } finally {
        isLoading.value = false;
      }
    };
    const handleEdit = (lessonData) => {
      router.push(`/courses/${courseId.value}/lessons/${lessonData.id}/edit`);
    };
    const handleDelete = async (lessonIdToDelete) => {
      var _a;
      const result = await swal.confirm("\u0E04\u0E38\u0E13\u0E41\u0E19\u0E48\u0E43\u0E08\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48\u0E17\u0E35\u0E48\u0E08\u0E30\u0E25\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49? \u0E01\u0E32\u0E23\u0E01\u0E23\u0E30\u0E17\u0E33\u0E19\u0E35\u0E49\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E49\u0E2D\u0E19\u0E01\u0E25\u0E31\u0E1A\u0E44\u0E14\u0E49", "\u0E25\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19");
      if (result) {
        try {
          await api.delete(`/api/lessons/${lessonIdToDelete}`);
          swal.success("\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E16\u0E39\u0E01\u0E25\u0E1A\u0E41\u0E25\u0E49\u0E27", "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08");
          router.push(`/courses/${courseId.value}/lessons`);
        } catch (err) {
          console.error("Failed to delete lesson:", err);
          swal.error(((_a = err.data) == null ? void 0 : _a.message) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49");
        }
      }
    };
    const handleLike = async (id) => {
      try {
        await api.post(`/api/courses/${courseId.value}/lessons/${id}/like`);
      } catch (err) {
        console.error("Failed to like lesson:", err);
      }
    };
    const handleDislike = async (id) => {
      try {
        await api.post(`/api/courses/${courseId.value}/lessons/${id}/dislike`);
      } catch (err) {
        console.error("Failed to dislike lesson:", err);
      }
    };
    const handleBookmark = async (id) => {
      try {
        const response = await api.post(`/api/courses/${courseId.value}/lessons/${id}/bookmark`);
        if (response.bookmarked) {
          swal.toast("\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E25\u0E49\u0E27", "success");
        } else {
          swal.toast("\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E41\u0E25\u0E49\u0E27", "info");
        }
      } catch (err) {
        console.error("Failed to bookmark lesson:", err);
      }
    };
    const handleShare = (lessonData) => {
      const url = `${(void 0).location.origin}/courses/${courseId.value}/lessons/${lessonData.id}`;
      (void 0).clipboard.writeText(url);
      swal.toast("\u0E04\u0E31\u0E14\u0E25\u0E2D\u0E01\u0E25\u0E34\u0E07\u0E01\u0E4C\u0E41\u0E25\u0E49\u0E27", "success");
    };
    watch(lessonId, async () => {
      await fetchLesson();
    });
    watch(lesson, (newLesson) => {
      var _a;
      if (newLesson == null ? void 0 : newLesson.title) {
        useHead({
          title: `${newLesson.title} - ${((_a = course.value) == null ? void 0 : _a.name) || "\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19"}`
        });
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtPage = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      if (isChildRoute.value) {
        _push(ssrRenderComponent(_component_NuxtPage, null, null, _parent));
      } else {
        _push(`<!--[-->`);
        if (isLoading.value) {
          _push(`<div class="flex justify-center items-center min-h-[50vh]"><div class="text-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div><p class="text-gray-500 dark:text-gray-400">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19...</p></div></div>`);
        } else if (error.value) {
          _push(`<div class="flex justify-center items-center min-h-[50vh]"><div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center max-w-md">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:error-circle-24-regular",
            class: "w-16 h-16 text-red-500 mx-auto mb-4"
          }, null, _parent));
          _push(`<h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14</h3><p class="text-red-600 dark:text-red-400 mb-4">${ssrInterpolate(error.value)}</p><button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48 </button></div></div>`);
        } else if (lesson.value) {
          _push(`<div class="max-w-4xl mx-auto px-4">`);
          _push(ssrRenderComponent(_sfc_main$1, {
            lesson: lesson.value,
            isAdmin: isCourseAdmin.value,
            "prev-lesson": prevLesson.value,
            "next-lesson": nextLesson.value,
            "current-index": currentLessonIndex.value,
            "total-lessons": totalLessons.value,
            onEdit: handleEdit,
            onDelete: handleDelete,
            onLike: handleLike,
            onDislike: handleDislike,
            onBookmark: handleBookmark,
            onShare: handleShare
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      }
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Courses/[id]/lessons/[lessonId].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_lessonId_-tjxTaLfB.mjs.map
