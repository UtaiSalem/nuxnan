import { ref, computed, watch, unref, useSSRContext } from 'vue';
import { ssrRenderTeleport, ssrRenderComponent, ssrInterpolate, ssrRenderStyle, ssrRenderAttr, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "ImageGalleryModal",
  __ssrInlineRender: true,
  props: {
    show: {
      type: Boolean,
      default: false
    },
    visit: {
      type: Object,
      default: null
    },
    images: {
      type: Array,
      default: () => []
    }
  },
  emits: ["close"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const currentIndex = ref(0);
    const currentImage = computed(() => props.images[currentIndex.value]);
    const currentImageUrl = computed(() => {
      if (!currentImage.value) return "";
      return getImageUrl(currentImage.value);
    });
    function getImageUrl(image) {
      if (!image) return "";
      if (image.image_url) return image.image_url;
      if (image.image_path) {
        const path = image.image_path.replace(/^public\//, "");
        return `/storage/${path}`;
      }
      return "";
    }
    function closeModal() {
      currentIndex.value = 0;
      emit("close");
    }
    function previousImage() {
      currentIndex.value = currentIndex.value > 0 ? currentIndex.value - 1 : props.images.length - 1;
    }
    function nextImage() {
      currentIndex.value = currentIndex.value < props.images.length - 1 ? currentIndex.value + 1 : 0;
    }
    function formatDate(dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    }
    function formatDateTime(dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    }
    watch(() => props.show, (newVal) => {
      if (newVal) {
        currentIndex.value = 0;
      }
    });
    watch(() => props.show, (newVal) => {
      if (newVal) {
        (void 0).addEventListener("keydown", handleKeydown);
      } else {
        (void 0).removeEventListener("keydown", handleKeydown);
      }
    });
    function handleKeydown(event) {
      if (!props.show) return;
      switch (event.key) {
        case "ArrowLeft":
          previousImage();
          break;
        case "ArrowRight":
          nextImage();
          break;
        case "Escape":
          closeModal();
          break;
      }
    }
    const editingDescription = ref(false);
    const editDescriptionText = ref("");
    const savingDescription = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a, _b;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 z-50 overflow-y-auto" data-v-e9882d8d><div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" data-v-e9882d8d></div><div class="relative min-h-screen flex items-center justify-center p-4" data-v-e9882d8d><div class="relative bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden" data-v-e9882d8d><div class="px-6 py-4 border-b border-gray-200 bg-white relative" data-v-e9882d8d><h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 pr-12" data-v-e9882d8d>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:photo-20-solid",
            class: "w-6 h-6 text-green-600"
          }, null, _parent));
          _push2(` \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h3><p class="text-sm text-gray-600 mt-1" data-v-e9882d8d> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48 ${ssrInterpolate(formatDate((_a = __props.visit) == null ? void 0 : _a.visit_date))} - ${ssrInterpolate(__props.images.length)} \u0E23\u0E39\u0E1B </p><button class="absolute top-4 right-4 w-10 h-10 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center transition-all hover:scale-110 shadow-lg" data-v-e9882d8d>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:x-mark",
            class: "w-6 h-6"
          }, null, _parent));
          _push2(`</button></div><div class="relative bg-black" style="${ssrRenderStyle({ "height": "60vh" })}" data-v-e9882d8d>`);
          if (__props.images.length > 0) {
            _push2(`<div class="h-full flex items-center justify-center" data-v-e9882d8d><img${ssrRenderAttr("src", currentImageUrl.value)}${ssrRenderAttr("alt", `\u0E23\u0E39\u0E1B\u0E17\u0E35\u0E48 ${currentIndex.value + 1}`)} class="max-h-full max-w-full object-contain" data-v-e9882d8d>`);
            if (__props.images.length > 1) {
              _push2(`<button class="absolute left-4 top-1/2 -translate-y-1/2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 w-14 h-14 rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110" data-v-e9882d8d>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:chevron-left-20-solid",
                class: "w-8 h-8"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            if (__props.images.length > 1) {
              _push2(`<button class="absolute right-4 top-1/2 -translate-y-1/2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 w-14 h-14 rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110" data-v-e9882d8d>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:chevron-right-20-solid",
                class: "w-8 h-8"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black bg-opacity-75 text-white px-4 py-2 rounded-full text-sm" data-v-e9882d8d>${ssrInterpolate(currentIndex.value + 1)} / ${ssrInterpolate(__props.images.length)}</div><a${ssrRenderAttr("href", currentImageUrl.value)}${ssrRenderAttr("download", `home_visit_${(_b = __props.visit) == null ? void 0 : _b.id}_${currentIndex.value + 1}.jpg`)} class="absolute top-4 right-4 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg flex items-center gap-2 shadow-xl transition-all hover:scale-105 font-bold" data-v-e9882d8d>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:arrow-down-tray-20-solid",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(`<span class="text-base" data-v-e9882d8d>\u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14</span></a></div>`);
          } else {
            _push2(`<div class="h-full flex items-center justify-center text-white" data-v-e9882d8d><div class="text-center" data-v-e9882d8d>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:photo",
              class: "w-24 h-24 mb-4 opacity-50"
            }, null, _parent));
            _push2(`<p class="text-lg" data-v-e9882d8d>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</p></div></div>`);
          }
          _push2(`</div>`);
          if (__props.images.length > 1) {
            _push2(`<div class="p-4 bg-gray-100 border-t border-gray-200" data-v-e9882d8d><div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100" data-v-e9882d8d><!--[-->`);
            ssrRenderList(__props.images, (image, index) => {
              _push2(`<button class="${ssrRenderClass([currentIndex.value === index ? "ring-4 ring-green-500 rounded-lg scale-105" : "opacity-60 hover:opacity-100", "flex-shrink-0 relative group transition-all"])}" data-v-e9882d8d><img${ssrRenderAttr("src", getImageUrl(image))}${ssrRenderAttr("alt", `\u0E20\u0E32\u0E1E\u0E22\u0E48\u0E2D\u0E17\u0E35\u0E48 ${index + 1}`)} class="${ssrRenderClass([currentIndex.value === index ? "border-green-500" : "border-gray-300 group-hover:border-green-400", "w-28 h-28 object-cover rounded-lg cursor-pointer transition-all border-2"])}" data-v-e9882d8d>`);
              if (currentIndex.value === index) {
                _push2(`<div class="absolute inset-0 bg-green-500 bg-opacity-30 rounded-lg flex items-center justify-center pointer-events-none" data-v-e9882d8d><div class="bg-green-600 rounded-full p-1" data-v-e9882d8d>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:check-20-solid",
                  class: "w-8 h-8 text-white"
                }, null, _parent));
                _push2(`</div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="absolute top-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded-full font-medium" data-v-e9882d8d>${ssrInterpolate(index + 1)}</div></button>`);
            });
            _push2(`<!--]--></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (__props.images.length > 0 && currentImage.value) {
            _push2(`<div class="px-6 py-4 bg-white border-t border-gray-200" data-v-e9882d8d><div class="flex items-center justify-between" data-v-e9882d8d><div class="flex-1" data-v-e9882d8d><div class="flex items-center gap-2" data-v-e9882d8d>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:information-circle-20-solid",
              class: "w-5 h-5"
            }, null, _parent));
            if (!editingDescription.value) {
              _push2(`<span data-v-e9882d8d>${ssrInterpolate(currentImage.value.description || "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19")}</span>`);
            } else {
              _push2(`<!---->`);
            }
            if (!editingDescription.value) {
              _push2(`<button class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200" data-v-e9882d8d>\u0E41\u0E01\u0E49\u0E44\u0E02</button>`);
            } else {
              _push2(`<div class="flex items-center gap-2" data-v-e9882d8d><input${ssrRenderAttr("value", editDescriptionText.value)} class="border px-2 py-1 rounded text-sm" placeholder="\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22..." data-v-e9882d8d><button${ssrIncludeBooleanAttr(savingDescription.value) ? " disabled" : ""} class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700" data-v-e9882d8d>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01</button><button${ssrIncludeBooleanAttr(savingDescription.value) ? " disabled" : ""} class="px-2 py-1 text-xs bg-gray-300 text-gray-700 rounded hover:bg-gray-400" data-v-e9882d8d>\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01</button></div>`);
            }
            _push2(`</div><p class="text-xs text-gray-500 mt-1" data-v-e9882d8d> \u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E40\u0E21\u0E37\u0E48\u0E2D: ${ssrInterpolate(formatDateTime(currentImage.value.created_at))}</p></div><div class="flex items-center gap-2" data-v-e9882d8d><button class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2" data-v-e9882d8d>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:arrow-down-tray-20-solid",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button><button${ssrIncludeBooleanAttr(_ctx.deletingImage) ? " disabled" : ""} class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2" data-v-e9882d8d>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:trash-20-solid",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(` \u0E25\u0E1A\u0E23\u0E39\u0E1B\u0E19\u0E35\u0E49 </button></div></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Teacher/Components/ImageGalleryModal.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ImageGalleryModal = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-e9882d8d"]]);

export { ImageGalleryModal as default };
//# sourceMappingURL=ImageGalleryModal-DpY9Yyol.mjs.map
