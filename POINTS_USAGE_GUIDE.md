# 🎯 คู่มือ Real-time Points Update

## วิธีการใช้งานสำหรับนักพัฒนา

### 🚀 เริ่มต้นใช้งาน

#### 1. Import useAuthStore
```vue
<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>
```

#### 2. แสดงแต้มใน Template
```vue
<template>
  <div>
    แต้มสะสมของคุณ: {{ authStore.points.toLocaleString() }}
  </div>
</template>
```

#### 3. ตัดแต้มเมื่อทำ Action
```javascript
// Like โพสต์ (ตัด 24 แต้ม)
const handleLike = async () => {
  const success = authStore.deductPoints(24)
  
  if (!success) {
    // แสดง error message ว่าแต้มไม่พอ
    alert('แต้มของคุณไม่เพียงพอ')
    return
  }
  
  try {
    // เรียก API
    await axios.post(`/posts/${postId}/like`)
  } catch (error) {
    // ถ้า API error ให้คืนแต้ม
    authStore.rollback(24)
  }
}
```

#### 4. คืนแต้มเมื่อยกเลิก Action
```javascript
// Unlike โพสต์ (คืน 12 แต้ม)
const handleUnlike = async () => {
  authStore.addPoints(12)
  
  try {
    await axios.post(`/posts/${postId}/unlike`)
  } catch (error) {
    // ถ้า API error ให้ตัดแต้มกลับ
    authStore.deductPoints(12)
  }
}
```

### 💡 ตัวอย่างการใช้งานจริง

#### ตัวอย่าง 1: Like/Unlike System
```vue
<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isLiked = ref(false)
const isProcessing = ref(false)

const toggleLike = async () => {
  if (isProcessing.value) return
  isProcessing.value = true
  
  if (isLiked.value) {
    // Unlike
    authStore.addPoints(12)
    isLiked.value = false
    
    try {
      await axios.post(`/unlike`)
    } catch (error) {
      // Rollback
      authStore.deductPoints(12)
      isLiked.value = true
    }
  } else {
    // Like
    const success = authStore.deductPoints(24)
    
    if (!success) {
      alert('แต้มไม่เพียงพอ ต้องการ 24 แต้ม')
      isProcessing.value = false
      return
    }
    
    isLiked.value = true
    
    try {
      await axios.post(`/like`)
    } catch (error) {
      // Rollback
      authStore.rollback(24)
      isLiked.value = false
    }
  }
  
  isProcessing.value = false
}
</script>

<template>
  <div>
    <button 
      @click="toggleLike" 
      :disabled="isProcessing"
      :class="{ 'text-red-500': isLiked }"
    >
      {{ isLiked ? '❤️' : '🤍' }} Like
    </button>
    
    <div class="points-display">
      แต้มคงเหลือ: {{ authStore.points }}
    </div>
  </div>
</template>
```

#### ตัวอย่าง 2: ตรวจสอบแต้มก่อนทำ Action
```vue
<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// ตรวจสอบว่ามีแต้มพอหรือไม่
const canLike = computed(() => authStore.points >= 24)
const canDislike = computed(() => authStore.points >= 12)

const handleAction = async (action) => {
  const pointsRequired = action === 'like' ? 24 : 12
  
  if (authStore.points < pointsRequired) {
    Swal.fire({
      title: 'แต้มไม่เพียงพอ',
      text: `ต้องการ ${pointsRequired} แต้ม (คุณมี ${authStore.points} แต้ม)`,
      icon: 'warning'
    })
    return
  }
  
  // ทำ action ต่อ...
}
</script>

<template>
  <div>
    <button 
      @click="handleAction('like')" 
      :disabled="!canLike"
      :class="{ 'opacity-50 cursor-not-allowed': !canLike }"
    >
      ❤️ Like (24 แต้ม)
    </button>
    
    <button 
      @click="handleAction('dislike')" 
      :disabled="!canDislike"
      :class="{ 'opacity-50 cursor-not-allowed': !canDislike }"
    >
      👎 Dislike (12 แต้ม)
    </button>
  </div>
</template>
```

#### ตัวอย่าง 3: แสดง Points ใน Navigation
```vue
<!-- layouts/main.vue -->
<template>
  <div class="points-badge">
    <img src="/badge.png" class="w-6 h-6" />
    <span class="font-semibold">
      {{ authStore.points.toLocaleString() }}
    </span>
  </div>
</template>
```

### 🎨 Tips & Best Practices

#### 1. ใช้ Computed Properties
```javascript
// ดี ✅
const points = computed(() => authStore.points)

// ไม่ดี ❌
const points = ref(authStore.points) // ไม่ reactive
```

#### 2. ตรวจสอบแต้มก่อนทำ Action
```javascript
// ดี ✅
if (authStore.points < 24) {
  showError('แต้มไม่เพียงพอ')
  return
}
authStore.deductPoints(24)

// ไม่ดี ❌
authStore.deductPoints(24) // อาจจะตัดไม่สำเร็จ
```

#### 3. Rollback เมื่อ Error
```javascript
// ดี ✅
try {
  authStore.deductPoints(24)
  await apiCall()
} catch (error) {
  authStore.rollback(24)
}

// ไม่ดี ❌
authStore.deductPoints(24)
await apiCall() // ถ้า error จะไม่ได้คืนแต้ม
```

#### 4. ใช้ Loading State
```javascript
// ดี ✅
const isProcessing = ref(false)

const handleLike = async () => {
  if (isProcessing.value) return // ป้องกันการกดซ้ำ
  isProcessing.value = true
  
  // ... ทำ action ...
  
  isProcessing.value = false
}
```

### 📦 Available Functions

#### authStore.deductPoints(amount)
ตัดแต้ม
```javascript
const success = authStore.deductPoints(24)
if (!success) {
  // แต้มไม่พอ
}
```

#### authStore.addPoints(amount)
เพิ่มแต้ม
```javascript
authStore.addPoints(12)
```

#### authStore.rollback(amount)
คืนแต้ม (alias ของ addPoints)
```javascript
authStore.rollback(24)
```

#### authStore.points
แต้มปัจจุบัน (Computed)
```javascript
console.log(authStore.points) // เช่น 1234
```

#### authStore.canLike
ตรวจสอบว่ามีแต้มพอสำหรับ Like (24 แต้ม)
```javascript
if (authStore.canLike) {
  // สามารถ Like ได้
}
```

#### authStore.canDislike
ตรวจสอบว่ามีแต้มพอสำหรับ Dislike (12 แต้ม)
```javascript
if (authStore.canDislike) {
  // สามารถ Dislike ได้
}
```

### 🐛 Debugging

#### 1. ดู Console Logs
```javascript
// authStore จะ log ข้อมูลอัตโนมัติ
console.log('💰 deductPoints:', { amount: 24, newPoints: 1210 })
console.log('💰 addPoints:', { amount: 12, newPoints: 1222 })
```

#### 2. ตรวจสอบค่าแต้มใน DevTools
```javascript
// ใน Console
window.$nuxt.$pinia.state.value.auth.user.points
```

#### 3. Watch การเปลี่ยนแปลง
```vue
<script setup>
import { watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

watch(() => authStore.points, (newVal, oldVal) => {
  console.log(`Points changed from ${oldVal} to ${newVal}`)
})
</script>
```

---

**💬 ต้องการความช่วยเหลือ?**  
กรุณาติดต่อทีมพัฒนาหรือเปิด Issue ใน Repository
