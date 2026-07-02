import Phaser from 'phaser'
import {
  ATTENDANCE_STATUS,
  buildSummary,
  isArriving,
  type SeatData,
} from '~/composables/useAttendanceStatus'
import type {
  AttendanceSceneHandlers,
  AttendanceSceneState,
} from '~/composables/useAttendancePhaser'

const BASE_STATE: AttendanceSceneState = {
  seats: [],
  totalSeats: 0,
  attendance: null,
  authMemberId: null,
  selectedMemberId: null,
  isCourseAdmin: false,
  clock: null,
  serverTimeMs: Date.now(),
  doorState: {
    canCheckIn: false,
    willBeLate: false,
    reason: 'no-data',
  },
  teacher: null,
}

// Responsive zoning: pick a symmetric pattern based on canvas width so the
// room looks balanced on every viewport instead of forcing a fixed 6 cols.
type RoomLayout = { zones: number[]; totalCols: number; aisleCount: number }
function computeLayout(width: number): RoomLayout {
  if (width < 480) return { zones: [1, 1], totalCols: 2, aisleCount: 1 }
  if (width < 640) return { zones: [2, 2], totalCols: 4, aisleCount: 1 }
  if (width < 1024) return { zones: [2, 2, 2], totalCols: 6, aisleCount: 2 }
  return { zones: [3, 3, 3], totalCols: 9, aisleCount: 2 }
}

const FLOOR_TOP = 112 // Reduced from 160 as wall height decreased
const FLOOR_SIDE = 42
const FLOOR_BOTTOM = 40 // Reduced from 118 to tighten layout
const BOARD_HEIGHT = 112
const FRONT_WALKWAY = 72 // reserved strip between board and first desk row for the teacher
const DOOR_AREA = 110
const TEACHER_W = 56
const ROW_GAP_FIXED = 104
const SEAT_SHOULDERS_Y = -12
const SEAT_SHOULDERS_W = 26
const SEAT_SHOULDERS_H = 4
const SEAT_BODY_Y = -14
const SEAT_BODY_W = 22
const SEAT_BODY_H = 14
const LEAVE_HATCH_COLOR = 0x064e7b

type SeatSpriteRefs = {
  container: Phaser.GameObjects.Container
  pulse?: Phaser.Tweens.Tween | null
}

type FacePoints = Phaser.Math.Vector2[]
const facePoint = (x: number, y: number) => new Phaser.Math.Vector2(x, y)

export class AttendanceRoomScene extends Phaser.Scene {
  private handlers: AttendanceSceneHandlers
  private sceneData: AttendanceSceneState = BASE_STATE
  private root?: Phaser.GameObjects.Container
  private staticLayer?: Phaser.GameObjects.Container
  private dynamicLayer?: Phaser.GameObjects.Container
  private seatRefs = new Map<number, SeatSpriteRefs>()
  private lastArrivalIds = new Set<number>()
  private layout: RoomLayout = computeLayout(640)
  private gridCenterX = 0
  private teacherSprite?: Phaser.GameObjects.Container
  private teacherPersonRef?: Phaser.GameObjects.Container
  private teacherEyes: Phaser.GameObjects.Arc[] = []
  private teacherLoopActive = false
  private teacherPatrolToken = 0
  private teacherShutdownHooked = false
  private teacherSeatGeometry: { zoneStarts: number[]; deskCellW: number; zoneGap: number; floorY: number; rowGap: number; rows: number; aisleWidth: number } | null = null
  private teacherThinkDots?: Phaser.GameObjects.Container
  private hoverTooltip?: Phaser.GameObjects.Container

  constructor(handlers: AttendanceSceneHandlers = {}) {
    super({ key: 'AttendanceRoomScene' })
    this.handlers = handlers
  }

  create() {
    this.emitHeightChange()
    this.renderRoom()
    this.handlers.onSceneReady?.()

    // T4: Safety - stop patrol on shutdown
    this.events.once(Phaser.Scenes.Events.SHUTDOWN, () => this.stopTeacherPatrol())
    this.events.once(Phaser.Scenes.Events.DESTROY, () => this.stopTeacherPatrol())
  }

  setSceneData(next: Partial<AttendanceSceneState>) {
    const prevRows = Math.ceil(this.sceneData.seats.length / this.layout.totalCols)
    this.sceneData = {
      ...this.sceneData,
      ...next,
      doorState: {
        ...this.sceneData.doorState,
        ...(next.doorState || {}),
      },
    }

    const nextRows = Math.ceil(this.sceneData.seats.length / this.layout.totalCols)
    if (this.sys.isActive()) {
      if (prevRows !== nextRows) {
        this.emitHeightChange()
        this.renderRoom() // Full re-render if layout changed
      } else {
        this.updateDynamicParts()
      }
    }
  }

  resizeScene(width: number, height: number) {
    this.cameras.main.setSize(width, height)
    this.renderRoom()
  }

  private emitHeightChange() {
    const { totalCols } = computeLayout(this.scale.width)
    const rows = Math.max(1, Math.ceil(this.sceneData.totalSeats / totalCols))
    const expectedHeight = FLOOR_TOP + FRONT_WALKWAY + rows * ROW_GAP_FIXED + DOOR_AREA + FLOOR_BOTTOM
    this.handlers.onHeightChange?.(expectedHeight)
  }

  private renderRoom() {
    this.root?.destroy(true)
    this.root = this.add.container(0, 0)
    
    this.staticLayer = this.add.container(0, 0)
    this.dynamicLayer = this.add.container(0, 0)
    this.root.add([this.staticLayer, this.dynamicLayer])

    const width = this.scale.width || this.cameras.main.width
    const height = this.scale.height || this.cameras.main.height
    this.layout = computeLayout(width)

    this.drawBackground(width, height)
    this.drawFloor(width, height)
    this.drawBoard(width)
    this.drawLighting(width)
    
    this.updateDynamicParts(true)
  }

  private updateDynamicParts(isFullRender = false) {
    if (isFullRender) {
      this.hoverTooltip = undefined
      this.dynamicLayer?.removeAll(true)
      this.seatRefs.clear()
      const width = this.scale.width || this.cameras.main.width
      const height = this.scale.height || this.cameras.main.height
      this.drawSeats(width, height)
      this.drawDoor(width, height)
      this.drawTeacher(width)
      this.playArrivalTweens()
    } else {
      this.updateBoardTexts()
      this.updateSeatStatuses()
      this.updateDoorAction()
      this.playArrivalTweens()
    }
  }

  private drawBackground(width: number, height: number) {
    const g = this.add.graphics()
    // Grass field background
    g.fillGradientStyle(0x7cc74e, 0x7cc74e, 0x64b03d, 0x64b03d, 1)
    g.fillRect(0, 0, width, height)
    // Front wall (warm wood gradient) — A4: reduced from 144 to 96
    g.fillGradientStyle(0x6b4a2b, 0x8b5e3c, 0xa47148, 0xa47148, 1)
    g.fillRect(0, 0, width, 96)

    this.staticLayer?.add(g)
  }

  private boardTitleText?: Phaser.GameObjects.Text
  private boardDateText?: Phaser.GameObjects.Text
  private boardSubText?: Phaser.GameObjects.Text
  private boardSummaryText?: Phaser.GameObjects.Text

