<template>
  <div class="cross-math-game min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 overflow-hidden">
    <!-- 3D Classroom Background with Props -->
    <div class="classroom-scene fixed inset-0 overflow-hidden pointer-events-none">
      <!-- Volumetric Light Effect -->
      <div class="absolute inset-0 bg-gradient-to-b from-amber-500/10 via-transparent to-transparent"></div>
      <div class="absolute top-0 left-1/4 w-96 h-full bg-gradient-to-r from-yellow-400/5 to-transparent rotate-12"></div>
      
      <!-- Floor with Perspective -->
      <div class="absolute bottom-0 left-0 right-0 h-1/3 bg-gradient-to-t from-amber-900/40 to-transparent"></div>
      
      <!-- Floating Clock -->
      <div class="absolute top-20 right-8 text-6xl animate-pulse-slow filter drop-shadow-lg">
        ⏰
      </div>
      
      <!-- Books -->
      <div class="absolute bottom-20 left-4 text-7xl filter drop-shadow-xl">
        📚
      </div>
      
      <!-- Plant -->
      <div class="absolute bottom-16 right-4 text-8xl filter drop-shadow-xl">
        🪴
      </div>
    </div>

    <!-- Login Screen -->
    <div v-if="gameState === 'login'" class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4">
      <div class="login-card bg-gradient-to-br from-amber-600 to-amber-800 p-8 rounded-3xl shadow-2xl border-4 border-amber-400 transform perspective-1000 rotate-x-2">
        <h1 class="text-4xl md:text-5xl font-bold text-white text-center mb-2 drop-shadow-lg">
          🦉 Cross Math
        </h1>
        <p class="text-amber-100 text-center mb-6 text-lg">ปริศนาตัวเลขสำหรับเด็กฉลาด</p>
        
        <div class="space-y-4">
          <!-- Player card from auth -->
          <div class="flex items-center gap-3 bg-white/15 backdrop-blur-sm rounded-2xl px-4 py-3 border border-white/30">
            <div class="relative shrink-0">
              <img
                v-if="authStore.user?.profile_photo_url || authStore.user?.avatar"
                :src="authStore.user?.profile_photo_url || authStore.user?.avatar"
                :alt="playerName"
                class="w-14 h-14 rounded-full object-cover border-3 border-white shadow-lg"
              >
              <div
                v-else
                class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-300 to-amber-500 border-3 border-white shadow-lg flex items-center justify-center text-2xl font-black text-white"
              >
                {{ playerName.charAt(0).toUpperCase() }}
              </div>
              <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-400 rounded-full border-2 border-amber-700"></div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-white/70 text-xs font-medium mb-0.5">ผู้เล่น</p>
              <p class="text-white font-bold text-lg leading-tight truncate">{{ playerName }}</p>
            </div>
            <div class="shrink-0 text-emerald-300 text-xl">✓</div>
          </div>

          <div>
            <label class="block text-white font-semibold mb-2 text-base">เป้าหมายเวลา / Target Time</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="option in timeOptions"
                :key="option.value"
                @click="selectedTime = option.value"
                :class="[
                  'py-3 px-4 rounded-xl font-bold text-lg transition-all transform',
                  selectedTime === option.value
                    ? 'bg-emerald-500 text-white scale-105 shadow-lg ring-4 ring-emerald-300'
                    : 'bg-white/80 text-slate-700 hover:bg-white'
                ]"
              >
                {{ option.label }}
              </button>
            </div>
          </div>

          <button
            @click="startGame"
            :disabled="!playerName.trim()"
            class="w-full py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-2xl font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            🎮 เริ่มเกม / Start
          </button>

          <p v-if="!authStore.user" class="text-amber-200/70 text-center text-xs font-medium">
            💡 เข้าสู่ระบบเพื่อบันทึกคะแนนสะสมของคุณ
          </p>
        </div>
      </div>
    </div>

    <!-- Game Screen -->
    <div v-else-if="gameState === 'playing'" class="relative z-10 min-h-screen flex flex-col items-center px-3 pt-4 pb-6 gap-3">

      <!-- Header -->
      <div class="w-full max-w-2xl grid grid-cols-3 gap-2 sm:flex sm:items-center">
        <div class="min-w-0 sm:flex-1 bg-slate-800/90 px-3 py-2 rounded-2xl border-2 border-amber-500/60 shadow-lg text-center">
          <p class="text-amber-400 font-bold text-sm truncate">{{ playerName }}</p>
        </div>
        <div class="min-w-0 sm:flex-1 bg-slate-800/90 px-3 py-2 rounded-2xl border-2 border-emerald-500/60 shadow-lg text-center">
          <p class="text-emerald-400 font-bold text-sm truncate">ระดับ {{ Math.ceil(currentLevel/10) }} / ด่าน {{ currentLevel }}</p>
        </div>
        <div class="min-w-0 sm:flex-1 bg-slate-800/90 px-3 py-2 rounded-2xl border-2 border-yellow-500/60 shadow-lg text-center">
          <p class="text-yellow-400 font-bold text-sm truncate">{{ score }} <span class="text-yellow-500/80 text-xs">แต้ม</span></p>
        </div>
        <div class="col-span-3 grid grid-cols-2 gap-2 sm:ml-auto sm:flex sm:shrink-0 sm:gap-1">
          <button @click="fetchLeaderboard" class="min-h-[44px] sm:min-h-0 min-w-0 py-2 px-3 bg-indigo-700/80 text-white rounded-xl border border-indigo-600 shadow text-sm font-semibold whitespace-nowrap">🏆 อันดับ</button>
          <button @click="openStageList" class="min-h-[44px] sm:min-h-0 min-w-0 py-2 px-3 bg-slate-700/90 text-white rounded-xl border border-slate-600 shadow text-sm font-semibold whitespace-nowrap">รายการด่าน</button>
          <button @click="showQuitConfirm = true" class="min-h-[44px] sm:min-h-0 min-w-0 py-2 px-3 bg-red-700/80 text-white rounded-xl border border-red-600 shadow text-sm font-semibold whitespace-nowrap">✕ ออก</button>
        </div>
      </div>

      <!-- Leaderboard Modal -->
      <div v-if="showLeaderboard" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showLeaderboard = false"></div>
        <div class="relative bg-slate-900 border-2 border-indigo-500 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col max-h-[90vh]">
          <div class="p-5 bg-gradient-to-r from-indigo-600 to-purple-600 flex justify-between items-center shrink-0">
            <h3 class="text-xl font-black text-white flex items-center gap-2">
              <span class="text-2xl">🏆</span> กระดานผู้นำ / Leaderboard
            </h3>
            <button @click="showLeaderboard = false" class="w-8 h-8 flex items-center justify-center rounded-full bg-black/20 text-white hover:bg-black/40 transition-all">✕</button>
          </div>

          <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
            <div v-if="loadingLeaderboard" class="flex flex-col items-center justify-center py-12 space-y-4">
              <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
              <p class="text-indigo-300 font-bold animate-pulse">กำลังโหลดข้อมูล...</p>
            </div>

            <div v-else-if="leaderboardData.length === 0" class="text-center py-12">
              <p class="text-slate-500 italic">ยังไม่มีข้อมูลคะแนน</p>
            </div>

            <template v-else>
              <div v-for="(entry, index) in leaderboardData" :key="entry.id" 
                class="flex items-center gap-3 bg-slate-800/50 p-3 rounded-2xl border border-slate-700/50 hover:bg-slate-800 transition-all"
                :class="{ 'ring-2 ring-amber-400 bg-indigo-900/30 border-indigo-400/50': entry.user_id === authStore.user?.id }"
              >
                <div class="w-8 font-black text-lg text-center" :class="[
                  index === 0 ? 'text-yellow-400 text-2xl' : index === 1 ? 'text-slate-300 text-xl' : index === 2 ? 'text-amber-600 text-lg' : 'text-slate-500'
                ]">
                  {{ index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : index + 1 }}
                </div>
                
                <img v-if="entry.user?.profile_photo_url || entry.user?.avatar" 
                  :src="entry.user?.profile_photo_url || entry.user?.avatar" 
                  class="w-10 h-10 rounded-full border-2 border-slate-700 shadow-sm object-cover"
                />
                <div v-else class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-slate-400 font-bold">
                  {{ (entry.player_name || 'G').charAt(0).toUpperCase() }}
                </div>

                <div class="flex-1 min-w-0">
                  <p class="text-white font-bold truncate">{{ entry.player_name }}</p>
                  <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tighter">LEVEL {{ entry.level }}</p>
                </div>

                <div class="text-right">
                  <p class="text-amber-400 font-black text-lg leading-none">{{ entry.score.toLocaleString() }}</p>
                  <p class="text-[9px] text-slate-500 font-bold">แต้ม / PTS</p>
                </div>
              </div>
            </template>
          </div>

          <!-- Current User Rank Sticky Footer -->
          <div v-if="userRankInfo && authStore.user" class="p-4 bg-slate-800 border-t border-slate-700 shrink-0">
            <div class="flex items-center gap-3 bg-indigo-900/40 p-3 rounded-2xl border border-indigo-500/30">
              <div class="w-8 text-center font-black text-indigo-300">#{{ userRankInfo.rank }}</div>
              <div class="flex-1">
                <p class="text-white font-bold text-sm">อันดับของคุณ</p>
                <p class="text-xs text-indigo-300">ความภูมิใจของวันนี้!</p>
              </div>
              <div class="text-right">
                <p class="text-amber-400 font-black text-lg leading-none">{{ userRankInfo.best_score.toLocaleString() }}</p>
                <p class="text-[9px] text-slate-400 font-bold uppercase">Best Score</p>
              </div>
            </div>
          </div>
          <div v-else-if="!authStore.user" class="p-4 bg-slate-800 border-t border-slate-700 text-center shrink-0">
             <p class="text-amber-400 text-xs font-bold">💡 เข้าสู่ระบบเพื่อบันทึกและดูอันดับของคุณ!</p>
          </div>
        </div>
      </div>

      <!-- Quit Confirm Modal -->
      <div v-if="showQuitConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70" @click="showQuitConfirm = false"></div>
        <div class="relative bg-slate-800 p-6 rounded-2xl shadow-2xl border-2 border-red-500 max-w-xs w-full text-center">
          <p class="text-4xl mb-3">🚪</p>
          <h3 class="text-white font-bold text-lg mb-1">ยกเลิกการเล่น?</h3>
          <p class="text-slate-400 text-sm mb-5">ความคืบหน้าในด่านนี้จะหายไป</p>
          <div class="flex gap-3">
            <button @click="showQuitConfirm = false" class="min-h-[44px] sm:min-h-0 flex-1 py-2 bg-slate-600 text-white rounded-xl font-semibold hover:bg-slate-500 transition-all">เล่นต่อ</button>
            <button @click="quitGame" class="min-h-[44px] sm:min-h-0 flex-1 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-500 transition-all">ออกเกม</button>
          </div>
        </div>
      </div>

      <!-- Stage list modal -->
      <div v-if="showStages" class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60" @click="closeStageList"></div>
        <div class="relative bg-slate-900 p-4 rounded-2xl shadow-2xl max-w-3xl w-full">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-white font-bold">รายการด่านทั้งหมด</h3>
            <button @click="closeStageList" class="text-slate-300">ปิด</button>
          </div>
          <div class="grid grid-cols-10 gap-2">
            <button v-for="s in stages" :key="s.id" @click="goToStage(s.stage)"
              :class="[
                'py-2 text-sm rounded-md',
                s.status === 'completed' ? 'bg-emerald-500 text-white' : s.status === 'unlocked' ? 'bg-amber-400 text-slate-800' : 'bg-slate-700 text-slate-400 cursor-not-allowed'
              ]">
              {{ s.stage }}
            </button>
          </div>
        </div>
      </div>

      <!-- Timer Bar -->
      <div class="w-full max-w-sm">
        <div class="h-5 bg-slate-800 rounded-full overflow-hidden shadow-inner border border-slate-700/80">
          <div
            class="h-full transition-all duration-1000 ease-linear rounded-full relative overflow-hidden"
            :class="[
              elapsedTime < selectedTime / 2 ? 'bg-gradient-to-r from-emerald-600 to-emerald-400' :
              elapsedTime < selectedTime ? 'bg-gradient-to-r from-yellow-600 to-yellow-400' :
              'bg-gradient-to-r from-red-600 to-red-400'
            ]"
            :style="{ width: `${Math.min(100, (elapsedTime / selectedTime) * 100)}%` }"
          >
            <div class="absolute inset-0 bg-gradient-to-b from-white/20 to-transparent"></div>
          </div>
        </div>
        <p class="text-center text-white/60 font-semibold mt-1 text-xs tracking-widest">⏱ {{ elapsedTime }}s</p>
      </div>

      <!-- Level Progress Bar -->
      <div class="w-full max-w-sm mb-1">
        <div class="flex justify-between items-end mb-1 px-1">
          <span class="text-[10px] font-bold text-indigo-300 uppercase tracking-wider">{{ levelProgress.label }}</span>
          <span class="text-[10px] font-black text-pink-400">{{ Math.round(levelProgress.percentage) }}%</span>
        </div>
        <div class="h-2.5 bg-slate-800/80 rounded-full overflow-hidden border border-slate-700/50 p-[1px]">
          <div
            class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full transition-all duration-700 ease-out shadow-[0_0_8px_rgba(192,38,211,0.5)]"
            :style="{ width: `${levelProgress.percentage}%` }"
          >
            <div class="w-full h-full bg-[length:20px_20px] bg-[linear-gradient(45deg,rgba(255,255,255,0.15)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.15)_50%,rgba(255,255,255,0.15)_75%,transparent_75%,transparent)] animate-stripes"></div>
          </div>
        </div>
      </div>

      <!-- Owl + Message -->
      <div class="flex flex-col items-center gap-1.5">
        <span class="text-5xl animate-bounce-slow filter drop-shadow-xl leading-none">🦉</span>
        <div class="bg-slate-800/95 px-4 py-1.5 rounded-full border border-amber-400/70 shadow-lg shadow-amber-900/30">
          <span class="text-white text-xs font-bold tracking-wide">{{ owlMessage }}</span>
        </div>
      </div>

      <!-- Game Board -->
      <div class="w-full max-w-[95vw] overflow-x-auto px-5 py-5">
          <div
            class="relative mx-auto"
            :style="{ width: boardSurfaceWidth }"
          >
          <!-- Outer amber frame shadow -->
          <div class="absolute -inset-5 bg-gradient-to-b from-amber-700 via-amber-600 to-amber-900 rounded-3xl shadow-2xl translate-y-1.5 blur-[1px]"></div>
          <!-- Inner amber border -->
          <div class="absolute -inset-4 bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 rounded-2xl shadow-lg"></div>

          <!-- Green chalkboard surface -->
          <div class="relative bg-gradient-to-br from-emerald-800 via-emerald-750 to-emerald-900 p-4 rounded-2xl border-4 border-emerald-700/80 shadow-inner">
            <!-- Subtle inner highlight -->
            <div class="absolute inset-0 rounded-2xl overflow-hidden pointer-events-none">
              <div class="absolute inset-0 bg-gradient-to-br from-white/8 via-transparent to-black/20"></div>
            </div>

            <!-- Math Grid -->
            <div
              class="relative grid gap-1.5 z-10 mx-auto"
              :style="{
                gridTemplateColumns: boardGridColumns,
                width: boardGridWidth
              }"
            >
              <template v-for="cell in gridCells" :key="cell.id">

              <!-- Blank number cell (user input) -->
              <div
                v-if="cell.type === 'number' && cell.blank"
                @click="selectCell(cell.id)"
                :ref="el => { if (el) cellRefs[cell.id] = el }"
                :tabindex="0"
                :class="[
                  'aspect-square rounded-xl flex items-center justify-center font-extrabold text-xl transition-all duration-150 cursor-pointer select-none outline-none',
                  activeCellId === cell.id
                    ? 'bg-white ring-4 ring-amber-400 shadow-xl shadow-amber-500/50 scale-110 text-slate-800 z-10'
                    : cell.status === 'correct'
                      ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg ring-2 ring-emerald-300'
                      : cell.status === 'incorrect'
                        ? 'bg-gradient-to-br from-red-400 to-red-600 text-white shadow-lg ring-2 ring-red-400 animate-shake'
                        : cell.value !== null
                          ? 'bg-amber-50 text-slate-800 border border-amber-300 shadow-sm'
                          : 'bg-white/85 text-slate-300 shadow-sm hover:bg-white hover:scale-105 active:scale-95 border border-white/50'
                ]"
              >
                {{ cell.value ?? '' }}
              </div>

              <!-- Pre-filled number cell (given, read-only) -->
              <div
                v-else-if="cell.type === 'number' && !cell.blank"
                class="aspect-square rounded-xl flex items-center justify-center font-extrabold text-xl select-none
                       bg-gradient-to-br from-amber-100 to-amber-200 text-slate-800 border border-amber-300 shadow-md"
              >
                {{ cell.value }}
              </div>

              <!-- Horizontal operator (between numbers in same row) -->
              <div
                v-else-if="cell.type === 'h_op'"
                class="aspect-square flex items-center justify-center select-none"
              >
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-700 to-slate-900 border-2 border-slate-500 shadow-inner flex items-center justify-center font-black text-amber-300 text-base leading-none">
                  {{ displayOp(cell.value) }}
                </div>
              </div>

              <!-- Vertical operator (between numbers in same column) -->
              <div
                v-else-if="cell.type === 'v_op'"
                class="aspect-square flex items-center justify-center select-none"
              >
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-700 to-slate-900 border-2 border-slate-500 shadow-inner flex items-center justify-center font-black text-amber-300 text-base leading-none">
                  {{ displayOp(cell.value) }}
                </div>
              </div>

              <!-- Equals sign before horizontal result -->
              <div
                v-else-if="cell.type === 'h_eq'"
                class="aspect-square flex items-center justify-center select-none"
              >
                <span class="text-emerald-400 font-extrabold text-lg leading-none">=</span>
              </div>

              <!-- Equals sign before vertical result -->
              <div
                v-else-if="cell.type === 'v_eq'"
                class="aspect-square flex items-center justify-center select-none"
              >
                <span class="text-emerald-400 font-extrabold text-lg leading-none">=</span>
              </div>

              <!-- Horizontal result (pre-computed answer for each row) -->
              <div
                v-else-if="cell.type === 'h_result'"
                :class="[
                  'aspect-square rounded-xl flex items-center justify-center font-extrabold text-xl select-none border-2 shadow-lg transition-all duration-300',
                  lineCorrect[`h-${cell.row}`]
                    ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white border-emerald-300 ring-2 ring-emerald-300/50 scale-105'
                    : 'bg-gradient-to-br from-teal-600 to-teal-800 text-teal-100 border-teal-500'
                ]"
              >
                {{ lineCorrect[`h-${cell.row}`] ? '✓' : cell.value }}
              </div>

              <!-- Vertical result (pre-computed answer for each column) -->
              <div
                v-else-if="cell.type === 'v_result'"
                :class="[
                  'aspect-square rounded-xl flex items-center justify-center font-extrabold text-xl select-none border-2 shadow-lg transition-all duration-300',
                  lineCorrect[`v-${cell.col}`]
                    ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white border-emerald-300 ring-2 ring-emerald-300/50 scale-105'
                    : 'bg-gradient-to-br from-teal-600 to-teal-800 text-teal-100 border-teal-500'
                ]"
              >
                {{ lineCorrect[`v-${cell.col}`] ? '✓' : cell.value }}
              </div>

              <!-- Corner / empty spacer -->
              <div v-else class="aspect-square"></div>

              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Numpad -->
      <div class="w-full max-w-xs mt-1">
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="digit in 9"
            :key="digit"
            @click="handleDigitInput(digit)"
            class="numpad-btn aspect-square rounded-2xl text-2xl font-bold select-none transition-all
                   bg-gradient-to-b from-slate-100 to-slate-250 text-slate-800
                   shadow-[0_5px_0_rgba(0,0,0,0.35)] border border-slate-300/80
                   hover:from-white hover:to-slate-100
                   active:shadow-[0_1px_0_rgba(0,0,0,0.2)] active:translate-y-1"
            :aria-label="`ปุ่มตัวเลข ${digit}`"
          >
            {{ digit }}
          </button>
          <button
            @click="useHint"
            :disabled="hintsRemaining <= 0"
            :class="[
              'numpad-btn col-span-1 py-3.5 rounded-2xl text-lg font-bold select-none transition-all border',
              hintsRemaining > 0
                ? 'bg-gradient-to-b from-amber-400 to-amber-600 text-white shadow-[0_5px_0_rgba(120,80,0,0.4)] border-amber-300/60 hover:from-amber-300 hover:to-amber-500 active:shadow-[0_1px_0_rgba(120,80,0,0.3)] active:translate-y-1'
                : 'bg-gradient-to-b from-slate-500 to-slate-700 text-slate-400 border-slate-600 cursor-not-allowed opacity-60'
            ]"
            aria-label="ปุ่มใบ้"
          >
            💡 {{ hintsRemaining }}
          </button>
          <button
            @click="handleDelete"
            class="numpad-btn col-span-2 py-3.5 rounded-2xl text-xl font-bold select-none transition-all
                   bg-gradient-to-b from-red-500 to-red-700 text-white
                   shadow-[0_5px_0_rgba(127,0,0,0.4)] border border-red-400/60
                   hover:from-red-400 hover:to-red-600
                   active:shadow-[0_1px_0_rgba(127,0,0,0.3)] active:translate-y-1"
            aria-label="ปุ่มลบ"
          >
            ⌫  ลบ
          </button>
        </div>
      </div>

      <!-- Keyboard hint -->
      <p class="text-slate-500/80 text-xs text-center tracking-wide">
        ⌨️ ลูกศร = เลื่อน &nbsp;|&nbsp; 1–9 = กรอก &nbsp;|&nbsp; Backspace = ลบ
      </p>
    </div>

    <!-- Result Modals -->
    <div v-if="gameState === 'levelComplete' || gameState === 'gameOver' || gameState === 'gameWin'" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
      
      <div 
        class="relative bg-gradient-to-br from-slate-800 to-slate-900 p-6 md:p-8 rounded-3xl shadow-2xl border-4 border-amber-400 max-w-md w-full animate-bounce-in"
      >
        <!-- Result Icon -->
        <div class="text-center mb-4">
          <span v-if="gameState === 'levelComplete'" class="text-6xl md:text-7xl">🎉</span>
          <span v-else-if="gameState === 'gameWin'" class="text-6xl md:text-7xl">🏆</span>
          <span v-else class="text-6xl md:text-7xl">⏰</span>
        </div>
        
        <!-- Result Title -->
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-2" :class="[
          gameState === 'levelComplete' || gameState === 'gameWin' ? 'text-emerald-400' : 'text-red-400'
        ]">
          <span v-if="gameState === 'levelComplete'">ผ่านด่านแล้ว! / Level Complete!</span>
          <span v-else-if="gameState === 'gameWin'">ชนะแล้ว! / You Win!</span>
          <span v-else>หมดเวลา! / Time's Up!</span>
        </h2>
        
        <!-- Score Summary -->
        <div class="bg-slate-700/50 p-4 rounded-xl mb-4">
          <div class="flex justify-between text-white mb-2">
            <span>คะแนนด่าน:</span>
            <span class="font-bold text-yellow-400">{{ levelScore }}</span>
          </div>
          <div class="flex justify-between text-white mb-2">
            <span>เวลาที่ใช้:</span>
            <span class="font-bold text-sky-400">{{ elapsedTime }}s</span>
          </div>
          <div v-if="timeBonus > 0" class="flex justify-between text-white mb-2">
            <span>โบนัสเร็ว:</span>
            <span class="font-bold text-emerald-400">+{{ timeBonus }}</span>
          </div>
          <div class="flex justify-between text-white border-t border-slate-600 pt-2 mt-2">
            <span>คะแนนรวม:</span>
            <span class="font-bold text-amber-400 text-xl">{{ score }}</span>
          </div>
        </div>
        
        <!-- Buttons -->
        <div class="flex gap-3">
          <button 
            v-if="gameState === 'levelComplete'"
            @click="nextLevel"
            aria-keyshortcuts="Enter"
            class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-lg font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            ด่านถัดไป →
          </button>
          <button 
            v-if="gameState === 'gameWin'"
            @click="restartGame"
            class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-lg font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            เล่นใหม่ 🔄
          </button>
          <button 
            v-if="gameState === 'gameOver'"
            @click="restartGame"
            class="flex-1 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white text-lg font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            เล่นใหม่ 🔄
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

