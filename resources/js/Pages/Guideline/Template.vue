<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

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
    buildPreviewUrl(form.template_proposal_url ?? "")
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
        <div class="bg-white border rounded-xl p-4 shadow-sm space-y-4">
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
                            {{ page.props.templateProposal.nama || "Lihat" }}
                        </a>
                    </template>
                    <template v-else>
                        Belum ada template tersimpan.
                    </template>
                </div>
            </div>

            <div v-if="templatePreviewUrl" class="space-y-2">
                <label class="text-sm font-medium">Preview</label>
                <div
                    class="rounded-lg border border-slate-200 overflow-hidden bg-white"
                >
                    <iframe
                        :src="templatePreviewUrl"
                        class="w-full h-[420px]"
                        loading="lazy"
                        referrerpolicy="no-referrer"
                    />
                </div>
                <p class="text-xs text-slate-500">
                    Pastikan link dapat diakses publik agar preview tampil.
                </p>
            </div>

            <div class="flex justify-end">
                <Button :disabled="form.processing || !isEditable" @click="submit">
                    {{ form.processing ? "Menyimpan..." : "Simpan Template" }}
                </Button>
            </div>
        </div>
    </div>
</template>