  private drawBoard(width: number) {
    const boardWidth = Math.min(width - 96, 700)
    const boardX = width / 2
    const boardY = 58

    const hangerY = boardY - BOARD_HEIGHT / 2 - 6
    const bracketOffset = Math.min(boardWidth * 0.28, 182)
    const hangers = this.add.graphics()
    hangers.fillStyle(0x4a5568, 1)
    hangers.fillRoundedRect(boardX - bracketOffset, hangerY, 6, 14, 2)
    hangers.fillRoundedRect(boardX + bracketOffset - 6, hangerY, 6, 14, 2)
    this.staticLayer?.add(hangers)

    const frame = this.add.graphics()
    frame.fillGradientStyle(0xa87547, 0xa87547, 0x7a5230, 0x7a5230, 1)
    frame.fillRoundedRect(boardX - boardWidth / 2, boardY - BOARD_HEIGHT / 2, boardWidth, BOARD_HEIGHT, 16)
    frame.lineStyle(2, 0x000000, 0.18)
    frame.strokeRoundedRect(boardX - boardWidth / 2, boardY - BOARD_HEIGHT / 2, boardWidth, BOARD_HEIGHT, 16)
    frame.fillGradientStyle(0x2f5d46, 0x2f5d46, 0x264d3b, 0x264d3b, 1)
    frame.fillRoundedRect(boardX - boardWidth / 2 + 8, boardY - BOARD_HEIGHT / 2 + 8, boardWidth - 16, BOARD_HEIGHT - 16, 12)
    this.staticLayer?.add(frame)

    this.boardTitleText = this.add.text(boardX, boardY - 26, '', {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: width < 640 ? '15px' : '18px',
      color: '#EAF5EC',
      fontStyle: 'bold',
      align: 'center',
      wordWrap: { width: boardWidth - 72, useAdvancedWrap: true },
      maxLines: 1,
    }).setOrigin(0.5)

    this.boardDateText = this.add.text(boardX, boardY - 5, '', {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: width < 640 ? '11px' : '12px',
      color: '#BFE0C9',
      align: 'center',
    }).setOrigin(0.5)

    this.boardSubText = this.add.text(boardX, boardY + 16, '', {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: width < 640 ? '10px' : '12px',
      color: '#BFE0C9',
      align: 'center',
      wordWrap: { width: boardWidth - 64, useAdvancedWrap: true },
      maxLines: 1,
    }).setOrigin(0.5)

    this.boardSummaryText = this.add.text(boardX, boardY + 38, '', {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: width < 640 ? '10px' : '12px',
      color: '#BFE0C9',
      fontStyle: 'bold',
      align: 'center',
      wordWrap: { width: boardWidth - 56, useAdvancedWrap: true },
    }).setOrigin(0.5)

    this.staticLayer?.add([this.boardTitleText, this.boardDateText, this.boardSubText, this.boardSummaryText])
    
    // D3: Board chalk dust
    for (let i = 0; i < 6; i++) {
      const dustX = boardX + (Math.random() - 0.5) * boardWidth * 0.8
      const dustY = boardY + (Math.random() - 0.5) * BOARD_HEIGHT * 0.6
      const dust = this.add.circle(dustX, dustY, 1 + Math.random() * 2, 0xffffff, 0.05)
      this.staticLayer?.add(dust)
    }

    this.updateBoardTexts()
  }

  private drawLighting(width: number) {
    const boardWidth = Math.min(width - 96, 700)
    const boardX = width / 2
    const boardY = 58
    
    const cone = this.add.graphics()
    // D2: Lighting cone from board (fades out)
    cone.fillGradientStyle(0xffffff, 0xffffff, 0xffffff, 0xffffff, 0.05, 0.05, 0, 0)
    cone.fillTriangle(
      boardX - boardWidth / 2 - 32, boardY + 10,
      boardX + boardWidth / 2 + 32, boardY + 10,
      boardX, boardY + 420
    )
    this.staticLayer?.add(cone)
  }

  private updateBoardTexts() {
    if (!this.boardTitleText || !this.boardDateText || !this.boardSubText || !this.boardSummaryText) return

    const summary = buildSummary(this.sceneData.seats)
    const title = this.sceneData.attendance
      ? [this.sceneData.attendance.group?.name, this.sceneData.attendance.title || this.sceneData.attendance.description]
          .filter(Boolean)
          .join(' · ')
      : 'ห้องเรียนจำลอง'

    this.boardTitleText.setText(this.truncateLabel(title, 56))
    this.boardDateText.setText(
      this.sceneData.attendance?.start_at
        ? new Date(this.sceneData.attendance.start_at).toLocaleDateString('th-TH', { dateStyle: 'medium' })
        : '',
    )
    
    const clockText = this.sceneData.clock
      ? `${this.sceneData.clock.range}   ${this.sceneData.clock.label}${this.sceneData.clock.value ? ` ${this.sceneData.clock.value}` : ''}`
      : ''
    this.boardSubText.setText(clockText)
    this.boardSubText.setColor(this.clockToneColor(this.sceneData.clock?.tone))

    this.boardSummaryText.setText(`มา ${summary.present}  สาย ${summary.late}  ลา ${summary.leave}  ขาด ${summary.absent}`)
  }

  private drawFloor(width: number, height: number) {
    const floorX = FLOOR_SIDE
    const floorY = FLOOR_TOP
    const floorW = width - FLOOR_SIDE * 2
    // A1: Use calculated height instead of fixed camera height
    const rows = Math.max(1, Math.ceil(this.sceneData.totalSeats / this.layout.totalCols))
    const floorH = FRONT_WALKWAY + rows * ROW_GAP_FIXED + 20

    const floor = this.add.graphics()
    floor.fillStyle(0xe9c58f, 1)
    floor.fillRoundedRect(floorX, floorY, floorW, floorH, 22)
    floor.lineStyle(4, 0x8b5e3c, 1)
    floor.strokeRoundedRect(floorX, floorY, floorW, floorH, 22)
    floor.lineStyle(1, 0xd9ae74, 0.6)
    for (let y = floorY + 18; y < floorY + floorH; y += 28) {
      floor.lineBetween(floorX + 6, y, floorX + floorW - 6, y)
    }
    this.staticLayer?.add(floor)

    const skirting = this.add.graphics()
    skirting.fillStyle(0x6b4a2b, 1)
    skirting.fillRect(floorX + 8, floorY + 6, floorW - 16, 6)
    this.staticLayer?.add(skirting)

    const topGlow = this.add.graphics()
    topGlow.fillGradientStyle(0xffffff, 0xffffff, 0xffffff, 0xffffff, 0.16, 0.16, 0, 0)
    topGlow.fillEllipse(floorX + floorW / 2, floorY + 14, floorW * 0.88, 56)
    this.staticLayer?.add(topGlow)
  }

