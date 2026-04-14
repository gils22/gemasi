<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";
import TentangSection from "@/components/landing/sections/TentangSection.vue";
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
    weights?: Array<{ label: string; point: number }>;
};

type TimelineLanding = {
    id: number;
    judul: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    deskripsi: string | null;
    urutan: number | null;
};

const page = usePage<
    PageProps & {
        landing?: LandingPayload | null;
        kategoriLanding?: KategoriLanding[];
        timelineLanding?: TimelineLanding[];
    }
>();
const landing = computed(() => page.props.landing ?? null);
const kategoriLanding = computed(() => page.props.kategoriLanding ?? []);
const timelineLanding = computed(() => page.props.timelineLanding ?? []);
</script>

<template>
    <LandingLayout>
        <div class="min-h-screen w-full bg-white">
            <div class="relative overflow-hidden">
                <div class="relative z-10">
                <TentangSection :landing="landing" />
                <TimerSection :items="timelineLanding" />
                <KategoriSection :categories="kategoriLanding" />
                <TimelineSection :items="timelineLanding" />
                <FaqSection :faqs="landing ? landing.faq_items : undefined" />
                </div>
            </div>
        </div>
    </LandingLayout>
</template>
