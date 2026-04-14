<script setup lang="ts">
import { computed, ref, watch } from "vue";
import CategorySideTabs from "@/components/landing/CategorySideTabs.vue";
import {
    Layers,
    LineChart,
    Cpu,
    Brush,
    Code2,
    Smartphone,
    Gamepad2,
    Database,
    ShieldCheck,
} from "lucide-vue-next";

type WeightItem = {
    label: string;
    point: number;
};

type CategoryItem = {
    id: string;
    name: string;
    description: string;
    accent?: string;
    icon?: unknown;
    weights?: WeightItem[];
};

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        categories: CategoryItem[];
        showWeights?: boolean;
        contentTitle?: string;
    }>(),
    {
        subtitle: "",
        showWeights: true,
        contentTitle: "",
    },
);

const activeIndex = ref(0);
const activeId = ref("");

const getCategoryMeta = (name: string) => {
    const label = name.toLowerCase();
    if (label.includes("fintech") || label.includes("bisnis")) {
        return { icon: Layers, accent: "bg-emerald-100 text-emerald-700" };
    }
    if (label.includes("business plan") || label.includes("plan")) {
        return { icon: LineChart, accent: "bg-blue-100 text-blue-700" };
    }
    if (label.includes("sistem informasi") || label.includes("aplikasi")) {
        return { icon: Cpu, accent: "bg-indigo-100 text-indigo-700" };
    }
    if (label.includes("ui/ux") || label.includes("ui") || label.includes("ux")) {
        return { icon: Brush, accent: "bg-pink-100 text-pink-700" };
    }
    if (label.includes("pemrograman") || label.includes("program")) {
        return { icon: Code2, accent: "bg-slate-100 text-slate-700" };
    }
    if (label.includes("data")) {
        return { icon: Database, accent: "bg-amber-100 text-amber-700" };
    }
    if (label.includes("ar") || label.includes("vr") || label.includes("interaktif")) {
        return { icon: Gamepad2, accent: "bg-violet-100 text-violet-700" };
    }
    if (label.includes("multimedia")) {
        return { icon: Smartphone, accent: "bg-cyan-100 text-cyan-700" };
    }
    return { icon: ShieldCheck, accent: "bg-slate-100 text-slate-600" };
};

const categoriesWithIcons = computed(() =>
    props.categories.map((cat) => {
        const meta = getCategoryMeta(cat.name);
        return {
            ...cat,
            icon: cat.icon ?? meta.icon,
            accent: cat.accent ?? meta.accent,
        };
    }),
);

const activeCategory = computed(
    () => categoriesWithIcons.value[activeIndex.value],
);
watch(activeIndex, (val) => {
    const item = categoriesWithIcons.value[val];
    if (item) activeId.value = String(item.id);
});

watch(
    categoriesWithIcons,
    (items) => {
        if (!items.length) return;
        if (!activeId.value) {
            activeId.value = String(items[0].id);
            activeIndex.value = 0;
            return;
        }
        const idx = items.findIndex(
            (item) => String(item.id) === activeId.value,
        );
        activeIndex.value = idx >= 0 ? idx : 0;
    },
    { immediate: true },
);

watch(
    activeId,
    (val) => {
        const idx = categoriesWithIcons.value.findIndex(
            (item) => String(item.id) === val,
        );
        if (idx >= 0) activeIndex.value = idx;
    },
);
</script>

<template>
    <section class="bg-transparent">
        <div class="mx-auto w-full max-w-6xl px-6 py-16">
            <div class="text-center">
                <h2 class="text-2xl font-semibold text-slate-900">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="mt-2 text-sm text-slate-600">
                    {{ subtitle }}
                </p>
            </div>

            <div
                class="mt-8 grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start"
            >
                <CategorySideTabs
                    v-model="activeId"
                    :items="categoriesWithIcons"
                />

                <Transition name="fade-slide" mode="out-in">
                    <div :key="activeCategory?.id" class="space-y-4">
                        <slot
                            name="content"
                            :category="activeCategory"
                            :index="activeIndex"
                        >
                            <div
                                class="rounded-lg border border-slate-200 bg-white/80 p-4 sm:p-5"
                            >
                                <h3 class="text-base font-semibold text-slate-900">
                                    {{ contentTitle || "Deskripsi" }}
                                </h3>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ activeCategory?.description }}
                                </p>
                            </div>

                            <div
                                v-if="showWeights && activeCategory?.weights?.length"
                                class="rounded-lg border border-slate-200 bg-white/80 p-4 sm:p-5"
                            >
                                <div class="flex items-center justify-between">
                                    <h3
                                        class="text-base font-semibold text-slate-900"
                                    >
                                        Bobot Penilaian
                                    </h3>
                                    <span
                                        class="text-xs font-semibold text-slate-500"
                                    >
                                        Poin
                                    </span>
                                </div>
                                <div class="mt-4 space-y-2 text-sm text-slate-700">
                                    <div
                                        v-for="(row, idx) in activeCategory.weights"
                                        :key="`${activeCategory.id}-weight-${idx}`"
                                        class="flex items-center justify-between rounded-lg border border-slate-200 bg-white/70 px-3 py-2 sm:px-4"
                                    >
                                        <span class="capitalize">
                                            {{ row.label }}
                                        </span>
                                        <span class="font-semibold text-slate-900">
                                            {{ row.point }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </slot>
                    </div>
                </Transition>
            </div>
        </div>
    </section>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