  private drawSeats(width: number, height: number) {
    const { zones, totalCols, aisleCount } = this.layout
    const floorLeft = FLOOR_SIDE + 22
    // Push the first desk row down to leave a clear walkway for the teacher
    // across the front of the room.
    const floorY = FLOOR_TOP + 20 + FRONT_WALKWAY
    const floorW = width - (FLOOR_SIDE + 22) * 2
    const floorH = height - FLOOR_TOP - FLOOR_BOTTOM - 64 - FRONT_WALKWAY
    const aisleWidth = Math.max(28, Math.min(48, floorW * 0.05))
    const zoneGap = 10
    const interZoneGaps = Math.max(0, zones.length - 1)
    const availableW = floorW - aisleWidth * aisleCount - zoneGap * (totalCols - zones.length)
    const deskCellW = Math.max(60, Math.min(112, availableW / totalCols))
    const rows = Math.max(1, Math.ceil(this.sceneData.totalSeats / totalCols))
    const rowGap = ROW_GAP_FIXED

    // Center the whole grid (zones + aisles) horizontally within the floor
    // so 1:1 or 2:2 on wide screens does not hug the left wall.
    const totalGridW =
      zones.reduce((sum, z) => sum + z * deskCellW + (z - 1) * zoneGap, 0) +
      interZoneGaps * aisleWidth
    const floorX = floorLeft + Math.max(0, (floorW - totalGridW) / 2)

    // Compute zone start X positions left-to-right with aisle gaps between zones.
    const zoneStarts: number[] = []
    let cursor = floorX
    for (let z = 0; z < zones.length; z++) {
      zoneStarts.push(cursor)
      cursor += zones[z] * deskCellW + (zones[z] - 1) * zoneGap
      if (z < interZoneGaps) cursor += aisleWidth
    }

    // Anchor door & teacher to the actual seat-grid center (not canvas center).
    const lastZoneEnd = zoneStarts[zones.length - 1] + zones[zones.length - 1] * deskCellW + (zones[zones.length - 1] - 1) * zoneGap
    this.gridCenterX = (zoneStarts[0] + lastZoneEnd) / 2
    this.teacherSeatGeometry = { zoneStarts, deskCellW, zoneGap, floorY, rowGap, rows, aisleWidth }

    this.drawAisles(zoneStarts, zones, deskCellW, zoneGap, aisleWidth, floorY, rows, rowGap)

    this.sceneData.seats.forEach((seat, index) => {
      const { x, y } = this.getSeatPos(index, totalCols, zoneStarts, zones, deskCellW, zoneGap, floorY, rowGap)
      const seatContainer = this.createSeat(seat, x, y, deskCellW)
      this.dynamicLayer?.add(seatContainer)
      this.seatRefs.set(seat.course_member_id, { container: seatContainer })
    })

    // Phase C: Empty placeholders
    for (let i = this.sceneData.seats.length; i < this.sceneData.totalSeats; i++) {
      const { x, y } = this.getSeatPos(i, totalCols, zoneStarts, zones, deskCellW, zoneGap, floorY, rowGap)
      const emptySeat = this.createEmptySeat(x, y, deskCellW, i + 1)
      this.dynamicLayer?.add(emptySeat)
    }
  }

  private getSeatPos(index: number, totalCols: number, zoneStarts: number[], zones: number[], deskCellW: number, zoneGap: number, floorY: number, rowGap: number) {
    const row = Math.floor(index / totalCols)
    const logicalCol = index % totalCols
    let zoneIndex = 0
    let zoneCol = logicalCol
    let acc = 0
    for (let z = 0; z < zones.length; z++) {
      if (logicalCol < acc + zones[z]) { zoneIndex = z; zoneCol = logicalCol - acc; break }
      acc += zones[z]
    }
    const x = zoneStarts[zoneIndex] + zoneCol * (deskCellW + zoneGap) + deskCellW / 2
    const y = floorY + row * rowGap + 32
    return { x, y }
  }

  private createEmptySeat(x: number, y: number, deskCellW: number, num: number) {
    const container = this.add.container(x, y)
    const deskTopW = Math.min(78, deskCellW - 8)
    const deskDepth = 16

    const avatarGhost = this.add.circle(0, -28, 13, 0xffffff, 0)
    avatarGhost.setStrokeStyle(2, 0x8c795f, 0.5)

    const desk = this.add.graphics()
    const topFace: FacePoints = [
      facePoint(-deskTopW / 2, 0),
      facePoint(0, -deskDepth),
      facePoint(deskTopW / 2, 0),
      facePoint(0, deskDepth),
    ]
    desk.lineStyle(1.5, 0x8b5e3c, 0.25)
    
    // Simple outline for placeholder
    desk.beginPath()
    desk.moveTo(topFace[0].x, topFace[0].y)
    for (let i = 1; i < topFace.length; i++) desk.lineTo(topFace[i].x, topFace[i].y)
    desk.closePath()
    desk.strokePath()

    const seatNum = this.add.text(0, 8, `${num}`, {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '10px',
      color: '#8b5e3c',
      fontStyle: 'bold',
    }).setOrigin(0.5).setAlpha(0.3)

    container.add([avatarGhost, desk, seatNum])
    return container
  }

  private drawAisles(zoneStarts: number[], zones: number[], deskCellW: number, zoneGap: number, aisleWidth: number, floorY: number, rows: number, rowGap: number) {
    const aisleHeight = rows * rowGap + 36
    // Aisle sits between zone z and zone z+1
    for (let z = 0; z < zones.length - 1; z++) {
      const zoneEnd = zoneStarts[z] + zones[z] * deskCellW + (zones[z] - 1) * zoneGap
      const x = zoneEnd + 2
      const aisle = this.add.graphics()
      aisle.fillStyle(0xf2d8ac, 0.68)
      aisle.fillRoundedRect(x, floorY - 10, aisleWidth - 4, aisleHeight, 10)
      aisle.lineStyle(1.5, 0xe3c089, 0.55)
      aisle.strokeRoundedRect(x, floorY - 10, aisleWidth - 4, aisleHeight, 10)

      aisle.lineStyle(2, 0xe3c089, 0.5)
      const centerX = x + (aisleWidth - 4) / 2
      for (let yy = floorY - 2; yy < floorY + aisleHeight - 10; yy += 16) {
        aisle.lineBetween(centerX, yy, centerX, Math.min(yy + 8, floorY + aisleHeight - 10))
      }
      this.staticLayer?.add(aisle)
    }
  }

