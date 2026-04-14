<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import DetailModal from "@/components/penjurian/DetailModal.vue";
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
import { Eye, Plus, SquarePen } from "lucide-vue-next";
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
};

type Lampiran = {
    id: number;
    nama: string;
    deskripsi: string | null;
    url: string;
};

type DetailKarya = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    peserta: {
        name: string | null;
        email: string | null;
        avatar: string | null;
    };
    anggota_tim: Array<{
        nama?: string;
        nim?: string;
        email?: string;
        peran?: string;
    }>;
    lampiran: Lampiran[];
};

const page = usePage<
    PageProps & {
        nominasi: NominasiRow[];
        gemasiAktifLabel: string;
        kategoriOptions?: string[];
    }
>();

const rows = computed(() => page.props.nominasi ?? []);
const isAdmin = computed(() => page.props.auth?.role === "admin");
const kategoriFilter = ref<string>("semua");
const daftarKategori = computed(() => page.props.kategoriOptions ?? []);
const filteredRows = computed(() => {
    if (kategoriFilter.value === "semua") return rows.value;
    return rows.value.filter(
        (row) => row.nama_kategori === kategoriFilter.value,
    );
});

const detailOpen = ref(false);
const detailLoading = ref(false);
const detailError = ref<string | null>(null);
const detailData = ref<DetailKarya | null>(null);

const openDetail = async (row: NominasiRow) => {
    detailOpen.value = true;
    detailLoading.value = true;
    detailError.value = null;
    detailData.value = null;

    try {
        const response = await fetch(row.url_detail, {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) {
            throw new Error("Gagal memuat detail karya.");
        }
        const payload = await response.json();
        detailData.value = payload.karya ?? null;
    } catch (error) {
        detailError.value =
            error instanceof Error
                ? error.message
                : "Gagal memuat detail karya.";
    } finally {
        detailLoading.value = false;
    }
};
const columns = [
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "peserta", label: "Peserta", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "status", label: "Status Nilai", sortable: true },
    { key: "total", label: "Total" },
    { key: "rata_rata", label: "Rata-rata" },
    { key: "jumlah_penilai", label: "Juri" },
];

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
                            row.sudah_dinilai
                                ? "Sudah dinilai"
                                : "Belum dinilai"
                        }}
                    </span>
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
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="text-slate-600 hover:text-slate-900"
                                        @click="openDetail(row)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Detail</TooltipContent>
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
                                            <SquarePen
                                                v-if="row.sudah_dinilai"
                                                class="h-4 w-4"
                                            />
                                            <Plus v-else class="h-4 w-4" />
                                        </a>
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{
                                        row.sudah_dinilai
                                            ? "Edit Nilai"
                                            : "Berikan Penilaian"
                                    }}
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    </TooltipProvider>
                </template>
            </DataTable>
        </div>

        <div class="md:hidden space-y-3">
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
                    <div class="grid gap-2 sm:grid-cols-2">
                        <Button
                            size="sm"
                            variant="outline"
                            class="w-full"
                            @click="openDetail(row)"
                        >
                            Lihat Detail
                        </Button>
                        <Button
                            size="sm"
                            class="w-full"
                            :variant="row.sudah_dinilai ? 'outline' : 'default'"
                            as-child
                        >
                            <a :href="row.url_nilai">
                                {{
                                    row.sudah_dinilai ? "Edit Nilai" : "Nilai"
                                }}
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <DetailModal
        v-model:open="detailOpen"
        :loading="detailLoading"
        :detail="detailData"
        :gemasi-label="page.props.gemasiAktifLabel"
    />

    <div v-if="detailError" class="sr-only">{{ detailError }}</div>
</template>

