<script setup lang="ts">
import LandingLayout from "@/Layouts/LandingLayout.vue";
import GlareHover from "@/components/GlareHover/GlareHover.vue";
import { ArrowRight } from "lucide-vue-next";

const pameranUrl = "https://pameran.gemasi.id";
</script>

<template>
    <LandingLayout>
        <section
            class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-100"
        >
            <div
                class="mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-6 py-20 text-center"
            >
                <div class="flex flex-col items-center gap-6">
                    <h1
                        class="text-3xl font-semibold text-slate-900 sm:text-4xl"
                    >
                        Pameran Karya GEMASI
                    </h1>
                    <GlareHover
                        width="auto"
                        height="auto"
                        background="transparent"
                        borderColor="transparent"
                        borderRadius="9999px"
                        className="border-transparent"
                    >
                        <a
                            :href="pameranUrl"
                            target="_blank"
                            rel="noopener"
                            class="group inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/35 px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-[0_18px_40px_rgba(15,23,42,0.12)] backdrop-blur-xl transition hover:bg-white/50 hover:border-slate-600"
                        >
                            <span>Pameran Gemasi</span>
                            <ArrowRight
                                class="h-5 w-5 -rotate-45 transition-transform duration-200 group-hover:rotate-0"
                            />
                        </a>
                    </GlareHover>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
