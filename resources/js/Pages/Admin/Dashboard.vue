<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import {
    Archive,
    RotateCcw,
    Sun,
    MoonStar,
    Cloud,
    CloudRain,
    CloudSun,
    CloudMoon,
    CloudSnow,
    CloudLightning,
    Users,
    FolderKanban,
    Send,
    Trophy,
    FileText,
    ClipboardCheck,
} from "lucide-vue-next";

type Statistik = {
    total_user: number;
    total_peserta: number;
    total_admin: number;
    total_juri: number;
    total_admin_juri: number;
    total_karya: number;
    karya_draft: number;
    karya_submitted: number;
    karya_nominasi: number;
    karya_dinilai_tahap_2: number;
};

type RingkasanEdisi = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    aktif: boolean;
};

const page = usePage<{
    statistik: Statistik;
    ringkasanEdisi: RingkasanEdisi;
    kategoriStats?: Array<{ id: number; nama: string; total: number }>;
    auth: {
        user?: {
            name?: string;
        } | null;
    };
    weather?: {
        code?: number | null;
        is_day?: number | null;
    } | null;
}>();

const statistik = computed(() => page.props.statistik);
const ringkasanEdisi = computed(() => page.props.ringkasanEdisi);
const namaUser = computed(() => page.props.auth?.user?.name ?? "Admin");
const weather = computed(() => page.props.weather ?? null);
const kategoriStats = computed(() => page.props.kategoriStats ?? []);
const totalKategoriKarya = computed(() =>
    kategoriStats.value.reduce((sum, item) => sum + item.total, 0),
);
const jam = new Date().getHours();
const sapaan = computed(() => {
    if (jam >= 4 && jam < 11) return "Selamat pagi";
    if (jam >= 11 && jam < 15) return "Selamat siang";
    if (jam >= 15 && jam < 19) return "Selamat sore";
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

const isReady = ref(false);
onMounted(() => {
    requestAnimationFrame(() => {
        isReady.value = true;
    });
});

const progressColors = [
    "bg-sky-500",
    "bg-blue-500",
    "bg-indigo-500",
    "bg-violet-500",
    "bg-cyan-500",
];

const selesaikanEdisi = () => {
    const edisi = ringkasanEdisi.value;
    if (!edisi || edisi.status !== "aktif") return;
    toast.warning(`Selesaikan ${edisi.nama}?`, {
        description: "Edisi akan dipindahkan ke arsip dan tidak aktif lagi.",
        action: {
            label: "Ya, Selesaikan",
            onClick: () => {
                router.post(
                    `/admin/edisi-lomba/${edisi.id}/selesaikan`,
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () =>
                            toast.success(
                                `Edisi ${edisi.nama} dipindahkan ke arsip`,
                            ),
                        onError: () => toast.error("Gagal menyelesaikan edisi"),
                    },
                );
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

const bukaKembaliEdisi = () => {
    const edisi = ringkasanEdisi.value;
    if (!edisi || edisi.status !== "arsip") return;
    toast.warning(`Buka kembali ${edisi.nama}?`, {
        description: "Edisi arsip akan dijadikan edisi aktif kembali.",
        action: {
            label: "Ya, Buka Kembali",
            onClick: () => {
                router.post(
                    `/admin/edisi-lomba/${edisi.id}/aktifkan`,
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () =>
                            toast.success(`Edisi ${edisi.nama} aktif kembali`),
                        onError: () =>
                            toast.error("Gagal membuka kembali edisi"),
                    },
                );
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <!-- Welcome -->
        <div
            v-if="!isReady"
            class="relative bg-white border rounded-2xl p-6 shadow-sm animate-pulse"
        >
            <div class="flex items-start gap-4">
                <div class="h-16 w-16 rounded-2xl bg-slate-100" />
                <div class="space-y-3 flex-1">
                    <div class="h-5 w-48 rounded-full bg-slate-100" />
                    <div class="h-4 w-40 rounded-full bg-slate-100" />
                </div>
            </div>
            <div class="absolute right-4 top-4 h-6 w-20 rounded-full bg-slate-100" />
        </div>

        <div
            v-else
            class="relative bg-white border rounded-2xl p-6 shadow-sm"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-700"
                >
                    <component :is="weatherIcon" class="h-8 w-8" />
                </div>
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        {{ sapaan }}, Admin.
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">
                        GEMASI {{ ringkasanEdisi.tahun }}.
                    </p>
                </div>
            </div>
            <div
                class="absolute right-4 top-4 inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700"
            >
                {{ ringkasanEdisi.status }}
            </div>
        </div>

        <!-- KPI Cards -->
        <div
            v-if="!isReady"
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4"
        >
            <div
                v-for="idx in 3"
                :key="`kpi-skel-top-${idx}`"
                class="bg-white border rounded-xl p-5 shadow-sm animate-pulse"
            >
                <div class="flex items-center justify-between">
                    <div class="space-y-3">
                        <div class="h-3 w-28 rounded-full bg-slate-100" />
                        <div class="h-6 w-16 rounded-full bg-slate-100" />
                    </div>
                    <div class="h-10 w-10 rounded-full bg-slate-100" />
                </div>
            </div>
        </div>

        <div
            v-else
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4"
        >
            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-sky-200 hover:bg-sky-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-sky-700 transition-colors duration-200 group-hover:text-sky-50"
                        >
                            Total Karya
                        </p>
                        <h3
                            class="text-2xl font-semibold mt-2 text-sky-700 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ statistik.total_karya }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sky-700 transition-colors duration-200 group-hover:bg-white group-hover:text-sky-600"
                    >
                        <FolderKanban class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-blue-200 hover:bg-blue-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-blue-700 transition-colors duration-200 group-hover:text-blue-50"
                        >
                            Karya Terkirim
                        </p>
                        <h3
                            class="text-2xl font-semibold mt-2 text-blue-700 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ statistik.karya_submitted }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700 transition-colors duration-200 group-hover:bg-white group-hover:text-blue-600"
                    >
                        <Send class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-indigo-200 hover:bg-indigo-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-indigo-700 transition-colors duration-200 group-hover:text-indigo-50"
                        >
                            Karya Draft
                        </p>
                        <h3
                            class="text-2xl font-semibold mt-2 text-indigo-700 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ statistik.karya_draft }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 transition-colors duration-200 group-hover:bg-white group-hover:text-indigo-600"
                    >
                        <FileText class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!isReady" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
                v-for="idx in 2"
                :key="`kpi-skel-bottom-${idx}`"
                class="bg-white border rounded-xl p-5 shadow-sm animate-pulse"
            >
                <div class="flex items-center justify-between">
                    <div class="space-y-3">
                        <div class="h-3 w-28 rounded-full bg-slate-100" />
                        <div class="h-6 w-16 rounded-full bg-slate-100" />
                    </div>
                    <div class="h-10 w-10 rounded-full bg-slate-100" />
                </div>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-violet-200 hover:bg-violet-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-violet-700 transition-colors duration-200 group-hover:text-violet-50"
                        >
                            Lolos Nominasi
                        </p>
                        <h3
                            class="text-2xl font-semibold mt-2 text-violet-700 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ statistik.karya_nominasi }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-700 transition-colors duration-200 group-hover:bg-white group-hover:text-violet-600"
                    >
                        <Trophy class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-cyan-200 hover:bg-cyan-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-cyan-700 transition-colors duration-200 group-hover:text-cyan-50"
                        >
                            Dinilai Tahap 2
                        </p>
                        <h3
                            class="text-2xl font-semibold mt-2 text-cyan-700 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ statistik.karya_dinilai_tahap_2 }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-100 text-cyan-700 transition-colors duration-200 group-hover:bg-white group-hover:text-cyan-600"
                    >
                        <ClipboardCheck class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Karya per Kategori -->
        <div
            v-if="!isReady"
            class="bg-white border rounded-2xl p-6 shadow-sm animate-pulse"
        >
            <div class="space-y-3">
                <div class="h-4 w-56 rounded-full bg-slate-100" />
                <div class="h-3 w-36 rounded-full bg-slate-100" />
            </div>
            <div class="mt-5 space-y-4">
                <div v-for="idx in 4" :key="`cat-skel-${idx}`" class="space-y-2">
                    <div class="h-3 w-40 rounded-full bg-slate-100" />
                    <div class="h-2 w-full rounded-full bg-slate-100" />
                </div>
            </div>
        </div>

        <div v-else class="bg-white border rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        Data Karya per Kategori
                    </h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Total karya: {{ totalKategoriKarya }}
                    </p>
                </div>
            </div>

            <div v-if="kategoriStats.length" class="mt-5 space-y-4">
                <div
                    v-for="(item, idx) in kategoriStats"
                    :key="item.id"
                    class="space-y-2"
                >
                    <div class="flex items-center justify-between text-sm">
                        <p class="font-medium text-slate-700">
                            {{ item.nama }}
                        </p>
                        <span class="text-slate-500">
                            {{ item.total }}
                        </span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-100">
                        <div
                            class="h-2 rounded-full transition-[width] duration-700 ease-out"
                            :class="progressColors[idx % progressColors.length]"
                            :style="{
                                width:
                                    totalKategoriKarya > 0 && isReady
                                        ? `${Math.round(
                                              (item.total /
                                                  totalKategoriKarya) *
                                                  100,
                                          )}%`
                                        : '0%',
                            }"
                        />
                    </div>
                </div>
            </div>
            <div v-else class="mt-5 text-sm text-slate-500">
                Belum ada data karya pada kategori.
            </div>
        </div>
    </div>
</template>
