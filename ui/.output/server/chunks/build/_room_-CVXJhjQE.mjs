import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, computed, ref, mergeProps, withCtx, unref, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderTeleport, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { p as useRoute, b as useRuntimeConfig, f as useHead } from './server.mjs';
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
  __name: "[room]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const config = useRuntimeConfig();
    config.public.apiBase || "";
    const level = computed(() => Number(route.params.level));
    const room = computed(() => Number(route.params.room));
    const levelName = computed(() => {
      const names = ["", "\u0E21.1", "\u0E21.2", "\u0E21.3", "\u0E21.4", "\u0E21.5", "\u0E21.6"];
      return names[level.value] || "";
    });
    useHead({
      title: computed(() => `\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 ${levelName.value}/${room.value}`)
    });
    const students = ref([]);
    const selectedStudent = ref(null);
    const isLoading = ref(true);
    const isUploading = ref(false);
    const isSaving = ref(false);
    const editForm = ref({
      student_number: "",
      title_name: "",
      first_name_thai: "",
      last_name_thai: "",
      first_name_english: "",
      last_name_english: ""
    });
    const getStudentImageUrl = (student) => {
      if (student.profile_image) {
        return `/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`;
      }
      return null;
    };
    const formatNationalId = (id) => {
      if (!id || id.length !== 13) return id;
      return id.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, "$1-$2-$3-$4-$5");
    };
    ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-purple-100 via-white to-purple-200" }, _attrs))}><div class="bg-white shadow-sm sticky top-0 z-40"><div class="max-w-7xl mx-auto px-4 py-4"><div class="flex items-center justify-between"><div class="flex items-center gap-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student-card/admin",
        class: "p-2 hover:bg-gray-100 rounded-lg transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:arrow-left",
              class: "w-6 h-6 text-gray-600"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(unref(Icon), {
                icon: "heroicons:arrow-left",
                class: "w-6 h-6 text-gray-600"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div><div class="flex items-center gap-2"><h1 class="text-xl font-bold text-gray-800">\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 ${ssrInterpolate(levelName.value)}/${ssrInterpolate(room.value)}</h1><span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">Admin</span></div><p class="text-sm text-gray-500">${ssrInterpolate(students.value.length)} \u0E04\u0E19</p></div></div></div></div></div>`);
      if (isLoading.value) {
        _push(`<div class="flex items-center justify-center py-20"><div class="text-center"><div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div><p class="text-gray-600">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div></div>`);
      } else {
        _push(`<div class="max-w-7xl mx-auto px-4 py-6">`);
        if (students.value.length === 0) {
          _push(`<div class="text-center py-20">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:user-group",
            class: "w-16 h-16 text-gray-300 mx-auto mb-4"
          }, null, _parent));
          _push(`<p class="text-gray-500">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
        } else {
          _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"><!--[-->`);
          ssrRenderList(students.value, (student) => {
            _push(`<div class="bg-white rounded-xl shadow-md overflow-hidden"><div class="relative aspect-[1.6/1] bg-gradient-to-br from-purple-500 to-purple-600 p-4"><div class="absolute top-2 right-2 bg-white/20 backdrop-blur px-2 py-1 rounded text-xs text-white">${ssrInterpolate(student.student_number)}</div><div class="flex items-center gap-3 h-full"><div class="w-20 h-24 bg-white rounded-lg overflow-hidden flex-shrink-0 relative group">`);
            if (getStudentImageUrl(student)) {
              _push(`<img${ssrRenderAttr("src", getStudentImageUrl(student))}${ssrRenderAttr("alt", student.full_name_thai)} class="w-full h-full object-cover">`);
            } else {
              _push(`<div class="w-full h-full flex items-center justify-center bg-gray-100">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user",
                class: "w-8 h-8 text-gray-400"
              }, null, _parent));
              _push(`</div>`);
            }
            _push(`<label class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:camera",
              class: "w-6 h-6 text-white"
            }, null, _parent));
            _push(`<input type="file" accept="image/*" class="hidden"></label></div><div class="flex-1 min-w-0 text-white"><p class="font-semibold text-sm truncate">${ssrInterpolate(student.full_name_thai || `${student.title_name}${student.first_name_thai} ${student.last_name_thai}`)}</p><p class="text-xs opacity-80">${ssrInterpolate(levelName.value)}/${ssrInterpolate(room.value)}</p></div></div></div><div class="p-3 border-t"><div class="flex items-center justify-between"><span class="text-sm text-gray-500">\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 ${ssrInterpolate(student.order_no)}</span><button class="px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium hover:bg-purple-200 transition-colors">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:pencil-square",
              class: "w-4 h-4 inline mr-1"
            }, null, _parent));
            _push(` \u0E41\u0E01\u0E49\u0E44\u0E02 </button></div></div></div>`);
          });
          _push(`<!--]--></div>`);
        }
        _push(`</div>`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (selectedStudent.value) {
          _push2(`<div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"><div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between"><h2 class="text-lg font-bold text-gray-800">\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h2><button class="p-2 hover:bg-gray-100 rounded-lg">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:x-mark",
            class: "w-6 h-6 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-6"><div class="flex items-center gap-6 mb-6"><div class="relative"><div class="w-28 h-36 bg-gray-100 rounded-xl overflow-hidden">`);
          if (getStudentImageUrl(selectedStudent.value)) {
            _push2(`<img${ssrRenderAttr("src", getStudentImageUrl(selectedStudent.value))}${ssrRenderAttr("alt", selectedStudent.value.full_name_thai)} class="w-full h-full object-cover">`);
          } else {
            _push2(`<div class="w-full h-full flex items-center justify-center">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:user",
              class: "w-12 h-12 text-gray-400"
            }, null, _parent));
            _push2(`</div>`);
          }
          _push2(`</div><label class="absolute -bottom-2 -right-2 p-2 bg-purple-500 text-white rounded-full cursor-pointer hover:bg-purple-600 transition-colors shadow-lg">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:camera",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`<input type="file" accept="image/*" class="hidden"></label></div><div><p class="text-lg font-bold text-gray-800">${ssrInterpolate(selectedStudent.value.full_name_thai)}</p><p class="text-sm text-gray-500">${ssrInterpolate(levelName.value)}/${ssrInterpolate(room.value)} \u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 ${ssrInterpolate(selectedStudent.value.order_no)}</p></div></div><div class="space-y-4"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</label><div class="flex gap-2"><input${ssrRenderAttr("value", editForm.value.student_number)} type="text" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50 transition-colors"> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div><div class="grid grid-cols-3 gap-3"><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E04\u0E33\u0E19\u0E33\u0E2B\u0E19\u0E49\u0E32</label><select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><option value=""${ssrIncludeBooleanAttr(Array.isArray(editForm.value.title_name) ? ssrLooseContain(editForm.value.title_name, "") : ssrLooseEqual(editForm.value.title_name, "")) ? " selected" : ""}>\u0E40\u0E25\u0E37\u0E2D\u0E01</option><option value="\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27"${ssrIncludeBooleanAttr(Array.isArray(editForm.value.title_name) ? ssrLooseContain(editForm.value.title_name, "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27") : ssrLooseEqual(editForm.value.title_name, "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27")) ? " selected" : ""}>\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27</option><option value="\u0E19\u0E32\u0E22"${ssrIncludeBooleanAttr(Array.isArray(editForm.value.title_name) ? ssrLooseContain(editForm.value.title_name, "\u0E19\u0E32\u0E22") : ssrLooseEqual(editForm.value.title_name, "\u0E19\u0E32\u0E22")) ? " selected" : ""}>\u0E19\u0E32\u0E22</option><option value="\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07"${ssrIncludeBooleanAttr(Array.isArray(editForm.value.title_name) ? ssrLooseContain(editForm.value.title_name, "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07") : ssrLooseEqual(editForm.value.title_name, "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07")) ? " selected" : ""}>\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07</option><option value="\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22"${ssrIncludeBooleanAttr(Array.isArray(editForm.value.title_name) ? ssrLooseContain(editForm.value.title_name, "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22") : ssrLooseEqual(editForm.value.title_name, "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22")) ? " selected" : ""}>\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E0A\u0E37\u0E48\u0E2D (\u0E44\u0E17\u0E22)</label><input${ssrRenderAttr("value", editForm.value.first_name_thai)} type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25 (\u0E44\u0E17\u0E22)</label><input${ssrRenderAttr("value", editForm.value.last_name_thai)} type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div></div><div class="flex justify-end"><button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50 transition-colors">`);
          if (isSaving.value) {
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:arrow-path",
              class: "w-5 h-5 animate-spin inline mr-2"
            }, null, _parent));
          } else {
            _push2(`<!---->`);
          }
          _push2(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25 </button></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</label><input${ssrRenderAttr("value", formatNationalId(selectedStudent.value.national_id))} type="text" disabled class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 font-mono"></div></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      if (isUploading.value) {
        _push(`<div class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center"><div class="bg-white rounded-xl p-6 text-center"><div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div><p class="text-gray-600">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E...</p></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student-card/admin/students/[level]/[room].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_room_-CVXJhjQE.mjs.map
