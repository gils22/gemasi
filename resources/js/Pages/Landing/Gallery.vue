<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import LandingLayout from "@/Layouts/LandingLayout.vue";

import { Card, CardContent } from "@/components/ui/card";

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
};

type GalleryItem = {
    id: number;
    name: string;
    preview_url: string;
    edisi_id: number;
};

const page = usePage<{
    daftarEdisi: Edisi[];
    galleryItems: GalleryItem[];
}>();

const daftarEdisi = computed(() => page.props.daftarEdisi ?? []);
const galleryItems = computed(() => page.props.galleryItems ?? []);

const activeEdisiId = ref<number | null>(null);

const activeEdisiValue = computed({
    get: () => (activeEdisiId.value ? String(activeEdisiId.value) : ""),
    set: (value: string) => {
        activeEdisiId.value = value ? Number(value) : null;
    },
});

watch(
    daftarEdisi,
    (items) => {
        if (!items.length) return;

        if (!activeEdisiId.value) {
            activeEdisiId.value = items[0].id;
        }
    },
    { immediate: true },
);

const filteredItems = computed(() => {
    if (!activeEdisiId.value) {
        return galleryItems.value;
    }

    return galleryItems.value.filter(
        (item) => item.edisi_id === activeEdisiId.value,
    );
});
</script>

<template>
    <LandingLayout>
        <section
            class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-100"
        >
            <div class="mx-auto w-full max-w-6xl px-6 py-16 mt-6">
                <div class="space-y-2 text-center">
                    <h1 class="text-3xl font-semibold text-slate-900">
                        Gallery GEMASI
                    </h1>

                    <p class="mx-auto max-w-2xl text-sm text-slate-600">
                        Dokumentasi kegiatan dan karya terbaik peserta GEMASI.
                    </p>
                </div>

                <!-- Sama seperti halaman Juara -->
                <div
                    class="mt-6 flex w-full flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <div class="w-full sm:w-[200px]">
                        <Select v-model="activeEdisiValue">
                            <SelectTrigger
                                class="w-full rounded-lg border-slate-200 bg-white/80 text-sm font-semibold text-slate-900"
                            >
                                <SelectValue placeholder="Pilih Tahun" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem
                                    v-for="edisi in daftarEdisi"
                                    :key="edisi.id"
                                    :value="String(edisi.id)"
                                >
                                    {{ edisi.tahun }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    v-if="!filteredItems.length"
                    class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500"
                >
                    Belum ada dokumentasi yang tersedia.
                </div>

                <div
                    v-else
                    class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Card
                        v-for="item in filteredItems"
                        :key="item.id"
                        class="overflow-hidden rounded-lg border-slate-200 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
                    >
                        <div class="aspect-[4/3] overflow-hidden bg-slate-100">
                            <img
                                :src="item.preview_url"
                                :alt="item.name"
                                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                            />
                        </div>

                        <CardContent class="p-4">
                            <h3
                                class="line-clamp-1 text-sm font-medium text-slate-900"
                            >
                                {{ item.name }}
                            </h3>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
