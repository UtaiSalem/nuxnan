<template>
  <section class="bg-gray-50 py-16">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl md:text-4xl font-bold text-center mb-10 text-gray-800">Pricing Plans</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white rounded-lg shadow p-8 text-center">
          <h3 class="text-xl font-semibold mb-2 text-blue-700">Basic</h3>
          <div class="text-4xl font-bold mb-4">Free</div>
          <ul class="text-gray-600 mb-6">
            <li>Community Access</li>
            <li>Basic Features</li>
            <li>Email Support</li>
          </ul>
          <button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Sign Up</button>
        </div>
        <div class="bg-white rounded-lg shadow p-8 text-center border-2 border-indigo-600">
          <h3 class="text-xl font-semibold mb-2 text-indigo-700">Pro</h3>
          <div class="text-4xl font-bold mb-4">$19/mo</div>
          <ul class="text-gray-600 mb-6">
            <li>All Basic Features</li>
            <li>Advanced Analytics</li>
            <li>Priority Support</li>
          </ul>
          <button class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition">Get Pro</button>
        </div>
        <div class="bg-white rounded-lg shadow p-8 text-center">
          <h3 class="text-xl font-semibold mb-2 text-green-700">Enterprise</h3>
          <div class="text-4xl font-bold mb-4">Contact Us</div>
          <ul class="text-gray-600 mb-6">
            <li>All Pro Features</li>
            <li>Custom Solutions</li>
            <li>Dedicated Support</li>
          </ul>
          <button class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">Contact</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// No script logic needed for static pricing section
</script>