const authStore = useAuthStore();
const { api } = useApi();
const config = useRuntimeConfig();

const gameState = ref('login');
const playerName = ref(authStore.user?.name || 'ผู้เล่น');
const selectedTime = ref(60);
const showQuitConfirm = ref(false);
const currentLevel = ref(1);
const score = ref(0);
const elapsedTime = ref(0);
const totalTimeSpent = ref(0);
const activeCellId = ref(null);
const cellRefs = ref({});
const gridCells = ref([]);
const hintsRemaining = ref(0);
const lineCorrect = ref({});

// Session tracking
const gameSessionId = ref('');

// Leaderboard state
const showLeaderboard = ref(false);
const loadingLeaderboard = ref(false);
const leaderboardData = ref([]);
const userRankInfo = ref(null);

const levelProgress = computed(() => {
  const stage = currentLevel.value;
  let start = 1, end = 10, size = 2, nextSize = 3;
  
  if (stage >= 1 && stage <= 10) { start = 1; end = 10; size = 2; nextSize = 3; }
  else if (stage >= 11 && stage <= 35) { start = 11; end = 35; size = 3; nextSize = 4; }
  else if (stage >= 36 && stage <= 55) { start = 36; end = 55; size = 4; nextSize = 5; }
  else if (stage >= 56 && stage <= 75) { start = 56; end = 75; size = 5; nextSize = 6; }
  else if (stage >= 76 && stage <= 88) { start = 76; end = 88; size = 6; nextSize = 7; }
  else if (stage >= 89 && stage <= 95) { start = 89; end = 95; size = 7; nextSize = 8; }
  else if (stage >= 96 && stage <= 98) { start = 96; end = 98; size = 8; nextSize = 9; }
  else if (stage === 99) { start = 99; end = 99; size = 9; nextSize = 10; }
  else if (stage === 100) { start = 100; end = 100; size = 10; nextSize = 'WIN'; }

  const currentInRange = stage - start + 1;
  const totalInRange = end - start + 1;
  const percentage = (currentInRange / totalInRange) * 100;

  return {
    size,
    nextSize,
    currentInRange,
    totalInRange,
    percentage,
    label: `📐 ${size}×${size} — ด่าน ${currentInRange}/${totalInRange} → ${nextSize}${typeof nextSize === 'number' ? '×' + nextSize : ''}`
  };
});

