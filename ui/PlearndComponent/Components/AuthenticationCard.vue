<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
<style>
@import url('https://fonts.googleapis.com/css2?family=Audiowide&display=swap');

.plearnd-brand {
                font-family: 'Audiowide', sans-serif;
                font-size: 1.5rem;
                font-weight: 400;
                font-style: normal;
            }
</style>
