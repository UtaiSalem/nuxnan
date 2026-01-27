import { defineComponent, ref, watch, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrRenderClass, ssrInterpolate, ssrRenderComponent, ssrRenderStyle } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc, u as useRouter, i as useApi, b as useRuntimeConfig } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "CourseCard",
  __ssrInlineRender: true,
  props: {
    course: {},
    index: {}
  },
  setup(__props) {
    const props = __props;
    useRouter();
    const config = useRuntimeConfig();
    useApi();
    const isFavorited = ref(props.course.is_favorited);
    const isLoadingFavorite = ref(false);
    watch(() => props.course.is_favorited, (newVal) => {
      isFavorited.value = newVal;
    });
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all cursor-pointer group" }, _attrs))} data-v-cea28b1c><div class="relative h-44 overflow-hidden bg-gray-100 dark:bg-gray-700" data-v-cea28b1c><img${ssrRenderAttr("src", getCoverUrl(__props.course))}${ssrRenderAttr("alt", __props.course.name)} class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" data-v-cea28b1c>`);
      if (getBadgeType(__props.course, (_a = __props.index) != null ? _a : 0)) {
        _push(`<div class="${ssrRenderClass([
          "absolute top-3 left-3 px-3 py-1 text-white text-xs font-bold rounded shadow-lg",
          getBadgeType(__props.course, (_b = __props.index) != null ? _b : 0) === "bestseller" ? "bg-blue-500" : "bg-orange-500"
        ])}" data-v-cea28b1c>${ssrInterpolate(getBadgeType(__props.course, (_c = __props.index) != null ? _c : 0) === "bestseller" ? "Best Seller" : "Trending")}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<button class="absolute top-3 right-3 p-2 rounded-full bg-white/80 dark:bg-black/50 hover:bg-white dark:hover:bg-black/70 transition-all text-red-500 z-10 shadow-sm backdrop-blur-sm"${ssrRenderAttr("title", unref(isFavorited) ? "Remove from favorites" : "Add to favorites")} data-v-cea28b1c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: unref(isFavorited) ? "fluent:heart-24-filled" : "fluent:heart-24-regular",
        class: ["w-5 h-5 transition-transform active:scale-95", { "animate-pulse": unref(isLoadingFavorite) }]
      }, null, _parent));
      _push(`</button><div class="absolute bottom-3 left-3 px-2 py-1 bg-yellow-500 text-gray-900 rounded text-xs font-bold flex items-center gap-1" data-v-cea28b1c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:star-16-filled",
        class: "w-3 h-3"
      }, null, _parent));
      _push(`<span data-v-cea28b1c>${ssrInterpolate(__props.course.rating || "4.5")}</span></div></div><div class="p-4" data-v-cea28b1c><div class="flex items-center justify-between mb-3" data-v-cea28b1c>`);
      if (__props.course.user) {
        _push(`<div class="flex items-center gap-2" data-v-cea28b1c><img${ssrRenderAttr("src", getInstructorAvatar(__props.course))}${ssrRenderAttr("alt", __props.course.user.name)} class="w-8 h-8 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600" data-v-cea28b1c><div class="min-w-0" data-v-cea28b1c><p class="text-xs text-gray-500 dark:text-gray-400" data-v-cea28b1c> By: ${ssrInterpolate(__props.course.user.name)}</p><p class="text-xs text-blue-500 truncate" data-v-cea28b1c>${ssrInterpolate(__props.course.category || "General")}</p></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-bold" data-v-cea28b1c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "ri:bit-coin-line",
        class: "w-5 h-5 text-yellow-500"
      }, null, _parent));
      _push(`<span data-v-cea28b1c>${ssrInterpolate(formatPrice(__props.course.price))}</span></div></div><h3 class="text-gray-800 dark:text-white font-bold mb-3 line-clamp-2 group-hover:text-blue-500 transition-colors" data-v-cea28b1c>${ssrInterpolate(__props.course.name)}</h3><div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700" data-v-cea28b1c><div class="flex items-center gap-1" data-v-cea28b1c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-open-16-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span data-v-cea28b1c>${ssrInterpolate(__props.course.lessons_count || 20)} Lectures</span></div><div class="flex items-center gap-1" data-v-cea28b1c>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:clock-16-regular",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span data-v-cea28b1c>${ssrInterpolate(__props.course.hours || 20)}Hrs</span></div></div>`);
      if (__props.course.isMember) {
        _push(`<div class="mt-3" data-v-cea28b1c>`);
        if (__props.course.auth_role == 4) {
          _push(`<div class="flex items-center justify-center gap-2 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-sm font-bold" data-v-cea28b1c>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:shield-person-20-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push(` Admin </div>`);
        } else {
          _push(`<div class="space-y-2" data-v-cea28b1c><div class="flex items-center justify-between text-xs font-medium" data-v-cea28b1c><span class="text-green-600 dark:text-green-400 flex items-center gap-1" data-v-cea28b1c>`);
          _push(ssrRenderComponent(unref(Icon), { icon: "fluent:hat-graduation-16-filled" }, null, _parent));
          _push(` Student </span><span class="text-gray-500" data-v-cea28b1c>${ssrInterpolate(Math.round(__props.course.auth_progress || 0))}%</span></div><div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden" data-v-cea28b1c><div class="h-full bg-green-500 rounded-full transition-all duration-500" style="${ssrRenderStyle({ width: `${__props.course.auth_progress || 0}%` })}" data-v-cea28b1c></div></div></div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<button class="mt-3 w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 group-hover:bg-blue-700" data-v-cea28b1c> View Details `);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/CourseCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CourseCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-cea28b1c"]]);

export { CourseCard as C };
//# sourceMappingURL=CourseCard-DKRWjEJn.mjs.map
