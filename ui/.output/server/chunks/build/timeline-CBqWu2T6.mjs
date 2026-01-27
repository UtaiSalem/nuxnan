import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { defineComponent, computed, withCtx, createVNode, unref, useSSRContext } from 'vue';
import { ssrRenderAttr, ssrRenderComponent } from 'vue/server-renderer';
import { p as publicAssetsURL } from '../_/nitro.mjs';
import { _ as _imports_2$1 } from './virtual_public-bm40i66I.mjs';
import { d as useAuthStore } from './server.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _imports_0 = publicAssetsURL("/images/resources/profile-baner.jpg");
const _imports_2 = publicAssetsURL("/images/resources/img-1.jpg");
const _imports_3 = publicAssetsURL("/images/svg/thumb.png");
const _imports_4 = publicAssetsURL("/images/svg/heart.png");
const _imports_5 = publicAssetsURL("/images/svg/smile.png");
const _imports_6 = publicAssetsURL("/images/svg/weep.png");
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "timeline",
  __ssrInlineRender: true,
  setup(__props) {
    const authStore = useAuthStore();
    const settingsUrl = computed(() => {
      var _a;
      if ((_a = authStore.user) == null ? void 0 : _a.reference_code) {
        return `/profile/${authStore.user.reference_code}/settings`;
      }
      return "/settings";
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<!--[--><section><div class="gap no-gap"><div class="profile-pic"><img${ssrRenderAttr("src", _imports_0)} alt=""><div class="user-dp"><figure><img${ssrRenderAttr("src", _imports_2$1)} alt=""><span class="status greenbg"></span></figure><h5>Monica Light</h5><ul><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        class: "redbg",
        to: "/about",
        title: "Profile"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-user"${_scopeId}></i>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-user" })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        class: "bluebg",
        to: unref(settingsUrl),
        title: "Edit Profile"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-pencil"${_scopeId}></i>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-pencil" })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li><li><a class="yellowbg" href="#" title="Follow"><i class="lni lni-star"></i></a></li></ul></div></div></div></section><section><div class="gap no-gap"><div class="profile-info"><ul><li title="followers"><i class="lni lni-star"></i><ins>1.5k</ins></li><li title="following"><i class="lni lni-users"></i><ins>223</ins></li><li title="Likes"><i class="lni lni-heart"></i><ins>665</ins></li><li title="Posts"><i class="lni lni-postcard"></i><ins>103</ins></li></ul></div></div></section><section><div class="gap no-top overlap"><div class="page-caro"><div class="link-item">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/about",
        title: ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-user"${_scopeId}></i><p${_scopeId}>About</p>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-user" }),
              createVNode("p", null, "About")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="link-item">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/timeline",
        title: ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-keyboard"${_scopeId}></i><p${_scopeId}>Timeline</p>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-keyboard" }),
              createVNode("p", null, "Timeline")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="link-item">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/followers",
        title: ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-emoji-smile"${_scopeId}></i><p${_scopeId}>Followers</p>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-emoji-smile" }),
              createVNode("p", null, "Followers")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="link-item">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/play/groups",
        title: ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-network"${_scopeId}></i><p${_scopeId}>Groups</p>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-network" }),
              createVNode("p", null, "Groups")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="link-item">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/photos",
        title: ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-gallery"${_scopeId}></i><p${_scopeId}>Photos</p>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-gallery" }),
              createVNode("p", null, "Photos")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="link-item">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/videos",
        title: ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<i class="lni lni-play"${_scopeId}></i><p${_scopeId}>Videos</p>`);
          } else {
            return [
              createVNode("i", { class: "lni lni-play" }),
              createVNode("p", null, "Videos")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div></div></section><section><div class="gap no-gap"><div class="container"><div class="row"><div class="col-lg-12"><h5 class="main-title">Share it</h5><ul class="social-share"><li><a class="facebook" href="#" title=""><i class="lni lni-facebook"></i></a></li><li><a class="twitter" href="#" title=""><i class="lni lni-twitter"></i></a></li><li><a class="youtube" href="#" title=""><i class="lni lni-youtube"></i></a></li><li><a class="pinterest" href="#" title=""><i class="lni lni-pinterest"></i></a></li><li><a class="instagram" href="#" title=""><i class="lni lni-instagram"></i></a></li><li><a class="dribble" href="#" title=""><i class="lni lni-dribbble"></i></a></li></ul></div></div></div></div></section><section><div class="gap"><div class="container"><div class="row"><div class="col-12"><div class="user-post"><figure><img${ssrRenderAttr("src", _imports_2$1)} alt=""></figure><div class="user-name"><h5>Monica Light</h5><span>1 weeks ago</span></div><div class="post-meta"><p> sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. David ! </p><img${ssrRenderAttr("src", _imports_2)} alt=""></div><div class="more ico-hover"><i class="lni lni-more"></i><div class="more-options"><ul><li><i class="lni lni-trash"></i> Delete Post</li><li><i class="lni lni-ban"></i> Hide Post</li><li><i class="lni lni-pencil-alt"></i> Edit Post</li></ul></div></div><div class="user-stat"><div class="emoji-state"><img${ssrRenderAttr("src", _imports_3)} alt=""><img${ssrRenderAttr("src", _imports_4)} alt=""><img${ssrRenderAttr("src", _imports_5)} alt=""><img${ssrRenderAttr("src", _imports_6)} alt=""><p>10</p></div><div class="coment-state"><span><i class="lni lni-comments"></i>24 comments</span><span><i class="lni lni-share"></i>56 shares</span></div></div><div class="stat-tools"><div class="box"><div class="Like"><a class="Like__link"><i class="lni lni-heart"></i> Like</a><div class="Emojis"><div class="Emoji Emoji--like"><div class="icon icon--like"></div></div><div class="Emoji Emoji--love"><div class="icon icon--heart"></div></div><div class="Emoji Emoji--haha"><div class="icon icon--haha"></div></div><div class="Emoji Emoji--wow"><div class="icon icon--wow"></div></div><div class="Emoji Emoji--sad"><div class="icon icon--sad"></div></div><div class="Emoji Emoji--angry"><div class="icon icon--angry"></div></div></div></div></div><a class="comment-to" href="#" title=""><i class="lni lni-comments"></i> comment</a><a class="share-to" href="#" title=""><i class="lni lni-share-alt"></i> share</a><div class="new-comment"><form method="post"><input type="text" placeholder="write comment"><button type="submit"><i class="lni lni-pointer"></i></button></form></div></div></div><div class="loadmore"><div class="loader"><div class="line"></div><div class="line"></div><div class="line"></div><div class="line"></div></div><span>Loading...</span></div></div></div></div></div></section><!--]-->`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/timeline.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=timeline-CBqWu2T6.mjs.map
