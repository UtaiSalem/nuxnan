<template>
  <div class="hexagon-avatar-container" :style="containerStyle">
    <canvas 
      ref="hexagonCanvas"
      :width="width"
      :height="height"
      :style="canvasStyle"
    ></canvas>
    <div v-if="showBadge" class="hexagon-badge" :style="badgeStyle">
      <canvas
        ref="badgeCanvas"
        :width="badgeSize"
        :height="badgeSize"
      ></canvas>
      <div class="badge-text" :style="badgeTextStyle">{{ level }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';

// Define props
const props = defineProps({
  // ขนาดของ hexagon
  width: {
    type: Number,
    default: 82
  },
  height: {
    type: Number,
    default: 90
  },
  // รูปภาพ avatar
  imageSrc: {
    type: String,
    required: true
  },
  // ตัวเลือก level
  level: {
    type: [String, Number],
    default: ''
  },
  // สีของเส้นขอบ (เดิม)
  lineColor: {
    type: String,
    default: '#ffffff'
  },
  // ความกว้างของเส้นขอบ (เดิม)
  lineWidth: {
    type: Number,
    default: 7
  },
  // การมนวนขอบ
  roundedCorners: {
    type: Boolean,
    default: true
  },
  roundedCornerRadius: {
    type: Number,
    default: 9
  },
  // สีพื้นหลัง
  fillColor: {
    type: String,
    default: '#45437f'
  },
  // แสดง badge หรือไม่
  showBadge: {
    type: Boolean,
    default: true
  },
  // ขนาดของ badge
  badgeSize: {
    type: Number,
    default: 22
  },
  // ระดับการศึกษา (elementary, secondary, university)
  educationLevel: {
    type: String,
    default: 'elementary', // elementary, secondary, university
    validator: (value) => ['elementary', 'secondary', 'university'].includes(value)
  },
  // ระดับชั้นเรียน (1-6)
  gradeLevel: {
    type: Number,
    default: 1,
    validator: (value) => value >= 1 && value <= 6
  },
  // จำนวนชั้นของเส้นกรอบ (1-6) - ไม่ใช้แล้ว กำหนดอัตโนมัติตามระดับการศึกษา
  borderLayers: {
    type: Number,
    default: 1,
    validator: (value) => value >= 1 && value <= 6
  },
  // จำนวนด้านที่เสร็จสิ้นในชั้นปัจจุบัน (0-6) - แสดงปีการศึกษาปัจจุบัน
  completedSides: {
    type: Number,
    default: 1,
    validator: (value) => value >= 0 && value <= 6
  },
  // ขนาด responsive (เปอร์เซ็นต์ของความกว้างหน้าจอ)
  responsive: {
    type: Boolean,
    default: false
  },
  // ขนาดสูงสุดเมื่อ responsive
  maxWidth: {
    type: Number,
    default: 150
  }
});

// Define emits
const emit = defineEmits(['loaded']);

// Template refs
const hexagonCanvas = ref(null);
const badgeCanvas = ref(null);

// Reactive state
const isLoaded = ref(false);

// Computed properties
const containerStyle = computed(() => {
  const style = {
    position: 'relative'
  };
  
  if (props.responsive) {
    style.width = '100%';
    style.maxWidth = `${props.maxWidth}px`;
    style.height = 'auto';
    style.aspectRatio = `${props.width} / ${props.height}`;
  } else {
    style.width = `${props.width}px`;
    style.height = `${props.height}px`;
  }
  
  return style;
});

const canvasStyle = computed(() => {
  const style = {
    position: 'absolute',
    top: 0,
    left: 0
  };
  
  if (props.responsive) {
    style.width = '100%';
    style.height = '100%';
  }
  
  return style;
});

// สีของเส้นขอบตามระดับการศึกษา
const educationLevelColors = computed(() => {
  switch (props.educationLevel) {
    case 'elementary':
      return '#4CAF50'; // เขียว - ประถมศึกษา
    case 'secondary':
      return '#2196F3'; // น้ำเงิน - มัธยมศึกษา
    case 'university':
      return '#9C27B0'; // ม่วง - ปริญญาตรี
    default:
      return '#4CAF50';
  }
});

// สีของเส้นขอบตามระดับชั้นเรียน (1-6)
const gradeLevelColors = computed(() => {
  switch (props.gradeLevel) {
    case 1:
      return '#FF6B6B'; // แดง - ชั้น 1
    case 2:
      return '#4ECDC4'; // เขียวมะนาว - ชั้น 2
    case 3:
      return '#45B7D1'; // ฟ้า - ชั้น 3
    case 4:
      return '#96CEB4'; // เขียวอ่อน - ชั้น 4
    case 5:
      return '#DDA0DD'; // ม่วงอ่อน - ชั้น 5
    case 6:
      return '#FFD700'; // ทอง - ชั้น 6
    default:
      return '#FF6B6B';
  }
});

// Compute border color based on level (เดิม)
const levelBorderColor = computed(() => {
  const level = parseInt(props.level) || 0;
  
  if (level >= 50) {
    return '#FFD700'; // Gold for legendary levels (50+)
  } else if (level >= 40) {
    return '#C0C0C0'; // Silver for expert levels (40-49)
  } else if (level >= 30) {
    return '#CD7F32'; // Bronze for advanced levels (30-39)
  } else if (level >= 20) {
    return '#9370DB'; // Purple for skilled levels (20-29)
  } else if (level >= 10) {
    return '#4169E1'; // Blue for intermediate levels (10-19)
  } else {
    return '#32CD32'; // Green for beginner levels (1-9)
  }
});

const badgeSize = computed(() => {
  // คำนวณขนาด badge ให้สัมพันธ์กับขนาดของ hexagon (ลดขนาดลง)
  return props.responsive ?
    Math.max(12, Math.min(32, props.width * 0.25)) :
    Math.max(12, props.badgeSize * 0.7); // ลดขนาด badge ลง 30%
});

const badgeStyle = computed(() => {
  const size = badgeSize.value;
  
  return {
    position: 'absolute',
    top: props.responsive ? '5%' : '96px', // เปลี่ยนจาก bottom เป็น top
    right: props.responsive ? '5%' : '8px',
    width: `${size}px`,
    height: `${size}px`,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 20, // เพิ่ม z-index ให้สูงขึ้นเพื่อแสดงด้านบน
    boxShadow: '0 2px 4px rgba(0, 0, 0, 0)'
  }
});

const badgeTextStyle = computed(() => {
  const size = badgeSize.value;
  
  return {
    position: 'absolute',
    color: '#ffffff',
    fontSize: props.responsive ? `${Math.max(7, size * 0.35)}px` : '8px', // ลดขนาดตัวอักษร
    fontWeight: 'bold',
    zIndex: 21, // สูงกว่า badge container
    pointerEvents: 'none'
  }
});

// Methods
function initHexagon() {
  const canvas = hexagonCanvas.value;
  if (!canvas) return;
  
  const ctx = canvas.getContext('2d');
  
  // โหลดรูปภาพ
  const img = new Image();
  img.onload = () => {
    drawHexagon(ctx, img);
    isLoaded.value = true;
    emit('loaded');
  };
  img.src = props.imageSrc;
}

function drawHexagon(ctx, img) {
  const { width, height } = props;
  
  // ล้าง canvas
  ctx.clearRect(0, 0, width, height);
  
  // สร้าง clipping path สำหรับ hexagon
  ctx.save();
  createHexagonPath(ctx);
  ctx.clip();
  
  // วาดรูปภาพใน hexagon
  const imgSize = Math.max(width, height);
  const offsetX = (width - imgSize) / 2;
  const offsetY = (height - imgSize) / 2;
  
  ctx.drawImage(img, offsetX, offsetY, imgSize, imgSize);
  ctx.restore();
  
  // วาดเส้นขอบ hexagon (หลายชั้น)
  drawHexagonBorders(ctx);
  
  // วาด badge รูปหกเหลี่ยม
  if (props.showBadge) {
    drawHexagonBadge();
  }
}

function createHexagonPath(ctx) {
  const { width, height, roundedCorners, roundedCornerRadius } = props;
  const centerX = width / 2;
  const centerY = height / 2;
  
  // กำหนดจำนวนชั้นขอบตามระดับการศึกษา
  let actualBorderLayers = props.borderLayers;
  if (props.educationLevel === 'elementary') {
    actualBorderLayers = 1; // ประถมศึกษา = 1 ชั้น
  } else if (props.educationLevel === 'secondary') {
    actualBorderLayers = 2; // มัธยมศึกษา = 2 ชั้น
  } else if (props.educationLevel === 'university') {
    actualBorderLayers = 3; // มหาวิทยาลัย = 3 ชั้น
  }
  
  const radiusX = width / 2 - (props.lineWidth * actualBorderLayers) / 2;
  const radiusY = height / 2 - (props.lineWidth * actualBorderLayers) / 2;
  
  ctx.beginPath();
  
  if (roundedCorners && roundedCornerRadius > 0) {
    drawPerfectRoundedHexagon(ctx, centerX, centerY, radiusX, radiusY, roundedCornerRadius);
  } else {
    drawPerfectHexagon(ctx, centerX, centerY, radiusX, radiusY);
  }
  
  ctx.closePath();
}

function drawPerfectHexagon(ctx, centerX, centerY, radiusX, radiusY) {
  // สร้าง hexagon สมมาตรโดยใช้มุม 60 องศา (π/3)
  // เริ่มจากจุดสูงสุด (ด้านบน) และวนขวาตามเข็มนาฬิกา
  const angleStep = Math.PI / 3;
  let angle = -Math.PI / 2; // เริ่มจากด้านบน (270 องศา หรือ -90 องศา)
  
  for (let i = 0; i <= 6; i++) {
    const x = centerX + radiusX * Math.cos(angle);
    const y = centerY + radiusY * Math.sin(angle);
    
    if (i === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
    
    angle += angleStep; // หมุนตามเข็มนาฬิกา
  }
}

function drawPerfectRoundedHexagon(ctx, centerX, centerY, radiusX, radiusY, radius) {
  // สร้าง hexagon สมมาตรพร้อมมุมมนวน
  // เริ่มจากจุดสูงสุด (ด้านบน) และวนขวาตามเข็มนาฬิกา
  const angleStep = Math.PI / 3;
  const startAngle = -Math.PI / 2; // เริ่มจากด้านบน
  const points = [];
  
  // คำนวณจุดทั้ง 6 จุดของ hexagon
  for (let i = 0; i < 6; i++) {
    const angle = startAngle + (i * angleStep);
    points.push({
      x: centerX + radiusX * Math.cos(angle),
      y: centerY + radiusY * Math.sin(angle)
    });
  }
  
  // วาด hexagon ด้วยมุมมนวน
  for (let i = 0; i < 6; i++) {
    const current = points[i];
    const next = points[(i + 1) % 6];
    const prev = points[(i + 5) % 6];
    
    // คำนวณทิศทางจาก prev ไป current
    const dx1 = current.x - prev.x;
    const dy1 = current.y - prev.y;
    const len1 = Math.sqrt(dx1 * dx1 + dy1 * dy1);
    
    // คำนวณทิศทางจาก current ไป next
    const dx2 = next.x - current.x;
    const dy2 = next.y - current.y;
    const len2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
    
    // จุดเริ่มต้นของเส้น (เว้นระยะ radius จากมุม)
    const startX = current.x - (dx1 / len1) * radius;
    const startY = current.y - (dy1 / len1) * radius;
    
    // จุดสิ้นสุดของเส้น (เว้นระยะ radius จากมุม)
    const endX = current.x + (dx2 / len2) * radius;
    const endY = current.y + (dy2 / len2) * radius;
    
    if (i === 0) {
      ctx.moveTo(startX, startY);
    } else {
      ctx.lineTo(startX, startY);
    }
    
    // สร้างมุมมนวนด้วย quadratic curve
    ctx.quadraticCurveTo(current.x, current.y, endX, endY);
  }
}

function drawHexagonBorders(ctx) {
  const { lineWidth, width, height } = props;
  
  // กำหนดจำนวนชั้นขอบตามระดับการศึกษา
  let actualBorderLayers = props.borderLayers;
  if (props.educationLevel === 'elementary') {
    actualBorderLayers = 1; // ประถมศึกษา = 1 ชั้น
  } else if (props.educationLevel === 'secondary') {
    actualBorderLayers = 2; // มัธยมศึกษา = 2 ชั้น
  } else if (props.educationLevel === 'university') {
    actualBorderLayers = 3; // มหาวิทยาลัย = 3 ชั้น
  }
  
  // วาดเส้นขอบหลายชั้น (จากนอกสู่ใน)
  for (let layer = actualBorderLayers - 1; layer >= 0; layer--) {
    const layerOffset = layer * lineWidth;
    const adjustedLineWidth = lineWidth;
    
    const centerX = width / 2;
    const centerY = height / 2;
    const radiusX = width / 2 - layerOffset - adjustedLineWidth / 2;
    const radiusY = height / 2 - layerOffset - adjustedLineWidth / 2;
    
    // กำหนดสีสำหรับแต่ละชั้น
    let layerColor;
    if (layer === actualBorderLayers - 1) {
      // ชั้นนอกสุด (ชั้นปัจจุบัน) - ใช้สีตามปีการศึกษา (gradeLevel)
      layerColor = gradeLevelColors.value;
    } else {
      // ชั้นที่เสร็จสิ้นแล้ว - ใช้สีตามระดับการศึกษา
      layerColor = educationLevelColors.value;
    }
    
    // วาดเส้นขอบแบบมีด้านสีขาว (สำหรับชั้นปัจจุบัน) - แสดงความคืบหน้าปีการศึกษา
    if (layer === actualBorderLayers - 1) {
      drawPartialHexagonBorder(ctx, centerX, centerY, radiusX, radiusY, layerColor, props.gradeLevel);
    } else {
      // วาดเส้นขอบแบบปกติ (สำหรับชั้นที่เสร็จสิ้นแล้ว)
      ctx.strokeStyle = layerColor;
      ctx.lineWidth = adjustedLineWidth;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      
      ctx.beginPath();
      if (props.roundedCorners && props.roundedCornerRadius > 0) {
        drawPerfectRoundedHexagon(ctx, centerX, centerY, radiusX, radiusY, props.roundedCornerRadius);
      } else {
        drawPerfectHexagon(ctx, centerX, centerY, radiusX, radiusY);
      }
      ctx.closePath();
      ctx.stroke();
    }
    
    // เพิ่มขอบสีขาวระหว่างชั้น (ยกเว้นชั้นสุดท้าย)
    if (layer > 0) {
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 1; // เส้นขอบสีขาวบางๆ
      
      ctx.beginPath();
      const whiteRadiusX = width / 2 - layerOffset - adjustedLineWidth + 1;
      const whiteRadiusY = height / 2 - layerOffset - adjustedLineWidth + 1;
      
      if (props.roundedCorners && props.roundedCornerRadius > 0) {
        drawPerfectRoundedHexagon(ctx, centerX, centerY, whiteRadiusX, whiteRadiusY, props.roundedCornerRadius);
      } else {
        drawPerfectHexagon(ctx, centerX, centerY, whiteRadiusX, whiteRadiusY);
      }
      
      ctx.closePath();
      ctx.stroke();
    }
  }
}

function drawPartialHexagonBorder(ctx, centerX, centerY, radiusX, radiusY, color, gradeLevel) {
  const angleStep = Math.PI / 3; // 60 องศา
  let startAngle = -Math.PI / 2; // เริ่มจากด้านบน (จุดสูงสุด) และวนขวาตามเข็มนาฬิกา
  
  ctx.lineWidth = props.lineWidth;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  
  // วาดด้านที่เสร็จสิ้นแล้วตามปีการศึกษา (gradeLevel)
  if (gradeLevel > 0) {
    ctx.strokeStyle = color;
    ctx.beginPath();
    
    for (let i = 0; i < gradeLevel; i++) {
      const angle1 = startAngle + (i * angleStep);
      const angle2 = startAngle + ((i + 1) * angleStep);
      
      const x1 = centerX + radiusX * Math.cos(angle1);
      const y1 = centerY + radiusY * Math.sin(angle1);
      const x2 = centerX + radiusX * Math.cos(angle2);
      const y2 = centerY + radiusY * Math.sin(angle2);
      
      if (i === 0) {
        ctx.moveTo(x1, y1);
      }
      ctx.lineTo(x2, y2);
    }
    ctx.stroke();
  }
  
  // วาดด้านที่ยังไม่เสร็จสิ้น (สีขาว) - ปีการศึกษาที่ยังไม่ถึง
  if (gradeLevel < 6) {
    ctx.strokeStyle = '#ffffff';
    ctx.beginPath();
    
    for (let i = gradeLevel; i < 6; i++) {
      const angle1 = startAngle + (i * angleStep);
      const angle2 = startAngle + ((i + 1) * angleStep);
      
      const x1 = centerX + radiusX * Math.cos(angle1);
      const y1 = centerY + radiusY * Math.sin(angle1);
      const x2 = centerX + radiusX * Math.cos(angle2);
      const y2 = centerY + radiusY * Math.sin(angle2);
      
      if (i === gradeLevel) {
        ctx.moveTo(x1, y1);
      }
      ctx.lineTo(x2, y2);
    }
    ctx.stroke();
  }
}

function drawHexagonBadge() {
  const canvas = badgeCanvas.value;
  if (!canvas) return;
  
  const ctx = canvas.getContext('2d');
  const size = badgeSize.value;
  
  // ล้าง canvas
  ctx.clearRect(0, 0, size, size);
  
  // วาดหกเหลี่ยม - เริ่มจากจุดสูงสุดและวนขวาตามเข็มนาฬิกา
  const centerX = size / 2;
  const centerY = size / 2;
  const radius = size / 2 - 2; // เว้นขอบเล็กน้อย
  
  ctx.beginPath();
  const angleStep = Math.PI / 3;
  let angle = -Math.PI / 2; // เริ่มจากด้านบน
  
  for (let i = 0; i <= 6; i++) {
    const x = centerX + radius * Math.cos(angle);
    const y = centerY + radius * Math.sin(angle);
    
    if (i === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
    
    angle += angleStep; // หมุนตามเข็มนาฬิกา
  }
  
  ctx.closePath();
  
  // เติมสี
  ctx.fillStyle = levelBorderColor.value;
  ctx.fill();
  
  // วาดขอบ
  ctx.strokeStyle = '#ffffff';
  ctx.lineWidth = 1;
  ctx.stroke();
}

// Lifecycle hooks
onMounted(() => {
  initHexagon();
  
  // ถ้าเป็น responsive ให้เพิ่ม event listener สำหรับ resize
  if (props.responsive) {
    window.addEventListener('resize', handleResize);
  }
});

// Watchers
watch(() => props.imageSrc, () => {
  initHexagon();
});

watch(() => props.width, () => {
  initHexagon();
});

watch(() => props.height, () => {
  initHexagon();
});

watch(() => props.level, () => {
  initHexagon();
});

watch(() => props.educationLevel, () => {
  initHexagon();
});

watch(() => props.gradeLevel, () => {
  initHexagon();
});

watch(() => props.completedSides, () => {
  initHexagon();
});

watch(() => props.borderLayers, () => {
  initHexagon();
});

watch(() => props.level, () => {
  initHexagon();
});

watch(() => props.showBadge, () => {
  initHexagon();
});

// Handle window resize for responsive mode
function handleResize() {
  if (props.responsive) {
    initHexagon();
  }
}
</script>

<style scoped>
.hexagon-avatar-container {
  display: inline-block;
  position: relative;
}

.hexagon-badge {
  font-family: Arial, sans-serif;
  position: relative;
}

.badge-text {
  font-family: Arial, sans-serif;
}
</style>
