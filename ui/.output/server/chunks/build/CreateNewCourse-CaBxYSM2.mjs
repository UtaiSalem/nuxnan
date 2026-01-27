import { b as useRuntimeConfig, j as __nuxt_component_0$2 } from './server.mjs';
import { ref, computed, mergeProps, withCtx, unref, createVNode, toDisplayString, withModifiers, createBlock, createCommentVNode, openBlock, withDirectives, vModelText, createTextVNode, Fragment, renderList, vModelSelect, vModelCheckbox, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderList, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { r as router } from './inertia-vue3-CWdJjaLG.mjs';
import _sfc_main$1 from './CoursesLayout-Dad4KCDw.mjs';
import { VueDatePicker } from '@vuepic/vue-datepicker';
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
import './main-CdHCodS1.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';

const _sfc_main = {
  __name: "CreateNewCourse",
  __ssrInlineRender: true,
  setup(__props) {
    const headerTitle = ref("\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48");
    const config = useRuntimeConfig();
    const tempCover = ref(`${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`);
    const coverInput = ref(null);
    const isOpenCategoryOptions = ref(false);
    const isOpenLevelOptions = ref(false);
    const crsStartDate = ref(/* @__PURE__ */ new Date());
    const crsEndDate = ref(/* @__PURE__ */ new Date());
    const courseRange = ref([crsStartDate.value, crsEndDate.value]);
    const currentDate = /* @__PURE__ */ new Date();
    const currentMonth = currentDate.getMonth() + 1;
    const currentYear = currentDate.getFullYear() + 543;
    let initialSemester = "1";
    let initialAcademicYear = currentYear;
    if (currentMonth >= 5 && currentMonth <= 9) {
      initialSemester = "1";
      initialAcademicYear = currentYear;
    } else if (currentMonth >= 10 && currentMonth <= 12) {
      initialSemester = "2";
      initialAcademicYear = currentYear;
    } else if (currentMonth >= 1 && currentMonth <= 3) {
      initialSemester = "2";
      initialAcademicYear = currentYear - 1;
    } else {
      initialSemester = "summer";
      initialAcademicYear = currentYear - 1;
    }
    ref({
      academy_id: "",
      code: "",
      name: "",
      description: "",
      category: "",
      level: "",
      credit_units: "",
      hours_per_week: "",
      start_date: courseRange.value[0],
      end_date: courseRange.value[1],
      auto_accept_members: true,
      tuition_fees: 0,
      saleable: true,
      price: 0,
      discount: 0,
      discount_type: "fixed",
      semester: initialSemester,
      academic_year: initialAcademicYear.toString(),
      status: true,
      cover: tempCover.value === `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg` ? null : tempCover.value
    });
    const form = ref({
      academy_id: "",
      code: "",
      name: "",
      description: "",
      //
      category: "",
      level: "",
      credit_units: 1,
      hours_per_week: 1,
      start_date: courseRange.value[0] ? new Date(courseRange.value[0]) : null,
      end_date: new Date((/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 30)),
      // new Date(new Date().setDate(new Date().getDate() + 30)),
      auto_accept_members: true,
      tuition_fees: 0,
      saleable: true,
      price: 0,
      discount: 0,
      discount_type: "fixed",
      semester: initialSemester,
      academic_year: initialAcademicYear.toString(),
      status: true,
      cover: ""
    });
    const courseCategories = ref([
      { name: "\u0E20\u0E32\u0E29\u0E32\u0E44\u0E17\u0E22" },
      { name: "\u0E04\u0E13\u0E34\u0E15\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C" },
      { name: "\u0E27\u0E34\u0E17\u0E22\u0E32\u0E28\u0E32\u0E2A\u0E15\u0E23\u0E4C" },
      { name: "\u0E2A\u0E31\u0E07\u0E04\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32 \u0E28\u0E32\u0E2A\u0E19\u0E32 \u0E41\u0E25\u0E30\u0E27\u0E31\u0E12\u0E19\u0E18\u0E23\u0E23\u0E21" },
      { name: "\u0E2A\u0E38\u0E02\u0E28\u0E36\u0E01\u0E29\u0E32\u0E41\u0E25\u0E30\u0E1E\u0E25\u0E28\u0E36\u0E01\u0E29\u0E32" },
      { name: "\u0E28\u0E34\u0E25\u0E1B\u0E30" },
      { name: "\u0E01\u0E32\u0E23\u0E07\u0E32\u0E19\u0E2D\u0E32\u0E0A\u0E35\u0E1E\u0E41\u0E25\u0E30\u0E40\u0E17\u0E04\u0E42\u0E19\u0E42\u0E25\u0E22\u0E35" },
      { name: "\u0E20\u0E32\u0E29\u0E32\u0E15\u0E48\u0E32\u0E07\u0E1B\u0E23\u0E30\u0E40\u0E17\u0E28" }
    ]);
    const courseLevelOptions = ref([
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 1" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 2" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 3" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 4" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 5" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E1B\u0E23\u0E30\u0E16\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 6" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 1" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 2" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 3" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 4" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 5" },
      { level: "\u0E0A\u0E31\u0E49\u0E19\u0E21\u0E31\u0E18\u0E22\u0E21\u0E28\u0E36\u0E01\u0E29\u0E32\u0E1B\u0E35\u0E17\u0E35\u0E48 6" }
    ]);
    const browseCover = () => {
      coverInput.value.click();
    };
    function onCoverInputChange(event) {
      form.value.cover = event.target.files[0];
      tempCover.value = URL.createObjectURL(event.target.files[0]);
    }
    function handleSelectCategory(category) {
      form.value.category = category;
      isOpenCategoryOptions.value = false;
    }
    function handleSelectLevel(level) {
      form.value.level = level;
      isOpenLevelOptions.value = false;
    }
    function handleStartDateSelection(startData) {
      crsStartDate.value = startData;
      courseRange.value[0] = crsStartDate.value;
      form.value.start_date = new Date(crsStartDate.value) || null;
    }
    function handleEndDateSelection(endDateData) {
      crsEndDate.value = endDateData;
      courseRange.value[1] = crsEndDate.value;
      form.value.end_date = new Date(crsEndDate.value) || null;
    }
    const netPrice = computed(() => {
      if (!form.value.saleable) return 0;
      const price = Number(form.value.price) || 0;
      const discount = Number(form.value.discount) || 0;
      if (form.value.discount_type === "percent") {
        const discountAmount = price * discount / 100;
        return Math.max(0, price - discountAmount);
      }
      return Math.max(0, price - discount);
    });
    async function handleSubmitForm() {
      var _a, _b, _c;
      try {
        const config2 = { headers: { "content-type": "multipart/form-data" } };
        const courseFormData = new FormData();
        courseFormData.append("academy_id", (_a = form.value.academy_id) != null ? _a : null);
        courseFormData.append("code", form.value.code);
        courseFormData.append("name", form.value.name);
        courseFormData.append("description", form.value.description);
        courseFormData.append("category", form.value.category);
        courseFormData.append("level", form.value.level);
        courseFormData.append("credit_units", form.value.credit_units);
        courseFormData.append("hours_per_week", form.value.hours_per_week);
        courseFormData.append("start_date", (_b = new Date(form.value.start_date).toISOString()) != null ? _b : null);
        courseFormData.append("end_date", (_c = new Date(form.value.end_date).toISOString()) != null ? _c : null);
        courseFormData.append("auto_accept_members", form.value.auto_accept_members ? 1 : 0);
        courseFormData.append("tuition_fees", form.value.tuition_fees);
        courseFormData.append("saleable", form.value.saleable ? 1 : 0);
        courseFormData.append("price", form.value.price);
        courseFormData.append("discount", form.value.discount);
        courseFormData.append("discount_type", form.value.discount_type);
        courseFormData.append("semester", form.value.semester);
        courseFormData.append("academic_year", form.value.academic_year);
        courseFormData.append("status", form.value.status ? 1 : 0);
        if (form.value.cover) {
          courseFormData.append("cover", form.value.cover);
        }
        const courseResp = await axios.post(`/courses`, courseFormData, config2);
        if (courseResp.data && courseResp.data.success) {
          Swal.fire(
            "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
            "\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E21\u0E1A\u0E39\u0E23\u0E13\u0E4C",
            "success"
          );
          router.get(`/courses/${courseResp.data.newCourse.id}`);
        }
      } catch (error) {
        Swal.fire(
          "\u0E25\u0E49\u0E21\u0E40\u0E2B\u0E25\u0E27",
          "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25, <br />\u0E01\u0E23\u0E38\u0E13\u0E32\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E04\u0E27\u0E32\u0E21\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 " + error.message,
          "error"
        );
      }
    }
    return (_ctx, _push, _parent, _attrs) => {
      const _component_ClientOnly = __nuxt_component_0$2;
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({ coursePageTitle: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48" }, _attrs), {
        coursesMainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="max-w-7xl mx-auto pb-12"${_scopeId}><div class="mb-8"${_scopeId}><h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2"${_scopeId}>${ssrInterpolate(headerTitle.value)}</h1><p class="text-gray-500 dark:text-gray-400"${_scopeId}>\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E0A\u0E38\u0E21\u0E0A\u0E19\u0E41\u0E2B\u0E48\u0E07\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</p></div><form class="grid grid-cols-1 lg:grid-cols-12 gap-8" id="create-new-course-form"${_scopeId}><div class="lg:col-span-4 space-y-6"${_scopeId}><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4"${_scopeId}>\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</label><div class="group relative aspect-video w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-violet-500 dark:hover:border-violet-500 transition-colors cursor-pointer"${_scopeId}>`);
            if (tempCover.value) {
              _push2(`<img${ssrRenderAttr("src", tempCover.value)} class="w-full h-full object-cover"${_scopeId}>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="absolute inset-0 flex flex-col items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:camera",
              class: "w-10 h-10 text-white mb-2"
            }, null, _parent2, _scopeId));
            _push2(`<span class="text-white text-sm font-medium"${_scopeId}>\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E23\u0E39\u0E1B\u0E1B\u0E01</span></div>`);
            if (!tempCover.value || tempCover.value.includes("default_cover")) {
              _push2(`<div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:photo",
                class: "w-12 h-12 text-gray-400 mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<span class="text-gray-500 text-sm"${_scopeId}>\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E</span></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<input type="file" class="hidden" accept="image/*"${_scopeId}></div><p class="text-xs text-gray-500 mt-2 text-center"${_scopeId}>\u0E41\u0E19\u0E30\u0E19\u0E33\u0E02\u0E19\u0E32\u0E14 1920x1080px \u0E2B\u0E23\u0E37\u0E2D\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E2A\u0E48\u0E27\u0E19 16:9</p></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4"${_scopeId}>\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19</label><div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50"${_scopeId}><img${ssrRenderAttr("src", _ctx.$page.props.auth.user.avatar || _ctx.$page.props.auth.user.profile_photo_url)}${ssrRenderAttr("alt", _ctx.$page.props.auth.user.name)} class="w-12 h-12 rounded-full ring-2 ring-white dark:ring-gray-600 object-cover"${_scopeId}><div${_scopeId}><h3 class="font-bold text-gray-900 dark:text-gray-100"${_scopeId}>${ssrInterpolate(_ctx.$page.props.auth.user.name)}</h3><p class="text-xs text-gray-500 dark:text-gray-400"${_scopeId}>Instructor</p></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32</label><div class="relative"${_scopeId}><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:qr-code",
              class: "h-5 w-5 text-gray-400"
            }, null, _parent2, _scopeId));
            _push2(`</div><input type="text"${ssrRenderAttr("value", form.value.code)} class="block w-full pl-10 pr-3 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all" placeholder="\u0E40\u0E0A\u0E48\u0E19 CS101"${_scopeId}></div></div></div><div class="lg:col-span-8 space-y-8"${_scopeId}><section${_scopeId}><h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"${_scopeId}><span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/50 text-violet-600 flex items-center justify-center text-sm"${_scopeId}>1</span> \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B </h2><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 <span class="text-red-500"${_scopeId}>*</span></label><input type="text"${ssrRenderAttr("value", form.value.name)} required class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all font-medium text-lg" placeholder="\u0E15\u0E31\u0E49\u0E07\u0E0A\u0E37\u0E48\u0E2D\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E49\u0E2A\u0E37\u0E48\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E2B\u0E21\u0E32\u0E22\u0E41\u0E25\u0E30\u0E19\u0E48\u0E32\u0E2A\u0E19\u0E43\u0E08"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</label><textarea rows="5" class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all" placeholder="\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E30\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49..."${_scopeId}>${ssrInterpolate(form.value.description)}</textarea></div></div></section><section${_scopeId}><h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"${_scopeId}><span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 flex items-center justify-center text-sm"${_scopeId}>2</span> \u0E01\u0E32\u0E23\u0E08\u0E31\u0E14\u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48\u0E41\u0E25\u0E30\u0E23\u0E30\u0E14\u0E31\u0E1A </h2><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-6"${_scopeId}><div class="relative"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E23\u0E30\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</label><div class="relative"${_scopeId}><button type="button" class="w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"${_scopeId}><span class="${ssrRenderClass(form.value.category ? "text-gray-900 dark:text-white" : "text-gray-400")}"${_scopeId}>${ssrInterpolate(form.value.category || "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E23\u0E30")}</span>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:chevron-down",
              class: "w-5 h-5 text-gray-400"
            }, null, _parent2, _scopeId));
            _push2(`</button>`);
            if (isOpenCategoryOptions.value) {
              _push2(`<div class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 max-h-60 overflow-y-auto"${_scopeId}><!--[-->`);
              ssrRenderList(courseCategories.value, (cat) => {
                _push2(`<div class="px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"${_scopeId}>${ssrInterpolate(cat.name)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div><div class="relative"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19</label><div class="relative"${_scopeId}><button type="button" class="w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"${_scopeId}><span class="${ssrRenderClass(form.value.level ? "text-gray-900 dark:text-white" : "text-gray-400")}"${_scopeId}>${ssrInterpolate(form.value.level || "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19")}</span>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:chevron-down",
              class: "w-5 h-5 text-gray-400"
            }, null, _parent2, _scopeId));
            _push2(`</button>`);
            if (isOpenLevelOptions.value) {
              _push2(`<div class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 max-h-60 overflow-y-auto"${_scopeId}><!--[-->`);
              ssrRenderList(courseLevelOptions.value, (lvl) => {
                _push2(`<div class="px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"${_scopeId}>${ssrInterpolate(lvl.level)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div></div></section><section${_scopeId}><h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"${_scopeId}><span class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 flex items-center justify-center text-sm"${_scopeId}>3</span> \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E41\u0E25\u0E30\u0E40\u0E27\u0E25\u0E32 </h2><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6"${_scopeId}><div class="grid grid-cols-1 md:grid-cols-2 gap-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19</label><select class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"${_scopeId}><option value=""${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "") : ssrLooseEqual(form.value.semester, "")) ? " selected" : ""}${_scopeId}>\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19</option><option value="1"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "1") : ssrLooseEqual(form.value.semester, "1")) ? " selected" : ""}${_scopeId}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 1</option><option value="2"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "2") : ssrLooseEqual(form.value.semester, "2")) ? " selected" : ""}${_scopeId}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 2</option><option value="3"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "3") : ssrLooseEqual(form.value.semester, "3")) ? " selected" : ""}${_scopeId}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 3</option><option value="summer"${ssrIncludeBooleanAttr(Array.isArray(form.value.semester) ? ssrLooseContain(form.value.semester, "summer") : ssrLooseEqual(form.value.semester, "summer")) ? " selected" : ""}${_scopeId}>\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E24\u0E14\u0E39\u0E23\u0E49\u0E2D\u0E19</option></select></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32</label><input type="text"${ssrRenderAttr("value", form.value.academic_year)} placeholder="\u0E40\u0E0A\u0E48\u0E19 2567" class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E2B\u0E19\u0E48\u0E27\u0E22\u0E01\u0E34\u0E15</label><div class="relative"${_scopeId}><input type="number"${ssrRenderAttr("value", form.value.credit_units)} min="0" class="block w-full pl-4 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"${_scopeId}><span class="absolute right-4 top-3 text-gray-400 text-sm"${_scopeId}>\u0E2B\u0E19\u0E48\u0E27\u0E22</span></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E04\u0E32\u0E1A/\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C</label><div class="relative"${_scopeId}><input type="number"${ssrRenderAttr("value", form.value.hours_per_week)} min="0" class="block w-full pl-4 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"${_scopeId}><span class="absolute right-4 top-3 text-gray-400 text-sm"${_scopeId}>\u0E0A\u0E21.</span></div></div></div><div class="border-t border-gray-100 dark:border-gray-700 pt-6 mt-2"${_scopeId}><div class="grid grid-cols-1 md:grid-cols-2 gap-6"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19</label>`);
            _push2(ssrRenderComponent(_component_ClientOnly, null, {}, _parent2, _scopeId));
            _push2(`</div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14</label>`);
            _push2(ssrRenderComponent(_component_ClientOnly, null, {}, _parent2, _scopeId));
            _push2(`</div></div></div><div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-700"${_scopeId}><div${_scopeId}><h4 class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34</h4><p class="text-sm text-gray-500"${_scopeId}>\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E30\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49\u0E17\u0E31\u0E19\u0E17\u0E35\u0E42\u0E14\u0E22\u0E44\u0E21\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34</p></div><label class="relative inline-flex items-center cursor-pointer"${_scopeId}><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(form.value.auto_accept_members) ? ssrLooseContain(form.value.auto_accept_members, null) : form.value.auto_accept_members) ? " checked" : ""} class="sr-only peer"${_scopeId}><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"${_scopeId}></div></label></div></div></section><section${_scopeId}><h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2"${_scopeId}><span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 flex items-center justify-center text-sm"${_scopeId}>4</span> \u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E41\u0E25\u0E30\u0E23\u0E32\u0E04\u0E32 </h2><div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6"${_scopeId}><div class="flex items-center justify-between mb-4"${_scopeId}><div${_scopeId}><h4 class="font-medium text-gray-900 dark:text-white"${_scopeId}>\u0E40\u0E1B\u0E34\u0E14\u0E02\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32</h4><p class="text-sm text-gray-500"${_scopeId}>\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E2B\u0E32\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E02\u0E32\u0E22\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E19\u0E35\u0E49\u0E43\u0E2B\u0E49\u0E1A\u0E38\u0E04\u0E04\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B</p></div><label class="relative inline-flex items-center cursor-pointer"${_scopeId}><input type="checkbox"${ssrIncludeBooleanAttr(Array.isArray(form.value.saleable) ? ssrLooseContain(form.value.saleable, null) : form.value.saleable) ? " checked" : ""} class="sr-only peer"${_scopeId}><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[&#39;&#39;] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"${_scopeId}></div></label></div>`);
            if (form.value.saleable) {
              _push2(`<div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in-down"${_scopeId}><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E23\u0E32\u0E04\u0E32\u0E1B\u0E01\u0E15\u0E34</label><div class="relative"${_scopeId}><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"${_scopeId}><span class="text-gray-500 font-bold"${_scopeId}>\u0E3F</span></div><input type="number"${ssrRenderAttr("value", form.value.price)} min="0" placeholder="0.00" class="block w-full pl-8 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"${_scopeId}></div></div><div${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14</label><div class="flex"${_scopeId}><div class="relative flex-1"${_scopeId}><input type="number"${ssrRenderAttr("value", form.value.discount)} min="0" class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-l-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 z-10" placeholder="0"${_scopeId}></div><select class="px-3 border-y border-r border-gray-200 dark:border-gray-600 rounded-r-xl bg-gray-50 dark:bg-gray-600 text-gray-700 dark:text-white focus:outline-none"${_scopeId}><option value="fixed"${ssrIncludeBooleanAttr(Array.isArray(form.value.discount_type) ? ssrLooseContain(form.value.discount_type, "fixed") : ssrLooseEqual(form.value.discount_type, "fixed")) ? " selected" : ""}${_scopeId}>\u0E3F</option><option value="percent"${ssrIncludeBooleanAttr(Array.isArray(form.value.discount_type) ? ssrLooseContain(form.value.discount_type, "percent") : ssrLooseEqual(form.value.discount_type, "percent")) ? " selected" : ""}${_scopeId}>%</option></select></div></div><div class="md:col-span-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/50 flex justify-between items-center"${_scopeId}><span class="text-emerald-800 dark:text-emerald-100 font-medium"${_scopeId}>\u0E23\u0E32\u0E04\u0E32\u0E2A\u0E38\u0E17\u0E18\u0E34 (Net Price)</span><div class="text-right"${_scopeId}><div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"${_scopeId}>\u0E3F${ssrInterpolate(netPrice.value.toLocaleString())}</div>`);
              if (Number(form.value.discount) > 0) {
                _push2(`<div class="text-xs text-emerald-600/70"${_scopeId}> (\u0E25\u0E14 ${ssrInterpolate(form.value.discount_type === "percent" ? form.value.discount + "%" : "\u0E3F" + form.value.discount)}) </div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="border-t border-gray-100 dark:border-gray-700 pt-6"${_scopeId}><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"${_scopeId}>\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 (Point)</label><div class="relative max-w-sm"${_scopeId}><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "noto:coin",
              class: "h-5 w-5"
            }, null, _parent2, _scopeId));
            _push2(`</div><input type="number"${ssrRenderAttr("value", form.value.tuition_fees)} min="0" placeholder="0" class="block w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"${_scopeId}></div><p class="text-xs text-gray-500 mt-1"${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 (0 = \u0E1F\u0E23\u0E35)</p></div></div></section><div class="flex items-center justify-end gap-4 pt-4 pb-12"${_scopeId}><button type="button" class="px-6 py-3 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="submit" class="px-8 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold shadow-lg shadow-violet-200 dark:shadow-none transition-all transform hover:-translate-y-0.5"${_scopeId}> \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 </button></div></div></form></div>`);
          } else {
            return [
              createVNode("div", { class: "max-w-7xl mx-auto pb-12" }, [
                createVNode("div", { class: "mb-8" }, [
                  createVNode("h1", { class: "text-3xl font-bold text-gray-800 dark:text-white mb-2" }, toDisplayString(headerTitle.value), 1),
                  createVNode("p", { class: "text-gray-500 dark:text-gray-400" }, "\u0E01\u0E23\u0E2D\u0E01\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E21\u0E48\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E0A\u0E38\u0E21\u0E0A\u0E19\u0E41\u0E2B\u0E48\u0E07\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49")
                ]),
                createVNode("form", {
                  onSubmit: withModifiers(handleSubmitForm, ["prevent"]),
                  class: "grid grid-cols-1 lg:grid-cols-12 gap-8",
                  id: "create-new-course-form"
                }, [
                  createVNode("div", { class: "lg:col-span-4 space-y-6" }, [
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700" }, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4" }, "\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E1B\u0E01\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"),
                      createVNode("div", {
                        class: "group relative aspect-video w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-violet-500 dark:hover:border-violet-500 transition-colors cursor-pointer",
                        onClick: browseCover
                      }, [
                        tempCover.value ? (openBlock(), createBlock("img", {
                          key: 0,
                          src: tempCover.value,
                          class: "w-full h-full object-cover"
                        }, null, 8, ["src"])) : createCommentVNode("", true),
                        createVNode("div", { class: "absolute inset-0 flex flex-col items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity" }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:camera",
                            class: "w-10 h-10 text-white mb-2"
                          }),
                          createVNode("span", { class: "text-white text-sm font-medium" }, "\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E23\u0E39\u0E1B\u0E1B\u0E01")
                        ]),
                        !tempCover.value || tempCover.value.includes("default_cover") ? (openBlock(), createBlock("div", {
                          key: 1,
                          class: "absolute inset-0 flex flex-col items-center justify-center pointer-events-none"
                        }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:photo",
                            class: "w-12 h-12 text-gray-400 mb-2"
                          }),
                          createVNode("span", { class: "text-gray-500 text-sm" }, "\u0E2D\u0E31\u0E1B\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E")
                        ])) : createCommentVNode("", true),
                        createVNode("input", {
                          type: "file",
                          class: "hidden",
                          accept: "image/*",
                          ref_key: "coverInput",
                          ref: coverInput,
                          onChange: onCoverInputChange
                        }, null, 544)
                      ]),
                      createVNode("p", { class: "text-xs text-gray-500 mt-2 text-center" }, "\u0E41\u0E19\u0E30\u0E19\u0E33\u0E02\u0E19\u0E32\u0E14 1920x1080px \u0E2B\u0E23\u0E37\u0E2D\u0E2D\u0E31\u0E15\u0E23\u0E32\u0E2A\u0E48\u0E27\u0E19 16:9")
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700" }, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4" }, "\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19"),
                      createVNode("div", { class: "flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50" }, [
                        createVNode("img", {
                          src: _ctx.$page.props.auth.user.avatar || _ctx.$page.props.auth.user.profile_photo_url,
                          alt: _ctx.$page.props.auth.user.name,
                          class: "w-12 h-12 rounded-full ring-2 ring-white dark:ring-gray-600 object-cover"
                        }, null, 8, ["src", "alt"]),
                        createVNode("div", null, [
                          createVNode("h3", { class: "font-bold text-gray-900 dark:text-gray-100" }, toDisplayString(_ctx.$page.props.auth.user.name), 1),
                          createVNode("p", { class: "text-xs text-gray-500 dark:text-gray-400" }, "Instructor")
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700" }, [
                      createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E23\u0E2B\u0E31\u0E2A\u0E27\u0E34\u0E0A\u0E32"),
                      createVNode("div", { class: "relative" }, [
                        createVNode("div", { class: "absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:qr-code",
                            class: "h-5 w-5 text-gray-400"
                          })
                        ]),
                        withDirectives(createVNode("input", {
                          type: "text",
                          "onUpdate:modelValue": ($event) => form.value.code = $event,
                          class: "block w-full pl-10 pr-3 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all",
                          placeholder: "\u0E40\u0E0A\u0E48\u0E19 CS101"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelText, form.value.code]
                        ])
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "lg:col-span-8 space-y-8" }, [
                    createVNode("section", null, [
                      createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/50 text-violet-600 flex items-center justify-center text-sm" }, "1"),
                        createTextVNode(" \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B ")
                      ]),
                      createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6" }, [
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, [
                            createTextVNode("\u0E0A\u0E37\u0E48\u0E2D\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 "),
                            createVNode("span", { class: "text-red-500" }, "*")
                          ]),
                          withDirectives(createVNode("input", {
                            type: "text",
                            "onUpdate:modelValue": ($event) => form.value.name = $event,
                            required: "",
                            class: "block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all font-medium text-lg",
                            placeholder: "\u0E15\u0E31\u0E49\u0E07\u0E0A\u0E37\u0E48\u0E2D\u0E27\u0E34\u0E0A\u0E32\u0E43\u0E2B\u0E49\u0E2A\u0E37\u0E48\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E2B\u0E21\u0E32\u0E22\u0E41\u0E25\u0E30\u0E19\u0E48\u0E32\u0E2A\u0E19\u0E43\u0E08"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, form.value.name]
                          ])
                        ]),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"),
                          withDirectives(createVNode("textarea", {
                            "onUpdate:modelValue": ($event) => form.value.description = $event,
                            rows: "5",
                            class: "block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all",
                            placeholder: "\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E2A\u0E34\u0E48\u0E07\u0E17\u0E35\u0E48\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E30\u0E44\u0E14\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49..."
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, form.value.description]
                          ])
                        ])
                      ])
                    ]),
                    createVNode("section", null, [
                      createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 flex items-center justify-center text-sm" }, "2"),
                        createTextVNode(" \u0E01\u0E32\u0E23\u0E08\u0E31\u0E14\u0E2B\u0E21\u0E27\u0E14\u0E2B\u0E21\u0E39\u0E48\u0E41\u0E25\u0E30\u0E23\u0E30\u0E14\u0E31\u0E1A ")
                      ]),
                      createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-6" }, [
                        createVNode("div", { class: "relative" }, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E23\u0E30\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"),
                          createVNode("div", { class: "relative" }, [
                            createVNode("button", {
                              type: "button",
                              onClick: ($event) => isOpenCategoryOptions.value = !isOpenCategoryOptions.value,
                              class: "w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
                            }, [
                              createVNode("span", {
                                class: form.value.category ? "text-gray-900 dark:text-white" : "text-gray-400"
                              }, toDisplayString(form.value.category || "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E32\u0E23\u0E30"), 3),
                              createVNode(unref(Icon), {
                                icon: "heroicons:chevron-down",
                                class: "w-5 h-5 text-gray-400"
                              })
                            ], 8, ["onClick"]),
                            isOpenCategoryOptions.value ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 max-h-60 overflow-y-auto"
                            }, [
                              (openBlock(true), createBlock(Fragment, null, renderList(courseCategories.value, (cat) => {
                                return openBlock(), createBlock("div", {
                                  key: cat.name,
                                  onClick: ($event) => handleSelectCategory(cat.name),
                                  class: "px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                }, toDisplayString(cat.name), 9, ["onClick"]);
                              }), 128))
                            ])) : createCommentVNode("", true)
                          ])
                        ]),
                        createVNode("div", { class: "relative" }, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19"),
                          createVNode("div", { class: "relative" }, [
                            createVNode("button", {
                              type: "button",
                              onClick: ($event) => isOpenLevelOptions.value = !isOpenLevelOptions.value,
                              class: "w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
                            }, [
                              createVNode("span", {
                                class: form.value.level ? "text-gray-900 dark:text-white" : "text-gray-400"
                              }, toDisplayString(form.value.level || "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E30\u0E14\u0E31\u0E1A\u0E0A\u0E31\u0E49\u0E19"), 3),
                              createVNode(unref(Icon), {
                                icon: "heroicons:chevron-down",
                                class: "w-5 h-5 text-gray-400"
                              })
                            ], 8, ["onClick"]),
                            isOpenLevelOptions.value ? (openBlock(), createBlock("div", {
                              key: 0,
                              class: "absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 max-h-60 overflow-y-auto"
                            }, [
                              (openBlock(true), createBlock(Fragment, null, renderList(courseLevelOptions.value, (lvl) => {
                                return openBlock(), createBlock("div", {
                                  key: lvl.level,
                                  onClick: ($event) => handleSelectLevel(lvl.level),
                                  class: "px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                }, toDisplayString(lvl.level), 9, ["onClick"]);
                              }), 128))
                            ])) : createCommentVNode("", true)
                          ])
                        ])
                      ])
                    ]),
                    createVNode("section", null, [
                      createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 flex items-center justify-center text-sm" }, "3"),
                        createTextVNode(" \u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E41\u0E25\u0E30\u0E40\u0E27\u0E25\u0E32 ")
                      ]),
                      createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6" }, [
                        createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-6" }, [
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19"),
                            withDirectives(createVNode("select", {
                              "onUpdate:modelValue": ($event) => form.value.semester = $event,
                              class: "block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                            }, [
                              createVNode("option", { value: "" }, "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19"),
                              createVNode("option", { value: "1" }, "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 1"),
                              createVNode("option", { value: "2" }, "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 2"),
                              createVNode("option", { value: "3" }, "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48 3"),
                              createVNode("option", { value: "summer" }, "\u0E20\u0E32\u0E04\u0E40\u0E23\u0E35\u0E22\u0E19\u0E24\u0E14\u0E39\u0E23\u0E49\u0E2D\u0E19")
                            ], 8, ["onUpdate:modelValue"]), [
                              [vModelSelect, form.value.semester]
                            ])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E1B\u0E35\u0E01\u0E32\u0E23\u0E28\u0E36\u0E01\u0E29\u0E32"),
                            withDirectives(createVNode("input", {
                              type: "text",
                              "onUpdate:modelValue": ($event) => form.value.academic_year = $event,
                              placeholder: "\u0E40\u0E0A\u0E48\u0E19 2567",
                              class: "block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, form.value.academic_year]
                            ])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E2B\u0E19\u0E48\u0E27\u0E22\u0E01\u0E34\u0E15"),
                            createVNode("div", { class: "relative" }, [
                              withDirectives(createVNode("input", {
                                type: "number",
                                "onUpdate:modelValue": ($event) => form.value.credit_units = $event,
                                min: "0",
                                class: "block w-full pl-4 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, form.value.credit_units]
                              ]),
                              createVNode("span", { class: "absolute right-4 top-3 text-gray-400 text-sm" }, "\u0E2B\u0E19\u0E48\u0E27\u0E22")
                            ])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E04\u0E32\u0E1A/\u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C"),
                            createVNode("div", { class: "relative" }, [
                              withDirectives(createVNode("input", {
                                type: "number",
                                "onUpdate:modelValue": ($event) => form.value.hours_per_week = $event,
                                min: "0",
                                class: "block w-full pl-4 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, form.value.hours_per_week]
                              ]),
                              createVNode("span", { class: "absolute right-4 top-3 text-gray-400 text-sm" }, "\u0E0A\u0E21.")
                            ])
                          ])
                        ]),
                        createVNode("div", { class: "border-t border-gray-100 dark:border-gray-700 pt-6 mt-2" }, [
                          createVNode("div", { class: "grid grid-cols-1 md:grid-cols-2 gap-6" }, [
                            createVNode("div", null, [
                              createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E23\u0E34\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19"),
                              createVNode(_component_ClientOnly, null, {
                                default: withCtx(() => [
                                  createVNode(unref(VueDatePicker), {
                                    modelValue: crsStartDate.value,
                                    "onUpdate:modelValue": [($event) => crsStartDate.value = $event, handleStartDateSelection],
                                    placeholder: "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E27\u0E31\u0E19\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19",
                                    format: "dd/MM/yyyy",
                                    "auto-apply": "",
                                    "input-class-name": "!bg-white dark:!bg-gray-700 !border-gray-200 dark:!border-gray-600 !rounded-xl !py-3 !text-gray-900 dark:!text-white"
                                  }, null, 8, ["modelValue", "onUpdate:modelValue"])
                                ]),
                                _: 1
                              })
                            ]),
                            createVNode("div", null, [
                              createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14"),
                              createVNode(_component_ClientOnly, null, {
                                default: withCtx(() => [
                                  createVNode(unref(VueDatePicker), {
                                    modelValue: crsEndDate.value,
                                    "onUpdate:modelValue": [($event) => crsEndDate.value = $event, handleEndDateSelection],
                                    placeholder: "\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E27\u0E31\u0E19\u0E2A\u0E34\u0E49\u0E19\u0E2A\u0E38\u0E14",
                                    format: "dd/MM/yyyy",
                                    "auto-apply": "",
                                    "input-class-name": "!bg-white dark:!bg-gray-700 !border-gray-200 dark:!border-gray-600 !rounded-xl !py-3 !text-gray-900 dark:!text-white"
                                  }, null, 8, ["modelValue", "onUpdate:modelValue"])
                                ]),
                                _: 1
                              })
                            ])
                          ])
                        ]),
                        createVNode("div", { class: "flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-700" }, [
                          createVNode("div", null, [
                            createVNode("h4", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34"),
                            createVNode("p", { class: "text-sm text-gray-500" }, "\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E08\u0E30\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49\u0E17\u0E31\u0E19\u0E17\u0E35\u0E42\u0E14\u0E22\u0E44\u0E21\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E23\u0E2D\u0E01\u0E32\u0E23\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34")
                          ]),
                          createVNode("label", { class: "relative inline-flex items-center cursor-pointer" }, [
                            withDirectives(createVNode("input", {
                              type: "checkbox",
                              "onUpdate:modelValue": ($event) => form.value.auto_accept_members = $event,
                              class: "sr-only peer"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelCheckbox, form.value.auto_accept_members]
                            ]),
                            createVNode("div", { class: "w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600" })
                          ])
                        ])
                      ])
                    ]),
                    createVNode("section", null, [
                      createVNode("h2", { class: "text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2" }, [
                        createVNode("span", { class: "w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 flex items-center justify-center text-sm" }, "4"),
                        createTextVNode(" \u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E41\u0E25\u0E30\u0E23\u0E32\u0E04\u0E32 ")
                      ]),
                      createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6" }, [
                        createVNode("div", { class: "flex items-center justify-between mb-4" }, [
                          createVNode("div", null, [
                            createVNode("h4", { class: "font-medium text-gray-900 dark:text-white" }, "\u0E40\u0E1B\u0E34\u0E14\u0E02\u0E32\u0E22\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"),
                            createVNode("p", { class: "text-sm text-gray-500" }, "\u0E40\u0E1B\u0E34\u0E14\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19\u0E2B\u0E32\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E02\u0E32\u0E22\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E19\u0E35\u0E49\u0E43\u0E2B\u0E49\u0E1A\u0E38\u0E04\u0E04\u0E25\u0E17\u0E31\u0E48\u0E27\u0E44\u0E1B")
                          ]),
                          createVNode("label", { class: "relative inline-flex items-center cursor-pointer" }, [
                            withDirectives(createVNode("input", {
                              type: "checkbox",
                              "onUpdate:modelValue": ($event) => form.value.saleable = $event,
                              class: "sr-only peer"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelCheckbox, form.value.saleable]
                            ]),
                            createVNode("div", { class: "w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500" })
                          ])
                        ]),
                        form.value.saleable ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in-down"
                        }, [
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E23\u0E32\u0E04\u0E32\u0E1B\u0E01\u0E15\u0E34"),
                            createVNode("div", { class: "relative" }, [
                              createVNode("div", { class: "absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" }, [
                                createVNode("span", { class: "text-gray-500 font-bold" }, "\u0E3F")
                              ]),
                              withDirectives(createVNode("input", {
                                type: "number",
                                "onUpdate:modelValue": ($event) => form.value.price = $event,
                                min: "0",
                                placeholder: "0.00",
                                class: "block w-full pl-8 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, form.value.price]
                              ])
                            ])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E2A\u0E48\u0E27\u0E19\u0E25\u0E14"),
                            createVNode("div", { class: "flex" }, [
                              createVNode("div", { class: "relative flex-1" }, [
                                withDirectives(createVNode("input", {
                                  type: "number",
                                  "onUpdate:modelValue": ($event) => form.value.discount = $event,
                                  min: "0",
                                  class: "block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-l-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 z-10",
                                  placeholder: "0"
                                }, null, 8, ["onUpdate:modelValue"]), [
                                  [vModelText, form.value.discount]
                                ])
                              ]),
                              withDirectives(createVNode("select", {
                                "onUpdate:modelValue": ($event) => form.value.discount_type = $event,
                                class: "px-3 border-y border-r border-gray-200 dark:border-gray-600 rounded-r-xl bg-gray-50 dark:bg-gray-600 text-gray-700 dark:text-white focus:outline-none"
                              }, [
                                createVNode("option", { value: "fixed" }, "\u0E3F"),
                                createVNode("option", { value: "percent" }, "%")
                              ], 8, ["onUpdate:modelValue"]), [
                                [vModelSelect, form.value.discount_type]
                              ])
                            ])
                          ]),
                          createVNode("div", { class: "md:col-span-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/50 flex justify-between items-center" }, [
                            createVNode("span", { class: "text-emerald-800 dark:text-emerald-100 font-medium" }, "\u0E23\u0E32\u0E04\u0E32\u0E2A\u0E38\u0E17\u0E18\u0E34 (Net Price)"),
                            createVNode("div", { class: "text-right" }, [
                              createVNode("div", { class: "text-2xl font-bold text-emerald-600 dark:text-emerald-400" }, "\u0E3F" + toDisplayString(netPrice.value.toLocaleString()), 1),
                              Number(form.value.discount) > 0 ? (openBlock(), createBlock("div", {
                                key: 0,
                                class: "text-xs text-emerald-600/70"
                              }, " (\u0E25\u0E14 " + toDisplayString(form.value.discount_type === "percent" ? form.value.discount + "%" : "\u0E3F" + form.value.discount) + ") ", 1)) : createCommentVNode("", true)
                            ])
                          ])
                        ])) : createCommentVNode("", true),
                        createVNode("div", { class: "border-t border-gray-100 dark:border-gray-700 pt-6" }, [
                          createVNode("label", { class: "block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" }, "\u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 (Point)"),
                          createVNode("div", { class: "relative max-w-sm" }, [
                            createVNode("div", { class: "absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" }, [
                              createVNode(unref(Icon), {
                                icon: "noto:coin",
                                class: "h-5 w-5"
                              })
                            ]),
                            withDirectives(createVNode("input", {
                              type: "number",
                              "onUpdate:modelValue": ($event) => form.value.tuition_fees = $event,
                              min: "0",
                              placeholder: "0",
                              class: "block w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, form.value.tuition_fees]
                            ])
                          ]),
                          createVNode("p", { class: "text-xs text-gray-500 mt-1" }, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E1C\u0E39\u0E49\u0E40\u0E23\u0E35\u0E22\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 (0 = \u0E1F\u0E23\u0E35)")
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "flex items-center justify-end gap-4 pt-4 pb-12" }, [
                      createVNode("button", {
                        type: "button",
                        onClick: ($event) => unref(router).visit("/learn/courses"),
                        class: "px-6 py-3 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                      }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
                      createVNode("button", {
                        type: "submit",
                        class: "px-8 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold shadow-lg shadow-violet-200 dark:shadow-none transition-all transform hover:-translate-y-0.5"
                      }, " \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32 ")
                    ])
                  ])
                ], 32)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Course/CreateNewCourse.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CreateNewCourse-CaBxYSM2.mjs.map
