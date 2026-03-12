<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { roleMenus } from "@/config/menu";
import type { PageProps } from "@/types/inertia";
import type { MenuItem, RoleKey } from "@/types/menu";
import { ChevronDown } from "lucide-vue-next";

import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { ScrollArea } from "@/components/ui/scroll-area";

/* -------------------------------------------------------------------------- */
/* PROPS & EMIT */
/* -------------------------------------------------------------------------- */

const props = defineProps<{
    collapsed: boolean;
    mobileOpen: boolean;
    isMobile: boolean;
}>();

const emit = defineEmits<{
    (e: "close-mobile"): void;
}>();

const page = usePage<PageProps>();

/* -------------------------------------------------------------------------- */
/* ROLE & MENUS */
/* -------------------------------------------------------------------------- */

const normalizePath = (path: string) => {
    const clean = path.split("?")[0].split("#")[0];
    if (clean.length > 1 && clean.endsWith("/")) {
        return clean.slice(0, -1);
    }
    return clean;
};

const currentPath = computed(() => normalizePath(page.url));

const roleFromPath = computed<RoleKey | "">(() => {
    if (currentPath.value.startsWith("/admin")) return "admin";
    if (currentPath.value.startsWith("/juri")) return "juri";
    if (currentPath.value.startsWith("/peserta")) return "peserta";
    return "";
});

const role = computed<RoleKey | "">(() => {
    const roleValue =
        page.props.auth?.role?.toLowerCase() ?? roleFromPath.value;
    if (
        roleValue === "admin" ||
        roleValue === "juri" ||
        roleValue === "peserta"
    ) {
        return roleValue;
    }
    return "";
});

const menus = computed<MenuItem[]>(() => {
    if (!role.value) return [];
    return roleMenus[role.value] ?? [];
});

/* -------------------------------------------------------------------------- */
/* ACTIVE STATE */
/* -------------------------------------------------------------------------- */

const isActive = (path: string) => currentPath.value === normalizePath(path);

const isMenuActive = (path: string) => {
    const normalized = normalizePath(path);
    if (currentPath.value === normalized) return true;

    // Prevent root role dashboard path (e.g. /peserta) from matching all children.
    const depth = normalized.split("/").filter(Boolean).length;
    if (depth <= 1) return false;

    return currentPath.value.startsWith(`${normalized}/`);
};

const isChildActive = (children: NonNullable<MenuItem["children"]>) =>
    children.some((child) => isMenuActive(child.href));

/* -------------------------------------------------------------------------- */
/* DROPDOWN STATE */
/* -------------------------------------------------------------------------- */

const openMenus = ref<string[]>([]);
const openCollapsedMenu = ref<string | null>(null);

watch(
    [menus, currentPath],
    () => {
        menus.value.forEach((menu) => {
            const children = menu.children ?? [];
            if (
                children.length > 0 &&
                children.some((child) => isMenuActive(child.href))
            ) {
                if (!openMenus.value.includes(menu.label)) {
                    openMenus.value.push(menu.label);
                }
            }
        });
    },
    { immediate: true },
);

const toggleMenu = (label: string) => {
    if (openMenus.value.includes(label)) {
        openMenus.value = openMenus.value.filter((l) => l !== label);
    } else {
        openMenus.value.push(label);
    }
};
const toggleCollapsedMenu = (label: string, open: boolean) => {
    openCollapsedMenu.value = open ? label : null;
};

watch(currentPath, () => {
    openCollapsedMenu.value = null;
});

watch(
    () => props.collapsed,
    (val) => {
        if (!val) {
            openCollapsedMenu.value = null;
        }
    },
);

/* -------------------------------------------------------------------------- */
/* CLASS HELPER */
/* -------------------------------------------------------------------------- */

const isCollapsed = computed(() => props.collapsed && !props.isMobile);
const dividerBefore = new Set([
    "Edisi Tahun",
    "Submission",
    "Penjurian",
    "Manajemen User",
]);

const daftarEdisi = computed(() => page.props.edisi?.daftar ?? []);
const edisiAktif = computed(() => page.props.edisi?.aktif ?? null);
const edisiDipilih = ref<string>(
    edisiAktif.value ? String(edisiAktif.value.id) : "",
);

watch(
    edisiAktif,
    (val) => {
        edisiDipilih.value = val ? String(val.id) : "";
    },
    { immediate: true },
);

const gantiEdisi = (value: string) => {
    if (!value) return;
    router.post(
        "/konteks/edisi",
        { edisi_id: Number(value) },
        {
            onSuccess: () => {
                router.reload();
            },
        },
    );
};

