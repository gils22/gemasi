<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { getLocalTimeZone, parseDate } from "@internationalized/date";
import { ChevronDown } from "lucide-vue-next";
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

type TimelineItem = {
    id: number;
    judul: string;
    tipe: "utama" | "tambahan";
    fase_kunci: string | null;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    deskripsi: string | null;
    urutan: number;
    aktif: boolean;
};

type FaseOption = { key: string; label: string };

const props = defineProps<{
    open: boolean;
    item?: TimelineItem | null;
    basePath: string;
    canCreate: boolean;
    canEdit: boolean;
    faseUtama: FaseOption[];
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const toInputDateTime = (val: string | null | undefined) => {
    if (!val) return "";
    const d = new Date(val);
    if (Number.isNaN(d.getTime())) return "";
    const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 16);
};

const form = useForm({
    judul: "",
    tipe: "utama" as "utama" | "tambahan",
    fase_kunci: "opening" as string | null,
    mulai_pada: "",
    selesai_pada: "",
    is_tba: true,
    deskripsi: "",
    urutan: 0,
    aktif: true,
});

const mulaiDate = ref<ReturnType<typeof parseDate> | null>(null);
const selesaiDate = ref<ReturnType<typeof parseDate> | null>(null);
const mulaiTime = ref("");
const selesaiTime = ref("");
const mulaiOpen = ref(false);
const selesaiOpen = ref(false);

const faseLabel = computed<Record<string, string>>(() => {
    return Object.fromEntries(props.faseUtama.map((f) => [f.key, f.label]));
});

const isFormInvalid = computed(() => {
    if (form.judul.trim().length === 0) return true;
    if (form.tipe === "utama" && !form.fase_kunci) return true;
    if (!form.is_tba && (!form.mulai_pada || !form.selesai_pada)) return true;

    return false;
});

watch(
    () => props.item,
    (val) => {
        if (val) {
            form.judul = val.judul ?? "";
            form.tipe = val.tipe ?? "utama";
            form.fase_kunci = val.fase_kunci ?? "opening";
            form.mulai_pada = toInputDateTime(val.mulai_pada);
            form.selesai_pada = toInputDateTime(val.selesai_pada);
            form.is_tba = !!val.is_tba;
            form.deskripsi = val.deskripsi ?? "";
            form.urutan = val.urutan ?? 0;
            form.aktif = !!val.aktif;
            if (form.mulai_pada) {
                mulaiDate.value = parseDate(form.mulai_pada.slice(0, 10));
                mulaiTime.value = form.mulai_pada.slice(11, 16) || "";
            } else {
                mulaiDate.value = null;
                mulaiTime.value = "";
            }
            if (form.selesai_pada) {
                selesaiDate.value = parseDate(form.selesai_pada.slice(0, 10));
                selesaiTime.value = form.selesai_pada.slice(11, 16) || "";
            } else {
                selesaiDate.value = null;
                selesaiTime.value = "";
            }
            return;
        }

        form.reset();
        form.tipe = "utama";
        form.fase_kunci = "opening";
        form.is_tba = true;
        form.aktif = true;
        mulaiDate.value = null;
        selesaiDate.value = null;
        mulaiTime.value = "";
        selesaiTime.value = "";
    },
    { immediate: true }
);

const syncMulai = () => {
    if (!mulaiDate.value || !mulaiTime.value) {
        form.mulai_pada = "";
        return;
    }
    form.mulai_pada = `${mulaiDate.value.toString()}T${mulaiTime.value}`;
};

const syncSelesai = () => {
    if (!selesaiDate.value || !selesaiTime.value) {
        form.selesai_pada = "";
        return;
    }
    form.selesai_pada = `${selesaiDate.value.toString()}T${selesaiTime.value}`;
};

watch([mulaiDate, mulaiTime], syncMulai);
watch([selesaiDate, selesaiTime], syncSelesai);

watch(
    () => form.is_tba,
    (isTba) => {
        if (isTba) {
            mulaiDate.value = null;
            selesaiDate.value = null;
            mulaiTime.value = "";
            selesaiTime.value = "";
            form.mulai_pada = "";
            form.selesai_pada = "";
        }
    }
);
watch(
    () => props.open,
    (open) => {
        if (!open) form.clearErrors();
    }
);

watch(
    () => form.tipe,
    (tipe) => {
        if (tipe === "utama" && !form.fase_kunci) {
            form.fase_kunci = "opening";
        }
        if (tipe === "utama" && form.fase_kunci && faseLabel.value[form.fase_kunci]) {
            form.judul = faseLabel.value[form.fase_kunci];
        }
    }
);

