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
import { Button } from "@/components/ui/button";
import { computed, reactive, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import type { PageProps } from "@/types/inertia";
import { SquarePen } from "lucide-vue-next";
import PameranModal from "@/Pages/Peserta/Pameran/PameranModal.vue";

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

const data = ref<PameranItem[]>(page.props.pameran ?? []);
const kategoriFilter = ref("all");
const statusFilter = ref("all");

watch(
    () => page.props.pameran,
    (val) => {
        data.value = val ?? [];
    },
    { immediate: true },
);

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
    { key: "nama_karya", label: "Karya", sortable: true },
    { key: "logo_preview", label: "Logo" },
    { key: "tim", label: "Tim", sortable: true },
    { key: "nama_kategori", label: "Kategori", sortable: true },
    { key: "video_link", label: "Video" },
    { key: "ringkasan", label: "Ringkasan" },
    { key: "logo_link", label: "Logo URL" },
];

const formState = reactive<
    Record<
        number,
        {
            logo: File | null;
            logoPreview: string | null;
            linkVideo: string;
            ringkasan: string;
            saving: boolean;
        }
    >
>({});
const editOpen = reactive<Record<number, boolean>>({});
const modalAttempt = reactive<Record<number, boolean>>({});

const ensureState = (item: PameranItem) => {
    if (!formState[item.id]) {
        formState[item.id] = {
            logo: null,
            logoPreview: null,
            linkVideo: item.pameran_link_video ?? "",
            ringkasan: item.pameran_ringkasan ?? "",
            saving: false,
        };
    }
    return formState[item.id];
};

const handleLogoChange = (item: PameranItem, event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    const state = ensureState(item);
    if (state.logoPreview) {
        URL.revokeObjectURL(state.logoPreview);
    }
    state.logo = file;
    state.logoPreview = file ? URL.createObjectURL(file) : null;
    (event.target as HTMLInputElement).value = "";
};

const openEdit = (item: PameranItem) => {
    ensureState(item);
    modalAttempt[item.id] = false;
    editOpen[item.id] = true;
};

const simpanPameran = (item: PameranItem) => {
    const state = ensureState(item);
    modalAttempt[item.id] = true;
    const missingLogo = !state.logo && !item.pameran_logo_name;
    const missingVideo = !state.linkVideo.trim();
    if (missingLogo || missingVideo) {
        return;
    }

    state.saving = true;
    router.post(
        `/admin/pameran-karya/${item.id}`,
        {
            pameran_logo: state.logo,
            pameran_link_video: state.linkVideo.trim() || null,
            pameran_ringkasan: state.ringkasan.trim() || null,
            _method: "patch",
        },
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                const target = data.value.find((entry) => entry.id === item.id);
                if (target) {
                    if (state.logoPreview) {
                        target.pameran_logo_url = state.logoPreview;
                    }
                    if (state.logo) {
                        target.pameran_logo_name = state.logo.name;
                    }
                    target.pameran_link_video = state.linkVideo.trim() || null;
                    target.pameran_ringkasan = state.ringkasan.trim() || null;
                    target.pameran_submitted_at = "Baru saja";
                }
                editOpen[item.id] = false;
                modalAttempt[item.id] = false;
            },
            onFinish: () => {
                state.saving = false;
                editOpen[item.id] = false;
            },
        },
    );
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

const getEmbedUrl = (url: string) => {
    const id = extractYoutubeId(url);
    if (!id) return "";
    return `https://www.youtube.com/embed/${id}`;
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
            :withAction="true"
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
                                >Belum Lengkap</SelectItem
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

            <template #nama_karya="{ row }">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ row.nama_karya }}
                    </p>
                    <Badge
                        :variant="row.pameran_lengkap ? 'success' : 'secondary'"
                    >
                        {{ row.pameran_lengkap ? "Lengkap" : "Belum lengkap" }}
                    </Badge>
                </div>
            </template>

            <template #logo_link>
                <span class="text-slate-400 text-sm">-</span>
            </template>

            <template #video_link="{ row }">
                <div class="w-full max-w-[220px]">
                    <div
                        v-if="row.pameran_link_video"
                        class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                    >
                        <iframe
                            v-if="getEmbedUrl(row.pameran_link_video)"
                            :src="getEmbedUrl(row.pameran_link_video)"
                            class="h-24 w-full"
                            title="Video demo"
                            frameborder="0"
                            allow="
                                accelerometer;
                                autoplay;
                                clipboard-write;
                                encrypted-media;
                                gyroscope;
                                picture-in-picture;
                                web-share;
                            "
                            allowfullscreen
                        />
                        <div
                            v-else
                            class="h-24 w-full flex items-center justify-center text-xs text-slate-400"
                        >
                            Preview video tidak tersedia
                        </div>
                        <a
                            :href="row.pameran_link_video"
                            target="_blank"
                            class="text-center block px-2 py-1 text-[11px] text-slate-600 hover:text-slate-900"
                        >
                            View Video
                        </a>
                    </div>
                    <div
                        v-else
                        class="h-24 w-full rounded-lg border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-400"
                    >
                        Belum ada video
                    </div>
                </div>
            </template>

            <template #ringkasan="{ row }">
                <p
                    class="text-sm text-slate-700 break-words whitespace-pre-line line-clamp-2"
                    :title="row.pameran_ringkasan || '-'"
                >
                    {{ row.pameran_ringkasan ?? "-" }}
                </p>
            </template>

            <template #actions="{ row }">
                <Button size="icon" variant="outline" @click="openEdit(row)">
                    <SquarePen class="h-4 w-4" />
                </Button>
                <PameranModal
                    v-model:open="editOpen[row.id]"
                    :item="row"
                    :state="ensureState(row)"
                    :attempt="modalAttempt[row.id]"
                    :boleh-edit="true"
                    :get-video-preview="getVideoPreview"
                    @logo-change="(e) => handleLogoChange(row, e)"
                    @save="simpanPameran(row)"
                />
            </template>
        </DataTable>
    </div>
</template>
