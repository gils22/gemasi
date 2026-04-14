<script setup lang="ts">
import { onMounted, onUnmounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import LandingNavbar from "@/components/landing/LandingNavbar.vue";
import LandingFooter from "@/components/landing/LandingFooter.vue";

const page = usePage();
let revealObserver: IntersectionObserver | null = null;

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
onMounted(() => {
    const elements = Array.from(
        document.querySelectorAll<HTMLElement>("[data-reveal]"),
    );
    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    revealObserver?.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.18, rootMargin: "0px 0px -10% 0px" },
    );
    elements.forEach((el) => revealObserver?.observe(el));
});
onUnmounted(() => {
    document.body.classList.remove("landing-scroll-hidden");
    document.documentElement.classList.remove("landing-scroll-hidden");
    revealObserver?.disconnect();
    revealObserver = null;
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
