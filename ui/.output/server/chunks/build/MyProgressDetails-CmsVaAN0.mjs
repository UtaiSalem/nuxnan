import { _ as __nuxt_component_0 } from './nuxt-link-Dhr1c_cd.mjs';
import * as Vue from 'vue';
import { inject, ref, computed, mergeProps, unref, withCtx, createVNode, toDisplayString, createTextVNode, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import { i as useApi } from './server.mjs';
import { Icon } from '@iconify/vue';
import { _ as _sfc_main$1 } from './AssignmentSubmissionForm-BYC_TqBC.mjs';
import { u as useSweetAlert } from './useSweetAlert-jHixiibP.mjs';

function getDefaultExportFromCjs (x) {
	return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, 'default') ? x['default'] : x;
}

function getDefaultExportFromNamespaceIfNotNamed (n) {
	return n && Object.prototype.hasOwnProperty.call(n, 'default') && Object.keys(n).length === 1 ? n['default'] : n;
}

const require$$0 = /*@__PURE__*/getDefaultExportFromNamespaceIfNotNamed(Vue);

var vue=require$$0;function _slicedToArray(arr, i) {
  return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest();
}

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

function _iterableToArrayLimit(arr, i) {
  var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"];

  if (_i == null) return;
  var _arr = [];
  var _n = true;
  var _d = false;

  var _s, _e;

  try {
    for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

  return arr2;
}

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}function randomString() {
  return Math.random().toString(16).substring(2);
}var script = vue.defineComponent({
  props: {
    // Sets width/diameter of the inner stroke.
    diameter: {
      type: Number,
      required: false,
      default: 200
    },
    // Sets the total steps/progress to the end.
    totalSteps: {
      type: Number,
      required: true,
      default: 10
    },
    // Sets the current progress of the inner stroke.
    completedSteps: {
      type: Number,
      required: true,
      default: 0
    },
    // Sets the start color of the inner stroke (gradient).
    startColor: {
      type: String,
      required: false,
      default: "#00C58E"
    },
    // Sets the end color of the inner stroke (gradient).
    stopColor: {
      type: String,
      required: false,
      default: "#00E0A1"
    },
    // Sets the color of the inner stroke to be applied to the shape.
    innerStrokeColor: {
      type: String,
      required: false,
      default: "#2F495E"
    },
    // Sets the width of the stroke to be applied to the shape.
    // Read more: https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/stroke-width
    strokeWidth: {
      type: Number,
      required: false,
      default: 10
    },
    // Sets the  width of the inner stroke to be applied to the shape.
    innerStrokeWidth: {
      type: Number,
      required: false,
      default: 10
    },
    // Sets the shape to be used at the end of stroked.
    // Read more: https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/stroke-linecap
    strokeLinecap: {
      type: String,
      required: false,
      default: "round"
    },
    // Sets how long the animation should take to complete one cycle.
    // Read more: https://www.w3schools.com/cssref/css3_pr_animation-duration.asp
    animateSpeed: {
      type: Number,
      required: false,
      default: 1000
    },
    // Sets the frames per seconds to update inner stroke animation.
    fps: {
      type: Number,
      required: false,
      default: 60
    },
    // Sets how the animation progresses through the duration of each cycle.
    // Read more: https://developer.mozilla.org/en-US/docs/Web/CSS/animation-timing-function
    timingFunc: {
      type: String,
      required: false,
      default: "linear"
    },
    // Sets the inner stroke direction.
    isClockwise: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  setup: function setup(props) {
    var gradient = vue.reactive({
      fx: 0.99,
      fy: 0.5,
      cx: 0.5,
      cy: 0.5,
      r: 0.65
    });
    var radialGradientId = "rg-".concat(randomString());
    var strokeDashoffset = vue.ref(0);
    var currentAngle = vue.ref(0);
    var gradientAnimation = vue.ref(null);
    var radius = vue.computed(function () {
      return props.diameter / 2;
    });
    var innerCircleDiameter = vue.computed(function () {
      return props.diameter - props.innerStrokeWidth * 2;
    });
    var circumference = vue.computed(function () {
      return Math.PI * innerCircleDiameter.value;
    });
    var stepSize = vue.computed(function () {
      return props.totalSteps === 0 ? 0 : 100 / props.totalSteps;
    });
    var finishedPercentage = vue.computed(function () {
      return stepSize.value * props.completedSteps;
    });
    var circleSlice = vue.computed(function () {
      return 2 * Math.PI / props.totalSteps;
    });
    var animationIncrements = vue.computed(function () {
      return 100 / props.fps;
    });
    var totalPoints = vue.computed(function () {
      return props.animateSpeed / animationIncrements.value;
    });
    var animateSlice = vue.computed(function () {
      return circleSlice.value / totalPoints.value;
    });
    var innerCircleRadius = vue.computed(function () {
      return innerCircleDiameter.value / 2;
    });
    var containerStyle = vue.computed(function () {
      return {
        height: "".concat(props.diameter, "px"),
        width: "".concat(props.diameter, "px")
      };
    });
    var progressStyle = vue.computed(function () {
      return {
        height: "".concat(props.diameter, "px"),
        width: "".concat(props.diameter, "px"),
        strokeWidth: "".concat(props.strokeWidth, "px"),
        strokeDashoffset: strokeDashoffset.value,
        transition: "stroke-dashoffset ".concat(props.animateSpeed, "ms ").concat(props.timingFunc)
      };
    });
    var strokeStyle = vue.computed(function () {
      return {
        height: "".concat(props.diameter, "px"),
        width: "".concat(props.diameter, "px"),
        strokeWidth: "".concat(props.innerStrokeWidth, "px")
      };
    });
    var innerCircleStyle = vue.computed(function () {
      return {
        width: "".concat(innerCircleDiameter.value, "px")
      };
    });
    vue.watch(function () {
      return [props.diameter, props.totalSteps, props.completedSteps, props.strokeWidth];
    }, changeProgress, {
      immediate: true
    });

    function getPointOfCircle(angle) {
      var radius = 0.5;
      var x = radius + radius * Math.cos(angle);
      var y = radius + radius * Math.sin(angle);
      return {
        x: x,
        y: y
      };
    }

    function gotoPoint() {
      var point = getPointOfCircle(currentAngle.value);

      if (point.x && point.y) {
        gradient.fx = point.x;
        gradient.fy = point.y;
      }
    }

    function direction() {
      return props.isClockwise ? 1 : -1;
    }

    function changeProgress() {
      strokeDashoffset.value = (100 - finishedPercentage.value) / 100 * circumference.value * direction();

      if (gradientAnimation.value) {
        clearInterval(gradientAnimation.value);
      }

      var angleOffset = (props.completedSteps - 1) * circleSlice.value;
      var i = (currentAngle.value - angleOffset) / animateSlice.value;
      var incrementer = Math.abs(i - totalPoints.value) / totalPoints.value;
      var isMoveForward = i < totalPoints.value;
      gradientAnimation.value = setInterval(function () {
        if (isMoveForward && i >= totalPoints.value || !isMoveForward && i < totalPoints.value) {
          gradientAnimation.value && clearInterval(gradientAnimation.value);
          return;
        }

        currentAngle.value = angleOffset + animateSlice.value * i;
        gotoPoint();
        i += isMoveForward ? incrementer : -incrementer;
      }, animationIncrements.value);
    }

    return {
      gradientAnimation: gradientAnimation,
      innerCircleRadius: innerCircleRadius,
      radialGradientId: radialGradientId,
      strokeDashoffset: strokeDashoffset,
      innerCircleStyle: innerCircleStyle,
      containerStyle: containerStyle,
      circumference: circumference,
      progressStyle: progressStyle,
      currentAngle: currentAngle,
      strokeStyle: strokeStyle,
      gradient: gradient,
      radius: radius
    };
  }
});var _hoisted_1 = ["width", "height"];
var _hoisted_2 = ["id", "fx", "fy", "cx", "cy", "r"];
var _hoisted_3 = ["stop-color"];
var _hoisted_4 = ["stop-color"];
var _hoisted_5 = ["r", "cx", "cy", "stroke", "stroke-dasharray", "stroke-linecap"];
var _hoisted_6 = ["transform", "r", "cx", "cy", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  return vue.openBlock(), vue.createElementBlock("div", {
    class: "vrp__wrapper",
    style: vue.normalizeStyle(_ctx.containerStyle)
  }, [vue.createElementVNode("div", {
    class: "vrp__inner",
    style: vue.normalizeStyle(_ctx.innerCircleStyle)
  }, [vue.renderSlot(_ctx.$slots, "default")], 4), (vue.openBlock(), vue.createElementBlock("svg", {
    width: _ctx.diameter,
    height: _ctx.diameter,
    version: "1.1",
    xmlns: "http://www.w3.org/2000/svg"
  }, [vue.createElementVNode("defs", null, [vue.createElementVNode("radialGradient", {
    id: _ctx.radialGradientId,
    fx: _ctx.gradient.fx,
    fy: _ctx.gradient.fy,
    cx: _ctx.gradient.cx,
    cy: _ctx.gradient.cy,
    r: _ctx.gradient.r
  }, [vue.createElementVNode("stop", {
    offset: "30%",
    "stop-color": _ctx.startColor
  }, null, 8, _hoisted_3), vue.createElementVNode("stop", {
    offset: "100%",
    "stop-color": _ctx.stopColor
  }, null, 8, _hoisted_4)], 8, _hoisted_2)]), vue.createElementVNode("circle", {
    r: _ctx.innerCircleRadius,
    cx: _ctx.radius,
    cy: _ctx.radius,
    fill: "transparent",
    stroke: _ctx.innerStrokeColor,
    "stroke-dasharray": _ctx.circumference,
    "stroke-dashoffset": "0",
    "stroke-linecap": _ctx.strokeLinecap,
    style: vue.normalizeStyle(_ctx.strokeStyle)
  }, null, 12, _hoisted_5), vue.createElementVNode("circle", {
    transform: 'rotate(270, ' + _ctx.radius + ',' + _ctx.radius + ')',
    r: _ctx.innerCircleRadius,
    cx: _ctx.radius,
    cy: _ctx.radius,
    fill: "transparent",
    stroke: "url('#".concat(_ctx.radialGradientId, "')"),
    "stroke-dasharray": _ctx.circumference,
    "stroke-dashoffset": _ctx.circumference,
    "stroke-linecap": _ctx.strokeLinecap,
    style: vue.normalizeStyle(_ctx.progressStyle)
  }, null, 12, _hoisted_6)], 8, _hoisted_1))], 4);
}function styleInject(css, ref) {
  if ( ref === void 0 ) ref = {};
  var insertAt = ref.insertAt;

  if (typeof document === 'undefined') { return; }

  var head = document.head || document.getElementsByTagName('head')[0];
  var style = document.createElement('style');
  style.type = 'text/css';

  if (insertAt === 'top') {
    if (head.firstChild) {
      head.insertBefore(style, head.firstChild);
    } else {
      head.appendChild(style);
    }
  } else {
    head.appendChild(style);
  }

  if (style.styleSheet) {
    style.styleSheet.cssText = css;
  } else {
    style.appendChild(document.createTextNode(css));
  }
}var css_248z = "\n.vrp__wrapper[data-v-6a0cf1f6] {\r\n  position: relative;\n}\n.vrp__inner[data-v-6a0cf1f6] {\r\n  position: absolute;\r\n  top: 0;\r\n  right: 0;\r\n  bottom: 0;\r\n  left: 0;\r\n  border-radius: 50%;\r\n  margin: 0 auto;\r\n  display: flex;\r\n  flex-direction: column;\r\n  align-items: center;\r\n  justify-content: center;\n}\r\n";
styleInject(css_248z);script.render = render;
script.__scopeId = "data-v-6a0cf1f6";// Default export is installable instance of component.
// IIFE injects install function into component, allowing component
// to be registered via Vue.use() as well as Vue.component(),
var component = /*#__PURE__*/(function () {
  // Assign InstallableComponent type
  var installable = script; // Attach install function executed by Vue.use()

  installable.install = function (app) {
    app.component("RadialProgressBar", installable);
  };

  return installable;
})(); // It's possible to expose named exports when writing components that can
// also be used as directives, etc. - eg. import { RollupDemoDirective } from 'rollup-demo';
// export const RollupDemoDirective = directive;
var namedExports=/*#__PURE__*/Object.freeze({__proto__:null,'default':component});Object.entries(namedExports).forEach(function (_ref) {
  var _ref2 = _slicedToArray(_ref, 2),
      exportName = _ref2[0],
      exported = _ref2[1];

  if (exportName !== "default") component[exportName] = exported;
});var RadialProgressBar_ssr=component;

const RadialProgress = /*@__PURE__*/getDefaultExportFromCjs(RadialProgressBar_ssr);

const _sfc_main = {
  __name: "MyProgressDetails",
  __ssrInlineRender: true,
  props: {
    courseId: { type: [String, Number], required: true },
    memberId: { type: [String, Number], required: true }
  },
  setup(__props) {
    const props = __props;
    const api = useApi();
    const swal = useSweetAlert();
    const isCourseAdmin = inject("isCourseAdmin", ref(false));
    const loading = ref(true);
    const data = ref(null);
    const expandedAssignmentId = ref(null);
    const answerLoading = ref(false);
    const gradingAnswer = ref(null);
    const onSubmitted = async () => {
      swal.toast("\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22\u0E41\u0E25\u0E49\u0E27", "success");
      expandedAssignmentId.value = null;
      await fetchData();
    };
    const fetchData = async () => {
      var _a;
      loading.value = true;
      try {
        const res = await api.get(`/api/courses/${props.courseId}/members/${props.memberId}/progress`);
        data.value = {
          assignments: [],
          quizzes: [],
          lessons: [],
          ...res
        };
        if (data.value.member) {
          form.value = {
            member_name: data.value.member.member_name || ((_a = data.value.member.user) == null ? void 0 : _a.name) || "",
            member_code: data.value.member.member_code || "",
            order_number: data.value.member.order_number || "",
            group_id: data.value.member.group_id || null
          };
        }
      } catch (e) {
        console.error(e);
      } finally {
        loading.value = false;
      }
    };
    const stats = computed(() => {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m;
      if (!data.value) return {};
      const d = data.value;
      const totalScore = (((_a = d.assignments) == null ? void 0 : _a.reduce((sum, a) => sum + (a.score || 0), 0)) || 0) + (((_b = d.quizzes) == null ? void 0 : _b.reduce((sum, q) => sum + (q.score || 0), 0)) || 0);
      const maxScore = (((_c = d.assignments) == null ? void 0 : _c.reduce((sum, a) => sum + (a.max_score || 0), 0)) || 0) + (((_d = d.quizzes) == null ? void 0 : _d.reduce((sum, q) => sum + (q.max_score || 0), 0)) || 0);
      return {
        totalScore,
        maxScore,
        grade: ((_e = d.member) == null ? void 0 : _e.grade_name) || "-",
        completedLessons: ((_f = d.lessons) == null ? void 0 : _f.filter((l) => l.completed).length) || 0,
        totalLessons: ((_g = d.lessons) == null ? void 0 : _g.length) || 0,
        completedAssignments: ((_h = d.assignments) == null ? void 0 : _h.filter((a) => a.submitted).length) || 0,
        totalAssignments: ((_i = d.assignments) == null ? void 0 : _i.length) || 0,
        completedQuizzes: ((_j = d.quizzes) == null ? void 0 : _j.filter((q) => q.completed).length) || 0,
        totalQuizzes: ((_k = d.quizzes) == null ? void 0 : _k.length) || 0,
        groupName: ((_m = (_l = d.member) == null ? void 0 : _l.group) == null ? void 0 : _m.name) || "\u0E44\u0E21\u0E48\u0E21\u0E35\u0E01\u0E25\u0E38\u0E48\u0E21",
        scorePercent: maxScore > 0 ? totalScore / maxScore * 100 : 0
      };
    });
    const form = ref({
      member_name: "",
      member_code: "",
      order_number: "",
      group_id: null
    });
    const isSaving = ref(false);
    const saveSuccess = ref(false);
    const getScoreColor = (score, max) => {
      if (!max) return "text-gray-500";
      const pct = score / max * 100;
      if (pct >= 80) return "text-green-600";
      if (pct >= 50) return "text-blue-600";
      return "text-red-600";
    };
    const getProgressBarColor = (score, max) => {
      if (!max) return "bg-gray-400 dark:bg-gray-600";
      const pct = score / max * 100;
      if (pct >= 80) return "bg-gradient-to-r from-green-400 to-green-500";
      if (pct >= 50) return "bg-gradient-to-r from-blue-400 to-blue-500";
      return "bg-gradient-to-r from-red-400 to-red-500";
    };
    const canShowScore = computed(() => {
      var _a, _b;
      if (isCourseAdmin.value) return true;
      if ((_b = (_a = data.value) == null ? void 0 : _a.member) == null ? void 0 : _b.order_number) return true;
      return false;
    });
    const activeTab = ref("lessons");
    const tabs = [
      { id: "lessons", label: "\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19", icon: "fluent:book-open-24-filled" },
      { id: "assignments", label: "\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22", icon: "fluent:document-text-24-filled" },
      { id: "quizzes", label: "\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A", icon: "fluent:quiz-new-24-filled" }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "space-y-6" }, _attrs))}>`);
      if (loading.value) {
        _push(`<div class="flex justify-center py-12">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "eos-icons:loading",
          class: "w-10 h-10 text-blue-600"
        }, null, _parent));
        _push(`</div>`);
      } else if (data.value) {
        _push(`<div class="animate-fade-in"><div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6"><div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4"><div><h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:person-edit-24-filled",
          class: "text-blue-500"
        }, null, _parent));
        _push(` \u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E2A\u0E48\u0E27\u0E19\u0E15\u0E31\u0E27 `);
        if (((_a = data.value.member) == null ? void 0 : _a.role) === 4) {
          _push(`<span class="px-2 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-700 rounded-full border border-purple-200"> \u0E1C\u0E39\u0E49\u0E14\u0E39\u0E41\u0E25\u0E23\u0E30\u0E1A\u0E1A (Admin) </span>`);
        } else {
          _push(`<span class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 rounded-full border border-blue-200"> \u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19 (Student) </span>`);
        }
        _push(`</h3><p class="text-sm text-gray-500">\u0E41\u0E01\u0E49\u0E44\u0E02\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E1E\u0E37\u0E49\u0E19\u0E10\u0E32\u0E19\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49</p></div><div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium"> \u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19: ${ssrInterpolate(stats.value.groupName)}</div></div><div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 (Order No.)</label><input${ssrRenderAttr("value", form.value.order_number)} type="number" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48..."></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27 (Student ID)</label><input${ssrRenderAttr("value", form.value.member_code)} type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E23\u0E2B\u0E31\u0E2A\u0E1B\u0E23\u0E30\u0E08\u0E33\u0E15\u0E31\u0E27..."></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25 (Name)</label><input${ssrRenderAttr("value", form.value.member_name)} type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="\u0E23\u0E30\u0E1A\u0E38\u0E0A\u0E37\u0E48\u0E2D-\u0E19\u0E32\u0E21\u0E2A\u0E01\u0E38\u0E25..."></div><div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">\u0E01\u0E25\u0E38\u0E48\u0E21\u0E40\u0E23\u0E35\u0E22\u0E19 (Group)</label><select class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500"><option${ssrRenderAttr("value", null)}${ssrIncludeBooleanAttr(Array.isArray(form.value.group_id) ? ssrLooseContain(form.value.group_id, null) : ssrLooseEqual(form.value.group_id, null)) ? " selected" : ""}>-- \u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E01\u0E25\u0E38\u0E48\u0E21 --</option><!--[-->`);
        ssrRenderList(data.value.groups, (group) => {
          _push(`<option${ssrRenderAttr("value", group.id)}${ssrIncludeBooleanAttr(Array.isArray(form.value.group_id) ? ssrLooseContain(form.value.group_id, group.id) : ssrLooseEqual(form.value.group_id, group.id)) ? " selected" : ""}>${ssrInterpolate(group.name)}</option>`);
        });
        _push(`<!--]--></select></div></div><div class="mt-6 flex items-center justify-end gap-3">`);
        if (saveSuccess.value) {
          _push(`<span class="text-green-600 text-sm flex items-center animate-fade-in">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:checkmark-circle-24-filled",
            class: "mr-1"
          }, null, _parent));
          _push(` \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E40\u0E23\u0E35\u0E22\u0E1A\u0E23\u0E49\u0E2D\u0E22 </span>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<button${ssrIncludeBooleanAttr(isSaving.value) ? " disabled" : ""} class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed">`);
        if (isSaving.value) {
          _push(ssrRenderComponent(unref(Icon), {
            icon: "eos-icons:loading",
            class: "mr-2 animate-spin"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(` ${ssrInterpolate(isSaving.value ? "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01..." : "\u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01\u0E01\u0E32\u0E23\u0E40\u0E1B\u0E25\u0E35\u0E48\u0E22\u0E19\u0E41\u0E1B\u0E25\u0E07")}</button></div></div>`);
        if (!canShowScore.value) {
          _push(`<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 text-center mb-8">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: "fluent:warning-24-filled",
            class: "w-12 h-12 text-yellow-500 mx-auto mb-2"
          }, null, _parent));
          _push(`<h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-200">\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48 (Order Number)</h3><p class="text-yellow-700 dark:text-yellow-300 mt-1"> \u0E01\u0E23\u0E38\u0E13\u0E32\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E02\u0E2D\u0E07\u0E04\u0E38\u0E13 \u0E2B\u0E23\u0E37\u0E2D\u0E15\u0E34\u0E14\u0E15\u0E48\u0E2D\u0E1C\u0E39\u0E49\u0E2A\u0E2D\u0E19\u0E40\u0E1E\u0E37\u0E48\u0E2D\u0E15\u0E23\u0E27\u0E08\u0E2A\u0E2D\u0E1A\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25 <br> (\u0E23\u0E30\u0E1A\u0E1A\u0E41\u0E2A\u0E14\u0E07\u0E04\u0E30\u0E41\u0E19\u0E19\u0E2A\u0E33\u0E2B\u0E23\u0E31\u0E1A\u0E19\u0E31\u0E01\u0E40\u0E23\u0E35\u0E22\u0E19\u0E17\u0E35\u0E48\u0E21\u0E35\u0E40\u0E25\u0E02\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27\u0E40\u0E17\u0E48\u0E32\u0E19\u0E31\u0E49\u0E19) </p></div>`);
        } else {
          _push(`<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8"><div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center"><div class="text-sm text-gray-500 mb-2">\u0E40\u0E01\u0E23\u0E14\u0E1B\u0E31\u0E08\u0E08\u0E38\u0E1A\u0E31\u0E19</div>`);
          _push(ssrRenderComponent(unref(RadialProgress), {
            diameter: 100,
            "completed-steps": 100,
            "total-steps": 100,
            "stroke-width": 8,
            "inner-stroke-width": 8,
            "start-color": "#3B82F6",
            "stop-color": "#2563EB",
            "inner-stroke-color": "#E5E7EB"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<span class="text-2xl font-bold text-blue-600 dark:text-blue-400"${_scopeId}>${ssrInterpolate(stats.value.grade)}</span>`);
              } else {
                return [
                  createVNode("span", { class: "text-2xl font-bold text-blue-600 dark:text-blue-400" }, toDisplayString(stats.value.grade), 1)
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div><div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center"><div class="text-sm text-gray-500 mb-2">\u0E04\u0E30\u0E41\u0E19\u0E19\u0E23\u0E27\u0E21</div>`);
          _push(ssrRenderComponent(unref(RadialProgress), {
            diameter: 100,
            "completed-steps": Math.round(stats.value.scorePercent),
            "total-steps": 100,
            "stroke-width": 8,
            "inner-stroke-width": 8,
            "start-color": "#10B981",
            "stop-color": "#059669",
            "inner-stroke-color": "#E5E7EB"
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="text-center"${_scopeId}><div class="text-xl font-bold text-gray-900 dark:text-white"${_scopeId}>${ssrInterpolate(stats.value.totalScore)}</div><div class="text-xs text-gray-400"${_scopeId}>/ ${ssrInterpolate(stats.value.maxScore)}</div></div>`);
              } else {
                return [
                  createVNode("div", { class: "text-center" }, [
                    createVNode("div", { class: "text-xl font-bold text-gray-900 dark:text-white" }, toDisplayString(stats.value.totalScore), 1),
                    createVNode("div", { class: "text-xs text-gray-400" }, "/ " + toDisplayString(stats.value.maxScore), 1)
                  ])
                ];
              }
            }),
            _: 1
          }, _parent));
          _push(`</div><div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"><div class="text-sm text-gray-500 mb-1">\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27</div><div class="text-3xl font-bold text-green-600 dark:text-green-400">${ssrInterpolate(stats.value.completedAssignments)} <span class="text-sm text-gray-400 font-normal">/ ${ssrInterpolate(stats.value.totalAssignments)}</span></div></div><div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700"><div class="text-sm text-gray-500 mb-1">\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E41\u0E25\u0E49\u0E27</div><div class="text-3xl font-bold text-purple-600 dark:text-purple-400">${ssrInterpolate(stats.value.completedQuizzes)} <span class="text-sm text-gray-400 font-normal">/ ${ssrInterpolate(stats.value.totalQuizzes)}</span></div></div></div>`);
        }
        _push(`<div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto"><!--[-->`);
        ssrRenderList(tabs, (tab) => {
          _push(`<button class="${ssrRenderClass([activeTab.value === tab.id ? "border-blue-600 text-blue-600 dark:text-blue-400" : "border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200", "px-4 py-3 text-sm font-medium flex items-center gap-2 whitespace-nowrap transition-colors border-b-2"])}">`);
          _push(ssrRenderComponent(unref(Icon), {
            icon: tab.icon,
            class: "w-5 h-5"
          }, null, _parent));
          _push(` ${ssrInterpolate(tab.label)}</button>`);
        });
        _push(`<!--]--></div><div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">`);
        if (activeTab.value === "lessons") {
          _push(`<div><div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center"><h3 class="font-semibold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</h3><span class="text-sm text-gray-500">${ssrInterpolate(stats.value.completedLessons)}/${ssrInterpolate(stats.value.totalLessons)}</span></div><div class="divide-y divide-gray-100 dark:divide-gray-700">`);
          if (data.value.lessons && data.value.lessons.length === 0) {
            _push(`<div class="p-8 text-center text-gray-500"> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(data.value.lessons, (lesson) => {
            _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><div class="flex flex-col gap-3"><div class="flex justify-between items-center"><div class="flex items-center gap-3 flex-1"><div class="${ssrRenderClass([lesson.completed ? "bg-green-100 text-green-600" : "bg-gray-100 text-gray-400", "flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"])}">`);
            _push(ssrRenderComponent(unref(Icon), {
              icon: lesson.completed ? "fluent:checkmark-24-filled" : "fluent:circle-24-regular",
              class: "w-5 h-5"
            }, null, _parent));
            _push(`</div><div class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(lesson.title)}</div></div><span class="${ssrRenderClass([lesson.completed ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-600", "text-xs px-2 py-1 rounded-full flex-shrink-0"])}">${ssrInterpolate(lesson.completed ? "\u0E40\u0E23\u0E35\u0E22\u0E19\u0E41\u0E25\u0E49\u0E27" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E40\u0E23\u0E35\u0E22\u0E19")}</span></div>`);
            if (data.value.lessons.length > 0) {
              _push(`<div class="mt-2"><div class="flex items-center justify-between mb-1"><span class="text-xs text-gray-500">\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32</span><span class="${ssrRenderClass([lesson.completed ? "text-green-600" : "text-gray-400", "text-xs font-medium"])}">${ssrInterpolate(lesson.completed ? "100%" : "0%")}</span></div><div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden"><div class="${ssrRenderClass([lesson.completed ? "bg-gradient-to-r from-green-400 to-green-500" : "bg-gray-400 dark:bg-gray-600", "h-full rounded-full transition-all duration-500 ease-out"])}" style="${ssrRenderStyle({ width: lesson.completed ? "100%" : "0%" })}"></div></div></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "assignments") {
          _push(`<div><div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center"><h3 class="font-semibold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22</h3><span class="text-sm text-gray-500">${ssrInterpolate(stats.value.completedAssignments)}/${ssrInterpolate(stats.value.totalAssignments)}</span></div><div class="divide-y divide-gray-100 dark:divide-gray-700">`);
          if (data.value.assignments && data.value.assignments.length === 0) {
            _push(`<div class="p-8 text-center text-gray-500"> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E07\u0E32\u0E19\u0E17\u0E35\u0E48\u0E21\u0E2D\u0E1A\u0E2B\u0E21\u0E32\u0E22\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(data.value.assignments, (assign) => {
            var _a2;
            _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><div class="flex flex-col gap-3"><div class="flex justify-between items-start"><div class="flex-1"><div class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(assign.title)}</div><div class="${ssrRenderClass([{
              "text-green-600": assign.submitted,
              "text-yellow-600": !assign.submitted
            }, "text-xs mt-1"])}">${ssrInterpolate(assign.submitted ? assign.graded ? "\u0E15\u0E23\u0E27\u0E08\u0E41\u0E25\u0E49\u0E27" : "\u0E2A\u0E48\u0E07\u0E41\u0E25\u0E49\u0E27" : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E2A\u0E48\u0E07")}</div>`);
            if (assign.submitted_at) {
              _push(`<div class="text-xs text-gray-400 mt-1"> \u0E2A\u0E48\u0E07\u0E40\u0E21\u0E37\u0E48\u0E2D: ${ssrInterpolate(new Date(assign.submitted_at).toLocaleDateString("th-TH"))}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
            if (canShowScore.value) {
              _push(`<div class="text-right flex-shrink-0"><div class="${ssrRenderClass([getScoreColor(assign.score, assign.max_score), "font-bold text-lg"])}">${ssrInterpolate(assign.score !== null ? assign.score : "-")}</div><div class="text-xs text-gray-400">\u0E40\u0E15\u0E47\u0E21 ${ssrInterpolate(assign.max_score)}</div><div class="mt-2 text-right">`);
              if (!unref(isCourseAdmin) && !assign.submitted) {
                _push(`<button class="text-xs px-3 py-1.5 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700">${ssrInterpolate(expandedAssignmentId.value === assign.id ? "\u0E1B\u0E34\u0E14" : "\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19")}</button>`);
              } else {
                _push(`<!---->`);
              }
              if (unref(isCourseAdmin) && (assign.submitted || assign.graded)) {
                _push(`<button class="${ssrRenderClass([expandedAssignmentId.value === assign.id ? "bg-orange-50 text-orange-600 border-orange-200" : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50", "text-xs px-3 py-1.5 rounded-lg transition-colors border"])}">${ssrInterpolate(expandedAssignmentId.value === assign.id ? "\u0E1B\u0E34\u0E14\u0E01\u0E32\u0E23\u0E15\u0E23\u0E27\u0E08" : assign.graded ? "\u0E41\u0E01\u0E49\u0E44\u0E02\u0E04\u0E30\u0E41\u0E19\u0E19" : "\u0E15\u0E23\u0E27\u0E08\u0E43\u0E2B\u0E49\u0E04\u0E30\u0E41\u0E19\u0E19")}</button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div>`);
            } else {
              _push(`<div class="text-right flex-shrink-0"><div class="text-xs text-gray-400 italic">\u0E0B\u0E48\u0E2D\u0E19\u0E04\u0E30\u0E41\u0E19\u0E19</div><div class="mt-2 text-right">`);
              if (!unref(isCourseAdmin) && !assign.submitted) {
                _push(`<button class="text-xs px-3 py-1.5 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700">${ssrInterpolate(expandedAssignmentId.value === assign.id ? "\u0E1B\u0E34\u0E14" : "\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19")}</button>`);
              } else {
                _push(`<!---->`);
              }
              _push(`</div></div>`);
            }
            _push(`</div>`);
            if (assign.max_score > 0 && canShowScore.value) {
              _push(`<div class="mt-2"><div class="flex items-center justify-between mb-1"><span class="text-xs text-gray-500">\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32</span><span class="${ssrRenderClass([getScoreColor(assign.score, assign.max_score), "text-xs font-medium"])}">${ssrInterpolate(assign.score !== null ? Math.round(assign.score / assign.max_score * 100) + "%" : "0%")}</span></div><div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden"><div class="${ssrRenderClass([assign.score !== null && assign.max_score > 0 ? getProgressBarColor(assign.score, assign.max_score) : "bg-gray-400 dark:bg-gray-600", "h-full rounded-full transition-all duration-500 ease-out"])}" style="${ssrRenderStyle({ width: assign.score !== null && assign.max_score > 0 ? `${Math.min(assign.score / assign.max_score * 100, 100)}%` : "0%" })}"></div></div></div>`);
            } else {
              _push(`<!---->`);
            }
            if (expandedAssignmentId.value === assign.id) {
              _push(`<div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">`);
              if (!unref(isCourseAdmin)) {
                _push(`<div>`);
                _push(ssrRenderComponent(_sfc_main$1, {
                  assignment: assign,
                  courseId: __props.courseId,
                  onSubmitted,
                  onCancel: ($event) => expandedAssignmentId.value = null
                }, null, _parent));
                _push(`</div>`);
              } else {
                _push(`<div>`);
                if (answerLoading.value) {
                  _push(`<div class="flex justify-center py-4">`);
                  _push(ssrRenderComponent(unref(Icon), {
                    icon: "eos-icons:loading",
                    class: "w-6 h-6 text-orange-500"
                  }, null, _parent));
                  _push(`</div>`);
                } else if (gradingAnswer.value) {
                  _push(`<div><div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl mb-4 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">${ssrInterpolate(gradingAnswer.value.content)} `);
                  if ((_a2 = gradingAnswer.value.images) == null ? void 0 : _a2.length) {
                    _push(`<div class="mt-3 flex flex-wrap gap-2"><!--[-->`);
                    ssrRenderList(gradingAnswer.value.images, (img) => {
                      _push(`<img${ssrRenderAttr("src", img.full_url || img.image_url)} class="w-20 h-20 object-cover rounded-lg border cursor-pointer">`);
                    });
                    _push(`<!--]--></div>`);
                  } else {
                    _push(`<!---->`);
                  }
                  _push(`</div><div class="flex items-center gap-3"><div class="font-bold text-sm">\u0E04\u0E30\u0E41\u0E19\u0E19:</div><input type="number"${ssrRenderAttr("value", gradingAnswer.value.newPoints)}${ssrRenderAttr("max", assign.max_score)} min="0" class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-orange-500 outline-none text-center font-bold"><span class="text-sm text-gray-500">/ ${ssrInterpolate(assign.max_score)}</span><button class="ml-auto px-4 py-1.5 bg-orange-500 text-white rounded-lg text-sm font-bold hover:bg-orange-600 shadow-sm"> \u0E1A\u0E31\u0E19\u0E17\u0E36\u0E01 </button></div></div>`);
                } else {
                  _push(`<div class="text-center py-4 text-gray-500 text-sm"> \u0E44\u0E21\u0E48\u0E1E\u0E1A\u0E04\u0E33\u0E15\u0E2D\u0E1A \u0E2B\u0E23\u0E37\u0E2D \u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E44\u0E14\u0E49\u0E2A\u0E48\u0E07\u0E07\u0E32\u0E19 </div>`);
                }
                _push(`</div>`);
              }
              _push(`</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        if (activeTab.value === "quizzes") {
          _push(`<div><div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center"><h3 class="font-semibold text-gray-800 dark:text-white">\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A</h3><span class="text-sm text-gray-500">${ssrInterpolate(stats.value.completedQuizzes)}/${ssrInterpolate(stats.value.totalQuizzes)}</span></div><div class="divide-y divide-gray-100 dark:divide-gray-700">`);
          if (data.value.quizzes && data.value.quizzes.length === 0) {
            _push(`<div class="p-8 text-center text-gray-500"> \u0E44\u0E21\u0E48\u0E21\u0E35\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A\u0E43\u0E19\u0E23\u0E32\u0E22\u0E27\u0E34\u0E0A\u0E32\u0E19\u0E35\u0E49 </div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<!--[-->`);
          ssrRenderList(data.value.quizzes, (quiz) => {
            _push(`<div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"><div class="flex flex-col gap-3"><div class="flex justify-between items-start"><div class="flex-1"><div class="font-medium text-gray-900 dark:text-white">${ssrInterpolate(quiz.title)}</div><div class="${ssrRenderClass([{
              "text-green-600": quiz.completed,
              "text-gray-500": !quiz.completed
            }, "text-xs mt-1 flex items-center gap-2"])}"><span>${ssrInterpolate(quiz.completed ? `\u0E17\u0E33\u0E41\u0E25\u0E49\u0E27 (${quiz.attempt_count} \u0E04\u0E23\u0E31\u0E49\u0E07)` : "\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E17\u0E33")}</span>`);
            if (quiz.completed && quiz.passed) {
              _push(`<span class="text-green-600 bg-green-100 px-1.5 py-0.5 rounded text-[10px]">\u0E1C\u0E48\u0E32\u0E19</span>`);
            } else {
              _push(`<!---->`);
            }
            if (quiz.completed && !quiz.passed) {
              _push(`<span class="text-red-600 bg-red-100 px-1.5 py-0.5 rounded text-[10px]">\u0E44\u0E21\u0E48\u0E1C\u0E48\u0E32\u0E19</span>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div>`);
            if (quiz.completed_at) {
              _push(`<div class="text-xs text-gray-400 mt-1"> \u0E25\u0E48\u0E32\u0E2A\u0E38\u0E14: ${ssrInterpolate(new Date(quiz.completed_at).toLocaleDateString("th-TH"))}</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div><div class="text-right flex-shrink-0">`);
            if (canShowScore.value) {
              _push(`<!--[--><div class="${ssrRenderClass([getScoreColor(quiz.score, quiz.max_score), "font-bold text-lg"])}">${ssrInterpolate(quiz.score !== null ? quiz.score : "-")}</div><div class="text-xs text-gray-400">\u0E40\u0E15\u0E47\u0E21 ${ssrInterpolate(quiz.max_score)}</div><!--]-->`);
            } else {
              _push(`<div class="text-xs text-gray-400 italic mb-2">\u0E0B\u0E48\u0E2D\u0E19\u0E04\u0E30\u0E41\u0E19\u0E19</div>`);
            }
            if (!quiz.passed) {
              _push(`<div>`);
              _push(ssrRenderComponent(_component_NuxtLink, {
                to: `/courses/${__props.courseId}/quizzes/${quiz.id}`,
                class: "text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 transition-colors"
              }, {
                default: withCtx((_, _push2, _parent2, _scopeId) => {
                  if (_push2) {
                    _push2(` \u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A `);
                  } else {
                    return [
                      createTextVNode(" \u0E17\u0E33\u0E41\u0E1A\u0E1A\u0E17\u0E14\u0E2A\u0E2D\u0E1A ")
                    ];
                  }
                }),
                _: 2
              }, _parent));
              _push(`</div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
            if (quiz.completed && quiz.max_score > 0 && canShowScore.value) {
              _push(`<div class="mt-2"><div class="flex items-center justify-between mb-1"><span class="text-xs text-gray-500">\u0E04\u0E27\u0E32\u0E21\u0E04\u0E37\u0E1A\u0E2B\u0E19\u0E49\u0E32</span><span class="${ssrRenderClass([getScoreColor(quiz.score, quiz.max_score), "text-xs font-medium"])}">${ssrInterpolate(Math.round(quiz.score / quiz.max_score * 100))}% </span></div><div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden"><div class="${ssrRenderClass([getProgressBarColor(quiz.score, quiz.max_score), "h-full rounded-full transition-all duration-500 ease-out"])}" style="${ssrRenderStyle({ width: `${Math.min(quiz.score / quiz.max_score * 100, 100)}%` })}"></div></div></div>`);
            } else {
              _push(`<!---->`);
            }
            _push(`</div></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<div class="flex flex-col items-center justify-center py-12 text-gray-500">`);
        _push(ssrRenderComponent(unref(Icon), {
          icon: "fluent:error-circle-24-filled",
          class: "w-12 h-12 text-red-400 mb-2"
        }, null, _parent));
        _push(`<p>\u0E44\u0E21\u0E48\u0E2A\u0E32\u0E21\u0E32\u0E23\u0E16\u0E42\u0E2B\u0E25\u0E14\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E44\u0E14\u0E49</p><button class="mt-2 text-blue-600 hover:underline">\u0E25\u0E2D\u0E07\u0E43\u0E2B\u0E21\u0E48</button></div>`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/learn/course/MyProgressDetails.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as _ };
//# sourceMappingURL=MyProgressDetails-CmsVaAN0.mjs.map
