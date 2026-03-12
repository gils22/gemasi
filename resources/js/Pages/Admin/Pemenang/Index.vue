<script setup lang="ts">
import { computed, ref, watch } from "vue";
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
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Plus, SquarePen, Trash2 } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type Pemenang = {
    id: number;
    peringkat: number;
    nama_karya: string | null;
    nama_kategori: string | null;
    nilai_final: number | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
};

type Arsip = {
    id: number;
    edisi_id: number;
    tahun: number | null;
    nama_edisi: string | null;
    peringkat: number;
    nama_kategori: string | null;
    nama_karya: string | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
};

type Edisi = { id: number; nama: string; tahun: number };

const page = usePage<
    PageProps & {
        pemenang: Pemenang[];
        pemenangArsip: Arsip[];
        daftarEdisi: Edisi[];
        gemasiAktifLabel: string;
    }
>();

const kategoriFilter = ref("all");
const data = computed(() => page.props.pemenang ?? []);
const arsipData = computed(() => page.props.pemenangArsip ?? []);
const daftarEdisi = computed(() => page.props.daftarEdisi ?? []);
const arsipFilterTahun = ref<string>("all");

const arsipFormOpen = ref(false);
const arsipEditing = ref<Arsip | null>(null);
const arsipForm = ref<{
    edisi_lomba_id: string;
    kategori: string;
    peringkat: string;
    nama_karya: string;
    anggota_tim: Array<{ nama: string; nim: string }>;
}>({
    edisi_lomba_id: "",
    kategori: "",
    peringkat: "1",
    nama_karya: "",
    anggota_tim: [{ nama: "", nim: "" }],
});

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

const arsipColumns = [
    { key: "tahun", label: "Tahun", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "peringkat", label: "Peringkat", sortable: true },
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "anggota_tim", label: "Anggota Tim" },
];

const arsipTahunOptions = computed(() => {
    const set = new Set<number>();
    arsipData.value.forEach((item) => {
        if (item.tahun) set.add(item.tahun);
    });
    return Array.from(set).sort((a, b) => b - a);
});

const filteredArsip = computed(() => {
    if (arsipFilterTahun.value === "all") return arsipData.value;
    return arsipData.value.filter(
        (item) => String(item.tahun) === arsipFilterTahun.value,
    );
});

const resetArsipForm = () => {
    const firstEdisi = daftarEdisi.value[0];
    arsipForm.value = {
        edisi_lomba_id: firstEdisi ? String(firstEdisi.id) : "",
        kategori: "",
        peringkat: "1",
        nama_karya: "",
        anggota_tim: [{ nama: "", nim: "" }],
    };
};

const bukaTambahArsip = () => {
    arsipEditing.value = null;
    resetArsipForm();
    arsipFormOpen.value = true;
};

const bukaEditArsip = (row: Arsip) => {
    arsipEditing.value = row;
    arsipForm.value = {
        edisi_lomba_id: String(row.edisi_id),
        kategori: row.nama_kategori ?? "",
        peringkat: String(row.peringkat ?? 1),
        nama_karya: row.nama_karya ?? "",
        anggota_tim:
            row.anggota_tim?.length > 0
                ? row.anggota_tim.map((item) => ({
                      nama: item.nama ?? "",
                      nim: item.nim ?? "",
                  }))
                : [{ nama: "", nim: "" }],
    };
    arsipFormOpen.value = true;
};

const tambahAnggotaArsip = () => {
    arsipForm.value.anggota_tim.push({ nama: "", nim: "" });
};

const hapusAnggotaArsip = (index: number) => {
    if (arsipForm.value.anggota_tim.length <= 1) return;
    arsipForm.value.anggota_tim.splice(index, 1);
};

const simpanArsip = () => {
    const payload = {
        edisi_lomba_id: Number(arsipForm.value.edisi_lomba_id),
        kategori: arsipForm.value.kategori,
        peringkat: Number(arsipForm.value.peringkat),
        nama_karya: arsipForm.value.nama_karya,
        anggota_tim: arsipForm.value.anggota_tim,
    };

    if (arsipEditing.value) {
        router.put(`/admin/pemenang/arsip/${arsipEditing.value.id}`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Pemenang arsip diperbarui.");
                arsipFormOpen.value = false;
            },
            onError: () => toast.error("Gagal memperbarui pemenang arsip."),
        });
        return;
    }

    router.post("/admin/pemenang/arsip", payload, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Pemenang arsip ditambahkan.");
            arsipFormOpen.value = false;
        },
        onError: () => toast.error("Gagal menambahkan pemenang arsip."),
    });
};

const hapusArsip = (row: Arsip) => {
    toast.warning("Hapus pemenang arsip?", {
        description: "Data arsip akan dihapus permanen.",
        action: {
            label: "Hapus",
            onClick: () => {
                router.delete(`/admin/pemenang/arsip/${row.id}`, {
                    preserveScroll: true,
                    onSuccess: () => toast.success("Pemenang arsip dihapus."),
                    onError: () => toast.error("Gagal menghapus pemenang arsip."),
                });
            },
        },
        cancel: { label: "Batal", onClick: () => {} },
    });
};

