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
    anggota_tim?: Array<{ nama?: string; nim?: string }>;
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
    const missingVideo = !state.linkVideo.trim();
    if (missingLogo || missingVideo) {
        toast.error(
            "Lengkapi semua field pameran yang wajib diisi.",
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
            class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="item in nominasiData"
                :key="item.id"
                class="rounded-lg border border-slate-200 bg-white overflow-hidden p-4 space-y-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="text-xs text-slate-500">
                        {{ item.nama_kategori }}
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

                <div class="flex items-start gap-3">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-400"
                    >
                        <img
                            v-if="item.pameran_logo_url"
                            :src="item.pameran_logo_url"
                            alt="Logo"
                            class="h-full w-full rounded-lg object-contain p-1"
                        />
                        <span v-else>
                            {{
                                item.nama_karya
                                    ? item.nama_karya.slice(0, 2).toUpperCase()
                                    : "GK"
                            }}
                        </span>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            {{ item.nama_karya }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            {{
                                item.pameran_ringkasan ??
                                "Deskripsi karya belum tersedia."
                            }}
                        </p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-slate-500">Anggota Tim</p>
                    <div
                        v-if="item.anggota_tim?.length"
                        class="space-y-1 text-sm text-slate-800"
                    >
                        <div
                            v-for="(anggota, aidx) in item.anggota_tim"
                            :key="`anggota-${item.id}-${aidx}`"
                            class="flex items-center justify-between gap-2"
                        >
                            <span class="font-medium">
                                {{ anggota.nama ?? "-" }}
                            </span>
                            <span class="text-xs text-slate-500">
                                {{ anggota.nim ?? "" }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-400">
                        Anggota belum terisi.
                    </p>
                </div>

                <a
                    v-if="item.pameran_link_video"
                    :href="item.pameran_link_video"
                    target="_blank"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                >
                    Lihat Video Demo
                </a>
                <Button
                    v-else
                    variant="outline"
                    class="w-full rounded-lg text-xs text-slate-500 border-slate-200"
                    disabled
                >
                    Video Demo Belum Tersedia
                </Button>

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
