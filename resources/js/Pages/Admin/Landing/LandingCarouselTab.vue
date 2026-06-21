<script setup lang="ts">
import { onBeforeUnmount, ref } from "vue";
import {
    GripVertical,
    Trash2,
    Image as ImageIcon,
    Upload,
    ImagePlus,
} from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

type CarouselImage = {
    name: string | null;
    url?: string;
    preview_url?: string;
    path?: string;
};

type LandingForm = {
    login_carousel_items: CarouselImage[];
    login_carousel_files?: File[];
    login_carousel_remove_paths?: string[];
};

const props = defineProps<{
    form: LandingForm;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const stagedFiles = ref<File[]>([]);
const stagedPreviews = ref<string[]>([]);
const draggingNewIndex = ref<number | null>(null);
const draggingExistingIndex = ref<number | null>(null);

const revokeIfBlob = (value?: string | null) => {
    if (value && value.startsWith("blob:")) {
        URL.revokeObjectURL(value);
    }
};

const onSelectFiles = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    if (!files.length) return;

    stagedPreviews.value.forEach((url) => revokeIfBlob(url));
    stagedFiles.value = files;
    stagedPreviews.value = files.map((file) => URL.createObjectURL(file));
    input.value = "";
};

const addStagedFiles = () => {
    if (!stagedFiles.value.length) return;

    props.form.login_carousel_files = [
        ...(props.form.login_carousel_files ?? []),
        ...stagedFiles.value,
    ];
    props.form.login_carousel_items = [
        ...props.form.login_carousel_items,
        ...stagedFiles.value.map((file, index) => ({
            name: file.name,
            url: stagedPreviews.value[index],
            preview_url: stagedPreviews.value[index],
        })),
    ];

    stagedFiles.value = [];
    stagedPreviews.value = [];
};

const removeStagedFile = (index: number) => {
    revokeIfBlob(stagedPreviews.value[index]);
    stagedFiles.value.splice(index, 1);
    stagedPreviews.value.splice(index, 1);
};

const removeExistingImage = (image: CarouselImage) => {
    if (!image.path) {
        revokeIfBlob(image.preview_url || image.url);
        props.form.login_carousel_items = props.form.login_carousel_items.filter(
            (item) => item !== image,
        );
        const fileIndex = props.form.login_carousel_files?.findIndex(
            (file) => file.name === image.name,
        );
        if (fileIndex !== undefined && fileIndex >= 0) {
            props.form.login_carousel_files?.splice(fileIndex, 1);
        }
        return;
    }

    props.form.login_carousel_remove_paths = [
        ...(props.form.login_carousel_remove_paths ?? []),
        image.path,
    ];
    props.form.login_carousel_items = props.form.login_carousel_items.filter(
        (item) => item.path !== image.path,
    );
};

const moveNewFile = (fromIndex: number, toIndex: number) => {
    const files = props.form.login_carousel_files ?? [];
    if (toIndex < 0 || toIndex >= files.length || fromIndex === toIndex) return;
    const [moved] = files.splice(fromIndex, 1);
    files.splice(toIndex, 0, moved);
};

const moveExistingImage = (fromIndex: number, toIndex: number) => {
    const items = props.form.login_carousel_items;
    if (toIndex < 0 || toIndex >= items.length || fromIndex === toIndex) return;
    const [moved] = items.splice(fromIndex, 1);
    items.splice(toIndex, 0, moved);
};

const onExistingDrop = (index: number) => {
    if (draggingExistingIndex.value === null) return;
    moveExistingImage(draggingExistingIndex.value, index);
    draggingExistingIndex.value = null;
};

const filePreviewUrl = (file: File) => window.URL.createObjectURL(file);

onBeforeUnmount(() => {
    stagedPreviews.value.forEach((url) => revokeIfBlob(url));
    props.form.login_carousel_items.forEach((item) =>
        revokeIfBlob(item.preview_url || item.url),
    );
});
</script>

<template>
    <Card class="rounded-2xl border-slate-200">
        <CardHeader class="space-y-1">
            <div class="flex items-center gap-2">
                <ImagePlus class="h-4 w-4 text-slate-500" />
                <CardTitle>Carousel Login</CardTitle>
            </div>
        </CardHeader>
        <CardContent class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Upload gambar carousel
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Gambar akan tampil sebagai carousel di halaman login.
                        </p>
                    </div>
                    <Button type="button" variant="outline" @click="inputRef?.click()">
                        <Upload class="mr-2 h-4 w-4" />
                        Pilih Gambar
                    </Button>
                </div>

                <input
                    ref="inputRef"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    class="hidden"
                    @change="onSelectFiles"
                />

                <div v-if="stagedFiles.length" class="mt-5 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Preview gambar baru
                        </p>
                        <Button type="button" size="sm" @click="addStagedFiles">
                            Tambahkan
                        </Button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="(file, index) in stagedFiles"
                            :key="`${file.name}-${index}`"
                            class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                        >
                            <img
                                :src="stagedPreviews[index]"
                                :alt="file.name"
                                class="h-40 w-full object-cover"
                            />
                            <div class="flex items-center justify-between gap-2 p-3">
                                <p class="min-w-0 truncate text-xs text-slate-600">
                                    {{ file.name }}
                                </p>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-slate-500 hover:text-rose-600"
                                    @click="removeStagedFile(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <ImageIcon class="h-4 w-4 text-slate-500" />
                    <p class="text-sm font-semibold text-slate-900">
                        Gambar tersimpan
                    </p>
                </div>

                <div
                    v-if="props.form.login_carousel_items.length"
                    class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="(image, index) in props.form.login_carousel_items"
                        :key="image.path || image.url"
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white transition"
                    >
                        <div class="aspect-[4/3] bg-slate-100">
                            <img
                                :src="image.preview_url || image.url"
                                :alt="image.name || 'Carousel'"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div class="flex items-center justify-between gap-2 p-3">
                            <p class="min-w-0 truncate text-xs text-slate-600">
                                {{ image.name || "Carousel image" }}
                            </p>
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 cursor-grab text-slate-500 active:cursor-grabbing"
                                    title="Seret untuk mengubah urutan"
                                    draggable="true"
                                    @dragstart="draggingExistingIndex = index"
                                    @dragover.prevent
                                    @drop.prevent="onExistingDrop(index)"
                                    @dragend="draggingExistingIndex = null"
                                >
                                    <GripVertical class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-slate-500 hover:text-rose-600"
                                    @click="removeExistingImage(image)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-xs text-slate-500">
                    Belum ada gambar carousel.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