const linkClass = (path: string) => [
    "relative flex items-center gap-3 rounded-lg text-sm font-medium transition-all duration-150",
    isCollapsed.value ? "justify-center px-2 py-2.5" : "px-3 py-2.5",
    isMenuActive(path)
        ? "bg-blue-50 text-blue-700"
        : "text-slate-600 hover:bg-slate-100 hover:text-slate-900",
];

const isParentActive = (menu: MenuItem) => {
    const children = menu.children ?? [];
    if (menu.href && isMenuActive(menu.href)) return true;
    if (children.length === 0) return false;
    return isChildActive(children);
};
</script>

<template>
    <aside
        :class="[
            'bg-white border-r border-slate-200 h-screen lg:sticky lg:top-0 flex flex-col transition-all duration-300 ease-in-out overflow-hidden min-h-0',
            isMobile
                ? mobileOpen
                    ? 'fixed left-0 top-0 w-64 z-50 shadow-xl'
                    : 'fixed -left-64 top-0 w-64 z-50'
                : collapsed
                  ? 'w-20'
                  : 'w-64',
        ]"
    >
        <!-- ===================== -->
        <!-- LOGO -->
        <!-- ===================== -->
        <div
            class="h-16 flex flex-col justify-between border-b border-slate-100"
        >
            <div
                :class="[
                    'flex items-center transition-all duration-300 h-full',
                    collapsed && !isMobile
                        ? 'justify-center px-0'
                        : 'px-6 gap-3',
                ]"
            >
                <img
                    :src="page.props.app?.logo || '/favicon.ico'"
                    alt="GEMASI"
                    class="w-10 h-10 object-cover"
                />

                <div v-if="!collapsed || isMobile" class="flex-1 min-w-0">
                    <Select
                        v-if="daftarEdisi.length > 1"
                        v-model="edisiDipilih"
                        @update:model-value="gantiEdisi"
                    >
                        <SelectTrigger class="w-full h-9 bg-white">
                            <SelectValue placeholder="Pilih edisi" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="edisi in daftarEdisi"
                                :key="edisi.id"
                                :value="String(edisi.id)"
                            >
                                {{ edisi.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span
                        v-else
                        class="text-base font-semibold text-slate-800 tracking-tight whitespace-nowrap"
                    >
                        GEMASI
                    </span>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- MENU -->
        <!-- ===================== -->
        <ScrollArea class="flex-1 min-h-0">
            <nav :class="['space-y-1 mt-4 pb-4', isCollapsed ? 'px-2' : 'px-3']">
                <TooltipProvider :delay-duration="150">
                    <template v-for="(menu, index) in menus" :key="menu.label">
                    <!-- ====================== -->
                    <!-- MENU WITH CHILDREN -->
                    <!-- ====================== -->
                    <div v-if="(menu.children ?? []).length > 0">
                        <!-- Parent -->
                        <template v-if="isCollapsed && !isMobile">
                            <Popover
                                :open="openCollapsedMenu === menu.label"
                                @update:open="
                                    (val) =>
                                        toggleCollapsedMenu(menu.label, val)
                                "
                            >
                                <PopoverTrigger as-child>
                                    <button
                                        :class="[
                                            'relative w-full flex items-center justify-center rounded-lg text-sm font-medium transition-all duration-200 px-2 py-2.5',
                                            isParentActive(menu)
                                                ? 'bg-blue-50 text-blue-700'
                                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                        ]"
                                    >
                                        <span
                                            v-if="isParentActive(menu)"
                                            class="absolute left-0 top-1 bottom-1 w-1 rounded-r bg-blue-600"
                                        />
                                        <component
                                            :is="menu.icon"
                                            class="w-5 h-5"
                                        />
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent
                                    side="right"
                                    align="start"
                                    class="w-56 p-0"
                                >
                                    <div
                                        class="rounded-xl border border-slate-200 bg-white shadow-lg p-3"
                                    >
                                        <div
                                            class="text-xs font-semibold text-slate-500 mb-2"
                                        >
                                            {{ menu.label }}
                                        </div>
                                        <div class="relative pl-4 space-y-1">
                                            <span
                                                class="absolute left-3 top-4 bottom-4 w-px bg-slate-200"
                                            />
                                            <Link
                                                v-for="child in menu.children ??
                                                []"
                                                :key="child.label"
                                                :href="child.href"
                                                class="group relative flex items-center gap-2 rounded-md pl-7 pr-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                                                @click="
                                                    openCollapsedMenu = null
                                                "
                                            >
                                                <span
                                                    :class="[
                                                        'h-1.5 w-1.5 rounded-full absolute left-3 top-1/2 -translate-y-1/2',
                                                        isMenuActive(child.href)
                                                            ? 'bg-blue-600'
                                                            : 'bg-slate-300 group-hover:bg-blue-500',
                                                    ]"
                                                />
                                                <span
                                                    :class="[
                                                        isMenuActive(child.href)
                                                            ? 'text-blue-700 font-medium'
                                                            : '',
                                                    ]"
                                                >
                                                    {{ child.label }}
                                                </span>
                                            </Link>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </template>
                        <Tooltip v-else>
                            <TooltipTrigger as-child>
                                <button
                                    @click="
                                        !collapsed && toggleMenu(menu.label)
                                    "
                                    :class="[
                                        'relative w-full flex items-center justify-between rounded-lg text-sm font-medium transition-all duration-200',
                                        isCollapsed
                                            ? 'px-2 py-2.5 justify-center'
                                            : 'px-3 py-2.5',
                                        isParentActive(menu)
                                            ? 'bg-blue-50 text-blue-700'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                    ]"
                                >
                                    <span
                                        v-if="isParentActive(menu)"
                                        class="absolute left-0 top-1 bottom-1 w-1 rounded-r bg-blue-600"
                                    />

                                    <div
                                        :class="[
                                            'flex items-center gap-3',
                                            isCollapsed
                                                ? 'w-full justify-center gap-0'
                                                : '',
                                        ]"
                                    >
                                        <component
                                            :is="menu.icon"
                                            class="w-5 h-5"
                                        />
                                        <span v-if="!collapsed || isMobile">
                                            {{ menu.label }}
                                        </span>
                                    </div>

                                    <ChevronDown
                                        v-if="!collapsed || isMobile"
                                        :class="[
                                            'w-6 h-6 transition-transform duration-300',
                                            openMenus.includes(menu.label)
                                                ? 'rotate-180'
                                                : '',
                                        ]"
                                    />
                                </button>
                            </TooltipTrigger>

                            <!-- Tooltip saat collapsed -->
                            <TooltipContent
                                v-if="collapsed && !isMobile"
                                side="right"
                            >
                                {{ menu.label }}
                            </TooltipContent>
                        </Tooltip>

                        <!-- Children -->
                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-40 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-40 opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div
                                v-show="
                                    openMenus.includes(menu.label) &&
                                    (!collapsed || isMobile)
                                "
                                class="ml-2 mt-1 space-y-1 overflow-hidden relative pl-4"
                            >
                                <span
                                    class="absolute left-3 top-2 bottom-1 w-px bg-slate-200"
                                />
                                <Link
                                    v-for="child in menu.children ?? []"
                                    :key="child.label"
                                    :href="child.href"
                                    :class="[
                                        linkClass(child.href),
                                        'text-[13px] py-2 relative pl-6',
                                        isMenuActive(child.href)
                                            ? 'bg-blue-50 text-blue-700'
                                            : '',
                                    ]"
                                    @click="isMobile && emit('close-mobile')"
                                >
                                    <span
                                        :class="[
                                            'absolute left-3 top-1/2 -translate-y-1/2 h-1.5 w-1.5 rounded-full',
                                            isMenuActive(child.href)
                                                ? 'bg-blue-600'
                                                : 'bg-slate-300',
                                        ]"
                                    />
                                    {{ child.label }}
                                </Link>
                            </div>
                        </transition>
                    </div>

                    <!-- ====================== -->
                    <!-- NORMAL MENU -->
                    <!-- ====================== -->
                    <Tooltip v-else-if="menu.href">
                        <TooltipTrigger as-child>
                            <Link
                                :href="menu.href"
                                :class="linkClass(menu.href)"
                                @click="isMobile && emit('close-mobile')"
                            >
                                <span
                                    v-if="isMenuActive(menu.href)"
                                    class="absolute left-0 top-1 bottom-1 w-1 rounded-r bg-blue-600"
                                />
                                <component :is="menu.icon" class="w-5 h-5" />

                                <span v-if="!collapsed || isMobile">
                                    {{ menu.label }}
                                </span>
                            </Link>
                        </TooltipTrigger>

                        <TooltipContent
                            v-if="collapsed && !isMobile"
                            side="right"
                        >
                            {{ menu.label }}
                        </TooltipContent>
                    </Tooltip>
                    </template>
                </TooltipProvider>
            </nav>
        </ScrollArea>
    </aside>
</template>
