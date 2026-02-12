<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Icon } from '@iconify/vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    user_info: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>
        <h2 class="text-2xl font-semibold text-center text-gray-700 plearnd-brand">Plearnd</h2>
        <p class="text-xl font-semibold text-center text-gray-600 my-4">ยินดีต้อนรับอีกครั้ง</p>

        <!-- <a :href="route('google.redirect', { provider: 'google'})" class="flex items-center justify-center mt-4 text-white border rounded-lg shadow-md hover:bg-gray-100">
            <div class="px-2 py-3">
                <svg class="w-6 h-6" viewBox="0 0 40 40">
                    <path
                        d="M36.3425 16.7358H35V16.6667H20V23.3333H29.4192C28.045 27.2142 24.3525 30 20 30C14.4775 30 10 25.5225 10 20C10 14.4775 14.4775 9.99999 20 9.99999C22.5492 9.99999 24.8683 10.9617 26.6342 12.5325L31.3483 7.81833C28.3717 5.04416 24.39 3.33333 20 3.33333C10.7958 3.33333 3.33335 10.7958 3.33335 20C3.33335 29.2042 10.7958 36.6667 20 36.6667C29.2042 36.6667 36.6667 29.2042 36.6667 20C36.6667 18.8825 36.5517 17.7917 36.3425 16.7358Z"
                        fill="#FFC107" />
                    <path
                        d="M5.25497 12.2425L10.7308 16.2583C12.2125 12.59 15.8008 9.99999 20 9.99999C22.5491 9.99999 24.8683 10.9617 26.6341 12.5325L31.3483 7.81833C28.3716 5.04416 24.39 3.33333 20 3.33333C13.5983 3.33333 8.04663 6.94749 5.25497 12.2425Z"
                        fill="#FF3D00" />
                    <path
                        d="M20 36.6667C24.305 36.6667 28.2167 35.0192 31.1742 32.34L26.0159 27.975C24.3425 29.2425 22.2625 30 20 30C15.665 30 11.9842 27.2359 10.5975 23.3784L5.16254 27.5659C7.92087 32.9634 13.5225 36.6667 20 36.6667Z"
                        fill="#4CAF50" />
                    <path
                        d="M36.3425 16.7358H35V16.6667H20V23.3333H29.4192C28.7592 25.1975 27.56 26.805 26.0133 27.9758C26.0142 27.975 26.015 27.975 26.0158 27.9742L31.1742 32.3392C30.8092 32.6708 36.6667 28.3333 36.6667 20C36.6667 18.8825 36.5517 17.7917 36.3425 16.7358Z"
                        fill="#1976D2" />
                </svg>
            </div>
            <h1 class="px-2 py-3 font-bold text-center text-gray-600">Sign in with Google</h1>
        </a> -->

        <!-- <div class="flex items-center justify-between my-4">
            <span class="w-5/12 border-b"></span>
            <p class="text-xs text-center text-gray-500 uppercase">or </p>
            <span class="w-5/12 border-b"></span>
        </div> -->

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="user_info" value="อีเมล / ชื่อ - สกุล / หมายเลขโทรศัพท์ หรือ รหัสส่วนตัว" />
                <TextInput
                    id="user_info"
                    v-model="form.user_info"
                    type="text"
                    class="block w-full mt-1"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.user_info" />
            </div>

            <div class="mt-4 relative">
                <InputLabel for="password" value="Password / รหัสผ่าน" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text':'password'"
                    class="block w-full mt-1"
                    required
                    autocomplete="current-password"
                />
                <button @click="showPassword = !showPassword" type="button" class="absolute top-[26px] right-0 px-3 py-2">
                    <Icon :icon="showPassword ? 'bi:eye-slash-fill':'bi:eye-fill'" class="w-5 h-5 text-gray-500" />
                </button>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="text-sm text-gray-600 ms-2">Remember me</span>
                </label>
            </div> -->

            <div class="my-4">
                <div class="">
                    <button type="submit" class="w-full p-2 text-center text-white bg-blue-600 rounded-md hover:bg-indigo-600" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Log in / เข้าสู่ระบบ
                    </button>
                </div>
            </div>
        </form>
        
        <div class="flex items-center justify-between mt-4">
            <div class="">
                <Link v-if="canResetPassword" :href="route('password.request')" class="w-full text-sm text-center text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Forgot your password? / ลืมรหัสผ่าน?
                </Link>
            </div>

            <div class="flex items-center justify-end ">
                <Link :href="'/register'" class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    สมัครสมาชิก
                </Link>
                <!-- <Link :href="'/register'" class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    สมัครสมาชิก
                </Link> -->
            </div>
        </div>
    </AuthenticationCard>
</template>
