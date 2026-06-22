<script setup lang="ts">
import { computed, ref } from "vue";
import { toast } from "vue-sonner";
import { ArrowLeft, ArrowRight } from "lucide-vue-next";
import { router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import {
    Stepper,
    StepperItem,
    StepperSeparator,
} from "@/components/ui/stepper";
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import StepDaftar from "./StepDaftar.vue";
import StepTim from "./StepTim.vue";
import StepLampiran from "./StepLampiran.vue";
import type { FormDaftarKarya } from "./types";
import type { PageProps } from "@/types/inertia";

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Daftar Karya" }, () => page),
});

const langkahAktif = ref(1);
const totalLangkah = 3;
const page = usePage<
    PageProps & {
        daftarKategori?: string[];
        gemasiAktifLabel?: string | null;
        karyaDraft?: Partial<FormDaftarKarya>;
        pendaftaranDibuka?: boolean;
        readOnly?: boolean;
        isArsipReadOnly?: boolean;
        templateProposalUrl?: string | null;
        templateProposalName?: string | null;
        flash?: {
            karya_id?: number | string | null;
        };
    }
>();

const edisiAktif = computed(() => page.props.edisi?.aktif);
const daftarKategori = computed(() => page.props.daftarKategori ?? []);
const pendaftaranDibuka = computed(() => Boolean(page.props.pendaftaranDibuka));
const templateProposalUrl = computed(
    () => page.props.templateProposalUrl ?? null,
);
const templateProposalName = computed(
    () => page.props.templateProposalName ?? null,
);
const isArsipReadOnly = computed(() => Boolean(page.props.isArsipReadOnly));
const gemasiAktifLabel = computed(() => {
    if (page.props.gemasiAktifLabel) return page.props.gemasiAktifLabel;
    if (edisiAktif.value)
        return `${edisiAktif.value.nama} (${edisiAktif.value.tahun})`;
    return "-";
});

const defaultForm = (): FormDaftarKarya => ({
    kategori: "",
    namaKarya: "",
    waKetua: "",
    dosenPembimbing: {
        nik: "",
        nama: "",
        email: "",
        bidang: "",
    },
    anggotaTim: [
        {
            nim: "",
            nama: "",
            email: "",
            peran: "ketua",
        },
    ],
    lampiran: [
        {
            file: null,
            namaFile: "",
            deskripsi: "",
            url: undefined,
        },
    ],
    proposalLink: "",
    linkTambahan: [
        {
            label: "",
            url: "",
        },
    ],
});

const form = ref<FormDaftarKarya>(defaultForm());
const isSavingStep = ref(false);
const isSubmitting = ref(false);
const isReadOnly = computed(() => Boolean(page.props.readOnly));
const formErrors = computed(() => (page.props as any).errors ?? {});

const karyaDraft = computed(() => page.props.karyaDraft ?? null);
const isEditMode = computed(() => Boolean(form.value.id));
if (karyaDraft.value) {
    form.value = {
        ...defaultForm(),
        ...karyaDraft.value,
        dosenPembimbing: {
            ...defaultForm().dosenPembimbing,
            ...(karyaDraft.value.dosenPembimbing ?? {}),
        },
        anggotaTim:
            karyaDraft.value.anggotaTim &&
            karyaDraft.value.anggotaTim.length > 0
                ? karyaDraft.value.anggotaTim
                : defaultForm().anggotaTim,
        lampiran:
            karyaDraft.value.lampiran && karyaDraft.value.lampiran.length > 0
                ? karyaDraft.value.lampiran
                : defaultForm().lampiran,
        proposalLink: karyaDraft.value.proposalLink ?? "",
        linkTambahan:
            karyaDraft.value.linkTambahan &&
            karyaDraft.value.linkTambahan.length > 0
                ? karyaDraft.value.linkTambahan
                : defaultForm().linkTambahan,
    };
}

