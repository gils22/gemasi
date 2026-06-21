<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { ImagePlus, Trash2, Upload } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

type GalleryImage = {
    name: string | null;
    url?: string;
    preview_url?: string;
    path?: string;
};

type LandingSettingForm = {
    gallery_items: GalleryImage[];
    gallery_files?: File[];
    gallery_remove_paths?: string[];
};

const props = defineProps<{
    form: LandingSettingForm;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const stagedFiles = ref<File[]>([]);
const stagedPreviews = ref<string[]>([]);

const revokeIfBlob = (value?: string | null) => {
    if (value && value.startsWith("blob:")) {
        URL.revokeObjectURL(value);
    }
};

const addFiles = (event: Event) => {
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

    props.form.gallery_files = [
        ...(props.form.gallery_files ?? []),
        ...stagedFiles.value,
    ];
    props.form.gallery_items = [
        ...props.form.gallery_items,
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
    const preview = stagedPreviews.value[index];
    revokeIfBlob(preview);
    stagedFiles.value.splice(index, 1);
    stagedPreviews.value.splice(index, 1);
};

const removeExistingImage = (image: GalleryImage) => {
    if (!image.path) {
        revokeIfBlob(image.preview_url || image.url);
        props.form.gallery_items = props.form.gallery_items.filter(
            (item) => item !== image,
        );
        const index = props.form.gallery_files?.findIndex(
            (file) => file.name === image.name,
        );
        if (index !== undefined && index >= 0) {
            props.form.gallery_files?.splice(index, 1);
        }
        return;
    }

    props.form.gallery_remove_paths = [
        ...(props.form.gallery_remove_paths ?? []),
        image.path,
    ];
    props.form.gallery_items = props.form.gallery_items.filter(
        (item) => item.path !== image.path,
    );
};

onBeforeUnmount(() => {
    stagedPreviews.value.forEach((url) => revokeIfBlob(url));
    props.form.gallery_items.forEach((item) => revokeIfBlob(item.preview_url || item.url));
});
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <ImagePlus class="h-4 w-4 text-slate-500" />
                        <h3 class="text-lg font-semibold text-slate-900">
                            Gallery Landing
                        </h3>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">
                        Gambar ini akan tampil pada menu gallery di landing page.
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
                @change="addFiles"
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

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                            <p class="truncate text-xs font-medium text-slate-700">
                                {{ file.name }}
                            </p>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-red-500"
                                @click="removeStagedFile(index)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center gap-2">
                    <ImagePlus class="h-4 w-4 text-slate-500" />
                    <p class="text-sm font-semibold text-slate-700">
                        Gambar tersimpan
                    </p>
                </div>

                <div
                    v-if="props.form.gallery_items.length"
                    class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="image in props.form.gallery_items"
                        :key="image.path || image.url"
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white"
                    >
                        <img
                            :src="image.preview_url || image.url"
                            :alt="image.name || 'Gallery'"
                            class="h-40 w-full object-cover"
                        />
                        <div class="flex items-center justify-between gap-3 p-3">
                            <p class="truncate text-xs font-medium text-slate-700">
                                {{ image.name || "Gallery image" }}
                            </p>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8"
                                @click="removeExistingImage(image)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500"
                >
                    Belum ada gambar gallery.
                </div>
            </div>
        </div>
    </div>
</template>
