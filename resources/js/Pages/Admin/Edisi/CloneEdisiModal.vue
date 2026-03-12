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
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
};

const props = defineProps<{
    open: boolean;
    daftarEdisi: Edisi[];
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const defaultSumber = computed(() => props.daftarEdisi[0]?.id ?? null);
const defaultTahun = computed(() => (props.daftarEdisi[0]?.tahun ?? new Date().getFullYear()) + 1);

const form = useForm({
    sumber_id: 0,
    nama: "",
    tahun: new Date().getFullYear() + 1,
});

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.clearErrors();
            return;
        }

        form.sumber_id = defaultSumber.value ?? 0;
        form.tahun = defaultTahun.value;
        form.nama = `GEMASI ${form.tahun}`;
    },
    { immediate: true }
);

const submit = () => {
    form.post("/admin/edisi-lomba/clone", {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Edisi berhasil di-clone (dasar)");
            emit("update:open", false);
        },
        onError: () => toast.error("Gagal clone edisi"),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-lg">
            <form @submit.prevent="submit" class="space-y-4">
                <DialogHeader>
                    <DialogTitle>Clone Edisi (Dasar)</DialogTitle>
                </DialogHeader>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Sumber Edisi</label>
                    <select
                        v-model.number="form.sumber_id"
                        class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option
                            v-for="edisi in daftarEdisi"
                            :key="edisi.id"
                            :value="edisi.id"
                        >
                            {{ edisi.nama }}
                        </option>
                    </select>
                    <p v-if="form.errors.sumber_id" class="text-xs text-destructive">{{ form.errors.sumber_id }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-2">
                        <Input v-model.number="form.tahun" type="number" placeholder="Tahun baru" />
                        <p v-if="form.errors.tahun" class="text-xs text-destructive">{{ form.errors.tahun }}</p>
                    </div>
                    <div class="space-y-2">
                        <Input v-model="form.nama" placeholder="Nama edisi baru" />
                        <p v-if="form.errors.nama" class="text-xs text-destructive">{{ form.errors.nama }}</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Batal</Button>
                    <Button type="submit" :disabled="form.processing || daftarEdisi.length === 0">
                        {{ form.processing ? "Memproses..." : "Clone" }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