const isFileLengkap = computed(() => {
    const draft = (karyaDraft.value as any) ?? {};
    const pageKarya = (page.props as any)?.karya ?? {};

    const draftStatus = String(
        draft.status ?? draft.status_tampilan ?? "",
    ).toLowerCase();
    const pageStatus = String(
        pageKarya.status ?? pageKarya.status_tampilan ?? "",
    ).toLowerCase();
    const formStatus = String(
        (form.value as any)?.status_tampilan ?? "",
    ).toLowerCase();

    if (draftStatus === "submitted" || draftStatus === "lengkap") return true;
    if (pageStatus === "submitted" || pageStatus === "lengkap") return true;
    if (formStatus === "submitted" || formStatus === "lengkap") return true;

    // Fallback: if proposal link exists and we're viewing (readOnly or arsip readOnly), treat as lengkap
    if (
        String(form.value.proposalLink ?? "").trim().length > 0 &&
        (Boolean(page.props.readOnly) ||
            Boolean(page.props.isArsipReadOnly) ||
            (isEditMode.value && pendaftaranDibuka.value))
    ) {
        return true;
    }

    return false;
});

const steps = [
    { id: 1, label: "Daftar", desc: "Data karya dasar" },
    { id: 2, label: "Tim", desc: "Profil tim" },
    { id: 3, label: "Lampiran", desc: "Link pendukung" },
];

const judulLangkah = computed(
    () => steps[langkahAktif.value - 1]?.label ?? "Daftar",
);
const deskripsiLangkah = computed(
    () => steps[langkahAktif.value - 1]?.desc ?? "Data karya dasar",
);

const isLangkahValid = computed(() => {
    if (langkahAktif.value === 1) {
        return (
            form.value.kategori.trim().length > 0 &&
            form.value.namaKarya.trim().length > 0 &&
            form.value.waKetua.trim().length > 0
        );
    }
    if (langkahAktif.value === 2) {
        const pembimbing = form.value.dosenPembimbing;
        const pembimbingLengkap =
            pembimbing.nama.trim().length > 0 &&
            pembimbing.nik.trim().length > 0 &&
            /^[0-9]+$/.test(pembimbing.nik.trim()) &&
            /\S+@\S+\.\S+/.test(pembimbing.email.trim()) &&
            pembimbing.bidang.trim().length > 0;
        if (!pembimbingLengkap) return false;

        if (!form.value.anggotaTim.length) return false;

        const semuaLengkap = form.value.anggotaTim.every(
            (item) =>
                item.nim.trim().length > 0 &&
                /^[0-9]+$/.test(item.nim.trim()) &&
                item.nama.trim().length > 0 &&
                /\S+@\S+\.\S+/.test(item.email.trim()),
        );
        if (!semuaLengkap) return false;

        const jumlahKetua = form.value.anggotaTim.filter(
            (item) => item.peran === "ketua",
        ).length;
        if (jumlahKetua !== 1) return false;

        return true;
    }
    return form.value.proposalLink.trim().length > 0;
});

const validationMessage = computed(() => {
    if (isReadOnly.value) return "";

    if (langkahAktif.value === 1) {
        if (!form.value.kategori.trim()) return "Kategori karya wajib diisi.";
        if (!form.value.namaKarya.trim()) return "Nama karya wajib diisi.";
        if (!form.value.waKetua.trim()) return "Nomor WA wajib diisi.";
        if (!/^[0-9+\-\s]+$/.test(form.value.waKetua.trim()))
            return "Nomor WA harus angka.";
        return "";
    }

    if (langkahAktif.value === 2) {
        const pembimbing = form.value.dosenPembimbing;
        if (!pembimbing.nik.trim()) return "NIK dosen pembimbing wajib diisi.";
        if (!/^[0-9]+$/.test(pembimbing.nik.trim()))
            return "NIK hanya boleh angka.";
        if (!pembimbing.nama.trim())
            return "Nama dosen pembimbing wajib diisi.";
        if (!pembimbing.email.trim())
            return "Email dosen pembimbing wajib diisi.";
        if (!/\S+@\S+\.\S+/.test(pembimbing.email.trim()))
            return "Email harus valid.";
        if (!pembimbing.bidang.trim())
            return "Bidang pembimbingan wajib diisi.";
        if (!form.value.anggotaTim.length)
            return "Minimal satu anggota tim wajib diisi.";

        for (const anggota of form.value.anggotaTim) {
            if (!anggota.nim.trim()) return "NIM anggota wajib diisi.";
            if (!/^[0-9]+$/.test(anggota.nim.trim()))
                return "NIM hanya boleh angka.";
            if (!anggota.nama.trim()) return "Nama anggota wajib diisi.";
            if (!anggota.email.trim()) return "Email anggota wajib diisi.";
            if (!/\S+@\S+\.\S+/.test(anggota.email.trim()))
                return "Email harus valid.";
        }

        const jumlahKetua = form.value.anggotaTim.filter(
            (item) => item.peran === "ketua",
        ).length;
        if (jumlahKetua !== 1) return "Harus ada tepat satu ketua tim.";
        return "";
    }

    if (!form.value.proposalLink.trim()) return "Proposal wajib diisi.";
    return "";
});

