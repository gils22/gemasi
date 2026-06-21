<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { ExternalLink, Info, X } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import type { FormDaftarKarya } from "./types";

const props = defineProps<{
    form: FormDaftarKarya;
    templateProposalUrl?: string | null;
    templateProposalName?: string | null;
    readOnly?: boolean;
    errors?: Record<string, string | undefined>;
}>();

const buildCopyUrl = (raw: string) => {
    const trimmed = raw?.trim();
    if (!trimmed) return "";

    try {
        const url = new URL(trimmed);
        if (!url.hostname.includes("docs.google.com")) return trimmed;

        if (url.pathname.includes("/document/d/")) {
            const parts = url.pathname.split("/");
            const idIndex = parts.findIndex((p) => p === "d");
            const docId = idIndex >= 0 ? parts[idIndex + 1] : "";
            if (docId) {
                return `https://docs.google.com/document/d/${docId}/copy`;
            }
        }

        if (url.pathname.includes("/spreadsheets/d/")) {
            const parts = url.pathname.split("/");
            const idIndex = parts.findIndex((p) => p === "d");
            const docId = idIndex >= 0 ? parts[idIndex + 1] : "";
            if (docId) {
                return `https://docs.google.com/spreadsheets/d/${docId}/copy`;
            }
        }

        if (url.pathname.includes("/presentation/d/")) {
            const parts = url.pathname.split("/");
            const idIndex = parts.findIndex((p) => p === "d");
            const docId = idIndex >= 0 ? parts[idIndex + 1] : "";
            if (docId) {
                return `https://docs.google.com/presentation/d/${docId}/copy`;
            }
        }

        return trimmed;
    } catch {
        return trimmed;
    }
};

const buildPreviewUrl = (raw: string) => {
    const trimmed = raw?.trim();
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

const templateCopyUrl = computed(() =>
    buildCopyUrl(props.templateProposalUrl ?? ""),
);
const templatePreviewUrl = computed(() =>
    buildPreviewUrl(props.templateProposalUrl ?? ""),
);
const proposalPreviewUrl = computed(() =>
    buildPreviewUrl(props.form.proposalLink ?? ""),
);

const proposalTerisi = computed(
    () => String(props.form.proposalLink ?? "").trim().length > 0,
);

const touched = reactive({
    proposalLink: false,
});

const proposalError = computed(() => {
    if (!touched.proposalLink) return "";
    if (props.errors?.proposalLink) return props.errors.proposalLink;
    if (!proposalTerisi.value) return "Proposal wajib diisi.";
    return "";
});

const linkTambahanText = computed({
    get: () =>
        props.form.linkTambahan
            .map((item) => item.url.trim())
            .filter((value) => value.length > 0)
            .join("\n"),
    set: (value: string) => {
        const urls = value
            .split("\n")
            .map((item) => item.trim())
            .filter((item) => item.length > 0);

        props.form.linkTambahan = urls.length
            ? urls.map((url) => ({ label: "", url }))
            : [{ label: "", url: "" }];
    },
});

const openTemplatePreview = ref(false);
const openProposalPreview = ref(false);
const showInfo = ref(true);
const templatePreviewBlocked = ref(false);
const proposalPreviewBlocked = ref(false);

const markTemplatePreview = () => {
    templatePreviewBlocked.value = true;
};

const markProposalPreview = () => {
    proposalPreviewBlocked.value = true;
};

const openTemplate = () => {
    templatePreviewBlocked.value = false;
    openTemplatePreview.value = true;
};

const openProposal = () => {
    proposalPreviewBlocked.value = false;
    openProposalPreview.value = true;
};
</script>

<template>
    <div class="space-y-5">
        <div
            v-if="showInfo"
            class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-sky-900"
        >
            <div class="flex items-start justify-between gap-3 text-sm">
                <div class="flex items-start gap-2">
                    <Info class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Proposal wajib diunggah melalui link Google Docs/Drive
                        menggunakan template yang tersedia. Lampiran pendukung
                        seperti gambar, PDF, PPT, video, website, Figma, atau
                        dokumen lainnya dapat dicantumkan pada Link Lampiran.
                        Pastikan setiap link dapat diakses oleh juri dengan
                        pengaturan akses "Anyone with the link".
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-md p-1 text-sky-700 hover:bg-sky-100"
                    @click="showInfo = false"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <label class="text-sm font-medium text-slate-700">
                Proposal (Link)
                <span
                    :class="proposalTerisi ? 'text-slate-700' : 'text-rose-500'"
                    >*</span
                >
            </label>

            <div
                class="space-y-3 rounded-xl border border-slate-200 bg-white p-3"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="form.proposalLink"
                        :class="[
                            'bg-white',
                            proposalError
                                ? 'border-rose-500 ring-rose-500'
                                : '',
                        ]"
                        :disabled="readOnly"
                        placeholder="Tempel link proposal (Google Docs/Drive)"
                        type="url"
                        inputmode="url"
                        @blur="touched.proposalLink = true"
                        @input="touched.proposalLink = true"
                    />
                    <Button
                        v-if="templateCopyUrl"
                        as-child
                        type="button"
                        variant="outline"
                        size="sm"
                    >
                        <a
                            :href="templateCopyUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1"
                        >
                            Download Template
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </Button>
                    <Button
                        v-if="templatePreviewUrl"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="openTemplate"
                    >
                        Preview Template
                    </Button>
                </div>

                <p v-if="proposalError" class="text-xs text-rose-600">
                    {{ proposalError }}
                </p>
            </div>
        </div>

        <div class="space-y-3">
            <label class="text-sm font-medium text-slate-700">
                Link Lampiran (opsional)
            </label>

            <Textarea
                v-model="linkTambahanText"
                :disabled="readOnly"
                rows="5"
                class="min-h-[140px]"
                placeholder="Masukkan link lampiran, Pisahkan setiap link dengan Enter.

https://drive.google.com/...
https://youtube.com/...
https://www.figma.com/..."
            />
        </div>
    </div>

    <Dialog v-model:open="openTemplatePreview">
        <DialogContent class="sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Preview Template Proposal</DialogTitle>
                <DialogDescription>
                    Pratinjau template proposal dari admin.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-3">
                <iframe
                    v-if="templatePreviewUrl && !templatePreviewBlocked"
                    :src="templatePreviewUrl"
                    class="h-[70vh] w-full rounded-lg border"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    @error="markTemplatePreview"
                />
                <div
                    v-else
                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700"
                >
                    Preview tidak tersedia. Pastikan akses file diatur ke
                    “Anyone with the link”.
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="openProposalPreview">
        <DialogContent class="sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Preview Proposal</DialogTitle>
                <DialogDescription>
                    Pratinjau proposal yang Anda unggah melalui link.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-3">
                <iframe
                    v-if="proposalPreviewUrl && !proposalPreviewBlocked"
                    :src="proposalPreviewUrl"
                    class="h-[70vh] w-full rounded-lg border"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    @error="markProposalPreview"
                />
                <div
                    v-else
                    class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700"
                >
                    Preview tidak tersedia. Pastikan akses link diatur ke
                    “Anyone with the link”.
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
