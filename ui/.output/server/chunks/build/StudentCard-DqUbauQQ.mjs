import { ref, reactive, computed, mergeProps, unref, withCtx, createTextVNode, createVNode, withModifiers, withDirectives, vModelText, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderStyle, ssrRenderAttr, ssrRenderComponent, ssrInterpolate, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { _ as _imports_1 } from './virtual_public-Zc294sLl.mjs';
import QRCodeVue3 from 'qrcode-vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
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
  __name: "StudentCard",
  __ssrInlineRender: true,
  props: {
    studentInfo: {
      type: Object,
      required: true
    }
  },
  emits: ["update", "upload-image"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const isEditStudentPhoto = ref(false);
    const isDeletingStudentPhoto = ref(false);
    const isSaving = ref(false);
    const isEditModalOpen = ref(false);
    const editForm = reactive({
      id: props.studentInfo.id,
      student_number: props.studentInfo.student_number,
      title_name: props.studentInfo.title_name || "",
      first_name_thai: props.studentInfo.first_name_thai || "",
      last_name_thai: props.studentInfo.last_name_thai || "",
      full_name_thai: props.studentInfo.full_name_thai || ((props.studentInfo.title_name || "") + " " + (props.studentInfo.first_name_thai || "") + " " + (props.studentInfo.last_name_thai || "")).trim(),
      first_name_english: props.studentInfo.first_name_english || "",
      level: props.studentInfo.class_level < 4 ? "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E15\u0E49\u0E19" : "\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E15\u0E2D\u0E19\u0E1B\u0E25\u0E32\u0E22",
      birth_date: props.studentInfo.birth_date,
      national_id: props.studentInfo.national_id
    });
    const formattedIdNumber = computed(() => {
      if (!editForm.national_id) return "";
      const idString = String(editForm.national_id);
      if (idString.length !== 13) return idString;
      const id = idString.replace(/\D/g, "");
      return id.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, "$1-$2-$3-$4-$5");
    });
    const displayFullNameThai = computed(() => {
      var _a, _b, _c;
      if (editForm.full_name_thai && editForm.full_name_thai.trim()) {
        return editForm.full_name_thai.trim();
      }
      const parts = [
        (_a = editForm.title_name) == null ? void 0 : _a.trim(),
        (_b = editForm.first_name_thai) == null ? void 0 : _b.trim(),
        (_c = editForm.last_name_thai) == null ? void 0 : _c.trim()
      ].filter((part) => part && part !== "");
      return parts.join(" ");
    });
    const fileInput = ref(null);
    const previewImage = ref(null);
    const tempPhoto = ref(props.studentInfo.profile_image || null);
    const studentImageUrl = computed(() => {
      if (previewImage.value) {
        return previewImage.value;
      }
      if (tempPhoto.value) {
        return `/storage/images/students/${props.studentInfo.class_level}/${props.studentInfo.class_section}/${tempPhoto.value}`;
      }
      return null;
    });
    const triggerFileInput = () => {
      fileInput.value.click();
    };
    const handleSubmit = async () => {
      try {
        isSaving.value = true;
        const payload = {
          national_id: editForm.national_id,
          student_number: editForm.student_number,
          title_name: editForm.title_name,
          first_name_thai: editForm.first_name_thai,
          last_name_thai: editForm.last_name_thai,
          first_name_english: editForm.first_name_english,
          birth_date: editForm.birth_date
          // เพิ่มฟิลด์นี้
        };
        const response = await axios.put(`/student-card/update/${editForm.id}`, payload, {
          headers: {
            "Content-Type": "application/json"
          }
        });
        if (response.data.success) {
          Swal.fire({
            title: "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
            icon: "success",
            confirmButtonText: "\u0E15\u0E01\u0E25\u0E07"
          });
          isEditModalOpen.value = false;
        }
      } catch (error) {
        Swal.fire({
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E44\u0E14\u0E49",
          icon: "error",
          confirmButtonText: "\u0E15\u0E01\u0E25\u0E07"
        });
      } finally {
        isSaving.value = false;
      }
    };
    ref(null);
    const studentPrefixName = (prefix) => {
      if (!prefix) return "";
      if (prefix == "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07" || prefix == "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27") {
        return "Ms.";
      } else if (prefix == "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22" || prefix == "\u0E19\u0E32\u0E22") {
        return "Mr.";
      } else {
        return "";
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex justify-center items-center font-sarabun" }, _attrs))} data-v-f3147420><div class="student-card-container w-full max-w-[640px] aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300" data-v-f3147420><div class="h-[20%] bg-gradient-to-br from-gray-50 to-gray-200 relative" style="${ssrRenderStyle({ "background": "linear-gradient(135deg, transparent 40%, #4a90e2 0%)" })}" data-v-f3147420><div class="absolute -left-2 md:-left-3 -top-[16px] sm:-top-[28px] md:-top-[22px] w-[22%] aspect-square rounded-full border-1 border-white flex items-center justify-center" data-v-f3147420><img${ssrRenderAttr("src", _imports_1)} alt="School Logo" class="w-[56%] h-[56%] object-cover rounded-full" data-v-f3147420></div><div class="absolute left-[16%] top-[10%] sm:top-2" data-v-f3147420><div class="text-[3.8vw] md:text-[28px] font-semibold md:font-bold text-gray-800" data-v-f3147420> \u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E23\u0E34\u0E22\u0E18\u0E23\u0E23\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E21\u0E39\u0E25\u0E19\u0E34\u0E18\u0E34 </div><div class="text-[2.4vw] sm:text-[2.5vw] md:text-[16px] -mt-1 sm:-mt-2.5 md:-mt-2 font-base text-gray-800 tracking-wider" data-v-f3147420> CHARIYATHAMSUKSA FOUNDATION SCHOOL </div><div class="text-[2.4vw] md:text-sm -mt-0.5 sm:-mt-2 md:-mt-1 text-gray-800" data-v-f3147420> 148 \u0E21.8 \u0E15.\u0E2A\u0E30\u0E01\u0E2D\u0E21 \u0E2D.\u0E08\u0E30\u0E19\u0E30 \u0E08.\u0E2A\u0E07\u0E02\u0E25\u0E32 90130 \u0E42\u0E17\u0E23.081-5412281 </div></div><div class="absolute z-50 top-0 right-2 text-white bg-gray-200/60 p-2 text-center rounded-full shadow-md cursor-pointer" data-v-f3147420>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "dashicons:edit",
        width: "20",
        height: "20"
      }, null, _parent));
      _push(`</div><div class="absolute z-10 top-6 md:top-[52px] right-2 text-white bg-blue-700 px-2 py-1 text-end rounded-md" data-v-f3147420><div class="text-[1.8vw] sm:text-[14px] font-semibold" data-v-f3147420>\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</div><div class="text-[1.4vw] sm:text-[10px] opacity-90" data-v-f3147420>STUDENT CARD</div></div></div><div class="flex p-[3%] gap-[3%] h-[80%]" data-v-f3147420><div class="w-[30%] h-[75%] rounded-xl overflow-hidden flex-shrink-0" data-v-f3147420><input type="file" accept="image/*" class="hidden" data-v-f3147420>`);
      if (previewImage.value || tempPhoto.value) {
        _push(`<div class="w-full h-full relative" data-v-f3147420><img${ssrRenderAttr("src", studentImageUrl.value)} alt="Student Photo" class="w-full h-full object-fill" data-v-f3147420><div class="absolute bottom-2 right-2 bg-white p-1 rounded-full shadow-md cursor-pointer" data-v-f3147420>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:pencil-solid",
          class: "w-5 h-5 text-gray-600"
        }, null, _parent));
        _push(`</div><button class="absolute bottom-2 right-2 bg-white p-1 rounded-full shadow-md cursor-pointer focus:outline-none focus:ring" aria-label="\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E23\u0E39\u0E1B" data-v-f3147420>`);
        if (isEditStudentPhoto.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:bubble-loading",
            class: "w-5 h-5 text-gray-600"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:pencil-solid",
            class: "w-5 h-5 text-gray-600"
          }, null, _parent));
        }
        _push(`</button><button class="absolute bottom-2 left-2 bg-red-500 p-1 rounded-full shadow-md cursor-pointer focus:outline-none focus:ring" aria-label="\u0E25\u0E1A\u0E23\u0E39\u0E1B" data-v-f3147420>`);
        if (isDeletingStudentPhoto.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:bubble-loading",
            class: "w-5 h-5 text-white"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        if (!isDeletingStudentPhoto.value && studentImageUrl.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:trash-solid",
            class: "w-5 h-5 text-white"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</button></div>`);
      } else {
        _push(`<div class="w-full h-full flex items-center justify-center bg-gray-300 cursor-pointer" data-v-f3147420>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "tabler:photo-plus",
          class: "w-10 h-10 text-gray-600/60",
          onClick: triggerFileInput
        }, null, _parent));
        _push(`</div>`);
      }
      _push(`</div><div class="flex-1 pt-0.5 relative" data-v-f3147420><div class="flex items-baseline" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-md md:text-lg font-medium text-gray-700" data-v-f3147420>\u0E0A\u0E37\u0E48\u0E2D</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700" data-v-f3147420>:</span><span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800" data-v-f3147420>${ssrInterpolate(displayFullNameThai.value)}</span></div><div class="flex items-baseline -mt-1" data-v-f3147420><span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600" data-v-f3147420>Name</span></div><div class="flex items-baseline -mt-3 sm:-mt-4 md:-mt-5" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700" data-v-f3147420></span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent" data-v-f3147420>:</span><span class="flex-1 text-[1.8vw] sm:text-sm font-normal text-gray-800" data-v-f3147420>${ssrInterpolate(editForm.first_name_english ? studentPrefixName(editForm.title_name) : "")} ${ssrInterpolate(editForm.first_name_english)}</span></div><div class="flex items-baseline mt-0.5" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700" data-v-f3147420>\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700" data-v-f3147420>:</span><span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800" data-v-f3147420>${ssrInterpolate(editForm.student_number)}</span></div><div class="flex items-baseline -mt-1" data-v-f3147420><span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600" data-v-f3147420>Student ID</span></div><div class="flex items-baseline mt-0.5" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-sm font-medium text-gray-700" data-v-f3147420>\u0E40\u0E25\u0E02\u0E1A\u0E31\u0E15\u0E23\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700" data-v-f3147420>:</span><span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800" data-v-f3147420>${ssrInterpolate(formattedIdNumber.value)}</span></div><div class="flex items-baseline -mt-1" data-v-f3147420><span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600" data-v-f3147420>ID Card No.</span></div><div class="flex items-baseline mt-0.5" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700" data-v-f3147420> \u0E23\u0E30\u0E14\u0E31\u0E1A </span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700" data-v-f3147420>:</span><span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800" data-v-f3147420>${ssrInterpolate(editForm.level)}</span></div><div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5" data-v-f3147420><span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600" data-v-f3147420>Level</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent" data-v-f3147420>:</span><span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800" data-v-f3147420>${ssrInterpolate(__props.studentInfo.class_level < 4 ? "LOWER SECONDARY" : "UPPER SECONDARY")}</span></div><div class="flex items-baseline mt-0.5" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700" data-v-f3147420>\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700" data-v-f3147420>:</span><span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800" data-v-f3147420>${ssrInterpolate(new Date(editForm.birth_date).toLocaleDateString("th-TH", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      }))}</span></div><div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5" data-v-f3147420><span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600" data-v-f3147420>Date of Birth</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent" data-v-f3147420>:</span><span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800" data-v-f3147420>${ssrInterpolate(new Date(editForm.birth_date).toLocaleDateString("en-US", {
        month: "2-digit",
        day: "2-digit",
        year: "numeric"
      }))}</span></div><div class="flex items-baseline mt-0.5" data-v-f3147420><span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700" data-v-f3147420>\u0E27\u0E31\u0E19\u0E2B\u0E21\u0E14\u0E2D\u0E32\u0E22\u0E38</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700" data-v-f3147420>:</span><span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800" data-v-f3147420>${ssrInterpolate(props.studentInfo.card_expiry_date ? new Date(props.studentInfo.card_expiry_date).toLocaleDateString("th-TH", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      }) : "-")}</span></div><div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5" data-v-f3147420><span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600" data-v-f3147420>Expiry Date</span><span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent" data-v-f3147420>:</span><span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800" data-v-f3147420>${ssrInterpolate(props.studentInfo.card_expiry_date ? new Date(props.studentInfo.card_expiry_date).toLocaleDateString("en-US", {
        month: "2-digit",
        day: "2-digit",
        year: "numeric"
      }) : "-")}</span></div></div><div class="absolute bottom-[5%] right-[3%] w-[15%] aspect-square" data-v-f3147420><div class="w-full h-full bg-white flex items-center justify-center rounded-lg shadow-md" data-v-f3147420>`);
      _push(ssrRenderComponent(unref(QRCodeVue3), {
        value: `${props.studentInfo.student_number}`,
        cornersSquareOptions: { type: "extra-rounded", color: "#000" },
        dotsOptions: {
          type: "dots",
          color: "#000"
        },
        cornersDotOptions: { type: "square", color: "#000" }
      }, null, _parent));
      _push(`</div></div><div class="absolute bottom-0 left-0 w-full h-[2.5%] flex items-center justify-center rounded-b-2xl" style="${ssrRenderStyle({ "background": "linear-gradient(135deg, #4a90e2 72%, transparent 0%)" })}" data-v-f3147420><span class="text-white text-[1vw] text-sm font-medium tracking-wider" data-v-f3147420></span></div></div>`);
      _push(ssrRenderComponent(unref(Dialog), {
        as: "div",
        onClose: ($event) => isEditModalOpen.value = false,
        open: isEditModalOpen.value,
        class: "relative z-50"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="fixed inset-0 bg-black/30" aria-hidden="true" data-v-f3147420${_scopeId}></div><div class="fixed inset-0 flex items-center justify-center p-4" data-v-f3147420${_scopeId}>`);
            _push2(ssrRenderComponent(unref(DialogPanel), { class: "w-full max-w-md bg-white rounded-lg p-6 shadow-xl" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(unref(DialogTitle), { class: "text-lg font-medium text-gray-900 mb-4" }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19`);
                      } else {
                        return [
                          createTextVNode("\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                  _push3(`<form data-v-f3147420${_scopeId2}><div class="mb-4" data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700 mb-1" data-v-f3147420${_scopeId2}>\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</label><input type="text"${ssrRenderAttr("value", editForm.student_number)} class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" data-v-f3147420${_scopeId2}></div><div class="mb-4" data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700" data-v-f3147420${_scopeId2}>\u0E04\u0E33\u0E19\u0E33\u0E2B\u0E19\u0E49\u0E32\u0E0A\u0E37\u0E48\u0E2D</label><input type="text"${ssrRenderAttr("value", editForm.title_name)} class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" data-v-f3147420${_scopeId2}></div><div data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700" data-v-f3147420${_scopeId2}>\u0E0A\u0E37\u0E48\u0E2D</label><input type="text"${ssrRenderAttr("value", editForm.first_name_thai)} class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" data-v-f3147420${_scopeId2}></div><div class="mb-4" data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700" data-v-f3147420${_scopeId2}>\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25</label><input type="text"${ssrRenderAttr("value", editForm.last_name_thai)} class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" data-v-f3147420${_scopeId2}></div><div class="mb-4" data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700 mb-1" data-v-f3147420${_scopeId2}>\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25 (\u0E2D\u0E31\u0E07\u0E01\u0E24\u0E29)</label><input type="text"${ssrRenderAttr("value", editForm.first_name_english)} class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" data-v-f3147420${_scopeId2}></div><div class="mb-4" data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700 mb-1" data-v-f3147420${_scopeId2}>\u0E40\u0E25\u0E02\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</label><input type="text"${ssrRenderAttr("value", editForm.national_id)} class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" data-v-f3147420${_scopeId2}></div><div class="mb-6" data-v-f3147420${_scopeId2}><label class="block text-sm font-medium text-gray-700 mb-1" data-v-f3147420${_scopeId2}>\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</label><input type="date"${ssrRenderAttr("value", editForm.birth_date)} class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" data-v-f3147420${_scopeId2}></div><div class="flex justify-end gap-2" data-v-f3147420${_scopeId2}><button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50" data-v-f3147420${_scopeId2}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="submit"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50" data-v-f3147420${_scopeId2}>${ssrInterpolate(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01")}</button></div></form>`);
                } else {
                  return [
                    createVNode(unref(DialogTitle), { class: "text-lg font-medium text-gray-900 mb-4" }, {
                      default: withCtx(() => [
                        createTextVNode("\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")
                      ]),
                      _: 1
                    }),
                    createVNode("form", {
                      onSubmit: withModifiers(handleSubmit, ["prevent"])
                    }, [
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.student_number = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.student_number]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700" }, "\u0E04\u0E33\u0E19\u0E33\u0E2B\u0E19\u0E49\u0E32\u0E0A\u0E37\u0E48\u0E2D"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.title_name = $event,
                          class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.title_name]
                        ])
                      ]),
                      createVNode("div", null, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700" }, "\u0E0A\u0E37\u0E48\u0E2D"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.first_name_thai = $event,
                          class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.first_name_thai]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700" }, "\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.last_name_thai = $event,
                          class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.last_name_thai]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25 (\u0E2D\u0E31\u0E07\u0E01\u0E24\u0E29)"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.first_name_english = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.first_name_english]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E40\u0E25\u0E02\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.national_id = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.national_id]
                        ])
                      ]),
                      createVNode("div", { class: "mb-6" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14"),
                        withDirectives(createVNode("input", {
                          type: "date",
                          "onUpdate:modelValue": ($event) => editForm.birth_date = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.birth_date]
                        ])
                      ]),
                      createVNode("div", { class: "flex justify-end gap-2" }, [
                        createVNode("button", {
                          type: "button",
                          onClick: ($event) => isEditModalOpen.value = false,
                          class: "px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
                        createVNode("button", {
                          type: "submit",
                          disabled: isSaving.value,
                          class: "px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        }, toDisplayString(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01"), 9, ["disabled"])
                      ])
                    ], 32)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", {
                class: "fixed inset-0 bg-black/30",
                "aria-hidden": "true"
              }),
              createVNode("div", { class: "fixed inset-0 flex items-center justify-center p-4" }, [
                createVNode(unref(DialogPanel), { class: "w-full max-w-md bg-white rounded-lg p-6 shadow-xl" }, {
                  default: withCtx(() => [
                    createVNode(unref(DialogTitle), { class: "text-lg font-medium text-gray-900 mb-4" }, {
                      default: withCtx(() => [
                        createTextVNode("\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")
                      ]),
                      _: 1
                    }),
                    createVNode("form", {
                      onSubmit: withModifiers(handleSubmit, ["prevent"])
                    }, [
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.student_number = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.student_number]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700" }, "\u0E04\u0E33\u0E19\u0E33\u0E2B\u0E19\u0E49\u0E32\u0E0A\u0E37\u0E48\u0E2D"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.title_name = $event,
                          class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.title_name]
                        ])
                      ]),
                      createVNode("div", null, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700" }, "\u0E0A\u0E37\u0E48\u0E2D"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.first_name_thai = $event,
                          class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.first_name_thai]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700" }, "\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.last_name_thai = $event,
                          class: "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.last_name_thai]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25 (\u0E2D\u0E31\u0E07\u0E01\u0E24\u0E29)"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.first_name_english = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.first_name_english]
                        ])
                      ]),
                      createVNode("div", { class: "mb-4" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E40\u0E25\u0E02\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19"),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => editForm.national_id = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.national_id]
                        ])
                      ]),
                      createVNode("div", { class: "mb-6" }, [
                        createVNode("label", { class: "block text-sm font-medium text-gray-700 mb-1" }, "\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14"),
                        withDirectives(createVNode("input", {
                          type: "date",
                          "onUpdate:modelValue": ($event) => editForm.birth_date = $event,
                          class: "w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, editForm.birth_date]
                        ])
                      ]),
                      createVNode("div", { class: "flex justify-end gap-2" }, [
                        createVNode("button", {
                          type: "button",
                          onClick: ($event) => isEditModalOpen.value = false,
                          class: "px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
                        createVNode("button", {
                          type: "submit",
                          disabled: isSaving.value,
                          class: "px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        }, toDisplayString(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01"), 9, ["disabled"])
                      ])
                    ], 32)
                  ]),
                  _: 1
                })
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/Card/StudentCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-f3147420"]]);

export { StudentCard as default };
//# sourceMappingURL=StudentCard-DqUbauQQ.mjs.map
