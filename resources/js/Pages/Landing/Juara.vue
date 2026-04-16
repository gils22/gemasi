<script setup lang="ts">
import { computed, ref, watch, nextTick, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import Confetti from "@/components/ui/confetti/Confetti.vue";
import { Search, Eye } from "lucide-vue-next";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
};

type Pemenang = {
    id?: number;
    peringkat: number;
    kategori: string | null;
    nama_karya: string | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
    deskripsi?: string | null;
    logo_url?: string | null;
    video_url?: string | null;
};

const page = usePage<{
    daftarEdisi: Edisi[];
    pemenangPerEdisi: Record<number, Pemenang[]>;
}>();

const daftarEdisi = computed(() => page.props.daftarEdisi ?? []);
const pemenangPerEdisi = computed(() => page.props.pemenangPerEdisi ?? {});

const edisiDenganPemenang = computed(() =>
    daftarEdisi.value
        .map((edisi) => ({
            ...edisi,
            pemenang: pemenangPerEdisi.value[edisi.id] ?? [],
        }))
        .filter((edisi) => edisi.pemenang.length > 0),
);

const activeEdisiId = ref<number | null>(null);
const activeKategori = ref<string>("Semua Kategori");
const searchQuery = ref("");
const showWinners = ref(true);
const confettiRef = ref<{
    fire: (options?: Record<string, unknown>) => void;
} | null>(null);

const activeEdisiValue = computed({
    get: () => (activeEdisiId.value ? String(activeEdisiId.value) : ""),
    set: (value: string) => {
        activeEdisiId.value = value ? Number(value) : null;
    },
});

const activeEdisi = computed(
    () =>
        edisiDenganPemenang.value.find(
            (edisi) => edisi.id === activeEdisiId.value,
        ) ??
        edisiDenganPemenang.value[0] ??
        null,
);

const kategoriOptions = computed(() => {
    const current = activeEdisi.value?.pemenang ?? [];
    const set = new Set<string>(current.map((row) => row.kategori ?? "-"));
    return ["Semua Kategori", ...Array.from(set)];
});

const activeKategoriValue = computed({
    get: () => activeKategori.value,
    set: (value: string) => {
        activeKategori.value = value || "Semua Kategori";
    },
});

const filteredPemenang = computed(() => {
    if (!activeEdisi.value) return [];
    let rows = activeEdisi.value.pemenang;
    if (activeKategori.value !== "Semua Kategori") {
        rows = rows.filter(
            (row) => (row.kategori ?? "-") === activeKategori.value,
        );
    }
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return rows;
    return rows.filter((row) => {
        const title = (row.nama_karya ?? "").toLowerCase();
        const team =
            row.anggota_tim
                ?.map((a) => a.nama ?? "")
                .join(" ")
                .toLowerCase() ?? "";
        const kategori = (row.kategori ?? "").toLowerCase();
        return title.includes(q) || team.includes(q) || kategori.includes(q);
    });
});

watch(
    edisiDenganPemenang,
    (items) => {
        if (!items.length) {
            activeEdisiId.value = null;
            return;
        }
        if (
            !activeEdisiId.value ||
            !items.some((item) => item.id === activeEdisiId.value)
        ) {
            activeEdisiId.value = items[0].id;
        }
    },
    { immediate: true },
);

const getWinnerId = (row: Pemenang, index: number) =>
    row.id ? String(row.id) : String(index + 1);

const fireSideCannons = () => {
    const base = {
        particleCount: 80,
        spread: 60,
        startVelocity: 45,
        decay: 0.9,
        scalar: 0.9,
    };

    confettiRef.value?.fire({
        ...base,
        angle: 60,
        origin: { x: 0.05, y: 0.75 },
    });
    confettiRef.value?.fire({
        ...base,
        angle: 120,
        origin: { x: 0.95, y: 0.75 },
    });
};

onMounted(async () => {
    await nextTick();
    fireSideCannons();
});

watch(
    () => activeEdisiId.value,
    async (value, prev) => {
        if (!showWinners.value || value === prev) return;
        await nextTick();
        fireSideCannons();
    },
);

watch(
    () => activeKategori.value,
    async (value, prev) => {
        if (!showWinners.value || value === prev) return;
        await nextTick();
        fireSideCannons();
    },
);

</script>

