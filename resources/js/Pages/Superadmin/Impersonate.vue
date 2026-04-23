<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import SuperadminLayout from "@/Layouts/SuperadminLayout.vue";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { LogIn } from "lucide-vue-next";
import type { PageProps } from "@/types/inertia";

type UserItem = {
    id: number;
    name: string | null;
    email: string | null;
    avatar: string | null;
};

defineOptions({
    layout: (h, page) =>
        h(SuperadminLayout, { title: "Superadmin" }, () => page),
});

const page = usePage<
    PageProps & {
        roleTarget: "admin" | "juri" | "peserta";
        users: UserItem[];
    }
>();

const roleTarget = ref<"admin" | "juri" | "peserta">(page.props.roleTarget);
const selectedUserId = ref<string>("");
const daftarEdisi = computed(() => page.props.edisi?.daftar ?? []);
const edisiAktif = computed(() => page.props.edisi?.aktif ?? null);
const edisiDipilih = ref<string>(
    edisiAktif.value ? String(edisiAktif.value.id) : "",
);

watch(
    () => page.props.roleTarget,
    (val) => {
        roleTarget.value = val;
        selectedUserId.value = "";
    },
);

watch(
    edisiAktif,
    (val) => {
        edisiDipilih.value = val ? String(val.id) : "";
    },
    { immediate: true },
);

const users = computed(() => page.props.users ?? []);

const roleLabel = computed(() => {
    if (roleTarget.value === "admin") return "Admin";
    if (roleTarget.value === "juri") return "Juri";
    return "Peserta";
});

const avatarSrc = (u: UserItem) => {
    const raw = (u.avatar ?? "").trim();
    if (!raw) return "";
    if (raw.includes("googleusercontent.com")) {
        return raw
            .replace(/=s\\d+-c/, "=s200-c")
            .replace(/(\\?|&)sz=\\d+/g, "$1sz=200");
    }
    if (raw.startsWith("http://") || raw.startsWith("https://")) return raw;
    return `/storage/${raw}`;
};

const displayName = (u: UserItem) => {
    const name = (u.name ?? "").trim();
    if (name) return name;
    const email = (u.email ?? "").trim();
    if (email && email.includes("@")) return email.split("@")[0];
    return "-";
};

const selectedUser = computed(() => {
    const id = Number(selectedUserId.value || 0);
    if (!id) return null;
    return users.value.find((u) => u.id === id) ?? null;
});

const changeRole = (val: unknown) => {
    const next = String(val ?? "");
    if (!["admin", "juri", "peserta"].includes(next)) return;
    router.get(
        "/superadmin",
        { role: next },
        { preserveScroll: true, preserveState: false, replace: true },
    );
};

const impersonate = () => {
    const id = Number(selectedUserId.value || 0);
    if (!id) return;
    router.post(
        "/superadmin/impersonate",
        { role: roleTarget.value, user_id: id },
        { preserveScroll: true },
    );
};

const gantiEdisi = (value: unknown) => {
    const v = String(value ?? "");
    if (!v) return;
    router.post(
        "/konteks/edisi",
        { edisi_id: Number(v) },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
        },
    );
};
</script>

<template>
    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold text-slate-900">
                        Masuk Sebagai
                    </h1>
                    <p class="text-sm text-slate-500">
                        Pilih Edisi, Role, dan User.
                    </p>
                </div>

                <div class="w-full lg:w-[360px] space-y-2">
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Edisi GEMASI
                    </p>
                    <Select
                        :model-value="edisiDipilih"
                        @update:model-value="gantiEdisi"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih edisi" />
                        </SelectTrigger>
                        <SelectContent
                            side="bottom"
                            :avoid-collisions="false"
                            :side-offset="8"
                            class="max-h-72"
                        >
                            <div
                                v-if="!daftarEdisi.length"
                                class="px-3 py-2 text-sm text-slate-500"
                            >
                                Tidak ada data
                            </div>
                            <SelectItem
                                v-else
                                v-for="e in daftarEdisi"
                                :key="e.id"
                                :value="String(e.id)"
                            >
                                {{ e.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4"
            >
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-slate-500">
                        Pilih Role
                    </p>
                    <Select
                        :model-value="roleTarget"
                        @update:model-value="changeRole"
                    >
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="admin">Admin</SelectItem>
                            <SelectItem value="juri">Juri</SelectItem>
                            <SelectItem value="peserta">Peserta</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <p class="text-xs font-semibold text-slate-500">
                        Pilih {{ roleLabel }}
                    </p>
                    <Select v-model="selectedUserId">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih user" />
                        </SelectTrigger>
                        <SelectContent
                            side="bottom"
                            :avoid-collisions="false"
                            :side-offset="8"
                            class="max-h-72"
                        >
                            <div
                                v-if="!users.length"
                                class="px-3 py-2 text-sm text-slate-500"
                            >
                                Tidak ada data
                            </div>
                            <SelectItem
                                v-else
                                v-for="u in users"
                                :key="u.id"
                                :value="String(u.id)"
                            >
                                <span class="font-medium">{{
                                    displayName(u)
                                }}</span>
                                <span class="text-slate-500">
                                    - {{ u.email ?? "-" }}
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Button
                    class="w-full"
                    :disabled="!selectedUserId"
                    @click="impersonate"
                >
                    Masuk
                </Button>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <p class="text-xs font-semibold uppercase text-slate-500">
                    Preview
                </p>

                <div v-if="selectedUser" class="mt-4 flex items-center gap-4">
                    <Avatar class="h-12 w-12">
                        <AvatarImage
                            :src="avatarSrc(selectedUser)"
                            :alt="displayName(selectedUser)"
                        />
                        <AvatarFallback>
                            {{
                                displayName(selectedUser)
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                        <p
                            class="text-base font-semibold text-slate-900 truncate"
                        >
                            {{ displayName(selectedUser) }}
                        </p>
                        <p class="text-sm text-slate-500 truncate">
                            {{ selectedUser.email ?? "-" }}
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500"
                >
                    Pilih user untuk melihat preview.
                </div>
            </div>
        </div>
    </section>
</template>
