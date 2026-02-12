<template>
  <section class="relative bg-linear-to-br from-sky-100 via-blue-50 to-white text-gray-800 overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-sky-200/40 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-100/40 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob animation-delay-2000"></div>
      <div class="absolute top-40 left-20 w-80 h-80 bg-cyan-100/40 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob animation-delay-4000"></div>
    </div>
    
    <!-- Grid pattern overlay -->
    <div class="absolute inset-0 grid-pattern" style="background-size: 50px 50px;"></div>
    
    <!-- Main content -->
    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-4">
      <div class="container mx-auto text-center max-w-4xl">
        <!-- Animated badge -->

        
        <!-- Main heading with gradient text -->
        <div class="text-xl font-bold mb-6 leading-tight animate-fade-in-up">
          <span class="block mb-2 text-indigo-700">Welcome to</span>
          <span class="font-audiowide text-5xl md:text-7xl bg-clip-text text-transparent bg-linear-to-r from-purple-600 to-blue-600">
            www.plearnd.com
          </span>
        </div>
        
        <!-- Subtitle with enhanced typography -->
        <p class="font-prompt text-xl md:text-2xl mb-12 text-gray-600  leading-relaxed animate-fade-in-up animation-delay-200 text-center">
          เรียนบ้าง เล่นบ้าง สร้างรายได้ด้วย PlearnD
        </p>
                
        <!-- Feature highlights -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 animate-fade-in-up animation-delay-600">
          <div class="flex flex-col items-center">
            <div class="w-16 h-16 bg-white/70 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 border border-gray-200/50">
              <img class="badge-item-preview-image" src="storage/images/badge/warrior-b.png" alt="badge-warrior-b">
            </div>
            <span class="text-sm font-medium text-gray-600">Play</span>
          </div>
          
          <div class="flex flex-col items-center">
            <div class="w-16 h-16 bg-white/70 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 border border-gray-200/50">
            <img class="badge-item-preview-image" src="storage/images/badge/mightiers-b.png" alt="badge-mightiers-b">
            </div>
            <span class="text-sm font-medium text-gray-600">Learnd</span>
          </div>
          
          <div class="flex flex-col items-center">
            <div class="w-16 h-16 bg-white/70 backdrop-blur-sm rounded-xl flex items-center justify-center mb-4 border border-gray-200/50">
            <img class="badge-item-preview-image" src="storage/images/badge/goldc.png" alt="badge-goldc-b">
            </div>
            <span class="text-sm font-medium text-gray-600">Earn</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
      <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
      </svg>
    </div>
  </section>
</template>

<script setup>
// No script logic needed for static hero section
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Audiowide&family=Prompt&display=swap');

.font-audiowide {
  font-family: 'Audiowide', cursive;
}

.font-prompt {
  font-family: 'Prompt', sans-serif;
}

@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fade-in-down {
  from {
    opacity: 0;
    transform: translateY(-30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

.animation-delay-200 {
  animation-delay: 0.2s;
}

.animation-delay-400 {
  animation-delay: 0.4s;
}

.animation-delay-600 {
  animation-delay: 0.6s;
}

.animate-fade-in-up {
  animation: fade-in-up 0.8s ease-out forwards;
  opacity: 0;
}

.animate-fade-in-down {
  animation: fade-in-down 0.8s ease-out forwards;
  opacity: 0;
}

.grid-pattern {
  background-image: linear-gradient(to right, rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
}
</style>
