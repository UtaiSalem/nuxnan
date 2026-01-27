import { ssrRenderAttrs, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { ref, useSSRContext } from 'vue';

const _sfc_main = {
  __name: "MentalMath",
  __ssrInlineRender: true,
  setup(__props) {
    const randomNumbers = ref([0, 0, 0, 0]);
    const tempResult = ref(0);
    const isStart = ref(false);
    ref(4);
    ref(5);
    const showTimer = ref(false);
    const secondsElapsed = ref(5);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(_attrs)}><div class="w-full h-screen max-h-fit flex flex-col items-center justify-between bg-green-200"><div class="bg-green-200 text-end md:flex">`);
      if (isStart.value) {
        _push(`<button class="plearnd-btn-primary w-32 m-6">Stop</button>`);
      } else {
        _push(`<button class="plearnd-btn-primary w-24 m-6">Start</button>`);
      }
      _push(`</div><div>`);
      if (showTimer.value) {
        _push(`<div class="plearnd-card w-56 h-24 flex flex-col items-center justify-center text-7xl my-2 font-bold">${ssrInterpolate(secondsElapsed.value)}</div>`);
      } else {
        _push(`<!--[-->`);
        ssrRenderList(randomNumbers.value, (item, index) => {
          _push(`<div class="plearnd-card w-56 h-24 flex flex-col items-center justify-center text-7xl my-2 font-bold">${ssrInterpolate(item)}</div>`);
        });
        _push(`<!--]-->`);
      }
      _push(`</div><div class="text-gray-400 w-full text-end px-4">${ssrInterpolate(tempResult.value)}</div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/MentalMath.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=MentalMath-CH0kP0lH.mjs.map
