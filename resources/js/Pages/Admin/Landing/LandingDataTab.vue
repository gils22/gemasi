<script setup lang="ts">
import { computed } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

type LandingKategori = {
    id: number;
    nama: string;
    deskripsi: string | null;
    aktif: boolean;
};

type LandingTimeline = {
    id: number;
    judul: string;
    is_tba: boolean;
    aktif: boolean;
};

type BobotLanding = {
    kategori_lomba_id: number;
    nama_kategori: string;
    icon_url?: string | null;
    kriteria: Array<{ nama: string; poin: number; deskripsi?: string }>;
};

type TemplateProposal = {
    nama: string | null;
    url: string;
};

type LandingEdition = {
    id: number;
    nama: string;
    tahun: number;
};

const props = defineProps<{
    edisiValue: string;
    daftarEdisi: LandingEdition[];
    activeTab: "kategori" | "bobot" | "ketentuan" | "template" | "timeline";
    kategori: LandingKategori[];
    bobotLanding: BobotLanding[];
    ketentuanLanding: string[];
    templateProposal: TemplateProposal | null;
    timeline: LandingTimeline[];
    tabClass?: (
        value: "kategori" | "bobot" | "ketentuan" | "template" | "timeline",
    ) => string;
}>();

const emit = defineEmits<{
    (e: "update:edisiValue", value: string): void;
    (
        e: "update:activeTab",
        value: "kategori" | "bobot" | "ketentuan" | "template" | "timeline",
    ): void;
}>();

const localEdisi = computed({
    get: () => props.edisiValue,
    set: (value: string) => emit("update:edisiValue", value),
});

const localTab = computed({
    get: () => props.activeTab,
    set: (
        value: "kategori" | "bobot" | "ketentuan" | "template" | "timeline",
    ) => emit("update:activeTab", value),
});
</script>

<template>
    <Card class="rounded-2xl border-slate-200">
        <CardHeader class="space-y-1">
            <CardTitle>Pengaturan Edisi Data</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 text-sm text-slate-600">
            <p class="text-xs text-slate-500">
                Pilih edisi untuk data kategori, timeline, dan bobot di landing.
            </p>
            <Select v-model="localEdisi">
                <SelectTrigger class="w-full bg-white">
                    <SelectValue placeholder="Gunakan edisi aktif" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="auto">Gunakan edisi aktif</SelectItem>
                    <SelectItem
                        v-for="edisi in props.daftarEdisi"
                        :key="edisi.id"
                        :value="String(edisi.id)"
                    >
                        {{ edisi.nama }} ({{ edisi.tahun }})
                    </SelectItem>
                </SelectContent>
            </Select>
        </CardContent>
    </Card>

    <div class="space-y-3 mt-3">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="props.tabClass?.('kategori') || ''"
                @click="localTab = 'kategori'"
            >
                Kategori
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="props.tabClass?.('bobot') || ''"
                @click="localTab = 'bobot'"
            >
                Bobot Penilaian
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="props.tabClass?.('ketentuan') || ''"
                @click="localTab = 'ketentuan'"
            >
                Ketentuan Umum
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="props.tabClass?.('template') || ''"
                @click="localTab = 'template'"
            >
                Template Proposal
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="props.tabClass?.('timeline') || ''"
                @click="localTab = 'timeline'"
            >
                Timeline
            </button>
        </div>

        <Card class="rounded-2xl border-slate-200">
            <CardContent class="space-y-2 text-sm text-slate-600">
                <div v-if="localTab === 'kategori'" class="space-y-2">
                    <div v-if="props.kategori.length" class="space-y-2">
                        <div
                            v-for="item in props.kategori"
                            :key="item.id"
                            class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2"
                        >
                            <div>
                                <p class="font-semibold text-slate-900">
                                    {{ item.nama }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ item.deskripsi || "-" }}
                                </p>
                            </div>
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    item.aktif
                                        ? 'text-emerald-600'
                                        : 'text-slate-400'
                                "
                            >
                                {{ item.aktif ? "Aktif" : "Nonaktif" }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-500">
                        Belum ada kategori aktif.
                    </p>
                </div>

                <div v-else-if="localTab === 'bobot'" class="space-y-3">
                    <div v-if="props.bobotLanding.length" class="space-y-3">
                        <div
                            v-for="item in props.bobotLanding"
                            :key="item.kategori_lomba_id"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                                >
                                    <img
                                        v-if="item.icon_url"
                                        :src="item.icon_url"
                                        alt="Icon kategori"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="text-xs text-slate-400"
                                        >-</span
                                    >
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ item.nama_kategori }}
                                    </p>
                                    <p class="text-[11px] text-slate-500">
                                        Icon kategori
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="item.kriteria.length"
                                class="mt-2 space-y-1"
                            >
                                <div
                                    v-for="(kriteria, idx) in item.kriteria"
                                    :key="`${item.kategori_lomba_id}-${idx}`"
                                    class="rounded-md border border-slate-100 px-2 py-2 text-xs text-slate-600"
                                >
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div>
                                            <span>{{ kriteria.nama }}</span>
                                            <p
                                                v-if="kriteria.deskripsi"
                                                class="mt-1 text-[11px] text-slate-500"
                                            >
                                                {{ kriteria.deskripsi }}
                                            </p>
                                        </div>
                                        <span
                                            class="font-semibold text-slate-900"
                                        >
                                            {{ kriteria.poin }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="mt-2 text-xs text-slate-500">
                                Bobot penilaian belum diisi.
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-500">
                        Belum ada bobot penilaian.
                    </p>
                </div>

                <div v-else-if="localTab === 'ketentuan'" class="space-y-2">
                    <ul
                        v-if="props.ketentuanLanding.length"
                        class="list-disc space-y-1 pl-5 text-sm text-slate-600"
                    >
                        <li
                            v-for="(item, idx) in props.ketentuanLanding"
                            :key="idx"
                        >
                            {{ item }}
                        </li>
                    </ul>
                    <p v-else class="text-xs text-slate-500">
                        Ketentuan umum belum diisi.
                    </p>
                </div>

                <div v-else-if="localTab === 'template'" class="space-y-2">
                    <div v-if="props.templateProposal?.url" class="space-y-1">
                        <p class="text-sm font-semibold text-slate-900">
                            {{
                                props.templateProposal.nama ||
                                "Template Proposal"
                            }}
                        </p>
                        <a
                            :href="props.templateProposal.url"
                            class="text-xs font-semibold text-blue-600 underline underline-offset-2"
                            target="_blank"
                            rel="noreferrer"
                        >
                            Buka Template
                        </a>
                    </div>
                    <p v-else class="text-xs text-slate-500">
                        Template proposal belum diisi.
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <div v-if="props.timeline.length" class="space-y-2">
                        <div
                            v-for="item in props.timeline"
                            :key="item.id"
                            class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2"
                        >
                            <div>
                                <p class="font-semibold text-slate-900">
                                    {{ item.judul }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ item.is_tba ? "TBA" : "Sesuai tanggal" }}
                                </p>
                            </div>
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    item.aktif
                                        ? 'text-emerald-600'
                                        : 'text-slate-400'
                                "
                            >
                                {{ item.aktif ? "Aktif" : "Nonaktif" }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-500">
                        Belum ada timeline aktif.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
