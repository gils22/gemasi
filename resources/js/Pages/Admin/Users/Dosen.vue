<script setup lang="ts">
import { computed, nextTick, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Plus, Eye, Upload } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";
import DosenFormModal from "./DosenFormModal.vue";
import DosenImportModal from "./DosenImportModal.vue";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";

type Dosen = {
    id: number;
    nik: string | null;
    nama: string;
    email: string;
    bidang: string | null;
    aktif: boolean;
    updated_at: string | null;
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Data Dosen" }, () => page),
});

const page = usePage<PageProps & { dosen: Dosen[] }>();
const dosen = computed(() => page.props.dosen ?? []);

const openModal = ref(false);
const selected = ref<Dosen | null>(null);
const openImport = ref(false);

const columns = [
    { key: "nama", label: "Nama", sortable: true },
    { key: "email", label: "Email", sortable: true },
    { key: "nik", label: "NIK" },
    { key: "bidang", label: "Bidang" },
    { key: "aktif", label: "Status" },
];

const handleCreate = () => {
    selected.value = null;
    openModal.value = true;
};

const handleView = (row: any) => {
    openModal.value = false;
    selected.value = null;
    nextTick(() => {
        selected.value = { ...row };
        openModal.value = true;
    });
};

const handleBulkDelete = (ids: number[]) => {
    if (!ids.length) return;

    toast.warning("Yakin ingin menghapus data dosen?", {
        description: `${ids.length} data dosen akan dihapus.`,
        action: {
            label: "Ya, Hapus",
            onClick: () => {
                router.delete("/admin/dosen/bulk-delete", {
                    data: { ids },
                    preserveScroll: true,
                    onSuccess: () =>
                        toast.success("Data dosen berhasil dihapus."),
                    onError: () => toast.error("Gagal menghapus data dosen."),
                });
            },
        },
        cancel: { label: "Batal", onClick: () => {} },
    });
};

const toggleAktif = (row: Dosen, value: boolean) => {
    router.patch(
        `/admin/dosen/${row.id}/toggle-aktif`,
        { aktif: value },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    `Dosen ${row.nama} ${value ? "diaktifkan" : "dinonaktifkan"}.`,
                ),
            onError: () => toast.error("Gagal mengubah status dosen."),
        },
    );
};
</script>

<template>
    <DataTable
        :columns="columns"
        :data="dosen"
        :withAction="true"
        :showBulkDelete="true"
        @bulk-delete="handleBulkDelete"
    >
        <template #toolbar-right>
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    @click="openImport = true"
                >
                    <Upload class="h-4 w-4" />
                    Import
                </Button>
                <Button @click="handleCreate">
                    <Plus class="h-4 w-4" />
                    Tambah
                </Button>
            </div>
        </template>

        <template #aktif="{ row }: { row: Dosen }">
            <div class="flex items-center gap-2">
                <Switch
                    :model-value="row.aktif"
                    @update:model-value="(val) => toggleAktif(row, val === true)"
                />
                <Badge
                    :class="
                        row.aktif
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'bg-slate-100 text-slate-600'
                    "
                >
                    {{ row.aktif ? "Aktif" : "Nonaktif" }}
                </Badge>
            </div>
        </template>

        <template #actions="{ row }">
            <div class="flex justify-end">
                <TooltipProvider :delay-duration="150">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="hidden md:inline-flex"
                                @click="handleView(row)"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent side="bottom">Detail</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <Button
                    variant="outline"
                    size="sm"
                    class="md:hidden"
                    @click="handleView(row)"
                >
                    <Eye class="h-4 w-4" />
                    Lihat
                </Button>
            </div>
        </template>
    </DataTable>

    <DosenFormModal
        v-model:open="openModal"
        :dosen="selected"
        :readonly="false"
    />
    <DosenImportModal v-model:open="openImport" :readonly="false" />
</template>
