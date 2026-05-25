import { ref, onUnmounted } from 'vue'
import type { Types } from 'phaser'

export function usePhaserGame() {
  const gameInstance = ref<any>(null)
  const isReady      = ref(false)

  async function createGame(
    containerId: string,
    config: Omit<Types.Core.GameConfig, 'parent'>
  ) {
    // Dynamic import — ไม่ import Phaser ตอน SSR
    const Phaser = (await import('phaser')).default

    // destroy game เก่าก่อน (ป้องกัน hot-reload ซ้ำ)
    if (gameInstance.value) {
      gameInstance.value.destroy(true)
      gameInstance.value = null
    }

    gameInstance.value = new Phaser.Game({
      ...config,
      parent: containerId,
    })
    isReady.value = true

    return gameInstance.value
  }

  function destroyGame() {
    if (gameInstance.value) {
      gameInstance.value.destroy(true)
      gameInstance.value = null
      isReady.value = false
    }
  }

  onUnmounted(() => destroyGame())

  return { gameInstance, isReady, createGame, destroyGame }
}
