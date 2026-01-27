<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Email Verification" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-4 text-sm">
            Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another. <br /> If you did not receive the email, we will gladly send you another.
        </div>
        <div class="mb-4 text-sm">
            ก่อนการดำเนินการต่อ กรุณายืนยันที่อยู่อีเมลของคุณโดยการคลิกที่ลิงก์ที่เราส่งไปให้คุณทางอีเมลเมื่อสักครู่? <br /> หากคุณไม่ได้รับอีเมล ทางเรายินดีที่จะส่งให้คุณอีกครั้ง
        </div>

        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-600">
            A new verification link has been sent to the email address you provided in your profile settings. 
        </div>
        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-600">
            ลิงก์ยืนยันใหม่ถูกส่งไปยังที่อยู่อีเมลที่คุณให้ไว้ในการตั้งค่าโปรไฟล์ของคุณแล้ว
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex flex-col gap-2 w-full">
                
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <div class="flex flex-col w-full">
                        <span> Resend Verification Email</span><span> ส่งอีเมลยืนยันอีกครั้ง</span> 
                    </div>
                </PrimaryButton>

                <Link :href="route('profile.show')"
                    class="p-2 text-center border text-sm bg-violet-400 text-white  hover:bg-violet-600 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Edit Profile/แก้ไขโปรไฟล์       
                </Link>

                <Link :href="route('logout')" method="post" as="button"
                    class="w-full border p-2 text-sm text-red-600 hover:text-gray-900 hover:bg-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Log Out
                </Link>

            </div>
        </form>
    </AuthenticationCard>
</template>