  private createSeat(seat: SeatData, x: number, y: number, deskCellW: number) {
    const container = this.add.container(x, y)
    const deskTopW = Math.min(78, deskCellW - 8)
    const deskTopH = 18
    const deskDepth = 16
    const avatarRadius = 14
    const isMe = seat.course_member_id === this.sceneData.authMemberId
    const isSelected = seat.course_member_id === this.sceneData.selectedMemberId
    const arriving = isArriving(seat, this.sceneData.serverTimeMs)

    const tone = this.seatTone(seat.status, arriving)
    container.setData('seat', seat) // for status lookups in differential render
    const desk = this.add.graphics()
    const leftFace: FacePoints = [
      facePoint(-deskTopW / 2, 0),
      facePoint(0, deskDepth),
      facePoint(0, deskDepth + deskTopH),
      facePoint(-deskTopW / 2, deskTopH),
    ]
    const rightFace: FacePoints = [
      facePoint(deskTopW / 2, 0),
      facePoint(0, deskDepth),
      facePoint(0, deskDepth + deskTopH),
      facePoint(deskTopW / 2, deskTopH),
    ]
    const topFace: FacePoints = [
      facePoint(-deskTopW / 2, 0),
      facePoint(0, -deskDepth),
      facePoint(deskTopW / 2, 0),
      facePoint(0, deskDepth),
    ]
    desk.fillStyle(tone.left, 1)
    desk.fillPoints(leftFace, true)
    desk.fillStyle(tone.right, 1)
    desk.fillPoints(rightFace, true)
    desk.fillStyle(tone.top, 1)
    desk.fillPoints(topFace, true)
    desk.lineStyle(1.5, tone.stroke, 1)
    desk.strokePoints(topFace, true)

    const shadow = this.add.ellipse(0, deskTopH + 24, deskTopW, 10, 0x5e8c34, seat.status === ATTENDANCE_STATUS.ABSENT ? 0.18 : 0.28)
    shadow.setName('shadow')
    const isLeave = seat.status === ATTENDANCE_STATUS.LEAVE
    const shoulders = this.add.rectangle(0, SEAT_SHOULDERS_Y, SEAT_SHOULDERS_W, SEAT_SHOULDERS_H, tone.stroke, isLeave ? 0.45 : 0.85)
    shoulders.setName('shoulders')
    const body = this.add.rectangle(0, SEAT_BODY_Y, SEAT_BODY_W, SEAT_BODY_H, tone.shirt, isLeave ? 0.45 : 1)
    body.setName('body')
    const leaveHatch = this.add.graphics()
    leaveHatch.setName('leaveHatch')
    this.drawLeaveHatch(leaveHatch, isLeave)

    const { circle: avatar } = this.drawAvatar(container, 0, -28, { avatar: seat.avatar, name: seat.name }, avatarRadius)
    avatar.setName('avatar')
    avatar.setStrokeStyle(3, tone.ring, 1)

    desk.setName('desk')
    const seatBadge = this.add.circle(0, 8, 10, tone.badge, 1)
    seatBadge.setName('seatBadge')
    const seatNum = this.add.text(0, 8, `${seat.seat_number}`, {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '10px',
      color: '#ffffff',
      fontStyle: 'bold',
    }).setOrigin(0.5)

    const nameText = this.add.text(0, 42, this.truncateLabel(seat.name, deskCellW < 74 ? 10 : 14), {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '10px',
      color: seat.status === ATTENDANCE_STATUS.ABSENT ? '#8C795F' : '#4A3220',
      fontStyle: isMe ? 'bold' : 'normal',
    }).setOrigin(0.5)

    const timeText = seat.time_in
      ? this.add.text(0, 56, seat.time_in, {
          fontFamily: 'monospace',
          fontSize: '9px',
          color: tone.time,
        }).setOrigin(0.5)
      : null

    container.add([shadow, shoulders, body, leaveHatch, avatar, desk, seatBadge, seatNum, nameText])
    if (timeText) container.add(timeText)

    container.setData('memberId', seat.course_member_id)
    container.setData('deskCellW', deskCellW)
    container.setData('targetY', y)

    if (isMe) {
      const meBadge = this.add.text(0, -48, 'คุณ', {
        fontFamily: 'Tahoma, sans-serif',
        fontSize: '9px',
        backgroundColor: '#7C3AED',
        color: '#ffffff',
        padding: { x: 4, y: 2 },
        fontStyle: 'bold',
      }).setOrigin(0.5)
      container.add(meBadge)
    }

    if (isSelected || isMe) {
      const ring = this.add.circle(0, 16, 40, 0x000000, 0)
      ring.setStrokeStyle(isSelected ? 3 : 2, 0x7c3aed, isSelected ? 1 : 0.55)
      container.addAt(ring, 0)
    }

    container.setSize(deskCellW, 80)
    container.setInteractive(
      new Phaser.Geom.Rectangle(-deskCellW / 2, -52, deskCellW, 120),
      Phaser.Geom.Rectangle.Contains,
    )
    container.on('pointerdown', () => this.handlers.onSeatSelect?.(seat.course_member_id))
    const nameLimit = deskCellW < 74 ? 10 : 14
    const fullName = (seat.name || '').trim()
    const isTruncated = fullName.length > nameLimit
    container.on('pointerover', () => {
      if (isTruncated) this.showNameTooltip(x, y, fullName)
      if (!this.sceneData.isCourseAdmin && !isMe) return
      this.tweens.add({
        targets: container,
        y: y - 2,
        duration: 120,
        overwrite: true,
      })
    })
    container.on('pointerout', () => {
      this.hideNameTooltip()
      this.tweens.add({
        targets: container,
        y,
        duration: 120,
        overwrite: true,
      })
    })

    return container
  }

  private updateSeatStatuses() {
    this.sceneData.seats.forEach((seat) => {
      const ref = this.seatRefs.get(seat.course_member_id)
      if (!ref) return

      const container = ref.container
      const arriving = isArriving(seat, this.sceneData.serverTimeMs)
      const tone = this.seatTone(seat.status, arriving)

      // Look up children by name (set in createSeat) — robust against list
      // re-ordering caused by drawAvatar adding children before the explicit
      // container.add() call.
      const desk = container.getByName('desk') as Phaser.GameObjects.Graphics
      const body = container.getByName('body') as Phaser.GameObjects.Rectangle
      const shoulders = container.getByName('shoulders') as Phaser.GameObjects.Rectangle | null
      const leaveHatch = container.getByName('leaveHatch') as Phaser.GameObjects.Graphics | null
      const avatar = container.getByName('avatar') as Phaser.GameObjects.Arc
      const seatBadge = container.getByName('seatBadge') as Phaser.GameObjects.Arc
      if (!desk || !body || !avatar || !seatBadge) return
      
      const deskTopW = Math.min(78, (container.getData('deskCellW') || 80) - 8)
      const deskTopH = 18
      const deskDepth = 16

      desk.clear()
      const leftFace: FacePoints = [
        facePoint(-deskTopW / 2, 0),
        facePoint(0, deskDepth),
        facePoint(0, deskDepth + deskTopH),
        facePoint(-deskTopW / 2, deskTopH),
      ]
      const rightFace: FacePoints = [
        facePoint(deskTopW / 2, 0),
        facePoint(0, deskDepth),
        facePoint(0, deskDepth + deskTopH),
        facePoint(deskTopW / 2, deskTopH),
      ]
      const topFace: FacePoints = [
        facePoint(-deskTopW / 2, 0),
        facePoint(0, -deskDepth),
        facePoint(deskTopW / 2, 0),
        facePoint(0, deskDepth),
      ]
      desk.fillStyle(tone.left, 1)
      desk.fillPoints(leftFace, true)
      desk.fillStyle(tone.right, 1)
      desk.fillPoints(rightFace, true)
      desk.fillStyle(tone.top, 1)
      desk.fillPoints(topFace, true)
      desk.lineStyle(1.5, tone.stroke, 1)
      desk.strokePoints(topFace, true)

      const isLeave = seat.status === ATTENDANCE_STATUS.LEAVE
      body.setFillStyle(tone.shirt, isLeave ? 0.45 : 1)
      shoulders?.setFillStyle(tone.stroke, isLeave ? 0.45 : 0.85)
      if (leaveHatch) this.drawLeaveHatch(leaveHatch, isLeave)
      avatar.setStrokeStyle(3, tone.ring, 1)
      seatBadge.setFillStyle(tone.badge, 1)

      // Update time text if it exists
      const timeText = container.list.find(c => c instanceof Phaser.GameObjects.Text && (c as any).style.fontFamily === 'monospace') as Phaser.GameObjects.Text
      if (timeText && seat.time_in) {
        timeText.setText(seat.time_in)
        timeText.setColor(tone.time)
      }
    })
  }

