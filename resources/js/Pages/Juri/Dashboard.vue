<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import type { PageProps } from "@/types/inertia";

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});

const page = usePage<PageProps>();
const edisiAktif = computed(() => page.props.edisi?.aktif);
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Dashboard Juri</h1>
            <p class="text-sm text-slate-500 mt-1">
                Ringkasan proses penilaian untuk edisi yang dipilih.
            </p>
        </div>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Edisi Aktif</p>
            <h2 class="text-xl font-semibold mt-1 text-slate-800">
                {{ edisiAktif ? `${edisiAktif.nama} (${edisiAktif.tahun})` : "-" }}
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Submission Ditugaskan</p>
                <h2 class="text-2xl font-semibold mt-2">0</h2>
            </div>
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Sudah Dinilai</p>
                <h2 class="text-2xl font-semibold mt-2 text-emerald-600">0</h2>
            </div>
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Belum Dinilai</p>
                <h2 class="text-2xl font-semibold mt-2 text-rose-600">0</h2>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-800">Catatan Penilaian</h3>
            <p class="text-sm text-slate-500 mt-2">
                Area ini nanti untuk menampilkan progres dan catatan review terbaru.
            </p>
        </div>
    </div>
</template>

