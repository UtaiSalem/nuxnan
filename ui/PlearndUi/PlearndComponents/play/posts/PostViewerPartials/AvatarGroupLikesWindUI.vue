<script setup>
    import { Icon } from '@iconify/vue';
    defineProps({
      likes:Number,
      showLikeIcon: Boolean,
    });
</script>

<template>
<!-- Component: Rounded full base sized grouped avatars with tooltip -->
<div class="flex items-center justify-center w-full">
    <!-- <div class="flex -space-x-4" v-if="likes>0">
      
      <div class="flex" v-if="likes>0">
        <a v-for="index in 3" :key="index" href="#" class="relative inline-flex items-center justify-center w-10 h-10 text-white rounded-full">
          <img src="https://i.pravatar.cc/40?img=31" alt="user name" title="user name" width="40" height="40" class="max-w-full border-2 border-white rounded-full" />
        </a>
      </div>

  
        <a href="#" class="group relative inline-flex w-10 h-10  cursor-pointer items-center justify-center overflow-hidden rounded-full border-2 border-white bg-slate-200 text-sm text-slate-500 before:invisible before:absolute before:bottom-[90%] before:left-1/2 before:z-10 before:mb-2 before:min-w-max before:-translate-x-1/2 before:rounded before:bg-slate-800/90 before:p-2 before:text-center before:text-xs before:text-white before:opacity-0 before:transition-all before:content-[attr(data-text)] hover:overflow-visible hover:before:visible hover:before:bottom-full hover:before:block hover:before:opacity-100" aria-describedby="tooltip-05">
          +{{ likes > 3 ? likes - 3 : likes}}
          <span role="tooltip" id="tooltip-05" class="invisible absolute bottom-full left-1/2 z-10 mb-2 w-36 -translate-x-1/2 rounded bg-slate-700 p-2 text-xs text-white opacity-0 transition-all before:invisible before:absolute before:left-1/2 before:top-full before:z-10 before:mb-2 before:-ml-1 before:border-x-4 before:border-t-4 before:border-x-transparent before:border-t-slate-700 before:opacity-0 before:transition-all before:content-[''] group-hover:visible group-hover:block group-hover:opacity-100 group-hover:before:visible group-hover:before:opacity-100"> John Langan, Christos </span>
        </a>
    </div> -->
    <Icon icon="icon-park-solid:like" class="w-5 h-5 text-green-500 mx-2" v-if="showLikeIcon"/>
    <span class="text-gray-400 text-sm">{{ likes }} {{ likes < 2 ? 'like' : 'likes' }}</span>
  </div>
  <!-- End Rounded full base sized grouped avatars with tooltip -->
</template>