  private doorActionContainer?: Phaser.GameObjects.Container
  private doorContainer?: Phaser.GameObjects.Container

  private drawDoor(width: number, height: number) {
    const doorX = this.gridCenterX || width / 2
    // A2: doorY = floorY + floorH - 24
    const rows = Math.max(1, Math.ceil(this.sceneData.totalSeats / this.layout.totalCols))
    const floorY = FLOOR_TOP
    const floorH = FRONT_WALKWAY + rows * ROW_GAP_FIXED + 20
    const doorY = floorY + floorH - 24
    const door = this.add.container(doorX, doorY)

    const frame = this.add.graphics()
    frame.fillGradientStyle(0x9a6b3f, 0x9a6b3f, 0x7a5230, 0x7a5230, 1)
    frame.fillRoundedRect(-72, -8, 144, 86, 18)
    frame.lineStyle(4, 0x5c3d24, 1)
    frame.strokeRoundedRect(-72, -8, 144, 86, 18)
    frame.fillStyle(0x6b4a2b, 0.45)
    frame.fillRoundedRect(-54, 8, 108, 14, 10)
    door.add(frame)

    const knob = this.add.circle(44, 40, 5, 0xfacc15, 0.95)
    door.add(knob)
    this.tweens.add({
      targets: knob,
      scale: { from: 1, to: 1.15 },
      alpha: { from: 0.8, to: 1 },
      duration: 1900,
      yoyo: true,
      repeat: -1,
    })

    // A3: Removed door chip as it is redundant

    this.doorActionContainer = this.createDoorAction()
    this.doorActionContainer.setPosition(0, 8)
    door.add(this.doorActionContainer)

    if (this.sceneData.doorState.canCheckIn) {
      door.setSize(180, 120)
      door.setInteractive(new Phaser.Geom.Rectangle(-90, -56, 180, 120), Phaser.Geom.Rectangle.Contains)
      door.on('pointerdown', () => this.handlers.onDoorClick?.())
      door.on('pointerover', () => {
        this.tweens.add({ targets: door, scaleX: 1.02, scaleY: 1.02, duration: 120, overwrite: true })
      })
      door.on('pointerout', () => {
        this.tweens.add({ targets: door, scaleX: 1, scaleY: 1, duration: 120, overwrite: true })
      })
    }

    this.doorContainer = door
    door.setDepth(1) // Above floor, below sprites
    this.dynamicLayer?.add(door)
  }

  private updateDoorAction() {
    if (!this.doorActionContainer || !this.doorContainer) return
    this.doorActionContainer.destroy(true)
    this.doorActionContainer = this.createDoorAction()
    this.doorActionContainer.setPosition(0, 8)
    this.doorContainer.add(this.doorActionContainer)
  }

  private createDoorAction() {
    const chip = this.add.container(0, 0)
    const { doorState } = this.sceneData

    let bgColor = 0xf8fafc
    let textColor = '#64748B'
    let label = 'ยังไม่พร้อมรายงานตัว'

    if (doorState.canCheckIn) {
      bgColor = doorState.willBeLate ? 0xf59e0b : 0x10b981
      textColor = '#FFFFFF'
      label = doorState.willBeLate ? 'รายงานตัว (สาย)' : 'เข้าห้องเรียน / รายงานตัว'
    } else if (doorState.reason === 'already-checked-in') {
      bgColor = 0x10b981
      textColor = '#FFFFFF'
      label = 'รายงานตัวแล้ว'
    } else if (doorState.reason === 'not-started') {
      label = `เริ่มในอีก ${this.formatDuration(doorState.countdown || 0)}`
    } else if (doorState.reason === 'ended') {
      bgColor = 0xef4444
      textColor = '#FFFFFF'
      label = 'จบคาบแล้ว'
    } else if (doorState.reason === 'on-leave') {
      bgColor = 0x0ea5e9
      textColor = '#FFFFFF'
      label = 'คุณลาแล้ว'
    }

    const background = this.add.graphics()
    background.fillStyle(bgColor, doorState.canCheckIn || doorState.reason === 'already-checked-in' || doorState.reason === 'ended' || doorState.reason === 'on-leave' ? 1 : 0.9)
    background.fillRoundedRect(-86, -16, 172, 34, 17)
    if (!doorState.canCheckIn) {
      background.lineStyle(1, 0xcbd5e1, 1)
      background.strokeRoundedRect(-86, -16, 172, 34, 17)
    }

    const text = this.add.text(0, 0, label, {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '12px',
      color: textColor,
      fontStyle: 'bold',
      align: 'center',
    }).setOrigin(0.5)

    chip.add([background, text])
    return chip
  }

  // ─── Teacher patrol ──────────────────────────────────────────────────────
  // Draws a teacher sprite that wanders the front of the class and the side
  // aisles, pausing next to desks (weighted toward students marked late).
  // Hidden on narrow viewports to avoid clutter.

