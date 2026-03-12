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

type AssignMap = Record<number, number[]>;

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

const form = ref<Record<number, { juri1: string; juri2: string }>>(
    Object.fromEntries(
        kategori.value.map((item) => {
            const assigned = page.props.penugasan?.[item.id] ?? [];
            return [
                item.id,
                {
                    juri1: assigned[0] ? String(assigned[0]) : "",
                    juri2: assigned[1] ? String(assigned[1]) : "",
                },
            ];
        }),
    ),
);

const isValid = computed(() =>
    kategori.value.every((item) => {
        const row = form.value[item.id];
        return row && row.juri1 !== "" && row.juri2 !== "" && row.juri1 !== row.juri2;
    }),
);

const simpan = () => {
    if (!isValid.value) {
        toast.error("Setiap kategori wajib memiliki 2 juri yang berbeda.");
        return;
    }

    const assignments = kategori.value.map((item) => ({
        kategori_lomba_id: item.id,
        juri_ids: [Number(form.value[item.id].juri1), Number(form.value[item.id].juri2)],
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
                    {{ page.props.gemasiAktifLabel }} - Setiap kategori wajib memiliki 2 juri.
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
                        class="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_220px_220px]"
                    >
                        <div class="font-medium text-slate-800">
                            {{ item.nama }}
                        </div>

                        <Select
                            :model-value="form[item.id]?.juri1 ?? ''"
                            @update:model-value="(val) => (form[item.id].juri1 = String(val ?? ''))"
                        >
                            <SelectTrigger class="bg-white">
                                <SelectValue placeholder="Pilih Juri 1" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="juri in juriOptions"
                                    :key="`${item.id}-${juri.id}-1`"
                                    :value="String(juri.id)"
                                >
                                    {{ juri.name }} - {{ juri.email }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            :model-value="form[item.id]?.juri2 ?? ''"
                            @update:model-value="(val) => (form[item.id].juri2 = String(val ?? ''))"
                        >
                            <SelectTrigger class="bg-white">
                                <SelectValue placeholder="Pilih Juri 2" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="juri in juriOptions"
                                    :key="`${item.id}-${juri.id}-2`"
                                    :value="String(juri.id)"
                                >
                                    {{ juri.name }} - {{ juri.email }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button @click="simpan" :disabled="!isValid">Simpan Penugasan</Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

