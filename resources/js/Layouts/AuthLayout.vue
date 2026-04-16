<script setup lang="ts">
import heroImage from "@/assets/gemasi2025.png";
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
                    class="absolute inset-0 bg-cover bg-center"
                    :style="{ backgroundImage: `url(${heroImage})` }"
                />
                <div class="absolute inset-0 bg-slate-900/20" />
            </div>
        </div>
    </div>
</template>
