<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { CheckCircle2, Circle, Plus, Trash2 } from "lucide-vue-next";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    aktif: boolean;
};

const page = usePage<{
    edisiAktif: Edisi;
    isEditable: boolean;
    basePath: string;
    panduan: {
        ketentuan: string[];
        panduan_tahap_2: string | null;
    };
}>();

const edisiAktif = computed(() => page.props.edisiAktif);
const isEditable = computed(() => page.props.isEditable === true);
const basePath = computed(() => page.props.basePath || "/admin");

const buildKetentuan = () =>
    page.props.panduan?.ketentuan?.length > 0
        ? page.props.panduan.ketentuan.map((item) => ({
              teks: item,
              dicentang: true,
          }))
        : [{ teks: "", dicentang: false }];

const form = useForm({
    edisi_id: edisiAktif.value.id,
    ketentuan: buildKetentuan(),
    panduan_tahap_2: page.props.panduan?.panduan_tahap_2 ?? "",
});

watch(
    () => page.props.panduan,
    () => {
        form.defaults({
            edisi_id: edisiAktif.value.id,
            ketentuan: buildKetentuan(),
            panduan_tahap_2: page.props.panduan?.panduan_tahap_2 ?? "",
        });
        form.reset();
        form.clearErrors();
    }
);

const isKetentuanInvalid = computed(() =>
    form.ketentuan.some(
        (item) => item.teks.trim().length > 0 && item.dicentang !== true
    )
);

const tambahKetentuan = () => {
    form.ketentuan.push({ teks: "", dicentang: false });
};

const hapusKetentuan = (index: number) => {
    if (form.ketentuan.length <= 1) return;
    form.ketentuan.splice(index, 1);
};

const submit = () => {
    if (!isEditable.value) return;
    if (isKetentuanInvalid.value) {
        toast.error("Centang tiap poin ketentuan yang diisi");
        return;
    }

    form.transform((data) => ({
        edisi_id: edisiAktif.value.id,
        ketentuan: data.ketentuan
            .map((item) => item.teks.trim())
            .filter(Boolean),
        panduan_tahap_2: data.panduan_tahap_2?.trim() || null,
    })).put(`${basePath.value}/guideline/ketentuan`, {
        preserveScroll: true,
        onSuccess: () => toast.success("Ketentuan umum tersimpan"),
        onError: () => toast.error("Gagal menyimpan ketentuan"),
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
                    Ketentuan Umum
                </h2>
            </div>

            <div v-if="!isEditable" class="text-xs text-amber-700">
                Edisi ini tidak aktif. Data hanya bisa dibaca.
            </div>

            <div class="space-y-3">
                <div
                    v-for="(item, idx) in form.ketentuan"
                    :key="`ketentuan-${idx}`"
                    class="flex items-center gap-2"
                >
                    <Input
                        v-model="form.ketentuan[idx].teks"
                        :disabled="!isEditable"
                        :placeholder="`Ketentuan ${idx + 1}`"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="!isEditable"
                        @click="
                            form.ketentuan[idx].dicentang =
                                !form.ketentuan[idx].dicentang
                        "
                    >
                        <CheckCircle2
                            v-if="form.ketentuan[idx].dicentang"
                            class="w-5 h-5 text-emerald-600"
                        />
                        <Circle v-else class="w-5 h-5 text-slate-400" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        :disabled="!isEditable || form.ketentuan.length <= 1"
                        @click="hapusKetentuan(idx)"
                    >
                        <Trash2 class="w-4 h-4" />
                    </Button>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="!isEditable"
                    @click="tambahKetentuan"
                >
                    <Plus class="w-4 h-4" />
                    Tambah Ketentuan
                </Button>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium"
                    >Panduan Penjurian Tahap 2</label
                >
                <Input
                    v-model="form.panduan_tahap_2"
                    :disabled="!isEditable"
                    placeholder="Masukkan tautan/teks panduan tahap 2"
                />
            </div>

            <div class="flex justify-end">
                <Button
                    :disabled="form.processing || !isEditable || isKetentuanInvalid"
                    @click="submit"
                >
                    {{ form.processing ? "Menyimpan..." : "Simpan Ketentuan" }}
                </Button>
            </div>
        </div>
    </div>
</template>