async function fetchLeaderboard() {
  showLeaderboard.value = true;
  loadingLeaderboard.value = true;
  try {
    const apiBase = config.public.apiBase;
    const url = `${apiBase}/api/game/scores?game_type=crossmath`;
    const headers = { 'Accept': 'application/json' };
    if (authStore.token) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
    }
    
    const response = await $fetch(url, { headers });
    leaderboardData.value = response.leaderboard;
    if (response.user_rank) {
      userRankInfo.value = {
        rank: response.user_rank,
        best_score: response.user_best_score?.score || 0
      };
    }
  } catch (error) {
    console.error('Failed to fetch leaderboard:', error);
  } finally {
    loadingLeaderboard.value = false;
  }
}

async function saveScore() {
  if (score.value <= 0) return;
  
  try {
    const apiBase = config.public.apiBase;
    const url = `${apiBase}/api/game/scores`;
    const headers = { 
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    if (authStore.token) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
    }

    await $fetch(url, {
      method: 'POST',
      headers,
      body: {
        game_type: 'crossmath',
        session_id: gameSessionId.value,
        score: score.value,
        level: currentLevel.value,
        time_spent: totalTimeSpent.value,
        player_name: playerName.value,
        metadata: {
          max_grid_size: gridSize.value
        }
      }
    });
  } catch (error) {
    console.error('Failed to save score:', error);
  }
}

