<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { Upload } from "lucide-vue-next";
import * as XLSX from "xlsx";

const props = defineProps<{
    open: boolean;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:open", value: boolean): void;
}>();

const file = ref<File | null>(null);
const processing = ref(false);
const page = usePage();
const fileInputRef = ref<HTMLInputElement | null>(null);

const fileLabel = computed(() => {
    if (!file.value) return "Belum ada file yang dipilih";
    return file.value.name;
});

const triggerPicker = () => {
    fileInputRef.value?.click();
};

watch(
    () => props.open,
    (val) => {
        if (!val) {
            file.value = null;
            processing.value = false;
        }
    },
);

const onFileChange = (e: Event) => {
    const f = (e.target as HTMLInputElement).files?.[0] ?? null;
    file.value = f;
};

const isExcelFile = (f: File) => {
    const name = (f.name || "").toLowerCase();
    return name.endsWith(".xlsx") || name.endsWith(".xls");
};

const normalize = (v: unknown) => String(v ?? "").trim();
const normalizeHeader = (v: unknown) => normalize(v).toLowerCase();

const parseExcelRows = async (f: File) => {
    const buf = await f.arrayBuffer();
    const wb = XLSX.read(buf, { type: "array" });
    const sheetName = wb.SheetNames?.[0];
    if (!sheetName) {
        return {
            rows: [] as Array<{ nik: string; nama: string; email: string }>,
            error: "Sheet tidak ditemukan.",
        };
    }

    const sheet = wb.Sheets[sheetName];
    const grid = XLSX.utils.sheet_to_json<any[]>(sheet, {
        header: 1,
        blankrows: false,
        defval: "",
    }) as any[][];

    const first = grid[0] ?? null;
    if (!first) {
        return { rows: [], error: "File kosong." };
    }

    const header = first.map(normalizeHeader);
    const hasHeader =
        header.includes("email") ||
        header.includes("nama") ||
        header.includes("nik");

    let idxNik = 0;
    let idxNama = 1;
    let idxEmail = 2;
    let start = 0;

    if (hasHeader) {
        idxNik = header.indexOf("nik");
        idxNama = header.indexOf("nama");
        idxEmail = header.indexOf("email");
        if (idxNik < 0 || idxNama < 0 || idxEmail < 0) {
            return {
                rows: [],
                error: "Header kolom wajib berisi: nik, nama, email.",
            };
        }
        start = 1;
    }

    const rows: Array<{ nik: string; nama: string; email: string }> = [];
    const seen = new Set<string>();

    for (let i = start; i < grid.length; i++) {
        const r = grid[i] ?? [];
        const nik = normalize(r[idxNik]);
        const nama = normalize(r[idxNama]);
        const email = normalize(r[idxEmail]);
        if (!nik && !nama && !email) continue;
        if (!nik || !nama || !email) {
            return {
                rows: [],
                error: `Baris ${i + 1}: NIK, Nama, dan Email wajib diisi.`,
            };
        }
        const key = email.toLowerCase();
        if (seen.has(key)) {
            return {
                rows: [],
                error: `Baris ${i + 1}: Email duplikat di file.`,
            };
        }
        seen.add(key);
        rows.push({ nik, nama, email });
        if (rows.length > 5000) {
            return { rows: [], error: "Maksimal 5000 baris per import." };
        }
    }

    if (rows.length === 0) {
        return { rows: [], error: "Tidak ada data yang bisa diimpor." };
    }

    return { rows, error: null as string | null };
};

const submit = async () => {
    if (props.readonly) return;
    if (!file.value) {
        toast.error("Pilih file Excel atau CSV terlebih dahulu.");
        return;
    }

    processing.value = true;

    if (isExcelFile(file.value)) {
        try {
            const parsed = await parseExcelRows(file.value);
            if (parsed.error) {
                toast.error(parsed.error);
                processing.value = false;
                return;
            }

            router.post(
                "/admin/dosen/import",
                { rows: parsed.rows },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success("Import data dosen berhasil.");
                        emit("update:open", false);
                    },
                    onError: (errors) => {
                        const msg =
                            (errors as any)?.rows ||
                            (errors as any)?.file ||
                            (page.props as any)?.errors?.rows ||
                            (page.props as any)?.errors?.file ||
                            "Gagal import data dosen.";
                        toast.error(String(msg));
                    },
                    onFinish: () => {
                        processing.value = false;
                    },
                },
            );
        } catch (e) {
            toast.error("Gagal membaca file Excel.");
            processing.value = false;
        }
        return;
    }

    router.post(
        "/admin/dosen/import",
        { file: file.value },
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.success("Import data dosen berhasil.");
                emit("update:open", false);
            },
            onError: (errors) => {
                const msg =
                    (errors as any)?.file ||
                    (page.props as any)?.errors?.file ||
                    "Gagal import data dosen.";
                toast.error(String(msg));
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Import Data Dosen</DialogTitle>
            </DialogHeader>

            <div class="mt-4 space-y-4">
                <div
                    class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"
                >
                    <p class="font-medium">Format Excel / CSV</p>

                    <p class="mt-2 text-slate-600">
                        Kolom header wajib:
                        <span class="font-medium">nik</span>,
                        <span class="font-medium">nama</span>, dan
                        <span class="font-medium">email</span>. Kolom lain di
                        file akan diabaikan.
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">File Excel / CSV</label>
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,.xlsx,.xls"
                        class="hidden"
                        :disabled="readonly"
                        @change="onFileChange"
                    />

                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full sm:w-auto"
                            :disabled="readonly"
                            @click="triggerPicker"
                        >
                            <Upload class="h-4 w-4" />
                            Pilih File
                        </Button>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-700 truncate">
                                {{ fileLabel }}
                            </p>
                            <p class="text-xs text-slate-500">Maksimal 5MB.</p>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-6 gap-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    :disabled="processing || readonly"
                    @click="submit"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    Import
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
