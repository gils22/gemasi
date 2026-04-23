<script setup lang="ts">
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import SuperadminLayout from "@/Layouts/SuperadminLayout.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Input } from "@/components/ui/input";
import { Mail, User2 } from "lucide-vue-next";

const page = usePage<{
    account: {
        name?: string | null;
        email?: string | null;
        avatar?: string | null;
    };
}>();

const account = computed(() => page.props.account);
const displayName = computed(() => account.value?.name?.trim() || "-");
const displayEmail = computed(() => account.value?.email?.trim() || "-");

const avatarSrc = computed(() => {
    const avatar = account.value?.avatar;
    if (!avatar) return "";

    // Keep Google avatar format and enlarge size when possible.
    if (avatar.includes("googleusercontent.com")) {
        return avatar
            .replace(/=s\d+-c/, "=s240-c")
            .replace(/(\?|&)sz=\d+/g, "$1sz=240");
    }

    if (avatar.startsWith("http://") || avatar.startsWith("https://")) {
        return avatar;
    }

    return `/storage/${avatar}`;
});

const roleFromPath = computed(() => {
    const path = (page.url ?? "").split("?")[0].split("#")[0];
    if (path.startsWith("/admin")) return "admin";
    if (path.startsWith("/juri")) return "juri";
    if (path.startsWith("/peserta")) return "peserta";
    return "user";
});

const profileTitle = computed(() => {
    if (roleFromPath.value === "admin") return "Profil Admin";
    if (roleFromPath.value === "juri") return "Profil Juri";
    if (roleFromPath.value === "peserta") return "Profil Peserta";
    return "Profil";
});

const avatarForm = useForm<{ avatar: File | null }>({
    avatar: null,
});

const avatarInputRef = ref<HTMLInputElement | null>(null);
const openAvatarPicker = () => {
    avatarInputRef.value?.click();
};

const onPickAvatar = (e: Event) => {
    const target = e.target as HTMLInputElement | null;
    const file = target?.files?.[0] ?? null;
    if (!file) return;

    avatarForm.avatar = file;
    avatarForm.post("/akun/avatar", {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => toast.success("Foto profil berhasil diperbarui."),
        onError: () => toast.error("Gagal memperbarui foto profil."),
    });

    // allow re-selecting the same file
    if (target) target.value = "";
};

defineOptions({
    layout: (h, pageVNode) => {
        const path =
            typeof window !== "undefined" ? window.location.pathname : "";
        const Layout = path.startsWith("/superadmin")
            ? SuperadminLayout
            : DashboardLayout;

        return h(Layout, { title: "Informasi Akun" }, () => pageVNode);
    },
});
</script>

<template>
    <div class="mx-auto max-w-4xl">
        <div class="rounded-2xl border bg-white shadow-sm">
            <div class="px-6 py-6 sm:px-8">
                <!-- Profile picture row -->
                <div class="flex items-start gap-5">
                    <Avatar class="h-32 w-32 border bg-white shadow-sm ring-8 ring-sky-50">
                        <AvatarImage :src="avatarSrc" :alt="displayName" />
                        <AvatarFallback class="text-3xl">
                            {{ displayName.charAt(0).toUpperCase() }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-700">
                            {{ profileTitle }}
                        </p>
                        <div class="mt-2">
                            <input
                                id="avatarUpload"
                                ref="avatarInputRef"
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                class="hidden"
                                @change="onPickAvatar"
                            />
                            <button
                                type="button"
                                class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:pointer-events-none disabled:opacity-50"
                                :disabled="avatarForm.processing"
                                @click="openAvatarPicker"
                            >
                                {{ avatarForm.processing ? "Mengunggah..." : "Unggah Foto" }}
                            </button>
                            <p class="mt-2 text-xs text-slate-500">
                                JPG/PNG/WEBP, maks 2MB.
                            </p>
                        </div>
                        <p v-if="avatarForm.errors.avatar" class="mt-2 text-xs text-rose-600">
                            {{ avatarForm.errors.avatar }}
                        </p>
                    </div>
                </div>

                <div class="my-6 border-t" />

                <!-- Fields (read-only) -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
                            <User2 class="h-4 w-4 text-slate-400" />
                            Nama
                        </label>
                        <Input :model-value="displayName" disabled class="bg-slate-50" />
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
                            <Mail class="h-4 w-4 text-slate-400" />
                            Email
                        </label>
                        <Input :model-value="displayEmail" disabled class="bg-slate-50" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
