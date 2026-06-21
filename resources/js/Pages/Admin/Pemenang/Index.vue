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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Eye, Star } from "lucide-vue-next";
import FavoritModal from "./FavoritModal.vue";
import type { PageProps } from "@/types/inertia";

type Pemenang = {
    id: number;
    peringkat: number;
    nama_karya: string | null;
    nama_kategori: string | null;
    nilai_final: number | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
    pameran_ringkasan: string | null;
    pameran_link_video: string | null;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_submitted_at: string | null;
};

type Favorit = {
    items: Array<{
        id: number;
        peringkat?: number | null;
        karya_peserta_id: number;
        nama_karya: string | null;
        nama_kategori: string | null;
        anggota_tim: Array<{ nama?: string; nim?: string }>;
        pameran_ringkasan: string | null;
        pameran_link_video: string | null;
        pameran_logo_name: string | null;
        pameran_logo_url: string | null;
        pameran_submitted_at: string | null;
    }>;
};

type FavoritOption = {
    id: number;
    label: string;
};

const page = usePage<
    PageProps & {
        pemenang: Pemenang[];
        favorit: Favorit | null;
        favoritOptions: FavoritOption[];
        gemasiAktifLabel: string;
    }
>();

const kategoriFilter = ref("all");
const data = computed(() => page.props.pemenang ?? []);
const favorit = computed(() => page.props.favorit ?? null);
const favoritOptions = computed(() => page.props.favoritOptions ?? []);
const openView = ref(false);
const selectedItem = ref<Pemenang | null>(null);
const openFavorit = ref(false);

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

const openDetail = (row: Pemenang) => {
    selectedItem.value = row;
    openView.value = true;
};

const extractYoutubeId = (url: string) => {
    const normalized = url.trim();
    if (!normalized) return "";
    const shortMatch = normalized.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
    if (shortMatch?.[1]) return shortMatch[1];
    const longMatch = normalized.match(/[?&]v=([a-zA-Z0-9_-]+)/);
    if (longMatch?.[1]) return longMatch[1];
    const embedMatch = normalized.match(
        /youtube\.com\/embed\/([a-zA-Z0-9_-]+)/,
    );
    if (embedMatch?.[1]) return embedMatch[1];
    return "";
};

const getEmbedUrl = (url: string | null) => {
    if (!url) return "";
    const id = extractYoutubeId(url);
    if (!id) return "";
    return `https://www.youtube.com/embed/${id}`;
};

