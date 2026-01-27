<template>
  <div class="flex h-full min-h-full" style="height: 100vh; width: 100vw;">
    <!-- Left Side - Welcome Section -->
    <div class="w-full md:w-1/2 gradient-bg relative overflow-hidden flex items-center justify-center p-8">
      <!-- Animated Stars Background -->
      <div class="absolute inset-0">
        <div class="stars"></div>
        <div class="stars2"></div>
        <div class="stars3"></div>
      </div>
      
      <!-- Grid Pattern -->
      <div class="absolute inset-0 opacity-20" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
      
      <!-- Floating Elements -->
      <div class="absolute top-20 right-20 float">
        <svg width="80" height="80" viewBox="0 0 100 100" class="spin-slow">
          <circle cx="50" cy="50" r="40" fill="#fbbf24" opacity="0.8"/>
          <ellipse cx="50" cy="50" rx="60" ry="5" fill="#f59e0b" opacity="0.6"/>
          <circle cx="35" cy="35" r="5" fill="#fef3c7" opacity="0.9"/>
          <circle cx="65" cy="65" r="3" fill="#fef3c7" opacity="0.7"/>
        </svg>
      </div>
      
      <div class="absolute top-40 left-20 rocket">
        <svg width="60" height="60" viewBox="0 0 100 100" style="transform: rotate(-45deg);">
          <path d="M50 10 L60 40 L70 45 L60 50 L50 80 L40 50 L30 45 L40 40 Z" fill="#ef4444"/>
          <circle cx="50" cy="30" r="8" fill="#fef3c7"/>
          <path d="M35 50 L30 70 L40 55 Z" fill="#dc2626"/>
          <path d="M65 50 L70 70 L60 55 Z" fill="#dc2626"/>
          <!-- Rocket flame -->
          <path d="M45 75 L50 85 L55 75 Z" fill="#fbbf24" class="flame"/>
        </svg>
      </div>
      
      <div class="absolute bottom-32 right-32 float" style="animation-delay: 1s;">
        <svg width="50" height="50" viewBox="0 0 100 100">
          <rect x="30" y="20" width="40" height="50" rx="5" fill="#8b5cf6"/>
          <circle cx="45" cy="35" r="8" fill="#c4b5fd"/>
          <circle cx="55" cy="35" r="8" fill="#c4b5fd"/>
          <rect x="35" y="55" width="10" height="15" fill="#6d28d9"/>
          <rect x="55" y="55" width="10" height="15" fill="#6d28d9"/>
          <circle cx="40" cy="35" r="3" fill="#1f2937"/>
          <circle cx="60" cy="35" r="3" fill="#1f2937"/>
          <!-- Alien antenna -->
          <line x1="45" y1="20" x2="45" y2="10" stroke="#6d28d9" stroke-width="2"/>
          <circle cx="45" cy="8" r="3" fill="#a78bfa"/>
        </svg>
      </div>
      
      <!-- Additional floating elements -->
      <div class="absolute top-60 right-40 float" style="animation-delay: 2s;">
        <svg width="30" height="30" viewBox="0 0 100 100">
          <polygon points="50,10 90,90 10,90" fill="#f472b6" opacity="0.8"/>
          <circle cx="50" cy="60" r="8" fill="#fce7f3"/>
        </svg>
      </div>
      
      <div class="absolute bottom-40 left-32 float" style="animation-delay: 1.5s;">
        <svg width="40" height="40" viewBox="0 0 100 100">
          <polygon points="50,15 61,35 82,35 66,50 73,71 50,56 27,71 34,50 18,35 39,35" fill="#fbbf24" opacity="0.9"/>
        </svg>
      </div>
      
      <!-- Main Content -->
      <div class="relative z-10 text-center text-white fade-in">
        <div class="mb-6">
          <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm mb-4 glow">
            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
            </svg>
          </div>
        </div>
        
        <h1 class="text-5xl md:text-7xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-white to-purple-200">
          Welcome! 🚀
        </h1>
        <p class="text-xl md:text-2xl mb-8 opacity-90 max-w-lg mx-auto leading-relaxed">
          Join our cosmic community and explore the universe of connections
        </p>
        
        <!-- Feature badges -->
        <div class="flex flex-wrap justify-center gap-3 mb-8">
          <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            Gamification
          </span>
          <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
            </svg>
            Community
          </span>
          <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Quests
          </span>
        </div>
        
        <!-- Toggle Buttons -->
        <div class="flex gap-4 justify-center">
          <button @click="showLoginForm" class="btn-secondary px-8 py-3 rounded-full font-semibold text-lg border-2 border-white hover:scale-105 transform transition-all duration-300 shadow-lg">
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
              Login
            </span>
          </button>
          <button @click="showRegisterForm" class="btn-secondary px-8 py-3 rounded-full font-semibold text-lg border-2 border-white hover:scale-105 transform transition-all duration-300 shadow-lg">
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
              </svg>
              Register
            </span>
          </button>
        </div>
        
        <!-- Bottom Planet Scene -->
        <div class="absolute -bottom-20 left-1/2 transform -translate-x-1/2 w-full">
          <svg width="300" height="150" viewBox="0 0 300 150">
            <!-- Planet shadow -->
            <ellipse cx="150" cy="120" rx="120" ry="30" fill="#4f46e5" opacity="0.6"/>
            <!-- Main planet -->
            <circle cx="150" cy="100" r="50" fill="url(#planetGradient)"/>
            <!-- Planet surface details -->
            <circle cx="140" cy="90" r="15" fill="#60a5fa" opacity="0.5"/>
            <circle cx="165" cy="105" r="20" fill="#60a5fa" opacity="0.5"/>
            <ellipse cx="150" cy="100" rx="50" ry="8" fill="#4f46e5" opacity="0.3"/>
            
            <!-- Avatars on planet -->
            <g transform="translate(100, 70)">
              <circle cx="0" cy="0" r="12" fill="#fbbf24"/>
              <circle cx="-3" cy="-3" r="3" fill="#1f2937"/>
              <circle cx="3" cy="-3" r="3" fill="#1f2937"/>
              <circle cx="0" cy="3" r="2" fill="#f59e0b"/>
            </g>
            
            <g transform="translate(150, 65)">
              <rect x="-10" y="-5" width="20" height="15" rx="3" fill="#ec4899"/>
              <circle cx="0" cy="-12" r="8" fill="#fde68a"/>
              <circle cx="-3" cy="-12" r="2" fill="#1f2937"/>
              <circle cx="3" cy="-12" r="2" fill="#1f2937"/>
            </g>
            
            <g transform="translate(200, 72)">
              <circle cx="0" cy="0" r="12" fill="#a78bfa"/>
              <circle cx="-4" cy="-2" r="2" fill="#fff"/>
              <circle cx="4" cy="-2" r="2" fill="#fff"/>
              <path d="M-3 3 Q0 6 3 3" stroke="#fff" stroke-width="1.5" fill="none"/>
            </g>
            
            <!-- Gradient definition -->
            <defs>
              <linearGradient id="planetGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#1e40af;stop-opacity:1" />
              </linearGradient>
            </defs>
          </svg>
        </div>
      </div>
    </div>
    
    <!-- Right Side - Forms -->
    <div class="w-full md:w-1/2 bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-8 relative overflow-hidden">
      <!-- Background decoration -->
      <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-purple-200 to-pink-200 rounded-full filter blur-3xl opacity-30"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-to-tr from-blue-200 to-cyan-200 rounded-full filter blur-3xl opacity-30"></div>
      
      <!-- Login Form -->
      <div v-show="showLogin" class="form-card rounded-3xl shadow-2xl p-8 md:p-12 w-full max-w-md slide-in relative z-10 border border-white/50 overflow-hidden">
        <!-- Background decoration for form -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-cyan-200 to-blue-200 rounded-full filter blur-2xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-purple-200 to-pink-200 rounded-full filter blur-2xl opacity-20"></div>
        
        <div class="text-center mb-8 relative z-10">
          <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 mb-6 shadow-xl glow">
            <svg class="w-10 h-10 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
          </div>
          <h2 class="text-4xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-3">Welcome Back!</h2>
          <p class="text-gray-600 text-lg">Login to continue your cosmic journey</p>
        </div>
        
        <form @submit.prevent="handleLogin" class="space-y-6 relative z-10">
          <div class="relative group">
            <label for="loginEmail" class="block text-gray-700 font-bold mb-3 text-sm uppercase tracking-wide">Email Address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-100 to-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                  </svg>
                </div>
              </div>
              <input type="email" id="loginEmail" v-model="loginForm.email" required class="w-full pl-16 pr-4 py-4 rounded-xl border-2 border-gray-200 bg-white/80 backdrop-blur-sm transition-all focus:border-cyan-500 focus:shadow-xl focus:bg-white focus:scale-[1.02] text-gray-700 placeholder-gray-400" placeholder="Enter your email">
            </div>
          </div>
          
          <div class="relative group">
            <label for="loginPassword" class="block text-gray-700 font-bold mb-3 text-sm uppercase tracking-wide">Password</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-100 to-blue-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                  <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                </div>
              </div>
              <input type="password" id="loginPassword" v-model="loginForm.password" required class="w-full pl-16 pr-4 py-4 rounded-xl border-2 border-gray-200 bg-white/80 backdrop-blur-sm transition-all focus:border-cyan-500 focus:shadow-xl focus:bg-white focus:scale-[1.02] text-gray-700 placeholder-gray-400" placeholder="Enter your password">
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <label class="flex items-center group cursor-pointer">
              <div class="relative">
                <input type="checkbox" v-model="loginForm.remember" class="w-5 h-5 text-cyan-600 rounded-lg border-gray-300 focus:ring-cyan-500 sr-only">
                <div class="w-5 h-5 rounded-lg border-2 border-gray-300 flex items-center justify-center transition-all group-hover:border-cyan-400" :class="loginForm.remember ? 'bg-cyan-500 border-cyan-500' : ''">
                  <svg v-if="loginForm.remember" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
              <span class="ml-3 text-gray-600 text-sm font-medium">Remember me</span>
            </label>
            <a href="#" class="text-cyan-600 text-sm font-bold hover:text-cyan-700 transition-colors hover:underline">Forgot password?</a>
          </div>
          
          <button type="submit" class="btn-primary w-full py-4 rounded-xl text-white font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            <span class="relative flex items-center justify-center gap-3">
              <span class="text-lg">Login to Your Account</span>
              <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </span>
          </button>
          
          <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-4 bg-white text-gray-500 font-medium">Or continue with</span>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <button type="button" class="group flex justify-center items-center px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
              <div class="w-5 h-5 mr-2 transition-transform group-hover:scale-110">
                <svg viewBox="0 0 24 24">
                  <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
              </div>
              <span class="font-medium">Google</span>
            </button>
            <button type="button" class="group flex justify-center items-center px-4 py-3 border-2 border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
              <div class="w-5 h-5 mr-2 transition-transform group-hover:scale-110">
                <svg fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
              </div>
              <span class="font-medium">GitHub</span>
            </button>
          </div>
        </form>
      </div>
      
      <!-- Register Form -->
      <div v-show="showRegister" class="form-card rounded-3xl shadow-2xl p-8 md:p-12 w-full max-w-md slide-in relative z-10 border border-white/50 overflow-hidden">
        <!-- Background decoration for form -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-200 to-pink-200 rounded-full filter blur-2xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-blue-200 to-cyan-200 rounded-full filter blur-2xl opacity-20"></div>
        
        <div class="text-center mb-8 relative z-10">
          <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-purple-400 via-pink-500 to-purple-600 mb-6 shadow-xl glow">
            <svg class="w-10 h-10 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
          </div>
          <h2 class="text-4xl font-bold bg-gradient-to-r from-purple-800 to-pink-600 bg-clip-text text-transparent mb-3">Create Your Account!</h2>
          <p class="text-gray-600 text-lg">Join our cosmic adventure</p>
        </div>
        
        <form @submit.prevent="handleRegister" class="space-y-6 relative z-10">
          <div class="relative">
            <label for="registerName" class="block text-gray-700 font-semibold mb-2">Full Name</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <input type="text" id="registerName" v-model="registerForm.name" required class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 transition-all focus:border-purple-500 focus:shadow-lg" placeholder="John Doe">
            </div>
          </div>
          
          <div class="relative">
            <label for="registerEmail" class="block text-gray-700 font-semibold mb-2">Email</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                </svg>
              </div>
              <input type="email" id="registerEmail" v-model="registerForm.email" required class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 transition-all focus:border-purple-500 focus:shadow-lg" placeholder="your@email.com">
            </div>
          </div>
          
          <div class="relative">
            <label for="registerPassword" class="block text-gray-700 font-semibold mb-2">Password</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <input type="password" id="registerPassword" v-model="registerForm.password" required class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 transition-all focus:border-purple-500 focus:shadow-lg" placeholder="••••••••">
            </div>
          </div>
          
          <div class="flex items-start">
            <input type="checkbox" v-model="registerForm.agree" required class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500 mt-1">
            <label for="registerAgree" class="ml-2 text-sm text-gray-600">
              I agree to the <a href="#" class="text-purple-600 hover:text-purple-700 font-medium">Terms of Service</a> and <a href="#" class="text-purple-600 hover:text-purple-700 font-medium">Privacy Policy</a>
            </label>
          </div>
          
          <button type="submit" class="btn-primary w-full py-4 rounded-lg text-white font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
            <span class="flex items-center justify-center gap-2">
              Register Now!
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </span>
          </button>
          
          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">Or sign up with</span>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <button type="button" class="flex justify-center items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
              <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              Google
            </button>
            <button type="button" class="flex justify-center items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
              GitHub
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Form visibility states
const showLogin = ref(false)
const showRegister = ref(true)

