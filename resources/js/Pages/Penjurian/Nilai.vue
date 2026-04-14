<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import { ArrowLeft } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type KriteriaRow = {
    nama: string;
    bobot: number;
    nilai: number;
};

type Karya = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    peserta: {
        name: string | null;
        email: string | null;
    };
};

const page = usePage<
    PageProps & {
        karya: Karya;
        kriteria: KriteriaRow[];
        nilaiTersimpan: number | null;
        catatan: string | null;
        juriOptions?: {
            id: number;
            name: string | null;
            email: string | null;
            status?: string;
            total_nilai?: number | null;
        }[];
        selectedJuriId?: number | null;
        gemasiAktifLabel: string;
    }
>();

const role = computed(() => page.props.auth?.role ?? "juri");
const isPrivileged = computed(() => role.value === "admin");
const routePrefix = computed(() => (isPrivileged.value ? "/admin" : "/juri"));
const juriOptions = computed(() => page.props.juriOptions ?? []);
const selectedJuriId = ref<number | null>(page.props.selectedJuriId ?? null);

const rows = ref<KriteriaRow[]>(
    (page.props.kriteria ?? []).map((item) => ({
        nama: item.nama,
        bobot: Number(item.bobot ?? 0),
        nilai: Number(item.nilai ?? 0),
    })),
);
const catatan = ref<string>(page.props.catatan ?? "");
const submitting = ref(false);

const totalNilai = computed(() =>
    rows.value.reduce(
        (acc, item) =>
            acc + (Number(item.nilai || 0) * Number(item.bobot || 0)) / 100,
        0,
    ),
);

const backToNominasi = () => {
    router.get(`${routePrefix.value}/penjurian/nominasi`);
};

const simpan = () => {
    const invalid = rows.value.some(
        (item) =>
            item.nilai < 0 ||
            item.nilai > 100 ||
            Number.isNaN(Number(item.nilai)),
    );
    if (invalid) {
        toast.error("Nilai tiap kriteria harus di antara 0 sampai 100.");
        return;
    }

    if (isPrivileged.value && !selectedJuriId.value) {
        toast.error("Pilih juri yang akan dinilai.");
        return;
    }

    submitting.value = true;
    router.post(
        `${routePrefix.value}/penjurian/nilai/${page.props.karya.id}`,
        {
            kriteria: rows.value,
            catatan: catatan.value || null,
            juri_id: isPrivileged.value ? selectedJuriId.value : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Nilai berhasil disimpan."),
            onError: () => toast.error("Gagal menyimpan nilai."),
            onFinish: () => (submitting.value = false),
        },
    );
};

const gantiJuri = (id: number | null) => {
    selectedJuriId.value = id;
    router.get(
        `${routePrefix.value}/penjurian/nilai/${page.props.karya.id}`,
        { juri_id: selectedJuriId.value },
        { preserveScroll: true, preserveState: true },
    );
};

watch(
    () => page.props.kriteria,
    (val) => {
        rows.value = (val ?? []).map((item) => ({
            nama: item.nama,
            bobot: Number(item.bobot ?? 0),
            nilai: Number(item.nilai ?? 0),
        }));
        catatan.value = page.props.catatan ?? "";
    },
);

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Input Nilai" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <Card>
            <CardHeader class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="outline" size="sm" @click="backToNominasi">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <span class="text-xs text-slate-500">{{
                        page.props.gemasiAktifLabel
                    }}</span>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle>{{ page.props.karya.nama_karya }}</CardTitle>
                        <p class="text-sm text-slate-500 mt-2">
                            {{ page.props.karya.nama_kategori }} -
                            {{ page.props.karya.peserta.name ?? "-" }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-if="isPrivileged">
                            <span
                                v-if="juriOptions.length === 0"
                                class="text-xs text-amber-600"
                            >
                                Belum ada penugasan juri.
                            </span>
                            <button
                                v-for="juri in juriOptions"
                                v-else
                                :key="juri.id"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition flex items-center gap-2"
                                :class="
                                    selectedJuriId === juri.id
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'
                                "
                                @click="gantiJuri(juri.id)"
                            >
                                {{ juri.name ?? "-" }}
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :class="
                                        juri.status === 'Sudah dinilai'
                                            ? 'bg-emerald-400'
                                            : 'bg-amber-400'
                                    "
                                />
                            </button>
                        </template>
                    </div>
                </div>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="hidden md:block overflow-x-auto rounded-lg border">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead class="bg-slate-50 text-slate-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Kriteria</th>
                                <th class="px-3 py-2 text-left">Bobot (%)</th>
                                <th class="px-3 py-2 text-left">
                                    Nilai (0-100)
                                </th>
                                <th class="px-3 py-2 text-left">
                                    Nilai Terbobot
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, idx) in rows"
                                :key="`${item.nama}-${idx}`"
                                class="border-t"
                            >
                                <td
                                    class="px-3 py-2 font-medium text-slate-800"
                                >
                                    {{ item.nama }}
                                </td>
                                <td class="px-3 py-2 text-slate-700">
                                    {{ item.bobot }}
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model.number="item.nilai"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="h-9 w-28"
                                    />
                                </td>
                                <td class="px-3 py-2 text-slate-700">
                                    {{
                                        (
                                            ((item.nilai || 0) * item.bobot) /
                                            100
                                        ).toFixed(2)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden space-y-3">
                    <div
                        v-for="(item, idx) in rows"
                        :key="`${item.nama}-${idx}`"
                        class="rounded-lg border border-slate-200 bg-white p-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ item.nama }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Bobot: {{ item.bobot }}%
                                </p>
                            </div>
                            <div class="text-right text-xs text-slate-600">
                                Nilai Terbobot
                                <div
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{
                                        (
                                            ((item.nilai || 0) * item.bobot) /
                                            100
                                        ).toFixed(2)
                                    }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="text-xs text-slate-500"
                                >Nilai (0-100)</label
                            >
                            <Input
                                v-model.number="item.nilai"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                class="mt-1 h-10 w-full"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-[220px_1fr]">
                    <div class="rounded-lg border px-3 py-2">
                        <p class="text-xs text-slate-500">Total Nilai</p>
                        <p class="text-lg font-semibold text-slate-900">
                            {{ totalNilai.toFixed(2) }}
                        </p>
                    </div>
                    <div class="rounded-lg border px-3 py-2">
                        <textarea
                            v-model="catatan"
                            rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Catatan untuk karya ini (opsional)"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button
                        :disabled="submitting"
                        class="w-full md:w-auto flex items-center gap-2"
                        @click="simpan"
                    >
                        <Spinner v-if="submitting" class="h-4 w-4" />
                        <span>Simpan Nilai</span>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
