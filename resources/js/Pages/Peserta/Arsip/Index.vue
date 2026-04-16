<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import PameranModal from "@/Pages/Peserta/Pameran/PameranModal.vue";
import type { PageProps } from "@/types/inertia";
import { Users } from "lucide-vue-next";

type ArsipKaryaItem = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    jumlah_anggota_tim: number;
    nama_ketua: string | null;
    status_tampilan: string;
    updated_at: string | null;
    edisi_label: string;
};

type ArsipPameranItem = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    edisi_label: string;
    anggota_tim?: Array<{ nama?: string; nim?: string }>;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_link_video: string | null;
    pameran_ringkasan: string | null;
    pameran_submitted_at: string | null;
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Arsip" }, () => page),
});

const page = usePage<
    PageProps & {
        arsipKarya?: ArsipKaryaItem[];
        arsipPameran?: ArsipPameranItem[];
    }
>();

const arsipKarya = computed(() => page.props.arsipKarya ?? []);
const arsipPameran = ref<ArsipPameranItem[]>(page.props.arsipPameran ?? []);
const selectedEdisi = ref("semua");
const activeTab = ref<"karya" | "pameran">("karya");

const edisiOptions = computed(() => {
    const labels = new Set<string>();

    arsipKarya.value.forEach((item) => labels.add(item.edisi_label));
    arsipPameran.value.forEach((item) => labels.add(item.edisi_label));

    return Array.from(labels);
});

const filteredArsipKarya = computed(() => {
    if (selectedEdisi.value === "semua") return arsipKarya.value;
    return arsipKarya.value.filter(
        (item) => item.edisi_label === selectedEdisi.value,
    );
});

const filteredArsipPameran = computed(() => {
    if (selectedEdisi.value === "semua") return arsipPameran.value;
    return arsipPameran.value.filter(
        (item) => item.edisi_label === selectedEdisi.value,
    );
});

watch(
    () => page.props.arsipPameran,
    (value) => {
        arsipPameran.value = value ?? [];
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

const ensureState = (item: ArsipPameranItem) => {
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

const handleLogoChange = (item: ArsipPameranItem, event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    const state = ensureState(item);
    if (state.logoPreview) {
        URL.revokeObjectURL(state.logoPreview);
    }
    state.logo = file;
    state.logoPreview = file ? URL.createObjectURL(file) : null;
    (event.target as HTMLInputElement).value = "";
};

const openEdit = (item: ArsipPameranItem) => {
    ensureState(item);
    modalAttempt[item.id] = false;
    editOpen[item.id] = true;
};

const savePameran = (item: ArsipPameranItem) => {
    const state = ensureState(item);
    modalAttempt[item.id] = true;

    const missingLogo = !state.logo && !item.pameran_logo_name;
    const missingVideo = !state.linkVideo.trim();
    if (missingLogo || missingVideo) {
        toast.error("Lengkapi semua field pameran yang wajib diisi.");
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
                toast.success("Data pameran arsip berhasil diperbarui.");
                const target = arsipPameran.value.find((entry) => entry.id === item.id);
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
                toast.error("Gagal memperbarui data pameran arsip.");
            },
            onFinish: () => {
                state.saving = false;
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
    return id ? `https://img.youtube.com/vi/${id}/hqdefault.jpg` : "";
};

const openKaryaDetail = (id: number) => {
    router.get(`/peserta/daftar-karya/form?karya=${id}`);
};
</script>

<template>
    <section class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Arsip</h1>
                <p class="text-sm text-slate-500">
                    Pilih edisi untuk melihat karya dan pameran arsip.
                </p>
            </div>
            <Select v-model="selectedEdisi">
                <SelectTrigger class="w-full sm:w-[240px] bg-white">
                    <SelectValue placeholder="Pilih Edisi" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua Edisi</SelectItem>
                    <SelectItem
                        v-for="label in edisiOptions"
                        :key="label"
                        :value="label"
                    >
                        {{ label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeTab === 'karya'
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100'
                "
                @click="activeTab = 'karya'"
            >
                Daftar Karya
            </button>
            <button
                type="button"
                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeTab === 'pameran'
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100'
                "
                @click="activeTab = 'pameran'"
            >
                Pameran Karya
            </button>
        </div>

        <div v-if="activeTab === 'karya'" class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Daftar Karya Arsip
                </h2>
                <p class="text-sm text-slate-500">
                    Data ini hanya untuk dilihat kembali.
                </p>
            </div>

            <div
                v-if="!filteredArsipKarya.length"
                class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
            >
                Tidak ada karya pada edisi yang dipilih.
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="item in filteredArsipKarya"
                    :key="item.id"
                    class="rounded-lg border border-slate-200 bg-white p-4"
                >
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-slate-800">
                                    {{ item.nama_karya }}
                                </h3>
                                <p class="truncate text-sm text-slate-600">
                                    {{ item.nama_kategori }}
                                </p>
                            </div>
                            <Badge
                                :class="
                                    item.status_tampilan === 'Lengkap'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-amber-50 text-amber-700'
                                "
                            >
                                {{ item.status_tampilan }}
                            </Badge>
                        </div>

                        <p class="text-xs font-medium text-sky-700">
                            {{ item.edisi_label }}
                        </p>

                        <div class="space-y-1 text-sm text-slate-600">
                            <div class="inline-flex items-center gap-1">
                                <Users class="h-4 w-4 text-slate-500" />
                                <span>{{ item.jumlah_anggota_tim }} anggota tim</span>
                            </div>
                            <p>Ketua: {{ item.nama_ketua ?? "-" }}</p>
                            <p>Diperbarui: {{ item.updated_at ?? "-" }}</p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            class="w-full"
                            @click="openKaryaDetail(item.id)"
                        >
                            Lihat Detail
                        </Button>
                    </div>
                </article>
            </div>
        </div>

        <div v-else class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    Pameran Arsip
                </h2>
                <p class="text-sm text-slate-500">
                    Karya arsip yang lolos nominasi tetap bisa diperbarui data
                    pamerannya.
                </p>
            </div>

            <div
                v-if="!filteredArsipPameran.length"
                class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
            >
                Tidak ada data pameran arsip pada edisi yang dipilih.
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="item in filteredArsipPameran"
                    :key="item.id"
                    class="rounded-lg border border-slate-200 bg-white p-4 space-y-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs text-slate-500">
                                {{ item.nama_kategori }}
                            </p>
                            <p class="text-xs font-medium text-sky-700">
                                {{ item.edisi_label }}
                            </p>
                        </div>
                        <Badge
                            :class="
                                item.pameran_submitted_at
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-amber-50 text-amber-700'
                            "
                        >
                            {{
                                item.pameran_submitted_at
                                    ? "Terkirim"
                                    : "Belum lengkap"
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
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">
                                {{ item.nama_karya }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                {{
                                    item.pameran_ringkasan ??
                                    "Ringkasan karya belum tersedia."
                                }}
                            </p>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        class="w-full"
                        @click="openEdit(item)"
                    >
                        Edit Data Pameran
                    </Button>

                    <PameranModal
                        :open="editOpen[item.id] ?? false"
                        :item="item"
                        :state="ensureState(item)"
                        :attempt="modalAttempt[item.id] ?? false"
                        :boleh-edit="true"
                        :get-video-preview="getVideoPreview"
                        @update:open="editOpen[item.id] = $event"
                        @logo-change="handleLogoChange(item, $event)"
                        @save="savePameran(item)"
                    />
                </article>
            </div>
        </div>
    </section>
</template>
