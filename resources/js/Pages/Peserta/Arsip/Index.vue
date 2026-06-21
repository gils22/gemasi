<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Eye, Users, Trophy } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type ArsipKaryaItem = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    wa_ketua?: string | null;
    dosen_pembimbing?: {
        nik?: string;
        nama?: string;
        email?: string;
        bidang?: string;
    } | null;
    proposal_link?: string | null;
    link_tambahan?: Array<{ label?: string; url?: string }>;
    lampiran?: Array<{
        id?: number;
        nama_asli?: string;
        deskripsi?: string;
        url?: string;
    }>;
    jumlah_anggota_tim: number;
    nama_ketua: string | null;
    status_tampilan: string;
    updated_at: string | null;
    edisi_label: string;
    edisi_id: number;
    is_juara?: boolean;
    is_nominasi?: boolean;
    peringkat?: number | null;
    anggota_tim?: Array<{
        nama?: string;
        nim?: string;
        email?: string;
        peran?: string;
    }>;
    pameran_logo_name: string | null;
    pameran_logo_url: string | null;
    pameran_link_video: string | null;
    pameran_ringkasan: string | null;
    pameran_submitted_at: string | null;
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Arsip" }, () => page),
});

const page = usePage<
    PageProps & {
        arsipKarya?: ArsipKaryaItem[];
        edisiOpsi?: Array<{ id: number; label: string }>;
        edisiTerpilih?: number | null;
    }
>();

const arsipKarya = computed(() => page.props.arsipKarya ?? []);
const edisiOpsi = computed(() => page.props.edisiOpsi ?? []);
const edisiTerpilih = ref<string>(
    page.props.edisiTerpilih ? String(page.props.edisiTerpilih) : "all",
);

const labelJuara = (peringkat?: number | null) => {
    if (peringkat === 1) return "Juara 1";
    if (peringkat === 2) return "Juara 2";
    if (peringkat === 3) return "Juara 3";
    return "Juara";
};

const openKaryaDetail = (item: ArsipKaryaItem) => {
    router.get(`/peserta/arsip/${item.id}`);
};

watch(
    () => page.props.edisiTerpilih,
    (value) => {
        edisiTerpilih.value = value ? String(value) : "all";
    },
);

const filterEdisi = (value: string) => {
    edisiTerpilih.value = value;
    router.get("/peserta/arsip", value === "all" ? {} : { edisi: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <section class="space-y-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Arsip</h1>
                <p class="text-sm text-slate-500">
                    Karya yang sudah diarsipkan tetap bisa dilihat kembali.
                </p>
            </div>
            <div class="w-full sm:w-72">
                <Select
                    :model-value="edisiTerpilih"
                    @update:model-value="filterEdisi"
                >
                    <SelectTrigger class="w-full bg-white">
                        <SelectValue placeholder="Semua edisi" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua edisi</SelectItem>
                        <SelectItem
                            v-for="edisi in edisiOpsi"
                            :key="edisi.id"
                            :value="String(edisi.id)"
                        >
                            {{ edisi.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div
            v-if="!arsipKarya.length"
            class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
        >
            Belum ada arsip karya.
        </div>

        <TooltipProvider v-else>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="item in arsipKarya"
                    :key="item.id"
                    class="rounded-lg border border-slate-200 bg-white p-4"
                >
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-slate-800">
                                    {{ item.nama_karya }}
                                </h3>
                                <p class="truncate text-sm text-slate-600">
                                    {{ item.nama_kategori }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <Badge
                                    :class="
                                        item.status_tampilan === 'Lengkap'
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-amber-50 text-amber-700'
                                    "
                                >
                                    {{ item.status_tampilan }}
                                </Badge>
                                <Badge
                                    v-if="item.is_juara"
                                    class="bg-violet-50 text-violet-700"
                                >
                                    <Trophy class="mr-1 h-3.5 w-3.5" />
                                    {{ labelJuara(item.peringkat) }}
                                </Badge>
                                <Badge
                                    v-else-if="item.is_nominasi"
                                    class="bg-sky-50 text-sky-700"
                                >
                                    Lolos Nominasi
                                </Badge>
                            </div>
                        </div>

                        <p class="text-xs font-medium text-sky-700">
                            {{ item.edisi_label }}
                        </p>

                        <div class="space-y-1 text-sm text-slate-600">
                            <div class="inline-flex items-center gap-1">
                                <Users class="h-4 w-4 text-slate-500" />
                                <span>{{ item.jumlah_anggota_tim }} anggota tim</span>
                            </div>
                            <p>Ketua: {{ item.nama_ketua ?? "-" }}</p>
                            <p>Diperbarui: {{ item.updated_at ?? "-" }}</p>
                        </div>

                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    @click="openKaryaDetail(item)"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>

                            <TooltipContent side="top">
                                Lihat Detail
                            </TooltipContent>
                        </Tooltip>
                    </div>
                </article>
            </div>
        </TooltipProvider>
    </section>
</template>
