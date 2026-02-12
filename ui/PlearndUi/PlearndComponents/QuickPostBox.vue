<template>
  <div class="bg-white rounded-lg overflow-hidden shadow-lg">
    <div class="tabs flex flex-col justify-center pt-4">
      <div class="tabs-header w-full flex justify-around">
        <a
          href=""
          class="w-full flex justify-center border-b-4 hover:border-blue-400 py-2 font-bold"

        >
          <!-- OPTION ITEM ICON -->
          <svg class="option-item-icon icon-status w-5 h-5 mt-[2px] opacity-50">
            <use xlink:href="#svg-status"></use>
          </svg>
          <!-- /OPTION ITEM ICON -->
          <span class="px-1">
            Status
          </span>
        </a>
        <a
          href=""

          class="w-full flex justify-center border-b-4 hover:border-blue-400 py-2 font-bold"

        >
          <!-- OPTION ITEM ICON -->
          <svg class="option-item-icon icon-status w-5 h-5 mt-[2px] opacity-40">
            <use xlink:href="#svg-blog-posts"></use>
          </svg>
          <!-- /OPTION ITEM ICON -->
          <span class="px-1">
            Blog
          </span>
        </a>
        <a
          href=""

          class="w-full flex justify-center border-b-4 hover:border-blue-500 py-2 font-bold"

        >
          <!-- OPTION ITEM ICON -->
          <svg class="option-item-icon icon-status w-5 h-5 mt-[2px] opacity-40">
            <use xlink:href="#svg-poll"></use>
          </svg>
          <!-- /OPTION ITEM ICON -->
          <span class="px-1">
            Poll
          </span>
        </a>
      </div>

      <div class="tabs-content w-full">
        <div class="">
          <form class="bg-white rounded-lg p-6" action="/create-post" method="post">
            <div class="flex mb-4 items-center">
              <div class="avatar online">
                <div
                  class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2"
                >
                  <img :src=" $page.props.profilePath + $page.props.auth.user.avatar" />
                </div>
              </div>
              <div class="mx-2">
                <div class="font-semibold">{{ $page.props.auth.user.name }}</div>
                <div class="text-gray-500">{{ new Date().toDateString() }}</div>
              </div>
            </div>

            <textarea
              v-model="form.body"
              class="bg-gray-50 w-full h-32 p-4 border rounded-lg focus:outline-none focus:shadow-outline-blue"
              placeholder="..."
            ></textarea>

            <div class="mt-4">
              <div class="quick-post-footer flex justify-between">
                <div class="quick-post-footer-actions flex">
                  <!-- QUICK POST FOOTER ACTION -->
                  <div
                    class="quick-post-footer-action text-tooltip-tft-medium mx-1"
                    data-title="Insert Photo"
                  >
                    <!-- QUICK POST FOOTER ACTION ICON -->
                    <svg class="quick-post-footer-action-icon icon-camera w-6 h-6">
                      <use xlink:href="#svg-camera"></use>
                    </svg>
                    <!-- <CameraIcon class="w-6 h-6"/> -->
                    <Icon icon="heroicons-solid:camera" class="w-6 h-6" />
                    <!-- /QUICK POST FOOTER ACTION ICON -->
                  </div>
                  <!-- /QUICK POST FOOTER ACTION -->

                  <!-- QUICK POST FOOTER ACTION -->
                  <div
                    class="quick-post-footer-action text-tooltip-tft-medium mx-1"
                    data-title="Insert GIF"
                  >
                    <!-- QUICK POST FOOTER ACTION ICON -->
                    <svg class="quick-post-footer-action-icon icon-gif w-6 h-6">
                      <use xlink:href="#svg-gif"></use>
                    </svg>
                    <!-- /QUICK POST FOOTER ACTION ICON -->
                  </div>
                  <!-- /QUICK POST FOOTER ACTION -->
                </div>
                <!-- /QUICK POST FOOTER ACTIONS -->

                <!-- QUICK POST FOOTER ACTIONS -->
                <div class="quick-post-footer-actions flex">
                  <!-- BUTTON -->
                  <button
                    class="button small void bg-gray-300 hover:bg-gray-600 hover:text-white py-2 px-5 rounded-lg mx-1 shadow-lg"
                  >
                    Discard
                  </button>
                  <!-- /BUTTON -->

                  <!-- BUTTON -->
                  <button
                    @click.prevent="handleSubmit" preserve-scroll
                    class="button small secondary bg-blue-600 hover:bg-blue-700 hover:text-white py-2 px-8 rounded-lg mx-1 text-lg font-semibold shadow-lg"
                  >
                    Post
                  </button>
                  <!-- /BUTTON -->
                </div>
                <!-- /QUICK POST FOOTER ACTIONS -->
              </div>
              <!-- /QUICK POST FOOTER -->
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue';
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const form = reactive({
  action: 'Created post',
  body: 'New post on '+ new Date().toString(),
})

function handleSubmit() {
  router.post('/newsfeed', form);
}
</script>
