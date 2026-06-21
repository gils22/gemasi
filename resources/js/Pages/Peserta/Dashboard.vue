<script setup lang="ts">
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import heroImage from "@/assets/gizmo.png";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
    Sun,
    MoonStar,
    Cloud,
    CloudRain,
    CloudSun,
    CloudMoon,
    CloudSnow,
    CloudLightning,
} from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});

type NominasiItem = {
    id: number;
    nama_karya: string | null;
    nama_kategori: string | null;
    pameran_logo_url?: string | null;
    pameran_submitted_at?: string | null;
};

type EdisiOption = {
    id: number;
    tahun: number;
    label: string;
};

type KaryaListItem = {
    id: number;
    nama_karya: string | null;
    nama_kategori: string | null;
    tahun: number | null;
    edisi_label: string | null;
    status: string | null;
    peran: string;
    lolos_nominasi: boolean;
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
        edisiAktif?: {
            id: number;
            nama: string;
            tahun: number;
            status: string;
            aktif: boolean;
        };
        edisiAktifLabel?: string;
        statusTim?: string;
        submissionCount?: number;
        nominasiCount?: number;
        totalKarya?: number;
        edisiOpsi?: EdisiOption[];
        karyaList?: KaryaListItem[];
        timeline?: TimelineItem[];
        nominasiList?: NominasiItem[];
        weather?: {
            code?: number | null;
            is_day?: number | null;
        } | null;
    }
>();
const edisiAktif = computed(() => page.props.edisiAktif ?? null);
const edisiAktifLabel = computed(() => page.props.edisiAktifLabel ?? "-");
const namaPeserta = computed(() => page.props.auth?.user?.name ?? "Peserta");
const statusTim = computed(() => page.props.statusTim ?? "-");
const submissionCount = computed(() => page.props.submissionCount ?? 0);
const nominasiCount = computed(() => page.props.nominasiCount ?? 0);
const totalKarya = computed(() => page.props.totalKarya ?? 0);
const edisiOpsi = computed(() => page.props.edisiOpsi ?? []);
const karyaList = computed(() => page.props.karyaList ?? []);
const timelineItems = computed(() => page.props.timeline ?? []);
const nominasiList = computed(() => page.props.nominasiList ?? []);
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

const normalizeDate = () => {
    const value = new Date();
    value.setHours(0, 0, 0, 0);
    return value;
};

const isEdisiArsip = computed(() => edisiAktif.value?.status === "arsip");

