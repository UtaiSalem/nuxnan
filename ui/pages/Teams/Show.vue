<script setup>
import DeleteTeamForm from '@/pages/Teams/Partials/DeleteTeamForm.vue';
import SectionBorder from '@/components/SectionBorder.vue';
import TeamMemberManager from '@/pages/Teams/Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from '@/pages/Teams/Partials/UpdateTeamNameForm.vue';

definePageMeta({
    layout: 'main',
    middleware: 'auth',
});

defineProps({
    team: Object,
    availableRoles: Array,
    permissions: Object,
});

useHead({
    title: 'Team Settings',
});
</script>

<template>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6">
                Team Settings
            </h2>

            <UpdateTeamNameForm :team="team" :permissions="permissions" />

            <TeamMemberManager
                class="mt-10 sm:mt-0"
                :team="team"
                :available-roles="availableRoles"
                :user-permissions="permissions"
            />

            <template v-if="permissions.canDeleteTeam && ! team.personal_team">
                <SectionBorder />

                <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
            </template>
        </div>
    </div>
</template>

