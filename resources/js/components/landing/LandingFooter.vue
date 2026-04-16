<script setup lang="ts">
import { Instagram, Facebook, Youtube, Copyright } from "lucide-vue-next";
import FluidCursor from "@/components/ui/fluid-cursor/FluidCursor.vue";

import logoAmikom from "@/assets/logo-amikom.svg";
import logoSi from "@/assets/logo-si.svg";
</script>

<template>
    <footer
        class="relative overflow-hidden border-t border-slate-200 bg-gradient-to-b from-white via-white to-[#E0F2FF] backdrop-blur"
    >
        <FluidCursor :fixed="false" class="opacity-60" />
        <div class="pointer-events-none absolute inset-0"></div>

        <div class="relative mx-auto w-full max-w-6xl px-6 py-10 md:py-12">
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr]">
                <div class="space-y-4 text-left">
                    <div class="text-2xl font-semibold text-slate-700">
                        GEMASI
                    </div>
                    <p class="text-lg text-slate-600 leading-relaxed">
                        Gelar Karya Mahasiswa Sistem Informasi.
                    </p>
                    <div class="space-y-2">
                        <div
                            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            Presented by
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-start gap-4"
                        >
                            <div class="flex h-10 items-center justify-center">
                                <img
                                    :src="logoAmikom"
                                    alt="Logo Amikom"
                                    class="h-6 w-auto"
                                />
                            </div>
                            <div class="flex h-10 items-center justify-center">
                                <img
                                    :src="logoSi"
                                    alt="Logo Sistem Informasi"
                                    class="h-6 w-auto"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-6 items-start md:items-end">
                    <ul
                        class="grid gap-x-6 gap-y-2 text-sm text-slate-600 text-left md:block md:text-right md:space-y-3"
                    >
                        <li>
                            <a
                                href="/landing/panduan"
                                class="hover:text-slate-900"
                                >Panduan</a
                            >
                        </li>
                        <li>
                            <a
                                href="/landing/pameran"
                                class="hover:text-slate-900"
                                >Pameran</a
                            >
                        </li>
                        <li>
                            <a
                                href="/landing/nominate"
                                class="hover:text-slate-900"
                                >Nominate</a
                            >
                        </li>
                        <li>
                            <a
                                href="/landing/juara"
                                class="hover:text-slate-900"
                                >Juara</a
                            >
                        </li>
                    </ul>
                    <div
                        class="flex flex-wrap items-center justify-start gap-3 md:justify-end"
                    >
                        <a
                            href="#"
                            class="rounded-full border border-slate-200 bg-white/70 p-2 text-slate-600 transition hover:text-slate-900"
                        >
                            <Instagram class="h-4 w-4" />
                        </a>
                        <a
                            href="#"
                            class="rounded-full border border-slate-200 bg-white/70 p-2 text-slate-600 transition hover:text-slate-900"
                        >
                            <Youtube class="h-4 w-4" />
                        </a>
                        <a
                            href="#"
                            class="rounded-full border border-slate-200 bg-white/70 p-2 text-slate-600 transition hover:text-slate-900"
                        >
                            <Facebook class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="mx-auto w-full max-w-6xl px-6 pb-8 md:pb-10">
            <div
                class="text-center text-6xl sm:text-7xl lg:text-[12rem] font-semibold uppercase tracking-widest text-slate-200/70"
            >
                GEMASI
            </div>
        </div>

        <div class="relative border-t border-slate-200/70">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-6 py-6 text-xs text-slate-500 md:flex-row md:items-center md:justify-between text-left"
            >
                <p class="flex items-center gap-2">
                    <Copyright class="h-3.5 w-3.5 text-slate-400" />
                    GEMASI | Sistem Informasi Universitas Amikom Yogyakarta.
                </p>
                <div
                    class="flex items-center justify-start gap-4 md:justify-end"
                >
                    <a href="#" class="hover:text-slate-700">Terms</a>
                    <a href="#" class="hover:text-slate-700">Privacy</a>
                </div>
            </div>
        </div>
    </footer>
</template>
