<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { BadgeVariants } from "."
import { cn } from "@/lib/utils"
import { badgeVariants } from "."

const props = defineProps<{
  variant?: BadgeVariants["variant"]
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <span
    data-slot="badge"
    :class="cn(badgeVariants({ variant }), $attrs.class)"
    v-bind="$attrs"
  >
    <slot />
  </span>
</template>
