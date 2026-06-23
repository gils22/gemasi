<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Badge } from "@/components/ui/badge";
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
} from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type TahapSummary = {
    total: number;
    dinilai: number;
    belum: number;
    kategori: string[];
};

type TimelineItem = {
    id: number;
    judul: string;
    mulai_pada?: string | null;
    selesai_pada?: string | null;
    is_tba: boolean;
    deskripsi?: string | null;
    aktif: boolean;
};

type TimelineStatus = "ongoing" | "finished" | "upcoming";

const page = usePage<
    PageProps & {
        edisiAktif: {
            id: number;
            nama: string;
            tahun: number;
            status: string;
            aktif: boolean;
        };
        tahap1: TahapSummary;
        tahap2: TahapSummary;
        timeline?: TimelineItem[];
        weather?: {
            code?: number | null;
            is_day?: number | null;
        } | null;
    }
>();

const edisiAktif = computed(() => page.props.edisiAktif);
const tahap1 = computed(() => page.props.tahap1);
const tahap2 = computed(() => page.props.tahap2);
const namaUser = computed(() => page.props.auth?.user?.name ?? "Juri");
const timelineItems = computed(() => page.props.timeline ?? []);
const weather = computed(() => page.props.weather ?? null);
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

const formatTimelineDate = (value?: string | null) => {
    const date = parseTimelineDate(value);
    if (!date) return "-";

    return date.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const isEdisiArsip = computed(() => edisiAktif.value?.status === "arsip");

const getTimelineStatus = (item: TimelineItem): TimelineStatus => {
    if (isEdisiArsip.value) return "finished";
    if (item.is_tba) return "upcoming";

    const start = parseTimelineDate(item.mulai_pada);
    const end = parseTimelineDate(item.selesai_pada);
    const now = new Date();

    if (start && now < start) return "upcoming";
    if (end && now > end) return "finished";
    if (start || end) return "ongoing";
    return item.aktif ? "ongoing" : "upcoming";
};

const timelineStatusLabel = computed(() => {
    if (!timelineItems.value.length) return "Belum ada timeline";
    if (isEdisiArsip.value) return "Selesai";

    const statuses = timelineItems.value.map(getTimelineStatus);
    if (statuses.includes("ongoing")) return "Berlangsung";
    if (statuses.every((status) => status === "finished")) return "Selesai";
    return "Belum dimulai";
});

const timelineStatusClass = computed(() =>
    timelineStatusLabel.value === "Berlangsung"
        ? "bg-indigo-50 text-indigo-700"
        : "bg-slate-100 text-slate-600",
);

const timelineItemStatusLabel = (item: TimelineItem) => {
    if (item.is_tba) return "TBA";
    const status = getTimelineStatus(item);
    if (status === "ongoing") return "Berlangsung";
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
const jam = new Date().getHours();
const sapaan = computed(() => {
    if (jam >= 4 && jam < 11) return "Selamat pagi";
    if (jam >= 11 && jam < 15) return "Selamat siang";
    if (jam >= 15 && jam < 18) return "Selamat sore";
    return "Selamat malam";
});
const weatherIcon = computed(() => {
    const code = weather.value?.code;
    const isDay = weather.value?.is_day === 1;
    if (code === null || code === undefined) {
        return isDay ? Sun : MoonStar;
    }
    if (code === 0) return isDay ? Sun : MoonStar;
    if ([1, 2, 3].includes(code)) return isDay ? CloudSun : CloudMoon;
    if ([45, 48].includes(code)) return Cloud;
    if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67].includes(code))
        return CloudRain;
    if ([71, 73, 75, 77, 85, 86].includes(code)) return CloudSnow;
    if ([80, 81, 82].includes(code)) return CloudRain;
    if ([95, 96, 99].includes(code)) return CloudLightning;
    return Cloud;
});

const hasTahap1 = computed(() => (tahap1.value?.kategori ?? []).length > 0);
const hasTahap2 = computed(() => (tahap2.value?.kategori ?? []).length > 0);

const summaryCards = computed(() => [
    {
        label: "Total Tahap 1",
        value: tahap1.value?.total ?? 0,
        helper: "Submission yang masuk penilaian awal",
        icon: FolderKanban,
    },
    {
        label: "Dinilai",
        value: (tahap1.value?.dinilai ?? 0) + (tahap2.value?.dinilai ?? 0),
        helper: "Total yang sudah selesai dinilai",
        icon: ClipboardCheck,
    },
    {
        label: "Total Tahap 2",
        value: tahap2.value?.total ?? 0,
        helper: "Nominasi yang perlu penilaian lanjut",
        icon: Trophy,
    },
    {
        label: "Belum Dinilai",
        value: (tahap1.value?.belum ?? 0) + (tahap2.value?.belum ?? 0),
        helper: "Masih menunggu proses penilaian",
        icon: Send,
    },
]);

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <div
            class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.45fr)_320px] lg:items-start"
        >
            <div class="space-y-4">
                <div
                    class="relative overflow-hidden rounded-lg border border-indigo-200/70 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 p-6 shadow-sm shadow-indigo-900/10"
                >
                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_34%),radial-gradient(circle_at_bottom_left,rgba(255,255,255,0.12),transparent_30%)]"
                    />
                    <div
                        class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
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
                                        {{ sapaan }}, {{ namaUser }}.
                                    </h1>
                                    <p
                                        class="mt-2 max-w-xl text-sm text-white/80"
                                    >
                                        Pantau dan kelola proses penilaian karya
                                        pada
                                        {{
                                            edisiAktif
                                                ? `${edisiAktif.nama} (${edisiAktif.tahun})`
                                                : "-"
                                        }}.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur"
                                >
                                    Juri
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur"
                                >
                                    {{ edisiAktif?.status ?? "-" }}
                                </span>
                            </div>
                        </div>

                        <img
                            :src="heroImage"
                            alt="GEMASI"
                            class="h-40 w-auto shrink-0 self-start object-contain object-right lg:mt-1"
                        />
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
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
                                    class="mt-2 text-3xl font-semibold text-slate-900 leading-none"
                                >
                                    {{ card.value }}
                                </h3>
                                <p
                                    class="mt-2 text-sm text-slate-500 leading-5"
                                >
                                    {{ card.helper }}
                                </p>
                            </div>
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-all duration-200 group-hover:bg-indigo-600 group-hover:text-white"
                            >
                                <component :is="card.icon" class="h-5 w-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-lg border bg-white p-5 shadow-sm lg:self-stretch"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Timeline
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Fase penilaian yang sedang berjalan.
                        </p>
                    </div>
                    <span
                        v-if="timelineItems.length"
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            timelineStatusClass,
                        ]"
                    >
                        {{ timelineStatusLabel }}
                    </span>
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
                                    {{
                                        item.is_tba
                                            ? "TBA"
                                            : item.mulai_pada || item.selesai_pada
                                              ? `${formatTimelineDate(item.mulai_pada)} - ${formatTimelineDate(item.selesai_pada)}`
                                              : "Belum dijadwalkan"
                                    }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="timelineItemStatusClass(item)"
                            >
                                {{ timelineItemStatusLabel(item) }}
                            </span>
                        </div>
                        <p
                            v-if="item.deskripsi"
                            class="mt-2 text-xs leading-relaxed text-slate-600"
                        >
                            {{ item.deskripsi }}
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-500"
                >
                    Belum ada timeline pada edisi ini.
                </div>
            </div>
        </div>
    </div>
</template>
