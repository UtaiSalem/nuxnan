import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, computed, ref, mergeProps, withCtx, unref, createVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderTeleport } from 'vue/server-renderer';
import QRCodeVue3 from 'qrcode-vue3';
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
      title: computed(() => `\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 ${levelName.value}/${room.value}`)
    });
    const students = ref([]);
    const selectedStudent = ref(null);
    const isLoading = ref(true);
    ref(false);
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
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-blue-100 via-white to-blue-200" }, _attrs))}><div class="bg-white shadow-sm sticky top-0 z-40"><div class="max-w-7xl mx-auto px-4 py-4"><div class="flex items-center justify-between"><div class="flex items-center gap-4">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/student-card",
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
      _push(`<div><h1 class="text-xl font-bold text-gray-800">\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 ${ssrInterpolate(levelName.value)}/${ssrInterpolate(room.value)}</h1><p class="text-sm text-gray-500">${ssrInterpolate(students.value.length)} \u0E04\u0E19</p></div></div></div></div></div>`);
      if (isLoading.value) {
        _push(`<div class="flex items-center justify-center py-20"><div class="text-center"><div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div><p class="text-gray-600">\u0E01\u0E33\u0E25\u0E31\u0E07\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</p></div></div>`);
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
            _push(`<div class="bg-white rounded-xl shadow-md overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"><div class="relative aspect-[1.6/1] bg-gradient-to-br from-blue-500 to-blue-600 p-4"><div class="absolute top-2 right-2 bg-white/20 backdrop-blur px-2 py-1 rounded text-xs text-white">${ssrInterpolate(student.student_number)}</div><div class="flex items-center gap-3 h-full"><div class="w-20 h-24 bg-white rounded-lg overflow-hidden flex-shrink-0">`);
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
            _push(`</div><div class="flex-1 min-w-0 text-white"><p class="font-semibold text-sm truncate">${ssrInterpolate(student.full_name_thai || `${student.title_name}${student.first_name_thai} ${student.last_name_thai}`)}</p><p class="text-xs opacity-80">${ssrInterpolate(levelName.value)}/${ssrInterpolate(room.value)}</p></div></div></div><div class="p-3 border-t"><div class="flex items-center justify-between text-sm"><span class="text-gray-500">\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 ${ssrInterpolate(student.order_no)}</span><button class="text-blue-600 hover:text-blue-700 font-medium"> \u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </button></div></div></div>`);
          });
          _push(`<!--]--></div>`);
        }
        _push(`</div>`);
      }
      ssrRenderTeleport(_push, (_push2) => {
        if (selectedStudent.value) {
          _push2(`<div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"><div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between"><h2 class="text-lg font-bold text-gray-800">\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h2><button class="p-2 hover:bg-gray-100 rounded-lg">`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:x-mark",
            class: "w-6 h-6 text-gray-500"
          }, null, _parent));
          _push2(`</button></div><div class="p-6"><div class="bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 rounded-2xl p-6 text-white mb-6 shadow-lg"><div class="flex items-start gap-4"><div class="w-28 h-36 bg-white rounded-xl overflow-hidden flex-shrink-0 shadow-md">`);
          if (getStudentImageUrl(selectedStudent.value)) {
            _push2(`<img${ssrRenderAttr("src", getStudentImageUrl(selectedStudent.value))}${ssrRenderAttr("alt", selectedStudent.value.full_name_thai)} class="w-full h-full object-cover">`);
          } else {
            _push2(`<div class="w-full h-full flex items-center justify-center bg-gray-100">`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:user",
              class: "w-12 h-12 text-gray-400"
            }, null, _parent));
            _push2(`</div>`);
          }
          _push2(`</div><div class="flex-1"><p class="text-xl font-bold mb-1">${ssrInterpolate(selectedStudent.value.full_name_thai || `${selectedStudent.value.title_name}${selectedStudent.value.first_name_thai} ${selectedStudent.value.last_name_thai}`)}</p><p class="text-blue-100 text-sm mb-3">${ssrInterpolate(selectedStudent.value.first_name_english)} ${ssrInterpolate(selectedStudent.value.last_name_english)}</p><div class="space-y-1 text-sm"><p><span class="opacity-70">\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19:</span> ${ssrInterpolate(selectedStudent.value.student_number)}</p><p><span class="opacity-70">\u0E0A\u0E31\u0E49\u0E19:</span> ${ssrInterpolate(levelName.value)}/${ssrInterpolate(room.value)}</p><p><span class="opacity-70">\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48:</span> ${ssrInterpolate(selectedStudent.value.order_no)}</p></div></div><div class="bg-white p-2 rounded-xl">`);
          _push2(ssrRenderComponent(unref(QRCodeVue3), {
            value: selectedStudent.value.student_number || "",
            width: 80,
            height: 80
          }, null, _parent));
          _push2(`</div></div></div><div class="grid grid-cols-2 gap-4"><div class="bg-gray-50 rounded-xl p-4"><p class="text-sm text-gray-500 mb-1">\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</p><p class="font-mono font-semibold text-gray-800">${ssrInterpolate(formatNationalId(selectedStudent.value.national_id))}</p></div><div class="bg-gray-50 rounded-xl p-4"><p class="text-sm text-gray-500 mb-1">\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</p><p class="font-semibold text-gray-800">${ssrInterpolate(selectedStudent.value.birth_date_string || selectedStudent.value.birth_date || "-")}</p></div></div></div></div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
      _push(`</div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/student-card/[level]/[room].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_room_-C-rTm_Iy.mjs.map
