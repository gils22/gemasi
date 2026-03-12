<script setup lang="ts">
import { ref, computed } from "vue";
import {
    Layers,
    Code2,
    Brush,
    LineChart,
    Cpu,
    Smartphone,
    Gamepad2,
} from "lucide-vue-next";

const categories = [
    {
        id: "bisnis",
        name: "Bisnis Digital & Fintech",
        icon: Layers,
        accent: "bg-emerald-100 text-emerald-700",
        description:
            "Kategori ini menantang peserta untuk mengembangkan aplikasi atau sistem yang mendukung bisnis digital dan teknologi keuangan. Solusi yang dihasilkan diharapkan mampu meningkatkan efisiensi, memecahkan masalah, dan mendorong pertumbuhan sektor bisnis digital dan fintech.",
        weights: [
            { label: "Inovasi", point: 20 },
            { label: "Fitur", point: 25 },
            { label: "Fungsionalitas", point: 25 },
            { label: "Pasar", point: 15 },
            { label: "Keberlanjutan", point: 15 },
        ],
    },
    {
        id: "business-plan",
        name: "Business Plan",
        icon: LineChart,
        accent: "bg-blue-100 text-blue-700",
        description:
            "Kategori ini berfokus pada pengembangan model bisnis berbasis produk ICT. Peserta diharapkan mampu mengimplementasikan pendekatan lean dan MVP untuk menghasilkan solusi inovatif yang memiliki problem–solution fit serta dampak bisnis yang berkelanjutan.",
        weights: [
            { label: "Kejelasan Model Bisnis", point: 25 },
            { label: "Validasi Pasar", point: 20 },
            { label: "Proyeksi Keuangan", point: 20 },
            { label: "Inovasi", point: 20 },
            { label: "Presentasi", point: 15 },
        ],
    },
    {
        id: "si",
        name: "Aplikasi dan Sistem Informasi",
        icon: Cpu,
        accent: "bg-indigo-100 text-indigo-700",
        description:
            "Kategori ini mencakup pengembangan sistem atau aplikasi yang mendukung pengelolaan informasi dan proses bisnis. Karya diharapkan memiliki alur kerja yang jelas, terstruktur, dan mampu membantu pengambilan keputusan dalam organisasi.",
        weights: [
            { label: "Inovasi", point: 20 },
            { label: "Fitur", point: 25 },
            { label: "Fungsionalitas", point: 25 },
            { label: "Pasar", point: 15 },
            { label: "Keberlanjutan", point: 15 },
        ],
    },
    {
        id: "uiux",
        name: "UI/UX",
        icon: Brush,
        accent: "bg-pink-100 text-pink-700",
        description:
            "Kategori UI/UX berfokus pada perancangan antarmuka dan pengalaman pengguna. Peserta diharapkan mampu menciptakan desain yang fungsional, mudah digunakan, dan memberikan pengalaman terbaik bagi pengguna pada platform web, mobile, atau desktop.",
        weights: [
            { label: "Riset Pengguna", point: 20 },
            { label: "Kejelasan Alur", point: 25 },
            { label: "Visual & Konsistensi", point: 25 },
            { label: "Prototipe", point: 15 },
            { label: "Presentasi", point: 15 },
        ],
    },
    {
        id: "programming",
        name: "Pemrograman",
        icon: Code2,
        accent: "bg-slate-100 text-slate-700",
        description:
            "Kategori pemrograman menilai kemampuan peserta dalam menyelesaikan permasalahan melalui kode program. Penilaian meliputi logika, problem solving, algoritma, serta efektivitas program dalam menjawab studi kasus yang diangkat.",
        weights: [
            { label: "Logika & Algoritma", point: 30 },
            { label: "Kualitas Kode", point: 25 },
            { label: "Kebenaran Solusi", point: 25 },
            { label: "Efisiensi", point: 20 },
        ],
    },
    {
        id: "datascience",
        name: "Data Science",
        icon: LineChart,
        accent: "bg-amber-100 text-amber-700",
        description:
            "Kategori Data Science menitikberatkan pada analisis dan pengolahan data untuk menghasilkan insight yang bernilai. Karya dapat berupa analisis statistik, visualisasi data, atau pemodelan data yang membantu pengambilan keputusan berbasis data.",
        weights: [
            { label: "Pemilihan Data", point: 20 },
            { label: "Analisis", point: 30 },
            { label: "Visualisasi", point: 20 },
            { label: "Interpretasi", point: 20 },
            { label: "Presentasi", point: 10 },
        ],
    },
    {
        id: "arvr",
        name: "Media Interaktif AR/VR",
        icon: Gamepad2,
        accent: "bg-violet-100 text-violet-700",
        description:
            "Kategori ini mencakup pengembangan aplikasi interaktif seperti game, augmented reality, atau virtual reality. Karya diharapkan mampu memberikan pengalaman interaktif yang menarik serta dapat dimanfaatkan untuk edukasi maupun hiburan.",
        weights: [
            { label: "Konsep & Storyboard", point: 25 },
            { label: "Interaksi", point: 25 },
            { label: "Kualitas Experience", point: 25 },
            { label: "Implementasi", point: 25 },
        ],
    },
    {
        id: "multimedia",
        name: "Multimedia",
        icon: Smartphone,
        accent: "bg-cyan-100 text-cyan-700",
        description:
            "Kategori multimedia merupakan ajang karya kreatif dalam bentuk visual 2D atau 3D. Karya dapat berupa video, animasi, atau konten digital lain yang menggabungkan kreativitas, inovasi, dan pesan informatif atau edukatif.",
        weights: [
            { label: "Kreativitas", point: 25 },
            { label: "Kualitas Visual", point: 25 },
            { label: "Narasi", point: 20 },
            { label: "Teknis Produksi", point: 20 },
            { label: "Presentasi", point: 10 },
        ],
    },
];

