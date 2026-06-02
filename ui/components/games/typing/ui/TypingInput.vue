<script setup lang="ts">
interface Props {
  modelValue: string
  disabled?: boolean
  autofocus?: boolean
  submitOn?: 'space' | 'enter' | 'both'
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'submit', 'keydown'])

const inputRef = ref<HTMLInputElement | null>(null)

function onInput(e: Event) {
  const target = e.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

function onKeydown(e: KeyboardEvent) {
  emit('keydown', e)
  const submitOn = props.submitOn ?? 'both'
  const isSpace = e.key === ' '
  const isEnter = e.key === 'Enter'
  const shouldSubmit =
    (submitOn === 'space' && isSpace) ||
    (submitOn === 'enter' && isEnter) ||
    (submitOn === 'both' && (isSpace || isEnter))

  if (shouldSubmit) {
    e.preventDefault()
    ;(e.target as HTMLInputElement).value = ''
    emit('update:modelValue', '')
    emit('submit')
  }
}

onMounted(() => {
  if (props.autofocus) {
    inputRef.value?.focus()
  }
})

function focus() {
  inputRef.value?.focus()
}

defineExpose({ focus })
</script>

<template>
  <div class="w-full max-w-2xl mx-auto">
    <input
      ref="inputRef"
      type="text"
      :value="modelValue"
      :disabled="disabled"
      class="w-full px-6 py-4 text-2xl font-mono text-center bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 rounded-2xl focus:border-primary-500 focus:ring-4 focus:ring-primary-500/20 outline-none transition-all duration-200"
      placeholder="Type here..."
      autocomplete="off"
      autocorrect="off"
      autocapitalize="off"
      spellcheck="false"
      @input="onInput"
      @keydown="onKeydown"
    />
    
    <div class="mt-4 flex justify-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
      <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded">Space</span>
      <span>to submit word</span>
    </div>
  </div>
</template>
