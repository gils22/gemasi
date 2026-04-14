<script setup lang="ts">
type TabItem = {
    id: string | number;
    name: string;
    icon?: unknown;
    accent?: string;
};

const props = withDefaults(
    defineProps<{
        items: TabItem[];
        modelValue: string;
        showAll?: boolean;
        allLabel?: string;
        allId?: string;
    }>(),
    {
        showAll: false,
        allLabel: "Semua Kategori",
        allId: "semua",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

const setActive = (value: string) => {
    emit("update:modelValue", value);
};
</script>

<template>
    <aside
        class="flex gap-2 overflow-x-auto pb-2 pr-4 sm:gap-3 sm:pb-3 lg:flex-col lg:overflow-visible lg:pb-0 lg:pr-0"
    >
        <div class="flex w-max gap-2 sm:gap-3 lg:w-full lg:flex-col">
            <button
                v-if="showAll"
                type="button"
                @click="setActive(allId)"
                class="group flex min-w-[120px] items-center gap-2 rounded-lg border border-transparent px-2 py-1 text-left text-[11px] font-semibold transition sm:min-w-[170px] sm:gap-2.5 sm:px-3 sm:py-2 sm:text-[12px] lg:min-w-0"
                :class="
                    modelValue === allId
                        ? 'border border-slate-900 bg-white ring-1 ring-slate-900/10 transform-gpu [transform:perspective(600px)_rotateX(4deg)]'
                        : 'bg-transparent text-slate-600 hover:border-slate-300 hover:bg-white/70'
                "
            >
                <span
                    class="flex h-6 w-6 items-center justify-center rounded-md bg-slate-100 text-slate-600 sm:h-7 sm:w-7"
                >
                    <slot name="allIcon" />
                </span>
                <span class="leading-tight">{{ allLabel }}</span>
            </button>

            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                @click="setActive(String(item.id))"
                class="group flex min-w-[120px] items-center gap-2 rounded-lg border border-transparent px-2 py-1 text-left text-[11px] font-semibold transition sm:min-w-[170px] sm:gap-2.5 sm:px-3 sm:py-2 sm:text-[12px] lg:min-w-0"
                :class="
                    modelValue === String(item.id)
                        ? 'border border-slate-900 bg-white ring-1 ring-slate-900/10 transform-gpu [transform:perspective(600px)_rotateX(4deg)]'
                        : 'bg-transparent text-slate-600 hover:border-slate-300 hover:bg-white/70'
                "
            >
                <span
                    v-if="item.icon"
                    class="flex h-6 w-6 items-center justify-center rounded-md sm:h-7 sm:w-7"
                    :class="item.accent"
                >
                    <component
                        :is="item.icon"
                        class="h-3.5 w-3.5 sm:h-4 sm:w-4"
                    />
                </span>
                <span class="leading-tight">{{ item.name }}</span>
            </button>
        </div>
    </aside>
</template>
