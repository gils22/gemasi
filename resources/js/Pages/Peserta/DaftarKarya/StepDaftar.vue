<script setup lang="ts">
import { computed, reactive } from "vue";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import type { FormDaftarKarya } from "./types";

const props = defineProps<{
    form: FormDaftarKarya;
    daftarKategori: string[];
    readOnly?: boolean;
    errors?: Record<string, string | undefined>;
}>();

const isFilled = (value: unknown) =>
    String(value ?? "")
        .trim()
        .length > 0;

const kategoriTerisi = computed(() => isFilled(props.form.kategori));
const namaTerisi = computed(() => isFilled(props.form.namaKarya));
const waTerisi = computed(() => isFilled(props.form.waKetua));
const waValid = computed(() => /^[0-9+\-\s]*$/.test(props.form.waKetua.trim()));
const isKategoriValid = computed(() => kategoriTerisi.value);
const isNamaValid = computed(() => namaTerisi.value);
const isWaValid = computed(() => waTerisi.value && waValid.value);
const touched = reactive({
    kategori: false,
    namaKarya: false,
    waKetua: false,
});

const kategoriError = computed(() =>
    touched.kategori
        ? props.errors?.kategori ||
          (!kategoriTerisi.value ? "Kategori wajib diisi." : "")
        : "",
);
const namaError = computed(() =>
    touched.namaKarya
        ? props.errors?.namaKarya ||
          (!namaTerisi.value ? "Nama karya wajib diisi." : "")
        : "",
);
const waError = computed(() => {
    if (!touched.waKetua) return "";
    if (props.errors?.waKetua) return props.errors.waKetua;
    if (!waTerisi.value) return "Nomor WA wajib diisi.";
    if (!waValid.value) return "Nomor WA harus angka.";
    return "";
});
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">
                Kategori Karya
                <span :class="isKategoriValid ? 'text-slate-800' : 'text-rose-500'">*</span>
            </label>
            <Select
                :model-value="form.kategori"
                :disabled="readOnly"
                @update:model-value="(val) => (form.kategori = String(val ?? ''))"
            >
                <SelectTrigger
                    :class="[
                        'w-full bg-white',
                        kategoriError ? 'border-rose-500 ring-rose-500' : '',
                    ]"
                    @blur="touched.kategori = true"
                >
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
            <p v-if="kategoriError" class="text-xs text-rose-600">
                {{ kategoriError }}
            </p>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">
                Nama Produk/Karya
                <span :class="isNamaValid ? 'text-slate-800' : 'text-rose-500'">*</span>
            </label>
            <Input
                v-model="form.namaKarya"
                :class="['bg-white', namaError ? 'border-rose-500 ring-rose-500' : '']"
                :disabled="readOnly"
                placeholder="Contoh: Sistem Informasi X"
                @blur="touched.namaKarya = true"
                @input="touched.namaKarya = true"
            />
            <p v-if="namaError" class="text-xs text-rose-600">
                {{ namaError }}
            </p>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">
                Nomor WA Ketua Tim
                <span :class="isWaValid ? 'text-slate-800' : 'text-rose-500'">*</span>
            </label>
            <Input
                v-model="form.waKetua"
                :class="['bg-white', waError ? 'border-rose-500 ring-rose-500' : '']"
                :disabled="readOnly"
                placeholder="Contoh: 0899xxxxxxxx"
                inputmode="numeric"
                type="tel"
                pattern="[0-9+\\-\\s]*"
                @blur="touched.waKetua = true"
                @input="
                    touched.waKetua = true;
                    form.waKetua = form.waKetua.replace(/[^0-9+\-\s]/g, '')
                "
            />
            <p v-if="waError" class="text-xs text-rose-600">
                {{ waError }}
            </p>
        </div>
    </div>
</template>
