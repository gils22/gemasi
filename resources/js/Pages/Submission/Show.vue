<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import {
    ArrowLeft,
    CheckCircle2,
    Plus,
    RotateCcw,
    SquarePen,
    Trash2,
} from "lucide-vue-next";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import type { PageProps } from "@/types/inertia";

type Lampiran = {
    id: number;
    nama: string;
    deskripsi: string | null;
    url: string;
};

type Submission = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    status: "draft" | "submitted";
    is_lolos_nominasi: boolean;
    submitted_at: string | null;
    updated_at: string | null;
    wa_ketua: string | null;
    dosen_pembimbing: {
        nik?: string;
        nama?: string;
        email?: string;
        bidang?: string;
    };
    proposal_path: string | null;
    link_tambahan: Array<{ label?: string; url?: string }>;
    nilai_tahap_1: number | null;
    catatan_tahap_1: string | null;
    anggota_tim: Array<{
        nama?: string;
        nim?: string;
        email?: string;
        peran?: string;
    }>;
    peserta: {
        id: number | null;
        name: string | null;
        email: string | null;
        avatar: string | null;
    };
    lampiran: Lampiran[];
};

const page = usePage<
    PageProps & {
        submission: Submission;
        gemasiAktifLabel: string;
        bolehKelola: boolean;
        bolehNilaiTahap1?: boolean;
        bolehLoloskanNominasi?: boolean;
    }
>();
const submission = computed(() => page.props.submission);
const bolehKelola = computed(() => page.props.bolehKelola === true);
const bolehNilaiTahap1 = computed(
    () => page.props.bolehNilaiTahap1 === true || bolehKelola.value,
);
const bolehLoloskanNominasi = computed(
    () => page.props.bolehLoloskanNominasi === true || bolehKelola.value,
);
const tabAktif = ref<"ringkasan" | "tim" | "lampiran">("ringkasan");
const daftarTab = ["ringkasan", "tim", "lampiran"] as const;

const role = computed(() => page.props.auth?.role ?? "admin");
const isPrivileged = computed(() => role.value === "admin");
const routePrefix = computed(() =>
    role.value === "juri" ? "/juri" : "/admin",
);
const nilaiTahap1 = ref<number | null>(null);
const catatanTahap1 = ref("");
const isSavingNilai = ref(false);
const openEditTim = ref(false);
const isSavingTim = ref(false);
const draftAnggota = ref<
    Array<{ nim: string; nama: string; email: string; peran: string }>
>([]);

const resetDraftAnggota = () => {
    const current = submission.value?.anggota_tim ?? [];
    draftAnggota.value = current.map((item) => ({
        nim: item.nim ?? "",
        nama: item.nama ?? "",
        email: item.email ?? "",
        peran: item.peran ?? "anggota",
    }));
};
const backToList = () => {
    const params = new URLSearchParams(window.location.search);
    const from = params.get("from");
    if (from === "nominasi") {
        router.get(`${routePrefix.value}/submission/nominasi`);
        return;
    }

    router.get(`${routePrefix.value}/submission/karya`);
};

const toggleNominasi = () => {
    if (!bolehLoloskanNominasi.value) return;
    if (
        !submission.value.is_lolos_nominasi &&
        submission.value.status !== "submitted"
    ) {
        toast.error("Hanya submission lengkap yang bisa diloloskan.");
        return;
    }
    if (
        !submission.value.is_lolos_nominasi &&
        submission.value.nilai_tahap_1 === null
    ) {
        toast.error("Nilai tahap 1 belum diisi.");
        return;
    }

    const endpoint = submission.value.is_lolos_nominasi
        ? `${routePrefix.value}/submission/${submission.value.id}/batalkan-nominasi`
        : `${routePrefix.value}/submission/${submission.value.id}/lolos-nominasi`;

    router.patch(
        endpoint,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(
                    submission.value.is_lolos_nominasi
                        ? "Nominasi dibatalkan."
                        : "Karya ditandai lolos nominasi.",
                );
            },
            onError: () => {
                toast.error("Gagal memperbarui status nominasi.");
            },
        },
    );
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get("tab");
    if (tab && (daftarTab as readonly string[]).includes(tab)) {
        tabAktif.value = tab as typeof tabAktif.value;
    }
});