<template>
    <LandingLayout>
        <section
            class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-100"
        >
            <Confetti
                ref="confettiRef"
                manualstart
                class="pointer-events-none fixed inset-0 z-0 h-screen w-screen"
            />
            <div
                class="relative z-10 mx-auto w-full max-w-6xl px-6 py-16 mt-6 min-h-screen"
            >
                <div class="space-y-2 text-center">
                    <transition name="fade-slide">
                        <div class="space-y-2" key="juara-header">
                            <h1 class="text-3xl font-semibold text-slate-900">
                                Juara GEMASI
                            </h1>
                            <p class="mx-auto max-w-2xl text-sm text-slate-600">
                                Rekap juara setiap tahun.
                            </p>
                        </div>
                    </transition>
                </div>

                <div
                    v-if="showWinners && edisiDenganPemenang.length"
                    class="mt-6 flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:gap-4"
                >
                        <div class="w-full sm:w-[200px]">
                            <Select v-model="activeEdisiValue">
                                <SelectTrigger
                                    class="w-full rounded-lg border-slate-200 bg-white/80 text-sm font-semibold text-slate-900"
                                >
                                    <SelectValue placeholder="Pilih Tahun" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="edisi in edisiDenganPemenang"
                                        :key="edisi.id"
                                        :value="String(edisi.id)"
                                    >
                                        {{ edisi.tahun }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="w-full sm:w-[240px]">
                            <Select v-model="activeKategoriValue">
                                <SelectTrigger
                                    class="w-full rounded-lg border-slate-200 bg-white/80 text-sm font-semibold text-slate-900"
                                >
                                    <SelectValue placeholder="Semua Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="kategori in kategoriOptions"
                                        :key="kategori"
                                        :value="kategori"
                                    >
                                        {{ kategori }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="w-full sm:ml-auto sm:w-[320px]">
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                />
                                <input
                                    v-model="searchQuery"
                                    type="search"
                                    placeholder="Cari nama atau tim..."
                                    class="w-full rounded-lg border border-slate-200 bg-white/80 py-2 pl-9 pr-3 text-sm font-semibold text-slate-900 transition focus:border-slate-400 focus:outline-none"
                                />
                            </div>
                        </div>
                </div>

                <div
                    v-if="!edisiDenganPemenang.length"
                    class="text-sm text-slate-500"
                >
                    Belum ada data pemenang yang dipublikasikan.
                </div>

                <transition name="fade-slide">
                    <div v-if="showWinners && activeEdisi" class="space-y-4">
                        <div
                            class="flex items-center justify-between flex-wrap gap-2 mt-4"
                        >
                            <h2 class="text-lg font-semibold text-slate-900">
                                {{ activeEdisi.nama }} ({{ activeEdisi.tahun }})
                            </h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <Card
                                v-for="(row, idx) in filteredPemenang"
                                :key="`${activeEdisi.id}-${idx}`"
                            class="relative border-slate-200 rounded-lg"
                        >
                            <CardHeader class="pb-2 space-y-3">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <Badge
                                            class="bg-amber-100 text-amber-700"
                                        >
                                            Juara {{ row.peringkat }}
                                        </Badge>
                                        <span
                                            v-if="row.kategori"
                                            class="text-xs text-slate-500"
                                        >
                                            {{ row.kategori }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-md border border-slate-200 bg-white text-xs font-semibold text-slate-400"
                                    >
                                            <img
                                                v-if="row.logo_url"
                                                :src="row.logo_url"
                                                alt="Logo karya"
                                                class="h-full w-full rounded-md object-contain p-1"
                                            />
                                            <span v-else>
                                                {{
                                                    (row.nama_karya ?? "GK")
                                                        .slice(0, 2)
                                                        .toUpperCase()
                                                }}
                                            </span>
                                        </div>
                                        <div>
                                            <CardTitle
                                                class="text-base text-slate-900"
                                            >
                                                {{ row.nama_karya ?? "-" }}
                                            </CardTitle>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-2 pb-12">
                                    <div v-if="row.anggota_tim?.length">
                                        <p class="text-xs text-slate-500">
                                            Anggota Tim
                                        </p>
                                        <div class="mt-1 space-y-1">
                                            <div
                                                v-for="(
                                                    anggota, aidx
                                                ) in row.anggota_tim"
                                                :key="`anggota-${aidx}`"
                                                class="flex items-center justify-between text-sm text-slate-800"
                                            >
                                                <span class="font-medium">
                                                    {{ anggota.nama ?? "-" }}
                                                </span>
                                                <span
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{ anggota.nim ?? "-" }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                                <div class="absolute bottom-4 right-4">
                                    <TooltipProvider :delay-duration="150">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Link
                                                    :href="`/juara/${getWinnerId(row, idx)}`"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </TooltipTrigger>
                                            <TooltipContent side="top">
                                                Lihat Detail
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </Card>
                        </div>
                    </div>
                </transition>
            </div>
        </section>
    </LandingLayout>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 300ms ease,
        transform 300ms ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(12px);
}
</style>
