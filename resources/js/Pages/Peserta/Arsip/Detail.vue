<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowLeft, Pencil, Trophy, Users } from "lucide-vue-next";
import PameranModal from "@/Pages/Peserta/Pameran/PameranModal.vue";
import { toast } from "vue-sonner";
import type { PageProps } from "@/types/inertia";

type DetailKarya = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    edisi_label: string;
    status_tampilan: string;
    is_juara?: boolean;
    is_nominasi?: boolean;
    peringkat?: number | null;
    wa_ketua?: string | null;
    dosen_pembimbing?: {
        nik?: string;
        nama?: string;
        email?: string;
        bidang?: string;
    } | null;
    proposal_link?: string | null;
    anggota_tim?: Array<{
        nama?: string;
        nim?: string;
        email?: string;
        peran?: string;
    }>;
    lampiran?: Array<{
        id?: number;
        nama_asli?: string;
        deskripsi?: string;
        url?: string;
    }>;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_link_video: string | null;
    pameran_ringkasan: string | null;
    pameran_submitted_at: string | null;
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Detail Arsip" }, () => page),
});

const page = usePage<PageProps & { karya: DetailKarya }>();
const karya = computed(() => page.props.karya);
const activeTab = ref<"lampiran" | "pameran">("lampiran");

const editOpen = ref(false);
const modalAttempt = ref(false);
const state = reactive({
    logo: null as File | null,
    logoPreview: null as string | null,
    linkVideo: "",
    ringkasan: "",
    saving: false,
});

state.linkVideo = karya.value.pameran_link_video ?? "";
state.ringkasan = karya.value.pameran_ringkasan ?? "";

const back = () => router.get("/peserta/arsip");

const openEdit = () => {
    modalAttempt.value = false;
    editOpen.value = true;
};

const handleLogoChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (state.logoPreview) URL.revokeObjectURL(state.logoPreview);
    state.logo = file;
    state.logoPreview = file ? URL.createObjectURL(file) : null;
    (event.target as HTMLInputElement).value = "";
};

const savePameran = () => {
    modalAttempt.value = true;
    if (!karya.value.is_juara) return;
    const missingLogo = !state.logo && !karya.value.pameran_logo_name;
    const missingVideo = !state.linkVideo.trim();
    if (missingLogo || missingVideo) {
        toast.error("Lengkapi logo dan link video pameran.");
        return;
    }

    state.saving = true;
    router.post(
        `/peserta/pameran-karya/${karya.value.id}`,
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
                toast.success("Data pameran berhasil diperbarui.");
                editOpen.value = false;
                modalAttempt.value = false;
            },
            onError: () => toast.error("Gagal memperbarui data pameran."),
            onFinish: () => {
                state.saving = false;
            },
        },
    );
};

const labelJuara = computed(() => {
    if (!karya.value.is_juara) return "";
    const peringkat = karya.value.peringkat;
    if (peringkat === 1) return "Juara 1";
    if (peringkat === 2) return "Juara 2";
    if (peringkat === 3) return "Juara 3";
    return "Juara";
});
</script>

