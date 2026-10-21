<script setup lang="ts">
// 3D procedural hand guide for Key Training (adapted from typing-3d-demo).
// Raises the target finger as a steady guide + pulses it on each keypress.
// fingerIndex: 0=L.Pinky 1=L.Ring 2=L.Middle 3=L.Index
//              4=R.Index 5=R.Middle 6=R.Ring 7=R.Pinky 8=Thumb
import type { Group, Mesh, MeshStandardMaterial, WebGLRenderer, Scene, OrthographicCamera } from 'three'
import FingerGuide from './FingerGuide.vue'

const props = withDefaults(defineProps<{
  fingerIndex: number | null
  pressTick?: number
  status?: 'correct' | 'incorrect' | 'none'
}>(), {
  pressTick: 0,
  status: 'none',
})

// index → finger key used in the 3D rig
const NAMES = [
  'leftPinky', 'leftRing', 'leftMiddle', 'leftIndex',
  'rightIndex', 'rightMiddle', 'rightRing', 'rightPinky',
  'thumb',
]
const COLORS = [
  0xef4444, 0xf97316, 0xfbbf24, 0x22c55e,
  0x2dd4bf, 0x3b82f6, 0x8b5cf6, 0xec4899,
  0x64748b,
]
const SKIN_L = 0xffc1a7
const SKIN_R = 0xffd0b6

const mount = ref<HTMLElement | null>(null)
let THREE: typeof import('three')
let renderer: WebGLRenderer | undefined
let scene: Scene
let camera: OrthographicCamera
const failed = ref(false)
let disposed = false
let reducedMotion = false
let frame = 0
let observer: ResizeObserver | undefined

interface FingerRig {
  pivot: Group
  tipPivot: Group
  mats: MeshStandardMaterial[]
  base: number
  side: 'left' | 'right'
  isThumb: boolean
}
const fingers: Record<string, FingerRig> = {}

const fingerData = [
  { name: 'Pinky',  x: -0.72, length: 1.02, r: 0.13 },
  { name: 'Ring',   x: -0.38, length: 1.31, r: 0.145 },
  { name: 'Middle', x: 0,     length: 1.45, r: 0.15 },
  { name: 'Index',  x: 0.38,  length: 1.29, r: 0.145 },
]

function makeHand(side: 'left' | 'right') {
  const skin = side === 'left' ? SKIN_L : SKIN_R
  const hand = new THREE.Group()
  const palm = new THREE.Mesh(
    new THREE.SphereGeometry(1, 24, 16),
    new THREE.MeshStandardMaterial({ color: skin, roughness: 0.68 }),
  )
  palm.position.set(0, -0.5, 0)
  palm.scale.set(0.88, 0.72, 0.24)
  hand.add(palm)
  const wrist = new THREE.Mesh(
    new THREE.CapsuleGeometry(0.36, 0.45, 6, 16),
    new THREE.MeshStandardMaterial({ color: skin, roughness: 0.68 }),
  )
  wrist.position.set(0, -1.18, -0.03)
  wrist.scale.z = 0.6
  hand.add(wrist)

  for (const digit of fingerData) {
    const pivot = new THREE.Group()
    pivot.position.set(digit.x, -0.05, 0)
    const mat1 = new THREE.MeshStandardMaterial({ color: skin, roughness: 0.62 })
    const seg1 = new THREE.Mesh(new THREE.CapsuleGeometry(digit.r, digit.length * 0.48, 5, 10), mat1)
    seg1.position.y = digit.length * 0.32
    pivot.add(seg1)
    const tipPivot = new THREE.Group()
    tipPivot.position.y = digit.length * 0.64
    pivot.add(tipPivot)
    const mat2 = new THREE.MeshStandardMaterial({ color: skin, roughness: 0.62 })
    const tip = new THREE.Mesh(new THREE.CapsuleGeometry(digit.r * 0.88, digit.length * 0.29, 5, 10), mat2)
    tip.position.y = digit.length * 0.22
    tipPivot.add(tip)
    const nail = new THREE.Mesh(
      new THREE.SphereGeometry(1, 12, 8),
      new THREE.MeshStandardMaterial({ color: 0xffeadf, roughness: 0.4 }),
    )
    nail.scale.set(digit.r * 0.65, digit.r * 0.95, 0.025)
    nail.position.set(0, digit.length * 0.29, digit.r * 0.86)
    tipPivot.add(nail)
    fingers[side + digit.name] = { pivot, tipPivot, mats: [mat1, mat2], base: 0, side, isThumb: false }
    hand.add(pivot)
  }

  const thumbPivot = new THREE.Group()
  // Build both hands in left-hand coordinates; mirror the entire right hand once.
  thumbPivot.position.set(0.68, -0.65, 0.07)
  thumbPivot.rotation.z = -0.85
  const tmat = new THREE.MeshStandardMaterial({ color: skin, roughness: 0.62 })
  const thumb = new THREE.Mesh(new THREE.CapsuleGeometry(0.16, 0.68, 6, 12), tmat)
  thumb.position.y = 0.37
  thumbPivot.add(thumb)
  fingers[side + 'Thumb'] = { pivot: thumbPivot, tipPivot: thumbPivot, mats: [tmat], base: thumbPivot.rotation.z, side, isThumb: true }
  hand.add(thumbPivot)

  hand.position.set(side === 'left' ? -1.65 : 1.65, -0.05, 0)
  if (side === 'right') hand.scale.x = -1
  scene.add(hand)
}

