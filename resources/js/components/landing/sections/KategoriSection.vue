<script setup lang="ts">
import { computed } from "vue";
import CategoryTabs from "@/components/landing/CategoryTabs.vue";
import {
    Layers,
    LineChart,
    Cpu,
    Brush,
    Code2,
    Gamepad2,
    Smartphone,
} from "lucide-vue-next";

type LandingKategori = {
    id: number;
    nama: string;
    slug?: string | null;
    deskripsi?: string | null;
    weights?: Array<{ label: string; point: number }>;
};

const props = defineProps<{
    categories?: LandingKategori[];
}>();

const fallbackCategories = [
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

const mappedCategories = computed(() => {
    if (props.categories && props.categories.length > 0) {
        return props.categories.map((item) => ({
            id: item.slug || String(item.id),
            name: item.nama,
            description: item.deskripsi ?? "",
            weights: item.weights ?? [],
        }));
    }
    return fallbackCategories;
});
</script>

<template>
    <section id="kategori" class="reveal bg-transparent" data-reveal>
        <CategoryTabs
            title="Pilih Bidang Kompetisimu"
            subtitle="Daftar kategori lomba yang dapat kamu ikuti."
            :categories="mappedCategories"
            :show-weights="true"
        />
    </section>
</template>
