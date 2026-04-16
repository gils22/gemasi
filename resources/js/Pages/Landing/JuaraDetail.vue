<script setup lang="ts">
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import { Badge } from "@/components/ui/badge";
import { ArrowLeft } from "lucide-vue-next";

type Winner = {
    peringkat: number;
    kategori: string | null;
    nama_karya: string | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
    deskripsi?: string | null;
    logo_url?: string | null;
    logo_name?: string | null;
    video_url?: string | null;
    pameran_submitted_at?: string | null;
};

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
} | null;

const page = usePage<{
    winner: Winner;
    edisi: Edisi;
}>();

const winner = computed(() => page.props.winner);
const edisi = computed(() => page.props.edisi);

const toVideoEmbedUrl = (url?: string | null) => {
    if (!url) return null;
    const ytMatch =
        url.match(
            /(?:youtu\.be\/|youtube\.com\/watch\?v=|youtube\.com\/embed\/)([A-Za-z0-9_-]{6,})/,
        ) || [];
    const id = ytMatch[1];
    if (id) {
        return `https://www.youtube.com/embed/${id}`;
    }
    return null;
};

const videoEmbedUrl = computed(() =>
    winner.value?.video_url ? toVideoEmbedUrl(winner.value.video_url) : null,
);
</script>

<template>
    <LandingLayout>
        <section class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-100">
            <div class="mx-auto w-full max-w-5xl px-6 py-16 mt-6">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <Link
                        href="/juara"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Kembali ke daftar juara
                    </Link>
                    <div v-if="edisi" class="text-xs text-slate-500">
                        {{ edisi.nama }} ({{ edisi.tahun }})
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white/90 p-6">
                    <div class="flex items-start gap-4 border-b border-slate-100 pb-4">
                        <div
                            class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-3"
                        >
                            <img
                                v-if="winner.logo_url"
                                :src="winner.logo_url"
                                alt="Logo karya"
                                class="h-full w-full object-contain"
                            />
                            <span
                                v-else
                                class="text-xs text-center text-slate-400"
                            >
                                Belum ada logo
                            </span>
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <Badge class="bg-amber-100 text-amber-700">
                                    Juara {{ winner.peringkat ?? "-" }}
                                </Badge>
                                <span v-if="winner.kategori">
                                    {{ winner.kategori }}
                                </span>
                            </div>
                            <h1 class="text-2xl font-semibold text-slate-900">
                                {{ winner.nama_karya ?? "-" }}
                            </h1>
                            <p class="text-xs text-slate-500 break-words">
                                {{ winner.logo_name ?? "-" }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ winner.pameran_submitted_at ?? "-" }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="winner.anggota_tim?.length"
                        class="mt-8 rounded-lg border border-slate-100 bg-slate-50/60 p-4"
                    >
                        <p class="text-xs font-semibold text-slate-500">
                            Anggota Tim
                        </p>
                        <div class="mt-2 space-y-1">
                            <div
                                v-for="(anggota, aidx) in winner.anggota_tim"
                                :key="`detail-anggota-${aidx}`"
                                class="flex items-center justify-between text-sm text-slate-800"
                            >
                                <span class="font-medium">
                                    {{ anggota.nama ?? "-" }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ anggota.nim ?? "-" }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 grid items-start gap-4 lg:grid-cols-[1fr_1fr]">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Video
                            </p>
                            <div
                                v-if="winner.video_url"
                                class="max-w-[320px] overflow-hidden rounded-xl border border-slate-200 bg-white"
                            >
                                <iframe
                                    v-if="videoEmbedUrl"
                                    :src="videoEmbedUrl"
                                    title="Video Demo"
                                    class="aspect-video w-full"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                />
                                <video
                                    v-else
                                    controls
                                    class="aspect-video w-full bg-black"
                                    :src="winner.video_url ?? undefined"
                                />
                            </div>
                            <a
                                v-if="winner.video_url"
                                :href="winner.video_url"
                                target="_blank"
                                class="inline-flex text-sm font-medium text-blue-600 hover:text-blue-700"
                            >
                                Buka link video
                            </a>
                            <p v-else class="text-sm text-slate-400">
                                Belum ada video
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Ringkasan
                            </p>
                            <p class="text-sm leading-6 text-slate-700 whitespace-pre-line">
                                {{ winner.deskripsi ?? "-" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
