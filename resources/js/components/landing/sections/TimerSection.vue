<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";

type TimelineItem = {
    judul: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    aktif?: boolean;
};

const props = defineProps<{
    items?: TimelineItem[];
}>();
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

const matchKeywords = (title: string, keywords: string[]) =>
    keywords.some((keyword) => new RegExp(keyword, "i").test(title));

const findByKeywords = (items: TimelineItem[], keywords: string[]) => {
    const activeItems = items.filter((item) => item.aktif !== false);
    return (
        activeItems.find((item) => matchKeywords(item.judul, keywords)) ??
        items.find((item) => matchKeywords(item.judul, keywords)) ??
        null
    );
};

const parseDate = (value: string, endOfDay = false) => {
    if (value.includes("T")) {
        return new Date(value);
    }
    return new Date(`${value}T${endOfDay ? "23:59:59" : "00:00:00"}+07:00`);
};

const timelines = computed(() => props.items ?? []);

const openingTimeline = computed(() =>
    findByKeywords(timelines.value, ["opening", "pembukaan"]),
);

const registrationTimeline = computed(() =>
    findByKeywords(timelines.value, ["pendaftaran", "registrasi", "daftar"]),
);

const registrationExtension = computed(() =>
    findByKeywords(timelines.value, ["perpanjangan", "extend", "extension"]),
);

const selectedTimeline = computed(() => {
    const candidates = [
        registrationExtension.value,
        registrationTimeline.value,
        openingTimeline.value,
    ].filter(Boolean) as TimelineItem[];

    const activeCandidate =
        candidates.find((item) => item.aktif !== false) ?? null;
    return activeCandidate ?? candidates[0] ?? null;
});

const targetDate = computed(() => {
    const item = selectedTimeline.value;
    if (!item || item.is_tba) return null;
    const preferEnd =
        registrationExtension.value === item ||
        registrationTimeline.value === item;
    const targetRaw = preferEnd
        ? item.selesai_pada || item.mulai_pada
        : item.mulai_pada || item.selesai_pada;
    if (!targetRaw) return null;
    return parseDate(targetRaw, Boolean(item.selesai_pada && preferEnd));
});

const remaining = computed(() => {
    if (!targetDate.value) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0 };
    }
    const diff = Math.max(0, targetDate.value.getTime() - now.value);
    if (diff <= 0) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0 };
    }
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
    <section class="reveal relative" data-reveal>
        <div class="mx-auto w-full max-w-5xl px-4 sm:px-6 pb-16">
            <div
                class="px-5 sm:px-6 py-6 sm:py-12 text-slate-900 border-2 border-slate-200 rounded-xl shadow-[inset_0_0_0_1px_rgba(255,255,255,0.35)]"
            >
                <div class="text-center">
                    <h2
                        class="text-xl sm:text-3xl font-semibold tracking-wide text-slate-900"
                    >
                        Countdown GEMASI
                    </h2>
                </div>

                <div class="mt-4 sm:mt-6">
                    <p
                        class="text-center text-base sm:text-lg font-semibold text-slate-700"
                    >
                        {{
                            selectedTimeline?.judul ||
                            "Timeline aktif belum tersedia"
                        }}
                    </p>
                    <div
                        class="mx-auto mt-6 grid w-fit grid-cols-4 items-start justify-center gap-x-16 sm:gap-x-20"
                    >
                        <div
                            class="timer-unit flex flex-col items-center gap-3"
                        >
                            <div class="timer-box-wrap has-colon">
                                <div
                                    class="timer-box flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-200/60 bg-gradient-to-b from-blue-500 to-blue-600 text-xl font-semibold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                                >
                                    {{ pad2(remaining.days) }}
                                </div>
                            </div>
                            <span class="text-sm sm:text-base text-slate-600"
                                >Hari</span
                            >
                        </div>
                        <div
                            class="timer-unit flex flex-col items-center gap-3"
                        >
                            <div class="timer-box-wrap has-colon">
                                <div
                                    class="timer-box flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-200/60 bg-gradient-to-b from-blue-500 to-blue-600 text-xl font-semibold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                                >
                                    {{ pad2(remaining.hours) }}
                                </div>
                            </div>
                            <span class="text-sm sm:text-base text-slate-600"
                                >Jam</span
                            >
                        </div>
                        <div
                            class="timer-unit flex flex-col items-center gap-3"
                        >
                            <div class="timer-box-wrap has-colon">
                                <div
                                    class="timer-box flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-200/60 bg-gradient-to-b from-blue-500 to-blue-600 text-xl font-semibold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                                >
                                    {{ pad2(remaining.minutes) }}
                                </div>
                            </div>
                            <span class="text-sm sm:text-base text-slate-600"
                                >Menit</span
                            >
                        </div>
                        <div
                            class="timer-unit flex flex-col items-center gap-3"
                        >
                            <div class="timer-box-wrap">
                                <div
                                    class="timer-box flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-200/60 bg-gradient-to-b from-blue-500 to-blue-600 text-xl font-semibold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] sm:h-28 sm:w-28 sm:text-4xl lg:h-32 lg:w-32 lg:text-5xl"
                                >
                                    {{ pad2(remaining.seconds) }}
                                </div>
                            </div>
                            <span class="text-sm sm:text-base text-slate-600"
                                >Detik</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.timer-box {
    font-variant-numeric: tabular-nums;
}

.timer-unit {
    position: relative;
}

.timer-box-wrap {
    position: relative;
}

.timer-box-wrap.has-colon::after {
    content: ":";
    position: absolute;
    right: -28px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.25rem;
    line-height: 1;
}

@media (min-width: 640px) {
    .timer-box-wrap.has-colon::after {
        right: -38px;
        font-size: 2.25rem;
    }
}

@media (min-width: 1024px) {
    .timer-box-wrap.has-colon::after {
        right: -44px;
        font-size: 3rem;
    }
}
</style>
