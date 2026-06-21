<script setup lang="ts">
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Archive, Eye, Plus, RotateCcw, Trash2, Users } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { toast } from "vue-sonner";

type KaryaItem = {
    id: number;
    edisi_lomba_id: number;
    nama_karya: string;
    nama_kategori: string;
    jumlah_anggota_tim: number;
    nama_ketua: string | null;
    status_tampilan: string;
    updated_at: string | null;
    edisi: string | null;
    dapat_dikelola: boolean;
    peran_akses?: "ketua" | "anggota";
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Daftar Karya" }, () => page),
});

const page = usePage<
    PageProps & {
        daftarKarya?: KaryaItem[];
        arsipPendaftaran?: KaryaItem[];
        punyaKaryaArsip?: boolean;
    }
>();
const daftarKarya = computed(() => page.props.daftarKarya ?? []);
const arsipPendaftaran = computed(() => page.props.arsipPendaftaran ?? []);
const pendaftaranDibuka = Boolean(page.props.pendaftaranDibuka);
const punyaKaryaArsip = Boolean(page.props.punyaKaryaArsip);
const gemasiAktifLabel =
    (page.props.gemasiAktifLabel as string | undefined) ?? "-";

const arsipOpen = ref(false);

const modalHapusTerbuka = ref(false);
const karyaDipilih = ref<KaryaItem | null>(null);

const formatDateTime = (value: string | null) => {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(date);
};

const bukaFormEdit = (id: number) => {
    router.get(`/peserta/daftar-karya/form?karya=${id}`);
};

const bukaModalHapus = (item: KaryaItem) => {
    karyaDipilih.value = item;
    modalHapusTerbuka.value = true;
};

