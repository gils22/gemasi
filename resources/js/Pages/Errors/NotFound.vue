<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-100">
        <div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center justify-center px-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.4em] text-slate-400">
                Error 404
            </p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">
                Halaman tidak ditemukan
            </h1>
            <p class="mt-3 text-sm text-slate-600 sm:text-base">
                Halaman yang kamu cari tidak tersedia atau sudah dipindahkan.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <Link
                    href="/"
                    class="inline-flex items-center rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-slate-400"
                >
                    Kembali ke Beranda
                </Link>
                <Link
                    href="/panduan"
                    class="inline-flex items-center rounded-full border border-transparent bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Lihat Panduan
                </Link>
            </div>
        </div>
    </div>
</template>