const topTabs = [
    { id: "persyaratan", label: "Ketentuan Umum" },
    { id: "kategori", label: "Kategori Lomba" },
];

const persyaratanUmum = [
    "Pendaftaran Gratis",
    "Mahasiswa aktif Program Studi Sistem Informasi maksimal telah melewati semester ganjil 2025/2026",
    "Pendaftar bisa terdiri dari minimal 1 mahasiswa (individu) atau maksimal 6 (tim) mahasiswa per karya, khusus kategori pemrograman maksimal 3",
    "Anggota tim harus berasal dari prodi Sistem Informasi",
    "Karya merupakan karya original (karya sendiri) atau berasal dari project matakuliah",
    "1 Orang atau 1 Tim boleh mendaftarkan lebih dari 1 produk pada kategori yang sama atau berbeda",
];

const activeTab = ref(topTabs[0].id);
const activeIndex = ref(0);
const activeCategory = computed(() => categories[activeIndex.value]);
</script>

<template>
    <div class="mt-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
        >
            <button
                v-for="tab in topTabs"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                class="w-full rounded-full border px-4 py-2 text-sm font-semibold transition sm:w-auto"
                :class="
                    activeTab === tab.id
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white/70 text-slate-600 hover:border-slate-300'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <div
            v-if="activeTab === 'kategori'"
            class="mt-6 grid gap-6 lg:grid-cols-[260px_1fr] lg:items-start"
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

            <div class="space-y-4">
                <div
                    class="rounded-2xl border border-slate-200 bg-white/80 p-4 sm:p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),_0_16px_30px_-24px_rgba(15,23,42,0.35)]"
                >
                    <h3 class="text-base font-semibold text-slate-900">
                        Deskripsi
                    </h3>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ activeCategory.description }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white/80 p-4 sm:p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),_0_16px_30px_-24px_rgba(15,23,42,0.35)]"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900">
                            Bobot Penilaian
                        </h3>
                        <span class="text-xs font-semibold text-slate-500">
                            Poin
                        </span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-700">
                        <div
                            v-for="(row, idx) in activeCategory.weights"
                            :key="`${activeCategory.id}-weight-${idx}`"
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-white/70 px-3 py-2 sm:px-4"
                        >
                            <span class="capitalize">{{ row.label }}</span>
                            <span class="font-semibold text-slate-900">
                                {{ row.point }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="activeTab === 'persyaratan'" class="mt-6">
            <div
                class="rounded-2xl border border-slate-200 bg-white/80 p-5 sm:p-6 shadow-[inset_0_1px_0_rgba(255,255,255,0.9),_0_16px_30px_-24px_rgba(15,23,42,0.35)]"
            >
                <h3 class="text-base font-semibold text-slate-900">
                    Ketentuan Umum
                </h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li
                        v-for="(item, idx) in persyaratanUmum"
                        :key="`syarat-${idx}`"
                        class="flex items-start gap-3"
                    >
                        <span class="mt-1 h-2 w-2 rounded-full bg-slate-400" />
                        <span>{{ item }}</span>
                    </li>
                </ul>
                <div class="mt-4">
                    <a
                        href="/landing/panduan-template-proposal"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                    >
                        Template Proposal
                    </a>
                </div>
            </div>
        </div>

        <div v-else class="mt-6" />
    </div>
</template>
