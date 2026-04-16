<script setup lang="ts">
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Eye, EyeOff, ShieldCheck } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

const page = usePage<
    PageProps & {
        account: {
            name: string | null;
            email: string | null;
            avatar?: string | null;
            has_password: boolean;
        };
        requirePasswordSetup?: boolean;
        flash?: {
            success?: string | null;
        };
    }
>();

const account = computed(() => page.props.account);
const requirePasswordSetup = computed(
    () => page.props.requirePasswordSetup === true,
);
const role = computed(() => page.props.auth?.role ?? "juri");
const isPeserta = computed(() => role.value === "peserta");
const avatarError = ref(false);
const pesertaAvatar = computed(() => {
    if (avatarError.value) return "";

    const avatar = account.value.avatar ?? page.props.auth?.user?.avatar ?? "";
    if (!avatar) return "";

    if (avatar.includes("googleusercontent.com")) {
        return avatar
            .replace(/=s\d+-c/, "=s200-c")
            .replace(/(\?|&)sz=\d+/g, "$1sz=200");
    }

    if (avatar.startsWith("http://") || avatar.startsWith("https://")) {
        return avatar;
    }

    return `/storage/${avatar}`;
});
const errors = computed(() => page.props.errors ?? {});
const flashSuccess = computed(() => page.props.flash?.success ?? "");
const currentPassword = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const showCurrent = ref(false);
const showPassword = ref(false);
const showConfirmation = ref(false);
const saving = ref(false);

const submit = () => {
    saving.value = true;
    router.patch(
        `/${role.value}/akun/password`,
        {
            current_password: currentPassword.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
            },
            onSuccess: () => {
                currentPassword.value = "";
                password.value = "";
                passwordConfirmation.value = "";

                if (requirePasswordSetup.value) {
                    router.get(`/${role.value}`);
                }
            },
        },
    );
};

defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Informasi Akun" }, () => page),
});
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                    <ShieldCheck class="h-7 w-7" />
                </div>
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold text-slate-900">
                        Informasi Akun
                    </h1>
                    <p class="text-sm text-slate-500">
                        {{
                            isPeserta
                                ? "Lihat informasi akun yang digunakan untuk masuk ke GEMASI."
                                : "Kelola password akun untuk login form selain Google."
                        }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="isPeserta"
            class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
        >
            <div
                class="flex items-center gap-5"
            >
                <div
                    class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-50 shadow-sm"
                >
                    <img
                        v-if="pesertaAvatar"
                        :src="pesertaAvatar"
                        :alt="account.name ?? 'Peserta'"
                        class="h-full w-full object-cover"
                        @error="avatarError = true"
                    />
                    <span
                        v-else
                        class="text-2xl font-semibold text-slate-500"
                    >
                        {{ (account.name ?? account.email ?? "P").charAt(0).toUpperCase() }}
                    </span>
                </div>
                <div class="min-w-0 space-y-1">
                    <p class="text-lg font-semibold text-slate-900">
                        {{ account.name ?? "-" }}
                    </p>
                    <p class="text-sm text-slate-500 break-all">
                        {{ account.email ?? "-" }}
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-[320px_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Nama
                    </p>
                    <p class="mt-1 text-sm font-medium text-slate-800">
                        {{ account.name ?? "-" }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Email
                    </p>
                    <p class="mt-1 text-sm font-medium text-slate-800 break-all">
                        {{ account.email ?? "-" }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        {{ isPeserta ? "Metode Login" : "Status Password" }}
                    </p>
                    <div class="mt-2">
                        <Badge
                            :class="isPeserta
                                ? 'bg-blue-100 text-blue-700'
                                : account.has_password
                                  ? 'bg-emerald-100 text-emerald-700'
                                  : 'bg-amber-100 text-amber-700'"
                        >
                            {{
                                isPeserta
                                    ? 'Google Student'
                                    : account.has_password
                                    ? 'Sudah dibuat'
                                    : 'Belum dibuat'
                            }}
                        </Badge>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5"
            >
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ isPeserta
                            ? 'Informasi Login'
                            : account.has_password
                              ? 'Ubah Password'
                              : 'Buat Password' }}
                    </h2>
                    <p v-if="isPeserta" class="text-sm text-slate-500">
                        Akun peserta menggunakan email Google Student yang
                        terhubung saat login. Untuk saat ini peserta tetap masuk
                        menggunakan Google.
                    </p>
                    <p v-else-if="requirePasswordSetup" class="text-sm text-amber-600">
                        Sebelum lanjut, buat password dulu agar nanti bisa login
                        dengan email dan password.
                    </p>
                    <p v-else class="text-sm text-slate-500">
                        Password ini hanya untuk login form. Login Google tetap
                        bisa digunakan.
                    </p>
                </div>

                <div
                    v-if="flashSuccess"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                >
                    {{ flashSuccess }}
                </div>

                <div v-if="account.has_password" class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">
                        Password Saat Ini
                    </label>
                    <div class="relative">
                        <Input
                            v-model="currentPassword"
                            :type="showCurrent ? 'text' : 'password'"
                            class="pr-10"
                            placeholder="Masukkan password saat ini"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                            @click="showCurrent = !showCurrent"
                        >
                            <Eye v-if="!showCurrent" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p
                        v-if="errors.current_password"
                        class="text-sm text-rose-600"
                    >
                        {{ errors.current_password }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">
                        Password Baru
                    </label>
                    <div class="relative">
                        <Input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="pr-10"
                            placeholder="Minimal 8 karakter"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                            @click="showPassword = !showPassword"
                        >
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="errors.password" class="text-sm text-rose-600">
                        {{ errors.password }}
                    </p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">
                        Konfirmasi Password
                    </label>
                    <div class="relative">
                        <Input
                            v-model="passwordConfirmation"
                            :type="showConfirmation ? 'text' : 'password'"
                            class="pr-10"
                            placeholder="Ulangi password baru"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                            @click="showConfirmation = !showConfirmation"
                        >
                            <Eye v-if="!showConfirmation" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button :disabled="saving" @click="submit">
                        {{
                            saving
                                ? 'Menyimpan...'
                                : account.has_password
                                  ? 'Simpan Password Baru'
                                  : 'Buat Password'
                        }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
