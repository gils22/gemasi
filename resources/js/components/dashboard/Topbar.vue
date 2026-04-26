<script setup lang="ts">
import { usePage, router } from "@inertiajs/vue3";
import {
    ListIndentDecrease,
    ListIndentIncrease,
    Bell,
    ChevronDown,
    CornerUpLeft,
    LogOut,
    Shield,
    User,
} from "lucide-vue-next";

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import type { PageProps } from "@/types/inertia";
import { computed, ref, watch } from "vue";

const props = defineProps<{
    title?: string;
    collapsed?: boolean;
}>();

const emit = defineEmits<{
    (e: "toggle-sidebar"): void;
}>();
const page = usePage<PageProps>();
const currentUser = computed(() => page.props.auth?.user ?? null);
const currentPath = computed(() => page.url.split("?")[0].split("#")[0]);
const roleFromPath = computed(() => {
    if (currentPath.value.startsWith("/admin")) return "admin";
    if (currentPath.value.startsWith("/juri")) return "juri";
    if (currentPath.value.startsWith("/peserta")) return "peserta";
    return null;
});
const currentRole = computed(
    () => page.props.auth?.role?.toLowerCase() ?? roleFromPath.value,
);
const isSuperadmin = computed(() => page.props.auth?.is_superadmin === true);
const isImpersonating = computed(() => page.props.auth?.impersonating === true);
const originalSuperadmin = computed(
    () => page.props.auth?.superadmin_original ?? null,
);
const avatarError = ref(false);
const displayName = computed(() => {
    const name = currentUser.value?.name?.trim();
    if (name) return name;

    const email = currentUser.value?.email?.trim();
    if (email && email.includes("@")) return email.split("@")[0];

    return "-";
});
const displayEmail = computed(() => currentUser.value?.email?.trim() || "-");

const roleLabel = computed(() => {
    if (!roleFromPath.value && isSuperadmin.value) return "Superadmin";
    if (currentRole.value === "admin") return "Admin";
    if (currentRole.value === "juri") return "Juri";
    if (currentRole.value === "peserta") return "Peserta";
    return "-";
});
const avatarSrc = computed(() => {
    if (avatarError.value) return "";

    const avatar = currentUser.value?.avatar;
    if (!avatar) return "";

    // Keep Google avatar format and enlarge size when possible.
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

watch(
    () => currentUser.value?.avatar,
    () => {
        avatarError.value = false;
    },
);
const fallbackTitle = computed(() => {
    if (currentPath.value.startsWith("/admin/juri")) return "Juri";
    if (currentPath.value.startsWith("/admin/peserta")) return "Peserta";
    if (currentPath.value.startsWith("/admin")) return "Dashboard";
    if (currentPath.value.startsWith("/juri")) return "Dashboard";
    if (currentPath.value.startsWith("/peserta")) return "Dashboard";
    return "Dashboard";
});
const displayTitle = computed(() => props.title?.trim() || fallbackTitle.value);

const logout = () => {
    router.post("/logout");
};

const stopImpersonate = () => {
    router.post("/superadmin/stop-impersonate");
};

const openAccountInfo = () => {
    if (isSuperadmin.value && !roleFromPath.value) {
        router.get("/superadmin/akun");
        return;
    }

    if (
        !currentRole.value ||
        !["admin", "juri", "peserta"].includes(currentRole.value)
    )
        return;
    router.get(`/${currentRole.value}/akun`);
};

const openChooseRole = () => {
    router.get("/superadmin");
};
</script>

<template>
    <header
        class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between"
    >
        <!-- ===================== -->
        <!-- LEFT SECTION -->
        <!-- ===================== -->
        <div class="min-w-0 flex items-center gap-2 sm:gap-4">
            <!-- Sidebar Toggle -->
            <button
                @click="emit('toggle-sidebar')"
                class="p-2 rounded-md hover:bg-slate-100 transition-colors duration-200"
            >
                <ListIndentIncrease
                    v-if="props.collapsed"
                    class="w-4 h-4 text-slate-700"
                />
                <ListIndentDecrease v-else class="w-4 h-4 text-slate-700" />
            </button>

            <!-- Dynamic Header -->
            <h1
                class="truncate text-base sm:text-lg font-semibold text-slate-800"
            >
                {{ displayTitle }}
            </h1>
        </div>

        <!-- ===================== -->
        <!-- RIGHT SECTION -->
        <!-- ===================== -->
        <div class="flex items-center gap-1 sm:gap-4">
            <!-- Optional Actions from Page -->
            <slot name="actions" />

            <!-- Notification -->
            <button
                class="hidden sm:inline-flex rounded-md p-2 hover:bg-slate-100 transition-colors duration-200"
            >
                <Bell class="w-4 h-4 text-slate-600" />
            </button>

            <!-- PROFILE DROPDOWN -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        class="flex items-center gap-2 sm:gap-3 hover:bg-slate-100 p-2 rounded-lg transition-colors duration-200"
                    >
                        <Avatar class="h-6 w-6">
                            <AvatarImage
                                :src="avatarSrc"
                                :alt="currentUser?.name ?? 'User'"
                                @error="avatarError = true"
                            />
                            <AvatarFallback>
                                {{ displayName.charAt(0).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="hidden sm:block text-left">
                            <p class="text-sm font-semibold text-slate-700">
                                {{ displayName }}
                            </p>
                            <p class="text-xs text-slate-400 capitalize">
                                {{ roleLabel }}
                            </p>
                        </div>

                        <ChevronDown class="w-4 h-4 text-slate-400" />
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    align="end"
                    class="w-60 rounded-xl border bg-white shadow-md p-0 overflow-hidden"
                >
                    <!-- Header -->
                    <div class="px-4 py-3 flex items-center gap-3">
                        <Avatar class="h-6 w-6">
                            <AvatarImage
                                :src="avatarSrc"
                                :alt="currentUser?.name ?? 'User'"
                                @error="avatarError = true"
                            />
                            <AvatarFallback>
                                {{ displayName.charAt(0).toUpperCase() }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="min-w-0">
                            <p
                                class="text-sm font-semibold text-slate-800 truncate"
                            >
                                {{ displayName }}
                            </p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ displayEmail }}
                            </p>
                        </div>
                    </div>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        v-if="isImpersonating"
                        class="gap-2 px-4 py-2 text-sm"
                        @click="stopImpersonate"
                    >
                        <CornerUpLeft class="w-4 h-4 text-slate-500" />
                        Kembali ke Superadmin
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="isSuperadmin"
                        class="gap-2 px-4 py-2 text-sm"
                        @click="openChooseRole"
                    >
                        <Shield class="w-4 h-4 text-slate-500" />
                        Pilih Peran
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        class="gap-2 px-4 py-2 text-sm"
                        @click="openAccountInfo"
                    >
                        <User class="w-4 h-4 text-slate-500" />
                        Informasi Akun
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        @click="logout"
                        class="gap-2 px-4 py-2 text-sm text-red-600 focus:text-red-600"
                    >
                        <LogOut class="w-4 h-4" />
                        Keluar
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
