<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Eye, CheckCircle2, RotateCcw } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type NominasiRow = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    peserta: {
        name: string | null;
        email: string | null;
        avatar: string | null;
    };
    sudah_dinilai: boolean;
    total_nilai: number | null;
    rata_rata?: number | null;
    jumlah_penilai?: number;
    url_nilai: string;
    url_detail: string;
    url_lolos?: string;
    url_batalkan?: string;
    status_label?: string;
    status_nominasi?: string;
    nilai_tahap_1?: number | null;
    is_lolos_nominasi?: boolean;
};

const page = usePage<
    PageProps & {
        nominasi: NominasiRow[];
        penilaianTahap1?: NominasiRow[];
        gemasiAktifLabel: string;
        kategoriOptions?: string[];
        stageOptions?: Array<{ value: "tahap_1" | "tahap_2"; label: string }>;
        selectedStage?: "tahap_1" | "tahap_2";
    }
>();

const rowsTahap2 = computed(() => page.props.nominasi ?? []);
const rowsTahap1 = computed(() => page.props.penilaianTahap1 ?? []);
const isAdmin = computed(() => page.props.auth?.role === "admin");
const kategoriFilter = ref<string>("semua");
const stageFilter = ref<"tahap_1" | "tahap_2">(
    page.props.selectedStage ??
        page.props.stageOptions?.[0]?.value ??
        "tahap_2",
);
const stageOptions = computed(() => page.props.stageOptions ?? []);
const currentStageLabel = computed(() => {
    const option = stageOptions.value.find(
        (item) => item.value === stageFilter.value,
    );
    return (
        option?.label ??
        (stageFilter.value === "tahap_1" ? "Tahap 1" : "Tahap 2")
    );
});
const currentRows = computed(() =>
    stageFilter.value === "tahap_1" ? rowsTahap1.value : rowsTahap2.value,
);
const daftarKategori = computed(() =>
    currentRows.value
        .map((row) => row.nama_kategori)
        .filter(
            (value, index, self) => Boolean(value) && self.indexOf(value) === index,
        ),
);
const filteredRows = computed(() => {
    if (kategoriFilter.value === "semua") return currentRows.value;
    return currentRows.value.filter(
        (row) => row.nama_kategori === kategoriFilter.value,
    );
});

const toggleNominasi = (row: NominasiRow) => {
    if (stageFilter.value !== "tahap_1") return;

    const endpoint = row.is_lolos_nominasi
        ? (row.url_batalkan ?? row.url_nilai)
        : (row.url_lolos ?? row.url_nilai);

    router.patch(
        endpoint,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(
                    row.is_lolos_nominasi
                        ? "Nominasi berhasil dibatalkan."
                        : "Karya berhasil diloloskan ke nominasi.",
                );
                router.reload({ preserveScroll: true, preserveState: true });
            },
            onError: () => {
                toast.error("Gagal memperbarui status nominasi.");
            },
        },
    );
};

const columnsTahap1 = [
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "peserta", label: "Peserta", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "status_nominasi", label: "Status Nominasi", sortable: true },
    { key: "nilai_tahap_1", label: "Nilai Tahap 1", sortable: true },
];

const columnsTahap2 = [
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "peserta", label: "Peserta", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "status", label: "Status Nilai", sortable: true },
    { key: "total", label: "Total" },
    { key: "rata_rata", label: "Rata-rata" },
    { key: "jumlah_penilai", label: "Juri" },
];

