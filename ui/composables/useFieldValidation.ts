import { computed, type Ref } from 'vue'

export const useFieldValidation = (
  value: Ref<string>,
  rules: Array<(v: string) => string | true>
) => {
  const error = computed(() => {
    for (const rule of rules) {
      const result = rule(value.value)
      if (result !== true) return result
    }
    return ''
  })
  const isValid = computed(() => !error.value)
  return { error, isValid }
}

// Common rules
export const validationRules = {
  required: (msg = 'จำเป็น') => (v: string) => v.trim() ? true : msg,
  minLen: (n: number) => (v: string) => v.length >= n ? true : `ต้องอย่างน้อย ${n} ตัวอักษร`,
  maxLen: (n: number) => (v: string) => v.length <= n ? true : `ไม่เกิน ${n} ตัวอักษร`,
  email: (msg = 'รูปแบบอีเมลไม่ถูกต้อง') => (v: string) => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return !v || pattern.test(v) ? true : msg
  }
}
