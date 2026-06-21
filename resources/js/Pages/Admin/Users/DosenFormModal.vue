<script setup lang="ts">
import { watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import { Switch } from "@/components/ui/switch";

const props = defineProps<{
    open: boolean;
    readonly?: boolean;
    dosen?: {
        id: number;
        nik: string | null;
        nama: string;
        email: string;
        bidang: string | null;
        aktif: boolean;
    } | null;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const form = useForm({
    nik: "",
    nama: "",
    email: "",
    bidang: "",
    aktif: true,
});

watch(
    () => props.dosen,
    (val) => {
        if (val?.id) {
            form.nik = val.nik ?? "";
            form.nama = val.nama ?? "";
            form.email = val.email ?? "";
            form.bidang = val.bidang ?? "";
            form.aktif = Boolean(val.aktif);
        } else {
            form.reset();
            form.aktif = true;
        }
    },
    { immediate: true },
);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.aktif = true;
            form.clearErrors();
        }
    },
);

const submit = () => {
    if (props.readonly) return;

    if (props.dosen?.id) {
        form.put(`/admin/dosen/${props.dosen.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Data dosen berhasil diperbarui.");
                emit("update:open", false);
            },
            onError: () => toast.error("Gagal memperbarui data dosen."),
        });
        return;
    }

    form.post("/admin/dosen", {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Data dosen berhasil ditambahkan.");
            emit("update:open", false);
        },
        onError: () => toast.error("Gagal menambahkan data dosen."),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-xl">
            <form @submit.prevent="submit" novalidate>
                <DialogHeader>
                    <DialogTitle>
                        {{ props.dosen?.id ? "Detail Dosen" : "Tambah Dosen" }}
                    </DialogTitle>
                </DialogHeader>

                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                NIK <span class="text-rose-600">*</span>
                            </label>
                            <Input
                                v-model="form.nik"
                                :disabled="props.readonly"
                                placeholder="NIK dosen"
                            />
                            <p
                                v-if="form.errors.nik"
                                class="text-xs text-rose-600"
                            >
                                {{ form.errors.nik }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Bidang</label>
                            <Input
                                v-model="form.bidang"
                                :disabled="props.readonly"
                                placeholder="(opsional)"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            Nama <span class="text-rose-600">*</span>
                        </label>
                        <Input
                            v-model="form.nama"
                            :disabled="props.readonly"
                            placeholder="Nama dosen"
                        />
                        <p
                            v-if="form.errors.nama"
                            class="text-xs text-rose-600"
                        >
                            {{ form.errors.nama }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            Email <span class="text-rose-600">*</span>
                        </label>
                        <Input
                            v-model="form.email"
                            :disabled="props.readonly"
                            placeholder="email@amikom.ac.id"
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-xs text-rose-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-900">
                                Status
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ form.aktif ? "Aktif" : "Nonaktif" }}
                            </p>
                        </div>
                        <Switch
                            :checked="form.aktif"
                            :disabled="props.readonly"
                            @update:checked="(v) => (form.aktif = v === true)"
                        />
                    </div>
                </div>

                <DialogFooter class="mt-6">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Tutup
                    </Button>
                    <Button v-if="!props.readonly" type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="h-4 w-4" />
                        <span v-else>{{ props.dosen?.id ? "Simpan" : "Tambah" }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