watch(
    () => form.fase_kunci,
    (fase) => {
        if (form.tipe === "utama" && fase && faseLabel.value[fase]) {
            form.judul = faseLabel.value[fase];
        }
    }
);

const submit = () => {
    if (isFormInvalid.value) {
        toast.error("Lengkapi field wajib terlebih dahulu");
        return;
    }

    const payload = {
        ...form.data(),
        mulai_pada: form.is_tba ? null : form.mulai_pada || null,
        selesai_pada: form.is_tba ? null : form.selesai_pada || null,
    };

    if (props.item?.id) {
        if (!props.canEdit) return;
        form.transform(() => payload).put(`${props.basePath}/timeline/${props.item.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Timeline berhasil diperbarui");
                emit("update:open", false);
            },
            onError: () => toast.error("Gagal memperbarui timeline"),
        });
        return;
    }

    if (!props.canCreate) return;
    form.transform(() => payload).post(`${props.basePath}/timeline`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Timeline berhasil ditambahkan");
            emit("update:open", false);
        },
        onError: () => toast.error("Gagal menambahkan timeline"),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="sm:max-w-2xl">
            <form @submit.prevent="submit" class="space-y-4">
                <DialogHeader>
                    <DialogTitle>
                        {{ item ? "Edit Timeline" : "Tambah Timeline" }}
                    </DialogTitle>
                </DialogHeader>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tipe</label>
                        <Select
                            :model-value="form.tipe"
                            @update:model-value="
                                (val) => (form.tipe = (val as 'utama' | 'tambahan') ?? 'utama')
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="utama">Fase Utama</SelectItem>
                                <SelectItem value="tambahan">Tambahan</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.tipe" class="text-xs text-destructive">
                            {{ form.errors.tipe }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">
                            {{ form.tipe === "utama" ? "Fase" : "Terkait fase (opsional)" }}
                        </label>
                        <Select
                            :model-value="form.fase_kunci ?? ''"
                            @update:model-value="
                                (val) =>
                                    (form.fase_kunci =
                                        (val as string) === '__kosong__'
                                            ? null
                                            : (val as string) || null)
                            "
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih fase" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-if="form.tipe === 'tambahan'" value="__kosong__">
                                    Tidak terkait fase
                                </SelectItem>
                                <SelectItem
                                    v-for="fase in faseUtama"
                                    :key="fase.key"
                                    :value="fase.key"
                                >
                                    {{ fase.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.fase_kunci" class="text-xs text-destructive">
                            {{ form.errors.fase_kunci }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Judul</label>
                    <Input
                        v-model="form.judul"
                        :readonly="form.tipe === 'utama'"
                        placeholder="Contoh: Perpanjangan Pendaftaran Gelombang 1"
                    />
                    <p v-if="form.errors.judul" class="text-xs text-destructive">
                        {{ form.errors.judul }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Deskripsi</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Keterangan tambahan (opsional)"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Urutan</label>
                    <Input v-model.number="form.urutan" type="number" min="0" />
                </div>

                <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                    <div>
                        <p class="text-sm font-medium">Tanggal TBA</p>
                        <p class="text-xs text-slate-500">Aktifkan jika tanggal belum final.</p>
                    </div>
                    <Switch
                        :model-value="form.is_tba"
                        @update:model-value="(val) => (form.is_tba = val === true)"
                    />
                </div>

                <div v-if="!form.is_tba" class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Mulai</label>
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] items-center gap-3">
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

                                <div class="flex items-center gap-2">
                                    <Input v-model="mulaiTime" type="time" class="w-full sm:w-28" />
                                    <span class="text-xs text-slate-500">WIB</span>
                                </div>
                            </div>
                            <p v-if="form.errors.mulai_pada" class="text-xs text-destructive">
                                {{ form.errors.mulai_pada }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Selesai</label>
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] items-center gap-3">
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

                                <div class="flex items-center gap-2">
                                    <Input v-model="selesaiTime" type="time" class="w-full sm:w-28" />
                                    <span class="text-xs text-slate-500">WIB</span>
                                </div>
                            </div>
                            <p v-if="form.errors.selesai_pada" class="text-xs text-destructive">
                                {{ form.errors.selesai_pada }}
                            </p>
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
                            (!item && !canCreate) ||
                            (!!item && !canEdit)
                        "
                    >
                        {{ form.processing ? "Menyimpan..." : "Simpan" }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

