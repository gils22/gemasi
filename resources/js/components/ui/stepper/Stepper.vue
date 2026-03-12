<script setup lang="ts">
import { cn } from "@/lib/utils";

defineProps<{
    class?: string;
}>();
</script>

<template>
    <ol
        :class="
            cn(
                'flex flex-wrap items-center gap-3 sm:gap-4',
                $props.class,
            )
        "
    >
        <slot />
    </ol>
</template>
