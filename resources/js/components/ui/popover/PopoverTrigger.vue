<script setup lang="ts">
import type { PopoverTriggerProps } from "reka-ui";
import { PopoverTrigger, useForwardProps } from "reka-ui";

const props = defineProps<PopoverTriggerProps & { class?: string }>();
const forwarded = useForwardProps(props);
</script>

<template>
  <PopoverTrigger v-bind="forwarded" data-slot="popover-trigger">
    <slot />
  </PopoverTrigger>
</template>
