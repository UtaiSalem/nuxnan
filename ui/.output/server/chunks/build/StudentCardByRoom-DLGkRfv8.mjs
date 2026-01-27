import { ref, computed, unref, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderList } from 'vue/server-renderer';
import StudentCard from './StudentCard-DqUbauQQ.mjs';
import { H as Head } from './inertia-vue3-CWdJjaLG.mjs';
import './virtual_public-Zc294sLl.mjs';
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
import 'qrcode-vue3';
import '@iconify/vue';
import 'sweetalert2';
import '@headlessui/vue';
import './server.mjs';
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "StudentCardByRoom",
  __ssrInlineRender: true,
  props: {
    students: {
      type: Array,
      default: () => []
    },
    room: String,
    level: String
  },
  setup(__props) {
    const props = __props;
    const searchTerm = ref("");
    const filteredStudents = computed(() => {
      if (!searchTerm.value) return props.students;
      if (!props.students) return [];
      return props.students.filter((student) => {
        const searchTermLower = searchTerm.value.toLowerCase();
        return student.student_name_th && student.student_name_th.toLowerCase().includes(searchTermLower) || student.student_id && student.student_id.toString().includes(searchTerm.value);
      });
    });
    const handlePhotoUpload = async ({ id, studentId, file }) => {
      if (!file || !studentId) return;
      const formData = new FormData();
      formData.append("photo", file);
      formData.append("_method", "patch");
      try {
        const response2 = await axios.post("/student-card/admin/upload-photo/" + id, formData, {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        });
      } catch (error) {
        console.error("Upload failed:", error);
      }
    };
    const handleUpdateStudent = async (updatedData) => {
      try {
        const response2 = await axios.put(`/student/${updatedData.id}`, updatedData);
        if (response2.data.success) {
          const index = props.students.findIndex((s) => s.id === updatedData.id);
          if (index !== -1) {
            props.students[index] = { ...props.students[index], ...updatedData };
          }
          alert("\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08");
        }
      } catch (error) {
        console.error("Update failed:", error);
        alert("\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25");
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E0A\u0E31\u0E49\u0E19 \u0E21.${__props.level}/${__props.room}`
      }, null, _parent));
      _push(`<div class="min-h-screen"><div class="max-w-7xl bg-slate-200 mx-auto"><div class="bg-white rounded-2xl shadow-xl p-6 mb-6"><div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4"><div class="space-y-2"><h1 class="text-2xl font-bold text-gray-800">\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h1><div class="flex gap-4"><div class="flex items-center gap-2"><span class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold"> \u0E0A\u0E31\u0E49\u0E19 \u0E21.${ssrInterpolate(__props.level)}/${ssrInterpolate(__props.room)}</span></div><div class="flex items-center gap-2 text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg><span class="font-medium">${ssrInterpolate(((_a = __props.students) == null ? void 0 : _a.length) || 0)} \u0E04\u0E19</span></div></div></div><div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full md:w-auto"><div class="relative w-full sm:w-80"><input type="text"${ssrRenderAttr("value", searchTerm.value)} placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E0A\u0E37\u0E48\u0E2D\u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19..." class="w-full px-4 py-2 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"><svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div><div class="flex items-center w-full sm:w-auto"><button class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200"><svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg> \u0E01\u0E25\u0E31\u0E1A </button></div></div></div></div>`);
      if (!__props.students || __props.students.length === 0) {
        _push(`<div class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-xl p-12"><svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg><p class="mt-4 text-gray-500 text-lg">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
      } else {
        _push(`<div class="grid grid-cols-1 gap-4"><!--[-->`);
        ssrRenderList(filteredStudents.value, (student) => {
          _push(`<div>`);
          _push(ssrRenderComponent(StudentCard, {
            studentInfo: student,
            onUpdate: handleUpdateStudent,
            onUploadImage: handlePhotoUpload
          }, null, _parent));
          _push(`</div>`);
        });
        _push(`<!--]--></div>`);
      }
      _push(`</div></div><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/Card/StudentCardByRoom.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=StudentCardByRoom-DLGkRfv8.mjs.map