watch(tabAktif, (val) => {
    const url = new URL(window.location.href);
    url.searchParams.set("tab", val);
    window.history.replaceState({}, "", url.toString());
});

watch(
    submission,
    (val) => {
        nilaiTahap1.value = val?.nilai_tahap_1 ?? null;
        catatanTahap1.value = val?.catatan_tahap_1 ?? "";
    },
    { immediate: true },
);

watch(openEditTim, (val) => {
    if (val) {
        resetDraftAnggota();
    }
});

const simpanNilaiTahap1 = () => {
    if (!bolehNilaiTahap1.value) return;
    isSavingNilai.value = true;
    router.patch(
        `${routePrefix.value}/submission/${submission.value.id}/nilai-tahap-1`,
        {
            nilai_tahap_1: nilaiTahap1.value,
            catatan_tahap_1: catatanTahap1.value?.trim() || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Nilai tahap 1 tersimpan.");
            },
            onError: () => {
                toast.error("Gagal menyimpan nilai tahap 1.");
            },
            onFinish: () => {
                isSavingNilai.value = false;
            },
        },
    );
};

const tambahAnggota = () => {
    if (draftAnggota.value.length >= 6) return;
    draftAnggota.value.push({
        nim: "",
        nama: "",
        email: "",
        peran: "anggota",
    });
};

const hapusAnggota = (index: number) => {
    if (draftAnggota.value.length <= 1) return;
    draftAnggota.value.splice(index, 1);
};

