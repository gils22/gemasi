<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { ScrollArea, ScrollBar } from "@/components/ui/scroll-area";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { ChevronDown, Check } from "lucide-vue-next";

type FavoritOption = {
    id: number;
    label: string;
};

type Favorit = {
    items: Array<{
        id: number;
        peringkat?: number | null;
        nama_karya: string | null;
        nama_kategori: string | null;
    }>;
} | null;

const props = defineProps<{
    open: boolean;
    favoritOptions: FavoritOption[];
    favorit: Favorit;
}>();

const emit = defineEmits<{
    (event: "update:open", value: boolean): void;
    (event: "submit", payload: { jumlah: number; karya_peserta_ids: number[] }): void;
}>();

const jumlah = ref("1");
const selectedIds = ref<string[]>([""]);
const openComboboxes = ref<Record<number, boolean>>({});
const searchQueries = ref<Record<number, string>>({});

watch(
    () => jumlah.value,
    (value) => {
        const total = Number(value) || 1;
        if (selectedIds.value.length < total) {
            selectedIds.value = [
                ...selectedIds.value,
                ...Array.from({ length: total - selectedIds.value.length }, () => ""),
            ];
        } else if (selectedIds.value.length > total) {
            selectedIds.value = selectedIds.value.slice(0, total);
        }
    },
    { immediate: true },
);

watch(
    () => props.open,
    (value) => {
        if (!value) return;
        jumlah.value = String(props.favorit?.items?.length || 1);
        selectedIds.value = Array.from(
            { length: Number(jumlah.value) || 1 },
            (_, index) => String(props.favorit?.items?.[index]?.id ?? ""),
        );
    },
);

const favoritTerpilih = computed(() =>
    props.favorit?.items?.map((item) => ({
        nama: item.nama_karya ?? "-",
        kategori: item.nama_kategori ?? "-",
    })) ?? [],
);

const setSelected = (index: number, value: string) => {
    selectedIds.value[index] = value;
    openComboboxes.value[index] = false;
};

const currentLabel = (value: string) =>
    props.favoritOptions.find((item) => String(item.id) === value)?.label ??
    "Pilih karya";

const filteredOptions = (index: number) => {
    const keyword = (searchQueries.value[index] ?? "").trim().toLowerCase();
    if (!keyword) return props.favoritOptions;
    return props.favoritOptions.filter((item) =>
        item.label.toLowerCase().includes(keyword),
    );
};

const submit = () => {
    emit("submit", {
        jumlah: Number(jumlah.value) || 1,
        karya_peserta_ids: selectedIds.value
            .map((value) => Number(value))
            .filter((value) => value > 0),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Atur Karya Favorit</DialogTitle>
                <DialogDescription>
                    Tentukan berapa karya yang akan masuk favorit lalu pilih satu per satu.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700">
                        Jumlah Karya Favorit
                    </p>
                    <Select v-model="jumlah">
                        <SelectTrigger class="h-10 bg-white">
                            <SelectValue placeholder="Pilih jumlah" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">1 Karya</SelectItem>
                            <SelectItem value="2">2 Karya</SelectItem>
                            <SelectItem value="3">3 Karya</SelectItem>
                            <SelectItem value="4">4 Karya</SelectItem>
                            <SelectItem value="5">5 Karya</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="index in Number(jumlah)"
                        :key="index"
                        class="space-y-2"
                    >
                        <p class="text-sm font-medium text-slate-700">
                            Favorit {{ index }}
                        </p>
                        <Popover
                            v-model:open="openComboboxes[index - 1]"
                        >
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="w-full justify-between bg-white"
                                    type="button"
                                >
                                    <span class="truncate">
                                        {{
                                            currentLabel(
                                                selectedIds[index - 1] ?? "",
                                            )
                                        }}
                                    </span>
                                    <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent
                                class="w-[--radix-popover-trigger-width] overflow-hidden rounded-md border border-slate-200 bg-white p-0 shadow-lg"
                                align="start"
                                side="bottom"
                                :avoid-collisions="false"
                                :side-offset="8"
                            >
                                <div class="border-b border-slate-100 p-2">
                                    <input
                                        v-model="searchQueries[index - 1]"
                                        class="h-9 w-full rounded-md border border-slate-200 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-indigo-300"
                                        placeholder="Ketik nama karya atau kategori..."
                                    />
                                </div>
                                <ScrollArea class="h-64">
                                    <div class="p-1">
                                        <button
                                            v-for="item in filteredOptions(index - 1)"
                                            :key="item.id"
                                            type="button"
                                            class="flex w-full items-start justify-between rounded-md px-3 py-2 text-left text-sm hover:bg-slate-100"
                                            @click="
                                                setSelected(
                                                    index - 1,
                                                    String(item.id),
                                                )
                                            "
                                        >
                                            <span class="min-w-0">
                                                <span class="block truncate font-medium text-slate-900">
                                                    {{ item.label.split(" — ")[0] }}
                                                </span>
                                                <span class="block truncate text-xs text-slate-500">
                                                    {{ item.label.split(" — ")[1] ?? "-" }}
                                                </span>
                                            </span>
                                            <Check
                                                v-if="
                                                    selectedIds[index - 1] ===
                                                    String(item.id)
                                                "
                                                class="h-4 w-4 text-indigo-600"
                                            />
                                        </button>
                                        <p
                                            v-if="!filteredOptions(index - 1).length"
                                            class="px-3 py-2 text-sm text-slate-500"
                                        >
                                            Tidak ada karya yang cocok dengan pencarian.
                                        </p>
                                    </div>
                                    <ScrollBar orientation="vertical" />
                                </ScrollArea>
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Favorit Saat Ini
                    </p>
                    <div v-if="favoritTerpilih.length" class="mt-3 space-y-2">
                        <p
                            v-for="(item, index) in favoritTerpilih"
                            :key="`${item.nama}-${index}`"
                            class="text-sm text-slate-800"
                        >
                            {{ index + 1 }}. {{ item.nama }}
                            <span class="text-slate-500">
                                — {{ item.kategori }}
                            </span>
                        </p>
                    </div>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        Belum ada karya favorit yang ditetapkan.
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" @click="emit('update:open', false)">
                        Batal
                    </Button>
                    <Button @click="submit">
                        Tetapkan Favorit
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
