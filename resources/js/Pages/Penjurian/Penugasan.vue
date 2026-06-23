<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { PageProps } from "@/types/inertia";

type Kategori = {
    id: number;
    nama: string;
};

type JuriOption = {
    id: number;
    name: string;
    email: string;
};

type AssignMap = Record<number, { tahap_1: number[]; tahap_2: number[] }>;

const page = usePage<
    PageProps & {
        gemasiAktifLabel: string;
        kategori: Kategori[];
        juriOptions: JuriOption[];
        penugasan: AssignMap;
    }
>();

const kategori = computed(() => page.props.kategori ?? []);
const juriOptions = computed(() => page.props.juriOptions ?? []);

const form = ref<
    Record<
        number,
        {
            tahap1Juri1: string;
            tahap1Juri2: string;
            tahap2Juri1: string;
            tahap2Juri2: string;
        }
    >
>(
    Object.fromEntries(
        kategori.value.map((item) => {
            const assigned = page.props.penugasan?.[item.id] ?? [];
            return [
                item.id,
                {
                    tahap1Juri1: assigned.tahap_1?.[0] ? String(assigned.tahap_1[0]) : "",
                    tahap1Juri2: assigned.tahap_1?.[1] ? String(assigned.tahap_1[1]) : "",
                    tahap2Juri1: assigned.tahap_2?.[0] ? String(assigned.tahap_2[0]) : "",
                    tahap2Juri2: assigned.tahap_2?.[1] ? String(assigned.tahap_2[1]) : "",
                },
            ];
        }),
    ),
);

const isValid = computed(() =>
    kategori.value.every((item) => {
        const row = form.value[item.id];
        const tahap1Valid =
            row.tahap1Juri1 !== "" &&
            row.tahap1Juri2 !== "" &&
            row.tahap1Juri1 !== row.tahap1Juri2;
        const tahap2Valid =
            row.tahap2Juri1 !== "" &&
            row.tahap2Juri2 !== "" &&
            row.tahap2Juri1 !== row.tahap2Juri2;
        return tahap1Valid || tahap2Valid;
    }),
);

const simpan = () => {
    if (!isValid.value) {
        toast.error("Setiap kategori minimal harus memiliki penugasan tahap 1 atau tahap 2.");
        return;
    }

    const assignments = kategori.value.map((item) => ({
        kategori_lomba_id: item.id,
        tahap_1:
            form.value[item.id].tahap1Juri1 && form.value[item.id].tahap1Juri2
                ? [Number(form.value[item.id].tahap1Juri1), Number(form.value[item.id].tahap1Juri2)]
                : [],
        tahap_2:
            form.value[item.id].tahap2Juri1 && form.value[item.id].tahap2Juri2
                ? [Number(form.value[item.id].tahap2Juri1), Number(form.value[item.id].tahap2Juri2)]
                : [],
    }));

    router.post(
        "/admin/penjurian/penugasan",
        { assignments },
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Penugasan juri berhasil disimpan."),
            onError: () => toast.error("Gagal menyimpan penugasan juri."),
        },
    );
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Penugasan Juri" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <Card>
            <CardHeader>
                <CardTitle>Penugasan Juri Per Kategori</CardTitle>
                <CardDescription>
                    {{ page.props.gemasiAktifLabel }} - Setiap kategori bisa memiliki penugasan tahap 1 dan/atau tahap 2.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="!kategori.length" class="text-sm text-slate-500">
                    Belum ada kategori aktif untuk edisi ini.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="item in kategori"
                        :key="item.id"
                        class="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_1fr]"
                    >
                        <div class="font-medium text-slate-800 md:col-span-2">
                            {{ item.nama }}
                        </div>

                        <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                            <p class="text-sm font-medium text-slate-700">Tahap 1</p>
                            <div class="grid gap-2 md:grid-cols-2">
                                <Select
                                    :model-value="form[item.id]?.tahap1Juri1 ?? ''"
                                    @update:model-value="(val) => (form[item.id].tahap1Juri1 = String(val ?? ''))"
                                >
                                    <SelectTrigger class="bg-white">
                                        <SelectValue placeholder="Juri 1" />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-72 overflow-auto">
                                        <SelectItem
                                            v-for="juri in juriOptions"
                                            :key="`${item.id}-${juri.id}-t1-1`"
                                            :value="String(juri.id)"
                                        >
                                            {{ juri.name }} - {{ juri.email }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select
                                    :model-value="form[item.id]?.tahap1Juri2 ?? ''"
                                    @update:model-value="(val) => (form[item.id].tahap1Juri2 = String(val ?? ''))"
                                >
                                    <SelectTrigger class="bg-white">
                                        <SelectValue placeholder="Juri 2" />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-72 overflow-auto">
                                        <SelectItem
                                            v-for="juri in juriOptions"
                                            :key="`${item.id}-${juri.id}-t1-2`"
                                            :value="String(juri.id)"
                                        >
                                            {{ juri.name }} - {{ juri.email }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-2 rounded-lg border border-slate-200 p-3">
                            <p class="text-sm font-medium text-slate-700">Tahap 2</p>
                            <div class="grid gap-2 md:grid-cols-2">
                                <Select
                                    :model-value="form[item.id]?.tahap2Juri1 ?? ''"
                                    @update:model-value="(val) => (form[item.id].tahap2Juri1 = String(val ?? ''))"
                                >
                                    <SelectTrigger class="bg-white">
                                        <SelectValue placeholder="Juri 1" />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-72 overflow-auto">
                                        <SelectItem
                                            v-for="juri in juriOptions"
                                            :key="`${item.id}-${juri.id}-t2-1`"
                                            :value="String(juri.id)"
                                        >
                                            {{ juri.name }} - {{ juri.email }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select
                                    :model-value="form[item.id]?.tahap2Juri2 ?? ''"
                                    @update:model-value="(val) => (form[item.id].tahap2Juri2 = String(val ?? ''))"
                                >
                                    <SelectTrigger class="bg-white">
                                        <SelectValue placeholder="Juri 2" />
                                    </SelectTrigger>
                                    <SelectContent class="max-h-72 overflow-auto">
                                        <SelectItem
                                            v-for="juri in juriOptions"
                                            :key="`${item.id}-${juri.id}-t2-2`"
                                            :value="String(juri.id)"
                                        >
                                            {{ juri.name }} - {{ juri.email }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button @click="simpan" :disabled="!isValid">Simpan Penugasan</Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

