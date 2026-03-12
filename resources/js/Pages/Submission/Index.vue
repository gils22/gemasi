<script setup lang="ts">
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { CheckCircle2, Eye, RotateCcw } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { Badge } from "@/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import type { PageProps } from "@/types/inertia";

type Submission = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    status: "draft" | "submitted";
    is_lolos_nominasi: boolean;
    submitted_at: string | null;
    nilai_tahap_1: number | null;
    peserta: {
        id: number | null;
        name: string | null;
        email: string | null;
        avatar: string | null;
    };
    anggota_tim: Array<{ nama?: string; peran?: string }>;
};

const page = usePage<
    PageProps & {
        submissions: Submission[];
        gemasiAktifLabel: string;
        bolehKelola: boolean;
        mode?: "karya" | "nominasi";
    }
>();

const kategoriFilter = ref<string>("semua");
const kelengkapanFilter = ref<"semua" | "lengkap" | "belum_lengkap">("semua");

const submissions = computed(() => page.props.submissions ?? []);
const bolehKelola = computed(() => page.props.bolehKelola === true);
const role = computed(() => page.props.auth?.role ?? "admin");
const isPrivileged = computed(() => role.value === "admin");
const routePrefix = computed(() => "/admin");
const mode = computed(() => page.props.mode ?? "karya");

const daftarKategori = computed(() => {
    return Array.from(
        new Set(submissions.value.map((item) => item.nama_kategori)),
    ).filter(Boolean);
});

const tableRows = computed(() =>
    submissions.value.map((item) => ({
        ...item,
        peserta_nama: item.peserta.name ?? "",
    })),
);

const filteredSubmissions = computed(() => {
    let rows = tableRows.value;

    return rows.filter((item) => {
        const cocokKategori =
            kategoriFilter.value === "semua" ||
            item.nama_kategori === kategoriFilter.value;
        const cocokKelengkapan =
            mode.value === "karya"
                ? kelengkapanFilter.value === "semua" ||
                  (kelengkapanFilter.value === "lengkap" &&
                      item.status === "submitted") ||
                  (kelengkapanFilter.value === "belum_lengkap" &&
                      item.status !== "submitted")
                : true;
        return cocokKategori && cocokKelengkapan;
    });
});

const columns = [
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "peserta", label: "Peserta", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "anggota", label: "Anggota Tim" },
    { key: "nilai_tahap_1", label: "Nilai Tahap 1", sortable: true },
];

const openDetail = (row: Submission) => {
    router.get(`${routePrefix.value}/submission/${row.id}`, {
        from: mode.value,
    });
};

const handleBulkDelete = (ids: number[]) => {
    if (!bolehKelola.value || !ids.length) return;
    router.delete(`${routePrefix.value}/submission/bulk-delete`, {
        data: { ids },
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Submission terpilih berhasil dihapus");
        },
        onError: () => {
            toast.error("Gagal menghapus submission terpilih");
        },
    });
};

const toggleNominasi = (row: Submission) => {
    if (!bolehKelola.value) return;
    if (!row.is_lolos_nominasi && row.status !== "submitted") {
        toast.error("Hanya submission lengkap yang bisa diloloskan.");
        return;
    }
    if (!row.is_lolos_nominasi && row.nilai_tahap_1 === null) {
        toast.error("Nilai tahap 1 belum diisi.");
        return;
    }

    const endpoint = row.is_lolos_nominasi
        ? `${routePrefix.value}/submission/${row.id}/batalkan-nominasi`
        : `${routePrefix.value}/submission/${row.id}/lolos-nominasi`;

    router.patch(
        endpoint,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    row.is_lolos_nominasi
                        ? "Nominasi dibatalkan."
                        : "Berhasil ditandai lolos nominasi.",
                );
            },
            onError: () => {
                toast.error("Gagal memperbarui status nominasi.");
            },
        },
    );
};

