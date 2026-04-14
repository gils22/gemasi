<script setup lang="ts">
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import { Badge } from "@/components/ui/badge";
import { ArrowLeft, Video } from "lucide-vue-next";

type Winner = {
    peringkat: number;
    kategori: string | null;
    nama_karya: string | null;
    anggota_tim: Array<{ nama?: string; nim?: string }>;
    deskripsi?: string | null;
    logo_url?: string | null;
    video_url?: string | null;
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
                    <div class="flex flex-col gap-6 md:flex-row md:items-start">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-20 w-20 items-center justify-center rounded-md border border-slate-200 bg-white text-sm font-semibold text-slate-400"
                            >
                                <img
                                    v-if="winner.logo_url"
                                    :src="winner.logo_url"
                                    alt="Logo karya"
                                    class="h-full w-full rounded-md object-cover"
                                />
                                <span v-else>
                                    {{
                                        (winner.nama_karya ?? "GK")
                                            .slice(0, 2)
                                            .toUpperCase()
                                    }}
                                </span>
                            </div>
                            <div>
                                <p
                                    v-if="winner.kategori"
                                    class="text-xs font-semibold uppercase text-slate-400"
                                >
                                    {{ winner.kategori }}
                                </p>
                                <h1 class="text-2xl font-semibold text-slate-900">
                                    {{ winner.nama_karya ?? "-" }}
                                </h1>
                                <div class="mt-3 flex items-center gap-2 text-sm text-slate-500">
                                    <Badge class="bg-amber-100 text-amber-700">
                                        #{{ winner.peringkat ?? "-" }}
                                    </Badge>
                                    <span v-if="winner.kategori">
                                        {{ winner.kategori }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1">
                            <div
                                v-if="winner.deskripsi"
                                class="space-y-2 text-sm text-slate-600"
                            >
                                <p class="text-xs font-semibold text-slate-500">
                                    Ringkasan Produk
                                </p>
                                <p>{{ winner.deskripsi }}</p>
                            </div>
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

                    <div v-if="winner.video_url" class="mt-8">
                        <p class="text-xs font-semibold text-slate-500">
                            Video Demo
                        </p>
                        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-900/5">
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
                        <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                            <Video class="h-4 w-4" />
                            Klik tombol play untuk memutar video.
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
