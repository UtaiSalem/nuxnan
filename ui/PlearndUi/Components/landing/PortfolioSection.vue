<template>
  <section class="bg-white py-16">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl md:text-4xl font-bold text-center mb-10 text-gray-800">Portfolio</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="rounded-lg overflow-hidden shadow-lg">
          <img src="/images/portfolio1.jpg" alt="Portfolio 1" class="w-full h-48 object-cover">
          <div class="p-6">
            <h3 class="text-xl font-semibold mb-2">Project One</h3>
            <p class="text-gray-600">A modern social platform for creative communities.</p>
          </div>
        </div>
        <div class="rounded-lg overflow-hidden shadow-lg">
          <img src="/images/portfolio2.jpg" alt="Portfolio 2" class="w-full h-48 object-cover">
          <div class="p-6">
            <h3 class="text-xl font-semibold mb-2">Project Two</h3>
            <p class="text-gray-600">Mobile-first networking app for professionals.</p>
          </div>
        </div>
        <div class="rounded-lg overflow-hidden shadow-lg">
          <img src="/images/portfolio3.jpg" alt="Portfolio 3" class="w-full h-48 object-cover">
          <div class="p-6">
            <h3 class="text-xl font-semibold mb-2">Project Three</h3>
            <p class="text-gray-600">Community-driven platform for events and groups.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// No script logic needed for static portfolio section
</script>
