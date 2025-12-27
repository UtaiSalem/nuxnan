<script setup>
import SectionBorder from '@/components/SectionBorder.vue';
import UpdatePasswordForm from '@/pages/profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/pages/profile/Partials/UpdateProfileInformationForm.vue';

definePageMeta({
    layout: 'main',
    middleware: 'auth',
});

defineProps({
    sessions: Array,
});

useHead({
    title: 'Profile',
});
</script>

<template>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6">
                Profile
            </h2>

            <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                <UpdateProfileInformationForm :user="$page.props.auth.user" />

                <SectionBorder />
            </div>

            <div v-if="$page.props.jetstream.canUpdatePassword">
                <UpdatePasswordForm class="mt-10 sm:mt-0" />

                <SectionBorder />
            </div>

            <!-- <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                <TwoFactorAuthenticationForm
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                    class="mt-10 sm:mt-0"
                />

                <SectionBorder />
            </div> -->

            <!-- <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" /> -->

            <!-- <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                <SectionBorder />

                <DeleteUserForm class="mt-10 sm:mt-0" />
            </template> -->
        </div>
    </div>
</template>