const goPrev = () => {
    if (langkahAktif.value > 1) langkahAktif.value--;
};

const saveStepDraft = (step: number, advanceStep = false) => {
    // Persist only fields that are filled, regardless of current step.
    const payload: Record<string, unknown> = {
        id: form.value.id ?? null,
        step,
    };

    // Step 1 fields
    if (String(form.value.kategori ?? "").trim().length > 0)
        payload.kategori = form.value.kategori;
    if (String(form.value.namaKarya ?? "").trim().length > 0)
        payload.namaKarya = form.value.namaKarya;
    if (String(form.value.waKetua ?? "").trim().length > 0)
        payload.waKetua = form.value.waKetua;

    // Step 2 fields
    const pembimbing = form.value.dosenPembimbing;
    if (
        pembimbing &&
        (String(pembimbing.nik ?? "").trim().length > 0 ||
            String(pembimbing.nama ?? "").trim().length > 0 ||
            String(pembimbing.email ?? "").trim().length > 0 ||
            String(pembimbing.bidang ?? "").trim().length > 0)
    )
        payload.dosenPembimbing = pembimbing;

    if (
        Array.isArray(form.value.anggotaTim) &&
        form.value.anggotaTim.length > 0
    )
        payload.anggotaTim = form.value.anggotaTim;

    // Step 3 fields
    if (String(form.value.proposalLink ?? "").trim().length > 0)
        payload.proposalLink = form.value.proposalLink;

    if (Array.isArray(form.value.linkTambahan)) {
        const filtered = form.value.linkTambahan.filter(
            (i) => String(i?.url ?? "").trim().length > 0,
        );
        if (filtered.length) payload.linkTambahan = filtered;
    }

    // Lampiran: include if any meaningful data present
    if (Array.isArray(form.value.lampiran)) {
        const anyLampiran = form.value.lampiran.some((l) => {
            return (
                l?.file != null ||
                String(l?.namaFile ?? "").trim().length > 0 ||
                String(l?.url ?? "").trim().length > 0 ||
                String(l?.deskripsi ?? "").trim().length > 0
            );
        });
        if (anyLampiran) payload.lampiran = form.value.lampiran;
    }

    isSavingStep.value = true;
    router.post("/peserta/daftar-karya/draft", payload, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (pageData: any) => {
            const karyaId = Number(pageData?.props?.flash?.karya_id ?? 0);
            if (karyaId > 0) {
                form.value.id = karyaId;
            }
            toast.success(`Draft tersimpan.`);
            // Redirect to daftar karya (list) after successful draft save
            router.get("/peserta/daftar-karya");
        },
        onError: (errors) => {
            const pesan = Object.values(errors ?? {})[0] as string | undefined;
            toast.error(pesan ? `${pesan}` : `Gagal menyimpan draft.`);
        },
        onFinish: () => {
            isSavingStep.value = false;
        },
    });
};

const goNext = () => {
    if (isReadOnly.value) {
        if (langkahAktif.value < totalLangkah) langkahAktif.value++;
        return;
    }

    if (!isLangkahValid.value) {
        return;
    }

    if (langkahAktif.value < totalLangkah) langkahAktif.value++;
};

const simpanDraft = () => {
    if (isReadOnly.value) return;
    if (!isLangkahValid.value) {
        return;
    }

    if ([1, 2, 3].includes(langkahAktif.value)) {
        saveStepDraft(langkahAktif.value, false);
    }
};