// ===== Stage pacing — 2×2 reduced to 10, more practice for mid grids =====
function getGridSizeForStage(stage) {
  if (stage >= 1 && stage <= 10) return 2;
  if (stage >= 11 && stage <= 35) return 3;
  if (stage >= 36 && stage <= 55) return 4;
  if (stage >= 56 && stage <= 75) return 5;
  if (stage >= 76 && stage <= 88) return 6;
  if (stage >= 89 && stage <= 95) return 7;
  if (stage >= 96 && stage <= 98) return 8;
  if (stage === 99) return 9;
  if (stage === 100) return 10;
  return 2;
}

const gridSize = computed(() => getGridSizeForStage(currentLevel.value));

const cellCount = computed(() => gridSize.value * 2 + 1);
const boardCellSize = computed(() => {
  if (gridSize.value >= 8) return 40;
  if (gridSize.value >= 5) return 44;
  return 48;
});
const boardGridColumns = computed(() => `repeat(${cellCount.value}, minmax(0, 1fr))`);
const boardGridWidth = computed(() => `${cellCount.value * boardCellSize.value}px`);
const boardSurfaceWidth = computed(() => `${cellCount.value * boardCellSize.value + 40}px`);

function displayOp(op) {
  if (op === '*') return '×';
  if (op === '/') return '÷';
  return op;
}

