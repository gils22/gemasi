<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { Plus, Trash2 } from "lucide-vue-next";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    aktif: boolean;
};

type BobotKategori = {
    kategori_lomba_id: number;
    nama_kategori: string;
    icon_url: string | null;
    kriteria: Array<{
        nama: string;
        deskripsi: string;
        poin: number;
    }>;
};

type BobotFormItem = {
    kategori_lomba_id: number;
    kriteria: Array<{
        nama: string;
        deskripsi: string;
        poin: number | string;
    }>;
};

const page = usePage<{
    edisiAktif: Edisi;
    isEditable: boolean;
    basePath: string;
    bobotKategori: BobotKategori[];
}>();

const edisiAktif = computed(() => page.props.edisiAktif);
const isEditable = computed(() => page.props.isEditable === true);
const basePath = computed(() => page.props.basePath || "/admin");
const daftarKategori = computed(() => page.props.bobotKategori ?? []);

const buildBobot = (items: BobotKategori[]): BobotFormItem[] =>
    items.map((item) => ({
        kategori_lomba_id: item.kategori_lomba_id,
        kriteria:
            item.kriteria?.length > 0
                ? item.kriteria.map((k) => ({
                      nama: k.nama ?? "",
                      deskripsi: k.deskripsi ?? "",
                      poin: Number(k.poin ?? 0),
                  }))
                : [{ nama: "", deskripsi: "", poin: "" }],
    }));

const form = useForm({
    edisi_id: edisiAktif.value.id,
    bobot: buildBobot(daftarKategori.value),
});

const activeKategoriId = ref<number | null>(
    form.bobot[0]?.kategori_lomba_id ?? null,
);

watch(
    () => page.props.bobotKategori,
    () => {
        const nextBobot = buildBobot(daftarKategori.value);
        form.defaults({
            edisi_id: edisiAktif.value.id,
            bobot: nextBobot,
        });
        form.reset();
        form.clearErrors();
        activeKategoriId.value = nextBobot[0]?.kategori_lomba_id ?? null;
    },
);

const activeKategoriIndex = computed(() => {
    const idx = form.bobot.findIndex(
        (item) => item.kategori_lomba_id === activeKategoriId.value,
    );
    return idx >= 0 ? idx : 0;
});

const activeKategori = computed(
    () => form.bobot[activeKategoriIndex.value] ?? null,
);

const totalByKategori = (indexKategori: number) =>
    form.bobot[indexKategori]?.kriteria?.reduce(
        (acc, item) => acc + Number(item.poin || 0),
        0,
    ) ?? 0;

const isTotalValid = (indexKategori: number) =>
    Number(totalByKategori(indexKategori).toFixed(2)) === 100;

const sisaPoin = (indexKategori: number) =>
    Number((100 - totalByKategori(indexKategori)).toFixed(2));

const isBobotInvalid = computed(() => {
    return form.bobot.some((item) => {
        if (!item.kriteria || item.kriteria.length === 0) return true;

        const adaKosong = item.kriteria.some((kriteria) => {
            const poin = Number(kriteria.poin);
            return (
                kriteria.nama.trim().length === 0 ||
                Number.isNaN(poin) ||
                poin < 0 ||
                poin > 100
            );
        });

        if (adaKosong) return true;

        return (
            Number(totalByKategori(form.bobot.indexOf(item)).toFixed(2)) !== 100
        );
    });
});

const tambahKriteria = (indexKategori: number) => {
    form.bobot[indexKategori].kriteria.push({
        nama: "",
        deskripsi: "",
        poin: "",
    });
};

const hapusKriteria = (indexKategori: number, indexKriteria: number) => {
    if (form.bobot[indexKategori].kriteria.length <= 1) return;
    form.bobot[indexKategori].kriteria.splice(indexKriteria, 1);
};

