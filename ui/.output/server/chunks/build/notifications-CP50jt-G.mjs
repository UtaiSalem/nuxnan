import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, ref, computed, withCtx, createTextVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrRenderClass, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { f as useHead } from './server.mjs';
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

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "notifications",
  __ssrInlineRender: true,
  setup(__props) {
    const activeTab = ref("All");
    const tabs = ["All", "Likes", "Comments", "Follows"];
    const notifications = ref([
      {
        id: 1,
        type: "like",
        user: "Sarah Anderson",
        avatar: "https://i.pravatar.cc/150?img=1",
        message: "liked your post",
        time: "5m ago",
        read: false
      },
      {
        id: 2,
        type: "comment",
        user: "John Smith",
        avatar: "https://i.pravatar.cc/150?img=2",
        message: "commented on your post",
        time: "1h ago",
        read: false
      },
      {
        id: 3,
        type: "follow",
        user: "Emily Chen",
        avatar: "https://i.pravatar.cc/150?img=3",
        message: "started following you",
        time: "2h ago",
        read: false
      },
      {
        id: 4,
        type: "like",
        user: "Michael Brown",
        avatar: "https://i.pravatar.cc/150?img=4",
        message: "liked your photo",
        time: "1d ago",
        read: true
      },
      {
        id: 5,
        type: "comment",
        user: "Jessica Lee",
        avatar: "https://i.pravatar.cc/150?img=5",
        message: "replied to your comment",
        time: "2d ago",
        read: true
      }
    ]);
    const filteredNotifications = computed(() => {
      if (activeTab.value === "All") return notifications.value;
      return notifications.value.filter((n) => {
        if (activeTab.value === "Likes") return n.type === "like";
        if (activeTab.value === "Comments") return n.type === "comment";
        if (activeTab.value === "Follows") return n.type === "follow";
        return true;
      });
    });
    const getNotificationIcon = (type) => {
      const icons = {
        like: "mdi:heart",
        comment: "mdi:comment",
        follow: "mdi:account-plus"
      };
      return icons[type] || "mdi:bell";
    };
    const getNotificationColor = (type) => {
      const colors = {
        like: "text-red-500",
        comment: "text-blue-500",
        follow: "text-green-500"
      };
      return colors[type] || "text-gray-500";
    };
    useHead({
      title: "Notifications"
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(_attrs)}><section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4"><div class="container mx-auto px-4"><h1 class="text-2xl font-bold mb-1">Notifications</h1><div class="flex items-center gap-2 text-sm text-primary-100">`);
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
      _push(`<span>Notifications</span></div></div></section><div class="container mx-auto px-4 pb-6"><div class="bg-white rounded-xl shadow-sm overflow-hidden"><div class="flex border-b border-secondary-200"><!--[-->`);
      ssrRenderList(tabs, (tab) => {
        _push(`<button class="${ssrRenderClass([unref(activeTab) === tab ? "text-primary-600" : "text-secondary-600", "flex-1 px-4 py-3 font-medium transition-colors relative"])}">${ssrInterpolate(tab)} `);
        if (unref(activeTab) === tab) {
          _push(`<div class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600"></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</button>`);
      });
      _push(`<!--]--></div><div class="divide-y divide-secondary-200"><!--[-->`);
      ssrRenderList(unref(filteredNotifications), (notification) => {
        _push(`<div class="${ssrRenderClass([{ "bg-primary-50/50": !notification.read }, "p-4 hover:bg-gray-50 transition-colors"])}"><div class="flex items-start gap-3"><img${ssrRenderAttr("src", notification.avatar)}${ssrRenderAttr("alt", notification.user)} class="w-12 h-12 rounded-full object-cover flex-shrink-0"><div class="flex-1 min-w-0"><p class="text-sm text-secondary-700 mb-1"><span class="font-semibold">${ssrInterpolate(notification.user)}</span> ${ssrInterpolate(notification.message)}</p><span class="text-xs text-secondary-500">${ssrInterpolate(notification.time)}</span></div>`);
        _push(ssrRenderComponent(unref(Icon), {
          name: getNotificationIcon(notification.type),
          class: ["w-6 h-6 flex-shrink-0", getNotificationColor(notification.type)]
        }, null, _parent));
        _push(`</div></div>`);
      });
      _push(`<!--]--></div></div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/notifications.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=notifications-CP50jt-G.mjs.map
