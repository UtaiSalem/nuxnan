<script setup>
/**
 * Course Layout
 * Wraps main layout and provides course-specific structure
 * Note: Course data providing should be done in pages, not layouts
 */
</script>

<template>
  <NuxtLayout name="main">
    <!-- Hero Slot: Pass through from page -->
    <template #hero>
      <slot name="hero" />
    </template>

    <!-- Left Widgets Slot -->
    <template #leftWidgets>
      <slot name="leftWidgets" />
    </template>

    <!-- Default Slot: Main Content -->
    <slot />

    <!-- Right Widgets Slot -->
    <template #rightWidgets>
      <slot name="rightWidgets" />
    </template>
  </NuxtLayout>
</template>
