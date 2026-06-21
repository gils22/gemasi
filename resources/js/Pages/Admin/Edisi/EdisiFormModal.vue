<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { getLocalTimeZone, parseDate } from "@internationalized/date";
import type { DateValue } from "reka-ui";
import { ChevronDown } from "lucide-vue-next";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Spinner } from "@/components/ui/spinner";

type Edisi = {
    id: number;
    nama: string;
    tahun: number;
    status: "draft" | "aktif" | "arsip";
    mulai_pada: string | null;
    selesai_pada: string | null;
};

const props = defineProps<{
    open: boolean;
    edisi?: Edisi | null;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const toInputDate = (val: string | null | undefined) => {
    if (!val) return "";
    return String(val).slice(0, 10);
};

const form = useForm({
    nama: "",
    tahun: new Date().getFullYear(),
    status: "draft" as "draft" | "aktif" | "arsip",
    mulai_pada: "",
    selesai_pada: "",
});

const mulaiDate = ref<DateValue | null>(null);
const selesaiDate = ref<DateValue | null>(null);
const mulaiOpen = ref(false);
const selesaiOpen = ref(false);

const isFormInvalid = computed(() => {
    if (form.nama.trim().length === 0) return true;
    if (!form.tahun || Number.isNaN(Number(form.tahun))) return true;
    if (!form.status) return true;

    return false;
});

watch(
    () => props.edisi,
    (val) => {
        if (val) {
            form.nama = val.nama;
            form.tahun = val.tahun;
            form.status = val.status;
            form.mulai_pada = toInputDate(val.mulai_pada);
            form.selesai_pada = toInputDate(val.selesai_pada);
            mulaiDate.value = form.mulai_pada
                ? (parseDate(form.mulai_pada) as DateValue)
                : null;
            selesaiDate.value = form.selesai_pada
                ? (parseDate(form.selesai_pada) as DateValue)
                : null;
        } else {
            form.reset();
            form.tahun = new Date().getFullYear();
            form.status = "draft";
            mulaiDate.value = null;
            selesaiDate.value = null;
        }
    },
    { immediate: true }
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            form.clearErrors();
            mulaiOpen.value = false;
            selesaiOpen.value = false;
        }
    }
);

watch(mulaiDate, (val) => {
    form.mulai_pada = val ? val.toString() : "";
});

watch(selesaiDate, (val) => {
    form.selesai_pada = val ? val.toString() : "";
});

const submit = () => {
    if (isFormInvalid.value) {
        toast.error("Lengkapi field wajib terlebih dahulu");
        return;
    }

    const payload = {
        ...form.data(),
        mulai_pada: form.mulai_pada || null,
        selesai_pada: form.selesai_pada || null,
    };

    if (props.edisi?.id) {
        form.transform(() => payload).put(`/admin/edisi-lomba/${props.edisi.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Edisi berhasil diperbarui");
                emit("update:open", false);
            },
            onError: () => toast.error("Gagal memperbarui edisi"),
        });
        return;
    }

    form.transform(() => payload).post("/admin/edisi-lomba", {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Edisi berhasil ditambahkan");
            emit("update:open", false);
        },
        onError: () => toast.error("Gagal menambahkan edisi"),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-lg">
            <form @submit.prevent="submit" class="space-y-4">
                <DialogHeader>
                    <DialogTitle>
                        {{ edisi ? "Edit Edisi Lomba" : "Tambah Edisi Lomba" }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-2">
                    <label class="text-sm font-medium">
                        Nama Edisi <span class="text-destructive">*</span>
                    </label>
                    <Input v-model="form.nama" placeholder="Nama edisi (contoh: GEMASI 2027)" />
                    <p v-if="form.errors.nama" class="text-xs text-destructive">{{ form.errors.nama }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            Tahun <span class="text-destructive">*</span>
                        </label>
                        <Input v-model.number="form.tahun" type="number" placeholder="Tahun" />
                        <p v-if="form.errors.tahun" class="text-xs text-destructive">{{ form.errors.tahun }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            Status <span class="text-destructive">*</span>
                        </label>
                        <Select
                            :model-value="form.status"
                            @update:model-value="
                                (val) =>
                                    (form.status =
                                        ((val as 'draft' | 'aktif' | 'arsip') ?? 'draft'))
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="arsip">Arsip</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tanggal Mulai</label>
                        <Popover v-model:open="mulaiOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-full justify-between font-normal"
                                >
                                    {{
                                        mulaiDate
                                            ? mulaiDate
                                                  .toDate(getLocalTimeZone())
                                                  .toLocaleDateString("id-ID")
                                            : "Pilih tanggal"
                                    }}
                                    <ChevronDown class="h-4 w-4 text-slate-400" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar
                                    :model-value="mulaiDate"
                                    layout="month-and-year"
                                    @update:model-value="(value) => {
                                        if (value) {
                                            mulaiDate = value
                                            mulaiOpen = false
                                        }
                                    }"
                                />
                            </PopoverContent>
                        </Popover>
                        <p v-if="form.errors.mulai_pada" class="text-xs text-destructive">
                            {{ form.errors.mulai_pada }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tanggal Selesai</label>
                        <Popover v-model:open="selesaiOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-full justify-between font-normal"
                                >
                                    {{
                                        selesaiDate
                                            ? selesaiDate
                                                  .toDate(getLocalTimeZone())
                                                  .toLocaleDateString("id-ID")
                                            : "Pilih tanggal"
                                    }}
                                    <ChevronDown class="h-4 w-4 text-slate-400" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar
                                    :model-value="selesaiDate"
                                    layout="month-and-year"
                                    @update:model-value="(value) => {
                                        if (value) {
                                            selesaiDate = value
                                            selesaiOpen = false
                                        }
                                    }"
                                />
                            </PopoverContent>
                        </Popover>
                        <p v-if="form.errors.selesai_pada" class="text-xs text-destructive">
                            {{ form.errors.selesai_pada }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Batal</Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || isFormInvalid"
                    >
                        <Spinner v-if="form.processing" class="h-4 w-4" />
                        <span v-else>Simpan</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

