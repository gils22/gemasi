<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import heroImage from "@/assets/gizmo.png";

import {
    Sun,
    MoonStar,
    Cloud,
    CloudRain,
    CloudSun,
    CloudMoon,
    CloudSnow,
    CloudLightning,
    FolderKanban,
    Send,
    Trophy,
    ClipboardCheck,
    FileText,
} from "lucide-vue-next";

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});

type Statistik = {
    total_karya: number;
    karya_submitted: number;
    karya_nominasi: number;
    karya_dinilai_tahap_2: number;
};

type TimelineItem = {
    id: number;
    judul: string;
    mulai_pada?: string | null;
    selesai_pada?: string | null;
    is_tba?: boolean;
    aktif: boolean;
};

type TimelineStatus = "ongoing" | "finished" | "upcoming";

type KategoriItem = {
    id: number;
    nama: string;
    total: number;
};

type RingkasanEdisi = {
    id: number;
    nama: string;
    tahun: number;
    status: string;
    aktif: boolean;
};

const page = usePage<{
    statistik: Statistik;
    ringkasanEdisi: RingkasanEdisi;
    timeline?: TimelineItem[];
    kategoriStats?: KategoriItem[];
    weather?: {
        code?: number | null;
        is_day?: number | null;
    } | null;
    auth: {
        user?: {
            name?: string;
        } | null;
    };
}>();

const statistik = computed(() => page.props.statistik);
const ringkasanEdisi = computed(() => page.props.ringkasanEdisi);

const timelineItems = computed(() => page.props.timeline ?? []);
const timelineMonthMap: Record<string, number> = {
    Jan: 0,
    Feb: 1,
    Mar: 2,
    Apr: 3,
    May: 4,
    Jun: 5,
    Jul: 6,
    Aug: 7,
    Sep: 8,
    Oct: 9,
    Nov: 10,
    Dec: 11,
};

const parseTimelineDate = (value?: string | null) => {
    if (!value) return null;

    const parsed = new Date(value);
    if (!Number.isNaN(parsed.getTime())) return parsed;

    const match = value.match(/^(\d{1,2})\s([A-Za-z]{3})\s(\d{4})$/);
    if (!match) return null;

    const monthIndex = timelineMonthMap[match[2]];
    if (monthIndex === undefined) return null;

    return new Date(Number(match[3]), monthIndex, Number(match[1]));
};

const normalizeDate = () => {
    const value = new Date();
    value.setHours(0, 0, 0, 0);
    return value;
};

const getTimelineStatus = (item: TimelineItem): TimelineStatus => {
    if (item.is_tba) return "upcoming";
    const start = parseTimelineDate(item.mulai_pada);
    const end = parseTimelineDate(item.selesai_pada);
    const now = normalizeDate();

    if (start && now < start) return "upcoming";
    if (end && now > end) return "finished";
    if (start || end) return "ongoing";
    return item.aktif ? "ongoing" : "upcoming";
};

const timelineStatusLabel = computed(() => {
    if (!timelineItems.value.length) return "Belum ada timeline";

    const statuses = timelineItems.value.map(getTimelineStatus);
    if (statuses.includes("ongoing")) return "Sedang berlangsung";
    if (statuses.every((status) => status === "finished")) return "Selesai";
    return "Belum dimulai";
});

const timelineStatusClass = computed(() =>
    timelineStatusLabel.value === "Sedang berlangsung"
        ? "bg-indigo-50 text-indigo-700"
        : "bg-slate-100 text-slate-600",
);

const timelineItemStatusLabel = (item: TimelineItem) => {
    if (item.is_tba) return "TBA";
    const status = getTimelineStatus(item);
    if (status === "ongoing") return "Sedang berlangsung";
    if (status === "finished") return "Selesai";
    return "Berikutnya";
};

const timelineItemStatusClass = (item: TimelineItem) => {
    if (item.is_tba) return "bg-slate-100 text-slate-500";
    const status = getTimelineStatus(item);
    if (status === "ongoing") return "bg-indigo-600 text-white";
    if (status === "finished") return "bg-slate-200 text-slate-700";
    return "bg-white text-slate-500";
};

const timelineItemContainerClass = (item: TimelineItem) => {
    if (item.is_tba) return "border-dashed border-slate-200 bg-slate-50/50";
    const status = getTimelineStatus(item);
    if (status === "ongoing") return "border-indigo-200 bg-indigo-50 shadow-sm";
    if (status === "finished") return "border-slate-200 bg-slate-50/70";
    return "border-slate-200 bg-white hover:bg-slate-50";
};

const kategoriStats = computed(() => page.props.kategoriStats ?? []);

const namaUser = computed(() => page.props.auth?.user?.name ?? "Admin");

const weather = computed(() => page.props.weather ?? null);

const totalKategoriKarya = computed(() =>
    kategoriStats.value.reduce((sum, item) => sum + item.total, 0),
);

const progressColors = [
    "bg-indigo-500",
    "bg-violet-500",
    "bg-sky-500",
    "bg-cyan-500",
    "bg-blue-500",
];

const weatherIcon = computed(() => {
    const code = weather.value?.code;
    const isDay = weather.value?.is_day === 1;

    if (code === null || code === undefined) {
        return isDay ? Sun : MoonStar;
    }

    if (code === 0) return isDay ? Sun : MoonStar;

    if ([1, 2, 3].includes(code)) {
        return isDay ? CloudSun : CloudMoon;
    }

    if ([45, 48].includes(code)) return Cloud;

    if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67].includes(code)) {
        return CloudRain;
    }

    if ([71, 73, 75, 77, 85, 86].includes(code)) {
        return CloudSnow;
    }

    if ([80, 81, 82].includes(code)) {
        return CloudRain;
    }

    if ([95, 96, 99].includes(code)) {
        return CloudLightning;
    }

    return Cloud;
});