const timeOptions = [
  { value: 30, label: '30 วินาที' },
  { value: 60, label: '1 นาที' },
  { value: 90, label: '1.30 นาที' },
  { value: 120, label: '2 นาที' }
];

const levelScore = ref(0);
const timeBonus = ref(0);

const owlMessages = {
  welcome: 'มาเริ่มกันเลย!',
  correct: 'เยี่ยมมาก! 🎉',
  incorrect: 'ลองใหม่นะ! 💪',
  levelComplete: 'เก่งมาก! 🌟',
  timeRunning: 'เร็วๆ นะ! ⏱️',
  gameWin: 'ยอดเยี่ยม! 🏆',
  gameOver: 'ไม่เป็นไร! 🔄'
};

const owlMessage = ref(owlMessages.welcome);

let timerInterval = null;

const showStages = ref(false);
const stages = ref([]);

function createStages() {
  stages.value = [];
  for (let i = 1; i <= 100; i++) {
    const size = getGridSizeForStage(i);
    stages.value.push({ id: `stage-${i}`, stage: i, level: Math.ceil(i / 10), size, status: i === 1 ? 'unlocked' : 'locked' });
  }
}

function unlockNextStage(stageNum) {
  const nextIdx = stageNum; // zero-based index for next stage
  if (nextIdx < stages.value.length) {
    if (stages.value[nextIdx].status === 'locked') stages.value[nextIdx].status = 'unlocked';
  }
}

