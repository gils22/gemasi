<script setup lang="ts">
import { watch, ref, computed } from "vue";
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
import { Checkbox } from "@/components/ui/checkbox";
import { Spinner } from "@/components/ui/spinner";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

/* =========================
PROPS
========================= */
const props = defineProps<{
    open: boolean;
    readonly?: boolean;
    kategoriOptions?: { id: number; nama: string }[];
    dosenOptions?: Array<{
        id: number;
        nik?: string | null;
        nama: string;
        email: string;
        bidang?: string | null;
    }>;
    user?: {
        id: number;
        name: string;
        email: string;
        roles?: { name: string }[];
        juri_kategori_tahap_1_ids?: number[];
        juri_kategori_tahap_2_ids?: number[];
    } | null;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

/* =========================
FORM STATE
========================= */
const form = useForm({
    dosen_id: null as number | null,
    name: "",
    email: "",
    roles: ["juri"] as string[],
    juri_kategori_tahap_1_ids: [] as number[],
    juri_kategori_tahap_2_ids: [] as number[],
});

const tahap1Enabled = ref(false);
const tahap2Enabled = ref(false);

/* =========================
WATCH USER (EDIT MODE)
========================= */
watch(
    () => props.user,
    (val) => {
        if (val?.id) {
            form.dosen_id = null;
            form.name = val.name ?? "";
            form.email = val.email ?? "";
            form.roles = val.roles?.map((r) => r.name) ?? [];
            form.juri_kategori_tahap_1_ids =
                (val as any).juri_kategori_tahap_1_ids ?? [];
            form.juri_kategori_tahap_2_ids =
                (val as any).juri_kategori_tahap_2_ids ?? [];
            tahap1Enabled.value =
                form.juri_kategori_tahap_1_ids.length > 0;
            tahap2Enabled.value =
                form.juri_kategori_tahap_2_ids.length > 0;
        } else {
            form.reset();
            tahap1Enabled.value = false;
            tahap2Enabled.value = false;
        }
    },
    { immediate: true }
);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            tahap1Enabled.value = false;
            tahap2Enabled.value = false;
            form.clearErrors();
        }
    }
);

/* =========================
TOGGLE ROLE
========================= */
const toggleRole = (role: string, checked: boolean) => {
    if (checked) {
        if (!form.roles.includes(role)) {
            form.roles.push(role);
        }
    } else {
        form.roles = form.roles.filter((r) => r !== role);
    }
};

const toggleKategori = (
    target: "tahap_1" | "tahap_2",
    kategoriId: number,
    checked: boolean
) => {
    if (target === "tahap_1") {
        if (checked) {
            if (!form.juri_kategori_tahap_1_ids.includes(kategoriId)) {
                form.juri_kategori_tahap_1_ids.push(kategoriId);
            }
        } else {
            form.juri_kategori_tahap_1_ids =
                form.juri_kategori_tahap_1_ids.filter((id) => id !== kategoriId);
        }
        return;
    }

    if (checked) {
        if (!form.juri_kategori_tahap_2_ids.includes(kategoriId)) {
            form.juri_kategori_tahap_2_ids.push(kategoriId);
        }
    } else {
        form.juri_kategori_tahap_2_ids =
            form.juri_kategori_tahap_2_ids.filter((id) => id !== kategoriId);
    }
};

watch(tahap1Enabled, (val) => {
    if (!val) {
        form.juri_kategori_tahap_1_ids = [];
    }
});

watch(tahap2Enabled, (val) => {
    if (!val) {
        form.juri_kategori_tahap_2_ids = [];
    }
});

