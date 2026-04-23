<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { SquarePen, Upload } from "lucide-vue-next";
import { Spinner } from "@/components/ui/spinner";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import type { PageProps } from "@/types/inertia";

type WinnerItem = {
    id: number;
    karya_id: number;
    edisi_id: number;
    edisi_label: string | null;
    peringkat: number;
    nama_karya: string | null;
    nama_kategori: string | null;
    nilai_final?: number | null;
    anggota_tim?: Array<{ nama?: string; nim?: string }>;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_link_video: string | null;
    pameran_ringkasan: string | null;
    pameran_submitted_at: string | null;
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Juara" }, () => page),
});

const page = usePage<
    PageProps & {
        pemenang: WinnerItem[];
        bolehEdit?: boolean;
        batasPengumpulan?: string | null;
    }
>();

const pemenangData = ref<WinnerItem[]>(page.props.pemenang ?? []);
const bolehEdit = computed(() => page.props.bolehEdit !== false);
const batasPengumpulan = computed(() => page.props.batasPengumpulan ?? null);

watch(
    () => page.props.pemenang,
    (value) => {
        pemenangData.value = value ?? [];
    },
    { immediate: true },
);

const logoInputRefs = reactive<Record<number, HTMLInputElement | null>>({});
const quickLogoSaving = reactive<Record<number, boolean>>({});
const editMode = reactive<Record<number, boolean>>({});
const editSaving = reactive<Record<number, boolean>>({});
const editDraft = reactive<
    Record<number, { linkVideo: string; ringkasan: string }>
>({});

const openLogoPicker = (item: WinnerItem) => {
    const input = logoInputRefs[item.karya_id];
    if (!input) return;
    input.click();
};

const quickGantiLogo = (item: WinnerItem, event: Event) => {
    if (!bolehEdit.value) {
        toast.error("Edit sedang dinonaktifkan.");
        return;
    }
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (!file) return;

    quickLogoSaving[item.karya_id] = true;
    const preview = URL.createObjectURL(file);

    router.post(
        `/peserta/juara/${item.karya_id}`,
        {
            pameran_logo: file,
            _method: "patch",
        },
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.success("Logo berhasil diperbarui.");
                const target = pemenangData.value.find(
                    (entry) => entry.karya_id === item.karya_id,
                );
                if (target) {
                    target.pameran_logo_url = preview;
                    target.pameran_logo_name = file.name;
                }
            },
            onError: () => {
                toast.error("Gagal memperbarui logo.");
                URL.revokeObjectURL(preview);
            },
            onFinish: () => {
                quickLogoSaving[item.karya_id] = false;
                (event.target as HTMLInputElement).value = "";
            },
        },
    );
};

const startEdit = (item: WinnerItem) => {
    if (!bolehEdit.value) {
        toast.error("Edit sedang dinonaktifkan.");
        return;
    }
    editDraft[item.karya_id] = {
        linkVideo: item.pameran_link_video ?? "",
        ringkasan: item.pameran_ringkasan ?? "",
    };
    editMode[item.karya_id] = true;
};