<template>
    <section class="space-y-6">
        <div class="flex items-center gap-3">
            <Button type="button" variant="outline" size="icon-sm" @click="back">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Detail Arsip Karya</h1>
                <p class="text-sm text-slate-500">
                    Ringkasan data daftar karya dan pameran.
                </p>
            </div>
        </div>

        <Card class="border-slate-200">
            <CardHeader class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge class="bg-slate-100 text-slate-700">
                        {{ karya.edisi_label }}
                    </Badge>
                    <Badge v-if="karya.is_juara" class="bg-violet-50 text-violet-700">
                        <Trophy class="mr-1 h-3.5 w-3.5" />
                        {{ labelJuara }}
                    </Badge>
                    <Badge v-else-if="karya.is_nominasi" class="bg-sky-50 text-sky-700">
                        Lolos Nominasi
                    </Badge>
                    <Badge v-else class="bg-rose-50 text-rose-700">
                        Tidak lolos nominasi
                    </Badge>
                </div>
                <CardTitle class="text-xl text-slate-900">
                    {{ karya.nama_karya }}
                </CardTitle>
                <p class="text-sm text-slate-600">{{ karya.nama_kategori }}</p>
            </CardHeader>

            <CardContent class="space-y-6">
                <div class="rounded-lg border border-slate-200 p-4 space-y-2">
                    <p class="text-sm font-semibold text-slate-900">Data Daftar Karya</p>
                    <p class="text-sm text-slate-600">
                        WA Ketua:
                        <span class="font-medium text-slate-900">
                            {{ karya.wa_ketua ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Dosen pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.nama ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        NIK pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.nik ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Email pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.email ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Bidang pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.bidang ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Proposal:
                        <a
                            v-if="karya.proposal_link"
                            :href="karya.proposal_link"
                            target="_blank"
                            class="font-medium text-indigo-600 hover:underline"
                        >
                            Buka proposal
                        </a>
                        <span v-else class="font-medium text-slate-900">-</span>
                    </p>
                </div>

                <div class="rounded-lg border border-slate-200 p-4 space-y-2">
                    <p class="text-sm font-semibold text-slate-900">Daftar Anggota</p>
                    <div v-if="karya.anggota_tim?.length" class="space-y-2">
                        <div
                            v-for="(anggota, index) in karya.anggota_tim"
                            :key="`anggota-${index}`"
                            class="rounded-md border border-slate-100 bg-slate-50 p-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-slate-900">
                                        {{ anggota.nama ?? "-" }}
                                    </p>
                                    <p class="text-sm text-slate-500">
                                        NIM: {{ anggota.nim ?? "-" }}
                                    </p>
                                </div>
                                <Badge
                                    :class="
                                        anggota.peran === 'ketua'
                                            ? 'bg-indigo-50 text-indigo-700'
                                            : 'bg-slate-100 text-slate-700'
                                    "
                                >
                                    {{ anggota.peran === "ketua" ? "Ketua" : "Anggota" }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">Belum ada anggota tim.</p>
                </div>

                <div class="grid w-full grid-cols-2 gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        :class="activeTab === 'lampiran' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : ''"
                        @click="activeTab = 'lampiran'"
                    >
                        Lampiran
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        :class="activeTab === 'pameran' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : ''"
                        @click="activeTab = 'pameran'"
                    >
                        Pameran Karya
                    </Button>
                </div>

                <div v-if="activeTab === 'lampiran'" class="mt-4">
                    <Card class="border-slate-200">
                        <CardHeader>
                            <CardTitle class="text-base">Lampiran</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="karya.lampiran?.length" class="space-y-2">
                                <a
                                    v-for="lampiran in karya.lampiran"
                                    :key="lampiran.id"
                                    :href="lampiran.url"
                                    target="_blank"
                                    class="block rounded-lg border border-slate-200 px-3 py-2 text-sm text-indigo-600 hover:bg-slate-50"
                                >
                                    {{ lampiran.nama_asli ?? "Lampiran" }}
                                </a>
                            </div>
                            <p v-else class="text-sm text-slate-500">
                                Belum ada lampiran.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div v-else class="mt-4">
                    <Card class="border-slate-200">
                        <CardHeader class="flex flex-row items-center justify-between gap-3">
                            <CardTitle class="text-base">Pameran Karya</CardTitle>
                            <Button
                                v-if="karya.is_juara"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="openEdit"
                            >
                                <Pencil class="mr-2 h-4 w-4" />
                                Edit
                            </Button>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-if="karya.is_nominasi && !karya.is_juara && !karya.pameran_submitted_at"
                                class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                            >
                                Tidak mengumpulkan pameran karya.
                            </div>

                            <div
                                v-else-if="karya.is_juara || karya.pameran_submitted_at"
                                class="space-y-3"
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-lg border border-slate-200 p-4">
                                        <p class="text-xs text-slate-500">Logo</p>
                                        <p class="mt-1 font-medium text-slate-900">
                                            {{ karya.pameran_logo_name ?? "-" }}
                                        </p>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 p-4">
                                        <p class="text-xs text-slate-500">Waktu submit</p>
                                        <p class="mt-1 font-medium text-slate-900">
                                            {{ karya.pameran_submitted_at ?? "-" }}
                                        </p>
                                    </div>
                                </div>
                                <div class="rounded-lg border border-slate-200 p-4 space-y-2">
                                    <p class="text-sm text-slate-600">
                                        Link video:
                                        <a
                                            v-if="karya.pameran_link_video"
                                            :href="karya.pameran_link_video"
                                            target="_blank"
                                            class="font-medium text-indigo-600 hover:underline"
                                        >
                                            Buka tautan
                                        </a>
                                        <span v-else class="font-medium text-slate-900">-</span>
                                    </p>
                                    <p class="text-sm text-slate-600">
                                        Ringkasan:
                                        <span class="font-medium text-slate-900">
                                            {{ karya.pameran_ringkasan ?? "-" }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <p v-else class="text-sm text-slate-500">
                                Data pameran belum tersedia.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </CardContent>
        </Card>

        <PameranModal
            :open="editOpen"
            :item="karya"
            :state="state"
            :attempt="modalAttempt"
            :boleh-edit="true"
            :get-video-preview="() => ''"
            @update:open="editOpen = $event"
            @logo-change="handleLogoChange"
            @save="savePameran"
        />
    </section>
</template>
