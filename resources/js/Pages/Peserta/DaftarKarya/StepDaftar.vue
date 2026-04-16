<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import type { FormDaftarKarya } from "./types";

defineProps<{
    form: FormDaftarKarya;
    daftarKategori: string[];
    readOnly?: boolean;
}>();
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">Kategori Karya *</label>
            <Select
                :model-value="form.kategori"
                :disabled="readOnly"
                @update:model-value="(val) => (form.kategori = String(val ?? ''))"
            >
                <SelectTrigger class="w-full bg-white">
                    <SelectValue placeholder="Pilih kategori karya" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="kategori in daftarKategori"
                        :key="kategori"
                        :value="kategori"
                    >
                        {{ kategori }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">Nama Produk/Karya *</label>
            <Input
                v-model="form.namaKarya"
                class="bg-white"
                :disabled="readOnly"
                placeholder="Contoh: Sistem Informasi X"
            />
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">Nomor WA Ketua Tim *</label>
            <Input
                v-model="form.waKetua"
                class="bg-white"
                :disabled="readOnly"
                placeholder="Contoh: 0899xxxxxxxx"
            />
        </div>
    </div>
</template>