const submit = () => {
    if (!isEditable.value) return;
    if (isBobotInvalid.value) {
        toast.error("Total poin tiap kategori harus tepat 100");
        return;
    }

    form.transform((data) => ({
        edisi_id: edisiAktif.value.id,
        bobot: data.bobot.map((item) => ({
            ...item,
            kriteria: item.kriteria.map((kriteria) => ({
                nama: kriteria.nama.trim(),
                deskripsi: kriteria.deskripsi.trim(),
                poin: Number(kriteria.poin || 0),
            })),
        })),
    })).put(`${basePath.value}/guideline/bobot`, {
        preserveScroll: true,
        onSuccess: () => toast.success("Bobot penilaian tersimpan"),
        onError: () => toast.error("Gagal menyimpan bobot"),
    });
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Guideline" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="bg-white border rounded-xl p-4 shadow-sm space-y-4">
            <div class="space-y-2">
                <h2 class="text-lg font-semibold text-slate-800">
                    Bobot Penilaian Tiap Kategori
                </h2>
            </div>

            <div v-if="!isEditable" class="text-xs text-amber-700">
                Edisi ini tidak aktif. Data hanya bisa dibaca.
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="(item, idxKategori) in form.bobot"
                    :key="`tab-kat-${item.kategori_lomba_id}`"
                    type="button"
                    class="shrink-0 px-3 py-2 rounded-lg border text-sm transition"
                    :class="
                        activeKategoriId === item.kategori_lomba_id
                            ? 'bg-white border-slate-300 text-slate-900'
                            : 'bg-slate-50 border-slate-200 text-slate-600'
                    "
                    @click="activeKategoriId = item.kategori_lomba_id"
                >
                    <span class="font-medium">
                        {{ daftarKategori[idxKategori]?.nama_kategori }}
                    </span>
                    <span
                        class="ml-2 text-xs"
                        :class="
                            isTotalValid(idxKategori)
                                ? 'text-emerald-700'
                                : 'text-rose-700'
                        "
                    >
                        {{ totalByKategori(idxKategori).toFixed(2) }}
                    </span>
                </button>
            </div>

            <div v-if="activeKategori" class="rounded-xl border p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-semibold text-slate-800">
                        {{ daftarKategori[activeKategoriIndex]?.nama_kategori }}
                    </h4>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs px-2 py-1 rounded-full"
                            :class="
                                isTotalValid(activeKategoriIndex)
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-rose-100 text-rose-700'
                            "
                        >
                            Total:
                            {{
                                totalByKategori(activeKategoriIndex).toFixed(2)
                            }}
                        </span>
                        <span
                            v-if="!isTotalValid(activeKategoriIndex)"
                            class="text-xs text-amber-700"
                        >
                            Sisa {{ sisaPoin(activeKategoriIndex).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div
                        class="hidden md:grid md:grid-cols-[minmax(0,1fr)_160px_72px] gap-2 px-1 text-xs font-medium text-slate-500"
                    >
                        <div>Kriteria Penilaian</div>
                        <div>Poin</div>
                        <div>Aksi</div>
                    </div>

                    <div
                        v-for="(
                            kriteria, idxKriteria
                        ) in activeKategori.kriteria"
                        :key="`k-${activeKategori.kategori_lomba_id}-${idxKriteria}`"
                        class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_160px_72px] gap-2 items-start md:items-center"
                    >
                        <div
                            class="grid grid-cols-1 gap-2 min-w-0 md:grid-cols-[1fr_1fr] md:gap-3"
                        >
                            <Input
                                v-model="kriteria.nama"
                                :disabled="!isEditable"
                                :placeholder="`Kriteria ${idxKriteria + 1}`"
                            />
                            <textarea
                                v-model="kriteria.deskripsi"
                                :disabled="!isEditable"
                                rows="1"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 resize-none"
                                placeholder="Deskripsi singkat kriteria"
                            />
                        </div>
                        <div class="md:self-start">
                            <Input
                                v-model.number="kriteria.poin"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :disabled="!isEditable"
                                placeholder="Contoh: 25"
                            />
                        </div>
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <div class="md:self-start">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            :disabled="
                                                !isEditable ||
                                                activeKategori.kriteria
                                                    .length <= 1
                                            "
                                            class="border-slate-200 text-slate-500 hover:border-red-300 hover:bg-red-50 hover:text-red-500 transition-all duration-200"
                                            @click="
                                                hapusKriteria(
                                                    activeKategoriIndex,
                                                    idxKriteria,
                                                )
                                            "
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TooltipTrigger>

                                <TooltipContent side="right">
                                    <p>Hapus kriteria</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="!isEditable"
                    @click="tambahKriteria(activeKategoriIndex)"
                >
                    <Plus class="w-4 h-4" />
                    Tambah Kriteria
                </Button>
            </div>

            <div class="flex justify-end">
                <Button
                    :disabled="form.processing || !isEditable || isBobotInvalid"
                    @click="submit"
                >
                    <Spinner v-if="form.processing" class="h-4 w-4" />
                    <span v-else>Simpan Bobot</span>
                </Button>
            </div>
        </div>
    </div>
</template>
