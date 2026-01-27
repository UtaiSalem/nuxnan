<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    // console.log('form submitted');
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Profile Information / ข้อมูลโปรไฟล์
        </template>

        <template #description>
            Update your account's profile information and email address./ อัพเดทข้อมูลโปรไฟล์และอีเมลของคุณ
        </template>

        <template #form>
            <!-- Profile Photo -->
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <!-- Profile Photo File Input -->
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Photo / รูปถ่าย" />

                <!-- Current Profile Photo -->
                <div v-show="! photoPreview" class="mt-2">
                    <img :src="user.profile_photo_url" :alt="user.name" class="object-cover w-20 h-20 rounded-full">
                </div>

                <!-- New Profile Photo Preview -->
                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block w-20 h-20 bg-center bg-no-repeat bg-cover rounded-full"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>

                <SecondaryButton class="mt-2 me-2" type="button" @click.prevent="selectNewPhoto">
                    Select A New Photo / เลือกรูปใหม่
                </SecondaryButton>

                <SecondaryButton
                    v-if="user.profile_photo_path"
                    type="button"
                    class="mt-2"
                    @click.prevent="deletePhoto"
                >
                    Remove Photo / ลบรูป
                </SecondaryButton>

                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Name/ ชื่อ - สกุล" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email/ อีเมล" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full mt-1"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2" />

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null">
                    <p class="mt-2 text-sm">
                        Your email address is unverified. / อีเมลของคุณยังไม่ได้รับการยืนยัน

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            @click.prevent="sendEmailVerification"
                        >
                            Click here to re-send the verification email. / คลิกที่นี่เพื่อส่งอีเมลยืนยันอีกครั้ง
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-2 text-sm font-medium text-green-600">
                        A new verification link has been sent to your email address. / ลิงค์ยืนยันใหม่ถูกส่งไปยังอีเมลของคุณแล้ว
                    </div>
                </div>
            </div>

            <!-- <div class="col-span-6 sm:col-span-4">
                <p class="text-sm font-semibold text-gray-600">Referal Link / ลิงค์อ้างอิง</p>
                <InputLabel for="referal_link" value="Referal Link / ลิงค์อ้างอิง" />
                <TextInput
                    id="referal_link"
                    type="text"
                    class="hidden"
                    required
                    autocomplete="referal_link"
                />
                <p>
                    <a :href="$page.props.auth.user.referal_link" target="_blank" class="hover:underline text-blue-600">
                    {{ $page.props.auth.user.referal_link }}
                    </a>
                </p>
            </div> -->
            <div class="col-span-6 sm:col-span-4">
                <!-- <InputLabel for="personal_code" value="Personal code / รหัสประจำตัวสมาชิก" />
                <TextInput
                    id="personal_code"
                    type="text"
                    class="hidden"
                    required
                    autocomplete="name"
                /> -->
                <p class="text-sm font-semibold text-gray-600">Personal code / รหัสประจำตัวสมาชิก</p>
                <p class="text-blue-600 text-[32px] font-bold">
                    {{ $page.props.auth.user.personal_code }}
                </p>
            </div>
            
            <div class="col-span-6 sm:col-span-4">
                <!-- <InputLabel for="member-level" value="จำนวนผู้อ้างอิง" />
                <TextInput
                    id="member-level"
                    type="text"
                    class="hidden"
                    required
                    autocomplete="name"
                /> -->
                <p class="text-sm font-semibold text-gray-600">จำนวนผู้อ้างอิง</p>
                <p class="text-blue-600 text-[32px] font-bold">
                    {{ $page.props.auth.user.no_of_ref }}
                </p>
            </div>
            
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved. / บันทึกแล้ว
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save / บันทึก
            </PrimaryButton>
        </template>
    </FormSection>
</template>
