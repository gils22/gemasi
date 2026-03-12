<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { PageProps } from "@/types/inertia";

type RekapRow = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    jumlah_penilai: number;
    total_nilai: number | null;
    rata_rata: number | null;
};

const page = usePage<PageProps & { rekap: RekapRow[]; gemasiAktifLabel: string }>();
const kategoriFilter = ref("semua");
const rows = computed(() => page.props.rekap ?? []);

const daftarKategori = computed(() => {
    return Array.from(new Set(rows.value.map((r) => r.nama_kategori))).filter(Boolean);
});

const filteredRows = computed(() => {
    if (kategoriFilter.value === "semua") {
        return rows.value;
    }

    return rows.value.filter((r) => r.nama_kategori === kategoriFilter.value);
});

const columns = [
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "jumlah_penilai", label: "Jumlah Penilai", sortable: true },
    { key: "total_nilai", label: "Total Nilai", sortable: true },
    { key: "rata_rata", label: "Rata-rata", sortable: true },
];

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Rekap Nilai" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="hidden md:block">
            <DataTable :columns="columns" :data="filteredRows" :withAction="false">
                <template #toolbar-left>
                    <Select v-model="kategoriFilter">
                        <SelectTrigger class="w-full sm:w-56 bg-white">
                            <SelectValue placeholder="Filter kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="semua">Semua Kategori</SelectItem>
                            <SelectItem v-for="kategori in daftarKategori" :key="kategori" :value="kategori">
                                {{ kategori }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>
                <template #total_nilai="{ row }: { row: RekapRow }">
                    <span class="font-medium text-slate-800">
                        {{ row.total_nilai !== null ? row.total_nilai.toFixed(2) : "-" }}
                    </span>
                </template>
                <template #rata_rata="{ row }: { row: RekapRow }">
                    <span class="font-medium text-slate-800">
                        {{ row.rata_rata !== null ? row.rata_rata.toFixed(2) : "-" }}
                    </span>
                </template>
            </DataTable>
        </div>

        <div class="md:hidden space-y-3">
            <Select v-model="kategoriFilter">
                <SelectTrigger class="w-full bg-white">
                    <SelectValue placeholder="Filter kategori" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua Kategori</SelectItem>
                    <SelectItem v-for="kategori in daftarKategori" :key="kategori" :value="kategori">
                        {{ kategori }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <div
                v-for="row in filteredRows"
                :key="row.id"
                class="rounded-xl border border-slate-200 bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ row.nama_karya }}
                        </p>
                        <p class="text-xs text-slate-500 truncate">
                            {{ row.nama_kategori }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Total Nilai</p>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ row.total_nilai !== null ? row.total_nilai.toFixed(2) : "-" }}
                        </p>
                        <p class="text-xs text-slate-500">Rata-rata</p>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ row.rata_rata !== null ? row.rata_rata.toFixed(2) : "-" }}
                        </p>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-600">
                    Jumlah Penilai: {{ row.jumlah_penilai }}
                </div>
            </div>
        </div>
    </div>
</template>

