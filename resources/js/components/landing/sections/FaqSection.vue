<script setup lang="ts">
import { ChevronDown } from "lucide-vue-next";

const faqs = [
    {
        q: "Siapa saja yang boleh mengikuti GEMASI?",
        a: "Peserta adalah mahasiswa Prodi Sistem Informasi sesuai ketentuan pada edisi yang aktif.",
    },
    {
        q: "Apakah kategori lomba selalu sama setiap tahun?",
        a: "Tidak. Kategori dapat berubah mengikuti tema dan edisi yang sedang berjalan.",
    },
    {
        q: "Bagaimana cara mengunggah karya?",
        a: "Unggah karya melalui dashboard peserta pada menu Daftar Karya sesuai tahapan yang tersedia.",
    },
    {
        q: "Kapan pengumuman nominasi dan pemenang?",
        a: "Jadwal pengumuman mengikuti timeline resmi dan diperbarui oleh panitia.",
    },
    {
        q: "Di mana saya bisa melihat panduan lengkap?",
        a: "Panduan tersedia pada halaman Panduan di menu atas landing.",
    },
];
</script>

<template>
    <section class="bg-transparent">
        <div class="mx-auto w-full max-w-6xl px-6 pb-20">
            <div class="flex flex-col gap-2 text-center">
                <h2 class="text-3xl font-semibold">FAQ</h2>
            </div>

            <div class="mt-6 space-y-3">
                <details
                    v-for="item in faqs"
                    :key="item.q"
                    class="group rounded-2xl border border-slate-200 bg-white/70 p-4"
                >
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between text-base font-semibold text-slate-900"
                    >
                        <span>{{ item.q }}</span>
                        <ChevronDown
                            class="h-4 w-4 text-slate-900 transition-transform duration-300 group-open:rotate-180"
                        />
                    </summary>
                    <div
                        class="grid transition-all duration-300 [grid-template-rows:0fr] group-open:[grid-template-rows:1fr]"
                    >
                        <div class="overflow-hidden">
                            <p class="mt-2 text-base text-slate-600">
                                {{ item.a }}
                            </p>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </section>
</template>
