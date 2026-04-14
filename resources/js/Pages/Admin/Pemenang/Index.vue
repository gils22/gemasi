<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { PageProps } from "@/types/inertia";

type Pemenang = {
    id: number;
    peringkat: number;
    nama_karya: string | null;
    nama_kategori: string | null;
    nilai_final: number | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
};

const page = usePage<
    PageProps & {
        pemenang: Pemenang[];
        gemasiAktifLabel: string;
    }
>();

const kategoriFilter = ref("all");
const data = computed(() => page.props.pemenang ?? []);

const kategoriOptions = computed(() => {
    const set = new Set<string>();
    data.value.forEach((item) => {
        if (item.nama_kategori) set.add(item.nama_kategori);
    });
    return Array.from(set);
});

const filtered = computed(() => {
    return data.value.filter((item) => {
        if (kategoriFilter.value === "all") return true;
        return item.nama_kategori === kategoriFilter.value;
    });
});

const columns = [
    { key: "peringkat", label: "Peringkat", sortable: true },
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "anggota_tim", label: "Anggota Tim" },
    { key: "nilai_final", label: "Nilai Final", sortable: true },
];

const tetapkanOtomatis = () => {
    toast.warning("Tetapkan pemenang otomatis?", {
        description: "Sistem akan memilih 3 nilai tertinggi per kategori.",
        action: {
            label: "Ya, Tetapkan",
            onClick: () => {
                router.post("/admin/pemenang/tetapkan", {}, {
                    preserveScroll: true,
                    onSuccess: () => toast.success("Pemenang berhasil ditetapkan."),
                    onError: () => toast.error("Gagal menetapkan pemenang."),
                });
            },
        },
        cancel: { label: "Batal", onClick: () => {} },
    });
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Pemenang" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <DataTable
            :columns="columns"
            :data="filtered"
            :withAction="false"
            :search-keys="['nama_karya', 'nama_kategori']"
        >
            <template #toolbar-left>
                <Select v-model="kategoriFilter">
                    <SelectTrigger class="w-44 h-10 bg-white">
                        <SelectValue placeholder="Filter Kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Kategori</SelectItem>
                        <SelectItem
                            v-for="kategori in kategoriOptions"
                            :key="kategori"
                            :value="kategori"
                        >
                            {{ kategori }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>

            <template #toolbar-right>
                <Button @click="tetapkanOtomatis">Tetapkan Otomatis</Button>
            </template>

            <template #peringkat="{ row }">
                <Badge class="bg-amber-100 text-amber-700">#{{ row.peringkat }}</Badge>
            </template>

            <template #nilai_final="{ row }">
                <span class="font-medium text-slate-800">
                    {{ row.nilai_final ?? "-" }}
                </span>
            </template>

            <template #anggota_tim="{ row }">
                <div class="space-y-1.5">
                    <div
                        v-for="(anggota, idx) in row.anggota_tim"
                        :key="`${row.id}-anggota-${idx}`"
                        class="flex items-center gap-2 text-sm text-slate-800"
                    >
                        <span class="font-medium">{{ anggota.nama ?? "-" }}</span>
                        <span class="text-xs text-slate-500">
                            {{ anggota.nim ?? "-" }}
                        </span>
                    </div>
                    <p
                        v-if="!row.anggota_tim?.length"
                        class="text-xs text-slate-500"
                    >
                        Belum ada anggota tim
                    </p>
                </div>
            </template>
        </DataTable>
    </div>
</template>
