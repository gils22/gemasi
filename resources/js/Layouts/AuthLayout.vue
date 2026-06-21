<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import heroImage from "@/assets/gemasi2025.png";

const page = usePage<{
    login_carousel_images?: Array<{
        name: string | null;
        url?: string;
        preview_url?: string;
    }>;
}>();

const carouselImages = computed(
    () => page.props.login_carousel_images ?? [],
);
const activeSlide = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

const hasCarousel = computed(() => carouselImages.value.length > 0);
const activeImage = computed(
    () => carouselImages.value[activeSlide.value] ?? null,
);

const nextSlide = () => {
    if (!hasCarousel.value) return;
    activeSlide.value = (activeSlide.value + 1) % carouselImages.value.length;
};

onMounted(() => {
    if (hasCarousel.value) {
        timer = setInterval(nextSlide, 4500);
    }
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <div class="min-h-screen bg-background">
        <a
            href="/"
            class="absolute left-6 top-6 z-10 inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900"
        >
            <span
                class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-700"
            >
                G
            </span>
            GEMASI
        </a>
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <div class="flex min-h-screen items-center justify-center px-6 py-12 lg:min-h-0">
                <div class="w-full max-w-sm rounded-xl border bg-card p-8 shadow-sm">
                    <slot />
                </div>
            </div>

            <div class="relative hidden min-h-screen md:block">
                <div
                    v-if="hasCarousel"
                    class="absolute inset-0 overflow-hidden bg-slate-100"
                >
                    <div class="relative h-full w-full">
                        <transition name="carousel-slide" mode="out-in">
                            <img
                                v-if="activeImage"
                                :key="activeImage.preview_url || activeImage.url || `slide-${activeSlide}`"
                                :src="activeImage.preview_url || activeImage.url || heroImage"
                                :alt="activeImage.name || 'Login carousel'"
                                class="absolute inset-0 h-full w-full object-cover"
                            />
                        </transition>
                    </div>
                    <div
                        class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2"
                    >
                        <button
                            v-for="(image, index) in carouselImages"
                            :key="`dot-${image.preview_url || image.url || index}`"
                            type="button"
                            class="h-2.5 rounded-full transition-all duration-300"
                            :class="
                                index === activeSlide
                                    ? 'w-8 bg-white'
                                    : 'w-2.5 bg-white/50'
                            "
                            @click="activeSlide = index"
                        />
                    </div>
                </div>
                <template v-else>
                    <div
                        class="absolute inset-0 bg-cover bg-center"
                        :style="{ backgroundImage: `url(${heroImage})` }"
                    />
                    <div class="absolute inset-0 bg-slate-900/20" />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.carousel-slide-enter-active,
.carousel-slide-leave-active {
    transition:
        opacity 0.7s ease,
        transform 0.7s ease;
}

.carousel-slide-enter-from {
    opacity: 0;
    transform: translateX(10%);
}

.carousel-slide-leave-to {
    opacity: 0;
    transform: translateX(-10%);
}
</style>
