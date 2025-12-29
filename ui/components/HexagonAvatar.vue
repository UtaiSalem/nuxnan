<template>
  <div 
    class="hexagon-wrapper"
    :class="[wrapperClass, { 'animated': animated }]"
    :style="wrapperStyle"
  >
    <!-- SVG Hexagon Border with Progress Style -->
    <svg 
      class="hexagon-border-svg"
      :viewBox="`0 0 ${svgSize} ${svgSize}`"
      :style="{ width: computedWidth, height: computedHeight }"
    >
      <defs>
        <!-- Gradient for active segments -->
        <linearGradient :id="`grad-active-${uniqueId}`" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" :stop-color="activeGradientColors[0]" />
          <stop offset="100%" :stop-color="activeGradientColors[1]" />
        </linearGradient>
        
        <!-- Clip path for inner image -->
        <clipPath :id="`clip-inner-${uniqueId}`">
          <polygon :points="innerHexagonPoints" />
        </clipPath>
      </defs>
      
      <!-- Background hexagon (inactive/gray) -->
      <polygon 
        :points="hexagonSvgPoints"
        fill="none"
        :stroke="inactiveColor"
        :stroke-width="computedBorderWidth"
        stroke-linejoin="round"
        stroke-linecap="round"
        class="hexagon-bg"
        style="stroke-miterlimit: 2;"
      />
      
      <!-- Active hexagon progress (colored based on grade) -->
      <polygon 
        :points="hexagonProgressPoints"
        fill="none"
        :stroke="`url(#grad-active-${uniqueId})`"
        :stroke-width="computedBorderWidth"
        stroke-linejoin="round"
        stroke-linecap="round"
        :stroke-dasharray="strokeDashArray"
        :stroke-dashoffset="strokeDashOffset"
        class="hexagon-progress-border"
        style="stroke-miterlimit: 2;"
      />
      
      <!-- Inner background -->
      <polygon 
        :points="innerHexagonPoints"
        :fill="bgColor"
        class="hexagon-inner-bg"
      />
    </svg>

    <!-- Inner Image Container -->
    <div 
      class="hexagon-inner"
      :style="innerContainerStyle"
    >
      <img 
        v-if="src"
        :src="src" 
        :alt="alt"
        class="hexagon-image"
        :style="imageStyle"
        @error="handleImageError"
      />
      <div 
        v-else 
        class="hexagon-fallback"
        :style="fallbackStyle"
      >
        <Icon :name="fallbackIcon" :style="{ fontSize: fallbackIconSize }" />
      </div>
    </div>

    <!-- Level Badge (Hexagon Shape) -->
    <div 
      v-if="showLevel && level !== null"
      class="hexagon-level-badge"
      :style="badgeContainerStyle"
    >
      <svg 
        :viewBox="`0 0 ${badgeSvgSize} ${badgeSvgSize}`"
        class="badge-svg"
      >
        <defs>
          <linearGradient :id="`badge-grad-${uniqueId}`" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" :stop-color="activeGradientColors[0]" />
            <stop offset="100%" :stop-color="activeGradientColors[1]" />
          </linearGradient>
        </defs>
        <polygon 
          :points="badgeHexagonPoints"
          :fill="`url(#badge-grad-${uniqueId})`"
          stroke="rgba(255,255,255,0.3)"
          stroke-width="1"
        />
      </svg>
      <span class="badge-text" :style="badgeTextStyle">{{ level }}</span>
    </div>

    <!-- Online Status Indicator -->
    <div 
      v-if="showOnlineStatus"
      class="hexagon-status"
      :class="[isOnline ? 'online' : 'offline', statusClass]"
      :style="statusStyle"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

// Types
interface GradientConfig {
  type?: 'linear' | 'radial'
  angle?: number
  colors: string[]
}

// Grade color schemes - ม.1 to ม.6
const gradeColors: Record<number, { color: string; gradient: string[] }> = {
  1: { color: '#4ade80', gradient: ['#4ade80', '#22c55e'] },   // Green - ม.1
  2: { color: '#38bdf8', gradient: ['#38bdf8', '#0ea5e9'] },   // Sky Blue - ม.2
  3: { color: '#a78bfa', gradient: ['#a78bfa', '#8b5cf6'] },   // Purple - ม.3
  4: { color: '#fb923c', gradient: ['#fb923c', '#f97316'] },   // Orange - ม.4
  5: { color: '#f472b6', gradient: ['#f472b6', '#ec4899'] },   // Pink - ม.5
  6: { color: '#fbbf24', gradient: ['#fbbf24', '#f59e0b'] },   // Gold - ม.6
}

