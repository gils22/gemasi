<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    aktif: boolean;
};

const page = usePage<{
    edisiAktif: Edisi;
    isEditable: boolean;
    basePath: string;
    templateProposal: {
        nama: string | null;
        url: string | null;
    };
}>();

const edisiAktif = computed(() => page.props.edisiAktif);
const isEditable = computed(() => page.props.isEditable === true);
const basePath = computed(() => page.props.basePath || "/admin");

const form = useForm({
    edisi_id: edisiAktif.value.id,
    template_proposal_name: page.props.templateProposal?.nama ?? "",
    template_proposal_url: page.props.templateProposal?.url ?? "",
});

watch(
    () => page.props.edisiAktif?.id,
    () => {
        form.defaults({
            edisi_id: edisiAktif.value.id,
            template_proposal_name: page.props.templateProposal?.nama ?? "",
            template_proposal_url: page.props.templateProposal?.url ?? "",
        });
        form.reset();
        form.clearErrors();
    },
);

const buildPreviewUrl = (raw: string) => {
    const trimmed = raw.trim();
    if (!trimmed) return "";

    try {
        const url = new URL(trimmed);
        if (!url.hostname.includes("docs.google.com")) return trimmed;

        if (url.pathname.includes("/document/d/")) {
            const parts = url.pathname.split("/");
            const idIndex = parts.findIndex((p) => p === "d");
            const docId = idIndex >= 0 ? parts[idIndex + 1] : "";
            if (docId) {
                return `https://docs.google.com/document/d/${docId}/preview`;
            }
        }

        if (url.pathname.includes("/spreadsheets/d/")) {
            const parts = url.pathname.split("/");
            const idIndex = parts.findIndex((p) => p === "d");
            const docId = idIndex >= 0 ? parts[idIndex + 1] : "";
            if (docId) {
                return `https://docs.google.com/spreadsheets/d/${docId}/preview`;
            }
        }

        if (url.pathname.includes("/presentation/d/")) {
            const parts = url.pathname.split("/");
            const idIndex = parts.findIndex((p) => p === "d");
            const docId = idIndex >= 0 ? parts[idIndex + 1] : "";
            if (docId) {
                return `https://docs.google.com/presentation/d/${docId}/preview`;
            }
        }

        return trimmed;
    } catch {
        return trimmed;
    }
};

const templatePreviewUrl = computed(() =>
    buildPreviewUrl(form.template_proposal_url ?? ""),
);

const submit = () => {
    if (!isEditable.value) return;

    form.transform((data) => ({
        edisi_id: edisiAktif.value.id,
        template_proposal_name: data.template_proposal_name?.trim() || null,
        template_proposal_url: data.template_proposal_url?.trim() || null,
    })).put(`${basePath.value}/guideline/template`, {
        preserveScroll: true,
        onSuccess: () => toast.success("Template proposal tersimpan"),
        onError: () => toast.error("Gagal menyimpan template"),
    });
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Guideline" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- LEFT -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold text-slate-800">
                            Template Proposal
                        </h2>
                    </div>

                    <div v-if="!isEditable" class="text-xs text-amber-700">
                        Edisi ini tidak aktif. Data hanya bisa dibaca.
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            Nama file untuk peserta
                        </label>

                        <Input
                            v-model="form.template_proposal_name"
                            :disabled="!isEditable"
                            placeholder="Contoh: Template Proposal GEMASI 2026"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            Link template proposal (Google Docs)
                        </label>

                        <Input
                            v-model="form.template_proposal_url"
                            :disabled="!isEditable"
                            placeholder="https://docs.google.com/document/d/.../edit"
                        />

                        <div class="text-xs text-slate-500">
                            <template v-if="page.props.templateProposal?.url">
                                Template tersimpan:
                                <a
                                    :href="page.props.templateProposal.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-slate-700 underline"
                                >
                                    {{
                                        page.props.templateProposal.nama ||
                                        "Lihat"
                                    }}
                                </a>
                            </template>

                            <template v-else>
                                Belum ada template tersimpan.
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button
                            :disabled="form.processing || !isEditable"
                            @click="submit"
                        >
                            <Spinner v-if="form.processing" class="h-4 w-4" />

                            <span v-else> Simpan Template </span>
                        </Button>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium">
                            Preview Template
                        </label>

                        <a
                            v-if="templatePreviewUrl"
                            :href="templatePreviewUrl"
                            target="_blank"
                            class="text-xs text-slate-600 underline"
                        >
                            Buka Preview
                        </a>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 min-h-[500px]"
                    >
                        <iframe
                            v-if="templatePreviewUrl"
                            :src="templatePreviewUrl"
                            class="w-full h-[500px]"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                        />

                        <div
                            v-else
                            class="h-[500px] flex items-center justify-center text-sm text-slate-400"
                        >
                            Preview template akan tampil di sini
                        </div>
                    </div>

                    <p class="text-xs text-slate-500">
                        Pastikan link dapat diakses publik agar preview tampil.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
