<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Plus, SquarePen, Archive, RotateCcw } from "lucide-vue-next";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import EdisiFormModal from "./EdisiFormModal.vue";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    mulai_pada: string | null;
    selesai_pada: string | null;
    created_at: string;
};

const page = usePage<{ daftarEdisi: Edisi[] }>();
const daftarEdisi = computed(() => page.props.daftarEdisi ?? []);

const selected = ref<Edisi | null>(null);
const openForm = ref(false);

const columns = [
    { key: "nama", label: "Nama", sortable: true },
    { key: "tahun", label: "Tahun", sortable: true },
    { key: "status", label: "Status", sortable: true },
    { key: "mulai_pada", label: "Mulai" },
    { key: "selesai_pada", label: "Selesai" },
];

const tambahEdisi = () => {
    selected.value = null;
    openForm.value = true;
};

const editEdisi = (row: Edisi) => {
    selected.value = row;
    openForm.value = true;
};

const bukaKembaliEdisi = (row: Edisi) => {
    toast.warning(`Buka kembali ${row.nama}?`, {
        description: "Edisi arsip akan dijadikan edisi aktif kembali.",
        action: {
            label: "Ya, Buka Kembali",
            onClick: () => {
                router.post(
                    `/admin/edisi-lomba/${row.id}/aktifkan`,
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () =>
                            toast.success(`Edisi ${row.nama} aktif kembali`),
                        onError: () =>
                            toast.error("Gagal membuka kembali edisi"),
                    },
                );
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

const selesaikanEdisi = (row: Edisi) => {
    toast.warning(`Selesaikan ${row.nama}?`, {
        description: "Edisi akan dipindahkan ke arsip dan tidak aktif lagi.",
        action: {
            label: "Ya, Selesaikan",
            onClick: () => {
                router.post(
                    `/admin/edisi-lomba/${row.id}/selesaikan`,
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () =>
                            toast.success(
                                `Edisi ${row.nama} dipindahkan ke arsip`,
                            ),
                        onError: () => toast.error("Gagal menyelesaikan edisi"),
                    },
                );
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

const bulkDeleteEdisi = (ids: number[]) => {
    if (!ids.length) return;

    toast.warning("Hapus edisi terpilih?", {
        description: "Hapus akan menghapus semua data terkait edisi.",
        action: {
            label: "Ya, Hapus",
            onClick: () => {
                router.delete("/admin/edisi-lomba/bulk-delete", {
                    data: { ids, force: true },
                    preserveScroll: true,
                    onSuccess: () =>
                        toast.success("Edisi terpilih berhasil dihapus"),
                    onError: (errors) =>
                        toast.error(
                            (errors as any)?.bulk || "Gagal menghapus edisi",
                        ),
                });
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Edisi Lomba" }, () => page),
});
</script>

<template>
    <DataTable
        :columns="columns"
        :data="daftarEdisi"
        :withAction="true"
        :showBulkDelete="true"
        @bulk-delete="bulkDeleteEdisi"
    >
        <template #toolbar-right>
            <Button @click="tambahEdisi">
                <Plus class="w-4 h-4" />
                Tambah Edisi
            </Button>
        </template>

        <template #status="{ row }: { row: Edisi }">
            <Badge
                :class="
                    row.status === 'aktif'
                        ? 'bg-green-100 text-green-700'
                        : row.status === 'arsip'
                          ? 'bg-amber-100 text-amber-700'
                          : 'bg-slate-100 text-slate-700'
                "
            >
                {{ row.status }}
            </Badge>
        </template>

        <template #tahun="{ row }: { row: Edisi }">
            {{ row.tahun }}
        </template>

        <template #mulai_pada="{ row }: { row: Edisi }">
            {{
                row.mulai_pada
                    ? new Date(row.mulai_pada).toLocaleDateString("id-ID", {
                          day: "2-digit",
                          month: "long",
                          year: "numeric",
                      })
                    : "-"
            }}
        </template>

        <template #selesai_pada="{ row }: { row: Edisi }">
            {{
                row.selesai_pada
                    ? new Date(row.selesai_pada).toLocaleDateString("id-ID", {
                          day: "2-digit",
                          month: "long",
                          year: "numeric",
                      })
                    : "-"
            }}
        </template>

        <template #actions="{ row }">
            <TooltipProvider :delay-duration="150">
                <div class="flex items-center justify-end gap-1">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="hidden md:inline-flex"
                                @click="editEdisi(row)"
                            >
                                <SquarePen class="w-4 h-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Edit</TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="row.status === 'arsip'">
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="hidden md:inline-flex"
                                @click="bukaKembaliEdisi(row)"
                            >
                                <RotateCcw class="w-4 h-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Buka kembali</TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="row.status === 'aktif'">
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="hidden md:inline-flex"
                                @click="selesaikanEdisi(row)"
                            >
                                <Archive class="w-4 h-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Arsipkan</TooltipContent>
                    </Tooltip>

                    <div class="md:hidden flex flex-wrap justify-end gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="editEdisi(row)"
                        >
                            <SquarePen class="w-4 h-4" />
                            Edit
                        </Button>
                        <Button
                            v-if="row.status === 'arsip'"
                            variant="outline"
                            size="sm"
                            @click="bukaKembaliEdisi(row)"
                        >
                            <RotateCcw class="w-4 h-4" />
                            Buka Kembali
                        </Button>
                        <Button
                            v-if="row.status === 'aktif'"
                            variant="outline"
                            size="sm"
                            @click="selesaikanEdisi(row)"
                        >
                            <Archive class="w-4 h-4" />
                            Arsipkan
                        </Button>
                    </div>
                </div>
            </TooltipProvider>
        </template>
    </DataTable>

    <EdisiFormModal v-model:open="openForm" :edisi="selected" />
</template>
