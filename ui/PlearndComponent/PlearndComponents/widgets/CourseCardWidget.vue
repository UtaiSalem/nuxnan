<template>
    <Link :href="'/courses/'+ course.id">
        <div class=" bg-white rounded-lg shadow-lg overflow-hidden flex flex-col justify-center items-center">
            <img :src="$page.props.profilePath + 'storage/images/banner-reaction.jpg'" alt="course-banner">
            <img :src="$page.props.profilePath + course.user.avatar" alt="course-banner"
                class="w-20 -mt-10 border-4 border-primary rounded-full">

            <div class="mt-2">
                <p class="font-semibold">
                    {{ course.name }}
                </p>
            </div>
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    course: Object,
})
</script>
