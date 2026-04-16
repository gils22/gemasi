<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import type { PageProps } from "@/types/inertia";

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Dashboard" }, () => page),
});

const page = usePage<
    PageProps & {
        edisiAktifLabel?: string;
    }
>();
const edisiAktifLabel = computed(() => page.props.edisiAktifLabel ?? "-");
const namaPeserta = computed(() => page.props.auth?.user?.name ?? "Peserta");
const jam = new Date().getHours();
const sapaan = computed(() => {
    if (jam >= 4 && jam < 11) return "Selamat pagi";
    if (jam >= 11 && jam < 15) return "Selamat siang";
    if (jam >= 15 && jam < 18) return "Selamat sore";
    return "Selamat malam";
});
</script>

<template>
    <div>
        <div
            class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ sapaan }}, {{ namaPeserta }}.
                </h1>
                <p class="text-sm text-slate-600">
                    {{ edisiAktifLabel }}
                </p>
            </div>
            <div
                class="absolute right-4 top-4 inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700"
            >
                Peserta
            </div>
        </div>

    </div>
</template>
