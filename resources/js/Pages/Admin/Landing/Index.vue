<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import LandingContentTab from "./LandingContentTab.vue";
import LandingDataTab from "./LandingDataTab.vue";
import type { PageProps } from "@/types/inertia";

type LandingSetting = {
    landing_edisi_lomba_id: number | null;
    hero_badge: string | null;
    hero_title: string | null;
    hero_subtitle: string | null;
    about_text: string | null;
    cta_badge: string | null;
    cta_label: string | null;
    cta_url: string | null;
    faq_items: Array<{ q: string; a: string }>;
};

type LandingKategori = {
    id: number;
    nama: string;
    slug: string | null;
    deskripsi: string | null;
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
    kriteria: Array<{ nama: string; poin: number }>;
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
    cta_badge: "",
    cta_label: "",
    cta_url: "",
    faq_items: [],
});

const kategori = computed(() => page.props.kategoriLanding ?? []);
const bobotLanding = computed(() => page.props.bobotLanding ?? []);
const ketentuanLanding = computed(() => page.props.ketentuanLanding ?? []);
const templateProposal = computed(() => page.props.templateProposal ?? null);
const timeline = computed(() => page.props.timelineLanding ?? []);
const daftarEdisi = computed(() => page.props.daftarEdisi ?? []);
const edisiLanding = computed(() => page.props.edisiLanding ?? null);
const submitting = ref(false);
const activeTab = ref<"manual" | "data">("manual");
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
        cta_badge: data?.cta_badge ?? "",
        cta_label: data?.cta_label ?? "",
        cta_url: data?.cta_url ?? "",
        faq_items: data?.faq_items?.map((item) => ({ ...item })) ?? [],
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
    router.put(
        "/admin/landing",
        {
            ...form.value,
            faq_items: form.value.faq_items.filter(
                (item) => item.q.trim() || item.a.trim(),
            ),
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Pengaturan landing disimpan."),
            onError: () => toast.error("Gagal menyimpan pengaturan landing."),
            onFinish: () => (submitting.value = false),
        },
    );
};

defineOptions({
    layout: (h, page) => h(DashboardLayout, { title: "Landing" }, () => page),
});
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                    :class="
                        activeTab === 'manual'
                            ? 'border-slate-900 bg-white text-slate-900'
                            : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                    "
                    @click="activeTab = 'manual'"
                >
                    Konten Landing
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                    :class="
                        activeTab === 'data'
                            ? 'border-slate-900 bg-white text-slate-900'
                            : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
                    "
                    @click="activeTab = 'data'"
                >
                    Data Edisi
                </button>
            </div>
            <Button :disabled="submitting" @click="simpan">
                Simpan Pengaturan
            </Button>
        </div>

        <div
            v-if="activeTab === 'manual'"
            class="mx-auto w-full max-w-4xl space-y-6"
        >
            <LandingContentTab
                :form="form"
                :on-add-faq="addFaq"
                :on-remove-faq="removeFaq"
            />
        </div>

        <div v-else class="mx-auto w-full max-w-5xl space-y-6">
            <LandingDataTab
                v-model:edisiValue="edisiValue"
                v-model:activeTab="activeDataTab"
                :daftar-edisi="daftarEdisi"
                :kategori="kategori"
                :bobot-landing="bobotLanding"
                :ketentuan-landing="ketentuanLanding"
                :template-proposal="templateProposal"
                :timeline="timeline"
            />
        </div>
    </div>
</template>
