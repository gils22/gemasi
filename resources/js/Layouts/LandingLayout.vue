<script setup lang="ts">
import { onMounted, onUnmounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import LandingNavbar from "@/components/landing/LandingNavbar.vue";
import LandingFooter from "@/components/landing/LandingFooter.vue";

const page = usePage();

const scrollFromQuery = () => {
    const params = new URLSearchParams(window.location.search);
    const section = params.get("section");
    if (!section) return;
    const target = document.getElementById(section);
    if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
};

onMounted(scrollFromQuery);
onMounted(() => {
    document.body.classList.add("landing-scroll-hidden");
    document.documentElement.classList.add("landing-scroll-hidden");
});
onUnmounted(() => {
    document.body.classList.remove("landing-scroll-hidden");
    document.documentElement.classList.remove("landing-scroll-hidden");
});
watch(
    () => page.url,
    () => {
        scrollFromQuery();
    },
);
</script>

<template>
    <div
        class="relative flex min-h-screen w-full flex-col bg-transparent text-foreground"
    >
        <LandingNavbar />

        <main class="flex-1 pb-16">
            <slot />
        </main>

        <LandingFooter />
    </div>
</template>
