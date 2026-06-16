import { onUnmounted, shallowRef } from 'vue'
import type { SeatData, SimulatorData } from '~/composables/useAttendanceStatus'

export interface AttendanceDoorState {
  canCheckIn: boolean
  willBeLate?: boolean
  reason?: string
  countdown?: number
}

export interface AttendanceTeacher {
  name: string
  avatar: string | null
}

export interface AttendanceSceneState {
  seats: SeatData[]
  totalSeats: number
  attendance: SimulatorData['attendance'] | null
  authMemberId: number | null
  selectedMemberId: number | null
  isCourseAdmin: boolean
  clock: { range: string; label: string; value: string; tone: string } | null
  serverTimeMs: number
  doorState: AttendanceDoorState
  teacher?: AttendanceTeacher | null
}

export interface AttendanceSceneHandlers {
  onSeatSelect?: (memberId: number) => void
  onDoorClick?: () => void
  onSceneReady?: () => void
  onHeightChange?: (height: number) => void
  getAvatarUrl?: (user: { avatar: string | null; name: string }) => string
}

type AttendanceRoomSceneInstance = {
  setSceneData: (next: Partial<AttendanceSceneState>) => void
  resizeScene: (width: number, height: number) => void
}

export function useAttendancePhaser() {
  const gameInstance = shallowRef<any>(null)
  const sceneInstance = shallowRef<AttendanceRoomSceneInstance | null>(null)

  async function createGame(
    containerId: string,
    width: number,
    height: number,
    initialState: AttendanceSceneState,
    handlers: AttendanceSceneHandlers = {},
  ) {
    const [{ default: Phaser }, { AttendanceRoomScene }] = await Promise.all([
      import('phaser'),
      import('~/components/learn/course/attendances/phaser/attendancePhaserScene'),
    ])

    destroyGame()

    const scene = new AttendanceRoomScene(handlers)
    scene.setSceneData(initialState)
    sceneInstance.value = scene

    gameInstance.value = new Phaser.Game({
      type: Phaser.AUTO,
      width,
      height,
      backgroundColor: '#7CC74E',
      parent: containerId,
      scene: [scene],
      scale: {
        mode: Phaser.Scale.NONE,
        autoCenter: Phaser.Scale.CENTER_BOTH,
      },
      render: {
        antialias: true,
        pixelArt: false,
        roundPixels: false,
      },
    })

    return gameInstance.value
  }

  function updateSceneData(next: Partial<AttendanceSceneState>) {
    sceneInstance.value?.setSceneData(next)
  }

  function resizeGame(width: number, height: number) {
    if (!gameInstance.value) return
    gameInstance.value.scale.resize(width, height)
    sceneInstance.value?.resizeScene(width, height)
  }

  function destroyGame() {
    if (gameInstance.value) {
      gameInstance.value.destroy(true)
      gameInstance.value = null
    }
    sceneInstance.value = null
  }

  onUnmounted(() => destroyGame())

  return {
    gameInstance,
    sceneInstance,
    createGame,
    updateSceneData,
    resizeGame,
    destroyGame,
  }
}