function keyFor(name: string) {
  if (name.endsWith('Thumb')) return 'thumb'
  return name
}

function resize() {
  if (!mount.value || !renderer) return
  const w = mount.value.clientWidth
  const h = mount.value.clientHeight
  if (!w || !h) return
  renderer.setSize(w, h, false)
  // Fit BOTH dimensions, including fingertips and wrists on wide/short panels.
  const halfHeight = Math.max(1.95, 3.25 * h / w)
  camera.left = -halfHeight * w / h
  camera.right = halfHeight * w / h
  camera.top = halfHeight
  camera.bottom = -halfHeight
  camera.updateProjectionMatrix()
}

function activeName() {
  return props.fingerIndex !== null ? NAMES[props.fingerIndex] : ''
}
function activeColor() {
  return props.fingerIndex !== null ? COLORS[props.fingerIndex] : null
}

function animate() {
  frame = requestAnimationFrame(animate)
  const target = activeName()
  const col = activeColor()
  const since = props.pressTick ? Date.now() - props.pressTick : Infinity
  const pulse = !reducedMotion && since < 600 ? Math.sin(Math.PI * Math.max(0, Math.min(1, since / 600))) : 0

  for (const [name, f] of Object.entries(fingers)) {
    const isTarget = keyFor(name) === target
    const amt = isTarget ? 0.55 + 0.45 * pulse : 0

    if (f.isThumb) {
      f.pivot.rotation.z = f.base - 0.25 * amt
    } else {
      f.pivot.rotation.x = THREE.MathUtils.lerp(f.pivot.rotation.x, -0.5 * amt, 0.18)
      f.tipPivot.rotation.x = THREE.MathUtils.lerp(f.tipPivot.rotation.x, isTarget ? -0.85 * amt : -0.12, 0.18)
    }

    const skin = f.side === 'left' ? SKIN_L : SKIN_R
    const want = isTarget && col !== null ? (props.status === 'incorrect' ? 0xef4444 : col) : skin
    for (const m of f.mats) {
      if (m.color.getHex() !== want) m.color.setHex(want)
    }
  }
  renderer!.render(scene, camera)
}

onMounted(async () => {
  try {
    THREE = await import('three')
    if (disposed || !mount.value) return
    reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    scene = new THREE.Scene()
    camera = new THREE.OrthographicCamera(-4.35, 4.35, 1, -1, 0.1, 100)
    camera.position.set(0, 0, 12)
    camera.lookAt(0, 0, 0)
    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true })
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2))
    renderer.setClearColor(0xffffff, 0)
    renderer.domElement.style.width = '100%'
    renderer.domElement.style.height = '100%'
    mount.value.appendChild(renderer.domElement)
    scene.add(new THREE.HemisphereLight(0xffffff, 0xc7a7a7, 2.3))
    const light = new THREE.DirectionalLight(0xffffff, 2.4)
    light.position.set(-2, 4, 9)
    scene.add(light)
    makeHand('left')
    makeHand('right')
    observer = new ResizeObserver(resize)
    observer.observe(mount.value)
    resize()
    animate()
  } catch {
    release()
    failed.value = true
  }
})

function release() {
  cancelAnimationFrame(frame)
  observer?.disconnect()
  scene?.traverse((object) => {
    const mesh = object as Mesh
    mesh.geometry?.dispose()
    if (mesh.material) {
      const materials = Array.isArray(mesh.material) ? mesh.material : [mesh.material]
      materials.forEach(material => material.dispose())
    }
  })
  renderer?.dispose()
  renderer?.domElement.remove()
}

onUnmounted(() => {
  disposed = true
  release()
})
</script>

<template>
  <FingerGuide v-if="failed" :finger-index="fingerIndex" :status="status" />
  <div v-else ref="mount" class="w-full h-full min-h-[150px] sm:min-h-[180px] xl:min-h-[120px]" aria-hidden="true"></div>
</template>
