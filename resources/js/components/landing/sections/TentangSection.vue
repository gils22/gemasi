<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import { ArrowRight } from "lucide-vue-next";
import GlareHover from "@/components/GlareHover/GlareHover.vue";

let typingTimer: number | undefined;
let typingPauseTimer: number | undefined;

const fullDescription =
    "Gelar Karya Mahasiswa Sistem Informasi (GEMASI) adalah ajang kompetisi yang mewadahi karya inovatif mahasiswa dari hasil final project mata kuliah. GEMASI membangun atmosfer kompetisi yang mendorong kreativitas, inovasi, serta membuka peluang jejaring untuk berkembang ke tingkat nasional dan internasional.";
const typedDescription = ref("");

const startTyping = () => {
    let index = 0;
    typedDescription.value = "";
    typingTimer = window.setInterval(() => {
        typedDescription.value = fullDescription.slice(0, index + 1);
        index += 1;
        if (index >= fullDescription.length) {
            window.clearInterval(typingTimer);
            typingPauseTimer = window.setTimeout(() => {
                startTyping();
            }, 5000);
        }
    }, 18);
};

onMounted(() => {
    startTyping();
});

onUnmounted(() => {
    if (typingTimer) {
        window.clearInterval(typingTimer);
    }
    if (typingPauseTimer) {
        window.clearTimeout(typingPauseTimer);
    }
});
</script>

<template>
    <section id="tentang" class="relative">
        <div class="mx-auto w-full max-w-5xl px-6 pt-28 pb-20 text-center">
            <div
                class="mx-auto inline-flex items-center gap-2 rounded-full border border-white/40 bg-white/50 px-4 py-1 text-[11px] sm:text-xs font-semibold uppercase tracking-widest text-slate-600 backdrop-blur"
            >
                GEMASI 2026
            </div>

            <h1
                class="mt-4 text-3xl sm:text-4xl md:text-5xl font-semibold leading-tight text-slate-900"
            >
                Beyond Innovation
            </h1>
            <p class="font-semibold mt-3 text-base sm:text-lg text-slate-600">
                Gelar Karya Mahasiswa Sistem Informasi Universitas Amikom
                Yogyakarta
            </p>

            <div
                class="mx-auto mt-6 max-w-3xl rounded-3xl bg-white/70 p-3 sm:p-4 shadow-[inset_0_12px_30px_rgba(148,163,184,0.22)]"
            >
                <div
                    class="h-[240px] sm:min-h-[200px] rounded-2xl border border-white/60 bg-white/70 p-4 sm:p-6 text-left text-sm sm:text-base text-slate-500 shadow-sm backdrop-blur"
                >
                    <p class="leading-relaxed">
                        {{ typedDescription }}
                        <span class="inline-block w-2 animate-pulse">|</span>
                    </p>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <GlareHover
                    width="auto"
                    height="auto"
                    background="transparent"
                    borderColor="transparent"
                    borderRadius="9999px"
                    className="border-transparent"
                >
                    <a
                        href="/login"
                        class="group inline-flex items-center gap-3 rounded-full border border-white/60 bg-white/35 px-4 sm:px-5 py-2 sm:py-2.5 text-sm font-semibold text-slate-700 shadow-[0_18px_40px_rgba(15,23,42,0.12)] backdrop-blur-xl transition hover:bg-white/50 hover:border-white"
                    >
                        <span class="hidden sm:inline"
                            >GEMASI 2026 • Beyond Innovation</span
                        >
                        <span class="hidden sm:inline h-4 w-px bg-slate-200" />
                        <span
                            class="inline-flex items-center gap-1 text-slate-900 group"
                        >
                            Daftar Sekarang
                            <ArrowRight
                                class="h-5 w-5 -rotate-45 transition-transform duration-200 group-hover:rotate-0 mt-1"
                            />
                        </span>
                    </a>
                </GlareHover>
            </div>
        </div>
    </section>
</template>