const normalizeUrl = (url: string) =>
    url.trim().replace(/^http:\/\//, "https://");

const extractYoutubeId = (url: string) => {
    const normalized = normalizeUrl(url);
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

const isEditing = (karyaId: number) => editMode[karyaId] === true;
const draftFor = (item: WinnerItem) =>
    editDraft[item.karya_id] ?? {
        linkVideo: item.pameran_link_video ?? "",
        ringkasan: item.pameran_ringkasan ?? "",
    };

const youtubeIdFor = (item: WinnerItem) =>
    extractYoutubeId(draftFor(item).linkVideo);
const canPlayInline = (item: WinnerItem) => Boolean(youtubeIdFor(item));
const videoEmbedUrlFor = (item: WinnerItem) =>
    canPlayInline(item)
        ? `https://www.youtube-nocookie.com/embed/${youtubeIdFor(item)}?rel=0`
        : null;

const cancelEdit = (item: WinnerItem) => {
    editMode[item.karya_id] = false;
    delete editDraft[item.karya_id];
};

const saveEdit = (item: WinnerItem) => {
    if (!bolehEdit.value) {
        toast.error("Edit sedang dinonaktifkan.");
        return;
    }
    const draft = draftFor(item);
    if (!draft.linkVideo.trim()) {
        toast.error("Link video wajib diisi.");
        return;
    }

    editSaving[item.karya_id] = true;
    router.post(
        `/peserta/juara/${item.karya_id}`,
        {
            pameran_link_video: draft.linkVideo.trim() || null,
            pameran_ringkasan: draft.ringkasan.trim() || null,
            _method: "patch",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Perubahan disimpan.");
                const target = pemenangData.value.find(
                    (entry) => entry.karya_id === item.karya_id,
                );
                if (target) {
                    target.pameran_link_video = draft.linkVideo.trim() || null;
                    target.pameran_ringkasan = draft.ringkasan.trim() || null;
                }
                cancelEdit(item);
            },
            onError: () => {
                toast.error("Gagal menyimpan perubahan.");
            },
            onFinish: () => {
                editSaving[item.karya_id] = false;
            },
        },
    );
};
</script>

<template>
    <section class="space-y-4 sm:space-y-5">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-semibold text-slate-800">
                    Daftar Karya Pemenang
                </h1>
            </div>
            <p v-if="!bolehEdit" class="text-xs text-rose-600">
                Edit sedang dinonaktifkan.
                <span v-if="batasPengumpulan"
                    >Batas: {{ batasPengumpulan }}.</span
                >
            </p>
        </div>

        <div
            v-if="!pemenangData.length"
            class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
        >
            Belum ada karya Anda yang masuk juara pada edisi ini.
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
            <article
                v-for="item in pemenangData"
                :key="item.id"
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <h2
                                    class="text-base font-semibold text-slate-900 truncate"
                                >
                                    {{ item.nama_karya ?? "-" }}
                                </h2>
                                <Badge
                                    class="bg-violet-50 text-violet-700 shrink-0"
                                >
                                    Juara {{ item.peringkat }}
                                </Badge>
                            </div>
                            <p class="text-sm text-slate-600 truncate">
                                {{ item.nama_kategori ?? "-" }}
                            </p>
                            <p
                                v-if="item.edisi_label"
                                class="mt-1 text-xs text-slate-500 truncate"
                            >
                                {{ item.edisi_label }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <template v-if="isEditing(item.karya_id)">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="editSaving[item.karya_id] === true"
                                @click="cancelEdit(item)"
                            >
                                Batal
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                :disabled="editSaving[item.karya_id] === true"
                                @click="saveEdit(item)"
                            >
                                <Spinner
                                    v-if="editSaving[item.karya_id] === true"
                                    class="mr-2"
                                />
                                Simpan
                            </Button>
                        </template>

                        <TooltipProvider v-else>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        :disabled="!bolehEdit"
                                        @click="startEdit(item)"
                                    >
                                        <SquarePen class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    Edit informasi juara
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                <div
                    class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-[280px_1fr]"
                >
                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs text-slate-500">Logo</p>
                            <div class="flex items-center gap-2">
                                <input
                                    :ref="
                                        (el) =>
                                            (logoInputRefs[item.karya_id] =
                                                el as HTMLInputElement | null)
                                    "
                                    type="file"
                                    accept=".jpg,.jpeg,.png"
                                    :disabled="!bolehEdit"
                                    class="hidden"
                                    @change="(e) => quickGantiLogo(item, e)"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="
                                        !bolehEdit ||
                                        quickLogoSaving[item.karya_id] === true
                                    "
                                    @click="openLogoPicker(item)"
                                >
                                    <Upload class="h-4 w-4" />
                                    Ganti Logo
                                </Button>
                            </div>
                        </div>

                        <div
                            class="mt-2 flex w-full aspect-square items-center justify-center rounded-lg border border-slate-200 bg-slate-50 overflow-hidden"
                        >
                            <img
                                v-if="item.pameran_logo_url"
                                :src="item.pameran_logo_url"
                                alt="Logo"
                                class="h-full w-full object-contain p-4"
                            />
                            <span v-else class="text-xs text-slate-400">-</span>
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-lg border border-slate-200 bg-white p-3"
                            >
                                <p class="text-xs text-slate-500">Video</p>
                                <template v-if="isEditing(item.karya_id)">
                                    <Input
                                        v-model="
                                            editDraft[item.karya_id].linkVideo
                                        "
                                        class="mt-2"
                                        placeholder="https://..."
                                    />
                                </template>
                                <div class="mt-2">
                                    <div
                                        v-if="videoEmbedUrlFor(item)"
                                        class="overflow-hidden rounded-xl border border-slate-200 bg-white"
                                    >
                                        <iframe
                                            :src="
                                                videoEmbedUrlFor(item) ??
                                                undefined
                                            "
                                            title="Video Demo"
                                            class="aspect-video w-full"
                                            allow="
                                                accelerometer;
                                                autoplay;
                                                clipboard-write;
                                                encrypted-media;
                                                gyroscope;
                                                picture-in-picture;
                                            "
                                            allowfullscreen
                                        />
                                    </div>

                                    <a
                                        v-else-if="
                                            draftFor(item).linkVideo.trim()
                                        "
                                        :href="draftFor(item).linkVideo.trim()"
                                        target="_blank"
                                        class="inline-flex text-xs font-semibold text-slate-600 hover:text-slate-900"
                                    >
                                        Buka link video
                                    </a>

                                    <p
                                        v-else
                                        class="mt-2 text-sm text-slate-500"
                                    >
                                        Link video belum diisi.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-lg border border-slate-200 bg-white p-3"
                            >
                                <p class="text-xs text-slate-500">
                                    Anggota Tim
                                </p>
                                <div
                                    v-if="item.anggota_tim?.length"
                                    class="mt-2 space-y-1"
                                >
                                    <div
                                        v-for="(
                                            anggota, idx
                                        ) in item.anggota_tim"
                                        :key="`anggota-${item.karya_id}-${idx}`"
                                        class="flex items-center justify-between gap-3 text-sm text-slate-700"
                                    >
                                        <span class="font-medium">
                                            {{ anggota.nama ?? "-" }}
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            {{ anggota.nim ?? "" }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="mt-2 text-sm text-slate-500">
                                    Anggota tim belum tersedia.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="text-xs text-slate-500">Ringkasan</p>
                            <template v-if="isEditing(item.karya_id)">
                                <textarea
                                    v-model="editDraft[item.karya_id].ringkasan"
                                    rows="4"
                                    class="mt-2 w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                                    placeholder="Ringkasan karya..."
                                />
                            </template>
                            <p
                                v-else
                                class="mt-2 text-sm text-slate-700 break-words whitespace-pre-line"
                            >
                                {{
                                    item.pameran_ringkasan ??
                                    "Ringkasan belum diisi."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