const getTimelineStatus = (item: TimelineItem): TimelineStatus => {
    if (isEdisiArsip.value) return "finished";
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
    if (isEdisiArsip.value) return "Selesai";

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
    if (code === null || code === undefined) return isDay ? Sun : MoonStar;
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

const badgeKelas = computed(() => {
    const value = statusTim.value.toLowerCase();
    if (value.includes("belum")) return "bg-slate-100 text-slate-700";
    if (value.includes("draft")) return "bg-amber-50 text-amber-700";
    if (value.includes("lengkap") || value.includes("submission"))
        return "bg-emerald-50 text-emerald-700";
    return "bg-sky-50 text-sky-700";
});

const selectedEdisiId = ref<string>("all");

const filteredKaryaList = computed(() =>
    selectedEdisiId.value === "all"
        ? karyaList.value
        : karyaList.value.filter((item) => item.tahun === Number(selectedEdisiId.value)),
);

const karyaBadgeClass = (status?: string | null, lolosNominasi?: boolean) => {
    if (lolosNominasi) return "bg-violet-50 text-violet-700";
    if (status === "submitted") return "bg-emerald-50 text-emerald-700";
    if (status === "draft") return "bg-amber-50 text-amber-700";
    return "bg-slate-100 text-slate-700";
};

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
                                        {{ sapaan }}, {{ namaPeserta }}.
                                    </h1>
                                    <p
                                        class="mt-2 max-w-xl text-sm text-white/80"
                                    >
                                        Pantau status submission, nominasi, dan
                                        progres karya pada
                                        {{ edisiAktifLabel }}.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <div
                                    class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur"
                                >
                                    Peserta
                                </div>
                            </div>
                        </div>

                        <img
                            :src="heroImage"
                            alt="GEMASI"
                            class="h-36 w-auto shrink-0 self-start object-contain object-right lg:mt-2"
                        />
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]"
                >
                    <div class="rounded-lg border bg-white p-6 shadow-sm">
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="text-lg font-semibold text-slate-900"
                                    >
                                        Semua Karya
                                    </h3>
                                    <span
                                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700"
                                    >
                                        {{ totalKarya }} karya
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">
                                    Pilih edisi tahun untuk melihat karya yang
                                    pernah kamu ikuti.
                                </p>
                            </div>
                            <div class="min-w-[220px]">
                                <p
                                    class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Edisi Tahun
                                </p>
                                <Select v-model="selectedEdisiId">
                                    <SelectTrigger class="h-10 w-full bg-white">
                                        <SelectValue placeholder="Semua edisi" />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-72 overflow-auto">
                                        <SelectItem value="all">
                                            Semua edisi
                                        </SelectItem>
                                        <SelectItem
                                            v-for="edisi in edisiOpsi"
                                            :key="edisi.id"
                                            :value="String(edisi.tahun)"
                                        >
                                            {{ edisi.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div
                            v-if="!filteredKaryaList.length"
                            class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500"
                        >
                            Belum ada karya pada edisi ini.
                        </div>

                        <div
                            v-else
                            class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2"
                        >
                            <article
                                v-for="item in filteredKaryaList"
                                :key="item.id"
                                class="rounded-xl border border-slate-200 bg-white p-4 transition-colors duration-200 hover:border-indigo-200 hover:bg-indigo-50/40"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <p
                                                class="truncate text-sm font-semibold text-slate-900"
                                            >
                                                {{ item.nama_karya ?? "-" }}
                                            </p>
                                            <Badge
                                                :class="
                                                    karyaBadgeClass(
                                                        item.status,
                                                        item.lolos_nominasi,
                                                    )
                                                "
                                            >
                                                {{
                                                    item.lolos_nominasi
                                                        ? "Nominasi"
                                                        : item.status ===
                                                            "submitted"
                                                          ? "Submitted"
                                                          : item.status ===
                                                              "draft"
                                                            ? "Draft"
                                                            : "Karya"
                                                }}
                                            </Badge>
                                        </div>
                                        <p
                                            class="mt-1 truncate text-xs text-slate-500"
                                        >
                                            {{ item.nama_kategori ?? "-" }}
                                        </p>
                                        <p class="mt-2 text-xs text-slate-500">
                                            Edisi: {{ item.edisi_label ?? "-" }}
                                        </p>
                                    </div>
                                    <Badge class="bg-slate-100 text-slate-700">
                                        {{ item.peran }}
                                    </Badge>
                                </div>
                            </article>
                        </div>
                    </div>

                    <aside class="rounded-lg border bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">
                                    Ringkasan
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Ringkasan karya yang kamu miliki.
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1"
                        >
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p
                                    class="text-[11px] uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Total karya
                                </p>
                                <p
                                    class="mt-1 text-base font-semibold text-slate-900"
                                >
                                    {{ totalKarya }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p
                                    class="text-[11px] uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Submission
                                </p>
                                <p
                                    class="mt-1 text-base font-semibold text-slate-900"
                                >
                                    {{ submissionCount }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p
                                    class="text-[11px] uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Nominasi
                                </p>
                                <p
                                    class="mt-1 text-base font-semibold text-slate-900"
                                >
                                    {{ nominasiCount }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p
                                    class="text-[11px] uppercase tracking-[0.18em] text-slate-500"
                                >
                                    Draft
                                </p>
                                <p
                                    class="mt-1 text-base font-semibold text-slate-900"
                                >
                                    {{
                                        Math.max(
                                            0,
                                            totalKarya - submissionCount,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </aside>
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
                                    {{
                                        item.is_tba
                                            ? "TBA"
                                            : item.mulai_pada &&
                                                item.selesai_pada
                                              ? `${item.mulai_pada} - ${item.selesai_pada}`
                                              : item.mulai_pada ||
                                                item.selesai_pada ||
                                                "Belum dijadwalkan"
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
