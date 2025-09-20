<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { type ButtonVariants, buttonVariants } from '.'

interface Props {
  variant?: ButtonVariants['variant']
  size?: ButtonVariants['size']
  class?: HTMLAttributes['class']
  asChild?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'default',
  asChild: false,
})
</script>

<template>
  <component
    :is="asChild ? 'div' : 'button'"
    :class="cn(buttonVariants({ variant, size }), $attrs.class)"
    v-bind="$attrs"
  >
    <slot />
  </component>
</template>
