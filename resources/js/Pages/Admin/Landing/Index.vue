<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import LandingContentTab from "./LandingContentTab.vue";
import LandingCarouselTab from "./LandingCarouselTab.vue";
import LandingGalleryTab from "./LandingGalleryTab.vue";
import LandingDataTab from "./LandingDataTab.vue";
import type { PageProps } from "@/types/inertia";

type LandingSetting = {
    landing_edisi_lomba_id: number | null;
    hero_badge: string | null;
    hero_title: string | null;
    hero_subtitle: string | null;
    about_text: string | null;
    video_file_url?: string | null;
    video_file_name?: string | null;
    video_url: string | null;
    login_carousel_items: Array<{
        name: string | null;
        url?: string;
        preview_url?: string;
        path?: string;
    }>;
    gallery_items: Array<{
        name: string | null;
        url?: string;
        preview_url?: string;
        path?: string;
    }>;
    cta_badge: string | null;
    cta_label: string | null;
    cta_url: string | null;
    faq_items: Array<{ q: string; a: string }>;
    video_file?: File | null;
    login_carousel_files?: File[];
    login_carousel_remove_paths?: string[];
    gallery_files?: File[];
    gallery_remove_paths?: string[];
};

type LandingKategori = {
    id: number;
    nama: string;
    slug: string | null;
    deskripsi: string | null;
    icon_url?: string | null;
    aktif: boolean;
};

type LandingTimeline = {
    id: number;
    judul: string;
    mulai_pada: string | null;
    selesai_pada: string | null;
    is_tba: boolean;
    aktif: boolean;
};

type BobotLanding = {
    kategori_lomba_id: number;
    nama_kategori: string;
    icon_url?: string | null;
    kriteria: Array<{ nama: string; poin: number; deskripsi?: string }>;
};

type TemplateProposal = {
    nama: string | null;
    url: string;
};

type LandingEdition = {
    id: number;
    nama: string;
    tahun: number;
    status: string;
    aktif: boolean;
};

const page = usePage<
    PageProps & {
        landing?: LandingSetting | null;
        kategoriLanding?: LandingKategori[];
        bobotLanding?: BobotLanding[];
        ketentuanLanding?: string[];
        templateProposal?: TemplateProposal | null;
        timelineLanding?: LandingTimeline[];
        daftarEdisi?: LandingEdition[];
        edisiLanding?: LandingEdition | null;
    }
>();

const form = ref<LandingSetting>({
    landing_edisi_lomba_id: null,
    hero_badge: "",
    hero_title: "",
    hero_subtitle: "",
    about_text: "",
    video_file_url: "",
    video_url: "",
    login_carousel_items: [],
    gallery_items: [],
    cta_badge: "",
    cta_label: "",
    cta_url: "",
    faq_items: [],
    video_file: null,
    login_carousel_files: [],
    login_carousel_remove_paths: [],
    gallery_files: [],
    gallery_remove_paths: [],
});

const kategori = computed(() => page.props.kategoriLanding ?? []);
const bobotLanding = computed(() => page.props.bobotLanding ?? []);
const ketentuanLanding = computed(() => page.props.ketentuanLanding ?? []);
const templateProposal = computed(() => page.props.templateProposal ?? null);
const timeline = computed(() => page.props.timelineLanding ?? []);
const daftarEdisi = computed(() => page.props.daftarEdisi ?? []);
const edisiLanding = computed(() => page.props.edisiLanding ?? null);
const submitting = ref(false);
const activeTab = ref<"manual" | "carousel" | "gallery" | "data">("manual");
const activeDataTab = ref<
    "kategori" | "bobot" | "ketentuan" | "template" | "timeline"
>("kategori");

const hydrate = () => {
    const data = page.props.landing;
    form.value = {
        landing_edisi_lomba_id: data?.landing_edisi_lomba_id ?? null,
        hero_badge: data?.hero_badge ?? "",
        hero_title: data?.hero_title ?? "",
        hero_subtitle: data?.hero_subtitle ?? "",
        about_text: data?.about_text ?? "",
        video_file_url: data?.video_file_url ?? "",
        video_file_name: data?.video_file_name ?? "",
        video_url: data?.video_url ?? "",
        login_carousel_items: data?.login_carousel_items ?? [],
        gallery_items: data?.gallery_items ?? [],
        cta_badge: data?.cta_badge ?? "",
        cta_label: data?.cta_label ?? "",
        cta_url: data?.cta_url ?? "",
        faq_items: data?.faq_items?.map((item) => ({ ...item })) ?? [],
        video_file: null,
        login_carousel_files: [],
        login_carousel_remove_paths: [],
        gallery_files: [],
        gallery_remove_paths: [],
    };
};

hydrate();

watch(
    () => page.props.landing,
    () => {
        hydrate();
    },
);

