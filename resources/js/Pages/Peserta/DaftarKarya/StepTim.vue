<script setup lang="ts">
import { computed, reactive } from "vue";
import { Plus, Trash2 } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { FormDaftarKarya } from "./types";

const props = defineProps<{
    form: FormDaftarKarya;
    readOnly?: boolean;
    errors?: Record<string, string | undefined>;
}>();

const MAKS_ANGGOTA = 6;

const isFilled = (value: unknown) =>
    String(value ?? "")
        .trim()
        .length > 0;

const pembimbingTerisi = computed(
    () =>
        isFilled(props.form.dosenPembimbing.nik) &&
        isFilled(props.form.dosenPembimbing.nama) &&
        isFilled(props.form.dosenPembimbing.email) &&
        isFilled(props.form.dosenPembimbing.bidang),
);

const anggotaTerisi = computed(() =>
    props.form.anggotaTim.every(
        (anggota) =>
            isFilled(anggota.nim) &&
            isFilled(anggota.nama) &&
            isFilled(anggota.email) &&
            isFilled(anggota.peran),
        ),
);
const isPembimbingValid = computed(
    () =>
        /^[0-9.]+$/.test(props.form.dosenPembimbing.nik.trim()) &&
        isFilled(props.form.dosenPembimbing.nik) &&
        isFilled(props.form.dosenPembimbing.nama) &&
        /\S+@\S+\.\S+/.test(props.form.dosenPembimbing.email.trim()) &&
        isFilled(props.form.dosenPembimbing.bidang),
);
const isAnggotaValid = computed(() =>
    props.form.anggotaTim.length > 0 &&
    props.form.anggotaTim.every(
        (anggota) =>
            /^[0-9.]+$/.test(anggota.nim.trim()) &&
            isFilled(anggota.nim) &&
            isFilled(anggota.nama) &&
            /\S+@\S+\.\S+/.test(anggota.email.trim()) &&
            isFilled(anggota.peran),
    ),
);

const touchedAnggota = reactive(
    props.form.anggotaTim.map(() => ({
        nim: false,
        nama: false,
        email: false,
        peran: false,
    })),
);

const touchedPembimbing = reactive({
    nik: false,
    nama: false,
    email: false,
    bidang: false,
});

const pembimbingErrors = computed(() => ({
    nik: touchedPembimbing.nik
        ? props.errors?.["dosenPembimbing.nik"] ||
          (!isFilled(props.form.dosenPembimbing.nik)
              ? "NIK wajib diisi."
              : "") ||
          (!/^[0-9.]+$/.test(props.form.dosenPembimbing.nik.trim())
              ? "NIK hanya boleh angka dan titik."
              : "")
        : "",
    nama: touchedPembimbing.nama
        ? props.errors?.["dosenPembimbing.nama"] ||
          (!isFilled(props.form.dosenPembimbing.nama) ? "Nama wajib diisi." : "")
        : "",
    email: touchedPembimbing.email
        ? props.errors?.["dosenPembimbing.email"] ||
          (!isFilled(props.form.dosenPembimbing.email)
              ? "Email wajib diisi."
              : "") ||
          (!/\S+@\S+\.\S+/.test(props.form.dosenPembimbing.email.trim())
              ? "Email harus valid."
              : "")
        : "",
    bidang: touchedPembimbing.bidang
        ? props.errors?.["dosenPembimbing.bidang"] ||
          (!isFilled(props.form.dosenPembimbing.bidang)
              ? "Bidang wajib diisi."
              : "")
        : "",
}));

