<template>
  <div id="loading" class="w-full h-screen flex items-center justify-center bg-gray-100">
    <div :class="`loader simple-loader ${!show ? 'hidden' : ''}`">
      <div class="loader-body">
        <!-- <img src="../../../../assets/images/loader.gif" alt="loader" class=" img-fluid w-25" width="200" height="200" /> -->
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    show: {
      type: Boolean,
      default: false
    }
  }
}
</script>

<style lang="scss" scoped></style>
