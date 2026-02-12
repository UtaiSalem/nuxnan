<script setup>
import { Link } from '@inertiajs/vue3';
</script>
<template>
    <div class="flex h-screen bg-gray-100">
      <!-- Sidebar -->
      <div class="w-64 bg-blue-400 text-white p-6">
        <h2 class="text-2xl font-bold mb-6">เมนูเกม</h2>
        <nav>
          <ul class="space-y-2">
            <li>
              <Link href="/"
                :class="{'bg-blue-700': $page.url === '/game/guessing-number'}"
                class="block py-2 px-4 hover:bg-blue-700 rounded">
                หน้าแรก
              </Link>
            </li>
            <li>
              <Link href="/game/guessing-number"
                :class="{'bg-blue-700': $page.url === '/game/guessing-number'}"
                class="block py-2 px-4 hover:bg-blue-700 rounded">
                เกมทายตัวเลข
              </Link>
            </li>
            <li>
              <Link href="/game/xo" 
                :class="{'bg-blue-700': $page.url === '/game/xo'}"
                class="block py-2 px-4 hover:bg-blue-700 rounded">
                เกม XO
              </Link>
            </li>
            <li>
              <Link href="/game/snake" 
                :class="{'bg-blue-700': $page.url === '/game/snake'}"
                class="block py-2 px-4 hover:bg-blue-700 rounded">
                เกมงู
              </Link>
            </li>
            <!-- เพิ่มลิงก์เกมอื่น ๆ ตามต้องการ -->
          </ul>
        </nav>
      </div>
  
      <!-- Main content area -->
      <div class="w-full h-full">
        <slot></slot>
      </div>
    </div>
  </template>