const submit = () => {
    if (isReadOnly.value) return;

    if (!isLangkahValid.value) {
        return;
    }

    isSubmitting.value = true;
    router.post("/peserta/daftar-karya", form.value as any, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                isEditMode.value
                    ? "Perubahan karya berhasil disimpan."
                    : "Karya berhasil didaftarkan.",
            );
        },
        onError: (errors) => {
            const pesan = Object.values(errors ?? {})[0] as string | undefined;
            toast.error(pesan ? `${pesan}` : "Gagal menyimpan data karya.");
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-3 sm:space-y-4">
        <Card class="py-0 gap-0 border-slate-200">
            <CardContent class="px-4 sm:px-6 py-4">
                <div class="flex items-center gap-2 min-w-0">
                    <Badge variant="secondary" class="max-w-full truncate">
                        {{ gemasiAktifLabel }}
                    </Badge>
                    <Badge
                        v-if="isReadOnly && !isArsipReadOnly"
                        class="bg-sky-50 text-sky-700"
                    >
                        Anggota view only
                    </Badge>
                </div>

                <Stepper class="mt-4">
                    <template v-for="(step, index) in steps" :key="step.id">
                        <StepperItem
                            :step="step.id"
                            :active="step.id === langkahAktif"
                            :completed="step.id < langkahAktif"
                            @click="
                                (isReadOnly || step.id <= langkahAktif) &&
                                (langkahAktif = step.id)
                            "
                        >
                            <template #title>{{ step.label }}</template>
                            <template #description>{{ step.desc }}</template>
                        </StepperItem>
                        <StepperSeparator v-if="index < steps.length - 1" />
                    </template>
                </Stepper>
            </CardContent>
        </Card>

        <Card class="py-0 gap-0 border-slate-200">
            <CardHeader
                class="px-4 sm:px-6 pt-4 pb-3 border-b border-slate-100"
            >
                <CardTitle class="text-lg sm:text-xl text-slate-800">
                    {{ judulLangkah }}
                </CardTitle>
                <p class="text-sm text-slate-500 mt-1">
                    {{ deskripsiLangkah }}
                </p>
            </CardHeader>

            <CardContent class="px-4 sm:px-6 py-4 space-y-5">
                <StepDaftar
                    v-if="langkahAktif === 1"
                    :form="form"
                    :daftar-kategori="daftarKategori"
                    :read-only="isReadOnly"
                    :errors="formErrors"
                />
                <StepTim
                    v-else-if="langkahAktif === 2"
                    :form="form"
                    :read-only="isReadOnly"
                    :errors="formErrors"
                />
                <StepLampiran
                    v-else
                    :form="form"
                    :read-only="isReadOnly"
                    :template-proposal-url="templateProposalUrl"
                    :template-proposal-name="templateProposalName"
                    :errors="formErrors"
                />

                <CardFooter
                    class="px-0 pt-2 border-t-0 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3"
                >
                    <Button
                        v-if="isArsipReadOnly"
                        variant="outline"
                        class="w-full sm:w-auto"
                        @click="router.get('/peserta/arsip')"
                    >
                        Kembali ke Arsip
                    </Button>

                    <div class="flex w-full justify-start sm:w-auto">
                        <Button
                            v-if="langkahAktif > 1"
                            variant="outline"
                            class="w-full sm:w-auto"
                            @click="goPrev"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            <span>Kembali</span>
                        </Button>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-2 sm:flex-row sm:gap-3 sm:justify-end"
                    >
                        <Button
                            v-if="
                                !isReadOnly &&
                                !isArsipReadOnly &&
                                langkahAktif <= totalLangkah &&
                                !isFileLengkap
                            "
                            variant="outline"
                            class="w-full sm:w-auto"
                            :disabled="!isLangkahValid || isSavingStep"
                            @click="simpanDraft"
                        >
                            <Spinner v-if="isSavingStep" class="h-4 w-4" />
                            <span v-else>Simpan Draft</span>
                        </Button>

                        <Button
                            v-if="langkahAktif < totalLangkah"
                            :disabled="
                                (!isReadOnly && !isLangkahValid) || isSavingStep
                            "
                            class="w-full sm:w-auto"
                            @click="goNext"
                        >
                            <template v-if="isSavingStep">
                                <Spinner />
                            </template>
                            <template v-else>
                                <span>Lanjut</span>
                                <ArrowRight class="w-4 h-4" />
                            </template>
                        </Button>

                        <Button
                            v-else-if="!isReadOnly"
                            :disabled="!isLangkahValid || isSubmitting"
                            class="w-full sm:w-auto"
                            @click="submit"
                        >
                            <Spinner v-if="isSubmitting" class="h-4 w-4" />
                            <span v-else>
                                {{
                                    isEditMode || isFileLengkap
                                        ? "Simpan Perubahan"
                                        : "Kirim"
                                }}
                            </span>
                        </Button>
                    </div>
                </CardFooter>
            </CardContent>
        </Card>
    </div>
</template>