const konfirmasiHapus = () => {
    if (!karyaDipilih.value) return;

    router.delete(`/peserta/daftar-karya/${karyaDipilih.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Karya berhasil diarsipkan.");
        },
        onError: () => {
            toast.error("Gagal mengarsipkan karya.");
        },
        onFinish: () => {
            modalHapusTerbuka.value = false;
            karyaDipilih.value = null;
        },
    });
};

const judulKonfirmasi = computed(
    () => karyaDipilih.value?.nama_karya ?? "karya ini",
);

const pulihkan = (item: KaryaItem) => {
    router.patch(
        `/peserta/daftar-karya/${item.id}/restore`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Karya berhasil dipulihkan."),
            onError: () => toast.error("Gagal memulihkan karya."),
        },
    );
};
</script>

<template>
    <section class="w-full space-y-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="">
                <h1 class="text-lg font-semibold text-slate-800">
                    Karya Terdaftar
                </h1>
                <p class="text-xs text-slate-600">
                    {{ gemasiAktifLabel }}
                </p>
            </div>
            <Button v-if="pendaftaranDibuka" as-child>
                <Link href="/peserta/daftar-karya/form?baru=1">
                    <Plus class="h-4 w-4" />
                    Tambah Karya Baru
                </Link>
            </Button>
            <div
                v-else
                class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                Pendaftaran sudah ditutup.
            </div>
        </div>

        <div
            v-if="!daftarKarya.length"
            class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
        >
            <span v-if="pendaftaranDibuka">
                Belum ada karya yang didaftarkan. Klik "Tambah Karya Baru" untuk
                mulai.
            </span>
            <span v-else-if="punyaKaryaArsip">
                Karya yang pernah dihapus dari daftar akan tetap tersimpan di
                arsip pendaftaran.
            </span>
            <span v-else> Belum ada karya yang terdaftar. </span>
        </div>

        <div
            v-else
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="item in daftarKarya"
                :key="item.id"
                class="rounded-lg border border-slate-200 bg-white p-4 flex flex-col gap-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-1 min-w-0">
                        <h3
                            class="text-base font-semibold text-slate-800 truncate"
                        >
                            {{ item.nama_karya }}
                        </h3>
                        <p class="text-sm text-slate-600 truncate">
                            {{ item.nama_kategori }} - {{ item.edisi ?? "-" }}
                        </p>
                    </div>
                    <Badge
                        :class="
                            item.status_tampilan === 'Lengkap'
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-amber-50 text-amber-700'
                        "
                    >
                        {{
                            item.status_tampilan === "Lengkap"
                                ? "File lengkap"
                                : "Draft"
                        }}
                    </Badge>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Badge
                        :class="
                            item.peran_akses === 'ketua'
                                ? 'bg-indigo-50 text-indigo-700'
                                : 'bg-slate-100 text-slate-700'
                        "
                    >
                        {{ item.peran_akses === "ketua" ? "Ketua" : "Anggota" }}
                    </Badge>
                </div>

                <div class="space-y-1 text-sm text-slate-600">
                    <div class="inline-flex items-center gap-1">
                        <Users class="h-4 w-4 text-slate-500" />
                        <span>{{ item.jumlah_anggota_tim }} anggota tim</span>
                    </div>
                    <div class="truncate">
                        <span class="text-slate-500">Ketua:</span>
                        {{ item.nama_ketua ?? "-" }}
                    </div>
                    <div>
                        <span class="text-slate-500">Diperbarui:</span>
                        {{ formatDateTime(item.updated_at) }}
                    </div>
                </div>

                <div class="mt-auto flex flex-wrap gap-2">
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    @click="bukaFormEdit(item.id)"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{
                                    item.dapat_dikelola
                                        ? "Lihat / Edit"
                                        : "Lihat"
                                }}
                            </TooltipContent>
                        </Tooltip>

                        <Tooltip v-if="item.dapat_dikelola">
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    class="text-slate-500 border-slate-200 hover:border-rose-200 hover:text-rose-600"
                                    @click="bukaModalHapus(item)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Hapus</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </article>
        </div>

        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        v-if="arsipPendaftaran.length"
                        type="button"
                        size="icon"
                        class="fixed bottom-6 right-6 z-50 h-12 w-12 rounded-full shadow-lg"
                        @click="arsipOpen = true"
                    >
                        <Archive class="h-5 w-5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Arsip pendaftaran</TooltipContent>
            </Tooltip>
        </TooltipProvider>

        <Dialog v-model:open="arsipOpen">
            <DialogContent class="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Arsip Pendaftaran</DialogTitle>
                    <DialogDescription>
                        Karya yang Anda Hapus dari Daftar Karya. Anda bisa
                        memulihkannya selama pendaftaran masih dibuka.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="!arsipPendaftaran.length"
                    class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
                >
                    Belum ada karya yang diarsipkan.
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-2"
                >
                    <article
                        v-for="item in arsipPendaftaran"
                        :key="`arsip-${item.id}`"
                        class="rounded-lg border border-slate-200 bg-white p-4 flex flex-col gap-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1 min-w-0">
                                <h3
                                    class="text-base font-semibold text-slate-800 truncate"
                                >
                                    {{ item.nama_karya }}
                                </h3>
                                <p class="text-sm text-slate-600 truncate">
                                    {{ item.nama_kategori }} -
                                    {{ item.edisi ?? "-" }}
                                </p>
                            </div>
                            <Badge class="bg-slate-100 text-slate-700">
                                Diarsipkan
                            </Badge>
                        </div>

                        <div class="space-y-1 text-sm text-slate-600">
                            <div class="inline-flex items-center gap-1">
                                <Users class="h-4 w-4 text-slate-500" />
                                <span>
                                    {{ item.jumlah_anggota_tim }} anggota tim
                                </span>
                            </div>
                            <div class="truncate">
                                <span class="text-slate-500">Ketua:</span>
                                {{ item.nama_ketua ?? "-" }}
                            </div>
                            <div>
                                <span class="text-slate-500">Diarsipkan:</span>
                                {{ formatDateTime(item.updated_at) }}
                            </div>
                        </div>

                        <div class="mt-auto flex flex-wrap gap-2">
                            <TooltipProvider>
                                <Tooltip v-if="item.dapat_dikelola">
                                    <TooltipTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon-sm"
                                            @click="pulihkan(item)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Pulihkan</TooltipContent>
                                </Tooltip>
                                <Tooltip v-else>
                                    <TooltipTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon-sm"
                                            @click="bukaFormEdit(item.id)"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Lihat</TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </article>
                </div>
            </DialogContent>
        </Dialog>

        <AlertDialog v-model:open="modalHapusTerbuka">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Karya</AlertDialogTitle>
                    <AlertDialogDescription>
                        Anda yakin ingin menghapus
                        <strong>{{ judulKonfirmasi }}</strong
                        >? Karya akan dipindahkan ke Arsip dan tidak tampil di
                        Daftar Karya.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="konfirmasiHapus">
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </section>
</template>