const anggotaErrors = computed(() =>
    props.form.anggotaTim.map((_, index) => ({
        nim: touchedAnggota[index]?.nim
            ? props.errors?.[`anggotaTim.${index}.nim`] ||
              (!isFilled(props.form.anggotaTim[index].nim)
                  ? "NIM wajib diisi."
                  : "") ||
              (!/^[0-9.]+$/.test(props.form.anggotaTim[index].nim.trim())
                  ? "NIM hanya boleh angka dan titik."
                  : "")
            : "",
        nama: touchedAnggota[index]?.nama
            ? props.errors?.[`anggotaTim.${index}.nama`] ||
              (!isFilled(props.form.anggotaTim[index].nama)
                  ? "Nama wajib diisi."
                  : "")
            : "",
        email: touchedAnggota[index]?.email
            ? props.errors?.[`anggotaTim.${index}.email`] ||
              (!isFilled(props.form.anggotaTim[index].email)
                  ? "Email wajib diisi."
                  : "") ||
              (!/\S+@\S+\.\S+/.test(props.form.anggotaTim[index].email.trim())
                  ? "Email harus valid."
                  : "")
            : "",
        peran: touchedAnggota[index]?.peran
            ? props.errors?.[`anggotaTim.${index}.peran`] ||
              (!isFilled(props.form.anggotaTim[index].peran)
                  ? "Peran wajib dipilih."
                  : "")
            : "",
    })),
);

const tambahAnggota = () => {
    if (props.readOnly) return;
    if (props.form.anggotaTim.length >= MAKS_ANGGOTA) return;
    props.form.anggotaTim.push({
        nim: "",
        nama: "",
        email: "",
        peran: "anggota",
    });
};

