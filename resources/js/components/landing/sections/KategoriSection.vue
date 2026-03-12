<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import FlipCard from "@/components/ui/flip-card/FlipCard.vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const categories = [
    {
        title: "Bisnis Digital & Fintech",
        description:
            "Kategori ini menantang peserta untuk mengembangkan aplikasi atau sistem yang mendukung bisnis digital dan teknologi keuangan. Solusi yang dihasilkan diharapkan mampu meningkatkan efisiensi, memecahkan masalah, dan mendorong pertumbuhan sektor bisnis digital dan fintech.",
        image: "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "Business Plan",
        description:
            "Kategori ini berfokus pada pengembangan model bisnis berbasis produk ICT. Peserta diharapkan mampu mengimplementasikan pendekatan lean dan MVP untuk menghasilkan solusi inovatif yang memiliki problem–solution fit serta dampak bisnis yang berkelanjutan.",
        image: "https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "Aplikasi dan Sistem Informasi",
        description:
            "Kategori ini mencakup pengembangan sistem atau aplikasi yang mendukung pengelolaan informasi dan proses bisnis. Karya diharapkan memiliki alur kerja yang jelas, terstruktur, dan mampu membantu pengambilan keputusan dalam organisasi.",
        image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "UI/UX",
        description:
            "Kategori UI/UX berfokus pada perancangan antarmuka dan pengalaman pengguna. Peserta diharapkan mampu menciptakan desain yang fungsional, mudah digunakan, dan memberikan pengalaman terbaik bagi pengguna pada platform web, mobile, atau desktop.",
        image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "Pemrograman",
        description:
            "Kategori pemrograman menilai kemampuan peserta dalam menyelesaikan permasalahan melalui kode program. Penilaian meliputi logika, problem solving, algoritma, serta efektivitas program dalam menjawab studi kasus yang diangkat.",
        image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "Data Science",
        description:
            "Kategori Data Science menitikberatkan pada analisis dan pengolahan data untuk menghasilkan insight yang bernilai. Karya dapat berupa analisis statistik, visualisasi data, atau pemodelan data yang membantu pengambilan keputusan berbasis data.",
        image: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "Media Interaktif AR/VR",
        description:
            "Kategori ini mencakup pengembangan aplikasi interaktif seperti game, augmented reality, atau virtual reality. Karya diharapkan mampu memberikan pengalaman interaktif yang menarik serta dapat dimanfaatkan untuk edukasi maupun hiburan.",
        image: "https://images.unsplash.com/photo-1535223289827-42f1e9919769?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
    {
        title: "Multimedia",
        description:
            "Kategori multimedia merupakan ajang karya kreatif dalam bentuk visual 2D atau 3D. Karya dapat berupa video, animasi, atau konten digital lain yang menggabungkan kreativitas, inovasi, dan pesan informatif atau edukatif.",
        image: "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=900&auto=format&fit=crop&q=60&ixlib=rb-4.0.3",
    },
];

const activeIndex = ref(0);
const autoScrollRef = ref<number | null>(null);
const isPaused = ref(false);

const mod = (n: number, m: number) => ((n % m) + m) % m;

const getOffset = (index: number) => {
    const total = categories.length;
    const raw = index - activeIndex.value;
    const wrapped =
        raw > total / 2 ? raw - total : raw < -total / 2 ? raw + total : raw;
    if (wrapped > 2) return 3;
    if (wrapped < -2) return -3;
    return wrapped;
};

const move = (dir: "prev" | "next") => {
    activeIndex.value = mod(
        activeIndex.value + (dir === "next" ? 1 : -1),
        categories.length,
    );
};

const startAutoScroll = () => {
    stopAutoScroll();
    autoScrollRef.value = window.setInterval(() => {
        if (isPaused.value) return;
        move("next");
    }, 2000);
};

const stopAutoScroll = () => {
    if (autoScrollRef.value) {
        clearInterval(autoScrollRef.value);
        autoScrollRef.value = null;
    }
};

onMounted(() => {
    startAutoScroll();
});

onUnmounted(() => {
    stopAutoScroll();
});
</script>