  private drawTeacher(width: number) {
    // T0: Safety Pre-flight - stop patrol + destroy old sprite to prevent orphans
    this.stopTeacherPatrol()
    if (this.teacherSprite) {
      this.teacherSprite.destroy()
      this.teacherSprite = undefined
      this.teacherPersonRef = undefined
    }

    // Invalidate any in-flight patrol from the previous render pass.
    this.teacherPatrolToken++
    this.teacherLoopActive = false
    if (!this.sceneData.teacher) return
    if (width < 640) return // mobile: no teacher sprite (compact view)
    if (!this.teacherSeatGeometry) return

    const geom = this.teacherSeatGeometry
    const startX = this.gridCenterX
    const startY = FLOOR_TOP + 28 + FRONT_WALKWAY / 2
    const teacher = this.add.container(startX, startY)

    // Warm wooden palette to match the farm-game classroom (distinct from
    // students' emerald shirts). Bigger than student sprites so it reads as
    // an authority figure even at a glance.
    const HEAD_R = 18
    const BORDER = 0x7A5230
    const SHIRT = 0x8B5E3C
    const SHIRT_DARK = 0x5C3D24

    // Subtle ground shadow under the teacher's feet for grounding
    const shadow = this.add.ellipse(0, 28, HEAD_R * 2.2, 6, 0x000000, 0.22)

    // Body: gradient-ish two-tone rect (shirt + collar)
    const person = this.add.container(0, 0)
    person.setName('person')
    const body = this.add.rectangle(0, 18, 28, 16, SHIRT, 1)
    body.setStrokeStyle(1.5, SHIRT_DARK, 1)
    const collar = this.add.rectangle(0, 11, 14, 4, SHIRT_DARK, 1)
    person.add([body, collar])

    // Head: white circle with a brown ring (matches original `border-2 border-[#7A5230] bg-white`)
    const { circle: head } = this.drawAvatar(person, 0, -4, { avatar: this.sceneData.teacher.avatar, name: this.sceneData.teacher.name }, HEAD_R)
    head.setStrokeStyle(2.5, BORDER, 1)

    // T3: Eyes (Visual Polish) — two dots that stay on the head
    const leftEye = this.add.circle(-5, -6, 2, 0x5C3D24, 1)
    const rightEye = this.add.circle(5, -6, 2, 0x5C3D24, 1)
    person.add([leftEye, rightEye])
    this.teacherEyes = [leftEye, rightEye]

    // Name label below the body (truncated)
    const fullName = this.sceneData.teacher.name
    const displayName = fullName.length > 12 ? fullName.slice(0, 11) + '…' : fullName
    const nameLabel = this.add.text(0, 36, displayName, {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '10px',
      color: '#FFFFFF',
      backgroundColor: '#7A5230',
      padding: { x: 5, y: 2 },
      fontStyle: 'bold',
    }).setOrigin(0.5)

    // The bob animates this inner group so it doesn't fight the patrol's y-tweens
    // on the outer container.
    teacher.add([shadow, person, nameLabel])
    teacher.setSize(TEACHER_W, 64)
    teacher.setInteractive(new Phaser.Geom.Rectangle(-TEACHER_W / 2, -24, TEACHER_W, 72), Phaser.Geom.Rectangle.Contains)
    teacher.on('pointerdown', () => this.showTeacherTooltip(teacher))
    teacher.setDepth(5)

    this.teacherSprite = teacher
    this.teacherPersonRef = person
    this.dynamicLayer?.add(teacher)

    // T4: ensure patrol stops cleanly when scene shuts down (hook once)
    if (!this.teacherShutdownHooked) {
      this.events.once(Phaser.Scenes.Events.SHUTDOWN, () => this.stopTeacherPatrol())
      this.events.once(Phaser.Scenes.Events.DESTROY, () => this.stopTeacherPatrol())
      this.teacherShutdownHooked = true
    }

    if (!this.prefersReducedMotion()) {
      // Walking bob — matches the original 0.45s alternate translateY animation.
      this.tweens.add({
        targets: person,
        y: -3,
        duration: 450,
        yoyo: true,
        repeat: -1,
        ease: 'Sine.InOut',
      })
    }

    this.teacherLoopActive = true
    this.startTeacherPatrol(geom)
  }

  private prefersReducedMotion() {
    if (typeof window === 'undefined') return false
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false
  }

  private showTeacherTooltip(teacher: Phaser.GameObjects.Container) {
    if (!this.sceneData.teacher) return
    const tip = this.add.text(0, -28, `ครู: ${this.sceneData.teacher.name}`, {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '11px',
      backgroundColor: '#1E3A8A',
      color: '#ffffff',
      padding: { x: 6, y: 3 },
    }).setOrigin(0.5)
    teacher.add(tip)
    this.tweens.add({
      targets: tip,
      alpha: { from: 1, to: 0 },
      delay: 1600,
      duration: 400,
      onComplete: () => tip.destroy(),
    })
  }

  // T4: Stop any in-flight patrol — invalidate token + kill tweens on the sprite.
  private stopTeacherPatrol() {
    this.teacherLoopActive = false
    this.teacherPatrolToken++
    if (this.teacherSprite) {
      this.tweens.killTweensOf(this.teacherSprite)
      if (this.teacherPersonRef) this.tweens.killTweensOf(this.teacherPersonRef)
    }
    this.destroyThinkDots()
  }

  // T1: Compute X positions of aisle centers between zones (real aisles, not grid center).
  private computeAisleXs(geom: NonNullable<AttendanceRoomScene['teacherSeatGeometry']>): number[] {
    const xs: number[] = []
    const zoneCount = geom.zoneStarts.length
    for (let z = 0; z < zoneCount - 1; z++) {
      const zoneCols = this.layout.zones[z]
      const zoneEnd = geom.zoneStarts[z] + zoneCols * geom.deskCellW + (zoneCols - 1) * geom.zoneGap
      xs.push(zoneEnd + geom.aisleWidth / 2)
    }
    return xs
  }

  private faceTeacher(towardX: number) {
    if (!this.teacherPersonRef || !this.teacherSprite) return
    const facing = towardX >= this.teacherSprite.x ? 1 : -1
    this.teacherPersonRef.setScale(facing, 1)
  }

  private showThinkDots() {
    this.destroyThinkDots()

    if (this.prefersReducedMotion() || !this.teacherSprite) return

    const dotsContainer = this.add.container(this.teacherSprite.x, this.teacherSprite.y - 30)
    dotsContainer.setDepth(6)
    if (this.dynamicLayer) {
      this.dynamicLayer.add(dotsContainer)
    }
    this.teacherThinkDots = dotsContainer

    const g1 = this.add.graphics()
    g1.fillStyle(0xffffff, 1)
    g1.fillCircle(0, 0, 3)
    g1.lineStyle(1.2, 0x7A5230, 1)
    g1.strokeCircle(0, 0, 3)
    g1.setPosition(-12, 0)
    g1.setAlpha(0)
    g1.setScale(0)

    const g2 = this.add.graphics()
    g2.fillStyle(0xffffff, 1)
    g2.fillCircle(0, 0, 3)
    g2.lineStyle(1.2, 0x7A5230, 1)
    g2.strokeCircle(0, 0, 3)
    g2.setPosition(0, 0)
    g2.setAlpha(0)
    g2.setScale(0)

    const g3 = this.add.graphics()
    g3.fillStyle(0xffffff, 1)
    g3.fillCircle(0, 0, 3)
    g3.lineStyle(1.2, 0x7A5230, 1)
    g3.strokeCircle(0, 0, 3)
    g3.setPosition(12, 0)
    g3.setAlpha(0)
    g3.setScale(0)

    dotsContainer.add([g1, g2, g3])

    this.tweens.add({
      targets: g1,
      alpha: 1,
      scaleX: 1,
      scaleY: 1,
      duration: 350,
      ease: 'Back.Out',
    })
    this.tweens.add({
      targets: g2,
      alpha: 1,
      scaleX: 1,
      scaleY: 1,
      delay: 150,
      duration: 350,
      ease: 'Back.Out',
    })
    this.tweens.add({
      targets: g3,
      alpha: 1,
      scaleX: 1,
      scaleY: 1,
      delay: 300,
      duration: 350,
      ease: 'Back.Out',
    })
  }

  private destroyThinkDots() {
    if (this.teacherThinkDots) {
      this.teacherThinkDots.destroy()
      this.teacherThinkDots = undefined
    }
  }

