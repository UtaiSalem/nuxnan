import { ref, unref, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { u as useForm, H as Head, r as router } from './inertia-vue3-CWdJjaLG.mjs';
import { a as getCurrentDate } from './dateUtils-DQlkT5wi.mjs';
import StudentCard from './StudentCard-BOGCzgEZ.mjs';
import StudentsCard from './StudentsCard-CUu90lC-.mjs';
import _sfc_main$1 from './AcademicInfoCard-DeAtPXoT.mjs';
import _sfc_main$2 from './AddressCard-C5OO_9mi.mjs';
import _sfc_main$3 from './ContactCard-DNJ688tb.mjs';
import _sfc_main$4 from './HealthInfoCard-CRvifp0i.mjs';
import _sfc_main$5 from './GuardianCard-C6LWpJ3F.mjs';
import _sfc_main$6 from './HomeVisitCard-BFPeJSf0.mjs';
import './server.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import './StudentInfoGrid-VBS3PfCt.mjs';
import './useStudentRoutes-BWKsyL_y.mjs';
import './ImageGalleryModal-DpY9Yyol.mjs';

const _sfc_main = {
  __name: "Dashboard",
  __ssrInlineRender: true,
  props: {
    stats: Object,
    students: Object,
    classrooms: Array,
    zones: {
      type: Array,
      default: () => []
    },
    filters: Object
  },
  setup(__props) {
    var _a, _b;
    const props = __props;
    const searchQuery = ref(((_a = props.filters) == null ? void 0 : _a.search) || "");
    const selectedClassroom = ref(((_b = props.filters) == null ? void 0 : _b.classroom) || "");
    const showCreateVisitModal = ref(false);
    const selectedStudent = ref(null);
    const isLoading = ref(false);
    const homeVisits = ref([]);
    const visitForm = useForm({
      visit_date: "",
      visit_time: "",
      notes: "",
      zone_id: "",
      images: []
    });
    const editForm = useForm({
      title_prefix_th: "",
      first_name_th: "",
      last_name_th: "",
      nickname: "",
      citizen_id: "",
      class_level: "",
      class_section: "",
      academic_info: null,
      contacts: []
    });
    const searchStudents = () => {
      isLoading.value = true;
      router.get(route("homevisit.teacher.search.students"), {
        search: searchQuery.value,
        classroom: selectedClassroom.value
      }, {
        preserveState: true,
        onStart: () => {
          isLoading.value = true;
        },
        onSuccess: (page) => {
          var _a2;
          if (((_a2 = page.props.students.data) == null ? void 0 : _a2.length) === 1) {
            selectStudent(page.props.students.data[0]);
          }
          isLoading.value = false;
        },
        onError: () => {
          isLoading.value = false;
        },
        onFinish: () => {
          isLoading.value = false;
        }
      });
    };
    const selectStudent = (student) => {
      selectedStudent.value = student;
      editForm.title_prefix_th = student.title_prefix_th || "";
      editForm.first_name_th = student.first_name_th || "";
      editForm.last_name_th = student.last_name_th || "";
      editForm.nickname = student.nickname || "";
      editForm.citizen_id = student.citizen_id || "";
      editForm.class_level = student.class_level || "";
      editForm.class_section = student.class_section || "";
      editForm.academic_info = student.academic_info || {};
      editForm.contacts = student.contacts || [];
      if (student.home_visits) {
        homeVisits.value = student.home_visits;
      } else {
        homeVisits.value = [];
      }
    };
    const createHomeVisit = (student) => {
      selectedStudent.value = student;
      visitForm.visit_date = getCurrentDate();
      showCreateVisitModal.value = true;
    };
    const createNewHomeVisit = () => {
      if (!selectedStudent.value) return;
      const formData = new FormData();
      formData.append("visit_date", visitForm.visit_date);
      formData.append("visit_time", visitForm.visit_time);
      formData.append("observations", visitForm.observations || "");
      formData.append("notes", visitForm.notes || "");
      if (visitForm.zone_id) {
        formData.append("zone_id", visitForm.zone_id);
      }
      if (visitForm.participants && visitForm.participants.length > 0) {
        visitForm.participants.forEach((participant, index) => {
          formData.append(`participants[${index}][name]`, participant.name);
        });
      }
      if (visitForm.images) {
        visitForm.images.forEach((image, index) => {
          formData.append(`images[${index}]`, image.file);
        });
      }
      router.post(route("homevisit.teacher.create.home.visit", selectedStudent.value.id), formData, {
        forceFormData: true,
        onSuccess: () => {
          visitForm.reset();
          visitForm.images = [];
          visitForm.participants = [];
          searchStudents();
        }
      });
    };
    const handleImageUpload = async (event) => {
      const files = Array.from(event.target.files);
      for (const file of files) {
        const compressedFile = await compressImage(file);
        const reader = new FileReader();
        reader.onload = (e) => {
          visitForm.images.push({
            file: compressedFile,
            preview: e.target.result
          });
        };
        reader.readAsDataURL(compressedFile);
      }
    };
    const compressImage = (file, maxWidth = 1200, quality = 0.8) => {
      return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
          const img = new Image();
          img.onload = () => {
            const canvas = (void 0).createElement("canvas");
            let width = img.width;
            let height = img.height;
            if (width > maxWidth) {
              height = height * maxWidth / width;
              width = maxWidth;
            }
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, width, height);
            canvas.toBlob(
              (blob) => {
                const compressedFile = new File([blob], file.name, {
                  type: "image/jpeg",
                  lastModified: Date.now()
                });
                resolve(compressedFile);
              },
              "image/jpeg",
              quality
            );
          };
          img.src = e.target.result;
        };
        reader.readAsDataURL(file);
      });
    };
    const removeImage = (index) => {
      visitForm.images.splice(index, 1);
    };
    const handleStudentSave = (message) => {
      searchStudents();
    };
    const handleStudentUpdate = (data) => {
      if (selectedStudent.value) {
        Object.assign(selectedStudent.value, data);
      }
    };
    const handleAcademicSave = (message) => {
      searchStudents();
    };
    const handleAcademicUpdate = (data) => {
      if (selectedStudent.value) {
        selectedStudent.value.academic_info = data;
      }
    };
    const handleAddressSave = (message) => {
    };
    const handleAddressUpdate = (data) => {
      if (selectedStudent.value) {
        selectedStudent.value.addresses = data;
      }
    };
    const handleContactSave = (message) => {
    };
    const handleContactUpdate = (data) => {
      if (selectedStudent.value) {
        selectedStudent.value.contacts = data;
      }
    };
    const handleHealthSave = (message) => {
    };
    const handleHealthUpdate = (data) => {
      if (selectedStudent.value) {
        selectedStudent.value.health_info = data;
      }
    };
    const handleGuardianSave = (message) => {
    };
    const handleGuardianUpdate = (data) => {
      if (selectedStudent.value) {
        selectedStudent.value.guardians = data;
      }
    };
    function onVisitUpdated(updatedVisit) {
      const index = homeVisits.value.findIndex((visit) => visit.id === updatedVisit.id);
      if (index !== -1) {
        homeVisits.value[index] = { ...homeVisits.value[index], ...updatedVisit };
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      var _a2, _b2, _c;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "\u0E23\u0E30\u0E1A\u0E1A\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E04\u0E23\u0E39" }, null, _parent));
      _push(`<div class="min-h-screen bg-gray-50"><nav class="bg-white shadow-sm border-b"><div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8"><div class="flex justify-between items-center h-16 sm:hidden"><div class="flex items-center min-w-0 flex-1"><div class="flex-shrink-0"><span class="text-xl">\u{1F3E0}</span></div><div class="ml-2 min-w-0 flex-1"><h1 class="text-sm font-semibold text-gray-900 truncate"> \u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h1><p class="text-xs text-gray-500 truncate"> \u0E04\u0E23\u0E39 </p></div></div><div class="flex items-center ml-2"><button class="text-gray-500 hover:text-gray-700 p-2 rounded-md" title="\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A"><i class="fas fa-sign-out-alt text-lg"></i></button></div></div><div class="hidden sm:flex justify-between h-16"><div class="flex items-center"><div class="flex-shrink-0 flex items-center"><span class="text-2xl">\u{1F3E0}</span><h1 class="ml-3 text-xl font-semibold text-gray-900"> \u0E23\u0E30\u0E1A\u0E1A\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 - \u0E04\u0E23\u0E39 </h1></div></div><div class="flex items-center space-x-4"><span class="text-sm text-gray-600"> \u0E2A\u0E27\u0E31\u0E2A\u0E14\u0E35, \u0E04\u0E23\u0E39 </span><button class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium transition-colors"> \u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A </button></div></div></div></nav><div class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8"><div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6"><div class="px-4 py-4 sm:hidden"><h3 class="text-base font-semibold text-gray-900 flex items-center"><i class="fas fa-search mr-2 text-indigo-600"></i> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><p class="mt-1 text-xs text-gray-500"> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </p></div><div class="hidden sm:block px-4 py-5 sm:px-6"><h3 class="text-lg leading-6 font-medium text-gray-900"> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E14\u0E49\u0E27\u0E22\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E14\u0E39\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E41\u0E25\u0E30\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </p></div><div class="px-4 pb-4 pt-2 sm:p-6"><div class="space-y-4"><div class="block sm:hidden space-y-4"><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</label><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19..."${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-base disabled:bg-gray-100 disabled:cursor-not-allowed"></div><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="w-full inline-flex justify-center items-center py-3 px-4 border border-transparent shadow-sm text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(`<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
      } else {
        _push(`<i class="fas fa-search mr-2"></i>`);
      }
      _push(` ${ssrInterpolate(isLoading.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32..." : "\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19")}</button></div><div class="hidden sm:flex flex-col sm:flex-row gap-4"><div class="flex-1"><input${ssrRenderAttr("value", searchQuery.value)} type="text" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"></div><button${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">`);
      if (isLoading.value) {
        _push(`<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`);
      } else {
        _push(`<i class="fas fa-search mr-2"></i>`);
      }
      _push(` ${ssrInterpolate(isLoading.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32..." : "\u0E04\u0E49\u0E19\u0E2B\u0E32")}</button></div></div></div></div>`);
      if (selectedStudent.value) {
        _push(`<div class="space-y-6"><div class="flex items-center space-x-3"><button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"><i class="fas fa-arrow-left mr-2"></i> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E43\u0E2B\u0E21\u0E48 </button><div class="text-sm text-gray-500"> \u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E39\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E02\u0E2D\u0E07: <span class="font-semibold">${ssrInterpolate(selectedStudent.value.first_name_th)} ${ssrInterpolate(selectedStudent.value.last_name_th)}</span></div></div><div class="space-y-6">`);
        _push(ssrRenderComponent(unref(StudentsCard), {
          student: selectedStudent.value,
          "student-card": selectedStudent.value,
          context: "teacher",
          onSave: handleStudentSave,
          onUpdate: handleStudentUpdate
        }, null, _parent));
        _push(ssrRenderComponent(unref(_sfc_main$1), {
          student: selectedStudent.value,
          context: "teacher",
          onSave: handleAcademicSave,
          onUpdate: handleAcademicUpdate
        }, null, _parent));
        _push(ssrRenderComponent(unref(_sfc_main$2), {
          student: selectedStudent.value,
          context: "teacher",
          onSave: handleAddressSave,
          onUpdate: handleAddressUpdate
        }, null, _parent));
        _push(ssrRenderComponent(unref(_sfc_main$3), {
          student: selectedStudent.value,
          context: "teacher",
          onSave: handleContactSave,
          onUpdate: handleContactUpdate
        }, null, _parent));
        _push(ssrRenderComponent(unref(_sfc_main$4), {
          student: selectedStudent.value,
          context: "teacher",
          onSave: handleHealthSave,
          onUpdate: handleHealthUpdate
        }, null, _parent));
        _push(ssrRenderComponent(unref(_sfc_main$5), {
          student: selectedStudent.value,
          context: "teacher",
          onSave: handleGuardianSave,
          onUpdate: handleGuardianUpdate
        }, null, _parent));
        _push(`</div>`);
        _push(ssrRenderComponent(_sfc_main$6, {
          "home-visits": homeVisits.value,
          "visit-form": unref(visitForm),
          zones: __props.zones,
          "create-new-home-visit": createNewHomeVisit,
          "handle-image-upload": handleImageUpload,
          "remove-image": removeImage,
          onVisitUpdated
        }, null, _parent));
        _push(`</div>`);
      } else if (((_a2 = __props.students.data) == null ? void 0 : _a2.length) > 1 && !selectedStudent.value) {
        _push(`<div class="bg-white shadow overflow-hidden sm:rounded-md"><div class="px-4 py-3 sm:hidden border-b border-gray-200"><div class="flex items-center justify-between"><h3 class="text-base font-semibold text-gray-900 flex items-center"><i class="fas fa-users mr-2 text-green-600"></i> \u0E23\u0E32\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">${ssrInterpolate(__props.students.data.length)} \u0E04\u0E19 </span></div></div><div class="hidden sm:block px-4 py-5 sm:px-6"><h3 class="text-lg leading-6 font-medium text-gray-900"> \u0E23\u0E32\u0E22\u0E0A\u0E37\u0E48\u0E2D\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E1E\u0E1A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2B\u0E25\u0E32\u0E22\u0E04\u0E19 ${ssrInterpolate(__props.students.data.length)} \u0E04\u0E19 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E14\u0E39\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 </p></div><ul class="divide-y divide-gray-200"><!--[-->`);
        ssrRenderList(__props.students.data, (student) => {
          _push(ssrRenderComponent(StudentCard, {
            key: student.id,
            student,
            onViewStudent: selectStudent,
            onCreateHomeVisit: createHomeVisit
          }, null, _parent));
        });
        _push(`<!--]--></ul>`);
        if (__props.students.links) {
          _push(`<div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6"><div class="flex items-center justify-between sm:hidden"><div class="flex-1 flex justify-between">`);
          if (__props.students.prev_page_url) {
            _push(`<a${ssrRenderAttr("href", __props.students.prev_page_url)} class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors"><i class="fas fa-chevron-left mr-2"></i> \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </a>`);
          } else {
            _push(`<div class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400"><i class="fas fa-chevron-left mr-2"></i> \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </div>`);
          }
          _push(`<span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700"> \u0E2B\u0E19\u0E49\u0E32 ${ssrInterpolate(__props.students.current_page)} \u0E08\u0E32\u0E01 ${ssrInterpolate(__props.students.last_page)}</span>`);
          if (__props.students.next_page_url) {
            _push(`<a${ssrRenderAttr("href", __props.students.next_page_url)} class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors"> \u0E16\u0E31\u0E14\u0E44\u0E1B <i class="fas fa-chevron-right ml-2"></i></a>`);
          } else {
            _push(`<div class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400"> \u0E16\u0E31\u0E14\u0E44\u0E1B <i class="fas fa-chevron-right ml-2"></i></div>`);
          }
          _push(`</div></div><div class="hidden sm:flex items-center justify-between"><div class="flex items-center"><p class="text-sm text-gray-700"> \u0E41\u0E2A\u0E14\u0E07 <span class="font-medium">${ssrInterpolate(__props.students.from)}</span> \u0E16\u0E36\u0E07 <span class="font-medium">${ssrInterpolate(__props.students.to)}</span> \u0E08\u0E32\u0E01 <span class="font-medium">${ssrInterpolate(__props.students.total)}</span> \u0E1C\u0E25\u0E25\u0E31\u0E1E\u0E18\u0E4C </p></div><div class="flex items-center space-x-2">`);
          if (__props.students.prev_page_url) {
            _push(`<a${ssrRenderAttr("href", __props.students.prev_page_url)} class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"><i class="fas fa-chevron-left mr-1"></i> \u0E01\u0E48\u0E2D\u0E19\u0E2B\u0E19\u0E49\u0E32 </a>`);
          } else {
            _push(`<!---->`);
          }
          if (__props.students.next_page_url) {
            _push(`<a${ssrRenderAttr("href", __props.students.next_page_url)} class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"> \u0E16\u0E31\u0E14\u0E44\u0E1B <i class="fas fa-chevron-right ml-1"></i></a>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else if (searchQuery.value || selectedClassroom.value) {
        _push(`<div class="bg-white rounded-lg shadow-sm border border-gray-200 text-center py-8 sm:py-12 mx-4 sm:mx-0"><i class="fas fa-search text-3xl sm:text-4xl text-gray-400 mb-3 sm:mb-4"></i><h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">\u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h3><p class="text-sm sm:text-base text-gray-500 px-4">\u0E25\u0E2D\u0E07\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E14\u0E49\u0E27\u0E22\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2D\u0E37\u0E48\u0E19\u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E15\u0E31\u0E27\u0E01\u0E23\u0E2D\u0E07\u0E0A\u0E31\u0E49\u0E19\u0E40\u0E23\u0E35\u0E22\u0E19</p></div>`);
      } else {
        _push(`<div class="bg-white rounded-lg shadow-sm border border-gray-200 text-center py-8 sm:py-12 mx-4 sm:mx-0"><i class="fas fa-users text-3xl sm:text-4xl text-gray-400 mb-3 sm:mb-4"></i><h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</h3><p class="text-sm sm:text-base text-gray-500 px-4">\u0E01\u0E23\u0E2D\u0E01\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E04\u0E49\u0E19\u0E2B\u0E32</p></div>`);
      }
      _push(`</div>`);
      if (showCreateVisitModal.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-y-auto"><div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"><div class="fixed inset-0 transition-opacity"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div><div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"><div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4"><h3 class="text-lg leading-6 font-medium text-gray-900 mb-4"> \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h3><div class="mb-4 p-3 bg-gray-50 rounded-lg"><div class="text-sm space-y-1"><div class="flex"><span class="text-gray-500 font-medium w-16">\u0E0A\u0E37\u0E48\u0E2D:</span><span class="font-semibold text-gray-900">${ssrInterpolate((_b2 = selectedStudent.value) == null ? void 0 : _b2.first_name_th)}</span></div><div class="flex"><span class="text-gray-500 font-medium w-16">\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25:</span><span class="font-semibold text-gray-900">${ssrInterpolate((_c = selectedStudent.value) == null ? void 0 : _c.last_name_th)}</span></div></div></div><form class="space-y-4"><div><label class="block text-sm font-medium text-gray-700"> \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 </label><input${ssrRenderAttr("value", unref(visitForm).visit_date)} type="date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></div><div><label class="block text-sm font-medium text-gray-700"> \u0E42\u0E0B\u0E19 <span class="text-gray-500 text-xs">(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</span></label><select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(unref(visitForm).zone_id) ? ssrLooseContain(unref(visitForm).zone_id, "") : ssrLooseEqual(unref(visitForm).zone_id, "")) ? " selected" : ""}>\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E42\u0E0B\u0E19</option><!--[-->`);
        ssrRenderList(__props.zones, (zone) => {
          _push(`<option${ssrRenderAttr("value", zone.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(visitForm).zone_id) ? ssrLooseContain(unref(visitForm).zone_id, zone.id) : ssrLooseEqual(unref(visitForm).zone_id, zone.id)) ? " selected" : ""}>${ssrInterpolate(zone.zone_name)}</option>`);
        });
        _push(`<!--]--></select></div><div><label class="block text-sm font-medium text-gray-700"> \u0E2B\u0E31\u0E27\u0E02\u0E49\u0E2D\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 </label><input${ssrRenderAttr("value", unref(visitForm).visit_purpose)} type="text" required placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E15\u0E34\u0E14\u0E15\u0E32\u0E21\u0E1C\u0E25\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19, \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></div><div><label class="block text-sm font-medium text-gray-700"> \u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14 </label><textarea rows="3" placeholder="\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">${ssrInterpolate(unref(visitForm).visit_notes)}</textarea></div><div class="flex justify-end space-x-3 pt-4"><button type="button" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="submit"${ssrIncludeBooleanAttr(unref(visitForm).processing) ? " disabled" : ""} class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50">`);
        if (unref(visitForm).processing) {
          _push(`<span>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01...</span>`);
        } else {
          _push(`<span>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</span>`);
        }
        _push(`</button></div></form></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Teacher/Dashboard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Dashboard-CnB-D6ip.mjs.map
