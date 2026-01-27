import { reactive, computed, ref, unref, useSSRContext } from 'vue';
import { ssrRenderClass, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { d as formatDateForInput, c as formatDateThai, e as calculateAge } from './dateUtils-DQlkT5wi.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = {
  __name: "StudentsCard",
  __ssrInlineRender: true,
  props: {
    student: {
      type: Object,
      required: true
    },
    studentCard: {
      type: Object,
      default: () => ({})
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    context: {
      type: String,
      default: "student"
      // 'student' or 'teacher'
    }
  },
  emits: ["save", "update"],
  setup(__props, { emit: __emit }) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m, _n, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x, _y, _z, _A, _B, _C, _D;
    const props = __props;
    const form = reactive({
      citizen_id: ((_a = props.student) == null ? void 0 : _a.citizen_id) || ((_b = props.studentCard) == null ? void 0 : _b.national_id) || "",
      student_id: ((_c = props.student) == null ? void 0 : _c.student_id) || ((_d = props.studentCard) == null ? void 0 : _d.student_number) || "",
      title_prefix_th: ((_e = props.student) == null ? void 0 : _e.title_prefix_th) || ((_f = props.studentCard) == null ? void 0 : _f.title_name) || "",
      first_name_th: ((_g = props.student) == null ? void 0 : _g.first_name_th) || ((_h = props.studentCard) == null ? void 0 : _h.first_name_thai) || "",
      last_name_th: ((_i = props.student) == null ? void 0 : _i.last_name_th) || ((_j = props.studentCard) == null ? void 0 : _j.last_name_thai) || "",
      middle_name_th: ((_k = props.student) == null ? void 0 : _k.middle_name_th) || "",
      title_prefix_en: ((_l = props.student) == null ? void 0 : _l.title_prefix_en) || "",
      first_name_en: ((_m = props.student) == null ? void 0 : _m.first_name_en) || "",
      last_name_en: ((_n = props.student) == null ? void 0 : _n.last_name_en) || "",
      middle_name_en: ((_o = props.student) == null ? void 0 : _o.middle_name_en) || "",
      nickname: ((_p = props.student) == null ? void 0 : _p.nickname) || "",
      gender: ((_q = props.student) == null ? void 0 : _q.gender) !== void 0 && ((_r = props.student) == null ? void 0 : _r.gender) !== null ? props.student.gender : null,
      // 0, 1, or null
      date_of_birth: formatDateForInput(((_s = props.student) == null ? void 0 : _s.date_of_birth) || ((_t = props.studentCard) == null ? void 0 : _t.birth_date)) || "",
      nationality: ((_u = props.student) == null ? void 0 : _u.nationality) || "\u0E44\u0E17\u0E22",
      religion: ((_v = props.student) == null ? void 0 : _v.religion) || "",
      profile_image: ((_w = props.student) == null ? void 0 : _w.profile_image) || ((_x = props.studentCard) == null ? void 0 : _x.profile_image) || "",
      status: ((_y = props.student) == null ? void 0 : _y.status) || "active",
      enrollment_date: formatDateForInput((_z = props.student) == null ? void 0 : _z.enrollment_date) || "",
      class_level: ((_A = props.student) == null ? void 0 : _A.class_level) || ((_B = props.studentCard) == null ? void 0 : _B.class_level) || "",
      class_section: ((_C = props.student) == null ? void 0 : _C.class_section) || ((_D = props.studentCard) == null ? void 0 : _D.class_section) || ""
    });
    computed(() => {
      if (form.gender === null || form.gender === void 0) return "";
      const genderMap = {
        0: "\u0E2B\u0E0D\u0E34\u0E07",
        1: "\u0E0A\u0E32\u0E22"
      };
      return genderMap[form.gender] || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
    });
    const classDisplayText = computed(() => {
      if (!form.class_level || !form.class_section) return "";
      return `\u0E21.${form.class_level}/${form.class_section}`;
    });
    const isSaving = ref(false);
    const saveStatus = ref(null);
    const studentPhoto = ref(null);
    ref(null);
    const showPhotoModal = ref(false);
    computed(() => {
      var _a2, _b2, _c2, _d2, _e2, _f2;
      if (form.profile_image) {
        const possiblePaths = [];
        if ((_a2 = props.student) == null ? void 0 : _a2.profile_image) {
          possiblePaths.push(`/storage/images/students/${(_b2 = props.student) == null ? void 0 : _b2.class_level}/${(_c2 = props.student) == null ? void 0 : _c2.class_section}/${props.student.profile_image}`);
        }
        if (((_d2 = props.studentCard) == null ? void 0 : _d2.profile_image) && ((_e2 = props.studentCard) == null ? void 0 : _e2.class_level) && ((_f2 = props.studentCard) == null ? void 0 : _f2.class_section)) {
          possiblePaths.push(`/storage/images/students/${props.studentCard.class_level}/${props.studentCard.class_section}/${props.studentCard.profile_image}`);
        }
        return possiblePaths;
      }
      return null;
    });
    const formatCitizenId = (citizenId) => {
      if (!citizenId) return "-";
      const digits = citizenId.replace(/\D/g, "");
      if (digits.length !== 13) return citizenId;
      return `${digits.substring(0, 1)}-${digits.substring(1, 5)}-${digits.substring(5, 10)}-${digits.substring(10, 12)}-${digits.substring(12, 13)}`;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[--><div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-2xl animate-fade-in" data-v-376523ff><div class="bg-gradient-to-br from-emerald-500 via-green-500 to-teal-600 px-6 py-5 relative overflow-hidden" data-v-376523ff><div class="absolute inset-0 opacity-10" data-v-376523ff><div class="absolute -top-4 -right-4 w-24 h-24 bg-white rounded-full animate-blob" data-v-376523ff></div><div class="absolute -bottom-4 -left-4 w-32 h-32 bg-white rounded-full animate-blob animation-delay-2000" data-v-376523ff></div></div><div class="relative z-10 flex items-center justify-between" data-v-376523ff><div class="flex items-center" data-v-376523ff><div class="w-12 h-12 bg-white bg-opacity-20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 animate-pulse-soft" data-v-376523ff><svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-376523ff><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" data-v-376523ff></path></svg></div><div class="ml-4" data-v-376523ff><h3 class="text-xl font-bold text-white" data-v-376523ff> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </h3><p class="text-emerald-100 text-sm mt-0.5" data-v-376523ff>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27\u0E41\u0E25\u0E30\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34</p></div></div><div class="hidden md:flex items-center space-x-2" data-v-376523ff><span class="px-3 py-1.5 bg-white bg-opacity-20 backdrop-blur-sm rounded-lg text-white text-sm font-medium" data-v-376523ff><i class="fas fa-user-graduate mr-1.5" data-v-376523ff></i> \u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 </span></div></div></div><form class="p-6 space-y-8" data-v-376523ff><div class="bg-gradient-to-br from-gray-50 to-emerald-50/30 rounded-2xl p-6 border-2 border-emerald-100 transition-all duration-300 hover:border-emerald-300" data-v-376523ff><div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6" data-v-376523ff><div class="flex-shrink-0 relative group" data-v-376523ff><div class="${ssrRenderClass([{ "cursor-pointer": studentPhoto.value }, "w-32 h-40 md:w-36 md:h-48 bg-gradient-to-br from-gray-200 to-gray-300 rounded-2xl overflow-hidden border-4 border-white shadow-2xl transition-all duration-300 group-hover:shadow-emerald-500/50 group-hover:scale-105"])}" data-v-376523ff>`);
      if (studentPhoto.value) {
        _push(`<img${ssrRenderAttr("src", studentPhoto.value)}${ssrRenderAttr("alt", `${form.first_name_th} ${form.last_name_th}`)} class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" data-v-376523ff>`);
      } else {
        _push(`<div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200" data-v-376523ff><svg class="w-16 h-16 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-376523ff><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" data-v-376523ff></path></svg><span class="text-xs text-gray-500 font-medium" data-v-376523ff>\u0E44\u0E21\u0E48\u0E21\u0E35\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></div>`);
      }
      if (studentPhoto.value) {
        _push(`<div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center" data-v-376523ff><i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" data-v-376523ff></i></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="absolute -bottom-2 -right-2 bg-gradient-to-r from-emerald-500 to-green-500 text-white rounded-full p-2 shadow-lg" data-v-376523ff><i class="fas fa-camera text-sm" data-v-376523ff></i></div></div><div class="flex-1 space-y-4 text-center md:text-left" data-v-376523ff><div data-v-376523ff><h4 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent" data-v-376523ff>${ssrInterpolate(form.title_prefix_th)} ${ssrInterpolate(form.first_name_th)} ${ssrInterpolate(form.last_name_th)}</h4><div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-3" data-v-376523ff><span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800 border border-emerald-200" data-v-376523ff><i class="fas fa-id-card mr-2" data-v-376523ff></i> ${ssrInterpolate(form.student_id)}</span>`);
      if (classDisplayText.value) {
        _push(`<span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-200" data-v-376523ff><i class="fas fa-school mr-2" data-v-376523ff></i> \u0E0A\u0E31\u0E49\u0E19 ${ssrInterpolate(classDisplayText.value)}</span>`);
      } else {
        _push(`<!---->`);
      }
      if (form.nickname) {
        _push(`<span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200" data-v-376523ff><i class="fas fa-smile mr-2" data-v-376523ff></i> ${ssrInterpolate(form.nickname)}</span>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><div class="pt-2" data-v-376523ff><input type="file" accept="image/*" class="hidden" data-v-376523ff><button type="button" class="group inline-flex items-center px-5 py-2.5 border-2 border-emerald-300 text-sm font-semibold rounded-xl text-emerald-700 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200 hover:scale-105 hover:shadow-lg" data-v-376523ff><i class="fas fa-camera mr-2 group-hover:rotate-12 transition-transform" data-v-376523ff></i> \u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E </button></div></div></div></div><div class="space-y-6" data-v-376523ff><div class="flex items-center space-x-3 mb-6" data-v-376523ff><div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-500 rounded-xl flex items-center justify-center shadow-lg" data-v-376523ff><i class="fas fa-signature text-white text-lg" data-v-376523ff></i></div><div data-v-376523ff><h4 class="text-lg font-bold text-gray-900" data-v-376523ff>\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25</h4><p class="text-sm text-gray-500" data-v-376523ff>\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E0A\u0E37\u0E48\u0E2D\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E07\u0E01\u0E24\u0E29</p></div></div><div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border-2 border-blue-100 transition-all duration-300 hover:border-blue-300" data-v-376523ff><div class="flex items-center mb-5" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><span class="bg-blue-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md" data-v-376523ff>TH</span><span class="text-base font-semibold text-blue-900" data-v-376523ff>\u0E0A\u0E37\u0E48\u0E2D\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22</span></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-5" data-v-376523ff><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-user-tag text-blue-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E04\u0E33\u0E19\u0E33\u0E2B\u0E19\u0E49\u0E32</span></div></label><select class="form-select" required data-v-376523ff><option value="" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_th) ? ssrLooseContain(form.title_prefix_th, "") : ssrLooseEqual(form.title_prefix_th, "")) ? " selected" : ""}>-- \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E04\u0E33\u0E19\u0E33\u0E2B\u0E19\u0E49\u0E32 --</option><option value="\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_th) ? ssrLooseContain(form.title_prefix_th, "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22") : ssrLooseEqual(form.title_prefix_th, "\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22")) ? " selected" : ""}>\u0E40\u0E14\u0E47\u0E01\u0E0A\u0E32\u0E22</option><option value="\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_th) ? ssrLooseContain(form.title_prefix_th, "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07") : ssrLooseEqual(form.title_prefix_th, "\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07")) ? " selected" : ""}>\u0E40\u0E14\u0E47\u0E01\u0E2B\u0E0D\u0E34\u0E07</option><option value="\u0E19\u0E32\u0E22" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_th) ? ssrLooseContain(form.title_prefix_th, "\u0E19\u0E32\u0E22") : ssrLooseEqual(form.title_prefix_th, "\u0E19\u0E32\u0E22")) ? " selected" : ""}>\u0E19\u0E32\u0E22</option><option value="\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_th) ? ssrLooseContain(form.title_prefix_th, "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27") : ssrLooseEqual(form.title_prefix_th, "\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27")) ? " selected" : ""}>\u0E19\u0E32\u0E07\u0E2A\u0E32\u0E27</option><option value="\u0E19\u0E32\u0E07" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_th) ? ssrLooseContain(form.title_prefix_th, "\u0E19\u0E32\u0E07") : ssrLooseEqual(form.title_prefix_th, "\u0E19\u0E32\u0E07")) ? " selected" : ""}>\u0E19\u0E32\u0E07</option></select></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-user text-blue-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E0A\u0E37\u0E48\u0E2D</span></div></label><input type="text"${ssrRenderAttr("value", form.first_name_th)} class="form-input" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22" required data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-user text-blue-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25</span></div></label><input type="text"${ssrRenderAttr("value", form.last_name_th)} class="form-input" placeholder="\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22" required data-v-376523ff></div></div></div><div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border-2 border-green-100 transition-all duration-300 hover:border-green-300" data-v-376523ff><div class="flex items-center mb-5" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><span class="bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md" data-v-376523ff>EN</span><span class="text-base font-semibold text-green-900" data-v-376523ff>\u0E0A\u0E37\u0E48\u0E2D\u0E20\u0E32\u0E29\u0E32\u0E2D\u0E31\u0E07\u0E01\u0E24\u0E29</span></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-5" data-v-376523ff><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-user-tag text-green-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>Title</span></div></label><select class="form-select" data-v-376523ff><option value="" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_en) ? ssrLooseContain(form.title_prefix_en, "") : ssrLooseEqual(form.title_prefix_en, "")) ? " selected" : ""}>-- Select Title --</option><option value="Master" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_en) ? ssrLooseContain(form.title_prefix_en, "Master") : ssrLooseEqual(form.title_prefix_en, "Master")) ? " selected" : ""}>Master</option><option value="Miss" data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_en) ? ssrLooseContain(form.title_prefix_en, "Miss") : ssrLooseEqual(form.title_prefix_en, "Miss")) ? " selected" : ""}>Miss</option><option value="Mr." data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_en) ? ssrLooseContain(form.title_prefix_en, "Mr.") : ssrLooseEqual(form.title_prefix_en, "Mr.")) ? " selected" : ""}>Mr.</option><option value="Mrs." data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_en) ? ssrLooseContain(form.title_prefix_en, "Mrs.") : ssrLooseEqual(form.title_prefix_en, "Mrs.")) ? " selected" : ""}>Mrs.</option><option value="Ms." data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.title_prefix_en) ? ssrLooseContain(form.title_prefix_en, "Ms.") : ssrLooseEqual(form.title_prefix_en, "Ms.")) ? " selected" : ""}>Ms.</option></select></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-user text-green-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>First Name</span></div></label><input type="text"${ssrRenderAttr("value", form.first_name_en)} class="form-input" placeholder="First Name" data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-user text-green-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>Last Name</span></div></label><input type="text"${ssrRenderAttr("value", form.last_name_en)} class="form-input" placeholder="Last Name" data-v-376523ff></div></div></div></div><div class="space-y-6" data-v-376523ff><div class="flex items-center space-x-3 mb-6" data-v-376523ff><div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg" data-v-376523ff><i class="fas fa-info-circle text-white text-lg" data-v-376523ff></i></div><div data-v-376523ff><h4 class="text-lg font-bold text-gray-900" data-v-376523ff>\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27</h4><p class="text-sm text-gray-500" data-v-376523ff>\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21\u0E02\u0E2D\u0E07\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</p></div></div><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" data-v-376523ff><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-id-card text-indigo-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E40\u0E25\u0E02\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E0A\u0E19</span></div></label><input type="text"${ssrRenderAttr("value", formatCitizenId(form.citizen_id))} class="form-input bg-gray-50 cursor-not-allowed" readonly data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-barcode text-purple-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E23\u0E2B\u0E31\u0E2A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</span></div></label><input type="text"${ssrRenderAttr("value", form.student_id)} class="form-input bg-gray-50 cursor-not-allowed" readonly data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-smile text-yellow-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E48\u0E19</span></div></label><input type="text"${ssrRenderAttr("value", form.nickname)} class="form-input" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E48\u0E19" data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-venus-mars text-pink-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E40\u0E1E\u0E28</span></div></label><select class="form-select" required data-v-376523ff><option${ssrRenderAttr("value", null)} disabled data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.gender) ? ssrLooseContain(form.gender, null) : ssrLooseEqual(form.gender, null)) ? " selected" : ""}>-- \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E40\u0E1E\u0E28 --</option><option${ssrRenderAttr("value", 1)} data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.gender) ? ssrLooseContain(form.gender, 1) : ssrLooseEqual(form.gender, 1)) ? " selected" : ""}>\u0E0A\u0E32\u0E22</option><option${ssrRenderAttr("value", 0)} data-v-376523ff${ssrIncludeBooleanAttr(Array.isArray(form.gender) ? ssrLooseContain(form.gender, 0) : ssrLooseEqual(form.gender, 0)) ? " selected" : ""}>\u0E2B\u0E0D\u0E34\u0E07</option></select></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-birthday-cake text-red-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E27\u0E31\u0E19\u0E40\u0E01\u0E34\u0E14</span></div></label><input type="date"${ssrRenderAttr("value", form.date_of_birth)} class="form-input" required data-v-376523ff>`);
      if (form.date_of_birth) {
        _push(`<div class="mt-2 flex items-center justify-between text-xs bg-gradient-to-r from-blue-50 to-purple-50 px-3 py-2 rounded-lg border border-blue-100" data-v-376523ff><span class="flex items-center text-blue-700" data-v-376523ff><i class="far fa-calendar-alt mr-1.5" data-v-376523ff></i> ${ssrInterpolate(unref(formatDateThai)(form.date_of_birth))}</span><span class="flex items-center text-purple-700 font-semibold" data-v-376523ff><i class="fas fa-hourglass-half mr-1.5" data-v-376523ff></i> ${ssrInterpolate(unref(calculateAge)(form.date_of_birth))} \u0E1B\u0E35 </span></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-flag text-blue-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E2A\u0E31\u0E0D\u0E0A\u0E32\u0E15\u0E34</span></div></label><input type="text"${ssrRenderAttr("value", form.nationality)} class="form-input" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E2A\u0E31\u0E0D\u0E0A\u0E32\u0E15\u0E34" data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-praying-hands text-orange-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E28\u0E32\u0E2A\u0E19\u0E32</span></div></label><input type="text"${ssrRenderAttr("value", form.religion)} class="form-input" placeholder="\u0E01\u0E23\u0E2D\u0E01\u0E28\u0E32\u0E2A\u0E19\u0E32" data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-graduation-cap text-emerald-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19 (\u0E21.)</span></div></label><input type="text"${ssrRenderAttr("value", form.class_level)} class="form-input bg-gray-50 cursor-not-allowed" readonly data-v-376523ff></div><div class="form-group" data-v-376523ff><label class="form-label group" data-v-376523ff><div class="flex items-center space-x-2" data-v-376523ff><i class="fas fa-door-open text-teal-500 group-hover:scale-110 transition-transform" data-v-376523ff></i><span data-v-376523ff>\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 (/)</span></div></label><input type="text"${ssrRenderAttr("value", form.class_section)} class="form-input bg-gray-50 cursor-not-allowed" readonly data-v-376523ff></div></div></div><div class="pt-6 border-t-2 border-gray-200" data-v-376523ff><div class="flex flex-wrap items-center justify-between gap-3" data-v-376523ff><div class="flex flex-wrap gap-2 text-xs" data-v-376523ff><div class="flex items-center bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-2 rounded-lg border border-gray-200 shadow-sm" data-v-376523ff><i class="fas fa-clock text-gray-500 mr-2" data-v-376523ff></i><span class="text-gray-700" data-v-376523ff>\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15: ${ssrInterpolate((/* @__PURE__ */ new Date()).toLocaleDateString("th-TH"))} ${ssrInterpolate((/* @__PURE__ */ new Date()).toLocaleTimeString("th-TH", { hour: "2-digit", minute: "2-digit" }))}</span></div></div></div></div><div class="pt-6 border-t-2 border-gray-200" data-v-376523ff><div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4" data-v-376523ff><div class="flex-1" data-v-376523ff>`);
      if (isSaving.value) {
        _push(`<div class="flex items-center text-sm text-blue-600 bg-blue-50 px-4 py-3 rounded-xl border border-blue-200 animate-pulse" data-v-376523ff><svg class="animate-spin h-5 w-5 mr-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" data-v-376523ff><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" data-v-376523ff></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" data-v-376523ff></path></svg><span class="font-medium" data-v-376523ff>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25...</span></div>`);
      } else if (saveStatus.value === "success") {
        _push(`<div class="flex items-center text-sm text-green-700 bg-gradient-to-r from-green-50 to-emerald-50 px-4 py-3 rounded-xl border border-green-200 shadow-sm" data-v-376523ff><div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3" data-v-376523ff><i class="fas fa-check text-white text-xs" data-v-376523ff></i></div><span class="font-semibold" data-v-376523ff>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!</span></div>`);
      } else if (saveStatus.value === "error") {
        _push(`<div class="flex items-center text-sm text-red-700 bg-gradient-to-r from-red-50 to-pink-50 px-4 py-3 rounded-xl border border-red-200 shadow-sm" data-v-376523ff><div class="w-5 h-5 bg-red-500 rounded-full flex items-center justify-center mr-3" data-v-376523ff><i class="fas fa-times text-white text-xs" data-v-376523ff></i></div><span class="font-semibold" data-v-376523ff>\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07</span></div>`);
      } else {
        _push(`<div class="text-sm text-gray-500 px-4 py-3" data-v-376523ff><i class="fas fa-info-circle mr-2" data-v-376523ff></i> \u0E01\u0E23\u0E38\u0E13\u0E32\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E48\u0E2D\u0E19\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </div>`);
      }
      _push(`</div><button type="submit"${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="group relative inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-emerald-600 to-green-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-green-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 hover:scale-105 hover:shadow-2xl overflow-hidden" data-v-376523ff><div class="absolute inset-0 bg-gradient-to-r from-green-600 to-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300" data-v-376523ff></div><div class="relative flex items-center" data-v-376523ff>`);
      if (!isSaving.value) {
        _push(`<svg class="w-5 h-5 mr-3 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-v-376523ff><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m0 0h8m0 0h2a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4 0V5a2 2 0 011-1h4a2 2 0 011 1v2M8 7V5a2 2 0 011-1h4a2 2 0 011 1v2m0 0v4m0 0h-4m4 0V9H8v4z" data-v-376523ff></path></svg>`);
      } else {
        _push(`<svg class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" data-v-376523ff><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" data-v-376523ff></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" data-v-376523ff></path></svg>`);
      }
      _push(`<span class="text-base" data-v-376523ff>${ssrInterpolate(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25")}</span></div></button></div></div></form></div>`);
      if (showPhotoModal.value) {
        _push(`<div class="fixed inset-0 z-50 overflow-y-auto" data-v-376523ff><div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0" data-v-376523ff><div class="fixed inset-0 transition-opacity bg-black bg-opacity-75" data-v-376523ff></div><div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full" data-v-376523ff><div class="bg-gray-50 px-4 py-3 sm:px-6 flex items-center justify-between border-b" data-v-376523ff><h3 class="text-lg leading-6 font-medium text-gray-900" data-v-376523ff> \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E ${ssrInterpolate(form.title_prefix_th)} ${ssrInterpolate(form.first_name_th)} ${ssrInterpolate(form.last_name_th)}</h3><button class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150" data-v-376523ff><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" data-v-376523ff><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" data-v-376523ff></path></svg></button></div><div class="bg-white px-4 py-6 sm:px-6" data-v-376523ff><div class="flex justify-center" data-v-376523ff><img${ssrRenderAttr("src", studentPhoto.value)}${ssrRenderAttr("alt", `${form.first_name_th} ${form.last_name_th}`)} class="max-w-full max-h-96 object-contain rounded-lg shadow-lg" data-v-376523ff></div></div><div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t" data-v-376523ff><button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors" data-v-376523ff> \u0E1B\u0E34\u0E14 </button></div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Components/StudentsCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const StudentsCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-376523ff"]]);

export { StudentsCard as default };
//# sourceMappingURL=StudentsCard-CUu90lC-.mjs.map