const addFaq = () => {
    form.value.faq_items.push({ q: "", a: "" });
};

const removeFaq = (index: number) => {
    form.value.faq_items.splice(index, 1);
};

const edisiValue = computed({
    get: () =>
        form.value.landing_edisi_lomba_id
            ? String(form.value.landing_edisi_lomba_id)
            : "auto",
    set: (value: string) => {
        form.value.landing_edisi_lomba_id =
            value === "auto" ? null : Number(value);
    },
});

const simpan = () => {
    submitting.value = true;
    const payload = new FormData();
    payload.append("_method", "put");
    payload.append(
        "landing_edisi_lomba_id",
        form.value.landing_edisi_lomba_id
            ? String(form.value.landing_edisi_lomba_id)
            : "",
    );
    payload.append("hero_badge", form.value.hero_badge ?? "");
    payload.append("hero_title", form.value.hero_title ?? "");
    payload.append("hero_subtitle", form.value.hero_subtitle ?? "");
    payload.append("about_text", form.value.about_text ?? "");
    payload.append("video_url", form.value.video_url ?? "");
    form.value.login_carousel_remove_paths?.forEach((path, index) => {
        payload.append(`login_carousel_remove_paths[${index}]`, path);
    });
    payload.append("cta_badge", form.value.cta_badge ?? "");
    payload.append("cta_label", form.value.cta_label ?? "");
    payload.append("cta_url", form.value.cta_url ?? "");
    form.value.faq_items
        .filter((item) => item.q.trim() || item.a.trim())
        .forEach((item, index) => {
            payload.append(`faq_items[${index}][q]`, item.q);
            payload.append(`faq_items[${index}][a]`, item.a);
        });

    if (form.value.video_file) {
        payload.append("video_file", form.value.video_file);
    }

    form.value.login_carousel_files?.forEach((file) => {
        payload.append("login_carousel_files[]", file);
    });

    form.value.gallery_remove_paths?.forEach((path, index) => {
        payload.append(`gallery_remove_paths[${index}]`, path);
    });
    form.value.gallery_files?.forEach((file) => {
        payload.append("gallery_files[]", file);
    });

    router.post("/admin/landing", payload, {
        preserveScroll: true,
        onSuccess: () => toast.success("Pengaturan landing disimpan."),
        onError: () => toast.error("Gagal menyimpan pengaturan landing."),
        onFinish: () => (submitting.value = false),
    });
};

const tabButtonClass = (tab: "manual" | "carousel" | "gallery" | "data") =>
    activeTab.value === tab
        ? "border-indigo-200 bg-indigo-50 text-indigo-700"
        : "border-slate-200 bg-white text-slate-600 hover:border-slate-300";

const dataTabButtonClass = (
    tab: "kategori" | "bobot" | "ketentuan" | "template" | "timeline",
) =>
    activeDataTab.value === tab
        ? "border-indigo-200 bg-indigo-50 text-indigo-700"
        : "border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300";

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Landing" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <div class="sticky top-0 z-10 -mx-6 px-6 py-4 backdrop-blur">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                        :class="tabButtonClass('manual')"
                        @click="activeTab = 'manual'"
                    >
                        Konten Landing
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                        :class="tabButtonClass('data')"
                        @click="activeTab = 'data'"
                    >
                        Data Edisi
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                        :class="tabButtonClass('carousel')"
                        @click="activeTab = 'carousel'"
                    >
                        Carousel Login
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                        :class="tabButtonClass('gallery')"
                        @click="activeTab = 'gallery'"
                    >
                        Gallery
                    </button>
                </div>
                <Button :disabled="submitting" @click="simpan">
                    <Spinner v-if="submitting" class="h-4 w-4" />
                    <span v-else>Simpan Pengaturan</span>
                </Button>
            </div>
        </div>

        <div class="mx-auto w-full max-w-6xl space-y-6">
            <div v-if="activeTab === 'manual'">
                <LandingContentTab
                    :form="form"
                    :on-add-faq="addFaq"
                    :on-remove-faq="removeFaq"
                />
            </div>

            <div v-else-if="activeTab === 'carousel'">
                <LandingCarouselTab :form="form" />
            </div>

            <div v-else-if="activeTab === 'gallery'">
                <LandingGalleryTab :form="form" />
            </div>

            <div v-else>
                <LandingDataTab
                    v-model:edisiValue="edisiValue"
                    v-model:activeTab="activeDataTab"
                    :daftar-edisi="daftarEdisi"
                    :kategori="kategori"
                    :bobot-landing="bobotLanding"
                    :ketentuan-landing="ketentuanLanding"
                    :template-proposal="templateProposal"
                    :timeline="timeline"
                    :tab-class="dataTabButtonClass"
                />
            </div>
        </div>
    </div>
</template>
