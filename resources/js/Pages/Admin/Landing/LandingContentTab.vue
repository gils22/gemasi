<script setup lang="ts">
import { Plus, Trash2 } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";

type LandingForm = {
    hero_badge: string | null;
    hero_title: string | null;
    hero_subtitle: string | null;
    about_text: string | null;
    video_file_url?: string | null;
    video_file_name?: string | null;
    video_url: string | null;
    cta_badge: string | null;
    cta_label: string | null;
    cta_url: string | null;
    faq_items: Array<{ q: string; a: string }>;
    video_file?: File | null;
};

const props = defineProps<{
    form: LandingForm;
    onAddFaq: () => void;
    onRemoveFaq: (index: number) => void;
}>();
</script>

<template>
    <Card class="rounded-2xl border-slate-200">
        <CardHeader class="space-y-1">
            <CardTitle>Konten Landing</CardTitle>
        </CardHeader>
        <CardContent class="space-y-5">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-xs text-slate-500">Hero Badge</label>
                    <Input
                        v-model="props.form.hero_badge"
                        placeholder="GEMASI 2026"
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-slate-500">Hero Title</label>
                    <Input
                        v-model="props.form.hero_title"
                        placeholder="Beyond Innovation"
                    />
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs text-slate-500">Hero Subtitle</label>
                <Input
                    v-model="props.form.hero_subtitle"
                    placeholder="Gelar Karya Mahasiswa Sistem Informasi Universitas Amikom Yogyakarta"
                />
            </div>
            <div class="space-y-1">
                <label class="text-xs text-slate-500">Tentang</label>
                <textarea
                    v-model="props.form.about_text"
                    rows="5"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Deskripsi singkat tentang GEMASI"
                />
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-xs text-slate-500">
                        Upload Video Motion Graphic
                    </label>
                    <input
                        type="file"
                        accept="video/mp4,video/webm,video/ogg,video/*"
                        class="block w-full rounded-md border border-input bg-background text-sm text-slate-700 file:mr-4 file:cursor-pointer file:rounded-md file:border-slate-200 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-black hover:file:bg-slate-50"
                        @change="
                            props.form.video_file =
                                ($event.target as HTMLInputElement)
                                    .files?.[0] ?? null
                        "
                    />
                    <p
                        v-if="props.form.video_file_url"
                        class="text-xs text-slate-500"
                    >
                        Video saat ini:
                        <a
                            href="/admin/landing/video/preview"
                            target="_blank"
                            class="text-indigo-600 hover:underline"
                        >
                            {{ props.form.video_file_name || "Preview video" }}
                        </a>
                    </p>
                    <p class="text-xs text-slate-500">
                        Upload file video untuk dipakai di landing. Jika diisi
                        juga link YouTube, file upload tetap diprioritaskan.
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="text-xs text-slate-500">
                        Atau link video / YouTube
                    </label>
                    <Input
                        v-model="props.form.video_url"
                        placeholder="Link YouTube atau file video, contoh https://..."
                    />
                    <p class="text-xs text-slate-500">
                        Bisa diisi link YouTube atau file video langsung.
                    </p>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-xs text-slate-500">CTA Badge</label>
                    <Input
                        v-model="props.form.cta_badge"
                        placeholder="GEMASI 2026 - Beyond Innovation"
                    />
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-slate-500">CTA Label</label>
                    <Input
                        v-model="props.form.cta_label"
                        placeholder="Daftar Sekarang"
                    />
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs text-slate-500">CTA URL</label>
                <Input v-model="props.form.cta_url" placeholder="/login" />
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-900">FAQ</p>
                    <Button variant="outline" size="sm" @click="props.onAddFaq">
                        <Plus class="h-4 w-4" />
                        Tambah
                    </Button>
                </div>
                <div v-if="props.form.faq_items.length" class="space-y-3">
                    <div
                        v-for="(item, idx) in props.form.faq_items"
                        :key="`faq-${idx}`"
                        class="rounded-lg border border-slate-200 bg-white p-3 space-y-2"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-500">
                                FAQ #{{ idx + 1 }}
                            </p>
                            <TooltipProvider :delay-duration="150">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-slate-500 hover:text-red-600"
                                            @click="props.onRemoveFaq(idx)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent side="top">
                                        Hapus FAQ
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                        <Input v-model="item.q" placeholder="Pertanyaan" />
                        <textarea
                            v-model="item.a"
                            rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Jawaban"
                        />
                    </div>
                </div>
                <p v-else class="text-xs text-slate-500">
                    Belum ada FAQ. Klik Tambah FAQ untuk membuatnya.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
