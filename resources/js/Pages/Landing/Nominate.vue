<script setup lang="ts">
import { ref, computed, watch } from "vue";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import CategorySideTabs from "@/components/landing/CategorySideTabs.vue";
import { Award } from "lucide-vue-next";
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
    icon_url?: string | null;
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

type TimelinePengumuman = {
    judul: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
};

const props = defineProps<{
    daftarEdisi?: EdisiItem[];
    edisiDefault?: number | null;
    kategoriOptions?: KategoriItem[];
    nominasi?: NominasiItem[];
    timelinePengumumanPerEdisi?: Record<number, TimelinePengumuman | null>;
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

const selectedTimelinePengumuman = computed(() => {
    const edisiId = Number(selectedEdisi.value);
    return props.timelinePengumumanPerEdisi?.[edisiId] ?? null;
});

const nominasiAktif = computed(() => {
    const timeline = selectedTimelinePengumuman.value;
    if (!timeline) return false;
    if (timeline.is_tba) return true;
    if (!timeline.mulai_pada) return true;

    const parsed = new Date(timeline.mulai_pada);
    if (Number.isNaN(parsed.getTime())) return false;

    const now = new Date();
    return now >= parsed;
});

const kategoriByEdisi = computed(() => {
    const edisiId = Number(selectedEdisi.value);
    return (props.kategoriOptions ?? [])
        .filter((item) => item.edisi_lomba_id === edisiId)
        .map((item) => ({
            ...item,
            name: item.nama,
            icon: item.icon_url ?? null,
            accent: "bg-slate-100 text-slate-600",
        }));
});

watch(
    kategoriByEdisi,
    (items) => {
        const exists = items.some(
            (item) => String(item.id) === selectedKategori.value,
        );
        if (!exists) {
            selectedKategori.value = items[0] ? String(items[0].id) : "";
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

const formatDateOnly = (value?: string | null) => {
    if (!value) return "TBA";

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return "TBA";

    return parsed.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    });
};

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
                        <SelectTrigger
                            class="h-10 font-semibold w-full sm:w-[220px]"
                        >
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

                <div class="mt-6">
                    <div
                        v-if="!nominasiAktif"
                        class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900"
                    >
                        <p class="text-base font-semibold">
                            Pengumuman nominasi belum aktif
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-amber-800">
                            Daftar nominasi akan tampil otomatis ketika timeline
                            sudah masuk
                            <span class="font-semibold">
                                {{
                                    selectedTimelinePengumuman?.judul ??
                                    "Pengumuman Nominasi"
                                }}
                            </span>
                            .
                        </p>
                        <p
                            v-if="
                                selectedTimelinePengumuman?.mulai_pada ||
                                selectedTimelinePengumuman?.selesai_pada
                            "
                            class="mt-2 text-sm text-amber-700"
                        >
                            Mulai:
                            {{
                                formatDateOnly(
                                    selectedTimelinePengumuman?.mulai_pada,
                                )
                            }}
                        </p>
                    </div>

                    <div
                        v-else
                        class="grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start"
                    >
                        <CategorySideTabs
                            v-model="selectedKategori"
                            :items="kategoriByEdisi"
                        />

                        <div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
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

                            <div
                                v-if="nominasiFiltered.length === 0"
                                class="mt-4"
                            >
                                <div
                                    class="rounded-lg border border-dashed border-slate-200 bg-white/70 p-6 text-sm text-slate-500"
                                >
                                    Belum ada karya nominasi.
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
                                                        {{
                                                            anggota.nama ?? "-"
                                                        }}
                                                    </span>
                                                    <span
                                                        class="text-slate-500"
                                                    >
                                                        {{ anggota.nim ?? "-" }}
                                                    </span>
                                                </p>
                                                <p
                                                    v-if="
                                                        !(
                                                            item.anggota_tim ??
                                                            []
                                                        ).length
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
