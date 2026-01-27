import { ref, mergeProps, withCtx, createVNode, createBlock, createCommentVNode, openBlock, Fragment, renderList, unref, createTextVNode, computed, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderList, ssrRenderAttrs, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';
import _sfc_main$7 from './AcademyLayout-CHtGHy8-.mjs';
import { _ as _export_sfc } from './server.mjs';
import { Icon } from '@iconify/vue';
import { L as Link } from './components-DEm4dYEV.mjs';
import 'sweetalert2';
import './main-BqvhuwHD.mjs';
import './nuxt-link-Dhr1c_cd.mjs';
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
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import 'pinia';
import './useGamification-BliN7lma.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import './AcademyCoverProfile-C2_VR89F.mjs';
import './AcademyNavbarTab-BFhMwTIK.mjs';

const _sfc_main$6 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded-lg space-y-4 animate-pulse" }, _attrs))}><div class="flex space-x-4"><div class="rounded-full bg-slate-200 h-10 w-10"></div><div class="flex-1 space-y-2 py-1"><div class="h-4 bg-slate-200 rounded w-3/4"></div><div class="h-4 bg-slate-200 rounded"></div></div></div></div>`);
}
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/accessories/PostLoadingSkeleton.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const PostLoadingSkeleton = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main$5 = {
  __name: "CreateAcademyPost",
  __ssrInlineRender: true,
  props: {
    academy_id: Number
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded mb-4" }, _attrs))}><textarea class="w-full border rounded p-2" placeholder="Write something to the academy..."></textarea><div class="flex justify-end mt-2"><button class="bg-blue-500 text-white px-4 py-2 rounded">Post</button></div></div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/posts/CreateAcademyPost.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const _sfc_main$4 = {
  __name: "AcademyPostViewer",
  __ssrInlineRender: true,
  props: {
    activity: Object
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white p-4 shadow rounded mb-4" }, _attrs))}><div class="flex items-center mb-2"><div class="font-bold">${ssrInterpolate(((_a = __props.activity.actor) == null ? void 0 : _a.name) || "User")}</div></div><p>${ssrInterpolate(((_b = __props.activity.data) == null ? void 0 : _b.content) || "Post content placeholder")}</p></div>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/posts/AcademyPostViewer.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const _sfc_main$3 = {
  __name: "AcademyInfoWidget",
  __ssrInlineRender: true,
  props: {
    academy: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const academyData = computed(() => {
      var _a;
      return ((_a = props.academy) == null ? void 0 : _a.data) || props.academy || {};
    });
    const stats = computed(() => [
      { label: "\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", value: academyData.value.total_students || 0, icon: "heroicons:users" },
      { label: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32", value: academyData.value.courses_offered || 0, icon: "heroicons:academic-cap" }
      // You can add more stats here if available in the academy data
    ]);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50"><h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:information-circle",
        class: "w-5 h-5 text-indigo-500"
      }, null, _parent));
      _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </h3></div><div class="p-5 space-y-4"><div><h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</h4><p class="mt-2 text-gray-700 dark:text-gray-300 text-sm leading-relaxed">${ssrInterpolate(academyData.value.description || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21")}</p></div><div class="grid grid-cols-2 gap-4"><!--[-->`);
      ssrRenderList(stats.value, (stat) => {
        _push(`<div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30"><div class="flex items-center gap-2 mb-1">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: stat.icon,
          class: "w-4 h-4 text-indigo-600 dark:text-indigo-400"
        }, null, _parent));
        _push(`<span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">${ssrInterpolate(stat.label)}</span></div><div class="text-xl font-bold text-indigo-900 dark:text-indigo-100">${ssrInterpolate(stat.value)}</div></div>`);
      });
      _push(`<!--]--></div>`);
      if (academyData.value.address) {
        _push(`<div class="pt-2"><h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">\u0E17\u0E35\u0E48\u0E15\u0E31\u0E49\u0E07\u0E1E\u0E23\u0E30\u0E40\u0E2D\u0E01</h4><div class="flex gap-2 text-sm text-gray-600 dark:text-gray-400 leading-snug">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:map-pin",
          class: "w-4 h-4 shrink-0 text-gray-400 mt-0.5"
        }, null, _parent));
        _push(`<span>${ssrInterpolate(academyData.value.address)}</span></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/widgets/AcademyInfoWidget.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const _sfc_main$2 = {
  __name: "AcademyActivityWidget",
  __ssrInlineRender: true,
  props: {
    academyId: {
      type: [Number, String],
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const activities = ref([]);
    const isLoading = ref(true);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_Link = Link;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden" }, _attrs))}><div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center"><h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-sm">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:bolt",
        class: "w-4 h-4 text-amber-500"
      }, null, _parent));
      _push(` \u0E04\u0E27\u0E32\u0E21\u0E40\u0E04\u0E25\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E2B\u0E27\u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14 </h3>`);
      _push(ssrRenderComponent(_component_Link, {
        href: `/academies/${props.academyId}`,
        class: "text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 `);
          } else {
            return [
              createTextVNode(" \u0E14\u0E39\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="p-3">`);
      if (isLoading.value) {
        _push(`<div class="flex justify-center py-6">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:3-dots-scale",
          class: "w-8 h-8 text-indigo-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (activities.value.length) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(activities.value, (activity) => {
          var _a, _b;
          _push(`<div class="flex gap-3 pb-3 border-b border-gray-50 dark:border-gray-700 last:border-0 last:pb-0"><img${ssrRenderAttr("src", ((_a = activity.user) == null ? void 0 : _a.profile_photo_url) || "/images/default-avatar.png")} class="w-8 h-8 rounded-full border border-gray-100 transition-transform hover:scale-105"><div class="min-w-0 flex-1"><p class="text-xs text-gray-900 dark:text-gray-100 line-clamp-2 leading-snug"><span class="font-bold">${ssrInterpolate((_b = activity.user) == null ? void 0 : _b.name)}</span> ${ssrInterpolate(activity.activity_type_label || "\u0E44\u0E14\u0E49\u0E42\u0E1E\u0E2A\u0E15\u0E4C\u0E02\u0E49\u0E2D\u0E04\u0E27\u0E32\u0E21\u0E43\u0E2B\u0E21\u0E48")}</p><p class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:clock",
            class: "w-3 h-3"
          }, null, _parent));
          _push(` ${ssrInterpolate(activity.created_at_for_humans)}</p></div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="py-10 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:chat-bubble-left",
          class: "w-8 h-8 text-gray-300 mx-auto mb-2"
        }, null, _parent));
        _push(`<p class="text-xs text-gray-500 dark:text-gray-400">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E27\u0E32\u0E21\u0E40\u0E04\u0E25\u0E37\u0E48\u0E2D\u0E19\u0E44\u0E2B\u0E27</p></div>`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/widgets/AcademyActivityWidget.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const _sfc_main$1 = {
  __name: "AcademyAnnouncementWidget",
  __ssrInlineRender: true,
  props: {
    academyId: {
      type: [Number, String],
      required: true
    }
  },
  setup(__props) {
    const announcements = ref([]);
    const isLoading = ref(true);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl shadow-lg border border-indigo-500/20 overflow-hidden text-white" }, _attrs))}><div class="p-4 border-b border-white/10 flex justify-between items-center"><h3 class="font-bold flex items-center gap-2 text-sm uppercase tracking-wider">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:megaphone",
        class: "w-4 h-4 text-amber-300"
      }, null, _parent));
      _push(` \u0E02\u0E48\u0E32\u0E27\u0E1B\u0E23\u0E30\u0E0A\u0E32\u0E2A\u0E31\u0E21\u0E1E\u0E31\u0E19\u0E18\u0E4C </h3><span class="flex h-2 w-2 rounded-full bg-red-400 animate-pulse"></span></div><div class="p-4">`);
      if (isLoading.value) {
        _push(`<div class="flex justify-center py-4">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-6 h-6 text-white/50"
        }, null, _parent));
        _push(`</div>`);
      } else if (announcements.value.length) {
        _push(`<div class="space-y-4"><!--[-->`);
        ssrRenderList(announcements.value, (announcement) => {
          var _a;
          _push(`<div class="group cursor-pointer"><p class="text-[11px] text-indigo-100 font-medium mb-1 flex items-center gap-1 opacity-80">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:calendar-days",
            class: "w-3 h-3"
          }, null, _parent));
          _push(` ${ssrInterpolate(announcement.created_at_for_humans)}</p><p class="text-xs font-semibold leading-relaxed group-hover:text-amber-200 transition-colors line-clamp-2">${ssrInterpolate(((_a = announcement.activityable) == null ? void 0 : _a.description) || "\u0E21\u0E35\u0E01\u0E32\u0E23\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E43\u0E2B\u0E21\u0E48\u0E20\u0E32\u0E22\u0E43\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19")}</p></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="py-6 text-center">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:inbox",
          class: "w-8 h-8 text-white/30 mx-auto mb-2"
        }, null, _parent));
        _push(`<p class="text-xs text-white/60">\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E48\u0E32\u0E27\u0E2A\u0E32\u0E23\u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p></div>`);
      }
      _push(`<button class="w-full mt-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-xs font-bold transition-all text-center"> \u0E14\u0E39\u0E1B\u0E23\u0E30\u0E01\u0E32\u0E28\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14 </button></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/widgets/AcademyAnnouncementWidget.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "ShowAcademy",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    isAcademyAdmin: Boolean,
    activities: Object
  },
  setup(__props) {
    var _a;
    const props = __props;
    const isLoadingAcademyPosts = ref(false);
    const academyActivities = ref(((_a = props.activities) == null ? void 0 : _a.data) || []);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$7, mergeProps({
        academy: __props.academy,
        isAcademyAdmin: __props.isAcademyAdmin
      }, _attrs), {
        leftSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$3, {
              academy: props.academy
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, {
              academyId: props.academy.data.id
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4" }, [
                createVNode(_sfc_main$3, {
                  academy: props.academy
                }, null, 8, ["academy"]),
                createVNode(_sfc_main$1, {
                  academyId: props.academy.data.id
                }, null, 8, ["academyId"])
              ])
            ];
          }
        }),
        academyContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mt-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$5, {
              academy_id: props.academy.data.id
            }, null, _parent2, _scopeId));
            if (isLoadingAcademyPosts.value) {
              _push2(`<div class="mt-4"${_scopeId}>`);
              _push2(ssrRenderComponent(PostLoadingSkeleton, null, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (academyActivities.value.length) {
              _push2(`<div${_scopeId}><!--[-->`);
              ssrRenderList(academyActivities.value, (activity, index) => {
                _push2(`<div${_scopeId}>`);
                _push2(ssrRenderComponent(_sfc_main$4, { activity }, null, _parent2, _scopeId));
                _push2(`</div>`);
              });
              _push2(`<!--]--></div>`);
            } else if (!academyActivities.value.length) {
              _push2(`<div${_scopeId}><div class="flex justify-center items-center h-[50vh]"${_scopeId}><p class="text-gray-500 text-lg"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E43\u0E14\u0E46 \u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49</p></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "mt-4" }, [
                createVNode(_sfc_main$5, {
                  academy_id: props.academy.data.id
                }, null, 8, ["academy_id"]),
                isLoadingAcademyPosts.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mt-4"
                }, [
                  createVNode(PostLoadingSkeleton)
                ])) : academyActivities.value.length ? (openBlock(), createBlock("div", { key: 1 }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(academyActivities.value, (activity, index) => {
                    return openBlock(), createBlock("div", { key: index }, [
                      createVNode(_sfc_main$4, { activity }, null, 8, ["activity"])
                    ]);
                  }), 128))
                ])) : !academyActivities.value.length ? (openBlock(), createBlock("div", { key: 2 }, [
                  createVNode("div", { class: "flex justify-center items-center h-[50vh]" }, [
                    createVNode("p", { class: "text-gray-500 text-lg" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E43\u0E14\u0E46 \u0E43\u0E19\u0E02\u0E13\u0E30\u0E19\u0E35\u0E49")
                  ])
                ])) : createCommentVNode("", true)
              ])
            ];
          }
        }),
        rightSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="space-y-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$2, {
              academyId: props.academy.data.id
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "space-y-4" }, [
                createVNode(_sfc_main$2, {
                  academyId: props.academy.data.id
                }, null, 8, ["academyId"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/ShowAcademy.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=ShowAcademy-Dbz2Lmr3.mjs.map