<template>
    <section id="kategori" class="bg-transparent">
        <div class="mx-auto w-full max-w-6xl py-16">
            <div class="">
                <div class="flex flex-col sm:gap-6 text-slate-900">
                    <div class="text-center">
                        <h2 class="text-2xl font-semibold text-slate-900">
                            Pilih Bidang Kompetisimu
                        </h2>
                    </div>

                    <div
                        class="relative flex flex-col items-center justify-center gap-4"
                        @mouseenter="isPaused = true"
                        @mouseleave="isPaused = false"
                    >
                        <div
                            class="relative h-[460px] w-full overflow-hidden perspective-[1200px]"
                        >
                            <div
                                v-for="(category, index) in categories"
                                :key="category.title"
                                class="coverflow-card"
                                :data-offset="getOffset(index)"
                                :class="`offset-${getOffset(index)}`"
                            >
                                <FlipCard
                                    class="flip-card h-[320px] w-[240px] sm:h-[380px] sm:w-[300px]"
                                >
                                    <template #default>
                                        <div
                                            class="relative h-full w-full overflow-hidden rounded-2xl"
                                        >
                                            <img
                                                :src="category.image"
                                                :alt="category.title"
                                                class="size-full rounded-2xl object-cover"
                                            />
                                            <div
                                                class="absolute inset-0 bg-gradient-to-t from-slate-900/70 via-slate-900/20 to-transparent"
                                            />
                                            <div
                                                class="absolute bottom-4 left-4 right-4 text-base font-semibold text-white"
                                            >
                                                {{ category.title }}
                                            </div>
                                        </div>
                                    </template>
                                    <template #back>
                                        <div
                                            class="flex h-full flex-col gap-2 rounded-2xl bg-white/80 p-5 text-slate-700 backdrop-blur-xl"
                                        >
                                            <h3
                                                class="text-base font-semibold text-slate-900"
                                            >
                                                {{ category.title }}
                                            </h3>
                                            <p
                                                class="mt-1 border-t border-slate-200 pt-3 text-xs leading-relaxed text-slate-600"
                                            >
                                                {{ category.description }}
                                            </p>
                                        </div>
                                    </template>
                                </FlipCard>
                            </div>
                        </div>

                        <div
                            class="sm:mt-2 flex items-center justify-center gap-6"
                        >
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/70 bg-white/70 text-slate-700 shadow-sm backdrop-blur transition hover:bg-white"
                                aria-label="Sebelumnya"
                                @click="move('prev')"
                            >
                                <ChevronLeft class="h-5 w-5" />
                            </button>
                            <div class="flex items-center gap-2">
                                <button
                                    v-for="(_, index) in categories"
                                    :key="index"
                                    type="button"
                                    class="rounded-full transition"
                                    :class="
                                        activeIndex === index
                                            ? 'h-2 w-2 bg-slate-900'
                                            : 'h-1.5 w-1.5 bg-slate-300'
                                    "
                                    :aria-label="`Pindah ke kartu ${index + 1}`"
                                    @click="activeIndex = index"
                                />
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/70 bg-white/70 text-slate-700 shadow-sm backdrop-blur transition hover:bg-white"
                                aria-label="Berikutnya"
                                @click="move('next')"
                            >
                                <ChevronRight class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.coverflow-card {
    position: absolute;
    left: 50%;
    top: 50%;
    transform-style: preserve-3d;
    transition:
        transform 700ms ease,
        opacity 700ms ease;
    transform: translate(-50%, -50%);
    opacity: 0;
    pointer-events: none;
}

.coverflow-card.offset-0 {
    transform: translate(-50%, -50%) scale(1.12);
    opacity: 1;
    z-index: 5;
    pointer-events: auto;
}

.coverflow-card.offset-1 {
    transform: translate(40%, -50%) scale(0.9) rotateY(-18deg);
    opacity: 0.9;
    z-index: 4;
}

.coverflow-card.offset-2 {
    transform: translate(80%, -50%) scale(0.8) rotateY(-24deg);
    opacity: 0.6;
    z-index: 3;
}

.coverflow-card.offset-3 {
    transform: translate(120%, -50%) scale(0.7) rotateY(-28deg);
    opacity: 0;
    z-index: 2;
}

.coverflow-card.offset--1 {
    transform: translate(-140%, -50%) scale(0.9) rotateY(18deg);
    opacity: 0.9;
    z-index: 4;
}

.coverflow-card.offset--2 {
    transform: translate(-190%, -50%) scale(0.8) rotateY(24deg);
    opacity: 0.6;
    z-index: 3;
}

.coverflow-card.offset--3 {
    transform: translate(-240%, -50%) scale(0.7) rotateY(28deg);
    opacity: 0;
    z-index: 2;
}

.flip-card :deep(.backface-hidden) {
    border-color: rgba(255, 255, 255, 0.7);
}

.flip-card :deep(.backface-hidden)::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        140deg,
        rgba(255, 255, 255, 0.35),
        rgba(148, 163, 184, 0.08) 55%,
        rgba(15, 23, 42, 0.22)
    );
    opacity: 0.75;
    pointer-events: none;
}

.flip-card :deep([class*="rotateY"]),
.flip-card :deep([class*="rotateX"]) {
    background: rgba(255, 255, 255, 0.7) !important;
    color: #0f172a !important;
}

@media (max-width: 768px) {
    .coverflow-card.offset-1 {
        transform: translate(30%, -50%) scale(0.88) rotateY(-16deg);
    }
    .coverflow-card.offset-2 {
        transform: translate(70%, -50%) scale(0.78) rotateY(-20deg);
    }
    .coverflow-card.offset--1 {
        transform: translate(-135%, -50%) scale(0.88) rotateY(16deg);
    }
    .coverflow-card.offset--2 {
        transform: translate(-170%, -50%) scale(0.78) rotateY(20deg);
    }
}
</style>