// Form data
const loginForm = ref({
  email: '',
  password: '',
  remember: false
})

const registerForm = ref({
  name: '',
  email: '',
  password: '',
  agree: false
})

// Methods
const showLoginForm = () => {
  showRegister.value = false
  showLogin.value = true
}

const showRegisterForm = () => {
  showLogin.value = false
  showRegister.value = true
}

const handleLogin = () => {
  // Login functionality would connect to a backend service
  console.log('Login attempted with:', loginForm.value)
  alert('Login functionality would connect to a backend service')
}

const handleRegister = () => {
  // Registration functionality would connect to a backend service
  console.log('Registration attempted with:', registerForm.value)
  alert('Registration functionality would connect to a backend service')
}

onMounted(() => {
  // Show register form by default
  showRegister.value = true
})
</script>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

@keyframes rocket {
  0%, 100% { transform: translate(0, 0) rotate(-45deg); }
  50% { transform: translate(10px, -10px) rotate(-45deg); }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(50px); }
  to { opacity: 1; transform: translateX(0); }
}

.float { animation: float 3s ease-in-out infinite; }
.rocket { animation: rocket 2s ease-in-out infinite; }
.spin-slow { animation: spin 20s linear infinite; }
.fade-in { animation: fadeIn 0.6s ease-out forwards; }
.slide-in { animation: slideIn 0.6s ease-out forwards; }
.flame { animation: flame 0.5s ease-in-out infinite alternate; }

