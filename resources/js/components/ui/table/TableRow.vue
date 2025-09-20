<template>
  <tr
    :class="cn(
      'border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted',
      $attrs.class
    )"
    v-bind="$attrs"
  >
    <slot />
  </tr>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils'

defineOptions({
  inheritAttrs: false
})
</script>