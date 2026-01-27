import { ref, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderComponent } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import ImageGalleryModal from './ImageGalleryModal-DpY9Yyol.mjs';
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

const _sfc_main = {
  __name: "HomeVisitCard",
  __ssrInlineRender: true,
  props: {
    homeVisits: Array,
    visitForm: Object,
    zones: {
      type: Array,
      default: () => []
    },
    createNewHomeVisit: Function,
    handleImageUpload: Function,
    removeImage: Function
  },
  emits: ["view-visit-images", "visit-updated"],
  setup(__props, { emit: __emit }) {
    function formatDateForInput(dateString) {
      if (!dateString) return "";
      if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;
      if (dateString.includes("T")) return dateString.split("T")[0];
      const d = new Date(dateString);
      if (isNaN(d)) return "";
      return d.toISOString().split("T")[0];
    }
    const showCreateForm = ref(false);
    const isDragging = ref(false);
    ref(null);
    const showImageGallery = ref(false);
    const selectedVisit = ref(null);
    const isDraggingInline = ref({});
    ref({});
    const savingVisits = ref({});
    ref({});
    function closeImageGallery() {
      showImageGallery.value = false;
      selectedVisit.value = null;
    }
    function getImageThumbnail(image) {
      if (!image) return "/images/placeholder.png";
      if (image.image_url) return image.image_url;
      if (image.image_path) {
        const path = image.image_path.replace(/^public\//, "");
        return `/storage/${path}`;
      }
      return "/images/placeholder.png";
    }
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "bg-white shadow overflow-hidden sm:rounded-lg" }, _attrs))}><div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-green-50 to-emerald-50"><div class="flex items-center justify-between"><div><h3 class="text-lg leading-6 font-medium text-gray-900"><i class="fas fa-home mr-2 text-green-600"></i> \u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h3><p class="mt-1 max-w-2xl text-sm text-gray-500"> \u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E41\u0E25\u0E30\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19 </p></div><button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="fas fa-plus mr-2"></i> \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E43\u0E2B\u0E21\u0E48 </button></div></div><div class="px-4 py-5 sm:p-6">`);
      if (showCreateForm.value) {
        _push(`<div class="mb-6 p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg border border-green-200"><div class="flex items-center justify-between mb-4"><h4 class="text-md font-medium text-gray-900">\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E43\u0E2B\u0E21\u0E48</h4><button class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button></div><form class="space-y-6"><div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="block text-sm font-medium text-gray-700">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 *</label><input${ssrRenderAttr("value", __props.visitForm.visit_date)} type="date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></div><div><label class="block text-sm font-medium text-gray-700">\u0E40\u0E27\u0E25\u0E32\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</label><input${ssrRenderAttr("value", __props.visitForm.visit_time)} type="time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></div></div><div><label class="block text-sm font-medium text-gray-700 mb-1"><i class="fas fa-map-marker-alt mr-2 text-green-600"></i> \u0E42\u0E0B\u0E19 <span class="text-gray-500 text-xs">(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</span></label><select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"><option value=""${ssrIncludeBooleanAttr(Array.isArray(__props.visitForm.zone_id) ? ssrLooseContain(__props.visitForm.zone_id, "") : ssrLooseEqual(__props.visitForm.zone_id, "")) ? " selected" : ""}>\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E42\u0E0B\u0E19</option><!--[-->`);
        ssrRenderList(__props.zones, (zone) => {
          _push(`<option${ssrRenderAttr("value", zone.id)}${ssrIncludeBooleanAttr(Array.isArray(__props.visitForm.zone_id) ? ssrLooseContain(__props.visitForm.zone_id, zone.id) : ssrLooseEqual(__props.visitForm.zone_id, zone.id)) ? " selected" : ""}>${ssrInterpolate(zone.zone_name)}</option>`);
        });
        _push(`<!--]--></select></div><div class="bg-white p-4 rounded-lg border border-gray-200"><div class="flex items-center justify-between mb-3"><label class="block text-sm font-medium text-gray-700"><i class="fas fa-users mr-2 text-green-600"></i> \u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </label><button type="button" class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-md transition-colors"><i class="fas fa-plus mr-1"></i> \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E23\u0E39 </button></div>`);
        if (__props.visitForm.participants.length === 0) {
          _push(`<div class="text-sm text-gray-500 text-center py-4"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 1 \u0E04\u0E19 </div>`);
        } else {
          _push(`<div class="space-y-3"><!--[-->`);
          ssrRenderList(__props.visitForm.participants, (participant, index) => {
            _push(`<div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200"><div class="flex-1"><input${ssrRenderAttr("value", participant.participant_name)} type="text" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E04\u0E23\u0E39 *" required class="w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"></div><button type="button" class="flex-shrink-0 text-red-600 hover:text-red-800 p-2" title="\u0E25\u0E1A"><i class="fas fa-trash-alt"></i></button></div>`);
          });
          _push(`<!--]--></div>`);
        }
        _push(`</div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E01\u0E32\u0E23\u0E2A\u0E31\u0E07\u0E40\u0E01\u0E15\u0E01\u0E32\u0E23\u0E13\u0E4C</label><textarea rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm placeholder:text-gray-400" placeholder="\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E2A\u0E20\u0E32\u0E1E\u0E41\u0E27\u0E14\u0E25\u0E49\u0E2D\u0E21\u0E1A\u0E49\u0E32\u0E19 \u0E2A\u0E20\u0E32\u0E1E\u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2D\u0E22\u0E39\u0E48 \u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E2A\u0E31\u0E07\u0E40\u0E01\u0E15\u0E01\u0E32\u0E23\u0E13\u0E4C...">${ssrInterpolate(__props.visitForm.observations)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</label><textarea rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm placeholder:text-gray-400" placeholder="\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38...">${ssrInterpolate(__props.visitForm.notes)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-1">\u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30</label><textarea rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm placeholder:text-gray-400" placeholder="\u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19...">${ssrInterpolate(__props.visitForm.recommendations)}</textarea></div><div><label class="text-sm font-medium text-gray-700 mb-2 flex items-center"><i class="fas fa-image mr-2 text-green-600"></i> \u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19 </label><div class="${ssrRenderClass([{ "border-green-500 bg-green-50": isDragging.value }, "relative flex flex-col items-center justify-center border-2 border-dashed rounded-lg p-6 cursor-pointer transition bg-white hover:bg-green-50"])}"><input type="file" multiple accept="image/*" class="hidden"><div class="flex flex-col items-center"><i class="fas fa-cloud-upload-alt text-3xl text-green-500 mb-2"></i><span class="text-sm text-gray-700 font-semibold">\u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E21\u0E32\u0E27\u0E32\u0E07 \u0E2B\u0E23\u0E37\u0E2D <span class="underline text-green-700">\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E1F\u0E25\u0E4C</span></span><span class="text-xs text-gray-500 mt-1">\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A\u0E44\u0E1F\u0E25\u0E4C JPG, PNG, GIF (\u0E02\u0E19\u0E32\u0E14\u0E08\u0E30\u0E16\u0E39\u0E01\u0E1A\u0E35\u0E1A\u0E2D\u0E31\u0E14\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34)</span>`);
        if (__props.visitForm.images && __props.visitForm.images.length > 0) {
          _push(`<span class="text-xs text-green-700 font-normal mt-1"> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E41\u0E25\u0E49\u0E27 ${ssrInterpolate(__props.visitForm.images.length)} \u0E44\u0E1F\u0E25\u0E4C </span>`);
        } else {
          _push(`<!---->`);
        }
        if (__props.visitForm.images && __props.visitForm.images.length > 0) {
          _push(`<button type="button" class="mt-2 text-xs text-red-600 hover:underline">\u0E25\u0E1A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</button>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (isDragging.value) {
          _push(`<div class="absolute inset-0 bg-green-100 bg-opacity-50 flex items-center justify-center pointer-events-none rounded-lg"><span class="text-green-700 font-normal text-lg">\u0E1B\u0E25\u0E48\u0E2D\u0E22\u0E44\u0E1F\u0E25\u0E4C\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14</span></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
        if (__props.visitForm.images && __props.visitForm.images.length > 0) {
          _push(`<div class="grid grid-cols-2 sm:grid-cols-4 gap-4"><!--[-->`);
          ssrRenderList(__props.visitForm.images, (image, index) => {
            _push(`<div class="relative group"><img${ssrRenderAttr("src", image.preview)} alt="Preview" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200"><button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-80 group-hover:opacity-100 transition">\xD7</button></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="flex justify-end gap-3"><button type="button" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors"> \u0E22\u0E01\u0E40\u0E25\u0E34\u0E01 </button><button type="submit"${ssrIncludeBooleanAttr(__props.visitForm.participants.length === 0) ? " disabled" : ""} class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"><i class="fas fa-save mr-2"></i> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </button></div></form></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div><h4 class="text-md font-medium text-gray-900 mb-4 flex items-center"><i class="fas fa-history mr-2 text-green-600"></i> \u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </h4>`);
      if (__props.homeVisits && __props.homeVisits.length > 0) {
        _push(`<div class="space-y-6"><!--[-->`);
        ssrRenderList(__props.homeVisits, (visit) => {
          _push(`<div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 overflow-hidden"><div class="p-4 bg-gray-50 border-b border-gray-200"><div class="flex items-start justify-between"><div class="flex items-center gap-2">`);
          if (visit.images && visit.images.length > 0) {
            _push(`<button class="text-blue-600 hover:text-blue-800 text-sm px-3 py-1 rounded hover:bg-blue-50 flex items-center gap-1">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:photo",
              class: "w-4 h-4"
            }, null, _parent));
            _push(` \u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E (${ssrInterpolate(visit.images.length)}) </button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div></div><form class="p-4 space-y-4"><div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21 *</label><input${ssrRenderAttr("value", formatDateForInput(visit.visit_date))} type="date" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"></div><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E40\u0E27\u0E25\u0E32\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</label><input${ssrRenderAttr("value", visit.visit_time)} type="time" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"></div></div><div><label class="block text-sm font-medium text-gray-700 mb-2"><i class="fas fa-map-marker-alt mr-2 text-green-600"></i> \u0E42\u0E0B\u0E19 <span class="text-gray-500 text-xs">(\u0E44\u0E21\u0E48\u0E1A\u0E31\u0E07\u0E04\u0E31\u0E1A)</span></label><select class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"><option${ssrRenderAttr("value", null)}${ssrIncludeBooleanAttr(Array.isArray(visit.zone_id) ? ssrLooseContain(visit.zone_id, null) : ssrLooseEqual(visit.zone_id, null)) ? " selected" : ""}>\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E42\u0E0B\u0E19</option><!--[-->`);
          ssrRenderList(__props.zones, (zone) => {
            _push(`<option${ssrRenderAttr("value", zone.id)}${ssrIncludeBooleanAttr(Array.isArray(visit.zone_id) ? ssrLooseContain(visit.zone_id, zone.id) : ssrLooseEqual(visit.zone_id, zone.id)) ? " selected" : ""}>${ssrInterpolate(zone.zone_name)}</option>`);
          });
          _push(`<!--]--></select>`);
          if (visit.zone) {
            _push(`<p class="mt-1 text-xs text-gray-500"> \u0E42\u0E0B\u0E19\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19: ${ssrInterpolate(visit.zone.zone_name)}</p>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div><div class="bg-white p-4 rounded-lg border border-gray-200"><div class="flex items-center justify-between mb-3"><label class="block text-sm font-medium text-gray-700">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:users-20-solid",
            class: "w-5 h-5 inline mr-2 text-green-600"
          }, null, _parent));
          _push(` \u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19 </label><button type="button" class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-md transition-colors">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:plus-20-solid",
            class: "w-4 h-4 mr-1"
          }, null, _parent));
          _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E04\u0E23\u0E39 </button></div>`);
          if (!visit.participants || visit.participants.length === 0) {
            _push(`<div class="text-sm text-gray-500 text-center py-4"> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E04\u0E23\u0E39\u0E1C\u0E39\u0E49\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21 \u0E01\u0E23\u0E38\u0E13\u0E32\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E2D\u0E22\u0E48\u0E32\u0E07\u0E19\u0E49\u0E2D\u0E22 1 \u0E04\u0E19 </div>`);
          } else {
            _push(`<div class="space-y-3"><!--[-->`);
            ssrRenderList(visit.participants, (participant, index) => {
              _push(`<div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200"><div class="flex-1"><input${ssrRenderAttr("value", participant.participant_name)} type="text" placeholder="\u0E0A\u0E37\u0E48\u0E2D\u0E04\u0E23\u0E39 *" required class="w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400"></div><button type="button" class="flex-shrink-0 text-red-600 hover:text-red-800 p-2" title="\u0E25\u0E1A">`);
              _push(ssrRenderComponent(unref(Icon), {
                icon: "heroicons:trash-20-solid",
                class: "w-4 h-4"
              }, null, _parent));
              _push(`</button></div>`);
            });
            _push(`<!--]--></div>`);
          }
          _push(`</div><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E01\u0E32\u0E23\u0E2A\u0E31\u0E07\u0E40\u0E01\u0E15\u0E01\u0E32\u0E23\u0E13\u0E4C</label><textarea rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400" placeholder="\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E2A\u0E20\u0E32\u0E1E\u0E41\u0E27\u0E14\u0E25\u0E49\u0E2D\u0E21\u0E1A\u0E49\u0E32\u0E19 \u0E2A\u0E20\u0E32\u0E1E\u0E04\u0E27\u0E32\u0E21\u0E40\u0E1B\u0E47\u0E19\u0E2D\u0E22\u0E39\u0E48 \u0E41\u0E25\u0E30\u0E01\u0E32\u0E23\u0E2A\u0E31\u0E07\u0E40\u0E01\u0E15\u0E01\u0E32\u0E23\u0E13\u0E4C...">${ssrInterpolate(visit.observations)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E40\u0E1E\u0E34\u0E48\u0E21\u0E40\u0E15\u0E34\u0E21</label><textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400" placeholder="\u0E2B\u0E21\u0E32\u0E22\u0E40\u0E2B\u0E15\u0E38...">${ssrInterpolate(visit.notes)}</textarea></div><div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30</label><textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 placeholder:text-gray-400" placeholder="\u0E02\u0E49\u0E2D\u0E40\u0E2A\u0E19\u0E2D\u0E41\u0E19\u0E30\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E01\u0E32\u0E23\u0E1E\u0E31\u0E12\u0E19\u0E32\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19...">${ssrInterpolate(visit.recommendations)}</textarea></div><div><label class="text-sm font-medium text-gray-700 mb-2 flex items-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:photo-20-solid",
            class: "w-5 h-5 mr-2 text-green-600"
          }, null, _parent));
          _push(` \u0E40\u0E1E\u0E34\u0E48\u0E21\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E2B\u0E25\u0E31\u0E01\u0E10\u0E32\u0E19 </label><div class="${ssrRenderClass([{ "border-green-500 bg-green-50": isDraggingInline.value[visit.id] }, "relative flex flex-col items-center justify-center border-2 border-dashed rounded-lg p-6 cursor-pointer transition bg-white hover:bg-green-50"])}"><input type="file" multiple accept="image/*" class="hidden"><div class="flex flex-col items-center">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "heroicons:cloud-arrow-up-20-solid",
            class: "w-8 h-8 text-green-500 mb-2"
          }, null, _parent));
          _push(`<span class="text-sm text-gray-700 font-normal">\u0E25\u0E32\u0E01\u0E44\u0E1F\u0E25\u0E4C\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E21\u0E32\u0E27\u0E32\u0E07 \u0E2B\u0E23\u0E37\u0E2D <span class="underline text-green-700">\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E25\u0E37\u0E2D\u0E01\u0E44\u0E1F\u0E25\u0E4C</span></span><span class="text-xs text-gray-500 mt-1">\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A\u0E44\u0E1F\u0E25\u0E4C JPG, PNG, GIF (\u0E02\u0E19\u0E32\u0E14\u0E08\u0E30\u0E16\u0E39\u0E01\u0E1A\u0E35\u0E1A\u0E2D\u0E31\u0E14\u0E2D\u0E31\u0E15\u0E42\u0E19\u0E21\u0E31\u0E15\u0E34)</span>`);
          if (visit.newImages && visit.newImages.length > 0) {
            _push(`<span class="text-xs text-green-700 font-normal mt-1"> \u0E40\u0E25\u0E37\u0E2D\u0E01\u0E41\u0E25\u0E49\u0E27 ${ssrInterpolate(visit.newImages.length)} \u0E44\u0E1F\u0E25\u0E4C </span>`);
          } else {
            _push(`<!---->`);
          }
          if (visit.newImages && visit.newImages.length > 0) {
            _push(`<button type="button" class="mt-2 text-xs text-red-600 hover:underline">\u0E25\u0E1A\u0E17\u0E31\u0E49\u0E07\u0E2B\u0E21\u0E14</button>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
          if (isDraggingInline.value[visit.id]) {
            _push(`<div class="absolute inset-0 bg-green-100 bg-opacity-50 flex items-center justify-center pointer-events-none rounded-lg"><span class="text-green-700 font-normal text-lg">\u0E1B\u0E25\u0E48\u0E2D\u0E22\u0E44\u0E1F\u0E25\u0E4C\u0E17\u0E35\u0E48\u0E19\u0E35\u0E48\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E2D\u0E31\u0E1E\u0E42\u0E2B\u0E25\u0E14</span></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div></div>`);
          if (visit.newImages && visit.newImages.length > 0) {
            _push(`<div class="grid grid-cols-2 sm:grid-cols-4 gap-4"><!--[-->`);
            ssrRenderList(visit.newImages, (image, index) => {
              _push(`<div class="relative group"><img${ssrRenderAttr("src", image.preview)} alt="Preview" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200"><button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-80 group-hover:opacity-100 transition">\xD7</button></div>`);
            });
            _push(`<!--]--></div>`);
          } else {
            _push(`<!---->`);
          }
          if (visit.images && visit.images.length > 0) {
            _push(`<div><label class="block text-sm font-medium text-gray-700 mb-2">\u0E23\u0E39\u0E1B\u0E20\u0E32\u0E1E\u0E17\u0E35\u0E48\u0E21\u0E35\u0E2D\u0E22\u0E39\u0E48</label><div class="grid grid-cols-2 sm:grid-cols-4 gap-4"><!--[-->`);
            ssrRenderList(visit.images.filter((img) => !img._deleted), (image, index) => {
              _push(`<div class="relative group"><img${ssrRenderAttr("src", getImageThumbnail(image))} alt="Existing image" class="w-full h-24 object-cover rounded-lg border-2 border-gray-200"><button type="button" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 opacity-80 group-hover:opacity-100 transition">\xD7</button><div class="absolute bottom-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded">${ssrInterpolate(index + 1)}</div></div>`);
            });
            _push(`<!--]--></div></div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<div class="flex justify-end gap-3 pt-4 border-t border-gray-200"><button type="button" class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium min-w-[100px]"> \u0E04\u0E37\u0E19\u0E04\u0E48\u0E32 </button><button type="submit"${ssrIncludeBooleanAttr(!visit.participants || visit.participants.length === 0 || savingVisits.value[visit.id]) ? " disabled" : ""} class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 min-w-[150px] justify-center">`);
          if (savingVisits.value[visit.id]) {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:arrow-path-20-solid",
              class: "w-4 h-4 animate-spin"
            }, null, _parent));
          } else {
            _push(ssrRenderComponent(unref(Icon), {
              icon: "heroicons:check-circle-20-solid",
              class: "w-4 h-4"
            }, null, _parent));
          }
          _push(` ${ssrInterpolate(savingVisits.value[visit.id] ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E41\u0E01\u0E49\u0E44\u0E02")}</button></div></form></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<div class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-300"><i class="fas fa-home text-4xl text-gray-300 mb-3"></i><p class="text-gray-500 mb-2">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E1B\u0E23\u0E30\u0E27\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19</p><button class="inline-flex items-center px-4 py-2 text-sm text-green-600 hover:text-green-700 font-medium"><i class="fas fa-plus mr-2"></i> \u0E2A\u0E23\u0E49\u0E32\u0E07\u0E01\u0E32\u0E23\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E1A\u0E49\u0E32\u0E19\u0E41\u0E23\u0E01 </button></div>`);
      }
      _push(`</div></div>`);
      _push(ssrRenderComponent(ImageGalleryModal, {
        show: showImageGallery.value,
        visit: selectedVisit.value,
        images: ((_a = selectedVisit.value) == null ? void 0 : _a.images) || [],
        onClose: closeImageGallery
      }, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/Learn/Student/HomeVisit/Teacher/Components/HomeVisitCard.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=HomeVisitCard-BFPeJSf0.mjs.map