const simpanAnggota = () => {
    if (!bolehKelola.value) return;
    isSavingTim.value = true;
    router.patch(
        `${routePrefix.value}/submission/${submission.value.id}/anggota-tim`,
        {
            anggota_tim: draftAnggota.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Anggota tim berhasil diperbarui.");
                openEditTim.value = false;
            },
            onError: () => {
                toast.error("Gagal memperbarui anggota tim.");
            },
            onFinish: () => {
                isSavingTim.value = false;
            },
        },
    );
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Detail Submission" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-xl border bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" size="sm" @click="backToList">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <Badge variant="secondary">{{
                        page.props.gemasiAktifLabel
                    }}</Badge>
                </div>
                <Button
                    v-if="bolehLoloskanNominasi"
                    variant="outline"
                    size="sm"
                    :disabled="
                        !submission.is_lolos_nominasi &&
                        (submission.status !== 'submitted' ||
                            submission.nilai_tahap_1 === null)
                    "
                    @click="toggleNominasi"
                >
                    <RotateCcw
                        v-if="submission.is_lolos_nominasi"
                        class="h-4 w-4"
                    />
                    <CheckCircle2 v-else class="h-4 w-4" />
                    {{
                        submission.is_lolos_nominasi
                            ? "Batalkan Nominasi"
                            : "Lolos Nominasi"
                    }}
                </Button>
            </div>
            <h1 class="mt-3 text-xl font-semibold text-slate-900">
                {{ submission.nama_karya }}
            </h1>
            <p class="text-sm text-slate-500">{{ submission.nama_kategori }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-medium"
                :class="
                    tabAktif === 'ringkasan'
                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                        : 'border-slate-200 bg-white text-slate-600'
                "
                @click="tabAktif = 'ringkasan'"
            >
                Ringkasan
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-medium"
                :class="
                    tabAktif === 'tim'
                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                        : 'border-slate-200 bg-white text-slate-600'
                "
                @click="tabAktif = 'tim'"
            >
                Tim
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-medium"
                :class="
                    tabAktif === 'lampiran'
                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                        : 'border-slate-200 bg-white text-slate-600'
                "
                @click="tabAktif = 'lampiran'"
            >
                Lampiran
            </button>
        </div>

        <div v-if="tabAktif === 'ringkasan'" class="grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border bg-white p-4">
                <p class="text-xs text-slate-500">Peserta</p>
                <div class="mt-2 flex items-center gap-3">
                    <Avatar class="h-9 w-9">
                        <AvatarImage
                            :src="
                                submission.peserta.avatar ??
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(submission.peserta.name ?? 'P')}&background=2563eb&color=fff`
                            "
                        />
                        <AvatarFallback>{{
                            (submission.peserta.name ?? "P")
                                .charAt(0)
                                .toUpperCase()
                        }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ submission.peserta.name ?? "-" }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ submission.peserta.email ?? "-" }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="rounded-lg border bg-white p-4 space-y-1">
                <p class="text-xs text-slate-500">Info Pengiriman</p>
                <p class="text-sm text-slate-800">
                    WA Ketua: {{ submission.wa_ketua ?? "-" }}
                </p>
                <p class="text-sm text-slate-800">
                    Dikirim: {{ submission.submitted_at ?? "-" }}
                </p>
                <p class="text-sm text-slate-800">
                    Update: {{ submission.updated_at ?? "-" }}
                </p>
            </div>
            <div class="rounded-lg border bg-white p-4 md:col-span-2 space-y-2">
                <p class="text-xs text-slate-500">Link Karya</p>
                <p class="text-sm">
                    Proposal:
                    <a
                        v-if="submission.proposal_path"
                        :href="submission.proposal_path"
                        target="_blank"
                        class="text-blue-600 hover:underline break-all"
                        >{{ submission.proposal_path }}</a
                    >
                    <span v-else>-</span>
                </p>
                <div class="text-sm space-y-1">
                    <p class="text-slate-800">Link tambahan:</p>
                    <ul
                        v-if="submission.link_tambahan?.length"
                        class="space-y-1"
                    >
                        <li
                            v-for="(item, idx) in submission.link_tambahan"
                            :key="idx"
                        >
                            <a
                                v-if="item.url"
                                :href="item.url"
                                target="_blank"
                                class="text-blue-600 hover:underline break-all"
                            >
                                {{ item.label || item.url }}
                            </a>
                            <span v-else>-</span>
                        </li>
                    </ul>
                    <p v-else class="text-slate-500">-</p>
                </div>
            </div>

        <div
            v-if="bolehNilaiTahap1"
            class="rounded-lg border bg-white p-4 md:col-span-2 space-y-3"
        >
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800">
                        Penilaian Tahap 1
                    </p>
                    <Badge variant="secondary"> 0 - 100 </Badge>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label class="text-xs text-slate-500"> Nilai </label>
                        <Input
                            v-model.number="nilaiTahap1"
                            type="number"
                            min="0"
                            max="100"
                            placeholder="0 - 100"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-slate-500">
                            Catatan (opsional)
                        </label>
                        <textarea
                            v-model="catatanTahap1"
                            rows="3"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                            placeholder="Catatan penilaian tahap 1"
                        />
                    </div>
                </div>
                <div class="flex justify-end">
                    <Button
                        type="button"
                        :disabled="isSavingNilai"
                        @click="simpanNilaiTahap1"
                    >
                        <Spinner v-if="isSavingNilai" class="mr-2" />
                        Simpan Nilai
                    </Button>
                </div>
            </div>
        </div>

        <div v-else-if="tabAktif === 'tim'" class="space-y-3">
            <div class="rounded-lg border bg-white p-4 space-y-2">
                <p class="text-xs">Dosen Pembimbing</p>
                <div
                    class="grid grid-cols-1 md:grid-cols-[1.2fr_1.5fr_2fr_1fr] gap-2"
                >
                    <div class="space-y-1">
                        <label class="text-[11px]">NIK</label>
                        <Input
                            :model-value="
                                submission.dosen_pembimbing?.nik || '-'
                            "
                            disabled
                            class="text-black w-full"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px]">Nama</label>
                        <Input
                            :model-value="
                                submission.dosen_pembimbing?.nama || '-'
                            "
                            disabled
                            class="text-black w-full"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px]">Email</label>
                        <Input
                            :model-value="
                                submission.dosen_pembimbing?.email || '-'
                            "
                            disabled
                            class="text-black w-full"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px]">Bidang</label>
                        <Input
                            :model-value="
                                submission.dosen_pembimbing?.bidang || '-'
                            "
                            disabled
                            class="text-black w-full"
                        />
                    </div>
                </div>
            </div>
            <div class="rounded-lg border bg-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs">Anggota Tim</p>
                    <Button
                        v-if="bolehKelola"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="openEditTim = true"
                        title="Edit anggota tim"
                    >
                        <SquarePen class="h-4 w-4" />
                    </Button>
                </div>
                <div class="space-y-2">
                    <div
                        class="grid grid-cols-1 md:grid-cols-[1.2fr_1.5fr_2fr_1fr] gap-2 text-[11px]"
                    >
                        <span>NIM</span>
                        <span>Nama</span>
                        <span>Email</span>
                        <span>Peran</span>
                    </div>
                    <div
                        v-for="(anggota, idx) in submission.anggota_tim"
                        :key="idx"
                        class="grid grid-cols-1 md:grid-cols-[1.2fr_1.5fr_2fr_1fr] gap-2"
                    >
                        <Input
                            :model-value="anggota.nim ?? '-'"
                            disabled
                            class="text-black w-full"
                        />
                        <Input
                            :model-value="anggota.nama ?? '-'"
                            disabled
                            class="text-black w-full"
                        />
                        <Input
                            :model-value="anggota.email ?? '-'"
                            disabled
                            class="text-black w-full"
                        />
                        <Input
                            :model-value="anggota.peran ?? 'anggota'"
                            disabled
                            class="text-black w-full"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-lg border bg-white p-4">
            <p class="text-xs text-slate-500 mb-2">Lampiran</p>
            <div v-if="submission.lampiran.length" class="space-y-2">
                <a
                    v-for="item in submission.lampiran"
                    :key="item.id"
                    :href="item.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block rounded-md border px-3 py-2 text-sm text-blue-700 hover:bg-slate-50"
                >
                    <p class="font-medium">{{ item.nama }}</p>
                    <p class="text-slate-500">{{ item.deskripsi || "-" }}</p>
                </a>
            </div>
            <p v-else class="text-sm text-slate-500">Tidak ada lampiran.</p>
        </div>

        <Dialog v-model:open="openEditTim">
            <DialogContent class="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Edit Anggota Tim</DialogTitle>
                    <DialogDescription>
                        Perbaiki data anggota tim jika ada kesalahan input.
                        Maksimal 6 anggota.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <div
                        class="grid grid-cols-1 md:grid-cols-[1.2fr_1.5fr_2fr_1fr_auto] gap-2 text-[11px] text-slate-500"
                    >
                        <span>NIM</span>
                        <span>Nama</span>
                        <span>Email</span>
                        <span>Peran</span>
                        <span class="text-right">Aksi</span>
                    </div>
                    <div
                        v-for="(anggota, idx) in draftAnggota"
                        :key="idx"
                        class="grid grid-cols-1 md:grid-cols-[1.2fr_1.5fr_2fr_1fr_auto] gap-2"
                    >
                        <Input v-model="anggota.nim" placeholder="NIM" />
                        <Input v-model="anggota.nama" placeholder="Nama" />
                        <Input v-model="anggota.email" placeholder="Email" />
                        <Input v-model="anggota.peran" placeholder="Peran" />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="md:ml-auto"
                            :disabled="draftAnggota.length <= 1"
                            @click="hapusAnggota(idx)"
                        >
                            <Trash2 class="h-4 w-4 text-rose-600" />
                        </Button>
                    </div>
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="draftAnggota.length >= 6"
                            @click="tambahAnggota"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Anggota
                        </Button>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="openEditTim = false"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        :disabled="isSavingTim"
                        @click="simpanAnggota"
                    >
                        <Spinner v-if="isSavingTim" class="mr-2" />
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
