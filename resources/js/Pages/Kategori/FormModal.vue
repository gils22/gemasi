<script setup lang="ts">
import { computed, ref, watch } from "vue";
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
import { ImagePlus, Trash2 } from "lucide-vue-next";
import { Spinner } from "@/components/ui/spinner";

type Kategori = {
    id: number;
    nama: string;
    deskripsi: string | null;
    aktif: boolean;
    icon_url: string | null;
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
    icon: null as File | null,
    aktif: true,
});

const iconPreview = ref<string | null>(null);
const iconInputRef = ref<HTMLInputElement | null>(null);

const isFormInvalid = computed(() => form.nama.trim().length === 0);

const clearIcon = () => {
    if (iconPreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(iconPreview.value);
    }
    iconPreview.value = props.kategori?.icon_url ?? null;
    form.icon = null;
    if (iconInputRef.value) {
        iconInputRef.value.value = "";
    }
};

watch(
    () => props.kategori,
    (val) => {
        clearIcon();
        if (val) {
            form.nama = val.nama ?? "";
            form.deskripsi = val.deskripsi ?? "";
            form.aktif = !!val.aktif;
            iconPreview.value = val.icon_url ?? null;
            return;
        }
        form.reset();
    },
    { immediate: true },
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.clearErrors();
            if (!props.kategori) form.reset();
        }
    },
);

const handleIconChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (iconPreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(iconPreview.value);
    }
    form.icon = file;
    iconPreview.value = file
        ? URL.createObjectURL(file)
        : (props.kategori?.icon_url ?? null);
    input.value = "";
};

const submit = () => {
    if (isFormInvalid.value) {
        toast.error("Nama kategori wajib diisi");
        return;
    }

    const payload = {
        ...form.data(),
    };

    if (props.kategori) {
        if (!props.canEdit) return;

        form.transform(() => ({
            ...payload,
            _method: "put",
        })).post(`${props.basePath}/kategori/${props.kategori.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.success("Kategori berhasil diperbarui");
                emit("update:open", false);
            },
            onError: () => toast.error("Gagal memperbarui kategori"),
        });
        return;
    }

    if (!props.canCreate) return;

    form.transform(() => payload).post(`${props.basePath}/kategori`, {
        preserveScroll: true,
        forceFormData: true,
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
        <DialogContent class="sm:max-w-xl">
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
                    <label class="text-sm font-medium">Icon Kategori</label>
                    <div
                        class="rounded-md border border-dashed border-slate-200 bg-slate-50 p-4"
                    >
                        <input
                            ref="iconInputRef"
                            type="file"
                            accept=".png,.jpg,.jpeg,.webp,.svg"
                            class="hidden"
                            @change="handleIconChange"
                        />

                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-white"
                            >
                                <img
                                    v-if="iconPreview"
                                    :src="iconPreview"
                                    alt="Preview icon"
                                    class="h-full w-full object-cover"
                                />
                                <ImagePlus
                                    v-else
                                    class="h-5 w-5 text-slate-400"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-700">
                                    Upload gambar icon kategori
                                </p>
                                <p class="text-xs text-slate-500">
                                    Rekomendasi 512x512 px, rasio 1:1, format
                                    PNG/WebP/SVG.
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="iconInputRef?.click()"
                                >
                                    Pilih File
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    :disabled="!iconPreview"
                                    @click="clearIcon"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <p
                            v-if="form.icon?.name"
                            class="mt-3 text-xs text-slate-600"
                        >
                            File baru: {{ form.icon.name }}
                        </p>
                    </div>
                    <p v-if="form.errors.icon" class="text-xs text-destructive">
                        {{ form.errors.icon }}
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
                    <p
                        v-if="form.errors.deskripsi"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.deskripsi }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Status</label>
                    <div
                        class="flex h-10 items-center justify-between rounded-lg border px-3 py-2"
                    >
                        <span class="text-sm">Kategori aktif</span>
                        <Switch
                            :model-value="form.aktif"
                            @update:model-value="
                                (val) => (form.aktif = val === true)
                            "
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
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
                        <Spinner v-if="form.processing" class="h-4 w-4" />
                        <span v-else>Simpan</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
