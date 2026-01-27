import { computed, toRef, mergeProps, withCtx, createVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderClass, ssrRenderAttr, ssrInterpolate, ssrRenderStyle } from 'vue/server-renderer';
import { a as usePage } from './inertia-vue3-CWdJjaLG.mjs';
import { Icon } from '@iconify/vue';
import { _ as _export_sfc } from './server.mjs';

function useMemberProgress(member, totalScore) {
  const percentage = computed(() => {
    var _a;
    if (!totalScore.value || totalScore.value === 0) return 0;
    const score = ((_a = member.value) == null ? void 0 : _a.achieved_score) || 0;
    return Math.round(score / totalScore.value * 100);
  });
  const progressStatus = computed(() => {
    const p = percentage.value;
    if (p >= 80) return "excellent";
    if (p >= 50) return "good";
    return "fair";
  });
  const statusMessage = computed(() => {
    switch (progressStatus.value) {
      case "excellent":
        return "\u0E22\u0E2D\u0E14\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21";
      case "good":
        return "\u0E14\u0E35";
      default:
        return "\u0E1E\u0E2D\u0E43\u0E0A\u0E49";
    }
  });
  const statusIcon = computed(() => {
    switch (progressStatus.value) {
      case "excellent":
        return "fluent:ribbon-star-20-filled";
      case "good":
        return "fluent:thumb-like-20-filled";
      default:
        return "fluent:battery-3-20-filled";
    }
  });
  const progressColor = computed(() => {
    switch (progressStatus.value) {
      case "excellent":
        return {
          ring: "ring-green-500",
          bg: "bg-green-500",
          text: "text-green-600",
          gradient: "from-green-400 to-green-600"
        };
      case "good":
        return {
          ring: "ring-yellow-400",
          bg: "bg-yellow-400",
          text: "text-yellow-600",
          gradient: "from-yellow-300 to-yellow-500"
        };
      case "fair":
      default:
        return {
          ring: "ring-orange-500",
          bg: "bg-orange-500",
          text: "text-orange-600",
          gradient: "from-orange-400 to-orange-600"
        };
    }
  });
  const remainingScore = computed(() => {
    var _a;
    const achieved = ((_a = member.value) == null ? void 0 : _a.achieved_score) || 0;
    const total = totalScore.value || 0;
    return Math.max(0, total - achieved);
  });
  const remainingPercentage = computed(() => {
    return Math.max(0, 100 - percentage.value);
  });
  const progressBarStyle = computed(() => {
    return {
      width: `${percentage.value}%`
    };
  });
  return {
    percentage,
    progressStatus,
    progressColor,
    statusMessage,
    statusIcon,
    remainingScore,
    remainingPercentage,
    progressBarStyle
  };
}
const _sfc_main$1 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "relative inline-block text-left" }, _attrs))}><button class="text-gray-500 hover:text-gray-700"> ... </button></div>`);
}
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/DotsDropdownMenu.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const DotsDropdownMenu = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main = {
  __name: "MemberCard",
  __ssrInlineRender: true,
  props: {
    member: {
      type: Object,
      required: true,
      default: () => ({})
    },
    dataIndex: {
      type: Number,
      default: 0
    }
  },
  emits: ["request-unmember-course"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const page = computed(() => usePage().props);
    const courseTotalScore = computed(() => {
      var _a, _b;
      return ((_b = (_a = page.value.course) == null ? void 0 : _a.data) == null ? void 0 : _b.total_score) || 100;
    });
    const isCourseAdmin = computed(() => page.value.isCourseAdmin);
    const authUserId = computed(() => {
      var _a, _b;
      return (_b = (_a = page.value.auth) == null ? void 0 : _a.user) == null ? void 0 : _b.id;
    });
    computed(
      () => {
        var _a, _b;
        return ((_b = (_a = props.member) == null ? void 0 : _a.user) == null ? void 0 : _b.id) === authUserId.value || isCourseAdmin.value;
      }
    );
    const memberRef = toRef(props, "member");
    const {
      percentage,
      progressStatus,
      progressColor,
      statusMessage,
      statusIcon
    } = useMemberProgress(memberRef, courseTotalScore);
    const memberDisplayName = computed(
      () => {
        var _a, _b, _c, _d, _e, _f;
        return ((_a = props.member) == null ? void 0 : _a.member_name) || ((_c = (_b = props.member) == null ? void 0 : _b.user) == null ? void 0 : _c.name) || ((_e = (_d = props.member) == null ? void 0 : _d.student) == null ? void 0 : _e.name) || ((_f = props.member) == null ? void 0 : _f.name) || "Unknown";
      }
    );
    const memberOrderNumber = computed(
      () => {
        var _a;
        return ((_a = props.member) == null ? void 0 : _a.order_number) || "#";
      }
    );
    const memberGroupName = computed(
      () => {
        var _a, _b;
        return ((_b = (_a = props.member) == null ? void 0 : _a.group) == null ? void 0 : _b.name) || "-";
      }
    );
    const memberAvatar = computed(
      () => {
        var _a, _b, _c, _d, _e;
        return ((_a = props.member) == null ? void 0 : _a.avatar) || ((_c = (_b = props.member) == null ? void 0 : _b.user) == null ? void 0 : _c.avatar) || ((_e = (_d = props.member) == null ? void 0 : _d.user) == null ? void 0 : _e.profile_photo_url) || "/images/default-avatar.png";
      }
    );
    computed(
      () => {
        var _a, _b, _c;
        return ((_a = props.member) == null ? void 0 : _a.member_code) || ((_c = (_b = props.member) == null ? void 0 : _b.user) == null ? void 0 : _c.email) || "";
      }
    );
    const handleUnmember = () => {
      emit("request-unmember-course", {
        memberId: props.member.id,
        memberName: memberDisplayName.value
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d;
      if (((_a = props.member) == null ? void 0 : _a.role) !== 3) {
        _push(`<li${ssrRenderAttrs(mergeProps({
          class: "group relative bg-white dark:bg-gray-800 hover:bg-gradient-to-br hover:from-white hover:to-indigo-50 dark:hover:from-gray-800 dark:hover:to-gray-700 shadow-lg hover:shadow-xl p-4 rounded-xl w-full mb-3 transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-900",
          "aria-label": `Member card for ${memberDisplayName.value}`,
          role: "article"
        }, _attrs))} data-v-4ac99d98>`);
        if (isCourseAdmin.value) {
          _push(`<div class="absolute top-3 right-3 z-10" data-v-4ac99d98>`);
          _push(ssrRenderComponent(DotsDropdownMenu, { onDeleteModel: handleUnmember }, {
            deleteModel: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<span data-v-4ac99d98${_scopeId}>\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span>`);
              } else {
                return [
                  createVNode("span", null, "\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01")
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="${ssrRenderClass([{ "pr-10": isCourseAdmin.value }, "flex flex-col lg:flex-row gap-4 items-start"])}" data-v-4ac99d98><div class="flex items-center w-full lg:w-64 lg:flex-shrink-0" data-v-4ac99d98><div class="relative flex-shrink-0" data-v-4ac99d98><img class="${ssrRenderClass([[unref(progressColor).ring, `group-hover:${unref(progressColor).ring}`], "w-16 h-16 rounded-full ring-2 transition-all duration-300"])}"${ssrRenderAttr("src", memberAvatar.value)}${ssrRenderAttr("alt", `${memberDisplayName.value}'s avatar`)} loading="lazy" data-v-4ac99d98><div class="${ssrRenderClass([unref(progressColor).bg, "absolute -bottom-1 -right-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-lg"])}" data-v-4ac99d98>${ssrInterpolate(unref(percentage))}% </div></div><div class="ml-4 min-w-0 flex-1" data-v-4ac99d98>`);
        if (isCourseAdmin.value) {
          _push(`<a${ssrRenderAttr("href", `/courses/${(_c = (_b = page.value.course) == null ? void 0 : _b.data) == null ? void 0 : _c.id}/members/${props.member.id}/member-settings`)} target="_blank" rel="noopener noreferrer"${ssrRenderAttr("title", memberDisplayName.value)} class="text-gray-900 dark:text-white font-bold tracking-wide text-lg hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-300 cursor-pointer inline-flex items-center group/link max-w-full" data-v-4ac99d98><span class="truncate" data-v-4ac99d98>${ssrInterpolate(memberDisplayName.value)}</span>`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:arrow-top-right-on-square",
            class: "w-4 h-4 ml-1.5 flex-shrink-0 opacity-0 group-hover/link:opacity-100 transition-opacity duration-200"
          }, null, _parent));
          _push(`</a>`);
        } else {
          _push(`<p${ssrRenderAttr("title", memberDisplayName.value)} class="text-gray-900 dark:text-white font-bold tracking-wide text-lg truncate" data-v-4ac99d98>${ssrInterpolate(memberDisplayName.value)}</p>`);
        }
        _push(`<div class="${ssrRenderClass([unref(progressColor).text, "flex items-center mt-1 mb-2"])}" data-v-4ac99d98>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: unref(statusIcon),
          class: "w-4 h-4 mr-1.5"
        }, null, _parent));
        _push(`<span class="text-xs font-semibold" data-v-4ac99d98>${ssrInterpolate(unref(statusMessage))}</span></div><div class="flex items-center space-x-3 text-sm" data-v-4ac99d98><span class="${ssrRenderClass([memberOrderNumber.value === "#" ? "text-orange-500 dark:text-orange-400 font-bold" : "text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400", "flex items-center transition-colors"])}" data-v-4ac99d98>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:number-symbol-square-20-filled",
          class: "w-5 h-5 mr-1"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-4ac99d98>${ssrInterpolate(memberOrderNumber.value)}</span></span><span class="flex items-center text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" data-v-4ac99d98>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:user-group-solid",
          class: "w-5 h-5 mr-1"
        }, null, _parent));
        _push(`<span class="font-medium" data-v-4ac99d98>${ssrInterpolate(memberGroupName.value)}</span></span></div></div></div><div class="flex flex-col items-start lg:items-end w-full flex-1" data-v-4ac99d98><div class="flex items-center justify-between mb-2 w-full" data-v-4ac99d98><div class="flex items-center" data-v-4ac99d98>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:academic-cap-solid",
          class: "w-5 h-5 text-indigo-500 dark:text-indigo-400 mr-2"
        }, null, _parent));
        _push(`<span class="text-sm font-semibold text-gray-700 dark:text-gray-300" data-v-4ac99d98>\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32</span></div></div><div class="relative w-full bg-gray-100 dark:bg-gray-700 rounded-full h-7 overflow-hidden shadow-inner" data-v-4ac99d98><div class="${ssrRenderClass([unref(progressColor).gradient, "h-full text-xs font-bold text-white flex items-center justify-center rounded-full transition-all duration-500 ease-out relative overflow-hidden bg-gradient-to-r"])}" style="${ssrRenderStyle(`width: ${unref(percentage)}%`)}" role="progressbar"${ssrRenderAttr("aria-valuenow", unref(percentage))} aria-valuemin="0" aria-valuemax="100"${ssrRenderAttr("aria-label", `Progress: ${unref(percentage)}% - ${unref(progressStatus)}`)} data-v-4ac99d98><div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" data-v-4ac99d98></div><span class="relative z-10 font-bold" data-v-4ac99d98>${ssrInterpolate(unref(percentage))}%</span></div>`);
        if (unref(percentage) === 0) {
          _push(`<div class="absolute inset-0 flex items-center justify-center" data-v-4ac99d98><span class="text-xs font-medium text-gray-500" data-v-4ac99d98>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E30\u0E41\u0E19\u0E19</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="flex items-center justify-between w-full mt-2 text-xs" data-v-4ac99d98><div class="text-gray-500 dark:text-gray-400" data-v-4ac99d98><span class="${ssrRenderClass([unref(progressColor).text, "font-medium"])}" data-v-4ac99d98>${ssrInterpolate(((_d = props.member) == null ? void 0 : _d.achieved_score) || 0)}</span><span class="mx-1" data-v-4ac99d98>/</span><span data-v-4ac99d98>${ssrInterpolate(courseTotalScore.value)}</span><span class="ml-1" data-v-4ac99d98>\u0E04\u0E30\u0E41\u0E19\u0E19</span></div></div></div></div></li>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/MemberCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const MemberCard = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-4ac99d98"]]);

export { MemberCard as M };
//# sourceMappingURL=MemberCard-DppXYXxE.mjs.map
