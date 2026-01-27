import { defineComponent, ref, mergeProps, unref, computed, watch, withCtx, createVNode, createTextVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderTeleport, ssrRenderStyle, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { u as useAuth } from './useAuth-BmyK1-KK.mjs';
import { _ as _export_sfc, i as useApi, d as useAuthStore } from './server.mjs';
import { u as useAvatar } from './useAvatar-C8DTKR1c.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { P as PollCard, S as ShareModal } from './PollCard-DKn1EeyZ.mjs';
import { I as ImageLightbox } from './ImageLightbox-D9vQ7Zkj.mjs';

const _sfc_main$4 = {
  __name: "CourseCreatePostModal",
  __ssrInlineRender: true,
  props: {
    show: {
      type: Boolean,
      default: false
    },
    courseId: {
      type: [String, Number],
      required: true
    },
    groupId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ["close", "post-created"],
  setup(__props, { emit: __emit }) {
    const { user } = useAuth();
    useApi();
    const postText = ref("");
    const isSubmitting = ref(false);
    ref([]);
    const selectedFiles = ref([]);
    ref(null);
    ref(null);
    const activeTab = ref("status");
    const tabs = [
      { id: "status", label: "\u0E2A\u0E16\u0E32\u0E19\u0E30", icon: "fluent:edit-24-regular" },
      { id: "poll", label: "\u0E42\u0E1E\u0E25", icon: "fluent:poll-24-regular" }
    ];
    const pollQuestion = ref("");
    const pollOptions = ref(["", ""]);
    const pollDuration = ref(24);
    const pollPointsPool = ref(12e3);
    const maxVotes = ref(100);
    ref("course");
    const imagePreviews = computed(() => {
      return [];
    });
    const formatFileSize = (bytes) => {
      if (bytes === 0) return "0 Bytes";
      const k = 1024;
      const sizes = ["Bytes", "KB", "MB", "GB"];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    };
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a, _b;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto backdrop-blur-sm" data-v-240cdcfa><div class="w-full max-w-2xl mx-4 mb-10 modal-content" data-v-240cdcfa><div class="bg-white dark:bg-vikinger-dark-300 rounded-xl shadow-2xl" data-v-240cdcfa><div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30" data-v-240cdcfa><div class="flex items-center gap-2" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:book-24-regular",
            class: "w-5 h-5 text-blue-500"
          }, null, _parent));
          _push2(`<h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-240cdcfa>\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h2></div><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-6 h-6 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-4 max-h-[70vh] overflow-y-auto" data-v-240cdcfa><input type="file" class="hidden" accept="image/*" multiple data-v-240cdcfa><input type="file" class="hidden" multiple data-v-240cdcfa><div class="flex items-center gap-3 mb-4" data-v-240cdcfa><img${ssrRenderAttr("src", ((_a = unref(user)) == null ? void 0 : _a.avatar) || "/images/default-avatar.png")} class="w-10 h-10 rounded-full object-cover" data-v-240cdcfa><div class="flex-1" data-v-240cdcfa><div class="font-medium text-gray-800 dark:text-white" data-v-240cdcfa>${ssrInterpolate((_b = unref(user)) == null ? void 0 : _b.name)}</div><div class="flex items-center gap-1 text-xs text-gray-500" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:people-community-24-regular",
            class: "w-3 h-3"
          }, null, _parent));
          _push2(`<span data-v-240cdcfa>\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span></div></div></div><div class="flex border-b border-gray-200 dark:border-vikinger-dark-50/30 mb-4" data-v-240cdcfa><!--[-->`);
          ssrRenderList(tabs, (tab) => {
            _push2(`<button class="${ssrRenderClass([activeTab.value === tab.id ? "text-blue-600 border-blue-600 dark:text-blue-400 dark:border-blue-400" : "text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-300", "flex-1 pb-3 text-sm font-medium flex items-center justify-center gap-2 border-b-2 transition-colors relative"])}" data-v-240cdcfa>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: tab.icon,
              class: "w-5 h-5"
            }, null, _parent));
            _push2(`<span data-v-240cdcfa>${ssrInterpolate(tab.label)}</span></button>`);
          });
          _push2(`<!--]--></div><div style="${ssrRenderStyle(activeTab.value === "status" ? null : { display: "none" })}" data-v-240cdcfa><div class="rounded-lg mb-4 min-h-[120px] p-4 bg-gray-50 dark:bg-vikinger-dark-200" data-v-240cdcfa><textarea placeholder="\u0E41\u0E0A\u0E23\u0E4C\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E16\u0E32\u0E21\u0E04\u0E33\u0E16\u0E32\u0E21\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49..." rows="4" class="w-full bg-transparent border-none outline-none resize-none text-gray-800 dark:text-white placeholder-gray-400"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-240cdcfa>${ssrInterpolate(postText.value)}</textarea></div>`);
          if (imagePreviews.value.length > 0) {
            _push2(`<div class="mb-4" data-v-240cdcfa><div class="flex flex-wrap gap-2" data-v-240cdcfa><!--[-->`);
            ssrRenderList(imagePreviews.value, (preview, index) => {
              _push2(`<div class="relative group" data-v-240cdcfa><img${ssrRenderAttr("src", preview.url)} class="w-24 h-24 object-cover rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" data-v-240cdcfa><button class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity" data-v-240cdcfa>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push2(`</button></div>`);
            });
            _push2(`<!--]-->`);
            if (imagePreviews.value.length < 10) {
              _push2(`<button class="w-24 h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center hover:border-vikinger-purple hover:bg-vikinger-purple/5 transition-all" data-v-240cdcfa>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:add-24-regular",
                class: "w-8 h-8 text-gray-400"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><p class="text-xs text-gray-500 mt-1" data-v-240cdcfa>${ssrInterpolate(imagePreviews.value.length)}/10 \u0E23\u0E39\u0E1B</p></div>`);
          } else {
            _push2(`<!---->`);
          }
          if (selectedFiles.value.length > 0) {
            _push2(`<div class="mb-4 space-y-2" data-v-240cdcfa><!--[-->`);
            ssrRenderList(selectedFiles.value, (file, index) => {
              _push2(`<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg" data-v-240cdcfa><div class="flex items-center gap-3" data-v-240cdcfa><div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center" data-v-240cdcfa>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:document-24-regular",
                class: "w-5 h-5 text-blue-500"
              }, null, _parent));
              _push2(`</div><div data-v-240cdcfa><p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate max-w-[200px]" data-v-240cdcfa>${ssrInterpolate(file.name)}</p><p class="text-xs text-gray-500" data-v-240cdcfa>${ssrInterpolate(formatFileSize(file.size))}</p></div></div><button class="p-1 hover:bg-gray-200 dark:hover:bg-vikinger-dark-100 rounded-full" data-v-240cdcfa>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-24-regular",
                class: "w-4 h-4 text-gray-400 hover:text-red-500"
              }, null, _parent));
              _push2(`</button></div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><div class="mb-4" style="${ssrRenderStyle(activeTab.value === "poll" ? null : { display: "none" })}" data-v-240cdcfa><div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700/30" data-v-240cdcfa><div class="flex items-center justify-between mb-3" data-v-240cdcfa><div class="flex items-center gap-2" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:poll-24-regular",
            class: "w-5 h-5 text-amber-600"
          }, null, _parent));
          _push2(`<span class="font-medium text-amber-700 dark:text-amber-300" data-v-240cdcfa>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E42\u0E1E\u0E25</span></div></div><input${ssrRenderAttr("value", pollQuestion.value)} type="text" placeholder="\u0E04\u0E33\u0E16\u0E32\u0E21\u0E42\u0E1E\u0E25..." class="w-full px-3 py-2 mb-3 rounded-lg border border-amber-200 dark:border-amber-700/50 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-white" data-v-240cdcfa><div class="space-y-3 mb-4" data-v-240cdcfa><!--[-->`);
          ssrRenderList(pollOptions.value, (option, index) => {
            _push2(`<div class="flex items-center gap-3 p-3 bg-white dark:bg-vikinger-dark-100 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30" data-v-240cdcfa><div class="w-7 h-7 rounded-full bg-vikinger-purple text-white flex items-center justify-center text-xs font-bold flex-shrink-0" data-v-240cdcfa>${ssrInterpolate(index + 1)}</div><input${ssrRenderAttr("value", pollOptions.value[index])} type="text"${ssrRenderAttr("placeholder", `\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E17\u0E35\u0E48 ${index + 1}`)} class="flex-1 bg-transparent border-none outline-none text-gray-800 dark:text-white text-sm" data-v-240cdcfa>`);
            if (pollOptions.value.length > 2) {
              _push2(`<button class="p-1 text-gray-400 hover:text-red-500 transition-colors" data-v-240cdcfa>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:dismiss-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push2(`</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          });
          _push2(`<!--]--></div>`);
          if (pollOptions.value.length < 10) {
            _push2(`<button class="w-full h-11 border-2 border-dashed border-gray-200 dark:border-vikinger-dark-50/20 rounded-xl flex items-center justify-center gap-2 text-sm text-gray-500 hover:border-vikinger-cyan hover:text-vikinger-cyan transition-all mb-4" data-v-240cdcfa>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:add-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push2(`<span data-v-240cdcfa>\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01 (\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 10 \u0E15\u0E31\u0E27\u0E40\u0E25\u0E37\u0E2D\u0E01)</span></button>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`<div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700/30" data-v-240cdcfa><label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:clock-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`<span data-v-240cdcfa>\u0E23\u0E30\u0E22\u0E30\u0E40\u0E27\u0E25\u0E32\u0E42\u0E1E\u0E25:</span><select class="px-2 py-1 rounded border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-sm" data-v-240cdcfa><option${ssrRenderAttr("value", 1)} data-v-240cdcfa${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 1) : ssrLooseEqual(pollDuration.value, 1)) ? " selected" : ""}>1 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</option><option${ssrRenderAttr("value", 6)} data-v-240cdcfa${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 6) : ssrLooseEqual(pollDuration.value, 6)) ? " selected" : ""}>6 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</option><option${ssrRenderAttr("value", 12)} data-v-240cdcfa${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 12) : ssrLooseEqual(pollDuration.value, 12)) ? " selected" : ""}>12 \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07</option><option${ssrRenderAttr("value", 24)} data-v-240cdcfa${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 24) : ssrLooseEqual(pollDuration.value, 24)) ? " selected" : ""}>1 \u0E27\u0E31\u0E19</option><option${ssrRenderAttr("value", 72)} data-v-240cdcfa${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 72) : ssrLooseEqual(pollDuration.value, 72)) ? " selected" : ""}>3 \u0E27\u0E31\u0E19</option><option${ssrRenderAttr("value", 168)} data-v-240cdcfa${ssrIncludeBooleanAttr(Array.isArray(pollDuration.value) ? ssrLooseContain(pollDuration.value, 168) : ssrLooseEqual(pollDuration.value, 168)) ? " selected" : ""}>1 \u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C</option></select></label></div></div><div class="grid grid-cols-2 gap-4 mt-6" data-v-240cdcfa><div data-v-240cdcfa><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider" data-v-240cdcfa> \u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E23\u0E27\u0E21 </label><div class="relative flex items-center" data-v-240cdcfa><div class="absolute left-3 text-vikinger-cyan" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:piggy-bank",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`</div><input${ssrRenderAttr("value", pollPointsPool.value)} type="number" min="0" step="100" class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/10 rounded-xl text-sm focus:ring-1 focus:ring-vikinger-cyan outline-none" placeholder="0" data-v-240cdcfa></div></div><div data-v-240cdcfa><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider" data-v-240cdcfa> \u0E08\u0E33\u0E19\u0E27\u0E19\u0E04\u0E19\u0E42\u0E2B\u0E27\u0E15\u0E2A\u0E39\u0E07\u0E2A\u0E38\u0E14 </label><div class="relative flex items-center" data-v-240cdcfa><div class="absolute left-3 text-vikinger-orange" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "mdi:account-group",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`</div><input${ssrRenderAttr("value", maxVotes.value)} type="number" min="1" class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/10 rounded-xl text-sm focus:ring-1 focus:ring-vikinger-cyan outline-none" placeholder="100" data-v-240cdcfa></div></div></div><div class="mt-4 p-4 bg-vikinger-cyan/5 dark:bg-vikinger-cyan/10 border border-vikinger-cyan/20 rounded-xl" data-v-240cdcfa><div class="flex items-start gap-3" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:info-24-regular",
            class: "w-6 h-6 text-vikinger-cyan flex-shrink-0 mt-0.5"
          }, null, _parent));
          _push2(`<div class="text-sm dark:text-gray-200 w-full" data-v-240cdcfa><p class="font-bold text-vikinger-cyan mb-2" data-v-240cdcfa>\u0E2A\u0E23\u0E38\u0E1B\u0E41\u0E15\u0E49\u0E21\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49:</p><ul class="space-y-1.5" data-v-240cdcfa><li class="flex justify-between items-center text-gray-600 dark:text-gray-400" data-v-240cdcfa><span data-v-240cdcfa>\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E42\u0E1E\u0E25</span><span class="font-semibold" data-v-240cdcfa>180 \u0E41\u0E15\u0E49\u0E21</span></li><li class="flex justify-between items-center text-gray-600 dark:text-gray-400" data-v-240cdcfa><span data-v-240cdcfa>\u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E04\u0E19\u0E42\u0E2B\u0E27\u0E15</span><span class="font-semibold" data-v-240cdcfa>${ssrInterpolate(pollPointsPool.value)} \u0E41\u0E15\u0E49\u0E21</span></li><li class="flex justify-between items-center pt-2 border-t border-vikinger-cyan/20 font-bold text-gray-800 dark:text-white mt-1" data-v-240cdcfa><span data-v-240cdcfa>\u0E23\u0E27\u0E21\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</span><span data-v-240cdcfa>${ssrInterpolate(180 + pollPointsPool.value)} \u0E41\u0E15\u0E49\u0E21</span></li></ul><div class="mt-3 flex items-center gap-2 text-xs text-green-600 dark:text-green-400 font-medium" data-v-240cdcfa>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:gift-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push2(`<span data-v-240cdcfa>\u0E1C\u0E39\u0E49\u0E23\u0E48\u0E27\u0E21\u0E42\u0E2B\u0E27\u0E15\u0E08\u0E30\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E42\u0E14\u0E22\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34!</span></div>`);
          if (unref(user) && unref(user).pp < 180 + pollPointsPool.value) {
            _push2(`<p class="text-xs text-red-500 font-bold mt-2" data-v-240cdcfa> ! \u0E04\u0E38\u0E13\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D (\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19 ${ssrInterpolate(unref(user).pp)} \u0E41\u0E15\u0E49\u0E21) </p>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div></div></div></div>`);
          if (activeTab.value === "status") {
            _push2(`<div class="flex flex-wrap gap-2 border-t border-gray-200 dark:border-vikinger-dark-50/30 pt-4" data-v-240cdcfa><button class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-240cdcfa>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:image-24-regular",
              class: "w-5 h-5 text-green-500"
            }, null, _parent));
            _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-240cdcfa>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></button><button class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-240cdcfa>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:attach-24-regular",
              class: "w-5 h-5 text-blue-500"
            }, null, _parent));
            _push2(`<span class="text-sm text-gray-700 dark:text-gray-300" data-v-240cdcfa>\u0E44\u0E1F\u0E25\u0E4C\u0E41\u0E19\u0E1A</span></button></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30" data-v-240cdcfa><button${ssrIncludeBooleanAttr(isSubmitting.value || activeTab.value === "status" && !postText.value.trim() && imagePreviews.value.length === 0 || activeTab.value === "poll" && !pollQuestion.value.trim()) ? " disabled" : ""} class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" data-v-240cdcfa>`);
          if (isSubmitting.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`<span data-v-240cdcfa>${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E1E\u0E2A\u0E15\u0E4C..." : "\u0E42\u0E1E\u0E2A\u0E15\u0E4C")}</span></button></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseCreatePostModal.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const CourseCreatePostModal = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-240cdcfa"]]);
const _sfc_main$3 = {
  __name: "CourseCreatePostBox",
  __ssrInlineRender: true,
  props: {
    courseId: {
      type: [String, Number],
      required: true
    },
    groupId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ["post-created"],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const { user } = useAuth();
    const { getAvatarUrl } = useAvatar();
    const showModal = ref(false);
    const userAvatar = computed(() => getAvatarUrl(user.value));
    const closeModal = () => {
      showModal.value = false;
    };
    const handlePostCreated = (post) => {
      emit("post-created", post);
      closeModal();
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-100 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-vikinger-dark-50/20" }, _attrs))}><div class="flex items-center gap-3 mb-3"><img${ssrRenderAttr("src", userAvatar.value)} alt="Avatar" class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"><button class="flex-1 text-left px-4 py-2.5 bg-gray-100 dark:bg-vikinger-dark-200/50 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 transition-colors text-sm"> \u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E2D\u0E30\u0E44\u0E23\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49... </button></div><div class="flex items-center justify-around pt-2 border-t border-gray-100 dark:border-vikinger-dark-50/20"><button class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:image-24-regular",
        class: "w-5 h-5 text-green-500"
      }, null, _parent));
      _push(`<span class="text-xs font-medium text-gray-600 dark:text-gray-400">\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></button><button class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:attach-24-regular",
        class: "w-5 h-5 text-blue-500"
      }, null, _parent));
      _push(`<span class="text-xs font-medium text-gray-600 dark:text-gray-400">\u0E44\u0E1F\u0E25\u0E4C\u0E41\u0E19\u0E1A</span></button><button class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:poll-24-regular",
        class: "w-5 h-5 text-amber-500"
      }, null, _parent));
      _push(`<span class="text-xs font-medium text-gray-600 dark:text-gray-400">\u0E42\u0E1E\u0E25</span></button></div>`);
      _push(ssrRenderComponent(CourseCreatePostModal, {
        show: showModal.value,
        "course-id": __props.courseId,
        "group-id": __props.groupId,
        onClose: closeModal,
        onPostCreated: handlePostCreated
      }, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseCreatePostBox.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "CourseFeedPost",
  __ssrInlineRender: true,
  props: {
    post: {},
    courseId: {},
    currentUserId: {},
    isCourseAdmin: { type: Boolean, default: false }
  },
  emits: ["edit", "delete", "post-updated"],
  setup(__props, { emit: __emit }) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m;
    const props = __props;
    const emit = __emit;
    const authStore = useAuthStore();
    useApi();
    const { getAvatarUrl } = useAvatar();
    const user = computed(() => authStore.user);
    const showMenu = ref(false);
    const showComments = ref(true);
    const showShareModal = ref(false);
    const selectedImageIndex = ref(null);
    const localIsLiked = ref(((_a = props.post) == null ? void 0 : _a.isLikedByAuth) || ((_b = props.post) == null ? void 0 : _b.is_liked) || false);
    const localIsDisliked = ref(((_c = props.post) == null ? void 0 : _c.isDislikedByAuth) || ((_d = props.post) == null ? void 0 : _d.is_disliked) || false);
    const localLikes = ref(((_e = props.post) == null ? void 0 : _e.likes) || ((_f = props.post) == null ? void 0 : _f.likes_count) || 0);
    const localDislikes = ref(((_g = props.post) == null ? void 0 : _g.dislikes) || ((_h = props.post) == null ? void 0 : _h.dislikes_count) || 0);
    const localCommentsCount = ref(((_i = props.post) == null ? void 0 : _i.comments_count) || 0);
    const localShares = ref(((_j = props.post) == null ? void 0 : _j.shares) || ((_k = props.post) == null ? void 0 : _k.shares_count) || 0);
    const isLiking = ref(false);
    const isDisliking = ref(false);
    const isCommenting = ref(false);
    const newComment = ref("");
    const displayedComments = ref(((_l = props.post) == null ? void 0 : _l.post_comments) || ((_m = props.post) == null ? void 0 : _m.comments) || []);
    const isLoadingComments = ref(false);
    ref(1);
    const hasMoreComments = ref(true);
    const replyingTo = ref(null);
    const replyContent = ref("");
    const isSubmittingReply = ref(false);
    const expandedReplies = ref({});
    const commentReplies = ref({});
    const loadingReplies = ref({});
    const isAuthor = computed(() => {
      var _a2, _b2;
      const currentUser = user.value;
      return ((_a2 = props.post.user) == null ? void 0 : _a2.id) === props.currentUserId || currentUser && ((_b2 = props.post.user) == null ? void 0 : _b2.id) === currentUser.id;
    });
    const postAuthor = computed(() => props.post.author || props.post.user || {});
    const postAuthorAvatar = computed(() => getAvatarUrl(postAuthor.value));
    const currentUserAvatar = computed(() => getAvatarUrl(user.value));
    const images = computed(() => {
      var _a2, _b2, _c2;
      if ((_a2 = props.post.imagesResources) == null ? void 0 : _a2.length) return props.post.imagesResources;
      if ((_b2 = props.post.images) == null ? void 0 : _b2.length) return props.post.images;
      if ((_c2 = props.post.media) == null ? void 0 : _c2.length) return props.post.media;
      return [];
    });
    const attachments = computed(() => props.post.attachments || []);
    const pollData = computed(() => props.post.poll || null);
    const formatDate = (date) => {
      if (!date) return "";
      if (typeof date === "string" && (date.includes("\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27") || date.includes("\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48"))) {
        return date;
      }
      const postDate = new Date(date);
      const now = /* @__PURE__ */ new Date();
      const diffMs = now.getTime() - postDate.getTime();
      const diffMins = Math.floor(diffMs / 6e4);
      const diffHours = Math.floor(diffMs / 36e5);
      const diffDays = Math.floor(diffMs / 864e5);
      if (diffMins < 1) return "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48";
      if (diffMins < 60) return `${diffMins} \u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diffHours < 24) return `${diffHours} \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      if (diffDays < 7) return `${diffDays} \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
      return postDate.toLocaleDateString("th-TH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const createdTime = computed(() => {
      return props.post.diff_humans_created_at || formatDate(props.post.created_at);
    });
    watch(() => props.post, (newPost) => {
      if (newPost) {
        localIsLiked.value = newPost.isLikedByAuth || newPost.is_liked || false;
        localIsDisliked.value = newPost.isDislikedByAuth || newPost.is_disliked || false;
        localLikes.value = newPost.likes || newPost.likes_count || 0;
        localDislikes.value = newPost.dislikes || newPost.dislikes_count || 0;
        localCommentsCount.value = newPost.comments_count || 0;
        localShares.value = newPost.shares || newPost.shares_count || 0;
        displayedComments.value = newPost.post_comments || newPost.comments || [];
      }
    }, { immediate: true });
    const handleShareSuccess = () => {
      localShares.value++;
      showShareModal.value = false;
    };
    const handlePollUpdate = (updatedPoll) => {
      if (props.post.poll) {
        Object.assign(props.post.poll, updatedPoll);
      }
      emit("post-updated", props.post);
    };
    const handlePollDelete = () => {
      emit("delete", props.post.id);
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a2;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden" }, _attrs))} data-v-658cffb4><div class="p-4 flex items-start gap-3" data-v-658cffb4>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/profile/${postAuthor.value.username}`
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", postAuthorAvatar.value)}${ssrRenderAttr("alt", postAuthor.value.name)} class="w-10 h-10 rounded-full object-cover" data-v-658cffb4${_scopeId}>`);
          } else {
            return [
              createVNode("img", {
                src: postAuthorAvatar.value,
                alt: postAuthor.value.name,
                class: "w-10 h-10 rounded-full object-cover"
              }, null, 8, ["src", "alt"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="flex-1 min-w-0" data-v-658cffb4><div class="flex items-center gap-2" data-v-658cffb4>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: `/profile/${postAuthor.value.username}`,
        class: "font-medium text-gray-900 dark:text-white hover:underline"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(postAuthor.value.name)}`);
          } else {
            return [
              createTextVNode(toDisplayString(postAuthor.value.name), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      if (postAuthor.value.role === "teacher" || postAuthor.value.role === "admin") {
        _push(`<span class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400" data-v-658cffb4> \u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19 </span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><p class="text-sm text-gray-500 dark:text-gray-400" data-v-658cffb4>${ssrInterpolate(createdTime.value)} `);
      if (__props.post.is_edited) {
        _push(`<span class="text-gray-400" data-v-658cffb4> \xB7 \u0E41\u0E01\u0E49\u0E44\u0E02\u0E41\u0E25\u0E49\u0E27</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</p></div>`);
      if (isAuthor.value || __props.isCourseAdmin) {
        _push(`<div class="relative" data-v-658cffb4><button class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 text-gray-400" data-v-658cffb4>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:more-horizontal-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`</button>`);
        if (showMenu.value) {
          _push(`<div class="absolute right-0 top-full mt-1 w-36 bg-white dark:bg-vikinger-dark-300 rounded-lg shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 py-1 z-10" data-v-658cffb4>`);
          if (isAuthor.value) {
            _push(`<button class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 flex items-center gap-2" data-v-658cffb4>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:edit-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2" data-v-658cffb4>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:delete-24-regular",
            class: "w-4 h-4"
          }, null, _parent));
          _push(` \u0E25\u0E1A </button></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="px-4 pb-3" data-v-658cffb4>`);
      if (__props.post.content) {
        _push(`<div class="prose dark:prose-invert prose-sm max-w-none whitespace-pre-wrap" data-v-658cffb4>${ssrInterpolate(__props.post.content)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
      if (images.value.length > 0) {
        _push(`<div class="relative" data-v-658cffb4>`);
        if (images.value.length === 1) {
          _push(`<img${ssrRenderAttr("src", images.value[0].url || images.value[0])}${ssrRenderAttr("alt", images.value[0].alt || "Post image")} class="w-full max-h-96 object-cover cursor-pointer" data-v-658cffb4>`);
        } else {
          _push(`<div class="${ssrRenderClass([[
            images.value.length === 2 ? "grid-cols-2" : "",
            images.value.length === 3 ? "grid-cols-3" : "",
            images.value.length >= 4 ? "grid-cols-2" : ""
          ], "grid gap-1"])}" data-v-658cffb4><!--[-->`);
          ssrRenderList(images.value.slice(0, 4), (image, index) => {
            _push(`<div class="relative aspect-square cursor-pointer" data-v-658cffb4><img${ssrRenderAttr("src", image.url || image)}${ssrRenderAttr("alt", image.alt || `Image ${index + 1}`)} class="w-full h-full object-cover" data-v-658cffb4>`);
            if (index === 3 && images.value.length > 4) {
              _push(`<div class="absolute inset-0 bg-black/50 flex items-center justify-center" data-v-658cffb4><span class="text-2xl font-bold text-white" data-v-658cffb4>+${ssrInterpolate(images.value.length - 4)}</span></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
          });
          _push(`<!--]--></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (attachments.value.length > 0) {
        _push(`<div class="px-4 py-2" data-v-658cffb4><div class="space-y-2" data-v-658cffb4><!--[-->`);
        ssrRenderList(attachments.value, (attachment) => {
          _push(`<a${ssrRenderAttr("href", attachment.url)} target="_blank" class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-50 transition-colors" data-v-658cffb4>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:document-24-regular",
            class: "w-5 h-5 text-blue-500"
          }, null, _parent));
          _push(`<span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate" data-v-658cffb4>${ssrInterpolate(attachment.name || attachment.filename)}</span><span class="text-xs text-gray-400" data-v-658cffb4>${ssrInterpolate(attachment.size)}</span></a>`);
        });
        _push(`<!--]--></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (pollData.value) {
        _push(`<div class="px-4 mb-3" data-v-658cffb4>`);
        _push(ssrRenderComponent(PollCard, {
          poll: pollData.value,
          "show-actions": isAuthor.value,
          "is-nested": true,
          onUpdate: handlePollUpdate,
          onDelete: handlePollDelete
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="px-4 py-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400" data-v-658cffb4><div class="flex items-center gap-3" data-v-658cffb4>`);
      if (localLikes.value > 0) {
        _push(`<div class="flex items-center gap-1" data-v-658cffb4><div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center" data-v-658cffb4>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:thumb-like-24-filled",
          class: "w-3 h-3 text-white"
        }, null, _parent));
        _push(`</div><span data-v-658cffb4>${ssrInterpolate(localLikes.value)}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      if (localDislikes.value > 0) {
        _push(`<div class="flex items-center gap-1" data-v-658cffb4><div class="w-5 h-5 rounded-full bg-gray-400 flex items-center justify-center" data-v-658cffb4>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:thumb-dislike-24-filled",
          class: "w-3 h-3 text-white"
        }, null, _parent));
        _push(`</div><span data-v-658cffb4>${ssrInterpolate(localDislikes.value)}</span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="flex items-center gap-4" data-v-658cffb4>`);
      if (localCommentsCount.value > 0) {
        _push(`<button class="hover:underline" data-v-658cffb4>${ssrInterpolate(localCommentsCount.value)} \u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19 </button>`);
      } else {
        _push(`<!---->`);
      }
      if (localShares.value > 0) {
        _push(`<span data-v-658cffb4>${ssrInterpolate(localShares.value)} \u0E41\u0E0A\u0E23\u0E4C</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="px-4 py-2 border-t border-gray-100 dark:border-vikinger-dark-50/30 flex items-center" data-v-658cffb4><button${ssrIncludeBooleanAttr(isLiking.value) ? " disabled" : ""} class="${ssrRenderClass([
        "flex-1 flex items-center justify-center gap-2 py-2 rounded-lg transition-colors",
        localIsLiked.value ? "text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20" : "text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100"
      ])}" data-v-658cffb4>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: localIsLiked.value ? "fluent:thumb-like-24-filled" : "fluent:thumb-like-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span data-v-658cffb4>\u0E16\u0E39\u0E01\u0E43\u0E08</span></button><button${ssrIncludeBooleanAttr(isDisliking.value) ? " disabled" : ""} class="${ssrRenderClass([
        "flex-1 flex items-center justify-center gap-2 py-2 rounded-lg transition-colors",
        localIsDisliked.value ? "text-gray-700 dark:text-gray-200" : "text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100"
      ])}" data-v-658cffb4>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: localIsDisliked.value ? "fluent:thumb-dislike-24-filled" : "fluent:thumb-dislike-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span data-v-658cffb4>\u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08</span></button><button class="flex-1 flex items-center justify-center gap-2 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors" data-v-658cffb4>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:chat-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span data-v-658cffb4>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19</span></button><button class="flex-1 flex items-center justify-center gap-2 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors" data-v-658cffb4>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:share-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`<span data-v-658cffb4>\u0E41\u0E0A\u0E23\u0E4C</span></button></div>`);
      if (showComments.value) {
        _push(`<div class="border-t border-gray-100 dark:border-vikinger-dark-50/30" data-v-658cffb4><div class="p-4 flex gap-3" data-v-658cffb4><img${ssrRenderAttr("src", currentUserAvatar.value)}${ssrRenderAttr("alt", (_a2 = user.value) == null ? void 0 : _a2.name)} class="w-8 h-8 rounded-full" data-v-658cffb4><div class="flex-1 flex gap-2" data-v-658cffb4><input${ssrRenderAttr("value", newComment.value)} type="text" placeholder="\u0E40\u0E02\u0E35\u0E22\u0E19\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19..." class="flex-1 px-4 py-2 bg-gray-100 dark:bg-vikinger-dark-100 rounded-full text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"${ssrIncludeBooleanAttr(isCommenting.value) ? " disabled" : ""} data-v-658cffb4><button${ssrIncludeBooleanAttr(!newComment.value.trim() || isCommenting.value) ? " disabled" : ""} class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" data-v-658cffb4>`);
        if (isCommenting.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:spinner-ios-20-regular",
            class: "w-4 h-4 animate-spin"
          }, null, _parent));
        } else {
          _push(`<span data-v-658cffb4>\u0E2A\u0E48\u0E07</span>`);
        }
        _push(`</button></div></div>`);
        if (displayedComments.value.length > 0) {
          _push(`<div class="px-4 pb-4 space-y-4" data-v-658cffb4><!--[-->`);
          ssrRenderList(displayedComments.value, (comment) => {
            var _a3, _b2, _c2, _d2, _e2, _f2, _g2, _h2, _i2, _j2, _k2;
            _push(`<div class="space-y-2" data-v-658cffb4><div class="flex gap-2" data-v-658cffb4><img${ssrRenderAttr("src", unref(getAvatarUrl)(comment.user || comment.author))}${ssrRenderAttr("alt", (_a3 = comment.user || comment.author) == null ? void 0 : _a3.name)} class="w-8 h-8 rounded-full" data-v-658cffb4><div class="flex-1" data-v-658cffb4><div class="bg-gray-100 dark:bg-vikinger-dark-100 rounded-xl px-3 py-2" data-v-658cffb4><div class="flex items-center gap-2" data-v-658cffb4><p class="font-medium text-sm text-gray-900 dark:text-white" data-v-658cffb4>${ssrInterpolate((_b2 = comment.user || comment.author) == null ? void 0 : _b2.name)}</p>`);
            if (((_c2 = comment.user || comment.author) == null ? void 0 : _c2.role) === "teacher") {
              _push(`<span class="px-1.5 py-0.5 text-xs rounded bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400" data-v-658cffb4> \u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19 </span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" data-v-658cffb4>${ssrInterpolate(comment.content)}</p></div><div class="flex items-center gap-4 mt-1 ml-2 text-xs text-gray-500" data-v-658cffb4><button class="${ssrRenderClass([
              "hover:text-blue-600",
              comment.isLikedByAuth || comment.is_liked ? "text-blue-600 font-medium" : ""
            ])}" data-v-658cffb4> \u0E16\u0E39\u0E01\u0E43\u0E08${ssrInterpolate(comment.likes > 0 ? ` (${comment.likes})` : "")}</button><button class="${ssrRenderClass([
              "hover:text-gray-700",
              comment.isDislikedByAuth || comment.is_disliked ? "text-gray-700 font-medium" : ""
            ])}" data-v-658cffb4> \u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08${ssrInterpolate(comment.dislikes > 0 ? ` (${comment.dislikes})` : "")}</button><button class="hover:text-blue-600" data-v-658cffb4> \u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A </button><span data-v-658cffb4>${ssrInterpolate(formatDate(comment.created_at))}</span>`);
            if (((_d2 = user.value) == null ? void 0 : _d2.id) === ((_e2 = comment.user || comment.author) == null ? void 0 : _e2.id) || __props.isCourseAdmin) {
              _push(`<button class="hover:text-red-600" data-v-658cffb4> \u0E25\u0E1A </button>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
            if (((_f2 = replyingTo.value) == null ? void 0 : _f2.id) === comment.id) {
              _push(`<div class="mt-2 flex gap-2" data-v-658cffb4><img${ssrRenderAttr("src", currentUserAvatar.value)}${ssrRenderAttr("alt", (_g2 = user.value) == null ? void 0 : _g2.name)} class="w-6 h-6 rounded-full" data-v-658cffb4><div class="flex-1 flex gap-2" data-v-658cffb4><input${ssrRenderAttr("value", replyContent.value)} type="text"${ssrRenderAttr("placeholder", `\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A ${(_h2 = comment.user || comment.author) == null ? void 0 : _h2.name}...`)} class="flex-1 px-3 py-1.5 bg-gray-100 dark:bg-vikinger-dark-100 rounded-full text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"${ssrIncludeBooleanAttr(isSubmittingReply.value) ? " disabled" : ""} data-v-658cffb4><button${ssrIncludeBooleanAttr(!replyContent.value.trim() || isSubmittingReply.value) ? " disabled" : ""} class="px-3 py-1.5 bg-blue-600 text-white rounded-full text-xs font-medium hover:bg-blue-700 disabled:opacity-50" data-v-658cffb4>`);
              if (isSubmittingReply.value) {
                _push(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:spinner-ios-20-regular",
                  class: "w-3 h-3 animate-spin"
                }, null, _parent));
              } else {
                _push(`<span data-v-658cffb4>\u0E2A\u0E48\u0E07</span>`);
              }
              _push(`</button><button class="px-3 py-1.5 text-gray-500 hover:text-gray-700 text-xs" data-v-658cffb4> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></div>`);
            } else {
              _push(`<!---->`);
            }
            if (comment.replies_count > 0 || ((_i2 = commentReplies.value[comment.id]) == null ? void 0 : _i2.length) > 0) {
              _push(`<button class="mt-2 ml-2 flex items-center gap-1 text-xs text-blue-600 hover:underline" data-v-658cffb4>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: expandedReplies.value[comment.id] ? "fluent:chevron-up-24-regular" : "fluent:chevron-down-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push(` ${ssrInterpolate(expandedReplies.value[comment.id] ? "\u0E0B\u0E48\u0E2D\u0E19" : "\u0E14\u0E39")} ${ssrInterpolate(comment.replies_count || ((_j2 = commentReplies.value[comment.id]) == null ? void 0 : _j2.length) || 0)} \u0E01\u0E32\u0E23\u0E15\u0E2D\u0E1A\u0E01\u0E25\u0E31\u0E1A </button>`);
            } else {
              _push(`<!---->`);
            }
            if (loadingReplies.value[comment.id]) {
              _push(`<div class="mt-2 ml-8" data-v-658cffb4>`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-4 h-4 animate-spin text-gray-400"
              }, null, _parent));
              _push(`</div>`);
            } else {
              _push(`<!---->`);
            }
            if (expandedReplies.value[comment.id] && ((_k2 = commentReplies.value[comment.id]) == null ? void 0 : _k2.length)) {
              _push(`<div class="mt-2 ml-8 space-y-3" data-v-658cffb4><!--[-->`);
              ssrRenderList(commentReplies.value[comment.id], (reply) => {
                var _a4, _b3;
                _push(`<div class="flex gap-2" data-v-658cffb4><img${ssrRenderAttr("src", unref(getAvatarUrl)(reply.user || reply.author))}${ssrRenderAttr("alt", (_a4 = reply.user || reply.author) == null ? void 0 : _a4.name)} class="w-6 h-6 rounded-full" data-v-658cffb4><div class="flex-1" data-v-658cffb4><div class="bg-gray-100 dark:bg-vikinger-dark-100 rounded-xl px-3 py-2" data-v-658cffb4><p class="font-medium text-xs text-gray-900 dark:text-white" data-v-658cffb4>${ssrInterpolate((_b3 = reply.user || reply.author) == null ? void 0 : _b3.name)}</p><p class="text-xs text-gray-700 dark:text-gray-300" data-v-658cffb4>${ssrInterpolate(reply.content)}</p></div><div class="flex items-center gap-3 mt-1 ml-2 text-xs text-gray-500" data-v-658cffb4><button class="${ssrRenderClass([
                  "hover:text-blue-600",
                  reply.isLikedByAuth || reply.is_liked ? "text-blue-600 font-medium" : ""
                ])}" data-v-658cffb4> \u0E16\u0E39\u0E01\u0E43\u0E08${ssrInterpolate(reply.likes > 0 ? ` (${reply.likes})` : "")}</button><button class="${ssrRenderClass([
                  "hover:text-gray-700",
                  reply.isDislikedByAuth || reply.is_disliked ? "text-gray-700 font-medium" : ""
                ])}" data-v-658cffb4> \u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E43\u0E08${ssrInterpolate(reply.dislikes > 0 ? ` (${reply.dislikes})` : "")}</button><span data-v-658cffb4>${ssrInterpolate(formatDate(reply.created_at))}</span></div></div></div>`);
              });
              _push(`<!--]--></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div></div>`);
          });
          _push(`<!--]-->`);
          if (hasMoreComments.value && localCommentsCount.value > displayedComments.value.length) {
            _push(`<button${ssrIncludeBooleanAttr(isLoadingComments.value) ? " disabled" : ""} class="w-full py-2 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg" data-v-658cffb4>`);
            if (isLoadingComments.value) {
              _push(ssrRenderComponent(unref(Icon), {
                icon: "fluent:spinner-ios-20-regular",
                class: "w-4 h-4 animate-spin inline mr-2"
              }, null, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(` \u0E14\u0E39\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21 (${ssrInterpolate(localCommentsCount.value - displayedComments.value.length)}) </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        } else {
          _push(`<div class="px-4 pb-4 text-center text-sm text-gray-500 dark:text-gray-400" data-v-658cffb4> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19 \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E27\u0E32\u0E21\u0E04\u0E34\u0E14\u0E40\u0E2B\u0E47\u0E19! </div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (showShareModal.value) {
        _push(ssrRenderComponent(ShareModal, {
          show: showShareModal.value,
          "shareable-type": "CoursePost",
          "shareable-id": __props.post.id,
          post: __props.post,
          onClose: ($event) => showShareModal.value = false,
          onShared: handleShareSuccess
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (selectedImageIndex.value !== null) {
        _push(ssrRenderComponent(ImageLightbox, {
          images: images.value,
          "initial-index": selectedImageIndex.value,
          onClose: ($event) => selectedImageIndex.value = null
        }, null, _parent));
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseFeedPost.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const CourseFeedPost = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-658cffb4"]]);
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "CourseEditPostModal",
  __ssrInlineRender: true,
  props: {
    show: { type: Boolean },
    post: {},
    courseId: {}
  },
  emits: ["close", "post-updated"],
  setup(__props, { emit: __emit }) {
    var _a;
    const props = __props;
    const { user } = useAuth();
    useApi();
    const postContent = ref(((_a = props.post) == null ? void 0 : _a.content) || "");
    const isSubmitting = ref(false);
    watch(() => props.post, (newPost) => {
      if (newPost) {
        postContent.value = newPost.content || "";
      }
    }, { immediate: true });
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a2, _b, _c, _d, _e, _f, _g, _h;
        if (__props.show) {
          _push2(`<div class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto backdrop-blur-sm" data-v-1bfd21ea><div class="w-full max-w-2xl mx-4 mb-10 modal-content" data-v-1bfd21ea><div class="bg-white dark:bg-vikinger-dark-300 rounded-xl shadow-2xl" data-v-1bfd21ea><div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30" data-v-1bfd21ea><div class="flex items-center gap-2" data-v-1bfd21ea>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:edit-24-regular",
            class: "w-5 h-5 text-blue-500"
          }, null, _parent));
          _push2(`<h2 class="text-xl font-bold text-gray-800 dark:text-white" data-v-1bfd21ea>\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1E\u0E2A\u0E15\u0E4C</h2></div><button class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full" data-v-1bfd21ea>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-regular",
            class: "w-6 h-6 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-4" data-v-1bfd21ea><div class="flex items-center gap-3 mb-4" data-v-1bfd21ea><img${ssrRenderAttr("src", ((_a2 = __props.post.user) == null ? void 0 : _a2.avatar) || ((_b = __props.post.author) == null ? void 0 : _b.avatar) || ((_c = unref(user)) == null ? void 0 : _c.avatar) || "/images/default-avatar.png")} class="w-10 h-10 rounded-full object-cover" data-v-1bfd21ea><div data-v-1bfd21ea><div class="font-medium text-gray-800 dark:text-white" data-v-1bfd21ea>${ssrInterpolate(((_d = __props.post.user) == null ? void 0 : _d.name) || ((_e = __props.post.author) == null ? void 0 : _e.name) || ((_f = unref(user)) == null ? void 0 : _f.name))}</div><div class="flex items-center gap-1 text-xs text-gray-500" data-v-1bfd21ea>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:book-24-regular",
            class: "w-3 h-3"
          }, null, _parent));
          _push2(`<span data-v-1bfd21ea>\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</span></div></div></div><div class="rounded-lg mb-4 min-h-[150px] p-4 bg-gray-50 dark:bg-vikinger-dark-200" data-v-1bfd21ea><textarea placeholder="\u0E41\u0E01\u0E49\u0E44\u0E02\u0E40\u0E19\u0E37\u0E49\u0E2D\u0E2B\u0E32\u0E42\u0E1E\u0E2A\u0E15\u0E4C..." rows="6" class="w-full bg-transparent border-none outline-none resize-none text-gray-800 dark:text-white placeholder-gray-400"${ssrIncludeBooleanAttr(isSubmitting.value) ? " disabled" : ""} data-v-1bfd21ea>${ssrInterpolate(postContent.value)}</textarea></div>`);
          if (((_g = __props.post.images) == null ? void 0 : _g.length) || ((_h = __props.post.media) == null ? void 0 : _h.length)) {
            _push2(`<div class="mb-4" data-v-1bfd21ea><p class="text-sm text-gray-500 mb-2" data-v-1bfd21ea>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E43\u0E19\u0E42\u0E1E\u0E2A\u0E15\u0E4C (\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E41\u0E01\u0E49\u0E44\u0E02\u0E44\u0E14\u0E49)</p><div class="flex flex-wrap gap-2" data-v-1bfd21ea><!--[-->`);
            ssrRenderList(__props.post.images || __props.post.media || __props.post.imagesResources || [], (image, index) => {
              _push2(`<img${ssrRenderAttr("src", image.url || image)} class="w-20 h-20 object-cover rounded-lg opacity-70" data-v-1bfd21ea>`);
            });
            _push2(`<!--]--></div></div>`);
          } else {
            _push2(`<!---->`);
          }
          _push2(`</div><div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30 flex gap-3" data-v-1bfd21ea><button class="flex-1 py-3 px-4 bg-gray-100 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-vikinger-dark-100 transition-all" data-v-1bfd21ea> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(isSubmitting.value || !postContent.value.trim()) ? " disabled" : ""} class="flex-1 py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2" data-v-1bfd21ea>`);
          if (isSubmitting.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(`<span data-v-1bfd21ea>${ssrInterpolate(isSubmitting.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")}</span></button></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseEditPostModal.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const CourseEditPostModal = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-1bfd21ea"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "CourseFeedsList",
  __ssrInlineRender: true,
  props: {
    courseId: {},
    isCourseAdmin: { type: Boolean, default: false },
    groupId: {},
    initialTab: { default: "all" }
  },
  setup(__props) {
    const props = __props;
    useApi();
    const { user } = useAuth();
    const posts = ref([]);
    const loading = ref(true);
    const loadingMore = ref(false);
    const hasMore = ref(true);
    ref(1);
    ref(10);
    const error = ref(null);
    const showEditModal = ref(false);
    const editingPost = ref(null);
    const activeTab = ref(props.initialTab);
    const tabs = [
      { id: "all", label: "\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14", icon: "fluent:feed-24-regular" },
      { id: "discussions", label: "\u0E1E\u0E39\u0E14\u0E04\u0E38\u0E22", icon: "fluent:chat-multiple-24-regular" },
      { id: "questions", label: "\u0E04\u0E33\u0E16\u0E32\u0E21", icon: "fluent:question-circle-24-regular" },
      { id: "materials", label: "\u0E40\u0E2D\u0E01\u0E2A\u0E32\u0E23", icon: "fluent:document-24-regular" },
      { id: "announcements", label: "\u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28", icon: "fluent:megaphone-24-regular" }
    ];
    ref(null);
    const handlePostCreated = (newPost) => {
      if (newPost) {
        posts.value.unshift(newPost);
      }
    };
    const handlePostUpdated = (updatedPost) => {
      const index = posts.value.findIndex((p) => p.id === updatedPost.id);
      if (index !== -1) {
        posts.value[index] = { ...posts.value[index], ...updatedPost };
      }
      showEditModal.value = false;
      editingPost.value = null;
    };
    const handlePostDeleted = (postId) => {
      posts.value = posts.value.filter((p) => p.id !== postId);
    };
    const openEditModal = (post) => {
      editingPost.value = { ...post };
      showEditModal.value = true;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-4" }, _attrs))} data-v-0f6913ec>`);
      _push(ssrRenderComponent(_sfc_main$3, {
        "course-id": __props.courseId,
        "group-id": __props.groupId,
        onPostCreated: handlePostCreated
      }, null, _parent));
      _push(`<div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide" data-v-0f6913ec><!--[-->`);
      ssrRenderList(tabs, (tab) => {
        _push(`<button class="${ssrRenderClass([
          activeTab.value === tab.id ? "bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg" : "bg-white dark:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100",
          "flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all duration-300"
        ])}" data-v-0f6913ec>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: tab.icon,
          class: "w-4 h-4"
        }, null, _parent));
        _push(` ${ssrInterpolate(tab.label)}</button>`);
      });
      _push(`<!--]--></div>`);
      if (error.value && !loading.value) {
        _push(`<div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-6 text-center" data-v-0f6913ec>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-regular",
          class: "w-12 h-12 mx-auto mb-3 text-red-500"
        }, null, _parent));
        _push(`<p class="text-red-600 dark:text-red-400 mb-4" data-v-0f6913ec>${ssrInterpolate(error.value)}</p><button class="px-6 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors" data-v-0f6913ec>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-sync-24-regular",
          class: "w-4 h-4 inline mr-2"
        }, null, _parent));
        _push(` \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </button></div>`);
      } else if (loading.value) {
        _push(`<div class="space-y-4" data-v-0f6913ec><!--[-->`);
        ssrRenderList(3, (i) => {
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-6 animate-pulse" data-v-0f6913ec><div class="flex items-center gap-4 mb-4" data-v-0f6913ec><div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full" data-v-0f6913ec></div><div class="flex-1 space-y-2" data-v-0f6913ec><div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/4" data-v-0f6913ec></div><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/6" data-v-0f6913ec></div></div></div><div class="space-y-2" data-v-0f6913ec><div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-full" data-v-0f6913ec></div><div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4" data-v-0f6913ec></div></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!--[--><div class="flex justify-center" data-v-0f6913ec><button class="flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full transition-colors" data-v-0f6913ec>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-sync-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(` \u0E23\u0E35\u0E40\u0E1F\u0E23\u0E0A\u0E1F\u0E35\u0E14 </button></div>`);
        if (posts.value.length > 0) {
          _push(`<div${ssrRenderAttrs({
            name: "feed",
            class: "space-y-4"
          })} data-v-0f6913ec>`);
          ssrRenderList(posts.value, (post) => {
            var _a;
            _push(ssrRenderComponent(CourseFeedPost, {
              key: post.id,
              post,
              "course-id": __props.courseId,
              "current-user-id": (_a = unref(user)) == null ? void 0 : _a.id,
              "is-course-admin": __props.isCourseAdmin,
              onEdit: openEditModal,
              onDelete: handlePostDeleted,
              onPostUpdated: handlePostUpdated
            }, null, _parent));
          });
          _push(`</div>`);
        } else {
          _push(`<div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center" data-v-0f6913ec>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:chat-24-regular",
            class: "w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600"
          }, null, _parent));
          _push(`<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2" data-v-0f6913ec> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E42\u0E1E\u0E2A\u0E15\u0E4C </h3><p class="text-gray-500 dark:text-gray-400" data-v-0f6913ec> \u0E40\u0E1B\u0E47\u0E19\u0E04\u0E19\u0E41\u0E23\u0E01\u0E17\u0E35\u0E48\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49! </p></div>`);
        }
        if (hasMore.value && posts.value.length > 0) {
          _push(`<div class="flex justify-center py-6" data-v-0f6913ec>`);
          if (loadingMore.value) {
            _push(`<div class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-vikinger-dark-200 text-blue-600 rounded-full shadow-sm" data-v-0f6913ec>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:spinner-ios-20-regular",
              class: "w-5 h-5 animate-spin"
            }, null, _parent));
            _push(` \u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14... </div>`);
          } else {
            _push(`<div class="h-4 w-full" data-v-0f6913ec></div>`);
          }
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        if (!hasMore.value && posts.value.length > 0) {
          _push(`<div class="text-center py-6 text-gray-500 dark:text-gray-400" data-v-0f6913ec>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-regular",
            class: "w-8 h-8 mx-auto mb-2 text-green-500"
          }, null, _parent));
          _push(`<p data-v-0f6913ec>\u0E04\u0E38\u0E13\u0E44\u0E14\u0E49\u0E14\u0E39\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14\u0E41\u0E25\u0E49\u0E27!</p></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      }
      if (showEditModal.value && editingPost.value) {
        _push(ssrRenderComponent(CourseEditPostModal, {
          show: showEditModal.value,
          post: editingPost.value,
          "course-id": __props.courseId,
          onClose: ($event) => {
            showEditModal.value = false;
            editingPost.value = null;
          },
          onPostUpdated: handlePostUpdated
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseFeedsList.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CourseFeedsList = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-0f6913ec"]]);

export { CourseFeedsList as C };
//# sourceMappingURL=CourseFeedsList-Ef74fyl-.mjs.map