const hapusAnggota = (index: number) => {
    if (props.readOnly) return;
    if (props.form.anggotaTim.length <= 1) return;
    props.form.anggotaTim.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-2">
            <h3 class="text-lg font-semibold text-slate-900">Kelola Tim Karya</h3>
            <p class="text-sm text-slate-500">
                Isi data dosen pembimbing dan anggota tim secara lengkap.
            </p>
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-800">
                Dosen Pembimbing
                <span :class="isPembimbingValid ? 'text-slate-800' : 'text-rose-500'">*</span>
            </label>

            <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3">
                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                    <div class="md:col-span-2">
                        <Input
                            v-model="form.dosenPembimbing.nik"
                            :class="['bg-white', pembimbingErrors.nik ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="NIK"
                            inputmode="numeric"
                            type="text"
                            pattern="[0-9.]*"
                            @blur="touchedPembimbing.nik = true"
                            @input="
                                touchedPembimbing.nik = true;
                                form.dosenPembimbing.nik = form.dosenPembimbing.nik.replace(/[^0-9.]/g, '')
                            "
                        />
                        <p v-if="pembimbingErrors.nik" class="mt-1 text-xs text-rose-600">
                            {{ pembimbingErrors.nik }}
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <Input
                            v-model="form.dosenPembimbing.nama"
                            :class="['bg-white', pembimbingErrors.nama ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="Nama"
                            @blur="touchedPembimbing.nama = true"
                            @input="touchedPembimbing.nama = true"
                        />
                        <p v-if="pembimbingErrors.nama" class="mt-1 text-xs text-rose-600">
                            {{ pembimbingErrors.nama }}
                        </p>
                    </div>
                    <div class="md:col-span-4">
                        <Input
                            v-model="form.dosenPembimbing.email"
                            :class="['bg-white', pembimbingErrors.email ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="Email Amikom"
                            type="email"
                            @blur="touchedPembimbing.email = true"
                            @input="touchedPembimbing.email = true"
                        />
                        <p v-if="pembimbingErrors.email" class="mt-1 text-xs text-rose-600">
                            {{ pembimbingErrors.email }}
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <Input
                            v-model="form.dosenPembimbing.bidang"
                            :class="['bg-white', pembimbingErrors.bidang ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="Bidang pembimbingan"
                            @blur="touchedPembimbing.bidang = true"
                            @input="touchedPembimbing.bidang = true"
                        />
                        <p v-if="pembimbingErrors.bidang" class="mt-1 text-xs text-rose-600">
                            {{ pembimbingErrors.bidang }}
                        </p>
                    </div>
                </div>
            </div>

            <p class="text-xs text-slate-500">
                Silahkan mencari dosen pembimbing atau dosen matakuliah pengampu.
            </p>
        </div>

        <div class="h-px bg-slate-200" />

        <div class="space-y-3">
            <label class="text-sm font-medium text-slate-800">
                Anggota Tim
                <span :class="isAnggotaValid ? 'text-slate-800' : 'text-rose-500'">*</span>
            </label>

            <div
                v-for="(anggota, index) in form.anggotaTim"
                :key="`anggota-${index}`"
                class="rounded-xl border border-slate-200 bg-white p-3"
            >
                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                    <div class="md:col-span-2">
                        <Input
                            v-model="anggota.nim"
                            :class="['bg-white', anggotaErrors[index]?.nim ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="NIM"
                            inputmode="numeric"
                            type="text"
                            pattern="[0-9.]*"
                            @blur="touchedAnggota[index].nim = true"
                            @input="
                                touchedAnggota[index].nim = true;
                                anggota.nim = anggota.nim.replace(/[^0-9.]/g, '')
                            "
                        />
                        <p v-if="anggotaErrors[index]?.nim" class="mt-1 text-xs text-rose-600">
                            {{ anggotaErrors[index]?.nim }}
                        </p>
                    </div>
                    <div class="md:col-span-3">
                        <Input
                            v-model="anggota.nama"
                            :class="['bg-white', anggotaErrors[index]?.nama ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="Nama"
                            @blur="touchedAnggota[index].nama = true"
                            @input="touchedAnggota[index].nama = true"
                        />
                        <p v-if="anggotaErrors[index]?.nama" class="mt-1 text-xs text-rose-600">
                            {{ anggotaErrors[index]?.nama }}
                        </p>
                    </div>
                    <div class="md:col-span-4">
                        <Input
                            v-model="anggota.email"
                            :class="['bg-white', anggotaErrors[index]?.email ? 'border-rose-500 ring-rose-500' : '']"
                            :disabled="readOnly"
                            placeholder="Email Amikom"
                            type="email"
                            @blur="touchedAnggota[index].email = true"
                            @input="touchedAnggota[index].email = true"
                        />
                        <p v-if="anggotaErrors[index]?.email" class="mt-1 text-xs text-rose-600">
                            {{ anggotaErrors[index]?.email }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <Select
                            :model-value="anggota.peran"
                            :disabled="readOnly"
                            @update:model-value="
                                (val) =>
                                    (anggota.peran =
                                        (val as 'ketua' | 'anggota') ?? 'anggota')
                            "
                        >
                            <SelectTrigger
                                :class="[
                                    'w-full bg-white',
                                    anggotaErrors[index]?.peran ? 'border-rose-500 ring-rose-500' : '',
                                ]"
                                @blur="touchedAnggota[index].peran = true"
                            >
                                <SelectValue placeholder="Peran" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ketua">Ketua</SelectItem>
                                <SelectItem value="anggota">Anggota</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="anggotaErrors[index]?.peran" class="mt-1 text-xs text-rose-600">
                            {{ anggotaErrors[index]?.peran }}
                        </p>
                    </div>
                    <div class="md:col-span-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="w-full text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                            :disabled="readOnly || form.anggotaTim.length <= 1"
                            @click="hapusAnggota(index)"
                        >
                            <Trash2 class="w-4 h-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                class="w-full sm:w-fit"
                :disabled="readOnly || form.anggotaTim.length >= MAKS_ANGGOTA"
                @click="tambahAnggota"
            >
                <Plus class="w-4 h-4" />
                Tambah Anggota
            </Button>

            <p class="text-xs text-slate-500">
                Wajib ada tepat satu anggota dengan peran Ketua. Maksimal 6 anggota per tim.
            </p>
        </div>
    </div>
</template>