const kembalikanKeKarya = (row: Submission) => {
    if (!bolehKelola.value) return;
    router.patch(
        `${routePrefix.value}/submission/${row.id}/batalkan-nominasi`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Karya dibatalkan nominasi.");
            },
            onError: () => {
                toast.error("Gagal mengembalikan karya.");
            },
        },
    );
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Submission Peserta" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="hidden md:block">
            <DataTable
                :columns="columns"
                :data="filteredSubmissions"
                :withAction="true"
                :showBulkDelete="mode === 'karya' && bolehKelola"
                :search-keys="['nama_karya', 'peserta_nama']"
                @bulk-delete="handleBulkDelete"
            >
                <template #toolbar-left>
                    <div class="flex flex-wrap items-center gap-2">
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

                        <Select v-if="mode === 'karya'" v-model="kelengkapanFilter">
                            <SelectTrigger class="w-44 h-10 bg-white">
                                <SelectValue placeholder="Filter Kelengkapan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="semua"
                                    >Semua Submission</SelectItem
                                >
                                <SelectItem value="lengkap"
                                    >Submission Lengkap</SelectItem
                                >
                                <SelectItem value="belum_lengkap"
                                    >Belum Lengkap</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                </template>

            <template #peserta="{ row }: { row: Submission }">
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
                        <p class="truncate font-medium text-slate-800">
                            {{ row.peserta.name ?? "-" }}
                        </p>
                        <p class="truncate text-xs text-slate-500">
                            {{ row.peserta.email ?? "-" }}
                        </p>
                    </div>
                </div>
            </template>

            <template #nama_karya="{ row }: { row: Submission }">
                <div class="min-w-0 space-y-1">
                    <p class="truncate font-medium text-slate-800">
                        {{ row.nama_karya }}
                    </p>
                    <Badge
                        v-if="mode !== 'nominasi' && row.is_lolos_nominasi"
                        class="bg-emerald-100 text-emerald-700"
                    >
                        Lolos Nominasi
                    </Badge>
                </div>
            </template>

            <template #anggota="{ row }: { row: Submission }">
                <span class="text-sm text-slate-700"
                    >{{ row.anggota_tim?.length ?? 0 }} orang</span
                >
            </template>

            <template #nilai_tahap_1="{ row }: { row: Submission }">
                <Badge
                    v-if="row.nilai_tahap_1 !== null"
                    class="bg-indigo-50 text-indigo-700"
                >
                    {{ row.nilai_tahap_1 }}
                </Badge>
                <span v-else class="text-sm text-slate-400">-</span>
            </template>

            <template #actions="{ row }: { row: Submission }">
                <TooltipProvider :delay-duration="150">
                    <div class="flex items-center justify-end gap-1">
                        <template v-if="mode === 'karya'">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="hidden md:inline-flex"
                                        @click="openDetail(row)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                            </Tooltip>

                            <Tooltip v-if="bolehKelola">
                                <TooltipTrigger as-child>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="hidden md:inline-flex"
                                        :class="
                                            row.is_lolos_nominasi
                                                ? 'text-amber-600 hover:text-amber-700'
                                                : 'text-emerald-600 hover:text-emerald-700'
                                        "
                                        @click="toggleNominasi(row)"
                                    >
                                        <RotateCcw
                                            v-if="row.is_lolos_nominasi"
                                            class="h-4 w-4"
                                        />
                                        <CheckCircle2 v-else class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>{{
                                    row.is_lolos_nominasi
                                        ? "Batalkan nominasi"
                                        : "Lolos nominasi"
                                }}</TooltipContent>
                            </Tooltip>

                            <Button
                                variant="outline"
                                size="sm"
                                class="md:hidden"
                                @click="openDetail(row)"
                            >
                                Lihat
                            </Button>

                            <Button
                                v-if="bolehKelola"
                                variant="outline"
                                size="sm"
                                class="md:hidden"
                                :disabled="
                                    !row.is_lolos_nominasi &&
                                    (row.status !== 'submitted' ||
                                        row.nilai_tahap_1 === null)
                                "
                                @click="toggleNominasi(row)"
                            >
                                {{
                                    row.is_lolos_nominasi ? "Batalkan" : "Lolos"
                                }}
                            </Button>
                        </template>

                        <template v-else>
                            <Tooltip v-if="bolehKelola">
                                <TooltipTrigger as-child>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="text-amber-600 hover:text-amber-700"
                                        @click="kembalikanKeKarya(row)"
                                    >
                                        <RotateCcw class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent
                                    >Batalkan Nominasi</TooltipContent
                                >
                            </Tooltip>
                        </template>
                    </div>
                </TooltipProvider>
            </template>
            </DataTable>
        </div>

        <div class="md:hidden space-y-3">
            <div class="flex flex-col gap-2">
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

                <Select v-if="mode === 'karya'" v-model="kelengkapanFilter">
                    <SelectTrigger class="w-full h-10 bg-white">
                        <SelectValue placeholder="Filter Kelengkapan" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua Submission</SelectItem>
                        <SelectItem value="lengkap"
                            >Submission Lengkap</SelectItem
                        >
                        <SelectItem value="belum_lengkap"
                            >Belum Lengkap</SelectItem
                        >
                    </SelectContent>
                </Select>
            </div>

            <div
                v-for="row in filteredSubmissions"
                :key="row.id"
                class="rounded-xl border border-slate-200 bg-white p-4"
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
                                (row.peserta.name ?? 'P')
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 wrap-break-word">
                            {{ row.nama_karya }}
                        </p>
                        <p class="text-xs text-slate-500 wrap-break-word">
                            {{ row.nama_kategori }}
                        </p>
                        <p class="text-xs text-slate-500 wrap-break-word">
                            {{ row.peserta.name ?? '-' }} �
                            {{ row.peserta.email ?? '-' }}
                        </p>
                        <Badge
                            v-if="row.is_lolos_nominasi"
                            class="mt-2 bg-emerald-100 text-emerald-700"
                        >
                            Lolos Nominasi
                        </Badge>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <span>Anggota: {{ row.anggota_tim?.length ?? 0 }} orang</span>
                    <span>Nilai T1: {{ row.nilai_tahap_1 ?? '-' }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <Button size="sm" class="w-full" variant="outline" @click="openDetail(row)">
                        Lihat
                    </Button>

                    <template v-if="mode === 'karya'">
                        <Button
                            v-if="bolehKelola"
                            size="sm"
                            class="w-full"
                            :variant="row.is_lolos_nominasi ? 'outline' : 'default'"
                            :disabled="!row.is_lolos_nominasi && (row.status !== 'submitted' || row.nilai_tahap_1 === null)"
                            @click="toggleNominasi(row)"
                        >
                            {{ row.is_lolos_nominasi ? "Batalkan" : "Lolos" }}
                        </Button>
                    </template>

                    <template v-else>
                        <Button
                            v-if="bolehKelola"
                            size="sm"
                            class="w-full"
                            variant="outline"
                            @click="kembalikanKeKarya(row)"
                        >
                            Batalkan Nominasi
                        </Button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