function openStageList() { showStages.value = true; }
function closeStageList() { showStages.value = false; }

function goToStage(stageNum) {
  if (!stageNum || stageNum < 1 || stageNum > 100) return;
  const s = stages.value[stageNum - 1];
  if (s.status === 'locked') return;
  currentLevel.value = stageNum;
  elapsedTime.value = 0;
  gameState.value = 'playing';
  generatePuzzle(stageNum);
  startTimer();
  showStages.value = false;
}


function evaluateExpression(numbers, operators) {
  if (!numbers || numbers.length === 0) return null;
  if (operators.length !== numbers.length - 1) return null;

  let result = numbers[0];
  for (let i = 0; i < operators.length; i++) {
    const num = numbers[i + 1];
    const op = operators[i];
    switch (op) {
      case '+': result += num; break;
      case '-': result -= num; break;
      case '*': result *= num; break;
      case '/':
        if (num === 0 || result % num !== 0) return null;
        result = result / num;
        break;
    }
    if (result < 1) return null;
  }
  return result;
}

function applyOperation(current, next, op) {
  switch (op) {
    case '+': return current + next;
    case '-': return current - next;
    case '*': return current * next;
    case '/':
      if (next === 0 || current % next !== 0) return null;
      return current / next;
    default:
      return null;
  }
}

function createLineOperators(numbers, allowedOps, maxResult = 99) {
  const ops = [];
  let result = numbers[0];

  for (let i = 1; i < numbers.length; i++) {
    const next = numbers[i];
    const candidates = allowedOps
      .map(op => ({ op, result: applyOperation(result, next, op) }))
      .filter(candidate => candidate.result !== null && candidate.result >= 1 && candidate.result <= maxResult);

    if (candidates.length === 0) return null;

    const selected = candidates[Math.floor(Math.random() * candidates.length)];
    ops.push(selected.op);
    result = selected.result;
  }

  return { ops, result };
}

// ===== Smaller numbers for first stages of each grid band =====
function getMaxNumForStage(stage) {
  const gridBandStarts = [1, 11, 36, 56, 76, 89, 96, 99, 100];
  let posInBand = 0;
  for (const start of gridBandStarts) {
    if (stage >= start) posInBand = stage - start;
  }
  // First 3 stages of new grid → max 5, next 3 → max 7, then full 9
  if (posInBand <= 2) return 5;
  if (posInBand <= 5) return 7;
  return 9;
}

// ===== CHANGE 4: Hint system helpers =====
function getInitialHints(size) {
  if (size >= 5) return 3;
  if (size >= 3) return 2;
  return 0;
}

function generatePuzzle(stage) {
  const size = getGridSizeForStage(stage);
  const lastIdx = size * 2;
  const count = lastIdx + 1;

  // Gradually unlock operators — warm-up 3 stages per new grid size
  let allowedOps = ['+'];
  if (stage >= 11) {
    allowedOps = stage <= 13 ? ['+'] : ['+', '-'];
  }
  if (stage >= 36) {
    allowedOps = stage <= 38 ? ['+', '-'] : ['+', '-', '*'];
  }
  if (stage >= 56) {
    allowedOps = stage <= 58 ? ['+', '-', '*'] : ['+', '-', '*', '/'];
  }

  // Smaller numbers for first stages of each grid band, gradually increasing to 9
  const maxNum = getMaxNumForStage(stage);

  let numbers = {}, hOps = {}, vOps = {}, hResults = {}, vResults = {};
  let attempts = 0;

  while (attempts < 50) {
    attempts++;
    numbers = {}; hOps = {}; vOps = {}; hResults = {}; vResults = {};

    for (let r = 0; r < lastIdx; r += 2)
      for (let c = 0; c < lastIdx; c += 2)
        numbers[`${r},${c}`] = Math.floor(Math.random() * maxNum) + 1;

    let valid = true;
    for (let r = 0; r < lastIdx && valid; r += 2) {
      const nums = [];
      for (let c = 0; c < lastIdx; c += 2) {
        nums.push(numbers[`${r},${c}`]);
      }
      const line = createLineOperators(nums, allowedOps);
      if (!line) { valid = false; break; }
      line.ops.forEach((op, index) => {
        hOps[`${r},${index * 2 + 1}`] = op;
      });
      hResults[r] = line.result;
    }
    if (!valid) continue;

    for (let c = 0; c < lastIdx && valid; c += 2) {
      const nums = [];
      for (let r = 0; r < lastIdx; r += 2) {
        nums.push(numbers[`${r},${c}`]);
      }
      const line = createLineOperators(nums, allowedOps);
      if (!line) { valid = false; break; }
      line.ops.forEach((op, index) => {
        vOps[`${index * 2 + 1},${c}`] = op;
      });
      vResults[c] = line.result;
    }
    if (valid) break;
  }

  const cells = [];
  for (let row = 0; row < count; row++) {
    for (let col = 0; col < count; col++) {
      const isRowEven = row % 2 === 0;
      const isColEven = col % 2 === 0;
      const isLastRow = row === lastIdx;
      const isLastCol = col === lastIdx;
      const isBeforeLastRow = row === lastIdx - 1;
      const isBeforeLastCol = col === lastIdx - 1;

      let type, value = null, blank = false, answer = null;

      if (isRowEven && isColEven && !isLastRow && !isLastCol) {
        type = 'number'; answer = numbers[`${row},${col}`]; value = answer;
      } else if (isRowEven && !isColEven && !isLastRow && !isBeforeLastCol && !isLastCol) {
        type = 'h_op'; value = hOps[`${row},${col}`];
      } else if (!isRowEven && isColEven && !isLastCol && !isBeforeLastRow && !isLastRow) {
        type = 'v_op'; value = vOps[`${row},${col}`];
      } else if (isRowEven && !isLastRow && isBeforeLastCol) {
        type = 'h_eq'; value = '=';
      } else if (isBeforeLastRow && isColEven && !isLastCol) {
        type = 'v_eq'; value = '=';
      } else if (isRowEven && !isLastRow && isLastCol) {
        type = 'h_result'; value = hResults[row];
      } else if (isLastRow && isColEven && !isLastCol) {
        type = 'v_result'; value = vResults[col];
      } else {
        type = 'corner';
      }

      cells.push({ id: `cell-${row}-${col}`, type, row, col, value, blank, answer, status: null });
    }
  }

  const numCells = cells.filter(c => c.type === 'number');

  // ===== CHANGE 1: Reduced hide ratio with grid penalty =====
  const stageBand = Math.ceil(stage / 10);
  const baseHide = stageBand <= 2 ? 0.40
                 : stageBand <= 4 ? 0.45
                 : stageBand <= 7 ? 0.50
                 : 0.55;
  // Larger grids get fewer hidden cells to avoid overwhelming the player
  const gridPenalty = Math.max(0, (size - 3) * 0.04);
  const hideRatio = Math.max(0.25, baseHide - gridPenalty);
  const numToBlank = Math.max(2, Math.ceil(numCells.length * hideRatio));

  const shuffled = [...numCells].sort(() => Math.random() - 0.5);
  for (let i = 0; i < numToBlank; i++) {
    shuffled[i].value = null;
    shuffled[i].blank = true;
  }

  // ===== 3×3 constraint: center cell always given + max 2 blanks per row/col =====
  if (size === 3) {
    // 1) Force center cell (2,2) to be filled
    const centerCell = cells.find(c => c.type === 'number' && c.row === 2 && c.col === 2);
    if (centerCell && centerCell.blank) {
      centerCell.blank = false;
      centerCell.value = centerCell.answer;
    }
    // 2) Ensure no row or column has all 3 cells blank (max 2 blanks per line)
    for (let r = 0; r < lastIdx; r += 2) {
      const rowCells = cells.filter(c => c.type === 'number' && c.row === r);
      const blankInRow = rowCells.filter(c => c.blank);
      if (blankInRow.length >= 3) {
        // Restore one random blank cell in this row
        const restore = blankInRow[Math.floor(Math.random() * blankInRow.length)];
        restore.blank = false;
        restore.value = restore.answer;
      }
    }
    for (let c = 0; c < lastIdx; c += 2) {
      const colCells = cells.filter(cl => cl.type === 'number' && cl.col === c);
      const blankInCol = colCells.filter(cl => cl.blank);
      if (blankInCol.length >= 3) {
        // Restore one random blank cell in this column
        const restore = blankInCol[Math.floor(Math.random() * blankInCol.length)];
        restore.blank = false;
        restore.value = restore.answer;
      }
    }
  }

  gridCells.value = cells;
  hintsRemaining.value = getInitialHints(size);
  lineCorrect.value = {};

  setTimeout(() => {
    const firstBlank = cells.find(c => c.type === 'number' && c.blank);
    if (firstBlank) selectCell(firstBlank.id);
  }, 100);
}

