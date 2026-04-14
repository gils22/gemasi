<script setup lang="ts">
import { ref, computed, watch } from "vue";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import CategorySideTabs from "@/components/landing/CategorySideTabs.vue";
import {
    Award,
    Layers,
    LineChart,
    Cpu,
    Brush,
    Code2,
    Gamepad2,
    Smartphone,
    Database,
    ShieldCheck,
} from "lucide-vue-next";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

type EdisiItem = {
    id: number;
    nama: string;
    tahun: number;
};

type KategoriItem = {
    id: number;
    nama: string;
    edisi_lomba_id: number;
    icon?: unknown;
    accent?: string;
};

type NominasiItem = {
    id: number;
    edisi_id: number;
    tahun?: number | null;
    nama_edisi?: string | null;
    kategori_id: number;
    kategori?: string | null;
    nama_karya: string;
    ringkasan?: string | null;
    anggota_tim?: Array<{ nama?: string | null; nim?: string | null }>;
};

const props = defineProps<{
    daftarEdisi?: EdisiItem[];
    edisiDefault?: number | null;
    kategoriOptions?: KategoriItem[];
    nominasi?: NominasiItem[];
}>();

const selectedEdisi = ref<string>(
    props.edisiDefault ? String(props.edisiDefault) : "",
);
const selectedKategori = ref<string>("");

watch(
    () => props.edisiDefault,
    (val) => {
        if (val) selectedEdisi.value = String(val);
    },
    { immediate: true },
);

const edisiOptions = computed(() => props.daftarEdisi ?? []);

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

const kategoriByEdisi = computed(() => {
    const edisiId = Number(selectedEdisi.value);
    return (props.kategoriOptions ?? [])
        .filter((item) => item.edisi_lomba_id === edisiId)
        .map((item) => {
            const meta = getCategoryMeta(item.nama);
            return {
                ...item,
                name: item.nama,
                icon: meta.icon,
                accent: meta.accent,
            };
        });
});

watch(
    kategoriByEdisi,
    (items) => {
        const exists = items.some(
            (item) => String(item.id) === selectedKategori.value,
        );
        if (!exists) {
            selectedKategori.value = items[0]
                ? String(items[0].id)
                : "";
        }
    },
    { immediate: true },
);

const nominasiFiltered = computed(() => {
    const edisiId = Number(selectedEdisi.value);
    let data = (props.nominasi ?? []).filter(
        (item) => item.edisi_id === edisiId,
    );
    if (selectedKategori.value) {
        const kategoriId = Number(selectedKategori.value);
        data = data.filter((item) => item.kategori_id === kategoriId);
    }
    return data;
});

const edisiLabel = computed(() => {
    const edisi = edisiOptions.value.find(
        (item) => String(item.id) === selectedEdisi.value,
    );
    if (!edisi) return "-";
    return `${edisi.nama} (${edisi.tahun})`;
});

const kategoriLabel = (item: NominasiItem) => {
    if (item.kategori) return item.kategori;
    const found = (props.kategoriOptions ?? []).find(
        (row) => row.id === item.kategori_id,
    );
    return found?.nama ?? "-";
};
</script>

<template>
    <LandingLayout>
        <section class="bg-gradient-to-b from-slate-50 via-white to-slate-100">
            <div class="mx-auto w-full max-w-6xl px-6 py-16 mt-6">
                <div class="max-w-3xl space-y-4">
                    <h1 class="text-3xl font-semibold text-slate-900">
                        Nominasi Karya Terbaik GEMASI
                    </h1>
                    <p class="text-sm text-slate-600">
                        Tahap nominasi menentukan karya yang melaju ke penjurian
                        akhir.
                    </p>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <Select v-model="selectedEdisi">
                        <SelectTrigger class="h-10 w-full sm:w-[220px]">
                            <SelectValue placeholder="Pilih Tahun" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="edisi in edisiOptions"
                                :key="edisi.id"
                                :value="String(edisi.id)"
                            >
                                {{ edisi.tahun }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div
                    class="mt-6 grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start"
                >
                    <CategorySideTabs
                        v-model="selectedKategori"
                        :items="kategoriByEdisi"
                    />

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    Karya yang Masuk Nominasi
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ edisiLabel }}
                                </p>
                            </div>
                            <span
                                class="text-xs font-semibold uppercase text-slate-400"
                            >
                                {{ nominasiFiltered.length }} Karya
                            </span>
                        </div>

                        <div v-if="nominasiFiltered.length === 0" class="mt-4">
                            <div
                                class="rounded-lg border border-dashed border-slate-200 bg-white/70 p-6 text-sm text-slate-500"
                            >
                                Belum ada karya nominasi untuk filter ini.
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-4 grid max-h-[520px] gap-4 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="item in nominasiFiltered"
                                :key="item.id"
                                class="rounded-lg border border-slate-200 bg-white/80 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-semibold uppercase text-slate-400"
                                        >
                                            {{ kategoriLabel(item) }}
                                        </p>
                                        <h3
                                            class="mt-2 text-base font-semibold text-slate-900"
                                        >
                                            {{ item.nama_karya }}
                                        </h3>
                                        <div
                                            class="mt-2 space-y-1 text-xs text-slate-600"
                                        >
                                            <p
                                                v-for="(
                                                    anggota, idx
                                                ) in item.anggota_tim ?? []"
                                                :key="`anggota-${item.id}-${idx}`"
                                                class="flex items-center justify-between gap-2"
                                            >
                                                <span
                                                    class="font-semibold text-slate-700"
                                                >
                                                    {{ anggota.nama ?? "-" }}
                                                </span>
                                                <span class="text-slate-500">
                                                    {{ anggota.nim ?? "-" }}
                                                </span>
                                            </p>
                                            <p
                                                v-if="
                                                    !(item.anggota_tim ?? [])
                                                        .length
                                                "
                                                class="text-slate-500"
                                            >
                                                Tim belum tersedia.
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/5 text-slate-600"
                                    >
                                        <Award class="h-5 w-5" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </LandingLayout>
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
