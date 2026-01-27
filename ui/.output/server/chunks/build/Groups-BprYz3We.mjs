import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { _ as _sfc_main$1 } from './GroupCard-DSuoJKLI.mjs';
import { defineComponent, ref, withCtx, createTextVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead, n as navigateTo } from './server.mjs';
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
import './courseMember-jssI8x6K.mjs';
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "Groups",
  __ssrInlineRender: true,
  setup(__props) {
    const activeFilter = ref("All Groups");
    const filters = ["All Groups", "My Groups", "Joined", "Popular", "New"];
    const groups = ref([
      {
        id: 1,
        name: "Photography Lovers",
        description: "Share your best shots and learn from others",
        cover: "https://picsum.photos/800/400?random=40",
        members_count: 12345,
        posts_count: 234,
        visits_count: 5678,
        groupMemberOfAuth: true,
        members: [],
        image_url: null
      },
      {
        id: 2,
        name: "Fitness & Wellness",
        description: "Motivation and tips for a healthy lifestyle",
        cover: "https://picsum.photos/800/400?random=41",
        members_count: 8765,
        posts_count: 567,
        visits_count: 3456,
        groupMemberOfAuth: false,
        members: [],
        image_url: null
      },
      {
        id: 3,
        name: "Tech Enthusiasts",
        description: "Latest in technology and innovation",
        cover: "https://picsum.photos/800/400?random=42",
        members_count: 23456,
        posts_count: 1234,
        visits_count: 15e3,
        groupMemberOfAuth: true,
        members: [],
        image_url: null
      },
      {
        id: 4,
        name: "Travel Adventures",
        description: "Explore the world together",
        cover: "https://picsum.photos/800/400?random=43",
        members_count: 15678,
        posts_count: 890,
        visits_count: 8901,
        groupMemberOfAuth: false,
        members: [],
        image_url: null
      }
    ]);
    const handleGroupClick = (group) => {
      navigateTo(`/groups/${group.id}`);
    };
    const handleGroupJoin = async (groupId) => {
      console.log("Joining group:", groupId);
      const group = groups.value.find((g) => g.id === groupId);
      if (group) {
        group.groupMemberOfAuth = true;
        group.members_count++;
      }
    };
    useHead({
      title: "Groups"
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      const _component_GroupCard = _sfc_main$1;
      _push(`<div${ssrRenderAttrs(_attrs)}><section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4"><div class="container mx-auto px-4"><h1 class="text-2xl font-bold mb-1">Groups</h1><div class="flex items-center gap-2 text-sm text-primary-100">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "hover:text-white transition-colors"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Home`);
          } else {
            return [
              createTextVNode("Home")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Icon), {
        icon: "mdi:chevron-right",
        class: "w-4 h-4"
      }, null, _parent));
      _push(`<span>Groups</span></div></div></section><div class="container mx-auto px-4 mb-6"><div class="flex gap-2 overflow-x-auto pb-2"><!--[-->`);
      ssrRenderList(filters, (filter) => {
        _push(`<button class="${ssrRenderClass([
          unref(activeFilter) === filter ? "bg-primary-600 text-white" : "bg-white text-secondary-700 hover:bg-gray-100",
          "px-4 py-2 rounded-full font-medium whitespace-nowrap transition-colors"
        ])}">${ssrInterpolate(filter)}</button>`);
      });
      _push(`<!--]--></div></div><div class="container mx-auto px-4 pb-6"><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"><!--[-->`);
      ssrRenderList(unref(groups), (group) => {
        _push(ssrRenderComponent(_component_GroupCard, {
          key: group.id,
          group,
          "course-id": "general",
          onClick: handleGroupClick,
          onJoin: handleGroupJoin
        }, null, _parent));
      });
      _push(`<!--]--></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Play/Groups.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=Groups-BprYz3We.mjs.map
