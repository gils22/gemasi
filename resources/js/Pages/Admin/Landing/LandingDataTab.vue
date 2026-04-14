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
    kriteria: Array<{ nama: string; poin: number }>;
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
}>();

const emit = defineEmits<{
    (e: "update:edisiValue", value: string): void;
    (e: "update:activeTab", value: "kategori" | "bobot" | "ketentuan" | "template" | "timeline"): void;
}>();

const localEdisi = computed({
    get: () => props.edisiValue,
    set: (value: string) => emit("update:edisiValue", value),
});

const localTab = computed({
    get: () => props.activeTab,
    set: (value: "kategori" | "bobot" | "ketentuan" | "template" | "timeline") =>
        emit("update:activeTab", value),
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Pengaturan Edisi Data</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm text-slate-600">
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

    <div class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="
                    localTab === 'kategori'
                        ? 'border-slate-900 bg-white text-slate-900'
                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                "
                @click="localTab = 'kategori'"
            >
                Kategori
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="
                    localTab === 'bobot'
                        ? 'border-slate-900 bg-white text-slate-900'
                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                "
                @click="localTab = 'bobot'"
            >
                Bobot Penilaian
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="
                    localTab === 'ketentuan'
                        ? 'border-slate-900 bg-white text-slate-900'
                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                "
                @click="localTab = 'ketentuan'"
            >
                Ketentuan Umum
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="
                    localTab === 'template'
                        ? 'border-slate-900 bg-white text-slate-900'
                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                "
                @click="localTab = 'template'"
            >
                Template Proposal
            </button>
            <button
                type="button"
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                :class="
                    localTab === 'timeline'
                        ? 'border-slate-900 bg-white text-slate-900'
                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                "
                @click="localTab = 'timeline'"
            >
                Timeline
            </button>
        </div>

        <Card>
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
                                :class="item.aktif ? 'text-emerald-600' : 'text-slate-400'"
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
                            <p class="font-semibold text-slate-900">
                                {{ item.nama_kategori }}
                            </p>
                            <div v-if="item.kriteria.length" class="mt-2 space-y-1">
                                <div
                                    v-for="(kriteria, idx) in item.kriteria"
                                    :key="`${item.kategori_lomba_id}-${idx}`"
                                    class="flex items-center justify-between text-xs text-slate-600"
                                >
                                    <span>{{ kriteria.nama }}</span>
                                    <span class="font-semibold text-slate-900">
                                        {{ kriteria.poin }}
                                    </span>
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
                        <li v-for="(item, idx) in props.ketentuanLanding" :key="idx">
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
                            {{ props.templateProposal.nama || "Template Proposal" }}
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
                                :class="item.aktif ? 'text-emerald-600' : 'text-slate-400'"
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
