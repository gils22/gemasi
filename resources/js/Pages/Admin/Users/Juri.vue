<script setup lang="ts">
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import DataTable from "@/components/common/DataTable.vue";
import UserFormModal from "./UserFormModal.vue";
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Plus, Eye } from "lucide-vue-next";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { ref, computed, nextTick } from "vue";
import { toast } from "vue-sonner";
import type { PageProps } from "@/types/inertia";

/* =========================
STATE
========================= */
const openCreate = ref(false);
const selectedUser = ref<any | null>(null);
const selectedUserValue = computed(() => selectedUser.value);

/* =========================
TYPE
========================= */
type Role = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    roles: Role[];
    juri_kategori_tahap_1_ids?: number[];
    juri_kategori_tahap_2_ids?: number[];
    juri_tahap?: string | null;
};

/* =========================
DATA FROM BACKEND
========================= */
const page = usePage<
    PageProps & {
        users: User[];
        kategoriOptions: { id: number; nama: string }[];
        dosenOptions?: Array<{
            id: number;
            nik?: string | null;
            nama: string;
            email: string;
            bidang?: string | null;
        }>;
    }
>();
const users = computed(() => page.props.users);
const kategoriOptions = computed(() => page.props.kategoriOptions ?? []);
const dosenOptions = computed(() => page.props.dosenOptions ?? []);
const kategoriMap = computed(() => {
    const map = new Map<number, string>();
    for (const item of kategoriOptions.value) {
        map.set(item.id, item.nama);
    }
    return map;
});
const filteredUsers = computed(() => {
    return users.value.filter((user) =>
        user.roles.some((role) => role.name === "juri")
    );
});

/* =========================
HANDLE VIEW (EDIT)
========================= */
const handleView = (row: any) => {
    openCreate.value = false;
    selectedUser.value = null;
    nextTick(() => {
        selectedUser.value = { ...row };
        openCreate.value = true;
    });
};

/* =========================
HANDLE CREATE
========================= */
const handleCreate = () => {
    selectedUser.value = null;
    openCreate.value = true;
};

/* =========================
BULK DELETE
========================= */
const handleBulkDelete = (ids: number[]) => {
    if (!ids.length) return;

    toast.warning("Yakin ingin menghapus data?", {
        description: `${ids.length} user akan dihapus.`,
        action: {
            label: "Ya, Hapus",
            onClick: () => {
                router.delete("/admin/users/bulk-delete", {
                    data: { ids },
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success("User berhasil dihapus");
                    },
                    onError: () => {
                        toast.error("Gagal menghapus user");
                    },
                });
            },
        },
        cancel: {
            label: "Batal",
            onClick: () => {},
        },
    });
};

/* =========================
COLUMNS
========================= */
const columns = [
    {
        key: "name",
        label: "Nama",
        sortable: true,
    },
    {
        key: "email",
        label: "Email",
    },
    {
        key: "juri_kategori",
        label: "Kategori Penilaian",
    },
];

/* =========================
LAYOUT
========================= */
defineOptions({
    layout: (h, page) =>
        h(DashboardLayout, { title: "Juri" }, () => page),
});
</script>

<template>
    <DataTable
        :columns="columns"
        :data="filteredUsers"
        :withAction="true"
        :showBulkDelete="true"
        @bulk-delete="handleBulkDelete"
    >
        <template #toolbar-right>
            <Button @click="handleCreate">
                <Plus class="w-4 h-4" />
                Tambah
            </Button>
        </template>
        <!-- CUSTOM CELL: NAME -->
        <template #name="{ row }: { row: User }">
            <div class="flex items-center gap-3">
                <Avatar class="h-8 w-8">
                    <AvatarImage
                        :src="
                            row.avatar ??
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(
                                row.name
                            )}`
                        "
                    />
                    <AvatarFallback>
                        {{ row.name.charAt(0).toUpperCase() }}
                    </AvatarFallback>
                </Avatar>

                {{ row.name }}
            </div>
        </template>

        <template #juri_kategori="{ row }: { row: User }">
            <div class="flex flex-wrap gap-1">
                <template v-if="row.roles?.some((role) => role.name === 'juri')">
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="text-[11px] font-medium text-slate-500">
                                Tahap 1
                            </span>
                            <span
                                v-for="kategoriId in row.juri_kategori_tahap_1_ids ?? []"
                                :key="`t1-${kategoriId}`"
                                class="px-2 py-1 text-xs rounded-full bg-sky-50 text-sky-700"
                            >
                                {{
                                    kategoriMap.get(kategoriId) ??
                                    `Kategori #${kategoriId}`
                                }}
                            </span>
                            <span
                                v-if="!(row.juri_kategori_tahap_1_ids ?? []).length"
                                class="text-xs text-slate-400"
                            >
                                -
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="text-[11px] font-medium text-slate-500">
                                Tahap 2
                            </span>
                            <span
                                v-for="kategoriId in row.juri_kategori_tahap_2_ids ?? []"
                                :key="`t2-${kategoriId}`"
                                class="px-2 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700"
                            >
                                {{
                                    kategoriMap.get(kategoriId) ??
                                    `Kategori #${kategoriId}`
                                }}
                            </span>
                            <span
                                v-if="!(row.juri_kategori_tahap_2_ids ?? []).length"
                                class="text-xs text-slate-400"
                            >
                                -
                            </span>
                        </div>
                    </div>
                </template>
                <span v-else class="text-xs text-slate-500">-</span>
            </div>
        </template>

        <template #actions="{ row }">
            <div class="flex justify-end">
                <TooltipProvider :delay-duration="150">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon"
                                variant="ghost"
                                class="hidden md:inline-flex"
                                @click="handleView(row)"
                            >
                                <Eye class="w-4 h-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent side="bottom">Detail</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <Button
                    variant="outline"
                    size="sm"
                    class="md:hidden"
                    @click="handleView(row)"
                >
                    <Eye class="w-4 h-4" />
                    Lihat
                </Button>
            </div>
        </template>
    </DataTable>

    <UserFormModal
        v-model:open="openCreate"
        :user="selectedUserValue"
        :readonly="false"
        :kategoriOptions="kategoriOptions"
        :dosenOptions="dosenOptions"
    />
</template>

