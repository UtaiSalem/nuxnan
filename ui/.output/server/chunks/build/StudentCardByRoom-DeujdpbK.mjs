import { ref, computed, unref, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderList, ssrRenderStyle, ssrRenderClass } from 'vue/server-renderer';
import { _ as _imports_1 } from './virtual_public-Zc294sLl.mjs';
import { H as Head } from './inertia-vue3-CWdJjaLG.mjs';
import QRCodeVue3 from 'qrcode-vue3';
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
import 'unhead/utils';
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';

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
    ref([]);
    const searchTerm = ref("");
    const filteredStudents = computed(() => {
      if (!searchTerm.value) return props.students;
      if (!props.students) return [];
      return props.students.filter((student) => {
        const searchTermLower = searchTerm.value.toLowerCase();
        return student.first_name_thai && student.first_name_thai.toLowerCase().includes(searchTermLower) || student.student_number && student.student_number.toString().includes(searchTerm.value);
      });
    });
    const studentPrefixName = (idx) => {
      const student = props.students[idx];
      if (!student.first_name_english) return "";
      if (student.title_name == "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07" || student.title_name == "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27") {
        return "Ms.";
      } else if (student.title_name == "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22" || student.title_name == "\u0E19\u0E32\u0E22") {
        return "Mr.";
      } else {
        return "";
      }
    };
    const formattedIdNumber = (idNumber) => {
      if (!idNumber) return "";
      const idString = String(idNumber);
      if (idString.length !== 13) return idString;
      const id = idString.replace(/\D/g, "");
      return id.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, "$1-$2-$3-$4-$5");
    };
    const studentThaiPrefixName = (index) => {
      var _a;
      const student = props.students[index];
      if (!(student == null ? void 0 : student.first_name_thai)) return { prefix: "", txtSize: "text-[46px]" };
      const fullLength = (student.first_name_thai.length || 0) + (((_a = student.last_name_thai) == null ? void 0 : _a.length) || 0);
      const isGirl = student.title_name === "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07";
      const isBoy = student.title_name === "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22";
      const isMiss = student.title_name === "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27";
      if (fullLength < 15) {
        return { prefix: student.title_name, txtSize: "text-[46px]" };
      }
      if (fullLength > 20) {
        if (isGirl) return { prefix: "\u0E14.\u0E0D.", txtSize: "text-[42px]" };
        if (isBoy) return { prefix: "\u0E14.\u0E0A.", txtSize: "text-[42px]" };
        if (isMiss) return { prefix: "\u0E19.\u0E2A.", txtSize: "text-[42px]" };
        return { prefix: "", txtSize: "text-[42px]" };
      }
      if (isGirl) return { prefix: "\u0E14.\u0E0D.", txtSize: "text-[46px]" };
      if (isBoy) return { prefix: "\u0E14.\u0E0A.", txtSize: "text-[46px]" };
      if (isMiss) return { prefix: "\u0E19.\u0E2A.", txtSize: "text-[46px]" };
      return { prefix: "", txtSize: "text-[44px]" };
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E0A\u0E31\u0E49\u0E19 \u0E21.${__props.level}/${__props.room}`
      }, null, _parent));
      _push(`<div class="min-h-screen" data-v-c7705de8><div class="max-w-7xl mx-auto" data-v-c7705de8><div class="bg-white rounded-2xl shadow-xl p-6 mb-6" data-v-c7705de8><div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4" data-v-c7705de8><div class="space-y-2" data-v-c7705de8><h1 class="text-2xl font-bold text-gray-800" data-v-c7705de8>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h1><div class="flex gap-4" data-v-c7705de8><div class="flex items-center gap-2" data-v-c7705de8><span class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold" data-v-c7705de8> \u0E0A\u0E31\u0E49\u0E19 \u0E21.${ssrInterpolate(__props.level)}/${ssrInterpolate(__props.room)}</span></div><div class="flex items-center gap-2 text-gray-600" data-v-c7705de8><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-c7705de8><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" data-v-c7705de8></path></svg><span class="font-medium" data-v-c7705de8>${ssrInterpolate(((_a = __props.students) == null ? void 0 : _a.length) || 0)} \u0E04\u0E19</span></div></div></div><div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full md:w-auto" data-v-c7705de8><div class="relative w-full sm:w-80" data-v-c7705de8><input id="student-search-input" type="text"${ssrRenderAttr("value", searchTerm.value)} placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E0A\u0E37\u0E48\u0E2D\u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19..." class="w-full px-4 py-2 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" data-v-c7705de8><svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-c7705de8><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" data-v-c7705de8></path></svg></div><div class="flex items-center w-full sm:w-auto" data-v-c7705de8><button class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200" data-v-c7705de8><svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-c7705de8><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" data-v-c7705de8></path></svg> \u0E01\u0E25\u0E31\u0E1A </button></div></div></div></div>`);
      if (!__props.students || __props.students.length === 0) {
        _push(`<div class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-xl p-12" data-v-c7705de8><svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-c7705de8><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" data-v-c7705de8></path></svg><p class="mt-4 text-gray-500 text-lg" data-v-c7705de8>\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
      } else {
        _push(`<div class="grid grid-cols-1 gap-4" data-v-c7705de8><!--[-->`);
        ssrRenderList(filteredStudents.value, (student, index) => {
          _push(`<div data-v-c7705de8><div class="flex justify-center items-center font-sarabun" data-v-c7705de8><div${ssrRenderAttr("id", `card-${index}`)} class="student-card-container w-full aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300" data-v-c7705de8><div class="h-[20%] -ml-8 flex items-center relative" style="${ssrRenderStyle({ "background": "linear-gradient(135deg, transparent 45%, #4a90e2 0%)" })}" data-v-c7705de8><div class="w-[22%] aspect-square border-1 border-white flex items-center justify-center" data-v-c7705de8><img${ssrRenderAttr("src", _imports_1)} alt="School Logo" class="w-[56%] h-[56%] mt-10 object-cover rounded-full" data-v-c7705de8></div><div class="-ml-10 -mt-2" data-v-c7705de8><div class="text-6xl font-semibold md:font-bold text-gray-800" data-v-c7705de8> \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E23\u0E34\u0E22\u0E18\u0E23\u0E23\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34 </div><div class="text-[34px] mt-2 font-semibold text-gray-800" data-v-c7705de8> CHARIYATHAMSUKSA FOUNDATION SCHOOL </div><div class="text-3xl -mt-1.5 text-gray-800" data-v-c7705de8> 148 \u0E21.8 \u0E15.\u0E2A\u0E30\u0E01\u0E2D\u0E21 \u0E2D.\u0E08\u0E30\u0E19\u0E30 \u0E08.\u0E2A\u0E07\u0E02\u0E25\u0E32 90130 \u0E42\u0E17\u0E23.081-5412281 </div></div><div class="absolute -top-8 right-4 mt-[148px] ml-12 text-white bg-blue-700 px-4 pb-2 text-end rounded-md" data-v-c7705de8><div class="text-3xl -mt-1.5 font-semibold" data-v-c7705de8>\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</div><div class="text-2xl" data-v-c7705de8>STUDENT CARD</div></div></div><div class="flex p-[2%] gap-[2%] h-[80%]" data-v-c7705de8><div class="w-[30%] h-[80%] rounded-xl overflow-hidden flex-shrink-0 mt-4" data-v-c7705de8>`);
          if (student.profile_image) {
            _push(`<div class="w-full h-full relative" data-v-c7705de8><img${ssrRenderAttr("src", `/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`)} alt="Student Photo" class="w-full h-full object-fill rounded-xl" data-v-c7705de8></div>`);
          } else {
            _push(`<div class="w-full h-full flex items-center justify-center bg-gray-300 cursor-pointer" data-v-c7705de8>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "tabler:photo-plus",
              class: "w-10 h-10 text-gray-600/60"
            }, null, _parent));
            _push(`</div>`);
          }
          _push(`</div><div class="flex-1 relative" data-v-c7705de8><div class="" data-v-c7705de8><div class="flex items-center" data-v-c7705de8><div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight mt-2" data-v-c7705de8>\u0E0A\u0E37\u0E48\u0E2D</div><div class="text-[42px] font-bold text-gray-800 leading-tight mr-3 mt-1" data-v-c7705de8>:</div><div class="${ssrRenderClass([studentThaiPrefixName(index).txtSize, "font-bold text-gray-800 leading-tight -mt-1"])}" data-v-c7705de8>${ssrInterpolate(studentThaiPrefixName(index).prefix)}${ssrInterpolate(student.first_name_thai)} ${ssrInterpolate(student.last_name_thai)}</div></div><div class="flex items-center -mt-3" data-v-c7705de8><div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight" data-v-c7705de8>Name</div><div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4" data-v-c7705de8>:</div><div class="text-[36px] font-base text-gray-700 leading-tight" data-v-c7705de8>`);
          if (student.first_name_english) {
            _push(`<span data-v-c7705de8>${ssrInterpolate(studentPrefixName(index))}${ssrInterpolate(student.first_name_english)}</span>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div><div class="" data-v-c7705de8><div class="flex items-center" data-v-c7705de8><div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight" data-v-c7705de8>\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27</div><div class="text-[42px] font-bold text-gray-700 leading-tight mr-3" data-v-c7705de8>:</div><div class="text-[44px] font-bold text-gray-800 leading-tight" data-v-c7705de8>${ssrInterpolate(student.student_number)}</div></div><div class="flex items-center -mt-4" data-v-c7705de8><div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight" data-v-c7705de8>Student ID</div><div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4" data-v-c7705de8>:</div></div></div><div class="" data-v-c7705de8><div class="flex" data-v-c7705de8><div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight" data-v-c7705de8>\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</div><div class="text-[42px] font-bold text-gray-700 leading-tight mr-3" data-v-c7705de8>:</div><div class="text-[44px] font-bold text-gray-800 leading-tight" data-v-c7705de8>${ssrInterpolate(formattedIdNumber(student.national_id))}</div></div><div class="flex -mt-4" data-v-c7705de8><div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight" data-v-c7705de8>ID Card Number</div><div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4" data-v-c7705de8>:</div></div></div><div class="" data-v-c7705de8><div class="flex" data-v-c7705de8><div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight" data-v-c7705de8>\u0E23\u0E30\u0E14\u0E31\u0E1A</div><div class="text-[42px] font-bold text-gray-700 leading-tight mr-3" data-v-c7705de8>:</div><div class="text-[44px] font-bold text-gray-800 leading-tight" data-v-c7705de8>${ssrInterpolate(student.class_level < 4 ? "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E15\u0E49\u0E19" : "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E1B\u0E25\u0E32\u0E22")}</div></div><div class="flex -mt-3" data-v-c7705de8><div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight" data-v-c7705de8>Level</div><div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4" data-v-c7705de8>:</div><div class="text-[36px] font-base text-gray-700 leading-tight" data-v-c7705de8>${ssrInterpolate(student.class_level < 4 ? "Lower Secondary" : "Upper Secondary")}</div></div></div><div class="" data-v-c7705de8><div class="flex" data-v-c7705de8><div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight" data-v-c7705de8>\u0E27\u0E31\u0E19/\u0E40\u0E14\u0E37\u0E2D\u0E19/\u0E1B\u0E35 \u0E40\u0E01\u0E34\u0E14</div><div class="text-[42px] font-bold text-gray-700 leading-tight mr-3" data-v-c7705de8>:</div><div class="text-[44px] font-bold text-gray-800 leading-tight" data-v-c7705de8>${ssrInterpolate(new Date(student.birth_date).toLocaleDateString("th-TH", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
          }))}</div></div><div class="flex -mt-3" data-v-c7705de8><div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight" data-v-c7705de8>Date of Birth</div><div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4" data-v-c7705de8>:</div><div class="text-[36px] font-base text-gray-700 leading-tight" data-v-c7705de8>${ssrInterpolate(new Date(student.birth_date).toLocaleDateString("en-US", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
          }))}</div></div></div><div class="-mt-1" data-v-c7705de8><div class="flex" data-v-c7705de8><div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight" data-v-c7705de8>\u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38\u0E1A\u0E31\u0E15\u0E23</div><div class="text-[42px] font-bold text-gray-700 leading-tight mr-3" data-v-c7705de8>:</div><div class="text-[44px] font-bold text-gray-800 leading-tight" data-v-c7705de8>${ssrInterpolate(new Date(student.card_expiry_date).toLocaleDateString("th-TH", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
          }))}</div></div><div class="flex -mt-3" data-v-c7705de8><div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight" data-v-c7705de8>Expiry Date</div><div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4" data-v-c7705de8>:</div><div class="text-[36px] font-base text-gray-700 leading-tight" data-v-c7705de8>${ssrInterpolate(new Date(student.card_expiry_date).toLocaleDateString("en-US", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
          }))}</div></div></div></div></div><div class="absolute bottom-10 right-10 w-[192px]" data-v-c7705de8>`);
          _push(ssrRenderComponent(unref(QRCodeVue3), {
            value: `${student.student_number}`,
            cornersSquareOptions: { type: "extra-rounded", color: "#000" },
            dotsOptions: {
              type: "dots",
              color: "#000"
            },
            cornersDotOptions: { type: "square", color: "#000" }
          }, null, _parent));
          _push(`</div><div class="absolute bottom-0 left-0 w-full h-8 flex items-center justify-center rounded-b-2xl" style="${ssrRenderStyle({ "background": "linear-gradient(135deg, #4a90e2 72%, transparent 0%)" })}" data-v-c7705de8><span class="text-white text-[1vw] text-sm font-medium tracking-wider" data-v-c7705de8></span></div></div></div><div class="flex justify-center items-center w-full mt-2" data-v-c7705de8><div class="w-full text-end" data-v-c7705de8><button class="px-4 py-2 bg-blue-600 text-white rounded-lg" data-v-c7705de8> \u0E14\u0E32\u0E27\u0E19\u0E4C\u0E42\u0E2B\u0E25\u0E14\u0E1A\u0E31\u0E15\u0E23\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </button></div></div></div>`);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/Card/Admin/StudentCardByRoom.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentCardByRoom = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-c7705de8"]]);

export { StudentCardByRoom as default };
//# sourceMappingURL=StudentCardByRoom-DeujdpbK.mjs.map
