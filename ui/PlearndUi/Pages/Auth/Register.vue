<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    isSuggesterActive: Boolean,
    suggester: Object,
});

const refSuggester = ref(props.suggester ? props.suggester.data : null);

const refIsSuggesterActive = ref(props.isSuggesterActive);
const isLoadingSuggester = ref(false);
const isLoadingSubmit = ref(false);


const showPassword = ref(false);

const form = useForm({
    suggester: refSuggester ? refSuggester.personal_code :'11111111',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const handleSubmit = () => {
    isLoadingSubmit.value = true;
    // console.log(form.name);
    checkUserNameIsAlreadyExists(form.name);
    // checkEmailIsAlreadyExists(form.email);
};

const submit = async () => {
    const suggesterResponse = await axios.get('/suggester/check/'+ form.suggester);

    if(suggesterResponse.data && !suggesterResponse.data.isSuggesterActive){
        Swal.fire({
            title: 'ไม่สามารถใช้รหัสผู้แนะนำนี้ได้',
            text: 'เนื่องจากครบจำนวนผู้ให้การแนะนำแล้ว กรุณาใช้รหัสผู้แนะนำใหม่',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });

        refIsSuggesterActive.value = false;
        isLoadingSuggester.value = false;
        isLoadingSubmit.value = false;
        form.referral = '';
    }else{
        form.post('/register', {
            onFinish: () => { 
                form.reset('password', 'password_confirmation')
                isLoadingSubmit.value = false;},
        });
    }
    isLoadingSubmit.value = false;
};

onMounted(() => {
    refSuggester.value ? checkSuggester(refSuggester.value): refIsSuggesterActive.value = false;
});

const handleInputSuggester = async (e) => {
    if(e.target.value.length == 8){
        form.suggester = e.target.value;
        isLoadingSuggester.value = true;
        try {

            const suggesterResponse = await axios.get('/suggester/check/'+ form.suggester);

            if(suggesterResponse.data && !suggesterResponse.data.isSuggesterActive){
                Swal.fire({
                    title: 'ไม่สามารถใช้รหัสผู้แนะนำนี้ได้',
                    text: 'เนื่องจากครบจำนวนผู้ให้การแนะนำแล้ว กรุณาใช้รหัสผู้แนะนำใหม่',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });

                refIsSuggesterActive.value = false;
                isLoadingSuggester.value = false;
                form.referral = '';
            }else{
                refIsSuggesterActive.value = true;
                refSuggester.value = suggesterResponse.data.suggester;
                isLoadingSuggester.value = false;
            }
        } catch (error) {
            isLoadingSuggester.value = false;
            Swal.fire({
                title: 'ไม่พบข้อมูลผู้แนะนำ',
                text: 'กรุณาตรวจสอบรหัสผู้แนะนำอีกครั้ง',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
            form.referral = '';
        }
    }
};

const checkSuggester = (suggesterParam) => {
    if(suggesterParam.no_of_ref < 5){
        refIsSuggesterActive.value = true;
        return;
    }else{
        refIsSuggesterActive.value = false;
        refSuggester.value = null;
        Swal.fire({
            title: 'ไม่สามารถใช้รหัสผู้แนะนำนี้ได้',
            text: 'เนื่องจากครบจำนวนผู้ให้การแนะนำแล้ว กรุณาใช้รหัสผู้แนะนำใหม่',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        form.referral = '';
        window.location.href = '/register';
    }
};

const checkUserNameIsAlreadyExists = async (name) => {
    const usernameResponse = await axios.get('/check-username-exists/'+ name);
    if (usernameResponse.data && usernameResponse.data.exists){
        Swal.fire({
            title: 'ชื่อนี้มีผู้ใช้แล้ว',
            text: ' กรุณาลงชื่อเข้าใช้ หรือใช้ชื่อผู้ใช้อื่น',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        isLoadingSubmit.value = false;
    }else{
        checkEmailIsAlreadyExists(form.email);
    }
};
        
const checkEmailIsAlreadyExists = async (email) => {
    const emailResponse = await axios.get('/check-email-exists/'+ email);
    if (emailResponse.data && emailResponse.data.exists){
        Swal.fire({
            title: 'อีเมลนี้มีผู้ใช้งานแล้ว',
            text: 'กรุณาลงชื่อเข้าใช้ หรือใช้อีเมลอื่น',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        isLoadingSubmit.value = false;
    }else{
        submit();
    }
};


</script>

<template>
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>
        <h2 class="text-2xl font-semibold text-center text-gray-700 plearnd-brand">Plearnd</h2>
        <p class="text-xl font-semibold text-center text-gray-600 my-2">ยินดีต้อนรับ</p>
        
        <div class="mb-2">
            <InputLabel v-if="!refSuggester || !refIsSuggesterActive" for="referral" value="รหัสผู้แนะนำ" />
            <TextInput v-if="!refSuggester || !refIsSuggesterActive" 
                id="referral"
                v-model="form.referral"
                type="text"
                class="block w-full mt-1"
                required
                autocomplete="referral"
                @input="handleInputSuggester"
            />
            <InputError class="mt-2" :message="form.errors.referral" />
        </div>

        <figure v-if="refIsSuggesterActive">
            <div class="text-md font-semibold text-gray-700 dark:text-gray-400">ผู้แนะนำ</div>
            <!-- label "ผู้แนะนำ" -->
            <div class="flex p-2 mb-4 mt-2 bg-green-300 rounded-md">
                <div class="flex-shrink-0">
                    <img class="rounded-full w-11 h-11" :src="refSuggester.avatar"
                        :alt="refSuggester.name + 'photo'">
                </div>
                <div class="w-full ps-3">
                    <div class="mb-1 space-x-2 text-sm text-gray-700 dark:text-gray-400">
                        <span class="font-semibold">{{ refSuggester.name }}</span>
                        <span class="mx-4">รหัสประจำตัว</span>
                        <span class="font-semibold">{{ refSuggester.personal_code }}</span>
                    </div>
                    <div class="text-base text-blue-600 dark:text-blue-500">{{ refSuggester.email }}</div>
                </div>
            </div>
        </figure>

        <!-- <InputError class="mt-2" :message="over_limit" /> -->
         <!-- loading spinner icon -->
        <div v-if="isLoadingSuggester" class="flex justify-center mt-4">
            <Icon icon="svg-spinners:6-dots-rotate" class="w-20 h-20 text-violet-500 animate-spin" />
        </div>

        <form @submit.prevent="handleSubmit" v-if="refIsSuggesterActive && !isLoadingSuggester">
            <input type="hidden" name="suggester" v-model="form.suggester" >
            <div>
                <InputLabel for="name" value="Name / ชื่อ - สกุล" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    required

                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email / อีเมล" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full mt-1"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="phone" value="หมายเลขโทรศัพท์ (ไม่บังคับใส่)" />
                <TextInput
                    id="phone"
                    v-model="form.phone"
                    type="number"
                    class="block w-full mt-1"
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4 relative">
                <InputLabel for="password" value="Password / รหัสผ่าน" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text':'password'"
                    class="block w-full mt-1"
                    required
                    autocomplete="new-password"
                />
                <button @click="showPassword = !showPassword" type="button" class="absolute top-[26px] right-0 px-3 py-2">
                    <Icon :icon="showPassword ? 'bi:eye-slash-fill':'bi:eye-fill'" class="w-5 h-5 text-gray-500" />
                </button>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password / ยืนยันรหัสผ่าน" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showPassword ? 'text':'password'"
                    class="block w-full mt-1"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                <InputLabel for="terms">
                    <div class="flex items-center">
                        <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                        <div class="ms-2">
                            I agree to the <a target="_blank" :href="route('terms.show')" class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Terms of Service</a> and <a target="_blank" :href="route('policy.show')" class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Privacy Policy</a>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link :href="'/login'" class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Already registered? เข้าสู่ระบบ
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <Icon v-if="isLoadingSubmit" icon="svg-spinners:6-dots-rotate" class="w-5 h-5 mr-1 animate-spin" />
                    <span>สมัครสมาชิก</span>
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
