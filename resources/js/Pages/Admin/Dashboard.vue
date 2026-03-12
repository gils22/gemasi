<script setup lang="ts">
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Archive, RotateCcw } from "lucide-vue-next";

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
}>();

const statistik = computed(() => page.props.statistik);
const ringkasanEdisi = computed(() => page.props.ringkasanEdisi);

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
                        onError: () =>
                            toast.error("Gagal menyelesaikan edisi"),
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
                            toast.success(
                                `Edisi ${edisi.nama} aktif kembali`,
                            ),
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
        <!-- Title -->
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">
                Dashboard Admin
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Data ringkas untuk edisi berjalan:
                <span class="font-medium text-slate-700">
                    {{ ringkasanEdisi.nama }} ({{ ringkasanEdisi.tahun }})
                </span>
            </p>
        </div>

        <!-- Test Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total User</p>
                <h2 class="text-2xl font-semibold mt-2">
                    {{ statistik.total_user }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Peserta</p>
                <h2 class="text-2xl font-semibold mt-2 text-emerald-600">
                    {{ statistik.total_peserta }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Admin & Juri</p>
                <h2 class="text-2xl font-semibold mt-2 text-blue-700">
                    {{ statistik.total_admin_juri }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Admin / Juri</p>
                <h2 class="text-2xl font-semibold mt-2 text-slate-800">
                    {{ statistik.total_admin }} / {{ statistik.total_juri }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Karya</p>
                <h2 class="text-2xl font-semibold mt-2 text-slate-800">
                    {{ statistik.total_karya }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Karya Draft</p>
                <h2 class="text-2xl font-semibold mt-2 text-slate-800">
                    {{ statistik.karya_draft }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Karya Terkirim</p>
                <h2 class="text-2xl font-semibold mt-2 text-emerald-600">
                    {{ statistik.karya_submitted }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Lolos Nominasi</p>
                <h2 class="text-2xl font-semibold mt-2 text-blue-700">
                    {{ statistik.karya_nominasi }}
                </h2>
            </div>

            <div class="bg-white border rounded-xl p-5 shadow-sm">
                <p class="text-sm text-slate-500">Dinilai Tahap 2</p>
                <h2 class="text-2xl font-semibold mt-2 text-slate-800">
                    {{ statistik.karya_dinilai_tahap_2 }}
                </h2>
            </div>
        </div>

        <!-- Placeholder Section -->
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-slate-600">
                    Status edisi:
                    <span class="font-medium capitalize">
                        {{ ringkasanEdisi.status }}
                    </span>
                    . Gunakan menu
                    <span class="font-medium">Edisi Tahun</span> untuk detail
                    lengkap.
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="ringkasanEdisi.status === 'aktif'"
                        variant="outline"
                        @click="selesaikanEdisi"
                    >
                        <Archive class="h-4 w-4" />
                        Selesaikan Lomba
                    </Button>
                    <Button
                        v-else-if="ringkasanEdisi.status === 'arsip'"
                        variant="outline"
                        @click="bukaKembaliEdisi"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Buka Kembali
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

