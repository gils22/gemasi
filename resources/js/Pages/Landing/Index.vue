<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import TentangSection from "@/components/landing/sections/TentangSection.vue";
import MotionGraphicSection from "@/components/landing/sections/MotionGraphicSection.vue";
import TimerSection from "@/components/landing/sections/TimerSection.vue";
import KategoriSection from "@/components/landing/sections/KategoriSection.vue";
import TimelineSection from "@/components/landing/sections/TimelineSection.vue";
import FaqSection from "@/components/landing/sections/FaqSection.vue";
import type { PageProps } from "@/types/inertia";

type LandingPayload = {
    landing_edisi_lomba_id?: number | null;
    hero_badge: string | null;
    hero_title: string | null;
    hero_subtitle: string | null;
    about_text: string | null;
    video_file_url?: string | null;
    video_stream_url?: string | null;
    video_url: string | null;
    cta_badge: string | null;
    cta_label: string | null;
    cta_url: string | null;
    faq_items: Array<{ q: string; a: string }>;
};

type KategoriLanding = {
    id: number;
    nama: string;
    slug: string | null;
    deskripsi: string | null;
    icon_url?: string | null;
    weights?: Array<{ label: string; point: number; description?: string }>;
};

type TimelineLanding = {
    id: number;
    judul: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    deskripsi: string | null;
};

const page = usePage<
    PageProps & {
        landing?: LandingPayload | null;
        kategoriLanding?: KategoriLanding[];
        galleryLanding?: Array<{ name: string; preview_url: string }>;
        timelineLanding?: TimelineLanding[];
    }
>();
const landing = computed(() => page.props.landing ?? null);
const kategoriLanding = computed(() => page.props.kategoriLanding ?? []);
const galleryLanding = computed(() => page.props.galleryLanding ?? []);
const timelineLanding = computed(() => page.props.timelineLanding ?? []);
</script>

<template>
    <LandingLayout>
        <div class="min-h-screen w-full bg-white">
            <div class="relative overflow-hidden">
                <div class="relative z-10">
                    <TentangSection :landing="landing" />
                    <MotionGraphicSection
                        :video-src="
                            landing?.video_stream_url ||
                            landing?.video_file_url ||
                            landing?.video_url
                        "
                    />
                    <KategoriSection :categories="kategoriLanding" />
                    <TimerSection :items="timelineLanding" />
                    <TimelineSection :items="timelineLanding" />
                    <FaqSection
                        :faqs="landing ? landing.faq_items : undefined"
                    />
                </div>
            </div>
        </div>
    </LandingLayout>
</template>
