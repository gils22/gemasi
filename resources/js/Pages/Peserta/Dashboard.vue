<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Link } from "@inertiajs/vue3";
import type { PageProps } from "@/types/inertia";

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});

const page = usePage<
    PageProps & {
        edisiAktifLabel?: string;
        statusTim?: string;
        submissionCount?: number;
        nominasiCount?: number;
        nominasiList?: Array<{
            id: number;
            nama_karya: string;
            nama_kategori: string;
            pameran_file: string | null;
            pameran_submitted_at: string | null;
        }>;
    }
>();
const edisiAktifLabel = computed(() => page.props.edisiAktifLabel ?? "-");
const statusTim = computed(() => page.props.statusTim ?? "Belum Terdaftar");
const submissionCount = computed(() => page.props.submissionCount ?? 0);
const nominasiCount = computed(() => page.props.nominasiCount ?? 0);
const nominasiList = computed(() => page.props.nominasiList ?? []);
</script>

<template>
    <div class="space-y-4 sm:space-y-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-800">Dashboard Peserta</h1>
            <p class="text-sm text-slate-500 mt-1">
                Ringkasan tim dan progres submission Anda.
            </p>
        </div>

        <Card class="py-0 gap-0 border-slate-200">
            <CardContent class="px-4 sm:px-6 py-4">
                <p class="text-sm text-slate-500">Edisi Aktif</p>
                <h2 class="text-lg sm:text-xl font-semibold mt-1 text-slate-800">
                    {{ edisiAktifLabel }}
                </h2>
            </CardContent>
        </Card>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
            <Card class="py-0 gap-0 border-slate-200">
                <CardContent class="px-4 sm:px-6 py-4">
                    <p class="text-sm text-slate-500">Status Tim</p>
                    <h2 class="text-xl sm:text-2xl font-semibold mt-2">
                        {{ statusTim }}
                    </h2>
                </CardContent>
            </Card>
            <Card class="py-0 gap-0 border-slate-200">
                <CardContent class="px-4 sm:px-6 py-4">
                    <p class="text-sm text-slate-500">Submission Terkirim</p>
                    <h2 class="text-xl sm:text-2xl font-semibold mt-2">
                        {{ submissionCount }}
                    </h2>
                </CardContent>
            </Card>
            <Card class="py-0 gap-0 border-slate-200 sm:col-span-2 xl:col-span-1">
                <CardContent class="px-4 sm:px-6 py-4">
                    <p class="text-sm text-slate-500">Status Nominasi</p>
                    <h2
                        class="text-xl sm:text-2xl font-semibold mt-2"
                        :class="
                            nominasiCount > 0
                                ? 'text-emerald-600'
                                : 'text-amber-600'
                        "
                    >
                        {{ nominasiCount > 0 ? "Lolos Nominasi" : "Belum Ada" }}
                    </h2>
                </CardContent>
            </Card>
        </div>

        <Card
            v-if="nominasiCount > 0"
            class="py-0 gap-0 border-emerald-200 bg-emerald-50/40"
        >
            <CardHeader class="px-4 sm:px-6 pt-4 pb-3 border-b border-emerald-100">
                <CardTitle class="text-base text-emerald-800">
                    Karya Lolos Nominasi
                </CardTitle>
            </CardHeader>
            <CardContent class="px-4 sm:px-6 py-4 space-y-3">
                <div class="space-y-2">
                    <div
                        v-for="item in nominasiList"
                        :key="item.id"
                        class="flex flex-col gap-1 rounded-md border border-emerald-100 bg-white px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ item.nama_karya }}
                            </p>
                            <Badge
                                :class="
                                    item.pameran_file
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-amber-50 text-amber-700'
                                "
                            >
                                {{ item.pameran_file ? "File terkirim" : "Belum kirim file" }}
                            </Badge>
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ item.nama_kategori }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button as-child>
                        <Link href="/peserta/pameran-karya">
                            Kelola Link Pameran
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card class="py-0 gap-0 border-slate-200">
            <CardHeader class="px-4 sm:px-6 pt-4 pb-3 border-b border-slate-100">
                <CardTitle class="text-base text-slate-800">Informasi</CardTitle>
            </CardHeader>
            <CardContent class="px-4 sm:px-6 py-4">
                <p class="text-sm text-slate-500">
                    Gunakan menu di samping untuk melengkapi data tim dan mengirim submission.
                </p>
            </CardContent>
        </Card>
    </div>
</template>

