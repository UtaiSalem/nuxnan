<template>
    <div>
        <div class="bg-white shadow rounded-lg dark:bg-dark-card">
            <div class="flex flex-row items-center justify-between p-6 pb-0">
                <h4>Stories</h4>
            </div>
            <div class="p-6 overflow-auto">
              <div id="stories" class="storiesWrapper flex justify-around">
                <div class="" v-for="i in 8" :key="i">
                    <img class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" :src="`storage/images/users/avatar/0${i}.png`" alt="Bordered avatar">
                    <span class="flex flex-wrap w-10 justify-center mx-auto">Your Story</span>
                </div>
              </div>
            </div>
        </div>
    </div>
</template>

<script setup>

</script>

<style lang="scss" scoped>

</style>
