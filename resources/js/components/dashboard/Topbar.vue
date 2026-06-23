<script setup lang="ts">
import { usePage, router } from "@inertiajs/vue3";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import {
    ListIndentDecrease,
    ListIndentIncrease,
    Bell,
    Search,
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
import { PopoverTrigger } from "@/components/ui/popover";

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import GlobalCommandPalette from "@/components/dashboard/GlobalCommandPalette.vue";
import type { PageProps } from "@/types/inertia";
import type { RoleKey } from "@/types/menu";

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
const searchOpen = ref(false);
const searchQuery = ref("");
const notificationOpen = ref(false);
const allowedRoles: RoleKey[] = ["admin", "juri", "peserta"];
const searchRole = computed<RoleKey | null>(() =>
    allowedRoles.includes(currentRole.value as RoleKey)
        ? (currentRole.value as RoleKey)
        : null,
);
const flash = computed(() => page.props.flash ?? {});
const notifications = computed(() => {
    const items: Array<{
        title: string;
        description: string;
        tone: "success" | "error" | "welcome" | "info";
    }> = [];

    if (flash.value.welcome) {
        items.push({
            title: "Selamat datang",
            description: flash.value.welcome,
            tone: "welcome",
        });
    }

    if (flash.value.success) {
        items.push({
            title: "Berhasil",
            description: flash.value.success,
            tone: "success",
        });
    }

    if (flash.value.error) {
        items.push({
            title: "Perhatian",
            description: flash.value.error,
            tone: "error",
        });
    }

    if (flash.value.nominasi) {
        const nominasiItems = Array.isArray(flash.value.nominasi)
            ? flash.value.nominasi
            : [flash.value.nominasi];

        nominasiItems
            .filter(Boolean)
            .forEach((namaKarya: string) => {
                items.push({
                    title: "Pengumuman Nominasi",
                    description: `Selamat! Karyamu "${namaKarya}" lolos masuk nominasi.`,
                    tone: "info",
                });
            });
    }

    return items;
});

const notificationToneClass = (tone: "success" | "error" | "welcome" | "info") => {
    if (tone === "success") return "bg-emerald-50 text-emerald-700";
    if (tone === "error") return "bg-rose-50 text-rose-700";
    if (tone === "info") return "bg-indigo-50 text-indigo-700";
    return "bg-indigo-50 text-indigo-700";
};

const openSearch = () => {
    if (!searchRole.value) return;
    searchOpen.value = true;
};

const handleGlobalShortcut = (event: KeyboardEvent) => {
    const isMac = navigator.platform.toUpperCase().includes("MAC");
    const shortcutPressed = isMac ? event.metaKey : event.ctrlKey;

    if (!shortcutPressed || event.key.toLowerCase() !== "k") return;

    event.preventDefault();
    openSearch();
};

onMounted(() => {
    window.addEventListener("keydown", handleGlobalShortcut);
    if (flash.value.welcome) {
        notificationOpen.value = true;
    }
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleGlobalShortcut);
});

watch(searchOpen, (value) => {
    if (!value) searchQuery.value = "";
});

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

            <GlobalCommandPalette
                v-model:open="searchOpen"
                v-model:query="searchQuery"
                :role="searchRole"
            >
                <template #trigger>
                    <PopoverTrigger as-child>
                        <button
                            v-if="searchRole"
                            type="button"
                            class="hidden items-center gap-2 rounded-sm border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-50 sm:inline-flex"
                            @click="openSearch"
                        >
                            <Search class="h-4 w-4 text-slate-500" />
                            <span>Cari menu...</span>
                            <span
                                class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[11px] font-semibold text-slate-500"
                            >
                                Ctrl K
                            </span>
                        </button>
                    </PopoverTrigger>
                </template>
            </GlobalCommandPalette>

            <!-- Notification -->
            <DropdownMenu v-model:open="notificationOpen">
                <DropdownMenuTrigger as-child>
                    <button
                        class="relative hidden rounded-md p-2 transition-colors duration-200 hover:bg-slate-100 sm:inline-flex"
                        type="button"
                    >
                        <Bell class="w-4 h-4 text-slate-600" />
                        <span
                            v-if="notifications.length"
                            class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white"
                        />
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    align="end"
                    class="w-80 overflow-hidden rounded-md border bg-white p-0 shadow-md"
                >
                    <div class="border-b px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">
                            Notifikasi
                        </p>
                    </div>

                    <div
                        v-if="notifications.length"
                        class="max-h-80 overflow-y-auto p-2"
                    >
                        <div
                            v-for="(item, index) in notifications"
                            :key="`${item.title}-${index}`"
                            class="rounded-lg px-3 py-3 transition-colors hover:bg-slate-50"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                    :class="notificationToneClass(item.tone)"
                                >
                                    <Bell class="h-4 w-4" />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ item.title }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="px-4 py-8 text-center text-sm text-slate-500"
                    >
                        Belum ada notifikasi.
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

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
                    class="w-60 rounded-md border bg-white shadow-md p-0 overflow-hidden"
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
