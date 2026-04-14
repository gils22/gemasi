<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { ArrowLeft } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type Lampiran = {
    id: number;
    nama: string;
    deskripsi: string | null;
    url: string;
};

type DetailKarya = {
    id: number;
    nama_karya: string;
    nama_kategori: string;
    peserta: {
        name: string | null;
        email: string | null;
        avatar: string | null;
    };
    anggota_tim: Array<{
        nama?: string;
        nim?: string;
        email?: string;
        peran?: string;
    }>;
    lampiran: Lampiran[];
};

const page = usePage<
    PageProps & { karya: DetailKarya; gemasiAktifLabel: string }
>();

const karya = page.props.karya;

const backToList = () => {
    const prefix = page.url.startsWith("/juri") ? "/juri" : "/admin";
    router.get(`${prefix}/penjurian/nominasi`);
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Detail Penjurian" }, () => page),
});
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-xl border bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="backToList">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <Badge variant="secondary">
                        {{ page.props.gemasiAktifLabel }}
                    </Badge>
                </div>
            </div>
            <h1 class="mt-3 text-xl font-semibold text-slate-900">
                {{ karya.nama_karya }}
            </h1>
            <p class="text-sm text-slate-500">{{ karya.nama_kategori }}</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border bg-white p-4">
                <p class="text-xs text-slate-500">Peserta</p>
                <div class="mt-2 flex items-center gap-3">
                    <Avatar class="h-9 w-9">
                        <AvatarImage
                            :src="
                                karya.peserta.avatar ??
                                `https://ui-avatars.com/api/?name=${encodeURIComponent(karya.peserta.name ?? 'P')}&background=2563eb&color=fff`
                            "
                        />
                        <AvatarFallback>
                            {{
                                (karya.peserta.name ?? "P")
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ karya.peserta.name ?? "-" }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ karya.peserta.email ?? "-" }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-4">
                <p class="text-xs text-slate-500">Anggota Tim</p>
                <div class="mt-2 space-y-2">
                    <div
                        v-for="(anggota, idx) in karya.anggota_tim"
                        :key="idx"
                        class="grid grid-cols-1 md:grid-cols-[1.2fr_1.5fr_2fr_1fr] gap-2 text-sm text-slate-800"
                    >
                        <span>{{ anggota.nim ?? "-" }}</span>
                        <span class="font-medium">{{ anggota.nama ?? "-" }}</span>
                        <span class="text-slate-500">{{ anggota.email ?? "-" }}</span>
                        <span class="text-slate-500">{{ anggota.peran ?? "-" }}</span>
                    </div>
                    <p v-if="!karya.anggota_tim?.length" class="text-sm text-slate-500">
                        Belum ada anggota tim.
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs text-slate-500 mb-2">Lampiran Karya</p>
            <div v-if="karya.lampiran.length" class="space-y-2">
                <a
                    v-for="item in karya.lampiran"
                    :key="item.id"
                    :href="item.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block rounded-md border px-3 py-2 text-sm text-blue-700 hover:bg-slate-50"
                >
                    <p class="font-medium">{{ item.nama }}</p>
                    <p class="text-slate-500">{{ item.deskripsi || "-" }}</p>
                </a>
            </div>
            <p v-else class="text-sm text-slate-500">Tidak ada lampiran.</p>
        </div>
    </div>
</template>
