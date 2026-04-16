<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
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

type TahapSummary = {
    total: number;
    dinilai: number;
    belum: number;
    kategori: string[];
};

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
const weather = computed(() => page.props.weather ?? null);
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

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <div class="relative bg-white border rounded-2xl p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-700"
                >
                    <component :is="weatherIcon" class="h-8 w-8" />
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">
                        {{ sapaan }}, {{ namaUser }}.
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        {{
                            edisiAktif
                                ? `${edisiAktif.nama} (${edisiAktif.tahun})`
                                : "-"
                        }}
                    </p>
                </div>
            </div>
            <div
                class="absolute right-4 top-4 inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
            >
                {{ edisiAktif?.status ?? "-" }}
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">
                            Penilaian Tahap 1
                        </h3>
                        <p class="text-sm text-slate-500">
                            Submission sesuai kategori Anda.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">
                            {{ tahap1.total ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs text-slate-500">Sudah Dinilai</p>
                        <p class="mt-1 text-lg font-semibold text-emerald-600">
                            {{ tahap1.dinilai ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs text-slate-500">Belum Dinilai</p>
                        <p class="mt-1 text-lg font-semibold text-amber-600">
                            {{ tahap1.belum ?? 0 }}
                        </p>
                    </div>
                </div>

                <div v-if="hasTahap1" class="flex flex-wrap gap-2">
                    <Badge
                        v-for="(item, idx) in tahap1.kategori"
                        :key="`tahap1-${idx}`"
                        class="bg-slate-100 text-slate-700"
                    >
                        {{ item }}
                    </Badge>
                </div>

                <Button v-if="hasTahap1" as="a" href="/juri/submission/karya">
                    Mulai Nilai Tahap 1
                </Button>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">
                            Penilaian Tahap 2
                        </h3>
                        <p class="text-sm text-slate-500">
                            Nominasi sesuai kategori Anda.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">
                            {{ tahap2.total ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs text-slate-500">Sudah Dinilai</p>
                        <p class="mt-1 text-lg font-semibold text-emerald-600">
                            {{ tahap2.dinilai ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs text-slate-500">Belum Dinilai</p>
                        <p class="mt-1 text-lg font-semibold text-amber-600">
                            {{ tahap2.belum ?? 0 }}
                        </p>
                    </div>
                </div>

                <div v-if="hasTahap2" class="flex flex-wrap gap-2">
                    <Badge
                        v-for="(item, idx) in tahap2.kategori"
                        :key="`tahap2-${idx}`"
                        class="bg-slate-100 text-slate-700"
                    >
                        {{ item }}
                    </Badge>
                </div>

                <Button v-if="hasTahap2" as="a" href="/juri/penjurian">
                    Mulai Nilai Tahap 2
                </Button>
            </div>
        </div>
    </div>
</template>
