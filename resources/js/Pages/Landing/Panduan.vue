<script setup lang="ts">
import { ref, onMounted } from "vue";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import PanduanTabs from "@/components/landing/PanduanTabs.vue";
import Skeleton from "@/components/ui/skeleton/Skeleton.vue";

const isLoading = ref(true);

type LandingKategori = {
    id: number;
    nama: string;
    slug?: string | null;
    deskripsi?: string | null;
    weights?: Array<{ label: string; point: number }>;
};

type TemplateProposal = {
    nama?: string | null;
    url?: string | null;
} | null;

const props = defineProps<{
    kategoriLanding?: LandingKategori[];
    ketentuanLanding?: string[];
    templateProposal?: TemplateProposal;
}>();

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 600);
});
</script>

<template>
    <LandingLayout>
        <section class="min-h-screen">
            <div class="mx-auto w-full max-w-6xl px-6 py-16 mt-6">
                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold text-slate-900">
                        Panduan
                    </h1>
                    <p class="text-sm text-slate-600">
                        Pilih kategori lomba untuk melihat panduan dan materi
                        pendukung.
                    </p>
                </div>

                <div v-if="isLoading" class="mt-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        <Skeleton class="h-9 w-full rounded-full sm:w-40 shadow-none" />
                        <Skeleton class="h-9 w-full rounded-full sm:w-44 shadow-none" />
                    </div>
                    <div class="mt-6 grid gap-6 lg:grid-cols-[220px_1fr] lg:items-start">
                        <div class="flex flex-col gap-3">
                            <Skeleton class="h-11 w-full rounded-2xl shadow-none" />
                            <Skeleton class="h-11 w-full rounded-2xl shadow-none" />
                            <Skeleton class="h-11 w-full rounded-2xl shadow-none" />
                            <Skeleton class="h-11 w-full rounded-2xl shadow-none" />
                        </div>
                        <div class="space-y-4">
                            <Skeleton class="h-28 w-full rounded-2xl shadow-none" />
                            <Skeleton class="h-40 w-full rounded-2xl shadow-none" />
                        </div>
                    </div>
                </div>
                <PanduanTabs
                    v-else
                    :categories="props.kategoriLanding"
                    :ketentuan="props.ketentuanLanding"
                    :template-proposal="props.templateProposal"
                />
            </div>
        </section>
    </LandingLayout>
</template>
