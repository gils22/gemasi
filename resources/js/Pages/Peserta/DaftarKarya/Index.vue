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
        templateProposalUrl?: string | null;
        templateProposalName?: string | null;
        flash?: {
            karya_id?: number | string | null;
        };
    }
>();

const edisiAktif = computed(() => page.props.edisi?.aktif);
const daftarKategori = computed(() => page.props.daftarKategori ?? []);
const templateProposalUrl = computed(
    () => page.props.templateProposalUrl ?? null,
);
const templateProposalName = computed(
    () => page.props.templateProposalName ?? null,
);
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
            pembimbing.email.trim().length > 0 &&
            pembimbing.bidang.trim().length > 0;
        if (!pembimbingLengkap) return false;

        if (!form.value.anggotaTim.length) return false;

        const semuaLengkap = form.value.anggotaTim.every(
            (item) =>
                item.nim.trim().length > 0 &&
                item.nama.trim().length > 0 &&
                item.email.trim().length > 0,
        );
        if (!semuaLengkap) return false;

        const jumlahKetua = form.value.anggotaTim.filter(
            (item) => item.peran === "ketua",
        ).length;
        if (jumlahKetua !== 1) return false;

        return true;
    }
    return true;
});

const goPrev = () => {
    if (langkahAktif.value > 1) langkahAktif.value--;
};

const saveStepDraft = (step: number) => {
    const payload: Record<string, unknown> = {
        id: form.value.id ?? null,
        step,
    };

    if (step === 1) {
        payload.kategori = form.value.kategori;
        payload.namaKarya = form.value.namaKarya;
        payload.waKetua = form.value.waKetua;
    } else if (step === 2) {
        payload.dosenPembimbing = form.value.dosenPembimbing;
        payload.anggotaTim = form.value.anggotaTim;
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
            toast.success(`Tahap ${step} tersimpan.`);
            if (langkahAktif.value < totalLangkah) langkahAktif.value++;
        },
        onError: (errors) => {
            const pesan = Object.values(errors ?? {})[0] as string | undefined;
            toast.error(pesan ? `${pesan}` : `Gagal menyimpan tahap ${step}.`);
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
        toast.error("Lengkapi field wajib di langkah ini");
        return;
    }

    if ([1, 2].includes(langkahAktif.value)) {
        saveStepDraft(langkahAktif.value);
        return;
    }

    if (langkahAktif.value < totalLangkah) langkahAktif.value++;
};

const submit = () => {
    if (isReadOnly.value) return;

    if (!isLangkahValid.value) {
        toast.error("Lengkapi data sebelum mengirim");
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
                    <Badge v-if="isReadOnly" class="bg-sky-50 text-sky-700">
                        Mode baca
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
                />
                <StepTim
                    v-else-if="langkahAktif === 2"
                    :form="form"
                    :read-only="isReadOnly"
                />
                <StepLampiran
                    v-else
                    :form="form"
                    :read-only="isReadOnly"
                    :template-proposal-url="templateProposalUrl"
                    :template-proposal-name="templateProposalName"
                />

                <CardFooter
                    class="px-0 pt-2 border-t-0 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <Button
                        v-if="isReadOnly"
                        variant="outline"
                        class="w-full sm:w-auto"
                        @click="router.get('/peserta/arsip')"
                    >
                        Kembali ke Arsip
                    </Button>

                    <Button
                        v-else-if="langkahAktif > 1"
                        variant="outline"
                        class="w-full sm:w-auto"
                        @click="goPrev"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Button>

                    <div v-else class="hidden sm:block" />

                    <Button
                        v-if="langkahAktif < totalLangkah"
                        :disabled="
                            (!isReadOnly && !isLangkahValid) || isSavingStep
                        "
                        class="w-full sm:w-auto"
                        @click="goNext"
                    >
                        <span class="sr-only">Lanjut</span>
                        <Spinner v-if="isSavingStep" />
                        <ArrowRight v-else class="w-4 h-4" />
                    </Button>

                    <Button
                        v-else-if="!isReadOnly"
                        :disabled="!isLangkahValid || isSubmitting"
                        class="w-full sm:w-auto"
                        @click="submit"
                    >
                        <Spinner v-if="isSubmitting" class="mr-2" />
                        {{
                            isSubmitting
                                ? "Menyimpan"
                                : isEditMode
                                  ? "Simpan Perubahan"
                                  : "Daftarkan Karya"
                        }}
                    </Button>
                </CardFooter>
            </CardContent>
        </Card>
    </div>
</template>
