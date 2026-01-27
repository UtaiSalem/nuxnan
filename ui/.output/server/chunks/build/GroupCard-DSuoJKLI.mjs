import { defineComponent, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrRenderStyle, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { u as useCourseMemberStore } from './courseMember-jssI8x6K.mjs';
import { storeToRefs } from 'pinia';
import { b as useRuntimeConfig } from './server.mjs';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "GroupCard",
  __ssrInlineRender: true,
  props: {
    group: {},
    isCourseAdmin: { type: Boolean, default: false },
    courseId: {},
    loading: { type: Boolean, default: false }
  },
  emits: ["edit", "delete", "click", "join"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const config = useRuntimeConfig();
    const groupColors = [
      "from-cyan-400 to-blue-500",
      "from-purple-400 to-pink-500",
      "from-green-400 to-teal-500",
      "from-orange-400 to-red-500",
      "from-indigo-400 to-purple-500",
      "from-yellow-400 to-orange-500"
    ];
    const groupColor = computed(() => {
      const index = props.group.id % groupColors.length;
      return groupColors[index];
    });
    const coverUrl = computed(() => {
      if (props.group.cover) {
        return `${config.public.apiBase}/storage/images/courses/groups/covers/${props.group.cover}`;
      }
      return null;
    });
    const groupImageUrl = computed(() => {
      if (props.group.image_url) {
        return `${config.public.apiBase}/storage/images/courses/groups/${props.group.image_url}`;
      }
      return null;
    });
    const memberAvatars = computed(() => {
      if (!props.group.members || props.group.members.length === 0) return [];
      return props.group.members.slice(0, 5).map((m) => {
        const user = m.user || m.member;
        return {
          name: (user == null ? void 0 : user.name) || "User",
          avatar: (user == null ? void 0 : user.avatar) || "/images/default-avatar.png"
        };
      });
    });
    const remainingMembers = computed(() => {
      const count = (props.group.members_count || 0) - 5;
      return count > 0 ? count : 0;
    });
    const isMember = computed(() => !!props.group.groupMemberOfAuth);
    const courseMemberStore = useCourseMemberStore();
    const { member } = storeToRefs(courseMemberStore);
    const isMemberOfOtherGroup = computed(() => {
      var _a;
      return ((_a = member.value) == null ? void 0 : _a.group_id) && member.value.group_id != props.group.id;
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "group/card relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:scale-[1.02] cursor-pointer" }, _attrs))}><div class="${ssrRenderClass([unref(groupColor), "relative h-32 bg-gradient-to-br flex items-center justify-center"])}" style="${ssrRenderStyle(unref(coverUrl) ? { backgroundImage: `url(${unref(coverUrl)})`, backgroundSize: "cover", backgroundPosition: "center" } : {})}"><div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>`);
      if (__props.isCourseAdmin) {
        _push(`<div class="absolute top-3 right-3 flex items-center gap-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full px-2 py-1 shadow-lg"><button class="p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-full transition-colors" title="\u0E41\u0E01\u0E49\u0E44\u0E02">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:edit-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button><button class="p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors" title="\u0E25\u0E1A">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:delete-24-filled",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button></div>`);
      } else {
        _push(`<div class="absolute top-3 right-3 w-10 h-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:user-group-solid",
          class: "w-5 h-5 text-purple-600"
        }, null, _parent));
        _push(`</div>`);
      }
      _push(`</div><div class="relative px-5 pb-5"><div class="flex justify-center -mt-12 mb-3"><div class="relative"><div class="absolute inset-0 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 rounded-2xl blur-lg opacity-50 group-hover/card:opacity-70 transition-opacity"></div><div class="relative w-24 h-24 rounded-2xl border-4 border-white dark:border-gray-800 bg-white dark:bg-gray-700 overflow-hidden shadow-xl">`);
      if (unref(groupImageUrl)) {
        _push(`<img${ssrRenderAttr("src", unref(groupImageUrl))}${ssrRenderAttr("alt", __props.group.name)} class="w-full h-full object-cover">`);
      } else {
        _push(`<div class="${ssrRenderClass(`w-full h-full flex items-center justify-center bg-gradient-to-br ${unref(groupColor)}`)}"><span class="text-3xl font-bold text-white">${ssrInterpolate(((_a = __props.group.name) == null ? void 0 : _a.charAt(0)) || "G")}</span></div>`);
      }
      _push(`</div></div></div><h3 class="text-center text-xl font-bold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover/card:text-purple-600 dark:group-hover/card:text-purple-400 transition-colors">${ssrInterpolate(__props.group.name)}</h3>`);
      if (__props.group.description) {
        _push(`<p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 uppercase tracking-wide font-medium">${ssrInterpolate(__props.group.description)}</p>`);
      } else {
        _push(`<div class="mb-4"></div>`);
      }
      _push(`<div class="flex items-center justify-center gap-6 mb-5"><div class="text-center"><div class="text-2xl font-black text-gray-900 dark:text-white">${ssrInterpolate(__props.group.members_count || 0)}</div><div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"> Members </div></div><div class="text-center"><div class="text-2xl font-black text-gray-900 dark:text-white">${ssrInterpolate(__props.group.posts_count || 0)}</div><div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"> Posts </div></div><div class="text-center"><div class="text-2xl font-black text-gray-900 dark:text-white">${ssrInterpolate(__props.group.visits_count && __props.group.visits_count >= 1e3 ? (__props.group.visits_count / 1e3).toFixed(1) + "K" : __props.group.visits_count || 0)}</div><div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"> Visits </div></div></div>`);
      if (unref(memberAvatars).length > 0) {
        _push(`<div class="flex items-center justify-center mb-5"><div class="flex -space-x-2"><!--[-->`);
        ssrRenderList(unref(memberAvatars), (member2, index) => {
          _push(`<div class="relative w-10 h-10 rounded-full border-2 border-white dark:border-gray-800 overflow-hidden shadow-md hover:scale-110 hover:z-10 transition-transform"${ssrRenderAttr("title", member2.name)}><img${ssrRenderAttr("src", member2.avatar)}${ssrRenderAttr("alt", member2.name)} class="w-full h-full object-cover"></div>`);
        });
        _push(`<!--]-->`);
        if (unref(remainingMembers) > 0) {
          _push(`<div class="relative w-10 h-10 rounded-full border-2 border-white dark:border-gray-800 bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-md"><span class="text-xs font-bold text-white">+${ssrInterpolate(unref(remainingMembers))}</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (!unref(isMember) && !__props.isCourseAdmin) {
        _push(`<button${ssrIncludeBooleanAttr(__props.loading) ? " disabled" : ""} class="${ssrRenderClass([unref(isMemberOfOtherGroup) ? "from-amber-500 via-orange-600 to-red-600 hover:from-amber-600 hover:via-orange-700 hover:to-red-700" : "from-purple-500 via-blue-500 to-purple-600 hover:from-purple-600 hover:via-blue-600 hover:to-purple-700", "w-full py-3 bg-gradient-to-r text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 group/btn disabled:opacity-70 disabled:cursor-not-allowed"])}">`);
        if (__props.loading) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "svg-spinners:ring-resize",
            class: "w-5 h-5 animate-spin"
          }, null, _parent));
        } else {
          _push(ssrRenderComponent(unref(Icon), {
            icon: unref(isMemberOfOtherGroup) ? "heroicons:arrow-path-rounded-square" : "heroicons:user-plus-solid",
            class: "w-5 h-5 group-hover/btn:scale-110 transition-transform"
          }, null, _parent));
        }
        _push(`<span>${ssrInterpolate(__props.loading ? "Processing..." : unref(isMemberOfOtherGroup) ? "Move Group" : "Join Group!")}</span></button>`);
      } else if (unref(isMember)) {
        _push(`<div class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:check-circle-solid",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>Member</span></div>`);
      } else if (__props.isCourseAdmin) {
        _push(`<button class="w-full py-3 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:cog-6-tooth-solid",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span>Manage</span></button>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/GroupCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=GroupCard-DSuoJKLI.mjs.map
