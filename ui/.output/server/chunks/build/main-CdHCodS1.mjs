import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import { ref, computed, watch, provide, mergeProps, withCtx, createVNode, unref, toDisplayString, defineComponent, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderClass, ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate, ssrRenderSlot, ssrRenderTeleport, ssrRenderStyle, ssrIncludeBooleanAttr } from 'vue/server-renderer';
import { Icon } from '@iconify/vue';
import jsQRLib from 'jsqr';
import { _ as _export_sfc, u as useRouter, p as useRoute, d as useAuthStore, v as useColorMode, i as useApi } from './server.mjs';
import { u as useToast } from './useToast-BpzfS75l.mjs';
import { _ as _imports_0 } from './virtual_public-CJ1CIvfL.mjs';
import { p as publicAssetsURL } from '../_/nitro.mjs';
import { defineStore } from 'pinia';
import { u as useGamification } from './useGamification-BliN7lma.mjs';
import 'vue-router';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';

const QR_TYPES = {
  coupon: {
    type: "coupon",
    prefix: "COUPON",
    label: "\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07",
    icon: "fluent:ticket-diagonal-24-filled",
    color: "text-purple-600",
    bgColor: "bg-purple-100",
    description: "\u0E23\u0E31\u0E1A\u0E41\u0E15\u0E49\u0E21\u0E2B\u0E23\u0E37\u0E2D\u0E40\u0E07\u0E34\u0E19\u0E08\u0E32\u0E01\u0E04\u0E39\u0E1B\u0E2D\u0E07"
  },
  checkin: {
    type: "checkin",
    prefix: "CHECKIN",
    label: "\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19",
    icon: "fluent:person-available-24-filled",
    color: "text-blue-600",
    bgColor: "bg-blue-100",
    description: "\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E0A\u0E31\u0E49\u0E19\u0E40\u0E23\u0E35\u0E22\u0E19"
  },
  event: {
    type: "event",
    prefix: "EVENT",
    label: "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21",
    icon: "fluent:calendar-checkmark-24-filled",
    color: "text-green-600",
    bgColor: "bg-green-100",
    description: "\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21"
  },
  poll: {
    type: "poll",
    prefix: "POLL",
    label: "\u0E15\u0E2D\u0E1A\u0E41\u0E1A\u0E1A\u0E2A\u0E33\u0E23\u0E27\u0E08",
    icon: "fluent:poll-24-filled",
    color: "text-orange-600",
    bgColor: "bg-orange-100",
    description: "\u0E15\u0E2D\u0E1A\u0E41\u0E1A\u0E1A\u0E2A\u0E33\u0E23\u0E27\u0E08\u0E2B\u0E23\u0E37\u0E2D\u0E42\u0E1E\u0E25"
  },
  share: {
    type: "share",
    prefix: "SHARE",
    label: "\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49",
    icon: "fluent:person-24-filled",
    color: "text-cyan-600",
    bgColor: "bg-cyan-100",
    description: "\u0E14\u0E39\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49"
  },
  course: {
    type: "course",
    prefix: "COURSE",
    label: "\u0E14\u0E39\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32",
    icon: "fluent:book-24-filled",
    color: "text-indigo-600",
    bgColor: "bg-indigo-100",
    description: "\u0E40\u0E02\u0E49\u0E32\u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32"
  },
  academy: {
    type: "academy",
    prefix: "ACADEMY",
    label: "\u0E14\u0E39\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19",
    icon: "fluent:building-24-filled",
    color: "text-rose-600",
    bgColor: "bg-rose-100",
    description: "\u0E40\u0E02\u0E49\u0E32\u0E14\u0E39\u0E23\u0E32\u0E22\u0E25\u0E30\u0E40\u0E2D\u0E35\u0E22\u0E14\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19"
  },
  reward: {
    type: "reward",
    prefix: "REWARD",
    label: "\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25",
    icon: "fluent:gift-24-filled",
    color: "text-amber-600",
    bgColor: "bg-amber-100",
    description: "\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E1E\u0E34\u0E40\u0E28\u0E29"
  },
  unknown: {
    type: "unknown",
    prefix: "",
    label: "\u0E44\u0E21\u0E48\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01",
    icon: "fluent:question-circle-24-filled",
    color: "text-gray-600",
    bgColor: "bg-gray-100",
    description: "QR Code \u0E17\u0E35\u0E48\u0E44\u0E21\u0E48\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01"
  }
};
function parseQRCode(qrString) {
  const trimmed = qrString.trim().toUpperCase();
  for (const [key, config] of Object.entries(QR_TYPES)) {
    if (key === "unknown") continue;
    if (trimmed.startsWith(config.prefix + ":")) {
      const dataString = qrString.trim().substring(config.prefix.length + 1);
      const dataParts = dataString.split(":");
      return {
        type: config.type,
        config,
        rawData: qrString,
        data: dataParts,
        isValid: dataParts.length > 0 && dataParts[0].length > 0
      };
    }
  }
  if (/^\d{8}$/.test(trimmed)) {
    return {
      type: "coupon",
      config: QR_TYPES.coupon,
      rawData: qrString,
      data: [trimmed],
      isValid: true
    };
  }
  return {
    type: "unknown",
    config: QR_TYPES.unknown,
    rawData: qrString,
    data: [qrString],
    isValid: false
  };
}
function useQRScanner() {
  const api = useApi();
  const toast = useToast();
  const router = useRouter();
  const authStore = useAuthStore();
  const isScanning = ref(false);
  const isProcessing = ref(false);
  const lastScannedData = ref(null);
  const actionResult = ref(null);
  const error = ref(null);
  const videoRef = ref(null);
  const canvasRef = ref(null);
  let stream = null;
  let animationId = null;
  const startScanning = async (video, canvas) => {
    videoRef.value = video;
    canvasRef.value = canvas;
    error.value = null;
    try {
      stream = await (void 0).mediaDevices.getUserMedia({
        video: { facingMode: "environment" }
      });
      video.srcObject = stream;
      await video.play();
      isScanning.value = true;
      scanFrame();
    } catch (err) {
      error.value = "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E1B\u0E34\u0E14\u0E01\u0E25\u0E49\u0E2D\u0E07\u0E44\u0E14\u0E49";
      toast.error("\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E1B\u0E34\u0E14\u0E01\u0E25\u0E49\u0E2D\u0E07\u0E44\u0E14\u0E49: " + err.message);
      throw err;
    }
  };
  const stopScanning = () => {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
    }
    if (animationId) {
      cancelAnimationFrame(animationId);
      animationId = null;
    }
    isScanning.value = false;
  };
  const scanFrame = () => {
    if (!videoRef.value || !canvasRef.value || !isScanning.value) return;
    const video = videoRef.value;
    const canvas = canvasRef.value;
    const ctx = canvas.getContext("2d");
    if (!ctx || video.readyState !== video.HAVE_ENOUGH_DATA) {
      animationId = requestAnimationFrame(scanFrame);
      return;
    }
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    try {
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const code = jsQRLib(imageData.data, imageData.width, imageData.height);
      if (code) {
        handleQRDetected(code.data);
        return;
      }
    } catch (e) {
    }
    animationId = requestAnimationFrame(scanFrame);
  };
  const handleQRDetected = async (qrString) => {
    if (isProcessing.value) return;
    stopScanning();
    await processQRCode(qrString);
  };
  const processQRCode = async (qrString) => {
    isProcessing.value = true;
    error.value = null;
    actionResult.value = null;
    try {
      const parsed = parseQRCode(qrString);
      lastScannedData.value = parsed;
      if (!parsed.isValid) {
        const result2 = {
          success: false,
          type: "unknown",
          message: "QR Code \u0E44\u0E21\u0E48\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01"
        };
        actionResult.value = result2;
        return result2;
      }
      const result = await executeQRAction(parsed);
      actionResult.value = result;
      return result;
    } catch (err) {
      const result = {
        success: false,
        type: "unknown",
        message: err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"
      };
      actionResult.value = result;
      error.value = result.message;
      return result;
    } finally {
      isProcessing.value = false;
    }
  };
  const executeQRAction = async (parsed) => {
    switch (parsed.type) {
      case "coupon":
        return await handleCouponQR(parsed);
      case "checkin":
        return await handleCheckinQR(parsed);
      case "event":
        return await handleEventQR(parsed);
      case "poll":
        return await handlePollQR(parsed);
      case "share":
        return handleShareQR(parsed);
      case "course":
        return handleCourseQR(parsed);
      case "academy":
        return handleAcademyQR(parsed);
      case "reward":
        return await handleRewardQR(parsed);
      default:
        return {
          success: false,
          type: "unknown",
          message: "\u0E44\u0E21\u0E48\u0E23\u0E39\u0E49\u0E08\u0E31\u0E01\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17 QR Code \u0E19\u0E35\u0E49"
        };
    }
  };
  const handleCouponQR = async (parsed) => {
    var _a, _b, _c, _d, _e;
    const couponCode = parsed.data[0];
    try {
      const response = await api.post("/api/coupons/redeem", {
        coupon_code: couponCode.replace(/\D/g, "")
      });
      if (response.success) {
        if (((_a = response.data) == null ? void 0 : _a.type) === "points") {
          authStore.setPoints(response.data.new_balance);
        }
        return {
          success: true,
          type: "coupon",
          message: response.message || "\u0E23\u0E31\u0E1A\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
          data: {
            couponType: (_b = response.data) == null ? void 0 : _b.type,
            amount: (_c = response.data) == null ? void 0 : _c.amount,
            newBalance: (_d = response.data) == null ? void 0 : _d.new_balance
          }
        };
      } else {
        return {
          success: false,
          type: "coupon",
          message: response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E44\u0E14\u0E49"
        };
      }
    } catch (err) {
      const errorMessage = ((_e = err.data) == null ? void 0 : _e.message) || err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E43\u0E0A\u0E49\u0E04\u0E39\u0E1B\u0E2D\u0E07";
      return {
        success: false,
        type: "coupon",
        message: errorMessage
      };
    }
  };
  const handleCheckinQR = async (parsed) => {
    const [classId, sessionId] = parsed.data;
    try {
      const response = await api.post("/api/classes/checkin", {
        class_id: classId,
        session_id: sessionId
      });
      if (response.success) {
        return {
          success: true,
          type: "checkin",
          message: response.message || "\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
          data: response.data
        };
      } else {
        return {
          success: false,
          type: "checkin",
          message: response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D\u0E44\u0E14\u0E49"
        };
      }
    } catch (err) {
      return {
        success: false,
        type: "checkin",
        message: err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14\u0E43\u0E19\u0E01\u0E32\u0E23\u0E40\u0E0A\u0E47\u0E04\u0E0A\u0E37\u0E48\u0E2D"
      };
    }
  };
  const handleEventQR = async (parsed) => {
    const eventId = parsed.data[0];
    try {
      const response = await api.post("/api/events/checkin", {
        event_id: eventId
      });
      if (response.success) {
        return {
          success: true,
          type: "event",
          message: response.message || "\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
          data: response.data
        };
      } else {
        return {
          success: false,
          type: "event",
          message: response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E40\u0E02\u0E49\u0E32\u0E23\u0E48\u0E27\u0E21\u0E01\u0E34\u0E08\u0E01\u0E23\u0E23\u0E21\u0E44\u0E14\u0E49"
        };
      }
    } catch (err) {
      return {
        success: false,
        type: "event",
        message: err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"
      };
    }
  };
  const handlePollQR = async (parsed) => {
    const pollId = parsed.data[0];
    router.push(`/polls/${pollId}`);
    return {
      success: true,
      type: "poll",
      message: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E1B\u0E34\u0E14\u0E41\u0E1A\u0E1A\u0E2A\u0E33\u0E23\u0E27\u0E08...",
      data: { pollId }
    };
  };
  const handleShareQR = (parsed) => {
    const userRefCode = parsed.data[0];
    router.push(`/profile/${userRefCode}`);
    return {
      success: true,
      type: "share",
      message: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E1B\u0E34\u0E14\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C...",
      data: { userRefCode }
    };
  };
  const handleCourseQR = (parsed) => {
    const courseId = parsed.data[0];
    router.push(`/learn/courses/${courseId}`);
    return {
      success: true,
      type: "course",
      message: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E1B\u0E34\u0E14\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32...",
      data: { courseId }
    };
  };
  const handleAcademyQR = (parsed) => {
    const academyId = parsed.data[0];
    router.push(`/academies/${academyId}`);
    return {
      success: true,
      type: "academy",
      message: "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E40\u0E1B\u0E34\u0E14\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19...",
      data: { academyId }
    };
  };
  const handleRewardQR = async (parsed) => {
    const rewardId = parsed.data[0];
    try {
      const response = await api.post("/api/rewards/claim", {
        reward_id: rewardId
      });
      if (response.success) {
        return {
          success: true,
          type: "reward",
          message: response.message || "\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!",
          data: response.data
        };
      } else {
        return {
          success: false,
          type: "reward",
          message: response.message || "\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E23\u0E31\u0E1A\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25\u0E44\u0E14\u0E49"
        };
      }
    } catch (err) {
      return {
        success: false,
        type: "reward",
        message: err.message || "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14"
      };
    }
  };
  const reset = () => {
    lastScannedData.value = null;
    actionResult.value = null;
    error.value = null;
    isProcessing.value = false;
  };
  return {
    // State
    isScanning,
    isProcessing,
    lastScannedData,
    actionResult,
    error,
    // Methods
    startScanning,
    stopScanning,
    processQRCode,
    handleQRDetected,
    reset,
    // Utilities
    parseQRCode,
    QR_TYPES
  };
}
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "UniversalQRModal",
  __ssrInlineRender: true,
  props: {
    modelValue: { type: Boolean }
  },
  emits: ["update:modelValue", "action-complete"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const {
      isScanning,
      isProcessing,
      actionResult,
      lastScannedData,
      stopScanning,
      reset
    } = useQRScanner();
    const toast = useToast();
    const mode = ref("input");
    const manualCode = ref("");
    const detectedType = ref(null);
    ref(null);
    ref(null);
    ref(null);
    const detectTypeFromInput = () => {
      if (!manualCode.value.trim()) {
        detectedType.value = null;
        return;
      }
      const parsed = parseQRCode(manualCode.value.trim());
      detectedType.value = parsed.isValid ? parsed.type : null;
    };
    const currentTypeConfig = computed(() => {
      if (lastScannedData.value) {
        return lastScannedData.value.config;
      }
      if (detectedType.value) {
        return QR_TYPES[detectedType.value];
      }
      return null;
    });
    const formatResultData = (data) => {
      if (!data) return "";
      if (data.amount !== void 0) {
        const unit = data.couponType === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E1A\u0E32\u0E17";
        return `+${data.amount.toLocaleString()} ${unit}`;
      }
      return "";
    };
    watch(() => props.modelValue, (newVal) => {
      if (!newVal) {
        stopScanning();
        setTimeout(() => {
          reset();
          manualCode.value = "";
          detectedType.value = null;
          mode.value = "input";
        }, 300);
      }
    });
    watch(manualCode, detectTypeFromInput);
    watch(actionResult, (result) => {
      if (result) {
        if (result.success) {
          toast.success(result.message);
        } else {
          toast.error(result.message);
        }
        emit("action-complete", result);
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderTeleport(_push, (_push2) => {
        var _a, _b, _c, _d, _e, _f;
        if (__props.modelValue) {
          _push2(`<div class="fixed inset-0 z-[100] flex items-center justify-center p-4" data-v-5cba42e0><div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-v-5cba42e0></div><div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto" data-v-5cba42e0><button class="absolute top-3 right-3 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-black/20 hover:bg-black/40 text-white transition-colors" data-v-5cba42e0>`);
          _push2(ssrRenderComponent(unref(Icon), {
            icon: "fluent:dismiss-24-filled",
            class: "w-5 h-5"
          }, null, _parent));
          _push2(`</button>`);
          if (unref(actionResult)) {
            _push2(`<div data-v-5cba42e0>`);
            if (unref(actionResult).success) {
              _push2(`<!--[--><div class="p-6 text-center bg-gradient-to-r from-green-500 to-emerald-500" data-v-5cba42e0><div class="w-16 h-16 mx-auto mb-3 rounded-full bg-white/20 flex items-center justify-center" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: ((_a = currentTypeConfig.value) == null ? void 0 : _a.icon) || "fluent:qr-code-24-filled",
                class: "w-8 h-8 text-white"
              }, null, _parent));
              _push2(`</div><div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-white text-sm mb-2" data-v-5cba42e0><span data-v-5cba42e0>${ssrInterpolate(((_b = currentTypeConfig.value) == null ? void 0 : _b.label) || "QR Code")}</span></div><h2 class="text-xl font-black text-white" data-v-5cba42e0>\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!</h2><p class="text-white/80 text-sm mt-1" data-v-5cba42e0>${ssrInterpolate(unref(actionResult).message)}</p></div>`);
              if (unref(actionResult).data) {
                _push2(`<div class="p-5" data-v-5cba42e0>`);
                if (unref(actionResult).type === "coupon") {
                  _push2(`<div class="space-y-3" data-v-5cba42e0><div class="grid grid-cols-2 gap-3" data-v-5cba42e0><div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center" data-v-5cba42e0><p class="text-xs text-gray-500 uppercase" data-v-5cba42e0>\u0E1B\u0E23\u0E30\u0E40\u0E20\u0E17</p><p class="text-base font-bold text-gray-900 dark:text-white mt-1" data-v-5cba42e0>${ssrInterpolate(unref(actionResult).data.couponType === "points" ? "\u{1F3AF} \u0E41\u0E15\u0E49\u0E21" : "\u{1F4B0} \u0E40\u0E07\u0E34\u0E19")}</p></div><div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center" data-v-5cba42e0><p class="text-xs text-gray-500 uppercase" data-v-5cba42e0>\u0E44\u0E14\u0E49\u0E23\u0E31\u0E1A</p><p class="text-base font-bold text-green-600 mt-1" data-v-5cba42e0>${ssrInterpolate(formatResultData(unref(actionResult).data))}</p></div></div><div class="bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl p-3 text-center" data-v-5cba42e0><p class="text-xs text-gray-500 uppercase" data-v-5cba42e0> \u0E22\u0E2D\u0E14${ssrInterpolate(unref(actionResult).data.couponType === "points" ? "\u0E41\u0E15\u0E49\u0E21" : "\u0E40\u0E07\u0E34\u0E19")}\u0E43\u0E2B\u0E21\u0E48 </p><p class="text-xl font-black text-vikinger-purple dark:text-vikinger-cyan mt-1" data-v-5cba42e0>${ssrInterpolate(unref(actionResult).data.couponType === "wallet" ? "\u0E3F" : "")} ${ssrInterpolate((_c = unref(actionResult).data.newBalance) == null ? void 0 : _c.toLocaleString())} ${ssrInterpolate(unref(actionResult).data.couponType === "points" ? " \u0E41\u0E15\u0E49\u0E21" : "")}</p></div></div>`);
                } else if (unref(actionResult).type === "checkin" || unref(actionResult).type === "event") {
                  _push2(`<div class="text-center" data-v-5cba42e0><div class="w-20 h-20 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center" data-v-5cba42e0>`);
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: "fluent:checkmark-circle-24-filled",
                    class: "w-12 h-12 text-green-500"
                  }, null, _parent));
                  _push2(`</div><p class="text-gray-600 dark:text-gray-400" data-v-5cba42e0>${ssrInterpolate(((_d = unref(actionResult).data) == null ? void 0 : _d.class_name) || ((_e = unref(actionResult).data) == null ? void 0 : _e.event_name) || "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22")}</p></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<!--]-->`);
            } else {
              _push2(`<div class="p-6 bg-gray-50 dark:bg-gray-800/50" data-v-5cba42e0><div class="flex flex-col items-center text-center" data-v-5cba42e0><div class="relative mb-4" data-v-5cba42e0><div class="w-24 h-24 rounded-full bg-gradient-to-br from-red-100 to-orange-100 dark:from-red-900/30 dark:to-orange-900/30 flex items-center justify-center" data-v-5cba42e0><div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center animate-pulse" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:warning-24-filled",
                class: "w-8 h-8 text-white"
              }, null, _parent));
              _push2(`</div></div>`);
              if (currentTypeConfig.value) {
                _push2(`<div class="${ssrRenderClass([currentTypeConfig.value.bgColor, "absolute -bottom-1 -right-1 w-8 h-8 rounded-full flex items-center justify-center shadow-lg"])}" data-v-5cba42e0>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: currentTypeConfig.value.icon,
                  class: ["w-4 h-4", currentTypeConfig.value.color]
                }, null, _parent));
                _push2(`</div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1" data-v-5cba42e0> \u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23\u0E44\u0E14\u0E49 </h2><div class="w-full mt-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800/50 shadow-sm" data-v-5cba42e0><div class="flex items-start gap-3" data-v-5cba42e0><div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:info-24-filled",
                class: "w-5 h-5 text-red-500"
              }, null, _parent));
              _push2(`</div><div class="flex-1 text-left" data-v-5cba42e0><p class="text-sm font-medium text-gray-700 dark:text-gray-300" data-v-5cba42e0>${ssrInterpolate(unref(actionResult).message)}</p><p class="text-xs text-gray-500 mt-1" data-v-5cba42e0> \u0E01\u0E23\u0E38\u0E13\u0E32\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E30\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48\u0E2D\u0E35\u0E01\u0E04\u0E23\u0E31\u0E49\u0E07 </p></div></div></div><div class="w-full mt-4 text-left" data-v-5cba42e0><p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium" data-v-5cba42e0>\u{1F4A1} \u0E04\u0E33\u0E41\u0E19\u0E30\u0E19\u0E33:</p><ul class="space-y-1.5 text-xs text-gray-500 dark:text-gray-400" data-v-5cba42e0><li class="flex items-start gap-2" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-12-filled",
                class: "w-3 h-3 mt-0.5 text-gray-400"
              }, null, _parent));
              _push2(`<span data-v-5cba42e0>\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E27\u0E48\u0E32\u0E23\u0E2B\u0E31\u0E2A\u0E16\u0E39\u0E01\u0E15\u0E49\u0E2D\u0E07\u0E04\u0E23\u0E1A\u0E16\u0E49\u0E27\u0E19</span></li><li class="flex items-start gap-2" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-12-filled",
                class: "w-3 h-3 mt-0.5 text-gray-400"
              }, null, _parent));
              _push2(`<span data-v-5cba42e0>\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E41\u0E15\u0E48\u0E25\u0E30\u0E23\u0E2B\u0E31\u0E2A\u0E43\u0E0A\u0E49\u0E44\u0E14\u0E49\u0E40\u0E1E\u0E35\u0E22\u0E07\u0E04\u0E23\u0E31\u0E49\u0E07\u0E40\u0E14\u0E35\u0E22\u0E27</span></li><li class="flex items-start gap-2" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:checkmark-12-filled",
                class: "w-3 h-3 mt-0.5 text-gray-400"
              }, null, _parent));
              _push2(`<span data-v-5cba42e0>\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E43\u0E0A\u0E49\u0E04\u0E39\u0E1B\u0E2D\u0E07\u0E02\u0E2D\u0E07\u0E15\u0E31\u0E27\u0E40\u0E2D\u0E07\u0E44\u0E14\u0E49</span></li></ul></div></div></div>`);
            }
            _push2(`<div class="p-5 flex gap-2" data-v-5cba42e0><button class="flex-1 py-2.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold hover:opacity-90 transition-all flex items-center justify-center gap-2 text-sm" data-v-5cba42e0>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:qr-code-24-regular",
              class: "w-4 h-4"
            }, null, _parent));
            _push2(` \u0E2A\u0E41\u0E01\u0E19\u0E2D\u0E35\u0E01 </button><button class="flex-1 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all text-sm" data-v-5cba42e0> \u0E1B\u0E34\u0E14 </button></div></div>`);
          } else {
            _push2(`<div data-v-5cba42e0><div class="bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-3 md:p-5" data-v-5cba42e0><h1 class="text-xl font-black text-white flex items-center gap-2" data-v-5cba42e0>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:qr-code-24-filled",
              class: "w-6 h-6"
            }, null, _parent));
            _push2(` \u0E2A\u0E41\u0E01\u0E19 QR Code </h1><p class="text-white/80 text-sm mt-1" data-v-5cba42e0>\u0E2A\u0E41\u0E01\u0E19\u0E2B\u0E23\u0E37\u0E2D\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A QR</p></div><div class="p-3 md:p-5" data-v-5cba42e0><div class="mb-4" data-v-5cba42e0><p class="text-xs text-gray-500 dark:text-gray-400 mb-2" data-v-5cba42e0>\u0E23\u0E2D\u0E07\u0E23\u0E31\u0E1A QR Code:</p><div class="flex flex-wrap gap-1.5" data-v-5cba42e0><!--[-->`);
            ssrRenderList(unref(QR_TYPES), (config, key) => {
              _push2(`<div class="${ssrRenderClass([[config.bgColor, config.color], "inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs"])}" style="${ssrRenderStyle(key !== "unknown" ? null : { display: "none" })}" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: config.icon,
                class: "w-3 h-3"
              }, null, _parent));
              _push2(`<span data-v-5cba42e0>${ssrInterpolate(config.label)}</span></div>`);
            });
            _push2(`<!--]--></div></div><div class="grid grid-cols-2 gap-2 mb-4" data-v-5cba42e0><button class="${ssrRenderClass([
              "p-3 rounded-xl border-2 transition-all flex flex-col items-center gap-1.5",
              mode.value === "scan" ? "border-vikinger-purple bg-vikinger-purple/10" : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
            ])}" data-v-5cba42e0>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:camera-24-regular",
              class: "w-6 h-6 text-vikinger-purple"
            }, null, _parent));
            _push2(`<span class="font-bold text-gray-900 dark:text-white text-xs" data-v-5cba42e0>\u0E2A\u0E41\u0E01\u0E19\u0E01\u0E25\u0E49\u0E2D\u0E07</span></button><button class="${ssrRenderClass([
              "p-3 rounded-xl border-2 transition-all flex flex-col items-center gap-1.5",
              mode.value === "input" ? "border-vikinger-cyan bg-vikinger-cyan/10" : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
            ])}" data-v-5cba42e0>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:keyboard-24-regular",
              class: "w-6 h-6 text-vikinger-cyan"
            }, null, _parent));
            _push2(`<span class="font-bold text-gray-900 dark:text-white text-xs" data-v-5cba42e0>\u0E1B\u0E49\u0E2D\u0E19\u0E23\u0E2B\u0E31\u0E2A</span></button></div>`);
            if (mode.value === "scan") {
              _push2(`<div class="mb-4" data-v-5cba42e0><div class="relative aspect-square bg-gray-900 rounded-xl overflow-hidden" data-v-5cba42e0><video class="w-full h-full object-cover" autoplay playsinline data-v-5cba42e0></video><canvas class="hidden" data-v-5cba42e0></canvas>`);
              if (!unref(isScanning)) {
                _push2(`<div class="absolute inset-0 bg-gray-900/80 flex items-center justify-center p-2 cursor-pointer group" data-v-5cba42e0><div class="w-full h-full flex flex-col items-center justify-center border-2 border-dashed border-gray-600 rounded-xl group-hover:border-vikinger-purple/50 group-hover:bg-white/5 transition-all" data-v-5cba42e0>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:camera-24-regular",
                  class: "w-12 h-12 text-gray-400 group-hover:text-vikinger-purple transition-colors mb-2"
                }, null, _parent));
                _push2(`<p class="text-gray-400 group-hover:text-gray-300 text-sm transition-colors text-center px-2" data-v-5cba42e0>\u0E04\u0E25\u0E34\u0E01\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E41\u0E01\u0E19</p></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (unref(isProcessing)) {
                _push2(`<div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center" data-v-5cba42e0>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:spinner-ios-20-regular",
                  class: "w-10 h-10 text-white animate-spin mb-2"
                }, null, _parent));
                _push2(`<p class="text-white text-sm" data-v-5cba42e0>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1B\u0E23\u0E30\u0E21\u0E27\u0E25\u0E1C\u0E25...</p></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (unref(isScanning) && !unref(isProcessing)) {
                _push2(`<div class="absolute inset-x-0 top-0 h-1 bg-vikinger-cyan animate-scan" data-v-5cba42e0></div>`);
              } else {
                _push2(`<!---->`);
              }
              if (unref(isScanning) && !unref(isProcessing)) {
                _push2(`<div class="absolute inset-6 border-2 border-vikinger-cyan rounded-xl pointer-events-none" data-v-5cba42e0><div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-vikinger-cyan rounded-tl-lg" data-v-5cba42e0></div><div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-vikinger-cyan rounded-tr-lg" data-v-5cba42e0></div><div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-vikinger-cyan rounded-bl-lg" data-v-5cba42e0></div><div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-vikinger-cyan rounded-br-lg" data-v-5cba42e0></div></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><button${ssrIncludeBooleanAttr(unref(isProcessing)) ? " disabled" : ""} class="${ssrRenderClass([
                "w-full mt-3 py-2.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 text-sm",
                unref(isScanning) ? "bg-red-500 text-white hover:bg-red-600" : "bg-vikinger-purple text-white hover:opacity-90",
                unref(isProcessing) && "opacity-50 cursor-not-allowed"
              ])}" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: unref(isScanning) ? "fluent:stop-24-regular" : "fluent:camera-24-regular",
                class: "w-4 h-4"
              }, null, _parent));
              _push2(` ${ssrInterpolate(unref(isScanning) ? "\u0E2B\u0E22\u0E38\u0E14\u0E2A\u0E41\u0E01\u0E19" : "\u0E40\u0E23\u0E34\u0E48\u0E21\u0E2A\u0E41\u0E01\u0E19")}</button></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (mode.value === "input") {
              _push2(`<div class="mb-4" data-v-5cba42e0><label class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 block" data-v-5cba42e0> \u0E23\u0E2B\u0E31\u0E2A QR Code </label>`);
              if (detectedType.value && currentTypeConfig.value) {
                _push2(`<div class="${ssrRenderClass([currentTypeConfig.value.bgColor, "mb-2 flex items-center gap-2 p-2 rounded-lg"])}" data-v-5cba42e0>`);
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: currentTypeConfig.value.icon,
                  class: ["w-4 h-4", currentTypeConfig.value.color]
                }, null, _parent));
                _push2(`<span class="${ssrRenderClass([currentTypeConfig.value.color, "text-xs font-medium"])}" data-v-5cba42e0>${ssrInterpolate(currentTypeConfig.value.label)}: ${ssrInterpolate(currentTypeConfig.value.description)}</span></div>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="flex gap-2" data-v-5cba42e0><input${ssrRenderAttr("value", manualCode.value)} type="text" placeholder="COUPON:12345678 \u0E2B\u0E23\u0E37\u0E2D 12345678" class="flex-1 px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"${ssrIncludeBooleanAttr(unref(isProcessing)) ? " disabled" : ""} data-v-5cba42e0><button type="button" class="px-3 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all" title="\u0E27\u0E32\u0E07\u0E08\u0E32\u0E01\u0E04\u0E25\u0E34\u0E1B\u0E1A\u0E2D\u0E23\u0E4C\u0E14" data-v-5cba42e0>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:clipboard-paste-24-regular",
                class: "w-5 h-5"
              }, null, _parent));
              _push2(`</button></div><p class="text-xs text-gray-500 mt-1.5" data-v-5cba42e0> \u0E23\u0E39\u0E1B\u0E41\u0E1A\u0E1A: COUPON:\u0E23\u0E2B\u0E31\u0E2A, CHECKIN:\u0E2B\u0E49\u0E2D\u0E07:\u0E23\u0E2D\u0E1A, EVENT:\u0E23\u0E2B\u0E31\u0E2A, \u0E2B\u0E23\u0E37\u0E2D\u0E23\u0E2B\u0E31\u0E2A\u0E15\u0E31\u0E27\u0E40\u0E25\u0E02 8 \u0E2B\u0E25\u0E31\u0E01 </p></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (mode.value === "input") {
              _push2(`<button${ssrIncludeBooleanAttr(!manualCode.value.trim() || unref(isProcessing)) ? " disabled" : ""} class="${ssrRenderClass([
                "w-full py-3 rounded-xl font-bold text-base transition-all flex items-center justify-center gap-2",
                manualCode.value.trim() && !unref(isProcessing) ? "bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white shadow-lg hover:opacity-90" : "bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed"
              ])}" data-v-5cba42e0>`);
              if (unref(isProcessing)) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "fluent:spinner-ios-20-regular",
                  class: "w-4 h-4 animate-spin"
                }, null, _parent));
              } else {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: ((_f = currentTypeConfig.value) == null ? void 0 : _f.icon) || "fluent:send-24-regular",
                  class: "w-4 h-4"
                }, null, _parent));
              }
              _push2(` ${ssrInterpolate(unref(isProcessing) ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1B\u0E23\u0E30\u0E21\u0E27\u0E25\u0E1C\u0E25..." : "\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23")}</button>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div>`);
          }
          _push2(`</div></div>`);
        } else {
          _push2(`<!---->`);
        }
      }, "body", false, _parent);
    };
  }
});
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/qr/UniversalQRModal.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const __nuxt_component_1 = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-5cba42e0"]]);
const _sfc_main$1 = {
  __name: "BottomNav",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    const navigation = [
      {
        name: "\u0E1F\u0E35\u0E14\u0E02\u0E48\u0E32\u0E27",
        href: "/play/newsfeed",
        icon: "fluent:chat-bubbles-question-24-regular",
        activeIcon: "fluent:chat-bubbles-question-24-filled"
      },
      {
        name: "\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19",
        href: "/academies",
        icon: "mdi:school-outline",
        activeIcon: "mdi:school"
      },
      {
        name: "\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19",
        href: "/learn/courses",
        icon: "fluent:book-24-regular",
        activeIcon: "fluent:book-24-filled"
      },
      {
        name: "\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49",
        href: "/earn/points",
        icon: "fluent:wallet-24-regular",
        activeIcon: "fluent:wallet-24-filled"
      },
      {
        name: "\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14",
        href: "/dashboard",
        icon: "fluent:grid-24-regular",
        activeIcon: "fluent:grid-24-filled"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-vikinger-dark-100 border-t border-gray-200 dark:border-vikinger-dark-50/30 lg:hidden pb-safe" }, _attrs))} data-v-a9cfae56><div class="flex items-center justify-around h-16 px-1" data-v-a9cfae56><!--[-->`);
      ssrRenderList(navigation, (item) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          key: item.href,
          to: item.href,
          class: ["flex flex-col items-center justify-center w-full h-full gap-0.5 transition-colors relative group py-1", unref(route).path.startsWith(item.href) ? "text-vikinger-purple dark:text-vikinger-cyan" : "text-gray-500 dark:text-gray-400 hover:text-vikinger-purple dark:hover:text-vikinger-cyan"]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="relative" data-v-a9cfae56${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Icon), {
                icon: unref(route).path.startsWith(item.href) ? item.activeIcon : item.icon,
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
              _push2(`</div><span class="text-[10px] font-medium truncate max-w-[64px] leading-tight" data-v-a9cfae56${_scopeId}>${ssrInterpolate(item.name)}</span>`);
            } else {
              return [
                createVNode("div", { class: "relative" }, [
                  createVNode(unref(Icon), {
                    icon: unref(route).path.startsWith(item.href) ? item.activeIcon : item.icon,
                    class: "w-6 h-6"
                  }, null, 8, ["icon"])
                ]),
                createVNode("span", { class: "text-[10px] font-medium truncate max-w-[64px] leading-tight" }, toDisplayString(item.name), 1)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/layout/BottomNav.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const __nuxt_component_2 = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["__scopeId", "data-v-a9cfae56"]]);
const _imports_1 = publicAssetsURL("/storage/images/badge/completedq.png");
const useUIStore = defineStore("ui", () => {
  const isSidebarOpen = ref(true);
  const colorMode = useColorMode();
  const isDarkMode = computed(() => colorMode.value === "dark");
  function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
  }
  function toggleTheme() {
    colorMode.preference = colorMode.value === "dark" ? "light" : "dark";
  }
  function setTheme(theme) {
    colorMode.preference = theme;
  }
  return {
    isSidebarOpen,
    isDarkMode,
    // colorMode, // Removed to prevent serialization issues
    toggleSidebar,
    toggleTheme,
    setTheme
  };
});
const _sfc_main = {
  __name: "main",
  __ssrInlineRender: true,
  setup(__props) {
    useRouter();
    const route = useRoute();
    const authStore = useAuthStore();
    useUIStore();
    const { getPointsLeaderboard, isLoading: isGamificationLoading } = useGamification();
    const leaderboard = ref([]);
    const fetchLeaderboard = async () => {
      try {
        const data = await getPointsLeaderboard({ limit: 10 });
        leaderboard.value = data.leaderboard;
      } catch (error) {
        console.error("Failed to fetch leaderboard:", error);
      }
    };
    const getRankColor = (index) => {
      const colors = [
        "bg-amber-500",
        // 1st - ทองนุ่มนวล
        "bg-slate-400",
        // 2nd - เงินนุ่มนวล
        "bg-orange-400",
        // 3rd - ทองแดงนุ่มนวล
        "bg-sky-400",
        // 4th - ฟ้านุ่มนวล
        "bg-emerald-400",
        // 5th - เขียวนุ่มนวล
        "bg-violet-400",
        // 6th - ม่วงนุ่มนวล
        "bg-rose-400",
        // 7th - ชมพูนุ่มนวล
        "bg-teal-400",
        // 8th - เขียวน้ำทะเลนุ่มนวล
        "bg-indigo-400",
        // 9th - คราม นุ่มนวล
        "bg-cyan-400"
        // 10th - ฟ้าอมเขียวนุ่มนวล
      ];
      return colors[index] || "bg-slate-400";
    };
    const getAvatarUrl = (user, index = 0) => {
      if (user.avatar) return user.avatar;
      const bgColors = [
        "94a3b8",
        // slate-400
        "64748b",
        // slate-500
        "78716c",
        // stone-500
        "6b7280",
        // gray-500
        "71717a",
        // zinc-500
        "737373",
        // neutral-500
        "a3a3a3",
        // neutral-400
        "9ca3af",
        // gray-400
        "a1a1aa",
        // zinc-400
        "a8a29e"
        // stone-400
      ];
      const bgColor = bgColors[index % bgColors.length];
      return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=${bgColor}&color=fff`;
    };
    const isLeftDrawerOpen = ref(false);
    const isRightDrawerOpen = ref(false);
    const isMobileSidebarOpen = ref(false);
    const isSettingsOpen = ref(false);
    const isEarnMenuOpen = ref(false);
    const earnSubmenu = [
      { name: "\u0E04\u0E30\u0E41\u0E19\u0E19", href: "/earn/points", icon: "fluent:coin-stack-24-regular" },
      { name: "\u0E01\u0E23\u0E30\u0E40\u0E1B\u0E4B\u0E32\u0E40\u0E07\u0E34\u0E19", href: "/earn/wallet", icon: "fluent:wallet-24-regular" },
      { name: "\u0E04\u0E39\u0E1B\u0E2D\u0E07", href: "/earn/coupons", icon: "fluent:ticket-diagonal-24-regular" },
      { name: "\u0E23\u0E32\u0E07\u0E27\u0E31\u0E25", href: "/earn/rewards", icon: "fluent:gift-24-regular" },
      { name: "\u0E04\u0E27\u0E32\u0E21\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08", href: "/earn/gamification", icon: "fluent:trophy-24-regular" }
    ];
    const isDarkMode = ref(false);
    const authUser = computed(() => {
      const user = authStore.user;
      if (!user) {
        return {
          name: "Guest",
          username: "guest",
          email: "",
          avatar: "/images/default-avatar.png",
          profile_photo_url: "/images/default-avatar.png",
          pp: 0,
          wallet: 0,
          level: 1,
          posts: 0,
          friends: 0,
          visits: 0
        };
      }
      return {
        name: user.username || user.name || "User",
        username: user.username || user.name,
        email: user.email || "",
        avatar: user.avatar || user.profile_photo_url || "/images/default-avatar.png",
        profile_photo_url: user.avatar || user.profile_photo_url || "/images/default-avatar.png",
        pp: authStore.points,
        // ใช้ authStore.points (computed reactive)
        wallet: Number(user.wallet) || 0,
        level: user.level || 24,
        posts: user.posts || 930,
        friends: user.friends || 82,
        visits: user.visits || "5.7K",
        is_plearnd_admin: user.is_plearnd_admin || false
      };
    });
    const navigation = [
      { name: "\u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E02\u0E48\u0E32\u0E27", href: "/play/newsfeed", icon: "fluent:feed-24-regular" },
      { name: "\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19", href: "/academies", icon: "mdi:school-outline" },
      { name: "\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32", href: "/learn/courses", icon: "fluent-mdl2:publish-course" },
      { name: "\u0E2A\u0E30\u0E2A\u0E21\u0E41\u0E15\u0E49\u0E21", href: "/earn/donates", icon: "mdi:hand-coin-outline" },
      { name: "\u0E14\u0E39\u0E2A\u0E34\u0E19\u0E04\u0E49\u0E32", href: "/earn/advertise", icon: "eos-icons:product-subscriptions-outlined" }
    ];
    const closeSettings = () => {
      isSettingsOpen.value = false;
    };
    const toggleTheme = () => {
      isDarkMode.value = !isDarkMode.value;
      if (isDarkMode.value) {
        (void 0).documentElement.classList.add("dark");
        (void 0).documentElement.classList.remove("light");
        localStorage.setItem("theme", "dark");
      } else {
        (void 0).documentElement.classList.remove("dark");
        (void 0).documentElement.classList.add("light");
        localStorage.setItem("theme", "light");
      }
    };
    watch(
      () => route.fullPath,
      () => {
        isMobileSidebarOpen.value = false;
      }
    );
    provide("isDarkMode", isDarkMode);
    const settingsUrl = computed(() => {
      var _a;
      if ((_a = authStore.user) == null ? void 0 : _a.reference_code) {
        return `/profile/${authStore.user.reference_code}/settings`;
      }
      return "/settings";
    });
    provide("toggleTheme", toggleTheme);
    const formatNumber = (num) => {
      if (num >= 1e6) {
        return (num / 1e6).toFixed(1).replace(/\.0$/, "") + "M";
      }
      if (num >= 1e3) {
        return (num / 1e3).toFixed(1).replace(/\.0$/, "") + "K";
      }
      return num.toLocaleString();
    };
    const isQRScannerOpen = ref(false);
    const onQRActionComplete = (result) => {
      if (result.success) {
        fetchLeaderboard();
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      const _component_QrUniversalQRModal = __nuxt_component_1;
      const _component_LayoutBottomNav = __nuxt_component_2;
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["min-h-screen transition-colors duration-300", isDarkMode.value ? "bg-vikinger-dark dark" : "bg-gray-200 light"]
      }, _attrs))} data-v-a7e5c7cb><header class="${ssrRenderClass([
        isDarkMode.value ? "bg-vikinger-dark-100 border-b border-vikinger-dark-50/30" : "bg-white border-b border-gray-200",
        "fixed top-0 left-0 right-0 h-16 z-50 shadow-lg transition-colors duration-300"
      ])}" data-v-a7e5c7cb><div class="h-full px-4 flex items-center justify-between gap-4" data-v-a7e5c7cb><div class="flex items-center gap-3" data-v-a7e5c7cb><button class="hidden lg:flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-vikinger hover:shadow-vikinger-lg transition-all duration-300 hover:scale-110 group relative overflow-hidden" data-v-a7e5c7cb><div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" data-v-a7e5c7cb></div><div class="relative flex flex-col gap-1 w-5" data-v-a7e5c7cb><span class="${ssrRenderClass([isLeftDrawerOpen.value ? "rotate-45 translate-y-1.5" : "", "h-0.5 bg-white rounded-full transition-all duration-300"])}" data-v-a7e5c7cb></span><span class="${ssrRenderClass([isLeftDrawerOpen.value ? "opacity-0 scale-0" : "", "h-0.5 bg-white rounded-full transition-all duration-300"])}" data-v-a7e5c7cb></span><span class="${ssrRenderClass([isLeftDrawerOpen.value ? "-rotate-45 -translate-y-1.5" : "", "h-0.5 bg-white rounded-full transition-all duration-300"])}" data-v-a7e5c7cb></span></div></button><button class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-md hover:shadow-lg transition-all duration-300 hover:scale-110 group relative overflow-hidden" data-v-a7e5c7cb><div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" data-v-a7e5c7cb></div><div class="relative flex flex-col gap-1 w-5" data-v-a7e5c7cb><span class="${ssrRenderClass([isMobileSidebarOpen.value ? "rotate-45 translate-y-1.5" : "", "h-0.5 bg-white rounded-full transition-all duration-300"])}" data-v-a7e5c7cb></span><span class="${ssrRenderClass([isMobileSidebarOpen.value ? "opacity-0 scale-0" : "", "h-0.5 bg-white rounded-full transition-all duration-300"])}" data-v-a7e5c7cb></span><span class="${ssrRenderClass([isMobileSidebarOpen.value ? "-rotate-45 -translate-y-1.5" : "", "h-0.5 bg-white rounded-full transition-all duration-300"])}" data-v-a7e5c7cb></span></div></button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "flex items-center gap-3"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<img${ssrRenderAttr("src", _imports_0)} alt="Plearnd Logo" class="w-10 h-10" data-v-a7e5c7cb${_scopeId}><span class="hidden md:inline-block px-3 py-1 text-lg font-audiowide text-white rounded-lg bg-gradient-vikinger shadow-lg" data-v-a7e5c7cb${_scopeId}>NUXNAN</span>`);
          } else {
            return [
              createVNode("img", {
                src: _imports_0,
                alt: "Plearnd Logo",
                class: "w-10 h-10"
              }),
              createVNode("span", { class: "hidden md:inline-block px-3 py-1 text-lg font-audiowide text-white rounded-lg bg-gradient-vikinger shadow-lg" }, "NUXNAN")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><div class="hidden md:flex items-center gap-2" data-v-a7e5c7cb><!--[-->`);
      ssrRenderList(navigation, (item) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          key: item.href,
          to: item.href,
          class: [
            "flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-300",
            unref(route).path.startsWith(item.href) ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: item.icon,
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="hidden xl:inline" data-v-a7e5c7cb${_scopeId}>${ssrInterpolate(item.name)}</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: item.icon,
                  class: "w-5 h-5"
                }, null, 8, ["icon"]),
                createVNode("span", { class: "hidden xl:inline" }, toDisplayString(item.name), 1)
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div><div class="flex items-center gap-2" data-v-a7e5c7cb>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/points",
        class: [
          "hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl transition-all hover:scale-105",
          isDarkMode.value ? "bg-gradient-to-r from-amber-900/40 to-orange-900/30 hover:from-amber-900/60 hover:to-orange-900/50 border border-amber-500/30" : "bg-gradient-to-r from-amber-50 to-orange-50 hover:from-amber-100 hover:to-orange-100 border border-amber-200"
        ]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-6 h-6 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center" data-v-a7e5c7cb${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:star-24-filled",
              class: "w-3.5 h-3.5 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><span class="${ssrRenderClass([isDarkMode.value ? "text-amber-400" : "text-amber-600", "font-bold text-sm"])}" data-v-a7e5c7cb${_scopeId}>${ssrInterpolate(formatNumber(authUser.value.pp))}</span>`);
          } else {
            return [
              createVNode("div", { class: "w-6 h-6 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:star-24-filled",
                  class: "w-3.5 h-3.5 text-white"
                })
              ]),
              createVNode("span", {
                class: ["font-bold text-sm", isDarkMode.value ? "text-amber-400" : "text-amber-600"]
              }, toDisplayString(formatNumber(authUser.value.pp)), 3)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/earn/wallet",
        class: [
          "hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl transition-all hover:scale-105",
          isDarkMode.value ? "bg-gradient-to-r from-emerald-900/40 to-green-900/30 hover:from-emerald-900/60 hover:to-green-900/50 border border-emerald-500/30" : "bg-gradient-to-r from-emerald-50 to-green-50 hover:from-emerald-100 hover:to-green-100 border border-emerald-200"
        ]
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-6 h-6 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center" data-v-a7e5c7cb${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Icon), {
              icon: "fluent:money-24-filled",
              class: "w-3.5 h-3.5 text-white"
            }, null, _parent2, _scopeId));
            _push2(`</div><span class="${ssrRenderClass([isDarkMode.value ? "text-emerald-400" : "text-emerald-600", "font-bold text-sm"])}" data-v-a7e5c7cb${_scopeId}> \u0E3F${ssrInterpolate(formatNumber(authUser.value.wallet))}</span>`);
          } else {
            return [
              createVNode("div", { class: "w-6 h-6 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center" }, [
                createVNode(unref(Icon), {
                  icon: "fluent:money-24-filled",
                  class: "w-3.5 h-3.5 text-white"
                })
              ]),
              createVNode("span", {
                class: ["font-bold text-sm", isDarkMode.value ? "text-emerald-400" : "text-emerald-600"]
              }, " \u0E3F" + toDisplayString(formatNumber(authUser.value.wallet)), 3)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<button class="${ssrRenderClass([
        isDarkMode.value ? "bg-gradient-to-br from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 shadow-lg shadow-purple-500/30" : "bg-gradient-to-br from-violet-500 to-purple-500 hover:from-violet-400 hover:to-purple-400 shadow-lg shadow-purple-500/30",
        "flex items-center justify-center w-10 h-10 rounded-xl transition-all duration-300 hover:scale-110"
      ])}" title="\u0E2A\u0E41\u0E01\u0E19 QR Code" data-v-a7e5c7cb>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:qr-code-24-filled",
        class: "w-5 h-5 text-white"
      }, null, _parent));
      _push(`</button>`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/profile",
        class: "group"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="w-10 h-10 rounded-full overflow-hidden border-2 border-vikinger-cyan shadow-lg group-hover:border-vikinger-purple group-hover:scale-110 transition-all" data-v-a7e5c7cb${_scopeId}><img${ssrRenderAttr("src", authUser.value.profile_photo_url)}${ssrRenderAttr("alt", authUser.value.name)} class="w-full h-full object-cover" data-v-a7e5c7cb${_scopeId}></div>`);
          } else {
            return [
              createVNode("div", { class: "w-10 h-10 rounded-full overflow-hidden border-2 border-vikinger-cyan shadow-lg group-hover:border-vikinger-purple group-hover:scale-110 transition-all" }, [
                createVNode("img", {
                  src: authUser.value.profile_photo_url,
                  alt: authUser.value.name,
                  class: "w-full h-full object-cover"
                }, null, 8, ["src", "alt"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<button class="${ssrRenderClass([
        isDarkMode.value ? "bg-vikinger-dark-200 hover:bg-vikinger-purple/20" : "bg-gray-100 hover:bg-gray-200",
        "hidden sm:flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300"
      ])}"${ssrRenderAttr("title", isDarkMode.value ? "Switch to Light Mode" : "Switch to Dark Mode")} data-v-a7e5c7cb>`);
      if (isDarkMode.value) {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:weather-sunny-24-filled",
          class: "w-5 h-5 text-yellow-400"
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:weather-moon-24-filled",
          class: "w-5 h-5 text-blue-500"
        }, null, _parent));
      }
      _push(`</button><div class="relative" data-v-a7e5c7cb><button class="${ssrRenderClass([
        isSettingsOpen.value ? "bg-gradient-to-br from-vikinger-purple to-vikinger-cyan text-white shadow-vikinger" : isDarkMode.value ? "bg-vikinger-dark-200 hover:bg-vikinger-purple/20 text-gray-300" : "bg-gray-100 hover:bg-gray-200 text-gray-700",
        "flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300"
      ])}" data-v-a7e5c7cb>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:settings-24-regular",
        class: "w-5 h-5"
      }, null, _parent));
      _push(`</button>`);
      if (isSettingsOpen.value) {
        _push(`<div class="${ssrRenderClass([
          isDarkMode.value ? "bg-vikinger-dark-100 border-vikinger-dark-50/30" : "bg-white border-gray-200",
          "absolute right-0 top-12 w-56 rounded-xl shadow-xl border overflow-hidden z-50"
        ])}" data-v-a7e5c7cb><div class="py-2" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/profile",
          onClick: closeSettings,
          class: ["flex items-center gap-3 px-4 py-3 transition-colors", isDarkMode.value ? "hover:bg-vikinger-dark-200 text-gray-300" : "hover:bg-gray-100 text-gray-700"]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:person-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-a7e5c7cb${_scopeId}>\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:person-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", null, "\u0E42\u0E1B\u0E23\u0E44\u0E1F\u0E25\u0E4C")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: settingsUrl.value,
          onClick: closeSettings,
          class: ["flex items-center gap-3 px-4 py-3 transition-colors", isDarkMode.value ? "hover:bg-vikinger-dark-200 text-gray-300" : "hover:bg-gray-100 text-gray-700"]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-a7e5c7cb${_scopeId}>\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:settings-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", null, "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/notifications",
          onClick: closeSettings,
          class: ["flex items-center gap-3 px-4 py-3 transition-colors", isDarkMode.value ? "hover:bg-vikinger-dark-200 text-gray-300" : "hover:bg-gray-100 text-gray-700"]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:alert-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span data-v-a7e5c7cb${_scopeId}>\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:alert-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", null, "\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        if (authUser.value.is_plearnd_admin) {
          _push(`<div class="${ssrRenderClass([isDarkMode.value ? "border-vikinger-dark-50/30" : "border-gray-200", "border-t my-1"])}" data-v-a7e5c7cb></div>`);
        } else {
          _push(`<!---->`);
        }
        if (authUser.value.is_plearnd_admin) {
          _push(`<div class="${ssrRenderClass([isDarkMode.value ? "text-gray-500" : "text-gray-400", "px-4 py-2 text-xs font-semibold uppercase tracking-wider"])}" data-v-a7e5c7cb> \u0E40\u0E21\u0E19\u0E39\u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A </div>`);
        } else {
          _push(`<!---->`);
        }
        if (authUser.value.is_plearnd_admin) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/PlearndAdmin/Donation/ApproveDonation",
            onClick: closeSettings,
            class: ["flex items-center gap-3 px-4 py-3 transition-colors", isDarkMode.value ? "hover:bg-vikinger-dark-200 text-gray-300" : "hover:bg-gray-100 text-gray-700"]
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:hand-coin",
                  class: "w-5 h-5 text-purple-500"
                }, null, _parent2, _scopeId));
                _push2(`<span data-v-a7e5c7cb${_scopeId}>\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</span>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "mdi:hand-coin",
                    class: "w-5 h-5 text-purple-500"
                  }),
                  createVNode("span", null, "\u0E2D\u0E19\u0E38\u0E21\u0E31\u0E15\u0E34\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        if (authUser.value.is_plearnd_admin) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/Admin/Resetpassword",
            onClick: closeSettings,
            class: ["flex items-center gap-3 px-4 py-3 transition-colors", isDarkMode.value ? "hover:bg-vikinger-dark-200 text-gray-300" : "hover:bg-gray-100 text-gray-700"]
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:lock-reset",
                  class: "w-5 h-5 text-orange-500"
                }, null, _parent2, _scopeId));
                _push2(`<span data-v-a7e5c7cb${_scopeId}>\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19</span>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "mdi:lock-reset",
                    class: "w-5 h-5 text-orange-500"
                  }),
                  createVNode("span", null, "\u0E23\u0E35\u0E40\u0E0B\u0E47\u0E15\u0E23\u0E2B\u0E31\u0E2A\u0E1C\u0E48\u0E32\u0E19")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        if (authUser.value.is_plearnd_admin) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            to: "/admin/support",
            onClick: closeSettings,
            class: ["flex items-center gap-3 px-4 py-3 transition-colors", isDarkMode.value ? "hover:bg-vikinger-dark-200 text-gray-300" : "hover:bg-gray-100 text-gray-700"]
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(ssrRenderComponent(unref(Icon), {
                  icon: "mdi:hand-heart",
                  class: "w-5 h-5 text-green-500"
                }, null, _parent2, _scopeId));
                _push2(`<span data-v-a7e5c7cb${_scopeId}>\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19</span>`);
              } else {
                return [
                  createVNode(unref(Icon), {
                    icon: "mdi:hand-heart",
                    class: "w-5 h-5 text-green-500"
                  }),
                  createVNode("span", null, "\u0E08\u0E31\u0E14\u0E01\u0E32\u0E23\u0E01\u0E32\u0E23\u0E2A\u0E19\u0E31\u0E1A\u0E2A\u0E19\u0E38\u0E19")
                ];
              }
            }),
            _: 1
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="${ssrRenderClass([isDarkMode.value ? "border-vikinger-dark-50/30" : "border-gray-200", "border-t my-1"])}" data-v-a7e5c7cb></div><button class="w-full flex items-center gap-3 px-4 py-3 transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:sign-out-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span data-v-a7e5c7cb>\u0E2D\u0E2D\u0E01\u0E08\u0E32\u0E01\u0E23\u0E30\u0E1A\u0E1A</span></button></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (isSettingsOpen.value) {
        _push(`<div class="fixed inset-0 z-40" data-v-a7e5c7cb></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><button class="${ssrRenderClass([
        isRightDrawerOpen.value ? "bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-vikinger text-white" : isDarkMode.value ? "hover:bg-vikinger-purple/10 text-gray-300" : "hover:bg-gray-100 text-gray-700",
        "hidden lg:flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300 relative overflow-hidden group"
      ])}" data-v-a7e5c7cb>`);
      if (!isRightDrawerOpen.value) {
        _push(`<div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" data-v-a7e5c7cb></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(unref(Icon), {
        icon: isRightDrawerOpen.value ? "fluent:trophy-24-filled" : "fluent:trophy-24-regular",
        class: "w-6 h-6 relative z-10"
      }, null, _parent));
      _push(`</button></div></div></header><div class="pt-16 flex min-h-screen" data-v-a7e5c7cb><aside class="${ssrRenderClass([
        "fixed left-0 top-16 h-[calc(100vh-4rem)] overflow-y-auto transition-all duration-300 z-40",
        "hidden lg:block",
        isLeftDrawerOpen.value ? "w-80" : "w-20",
        isDarkMode.value ? "bg-vikinger-dark-100 border-r border-vikinger-dark-50/30" : "bg-white border-r border-gray-200"
      ])}" data-v-a7e5c7cb>`);
      if (isLeftDrawerOpen.value) {
        _push(`<div class="p-6 space-y-6" data-v-a7e5c7cb><div class="text-center" data-v-a7e5c7cb><div class="relative inline-block mb-4" data-v-a7e5c7cb><img${ssrRenderAttr("src", authUser.value.profile_photo_url)} class="w-24 h-24 rounded-full border-4 border-vikinger-purple shadow-lg"${ssrRenderAttr("alt", authUser.value.name)} data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "border-vikinger-dark-100" : "border-white", "absolute -bottom-2 -right-2 w-10 h-10 bg-gradient-vikinger rounded-full flex items-center justify-center text-white font-bold border-4 transition-colors duration-300"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.level)}</div></div><h3 class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-xl font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.name)}</h3><p class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-600", "text-sm"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.email)}</p></div><div class="flex justify-center gap-2" data-v-a7e5c7cb><div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:trophy-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="w-10 h-10 bg-vikinger-purple rounded-lg flex items-center justify-center" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:shield-checkmark-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:checkmark-circle-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div><div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:star-24-filled",
          class: "w-6 h-6 text-white"
        }, null, _parent));
        _push(`</div></div><div class="${ssrRenderClass([isDarkMode.value ? "border-vikinger-dark-50/30" : "border-gray-200", "grid grid-cols-3 gap-4 text-center py-4 border-y transition-colors duration-300"])}" data-v-a7e5c7cb><div data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-2xl font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.posts)}</div><div class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-600", "text-xs uppercase"])}" data-v-a7e5c7cb> \u0E42\u0E1E\u0E2A\u0E15\u0E4C </div></div><div data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-2xl font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.friends)}</div><div class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-600", "text-xs uppercase"])}" data-v-a7e5c7cb> \u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19 </div></div><div data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-2xl font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.visits)}</div><div class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-600", "text-xs uppercase"])}" data-v-a7e5c7cb> \u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E0A\u0E21 </div></div></div><div class="space-y-1" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/dashboard",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300",
            unref(route).path === "/dashboard" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:grid-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:grid-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/play/newsfeed",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300",
            unref(route).path === "/play/newsfeed" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:chat-bubbles-question-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E1F\u0E35\u0E14\u0E02\u0E48\u0E32\u0E27</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:chat-bubbles-question-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E1F\u0E35\u0E14\u0E02\u0E48\u0E32\u0E27")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/academies",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300",
            unref(route).path.startsWith("/academies") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:school-outline",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:school-outline",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/Learn/Courses",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300",
            unref(route).path.startsWith("/Learn/Courses") || unref(route).path.startsWith("/learn/courses") || unref(route).path.startsWith("/courses") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:book-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<div data-v-a7e5c7cb><button class="${ssrRenderClass([
          unref(route).path.startsWith("/earn") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple",
          "w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-all duration-300"
        ])}" data-v-a7e5c7cb><div class="flex items-center gap-3" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:wallet-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="font-semibold" data-v-a7e5c7cb>\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49</span></div>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: isEarnMenuOpen.value ? "fluent:chevron-up-24-regular" : "fluent:chevron-down-24-regular",
          class: "w-4 h-4 transition-transform duration-200"
        }, null, _parent));
        _push(`</button>`);
        if (isEarnMenuOpen.value) {
          _push(`<div class="ml-4 mt-1 space-y-1 border-l-2 border-vikinger-purple/30 pl-3" data-v-a7e5c7cb><!--[-->`);
          ssrRenderList(earnSubmenu, (sub) => {
            _push(ssrRenderComponent(_component_NuxtLink, {
              key: sub.href,
              to: sub.href,
              class: [
                "flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm",
                unref(route).path === sub.href ? "bg-vikinger-purple/20 text-vikinger-purple dark:text-vikinger-cyan font-medium" : isDarkMode.value ? "text-gray-400 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-600 hover:bg-gray-100 hover:text-vikinger-purple"
              ]
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: sub.icon,
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(`<span data-v-a7e5c7cb${_scopeId}>${ssrInterpolate(sub.name)}</span>`);
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: sub.icon,
                      class: "w-4 h-4"
                    }, null, 8, ["icon"]),
                    createVNode("span", null, toDisplayString(sub.name), 1)
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/notifications",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300",
            unref(route).path === "/notifications" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:alert-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:alert-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: settingsUrl.value,
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300",
            unref(route).path.includes("/settings") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:settings-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else {
        _push(`<div class="p-3 space-y-2 flex flex-col items-center" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/profile",
          class: "mb-4"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<img${ssrRenderAttr("src", authUser.value.profile_photo_url)} class="w-12 h-12 rounded-full border-2 border-vikinger-purple shadow-lg"${ssrRenderAttr("alt", authUser.value.name)} data-v-a7e5c7cb${_scopeId}>`);
            } else {
              return [
                createVNode("img", {
                  src: authUser.value.profile_photo_url,
                  class: "w-12 h-12 rounded-full border-2 border-vikinger-purple shadow-lg",
                  alt: authUser.value.name
                }, null, 8, ["src", "alt"])
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/dashboard",
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path === "/dashboard" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:grid-24-regular",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:grid-24-regular",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/play/newsfeed",
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path === "/play/newsfeed" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E1F\u0E35\u0E14\u0E02\u0E48\u0E32\u0E27"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:chat-bubbles-question-24-regular",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:chat-bubbles-question-24-regular",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/academies",
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path.startsWith("/academies") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:school-outline",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:school-outline",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/Learn/Courses",
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path.startsWith("/Learn/Courses") || unref(route).path.startsWith("/learn/courses") || unref(route).path.startsWith("/courses") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-regular",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:book-24-regular",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/earn/points",
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path.startsWith("/earn") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:wallet-24-regular",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:wallet-24-regular",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/notifications",
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path === "/notifications" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:alert-24-regular",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:alert-24-regular",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: settingsUrl.value,
          class: [
            "w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300",
            unref(route).path.includes("/settings") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan" : "text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple"
          ],
          title: "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-6 h-6"
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:settings-24-regular",
                  class: "w-6 h-6"
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      }
      _push(`</aside><main class="${ssrRenderClass([
        "flex-1 min-h-screen transition-all duration-300",
        isLeftDrawerOpen.value ? "lg:pl-80" : "lg:pl-20",
        isRightDrawerOpen.value ? "lg:pr-80" : "lg:pr-20"
      ])}" data-v-a7e5c7cb><div class="max-w-6xl mx-auto px-4 py-6" data-v-a7e5c7cb>`);
      if (_ctx.$slots.hero) {
        _push(`<div class="w-full mb-6" data-v-a7e5c7cb>`);
        ssrRenderSlot(_ctx.$slots, "hero", {}, null, _push, _parent);
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="grid grid-cols-1 lg:grid-cols-12 gap-6" data-v-a7e5c7cb>`);
      if (_ctx.$slots.leftWidgets) {
        _push(`<div class="hidden lg:block lg:col-span-3 space-y-4" data-v-a7e5c7cb>`);
        ssrRenderSlot(_ctx.$slots, "leftWidgets", {}, null, _push, _parent);
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="${ssrRenderClass([
        "w-full",
        _ctx.$slots.leftWidgets && _ctx.$slots.rightWidgets ? "lg:col-span-6" : _ctx.$slots.leftWidgets || _ctx.$slots.rightWidgets ? "lg:col-span-9" : "lg:col-span-12"
      ])}" data-v-a7e5c7cb>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
      if (_ctx.$slots.rightWidgets) {
        _push(`<div class="hidden lg:block lg:col-span-3 space-y-4" data-v-a7e5c7cb>`);
        ssrRenderSlot(_ctx.$slots, "rightWidgets", {}, null, _push, _parent);
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div></main><aside class="${ssrRenderClass([
        "fixed right-0 top-16 h-[calc(100vh-4rem)] overflow-y-auto transition-all duration-300 z-40",
        "hidden lg:block",
        isRightDrawerOpen.value ? "w-80" : "w-20",
        isDarkMode.value ? "bg-vikinger-dark-100 border-l border-vikinger-dark-50/30" : "bg-white border-l border-gray-200"
      ])}" data-v-a7e5c7cb>`);
      if (isRightDrawerOpen.value) {
        _push(`<div class="p-6 space-y-6" data-v-a7e5c7cb><div class="flex items-center justify-between" data-v-a7e5c7cb><h3 class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-lg font-bold"])}" data-v-a7e5c7cb> \u0E01\u0E23\u0E30\u0E14\u0E32\u0E19\u0E1C\u0E39\u0E49\u0E19\u0E33 </h3><span class="px-2 py-1 rounded-lg bg-vikinger-purple text-white text-[10px] font-bold uppercase tracking-wider" data-v-a7e5c7cb>\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E22\u0E2D\u0E14\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21</span></div><div class="relative" data-v-a7e5c7cb><input type="text" placeholder="\u0E04\u0E49\u0E19\u0E2B\u0E32\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01..." class="${ssrRenderClass([
          isDarkMode.value ? "bg-vikinger-dark-200 border-vikinger-dark-50/30 text-white placeholder-gray-400 focus:border-vikinger-purple" : "bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 focus:border-vikinger-purple",
          "w-full px-4 py-2 pl-10 rounded-lg border transition-colors duration-300 focus:ring-2 focus:ring-vikinger-purple/20"
        ])}" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:search-24-regular",
          class: ["absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5", isDarkMode.value ? "text-gray-400" : "text-gray-500"]
        }, null, _parent));
        _push(`</div>`);
        if (unref(isGamificationLoading) && !leaderboard.value.length) {
          _push(`<div class="space-y-3 animate-pulse" data-v-a7e5c7cb><!--[-->`);
          ssrRenderList(10, (i) => {
            _push(`<div class="flex items-center gap-3 p-2" data-v-a7e5c7cb><div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-200 rounded-full" data-v-a7e5c7cb></div><div class="flex-1 space-y-2" data-v-a7e5c7cb><div class="h-3 bg-gray-200 dark:bg-vikinger-dark-200 rounded w-2/3" data-v-a7e5c7cb></div><div class="h-2 bg-gray-200 dark:bg-vikinger-dark-200 rounded w-1/2" data-v-a7e5c7cb></div></div></div>`);
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<div class="space-y-3" data-v-a7e5c7cb><!--[-->`);
          ssrRenderList(leaderboard.value, (user, index) => {
            _push(`<div class="${ssrRenderClass([isDarkMode.value ? "hover:bg-vikinger-dark-200" : "hover:bg-gray-100", "flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors group"])}" data-v-a7e5c7cb><div class="relative" data-v-a7e5c7cb><img${ssrRenderAttr("src", getAvatarUrl(user, index))} class="w-10 h-10 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors bg-white object-cover" data-v-a7e5c7cb><div class="${ssrRenderClass([[
              getRankColor(index),
              isDarkMode.value ? "border-vikinger-dark-100" : "border-white"
            ], "absolute -top-1 -left-1 w-5 h-5 rounded-full border-2 flex items-center justify-center text-[10px] font-bold text-white shadow-sm"])}" data-v-a7e5c7cb>${ssrInterpolate(index + 1)}</div></div><div class="flex-1 min-w-0" data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-sm font-semibold truncate"])}" data-v-a7e5c7cb>${ssrInterpolate(user.name)}</div><div class="flex items-center gap-3 mt-1" data-v-a7e5c7cb><div class="flex items-center gap-1 text-[10px] font-bold text-vikinger-purple" title="\u0E41\u0E15\u0E49\u0E21\u0E2A\u0E30\u0E2A\u0E21 (PP)" data-v-a7e5c7cb><img${ssrRenderAttr("src", _imports_1)} class="w-3.5 h-3.5" alt="pp" data-v-a7e5c7cb> ${ssrInterpolate(formatNumber(user.points || 0))}</div></div></div><div class="shrink-0 group-hover:scale-110 transition-transform" data-v-a7e5c7cb>`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: "fluent:star-24-filled",
              class: "w-4 h-4 text-vikinger-yellow"
            }, null, _parent));
            _push(`</div></div>`);
          });
          _push(`<!--]-->`);
          if (!leaderboard.value.length) {
            _push(`<div class="text-center py-4 text-gray-500 dark:text-gray-400 text-xs" data-v-a7e5c7cb> \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E25\u0E33\u0E14\u0E31\u0E1A </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      if (!isRightDrawerOpen.value) {
        _push(`<div class="p-2 pt-6 space-y-3 flex flex-col items-center" data-v-a7e5c7cb><!--[-->`);
        ssrRenderList(leaderboard.value.slice(0, 10), (user, index) => {
          _push(`<div class="relative cursor-pointer transition-transform hover:scale-110 group"${ssrRenderAttr("title", `Rank ${index + 1}: ${user.name}`)} data-v-a7e5c7cb><img${ssrRenderAttr("src", getAvatarUrl(user, index))} class="w-11 h-11 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors bg-white object-cover" data-v-a7e5c7cb><div class="${ssrRenderClass([[
            getRankColor(index),
            isDarkMode.value ? "border-vikinger-dark-100" : "border-white"
          ], "absolute -top-1 -left-1 w-5 h-5 rounded-full border-2 flex items-center justify-center text-[9px] font-bold text-white shadow-sm"])}" data-v-a7e5c7cb>${ssrInterpolate(index + 1)}</div></div>`);
        });
        _push(`<!--]--></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</aside></div>`);
      if (isMobileSidebarOpen.value) {
        _push(`<div class="fixed inset-0 z-50 lg:hidden" data-v-a7e5c7cb><div class="absolute inset-0 bg-black/50" data-v-a7e5c7cb></div><aside class="${ssrRenderClass([isDarkMode.value ? "bg-vikinger-dark-100" : "bg-white", "absolute left-0 top-0 h-full w-80 max-w-[85vw] overflow-y-auto transition-colors duration-300"])}" data-v-a7e5c7cb><div class="p-6 space-y-6" data-v-a7e5c7cb><button class="${ssrRenderClass([isDarkMode.value ? "hover:bg-vikinger-purple/10" : "hover:bg-gray-100", "absolute top-4 right-4 p-2 rounded-full transition-colors"])}" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "mdi:close",
          class: ["w-6 h-6", isDarkMode.value ? "text-gray-300" : "text-gray-700"]
        }, null, _parent));
        _push(`</button><div class="text-center" data-v-a7e5c7cb><div class="relative inline-block mb-3" data-v-a7e5c7cb><img${ssrRenderAttr("src", authUser.value.profile_photo_url)} class="w-20 h-20 rounded-full mx-auto border-4 border-vikinger-purple" data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "border-vikinger-dark-100" : "border-white", "absolute -bottom-1 -right-1 w-8 h-8 bg-gradient-vikinger rounded-full flex items-center justify-center text-white text-xs font-bold border-2"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.level)}</div></div><h3 class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "text-lg font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.name)}</h3><p class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-500", "text-sm"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.email)}</p></div><div class="${ssrRenderClass([isDarkMode.value ? "border-vikinger-dark-50/30" : "border-gray-200", "flex justify-center gap-6 py-3 border-y"])}" data-v-a7e5c7cb><div class="text-center" data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.posts)}</div><div class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-500", "text-xs uppercase"])}" data-v-a7e5c7cb>\u0E42\u0E1E\u0E2A\u0E15\u0E4C</div></div><div class="text-center" data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.friends)}</div><div class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-500", "text-xs uppercase"])}" data-v-a7e5c7cb>\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E19</div></div><div class="text-center" data-v-a7e5c7cb><div class="${ssrRenderClass([isDarkMode.value ? "text-white" : "text-gray-900", "font-bold"])}" data-v-a7e5c7cb>${ssrInterpolate(authUser.value.visits)}</div><div class="${ssrRenderClass([isDarkMode.value ? "text-gray-400" : "text-gray-500", "text-xs uppercase"])}" data-v-a7e5c7cb>\u0E40\u0E22\u0E35\u0E48\u0E22\u0E21\u0E0A\u0E21</div></div></div><nav class="space-y-1" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/dashboard",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-colors",
            unref(route).path === "/dashboard" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:grid-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:grid-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E41\u0E14\u0E0A\u0E1A\u0E2D\u0E23\u0E4C\u0E14")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/play/newsfeed",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-colors",
            unref(route).path === "/play/newsfeed" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:chat-bubbles-question-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E1F\u0E35\u0E14\u0E02\u0E48\u0E32\u0E27</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:chat-bubbles-question-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E1F\u0E35\u0E14\u0E02\u0E48\u0E32\u0E27")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/academies",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-colors",
            unref(route).path.startsWith("/academies") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "mdi:school-outline",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "mdi:school-outline",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E42\u0E23\u0E07\u0E40\u0E23\u0E35\u0E22\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/Learn/Courses",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-colors",
            unref(route).path.startsWith("/Learn/Courses") || unref(route).path.startsWith("/learn/courses") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:book-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:book-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E04\u0E2D\u0E23\u0E4C\u0E2A\u0E40\u0E23\u0E35\u0E22\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<div data-v-a7e5c7cb><button class="${ssrRenderClass([
          unref(route).path.startsWith("/earn") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100",
          "w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-colors"
        ])}" data-v-a7e5c7cb><div class="flex items-center gap-3" data-v-a7e5c7cb>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:wallet-24-regular",
          class: "w-5 h-5"
        }, null, _parent));
        _push(`<span class="font-semibold" data-v-a7e5c7cb>\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49</span></div>`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: isEarnMenuOpen.value ? "fluent:chevron-up-24-regular" : "fluent:chevron-down-24-regular",
          class: "w-4 h-4"
        }, null, _parent));
        _push(`</button>`);
        if (isEarnMenuOpen.value) {
          _push(`<div class="ml-4 mt-1 space-y-1 border-l-2 border-vikinger-purple/30 pl-3" data-v-a7e5c7cb><!--[-->`);
          ssrRenderList(earnSubmenu, (sub) => {
            _push(ssrRenderComponent(_component_NuxtLink, {
              key: sub.href,
              to: sub.href,
              class: [
                "flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-sm",
                unref(route).path === sub.href ? "bg-vikinger-purple/20 text-vikinger-purple dark:text-vikinger-cyan font-medium" : isDarkMode.value ? "text-gray-400 hover:bg-vikinger-purple/10" : "text-gray-600 hover:bg-gray-100"
              ]
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(unref(Icon), {
                    icon: sub.icon,
                    class: "w-4 h-4"
                  }, null, _parent2, _scopeId));
                  _push2(`<span data-v-a7e5c7cb${_scopeId}>${ssrInterpolate(sub.name)}</span>`);
                } else {
                  return [
                    createVNode(unref(Icon), {
                      icon: sub.icon,
                      class: "w-4 h-4"
                    }, null, 8, ["icon"]),
                    createVNode("span", null, toDisplayString(sub.name), 1)
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]--></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: "/notifications",
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-colors",
            unref(route).path === "/notifications" ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:alert-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:alert-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E01\u0E32\u0E23\u0E41\u0E08\u0E49\u0E07\u0E40\u0E15\u0E37\u0E2D\u0E19")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: settingsUrl.value,
          class: [
            "flex items-center gap-3 px-4 py-3 rounded-lg transition-colors",
            unref(route).path.includes("/settings") ? "bg-gradient-vikinger text-white shadow-vikinger" : isDarkMode.value ? "text-gray-300 hover:bg-vikinger-purple/10" : "text-gray-700 hover:bg-gray-100"
          ]
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(unref(Icon), {
                icon: "fluent:settings-24-regular",
                class: "w-5 h-5"
              }, null, _parent2, _scopeId));
              _push2(`<span class="font-semibold" data-v-a7e5c7cb${_scopeId}>\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32</span>`);
            } else {
              return [
                createVNode(unref(Icon), {
                  icon: "fluent:settings-24-regular",
                  class: "w-5 h-5"
                }),
                createVNode("span", { class: "font-semibold" }, "\u0E15\u0E31\u0E49\u0E07\u0E04\u0E48\u0E32")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</nav></div></aside></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(_component_QrUniversalQRModal, {
        modelValue: isQRScannerOpen.value,
        "onUpdate:modelValue": ($event) => isQRScannerOpen.value = $event,
        onActionComplete: onQRActionComplete
      }, null, _parent));
      _push(ssrRenderComponent(_component_LayoutBottomNav, null, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/main.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const MainLayout = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-a7e5c7cb"]]);

export { MainLayout as default };
//# sourceMappingURL=main-CdHCodS1.mjs.map
