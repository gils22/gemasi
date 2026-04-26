<script setup lang="ts">
import { computed } from "vue";
import { ExternalLink, Info, Plus, X } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { ref } from "vue";
import type { FormDaftarKarya } from "./types";

const props = defineProps<{
    form: FormDaftarKarya;
    templateProposalUrl?: string | null;
    templateProposalName?: string | null;
    readOnly?: boolean;
}>();

const maxFileSize = 5 * 1024 * 1024;

const tambahLampiran = () => {
    if (props.readOnly) return;
    props.form.lampiran.push({
        file: null,
        namaFile: "",
        deskripsi: "",
    });
};

const hapusLampiran = (index: number) => {
    if (props.readOnly) return;
    if (props.form.lampiran.length <= 1) return;
    props.form.lampiran.splice(index, 1);
};

const tambahLinkTambahan = () => {
    if (props.readOnly) return;
    props.form.linkTambahan.push({ label: "", url: "" });
};

const hapusLinkTambahan = (index: number) => {
    if (props.readOnly) return;
    if (props.form.linkTambahan.length <= 1) return;
    props.form.linkTambahan.splice(index, 1);
};

const onFileChange = (event: Event, index: number) => {
    if (props.readOnly) return;
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    if (!file) {
        props.form.lampiran[index].file = null;
        props.form.lampiran[index].namaFile = "";
        return;
    }

    if (file.size > maxFileSize) {
        toast.error("Ukuran file maksimal 5MB per file.");
        target.value = "";
        return;
    }

    props.form.lampiran[index].file = file;
    props.form.lampiran[index].namaFile = file.name;
};

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

const openTemplatePreview = ref(false);
const openProposalPreview = ref(false);
const showInfo = ref(true);
const templatePreviewBlocked = ref(false);
const proposalPreviewBlocked = ref(false);

const markTemplatePreview = () => {
    templatePreviewBlocked.value = true;
    templatePreviewStatus.value = "blocked";
};

const markProposalPreview = () => {
    proposalPreviewBlocked.value = true;
    proposalPreviewStatus.value = "blocked";
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
                        Lampiran opsional. File pendukung bisa berupa gambar,
                        PDF, DOCX, atau PPTX. Proposal diisi lewat link Google
                        Docs/Drive (make a copy). Untuk file lain, unggah ke
                        Google Drive lalu tempel di Link Tambahan. Pastikan
                        akses file diatur ke “Anyone with the link” agar juri
                        bisa melihatnya dan Pastikan membuka link dengan email
                        students
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
            </label>

            <div
                class="rounded-xl border border-slate-200 bg-white p-3 space-y-2"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Input
                        v-model="form.proposalLink"
                        class="bg-white"
                        :disabled="readOnly"
                        placeholder="Tempel link proposal (Google Docs/Drive)"
                    />
                    <p class="text-xs text-slate-600">
                        Nama file peserta :
                        <span class="font-medium">
                            {{ templateProposalName || "Template proposal" }}
                        </span>
                    </p>
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
                            Download
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
                        Preview template
                    </Button>
                    <span v-else class="text-xs text-slate-500">
                        Template proposal akan disediakan admin.
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs text-slate-500 truncate">
                        {{
                            form.proposalLink
                                ? "Link tersimpan"
                                : "Belum ada link proposal"
                        }}
                    </p>
                    <Button
                        v-if="form.proposalLink"
                        as-child
                        type="button"
                        variant="outline"
                        size="sm"
                    >
                        <a
                            :href="form.proposalLink"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1"
                        >
                            Buka link
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </Button>
                    <Button
                        v-if="proposalPreviewUrl"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="openProposal"
                    >
                        Preview proposal
                    </Button>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <label class="text-sm font-medium text-slate-700"
                >Tambah Lampiran (opsional)</label
            >

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div
                    v-for="(item, index) in form.lampiran"
                    :key="`lampiran-${index}`"
                    class="rounded-xl border border-slate-200 bg-white p-3 space-y-2"
                >
                    <div class="flex items-start gap-2">
                        <input
                            type="file"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,image/*"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                            :disabled="readOnly"
                            @change="onFileChange($event, index)"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                            :disabled="readOnly || form.lampiran.length <= 1"
                            @click="hapusLampiran(index)"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <p class="text-xs text-slate-500 truncate">
                        {{ item.namaFile || "Belum ada file dipilih" }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="item.url"
                            :href="item.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-700 hover:bg-slate-200"
                        >
                            Lihat lampiran
                            <ExternalLink class="h-3 w-3" />
                        </a>
                        <span
                            v-if="item.file"
                            class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700"
                        >
                            File baru dipilih
                        </span>
                    </div>

                    <textarea
                        v-model="item.deskripsi"
                        rows="3"
                        class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                        :disabled="readOnly"
                        placeholder="Deskripsi lampiran wajib diisi"
                    />
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                class="w-full sm:w-fit"
                :disabled="readOnly"
                @click="tambahLampiran"
            >
                <Plus class="w-4 h-4" />
                Tambah Lampiran
            </Button>

            <p class="text-xs text-slate-500">
                Ukuran file maksimal 5MB per file.
            </p>
        </div>

        <div class="space-y-3">
            <label class="text-sm font-medium text-slate-700">
                Link Tambahan (opsional)
            </label>

            <div class="space-y-2">
                <div
                    v-for="(item, index) in form.linkTambahan"
                    :key="`link-${index}`"
                    class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_2fr_auto]"
                >
                    <Input
                        v-model="item.label"
                        :disabled="readOnly"
                        placeholder="Contoh: PPT / Website / Video"
                    />
                    <Input
                        v-model="item.url"
                        :disabled="readOnly"
                        placeholder="https://..."
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        :disabled="readOnly || form.linkTambahan.length <= 1"
                        @click="hapusLinkTambahan(index)"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                class="w-full sm:w-fit"
                :disabled="readOnly"
                @click="tambahLinkTambahan"
            >
                <Plus class="w-4 h-4" />
                Tambah Link
            </Button>
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
                    class="w-full h-[70vh] rounded-lg border"
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
                    class="w-full h-[70vh] rounded-lg border"
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