  private startTeacherPatrol(geom: NonNullable<AttendanceRoomScene['teacherSeatGeometry']>) {
    if (!this.teacherSprite) return
    const rand = (min: number, max: number) => min + Math.random() * (max - min)
    const teacher = this.teacherSprite
    const isReduced = this.prefersReducedMotion()
    const myToken = this.teacherPatrolToken

    // Walk mid-way through the reserved front walkway (between board and row 1).
    const frontY = FLOOR_TOP + 28 + FRONT_WALKWAY / 2
    const lastZoneIdx = geom.zoneStarts.length - 1
    const lastZoneCols = this.layout.zones[lastZoneIdx]
    const leftEdge = geom.zoneStarts[0] + 12
    const rightEdge =
      geom.zoneStarts[lastZoneIdx] + lastZoneCols * geom.deskCellW + (lastZoneCols - 1) * geom.zoneGap - 12

    const isStale = () => myToken !== this.teacherPatrolToken || !this.teacherLoopActive || !this.teacherSprite || !this.sys.isActive()

    const step = () => {
      this.destroyThinkDots()
      if (isStale()) return

      // T5/T6/O: Responsive & Reduced Motion
      // desktop -> full patrol (40% inspect, any aisle)
      // tablet  -> 20% inspect but only first aisle
      // mobile  -> front-only
      const w = this.scale.width
      const inspectChance = isReduced ? 0 : w >= 1024 ? 0.4 : w >= 768 ? 0.2 : 0
      const inspectRow = Math.random() < inspectChance

      // Reduced motion: 3x slower travel
      const speedMult = isReduced ? 3 : 1

      if (inspectRow) {
        // T1/O: Pick aisle — desktop random, tablet first aisle only
        const aisleXs = this.computeAisleXs(geom)
        const aisleX = aisleXs.length === 0
          ? this.gridCenterX
          : w >= 1024
            ? aisleXs[Math.floor(Math.random() * aisleXs.length)]
            : aisleXs[0]
        const row = Math.floor(rand(0, Math.max(1, geom.rows)))
        const targetY = geom.floorY + (row + 0.5) * geom.rowGap

        this.faceTeacher(aisleX)

        this.tweens.add({
          targets: teacher,
          x: aisleX,
          y: frontY,
          duration: 900 * speedMult,
          ease: 'Sine.InOut',
          onComplete: () => {
            if (isStale()) {
              this.destroyThinkDots()
              return
            }
            this.tweens.add({
              targets: teacher,
              y: targetY,
              duration: 1400 * speedMult,
              ease: 'Sine.InOut',
              onComplete: () => {
                if (isStale()) {
                  this.destroyThinkDots()
                  return
                }
                // T8: Use delay on the return tween for stability
                const pauseDelay = rand(1200, 2600) * speedMult
                if (pauseDelay >= 1200) {
                  this.showThinkDots()
                }
                this.tweens.add({
                  targets: teacher,
                  x: this.gridCenterX,
                  y: frontY,
                  delay: pauseDelay,
                  duration: 1400 * speedMult,
                  ease: 'Sine.InOut',
                  onStart: () => {
                    this.destroyThinkDots()
                    this.faceTeacher(this.gridCenterX)
                  },
                  onComplete: step,
                })
              },
            })
          },
        })
      } else {
        const targetX = rand(leftEdge, rightEdge)
        this.faceTeacher(targetX)

        this.tweens.add({
          targets: teacher,
          x: targetX,
          y: frontY,
          duration: rand(2200, 3600) * speedMult,
          ease: 'Sine.InOut',
          onComplete: () => {
            if (isStale()) return
            // T8: Pause via delay
            this.tweens.add({
              targets: teacher,
              x: targetX,
              delay: rand(500, 1500) * speedMult,
              duration: 1, // dummy duration
              onComplete: step,
            })
          },
        })
      }
    }

    step()
  }

  private playArrivalTweens() {
    const arrivingNow = new Set(
      this.sceneData.seats
        .filter((seat) => isArriving(seat, this.sceneData.serverTimeMs))
        .map((seat) => seat.course_member_id),
    )

    arrivingNow.forEach((memberId) => {
      if (this.lastArrivalIds.has(memberId)) return
      const seat = this.sceneData.seats.find((s) => s.course_member_id === memberId)
      const ref = this.seatRefs.get(memberId)
      if (!seat || !ref) return

      // Hide the seat occupant briefly while a walker sprite covers the distance
      // from the door to the desk — restored when the walk completes.
      if (!this.prefersReducedMotion()) {
        this.spawnStudentWalker(seat, ref.container)
      }

      ref.pulse = this.tweens.add({
        targets: ref.container,
        scaleX: { from: 0.92, to: 1 },
        scaleY: { from: 0.92, to: 1 },
        alpha: { from: 0.65, to: 1 },
        duration: 420,
        delay: this.prefersReducedMotion() ? 0 : 2400,
        ease: 'Back.Out',
      })
    })

    this.lastArrivalIds = arrivingNow
  }

  private spawnStudentWalker(seat: SeatData, seatContainer: Phaser.GameObjects.Container) {
    const seatX = seatContainer.x
    const seatY = seatContainer.y
    const doorX = this.gridCenterX || this.scale.width / 2
    const rows = Math.max(1, Math.ceil(this.sceneData.totalSeats / this.layout.totalCols))
    const floorY = FLOOR_TOP
    const floorH = FRONT_WALKWAY + rows * ROW_GAP_FIXED + 20
    const doorY = floorY + floorH - 24

    const walker = this.add.container(doorX, doorY)
    const person = this.add.container(0, 0)
    person.setName('person')
    walker.add(person)

    const { circle: avatar } = this.drawAvatar(person, 0, 0, { avatar: seat.avatar, name: seat.name }, 11)
    avatar.setStrokeStyle(2, 0x10b981, 1)
    const body = this.add.rectangle(0, 14, 14, 8, 0x059669, 1)
    person.add(body)
    person.sendToBack(body)
    
    walker.setDepth(20) // Well above everything
    walker.setAlpha(0)
    this.dynamicLayer?.add(walker)

    // Walking bob for walker
    this.tweens.add({
      targets: person,
      y: -2,
      duration: 300,
      yoyo: true,
      repeat: -1,
      ease: 'Sine.InOut'
    })

    // Trail effect
    const trail = this.add.graphics()
    this.dynamicLayer?.add(trail)

    seatContainer.setAlpha(0.001)

    this.tweens.chain({
      targets: walker,
      tweens: [
        { alpha: 1, duration: 220 },
        { 
          y: seatY, 
          duration: 1300, 
          ease: 'Sine.InOut',
          onUpdate: () => {
            if (!trail.active) return
            trail.clear()
            trail.lineStyle(2, 0x10b981, 0.4)
            trail.lineBetween(doorX, doorY, walker.x, walker.y)
          }
        },
        { 
          x: seatX, 
          duration: 900, 
          ease: 'Sine.InOut',
          onStart: () => {
              // T2/T7: Flip toward movement
              person.setScale(seatX > doorX ? 1 : -1, 1)
          },
          onUpdate: () => {
            if (!trail.active) return
            trail.clear()
            trail.lineStyle(2, 0x10b981, 0.4)
            trail.beginPath()
            trail.moveTo(doorX, doorY)
            trail.lineTo(doorX, seatY)
            trail.lineTo(walker.x, walker.y)
            trail.strokePath()
          }
        },
        { alpha: 0, duration: 200 },
      ],
      onComplete: () => {
        walker.destroy()
        if (trail.active) {
          this.tweens.add({
            targets: trail,
            alpha: 0,
            duration: 800,
            onComplete: () => trail.destroy()
          })
        }
        seatContainer.setAlpha(1)
        
        const impact = this.add.circle(seatX, seatY, 4, 0xfacc15, 0.8)
        this.dynamicLayer?.add(impact)
        this.tweens.add({
          targets: impact,
          scale: 8,
          alpha: 0,
          duration: 500,
          ease: 'Quad.Out',
          onComplete: () => impact.destroy()
        })
      },
    })
  }

