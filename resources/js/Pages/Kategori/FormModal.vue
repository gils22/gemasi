<script setup lang="ts">
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";

type Kategori = {
    id: number;
    nama: string;
    deskripsi: string | null;
    urutan: number;
    aktif: boolean;
};

const props = defineProps<{
    open: boolean;
    kategori?: Kategori | null;
    basePath: string;
    canCreate: boolean;
    canEdit: boolean;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const form = useForm({
    nama: "",
    deskripsi: "",
    urutan: 0,
    aktif: true,
});

const isFormInvalid = computed(() => form.nama.trim().length === 0);

watch(
    () => props.kategori,
    (val) => {
        if (val) {
            form.nama = val.nama ?? "";
            form.deskripsi = val.deskripsi ?? "";
            form.urutan = val.urutan ?? 0;
            form.aktif = !!val.aktif;
            return;
        }
        form.reset();
    },
    { immediate: true }
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.clearErrors();
            if (!props.kategori) form.reset();
        }
    }
);

const submit = () => {
    if (isFormInvalid.value) {
        toast.error("Nama kategori wajib diisi");
        return;
    }

    if (props.kategori) {
        if (!props.canEdit) return;

        form.put(`${props.basePath}/kategori/${props.kategori.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Kategori berhasil diperbarui");
                emit("update:open", false);
            },
            onError: () => toast.error("Gagal memperbarui kategori"),
        });
        return;
    }

    if (!props.canCreate) return;

    form.post(`${props.basePath}/kategori`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Kategori berhasil ditambahkan");
            emit("update:open", false);
        },
        onError: () => toast.error("Gagal menambahkan kategori"),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-lg">
            <form @submit.prevent="submit" class="space-y-4">
                <DialogHeader>
                    <DialogTitle>
                        {{ kategori ? "Edit Kategori" : "Tambah Kategori" }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-2">
                    <label class="text-sm font-medium">
                        Nama Kategori <span class="text-destructive">*</span>
                    </label>
                    <Input v-model="form.nama" placeholder="Nama kategori" />
                    <p v-if="form.errors.nama" class="text-xs text-destructive">
                        {{ form.errors.nama }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Deskripsi</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Deskripsi kategori (opsional)"
                    />
                    <p v-if="form.errors.deskripsi" class="text-xs text-destructive">
                        {{ form.errors.deskripsi }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Urutan</label>
                        <Input
                            v-model.number="form.urutan"
                            type="number"
                            min="0"
                            placeholder="Urutan tampil"
                        />
                        <p v-if="form.errors.urutan" class="text-xs text-destructive">
                            {{ form.errors.urutan }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Status</label>
                        <div class="flex items-center justify-between rounded-lg border px-3 py-2 h-10">
                            <span class="text-sm">Kategori aktif</span>
                            <Switch
                                :model-value="form.aktif"
                                @update:model-value="(val) => (form.aktif = val === true)"
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            isFormInvalid ||
                            (!kategori && !canCreate) ||
                            (!!kategori && !canEdit)
                        "
                    >
                        {{ form.processing ? "Menyimpan..." : "Simpan" }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

