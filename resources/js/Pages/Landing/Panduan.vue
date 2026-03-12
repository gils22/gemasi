<script setup lang="ts">
import LandingLayout from "@/Layouts/LandingLayout.vue";
import PanduanTabs from "@/components/landing/PanduanTabs.vue";
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

                <PanduanTabs />
            </div>
        </section>
    </LandingLayout>
</template>