const summaryCards = computed(() => [
    {
        label: "Total Karya",
        value: statistik.value.total_karya,
        helper: "Semua karya pada edisi aktif",
        icon: FolderKanban,
    },
    {
        label: "Karya Terkirim Lengkap",
        value: statistik.value.karya_submitted,
        helper: "Karya yang telah disubmit peserta",
        icon: Send,
    },
    {
        label: "Karya Lolos Nominasi",
        value: statistik.value.karya_nominasi,
        helper: "Karya yang lolos penjurian tahap 1",
        icon: Trophy,
    },
    {
        label: "Karya Dinilai Tahap 2",
        value: statistik.value.karya_dinilai_tahap_2,
        helper: "Karya yang masuk penilaian lanjutan",
        icon: ClipboardCheck,
    },
]);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
            <!-- LEFT -->
            <div class="space-y-4">
                <!-- HERO -->
                <div
                    class="relative overflow-hidden rounded-lg border border-indigo-200/70 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 p-6 shadow-sm shadow-indigo-900/10"
                >
                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_34%),radial-gradient(circle_at_bottom_left,rgba(255,255,255,0.12),transparent_30%)]"
                    />

                    <div
                        class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="space-y-5 text-white">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-lg bg-white/15 text-white ring-1 ring-white/15 backdrop-blur"
                                >
                                    <component
                                        :is="weatherIcon"
                                        class="h-8 w-8"
                                    />
                                </div>

                                <div class="max-w-2xl">
                                    <h1
                                        class="text-2xl font-semibold tracking-tight"
                                    >
                                        Selamat datang,
                                        {{ namaUser }}.
                                    </h1>

                                    <p
                                        class="mt-2 max-w-xl text-sm text-white/80"
                                    >
                                        Pantau progres edisi, kategori, dan
                                        penjurian GEMASI dari satu dashboard.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 max-w-md">
                                <div
                                    class="rounded-lg border border-white/15 bg-white/10 px-4 py-3 text-white backdrop-blur"
                                >
                                    <p
                                        class="text-[11px] uppercase tracking-[0.2em] text-white/65"
                                    >
                                        Edisi
                                    </p>

                                    <p class="mt-1 text-sm font-semibold">
                                        {{ ringkasanEdisi.nama }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-lg border border-white/15 bg-white/10 px-4 py-3 text-white backdrop-blur"
                                >
                                    <p
                                        class="text-[11px] uppercase tracking-[0.2em] text-white/65"
                                    >
                                        Status
                                    </p>

                                    <p class="mt-1 text-sm font-semibold">
                                        {{ ringkasanEdisi.status }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <img
                            :src="heroImage"
                            alt="GEMASI"
                            class="h-40 object-contain"
                        />
                    </div>
                </div>

                <!-- SUMMARY -->
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="group rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-slate-500">
                                    {{ card.label }}
                                </p>

                                <h3
                                    class="mt-2 text-3xl font-semibold text-slate-900"
                                >
                                    {{ card.value }}
                                </h3>

                                <p class="mt-2 text-sm text-slate-500">
                                    {{ card.helper }}
                                </p>
                            </div>

                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-all duration-200 group-hover:bg-indigo-600 group-hover:text-white"
                                t
                            >
                                <component :is="card.icon" class="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CHART -->
                <div class="rounded-lg border bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                Data Karya per Kategori
                            </h3>

                            <p class="mt-1 text-sm text-slate-600">
                                Grafik kolom karya tiap kategori.
                            </p>
                        </div>

                        <p class="text-sm text-slate-500">
                            Total karya:
                            {{ totalKategoriKarya }}
                        </p>
                    </div>

                    <div
                        class="mt-6 grid h-[280px] grid-cols-8 items-end gap-3 rounded-lg bg-slate-50 p-6"
                    >
                        <div
                            v-for="(item, idx) in kategoriStats"
                            :key="item.id"
                            class="flex flex-col items-center gap-3"
                        >
                            <div
                                class="w-8 rounded-t-lg transition-all duration-500"
                                :class="
                                    progressColors[idx % progressColors.length]
                                "
                                :style="{
                                    height: `${40 + item.total * 18}px`,
                                }"
                            />

                            <div class="text-center">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ item.total }}
                                </p>

                                <p
                                    class="mt-1 text-[11px] leading-tight text-slate-500"
                                >
                                    {{ item.nama }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <aside class="rounded-lg border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Timeline
                        </p>
                    </div>
                </div>

                <div v-if="timelineItems.length" class="mt-4 space-y-3">
                    <div
                        v-for="item in timelineItems"
                        :key="item.id"
                        class="rounded-lg border p-3 transition-all duration-200"
                        :class="timelineItemContainerClass(item)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold"
                                    :class="
                                        getTimelineStatus(item) === 'ongoing'
                                            ? 'text-indigo-700'
                                            : 'text-slate-900'
                                    "
                                >
                                    {{ item.judul }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ item.mulai_pada }}
                                    -
                                    {{ item.selesai_pada }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="timelineItemStatusClass(item)"
                            >
                                {{ timelineItemStatusLabel(item) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-500"
                >
                    Belum ada timeline pada edisi ini.
                </div>
            </aside>
        </div>
    </div>
</template>
