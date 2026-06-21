<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Eye, Plus } from "lucide-vue-next";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import FormModal from "./FormModal.vue";

type TimelineItem = {
    id: number;
    judul: string;
    tipe: "utama" | "tambahan";
    fase_kunci: string | null;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    deskripsi: string | null;
    aktif: boolean;
    created_at: string;
};

type EdisiAktif = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    aktif: boolean;
};

type FaseOption = { key: string; label: string };

const page = usePage<{
    timeline: TimelineItem[];
    edisiAktif: EdisiAktif;
    modeArsip: boolean;
    isEditable: boolean;
    isAdmin: boolean;
    basePath: string;
    faseUtama: FaseOption[];
}>();

const timeline = computed(() => page.props.timeline ?? []);
const edisiAktif = computed(() => page.props.edisiAktif);
const modeArsip = computed(() => page.props.modeArsip === true);
const isEditable = computed(() => page.props.isEditable === true);
const isAdmin = computed(() => page.props.isAdmin === true);
const basePath = computed(() => page.props.basePath || "/admin");
const faseUtama = computed(() => page.props.faseUtama ?? []);
const faseMap = computed<Record<string, string>>(() => {
    return Object.fromEntries(faseUtama.value.map((f) => [f.key, f.label]));
});

const canCreate = computed(() => isAdmin.value && isEditable.value);
const canEdit = computed(() => isEditable.value);
const canDelete = computed(() => isAdmin.value && isEditable.value);

const openForm = ref(false);
const selectedTimeline = ref<TimelineItem | null>(null);

const columns = [
    { key: "judul", label: "Agenda", sortable: true },
    { key: "tipe", label: "Tipe", sortable: true },
    { key: "jadwal", label: "Jadwal" },
];

const tambahTimeline = () => {
    selectedTimeline.value = null;
    openForm.value = true;
};

const editTimeline = (row: TimelineItem) => {
    selectedTimeline.value = row;
    openForm.value = true;
};

const handleBulkDelete = (ids: number[]) => {
    if (!canDelete.value || ids.length === 0) return;

    toast.warning(`Hapus ${ids.length} agenda terpilih?`, {
        description: "Data timeline akan dihapus permanen.",
        action: {
            label: "Ya, Hapus",
            onClick: () => {
                router.delete(`${basePath.value}/timeline/bulk-delete`, {
                    data: { ids },
                    preserveScroll: true,
                    preserveState: false,
                    onSuccess: () => toast.success("Timeline berhasil dihapus"),
                    onError: () => toast.error("Gagal menghapus timeline"),
                });
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

const formatJadwal = (row: TimelineItem) => {
    if (row.is_tba) return "TBA";

    const mulai = row.mulai_pada
        ? new Date(row.mulai_pada).toLocaleString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
              hour: "2-digit",
              minute: "2-digit",
          })
        : "-";

    const selesai = row.selesai_pada
        ? new Date(row.selesai_pada).toLocaleString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
              hour: "2-digit",
              minute: "2-digit",
          })
        : "-";

    return `${mulai} - ${selesai}`;
};


defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Timeline" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <DataTable
            :columns="columns"
            :data="timeline"
            :withAction="true"
            :showBulkDelete="canDelete"
            @bulk-delete="handleBulkDelete"
        >
            <template #toolbar-right>
                <Button v-if="canCreate" @click="tambahTimeline">
                    <Plus class="w-4 h-4" />
                    Tambah Timeline
                </Button>
            </template>

            <template #judul="{ row }: { row: TimelineItem }">
                <div class="space-y-1">
                    <p class="font-medium text-slate-800">{{ row.judul }}</p>
                    <p v-if="row.fase_kunci" class="text-xs text-slate-500">
                        {{ faseMap[row.fase_kunci] ?? row.fase_kunci }}
                    </p>
                </div>
            </template>

            <template #tipe="{ row }: { row: TimelineItem }">
                <Badge
                    :class="
                        row.tipe === 'utama'
                            ? 'bg-blue-100 text-blue-700'
                            : 'bg-purple-100 text-purple-700'
                    "
                >
                    {{ row.tipe === "utama" ? "Utama" : "Opsional" }}
                </Badge>
            </template>

            <template #jadwal="{ row }: { row: TimelineItem }">
                <span class="text-sm text-slate-700">
                    {{ formatJadwal(row) }}
                </span>
            </template>

            <template #actions="{ row }: { row: TimelineItem }">
                <div class="flex items-center justify-end gap-1">
                    <Button
                        size="icon"
                        variant="ghost"
                        class="hidden md:inline-flex"
                        :disabled="!canEdit"
                        @click="editTimeline(row)"
                    >
                        <Eye class="w-4 h-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="md:hidden"
                        :disabled="!canEdit"
                        @click="editTimeline(row)"
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
        :item="selectedTimeline"
        :base-path="basePath"
        :can-create="canCreate"
        :can-edit="canEdit"
        :fase-utama="faseUtama"
    />
</template>

