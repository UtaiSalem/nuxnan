<template>
    <div>
        <!-- :class="[ 
            reactionState ? 'text-white bg-'+ reactionTheme + ' border-' + reactionTheme  : ' text-gray-700 hover:border-' + reactionTheme + ' hover:text-'+ reactionTheme +' ',
            !reactionState && isLoading ? ' text-'+ reactionTheme : ''
        ]" -->
        <button type="submit" @click.prevent="handleSubmit" :disabled="isLoading" 
            :class="[reactionState ? 'text-white '+ bgTheme :  textTheme]"
            class=" flex justify-center items-center space-x-1 text-sm rounded-md px-2 py-1 hover:scale-110 cursor-pointer ">
            <div v-if="isLoading">
                <Icon icon="eos-icons:bubble-loading" class="w-6 h-6 "/>
            </div>
            <div v-else class="flex justify-around items-center space-x-2">
                <span v-if="reactionState" :class="'text-white '+ ' bg-'+ reactionTheme">
                    <slot name="solid-icon"></slot>
                </span>
                <span v-else>
                    <slot name="outline-icon"></slot>
                </span>
                <!-- <Icon v-else icon="heroicons-outline:thumb-up" :class="'w-6 h-6 ' + 'text' + reactionTheme"  /> -->
            </div>
            <span v-if="reactionState">
                <slot name="delete-action"></slot>
            </span>
            <span v-else>
                <slot name="create-action"></slot>
            </span>
        </button>
    </div>
</template>

<script setup>
    import { computed, ref } from 'vue';
    import { useForm } from "@inertiajs/vue3";
    import { Icon } from '@iconify/vue';

    const props = defineProps({
        desModel: { type: String, default: ''},
        desModelId: { type: Number, default: 0},
        reactionType: { type: String, default: ''},
        reactionState: { type: Boolean, default: false },
        reactionTheme: { type: String, default: '' }
    });

    const reactionState = ref(props.reactionState);
    const textTheme = computed(()=> 'text-' + props.reactionTheme);
    const bgTheme = computed(()=> 'bg-' + props.reactionTheme);
    
    
    const isLoading = ref(false);
    const reactionForm = useForm({});
    const handleSubmit = () => {
        isLoading.value = true;
        reactionForm.post(`/${props.desModel}/${props.desModelId}/${props.reactionType}`,
            { 
                preserveScroll: true, 
                onSuccess: () => {
                    reactionState.value = !reactionState.value;
                    isLoading.value = false; 
                } 
            });
    };
</script>
