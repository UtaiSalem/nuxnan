<template>
    <div>
        <n-avatar-group :options="options" :size="40" :max="3">
            <template #avatar="{ option: { name, src } }">
            <n-tooltip>
                <template #trigger>
                <n-avatar :src="src" />
                </template>
                {{ name }}
            </n-tooltip>
            </template>
            <template #rest="{ options: restOptions, rest }">
            <n-dropdown :options="createDropdownOptions(restOptions)" placement="top">
                <n-avatar>+{{ rest }}</n-avatar>
            </n-dropdown>
            </template>
        </n-avatar-group>
    </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { NAvatarGroup, NTooltip, NDropdown, NAvatar } from 'naive-ui';

const options = reactive([
        {
          name: 'Leonardo DiCaprio',
          src: 'https://gw.alipayobjects.com/zos/antfincdn/aPkFc8Sj7n/method-draw-image.svg'
        },
        {
          name: 'Jennifer Lawrence',
          src: 'https://07akioni.oss-cn-beijing.aliyuncs.com/07akioni.jpeg'
        },
        {
          name: 'Audrey Hepburn',
          src: 'https://gw.alipayobjects.com/zos/antfincdn/aPkFc8Sj7n/method-draw-image.svg'
        },
        {
          name: 'Anne Hathaway',
          src: 'https://07akioni.oss-cn-beijing.aliyuncs.com/07akioni.jpeg'
        },
        {
          name: 'Taylor Swift',
          src: 'https://gw.alipayobjects.com/zos/antfincdn/aPkFc8Sj7n/method-draw-image.svg'
        }
]);

const createDropdownOptions = computed(() => (options.map( (option) => ({
    key: option.name,
    label: option.name
}) ) ) );

</script>

<style lang="scss" scoped>

</style>