  private seatTone(status: number, arriving: boolean) {
    if (arriving) {
      return {
        top: 0xd9a05b,
        left: 0xb97f3f,
        right: 0x9a6630,
        stroke: 0xa9763b,
        ring: 0x34d399,
        badge: 0x34d399,
        shirt: 0x059669,
        time: '#065F46',
      }
    }

    switch (status) {
      case ATTENDANCE_STATUS.PRESENT:
        return {
          top: 0xd9a05b,
          left: 0xb97f3f,
          right: 0x9a6630,
          stroke: 0xa9763b,
          ring: 0x10b981,
          badge: 0x10b981,
          shirt: 0x059669,
          time: '#065F46',
        }
      case ATTENDANCE_STATUS.LATE:
        return {
          top: 0xd9a05b,
          left: 0xb97f3f,
          right: 0x9a6630,
          stroke: 0xa9763b,
          ring: 0xf59e0b,
          badge: 0xf59e0b,
          shirt: 0xd97706,
          time: '#92400E',
        }
      case ATTENDANCE_STATUS.LEAVE:
        return {
          top: 0xd3bc93,
          left: 0xbfa176,
          right: 0xa98c64,
          stroke: 0xb09a72,
          ring: 0x0ea5e9,
          badge: 0x0ea5e9,
          shirt: 0x0284c7,
          time: '#0C4A6E',
        }
      default:
        return {
          top: 0xd3bc93,
          left: 0xbfa176,
          right: 0xa98c64,
          stroke: 0xb09a72,
          ring: 0x94a3b8,
          badge: 0x94a3b8,
          shirt: 0x94a3b8,
          time: '#64748B',
        }
    }
  }

  private clockToneColor(tone?: string | null) {
    switch (tone) {
      case 'sky': return '#7DD3FC'
      case 'emerald': return '#86EFAC'
      case 'amber': return '#FCD34D'
      case 'red': return '#FCA5A5'
      default: return '#BFE0C9'
    }
  }

  private getInitials(name: string) {
    const words = name.trim().split(/\s+/).filter(Boolean)
    return words.slice(0, 2).map((word) => word[0]).join('').toUpperCase() || '?'
  }

  private truncateLabel(value: string | null | undefined, max: number) {
    const normalized = (value || '').trim()
    if (!normalized) return ''
    return normalized.length > max ? `${normalized.slice(0, Math.max(0, max - 1))}…` : normalized
  }

  private showNameTooltip(seatX: number, seatY: number, fullName: string) {
    this.hideNameTooltip()
    const sceneWidth = this.scale.width
    const label = this.add.text(0, 0, fullName, {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: '11px',
      color: '#ffffff',
      padding: { x: 8, y: 4 },
    }).setOrigin(0.5)
    const bgWidth = label.width + 8
    const bgHeight = label.height + 4
    const bg = this.add.graphics()
    bg.fillStyle(0x1a1a1a, 0.92)
    bg.fillRoundedRect(-bgWidth / 2, -bgHeight / 2, bgWidth, bgHeight, 6)

    const tip = this.add.container(0, 0, [bg, label])
    const clampedX = Math.max(bgWidth / 2 + 8, Math.min(sceneWidth - bgWidth / 2 - 8, seatX))
    tip.setPosition(clampedX, seatY - 60)
    tip.setDepth(50)
    tip.setAlpha(0)
    this.dynamicLayer?.add(tip)
    this.tweens.add({ targets: tip, alpha: 1, duration: 120 })
    this.hoverTooltip = tip
  }

  private hideNameTooltip() {
    if (!this.hoverTooltip) return
    const tip = this.hoverTooltip
    this.hoverTooltip = undefined
    this.tweens.add({
      targets: tip,
      alpha: 0,
      duration: 100,
      onComplete: () => tip.destroy(),
    })
  }

  /**
   * Phase E: Draw a circular avatar — async-loads the real image when a URL is
   * available, falls back to initials text otherwise. The returned `circle` is
   * the head shape callers can re-stroke for status color.
   *
   * Image loading is best-effort: the initials are drawn synchronously so the
   * UI is never blank, and the image overlay is added on top once decoded.
   */
  private drawAvatar(
    parent: Phaser.GameObjects.Container,
    x: number,
    y: number,
    person: { avatar: string | null; name: string },
    radius: number,
  ): { circle: Phaser.GameObjects.Arc } {
    const circle = this.add.circle(x, y, radius, 0xffffff, 1)
    const initials = this.add.text(x, y, this.getInitials(person.name), {
      fontFamily: 'Tahoma, sans-serif',
      fontSize: `${Math.max(10, Math.round(radius * 0.85))}px`,
      color: '#334155',
      fontStyle: 'bold',
    }).setOrigin(0.5)
    parent.add([circle, initials])

    const resolved = this.handlers.getAvatarUrl?.(person) || person.avatar || null
    if (!resolved) return { circle }

    const key = `avatar-${resolved}`
    const apply = () => {
      if (!parent.active) return
      const img = this.add.image(x, y, key)
      const d = (radius - 1) * 2
      img.setDisplaySize(d, d)
      // Stencil-mask the image to a circle of the same radius as the head.
      const mask = this.make.graphics({ x: 0, y: 0 }, false)
      const updateMask = () => {
        mask.clear()
        mask.fillStyle(0xffffff)
        const world = parent.getWorldTransformMatrix()
        mask.fillCircle(world.tx + x, world.ty + y, radius - 1)
      }
      updateMask()
      img.setMask(mask.createGeometryMask())
      // Keep the mask following the parent (teacher patrols, walker moves).
      const onUpdate = () => updateMask()
      this.events.on('postupdate', onUpdate)
      img.once('destroy', () => this.events.off('postupdate', onUpdate))
      initials.setVisible(false)
      parent.add(img)
    }

    if (this.textures.exists(key)) {
      apply()
    } else {
      this.load.image(key, resolved)
      this.load.once(`filecomplete-image-${key}`, apply)
      this.load.once('loaderror', () => { /* keep initials */ })
      if (!this.load.isLoading()) this.load.start()
    }
    return { circle }
  }
  private drawLeaveHatch(graphic: Phaser.GameObjects.Graphics, visible: boolean) {
    graphic.clear()
    graphic.setVisible(visible)
    if (!visible) return

    graphic.lineStyle(2, LEAVE_HATCH_COLOR, 0.35)
    const left = -SEAT_BODY_W / 2
    const right = SEAT_BODY_W / 2
    const top = SEAT_BODY_Y - SEAT_BODY_H / 2
    const bottom = SEAT_BODY_Y + SEAT_BODY_H / 2
    const lines: Array<[number, number, number, number]> = [
      [left + 1, top + 3, left + 7, bottom - 1],
      [left + 7, top + 1, right - 1, bottom - 1],
      [left + 13, top + 1, right - 1, top + 7],
    ]

    lines.forEach(([x1, y1, x2, y2]) => {
      graphic.lineBetween(x1, y1, x2, y2)
    })
  }

  private formatDuration(ms: number) {
    const total = Math.max(0, Math.floor(ms / 1000))
    const h = Math.floor(total / 3600)
    const m = Math.floor((total % 3600) / 60)
    const s = total % 60
    if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  }
}
