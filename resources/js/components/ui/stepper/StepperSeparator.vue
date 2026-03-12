<script setup lang="ts">
import { cn } from "@/lib/utils";

defineProps<{
    class?: string;
}>();
</script>

<template>
    <div
        :class="
            cn(
                'hidden h-px flex-1 bg-slate-200 sm:block',
                $props.class,
            )
        "
        aria-hidden="true"
    />
</template>
