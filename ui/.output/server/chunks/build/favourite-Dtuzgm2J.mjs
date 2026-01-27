import { C as CourseCard } from './CourseCard-DKRWjEJn.mjs';
import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { withAsyncContext, computed, mergeProps, unref, withCtx, createVNode, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList } from 'vue/server-renderer';
import axios from 'axios';
import { Icon } from '@iconify/vue';
import { u as useAsyncData } from './asyncData-BSaJWK3Z.mjs';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const courseService = {
  /**
   * ดึงข้อมูลรายวิชาเดียว
   */
  async getCourse(courseId) {
    const response = await axios.get(`/api/courses/${courseId}`);
    return response.data;
  },
  /**
   * ดึงรายการรายวิชา
   */
  async getCourses(params = {}) {
    const response = await axios.get("/api/courses", { params });
    return response.data;
  },
  /**
   * Get favorite courses
   */
  async getFavoriteCourses(params = {}) {
    const response = await axios.get("/api/courses/favorites", { params });
    return response.data;
  },
  /**
   * สร้างรายวิชาใหม่
   */
  async createCourse(data) {
    const response = await axios.post("/api/courses", data);
    return response.data;
  },
  /**
   * อัพเดทข้อมูลรายวิชา
   */
  async updateCourse(courseId, data) {
    const response = await axios.patch(`/api/courses/${courseId}`, data);
    return response.data;
  },
  /**
   * ลบรายวิชา
   */
  async deleteCourse(courseId) {
    const response = await axios.delete(`/api/courses/${courseId}`);
    return response.data;
  },
  /**
   * Toggle favorite status
   */
  async toggleFavorite(courseId) {
    const response = await axios.post(`/api/courses/${courseId}/favorite`);
    return response.data;
  },
  /**
   * อัพเดทรูปปก
   */
  async updateCourseCover(courseId, file) {
    const formData = new FormData();
    formData.append("cover", file);
    formData.append("_method", "PATCH");
    const response = await axios.post(
      `/courses/${courseId}`,
      formData,
      {
        headers: {
          "Content-Type": "multipart/form-data"
        }
      }
    );
    return response.data;
  },
  /**
   * อัพเดทโลโก้
   */
  async updateCourseLogo(courseId, file) {
    const formData = new FormData();
    formData.append("logo", file);
    formData.append("_method", "PATCH");
    const response = await axios.post(
      `/courses/${courseId}`,
      formData,
      {
        headers: {
          "Content-Type": "multipart/form-data"
        }
      }
    );
    return response.data;
  },
  /**
   * อัพเดทหัวข้อรายวิชา
   */
  async updateCourseHeader(courseId, header) {
    const response = await axios.patch(`/courses/${courseId}`, {
      name: header
    });
    return response.data;
  },
  /**
   * อัพเดทรหัสวิชา
   */
  async updateCourseSubheader(courseId, subheader) {
    const response = await axios.patch(`/courses/${courseId}`, {
      code: subheader
    });
    return response.data;
  },
  // ============= Member Management =============
  /**
   * ขอเป็นสมาชิกรายวิชา
   */
  async requestMembership(courseId, groupId = null) {
    const response = await axios.post(`/courses/${courseId}/members`, {
      group_id: groupId
    });
    return response.data;
  },
  /**
   * ยกเลิกสมาชิก / ออกจากรายวิชา
   */
  async cancelMembership(courseId, memberId) {
    const response = await axios.delete(`/courses/${courseId}/members/${memberId}`);
    return response.data;
  },
  /**
   * ดึงรายชื่อสมาชิกในรายวิชา
   */
  async getCourseMembers(courseId, params = {}) {
    const response = await axios.get(`/courses/${courseId}/members`, { params });
    return response.data;
  },
  /**
   * อัพเดทสถานะสมาชิก (สำหรับ Admin)
   */
  async updateMemberStatus(courseId, memberId, status) {
    const response = await axios.patch(
      `/courses/${courseId}/members/${memberId}`,
      { status }
    );
    return response.data;
  },
  // ============= Groups =============
  /**
   * ดึงกลุ่มในรายวิชา
   */
  async getCourseGroups(courseId) {
    const response = await axios.get(`/courses/${courseId}/groups`);
    return response.data;
  },
  /**
   * สร้างกลุ่มใหม่
   */
  async createCourseGroup(courseId, data) {
    const response = await axios.post(`/courses/${courseId}/groups`, data);
    return response.data;
  },
  /**
   * อัพเดทกลุ่ม
   */
  async updateCourseGroup(courseId, groupId, data) {
    const response = await axios.patch(
      `/courses/${courseId}/groups/${groupId}`,
      data
    );
    return response.data;
  },
  /**
   * ลบกลุ่ม
   */
  async deleteCourseGroup(courseId, groupId) {
    const response = await axios.delete(`/courses/${courseId}/groups/${groupId}`);
    return response.data;
  }
};
const _sfc_main = {
  __name: "favourite",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const { data, pending, refresh } = ([__temp, __restore] = withAsyncContext(() => useAsyncData(
      "favoriteCourses",
      () => courseService.getFavoriteCourses()
    )), __temp = await __temp, __restore(), __temp);
    const courses = computed(() => {
      var _a;
      return ((_a = data.value) == null ? void 0 : _a.data) || [];
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_CourseCard = CourseCard;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gray-50 dark:bg-gray-900 pb-12" }, _attrs))}><div class="container mx-auto px-4 py-8"><div class="flex items-center justify-between mb-8"><div class="flex items-center gap-3"><div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:heart-24-filled",
        class: "text-red-500 w-6 h-6"
      }, null, _parent));
      _push(`</div><div><h1 class="text-2xl font-bold text-gray-900 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E42\u0E1B\u0E23\u0E14\u0E02\u0E2D\u0E07\u0E09\u0E31\u0E19</h1><p class="text-sm text-gray-500 dark:text-gray-400">\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E44\u0E27\u0E49\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E14\u0E39\u0E20\u0E32\u0E22\u0E2B\u0E25\u0E31\u0E07</p></div></div></div>`);
      if (unref(pending)) {
        _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"><!--[-->`);
        ssrRenderList(4, (i) => {
          _push(`<div class="bg-white dark:bg-gray-800 rounded-xl h-[340px] animate-pulse shadow-sm p-4"><div class="h-40 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else if (unref(courses).length > 0) {
        _push(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"><!--[-->`);
        ssrRenderList(unref(courses), (course, index) => {
          _push(ssrRenderComponent(_component_CourseCard, {
            key: course.id,
            course,
            index,
            class: "h-full"
          }, null, _parent));
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700"><div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 dark:bg-red-900/10 rounded-full mb-4">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:heart-broken-24-regular",
          class: "w-8 h-8 text-red-500/50"
        }, null, _parent));
        _push(`</div><h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E43\u0E19\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E42\u0E1B\u0E23\u0E14</h3><p class="text-gray-500 text-sm mb-6 text-center max-w-sm"> \u0E01\u0E14\u0E2B\u0E31\u0E27\u0E43\u0E08\u0E17\u0E35\u0E48\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E2A\u0E19\u0E43\u0E08\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E01\u0E47\u0E1A\u0E44\u0E27\u0E49\u0E14\u0E39\u0E20\u0E32\u0E22\u0E2B\u0E25\u0E31\u0E07 </p>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/courses",
          class: "inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium shadow-lg shadow-blue-500/30"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), { icon: "fluent:compass-northwest-20-filled" }, null, _parent2, _scopeId));
              _push2(` \u0E2A\u0E33\u0E23\u0E27\u0E08\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19 `);
            } else {
              return [
                createVNode(unref(Icon), { icon: "fluent:compass-northwest-20-filled" }),
                createTextVNode(" \u0E2A\u0E33\u0E23\u0E27\u0E08\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/favourite.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=favourite-Dtuzgm2J.mjs.map
