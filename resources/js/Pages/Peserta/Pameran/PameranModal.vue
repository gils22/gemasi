<script setup lang="ts">
import { ref } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";

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
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-xl">
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
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="flex-1 space-y-2">
                            <div
                                :class="[
                                    'flex items-center gap-2 rounded-md border border-input bg-white px-3 py-2 text-sm',
                                    props.attempt && !props.state.logo && !props.item.pameran_logo_name
                                        ? 'border-rose-300 ring-1 ring-rose-200'
                                        : '',
                                ]"
                            >
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="!props.bolehEdit"
                                    @click="fileInputRef?.click()"
                                >
                                    Pilih File
                                </Button>
                                <span class="text-xs text-slate-500">
                                    {{
                                        props.state.logo?.name ??
                                        props.item.pameran_logo_name ??
                                        "Tidak ada file yang dipilih"
                                    }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="h-20 w-20 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center"
                        >
                            <img
                                v-if="props.state.logoPreview"
                                :src="props.state.logoPreview"
                                alt="Preview logo"
                                class="h-full w-full object-contain"
                            />
                            <img
                                v-else-if="props.item.pameran_logo_url"
                                :src="props.item.pameran_logo_url"
                                alt="Logo"
                                class="h-full w-full object-contain"
                            />
                            <span v-else class="text-xs text-slate-400">-</span>
                        </div>
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
                        Penjelasan Singkat (maks 150 kata) *
                    </label>
                    <textarea
                        v-model="props.state.ringkasan"
                        rows="4"
                        class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm"
                        :disabled="!props.bolehEdit"
                        :class="
                            props.attempt && !props.state.ringkasan.trim()
                                ? 'border-rose-300 ring-1 ring-rose-200'
                                : ''
                        "
                        placeholder="Ringkasan karya singkat..."
                    />
                    <p class="text-xs text-slate-400">
                        {{
                            props.state.ringkasan.trim()
                                ? props.state.ringkasan
                                      .trim()
                                      .split(/\s+/)
                                      .filter(Boolean).length
                                : 0
                        }}/150 kata
                    </p>
                    <p
                        v-if="props.attempt && !props.state.ringkasan.trim()"
                        class="text-xs text-rose-600"
                    >
                        Ringkasan wajib diisi.
                    </p>
                    <p
                        v-if="
                            props.attempt &&
                            props.state.ringkasan
                                .trim()
                                .split(/\s+/)
                                .filter(Boolean).length > 150
                        "
                        class="text-xs text-rose-600"
                    >
                        Ringkasan maksimal 150 kata.
                    </p>
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
                            class="inline-block overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                        >
                            <img
                                v-if="props.getVideoPreview(props.state.linkVideo).length"
                                :src="props.getVideoPreview(props.state.linkVideo)"
                                alt="Preview video"
                                class="h-20 w-36 object-contain bg-white"
                            />
                            <div
                                v-else
                                class="h-20 w-36 flex items-center justify-center text-xs text-slate-400"
                            >
                                Preview video
                            </div>
                            <div class="px-2 py-1 text-[11px] text-slate-600">
                                Klik untuk membuka
                            </div>
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
