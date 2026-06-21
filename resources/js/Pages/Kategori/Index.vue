<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Eye, Plus } from "lucide-vue-next";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import FormModal from "./FormModal.vue";

type Kategori = {
    id: number;
    nama: string;
    slug: string;
    deskripsi: string | null;
    aktif: boolean;
    created_at: string;
    icon_url: string | null;
};

type EdisiAktif = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    aktif: boolean;
};

const page = usePage<{
    kategori: Kategori[];
    edisiAktif: EdisiAktif;
    modeArsip: boolean;
    isEditable: boolean;
    isAdmin: boolean;
    basePath: string;
}>();

const kategori = computed(() => page.props.kategori ?? []);
const edisiAktif = computed(() => page.props.edisiAktif);
const modeArsip = computed(() => page.props.modeArsip === true);
const isEditable = computed(() => page.props.isEditable === true);
const isAdmin = computed(() => page.props.isAdmin === true);
const basePath = computed(() => page.props.basePath || "/admin");
const canCreate = computed(() => isAdmin.value && isEditable.value);
const canEdit = computed(() => isEditable.value);
const canDelete = computed(() => isAdmin.value && isEditable.value);

const openForm = ref(false);
const selectedKategori = ref<Kategori | null>(null);

const columns = [
    { key: "icon", label: "Icon" },
    { key: "nama", label: "Nama", sortable: true },
    { key: "deskripsi", label: "Deskripsi" },
    { key: "aktif", label: "Status" },
];

const tambahKategori = () => {
    selectedKategori.value = null;
    openForm.value = true;
};

const editKategori = (row: Kategori) => {
    selectedKategori.value = row;
    openForm.value = true;
};

const handleBulkDelete = (ids: number[]) => {
    if (!canDelete.value || ids.length === 0) return;

    toast.warning(`Hapus ${ids.length} kategori terpilih?`, {
        description: "Data kategori akan dihapus permanen.",
        action: {
            label: "Ya, Hapus",
            onClick: () => {
                router.delete(`${basePath.value}/kategori/bulk-delete`, {
                    data: { ids },
                    preserveScroll: true,
                    onSuccess: () => toast.success("Kategori berhasil dihapus"),
                    onError: () => toast.error("Gagal menghapus kategori"),
                });
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

const toggleKategoriAktif = (row: Kategori, value: boolean) => {
    if (!canEdit.value) return;

    router.patch(
        `${basePath.value}/kategori/${row.id}/toggle-aktif`,
        {
            aktif: value,
        },
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.success(
                    `Kategori ${row.nama} ${value ? "diaktifkan" : "dinonaktifkan"}`,
                ),
            onError: () => toast.error("Gagal mengubah status kategori"),
        },
    );
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Kategori" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <DataTable
            :columns="columns"
            :data="kategori"
            :withAction="true"
            :showBulkDelete="canDelete"
            @bulk-delete="handleBulkDelete"
        >
            <template #toolbar-right>
                <Button v-if="canCreate" @click="tambahKategori">
                    <Plus class="w-4 h-4" />
                    Tambah Kategori
                </Button>
            </template>

            <template #icon="{ row }: { row: Kategori }">
                <div
                    class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50"
                >
                    <img
                        v-if="row.icon_url"
                        :src="row.icon_url"
                        alt="Icon kategori"
                        class="h-full w-full object-cover"
                    />
                    <span v-else class="text-xs text-slate-400">-</span>
                </div>
            </template>

            <template #deskripsi="{ row }: { row: Kategori }">
                <span
                    class="block max-w-[320px] truncate text-sm text-slate-600 sm:max-w-[420px] lg:max-w-[520px]"
                    :title="row.deskripsi || '-'"
                >
                    {{ row.deskripsi || "-" }}
                </span>
            </template>

            <template #aktif="{ row }: { row: Kategori }">
                <div class="flex items-center gap-2">
                    <Switch
                        :model-value="row.aktif"
                        :disabled="!canEdit"
                        @update:model-value="
                            (val) => toggleKategoriAktif(row, val === true)
                        "
                    />
                    <Badge
                        :class="
                            row.aktif
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-slate-100 text-slate-700'
                        "
                    >
                        {{ row.aktif ? "aktif" : "nonaktif" }}
                    </Badge>
                </div>
            </template>

            <template #actions="{ row }: { row: Kategori }">
                <div class="flex items-center justify-end gap-1">
                    <TooltipProvider :delay-duration="150">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    class="hidden md:inline-flex"
                                    :disabled="!canEdit"
                                    @click="editKategori(row)"
                                >
                                    <Eye class="w-4 h-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="bottom">
                                Detail
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    <Button
                        variant="outline"
                        size="sm"
                        class="md:hidden"
                        :disabled="!canEdit"
                        @click="editKategori(row)"
                    >
                        <Eye class="w-4 h-4" />
                        Lihat
                    </Button>
                </div>
            </template>
        </DataTable>
    </div>

    <FormModal
        v-model:open="openForm"
        :kategori="selectedKategori"
        :base-path="basePath"
        :can-create="canCreate"
        :can-edit="canEdit"
    />
</template>
