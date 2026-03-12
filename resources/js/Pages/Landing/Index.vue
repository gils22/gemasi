<script setup lang="ts">
import LandingLayout from "@/Layouts/LandingLayout.vue";
import AuroraBackground from "@/components/ui/aurora-background/AuroraBackground.vue";
import TentangSection from "@/components/landing/sections/TentangSection.vue";
import TimerSection from "@/components/landing/sections/TimerSection.vue";
import KategoriSection from "@/components/landing/sections/KategoriSection.vue";
import TimelineSection from "@/components/landing/sections/TimelineSection.vue";
import FaqSection from "@/components/landing/sections/FaqSection.vue";
</script>

<template>
    <LandingLayout>
        <AuroraBackground class="min-h-screen w-full">
            <div class="relative">
                <TentangSection />
                <TimerSection />
                <KategoriSection />
                <TimelineSection />
                <FaqSection />
            </div>
        </AuroraBackground>
    </LandingLayout>
</template>
