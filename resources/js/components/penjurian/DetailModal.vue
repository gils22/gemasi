<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { ExternalLink } from "lucide-vue-next";

type Lampiran = {
    id: number;
    nama: string;
    deskripsi: string | null;
    url: string;
};

type DetailKarya = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    total_nilai?: number | null;
    rata_rata?: number | null;
    anggota_tim: Array<{
        nama?: string;
        nim?: string;
        peran?: string;
    }>;
    lampiran: Lampiran[];
};

const props = defineProps<{
    open: boolean;
    loading?: boolean;
    detail: DetailKarya | null;
    gemasiLabel?: string;
}>();

defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-lg font-semibold text-slate-900">
                    Detail Karya
                </DialogTitle>
                <Badge v-if="gemasiLabel" variant="secondary" class="mt-2 w-fit">
                    {{ gemasiLabel }}
                </Badge>
            </DialogHeader>

            <div v-if="loading" class="py-6 text-sm text-slate-500">
                Memuat detail karya...
            </div>

            <div v-else-if="detail" class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        Nama Karya
                    </p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">
                        {{ detail.nama_karya }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ detail.nama_kategori }}
                    </p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500">Anggota Tim</p>
                    <div class="mt-2 space-y-2">
                        <div
                            v-for="(anggota, idx) in detail.anggota_tim"
                            :key="idx"
                            class="grid grid-cols-1 gap-1 text-sm text-slate-800 sm:grid-cols-[1.2fr_1.8fr_1fr]"
                        >
                            <span>{{ anggota.nim ?? "-" }}</span>
                            <span class="font-medium">{{ anggota.nama ?? "-" }}</span>
                            <span class="text-slate-500">
                                {{ anggota.peran ?? "-" }}
                            </span>
                        </div>
                        <p
                            v-if="!detail.anggota_tim?.length"
                            class="text-sm text-slate-500"
                        >
                            Belum ada anggota tim.
                        </p>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500 mb-2">Lampiran Karya</p>
                    <div v-if="detail.lampiran.length" class="space-y-2">
                        <a
                            v-for="item in detail.lampiran"
                            :key="item.id"
                            :href="item.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm text-blue-700 hover:bg-slate-50"
                        >
                            <div>
                                <p class="font-medium">{{ item.nama }}</p>
                                <p class="text-slate-500">
                                    {{ item.deskripsi || "-" }}
                                </p>
                            </div>
                            <ExternalLink class="h-4 w-4" />
                        </a>
                    </div>
                    <p v-else class="text-sm text-slate-500">
                        Tidak ada lampiran.
                    </p>
                </div>
            </div>

            <div v-else class="py-6 text-sm text-slate-500">
                Data detail belum tersedia.
            </div>
        </DialogContent>
    </Dialog>
</template>
