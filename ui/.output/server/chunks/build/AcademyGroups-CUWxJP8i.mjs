import { ref, computed, mergeProps, unref, withCtx, defineComponent, createVNode, createBlock, createCommentVNode, createTextVNode, openBlock, Fragment, renderList, Teleport, withModifiers, withDirectives, vModelText, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderTeleport, ssrRenderAttr, ssrRenderClass, ssrInterpolate, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { p as useRoute, i as useApi, n as navigateTo } from './server.mjs';
import _sfc_main$2 from './AcademyLayout-CV3k8mo2.mjs';
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
import './AcademyCoverProfile-C2_VR89F.mjs';
import './AcademyNavbarTab-BFhMwTIK.mjs';

const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "AcademyGroupCard",
  __ssrInlineRender: true,
  props: {
    group: {},
    isAcademyAdmin: { type: Boolean, default: false },
    loading: { type: Boolean, default: false }
  },
  emits: ["edit", "delete", "click", "join"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const groupColors = {
      "department": "from-indigo-400 to-purple-500",
      "classroom": "from-cyan-400 to-blue-500",
      "club": "from-green-400 to-teal-500"
    };
    const groupColor = computed(() => {
      return groupColors[props.group.type] || "from-purple-400 to-pink-500";
    });
    const typeLabels = {
      "department": "\u0E1D\u0E48\u0E32\u0E22\u0E07\u0E32\u0E19",
      "classroom": "\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19",
      "club": "\u0E0A\u0E21\u0E23\u0E21"
    };
    const typeLabel = computed(() => {
      return typeLabels[props.group.type] || "\u0E01\u0E25\u0E38\u0E48\u0E21";
    });
    const typeIcons = {
      "department": "heroicons:briefcase-solid",
      "classroom": "heroicons:academic-cap-solid",
      "club": "heroicons:star-solid"
    };
    const typeIcon = computed(() => {
      return typeIcons[props.group.type] || "heroicons:user-group-solid";
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "group/card relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:scale-[1.02] cursor-pointer" }, _attrs))}><div class="${ssrRenderClass([unref(groupColor), "relative h-24 bg-gradient-to-br flex items-center justify-center"])}"><div class="absolute top-3 left-3 flex items-center gap-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full px-3 py-1 shadow-lg">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: unref(typeIcon),
        class: ["w-4 h-4", `text-${__props.group.type === "department" ? "indigo" : __props.group.type === "classroom" ? "cyan" : "green"}-600`]
      }, null, _parent));
      _push(`<span class="text-xs font-semibold text-gray-700 dark:text-gray-200">${ssrInterpolate(unref(typeLabel))}</span></div>`);
      if (__props.isAcademyAdmin) {
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
        _push(`<!---->`);
      }
      _push(`</div><div class="relative px-5 py-4"><div class="flex justify-center -mt-12 mb-3"><div class="relative"><div class="absolute inset-0 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 rounded-2xl blur-lg opacity-50 group-hover/card:opacity-70 transition-opacity"></div><div class="relative w-20 h-20 rounded-2xl border-4 border-white dark:border-gray-800 bg-white dark:bg-gray-700 overflow-hidden shadow-xl"><div class="${ssrRenderClass(`w-full h-full flex items-center justify-center bg-gradient-to-br ${unref(groupColor)}`)}">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: unref(typeIcon),
        class: "w-8 h-8 text-white"
      }, null, _parent));
      _push(`</div></div></div></div><h3 class="text-center text-lg font-bold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover/card:text-purple-600 dark:group-hover/card:text-purple-400 transition-colors">${ssrInterpolate(__props.group.name)}</h3>`);
      if (__props.group.description) {
        _push(`<p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">${ssrInterpolate(__props.group.description)}</p>`);
      } else {
        _push(`<div class="mb-4"></div>`);
      }
      _push(`<div class="flex items-center justify-center gap-6 mb-4"><div class="text-center"><div class="text-xl font-black text-gray-900 dark:text-white">${ssrInterpolate(__props.group.members_count || 0)}</div><div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"> \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </div></div></div><button class="w-full py-2.5 bg-gradient-to-r from-purple-500 via-blue-500 to-purple-600 hover:from-purple-600 hover:via-blue-600 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 group/btn">`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "heroicons:eye-solid",
        class: "w-5 h-5 group-hover/btn:scale-110 transition-transform"
      }, null, _parent));
      _push(`<span>\u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14</span></button></div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/academy/AcademyGroupCard.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const _sfc_main = {
  __name: "AcademyGroups",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const api = useApi();
    const academy = ref(null);
    const isAcademyAdmin = ref(false);
    const isLoadingAcademy = ref(true);
    const groups = ref([]);
    const isLoading = ref(false);
    const showCreateModal = ref(false);
    const newGroup = ref({
      name: "",
      description: "",
      type: "classroom"
    });
    computed(() => route.query.academyId || route.params.academyId);
    computed(() => route.params.name);
    const createGroup = async () => {
      var _a, _b, _c;
      const id = ((_a = academy.value) == null ? void 0 : _a.id) || ((_c = (_b = academy.value) == null ? void 0 : _b.data) == null ? void 0 : _c.id);
      if (!id) return;
      try {
        const response = await api.post(`/api/academies/${id}/groups`, newGroup.value);
        if (response.success) {
          groups.value.push(response.group);
          showCreateModal.value = false;
          newGroup.value = { name: "", description: "", type: "classroom" };
          Swal.fire({
            icon: "success",
            title: "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
            text: "\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48\u0E16\u0E39\u0E01\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
            timer: 2e3,
            showConfirmButton: false
          });
        }
      } catch (error) {
        console.error("Error creating group:", error);
        Swal.fire({
          icon: "error",
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E44\u0E14\u0E49 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07"
        });
      }
    };
    const deleteGroup = async (groupId) => {
      const result = await Swal.fire({
        icon: "warning",
        title: "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A",
        text: "\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48? \u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E19\u0E35\u0E49\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01\u0E44\u0E14\u0E49",
        showCancelButton: true,
        confirmButtonText: "\u0E25\u0E1A",
        cancelButtonText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
        confirmButtonColor: "#dc2626"
      });
      if (result.isConfirmed) {
        try {
          const response = await api.delete(`/api/academies/groups/${groupId}`);
          if (response.success) {
            groups.value = groups.value.filter((g) => g.id !== groupId);
            Swal.fire({
              icon: "success",
              title: "\u0E25\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
              timer: 2e3,
              showConfirmButton: false
            });
          }
        } catch (error) {
          console.error("Error deleting group:", error);
        }
      }
    };
    const handleGroupClick = (group) => {
      navigateTo(`/Learn/Academy/GroupDetail?groupId=${group.id}`);
    };
    return (_ctx, _push, _parent, _attrs) => {
      if (isLoadingAcademy.value) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex justify-center items-center h-screen" }, _attrs))}>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "svg-spinners:ring-resize",
          class: "w-12 h-12 text-purple-500"
        }, null, _parent));
        _push(`</div>`);
      } else if (!academy.value) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "flex flex-col justify-center items-center h-screen gap-4" }, _attrs))}>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "heroicons:exclamation-circle",
          class: "w-16 h-16 text-red-400"
        }, null, _parent));
        _push(`<p class="text-gray-600 dark:text-gray-300 text-lg">\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E44\u0E14\u0E49</p><button class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg"> \u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </button></div>`);
      } else {
        _push(ssrRenderComponent(_sfc_main$2, mergeProps({
          academy: academy.value,
          isAcademyAdmin: isAcademyAdmin.value
        }, _attrs), {
          academyContent: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="mt-4"${_scopeId}><div class="flex items-center justify-between mb-6"${_scopeId}><h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:user-group-solid",
                class: "w-7 h-7 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 </h2>`);
              if (isAcademyAdmin.value) {
                _push2(`<button class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:plus-circle-solid",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 </button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (isLoading.value) {
                _push2(`<div class="flex justify-center items-center h-[40vh]"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "svg-spinners:ring-resize",
                  class: "w-12 h-12 text-purple-500"
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (groups.value.length > 0) {
                _push2(`<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"${_scopeId}><!--[-->`);
                ssrRenderList(groups.value, (group) => {
                  _push2(ssrRenderComponent(_sfc_main$1, {
                    key: group.id,
                    group,
                    isAcademyAdmin: isAcademyAdmin.value,
                    onClick: handleGroupClick,
                    onDelete: deleteGroup
                  }, null, _parent2, _scopeId));
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="flex flex-col justify-center items-center h-[40vh] text-center"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-group",
                  class: "w-20 h-20 text-gray-300 dark:text-gray-600 mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-500 dark:text-gray-400 text-lg mb-2"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49</p>`);
                if (isAcademyAdmin.value) {
                  _push2(`<p class="text-gray-400 dark:text-gray-500 text-sm"${_scopeId}>\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21 &quot;\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48&quot; \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19</p>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              }
              ssrRenderTeleport(_push2, (_push3) => {
                if (showCreateModal.value) {
                  _push3(`<div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"${_scopeId}><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"${_scopeId}><div class="bg-gradient-to-r from-purple-500 to-blue-500 px-6 py-4"${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2"${_scopeId}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "heroicons:plus-circle-solid",
                    class: "w-6 h-6"
                  }, null, _parent2, _scopeId));
                  _push3(` \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 </h3></div><div class="p-6 space-y-4"${_scopeId}><div${_scopeId}><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E25\u0E38\u0E48\u0E21 </label><input${ssrRenderAttr("value", newGroup.value.name)} type="text" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="\u0E40\u0E0A\u0E48\u0E19 \u0E1D\u0E48\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E01\u0E32\u0E23, \u0E2B\u0E49\u0E2D\u0E07 \u0E21.4/1"${_scopeId}></div><div${_scopeId}><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E01\u0E25\u0E38\u0E48\u0E21 </label><div class="grid grid-cols-3 gap-2"${_scopeId}><button class="${ssrRenderClass([
                    "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                    newGroup.value.type === "department" ? "border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30" : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                  ])}"${_scopeId}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "heroicons:briefcase-solid",
                    class: "w-6 h-6 text-indigo-500 mb-1"
                  }, null, _parent2, _scopeId));
                  _push3(`<span class="text-xs font-medium"${_scopeId}>\u0E1D\u0E48\u0E32\u0E22\u0E07\u0E32\u0E19</span></button><button class="${ssrRenderClass([
                    "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                    newGroup.value.type === "classroom" ? "border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30" : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                  ])}"${_scopeId}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "heroicons:academic-cap-solid",
                    class: "w-6 h-6 text-cyan-500 mb-1"
                  }, null, _parent2, _scopeId));
                  _push3(`<span class="text-xs font-medium"${_scopeId}>\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</span></button><button class="${ssrRenderClass([
                    "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                    newGroup.value.type === "club" ? "border-green-500 bg-green-50 dark:bg-green-900/30" : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                  ])}"${_scopeId}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "heroicons:star-solid",
                    class: "w-6 h-6 text-green-500 mb-1"
                  }, null, _parent2, _scopeId));
                  _push3(`<span class="text-xs font-medium"${_scopeId}>\u0E0A\u0E21\u0E23\u0E21</span></button></div></div><div${_scopeId}><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) </label><textarea rows="3" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none" placeholder="\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49..."${_scopeId}>${ssrInterpolate(newGroup.value.description)}</textarea></div></div><div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3"${_scopeId}><button class="px-4 py-2 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(!newGroup.value.name) ? " disabled" : ""} class="px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition-all"${_scopeId}> \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21 </button></div></div></div>`);
                } else {
                  _push3(`<!---->`);
                }
              }, "body", false, _parent2);
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "mt-4" }, [
                  createVNode("div", { class: "flex items-center justify-between mb-6" }, [
                    createVNode("h2", { class: "text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2" }, [
                      createVNode(unref(Icon), {
                        icon: "heroicons:user-group-solid",
                        class: "w-7 h-7 text-purple-500"
                      }),
                      createTextVNode(" \u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19 ")
                    ]),
                    isAcademyAdmin.value ? (openBlock(), createBlock("button", {
                      key: 0,
                      onClick: ($event) => showCreateModal.value = true,
                      class: "flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "heroicons:plus-circle-solid",
                        class: "w-5 h-5"
                      }),
                      createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 ")
                    ], 8, ["onClick"])) : createCommentVNode("", true)
                  ]),
                  isLoading.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "flex justify-center items-center h-[40vh]"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "svg-spinners:ring-resize",
                      class: "w-12 h-12 text-purple-500"
                    })
                  ])) : groups.value.length > 0 ? (openBlock(), createBlock("div", {
                    key: 1,
                    class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(groups.value, (group) => {
                      return openBlock(), createBlock(_sfc_main$1, {
                        key: group.id,
                        group,
                        isAcademyAdmin: isAcademyAdmin.value,
                        onClick: handleGroupClick,
                        onDelete: deleteGroup
                      }, null, 8, ["group", "isAcademyAdmin"]);
                    }), 128))
                  ])) : (openBlock(), createBlock("div", {
                    key: 2,
                    class: "flex flex-col justify-center items-center h-[40vh] text-center"
                  }, [
                    createVNode(unref(Icon), {
                      icon: "heroicons:user-group",
                      class: "w-20 h-20 text-gray-300 dark:text-gray-600 mb-4"
                    }),
                    createVNode("p", { class: "text-gray-500 dark:text-gray-400 text-lg mb-2" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E19\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E19\u0E35\u0E49"),
                    isAcademyAdmin.value ? (openBlock(), createBlock("p", {
                      key: 0,
                      class: "text-gray-400 dark:text-gray-500 text-sm"
                    }, '\u0E01\u0E14\u0E1B\u0E38\u0E48\u0E21 "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48" \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E15\u0E49\u0E19')) : createCommentVNode("", true)
                  ])),
                  (openBlock(), createBlock(Teleport, { to: "body" }, [
                    showCreateModal.value ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50",
                      onClick: withModifiers(($event) => showCreateModal.value = false, ["self"])
                    }, [
                      createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" }, [
                        createVNode("div", { class: "bg-gradient-to-r from-purple-500 to-blue-500 px-6 py-4" }, [
                          createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                            createVNode(unref(Icon), {
                              icon: "heroicons:plus-circle-solid",
                              class: "w-6 h-6"
                            }),
                            createTextVNode(" \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21\u0E43\u0E2B\u0E21\u0E48 ")
                          ])
                        ]),
                        createVNode("div", { class: "p-6 space-y-4" }, [
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" }, " \u0E0A\u0E37\u0E48\u0E2D\u0E01\u0E25\u0E38\u0E48\u0E21 "),
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => newGroup.value.name = $event,
                              type: "text",
                              class: "w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all",
                              placeholder: "\u0E40\u0E0A\u0E48\u0E19 \u0E1D\u0E48\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E01\u0E32\u0E23, \u0E2B\u0E49\u0E2D\u0E07 \u0E21.4/1"
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, newGroup.value.name]
                            ])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" }, " \u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17\u0E01\u0E25\u0E38\u0E48\u0E21 "),
                            createVNode("div", { class: "grid grid-cols-3 gap-2" }, [
                              createVNode("button", {
                                onClick: ($event) => newGroup.value.type = "department",
                                class: [
                                  "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                                  newGroup.value.type === "department" ? "border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30" : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                                ]
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "heroicons:briefcase-solid",
                                  class: "w-6 h-6 text-indigo-500 mb-1"
                                }),
                                createVNode("span", { class: "text-xs font-medium" }, "\u0E1D\u0E48\u0E32\u0E22\u0E07\u0E32\u0E19")
                              ], 10, ["onClick"]),
                              createVNode("button", {
                                onClick: ($event) => newGroup.value.type = "classroom",
                                class: [
                                  "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                                  newGroup.value.type === "classroom" ? "border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30" : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                                ]
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "heroicons:academic-cap-solid",
                                  class: "w-6 h-6 text-cyan-500 mb-1"
                                }),
                                createVNode("span", { class: "text-xs font-medium" }, "\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19")
                              ], 10, ["onClick"]),
                              createVNode("button", {
                                onClick: ($event) => newGroup.value.type = "club",
                                class: [
                                  "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                                  newGroup.value.type === "club" ? "border-green-500 bg-green-50 dark:bg-green-900/30" : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                                ]
                              }, [
                                createVNode(unref(Icon), {
                                  icon: "heroicons:star-solid",
                                  class: "w-6 h-6 text-green-500 mb-1"
                                }),
                                createVNode("span", { class: "text-xs font-medium" }, "\u0E0A\u0E21\u0E23\u0E21")
                              ], 10, ["onClick"])
                            ])
                          ]),
                          createVNode("div", null, [
                            createVNode("label", { class: "block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" }, " \u0E04\u0E33\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22 (\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A) "),
                            withDirectives(createVNode("textarea", {
                              "onUpdate:modelValue": ($event) => newGroup.value.description = $event,
                              rows: "3",
                              class: "w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none",
                              placeholder: "\u0E2D\u0E18\u0E34\u0E1A\u0E32\u0E22\u0E40\u0E01\u0E35\u0E48\u0E22\u0E27\u0E01\u0E31\u0E1A\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49..."
                            }, null, 8, ["onUpdate:modelValue"]), [
                              [vModelText, newGroup.value.description]
                            ])
                          ])
                        ]),
                        createVNode("div", { class: "px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3" }, [
                          createVNode("button", {
                            onClick: ($event) => showCreateModal.value = false,
                            class: "px-4 py-2 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                          }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
                          createVNode("button", {
                            onClick: createGroup,
                            disabled: !newGroup.value.name,
                            class: "px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                          }, " \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E25\u0E38\u0E48\u0E21 ", 8, ["disabled"])
                        ])
                      ])
                    ], 8, ["onClick"])) : createCommentVNode("", true)
                  ]))
                ])
              ];
            }
          }),
          _: 1
        }, _parent));
      }
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/AcademyGroups.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=AcademyGroups-CUWxJP8i.mjs.map
