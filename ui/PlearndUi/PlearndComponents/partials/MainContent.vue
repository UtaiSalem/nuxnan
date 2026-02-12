<template>
  <div class="flex justify-center lg:justify-between space-x-4 mx-32">
    <!-- Sidebar start-->
    <div class="hidden lg:block w-[23%]">
      <!-- Sidebar -->
      <!-- <LeftSideWidget /> -->
      <!-- ./Sidebar -->
    </div>
    <!-- Sidebar end -->

    <!-- content -->
    <div class="container mx-auto">
      <main class="">
        <div class="h-full w-full">

            <slot></slot>

        </div>
      </main>
    </div>
    <!-- End content -->

    <!-- right sidebar -->
    <div class="hidden lg:block w-[23%]">
      <LeftSideWidget />
    </div>
    <!--End right sidebar -->
  </div>
</template>
<script setup>
// import LeftSideWidget from "@/Components/widgets/LeftSideWidget.vue";

</script>