// Level color tiers
const levelColorTiers: { minLevel: number; gradient: string[] }[] = [
  { minLevel: 1, gradient: ['#969696', '#6b6b6b'] },
  { minLevel: 5, gradient: ['#4fc35b', '#2eb82e'] },
  { minLevel: 10, gradient: ['#1bc5bd', '#0dceca'] },
  { minLevel: 20, gradient: ['#23d2e2', '#1abc9c'] },
  { minLevel: 30, gradient: ['#3b82f6', '#2563eb'] },
  { minLevel: 40, gradient: ['#615dfa', '#4338ca'] },
  { minLevel: 50, gradient: ['#f59e0b', '#d97706'] },
  { minLevel: 60, gradient: ['#ef4444', '#dc2626'] },
  { minLevel: 80, gradient: ['#ec4899', '#db2777'] },
  { minLevel: 100, gradient: ['#fbbf24', '#f59e0b'] },
]

function getLevelColorTier(level: number) {
  let tier = levelColorTiers[0]
  for (const t of levelColorTiers) {
    if (level >= t.minLevel) tier = t
  }
  return tier
}

// Props
const props = withDefaults(defineProps<{
  src?: string | null
  alt?: string
  fallbackIcon?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | number
  width?: number | string
  height?: number | string
  borderWidth?: number
  borderColor?: string
  borderGradient?: GradientConfig | null
  bgColor?: string
  grade?: number | null  // 1-6 for ม.1 to ม.6
  showLevel?: boolean
  level?: number | null
  useLevelBasedBorder?: boolean
  useGradeBasedBorder?: boolean
  inactiveSegmentColor?: string
  showOnlineStatus?: boolean
  isOnline?: boolean
  animated?: boolean
  wrapperClass?: string
  borderClass?: string
  innerClass?: string
  levelBadgeClass?: string
  statusClass?: string
}>(), {
  alt: 'Avatar',
  fallbackIcon: 'mdi:account',
  size: 'md',
  borderWidth: 8,
  borderColor: '#23d2e2',
  borderGradient: () => ({ type: 'linear', angle: 135, colors: ['#615dfa', '#23d2e2'] }),
  bgColor: '#1d2333',
  grade: 6,
  showLevel: false,  // Disabled by default - enable later
  level: null,
  useLevelBasedBorder: false,
  useGradeBasedBorder: true,
  inactiveSegmentColor: '#3a3f50',
  showOnlineStatus: false,
  isOnline: false,
  animated: false,
  wrapperClass: '',
  borderClass: '',
  innerClass: '',
  levelBadgeClass: '',
  statusClass: '',
})

const emit = defineEmits<{
  (e: 'error', event: Event): void
}>()

// Unique ID
const uniqueId = ref(`hex-${Math.random().toString(36).substr(2, 9)}`)

// Size presets
const sizePresets: Record<string, number> = {
  'xs': 40,
  'sm': 56,
  'md': 100,
  'lg': 140,
  'xl': 180,
  '2xl': 220,
  '3xl': 280,
}

// SVG size (viewBox)
const svgSize = 100

// Computed sizes
const computedSize = computed(() => {
  if (typeof props.size === 'number') return props.size
  return sizePresets[props.size] || sizePresets['md']
})

const computedWidth = computed(() => `${computedSize.value}px`)
const computedHeight = computed(() => `${computedSize.value}px`)

// Border width scaled to SVG viewBox
const computedBorderWidth = computed(() => {
  return Math.max(4, (props.borderWidth / computedSize.value) * svgSize)
})

// Inactive color
const inactiveColor = computed(() => props.inactiveSegmentColor)

// Active gradient colors
const activeGradientColors = computed(() => {
  if (props.useGradeBasedBorder && props.grade) {
    const gc = gradeColors[props.grade] || gradeColors[6]
    return gc.gradient
  }
  if (props.useLevelBasedBorder && props.level) {
    return getLevelColorTier(props.level).gradient
  }
  if (props.borderGradient) {
    return props.borderGradient.colors.slice(0, 2)
  }
  return ['#615dfa', '#23d2e2']
})

// Hexagon geometry calculations
// Pointy-top hexagon with center at (50, 50) and radius 45
const hexRadius = computed(() => (svgSize / 2) - (computedBorderWidth.value / 2) - 2)
const hexCenter = svgSize / 2

// Calculate hexagon vertices (pointy-top, starting from top)
const getHexagonVertices = (radius: number, cx: number, cy: number) => {
  const vertices = []
  for (let i = 0; i < 6; i++) {
    const angle = (Math.PI / 180) * (270 + i * 60)
    vertices.push({
      x: cx + radius * Math.cos(angle),
      y: cy + radius * Math.sin(angle)
    })
  }
  return vertices
}

// SVG polygon points string
const hexagonSvgPoints = computed(() => {
  const vertices = getHexagonVertices(hexRadius.value, hexCenter, hexCenter)
  return vertices.map(v => `${v.x},${v.y}`).join(' ')
})

// Progress polygon - rotated to start from top-right corner going clockwise
const hexagonProgressPoints = computed(() => {
  // Start from different vertex for proper progress animation
  // We want the progress to start from top and go clockwise
  const vertices = getHexagonVertices(hexRadius.value, hexCenter, hexCenter)
  // Reorder: start from top vertex (index 0)
  return vertices.map(v => `${v.x},${v.y}`).join(' ')
})

