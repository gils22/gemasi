<script setup lang="ts">
import { ref, computed } from "vue";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import GlareHover from "@/components/GlareHover/GlareHover.vue";
import {
    ArrowRight,
    Sparkles,
    Award,
    Layers,
    LineChart,
    Cpu,
    Brush,
    Code2,
    Gamepad2,
    Smartphone,
} from "lucide-vue-next";

const categories = [
    {
        id: "bisnis",
        name: "Bisnis Digital & Fintech",
        icon: Layers,
        accent: "bg-emerald-100 text-emerald-700",
        nominations: [
            {
                title: "FinTrack UMKM",
                team: "Tim Ledger",
                summary:
                    "Aplikasi pembukuan sederhana dengan analitik cashflow dan rekomendasi.",
            },
            {
                title: "Paylink Merchant",
                team: "Tim Polaris",
                summary:
                    "Integrasi pembayaran QR dan dashboard transaksi untuk UMKM.",
            },
        ],
    },
    {
        id: "business-plan",
        name: "Business Plan",
        icon: LineChart,
        accent: "bg-blue-100 text-blue-700",
        nominations: [
            {
                title: "AgriSupply Hub",
                team: "Tim Harvest",
                summary:
                    "Rantai pasok digital untuk komoditas lokal dengan model berlangganan.",
            },
            {
                title: "EduBridge",
                team: "Tim Skyline",
                summary:
                    "Platform bimbingan belajar berbasis komunitas untuk daerah 3T.",
            },
        ],
    },
    {
        id: "si",
        name: "Aplikasi dan Sistem Informasi",
        icon: Cpu,
        accent: "bg-indigo-100 text-indigo-700",
        nominations: [
            {
                title: "Sistem Monitoring Energi Kampus",
                team: "Tim Voltara",
                summary:
                    "Platform pemantauan konsumsi energi real-time untuk efisiensi operasional.",
            },
            {
                title: "Smart Inventory Lab",
                team: "Tim Atlas",
                summary:
                    "Manajemen inventaris laboratorium terintegrasi dengan notifikasi otomatis.",
            },
        ],
    },
    {
        id: "uiux",
        name: "UI/UX",
        icon: Brush,
        accent: "bg-pink-100 text-pink-700",
        nominations: [
            {
                title: "UX Audit Mobile Health",
                team: "Tim Flow",
                summary:
                    "Perancangan ulang pengalaman pengguna untuk aplikasi kesehatan digital.",
            },
            {
                title: "Design System GEMASI",
                team: "Tim Canvas",
                summary:
                    "Sistem desain konsisten untuk aplikasi layanan kampus.",
            },
        ],
    },
    {
        id: "programming",
        name: "Pemrograman",
        icon: Code2,
        accent: "bg-slate-100 text-slate-700",
        nominations: [
            {
                title: "Smart Attendance Face ID",
                team: "Tim Vertex",
                summary:
                    "Solusi absensi berbasis pengenalan wajah dengan anti-spoofing.",
            },
            {
                title: "Realtime Queue API",
                team: "Tim Nodewave",
                summary: "Layanan antrian real-time dengan fallback offline.",
            },
        ],
    },
    {
        id: "datascience",
        name: "Data Science",
        icon: LineChart,
        accent: "bg-amber-100 text-amber-700",
        nominations: [
            {
                title: "Insight Penjualan Retail",
                team: "Tim Prism",
                summary:
                    "Analisis pola penjualan dan prediksi permintaan berbasis data historis.",
            },
            {
                title: "City Mobility Forecast",
                team: "Tim Orbit",
                summary:
                    "Prediksi kepadatan transportasi publik untuk optimalisasi rute.",
            },
        ],
    },
    {
        id: "arvr",
        name: "Media Interaktif AR/VR",
        icon: Gamepad2,
        accent: "bg-violet-100 text-violet-700",
        nominations: [
            {
                title: "AR Campus Tour",
                team: "Tim VXR",
                summary:
                    "Tur kampus interaktif berbasis augmented reality untuk calon mahasiswa.",
            },
            {
                title: "VR Lab Safety",
                team: "Tim Immersive",
                summary:
                    "Simulasi pelatihan keselamatan laboratorium berbasis VR.",
            },
        ],
    },
    {
        id: "multimedia",
        name: "Multimedia",
        icon: Smartphone,
        accent: "bg-cyan-100 text-cyan-700",
        nominations: [
            {
                title: "Motion Story GEMASI",
                team: "Tim Aura",
                summary:
                    "Video motion graphic untuk publikasi kegiatan GEMASI.",
            },
            {
                title: "3D Campus Identity",
                team: "Tim Lumen",
                summary: "Branding visual 3D untuk identitas institusi.",
            },
        ],
    },
];

