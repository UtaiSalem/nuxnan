import { computed, unref, withCtx, createVNode, createTextVNode, createBlock, createCommentVNode, openBlock, withModifiers, useSSRContext } from 'vue';
import { ssrRenderComponent } from 'vue/server-renderer';
import { u as useForm, H as Head, L as Link } from './inertia-vue3-CWdJjaLG.mjs';
import { A as AuthenticationCard } from './AuthenticationCard-Cc5GEVqI.mjs';
import { _ as _sfc_main$2 } from './AuthenticationCardLogo-DVECfwqv.mjs';
import { _ as _sfc_main$1 } from './PrimaryButton-D8uKmFqJ.mjs';
import './server.mjs';
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
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import './virtual_public-CJ1CIvfL.mjs';

const _sfc_main = {
  __name: "VerifyEmail",
  __ssrInlineRender: true,
  props: {
    status: String
  },
  setup(__props) {
    const props = __props;
    const form = useForm({});
    const submit = () => {
      form.post(route("verification.send"));
    };
    const verificationLinkSent = computed(() => props.status === "verification-link-sent");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Email Verification" }, null, _parent));
      _push(ssrRenderComponent(AuthenticationCard, null, {
        logo: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$2, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$2)
            ];
          }
        }),
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-4 text-sm"${_scopeId}> Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn&#39;t receive the email, we will gladly send you another. <br${_scopeId}> If you did not receive the email, we will gladly send you another. </div><div class="mb-4 text-sm"${_scopeId}> \u0E01\u0E48\u0E2D\u0E19\u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E15\u0E48\u0E2D \u0E01\u0E23\u0E38\u0E13\u0E32\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E42\u0E14\u0E22\u0E01\u0E32\u0E23\u0E04\u0E25\u0E34\u0E01\u0E17\u0E35\u0E48\u0E25\u0E34\u0E07\u0E01\u0E4C\u0E17\u0E35\u0E48\u0E40\u0E23\u0E32\u0E2A\u0E48\u0E07\u0E44\u0E1B\u0E43\u0E2B\u0E49\u0E04\u0E38\u0E13\u0E17\u0E32\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48? <br${_scopeId}> \u0E2B\u0E32\u0E01\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E17\u0E32\u0E07\u0E40\u0E23\u0E32\u0E22\u0E34\u0E19\u0E14\u0E35\u0E17\u0E35\u0E48\u0E08\u0E30\u0E2A\u0E48\u0E07\u0E43\u0E2B\u0E49\u0E04\u0E38\u0E13\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </div>`);
            if (verificationLinkSent.value) {
              _push2(`<div class="mb-4 font-medium text-sm text-green-600"${_scopeId}> A new verification link has been sent to the email address you provided in your profile settings. </div>`);
            } else {
              _push2(`<!---->`);
            }
            if (verificationLinkSent.value) {
              _push2(`<div class="mb-4 font-medium text-sm text-green-600"${_scopeId}> \u0E25\u0E34\u0E07\u0E01\u0E4C\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E43\u0E2B\u0E21\u0E48\u0E16\u0E39\u0E01\u0E2A\u0E48\u0E07\u0E44\u0E1B\u0E22\u0E31\u0E07\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E44\u0E27\u0E49\u0E43\u0E19\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E41\u0E25\u0E49\u0E27 </div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<form${_scopeId}><div class="mt-4 flex flex-col gap-2 w-full"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$1, {
              class: { "opacity-25": unref(form).processing },
              disabled: unref(form).processing
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div class="flex flex-col w-full"${_scopeId2}><span${_scopeId2}> Resend Verification Email</span><span${_scopeId2}> \u0E2A\u0E48\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07</span></div>`);
                } else {
                  return [
                    createVNode("div", { class: "flex flex-col w-full" }, [
                      createVNode("span", null, " Resend Verification Email"),
                      createVNode("span", null, " \u0E2A\u0E48\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07")
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("profile.show"),
              class: "p-2 text-center border text-sm bg-violet-400 text-white hover:bg-violet-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Edit Profile/\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C `);
                } else {
                  return [
                    createTextVNode(" Edit Profile/\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("logout"),
              method: "post",
              as: "button",
              class: "w-full border p-2 text-sm text-red-600 hover:text-gray-900 hover:bg-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Log Out `);
                } else {
                  return [
                    createTextVNode(" Log Out ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></form>`);
          } else {
            return [
              createVNode("div", { class: "mb-4 text-sm" }, [
                createTextVNode(" Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another. "),
                createVNode("br"),
                createTextVNode(" If you did not receive the email, we will gladly send you another. ")
              ]),
              createVNode("div", { class: "mb-4 text-sm" }, [
                createTextVNode(" \u0E01\u0E48\u0E2D\u0E19\u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E15\u0E48\u0E2D \u0E01\u0E23\u0E38\u0E13\u0E32\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E42\u0E14\u0E22\u0E01\u0E32\u0E23\u0E04\u0E25\u0E34\u0E01\u0E17\u0E35\u0E48\u0E25\u0E34\u0E07\u0E01\u0E4C\u0E17\u0E35\u0E48\u0E40\u0E23\u0E32\u0E2A\u0E48\u0E07\u0E44\u0E1B\u0E43\u0E2B\u0E49\u0E04\u0E38\u0E13\u0E17\u0E32\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48? "),
                createVNode("br"),
                createTextVNode(" \u0E2B\u0E32\u0E01\u0E04\u0E38\u0E13\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E2D\u0E35\u0E40\u0E21\u0E25 \u0E17\u0E32\u0E07\u0E40\u0E23\u0E32\u0E22\u0E34\u0E19\u0E14\u0E35\u0E17\u0E35\u0E48\u0E08\u0E30\u0E2A\u0E48\u0E07\u0E43\u0E2B\u0E49\u0E04\u0E38\u0E13\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 ")
              ]),
              verificationLinkSent.value ? (openBlock(), createBlock("div", {
                key: 0,
                class: "mb-4 font-medium text-sm text-green-600"
              }, " A new verification link has been sent to the email address you provided in your profile settings. ")) : createCommentVNode("", true),
              verificationLinkSent.value ? (openBlock(), createBlock("div", {
                key: 1,
                class: "mb-4 font-medium text-sm text-green-600"
              }, " \u0E25\u0E34\u0E07\u0E01\u0E4C\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E43\u0E2B\u0E21\u0E48\u0E16\u0E39\u0E01\u0E2A\u0E48\u0E07\u0E44\u0E1B\u0E22\u0E31\u0E07\u0E17\u0E35\u0E48\u0E2D\u0E22\u0E39\u0E48\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E17\u0E35\u0E48\u0E04\u0E38\u0E13\u0E43\u0E2B\u0E49\u0E44\u0E27\u0E49\u0E43\u0E19\u0E01\u0E32\u0E23\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E41\u0E25\u0E49\u0E27 ")) : createCommentVNode("", true),
              createVNode("form", {
                onSubmit: withModifiers(submit, ["prevent"])
              }, [
                createVNode("div", { class: "mt-4 flex flex-col gap-2 w-full" }, [
                  createVNode(_sfc_main$1, {
                    class: { "opacity-25": unref(form).processing },
                    disabled: unref(form).processing
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "flex flex-col w-full" }, [
                        createVNode("span", null, " Resend Verification Email"),
                        createVNode("span", null, " \u0E2A\u0E48\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07")
                      ])
                    ]),
                    _: 1
                  }, 8, ["class", "disabled"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("profile.show"),
                    class: "p-2 text-center border text-sm bg-violet-400 text-white hover:bg-violet-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Edit Profile/\u0E41\u0E01\u0E49\u0E44\u0E02\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("logout"),
                    method: "post",
                    as: "button",
                    class: "w-full border p-2 text-sm text-red-600 hover:text-gray-900 hover:bg-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" Log Out ")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ], 32)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/auth/VerifyEmail.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=VerifyEmail-CrzPpxOz.mjs.map
