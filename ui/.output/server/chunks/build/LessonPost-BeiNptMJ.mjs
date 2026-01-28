import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, watch, nextTick, computed, mergeProps, unref, withCtx, createVNode, toDisplayString, withDirectives, vModelText, createBlock, createCommentVNode, openBlock, Fragment, renderList, createTextVNode, vModelCheckbox, resolveComponent, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderClass, ssrInterpolate, ssrRenderAttr, ssrRenderStyle, ssrRenderList, ssrIncludeBooleanAttr, ssrRenderTeleport, ssrLooseContain } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as __nuxt_component_1 } from './RichTextViewer-UnGuFLT8.mjs';
import { _ as _export_sfc, d as useAuthStore, i as useApi } from './server.mjs';
import { _ as _sfc_main$1$1, Q as QuestionsListViewer } from './LessonQuizSection-D7bfNnT3.mjs';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import { TransitionRoot, Dialog, TransitionChild, DialogPanel, DialogTitle } from '@headlessui/vue';

const _sfc_main$6 = /* @__PURE__ */ defineComponent({
  __name: "VideoModal",
  __ssrInlineRender: true,
  props: {
    youtubeUrl: {},
    vimeoUrl: {},
    videoSrc: {},
    title: {}
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const isVisible = ref(false);
    ref(true);
    const iframeLoaded = ref(false);
    const getYoutubeVideoId = (url) => {
      if (!url) return null;
      if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
        return url;
      }
      const patterns = [
        /(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/,
        /youtu\.be\/([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
        /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/
      ];
      for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) {
          return match[1];
        }
      }
      return null;
    };
    const youtubeVideoId = computed(
      () => props.youtubeUrl ? getYoutubeVideoId(props.youtubeUrl) : null
    );
    const youtubeEmbedUrl = computed(() => {
      if (!youtubeVideoId.value) return null;
      const params = new URLSearchParams({
        autoplay: "1",
        rel: "0",
        modestbranding: "1",
        playsinline: "1"
      });
      return `https://www.youtube-nocookie.com/embed/${youtubeVideoId.value}?${params.toString()}`;
    });
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        _push2(`<div class="${ssrRenderClass([[isVisible.value ? "bg-black/80 backdrop-blur-md" : "bg-black/0 backdrop-blur-0"], "modal-backdrop fixed inset-0 z-[9999] flex items-center justify-center p-4 md:p-8 transition-all duration-300"])}" data-v-b7d88a0f><div class="${ssrRenderClass([[isVisible.value ? "opacity-100 scale-100" : "opacity-0 scale-95"], "relative w-full max-w-5xl transition-all duration-300 transform"])}" data-v-b7d88a0f><button class="absolute -top-12 right-0 md:-right-12 md:top-0 p-3 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-all duration-200 z-10 group" title="\u0E1B\u0E34\u0E14 (Esc)" data-v-b7d88a0f>`);
        _push2(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-filled",
          class: "w-8 h-8 transform group-hover:rotate-90 transition-transform duration-200"
        }, null, _parent));
        _push2(`</button>`);
        if (__props.title) {
          _push2(`<h3 class="text-white text-lg font-semibold mb-4 truncate pr-12 md:pr-0" data-v-b7d88a0f>${ssrInterpolate(__props.title)}</h3>`);
        } else {
          _push2(`<!---->`);
        }
        _push2(`<div class="relative rounded-2xl overflow-hidden shadow-2xl ring-1 ring-white/20 bg-black aspect-video" data-v-b7d88a0f>`);
        if (youtubeEmbedUrl.value) {
          _push2(`<!--[-->`);
          if (!iframeLoaded.value) {
            _push2(`<div class="absolute inset-0 flex items-center justify-center bg-gray-900" data-v-b7d88a0f><div class="text-center" data-v-b7d88a0f><div class="w-20 h-20 mx-auto mb-4 bg-gray-800 rounded-full flex items-center justify-center animate-pulse" data-v-b7d88a0f>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:play-24-filled",
              class: "w-10 h-10 text-gray-600"
            }, null, _parent));
            _push2(`</div><span class="text-gray-500" data-v-b7d88a0f>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D...</span></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<iframe${ssrRenderAttr("src", youtubeEmbedUrl.value)} class="absolute inset-0 w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen data-v-b7d88a0f></iframe><!--]-->`);
        } else {
          _push2(`<div class="w-full h-full flex items-center justify-center bg-gray-900" data-v-b7d88a0f><div class="text-center" data-v-b7d88a0f>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:video-off-24-filled",
            class: "w-16 h-16 text-gray-600 mx-auto mb-4"
          }, null, _parent));
          _push2(`<span class="text-gray-500" data-v-b7d88a0f>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D</span></div></div>`);
        }
        _push2(`</div><p class="text-white/50 text-center text-sm mt-4 hidden md:block" data-v-b7d88a0f> \u0E01\u0E14 <kbd class="px-2 py-1 bg-white/10 rounded text-white/70" data-v-b7d88a0f>Esc</kbd> \u0E2B\u0E23\u0E37\u0E2D\u0E04\u0E25\u0E34\u0E01\u0E14\u0E49\u0E32\u0E19\u0E19\u0E2D\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E1B\u0E34\u0E14 </p></div></div>`);
      }, "body", false, _parent);
    };
  }
});
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/media/VideoModal.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const VideoModal = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["__scopeId", "data-v-b7d88a0f"]]);
const _sfc_main$5 = /* @__PURE__ */ defineComponent({
  __name: "TopicAccordion",
  __ssrInlineRender: true,
  props: {
    topic: {},
    isCompleted: { type: Boolean, default: false }
  },
  emits: ["toggleComplete"],
  setup(__props, { emit: __emit }) {
    const isExpanded = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["border rounded-xl overflow-hidden transition-all duration-300", [
          __props.isCompleted ? "border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10" : "border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
        ]]
      }, _attrs))}><button class="w-full flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"><div class="flex items-center gap-3 flex-1"><div class="${ssrRenderClass([[
        __props.isCompleted ? "bg-green-500 border-green-500" : "border-gray-300 dark:border-gray-600 hover:border-green-500"
      ], "flex-shrink-0 w-6 h-6 rounded-md border-2 flex items-center justify-center cursor-pointer transition-all"])}">`);
      if (__props.isCompleted) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-24-filled",
          class: "w-4 h-4 text-white"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:document-text-24-regular",
        class: ["w-5 h-5 flex-shrink-0", __props.isCompleted ? "text-green-600 dark:text-green-400" : "text-gray-600 dark:text-gray-400"]
      }, null, _parent));
      _push(`<h4 class="${ssrRenderClass([[
        __props.isCompleted ? "text-green-900 dark:text-green-100 line-through" : "text-gray-900 dark:text-white"
      ], "text-left font-medium transition-colors"])}">${ssrInterpolate(__props.topic.title)}</h4></div>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: isExpanded.value ? "fluent:chevron-up-24-filled" : "fluent:chevron-down-24-filled",
        class: "w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0 ml-2"
      }, null, _parent));
      _push(`</button><div class="border-t border-gray-200 dark:border-gray-700" style="${ssrRenderStyle(isExpanded.value ? null : { display: "none" })}"><div class="p-4 space-y-4">`);
      if (__props.topic.content) {
        _push(`<div class="prose prose-sm dark:prose-invert max-w-none">`);
        _push(ssrRenderComponent(__nuxt_component_1, {
          content: __props.topic.content
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.topic.images && __props.topic.images.length > 0) {
        _push(`<div class="grid grid-cols-2 gap-2"><!--[-->`);
        ssrRenderList(__props.topic.images, (image) => {
          _push(`<img${ssrRenderAttr("src", image.full_url)}${ssrRenderAttr("alt", __props.topic.title)} class="rounded-lg object-cover w-full h-48 border border-gray-200 dark:border-gray-700 hover:scale-105 transition-transform cursor-pointer">`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.topic.assignments && __props.topic.assignments.length > 0) {
        _push(`<div class="pt-4 border-t border-gray-200 dark:border-gray-700"><div class="flex items-center gap-2 mb-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-24-filled",
          class: "w-5 h-5 text-green-600 dark:text-green-400"
        }, null, _parent));
        _push(`<h5 class="font-semibold text-gray-900 dark:text-white">\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E43\u0E19\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E19\u0E35\u0E49</h5></div><div class="space-y-2"><!--[-->`);
        ssrRenderList(__props.topic.assignments, (assignment) => {
          _push(`<div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"><p class="text-sm font-medium text-gray-900 dark:text-white">${ssrInterpolate(assignment.title)}</p></div>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.topic.questions && __props.topic.questions.length > 0) {
        _push(`<div class="pt-4 border-t border-gray-200 dark:border-gray-700"><div class="flex items-center gap-2 mb-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:quiz-new-24-filled",
          class: "w-5 h-5 text-orange-600 dark:text-orange-400"
        }, null, _parent));
        _push(`<h5 class="font-semibold text-gray-900 dark:text-white">\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E19\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E19\u0E35\u0E49</h5></div><p class="text-sm text-gray-600 dark:text-gray-400"> \u0E21\u0E35 ${ssrInterpolate(__props.topic.questions.length)} \u0E04\u0E33\u0E16\u0E32\u0E21 </p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/TopicAccordion.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = /* @__PURE__ */ defineComponent({
  __name: "AssignmentFormModal",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    lessonId: {},
    assignment: {}
  },
  emits: ["close", "submit"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    useApi();
    const form = ref({
      title: "",
      description: "",
      points: 10,
      start_date: "",
      due_date: "",
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
        start_date: getCurrentDateTime(),
        due_date: getTomorrowDateTime(),
        images: [],
        existingImages: []
      };
      imagePreviews.value = [];
    };
    watch(() => props.assignment, (newVal) => {
      if (newVal) {
        form.value = {
          title: newVal.title,
          description: newVal.description || "",
          points: newVal.points || 10,
          start_date: newVal.start_date ? new Date(newVal.start_date) : "",
          due_date: newVal.due_date ? new Date(newVal.due_date) : "",
          images: [],
          existingImages: newVal.images || []
        };
      } else {
        resetForm();
      }
    }, { immediate: true });
    watch(() => props.show, (val) => {
      if (val && !props.assignment) {
        resetForm();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_VueDatePicker = resolveComponent("VueDatePicker");
      if (__props.show) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" }, _attrs))} data-v-391bb215><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" data-v-391bb215><div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 z-10" data-v-391bb215><h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" data-v-391bb215>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-edit-24-filled",
          class: "text-green-600"
        }, null, _parent));
        _push(` ${ssrInterpolate(__props.assignment ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E43\u0E2B\u0E21\u0E48")}</h3><button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors" data-v-391bb215>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-6 h-6 text-gray-500"
        }, null, _parent));
        _push(`</button></div><div class="p-6 space-y-6" data-v-391bb215><div data-v-391bb215><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-391bb215> \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14 <span class="text-red-500" data-v-391bb215>*</span></label><input${ssrRenderAttr("value", form.value.title)} type="text" class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all" placeholder="Ex. \u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E17\u0E1A\u0E17\u0E27\u0E19\u0E1A\u0E17\u0E17\u0E35\u0E48 1" data-v-391bb215></div><div data-v-391bb215><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-391bb215> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 / \u0E42\u0E08\u0E17\u0E22\u0E4C </label><textarea rows="4" class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all resize-none" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E02\u0E2D\u0E07\u0E07\u0E32\u0E19..." data-v-391bb215>${ssrInterpolate(form.value.description)}</textarea></div><div class="grid grid-cols-2 gap-4" data-v-391bb215><div data-v-391bb215><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-391bb215> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19 (Start Date) </label><div class="relative" data-v-391bb215>`);
        _push(ssrRenderComponent(_component_VueDatePicker, {
          modelValue: form.value.start_date,
          "onUpdate:modelValue": ($event) => form.value.start_date = $event,
          "auto-apply": "",
          "enable-time-picker": true,
          dark: true
        }, null, _parent));
        _push(`</div></div><div data-v-391bb215><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-391bb215> \u0E01\u0E33\u0E2B\u0E19\u0E14\u0E2A\u0E48\u0E07 (Due Date) </label>`);
        _push(ssrRenderComponent(_component_VueDatePicker, {
          modelValue: form.value.due_date,
          "onUpdate:modelValue": ($event) => form.value.due_date = $event,
          "auto-apply": "",
          "enable-time-picker": true,
          dark: true
        }, null, _parent));
        _push(`</div></div><div data-v-391bb215><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-391bb215> \u0E04\u0E30\u0E41\u0E19\u0E19\u0E40\u0E15\u0E47\u0E21 </label><div class="relative" data-v-391bb215><input${ssrRenderAttr("value", form.value.points)} type="number" min="0" class="w-full px-4 py-2 pl-10 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all" data-v-391bb215>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-regular",
          class: "absolute left-3 top-2.5 w-5 h-5 text-gray-400"
        }, null, _parent));
        _push(`</div></div><div data-v-391bb215><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-v-391bb215> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A </label><div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4" data-v-391bb215><!--[-->`);
        ssrRenderList(form.value.existingImages, (img) => {
          _push(`<div class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden" data-v-391bb215><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-full object-cover" data-v-391bb215><button class="absolute top-1 right-1 p-1bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity" data-v-391bb215>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-16-filled",
            class: "w-4 h-4"
          }, null, _parent));
          _push(`</button></div>`);
        });
        _push(`<!--]--><!--[-->`);
        ssrRenderList(imagePreviews.value, (preview, idx) => {
          _push(`<div class="relative group aspect-video bg-gray-100 rounded-lg overflow-hidden" data-v-391bb215><img${ssrRenderAttr("src", preview)} class="w-full h-full object-cover" data-v-391bb215><button class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full shadow-sm hover:bg-red-600 transition-colors" data-v-391bb215>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-12-regular",
            class: "w-3 h-3"
          }, null, _parent));
          _push(`</button></div>`);
        });
        _push(`<!--]--><label class="aspect-video flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-green-500 hover:text-green-500 transition-colors bg-gray-50 dark:bg-gray-700/50" data-v-391bb215>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:image-add-24-regular",
          class: "w-8 h-8 mb-2"
        }, null, _parent));
        _push(`<span class="text-xs" data-v-391bb215>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span><input type="file" multiple accept="image/*" class="hidden" data-v-391bb215></label></div></div></div><div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl" data-v-391bb215><button class="px-5 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors" data-v-391bb215> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 hover:shadow-green-500/40 hover:from-green-600 hover:to-emerald-700 active:scale-[0.98] transition-all flex items-center gap-2" data-v-391bb215>`);
        if (isSubmitting.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:loading",
            class: "w-5 h-5"
          }, null, _parent));
        } else {
          _push(`<span data-v-391bb215>${ssrInterpolate(__props.assignment ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02" : "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14")}</span>`);
        }
        _push(`</button></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
});
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/AssignmentFormModal.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const AssignmentFormModal = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-391bb215"]]);
const _sfc_main$3 = /* @__PURE__ */ defineComponent({
  __name: "AssignmentGradingModal",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    assignment: {},
    lessonId: {}
  },
  emits: ["close", "graded"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const api = useApi();
    const swal = useSweetAlert();
    const { getAvatarUrl } = useAvatar();
    const answers = ref([]);
    const isLoading = ref(false);
    const selectedAnswer = ref(null);
    const gradePoints = ref("");
    const feedback = ref("");
    const isGrading = ref(false);
    watch(() => props.show, async (newVal) => {
      if (newVal && props.assignment) {
        isLoading.value = true;
        selectedAnswer.value = null;
        feedback.value = "";
        try {
          const response = await api.get(`/api/assignments/${props.assignment.id}/answers`);
          answers.value = response.answers || [];
        } catch (error) {
          console.error("Failed to fetch answers:", error);
          swal.toast("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E44\u0E14\u0E49", "error");
        } finally {
          isLoading.value = false;
        }
      }
    });
    const getUserAvatar = (user) => getAvatarUrl(user);
    const formatDate = (date) => {
      if (!date) return "-";
      return new Date(date).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      if (__props.show) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" }, _attrs))} data-v-60d73bb0><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-5xl h-[80vh] flex overflow-hidden" data-v-60d73bb0><div class="w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-800/50" data-v-60d73bb0><div class="p-4 border-b border-gray-200 dark:border-gray-700" data-v-60d73bb0><h3 class="font-bold text-gray-900 dark:text-white flex items-center justify-between" data-v-60d73bb0><span data-v-60d73bb0>\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19\u0E41\u0E25\u0E49\u0E27 (${ssrInterpolate(answers.value.length)})</span><button class="md:hidden text-gray-500" data-v-60d73bb0>`);
        _push(ssrRenderComponent(unref(Icon), { icon: "fluent:dismiss-24-regular" }, null, _parent));
        _push(`</button></h3></div><div class="flex-1 overflow-y-auto p-2 space-y-2" data-v-60d73bb0>`);
        if (isLoading.value) {
          _push(`<div class="flex justify-center py-8" data-v-60d73bb0>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:loading",
            class: "w-6 h-6 text-green-500"
          }, null, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--[-->`);
        ssrRenderList(answers.value, (answer) => {
          var _a2, _b2, _c2;
          _push(`<button class="${ssrRenderClass([((_a2 = selectedAnswer.value) == null ? void 0 : _a2.id) === answer.id ? "bg-white dark:bg-gray-700 shadow-sm ring-1 ring-green-500" : "hover:bg-gray-100 dark:hover:bg-gray-700", "w-full p-3 rounded-lg flex items-center gap-3 transition-colors text-left"])}" data-v-60d73bb0><img${ssrRenderAttr("src", getUserAvatar(answer.user))} class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600" data-v-60d73bb0><div class="flex-1 min-w-0" data-v-60d73bb0><div class="font-medium text-gray-900 dark:text-white truncate" data-v-60d73bb0>${ssrInterpolate((_b2 = answer.user) == null ? void 0 : _b2.firstname)} ${ssrInterpolate((_c2 = answer.user) == null ? void 0 : _c2.lastname)}</div><div class="text-xs text-gray-500 flex justify-between" data-v-60d73bb0><span data-v-60d73bb0>${ssrInterpolate(formatDate(answer.created_at))}</span>`);
          if (answer.points !== null) {
            _push(`<span class="font-bold text-amber-600 dark:text-amber-400" data-v-60d73bb0>${ssrInterpolate(answer.points)} / ${ssrInterpolate(__props.assignment.points)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></button>`);
        });
        _push(`<!--]-->`);
        if (!isLoading.value && answers.value.length === 0) {
          _push(`<div class="text-center py-8 text-gray-500" data-v-60d73bb0><p data-v-60d73bb0>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E43\u0E04\u0E23\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div><div class="flex-1 flex flex-col w-2/3" data-v-60d73bb0><div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-gray-800" data-v-60d73bb0><h3 class="font-bold text-lg text-gray-900 dark:text-white" data-v-60d73bb0>${ssrInterpolate((_a = __props.assignment) == null ? void 0 : _a.title)}</h3><button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-v-60d73bb0>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:dismiss-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button></div><div class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900" data-v-60d73bb0>`);
        if (selectedAnswer.value) {
          _push(`<div class="space-y-6" data-v-60d73bb0><div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700" data-v-60d73bb0><div class="flex items-center gap-3 mb-4" data-v-60d73bb0><img${ssrRenderAttr("src", getUserAvatar(selectedAnswer.value.user))} class="w-10 h-10 rounded-full" data-v-60d73bb0><div data-v-60d73bb0><h4 class="font-bold text-gray-900 dark:text-white" data-v-60d73bb0>${ssrInterpolate((_b = selectedAnswer.value.user) == null ? void 0 : _b.firstname)} ${ssrInterpolate((_c = selectedAnswer.value.user) == null ? void 0 : _c.lastname)}</h4><p class="text-xs text-gray-500" data-v-60d73bb0>\u0E2A\u0E48\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D ${ssrInterpolate(formatDate(selectedAnswer.value.created_at))}</p></div></div><div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap" data-v-60d73bb0>${ssrInterpolate(selectedAnswer.value.content)}</div>`);
          if ((_d = selectedAnswer.value.images) == null ? void 0 : _d.length) {
            _push(`<div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3" data-v-60d73bb0><!--[-->`);
            ssrRenderList(selectedAnswer.value.images, (img) => {
              _push(`<a${ssrRenderAttr("href", img.full_url || img.image_url)} target="_blank" class="block rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:opacity-90" data-v-60d73bb0><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-full h-32 object-cover" data-v-60d73bb0></a>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
        } else {
          _push(`<div class="h-full flex flex-col items-center justify-center text-gray-500" data-v-60d73bb0>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:person-board-24-regular",
            class: "w-16 h-16 opacity-30 mb-4"
          }, null, _parent));
          _push(`<p data-v-60d73bb0>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E32\u0E01\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E14\u0E49\u0E32\u0E19\u0E0B\u0E49\u0E32\u0E22\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E15\u0E23\u0E27\u0E08\u0E07\u0E32\u0E19</p></div>`);
        }
        _push(`</div>`);
        if (selectedAnswer.value) {
          _push(`<div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-10" data-v-60d73bb0><div class="flex items-center gap-4 max-w-2xl mx-auto" data-v-60d73bb0><div class="flex-1" data-v-60d73bb0><label class="block text-xs font-medium text-gray-500 mb-1" data-v-60d73bb0>\u0E04\u0E30\u0E41\u0E19\u0E19 (\u0E40\u0E15\u0E47\u0E21 ${ssrInterpolate(__props.assignment.points)})</label><div class="relative" data-v-60d73bb0><input${ssrRenderAttr("value", gradePoints.value)} type="number" min="0"${ssrRenderAttr("max", __props.assignment.points)} class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 outline-none" placeholder="\u0E43\u0E2A\u0E48\u0E04\u0E30\u0E41\u0E19\u0E19" data-v-60d73bb0>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:star-24-regular",
            class: "absolute right-3 top-2.5 w-5 h-5 text-gray-400"
          }, null, _parent));
          _push(`</div><input type="range"${ssrRenderAttr("value", gradePoints.value)} min="0"${ssrRenderAttr("max", __props.assignment.points)} step="1" class="w-full mt-3 accent-green-500 cursor-grab active:cursor-grabbing h-2 bg-gray-200 rounded-lg appearance-none dark:bg-gray-700" data-v-60d73bb0><div class="mt-4" data-v-60d73bb0><label class="block text-xs font-medium text-gray-500 mb-1" data-v-60d73bb0>\u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30 (Feedback)</label><textarea rows="2" class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 outline-none resize-none" placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E02\u0E49\u0E2D\u0E41\u0E19\u0E30\u0E19\u0E33\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21..." data-v-60d73bb0>${ssrInterpolate(feedback.value)}</textarea></div></div><button${ssrIncludeBooleanAttr(isGrading.value) ? " disabled" : ""} class="mt-5 px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl active:scale-95 transition-all text-sm flex items-center gap-2" data-v-60d73bb0>`);
          if (isGrading.value) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "eos-icons:loading",
              class: "w-5 h-5"
            }, null, _parent));
          } else {
            _push(`<span data-v-60d73bb0>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E04\u0E30\u0E41\u0E19\u0E19</span>`);
          }
          _push(`</button></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
});
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/AssignmentGradingModal.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const AssignmentGradingModal = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-60d73bb0"]]);
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "QuestionFormModal",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    lessonId: {},
    question: {}
  },
  emits: ["close", "submit"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const api = useApi();
    const swal = useSweetAlert();
    const isLoading = ref(false);
    const form = ref({
      text: "",
      points: 1,
      images: [],
      options: [
        { text: "", is_correct: false, id: null, image: null, imageFile: null },
        { text: "", is_correct: false, id: null, image: null, imageFile: null },
        { text: "", is_correct: false, id: null, image: null, imageFile: null },
        { text: "", is_correct: false, id: null, image: null, imageFile: null }
      ],
      deleted_images: []
    });
    const existingImages = ref([]);
    const previewImages = ref([]);
    const initForm = () => {
      if (props.question) {
        form.value.text = props.question.text || "";
        form.value.points = props.question.points || 1;
        form.value.images = [];
        form.value.deleted_images = [];
        existingImages.value = [...props.question.images || []];
        if (props.question.options && props.question.options.length > 0) {
          form.value.options = props.question.options.map((opt) => ({
            id: opt.id,
            text: opt.text,
            is_correct: !!opt.is_correct,
            image: opt.images && opt.images.length > 0 ? opt.images[0].full_url || opt.images[0].image_url : null,
            imageFile: null
          }));
        } else {
          form.value.options = [
            { text: "", is_correct: false, id: null, image: null, imageFile: null },
            { text: "", is_correct: false, id: null, image: null, imageFile: null },
            { text: "", is_correct: false, id: null, image: null, imageFile: null },
            { text: "", is_correct: false, id: null, image: null, imageFile: null }
          ];
        }
      } else {
        resetForm();
      }
    };
    watch(() => props.question, initForm, { immediate: true });
    watch(() => props.show, (isOpen) => {
      if (isOpen) initForm();
    });
    function resetForm() {
      form.value = {
        text: "",
        points: 1,
        images: [],
        options: [
          { text: "", is_correct: false, id: null, image: null, imageFile: null },
          { text: "", is_correct: false, id: null, image: null, imageFile: null },
          { text: "", is_correct: false, id: null, image: null, imageFile: null },
          { text: "", is_correct: false, id: null, image: null, imageFile: null }
        ],
        deleted_images: []
      };
      existingImages.value = [];
      previewImages.value = [];
    }
    const handleFileSelect = (event) => {
      const input = event.target;
      if (input.files) {
        const newFiles = Array.from(input.files);
        form.value.images = [...form.value.images, ...newFiles];
        newFiles.forEach((file) => {
          previewImages.value.push(URL.createObjectURL(file));
        });
      }
    };
    const removeFile = (index) => {
      URL.revokeObjectURL(previewImages.value[index]);
      previewImages.value.splice(index, 1);
      form.value.images.splice(index, 1);
    };
    const removeExistingImage = (imageId) => {
      const index = existingImages.value.findIndex((img) => img.id === imageId);
      if (index !== -1) {
        existingImages.value.splice(index, 1);
        form.value.deleted_images.push(imageId);
      }
    };
    const addOption = () => {
      form.value.options.push({ text: "", is_correct: false, id: null, image: null, imageFile: null });
    };
    const removeOption = (index) => {
      form.value.options.splice(index, 1);
    };
    const handleOptionFileSelect = (event, index) => {
      const input = event.target;
      if (input.files && input.files[0]) {
        const file = input.files[0];
        form.value.options[index].imageFile = file;
        form.value.options[index].image = URL.createObjectURL(file);
      }
    };
    const removeOptionImage = (index) => {
      if (form.value.options[index].imageFile) {
        URL.revokeObjectURL(form.value.options[index].image);
      }
      form.value.options[index].image = null;
      form.value.options[index].imageFile = null;
    };
    const onSubmit = async () => {
      var _a;
      if (!form.value.text) {
        swal.error("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E30\u0E1A\u0E38\u0E04\u0E33\u0E16\u0E32\u0E21");
        return;
      }
      if (!form.value.options.some((o) => o.is_correct)) {
        swal.error("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 1 \u0E02\u0E49\u0E2D");
        return;
      }
      if (form.value.options.some((o) => {
        var _a2;
        return !((_a2 = o.text) == null ? void 0 : _a2.trim()) && !o.image;
      })) {
        swal.error("\u0E01\u0E23\u0E38\u0E13\u0E32\u0E01\u0E23\u0E2D\u0E01\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E43\u0E2B\u0E49\u0E04\u0E23\u0E1A\u0E16\u0E49\u0E27\u0E19 (\u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E)");
        return;
      }
      isLoading.value = true;
      try {
        const formData = new FormData();
        formData.append("text", form.value.text);
        formData.append("points", form.value.points.toString());
        form.value.options.forEach((opt, index) => {
          if (opt.id) formData.append(`options[${index}][id]`, opt.id.toString());
          formData.append(`options[${index}][text]`, opt.text || "");
          formData.append(`options[${index}][is_correct]`, opt.is_correct ? "true" : "false");
          if (opt.imageFile) {
            formData.append(`options[${index}][image]`, opt.imageFile);
          }
        });
        form.value.images.forEach((file) => {
          formData.append("images[]", file);
        });
        form.value.deleted_images.forEach((id) => {
          formData.append("deleted_images[]", id.toString());
        });
        let question;
        if (props.question) {
          formData.append("_method", "PUT");
          const res = await api.post(`/api/lessons/${props.lessonId}/questions/${props.question.id}`, formData);
          question = res.question;
        } else {
          const res = await api.post(`/api/lessons/${props.lessonId}/questions`, formData);
          question = res.question;
        }
        emit("submit", question);
        emit("close");
        swal.toast("\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E04\u0E33\u0E16\u0E32\u0E21\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22", "success");
      } catch (error) {
        console.error("Failed to save question:", error);
        swal.error(((_a = error == null ? void 0 : error.data) == null ? void 0 : _a.message) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E04\u0E33\u0E16\u0E32\u0E21\u0E44\u0E14\u0E49");
      } finally {
        isLoading.value = false;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(TransitionRoot), mergeProps({
        appear: "",
        show: __props.show,
        as: "template"
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Dialog), {
              as: "div",
              onClose: ($event) => emit("close"),
              class: "relative z-50"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(unref(TransitionChild), {
                    as: "template",
                    enter: "duration-300 ease-out",
                    "enter-from": "opacity-0",
                    "enter-to": "opacity-100",
                    leave: "duration-200 ease-in",
                    "leave-from": "opacity-100",
                    "leave-to": "opacity-0"
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<div class="fixed inset-0 bg-black/25 backdrop-blur-sm"${_scopeId3}></div>`);
                      } else {
                        return [
                          createVNode("div", { class: "fixed inset-0 bg-black/25 backdrop-blur-sm" })
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(`<div class="fixed inset-0 overflow-y-auto"${_scopeId2}><div class="flex min-h-full items-center justify-center p-4 text-center"${_scopeId2}>`);
                  _push3(ssrRenderComponent(unref(TransitionChild), {
                    as: "template",
                    enter: "duration-300 ease-out",
                    "enter-from": "opacity-0 scale-95",
                    "enter-to": "opacity-100 scale-100",
                    leave: "duration-200 ease-in",
                    "leave-from": "opacity-100 scale-100",
                    "leave-to": "opacity-0 scale-95"
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(ssrRenderComponent(unref(DialogPanel), { class: "w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all" }, {
                          default: withCtx((_4, _push5, _parent5, _scopeId4) => {
                            if (_push5) {
                              _push5(ssrRenderComponent(unref(DialogTitle), {
                                as: "h3",
                                class: "text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between"
                              }, {
                                default: withCtx((_5, _push6, _parent6, _scopeId5) => {
                                  if (_push6) {
                                    _push6(`<span${_scopeId5}>${ssrInterpolate(__props.question ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48")}</span><button class="text-gray-400 hover:text-gray-500"${_scopeId5}>`);
                                    _push6(ssrRenderComponent(unref(Icon), {
                                      icon: "fluent:dismiss-24-regular",
                                      class: "w-6 h-6"
                                    }, null, _parent6, _scopeId5));
                                    _push6(`</button>`);
                                  } else {
                                    return [
                                      createVNode("span", null, toDisplayString(__props.question ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48"), 1),
                                      createVNode("button", {
                                        onClick: ($event) => emit("close"),
                                        class: "text-gray-400 hover:text-gray-500"
                                      }, [
                                        createVNode(unref(Icon), {
                                          icon: "fluent:dismiss-24-regular",
                                          class: "w-6 h-6"
                                        })
                                      ], 8, ["onClick"])
                                    ];
                                  }
                                }),
                                _: 1
                              }, _parent5, _scopeId4));
                              _push5(`<div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2"${_scopeId4}><div${_scopeId4}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId4}>\u0E04\u0E33\u0E16\u0E32\u0E21</label><textarea rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E04\u0E33\u0E16\u0E32\u0E21..."${_scopeId4}>${ssrInterpolate(form.value.text)}</textarea></div><div${_scopeId4}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId4}>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A (\u0E16\u0E49\u0E32\u0E21\u0E35)</label>`);
                              if (existingImages.value.length > 0) {
                                _push5(`<div class="grid grid-cols-4 gap-2 mb-2"${_scopeId4}><!--[-->`);
                                ssrRenderList(existingImages.value, (img) => {
                                  _push5(`<div class="relative group"${_scopeId4}><img${ssrRenderAttr("src", img.full_url || img.image_url)} class="h-16 w-full object-cover rounded-lg border dark:border-gray-600"${_scopeId4}><button class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"${_scopeId4}>`);
                                  _push5(ssrRenderComponent(unref(Icon), {
                                    icon: "fluent:dismiss-12-regular",
                                    class: "w-3 h-3"
                                  }, null, _parent5, _scopeId4));
                                  _push5(`</button></div>`);
                                });
                                _push5(`<!--]--></div>`);
                              } else {
                                _push5(`<!---->`);
                              }
                              if (previewImages.value.length > 0) {
                                _push5(`<div class="grid grid-cols-4 gap-2 mb-2"${_scopeId4}><!--[-->`);
                                ssrRenderList(previewImages.value, (url, idx) => {
                                  _push5(`<div class="relative group"${_scopeId4}><img${ssrRenderAttr("src", url)} class="h-16 w-full object-cover rounded-lg border dark:border-gray-600"${_scopeId4}><button class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"${_scopeId4}>`);
                                  _push5(ssrRenderComponent(unref(Icon), {
                                    icon: "fluent:dismiss-12-regular",
                                    class: "w-3 h-3"
                                  }, null, _parent5, _scopeId4));
                                  _push5(`</button></div>`);
                                });
                                _push5(`<!--]--></div>`);
                              } else {
                                _push5(`<!---->`);
                              }
                              _push5(`<label class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"${_scopeId4}>`);
                              _push5(ssrRenderComponent(unref(Icon), {
                                icon: "fluent:image-add-24-regular",
                                class: "w-5 h-5"
                              }, null, _parent5, _scopeId4));
                              _push5(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E <input type="file" multiple accept="image/*" class="hidden"${_scopeId4}></label></div><hr class="border-gray-200 dark:border-gray-700 my-4"${_scopeId4}><div${_scopeId4}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId4}>\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A</label><div class="space-y-3"${_scopeId4}><!--[-->`);
                              ssrRenderList(form.value.options, (option, index) => {
                                _push5(`<div class="flex items-center gap-3"${_scopeId4}><div class="flex-shrink-0 pt-1 cursor-pointer" title="\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07 (\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D)"${_scopeId4}><div class="${ssrRenderClass([option.is_correct ? "bg-green-500 border-green-500 text-white" : "border-gray-300 dark:border-gray-600 hover:border-green-400", "w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200"])}"${_scopeId4}>`);
                                if (option.is_correct) {
                                  _push5(ssrRenderComponent(unref(Icon), {
                                    icon: "fluent:checkmark-16-filled",
                                    class: "w-4 h-4"
                                  }, null, _parent5, _scopeId4));
                                } else {
                                  _push5(`<!---->`);
                                }
                                _push5(`</div><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(option.is_correct) ? ssrLooseContain(option.is_correct, null) : option.is_correct) ? " checked" : ""} class="hidden"${_scopeId4}></div><div class="flex-1"${_scopeId4}>`);
                                if (option.image) {
                                  _push5(`<div class="relative mb-2 w-max group"${_scopeId4}><img${ssrRenderAttr("src", option.image)} class="h-16 w-auto object-contain rounded border bg-gray-50 dark:bg-gray-900"${_scopeId4}><button class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"${_scopeId4}>`);
                                  _push5(ssrRenderComponent(unref(Icon), {
                                    icon: "fluent:dismiss-12-regular",
                                    class: "w-3 h-3"
                                  }, null, _parent5, _scopeId4));
                                  _push5(`</button></div>`);
                                } else {
                                  _push5(`<!---->`);
                                }
                                _push5(`<div class="flex gap-2"${_scopeId4}><input type="text"${ssrRenderAttr("value", option.text)}${ssrRenderAttr("placeholder", `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`)} class="${ssrRenderClass([{ "border-green-500 ring-1 ring-green-500": option.is_correct }, "w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500 text-sm"])}"${_scopeId4}><label class="flex-shrink-0 cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"${_scopeId4}>`);
                                _push5(ssrRenderComponent(unref(Icon), {
                                  icon: "fluent:image-20-regular",
                                  class: "w-5 h-5"
                                }, null, _parent5, _scopeId4));
                                _push5(`<input type="file" accept="image/*" class="hidden"${_scopeId4}></label></div></div>`);
                                if (form.value.options.length > 2) {
                                  _push5(`<button class="text-gray-400 hover:text-red-500 p-1"${_scopeId4}>`);
                                  _push5(ssrRenderComponent(unref(Icon), {
                                    icon: "fluent:delete-20-regular",
                                    class: "w-5 h-5"
                                  }, null, _parent5, _scopeId4));
                                  _push5(`</button>`);
                                } else {
                                  _push5(`<!---->`);
                                }
                                _push5(`</div>`);
                              });
                              _push5(`<!--]--></div><button class="mt-3 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium"${_scopeId4}>`);
                              _push5(ssrRenderComponent(unref(Icon), {
                                icon: "fluent:add-circle-20-filled",
                                class: "w-5 h-5"
                              }, null, _parent5, _scopeId4));
                              _push5(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 </button></div><hr class="border-gray-200 dark:border-gray-700 my-4"${_scopeId4}><div${_scopeId4}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"${_scopeId4}>\u0E04\u0E30\u0E41\u0E19\u0E19</label><input type="number"${ssrRenderAttr("value", form.value.points)} class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500" min="1"${_scopeId4}></div></div><div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700"${_scopeId4}><button type="button" class="inline-flex justify-center rounded-lg border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""}${_scopeId4}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="button" class="inline-flex justify-center rounded-lg border border-transparent bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 flex items-center gap-2"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""}${_scopeId4}>`);
                              if (isLoading.value) {
                                _push5(ssrRenderComponent(unref(Icon), {
                                  icon: "eos-icons:loading",
                                  class: "w-4 h-4 animate-spin"
                                }, null, _parent5, _scopeId4));
                              } else {
                                _push5(`<!---->`);
                              }
                              _push5(`<span${_scopeId4}>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01</span></button></div>`);
                            } else {
                              return [
                                createVNode(unref(DialogTitle), {
                                  as: "h3",
                                  class: "text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between"
                                }, {
                                  default: withCtx(() => [
                                    createVNode("span", null, toDisplayString(__props.question ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48"), 1),
                                    createVNode("button", {
                                      onClick: ($event) => emit("close"),
                                      class: "text-gray-400 hover:text-gray-500"
                                    }, [
                                      createVNode(unref(Icon), {
                                        icon: "fluent:dismiss-24-regular",
                                        class: "w-6 h-6"
                                      })
                                    ], 8, ["onClick"])
                                  ]),
                                  _: 1
                                }),
                                createVNode("div", { class: "space-y-4 max-h-[70vh] overflow-y-auto pr-2" }, [
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E33\u0E16\u0E32\u0E21"),
                                    withDirectives(createVNode("textarea", {
                                      "onUpdate:modelValue": ($event) => form.value.text = $event,
                                      rows: "3",
                                      class: "w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                      placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E04\u0E33\u0E16\u0E32\u0E21..."
                                    }, null, 8, ["onUpdate:modelValue"]), [
                                      [vModelText, form.value.text]
                                    ])
                                  ]),
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A (\u0E16\u0E49\u0E32\u0E21\u0E35)"),
                                    existingImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                      key: 0,
                                      class: "grid grid-cols-4 gap-2 mb-2"
                                    }, [
                                      (openBlock(true), createBlock(Fragment, null, renderList(existingImages.value, (img) => {
                                        return openBlock(), createBlock("div", {
                                          key: img.id,
                                          class: "relative group"
                                        }, [
                                          createVNode("img", {
                                            src: img.full_url || img.image_url,
                                            class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                          }, null, 8, ["src"]),
                                          createVNode("button", {
                                            onClick: ($event) => removeExistingImage(img.id),
                                            class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                          }, [
                                            createVNode(unref(Icon), {
                                              icon: "fluent:dismiss-12-regular",
                                              class: "w-3 h-3"
                                            })
                                          ], 8, ["onClick"])
                                        ]);
                                      }), 128))
                                    ])) : createCommentVNode("", true),
                                    previewImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                      key: 1,
                                      class: "grid grid-cols-4 gap-2 mb-2"
                                    }, [
                                      (openBlock(true), createBlock(Fragment, null, renderList(previewImages.value, (url, idx) => {
                                        return openBlock(), createBlock("div", {
                                          key: idx,
                                          class: "relative group"
                                        }, [
                                          createVNode("img", {
                                            src: url,
                                            class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                          }, null, 8, ["src"]),
                                          createVNode("button", {
                                            onClick: ($event) => removeFile(idx),
                                            class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                          }, [
                                            createVNode(unref(Icon), {
                                              icon: "fluent:dismiss-12-regular",
                                              class: "w-3 h-3"
                                            })
                                          ], 8, ["onClick"])
                                        ]);
                                      }), 128))
                                    ])) : createCommentVNode("", true),
                                    createVNode("label", { class: "cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" }, [
                                      createVNode(unref(Icon), {
                                        icon: "fluent:image-add-24-regular",
                                        class: "w-5 h-5"
                                      }),
                                      createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E "),
                                      createVNode("input", {
                                        type: "file",
                                        multiple: "",
                                        accept: "image/*",
                                        class: "hidden",
                                        onChange: handleFileSelect
                                      }, null, 32)
                                    ])
                                  ]),
                                  createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A"),
                                    createVNode("div", { class: "space-y-3" }, [
                                      (openBlock(true), createBlock(Fragment, null, renderList(form.value.options, (option, index) => {
                                        return openBlock(), createBlock("div", {
                                          key: index,
                                          class: "flex items-center gap-3"
                                        }, [
                                          createVNode("div", {
                                            class: "flex-shrink-0 pt-1 cursor-pointer",
                                            onClick: ($event) => option.is_correct = !option.is_correct,
                                            title: "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07 (\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D)"
                                          }, [
                                            createVNode("div", {
                                              class: ["w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200", option.is_correct ? "bg-green-500 border-green-500 text-white" : "border-gray-300 dark:border-gray-600 hover:border-green-400"]
                                            }, [
                                              option.is_correct ? (openBlock(), createBlock(unref(Icon), {
                                                key: 0,
                                                icon: "fluent:checkmark-16-filled",
                                                class: "w-4 h-4"
                                              })) : createCommentVNode("", true)
                                            ], 2),
                                            withDirectives(createVNode("input", {
                                              type: "checkbox",
                                              "onUpdate:modelValue": ($event) => option.is_correct = $event,
                                              class: "hidden"
                                            }, null, 8, ["onUpdate:modelValue"]), [
                                              [vModelCheckbox, option.is_correct]
                                            ])
                                          ], 8, ["onClick"]),
                                          createVNode("div", { class: "flex-1" }, [
                                            option.image ? (openBlock(), createBlock("div", {
                                              key: 0,
                                              class: "relative mb-2 w-max group"
                                            }, [
                                              createVNode("img", {
                                                src: option.image,
                                                class: "h-16 w-auto object-contain rounded border bg-gray-50 dark:bg-gray-900"
                                              }, null, 8, ["src"]),
                                              createVNode("button", {
                                                onClick: ($event) => removeOptionImage(index),
                                                class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                              }, [
                                                createVNode(unref(Icon), {
                                                  icon: "fluent:dismiss-12-regular",
                                                  class: "w-3 h-3"
                                                })
                                              ], 8, ["onClick"])
                                            ])) : createCommentVNode("", true),
                                            createVNode("div", { class: "flex gap-2" }, [
                                              withDirectives(createVNode("input", {
                                                type: "text",
                                                "onUpdate:modelValue": ($event) => option.text = $event,
                                                class: ["w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500 text-sm", { "border-green-500 ring-1 ring-green-500": option.is_correct }],
                                                placeholder: `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`
                                              }, null, 10, ["onUpdate:modelValue", "placeholder"]), [
                                                [vModelText, option.text]
                                              ]),
                                              createVNode("label", {
                                                class: "flex-shrink-0 cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400",
                                                title: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"
                                              }, [
                                                createVNode(unref(Icon), {
                                                  icon: "fluent:image-20-regular",
                                                  class: "w-5 h-5"
                                                }),
                                                createVNode("input", {
                                                  type: "file",
                                                  accept: "image/*",
                                                  class: "hidden",
                                                  onChange: (e) => handleOptionFileSelect(e, index)
                                                }, null, 40, ["onChange"])
                                              ])
                                            ])
                                          ]),
                                          form.value.options.length > 2 ? (openBlock(), createBlock("button", {
                                            key: 0,
                                            onClick: ($event) => removeOption(index),
                                            class: "text-gray-400 hover:text-red-500 p-1"
                                          }, [
                                            createVNode(unref(Icon), {
                                              icon: "fluent:delete-20-regular",
                                              class: "w-5 h-5"
                                            })
                                          ], 8, ["onClick"])) : createCommentVNode("", true)
                                        ]);
                                      }), 128))
                                    ]),
                                    createVNode("button", {
                                      onClick: addOption,
                                      class: "mt-3 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium"
                                    }, [
                                      createVNode(unref(Icon), {
                                        icon: "fluent:add-circle-20-filled",
                                        class: "w-5 h-5"
                                      }),
                                      createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 ")
                                    ])
                                  ]),
                                  createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E30\u0E41\u0E19\u0E19"),
                                    withDirectives(createVNode("input", {
                                      type: "number",
                                      "onUpdate:modelValue": ($event) => form.value.points = $event,
                                      class: "w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                      min: "1"
                                    }, null, 8, ["onUpdate:modelValue"]), [
                                      [vModelText, form.value.points]
                                    ])
                                  ])
                                ]),
                                createVNode("div", { class: "mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700" }, [
                                  createVNode("button", {
                                    type: "button",
                                    class: "inline-flex justify-center rounded-lg border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none",
                                    onClick: ($event) => emit("close"),
                                    disabled: isLoading.value
                                  }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick", "disabled"]),
                                  createVNode("button", {
                                    type: "button",
                                    class: "inline-flex justify-center rounded-lg border border-transparent bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 flex items-center gap-2",
                                    onClick: onSubmit,
                                    disabled: isLoading.value
                                  }, [
                                    isLoading.value ? (openBlock(), createBlock(unref(Icon), {
                                      key: 0,
                                      icon: "eos-icons:loading",
                                      class: "w-4 h-4 animate-spin"
                                    })) : createCommentVNode("", true),
                                    createVNode("span", null, "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")
                                  ], 8, ["disabled"])
                                ])
                              ];
                            }
                          }),
                          _: 1
                        }, _parent4, _scopeId3));
                      } else {
                        return [
                          createVNode(unref(DialogPanel), { class: "w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all" }, {
                            default: withCtx(() => [
                              createVNode(unref(DialogTitle), {
                                as: "h3",
                                class: "text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between"
                              }, {
                                default: withCtx(() => [
                                  createVNode("span", null, toDisplayString(__props.question ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48"), 1),
                                  createVNode("button", {
                                    onClick: ($event) => emit("close"),
                                    class: "text-gray-400 hover:text-gray-500"
                                  }, [
                                    createVNode(unref(Icon), {
                                      icon: "fluent:dismiss-24-regular",
                                      class: "w-6 h-6"
                                    })
                                  ], 8, ["onClick"])
                                ]),
                                _: 1
                              }),
                              createVNode("div", { class: "space-y-4 max-h-[70vh] overflow-y-auto pr-2" }, [
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E33\u0E16\u0E32\u0E21"),
                                  withDirectives(createVNode("textarea", {
                                    "onUpdate:modelValue": ($event) => form.value.text = $event,
                                    rows: "3",
                                    class: "w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                    placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E04\u0E33\u0E16\u0E32\u0E21..."
                                  }, null, 8, ["onUpdate:modelValue"]), [
                                    [vModelText, form.value.text]
                                  ])
                                ]),
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A (\u0E16\u0E49\u0E32\u0E21\u0E35)"),
                                  existingImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                    key: 0,
                                    class: "grid grid-cols-4 gap-2 mb-2"
                                  }, [
                                    (openBlock(true), createBlock(Fragment, null, renderList(existingImages.value, (img) => {
                                      return openBlock(), createBlock("div", {
                                        key: img.id,
                                        class: "relative group"
                                      }, [
                                        createVNode("img", {
                                          src: img.full_url || img.image_url,
                                          class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                        }, null, 8, ["src"]),
                                        createVNode("button", {
                                          onClick: ($event) => removeExistingImage(img.id),
                                          class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                        }, [
                                          createVNode(unref(Icon), {
                                            icon: "fluent:dismiss-12-regular",
                                            class: "w-3 h-3"
                                          })
                                        ], 8, ["onClick"])
                                      ]);
                                    }), 128))
                                  ])) : createCommentVNode("", true),
                                  previewImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                    key: 1,
                                    class: "grid grid-cols-4 gap-2 mb-2"
                                  }, [
                                    (openBlock(true), createBlock(Fragment, null, renderList(previewImages.value, (url, idx) => {
                                      return openBlock(), createBlock("div", {
                                        key: idx,
                                        class: "relative group"
                                      }, [
                                        createVNode("img", {
                                          src: url,
                                          class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                        }, null, 8, ["src"]),
                                        createVNode("button", {
                                          onClick: ($event) => removeFile(idx),
                                          class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                        }, [
                                          createVNode(unref(Icon), {
                                            icon: "fluent:dismiss-12-regular",
                                            class: "w-3 h-3"
                                          })
                                        ], 8, ["onClick"])
                                      ]);
                                    }), 128))
                                  ])) : createCommentVNode("", true),
                                  createVNode("label", { class: "cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" }, [
                                    createVNode(unref(Icon), {
                                      icon: "fluent:image-add-24-regular",
                                      class: "w-5 h-5"
                                    }),
                                    createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E "),
                                    createVNode("input", {
                                      type: "file",
                                      multiple: "",
                                      accept: "image/*",
                                      class: "hidden",
                                      onChange: handleFileSelect
                                    }, null, 32)
                                  ])
                                ]),
                                createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A"),
                                  createVNode("div", { class: "space-y-3" }, [
                                    (openBlock(true), createBlock(Fragment, null, renderList(form.value.options, (option, index) => {
                                      return openBlock(), createBlock("div", {
                                        key: index,
                                        class: "flex items-center gap-3"
                                      }, [
                                        createVNode("div", {
                                          class: "flex-shrink-0 pt-1 cursor-pointer",
                                          onClick: ($event) => option.is_correct = !option.is_correct,
                                          title: "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07 (\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D)"
                                        }, [
                                          createVNode("div", {
                                            class: ["w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200", option.is_correct ? "bg-green-500 border-green-500 text-white" : "border-gray-300 dark:border-gray-600 hover:border-green-400"]
                                          }, [
                                            option.is_correct ? (openBlock(), createBlock(unref(Icon), {
                                              key: 0,
                                              icon: "fluent:checkmark-16-filled",
                                              class: "w-4 h-4"
                                            })) : createCommentVNode("", true)
                                          ], 2),
                                          withDirectives(createVNode("input", {
                                            type: "checkbox",
                                            "onUpdate:modelValue": ($event) => option.is_correct = $event,
                                            class: "hidden"
                                          }, null, 8, ["onUpdate:modelValue"]), [
                                            [vModelCheckbox, option.is_correct]
                                          ])
                                        ], 8, ["onClick"]),
                                        createVNode("div", { class: "flex-1" }, [
                                          option.image ? (openBlock(), createBlock("div", {
                                            key: 0,
                                            class: "relative mb-2 w-max group"
                                          }, [
                                            createVNode("img", {
                                              src: option.image,
                                              class: "h-16 w-auto object-contain rounded border bg-gray-50 dark:bg-gray-900"
                                            }, null, 8, ["src"]),
                                            createVNode("button", {
                                              onClick: ($event) => removeOptionImage(index),
                                              class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                            }, [
                                              createVNode(unref(Icon), {
                                                icon: "fluent:dismiss-12-regular",
                                                class: "w-3 h-3"
                                              })
                                            ], 8, ["onClick"])
                                          ])) : createCommentVNode("", true),
                                          createVNode("div", { class: "flex gap-2" }, [
                                            withDirectives(createVNode("input", {
                                              type: "text",
                                              "onUpdate:modelValue": ($event) => option.text = $event,
                                              class: ["w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500 text-sm", { "border-green-500 ring-1 ring-green-500": option.is_correct }],
                                              placeholder: `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`
                                            }, null, 10, ["onUpdate:modelValue", "placeholder"]), [
                                              [vModelText, option.text]
                                            ]),
                                            createVNode("label", {
                                              class: "flex-shrink-0 cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400",
                                              title: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"
                                            }, [
                                              createVNode(unref(Icon), {
                                                icon: "fluent:image-20-regular",
                                                class: "w-5 h-5"
                                              }),
                                              createVNode("input", {
                                                type: "file",
                                                accept: "image/*",
                                                class: "hidden",
                                                onChange: (e) => handleOptionFileSelect(e, index)
                                              }, null, 40, ["onChange"])
                                            ])
                                          ])
                                        ]),
                                        form.value.options.length > 2 ? (openBlock(), createBlock("button", {
                                          key: 0,
                                          onClick: ($event) => removeOption(index),
                                          class: "text-gray-400 hover:text-red-500 p-1"
                                        }, [
                                          createVNode(unref(Icon), {
                                            icon: "fluent:delete-20-regular",
                                            class: "w-5 h-5"
                                          })
                                        ], 8, ["onClick"])) : createCommentVNode("", true)
                                      ]);
                                    }), 128))
                                  ]),
                                  createVNode("button", {
                                    onClick: addOption,
                                    class: "mt-3 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium"
                                  }, [
                                    createVNode(unref(Icon), {
                                      icon: "fluent:add-circle-20-filled",
                                      class: "w-5 h-5"
                                    }),
                                    createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 ")
                                  ])
                                ]),
                                createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E30\u0E41\u0E19\u0E19"),
                                  withDirectives(createVNode("input", {
                                    type: "number",
                                    "onUpdate:modelValue": ($event) => form.value.points = $event,
                                    class: "w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                    min: "1"
                                  }, null, 8, ["onUpdate:modelValue"]), [
                                    [vModelText, form.value.points]
                                  ])
                                ])
                              ]),
                              createVNode("div", { class: "mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700" }, [
                                createVNode("button", {
                                  type: "button",
                                  class: "inline-flex justify-center rounded-lg border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none",
                                  onClick: ($event) => emit("close"),
                                  disabled: isLoading.value
                                }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick", "disabled"]),
                                createVNode("button", {
                                  type: "button",
                                  class: "inline-flex justify-center rounded-lg border border-transparent bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 flex items-center gap-2",
                                  onClick: onSubmit,
                                  disabled: isLoading.value
                                }, [
                                  isLoading.value ? (openBlock(), createBlock(unref(Icon), {
                                    key: 0,
                                    icon: "eos-icons:loading",
                                    class: "w-4 h-4 animate-spin"
                                  })) : createCommentVNode("", true),
                                  createVNode("span", null, "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")
                                ], 8, ["disabled"])
                              ])
                            ]),
                            _: 1
                          })
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(`</div></div>`);
                } else {
                  return [
                    createVNode(unref(TransitionChild), {
                      as: "template",
                      enter: "duration-300 ease-out",
                      "enter-from": "opacity-0",
                      "enter-to": "opacity-100",
                      leave: "duration-200 ease-in",
                      "leave-from": "opacity-100",
                      "leave-to": "opacity-0"
                    }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "fixed inset-0 bg-black/25 backdrop-blur-sm" })
                      ]),
                      _: 1
                    }),
                    createVNode("div", { class: "fixed inset-0 overflow-y-auto" }, [
                      createVNode("div", { class: "flex min-h-full items-center justify-center p-4 text-center" }, [
                        createVNode(unref(TransitionChild), {
                          as: "template",
                          enter: "duration-300 ease-out",
                          "enter-from": "opacity-0 scale-95",
                          "enter-to": "opacity-100 scale-100",
                          leave: "duration-200 ease-in",
                          "leave-from": "opacity-100 scale-100",
                          "leave-to": "opacity-0 scale-95"
                        }, {
                          default: withCtx(() => [
                            createVNode(unref(DialogPanel), { class: "w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all" }, {
                              default: withCtx(() => [
                                createVNode(unref(DialogTitle), {
                                  as: "h3",
                                  class: "text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between"
                                }, {
                                  default: withCtx(() => [
                                    createVNode("span", null, toDisplayString(__props.question ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48"), 1),
                                    createVNode("button", {
                                      onClick: ($event) => emit("close"),
                                      class: "text-gray-400 hover:text-gray-500"
                                    }, [
                                      createVNode(unref(Icon), {
                                        icon: "fluent:dismiss-24-regular",
                                        class: "w-6 h-6"
                                      })
                                    ], 8, ["onClick"])
                                  ]),
                                  _: 1
                                }),
                                createVNode("div", { class: "space-y-4 max-h-[70vh] overflow-y-auto pr-2" }, [
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E33\u0E16\u0E32\u0E21"),
                                    withDirectives(createVNode("textarea", {
                                      "onUpdate:modelValue": ($event) => form.value.text = $event,
                                      rows: "3",
                                      class: "w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                      placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E04\u0E33\u0E16\u0E32\u0E21..."
                                    }, null, 8, ["onUpdate:modelValue"]), [
                                      [vModelText, form.value.text]
                                    ])
                                  ]),
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A (\u0E16\u0E49\u0E32\u0E21\u0E35)"),
                                    existingImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                      key: 0,
                                      class: "grid grid-cols-4 gap-2 mb-2"
                                    }, [
                                      (openBlock(true), createBlock(Fragment, null, renderList(existingImages.value, (img) => {
                                        return openBlock(), createBlock("div", {
                                          key: img.id,
                                          class: "relative group"
                                        }, [
                                          createVNode("img", {
                                            src: img.full_url || img.image_url,
                                            class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                          }, null, 8, ["src"]),
                                          createVNode("button", {
                                            onClick: ($event) => removeExistingImage(img.id),
                                            class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                          }, [
                                            createVNode(unref(Icon), {
                                              icon: "fluent:dismiss-12-regular",
                                              class: "w-3 h-3"
                                            })
                                          ], 8, ["onClick"])
                                        ]);
                                      }), 128))
                                    ])) : createCommentVNode("", true),
                                    previewImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                      key: 1,
                                      class: "grid grid-cols-4 gap-2 mb-2"
                                    }, [
                                      (openBlock(true), createBlock(Fragment, null, renderList(previewImages.value, (url, idx) => {
                                        return openBlock(), createBlock("div", {
                                          key: idx,
                                          class: "relative group"
                                        }, [
                                          createVNode("img", {
                                            src: url,
                                            class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                          }, null, 8, ["src"]),
                                          createVNode("button", {
                                            onClick: ($event) => removeFile(idx),
                                            class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                          }, [
                                            createVNode(unref(Icon), {
                                              icon: "fluent:dismiss-12-regular",
                                              class: "w-3 h-3"
                                            })
                                          ], 8, ["onClick"])
                                        ]);
                                      }), 128))
                                    ])) : createCommentVNode("", true),
                                    createVNode("label", { class: "cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" }, [
                                      createVNode(unref(Icon), {
                                        icon: "fluent:image-add-24-regular",
                                        class: "w-5 h-5"
                                      }),
                                      createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E "),
                                      createVNode("input", {
                                        type: "file",
                                        multiple: "",
                                        accept: "image/*",
                                        class: "hidden",
                                        onChange: handleFileSelect
                                      }, null, 32)
                                    ])
                                  ]),
                                  createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A"),
                                    createVNode("div", { class: "space-y-3" }, [
                                      (openBlock(true), createBlock(Fragment, null, renderList(form.value.options, (option, index) => {
                                        return openBlock(), createBlock("div", {
                                          key: index,
                                          class: "flex items-center gap-3"
                                        }, [
                                          createVNode("div", {
                                            class: "flex-shrink-0 pt-1 cursor-pointer",
                                            onClick: ($event) => option.is_correct = !option.is_correct,
                                            title: "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07 (\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D)"
                                          }, [
                                            createVNode("div", {
                                              class: ["w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200", option.is_correct ? "bg-green-500 border-green-500 text-white" : "border-gray-300 dark:border-gray-600 hover:border-green-400"]
                                            }, [
                                              option.is_correct ? (openBlock(), createBlock(unref(Icon), {
                                                key: 0,
                                                icon: "fluent:checkmark-16-filled",
                                                class: "w-4 h-4"
                                              })) : createCommentVNode("", true)
                                            ], 2),
                                            withDirectives(createVNode("input", {
                                              type: "checkbox",
                                              "onUpdate:modelValue": ($event) => option.is_correct = $event,
                                              class: "hidden"
                                            }, null, 8, ["onUpdate:modelValue"]), [
                                              [vModelCheckbox, option.is_correct]
                                            ])
                                          ], 8, ["onClick"]),
                                          createVNode("div", { class: "flex-1" }, [
                                            option.image ? (openBlock(), createBlock("div", {
                                              key: 0,
                                              class: "relative mb-2 w-max group"
                                            }, [
                                              createVNode("img", {
                                                src: option.image,
                                                class: "h-16 w-auto object-contain rounded border bg-gray-50 dark:bg-gray-900"
                                              }, null, 8, ["src"]),
                                              createVNode("button", {
                                                onClick: ($event) => removeOptionImage(index),
                                                class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                              }, [
                                                createVNode(unref(Icon), {
                                                  icon: "fluent:dismiss-12-regular",
                                                  class: "w-3 h-3"
                                                })
                                              ], 8, ["onClick"])
                                            ])) : createCommentVNode("", true),
                                            createVNode("div", { class: "flex gap-2" }, [
                                              withDirectives(createVNode("input", {
                                                type: "text",
                                                "onUpdate:modelValue": ($event) => option.text = $event,
                                                class: ["w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500 text-sm", { "border-green-500 ring-1 ring-green-500": option.is_correct }],
                                                placeholder: `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`
                                              }, null, 10, ["onUpdate:modelValue", "placeholder"]), [
                                                [vModelText, option.text]
                                              ]),
                                              createVNode("label", {
                                                class: "flex-shrink-0 cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400",
                                                title: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"
                                              }, [
                                                createVNode(unref(Icon), {
                                                  icon: "fluent:image-20-regular",
                                                  class: "w-5 h-5"
                                                }),
                                                createVNode("input", {
                                                  type: "file",
                                                  accept: "image/*",
                                                  class: "hidden",
                                                  onChange: (e) => handleOptionFileSelect(e, index)
                                                }, null, 40, ["onChange"])
                                              ])
                                            ])
                                          ]),
                                          form.value.options.length > 2 ? (openBlock(), createBlock("button", {
                                            key: 0,
                                            onClick: ($event) => removeOption(index),
                                            class: "text-gray-400 hover:text-red-500 p-1"
                                          }, [
                                            createVNode(unref(Icon), {
                                              icon: "fluent:delete-20-regular",
                                              class: "w-5 h-5"
                                            })
                                          ], 8, ["onClick"])) : createCommentVNode("", true)
                                        ]);
                                      }), 128))
                                    ]),
                                    createVNode("button", {
                                      onClick: addOption,
                                      class: "mt-3 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium"
                                    }, [
                                      createVNode(unref(Icon), {
                                        icon: "fluent:add-circle-20-filled",
                                        class: "w-5 h-5"
                                      }),
                                      createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 ")
                                    ])
                                  ]),
                                  createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                  createVNode("div", null, [
                                    createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E30\u0E41\u0E19\u0E19"),
                                    withDirectives(createVNode("input", {
                                      type: "number",
                                      "onUpdate:modelValue": ($event) => form.value.points = $event,
                                      class: "w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                      min: "1"
                                    }, null, 8, ["onUpdate:modelValue"]), [
                                      [vModelText, form.value.points]
                                    ])
                                  ])
                                ]),
                                createVNode("div", { class: "mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700" }, [
                                  createVNode("button", {
                                    type: "button",
                                    class: "inline-flex justify-center rounded-lg border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none",
                                    onClick: ($event) => emit("close"),
                                    disabled: isLoading.value
                                  }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick", "disabled"]),
                                  createVNode("button", {
                                    type: "button",
                                    class: "inline-flex justify-center rounded-lg border border-transparent bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 flex items-center gap-2",
                                    onClick: onSubmit,
                                    disabled: isLoading.value
                                  }, [
                                    isLoading.value ? (openBlock(), createBlock(unref(Icon), {
                                      key: 0,
                                      icon: "eos-icons:loading",
                                      class: "w-4 h-4 animate-spin"
                                    })) : createCommentVNode("", true),
                                    createVNode("span", null, "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")
                                  ], 8, ["disabled"])
                                ])
                              ]),
                              _: 1
                            })
                          ]),
                          _: 1
                        })
                      ])
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Dialog), {
                as: "div",
                onClose: ($event) => emit("close"),
                class: "relative z-50"
              }, {
                default: withCtx(() => [
                  createVNode(unref(TransitionChild), {
                    as: "template",
                    enter: "duration-300 ease-out",
                    "enter-from": "opacity-0",
                    "enter-to": "opacity-100",
                    leave: "duration-200 ease-in",
                    "leave-from": "opacity-100",
                    "leave-to": "opacity-0"
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "fixed inset-0 bg-black/25 backdrop-blur-sm" })
                    ]),
                    _: 1
                  }),
                  createVNode("div", { class: "fixed inset-0 overflow-y-auto" }, [
                    createVNode("div", { class: "flex min-h-full items-center justify-center p-4 text-center" }, [
                      createVNode(unref(TransitionChild), {
                        as: "template",
                        enter: "duration-300 ease-out",
                        "enter-from": "opacity-0 scale-95",
                        "enter-to": "opacity-100 scale-100",
                        leave: "duration-200 ease-in",
                        "leave-from": "opacity-100 scale-100",
                        "leave-to": "opacity-0 scale-95"
                      }, {
                        default: withCtx(() => [
                          createVNode(unref(DialogPanel), { class: "w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all" }, {
                            default: withCtx(() => [
                              createVNode(unref(DialogTitle), {
                                as: "h3",
                                class: "text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between"
                              }, {
                                default: withCtx(() => [
                                  createVNode("span", null, toDisplayString(__props.question ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E16\u0E32\u0E21" : "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48"), 1),
                                  createVNode("button", {
                                    onClick: ($event) => emit("close"),
                                    class: "text-gray-400 hover:text-gray-500"
                                  }, [
                                    createVNode(unref(Icon), {
                                      icon: "fluent:dismiss-24-regular",
                                      class: "w-6 h-6"
                                    })
                                  ], 8, ["onClick"])
                                ]),
                                _: 1
                              }),
                              createVNode("div", { class: "space-y-4 max-h-[70vh] overflow-y-auto pr-2" }, [
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E33\u0E16\u0E32\u0E21"),
                                  withDirectives(createVNode("textarea", {
                                    "onUpdate:modelValue": ($event) => form.value.text = $event,
                                    rows: "3",
                                    class: "w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                    placeholder: "\u0E23\u0E30\u0E1A\u0E38\u0E04\u0E33\u0E16\u0E32\u0E21..."
                                  }, null, 8, ["onUpdate:modelValue"]), [
                                    [vModelText, form.value.text]
                                  ])
                                ]),
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A (\u0E16\u0E49\u0E32\u0E21\u0E35)"),
                                  existingImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                    key: 0,
                                    class: "grid grid-cols-4 gap-2 mb-2"
                                  }, [
                                    (openBlock(true), createBlock(Fragment, null, renderList(existingImages.value, (img) => {
                                      return openBlock(), createBlock("div", {
                                        key: img.id,
                                        class: "relative group"
                                      }, [
                                        createVNode("img", {
                                          src: img.full_url || img.image_url,
                                          class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                        }, null, 8, ["src"]),
                                        createVNode("button", {
                                          onClick: ($event) => removeExistingImage(img.id),
                                          class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                        }, [
                                          createVNode(unref(Icon), {
                                            icon: "fluent:dismiss-12-regular",
                                            class: "w-3 h-3"
                                          })
                                        ], 8, ["onClick"])
                                      ]);
                                    }), 128))
                                  ])) : createCommentVNode("", true),
                                  previewImages.value.length > 0 ? (openBlock(), createBlock("div", {
                                    key: 1,
                                    class: "grid grid-cols-4 gap-2 mb-2"
                                  }, [
                                    (openBlock(true), createBlock(Fragment, null, renderList(previewImages.value, (url, idx) => {
                                      return openBlock(), createBlock("div", {
                                        key: idx,
                                        class: "relative group"
                                      }, [
                                        createVNode("img", {
                                          src: url,
                                          class: "h-16 w-full object-cover rounded-lg border dark:border-gray-600"
                                        }, null, 8, ["src"]),
                                        createVNode("button", {
                                          onClick: ($event) => removeFile(idx),
                                          class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                        }, [
                                          createVNode(unref(Icon), {
                                            icon: "fluent:dismiss-12-regular",
                                            class: "w-3 h-3"
                                          })
                                        ], 8, ["onClick"])
                                      ]);
                                    }), 128))
                                  ])) : createCommentVNode("", true),
                                  createVNode("label", { class: "cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" }, [
                                    createVNode(unref(Icon), {
                                      icon: "fluent:image-add-24-regular",
                                      class: "w-5 h-5"
                                    }),
                                    createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E "),
                                    createVNode("input", {
                                      type: "file",
                                      multiple: "",
                                      accept: "image/*",
                                      class: "hidden",
                                      onChange: handleFileSelect
                                    }, null, 32)
                                  ])
                                ]),
                                createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E15\u0E2D\u0E1A"),
                                  createVNode("div", { class: "space-y-3" }, [
                                    (openBlock(true), createBlock(Fragment, null, renderList(form.value.options, (option, index) => {
                                      return openBlock(), createBlock("div", {
                                        key: index,
                                        class: "flex items-center gap-3"
                                      }, [
                                        createVNode("div", {
                                          class: "flex-shrink-0 pt-1 cursor-pointer",
                                          onClick: ($event) => option.is_correct = !option.is_correct,
                                          title: "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E17\u0E35\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07 (\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E14\u0E49\u0E21\u0E32\u0E01\u0E01\u0E27\u0E48\u0E32 1 \u0E02\u0E49\u0E2D)"
                                        }, [
                                          createVNode("div", {
                                            class: ["w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200", option.is_correct ? "bg-green-500 border-green-500 text-white" : "border-gray-300 dark:border-gray-600 hover:border-green-400"]
                                          }, [
                                            option.is_correct ? (openBlock(), createBlock(unref(Icon), {
                                              key: 0,
                                              icon: "fluent:checkmark-16-filled",
                                              class: "w-4 h-4"
                                            })) : createCommentVNode("", true)
                                          ], 2),
                                          withDirectives(createVNode("input", {
                                            type: "checkbox",
                                            "onUpdate:modelValue": ($event) => option.is_correct = $event,
                                            class: "hidden"
                                          }, null, 8, ["onUpdate:modelValue"]), [
                                            [vModelCheckbox, option.is_correct]
                                          ])
                                        ], 8, ["onClick"]),
                                        createVNode("div", { class: "flex-1" }, [
                                          option.image ? (openBlock(), createBlock("div", {
                                            key: 0,
                                            class: "relative mb-2 w-max group"
                                          }, [
                                            createVNode("img", {
                                              src: option.image,
                                              class: "h-16 w-auto object-contain rounded border bg-gray-50 dark:bg-gray-900"
                                            }, null, 8, ["src"]),
                                            createVNode("button", {
                                              onClick: ($event) => removeOptionImage(index),
                                              class: "absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                            }, [
                                              createVNode(unref(Icon), {
                                                icon: "fluent:dismiss-12-regular",
                                                class: "w-3 h-3"
                                              })
                                            ], 8, ["onClick"])
                                          ])) : createCommentVNode("", true),
                                          createVNode("div", { class: "flex gap-2" }, [
                                            withDirectives(createVNode("input", {
                                              type: "text",
                                              "onUpdate:modelValue": ($event) => option.text = $event,
                                              class: ["w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500 text-sm", { "border-green-500 ring-1 ring-green-500": option.is_correct }],
                                              placeholder: `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`
                                            }, null, 10, ["onUpdate:modelValue", "placeholder"]), [
                                              [vModelText, option.text]
                                            ]),
                                            createVNode("label", {
                                              class: "flex-shrink-0 cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400",
                                              title: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E"
                                            }, [
                                              createVNode(unref(Icon), {
                                                icon: "fluent:image-20-regular",
                                                class: "w-5 h-5"
                                              }),
                                              createVNode("input", {
                                                type: "file",
                                                accept: "image/*",
                                                class: "hidden",
                                                onChange: (e) => handleOptionFileSelect(e, index)
                                              }, null, 40, ["onChange"])
                                            ])
                                          ])
                                        ]),
                                        form.value.options.length > 2 ? (openBlock(), createBlock("button", {
                                          key: 0,
                                          onClick: ($event) => removeOption(index),
                                          class: "text-gray-400 hover:text-red-500 p-1"
                                        }, [
                                          createVNode(unref(Icon), {
                                            icon: "fluent:delete-20-regular",
                                            class: "w-5 h-5"
                                          })
                                        ], 8, ["onClick"])) : createCommentVNode("", true)
                                      ]);
                                    }), 128))
                                  ]),
                                  createVNode("button", {
                                    onClick: addOption,
                                    class: "mt-3 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium"
                                  }, [
                                    createVNode(unref(Icon), {
                                      icon: "fluent:add-circle-20-filled",
                                      class: "w-5 h-5"
                                    }),
                                    createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 ")
                                  ])
                                ]),
                                createVNode("hr", { class: "border-gray-200 dark:border-gray-700 my-4" }),
                                createVNode("div", null, [
                                  createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" }, "\u0E04\u0E30\u0E41\u0E19\u0E19"),
                                  withDirectives(createVNode("input", {
                                    type: "number",
                                    "onUpdate:modelValue": ($event) => form.value.points = $event,
                                    class: "w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500",
                                    min: "1"
                                  }, null, 8, ["onUpdate:modelValue"]), [
                                    [vModelText, form.value.points]
                                  ])
                                ])
                              ]),
                              createVNode("div", { class: "mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700" }, [
                                createVNode("button", {
                                  type: "button",
                                  class: "inline-flex justify-center rounded-lg border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none",
                                  onClick: ($event) => emit("close"),
                                  disabled: isLoading.value
                                }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick", "disabled"]),
                                createVNode("button", {
                                  type: "button",
                                  class: "inline-flex justify-center rounded-lg border border-transparent bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 flex items-center gap-2",
                                  onClick: onSubmit,
                                  disabled: isLoading.value
                                }, [
                                  isLoading.value ? (openBlock(), createBlock(unref(Icon), {
                                    key: 0,
                                    icon: "eos-icons:loading",
                                    class: "w-4 h-4 animate-spin"
                                  })) : createCommentVNode("", true),
                                  createVNode("span", null, "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")
                                ], 8, ["disabled"])
                              ])
                            ]),
                            _: 1
                          })
                        ]),
                        _: 1
                      })
                    ])
                  ])
                ]),
                _: 1
              }, 8, ["onClose"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/QuestionFormModal.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "LessonInteractionTabs",
  __ssrInlineRender: true,
  props: {
    lesson: {},
    isAdmin: { type: Boolean, default: false }
  },
  emits: ["like", "dislike", "bookmark", "share", "comment", "add-comment", "complete"],
  setup(__props, { emit: __emit }) {
    var _a;
    const props = __props;
    const { getAvatarUrl } = useAvatar();
    const authStore = useAuthStore();
    const api = useApi();
    const swal = useSweetAlert();
    const activeTab = ref("reaction");
    const isLiked = ref(props.lesson.is_liked_by_auth || false);
    const isDisliked = ref(props.lesson.is_disliked_by_auth || false);
    const isBookmarked = ref(props.lesson.is_bookmarked_by_auth || false);
    const isCompleted = ref(((_a = props.lesson.progress) == null ? void 0 : _a.status) === "completed" || false);
    const isTogglingProgress = ref(false);
    const likeCount = ref(props.lesson.like_count || 0);
    const dislikeCount = ref(props.lesson.dislike_count || 0);
    const bookmarkCount = ref(props.lesson.bookmarks_count || 0);
    const commentCount = ref(props.lesson.comment_count || 0);
    const shareCount = ref(props.lesson.share_count || 0);
    const newComment = ref("");
    const isCommenting = ref(false);
    const currentUserAvatar = computed(() => getAvatarUrl(authStore.user));
    const showAssignmentModal = ref(false);
    const showGradingModal = ref(false);
    const editingAssignment = ref(null);
    const gradingAssignment = ref(null);
    const isCreator = computed(() => {
      var _a2, _b;
      return props.isAdmin || ((_a2 = authStore.user) == null ? void 0 : _a2.id) === ((_b = props.lesson.creater) == null ? void 0 : _b.id);
    });
    const openGradingModal = (assignment) => {
      gradingAssignment.value = assignment;
      showGradingModal.value = true;
    };
    const openEditAssignment = (assignment) => {
      editingAssignment.value = assignment;
      showAssignmentModal.value = true;
    };
    const deleteAssignment = async (assignment) => {
      var _a2;
      const confirmed = await swal.confirm("\u0E25\u0E1A\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14", `\u0E04\u0E38\u0E13\u0E41\u0E19\u0E48\u0E43\u0E08\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48\u0E17\u0E35\u0E48\u0E08\u0E30\u0E25\u0E1A "${assignment.title}"?`);
      if (!confirmed) return;
      try {
        const response = await api.delete(`/api/lessons/${props.lesson.id}/assignments/${assignment.id}`);
        if (response) {
          const index = props.lesson.assignments.findIndex((a) => a.id === assignment.id);
          if (index !== -1) {
            props.lesson.assignments.splice(index, 1);
            swal.toast("\u0E25\u0E1A\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", "success");
          }
        }
      } catch (error) {
        console.error("Failed to delete assignment:", error);
        swal.error(((_a2 = error == null ? void 0 : error.data) == null ? void 0 : _a2.message) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E25\u0E1A\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E44\u0E14\u0E49");
      }
    };
    const handleSubmitAnswer = async (assignmentId, answerData) => {
      var _a2;
      try {
        const formData = new FormData();
        formData.append("content", answerData.content);
        if (props.lesson.course_id) {
          formData.append("course_id", props.lesson.course_id.toString());
        }
        if (answerData.files) {
          answerData.files.forEach((file) => {
            formData.append("images[]", file);
          });
        }
        if (answerData.deleted_images && answerData.deleted_images.length > 0) {
          answerData.deleted_images.forEach((id) => {
            formData.append("deleted_images[]", id.toString());
          });
        }
        const response = await api.post(`/api/assignments/${assignmentId}/answers`, formData);
        if (response.success) {
          const assignment = props.lesson.assignments.find((a) => a.id === assignmentId);
          if (assignment) {
            if (!assignment.answers) assignment.answers = [];
            const existingIndex = assignment.answers.findIndex((a) => {
              var _a3, _b;
              const userId = (_a3 = authStore.user) == null ? void 0 : _a3.id;
              return a.user_id === userId || a.user === userId || ((_b = a.student) == null ? void 0 : _b.id) === userId;
            });
            if (existingIndex !== -1) {
              assignment.answers[existingIndex] = response.newAnswer;
            } else {
              assignment.answers.push(response.newAnswer);
            }
          }
          swal.toast("\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27", "success");
        }
      } catch (error) {
        console.error("Failed to submit answer:", error);
        swal.error(((_a2 = error == null ? void 0 : error.data) == null ? void 0 : _a2.message) || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E48\u0E07\u0E04\u0E33\u0E15\u0E2D\u0E1A\u0E44\u0E14\u0E49");
      }
    };
    const showQuestionModal = ref(false);
    const editingQuestion = ref(null);
    const openCreateQuestion = () => {
      editingQuestion.value = null;
      showQuestionModal.value = true;
    };
    const openEditQuestion = (question) => {
      editingQuestion.value = question;
      showQuestionModal.value = true;
    };
    const handleQuestionSubmit = (question) => {
      if (!question) return;
      if (!props.lesson.questions) props.lesson.questions = [];
      props.lesson.questions = props.lesson.questions.filter((q) => q && q.id);
      if (editingQuestion.value) {
        const index = props.lesson.questions.findIndex((q) => q.id === question.id);
        if (index !== -1) {
          props.lesson.questions[index] = question;
        }
      } else {
        props.lesson.questions.push(question);
      }
    };
    const updateQuestions = (newQuestions) => {
      props.lesson.questions = newQuestions;
    };
    const handleAssignmentSubmit = (newAssignment) => {
      if (editingAssignment.value) {
        const index = props.lesson.assignments.findIndex((a) => a.id === newAssignment.id);
        if (index !== -1) {
          props.lesson.assignments[index] = newAssignment;
        }
      } else {
        if (!props.lesson.assignments) props.lesson.assignments = [];
        props.lesson.assignments.push(newAssignment);
      }
    };
    computed(() => props.lesson.assignments && props.lesson.assignments.length > 0);
    computed(() => props.lesson.questions && props.lesson.questions.length > 0);
    const assignmentCount = computed(() => {
      var _a2;
      return ((_a2 = props.lesson.assignments) == null ? void 0 : _a2.length) || 0;
    });
    const questionCount = computed(() => {
      var _a2;
      return ((_a2 = props.lesson.questions) == null ? void 0 : _a2.length) || 0;
    });
    const getCommentAvatar = (comment) => getAvatarUrl((comment == null ? void 0 : comment.user) || (comment == null ? void 0 : comment.author));
    const tabs = computed(() => [
      {
        id: "reaction",
        label: "\u0E23\u0E35\u0E41\u0E2D\u0E04\u0E0A\u0E31\u0E48\u0E19",
        icon: "fluent:emoji-24-regular",
        activeIcon: "fluent:emoji-24-filled",
        count: likeCount.value + commentCount.value,
        color: "blue"
      },
      {
        id: "assignment",
        label: "\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14",
        icon: "fluent:clipboard-task-24-regular",
        activeIcon: "fluent:clipboard-task-24-filled",
        count: assignmentCount.value,
        color: "green"
      },
      {
        id: "quiz",
        label: "\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A",
        icon: "fluent:quiz-new-24-regular",
        activeIcon: "fluent:quiz-new-24-filled",
        count: questionCount.value,
        color: "orange"
      }
    ]);
    const localComments = ref(props.lesson.comments || []);
    ref(null);
    const isLoadingMore = ref(false);
    const hasMoreComments = computed(() => localComments.value.length < commentCount.value);
    const replyingTo = ref(null);
    const replyContent = ref("");
    const isSubmittingReply = ref(false);
    const expandedReplies = ref({});
    const canDeleteComment = (comment) => {
      var _a2, _b, _c;
      const userId = (_a2 = authStore.user) == null ? void 0 : _a2.id;
      const commentOwnerId = (_b = comment.user) == null ? void 0 : _b.id;
      return userId === commentOwnerId || props.isAdmin || ((_c = props.lesson.creater) == null ? void 0 : _c.id) === userId;
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a2;
      _push(`<!--[--><div class="border-t border-gray-200 dark:border-gray-700 pt-4" data-v-dbec9499><div class="flex items-center gap-1 mb-4 bg-gray-100 dark:bg-gray-700/50 p-1 rounded-xl" data-v-dbec9499><!--[-->`);
      ssrRenderList(tabs.value, (tab) => {
        _push(`<button class="${ssrRenderClass([[
          activeTab.value === tab.id ? `bg-white dark:bg-gray-800 text-${tab.color}-600 dark:text-${tab.color}-400 shadow-md` : "text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200"
        ], "flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-medium text-sm transition-all duration-200"])}" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: activeTab.value === tab.id ? tab.activeIcon : tab.icon,
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-dbec9499>${ssrInterpolate(tab.label)}</span>`);
        if (tab.count > 0) {
          _push(`<span class="${ssrRenderClass([[
            activeTab.value === tab.id ? `bg-${tab.color}-100 dark:bg-${tab.color}-900/30 text-${tab.color}-600 dark:text-${tab.color}-400` : "bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400"
          ], "px-2 py-0.5 text-xs font-bold rounded-full"])}" data-v-dbec9499>${ssrInterpolate(tab.count)}</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      });
      _push(`<!--]--></div><div class="min-h-[120px]" data-v-dbec9499><div class="space-y-4" style="${ssrRenderStyle(activeTab.value === "reaction" ? null : { display: "none" })}" data-v-dbec9499><div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400" data-v-dbec9499><div class="flex items-center gap-4" data-v-dbec9499>`);
      if (likeCount.value > 0 || dislikeCount.value > 0) {
        _push(`<span class="flex items-center gap-1.5 hover:text-vikinger-purple cursor-pointer transition-colors" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:thumb-like-24-filled",
          class: "w-4 h-4 text-vikinger-purple"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(likeCount.value)}</span></span>`);
      } else {
        _push(`<!---->`);
      }
      if (dislikeCount.value > 0) {
        _push(`<span class="flex items-center gap-1.5 hover:text-red-500 cursor-pointer transition-colors" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:thumb-dislike-24-filled",
          class: "w-4 h-4 text-red-500"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(dislikeCount.value)}</span></span>`);
      } else {
        _push(`<!---->`);
      }
      if (commentCount.value > 0) {
        _push(`<span class="flex items-center gap-1.5 hover:text-vikinger-cyan cursor-pointer transition-colors" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:comment-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(commentCount.value)}</span></span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex items-center gap-4" data-v-dbec9499>`);
      if (shareCount.value > 0) {
        _push(`<span class="flex items-center gap-1.5 hover:text-vikinger-green cursor-pointer transition-colors" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:share-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(shareCount.value)}</span></span>`);
      } else {
        _push(`<!---->`);
      }
      if (bookmarkCount.value > 0) {
        _push(`<span class="flex items-center gap-1.5 hover:text-amber-500 cursor-pointer transition-colors" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:bookmark-24-filled",
          class: "w-4 h-4 text-amber-500"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(bookmarkCount.value)}</span></span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="flex items-center gap-2" data-v-dbec9499><button class="${ssrRenderClass([isLiked.value ? "bg-vikinger-purple/10 text-vikinger-purple" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300", "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"])}" data-v-dbec9499>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: isLiked.value ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
        class: "w-5 h-5 transition-transform hover:scale-110"
      }, null, _parent));
      _push(`<span class="text-sm font-medium" data-v-dbec9499>${ssrInterpolate(isLiked.value ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button class="${ssrRenderClass([isDisliked.value ? "bg-red-500/10 text-red-500" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300", "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"])}" data-v-dbec9499>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: isDisliked.value ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
        class: "w-5 h-5 transition-transform hover:scale-110"
      }, null, _parent));
      _push(`<span class="text-sm font-medium" data-v-dbec9499>${ssrInterpolate(isDisliked.value ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group" data-v-dbec9499>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:comment-24-regular",
        class: "w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-cyan transition-colors"
      }, null, _parent));
      _push(`<span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-dbec9499>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</span></button><button class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group" data-v-dbec9499>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:share-24-regular",
        class: "w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-green transition-colors"
      }, null, _parent));
      _push(`<span class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-dbec9499>\u0E41\u0E0A\u0E23\u0E4C</span></button><button class="${ssrRenderClass([isBookmarked.value ? "bg-amber-500/10 text-amber-500" : "hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300", "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"])}" data-v-dbec9499>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: isBookmarked.value ? "fluent:bookmark-24-filled" : "fluent:bookmark-24-regular",
        class: "w-5 h-5 transition-transform hover:scale-110"
      }, null, _parent));
      _push(`<span class="text-sm font-medium" data-v-dbec9499>${ssrInterpolate(isBookmarked.value ? "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E41\u0E25\u0E49\u0E27" : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")}</span></button></div><div class="mt-4" data-v-dbec9499><button${ssrIncludeBooleanAttr(isTogglingProgress.value) ? " disabled" : ""} class="${ssrRenderClass([isCompleted.value ? "bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/30" : "bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30 hover:from-blue-600 hover:to-blue-700", "w-full flex items-center justify-center gap-3 py-3 px-6 rounded-xl font-semibold transition-all duration-300 transform hover:scale-[1.02]"])}" data-v-dbec9499>`);
      if (isTogglingProgress.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "eos-icons:bubble-loading",
          class: "w-6 h-6"
        }, null, _parent));
      } else {
        _push(`<!--[-->`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: isCompleted.value ? "fluent:checkmark-circle-24-filled" : "fluent:checkbox-unchecked-24-regular",
          class: "w-6 h-6"
        }, null, _parent));
        _push(`<span data-v-dbec9499>${ssrInterpolate(isCompleted.value ? "\u2713 \u0E2D\u0E48\u0E32\u0E19\u0E41\u0E25\u0E49\u0E27" : "\u0E17\u0E33\u0E40\u0E04\u0E23\u0E37\u0E48\u0E2D\u0E07\u0E2B\u0E21\u0E32\u0E22\u0E27\u0E48\u0E32\u0E2D\u0E48\u0E32\u0E19\u0E41\u0E25\u0E49\u0E27")}</span><!--]-->`);
      }
      _push(`</button></div><div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700" data-v-dbec9499><div class="flex gap-3" data-v-dbec9499><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-10 h-10 flex-shrink-0 rounded-full object-cover ring-2 ring-vikinger-purple/20" alt="Your avatar" data-v-dbec9499><div class="flex-1 flex gap-2" data-v-dbec9499><input${ssrRenderAttr("value", newComment.value)} type="text" placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49..." class="flex-1 px-4 py-2.5 rounded-full bg-gray-100 dark:bg-gray-700 border-none outline-none text-gray-800 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple/30 transition-all"${ssrIncludeBooleanAttr(isCommenting.value) ? " disabled" : ""} data-v-dbec9499><button${ssrIncludeBooleanAttr(isCommenting.value || !newComment.value.trim()) ? " disabled" : ""} class="p-2.5 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed" data-v-dbec9499>`);
      if (!isCommenting.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:send-24-filled",
          class: "w-5 h-5"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:spinner-ios-20-regular",
          class: "w-5 h-5 animate-spin"
        }, null, _parent));
      }
      _push(`</button></div></div></div>`);
      if (localComments.value.length > 0) {
        _push(`<div class="mt-4 space-y-4" data-v-dbec9499><!--[-->`);
        ssrRenderList(localComments.value, (comment) => {
          var _a3, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m;
          _push(`<div class="flex gap-3" data-v-dbec9499><img${ssrRenderAttr("src", getCommentAvatar(comment))} class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover"${ssrRenderAttr("alt", ((_a3 = comment.user) == null ? void 0 : _a3.username) || ((_b = comment.author) == null ? void 0 : _b.username))} data-v-dbec9499><div class="flex-1" data-v-dbec9499><div class="bg-gray-100 dark:bg-gray-700 rounded-2xl p-3" data-v-dbec9499><h6 class="font-semibold text-sm text-gray-800 dark:text-white" data-v-dbec9499>${ssrInterpolate(((_c = comment.user) == null ? void 0 : _c.username) || ((_d = comment.author) == null ? void 0 : _d.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}</h6><p class="text-sm text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-wrap" data-v-dbec9499>${ssrInterpolate(comment.content)}</p></div>`);
          if (comment.likes || comment.dislikes) {
            _push(`<div class="flex items-center gap-3 mt-1 px-2 text-[11px] text-gray-500 dark:text-gray-400" data-v-dbec9499>`);
            if (comment.likes) {
              _push(`<span class="flex items-center gap-1" data-v-dbec9499>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:thumb-like-16-filled",
                class: "w-3 h-3 text-vikinger-purple"
              }, null, _parent));
              _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(comment.likes)}</span></span>`);
            } else {
              _push(`<!---->`);
            }
            if (comment.dislikes) {
              _push(`<span class="flex items-center gap-1" data-v-dbec9499>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:thumb-dislike-16-filled",
                class: "w-3 h-3 text-red-500"
              }, null, _parent));
              _push(`<span class="font-medium" data-v-dbec9499>${ssrInterpolate(comment.dislikes)}</span></span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400 px-2" data-v-dbec9499><span data-v-dbec9499>${ssrInterpolate(comment.create_at || comment.created_at_for_humans || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</span><button${ssrIncludeBooleanAttr(comment.isLiking || ((_e = unref(authStore).user) == null ? void 0 : _e.id) === ((_f = comment.user) == null ? void 0 : _f.id)) ? " disabled" : ""} class="${ssrRenderClass([
            "flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md",
            comment.isLikedByAuth ? "text-vikinger-purple bg-vikinger-purple/10" : "hover:text-vikinger-purple hover:bg-gray-100 dark:hover:bg-gray-600",
            ((_g = unref(authStore).user) == null ? void 0 : _g.id) === ((_h = comment.user) == null ? void 0 : _h.id) ? "opacity-50 cursor-not-allowed" : ""
          ])}" data-v-dbec9499>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: comment.isLikedByAuth ? "fluent:thumb-like-20-filled" : "fluent:thumb-like-20-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(`<span data-v-dbec9499>${ssrInterpolate(comment.isLikedByAuth ? "\u0E16\u0E39\u0E01\u0E43\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button${ssrIncludeBooleanAttr(comment.isDisliking || ((_i = unref(authStore).user) == null ? void 0 : _i.id) === ((_j = comment.user) == null ? void 0 : _j.id)) ? " disabled" : ""} class="${ssrRenderClass([
            "flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md",
            comment.isDislikedByAuth ? "text-red-500 bg-red-500/10" : "hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-600",
            ((_k = unref(authStore).user) == null ? void 0 : _k.id) === ((_l = comment.user) == null ? void 0 : _l.id) ? "opacity-50 cursor-not-allowed" : ""
          ])}" data-v-dbec9499>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: comment.isDislikedByAuth ? "fluent:thumb-dislike-20-filled" : "fluent:thumb-dislike-20-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          _push(`<span data-v-dbec9499>${ssrInterpolate(comment.isDislikedByAuth ? "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08" : "\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08")}</span></button><button class="${ssrRenderClass([
            "flex items-center gap-1 font-medium px-1.5 py-0.5 rounded-md transition-colors",
            replyingTo.value === comment.id || expandedReplies.value[comment.id] ? "text-vikinger-cyan bg-vikinger-cyan/10" : "hover:text-vikinger-cyan hover:bg-gray-100 dark:hover:bg-gray-600"
          ])}" data-v-dbec9499>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: replyingTo.value === comment.id ? "fluent:chevron-up-20-regular" : "fluent:arrow-reply-20-regular",
            class: "w-3.5 h-3.5"
          }, null, _parent));
          if (comment.replies && comment.replies.length > 0) {
            _push(`<span data-v-dbec9499>${ssrInterpolate(replyingTo.value === comment.id ? "\u0E0B\u0E48\u0E2D\u0E19" : "\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A")} (${ssrInterpolate(comment.replies.length)}) </span>`);
          } else {
            _push(`<span data-v-dbec9499>\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A</span>`);
          }
          _push(`</button>`);
          if (canDeleteComment(comment)) {
            _push(`<button class="flex items-center gap-1 font-medium px-1.5 py-0.5 rounded-md transition-colors text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" data-v-dbec9499>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:delete-20-regular",
              class: "w-3.5 h-3.5"
            }, null, _parent));
            _push(`<span data-v-dbec9499>\u0E25\u0E1A</span></button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          if (replyingTo.value === comment.id) {
            _push(`<div class="mt-3 pl-4 border-l-2 border-vikinger-purple/30" data-v-dbec9499><div class="flex gap-2" data-v-dbec9499><img${ssrRenderAttr("src", currentUserAvatar.value)} class="w-8 h-8 flex-shrink-0 rounded-full object-cover" alt="Your avatar" data-v-dbec9499><div class="flex-1 flex gap-2" data-v-dbec9499><input${ssrRenderAttr("value", replyContent.value)} type="text"${ssrRenderAttr("placeholder", `\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A ${((_m = comment.user) == null ? void 0 : _m.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49"}...`)} class="flex-1 px-3 py-2 rounded-full bg-gray-100 dark:bg-gray-700 border-none outline-none text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple/30 transition-all"${ssrIncludeBooleanAttr(isSubmittingReply.value) ? " disabled" : ""} data-v-dbec9499><button${ssrIncludeBooleanAttr(isSubmittingReply.value || !replyContent.value.trim()) ? " disabled" : ""} class="p-2 rounded-full bg-vikinger-purple text-white hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors" data-v-dbec9499>`);
            if (!isSubmittingReply.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:send-24-filled",
                class: "w-4 h-4"
              }, null, _parent));
            } else {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-4 h-4 animate-spin"
              }, null, _parent));
            }
            _push(`</button><button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 transition-colors" data-v-dbec9499>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:dismiss-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(`</button></div></div></div>`);
          } else {
            _push(`<!---->`);
          }
          if (expandedReplies.value[comment.id] && comment.replies && comment.replies.length > 0) {
            _push(`<div class="mt-3 pl-4 border-l-2 border-gray-200 dark:border-gray-600 space-y-3" data-v-dbec9499><!--[-->`);
            ssrRenderList(comment.replies, (reply) => {
              var _a4, _b2, _c2, _d2, _e2, _f2, _g2, _h2, _i2, _j2;
              _push(`<div class="flex gap-2" data-v-dbec9499><img${ssrRenderAttr("src", getCommentAvatar(reply))} class="w-8 h-8 flex-shrink-0 aspect-square rounded-full object-cover"${ssrRenderAttr("alt", (_a4 = reply.user) == null ? void 0 : _a4.username)} data-v-dbec9499><div class="flex-1" data-v-dbec9499><div class="bg-gray-50 dark:bg-gray-600 rounded-xl p-2.5" data-v-dbec9499><h6 class="font-semibold text-xs text-gray-800 dark:text-white" data-v-dbec9499>${ssrInterpolate(((_b2 = reply.user) == null ? void 0 : _b2.username) || "\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49")}</h6><p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5 whitespace-pre-wrap" data-v-dbec9499>${ssrInterpolate(reply.content)}</p></div><div class="flex items-center gap-2 mt-1 text-[10px] text-gray-500 dark:text-gray-400 px-1" data-v-dbec9499><span data-v-dbec9499>${ssrInterpolate(reply.create_at || "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48")}</span><button${ssrIncludeBooleanAttr(reply.isLiking || ((_c2 = unref(authStore).user) == null ? void 0 : _c2.id) === ((_d2 = reply.user) == null ? void 0 : _d2.id)) ? " disabled" : ""} class="${ssrRenderClass([
                "flex items-center gap-0.5 font-medium transition-colors",
                reply.isLikedByAuth ? "text-vikinger-purple" : "hover:text-vikinger-purple",
                ((_e2 = unref(authStore).user) == null ? void 0 : _e2.id) === ((_f2 = reply.user) == null ? void 0 : _f2.id) ? "opacity-50 cursor-not-allowed" : ""
              ])}" data-v-dbec9499>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: reply.isLikedByAuth ? "fluent:thumb-like-16-filled" : "fluent:thumb-like-16-regular",
                class: "w-3 h-3"
              }, null, _parent));
              if (reply.likes) {
                _push(`<span data-v-dbec9499>${ssrInterpolate(reply.likes)}</span>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</button><button${ssrIncludeBooleanAttr(reply.isDisliking || ((_g2 = unref(authStore).user) == null ? void 0 : _g2.id) === ((_h2 = reply.user) == null ? void 0 : _h2.id)) ? " disabled" : ""} class="${ssrRenderClass([
                "flex items-center gap-0.5 font-medium transition-colors",
                reply.isDislikedByAuth ? "text-red-500" : "hover:text-red-500",
                ((_i2 = unref(authStore).user) == null ? void 0 : _i2.id) === ((_j2 = reply.user) == null ? void 0 : _j2.id) ? "opacity-50 cursor-not-allowed" : ""
              ])}" data-v-dbec9499>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: reply.isDislikedByAuth ? "fluent:thumb-dislike-16-filled" : "fluent:thumb-dislike-16-regular",
                class: "w-3 h-3"
              }, null, _parent));
              if (reply.dislikes) {
                _push(`<span data-v-dbec9499>${ssrInterpolate(reply.dislikes)}</span>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</button>`);
              if (canDeleteComment(reply)) {
                _push(`<button class="flex items-center gap-0.5 font-medium transition-colors text-gray-400 hover:text-red-500" data-v-dbec9499>`);
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:delete-16-regular",
                  class: "w-3 h-3"
                }, null, _parent));
                _push(`<span data-v-dbec9499>\u0E25\u0E1A</span></button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div></div>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      if (hasMoreComments.value && localComments.value.length > 0) {
        _push(`<div class="mt-4 text-center" data-v-dbec9499><button${ssrIncludeBooleanAttr(isLoadingMore.value) ? " disabled" : ""} class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors disabled:opacity-50" data-v-dbec9499>`);
        if (isLoadingMore.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:bubble-loading",
            class: "w-4 h-4"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chevron-down-20-regular",
            class: "w-4 h-4"
          }, null, _parent));
        }
        _push(` ${ssrInterpolate(isLoadingMore.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14..." : "\u0E14\u0E39\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</button></div>`);
      } else if (localComments.value.length === 0) {
        _push(`<div class="mt-4 text-center py-6 text-gray-500 dark:text-gray-400" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:comment-24-regular",
          class: "w-10 h-10 mx-auto mb-2 opacity-50"
        }, null, _parent));
        _push(`<p class="text-sm" data-v-dbec9499>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19 \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19!</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div style="${ssrRenderStyle(activeTab.value === "assignment" ? null : { display: "none" })}" data-v-dbec9499>`);
      if (isCreator.value) {
        _push(`<div class="mb-4 flex justify-end" data-v-dbec9499><button class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:add-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14 </button></div>`);
      } else {
        _push(`<!---->`);
      }
      if ((_a2 = __props.lesson.assignments) == null ? void 0 : _a2.length) {
        _push(ssrRenderComponent(_sfc_main$1$1, {
          assignments: __props.lesson.assignments,
          "lesson-id": __props.lesson.id,
          "course-id": __props.lesson.course_id,
          "is-creator": isCreator.value,
          onSubmit: handleSubmitAnswer,
          onClose: ($event) => activeTab.value = "reaction",
          onEdit: openEditAssignment,
          onDelete: deleteAssignment,
          onViewSubmissions: openGradingModal
        }, null, _parent));
      } else {
        _push(`<div class="flex flex-col items-center justify-center py-12 text-center" data-v-dbec9499><div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4" data-v-dbec9499>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:clipboard-task-24-regular",
          class: "w-10 h-10 text-green-500 dark:text-green-400"
        }, null, _parent));
        _push(`</div><h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2" data-v-dbec9499> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14 </h4><p class="text-gray-500 dark:text-gray-400 max-w-sm" data-v-dbec9499> \u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E43\u0E2B\u0E49\u0E17\u0E33 ${ssrInterpolate(isCreator.value ? "\u0E04\u0E38\u0E13\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14\u0E44\u0E14\u0E49\u0E42\u0E14\u0E22\u0E04\u0E25\u0E34\u0E01\u0E17\u0E35\u0E48\u0E1B\u0E38\u0E48\u0E21\u0E14\u0E49\u0E32\u0E19\u0E1A\u0E19" : "\u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E2D\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E1A\u0E1A\u0E1D\u0E36\u0E01\u0E2B\u0E31\u0E14")}</p></div>`);
      }
      _push(`</div><div style="${ssrRenderStyle(activeTab.value === "quiz" ? null : { display: "none" })}" data-v-dbec9499>`);
      _push(ssrRenderComponent(QuestionsListViewer, {
        questions: __props.lesson.questions || [],
        "lesson-id": __props.lesson.id,
        "is-creator": isCreator.value,
        onCreate: openCreateQuestion,
        onEdit: openEditQuestion,
        "onUpdate:questions": updateQuestions
      }, null, _parent));
      _push(`</div></div></div>`);
      _push(ssrRenderComponent(AssignmentFormModal, {
        show: showAssignmentModal.value,
        "lesson-id": __props.lesson.id,
        assignment: editingAssignment.value,
        onClose: ($event) => showAssignmentModal.value = false,
        onSubmit: handleAssignmentSubmit
      }, null, _parent));
      _push(ssrRenderComponent(AssignmentGradingModal, {
        show: showGradingModal.value,
        "lesson-id": __props.lesson.id,
        assignment: gradingAssignment.value,
        onClose: ($event) => showGradingModal.value = false
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$2, {
        show: showQuestionModal.value,
        "lesson-id": __props.lesson.id,
        question: editingQuestion.value,
        onClose: ($event) => showQuestionModal.value = false,
        onSubmit: handleQuestionSubmit
      }, null, _parent));
      _push(`<!--]-->`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonInteractionTabs.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const LessonInteractionTabs = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-dbec9499"]]);
const MAX_CONTENT_HEIGHT = 384;
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "LessonPost",
  __ssrInlineRender: true,
  props: {
    lesson: {},
    isAdmin: { type: Boolean, default: false },
    currentUser: {},
    prevLesson: {},
    nextLesson: {},
    currentIndex: {},
    totalLessons: {},
    showNavigation: { type: Boolean, default: true }
  },
  emits: ["edit", "delete", "like", "dislike", "bookmark", "share", "comment", "navigate"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const showFullContent = ref(false);
    const showTopics = ref(false);
    const completedTopics = ref([]);
    const showVideoModal = ref(false);
    const contentRef = ref(null);
    const isContentOverflowing = ref(false);
    const checkContentOverflow = () => {
      if (contentRef.value) {
        const scrollHeight = contentRef.value.scrollHeight;
        isContentOverflowing.value = scrollHeight > MAX_CONTENT_HEIGHT;
      }
    };
    watch(
      () => props.lesson.content,
      () => {
        nextTick(() => {
          checkContentOverflow();
        });
      }
    );
    const statusColor = computed(() => {
      return props.lesson.status === 1 ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400" : "bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400";
    });
    const statusText = computed(() => {
      return props.lesson.status === 1 ? "\u0E40\u0E1C\u0E22\u0E41\u0E1E\u0E23\u0E48\u0E41\u0E25\u0E49\u0E27" : "\u0E41\u0E1A\u0E1A\u0E23\u0E48\u0E32\u0E07";
    });
    const hasTopics = computed(() => props.lesson.topics && props.lesson.topics.length > 0);
    computed(
      () => props.lesson.assignments && props.lesson.assignments.length > 0
    );
    computed(() => props.lesson.questions && props.lesson.questions.length > 0);
    const progressPercentage = computed(() => {
      if (!hasTopics.value) return 0;
      return Math.round(completedTopics.value.length / props.lesson.topics.length * 100);
    });
    const estimatedReadingTime = computed(() => {
      const content = props.lesson.content || "";
      const description = props.lesson.description || "";
      const text = content.replace(/<[^>]*>/g, "") + " " + description;
      const wordCount = text.trim().split(/\s+/).filter(Boolean).length;
      const minutes = Math.ceil(wordCount / 200);
      return minutes > 0 ? minutes : 1;
    });
    const hasNavigation = computed(() => props.showNavigation && (props.prevLesson || props.nextLesson));
    const lessonPosition = computed(() => {
      if (props.currentIndex !== void 0 && props.totalLessons) {
        return `${props.currentIndex + 1}/${props.totalLessons}`;
      }
      return null;
    });
    const getYoutubeVideoId = (url) => {
      if (!url) return null;
      if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
        return url;
      }
      const patterns = [
        /(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/,
        // youtube.com/watch?v=ID
        /youtu\.be\/([a-zA-Z0-9_-]{11})/,
        // youtu.be/ID
        /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
        // youtube.com/embed/ID
        /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
        // youtube.com/v/ID
        /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/
        // youtube.com/shorts/ID
      ];
      for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) {
          return match[1];
        }
      }
      return null;
    };
    const youtubeVideoId = computed(() => getYoutubeVideoId(props.lesson.youtube_url));
    const hasYoutubeVideo = computed(() => !!youtubeVideoId.value);
    computed(() => {
      if (!youtubeVideoId.value) return null;
      return `https://www.youtube.com/embed/${youtubeVideoId.value}`;
    });
    const youtubeThumbnailUrl = computed(() => {
      if (!youtubeVideoId.value) return null;
      return `https://img.youtube.com/vi/${youtubeVideoId.value}/maxresdefault.jpg`;
    });
    const closeVideoModal = () => {
      showVideoModal.value = false;
    };
    const handleTopicComplete = (topicId) => {
      const index = completedTopics.value.indexOf(topicId);
      if (index > -1) {
        completedTopics.value.splice(index, 1);
      } else {
        completedTopics.value.push(topicId);
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<article${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden mb-6 border border-gray-200 dark:border-gray-700" }, _attrs))}><div class="relative"><div class="relative h-40 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20 overflow-hidden rounded-t-2xl"><div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 dark:from-blue-900/30 dark:via-purple-900/30 dark:to-pink-900/30">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-24-filled",
        class: "w-20 h-20 text-blue-400/40 dark:text-blue-300/30 animate-pulse"
      }, null, _parent));
      _push(`</div><div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div></div><div class="absolute top-4 left-4 flex flex-wrap gap-2"><span class="${ssrRenderClass([statusColor.value, "inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg backdrop-blur-md bg-white/20 dark:bg-gray-900/30 ring-1 ring-white/20 transition-transform hover:scale-105"])}">`);
      if (__props.lesson.status === 1) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:draft-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
      }
      _push(` ${ssrInterpolate(statusText.value)}</span><span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:number-symbol-24-filled",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` \u0E1A\u0E17\u0E17\u0E35\u0E48 ${ssrInterpolate(__props.lesson.order)}</span><span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clock-24-filled",
        class: "w-4 h-4"
      }, null, _parent));
      _push(` ${ssrInterpolate(estimatedReadingTime.value)} \u0E19\u0E32\u0E17\u0E35 </span>`);
      if (lessonPosition.value) {
        _push(`<span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:list-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(lessonPosition.value)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (__props.lesson.point_tuition_fee > 0) {
        _push(`<div class="absolute top-4 right-4"><span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:diamond-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(__props.lesson.point_tuition_fee)} \u0E1E\u0E2D\u0E22\u0E15\u0E4C </span></div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.isAdmin) {
        _push(`<div class="${ssrRenderClass([{ "top-16": __props.lesson.point_tuition_fee > 0 }, "absolute top-4 right-4 flex gap-2"])}"><button class="p-3 bg-white/95 dark:bg-gray-800/95 rounded-xl hover:bg-white dark:hover:bg-gray-800 transition-all duration-200 backdrop-blur-md shadow-lg ring-1 ring-gray-200/50 dark:ring-gray-700/50 hover:scale-105" title="\u0E41\u0E01\u0E49\u0E44\u0E02">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:edit-24-regular",
          class: "w-5 h-5 text-blue-600 dark:text-blue-400"
        }, null, _parent));
        _push(`</button><button class="p-3 bg-white/95 dark:bg-gray-800/95 rounded-xl hover:bg-white dark:hover:bg-gray-800 transition-all duration-200 backdrop-blur-md shadow-lg ring-1 ring-gray-200/50 dark:ring-gray-700/50 hover:scale-105" title="\u0E25\u0E1A">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:delete-24-regular",
          class: "w-5 h-5 text-red-600 dark:text-red-400"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-6 space-y-6"><div><h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">${ssrInterpolate(__props.lesson.title)}</h2><div class="flex items-center justify-between mb-4"><div class="flex items-center gap-3"><img${ssrRenderAttr("src", ((_a = __props.lesson.creater) == null ? void 0 : _a.avatar) || "/images/default-avatar.png")}${ssrRenderAttr("alt", (_b = __props.lesson.creater) == null ? void 0 : _b.name)} class="w-10 h-10 rounded-full ring-2 ring-blue-500 object-cover"><div><p class="font-medium text-gray-900 dark:text-white">${ssrInterpolate((_c = __props.lesson.creater) == null ? void 0 : _c.name)}</p><p class="text-sm text-gray-500 dark:text-gray-400">${ssrInterpolate(__props.lesson.created_at_for_humans)}</p></div></div><div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400"><div class="flex items-center gap-1">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clock-24-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span>${ssrInterpolate(__props.lesson.min_read)} \u0E19\u0E32\u0E17\u0E35</span></div>`);
      if (__props.lesson.view_count) {
        _push(`<div class="flex items-center gap-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:eye-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(__props.lesson.view_count)}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
      if (hasTopics.value && !__props.isAdmin) {
        _push(`<div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden"><div class="bg-gradient-to-r from-blue-500 to-purple-500 h-full transition-all duration-500" style="${ssrRenderStyle({ width: `${progressPercentage.value}%` })}"></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (hasTopics.value && !__props.isAdmin) {
        _push(`<p class="text-xs text-gray-500 dark:text-gray-400 mt-1"> \u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32: ${ssrInterpolate(progressPercentage.value)}% (${ssrInterpolate(completedTopics.value.length)}/${ssrInterpolate(__props.lesson.topics.length)} \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D) </p>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (__props.lesson.description) {
        _push(`<div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800"><p class="text-gray-700 dark:text-gray-300 leading-relaxed">${ssrInterpolate(__props.lesson.description)}</p></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="relative"><div class="${ssrRenderClass([
        "prose prose-blue dark:prose-invert max-w-none transition-all duration-300",
        !showFullContent.value && isContentOverflowing.value && "max-h-96 overflow-hidden"
      ])}">`);
      _push(ssrRenderComponent(__nuxt_component_1, {
        content: __props.lesson.content
      }, null, _parent));
      _push(`</div>`);
      if (isContentOverflowing.value && !showFullContent.value) {
        _push(`<div class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-white dark:from-gray-800 to-transparent flex items-end justify-center pb-2"><button class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium shadow-lg"> \u0E2D\u0E48\u0E32\u0E19\u0E15\u0E48\u0E2D `);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-down-24-regular",
          class: "w-4 h-4 inline ml-1"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<!---->`);
      }
      if (isContentOverflowing.value && showFullContent.value) {
        _push(`<button class="mt-4 w-full px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium"> \u0E22\u0E48\u0E2D\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32 `);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:chevron-up-24-regular",
          class: "w-4 h-4 inline ml-1"
        }, null, _parent));
        _push(`</button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (hasYoutubeVideo.value) {
        _push(`<div class="mt-6"><h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:video-24-filled",
          class: "w-6 h-6 text-red-600 dark:text-red-400"
        }, null, _parent));
        _push(` \u0E27\u0E34\u0E14\u0E35\u0E42\u0E2D\u0E1B\u0E23\u0E30\u0E01\u0E2D\u0E1A\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><div class="relative rounded-2xl overflow-hidden cursor-pointer group shadow-lg border border-gray-200 dark:border-gray-700"><div class="aspect-video"><img${ssrRenderAttr("src", youtubeThumbnailUrl.value)}${ssrRenderAttr("alt", __props.lesson.title)} class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"></div><div class="absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/60 via-black/20 to-transparent group-hover:from-black/70 group-hover:via-black/30 transition-all duration-300"><div class="w-20 h-20 bg-red-600 rounded-full flex items-center justify-center shadow-2xl transform transition-all duration-300 group-hover:scale-110 group-hover:shadow-red-500/50">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:play-24-filled",
          class: "w-10 h-10 text-white ml-1"
        }, null, _parent));
        _push(`</div></div><div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent"><div class="flex items-center justify-between"><span class="text-white font-medium truncate">${ssrInterpolate(__props.lesson.title)}</span><span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-600/90 text-white text-sm font-medium">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "logos:youtube-icon",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` YouTube </span></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (hasTopics.value) {
        _push(`<div><button class="w-full flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors"><div class="flex items-center gap-3">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:book-open-24-filled",
          class: "w-6 h-6 text-purple-600 dark:text-purple-400"
        }, null, _parent));
        _push(`<div class="text-left"><h3 class="font-semibold text-gray-900 dark:text-white">\u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E22\u0E48\u0E2D\u0E22</h3><p class="text-sm text-gray-600 dark:text-gray-400">${ssrInterpolate(__props.lesson.topics.length)} \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D </p></div></div>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: showTopics.value ? "fluent:chevron-up-24-filled" : "fluent:chevron-down-24-filled",
          class: "w-5 h-5 text-gray-600 dark:text-gray-400"
        }, null, _parent));
        _push(`</button><div class="mt-4 space-y-2" style="${ssrRenderStyle(showTopics.value ? null : { display: "none" })}"><!--[-->`);
        ssrRenderList(__props.lesson.topics, (topic) => {
          _push(ssrRenderComponent(_sfc_main$5, {
            key: topic.id,
            topic,
            "is-completed": completedTopics.value.includes(topic.id),
            onToggleComplete: handleTopicComplete
          }, null, _parent));
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(LessonInteractionTabs, {
        lesson: __props.lesson,
        isAdmin: __props.isAdmin,
        onLike: ($event) => _ctx.$emit("like", __props.lesson.id),
        onDislike: ($event) => _ctx.$emit("dislike", __props.lesson.id),
        onBookmark: ($event) => _ctx.$emit("bookmark", __props.lesson.id),
        onShare: ($event) => _ctx.$emit("share", __props.lesson)
      }, null, _parent));
      if (hasNavigation.value) {
        _push(`<div class="flex items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">`);
        if (__props.prevLesson) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/courses/${__props.lesson.course_id}/lessons/${__props.prevLesson.id}`,
            class: "flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-700 shadow-md hover:shadow-lg transition-all group hover:-translate-x-1"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chevron-left-24-filled",
                  class: "w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors"
                }, null, _parent2, _scopeId));
                _push2(`<div class="text-left"${_scopeId}><p class="text-xs text-gray-400 uppercase tracking-wide"${_scopeId}>\u0E1A\u0E17\u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32</p><p class="text-sm font-semibold text-gray-700 dark:text-gray-200 line-clamp-1 max-w-[150px] md:max-w-[200px]"${_scopeId}>${ssrInterpolate(__props.prevLesson.title)}</p></div>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "fluent:chevron-left-24-filled",
                    class: "w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors"
                  }),
                  createVNode("div", { class: "text-left" }, [
                    createVNode("p", { class: "text-xs text-gray-400 uppercase tracking-wide" }, "\u0E1A\u0E17\u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32"),
                    createVNode("p", { class: "text-sm font-semibold text-gray-700 dark:text-gray-200 line-clamp-1 max-w-[150px] md:max-w-[200px]" }, toDisplayString(__props.prevLesson.title), 1)
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<div class="flex-1"></div>`);
        }
        if (lessonPosition.value) {
          _push(`<div class="hidden md:flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 rounded-full shadow">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:book-open-24-regular",
            class: "w-4 h-4 text-blue-500"
          }, null, _parent));
          _push(`<span class="text-sm font-medium text-gray-600 dark:text-gray-300">${ssrInterpolate(lessonPosition.value)}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.nextLesson) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: `/courses/${__props.lesson.course_id}/lessons/${__props.nextLesson.id}`,
            class: "flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-700 shadow-md hover:shadow-lg transition-all group hover:translate-x-1"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="text-right"${_scopeId}><p class="text-xs text-gray-400 uppercase tracking-wide"${_scopeId}>\u0E1A\u0E17\u0E16\u0E31\u0E14\u0E44\u0E1B</p><p class="text-sm font-semibold text-gray-700 dark:text-gray-200 line-clamp-1 max-w-[150px] md:max-w-[200px]"${_scopeId}>${ssrInterpolate(__props.nextLesson.title)}</p></div>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:chevron-right-24-filled",
                  class: "w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors"
                }, null, _parent2, _scopeId));
              } else {
                return [
                  createVNode("div", { class: "text-right" }, [
                    createVNode("p", { class: "text-xs text-gray-400 uppercase tracking-wide" }, "\u0E1A\u0E17\u0E16\u0E31\u0E14\u0E44\u0E1B"),
                    createVNode("p", { class: "text-sm font-semibold text-gray-700 dark:text-gray-200 line-clamp-1 max-w-[150px] md:max-w-[200px]" }, toDisplayString(__props.nextLesson.title), 1)
                  ]),
                  createVNode(unref(Icon), {
                    icon: "fluent:chevron-right-24-filled",
                    class: "w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors"
                  })
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<div class="flex-1"></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (showVideoModal.value) {
        _push(ssrRenderComponent(VideoModal, {
          "youtube-url": __props.lesson.youtube_url,
          title: __props.lesson.title,
          onClose: closeVideoModal
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</article>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/lesson/LessonPost.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=LessonPost-BeiNptMJ.mjs.map
