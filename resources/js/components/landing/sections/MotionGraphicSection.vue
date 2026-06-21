<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";

const props = defineProps<{
    title?: string;
    subtitle?: string;
    videoSrc?: string;
    posterSrc?: string;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);

const rawSource = computed(() => props.videoSrc?.trim() ?? "");
const fallbackSource = "/videos/motiongraphic.mp4";

const youtubeId = computed(() => {
    if (!rawSource.value) return "";

    const patterns = [
        /(?:youtu\.be\/)([A-Za-z0-9_-]{6,})/,
        /(?:youtube\.com\/watch\?v=)([A-Za-z0-9_-]{6,})/,
        /(?:youtube\.com\/embed\/)([A-Za-z0-9_-]{6,})/,
        /(?:youtube\.com\/shorts\/)([A-Za-z0-9_-]{6,})/,
    ];

    for (const pattern of patterns) {
        const match = rawSource.value.match(pattern);
        if (match?.[1]) return match[1];
    }

    return "";
});

const isYoutube = computed(() => youtubeId.value.length > 0);
const resolvedSource = computed(() =>
    isYoutube.value ? youtubeId.value : rawSource.value || fallbackSource,
);
const embedUrl = computed(() =>
    isYoutube.value
        ? `https://www.youtube-nocookie.com/embed/${resolvedSource.value}?autoplay=1&mute=1&loop=1&playsinline=1&controls=1&rel=0&playlist=${resolvedSource.value}`
        : resolvedSource.value,
);

const forcePlay = async () => {
    if (isYoutube.value) return;

    await nextTick();
    const video = videoRef.value;
    if (!video) return;

    try {
        video.muted = true;
        video.playsInline = true;
        await video.play();
    } catch {}
};

onMounted(forcePlay);
watch(() => embedUrl.value, forcePlay);
</script>

<template>
    <section
        id="motion-graphic"
        data-reveal
        class="reveal--hero relative bg-white py-8 sm:py-12"
    >
        <div class="mx-auto w-full max-w-5xl px-6">
            <div
                class="overflow-hidden rounded-sm border border-white bg-slate-50 p-3"
            >
                <div class="overflow-hidden rounded-sm shadow-inner">
                    <iframe
                        v-if="isYoutube"
                        :src="embedUrl"
                        class="aspect-video w-full"
                        title="Motion Graphic GEMASI"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    />
                    <video
                        v-else
                        ref="videoRef"
                        :src="embedUrl"
                        :poster="props.posterSrc"
                        class="aspect-video w-full object-cover"
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="auto"
                        controls
                    />
                </div>
            </div>
        </div>
    </section>
</template>
