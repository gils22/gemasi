<script setup lang="ts">
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
}>();

const MAKS_ANGGOTA = 6;

const tambahAnggota = () => {
    if (props.form.anggotaTim.length >= MAKS_ANGGOTA) return;
    props.form.anggotaTim.push({
        nim: "",
        nama: "",
        email: "",
        peran: "anggota",
    });
};

const hapusAnggota = (index: number) => {
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
                Dosen Pembimbing *
            </label>

            <div class="rounded-xl border border-slate-200 bg-slate-50/40 p-3">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div class="md:col-span-2">
                        <Input
                            v-model="form.dosenPembimbing.nik"
                            class="bg-white"
                            placeholder="NIK"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <Input
                            v-model="form.dosenPembimbing.nama"
                            class="bg-white"
                            placeholder="Nama"
                        />
                    </div>
                    <div class="md:col-span-4">
                        <Input
                            v-model="form.dosenPembimbing.email"
                            class="bg-white"
                            placeholder="Email Amikom"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <Input
                            v-model="form.dosenPembimbing.bidang"
                            class="bg-white"
                            placeholder="Bidang pembimbingan"
                        />
                    </div>
                </div>
            </div>

            <p class="text-xs text-slate-500">
                Silahkan mencari dosen pembimbing atau dosen matakuliah
                pengampu.
            </p>
        </div>

        <div class="h-px bg-slate-200" />

        <div class="space-y-3">
            <label class="text-sm font-medium text-slate-800">Anggota Tim *</label>

            <div
                v-for="(anggota, index) in form.anggotaTim"
                :key="`anggota-${index}`"
                class="rounded-xl border border-slate-200 bg-white p-3"
            >
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div class="md:col-span-2">
                        <Input
                            v-model="anggota.nim"
                            class="bg-white"
                            placeholder="NIM"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <Input
                            v-model="anggota.nama"
                            class="bg-white"
                            placeholder="Nama"
                        />
                    </div>
                    <div class="md:col-span-4">
                        <Input
                            v-model="anggota.email"
                            class="bg-white"
                            placeholder="Email Amikom"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <Select
                            :model-value="anggota.peran"
                            @update:model-value="
                                (val) =>
                                    (anggota.peran =
                                        (val as 'ketua' | 'anggota') ?? 'anggota')
                            "
                        >
                            <SelectTrigger class="w-full bg-white">
                                <SelectValue placeholder="Peran" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ketua">Ketua</SelectItem>
                                <SelectItem value="anggota">Anggota</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="md:col-span-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="w-full text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                            :disabled="form.anggotaTim.length <= 1"
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
                :disabled="form.anggotaTim.length >= MAKS_ANGGOTA"
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

