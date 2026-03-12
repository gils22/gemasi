<script setup lang="ts">
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps<{
    step: number | string;
    active?: boolean;
    completed?: boolean;
    class?: string;
}>();

const stateClass = computed(() => {
    if (props.active) {
        return {
            wrapper: "text-slate-900",
            indicator:
                "bg-slate-900 text-white border-slate-900 shadow-sm",
        };
    }
    if (props.completed) {
        return {
            wrapper: "text-slate-900",
            indicator:
                "bg-slate-100 text-slate-700 border-slate-200",
        };
    }
    return {
        wrapper: "text-slate-400",
        indicator:
            "bg-white text-slate-400 border-slate-200",
    };
});
</script>

<template>
    <li
        :class="
            cn(
                'flex flex-col items-center gap-2 text-center min-w-[120px]',
                $props.class,
            )
        "
    >
        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-full border text-sm font-semibold transition"
            :class="stateClass.indicator"
        >
            <slot name="icon">
                {{ step }}
            </slot>
        </button>
        <div :class="cn('space-y-0.5', stateClass.wrapper)">
            <div class="text-sm font-semibold">
                <slot name="title" />
            </div>
            <div class="text-xs text-slate-500">
                <slot name="description" />
            </div>
        </div>
    </li>
</template>
