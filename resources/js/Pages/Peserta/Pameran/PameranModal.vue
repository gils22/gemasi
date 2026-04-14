<script setup lang="ts">
import { ref } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { Image as ImageIcon } from "lucide-vue-next";

type NominasiItem = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_link_video: string | null;
    pameran_ringkasan: string | null;
    pameran_submitted_at: string | null;
};

type FormState = {
    logo: File | null;
    logoPreview: string | null;
    linkVideo: string;
    ringkasan: string;
    saving: boolean;
};

const props = defineProps<{
    open: boolean;
    item: NominasiItem;
    state: FormState;
    attempt: boolean;
    bolehEdit: boolean;
    getVideoPreview: (url: string) => string;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
    (e: "logo-change", event: Event): void;
    (e: "save"): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);

const clearLogo = () => {
    if (props.state.logoPreview) {
        URL.revokeObjectURL(props.state.logoPreview);
    }
    props.state.logo = null;
    props.state.logoPreview = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = "";
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Data Pameran</DialogTitle>
                <DialogDescription>
                    Lengkapi logo, ringkasan, dan link video karya.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-xs text-slate-500">Logo Produk *</label>
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        :disabled="!props.bolehEdit"
                        class="hidden"
                        @change="emit('logo-change', $event)"
                    />
                    <div
                        :class="[
                            'flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-slate-200 bg-transparent px-4 py-2.5 text-center',
                            props.attempt && !props.state.logo && !props.item.pameran_logo_name
                                ? 'border-rose-300 ring-1 ring-rose-200'
                                : '',
                        ]"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400">
                            <ImageIcon class="h-4 w-4" />
                        </div>
                        <div class="text-sm font-semibold text-slate-700">
                            Unggah gambar saja
                        </div>
                        <div class="text-[11px] text-slate-500">
                            PNG, JPG, GIF, WebP diperbolehkan
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!props.bolehEdit"
                            @click="fileInputRef?.click()"
                        >
                            Pilih Gambar
                        </Button>
                    </div>

                    <div
                        v-if="props.state.logo"
                        class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-10 w-10 overflow-hidden rounded-md border border-slate-200 bg-slate-50 flex items-center justify-center"
                            >
                                <img
                                    v-if="props.state.logoPreview"
                                    :src="props.state.logoPreview"
                                    alt="Preview logo"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else class="text-xs text-slate-400">-</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-slate-700">
                                    {{ props.state.logo?.name ?? "File tidak tersedia" }}
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    {{
                                        props.state.logo?.size
                                            ? `${(props.state.logo.size / 1024 / 1024).toFixed(2)} MB`
                                            : ""
                                    }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-slate-400 hover:text-slate-600"
                            @click.prevent.stop="clearLogo"
                        >
                            ✕
                        </button>
                    </div>
                    <p
                        v-if="props.attempt && !props.state.logo && !props.item.pameran_logo_name"
                        class="text-xs text-rose-600"
                    >
                        Logo produk wajib diisi.
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-xs text-slate-500">
                        Penjelasan Singkat (opsional)
                    </label>
                    <textarea
                        v-model="props.state.ringkasan"
                        rows="3"
                        class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                        :disabled="!props.bolehEdit"
                        placeholder="Ringkasan karya singkat..."
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-xs text-slate-500">
                        Link Video Karya/Produk *
                    </label>
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="flex-1 min-w-[220px]">
                            <Input
                                v-model="props.state.linkVideo"
                                placeholder="https://..."
                                :disabled="!props.bolehEdit"
                                :class="
                                    props.attempt && !props.state.linkVideo.trim()
                                        ? 'border-rose-300 ring-1 ring-rose-200'
                                        : ''
                                "
                            />
                        </div>
                        <a
                            v-if="props.state.linkVideo.trim()"
                            :href="props.state.linkVideo.trim()"
                            target="_blank"
                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
                        >
                            Buka tautan
                        </a>
                    </div>
                    <p
                        v-if="props.attempt && !props.state.linkVideo.trim()"
                        class="text-xs text-rose-600"
                    >
                        Link video wajib diisi.
                    </p>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <Button
                    type="button"
                    :disabled="props.state.saving || !props.bolehEdit"
                    @click="emit('save')"
                >
                    <Spinner v-if="props.state.saving" class="mr-2" />
                    Simpan
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