watch(
    daftarEdisi,
    (val) => {
        if (!arsipForm.value.edisi_lomba_id && val.length > 0) {
            arsipForm.value.edisi_lomba_id = String(val[0].id);
        }
    },
    { immediate: true },
);

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

        <div class="rounded-xl border bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">
                        Pemenang Arsip (Manual)
                    </h3>
                    <p class="text-xs text-slate-500">
                        Tambahkan pemenang tahun-tahun sebelumnya untuk
                        kebutuhan landing.
                    </p>
                </div>
                <Button variant="outline" @click="bukaTambahArsip">
                    <Plus class="h-4 w-4" />
                    Tambah Manual
                </Button>
            </div>

            <div class="mt-4">
                <DataTable
                    :columns="arsipColumns"
                    :data="filteredArsip"
                    :withAction="true"
                    :search-keys="['nama_karya', 'nama_kategori']"
                >
                    <template #toolbar-left>
                        <Select v-model="arsipFilterTahun">
                            <SelectTrigger class="w-44 h-10 bg-white">
                                <SelectValue placeholder="Filter Tahun" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua Tahun
                                </SelectItem>
                                <SelectItem
                                    v-for="tahun in arsipTahunOptions"
                                    :key="tahun"
                                    :value="String(tahun)"
                                >
                                    {{ tahun }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </template>

                    <template #peringkat="{ row }">
                        <Badge class="bg-amber-100 text-amber-700">
                            #{{ row.peringkat }}
                        </Badge>
                    </template>

                    <template #anggota_tim="{ row }">
                        <div class="space-y-1.5">
                            <div
                                v-for="(anggota, idx) in row.anggota_tim"
                                :key="`${row.id}-arsip-${idx}`"
                                class="flex items-center gap-2 text-sm text-slate-800"
                            >
                                <span class="font-medium">
                                    {{ anggota.nama ?? "-" }}
                                </span>
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

                    <template #actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <Button
                                size="icon"
                                variant="ghost"
                                @click="bukaEditArsip(row)"
                            >
                                <SquarePen class="h-4 w-4" />
                            </Button>
                            <Button
                                size="icon"
                                variant="ghost"
                                @click="hapusArsip(row)"
                            >
                                <Trash2 class="h-4 w-4 text-rose-600" />
                            </Button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:open="arsipFormOpen">
            <DialogContent class="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ arsipEditing ? "Edit" : "Tambah" }} Pemenang Arsip
                    </DialogTitle>
                    <DialogDescription>
                        Data ini akan ditampilkan pada landing untuk tahun
                        sebelumnya.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs">Edisi</label>
                            <Select v-model="arsipForm.edisi_lomba_id">
                                <SelectTrigger class="w-full bg-white">
                                    <SelectValue placeholder="Pilih edisi" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="edisi in daftarEdisi"
                                        :key="edisi.id"
                                        :value="String(edisi.id)"
                                    >
                                        {{ edisi.nama }} ({{ edisi.tahun }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs">Peringkat</label>
                            <Select v-model="arsipForm.peringkat">
                                <SelectTrigger class="w-full bg-white">
                                    <SelectValue placeholder="Peringkat" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Juara 1</SelectItem>
                                    <SelectItem value="2">Juara 2</SelectItem>
                                    <SelectItem value="3">Juara 3</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs">Kategori</label>
                            <Input
                                v-model="arsipForm.kategori"
                                placeholder="Kategori lomba"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs">Nama Karya</label>
                            <Input
                                v-model="arsipForm.nama_karya"
                                placeholder="Nama karya"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div
                            class="grid grid-cols-1 md:grid-cols-[1.6fr_1fr_auto] gap-2 text-[11px] text-slate-500"
                        >
                            <span>Nama Anggota</span>
                            <span>NIM</span>
                            <span class="text-right">Aksi</span>
                        </div>
                        <div
                            v-for="(anggota, idx) in arsipForm.anggota_tim"
                            :key="`anggota-${idx}`"
                            class="grid grid-cols-1 md:grid-cols-[1.6fr_1fr_auto] gap-2"
                        >
                            <Input
                                v-model="anggota.nama"
                                placeholder="Nama"
                            />
                            <Input v-model="anggota.nim" placeholder="NIM" />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="md:ml-auto"
                                :disabled="arsipForm.anggota_tim.length <= 1"
                                @click="hapusAnggotaArsip(idx)"
                            >
                                <Trash2 class="h-4 w-4 text-rose-600" />
                            </Button>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="tambahAnggotaArsip"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Anggota
                        </Button>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="arsipFormOpen = false"
                    >
                        Batal
                    </Button>
                    <Button type="button" @click="simpanArsip">
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
