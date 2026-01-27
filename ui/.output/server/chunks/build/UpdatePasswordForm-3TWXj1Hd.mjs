import { ref, mergeProps, withCtx, unref, createTextVNode, createVNode, withModifiers, createBlock, openBlock, useSSRContext } from 'vue';
import { ssrRenderComponent } from 'vue/server-renderer';
import { u as useForm } from './inertia-vue3-CWdJjaLG.mjs';
import { _ as _sfc_main$2 } from './ActionMessage-CLeRFQtH.mjs';
import { _ as _sfc_main$1 } from './FormSection-fwBpxzn-.mjs';
import { _ as _sfc_main$6 } from './InputError-DhKaE0Xu.mjs';
import { _ as _sfc_main$4 } from './InputLabel-BgPfgEkm.mjs';
import { _ as _sfc_main$3 } from './PrimaryButton-D8uKmFqJ.mjs';
import { _ as _sfc_main$5 } from './TextInput-B3Ml7s7p.mjs';
import { Icon } from '@iconify/vue';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import './SectionTitle-BqtbC2dE.mjs';

const _sfc_main = {
  __name: "UpdatePasswordForm",
  __ssrInlineRender: true,
  setup(__props) {
    const passwordInput = ref(null);
    const currentPasswordInput = ref(null);
    const showPassword = ref(false);
    const form = useForm({
      current_password: "",
      password: "",
      password_confirmation: ""
    });
    const updatePassword = () => {
      form.put(route("user-password.update"), {
        errorBag: "updatePassword",
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
          if (form.errors.password) {
            form.reset("password", "password_confirmation");
            passwordInput.value.focus();
          }
          if (form.errors.current_password) {
            form.reset("current_password");
            currentPasswordInput.value.focus();
          }
        }
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({ onSubmitted: updatePassword }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Update Password `);
          } else {
            return [
              createTextVNode(" Update Password ")
            ];
          }
        }),
        description: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Ensure your account is using a long, random password to stay secure. `);
          } else {
            return [
              createTextVNode(" Ensure your account is using a long, random password to stay secure. ")
            ];
          }
        }),
        form: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="col-span-6 sm:col-span-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              for: "current_password",
              value: "Current Password"
            }, null, _parent2, _scopeId));
            _push2(`<div class="flex"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$5, {
              id: "current_password",
              ref_key: "currentPasswordInput",
              ref: currentPasswordInput,
              modelValue: unref(form).current_password,
              "onUpdate:modelValue": ($event) => unref(form).current_password = $event,
              type: showPassword.value ? "text" : "password",
              class: "mt-1 block w-full",
              autocomplete: "current-password"
            }, null, _parent2, _scopeId));
            _push2(`<button class="ml-2"${_scopeId}>`);
            if (showPassword.value) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:show",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:hide",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            }
            _push2(`</button></div>`);
            _push2(ssrRenderComponent(_sfc_main$6, {
              message: unref(form).errors.current_password,
              class: "mt-2"
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="col-span-6 sm:col-span-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              for: "password",
              value: "New Password"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$5, {
              id: "password",
              ref_key: "passwordInput",
              ref: passwordInput,
              modelValue: unref(form).password,
              "onUpdate:modelValue": ($event) => unref(form).password = $event,
              type: showPassword.value ? "text" : "password",
              class: "mt-1 block w-full",
              autocomplete: "new-password"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$6, {
              message: unref(form).errors.password,
              class: "mt-2"
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="col-span-6 sm:col-span-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              for: "password_confirmation",
              value: "Confirm Password"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$5, {
              id: "password_confirmation",
              modelValue: unref(form).password_confirmation,
              "onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
              type: showPassword.value ? "text" : "password",
              class: "mt-1 block w-full",
              autocomplete: "new-password"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$6, {
              message: unref(form).errors.password_confirmation,
              class: "mt-2"
            }, null, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode(_sfc_main$4, {
                  for: "current_password",
                  value: "Current Password"
                }),
                createVNode("div", { class: "flex" }, [
                  createVNode(_sfc_main$5, {
                    id: "current_password",
                    ref_key: "currentPasswordInput",
                    ref: currentPasswordInput,
                    modelValue: unref(form).current_password,
                    "onUpdate:modelValue": ($event) => unref(form).current_password = $event,
                    type: showPassword.value ? "text" : "password",
                    class: "mt-1 block w-full",
                    autocomplete: "current-password"
                  }, null, 8, ["modelValue", "onUpdate:modelValue", "type"]),
                  createVNode("button", {
                    class: "ml-2",
                    onClick: withModifiers(($event) => showPassword.value = !showPassword.value, ["prevent"])
                  }, [
                    showPassword.value ? (openBlock(), createBlock(unref(Icon), {
                      key: 0,
                      icon: "mdi:show",
                      class: "w-6 h-6"
                    })) : (openBlock(), createBlock(unref(Icon), {
                      key: 1,
                      icon: "mdi:hide",
                      class: "w-6 h-6"
                    }))
                  ], 8, ["onClick"])
                ]),
                createVNode(_sfc_main$6, {
                  message: unref(form).errors.current_password,
                  class: "mt-2"
                }, null, 8, ["message"])
              ]),
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode(_sfc_main$4, {
                  for: "password",
                  value: "New Password"
                }),
                createVNode(_sfc_main$5, {
                  id: "password",
                  ref_key: "passwordInput",
                  ref: passwordInput,
                  modelValue: unref(form).password,
                  "onUpdate:modelValue": ($event) => unref(form).password = $event,
                  type: showPassword.value ? "text" : "password",
                  class: "mt-1 block w-full",
                  autocomplete: "new-password"
                }, null, 8, ["modelValue", "onUpdate:modelValue", "type"]),
                createVNode(_sfc_main$6, {
                  message: unref(form).errors.password,
                  class: "mt-2"
                }, null, 8, ["message"])
              ]),
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode(_sfc_main$4, {
                  for: "password_confirmation",
                  value: "Confirm Password"
                }),
                createVNode(_sfc_main$5, {
                  id: "password_confirmation",
                  modelValue: unref(form).password_confirmation,
                  "onUpdate:modelValue": ($event) => unref(form).password_confirmation = $event,
                  type: showPassword.value ? "text" : "password",
                  class: "mt-1 block w-full",
                  autocomplete: "new-password"
                }, null, 8, ["modelValue", "onUpdate:modelValue", "type"]),
                createVNode(_sfc_main$6, {
                  message: unref(form).errors.password_confirmation,
                  class: "mt-2"
                }, null, 8, ["message"])
              ])
            ];
          }
        }),
        actions: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_sfc_main$2, {
              on: unref(form).recentlySuccessful,
              class: "me-3"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Saved. `);
                } else {
                  return [
                    createTextVNode(" Saved. ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$3, {
              class: { "opacity-25": unref(form).processing },
              disabled: unref(form).processing
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Save `);
                } else {
                  return [
                    createTextVNode(" Save ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(_sfc_main$2, {
                on: unref(form).recentlySuccessful,
                class: "me-3"
              }, {
                default: withCtx(() => [
                  createTextVNode(" Saved. ")
                ]),
                _: 1
              }, 8, ["on"]),
              createVNode(_sfc_main$3, {
                class: { "opacity-25": unref(form).processing },
                disabled: unref(form).processing
              }, {
                default: withCtx(() => [
                  createTextVNode(" Save ")
                ]),
                _: 1
              }, 8, ["class", "disabled"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/profile/Partials/UpdatePasswordForm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=UpdatePasswordForm-3TWXj1Hd.mjs.map
