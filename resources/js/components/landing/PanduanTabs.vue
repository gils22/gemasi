<script setup lang="ts">
import { ref, computed, watch } from "vue";
import CategorySideTabs from "@/components/landing/CategorySideTabs.vue";
import {
    Layers,
    Code2,
    Brush,
    LineChart,
    Cpu,
    Smartphone,
    Gamepad2,
    Database,
    ShieldCheck,
    ArrowRight,
} from "lucide-vue-next";

type LandingKategori = {
    id: number;
    nama: string;
    slug?: string | null;
    deskripsi?: string | null;
    weights?: Array<{ label: string; point: number }>;
};

type TemplateProposal = {
    nama?: string | null;
    url?: string | null;
} | null;

const props = defineProps<{
    categories?: LandingKategori[];
    ketentuan?: string[];
    templateProposal?: TemplateProposal;
}>();

const topTabs = [
    { id: "persyaratan", label: "Ketentuan Umum" },
    { id: "kategori", label: "Kategori Lomba" },
    { id: "template", label: "Template Proposal" },
];

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
    if (
        label.includes("ui/ux") ||
        label.includes("ui") ||
        label.includes("ux")
    ) {
        return { icon: Brush, accent: "bg-pink-100 text-pink-700" };
    }
    if (label.includes("pemrograman") || label.includes("program")) {
        return { icon: Code2, accent: "bg-slate-100 text-slate-700" };
    }
    if (label.includes("data")) {
        return { icon: Database, accent: "bg-amber-100 text-amber-700" };
    }
    if (
        label.includes("ar") ||
        label.includes("vr") ||
        label.includes("interaktif")
    ) {
        return { icon: Gamepad2, accent: "bg-violet-100 text-violet-700" };
    }
    if (label.includes("multimedia")) {
        return { icon: Smartphone, accent: "bg-cyan-100 text-cyan-700" };
    }
    return { icon: ShieldCheck, accent: "bg-slate-100 text-slate-600" };
};

const mappedCategories = computed(() => {
    const raw = props.categories ?? [];
    return raw.map((item) => {
        const meta = getCategoryMeta(item.nama);
        return {
            id: item.slug || String(item.id),
            name: item.nama,
            description: item.deskripsi ?? "",
            weights: item.weights ?? [],
            icon: meta.icon,
            accent: meta.accent,
        };
    });
});

const activeTab = ref(topTabs[0].id);
const activeIndex = ref(0);
const activeId = ref("");
const activeCategory = computed(
    () => mappedCategories.value[activeIndex.value],
);
const ketentuanUmum = computed(() => props.ketentuan ?? []);

watch(
    mappedCategories,
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

watch(activeId, (val) => {
    const idx = mappedCategories.value.findIndex(
        (item) => String(item.id) === val,
    );
    if (idx >= 0) activeIndex.value = idx;
});

watch(activeIndex, (val) => {
    const item = mappedCategories.value[val];
    if (item) activeId.value = String(item.id);
});
</script>

<template>
    <div class="mt-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
        >
            <button
                v-for="tab in topTabs"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                class="w-full rounded-full border px-4 py-2 text-sm font-semibold transition sm:w-auto"
                :class="
                    activeTab === tab.id
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white/70 text-slate-600 hover:border-slate-300'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <div
            v-if="activeTab === 'kategori'"
            class="mt-6 grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start"
        >
            <CategorySideTabs v-model="activeId" :items="mappedCategories" />

            <Transition name="fade-slide" mode="out-in">
                <div :key="activeCategory?.id" class="space-y-4">
                    <div
                        class="rounded-lg border border-slate-200 bg-white/80 p-4 sm:p-5"
                    >
                        <h3 class="text-base font-semibold text-slate-900">
                            Deskripsi
                        </h3>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ activeCategory?.description || "-" }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-white/80 p-4 sm:p-5"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900">
                                Bobot Penilaian
                            </h3>
                            <span class="text-xs font-semibold text-slate-500">
                                Poin
                            </span>
                        </div>
                        <div class="mt-4 space-y-2 text-sm text-slate-700">
                            <div
                                v-for="(row, idx) in activeCategory?.weights ??
                                []"
                                :key="`${activeCategory?.id}-weight-${idx}`"
                                class="flex items-center justify-between rounded-lg border border-slate-200 bg-white/70 px-3 py-2 sm:px-4"
                            >
                                <span class="capitalize">{{ row.label }}</span>
                                <span class="font-semibold text-slate-900">
                                    {{ row.point }}
                                </span>
                            </div>
                            <p
                                v-if="!(activeCategory?.weights ?? []).length"
                                class="text-xs text-slate-500"
                            >
                                Bobot penilaian belum tersedia.
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <div v-else-if="activeTab === 'persyaratan'" class="mt-6">
            <div
                class="rounded-lg border border-slate-200 bg-white/80 p-5 sm:p-6"
            >
                <h3 class="text-base font-semibold text-slate-900">
                    Ketentuan Umum
                </h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li
                        v-for="(item, idx) in ketentuanUmum"
                        :key="`syarat-${idx}`"
                        class="flex items-start gap-3"
                    >
                        <span class="mt-1 h-2 w-2 rounded-full bg-slate-400" />
                        <span>{{ item }}</span>
                    </li>
                </ul>
                <p
                    v-if="ketentuanUmum.length === 0"
                    class="mt-3 text-sm text-slate-500"
                >
                    Ketentuan umum belum tersedia.
                </p>
            </div>
        </div>

        <div v-else-if="activeTab === 'template'" class="mt-6">
            <div
                class="rounded-lg border border-slate-200 bg-white/80 p-5 sm:p-6"
            >
                <h3 class="text-base font-semibold text-slate-900">
                    Template Proposal
                </h3>
                <p class="mt-2 text-sm text-slate-600">
                    Unduh template proposal untuk mengirimkan karya.
                </p>
                <div class="mt-4">
                    <a
                        v-if="templateProposal?.url"
                        :href="templateProposal.url"
                        target="_blank"
                        rel="noopener"
                        class="group inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                    >
                        {{ templateProposal?.nama || "Unduh Template" }}
                        <ArrowRight
                            class="h-4 w-4 text-slate-700 -rotate-45 transition-transform duration-200 group-hover:rotate-0 group-hover:text-slate-900"
                        />
                    </a>
                    <p v-else class="text-sm text-slate-500">
                        Template proposal belum tersedia.
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="mt-6" />
    </div>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 220ms ease,
        transform 220ms ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
