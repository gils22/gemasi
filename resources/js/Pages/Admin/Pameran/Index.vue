<script setup lang="ts">
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import DataTable from "@/components/common/DataTable.vue";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import type { PageProps } from "@/types/inertia";

type PameranItem = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    anggota_tim: Array<{ nama?: string; nim?: string; peran?: string }>;
    pameran_ringkasan: string | null;
    pameran_link_video: string | null;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_submitted_at: string | null;
    pameran_lengkap: boolean;
    peserta: {
        name: string | null;
        email: string | null;
        avatar: string | null;
    };
};

const page = usePage<
    PageProps & { pameran: PameranItem[]; gemasiAktifLabel: string }
>();

const data = computed(() => page.props.pameran ?? []);
const kategoriFilter = ref("all");
const statusFilter = ref("all");

const kategoriOptions = computed(() => {
    const set = new Set<string>();
    data.value.forEach((item) => {
        if (item.nama_kategori) set.add(item.nama_kategori);
    });
    return Array.from(set);
});

const filteredRows = computed(() => {
    return data.value.filter((item) => {
        const kategoriOk =
            kategoriFilter.value === "all" ||
            item.nama_kategori === kategoriFilter.value;
        const statusOk =
            statusFilter.value === "all" ||
            (statusFilter.value === "lengkap" && item.pameran_lengkap) ||
            (statusFilter.value === "belum-mengirim" && !item.pameran_lengkap);
        return kategoriOk && statusOk;
    });
});

const tableRows = computed(() =>
    filteredRows.value.map((item) => ({
        ...item,
        peserta_nama:
            item.anggota_tim?.map((anggota) => anggota.nama ?? "").join(" ") ??
            "",
        video_link: item.pameran_link_video ?? "",
        logo_link: item.pameran_logo_url ?? "",
        logo_preview: item.pameran_logo_url ?? "",
    })),
);

const columns = [
    { key: "logo_link", label: "Logo URL" },
    { key: "logo_preview", label: "Logo" },
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "tim", label: "Tim", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "video_link", label: "Video" },
    { key: "ringkasan", label: "Ringkasan" },
    { key: "status", label: "Status" },
];

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

const getVideoPreview = (url: string) => {
    const id = extractYoutubeId(url);
    if (id) {
        return {
            type: "youtube" as const,
            thumbnail: `https://img.youtube.com/vi/${id}/hqdefault.jpg`,
        };
    }
    return {
        type: "generic" as const,
        thumbnail: "",
    };
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Pengumpulan Pameran" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <DataTable
            :columns="columns"
            :data="tableRows"
            :withAction="false"
            :search-keys="['nama_karya', 'peserta_nama']"
            :hidden-columns="['logo_link']"
            :export-column-keys="columns.map((col) => col.key)"
        >
            <template #toolbar-left>
                <div class="flex flex-col sm:flex-row gap-2">
                    <Select v-model="kategoriFilter">
                        <SelectTrigger class="w-full sm:w-44 bg-white">
                            <SelectValue placeholder="Semua Kategori" />
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

                    <Select v-model="statusFilter">
                        <SelectTrigger class="w-full sm:w-36 bg-white">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="lengkap">Lengkap</SelectItem>
                            <SelectItem value="belum-mengirim"
                                >Belum Mengirim</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
            </template>

            <template #logo_preview="{ row }">
                <div
                    class="h-12 w-12 rounded-md border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center"
                >
                    <img
                        v-if="row.logo_preview"
                        :src="row.logo_preview"
                        alt="Logo"
                        class="h-full w-full object-contain"
                    />
                    <span v-else class="text-[10px] text-slate-400">-</span>
                </div>
            </template>

            <template #tim="{ row }">
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

            <template #status="{ row }">
                <Badge :variant="row.pameran_lengkap ? 'success' : 'secondary'">
                    {{ row.pameran_lengkap ? "Lengkap" : "Belum mengirim" }}
                </Badge>
            </template>

            <template #logo_link>
                <span class="text-slate-400 text-sm">-</span>
            </template>

            <template #video_link="{ row }">
                <div class="w-full max-w-[220px]">
                    <a
                        v-if="row.pameran_link_video"
                        :href="row.pameran_link_video"
                        target="_blank"
                        class="block overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                    >
                        <img
                            v-if="
                                getVideoPreview(row.pameran_link_video)
                                    .thumbnail
                            "
                            :src="
                                getVideoPreview(row.pameran_link_video)
                                    .thumbnail
                            "
                            alt="Preview video"
                            class="h-24 w-full object-cover"
                        />
                        <div
                            v-else
                            class="h-24 w-full flex items-center justify-center text-xs text-slate-400"
                        >
                            Preview video
                        </div>
                        <div class="px-2 py-1 text-[11px] text-slate-600">
                            Klik untuk membuka
                        </div>
                    </a>
                    <div
                        v-else
                        class="h-24 w-full rounded-lg border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-400"
                    >
                        Belum ada video
                    </div>
                </div>
            </template>

            <template #ringkasan="{ row }">
                <p class="text-sm text-slate-700 line-clamp-2">
                    {{ row.pameran_ringkasan ?? "-" }}
                </p>
            </template>
        </DataTable>
    </div>
</template>
