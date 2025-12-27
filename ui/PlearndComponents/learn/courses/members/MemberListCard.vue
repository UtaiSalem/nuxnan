<script setup>
import { computed } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import Badge from '@/PlearndComponents/accessories/Badge.vue';
import CyanSeaButton from '@/PlearndComponents/accessories/CyanSeaButton.vue';

const props = defineProps({
    member: {
      type: Object,
      default: () => ({})
    },
    groups: {
      type: Object,
      default: () => ({})
    },
});

const emit = defineEmits(['request-unmember-course']);

const canManage = computed(() => (props.member.user.id === usePage().props.auth.user.id));

function requestToBeUnMember(){
  emit('request-unmember-course');
}
</script>

<template>
    <li class="bg-white shadow-lg p-4 rounded-lg flex justify-between w-full mb-3">
      
      <div class="flex items-center">
        <img class="w-12 h-12 rounded-full" :src="member.user.avatar" :alt="member.user.name">
        <div class="ml-3">
          <p class="text-gray-900  tracking-wide text-sm">{{member.user.name}}</p>
          <div class="flex text-sm">
            <!-- <p class="font-semibold mr-1">{{member.amount}}</p> -->
            <!-- <span class="text-gray-600">{{member.currency}}</span> -->
          </div>
        </div>
      </div>

      <!-- Component: Rounded base basic select -->
      <!-- <div class="relative ">
        <select :id="`id-${props.member.id}`"  class="relative  h-10 px-2 text-sm transition-all bg-white border rounded outline-none appearance-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white focus:border-violet-500 focus:focus-visible:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
          <option v-for="(item, index) in props.groups" :key="index" 
            :value="item.id"
            :selected="item.id == props.member.group_id"
          >
          {{ item.name }}
          </option>
        </select>
        <label :for="`id-${props.member.id}`" class="pointer-events-none absolute top-2.5 left-2 z-[1] px-2 text-sm text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all  peer-valid:-top-2 peer-valid:text-xs peer-focus:-top-2 peer-focus:text-xs peer-focus:text-violet-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
          กลุ่ม
        </label>
        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute top-2.5 right-2 h-5 w-5 fill-slate-400 transition-all peer-focus:fill-violet-500 peer-disabled:cursor-not-allowed" viewBox="0 0 20 20" fill="currentColor" aria-labelledby="title-04 description-04" role="graphics-symbol">
          <title id="title-04">Arrow Icon</title>
          <desc id="description-04">Arrow icon of the select list.</desc>
          <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </div> -->
      <!-- End Rounded base basic select -->

      <div class="hidden sm:flex items-center" >
        <!-- <badge :color="'green'">
            {{ member.status }}
        </badge> -->
        <Badge :color="'green'" class="mx-2">
            {{ props.member.status==1? 'สมาชิก':'รอตอบรับ' }}
        </Badge>

        <!-- <CyanSeaButton v-if="$page.props.isCourseAdmin">
            <span class="flex items-center justify-center ">
                - <Icon icon="majesticons:door-exit-line" class="ml-2" /> 
                <span class="hidden sm:block text-sm font-normal mx-2">
                    ลบสมาชิก
                </span>
            </span>
        </CyanSeaButton> -->
        
        <!-- <button v-if="canManage" @click.prevent="requestToBeUnMember" class="bg-gray-400 py-2 px-4 rounded-lg text-white shadow-lg  hover:shadow-xl hover:bg-red-300 border border-gray-400">
            <div class="flex items-center justify-center ">
                <Icon icon="majesticons:door-exit-line" class="ml-2" /> 
                <span class="hidden sm:block text-sm font-normal mx-2">
                    {{ props.member.group.name }}
                </span>
            </div>
        </button> -->
      </div>

    </li>
</template>