function selectCell(cellId) {
  const cell = gridCells.value.find(c => c.id === cellId);
  if (cell && cell.type === 'number' && cell.blank) {
    activeCellId.value = cellId;
    nextTick(() => {
      const el = cellRefs.value[cellId];
      if (el) el.focus();
    });
  }
}

function handleDigitInput(digit) {
  if (!activeCellId.value) {
    const firstBlank = gridCells.value.find(c => c.type === 'number' && c.blank && !c.value);
    if (firstBlank) selectCell(firstBlank.id);
    else return;
  }
  const cell = gridCells.value.find(c => c.id === activeCellId.value);
  if (cell && cell.type === 'number' && cell.blank) {
    cell.value = digit;
    checkAllLines();
    if (cell.status === 'correct') owlMessage.value = owlMessages.correct;
    else if (cell.status === 'incorrect') owlMessage.value = owlMessages.incorrect;
    moveToNextCell();
  }
}

function handleDelete() {
  if (!activeCellId.value) return;
  const cell = gridCells.value.find(c => c.id === activeCellId.value);
  if (cell && cell.type === 'number' && cell.blank) {
    cell.value = null;
    cell.status = null;
    lineCorrect.value = {};
    gridCells.value.filter(c => c.type === 'number').forEach(c => { c.status = null; });
  }
}

// ===== CHANGE 4: Hint system =====
function useHint() {
  if (hintsRemaining.value <= 0) return;
  const emptyCells = gridCells.value.filter(c => c.type === 'number' && c.blank && c.value === null);
  if (emptyCells.length === 0) return;
  const cell = emptyCells[Math.floor(Math.random() * emptyCells.length)];
  cell.value = cell.answer;
  cell.status = 'correct';
  hintsRemaining.value--;
  owlMessage.value = 'ช่วยเติมให้ 1 ช่อง! 💡';
  checkAllLines();
  const nextEmpty = gridCells.value.find(c => c.type === 'number' && c.blank && c.value === null);
  if (nextEmpty) selectCell(nextEmpty.id);
}

// ===== CHANGE 5: Visual feedback — track correct rows/columns =====
function checkAllLines() {
  const size = gridSize.value;
  const lastIdx = size * 2;
  let correctH = 0, correctV = 0;

  lineCorrect.value = {};

  gridCells.value
    .filter(c => c.type === 'number' && c.blank)
    .forEach(c => { c.status = null; });

  for (let r = 0; r < lastIdx; r += 2) {
    const hRes = gridCells.value.find(c => c.type === 'h_result' && c.row === r);
    if (!hRes) continue;
    const rowNums = gridCells.value.filter(c => c.type === 'number' && c.row === r).sort((a, b) => a.col - b.col);
    if (rowNums.some(c => c.value === null)) continue;
    const rowHops = gridCells.value.filter(c => c.type === 'h_op' && c.row === r).sort((a, b) => a.col - b.col);
    const computed = evaluateExpression(rowNums.map(c => c.value), rowHops.map(c => c.value));
    if (computed !== null && computed === hRes.value) {
      correctH++;
      lineCorrect.value[`h-${r}`] = true;
      rowNums.forEach(c => { c.status = 'correct'; });
    } else {
      rowNums.forEach(c => { if (c.blank) c.status = 'incorrect'; });
    }
  }

  for (let col = 0; col < lastIdx; col += 2) {
    const vRes = gridCells.value.find(c => c.type === 'v_result' && c.col === col);
    if (!vRes) continue;
    const colNums = gridCells.value.filter(c => c.type === 'number' && c.col === col).sort((a, b) => a.row - b.row);
    if (colNums.some(c => c.value === null)) continue;
    const colVops = gridCells.value.filter(c => c.type === 'v_op' && c.col === col).sort((a, b) => a.row - b.row);
    const computed = evaluateExpression(colNums.map(c => c.value), colVops.map(c => c.value));
    if (computed !== null && computed === vRes.value) {
      correctV++;
      lineCorrect.value[`v-${col}`] = true;
      colNums.forEach(c => { c.status = 'correct'; });
    } else {
      colNums.forEach(c => { if (c.blank) c.status = 'incorrect'; });
    }
  }

  if (correctH === size && correctV === size) levelComplete();
}