const columns = computed(() =>
    stageFilter.value === "tahap_1" ? columnsTahap1 : columnsTahap2,
);

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Penjurian Nominasi" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="hidden md:block">
            <DataTable
                :columns="columns"
                :data="filteredRows"
                :withAction="true"
                :search-keys="['nama_karya', 'nama_kategori']"
            >
                <template #toolbar-left>
                    <Select v-model="kategoriFilter">
                        <SelectTrigger class="w-44 h-10 bg-white">
                            <SelectValue placeholder="Filter Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="semua"
                                >Semua Kategori</SelectItem
                            >
                            <SelectItem
                                v-for="kategori in daftarKategori"
                                :key="kategori"
                                :value="kategori"
                            >
                                {{ kategori }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>
                <template #toolbar-right>
                    <div
                        v-if="stageOptions.length > 1"
                        class="flex items-center gap-3"
                    >
                        <p class="text-sm font-medium text-slate-600">
                            Penjurian :
                        </p>
                        <Select v-model="stageFilter">
                            <SelectTrigger class="w-40 h-10 bg-white">
                                <SelectValue placeholder="Pilih Tahap" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in stageOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>
                <template #peserta="{ row }: { row: NominasiRow }">
                    <div class="flex items-center gap-3 min-w-0">
                        <Avatar class="h-8 w-8">
                            <AvatarImage
                                :src="
                                    row.peserta.avatar ??
                                    `https://ui-avatars.com/api/?name=${encodeURIComponent(row.peserta.name ?? 'P')}&background=2563eb&color=fff`
                                "
                            />
                            <AvatarFallback>
                                {{
                                    (row.peserta.name ?? "P")
                                        .charAt(0)
                                        .toUpperCase()
                                }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-slate-800"
                            >
                                {{ row.peserta.name ?? "-" }}
                            </p>
                            <p class="truncate text-xs text-slate-500">
                                {{ row.peserta.email ?? "-" }}
                            </p>
                        </div>
                    </div>
                </template>

                <template #status="{ row }: { row: NominasiRow }">
                    <span
                        class="text-xs font-medium"
                        :class="
                            row.sudah_dinilai
                                ? 'text-emerald-600'
                                : 'text-amber-600'
                        "
                    >
                        {{
                            row.status_label ??
                            (row.sudah_dinilai
                                ? "Sudah dinilai"
                                : "Belum dinilai")
                        }}
                    </span>
                </template>

                <template #status_nominasi="{ row }: { row: NominasiRow }">
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="
                            row.status_nominasi === 'Lolos Nominasi'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-slate-100 text-slate-600'
                        "
                    >
                        {{ row.status_nominasi ?? "-" }}
                    </span>
                </template>

                <template #nilai_tahap_1="{ row }: { row: NominasiRow }">
                    <span
                        v-if="
                            row.nilai_tahap_1 !== null &&
                            row.nilai_tahap_1 !== undefined
                        "
                        class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700"
                    >
                        {{ row.nilai_tahap_1.toFixed(0) }}
                    </span>
                    <span v-else class="text-sm text-slate-400">-</span>
                </template>

                <template #total="{ row }: { row: NominasiRow }">
                    <span class="text-sm text-slate-700">
                        {{
                            row.total_nilai !== null
                                ? row.total_nilai.toFixed(2)
                                : "-"
                        }}
                    </span>
                </template>

                <template #rata_rata="{ row }: { row: NominasiRow }">
                    <span class="text-sm text-slate-700">
                        {{
                            row.rata_rata !== null &&
                            row.rata_rata !== undefined
                                ? row.rata_rata.toFixed(2)
                                : "-"
                        }}
                    </span>
                </template>

                <template #jumlah_penilai="{ row }: { row: NominasiRow }">
                    <span class="text-sm text-slate-700">
                        {{ row.jumlah_penilai ?? 0 }}
                    </span>
                </template>

                <template #actions="{ row }: { row: NominasiRow }">
                    <TooltipProvider :delay-duration="150">
                        <div class="flex items-center justify-end gap-1">
                            <template v-if="stageFilter === 'tahap_1'">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            :class="
                                                row.is_lolos_nominasi
                                                    ? 'text-amber-600 hover:text-amber-700'
                                                    : 'text-emerald-600 hover:text-emerald-700'
                                            "
                                            @click="toggleNominasi(row)"
                                        >
                                            <component
                                                :is="
                                                    row.is_lolos_nominasi
                                                        ? RotateCcw
                                                        : CheckCircle2
                                                "
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {{
                                            row.is_lolos_nominasi
                                                ? "Batalkan Nominasi"
                                                : "Loloskan Nominasi"
                                        }}
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="text-slate-600 hover:text-slate-900"
                                            as-child
                                        >
                                            <a :href="row.url_nilai">
                                                <Eye class="h-4 w-4" />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {{
                                            row.sudah_dinilai
                                                ? "Edit Nilai"
                                                : "Nilai"
                                        }}
                                    </TooltipContent>
                                </Tooltip>
                            </template>
                            <template v-else>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="text-slate-600 hover:text-slate-900"
                                            as-child
                                        >
                                            <a :href="row.url_nilai">
                                                <Eye class="h-4 w-4" />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {{
                                            row.sudah_dinilai
                                                ? "Edit Nilai"
                                                : "Nilai"
                                        }}
                                    </TooltipContent>
                                </Tooltip>
                            </template>
                        </div>
                    </TooltipProvider>
                </template>
            </DataTable>
        </div>

        <div class="md:hidden space-y-3">
            <Select v-if="stageOptions.length > 1" v-model="stageFilter">
                <SelectTrigger class="w-full h-10 bg-white">
                    <SelectValue placeholder="Pilih Tahap" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in stageOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="kategoriFilter">
                <SelectTrigger class="w-full h-10 bg-white">
                    <SelectValue placeholder="Filter Kategori" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua Kategori</SelectItem>
                    <SelectItem
                        v-for="kategori in daftarKategori"
                        :key="kategori"
                        :value="kategori"
                    >
                        {{ kategori }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <div
                v-for="row in filteredRows"
                :key="row.id"
                class="rounded-xl border border-slate-200 bg-white p-4 w-full"
            >
                <div class="flex items-start gap-3">
                    <Avatar class="h-9 w-9">
                        <AvatarImage
                            :src="
                                row.peserta.avatar ??
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(row.peserta.name ?? 'P')}&background=2563eb&color=fff`
                            "
                        />
                        <AvatarFallback>
                            {{
                                (row.peserta.name ?? "P")
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm font-semibold text-slate-900 wrap-break-word"
                        >
                            {{ row.nama_karya }}
                        </p>
                        <p class="text-xs text-slate-500 wrap-break-word">
                            {{ row.nama_kategori }}
                        </p>
                        <p class="text-xs text-slate-500 wrap-break-word">
                            {{ row.peserta.name ?? "-" }} ·
                            {{ row.peserta.email ?? "-" }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-3 flex flex-wrap items-center justify-between gap-2"
                >
                    <span
                        class="text-xs font-medium"
                        :class="
                            row.sudah_dinilai
                                ? 'text-emerald-600'
                                : 'text-amber-600'
                        "
                    >
                        {{
                            row.sudah_dinilai
                                ? "Sudah dinilai"
                                : "Belum dinilai"
                        }}
                    </span>
                    <span class="text-xs text-slate-600">
                        Total:
                        {{
                            row.total_nilai !== null
                                ? row.total_nilai.toFixed(2)
                                : "-"
                        }}
                    </span>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <span class="truncate"
                        >Rata-rata:
                        {{
                            row.rata_rata !== null &&
                            row.rata_rata !== undefined
                                ? row.rata_rata.toFixed(2)
                                : "-"
                        }}</span
                    >
                    <span class="truncate text-right"
                        >Penilai: {{ row.jumlah_penilai ?? 0 }}</span
                    >
                </div>

                <div class="mt-3">
                    <div
                        class="grid gap-2"
                        :class="
                            stageFilter === 'tahap_1'
                                ? 'sm:grid-cols-2'
                                : 'sm:grid-cols-1'
                        "
                    >
                        <Button
                            v-if="stageFilter === 'tahap_1'"
                            size="sm"
                            class="w-full"
                            variant="outline"
                            @click="toggleNominasi(row)"
                        >
                            <component
                                :is="
                                    row.is_lolos_nominasi
                                        ? RotateCcw
                                        : CheckCircle2
                                "
                                class="mr-2 h-4 w-4"
                            />
                            {{
                                row.is_lolos_nominasi ? "Batalkan" : "Loloskan"
                            }}
                        </Button>
                        <Button
                            size="sm"
                            class="w-full"
                            :variant="row.sudah_dinilai ? 'outline' : 'default'"
                            as-child
                        >
                            <a :href="row.url_nilai">
                                {{ row.sudah_dinilai ? "Edit Nilai" : "Nilai" }}
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
