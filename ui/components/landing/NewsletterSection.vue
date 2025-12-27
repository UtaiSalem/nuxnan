<template>
  <section class="bg-indigo-700 py-16 text-white">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-3xl md:text-4xl font-bold mb-4">Subscribe to Our Newsletter</h2>
      <p class="text-lg mb-8">Get the latest updates, news, and offers from Pitnik directly to your inbox.</p>
      <form class="flex flex-col md:flex-row justify-center gap-4 max-w-xl mx-auto">
        <input type="email" placeholder="Enter your email" class="px-4 py-3 rounded text-gray-800 focus:outline-none w-full md:w-auto" required>
        <button type="submit" class="bg-white text-indigo-700 font-semibold px-6 py-3 rounded shadow hover:bg-indigo-50 transition">Subscribe</button>
      </form>
    </div>
  </section>
</template>

<script setup>
// No script logic needed for static newsletter section
</script>