const tetapkanOtomatis = () => {
    toast.warning("Tetapkan pemenang otomatis?", {
        description: "Sistem akan memilih 3 nilai tertinggi per kategori.",
        action: {
            label: "Ya, Tetapkan",
            onClick: () => {
                router.post(
                    "/admin/pemenang/tetapkan",
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () =>
                            toast.success("Pemenang berhasil ditetapkan."),
                        onError: () =>
                            toast.error("Gagal menetapkan pemenang."),
                    },
                );
            },
        },
        cancel: { label: "Batal", onClick: () => {} },
    });
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Pemenang" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <FavoritModal
            v-model:open="openFavorit"
            :favorit="favorit"
            :favorit-options="favoritOptions"
            @submit="
                (payload) => {
                    toast.warning('Tetapkan karya favorit?', {
                        description:
                            'Sistem akan menyimpan pilihan favorit untuk edisi aktif.',
                        action: {
                            label: 'Ya, Tetapkan',
                            onClick: () => {
                                router.post(
                                    '/admin/pemenang/favorit',
                                    payload,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            toast.success(
                                                'Karya favorit berhasil ditetapkan.',
                                            ),
                                        onError: () =>
                                            toast.error(
                                                'Gagal menetapkan favorit.',
                                            ),
                                    },
                                );
                            },
                        },
                        cancel: { label: 'Batal', onClick: () => {} },
                    });
                }
            "
        />

        <DataTable
            :columns="columns"
            :data="filtered"
            :withAction="true"
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
                <div class="flex items-center gap-3">
                    <Button @click="tetapkanOtomatis">Tetapkan Otomatis</Button>
                    <Button variant="outline" @click="openFavorit = true">
                        <Star class="mr-2 h-4 w-4" />
                        Atur Favorit
                    </Button>
                </div>
            </template>

            <template #peringkat="{ row }">
                <Badge class="bg-amber-100 text-amber-700"
                    >#{{ row.peringkat }}</Badge
                >
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
                        <span class="font-medium">{{
                            anggota.nama ?? "-"
                        }}</span>
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
                <TooltipProvider :delay-duration="150">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                @click="openDetail(row)"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Detail Karya</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </template>
        </DataTable>

        <Dialog v-model:open="openView">
            <DialogContent class="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Detail Karya</DialogTitle>
                </DialogHeader>

                <div v-if="selectedItem" class="space-y-5">
                    <div
                        class="flex items-start gap-4 border-b border-slate-100 pb-4"
                    >
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-3"
                        >
                            <img
                                v-if="selectedItem.pameran_logo_url"
                                :src="selectedItem.pameran_logo_url"
                                alt="Logo karya"
                                class="h-full w-full object-contain"
                            />
                            <span
                                v-else
                                class="text-center text-xs text-slate-400"
                            >
                                Belum ada logo
                            </span>
                        </div>

                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="space-y-1">
                                <p class="text-lg font-semibold text-slate-900">
                                    {{ selectedItem.nama_karya ?? "-" }}
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{ selectedItem.nama_kategori ?? "-" }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Badge class="bg-amber-100 text-amber-700">
                                    #{{ selectedItem.peringkat }}
                                </Badge>
                                <Badge class="bg-slate-100 text-slate-700">
                                    Nilai {{ selectedItem.nilai_final ?? "-" }}
                                </Badge>
                                <Badge class="bg-sky-50 text-sky-700">
                                    {{
                                        selectedItem.pameran_submitted_at ?? "-"
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div
                            class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-3"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Data Karya
                            </p>
                            <div class="space-y-3 text-sm">
                                <div
                                    class="rounded-xl border border-white bg-white p-3"
                                >
                                    <p class="text-xs text-slate-500">
                                        Nama Karya
                                    </p>
                                    <p class="font-medium text-slate-900">
                                        {{ selectedItem.nama_karya ?? "-" }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-white bg-white p-3"
                                >
                                    <p class="text-xs text-slate-500">
                                        Kategori
                                    </p>
                                    <p class="font-medium text-slate-900">
                                        {{ selectedItem.nama_kategori ?? "-" }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-white bg-white p-3"
                                >
                                    <p class="text-xs text-slate-500">
                                        Anggota Tim
                                    </p>
                                    <div class="mt-2 space-y-2">
                                        <div
                                            v-for="(
                                                anggota, idx
                                            ) in selectedItem.anggota_tim"
                                            :key="`${selectedItem.id}-anggota-${idx}`"
                                            class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"
                                        >
                                            <div>
                                                <p
                                                    class="font-medium text-slate-900"
                                                >
                                                    {{ anggota.nama ?? "-" }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{ anggota.nim ?? "-" }}
                                                </p>
                                            </div>
                                        </div>
                                        <p
                                            v-if="
                                                !selectedItem.anggota_tim
                                                    ?.length
                                            "
                                            class="text-sm text-slate-500"
                                        >
                                            Belum ada anggota tim
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-3"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                Lampiran Pameran
                            </p>
                            <div class="space-y-3">
                                <div
                                    class="rounded-xl border border-white bg-white p-3"
                                >
                                    <p class="text-xs text-slate-500">Logo</p>
                                    <p
                                        class="truncate font-medium text-slate-900"
                                    >
                                        {{
                                            selectedItem.pameran_logo_name ??
                                            "-"
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-white bg-white p-3"
                                >
                                    <p class="text-xs text-slate-500">
                                        Link Video
                                    </p>
                                    <a
                                        v-if="selectedItem.pameran_link_video"
                                        :href="selectedItem.pameran_link_video"
                                        target="_blank"
                                        class="break-all font-medium text-blue-600 hover:text-blue-700"
                                    >
                                        {{ selectedItem.pameran_link_video }}
                                    </a>
                                    <p
                                        v-else
                                        class="font-medium text-slate-400"
                                    >
                                        Belum ada video
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-white bg-white p-3"
                                >
                                    <p class="text-xs text-slate-500">
                                        Ringkasan
                                    </p>
                                    <p
                                        class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700"
                                    >
                                        {{
                                            selectedItem.pameran_ringkasan ??
                                            "-"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
