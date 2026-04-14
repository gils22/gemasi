<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, nextTick } from "vue";

type LandingTimeline = {
    id: number;
    judul: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    deskripsi?: string | null;
    urutan?: number | null;
};

const props = defineProps<{
    items?: LandingTimeline[];
}>();

const fallbackSteps = [
    { title: "Opening GEMASI", date: "1 Februari 2026" },
    { title: "Pendaftaran GEMASI", date: "1–28 Februari 2026" },
    { title: "Penjurian Tahap 1", date: "2–6 Maret 2026" },
    { title: "Pameran Karya", date: "12–15 Maret 2026" },
    { title: "Penjurian Tahap 2", date: "10 Maret 2026" },
    { title: "Awarding GEMASI", date: "16 Maret 2026" },
];

const formatDate = (value: string | null) => {
    if (!value) return "";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const steps = computed(() => {
    if (!props.items || props.items.length === 0) {
        return fallbackSteps;
    }

    return props.items.map((item) => {
        if (item.is_tba) {
            return { title: item.judul, date: "TBA" };
        }
        const start = formatDate(item.mulai_pada);
        const end = formatDate(item.selesai_pada);
        const date = start && end && start !== end ? `${start} – ${end}` : start || end;
        return { title: item.judul, date: date || "TBA" };
    });
});

const activeIndex = ref(0);
const wrapperRef = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;
let rafId = 0;

const updateProgress = () => {};
const scheduleUpdate = () => {};

onMounted(async () => {
    await nextTick();
    const items = Array.from(document.querySelectorAll("[data-step]"));
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const index = Number(
                        entry.target.getAttribute("data-index"),
                    );
                    if (!Number.isNaN(index)) {
                        activeIndex.value = index;
                    }
                }
            });
        },
        { rootMargin: "-20% 0px -40% 0px", threshold: 0.2 },
    );

    items.forEach((item) => observer?.observe(item));
    window.addEventListener("resize", scheduleUpdate);
});

onUnmounted(() => {
    observer?.disconnect();
    observer = null;
    window.removeEventListener("resize", scheduleUpdate);
    cancelAnimationFrame(rafId);
});
</script>

<template>
    <section id="timeline" class="reveal bg-transparent" data-reveal>
        <div class="mx-auto w-full max-w-6xl px-6 py-16">
            <div class="text-center flex flex-col gap-3 text-slate-900">
                <h2 class="text-3xl font-semibold">Timeline</h2>
            </div>

            <div ref="wrapperRef" class="relative mt-10">
                <div
                    class="absolute left-1/2 top-0 h-full w-1 -translate-x-1/2 bg-slate-200"
                />

                <div class="space-y-14">
                <div
                    v-for="(step, index) in steps"
                    :key="step.title"
                    data-step
                    :data-index="index"
                        class="relative grid items-start gap-6 grid-cols-[1fr_auto_1fr]"
                    >
                        <div
                            v-if="index % 2 === 0"
                            class="text-right pr-6 justify-self-end max-w-[280px]"
                        >
                            <div class="flex flex-col items-end gap-1">
                                <h3
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    {{ step.title }}
                                </h3>
                                <p
                                    class="text-sm text-slate-600 leading-relaxed"
                                >
                                    {{ step.date }}
                                </p>
                            </div>
                        </div>
                        <div v-else class="invisible" />

                        <div class="flex justify-center">
                            <div
                                class="z-10 h-3.5 w-3.5 rounded-full border border-white/70 bg-slate-300 transition"
                                :class="
                                    activeIndex >= index
                                        ? 'bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 shadow-[0_0_0_6px_rgba(99,102,241,0.18)]'
                                        : ''
                                "
                            />
                        </div>

                        <div
                            v-if="index % 2 === 1"
                            class="text-left pl-6 justify-self-start max-w-[280px]"
                        >
                            <div class="flex flex-col items-start gap-1">
                                <h3
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    {{ step.title }}
                                </h3>
                                <p
                                    class="text-sm text-slate-600 leading-relaxed"
                                >
                                    {{ step.date }}
                                </p>
                            </div>
                        </div>
                        <div v-else class="invisible" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
