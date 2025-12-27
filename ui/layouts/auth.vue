<template>
  <div class="h-screen w-full bg-black relative overflow-y-auto scrollbar-hide">
    <!-- Space Background with Stars -->
    <div class="absolute inset-0 z-0">
      <!-- Deep space gradient -->
      <div class="absolute inset-0 bg-gradient-to-b from-[#0a0a1a] via-[#0d0d2b] to-[#1a0a2e]"></div>
      
      <!-- Static star layers -->
      <div class="stars-layer-1"></div>
      <div class="stars-layer-2"></div>
      <div class="stars-layer-3"></div>
      
      <!-- Nebula effects -->
      <div class="absolute top-1/4 -left-20 w-96 h-96 bg-purple-600/30 rounded-full filter blur-[100px] animate-pulse-slow"></div>
      <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-blue-600/20 rounded-full filter blur-[80px] animate-pulse-slow animation-delay-2000"></div>
      <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-cyan-500/15 rounded-full filter blur-[60px] animate-pulse-slow animation-delay-4000"></div>
      
      <!-- Moving stars (warp effect) -->
      <div class="warp-stars">
        <div v-for="i in 100" :key="i" class="warp-star" :style="getWarpStarStyle(i)"></div>
      </div>
      
      <!-- Shooting stars -->
      <div class="shooting-star shooting-star-1"></div>
      <div class="shooting-star shooting-star-2"></div>
      <div class="shooting-star shooting-star-3"></div>
      
      <!-- Central galaxy glow -->
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px]">
        <div class="absolute inset-0 bg-gradient-radial from-purple-500/10 via-transparent to-transparent rounded-full animate-spin-slow"></div>
        <div class="absolute inset-10 bg-gradient-radial from-cyan-500/10 via-transparent to-transparent rounded-full animate-spin-reverse"></div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 lg:p-8">
      <div class="w-full max-w-7xl mx-auto">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Generate random warp star styles for the traveling effect
const getWarpStarStyle = (index: number) => {
  const angle = (index / 100) * 360
  const distance = Math.random() * 50 + 50
  const size = Math.random() * 2 + 1
  const duration = Math.random() * 3 + 2
  const delay = Math.random() * 5
  
  return {
    '--angle': `${angle}deg`,
    '--distance': `${distance}%`,
    width: `${size}px`,
    height: `${size}px`,
    animationDuration: `${duration}s`,
    animationDelay: `${delay}s`,
  }
}
</script>

<style scoped>
/* Static star layers with parallax effect */
.stars-layer-1,
.stars-layer-2,
.stars-layer-3 {
  position: absolute;
  inset: 0;
  background-repeat: repeat;
}

.stars-layer-1 {
  background-image: radial-gradient(2px 2px at 20px 30px, white, transparent),
                    radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
                    radial-gradient(1px 1px at 90px 40px, white, transparent),
                    radial-gradient(2px 2px at 160px 120px, rgba(255,255,255,0.9), transparent),
                    radial-gradient(1px 1px at 230px 80px, white, transparent),
                    radial-gradient(2px 2px at 300px 160px, rgba(255,255,255,0.7), transparent);
  background-size: 350px 350px;
  animation: twinkle 4s ease-in-out infinite;
}

.stars-layer-2 {
  background-image: radial-gradient(1px 1px at 50px 80px, white, transparent),
                    radial-gradient(2px 2px at 100px 150px, rgba(200,220,255,0.8), transparent),
                    radial-gradient(1px 1px at 180px 30px, white, transparent),
                    radial-gradient(1px 1px at 250px 200px, rgba(255,255,255,0.6), transparent),
                    radial-gradient(2px 2px at 320px 100px, rgba(180,200,255,0.9), transparent);
  background-size: 400px 400px;
  animation: twinkle 6s ease-in-out infinite 1s;
}

.stars-layer-3 {
  background-image: radial-gradient(1px 1px at 70px 120px, rgba(255,255,255,0.5), transparent),
                    radial-gradient(1px 1px at 140px 60px, white, transparent),
                    radial-gradient(1px 1px at 220px 180px, rgba(255,255,255,0.7), transparent),
                    radial-gradient(1px 1px at 290px 40px, rgba(200,220,255,0.6), transparent);
  background-size: 450px 450px;
  animation: twinkle 5s ease-in-out infinite 2s;
}

@keyframes twinkle {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Warp speed stars */
.warp-stars {
  position: absolute;
  inset: 0;
  perspective: 500px;
  overflow: hidden;
}

.warp-star {
  position: absolute;
  left: 50%;
  top: 50%;
  background: linear-gradient(to right, transparent, white, transparent);
  border-radius: 50%;
  animation: warp-travel linear infinite;
  transform-origin: center center;
}

@keyframes warp-travel {
  0% {
    transform: rotate(var(--angle)) translateX(0) scaleX(1);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: rotate(var(--angle)) translateX(var(--distance)) scaleX(20);
    opacity: 0;
  }
}

/* Shooting stars */
.shooting-star {
  position: absolute;
  width: 150px;
  height: 2px;
  background: linear-gradient(to right, transparent, white, transparent);
  border-radius: 50%;
  animation: shoot 4s linear infinite;
  opacity: 0;
}

.shooting-star-1 {
  top: 20%;
  left: 10%;
  animation-delay: 0s;
  transform: rotate(-45deg);
}

.shooting-star-2 {
  top: 40%;
  left: 60%;
  animation-delay: 2s;
  transform: rotate(-30deg);
}

.shooting-star-3 {
  top: 60%;
  left: 30%;
  animation-delay: 4s;
  transform: rotate(-60deg);
}

@keyframes shoot {
  0% {
    transform: translateX(0) rotate(-45deg);
    opacity: 0;
  }
  5% {
    opacity: 1;
  }
  20% {
    transform: translateX(300px) rotate(-45deg);
    opacity: 0;
  }
  100% {
    opacity: 0;
  }
}

/* Slow pulsing nebulas */
.animate-pulse-slow {
  animation: pulse-slow 8s ease-in-out infinite;
}

@keyframes pulse-slow {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.1); }
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

/* Galaxy rotation */
.animate-spin-slow {
  animation: spin 60s linear infinite;
}

.animate-spin-reverse {
  animation: spin 45s linear infinite reverse;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Radial gradient helper */
.bg-gradient-radial {
  background: radial-gradient(circle, var(--tw-gradient-from), var(--tw-gradient-via), var(--tw-gradient-to));
}
</style>
