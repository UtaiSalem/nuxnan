import { ref, mergeProps, withCtx, unref, createTextVNode, createVNode, createBlock, createCommentVNode, openBlock, withDirectives, vShow, withModifiers, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderStyle, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';
import { u as useForm, L as Link, r as router } from './inertia-vue3-CWdJjaLG.mjs';
import { _ as _sfc_main$2 } from './ActionMessage-CLeRFQtH.mjs';
import { _ as _sfc_main$1 } from './FormSection-fwBpxzn-.mjs';
import { _ as _sfc_main$6 } from './InputError-DhKaE0Xu.mjs';
import { _ as _sfc_main$4 } from './InputLabel-BgPfgEkm.mjs';
import { _ as _sfc_main$3 } from './PrimaryButton-D8uKmFqJ.mjs';
import { _ as _sfc_main$5 } from './SecondaryButton-CNmWyvs4.mjs';
import { _ as _sfc_main$7 } from './TextInput-B3Ml7s7p.mjs';
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
import './SectionTitle-BqtbC2dE.mjs';

const _sfc_main = {
  __name: "UpdateProfileInformationForm",
  __ssrInlineRender: true,
  props: {
    user: Object
  },
  setup(__props) {
    const props = __props;
    const form = useForm({
      _method: "PUT",
      name: props.user.name,
      email: props.user.email,
      photo: null
    });
    const verificationLinkSent = ref(null);
    const photoPreview = ref(null);
    const photoInput = ref(null);
    const updateProfileInformation = () => {
      if (photoInput.value) {
        form.photo = photoInput.value.files[0];
      }
      form.post(route("user-profile-information.update"), {
        errorBag: "updateProfileInformation",
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput()
      });
    };
    const sendEmailVerification = () => {
      verificationLinkSent.value = true;
    };
    const selectNewPhoto = () => {
      photoInput.value.click();
    };
    const updatePhotoPreview = () => {
      const photo = photoInput.value.files[0];
      if (!photo) return;
      const reader = new FileReader();
      reader.onload = (e) => {
        photoPreview.value = e.target.result;
      };
      reader.readAsDataURL(photo);
    };
    const deletePhoto = () => {
      router.delete(route("current-user-photo.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
          photoPreview.value = null;
          clearPhotoFileInput();
        }
      });
    };
    const clearPhotoFileInput = () => {
      var _a;
      if ((_a = photoInput.value) == null ? void 0 : _a.value) {
        photoInput.value.value = null;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, mergeProps({ onSubmitted: updateProfileInformation }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Profile Information / \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C `);
          } else {
            return [
              createTextVNode(" Profile Information / \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C ")
            ];
          }
        }),
        description: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Update your account&#39;s profile information and email address./ \u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E41\u0E25\u0E30\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 `);
          } else {
            return [
              createTextVNode(" Update your account's profile information and email address./ \u0E2D\u0E31\u0E1E\u0E40\u0E14\u0E17\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E41\u0E25\u0E30\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 ")
            ];
          }
        }),
        form: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (_ctx.$page.props.jetstream.managesProfilePhotos) {
              _push2(`<div class="col-span-6 sm:col-span-4"${_scopeId}><input id="photo" type="file" class="hidden"${_scopeId}>`);
              _push2(ssrRenderComponent(_sfc_main$4, {
                for: "photo",
                value: "Photo / \u0E23\u0E39\u0E1B\u0E16\u0E48\u0E32\u0E22"
              }, null, _parent2, _scopeId));
              _push2(`<div class="mt-2" style="${ssrRenderStyle(!photoPreview.value ? null : { display: "none" })}"${_scopeId}><img${ssrRenderAttr("src", __props.user.profile_photo_url)}${ssrRenderAttr("alt", __props.user.name)} class="object-cover w-20 h-20 rounded-full"${_scopeId}></div><div class="mt-2" style="${ssrRenderStyle(photoPreview.value ? null : { display: "none" })}"${_scopeId}><span class="block w-20 h-20 bg-center bg-no-repeat bg-cover rounded-full" style="${ssrRenderStyle("background-image: url('" + photoPreview.value + "');")}"${_scopeId}></span></div>`);
              _push2(ssrRenderComponent(_sfc_main$5, {
                class: "mt-2 me-2",
                type: "button",
                onClick: selectNewPhoto
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Select A New Photo / \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E39\u0E1B\u0E43\u0E2B\u0E21\u0E48 `);
                  } else {
                    return [
                      createTextVNode(" Select A New Photo / \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E39\u0E1B\u0E43\u0E2B\u0E21\u0E48 ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              if (__props.user.profile_photo_path) {
                _push2(ssrRenderComponent(_sfc_main$5, {
                  type: "button",
                  class: "mt-2",
                  onClick: deletePhoto
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(` Remove Photo / \u0E25\u0E1A\u0E23\u0E39\u0E1B `);
                    } else {
                      return [
                        createTextVNode(" Remove Photo / \u0E25\u0E1A\u0E23\u0E39\u0E1B ")
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(ssrRenderComponent(_sfc_main$6, {
                message: unref(form).errors.photo,
                class: "mt-2"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="col-span-6 sm:col-span-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              for: "name",
              value: "Name/ \u0E0A\u0E37\u0E48\u0E2D - \u0E2A\u0E01\u0E38\u0E25"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$7, {
              id: "name",
              modelValue: unref(form).name,
              "onUpdate:modelValue": ($event) => unref(form).name = $event,
              type: "text",
              class: "block w-full mt-1",
              required: "",
              autocomplete: "name"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$6, {
              message: unref(form).errors.name,
              class: "mt-2"
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="col-span-6 sm:col-span-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$4, {
              for: "email",
              value: "Email/ \u0E2D\u0E35\u0E40\u0E21\u0E25"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$7, {
              id: "email",
              modelValue: unref(form).email,
              "onUpdate:modelValue": ($event) => unref(form).email = $event,
              type: "email",
              class: "block w-full mt-1",
              required: "",
              autocomplete: "username"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$6, {
              message: unref(form).errors.email,
              class: "mt-2"
            }, null, _parent2, _scopeId));
            if (_ctx.$page.props.jetstream.hasEmailVerification && __props.user.email_verified_at === null) {
              _push2(`<div${_scopeId}><p class="mt-2 text-sm"${_scopeId}> Your email address is unverified. / \u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19 `);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("verification.send"),
                method: "post",
                as: "button",
                class: "text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500",
                onClick: sendEmailVerification
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Click here to re-send the verification email. / \u0E04\u0E25\u0E34\u0E01\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E48\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 `);
                  } else {
                    return [
                      createTextVNode(" Click here to re-send the verification email. / \u0E04\u0E25\u0E34\u0E01\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E48\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</p><div class="mt-2 text-sm font-medium text-green-600" style="${ssrRenderStyle(verificationLinkSent.value ? null : { display: "none" })}"${_scopeId}> A new verification link has been sent to your email address. / \u0E25\u0E34\u0E07\u0E04\u0E4C\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E43\u0E2B\u0E21\u0E48\u0E16\u0E39\u0E01\u0E2A\u0E48\u0E07\u0E44\u0E1B\u0E22\u0E31\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E41\u0E25\u0E49\u0E27 </div></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="col-span-6 sm:col-span-4"${_scopeId}><p class="text-sm font-semibold text-gray-600"${_scopeId}>Personal code / \u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</p><p class="text-blue-600 text-[32px] font-bold"${_scopeId}>${ssrInterpolate(_ctx.$page.props.auth.user.personal_code)}</p></div><div class="col-span-6 sm:col-span-4"${_scopeId}><p class="text-sm font-semibold text-gray-600"${_scopeId}>\u0E08\u0E33\u0E19\u0E27\u0E19\u0E1C\u0E39\u0E49\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07</p><p class="text-blue-600 text-[32px] font-bold"${_scopeId}>${ssrInterpolate(_ctx.$page.props.auth.user.no_of_ref)}</p></div>`);
          } else {
            return [
              _ctx.$page.props.jetstream.managesProfilePhotos ? (openBlock(), createBlock("div", {
                key: 0,
                class: "col-span-6 sm:col-span-4"
              }, [
                createVNode("input", {
                  id: "photo",
                  ref_key: "photoInput",
                  ref: photoInput,
                  type: "file",
                  class: "hidden",
                  onChange: updatePhotoPreview
                }, null, 544),
                createVNode(_sfc_main$4, {
                  for: "photo",
                  value: "Photo / \u0E23\u0E39\u0E1B\u0E16\u0E48\u0E32\u0E22"
                }),
                withDirectives(createVNode("div", { class: "mt-2" }, [
                  createVNode("img", {
                    src: __props.user.profile_photo_url,
                    alt: __props.user.name,
                    class: "object-cover w-20 h-20 rounded-full"
                  }, null, 8, ["src", "alt"])
                ], 512), [
                  [vShow, !photoPreview.value]
                ]),
                withDirectives(createVNode("div", { class: "mt-2" }, [
                  createVNode("span", {
                    class: "block w-20 h-20 bg-center bg-no-repeat bg-cover rounded-full",
                    style: "background-image: url('" + photoPreview.value + "');"
                  }, null, 4)
                ], 512), [
                  [vShow, photoPreview.value]
                ]),
                createVNode(_sfc_main$5, {
                  class: "mt-2 me-2",
                  type: "button",
                  onClick: withModifiers(selectNewPhoto, ["prevent"])
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Select A New Photo / \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E23\u0E39\u0E1B\u0E43\u0E2B\u0E21\u0E48 ")
                  ]),
                  _: 1
                }),
                __props.user.profile_photo_path ? (openBlock(), createBlock(_sfc_main$5, {
                  key: 0,
                  type: "button",
                  class: "mt-2",
                  onClick: withModifiers(deletePhoto, ["prevent"])
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Remove Photo / \u0E25\u0E1A\u0E23\u0E39\u0E1B ")
                  ]),
                  _: 1
                })) : createCommentVNode("", true),
                createVNode(_sfc_main$6, {
                  message: unref(form).errors.photo,
                  class: "mt-2"
                }, null, 8, ["message"])
              ])) : createCommentVNode("", true),
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode(_sfc_main$4, {
                  for: "name",
                  value: "Name/ \u0E0A\u0E37\u0E48\u0E2D - \u0E2A\u0E01\u0E38\u0E25"
                }),
                createVNode(_sfc_main$7, {
                  id: "name",
                  modelValue: unref(form).name,
                  "onUpdate:modelValue": ($event) => unref(form).name = $event,
                  type: "text",
                  class: "block w-full mt-1",
                  required: "",
                  autocomplete: "name"
                }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                createVNode(_sfc_main$6, {
                  message: unref(form).errors.name,
                  class: "mt-2"
                }, null, 8, ["message"])
              ]),
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode(_sfc_main$4, {
                  for: "email",
                  value: "Email/ \u0E2D\u0E35\u0E40\u0E21\u0E25"
                }),
                createVNode(_sfc_main$7, {
                  id: "email",
                  modelValue: unref(form).email,
                  "onUpdate:modelValue": ($event) => unref(form).email = $event,
                  type: "email",
                  class: "block w-full mt-1",
                  required: "",
                  autocomplete: "username"
                }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                createVNode(_sfc_main$6, {
                  message: unref(form).errors.email,
                  class: "mt-2"
                }, null, 8, ["message"]),
                _ctx.$page.props.jetstream.hasEmailVerification && __props.user.email_verified_at === null ? (openBlock(), createBlock("div", { key: 0 }, [
                  createVNode("p", { class: "mt-2 text-sm" }, [
                    createTextVNode(" Your email address is unverified. / \u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19 "),
                    createVNode(unref(Link), {
                      href: _ctx.route("verification.send"),
                      method: "post",
                      as: "button",
                      class: "text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500",
                      onClick: withModifiers(sendEmailVerification, ["prevent"])
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Click here to re-send the verification email. / \u0E04\u0E25\u0E34\u0E01\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2A\u0E48\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 ")
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ]),
                  withDirectives(createVNode("div", { class: "mt-2 text-sm font-medium text-green-600" }, " A new verification link has been sent to your email address. / \u0E25\u0E34\u0E07\u0E04\u0E4C\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E43\u0E2B\u0E21\u0E48\u0E16\u0E39\u0E01\u0E2A\u0E48\u0E07\u0E44\u0E1B\u0E22\u0E31\u0E07\u0E2D\u0E35\u0E40\u0E21\u0E25\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E41\u0E25\u0E49\u0E27 ", 512), [
                    [vShow, verificationLinkSent.value]
                  ])
                ])) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode("p", { class: "text-sm font-semibold text-gray-600" }, "Personal code / \u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01"),
                createVNode("p", { class: "text-blue-600 text-[32px] font-bold" }, toDisplayString(_ctx.$page.props.auth.user.personal_code), 1)
              ]),
              createVNode("div", { class: "col-span-6 sm:col-span-4" }, [
                createVNode("p", { class: "text-sm font-semibold text-gray-600" }, "\u0E08\u0E33\u0E19\u0E27\u0E19\u0E1C\u0E39\u0E49\u0E2D\u0E49\u0E32\u0E07\u0E2D\u0E34\u0E07"),
                createVNode("p", { class: "text-blue-600 text-[32px] font-bold" }, toDisplayString(_ctx.$page.props.auth.user.no_of_ref), 1)
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
                  _push3(` Saved. / \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E41\u0E25\u0E49\u0E27 `);
                } else {
                  return [
                    createTextVNode(" Saved. / \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E41\u0E25\u0E49\u0E27 ")
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
                  _push3(` Save / \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 `);
                } else {
                  return [
                    createTextVNode(" Save / \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 ")
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
                  createTextVNode(" Saved. / \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E41\u0E25\u0E49\u0E27 ")
                ]),
                _: 1
              }, 8, ["on"]),
              createVNode(_sfc_main$3, {
                class: { "opacity-25": unref(form).processing },
                disabled: unref(form).processing
              }, {
                default: withCtx(() => [
                  createTextVNode(" Save / \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 ")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/profile/Partials/UpdateProfileInformationForm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=UpdateProfileInformationForm-FiE2A5zw.mjs.map
