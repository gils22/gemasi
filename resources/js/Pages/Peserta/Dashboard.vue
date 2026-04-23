<script setup lang="ts">
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    FileText,
    Trophy,
    UserCircle2,
    Archive,
    ArrowRight,
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

const page = usePage<
    PageProps & {
        edisiAktifLabel?: string;
        statusTim?: string;
        submissionCount?: number;
        nominasiCount?: number;
        nominasiList?: NominasiItem[];
    }
>();
const edisiAktifLabel = computed(() => page.props.edisiAktifLabel ?? "-");
const namaPeserta = computed(() => page.props.auth?.user?.name ?? "Peserta");
const statusTim = computed(() => page.props.statusTim ?? "-");
const submissionCount = computed(() => page.props.submissionCount ?? 0);
const nominasiCount = computed(() => page.props.nominasiCount ?? 0);
const nominasiList = computed(() => page.props.nominasiList ?? []);
const jam = new Date().getHours();
const sapaan = computed(() => {
    if (jam >= 4 && jam < 11) return "Selamat pagi";
    if (jam >= 11 && jam < 15) return "Selamat siang";
    if (jam >= 15 && jam < 18) return "Selamat sore";
    return "Selamat malam";
});

const badgeKelas = computed(() => {
    const value = statusTim.value.toLowerCase();
    if (value.includes("belum")) return "bg-slate-100 text-slate-700";
    if (value.includes("draft")) return "bg-amber-50 text-amber-700";
    if (value.includes("lengkap") || value.includes("submission"))
        return "bg-emerald-50 text-emerald-700";
    return "bg-sky-50 text-sky-700";
});
</script>

<template>
    <div class="space-y-6">
        <div class="relative bg-white border rounded-2xl p-6 shadow-sm">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ sapaan }}, {{ namaPeserta }}.
                </h1>
                <p class="text-sm text-slate-600">
                    {{ edisiAktifLabel }}
                </p>
            </div>

            <div class="absolute right-4 top-4 flex flex-col items-end gap-2">
                <div
                    class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700"
                >
                    Peserta
                </div>
                <Badge :class="badgeKelas">
                    {{ statusTim }}
                </Badge>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-sky-200 hover:bg-sky-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-sky-700 transition-colors duration-200 group-hover:text-sky-50"
                        >
                            Status Submission
                        </p>
                        <h3
                            class="mt-2 text-lg font-semibold text-slate-900 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ statusTim }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sky-700 transition-colors duration-200 group-hover:bg-white group-hover:text-sky-600"
                    >
                        <FileText class="h-5 w-5" />
                    </div>
                </div>
            </div>

            <div
                class="group bg-white border rounded-xl p-5 shadow-sm transition-all duration-200 ease-out hover:border-emerald-200 hover:bg-emerald-500"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-xs font-semibold text-emerald-700 transition-colors duration-200 group-hover:text-emerald-50"
                        >
                            Submission Lengkap
                        </p>
                        <h3
                            class="text-2xl font-semibold mt-2 text-emerald-700 transition-colors duration-200 group-hover:text-white"
                        >
                            {{ submissionCount }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 transition-colors duration-200 group-hover:bg-white group-hover:text-emerald-600"
                    >
                        <ArrowRight class="h-5 w-5" />
                    </div>
                </div>
            </div>

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
                            {{ nominasiCount }}
                        </h3>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-700 transition-colors duration-200 group-hover:bg-white group-hover:text-violet-600"
                    >
                        <Trophy class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        Nominasi Anda
                    </h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Karya yang lolos nominasi pada edisi aktif.
                    </p>
                </div>
                <Button as-child variant="outline">
                    <Link href="/peserta/pameran-karya"> Kelola Pameran </Link>
                </Button>
            </div>

            <div
                v-if="!nominasiList.length"
                class="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500"
            >
                Belum ada karya yang lolos nominasi.
            </div>

            <div v-else class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <article
                    v-for="item in nominasiList"
                    :key="item.id"
                    class="rounded-xl border border-slate-200 bg-white p-4"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-400 overflow-hidden"
                        >
                            <img
                                v-if="item.pameran_logo_url"
                                :src="item.pameran_logo_url"
                                alt="Logo"
                                class="h-full w-full object-contain p-1"
                            />
                            <span v-else>
                                {{
                                    item.nama_karya
                                        ? item.nama_karya
                                              .slice(0, 2)
                                              .toUpperCase()
                                        : "GK"
                                }}
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-sm font-semibold text-slate-900 truncate"
                            >
                                {{ item.nama_karya ?? "-" }}
                            </p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ item.nama_kategori ?? "-" }}
                            </p>
                            <div class="mt-3 flex items-center gap-2">
                                <Badge
                                    :class="
                                        item.pameran_submitted_at
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-amber-50 text-amber-700'
                                    "
                                >
                                    {{
                                        item.pameran_submitted_at
                                            ? "Pameran diisi"
                                            : "Pameran belum diisi"
                                    }}
                                </Badge>
                                <span
                                    v-if="item.pameran_submitted_at"
                                    class="text-xs text-slate-500"
                                >
                                    {{ item.pameran_submitted_at }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</template>