.gradient-bg {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
}

.form-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

input:focus {
  outline: none;
  border-color: #06b6d4;
  box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn-primary:hover::before {
  left: 100%;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(6, 182, 212, 0.3);
}

.btn-secondary {
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-secondary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn-secondary:hover::before {
  left: 100%;
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
}

.glow {
  box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.5); }
  50% { box-shadow: 0 0 30px rgba(255, 255, 255, 0.8); }
  100% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.5); }
}

@keyframes flame {
  0%, 100% { opacity: 0.8; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.1); }
}

@keyframes twinkle {
  0%, 100% { opacity: 0; }
  50% { opacity: 1; }
}

/* Animated stars background */
.stars, .stars2, .stars3 {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.stars {
  background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Ccircle cx='50' cy='50' r='1' fill='white' opacity='0.8'/%3E%3C/svg%3E") repeat;
  background-size: 100px 100px;
  animation: twinkle 5s ease-in-out infinite;
}

.stars2 {
  background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='150'%3E%3Ccircle cx='75' cy='75' r='1.5' fill='white' opacity='0.6'/%3E%3C/svg%3E") repeat;
  background-size: 150px 150px;
  animation: twinkle 8s ease-in-out infinite;
  animation-delay: 1s;
}

.stars3 {
  background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Ccircle cx='100' cy='100' r='0.5' fill='white' opacity='0.4'/%3E%3C/svg%3E") repeat;
  background-size: 200px 200px;
  animation: twinkle 12s ease-in-out infinite;
  animation-delay: 2s;
}
</style>

