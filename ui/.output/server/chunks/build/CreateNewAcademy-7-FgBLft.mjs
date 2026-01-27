import { ref, withCtx, createVNode, unref, createBlock, openBlock, withModifiers, withDirectives, vModelText, vModelCheckbox, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrIncludeBooleanAttr, ssrLooseContain } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import { L as Link, r as router } from './inertia-vue3-CWdJjaLG.mjs';
import { _ as _sfc_main$1 } from './TextInput-B3Ml7s7p.mjs';
import MainLayout from './main-CdHCodS1.mjs';
import Swal from 'sweetalert2';
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
import './nuxt-link-Dhr1c_cd.mjs';
import 'jsqr';
import './useToast-BpzfS75l.mjs';
import './virtual_public-CJ1CIvfL.mjs';
import './useGamification-BliN7lma.mjs';

const _sfc_main = {
  __name: "CreateNewAcademy",
  __ssrInlineRender: true,
  emits: ["close-modal", "add-new-academy"],
  setup(__props, { emit: __emit }) {
    ref(false);
    const coverInput = ref(null);
    const logoInput = ref(null);
    const tempCover = ref(null);
    const tempLogo = ref(null);
    const isLoading = ref(false);
    const form = ref({
      name: "",
      slogan: "",
      address: "",
      cover: null,
      logo: null,
      autoAcceptMember: true,
      membershipFees: 0
    });
    const browseCover = () => {
      coverInput.value.click();
    };
    const browseLogo = () => {
      logoInput.value.click();
    };
    function onCoverInputChange(event) {
      form.value.cover = event.target.files[0];
      tempCover.value = URL.createObjectURL(event.target.files[0]);
    }
    function onLogoInputChange(event) {
      form.value.logo = event.target.files[0];
      tempLogo.value = URL.createObjectURL(event.target.files[0]);
    }
    async function formSubmit(e) {
      e.preventDefault();
      isLoading.value = true;
      const config = { headers: { "content-type": "multipart/form-data" } };
      const data = new FormData();
      data.append("name", form.value.name);
      data.append("slogan", form.value.slogan);
      data.append("address", form.value.address);
      data.append("autoAcceptMember", form.value.autoAcceptMember);
      data.append("membershipFees", form.value.membershipFees);
      form.value.logo ? data.append("logo", form.value.logo) : null;
      form.value.cover ? data.append("cover", form.value.cover) : null;
      try {
        let resp = await axios.post("/academies", data, config);
        if (resp.data && resp.data.success) {
          Swal.fire(
            "\u0E40\u0E2A\u0E23\u0E47\u0E08\u0E2A\u0E21\u0E1A\u0E39\u0E23\u0E13\u0E4C",
            "\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E43\u0E2B\u0E21\u0E48\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27",
            "success"
          );
          router.get(`/academies/${resp.data.academy}`);
        } else {
          Swal.fire(
            "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14",
            "\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E44\u0E21\u0E48\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E1E\u0E2D \u0E15\u0E49\u0E2D\u0E07\u0E21\u0E35\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21\u0E40\u0E01\u0E34\u0E19 1,000,000 \u0E41\u0E15\u0E49\u0E21",
            "error"
          );
        }
      } catch (error) {
      }
      isLoading.value = false;
    }
    function handleCancle() {
      (void 0).location = "/academies";
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}>`);
      _push(ssrRenderComponent(MainLayout, { title: "Create new Academy" }, {
        leftSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div${_scopeId}></div>`);
          } else {
            return [
              createVNode("div")
            ];
          }
        }),
        mainContent: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="bg-indigo-500 p-2 rounded-lg shadow-xl"${_scopeId}><div class="flex items-center justify-between w-full px-4 py-2 rounded-t-lg bg-indigo-500"${_scopeId}><h3 class="text-2xl text-white font-prompt"${_scopeId}>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E43\u0E2B\u0E21\u0E48</h3>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: "/academies",
              class: "w-10 p-3 rounded-full cursor-pointer hover:bg-indigo-400"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<svg version="1.1" id="Go_Back_Arrow_Icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="20px" height="18px" viewBox="0 0 14 10" enable-background="new 0 0 14 10" xml:space="preserve"${_scopeId2}><path fill-rule="evenodd" clip-rule="evenodd" fill="#FFFFFF" d="M14,6H3.364l2.644,2.75L4.806,10L1.202,6.25l0,0L0,5L4.806,0
                                    l1.201,1.25L3.364,4H14V6z"${_scopeId2}></path></svg>`);
                } else {
                  return [
                    (openBlock(), createBlock("svg", {
                      version: "1.1",
                      id: "Go_Back_Arrow_Icon",
                      xmlns: "http://www.w3.org/2000/svg",
                      "xmlns:xlink": "http://www.w3.org/1999/xlink",
                      x: "0px",
                      y: "0px",
                      width: "20px",
                      height: "18px",
                      viewBox: "0 0 14 10",
                      "enable-background": "new 0 0 14 10",
                      "xml:space": "preserve"
                    }, [
                      createVNode("path", {
                        "fill-rule": "evenodd",
                        "clip-rule": "evenodd",
                        fill: "#FFFFFF",
                        d: "M14,6H3.364l2.644,2.75L4.806,10L1.202,6.25l0,0L0,5L4.806,0\r\n                                    l1.201,1.25L3.364,4H14V6z"
                      })
                    ]))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div><div class="mt-4 rounded-lg shadow-xl"${_scopeId}><form id="create-new-academy-form" class="flex flex-col justify-center" enctype="multipart/form-data"${_scopeId}><div class="bg-white rounded-t-lg shadow-xl"${_scopeId}><div class="relative w-full h-[256px]"${_scopeId}><img${ssrRenderAttr("src", tempCover.value || "/storage/images/academies/covers/default_cover.png")} class="w-full h-full rounded-tl-lg rounded-tr-lg"${_scopeId}><div class="absolute top-2 right-2 flex flex-col"${_scopeId}><input type="file" class="hidden" accept="image/*"${_scopeId}><button type="button" class="text-white hover:bg-white hover:bg-opacity-50 hover:text-gray-600 transition duration-200 rounded-full p-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:camera",
              class: "w-6 h-6"
            }, null, _parent2, _scopeId));
            _push2(`</button></div></div><div class="relative flex flex-col items-center -mt-44"${_scopeId}><img class="w-40 h-40 border-4 border-white rounded-full"${ssrRenderAttr("src", tempLogo.value || "/storage/images/badge/level-badge.png")} alt="school logo"${_scopeId}><div class="relative"${_scopeId}><input type="file" class="hidden" accept="image/*"${_scopeId}><button type="button" class="absolute bottom-1 -right-4 flex justify-center hover:bg-opacity-80 hover:text-gray-600 transition duration-200"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:camera",
              class: "w-[28px] h-[28px] text-white bg-gray-600 rounded-full p-1 bg-opacity-70 hover:bg-opacity-90"
            }, null, _parent2, _scopeId));
            _push2(`</button></div></div></div><div class="bg-white rounded-b-lg mt-4"${_scopeId}><div class="space-y-2 p-4"${_scopeId}>`);
            _push2(ssrRenderComponent(_sfc_main$1, {
              id: "school_name",
              type: "text",
              class: "mt-1 block w-full text-center",
              modelValue: form.value.name,
              "onUpdate:modelValue": ($event) => form.value.name = $event,
              required: "",
              autocomplete: "school_name",
              placeholder: "\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(_sfc_main$1, {
              id: "slogan_name",
              type: "text",
              class: "mt-1 block w-full text-center",
              modelValue: form.value.slogan,
              "onUpdate:modelValue": ($event) => form.value.slogan = $event,
              required: "",
              autocomplete: "slogan_name",
              placeholder: "\u0E04\u0E33\u0E02\u0E27\u0E31\u0E0D, \u0E1B\u0E13\u0E34\u0E18\u0E32\u0E19"
            }, null, _parent2, _scopeId));
            _push2(`<textarea class="w-full py-3 border border-slate-200 rounded-lg px-3 focus:outline-none focus:border-slate-500 hover:shadow dark:bg-gray-600 dark:text-gray-100" name="bio" placeholder="\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48\u0E15\u0E31\u0E49\u0E07"${_scopeId}>${ssrInterpolate(form.value.address)}</textarea></div><div class="relative flex flex-wrap items-center ml-4 -mt-2 mb-2"${_scopeId}><input class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-violet-500 checked:bg-violet-500 checked:hover:border-violet-600 checked:hover:bg-violet-600 focus:outline-none checked:focus:border-violet-700 checked:focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" type="checkbox" id="id-c01"${ssrIncludeBooleanAttr(Array.isArray(form.value.autoAcceptMember) ? ssrLooseContain(form.value.autoAcceptMember, null) : form.value.autoAcceptMember) ? " checked" : ""}${ssrIncludeBooleanAttr(form.value.autoAcceptMember) ? " checked" : ""}${_scopeId}><label class="pl-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400" for="id-c01"${_scopeId}> \u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34 </label></div><div class="relative m-4 w-1/2"${_scopeId}><input id="id-l11" type="number"${ssrRenderAttr("value", form.value.membershipFees)} placeholder="" class="relative w-full h-12 px-4 pl-12 placeholder-transparent transition-all border rounded outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"${_scopeId}><label for="id-l11" class="cursor-text peer-focus:cursor-default peer-autofill:-top-2 absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:left-10 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-[&#39;\\00a0*&#39;] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:left-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent"${_scopeId}> \u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 </label>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "noto:coin",
              class: "absolute w-6 h-6 top-3 left-4 stroke-slate-400 peer-disabled:cursor-not-allowed"
            }, null, _parent2, _scopeId));
            _push2(`<small class="absolute flex justify-between w-full px-4 py-1 text-xs transition text-slate-400 peer-invalid:text-pink-500"${_scopeId}><span${_scopeId}>\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></small></div><div class="flex items-center justify-end w-full p-4"${_scopeId}><div class="flex space-x-4"${_scopeId}><button type="submit"${ssrIncludeBooleanAttr(isLoading.value) ? " disabled" : ""} class="inline-flex items-center justify-center h-10 gap-2 px-5 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none"${_scopeId}><span${_scopeId}>\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01</span>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "heroicons-outline:plus-circle",
              class: "w-7 h-7"
            }, null, _parent2, _scopeId));
            _push2(`</button><button type="button" class="px-6 py-2 bg-slate-200 hover:bg-red-300 hover:text-gray-800 border rounded-full"${_scopeId}> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button></div></div></div></form></div>`);
          } else {
            return [
              createVNode("div", { class: "bg-indigo-500 p-2 rounded-lg shadow-xl" }, [
                createVNode("div", { class: "flex items-center justify-between w-full px-4 py-2 rounded-t-lg bg-indigo-500" }, [
                  createVNode("h3", { class: "text-2xl text-white font-prompt" }, "\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49\u0E43\u0E2B\u0E21\u0E48"),
                  createVNode(unref(Link), {
                    href: "/academies",
                    class: "w-10 p-3 rounded-full cursor-pointer hover:bg-indigo-400"
                  }, {
                    default: withCtx(() => [
                      (openBlock(), createBlock("svg", {
                        version: "1.1",
                        id: "Go_Back_Arrow_Icon",
                        xmlns: "http://www.w3.org/2000/svg",
                        "xmlns:xlink": "http://www.w3.org/1999/xlink",
                        x: "0px",
                        y: "0px",
                        width: "20px",
                        height: "18px",
                        viewBox: "0 0 14 10",
                        "enable-background": "new 0 0 14 10",
                        "xml:space": "preserve"
                      }, [
                        createVNode("path", {
                          "fill-rule": "evenodd",
                          "clip-rule": "evenodd",
                          fill: "#FFFFFF",
                          d: "M14,6H3.364l2.644,2.75L4.806,10L1.202,6.25l0,0L0,5L4.806,0\r\n                                    l1.201,1.25L3.364,4H14V6z"
                        })
                      ]))
                    ]),
                    _: 1
                  })
                ])
              ]),
              createVNode("div", { class: "mt-4 rounded-lg shadow-xl" }, [
                createVNode("form", {
                  id: "create-new-academy-form",
                  onSubmit: withModifiers(formSubmit, ["prevent"]),
                  class: "flex flex-col justify-center",
                  enctype: "multipart/form-data"
                }, [
                  createVNode("div", { class: "bg-white rounded-t-lg shadow-xl" }, [
                    createVNode("div", { class: "relative w-full h-[256px]" }, [
                      createVNode("img", {
                        src: tempCover.value || "/storage/images/academies/covers/default_cover.png",
                        class: "w-full h-full rounded-tl-lg rounded-tr-lg"
                      }, null, 8, ["src"]),
                      createVNode("div", { class: "absolute top-2 right-2 flex flex-col" }, [
                        createVNode("input", {
                          type: "file",
                          class: "hidden",
                          accept: "image/*",
                          ref_key: "coverInput",
                          ref: coverInput,
                          onChange: onCoverInputChange
                        }, null, 544),
                        createVNode("button", {
                          type: "button",
                          onClick: withModifiers(browseCover, ["prevent"]),
                          class: "text-white hover:bg-white hover:bg-opacity-50 hover:text-gray-600 transition duration-200 rounded-full p-2"
                        }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:camera",
                            class: "w-6 h-6"
                          })
                        ])
                      ])
                    ]),
                    createVNode("div", { class: "relative flex flex-col items-center -mt-44" }, [
                      createVNode("img", {
                        class: "w-40 h-40 border-4 border-white rounded-full",
                        src: tempLogo.value || "/storage/images/badge/level-badge.png",
                        alt: "school logo"
                      }, null, 8, ["src"]),
                      createVNode("div", { class: "relative" }, [
                        createVNode("input", {
                          type: "file",
                          class: "hidden",
                          accept: "image/*",
                          ref_key: "logoInput",
                          ref: logoInput,
                          onChange: onLogoInputChange
                        }, null, 544),
                        createVNode("button", {
                          type: "button",
                          onClick: withModifiers(browseLogo, ["prevent"]),
                          class: "absolute bottom-1 -right-4 flex justify-center hover:bg-opacity-80 hover:text-gray-600 transition duration-200"
                        }, [
                          createVNode(unref(Icon), {
                            icon: "heroicons:camera",
                            class: "w-[28px] h-[28px] text-white bg-gray-600 rounded-full p-1 bg-opacity-70 hover:bg-opacity-90"
                          })
                        ])
                      ])
                    ])
                  ]),
                  createVNode("div", { class: "bg-white rounded-b-lg mt-4" }, [
                    createVNode("div", { class: "space-y-2 p-4" }, [
                      createVNode(_sfc_main$1, {
                        id: "school_name",
                        type: "text",
                        class: "mt-1 block w-full text-center",
                        modelValue: form.value.name,
                        "onUpdate:modelValue": ($event) => form.value.name = $event,
                        required: "",
                        autocomplete: "school_name",
                        placeholder: "\u0E0A\u0E37\u0E48\u0E2D\u0E41\u0E2B\u0E25\u0E48\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                      createVNode(_sfc_main$1, {
                        id: "slogan_name",
                        type: "text",
                        class: "mt-1 block w-full text-center",
                        modelValue: form.value.slogan,
                        "onUpdate:modelValue": ($event) => form.value.slogan = $event,
                        required: "",
                        autocomplete: "slogan_name",
                        placeholder: "\u0E04\u0E33\u0E02\u0E27\u0E31\u0E0D, \u0E1B\u0E13\u0E34\u0E18\u0E32\u0E19"
                      }, null, 8, ["modelValue", "onUpdate:modelValue"]),
                      withDirectives(createVNode("textarea", {
                        class: "w-full py-3 border border-slate-200 rounded-lg px-3 focus:outline-none focus:border-slate-500 hover:shadow dark:bg-gray-600 dark:text-gray-100",
                        name: "bio",
                        placeholder: "\u0E2A\u0E16\u0E32\u0E19\u0E17\u0E35\u0E48\u0E15\u0E31\u0E49\u0E07",
                        "onUpdate:modelValue": ($event) => form.value.address = $event
                      }, "                                ", 8, ["onUpdate:modelValue"]), [
                        [vModelText, form.value.address]
                      ])
                    ]),
                    createVNode("div", { class: "relative flex flex-wrap items-center ml-4 -mt-2 mb-2" }, [
                      withDirectives(createVNode("input", {
                        class: "w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-violet-500 checked:bg-violet-500 checked:hover:border-violet-600 checked:hover:bg-violet-600 focus:outline-none checked:focus:border-violet-700 checked:focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50",
                        type: "checkbox",
                        id: "id-c01",
                        "onUpdate:modelValue": ($event) => form.value.autoAcceptMember = $event,
                        checked: form.value.autoAcceptMember
                      }, null, 8, ["onUpdate:modelValue", "checked"]), [
                        [vModelCheckbox, form.value.autoAcceptMember]
                      ]),
                      createVNode("label", {
                        class: "pl-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400",
                        for: "id-c01"
                      }, " \u0E15\u0E2D\u0E1A\u0E23\u0E31\u0E1A\u0E04\u0E33\u0E02\u0E2D\u0E40\u0E1B\u0E47\u0E19\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34 ")
                    ]),
                    createVNode("div", { class: "relative m-4 w-1/2" }, [
                      withDirectives(createVNode("input", {
                        id: "id-l11",
                        type: "number",
                        "onUpdate:modelValue": ($event) => form.value.membershipFees = $event,
                        placeholder: "",
                        class: "relative w-full h-12 px-4 pl-12 placeholder-transparent transition-all border rounded outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelText, form.value.membershipFees]
                      ]),
                      createVNode("label", {
                        for: "id-l11",
                        class: "cursor-text peer-focus:cursor-default peer-autofill:-top-2 absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:left-10 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:left-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent"
                      }, " \u0E04\u0E48\u0E32\u0E18\u0E23\u0E23\u0E21\u0E40\u0E19\u0E35\u0E22\u0E21\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01 "),
                      createVNode(unref(Icon), {
                        icon: "noto:coin",
                        class: "absolute w-6 h-6 top-3 left-4 stroke-slate-400 peer-disabled:cursor-not-allowed"
                      }),
                      createVNode("small", { class: "absolute flex justify-between w-full px-4 py-1 text-xs transition text-slate-400 peer-invalid:text-pink-500" }, [
                        createVNode("span", null, "\u0E04\u0E30\u0E41\u0E19\u0E19\u0E17\u0E35\u0E48\u0E15\u0E49\u0E2D\u0E07\u0E43\u0E0A\u0E49\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E2D\u0E37\u0E48\u0E19\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01")
                      ])
                    ]),
                    createVNode("div", { class: "flex items-center justify-end w-full p-4" }, [
                      createVNode("div", { class: "flex space-x-4" }, [
                        createVNode("button", {
                          type: "submit",
                          disabled: isLoading.value,
                          class: "inline-flex items-center justify-center h-10 gap-2 px-5 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none"
                        }, [
                          createVNode("span", null, "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01"),
                          createVNode(unref(Icon), {
                            icon: "heroicons-outline:plus-circle",
                            class: "w-7 h-7"
                          })
                        ], 8, ["disabled"]),
                        createVNode("button", {
                          type: "button",
                          onClick: withModifiers(handleCancle, ["prevent"]),
                          class: "px-6 py-2 bg-slate-200 hover:bg-red-300 hover:text-gray-800 border rounded-full"
                        }, " \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 ")
                      ])
                    ])
                  ])
                ], 32)
              ])
            ];
          }
        }),
        rightSideWidget: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div${_scopeId}></div>`);
          } else {
            return [
              createVNode("div")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Academy/CreateNewAcademy.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=CreateNewAcademy-7-FgBLft.mjs.map
