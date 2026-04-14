<script setup lang="ts">
import { computed } from "vue";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/components/ui/accordion";

type FaqItem = { q: string; a: string };

const props = defineProps<{
    faqs?: FaqItem[];
}>();

const fallbackFaqs: FaqItem[] = [
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

const faqList = computed(() =>
    props.faqs !== undefined ? props.faqs : fallbackFaqs,
);
const showFaq = computed(() => faqList.value.length > 0);
</script>

<template>
    <section v-if="showFaq" class="reveal bg-transparent" data-reveal>
        <div class="mx-auto w-full max-w-6xl px-12 pb-20">
            <div class="flex flex-col gap-2 text-center">
                <h2 class="text-3xl font-semibold">FAQ</h2>
            </div>

            <Accordion type="single" collapsible class="mt-6">
                <AccordionItem
                    v-for="item in faqList"
                    :key="item.q"
                    :value="item.q"
                    class="border-b border-slate-200 px-0"
                >
                    <AccordionTrigger
                        class="py-4 text-base font-semibold text-slate-900 hover:no-underline"
                    >
                        {{ item.q }}
                    </AccordionTrigger>
                    <AccordionContent class="text-base text-slate-600">
                        {{ item.a }}
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>
    </section>
</template>
