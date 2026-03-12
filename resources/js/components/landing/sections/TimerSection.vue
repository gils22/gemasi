<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";

const deadline = new Date("2026-12-31T23:59:59+07:00");
const now = ref(Date.now());

let timer: number | undefined;

onMounted(() => {
    timer = window.setInterval(() => {
        now.value = Date.now();
    }, 500);
});

onUnmounted(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});

const remaining = computed(() => {
    const diff = Math.max(0, deadline.getTime() - now.value);
    const totalSeconds = Math.floor(diff / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return { days, hours, minutes, seconds };
});

const pad2 = (value: number) => String(value).padStart(2, "0");
</script>

<template>
    <section class="relative">
        <div class="mx-auto w-full max-w-6xl px-6 pb-16">
            <div
                class="px-6 py-12 text-slate-900 border-2 border-slate-200 rounded-xl shadow-[inset_0_0_0_1px_rgba(255,255,255,0.35)]"
            >
                <h2
                    class="text-center text-xl sm:text-3xl font-semibold tracking-wide text-slate-900"
                >
                    Daftar Sekarang Sebelum :
                </h2>

                <div
                    class="mt-10 flex items-center justify-center gap-2 sm:gap-6 flex-nowrap"
                >
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-xl border border-white/50 bg-white/40 text-xl font-semibold text-slate-900 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.35)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                        >
                            {{ pad2(remaining.days) }}
                        </div>
                        <span class="text-sm sm:text-base text-slate-700"
                            >Hari</span
                        >
                    </div>
                    <span class="text-2xl sm:text-5xl text-slate-500"> : </span>
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-xl border border-white/50 bg-white/40 text-xl font-semibold text-slate-900 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.35)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                        >
                            {{ pad2(remaining.hours) }}
                        </div>
                        <span class="text-sm sm:text-base text-slate-700"
                            >Jam</span
                        >
                    </div>
                    <span class="text-2xl sm:text-5xl text-slate-500"> : </span>
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-xl border border-white/50 bg-white/40 text-xl font-semibold text-slate-900 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.35)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                        >
                            {{ pad2(remaining.minutes) }}
                        </div>
                        <span class="text-sm sm:text-base text-slate-700"
                            >Menit</span
                        >
                    </div>
                    <span class="text-2xl sm:text-5xl text-slate-500"> : </span>
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-xl border border-white/50 bg-white/40 text-xl font-semibold text-slate-900 shadow-[inset_0_0_0_1px_rgba(255,255,255,0.35)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                        >
                            {{ pad2(remaining.seconds) }}
                        </div>
                        <span class="text-sm sm:text-base text-slate-700"
                            >Detik</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
