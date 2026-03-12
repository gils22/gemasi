<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { SquarePen, Plus } from "lucide-vue-next";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import type { PageProps } from "@/types/inertia";
import PameranModal from "@/Pages/Peserta/Pameran/PameranModal.vue";

type NominasiItem = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_link_video: string | null;
    pameran_ringkasan: string | null;
    pameran_submitted_at: string | null;
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Pameran Karya" }, () => page),
});

const page = usePage<
    PageProps & {
        nominasi: NominasiItem[];
        edisiAktifLabel: string;
        bolehEdit?: boolean;
        batasPengumpulan?: string | null;
    }
>();

const edisiLabel = computed(() => page.props.edisiAktifLabel ?? "-");
const nominasiData = ref<NominasiItem[]>(page.props.nominasi ?? []);
const bolehEdit = computed(() => page.props.bolehEdit !== false);
const batasPengumpulan = computed(() => page.props.batasPengumpulan ?? null);

watch(
    () => page.props.nominasi,
    (value) => {
        nominasiData.value = value ?? [];
    },
    { immediate: true },
);

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

const ensureState = (item: NominasiItem) => {
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

const handleLogoChange = (item: NominasiItem, event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    const state = ensureState(item);
    if (state.logoPreview) {
        URL.revokeObjectURL(state.logoPreview);
    }
    state.logo = file;
    state.logoPreview = file ? URL.createObjectURL(file) : null;
    (event.target as HTMLInputElement).value = "";
};

const openEdit = (item: NominasiItem) => {
    ensureState(item);
    modalAttempt[item.id] = false;
    editOpen[item.id] = true;
};

const simpanLink = (item: NominasiItem) => {
    if (!bolehEdit.value) {
        toast.error("Pengumpulan pameran sudah ditutup.");
        return;
    }
    const state = ensureState(item);
    modalAttempt[item.id] = true;
    const missingLogo = !state.logo && !item.pameran_logo_name;
    const missingRingkasan = !state.ringkasan.trim();
    const ringkasanWordCount = state.ringkasan
        ? state.ringkasan.trim().split(/\s+/).filter(Boolean).length
        : 0;
    const ringkasanOver = ringkasanWordCount > 150;
    const missingVideo = !state.linkVideo.trim();
    if (missingLogo || missingRingkasan || missingVideo || ringkasanOver) {
        toast.error(
            ringkasanOver
                ? "Ringkasan maksimal 150 kata."
                : "Lengkapi semua field pameran yang wajib diisi.",
        );
        return;
    }
    state.saving = true;
    router.post(
        `/peserta/pameran-karya/${item.id}`,
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
                toast.success("File pameran disimpan.");
                const target = nominasiData.value.find(
                    (entry) => entry.id === item.id,
                );
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
            onError: () => {
                toast.error("Gagal menyimpan file pameran.");
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
        return `https://img.youtube.com/vi/${id}/hqdefault.jpg`;
    }
    return "";
};
</script>

<template>
    <section class="space-y-4 sm:space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-800">Pameran Karya</h1>
                <p class="text-xs text-slate-500">{{ edisiLabel }}</p>
            </div>
            <p v-if="!bolehEdit" class="text-xs text-rose-600">
                Pengumpulan ditutup.
                <span v-if="batasPengumpulan">Batas: {{ batasPengumpulan }}.</span>
            </p>
        </div>

        <div
            v-if="!nominasiData.length"
            class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
        >
            Belum ada karya yang lolos nominasi pada edisi ini.
        </div>

        <div
            v-else
            class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="item in nominasiData"
                :key="item.id"
                class="rounded-lg border border-slate-200 bg-white overflow-hidden"
            >
                <div
                    class="aspect-[16/9] bg-slate-100 flex items-center justify-center p-1.5"
                >
                    <img
                        v-if="item.pameran_logo_url"
                        :src="item.pameran_logo_url"
                        alt="Logo"
                        class="h-full w-full object-contain"
                    />
                    <span v-else class="text-xs text-slate-400"
                        >Logo belum ada</span
                    >
                </div>

                <div class="p-3 space-y-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-0.5">
                            <h2 class="text-sm font-semibold text-slate-900">
                                {{ item.nama_karya }}
                            </h2>
                            <p class="text-xs text-slate-500">
                                {{ item.nama_kategori }}
                            </p>
                        </div>
                        <Badge
                            :variant="
                                item.pameran_submitted_at
                                    ? 'success'
                                    : 'destructive'
                            "
                        >
                            {{
                                item.pameran_submitted_at
                                    ? "Terkirim"
                                    : "Belum mengirim"
                            }}
                        </Badge>
                    </div>

                    <p class="text-sm text-slate-700 line-clamp-2">
                        {{ item.pameran_ringkasan ?? "Ringkasan belum diisi." }}
                    </p>

                    <div class="flex items-center justify-between gap-2">
                        <div class="text-xs text-slate-500">
                            {{
                                item.pameran_submitted_at
                                    ? `Terakhir diperbarui: ${item.pameran_submitted_at}`
                                    : "Belum mengirim data pameran"
                            }}
                        </div>
                        <a
                            v-if="item.pameran_link_video"
                            :href="item.pameran_link_video"
                            target="_blank"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-600"
                        >
                            Lihat Video
                            <div class="h-10 w-16 rounded bg-white overflow-hidden flex items-center justify-center">
                                <img
                                    v-if="
                                        getVideoPreview(item.pameran_link_video)
                                            .length
                                    "
                                    :src="getVideoPreview(item.pameran_link_video)"
                                    alt="Preview video"
                                    class="h-full w-full object-contain"
                                />
                            </div>
                        </a>
                        <div
                            v-else
                            class="inline-flex items-center justify-center h-10 w-16 rounded border border-dashed border-slate-200 bg-slate-50 text-[10px] text-slate-400"
                        >
                            Video
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        :disabled="!bolehEdit"
                                        @click="openEdit(item)"
                                    >
                                        <SquarePen
                                            v-if="item.pameran_submitted_at"
                                            class="h-4 w-4"
                                        />
                                        <Plus v-else class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{
                                        item.pameran_submitted_at
                                            ? "Edit data pameran"
                                            : "Isi data pameran"
                                    }}
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                <PameranModal
                    v-model:open="editOpen[item.id]"
                    :item="item"
                    :state="ensureState(item)"
                    :attempt="modalAttempt[item.id]"
                    :boleh-edit="bolehEdit"
                    :get-video-preview="getVideoPreview"
                    @logo-change="(e) => handleLogoChange(item, e)"
                    @save="simpanLink(item)"
                />
            </article>
        </div>
    </section>
</template>
