<template>
  <Icon :icon="icon" v-bind="$attrs" />
</template>

<script>
import { Icon } from '@iconify/vue'

export default {
  name: 'IconWrapper',
  components: {
    Icon,
  },
  props: {
    icon: {
      type: String,
      required: true,
    },
  },
}
</script>
