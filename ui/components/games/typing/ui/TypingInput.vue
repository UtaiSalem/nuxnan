<script setup lang="ts">
interface Props {
  modelValue: string
  disabled?: boolean
  autofocus?: boolean
  submitOn?: 'space' | 'enter' | 'both'
}

const props = defineProps<Props>()
const { t } = useI18n()
const submitKey = computed(() => props.submitOn === 'enter' ? t('typingArena.enter') : props.submitOn === 'space' ? t('typingArena.space') : `${t('typingArena.space')} ${t('typingArena.or')} ${t('typingArena.enter')}`)

const emit = defineEmits(['update:modelValue', 'submit', 'keydown'])

const inputRef = ref<HTMLInputElement | null>(null)
useKeyboardAwareInput(inputRef)

function onInput(e: Event) {
  const target = e.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

function onKeydown(e: KeyboardEvent) {
  if (e.isComposing || e.keyCode === 229 || props.disabled) return
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
      class="w-full px-4 sm:px-6 py-4 text-2xl font-mono text-center text-vikinger-dark dark:text-white bg-vikinger-light-200 dark:bg-vikinger-dark-300 border-2 border-vikinger-purple/25 rounded-vikinger-lg focus:border-vikinger-purple focus:ring-4 focus:ring-vikinger-purple/15 outline-none transition-all duration-200"
      :placeholder="t('typingArena.input')"
      :aria-label="t('typingArena.inputLabel')"
      autocomplete="off"
      autocorrect="off"
      autocapitalize="off"
      spellcheck="false"
      @input="onInput"
      @keydown="onKeydown"
    />
    
    <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
      <kbd class="rounded-md border border-vikinger-purple/20 bg-vikinger-purple/10 px-2 py-1">{{ submitKey }}</kbd>
      <span>{{ t(submitOn === 'enter' ? 'typingArena.submitSentence' : 'typingArena.submitWord') }}</span>
    </div>
  </div>
</template>