const activeIndex = ref(0);
const activeCategory = computed(() => categories[activeIndex.value]);
</script>

<template>
    <LandingLayout>
        <section class="bg-gradient-to-b from-slate-50 via-white to-slate-100">
            <div class="mx-auto w-full max-w-6xl px-6 py-16 mt-6">
                <div class="max-w-3xl space-y-4">
                    <h1 class="text-3xl font-semibold text-slate-900">
                        Nominasi Karya Terbaik GEMASI
                    </h1>
                    <p class="text-sm text-slate-600">
                        Tahap nominasi menentukan karya yang melaju ke penjurian
                        akhir. Pastikan karya sudah lengkap dan sesuai kriteria
                        penilaian.
                    </p>
                </div>

                <div
                    class="mt-10 grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start"
                >
                    <aside
                        class="flex gap-3 overflow-x-auto pb-2 sm:pb-3 lg:flex-col lg:overflow-visible lg:pb-0"
                    >
                        <button
                            v-for="(cat, idx) in categories"
                            :key="cat.id"
                            type="button"
                            @click="activeIndex = idx"
                            class="group flex min-w-[180px] items-center gap-3 rounded-2xl border border-transparent px-4 py-3 text-left text-sm font-semibold transition sm:min-w-[200px] lg:min-w-0"
                            :class="
                                idx === activeIndex
                                    ? 'border border-slate-900 bg-white shadow-[0_12px_26px_-18px_rgba(15,23,42,0.45)] ring-1 ring-slate-900/10 transform-gpu [transform:perspective(600px)_rotateX(4deg)]'
                                    : 'bg-transparent text-slate-600 hover:border-slate-200 hover:bg-white/60'
                            "
                        >
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-xl"
                                :class="cat.accent"
                            >
                                <component :is="cat.icon" class="h-5 w-5" />
                            </span>
                            <span class="leading-tight">
                                {{ cat.name }}
                            </span>
                        </button>
                    </aside>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-slate-900">
                                Karya yang Masuk Nominasi
                            </h2>
                            <span
                                class="text-xs font-semibold uppercase text-slate-400"
                            >
                                {{ activeCategory.nominations.length }} Karya
                            </span>
                        </div>
                        <div class="mt-4 grid gap-4">
                            <div
                                v-for="(
                                    item, idx
                                ) in activeCategory.nominations"
                                :key="`${activeCategory.id}-nomination-${idx}`"
                                class="rounded-2xl border border-slate-200 bg-white/80 p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9)]"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-semibold uppercase text-slate-400"
                                        >
                                            {{ activeCategory.name }}
                                        </p>
                                        <h3
                                            class="mt-2 text-base font-semibold text-slate-900"
                                        >
                                            {{ item.title }}
                                        </h3>
                                    </div>
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/5 text-slate-600"
                                    >
                                        <Award class="h-5 w-5" />
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ item.summary }}
                                </p>
                                <p
                                    class="mt-4 text-xs font-semibold text-slate-500"
                                >
                                    {{ item.team }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