/* =========================
SUBMIT (SAFE VERSION)
========================= */
const handleSubmit = () => {
    if (props.readonly) return;

    if (props.user?.id) {
        form.put(`/admin/users/${props.user.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("User berhasil diperbarui");
                emit("update:open", false);
            },
            onError: () => {
                toast.error("Gagal memperbarui user");
            },
        });
    } else {
        form.post("/admin/users", {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("User berhasil ditambahkan");
                form.reset();
                emit("update:open", false);
            },
            onError: () => {
                toast.error("Gagal menambahkan user");
            },
        });
    }
};

const isCreateMode = computed(() => !props.user?.id);
const isCreateJuri = computed(() => isCreateMode.value && form.roles.includes("juri"));
const dosenList = computed(() => props.dosenOptions ?? []);

watch(
    () => form.dosen_id,
    (val) => {
        if (!isCreateJuri.value) return;
        const dosen = dosenList.value.find((d) => d.id === Number(val ?? 0));
        if (!dosen) return;
        form.name = dosen.nama;
        form.email = dosen.email;
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-3xl">
            <form @submit.prevent="handleSubmit" novalidate>
                <DialogHeader>
                        <DialogTitle>
                            {{
                                props.user?.id
                                ? "Detail / Edit Juri"
                                : "Tambah Juri"
                            }}
                        </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div
                            v-if="isCreateJuri"
                            class="space-y-1 md:col-span-2"
                        >
                            <Select
                                :model-value="form.dosen_id ? String(form.dosen_id) : ''"
                                @update:model-value="(v) => (form.dosen_id = v ? Number(v) : null)"
                                :disabled="props.readonly"
                            >
                                <SelectTrigger class="w-full bg-white">
                                    <SelectValue placeholder="Pilih dosen" />
                                </SelectTrigger>
                                <SelectContent class="max-h-72">
                                    <div
                                        v-if="!dosenList.length"
                                        class="px-3 py-2 text-sm text-slate-500"
                                    >
                                        Tidak ada data dosen
                                    </div>
                                    <SelectItem
                                        v-else
                                        v-for="d in dosenList"
                                        :key="d.id"
                                        :value="String(d.id)"
                                    >
                                        <span class="font-medium">{{ d.nama }}</span>
                                        <span class="text-slate-500">
                                            - {{ d.email }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="(form.errors as any).dosen_id"
                                class="text-xs text-destructive"
                            >
                                {{ (form.errors as any).dosen_id }}
                            </p>
                        </div>

                        <!-- NAME -->
                        <div class="space-y-1">
                            <Input
                                v-model="form.name"
                                placeholder="Nama"
                                :disabled="props.readonly || isCreateJuri"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- EMAIL -->
                        <div class="space-y-1">
                            <Input
                                v-model="form.email"
                                placeholder="Email"
                                :disabled="props.readonly || isCreateJuri"
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>
                    </div>

                    <!-- KATEGORI JURI -->
                    <div v-if="form.roles.includes('juri')" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Tahap Penjurian
                            </label>
                            <div class="grid gap-3 md:grid-cols-2 md:items-start">
                                <div class="rounded-lg border border-slate-200 p-3 space-y-3 h-fit md:self-start">
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <Checkbox
                                            :model-value="tahap1Enabled"
                                            :disabled="props.readonly"
                                            @update:model-value="(val) => (tahap1Enabled = val === true)"
                                        />
                                        Juri Tahap 1
                                    </label>

                                    <div
                                        v-if="tahap1Enabled"
                                        class="grid grid-cols-1 gap-2"
                                    >
                                        <label
                                            v-for="kategori in props.kategoriOptions ?? []"
                                            :key="`t1-${kategori.id}`"
                                            class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                            :class="
                                                form.juri_kategori_tahap_1_ids.includes(kategori.id)
                                                    ? 'bg-slate-50'
                                                    : 'bg-white'
                                            "
                                        >
                                            <Checkbox
                                                :model-value="
                                                    form.juri_kategori_tahap_1_ids.includes(
                                                        kategori.id
                                                    )
                                                "
                                                :disabled="props.readonly"
                                                @update:model-value="
                                                    (val) =>
                                                        toggleKategori(
                                                            'tahap_1',
                                                            kategori.id,
                                                            val === true
                                                        )
                                                "
                                            />
                                            {{ kategori.nama }}
                                        </label>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-slate-200 p-3 space-y-3 h-fit md:self-start">
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <Checkbox
                                            :model-value="tahap2Enabled"
                                            :disabled="props.readonly"
                                            @update:model-value="(val) => (tahap2Enabled = val === true)"
                                        />
                                        Juri Tahap 2
                                    </label>

                                    <div
                                        v-if="tahap2Enabled"
                                        class="grid grid-cols-1 gap-2"
                                    >
                                        <label
                                            v-for="kategori in props.kategoriOptions ?? []"
                                            :key="`t2-${kategori.id}`"
                                            class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                            :class="
                                                form.juri_kategori_tahap_2_ids.includes(kategori.id)
                                                    ? 'bg-slate-50'
                                                    : 'bg-white'
                                            "
                                        >
                                            <Checkbox
                                                :model-value="
                                                    form.juri_kategori_tahap_2_ids.includes(
                                                        kategori.id
                                                    )
                                                "
                                                :disabled="props.readonly"
                                                @update:model-value="
                                                    (val) =>
                                                        toggleKategori(
                                                            'tahap_2',
                                                            kategori.id,
                                                            val === true
                                                        )
                                                "
                                            />
                                            {{ kategori.nama }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <p
                                v-if="form.errors.juri_kategori_tahap_1_ids"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.juri_kategori_tahap_1_ids }}
                            </p>
                            <p
                                v-if="form.errors.juri_kategori_tahap_2_ids"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.juri_kategori_tahap_2_ids }}
                            </p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Batal
                    </Button>

                    <Button
                        v-if="!props.readonly"
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2"
                    >
                        <Spinner v-if="form.processing" class="w-4 h-4" />
                        {{
                            form.processing
                                ? "Menyimpan..."
                                : props.user?.id
                                ? "Update"
                                : "Simpan"
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

