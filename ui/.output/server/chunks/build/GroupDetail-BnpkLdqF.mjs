import { ref, computed, mergeProps, withCtx, unref, createVNode, createBlock, createCommentVNode, openBlock, Fragment, toDisplayString, createTextVNode, renderList, Teleport, withModifiers, withDirectives, vModelText, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderClass, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrRenderTeleport, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import _sfc_main$1 from './AcademyLayout-CHtGHy8-.mjs';
import { a as useNuxtApp } from './server.mjs';
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

const _sfc_main = {
  __name: "GroupDetail",
  __ssrInlineRender: true,
  props: {
    academy: Object,
    isAcademyAdmin: Boolean,
    groupId: [String, Number]
  },
  setup(__props) {
    const props = __props;
    const group = ref(null);
    const members = ref([]);
    const isLoading = ref(true);
    const showAddMemberModal = ref(false);
    const searchQuery = ref("");
    const searchResults = ref([]);
    const isSearching = ref(false);
    const selectedUser = ref(null);
    const selectedRole = ref("student");
    const { $api } = useNuxtApp();
    const searchUsers = async () => {
      if (!searchQuery.value || searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
      }
      try {
        isSearching.value = true;
        const response = await $api(`/api/users/search?q=${searchQuery.value}`);
        if (response.success) {
          const memberIds = members.value.map((m) => m.id);
          searchResults.value = response.users.filter((u) => !memberIds.includes(u.id));
        }
      } catch (error) {
        console.error("Error searching users:", error);
      } finally {
        isSearching.value = false;
      }
    };
    const addMember = async () => {
      if (!selectedUser.value) return;
      try {
        const response = await $api(`/academies/groups/${props.groupId}/members`, {
          method: "POST",
          body: {
            user_id: selectedUser.value.id,
            role: selectedRole.value
          }
        });
        if (response.success) {
          members.value.push({
            ...selectedUser.value,
            pivot: { role: selectedRole.value }
          });
          showAddMemberModal.value = false;
          selectedUser.value = null;
          searchQuery.value = "";
          searchResults.value = [];
          Swal.fire({
            icon: "success",
            title: "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
            timer: 2e3,
            showConfirmButton: false
          });
        }
      } catch (error) {
        console.error("Error adding member:", error);
        Swal.fire({
          icon: "error",
          title: "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
          text: error.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E44\u0E14\u0E49"
        });
      }
    };
    const removeMember = async (userId) => {
      const result = await Swal.fire({
        icon: "warning",
        title: "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A",
        text: "\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E04\u0E19\u0E19\u0E35\u0E49\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E01\u0E25\u0E38\u0E48\u0E21\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?",
        showCancelButton: true,
        confirmButtonText: "\u0E25\u0E1A",
        cancelButtonText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
        confirmButtonColor: "#dc2626"
      });
      if (result.isConfirmed) {
        try {
          const response = await $api(`/academies/groups/${props.groupId}/members`, {
            method: "DELETE",
            body: { user_id: userId }
          });
          if (response.success) {
            members.value = members.value.filter((m) => m.id !== userId);
            Swal.fire({
              icon: "success",
              title: "\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
              timer: 2e3,
              showConfirmButton: false
            });
          }
        } catch (error) {
          console.error("Error removing member:", error);
        }
      }
    };
    const updateRole = async (userId, newRole) => {
      try {
        const response = await $api(`/academies/groups/${props.groupId}/members/role`, {
          method: "PATCH",
          body: {
            user_id: userId,
            role: newRole
          }
        });
        if (response.success) {
          const member = members.value.find((m) => m.id === userId);
          if (member) {
            member.pivot.role = newRole;
          }
          Swal.fire({
            icon: "success",
            title: "\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E15\u0E1A\u0E17\u0E1A\u0E32\u0E17\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08",
            timer: 2e3,
            showConfirmButton: false
          });
        }
      } catch (error) {
        console.error("Error updating role:", error);
      }
    };
    const typeInfo = computed(() => {
      var _a;
      const types = {
        department: { label: "\u0E1D\u0E48\u0E32\u0E22\u0E07\u0E32\u0E19", icon: "heroicons:briefcase-solid", color: "indigo" },
        classroom: { label: "\u0E2B\u0E49\u0E2D\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19", icon: "heroicons:academic-cap-solid", color: "cyan" },
        club: { label: "\u0E0A\u0E21\u0E23\u0E21", icon: "heroicons:star-solid", color: "green" }
      };
      return types[(_a = group.value) == null ? void 0 : _a.type] || types.classroom;
    });
    const roleInfo = {
      student: { label: "\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19", color: "blue" },
      teacher: { label: "\u0E04\u0E23\u0E39", color: "purple" },
      admin: { label: "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25", color: "red" }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({
        academy: __props.academy,
        isAcademyAdmin: __props.isAcademyAdmin
      }, _attrs), {
        academyContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mt-4"${_scopeId}>`);
            if (isLoading.value) {
              _push2(`<div class="flex justify-center items-center h-[40vh]"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "svg-spinners:ring-resize",
                class: "w-12 h-12 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else if (group.value) {
              _push2(`<!--[--><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6"${_scopeId}><div class="${ssrRenderClass([`from-${typeInfo.value.color}-400 to-${typeInfo.value.color}-600`, "h-32 bg-gradient-to-br flex items-center justify-center"])}"${_scopeId}><div class="text-center text-white"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: typeInfo.value.icon,
                class: "w-12 h-12 mx-auto mb-2"
              }, null, _parent2, _scopeId));
              _push2(`<span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full"${_scopeId}>${ssrInterpolate(typeInfo.value.label)}</span></div></div><div class="p-6"${_scopeId}><h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"${_scopeId}>${ssrInterpolate(group.value.name)}</h1>`);
              if (group.value.description) {
                _push2(`<p class="text-gray-500 dark:text-gray-400"${_scopeId}>${ssrInterpolate(group.value.description)}</p>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="flex items-center gap-4 mt-4"${_scopeId}><div class="flex items-center gap-2 text-gray-600 dark:text-gray-300"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:users-solid",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span${_scopeId}>${ssrInterpolate(members.value.length)} \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></div></div></div></div><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6"${_scopeId}><div class="flex items-center justify-between mb-6"${_scopeId}><h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:users-solid",
                class: "w-6 h-6 text-purple-500"
              }, null, _parent2, _scopeId));
              _push2(` \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21 </h2>`);
              if (__props.isAcademyAdmin) {
                _push2(`<button class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-plus-solid",
                  class: "w-5 h-5"
                }, null, _parent2, _scopeId));
                _push2(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </button>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div>`);
              if (members.value.length > 0) {
                _push2(`<div class="space-y-3"${_scopeId}><!--[-->`);
                ssrRenderList(members.value, (member) => {
                  var _a, _b, _c, _d, _e, _f;
                  _push2(`<div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"${_scopeId}><div class="flex items-center gap-4"${_scopeId}><img${ssrRenderAttr("src", member.profile_photo_url || "/images/default-avatar.png")}${ssrRenderAttr("alt", member.name)} class="w-12 h-12 rounded-full object-cover"${_scopeId}><div${_scopeId}><h3 class="font-semibold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(member.name)}</h3><span class="${ssrRenderClass([`bg-${roleInfo[((_a = member.pivot) == null ? void 0 : _a.role) || "student"].color}-100 text-${roleInfo[((_b = member.pivot) == null ? void 0 : _b.role) || "student"].color}-700 dark:bg-${roleInfo[((_c = member.pivot) == null ? void 0 : _c.role) || "student"].color}-900/30 dark:text-${roleInfo[((_d = member.pivot) == null ? void 0 : _d.role) || "student"].color}-300`, "text-xs px-2 py-0.5 rounded-full"])}"${_scopeId}>${ssrInterpolate(roleInfo[((_e = member.pivot) == null ? void 0 : _e.role) || "student"].label)}</span></div></div>`);
                  if (__props.isAcademyAdmin) {
                    _push2(`<div class="flex items-center gap-2"${_scopeId}><select${ssrRenderAttr("value", ((_f = member.pivot) == null ? void 0 : _f.role) || "student")} class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"${_scopeId}><option value="student"${_scopeId}>\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19</option><option value="teacher"${_scopeId}>\u0E04\u0E23\u0E39</option><option value="admin"${_scopeId}>\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25</option></select><button class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01"${_scopeId}>`);
                    _push2(ssrRenderComponent(unref(Icon), {
                      icon: "heroicons:trash-solid",
                      class: "w-5 h-5"
                    }, null, _parent2, _scopeId));
                    _push2(`</button></div>`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`</div>`);
                });
                _push2(`<!--]--></div>`);
              } else {
                _push2(`<div class="text-center py-12"${_scopeId}>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:users",
                  class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
                }, null, _parent2, _scopeId));
                _push2(`<p class="text-gray-500 dark:text-gray-400"${_scopeId}>\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49</p></div>`);
              }
              _push2(`</div><!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            ssrRenderTeleport(_push2, (_push3) => {
              if (showAddMemberModal.value) {
                _push3(`<div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"${_scopeId}><div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden"${_scopeId}><div class="bg-gradient-to-r from-purple-500 to-blue-500 px-6 py-4"${_scopeId}><h3 class="text-xl font-bold text-white flex items-center gap-2"${_scopeId}>`);
                _push3(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:user-plus-solid",
                  class: "w-6 h-6"
                }, null, _parent2, _scopeId));
                _push3(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </h3></div><div class="p-6 space-y-4"${_scopeId}><div${_scopeId}><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 </label><div class="relative"${_scopeId}><input${ssrRenderAttr("value", searchQuery.value)} type="text" class="w-full px-4 py-3 pl-10 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49..."${_scopeId}>`);
                _push3(ssrRenderComponent(unref(Icon), {
                  icon: "heroicons:magnifying-glass-solid",
                  class: "w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                }, null, _parent2, _scopeId));
                _push3(`</div>`);
                if (searchResults.value.length > 0) {
                  _push3(`<div class="mt-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-xl"${_scopeId}><!--[-->`);
                  ssrRenderList(searchResults.value, (user) => {
                    _push3(`<button class="w-full flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"${_scopeId}><img${ssrRenderAttr("src", user.profile_photo_url || "/images/default-avatar.png")}${ssrRenderAttr("alt", user.name)} class="w-10 h-10 rounded-full object-cover"${_scopeId}><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(user.name)}</span></button>`);
                  });
                  _push3(`<!--]--></div>`);
                } else {
                  _push3(`<!---->`);
                }
                _push3(`</div>`);
                if (selectedUser.value) {
                  _push3(`<div class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl"${_scopeId}><img${ssrRenderAttr("src", selectedUser.value.profile_photo_url || "/images/default-avatar.png")}${ssrRenderAttr("alt", selectedUser.value.name)} class="w-10 h-10 rounded-full object-cover"${_scopeId}><span class="font-medium text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(selectedUser.value.name)}</span><button class="ml-auto p-1 text-gray-400 hover:text-gray-600"${_scopeId}>`);
                  _push3(ssrRenderComponent(unref(Icon), {
                    icon: "heroicons:x-mark-solid",
                    class: "w-5 h-5"
                  }, null, _parent2, _scopeId));
                  _push3(`</button></div>`);
                } else {
                  _push3(`<!---->`);
                }
                _push3(`<div${_scopeId}><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2"${_scopeId}> \u0E1A\u0E17\u0E1A\u0E32\u0E17 </label><div class="grid grid-cols-3 gap-2"${_scopeId}><!--[-->`);
                ssrRenderList(roleInfo, (info, role) => {
                  _push3(`<button class="${ssrRenderClass([
                    "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                    selectedRole.value === role ? `border-${info.color}-500 bg-${info.color}-50 dark:bg-${info.color}-900/30` : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                  ])}"${_scopeId}><span class="text-sm font-medium"${_scopeId}>${ssrInterpolate(info.label)}</span></button>`);
                });
                _push3(`<!--]--></div></div></div><div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3"${_scopeId}><button class="px-4 py-2 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button${ssrIncludeBooleanAttr(!selectedUser.value) ? " disabled" : ""} class="px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition-all"${_scopeId}> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </button></div></div></div>`);
              } else {
                _push3(`<!---->`);
              }
            }, "body", false, _parent2);
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "mt-4" }, [
                isLoading.value ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "flex justify-center items-center h-[40vh]"
                }, [
                  createVNode(unref(Icon), {
                    icon: "svg-spinners:ring-resize",
                    class: "w-12 h-12 text-purple-500"
                  })
                ])) : group.value ? (openBlock(), createBlock(Fragment, { key: 1 }, [
                  createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6" }, [
                    createVNode("div", {
                      class: ["h-32 bg-gradient-to-br flex items-center justify-center", `from-${typeInfo.value.color}-400 to-${typeInfo.value.color}-600`]
                    }, [
                      createVNode("div", { class: "text-center text-white" }, [
                        createVNode(unref(Icon), {
                          icon: typeInfo.value.icon,
                          class: "w-12 h-12 mx-auto mb-2"
                        }, null, 8, ["icon"]),
                        createVNode("span", { class: "text-sm font-medium bg-white/20 px-3 py-1 rounded-full" }, toDisplayString(typeInfo.value.label), 1)
                      ])
                    ], 2),
                    createVNode("div", { class: "p-6" }, [
                      createVNode("h1", { class: "text-2xl font-bold text-gray-900 dark:text-white mb-2" }, toDisplayString(group.value.name), 1),
                      group.value.description ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "text-gray-500 dark:text-gray-400"
                      }, toDisplayString(group.value.description), 1)) : createCommentVNode("", true),
                      createVNode("div", { class: "flex items-center gap-4 mt-4" }, [
                        createVNode("div", { class: "flex items-center gap-2 text-gray-600 dark:text-gray-300" }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:users-solid",
                            class: "w-5 h-5"
                          }),
                          createVNode("span", null, toDisplayString(members.value.length) + " \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01", 1)
                        ])
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6" }, [
                    createVNode("div", { class: "flex items-center justify-between mb-6" }, [
                      createVNode("h2", { class: "text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2" }, [
                        createVNode(unref(Icon), {
                          icon: "heroicons:users-solid",
                          class: "w-6 h-6 text-purple-500"
                        }),
                        createTextVNode(" \u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21 ")
                      ]),
                      __props.isAcademyAdmin ? (openBlock(), createBlock("button", {
                        key: 0,
                        onClick: ($event) => showAddMemberModal.value = true,
                        class: "flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
                      }, [
                        createVNode(unref(Icon), {
                          icon: "heroicons:user-plus-solid",
                          class: "w-5 h-5"
                        }),
                        createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 ")
                      ], 8, ["onClick"])) : createCommentVNode("", true)
                    ]),
                    members.value.length > 0 ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "space-y-3"
                    }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(members.value, (member) => {
                        var _a, _b, _c, _d, _e, _f;
                        return openBlock(), createBlock("div", {
                          key: member.id,
                          class: "flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        }, [
                          createVNode("div", { class: "flex items-center gap-4" }, [
                            createVNode("img", {
                              src: member.profile_photo_url || "/images/default-avatar.png",
                              alt: member.name,
                              class: "w-12 h-12 rounded-full object-cover"
                            }, null, 8, ["src", "alt"]),
                            createVNode("div", null, [
                              createVNode("h3", { class: "font-semibold text-gray-900 dark:text-white" }, toDisplayString(member.name), 1),
                              createVNode("span", {
                                class: ["text-xs px-2 py-0.5 rounded-full", `bg-${roleInfo[((_a = member.pivot) == null ? void 0 : _a.role) || "student"].color}-100 text-${roleInfo[((_b = member.pivot) == null ? void 0 : _b.role) || "student"].color}-700 dark:bg-${roleInfo[((_c = member.pivot) == null ? void 0 : _c.role) || "student"].color}-900/30 dark:text-${roleInfo[((_d = member.pivot) == null ? void 0 : _d.role) || "student"].color}-300`]
                              }, toDisplayString(roleInfo[((_e = member.pivot) == null ? void 0 : _e.role) || "student"].label), 3)
                            ])
                          ]),
                          __props.isAcademyAdmin ? (openBlock(), createBlock("div", {
                            key: 0,
                            class: "flex items-center gap-2"
                          }, [
                            createVNode("select", {
                              value: ((_f = member.pivot) == null ? void 0 : _f.role) || "student",
                              onChange: ($event) => updateRole(member.id, $event.target.value),
                              class: "text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            }, [
                              createVNode("option", { value: "student" }, "\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19"),
                              createVNode("option", { value: "teacher" }, "\u0E04\u0E23\u0E39"),
                              createVNode("option", { value: "admin" }, "\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25")
                            ], 40, ["value", "onChange"]),
                            createVNode("button", {
                              onClick: ($event) => removeMember(member.id),
                              class: "p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors",
                              title: "\u0E25\u0E1A\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01"
                            }, [
                              createVNode(unref(Icon), {
                                icon: "heroicons:trash-solid",
                                class: "w-5 h-5"
                              })
                            ], 8, ["onClick"])
                          ])) : createCommentVNode("", true)
                        ]);
                      }), 128))
                    ])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "text-center py-12"
                    }, [
                      createVNode(unref(Icon), {
                        icon: "heroicons:users",
                        class: "w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"
                      }),
                      createVNode("p", { class: "text-gray-500 dark:text-gray-400" }, "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E43\u0E19\u0E01\u0E25\u0E38\u0E48\u0E21\u0E19\u0E35\u0E49")
                    ]))
                  ])
                ], 64)) : createCommentVNode("", true),
                (openBlock(), createBlock(Teleport, { to: "body" }, [
                  showAddMemberModal.value ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50",
                    onClick: withModifiers(($event) => showAddMemberModal.value = false, ["self"])
                  }, [
                    createVNode("div", { class: "bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden" }, [
                      createVNode("div", { class: "bg-gradient-to-r from-purple-500 to-blue-500 px-6 py-4" }, [
                        createVNode("h3", { class: "text-xl font-bold text-white flex items-center gap-2" }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:user-plus-solid",
                            class: "w-6 h-6"
                          }),
                          createTextVNode(" \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 ")
                        ])
                      ]),
                      createVNode("div", { class: "p-6 space-y-4" }, [
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" }, " \u0E04\u0E49\u0E19\u0E2B\u0E32\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49 "),
                          createVNode("div", { class: "relative" }, [
                            withDirectives(createVNode("input", {
                              "onUpdate:modelValue": ($event) => searchQuery.value = $event,
                              onInput: searchUsers,
                              type: "text",
                              class: "w-full px-4 py-3 pl-10 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent",
                              placeholder: "\u0E1E\u0E34\u0E21\u0E1E\u0E4C\u0E0A\u0E37\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49..."
                            }, null, 40, ["onUpdate:modelValue"]), [
                              [vModelText, searchQuery.value]
                            ]),
                            createVNode(unref(Icon), {
                              icon: "heroicons:magnifying-glass-solid",
                              class: "w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                            })
                          ]),
                          searchResults.value.length > 0 ? (openBlock(), createBlock("div", {
                            key: 0,
                            class: "mt-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-xl"
                          }, [
                            (openBlock(true), createBlock(Fragment, null, renderList(searchResults.value, (user) => {
                              return openBlock(), createBlock("button", {
                                key: user.id,
                                onClick: ($event) => {
                                  selectedUser.value = user;
                                  searchQuery.value = user.name;
                                  searchResults.value = [];
                                },
                                class: "w-full flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                              }, [
                                createVNode("img", {
                                  src: user.profile_photo_url || "/images/default-avatar.png",
                                  alt: user.name,
                                  class: "w-10 h-10 rounded-full object-cover"
                                }, null, 8, ["src", "alt"]),
                                createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(user.name), 1)
                              ], 8, ["onClick"]);
                            }), 128))
                          ])) : createCommentVNode("", true)
                        ]),
                        selectedUser.value ? (openBlock(), createBlock("div", {
                          key: 0,
                          class: "flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl"
                        }, [
                          createVNode("img", {
                            src: selectedUser.value.profile_photo_url || "/images/default-avatar.png",
                            alt: selectedUser.value.name,
                            class: "w-10 h-10 rounded-full object-cover"
                          }, null, 8, ["src", "alt"]),
                          createVNode("span", { class: "font-medium text-gray-900 dark:text-white" }, toDisplayString(selectedUser.value.name), 1),
                          createVNode("button", {
                            onClick: ($event) => {
                              selectedUser.value = null;
                              searchQuery.value = "";
                            },
                            class: "ml-auto p-1 text-gray-400 hover:text-gray-600"
                          }, [
                            createVNode(unref(Icon), {
                              icon: "heroicons:x-mark-solid",
                              class: "w-5 h-5"
                            })
                          ], 8, ["onClick"])
                        ])) : createCommentVNode("", true),
                        createVNode("div", null, [
                          createVNode("label", { class: "block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" }, " \u0E1A\u0E17\u0E1A\u0E32\u0E17 "),
                          createVNode("div", { class: "grid grid-cols-3 gap-2" }, [
                            (openBlock(), createBlock(Fragment, null, renderList(roleInfo, (info, role) => {
                              return createVNode("button", {
                                key: role,
                                onClick: ($event) => selectedRole.value = role,
                                class: [
                                  "flex flex-col items-center p-3 rounded-xl border-2 transition-all",
                                  selectedRole.value === role ? `border-${info.color}-500 bg-${info.color}-50 dark:bg-${info.color}-900/30` : "border-gray-200 dark:border-gray-600 hover:border-gray-300"
                                ]
                              }, [
                                createVNode("span", { class: "text-sm font-medium" }, toDisplayString(info.label), 1)
                              ], 10, ["onClick"]);
                            }), 64))
                          ])
                        ])
                      ]),
                      createVNode("div", { class: "px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3" }, [
                        createVNode("button", {
                          onClick: ($event) => showAddMemberModal.value = false,
                          class: "px-4 py-2 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ", 8, ["onClick"]),
                        createVNode("button", {
                          onClick: addMember,
                          disabled: !selectedUser.value,
                          class: "px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        }, " \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 ", 8, ["disabled"])
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
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/GroupDetail.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=GroupDetail-BnpkLdqF.mjs.map