function moveToNextCell() {
  const blankCells = gridCells.value.filter(c => c.type === 'number' && c.blank);
  const currentIdx = blankCells.findIndex(c => c.id === activeCellId.value);
  if (currentIdx < blankCells.length - 1) {
    selectCell(blankCells[currentIdx + 1].id);
  } else {
    const firstEmpty = blankCells.find(c => !c.value);
    if (firstEmpty) selectCell(firstEmpty.id);
  }
}

function startGame() {
  if (!playerName.value.trim()) return;

  // Generate unique session ID for this game session
  gameSessionId.value = uuid();

  gameState.value = 'playing';
  currentLevel.value = 1;
  score.value = 0;
  elapsedTime.value = 0;
  totalTimeSpent.value = 0;

  generatePuzzle(currentLevel.value);
  startTimer();
  
  owlMessage.value = owlMessages.welcome;
}

function startTimer() {
  if (timerInterval) clearInterval(timerInterval);

  timerInterval = setInterval(() => {
    elapsedTime.value++;
    totalTimeSpent.value++;
  }, 1000);
}

function levelComplete() {
  clearInterval(timerInterval);

  // ===== CHANGE 4: Hint score penalty (-10 per hint used) =====
  const hintsUsed = getInitialHints(gridSize.value) - hintsRemaining.value;
  const hintPenalty = hintsUsed * 10;
  const baseScore = Math.max(0, gridSize.value * gridSize.value * 10 - hintPenalty);
  levelScore.value = baseScore;
  timeBonus.value = Math.max(0, Math.floor((selectedTime.value - elapsedTime.value) * 2));
  score.value += baseScore + timeBonus.value;

  owlMessage.value = owlMessages.levelComplete;

  // mark current stage as completed and unlock next
  const idx = currentLevel.value - 1;
  if (stages.value[idx]) stages.value[idx].status = 'completed';
  unlockNextStage(currentLevel.value);

  saveScore(); // Save score after every completed level

  if (currentLevel.value >= 100) {
    gameState.value = 'gameWin';
    owlMessage.value = owlMessages.gameWin;
  } else {
    gameState.value = 'levelComplete';
  }
}

function nextLevel() {
  currentLevel.value++;
  elapsedTime.value = 0;

  gameState.value = 'playing';
  generatePuzzle(currentLevel.value);
  startTimer();
  
  owlMessage.value = owlMessages.welcome;
}

function gameOver() {
  clearInterval(timerInterval);
  gameState.value = 'gameOver';
  owlMessage.value = owlMessages.gameOver;
  saveScore(); // Save score on game over (time up)
}

function quitGame() {
  clearInterval(timerInterval);
  saveScore(); // Save score on quit
  showQuitConfirm.value = false;
  gameState.value = 'login';
  playerName.value = authStore.user?.name || 'ผู้เล่น';
  currentLevel.value = 1;
  score.value = 0;
  elapsedTime.value = 0;
  totalTimeSpent.value = 0;
  activeCellId.value = null;
}

function restartGame() {
  gameState.value = 'login';
  playerName.value = authStore.user?.name || 'ผู้เล่น';
  currentLevel.value = 1;
  score.value = 0;
  elapsedTime.value = 0;
  activeCellId.value = null;
  gridCells.value = [];
}

function handleKeydown(event) {
  if (event.key === 'Enter' && gameState.value === 'levelComplete') {
    if (event.repeat) return;
    event.preventDefault();
    nextLevel();
    return;
  }
  if (gameState.value !== 'playing') return;

  const blankCells = gridCells.value.filter(c => c.type === 'number' && c.blank);
  const currentIndex = blankCells.findIndex(c => c.id === activeCellId.value);

  switch (event.key) {
    case 'ArrowRight':
    case 'ArrowDown':
      if (currentIndex < blankCells.length - 1) selectCell(blankCells[currentIndex + 1].id);
      event.preventDefault();
      break;
    case 'ArrowLeft':
    case 'ArrowUp':
      if (currentIndex > 0) selectCell(blankCells[currentIndex - 1].id);
      event.preventDefault();
      break;
    case 'Tab':
      event.preventDefault();
      moveToNextCell();
      break;
    case 'Delete':
    case 'Backspace':
      handleDelete();
      event.preventDefault();
      break;
    default:
      if (event.key >= '1' && event.key <= '9') handleDigitInput(parseInt(event.key));
      break;
  }
}

onMounted(() => {
  createStages();
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  if (timerInterval) clearInterval(timerInterval);
});

watch(activeCellId, (newVal) => {
  if (newVal) {
    nextTick(() => {
      const el = cellRefs.value[newVal];
      if (el) el.focus();
    });
  }
});
</script>

<style scoped>
@keyframes stripes {
  from { background-position: 0 0; }
  to { background-position: 40px 0; }
}

.animate-stripes {
  animation: stripes 1s linear infinite;
}

@keyframes bounce-in {
  0% {
    transform: scale(0) translateY(-50%);
    opacity: 0;
  }
  50% {
    transform: scale(1.1) translateY(0);
  }
  100% {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-4px); }
  40% { transform: translateX(4px); }
  60% { transform: translateX(-3px); }
  80% { transform: translateX(3px); }
}

.animate-shake {
  animation: shake 0.4s ease-in-out;
}

@keyframes pulse-slow {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

@keyframes bounce-slow {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce-in {
  animation: bounce-in 0.5s ease-out forwards;
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}

.animate-bounce-slow {
  animation: bounce-slow 2s ease-in-out infinite;
}

.perspective-1000 {
  perspective: 1000px;
}

.rotate-x-2 {
  transform: rotateX(2deg);
}

.numpad-btn {
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

.numpad-btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.5);
}
</style>
