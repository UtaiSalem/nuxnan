import { ref } from 'vue'

export function useComboSystem() {
  const combo    = ref(0)
  const maxCombo = ref(0)

  const MILESTONES = [5, 10, 20, 30, 50]

  function addCombo(): number | null {
    combo.value++
    if (combo.value > maxCombo.value) maxCombo.value = combo.value
    return MILESTONES.includes(combo.value) ? combo.value : null
  }

  function breakCombo(): number {
    const was = combo.value
    combo.value = 0
    return was
  }

  function reset() {
    combo.value    = 0
    maxCombo.value = 0
  }

  return { combo, maxCombo, addCombo, breakCombo, reset }
}
