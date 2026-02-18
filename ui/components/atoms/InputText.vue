<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1 tracking-wider">{{ label }}</label>
    <div class="relative">
      <input
        :id="id"
        :name="name"
        :autocomplete="autocomplete"
        :type="inputType"
        :placeholder="placeholder"
        :value="modelValue"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:border-vikinger-purple focus:ring-1 focus:ring-vikinger-purple transition-colors duration-300 font-bold"
        :class="{ 'pr-12': type === 'password' && showToggle }"
      />
      <button
        v-if="type === 'password' && showToggle"
        type="button"
        @click="togglePasswordVisibility"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-vikinger-purple transition-colors duration-200"
      >
        <Icon :icon="isPasswordVisible ? 'fluent:eye-off-24-regular' : 'fluent:eye-24-regular'" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

const uniqueId = useId()

const props = defineProps({
  label: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  modelValue: {
    type: String,
    default: ''
  },
  showToggle: {
    type: Boolean,
    default: true
  },
  id: {
    type: String,
    default: ''
  },
  name: {
    type: String,
    default: ''
  },
  autocomplete: {
    type: String,
    default: 'off'
  }
})

defineEmits(['update:modelValue'])

const inputType = ref(props.type)
const isPasswordVisible = ref(false)

watch(() => props.type, (newType) => {
  inputType.value = newType
})

const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value
  inputType.value = isPasswordVisible.value ? 'text' : 'password'
}
</script>
