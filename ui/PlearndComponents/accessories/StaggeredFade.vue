<script setup>
import Velocity from "velocity-animate";

const props = defineProps({
    duration: {
      type: Number,
      default: 200 // duration of each element transition
    }
});

function beforeEnter(el) {
    el.style.opacity = 0;
    el.style.height = 0;
}

function enter(el, done) {
    // Each element requires a data-index attribute in order for the transition to work properly
    const index = el.dataset.index || 1;
    var delay = index * props.duration;
    setTimeout(() => {
    Velocity(el, { opacity: 1, height: "100%" }, { complete: done });
    }, delay);
}

function leave(el, done) {
    // Each element requires a data-index attribute in order for the transition to work properly
    const index = el.dataset.index || 1;
    var delay = index * props.duration;
    setTimeout(() => {
    Velocity(el, { opacity: 0, height: 0 }, { complete: done });
    }, delay);
}

</script>
<template>
    <transition-group
      name="staggered-fade"
      :css="false"
      appear
      v-bind="$attrs"
      @before-enter="beforeEnter"
      @enter="enter"
      @leave="leave"
    >
      <!-- Each element requires a data-index attribute in order for the transition to work properly -->
      <slot></slot>
    </transition-group>
  </template>
