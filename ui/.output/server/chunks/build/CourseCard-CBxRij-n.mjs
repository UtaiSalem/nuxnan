import { defineComponent, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderClass, ssrInterpolate, ssrRenderComponent, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, u as useRouter, b as useRuntimeConfig } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "CourseCard",
  __ssrInlineRender: true,
  props: {
    course: {},
    index: {}
  },
  emits: ["loading-page"],
  setup(__props, { emit: __emit }) {
    useRouter();
    const config = useRuntimeConfig();
    const getCoverUrl = (course) => {
      if (course.cover) {
        if (course.cover.startsWith("http")) {
          return course.cover;
        }
        return `${config.public.apiBase}/storage/images/courses/covers/${course.cover}`;
      }
      return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
    };
    const getInstructorAvatar = (course) => {
      var _a;
      return ((_a = course.user) == null ? void 0 : _a.avatar) || "/images/default-avatar.png";
    };
    const formatPrice = (price) => {
      return new Intl.NumberFormat("th-TH").format(price || 0);
    };
    const getBadgeType = (course, index) => {
      if (index === void 0) return null;
      if (course.enrolled_students > 50) return "bestseller";
      if (index < 3) return "trending";
      return null;
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all cursor-pointer group h-full flex flex-col" }, _attrs))} data-v-4e52d0f3><div class="relative h-44 overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0" data-v-4e52d0f3><img${ssrRenderAttr("src", getCoverUrl(__props.course))}${ssrRenderAttr("alt", __props.course.name)} class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-v-4e52d0f3>`);
      if (getBadgeType(__props.course, (_a = __props.index) != null ? _a : 0)) {
        _push(`<div class="${ssrRenderClass([
          "absolute top-3 left-3 px-3 py-1 text-white text-xs font-bold rounded shadow-lg",
          getBadgeType(__props.course, (_b = __props.index) != null ? _b : 0) === "bestseller" ? "bg-blue-500" : "bg-orange-500"
        ])}" data-v-4e52d0f3>${ssrInterpolate(getBadgeType(__props.course, (_c = __props.index) != null ? _c : 0) === "bestseller" ? "Best Seller" : "Trending")}</div>`);
      } else {
        _push(`<!---->`);
      }
      if (__props.course.rating) {
        _push(`<div class="absolute bottom-3 left-3 px-2 py-1 bg-yellow-500 text-gray-900 rounded text-xs font-bold flex items-center gap-1" data-v-4e52d0f3>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-16-filled",
          class: "w-3 h-3"
        }, null, _parent));
        _push(`<span data-v-4e52d0f3>${ssrInterpolate(typeof __props.course.rating === "number" ? __props.course.rating.toFixed(1) : __props.course.rating)}</span>`);
        if (__props.course.reviews_count) {
          _push(`<span class="text-gray-700" data-v-4e52d0f3>(${ssrInterpolate(__props.course.reviews_count)})</span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="p-4 flex flex-col flex-grow" data-v-4e52d0f3><div class="flex items-center justify-between mb-3" data-v-4e52d0f3>`);
      if (__props.course.user) {
        _push(`<div class="flex items-center gap-2" data-v-4e52d0f3><img${ssrRenderAttr("src", getInstructorAvatar(__props.course))}${ssrRenderAttr("alt", __props.course.user.name)} class="w-8 h-8 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600" data-v-4e52d0f3><div class="min-w-0" data-v-4e52d0f3><p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[100px]" data-v-4e52d0f3> By: ${ssrInterpolate(__props.course.user.name)}</p><p class="text-xs text-blue-500 truncate max-w-[100px]" data-v-4e52d0f3>${ssrInterpolate(__props.course.category || "General")}</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-bold" data-v-4e52d0f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "ri:bit-coin-line",
        class: "w-5 h-5 text-yellow-500"
      }, null, _parent));
      _push(`<span data-v-4e52d0f3>${ssrInterpolate(formatPrice(__props.course.price))}</span></div></div><h3 class="text-gray-800 dark:text-white font-bold mb-3 line-clamp-2 group-hover:text-blue-500 transition-colors flex-grow" data-v-4e52d0f3>${ssrInterpolate(__props.course.name)}</h3><div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700 mt-auto" data-v-4e52d0f3><div class="flex items-center gap-1" data-v-4e52d0f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-open-16-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span data-v-4e52d0f3>${ssrInterpolate(__props.course.lessons_count || 20)} Lectures</span></div><div class="flex items-center gap-1" data-v-4e52d0f3>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clock-16-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span data-v-4e52d0f3>${ssrInterpolate(__props.course.hours || 20)}Hrs</span></div></div>`);
      if (__props.course.isMember) {
        _push(`<div class="mt-3" data-v-4e52d0f3><div class="space-y-2" data-v-4e52d0f3><div class="flex items-center justify-between text-xs font-medium" data-v-4e52d0f3><span class="text-green-600 dark:text-green-400 flex items-center gap-1" data-v-4e52d0f3>`);
        _push(ssrRenderComponent(unref(Icon), { icon: "fluent:hat-graduation-16-filled" }, null, _parent));
        _push(` Student </span><span class="text-gray-500" data-v-4e52d0f3>${ssrInterpolate(Math.round(__props.course.auth_progress || 0))}%</span></div><div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden" data-v-4e52d0f3><div class="h-full bg-green-500 rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${__props.course.auth_progress || 0}%` })}" data-v-4e52d0f3></div></div></div></div>`);
      } else {
        _push(`<button class="mt-3 w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 group-hover:bg-blue-700" data-v-4e52d0f3> View Details `);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:arrow-right-16-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button>`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/CourseCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CourseCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-4e52d0f3"]]);

export { CourseCard as C };
//# sourceMappingURL=CourseCard-CBxRij-n.mjs.map