// Inner hexagon (for image clip)
const innerRadius = computed(() => hexRadius.value - computedBorderWidth.value / 2 - 2)
const innerHexagonPoints = computed(() => {
  const vertices = getHexagonVertices(innerRadius.value, hexCenter, hexCenter)
  return vertices.map(v => `${v.x},${v.y}`).join(' ')
})

// Calculate hexagon perimeter for stroke-dasharray
// For a regular hexagon inscribed in a circle with radius r, side length = r
// But we need the actual path length when drawing the polygon
const hexagonPerimeter = computed(() => {
  const vertices = getHexagonVertices(hexRadius.value, hexCenter, hexCenter)
  let perimeter = 0
  for (let i = 0; i < 6; i++) {
    const next = (i + 1) % 6
    const dx = vertices[next].x - vertices[i].x
    const dy = vertices[next].y - vertices[i].y
    perimeter += Math.sqrt(dx * dx + dy * dy)
  }
  return perimeter
})

// Stroke dash calculations for grade progress
const strokeDashArray = computed(() => {
  return hexagonPerimeter.value
})

const strokeDashOffset = computed(() => {
  // Grade 1-6 maps to 1/6 to 6/6 of perimeter
  const gradeValue = Math.min(6, Math.max(1, props.grade || 6))
  const filledFraction = gradeValue / 6
  const offset = hexagonPerimeter.value * (1 - filledFraction)
  return offset
})

// Wrapper style
const wrapperStyle = computed(() => ({
  width: computedWidth.value,
  height: computedHeight.value,
}))

// Inner container style
const innerContainerStyle = computed(() => {
  const innerSizePx = (innerRadius.value / hexRadius.value) * computedSize.value * 0.85
  return {
    width: `${innerSizePx}px`,
    height: `${innerSizePx}px`,
    clipPath: `polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%)`,
  }
})

// Image style
const imageStyle = computed(() => ({
  width: '100%',
  height: '100%',
  objectFit: 'cover' as const,
}))

// Fallback style
const fallbackStyle = computed(() => ({
  width: '100%',
  height: '100%',
}))

const fallbackIconSize = computed(() => `${computedSize.value * 0.4}px`)

// Badge calculations
const badgeSvgSize = 50
const badgeHexagonPoints = computed(() => {
  const vertices = getHexagonVertices(22, 25, 25)
  return vertices.map(v => `${v.x},${v.y}`).join(' ')
})

const badgeContainerStyle = computed(() => {
  const scale = computedSize.value / 100
  const size = Math.max(28, 45 * scale)
  return {
    width: `${size}px`,
    height: `${size}px`,
  }
})

const badgeTextStyle = computed(() => {
  const scale = computedSize.value / 100
  return {
    fontSize: `${Math.max(10, 16 * scale)}px`,
  }
})

// Status style
const statusStyle = computed(() => {
  const scale = computedSize.value / 100
  const size = Math.max(10, 14 * scale)
  return {
    width: `${size}px`,
    height: `${size}px`,
  }
})

// Event handlers
function handleImageError(event: Event) {
  emit('error', event)
}
</script>

<style scoped>
/* Hexagon Avatar with Progress-style Border */

.hexagon-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.hexagon-border-svg {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
}

.hexagon-bg {
  opacity: 0.6;
}

.hexagon-progress-border {
  transition: stroke-dashoffset 0.5s ease-out;
  filter: drop-shadow(0 0 6px currentColor);
}

.hexagon-inner {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: #1d2333;
}

.hexagon-image {
  object-fit: cover;
  transition: transform 0.3s ease;
}

.hexagon-wrapper:hover .hexagon-image {
  transform: scale(1.05);
}

.hexagon-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.6);
}

/* Level Badge - Hexagon shaped */
.hexagon-level-badge {
  position: absolute;
  bottom: -5%;
  right: -5%;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge-svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.badge-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-weight: 700;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

/* Online Status */
.hexagon-status {
  position: absolute;
  bottom: 10%;
  left: 5%;
  border-radius: 50%;
  border: 2px solid #1d2333;
  z-index: 15;
}

.hexagon-status.online {
  background: #40d04f;
  box-shadow: 0 0 8px rgba(64, 208, 79, 0.6);
  animation: pulse-online 2s ease-in-out infinite;
}

.hexagon-status.offline {
  background: #6b7280;
}

@keyframes pulse-online {
  0%, 100% {
    box-shadow: 0 0 8px rgba(64, 208, 79, 0.6);
  }
  50% {
    box-shadow: 0 0 16px rgba(64, 208, 79, 0.9);
  }
}

/* Animation */
.hexagon-wrapper.animated .hexagon-progress-border {
  animation: hexagon-glow 2s ease-in-out infinite;
}

@keyframes hexagon-glow {
  0%, 100% {
    filter: drop-shadow(0 0 6px currentColor);
  }
  50% {
    filter: drop-shadow(0 0 12px currentColor);
  }
}

/* Hover effects */
.hexagon-wrapper:hover .hexagon-progress-border {
  filter: drop-shadow(0 0 10px currentColor);
}
</style>
