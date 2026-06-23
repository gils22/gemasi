<script setup lang="ts">
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowLeft, FileText, Users } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type DetailKarya = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    edisi_label: string;
    status_tampilan: string;
    wa_ketua?: string | null;
    dosen_pembimbing?: {
        nik?: string;
        nama?: string;
        email?: string;
        bidang?: string;
    } | null;
    proposal_link?: string | null;
    link_tambahan?: Array<{ label?: string; url?: string }>;
    anggota_tim?: Array<{
        nama?: string;
        nim?: string;
        email?: string;
        peran?: string;
    }>;
    lampiran?: Array<{
        id?: number;
        nama_asli?: string;
        deskripsi?: string;
        url?: string;
    }>;
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Detail Karya" }, () => page),
});

const page = usePage<PageProps & { karya: DetailKarya }>();
const karya = computed(() => page.props.karya);
const lampiranTambahan = computed(() => {
    const data = karya.value.link_tambahan ?? [];
    return data
        .map((item) => {
            if (typeof item === "string") {
                return { label: "", url: item.trim() };
            }
            return {
                label: item?.label ?? "",
                url: item?.url ?? "",
            };
        })
        .filter((item) => item.url.length > 0);
});

const back = () => router.get("/peserta/daftar-karya");
</script>

<template>
    <section class="space-y-6">
        <div class="flex items-center gap-3">
            <Button type="button" variant="outline" size="icon-sm" @click="back">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Detail Karya</h1>
                <p class="text-sm text-slate-500">
                    Tampilan ringkas data karya saat pendaftaran sudah ditutup.
                </p>
            </div>
        </div>

        <Card class="border-slate-200">
            <CardHeader class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge class="bg-slate-100 text-slate-700">
                        {{ karya.edisi_label }}
                    </Badge>
                    <Badge class="bg-indigo-50 text-indigo-700">
                        {{ karya.status_tampilan }}
                    </Badge>
                </div>
                <CardTitle class="text-xl text-slate-900">
                    {{ karya.nama_karya }}
                </CardTitle>
                <p class="text-sm text-slate-600">
                    {{ karya.nama_kategori }}
                </p>
            </CardHeader>

            <CardContent class="space-y-6">
                <div class="rounded-lg border border-slate-200 p-4 space-y-2">
                    <p class="text-sm font-semibold text-slate-900">
                        Data Daftar Karya
                    </p>
                    <p class="text-sm text-slate-600">
                        WA Ketua:
                        <span class="font-medium text-slate-900">
                            {{ karya.wa_ketua ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Dosen pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.nama ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        NIK pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.nik ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Email pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.email ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Bidang pembimbing:
                        <span class="font-medium text-slate-900">
                            {{ karya.dosen_pembimbing?.bidang ?? "-" }}
                        </span>
                    </p>
                    <p class="text-sm text-slate-600">
                        Proposal:
                        <a
                            v-if="karya.proposal_link"
                            :href="karya.proposal_link"
                            target="_blank"
                            class="font-medium text-indigo-600 hover:underline"
                        >
                            Buka proposal
                        </a>
                        <span v-else class="font-medium text-slate-900">-</span>
                    </p>
                    <div class="space-y-2">
                        <p class="text-sm text-slate-600">Lampiran:</p>
                        <div v-if="lampiranTambahan.length" class="space-y-2">
                            <a
                                v-for="(item, index) in lampiranTambahan"
                                :key="`${item.url ?? index}-${index}`"
                                :href="item.url"
                                target="_blank"
                                class="block rounded-lg border border-slate-200 px-3 py-2 text-sm text-indigo-600 hover:bg-slate-50"
                            >
                                {{ item.label || item.url || "Lampiran" }}
                            </a>
                        </div>
                        <span v-else class="font-medium text-slate-900">-</span>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 p-4 space-y-2">
                    <p class="text-sm font-semibold text-slate-900">
                        Daftar Anggota
                    </p>
                    <div v-if="karya.anggota_tim?.length" class="space-y-2">
                        <div
                            v-for="(anggota, index) in karya.anggota_tim"
                            :key="`anggota-${index}`"
                            class="rounded-md border border-slate-100 bg-slate-50 p-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-900">
                                        {{ anggota.nama ?? "-" }}
                                    </p>
                                    <p class="text-sm text-slate-500">
                                        NIM: {{ anggota.nim ?? "-" }}
                                    </p>
                                    <p class="text-sm text-slate-500">
                                        Email: {{ anggota.email ?? "-" }}
                                    </p>
                                </div>
                                <Badge
                                    :class="
                                        anggota.peran === 'ketua'
                                            ? 'bg-indigo-50 text-indigo-700'
                                            : 'bg-slate-100 text-slate-700'
                                    "
                                >
                                    {{ anggota.peran === "ketua" ? "Ketua" : "Anggota" }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">
                        Belum ada anggota tim.
                    </p>
                </div>

            </CardContent>
        </Card>
    </section>
</template>
