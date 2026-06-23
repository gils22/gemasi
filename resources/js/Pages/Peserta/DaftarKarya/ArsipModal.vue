<script setup lang="ts">
import { ref, watch } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Users, RotateCcw, Eye, Trash2 } from "lucide-vue-next";
import {
    Card,
    CardHeader,
    CardTitle,
    CardContent,
    CardFooter,
} from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";

type KaryaItem = any;

const props = defineProps<{
    open: boolean;
    arsipPendaftaran: KaryaItem[];
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
    (e: "pulihkan", item: KaryaItem): void;
    (e: "bukaFormEdit", id: number): void;
    (e: "hapusPermanen", item: KaryaItem): void;
    (e: "hapusSemua"): void;
}>();

const internalOpen = ref<boolean>(props.open ?? false);
watch(
    () => props.open,
    (v) => (internalOpen.value = !!v),
);
watch(internalOpen, (v) => emit("update:open", v));

const formatDateTime = (value: string | null) => {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(date);
};

const handlePulihkan = (item: KaryaItem) => emit("pulihkan", item);
const handleBukaFormEdit = (id: number) => emit("bukaFormEdit", id);
const handleHapusPermanen = (item: KaryaItem) => emit("hapusPermanen", item);
const handleHapusSemua = () => emit("hapusSemua");
const confirmAndHapusSemua = () => {
    if (
        confirm(
            "Hapus semua arsip pendaftaran? Tindakan ini tidak dapat dibatalkan.",
        )
    ) {
        handleHapusSemua();
    }
};
</script>

<template>
    <Dialog v-model:open="internalOpen">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <div class="flex items-start justify-between gap-4 w-full">
                    <div>
                        <DialogTitle>Arsip Pendaftaran</DialogTitle>
                        <DialogDescription>
                            Karya yang Anda Hapus dari Daftar Karya. Anda bisa
                            memulihkannya selama pendaftaran masih dibuka.
                        </DialogDescription>
                    </div>
                    <div class="ml-auto">
                        <Button
                            v-if="arsipPendaftaran.length"
                            type="button"
                            variant="destructive"
                            size="sm"
                            @click="confirmAndHapusSemua"
                        >
                            Hapus Semua
                        </Button>
                    </div>
                </div>
            </DialogHeader>

            <div
                v-if="!arsipPendaftaran.length"
                class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
            >
                Belum ada karya yang diarsipkan.
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-2"
            >
                <Card
                    v-for="item in arsipPendaftaran"
                    :key="`arsip-${item.id}`"
                    class="p-4"
                >
                    <CardHeader class="p-0">
                        <div
                            class="flex items-start justify-between gap-2 w-full"
                        >
                            <div class="min-w-0">
                                <CardTitle class="truncate text-base">{{
                                    item.nama_karya
                                }}</CardTitle>
                                <p class="text-sm text-slate-600 truncate">
                                    {{ item.nama_kategori }} -
                                    {{ item.edisi ?? "-" }}
                                </p>
                            </div>
                            <Badge class="bg-slate-100 text-slate-700"
                                >Diarsipkan</Badge
                            >
                        </div>
                    </CardHeader>

                    <CardContent class="p-0 mt-2 text-sm text-slate-600">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <Users class="h-4 w-4 text-slate-500" />
                            <span
                                >{{ item.jumlah_anggota_tim }} anggota tim</span
                            >
                        </div>
                        <div class="truncate mb-1">
                            <span class="text-slate-500">Ketua:</span>
                            {{ item.nama_ketua ?? "-" }}
                        </div>
                        <div>
                            <span class="text-slate-500">Diarsipkan:</span>
                            {{ formatDateTime(item.updated_at) }}
                        </div>
                    </CardContent>

                    <CardFooter class="p-0 mt-3">
                        <div class="mt-auto flex flex-wrap gap-2">
                            <TooltipProvider>
                                <Tooltip v-if="item.dapat_dikelola">
                                    <TooltipTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon-sm"
                                            @click="handlePulihkan(item)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Pulihkan</TooltipContent>
                                </Tooltip>

                                <Tooltip v-else>
                                    <TooltipTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon-sm"
                                            @click="handleBukaFormEdit(item.id)"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Lihat</TooltipContent>
                                </Tooltip>

                                <Tooltip v-if="item.dapat_dikelola">
                                    <TooltipTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="icon-sm"
                                            class="text-rose-600"
                                            @click="handleHapusPermanen(item)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Hapus Permanen</TooltipContent
                                    >
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </CardFooter>
                </Card>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
