<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import GlareHover from "@/components/GlareHover/GlareHover.vue";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";

const quickLinks = [
    {
        label: "Panduan",
        href: "/panduan",
    },
    {
        label: "Pameran",
        href: "/pameran",
    },
    {
        label: "Nominate",
        href: "/nominate",
    },
    {
        label: "Juara",
        href: "/juara",
    },
];
</script>

<template>
    <header
        class="reveal--nav fixed inset-x-0 top-0 z-50 bg-white/70 backdrop-blur-md"
        data-reveal
    >
        <div class="relative">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div
                    class="h-14 sm:h-16 flex items-center border-b border-white/60"
                >
                    <Link
                        href="/"
                        class="flex items-center gap-3 text-slate-900 font-semibold tracking-tight"
                    >
                        <div
                            class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/70 flex items-center justify-center border border-white/70 shadow-sm"
                        >
                            <span class="text-sm">G</span>
                        </div>
                        GEMASI
                    </Link>

                    <nav
                        class="hidden md:flex flex-1 items-center justify-center gap-12 text-sm text-slate-900"
                    >
                        <Link
                            href="/panduan"
                            class="font-semibold hover:underline hover:underline-offset-4 decoration-1 decoration-slate-900 transition"
                        >
                            Panduan
                        </Link>
                        <Link
                            href="/pameran"
                            class="font-semibold hover:underline hover:underline-offset-4 decoration-1 decoration-slate-900 transition"
                        >
                            Pameran
                        </Link>
                        <Link
                            href="/nominate"
                            class="font-semibold hover:underline hover:underline-offset-4 decoration-1 decoration-slate-900 transition"
                        >
                            Nominate
                        </Link>
                        <Link
                            href="/juara"
                            class="font-semibold hover:underline hover:underline-offset-4 decoration-1 decoration-slate-900 transition"
                        >
                            Juara
                        </Link>
                    </nav>

                    <div class="ml-auto hidden md:flex items-center">
                        <GlareHover
                            width="auto"
                            height="auto"
                            background="transparent"
                            borderColor="transparent"
                            borderRadius="9999px"
                            className="border-transparent"
                        >
                            <Button
                                as-child
                                size="lg"
                                class="rounded-full bg-slate-900 text-white justify-center hover:bg-slate-800"
                            >
                                <Link href="/login">Masuk</Link>
                            </Button>
                        </GlareHover>
                    </div>

                    <div class="ml-auto flex items-center gap-2 md:hidden">
                        <Popover v-slot="{ open }">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9 rounded-md text-slate-700 hover:text-slate-900 hover:bg-white/60"
                                >
                                    <span
                                        class="relative h-3 w-4"
                                        aria-hidden="true"
                                    >
                                        <span
                                            class="absolute left-0 h-0.5 w-full rounded-full bg-current transition-all duration-300"
                                            :class="
                                                open
                                                    ? 'top-1/2 -translate-y-1/2 rotate-45'
                                                    : 'top-[2px] translate-y-0 rotate-0'
                                            "
                                        />
                                        <span
                                            class="absolute left-0 h-0.5 w-full rounded-full bg-current transition-all duration-300"
                                            :class="
                                                open
                                                    ? 'top-1/2 -translate-y-1/2 -rotate-45'
                                                    : 'bottom-[2px] translate-y-0 rotate-0'
                                            "
                                        />
                                    </span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent
                                align="start"
                                side="bottom"
                                :side-offset="0"
                                class="w-screen bg-transparent p-0 mt-2 shadow-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=open]:fade-in-0 data-[state=closed]:fade-out-0 data-[state=open]:slide-in-from-top-2 data-[state=closed]:slide-out-to-top-2"
                            >
                                <div
                                    class="w-full bg-white/40 shadow-[0_24px_70px_rgba(15,23,42,0.12)] backdrop-blur-lg"
                                >
                                    <div
                                        class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 sm:px-6 py-4 sm:py-6 items-center"
                                    >
                                        <GlareHover
                                            v-for="item in quickLinks"
                                            :key="item.label"
                                            width="100%"
                                            height="auto"
                                            background="transparent"
                                            borderColor="transparent"
                                            borderRadius="12px"
                                            className="border-transparent w-full"
                                        >
                                            <Button
                                                as-child
                                                variant="ghost"
                                                class="w-full max-w-sm sm:max-w-md justify-center rounded-lg text-slate-700 hover:bg-white/70 hover:text-slate-900"
                                            >
                                                <Link :href="item.href">
                                                    {{ item.label }}
                                                </Link>
                                            </Button>
                                        </GlareHover>
                                        <GlareHover
                                            width="100%"
                                            height="auto"
                                            background="transparent"
                                            borderColor="transparent"
                                            borderRadius="12px"
                                            className="border-transparent w-full"
                                        >
                                            <Button
                                                as-child
                                                class="w-full max-w-sm sm:max-w-md rounded-xl bg-slate-900 text-white justify-center hover:bg-slate-800"
                                            >
                                                <Link href="/login">Masuk</Link>
                                            </Button>
                                        </GlareHover>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
